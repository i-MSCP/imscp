<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Load hosting plan
 *
 * @return bool TRUE on success, FALSE on failure
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function loadHostingPlan()
{
    global $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $php, $cgi,
           $id, $backup, $dns, $aps, $extMail, $webFolderProtection, $status;

    $stmt = exec_query('SELECT * FROM hosting_plans WHERE id = ? AND reseller_id = ?', [$id, $_SESSION['user_id']]);
    if (!$stmt->rowCount()) {
        return false;
    }

    $data = $stmt->fetchRow();
    $name = $data['name'];
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
    $phpini->loadResellerPermissions($_SESSION['user_id']); // Load reseller permissions
    $phpini->loadClientPermissions(); // Load client default PHP permissions
    $phpini->loadDomainIni(); // Load domain default PHP configuration options

    $phpini->setClientPermission('phpiniSystem', $phpEditor);
    $phpini->setClientPermission('phpiniAllowUrlFopen', $phpAllowUrlFopen);
    $phpini->setClientPermission('phpiniDisplayErrors', $phpDisplayErrors);
    $phpini->setClientPermission('phpiniDisableFunctions', $phpDisableFunctions);
    $phpini->setClientPermission('phpiniMailFunction', $phpiniMailFunction);
    $phpini->setDomainIni('phpiniMemoryLimit', $phpMemoryLimitValue); // Must be set before phpiniPostMaxSize
    $phpini->setDomainIni('phpiniPostMaxSize', $phpPostMaxSizeValue); // Must be set before phpiniUploadMaxFileSize
    $phpini->setDomainIni('phpiniUploadMaxFileSize', $phpUploadMaxFilesizeValue);
    $phpini->setDomainIni('phpiniMaxExecutionTime', $phpMaxExecutionTimeValue);
    $phpini->setDomainIni('phpiniMaxInputTime', $phpMaxInputTimeValue);
    $phpini->setDomainIni('phpiniMemoryLimit', $phpMemoryLimitValue);
    return true;
}

/**
 * Generate PHP editor block
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function generatePhpBlock($tpl)
{
    $phpini = iMSCP_PHPini::getInstance();

    if (!$phpini->resellerHasPermission('phpiniSystem')) {
        $tpl->assign('PHP_EDITOR_BLOCK', '');
    }

    $tpl->assign([
        'PHP_EDITOR_YES'         => $phpini->clientHasPermission('phpiniSystem') ? ' checked' : '',
        'PHP_EDITOR_NO'          => $phpini->clientHasPermission('phpiniSystem') ? '' : ' checked',
        'TR_PHP_EDITOR'          => tr('PHP Editor'),
        'TR_PHP_EDITOR_SETTINGS' => tr('PHP Settings'),
        'TR_SETTINGS'            => tr('PHP Settings'),
        'TR_DIRECTIVES_VALUES'   => tr('PHP Configuration options'),
        'TR_FIELDS_OK'           => tr('All fields are valid.'),
        'TR_MIB'                 => tr('MiB'),
        'TR_SEC'                 => tr('Sec.')
    ]);

    iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
        /** @var iMSCP_Events_Event $e */
        $translations = $e->getParam('translations');
        $translations['core']['close'] = tr('Close');
        $translations['core']['fields_ok'] = tr('All fields are valid.');
        $translations['core']['out_of_range_value_error'] = tr('Value for the PHP %%s directive must be in range %%d to %%d.');
        $translations['core']['lower_value_expected_error'] = tr('%%s cannot be greater than %%s.');
    });

    $permissionsBlock = false;

    if (!$phpini->resellerHasPermission('phpiniAllowUrlFopen')) {
        $tpl->assign('PHP_EDITOR_ALLOW_URL_FOPEN_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_CAN_EDIT_ALLOW_URL_FOPEN' => tr('Can edit the PHP %s configuration option', '<b>allow_url_fopen</b>'),
            'ALLOW_URL_FOPEN_YES'         => $phpini->clientHasPermission('phpiniAllowUrlFopen') ? ' checked' : '',
            'ALLOW_URL_FOPEN_NO'          => $phpini->clientHasPermission('phpiniAllowUrlFopen') ? '' : ' checked'
        ]);
        $permissionsBlock = true;
    }

    if (!$phpini->resellerHasPermission('phpiniDisplayErrors')) {
        $tpl->assign('PHP_EDITOR_DISPLAY_ERRORS_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_CAN_EDIT_DISPLAY_ERRORS' => tr('Can edit the PHP %s configuration option', '<b>display_errors</b>'),
            'DISPLAY_ERRORS_YES'         => $phpini->clientHasPermission('phpiniDisplayErrors') ? ' checked' : '',
            'DISPLAY_ERRORS_NO'          => $phpini->clientHasPermission('phpiniDisplayErrors') ? '' : ' checked'
        ]);
        $permissionsBlock = true;
    }

    $cfg = iMSCP_Registry::get('config');

    if ($cfg['HTTPD_SERVER'] == 'apache_itk') {
        $tpl->assign([
            'PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK' => '',
            'PHP_EDITOR_MAIL_FUNCTION_BLOCK'     => ''
        ]);
    } else {
        if ($phpini->resellerHasPermission('phpiniDisableFunctions')) {
            $tpl->assign([
                'TR_CAN_EDIT_DISABLE_FUNCTIONS' => tr('Can edit the PHP %s configuration option', '<b>disable_functions</b>'),
                'DISABLE_FUNCTIONS_YES'         => $phpini->getClientPermission('phpiniDisableFunctions') == 'yes' ? ' checked' : '',
                'DISABLE_FUNCTIONS_NO'          => $phpini->getClientPermission('phpiniDisableFunctions') == 'no' ? ' checked' : '',
                'TR_ONLY_EXEC'                  => tr('Only exec'),
                'DISABLE_FUNCTIONS_EXEC'        => $phpini->getClientPermission('phpiniDisableFunctions') == 'exec' ? ' checked' : '',
            ]);
        } else {
            $tpl->assign('PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK', '');
        }

        if ($phpini->resellerHasPermission('phpiniMailFunction')) {
            $tpl->assign([
                'TR_CAN_USE_MAIL_FUNCTION' => tr('Can use the PHP %s function', '<b>mail</b>'),
                'MAIL_FUNCTION_YES'        => $phpini->clientHasPermission('phpiniMailFunction') ? ' checked' : '',
                'MAIL_FUNCTION_NO'         => $phpini->clientHasPermission('phpiniMailFunction') ? '' : ' checked'
            ]);
        } else {
            $tpl->assign('PHP_EDITOR_MAIL_FUNCTION_BLOCK', '');
        }

        $permissionsBlock = true;
    }

    if (!$permissionsBlock) {
        $tpl->assign('PHP_EDITOR_PERMISSIONS_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_PERMISSIONS' => tr('PHP Permissions'),
            'TR_ONLY_EXEC'   => tr('Only exec')
        ]);
    }

    $tpl->assign([
        'TR_POST_MAX_SIZE'          => tr('PHP %s configuration option', '<b>post_max_size</b>'),
        'POST_MAX_SIZE'             => tohtml($phpini->getDomainIni('phpiniPostMaxSize'), 'htmlAttr'),
        'TR_UPLOAD_MAX_FILEZISE'    => tr('PHP %s configuration option', '<b>upload_max_filesize</b>'),
        'UPLOAD_MAX_FILESIZE'       => tohtml($phpini->getDomainIni('phpiniUploadMaxFileSize'), 'htmlAttr'),
        'TR_MAX_EXECUTION_TIME'     => tr('PHP %s configuration option', '<b>max_execution_time</b>'),
        'MAX_EXECUTION_TIME'        => tohtml($phpini->getDomainIni('phpiniMaxExecutionTime'), 'htmlAttr'),
        'TR_MAX_INPUT_TIME'         => tr('PHP %s configuration option', '<b>max_input_time</b>'),
        'MAX_INPUT_TIME'            => tohtml($phpini->getDomainIni('phpiniMaxInputTime'), 'htmlAttr'),
        'TR_MEMORY_LIMIT'           => tr('PHP %s configuration option', '<b>memory_limit</b>'),
        'MEMORY_LIMIT'              => tohtml($phpini->getDomainIni('phpiniMemoryLimit'), 'htmlAttr'),
        'POST_MAX_SIZE_LIMIT'       => tohtml($phpini->getResellerPermission('phpiniPostMaxSize'), 'htmlAttr'),
        'UPLOAD_MAX_FILESIZE_LIMIT' => tohtml($phpini->getResellerPermission('phpiniUploadMaxFileSize'), 'htmlAttr'),
        'MAX_EXECUTION_TIME_LIMIT'  => tohtml($phpini->getResellerPermission('phpiniMaxExecutionTime'), 'htmlAttr'),
        'MAX_INPUT_TIME_LIMIT'      => tohtml($phpini->getResellerPermission('phpiniMaxInputTime'), 'htmlAttr'),
        'MEMORY_LIMIT_LIMIT'        => tohtml($phpini->getResellerPermission('phpiniMemoryLimit'), 'htmlAttr')
    ]);
}

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function generatePage($tpl)
{
    global $id, $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $php, $cgi,
           $backup, $dns, $aps, $extMail, $webFolderProtection, $status;

    $tpl->assign([
        'ID'                      => tohtml($id, 'htmlAttr'),
        'NAME'                    => tohtml($name, 'htmlAttr'),
        'DESCRIPTION'             => tohtml($description),
        'MAX_SUB'                 => tohtml($sub, 'htmlAttr'),
        'MAX_ALS'                 => tohtml($als, 'htmlAttr'),
        'MAX_MAIL'                => tohtml($mail, 'htmlAttr'),
        'MAIL_QUOTA'              => tohtml($mailQuota, 'htmlAttr'),
        'MAX_FTP'                 => tohtml($ftp, 'htmlAttr'),
        'MAX_SQLD'                => tohtml($sqld, 'htmlAttr'),
        'MAX_SQLU'                => tohtml($sqlu, 'htmlAttr'),
        'MONTHLY_TRAFFIC'         => tohtml($traffic, 'htmlAttr'),
        'MAX_DISKSPACE'           => tohtml($diskSpace, 'htmlAttr'),
        'PHP_YES'                 => $php == '_yes_' ? ' checked' : '',
        'PHP_NO'                  => $php == '_yes_' ? '' : ' checked',
        'CGI_YES'                 => $cgi == '_yes_' ? ' checked' : '',
        'CGI_NO'                  => $cgi == '_yes_' ? '' : ' checked',
        'DNS_YES'                 => $dns == '_yes_' ? ' checked' : '',
        'DNS_NO'                  => $dns == '_yes_' ? '' : ' checked',
        'TR_SOFTWARE_YES'         => $aps == '_yes_' ? ' checked' : '',
        'TR_SOFTWARE_NO'          => $aps == '_yes_' ? '' : ' checked',
        'SOFTWARE_YES'            => $aps == '_yes_' ? ' checked' : '',
        'SOFTWARE_NO'             => $aps == '_yes_' ? '' : ' checked',
        'EXTMAIL_YES'             => $extMail == '_yes_' ? ' checked' : '',
        'EXTMAIL_NO'              => $extMail == '_yes_' ? '' : ' checked',
        'BACKUPD'                 => in_array('_dmn_', $backup) ? ' checked' : '',
        'BACKUPS'                 => in_array('_sql_', $backup) ? ' checked' : '',
        'BACKUPM'                 => in_array('_mail_', $backup) ? ' checked' : '',
        'PROTECT_WEB_FOLDERS_YES' => $webFolderProtection == '_yes_' ? ' checked' : '',
        'PROTECT_WEB_FOLDERS_NO'  => $webFolderProtection == '_yes_' ? '' : ' checked',
        'STATUS_YES'              => $status ? ' checked' : '',
        'STATUS_NO'               => !$status ? ' checked' : ''
    ]);

    iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
        /** @var iMSCP_Events_Event $e */
        $translations = $e->getParam('translations');
        $translations['core']['error_field_stack'] = iMSCP_Registry::isRegistered('errFieldsStack')
            ? iMSCP_Registry::get('errFieldsStack') : [];
    });

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

    generatePhpBlock($tpl);
}

/**
 * Check input data
 *
 * @return bool TRUE if data are valid, FALSE otherwise
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function checkInputData()
{
    global $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $php, $cgi,
           $dns, $backup, $aps, $extMail, $webFolderProtection, $status;

    $name = isset($_POST['name']) ? clean_input($_POST['name']) : $name;
    $description = isset($_POST['description']) ? clean_input($_POST['description']) : $description;
    $sub = isset($_POST['sub']) ? clean_input($_POST['sub']) : $sub;
    $als = isset($_POST['als']) ? clean_input($_POST['als']) : $als;
    $mail = isset($_POST['mail']) ? clean_input($_POST['mail']) : $mail;
    $mailQuota = isset($_POST['mail_quota']) ? clean_input($_POST['mail_quota']) : $mailQuota;
    $ftp = isset($_POST['ftp']) ? clean_input($_POST['ftp']) : $ftp;
    $sqld = isset($_POST['sql_db']) ? clean_input($_POST['sql_db']) : $sqld;
    $sqlu = isset($_POST['sql_user']) ? clean_input($_POST['sql_user']) : $sqlu;
    $traffic = isset($_POST['traff']) ? clean_input($_POST['traff']) : $traffic;
    $diskSpace = isset($_POST['disk']) ? clean_input($_POST['disk']) : $diskSpace;
    $php = isset($_POST['php']) ? clean_input($_POST['php']) : $php;
    $cgi = isset($_POST['cgi']) ? clean_input($_POST['cgi']) : $cgi;
    $dns = isset($_POST['dns']) ? clean_input($_POST['dns']) : $dns;
    $backup = isset($_POST['backup']) && is_array($_POST['backup']) ? $_POST['backup'] : $backup;
    $aps = isset($_POST['softwares_installer']) ? clean_input($_POST['softwares_installer']) : $aps;
    $extMail = isset($_POST['external_mail']) ? clean_input($_POST['external_mail']) : $extMail;
    $webFolderProtection = isset($_POST['protected_webfolders']) ? clean_input($_POST['protected_webfolders']) : $webFolderProtection;
    $status = isset($_POST['status']) ? clean_input($_POST['status']) : $status;

    $php = $php === '_yes_' ? '_yes_' : '_no_';
    $cgi = $cgi === '_yes_' ? '_yes_' : '_no_';
    $dns = resellerHasFeature('custom_dns_records') && $dns === '_yes_' ? '_yes_' : '_no_';
    $backup = resellerHasFeature('backup') ? array_intersect($backup, ['_dmn_', '_sql_', '_mail_']) : [];
    $aps = resellerHasFeature('aps') && $aps === '_yes_' ? '_yes_' : '_no_';
    $extMail = $extMail === '_yes_' ? '_yes_' : '_no_';
    $webFolderProtection = $webFolderProtection === '_yes_' ? '_yes_' : '_no_';

    $errFieldsStack = [];

    if ($aps == '_yes_') { // Ensure that PHP is enabled when software installer is enabled
        $php = '_yes_';
    }

    if ($name === '') {
        set_page_message(tr('Name cannot be empty.'), 'error');
        $errFieldsStack[] = 'name';
    }

    if ($description === '') {
        set_page_message(tr('Description cannot be empty.'), 'error');
        $errFieldsStack[] = 'description';
    }

    if (!resellerHasFeature('subdomains')) {
        $sub = '-1';
    } elseif (!imscp_limit_check($sub, -1)) {
        set_page_message(tr('Incorrect subdomain limit.'), 'error');
        $errFieldsStack[] = 'sub';
    }

    if (!resellerHasFeature('domain_aliases')) {
        $als = '-1';
    } elseif (!imscp_limit_check($als, -1)) {
        set_page_message(tr('Incorrect domain aliases limit.'), 'error');
        $errFieldsStack[] = 'als';
    }

    if (!resellerHasFeature('mail')) {
        $mail = '-1';
    } elseif (!imscp_limit_check($mail, -1)) {
        set_page_message(tr('Incorrect mail account limit.'), 'error');
        $errFieldsStack[] = 'mail';
    }

    if (!resellerHasFeature('ftp')) {
        $ftp = '-1';
    } elseif (!imscp_limit_check($ftp, -1)) {
        set_page_message(tr('Incorrect FTP accounts limit.'), 'error');
        $errFieldsStack[] = 'ftp';
    }

    if (!resellerHasFeature('sql_db')) {
        $sqld = '-1';
    } elseif (!imscp_limit_check($sqld, -1)) {
        set_page_message(tr('Incorrect SQL databases limit.'), 'error');
        $errFieldsStack[] = 'sql_db';
    } elseif ($sqlu != -1 && $sqld == -1) {
        set_page_message(tr('SQL user limit is <i>disabled</i>.'), 'error');
        $errFieldsStack[] = 'sql_db';
        $errFieldsStack[] = 'sql_user';
    }

    if (!resellerHasFeature('sql_user')) {
        $sqlu = '-1';
    } elseif (!imscp_limit_check($sqlu, -1)) {
        set_page_message(tr('Incorrect SQL user limit.'), 'error');
        $errFieldsStack[] = 'sql_user';
    } elseif ($sqlu == -1 && $sqld != -1) {
        set_page_message(tr('SQL database limit is not <i>disabled</i>.'), 'error');
        $errFieldsStack[] = 'sql_user';
        $errFieldsStack[] = 'sql_db';
    }

    if (!imscp_limit_check($traffic, NULL)) {
        set_page_message(tr('Incorrect monthly traffic limit.'), 'error');
        $errFieldsStack[] = 'traff';
    }

    if (!imscp_limit_check($diskSpace, NULL)) {
        set_page_message(tr('Incorrect disk space limit.'), 'error');
        $errFieldsStack[] = 'disk';
    }

    if ($mail != '-1') {
        if (!imscp_limit_check($mailQuota, NULL)) {
            set_page_message(tr('Wrong syntax for the mail quota value.'), 'error');
            $errFieldsStack[] = 'mail_quota';
        } elseif ($diskSpace != 0 && $mailQuota > $diskSpace) {
            set_page_message(tr('Mail quota cannot be bigger than disk space limit.'), 'error');
            $errFieldsStack[] = 'mail_quota';
        } elseif ($diskSpace != 0 && $mailQuota == 0) {
            set_page_message(tr('Mail quota cannot be unlimited. Max value is %d MiB.', $diskSpace), 'error');
            $errFieldsStack[] = 'mail_quota';
        }
    } else {
        $mailQuota = $diskSpace;
    }

    $phpini = iMSCP_PHPini::getInstance();

    if (isset($_POST['php_ini_system']) && $php != '_no_' && $phpini->resellerHasPermission('phpiniSystem')) {
        $phpini->setClientPermission('phpiniSystem', clean_input($_POST['php_ini_system']));

        if ($phpini->clientHasPermission('phpiniSystem')) {
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

            if (isset($_POST['memory_limit'])) { // Must be set before phpiniPostMaxSize
                $phpini->setDomainIni('phpiniMemoryLimit', clean_input($_POST['memory_limit']));
            }

            if (isset($_POST['post_max_size'])) { // Must be set before phpiniUploadMaxFileSize
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
        } else {
            $phpini->loadClientPermissions(); // Reset client PHP permissions to default values
            $phpini->loadDomainIni(); // Reset domain PHP configuration options to default values
        }
    } else {
        $phpini->loadClientPermissions(); // Reset client PHP permissions to default values
        $phpini->loadDomainIni(); // Reset domain PHP configuration options to default values
    }

    if (!empty($errFieldsStack)) {
        iMSCP_Registry::set('errFieldsStack', $errFieldsStack);
        return false;
    }

    return true;
}

/**
 * Update hosting plan
 *
 * @return bool TRUE on success, FALSE otherwise
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function updateHostingPlan()
{
    global $id, $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $php,
           $cgi, $dns, $backup, $aps, $extMail, $webFolderProtection, $status;

    $phpini = iMSCP_PHPini::getInstance();
    $props = "$php;$cgi;$sub;$als;$mail;$ftp;$sqld;$sqlu;$traffic;$diskSpace;" . implode('|', $backup) . ";$dns;$aps";
    $props .= ';' . $phpini->getClientPermission('phpiniSystem');
    $props .= ';' . $phpini->getClientPermission('phpiniAllowUrlFopen');
    $props .= ';' . $phpini->getClientPermission('phpiniDisplayErrors');
    $props .= ';' . $phpini->getClientPermission('phpiniDisableFunctions');
    $props .= ';' . $phpini->getClientPermission('phpiniMailFunction');
    $props .= ';' . $phpini->getDomainIni('phpiniPostMaxSize');
    $props .= ';' . $phpini->getDomainIni('phpiniUploadMaxFileSize');
    $props .= ';' . $phpini->getDomainIni('phpiniMaxExecutionTime');
    $props .= ';' . $phpini->getDomainIni('phpiniMaxInputTime');
    $props .= ';' . $phpini->getDomainIni('phpiniMemoryLimit');
    $props .= ';' . $extMail . ';' . $webFolderProtection . ';' . $mailQuota * 1048576;

    if (!reseller_limits_check($_SESSION['user_id'], $props)) {
        set_page_message(tr('Hosting plan limits exceed your limits.'), 'error');
        return false;
    }

    exec_query('UPDATE hosting_plans SET name = ?, description = ?, props = ?, status = ? WHERE id = ?', [
        $name, $description, $props, $status, $id
    ]);
    return true;
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('reseller');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

if (!isset($_GET['id'])) {
    showBadRequestErrorPage();
}

$id = intval($_GET['id']);

if (!loadHostingPlan()) {
    showBadRequestErrorPage();
}

if (!empty($_POST) && checkInputData() && updateHostingPlan()) {
    set_page_message(tr('Hosting plan successfully updated.'), 'success');
    redirectTo('hosting_plan.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'                             => 'shared/layouts/ui.tpl',
    'page'                               => 'reseller/hosting_plan_edit.tpl',
    'page_message'                       => 'layout',
    'nb_subdomains'                      => 'page',
    'nb_domain_aliases'                  => 'page',
    'nb_mail'                            => 'page',
    'nb_ftp'                             => 'page',
    'nb_sqld'                            => 'page',
    'nb_sqlu'                            => 'page',
    'php_feature'                        => 'page',
    'php_editor_feature'                 => 'page',
    'php_editor_permissions_block'       => 'php_editor_feature',
    'php_editor_allow_url_fopen_block'   => 'php_editor_permissions_block',
    'php_editor_display_errors_block'    => 'php_editor_permissions_block',
    'php_editor_disable_functions_block' => 'php_editor_permissions_block',
    'php_editor_mail_function_block'     => 'php_editor_permissions_block',
    'php_editor_default_values_block'    => 'php_editor_feature',
    'cgi_feature'                        => 'page',
    'custom_dns_feature'                 => 'page',
    'aps_feature'                        => 'page',
    'backup_feature'                     => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'                 => tr('Reseller /Hosting Plans / Edit Hosting Plan'),
    'TR_HOSTING_PLAN'               => tr('Hosting plan'),
    'TR_NAME'                       => tr('Name'),
    'TR_DESCRIPTON'                 => tr('Description'),
    'TR_HOSTING_PLAN_LIMITS'        => tr('Limits'),
    'TR_MAX_SUB'                    => tr('Subdomains limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ∞)</i>',
    'TR_MAX_ALS'                    => tr('Domain aliases limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ∞)</i>',
    'TR_MAX_MAIL'                   => tr('Mail accounts limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ∞)</i>',
    'TR_MAIL_QUOTA'                 => tr('Mail quota [MiB]') . '<br/><i>(0 ∞)</i>',
    'TR_MAX_FTP'                    => tr('FTP accounts limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ∞)</i>',
    'TR_MAX_SQLD'                   => tr('SQL databases limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ∞)</i>',
    'TR_MAX_SQLU'                   => tr('SQL users limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ∞)</i>',
    'TR_MONTHLY_TRAFFIC'            => tr('Monthly traffic limit [MiB]') . '<br/><i>(0 ∞)</i>',
    'TR_MAX_DISKSPACE'              => tr('Disk space limit [MiB]') . '<br/><i>(0 ∞)</i>',
    'TR_HOSTING_PLAN_FEATURES'      => tr('Features'),
    'TR_PHP'                        => tr('PHP'),
    'TR_CGI'                        => tr('CGI'),
    'TR_DNS'                        => tr('Custom DNS records'),
    'TR_SOFTWARE_SUPP'              => tr('Software installer'),
    'TR_EXTMAIL'                    => tr('External mail server'),
    'TR_WEB_FOLDER_PROTECTION'      => tr('Web folder protection'),
    'TR_WEB_FOLDER_PROTECTION_HELP' => tr('If set to `yes`, Web folders will be protected against deletion.'),
    'TR_BACKUP'                     => tr('Backup'),
    'TR_BACKUP_DOMAIN'              => tr('Domain'),
    'TR_BACKUP_SQL'                 => tr('SQL'),
    'TR_BACKUP_MAIL'                => tr('Mail'),
    'TR_AVAILABILITY'               => tr('Hosting plan availability'),
    'TR_STATUS'                     => tr('Available'),
    'TR_YES'                        => tr('yes'),
    'TR_NO'                         => tr('no'),
    'TR_UPDATE'                     => tr('Update'),
]);

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
