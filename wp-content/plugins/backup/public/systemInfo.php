<?php
require_once(SG_SCHEDULE_PATH . 'SGSchedule.php');
$contentClassName = getBackupPageContentClassName('system_info');
?>
<div id="sg-backup-page-content-system_info" class="sg-backup-page-content <?php echo $contentClassName; ?>">
    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal" method="post" data-sgform="ajax" data-type="sgsettings">
                <fieldset>
                    <div><h1 class="sg-backup-page-title"><?php _backupGuardT('System information') ?></h1></div>
                    <div class="form-group">
                        <label class="col-md-3 sg-control-label sg-user-info"><?php _backupGuardT('Disk free space'); ?></label>
                        <div class="col-md-3 text-left">
                            <label class="sg-control-label">
                                <?php echo backupGuardDiskFreeSize(SG_APP_ROOT_DIRECTORY); ?>
                            </label>
                        </div>
                    </div>
                    <div class="form-group sg-info-wrapper">
                        <label class="col-md-3 sg-control-label sg-user-info"><?php _backupGuardT('Memory limit'); ?></label>
                        <div class="col-md-3 text-left">
                            <label class="sg-control-label"><?php echo SGBoot::$memoryLimit; ?></label>
                        </div>
                    </div>
                    <div class="form-group sg-info-wrapper">
                        <label class="col-md-3 sg-control-label sg-user-info">
                            <?php _backupGuardT('Max execution time'); ?>
                        </label>
                        <div class="col-md-3 text-left">
                            <label class="sg-control-label"><?php echo SGBoot::$executionTimeLimit; ?></label>
                        </div>
                    </div>
                    <div class="form-group sg-info-wrapper">
                        <label class="col-md-3 sg-control-label sg-user-info">
                            <?php _backupGuardT('PHP version'); ?>
                        </label>
                        <div class="col-md-3 text-left">
                            <label class="sg-control-label"><?php echo PHP_VERSION; ?></label>
                        </div>
                    </div>
                    <div class="form-group sg-info-wrapper">
                        <label class="col-md-3 sg-control-label sg-user-info"><?php _backupGuardT('MySQL version'); ?></label>
                        <div class="col-md-3 text-left">
                            <label class="sg-control-label"><?php echo SG_MYSQL_VERSION; ?></label>
                        </div>
                    </div>
                    <div class="form-group sg-info-wrapper">
                        <label class="col-md-3 sg-control-label sg-user-info">
                            <?php _backupGuardT('Int size'); ?>
                        </label>
                        <div class="col-md-3 text-left">
                            <?php echo '<label class="sg-control-label">' . PHP_INT_SIZE . '</label>'; ?>
                            <?php
                            if (PHP_INT_SIZE < 8) {
                                echo '<label class="sg-control-label backup-guard-label-warning">Notice that archive size cannot be bigger than 2GB. This limitaion is comming from system.</label>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="form-group sg-info-wrapper">
                        <div class="col-md-3 ">
                            <label class="sg-control-label sg-user-info"><?php _backupGuardT('Curl version'); ?></label>
                        </div>
                        <div class="col-md-8 text-left">
                            <?php
                            if (function_exists('curl_version') && function_exists('curl_exec')) {
                                $cv = curl_version();
                                echo '<label class="sg-control-label sg-blue-label">' . $cv['version'] . ' / SSL: ' . $cv['ssl_version'] . ' / libz: ' . $cv['libz_version'] . '</label>';
                            } else {
                                echo '<label class="sg-control-label backup-guard-label-warning">Curl required for BackupGuard for better functioning.</label>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="form-group sg-info-wrapper">
                        <div class="col-md-3 ">
                            <label class="sg-control-label sg-user-info"><?php _backupGuardT('Is cron available'); ?></label>
                        </div>
                        <div class="col-md-3 text-left">
                            <?php
                            $isCronAvailable = SGSchedule::isCronAvailable(true);
                            if ($isCronAvailable) {
                                echo '<label class="sg-control-label">Yes</label>';
                            } else {
                                //echo '<label class="sg-control-label backup-guard-label-warning">Please consider enabling WP Cron in order to be able to setup schedules.</label>';
                                echo '<label class="sg-control-label backup-guard-label-warning">WP cron is disabled on your end. If you don\'t use a custom cron, please, enable the WP cron or else the scheduled (backup) won\'t be successfully implemented.</label>';
                            }
                            ?>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>
