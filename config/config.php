<?php
$GLOBALS['TL_CRON']['daily'][] = array('InternalLogMailer', 'run');
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('CSVDownloader', 'loadDataContainerHook');
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('NewsletterMultimail', 'loadDataContainerHook');
if (isset($GLOBALS['BE_MOD']['content']['newsletter']['send']))
	$GLOBALS['BE_MOD']['content']['newsletter']['send'] = array('NewsletterMultimail', 'send');
else
	\System::log('Could not override newsletter send operation to use class "NewsletterMultimail"', __METHOD__, TL_ERROR);
