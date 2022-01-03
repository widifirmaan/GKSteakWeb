<?php

namespace BackupGuard;
use \SGConfig;

require_once(dirname(__FILE__).'/Helper.php');

class Client
{
	private $accessToken = null;
	private $uploadAccessToken = null;

	public function __construct($accessToken = null, $uploadAccessToken = null)
	{
		$this->setAccessToken($accessToken);
		$this->setUploadAccessToken($uploadAccessToken);
	}

	public function getAccessToken()
	{
		return $this->accessToken;
	}

	public function getUploadAccessToken()
	{
		return $this->uploadAccessToken;
	}

	public function setAccessToken($accessToken)
	{
		$this->accessToken = $accessToken;
	}

	public function setUploadAccessToken($uploadAccessToken)
	{
		$this->uploadAccessToken = $uploadAccessToken;
	}

	public function createAccessToken($clientId, $clientSecret, $email, $password)
	{
		$response = Helper::sendPostRequest(
			'/token',
			array(
				'grant_type' => 'password',
				'client_id' => $clientId,
				'client_secret' => $clientSecret,
				'email' => $email,
				'password' => $password
			)
		);
		Helper::validateResponse($response);

		$accessToken = $response->getBodyParam('access_token');
		$refreshToken = $response->getBodyParam('refresh_token');

		return array(
			'access_token' => $accessToken,
			'refresh_token' => $refreshToken
		);
	}

	public function refreshAccessToken($clientId, $clientSecret, $refreshToken)
	{
		$response = Helper::sendPostRequest(
			'/token',
			array(
				'grant_type' => 'refresh_token',
				'client_id' => $clientId,
				'client_secret' => $clientSecret,
				'refresh_token' => $refreshToken
			)
		);

		Helper::validateResponse($response);

		$accessToken = $response->getBodyParam('access_token');
		$refreshToken = $response->getBodyParam('refresh_token');

		return array(
			'access_token' => $accessToken,
			'refresh_token' => $refreshToken
		);
	}

	public function getCurrentUser()
	{
		Helper::requiredParam('access_token', $this->getAccessToken());

		$response = Helper::sendGetRequest(
			'/users',
			array(),
			array(
				'access_token' => $this->getAccessToken()
			)
		);

		Helper::validateResponse($response);

		return $response->getBody();
	}

	public function createUser($userInfo)
	{
		Helper::requiredParam('access_token', $this->getAccessToken());
		Helper::requiredParamInArray($userInfo, 'email');
		Helper::requiredParamInArray($userInfo, 'password');
		Helper::requiredParamInArray($userInfo, 'firstname');
		Helper::requiredParamInArray($userInfo, 'lastname');

		$params = array(
			'email' => $userInfo['email'],
			'password' => $userInfo['password'],
			'firstname' => $userInfo['firstname'],
			'lastname' => $userInfo['lastname']
		);

		if (!empty($userInfo['package'])) {
			$params['package'] = $userInfo['package'];
		}

		$response = Helper::sendPostRequest(
			'/users',
			$params,
			array(
				'access_token' => $this->getAccessToken()
			)
		);

		Helper::validateResponse($response);

		return $response->getBodyParam('user_id');
	}

	public function getBanner($env, $type, $userType = null)
	{
		Helper::requiredParam('environment', $env);

		$params = array(
			'environment' => $env,
			'type' => $type
		);

		if ($userType) {
			$params['user_type'] = $userType;
		}

		$response = Helper::sendGetRequest(
			'/banners',
			$params
		);

		try {
			Helper::validateResponse($response);
		}
		catch (Exception $e) {
			return '';
		}

		return $response->getBodyParam('html');
	}

	public function validateUrl($url, $productName)
	{
		Helper::requiredParam('access_token', $this->getAccessToken());
		Helper::requiredParam('url', $url);
		Helper::requiredParam('product', $productName);

		$params = array(
			'url' => $url,
			'product' => $productName
		);

		$response = Helper::sendPostRequest(
			'/products/validateUrl',
			$params,
			array(
				'access_token' => $this->getAccessToken()
			)
		);

		Helper::validateResponse($response);

		return $response->getBodyParam('license');
	}

	public function getAllUserProducts($productName = '')
	{
		Helper::requiredParam('access_token', $this->getAccessToken());

		$params = array();

		if ($productName) {
			$params['product'] = $productName;
		}

		$response = Helper::sendGetRequest(
			'/products',
			$params,
			array(
				'access_token' => $this->getAccessToken()
			)
		);

		Helper::validateResponse($response);

		return $response->getBodyParam('products');
	}

	public function linkUrlToProduct($url, $userProductId)
	{
		Helper::requiredParam('access_token', $this->getAccessToken());
		Helper::requiredParam('url', $url);
		Helper::requiredParam('product_id', $userProductId);

		$params = array(
			'url' => $url,
			'id' => $userProductId
		);

		$response = Helper::sendPostRequest(
			'/products/link',
			$params,
			array(
				'access_token' => $this->getAccessToken()
			)
		);

		Helper::validateResponse($response);

		return $response->getBodyParam('link_id');
	}

	//Added by Nerses
	public function getMerchantOrderId($productName)
	{
		Helper::requiredParam('access_token', $this->getAccessToken());
		Helper::requiredParam('product', $productName);

		$params = array(
			'product' => $productName
		);

		$response = Helper::sendGetRequest(
			'/products/merchant',
			$params,
			array(
				'access_token' => $this->getAccessToken()
			)
		);

		Helper::validateResponse($response);

		return $response->getBodyParam('id');
	}

	public function storeSubscriberInfo($url, $fname, $lname, $email, $priority)
	{
		Helper::requiredParam('url', $url);
		Helper::requiredParam('fname', $fname);
		Helper::requiredParam('lname', $lname);
		Helper::requiredParam('email', $email);
		Helper::requiredParam('priority', $priority);

		$params = array(
			'url' => $url,
			'fname' => $fname,
			'lname' => $lname,
			'email' => $email,
			'priority' => $priority,
			'referrer' => 'wordpress-backup-free'
		);

		$response = Helper::sendPostRequest(
			'/products/subscriber',
			$params
		);

		Helper::validateResponse($response);

		return $response->getBodyParam('subscriber');
	}

	public function storeSurveyResult($url, $firstname, $lastname, $email, $response)
	{
		Helper::requiredParam('url', $url);
		Helper::requiredParam('firstname', $firstname);
		Helper::requiredParam('lastname', $lastname);
		Helper::requiredParam('email', $email);
		Helper::requiredParam('response', $response);

		$params = array(
			'url' => $url,
			'firstname' => $firstname,
			'lastname' => $lastname,
			'email' => $email,
			'response' => $response,
			'name' => SG_PRODUCT_IDENTIFIER.'-deactivation'
		);

		$response = Helper::sendPostRequest(
			'/products/survey',
			$params
		);

		Helper::validateResponse($response);

		return $response->getBodyParam('survey');
	}

	public function createProfile($profileName, $master = 1, $profileType = 4)
	{
		Helper::requiredParam('profile_type', $profileType);
		Helper::requiredParam('profile_name', $profileName);

		$params = array(
			'profile_type' => $profileType,
			'profile_name' => $profileName,
			'master' => $master
		);

		$response = Helper::sendPostRequest(
			'/backups',
			$params,
			array(
				'access_token' => $this->getUploadAccessToken()
			)
		);

		Helper::validateResponse($response);

		$profileId = $response->getBodyParam('profile_id');
		$backupId = $response->getBodyParam('backup_id');

		return array(
			'profile_id' => $profileId,
			'backup_id' => $backupId
		);
	}

	public function createBackup($profileId, $backupName, $master = 0)
	{
		Helper::requiredParam('profile_id', $profileId);
		Helper::requiredParam('backup_name', $backupName);
		if (\SGConfig::get('BACKUP_GUARD_CREATE_MASTER')) {
			$master = 1;
			SGConfig::set('BACKUP_GUARD_CREATE_MASTER', 0);
		}

		$params = array(
			'profile_id' => $profileId,
			'master' => $master
		);

		$response = Helper::sendPostRequest(
			'/backups',
			$params,
			array(
				'access_token' => $this->getUploadAccessToken()
			)
		);

		Helper::validateResponse($response);

		$profileId = $response->getBodyParam('profile_id');
		$backupId = $response->getBodyParam('backup_id');

		return array(
			'profile_id' => $profileId,
			'backup_id' => $backupId
		);
	}

	public function getBackupChanges($profileId, $files)
	{
		Helper::requiredParam('profile_id', $profileId);
		Helper::requiredParam('files', $files);

		$params = array(
			'profile_id' => $profileId,
			'files' => $files
		);

		$response = Helper::sendPostRequest(
			'/backups/get_changes',
			$params
		);

		Helper::validateResponse($response);

		return $response->getBodyParam('files');
	}

	public function finalizeBackup($backupId)
	{
		Helper::requiredParam('backup_id', $backupId);

		$params = array(
			'backup_id' => $backupId
		);

		$response = Helper::sendPostRequest(
			'/backups/finalize',
			$params,
			array(
				'access_token' => $this->getUploadAccessToken()
			)
		);

		Helper::validateResponse($response);

		return;
	}

	public function getBackupContents($backupId, $root, $recursive)
	{
		Helper::requiredParam('backup_id', $backupId);

		$params = array(
			'backup_id' => $backupId,
			'root' => $root,
			'recursive' => $recursive
		);

		$response = Helper::sendGetRequest(
			'/backups/ls',
			$params
		);

		Helper::validateResponse($response);

		return $response->getBodyParam('files');
	}

	public function getAllBackups($profileId)
	{
		Helper::requiredParam('profile_id', $profileId);

		$response = Helper::sendGetRequest(
			'/backups/'.$profileId,
			array(),
			array(
				'access_token' => $this->getUploadAccessToken()
			)
		);

		Helper::validateResponse($response);

		return $response->getBodyParam('backups');
	}

	public function createUploadSession($backupId, $data)
	{
		Helper::requiredParam('backup_id', $backupId);

		$response = Helper::sendRequest(
			'/upload/'.$backupId,
			'PUT',
			$data,
			array(
				'access_token' => $this->getUploadAccessToken()
			)
		);

		Helper::validateResponse($response);

		$uploadId = $response->getBodyParam('upload_id');
		$offset = $response->getBodyParam('offset');

		return array(
			'upload_id' => $uploadId,
			'offset' => $offset
		);
	}

	public function resumeUploadSession($backupId, $uploadId, $offset, $data)
	{
		Helper::requiredParam('backup_id', $backupId);
		Helper::requiredParam('upload_id', $uploadId);
		Helper::requiredParam('offset', $offset);

		$response = Helper::sendRequest(
			'/upload/'.$backupId.'/'.$uploadId.'/'.$offset,
			'PUT',
			$data,
			array(
				'access_token' => $this->getUploadAccessToken()
			)
		);

		Helper::validateResponse($response);

		$uploadId = $response->getBodyParam('upload_id');
		$offset = $response->getBodyParam('offset');

		return array(
			'upload_id' => $uploadId,
			'offset' => $offset
		);
	}

	public function finalizeUpload($backupId, $uploadId, $path, $db = 0)
	{
		Helper::requiredParam('backup_id', $backupId);
		Helper::requiredParam('upload_id', $uploadId);
		Helper::requiredParam('path', $path);

		$params = array(
			'upload_id' => $uploadId,
			'path' => $path,
			'db' => $db
		);

		$response = Helper::sendPostRequest(
			'/upload/'.$backupId.'/finalize',
			$params,
			array(
				'access_token' => $this->getUploadAccessToken()
			)
		);

		Helper::validateResponse($response);

		return;
	}

	public function createFolder($backupId, $path)
	{
		Helper::requiredParam('backup_id', $backupId);
		Helper::requiredParam('path', $path);

		$params = array(
			'backup_id' => $backupId
		);

		$response = Helper::sendGetRequest(
			'/files/folder',
			$params
		);

		Helper::validateResponse($response);

		return;
	}

	public function downloadFile($backupId, $path, $offset, $limit)
	{
		Helper::requiredParam('backup_id', $backupId);
		Helper::requiredParam('path', $path);
		Helper::requiredParam('offset', $offset);
		Helper::requiredParam('limit', $limit);

		$params = array(
			'backup_id' => $backupId,
			'path' => $path,
			'offset' => $offset,
			'limit' => $limit
		);

		$response = Helper::sendPostRequest(
			'/files',
			$params,
			array(
				'access_token' => $this->getUploadAccessToken()
			)
		);

		Helper::validateResponse($response);

		return $response->getBody();
	}

	public function checkCloudAccount()
	{
		Helper::requiredParam('access_token', $this->getAccessToken());

		$response = Helper::sendGetRequest(
			'/users/cloud',
			array(),
			array(
				'access_token' => $this->getAccessToken()
			)
		);

		Helper::validateResponse($response);

		$trial = $response->getBodyParam('trial');
		$usedStorage = $response->getBodyParam('used_storage');
		$lastPaymentDate = $response->getBodyParam('last_payment_date');
		$paymentFrequency = $response->getBodyParam('payment_frequency');
		$package = $response->getBodyParam('package');

		return array(
			'trial' => $trial,
			'usedStorage' => $usedStorage,
			'lastPaymentDate' => $lastPaymentDate,
			'paymentFrequency' => $paymentFrequency,
			'package' => $package
		);
	}

	public function checkEmailExists($email)
	{
		Helper::requiredParam('email', $email);

		$response = Helper::sendGetRequest(
			'/users/email/'.$email,
			array()
		);

		Helper::validateResponse($response);

		return $response->getBodyParam('found');
	}

	public function addCloudAccountToUser()
	{
		Helper::requiredParam('access_token', $this->getAccessToken());

		$response = Helper::sendPostRequest(
			'/users/cloud',
			array(),
			array(
				'access_token' => $this->getAccessToken()
			)
		);

		Helper::validateResponse($response);

		return;
	}

	public function createCloudUser($email, $firstname, $lastname)
	{
		Helper::requiredParam('email', $email);
		Helper::requiredParam('firstname', $firstname);
		Helper::requiredParam('lastname', $lastname);

		$response = Helper::sendPostRequest(
			'/users',
			array(
				'email' => $email,
				'firstname' => $firstname,
				'lastname' => $lastname
			)
		);

		Helper::validateResponse($response);

		$email = $response->getBodyParam('email');
		$password = $response->getBodyParam('password');

		return array(
			'password' => $password,
			'email' => $email
		);
	}

	public function createUploadAccessToken($clientId, $clientSecret, $email, $password, $scope)
	{
		$response = Helper::sendPostRequest(
			'/token',
			array(
				'grant_type' => 'password',
				'scope' => $scope,
				'client_id' => $clientId,
				'client_secret' => $clientSecret,
				'email' => $email,
				'password' => $password
			)
		);

		Helper::validateResponse($response);

		$accessToken = $response->getBodyParam('access_token');
		$refreshToken = $response->getBodyParam('refresh_token');

		return array(
			'access_token' => $accessToken,
			'refresh_token' => $refreshToken
		);
	}

	public function getProfiles()
	{
		Helper::requiredParam('access_token', $this->getUploadAccessToken());

		$response = Helper::sendGetRequest(
			'/profiles',
			array(),
			array(
				'access_token' => $this->getUploadAccessToken()
			)
		);

		Helper::validateResponse($response);

		return $response->getBodyParam('profiles');
	}
}
