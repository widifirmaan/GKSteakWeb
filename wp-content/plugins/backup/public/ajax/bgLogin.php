<?php

require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_STORAGE_PATH.'BackupGuardStorage.php');
require_once(SG_LIB_PATH.'SGAuthClient.php');

function BackupGuardLogouCloudUser() {
	SGConfig::set('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN', '');
	SGConfig::set('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN_EXPIRES','');
	SGConfig::set('SG_BACKUPGUARD_UPLOAD_REFRESH_TOKEN','');
	SGConfig::set('SG_BACKUPGUARD_CLOUD_ACCOUNT', '');
	SGConfig::set('SG_BACKUPGUARD_CLOUD_ACCOUNT_EMAIL', '');
	SGConfig::set('BACKUP_GUARD_PROFILE_ID', '');
}
if(backupGuardIsAjax() && count($_POST)) {
	$_POST = backupGuardRemoveSlashes($_POST);
	$_POST = backupGuardSanitizeTextField($_POST);

	if(isset($_POST['cancel'])) {

		BackupGuardLogouCloudUser();
		die('{"success":"success"}');
	}

	try {
		BackupGuardLogouCloudUser();
		$email = $_POST['email'];
		$password = sha1($_POST['password']);

		if (!$email || !$password) {
			die('{"error":"Invalid arguments"}');
		}
		
		$bgStorage = new BackupGuard\Storage();
		
		$accessToken = $bgStorage->connect($email, $password, true);
		if ($accessToken) {
			try {
				$account = $bgStorage->checkCloudAccount();
				SGConfig::set('SG_BACKUPGUARD_CLOUD_ACCOUNT', serialize($account));
			}
			catch(Exception $exp) {
				$bgStorage->addCloudAccountToUser();
			}

			$bgStorage->connect($email, $password);	
			$profiles = $bgStorage->getProfiles();
		}
	}
	catch(Exception $exp) {
		die('{"error":"'.$exp->getMessage().'"}');
	}

	SGConfig::set('SG_BACKUPGUARD_CLOUD_ACCOUNT_EMAIL', $email);
	die(json_encode(array(
		'profiles' => $profiles
	)));
}