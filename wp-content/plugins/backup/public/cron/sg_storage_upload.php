<?php
require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_BACKUP_PATH.'SGBackupStorage.php');

SGBackupStorage::getInstance()->startNextUploadInQueue();