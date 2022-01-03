<?php

require_once SG_LOG_PATH . 'SGILogHandler.php';

class SGFileLogHandler implements SGILogHandler
{
    protected $_filePath = '';

    public function __construct($filePath)
    {
        $this->_filePath = $filePath;
    }

    public function canBeCleared()
    {
        return true;
    }

    public function isWritable()
    {
        if (!file_exists($this->_filePath)) {
            $fp = fopen($this->_filePath, 'wb');
            if (!$fp) {
                return false;
            }

            fclose($fp);
        }

        return is_writable($this->_filePath);
    }

    public function write($message)
    {
        if (!self::isWritable()) {
            return false;
        }

        $date    = backupGuardConvertDateTimezone(@date('Y-m-d H:i:s'), true);
        $content = $date . ': ' . $message . PHP_EOL;
        if (file_put_contents($this->_filePath, $content, FILE_APPEND)) {
            return true;
        }

        return false;
    }

    public function readAll()
    {
        if (!is_readable($this->_filePath)) {
            return false;
        }

        $content = file_get_contents($this->_filePath);

        return $content;
    }

    public function clear()
    {
        if (!self::isWritable()) {
            return false;
        }

        return @unlink($this->_filePath);
    }
}
