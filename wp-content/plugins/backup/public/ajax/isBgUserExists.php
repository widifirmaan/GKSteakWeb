<?php

require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_LIB_PATH.'BackupGuard/Client.php');

if(backupGuardIsAjax() && count($_POST)) {
	$_POST = backupGuardRemoveSlashes($_POST);
	$_POST = backupGuardSanitizeTextField($_POST);
	$messages = array();

	$email = $_POST['email'];
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$messages['validationError'] = 'error';
		$messages['message'] = 'Invalid email';
		echo json_encode($messages);
		die();
	}

	$client = new BackupGuard\Client();
	$found = $client->checkEmailExists($email);

	if (!$found) {
		die('{"user":"notFound"}');
	}

	die('{"success":"success"}');
}