// phpcs:ignoreFile
sgBackup = {};
sgBackup.isModalOpen = false;
SG_CURRENT_ACTIVE_AJAX = '';
SG_NOTICE_EXECUTION_TIMEOUT = 'timeout_error';
SG_NOTICE_EXECUTION_FREE_TIMEOUT = 'timeout_free_error';
SG_NOTICE_MIGRATION_ERROR = 'migration_error';
SG_NOTICE_NOT_WRITABLE_ERROR = 'restore_notwritable_error';

jQuery(window).on(
    'load', function () {
        if (jQuery('.sg-active-action-id').length == 0) {
            sgBackup.showReviewModal();
        }
        sgBackup.changeMainUrl();
    }
);

jQuery(document).ready(
    function () {
        jQuery('span[data-toggle=tooltip]').tooltip();

        sgBackup.init();

        jQuery('.sg-badge-warning').on(
            'click', function () {
                var url = jQuery(this).attr('target-url');
                if (url) {
                    window.open(url, '_blank');
                }
            }
        );

        jQuery("#rateYo").rateYo(
            {
                rating: 5,
                fullStar: true,
                spacing: "3px",
                starWidth: "16px",
                starHeight: "16px",
                rating: 4.5,
                ratedFill: "#ffffff"
            }
        );

        if (typeof SG_AJAX_REQUEST_FREQUENCY === 'undefined') {
            SG_AJAX_REQUEST_FREQUENCY = 2000;
        }

        sgBackup.hideAjaxSpinner();
        var notice = "";
        jQuery('.notice-dismiss').on(
            'click', function () {
                if (jQuery(this).parent().attr('data-notice-id') == SG_NOTICE_EXECUTION_TIMEOUT) {
                    notice = SG_NOTICE_EXECUTION_TIMEOUT;
                } else if (jQuery(this).parent().attr('data-notice-id') == SG_NOTICE_EXECUTION_FREE_TIMEOUT) {
                    notice = SG_NOTICE_EXECUTION_TIMEOUT;
                } else if (jQuery(this).parent().attr('data-notice-id') == SG_NOTICE_MIGRATION_ERROR) {
                    notice = SG_NOTICE_MIGRATION_ERROR;
                } else if (jQuery(this).parent().attr('data-notice-id') == SG_NOTICE_NOT_WRITABLE_ERROR) {
                    notice = SG_NOTICE_NOT_WRITABLE_ERROR
                }
                var sgNoticeClosedHandler = new sgRequestHandler(
                    'hideNotice', {
                        notice: notice,
                        token: BG_BACKUP_STRINGS.nonce
                    }
                );
                sgNoticeClosedHandler.run();
            }
        );

        //send awake requests only if there is an active action
        if (jQuery('.sg-active-action-id').length > 0) {
            setInterval(sgBackup.awake, SG_AJAX_REQUEST_FREQUENCY);
        }
    }
);

sgBackup.awake = function () {
    var awakeAjaxHandler = new sgRequestHandler('awake', {token: BG_BACKUP_STRINGS.nonce});
    awakeAjaxHandler.run();
}

//SG init
sgBackup.init = function () {
    sgBackup.initModals();
    sgBackup.downloadButton();
    sgBackup.navMenu();
    sgBackup.sendUsageDataStatus();
};

sgBackup.sendUsageDataStatus = function () {
    var checkbox = jQuery('.backup-send-usage-data-status');

    if (!checkbox.length) {
        return false;
    }

    checkbox.bind(
        'switchChange.bootstrapSwitch', function () {
            var currentStatus = jQuery(this).is(':checked');
            var action = 'send_usage_status';
            jQuery(this).prop('disabled', true);
            var ajaxHandler = new sgRequestHandler(action, {currentStatus: currentStatus, token: BG_BACKUP_STRINGS.nonce});
            ajaxHandler.callback = function (data, error) {
                jQuery(this).prop('disabled', false);
            }

            ajaxHandler.run();
        }
    );
};

sgBackup.navMenu = function () {
    var navMenu = jQuery('.sg-backup-sidebar-nav a');

    if (!navMenu.length) {
        return false;
    }

    navMenu.unbind('click').bind(
        'click', function (event) {
            event.preventDefault();
            sgBackup.init();

            var currentUrl = jQuery(this).attr('href');
            var openContent = jQuery(this).data('open-content');

            if (typeof openContent != 'undefined' && openContent == 0) {
                window.open(currentUrl);
                return true;
            }
            jQuery('.sg-backup-page-content').addClass('sg-visibility-hidden');
            jQuery('.sg-backup-sidebar-nav li').removeClass('active');

            var currentKey = jQuery(this).data('page-key');
            var currentPageContent = jQuery('#sg-backup-page-content-' + currentKey);

            if (!currentPageContent.length) {
                return false;
            }
            jQuery(this).parent().addClass('active');
            currentPageContent.removeClass('sg-visibility-hidden');
        }
    );
};

sgBackup.downloadButton = function () {
    var downloadButtons = jQuery('.sg-bg-download-button');

    if (!downloadButtons.length) {
        return false;
    }
    var isOPen = false;
    jQuery(window).bind(
        'click', function () {
            if (isOPen) {
                jQuery('.sg-backup-table .dropdown-menu').removeClass('sg-bg-show');
                isOPen = false;
            }
        }
    );

    downloadButtons.bind(
        'click', function () {
            var currentButton = jQuery(this);

            if (!currentButton.length) {
                return false;
            }
            setTimeout(
                function () {
                    currentButton.next().addClass('sg-bg-show');
                    isOPen = true;
                }, 0
            );
        }
    );
};

//SG Modal popup logic
sgBackup.initModals = function () {

    jQuery('[data-toggle="modal"][href], [data-toggle="modal"][data-remote]').off('click').on(
        'click', function (e) {
            var param = '';
            if (typeof jQuery(this).attr('data-sgbp-params') !== 'undefined') {
                param = jQuery(this).attr('data-sgbp-params');
            }

            e.preventDefault();
            var btn = jQuery(this),
            url = btn.attr('data-remote'),
            modalName = btn.attr('data-modal-name'),
            backupType = btn.attr('sg-data-backup-type'),
            modal = jQuery('#sg-modal');
            if (modal.length == 0) {
                modal = jQuery(
                    '' +
                    '<div class="modal fade" id="sg-modal" tabindex="-1" role="dialog" aria-hidden="true"></div>' +
                    ''
                );
                body.append(modal);
            }
            sgBackup.showAjaxSpinner('#sg-content-wrapper');
            if (typeof sgBackup.disableUi == 'function') {
                sgBackup.disableUi();
            }

            var ajaxHandler = new sgRequestHandler(
                url, {
                    param: param,
                    backupType: backupType,
                    token: BG_BACKUP_STRINGS.nonce
                }
            );

            if (modalName == 'backup-guard-details') {
                modal.modal(
                    {
                        backdrop: 'static',
                        keyboard: false
                    }
                );
            }

            ajaxHandler.type = 'GET';
            ajaxHandler.dataType = 'html';
            ajaxHandler.callback = function (data, error) {
                sgBackup.hideAjaxSpinner();
                if (typeof sgBackup.enableUi == 'function') {
                    sgBackup.enableUi();
                }
                if (error === false) {
                    jQuery('#sg-modal').append(data);
                }
                modal.on(
                    'hide.bs.modal', function () {
                        if (SG_CURRENT_ACTIVE_AJAX != '') {
                            if (!confirm(BG_MAIN_STRINGS.confirmCancel)) {
                                return false;
                            }
                            SG_CURRENT_ACTIVE_AJAX.abort();
                            SG_CURRENT_ACTIVE_AJAX = '';
                        }
                    }
                );
                modal.one(
                    'hidden.bs.modal', function () {
                        modal.html('');
                    }
                ).modal('show');
                sgBackup.didOpenModal(modalName, param);
            };

            if (modalName == 'ftp-settings' || modalName == 'amazon-settings') {
                var storage = 'FTP';
                if (modalName == 'amazon-settings') {
                    storage = 'AMAZON';
                }
                error = false;
                var isFeatureAvailable = new sgRequestHandler('isFeatureAvailable', {sgFeature: storage});
                isFeatureAvailable.callback = function (response) {
                    if (typeof response.error !== 'undefined') {
                        var alert = sgBackup.alertGenerator(response.error, 'alert-warning');
                        jQuery('.sg-cloud-container legend').after(alert);
                        that.bootstrapSwitch('state', false);
                        sgBackup.hideAjaxSpinner();
                    } else {
                        ajaxHandler.run();
                    }
                }

                isFeatureAvailable.run();
            } else {
                ajaxHandler.run();
            }
        }
    );
};

sgBackup.toggleSftpSettings = function () {
    jQuery('#ftpPort').val('22');
    jQuery('#sg-sftp-key-file-block').show();
    jQuery('#sg-browse-key-file-block').hide();

    if (jQuery('#sg-connect-with-key-file').is(':checked') && connectioType == 'sftp') {
        jQuery('#sg-browse-key-file-block').show();
    }
}

sgBackup.toggleFtpSettings = function () {
    jQuery('#ftpPort').val('21');
    jQuery('#sg-sftp-key-file-block').hide();
}

// Show/hide some fields that are needed/not needed for ftp/sftp
sgBackup.toggleNeededFtpFields = function (connectioType) {
    if (connectioType == 'sftp') {
        sgBackup.toggleSftpSettings();

    } else if (connectioType == 'ftp') {
        sgBackup.toggleFtpSettings();
    }
}

sgBackup.didOpenModal = function (modalName, param) {
    if (modalName == 'manual-backup' || modalName == 'manual-restore') {
        sgBackup.initManulBackupRadioInputs();
        sgBackup.initManualBackupTooltips();
        jQuery('#fileSystemTreeContainer').jstree(
            {
                'core': {

                    'data': {
                        'url': function (node) {
                            return getAjaxUrl();
                        },
                        'data': function (node) {
                            var path = node.id;
                            return {
                                action: "backup_guard_getBackupContent",
                                path: path,
                                backupName: param,
                                token: BG_BACKUP_STRINGS.nonce
                            };
                        }
                    }
                },
                "plugins": ["wholerow", "checkbox", "types"],
                "checkbox": {
                    "keep_selected_style": false
                },
                "types": {
                    "file": {
                        "icon": "bg-file-icon"
                    },
                    "folder": {
                        "icon": "bg-folder-icon"
                    },
                    "default": {
                        "icon": "bg-no-icon"
                    }
                }
            }
        );
    } else if (modalName == 'import') {
        sgBackup.initImportTooltips();
        jQuery('#modal-import-2').hide();
        jQuery('#modal-import-3').hide();
        jQuery('#switch-modal-import-pages-back').hide();
        jQuery('#uploadSgbpFile').hide();
        if (jQuery('#modal-import-1').length == 0) {
            sgBackup.toggleDownloadFromPCPage();
        }
        sgBackup.initFileUpload();
    } else if (modalName == 'ftp-settings') {
        connectioType = jQuery('#sg-connection-method').val();
        sgBackup.toggleNeededFtpFields(connectioType);

        jQuery('#sg-connection-method').on(
            'change', function () {
                connectioType = jQuery(this).val();
                sgBackup.toggleNeededFtpFields(connectioType);
            }
        );

        jQuery('#sg-connect-with-key-file').on(
            'click', function () {
                if (jQuery(this).is(':checked')) {
                    jQuery('#sg-browse-key-file-block').show();
                } else {
                    jQuery('#sg-browse-key-file-block').hide();
                }
            }
        )

        sgBackup.initSFTPKeyFileSelection();

        jQuery('#sg-modal').on(
            'hidden.bs.modal', function () {
                if (sgBackup.isFtpConnected != true) {
                    jQuery('input[data-storage=FTP]').bootstrapSwitch('state', false);
                }
            }
        )
    } else if (modalName == 'amazon-settings') {
        jQuery('#sg-modal').on(
            'hidden.bs.modal', function () {
                if (sgBackup.isAmazonConnected != true) {
                    jQuery('input[data-storage=AMAZON]').bootstrapSwitch('state', false);
                }
            }
        );
        jQuery("#bucketType").on(
            "change", function () {
                jQuery("#bucketType option").each(
                    function () {
                        var name = jQuery(this).val();
                        jQuery(".form-group-" + name).css("display", "none");
                        // Add $(this).val() to your list
                    }
                );
                var selected = jQuery("#bucketType").val();
                jQuery(".form-group-" + selected).css("display", "block");
            }
        )
    } else if (modalName == 'manual-review') {
        var action = 'setReviewPopupState';
        jQuery('#sgLeaveReview').click(
            function () {
                var reviewUrl = jQuery(this).attr('data-review-url');
                //Never show again
                var reviewState = 2;
                var ajaxHandler = new sgRequestHandler(action, {reviewState: reviewState, token: BG_BACKUP_STRINGS.nonce});
                ajaxHandler.run();
                window.open(reviewUrl);
            }
        );

        jQuery('#sgDontAskAgain').click(
            function () {
                //Never show again
                var reviewState = 2;
                var ajaxHandler = new sgRequestHandler(action, {reviewState: reviewState, token: BG_BACKUP_STRINGS.nonce});
                ajaxHandler.run();
            }
        );

        jQuery('#sgAskLater').click(
            function () {
                var reviewState = 0;
                var ajaxHandler = new sgRequestHandler(action, {reviewState: reviewState, token: BG_BACKUP_STRINGS.nonce});
                ajaxHandler.run();
            }
        );
    } else if (modalName == 'create-schedule') {
        sgBackup.initScheduleCreation();
    }
};

sgBackup.isAnyOpenModal = function () {
    return jQuery('#sg-modal').length;
};

sgBackup.alertGenerator = function (content, alertClass) {
    var sgalert = '';
    sgalert += '<div class="alert alert-dismissible ' + alertClass + '">';
    sgalert += '<button type="button" class="close" data-dismiss="alert">×</button>';
    if (jQuery.isArray(content)) {
        jQuery.each(
            content, function (index, value) {
                sgalert += value + '<br/>';
            }
        );
    } else if (content != '') {
        sgalert += content.replace('[', '').replace(']', '').replace('"', '');
    }
    sgalert += '</div>';
    return sgalert;
};

sgBackup.scrollToElement = function (id) {
    if (jQuery(id).position()) {
        if (jQuery(id).position().top < jQuery(window).scrollTop()) {
            //scroll up
            jQuery('html,body').animate({scrollTop: jQuery(id).position().top}, 1000);
        } else if (jQuery(id).position().top + jQuery(id).height() > jQuery(window).scrollTop() + (window.innerHeight || document.documentElement.clientHeight)) {
            //scroll down
            jQuery('html,body').animate({scrollTop: jQuery(id).position().top - (window.innerHeight || document.documentElement.clientHeight) + jQuery(id).height() + 15}, 1000);
        }
    }
};

sgBackup.showAjaxSpinner = function (appendToElement) {
    if (typeof appendToElement == 'undefined') {
        appendToElement = '#sg-wrapper';
    }
    jQuery('<div class="sg-spinner"></div>').appendTo(appendToElement);
};

sgBackup.hideAjaxSpinner = function () {
    jQuery('.sg-spinner').remove();
};
sgBackup.showReviewModal = function () {
    if (typeof sgShowReview != 'undefined') {
        jQuery('#sg-review').trigger("click");
    }
};

sgBackup.initTablePagination = function (pageName) {
    var callBack = pageName + '';
    jQuery.fn.sgTablePagination = function (opts) {
        var jQuerythis = this,
            defaults = {
                perPage: 7,
                showPrevNext: false,
                hidePageNumbers: false,
                pagerSelector: '.' + pageName + ' .pagination'
        },
            settings = jQuery.extend(defaults, opts);

        var listElement = jQuerythis.children('tbody');
        var perPage = settings.perPage;
        var children = listElement.children();
        var pager = jQuery('.' + pageName + '.pager');

        if (typeof settings.childSelector != "undefined") {
            children = listElement.find(settings.childSelector);
        }

        if (typeof settings.pagerSelector != "undefined") {
            pager = jQuery(settings.pagerSelector);
        }

        var numItems = children.length;
        var numPages = Math.ceil(numItems / perPage);

        pager.data("curr", 0);

        if (settings.showPrevNext) {
            jQuery('<li><a href="#" class="prev_link">«</a></li>').appendTo(pager);
        }

        var curr = 0;
        while (numPages > curr && (settings.hidePageNumbers == false)) {
            jQuery('<li><a href="#" class="page_link">' + (curr + 1) + '</a></li>').appendTo(pager);
            curr++;
        }

        if (curr <= 1) {
            jQuery(settings.pagerSelector).parent('div').hide();
            jQuery('.' + pageName + '.page_link').hide();
        }

        if (settings.showPrevNext) {
            jQuery('<li><a href="#" class="next_link">»</a></li>').appendTo(pager);
        }

        pager.find('.page_link:first').addClass('active');
        pager.find('.prev_link').hide();
        if (numPages <= 1) {
            pager.find('.next_link').hide();
        }
        pager.children().eq(1).addClass("active");

        children.hide();
        children.slice(0, perPage).show();

        pager.find('li .page_link').click(
            function () {
                var clickedPage = jQuery(this).html().valueOf() - 1;
                goTo(clickedPage, perPage);
                return false;
            }
        );
        pager.find('li .prev_link').click(
            function () {
                previous();
                return false;
            }
        );
        pager.find('li .next_link').click(
            function () {
                next();
                return false;
            }
        );

        function previous()
        {
            var goToPage = parseInt(pager.data("curr")) - 1;
            goTo(goToPage);
        }

        function next()
        {
            goToPage = parseInt(pager.data("curr")) + 1;
            goTo(goToPage);
        }

        function goTo(page)
        {
            var startAt = page * perPage,
                endOn = startAt + perPage;

            children.css('display', 'none').slice(startAt, endOn).show();

            if (page >= 1) {
                pager.find('.prev_link').show();
            } else {
                pager.find('.prev_link').hide();
            }

            if (page < (numPages - 1)) {
                pager.find('.next_link').show();
            } else {
                pager.find('.next_link').hide();
            }

            pager.data("curr", page);
            pager.children().removeClass("active");
            pager.children().eq(page + 1).addClass("active");

        }
    };
    jQuery('table.paginated.' + pageName).sgTablePagination(
        {
            pagerSelector: '.' + pageName + ' .pagination',
            showPrevNext: true,
            hidePageNumbers: false,
            perPage: 7
        }
    );
};

sgBackup.logout = function () {
    var ajaxHandler = new sgRequestHandler('logout', {token: BG_BACKUP_STRINGS.nonce});
    ajaxHandler.callback = function (response) {
        location.reload();
    };
    ajaxHandler.run();
};

sgBackup.changeMainUrl = function () {
    jQuery('nav#sg-main-sidebar li').click(
        function () {

            let attr = jQuery(this).children('a').attr('data-page-key');

            if (typeof attr !== typeof undefined && attr !== false) {

                let queryParams = new URLSearchParams(window.location.search);

                queryParams.set('page', 'backup_guard_' + attr);

                history.replaceState(null, null, "?" + queryParams.toString());
            }
        }
    );
};

sgBackup.daysInMonth = function (year, month) {
    return new Date(year, month, 0).getDate();
};
