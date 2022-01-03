// phpcs:ignoreFile
BG_SCHEDULE_INTERVAL_HOURLY = 0;
BG_SCHEDULE_INTERVAL_DAILY = 1;
BG_SCHEDULE_INTERVAL_WEEKLY = 2;
BG_SCHEDULE_INTERVAL_MONTHLY = 3;
BG_SCHEDULE_INTERVAL_YEARLY = 4;

jQuery(document).ready(
    function () {
        sgBackup.initTablePagination('sg-schedule');
        sgBackup.initScheduleCreation();
    }
);

sgBackup.initScheduleCreation = function () {
    sgBackup.initScheduleSwitchButtons();
    sgBackup.initManulBackupRadioInputs();
    sgBackup.initManualBackupTooltips();
    sgBackup.toggleDaySelection();
    sgBackup.initIntervalSelection();
}

sgBackup.removeSchedule = function (id) {
    var ajaxHandler = new sgRequestHandler('schedule', {remove: true, id: id});

    if (!confirm(BG_SCHEDULE_STRINGS.confirm)) {
        return false;
    }

    ajaxHandler.callback = function (response) {
        jQuery('.alert').remove();

        if (typeof response.success !== 'undefined') {
            location.reload();
        } else {
            //if error
            var alert = sgBackup.alertGenerator(BG_SCHEDULE_STRINGS.deletionError, 'alert-danger');
            jQuery('.sg-schedule-container legend').after(alert);
        }

        sgBackup.scrollToElement('.alert');
    };
    ajaxHandler.run();
}

sgBackup.initIntervalSelection = function () {
    if (jQuery('#sg-schedule-interval').val() == BG_SCHEDULE_INTERVAL_WEEKLY) {
        jQuery('#sg-schedule-day-of-week-select').show();
    } else if (jQuery('#sg-schedule-interval').val() == BG_SCHEDULE_INTERVAL_MONTHLY) {
        jQuery('#sg-schedule-day-of-month-select').show();
    } else if (jQuery('#sg-schedule-interval').val() == BG_SCHEDULE_INTERVAL_HOURLY) {
        jQuery('#sg-schedule-timezone').hide();
    }

    jQuery('#sg-schedule-interval').on(
        'change', function () {
            jQuery('#sg-schedule-timezone').show();
            if (jQuery(this).val() == BG_SCHEDULE_INTERVAL_WEEKLY) {
                jQuery('#sg-schedule-month-of-year-select').hide();
                jQuery('#sg-schedule-day-of-month-select').hide();
                jQuery('#sg-schedule-day-of-week-select').show();
            } else if (jQuery(this).val() == BG_SCHEDULE_INTERVAL_MONTHLY) {
                jQuery('#sg-schedule-month-of-year-select').hide();
                jQuery('#sg-schedule-day-of-week-select').hide();
                jQuery('#sg-schedule-day-of-month-select').show();
                sgBackup.updateDaysOfMonth();
            } else if (jQuery('#sg-schedule-interval').val() == BG_SCHEDULE_INTERVAL_HOURLY) {
                jQuery('#sg-schedule-timezone').hide();
            } else if (jQuery('#sg-schedule-interval').val() == BG_SCHEDULE_INTERVAL_DAILY) {
                sgBackup.toggleDaySelection();
            } else {
                jQuery('#sg-schedule-month-of-year-select').show();
                jQuery('#sg-schedule-day-of-week-select').hide();
                jQuery('#sg-schedule-day-of-month-select').show();
                sgBackup.updateDaysOfMonth();
            }
        }
    );
}

sgBackup.toggleDaySelection = function () {
    jQuery('#sg-schedule-day-of-week-select').hide();
    jQuery('#sg-schedule-day-of-month-select').hide();
    jQuery('#sg-schedule-month-of-year-select').hide();
}

sgBackup.updateDaysOfMonth = function () {
    let year = new Date().getFullYear();
    let interval = jQuery('#sg-schedule-interval').val();
    let month = '';

    if (interval == BG_SCHEDULE_INTERVAL_MONTHLY) {
        month = new Date().getMonth() + 1;
    } else {
        month = parseInt(jQuery('#sg-schedule-month-of-year').val());
    }

    let days = sgBackup.daysInMonth(year, month);

    jQuery("#sg-schedule-day-of-month option").show();

    jQuery("#sg-schedule-day-of-month option").each(
        function () {
            if (jQuery(this).val() > days) {
                jQuery(this).hide();
            }
        }
    );
}

sgBackup.prependErrorMsg = function (alert) {
    if (typeof jQuery('#sg-schedule-id').val() === 'undefined') {
        jQuery('.sg-schedule-container legend').after(alert);
    } else {
        jQuery('#sg-modal .modal-header').prepend(alert);
    }
}

//SGSchedule AJAX callback
sgBackup.schedule = function () {
    var error = [];
    var scheduleForm = jQuery('form[data-type=schedule]');

    //Validation
    jQuery('.alert').remove();
    if (jQuery('input[type=radio][name=backupType]:checked').val() == 2) {
        if (jQuery('.sg-custom-option:checked').length <= 0) {
            error.push(BG_SCHEDULE_STRINGS.invalidBackupOption);
        }
        //Check if any file is selected
        if (jQuery('input[type=checkbox][name=backupFiles]:checked').length > 0) {
            if (jQuery('.sg-custom-backup-files input:checkbox:checked').length <= 0) {
                error.push(BG_SCHEDULE_STRINGS.invalidDirectorySelected);
            }
        }
    }
    //Check if any cloud is selected
    if (jQuery('input[type=checkbox][name=backupCloud]:checked').length > 0) {
        if (jQuery('.sg-custom-backup-cloud input:checkbox:checked').length <= 0) {
            error.push(BG_SCHEDULE_STRINGS.invalidCloud);
        }
    }
    //If any error show it and abort ajax
    if (error.length) {
        var alert = sgBackup.alertGenerator(error, 'alert-danger');
        sgBackup.prependErrorMsg(alert);
        sgBackup.scrollToElement('.alert');
        return false;
    }

    //Before sending
    jQuery('#sg-save-schedule').attr('disabled', 'disabled');
    jQuery('#sg-save-schedule').html(BG_SCHEDULE_STRINGS.savingInProgress);

    //On Success
    var ajaxHandler = new sgRequestHandler('schedule', scheduleForm.serialize());
    ajaxHandler.dataIsObject = false;
    ajaxHandler.callback = function (response) {
        jQuery('.alert').remove();
        if (typeof response.success !== 'undefined') {
            var alert = sgBackup.alertGenerator(BG_SCHEDULE_STRINGS.successMessage, 'alert-success');
            sgBackup.prependErrorMsg(alert);
            location.reload();
        } else {
            //if error
            var alert = sgBackup.alertGenerator(response, 'alert-danger');
            sgBackup.prependErrorMsg(alert);
        }

        //Always
        jQuery('#sg-save-schedule').removeAttr('disabled', 'disabled');
        jQuery('#sg-save-schedule').html(BG_SCHEDULE_STRINGS.saveButtonText);
        sgBackup.scrollToElement('.alert');
    };
    ajaxHandler.run();
};

sgBackup.initScheduleSwitchButtons = function () {
    jQuery('.sg-switch').bootstrapSwitch();
    if (jQuery('#sg-backup-page-content-schedule  .sg-switch').is(':checked')) {
        jQuery('#sg-backup-page-content-schedule  .sg-schedule-settings').show();
    }
    jQuery('.sg-switch').on(
        'switchChange.bootstrapSwitch', function (event, state) {
            var url = jQuery(this).attr('data-remote');
            if (state) {
                var isFeatureAvailable = new sgRequestHandler('isFeatureAvailable', {sgFeature: "SCHEDULE"});
                isFeatureAvailable.callback = function (response) {
                    jQuery('.alert').remove();
                    if (typeof response.success !== 'undefined') {
                        //Show or Hide settings panel
                        jQuery('.sg-schedule-settings').fadeIn();
                    } else {
                        var alert = sgBackup.alertGenerator(response.error, 'alert-warning');
                        jQuery('.sg-schedule-container legend').after(alert);
                        jQuery('.sg-switch').bootstrapSwitch('state', false);
                    }
                }

                isFeatureAvailable.run();
            } else {
                var ajaxHandler = new sgRequestHandler('schedule', {remove: true});
                ajaxHandler.run();
                jQuery('.sg-schedule-settings').fadeOut();
            }
        }
    );
};

sgBackup.initManulBackupRadioInputs = function () {
    jQuery('input[type=radio][name=backupType]').off('change').on(
        'change', function () {
            jQuery('.sg-custom-backup').fadeToggle();
            jQuery('.sg-custom-backup').children().find('input[class^=sg-custom]').removeAttr('checked');
            jQuery('.sg-custom-backup-files').hide();
        }
    );
    jQuery('input[type=checkbox][name=backupFiles], input[type=checkbox][name=backupCloud], input[type=checkbox][name=backupDatabase]').off('change').on(
        'change', function () {
            var sgCheckBoxWrapper = jQuery(this).closest('.checkbox').find('.sg-checkbox');
            sgCheckBoxWrapper.fadeToggle();
            if (jQuery(this).attr('name') == 'backupFiles') {
                sgCheckBoxWrapper.find('input[type=checkbox]').attr('checked', 'checked');
            }
        }
    );
    jQuery('input[type=radio][name=backupDBType]').off('change').on(
        'change', function () {
            var sgCheckBoxWrapper = jQuery(this).closest('.checkbox').find('.sg-custom-backup-tables');
            if (jQuery('input[type=radio][name=backupDBType]:checked').val() == '2') {
                sgCheckBoxWrapper.find('input[type=checkbox]').not("[disabled]").prop('checked', true)
                sgCheckBoxWrapper.fadeIn();
            } else {
                sgCheckBoxWrapper.fadeOut();
                sgCheckBoxWrapper.find('input[type=checkbox][current="true"]').not("[disabled]").prop('checked', true)
                sgCheckBoxWrapper.find('input[type=checkbox][current="false"]').prop('checked', false)
            }
        }
    )
}

sgBackup.initManualBackupTooltips = function () {
    jQuery('[for=cloud-ftp]').tooltip();
    jQuery('[for=cloud-dropbox]').tooltip();
    jQuery('[for=cloud-gdrive]').tooltip();
    jQuery('[for=cloud-one-drive]').tooltip();
    jQuery('[for=cloud-p-cloud]').tooltip();
    jQuery('[for=cloud-box]').tooltip();
    jQuery('[for=cloud-amazon]').tooltip();
    jQuery('[for=cloud-backup-guard]').tooltip();
}
