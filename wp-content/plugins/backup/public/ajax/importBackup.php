<?php

require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_BACKUP_PATH.'SGBackup.php');
require_once(SG_LIB_PATH.'SGUploadHandler.php');

$error = array();
$success = array('success'=>1);

try {

	if (!current_user_can('administrator')) {
		die();
	}

	$sgUploadHandler = new BackupGuard\Upload\Handler($_FILES);
}
catch (SGException $exception) {
	array_push($error, $exception->getMessage());
	die(json_encode($error));
}

echo json_encode($success);
die();
