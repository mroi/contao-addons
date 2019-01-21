<?php
ClassLoader::addNamespace('Addons');
ClassLoader::addClasses(array(
	'Addons\CSVDownloader' => 'system/modules/addons/classes/CSVDownloader.php',
	'Addons\InternalLogMailer' => 'system/modules/addons/classes/InternalLogMailer.php',
	'Addons\NewsletterMultimail' => 'system/modules/addons/classes/NewsletterMultimail.php'
));
