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

use iMSCP\Crypt as Crypt;
use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_Exception as iMSCPException;
use iMSCP_PHPini as PhpIni;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;
use Zend_Form as Form;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Retrieve form data
 *
 * @return array Reference to array of data
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function getFormData()
{
    static $data = NULL;

    if (NULL !== $data) {
        return $data;
    }

    $stmt = exec_query('SELECT ip_id, ip_number FROM server_ips ORDER BY ip_number');

    if ($stmt->rowCount()) {
        $data['server_ips'] = $stmt->fetchAll();
    } else {
        set_page_message(tr('Unable to get the IP address list. Please fix this problem.'), 'error');
        redirectTo('users.php');
    }

    $phpini = PhpIni::getInstance();

    foreach (
        [
            'max_dmn_cnt'                  => '0',
            'max_sub_cnt'                  => '0',
            'max_als_cnt'                  => '0',
            'max_mail_cnt'                 => '0',
            'max_ftp_cnt'                  => '0',
            'max_sql_db_cnt'               => '0',
            'max_sql_user_cnt'             => '0',
            'max_traff_amnt'               => '0',
            'max_disk_amnt'                => '0',
            'software_allowed'             => 'no',
            'softwaredepot_allowed'        => 'no',
            'websoftwaredepot_allowed'     => 'no',
            'support_system'               => 'yes',
            'php_ini_system'               => $phpini->getResellerPermission('phpiniSystem'),
            'php_ini_al_allow_url_fopen'   => $phpini->getResellerPermission('phpiniAllowUrlFopen'),
            'php_ini_al_display_errors'    => $phpini->getResellerPermission('phpiniDisplayErrors'),
            'php_ini_al_disable_functions' => $phpini->getResellerPermission('phpiniDisableFunctions'),
            'php_ini_al_mail_function'     => $phpini->getResellerPermission('phpiniMailFunction'),
            'post_max_size'                => $phpini->getResellerPermission('phpiniPostMaxSize'),
            'upload_max_filesize'          => $phpini->getResellerPermission('phpiniUploadMaxFileSize'),
            'max_execution_time'           => $phpini->getResellerPermission('phpiniMaxExecutionTime'),
            'max_input_time'               => $phpini->getResellerPermission('phpiniMaxInputTime'),
            'memory_limit'                 => $phpini->getResellerPermission('phpiniMemoryLimit')
        ] as $key => $value
    ) {
        if (isset($_POST[$key])) {
            $data[$key] = clean_input($_POST[$key]);
            continue;
        }

        $data[$key] = $value;
    }

    if (isset($_POST['reseller_ips']) && is_array($_POST['reseller_ips'])) {
        foreach ($_POST['reseller_ips'] as $key => $value) {
            $_POST['reseller_ips'][$key] = clean_input($value);
        }

        $data['reseller_ips'] = $_POST['reseller_ips'];
    } else { // We are safe here
        $data['reseller_ips'] = [];
    }

    return $data;
}

/**
 * Generates IP list form
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 */
function generateIpListForm(TemplateEngine $tpl)
{
    $data = getFormData();
    $tpl->assign([
        'TR_IP_ADDRESS' => tr('IP address'),
        'TR_IP_LABEL'   => tr('Label'),
        'TR_ASSIGN'     => tr('Assign')
    ]);

    EventsManager::getInstance()->registerListener(Events::onGetJsTranslations, function ($e) {
        /** @var $e \iMSCP_Events_Event */
        $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
    });

    $checkFirst = sizeof($data['server_ips']) == 1;

    foreach ($data['server_ips'] as $ipData) {
        $tpl->assign([
            'IP_ID'       => tohtml($ipData['ip_id']),
            'IP_NUMBER'   => tohtml(($ipData['ip_number'] == '0.0.0.0') ? tr('Any') : $ipData['ip_number']),
            'IP_ASSIGNED' => ($checkFirst || in_array($ipData['ip_id'], $data['reseller_ips'])) ? ' checked' : ''
        ]);
        $tpl->parse('IP_BLOCK', '.ip_block');
    }
}

/**
 * Generates features form
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function generateLimitsForm(TemplateEngine $tpl)
{
    $data = getFormData();
    $tpl->assign([
        'TR_ACCOUNT_LIMITS'   => tr('Account limits'),
        'TR_MAX_DMN_CNT'      => tr('Domains limit') . '<br/><i>(0 ∞)</i>',
        'MAX_DMN_CNT'         => tohtml($data['max_dmn_cnt']),
        'TR_MAX_SUB_CNT'      => tr('Subdomains limit') . '<br><i>(-1 ' . tr('disabled') . ', 0 ∞)</i>',
        'MAX_SUB_CNT'         => tohtml($data['max_sub_cnt']),
        'TR_MAX_ALS_CNT'      => tr('Domain aliases limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ∞)</i>',
        'MAX_ALS_CNT'         => tohtml($data['max_als_cnt']),
        'TR_MAX_MAIL_CNT'     => tr('Mail accounts limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ∞)</i>',
        'MAX_MAIL_CNT'        => tohtml($data['max_mail_cnt']),
        'TR_MAX_FTP_CNT'      => tr('FTP accounts limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ∞)</i>',
        'MAX_FTP_CNT'         => tohtml($data['max_ftp_cnt']),
        'TR_MAX_SQL_DB_CNT'   => tr('SQL databases limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ∞)</i>',
        'MAX_SQL_DB_CNT'      => tohtml($data['max_sql_db_cnt']),
        'TR_MAX_SQL_USER_CNT' => tr('SQL users limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ∞)</i>',
        'MAX_SQL_USER_CNT'    => tohtml($data['max_sql_user_cnt']),
        'TR_MAX_TRAFF_AMNT'   => tr('Monthly traffic limit [MiB]') . '<br/><i>(0 ∞)</i>',
        'MAX_TRAFF_AMNT'      => tohtml($data['max_traff_amnt']),
        'TR_MAX_DISK_AMNT'    => tr('Disk space limit [MiB]') . '<br/><i>(0 ∞)</i>',
        'MAX_DISK_AMNT'       => tohtml($data['max_disk_amnt'])
    ]);
}

/**
 * Generates features form
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function generateFeaturesForm(TemplateEngine $tpl)
{
    $data = getFormData();
    $tpl->assign([
        'TR_FEATURES'                      => tr('Features'),
        'TR_SETTINGS'                      => tr('PHP Settings'),
        'TR_PHP_EDITOR'                    => tr('PHP Editor'),
        'TR_PHP_EDITOR_SETTINGS'           => tr('PHP Settings'),
        'TR_PERMISSIONS'                   => tr('PHP Permissions'),
        'TR_DIRECTIVES_VALUES'             => tr('PHP Configuration options'),
        'TR_FIELDS_OK'                     => tr('All fields are valid.'),
        'PHP_INI_SYSTEM_YES'               => $data['php_ini_system'] == 'yes' ? ' checked' : '',
        'PHP_INI_SYSTEM_NO'                => $data['php_ini_system'] != 'yes' ? ' checked' : '',
        'TR_PHP_INI_AL_ALLOW_URL_FOPEN'    => tr('Can edit the PHP %s configuration option', '<b>allow_url_fopen</b>'),
        'PHP_INI_AL_ALLOW_URL_FOPEN_YES'   => $data['php_ini_al_allow_url_fopen'] == 'yes' ? ' checked' : '',
        'PHP_INI_AL_ALLOW_URL_FOPEN_NO'    => $data['php_ini_al_allow_url_fopen'] != 'yes' ? ' checked' : '',
        'TR_PHP_INI_AL_DISPLAY_ERRORS'     => tr('Can edit the PHP %s configuration option', '<b>display_errors</b>'),
        'PHP_INI_AL_DISPLAY_ERRORS_YES'    => $data['php_ini_al_display_errors'] == 'yes' ? ' checked' : '',
        'PHP_INI_AL_DISPLAY_ERRORS_NO'     => $data['php_ini_al_display_errors'] != 'yes' ? ' checked' : '',
        'TR_MEMORY_LIMIT'                  => tr('PHP %s configuration option', '<b>memory_limit</b>'),
        'MEMORY_LIMIT'                     => tohtml($data['memory_limit']),
        'TR_UPLOAD_MAX_FILESIZE'           => tr('PHP %s configuration option', '<b>upload_max_filesize</b>'),
        'UPLOAD_MAX_FILESIZE'              => tohtml($data['upload_max_filesize']),
        'TR_POST_MAX_SIZE'                 => tr('PHP %s configuration option', '<b>post_max_size</b>'),
        'POST_MAX_SIZE'                    => tohtml($data['post_max_size']),
        'TR_MAX_EXECUTION_TIME'            => tr('PHP %s configuration option', '<b>max_execution_time</b>'),
        'MAX_EXECUTION_TIME'               => tohtml($data['max_execution_time']),
        'TR_MAX_INPUT_TIME'                => tr('PHP %s configuration option', '<b>max_input_time</b>'),
        'MAX_INPUT_TIME'                   => tohtml($data['max_input_time']),
        'TR_SOFTWARES_INSTALLER'           => tr('Software installer'),
        'SOFTWARES_INSTALLER_YES'          => $data['software_allowed'] == 'yes' ? ' checked' : '',
        'SOFTWARES_INSTALLER_NO'           => $data['software_allowed'] != 'yes' ? ' checked' : '',
        'TR_SOFTWARES_REPOSITORY'          => tr('Software repository'),
        'SOFTWARES_REPOSITORY_YES'         => $data['softwaredepot_allowed'] == 'yes' ? ' checked' : '',
        'SOFTWARES_REPOSITORY_NO'          => $data['softwaredepot_allowed'] != 'yes' ? ' checked' : '',
        'TR_WEB_SOFTWARES_REPOSITORY'      => tr('Web software repository'),
        'WEB_SOFTWARES_REPOSITORY_YES'     => $data['websoftwaredepot_allowed'] == 'yes' ? ' checked' : '',
        'WEB_SOFTWARES_REPOSITORY_NO'      => $data['websoftwaredepot_allowed'] != 'yes' ? ' checked' : '',
        'TR_SUPPORT_SYSTEM'                => tr('Support system'),
        'SUPPORT_SYSTEM_YES'               => $data['support_system'] == 'yes' ? ' checked' : '',
        'SUPPORT_SYSTEM_NO'                => $data['support_system'] != 'yes' ? ' checked' : '',
        'TR_PHP_INI_PERMISSION_HELP'       => tohtml(tr('If set to `yes`, the reseller can allows his customers to edit this PHP configuration option.'), 'htmlAttr'),
        'TR_PHP_INI_AL_MAIL_FUNCTION_HELP' => tohtml(tr('If set to `yes`, the reseller can enable/disable the PHP mail function for his customers, else, the PHP mail function is disabled.'), 'htmlAttr'),
        'TR_YES'                           => tr('Yes'),
        'TR_NO'                            => tr('No'),
        'TR_MIB'                           => tr('MiB'),
        'TR_SEC'                           => tr('Sec.')
    ]);

    EventsManager::getInstance()->registerListener(Events::onGetJsTranslations, function ($e) {
        /** @var iMSCP_Events_Event $e */
        $translations = $e->getParam('translations');
        $translations['core']['close'] = tr('Close');
        $translations['core']['fields_ok'] = tr('All fields are valid.');
        $translations['core']['out_of_range_value_error'] = tr('Value for the PHP %%s directive must be in range %%d to %%d.');
        $translations['core']['lower_value_expected_error'] = tr('%%s cannot be greater than %%s.');
        $translations['core']['error_field_stack'] = Registry::isRegistered('errFieldsStack')
            ? Registry::get('errFieldsStack') : [];
    });

    if (Registry::get('config')['HTTPD_PACKAGE'] != 'Servers::httpd::apache_itk') {
        $tpl->assign([
            'TR_PHP_INI_AL_DISABLE_FUNCTIONS'  => tr('Can edit the PHP %s configuration option', '<b>disable_functions</b>'),
            'PHP_INI_AL_DISABLE_FUNCTIONS_YES' => $data['php_ini_al_disable_functions'] == 'yes' ? ' checked' : '',
            'PHP_INI_AL_DISABLE_FUNCTIONS_NO'  => $data['php_ini_al_disable_functions'] != 'yes' ? ' checked' : '',
            'TR_PHP_INI_AL_MAIL_FUNCTION'      => tr('Can use the PHP %s function', '<b>mail</b>'),
            'PHP_INI_AL_MAIL_FUNCTION_YES'     => $data['php_ini_al_mail_function'] == 'yes' ? ' checked' : '',
            'PHP_INI_AL_MAIL_FUNCTION_NO'      => $data['php_ini_al_mail_function'] != 'yes' ? ' checked' : '',
        ]);
        return;
    }

    $tpl->assign('PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK', '');
    $tpl->assign('PHP_EDITOR_MAIL_FUNCTION_BLOCK', '');
}

/**
 * Add reseller user
 *
 * @throws Exception
 * @param Form $form
 * @return void
 */
function addResellerUser(Form $form)
{
    $error = false;
    $errFieldsStack = [];
    $db = Database::getInstance();

    try {
        // Check for login and personal data
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $fieldname => $msgsStack) {
                $errFieldsStack[] = $fieldname;
                set_page_message(reset($msgsStack), 'error');
            }
        }

        $data = getFormData();

        // Check for ip addresses - We are safe here
        $resellerIps = [];
        foreach ($data['server_ips'] as $serverIpData) {
            if (in_array($serverIpData['ip_id'], $data['reseller_ips'])) {
                $resellerIps[] = $serverIpData['ip_id'];
            }
        }
        sort($resellerIps);

        if (empty($resellerIps)) {
            set_page_message(tr('You must assign at least one IP to this reseller.'), 'error');
            $error = true;
        }

        // Check for max domains limit
        if (!imscp_limit_check($data['max_dmn_cnt'], NULL)) {
            set_page_message(tr('Incorrect limit for %s.', tr('domain')), 'error');
            $errFieldsStack[] = 'max_dmn_cnt';
        }

        // Check for max subdomains limit
        if (!imscp_limit_check($data['max_sub_cnt'])) {
            set_page_message(tr('Incorrect limit for %s.', tr('subdomains')), 'error');
            $errFieldsStack[] = 'max_sub_cnt';
        }

        // check for max domain aliases limit
        if (!imscp_limit_check($data['max_als_cnt'])) {
            set_page_message(tr('Incorrect limit for %s.', tr('domain aliases')), 'error');
            $errFieldsStack[] = 'max_als_cnt';
        }

        // Check for max mail accounts limit
        if (!imscp_limit_check($data['max_mail_cnt'])) {
            set_page_message(tr('Incorrect limit for %s.', tr('mail accounts')), 'error');
            $errFieldsStack[] = 'max_mail_cnt';
        }

        // Check for max ftp accounts limit
        if (!imscp_limit_check($data['max_ftp_cnt'])) {
            set_page_message(tr('Incorrect limit for %s.', tr('Ftp accounts')), 'error');
            $errFieldsStack[] = 'max_ftp_cnt';
        }

        // Check for max Sql databases limit
        if (!imscp_limit_check($data['max_sql_db_cnt'])) {
            set_page_message(tr('Incorrect limit for %s.', tr('SQL databases')), 'error');
            $errFieldsStack[] = 'max_sql_db_cnt';
        } elseif ($_POST['max_sql_db_cnt'] == -1 && $_POST['max_sql_user_cnt'] != -1) {
            set_page_message(tr('SQL database limit is disabled but SQL user limit is not.'), 'error');
            $errFieldsStack[] = 'max_sql_db_cnt';
        }

        // Check for max Sql users limit
        if (!imscp_limit_check($data['max_sql_user_cnt'])) {
            set_page_message(tr('Incorrect limit for %s.', tr('SQL users')), 'error');
            $errFieldsStack[] = 'max_sql_user_cnt';
        } elseif ($_POST['max_sql_user_cnt'] == -1 && $_POST['max_sql_db_cnt'] != -1) {
            set_page_message(tr('SQL user limit is disabled but SQL database limit is not.'), 'error');
            $errFieldsStack[] = 'max_sql_user_cnt';
        }

        // Check for max monthly traffic limit
        if (!imscp_limit_check($data['max_traff_amnt'], NULL)) {
            set_page_message(tr('Incorrect limit for %s.', tr('traffic')), 'error');
            $errFieldsStack[] = 'max_traff_amnt';
        }

        // Check for max disk space limit
        if (!imscp_limit_check($data['max_disk_amnt'], NULL)) {
            set_page_message(tr('Incorrect limit for %s.', tr('Disk space')), 'error');
            $errFieldsStack[] = 'max_disk_amnt';
        }

        $db->beginTransaction();

        // Check for PHP settings
        $phpini = PhpIni::getInstance();
        $phpini->setResellerPermission('phpiniSystem', $data['php_ini_system']);

        if ($phpini->resellerHasPermission('phpiniSystem')) {
            $phpini->setResellerPermission('phpiniAllowUrlFopen', $data['php_ini_al_allow_url_fopen']);
            $phpini->setResellerPermission('phpiniDisplayErrors', $data['php_ini_al_display_errors']);
            $phpini->setResellerPermission('phpiniDisableFunctions', $data['php_ini_al_disable_functions']);
            $phpini->setResellerPermission('phpiniMailFunction', $data['php_ini_al_mail_function']);

            $phpini->setResellerPermission('phpiniMemoryLimit', $data['memory_limit']); // Must be set before phpiniPostMaxSize
            $phpini->setResellerPermission('phpiniPostMaxSize', $data['post_max_size']); // Must be set before phpiniUploadMaxFileSize
            $phpini->setResellerPermission('phpiniUploadMaxFileSize', $data['upload_max_filesize']);
            $phpini->setResellerPermission('phpiniMaxExecutionTime', $data['max_execution_time']);
            $phpini->setResellerPermission('phpiniMaxInputTime', $data['max_input_time']);
        }

        if (empty($errFieldsStack) && !$error) {
            EventsManager::getInstance()->dispatch(Events::onBeforeAddUser, [
                'userData' => $form->getValues()
            ]);

            exec_query(
                '
                    INSERT INTO admin (
                        admin_name, admin_pass, admin_type, domain_created, created_by, fname, lname, firm, zip, city,
                        state, country, email, phone, fax, street1, street2, gender
                    ) VALUES (
                        ?, ?, ?, unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ',
                [
                    $form->getValue('admin_name'), Crypt::apr1MD5($form->getValue('admin_pass')), 'reseller',
                    $_SESSION['user_id'], $form->getValue('fname'), $form->getValue('lname'), $form->getValue('firm'),
                    $form->getValue('zip'), $form->getValue('city'), $form->getValue('state'), $form->getValue('country'),
                    encode_idna($form->getValue('email')), $form->getValue('phone'), $form->getValue('fax'),
                    $form->getValue('street1'), $form->getValue('street2'), $form->getValue('gender')
                ]
            );

            $resellerId = $db->insertId();
            $cfg = Registry::get('config');

            exec_query('INSERT INTO user_gui_props (user_id, lang, layout) VALUES (?, ?, ?)', [
                $resellerId, $cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME']
            ]);
            exec_query(
                '
                    INSERT INTO reseller_props (
                        reseller_id, reseller_ips, max_dmn_cnt, current_dmn_cnt, max_sub_cnt, current_sub_cnt,
                        max_als_cnt, current_als_cnt, max_mail_cnt, current_mail_cnt, max_ftp_cnt, current_ftp_cnt,
                        max_sql_db_cnt, current_sql_db_cnt, max_sql_user_cnt, current_sql_user_cnt, max_traff_amnt,
                        current_traff_amnt, max_disk_amnt, current_disk_amnt, support_system,
                        software_allowed, softwaredepot_allowed, websoftwaredepot_allowed, php_ini_system,
                        php_ini_al_disable_functions, php_ini_al_mail_function, php_ini_al_allow_url_fopen,
                        php_ini_al_display_errors, php_ini_max_post_max_size, php_ini_max_upload_max_filesize,
                        php_ini_max_max_execution_time, php_ini_max_max_input_time, php_ini_max_memory_limit
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?
                    )
                ',
                [
                    $resellerId, implode(';', $resellerIps) . ';', $data['max_dmn_cnt'], '0', $data['max_sub_cnt'], '0',
                    $data['max_als_cnt'], '0', $data['max_mail_cnt'], '0', $data['max_ftp_cnt'], '0', $data['max_sql_db_cnt'],
                    '0', $data['max_sql_user_cnt'], '0', $data['max_traff_amnt'], '0', $data['max_disk_amnt'], '0',
                    $data['support_system'], $data['software_allowed'], $data['softwaredepot_allowed'],
                    $data['websoftwaredepot_allowed'],
                    $phpini->getResellerPermission('phpiniSystem'),
                    $phpini->getResellerPermission('phpiniDisableFunctions'),
                    $phpini->getResellerPermission('phpiniMailFunction'),
                    $phpini->getResellerPermission('phpiniAllowUrlFopen'),
                    $phpini->getResellerPermission('phpiniDisplayErrors'),
                    $phpini->getResellerPermission('phpiniPostMaxSize'),
                    $phpini->getResellerPermission('phpiniUploadMaxFileSize'),
                    $phpini->getResellerPermission('phpiniMaxExecutionTime'),
                    $phpini->getResellerPermission('phpiniMaxInputTime'),
                    $phpini->getResellerPermission('phpiniMemoryLimit')
                ]
            );

            // Creating Software repository for reseller if needed
            if ($data['software_allowed'] == 'yes' && !@mkdir($cfg['GUI_APS_DIR'] . '/' . $resellerId, 0750, true)) {
                write_log('System was unable to create directory for reseller software repository', E_USER_ERROR);
                throw new iMSCPException(sprintf('Could not create directory for software repository'));
            }

            EventsManager::getInstance()->dispatch(Events::onAfterAddUser, [
                'userId'   => $resellerId,
                'userData' => $form->getValues()
            ]);

            $db->commit();
            send_add_user_auto_msg(
                $_SESSION['user_id'], $form->getValue('admin_name'), $form->getValue('admin_pass'),
                $form->getValue('email'), $form->getValue('fname'),
                $form->getValue('lname'), tr('Reseller')
            );
            write_log(
                sprintf('The %s reseller has been added by %s', $form->getValue('admin_name'), $_SESSION['user_logged']),
                E_USER_NOTICE
            );
            set_page_message('Reseller has been added.', 'success');
            redirectTo('users.php');
        } elseif (!empty($errFieldsStack)) {
            Registry::set('errFieldsStack', $errFieldsStack);
        }
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl Template engine instance
 * @param Form $form
 * @return void
 */
function generatePage(TemplateEngine $tpl, Form $form)
{
    /** @noinspection PhpUndefinedFieldInspection */
    $tpl->form = $form;

    generateIpListForm($tpl);
    generateLimitsForm($tpl);
    generateFeaturesForm($tpl);
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
EventsManager::getInstance()->dispatch(Events::onAdminScriptStart);

$phpini = PhpIni::getInstance();
$phpini->loadResellerPermissions(); // Load reseller default PHP permissions

$form = getUserLoginDataForm(true, true)->addElements(getUserPersonalDataForm()->getElements());
$form->setDefault('gender', 'U');

if (!empty($_POST)) {
    addResellerUser($form);
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'                             => 'shared/layouts/ui.tpl',
    'page'                               => 'admin/reseller_add.phtml',
    'page_message'                       => 'layout',
    'ips_block'                          => 'page',
    'ip_block'                           => 'ips_block',
    'php_editor_disable_functions_block' => 'page',
    'php_editor_mail_function_block'     => 'page'
]);
$tpl->assign('TR_PAGE_TITLE', tohtml(tr('Admin / Users / Add Reseller')));

generateNavigation($tpl);
generatePage($tpl, $form);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventsManager::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
