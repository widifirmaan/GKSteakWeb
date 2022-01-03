<?php
require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_BACKUP_PATH.'SGBackup.php');

if ($id) {
	$allActions = SGBackup::getRunningActions();
	if (count($allActions)) { // abort any other backup if there is an active action
		die();
	}

	$b = new SGBackup();
	$b->setIsManual(false);
	$options = $b->getScheduleParamsById($id);

	if ($options) {
		$b->backup(json_decode($options['backup_options'], true));
	}
}
