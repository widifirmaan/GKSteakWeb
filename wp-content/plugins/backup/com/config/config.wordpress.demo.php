<?php

define('SG_USER_MODE', 1);

define('SG_FEATURE_DOWNLOAD_FROM_CLOUD', 1);
define('SG_FEATURE_STORAGE', 1);
define('SG_FEATURE_FTP', 1);
define('SG_FEATURE_AMAZON', 1);
define('SG_FEATURE_DROPBOX', 1);
define('SG_FEATURE_GOOGLE_DRIVE', 1);
define('SG_FEATURE_ONE_DRIVE', 1);
define('SG_FEATURE_P_CLOUD', 1);
define('SG_FEATURE_BOX', 1);
define('SG_FEATURE_SCHEDULE', 1);
define('SG_FEATURE_DELETE_LOCAL_BACKUP_AFTER_UPLOAD', 1);
define('SG_FEATURE_ALERT_BEFORE_UPDATE', 1);
define('SG_FEATURE_NUMBER_OF_BACKUPS_TO_KEEP', 1);
define('SG_FEATURE_CUSTOM_BACKUP_NAME', 1);
define('SG_FEATURE_SUBDIRECTORIES', 1);
define('SG_FEATURE_BACKGROUND_MODE', 1);
define('SG_FEATURE_NOTIFICATIONS', 1);
define('SG_FEATURE_MULTI_SCHEDULE', 1);
define('SG_FEATURE_SHOW_UPGRADE_PAGE', 0);
define('SG_FEATURE_BACKUP_WITH_MIGRATION', 1);
define('SG_FEATURE_NUMBER_OF_ROWS_TO_BACKUP', 1);
define('SG_FEATURE_BACKUP_DELETION_WILL_ALSO_DELETE_FROM_CLOUD', 1);
define('SG_FEATURE_SLECTIVE_RESTORE', 1);
define('SG_FEATURE_HIDE_ADS', 1);

//Storage
define('SG_STORAGE_FTP', 1);
define('SG_STORAGE_DROPBOX', 2);
define('SG_STORAGE_GOOGLE_DRIVE', 3);
define('SG_STORAGE_AMAZON', 4);
define('SG_STORAGE_ONE_DRIVE', 5);
define('SG_STORAGE_P_CLOUD', 7);
define('SG_STORAGE_BOX', 8);

define('SG_STORAGE_GOOGLE_DRIVE_CLIENT_ID', '1030123017859-vfdlqkjhiuuu5n36pbov93v9ruo6jpj5.apps.googleusercontent.com');
define('SG_STORAGE_GOOGLE_DRIVE_SECRET', 'oUcZwC17q5ZSbYahnQkGYpyH');
define('SG_STORAGE_GOOGLE_DRIVE_REDIRECT_URI', 'https://backup-guard.com/gdrive/');
define('SG_STORAGE_DROPBOX_KEY', 'n3yhajm64h88m9t');
define('SG_STORAGE_DROPBOX_SECRET', 's8crjkls7f9wqtd');
define('SG_STORAGE_DROPBOX_CLIENT_ID', 'backup-guard');
define('SG_STORAGE_DROPBOX_REDIRECT_URI', 'https://backup-guard.com/dropbox/');

define('SG_STORAGE_ONE_DRIVE_SECRET', 'tdajnuNEibaaEdCB3OfaXQk');
define('SG_STORAGE_ONE_DRIVE_REDIRECT_URI', "https://backup-guard.com/onedrive/");
define('SG_STORAGE_ONE_DRIVE_CLIENT_ID', "48c83729-fb5b-43d5-a66d-9532f4dfefdb");


define('SG_STORAGE_P_CLOUD_SECRET', '4kCf1CW3euu86kLEjL0rh7wdMFfV');
define('SG_STORAGE_P_CLOUD_REDIRECT_URI', "https://backup-guard.com/pcloud");
define('SG_STORAGE_P_CLOUD_CLIENT_ID', "dr1LOH66Yu8");


define('SG_STORAGE_BOX_SECRET', 'Fv3guGigz9gTkHGsNJBilgm9oFlRARn0');
define('SG_STORAGE_BOX_REDIRECT_URI', "https://backup-guard.com/box");
define('SG_STORAGE_BOX_CLIENT_ID', "4e92lvc5g8ir2se7c9lmzhv1ahn3knfy");

$SG_BACKUP_AMAZON_REGIONS = array(
	array("name"=>"US East (N. Virginia)", "region"=>"us-east-1"),
	array("name"=>"US East (Ohio)", "region"=>"us-east-2"),
	array("name"=>"US West (Oregon)", "region"=>"us-west-2"),
	array("name"=>"US West (N. California)", "region"=>"us-west-1"),
	array("name"=>"EU (Ireland)", "region"=>"eu-west-1"),
	array("name"=>"EU (London)", "region"=>"eu-west-2"),
	array("name"=>"EU (Frankfurt)", "region"=>"eu-central-1"),
	array("name"=>"Asia Pacific (Singapore)", "region"=>"ap-southeast-1"),
	array("name"=>"Asia Pacific (Tokyo)", "region"=>"ap-northeast-1"),
	array("name"=>"Asia Pacific (Sydney)", "region"=>"ap-southeast-2"),
	array("name"=>"Asia Pacific (Seoul)", "region"=>"ap-northeast-2"),
	array("name"=>"Asia Pacific (Mumbai)", "region"=>"ap-south-1"),
	array("name"=>"South America (São Paulo)", "region"=>"sa-east-1"),
	array("name"=>"Canada (Central)", "region"=>"ca-central-1")
);

$SG_BACKUP_GOOGLE_REGIONS = array(
	array("name" => "Google Storage: EU", "region" => "google-storage"),
	array("name" => "Google Storage: USA", "region" => "google-storage-us"),
	array("name" => "Google Storage: Asia", "region" => "google-storage-asia")
);

define('SG_PRODUCT_IDENTIFIER', 'backup-guard-wp-platinum');

//BackupGuard Support URL
define('SG_BACKUP_SUPPORT_URL', 'https://help.backup-guard.com/en/');
define('BG_UPGRADE_URL', 'https://backup-guard.com/products/security#pricing');
