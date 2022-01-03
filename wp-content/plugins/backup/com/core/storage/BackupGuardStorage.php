<?php
namespace BackupGuard;

use \SGConfig;
use \SGExceptionForbidden;
require_once(SG_STORAGE_PATH.'SGStorage.php');
require_once(SG_LIB_PATH.'BackupGuard/Client.php');

class Storage extends \SGStorage
{
	private $client = null;
	private $accessToken = '';
	private $refreshToken = '';

	public function init()
	{
		$this->client = new Client();
		$this->client->setUploadAccessToken(SGConfig::get('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN'));
	}

	public function setAccessToken($accessToken)
	{
		$this->accessToken = $accessToken;
	}

	private function getRefreshToken()
	{
		return SGConfig::get('SG_BACKUPGUARD_UPLOAD_REFRESH_TOKEN', true);
	}

	private function getProfileId()
	{
		return SGConfig::get("BACKUP_GUARD_PROFILE_ID");
	}

	private function getAccessToken(&$expirationTs = 0)
	{
		$expirationTs = (int)SGConfig::get('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN_EXPIRES');
		return SGConfig::get('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN');
	}

	public function connect()
	{
		if ($this->isConnected()) {
			return;
		}

		$args = func_get_args();
		if (!count($args)) {
			throw new SGExceptionForbidden('Invalid credentials');
		}

		$email = $args[0];
		$password = $args[1];
		$tmp = isset($args[2])?$args[2]:false;

		if (!$email || !$password) {
			throw new SGExceptionForbidden('Invalid credentials');
		}

		if ($tmp) {
			$tokens = $this->client->createAccessToken(
				SG_BACKUPGUARD_UPLOAD_CLIENT_ID,
				SG_BACKUPGUARD_UPLOAD_CLIENT_SECRET,
				$email,
				$password
			);
		}
		else {
			$tokens = $this->client->createUploadAccessToken(
				SG_BACKUPGUARD_UPLOAD_CLIENT_ID,
				SG_BACKUPGUARD_UPLOAD_CLIENT_SECRET,
				$email,
				$password,
				SG_BACKUPGUARD_UPLOAD_SCOPE
			);
		}

		if (empty($tokens)) {
			throw new SGExceptionForbidden('Invalid credentials');
		}

		$this->setTokens(
			$tokens['access_token'],
			time()+Config::TOKEN_EXPIRES,
			$tokens['refresh_token'],
			$tmp
		);

		return $tokens['access_token'];
	}

	private function setTokens($accessToken = '', $accessTokenExpires = 0, $refreshToken = '', $tmp = false)
	{
		$this->accessToken = $accessToken;
		$this->refreshToken = $refreshToken;
		
		if (!$tmp) {
			SGConfig::set('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN', $accessToken);
			SGConfig::set('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN_EXPIRES', $accessTokenExpires);
			SGConfig::set('SG_BACKUPGUARD_UPLOAD_REFRESH_TOKEN', $refreshToken);

			$this->client->setUploadAccessToken($accessToken);
		}
	}

	public function connectOffline()
	{
		if ($this->isConnected()) {
			return;
		}

		$refreshToken = $this->getRefreshToken();

		if (!$refreshToken) {
			throw new \SGExceptionNotFound('Refresh token not found');
		}
			
		// refresh access token using the refresh token
		$tokens = $this->client->refreshAccessToken(SG_BACKUPGUARD_UPLOAD_CLIENT_ID, SG_BACKUPGUARD_UPLOAD_CLIENT_SECRET, $refreshToken);

		// set the new access token
		$this->setTokens(
			$tokens['access_token'],
			time()+Config::TOKEN_EXPIRES,
			$tokens['refresh_token']
		);

		$this->connected = true;
	}

	public function checkConnected()
	{
		$accessToken = $this->getAccessToken($expirationTs);
		// to avoid case where token can expire in the middle of upload
		$this->connected = ($accessToken&&$expirationTs>=(time()+60))?true:false;

		if ($this->connected) {
			$this->client->setUploadAccessToken(SGConfig::get('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN'));
		}
	}

	public function getListOfFiles()
	{
		if (!$this->isConnected()) {
			$this->connectOffline();
		}

		$profileId = $this->getProfileId();

		if ($profileId) {
			$list = $this->client->getAllBackups($profileId);
			return $list;
		}

		return array();
	}

	public function createFolder($folderName)
	{

	}

	public function downloadFile($filePath, $size, $backupId = null)
	{
		$offset = 0;
		$result = false;
		$chunk = 2000000; // 2MB
		$loaclFilePath = SG_BACKUP_DIRECTORY.basename($filePath);
		$serverFilePath = $filePath;

		$fp = @fopen($loaclFilePath, 'ab');

		while ($size > $offset) {
			if (!file_exists($loaclFilePath)) {
				$result = false;
				break;
			}

			$data = $this->client->downloadFile($backupId, $serverFilePath, $offset, $chunk);

			if (strlen($data)) {
				fwrite($fp, $data);
			}
			else {
				break;
			}

			$offset += $chunk;
			$result = true;
		}

		return $result;
	}

	private function saveStateData($fileOffset, $uploadId, $backupId, $profileId)
	{
		$token = $this->delegate->getToken();
		$actionId = $this->delegate->getActionId();
		$pendingStorageUploads = $this->delegate->getPendingStorageUploads();
		$currentUploadChunksCount = $this->delegate->getCurrentUploadChunksCount();
		$progress = $this->delegate->getProgress();

		$this->state->setCurrentUploadChunksCount($currentUploadChunksCount);
		$this->state->setStorageType(SG_STORAGE_BACKUP_GUARD);
		$this->state->setPendingStorageUploads($pendingStorageUploads);
		$this->state->setToken($token);
		$this->state->setActionId($actionId);
		$this->state->setAction(SG_STATE_ACTION_UPLOADING_BACKUP);
		$this->state->setProgress($progress);
		
		$this->state->setOffset($fileOffset);
		$this->state->setUploadId($uploadId);
		$this->state->setBackupId($backupId);
		$this->state->setProfileId($profileId);
		
		$this->state->save();
	}

	public function uploadFile($filePath)
	{
		if (!$this->isConnected()) {
			throw new SGExceptionForbidden('Permission denied. Authentication required.');
		}

		if (!file_exists($filePath) || !is_readable($filePath)) {
			throw new \SGExceptionNotFound('File does not exist or is not readable: '.$filePath);
		}

		//$chunkSizeBytes = 2000000;
		$chunkSizeBytes = (int)getCloudUploadChunkSize() * 1024 * 1024;
		$fileSize = backupGuardRealFilesize($filePath);
		$backupFileName = $this->state->getBackupFileName().'.sgbp';

		$this->delegate->willStartUpload((int)ceil($fileSize/$chunkSizeBytes));

		$handle = fopen($filePath, "rb");
		$byteOffset = $this->state->getOffset();
		fseek($handle, $byteOffset);

		if ($this->state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {

			$profileId = $this->getProfileId();
			if ($profileId) {
				$result = $this->client->createBackup($profileId, $backupFileName);
				if (count($result)) {
					$profileId = $result['profile_id'];
					$backupId = $result['backup_id'];
				}
				else {
					throw new SGExceptionForbidden('Something went wrong. Unable to create backup.');
				}
			}
			else {
				throw new SGExceptionForbidden('Something went wrong. Unable to create profile.');
			}

			$data = fread($handle, $chunkSizeBytes);
			$result = $this->client->createUploadSession($backupId, $data);
			if (count($result)) {
				$uploadId = $result['upload_id'];
				$offset = $result['offset'];
			}
			else {
				throw new SGExceptionForbidden('Something went wrong. Unable to start upload session.');
			}

			$byteOffset += strlen($data);
		}
		else {
			$uploadId = $this->state->getUploadId();
			$backupId = $this->state->getBackupId();
			$profileId = $this->state->getProfileId();
		}

		\SGPing::update();

		while ($byteOffset < $fileSize) {
			$data = fread($handle, $chunkSizeBytes);
			$result = $this->client->resumeUploadSession($backupId, $uploadId, $byteOffset, $data);
			if (count($result)) {
				$uploadId = $result['upload_id'];
				$offset = $result['offset'];
			}
			else {
				throw new SGExceptionForbidden('Something went wrong. Unable to start upload session.');
			}
			
			if (!$this->delegate->shouldUploadNextChunk()) {
				fclose($handle);
				return;
			}

			\SGPing::update();
			$byteOffset += strlen($data);
			$shouldReload = $this->shouldReload();
			if ($shouldReload && backupGuardIsReloadEnabled()) {
				$this->saveStateData($byteOffset, $uploadId, $backupId, $profileId);
				@fclose($handle);
				$this->reload();
			}
		}

		$path = $backupFileName;

		$this->client->finalizeUpload($backupId, $uploadId, $path);
		$this->client->finalizeBackup($backupId);
		fclose($handle);
	}

	public function deleteFile($fileName)
	{

	}

	public function deleteFolder($folderName)
	{

	}

	public function fileExists($path)
	{

	}

	public function checkCloudAccount()
	{
		$this->client->setAccessToken($this->accessToken);
		$account = $this->client->checkCloudAccount();
		return $account;
	}

	public function getProfiles()
	{
		$profiles = array();

		try {
			$profiles = $this->client->getProfiles();
		}
		catch (UnauthorizedException $exp) {
			$this->connected = false;
			$this->connectOffline();
			$profiles = $this->getProfiles();
		}
		catch (Exception $exp) {
			$profileId = $this->createProfile();
			$profiles[] = array(
				'id' => $profileId,
				'name' => $profileName
			);
		}

		return $profiles;
	}

	public function createProfile($name = null)
	{
		$profileName = $name?$name:backupGuardGetSiteUrl();
		try {
			$result = $this->client->createProfile($profileName);
			$profileId = $result['profile_id'];
		}
		catch (UnauthorizedException $exp) {
			$this->connected = false;
			$this->connectOffline();
			$profileId = $this->createProfile();
		}
		catch (Exception $exp) {
			$profileId = 0;
		}

		return $profileId;
	}
}