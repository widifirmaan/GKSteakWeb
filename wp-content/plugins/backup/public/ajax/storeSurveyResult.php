<?php

require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_LIB_PATH.'BackupGuard/Client.php');

if(backupGuardIsAjax() && count($_POST)) {
	$_POST = backupGuardRemoveSlashes($_POST);
	$_POST = backupGuardSanitizeTextField($_POST);

	$error = '';

	if (isset($_POST['skip'])) {
		$firstname = 'skip';
		$lastname = 'skip';
		$email = 'skip';
		$response = 'skip';
		$url = site_url();
	}
	else {
		$firstname = $_POST['firstname'];
		$lastname = $_POST['lastname'];
		$email = $_POST['email'];
		$response = $_POST['response'];
		$url = site_url();
	}

	$client = new BackupGuard\Client();
	$id = $client->storeSurveyResult($url, $firstname, $lastname, $email, $response);

	if ($id) {
		die('{"success":"success"}');
	}

	die('"'.$error.'"');
}
