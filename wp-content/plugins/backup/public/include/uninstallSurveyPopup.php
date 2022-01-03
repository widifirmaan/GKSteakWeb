<div id="bg-deactivation-survey-popup-container" class="bg-deactivation-survey-popup-container is-dismissible" style="display: none;">
	<div class="bg-deactivation-survey-popup-overlay"></div>

 	<div class="bg-deactivation-survey-popup-tbl">
		<div class="bg-deactivation-survey-popup-cel">
			<div class="bg-deactivation-survey-popup-content">
				<a href="javascript:void(0)" class="bg-deactivation-survey-popup-cancel" id="bg-close-and-deactivate"><img src="<?php echo SG_IMAGE_URL."close.png" ?>" class="wp_fm_loader"></a>
				<div class="bg-deactivation-survey-popup-inner-content">
					<h3>We are sorry to see that you intend to</h3>
					<h3>deactivate BackupGuard plugin.</h3>
					<p class="bg-deactivation-survey-popup-desc">
						Please, tell us why you want to deactivate BackupGuard plugin in order to improve our product.
					</p>
					<form>
						<div class="bg-deactivation-survey-popup-form-group">
							<div class="bg-deactivation-survey-popup-form-twocol">
								<input name="bg-deactivation-survey-first-name" id="bg-deactivation-survey-first-name" class="regular-text" type="text" value="" placeholder="First Name">
								<span id="bg-deactivation-survey-first-name-error" class="bg-deactivation-survey-popup-error-message">Please Enter First Name.</span>
							</div>
							<div class="bg-deactivation-survey-popup-form-twocol">
								<input name="bg-deactivation-survey-last-name" id="bg-deactivation-survey-last-name" class="regular-text" type="text" value="" placeholder="Last Name">
								<span id="bg-deactivation-survey-last-name-error" class="bg-deactivation-survey-popup-error-message">Please Enter Last Name.</span>
							</div>
						</div>
						<div class="bg-deactivation-survey-popup-form-group">
							<div class="bg-deactivation-survey-popup-form-onecol">
								<input name="bg-deactivation-survey-email" id="bg-deactivation-survey-email" class="regular-text" type="text" value="" placeholder="Email Address">
								<span id="bg-deactivation-survey-email-error" class="bg-deactivation-survey-popup-error-message">Please Enter Valid Email Address.</span>
							</div>
						</div>
						<div class="bg-deactivation-survey-popup-form-group">
							<div class="bg-deactivation-survey-popup-form-onecol">
								<select name="bg-deactivation-survey-reason" id="bg-deactivation-survey-reason">
									<option value="">Please Select Deactivation Reason</option>
									<option value="It was not what I expected.">It was not what I expected.</option>
									<option value="I didn't find it to be useful">I didn't find it to be useful</option>
									<option value="I don't like the design of the dashboard">I don't like the design of the dashboard</option>
									<option value="Privacy concerns">Privacy concerns</option>
									<option value="custom">Performance</option>
									<option value="custom">Missing features</option>
									<option value="custom">Other</option>
								</select>
								<span id="bg-deactivation-survey-reason-error" class="bg-deactivation-survey-popup-error-message">Please Select Deactivation Reason.</span>
							</div>
						</div>
						<div class="bg-deactivation-survey-popup-form-group">
							<div class="bg-deactivation-survey-popup-form-onecol">
								<textarea name="bg-deactivation-survey-reason-custom" id="bg-deactivation-survey-reason-custom" class="regular-text" placeholder="Please describe the issue in more details" hidden></textarea>
								<span id="bg-deactivation-survey-reason-custom-error" class="bg-deactivation-survey-popup-error-message">Please Enter Deactivation Reason.</span>
							</div>
						</div>
						<div class="bg-deactivation-survey-popup-control-buttons-container">
							<button class="bg-deactivation-survey-result-submit button button-primary">Submit</button>
							<!-- <button id="bg-skip-and-deactivate" class="bg-deactivation-survey-popup-cancel button button-secondary">Skip and deactivate</button> -->
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
