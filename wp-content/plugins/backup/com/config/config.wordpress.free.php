<?php

define('SG_FEATURE_DOWNLOAD_FROM_CLOUD', 0);
define('SG_FEATURE_STORAGE', 1);
define('SG_FEATURE_FTP', 0);
define('SG_FEATURE_AMAZON', 0);
define('SG_FEATURE_DROPBOX', 1);
define('SG_FEATURE_GOOGLE_DRIVE', 0);
define('SG_FEATURE_ONE_DRIVE', 0);
define('SG_FEATURE_P_CLOUD', 0);
define('SG_FEATURE_BOX', 0);
define('SG_FEATURE_SCHEDULE', 1);
define('SG_FEATURE_DELETE_LOCAL_BACKUP_AFTER_UPLOAD', 0);
define('SG_FEATURE_ALERT_BEFORE_UPDATE', 0);
define('SG_FEATURE_NUMBER_OF_BACKUPS_TO_KEEP', 0);
define('SG_FEATURE_CUSTOM_BACKUP_NAME', 0);
define('SG_FEATURE_SUBDIRECTORIES', 0);
define('SG_FEATURE_BACKGROUND_MODE', 0);
define('SG_FEATURE_NOTIFICATIONS', 0);
define('SG_FEATURE_MULTI_SCHEDULE', 0);
define('SG_FEATURE_SHOW_UPGRADE_PAGE', 1);
define('SG_FEATURE_BACKUP_WITH_MIGRATION', 0);
define('SG_FEATURE_NUMBER_OF_ROWS_TO_BACKUP', 1);
define('SG_FEATURE_BACKUP_DELETION_WILL_ALSO_DELETE_FROM_CLOUD', 0);
define('SG_FEATURE_SLECTIVE_RESTORE', 0);
define('SG_FEATURE_HIDE_ADS', 0);

//Storage
define('SG_STORAGE_FTP', 1);
define('SG_STORAGE_DROPBOX', 2);
define('SG_STORAGE_GOOGLE_DRIVE', 3);
define('SG_STORAGE_AMAZON', 4);
define('SG_STORAGE_ONE_DRIVE', 5);
define('SG_STORAGE_BACKUP_GUARD', 6);
define('SG_STORAGE_P_CLOUD', 7);
define('SG_STORAGE_BOX', 8);

define('SG_STORAGE_DROPBOX_KEY', 'n3yhajm64h88m9t');
define('SG_STORAGE_DROPBOX_SECRET', 's8crjkls7f9wqtd');
define('SG_STORAGE_DROPBOX_CLIENT_ID', 'backup-guard');
define('SG_STORAGE_DROPBOX_REDIRECT_URI', 'https://backup-guard.com/dropbox/');
define('SG_FEATURE_BACKUP_GUARD', 1);

define('SG_PRODUCT_IDENTIFIER', 'backup-guard-wp-free');

//BackupGuard Support URL
define('SG_BACKUP_SUPPORT_URL', 'https://help.backup-guard.com/en/');
define('BG_UPGRADE_URL', 'https://backup-guard.com/products/backup-wordpress#pricing');
