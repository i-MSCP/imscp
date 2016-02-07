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
 * Generate PHP editor block
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function _reseller_generatePhpBlock($tpl)
{
    $cfg = iMSCP_Registry::get('config');

    $phpini = iMSCP_PHPini::getInstance();

    if ($phpini->clientHasPermission('phpiniSystem')) {
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
                    'TR_ONLY_EXEC' => tr('Only exec'),
                    'DISABLE_FUNCTIONS_EXEC' => $phpini->getClientPermission('phpiniDisableFunctions') == 'exec' ? ' checked' : '',
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
                'TR_ONLY_EXEC' => tr('Only exec')
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
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @param int $id Hosting plan unique identifier
 * @param int $resellerId Reseller unique identifier
 * @return void
 */
function reseller_generatePage($tpl, $id, $resellerId)
{
    $stmt = exec_query('SELECT * FROM hosting_plans WHERE id = ? AND reseller_id = ?', array($id, $resellerId));
    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $data = $stmt->fetchRow();
    $description = $data['description'];
    $status = $data['status'];

    list(
        $php, $cgi, $sub, $als, $mail, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $backup, $dns, $aps, $phpEditor,
        $phpAllowUrlFopen, $phpDisplayErrors, $phpDisableFunctions, $phpiniMailFunction, $phpPostMaxSizeValue,
        $phpUploadMaxFilesizeValue, $phpMaxExecutionTimeValue, $phpMaxInputTimeValue, $phpMemoryLimitValue, $extMail,
        $webFolderProtection, $mailQuota
        ) = explode(';', $data['props']);

    $backup = explode('|', $backup);
    $mailQuota = $mailQuota / 1048576;

    $phpini = iMSCP_PHPini::getInstance();

    $phpini->setClientPermission('phpiniSystem', $phpEditor);
    $phpini->setClientPermission('phpiniAllowUrlFopen', $phpAllowUrlFopen);
    $phpini->setClientPermission('phpiniDisplayErrors', $phpDisplayErrors);
    $phpini->setClientPermission('phpiniDisableFunctions', $phpDisableFunctions);
    $phpini->setClientPermission('phpiniMailFunction', $phpiniMailFunction);

    $phpini->setDomainIni('phpiniPostMaxSize', $phpPostMaxSizeValue);
    $phpini->setDomainIni('phpiniUploadMaxFileSize', $phpUploadMaxFilesizeValue);
    $phpini->setDomainIni('phpiniMaxExecutionTime', $phpMaxExecutionTimeValue);
    $phpini->setDomainIni('phpiniMaxInputTime', $phpMaxInputTimeValue);
    $phpini->setDomainIni('phpiniMemoryLimit', $phpMemoryLimitValue);

    $tpl->assign(array(
        'ID' => tohtml($id),
        'NAME' => tohtml($data['name']),
        'DESCRIPTION' => tohtml($description),
        'MAX_SUB' => tohtml($sub),
        'MAX_ALS' => tohtml($als),
        'MAX_MAIL' => tohtml($mail),
        'MAIL_QUOTA' => tohtml($mailQuota),
        'MAX_FTP' => tohtml($ftp),
        'MAX_SQLD' => tohtml($sqld),
        'MAX_SQLU' => tohtml($sqlu),
        'MONTHLY_TRAFFIC' => tohtml($traffic),
        'MAX_DISKSPACE' => tohtml($diskSpace),
        'PHP_YES' => $php == '_yes_' ? ' checked' : '',
        'PHP_NO' => $php == '_no_' ? ' checked' : '',
        'CGI_YES' => $cgi == '_yes_' ? ' checked' : '',
        'CGI_NO' => $cgi == '_no_' ? ' checked' : '',
        'DNS_YES' => $dns == '_yes_' ? ' checked' : '',
        'DNS_NO' => $dns == '_no_' ? ' checked' : '',
        'SOFTWARE_YES' => $aps == '_yes_' ? ' checked' : '',
        'SOFTWARE_NO' => $aps == '_no_' ? ' checked' : '',
        'EXTMAIL_YES' => $extMail == '_yes_' ? ' checked' : '',
        'EXTMAIL_NO' => $extMail == '_no_' ? ' checked' : '',
        'PROTECT_WEB_FOLDERS_YES' => $webFolderProtection == '_yes_' ? ' checked' : '',
        'PROTECT_WEB_FOLDERS_NO' => $webFolderProtection == '_no_' ? ' checked' : '',
        'STATUS_YES' => $status ? ' checked' : '',
        'STATUS_NO' => !$status ? ' checked' : ''
    ));

    if (resellerHasFeature('backup')) {
        $tpl->assign(array(
            'BACKUPD' => in_array('_dmn_', $backup) ? ' checked' : '',
            'BACKUPS' => in_array('_sql_', $backup) ? ' checked' : '',
            'BACKUPM' => in_array('_mail_', $backup) ? ' checked' : ''
        ));
    } else {
        $tpl->assign('BACKUP_FEATURE', '');
    }

    _reseller_generatePhpBlock($tpl);
}

/**
 * Generate error page
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $id Hosting plan identifier
 * @return void
 */
function reseller_generateErrorPage($tpl, $id)
{
    global $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace,
           $php, $cgi, $backup, $dns, $aps, $extMail, $webFolderProtection, $status;

    $tpl->assign(array(
        'ID' => tohtml($id),
        'NAME' => tohtml($name),
        'DESCRIPTION' => tohtml($description),
        'MAX_SUB' => tohtml($sub),
        'MAX_ALS' => tohtml($als),
        'MAX_MAIL' => tohtml($mail),
        'MAIL_QUOTA' => tohtml($mailQuota),
        'MAX_FTP' => tohtml($ftp),
        'MAX_SQLD' => tohtml($sqld),
        'MAX_SQLU' => tohtml($sqlu),
        'MONTHLY_TRAFFIC' => tohtml($traffic),
        'MAX_DISKSPACE' => tohtml($diskSpace),
        'PHP_YES' => $php == '_yes_' ? ' checked' : '',
        'PHP_NO' => $php == '_no_' ? ' checked' : '',
        'CGI_YES' => $cgi == '_yes_' ? ' checked' : '',
        'CGI_NO' => $cgi == '_no_' ? ' checked' : '',
        'DNS_YES' => $dns == '_yes_' ? ' checked' : '',
        'DNS_NO' => $dns == '_no_' ? ' checked' : '',
        'SOFTWARE_YES' => $aps == '_yes_' ? ' checked' : '',
        'SOFTWARE_NO' => $aps == '_no_' ? ' checked' : '',
        'EXTMAIL_YES' => $extMail == '_yes_' ? ' checked' : '',
        'EXTMAIL_NO' => $extMail == '_no_' ? ' checked' : '',
        'PROTECT_WEB_FOLDERS_YES' => $webFolderProtection == '_yes_' ? ' checked' : '',
        'PROTECT_WEB_FOLDERS_NO' => $webFolderProtection == '_no_' ? ' checked' : '',
        'STATUS_YES' => $status ? ' checked' : '',
        'STATUS_NO' => !$status ? ' checked' : ''
    ));

    if (resellerHasFeature('backup')) {
        $tpl->assign(array(
            'BACKUPD' => in_array('_dmn_', $backup) ? ' checked' : '',
            'BACKUPS' => in_array('_sql_', $backup) ? ' checked' : '',
            'BACKUPM' => in_array('_mail_', $backup) ? ' checked' : ''
        ));
    } else {
        $tpl->assign('BACKUP_FEATURE', '');
    }

    _reseller_generatePhpBlock($tpl);
}

/**
 * Check input data
 *
 * @return bool TRUE if data are valid, FALSE otherwise
 */
function reseller_checkData()
{
    global $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $php,
           $cgi, $dns, $backup, $aps, $extMail, $webFolderProtection, $status;

    $name = isset($_POST['hp_name']) ? clean_input($_POST['hp_name']) : '';
    $description = isset($_POST['hp_description']) ? clean_input($_POST['hp_description']) : '';
    $sub = isset($_POST['hp_sub']) ? clean_input($_POST['hp_sub']) : '-1';
    $als = isset($_POST['hp_als']) ? clean_input($_POST['hp_als']) : '-1';
    $mail = isset($_POST['hp_mail']) ? clean_input($_POST['hp_mail']) : '-1';
    $mailQuota = isset($_POST['hp_mail_quota']) ? clean_input($_POST['hp_mail_quota']) : '';
    $ftp = isset($_POST['hp_ftp']) ? clean_input($_POST['hp_ftp']) : '-1';
    $sqld = isset($_POST['hp_sql_db']) ? clean_input($_POST['hp_sql_db']) : '-1';
    $sqlu = isset($_POST['hp_sql_user']) ? clean_input($_POST['hp_sql_user']) : '-1';
    $traffic = isset($_POST['hp_traff']) ? clean_input($_POST['hp_traff']) : '';
    $diskSpace = isset($_POST['hp_disk']) ? clean_input($_POST['hp_disk']) : '';
    $php = isset($_POST['hp_php']) ? clean_input($_POST['hp_php']) : '_no_';
    $cgi = isset($_POST['hp_cgi']) ? clean_input($_POST['hp_cgi']) : '_no_';
    $dns = isset($_POST['hp_dns']) ? clean_input($_POST['hp_dns']) : '_no_';
    $backup = isset($_POST['hp_backup']) && is_array($_POST['hp_backup']) ? $_POST['hp_backup'] : array();
    $aps = isset($_POST['hp_softwares_installer']) ? clean_input($_POST['hp_softwares_installer']) : '_no_';
    $extMail = isset($_POST['hp_external_mail']) ? clean_input($_POST['hp_external_mail']) : '_no_';
    $webFolderProtection = isset($_POST['hp_protected_webfolders']) ? clean_input($_POST['hp_protected_webfolders']) : '_no_';
    $status = isset($_POST['hp_status']) ? clean_input($_POST['hp_status']) : '0';
    $php = $php == '_yes_' ? '_yes_' : '_no_';
    $cgi = $cgi == '_yes_' ? '_yes_' : '_no_';
    $dns = $dns == '_yes_' ? '_yes_' : '_no_';
    $backup = resellerHasFeature('backup') ? array_intersect($backup, array('_dmn_', '_sql_', '_mail_')) : array();
    $aps = resellerHasFeature('aps') && $aps == '_yes_' ? '_yes_' : '_no_';
    $extMail = $extMail == '_yes_' ? '_yes_' : '_no_';
    $webFolderProtection = $webFolderProtection == '_yes_' ? '_yes_' : '_no_';

    if ($name == '') {
        set_page_message(tr('Name cannot be empty.'), 'error');
    }

    if ($description == '') {
        set_page_message(tr('Description cannot be empty.'), 'error');
    }

    if (!resellerHasFeature('subdomains')) {
        $sub = '-1';
    } elseif (!imscp_limit_check($sub, -1)) {
        set_page_message(tr('Incorrect subdomain limit.'), 'error');
    }

    if (!resellerHasFeature('domain_aliases')) {
        $als = '-1';
    } elseif (!imscp_limit_check($als, -1)) {
        set_page_message(tr('Incorrect domain alias limit.'), 'error');
    }

    if (!resellerHasFeature('mail')) {
        $mail = '-1';
    } elseif (!imscp_limit_check($mail, -1)) {
        set_page_message(tr('Incorrect email account limit.'), 'error');
    }

    if (!resellerHasFeature('ftp')) {
        $ftp = '-1';
    } elseif (!imscp_limit_check($ftp, -1)) {
        set_page_message(tr('Incorrect FTP account limit.'), 'error');
    }

    if (!resellerHasFeature('sql_db')) {
        $sqld = '-1';
    } elseif (!imscp_limit_check($sqld, -1)) {
        set_page_message(tr('Incorrect SQL user limit.'), 'error');
    } elseif ($sqlu != -1 && $sqld == -1) {
        set_page_message(tr('SQL user limit is <i>disabled</i>.'), 'error');
    }

    if (!resellerHasFeature('sql_user')) {
        $sqlu = '-1';
    } elseif (!imscp_limit_check($sqlu, -1)) {
        set_page_message(tr('Incorrect SQL database limit.'), 'error');
    } elseif ($sqlu == -1 && $sqld != -1) {
        set_page_message(tr('SQL database limit is not <i>disabled</i>.'), 'error');
    }

    if (!imscp_limit_check($traffic, null)) {
        set_page_message(tr('Incorrect monthly traffic limit.'), 'error');
    }

    if (!imscp_limit_check($diskSpace, null)) {
        set_page_message(tr('Incorrect disk space limit.'), 'error');
    }

    if (isset($_POST['hp_mail_quota'])) {
        if (!imscp_limit_check($mailQuota, null)) {
            set_page_message(tr('Incorrect Email quota.'), 'error');
        } elseif ($diskSpace != 0 && $mailQuota > $diskSpace) {
            set_page_message(tr('Email quota cannot be bigger than disk space limit.'), 'error');
        } elseif ($diskSpace != 0 && $mailQuota == 0) {
            set_page_message(tr('Email quota cannot be unlimited. Max value is %d MiB.', $diskSpace), 'error');
        }
    }

    $phpini = iMSCP_PHPini::getInstance();

    if (isset($_POST['phpiniSystem']) && $php != '_no_' && $phpini->resellerHasPermission('phpiniSystem')) {
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

        if (isset($_POST['post_max_size'])) {
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
    } else {
        $phpini->loadClientPermissions(); // Reset values to default PHP permissions
        $phpini->loadDomainIni(); // Reset values to default PHP configuration options
    }

    if ($php == '_no_' && $aps == '_yes_') {
        set_page_message(tr('The software installer require the PHP support.'), 'error');
    }

    if (!Zend_Session::namespaceIsset('pageMessages')) {
        return true;
    }

    return false;
}

/**
 * Update hosting plan
 *
 * @param int $id Hosting plan identifier
 * @return bool TRUE on success, FALSE otherwise
 */
function reseller_updateHostingPlan($id)
{
    global $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $php,
           $cgi, $dns, $backup, $aps, $extMail, $webFolderProtection, $status;

    $phpini = iMSCP_PHPini::getInstance();

    $hpProps = "$php;$cgi;$sub;$als;$mail;$ftp;$sqld;$sqlu;$traffic;$diskSpace;" . implode('|', $backup) . ";$dns;$aps";
    $hpProps .= ';' . $phpini->getClientPermission('phpiniSystem');
    $hpProps .= ';' . $phpini->getClientPermission('phpiniAllowUrlFopen');
    $hpProps .= ';' . $phpini->getClientPermission('phpiniDisplayErrors');
    $hpProps .= ';' . $phpini->getClientPermission('phpiniDisableFunctions');
    $hpProps .= ';' . $phpini->getClientPermission('phpiniMailFunction');
    $hpProps .= ';' . $phpini->getDomainIni('phpiniPostMaxSize');
    $hpProps .= ';' . $phpini->getDomainIni('phpiniUploadMaxFileSize');
    $hpProps .= ';' . $phpini->getDomainIni('phpiniMaxExecutionTime');
    $hpProps .= ';' . $phpini->getDomainIni('phpiniMaxInputTime');
    $hpProps .= ';' . $phpini->getDomainIni('phpiniMemoryLimit');
    $hpProps .= ';' . $extMail . ';' . $webFolderProtection . ';' . $mailQuota * 1048576;

    if (reseller_limits_check($_SESSION['user_id'], $hpProps)) {
        exec_query('UPDATE hosting_plans SET name = ?, description = ?, props = ?, status = ? WHERE id = ?', array(
            $name, $description, $hpProps, $status, $id
        ));
        return true;
    }

    set_page_message(tr('Hosting plan limits exceed your limits.'), 'error');
    return false;
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);
check_login('reseller');

if (!isset($_GET['id'])) {
    showBadRequestErrorPage();
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'reseller/hosting_plan_edit.tpl',
    'page_message' => 'layout',
    'nb_subdomains' => 'page',
    'nb_domain_aliases' => 'page',
    'nb_mail' => 'page',
    'nb_ftp' => 'page',
    'nb_sqld' => 'page',
    'nb_sqlu' => 'page',
    'php_feature' => 'page',
    'php_editor_feature' => 'page',
    'php_editor_permissions_block' => 'php_editor_feature',
    'php_editor_allow_url_fopen_block' => 'php_editor_permissions_block',
    'php_editor_display_errors_block' => 'php_editor_permissions_block',
    'php_editor_disable_functions_block' => 'php_editor_permissions_block',
    'php_editor_mail_function_block' => 'php_editor_permissions_block',
    'php_editor_default_values_block' => 'php_editor_feature',
    'cgi_feature' => 'page',
    'custom_dns_feature' => 'page',
    'aps_feature' => 'page',
    'backup_feature' => 'page'
));


$id = intval($_GET['id']);
$phpini = iMSCP_PHPini::getInstance();
$phpini->loadResellerPermissions($_SESSION['user_id']);

if (!empty($_POST)) {
    if (reseller_checkData() && reseller_updateHostingPlan($id)) {
        set_page_message(tr('Hosting plan successfully updated.'), 'success');
        redirectTo('hosting_plan.php');
    }

    reseller_generateErrorPage($tpl, $id);
} else {
    reseller_generatePage($tpl, $id, $_SESSION['user_id']);
}

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Reseller /Hosting Plans / {TR_DYNAMIC_TITLE}'),
    'TR_HOSTING_PLAN' => tr('Hosting plan'),
    'TR_NAME' => tr('Name'),
    'TR_DESCRIPTON' => tr('Description'),
    'TR_HOSTING_PLAN_LIMITS' => tr('Limits'),
    'TR_MAX_SUB' => tr('Subdomain limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_ALS' => tr('Domain alias limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_MAIL' => tr('Email account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MAIL_QUOTA' => tr('Email quota [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_FTP' => tr('FTP account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_SQLD' => tr('SQL database limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_SQLU' => tr('SQL user limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
    'TR_MONTHLY_TRAFFIC' => tr('Monthly traffic limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
    'TR_MAX_DISKSPACE' => tr('Disk space limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
    'TR_HOSTING_PLAN_FEATURES' => tr('Features'),
    'TR_PHP' => tr('PHP'),
    'TR_CGI' => tr('CGI'),
    'TR_DNS' => tr('Custom DNS records'),
    'TR_SOFTWARE_SUPP' => tr('Software installer'),
    'TR_EXTMAIL' => tr('External mail server'),
    'TR_WEB_FOLDER_PROTECTION' => tr('Web folder protection'),
    'TR_WEB_FOLDER_PROTECTION_HELP' => tr("If set to 'yes', Web folders as provisioned by i-MSCP will be protected against deletion using the immutable flag (only if supported by the file system)."),
    'TR_BACKUP' => tr('Backup'),
    'TR_BACKUP_DOMAIN' => tr('Domain'),
    'TR_BACKUP_SQL' => tr('SQL'),
    'TR_BACKUP_MAIL' => tr('Mail'),
    'TR_AVAILABILITY' => tr('Hosting plan availability'),
    'TR_STATUS' => tr('Available'),
    'TR_YES' => tr('yes'),
    'TR_NO' => tr('no'),
    'TR_UPDATE' => tr('Update'),
));

if (!resellerHasFeature('subdomains')) {
    $tpl->assign('NB_SUBDOMAIN', '');
}

if (!resellerHasFeature('domain_aliases')) {
    $tpl->assign('NB_DOMAIN_ALIASES', '');
}

if (!resellerHasFeature('mail')) {
    $tpl->assign('NB_MAIL', '');
}

if (!resellerHasFeature('ftp')) {
    $tpl->assign('NB_FTP', '');
}

if (!resellerHasFeature('sql_db')) {
    $tpl->assign('NB_SQLD', '');
}

if (!resellerHasFeature('sql_user')) {
    $tpl->assign('NB_SQLU', '');
}

if (!resellerHasFeature('php')) {
    $tpl->assign('PHP_FEATURE', '');
}

if (!resellerHasFeature('php_editor')) {
    $tpl->assign('PHP_EDITOR_FEATURE', '');
}

if (!resellerHasFeature('cgi')) {
    $tpl->assign('CGI_FEATURE', '');
}

if (!resellerHasFeature('custom_dns_records')) {
    $tpl->assign('CUSTOM_DNS_RECORDS_FEATURE', '');
}

if (!resellerHasFeature('aps')) {
    $tpl->assign('APS_FEATURE', '');
}

if (!resellerHasFeature('external_mail')) {
    $tpl->assign('EXT_MAIL_FEATURE', '');
}

if (!resellerHasFeature('backup')) {
    $tpl->assign('BACKUP_FEATURE', '');
}

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
