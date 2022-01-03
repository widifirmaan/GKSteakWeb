<?php

require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_STORAGE_PATH.'BackupGuardStorage.php');

if(backupGuardIsAjax() && count($_POST)) {
	$_POST = backupGuardRemoveSlashes($_POST);
	$_POST = backupGuardSanitizeTextField($_POST);

	if (isset($_POST['profileId']) &&  $_POST['profileId']) {
		$profileId = $_POST['profileId'];
	}
	else {
		$profileName = @$_POST['profileName'];
		$bgStorage = new BackupGuard\Storage();
		$profileId = $bgStorage->createProfile($profileName);
	}

	SGConfig::set('BACKUP_GUARD_PROFILE_ID', $profileId);
	SGConfig::set('BACKUP_GUARD_CREATE_MASTER', 1);
	die('{"success":"success"}');
}