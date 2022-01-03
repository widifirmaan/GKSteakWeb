<?php

require_once SG_BACKUP_PATH . 'SGBackup.php';
backupGuardIncludeFile(SG_STORAGE_PATH . 'SGGoogleDriveStorage.php');
backupGuardIncludeFile(SG_STORAGE_PATH . 'SGDropboxStorage.php');
backupGuardIncludeFile(SG_STORAGE_PATH . 'SGOneDriveStorage.php');
backupGuardIncludeFile(SG_STORAGE_PATH . 'SGPCloudStorage.php');
backupGuardIncludeFile(SG_STORAGE_PATH . 'SGBoxStorage.php');
backupGuardIncludeFile(SG_STORAGE_PATH . 'SGFTPManager.php');
backupGuardIncludeFile(SG_STORAGE_PATH . 'SGAmazonStorage.php');
backupGuardIncludeFile(SG_STORAGE_PATH . 'BackupGuardStorage.php');

class SGBackupStorage implements SGIStorageDelegate
{

    private static $_instance = null;
    private $_actionId = null;
    private $_currentUploadChunksCount = 0;
    private $_totalUploadChunksCount = 0;
    private $_progressUpdateInterval = 0;
    private $_nextProgressUpdate = 0;
    private $_backgroundMode = false;
    private $_delegate = null;
    private $_state = null;
    private $_token = null;
    private $_pendingStorageUploads = array();
    private $_reloadStartTs;

    private function __construct()
    {
        $this->_backgroundMode         = SGConfig::get('SG_BACKUP_IN_BACKGROUND_MODE');
        $this->_progressUpdateInterval = SGConfig::get('SG_ACTION_PROGRESS_UPDATE_INTERVAL');
    }

    private function __clone()
    {
    }

    public function setPendingStorageUploads($pendingStorageUploads)
    {
        $this->_pendingStorageUploads = $pendingStorageUploads;
    }

    public function setToken($token)
    {
        $this->_token = $token;
    }

    public function setState($state)
    {
        $this->_state = $state;
    }

    public function setDelegate($delegate)
    {
        $this->_delegate = $delegate;
    }

    public function getPendingStorageUploads()
    {
        return $this->_pendingStorageUploads;
    }

    public function getState()
    {
        return $this->_state;
    }

    public function getActionId()
    {
        return $this->_actionId;
    }

    public function getCurrentUploadChunksCount()
    {
        return $this->_currentUploadChunksCount;
    }

    public function reload()
    {
        $this->_delegate->reload();
    }

    public function getToken()
    {
        return $this->_token;
    }

    public function getProgress()
    {
        return $this->_nextProgressUpdate;
    }

    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function deleteBackupFromStorage($storageId, $backupName)
    {
        try {
            $uploadFolder = trim(SGConfig::get('SG_STORAGE_BACKUPS_FOLDER_NAME'), '/');

            $storage = $this->storageObjectById($storageId);
            $path    = "/" . $uploadFolder . "/" . $backupName . ".sgbp";

            if ($storage) {
                $storage->deleteFile($path);
            }
        } catch (Exception $e) {
        }
    }

    public function listStorage($storageId, $storageName = '')
    {
        $storage     = $this->storageObjectById($storageId, $storageName);
        $listOfFiles = $storage->getListOfFiles();

        return $listOfFiles;
    }

    public function downloadBackupArchiveFromCloud($storageId, $archive, $size, $backupId = null, $storageName = '')
    {
        $storage = $this->storageObjectById($storageId, $storageName);
        $result  = $storage->downloadFile($archive, $size, $backupId);

        return $result ? true : false;
    }

    public static function queueBackupForUpload($backupName, $storageId, $options)
    {
        return SGBackup::createAction($backupName, SG_ACTION_TYPE_UPLOAD, SG_ACTION_STATUS_CREATED, $storageId, json_encode($options));
    }

    public function startUploadByActionId($actionId, $storageName = '')
    {
        if ($this->_state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {
            $sgdb = SGDatabase::getInstance();

            $res = $sgdb->query('SELECT * FROM ' . SG_ACTION_TABLE_NAME . ' WHERE id=%d LIMIT 1', array( $actionId ));

            if (!count($res)) {
                return false;
            }

            $row = $res[0];

            if ($row['type'] != SG_ACTION_TYPE_UPLOAD) {
                return false;
            }

            $this->_actionId = $actionId;
            $type           = $row['subtype'];
            $backupName     = $row['name'];
        } else {
            $this->_nextProgressUpdate       = $this->_state->getProgress() ? $this->_state->getProgress() : $this->_progressUpdateInterval;
            $this->_actionId                 = $this->_state->getActionId();
            $this->_currentUploadChunksCount = $this->_state->getCurrentUploadChunksCount();
            $type                           = $this->_state->getStorageType();
            $backupName                     = $this->_state->getBackupFileName();
        }

        $storage = $this->storageObjectById($type, $storageName);
        $this->startBackupUpload($backupName, $storage, $storageName);

        return true;
    }

    public function startDownloadByActionId($actionId)
    {
        $sgdb = SGDatabase::getInstance();

        $res = $sgdb->query('SELECT * FROM ' . SG_ACTION_TABLE_NAME . ' WHERE id=%d LIMIT 1', array( $actionId ));

        if (! count($res)) {
            return false;
        }

        $row = $res[0];

        if ($row['type'] != SG_ACTION_TYPE_UPLOAD) {
            return false;
        }

        $this->_actionId = $actionId;

        return true;
    }

    private function storageObjectById($storageId, &$storageName = '')
    {
        $res              = $this->getStorageInfoById($storageId);
        $storageName      = $res['storageName'];
        $storageClassName = $res['storageClassName'];

        if (!$storageClassName) {
            throw new SGExceptionNotFound('Unknown storage');
        }

        return new $storageClassName();
    }

    public function getStorageInfoById($storageId)
    {
        $storageName      = '';
        $storageClassName = '';
        $storageId        = (int) $storageId;
        $isConnected      = true;

        switch ($storageId) {
            case SG_STORAGE_FTP:
                if (SGBoot::isFeatureAvailable('FTP')) {
                    $connectionMethod = SGConfig::get('SG_STORAGE_CONNECTION_METHOD');

                    if ($connectionMethod == 'ftp') {
                        $storageName = 'FTP';
                    } else {
                        $storageName = 'SFTP';
                    }
                    $isFtpConnected = SGConfig::get('SG_STORAGE_FTP_CONNECTED');

                    if (empty($isFtpConnected)) {
                        $isConnected = false;
                    }
                    $storageClassName = "SGFTPManager";
                }
                break;
            case SG_STORAGE_DROPBOX:
                if (SGBoot::isFeatureAvailable('DROPBOX')) {
                    $storageName      = 'Dropbox';
                    $storageClassName = "SGDropboxStorage";
                }
                $isDropboxConnected = SGConfig::get('SG_DROPBOX_ACCESS_TOKEN');

                if (empty($isDropboxConnected)) {
                    $isConnected = false;
                }
                break;
            case SG_STORAGE_GOOGLE_DRIVE:
                if (SGBoot::isFeatureAvailable('GOOGLE_DRIVE')) {
                    $storageName      = 'Google Drive';
                    $storageClassName = "SGGoogleDriveStorage";
                }
                $isGdriveConnected = SGConfig::get('SG_GOOGLE_DRIVE_REFRESH_TOKEN');

                if (empty($isGdriveConnected)) {
                    $isConnected = false;
                }
                break;
            case SG_STORAGE_AMAZON:
                if (SGBoot::isFeatureAvailable('AMAZON')) {
                    $storageName      = 'Amazon S3';
                    $storageClassName = "SGAmazonStorage";
                }
                $isAmazonConnected = SGConfig::get('SG_STORAGE_AMAZON_CONNECTED');

                if (empty($isAmazonConnected)) {
                    $isConnected = false;
                }
                break;
            case SG_STORAGE_ONE_DRIVE:
                if (SGBoot::isFeatureAvailable('ONE_DRIVE')) {
                    $storageName      = 'One Drive';
                    $storageClassName = "SGOneDriveStorage";
                }
                $isOneDriveConnected = SGConfig::get('SG_ONE_DRIVE_REFRESH_TOKEN');

                if (empty($isOneDriveConnected)) {
                    $isConnected = false;
                }
                break;
            case SG_STORAGE_P_CLOUD:
                if (SGBoot::isFeatureAvailable('P_CLOUD')) {
                    $storageName      = 'pCloud';
                    $storageClassName = "SGPCloudStorage";
                }

                $isPCloudConnected = SGConfig::get('SG_P_CLOUD_ACCESS_TOKEN');

                if (empty($isPCloudConnected)) {
                    $isConnected = false;
                }
                break;
            case SG_STORAGE_BOX:
                if (SGBoot::isFeatureAvailable('BOX')) {
                    $storageName      = 'box.com';
                    $storageClassName = "SGBoxStorage";
                }

                $isBoxConnected = SGConfig::get('SG_BOX_REFRESH_TOKEN');

                if (empty($isBoxConnected)) {
                    $isConnected = false;
                }
                break;
            case SG_STORAGE_BACKUP_GUARD:
                if (SGBoot::isFeatureAvailable('BACKUP_GUARD') && SG_SHOW_BACKUPGUARD_CLOUD) {
                    $storageName      = 'BackupGuard';
                    $storageClassName = "BackupGuard\Storage";
                }
                $isBackupGuardConnected = SGConfig::get('SG_BACKUPGUARD_CLOUD_ACCOUNT') ? unserialize(SGConfig::get('SG_BACKUPGUARD_CLOUD_ACCOUNT')) : '';

                if (empty($isBackupGuardConnected)) {
                    $isConnected = false;
                }
                break;
        }

        $res = array(
            'storageName'      => $storageName,
            'storageClassName' => $storageClassName,
            'isConnected'      => $isConnected,
        );

        return $res;
    }

    public function shouldUploadNextChunk()
    {
        /*if (SGBoot::isFeatureAvailable('BACKGROUND_MODE') && $this->_backgroundMode)
        {
        SGBackgroundMode::next();
        }*/

        $this->_currentUploadChunksCount ++;
        if ($this->updateProgress()) {
            $this->checkCancellation();
        }

        return true;
    }

    public function willStartUpload($chunksCount)
    {
        $this->_totalUploadChunksCount = $chunksCount;

        if ($this->_state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {
            $this->resetProgress();
        }
    }

    public function updateProgressManually($progress)
    {
        /*if (SGBoot::isFeatureAvailable('BACKGROUND_MODE') && $this->_backgroundMode)
        {
        SGBackgroundMode::next();
        }*/

        if ($this->updateProgress($progress)) {
            $this->checkCancellation();
        }
    }

    private function updateProgress($progress = null)
    {
        if (!$progress) {
            $progress = (int) ceil($this->_currentUploadChunksCount * 100.0 / $this->_totalUploadChunksCount);
        }

        if ($progress >= $this->_nextProgressUpdate) {
            $this->_nextProgressUpdate += $this->_progressUpdateInterval;

            $progress = max($progress, 0);
            $progress = min($progress, 100);
            SGBackup::changeActionProgress($this->_actionId, $progress);

            return true;
        }

        return false;
    }

    private function resetProgress()
    {
        $this->_currentUploadChunksCount = 0;
        $this->_nextProgressUpdate       = $this->_progressUpdateInterval;
    }

    private function checkCancellation()
    {
        $status = SGBackup::getActionStatus($this->_actionId);
        if ($status == SG_ACTION_STATUS_CANCELLING) {
            SGBackupLog::write('Upload cancelled');
            throw new SGExceptionSkip();
        } elseif ($status == SG_ACTION_STATUS_ERROR) {
            SGBackupLog::write('Upload timeout error');
            throw new SGExceptionExecutionTimeError();
        }
    }

    public function shouldReload()
    {
        $currentTime = time();

        if (( $currentTime - $this->_reloadStartTs ) >= SG_RELOAD_TIMEOUT) {
            return true;
        }

        return false;
    }

    private function startBackupUpload($backupName, SGStorage $storage, $storageName)
    {
        $this->_reloadStartTs = time();
        if ($this->_state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {
            $actionStartTs = time();
        } else {
            $actionStartTs = $this->_state->getActionStartTs();
        }

        SGPing::update();

        $backupPath      = SG_BACKUP_DIRECTORY . $backupName;
        $filesBackupPath = $backupPath . '/' . $backupName . '.sgbp';

        if (! is_readable($filesBackupPath)) {
            SGBackup::changeActionStatus($this->_actionId, SG_ACTION_STATUS_ERROR);
            throw new SGExceptionNotFound('Backup not found');
        }

        try {
            @session_write_close();

            if ($this->_state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {
                SGBackup::changeActionStatus($this->_actionId, SG_ACTION_STATUS_IN_PROGRESS_FILES);

                SGBackupLog::write('-');
                SGBackupLog::writeAction('upload to ' . $storageName, SG_BACKUP_LOG_POS_START);
                SGBackupLog::write('Authenticating');
            }

            $storage->setDelegate($this);
            $storage->loadState();
            $storage->connectOffline();

            //get backups container folder
            $backupsFolder = $this->_state->getActiveDirectory();

            if ($this->_state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {
                SGBackupLog::write('Preparing folder');

                $folderTree = SG_BACKUP_DEFAULT_FOLDER_NAME;

                if (SGBoot::isFeatureAvailable('SUBDIRECTORIES')) {
                    $folderTree = SGConfig::get('SG_STORAGE_BACKUPS_FOLDER_NAME');
                }

                //create backups container folder, if needed
                $backupsFolder = $storage->createFolder($folderTree);
            }

            $storage->setActiveDirectory($backupsFolder);

            if ($this->_state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {
                SGBackupLog::write('Uploading file');
            }

            $storage->uploadFile($filesBackupPath);

            SGBackupLog::writeAction('upload to ' . $storageName, SG_BACKUP_LOG_POS_END);

            //Writing upload status to report file
            file_put_contents($backupPath . '/' . SG_REPORT_FILE_NAME, 'Uploaded to ' . $storageName . ": completed\n", FILE_APPEND);
            SGBackupLog::write('Total duration: ' . backupGuardFormattedDuration($actionStartTs, time()));

            SGBackup::changeActionStatus($this->_actionId, SG_ACTION_STATUS_FINISHED);
        } catch (Exception $exception) {
            if ($exception instanceof SGExceptionSkip) {
                SGBackup::changeActionStatus($this->_actionId, SG_ACTION_STATUS_CANCELLED);
                //Writing upload status to report file
                file_put_contents($backupPath . '/' . SG_REPORT_FILE_NAME, 'Uploaded to ' . $storageName . ': canceled', FILE_APPEND);
                SGBackupMailNotification::sendBackupNotification(
                    SG_ACTION_STATUS_CANCELLED,
                    array(
                        'flowFilePath' => $backupPath . '/' . SG_REPORT_FILE_NAME,
                        'archiveName'  => $backupName
                    )
                );
            } else {
                SGBackup::changeActionStatus($this->_actionId, SG_ACTION_STATUS_FINISHED_WARNINGS);

                if (!$exception instanceof SGExceptionExecutionTimeError) {//to prevent log duplication for timeout exception
                    SGBackupLog::writeExceptionObject($exception);
                }

                if (SGBoot::isFeatureAvailable('NOTIFICATIONS')) {
                    //Writing upload status to report file
                    file_put_contents($backupPath . '/' . SG_REPORT_FILE_NAME, 'Uploaded to ' . $storageName . ': failed', FILE_APPEND);
                    SGBackupMailNotification::sendBackupNotification(
                        SG_ACTION_STATUS_ERROR,
                        array(
                            'flowFilePath' => $backupPath . '/' . SG_REPORT_FILE_NAME,
                            'archiveName'  => $backupName
                        )
                    );
                }
            }

            //delete file inside storage
            $storageId = $this->_state->getStorageType();
            $this->deleteBackupFromStorage($storageId, $backupName);

            //delete report file in case of error
            @unlink($backupPath . '/' . SG_REPORT_FILE_NAME);
        }
    }
}
