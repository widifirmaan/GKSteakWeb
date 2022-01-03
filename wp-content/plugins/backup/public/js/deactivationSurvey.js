var deactivationUrl = "";

jQuery("tr[data-slug='backupguard-pro'] .deactivate a").click(function() {
	event.preventDefault();
	deactivationUrl = jQuery(this).attr("href");
	jQuery("#bg-deactivation-survey-popup-container").show();
});

jQuery("tr[data-slug='backup'] .deactivate a").click(function() {
	event.preventDefault();
	deactivationUrl = jQuery(this).attr("href");
	jQuery("#bg-deactivation-survey-popup-container").show();
});

/*
jQuery('.bg-deactivation-survey-popup-cancel').click(function(e) {
	jQuery('.bg-deactivation-survey-popup-container').slideUp();
	jQuery('.bg-deactivation-survey-popup-overlay').hide();
});
*/

jQuery("#bg-close-and-deactivate").on("click", function () {
	event.preventDefault();
	
	var data = {
		action: 'backup_guard_storeSurveyResult',
		skip: 'skip',
		token: BG_BACKUP_STRINGS.nonce
	};

	jQuery.post(ajaxurl, data, function(response) {
	}).always(function() {
		window.location.replace(deactivationUrl);
	});
});

jQuery('#bg-deactivation-survey-reason').on('change', function () {
	if (jQuery(this).val() == "custom") {
		jQuery('#bg-deactivation-survey-reason-custom').show();
	}
	else {
		jQuery('#bg-deactivation-survey-reason-custom').hide();
	}
});

jQuery('.bg-deactivation-survey-result-submit').click(function(e) {
	e.preventDefault();

	var email = jQuery('#bg-deactivation-survey-email').val();
	var firstname = jQuery.trim(jQuery('#bg-deactivation-survey-first-name').val());
	var lastname = jQuery.trim(jQuery('#bg-deactivation-survey-last-name').val());
	var reason = jQuery.trim(jQuery('#bg-deactivation-survey-reason').val());

	var sendData = true;
	jQuery('.bg-deactivation-survey-popup-error-message').hide();

	if (reason == "custom") {
		reason = jQuery('#bg-deactivation-survey-reason-custom').val();

		if (!reason) {
			jQuery('#bg-deactivation-survey-reason-custom-error').show();
			sendData = false;
		}
	}
	else if (!reason) {
		jQuery('#bg-deactivation-survey-reason-error').show();
		sendData = false;
	}

	if(firstname == '') {
		jQuery('#bg-deactivation-survey-first-name-error').show();
		sendData = false;
	}

	if(lastname == '') {
		jQuery('#bg-deactivation-survey-last-name-error').show();
		sendData = false;
	}

	if (!isValidEmailAddress(email)) {
		jQuery('#bg-deactivation-survey-email-error').show();
		sendData = false;
	}

	if(sendData) {
		jQuery('.bg-deactivation-survey-popup-container').slideUp();
		jQuery('.bg-deactivation-survey-popup-overlay').hide();

		var data = {
			action: 'backup_guard_storeSurveyResult',
			email: email,
			firstname: firstname,
			lastname: lastname,
			response: reason,
			token: BG_BACKUP_STRINGS.nonce
		};

		jQuery.post(ajaxurl, data, function(response) {
			window.location.replace(deactivationUrl);
		});
	}
});

function isValidEmailAddress(emailAddress) {
    var pattern = new RegExp(/^(("[\w-+\s]+")|([\w-+]+(?:\.[\w-+]+)*)|("[\w-+\s]+")([\w-+]+(?:\.[\w-+]+)*))(@((?:[\w-+]+\.)*\w[\w-+]{0,66})\.([a-z]{2,10}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][\d]\.|1[\d]{2}\.|[\d]{1,2}\.))((25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\.){2}(25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\]?$)/i);
    return pattern.test(emailAddress);
}
