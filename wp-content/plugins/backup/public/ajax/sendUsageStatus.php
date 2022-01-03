<?php
require_once(dirname(__FILE__).'/../boot.php');

if(backupGuardIsAjax() && count($_POST)) {

    $usageStatus = $_POST['currentStatus'] == "true" ? 1 : 0;
    SGConfig::set('SG_BACKUP_SEND_USAGE_STATUS', $usageStatus);
}
