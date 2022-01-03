<?php
use BackupGuard\Helper;

class SGStatsRequests
{
    public static function initialSync()
    {
        $allowDataCollection = SGConfig::get('SG_BACKUP_SEND_USAGE_STATUS');
        $allowInitialSync = SGConfig::get('SG_BACKUP_INITIAL_SYNC');
       
        if ($allowDataCollection && !$allowInitialSync) {
            SGConfig::set('SG_BACKUP_INITIAL_SYNC', 1);
            $data = self::getInitialSyncData();
            Helper::sendPostRequest(
                '/stats/init',
                $data
            );
        }
    }

    private static function getInitialSyncData()
    {
        $ip = self::getIpAddress();
        $pluginVersion = SG_BACKUP_GUARD_VERSION;
        global $wp_version;
        $url = get_site_url();
        $capabilities = backupGuardGetCapabilities();

        $data = array(
            'ip' => $ip,
            'pluginVersion' => $pluginVersion,
            'WPVersion' => $wp_version,
            'URL' => $url,
            'pluginPackage' => $capabilities,
        );

        return apply_filters('sgBackupInitialSyncData', $data);
    }

    public static function getIpAddress()
    {
        $ipAddress = 'UNKNOWN';

        if (getenv('HTTP_CLIENT_IP')) {
            $ipAddress = getenv('HTTP_CLIENT_IP');
        }
        else if (getenv('HTTP_X_FORWARDED_FOR')) {
            $ipAddress = getenv('HTTP_X_FORWARDED_FOR');
        }
        else if (getenv('HTTP_X_FORWARDED')) {
            $ipAddress = getenv('HTTP_X_FORWARDED');
        }
        else if (getenv('HTTP_FORWARDED_FOR')) {
            $ipAddress = getenv('HTTP_FORWARDED_FOR');
        }
        else if (getenv('HTTP_FORWARDED')) {
            $ipAddress = getenv('HTTP_FORWARDED');
        }
        else if (getenv('REMOTE_ADDR')) {
            $ipAddress = getenv('REMOTE_ADDR');
        }

        return $ipAddress;
    }

}