<?php

require_once(SG_LIB_PATH . 'SGReloadHandler.php');
require_once(SG_LIB_PATH . 'SGState.php');
require_once(SG_LIB_PATH . 'SGUploadState.php');

interface SGIStorageDelegate
{
    public function willStartUpload($chunksCount);

    public function updateProgressManually($progress);

    public function shouldUploadNextChunk();
}

abstract class SGStorage // phpcs:ignore
{
    // phpcs:disable
    protected $connected = false;
    protected $activeDirectory = '';
    protected $delegate = null;
    protected $state = null;
    // phpcs:enable

    /* Make all initializations here */
    abstract public function init();

    /* Connect to the Storage */
    abstract public function connect();

    /* Connect offline. This will ensure that redirections to other pages won't be made */
    abstract public function connectOffline();

    /* Check if it is already connected */
    abstract public function checkConnected();

    /* Get list of files inside the active directory */
    abstract public function getListOfFiles();

    /* Create a folder inside the active directory. If folder already exists, do nothing. */
    abstract public function createFolder($folderName);

    /* Download file from Storage*/
    abstract public function downloadFile($filePath, $size, $backupId = null);

    /* Upload local file to Storage */
    abstract public function uploadFile($filePath);

    /* Delete file from active directory */
    abstract public function deleteFile($fileName);

    /* Delete folder and it's contents from active directory */
    abstract public function deleteFolder($folderName);

    /* Search if file or folder exists in given path */
    abstract public function fileExists($path);

    public function __construct()
    {
        @session_write_close();
        @session_start();
        $this->init();
        $this->checkConnected();
    }

    /* NOTE: Depending on the storage type, $directory could be the ID of the directory and not the name. */
    public function setActiveDirectory($directory)
    {
        $this->activeDirectory = $directory;
    }

    public function getActiveDirectory()
    {
        return $this->activeDirectory;
    }

    public function isConnected()
    {
        return $this->connected;
    }

    public function setDelegate(SGIStorageDelegate $delegate)
    {
        $this->delegate = $delegate;
    }

    public function loadState()
    {
        $this->state = $this->delegate->getState();
    }

    public function reload()
    {
        $this->delegate->reload();
    }

    public function shouldReload()
    {
        return $this->delegate->shouldReload();
    }

    public function standardizeFileCreationDate($date)
    {
        $time = strtotime($date);

        return backupGuardConvertDateTimezone(date('Y-m-d H:i:s', $time), true);
    }
}
