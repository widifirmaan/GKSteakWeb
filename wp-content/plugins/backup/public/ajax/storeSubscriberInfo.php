<?php

require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_LIB_PATH.'BackupGuard/Client.php');
require_once(dirname(__FILE__).'/sendUsageStatus.php');

if(backupGuardIsAjax() && count($_POST)) {
	$_POST = backupGuardRemoveSlashes($_POST);
	$_POST = backupGuardSanitizeTextField($_POST);

	$error = '';
	$firstName = $_POST['fname'];
	$lastName = $_POST['lname'];
	$email = $_POST['email'];
	$priority = $_POST['priority'];
	$url = site_url();

	$client = new BackupGuard\Client();
	$id = $client->storeSubscriberInfo($url, $firstName, $lastName, $email, $priority);

	if ($id) {
		SGConfig::set('SG_HIDE_VERIFICATION_POPUP_STATE', 1);
		die('0');
	}

	die('"'.$error.'"');
}
