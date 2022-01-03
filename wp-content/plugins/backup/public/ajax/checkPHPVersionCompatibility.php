<?php

require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_LIB_PATH.'SGArchive.php');

if(backupGuardIsAjax() && count($_POST)) {
	try {
		$name = $_POST['bname'];
		$name = backupGuardRemoveSlashes($name);
		$path = SG_BACKUP_DIRECTORY.$name.'/'.$name.'.sgbp';

		$sgArchive = new SGArchive($path, 'r');
		$headers = $sgArchive->getArchiveHeaders();

		if (isset($headers['phpVersion'])) {
			$oldPHPVersion = $headers['phpVersion'];
			$currentVersion = phpversion();

			// Drop the last digits of version (e.g. 5.3.3 will be 5) by explicit casting from string to int. This will check the migrations like php 5.x.x -> 7.x.x
			if ((int)$oldPHPVersion != (int)$currentVersion) {
				die(json_encode(array(
					'warning' => 'Warning: The backup has been captured for php '.$oldPHPVersion.' whereas your server is running php '.$currentVersion.'. If you’re sure the website is compatible with php '.$currentVersion.', please confirm to start the restoration.'
				)));
			}
		}

		die(json_encode(array()));
	}
	catch(Exception $e) {
		die(json_encode(array(
			'error' => $e->getMessage()
		)));
	}
}
