<?php

//check PHP version
if (version_compare(PHP_VERSION, '5.3.3', '<')) {
    die('PHP >=5.3.3 version required.');
}

require_once SG_EXCEPTION_PATH . 'SGException.php';
require_once SG_CORE_PATH . 'functions.php';
backupGuardIncludeFile(SG_CORE_PATH . 'functions.silver.php');
backupGuardIncludeFile(SG_CORE_PATH . 'functions.gold.php');
backupGuardIncludeFile(SG_CORE_PATH . 'functions.platinum.php');
require_once SG_CORE_PATH . 'SGPing.php';
require_once SG_DATABASE_PATH . 'SGDatabase.php';
require_once SG_CORE_PATH . 'SGConfig.php';
require_once SG_NOTICE_PATH . 'SGNotice.php';
require_once SG_NOTICE_PATH . 'SGNoticeHandler.php';
backupGuardIncludeFile(SG_BACKUP_PATH . 'SGBackupSchedule.php');
backupGuardIncludeFile(SG_EXTENSION_PATH . 'SGExtension.php');

class SGBoot
{
    public static $executionTimeLimit = 0;
    public static $memoryLimit = 0;

    public static function init()
    {
        //get current execution time limit
        self::$executionTimeLimit = ini_get('max_execution_time');

        //get current memory limit
        self::$memoryLimit = ini_get('memory_limit');

        //remove execution time limit
        @ini_set('max_execution_time', 0);

        //change initial memory limit
        @ini_set('memory_limit', '512M');

        //don't let server to abort scripts
        @ignore_user_abort(true);

        //load all config variables from database
        SGConfig::getAll();

        // TODO: remove
        backupGuardMigrateDownloadMode();

        try {
            //check minimum requirements
            self::checkMinimumRequirements();

            //prepare directory for backups
            self::prepare();
        } catch (SGException $exception) {
            die($exception);
        }
    }

    public static function didInstallForFirstTime()
    {
        self::setPluginInstallUpdateDate();
    }

    public static function didUpdatePluginVersion()
    {
        self::setPluginInstallUpdateDate();
    }

    public static function setPluginInstallUpdateDate()
    {
        SGConfig::set('SG_PLUGIN_INSTALL_UPDATE_DATE', time());
    }

    private static function installConfigTable($sgdb)
    {
        $dbEngine     = backupGuardGetDatabaseEngine();
        $downloadMode = backupGuardCheckDownloadMode();
        //create config table
        $res = $sgdb->query(
            'CREATE TABLE IF NOT EXISTS `' . SG_CONFIG_TABLE_NAME . '` (
			  `ckey` varchar(100) NOT NULL,
			  `cvalue` text NOT NULL,
			  PRIMARY KEY (`ckey`)
			) ENGINE=' . $dbEngine . ' DEFAULT CHARSET=utf8;'
        );
        if ($res === false) {
            return false;
        }

        //delete all content from config table (just in case if wasn't dropped)
        $sgdb->query('DELETE FROM `' . SG_CONFIG_TABLE_NAME . '`;');

        //populate config table
        $res = $sgdb->query(
            "INSERT INTO `" . SG_CONFIG_TABLE_NAME . "` VALUES
			('SG_BACKUP_GUARD_VERSION','" . SG_BACKUP_GUARD_VERSION . "'),
			('SG_BACKUP_WITH_RELOADINGS', '1'),
			('SG_BACKUP_SYNCHRONOUS_STORAGE_UPLOAD','1'),
			('SG_NOTIFICATIONS_ENABLED','0'),
			('SG_SHOW_STATISTICS_WIDGET','1'),
			('SG_BACKUP_SEND_USAGE_STATUS','0'),
			('SG_NOTIFICATIONS_EMAIL_ADDRESS',''),
			('SG_STORAGE_BACKUPS_FOLDER_NAME','sg_backups'),
			('SG_DOWNLOAD_MODE'," . $downloadMode . ");"
        );
        if ($res === false) {
            return false;
        }

        return true;
    }

    private static function installScheduleTable($sgdb)
    {
        $dbEngine = backupGuardGetDatabaseEngine();

        //create schedule table
        $res = $sgdb->query(
            'CREATE TABLE IF NOT EXISTS `' . SG_SCHEDULE_TABLE_NAME . '` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `label` varchar(255) NOT NULL,
			  `status` tinyint(3) unsigned NOT NULL,
			  `schedule_options` varchar(255) NOT NULL,
			  `backup_options` text NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=' . $dbEngine . ' DEFAULT CHARSET=utf8;'
        );
        if ($res === false) {
            return false;
        }

        return true;
    }

    private static function installActionTable($sgdb)
    {
        $dbEngine = backupGuardGetDatabaseEngine();

        //create action table
        $res = $sgdb->query(
            "CREATE TABLE IF NOT EXISTS `" . SG_ACTION_TABLE_NAME . "` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(255) NOT NULL,
			  `type` tinyint(3) unsigned NOT NULL,
			  `subtype` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `status` tinyint(3) unsigned NOT NULL,
			  `progress` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `start_date` datetime NOT NULL,
			  `update_date` datetime DEFAULT NULL,
			  `options` text NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=" . $dbEngine . " DEFAULT CHARSET=utf8;"
        );
        if ($res === false) {
            return false;
        }

        return true;
    }

    public static function install()
    {
        $sgdb = SGDatabase::getInstance();

        try {
            if (!self::installConfigTable($sgdb)) {
                throw new SGExceptionDatabaseError('Could not install config table');
            }

            if (!self::installScheduleTable($sgdb)) {
                throw new SGExceptionDatabaseError('Could not install schedule table');
            }

            if (!self::installActionTable($sgdb)) {
                throw new SGExceptionDatabaseError('Could not install action table');
            }

            self::installReviewSettings();
            self::checkliteSpeedHosting();
        } catch (SGException $exception) {
            die($exception);
        }
    }

    private static function installReviewSettings()
    {
        $usageDays = SGConfig::get('usageDays');
        if (!$usageDays) {
            SGConfig::set('usageDays', 0);

            $timeDate    = new \DateTime('now');
            $installTime = strtotime($timeDate->format('Y-m-d H:i:s'));
            SGConfig::set('installDate', $installTime);
            $timeDate->modify('+' . SG_BACKUP_REVIEW_PERIOD . ' day');

            $timeNow = strtotime($timeDate->format('Y-m-d H:i:s'));
            SGConfig::set('openNextTime', $timeNow);
        }
        $backupCountReview = SGConfig::get('backupReviewCount');
        if (!$backupCountReview) {
            SGConfig::set('backupReviewCount', SG_BACKUP_REVIEW_BACKUP_COUNT);
        }

        $restoreReviewCount = SGConfig::get('restoreReviewCount');
        if (!$restoreReviewCount) {
            SGConfig::set('restoreReviewCount', SG_BACKUP_REVIEW_RESTORE_COUNT);
        }

        // TODO: remove and check banners logic
        SGConfig::set('closeReviewBanner', 1);
    }

    private static function checkliteSpeedHosting($delete = false)
    {
        if ($delete) {
            removeLiteSpeedHtaccessModule();
        } else {
            addLiteSpeedHtaccessModule();
        }
    }

    private static function cleanupSchedules()
    {
        $schedules = SGBackupSchedule::getAllSchedules();
        foreach ($schedules as $schedule) {
            SGBackupSchedule::remove($schedule['id']);
        }
    }

    public static function uninstall($deleteBackups = false)
    {
        try {
            @unlink(SG_PING_FILE_PATH);

            if (self::isFeatureAvailable('SCHEDULE')) {
                self::cleanupSchedules();
            }

            $sgdb = SGDatabase::getInstance();

            //drop config table
            $res = $sgdb->query('DROP TABLE IF EXISTS `' . SG_CONFIG_TABLE_NAME . '`;');
            if ($res === false) {
                throw new SGExceptionDatabaseError('Could not execute query');
            }

            //drop schedule table
            $res = $sgdb->query('DROP TABLE IF EXISTS `' . SG_SCHEDULE_TABLE_NAME . '`;');
            if ($res === false) {
                throw new SGExceptionDatabaseError('Could not execute query');
            }

            //drop action table
            $res = $sgdb->query('DROP TABLE IF EXISTS `' . SG_ACTION_TABLE_NAME . '`;');
            if ($res === false) {
                throw new SGExceptionDatabaseError('Could not execute query');
            }

            self::checkliteSpeedHosting(true);

            //delete directory of backups
            if ($deleteBackups) {
                $backupPath = SGConfig::get('SG_BACKUP_DIRECTORY');
                backupGuardDeleteDirectory($backupPath);
            }
        } catch (SGException $exception) {
            die($exception);
        }
    }

    public static function checkRequirement($requirement)
    {
        if ($requirement == 'ftp' && !extension_loaded('ftp')) {
            throw new SGExceptionNotFound('FTP extension is not loaded.');
        } else if ($requirement == 'curl' && !function_exists('curl_version')) {
            throw new SGExceptionNotFound('cURL extension is not loaded.');
        } else if ($requirement == 'intSize' && PHP_INT_SIZE < 8) {
            throw new SGExceptionIO("BackupGuard uses 64-bit integers, but it looks like we're running on a version of PHP that doesn't support 64-bit integers (PHP_INT_MAX=" . ((string) PHP_INT_MAX) . ")");
        }
    }

    public static function isFeatureAvailable($feature)
    {
        return ((int) SGConfig::get('SG_FEATURE_' . strtoupper($feature)) === 1 ? true : false);
    }

    private static function prepare()
    {
        $backupPath = SGConfig::get('SG_BACKUP_DIRECTORY');

        //create directory for backups
        if (!is_dir($backupPath)) {
            if (!@mkdir($backupPath)) {
                throw new SGExceptionMethodNotAllowed('Cannot create folder: ' . $backupPath);
            }

            if (!@file_put_contents($backupPath . '.htaccess', 'deny from all')) {
                throw new SGExceptionMethodNotAllowed('Cannot create htaccess file');
            }

            if (!@file_put_contents($backupPath . 'index.php', "<?php\n// Silence is golden")) {
                throw new SGExceptionMethodNotAllowed('Cannot create index file');
            }
        }

        //check permissions of backups directory
        if (!is_writable($backupPath)) {
            throw new SGExceptionForbidden('Permission denied. Directory is not writable: ' . $backupPath);
        }

        //prepare notices
        $noticeHandler = new SGNoticeHandler();
        $noticeHandler->run();
    }

    private static function checkMinimumRequirements()
    {
        //check ZLib library
        if (!function_exists('gzdeflate')) {
            throw new SGExceptionNotFound('ZLib extension is not loaded.');
        }
    }
}
