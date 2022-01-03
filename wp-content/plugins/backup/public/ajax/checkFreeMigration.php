<?php
require_once(dirname(__FILE__).'/../boot.php');
require_once(SG_LIB_PATH.'SGArchive.php');
$pluginCapabilities = backupGuardGetCapabilities();

if ($pluginCapabilities != BACKUP_GUARD_CAPABILITIES_FREE) {
	return '';
}
$backupName = $_POST['bname'];

$path = SG_BACKUP_DIRECTORY.$backupName.'/'.$backupName.'.sgbp';

$sgArchive = new SGArchive($path, 'r');
$headers = $sgArchive->getArchiveHeaders();

$siteUrl = $headers['siteUrl'];
$dbPrefix = $headers['dbPrefix'];

if ($siteUrl != SG_SITE_URL) {
	printf("The source url (%s) doesn’t match the current url (%s). This is considered as migration and it is not available in the free plugin. <a href='%s' target='_blank'>Upgrade now</a>", $siteUrl, SG_SITE_URL, BG_UPGRADE_URL);
}
else if ($dbPrefix != SG_ENV_DB_PREFIX) {
	printf("The source db prefix (%s) doesn’t match the current db prefix (%s). This is considered as migration and it is not available in the free plugin.
You can change the current db prefix manually or upgrade to one of our PRO versions. <a href='%s' target='_blank'>Upgrade now</a>", $dbPrefix, SG_ENV_DB_PREFIX, BG_UPGRADE_URL);
}