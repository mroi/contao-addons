<?php
namespace Mroi\ContaoAddons\Utilities;

use Contao\CoreBundle\Repository\CronJobRepository;


class LogMailer extends \Contao\System {

	private $cronJobs;
	private $lastRun;

	public function __construct(CronJobRepository $cronJobs) {
		$job = $cronJobs->findOneByName(get_class());
		$this->cronJobs = $cronJobs;
		$this->lastRun = $job ? $job->getLastRun()->getTimestamp() : null;
		parent::__construct();
		$this->import('Database');
	}

	public function __invoke(): void {
		$job = $this->cronJobs->findOneByName(get_class());
		$currentRun = $job->getLastRun()->getTimestamp();

		if (!$this->lastRun) $this->lastRun = $currentRun;

		$logEntries = $this->Database->query("SELECT * FROM tl_log WHERE " .
			"tstamp>{$this->lastRun} AND tstamp<=$currentRun AND action!='CRON' ORDER BY tstamp");

		if ($logEntries->numRows > 0) {
			$page = \Contao\PageModel::findPublishedRootPages(array('order' => 'sorting'))[0];
			$title = $page->pageTitle ?? 'Website';

			$mail = new \Contao\Email();
			$mail->from = "noreply@" . \Contao\Environment::get('host');
			$mail->fromName = "Contao Log Mailer";
			$mail->subject = "Log-Einträge für " . $title;

			$mail->html =
				"<!DOCTYPE html>" .
				"<html>" .
				"<head><style type=\"text/css\">" .
				"body{text-rendering:optimizeLegibility;}" .
				"a{color:#000;text-decoration:none;}" .
				"h1{margin-bottom:0;font-size:1.3em;text-transform:uppercase;}" .
				"p{margin-top:0;margin-bottom:1em;font-size:1.3em;}" .
				"table{border-collapse:collapse;}" .
				"thead tr{color:#fff;background-color:#e4790f;}" .
				"tbody tr:nth-child(odd){background-color:#fef7f1;}" .
				"tbody tr:nth-child(even){background-color:#fce9d9;}" .
				"th{text-align:left}" .
				"th,td{padding:2px 1ex 2px 1ex;}" .
				"</style></head>" .
				"<body>" .
				"<h1>" . $title . "</h1>\n" .
				"<p>" . date($GLOBALS['TL_CONFIG']['datimFormat'], $this->lastRun) . " – " . date($GLOBALS['TL_CONFIG']['datimFormat'], $currentRun) . "</p>\n\n" .
				"<table><thead><tr><th>Zeit</th>\t<th>Benutzer</th>\t<th>Aktion</th></tr></thead><tbody>\n";
			while ($logEntries->next())
				$mail->html .= "<tr><td>" . date($GLOBALS['TL_CONFIG']['datimFormat'], $logEntries->tstamp) . "</td>\t<td>" . $logEntries->username . "</td>\t<td>" . $logEntries->text . "</td></tr>\n";
			$mail->html .= "</tbody></table></body></html>";

			$mail->text = strip_tags($mail->html);
			$mail->sendTo($GLOBALS['TL_CONFIG']['adminEmail']);
		}
	}
}
