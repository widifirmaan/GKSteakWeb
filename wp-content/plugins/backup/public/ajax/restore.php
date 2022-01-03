<?php
    require_once(dirname(__FILE__).'/../boot.php');
    require_once(SG_BACKUP_PATH.'SGBackup.php');
    if(backupGuardIsAjax() && count($_POST)) {
        $error = array();
        try {
            //Getting Backup Name
            $backupName = $_POST['bname'];
            $restoreMode = isset($_POST['type'])? $_POST['type'] : SG_RESTORE_MODE_FULL; //if type is not set that means it is an old backup and no selective restore is available. only full

            if (!SGBoot::isFeatureAvailable('SLECTIVE_RESTORE')) {
                $restoreMode = SG_RESTORE_MODE_FULL;
            }

			$restoreFiles = isset($_POST['paths']) ? $_POST['paths'] : array();
            $backup = new SGBackup();
            $backup->restore($backupName, $restoreMode, $restoreFiles);
        }
        catch(SGException $exception) {
            array_push($error, $exception->getMessage());
            die(json_encode($error));
        }
    }
