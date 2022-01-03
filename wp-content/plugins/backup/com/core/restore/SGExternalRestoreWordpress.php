<?php

class SGExternalRestoreWordpress extends SGExternalRestore
{
    private $_destinationPath = '';
    private $_destinationUrl = '';

    protected function canPrepare()
    {
        //please lets try to check if it can work in the root directory
        $this->_destinationUrl  = rtrim(SG_SITE_URL, '/') . '/';
        $this->_destinationPath = ABSPATH;
        if ($this->testUrlAvailability($this->_destinationUrl, $this->_destinationPath)) {
            return true;
        }

        //then we check for the uploads directory
        $this->_destinationUrl  = SG_UPLOAD_URL . '/';
        $this->_destinationPath = SG_UPLOAD_PATH . '/';
        if ($this->testUrlAvailability($this->_destinationUrl, $this->_destinationPath)) {
            return true;
        }

        return false;
    }

    protected function getCustomConstants()
    {
        return array(
            'ABSPATH' => ABSPATH,
            'DB_NAME' => DB_NAME,
            'DB_USER' => DB_USER,
            'DB_PASSWORD' => DB_PASSWORD,
            'DB_HOST' => DB_HOST,
            'DB_CHARSET' => DB_CHARSET,
            'DB_COLLATE' => DB_COLLATE
        );
    }

    public function getDestinationPath()
    {
        return $this->_destinationPath;
    }

    public function getDestinationUrl()
    {
        return $this->_destinationUrl;
    }

    private function testUrlAvailability($url, $path)
    {
        // TODO:: remove after check logic
        return true;

        $path .= 'bg_test.php';
        $url  .= 'bg_test.php';

        if (@file_put_contents($path, '<?php echo "ok"; ?>')) {
            $headers   = @wp_remote_get(
                $url,
                array(
                    'sslverify' => false
                )
            );
            $isWpError = is_wp_error($headers);
            if (!$isWpError && !empty($headers) && $headers['response']['code'] == '200') {
                @unlink($path);

                return true;
            } else {
                $headers   = @wp_remote_get(
                    $url,
                    array(
                        'sslverify' => false,
                        'stream' => true
                    )
                );
                $isWpError = is_wp_error($headers);
                if (!$isWpError && !empty($headers) && $headers['response']['code'] == '200') {
                    @unlink($path);

                    return true;
                }
            }
            @unlink($path);
        }

        return false;
    }
}
