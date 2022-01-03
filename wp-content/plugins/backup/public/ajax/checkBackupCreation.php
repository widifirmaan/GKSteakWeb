<?php
	require_once(dirname(__FILE__).'/../boot.php');
	require_once(SG_BACKUP_PATH.'SGBackup.php');
	if (backupGuardIsAjax()) {
		$timeout = 10; //in sec
		while ($timeout != 0) {
			sleep(1);
			$timeout--;
			
			$created = SGConfig::get('SG_RUNNING_ACTION', true);
			if ($created) {
				die((1));
			}
			
			$runningActions = SGBackup::getRunningActions();
			
			if (empty($runningActions)) {
				die('{"status":1}');
			}
		}
		
		$runningActions = SGBackup::getRunningActions();
		if (!empty($runningActions)) {
			// when there are multiple uncompleted actions
			if ($runningActions && count($runningActions) == 1 && $runningActions[0]['progress'] == 0) {
				SGBackup::cleanRunningActions($runningActions);
				die(json_encode(array(
					'status' => 'cleaned'
				)));
			}
		}
		
		die('{"status":1}');
	}
