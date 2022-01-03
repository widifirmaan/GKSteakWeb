<?php
global $current_user;
$websiteName = get_bloginfo();
?>

<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h4 class="modal-title"><?php _backupGuardT('BackupGuard')?></h4>
		</div>
		<form class="form-horizontal" id="backupGuardDetailsModal" data-sgform="ajax">
			<div class="modal-body sg-modal-body">
                <div class="hide sgbg-connection-message bg-invalid-login">
                    Invalid login info. Please try again.
                </div>
                <div class="hide sgbg-connection-message bg-email-confirmation">
                    We've sent you a confirmation e-mail. Please activate your account to get started. If you didn't receive it, please <a href="<?php echo SG_BACKUP_SUPPORT_URL; ?>" target="_blank">contact us</a>.
                </div>
                <div class="sgbg-connection-message bg-welcome-message">
                    Please enter your BackupGuard e-mail address. If you don't have an account yet, we will create it for you.
                </div>
                <div class="hide sgbg-connection-message bg-loggin-form-message">
                    Account found. Please enter your password to continue. <a href="<?php echo SG_FORGOT_PASSWORD_URL; ?>" target="_blank">Forgot your password?</a>
                </div>
                <div class="hide sgbg-connection-message bg-register-form-message">
                    No account was found with the provided e-mail address. Please enter your full name to create a new account.
                </div>
                <div class="hide sgbg-connection-message bg-create-form-message">
                    Now we need to setup your website's profile. Please enter a new profile name for your website.
                </div>
				<div class="col-md-12">
					<div id="bg-email-container" class="form-group">
						<label class="col-md-3 control-label" for="email"><?php _backupGuardT('Email')?></label>
						<div class="col-md-8">
							<input id="email" name="email" type="text" class="form-control input-md sg-backup-input sg-backup-input-email" placeholder="<?php _backupGuardT('Email')?>" autocomplete="off">
						</div>
					</div>
					<div id="bg-firstname-container" class="form-group hidden">
						<label class="col-md-3 control-label" for="firstname"><?php _backupGuardT('First Name')?></label>
						<div class="col-md-8">
							<input id="firstname" name="firstname" type="text" class="form-control input-md sg-backup-input" placeholder="<?php _backupGuardT('First Name')?>" value="<?php echo $current_user->user_firstname?>" autocomplete="off">
						</div>
					</div>
					<div id="bg-lastname-container" class="form-group hidden">
						<label class="col-md-3 control-label" for="lastname"><?php _backupGuardT('Last Name')?></label>
						<div class="col-md-8">
							<input id="lastname" name="lastname" type="text" class="form-control input-md sg-backup-input" placeholder="<?php _backupGuardT('Last Name')?>" value="<?php echo $current_user->user_lastname?>" autocomplete="off">
						</div>
					</div>

					<div id="bg-password-container" class="form-group hidden">
						<label class="col-md-3 control-label" for="password"><?php _backupGuardT('Password')?></label>
						<div class="col-md-8">
							<input id="password" name="password" type="password" class="form-control input-md sg-backup-input" placeholder="<?php _backupGuardT('Password')?>" autocomplete="off">
						</div>
					</div>
					<div id="bg-profiles-container" class="form-group hidden">
						<div class="row bg-select-profile hidden">
							<label class="col-md-3 control-label" for="password"><?php _backupGuardT('Profiles')?></label>
							<div class="col-md-8">
								<select id="bg-profiles" name="bg-profiles" class="form-control input-md">
									<option value="0"><?php _backupGuardT('Create New')?></option>
								</select>
							</div>
						</div>
						<br>
						<div id="bg-profile-name-container" class="row">
							<label class="col-md-3 control-label" for="bg-profile-name"><?php _backupGuardT('Profile name')?></label>
							<div class="col-md-8">
								<input id="bg-profile-name" name="bg-profile-name" type="text" class="form-control input-md sg-backup-input" placeholder="<?php _backupGuardT('Profile name')?>" value="<?php echo esc_attr($websiteName); ?>">
							</div>
						</div>
					</div>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="modal-footer">
                <span id="bg-close-button" class="modal-close-button" data-dismiss="modal"><?php _backupGuardT('Close')?></span>
				<button id="bg-connect-account" type="button" class="btn btn-success" onclick="sgBackup.backupGuardConnect()"><?php _backupGuardT('Connect')?></button>
				<button id="bg-login" type="button" class="btn btn-success hidden" onclick="sgBackup.backupGuardLogin()"><?php _backupGuardT('Login')?></button>
				<button id="bg-create-account" type="button" class="btn btn-success hidden" onclick="sgBackup.createCloudUser()"><?php _backupGuardT('Create Account')?></button>
				<button id="bg-choose-profile" type="button" class="btn btn-success hidden" onclick="sgBackup.chooseProfile()"><?php _backupGuardT('Choose')?></button>
			</div>
		</form>
	</div>
</div>
