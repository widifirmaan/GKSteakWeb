jQuery(document).on('click', '.backup-guard-discount-notice .notice-dismiss', function() {
	jQuery.ajax({
		url: ajaxurl,
		data: {
			action: 'backup_guard_dismiss_discount_notice',
			token: BG_BACKUP_STRINGS.nonce
		}
	})
});
