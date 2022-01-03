<?php

/**
 * Plugin Name:       Backup
 * Plugin URI:        https://backup-guard.com/products/backup-wordpress
 * Description:       Backup Guard is the most complete site backup and restore plugin. We offer the easiest way to backup, restore or migrate your site. You can backup your files, database or both.
 * Version:           1.6.8.4
 * Author:            BackupGuard
 * Author URI:        https://backup-guard.com/products/backup-wordpress
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (function_exists('activate_backup_guard')) {
	die('Please deactivate any other BackupGuard version before activating this one.');
}

if (!defined('SG_BACKUP_GUARD_VERSION')) {
	define('SG_BACKUP_GUARD_VERSION', '1.6.8.4');
}

if (!defined('SG_BACKUP_GUARD_MAIN_FILE')) {
	define('SG_BACKUP_GUARD_MAIN_FILE', __FILE__);
}

if (!defined('SG_FORCE_DB_TABLES_RESET')) {
	define('SG_FORCE_DB_TABLES_RESET', false);
}

//if this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

require_once(plugin_dir_path(__FILE__).'public/boot.php');
require_once(plugin_dir_path(__FILE__).'BackupGuard.php');
