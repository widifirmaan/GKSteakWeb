<?php

require_once dirname(__FILE__) . '/../boot.php';
require_once SG_BACKUP_PATH . 'SGBackupSchedule.php';

$error   = array();
$success = array('success' => 1);

if (backupGuardIsAjax() && isset($_POST['cancel'])) {
    SGConfig::set('SG_NOTIFICATIONS_ENABLED', '0');
    SGConfig::set('SG_NOTIFICATIONS_EMAIL_ADDRESS', '');

    die(json_encode($success));
}

if (backupGuardIsAjax() && count($_POST)) {
    $_POST = backupGuardRemoveSlashes($_POST);
    $_POST = backupGuardSanitizeTextField($_POST);

    $amountOfBackupsToKeep = (int) @$_POST['amount-of-backups-to-keep'];
    if ($amountOfBackupsToKeep <= 0) {
        $amountOfBackupsToKeep = SG_NUMBER_OF_BACKUPS_TO_KEEP;
    }
    SGConfig::set('SG_AMOUNT_OF_BACKUPS_TO_KEEP', $amountOfBackupsToKeep);

    SGConfig::set('SG_NOTIFICATIONS_ENABLED', '0');
    $emails = '';
    if (isset($_POST['sgIsEmailNotification'])) {
        $emails      = @$_POST['sgUserEmail'];
        $emailsArray = explode(',', $emails);

        if (empty($emails)) {
            array_push($error, _backupGuardT('Email is required.', true));
        }

        foreach ($emailsArray as $email) {
            $email = trim($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                array_push($error, _backupGuardT('Invalid email address.', true));
            }
        }

        SGConfig::set('SG_NOTIFICATIONS_ENABLED', '1');
    }
    $ajaxInterval = (int) $_POST['ajaxInterval'];

    if (count($error)) {
        die(json_decode($error));
    }

    if (isset($_POST['sg-hide-ads'])) {
        SGConfig::set('SG_DISABLE_ADS', '1');
    } else {
        SGConfig::set('SG_DISABLE_ADS', '0');
    }

    if (isset($_POST['sg-download-mode'])) {
        SGConfig::set('SG_DOWNLOAD_MODE', (int) $_POST['sg-download-mode']);
    }

    if (isset($_POST['sg-timezone'])) {
        $currentTimezone = SGConfig::get('SG_TIMEZONE') ?: SG_DEFAULT_TIMEZONE;
        SGConfig::set('SG_TIMEZONE', $_POST['sg-timezone']);

        if ($currentTimezone != $_POST['sg-timezone']) {
            modifyCronJobsByTimezone();
        }
    }

    if (isset($_POST['sg-background-reload-method'])) {
        SGConfig::set('SG_BACKGROUND_RELOAD_METHOD', (int) $_POST['sg-background-reload-method']);
    } else {
        SGConfig::set('SG_BACKGROUND_RELOAD_METHOD', SG_RELOAD_METHOD_CURL);
    }

    if (isset($_POST['delete-backup-after-upload'])) {
        SGConfig::set('SG_DELETE_BACKUP_AFTER_UPLOAD', '1');
    } else {
        SGConfig::set('SG_DELETE_BACKUP_AFTER_UPLOAD', '0');
    }

    if (isset($_POST['delete-backup-from-cloud'])) {
        SGConfig::set('SG_DELETE_BACKUP_FROM_CLOUD', '1');
    } else {
        SGConfig::set('SG_DELETE_BACKUP_FROM_CLOUD', '0');
    }

    if (isset($_POST['alert-before-update'])) {
        SGConfig::set('SG_ALERT_BEFORE_UPDATE', '1');
    } else {
        SGConfig::set('SG_ALERT_BEFORE_UPDATE', '0');
    }

    if (isset($_POST['show-statistics-widget'])) {
        SGConfig::set('SG_SHOW_STATISTICS_WIDGET', '1');
    } else {
        SGConfig::set('SG_SHOW_STATISTICS_WIDGET', '0');
    }

    if (isset($_POST['ftp-passive-mode'])) {
        SGConfig::set('SG_FTP_PASSIVE_MODE', '1');
    } else {
        SGConfig::set('SG_FTP_PASSIVE_MODE', '0');
    }

    if (isset($_POST['sg-number-of-rows-to-backup'])) {
        SGConfig::set('SG_BACKUP_DATABASE_INSERT_LIMIT', (int) $_POST['sg-number-of-rows-to-backup']);
    } else {
        SGConfig::set('SG_BACKUP_DATABASE_INSERT_LIMIT', SG_BACKUP_DATABASE_INSERT_LIMIT);
    }

    $backupFileName = 'sg_backup_';
    if (isset($_POST['backup-file-name'])) {
        $backupFileName = $_POST['backup-file-name'];
    }

    $isReloadingsEnabled = 0;
    if (isset($_POST['backup-with-reloadings'])) {
        $isReloadingsEnabled = 1;
    }

    if (isset($_POST['sg-paths-to-exclude'])) {
        SGConfig::set('SG_PATHS_TO_EXCLUDE', $_POST['sg-paths-to-exclude']);
    } else {
        SGConfig::set('SG_PATHS_TO_EXCLUDE', '');
    }

    if (isset($_POST['sg-tables-to-exclude'])) {
        SGConfig::set('SG_TABLES_TO_EXCLUDE', $_POST['sg-tables-to-exclude']);
    } else {
        SGConfig::set('SG_TABLES_TO_EXCLUDE', '');
    }

    if (isset($_POST['sg-upload-cloud-chunk-size'])) {
        SGConfig::set('SG_BACKUP_CLOUD_UPLOAD_CHUNK_SIZE', $_POST['sg-upload-cloud-chunk-size']);
    } else {
        SGConfig::set('SG_BACKUP_CLOUD_UPLOAD_CHUNK_SIZE', '');
    }

    SGConfig::set('SG_BACKUP_WITH_RELOADINGS', $isReloadingsEnabled);
    SGConfig::set('SG_BACKUP_FILE_NAME_PREFIX', $backupFileName);
    SGConfig::set('SG_AJAX_REQUEST_FREQUENCY', $ajaxInterval);
    SGConfig::set('SG_NOTIFICATIONS_EMAIL_ADDRESS', $emails);
    die(json_encode($success));
}

if (backupGuardIsAjax() && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($_GET["type"] == "updateSetting") {
        //disable alert-before-update from updates page
        if (isset($_GET["alert-before-update"])) {
            SGConfig::set('SG_ALERT_BEFORE_UPDATE', $_GET["alert-before-update"]);
        }
    }
}
