<?php
$GLOBALS['TL_CRON']['daily'][] = array('InternalLogMailer', 'run');
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('CSVDownloader', 'loadDataContainer');
