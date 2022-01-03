<?php

require_once SG_STORAGE_PATH . 'SGStorage.php';

use Dropbox as dbx;

class SGDropboxStorage extends SGStorage
{
    private $_client = null;
    private $_fd = null;
    private $_filePath = '';

    public function init()
    {
        //check if curl extension is loaded
        SGBoot::checkRequirement('curl');

        // Dropbox api 2
        $this->setActiveDirectory('');

        @set_exception_handler(array('SGDropboxStorage', 'exceptionHandler'));
        include_once SG_STORAGE_PATH . 'SGDropbox.php';
    }

    public static function exceptionHandler($exception)
    {
        if (SG_ENV_ADAPTER == SG_ENV_WORDPRESS) {
            wp_die($exception->getMessage());
        } elseif (SG_ENV_ADAPTER == SG_ENV_MAGENTO) {
            die($exception->getMessage());
        }
    }

    public function connect()
    {
        if ($this->isConnected()) {
            return;
        }

        // phpcs:disable
        $authCode = $this->getAuthCodeFromURL($cancel);

        if ($cancel) {
            throw new SGExceptionMethodNotAllowed('User did not allow access');
        }
        // phpcs:enable

        $this->auth($authCode);
    }

    private function auth($authCode = '')
    {
        if ($authCode) {
            try {
                //exchange authorization code for access token
                parse_str($_SERVER['QUERY_STRING'], $arr);
                list($accessToken) = $this->getWebAuth()->finish($arr);

                $this->setAccessToken($accessToken);
                $accountInfo = $this->getClient()->getAccountInfo();
                SGConfig::set('SG_DROPBOX_CONNECTION_STRING', $accountInfo['email']);

                $this->connected = true;
                return;
            } catch (Exception $ex) {
            }
        }

        $refUrl       = base64_encode($this->getRefURL());
        $authorizeUrl = $this->getWebAuth()->start($refUrl);
        header("Location: $authorizeUrl");
        exit;
    }

    public function connectOffline()
    {
        if ($this->isConnected()) {
            return;
        }

        if (!$this->getClient()) {
            throw new SGExceptionNotFound('Access token not found');
        }

        $this->connected = true;
    }

    public function checkConnected()
    {
        $accessToken     = $this->getAccessToken();
        $this->connected = $accessToken ? true : false;
    }

    public function getListOfFiles()
    {
        if (!$this->isConnected()) {
            throw new SGExceptionForbidden('Permission denied. Authentication required.');
        }

        $listOfFiles     = array();
        $activeDirectory = rtrim($this->getActiveDirectory()) . '/';
        $metaData        = $this->getClient()->getMetadataWithChildren($activeDirectory . SGConfig::get('SG_STORAGE_BACKUPS_FOLDER_NAME'));

        foreach ($metaData['entries'] as $file) {
            $size = $file['size'];
            $date = $this->standardizeFileCreationDate($file['client_modified']);
            $name = basename($file['path_display']);

            $listOfFiles[$name] = array(
                'name' => $name,
                'size' => $size,
                'date' => $date,
                'path' => $file['path_display'],
            );
        }

        krsort($listOfFiles);
        return $listOfFiles;
    }

    public function createFolder($folderName)
    {
        if (!$this->isConnected()) {
            throw new SGExceptionForbidden('Permission denied. Authentication required.');
        }

        $path = rtrim($this->getActiveDirectory(), '/') . '/' . $folderName;
        $this->getClient()->createFolder($path);

        return $path;
    }

    public function downloadFile($file, $size, $backupId = null)
    {
        if (!$file) {
            return false;
        }

        $this->_filePath = SG_BACKUP_DIRECTORY . basename($file);
        $this->_fd       = fopen(SG_BACKUP_DIRECTORY . basename($file), "w");

        $client = $this->getClient();

        $url    = "https://content.dropboxapi.com/2/files/download";
        $params = array(
            "path" => $file
        );

        $chunk  = 1.0 * 1024 * 1024;
        $start  = 0;
        $end    = $chunk;
        $result = true;

        while (true) {
            if ($end > $size) {
                $end = $size;
            }

            if ($start >= $size) {
                $result = true;
                break;
            }

            $curl = $client->mkCurl($url);
            $curl->set(CURLOPT_CUSTOMREQUEST, "POST");
            $curl->set(CURLOPT_WRITEFUNCTION, array($this, 'writer'));
            $curl->addHeader("Dropbox-API-Arg: " . json_encode($params));
            $curl->addHeader("Range: bytes=$start-$end");

            $response = $curl->exec();

            if ($response->statusCode !== 206) {
                $result = false;
                break;
            }

            $start = $end + 1;
            $end   += $chunk;
        }

        fclose($this->_fd);

        if (!$result) {
            @unlink(SG_BACKUP_DIRECTORY . basename($file));
        }

        return $result;
    }

    public function writer($ch, $data)
    {
        if (!file_exists($this->_filePath)) {
            return -1;
        }

        fwrite($this->_fd, $data);
        return strlen($data);
    }

    private function saveStateData($uploadId, $offset)
    {
        $token                    = $this->delegate->getToken();
        $actionId                 = $this->delegate->getActionId();
        $pendingStorageUploads    = $this->delegate->getPendingStorageUploads();
        $currentUploadChunksCount = $this->delegate->getCurrentUploadChunksCount();
        $progress                 = $this->delegate->getProgress();

        $this->state->setProgress($progress);
        $this->state->setCurrentUploadChunksCount($currentUploadChunksCount);
        $this->state->setStorageType(SG_STORAGE_DROPBOX);
        $this->state->setPendingStorageUploads($pendingStorageUploads);
        $this->state->setToken($token);
        $this->state->setUploadId($uploadId);
        $this->state->setOffset($offset);
        $this->state->setAction(SG_STATE_ACTION_UPLOADING_BACKUP);
        $this->state->setActiveDirectory($this->getActiveDirectory());
        $this->state->setActionId($actionId);
        $this->state->save();
    }

    public function uploadFile($filePath)
    {
        if (!$this->isConnected()) {
            throw new SGExceptionForbidden('Permission denied. Authentication required.');
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new SGExceptionNotFound('File does not exist or is not readable: ' . $filePath);
        }

        $client = $this->getClient();
        //$chunkSizeBytes = 2.0 * 1024 * 1024;
        $chunkSizeBytes = (int) getCloudUploadChunkSize() * 1024 * 1024;

        //Note: Because PHP's integer type is signed and many platforms use 32bit integers,
        //some filesystem functions may return unexpected results for files which are larger than 2GB.
        $fileSize = backupGuardRealFilesize($filePath);

        $this->delegate->willStartUpload((int) ceil($fileSize / $chunkSizeBytes));

        $handle     = fopen($filePath, "rb");
        $byteOffset = $this->state->getOffset();
        fseek($handle, $byteOffset);

        if ($this->state->getAction() == SG_STATE_ACTION_PREPARING_STATE_FILE) {
            $data       = fread($handle, $chunkSizeBytes);
            $uploadId   = $client->chunkedUploadStart($data);
            $byteOffset += strlen($data);
        } else {
            $uploadId = $this->state->getUploadId();
        }

        SGPing::update();

        while ($byteOffset < $fileSize) {
            $data = fread($handle, $chunkSizeBytes);
            $client->chunkedUploadContinue($uploadId, $byteOffset, $data);
            $byteOffset += strlen($data);

            if (!$this->delegate->shouldUploadNextChunk()) {
                fclose($handle);
                return;
            }

            SGPing::update();

            $shouldReload = $this->shouldReload();
            if ($shouldReload && backupGuardIsReloadEnabled()) {
                $this->saveStateData($uploadId, $byteOffset);
                @fclose($handle);
                $this->reload();
            }
        }

        $activeDirectory = $this->getActiveDirectory();

        $path = rtrim($activeDirectory, '/') . '/' . basename($filePath);

        $result = $client->chunkedUploadFinish($uploadId, $path, $byteOffset);
        fclose($handle);

        return $result;
    }

    public function fileExists($path)
    {
        $this->connectOffline();
        if (!$this->isConnected()) {
            throw new SGExceptionForbidden('Permission denied. Authentication required.');
        }

        $client = $this->getClient();
        try {
            $result = $client->searchFileNames(dirname($path), basename($path));
        } catch (Exception $e) {
            return false;
        }

        return $result;
    }

    public function deleteFile($path)
    {
        $this->connectOffline();
        if (!$this->isConnected()) {
            throw new SGExceptionForbidden('Permission denied. Authentication required.');
        }

        return $this->getClient()->delete($path);
    }

    public function deleteFolder($folderName)
    {
        return $this->deleteFile($folderName);
    }

    private function getAppInfo()
    {
        $key    = SG_STORAGE_DROPBOX_KEY;
        $secret = SG_STORAGE_DROPBOX_SECRET;

        $appInfo = new dbx\AppInfo($key, $secret);
        return $appInfo;
    }

    private function getAccessToken()
    {
        return SGConfig::get('SG_DROPBOX_ACCESS_TOKEN');
    }

    private function setAccessToken($accessToken)
    {
        SGConfig::set('SG_DROPBOX_ACCESS_TOKEN', $accessToken, true);
    }

    private function getClient()
    {
        if (!$this->_client) {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return false;
            }

            $appInfo = $this->getAppInfo();
            return new dbx\Client($accessToken, SG_STORAGE_DROPBOX_CLIENT_ID, null, $appInfo->getHost());
        }

        return $this->_client;
    }

    private function getWebAuth()
    {
        $appInfo        = $this->getAppInfo();
        $redirectUri    = SG_STORAGE_DROPBOX_REDIRECT_URI;
        $savedCSRFToken = SGConfig::get('SG_DROPBOX_CONNECTION_CSRF_TOKEN');
        if (!empty($savedCSRFToken)) {
            $_SESSION['dropbox-auth-csrf-token'] = $savedCSRFToken;
            SGConfig::set('SG_DROPBOX_CONNECTION_CSRF_TOKEN', false);
        }

        $csrfTokenStore = new dbx\ArrayEntryStore($_SESSION, 'dropbox-auth-csrf-token');
        return new dbx\WebAuth($appInfo, SG_STORAGE_DROPBOX_CLIENT_ID, $redirectUri, $csrfTokenStore, null);
    }

    private function getCurrentURL()
    {
        $http = backupGuardGetCurrentUrlScheme();
        $url  = $http . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return $url;
    }

    private function getRefURL()
    {
        $refUrl = $this->getCurrentURL();
        if (!$_SERVER['QUERY_STRING']) {
            $refUrl .= '?';
        } else {
            $refUrl .= '&';
        }

        return $refUrl;
    }

    private function getAuthCodeFromURL(&$cancel = false)
    {
        $query = $_SERVER['QUERY_STRING'];
        if (!$query) {
            return '';
        }

        $query = explode('&', $query);
        $code  = '';
        foreach ($query as $q) {
            $q = explode('=', $q);
            if ($q[0] == 'code') {
                $code = $q[1];
            } else if ($q[0] == 'cancel' && $q[1] == '1') {
                $cancel = true;
                break;
            }
        }

        return $code;
    }
}
