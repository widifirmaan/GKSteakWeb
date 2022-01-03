<?php

require_once SG_BACKUP_PATH . 'SGIBackupDelegate.php';
require_once SG_LIB_PATH . 'SGDBState.php';
require_once SG_LIB_PATH . 'SGMysqldump.php';
require_once SG_LIB_PATH . 'SGCharsetHandler.php';
backupGuardIncludeFile(SG_LIB_PATH . 'SGMigrate.php');

class SGBackupDatabase implements SGIMysqldumpDelegate
{
    private $_sgdb = null;
    private $_backupFilePath = '';
    private $_delegate = null;
    private $_cancelled = false;
    private $_nextProgressUpdate = 0;
    private $_actionStartTs = 0;
    private $_totalRowCount = 0;
    private $_currentRowCount = 0;
    private $_warningsFound = false;
    private $_pendingStorageUploads = array();
    private $_state = null;
    private $_reloadStartTs = null;
    private $_progressUpdateInterval;
    private $_migrationAvailable = null;
    private $_oldDbPrefix = null;
    private $_backedUpTables = null;
    private $_newTableNames = null;
    private $_migrateObj = null;
    private $_charsetHanlder = null;

    public function __construct()
    {
        $this->_sgdb                   = SGDatabase::getInstance();
        $this->_progressUpdateInterval = SGConfig::get('SG_ACTION_PROGRESS_UPDATE_INTERVAL');
    }

    public function setDelegate(SGIBackupDelegate $delegate)
    {
        $this->_delegate = $delegate;
    }

    public function setFilePath($filePath)
    {
        $this->_backupFilePath = $filePath;
    }

    public function isMigrationAvailable()
    {
        if ($this->_migrationAvailable === null) {
            $this->_migrationAvailable = SGBoot::isFeatureAvailable('BACKUP_WITH_MIGRATION');
        }

        return $this->_migrationAvailable;
    }

    public function getOldDbPrefix()
    {
        if ($this->_oldDbPrefix === null) {
            $this->_oldDbPrefix = SGConfig::get('SG_OLD_DB_PREFIX');
        }

        return $this->_oldDbPrefix;
    }

    public function getBackedUpTables()
    {
        if ($this->_backedUpTables === null) {
            $tableNames = SGConfig::get('SG_BACKUPED_TABLES');
            if ($tableNames) {
                $tableNames = json_decode($tableNames, true);
            } else {
                $tableNames = array();
            }
            $this->_backedUpTables = $tableNames;
        }

        return $this->_backedUpTables;
    }

    public function getNewTableNames()
    {
        if ($this->_newTableNames === null) {
            $oldDbPrefix = $this->getOldDbPrefix();
            $tableNames  = $this->getBackedUpTables();

            $newTableNames = array();
            foreach ($tableNames as $tableName) {
                $newTableNames[] = str_replace($oldDbPrefix, SG_ENV_DB_PREFIX, $tableName);
            }
            $this->_newTableNames = $newTableNames;
        }

        return $this->_newTableNames;
    }

    public function getMigrateObj()
    {
        if ($this->_migrateObj === null) {
            $this->_migrateObj = new SGMigrate();
        }

        return $this->_migrateObj;
    }

    public function getCharsetHandler()
    {
        if ($this->_charsetHanlder === null) {
            $this->_charsetHanlder = new SGCharsetHandler();
        }

        return $this->_charsetHanlder;
    }

    public function didFindWarnings()
    {
        return $this->_warningsFound;
    }

    public function setPendingStorageUploads($pendingStorageUploads)
    {
        $this->_pendingStorageUploads = $pendingStorageUploads;
    }

    public function saveStateData($offset, $cursor, $inprogress, $lineSize, $backedUpTables)
    {
        $sgDBState = $this->getState();
        $token     = $this->_delegate->getToken();

        $sgDBState->setLineSize($lineSize);
        $sgDBState->setNumberOfEntries($this->_totalRowCount);
        $sgDBState->setAction(SG_STATE_ACTION_EXPORTING_SQL);
        $sgDBState->setInprogress($inprogress);
        $sgDBState->setCursor($cursor);
        $sgDBState->setProgressCursor($this->_currentRowCount);
        $sgDBState->setOffset($offset);
        $sgDBState->setToken($token);
        $sgDBState->setProgress($this->_nextProgressUpdate);
        $sgDBState->setWarningsFound($this->_warningsFound);
        $sgDBState->setPendingStorageUploads($this->_pendingStorageUploads);
        $sgDBState->setBackedUpTables($backedUpTables);
        $sgDBState->save();
    }

    public function getState()
    {
        return $this->_delegate->getState();
    }

    public function shouldReload()
    {
        $currentTime = time();

        if (($currentTime - $this->_reloadStartTs) >= SG_RELOAD_TIMEOUT) {
            return true;
        }

        return false;
    }

    public function reload()
    {
        $this->_delegate->reload();
    }

    public function backup($filePath)
    {
        $this->_reloadStartTs = time();
        $this->_state         = $this->_delegate->getState();

        if ($this->_state && $this->_state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {
            $this->_actionStartTs = time();
            SGBackupLog::writeAction('backup database', SG_BACKUP_LOG_POS_START);
            $this->resetBackupProgress();
        } else {
            $this->_actionStartTs   = $this->_state->getActionStartTs();
            $this->_totalRowCount   = $this->_state->getNumberOfEntries();
            $this->_currentRowCount = $this->_state->getProgressCursor();
        }

        $this->_backupFilePath = $filePath;

        $this->export();

        SGBackupLog::writeAction('backup database', SG_BACKUP_LOG_POS_END);
        SGBackupLog::write('Backup database total duration: ' . backupGuardFormattedDuration($this->_actionStartTs, time()));
    }

    public function restore($filePath)
    {
        $this->_reloadStartTs  = time();
        $this->_backupFilePath = $filePath;

        $sgDBState = $this->getState();
        if ($sgDBState && $sgDBState->getType() == SG_STATE_TYPE_DB) {
            if ($sgDBState->getAction() != SG_STATE_ACTION_RESTORING_DATABASE) {
                SGBackupLog::writeAction('restore database', SG_BACKUP_LOG_POS_START);
                $this->_actionStartTs = time();
                //prepare for restore (reset variables)
                $this->resetRestoreProgress();
            }
            //import all db tables
            $this->import();
        }

        //run migration logic
        if ($this->isMigrationAvailable()) {
            if ($sgDBState->getAction() != SG_STATE_ACTION_MIGRATING_DATABASE) {
                $this->_delegate->prepareMigrateStateFile();
            }

            $this->processMigration();
        }

        //external restore file doesn't have the wordpress functions
        //so we cannot do anything here
        //it will finalize the restore for itself
        if (!SGExternalRestore::isEnabled()) {
            $this->finalizeRestore();
        }

        SGBackupLog::writeAction('restore database', SG_BACKUP_LOG_POS_END);
        SGBackupLog::write('Restore database total duration: ' . backupGuardFormattedDuration($this->_actionStartTs, time()));
    }

    private function processMigration()
    {
        $sgMigrateState = $this->getState();
        if ($sgMigrateState && $sgMigrateState->getAction() != SG_STATE_ACTION_MIGRATING_DATABASE) {
            SGBackupLog::writeAction('migration', SG_BACKUP_LOG_POS_START);
        }

        $sgMigrate = new SGMigrate($this->_sgdb);
        $sgMigrate->setDelegate($this);

        //Force empty value upload_path in table wp_options
        $params = array(
            'upload_path'
        );

        $this->_sgdb->query('UPDATE ' . SG_ENV_DB_PREFIX . 'options SET option_value="" WHERE option_name=%s', $params);

        $tables = $this->getTables();

        $oldSiteUrl = SGConfig::get('SG_OLD_SITE_URL');

        // Find and replace old urls with new ones
        $sgMigrate->migrate($oldSiteUrl, SG_SITE_URL, $tables);

        // Find and replace old db prefixes with new ones
        $sgMigrate->migrateDBPrefix();

        $isMultisite = backupGuardIsMultisite();
        if ($isMultisite) {
            $tables = explode(',', SG_MULTISITE_TABLES_TO_MIGRATE);

            $oldPath   = SGConfig::get('SG_MULTISITE_OLD_PATH');
            $newPath   = PATH_CURRENT_SITE;
            $newDomain = DOMAIN_CURRENT_SITE;

            $sgMigrate->migrateMultisite($newDomain, $newPath, $oldPath, $tables);
        }

        SGBackupLog::writeAction('migration', SG_BACKUP_LOG_POS_END);
    }

    public function finalizeRestore()
    {
        if (SG_ENV_ADAPTER != SG_ENV_WORDPRESS) {
            return;
        }

        //recreate current user (to be able to login with it)
        $this->restoreCurrentUser();

        //setting the following options will tell WordPress that the db is already updated
        // phpcs:disable
        global $wp_db_version;
        update_option('db_version', $wp_db_version);
        // phpcs:enable
        update_option('db_upgraded', true);

        //fix invalid upload path inserted in db
        update_option("upload_path", "");
    }

    private function export()
    {
        if ($this->_state && $this->_state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {
            if (!$this->isWritable($this->_backupFilePath)) {
                throw new SGExceptionForbidden('Permission denied. File is not writable: ' . $this->_backupFilePath);
            }
        }

        $customTablesToExclude = str_replace(' ', '', SGConfig::get('SG_TABLES_TO_EXCLUDE'));
        $tablesToExclude       = explode(',', SGConfig::get('SG_BACKUP_DATABASE_EXCLUDE') . ',' . $customTablesToExclude);

        $tablesToBackup = $this->_state->getTablesToBackup() ? explode(',', $this->_state->getTablesToBackup()) : array();

        $dump = new SGMysqldump(
            $this->_sgdb,
            SG_DB_NAME,
            'mysql',
            array(
                'exclude-tables' => $tablesToExclude,
                'include-tables' => $tablesToBackup,
                'skip-dump-date' => true,
                'skip-comments' => true,
                'skip-tz-utz' => true,
                'add-drop-table' => true,
                'no-autocommit' => false,
                'single-transaction' => false,
                'lock-tables' => false,
                'default-character-set' => SG_DB_CHARSET,
                'add-locks' => false
            )
        );

        $dump->setDelegate($this);

        $dump->start($this->_backupFilePath);
    }

    private function prepareQueryToExec($query)
    {
        $query = $this->replaceInvalidCharacters($query);
        $query = $this->replaceInvalidEngineTypeInQuery($query);

        if ($this->isMigrationAvailable()) {
            $tableNames    = $this->getBackedUpTables();
            $newTableNames = $this->getNewTableNames();
            $query         = $this->getMigrateObj()->replaceValuesInQuery($tableNames, $newTableNames, $query);
        }

        $query = $this->getCharsetHandler()->replaceInvalidCharsets($query);

        $query = rtrim(trim($query), "/*SGEnd*/");

        return $query;
    }

    private function replaceInvalidEngineTypeInQuery($query)
    {
        if (version_compare(SG_MYSQL_VERSION, '5.1', '>=')) {
            return str_replace("TYPE=InnoDB", "ENGINE=InnoDB", $query);
        } else {
            return str_replace("ENGINE=InnoDB", "TYPE=InnoDB", $query);
        }
    }

    private function replaceInvalidCharacters($str)
    {
        return $str;//preg_replace('/\x00/', '', $str);;
    }

    private function getDatabaseHeaders()
    {
        return "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;/*SGEnd*/" . PHP_EOL .
            "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;/*SGEnd*/" . PHP_EOL .
            "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;/*SGEnd*/" . PHP_EOL .
            "/*!40101 SET NAMES " . SG_DB_CHARSET . " */;/*SGEnd*/" . PHP_EOL .
            "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;/*SGEnd*/" . PHP_EOL .
            "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;/*SGEnd*/" . PHP_EOL .
            "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;/*SGEnd*/" . PHP_EOL .
            "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;/*SGEnd*/" . PHP_EOL;
    }

    private function import()
    {
        $fileHandle = @fopen($this->_backupFilePath, 'r');
        if (!is_resource($fileHandle)) {
            throw new SGExceptionForbidden('Could not open file: ' . $this->_backupFilePath);
        }

        $importQuery = '';
        $sgDBState   = $this->getState();
        if ($sgDBState && $sgDBState->getAction() == SG_STATE_ACTION_RESTORING_DATABASE) {
            $offset = $sgDBState->getOffset();
            fseek($fileHandle, $offset);

            $this->_totalRowCount      = $sgDBState->getNumberOfEntries();
            $this->_currentRowCount    = $sgDBState->getProgressCursor();
            $this->_nextProgressUpdate = $sgDBState->getProgress();
            $this->_warningsFound      = $sgDBState->getWarningsFound();

            $importQuery = $this->getDatabaseHeaders();
        }

        while (($row = @fgets($fileHandle)) !== false) {
            $importQuery .= $row;
            $trimmedRow  = trim($row);

            if (strpos($trimmedRow, 'CREATE TABLE') !== false) {
                $strLength   = strlen($trimmedRow);
                $strCtLength = strlen('CREATE TABLE ');
                $length      = $strLength - $strCtLength - 2;
                $tableName   = substr($trimmedRow, $strCtLength, $length);

                SGBackupLog::write('Importing table: ' . $tableName);
            }

            if ($trimmedRow && substr($trimmedRow, -9) == "/*SGEnd*/") {
                $queries = explode("/*SGEnd*/" . PHP_EOL, $importQuery);
                foreach ($queries as $query) {
                    if (!$query) {
                        continue;
                    }

                    $importQuery = $this->prepareQueryToExec($query);

                    SGPing::update();
                    $res = $this->_sgdb->execRaw($importQuery);
                    if ($res === false) {
                        //continue restoring database if any query fails
                        //we will just show a warning inside the log

                        if (isset($tableName)) {
                            $this->warn('Could not import table: ' . $tableName);
                        }

                        $this->warn('Error: ' . $this->_sgdb->getLastError());
                    }

                    $shouldReload    = $this->shouldReload();
                    $isReloadEnabled = backupGuardIsReloadEnabled();
                    if ($shouldReload && $isReloadEnabled && SGExternalRestore::isEnabled()) {
                        $offset = ftell($fileHandle);
                        $token  = $this->_delegate->getToken();

                        $sgDBState = $this->getState();

                        $sgDBState->setToken($token);
                        $sgDBState->setOffset($offset);
                        $sgDBState->setProgress($this->_nextProgressUpdate);
                        $sgDBState->setWarningsFound($this->_warningsFound);
                        $sgDBState->setNumberOfEntries($this->_totalRowCount);
                        $sgDBState->setProgressCursor($this->_currentRowCount);
                        $sgDBState->setActionId($this->_delegate->getActionId());
                        $sgDBState->setAction(SG_STATE_ACTION_RESTORING_DATABASE);

                        $sgDBState->save();

                        SGPing::update();
                        @fclose($fileHandle);

                        $this->reload();
                    }
                }

                $importQuery = '';
            }

            $this->_currentRowCount++;
            SGPing::update();
            $this->updateProgress();
        }

        @fclose($fileHandle);
    }

    public function saveCurrentUser()
    {
        if (SG_ENV_ADAPTER != SG_ENV_WORDPRESS) {
            return;
        }

        $user = wp_get_current_user();

        $currentUser = serialize(
        // phpcs:disable
            array(
                'login' => $user->user_login,
                'pass' => $user->user_pass,
                'email' => $user->user_email,
            )
        // phpcs:enable
        );

        SGConfig::set('SG_CURRENT_USER', $currentUser);
    }

    private function restoreCurrentUser()
    {
        $currentUser = SGConfig::get('SG_CURRENT_USER');
        $user        = unserialize($currentUser);

        //erase user data from the config table
        SGConfig::set('SG_CURRENT_USER', '');

        //if a user is found, it means it's cache, because we have dropped wp_users already
        $cachedUser = get_user_by('login', $user['login']);
        if ($cachedUser) {
            clean_user_cache($cachedUser); //delete user from cache
        }

        //create a user (it will be a subscriber)
        $id = wp_create_user($user['login'], $user['pass'], $user['email']);
        if (is_wp_error($id)) {
            SGBackupLog::write('User not recreated: ' . $id->get_error_message());

            return false; //user was not created for some reason
        }

        //get the newly created user
        $newUser = get_user_by('id', $id);

        //remove its role of subscriber
        $newUser->remove_role('subscriber');
        $isMultisite = backupGuardIsMultisite();

        if ($isMultisite) {
            // add super adminn role
            grant_super_admin($id);
        } else {
            //add admin role
            $newUser->add_role('administrator');
        }

        //update password to set the correct (old) password
        $this->_sgdb->query(
            'UPDATE ' . SG_ENV_DB_PREFIX . 'users SET user_pass=%s WHERE ID=%d',
            array(
                $user['pass'],
                $id
            )
        );

        //clean cache, so new password can take effect
        clean_user_cache($newUser);

        SGBackupLog::write('User recreated: ' . $user['login']);
    }

    public function warn($message)
    {
        $this->_warningsFound = true;
        SGBackupLog::writeWarning($message);
    }

    public function didExportRow()
    {
        $this->_currentRowCount++;
        SGPing::update();

        if ($this->updateProgress()) {
            if ($this->_delegate && $this->_delegate->isCancelled()) {
                $this->_cancelled = true;

                return;
            }
        }

        /*if (SGBoot::isFeatureAvailable('BACKGROUND_MODE') && $this->_delegate->isBackgroundMode())
        {
        SGBackgroundMode::next();
        }*/
    }

    public function cancel()
    {
        @unlink($this->filePath);
    }

    private function resetBackupProgress()
    {
        $this->_totalRowCount   = 0;
        $this->_currentRowCount = 0;
        $tableNames             = $this->getTables();
        foreach ($tableNames as $table) {
            $this->_totalRowCount += $this->getTableRowsCount($table);
        }
        $this->_nextProgressUpdate = $this->_progressUpdateInterval;
        SGBackupLog::write('Total tables to backup: ' . count($tableNames));
        SGBackupLog::write('Total rows to backup: ' . $this->_totalRowCount);
    }

    private function resetRestoreProgress()
    {
        $this->_totalRowCount          = $this->getFileLinesCount($this->_backupFilePath);
        $this->_currentRowCount        = 0;
        $this->_progressUpdateInterval = SGConfig::get('SG_ACTION_PROGRESS_UPDATE_INTERVAL');
        $this->_nextProgressUpdate     = $this->_progressUpdateInterval;
    }

    private function getTables()
    {
        $tableNames = array();
        $tables     = $this->_sgdb->query('SHOW TABLES FROM `' . SG_DB_NAME . '`');
        if (!$tables) {
            throw new SGExceptionDatabaseError('Could not get tables of database: ' . SG_DB_NAME);
        }
        foreach ($tables as $table) {
            $tableName       = $table['Tables_in_' . SG_DB_NAME];
            $tablesToExclude = explode(',', SGConfig::get('SG_BACKUP_DATABASE_EXCLUDE'));
            if (in_array($tableName, $tablesToExclude)) {
                continue;
            }
            $tableNames[] = $tableName;
        }

        return $tableNames;
    }

    private function getTableRowsCount($tableName)
    {
        $count        = 0;
        $tableRowsNum = $this->_sgdb->query('SELECT COUNT(*) AS total FROM ' . $tableName);
        $count        = @$tableRowsNum[0]['total'];

        return $count;
    }

    private function getFileLinesCount($filePath)
    {
        $fileHandle = @fopen($filePath, 'rb');
        if (!is_resource($fileHandle)) {
            throw new SGExceptionForbidden('Could not open file: ' . $filePath);
        }

        $linecount = 0;
        while (!feof($fileHandle)) {
            $linecount += substr_count(fread($fileHandle, 8192), "\n");
        }

        @fclose($fileHandle);

        return $linecount;
    }

    private function updateProgress()
    {
        $progress = round($this->_currentRowCount * 100.0 / $this->_totalRowCount);

        if ($progress >= $this->_nextProgressUpdate) {
            $this->_nextProgressUpdate += $this->_progressUpdateInterval;

            if ($this->_delegate) {
                $this->_delegate->didUpdateProgress($progress);
            }

            return true;
        }

        return false;
    }

    /* Helper Functions */

    private function isWritable($filePath)
    {
        if (!file_exists($filePath)) {
            $fp = @fopen($filePath, 'wb');
            if (!$fp) {
                throw new SGExceptionForbidden('Could not open file: ' . $filePath);
            }
            @fclose($fp);
        }

        return is_writable($filePath);
    }
}
