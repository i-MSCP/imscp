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

use iMSCP\PHPini;
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate PHP editor block
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function generatePhpBlock($tpl)
{
    $phpini = PHPini::getInstance();

    if (!$phpini->resellerHasPermission('phpiniSystem')) {
        $tpl->assign('PHP_EDITOR_BLOCK', '');
    }

    $cfg = Registry::get('config');

    $tpl->assign([
        'PHP_EDITOR_YES'         => $phpini->clientHasPermission('phpiniSystem') ? ' checked' : '',
        'PHP_EDITOR_NO'          => $phpini->clientHasPermission('phpiniSystem') ? '' : ' checked',
        'TR_PHP_EDITOR'          => tohtml(tr('PHP Editor')),
        'TR_PHP_EDITOR_SETTINGS' => tohtml(tr('PHP Settings')),
        'TR_SETTINGS'            => tohtml(tr('PHP Settings')),
        'TR_DIRECTIVES_VALUES'   => tohtml(tr('PHP Configuration options')),
        'TR_FIELDS_OK'           => tohtml(tr('All fields are valid.')),
        'TR_MIB'                 => tohtml(tr('MiB')),
        'TR_SEC'                 => tohtml(tr('Sec.'))
    ]);

    Registry::get('iMSCP_Application')->getEventsManager()->registerListener('onGetJsTranslations', function (iMSCP_Events_Event $e) {
        $translations = $e->getParam('translations');
        $translations['core']['close'] = tohtml(tr('Close'));
        $translations['core']['fields_ok'] = tohtml(tr('All fields are valid.'));
        $translations['core']['out_of_range_value_error'] = tohtml(tr('Value for the PHP %%s directive must be in range %%d to %%d.'));
        $translations['core']['lower_value_expected_error'] = tohtml(tr('%%s cannot be greater than %%s.'));
    });

    $permissionsBlock = false;

    if (!$phpini->resellerHasPermission('phpiniConfigLevel')) {
        $tpl->assign('PHPINI_PERM_CONFIG_LEVEL_BLOCK', '');
    } else {
        if ($phpini->getResellerPermission('phpiniConfigLevel') == 'per_site') {
            $tpl->assign([
                'TR_PHPINI_PERM_CONFIG_LEVEL'         => tohtml(tr('PHP configuration level')),
                'TR_PHPINI_PERM_CONFIG_LEVEL_HELP'    => tohtml(tr('Per site: Different PHP configuration for each customer domain, including subdomains<br>Per domain: Identical PHP configuration for each customer domain, including subdomains<br>Per user: Identical PHP configuration for all customer domains, including subdomains'), 'htmlAttr'),
                'TR_PER_DOMAIN'                       => tohtml(tr('Per domain')),
                'TR_PER_SITE'                         => tohtml(tr('Per site')),
                'TR_PER_USER'                         => tohtml(tr('Per user')),
                'PHPINI_PERM_CONFIG_LEVEL_PER_DOMAIN' => $phpini->getClientPermission('phpiniConfigLevel') == 'per_domain' ? ' checked' : '',
                'PHPINI_PERM_CONFIG_LEVEL_PER_SITE'   => $phpini->getClientPermission('phpiniConfigLevel') == 'per_site' ? ' checked' : '',
                'PHPINI_PERM_CONFIG_LEVEL_PER_USER'   => $phpini->getClientPermission('phpiniConfigLevel') == 'per_user' ? ' checked' : '',
            ]);
        } else {
            $tpl->assign([
                'PHPINI_PERM_CONFIG_LEVEL_PER_SITE_BLOCK' => '',
                'TR_PHPINI_PERM_CONFIG_LEVEL'             => tohtml(tr('PHP configuration level')),
                'TR_PHPINI_PERM_CONFIG_LEVEL_HELP'        => tohtml(tr('Per domain: Identical PHP configuration for each customer domain, including subdomains<br>Per user: Identical PHP configuration for all customer domains, including subdomains'), 'htmlAttr'),
                'TR_PER_DOMAIN'                           => tohtml(tr('Per domain')),
                'TR_PER_USER'                             => tohtml(tr('Per user')),
                'PHPINI_PERM_CONFIG_LEVEL_PER_DOMAIN'     => $phpini->getClientPermission('phpiniConfigLevel') == 'per_domain' ? ' checked' : '',
                'PHPINI_PERM_CONFIG_LEVEL_PER_SITE'       => $phpini->getClientPermission('phpiniConfigLevel') == 'per_site' ? ' checked' : '',
                'PHPINI_PERM_CONFIG_LEVEL_PER_USER'       => $phpini->getClientPermission('phpiniConfigLevel') == 'per_user' ? ' checked' : '',
            ]);
        }
        $permissionsBlock = true;
    }

    if (!$phpini->resellerHasPermission('phpiniAllowUrlFopen')) {
        $tpl->assign('PHP_EDITOR_ALLOW_URL_FOPEN_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_CAN_EDIT_ALLOW_URL_FOPEN' => tr('Can edit the PHP %s configuration option', '<strong>allow_url_fopen</strong>'),
            'ALLOW_URL_FOPEN_YES'         => $phpini->clientHasPermission('phpiniAllowUrlFopen') ? ' checked' : '',
            'ALLOW_URL_FOPEN_NO'          => $phpini->clientHasPermission('phpiniAllowUrlFopen') ? '' : ' checked'
        ]);
        $permissionsBlock = true;
    }

    if (!$phpini->resellerHasPermission('phpiniDisplayErrors')) {
        $tpl->assign('PHP_EDITOR_DISPLAY_ERRORS_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_CAN_EDIT_DISPLAY_ERRORS' => tr('Can edit the PHP %s configuration option', '<strong>display_errors</strong>'),
            'DISPLAY_ERRORS_YES'         => $phpini->clientHasPermission('phpiniDisplayErrors') ? ' checked' : '',
            'DISPLAY_ERRORS_NO'          => $phpini->clientHasPermission('phpiniDisplayErrors') ? '' : ' checked'
        ]);
        $permissionsBlock = true;
    }

    if ($cfg['HTTPD_SERVER'] == 'apache2_mpm_itk') {
        $tpl->assign([
            'PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK' => '',
            'PHP_EDITOR_MAIL_FUNCTION_BLOCK'     => ''
        ]);
    } else {
        if ($phpini->resellerHasPermission('phpiniDisableFunctions')) {
            $tpl->assign([
                'TR_CAN_EDIT_DISABLE_FUNCTIONS' => tr('Can edit the PHP %s configuration option', '<strong>disable_functions</strong>'),
                'DISABLE_FUNCTIONS_YES'         => $phpini->getClientPermission('phpiniDisableFunctions') == 'yes' ? ' checked' : '',
                'DISABLE_FUNCTIONS_NO'          => $phpini->getClientPermission('phpiniDisableFunctions') == 'no' ? ' checked' : '',
                'TR_ONLY_EXEC'                  => tohtml(tr('Only exec')),
                'DISABLE_FUNCTIONS_EXEC'        => $phpini->getClientPermission('phpiniDisableFunctions') == 'exec' ? ' checked' : ''
            ]);
        } else {
            $tpl->assign('PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK', '');
        }

        if ($phpini->resellerHasPermission('phpiniMailFunction')) {
            $tpl->assign([
                'TR_CAN_USE_MAIL_FUNCTION' => tr('Can use the PHP %s function', '<strong>mail</strong>'),
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
            'TR_PERMISSIONS' => tohtml(tr('PHP Permissions')),
            'TR_ONLY_EXEC'   => tohtml(tr('Only exec'))
        ]);
    }

    $tpl->assign([
        'TR_POST_MAX_SIZE'          => tr('PHP %s configuration option', '<strong>post_max_size</strong>'),
        'POST_MAX_SIZE'             => tohtml($phpini->getIniOption('phpiniPostMaxSize'), 'htmlAttr'),
        'TR_UPLOAD_MAX_FILEZISE'    => tr('PHP %s configuration option', '<strong>upload_max_filesize</strong>'),
        'UPLOAD_MAX_FILESIZE'       => tohtml($phpini->getIniOption('phpiniUploadMaxFileSize'), 'htmlAttr'),
        'TR_MAX_EXECUTION_TIME'     => tr('PHP %s configuration option', '<strong>max_execution_time</strong>'),
        'MAX_EXECUTION_TIME'        => tohtml($phpini->getIniOption('phpiniMaxExecutionTime'), 'htmlAttr'),
        'TR_MAX_INPUT_TIME'         => tr('PHP %s configuration option', '<strong>max_input_time</strong>'),
        'MAX_INPUT_TIME'            => tohtml($phpini->getIniOption('phpiniMaxInputTime'), 'htmlAttr'),
        'TR_MEMORY_LIMIT'           => tr('PHP %s configuration option', '<strong>memory_limit</strong>'),
        'MEMORY_LIMIT'              => tohtml($phpini->getIniOption('phpiniMemoryLimit'), 'htmlAttr'),
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
 * @param TemplateEngine $tpl
 * @return void
 */
function generatePage($tpl)
{
    global $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $php, $cgi, $backup, $dns, $aps, $extMail,
           $webFolderProtection, $status;

    $tpl->assign([
        'NAME_VALUE'              => tohtml($name, 'htmlAttr'),
        'DESCRIPTION_VALUE'       => tohtml($description),
        'MAX_SUB_LIMITS'          => tohtml($sub, 'htmlAttr'),
        'MAX_ALS_VALUES'          => tohtml($als, 'htmlAttr'),
        'MAIL_VALUE'              => tohtml($mail, 'htmlAttr'),
        'MAIL_QUOTA_VALUE'        => tohtml($mailQuota, 'htmlAttr'),
        'FTP_VALUE'               => tohtml($ftp, 'htmlAttr'),
        'SQL_DB_VALUE'            => tohtml($sqld, 'htmlAttr'),
        'SQL_USER_VALUE'          => tohtml($sqlu, 'htmlAttr'),
        'TRAFF_VALUE'             => tohtml($traffic, 'htmlAttr'),
        'DISK_VALUE'              => tohtml($diskSpace, 'htmlAttr'),
        'PHP_YES'                 => $php == '_yes_' ? ' checked' : '',
        'PHP_NO'                  => $php == '_yes_' ? '' : 'checked',
        'CGI_YES'                 => $cgi == '_yes_' ? ' checked' : '',
        'CGI_NO'                  => $cgi == '_yes_' ? '' : ' checked',
        'DNS_YES'                 => $dns == '_yes_' ? ' checked' : '',
        'DNS_NO'                  => $dns == '_yes_' ? '' : ' checked',
        'SOFTWARE_YES'            => $aps == '_yes_' ? ' checked' : '',
        'SOFTWARE_NO'             => $aps == '_yes_' ? '' : ' checked',
        'EXTMAIL_YES'             => $extMail == '_yes_' ? ' checked' : '',
        'EXTMAIL_NO'              => $extMail == '_yes_' ? '' : ' checked',
        'VL_BACKUPD'              => in_array('_dmn_', $backup) ? ' checked' : '',
        'VL_BACKUPS'              => in_array('_sql_', $backup) ? ' checked' : '',
        'VL_BACKUPM'              => in_array('_mail_', $backup) ? ' checked' : '',
        'PROTECT_WEB_FOLDERS_YES' => $webFolderProtection == '_yes_' ? ' checked' : '',
        'PROTECT_WEB_FOLDERS_NO'  => $webFolderProtection == '_yes_' ? '' : ' checked',
        'STATUS_YES'              => $status ? ' checked' : ' checked',
        'STATUS_NO'               => $status ? '' : ' checked'
    ]);

    Registry::get('iMSCP_Application')->getEventsManager()->registerListener('onGetJsTranslations', function (iMSCP_Events_Event $e) {
        $translations = $e->getParam('translations');
        $translations['core']['error_field_stack'] = Registry::isRegistered('errFieldsStack') ? Registry::get('errFieldsStack') : [];
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
 */
function checkInputData()
{
    global $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $php, $cgi, $dns, $backup, $aps, $extMail,
           $webFolderProtection, $status;

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
        set_page_message(tr('Incorrect subdomains limit.'), 'error');
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
        set_page_message(tr('Incorrect mail accounts limit.'), 'error');
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
        set_page_message(tr('SQL user limit is disabled.'), 'error');
        $errFieldsStack[] = 'sql_db';
        $errFieldsStack[] = 'sql_user';
    }

    if (!resellerHasFeature('sql_user')) {
        $sqlu = '-1';
    } elseif (!imscp_limit_check($sqlu, -1)) {
        set_page_message(tr('Incorrect SQL users limit.'), 'error');
        $errFieldsStack[] = 'sql_user';
    } elseif ($sqlu == -1 && $sqld != -1) {
        set_page_message(tr('SQL databases limit is not disabled.'), 'error');
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

    $phpini = PHPini::getInstance();

    if (isset($_POST['php_ini_system']) && $php != '_no_' && $phpini->resellerHasPermission('phpiniSystem')) {
        $phpini->setClientPermission('phpiniSystem', clean_input($_POST['php_ini_system']));

        if ($phpini->clientHasPermission('phpiniSystem')) {
            if (isset($_POST['phpini_perm_config_level'])) {
                $phpini->setClientPermission('phpiniConfigLevel', clean_input($_POST['phpini_perm_config_level']));
            }

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
                $phpini->setIniOption('phpiniMemoryLimit', clean_input($_POST['memory_limit']));
            }

            if (isset($_POST['post_max_size'])) { // Must be set before phpiniUploadMaxFileSize
                $phpini->setIniOption('phpiniPostMaxSize', clean_input($_POST['post_max_size']));
            }

            if (isset($_POST['upload_max_filesize'])) {
                $phpini->setIniOption('phpiniUploadMaxFileSize', clean_input($_POST['upload_max_filesize']));
            }

            if (isset($_POST['max_execution_time'])) {
                $phpini->setIniOption('phpiniMaxExecutionTime', clean_input($_POST['max_execution_time']));
            }

            if (isset($_POST['max_input_time'])) {
                $phpini->setIniOption('phpiniMaxInputTime', clean_input($_POST['max_input_time']));
            }
        }
    }

    if (!empty($errFieldsStack)) {
        Registry::set('errFieldsStack', $errFieldsStack);
        return false;
    }

    return true;
}

/**
 * Add hosting plan
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function addHostingPlan()
{
    global $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $php, $cgi, $dns, $backup, $aps, $extMail,
           $webFolderProtection, $status;

    $stmt = exec_query('SELECT id FROM hosting_plans WHERE name = ? AND reseller_id = ? LIMIT 1', [$name, $_SESSION['user_id']]);

    if ($stmt->rowCount()) {
        set_page_message(tr('A hosting plan with same name already exists.'), 'error');
        return false;
    }

    $phpini = PHPini::getInstance();
    $props = "$php;$cgi;$sub;$als;$mail;$ftp;$sqld;$sqlu;$traffic;$diskSpace;" . implode('|', $backup) . ";$dns;$aps";
    $props .= ';' . $phpini->getClientPermission('phpiniSystem');
    $props .= ';' . $phpini->getClientPermission('phpiniConfigLevel');
    $props .= ';' . $phpini->getClientPermission('phpiniAllowUrlFopen');
    $props .= ';' . $phpini->getClientPermission('phpiniDisplayErrors');
    $props .= ';' . $phpini->getClientPermission('phpiniDisableFunctions');
    $props .= ';' . $phpini->getClientPermission('phpiniMailFunction');
    $props .= ';' . $phpini->getIniOption('phpiniPostMaxSize');
    $props .= ';' . $phpini->getIniOption('phpiniUploadMaxFileSize');
    $props .= ';' . $phpini->getIniOption('phpiniMaxExecutionTime');
    $props .= ';' . $phpini->getIniOption('phpiniMaxInputTime');
    $props .= ';' . $phpini->getIniOption('phpiniMemoryLimit');
    $props .= ';' . $extMail . ';' . $webFolderProtection . ';' . $mailQuota * 1048576;

    if (!reseller_limits_check($_SESSION['user_id'], $props)) {
        set_page_message(tr('Hosting plan limits exceed your limits.'), 'error');
        return false;
    }

    exec_query('INSERT INTO hosting_plans(reseller_id, name, description, props, status) VALUES (?, ?, ?, ?, ?)', [
        $_SESSION['user_id'], $name, $description, $props, $status
    ]);

    return true;
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('reseller');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptStart);

// Initialize global variables
$name = $description = '';
$sub = $als = $mail = $mailQuota = $ftp = $sqld = $sqlu = $traffic = $diskSpace = 0;
$php = $cgi = $dns = $aps = $extMail = '_no_';
$webFolderProtection = '_yes_';
$status = 1;
$backup = [];

$phpini = PHPini::getInstance();
$phpini->loadResellerPermissions($_SESSION['user_id']); // Load reseller permissions
$phpini->loadClientPermissions(); // Load client default PHP permissions
$phpini->loadIniOptions(); // Load domain default PHP configuration options

if (!empty($_POST) && checkInputData() && addHostingPlan()) {
    set_page_message(tr('Hosting plan successfully created.'), 'success');
    redirectTo('hosting_plan.php');
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                                  => 'shared/layouts/ui.tpl',
    'page'                                    => 'reseller/hosting_plan_add.tpl',
    'page_message'                            => 'layout',
    'nb_subdomains'                           => 'page',
    'nb_domain_aliases'                       => 'page',
    'nb_mail'                                 => 'page',
    'nb_ftp'                                  => 'page',
    'nb_sqld'                                 => 'page',
    'nb_sqlu'                                 => 'page',
    'php_feature'                             => 'page',
    'php_editor_feature'                      => 'page',
    'php_editor_permissions_block'            => 'php_editor_feature',
    'phpini_perm_config_level_block'          => 'php_editor_permissions_block',
    'phpini_perm_config_level_per_site_block' => 'phpini_perm_config_level_block',
    'php_editor_allow_url_fopen_block'        => 'php_editor_permissions_block',
    'php_editor_display_errors_block'         => 'php_editor_permissions_block',
    'php_editor_disable_functions_block'      => 'php_editor_permissions_block',
    'php_editor_mail_function_block'          => 'php_editor_permissions_block',
    'php_editor_default_values_block'         => 'php_editor_feature',
    'cgi_feature'                             => 'page',
    'custom_dns_records_feature'              => 'page',
    'aps_feature'                             => 'page',
    'backup_feature'                          => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'                 => tohtml(tr('Reseller / Hosting Plans / Add Hosting Plan')),
    'TR_HOSTING_PLAN'               => tohtml(tr('Hosting plan')),
    'TR_NAME'                       => tohtml(tr('Name')),
    'TR_DESCRIPTON'                 => tohtml(tr('Description')),
    'TR_HOSTING_PLAN_LIMITS'        => tohtml(tr('Limits')),
    'TR_MAX_SUBDOMAINS'             => tohtml(tr('Subdomains limit')) . '<br><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
    'TR_MAX_ALIASES'                => tohtml(tr('Domain aliases limit')) . '<br><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
    'TR_MAX_MAILACCOUNTS'           => tohtml(tr('Mail accounts limit')) . '<br><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
    'TR_MAIL_QUOTA'                 => tohtml(tr('Mail quota [MiB]')) . '<br><i>(0 ∞)</i>',
    'TR_MAX_FTP'                    => tohtml(tr('FTP accounts limit')) . '<br><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
    'TR_MAX_SQL'                    => tohtml(tr('SQL databases limit')) . '<br><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
    'TR_MAX_SQL_USERS'              => tohtml(tr('SQL users limit')) . '<br><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
    'TR_MAX_TRAFFIC'                => tohtml(tr('Monthly traffic limit [MiB]')) . '<br><i>(0 ∞)</i>',
    'TR_DISK_LIMIT'                 => tohtml(tr('Disk space limit [MiB]')) . '<br><i>(0 ∞)</i>',
    'TR_HOSTING_PLAN_FEATURES'      => tohtml(tr('Features')),
    'TR_PHP'                        => tohtml(tr('PHP')),
    'TR_CGI'                        => tohtml(tr('CGI')),
    'TR_DNS'                        => tohtml(tr('Custom DNS records')),
    'TR_BACKUP'                     => tohtml(tr('Backup')),
    'TR_BACKUP_DOMAIN'              => tohtml(tr('Domain')),
    'TR_BACKUP_SQL'                 => tohtml(tr('SQL')),
    'TR_BACKUP_MAIL'                => tohtml(tr('Mail')),
    'TR_SOFTWARE_SUPP'              => tohtml(tr('Software installer')),
    'TR_EXTMAIL'                    => tohtml(tr('External mail server')),
    'TR_WEB_FOLDER_PROTECTION'      => tohtml(tr('Web folder protection')),
    'TR_WEB_FOLDER_PROTECTION_HELP' => tohtml(tr('If set to `yes`, Web folders will be protected against deletion.')),
    'TR_HP_AVAILABILITY'            => tohtml(tr('Hosting plan availability')),
    'TR_STATUS'                     => tohtml(tr('Available')),
    'TR_YES'                        => tohtml(tr('Yes')),
    'TR_NO'                         => tohtml(tr('No')),
    'TR_ADD'                        => tohtml(tr('Add'), 'htmlAttr'),
    'TR_CANCEL'                     => tohtml(tr('Cancel'))
]);

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
