<?php
$contentClassName = getBackupPageContentClassName('pro_features');
$optionsAvailability = array();
$optionsAvailability['Website Backup & Restore'] = array('free' => 1, 'silver' => 1, 'gold' => 1, 'platinum' => 1);
$optionsAvailability['Website Migration'] = array('free' => 0, 'silver' => 1, 'gold' => 1, 'platinum' => 1);
$optionsAvailability['Backup to Dropbox (64-bit OS)'] = array('free' => 1, 'silver' => 1, 'gold' => 1, 'platinum' => 1);
$optionsAvailability['Backup Download & Import'] = array('free' => 1, 'silver' => 1, 'gold' => 1, 'platinum' => 1);
$optionsAvailability['Automatic Backups (single profile)'] = array('free' => 0, 'silver' => 1, 'gold' => 1, 'platinum' => 1);
$optionsAvailability['E-mail Notifications'] = array('free' => 0, 'silver' => 1, 'gold' => 1, 'platinum' => 1);
$optionsAvailability['Backup to SFTP/FTP'] = array('free' => 0, 'silver' => 1, 'gold' => 1, 'platinum' => 1);
/// start Gold
$optionsAvailability['Backup to Google Drive'] = array('free' => 0, 'silver' => 0, 'gold' => 1, 'platinum' => 1);
$optionsAvailability['Backup to Amazon S3'] = array('free' => 0, 'silver' => 0, 'gold' => 1, 'platinum' => 1);
$optionsAvailability['Backup to OneDrive'] = array('free' => 0, 'silver' => 0, 'gold' => 1, 'platinum' => 1);
$optionsAvailability['Backup Retention'] = array('free' => 0, 'silver' => 0, 'gold' => 1, 'platinum' => 1);
$optionsAvailability['Restore from all Supported Clouds'] = array('free' => 0, 'silver' => 0, 'gold' => 1, 'platinum' => 1);
$optionsAvailability['Delete Local Copy after Upload'] = array('free' => 0, 'silver' => 0, 'gold' => 1, 'platinum' => 1);
$optionsAvailability['Customize Backup Name'] = array('free' => 0, 'silver' => 0, 'gold' => 1, 'platinum' => 1);
// start Platinum
$optionsAvailability['Multiple Automatic Backups'] = array('free' => 0, 'silver' => 0, 'gold' => 0, 'platinum' => 1);
$optionsAvailability['Multiple Automatic Backups'] = array('free' => 0, 'silver' => 0, 'gold' => 0, 'platinum' => 1);
?>
<div id="sg-backup-page-content-pro_features" class="sg-backup-page-content <?php echo $contentClassName; ?>">
    <div><h1 class="sg-backup-page-title"><?php _backupGuardT('Why upgrade?') ?></h1></div>
    <div class="sg-wrap-container sg-pricing-table-wrapper">
        <h3 class="sg-backup-guard-plans-title"><?php _backupGuardT('BackupGuard Plans') ?></h3>
        <div class="sg-backup-header-row sg-backup-table-row" style="clear: both">
            <div class="col-sm-4 sg-pricing-table-header-first-column">
                <span class="sg-pricing-table-header-label"></span>
            </div>
            <div class="col-sm-2">
                <span class="sg-pricing-table-header-label"><?php _backupGuardT('Free'); ?></span>
                <span class="sg-pricing-table-blue-label"><b><?php _backupGuardT('0'); ?></b> <span
                            class="sg-backup-pricing-currency">US$</span></span>
            </div>
            <div class="col-sm-2">
                <span class="sg-pricing-table-header-label"><?php _backupGuardT('Silver'); ?></span>
                <span class="sg-pricing-table-blue-label"><b><?php _backupGuardT('25'); ?></b> <span
                            class="sg-backup-pricing-currency">US$</span></span>
            </div>
            <div class="col-sm-2">
                <span class="sg-pricing-table-header-label"><?php _backupGuardT('Gold'); ?></span>
                <span class="sg-pricing-table-blue-label"><b><?php _backupGuardT('39'); ?></b> <span
                            class="sg-backup-pricing-currency">US$</span></span>
            </div>
            <div class="col-sm-2">
                <span class="sg-pricing-table-header-label"><?php _backupGuardT('Platinum'); ?></span>
                <span class="sg-pricing-table-blue-label"><b><?php _backupGuardT('99'); ?></b> <span
                            class="sg-backup-pricing-currency">US$</span></span>
            </div>
        </div>
        <div class="sg-backup-table-row" style="clear: both">
            <div class="col-sm-4">
                <span class="sg-pricng-table-option"><?php _backupGuardT('Websites Licences ') ?></span>
            </div>
            <div class="col-sm-2" style="text-align: center">
                <span class="sg-backup-plan-excluded"></span>
            </div>
            <div class="col-sm-2" style="text-align: center">
                <span class="sg-pricing-table-includes"><?php _backupGuardT('Up to 2') ?></span>
            </div>
            <div class="col-sm-2" style="text-align: center">
                <span class="sg-pricing-table-includes"><?php _backupGuardT('Up to 5') ?></span>
            </div>
            <div class="col-sm-2" style="text-align: center">
                <span class="sg-pricing-table-includes"><?php _backupGuardT('Unlimited') ?></span>
            </div>
        </div>
        <div class="sg-backup-table-options-wrapper" >
            <?php foreach ($optionsAvailability as $label => $availability) :?>
                <div class="sg-backup-table-row" style="clear: both">
                    <div class="col-sm-4 sg-pricing-table-option-wrapper">
                        <span class="sg-pricing-table-option"><?php _backupGuardT($label); ?></span>
                    </div>
                    <div class="col-sm-2">
                        <?php if ($availability['free'] == 1) : ?>
                            <span class="sg-backup-plan-included"></span>
                        <?php else : ?>
                            <span class="sg-backup-plan-excluded"></span>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-2">
                        <?php if ($availability['silver'] == 1) : ?>
                            <span class="sg-backup-plan-included"></span>
                        <?php else : ?>
                            <span class="sg-backup-plan-excluded"></span>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-2">
                        <?php if ($availability['gold'] == 1) : ?>
                            <span class="sg-backup-plan-included"></span>
                        <?php else : ?>
                            <span class="sg-backup-plan-excluded"></span>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-2">
                        <?php if ($availability['platinum'] == 1) : ?>
                            <span class="sg-backup-plan-included"></span>
                        <?php else : ?>
                            <span class="sg-backup-plan-excluded"></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
