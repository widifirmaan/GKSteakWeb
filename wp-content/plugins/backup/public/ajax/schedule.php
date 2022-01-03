<?php

require_once dirname(__FILE__) . '/../boot.php';
require_once SG_BACKUP_PATH . 'SGBackupSchedule.php';

$error   = array();
$success = array('success' => 1);

if (backupGuardIsAjax() && count($_POST)) {
    $_POST = backupGuardRemoveSlashes($_POST);

    if (isset($_POST['remove'])) {
        if (isset($_POST['id'])) {
            SGBackupSchedule::remove((int) $_POST['id']);
        } else {
            SGBackupSchedule::remove();
        }

        die(json_encode($success));
    }

    //Check if cron available
    if (!SGSchedule::isCronAvailable()) {
        array_push($error, _backupGuardT('Cron is not available on your hosting.', true));
        die(json_encode($error));
    }

    $options     = $_POST;
    $cronOptions = array(
        'SG_BACKUP_IN_BACKGROUND_MODE' => 0,
        'SG_BACKUP_UPLOAD_TO_STORAGES' => '',
        'SG_ACTION_BACKUP_DATABASE_AVAILABLE' => 0,
        'SG_ACTION_BACKUP_FILES_AVAILABLE' => '',
        'SG_BACKUP_FILE_PATHS_EXCLUDE' => '',
        'SG_BACKUP_FILE_PATHS' => '',
    );

    $cronLabel = '';
    //Check if schedule name is not empaty
    if (isset($options['sg-schedule-label'])) {
        $label = trim($options['sg-schedule-label']);
        $label = backupGuardSanitizeTextField($label);
        if (empty($label)) {
            array_push($error, _backupGuardT('Label field is required.', true));
            die(json_encode($error));
        } else {
            $cronLabel = $label;
        }
    } else {
        array_push($error, _backupGuardT('Label field is required.', true));
        die(json_encode($error));
    }

    //If background mode
    $isBackgroundMode                            = isset($options['backgroundMode']) ? 1 : 0;
    $cronOptions['SG_BACKUP_IN_BACKGROUND_MODE'] = $isBackgroundMode;

    //If cloud backup
    if (isset($options['backupCloud']) && count($options['backupStorages'])) {
        $clouds                                      = backupGuardSanitizeTextField($options['backupStorages']);
        $cronOptions['SG_BACKUP_UPLOAD_TO_STORAGES'] = implode(',', $clouds);
    }

    $cronOptions['SG_BACKUP_TYPE'] = $options['backupType'];
    if ($options['backupType'] == SG_BACKUP_TYPE_FULL) {
        $cronOptions['SG_BACKUP_FILE_PATHS_EXCLUDE']        = SG_BACKUP_FILE_PATHS_EXCLUDE;
        $cronOptions['SG_BACKUP_FILE_PATHS']                = 'wp-content';
        $cronOptions['SG_ACTION_BACKUP_DATABASE_AVAILABLE'] = 1;
        $cronOptions['SG_ACTION_BACKUP_FILES_AVAILABLE']    = 1;
    } else if ($options['backupType'] == SG_BACKUP_TYPE_CUSTOM) {
        //If database backup
        $isDatabaseBackup                                   = isset($options['backupDatabase']) ? 1 : 0;
        $cronOptions['SG_ACTION_BACKUP_DATABASE_AVAILABLE'] = $isDatabaseBackup;

        //If db backup
        if ($options['backupDBType']) {
            $tablesToBackup                              = implode(',', $options['table']);
            $backupOptions['SG_BACKUP_TABLES_TO_BACKUP'] = $tablesToBackup;
        }
        //If files backup
        if (isset($options['backupFiles']) && count($options['directory'])) {
            $backupFiles          = explode(',', SG_BACKUP_FILE_PATHS);
            $options['directory'] = backupGuardSanitizeTextField($options['directory']);
            $filesToExclude       = @array_diff($backupFiles, $options['directory']);

            if (in_array('wp-content', $options['directory'])) {
                $options['directory'] = array('wp-content');
            } else {
                $filesToExclude = array_diff($filesToExclude, array('wp-content'));
            }

            $filesToExclude = implode(',', $filesToExclude);
            if (strlen($filesToExclude)) {
                $filesToExclude = ',' . $filesToExclude;
            }

            $cronOptions['SG_BACKUP_FILE_PATHS_EXCLUDE']     = SG_BACKUP_FILE_PATHS_EXCLUDE . $filesToExclude;
            $cronOptions['SG_ACTION_BACKUP_FILES_AVAILABLE'] = 1;
            $cronOptions['SG_BACKUP_FILE_PATHS']             = implode(',', $options['directory']);
        } else {
            $cronOptions['SG_ACTION_BACKUP_FILES_AVAILABLE'] = 0;
            $cronOptions['SG_BACKUP_FILE_PATHS']             = 0;
        }
    } else {
        array_push($error, _backupGuardT('Invalid backup type', true));
        die(json_encode($error));
    }

    $scheduleIntervalDay   = '';
    $scheduleIntervalMonth = '';
    if ($options['scheduleInterval'] == BG_SCHEDULE_INTERVAL_WEEKLY && isset($options['sg-schedule-day-of-week'])) {
        $scheduleIntervalDay = (int) $options['sg-schedule-day-of-week'];
    } else if ($options['scheduleInterval'] == BG_SCHEDULE_INTERVAL_MONTHLY && isset($options['sg-schedule-day-of-month'])) {
        $scheduleIntervalDay = (int) $options['sg-schedule-day-of-month'];
    } else if ($options['scheduleInterval'] == BG_SCHEDULE_INTERVAL_YEARLY && isset($options['sg-schedule-day-of-month']) && isset($options['sg-schedule-month-of-year'])) {
        $scheduleIntervalDay   = (int) $options['sg-schedule-day-of-month'];
        $scheduleIntervalMonth = (int) $options['sg-schedule-month-of-year'];
    }

    try {
        $cronTab = array(
            'monthOfInterval' => $scheduleIntervalMonth,
            'dayOfInterval' => $scheduleIntervalDay,
            'intervalHour' => $options['scheduleHour'],
            'interval' => $options['scheduleInterval']
        );

        if (isset($options['sg-schedule-id'])) {
            SGBackupSchedule::remove($options['sg-schedule-id']);
        }

        SGBackupSchedule::create($cronTab, $cronOptions, $cronLabel);

        die(json_encode($success));
    } catch (SGException $exception) {
        array_push($error, $exception->getMessage());
        die(json_encode($error));
    }
}
