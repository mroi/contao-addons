<?php
$GLOBALS['TL_CRON']['daily'][] = array('Mroi\ContaoAddons\Utilities\LogMailer', 'run');
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('Mroi\ContaoAddons\Utilities\CSVDownloader', 'loadDataContainerHook');
$GLOBALS['TL_HOOKS']['loadLanguageFile'][] = array('Mroi\ContaoAddons\Utilities\CSVDownloader', 'loadLanguageFileHook');
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('Mroi\ContaoAddons\Newsletter\Multimail', 'loadDataContainerHook');
