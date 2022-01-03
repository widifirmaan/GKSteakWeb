<?php
	require_once(dirname(__FILE__).'/../boot.php');

	$directories = SG_BACKUP_FILE_PATHS;
	$directories = explode(',', $directories);
	$dropbox = SGConfig::get('SG_DROPBOX_ACCESS_TOKEN');
	$gdrive = SGConfig::get('SG_GOOGLE_DRIVE_REFRESH_TOKEN');
	$ftp = SGConfig::get('SG_STORAGE_FTP_CONNECTED');
	$amazon = SGConfig::get('SG_AMAZON_KEY');
	$oneDrive = SGConfig::get('SG_ONE_DRIVE_REFRESH_TOKEN');
	$pCloud = SGConfig::get('SG_P_CLOUD_ACCESS_TOKEN');
	$box = SGConfig::get('SG_BOX_REFRESH_TOKEN');
	$backupGuard = SGConfig::get('SG_BACKUPGUARD_UPLOAD_ACCESS_TOKEN');

	$backupType = (int)@$_GET['backupType'];
	$infoIconHtml = '<span class="dashicons dashicons-editor-help sgbg-info-icon"></span>';
?>
<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<div id="sg-modal-manual-backup-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<h4 class="modal-title"><?php ($backupType == SG_BACKUP_METHOD_MIGRATE)?_backupGuardT("Prepare migration package"):_backupGuardT("Manual Backup") ?></h4>
			</div>
		</div>
		<form class="form-horizontal" method="post" id="manualBackup">
			<div class="modal-body sg-modal-body">
				<!-- Multiple Radios -->
				<div class="form-group">
					<div class="col-md-12">
						<input type="text" name="sg-custom-backup-name" id="sg-custom-backup-name" class="sg-backup-input" placeholder="Custom backup name (optional)">
					</div>
					<div class="col-md-12">
						<div class="radio">
							<label for="full-backup-radio">
								<input type="radio" name="backupType" id="full-backup-radio" value="1" checked="checked">
								<?php _backupGuardT('Full backup'); ?>
							</label>
						</div>
						<div class="radio">
							<label for="custom-backup-radio">
								<input type="radio" name="backupType" id="custom-backup-radio" value="2">
								<?php _backupGuardT('Custom backup'); ?>
							</label>
						</div>
						<div class="col-md-12 sg-custom-backup">
							<?php backupGuardGetBackupTablesHTML(); ?>
							<div class="checkbox sg-no-padding-top">
								<label for="custom-backupfiles-chbx">
									<input type="checkbox" class="sg-custom-option" name="backupFiles" id="custom-backupfiles-chbx">
									<span class="sg-checkbox-label-text"><?php _backupGuardT('Backup files'); ?></span>
								</label>
								<!--Files-->
								<div class="col-md-12 sg-checkbox sg-custom-backup-files">
									<?php foreach ($directories as $directory): ?>
										<div class="checkbox">
											<label for="<?php echo 'sgbg'.$directory?>">
												<input type="checkbox" name="directory[]" id="<?php echo 'sgbg'.$directory;?>" value="<?php echo $directory;?>">
												<span class="sg-checkbox-label-text"><?php echo basename($directory);?></span>
											</label>
										</div>
									<?php endforeach;?>
								</div>
							</div>
						</div>
						<div class="clearfix"></div>
						<?php if(SGBoot::isFeatureAvailable('STORAGE')): ?>
							<!--Cloud-->
							<div class="checkbox sg-no-padding-top">
								<label for="custom-backupcloud-chbx">
									<input type="checkbox" name="backupCloud" id="custom-backupcloud-chbx">
									<span class="sg-checkbox-label-text"><?php _backupGuardT('Upload to cloud'); ?></span>
								</label>
								<!--Storages-->
								<div class="col-md-12 sg-checkbox sg-custom-backup-cloud">
									<?php if(SGBoot::isFeatureAvailable('BACKUP_GUARD') && SG_SHOW_BACKUPGUARD_CLOUD): ?>
										<div class="checkbox">
											<label for="cloud-backup-guard" <?php echo empty($backupGuard)?'data-toggle="tooltip" data-placement="right" title="'._backupGuardT('BackupGuard is not active.',true).'"':''?>>
												<input type="checkbox" name="backupStorages[]" id="cloud-backup-guard" value="<?php echo SG_STORAGE_BACKUP_GUARD ?>" <?php echo empty($backupGuard)?'disabled="disabled"':''?>>
												<span class="sg-checkbox-label-text"><?php echo 'BackupGuard' ?></span>
											</label>
										</div>
									<?php endif; ?>
									<?php if(SGBoot::isFeatureAvailable('FTP')): ?>
										<div class="checkbox">
											<label for="cloud-ftp" <?php echo empty($ftp)?'data-toggle="tooltip" data-placement="right" title="'._backupGuardT('FTP is not active.',true).'"':''?>>
												<input type="checkbox" name="backupStorages[]" id="cloud-ftp" value="<?php echo SG_STORAGE_FTP ?>" <?php echo empty($ftp)?'disabled="disabled"':''?>>
												<span class="sg-checkbox-label-text"><?php echo 'FTP' ?></span>
											</label>
										</div>
									<?php endif; ?>
									<?php if(SGBoot::isFeatureAvailable('DROPBOX')): ?>
										<div class="checkbox">
											<label for="cloud-dropbox" <?php echo empty($dropbox)?'data-toggle="tooltip" data-placement="right" title="'._backupGuardT('Dropbox is not active.',true).'"':''?>>
												<input type="checkbox" name="backupStorages[]" id="cloud-dropbox" value="<?php echo SG_STORAGE_DROPBOX ?>"
													<?php echo empty($dropbox)?'disabled="disabled"':''?>>
												<span class="sg-checkbox-label-text"><?php echo 'Dropbox' ?></span>
											</label>
										</div>
									<?php endif; ?>
									<?php if(SGBoot::isFeatureAvailable('GOOGLE_DRIVE')): ?>
										<div class="checkbox">
											<label for="cloud-gdrive" <?php echo empty($gdrive)?'data-toggle="tooltip" data-placement="right" title="'._backupGuardT('Google Drive is not active.',true).'"':''?>>
												<input type="checkbox" name="backupStorages[]" id="cloud-gdrive" value="<?php echo SG_STORAGE_GOOGLE_DRIVE?>"
													<?php echo empty($gdrive)?'disabled="disabled"':''?>>
												<span class="sg-checkbox-label-text"><?php echo 'Google Drive' ?></span>
											</label>
										</div>
									<?php endif; ?>
									<?php if(SGBoot::isFeatureAvailable('AMAZON')): ?>
										<div class="checkbox">
											<label for="cloud-amazon" <?php echo empty($amazon)?'data-toggle="tooltip" data-placement="right" title="'._backupGuardT((backupGuardIsAccountGold()? 'Amazon ':'').'S3 is not active.',true).'"':''?>>
												<input type="checkbox" name="backupStorages[]" id="cloud-amazon" value="<?php echo SG_STORAGE_AMAZON?>"
													<?php echo empty($amazon)?'disabled="disabled"':''?>>
												<span class="sg-checkbox-label-text"><?php echo (backupGuardIsAccountGold()? 'Amazon ':'').'S3' ?></span>
											</label>
										</div>
									<?php endif; ?>
									<?php if(SGBoot::isFeatureAvailable('ONE_DRIVE')): ?>
										<div class="checkbox">
											<label for="cloud-one-drive" <?php echo empty($oneDrive)?'data-toggle="tooltip" data-placement="right" title="'._backupGuardT('One Drive is not active.', true).'"':''?>>
												<input type="checkbox" name="backupStorages[]" id="cloud-one-drive" value="<?php echo SG_STORAGE_ONE_DRIVE?>" <?php echo empty($oneDrive)?'disabled="disabled"':''?>>
												<span class="sg-checkbox-label-text"><?php echo 'One Drive' ?></span>
											</label>
										</div>
									<?php endif;?>
									<?php if(SGBoot::isFeatureAvailable('P_CLOUD')): ?>
                                        <div class="checkbox">
                                            <label for="cloud-p-cloud" <?php echo empty($pCloud)?'data-toggle="tooltip" data-placement="right" title="'._backupGuardT('pCloud is not active.', true).'"':''?>>
                                                <input type="checkbox" name="backupStorages[]" id="cloud-p-cloud" value="<?php echo SG_STORAGE_P_CLOUD?>" <?php echo empty($pCloud)?'disabled="disabled"':''?>>
                                                <span class="sg-checkbox-label-text"><?php echo 'pCloud' ?></span>
                                            </label>
                                        </div>
									<?php endif;?>
									<?php if(SGBoot::isFeatureAvailable('BOX')): ?>
                                        <div class="checkbox">
                                            <label for="cloud-box" <?php echo empty($box)?'data-toggle="tooltip" data-placement="right" title="'._backupGuardT('box.com is not active.', true).'"':''?>>
                                                <input type="checkbox" name="backupStorages[]" id="cloud-box" value="<?php echo SG_STORAGE_BOX?>" <?php echo empty($box)?'disabled="disabled"':''?>>
                                                <span class="sg-checkbox-label-text"><?php echo 'box.com' ?></span>
                                            </label>
                                        </div>
									<?php endif;?>
								</div>
								<div class="clearfix"></div>
							</div>
						<?php endif; ?>
						<!-- Background mode -->
						<?php if(SGBoot::isFeatureAvailable('BACKGROUND_MODE')): ?>
							<div class="checkbox">
								<label for="sg-background-chbx">
									<input type="checkbox" name="backgroundMode" id="sg-background-chbx">
									<span class="sg-checkbox-label-text"><?php _backupGuardT('Background mode'); ?></span><?php echo $infoIconHtml; ?>
									<span class="infoSelectRepeat samefontStyle sgbg-info-text"><?php _backupGuardT('Enable background mode to avoid CPU overload')?></span>
								</label>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<input type="text" name="backup-type" value="<?php echo $backupType?>" hidden>
				<span class="modal-close-button" data-dismiss="modal">Close</span>
				<button type="button" onclick="sgBackup.manualBackup()" class="btn btn-success"><?php _backupGuardT('Backup')?></button>
			</div>
		</form>
	</div>
</div>
