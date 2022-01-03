/* phpcs:disable */

BG_BACKUP_STRINGS_DEACTIVATE = [];
SG_DOWNLOAD_PROGRESS = '';
SG_ACTIVE_DOWNLOAD_AJAX = '';
SG_CHECK_ACTION_STATUS_REQUEST_FREQUENCY = 10000;

SG_STORAGE_FTP = 1;
SG_STORAGE_DROPBOX = 2;
SG_STORAGE_GOOGLE_DRIVE = 3;
SG_STORAGE_AMAZON = 4;
SG_STORAGE_ONE_DRIVE = 5;
SG_STORAGE_BACKUP_GUARD = 6;
SG_STORAGE_P_CLOUD = 7;
SG_STORAGE_BOX = 8;

BG_BACKUP_STRINGS_DEACTIVATE.areYouSure = "Are you sure?";


jQuery(document).on(
    'change',
    '.btn-file :file',
    function () {
        var input = jQuery(this);

        if (input.get(0).files) {
            var numFile = input.get(0).files.length
        } else {
            var numFiles = 1;
        }

        var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
        input.trigger('fileselect', [numFiles, label]);
    }
);

jQuery(document).ready(
    function () {
        sgBackup.initTablePagination('sg-backups');
        sgBackup.initActiveAction();
        sgBackup.initBackupDeletion();
        sgBackup.toggleMultiDeleteButton();
        sgBackup.closeFreeBanner();
        sgBackup.closeChristmasBanner();

        jQuery('span[data-toggle=tooltip]').tooltip();

        jQuery('#sg-checkbox-select-all').on(
            'change',
            function () {
                var checkAll = jQuery('#sg-checkbox-select-all');
                jQuery('tbody input[type="checkbox"]:not(:disabled):visible').prop('checked', checkAll.prop('checked'));
                sgBackup.toggleMultiDeleteButton();
            }
        );

        jQuery('#sg-delete-multi-backups').on(
            'click',
            function () {
                if (!confirm(BG_BACKUP_STRINGS_DEACTIVATE.areYouSure)) {
                    return false;
                }

                var backups = jQuery('tbody input[type="checkbox"]:checked');
                var backupNames = [];
                backups.each(
                    function (i) {
                        backupNames[i] = jQuery(this).val();
                    }
                );

                if (backupNames.length) {
                    sgBackup.deleteMultiBackups(backupNames);
                }
            }
        );

        jQuery('tbody input[type="checkbox"]').on(
            'change',
            function () {
                var numberOfBackups = jQuery('tbody input[type="checkbox"]').length;
                var numberOfChoosenBackups = sgBackup.getSelectedBackupsNumber();
                var isCheked = jQuery(this).is(':checked');
                sgBackup.toggleMultiDeleteButton();

                if (!isCheked) {
                    jQuery('#sg-checkbox-select-all').prop('checked', false);
                } else {
                    if (numberOfBackups === numberOfChoosenBackups) {
                        jQuery('#sg-checkbox-select-all').prop('checked', true);
                    }
                }
            }
        );

        var surveyClose = function (e) {
            e.preventDefault();

            jQuery('.bg-verify-user-info-container').slideUp();
            jQuery('.bg-verify-user-info-overlay').hide();

            var ajaxHandler = new sgRequestHandler('setUserInfoVerificationPopupState', {token: BG_BACKUP_STRINGS.nonce});
            ajaxHandler.run();
        };

        jQuery('.bg-verify-user-info-overlay').bind('click', surveyClose);

        jQuery('.bg-verify-user-info-cancel').click(surveyClose);

        jQuery('#bg-verify-user-prioraty').on(
            'change',
            function () {
                if (jQuery(this).val() === "other") {
                    jQuery('#bg-verify-user-priorati-custom').show();
                } else {
                    jQuery('#bg-verify-user-priorati-custom').hide();
                }
            }
        );

        jQuery('.bg-verify-user-info-verify-email').click(
            function (e) {
                e.preventDefault();

                var email = jQuery('#bg-verify-user-info-email').val();
                var fname = jQuery('#bg-verify-user-info-name').val();
                var lname = jQuery('#bg-verify-user-info-last-name').val();
                var priority = jQuery('#bg-verify-user-prioraty').val();

                var sendData = true;
                jQuery('.bg-verify-user-info-error-message').hide();

                if (priority === "other") {
                    priority = jQuery('#bg-verify-user-priorati-custom').val();

                    if (!priority) {
                        jQuery('#bg-verify-user-info-priority-custom-error').show()
                        sendData = false;
                    }
                } else if (!priority) {
                    jQuery('#bg-verify-user-info-priority-error').show()
                    sendData = false;
                }

                if (fname === '') {
                    jQuery('#bg-verify-user-info-name-error').show();
                    sendData = false;
                }

                if (lname === '') {
                    jQuery('#bg-verify-user-info-last-name-error').show();
                    sendData = false;
                }

                if (!sgBackup.isValidEmailAddress(email)) {
                    jQuery('#bg-verify-user-info-email-error').show();
                    sendData = false;
                }

                if (sendData) {
                    jQuery('.bg-verify-user-info-container').slideUp();
                    jQuery('.bg-verify-user-info-overlay').hide();
                    var currentStatus = jQuery('.backup-send-usage-data-status').is(':checked');

                    var ajaxHandler = new sgRequestHandler(
                        'storeSubscriberInfo',
                        {
                            email: email,
                            fname: fname,
                            lname: lname,
                            priority: priority,
                            currentStatus: currentStatus,
                            token: BG_BACKUP_STRINGS.nonce
                        }
                    );

                    ajaxHandler.run();
                }
            }
        );
    }
);

sgBackup.isValidEmailAddress = function (emailAddress) {
    var pattern = new RegExp(/^(("[\w-+\s]+")|([\w-+]+(?:\.[\w-+]+)*)|("[\w-+\s]+")([\w-+]+(?:\.[\w-+]+)*))(@((?:[\w-+]+\.)*\w[\w-+]{0,66})\.([a-z]{2,10}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][\d]\.|1[\d]{2}\.|[\d]{1,2}\.))((25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\.){2}(25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\]?$)/i);
    return pattern.test(emailAddress);

};

sgBackup.getSelectedBackupsNumber = function () {
    return jQuery('tbody input[type="checkbox"]:checked').length

};

sgBackup.toggleMultiDeleteButton = function () {
    var numberOfChoosenBackups = sgBackup.getSelectedBackupsNumber();
    var target = jQuery('#sg-delete-multi-backups');
    if (numberOfChoosenBackups > 0) {
        target.removeAttr('disabled');
    } else {
        target.attr('disabled', 'disabled');
    }

};

sgBackup.closeFreeBanner = function () {
    jQuery('.sg-close-free-banner').bind(
        'click',
        function () {
            var ajaxHandler = new sgRequestHandler(
                'closeFreeBanner',
                {
                    token: BG_BACKUP_STRINGS.nonce
                }
            );
            ajaxHandler.callback = function (response, error) {
                jQuery('#sg-banner').remove();
            };
            ajaxHandler.run();
        }
    );

};

sgBackup.closeChristmasBanner = function () {
    jQuery('.sg-close-christmas-banner').bind(
        'click',
        function () {
            var ajaxHandler = new sgRequestHandler(
                'closeChristmasBanner',
                {
                    token: BG_BACKUP_STRINGS.nonce
                }
            );
            ajaxHandler.callback = function (response, error) {
                jQuery('#sg-christmas-banner').remove();
            };
            ajaxHandler.run();
        }
    );
};

sgBackup.deleteMultiBackups = function (backupNames) {
    var ajaxHandler = new sgRequestHandler('deleteBackup', {backupName: backupNames, token: BG_BACKUP_STRINGS.nonce});
    ajaxHandler.callback = function (response) {
        location.reload();
    };
    ajaxHandler.run();

};

// SGManual Backup AJAX callback.
sgBackup.manualBackup = function (checkedStatus) {
    var error = [];
    // Validation.
    jQuery('.alert').remove();
    if (jQuery('input[type=radio][name=backupType]:checked').val() === 2) {
        if (jQuery('.sg-custom-option:checked').length <= 0) {
            error.push(BG_BACKUP_STRINGS.invalidBackupOption);
        }

        // Check if any file is selected.
        if (jQuery('input[type=checkbox][name=backupFiles]:checked').length > 0) {
            if (jQuery('.sg-custom-backup-files input:checkbox:checked').length <= 0) {
                error.push(BG_BACKUP_STRINGS.invalidDirectorySelected);
            }
        }
    }

    // Check if any cloud is selected.
    if (jQuery('input[type=checkbox][name=backupCloud]:checked').length > 0) {
        if (jQuery('.sg-custom-backup-cloud input:checkbox:checked').length <= 0) {
            error.push(BG_BACKUP_STRINGS.invalidCloud);
        }
    }

    // If any error show it and abort ajax.
    if (error.length) {
        var sgAlert = sgBackup.alertGenerator(error, 'alert-danger');
        jQuery('#sg-modal .modal-header').prepend(sgAlert);
        return false;
    }

    if (!checkedStatus) {
        sgBackup.checkBackupCreation();
    }

    // Before all disable buttons.
    jQuery('.alert').remove();
    jQuery('.modal-footer .btn-primary').attr('disabled', 'disabled');
    jQuery('.modal-footer .btn-primary').html(BG_BACKUP_STRINGS.backupInProgress);

    // Reset Status!
    var backupName = jQuery("#sg-custom-backup-name").val();
    var resetStatusHandler = new sgRequestHandler(
        'resetStatus',
        {
            backupName: backupName,
            token: BG_BACKUP_STRINGS.nonce
        }
    );
    resetStatusHandler.callback = function (response, error) {
        var manualBackupForm = jQuery('#manualBackup');
        var manualBackupHandler = new sgRequestHandler('manualBackup', manualBackupForm.serialize() + '&token=' + BG_BACKUP_STRINGS.nonce);

        manualBackupHandler.dataIsObject = false;
        // If error!
        if (typeof response.success === 'undefined') {
            var sgAlert = sgBackup.alertGenerator(response, 'alert-danger');
            jQuery('#sg-modal .modal-header').prepend(sgAlert);

            if (response === 0 || response === false || response === '0' || response === 'false') {
                response = BG_BACKUP_STRINGS.errorMessage;
            }

            sgBackup.restManualBackupModal();
            return false;
        }

        if (checkedStatus) {
            sgBackup.hideAndReload();
        }

        manualBackupHandler.run();
    };
    resetStatusHandler.run();

};

sgBackup.hideAndReload = function () {
    jQuery('#sg-modal').modal('hide');
    location.reload();

};

sgBackup.restManualBackupModal = function () {
    jQuery('.modal-footer .btn-primary').removeAttr('disabled');
    jQuery('.modal-footer .btn-primary').html('Backup');

};

sgBackup.cancelDonwload = function (name) {
    var cancelDonwloadHandler = new sgRequestHandler('cancelDownload', {name: name, token: BG_BACKUP_STRINGS.nonce});
    cancelDonwloadHandler.callback = function (response) {
        sgBackup.hideAjaxSpinner();
        location.reload();
    };
    cancelDonwloadHandler.run();

};

sgBackup.listStorage = function (importFrom) {
    var listStorage = new sgRequestHandler('listStorage', {storage: importFrom, token: BG_BACKUP_STRINGS.nonce});
    sgBackup.showAjaxSpinner('#sg-modal-inport-from');
    jQuery('#sg-archive-list-table tbody').empty();

    jQuery('#sg-modal').off('hide.bs.modal').on(
        'hide.bs.modal',
        function (e) {
            if (SG_ACTIVE_DOWNLOAD_AJAX) {
                if (!confirm(BG_BACKUP_STRINGS.confirm)) {
                    e.preventDefault();
                    return false;
                }

                var target = jQuery('input[name="select-archive-to-download"]:checked');
                var name = target.attr('file-name');

                sgBackup.cancelDonwload(name);
            }
        }
    );

    listStorage.callback = function (response, error) {
        var cloudName = '';
        var cloudId = parseInt(importFrom, 10);

        switch (cloudId) {
        case SG_STORAGE_AMAZON:
            cloudName = "S3";
                break;

        case SG_STORAGE_DROPBOX:
            cloudName = "Dropbox";
                break;

        case SG_STORAGE_GOOGLE_DRIVE:
            cloudName = "Google Drive";
                break;

        case SG_STORAGE_FTP:
            cloudName = "FTP";
                break;

        case SG_STORAGE_ONE_DRIVE:
            cloudName = "OneDrive";
                break;

        case SG_STORAGE_P_CLOUD:
            cloudName = "pCloud";
                break;

        case SG_STORAGE_BOX:
            cloudName = "box.com";
                break;

        case SG_STORAGE_BACKUP_GUARD:
            cloudName = "BackupGuard";
                break;

        default:
            cloudName = '';
                break;
        }//end switch

        jQuery('.modal-title').html('Import from ' + cloudName);

        sgBackup.hideAjaxSpinner();
        var content = '';
        if ((typeof response.error !== "undefined") || response.length === 0 || response === undefined) {
            content = '<tr><td colspan="4">' + BG_BACKUP_STRINGS.noBackupsAvailable + '</td></tr>';
        } else {
            jQuery.each(
                response,
                function (key, value) {
                    var backupId = 0;

                    if (typeof value.id !== 'undefined') {
                        backupId = value.id;
                        value.path = value.name;
                    }

                    content += '<tr>';
                    content += '<td class="file-select-radio"><input type="radio" file-name="' + value.name + '" name="select-archive-to-download" size="' + value.size + '" backup-id="' + backupId + '" storage="' + importFrom + '" value="' + value.path + '"></td>';
                    content += '<td>' + value.name + '</td>';
                    content += '<td>' + sgBackup.convertBytesToMegabytes(value.size) + '</td>';
                    content += '<td>' + value.date + '</td>';
                    content += '</tr>';
                }
            );
        }//end if

        jQuery('#sg-archive-list-table tbody').append(content);
        sgBackup.toggleDownloadFromCloudPage();
    };

    listStorage.run();

};


sgBackup.convertBytesToMegabytes = function ($bytes) {
    return ($bytes / (1024 * 1024)).toFixed(2);

};

// Init file upload!
sgBackup.initFileUpload = function () {
    sgBackup.downloadFromPC();

    jQuery('#uploadSgbpFile').click(
        function () {
            if (jQuery('#modal-import-3').is(":visible")) {
                var target = jQuery('input[name="select-archive-to-download"]:checked');
                var path = target.val();
                var name = target.attr('file-name');
                var storage = target.attr('storage');
                var size = target.attr('size');
                var backupId = target.attr('backup-id');
                sgBackup.downloadFromCloud(path, name, storage, size, backupId);
            }
        }
    );

};

sgBackup.nextPage = function () {
    var importFrom = jQuery('input[name="storage-radio"]:checked').val();
    jQuery('.alert').remove();

    if (!importFrom) {
        var alert = sgBackup.alertGenerator(BG_BACKUP_STRINGS.invalidImportOption, 'alert-danger');
        jQuery('#sg-modal .modal-header').prepend(alert);
    } else {
        if (importFrom === 'local-pc') {
            sgBackup.toggleDownloadFromPCPage();
        } else {
            var isFeatureAvailable = new sgRequestHandler('isFeatureAvailable', {sgFeature: "DOWNLOAD_FROM_CLOUD"});
            isFeatureAvailable.callback = function (response) {
                if (typeof response.success !== 'undefined') {
                    sgBackup.listStorage(importFrom);
                } else {
                    var alert = sgBackup.alertGenerator(response.error, 'alert-danger');
                    jQuery('#sg-modal .modal-header').prepend(alert);
                }
            };

            isFeatureAvailable.run();
        }
    }

};

sgBackup.previousPage = function () {
    if (jQuery('#modal-import-2').is(":visible")) {
        jQuery('#modal-import-2').hide();
    } else {
        jQuery('#modal-import-3').hide();
    }

    sgBackup.toggleNavigationButtons();

    jQuery('#modal-import-1').show();
    jQuery('#uploadSgbpFile').hide();

    jQuery('.modal-title').html('Import from');

};

sgBackup.toggleNavigationButtons = function () {
    jQuery('#switch-modal-import-pages-next').toggle();
    jQuery('#switch-modal-import-pages-back').toggle();

};

sgBackup.toggleDownloadFromPCPage = function () {
    sgBackup.toggleNavigationButtons();
    jQuery('#modal-import-1').toggle();
    jQuery('#modal-import-2').toggle();
    jQuery('#uploadSgbpFile').toggle();

};

sgBackup.toggleDownloadFromCloudPage = function () {
    sgBackup.toggleNavigationButtons();
    jQuery('#modal-import-1').toggle();
    jQuery('#modal-import-3').toggle();
    jQuery('#uploadSgbpFile').toggle();

};

sgBackup.downloadFromCloud = function (path, name, storage, size, backupId) {
    sgBackup.showAjaxSpinner('.modal-dialog');
    var error = [];
    if (!path) {
        error.push(BG_BACKUP_STRINGS.invalidDownloadFile);
    }

    jQuery('.alert').remove();

    if (error.length) {
        sgBackup.hideAjaxSpinner();
        var sgAlert = sgBackup.alertGenerator(error, 'alert-danger');
        jQuery('#sg-modal .modal-header').prepend(sgAlert);
        return false;
    }

    var downloadFromCloudHandler = new sgRequestHandler(
        'downloadFromCloud',
        {
            path: path,
            storage: storage,
            size: size,
            backupId: backupId,
            token: BG_BACKUP_STRINGS.nonce
        }
    );

    jQuery('#switch-modal-import-pages-back').hide();
    jQuery('#uploadSgbpFile').attr('disabled', 'disabled');

    downloadFromCloudHandler.callback = function (response, error) {
        sgBackup.hideAjaxSpinner();
        jQuery('.alert').remove();

        clearTimeout(SG_DOWNLOAD_PROGRESS);

        if (typeof response.success !== 'undefined') {
            location.reload();
        } else {
            jQuery('#uploadSgbpFile').html(BG_BACKUP_STRINGS.import);

            var sgAlert = sgBackup.alertGenerator(response.error, 'alert-danger');

            jQuery('#uploadSgbpFile').attr('disabled', false);
            jQuery('#switch-modal-import-pages-back').toggle();
            jQuery('#sg-modal .modal-header').prepend(sgAlert);
            SG_ACTIVE_DOWNLOAD_AJAX = false;

            return false;
        }
    };

    SG_ACTIVE_DOWNLOAD_AJAX = true;
    downloadFromCloudHandler.run();
    sgBackup.fileDownloadProgress(name, size);

};

sgBackup.downloadFromPC = function () {
    var sgData = null;
    jQuery('#sg-modal').off('hide.bs.modal').on(
        'hide.bs.modal',
        function (e) {
            if (SG_ACTIVE_DOWNLOAD_AJAX) {
                if (!confirm(BG_BACKUP_STRINGS.confirm)) {
                    e.preventDefault();
                    return false;
                }

                sgData.abort();
                sgBackup.cancelDonwload(sgData.files[0].name);
            }
        }
    );

    jQuery('.sg-backup-upload-input').fileupload(
        {
            dataType: 'json',
            maxChunkSize: 2000000,
            add: function (e, data) {
                if (data.originalFiles.length) {
                    var fileName = data.originalFiles[0].name;
                    jQuery('#sg-import-file-name').val(fileName);
                }

                jQuery('#uploadSgbpFile').click(
                    function () {
                        if (jQuery('#modal-import-2').is(":visible")) {
                            sgData = data;
                            SG_ACTIVE_DOWNLOAD_AJAX = true;
                            jQuery('#uploadSgbpFile').attr('disabled', 'disabled');
                            jQuery('#switch-modal-import-pages-back').hide();
                            jQuery('#uploadSgbpFile').html(BG_BACKUP_STRINGS.importInProgress);
                            data.submit();
                        }
                    }
                );
            },
            done: function (e, data) {
                location.reload();
            },
            progress: function (e, data) {
                var progress = parseInt(((data.loaded / data.total) * 100), 10);
                jQuery('#uploadSgbpFile').html('Importing (' + Math.round(progress) + '%)');
            }
        }
    ).on(
        'fileuploadfail',
        function (e, data) {
            var alert = sgBackup.alertGenerator(BG_BACKUP_STRINGS.fileUploadFailed, 'alert-danger');
            jQuery('#sg-modal .modal-header').prepend(alert);
        }
    );

};

sgBackup.fileDownloadProgress = function (file, size) {
    var getFileDownloadProgress = new sgRequestHandler(
        'getFileDownloadProgress',
        {
            file: file,
            size: size,
            token: BG_BACKUP_STRINGS.nonce
        }
    );

    getFileDownloadProgress.callback = function (response) {
        if (typeof response.progress !== 'undefined') {
            jQuery('#uploadSgbpFile').html('Importing (' + Math.round(response.progress) + '%)');
            SG_DOWNLOAD_PROGRESS = setTimeout(
                function () {
                    getFileDownloadProgress.run();
                },
                SG_AJAX_REQUEST_FREQUENCY
            );
        }
    };

    getFileDownloadProgress.run();

};

sgBackup.fileUploadProgress = function (e) {
    if (e.lengthComputable) {
        jQuery('#uploadSgbpFile').html('Importing (' + Math.round((e.loaded * 100.0) / e.total) + '%)');
    }

};

sgBackup.checkBackupCreation = function () {
    jQuery('#manualBackup .btn-success').attr('disabled', true);
    var sgBackupCreationHandler = new sgRequestHandler('checkBackupCreation', {token: BG_BACKUP_STRINGS.nonce});
    sgBackupCreationHandler.dataType = 'html';
    sgBackupCreationHandler.callback = function (response) {
        var hideAndReload = function () {
            jQuery('#sg-modal').modal('hide');
            location.reload();
        };
        if (response.length) {
            var result = jQuery.parseJSON(response);
            if (result && result.status === 'cleaned') {
                sgBackup.manualBackup('cleaned');
            } else {
                hideAndReload();
            }
        } else {
            hideAndReload();
        }
    };
    sgBackupCreationHandler.run();

};

sgBackup.checkRestoreCreation = function () {
    jQuery('#manualBackup .btn-success').attr('disabled', true);
    var sgRestoreCreationHandler = new sgRequestHandler('checkRestoreCreation', {token: BG_BACKUP_STRINGS.nonce});
    sgRestoreCreationHandler.callback = function (response) {
        if (response.status === 0 && response.external_enabled === 1) {
            location.href = response.external_url;
        } else if (response.status === 'cleaned') {
            jQuery('#manualBackup .btn-success').click();
        } else {
            location.reload();
        }
    };
    sgRestoreCreationHandler.run();

};

sgBackup.initManulBackupRadioInputs = function () {
    jQuery('input[type=radio][name=backupType]').off('change').on(
        'change',
        function () {
            jQuery('.sg-custom-backup').fadeToggle();
        }
    );
    jQuery('input[type=radio][name=restoreType]').off('change').on(
        'change',
        function () {
            if (jQuery('input[type=radio][name=restoreType]:checked').val() === "files") {
                jQuery('.sg-restore-files-options').fadeIn();
            } else {
                jQuery('.sg-restore-files-options').fadeOut();
            }
        }
    );

    jQuery('input[type=radio][name=restoreFilesType]').off('change').on(
        'change',
        function () {
            jQuery('.sg-file-selective-restore').fadeToggle();
        }
    );

    jQuery('input[type=checkbox][name=backupFiles], input[type=checkbox][name=backupDatabase], input[type=checkbox][name=backupCloud]').off('change').on(
        'change',
        function () {
            var sgCheckBoxWrapper = jQuery(this).closest('.checkbox').find('.sg-checkbox');
            sgCheckBoxWrapper.fadeToggle();
            if (jQuery(this).attr('name') === 'backupFiles') {
                sgCheckBoxWrapper.find('input[type=checkbox]').attr('checked', 'checked');
            }
        }
    );
    jQuery('input[type=radio][name=backupDBType]').off('change').on(
        'change',
        function () {
            var sgCheckBoxWrapper = jQuery(this).closest('.checkbox').find('.sg-custom-backup-tables');
            if (jQuery('input[type=radio][name=backupDBType]:checked').val() === '2') {
                sgCheckBoxWrapper.find('input[type=checkbox]').not("[disabled]").prop('checked', true)
                sgCheckBoxWrapper.fadeIn();
            } else {
                sgCheckBoxWrapper.fadeOut();
                sgCheckBoxWrapper.find('input[type=checkbox][current="true"]').not("[disabled]").prop('checked', true)
                sgCheckBoxWrapper.find('input[type=checkbox][current="false"]').prop('checked', false)
            }
        }
    )

};

sgBackup.initImportTooltips = function () {
    jQuery('a[data-toggle=tooltip]').tooltip();

};

sgBackup.initManualBackupTooltips = function () {
    jQuery('[for=cloud-ftp]').tooltip();
    jQuery('[for=cloud-dropbox]').tooltip();
    jQuery('[for=cloud-gdrive]').tooltip();
    jQuery('[for=cloud-one-drive]').tooltip();
    jQuery('[for=cloud-p-cloud]').tooltip();
    jQuery('[for=cloud-box]').tooltip();
    jQuery('[for=cloud-amazon]').tooltip();
    jQuery('[for=cloud-backup-guard]').tooltip();

    jQuery('a[data-toggle=tooltip]').tooltip();

};

sgBackup.startRestore = function (bname) {
    var checkIsItMigration = new sgRequestHandler('checkFreeMigration', {bname: bname, token: BG_BACKUP_STRINGS.nonce});

    checkIsItMigration.callback = function (response) {
        if (response) {
            jQuery('.modal-body.sg-modal-body').html(response);
            return false;
        }

        sgBackup.startRestoreAction(bname);
    };
    checkIsItMigration.dataType = '';
    checkIsItMigration.run();

};

sgBackup.startRestoreAction = function (bname) {
    jQuery('.alert').remove();
    var type = jQuery('input[type=radio][name=restoreType]:checked').val();
    var restoreFilesType = jQuery('input[type=radio][name=restoreFilesType]:checked').val() || "0";

    if (restoreFilesType === "0") {
        var paths = "/";
    } else {
        var paths = jQuery("#fileSystemTreeContainer").jstree("get_selected");
    }

    var checkPHPVersionCompatibility = new sgRequestHandler(
        'checkPHPVersionCompatibility',
        {
            bname: bname,
            token: BG_BACKUP_STRINGS.nonce
        }
    );

    checkPHPVersionCompatibility.callback = function (response) {
        if (typeof response.error !== 'undefined') {
            alert(response.error);
            return false;
        } else if (typeof response.warning !== 'undefined') {
            if (!confirm(response.warning)) {
                return false;
            }
        }

        sgBackup.showAjaxSpinner('#sg-content-wrapper');
        var resetStatusHandler = new sgRequestHandler('resetStatus', {token: BG_BACKUP_STRINGS.nonce});
        resetStatusHandler.callback = function (response) {
            // If error!
            if (typeof response.success === 'undefined') {
                alert(response);
                location.reload();
                return false;
            }

            var restoreHandler = new sgRequestHandler('restore', {bname: bname, type: type, paths: paths});
            restoreHandler.run();
            sgBackup.checkRestoreCreation();
        };
        resetStatusHandler.run();
    };

    if (type === "files" && restoreFilesType === 1) {
        var isFeatureAvailable = new sgRequestHandler('isFeatureAvailable', {sgFeature: "SLECTIVE_RESTORE"});
        isFeatureAvailable.callback = function (response) {
            if (typeof response.success !== 'undefined') {
                checkPHPVersionCompatibility.run();
            } else {
                var alert = sgBackup.alertGenerator(response.error, 'alert-warning');
                jQuery('#sg-modal .modal-header').prepend(alert);
                return false;
            }
        };

        isFeatureAvailable.run();
    } else {
        checkPHPVersionCompatibility.run();
    }

};

sgBackup.initActiveAction = function () {
    if (jQuery('.sg-active-action-id').length <= 0) {
        return;
    }

    var activeActionsIds = [];
    jQuery('.sg-active-action-id').each(
        function () {
            activeActionsIds.push(jQuery(this).val());
        }
    );

    // Cancel Button!
    jQuery('.sg-cancel-backup').click(
        function () {
            if (confirm('Are you sure?')) {
                var actionId = jQuery(this).attr('sg-data-backup-id');
                var sgCancelHandler = new sgRequestHandler(
                    'cancelBackup',
                    {
                        actionId: actionId,
                        token: BG_BACKUP_STRINGS.nonce
                    }
                );
                sgCancelHandler.run();
            }
        }
    );

    var activeActionsIdsLength = activeActionsIds.length;
    for (var i = 0; i < activeActionsIdsLength; i++) {
        // GetProgress!
        sgBackup.getActionProgress(activeActionsIds[i]);
    }

};

sgBackup.getActionProgress = function (actionId) {
    var progressBar = jQuery('.sg-progress .progress-bar', '#sg-status-tabe-data-' + actionId);

    var sgActionHandler = new sgRequestHandler('getAction', {actionId: actionId, token: BG_BACKUP_STRINGS.nonce});
    // Init tooltip!
    var statusTooltip = jQuery('#sg-status-tabe-data-' + actionId + '[data-toggle=tooltip]').tooltip();

    sgActionHandler.callback = function (response) {
        if (response) {
            sgBackup.disableUi();
            var progressInPercents = response.progress + '%';
            progressBar.width(progressInPercents);
            sgBackup.statusUpdate(statusTooltip, response, progressInPercents);
            setTimeout(
                function () {
                    sgActionHandler.run();
                },
                SG_CHECK_ACTION_STATUS_REQUEST_FREQUENCY
            );
        } else {
            jQuery('[class*=sg-status]').addClass('active');
            jQuery('.sg-progress').remove();
            jQuery('.sg-active-action-id').remove();
            location.reload();
        }
    };
    sgActionHandler.run();

};

sgBackup.statusUpdate = function (tooltip, response, progressInPercents) {
    var tooltipText = '';
    if (response.type === '1') {
        var currentAction = 'Backup';
        if (response.status === '1') {
            tooltipText = currentAction + ' database - ' + progressInPercents;
        } else if (response.status === '2') {
            tooltipText = currentAction + ' files - ' + progressInPercents;
        }

        jQuery('.sg-status-' + response.status).prevAll('[class*=sg-status]').addClass('active');
    } else if (response.type === '2') {
        var currentAction = 'Restore';
        if (response.status === '1') {
            tooltipText = currentAction + ' database - ' + progressInPercents;
        } else if (response.status === '2') {
            tooltipText = currentAction + ' files - ' + progressInPercents;
        }

        jQuery('.sg-status-' + response.type + response.status).prevAll('[class*=sg-status]').addClass('active');
    } else if (response.type === '3') {
        var cloudIcon = jQuery('.sg-status-' + response.type + response.subtype);
        if (response.subtype === SG_STORAGE_FTP) {
            tooltipText = 'Uploading to FTP - ' + progressInPercents;
        } else if (response.subtype === SG_STORAGE_DROPBOX) {
            tooltipText = 'Uploading to Dropbox - ' + progressInPercents;
        } else if (response.subtype === SG_STORAGE_GOOGLE_DRIVE) {
            tooltipText = 'Uploading to Google Drive - ' + progressInPercents;
        } else if (response.subtype === SG_STORAGE_AMAZON) {
            tooltipText = 'Uploading to Amazon S3 - ' + progressInPercents;
        } else if (response.subtype === SG_STORAGE_ONE_DRIVE) {
            tooltipText = 'Uploading to OneDrive - ' + progressInPercents;
        } else if (response.subtype === SG_STORAGE_P_CLOUD) {
            tooltipText = 'Uploading to pCloud - ' + progressInPercents;
        } else if (response.subtype === SG_STORAGE_BOX) {
            tooltipText = 'Uploading to box.com - ' + progressInPercents;
        } else if (response.subtype === SG_STORAGE_BACKUP_GUARD) {
            tooltipText = 'Uploading to BackupGuard - ' + progressInPercents;
        }

        cloudIcon.prevAll('[class*=sg-status]').addClass('active');
    }//end if

    tooltip.attr('data-original-title', tooltipText);

};

sgBackup.disableUi = function () {
    jQuery('#sg-manual-backup').attr('disabled', 'disabled');
    jQuery('#sg-backup-with-migration').attr('disabled', 'disabled');
    jQuery('#sg-import').attr('disabled', 'disabled');
    jQuery('.sg-restore').attr('disabled', 'disabled');
    jQuery('.sg-restore-button').attr('disabled', 'disabled');

};

sgBackup.enableUi = function () {
    jQuery('#sg-manual-backup').removeAttr('disabled');
    jQuery('#sg-backup-with-migration').removeAttr('disabled');
    jQuery('#sg-import').removeAttr('disabled');
    jQuery('.sg-restore').removeAttr('disabled');
    jQuery('.sg-restore-button').removeAttr('disabled');

};

sgBackup.initBackupDeletion = function () {
    jQuery('.sg-remove-backup').click(
        function () {
            var btn = jQuery(this),
                url = btn.attr('data-remote'),
                backupName = [btn.attr('data-sgbackup-name')];
            if (confirm('Are you sure?')) {
                var ajaxHandler = new sgRequestHandler(url, {backupName: backupName, token: BG_BACKUP_STRINGS.nonce});
                ajaxHandler.callback = function (response) {
                    location.reload();
                };
                ajaxHandler.run();
            }
        }
    );

};

/* phpcs:enable */
