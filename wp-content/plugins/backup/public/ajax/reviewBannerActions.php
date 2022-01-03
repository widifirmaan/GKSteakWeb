<?php
require_once(SG_LIB_PATH.'SGReviewManager.php');
$type = $_POST['type'];
if ($type == 'dayCount') {
	$timeDate = new \DateTime('now');
	$installTime = strtotime($timeDate->format('Y-m-d H:i:s'));
	SGConfig::set('installDate', $installTime);
	$timeDate->modify('+'.SG_BACKUP_REVIEW_PERIOD.' day');

	$timeNow = strtotime($timeDate->format('Y-m-d H:i:s'));
	SGConfig::set('openNextTime', $timeNow);

	$usageDays = SGConfig::get('usageDays');
	$usageDays += SG_BACKUP_REVIEW_PERIOD;
	SGConfig::set('usageDays', $usageDays);
}
else if ($type == 'backupCount') {
	$backupCountReview = SGConfig::get('backupReviewCount');
	if (empty($backupCountReview)) {
		$backupCountReview = SGReviewManager::getBackupCounts();
	}
	$backupCountReview += SG_BACKUP_REVIEW_BACKUP_COUNT;
	SGConfig::set('backupReviewCount', $backupCountReview);
}
else if ($type == 'restoreCount') {
	$restoreReviewCount = SGConfig::get('restoreReviewCount');
	if (empty($restoreReviewCount)) {
		$restoreReviewCount = SGReviewManager::getBackupRestoreCounts();
	}
	$restoreReviewCount += SG_BACKUP_REVIEW_RESTORE_COUNT;
	SGConfig::set('restoreReviewCount', $restoreReviewCount);
}