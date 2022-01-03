<?php

$isAdsEnabled = SGConfig::get('SG_DISABLE_ADS');
$closeFreeBanner = SGConfig::get('SG_CLOSE_FREE_BANNER');
$closeChristmasBanner = SGConfig::get('SG_CLOSE_CHRISTMAS_BANNER');
$installDate = SGConfig::get('installDate');
$dealDate = strtotime("6 December 2021");
$pluginCapabilities = backupGuardGetCapabilities();

if (($pluginCapabilities == BACKUP_GUARD_CAPABILITIES_FREE || $pluginCapabilities == BACKUP_GUARD_CAPABILITIES_SILVER) && !$closeChristmasBanner && !$isAdsEnabled && $installDate >= $dealDate) {
    include_once(SG_NOTICE_TEMPLATES_PATH . 'christmasBanner.php');
}

if (!$isAdsEnabled && !$closeFreeBanner) {
    include_once(SG_NOTICE_TEMPLATES_PATH . 'banner.php');
}

SGNotice::getInstance()->renderAll();
?>

<div class="sg-spinner"></div>
<div class="sg-wrapper-less">
    <div id="sg-wrapper">
