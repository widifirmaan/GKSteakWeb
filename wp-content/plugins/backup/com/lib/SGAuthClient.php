<?php

require_once(dirname(__FILE__) . '/BackupGuard/Client.php');

class SGAuthClient
{
    private static $_instance = null;
    private $_client = null;
    private $_accessToken = '';
    private $_accessTokenExpires = 0;
    private $_uploadAccessToken = '';
    private $_uploadAccessTokenExpires = 0;

    private function __construct()
    {
        $this->_accessToken              = SGConfig::get('SG_BACKUPGUARD_ACCESS_TOKEN', true);
        $this->_accessTokenExpires       = SGConfig::get('SG_BACKUPGUARD_ACCESS_TOKEN_EXPIRES', true);
        $this->_uploadAccessToken        = SGConfig::get('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN', true);
        $this->_uploadAccessTokenExpires = SGConfig::get('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN_EXPIRES', true);

        $this->_client = new BackupGuard\Client($this->_accessToken);
    }

    private function __clone()
    {
    }

    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function getAccessToken()
    {
        return $this->_accessToken;
    }

    public function getUploadAccessToken()
    {
        return $this->_uploadAccessToken;
    }

    public function login($email, $password)
    {
        try {
            $accessToken = $this->createAccessToken($email, $password);
        } catch (BackupGuard\Exception $ex) {
            return false;
        }

        $this->_client->setAccessToken($accessToken);

        return true;
    }

    public function logout()
    {
        $this->setTokens(); //reset all tokens
        $this->_client->setAccessToken(null);

        return true;
    }

    public function getCurrentUser()
    {
        try {
            $user = $this->_client->getCurrentUser();
        } catch (BackupGuard\Exception $ex) {
            return false;
        }

        return $user;
    }

    public function validateUrl($url)
    {
        if (!$this->prepareAuthorizedRequest()) {
            return -1;
        }

        try {
            $result = $this->_client->validateUrl($url, SG_PRODUCT_IDENTIFIER);
        } catch (BackupGuard\Exception $ex) {
            $result = $this->handleUnauthorizedException($ex);
            if ($result === true) { //we can try again
                $result = $this->validateUrl($url);
            }
        }

        return $result;
    }

    public function getAllUserProducts()
    {
        if (!$this->prepareAuthorizedRequest()) {
            return -1;
        }

        try {
            $result = $this->_client->getAllUserProducts(SG_PRODUCT_IDENTIFIER);
        } catch (BackupGuard\Exception $ex) {
            $result = $this->handleUnauthorizedException($ex);
            if ($result === true) { //we can try again
                $result = $this->getAllUserProducts();
            }
        }

        return $result;
    }

    public function isAnyLicenseAvailable($products)
    {
        if (empty($products) || $products == -1) {
            return false;
        }

        foreach ($products as $product) {
            if (!$product['licenses']) {
                return true;
            }

            $availableLicenses = $product['licenses'] - $product['used_licenses'];
            if ($availableLicenses > 0) {
                return true;
            }
        }

        return false;
    }

    public function linkUrlToProduct($url, $userProductId, &$error)
    {
        if (!$this->prepareAuthorizedRequest()) {
            return -1;
        }

        try {
            $result = $this->_client->linkUrlToProduct($url, $userProductId);
        } catch (BackupGuard\Exception $ex) {
            $result = $this->handleUnauthorizedException($ex);
            if ($result === true) { //we can try again
                $result = $this->linkUrlToProduct($url, $userProductId);
            }

            $error = $ex->getMessage();
        }

        return $result;
    }

    public function filterUpdateChecks($options)
    {
        //we need to be sure that access token is fresh before checking for updates
        $this->prepareAuthorizedRequest();

        $options['headers']['access_token'] = $this->getAccessToken();

        return $options;
    }

    private function handleUnauthorizedException($ex)
    {
        if ($ex instanceof BackupGuard\UnauthorizedException) {
            //access token has expired or is invalid, refresh it
            if ($this->refreshAccessToken()) {
                return true;
            } else {
                return -1; //could not refresh token, login is required
            }
        }

        return false;
    }

    private function prepareAuthorizedRequest()
    {
        //no access token found, login is required
        if (!$this->_accessToken) {
            return false;
        }

        //access token is expired, try to refresh it
        if (time() > $this->_accessTokenExpires) {
            if (!$this->refreshAccessToken()) {
                return false;
            }
        }

        return true;
    }

    private function setTokens($accessToken = '', $accessTokenExpires = 0, $refreshToken = '')
    {
        $this->_accessToken        = $accessToken;
        $this->_accessTokenExpires = $accessTokenExpires;
        $this->_client->setAccessToken($accessToken);

        SGConfig::set('SG_BACKUPGUARD_ACCESS_TOKEN', $accessToken, true);
        SGConfig::set('SG_BACKUPGUARD_ACCESS_TOKEN_EXPIRES', $accessTokenExpires, true);

        SGConfig::set('SG_BACKUPGUARD_REFRESH_TOKEN', $refreshToken, true);
    }

    private function createAccessToken($email, $password)
    {
        $tokens = $this->_client->createAccessToken(
            SG_BACKUPGUARD_CLIENT_ID,
            SG_BACKUPGUARD_CLIENT_SECRET,
            $email,
            $password
        );

        $this->setTokens(
            $tokens['access_token'],
            time() + BackupGuard\Config::TOKEN_EXPIRES,
            $tokens['refresh_token']
        );

        return $tokens['access_token'];
    }

    private function refreshAccessToken()
    {
        $refreshToken = SGConfig::get('SG_BACKUPGUARD_REFRESH_TOKEN', true);
        if (!$refreshToken) {
            $this->logout();

            return false;
        }

        try {
            $tokens = $this->_client->refreshAccessToken(
                SG_BACKUPGUARD_CLIENT_ID,
                SG_BACKUPGUARD_CLIENT_SECRET,
                $refreshToken
            );
        } catch (BackupGuard\Exception $ex) { //for some reason the refresh token doesn't work
            $this->logout();

            return false;
        }

        $this->setTokens(
            $tokens['access_token'],
            time() + BackupGuard\Config::TOKEN_EXPIRES,
            $tokens['refresh_token']
        );

        return $tokens['access_token'];
    }

    // Added by Nerses
    public function getMerchantOrderId()
    {
        if (!$this->prepareAuthorizedRequest()) {
            return -1;
        }

        try {
            $result = $this->_client->getMerchantOrderId(SG_PRODUCT_IDENTIFIER);
        } catch (BackupGuard\Exception $ex) {
            $result = $this->handleUnauthorizedException($ex);
            if ($result === true) { //we can try again
                $result = $this->getMerchantOrderId();
            }

            // phpcs:disable
            $error = $ex->getMessage();
            // phpcs:enable
        }

        return $result;
    }

    public function createUploadAccessToken($email, $password)
    {
        $tokens = $this->_client->createUploadAccessToken(
            SG_BACKUPGUARD_UPLOAD_CLIENT_ID,
            SG_BACKUPGUARD_UPLOAD_CLIENT_SECRET,
            $email,
            $password,
            SG_BACKUPGUARD_UPLOAD_SCOPE
        );

        $refreshToken                    = $tokens['refresh_token'];
        $this->_uploadAccessToken        = $tokens['access_token'];
        $this->_uploadAccessTokenExpires = time() + BackupGuard\Config::TOKEN_EXPIRES;
        $this->_client->setUploadAccessToken($this->_uploadAccessToken);

        SGConfig::set('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN', $this->_uploadAccessToken, true);
        SGConfig::set('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN_EXPIRES', $this->_uploadAccessTokenExpires, true);

        SGConfig::set('SG_BACKUPGUARD_UPLOAD_REFRESH_TOKEN', $refreshToken, true);

        return $tokens['access_token'];
    }
}
