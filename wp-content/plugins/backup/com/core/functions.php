<?php

function backupGuardGetSiteUrl()
{
    if (SG_ENV_ADAPTER == SG_ENV_WORDPRESS) {
        return get_site_url();
    } else {
        return sprintf(
            "%s://%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'],
            $_SERVER['REQUEST_URI']
        );
    }
}

function backupGuardGetCapabilities()
{
    switch (SG_PRODUCT_IDENTIFIER) {
        case 'backup-guard-en':
        case 'backup-guard-wp-platinum':
        case 'backup-guard-en-regular':
        case 'backup-guard-en-extended':
            return BACKUP_GUARD_CAPABILITIES_PLATINUM;
        case 'backup-guard-wp-gold':
            return BACKUP_GUARD_CAPABILITIES_GOLD;
        case 'backup-guard-wp-silver':
            return BACKUP_GUARD_CAPABILITIES_SILVER;
        case 'backup-guard-wp-free':
            return BACKUP_GUARD_CAPABILITIES_FREE;
    }
}

function convertToReadableSize($size)
{
    if (!$size) {
        return '0';
    }

    $base   = log($size) / log(1000);
    $suffix = array("", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
    $fBase  = floor($base);

    return round(pow(1000, $base - floor($base)), 1) . $suffix[$fBase];
}



function backupGuardgetSealPopup()
{
    $currentDate               = time();
    $sgShouldShowPopup         = SGConfig::get('SG_SHOULD_SHOW_POPUP') == null ? true : SGConfig::get('SG_SHOULD_SHOW_POPUP');
    $sgPluginInstallUpdateDate = SGConfig::get('SG_PLUGIN_INSTALL_UPDATE_DATE') == null ? time() : SGConfig::get('SG_PLUGIN_INSTALL_UPDATE_DATE');

    // check ig plugin is active for free days show poup
    if (($currentDate - $sgPluginInstallUpdateDate >= SG_PLUGIN_ACTIVE_INTERVAL) && $sgShouldShowPopup) {
        ?>
        <script>
            window.SGPMPopupLoader = window.SGPMPopupLoader || {
                ids: [], popups: {}, call: function (w, d, s, l, id) {
                    w['sgp'] = w['sgp'] || function () {
                        (w['sgp'].q = w['sgp'].q || []).push(arguments[0]);
                    };
                    var sg1 = d.createElement(s), sg0 = d.getElementsByTagName(s)[0];
                    if (SGPMPopupLoader && SGPMPopupLoader.ids && SGPMPopupLoader.ids.length > 0) {
                        SGPMPopupLoader.ids.push(id);
                        return;
                    }
                    SGPMPopupLoader.ids.push(id);
                    sg1.onload = function () {
                        SGPMPopup.openSGPMPopup();
                    };
                    sg1.async = true;
                    sg1.src = l;
                    sg0.parentNode.insertBefore(sg1, sg0);
                    return {};
                }
            };
            SGPMPopupLoader.call(window, document, 'script', 'https://popupmaker.com/assets/lib/SGPMPopup.min.js', '7c685e17');
        </script>
        <?php
        SGConfig::set('SG_SHOULD_SHOW_POPUP', 0);
    }

    return;
}

function backupGuardConvertDateTimezone($dateTime, $currentTimezone = false, $dateFormat = "Y-m-d H:i:s", $timeZone = SG_DEFAULT_TIMEZONE)
{
    if ($currentTimezone) {
        $getCurrentTimezone = SGConfig::get('SG_TIMEZONE', true);

        if ($getCurrentTimezone) {
            $timeZone = $getCurrentTimezone;
        }
    }

    $newDateTime = new DateTime($dateTime);
    $newDateTime->setTimezone(new DateTimeZone($timeZone));

    return $newDateTime->format($dateFormat);
}

function backupGuardCeliDateTimezone($time)
{

    $currentDateTime = date('Y-m-d H', $time);

    $celiCurrentDateTime = $currentDateTime . ':00:00';

    return date('Y-m-d H:i:s', strtotime($celiCurrentDateTime));
}

function backupGuardConvertDateTimezoneToUTC($dateTime, $timezone = 'UTC')
{
    $getCurrentTimezone = SGConfig::get('SG_TIMEZONE', true);
    if ($getCurrentTimezone) {
        $timezone = $getCurrentTimezone;
    }

    $newDateTime = new DateTime($dateTime, new DateTimeZone($timezone));
    $newDateTime->setTimezone(new DateTimeZone("UTC"));
    $dateTimeUTC = $newDateTime->format("Y-m-d H:i:s");

    return $dateTimeUTC;
}

function backupGuardRemoveSlashes($value)
{
    if (SG_ENV_ADAPTER == SG_ENV_WORDPRESS) {
        return wp_unslash($value);
    } else {
        if (is_array($value)) {
            return array_map('stripslashes', $value);
        }

        return stripslashes($value);
    }
}

function backupGuardSanitizeTextField($value)
{
    if (SG_ENV_ADAPTER == SG_ENV_WORDPRESS) {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }

        return sanitize_text_field($value);
    } else {
        if (is_array($value)) {
            return array_map('strip_tags', $value);
        }

        return strip_tags($value);
    }
}

function backupGuardIsMultisite()
{
    if (SG_ENV_ADAPTER == SG_ENV_WORDPRESS) {
        return defined('BG_IS_MULTISITE') ? BG_IS_MULTISITE : is_multisite();
    } else {
        return false;
    }
}

function backupGuardGetBanner($env, $type = "plugin", $userType = null)
{
    include_once SG_LIB_PATH . 'BackupGuard/Client.php';
    $client = new BackupGuard\Client();

    return $client->getBanner(strtolower($env), $type, $userType);
}

function backupGuardGetFilenameOptions($options)
{
    $selectedPaths  = explode(',', $options['SG_BACKUP_FILE_PATHS']);
    $pathsToExclude = explode(',', $options['SG_BACKUP_FILE_PATHS_EXCLUDE']);

    $opt = '';

    if (SG_ENV_ADAPTER == SG_ENV_WORDPRESS) {
        $opt .= 'opt(';

        if ($options['SG_BACKUP_TYPE'] == SG_BACKUP_TYPE_CUSTOM) {
            if ($options['SG_ACTION_BACKUP_DATABASE_AVAILABLE']) {
                $opt .= 'db_';
            }

            if ($options['SG_ACTION_BACKUP_FILES_AVAILABLE']) {
                if (in_array('wp-content', $selectedPaths)) {
                    $opt .= 'wpc_';
                }
                if (!in_array('wp-content/plugins', $pathsToExclude)) {
                    $opt .= 'plg_';
                }
                if (!in_array('wp-content/themes', $pathsToExclude)) {
                    $opt .= 'thm_';
                }
                if (!in_array('wp-content/uploads', $pathsToExclude)) {
                    $opt .= 'upl_';
                }
            }
        } else {
            $opt .= 'full';
        }

        $opt = trim($opt, "_");
        $opt .= ')_';
    }

    return $opt;
}

function backupGuardGenerateToken()
{
    return md5(time());
}

// Parse a URL and return its components
function backupGuardParseUrl($url)
{
    $urlComponents = parse_url($url);
    $domain        = $urlComponents['host'];
    $port          = '';

    if (isset($urlComponents['port']) && strlen($urlComponents['port'])) {
        $port = ":" . $urlComponents['port'];
    }

    $domain = preg_replace("/(www|\dww|w\dw|ww\d)\./", "", $domain);

    $path = "";
    if (isset($urlComponents['path'])) {
        $path = $urlComponents['path'];
    }

    return $domain . $port . $path;
}

function backupGuardIsReloadEnabled()
{
    // Check if reloads option is turned on
    return SGConfig::get('SG_BACKUP_WITH_RELOADINGS') ? true : false;
}

function backupGuardGetBackupOptions($options)
{
    $backupOptions = array(
        'SG_BACKUP_UPLOAD_TO_STORAGES' => '',
        'SG_BACKUP_FILE_PATHS_EXCLUDE' => '',
        'SG_BACKUP_FILE_PATHS' => ''
    );

    if (isset($options['sg-custom-backup-name']) && $options['sg-custom-backup-name']) {
        SGConfig::set("SG_CUSTOM_BACKUP_NAME", $options['sg-custom-backup-name']);
    } else {
        SGConfig::set("SG_CUSTOM_BACKUP_NAME", '');
    }

    //If background mode
    $isBackgroundMode = !empty($options['backgroundMode']) ? 1 : 0;

    if ($isBackgroundMode) {
        $backupOptions['SG_BACKUP_IN_BACKGROUND_MODE'] = $isBackgroundMode;
    }

    //If cloud backup
    if (!empty($options['backupCloud']) && count($options['backupStorages'])) {
        $clouds                                        = $options['backupStorages'];
        $backupOptions['SG_BACKUP_UPLOAD_TO_STORAGES'] = implode(',', $clouds);
    }

    $backupOptions['SG_BACKUP_TYPE'] = $options['backupType'];

    if ($options['backupType'] == SG_BACKUP_TYPE_FULL) {
        $backupOptions['SG_ACTION_BACKUP_DATABASE_AVAILABLE'] = 1;
        $backupOptions['SG_ACTION_BACKUP_FILES_AVAILABLE']    = 1;
        $backupOptions['SG_BACKUP_FILE_PATHS_EXCLUDE']        = SG_BACKUP_FILE_PATHS_EXCLUDE;
        $backupOptions['SG_BACKUP_FILE_PATHS']                = 'wp-content';
    } else if ($options['backupType'] == SG_BACKUP_TYPE_CUSTOM) {
        //If database backup
        $isDatabaseBackup                                     = !empty($options['backupDatabase']) ? 1 : 0;
        $backupOptions['SG_ACTION_BACKUP_DATABASE_AVAILABLE'] = $isDatabaseBackup;

        //If db backup
        if ($options['backupDBType']) {
            $tablesToBackup                              = implode(',', $options['table']);
            $backupOptions['SG_BACKUP_TABLES_TO_BACKUP'] = $tablesToBackup;
        }

        //If files backup
        if (!empty($options['backupFiles']) && count($options['directory'])) {
            $backupFiles    = explode(',', SG_BACKUP_FILE_PATHS);
            $filesToExclude = @array_diff($backupFiles, $options['directory']);

            if (in_array('wp-content', $options['directory'])) {
                $options['directory'] = array('wp-content');
            } else {
                $filesToExclude = array_diff($filesToExclude, array('wp-content'));
            }

            $filesToExclude = implode(',', $filesToExclude);
            if (strlen($filesToExclude)) {
                $filesToExclude = ',' . $filesToExclude;
            }

            $backupOptions['SG_BACKUP_FILE_PATHS_EXCLUDE']     = SG_BACKUP_FILE_PATHS_EXCLUDE . $filesToExclude;
            $options['directory']                              = backupGuardSanitizeTextField($options['directory']);
            $backupOptions['SG_BACKUP_FILE_PATHS']             = implode(',', $options['directory']);
            $backupOptions['SG_ACTION_BACKUP_FILES_AVAILABLE'] = 1;
        } else {
            $backupOptions['SG_ACTION_BACKUP_FILES_AVAILABLE'] = 0;
            $backupOptions['SG_BACKUP_FILE_PATHS']             = 0;
        }
    }

    return $backupOptions;
}

function backupGuardLoadStateData()
{
    if (file_exists(SG_BACKUP_DIRECTORY . SG_STATE_FILE_NAME)) {
        $sgState   = new SGState();
        $stateFile = file_get_contents(SG_BACKUP_DIRECTORY . SG_STATE_FILE_NAME);
        $sgState   = $sgState->factory($stateFile);

        return $sgState;
    }

    return false;
}

function backupGuardValidateApiCall($token)
{
    if (!strlen($token)) {
        exit();
    }

    $statePath = SG_BACKUP_DIRECTORY . SG_STATE_FILE_NAME;

    if (!file_exists($statePath)) {
        exit();
    }

    $state      = file_get_contents($statePath);
    $state      = json_decode($state, true);
    $stateToken = $state['token'];

    if ($stateToken != $token) {
        exit();
    }

    return true;
}

function backupGuardScanBackupsDirectory($path)
{
    $backups       = scandir($path);
    $backupFolders = array();
    foreach ($backups as $backup) {
        if ($backup == "." || $backup == "..") {
            continue;
        }

        if (is_dir($path . $backup)) {
            $backupFolders[$backup] = filemtime($path . $backup);
        }
    }
    // Sort(from low to high) backups by creation date
    asort($backupFolders);

    return $backupFolders;
}

function backupGuardSymlinksCleanup($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object == "." || $object == "..") {
                continue;
            }

            if (filetype($dir . $object) != "dir") {
                @unlink($dir . $object);
            } else {
                backupGuardSymlinksCleanup($dir . $object . '/');
                @rmdir($dir . $object);
            }
        }
    } else if (file_exists($dir)) {
        @unlink($dir);
    }

    return;
}

function backupGuardRealFilesize($filename)
{
    $fp     = fopen($filename, 'r');
    $return = false;
    if (is_resource($fp)) {
        if (PHP_INT_SIZE < 8) { // 32 bit
            if (0 === fseek($fp, 0, SEEK_END)) {
                $return = 0.0;
                $step   = 0x7FFFFFFF;
                while ($step > 0) {
                    if (0 === fseek($fp, -$step, SEEK_CUR)) {
                        $return += floatval($step);
                    } else {
                        $step >>= 1;
                    }
                }
            }
        } else if (0 === fseek($fp, 0, SEEK_END)) { // 64 bit
            $return = ftell($fp);
        }
    }

    return $return;
}

function backupGuardFormattedDuration($startTs, $endTs)
{
    $result  = '';
    $seconds = $endTs - $startTs;

    if ($seconds < 1) {
        return '0 seconds';
    }

    $days = intval(intval($seconds) / (3600 * 24));
    if ($days > 0) {
        $result .= $days . (($days > 1) ? ' days ' : ' day ');
    }

    $hours = (intval($seconds) / 3600) % 24;
    if ($hours > 0) {
        $result .= $hours . (($hours > 1) ? ' hours ' : ' hour ');
    }

    $minutes = (intval($seconds) / 60) % 60;
    if ($minutes > 0) {
        $result .= $minutes . (($minutes > 1) ? ' minutes ' : ' minute ');
    }

    $seconds = intval($seconds) % 60;
    if ($seconds > 0) {
        $result .= $seconds . (($seconds > 1) ? ' seconds' : ' second');
    }

    return $result;
}

function backupGuardDeleteDirectory($dirName)
{
    $dirHandle = null;
    if (is_dir($dirName)) {
        $dirHandle = opendir($dirName);
    }

    if (!$dirHandle) {
        return false;
    }

    while ($file = readdir($dirHandle)) {
        if ($file != "." && $file != "..") {
            if (!is_dir($dirName . "/" . $file)) {
                @unlink($dirName . "/" . $file);
            } else {
                backupGuardDeleteDirectory($dirName . '/' . $file);
            }
        }
    }

    closedir($dirHandle);

    return @rmdir($dirName);
}

function backupGuardMakeSymlinkFolder($filename)
{
    $filename = backupGuardRemoveSlashes($filename);

    $downloaddir = SG_SYMLINK_PATH;

    if (!file_exists($downloaddir)) {
        mkdir($downloaddir, 0777);
    }

    $letters = 'abcdefghijklmnopqrstuvwxyz';
    srand((double) microtime() * 1000000);
    $string = '';

    for ($i = 1; $i <= rand(4, 12); $i++) {
        $q      = rand(1, 24);
        $string = $string . $letters[$q];
    }

    $handle = opendir($downloaddir);
    while ($dir = readdir($handle)) {
        if ($dir == "." || $dir == "..") {
            continue;
        }

        if (is_dir($downloaddir . $dir)) {
            @unlink($downloaddir . $dir . "/" . $filename);
            @rmdir($downloaddir . $dir);
        }
    }

    closedir($handle);
    mkdir($downloaddir . $string, 0777);

    return $string;
}

function backupGuardDownloadFile($file, $type = 'application/octet-stream')
{
    if (ob_get_level()) {
        ob_end_clean();
    }

    $file = backupGuardRemoveSlashes($file);
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $type);
        header('Content-Disposition: attachment; filename="' . basename($file) . '";');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }

    exit;
}

function backupGuardDownloadViaPhp($backupName, $fileName)
{
    $str = backupGuardMakeSymlinkFolder($fileName);
    @copy(SG_BACKUP_DIRECTORY . $backupName . '/' . $fileName, SG_SYMLINK_PATH . $str . '/' . $fileName);

    if (file_exists(SG_SYMLINK_PATH . $str . '/' . $fileName)) {
        $remoteGet = wp_remote_get(SG_SYMLINK_URL . $str . '/' . $fileName);
        if (!is_wp_error($remoteGet)) {
            $content = wp_remote_retrieve_body($remoteGet);
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: private', false);
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $fileName . ';');
            header('Content-Transfer-Encoding: binary');
            echo $content;
            exit;
        }
    }
}

function backupGuardDownloadFileViaFunction($safeDir, $fileName, $type)
{
    $downloadDir = SG_SYMLINK_PATH;
    $downloadURL = SG_SYMLINK_URL;

    $safeDir = backupGuardRemoveSlashes($safeDir);
    $string  = backupGuardMakeSymlinkFolder($fileName);

    $target = $safeDir . $fileName;
    $link   = $downloadDir . $string . '/' . $fileName;

    if ($type == BACKUP_GUARD_DOWNLOAD_MODE_LINK) {
        $res  = @link($target, $link);
        $name = 'link';
    } else {
        $res  = @symlink($target, $link);
        $name = 'symlink';
    }

    if ($res) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Content-Transfer-Encoding: binary');
        header("Location: " . $downloadURL . $string . "/" . $fileName);
    } else {
        wp_die(_backupGuardT(ucfirst($name) . " / shortcut creation failed! Seems your server configurations don't allow $name creation, so we're unable to provide you the direct download url. You can download your backup using any FTP client. All backups and related stuff we locate '/wp-content/uploads/backup-guard' directory. If you need this functionality, you should check out your server configurations and make sure you don't have any limitation related to $name creation.", true));
    }
    exit;
}

function backupGuardDownloadFileSymlink($safedir, $filename)
{
    $downloaddir = SG_SYMLINK_PATH;
    $downloadURL = SG_SYMLINK_URL;

    $safedir = backupGuardRemoveSlashes($safedir);
    $string  = backupGuardMakeSymlinkFolder($filename);

    $res = @symlink($safedir . $filename, $downloaddir . $string . "/" . $filename);
    if ($res) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header("Location: " . $downloadURL . $string . "/" . $filename);
    } else {
        wp_die(_backupGuardT("Symlink / shortcut creation failed! Seems your server configurations don't allow symlink creation, so we're unable to provide you the direct download url. You can download your backup using any FTP client. All backups and related stuff we locate '/wp-content/uploads/backup-guard' directory. If you need this functionality, you should check out your server configurations and make sure you don't have any limitation related to symlink creation.", true));
    }
    exit;
}

function backupGuardDownloadFileLink($safedir, $filename)
{
    $downloaddir = SG_SYMLINK_PATH;
    $downloadURL = SG_SYMLINK_URL;

    $safedir = backupGuardRemoveSlashes($safedir);
    $string  = backupGuardMakeSymlinkFolder($filename);

    $res = @link($safedir . $filename, $downloaddir . $string . "/" . $filename);
    if ($res) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header("Location: " . $downloadURL . $string . "/" . $filename);
    } else {
        wp_die(_backupGuardT("Link / shortcut creation failed! Seems your server configurations don't allow link creation, so we're unable to provide you the direct download url. You can download your backup using any FTP client. All backups and related stuff we locate '/wp-content/uploads/backup-guard' directory. If you need this functionality, you should check out your server configurations and make sure you don't have any limitation related to link creation.", true));
    }
    exit;
}

function backupGuardGetCurrentUrlScheme()
{
    return (is_ssl()) ? 'https' : 'http';
}

function backupGuardGetCurrentUrlHost()
{
    if (defined('SG_SITE_URL')) {
        $url = SG_SITE_URL;
    } else {
        $url = get_site_url();
    }

    $parseUrl = parse_url($url);

    return $parseUrl['host'];
}

function backupGuardValidateLicense()
{
    $pluginCapabilities = backupGuardGetCapabilities();
    if ($pluginCapabilities == BACKUP_GUARD_CAPABILITIES_FREE) {
        return true;
    }

    //only check once per day
    $ts = (int) SGConfig::get('SG_LICENSE_CHECK_TS');
    if (time() - $ts < SG_LICENSE_CHECK_TIMEOUT) {
        return true;
    }

    include_once SG_LIB_PATH . 'SGAuthClient.php';

    $url = site_url();

    $auth = SGAuthClient::getInstance();
    $res  = $auth->validateUrl($url);

    if ($res === -1) { //login is required
        backup_guard_login_page();

        return false;
    } else if ($res === false) { //invalid license
        backup_guard_link_license_page();

        return false;
    } else {
        SGConfig::set('SG_LICENSE_CHECK_TS', time(), true);
        SGConfig::set('SG_LICENSE_KEY', $res, true);
    }

    return true;
}

//returns true if string $haystack ends with string $needle or $needle is an empty string
function backupGuardStringEndsWith($haystack, $needle)
{
    $length = strlen($needle);

    return $length === 0 ||
        (substr($haystack, -$length) === $needle);
}

//returns true if string $haystack starts with string $needle
function backupGuardStringStartsWith($haystack, $needle)
{
    $length = strlen($needle);

    return (substr($haystack, 0, $length) === $needle);
}

function backupGuardGetDbTables()
{
    $sgdb                  = SGDatabase::getInstance();
    $tables                = $sgdb->query("SHOW TABLES");
    $tablesKey             = 'Tables_in_' . SG_DB_NAME;
    $tableNames            = array();
    $customTablesToExclude = str_replace(' ', '', SGConfig::get('SG_TABLES_TO_EXCLUDE'));
    $tablesToExclude       = explode(',', $customTablesToExclude);
    foreach ($tables as $table) :
        $tableName = $table[$tablesKey];
        if ($tableName != SG_ACTION_TABLE_NAME && $tableName != SG_CONFIG_TABLE_NAME && $tableName != SG_SCHEDULE_TABLE_NAME) {
            array_push(
                $tableNames,
                array('name' => $tableName, 'current' => backupGuardStringStartsWith($tableName, SG_ENV_DB_PREFIX) ? 'true' : 'false', 'disabled' => in_array($tableName, $tablesToExclude) ? 'disabled' : '')
            );
        }
    endforeach;
    usort(
        $tableNames,
        function ($name1, $name2) {
            if (backupGuardStringStartsWith($name1['name'], SG_ENV_DB_PREFIX)) {
                if (backupGuardStringStartsWith($name2['name'], SG_ENV_DB_PREFIX)) {
                    return 0;
                }

                return -1;
            }

            return 1;
        }
    );

    return $tableNames;
}

function backupGuardGetBackupTablesHTML($defaultChecked = false)
{
    $tables = backupGuardGetDbTables();
    ?>

    <div class="checkbox">
        <label for="custom-backupdb-chbx">
            <input type="checkbox" class="sg-custom-option" name="backupDatabase"
                   id="custom-backupdb-chbx" <?php echo $defaultChecked ? 'checked' : '' ?>>
            <span class="sg-checkbox-label-text"><?php _backupGuardT('Backup database'); ?></span>
        </label>
        <div class="col-md-12 sg-checkbox sg-backup-db-options">
            <div class="checkbox">
                <label for="custombackupdbfull-radio" class="sg-backup-db-mode"
                       title="<?php _backupGuardT('Backup all tables found in the database') ?>">
                    <input type="radio" name="backupDBType" id="custombackupdbfull-radio" value="0" checked>
                    <?php _backupGuardT('Full'); ?>
                </label>
                <label for="custombackupdbcurent-radio" class="sg-backup-db-mode"
                       title="<?php echo _backupGuardT('Backup tables related to the current WordPress installation. Only tables with', true) . ' ' . SG_ENV_DB_PREFIX . ' ' . _backupGuardT('will be backed up', true) ?>">
                    <input type="radio" name="backupDBType" id="custombackupdbcurent-radio" value="1">
                    <?php _backupGuardT('Only WordPress'); ?>
                </label>
                <label for="custombackupdbcustom-radio" class="sg-backup-db-mode"
                       title="<?php _backupGuardT('Select tables you want to include in your backup') ?>">
                    <input type="radio" name="backupDBType" id="custombackupdbcustom-radio" value="2">
                    <?php _backupGuardT('Custom'); ?>
                </label>
                <!--Tables-->
                <div class="col-md-12 sg-custom-backup-tables">
                    <?php foreach ($tables as $table) : ?>
                        <div class="checkbox">
                            <label for="<?php echo $table['name'] ?>">
                                <input type="checkbox" name="table[]"
                                       current="<?php echo $table['current'] ?>" <?php echo $table['disabled'] ?>
                                       id="<?php echo $table['name'] ?>" value="<?php echo $table['name']; ?>">
                                <span class="sg-checkbox-label-text"><?php echo basename($table['name']); ?></span>
                                <?php if ($table['disabled']) { ?>
                                    <span class="sg-disableText"><?php _backupGuardT('(excluded from settings)') ?></span>
                                <?php } ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    <?php
}

function backupGuardIsAccountGold()
{
    return strpos("gold", SG_PRODUCT_IDENTIFIER) !== false;
}

function backupGuardGetProductName()
{
    $name = '';
    switch (SG_PRODUCT_IDENTIFIER) {
        case 'backup-guard-wp-silver':
            $name = 'Silver';
            break;
        case 'backup-guard-wp-platinum':
            $name = 'Platinum';
            break;
        case 'backup-guard-en':
        case 'backup-guard-en-regular':
            $name = 'Regular';
            break;
        case 'backup-guard-en-extended':
            $name = 'Extended';
            break;
        case 'backup-guard-wp-gold':
            $name = 'Gold';
            break;
        case 'backup-guard-wp-free':
            $name = 'Free';
            break;
    }

    return $name;
}

function backupGuardGetFileSelectiveRestore()
{
    ?>
    <div class="col-md-12 sg-checkbox sg-restore-files-options">
        <div class="checkbox">
            <label for="restorefilesfull-radio" class="sg-restore-files-mode">
                <input type="radio" name="restoreFilesType" checked id="restorefilesfull-radio" value="0">
                <?php _backupGuardT('Full'); ?>
            </label>

            <label for="restorefilescustom-radio" class="sg-restore-files-mode">
                <input type="radio" name="restoreFilesType" id="restorefilescustom-radio" value="1">
                <?php _backupGuardT('Custom'); ?>
            </label>
            <!--Files-->
            <div class="col-md-12 sg-file-selective-restore">
                <div id="fileSystemTreeContainer"></div>
            </div>
        </div>
    </div>
    <?php
}

function checkAllMissedTables()
{
    $sgdb      = SGDatabase::getInstance();
    $allTables = array(SG_CONFIG_TABLE_NAME, SG_SCHEDULE_TABLE_NAME, SG_ACTION_TABLE_NAME);
    $status    = true;

    foreach ($allTables as $table) {
        $query = $sgdb->query(
            "SELECT count(*) as isExists
			FROM information_schema.TABLES
			WHERE (TABLE_SCHEMA = '" . DB_NAME . "') AND (TABLE_NAME = '$table')"
        );

        if (empty($query[0]['isExists'])) {
            $status = false;
        }
    }

    return $status;
}

function backupGuardIncludeFile($filePath)
{
    if (file_exists($filePath)) {
        include_once $filePath;
    }
}

function getCloudUploadDefaultMaxChunkSize()
{
    $memory     = (int) SGBoot::$memoryLimit;
    $uploadSize = 1;

    if ($memory <= 128) {
        $uploadSize = 4;
    } else if ($memory > 128 && $memory <= 256) {
        $uploadSize = 8;
    } else if ($memory > 256 && $memory <= 512) {
        $uploadSize = 16;
    } else if ($memory > 512) {
        $uploadSize = 32;
    }

    return $uploadSize;
}

function getCloudUploadChunkSize()
{
    $cloudUploadDefaultChunkSize = (int) getCloudUploadDefaultMaxChunkSize();
    $savedCloudUploadChunkSize   = (int) SGConfig::get('SG_BACKUP_CLOUD_UPLOAD_CHUNK_SIZE');

    return ($savedCloudUploadChunkSize ? $savedCloudUploadChunkSize : $cloudUploadDefaultChunkSize);
}

function backupGuardCheckOS()
{
    $os = strtoupper(substr(PHP_OS, 0, 3));

    if ($os === 'WIN') {
        return 'windows';
    } else if ($os === 'LIN') {
        return 'linux';
    }

    return 'other';
}

function backupGuardCheckDownloadMode()
{
    $system  = backupGuardCheckOS();
    $link    = false;
    $symlink = false;

    if (!file_exists(SG_SYMLINK_PATH)) {
        mkdir(SG_SYMLINK_PATH);
    }

    backupGuardRemoveDownloadTmpFiles();

    $testFile = fopen(SG_SYMLINK_PATH . 'test.log', 'w');

    if (!$testFile) {
        return BACKUP_GUARD_DOWNLOAD_MODE_PHP;
    }

    if (function_exists('link')) {
        $link = @link(SG_SYMLINK_PATH . 'test.log', SG_SYMLINK_PATH . 'link.log');
    }

    if (function_exists('symlink')) {
        $symlink = @symlink(SG_SYMLINK_PATH . 'test.log', SG_SYMLINK_PATH . 'symlink.log');
    }

    backupGuardRemoveDownloadTmpFiles();

    if ($system == 'windows') {
        if ($symlink) {
            return BACKUP_GUARD_DOWNLOAD_MODE_SYMLINK;
        }
    } else {
        if ($link) {
            return BACKUP_GUARD_DOWNLOAD_MODE_LINK;
        } elseif ($symlink) {
            return BACKUP_GUARD_DOWNLOAD_MODE_SYMLINK;
        }
    }

    return BACKUP_GUARD_DOWNLOAD_MODE_PHP;
}

function backupGuardRemoveDownloadTmpFiles()
{
    if (file_exists(SG_SYMLINK_PATH . 'test.log')) {
        @unlink(SG_SYMLINK_PATH . 'test.log');
    }

    if (file_exists(SG_SYMLINK_PATH . 'link.log')) {
        @unlink(SG_SYMLINK_PATH . 'link.log');
    }

    if (file_exists(SG_SYMLINK_PATH . 'symlink.log') || is_link(SG_SYMLINK_PATH . 'symlink.log')) {
        @unlink(SG_SYMLINK_PATH . 'symlink.log');
    }
}

function backupGuardMigrateDownloadMode()
{
    $downloadModeRow = SGConfig::get('SG_DOWNLOAD_MODE', true);

    if (!is_null($downloadModeRow)) {
        return true;
    }

    $downloadMode   = BACKUP_GUARD_DOWNLOAD_MODE_PHP;
    $downloadViaPhp = SGConfig::get('SG_DOWNLOAD_VIA_PHP', true);

    if ($downloadViaPhp != BACKUP_GUARD_DOWNLOAD_MODE_PHP) {
        $downloadMode = backupGuardCheckDownloadMode();
    }

    SGConfig::set('SG_DOWNLOAD_MODE', $downloadMode);

    return true;
}

function addLiteSpeedHtaccessModule()
{
    $server = '';
    if (isset($_SERVER['SERVER_SOFTWARE'])) {
        $server = strtolower($_SERVER['SERVER_SOFTWARE']);
    }

    if (strpos($server, 'litespeed') !== false) {
        $htaccessFile    = ABSPATH . '.htaccess';
        $htaccessContent = '';

        if (is_readable($htaccessFile)) {
            $htaccessContent = @file_get_contents($htaccessFile);
            if (!$htaccessContent) {
                $htaccessContent = '';
            }
        }

        if (!$htaccessContent || !preg_match('/noabort/i', $htaccessContent)) {
            $liteSpeedTemplate = file_get_contents(SG_HTACCESS_TEMPLATES_PATH . 'liteSpeed.php');
            $result            = file_put_contents($htaccessFile, "\n" . $liteSpeedTemplate, FILE_APPEND);

            if ($result) {
                return true;
            }
        }
    }

    return false;
}

function removeLiteSpeedHtaccessModule()
{
    $htaccessFile    = ABSPATH . '.htaccess';
    $htaccessContent = file_get_contents($htaccessFile);

    $result = preg_replace('/(# LITESPEED START[\s\S]+?# LITESPEED END)/', '', $htaccessContent);

    if ($result) {
        $change = file_put_contents($htaccessFile, $result);

        if ($change) {
            return true;
        }
    }

    return false;
}

function getAllTimezones()
{
    static $regions = array(
        DateTimeZone::AFRICA,
        DateTimeZone::AMERICA,
        DateTimeZone::ANTARCTICA,
        DateTimeZone::ASIA,
        DateTimeZone::ATLANTIC,
        DateTimeZone::AUSTRALIA,
        DateTimeZone::EUROPE,
        DateTimeZone::INDIAN,
        DateTimeZone::PACIFIC,
    );

    $timezones = array();
    foreach ($regions as $region) {
        $timezones = array_merge($timezones, DateTimeZone::listIdentifiers($region));
    }

    $timezoneOffsets = array();
    foreach ($timezones as $timezone) {
        $tz                         = new DateTimeZone($timezone);
        $timezoneOffsets[$timezone] = $tz->getOffset(new DateTime());
    }

    asort($timezoneOffsets);

    $timezoneList = array();
    foreach ($timezoneOffsets as $timezone => $offset) {
        $offsetPrefix            = $offset < 0 ? '-' : '+';
        $offsetFormatted         = gmdate('H:i', abs($offset));
        $offsetFormattedOnlyHour = gmdate('H', abs($offset));

        $prettyOffset = "UTC${offsetPrefix}${offsetFormatted}";

        $timezoneList[$timezone] = ["(${prettyOffset}) $timezone", "${offsetPrefix}${offsetFormattedOnlyHour}"];
    }

    return $timezoneList;
}

function backupGuardIsMaintenanceMode()
{
    if (file_exists(ABSPATH . '.maintenance')) {
        return true;
    }

    return false;
}

function backupGuardDiskFreeSize($dir)
{
    if (function_exists('disk_free_space')) {
        return convertToReadableSize(@disk_free_space($dir));
    }

    return 0;
}
