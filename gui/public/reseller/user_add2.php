<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2016 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get parameters from previous page.
 *
 * @return bool TRUE if parameters from previous page are found, FALSE otherwise
 */
function get_pageone_param()
{
    global $dmnName, $dmnExpire, $hpId;

    if (!isset($_SESSION['dmn_name'])) {
        return false;
    }

    $dmnName = $_SESSION['dmn_name'];
    $dmnExpire = $_SESSION['dmn_expire'];
    $hpId = $_SESSION['dmn_tpl'];
    return true;
}

/**
 * Show page with initial data fields
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param iMSCP_PHPini $phpini
 * @return void
 */
function get_init_au2_page($tpl, $phpini)
{
    global $hpName, $php, $cgi, $sub, $als, $mail, $mailQuota, $ftp, $sqlDb, $sqlUser, $traffic, $diskSpace, $backup,
           $dns, $aps, $extMailServer, $webFolderProtection;

    $cfg = iMSCP_Registry::get('config');

    $tpl->assign(array(
        'VL_TEMPLATE_NAME' => tohtml($hpName),
        'MAX_DMN_CNT' => '',
        'MAX_SUBDMN_CNT' => tohtml($sub),
        'MAX_DMN_ALIAS_CNT' => tohtml($als),
        'MAX_MAIL_CNT' => tohtml($mail),
        'MAIL_QUOTA' => tohtml($mailQuota),
        'MAX_FTP_CNT' => tohtml($ftp),
        'MAX_SQL_CNT' => tohtml($sqlDb),
        'VL_MAX_SQL_USERS' => tohtml($sqlUser),
        'VL_MAX_TRAFFIC' => tohtml($traffic),
        'VL_MAX_DISK_USAGE' => tohtml($diskSpace),
        'VL_EXTMAILY' => $extMailServer == '_yes_' ? ' checked' : '',
        'VL_EXTMAILN' => $extMailServer == '_no_' ? ' checked' : '',
        'VL_PHPY' => $php == '_yes_' ? ' checked' : '',
        'VL_PHPN' => $php == '_no_' ? ' checked' : '',
        'VL_CGIY' => $cgi == '_yes_' ? ' checked' : '',
        'VL_CGIN' => $cgi == '_no_' ? ' checked' : ''
    ));

    if (resellerHasFeature('custom_dns_records')) {
        $tpl->assign(array(
            'VL_DNSY' => $dns == '_yes_' ? ' checked' : '',
            'VL_DNSN' => $dns == '_no_' ? ' checked' : ''
        ));
    }

    if (resellerHasFeature('aps')) {
        $tpl->assign(array(
            'VL_SOFTWAREY' => $aps == '_yes_' ? ' checked' : '',
            'VL_SOFTWAREN' => $aps == '_no_' ? ' checked' : ''
        ));
    }

    if (resellerHasFeature('backup')) {
        $tpl->assign(array(
            'VL_BACKUPD' => in_array('_dmn_', $backup) ? ' checked' : '',
            'VL_BACKUPS' => in_array('_sql_', $backup) ? ' checked' : '',
            'VL_BACKUPM' => in_array('_mail_', $backup) ? ' checked' : ''
        ));
    }

    $tpl->assign(array(
        'VL_WEB_FOLDER_PROTECTION_YES' => $webFolderProtection == '_yes_' ? ' checked' : '',
        'VL_WEB_FOLDER_PROTECTION_NO' => $webFolderProtection == '_no_' ? ' checked' : ''
    ));

    if ($phpini->resellerHasPermission('phpiniSystem')) {
        $tpl->assign(array(
            'PHP_EDITOR_YES' => $phpini->clientHasPermission('phpiniSystem') ? ' checked' : '',
            'PHP_EDITOR_NO' => $phpini->clientHasPermission('phpiniSystem') ? '' : ' checked',
            'TR_PHP_EDITOR' => tr('PHP Editor'),
            'TR_PHP_EDITOR_SETTINGS' => tr('PHP Editor Settings'),
            'TR_SETTINGS' => tr('Settings'),
            'TR_DIRECTIVES_VALUES' => tr('Directive values'),
            'TR_FIELDS_OK' => tr('All fields are valid.'),
            'TR_MIB' => tr('MiB'),
            'TR_SEC' => tr('Sec.')
        ));

        iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
            /** @var iMSCP_Events_Event $e */
            $translations = $e->getParam('translations');
            $translations['core']['close'] = tr('Close');
            $translations['core']['fields_ok'] = tr('All fields are valid.');
            $translations['core']['out_of_range_value_error'] = tr('Value for the PHP %%s directive must be in range %%d to %%d.');
            $translations['core']['lower_value_expected_error'] = tr('%%s must be lower than %%s.');
            $translations['core']['error_field_stack'] = iMSCP_Registry::isRegistered('errFieldsStack')
                ? iMSCP_Registry::get('errFieldsStack') : array();
        });

        $permissionsBlock = false;

        if (!$phpini->resellerHasPermission('phpiniAllowUrlFopen')) {
            $tpl->assign('PHP_EDITOR_ALLOW_URL_FOPEN_BLOCK', '');
        } else {
            $tpl->assign(array(
                'TR_CAN_EDIT_ALLOW_URL_FOPEN' => tr('Can edit the PHP %s directive', '<b>allow_url_fopen</b>'),
                'ALLOW_URL_FOPEN_YES' => $phpini->clientHasPermission('phpiniAllowUrlFopen') ? ' checked' : '',
                'ALLOW_URL_FOPEN_NO' => $phpini->clientHasPermission('phpiniAllowUrlFopen') ? '' : ' checked'
            ));
            $permissionsBlock = true;
        }

        if (!$phpini->resellerHasPermission('phpiniDisplayErrors')) {
            $tpl->assign('PHP_EDITOR_DISPLAY_ERRORS_BLOCK', '');
        } else {
            $tpl->assign(array(
                'TR_CAN_EDIT_DISPLAY_ERRORS' => tr('Can edit the PHP %s directive', '<b>display_errors</b>'),
                'DISPLAY_ERRORS_YES' => $phpini->clientHasPermission('phpiniDisplayErrors') ? ' checked' : '',
                'DISPLAY_ERRORS_NO' => $phpini->clientHasPermission('phpiniDisplayErrors') ? '' : ' checked'
            ));
            $permissionsBlock = true;
        }

        if ($cfg['HTTPD_SERVER'] == 'apache_itk') {
            $tpl->assign(array(
                'PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK' => '',
                'PHP_EDITOR_MAIL_FUNCTION_BLOCK' => ''
            ));
        } else {
            if ($phpini->resellerHasPermission('phpiniDisableFunctions')) {
                $tpl->assign(array(
                    'TR_CAN_EDIT_DISABLE_FUNCTIONS' => tr('Can edit the PHP %s directive', '<b>disable_functions</b>'),
                    'DISABLE_FUNCTIONS_YES' => $phpini->getClientPermission('phpiniDisableFunctions') == 'yes' ? ' checked' : '',
                    'DISABLE_FUNCTIONS_NO' => $phpini->getClientPermission('phpiniDisableFunctions') == 'no' ? ' checked' : '',
                    'DISABLE_FUNCTIONS_EXEC' => $phpini->getClientPermission('phpiniDisableFunctions') == 'exec' ? ' checked' : '',
                    'TR_ONLY_EXEC' => tr('Only exec')
                ));
            } else {
                $tpl->assign('PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK', '');
            }

            if ($phpini->resellerHasPermission('phpiniMailFunction')) {
                $tpl->assign(array(
                    'TR_CAN_USE_MAIL_FUNCTION' => tr('Can use the PHP %s function', '<b>mail</b>'),
                    'MAIL_FUNCTION_YES' => $phpini->clientHasPermission('phpiniMailFunction') ? ' checked' : '',
                    'MAIL_FUNCTION_NO' => $phpini->clientHasPermission('phpiniMailFunction') ? '' : ' checked'
                ));
            } else {
                $tpl->assign('PHP_EDITOR_MAIL_FUNCTION_BLOCK', '');
            }

            $permissionsBlock = true;
        }

        if (!$permissionsBlock) {
            $tpl->assign('PHP_EDITOR_PERMISSIONS_BLOCK', '');
        } else {
            $tpl->assign(array(
                'TR_PERMISSIONS' => tr('Permissions'),
                'TR_ONLY_EXEC' => tr("Only exec")
            ));
        }

        $tpl->assign(array(
            'TR_POST_MAX_SIZE' => tr('PHP %s directive', '<b>post_max_size</b>'),
            'POST_MAX_SIZE' => tohtml($phpini->getDomainIni('phpiniPostMaxSize')),
            'TR_UPLOAD_MAX_FILEZISE' => tr('PHP %s directive', '<b>upload_max_filesize</b>'),
            'UPLOAD_MAX_FILESIZE' => tohtml($phpini->getDomainIni('phpiniUploadMaxFileSize')),
            'TR_MAX_EXECUTION_TIME' => tr('PHP %s directive', '<b>max_execution_time</b>'),
            'MAX_EXECUTION_TIME' => tohtml($phpini->getDomainIni('phpiniMaxExecutionTime')),
            'TR_MAX_INPUT_TIME' => tr('PHP %s directive', '<b>max_input_time</b>'),
            'MAX_INPUT_TIME' => tohtml($phpini->getDomainIni('phpiniMaxInputTime')),
            'TR_MEMORY_LIMIT' => tr('PHP %s directive', '<b>memory_limit</b>'),
            'MEMORY_LIMIT' => tohtml($phpini->getDomainIni('phpiniMemoryLimit')),

            'POST_MAX_SIZE_LIMIT' => $phpini->getResellerPermission('phpiniPostMaxSize'),
            'UPLOAD_MAX_FILESIZE_LIMIT' => $phpini->getResellerPermission('phpiniUploadMaxFileSize'),
            'MAX_EXECUTION_TIME_LIMIT' => $phpini->getResellerPermission('phpiniMaxExecutionTime'),
            'MAX_INPUT_TIME_LIMIT' => $phpini->getResellerPermission('phpiniMaxInputTime'),
            'MEMORY_LIMIT_LIMIT' => $phpini->getResellerPermission('phpiniMemoryLimit')
        ));
        return;
    }

    $tpl->assign('PHP_EDITOR_BLOCK', '');

}

/**
 * Get hosting plan data.
 *
 * @param int $hpid Hosting plan unique identifier
 * @param int $resellerId Reseller unique identifier
 * @param iMSCP_PHPini $phpini
 * @return void
 */
function reseller_getHostingPlanData($hpid, $resellerId, $phpini)
{
    global $hpName, $php, $cgi, $sub, $als, $mail, $mailQuota, $ftp, $sqlDb, $sqlUser, $traffic, $diskSpace, $backup,
           $dns, $aps, $extMailServer, $webFolderProtection;

    if ($hpid != 0) {
        $stmt = exec_query('SELECT name, props FROM hosting_plans WHERE reseller_id = ? AND id = ?', array(
            $resellerId, $hpid
        ));

        if (!$stmt->rowCount()) {
            showBadRequestErrorPage();
        }

        $data = $stmt->fetchRow();

        list(
            $php, $cgi, $sub, $als, $mail, $ftp, $sqlDb, $sqlUser, $traffic, $diskSpace, $backup, $dns, $aps,
            $phpEditor, $phpiniAllowUrlFopen, $phpiniDisplayErrors, $phpiniDisableFunctions, $phpiniMailFunction,
            $phpiniPostMaxSize, $phpiniUploadMaxFileSize, $phpiniMaxExecutionTime, $phpiniMaxInputTime,
            $phpiniMemoryLimit, $extMailServer, $webFolderProtection, $mailQuota
            ) = explode(';', $data['props']);

        $backup = explode('|', $backup);
        $mailQuota = ($mailQuota != '0') ? $mailQuota / 1048576 : '0';
        $hpName = $data['name'];

        $phpini->setClientPermission('phpiniSystem', $phpEditor);
        $phpini->setClientPermission('phpiniAllowUrlFopen', $phpiniAllowUrlFopen);
        $phpini->setClientPermission('phpiniDisplayErrors', $phpiniDisplayErrors);
        $phpini->setClientPermission('phpiniDisableFunctions', $phpiniDisableFunctions);
        $phpini->setClientPermission('phpiniMailFunction', $phpiniMailFunction);

        $phpini->setDomainIni('phpiniPostMaxSize', $phpiniPostMaxSize);
        $phpini->setDomainIni('phpiniUploadMaxFileSize', $phpiniUploadMaxFileSize);
        $phpini->setDomainIni('phpiniMaxExecutionTime', $phpiniMaxExecutionTime);
        $phpini->setDomainIni('phpiniMaxInputTime', $phpiniMaxInputTime);
        $phpini->setDomainIni('phpiniMemoryLimit', $phpiniMemoryLimit);
        return;
    }

    $hpName = 'Custom';
    $sub = $als = $mail = $mailQuota = $ftp = $sqlDb = $sqlUser = $traffic = $diskSpace = '0';
    $php = $cgi = $dns = $aps = $extMailServer = '_no_';
    $backup = array();
    $webFolderProtection = '_yes_';
}

/**
 * Check validity of input data
 *
 * @param iMSCP_PHPini $phpini
 * @return bool TRUE if all data are valid, FALSE otherwise
 */
function check_user_data($phpini)
{
    global $php, $cgi, $sub, $als, $mail, $mailQuota, $ftp, $sqlDb, $sqlUser, $traffic, $diskSpace, $backup, $dns, $aps,
           $extMailServer, $webFolderProtection;

    // Subdomains limit
    if (isset($_POST['nreseller_max_subdomain_cnt'])) {
        $sub = clean_input($_POST['nreseller_max_subdomain_cnt']);
    }

    if (!resellerHasFeature('subdomains')) {
        $sub = '-1';
    } elseif (!imscp_limit_check($sub, -1)) {
        set_page_message(tr('Incorrect subdomain limit.'), 'error');
    }

    // Domain aliases limit
    if (isset($_POST['nreseller_max_alias_cnt'])) {
        $als = clean_input($_POST['nreseller_max_alias_cnt']);
    }

    if (!resellerHasFeature('domain_aliases')) {
        $als = '-1';
    } elseif (!imscp_limit_check($als, -1)) {
        set_page_message(tr('Incorrect alias limit.'), 'error');
    }

    // Mail accounts limit
    if (isset($_POST['nreseller_max_mail_cnt'])) {
        $mail = clean_input($_POST['nreseller_max_mail_cnt']);
    }

    if (!resellerHasFeature('mail')) {
        $mail = '-1';
    } elseif (!imscp_limit_check($mail, -1)) {
        set_page_message(tr('Incorrect email account limit.'), 'error');
    }

    // Ftp accounts limit
    if (isset($_POST['nreseller_max_ftp_cnt']) || $ftp == -1) {
        $ftp = clean_input($_POST['nreseller_max_ftp_cnt']);
    }

    if (!resellerHasFeature('ftp')) {
        $ftp = '-1';
    } elseif (!imscp_limit_check($ftp, -1)) {
        set_page_message(tr('Incorrect FTP account limit.'), 'error');
    }

    // SQL database limit
    if (isset($_POST['nreseller_max_sql_db_cnt'])) {
        $sqlDb = clean_input($_POST['nreseller_max_sql_db_cnt']);
    }

    if (!resellerHasFeature('sql_db')) {
        $sqlDb = -1;
    } elseif (!imscp_limit_check($sqlDb, -1)) {
        set_page_message(tr('Incorrect SQL database limit.'), 'error');
    } elseif ($sqlDb != -1 && $sqlUser == -1) {
        set_page_message(tr('SQL user limit is disabled.'), 'error');
    }

    // SQL users limit
    if (isset($_POST['nreseller_max_sql_user_cnt'])) {
        $sqlUser = clean_input($_POST['nreseller_max_sql_user_cnt']);
    }

    if (!resellerHasFeature('sql_user')) {
        $sqlUser = -1;
    } elseif (!imscp_limit_check($sqlUser, -1)) {
        set_page_message(tr('Incorrect SQL user limit.'), 'error');
    } elseif ($sqlUser != -1 && $sqlDb == -1) {
        set_page_message(tr("SQL database limit is disabled."), 'error');
    }

    // Monthly traffic limit
    if (isset($_POST['nreseller_max_traffic'])) {
        $traffic = clean_input($_POST['nreseller_max_traffic']);
    }

    if (!imscp_limit_check($traffic, null)) {
        set_page_message(tr('Incorrect monthly traffic limit.'), 'error');
    }

    // Disk space limit
    if (isset($_POST['nreseller_max_disk'])) {
        $diskSpace = clean_input($_POST['nreseller_max_disk']);
    }

    if (!imscp_limit_check($diskSpace, null)) {
        set_page_message(tr('Incorrect disk space limit.'), 'error');
    }

    if (isset($_POST['nreseller_mail_quota'])) {
        $mailQuota = clean_input($_POST['nreseller_mail_quota']);

        if (!imscp_limit_check($mailQuota, null)) {
            set_page_message(tr('Incorrect Email quota'), 'error');
        } elseif ($diskSpace != '0' && $mailQuota > $diskSpace) {
            set_page_message(tr('Email quota cannot be bigger than disk space limit.'), 'error');
        } elseif ($diskSpace != '0' && $mailQuota == '0') {
            set_page_message(tr('Email quota cannot be unlimited. Max value is %d MiB.', $diskSpace), 'error');
        }
    }

    // PHP feature
    if (isset($_POST['php'])) {
        $php = $_POST['php'];
    }

    // PHP Editor feature
    if (isset($_POST['phpiniSystem']) && $phpini->resellerHasPermission('phpiniSystem')) {
        $phpini->setClientPermission('phpiniSystem', clean_input($_POST['phpiniSystem']));

        if (isset($_POST['phpini_perm_allow_url_fopen'])) {
            $phpini->setClientPermission('phpiniAllowUrlFopen', clean_input($_POST['phpini_perm_allow_url_fopen']));
        }

        if (isset($_POST['phpini_perm_display_errors'])) {
            $phpini->setClientPermission('phpiniDisplayErrors', clean_input($_POST['phpini_perm_display_errors']));
        }

        if (isset($_POST['phpini_perm_disable_functions'])) {
            $phpini->setClientPermission('phpiniDisableFunctions', clean_input($_POST['phpini_perm_disable_functions']));
        }

        if (isset($_POST['phpini_perm_mail_function'])) {
            $phpini->setClientPermission('phpiniMailFunction', clean_input($_POST['phpini_perm_mail_function']));
        }

        if (!isset($_POST['post_max_size'])) {
            $phpini->setDomainIni('phpiniPostMaxSize', clean_input($_POST['post_max_size']));
        }

        if (isset($_POST['upload_max_filesize'])) {
            $phpini->setDomainIni('phpiniUploadMaxFileSize', clean_input($_POST['upload_max_filesize']));
        }

        if (isset($_POST['max_execution_time'])) {
            $phpini->setDomainIni('phpiniMaxExecutionTime', clean_input($_POST['max_execution_time']));
        }

        if (isset($_POST['max_input_time'])) {
            $phpini->setDomainIni('phpiniMaxInputTime', clean_input($_POST['max_input_time']));
        }

        if (isset($_POST['memory_limit'])) {
            $phpini->setDomainIni('phpiniMemoryLimit', clean_input($_POST['memory_limit']));
        }
    }

    // CGI feature
    if (isset($_POST['cgi'])) {
        $cgi = $_POST['cgi'];
    } else {
        $cgi = '_no_';
    }

    // Custom DNS records feature
    if (resellerHasFeature('custom_dns_records')) {
        if (isset($_POST['dns'])) {
            $dns = $_POST['dns'];
        } else {
            $dns = '_no_';
        }
    } else {
        $dns = '_no_';
    }

    // External mail server feature
    if (resellerHasFeature('external_mail') && isset($_POST['external_mail'])) {
        $extMailServer = clean_input($_POST['external_mail']);
    } else {
        $extMailServer = '_no_';
    }

    // Backup feature
    if (resellerHasFeature('backup')) {
        $backup = isset($_POST['backup']) && is_array($_POST['backup']) ? array_intersect($_POST['backup'], array('_dmn_', '_sql_', '_mail_')) : array();
    } else {
        $backup = array();
    }

    // APS feature
    if (isset($_POST['software_allowed']) && resellerHasFeature('aps')) {
        $aps = $_POST['software_allowed'];
    } else {
        $aps = '_no_';
    }

    if ($php == '_no_' && $aps == '_yes_') {
        set_page_message(tr('The software installer feature requires PHP.'), 'error');
    }

    // Web folders protection
    if (isset($_POST['web_folder_protection'])) {
        $webFolderProtection = $_POST['web_folder_protection'];
    } else {
        $webFolderProtection = '_yes_';
    }

    if (!Zend_Session::namespaceIsset('pageMessages')) {
        return true;
    }

    return false;
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);
check_login('reseller');

$phpini = iMSCP_PHPini::getInstance();
$phpini->loadResellerPermissions($_SESSION['user_id']);
$phpini->loadClientPermissions();
$phpini->loadDomainIni();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'reseller/user_add2.tpl',
    'page_message' => 'layout',
    'subdomain_feature' => 'page',
    'alias_feature' => 'page',
    'mail_feature' => 'page',
    'custom_dns_records_feature' => 'page',
    'ext_mail_feature' => 'page',
    'ftp_feature' => 'page',
    'sql_feature' => 'page',
    'aps_feature' => 'page',
    'backup_feature' => 'page',
    'php_editor_block' => 'page',
    'php_editor_permissions_block' => 'php_editor_block',
    'php_editor_allow_url_fopen_block' => 'php_editor_permissions_block',
    'php_editor_display_errors_block' => 'php_editor_permissions_block',
    'php_editor_disable_functions_block' => 'php_editor_permissions_block',
    "php_mail_function_block" => 'php_editor_permissions_block',
    'php_editor_default_values_block' => 'php_editor_block'
));

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Reseller / Customers / Add Customer - Next Step'),
    'TR_ADD_USER' => tr('Add user'),
    'TR_HOSTING_PLAN' => tr('Hosting plan'),
    'TR_NAME' => tr('Name'),
    'TR_MAX_DOMAIN' => tr('Domain limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_SUBDOMAIN' => tr('Subdomain limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_DOMAIN_ALIAS' => tr('Domain alias limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_MAIL_COUNT' => tr('Email account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MAIL_QUOTA' => tr('Email quota [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_FTP' => tr('FTP account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_SQL_DB' => tr('SQL database limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_SQL_USERS' => tr('SQL user limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_TRAFFIC' => tr('Monthly traffic limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_DISK_USAGE' => tr('Disk space limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
    'TR_EXTMAIL' => tr('External mail server'),
    'TR_PHP' => tr('PHP'),
    'TR_CGI' => tr('CGI'),
    'TR_BACKUP' => tr('Backup'),
    'TR_BACKUP_DOMAIN' => tr('Domain'),
    'TR_BACKUP_SQL' => tr('SQL'),
    'TR_BACKUP_MAIL' => tr('Mail'),
    'TR_DNS' => tr('Custom DNS records'),
    'TR_YES' => tr('yes'),
    'TR_NO' => tr('no'),
    'TR_NEXT_STEP' => tr('Next step'),
    'TR_FEATURES' => tr('Features'),
    'TR_LIMITS' => tr('Limits'),
    'TR_WEB_FOLDER_PROTECTION' => tr('Web folder protection'),
    'TR_WEB_FOLDER_PROTECTION_HELP' => tr("If set to 'yes', Web folders as provisioned by i-MSCP will be protected against deletion using the immutable flag (only if supported by the file system)."),
    'TR_SOFTWARE_SUPP' => tr('Software installer')
));

generateNavigation($tpl);

global $dmnName, $dmnExpire, $php, $cgi, $sub, $als, $mail, $mailQuota, $ftp, $sqlDb, $sqlUser, $traffic, $diskSpace,
       $backup, $dns, $aps, $extMailServer, $webFolderProtection;

if (!get_pageone_param()) {
    set_page_message(tr('Domain data were been altered. Please try again.'), 'error');
    unsetMessages();
    redirectTo('user_add1.php');
}

if (isset($_POST['uaction']) && ('user_add2_nxt' == $_POST['uaction']) && (!isset($_SESSION['step_one']))) {
    if (check_user_data($phpini)) {
        $_SESSION['step_two_data'] = "$dmnName;0";
        $_SESSION['ch_hpprops'] =
            "$php;$cgi;$sub;$als;$mail;$ftp;$sqlDb;$sqlUser;$traffic;$diskSpace;" . implode('|', $backup) . ";$dns;$aps;" .
            $phpini->getClientPermission('phpiniSystem') . ';' .
            $phpini->getClientPermission('phpiniAllowUrlFopen') . ';' .
            $phpini->getClientPermission('phpiniDisplayErrors') . ';' .
            $phpini->getClientPermission('phpiniDisableFunctions') . ';' .
            $phpini->getClientPermission('phpiniMailFunction') . ';' .
            $phpini->getDomainIni('phpiniPostMaxSize') . ";" .
            $phpini->getDomainIni('phpiniUploadMaxFileSize') . ';' .
            $phpini->getDomainIni('phpiniMaxExecutionTime') . ';' .
            $phpini->getDomainIni('phpiniMaxInputTime') . ';' .
            $phpini->getDomainIni('phpiniMemoryLimit') . ';' .
            $extMailServer . ';' . $webFolderProtection . ';' . $mailQuota * 1048576;

        if (reseller_limits_check($_SESSION['user_id'], $_SESSION['ch_hpprops'])) {
            redirectTo('user_add3.php');
        }
    }
} else {
    unset($_SESSION['step_one']);
    global $hpId;
    reseller_getHostingPlanData($hpId, $_SESSION['user_id'], $phpini);
}

get_init_au2_page($tpl, $phpini);

if (!resellerHasFeature('subdomains')) {
    $tpl->assign('SUBDOMAIN_FEATURE', '');
}

if (!resellerHasFeature('domain_aliases')) {
    $tpl->assign('ALIAS_FEATURE', '');
}

if (!resellerHasFeature('custom_dns_records')) {
    $tpl->assign('CUSTOM_DNS_RECORDS_FEATURE', '');
}

if (!resellerHasFeature('mail')) {
    $tpl->assign('MAIL_FEATURE', '');
    $tpl->assign('EXT_MAIL_FEATURE', '');
}

if (!resellerHasFeature('ftp')) {
    $tpl->assign('FTP_FEATURE', '');
}

if (!resellerHasFeature('sql')) {
    $tpl->assign('SQL_FEATURE', '');
}

if (!resellerHasFeature('aps')) {
    $tpl->assign('APS_FEATURE', '');
}

if (!resellerHasFeature('backup')) {
    $tpl->assign('BACKUP_FEATURE', '');
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
