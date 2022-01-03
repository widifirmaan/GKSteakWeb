<?php
$dropbox = SGConfig::get('SG_DROPBOX_ACCESS_TOKEN');
$gdrive = SGConfig::get('SG_GOOGLE_DRIVE_REFRESH_TOKEN');
$ftp = SGConfig::get('SG_STORAGE_FTP_CONNECTED');
$amazon = SGConfig::get('SG_STORAGE_AMAZON_CONNECTED');
$oneDrive = SGConfig::get('SG_ONE_DRIVE_REFRESH_TOKEN');
$pCloud = SGConfig::get('SG_P_CLOUD_ACCESS_TOKEN');
$box = SGConfig::get('SG_BOX_REFRESH_TOKEN');
$ftpUsername = SGConfig::get('SG_FTP_CONNECTION_STRING');
$gdriveUsername = SGConfig::get('SG_GOOGLE_DRIVE_CONNECTION_STRING');
$dropboxUsername = SGConfig::get('SG_DROPBOX_CONNECTION_STRING');
$amazonInfo = SGConfig::get('SG_AMAZON_BUCKET');

$oneDriveInfo = SGConfig::get('SG_ONE_DRIVE_CONNECTION_STRING');
$pCloudInfo = SGConfig::get('SG_P_CLOUD_CONNECTION_STRING');
$boxInfo = SGConfig::get('SG_BOX_CONNECTION_STRING');

$contentClassName = getBackupPageContentClassName('cloud');


$backupGuardCloudAccount = SGConfig::get('SG_BACKUPGUARD_CLOUD_ACCOUNT')?unserialize(SGConfig::get('SG_BACKUPGUARD_CLOUD_ACCOUNT')):'';
$backupGuardCloudAccountEmail = SGConfig::get('SG_BACKUPGUARD_CLOUD_ACCOUNT_EMAIL');

$backupGuardConnectionString = "<span>". _backupGuardT('Connect now and get 1 GB storage space for FREE', true)."</span>";

if (!empty($backupGuardCloudAccount)) {
	$usedSpace = $backupGuardCloudAccount['usedStorage'];
	$storage = $backupGuardCloudAccount['package']['storage'];
	$availableSpaceInpercents = $usedSpace*100/$storage;

	if ($availableSpaceInpercents < 25) {
		$usedSpaceTextColor = "green";
	}
	else if ((25 <= $availableSpaceInpercents) && ($availableSpaceInpercents < 50)) {
		$usedSpaceTextColor = "black";
	}
	else if ((50 <= $availableSpaceInpercents) && ($availableSpaceInpercents <= 75)) {
		$usedSpaceTextColor = "orange";
	}
	else if ($availableSpaceInpercents >= 75) {
		$usedSpaceTextColor = "red";
	}

	$backupGuardConnectionString = $backupGuardCloudAccountEmail.' | <span style="color: '.$usedSpaceTextColor.';">'.convertToReadableSize($usedSpace*BACKUP_GUARD_ONE_MB).' / '.convertToReadableSize($storage*BACKUP_GUARD_ONE_MB).'</span> | <a target="_blank" href="'.BACKUP_GUARD_CLOUD_UPGRADE_URL.'">Upgrade for more space</a>';
}
?>
<div id="sg-backup-page-content-cloud" class="sg-backup-page-content <?php echo $contentClassName; ?>">
<div class="row sg-cloud-container">
    <div class="col-md-12">
        <form class="form-horizontal">
            <fieldset>
                <div><h1 class="sg-backup-page-title"><?php _backupGuardT('Cloud settings')?></h1></div>
                <?php if (SGBoot::isFeatureAvailable('SUBDIRECTORIES')): ?>
                    <div class="form-group form-inline">
                        <label class="col-md-5 sg-control-label">
                            <?php _backupGuardT('Destination folder')?>
                        </label>
                        <div class="col-md-3">
                            <input id="cloudFolder" name="cloudFolder" type="text" class="form-control input-md sg-backup-input" value="<?php echo esc_html(SGConfig::get('SG_STORAGE_BACKUPS_FOLDER_NAME'))?>">
                            <button type="button" id="sg-save-cloud-folder" class="btn btn-success pull-right"><?php _backupGuardT('Save');?></button>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- BackupGuard -->
				<?php if (SGBoot::isFeatureAvailable('BACKUP_GUARD') && SG_SHOW_BACKUPGUARD_CLOUD): ?>
					<div class="form-group">
						<label class="col-md-5 sg-control-label sg-user-info">
                            <div class="sg-cloud-icon-wrapper">
                                <span class="sg-cloud-icon sg-cloud-backup-guard"></span>
                            </div>
                            <div class="sg-cloud-label-with-info">
                                <span><?php echo 'BackupGuard' ?></span>
                                <span class="sg-backupguard-user sg-helper-block">
								<?php echo $backupGuardConnectionString ?>
							</span>
                            </div>
						</label>
						<div class="col-md-3">
							<label class="sg-switch-container">
								<input type="checkbox" data-on-text="<?php _backupGuardT('ON')?>" data-off-text="<?php _backupGuardT('OFF')?>" data-storage="BACKUP_GUARD" data-remote="bgLogin" class="sg-switch" <?php echo !empty($backupGuardCloudAccount)?'checked="checked"':''?>>
								<a id="backup-guard-details" href="javascript:void(0)" class="hide" data-toggle="modal" data-modal-name="backup-guard-details" data-remote="modalBackupGuardDetails"></a>
							</label>
						</div>
					</div>
				<?php endif; ?>
                <!-- Dropbox -->
                <?php if (SGBoot::isFeatureAvailable('DROPBOX')): ?>
                    <div class="form-group">
                        <label class="col-md-5 sg-control-label">
                            <div class="sg-cloud-icon-wrapper">
                                <span class="sg-cloud-icon sg-cloud-dropbox"></span>
                            </div>
                            <div class="sg-cloud-label-wrapper">
                                <span><?php echo 'Dropbox' ?></span>
                                <?php if(!empty($dropboxUsername)): ?>
                                    <br/>
                                    <span class="text-muted sg-dropbox-user sg-helper-block"><?php echo $dropboxUsername;?></span>
                                <?php endif;?>
                            </div>
                        </label>
                        <div class="col-md-3">
                            <label class="sg-switch-container">
                                <input data-on-text="<?php _backupGuardT('ON')?>" data-off-text="<?php _backupGuardT('OFF')?>" data-storage="DROPBOX" data-remote="cloudDropbox" type="checkbox" class="sg-switch" <?php echo !empty($dropbox)?'checked="checked"':''?>>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- Google Drive -->
                <?php if (SGBoot::isFeatureAvailable('GOOGLE_DRIVE')): ?>
                    <div class="form-group">
                        <label class="col-md-5 sg-control-label">
                            <div class="sg-cloud-icon-wrapper">
                                <span class="sg-cloud-icon sg-cloud-google-drive"></span>
                            </div>
                            <div class="sg-cloud-label-wrapper">
                            <?php echo 'Google Drive' ?>
                                <?php if(!empty($gdriveUsername)): ?>
                                    <br/>
                                    <span class="text-muted sg-gdrive-user sg-helper-block"><?php echo $gdriveUsername;?></span>
                                <?php endif;?>
                            </div>
                        </label>
                        <div class="col-md-3">
                            <label class="sg-switch-container">
                                <input data-on-text="<?php _backupGuardT('ON')?>" data-off-text="<?php _backupGuardT('OFF')?>" data-storage="GOOGLE_DRIVE" data-remote="cloudGdrive" type="checkbox" class="sg-switch" <?php echo !empty($gdrive)?'checked="checked"':''?>>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- FTP -->
                <?php if (SGBoot::isFeatureAvailable('FTP')): ?>
                    <div class="form-group">
                        <label class="col-md-5 sg-control-label sg-user-info">
                            <div class="sg-cloud-icon-wrapper">
                                <span class="sg-cloud-icon sg-cloud-ftp"></span>
                            </div>
                            <div class="sg-cloud-label-wrapper">
                                <?php echo 'FTP / SFTP' ?>
                                <?php if(!empty($ftpUsername)): ?>
                                    <br/>
                                    <span class="text-muted sg-ftp-user sg-helper-block"><?php echo $ftpUsername;?></span>
                                <?php endif;?>
                            </div>
                        </label>
                        <div class="col-md-3">
                            <label class="sg-switch-container">
                                <input type="checkbox" data-on-text="<?php _backupGuardT('ON')?>" data-off-text="<?php _backupGuardT('OFF')?>" data-storage="FTP" data-remote="cloudFtp" class="sg-switch" <?php echo !empty($ftp)?'checked="checked"':''?>>
                                <a id="ftp-settings" href="javascript:void(0)" class="hide" data-toggle="modal" data-modal-name="ftp-settings" data-remote="modalFtpSettings"><?php echo 'FTP '._backupGuardT('Settings', true) ?></a>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- Amazon S3 -->
                <?php if (SGBoot::isFeatureAvailable('AMAZON')): ?>
                    <div class="form-group">
                        <label class="col-md-5 sg-control-label">
                            <div class="sg-cloud-icon-wrapper">
                                <span class="sg-cloud-icon sg-cloud-amazon"></span>
                            </div>
                            <div class="sg-cloud-label-wrapper">
                            <?php echo (backupGuardIsAccountGold()? 'Amazon ':'').'S3'?>
                                <?php if (!empty($amazonInfo)):?>
                                    <br/>
                                    <span class="text-muted sg-amazonr-user sg-helper-block"><?php echo $amazonInfo;?></span>
                                <?php endif;?>
                            </div>
                        </label>
                        <div class="col-md-3">
                            <label class="sg-switch-container">
                                <input type="checkbox" data-on-text="<?php _backupGuardT('ON')?>" data-off-text="<?php _backupGuardT('OFF')?>" data-storage="AMAZON" data-remote="cloudAmazon" class="sg-switch" <?php echo !empty($amazon)?'checked="checked"':''?>>
                                <a id="amazon-settings" href="javascript:void(0)" class="hide" data-toggle="modal" data-modal-name="amazon-settings" data-remote="modalAmazonSettings"><?php echo 'Amazon'._backupGuardT('Settings', true)?></a>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- One Drive -->
                <?php if (SGBoot::isFeatureAvailable('ONE_DRIVE')): ?>
                    <div class="form-group">
                        <label class="col-md-5 sg-control-label">
                            <div class="sg-cloud-icon-wrapper">
                                <span class="sg-cloud-icon sg-cloud-one-drive"></span>
                            </div>
                            <div class="sg-cloud-label-wrapper">
                                <?php echo 'One Drive' ?>
                                <?php if(!empty($oneDriveInfo)): ?>
                                    <br/>
                                    <span class="text-muted sg-gdrive-user sg-helper-block"><?php echo $oneDriveInfo;?></span>
                                <?php endif;?>
                            </div>
                        </label>
                        <div class="col-md-3">
                            <label class="sg-switch-container">
                                <input data-on-text="<?php _backupGuardT('ON')?>" data-off-text="<?php _backupGuardT('OFF')?>" data-storage="ONE_DRIVE" data-remote="cloudOneDrive" type="checkbox" class="sg-switch" <?php echo !empty($oneDrive)?'checked="checked"':''?>>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- PCloud -->
	            <?php if ( SGBoot::isFeatureAvailable( 'P_CLOUD' ) ): ?>
                    <div class="form-group">
                        <label class="col-md-5 sg-control-label">
                            <div class="sg-cloud-icon-wrapper">
                                <span class="sg-cloud-icon sg-cloud-pcloud"></span>
                            </div>
                            <div class="sg-cloud-label-wrapper">
					            <?php echo 'pCloud' ?>
					            <?php if ( ! empty( $pCloudInfo ) ): ?>
                                    <br/>
                                    <span class="text-muted sg-gdrive-user sg-helper-block"><?php echo json_decode( $pCloudInfo, true )['email']; ?></span>
					            <?php endif; ?>
                            </div>
                        </label>
                        <div class="col-md-3">
                            <label class="sg-switch-container">
                                <input data-on-text="<?php _backupGuardT('ON')?>" data-off-text="<?php _backupGuardT('OFF')?>" data-storage="P_CLOUD" data-remote="cloudPCloud" type="checkbox" class="sg-switch" <?php echo !empty($pCloud)?'checked="checked"':''?>>
                            </label>
                        </div>
                    </div>
	            <?php endif; ?>

                <!-- Box -->
	            <?php if ( SGBoot::isFeatureAvailable( 'BOX' ) ): ?>
                    <div class="form-group">
                        <label class="col-md-5 sg-control-label">
                            <div class="sg-cloud-icon-wrapper">
                                <span class="sg-cloud-icon sg-cloud-box"></span>
                            </div>
                            <div class="sg-cloud-label-wrapper">
					            <?php echo 'box.com' ?>
					            <?php if ( ! empty( $boxInfo ) ): ?>
                                    <br/>
                                    <span class="text-muted sg-gdrive-user sg-helper-block"><?php echo json_decode( $boxInfo, true )['login']; ?></span>
					            <?php endif; ?>
                            </div>
                        </label>
                        <div class="col-md-3">
                            <label class="sg-switch-container">
                                <input data-on-text="<?php _backupGuardT('ON')?>" data-off-text="<?php _backupGuardT('OFF')?>" data-storage="BOX" data-remote="cloudBox" type="checkbox" class="sg-switch" <?php echo !empty($box)?'checked="checked"':''?>>
                            </label>
                        </div>
                    </div>
	            <?php endif; ?>


            </fieldset>
        </form>
    </div>
</div>
</div>
