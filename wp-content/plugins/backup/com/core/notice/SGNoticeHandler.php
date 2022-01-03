<?php

class SGNoticeHandler
{
	public function run()
	{
		$this->checkTimeoutError();
		$this->checkMigrationError();
		$this->checkRestoreNotWritableError();
		$this->checkLiteSpeedWarning();
		$this->checkTables();
		$this->checkPingFilePermission();
	}

	private function checkTimeoutError()
	{
		$pluginCapabilities = backupGuardGetCapabilities();
		if (SGConfig::get('SG_EXCEPTION_TIMEOUT_ERROR')) {
			if ($pluginCapabilities != BACKUP_GUARD_CAPABILITIES_FREE) {
				SGNotice::getInstance()->addNoticeFromTemplate('timeout_error', SG_NOTICE_ERROR, true);
			}
			else {
				SGNotice::getInstance()->addNoticeFromTemplate('timeout_free_error', SG_NOTICE_ERROR, true);
			}
		}
	}
	
	public function checkTables()
	{
		if (!checkAllMissedTables()) {
			SGNotice::getInstance()->addNoticeFromTemplate('missed_table', SG_NOTICE_ERROR, true);
		}
	}

	private function checkMigrationError()
	{
		if (SGConfig::get('SG_BACKUP_SHOW_MIGRATION_ERROR')) {
			SGNotice::getInstance()->addNoticeFromTemplate('migration_error', SG_NOTICE_ERROR, true);
		}
	}

	private function checkRestoreNotWritableError()
	{
		if (SGConfig::get('SG_BACKUP_SHOW_NOT_WRITABLE_ERROR')) {
			SGNotice::getInstance()->addNoticeFromTemplate('restore_notwritable_error', SG_NOTICE_ERROR, true);
		}
	}

	private function checkLiteSpeedWarning()
	{
		$server = '';
		if (isset($_SERVER['SERVER_SOFTWARE'])) {
			$server = strtolower($_SERVER['SERVER_SOFTWARE']);
		}

		//check if LiteSpeed server is running
		if (strpos($server, 'litespeed') !== false) {
			$htaccessContent = '';
			if (is_readable(ABSPATH.'.htaccess')) {
				$htaccessContent = @file_get_contents(ABSPATH.'.htaccess');
				if (!$htaccessContent) {
					$htaccessContent = '';
				}
			}

			if (!$htaccessContent || !preg_match('/noabort/i', $htaccessContent)) {
				SGNotice::getInstance()->addNoticeFromTemplate('litespeed_warning', SG_NOTICE_WARNING);
			}
		}
	}
	
	private function checkPingFilePermission()
	{
		if (file_exists(SG_PING_FILE_PATH) && !is_readable(SG_PING_FILE_PATH)) {
			SGNotice::getInstance()->addNoticeFromTemplate('ping_permission', SG_NOTICE_ERROR, true);
		}
	}
}
