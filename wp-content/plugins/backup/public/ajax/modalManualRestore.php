<?php
	require_once(dirname(__FILE__).'/../boot.php');
	require_once(SG_LIB_PATH.'SGArchive.php');
	$backupName = $_GET['param'];
	$backupName = backupGuardRemoveSlashes($backupName);
	$backupPath = SG_BACKUP_DIRECTORY.$backupName;
	$backupPath= $backupPath.'/'.$backupName.'.sgbp';

	$archive = new SGArchive($backupPath,'r');
	$headers = $archive->getArchiveHeaders();
	if($headers["selectivRestoreable"]){
		?>
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<h4 class="modal-title"><?php _backupGuardT("Manual Restore") ?></h4>
				</div>
				<form class="form-horizontal" method="post" id="manualBackup">
					<div class="modal-body sg-modal-body">
						<!-- Multiple Radios -->
						<div class="form-group">
							<div class="col-md-12">
								<div class="radio">
									<label for="fullrestore-radio">
										<input type="radio" name="restoreType" id="fullrestore-radio" value="<?php echo SG_RESTORE_MODE_FULL ?>" checked="checked">
										<?php _backupGuardT('Full restore'); ?>
									</label>
								</div>
								<div class="radio">
									<label for="filerestore-radio">
										<input type="radio" name="restoreType" id="filerestore-radio" value="<?php echo SG_RESTORE_MODE_FILES ?>">
										<?php _backupGuardT('Restore files'); ?>
									</label>
								</div>
								<?php if (SGBoot::isFeatureAvailable('SLECTIVE_RESTORE')): ?>
									<?php backupGuardGetFileSelectiveRestore(); ?>
								<?php endif; ?>
								<div class="radio">
									<label for="dbrestore-radio">
										<input type="radio" name="restoreType" id="dbrestore-radio" value="<?php echo SG_RESTORE_MODE_DB ?>">
										<?php _backupGuardT('Restore DB'); ?>
									</label>
								</div>

							</div>
						</div>
					</div>
					<div class="modal-footer">
						<span class="modal-close-button" data-dismiss="modal">Close</span>
						<button type="button" onclick="sgBackup.startRestore('<?php echo addslashes(htmlspecialchars($backupName)) ?>')" class="btn btn-success"><?php _backupGuardT('Restore')?></button>
					</div>
				</form>
			</div>
		</div>
	<?php
	}else{
		?>
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<h4 class="modal-title"><?php _backupGuardT("Are you sure?") ?></h4>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					<button type="button" onclick="sgBackup.startRestore('<?php echo htmlspecialchars($backupName) ?>')" class="btn btn-primary"><?php _backupGuardT('Restore')?></button>
				</div>
			</div>
		</div>
		<?php
	}


