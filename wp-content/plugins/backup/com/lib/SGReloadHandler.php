<?php

require_once(SG_REQUEST_PATH . 'SGRequest.php');

class SGReloadHandler
{
    private $_scheme;
    private $_host;
    private $_uri;
    private $_port;

    public function __construct($url)
    {
        $this->_scheme = backupGuardGetCurrentUrlScheme();
        $this->_host   = backupGuardGetCurrentUrlHost();
        $this->_uri    = $url;
        $this->_port   = @$_SERVER['SERVER_PORT'];

        if (!$this->_port) {
            $this->_port = 80;
        }
    }

    public function reload()
    {
        $selectedReloadMethod = SGConfig::get('SG_BACKGROUND_RELOAD_METHOD');
        $url                  = $this->_scheme . '://' . $this->_host . $this->_uri . "&method=" . $selectedReloadMethod;

        $request = SGRequest::getInstance();
        $request->setUrl($url);
        $request->setParams(array());
        $request->setHeaders(array());
        $request->sendGetRequest();
    }
}
