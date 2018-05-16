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
 * @param int $resellerId Domain unique identifier
 * @param bool $forUpdate Tell whether or not data are fetched for update
 * @return array Reference to array of data
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function getFormData($resellerId, $forUpdate = false)
{
    static $data = NULL;

    if (NULL !== $data) {
        return $data;
    }

    $stmt = exec_query(
        '
            SELECT t1.*, t2.*
            FROM admin AS t1
            JOIN reseller_props AS t2 ON(t2.reseller_id = t1.admin_id)
            WHERE t1.admin_id = ?
        ',
        $resellerId
    );

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $data = $stmt->fetchRow();
    $data['admin_pass'] = '';

    // Getting total of consumed items for the given reseller.
    list($data['nbDomains'], $data['nbSubdomains'], $data['nbDomainAliases'], $data['nbMailAccounts'],
        $data['nbFtpAccounts'], $data['nbSqlDatabases'], $data['nbSqlUsers'], $data['totalTraffic'],
        $data['totalDiskspace']) = getResellerStats($resellerId);

    // Ip data begin

    // Fetch server ip list
    $stmt = exec_query('SELECT ip_id, ip_number FROM server_ips  ORDER BY ip_number');

    if (!$stmt->rowCount()) {
        set_page_message(tr('Unable to get the IP address list. Please fix this problem.'), 'error');
        redirectTo('users.php');
    }

    $data['server_ips'] = $stmt->fetchAll();

    // Convert reseller ip list to array
    $data['reseller_ips'] = explode(';', trim($data['reseller_ips'], ';'));

    // Fetch all ip id used by reseller's customers
    $stmt = exec_query(
        'SELECT DISTINCT domain_ip_id FROM domain JOIN admin ON(admin_id = domain_admin_id) WHERE created_by = ?',
        $resellerId
    );

    if ($stmt->rowCount()) {
        $data['used_ips'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $data['used_ips'] = [];
    }

    $fallbackData = [];
    foreach ($data as $key => $value) {
        $fallbackData["fallback_$key"] = $value;
    }

    $data = array_merge($data, $fallbackData);

    $phpini = PhpIni::getInstance();

    $data['php_ini_system'] = $phpini->getResellerPermission('phpiniSystem');
    $data['php_ini_al_disable_functions'] = $phpini->getResellerPermission('phpiniDisableFunctions');
    $data['php_ini_al_mail_function'] = $phpini->getResellerPermission('phpiniMailFunction');
    $data['php_ini_al_allow_url_fopen'] = $phpini->getResellerPermission('phpiniAllowUrlFopen');
    $data['php_ini_al_display_errors'] = $phpini->getResellerPermission('phpiniDisplayErrors');
    $data['post_max_size'] = $phpini->getResellerPermission('phpiniPostMaxSize');
    $data['upload_max_filesize'] = $phpini->getResellerPermission('phpiniUploadMaxFileSize');
    $data['max_execution_time'] = $phpini->getResellerPermission('phpiniMaxExecutionTime');
    $data['max_input_time'] = $phpini->getResellerPermission('phpiniMaxInputTime');
    $data['memory_limit'] = $phpini->getResellerPermission('phpiniMemoryLimit');

    if (!$forUpdate) {
        return $data;
    }

    foreach (
        [
            'max_dmn_cnt', 'max_sub_cnt', 'max_als_cnt', 'max_mail_cnt', 'max_ftp_cnt', 'max_sql_db_cnt',
            'max_sql_user_cnt', 'max_traff_amnt', 'max_disk_amnt', 'software_allowed', 'softwaredepot_allowed',
            'websoftwaredepot_allowed', 'support_system'
        ] as $key
    ) {
        if (isset($_POST[$key])) {
            $data[$key] = clean_input($_POST[$key]);
        }
    }

    if (isset($_POST['reseller_ips']) && is_array($data['reseller_ips'])) {
        foreach ($_POST['reseller_ips'] as $key => $value) {
            $_POST['reseller_ips'][$key] = clean_input($value);
        }

        $data['reseller_ips'] = $_POST['reseller_ips'];
    } else { // We are safe here
        $data['reseller_ips'] = [];
    }

    if (isset($_POST['php_ini_system'])) {
        $data['php_ini_system'] = clean_input($_POST['php_ini_system']);
    }

    if (isset($_POST['php_ini_al_disable_functions'])) {
        $data['php_ini_al_disable_functions'] = clean_input($_POST['php_ini_al_disable_functions']);
    }

    if (isset($_POST['php_ini_al_mail_function'])) {
        $data['php_ini_al_mail_function'] = clean_input($_POST['php_ini_al_mail_function']);
    }

    if (isset($_POST['php_ini_al_allow_url_fopen'])) {
        $data['php_ini_al_allow_url_fopen'] = clean_input($_POST['php_ini_al_allow_url_fopen']);
    }

    if (isset($_POST['php_ini_al_display_errors'])) {
        $data['php_ini_al_display_errors'] = clean_input($_POST['php_ini_al_display_errors']);
    }

    if (isset($_POST['post_max_size'])) {
        $data['post_max_size'] = clean_input($_POST['post_max_size']);
    }

    if (isset($_POST['upload_max_filesize'])) {
        $data['upload_max_filesize'] = clean_input($_POST['upload_max_filesize']);
    }

    if (isset($_POST['max_execution_time'])) {
        $data['max_execution_time'] = clean_input($_POST['max_execution_time']);
    }

    if (isset($_POST['max_input_time'])) {
        $data['max_input_time'] = clean_input($_POST['max_input_time']);
    }

    if (isset($_POST['memory_limit'])) {
        $data['memory_limit'] = clean_input($_POST['memory_limit']);
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
    global $resellerId;

    $data = getFormData($resellerId);
    $assignedTranslation = tr('Already in use');
    $unusedTranslation = tr('Not used');

    $tpl->assign([
        'TR_IP_ADDRESS' => tr('IP address'),
        'TR_IP_LABEL'   => tr('Label'),
        'TR_ASSIGN'     => tr('Assign'),
        'TR_STATUS'     => tr('Usage status')
    ]);

    EventsManager::getInstance()->registerListener(Events::onGetJsTranslations, function ($e) {
        /** @var $e \iMSCP_Events_Event */
        $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
    });

    foreach ($data['server_ips'] as $ipData) {
        $resellerHasIp = in_array($ipData['ip_id'], $data['reseller_ips']);
        $isUsedIp = in_array($ipData['ip_id'], $data['used_ips']);
        $tpl->assign([
            'IP_ID'       => tohtml($ipData['ip_id']),
            'IP_NUMBER'   => tohtml(($ipData['ip_number'] == '0.0.0.0') ? tr('Any') : $ipData['ip_number']),
            'IP_ASSIGNED' => $resellerHasIp ? ' checked' : '',
            'IP_STATUS'   => $isUsedIp ? $assignedTranslation : $unusedTranslation,
            'IP_READONLY' => $isUsedIp ? ' title="' . tohtml(tr('You cannot unassign an IP address already in use.'), 'htmlAttr') . '" readonly' : ''
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
    global $resellerId;

    $data = getFormData($resellerId);
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
    global $resellerId;

    $data = getFormData($resellerId);
    $tpl->assign([
        'TR_FEATURES'                      => tr('Features'),
        'TR_SETTINGS'                      => tr('PHP Settings'),
        'TR_PHP_EDITOR'                    => tr('PHP Editor'),
        'TR_PHP_EDITOR_SETTINGS'           => tr('PHP Settings'),
        'TR_PERMISSIONS'                   => tr('PHP Permissions'),
        'TR_DIRECTIVES_VALUES'             => tr('PHP directives values'),
        'TR_FIELDS_OK'                     => tr('All fields are valid.'),
        'PHP_INI_SYSTEM_YES'               => $data['php_ini_system'] == 'yes' ? ' checked' : '',
        'PHP_INI_SYSTEM_NO'                => $data['php_ini_system'] != 'yes' ? ' checked' : '',
        'TR_PHP_INI_AL_ALLOW_URL_FOPEN'    => tr('Can edit the PHP %s configuration option', '<b>allow_url_fopen</b>'),
        'PHP_INI_AL_ALLOW_URL_FOPEN_YES'   => $data['php_ini_al_allow_url_fopen'] == 'yes' ? ' checked' : '',
        'PHP_INI_AL_ALLOW_URL_FOPEN_NO'    => $data['php_ini_al_allow_url_fopen'] != 'yes' ? ' checked' : '',
        'TR_PHP_INI_AL_DISPLAY_ERRORS'     => tr('Can edit the PHP %s configuration option', '<b>display_errors</b>'),
        'PHP_INI_AL_DISPLAY_ERRORS_YES'    => $data['php_ini_al_display_errors'] == 'yes' ? ' checked' : '',
        'PHP_INI_AL_DISPLAY_ERRORS_NO'     => $data['php_ini_al_display_errors'] != 'yes' ? ' checked' : '',
        'TR_PHP_INI_AL_DISABLE_FUNCTIONS'  => tr('Can edit the PHP %s configuration option', '<b>disable_functions</b>'),
        'PHP_INI_AL_DISABLE_FUNCTIONS_YES' => $data['php_ini_al_disable_functions'] == 'yes' ? ' checked' : '',
        'PHP_INI_AL_DISABLE_FUNCTIONS_NO'  => $data['php_ini_al_disable_functions'] != 'yes' ? ' checked' : '',
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

            'TR_PHP_INI_AL_MAIL_FUNCTION'  => tr('Can use the PHP %s function', '<b>mail</b>'),
            'PHP_INI_AL_MAIL_FUNCTION_YES' => $data['php_ini_al_mail_function'] == 'yes' ? ' checked' : '',
            'PHP_INI_AL_MAIL_FUNCTION_NO'  => $data['php_ini_al_mail_function'] != 'yes' ? ' checked' : '',
        ]);
        return;
    }

    $tpl->assign('PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK', '');
    $tpl->assign('PHP_EDITOR_MAIL_FUNCTION_BLOCK', '');
}

/**
 * Update reseller user
 *
 * @throws Exception
 * @param Form $form
 * @return void
 */
function updateResellerUser(Form $form)
{
    global $resellerId;

    $error = false;
    $errFieldsStack = [];
    $db = Database::getInstance();

    try {
        $data = getFormData($resellerId, true);

        $stmt = exec_query(
            "
            SELECT
                IFNULL(SUM(t1.domain_subd_limit), 0) AS subdomains,
                IFNULL(SUM(t1.domain_alias_limit), 0) AS domainAliases,
                IFNULL(SUM(t1.domain_mailacc_limit), 0) AS mailAccounts,
                IFNULL(SUM(t1.domain_ftpacc_limit), 0) AS ftpAccounts,
                IFNULL(SUM(t1.domain_sqld_limit), 0) AS sqlDatabases,
                IFNULL(SUM(t1.domain_sqlu_limit), 0) AS sqlUsers,
                IFNULL(SUM(t1.domain_traffic_limit), 0) AS traffic,
                IFNULL(SUM(t1.domain_disk_limit), 0) AS diskspace
            FROM domain AS t1
            JOIN admin AS t2 ON(t2.admin_id = t1.domain_admin_id)
            WHERE t2.created_by = ?
        ",
            $resellerId
        );

        $unlimitedItems = array_map(
            function ($element) {
                return $element == -1 ? false : $element == 0;
            },
            $stmt->fetchRow(PDO::FETCH_ASSOC)
        );

        // Check for login and personal data
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $fieldname => $msgsStack) {
                $errFieldsStack[] = $fieldname;
                set_page_message(reset($msgsStack), 'error');
            }
        }

        $form->setDefault('admin_name', $data['fallback_admin_name']);

        // Make sure to compare and store email in IDNA form
        $form->setDefault('email', encode_idna($form->getValue('email')));

        // Check for ip addresses
        $resellerIps = [];
        foreach ($data['server_ips'] as $serverIpData) {
            if (in_array($serverIpData['ip_id'], $data['reseller_ips'], true)) {
                $resellerIps[] = $serverIpData['ip_id'];
            }
        }

        $resellerIps = array_unique(array_merge($resellerIps, $data['used_ips']));
        sort($resellerIps);

        if (empty($resellerIps)) {
            set_page_message(tr('You must assign at least one IP to this reseller.'), 'error');
            $error = true;
        }

        // Check for max domains limit
        if (imscp_limit_check($data['max_dmn_cnt'], NULL)) {
            $rs = checkResellerLimit(
                $data['max_dmn_cnt'], $data['current_dmn_cnt'], $data['nbDomains'], false, tr('domains')
            );
        } else {
            set_page_message(tr('Incorrect limit for %s.', tr('domain')), 'error');
            $rs = false;
        }

        if (!$rs) {
            $errFieldsStack[] = 'max_dmn_cnt';
        }

        // Check for max subdomains limit
        if (imscp_limit_check($data['max_sub_cnt'])) {
            $rs = checkResellerLimit(
                $data['max_sub_cnt'], $data['current_sub_cnt'], $data['nbSubdomains'], $unlimitedItems['subdomains'],
                tr('subdomains')
            );
        } else {
            set_page_message(tr('Incorrect limit for %s.', tr('subdomains')), 'error');
            $rs = false;
        }

        if (!$rs) {
            $errFieldsStack[] = 'max_sub_cnt';
        }

        // check for max domain aliases limit
        if (imscp_limit_check($data['max_als_cnt'])) {
            $rs = checkResellerLimit(
                $data['max_als_cnt'], $data['current_als_cnt'], $data['nbDomainAliases'],
                $unlimitedItems['domainAliases'], tr('domain aliases')
            );
        } else {
            set_page_message(tr('Incorrect limit for %s.', tr('domain aliases')), 'error');
            $rs = false;
        }

        if (!$rs) {
            $errFieldsStack[] = 'max_als_cnt';
        }

        // Check for max mail accounts limit
        if (imscp_limit_check($data['max_mail_cnt'])) {
            $rs = checkResellerLimit(
                $data['max_mail_cnt'], $data['current_mail_cnt'], $data['nbMailAccounts'],
                $unlimitedItems['mailAccounts'], tr('mail')
            );
        } else {
            set_page_message(tr('Incorrect limit for %s.', tr('mail accounts')), 'error');
            $rs = false;
        }

        if (!$rs) {
            $errFieldsStack[] = 'max_mail_cnt';
        }

        // Check for max FTP accounts limit
        if (imscp_limit_check($data['max_ftp_cnt'])) {
            $rs = checkResellerLimit(
                $data['max_ftp_cnt'], $data['current_ftp_cnt'], $data['nbFtpAccounts'], $unlimitedItems['ftpAccounts'],
                tr('Ftp')
            );
        } else {
            set_page_message(tr('Incorrect limit for %s.', tr('Ftp accounts')), 'error');
            $rs = false;
        }

        if (!$rs) {
            $errFieldsStack[] = 'max_ftp_cnt';
        }

        // Check for max SQL databases limit
        if (!$rs = imscp_limit_check($data['max_sql_db_cnt'])) {
            set_page_message(tr('Incorrect limit for %s.', tr('SQL databases')), 'error');
        } elseif ($data['max_sql_db_cnt'] == -1 && $data['max_sql_user_cnt'] != -1) {
            set_page_message(tr('SQL database limit is disabled but SQL user limit is not.'), 'error');
            $rs = false;
        } else {
            $rs = checkResellerLimit(
                $data['max_sql_db_cnt'], $data['current_sql_db_cnt'], $unlimitedItems['nbSqlDatabases'],
                $data['sqlDatabases'], tr('SQL databases')
            );
        }

        if (!$rs) {
            $errFieldsStack[] = 'max_sql_db_cnt';
        }

        // Check for max SQL users limit
        if (!$rs = imscp_limit_check($data['max_sql_user_cnt'])) {
            set_page_message(tr('Incorrect limit for %s.', tr('SQL users')), 'error');
        } elseif ($data['max_sql_db_cnt'] != -1 && $data['max_sql_user_cnt'] == -1) {
            set_page_message(tr('SQL user limit is disabled but SQL database limit is not.'), 'error');
            $rs = false;
        } else {
            $rs = checkResellerLimit(
                $data['max_sql_user_cnt'], $data['current_sql_user_cnt'], $data['nbSqlUsers'],
                $unlimitedItems['sqlUsers'], tr('SQL users')
            );
        }

        if (!$rs) {
            $errFieldsStack[] = 'max_sql_user_cnt';
        }

        // Check for max monthly traffic limit
        if (imscp_limit_check($data['max_traff_amnt'], NULL)) {
            $rs = checkResellerLimit(
                $data['max_traff_amnt'], $data['current_traff_amnt'], $data['totalTraffic'] / 1048576,
                $unlimitedItems['traffic'], tr('traffic')
            );
        } else {
            set_page_message(tr('Incorrect limit for %s.', tr('traffic')), 'error');
            $rs = false;
        }

        if (!$rs) {
            $errFieldsStack[] = 'max_traff_amnt';
        }

        // Check for max disk space limit
        if (imscp_limit_check($data['max_disk_amnt'], NULL)) {
            $rs = checkResellerLimit(
                $data['max_disk_amnt'], $data['current_disk_amnt'], $data['totalDiskspace'] / 1048576,
                $unlimitedItems['diskspace'], tr('disk space')
            );
        } else {
            set_page_message(tr('Incorrect limit for %s.', tr('disk space')), 'error');
            $rs = false;
        }

        if (!$rs) {
            $errFieldsStack[] = 'max_disk_amnt';
        }

        $db->beginTransaction();

        // Check for PHP settings
        $phpini = iMSCP_PHPini::getInstance();
        $curResPhpPerms = $phpini->getResellerPermission();
        $phpini->setResellerPermission('phpiniSystem', $data['php_ini_system']);

        if ($phpini->resellerHasPermission('phpiniSystem')) {
            // We are safe here; If a value is not valid, previous value is used
            $phpini->setResellerPermission('phpiniDisableFunctions', $data['php_ini_al_disable_functions']);
            $phpini->setResellerPermission('phpiniMailFunction', $data['php_ini_al_mail_function']);
            $phpini->setResellerPermission('phpiniAllowUrlFopen', $data['php_ini_al_allow_url_fopen']);
            $phpini->setResellerPermission('phpiniDisplayErrors', $data['php_ini_al_display_errors']);

            // Must be set before phpiniPostMaxSize
            $phpini->setResellerPermission('phpiniMemoryLimit', $data['memory_limit']);

            // Must be set before phpiniUploadMaxFileSize
            $phpini->setResellerPermission('phpiniPostMaxSize', $data['post_max_size']);

            $phpini->setResellerPermission('phpiniUploadMaxFileSize', $data['upload_max_filesize']);
            $phpini->setResellerPermission('phpiniMaxExecutionTime', $data['max_execution_time']);
            $phpini->setResellerPermission('phpiniMaxInputTime', $data['max_input_time']);
        } else {
            $phpini->loadResellerPermissions(); // Reset reseller PHP permissions to default values
        }

        if (empty($errFieldsStack) && !$error) {
            EventsManager::getInstance()->dispatch(Events::onBeforeEditUser, [
                'userId'   => $resellerId,
                'userData' => $form->getValues()
            ]);

            $oldValues = $newValues = [];

            foreach ($data as $property => $value) {
                if (strpos($property, 'fallback_') !== false) {
                    $property = substr($property, 9);
                    $oldValues[$property] = $value;

                    if (($formVal = $form->getValue($property)) !== NULL) {
                        $newValues[$property] = $formVal;
                        continue;
                    }

                    $newValues[$property] = $data[$property];
                }
            }

            $needPHPiniChange = ($curResPhpPerms != $phpini->getResellerPermission());
            unset($curResPhpPerms);

            // Nothing has been changed ?
            if ($newValues == $oldValues && !$needPHPiniChange) {
                set_page_message(tr('Nothing has been changed.'), 'info');
                redirectTo('users.php');
            }

            // Update reseller personal data (including password if needed)

            $bindParams = [
                $form->getValue('fname'), $form->getValue('lname'), $form->getValue('gender'), $form->getValue('firm'),
                $form->getValue('zip'), $form->getValue('city'), $form->getValue('state'), $form->getValue('country'),
                $form->getValue('email'), $form->getValue('phone'), $form->getValue('fax'),
                $form->getValue('street1'), $form->getValue('street2'), $resellerId
            ];

            if ($form->getValue('admin_pass') != '') {
                $setPassword = 'admin_pass = ?,';
                array_unshift($bindParams, Crypt::apr1MD5($form->getValue('admin_pass')));
            } else {
                $setPassword = '';
            }

            exec_query(
                "
                    UPDATE admin SET {$setPassword} fname = ?, lname = ?, gender = ?, firm = ?, zip = ?, city = ?,
                        state = ?, country = ?, email = ?, phone = ?, fax = ?, street1 = ?, street2 = ?
                    WHERE admin_id = ?
                ",
                $bindParams
            );

            exec_query(
                '
                    UPDATE
                        reseller_props
                    SET
                        max_dmn_cnt = ?, max_sub_cnt = ?, max_als_cnt = ?, max_mail_cnt = ?, max_ftp_cnt = ?,
                        max_sql_db_cnt = ?, max_sql_user_cnt = ?, max_traff_amnt = ?, max_disk_amnt = ?,
                        reseller_ips = ?, software_allowed = ?, softwaredepot_allowed = ?,
                        websoftwaredepot_allowed = ?, support_system = ?, php_ini_system = ?,
                        php_ini_al_disable_functions = ?, php_ini_al_mail_function = ?,
                        php_ini_al_allow_url_fopen = ?, php_ini_al_display_errors = ?, php_ini_max_post_max_size = ?,
                        php_ini_max_upload_max_filesize = ?, php_ini_max_max_execution_time = ?,
                        php_ini_max_max_input_time = ?, php_ini_max_memory_limit = ?
                    WHERE
                        reseller_id = ?
                ',
                [
                    $data['max_dmn_cnt'], $data['max_sub_cnt'], $data['max_als_cnt'], $data['max_mail_cnt'],
                    $data['max_ftp_cnt'], $data['max_sql_db_cnt'], $data['max_sql_user_cnt'], $data['max_traff_amnt'],
                    $data['max_disk_amnt'], implode(';', $resellerIps) . ';', $data['software_allowed'],
                    $data['softwaredepot_allowed'], $data['websoftwaredepot_allowed'], $data['support_system'],
                    $phpini->getResellerPermission('phpiniSystem'),
                    $phpini->getResellerPermission('phpiniDisableFunctions'),
                    $phpini->getResellerPermission('phpiniMailFunction'),
                    $phpini->getResellerPermission('phpiniAllowUrlFopen'),
                    $phpini->getResellerPermission('phpiniDisplayErrors'),
                    $phpini->getResellerPermission('phpiniPostMaxSize'),
                    $phpini->getResellerPermission('phpiniUploadMaxFileSize'),
                    $phpini->getResellerPermission('phpiniMaxExecutionTime'),
                    $phpini->getResellerPermission('phpiniMaxInputTime'),
                    $phpini->getResellerPermission('phpiniMemoryLimit'),
                    $resellerId
                ]
            );

            // Sync client PHP permissions with reseller PHP permissions
            if ($needPHPiniChange && $phpini->syncClientPermissionsWithResellerPermissions($resellerId)) {
                $needDaemonRequest = true;
            } else {
                $needDaemonRequest = false;
            }

            // Updating software installer properties
            if ($data['software_allowed'] == 'no') {
                exec_query(
                    '
                        UPDATE domain
                        JOIN admin ON(admin_id = domain_admin_id)
                        SET domain_software_allowed = ?
                        WHERE created_by = ?
                    ',
                    [$data['softwaredepot_allowed'], $resellerId]
                );
            }

            if ($data['websoftwaredepot_allowed'] == 'no') {
                $stmt = exec_query(
                    'SELECT software_id FROM web_software WHERE software_depot = ? AND reseller_id = ?',
                    ['yes', $resellerId]
                );

                if ($stmt->rowCount()) {
                    while ($row = $stmt->fetchRow()) {
                        exec_query('UPDATE web_software_inst SET software_res_del = ? WHERE software_id = ?', [
                            '1', $row['software_id']
                        ]);
                    }

                    exec_query('DELETE FROM web_software WHERE software_depot = ? AND reseller_id = ?', [
                        'yes', $resellerId
                    ]);
                }
            }

            // Force user to login again (needed due to possible password or email change)
            exec_query('DELETE FROM login WHERE user_name = ?', $data['fallback_admin_name']);

            EventsManager::getInstance()->dispatch(Events::onAfterEditUser, [
                'userId'   => $resellerId,
                'userData' => $form->getValues()
            ]);

            $db->commit();

            // Send mail to reseller for new password
            if ($form->getValue('admin_pass') !== '') {
                send_add_user_auto_msg(
                    $_SESSION['user_id'], $data['admin_name'], $form->getValue('admin_pass'), $form->getValue('email'),
                    $form->getValue('fname'), $form->getValue('lname'), tr('Reseller')
                );
            }

            if ($needDaemonRequest) {
                send_request();
            }

            write_log(
                sprintf('The %s reseller has been updated by %s', $form->getValue('admin_name'), $_SESSION['user_logged']),
                E_USER_NOTICE
            );
            set_page_message('Reseller has been updated.', 'success');
            redirectTo('users.php');
        } elseif (!empty($errFieldsStack)) {
            iMSCP_Registry::set('errFieldsStack', $errFieldsStack);
        }
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Check reseller limit
 *
 * @param int $newLimit New limit (-1 for deactivation, 0 for ∞, $newLimit > 0 to limit items quantity)
 * @param int $assignedByReseller How many items are already assigned to reseller's customers
 * @param int $consumedByCustomers How many items are already consumed by reseller's customers.
 * @param bool $unlimitedService Tells whether or not the service is set as unlimited for a reseller's customer
 * @param String $serviceName Service name for which new limit is verified
 * @return bool TRUE if new limit is valid, FALSE otherwise
 * @throws Zend_Exception
 */
function checkResellerLimit($newLimit, $assignedByReseller, $consumedByCustomers, $unlimitedService, $serviceName)
{
    $retVal = true;

    // We process only if the new limit value is not equal to 0 (unlimited)
    if ($newLimit == 0) {
        return $retVal;
    }

    // The service is limited for all customers
    if ($unlimitedService == false) {
        // If the new limit is lower than the already consomed item by customer
        if ($newLimit < $consumedByCustomers && $newLimit != -1) {
            set_page_message(
                tr(
                    "%s: The clients consumption (%s) for this reseller is greater than the new limit.",
                    '<b>' . ucfirst($serviceName) . '</b>', $consumedByCustomers),
                'error'
            );
            $retVal = false;
            // If the new limit is lower than the items already assigned by the reseller
        } elseif ($newLimit < $assignedByReseller && $newLimit != -1) {
            set_page_message(
                tr(
                    '%s: The total of items (%s) already assigned by the reseller is greater than the new limit.',
                    '<b>' . ucfirst($serviceName) . '</b>', $assignedByReseller
                ),
                'error'
            );
            $retVal = false;
            // If the new limit is -1 (disabled) and assigned items are already consumed by customer
        } elseif ($newLimit == -1 && $consumedByCustomers > 0) {
            set_page_message(
                tr(
                    "%s: You cannot disable a service already consumed by reseller's customers.",
                    '<b>' . ucfirst($serviceName) . '</b>'
                ),
                'error'
            );
            $retVal = false;
            // If the new limit is -1 (disabled) and the already assigned accounts/limits by reseller is greater 0
        } elseif ($newLimit == -1 && $assignedByReseller > 0) {
            set_page_message(
                tr(
                    "%s: You cannot disable a service already sold to reseller's customers.",
                    '<b>' . ucfirst($serviceName) . '</b>'
                ),
                'error'
            );
            $retVal = false;
        }
        // One or more reseller's customers have unlimited items
    } elseif ($newLimit != 0) {
        set_page_message(
            tr('%s: This reseller has customer(s) with unlimited items.', '<b>' . ucfirst($serviceName) . '</b>'),
            'error'
        );
        set_page_message(tr('If you want to limit the reseller, you must first limit its customers.'), 'error');
        $retVal = false;
    }


    return $retVal;
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
    global $resellerId;

    /** @noinspection PhpUndefinedFieldInspection */
    $tpl->form = $form;

    if (empty($_POST)) {
        $form->setDefaults(getFormData($resellerId));
        $form->setDefault('email', decode_idna(getFormData($resellerId)['email']));
    }

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

if (!isset($_GET['edit_id'])) {
    showBadRequestErrorPage();
}

global $resellerId;
$resellerId = intval($_GET['edit_id']);

$phpini = iMSCP_PHPini::getInstance();
$phpini->loadResellerPermissions($resellerId); // Load reseller PHP permissions

$form = getUserLoginDataForm(false, false)->addElements(getUserPersonalDataForm()->getElements());

if (!empty($_POST)) {
    updateResellerUser($form);
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'                             => 'shared/layouts/ui.tpl',
    'page'                               => 'admin/reseller_edit.phtml',
    'page_message'                       => 'layout',
    'ips_block'                          => 'page',
    'ip_block'                           => 'ips_block',
    'php_editor_disable_functions_block' => 'page',
    'php_editor_mail_function_block'     => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE' => tohtml(tr('Admin / Users / Edit Reseller')),
    'EDIT_ID'       => tourl($resellerId)
]);

generateNavigation($tpl);
generatePage($tpl, $form);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventsManager::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
