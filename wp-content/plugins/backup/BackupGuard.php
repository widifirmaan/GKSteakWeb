<?php

// hook to wordpres widget
function backup_guard_register_widget()
{
    if (!class_exists('SGWordPressWidget')) {
        @include_once SG_WIDGET_PATH . 'SGWordPressWidget.php';
    }

    register_widget('SGWordPressWidget');
}
    add_action('widgets_init', 'backup_guard_register_widget');

//The code that runs during plugin activation.
function activate_backup_guard()
{
    //check if database should be updated
    if (backupGuardShouldUpdate()) {
        SGBoot::install();
        SGBoot::didInstallForFirstTime();
    }
}

// The code that runs during plugin deactivation.
function uninstall_backup_guard()
{
    SGBoot::uninstall();
}

function deactivate_backup_guard()
{
    $pluginCapabilities = backupGuardGetCapabilities();
    if ($pluginCapabilities != BACKUP_GUARD_CAPABILITIES_FREE) {
        include_once SG_LIB_PATH . 'SGAuthClient.php';
        SGAuthClient::getInstance()->logout();
        SGConfig::set('SG_LICENSE_CHECK_TS', 0, true);
        SGConfig::set('SG_LOGGED_USER', '', true);
    }
}

function backupGuardMaybeShortenEddFilename($return, $package)
{
    if (strpos($package, 'backup-guard') !== false) {
        add_filter('wp_unique_filename', 'backupGuardShortenEddFilename', 10, 2);
    }
    return $return;
}

function backupGuardShortenEddFilename($filename, $ext)
{
    $filename = substr($filename, 0, 20) . $ext;
    remove_filter('wp_unique_filename', 'backupGuardShortenEddFilename', 10);
    return $filename;
}

    add_filter('upgrader_pre_download', 'backupGuardMaybeShortenEddFilename', 10, 4);

    register_activation_hook(SG_BACKUP_GUARD_MAIN_FILE, 'activate_backup_guard');
    register_uninstall_hook(SG_BACKUP_GUARD_MAIN_FILE, 'uninstall_backup_guard');
    register_deactivation_hook(SG_BACKUP_GUARD_MAIN_FILE, 'deactivate_backup_guard');
//add_action('admin_footer', 'before_deactivate_backup_guard');

function before_deactivate_backup_guard()
{
    wp_enqueue_style('before-deactivate-backup-guard-css', plugin_dir_url(__FILE__) . 'public/css/deactivationSurvey.css');
    wp_enqueue_script('before-deactivate-backup-guard-js', plugin_dir_url(__FILE__) . 'public/js/deactivationSurvey.js', array('jquery'));

    wp_localize_script(
        'before-deactivate-backup-guard-js',
        'BG_BACKUP_STRINGS_DEACTIVATE',
        array( 'nonce' => wp_create_nonce('backupGuardAjaxNonce'),
           'areYouSure' => _backupGuardT('Are you sure?', true),
           'invalidCloud' => _backupGuardT('Please select at least 1 cloud', true)
        )
    );

    include_once plugin_dir_path(__FILE__) . 'public/include/uninstallSurveyPopup.php';
}

// Register Admin Menus for single and multisite
if (is_multisite()) {
    add_action('network_admin_menu', 'backup_guard_admin_menu');
} else {
    add_action('admin_menu', 'backup_guard_admin_menu');
}

function backup_guard_admin_menu()
{
    $capability = 'manage_options';
    if (defined('SG_USER_MODE') && SG_USER_MODE) {
        $capability = 'read';
    }

    add_menu_page('Backups', 'BackupGuard', $capability, 'backup_guard_backups', 'includeAllPages', 'data:image/svg+xml;base64,PHN2ZyBpZD0iTGF5ZXJfMSIgZGF0YS1uYW1lPSJMYXllciAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2MzAuMzQgNjYzLjAzIj48ZGVmcz48c3R5bGU+LmNscy0xe2ZpbGw6I2ZmZjt9PC9zdHlsZT48L2RlZnM+PHRpdGxlPkFydGJvYXJkIDI8L3RpdGxlPjxwYXRoIGNsYXNzPSJjbHMtMSIgZD0iTTUzMC4xMSwxODUuNzljLTcxLjktOC44Mi0xMzcuNzMtNDAtMTkwLTg2LjU3djkyLjY1YTI4MC41OSwyODAuNTksMCwwLDAsMTE2LjUyLDUyYy05LjE0LDg5LjQzLTUyLDE2OS41NS0xMTYuNTIsMjI4Ljg3djkwLjRDNDU5Ljg0LDQ3Ny4xMyw1MzAuNiwzMzMuNDIsNTMwLjExLDE4NS43OVoiLz48cGF0aCBjbGFzcz0iY2xzLTEiIGQ9Ik0xNzQuMjksMjQ0YTI4MC40NiwyODAuNDYsMCwwLDAsMTE1Ljc3LTUxLjExVjEwMGMtNTIuNDQsNDYuMjgtMTE3LjYyLDc3LTE4OS44Myw4NS4xNUM5OS41NCwzMzMsMTcwLjIyLDQ3Ni44MiwyOTAuMDYsNTYzVjQ3Mi4wOUMyMjYsNDEyLjg2LDE4My40MiwzMzMuMDYsMTc0LjI5LDI0NFoiLz48L3N2Zz4=', 74);

    add_submenu_page('backup_guard_backups', _backupGuardT('Backups', true), _backupGuardT('Backups', true), $capability, 'backup_guard_backups', 'includeAllPages');
    add_submenu_page('backup_guard_backups', _backupGuardT('Cloud', true), _backupGuardT('Cloud', true), $capability, 'backup_guard_cloud', 'includeAllPages');
    add_submenu_page('backup_guard_backups', _backupGuardT('Schedule', true), _backupGuardT('Schedule', true), $capability, 'backup_guard_schedule', 'includeAllPages');

    add_submenu_page('backup_guard_backups', _backupGuardT('Settings', true), _backupGuardT('Settings', true), $capability, 'backup_guard_settings', 'includeAllPages');

    add_submenu_page('backup_guard_backups', _backupGuardT('System Info.', true), _backupGuardT('System Info.', true), $capability, 'backup_guard_system_info', 'includeAllPages');

    //add_submenu_page('backup_guard_backups', _backupGuardT('Services', true), _backupGuardT('Services', true), $capability, 'backup_guard_services', 'includeAllPages');
    add_submenu_page('backup_guard_backups', _backupGuardT('Video Tutorials', true), _backupGuardT('Video Tutorials', true), $capability, 'backup_guard_videoTutorials', 'includeAllPages');
    add_submenu_page('backup_guard_backups', _backupGuardT('Support', true), _backupGuardT('Support', true), $capability, 'backup_guard_support', 'includeAllPages');

    //Check if should show upgrade page
    if (SGBoot::isFeatureAvailable('SHOW_UPGRADE_PAGE')) {
        add_submenu_page('backup_guard_backups', _backupGuardT('Why upgrade?', true), _backupGuardT('Why upgrade?', true), $capability, 'backup_guard_pro_features', 'includeAllPages');
    }
}

function getBackupPageContentClassName($pageName = '')
{
    $hiddenClassName = 'sg-visibility-hidden';
    $page = $_GET['page'];

    if (strpos($page, $pageName)) {
        $hiddenClassName = '';
    }

    return $hiddenClassName;
}

function includeAllPages()
{
    if (!backupGuardValidateLicense()) {
        return false;
    }
    backup_guard_backups_page();
    backup_guard_cloud_page();
    backup_guard_system_info_page();
    backup_guard_services_page();
    backup_guard_pro_features_page();
    backup_guard_support_page();
    backup_guard_schedule_page();
    backup_guard_settings_page();

    include_once plugin_dir_path(__FILE__) . 'public/pagesContent.php';

    return true;
}

function backup_guard_system_info_page()
{
    if (backupGuardValidateLicense()) {
        //require_once(plugin_dir_path(__FILE__).'public/systemInfo.php');
    }
}

function backup_guard_services_page()
{
    if (backupGuardValidateLicense()) {
        //require_once(plugin_dir_path(__FILE__).'public/services.php');
    }
}

//Pro features page
function backup_guard_pro_features_page()
{
    //  require_once(plugin_dir_path(__FILE__).'public/proFeatures.php');
}

function backup_guard_security_page()
{
    include_once plugin_dir_path(__FILE__) . 'public/security.php';
}

//Support page
function backup_guard_support_page()
{
    if (backupGuardValidateLicense()) {
        //  require_once(plugin_dir_path(__FILE__).'public/support.php');
    }
}

//Backups Page
function backup_guard_backups_page()
{
    if (backupGuardValidateLicense()) {
        wp_enqueue_script('backup-guard-iframe-transport-js', plugin_dir_url(__FILE__) . 'public/js/jquery.iframe-transport.js', array('jquery'));
        wp_enqueue_script('backup-guard-fileupload-js', plugin_dir_url(__FILE__) . 'public/js/jquery.fileupload.js', array('jquery'));
        wp_enqueue_script('backup-guard-jstree-js', plugin_dir_url(__FILE__) . 'public/js/jstree.min.js', array('jquery'));
        wp_enqueue_script('backup-guard-jstree-checkbox-js', plugin_dir_url(__FILE__) . 'public/js/jstree.checkbox.js', array('jquery'));
        wp_enqueue_script('backup-guard-jstree-wholerow-js', plugin_dir_url(__FILE__) . 'public/js/jstree.wholerow.js', array('jquery'));
        wp_enqueue_script('backup-guard-jstree-types-js', plugin_dir_url(__FILE__) . 'public/js/jstree.types.js', array('jquery'));
        wp_enqueue_style('backup-guard-jstree-css', plugin_dir_url(__FILE__) . 'public/css/default/style.min.css');
        wp_enqueue_script('backup-guard-backups-js', plugin_dir_url(__FILE__) . 'public/js/sgbackup.js', array('jquery', 'jquery-effects-core', 'jquery-effects-transfer', 'jquery-ui-widget'));

        // Localize the script with new data
        wp_localize_script(
            'backup-guard-backups-js',
            'BG_BACKUP_STRINGS',
            array(
                'confirm'                  => _backupGuardT('Are you sure you want to cancel import?', true),
                'nonce' => wp_create_nonce('backupGuardAjaxNonce'),
                'invalidBackupOption'      => _backupGuardT('Please choose at least one option.', true),
                'invalidDirectorySelected' => _backupGuardT('Please choose at least one directory.', true),
                'invalidCloud'             => _backupGuardT('Please choose at least one cloud.', true),
                'backupInProgress'         => _backupGuardT('Backing Up...', true),
                'errorMessage'             => _backupGuardT('Something went wrong. Please try again.', true),
                'noBackupsAvailable'       => _backupGuardT('No backups found.', true),
                'invalidImportOption'      => _backupGuardT('Please select one of the options.', true),
                'invalidDownloadFile'      => _backupGuardT('Please choose one of the files.', true),
                'import'                   => _backupGuardT('Import', true),
                'importInProgress'         => _backupGuardT('Importing please wait...', true),
                'fileUploadFailed'         => _backupGuardT('File upload failed.', true)
            )
        );

        //  require_once(plugin_dir_path( __FILE__ ).'public/backups.php');
    }
}

//Cloud Page
function backup_guard_cloud_page()
{
    if (backupGuardValidateLicense()) {
        wp_enqueue_style('backup-guard-switch-css', plugin_dir_url(__FILE__) . 'public/css/bootstrap-switch.min.css');
        wp_enqueue_script('backup-guard-switch-js', plugin_dir_url(__FILE__) . 'public/js/bootstrap-switch.min.js', array('jquery'), SG_BACKUP_GUARD_VERSION, true);
        wp_enqueue_script('backup-guard-jquery-validate-js', plugin_dir_url(__FILE__) . 'public/js/jquery.validate.min.js', array('jquery', 'backup-guard-switch-js'), SG_BACKUP_GUARD_VERSION, true);
        wp_enqueue_script('backup-guard-cloud-js', plugin_dir_url(__FILE__) . 'public/js/sgcloud.js', array('jquery', 'backup-guard-switch-js'), SG_BACKUP_GUARD_VERSION, true);

        // Localize the script with new data
        wp_localize_script(
            'backup-guard-cloud-js',
            'BG_CLOUD_STRINGS',
            array(
                'invalidImportFile'             => _backupGuardT('Please select a file.', true),
                'invalidFileSize'               => _backupGuardT('File is too large.', true),
                'connectionInProgress'          => _backupGuardT('Connecting...', true),
                'invalidDestinationFolder'      => _backupGuardT('Destination folder is required.', true),
                'successMessage'                => _backupGuardT('Successfully saved.', true)
            )
        );

        //require_once(plugin_dir_path(__FILE__).'public/cloud.php');
    }
}

//Schedule Page
function backup_guard_schedule_page()
{
    if (backupGuardValidateLicense()) {
        wp_enqueue_style('backup-guard-switch-css', plugin_dir_url(__FILE__) . 'public/css/bootstrap-switch.min.css');
        wp_enqueue_script('backup-guard-switch-js', plugin_dir_url(__FILE__) . 'public/js/bootstrap-switch.min.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('backup-guard-schedule-js', plugin_dir_url(__FILE__) . 'public/js/sgschedule.js', array('jquery'), '1.0.0', true);

        // Localize the script with new data
        wp_localize_script(
            'backup-guard-schedule-js',
            'BG_SCHEDULE_STRINGS',
            array(
                'deletionError'            => _backupGuardT('Unable to delete schedule', true),
                'confirm'                  => _backupGuardT('Are you sure?', true),
                'invalidBackupOption'      => _backupGuardT('Please choose at least one option.', true),
                'invalidDirectorySelected' => _backupGuardT('Please choose at least one directory.', true),
                'invalidCloud'             => _backupGuardT('Please choose at least one cloud.', true),
                'savingInProgress'         => _backupGuardT('Saving...', true),
                'successMessage'           => _backupGuardT('You have successfully activated schedule.', true),
                'saveButtonText'           => _backupGuardT('Save', true)
            )
        );

        //  require_once(plugin_dir_path( __FILE__ ).'public/schedule.php');
    }
}

//Settings Page
function backup_guard_settings_page()
{
    if (backupGuardValidateLicense()) {
        wp_enqueue_style('backup-guard-switch-css', plugin_dir_url(__FILE__) . 'public/css/bootstrap-switch.min.css');
        wp_enqueue_script('backup-guard-switch-js', plugin_dir_url(__FILE__) . 'public/js/bootstrap-switch.min.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('backup-guard-settings-js', plugin_dir_url(__FILE__) . 'public/js/sgsettings.js', array('jquery'), '1.0.0', true);

        // Localize the script with new data
        wp_localize_script(
            'backup-guard-settings-js',
            'BG_SETTINGS_STRINGS',
            array(
                'invalidEmailAddress'             => _backupGuardT('Please enter valid email.', true),
                'invalidFileName'                 => _backupGuardT('Please enter valid file name.', true),
                'invalidRetentionNumber'          => _backupGuardT('Please enter a valid retention number.', true),
                'successMessage'                  => _backupGuardT('Successfully saved.', true),
                'savingInProgress'                => _backupGuardT('Saving...', true),
                'retentionConfirmationFirstPart'  => _backupGuardT('Are you sure you want to keep the latest', true),
                'retentionConfirmationSecondPart' => _backupGuardT('backups? All older backups will be deleted.', true),
                'saveButtonText'                  => _backupGuardT('Save', true)
            )
        );

        //require_once(plugin_dir_path(__FILE__).'public/settings.php');
    }
}

function backup_guard_login_page()
{
    wp_enqueue_script('backup-guard-login-js', plugin_dir_url(__FILE__) . 'public/js/sglogin.js', array('jquery'), '1.0.0', true);

    include_once plugin_dir_path(__FILE__) . 'public/login.php';
}

function backup_guard_link_license_page()
{
    wp_enqueue_script('backup-guard-license-js', plugin_dir_url(__FILE__) . 'public/js/sglicense.js', array('jquery'), '1.0.0', true);
    // Localize the script with new data
    wp_localize_script(
        'backup-guard-license-js',
        'BG_LICENSE_STRINGS',
        array(
            'invalidLicense'    => _backupGuardT('Please choose a license first', true),
            'availableLicenses' => _backupGuardT('There are no available licenses for using the selected product', true)
        )
    );

    include_once plugin_dir_path(__FILE__) . 'public/link_license.php';
}

    add_action('admin_enqueue_scripts', 'enqueue_backup_guard_scripts');
function enqueue_backup_guard_scripts($hook)
{
    wp_enqueue_script('backup-guard-discount-notice', plugin_dir_url(__FILE__) . 'public/js/sgNoticeDismiss.js', array('jquery'), '1.0', true);

    if (!strpos($hook, 'backup_guard')) {
        if ($hook == "index.php") {
            wp_enqueue_script('backup-guard-chart-manager', plugin_dir_url(__FILE__) . 'public/js/Chart.bundle.min.js');
        }
        return;
    }

    wp_enqueue_style('backup-guard-spinner', plugin_dir_url(__FILE__) . 'public/css/spinner.css');
    wp_enqueue_style('backup-guard-wordpress', plugin_dir_url(__FILE__) . 'public/css/bgstyle.wordpress.css');
    wp_enqueue_style('backup-guard-less', plugin_dir_url(__FILE__) . 'public/css/bgstyle.less.css');
    wp_enqueue_style('backup-guard-styles', plugin_dir_url(__FILE__) . 'public/css/styles.css');

    echo '<script type="text/javascript">sgBackup={};';
    $sgAjaxRequestFrequency = SGConfig::get('SG_AJAX_REQUEST_FREQUENCY');
    if (!$sgAjaxRequestFrequency) {
        $sgAjaxRequestFrequency = SG_AJAX_DEFAULT_REQUEST_FREQUENCY;
    }
    echo 'SG_AJAX_REQUEST_FREQUENCY = "' . $sgAjaxRequestFrequency . '";';
    echo 'function getAjaxUrl(url) {' .
         'if (url==="cloudDropbox" || url==="cloudGdrive" || url==="cloudOneDrive"  || url==="cloudPCloud" || url==="cloudBox") return "' . admin_url('admin-post.php?action=backup_guard_') . '"+url+"&token=' . wp_create_nonce('backupGuardAjaxNonce') . '";' .
         'return "' . admin_url('admin-ajax.php') . '";}</script>';

    wp_enqueue_media();
    wp_enqueue_script('backup-guard-less-framework', plugin_dir_url(__FILE__) . 'public/js/less.min.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('backup-guard-bootstrap-framework', plugin_dir_url(__FILE__) . 'public/js/bootstrap.min.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('backup-guard-sgrequest-js', plugin_dir_url(__FILE__) . 'public/js/sgrequesthandler.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('backup-guard-sgwprequest-js', plugin_dir_url(__FILE__) . 'public/js/sgrequesthandler.wordpress.js', array('jquery'), '1.0.0', true);

    wp_enqueue_style('backup-guard-rateyo-css', plugin_dir_url(__FILE__) . 'public/css/jquery.rateyo.css');
    wp_enqueue_script('backup-guard-rateyo-js', plugin_dir_url(__FILE__) . 'public/js/jquery.rateyo.js');

    wp_enqueue_script('backup-guard-main-js', plugin_dir_url(__FILE__) . 'public/js/main.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('backup-popup.js', plugin_dir_url(__FILE__) . 'public/js/popup.js', array('jquery'), '1.0.0', true);
    wp_enqueue_style('popupTheme.css', plugin_dir_url(__FILE__) . 'public/css/popupTheme.css');

    // Localize the script with new data
    wp_localize_script(
        'backup-guard-main-js',
        'BG_MAIN_STRINGS',
        array(
            'confirmCancel' => _backupGuardT('Are you sure you want to cancel?', true)
        )
    );

    wp_localize_script(
        'backup-guard-main-js',
        'BG_BACKUP_STRINGS',
        array(
            'nonce' => wp_create_nonce('backupGuardAjaxNonce')
        )
    );
}

// adding actions to handle modal ajax requests
    add_action('wp_ajax_backup_guard_modalManualBackup', 'backup_guard_get_manual_modal');
    add_action('wp_ajax_backup_guard_modalManualRestore', 'backup_guard_get_manual_restore_modal');
    add_action('wp_ajax_backup_guard_modalImport', 'backup_guard_get_import_modal');
    add_action('wp_ajax_backup_guard_modalFtpSettings', 'backup_guard_get_ftp_modal');
    add_action('wp_ajax_backup_guard_modalAmazonSettings', 'backup_guard_get_amazon_modal');
    add_action('wp_ajax_backup_guard_modalPrivacy', 'backup_guard_get_privacy_modal');
    add_action('wp_ajax_backup_guard_modalTerms', 'backup_guard_get_terms_modal');
    add_action('wp_ajax_backup_guard_modalReview', 'backup_guard_get_review_modal');
    add_action('wp_ajax_backup_guard_getFileDownloadProgress', 'backup_guard_get_file_download_progress');
    add_action('wp_ajax_backup_guard_modalCreateSchedule', 'backup_guard_create_schedule');
    add_action('wp_ajax_backup_guard_getBackupContent', 'backup_guard_get_backup_content');

    add_action('wp_ajax_backup_guard_modalBackupGuardDetails', 'backup_guard_get_backup_guard_modal');

function backup_guard_get_backup_guard_modal()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'modalBackupGuardDetails.php';
    exit();
}

function backup_guard_get_file_download_progress()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'getFileDownloadProgress.php';
    exit();
}

function backup_guard_create_schedule()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'modalCreateSchedule.php';
    exit();
}

function backup_guard_get_manual_modal()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    if (current_user_can('activate_plugins') || (defined('SG_USER_MODE') && SG_USER_MODE)) {
        include_once SG_PUBLIC_AJAX_PATH . 'modalManualBackup.php';
    }
    exit();
}

function backup_guard_get_manual_restore_modal()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'modalManualRestore.php';
    exit();
}

function backup_guard_get_backup_content()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'getBackupContent.php';
    exit();
}

function backup_guard_get_import_modal()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'modalImport.php';
    exit();
}

function backup_guard_get_ftp_modal()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'modalFtpSettings.php';
    exit();
}

function backup_guard_get_amazon_modal()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'modalAmazonSettings.php';
    exit();
}

function backup_guard_get_privacy_modal()
{
    include_once SG_PUBLIC_AJAX_PATH . 'modalPrivacy.php';
}

function backup_guard_get_terms_modal()
{
    include_once SG_PUBLIC_AJAX_PATH . 'modalTerms.php';
    exit();
}

function backup_guard_get_review_modal()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'modalReview.php';
    exit();
}

function backup_guard_register_ajax_callbacks()
{
    if (is_super_admin() || (defined('SG_USER_MODE') && SG_USER_MODE)) {
        // adding actions to handle ajax and post requests
        add_action('wp_ajax_backup_guard_cancelBackup', 'backup_guard_cancel_backup');
        add_action('wp_ajax_backup_guard_checkBackupCreation', 'backup_guard_check_backup_creation');
        add_action('wp_ajax_backup_guard_checkRestoreCreation', 'backup_guard_check_restore_creation');
        add_action('wp_ajax_backup_guard_cloudDropbox', 'backup_guard_cloud_dropbox');
        add_action('wp_ajax_backup_guard_send_usage_status', 'backup_guard_send_usage_status');

        $pluginCapabilities = backupGuardGetCapabilities();
        if ($pluginCapabilities != BACKUP_GUARD_CAPABILITIES_FREE) {
            include_once dirname(__FILE__) . '/BackupGuardPro.php';
        }
        add_action('wp_ajax_backup_guard_curlChecker', 'backup_guard_curl_checker');
        add_action('wp_ajax_backup_guard_deleteBackup', 'backup_guard_delete_backup');
        add_action('wp_ajax_backup_guard_getAction', 'backup_guard_get_action');
        add_action('wp_ajax_backup_guard_getRunningActions', 'backup_guard_get_running_actions');
        add_action('wp_ajax_backup_guard_importBackup', 'backup_guard_get_import_backup');
        add_action('wp_ajax_backup_guard_resetStatus', 'backup_guard_reset_status');
        add_action('wp_ajax_backup_guard_restore', 'backup_guard_restore');
        add_action('wp_ajax_backup_guard_saveCloudFolder', 'backup_guard_save_cloud_folder');
        add_action('wp_ajax_backup_guard_schedule', 'backup_guard_schedule');
        add_action('wp_ajax_backup_guard_settings', 'backup_guard_settings');
        add_action('wp_ajax_backup_guard_setReviewPopupState', 'backup_guard_set_review_popup_state');
        add_action('wp_ajax_backup_guard_sendUsageStatistics', 'backup_guard_send_usage_statistics');
        add_action('wp_ajax_backup_guard_hideNotice', 'backup_guard_hide_notice');
        add_action('wp_ajax_backup_guard_downloadFromCloud', 'backup_guard_download_from_cloud');
        add_action('wp_ajax_backup_guard_listStorage', 'backup_guard_list_storage');
        add_action('wp_ajax_backup_guard_cancelDownload', 'backup_guard_cancel_download');
        add_action('wp_ajax_backup_guard_awake', 'backup_guard_awake');
        add_action('wp_ajax_backup_guard_manualBackup', 'backup_guard_manual_backup');
        add_action('admin_post_backup_guard_downloadBackup', 'backup_guard_download_backup');
        add_action('wp_ajax_backup_guard_login', 'backup_guard_login');
        add_action('wp_ajax_backup_guard_logout', 'backup_guard_logout');
        add_action('wp_ajax_backup_guard_link_license', 'backup_guard_link_license');
        add_action('wp_ajax_backup_guard_importKeyFile', 'backup_guard_import_key_file');
        add_action('wp_ajax_backup_guard_isFeatureAvailable', 'backup_guard_is_feature_available');
        add_action('wp_ajax_backup_guard_dismiss_discount_notice', 'backup_guard_dismiss_discount_notice');
        add_action('wp_ajax_backup_guard_checkFreeMigration', 'backup_guard_check_free_migration');
        add_action('wp_ajax_backup_guard_checkPHPVersionCompatibility', 'backup_guard_check_php_version_compatibility');
        add_action('wp_ajax_backup_guard_setUserInfoVerificationPopupState', 'backup_guard_set_user_info_verification_popup_state');
        add_action('wp_ajax_backup_guard_storeSubscriberInfo', 'backup_guard_store_subscriber_info');
        add_action('wp_ajax_backup_guard_storeSurveyResult', 'backup_guard_store_survey_result');
        add_action('wp_ajax_backup_guard_reviewDontShow', 'backup_guard_review_dont_show');
        add_action('wp_ajax_backup_guard_review_later', 'backup_guard_review_later');
        add_action('wp_ajax_backup_guard_closeFreeBanner', 'wp_ajax_backup_guard_close_free_banner');
        add_action('wp_ajax_backup_guard_closeChristmasBanner', 'wp_ajax_backup_guard_close_christmas_banner');
        // related to cloud
        add_action('wp_ajax_backup_guard_isBgUserExists', 'backup_guard_is_bg_user_exists');
        add_action('wp_ajax_backup_guard_createCloudUser', 'backup_guard_create_cloud_user');
        add_action('wp_ajax_backup_guard_bgAutoLogin', 'backup_guard_bg_auto_login');
        add_action('wp_ajax_backup_guard_bgLogin', 'backup_guard_bg_login');
        add_action('wp_ajax_backup_guard_chooseProfile', 'backup_guard_choose_profile');
    }
}

function wp_ajax_backup_guard_close_free_banner()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    SGConfig::set('SG_CLOSE_FREE_BANNER', 1);
    wp_die();
}

function wp_ajax_backup_guard_close_christmas_banner()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    SGConfig::set('SG_CLOSE_CHRISTMAS_BANNER', 1);
    wp_die();
}

function backup_guard_review_dont_show()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    SGConfig::set('closeReviewBanner', 1);
    wp_die();
}

function backup_guard_review_later()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'reviewBannerActions.php';
    wp_die();
}

function backup_guard_choose_profile()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'chooseProfile.php';
}

function backup_guard_bg_login()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'bgLogin.php';
}

function backup_guard_bg_auto_login()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'bgAutoLogin.php';
}

function backup_guard_create_cloud_user()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'createCloudUser.php';
}

function backup_guard_is_bg_user_exists()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'isBgUserExists.php';
}

function backup_guard_store_survey_result()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'storeSurveyResult.php';
}

function backup_guard_store_subscriber_info()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'storeSubscriberInfo.php';
}

function backup_guard_set_user_info_verification_popup_state()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'setUserInfoVerificationPopupState.php';
}

function backup_guard_dismiss_discount_notice()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'dismissDiscountNotice.php';
}

function backup_guard_is_feature_available()
{
    include_once SG_PUBLIC_AJAX_PATH . 'isFeatureAvailable.php';
}

function backup_guard_check_free_migration()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'checkFreeMigration.php';
    die;
}

function backup_guard_check_php_version_compatibility()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'checkPHPVersionCompatibility.php';
}

    add_action('init', 'backup_guard_init');
    add_action('wp_ajax_nopriv_backup_guard_awake', 'backup_guard_awake_nopriv');
    add_action('admin_post_backup_guard_cloudDropbox', 'backup_guard_cloud_dropbox');

function backup_guard_import_key_file()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'importKeyFile.php';
}

function backup_guard_awake()
{
    $method = SG_RELOAD_METHOD_AJAX;
    include_once SG_PUBLIC_AJAX_PATH . 'awake.php';
}

function backup_guard_awake_nopriv()
{
    $token = @$_GET['token'];
    $method = @$_GET['method'];

    if (backupGuardValidateApiCall($token)) {
        include_once SG_PUBLIC_AJAX_PATH . 'awake.php';
    }
}

function backup_guard_cancel_download()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'cancelDownload.php';
}

function backup_guard_list_storage()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'listStorage.php';
}

function backup_guard_download_from_cloud()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'downloadFromCloud.php';
}

function backup_guard_hide_notice()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'hideNotice.php';
}

function backup_guard_cancel_backup()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'cancelBackup.php';
}

function backup_guard_check_backup_creation()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'checkBackupCreation.php';
}

function backup_guard_check_restore_creation()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'checkRestoreCreation.php';
}

function backup_guard_cloud_dropbox()
{
    if (current_user_can('activate_plugins') || (defined('SG_USER_MODE') && SG_USER_MODE)) {
        check_ajax_referer('backupGuardAjaxNonce', 'token');
        include_once SG_PUBLIC_AJAX_PATH . 'cloudDropbox.php';
    }
}

function backup_guard_send_usage_status()
{

    if (current_user_can('activate_plugins') || (defined('SG_USER_MODE') && SG_USER_MODE)) {
        check_ajax_referer('backupGuardAjaxNonce', 'token');
        include_once SG_PUBLIC_AJAX_PATH . 'sendUsageStatus.php';
    }
}

function backup_guard_curl_checker()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'curlChecker.php';
}

function backup_guard_delete_backup()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'deleteBackup.php';
}

function backup_guard_download_backup()
{
    include_once SG_PUBLIC_AJAX_PATH . 'downloadBackup.php';
}

function backup_guard_get_action()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'getAction.php';
}

function backup_guard_get_running_actions()
{
    include_once SG_PUBLIC_AJAX_PATH . 'getRunningActions.php';
}

function backup_guard_get_import_backup()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'importBackup.php';
}

function backup_guard_manual_backup()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'manualBackup.php';
}

function backup_guard_reset_status()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'resetStatus.php';
}

function backup_guard_restore()
{
    include_once SG_PUBLIC_AJAX_PATH . 'restore.php';
}

function backup_guard_save_cloud_folder()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'saveCloudFolder.php';
}

function backup_guard_schedule()
{
    include_once SG_PUBLIC_AJAX_PATH . 'schedule.php';
}

function backup_guard_settings()
{
    include_once SG_PUBLIC_AJAX_PATH . 'settings.php';
}

function backup_guard_set_review_popup_state()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'setReviewPopupState.php';
}

function backup_guard_send_usage_statistics()
{
    include_once SG_PUBLIC_AJAX_PATH . 'sendUsageStatistics.php';
}

function backup_guard_login()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'login.php';
}

function backup_guard_logout()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'logout.php';
}

function backup_guard_link_license()
{
    check_ajax_referer('backupGuardAjaxNonce', 'token');
    include_once SG_PUBLIC_AJAX_PATH . 'linkLicense.php';
}

//adds once weekly to the existing schedules.
    add_filter('cron_schedules', 'backup_guard_cron_add_weekly');
function backup_guard_cron_add_weekly($schedules)
{
    $schedules['weekly'] = array(
        'interval' => 60 * 60 * 24 * 7,
        'display' => 'Once weekly'
    );
    return $schedules;
}

//adds once monthly to the existing schedules.
    add_filter('cron_schedules', 'backup_guard_cron_add_monthly');
function backup_guard_cron_add_monthly($schedules)
{
    $schedules['monthly'] = array(
        'interval' => 60 * 60 * 24 * 30,
        'display' => 'Once monthly'
    );
    return $schedules;
}

//adds once yearly to the existing schedules.
    add_filter('cron_schedules', 'backup_guard_cron_add_yearly');
function backup_guard_cron_add_yearly($schedules)
{
    $schedules['yearly'] = array(
        'interval' => 60 * 60 * 24 * 30 * 12,
        'display' => 'Once yearly'
    );
    return $schedules;
}

function backup_guard_init()
{
    backup_guard_register_ajax_callbacks();
    // backupGuardPluginRedirect();

    //check if database should be updated
    if (backupGuardShouldUpdate()) {
        SGBoot::install();
    }

    backupGuardSymlinksCleanup(SG_SYMLINK_PATH);
}

    add_action(SG_SCHEDULE_ACTION, 'backup_guard_schedule_action', 10, 1);

function backup_guard_schedule_action($id)
{
    include_once SG_PUBLIC_PATH . 'cron/sg_backup.php';
}

function sgBackupAdminInit()
{
    //load pro plugin updater
    $pluginCapabilities = backupGuardGetCapabilities();
    $isLoggedIn = is_user_logged_in();

    if ($pluginCapabilities != BACKUP_GUARD_CAPABILITIES_FREE && $isLoggedIn) {
        include_once dirname(__FILE__) . '/plugin-update-checker/plugin-update-checker.php';
        include_once dirname(__FILE__) . '/plugin-update-checker/Puc/v4/Utils.php';
        include_once dirname(__FILE__) . '/plugin-update-checker/Puc/v4/UpdateChecker.php';
        include_once dirname(__FILE__) . '/plugin-update-checker/Puc/v4/Scheduler.php';
        include_once SG_LIB_PATH . 'SGAuthClient.php';

        $licenseKey = SGConfig::get('SG_LICENSE_KEY');

        $updateChecker = Puc_v4_Factory::buildUpdateChecker(
            BackupGuard\Config::URL . '/products/details/' . $licenseKey . '/' . SG_PRODUCT_IDENTIFIER,
            SG_BACKUP_GUARD_MAIN_FILE,
            SG_PRODUCT_IDENTIFIER
        );

        $updateChecker->addHttpRequestArgFilter(
            array(
            SGAuthClient::getInstance(),
            'filterUpdateChecks'
            )
        );
    }

    include_once SG_LIB_PATH . 'SGStatsRequests.php';
    SGStatsRequests::initialSync();
}

    add_action('admin_init', 'sgBackupAdminInit');

if (SGBoot::isFeatureAvailable('ALERT_BEFORE_UPDATE')) {
    add_filter('upgrader_pre_download', 'backupGuardOnBeforeUpdateDownload', 10, 3);
    add_action('core_upgrade_preamble', 'backupGuardOnUpgradeScreenActivate');
    add_action('current_screen', 'backupGuardOnScreenActivate');
}

// Register the new dashboard widget with the 'wp_dashboard_setup' action
    add_action('wp_dashboard_setup', 'backup_guard_add_dashboard_widgets');

function backup_guard_add_dashboard_widgets()
{
    include_once SG_CORE_PATH . 'SGConfig.php';

    $userId = get_current_user_id();
    $userData = get_userdata($userId);
    $userRoles = $userData->roles;
    $isAdminUser = false;
    for ($i = 0; $i < count($userRoles); $i++) {
        if ($userRoles[$i] == "administrator") {
            $isAdminUser = true;
            break;
        }
    }

    if (!$isAdminUser) {
        return;
    }

    $isShowStatisticsWidgetEnabled = SGConfig::get('SG_SHOW_STATISTICS_WIDGET');
    if (!$isShowStatisticsWidgetEnabled) {
        return;
    }


    include_once plugin_dir_path(__FILE__) . 'public/dashboardWidget.php';
    wp_add_dashboard_widget('backupGuardWidget', 'Backup Guard', 'backup_guard_dashboard_widget_function');
}

    add_action('plugins_loaded', 'backupGuardloadTextDomain');
function backupGuardloadTextDomain()
{
    $backupGuardLangDir = plugin_dir_path(__FILE__) . 'languages/';
    $backupGuardLangDir = apply_filters('backupguardLanguagesDirectory', $backupGuardLangDir);

    $locale = apply_filters('bg_plugin_locale', get_locale(), BACKUP_GUARD_TEXTDOMAIN);
    $mofile = sprintf('%1$s-%2$s.mo', BACKUP_GUARD_TEXTDOMAIN, $locale);

    $mofileLocal = $backupGuardLangDir . $mofile;

    if (file_exists($mofileLocal)) {
        // Look in local /wp-content/plugins/popup-builder/languages/ folder
        load_textdomain(BACKUP_GUARD_TEXTDOMAIN, $mofileLocal);
    } else {
        // Load the default language files
        load_plugin_textdomain(BACKUP_GUARD_TEXTDOMAIN, false, $backupGuardLangDir);
    }
}

if (backupGuardShouldShowDiscountNotice() && checkDueDateDiscount()) {
    add_action('admin_notices', 'backup_guard_discount_notice');
}

function backup_guard_discount_notice()
{
    /*$capabilities = backupGuardGetCapabilities();
    $upgradeUrl = BG_UPGRADE_URL;*/
    ?>
        <div class="backup-guard-discount-notice updated notice is-dismissible">
            <div class="sgbg-col sgbg-col1"></div>
            <div class="sgbg-col sgbg-col2"></div>
            <div class="sgbg-col sgbg-col3">
                <div class="sgbg-text-col-1">
                    -50%
                </div>
                <div class="sgbg-text-col-2">
                    <div class="sgbg-discount-text-1">Discount</div>
                    <div class="sgbg-discount-text-2">All Backup Guard Solutions</div>
                </div>
            </div>
            <div class="sgbg-col sgbg-col4">
                <a href="https://backup-guard.com/products/backup-wordpress" target="_blank"><button class="sgbg-button">Click Here</button></a>
            </div>
        </div>
        <style>
            .backup-guard-discount-notice.updated.notice.is-dismissible {
                padding: 0;
                border-left-color: #FFFFFF !important;
                background-color: #000000;
                height: 160px;
            }
            .backup-guard-discount-notice button:before {
                color: #ffffff !important;
            }
            .sgbg-col {
                display: inline-block;
                width: 25%;
                height: 100%;
                padding: 0 25px;
                box-sizing: border-box;
            }
            .sgbg-col1 {
                width: 10%;
                background-color: #FFFFFF;
                background-image: url("<?php echo SG_IMAGE_URL ?>BgBFLogo.jpg");
                background-size: 80%;
                background-repeat: no-repeat;
                background-position: center;
            }
            .sgbg-col2 {
                width: 20%;
                background-image: url("<?php echo SG_IMAGE_URL ?>BF.png");
                background-size: contain;
                margin-left: 70px;
                background-position: center;
                background-repeat: no-repeat;
            }
            .sgbg-col3 {
                vertical-align: top;
                width: 45%;
                margin-top: 55px;
            }
            .sgbg-col4 {
                width: 10%;
            }
            .sgbg-text-col-1,
            .sgbg-text-col-2 {
                width: 49%;
                display: inline-block;
                color: #FFFFFF;
            }
            .sgbg-text-col-1 {
                font-size: 100px;
                line-height: 0;
                font-weight: bold;
                text-align: right;
                padding-right: 26px;
                box-sizing: border-box;
            }
            .sgbg-discount-text-2 {
                font-size: 19px;
            }
            .sgbg-discount-text-1 {
                font-size: 60px;
                padding-bottom: 27px;
                font-weight: bold;
            }
            .sgbg-col4 {
                vertical-align: top;
            }
            .sgbg-button {
                width: 183px;
                height: 67px;
                font-size: 20px;
                border: #ffffff;
                border-radius: 10px;
                margin-top: 48px;
                background-color: #FFFFFF;
                color: #000000;
                cursor: pointer !important;
            }
            .sgbg-button:hover {
                background-color: #000000;
                border: 1px solid #FFFFFF;
                color: #FFFFFF;
            }
            .backup-guard-discount-notice .notice-dismiss::before {
                content: "x";
                font-weight: 300;
                font-family: Arial, sans-serif;
            }

            @media (max-width: 1810px) {
                .sgbg-text-col-1 {
                    font-size: 80px;
                }
                .sgbg-discount-text-1 {
                    font-size: 43px;
                }
                .sgbg-discount-text-2 {
                    font-size: 15px;
                }
                .sgbg-discount-text-1 {
                    padding-bottom: 18px;
                }
                .sgbg-col3 {
                    margin-top: 60px;
                }
            }
            @media (max-width: 1477px) {
                .sgbg-discount-text-2 {
                    font-size: 12px;
                }
                .sgbg-discount-text-1 {
                    font-size: 35px;
                }
                .sgbg-discount-text-1 {
                    padding-bottom: 13px;
                }
                .sgbg-col {
                    padding: 0;
                }
                .sgbg-col2 {
                    margin-left: 40px;
                }
                .sgbg-col2 {
                    margin-left: 0;
                }
            }
        </style>
        <?php
}

    add_action('admin_notices', 'backup_guard_review_banner');
function backup_guard_review_banner()
{
    include_once SG_LIB_PATH . 'SGReviewManager.php';
    $reviewManager = new SGReviewManager();
    $reviewManager->renderContent();
}
