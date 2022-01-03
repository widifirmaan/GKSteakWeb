<?php
require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_BACKUP_PATH.'SGBackup.php');

if(backupGuardIsAjax() && count($_POST)) {
	$error = array();
	try {

		if (isset($_POST['backupName']) && $_POST['backupName']) {
			if (file_exists(SG_BACKUP_DIRECTORY.$_POST['backupName'])) {
				throw new SGExceptionForbidden($_POST['backupName']." backup already exists");
			}
		}

		@unlink(SG_BACKUP_DIRECTORY.'sg_backup.state');
		SGConfig::set('SG_RUNNING_ACTION', 0, true);
		$key = md5(microtime(true));
		SGConfig::set('SG_BACKUP_CURRENT_KEY', $key, true);
		die('{"success":1}');
	}
	catch(SGException $exception) {
		array_push($error, $exception->getMessage());
		die(json_encode($error));
	}
}
