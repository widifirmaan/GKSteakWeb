<?php

global $wp_version, $wpdb; // phpcs:ignore
if (!defined('SG_ENV_WORDPRESS')) {
    define('SG_ENV_WORDPRESS', 'Wordpress');
}
define('SG_ENV_MAGENTO', 'Magento');
define('SG_ENV_VERSION', $wp_version); // phpcs:ignore
if (!defined('SG_ENV_ADAPTER')) {
    define('SG_ENV_ADAPTER', SG_ENV_WORDPRESS);
}
if (!defined('SG_ENV_DB_PREFIX')) {
    define('SG_ENV_DB_PREFIX', $wpdb->prefix);
}
require_once(dirname(__FILE__) . '/config.php');

define('SG_ENV_CORE_TABLE', SG_WORDPRESS_CORE_TABLE);
//Database
if (!defined('SG_DB_ADAPTER')) {
    define('SG_DB_ADAPTER', SG_ENV_ADAPTER);
}
if (!defined('SG_DB_NAME')) {
    define('SG_DB_NAME', $wpdb->dbname);
}
if (!defined('SG_BACKUP_DATABASE_EXCLUDE')) {
    define('SG_BACKUP_DATABASE_EXCLUDE', SG_ACTION_TABLE_NAME . ',' . SG_CONFIG_TABLE_NAME . ',' . SG_SCHEDULE_TABLE_NAME);
}

//Templates
define('SG_TEMPLATES_PATH', realpath(SG_APP_PATH . '../public/templates') . '/');

//Mail
define('SG_MAIL_TEMPLATES_PATH', realpath(SG_APP_PATH . '../public/templates/mails') . '/');
define('SG_MAIL_BACKUP_TEMPLATE', 'mail_backup.php');
define('SG_MAIL_RESTORE_TEMPLATE', 'mail_restore.php');
define('SG_MAIL_UPLOAD_TEMPLATE', 'mail_upload.php');

//Notice
define('SG_NOTICE_TEMPLATES_PATH', realpath(SG_APP_PATH . '../public/templates/notices') . '/');

//Htaccess
define('SG_HTACCESS_TEMPLATES_PATH', realpath(SG_APP_PATH . '../public/templates/htaccess') . '/');

//BackupGuard SDK
define('SG_BACKUPGUARD_CLIENT_ID', 'wordpress');
define('SG_BACKUPGUARD_CLIENT_SECRET', 'AAPQEgsyQrt6wqDBk7fpa24NP6W43evtayxXmUqS');

define('SG_BACKUPGUARD_UPLOAD_CLIENT_ID', 'backupguard');
define('SG_BACKUPGUARD_UPLOAD_CLIENT_SECRET', 'e9503d56b06b95241abf68eaa0d13194aae9503e');

define('SG_BACKUPGUARD_UPLOAD_SCOPE', 'create_backups');

//Backup
$wpContent = basename(WP_CONTENT_DIR);
$wpPlugins = basename(WP_PLUGIN_DIR);
$wpThemes  = basename(get_theme_root());

$uploadDir = wp_upload_dir(); // phpcs:ignore
$wpUploads = basename($uploadDir['basedir']); // phpcs:ignore

$dbCharset = 'utf8';
if (@constant("DB_CHARSET")) {
    $dbCharset = DB_CHARSET;
}

//Define same constants in magento config file
define('SG_UPLOAD_PATH', $uploadDir['basedir']);
define('SG_UPLOAD_URL', $uploadDir['baseurl']);
if (!defined('SG_SITE_URL')) {
    define('SG_SITE_URL', get_site_url());
}
define('SG_HOME_URL', get_home_url());
define('SG_DB_CHARSET', $dbCharset);
if (!defined('SG_MYSQL_VERSION')) {
    define('SG_MYSQL_VERSION', $wpdb->db_version());
}
$type = "standard";

if (is_multisite()) {
    $type = "multisite";
}

define('SG_SITE_TYPE', $type);

if (!defined('SG_PING_FILE_PATH')) {
    define('SG_PING_FILE_PATH', $uploadDir['basedir'] . '/backup-guard/ping.json');
}
//Symlink download
define('SG_SYMLINK_PATH', $uploadDir['basedir'] . '/sg_symlinks/');
define('SG_SYMLINK_URL', $uploadDir['baseurl'] . '/sg_symlinks/');

if (!defined('SG_APP_ROOT_DIRECTORY')) {
    define('SG_APP_ROOT_DIRECTORY', realpath(dirname(WP_CONTENT_DIR) . "/")); //Wordpress Define
}

$sgBackupFilePathsExclude = array(
    $wpContent . '/' . $wpPlugins . '/backup/',
    $wpContent . '/' . $wpPlugins . '/backup-guard-pro/',
    $wpContent . '/' . $wpPlugins . '/backup-guard-silver/',
    $wpContent . '/' . $wpPlugins . '/backup-guard-gold/',
    $wpContent . '/' . $wpPlugins . '/backup-guard-platinum/',
    $wpContent . '/' . $wpUploads . '/backup-guard/',
    $wpContent . '/' . $wpUploads . '/sg_symlinks/',
    $wpContent . '/ai1wm-backups/',
    $wpContent . '/aiowps_backups/',
    $wpContent . '/Dropbox_Backup/',
    $wpContent . '/updraft/',
    $wpContent . '/upsupsystic/',
    $wpContent . '/wpbackitup_backups/',
    $wpContent . '/wpbackitup_restore/',
    $wpContent . '/backups/',
    $wpContent . '/cache/',
    $wpContent . '/' . $wpUploads . '/wp-clone/',
    $wpContent . '/' . $wpUploads . '/wp-staging/',
    $wpContent . '/' . $wpUploads . '/wp-migrate-db/',
    $wpContent . '/' . $wpUploads . '/db-backup/',
    $wpContent . '/' . $wpPlugins . '/wordpress-move/backup/',
    $wpContent . '/as3b_backups/',
    $wpContent . '/' . $wpUploads . '/backupbuddy_backups/',
    $wpContent . '/backups-dup-pro/',
    $wpContent . '/managewp/backups/',
    $wpContent . '/' . $wpUploads . '/backupbuddy_temp/',
    $wpContent . '/' . $wpUploads . '/pb_backupbuddy/',
    $wpContent . '/' . $wpUploads . '/snapshots/',
    $wpContent . '/debug.log',
    $wpContent . '/backup-db/'
);

define('SG_BACKUP_FILE_PATHS_EXCLUDE', implode(',', $sgBackupFilePathsExclude));
if (!defined('SG_BACKUP_DIRECTORY')) {
    define('SG_BACKUP_DIRECTORY', $uploadDir['basedir'] . '/backup-guard/'); //backups will be stored here
}
define('SG_BACKUP_DIRECTORY_URL', SG_UPLOAD_URL . '/backup-guard/');

//Storage
define('SG_STORAGE_UPLOAD_CRON', '');

define('SG_BACKUP_FILE_PATHS', $wpContent . ',' . $wpContent . '/' . $wpPlugins . ',' . $wpContent . '/' . $wpThemes . ',' . $wpContent . '/' . $wpUploads);

if (!defined('SG_WP_OPTIONS_MIGRATABLE_VALUES')) {
    define('SG_WP_OPTIONS_MIGRATABLE_VALUES', 'user_roles');
}
if (!defined('SG_WP_USERMETA_MIGRATABLE_VALUES')) {
    define('SG_WP_USERMETA_MIGRATABLE_VALUES', 'capabilities,user_level,dashboard_quick_press_last_post_id,user-settings,user-settings-time');
}
if (!defined('SG_MISC_MIGRATABLE_TABLES')) {
    define('SG_MISC_MIGRATABLE_TABLES', SG_ENV_DB_PREFIX . 'options,' . SG_ENV_DB_PREFIX . 'usermeta');
}
if (!defined('SG_MULTISITE_TABLES_TO_MIGRATE')) {
    define('SG_MULTISITE_TABLES_TO_MIGRATE', SG_ENV_DB_PREFIX . 'blogs,' . SG_ENV_DB_PREFIX . 'site');
}
if (!defined('SG_SUBDOMAIN_INSTALL')) {
    define('SG_SUBDOMAIN_INSTALL', defined('SUBDOMAIN_INSTALL') ? SUBDOMAIN_INSTALL : false);
}

define('SG_BACKUP_PRODUCTS_URL', 'https://backup-guard.com/admin/products/view');

define('SG_BACKUP_GUARD_SECURITY_EXTENSION', 'backup-guard-security');
