<?php
require_once(dirname(__FILE__).'/boot.php');
require_once(SG_BACKUP_PATH.'SGBackup.php');
require_once(SG_PUBLIC_INCLUDE_PATH.'header.php');
require_once(SG_PUBLIC_INCLUDE_PATH.'sidebar.php');
$pluginCapabilities = backupGuardGetCapabilities();
$pluginCapabilities = backupGuardGetCapabilities();
?>
<div class="sg-top-info"><?php echo backupGuardLoggedMessage(); ?></div>
<div id="sg-content-wrapper">
    <div class="container-fluid">
        <?php require_once(plugin_dir_path( __FILE__ ).'backups.php'); ?>
        <?php require_once(plugin_dir_path(__FILE__).'cloud.php'); ?>
        <?php require_once(plugin_dir_path(__FILE__).'schedule.php'); ?>
        <?php require_once(plugin_dir_path(__FILE__).'settings.php'); ?>
        <?php require_once(plugin_dir_path(__FILE__).'systemInfo.php'); ?>
        <?php require_once(plugin_dir_path(__FILE__).'services.php'); ?>
        <?php require_once(plugin_dir_path(__FILE__).'videoTutorials.php'); ?>
        <?php require_once(plugin_dir_path(__FILE__).'support.php'); ?>
        <?php
            if (SGBoot::isFeatureAvailable('SHOW_UPGRADE_PAGE')) {
                require_once(plugin_dir_path(__FILE__).'proFeatures.php');
            }
        ?>
    </div>
</div>
<div class="clearfix"></div>
<?php
require_once(SG_PUBLIC_INCLUDE_PATH.'/footer.php');
?>
