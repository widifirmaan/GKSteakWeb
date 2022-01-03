<?php

require_once(dirname(__FILE__) . '/config.php');

//Plugin's directory name
if (!defined('SG_PLUGIN_NAME')) {
    define('SG_PLUGIN_NAME', basename(dirname(SG_PUBLIC_PATH)));
}

//Urls
if (!defined('SG_PUBLIC_URL')) {
    define('SG_PUBLIC_URL', plugins_url() . '/' . SG_PLUGIN_NAME . '/public/');
}
define('SG_PUBLIC_AJAX_URL', SG_PUBLIC_URL . 'ajax/');
define('SG_PUBLIC_BACKUPS_URL', network_admin_url('admin.php?page=backup_guard_backups'));
define('SG_PUBLIC_CLOUD_URL', network_admin_url('admin.php?page=backup_guard_cloud'));
define('SG_BACKUP_GUARD_REVIEW_URL', 'https://wordpress.org/support/view/plugin-reviews/backup?filter=5');
define('SG_IMAGE_URL', SG_PUBLIC_URL . 'img/');

//BackupGuard Site URL
if (!defined('SG_BACKUP_SITE_URL')) {
    define('SG_BACKUP_SITE_URL', 'https://backup-guard.com/products/backup-wordpress');
}
define('SG_BACKUP_UPGRADE_URL', 'https://backup-guard.com/products/backup-wordpress/0');

define('SG_BACKUP_SITE_PRICING_URL', 'https://backup-guard.com/products/backup-wordpress#pricing');

define('SG_BACKUP_ADMIN_LOGIN_URL', 'https://backup-guard.com/admin');

// banner URLS
define('SG_BACKUP_KNOWLEDGE_BASE_URL', 'https://help.backup-guard.com/en/');
define('SG_BACKUP_DEMO_URL', 'https://backup-guard.com/wordpress/wp-login.php');
define('SG_BACKUP_FAQ_URL', 'https://backup-guard.com/products/backup-wordpress/faq');
define('SG_BACKUP_CONTACT_US_URL', 'https://wordpress.org/support/plugin/backup/');
