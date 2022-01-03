<?php

require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_LIB_PATH.'BackupGuard/Client.php');

if(backupGuardIsAjax() && count($_POST)) {

	$url = 'none';
	$firstName = 'none';
	$lastName = 'none';
	$email = 'none';
	$priority = 'none';

	$client = new BackupGuard\Client();
	$id = $client->storeSubscriberInfo($url, $firstName, $lastName, $email, $priority);

	SGConfig::set('SG_HIDE_VERIFICATION_POPUP_STATE', 1);
	die('0');
}
