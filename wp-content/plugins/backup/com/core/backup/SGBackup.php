<?php

require_once SG_BACKUP_PATH . 'SGBackupLog.php';
require_once SG_RESTORE_PATH . 'SGExternalRestore.php';
require_once SG_LIB_PATH . 'SGState.php';
backupGuardIncludeFile(SG_LIB_PATH . 'SGBackgroundMode.php');
require_once SG_BACKUP_PATH . 'SGBackupFiles.php';
require_once SG_BACKUP_PATH . 'SGBackupDatabase.php';
backupGuardIncludeFile(SG_BACKUP_PATH . 'SGBackupStorage.php');
backupGuardIncludeFile(SG_BACKUP_PATH . 'SGBackupMailNotification.php');
require_once SG_LOG_PATH . 'SGFileLogHandler.php';
require_once SG_LIB_PATH . 'SGReloader.php';
require_once SG_LIB_PATH . 'SGCallback.php';

//close session for writing
@session_write_close();

class SGBackup implements SGIBackupDelegate
{
    private $_backupFiles = null;
    private $_backupDatabase = null;
    private $_actionId = null;
    private $_filesBackupAvailable = false;
    private $_databaseBackupAvailable = false;
    private $_isManual = true;
    private $_actionStartTs = 0;
    private $_fileName = '';
    private $_filesBackupPath = '';
    private $_databaseBackupPath = '';
    private $_backupLogPath = '';
    private $_restoreLogPath = '';
    private $_backgroundMode = false;
    private $_pendingStorageUploads = array();
    private $_state = null;
    private $_token = '';
    private $_options = array();

    public function __construct()
    {
        $this->_backupFiles = new SGBackupFiles();
        $this->_backupFiles->setDelegate($this);

        $this->_backupDatabase = new SGBackupDatabase();
        $this->_backupDatabase->setDelegate($this);
    }

    public function getScheduleParamsById($id)
    {
        $sgdb = SGDatabase::getInstance();
        $res  = $sgdb->query('SELECT * FROM ' . SG_SCHEDULE_TABLE_NAME . ' WHERE id=%d', array($id));
        if (empty($res)) {
            return '';
        }

        return $res[0];
    }

    private function handleBackupExecutionTimeout()
    {
        $this->_backupDatabase->setFilePath($this->_databaseBackupPath);
        $this->_backupDatabase->cancel();

        $this->_backupFiles->setFilePath($this->_filesBackupPath);
        $this->_backupFiles->cancel();

        if (SGBoot::isFeatureAvailable('NOTIFICATIONS')) {
            file_put_contents(dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME, 'Backup: failed', FILE_APPEND);
            SGBackupMailNotification::sendBackupNotification(
                SG_ACTION_STATUS_ERROR,
                array(
                    'flowFilePath' => dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME,
                    'archiveName' => $this->_fileName
                )
            );
        }
    }

    private function handleRestoreExecutionTimeout()
    {
        if (SGBoot::isFeatureAvailable('NOTIFICATIONS')) {
            file_put_contents(dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME, 'Restore: failed', FILE_APPEND);
            SGBackupMailNotification::sendRestoreNotification(
                false,
                array(
                    'flowFilePath' => dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME,
                    'archiveName' => $this->_fileName
                )
            );
        }
    }

    private function handleUploadExecutionTimeout()
    {
        self::changeActionStatus($this->_actionId, SG_ACTION_STATUS_FINISHED_WARNINGS);

        if (SGBoot::isFeatureAvailable('NOTIFICATIONS')) {
            file_put_contents(dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME, 'Upload: failed', FILE_APPEND);

            SGBackupMailNotification::sendUploadNotification(
                false,
                array(
                    'flowFilePath' => dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME,
                    'archiveName' => $this->_fileName
                )
            );
        }
    }

    public function handleExecutionTimeout($actionId)
    {
        $this->_actionId = $actionId;
        $action          = self::getAction($actionId);
        $this->_fileName = $action['name'];
        $actionType      = $action['type'];
        $backupPath      = SG_BACKUP_DIRECTORY . $this->_fileName;

        $this->_filesBackupPath    = $backupPath . '/' . $this->_fileName . '.sgbp';
        $this->_databaseBackupPath = $backupPath . '/' . $this->_fileName . '.sql';

        if ($actionType == SG_ACTION_TYPE_RESTORE) {
            $this->handleRestoreExecutionTimeout();
            $this->prepareRestoreLogFile($backupPath, true);
        } elseif ($actionType == SG_ACTION_TYPE_BACKUP) {
            $this->handleBackupExecutionTimeout();
            $this->prepareBackupLogFile($backupPath, true);
        } else {
            $this->handleUploadExecutionTimeout();
            $this->prepareBackupLogFile($backupPath, true);
        }

        //Stop all the running actions related to the specific backup, like backup, upload...
        $allActions = self::getRunningActions();
        foreach ($allActions as $action) {
            self::changeActionStatus($action['id'], SG_ACTION_STATUS_ERROR);
        }

        $exception = new SGExceptionExecutionTimeError();
        SGBackupLog::writeExceptionObject($exception);
        SGConfig::set('SG_EXCEPTION_TIMEOUT_ERROR', '1', true);
    }

    public function listStorage($storage)
    {
        if (SGBoot::isFeatureAvailable('DOWNLOAD_FROM_CLOUD')) {
            $listOfFiles = SGBackupStorage::getInstance()->listStorage($storage);

            return $listOfFiles;
        }

        return array();
    }

    public function downloadBackupArchiveFromCloud($archive, $storage, $size, $backupId = null)
    {
        $result = false;
        if (SGBoot::isFeatureAvailable('DOWNLOAD_FROM_CLOUD')) {
            $result = SGBackupStorage::getInstance()->downloadBackupArchiveFromCloud($storage, $archive, $size, $backupId);
        }

        return $result;
    }

    public function getState()
    {
        return $this->_state;
    }

    private function prepareFilesStateFile()
    {
        $this->_state = new SGFileState();

        $this->_state->setRanges(array());
        $this->_state->setOffset(0);
        $this->_state->setToken($this->_token);
        $this->_state->setAction(SG_STATE_ACTION_PREPARING_STATE_FILE);
        $this->_state->setType(SG_STATE_TYPE_FILE);
        $this->_state->setActionId($this->_actionId);
        $this->_state->setActionStartTs($this->_actionStartTs);
        $this->_state->setBackupFileName($this->_fileName);
        $this->_state->setBackupFilePath($this->_filesBackupPath);
        $this->_state->setPendingStorageUploads($this->_pendingStorageUploads);
        $this->_state->setCdrCursor(0);
        $this->_state->setRestoreMode(@$this->restoreMode);
        $this->_state->setRestoreFiles(@$this->restoreFiles);
    }

    private function prepareDBStateFile()
    {
        $this->_state = new SGDBState();
        $this->_state->setToken($this->_token);
        $this->_state->setOffset(0);
        $this->_state->setAction(SG_STATE_ACTION_PREPARING_STATE_FILE);
        $this->_state->setType(SG_STATE_TYPE_DB);
        $this->_state->setActionId($this->_actionId);
        $this->_state->setActionStartTs($this->_actionStartTs);
        $this->_state->setBackupFileName($this->_fileName);
        $this->_state->setBackupFilePath($this->_filesBackupPath);
        $this->_state->setPendingStorageUploads($this->_pendingStorageUploads);
        $this->_state->setBackedUpTables(array());
        $this->_state->setTablesToBackup(@$this->_options['SG_BACKUP_TABLES_TO_BACKUP']);
    }

    private function prepareUploadStateFile()
    {
        $this->_state = new SGUploadState();
        $this->_state->setOffset(0);
        $this->_state->setActiveDirectory('');
        $this->_state->setCurrentUploadChunksCount(0);
        $this->_state->setTotalUploadChunksCount(0);
        $this->_state->setUploadId(0);
        $this->_state->setParts(array());
        $this->_state->setToken($this->_token);
        $this->_state->setAction(SG_STATE_ACTION_PREPARING_STATE_FILE);
        $this->_state->setType(SG_STATE_TYPE_UPLOAD);
        $this->_state->setActionId($this->_actionId);
        $this->_state->setActionStartTs($this->_actionStartTs);
        $this->_state->setBackupFileName($this->_fileName);
        $this->_state->setBackupFilePath($this->_filesBackupPath);
        $this->_state->setPendingStorageUploads($this->_pendingStorageUploads);
    }

    public function prepareMigrateStateFile()
    {
        $this->_state = new SGMigrateState();
        $this->_state->setActionId($this->_actionId);
        $this->_state->setAction(SG_STATE_ACTION_PREPARING_STATE_FILE);
        $this->_state->setInprogress(false);
        $this->_state->setTableCursor(0);
        $this->_state->setColumnCursor(0);
        $this->_state->setToken($this->_token);
    }

    public function getNoprivReloadAjaxUrl()
    {
        $url = @$_SERVER['REQUEST_URI'];

        if (empty($url)) {
            if (function_exists('admin_url')) {
                $parseUrl = parse_url(admin_url('admin-ajax.php'));
                $url      = $parseUrl['path'];
            }
        }

        if (SG_ENV_ADAPTER == SG_ENV_WORDPRESS) {
            if (strpos($url, 'wp-cron.php')) {
                $url = substr($url, 0, strpos($url, 'wp-cron.php'));
                $url .= 'wp-admin/admin-ajax.php';
            }

            //this is needed for users that use alternate cron
            if (strpos($url, 'wp-admin/tools.php')) {
                $url = substr($url, 0, strpos($url, 'wp-admin/tools.php'));
                $url .= 'wp-admin/admin-ajax.php';
            }

            $url = explode('?', $url);
            $url = $url[0] . '?action=backup_guard_awake&token=' . $this->_token;
        }

        return $url;
    }

    public function reload()
    {
        $url      = $this->getNoprivReloadAjaxUrl();
        $callback = new SGCallback("SGBackup", "reloadCallback");
        SGBackupLog::write('------- Reload Service -------');
        SGReloader::didCompleteCallback();
        SGReloader::registerCallback($callback);
        SGReloader::reloadWithAjaxUrl($url);
        die();
    }

    public function reloadCallback($params)
    {
        $actions = self::getRunningActions();
        if (count($actions)) {
            $action = $actions[0];
            $method = $params['method'];

            $this->_state = backupGuardLoadStateData();
            if ($action['type'] == SG_ACTION_TYPE_RESTORE) {
                $this->restore($action['name']);
            } else {
                $options = json_decode($action['options'], true);
                $this->backup($options, $this->_state, $method);
            }
        }
    }

    private function saveStateFile()
    {
        $this->_state->save();
    }

    public function getToken()
    {
        return $this->_token;
    }

    private function reloadMethodNameByMethodId($method)
    {
        $name = "none";
        switch ($method) {
            case SG_RELOAD_METHOD_STREAM:
                $name = "stream";
                break;
            case SG_RELOAD_METHOD_CURL:
                $name = "curl";
                break;
            case SG_RELOAD_METHOD_SOCKET:
                $name = "socket";
                break;
            case SG_RELOAD_METHOD_AJAX:
                $name = "ajax";
                break;
            default:
                break;
        }

        return $name;
    }

    /* Backup implementation */
    public function backup($options, $state = false, $reloadMethod = null)
    {
        SGPing::update();

        $this->_options = $options;
        $this->_token   = backupGuardGenerateToken();

        $this->_filesBackupAvailable    = isset($options['SG_ACTION_BACKUP_FILES_AVAILABLE']) ? $options['SG_ACTION_BACKUP_FILES_AVAILABLE'] : false;
        $this->_databaseBackupAvailable = isset($options['SG_ACTION_BACKUP_DATABASE_AVAILABLE']) ? $options['SG_ACTION_BACKUP_DATABASE_AVAILABLE'] : false;
        $this->_backgroundMode          = isset($options['SG_BACKUP_IN_BACKGROUND_MODE']) ? $options['SG_BACKUP_IN_BACKGROUND_MODE'] : false;

        if (!$state) {
            $this->_fileName = $this->getBackupFileName();
            $this->prepareBackupFolder(SG_BACKUP_DIRECTORY . $this->_fileName);
            $this->prepareForBackup($options);
            $this->prepareBackupReport();

            SGBackupLog::write("Reload method set to ajax");
            SGConfig::set('SG_RELOAD_METHOD', SG_RELOAD_METHOD_AJAX, true);

            if ($this->_databaseBackupAvailable) {
                $this->prepareDBStateFile();
            } else {
                $this->prepareFilesStateFile();
            }

            $this->saveStateFile();
            SGReloader::reset();
            if (backupGuardIsReloadEnabled()) {
                $this->reload();
            }
        } else {
            $this->_state                 = $state;
            $this->_fileName              = $state->getBackupFileName();
            $this->_actionId              = $state->getActionId();
            $this->_actionStartTs         = $state->getActionStartTs();
            $this->_pendingStorageUploads = $state->getPendingStorageUploads();

            $this->prepareBackupLogFile(SG_BACKUP_DIRECTORY . $this->_fileName, true);
            $this->setBackupPaths();
            $this->prepareAdditionalConfigurations();
        }

        SGPing::update();

        try {
            $rootDirectory = rtrim(SGConfig::get('SG_APP_ROOT_DIRECTORY'), '/') . '/';

            if ($this->_databaseBackupAvailable) {
                $this->_backupDatabase->setFilePath($this->_databaseBackupPath);
                $this->_backupDatabase->setPendingStorageUploads($this->_pendingStorageUploads);

                if (!$this->_filesBackupAvailable) {
                    $options['SG_BACKUP_FILE_PATHS'] = '';
                }

                if ($this->_state->getType() == SG_STATE_TYPE_DB) {
                    $this->_backupDatabase->backup($this->_databaseBackupPath);
                    $this->prepareFilesStateFile();
                    $this->saveStateFile();
                    self::changeActionStatus($this->_actionId, SG_ACTION_STATUS_IN_PROGRESS_FILES);

                    if (backupGuardIsReloadEnabled()) {
                        $this->reload();
                    }
                }

                $path = substr($this->_databaseBackupPath, strlen($rootDirectory));
                $this->_backupFiles->addDontExclude($this->_databaseBackupPath);
                $backupItems                     = $options['SG_BACKUP_FILE_PATHS'];
                $allItems                        = $backupItems ? explode(',', $backupItems) : array();
                $allItems[]                      = $path;
                $options['SG_BACKUP_FILE_PATHS'] = implode(',', $allItems);

                if ($this->_state->getType() == SG_STATE_TYPE_DB) {
                    $currentStatus = $this->getCurrentActionStatus();
                    if ($currentStatus == SG_ACTION_STATUS_CANCELLING || $currentStatus == SG_ACTION_STATUS_CANCELLED) {
                        // If action canceled during backup of database, cancelaion handling will happen here
                        // in other cases handling will happen in respective classes
                        $this->cancel();
                    }
                }
            }

            if ($this->_state->getType() == SG_STATE_TYPE_FILE) {
                //* TODO: check this logic
                /*
                $treeFilePath = SG_BACKUP_DIRECTORY.$this->_fileName.'/'.SG_TREE_FILE_NAME;
                $treeFilePathWithoutRootDir = substr($treeFilePath, strlen($rootDirectory));
                $this->_backupFiles->addDontExclude($treeFilePath);
                $backupItems = $options['SG_BACKUP_FILE_PATHS'];
                $allItems = $backupItems?explode(',', $backupItems):array();
                $allItems[] = $treeFilePathWithoutRootDir;
                $options['SG_BACKUP_FILE_PATHS'] = implode(',', $allItems);
                */

                $this->_backupFiles->setPendingStorageUploads($this->_pendingStorageUploads);
                $this->_backupFiles->backup($this->_filesBackupPath, $options, $this->_state);
                $this->didFinishBackup();

                SGPing::update();

                $this->prepareUploadStateFile();
                $this->saveStateFile();
            }

            //continue uploading backup to storages
            $this->backupUploadToStorages();

            // Clear temporary files
            $this->clear();
        } catch (SGException $exception) {
            if ($exception instanceof SGExceptionSkip) {
                $this->setCurrentActionStatusCancelled();
            } else {
                SGBackupLog::writeExceptionObject($exception);

                if ($this->_state->getType() != SG_STATE_TYPE_UPLOAD) {
                    if ($this->_databaseBackupAvailable) {
                        $this->_backupDatabase->cancel();
                    }

                    $this->_backupFiles->cancel();
                }

                if (SGBoot::isFeatureAvailable('NOTIFICATIONS')) {
                    //Writing backup status to report file
                    file_put_contents(dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME, 'Backup: failed', FILE_APPEND);
                    SGBackupMailNotification::sendBackupNotification(
                        SG_ACTION_STATUS_ERROR,
                        array(
                            'flowFilePath' => dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME,
                            'archiveName' => $this->_fileName
                        )
                    );
                }

                self::changeActionStatus($this->_actionId, SG_ACTION_STATUS_ERROR);
            }

            // Clear temporary files
            $this->clear();
        }
    }

    private function prepareBackupReport()
    {
        file_put_contents(dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME, 'Report for: ' . SG_SITE_URL . "\n", FILE_APPEND);
    }

    private function shouldDeleteBackupAfterUpload()
    {
        return SGConfig::get('SG_DELETE_BACKUP_AFTER_UPLOAD') ? true : false;
    }

    private function backupUploadToStorages()
    {
        //check list of storages to upload if any
        $uploadToStorages = count($this->_pendingStorageUploads) ? true : false;
        if (SGBoot::isFeatureAvailable('STORAGE') && $uploadToStorages) {
            while (count($this->_pendingStorageUploads)) {
                $sgBackupStorage = SGBackupStorage::getInstance();
                $storageId       = $this->_pendingStorageUploads[0];

                $storageInfo = $sgBackupStorage->getStorageInfoById($storageId);
                if (empty($storageInfo['isConnected'])) {
                    SGBackupLog::write($storageInfo['storageName'] . ' stopped');
                    array_shift($this->_pendingStorageUploads);
                    continue;
                }

                if ($this->_state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {
                    // Create action for upload
                    $this->_actionId = SGBackupStorage::queueBackupForUpload($this->_fileName, $storageId, $this->_options);
                } else {
                    // Get upload action id if it does not finished yet
                    $this->_actionId = $this->_state->getActionId();
                }


                $sgBackupStorage->setDelegate($this);
                $sgBackupStorage->setState($this->_state);
                $sgBackupStorage->setToken($this->_token);
                $sgBackupStorage->setPendingStorageUploads($this->_pendingStorageUploads);
                $sgBackupStorage->startUploadByActionId($this->_actionId);

                array_shift($this->_pendingStorageUploads);
                // Reset state file to defaults for next storage upload
                $this->prepareUploadStateFile();
            }

            $this->didFinishUpload();
        }
    }

    private function didFinishUpload()
    {
        //check if option is enabled
        $isDeleteLocalBackupFeatureAvailable = SGBoot::isFeatureAvailable('DELETE_LOCAL_BACKUP_AFTER_UPLOAD');

        if (SGBoot::isFeatureAvailable('NOTIFICATIONS')) {
            SGBackupMailNotification::sendBackupNotification(
                SG_ACTION_STATUS_FINISHED,
                array(
                    'flowFilePath' => dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME,
                    'archiveName' => $this->_fileName
                )
            );
        }

        $status = SGBackup::getActionStatus($this->_actionId);

        if ($this->shouldDeleteBackupAfterUpload() && $isDeleteLocalBackupFeatureAvailable && $status == SG_ACTION_STATUS_FINISHED) {
            @unlink(SG_BACKUP_DIRECTORY . $this->_fileName . '/' . $this->_fileName . '.' . SGBP_EXT);
        }
    }

    // Delete state and flow files after upload
    private function clear()
    {
        @unlink(dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME);
        /// ToDo check this logic
        //@unlink(SG_BACKUP_DIRECTORY.$this->_fileName.'/'.SG_TREE_FILE_NAME);
        @unlink(SG_BACKUP_DIRECTORY . SG_STATE_FILE_NAME);
        @unlink(SG_BACKUP_DIRECTORY . SG_RELOADER_STATE_FILE_NAME);
        @unlink(SG_PING_FILE_PATH);
        SGConfig::set("SG_CUSTOM_BACKUP_NAME", '');
    }

    private function cleanUp()
    {
        //delete sql file
        if ($this->_databaseBackupAvailable) {
            @unlink($this->_databaseBackupPath);
        }
    }

    private function getBackupFileName()
    {
        if (SGConfig::get("SG_CUSTOM_BACKUP_NAME")) {
            return backupGuardRemoveSlashes(SGConfig::get("SG_CUSTOM_BACKUP_NAME"));
        }

        $sgBackupPrefix = SG_BACKUP_FILE_NAME_DEFAULT_PREFIX;
        if (function_exists('backupGuardGetCustomPrefix') && SGBoot::isFeatureAvailable('CUSTOM_BACKUP_NAME')) {
            $sgBackupPrefix = backupGuardGetCustomPrefix();
        }

        $sgBackupPrefix .= backupGuardGetFilenameOptions($this->_options);

        $date = backupGuardConvertDateTimezone(@date('YmdHis'), true, 'YmdHis');

        return $sgBackupPrefix . ($date);
    }

    private function prepareBackupFolder($backupPath)
    {
        if (!is_writable(SG_BACKUP_DIRECTORY)) {
            throw new SGExceptionForbidden('Permission denied. Directory is not writable: ' . $backupPath);
        }

        //create backup folder
        if (!file_exists($backupPath) && !@mkdir($backupPath)) {
            throw new SGExceptionMethodNotAllowed('Cannot create folder: ' . $backupPath);
        }

        if (!is_writable($backupPath)) {
            throw new SGExceptionForbidden('Permission denied. Directory is not writable: ' . $backupPath);
        }

        //create backup log file
        $this->prepareBackupLogFile($backupPath);
    }

    private function extendLogFileHeader($content)
    {
        $isManual = $this->getIsManual();
        if ($isManual) {
            $content .= 'Backup mode: Manual' . PHP_EOL;
        } else {
            $content .= 'Backup mode: Schedule' . PHP_EOL;
        }

        return $content;
    }

    private function prepareBackupLogFile($backupPath, $exists = false)
    {
        $file                 = $backupPath . '/' . $this->_fileName . '_backup.log';
        $this->_backupLogPath = $file;

        if (!$exists) {
            $isUpload = $this->getIsUploadStorage();

            $content = self::getLogFileHeader(SG_ACTION_TYPE_BACKUP, $this->_fileName, $isUpload);
            $content = $this->extendLogFileHeader($content);

            $types = array();
            if ($this->_filesBackupAvailable) {
                $types[] = 'files';
            }
            if ($this->_databaseBackupAvailable) {
                $types[] = 'database';
            }

            $content .= 'Backup type: ' . implode(',', $types) . PHP_EOL . PHP_EOL;

            if (!file_put_contents($file, $content)) {
                throw new SGExceptionMethodNotAllowed('Cannot create backup log file: ' . $file);
            }
        }

        //create file log handler
        $fileLogHandler = new SGFileLogHandler($file);
        SGLog::registerLogHandler($fileLogHandler, SG_LOG_LEVEL_LOW, true);
    }

    private function setBackupPaths()
    {
        $this->_filesBackupPath    = SG_BACKUP_DIRECTORY . $this->_fileName . '/' . $this->_fileName . '.sgbp';
        $this->_databaseBackupPath = SG_BACKUP_DIRECTORY . $this->_fileName . '/' . $this->_fileName . '.sql';
    }

    private function prepareUploadToStorages($options)
    {
        $uploadToStorages = $options['SG_BACKUP_UPLOAD_TO_STORAGES'];

        if (SGBoot::isFeatureAvailable('STORAGE') && $uploadToStorages) {
            $this->_pendingStorageUploads = explode(',', $uploadToStorages);
        }
    }

    private function prepareAdditionalConfigurations()
    {
        $this->_backupFiles->setFilePath($this->_filesBackupPath);
        SGConfig::set('SG_RUNNING_ACTION', 1, true);
    }

    private function prepareForBackup($options)
    {
        //start logging
        SGBackupLog::writeAction('backup', SG_BACKUP_LOG_POS_START);

        //save timestamp for future use
        $this->_actionStartTs = time();

        //create action inside db
        $status          = $this->_databaseBackupAvailable ? SG_ACTION_STATUS_IN_PROGRESS_DB : SG_ACTION_STATUS_IN_PROGRESS_FILES;
        $this->_actionId = self::createAction($this->_fileName, SG_ACTION_TYPE_BACKUP, $status, 0, json_encode($options));

        //set paths
        $this->setBackupPaths();

        //prepare sgbp file
        @file_put_contents($this->_filesBackupPath, '');

        if (!is_writable($this->_filesBackupPath)) {
            throw new SGExceptionForbidden('Could not create backup file: ' . $this->_filesBackupPath);
        }

        //additional configuration
        $this->prepareAdditionalConfigurations();

        //check if upload to storages is needed
        $this->prepareUploadToStorages($options);
    }

    public function cancel()
    {
        $dir = SG_BACKUP_DIRECTORY . $this->_fileName;

        if (SGBoot::isFeatureAvailable('NOTIFICATIONS')) {
            //Writing backup status to report file
            file_put_contents($dir . '/' . SG_REPORT_FILE_NAME, 'Backup: canceled', FILE_APPEND);
            SGBackupMailNotification::sendBackupNotification(
                SG_ACTION_STATUS_CANCELLED,
                array(
                    'flowFilePath' => dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME,
                    'archiveName' => $this->_fileName
                )
            );
        }

        if ($dir != SG_BACKUP_DIRECTORY) {
            backupGuardDeleteDirectory($dir);
        }

        $this->clear();
        throw new SGExceptionSkip();
    }

    private function didFinishBackup()
    {
        if (SGConfig::get('SG_REVIEW_POPUP_STATE') != SG_NEVER_SHOW_REVIEW_POPUP) {
            SGConfig::set('SG_REVIEW_POPUP_STATE', SG_SHOW_REVIEW_POPUP);
        }

        $action = $this->didFindWarnings() ? SG_ACTION_STATUS_FINISHED_WARNINGS : SG_ACTION_STATUS_FINISHED;
        self::changeActionStatus($this->_actionId, $action);

        SGBackupLog::writeAction('backup', SG_BACKUP_LOG_POS_END);

        $report = $this->didFindWarnings() ? 'completed with warnings' : 'completed';

        //Writing backup status to report file
        file_put_contents(dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME, 'Backup: ' . $report . "\n", FILE_APPEND);
        if (SGBoot::isFeatureAvailable('NOTIFICATIONS') && !count($this->_pendingStorageUploads)) {
            SGBackupMailNotification::sendBackupNotification(
                $action,
                array(
                    'flowFilePath' => dirname($this->_filesBackupPath) . '/' . SG_REPORT_FILE_NAME,
                    'archiveName' => $this->_fileName
                )
            );
        }
        SGBackupLog::write('Total duration: ' . backupGuardFormattedDuration($this->_actionStartTs, time()));
        SGBackupLog::write('Memory peak usage: ' . (memory_get_peak_usage(true) / 1024 / 1024) . 'MB');
        if (function_exists('sys_getloadavg')) {
            SGBackupLog::write('CPU usage: ' . implode(' / ', sys_getloadavg()));
        }

        $archiveSizeInBytes = backupGuardRealFilesize($this->_filesBackupPath);
        $archiveSize        = convertToReadableSize($archiveSizeInBytes);
        SGBackupLog::write("Archive size: " . $archiveSize . " (" . $archiveSizeInBytes . " bytes)");

        $this->cleanUp();
        if (SGBoot::isFeatureAvailable('NUMBER_OF_BACKUPS_TO_KEEP') && function_exists('backupGuardOutdatedBackupsCleanup')) {
            backupGuardOutdatedBackupsCleanup(SG_BACKUP_DIRECTORY);
        }
    }

    public function handleMigrationErrors($exception)
    {
        SGConfig::set('SG_BACKUP_SHOW_MIGRATION_ERROR', 1);
        SGConfig::set('SG_BACKUP_MIGRATION_ERROR', (string) $exception);
    }

    public function getActionId()
    {
        return $this->_actionId;
    }

    /* Restore implementation */

    public function restore($backupName, $restoreMode = null, $restoreFiles = null)
    {
        try {
            SGPing::update();
            $this->_token = backupGuardGenerateToken();
            if ($restoreMode != null) {
                $this->restoreMode = $restoreMode;
            }
            if ($restoreFiles != null) {
                $this->restoreFiles = $restoreFiles;
            }
            $backupName = backupGuardRemoveSlashes($backupName);
            $this->prepareForRestore($backupName);

            if ($this->_state && ($this->_state->getAction() == SG_STATE_ACTION_RESTORING_DATABASE || $this->_state->getAction() == SG_STATE_ACTION_MIGRATING_DATABASE)) {
                $this->didFinishFilesRestore();
            } else {
                $this->_backupFiles->setFileName($backupName);
                $this->_backupFiles->restore($this->_filesBackupPath);

                $this->prepareDBStateFile();
                $this->didFinishFilesRestore();
            }
        } catch (SGException $exception) {
            if (!$exception instanceof SGExceptionSkip) {
                SGBackupLog::writeExceptionObject($exception);

                if ($exception instanceof SGExceptionMigrationError) {
                    $this->handleMigrationErrors($exception);
                }

                if (SGBoot::isFeatureAvailable('NOTIFICATIONS')) {
                    SGBackupMailNotification::sendRestoreNotification(false);
                }

                self::changeActionStatus($this->_actionId, SG_ACTION_STATUS_FINISHED_WARNINGS);
            } else {
                self::changeActionStatus($this->_actionId, SG_ACTION_STATUS_CANCELLED);
            }
        }
    }

    private function prepareForRestore($backupName)
    {
        //prepare file name
        $this->_fileName = $backupName;

        //set paths
        $restorePath               = SG_BACKUP_DIRECTORY . $this->_fileName;
        $this->_filesBackupPath    = $restorePath . '/' . $this->_fileName . '.sgbp';
        $this->_databaseBackupPath = $restorePath . '/' . $this->_fileName . '.sql';

        if (!$this->_state) {
            //create action inside db
            $this->_actionId = self::createAction($this->_fileName, SG_ACTION_TYPE_RESTORE, SG_ACTION_STATUS_IN_PROGRESS_FILES);

            //save current user credentials
            $this->_backupDatabase->saveCurrentUser();

            //check if we can run external restore
            $externalRestoreEnabled = SGExternalRestore::getInstance()->prepare($this->_actionId);

            //prepare folder
            $this->prepareRestoreFolder($restorePath);

            SGConfig::set('SG_RUNNING_ACTION', 1, true);

            //save timestamp for future use
            $this->_actionStartTs = time();

            $this->prepareFilesStateFile();
            $this->saveStateFile();
            SGReloader::reset();

            if ($externalRestoreEnabled) {
                $this->reload();
            }
        } else {
            $this->_actionId      = $this->_state->getActionId();
            $this->_actionStartTs = $this->_state->getActionStartTs();
            $this->prepareRestoreLogFile($restorePath, true);
        }
    }

    private function prepareRestoreFolder($restorePath)
    {
        if (!is_writable($restorePath)) {
            SGConfig::set('SG_BACKUP_NOT_WRITABLE_DIR_PATH', $restorePath);
            SGConfig::set('SG_BACKUP_SHOW_NOT_WRITABLE_ERROR', 1);
            throw new SGExceptionForbidden('Permission denied. Directory is not writable: ' . $restorePath);
        }

        $this->_filesBackupAvailable = file_exists($this->_filesBackupPath);

        //create restore log file
        $this->prepareRestoreLogFile($restorePath);
    }

    private function prepareRestoreLogFile($backupPath, $exists = false)
    {
        $file                  = $backupPath . '/' . $this->_fileName . '_restore.log';
        $this->_restoreLogPath = $file;

        if (!$exists) {
            $content = self::getLogFileHeader(SG_ACTION_TYPE_RESTORE, $this->_fileName);

            $content .= PHP_EOL;

            if (!file_put_contents($file, $content)) {
                throw new SGExceptionMethodNotAllowed('Cannot create restore log file: ' . $file);
            }
        }

        //create file log handler
        $fileLogHandler = new SGFileLogHandler($file);
        SGLog::registerLogHandler($fileLogHandler, SG_LOG_LEVEL_LOW, true);
    }

    private function didFinishRestore()
    {
        SGBackupLog::writeAction('restore', SG_BACKUP_LOG_POS_END);
        if (SGExternalRestore::isEnabled()) {
            SGBackupLog::write("Leaving maintenance mode");
        }

        if (SGBoot::isFeatureAvailable('NOTIFICATIONS')) {
            SGBackupMailNotification::sendRestoreNotification(true);
        }

        SGBackupLog::write('Memory peak usage: ' . (memory_get_peak_usage(true) / 1024 / 1024) . 'MB');
        if (function_exists('sys_getloadavg')) {
            SGBackupLog::write('CPU usage: ' . implode(' / ', sys_getloadavg()));
        }
        SGBackupLog::write('Total duration: ' . backupGuardFormattedDuration($this->_actionStartTs, time()));

        $this->cleanUp();
    }

    private function didFinishFilesRestore()
    {
        $this->_databaseBackupAvailable = file_exists($this->_databaseBackupPath);

        if ($this->_databaseBackupAvailable) {
            if ($this->_state->getAction() == SG_STATE_ACTION_RESTORING_DATABASE) {
                self::changeActionStatus($this->_actionId, SG_ACTION_STATUS_IN_PROGRESS_DB);
            }

            $this->_backupDatabase->restore($this->_databaseBackupPath);
        }

        $action = $this->didFindWarnings() ? SG_ACTION_STATUS_FINISHED_WARNINGS : SG_ACTION_STATUS_FINISHED;

        self::changeActionStatus($this->_actionId, $action);

        //we let the external restore to finalize the restore for itself
        if (SGExternalRestore::isEnabled()) {
            return;
        }

        $this->didFinishRestore();
    }

    public function finalizeExternalRestore($actionId)
    {
        $action = self::getAction($actionId);

        $this->_state = backupGuardLoadStateData();
        $this->prepareForRestore($action['name']);

        $this->_databaseBackupAvailable = file_exists($this->_databaseBackupPath);

        if ($this->_databaseBackupAvailable) {
            $this->_backupDatabase->finalizeRestore();
        }

        $this->didFinishRestore();
    }

    /* General methods */

    public static function getLogFileHeader($actionType, $fileName, $isUpload = false)
    {
        $pluginCapabilities = backupGuardGetCapabilities();
        $timezone           = SGConfig::get('SG_TIMEZONE') ?: SG_DEFAULT_TIMEZONE;

        $confs                            = array();
        $confs['sg_backup_guard_version'] = SG_BACKUP_GUARD_VERSION;
        $confs['sg_archive_version']      = SG_ARCHIVE_VERSION;
        $confs['sg_user_mode']            = ($pluginCapabilities != BACKUP_GUARD_CAPABILITIES_FREE) ? 'pro' : 'free'; // Check if user is pro or free
        $confs['os']                      = PHP_OS;
        $confs['server']                  = @$_SERVER['SERVER_SOFTWARE'];
        $confs['php_version']             = PHP_VERSION;
        $confs['sapi']                    = PHP_SAPI;
        $confs['mysql_version']           = SG_MYSQL_VERSION;
        $confs['int_size']                = PHP_INT_SIZE;
        $confs['method']                  = backupGuardIsReloadEnabled() ? 'ON' : 'OFF';

        $confs['dbprefix']            = SG_ENV_DB_PREFIX;
        $confs['siteurl']             = SG_SITE_URL;
        $confs['homeurl']             = SG_HOME_URL;
        $confs['uploadspath']         = SG_UPLOAD_PATH;
        $confs['installation']        = SG_SITE_TYPE;
        $freeSpace                    = backupGuardDiskFreeSize(SG_APP_ROOT_DIRECTORY);
        $confs['free_space']          = $freeSpace == false ? 'unknown' : $freeSpace;
        $isCurlAvailable              = function_exists('curl_version');
        $confs['curl_available']      = $isCurlAvailable ? 'Yes' : 'No';
        $confs['email_notifications'] = SGConfig::get('SG_NOTIFICATIONS_ENABLED') ? 'ON' : 'OFF';
        $confs['ftp_passive_mode']    = SGConfig::get('SG_FTP_PASSIVE_MODE') ? 'ON' : 'OFF';

        if (extension_loaded('gmp')) {
            $lib = 'gmp';
        } else if (extension_loaded('bcmath')) {
            $lib = 'bcmath';
        } else {
            $lib = 'BigInteger';
        }

        $confs['int_lib']            = $lib;
        $confs['memory_limit']       = SGBoot::$memoryLimit;
        $confs['max_execution_time'] = SGBoot::$executionTimeLimit;
        $confs['env']                = SG_ENV_ADAPTER . ' ' . SG_ENV_VERSION;

        $content = '';
        $content .= 'Date: ' . backupGuardConvertDateTimezone(@date('Y-m-d H:i'), true) . ' ' . $timezone . PHP_EOL;
        $content .= 'Reloads: ' . $confs['method'] . PHP_EOL;

        if ($actionType == SG_ACTION_TYPE_RESTORE) {
            $confs['restore_method'] = SGExternalRestore::isEnabled() ? 'external' : 'standard';
            $content                 .= 'Restore Method: ' . $confs['restore_method'] . PHP_EOL;
        }

        $content .= 'User mode: ' . backupGuardGetProductName() . PHP_EOL;
        $content .= 'BackupGuard version: ' . $confs['sg_backup_guard_version'] . PHP_EOL;
        $content .= 'Supported archive version: ' . $confs['sg_archive_version'] . PHP_EOL;

        $content .= 'Database prefix: ' . $confs['dbprefix'] . PHP_EOL;
        $content .= 'Site URL: ' . $confs['siteurl'] . PHP_EOL;
        $content .= 'Home URL: ' . $confs['homeurl'] . PHP_EOL;
        $content .= 'Uploads path: ' . $confs['uploadspath'] . PHP_EOL;
        $content .= 'Site installation: ' . $confs['installation'] . PHP_EOL;

        $content .= 'OS: ' . $confs['os'] . PHP_EOL;
        $content .= 'Server: ' . $confs['server'] . PHP_EOL;
        $content .= 'User agent: ' . @$_SERVER['HTTP_USER_AGENT'] . PHP_EOL;
        $content .= 'PHP version: ' . $confs['php_version'] . PHP_EOL;
        $content .= 'MySQL version: ' . $confs['mysql_version'] . PHP_EOL;
        $content .= 'Int size: ' . $confs['int_size'] . PHP_EOL;
        $content .= 'Int lib: ' . $confs['int_lib'] . PHP_EOL;
        $content .= 'Memory limit: ' . $confs['memory_limit'] . PHP_EOL;
        $content .= 'Max execution time: ' . $confs['max_execution_time'] . PHP_EOL;
        $content .= 'Disk free space: ' . $confs['free_space'] . PHP_EOL;
        $content .= 'CURL available: ' . $confs['curl_available'] . PHP_EOL;
        $content .= 'Openssl version: ' . OPENSSL_VERSION_TEXT . PHP_EOL;
        if ($isCurlAvailable) {
            $cv              = curl_version();
            $curlVersionText = $cv['version'] . ' / SSL: ' . $cv['ssl_version'] . ' / libz: ' . $cv['libz_version'];
            $content         .= 'CURL version: ' . $curlVersionText . PHP_EOL;
        }
        $content .= 'Email notifications: ' . $confs['email_notifications'] . PHP_EOL;
        $content .= 'FTP passive mode: ' . $confs['ftp_passive_mode'] . PHP_EOL;
        $content .= 'Exclude paths: ' . SGConfig::get('SG_PATHS_TO_EXCLUDE') . PHP_EOL;
        $content .= 'Tables to exclude: ' . SGConfig::get('SG_TABLES_TO_EXCLUDE') . PHP_EOL;
        $content .= 'Number of rows to backup: ' . (int) SGConfig::get('SG_BACKUP_DATABASE_INSERT_LIMIT') . PHP_EOL;
        $content .= 'AJAX request frequency: ' . SGConfig::get('SG_AJAX_REQUEST_FREQUENCY') . PHP_EOL;

        if ($actionType == SG_ACTION_TYPE_BACKUP && $isUpload) {
            $content .= 'Upload chunk size: ' . SGConfig::get('SG_BACKUP_CLOUD_UPLOAD_CHUNK_SIZE') . 'MB' . PHP_EOL;
        }

        if ($actionType == SG_ACTION_TYPE_RESTORE) {
            $archivePath          = SG_BACKUP_DIRECTORY . $fileName . '/' . $fileName . '.sgbp';
            $archiveSizeInBytes   = backupGuardRealFilesize($archivePath);
            $confs['archiveSize'] = convertToReadableSize($archiveSizeInBytes);
            $content              .= 'Archive Size: ' . $confs['archiveSize'] . ' (' . $archiveSizeInBytes . ' bytes)' . PHP_EOL;
        }

        $content .= 'Environment: ' . $confs['env'] . PHP_EOL;

        return $content;
    }

    private function didFindWarnings()
    {
        $warningsDatabase = $this->_databaseBackupAvailable ? $this->_backupDatabase->didFindWarnings() : false;
        $warningsFiles    = $this->_backupFiles->didFindWarnings();

        return ($warningsFiles || $warningsDatabase);
    }

    public static function createAction($name, $type, $status, $subtype = 0, $options = '')
    {
        $sgdb = SGDatabase::getInstance();

        $date = backupGuardConvertDateTimezone(@date('Y-m-d H:i:s'), true);
        $res  = $sgdb->query('INSERT INTO ' . SG_ACTION_TABLE_NAME . ' (name, type, subtype, status, start_date, options) VALUES (%s, %d, %d, %d, %s, %s)', array($name, $type, $subtype, $status, $date, $options));

        if (!$res) {
            throw new SGExceptionDatabaseError('Could not create action');
        }

        return $sgdb->lastInsertId();
    }

    private function getCurrentActionStatus()
    {
        return self::getActionStatus($this->_actionId);
    }

    private function setCurrentActionStatusCancelled()
    {
        $sgdb = SGDatabase::getInstance();
        $date = backupGuardConvertDateTimezone(@date('Y-m-d H:i:s'), true);
        $sgdb->query('UPDATE ' . SG_ACTION_TABLE_NAME . ' SET status=%d, update_date=%s WHERE name=%s', array(SG_ACTION_STATUS_CANCELLED, $date, $this->_fileName));
    }

    public static function changeActionStatus($actionId, $status)
    {
        $sgdb = SGDatabase::getInstance();

        $progress = '';
        if ($status == SG_ACTION_STATUS_FINISHED || $status == SG_ACTION_STATUS_FINISHED_WARNINGS) {
            $progress = 100;
        } else if ($status == SG_ACTION_STATUS_CREATED || $status == SG_ACTION_STATUS_IN_PROGRESS_FILES || $status == SG_ACTION_STATUS_IN_PROGRESS_DB) {
            $progress = 0;
        }

        if ($progress !== '') {
            $progress = ' progress=' . $progress . ',';
        }

        $date = backupGuardConvertDateTimezone(@date('Y-m-d H:i:s'), true);
        $sgdb->query('UPDATE ' . SG_ACTION_TABLE_NAME . ' SET status=%d,' . $progress . ' update_date=%s WHERE id=%d', array($status, $date, $actionId));
    }

    public static function changeActionProgress($actionId, $progress)
    {
        $sgdb = SGDatabase::getInstance();
        $date = backupGuardConvertDateTimezone(@date('Y-m-d H:i:s'), true);
        $sgdb->query('UPDATE ' . SG_ACTION_TABLE_NAME . ' SET progress=%d, update_date=%s WHERE id=%d', array($progress, $date, $actionId));
    }

    /* Methods for frontend use */

    public static function getAction($actionId)
    {
        $sgdb = SGDatabase::getInstance();
        $res  = $sgdb->query('SELECT * FROM ' . SG_ACTION_TABLE_NAME . ' WHERE id=%d', array($actionId));
        if (empty($res)) {
            return false;
        }

        return $res[0];
    }

    public static function getActionByName($name)
    {
        $sgdb = SGDatabase::getInstance();
        $res  = $sgdb->query('SELECT * FROM ' . SG_ACTION_TABLE_NAME . ' WHERE name=%s', array($name));
        if (empty($res)) {
            return false;
        }

        return $res[0];
    }

    public static function getActionProgress($actionId)
    {
        $sgdb = SGDatabase::getInstance();
        $res  = $sgdb->query('SELECT progress FROM ' . SG_ACTION_TABLE_NAME . ' WHERE id=%d', array($actionId));
        if (empty($res)) {
            return false;
        }

        return (int) $res[0]['progress'];
    }

    public static function getActionStatus($actionId)
    {
        $sgdb = SGDatabase::getInstance();
        $res  = $sgdb->query('SELECT status FROM ' . SG_ACTION_TABLE_NAME . ' WHERE id=%d', array($actionId));
        if (empty($res)) {
            return false;
        }

        return (int) $res[0]['status'];
    }

    public static function deleteActionById($actionId)
    {
        $sgdb = SGDatabase::getInstance();
        $res  = $sgdb->query('DELETE FROM ' . SG_ACTION_TABLE_NAME . ' WHERE id=%d', array($actionId));

        return $res;
    }

    public static function cleanRunningActions($runningActions)
    {
        if (empty($runningActions)) {
            return false;
        }
        foreach ($runningActions as $action) {
            if (empty($action)) {
                continue;
            }
            if ($action['status'] == SG_ACTION_STATUS_IN_PROGRESS_FILES || $action['status'] == SG_ACTION_STATUS_IN_PROGRESS_DB) {
                $id = $action['id'];
                SGBackup::deleteActionById($id);
            }
        }

        return true;
    }

    public static function getRunningActions()
    {
        $sgdb = SGDatabase::getInstance();
        $res  = $sgdb->query('SELECT * FROM ' . SG_ACTION_TABLE_NAME . ' WHERE status=%d OR status=%d OR status=%d ORDER BY status DESC', array(SG_ACTION_STATUS_IN_PROGRESS_FILES, SG_ACTION_STATUS_IN_PROGRESS_DB, SG_ACTION_STATUS_CREATED));

        return $res;
    }

    public static function getBackupFileInfo($file)
    {
        return pathinfo(SG_BACKUP_DIRECTORY . $file);
    }

    public static function autodetectBackups()
    {
        $path              = SG_BACKUP_DIRECTORY;
        $files             = scandir(SG_BACKUP_DIRECTORY);
        $backupLogPostfix  = "_backup.log";
        $restoreLogPostfix = "_restore.log";

        foreach ($files as $file) {
            $fileInfo = self::getBackupFileInfo($file);

            if (!empty($fileInfo['extension']) && $fileInfo['extension'] == SGBP_EXT) {
                @mkdir($path . $fileInfo['filename'], 0777);

                if (file_exists($path . $fileInfo['filename'])) {
                    rename($path . $file, $path . $fileInfo['filename'] . '/' . $file);
                }

                if (file_exists($path . $fileInfo['filename'] . $backupLogPostfix)) {
                    rename($path . $fileInfo['filename'] . $backupLogPostfix, $path . $fileInfo['filename'] . '/' . $fileInfo['filename'] . $backupLogPostfix);
                }

                if (file_exists($path . $fileInfo['filename'] . $restoreLogPostfix)) {
                    rename($path . $fileInfo['filename'] . $restoreLogPostfix, $path . $fileInfo['filename'] . '/' . $fileInfo['filename'] . $restoreLogPostfix);
                }
            }
        }
    }

    public static function getAllBackups()
    {
        $backups = array();

        $path = SG_BACKUP_DIRECTORY;
        self::autodetectBackups();
        clearstatcache();

        $action = self::getRunningActions();
        if (SGBoot::isFeatureAvailable('NUMBER_OF_BACKUPS_TO_KEEP') && !count($action) && function_exists('backupGuardOutdatedBackupsCleanup')) {
            backupGuardOutdatedBackupsCleanup($path);
        }

        //remove external restore file
        SGExternalRestore::getInstance()->cleanup();

        if ($handle = @opendir($path)) {
            $sgdb       = SGDatabase::getInstance();
            $data       = $sgdb->query('SELECT id, name, type, subtype, status, progress, update_date, options FROM ' . SG_ACTION_TABLE_NAME);
            $allBackups = array();
            foreach ($data as $row) {
                $allBackups[$row['name']][] = $row;
            }

            while (($entry = readdir($handle)) !== false) {
                if ($entry === '.' || $entry === '..' || !is_dir($path . $entry)) {
                    continue;
                }

                $backup                = array();
                $backup['name']        = $entry;
                $backup['id']          = '';
                $backup['status']      = '';
                $backup['files']       = file_exists($path . $entry . '/' . $entry . '.sgbp') ? 1 : 0;
                $backup['backup_log']  = file_exists($path . $entry . '/' . $entry . '_backup.log') ? 1 : 0;
                $backup['restore_log'] = file_exists($path . $entry . '/' . $entry . '_restore.log') ? 1 : 0;
                $backup['options']     = '';
                if (!$backup['files'] && !$backup['backup_log'] && !$backup['restore_log']) {
                    continue;
                }
                $backupRow = null;
                if (isset($allBackups[$entry])) {
                    $skip = false;
                    foreach ($allBackups[$entry] as $row) {
                        if ($row['status'] == SG_ACTION_STATUS_IN_PROGRESS_FILES || $row['status'] == SG_ACTION_STATUS_IN_PROGRESS_DB) {
                            $backupRow = $row;
                            break;
                        } else if (($row['status'] == SG_ACTION_STATUS_CANCELLING || $row['status'] == SG_ACTION_STATUS_CANCELLED) && $row['type'] != SG_ACTION_TYPE_UPLOAD) {
                            $skip = true;
                            break;
                        }

                        $backupRow = $row;

                        if ($row['status'] == SG_ACTION_STATUS_FINISHED_WARNINGS || $row['status'] == SG_ACTION_STATUS_ERROR) {
                            if ($row['type'] == SG_ACTION_TYPE_UPLOAD && file_exists(SG_BACKUP_DIRECTORY . $entry . '/' . $entry . '.sgbp')) {
                                $backupRow['status'] = SG_ACTION_STATUS_FINISHED_WARNINGS;
                            }
                        }
                    }

                    if ($skip === true) {
                        continue;
                    }
                }

                if ($backupRow) {
                    $backup['active'] = ($backupRow['status'] == SG_ACTION_STATUS_IN_PROGRESS_FILES ||
                        $backupRow['status'] == SG_ACTION_STATUS_IN_PROGRESS_DB ||
                        $backupRow['status'] == SG_ACTION_STATUS_CREATED) ? 1 : 0;

                    $backup['status']   = $backupRow['status'];
                    $backup['type']     = (int) $backupRow['type'];
                    $backup['subtype']  = (int) $backupRow['subtype'];
                    $backup['progress'] = (int) $backupRow['progress'];
                    $backup['id']       = (int) $backupRow['id'];
                    $backup['options']  = $backupRow['options'];
                } else {
                    $backup['active'] = 0;
                }

                $size = '';
                if ($backup['files']) {
                    $size = number_format(backupGuardRealFilesize($path . $entry . '/' . $entry . '.sgbp') / 1000.0 / 1000.0, 2, '.', '') . ' MB';
                }

                $backup['size'] = $size;

                $modifiedTime           = filemtime($path . $entry . '/.');
                $date                   = backupGuardConvertDateTimezone(@date('Y-m-d H:i', $modifiedTime));
                $backup['date']         = $date;
                $backup['modifiedTime'] = $modifiedTime;
                $backups[]              = $backup;
            }
            closedir($handle);
        }

        usort($backups, array('SGBackup', 'sort'));

        return array_values($backups);
    }

    public static function sort($arg1, $arg2)
    {
        return $arg1['modifiedTime'] > $arg2['modifiedTime'] ? -1 : 1;
    }

    public static function deleteBackup($backupName, $deleteAction = true)
    {
        $isDeleteBackupFromCloudEnabled = SGConfig::get('SG_DELETE_BACKUP_FROM_CLOUD');
        if ($isDeleteBackupFromCloudEnabled) {
            $backupRow = self::getActionByName($backupName);
            if ($backupRow) {
                $options = $backupRow['options'];
                if ($options) {
                    $options = json_decode($options, true);

                    if (!empty($options['SG_BACKUP_UPLOAD_TO_STORAGES'])) {
                        $storages = explode(',', $options['SG_BACKUP_UPLOAD_TO_STORAGES']);
                        self::deleteBackupFromCloud($storages, $backupName);
                    }
                }
            }
        }

        backupGuardDeleteDirectory(SG_BACKUP_DIRECTORY . $backupName);

        if ($deleteAction) {
            $sgdb = SGDatabase::getInstance();
            $sgdb->query('DELETE FROM ' . SG_ACTION_TABLE_NAME . ' WHERE name=%s', array($backupName));
        }
    }

    private static function deleteBackupFromCloud($storages, $backupName)
    {
        foreach ($storages as $storage) {
            $storage = (int) $storage;

            $sgBackupStorage = SGBackupStorage::getInstance();
            $sgBackupStorage->deleteBackupFromStorage($storage, $backupName);
        }
    }

    public static function cancelAction($actionId)
    {
        self::changeActionStatus($actionId, SG_ACTION_STATUS_CANCELLING);
    }

    public static function importKeyFile($sgSshKeyFile)
    {
        $filename   = $sgSshKeyFile['name'];
        $uploadPath = SG_BACKUP_DIRECTORY . SG_SSH_KEY_FILE_FOLDER_NAME;
        $filename   = $uploadPath . $filename;

        if (!@file_exists($uploadPath)) {
            if (!@mkdir($uploadPath)) {
                throw new SGExceptionForbidden('SSH key file folder is not accessible');
            }
        }

        if (!empty($sgSshKeyFile) && $sgSshKeyFile['name'] != '') {
            if (!@move_uploaded_file($sgSshKeyFile['tmp_name'], $filename)) {
                throw new SGExceptionForbidden('Error while uploading ssh key file');
            }
        }
    }

    public static function upload($filesUploadSgbp)
    {
        $filename        = str_replace('.sgbp', '', $filesUploadSgbp['name']);
        $backupDirectory = $filename . '/';
        $uploadPath      = SG_BACKUP_DIRECTORY . $backupDirectory;
        $filename        = $uploadPath . $filename;

        if (!@file_exists($uploadPath)) {
            if (!@mkdir($uploadPath)) {
                throw new SGExceptionForbidden('Upload folder is not accessible');
            }
        }

        if (!empty($filesUploadSgbp) && $filesUploadSgbp['name'] != '') {
            if ($filesUploadSgbp['type'] != 'application/octet-stream') {
                throw new SGExceptionBadRequest('Not a valid backup file');
            }
            if (!@move_uploaded_file($filesUploadSgbp['tmp_name'], $filename . '.sgbp')) {
                throw new SGExceptionForbidden('Error while uploading file');
            }
        }
    }

    public static function download($filename, $type)
    {
        $backupDirectory = SG_BACKUP_DIRECTORY . $filename . '/';
        $downloadMode    = SGConfig::get('SG_DOWNLOAD_MODE');

        switch ($type) {
            case SG_BACKUP_DOWNLOAD_TYPE_SGBP:
                $filename .= '.sgbp';
                if ($downloadMode == 1) {
                    backupGuardDownloadFile($backupDirectory . $filename);
                } else {
                    backupGuardDownloadFileViaFunction($backupDirectory, $filename, $downloadMode);
                }
                break;
            case SG_BACKUP_DOWNLOAD_TYPE_BACKUP_LOG:
                $filename .= '_backup.log';
                backupGuardDownloadFile($backupDirectory . $filename, 'text/plain');
                break;
            case SG_BACKUP_DOWNLOAD_TYPE_RESTORE_LOG:
                $filename .= '_restore.log';
                backupGuardDownloadFile($backupDirectory . $filename, 'text/plain');
                break;
        }

        exit;
    }

    /* SGIBackupDelegate implementation */

    public function isCancelled()
    {
        $status = $this->getCurrentActionStatus();

        if ($status == SG_ACTION_STATUS_CANCELLING) {
            $this->cancel();

            return true;
        }

        return false;
    }

    public function didUpdateProgress($progress)
    {
        $progress = max($progress, 0);
        $progress = min($progress, 100);

        self::changeActionProgress($this->_actionId, $progress);
    }

    public function isBackgroundMode()
    {
        return $this->_backgroundMode;
    }

    public function setIsManual($isManual)
    {
        $this->_isManual = $isManual;
    }

    public function getIsManual()
    {
        return $this->_isManual;
    }

    public function getIsUploadStorage()
    {
        $uploadToStoragesString = $this->_options['SG_BACKUP_UPLOAD_TO_STORAGES'];
        if (empty($uploadToStoragesString)) {
            return false;
        }

        $uploadToStorages = explode(',', $uploadToStoragesString);
        if (count($uploadToStorages)) {
            return true;
        }

        return false;
    }
}
