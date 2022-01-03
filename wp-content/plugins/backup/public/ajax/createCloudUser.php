<?php

require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_LIB_PATH.'BackupGuard/Client.php');
require_once(SG_LIB_PATH.'SGAuthClient.php');

if(backupGuardIsAjax() && count($_POST)) {
	$_POST = backupGuardRemoveSlashes($_POST);
	$_POST = backupGuardSanitizeTextField($_POST);

	$email = $_POST['email'];
	$firstname = $_POST['firstname'];
	$lastname = $_POST['lastname'];

	$client = new BackupGuard\Client();
	try {
		$user = $client->createCloudUser($email, $firstname, $lastname);
		if ($user) {
			$email = $user['email'];
			$password = $user['password'];
			
			$auth = SGAuthClient::getInstance();
			$auth->createUploadAccessToken($email, $password);
		}
	}
	catch (Exception $exp) {
		die('{"error":"error"}');
	}

	die('{"success":"success"}');
}