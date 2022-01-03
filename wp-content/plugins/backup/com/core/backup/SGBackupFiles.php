<?php

require_once SG_BACKUP_PATH . 'SGIBackupDelegate.php';
require_once SG_BACKUP_PATH . 'SGBackup.php';
require_once SG_LIB_PATH . 'SGArchive.php';
require_once SG_LIB_PATH . 'SGReloadHandler.php';
require_once SG_LIB_PATH . 'SGFileState.php';

require_once SG_LIB_PATH . 'SGFileEntry.php';
require_once SG_LIB_PATH . 'SGCdrEntry.php';

class SGBackupFiles implements SGArchiveDelegate
{
    const BUFFER_SIZE = 1000; // max files count to keep in buffer before writing to file tree
    private $_rootDirectory = '';
    private $_excludeFilePaths = array();
    private $_filePath = '';
    private $_sgbp = null;
    private $_delegate = null;
    private $_filesActionStartTs = 0;
    private $_nextProgressUpdate = 0;
    private $_progressUpdateInterval = 0;
    private $_warningsFound = false;
    private $_dontExclude = array();
    private $_cdrSize = 0;
    private $_pendingStorageUploads = array();
    private $_fileName = '';
    private $_progressCursor = 0;
    private $_numberOfEntries = 0;
    private $_cursor = 0;
    private $_reloadStartTs;

    public function __construct()
    {
        $this->_rootDirectory          = rtrim(SGConfig::get('SG_APP_ROOT_DIRECTORY'), '/') . '/';
        $this->_progressUpdateInterval = SGConfig::get('SG_ACTION_PROGRESS_UPDATE_INTERVAL');
    }

    public function setDelegate(SGIBackupDelegate $delegate)
    {
        $this->_delegate = $delegate;
    }

    public function setFilePath($filePath)
    {
        $this->_filePath = $filePath;
    }

    public function setFileName($fileName)
    {
        $this->_fileName = $fileName;
    }

    public function setPendingStorageUploads($pendingStorageUploads)
    {
        $this->_pendingStorageUploads = $pendingStorageUploads;
    }

    public function addDontExclude($ex)
    {
        $this->_dontExclude[] = $ex;
    }

    public function didExtractArchiveMeta($meta)
    {
        $file = dirname($this->_filePath) . '/' . $this->_fileName . '_restore.log';

        if (file_exists($file)) {
            $archiveVersion = SGConfig::get('SG_CURRENT_ARCHIVE_VERSION');

            $content = '';
            $content .= '---' . PHP_EOL;
            $content .= 'Archive version: ' . $archiveVersion . PHP_EOL;
            $content .= 'Archive database prefix: ' . $meta['dbPrefix'] . PHP_EOL;
            $content .= 'Archive site URL: ' . $meta['siteUrl'] . PHP_EOL . PHP_EOL;

            file_put_contents($file, $content, FILE_APPEND);
        }
    }

    public function didFindWarnings()
    {
        return $this->_warningsFound;
    }

    private function addEntriesInFileTree($entries)
    {
        foreach ($entries as $entry) {
            file_put_contents(dirname($this->_filePath) . '/' . SG_TREE_FILE_NAME, serialize($entry) . "\n", FILE_APPEND);
        }
    }

    private function loadFileTree()
    {
        $allItems = file_get_contents(dirname($this->_filePath) . '/' . SG_TREE_FILE_NAME);

        return unserialize($allItems);
    }

    public function shouldReload()
    {
        $currentTime = time();

        if (($currentTime - $this->_reloadStartTs) >= SG_RELOAD_TIMEOUT) {
            return true;
        }

        return false;
    }

    public function getState()
    {
        return $this->_delegate->getState();
    }

    public function backup($filePath, $options, $state)
    {
        $this->_reloadStartTs = time();
        if ($state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {
            $this->_filesActionStartTs = time();
            $state->setFilesActionStartTs($this->_filesActionStartTs);
            SGBackupLog::writeAction('backup files', SG_BACKUP_LOG_POS_START);
        }

        if (strlen($options['SG_BACKUP_FILE_PATHS_EXCLUDE'])) {
            $excludePaths       = $options['SG_BACKUP_FILE_PATHS_EXCLUDE'];
            $userCustomExcludes = SGConfig::get('SG_PATHS_TO_EXCLUDE');
            if (!empty($userCustomExcludes)) {
                $excludePaths .= ',' . $userCustomExcludes;
            }

            $this->_excludeFilePaths = explode(',', $excludePaths);
        } else {
            $this->_excludeFilePaths = array();
        }

        $this->_filePath = $filePath;
        $backupItems     = $options['SG_BACKUP_FILE_PATHS'];
        $allItems        = explode(',', $backupItems);

        if (!is_writable($filePath)) {
            throw new SGExceptionForbidden('Could not create backup file: ' . $filePath);
        }

        if ($state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {
            $this->resetProgress();
            SGBackupLog::write("------- Start prepare file tree -------");
            $this->prepareFileTree($allItems);
            SGBackupLog::write("------- End prepare file tree -------");

            $this->saveStateData(SG_STATE_ACTION_LISTING_FILES, array(), 0, 0, false, 0);

            SGBackupLog::write('Number of files to backup: ' . $this->_numberOfEntries);
            SGBackupLog::write('Root path: ' . $this->_filePath . '/');

            if (backupGuardIsReloadEnabled()) {
                $this->reload();
            }
        } else {
            $this->_nextProgressUpdate = $state->getProgress();
            $this->_warningsFound      = $state->getWarningsFound();

            $this->_numberOfEntries    = $state->getNumberOfEntries();
            $this->_progressCursor     = $state->getProgressCursor();
            $this->_filesActionStartTs = $state->getFilesActionStartTs();
        }

        $this->_cdrSize = $state->getCdrSize();
        $this->_sgbp    = new SGArchive($filePath, 'a', $this->_cdrSize);
        $this->_sgbp->setDelegate($this);

        $this->_cursor = $state->getCursor();

        if (file_exists(dirname($this->_filePath) . '/' . SG_TREE_FILE_NAME)) {
            $fileTreeHandle = fopen(dirname($this->_filePath) . '/' . SG_TREE_FILE_NAME, 'r');
            if ($fileTreeHandle) {
                fseek($fileTreeHandle, $this->_cursor);
                $i = 0;
                while (($fileTreeLine = fgets($fileTreeHandle)) !== false) {
                    $file     = unserialize($fileTreeLine);
                    $filePath = str_replace(ABSPATH, '', $file['path']);
                    if (!$state->getInprogress()) {
                        SGBackupLog::writeAction('backup file: ' . $filePath, SG_BACKUP_LOG_POS_START);
                    }

                    $path = $file['path'];
                    $this->addFileToArchive($path);
                    SGBackupLog::writeAction('backup file: ' . $filePath, SG_BACKUP_LOG_POS_END);

                    $this->_cursor  = ftell($fileTreeHandle);
                    $this->_cdrSize = $this->_sgbp->getCdrFilesCount();
                    $this->saveStateData(SG_STATE_ACTION_COMPRESSING_FILES, array(), 0, 0, false, $state->getFileOffsetInArchive());

                    $i++;
                    if (SGBoot::isFeatureAvailable('BACKGROUND_MODE') && $this->_delegate->isBackgroundMode()) {
                        if ($i % SG_FILES_COUNT == 0) {
                            SGBackgroundMode::next();
                        }
                    }
                }
            }
        }

        $this->_sgbp->finalize();
        $this->clear();

        SGBackupLog::writeAction('backup files', SG_BACKUP_LOG_POS_END);
        SGBackupLog::write('Backup files total duration: ' . backupGuardFormattedDuration($this->_filesActionStartTs, time()));
    }

    private function clear()
    {
        @unlink(dirname($this->_filePath) . '/' . SG_TREE_FILE_NAME);
    }

    public function reload()
    {
        $this->_delegate->reload();
    }

    public function getToken()
    {
        return $this->_delegate->getToken();
    }

    public function getProgress()
    {
        return $this->_nextProgressUpdate;
    }

    public function saveStateData($action, $ranges = array(), $offset = 0, $headerSize = 0, $inprogress = false, $fileOfssetInArchive = 0)
    {
        $sgFileState = $this->_delegate->getState();
        $token       = $this->getToken();

        $sgFileState->setInprogress($inprogress);
        $sgFileState->setHeaderSize($headerSize);
        $sgFileState->setRanges($ranges);
        $sgFileState->setOffset($offset);
        $sgFileState->setToken($token);
        $sgFileState->setAction($action);
        $sgFileState->setProgress($this->_nextProgressUpdate);
        $sgFileState->setWarningsFound($this->_warningsFound);
        $sgFileState->setCdrSize($this->_cdrSize);
        $sgFileState->setPendingStorageUploads($this->_pendingStorageUploads);
        $sgFileState->setNumberOfEntries($this->_numberOfEntries);
        $sgFileState->setCursor($this->_cursor);
        $sgFileState->setFileOffsetInArchive($fileOfssetInArchive);
        $sgFileState->setProgressCursor($this->_progressCursor);

        $sgFileState->save();
    }

    public function didStartRestoreFiles()
    {
        //start logging
        if (backupGuardIsMaintenanceMode() && SGExternalRestore::isEnabled()) {
            SGBackupLog::write("Entering maintenance mode");
        }
        SGBackupLog::writeAction('restore', SG_BACKUP_LOG_POS_START);
        SGBackupLog::writeAction('restore files', SG_BACKUP_LOG_POS_START);
        $this->_filesActionStartTs = time();
    }

    public function restore($filePath)
    {
        $this->_reloadStartTs = time();
        $state                = $this->getState();
        $this->_filePath      = $filePath;
        $this->resetProgress();
        $this->_warningsFound = false;

        if ($state) {
            $this->_nextProgressUpdate = $state->getProgress();
            $this->_warningsFound      = $state->getWarningsFound();
            $this->_progressCursor     = $state->getCursor();
            $this->_numberOfEntries    = $state->getCdrSize();
            $this->_filesActionStartTs = $state->getFilesActionStartTs();
        }

        $this->extractArchive($filePath);
        SGBackupLog::writeAction('restore files', SG_BACKUP_LOG_POS_END);
        SGBackupLog::write('Restore files total duration: ' . backupGuardFormattedDuration($this->_filesActionStartTs, time()));
    }

    private function extractArchive($filePath)
    {
        $restorePath = $this->_rootDirectory;

        $state = $this->getState();
        $sgbp  = new SGArchive($filePath, 'r');
        $sgbp->setDelegate($this);
        $sgbp->extractTo($restorePath, $state);
    }

    public function getCorrectCdrFilename($filename)
    {
        $backupsPath = $this->pathWithoutRootDirectory(realpath(SG_BACKUP_DIRECTORY));

        if (strpos($filename, $backupsPath) === 0) {
            $newPath  = dirname($this->pathWithoutRootDirectory(realpath($this->_filePath)));
            $filename = substr(basename(trim($this->_filePath)), 0, -4); //remove sgbp extension

            return $newPath . '/' . $filename . 'sql';
        }

        return $filename;
    }

    public function didStartExtractFile($filePath)
    {
        SGBackupLog::write('Start restore file: ' . $filePath);
    }

    public function didExtractFile($filePath)
    {
        //update progress
        $this->_progressCursor++;
        $this->updateProgress();

        SGBackupLog::write('End restore file: ' . $filePath);
    }

    public function didFindExtractError($error)
    {
        $this->warn($error);
    }

    public function didCountFilesInsideArchive($count)
    {
        $this->_numberOfEntries = $count;
        SGBackupLog::write('Number of files to restore: ' . $count);
        $state                     = $this->getState();
        $this->_filesActionStartTs = time();
        $state->setFilesActionStartTs($this->_filesActionStartTs);
    }

    private function prepareFileTree($allItems)
    {
        $entries = array();

        /**
         * ToDo check this logic
         */
        //file_put_contents(dirname($this->_filePath).'/'.SG_TREE_FILE_NAME, "");

        foreach ($allItems as $item) {
            $path = $this->_rootDirectory . $item;
            $this->addDirectoryEntriesInFileTree($path, $entries);
        }

        if (count($entries)) {
            $this->addEntriesInFileTree($entries);
        }
    }

    private function resetProgress()
    {
        $this->_progressCursor     = 0;
        $this->_nextProgressUpdate = $this->_progressUpdateInterval;
    }

    private function pathWithoutRootDirectory($path)
    {
        return substr($path, strlen($this->_rootDirectory));
    }

    private function shouldExcludeFile($path)
    {
        if (in_array($path, $this->_dontExclude)) {
            return false;
        }

        //get the name of the file/directory removing the root directory
        $file = $this->pathWithoutRootDirectory($path);

        //check if file/directory must be excluded
        foreach ($this->_excludeFilePaths as $exPath) {
            $exPath = trim($exPath);
            $exPath = trim($exPath, '/');
            if (strpos($file, $exPath) === 0) {
                return true;
            }
        }

        return false;
    }

    private function addDirectoryEntriesInFileTree($path, &$entries = array())
    {
        if ($this->shouldExcludeFile($path)) {
            return;
        }
        SGPing::update();
        if (is_dir($path)) {
            if ($handle = @opendir($path)) {
                while (($file = readdir($handle)) !== false) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    if (SG_ENV_ADAPTER == SG_ENV_WORDPRESS) {
                        if (($path == $this->_rootDirectory || $path == $this->_rootDirectory . 'wp-content') && strpos($file, 'backup') !== false) {
                            continue;
                        }
                    }

                    $this->addDirectoryEntriesInFileTree($path . '/' . $file, $entries);
                }

                closedir($handle);
            } else {
                $this->warn('Could not read directory (skipping): ' . $path);
            }
        } else {
            if (is_readable($path)) {
                $dateModified = filemtime($path);

                $fileEntry = new SGFileEntry();
                $fileEntry->setName(basename($path));
                $fileEntry->setPath($path);
                $fileEntry->setDateModified($dateModified);

                $this->_numberOfEntries++;
                array_push($entries, $fileEntry->toArray());

                if (count($entries) > self::BUFFER_SIZE) {
                    $this->addEntriesInFileTree($entries);
                    $entries = array();
                }
            } else {
                $this->warn('Path is not readable (skipping): ' . $path);
            }
        }
    }

    public function cancel()
    {
        @unlink($this->_filePath);
    }

    private function addFileToArchive($path)
    {
        if ($this->shouldExcludeFile($path)) {
            return true;
        }

        //check if it is a directory
        if (is_dir($path)) {
            $this->backupDirectory($path);

            return;
        }

        //it is a file, try to add it to archive
        if (is_readable($path)) {
            $file = substr($path, strlen($this->_rootDirectory));
            $file = str_replace('\\', '/', $file);
            $this->_sgbp->addFileFromPath($file, $path);
        } else {
            $this->warn('Could not read file (skipping): ' . $path);
        }

        //update progress and check cancellation
        $this->_progressCursor++;
        if ($this->updateProgress()) {
            if ($this->_delegate && $this->_delegate->isCancelled()) {
                return;
            }
        }

        /*if (SGBoot::isFeatureAvailable('BACKGROUND_MODE') && $this->_delegate->isBackgroundMode())
        {
        SGBackgroundMode::next();
        }*/
    }

    private function backupDirectory($path)
    {
        if ($handle = @opendir($path)) {
            $filesFound = false;
            while (($file = readdir($handle)) !== false) {
                if ($file === '.') {
                    continue;
                }
                if ($file === '..') {
                    continue;
                }

                $filesFound = true;
                $this->addFileToArchive($path . '/' . $file);
            }

            if (!$filesFound) {
                $file = substr($path, strlen($this->_rootDirectory));
                $file = str_replace('\\', '/', $file);
                $this->_sgbp->addFile($file . '/', ''); //create empty directory
            }

            closedir($handle);
        } else {
            $this->warn('Could not read directory (skipping): ' . $path);
        }
    }

    public function warn($message)
    {
        $this->_warningsFound = true;
        SGBackupLog::writeWarning($message);
    }

    private function updateProgress()
    {
        $progress = round($this->_progressCursor * 100.0 / $this->_numberOfEntries);

        if ($progress >= $this->_nextProgressUpdate) {
            $this->_nextProgressUpdate += $this->_progressUpdateInterval;

            if ($this->_delegate) {
                $this->_delegate->didUpdateProgress($progress);
            }

            return true;
        }

        return false;
    }
}
