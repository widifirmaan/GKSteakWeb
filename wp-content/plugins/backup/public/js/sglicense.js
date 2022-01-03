jQuery(document).ready(function() {
	jQuery('#link-btn').click(function(event) {
		event.preventDefault();
		sgBackup.linkLicense();
	});
});

sgBackup.linkLicense = function()
{
	var productId = jQuery('#product').val();
	if (productId == '0') {
		alert(BG_LICENSE_STRINGS.invalidLicense);
		return;
	}

	var availableLicenses = jQuery("#product option:selected").attr('data-licenses');
	if (availableLicenses != 'Unlimited' && parseInt(availableLicenses) == 0) {
		alert(BG_LICENSE_STRINGS.availableLicenses);
		return;
	}

	sgBackup.showAjaxSpinner('#bg-wrapper');

	var ajaxHandler = new sgRequestHandler('link_license', {productId: productId, token: BG_BACKUP_STRINGS.nonce});
	ajaxHandler.callback = function(response) {
		sgBackup.hideAjaxSpinner();
		if (response == '0') {
			location.reload();
		}
		else {
			alert(response);
		}
	};
	ajaxHandler.run();
}
