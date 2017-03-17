<?php

class InternalLogMailer extends \System {

	public function __construct() {
		parent::__construct();
		$this->import('Database');
	}

	public function run() {
		$now = time();
		$lastRun = $now - 24 * 60 * 60;

		$timestamp = $this->Database->prepare("SELECT * FROM tl_cron WHERE name ='logmailer'")->limit(1)->execute();
		if ($timestamp->numRows > 0)
			$lastRun = $timestamp->value;
		else
			$this->Database->query("INSERT INTO tl_cron (name, value) VALUES ('logmailer', $lastRun)");

		$logEntry = $this->Database->query("SELECT * FROM tl_log WHERE " .
			"tstamp>$lastRun AND tstamp<=$now AND action!='CRON' ORDER BY tstamp");

		if ($logEntry->numRows > 0) {
			$mail = new \Email();
			$mail->from = "noreply@" . \Environment::get('host');
			$mail->fromName = "Contao Log Mailer";
			$mail->subject = "Log-Einträge für " . $GLOBALS['TL_CONFIG']['websiteTitle'];

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
				"<h1>" . $GLOBALS['TL_CONFIG']['websiteTitle'] . "</h1>\n" .
				"<p>" . date($GLOBALS['TL_CONFIG']['datimFormat'], $lastRun) . " – " . date($GLOBALS['TL_CONFIG']['datimFormat'], $now) . "</p>\n\n" .
				"<table><thead><tr><th>Zeit</th>\t<th>Benutzer</th>\t<th>Aktion</th></tr></thead><tbody>\n";
			while ($logEntry->next())
				$mail->html .= "<tr><td>" . date($GLOBALS['TL_CONFIG']['datimFormat'], $logEntry->tstamp) . "</td>\t<td>" . $logEntry->username . "</td>\t<td>" . $logEntry->text . "</td></tr>\n";
			$mail->html .= "</tbody></table></body></html>";

			$mail->text = strip_tags($mail->html);
			$mail->sendTo($GLOBALS['TL_CONFIG']['adminEmail']);
		}

		$this->Database->query("UPDATE tl_cron SET value=$now WHERE name='logmailer'");
	}
}
