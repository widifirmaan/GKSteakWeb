<?php
    $extensionAdapter = SGExtension::getInstance();
    $page = $_GET['page'];

    $isDisabelAdsEnabled = SGConfig::get('SG_DISABLE_ADS');
    $showUpgradeButton = SGBoot::isFeatureAvailable('SHOW_UPGRADE_PAGE');
    $buttonText = 'Buy now!';
    $upgradeText = 'Website migration, Backup to cloud, automation, mail notifications, and more in our PRO package!';
    $buttonUrl = SG_BACKUP_SITE_URL;

    $pluginCapabilities = backupGuardGetCapabilities();

if ($pluginCapabilities != BACKUP_GUARD_CAPABILITIES_FREE) {
    $buttonText = 'Upgrade to ';
    $buttonUrl = SG_BACKUP_PRODUCTS_URL;

    $upgradeTo = "";
    if ($pluginCapabilities == BACKUP_GUARD_CAPABILITIES_GOLD) {
        $upgradeTo = 'Platinum';
    } elseif ($pluginCapabilities == BACKUP_GUARD_CAPABILITIES_SILVER) {
        $upgradeTo = 'Gold';
    }

    $upgradeText = $buttonText . $upgradeTo . ' by paying only difference between plans.';
    $buttonText = $buttonText . $upgradeTo;
}

    $supportUrl = network_admin_url('admin.php?page=backup_guard_support');
    $openContent = 1;
if ($pluginCapabilities == BACKUP_GUARD_CAPABILITIES_FREE) {
    $openContent = 0;
    $supportUrl = BACKUP_GUARD_WORDPRESS_SUPPORT_URL;
}
?>
<div id="sg-sidebar-wrapper" class="metro">
    <a class="sg-site-url"  href="<?php echo network_admin_url('admin.php?page=backup_guard_backups'); ?>">
        <div class="title">
            <span class="sg-action-menu-arrow"></span>
        </div>
    </a>
    <nav class="sidebar dark sg-backup-sidebar-nav" id="sg-main-sidebar">
        <ul>
            <li class="<?php echo strpos($page, 'backups') ? 'active' : ''?>">
                <a href="<?php echo network_admin_url('admin.php?page=backup_guard_backups'); ?>" data-page-key="backups">
                    <span class="glyphicon glyphicon-hdd"></span><?php _backupGuardT('Backups')?>
                </a>
                <span class="sg-action-menu-arrow"></span>
            </li>
            <li class="<?php echo strpos($page, 'cloud') ? 'active' : ''?>">
                <a href="<?php echo network_admin_url('admin.php?page=backup_guard_cloud'); ?>" data-page-key="cloud">
                    <span class="glyphicon glyphicon-cloud" aria-hidden="true"></span><?php _backupGuardT('Cloud')?>
                </a>
                <span class="sg-action-menu-arrow"></span>
            </li>
            <?php if (SGBoot::isFeatureAvailable('SCHEDULE')) :?>
                <li class="<?php echo strpos($page, 'schedule') ? 'active' : ''?>">
                    <a href="<?php echo network_admin_url('admin.php?page=backup_guard_schedule'); ?>" data-page-key="schedule">
                        <span class="glyphicon glyphicon-time" aria-hidden="true"></span><?php _backupGuardT('Schedule')?>
                    </a>
                    <span class="sg-action-menu-arrow"></span>
                </li>
            <?php endif;?>
            <li class="<?php echo strpos($page, 'settings') ? 'active' : ''?>">
                <a href="<?php echo network_admin_url('admin.php?page=backup_guard_settings'); ?>" data-page-key="settings">
                    <span class="glyphicon glyphicon-cog" aria-hidden="true"></span><?php _backupGuardT('Settings')?>
                </a>
                <span class="sg-action-menu-arrow"></span>
            </li>
            <li class="<?php echo strpos($page, 'system_info') ? 'active' : ''?>">
                <a href="<?php echo network_admin_url('admin.php?page=backup_guard_system_info'); ?>" data-page-key="system_info">
                    <span class="glyphicon glyphicon-equalizer" aria-hidden="true"></span><?php _backupGuardT('System Info.')?>
                </a>
                <span class="sg-action-menu-arrow"></span>
            </li>
            <!--<li class="<?php /*echo strpos($page, 'services') ? 'active' : ''*/?>">
                <a href="<?php /*echo network_admin_url('admin.php?page=backup_guard_services'); */?>" data-page-key="services">
                    <span class="glyphicon glyphicon-briefcase" aria-hidden="true"></span><?php /*_backupGuardT('Services')*/?>
                </a>
                <span class="sg-action-menu-arrow"></span>
            </li>-->
            <li class="<?php echo strpos($page, 'videoTutorials') ? 'active' : ''?>">
                <a href="<?php echo $supportUrl; ?>" data-page-key="videoTutorials">
                    <span class="sg-backup-menu-video" aria-hidden="true"></span><?php _backupGuardT('Video Tutorials')?>
                </a>
                <span class="sg-action-menu-arrow"></span>
            </li>
            <li class="<?php echo strpos($page, 'support') ? 'active' : ''?>">
                <a href="<?php echo $supportUrl; ?>" data-page-key="support" data-open-content="<?php echo $openContent; ?>">
                    <span class="glyphicon glyphicon-envelope" aria-hidden="true"></span><?php _backupGuardT('Support')?>
                </a>
                <span class="sg-action-menu-arrow"></span>
            </li>
            <?php if (SGBoot::isFeatureAvailable('SHOW_UPGRADE_PAGE')) :?>
                <li class="<?php echo strpos($page, 'pro_features') ? 'active' : ''?>">
                    <a href="<?php echo network_admin_url('admin.php?page=backup_guard_pro_features'); ?>" data-page-key="pro_features">
                        <span class="glyphicon glyphicon-refresh" aria-hidden="true"></span><?php _backupGuardT('Why upgrade?')?>
                    </a>
                    <span class="sg-action-menu-arrow"></span>
                </li>
            <?php endif; ?>
            <!-- Will be added in the future release -->
            <!-- <?php if ($extensionAdapter->isExtensionActive(SG_BACKUP_GUARD_SECURITY_EXTENSION)) :?>
                    <li class="<?php echo strpos($page, 'security') ? 'active' : ''?>">
                        <a href="<?php echo network_admin_url('admin.php?page=backup_guard_security'); ?>">
                            <span class="glyphicon glyphicon-lock" aria-hidden="true"></span>Security
                            <span class="badge badge-info">New</span>
                        </a>
                    </li>
                 <?php endif; ?> -->
        </ul>
    </nav>
    <?php if ($showUpgradeButton && !$isDisabelAdsEnabled) :?>
        <div class="sg-alert-pro">
            <p class="sg-upgrade-text">
                <?php _backupGuardT($upgradeText); ?>
            </p>
            <p>
                <a class="btn btn-success" target="_blank" href="<?php echo $buttonUrl . '?utm_source=plugin&utm_medium=Left_cta&utm_campaign=BuyNow_plugin'; ?>">
                    <?php _backupGuardT($buttonText); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>
</div>
