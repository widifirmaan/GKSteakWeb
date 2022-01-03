<?php

interface SGArchiveDelegate
{
    public function getCorrectCdrFilename($filename);

    public function didExtractFile($filePath);

    public function didCountFilesInsideArchive($count);

    public function didFindExtractError($error);

    public function warn($message);

    public function didExtractArchiveMeta($meta);

    public function didStartRestoreFiles();
}

class SGArchive // phpcs:ignore
{
    const VERSION = 5;
    const CHUNK_SIZE = 1048576; //1mb
    private $_filePath = '';
    private $_mode = '';
    private $_fileHandle = null;
    private $_cdrFileHandle = null;
    private $_cdrFilesCount = 0;
    private $_cdr = array();
    private $_fileOffset = 0;
    private $_delegate;
    private $_ranges = array();
    private $_state = null;
    private $_rangeCursor = 0;
    private $_cdrOffset = 0;

    public function __construct($filePath, $mode, $cdrSize = 0)
    {
        $this->_filePath = $filePath;
        $this->_mode = $mode;
        $this->_fileHandle = @fopen($filePath, $mode . 'b');
        $this->clear();

        if ($cdrSize) {
            $this->_cdrFilesCount = $cdrSize;
        }

        if ($mode == 'a') {
            $cdrPath = $filePath . '.cdr';

            $this->_cdrFileHandle = @fopen($cdrPath, $mode . 'b');
        }
    }

    public function setDelegate(SGArchiveDelegate $delegate)
    {
        $this->_delegate = $delegate;
    }

    public function getCdrFilesCount()
    {
        return $this->_cdrFilesCount;
    }

    public function addFileFromPath($filename, $path)
    {
        $headerSize = 0;
        $len = 0;
        $zlen = 0;
        $start = 0;

        $fp = fopen($path, 'rb');
        $fileSize = backupGuardRealFilesize($path);

        $state = $this->_delegate->getState();
        $offset = $state->getOffset();

        if (!$state->getInprogress()) {
            $headerSize = $this->addFileHeader();
        } else {
            $headerSize = $state->getHeaderSize();
            $this->_fileOffset = $state->getFileOffsetInArchive();
        }

        $this->_ranges = $state->getRanges();
        if (count($this->_ranges)) {
            $range = end($this->_ranges); //get last range of file

            $start += $range['start'] + $range['size'];
            $zlen = $start; // get file compressed size before reload
        }

        fseek($fp, $offset); // move to point before reload
        //read file in small chunks
        while ($offset < $fileSize) {
            $data = fread($fp, self::CHUNK_SIZE);
            if ($data === '') {
                //When fread fails to read and compress on fly
                if ($zlen == 0 && $fileSize != 0 && strlen($data) == 0) {
                    $this->_delegate->warn('Failed to read file: ' . basename($filename));
                }
                break;
            }

            $data = gzdeflate($data);
            $zlen += strlen($data);
            $sgArchiveSize = backupGuardRealFilesize($this->_filePath);
            $sgArchiveSize += strlen($data);

            if ($sgArchiveSize > SG_ARCHIVE_MAX_SIZE_32) {
                SGBoot::checkRequirement('intSize');
            }

            $this->write($data);

            array_push(
                $this->_ranges,
                array(
                    'start' => $start,
                    'size' => strlen($data)
                )
            );
            $offset = ftell($fp);

            $start += strlen($data);

            SGPing::update();
            $shouldReload = $this->_delegate->shouldReload();
            if ($shouldReload) {
                $this->_delegate->saveStateData(SG_STATE_ACTION_COMPRESSING_FILES, $this->_ranges, $offset, $headerSize, true, $this->_fileOffset);

                if (backupGuardIsReloadEnabled()) {
                    @fclose($fp);
                    @fclose($this->_fileHandle);
                    @fclose($this->_cdrFileHandle);

                    $this->_delegate->reload();
                }
            }
        }

        if ($state->getInprogress()) {
            $headerSize = $state->getHeaderSize();
        }

        SGPing::update();

        fclose($fp);

        $this->addFileToCdr($filename, $zlen, $len, $headerSize);
    }

    public function addFile($filename, $data)
    {
        $headerSize = $this->addFileHeader();

        if ($data) {
            $data = gzdeflate($data);
            $this->write($data);
        }

        $zlen = strlen($data);
        $len = 0;

        $this->addFileToCdr($filename, $zlen, $len, $headerSize);
    }

    private function addFileHeader()
    {
        //save extra
        $extra = '';

        $extraLengthInBytes = 4;
        $this->write($this->packToLittleEndian(strlen($extra), $extraLengthInBytes) . $extra);

        return $extraLengthInBytes + strlen($extra);
    }

    private function addFileToCdr($filename, $zlen, $len, $headerSize)
    {
        //store cdr data for later use
        $this->addToCdr($filename, $zlen, $len);

        $this->_fileOffset += $headerSize + $zlen;
    }

    public function finalize()
    {
        $this->addFooter();

        fclose($this->_fileHandle);

        $this->clear();
    }

    private function addFooter()
    {
        $footer = '';

        //save version
        $footer .= $this->packToLittleEndian(self::VERSION, 1);

        $tables = SGConfig::get('SG_BACKUPED_TABLES');

        if ($tables) {
            $tables = json_encode($tables);
        } else {
            $tables = "";
        }

        $multisitePath = "";
        $multisiteDomain = "";

        if (SG_ENV_ADAPTER == SG_ENV_WORDPRESS) {
            // in case of multisite save old path and domain for later usage
            if (is_multisite()) {
                $multisitePath = PATH_CURRENT_SITE;
                $multisiteDomain = DOMAIN_CURRENT_SITE;
            }
        }

        //save db prefix, site and home url for later use
        $extra = json_encode(
            array(
                'siteUrl' => get_site_url(),
                'home' => get_home_url(),
                'dbPrefix' => SG_ENV_DB_PREFIX,
                'tables' => $tables,
                'method' => SGConfig::get('SG_BACKUP_TYPE'),
                'multisitePath' => $multisitePath,
                'multisiteDomain' => $multisiteDomain,
                'selectivRestoreable' => true,
                'phpVersion' => phpversion()
            )
        );

        //extra size
        $footer .= $this->packToLittleEndian(strlen($extra), 4) . $extra;

        //save cdr size
        $footer .= $this->packToLittleEndian($this->_cdrFilesCount, 4);

        $this->write($footer);

        //save cdr
        $cdrLen = $this->writeCdr();

        //save offset to the start of footer
        $len = $cdrLen + strlen($extra) + 13;
        $this->write($this->packToLittleEndian($len, 4));
    }

    private function writeCdr()
    {
        @fclose($this->_cdrFileHandle);

        $cdrLen = 0;
        $fp = @fopen($this->_filePath . '.cdr', 'rb');

        while (!feof($fp)) {
            $data = fread($fp, self::CHUNK_SIZE);
            $cdrLen += strlen($data);
            $this->write($data);
        }

        @fclose($fp);
        @unlink($this->_filePath . '.cdr');

        return $cdrLen;
    }

    private function clear()
    {
        $this->_cdr = array();
        $this->_fileOffset = 0;
        $this->_cdrFilesCount = 0;
    }

    private function addToCdr($filename, $compressedLength, $uncompressedLength)
    {
        $rec = $this->packToLittleEndian(0, 4); //crc (not used in this version)
        $rec .= $this->packToLittleEndian(strlen($filename), 2);
        $rec .= $filename;
        // file offset, compressed length, uncompressed length all are writen in 8 bytes to cover big integer size
        $rec .= $this->packToLittleEndian($this->_fileOffset, 8);
        $rec .= $this->packToLittleEndian($compressedLength, 8);
        $rec .= $this->packToLittleEndian($uncompressedLength, 8); //uncompressed size (not used in this version)
        $rec .= $this->packToLittleEndian(count($this->_ranges), 4);

        foreach ($this->_ranges as $range) {
            // start and size all are writen in 8 bytes to cover big integer size
            $rec .= $this->packToLittleEndian($range['start'], 8);
            $rec .= $this->packToLittleEndian($range['size'], 8);
        }

        fwrite($this->_cdrFileHandle, $rec);
        fflush($this->_cdrFileHandle);

        $this->_cdrFilesCount++;
    }

    private function isEnoughFreeSpaceOnDisk($dataSize)
    {
        $freeSpace = false;

        if (function_exists('disk_free_space')) {
            $freeSpace = @disk_free_space(SG_APP_ROOT_DIRECTORY);
        }

        if ($freeSpace === false || $freeSpace === null) {
            return true;
        }

        if ($freeSpace < $dataSize) {
            return false;
        }

        return true;
    }

    private function write($data)
    {
        $isEnoughFreeSpaceOnDisk = $this->isEnoughFreeSpaceOnDisk(strlen($data));
        if (!$isEnoughFreeSpaceOnDisk) {
            throw new SGExceptionIO('Failed to write in the archive due to not sufficient disk free space.');
        }

        $result = fwrite($this->_fileHandle, $data);
        if ($result === false) {
            throw new SGExceptionIO('Failed to write in archive');
        }
        fflush($this->_fileHandle);
    }

    private function read($length)
    {
        $result = fread($this->_fileHandle, $length);
        if ($result === false) {
            throw new SGExceptionIO('Failed to read from archive');
        }
        return $result;
    }

    private function packToLittleEndian($value, $size = 4)
    {
        if (is_int($value)) {
            $size *= 2; //2 characters for each byte
            $value = str_pad(dechex($value), $size, '0', STR_PAD_LEFT);
            return strrev(pack('H' . $size, $value));
        }

        $hex = str_pad($value->toHex(), 16, '0', STR_PAD_LEFT);

        $high = substr($hex, 0, 8);
        $low = substr($hex, 8, 8);

        $high = strrev(pack('H8', $high));
        $low = strrev(pack('H8', $low));

        return $low . $high;
    }

    public function getArchiveHeaders()
    {
        return $this->extractHeaders();
    }

    public function getFilesList()
    {
        $list = array();
        $cdrSize = hexdec($this->unpackLittleEndian($this->read(4), 4));
        $this->_cdrOffset = ftell($this->_fileHandle);

        for ($i = 0; $i < $cdrSize; $i++) {
            $el = $this->getNextCdrElement($this->_cdrOffset);
            array_push($list, $el[0]);
        }
        return $list;
    }

    public function getTreefromList($list, $limit = "")
    {
        $tree = array();
        if (end($list) == "./sql") {
            array_pop($list);
        }
        for ($i = 0; $i < count($list); $i++) {
            if (!backupGuardStringStartsWith($list[$i], $limit)) {
                continue;
            }
            $path = substr($list[$i], strlen($limit));
            $path = explode(DIRECTORY_SEPARATOR, $path);
            $exists = false;
            foreach ($tree as $el) {
                if ($path[0] == $el->name) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $node = new stdClass();
                $node->name = $path[0];
                if (count($path) > 1) {
                    $node->type = "folder";
                } else {
                    $node->type = "file";
                }
                array_push($tree, $node);
            }
        }
        return $tree;
    }

    public function extractTo($destinationPath, $state = null)
    {
        $this->_state = $state;
        $action = $state->getAction();

        if ($action == SG_STATE_ACTION_PREPARING_STATE_FILE) {
            $this->extract($destinationPath);
        } else {
            $this->continueExtract($destinationPath);
        }
    }

    private function extractHeaders()
    {
        //read offset
        fseek($this->_fileHandle, -4, SEEK_END);
        $offset = hexdec($this->unpackLittleEndian($this->read(4), 4));

        //read version
        fseek($this->_fileHandle, -$offset, SEEK_END);
        $version = hexdec($this->unpackLittleEndian($this->read(1), 1));
        SGConfig::set('SG_CURRENT_ARCHIVE_VERSION', $version);

        //read extra size (not used in this version)
        $extraSize = hexdec($this->unpackLittleEndian($this->read(4), 4));

        //read extra
        $extra = array();
        if ($extraSize > 0) {
            $extra = $this->read($extraSize);
            $extra = json_decode($extra, true);

            SGConfig::set('SG_OLD_SITE_URL', $extra['siteUrl']);
            SGConfig::set('SG_OLD_DB_PREFIX', $extra['dbPrefix']);

            if (isset($extra['phpVersion'])) {
                SGConfig::set('SG_OLD_PHP_VERSION', $extra['phpVersion']);
            }

            SGConfig::set('SG_BACKUPED_TABLES', $extra['tables']);
            SGConfig::set('SG_BACKUP_TYPE', $extra['method']);

            SGConfig::set('SG_MULTISITE_OLD_PATH', $extra['multisitePath']);
            SGConfig::set('SG_MULTISITE_OLD_DOMAIN', $extra['multisiteDomain']);
        }

        $extra['version'] = $version;
        return $extra;
    }

    private function extract($destinationPath)
    {
        $extra = $this->extractHeaders();
        $version = $extra['version'];

        $this->_delegate->didExtractArchiveMeta($extra);

        $isMultisite = backupGuardIsMultisite();
        $archiveIsMultisite = $extra['multisitePath'] != '' || $extra['multisiteDomain'] != '';

        if (SG_ENV_ADAPTER == SG_ENV_WORDPRESS) {
            if ($archiveIsMultisite && !$isMultisite) {
                throw new SGExceptionMigrationError("In order to restore this archive you should set up Multisite WordPress!");
            } elseif (!$archiveIsMultisite && $isMultisite) {
                throw new SGExceptionMigrationError("In order to restore this archive you should set up a Standard instead of Multisite WordPress!");
            }
        }

        if ($version >= SG_MIN_SUPPORTED_ARCHIVE_VERSION && $version <= SG_MAX_SUPPORTED_ARCHIVE_VERSION) {
            if (!SGBoot::isFeatureAvailable('BACKUP_WITH_MIGRATION')) {
                if ($extra['method'] != SG_BACKUP_METHOD_MIGRATE) {
                    if ($extra['siteUrl'] == SG_SITE_URL) {
                        if ($extra['dbPrefix'] != SG_ENV_DB_PREFIX) {
                            throw new SGException("Seems you have changed database prefix. You should keep it constant to be able to restore this backup. Setup your WordPress installation with " . $extra['dbPrefix'] . " datbase prefix.");
                        }
                    } else {
                        throw new SGExceptionMigrationError("You should install <b>BackupGuard Pro</b> to be able to migrate the website. More detailed information regarding features included in <b>Free</b> and <b>Pro</b> versions you can find here: <a href='" . SG_BACKUP_SITE_URL . "'>" . SG_BACKUP_SITE_URL . "</a>");
                    }
                } else {
                    throw new SGExceptionMigrationError("You should install <b>BackupGuard Pro</b> to be able to restore a package designed for migration.More detailed information regarding features included in <b>Free</b> and <b>Pro</b> versions you can find here: <a href='" . SG_BACKUP_SITE_URL . "'>" . SG_BACKUP_SITE_URL . "</a>");
                }
            }
        } else {
            throw new SGExceptionBadRequest('Invalid SGArchive file');
        }

        //read cdr size
        $this->_cdrFilesCount = hexdec($this->unpackLittleEndian($this->read(4), 4));

        $this->_delegate->didStartRestoreFiles();
        $this->_delegate->didCountFilesInsideArchive($this->_cdrFilesCount);

        // $this->extractCdr($cdrSize, $destinationPath);
        $this->_cdrOffset = ftell($this->_fileHandle);
        $this->extractFiles($destinationPath);
    }

    private function continueExtract($destinationPath)
    {
        $this->_fileOffset = $this->_state->getOffset();
        fseek($this->_fileHandle, $this->_fileOffset);
        $this->extractFiles($destinationPath);
    }

    private function getNextCdrElement($offset)
    {
        fseek($this->_fileHandle, $this->_cdrOffset);
        //read crc (not used in this version)
        $this->read(4);

        //read filename
        $filenameLen = hexdec($this->unpackLittleEndian($this->read(2), 2));
        $filename = $this->read($filenameLen);
        $filename = $this->_delegate->getCorrectCdrFilename($filename);

        //read file offset
        $fileOffsetInArchive = $this->unpackLittleEndian($this->read(8), 8);
        $fileOffsetInArchive = hexdec($fileOffsetInArchive);

        //read compressed length
        $zlen = $this->unpackLittleEndian($this->read(8), 8);
        $zlen = hexdec($zlen);

        //read uncompressed length (not used in this version)
        $this->read(8);

        $rangeLen = hexdec($this->unpackLittleEndian($this->read(4), 4));

        $ranges = array();
        for ($i = 0; $i < $rangeLen; $i++) {
            $start = $this->unpackLittleEndian($this->read(8), 8);
            $start = hexdec($start);

            $size = $this->unpackLittleEndian($this->read(8), 8);
            $size = hexdec($size);

            $ranges[] = array(
                'start' => $start,
                'size' => $size
            );
        }

        $this->_cdrOffset = ftell($this->_fileHandle);
        return array($filename, $zlen, $ranges, $fileOffsetInArchive);
    }

    private function extractFiles($destinationPath)
    {
        $action = $this->_state->getAction();
        if ($action == SG_STATE_ACTION_PREPARING_STATE_FILE) {
            $inprogress = false;
            fseek($this->_fileHandle, 0, SEEK_SET);
        } else {
            $inprogress = $this->_state->getInprogress();
            $this->_cdrFilesCount = $this->_state->getCdrSize();
            $this->_cdrOffset = $this->_state->getCdrCursor();
        }

        $sqlFileEnding = $this->_state->getBackupFileName() . '/' . $this->_state->getBackupFileName() . '.sql';
        $restoreMode = $this->_state->getRestoreMode();
        $restoreFiles = $this->_state->getRestoreFiles();

        while ($this->_cdrFilesCount) {
            $warningFoundDuringExtract = false;

            if ($inprogress) {
                $row = $this->_state->getCdr();
            } else {
                $row = $this->getNextCdrElement($this->_cdrOffset);

                fseek($this->_fileHandle, $this->_fileOffset);

                //read extra (not used in this version)
                $this->read(4);
            }

            $path = $destinationPath . $row[0];
            $path = str_replace('\\', '/', $path);
            $restoreCurrentFile = false;

            if ($restoreMode == SG_RESTORE_MODE_FILES && $restoreFiles != null && count($restoreFiles) > 0) {
                for ($j = 0; $j < count($restoreFiles); $j++) {
                    if ($restoreFiles[$j] == "/" || backupGuardStringStartsWith($row[0], $restoreFiles[$j])) {
                        $restoreCurrentFile = true;
                        break;
                    }
                }
            }

            // check if file should be restored according restore mode selected by user
            if ($restoreMode == SG_RESTORE_MODE_FULL || ($restoreMode == SG_RESTORE_MODE_DB && backupGuardStringEndsWith($path, $sqlFileEnding)) || ($restoreMode == SG_RESTORE_MODE_FILES && !backupGuardStringEndsWith($path, $sqlFileEnding) && $restoreCurrentFile)) {
                if ($path[strlen($path) - 1] != '/') {//it's not an empty directory
                    $path = dirname($path);
                }

                if (!$inprogress) {
                    if (!$this->createPath($path)) {
                        $ranges = $row[2];

                        //get last range of file
                        $range = end($ranges);
                        $offset = $range['start'] + $range['size'];

                        // skip file and continue
                        fseek($this->_fileHandle, $offset, SEEK_CUR);
                        $this->_delegate->didFindExtractError('Could not create directory: ' . dirname($path));
                        continue;
                    }
                }

                $path = $destinationPath . $row[0];
                $tmpPath = $path . ".sgbpTmpFile";

                if (!$inprogress) {
                    $this->_delegate->didStartExtractFile($path);

                    if (!is_writable(dirname($tmpPath))) {
                        $this->_delegate->didFindExtractError('Destination path is not writable: ' . dirname($path));
                    }
                }

                if (!$inprogress) {
                    $tmpFp = @fopen($tmpPath, 'wb');
                } else {
                    $tmpFp = @fopen($tmpPath, 'ab');
                }

                $zlen = $row[1]; // phpcs:ignore
                SGPing::update();
                $ranges = $row[2];

                if ($inprogress) {
                    $this->_rangeCursor = $this->_state->getRangeCursor();
                } else {
                    $this->_rangeCursor = 0;
                }

                for ($i = $this->_rangeCursor; $i < count($ranges); $i++) {
                    $start = $ranges[$i]['start']; // phpcs:ignore
                    $size = $ranges[$i]['size'];

                    $data = $this->read($size);
                    $data = gzinflate($data);

                    //If gzinflate() failed to uncompress, skip the current file and continue extraction
                    if (!$data) {
                        $warningFoundDuringExtract = true;
                        $this->_delegate->didFindExtractError('Failed to extract path: ' . $path);

                        //Assume we've extracted the current file
                        for ($idx = $i + 1; $idx < count($ranges); $idx++) {
                            $start = $ranges[$idx]['start']; // phpcs:ignore
                            $size = $ranges[$idx]['size'];

                            fseek($this->_fileHandle, $size, SEEK_CUR);
                        }

                        $inprogress = false;
                        @fclose($tmpFp);

                        SGPing::update();

                        break;
                    } else {
                        $inprogress = true;
                        if (($i + 1) == count($ranges)) {
                            $inprogress = false;
                        }
                        if (is_resource($tmpFp)) {
                            $isEnoughFreeSpaceOnDisk = $this->isEnoughFreeSpaceOnDisk(strlen($data));
                            if (!$isEnoughFreeSpaceOnDisk) {
                                throw new SGExceptionIO('Failed to write in the archive due to not sufficient disk free space.');
                            }

                            fwrite($tmpFp, $data);
                            fflush($tmpFp);

                            $shouldReload = $this->_delegate->shouldReload();

                            //restore with reloads will only work in external mode
                            if ($shouldReload && SGExternalRestore::isEnabled()) {
                                if (!$inprogress) {
                                    $this->_cdrFilesCount--;

                                    @rename($tmpPath, $path);
                                    $this->_delegate->didExtractFile($path);
                                }

                                $token = $this->_delegate->getToken();
                                $progress = $this->_delegate->getProgress();

                                $this->_fileOffset = ftell($this->_fileHandle);

                                $this->_state->setRestoreMode($restoreMode);
                                $this->_state->setOffset($this->_fileOffset);
                                $this->_state->setInprogress($inprogress);
                                $this->_state->setToken($token);
                                $this->_state->setProgress($progress);
                                $this->_state->setAction(SG_STATE_ACTION_RESTORING_FILES);
                                $this->_state->setRangeCursor($i + 1);

                                $this->_state->setCdr($row);
                                $this->_state->setCdrSize($this->_cdrFilesCount);
                                $this->_state->setCdrCursor($this->_cdrOffset);
                                $this->_state->save();

                                SGPing::update();

                                @fclose($tmpFp);
                                @fclose($this->_fileHandle);

                                $this->_delegate->reload();
                            }
                        }
                    }
                    SGPing::update();
                }

                if (is_resource($tmpFp)) {
                    @fclose($tmpFp);
                }

                if (!$warningFoundDuringExtract) {
                    @rename($tmpPath, $path);
                } else {
                    @unlink($tmpPath);
                }

                $this->_delegate->didExtractFile($path);
                $this->_fileOffset = ftell($this->_fileHandle);
            } else {
                //if file should not be restored skip it and go to the next file
                $ranges = $row[2];

                for ($idx = 0; $idx < count($ranges); $idx++) {
                    $size = $ranges[$idx]['size'];

                    fseek($this->_fileHandle, $size, SEEK_CUR);
                }
                $this->_fileOffset = ftell($this->_fileHandle);
            }

            $this->_cdrFilesCount--;
        }
    }

    private function unpackLittleEndian($data, $size)
    {
        $size *= 2; //2 characters for each byte

        $data = unpack('H' . $size, strrev($data));
        return $data[1];
    }

    private function createPath($path)
    {
        if (is_dir($path)) {
            return true;
        }
        $prevPath = substr($path, 0, strrpos($path, '/', -2) + 1);
        $return = $this->createPath($prevPath);
        if ($return && is_writable($prevPath)) {
            if (!@mkdir($path)) {
                return false;
            }

            @chmod($path, 0777);
            return true;
        }

        return false;
    }

    public function getVersion()
    {
        //read offset
        fseek($this->_fileHandle, -4, SEEK_END);
        $offset = hexdec($this->unpackLittleEndian($this->read(4), 4));

        //read version
        fseek($this->_fileHandle, -$offset, SEEK_END);
        $version = hexdec($this->unpackLittleEndian($this->read(1), 1));

        return $version;
    }
}
