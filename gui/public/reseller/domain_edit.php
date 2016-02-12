<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP Team
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
 * Get mail data
 *
 * @param int $domainId Domain id
 * @param int $mailQuota Mail quota limit
 * @return array An array which contain in order sum of all mailbox quota, current quota limit, number of mailboxes)
 */
function reseller_getMailData($domainId, $mailQuota)
{
    static $mailData = null;

    if (null === $mailData) {
        $stmt = exec_query(
            '
                SELECT SUM(quota) AS quota, COUNT(mail_id) AS nb_mailboxes FROM mail_users
                WHERE domain_id = ? AND quota IS NOT NULL
            ',
            $domainId
        );
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        if ($mailQuota == 0) {
            $mailData = array(
                'quota_sum' => bytesHuman($row['quota']),
                'quota_limit' => tr('Unlimited'),
                'nb_mailboxes' => $row['nb_mailboxes']
            );
        } else {
            $mailData = array(
                'quota_sum' => bytesHuman($row['quota']),
                'quota_limit' => bytesHuman($mailQuota * 1048576),
                'nb_mailboxes' => $row['nb_mailboxes']
            );
        }
    }

    return $mailData;
}

/**
 * Return properties for the given reseller
 *
 * @param int $resellerId Reseller id
 * @return array Reseller properties
 */
function reseller_getResellerProps($resellerId)
{
    $stmt = exec_query(
        '
            SELECT
                reseller_id, current_sub_cnt, max_sub_cnt, current_als_cnt, max_als_cnt, current_mail_cnt, max_mail_cnt,
                current_ftp_cnt, max_ftp_cnt, current_sql_db_cnt, max_sql_db_cnt, current_sql_user_cnt, max_sql_user_cnt,
                current_disk_amnt, max_disk_amnt, current_traff_amnt, max_traff_amnt, software_allowed
            FROM
                reseller_props
            WHERE
                reseller_id = ?
        ',
        $resellerId
    );

    return $stmt->fetchRow();
}

/**
 * Return properties for the given domain
 *
 * @param int $domainId Domain id
 * @return array Array containing domain properties
 */
function reseller_getDomainProps($domainId)
{
    $stmt = exec_query(
        '
            SELECT
                admin_id, domain_id, domain_name, domain_expires, domain_status, domain_ip_id, domain_subd_limit,
                domain_alias_limit, domain_mailacc_limit, domain_ftpacc_limit, domain_sqld_limit,
                domain_sqlu_limit, domain_disk_limit, domain_disk_usage, domain_traffic_limit, domain_php,
                domain_cgi, domain_dns, domain_software_allowed, allowbackup, domain_external_mail,
                web_folder_protection, (mail_quota / 1048576) AS mail_quota
            FROM
                domain
            INNER JOIN
                admin ON(admin_id = domain_admin_id)
            WHERE
                domain_id = ?
        ',
        $domainId
    );
    $data = $stmt->fetchRow();

    // domain traffic
    $fdofmnth = mktime(0, 0, 0, date('m'), 1, date('Y'));
    $ldofmnth = mktime(1, 0, 0, date('m') + 1, 0, date('Y'));
    $stmt = exec_query(
        '
            SELECT IFNULL(SUM(dtraff_web) + SUM(dtraff_ftp) + SUM(dtraff_mail) + SUM(dtraff_pop), 0) AS traffic
            FROM domain_traffic  WHERE domain_id = ? AND dtraff_time > ? AND dtraff_time < ?
        ',
        array($domainId, $fdofmnth, $ldofmnth)
    );
    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
    $data['domainTraffic'] = $row['traffic'];
    return $data;
}

/**
 * Returns domain data
 *
 * @param int $domainId Domain unique identifier
 * @param bool $forUpdate Tell whether or not data are fetched for update
 * @return array Reference to array of data
 */
function &reseller_getData($domainId, $forUpdate = false)
{
    static $data = null;

    $cfg = iMSCP_Registry::get('config');

    if (null !== $data) {
        return $data;
    }

    $statusOk = "ok|disabled|ordered";

    // Checks for domain existence and status
    $stmt = exec_query(
        '
            SELECT
                t1.domain_status, COUNT(t3.subdomain_status) + COUNT(t4.alias_status) +
                COUNT(t5.subdomain_alias_status) AS status_not_ok
            FROM domain AS t1
            INNER JOIN admin AS t2 ON(admin_id = domain_admin_id)
            LEFT JOIN subdomain AS t3 ON (t1.domain_id = t3.domain_id AND t3.subdomain_status NOT RLIKE ?)
            LEFT JOIN domain_aliasses AS t4 ON (t1.domain_id = t4.domain_id AND t4.alias_status NOT RLIKE ?)
            LEFT JOIN subdomain_alias AS t5 ON (t5.alias_id = t4.alias_id AND t5.subdomain_alias_status NOT RLIKE ?)
            WHERE t1.domain_id = ?
            AND t2.created_by = ?
        ',
        array($statusOk, $statusOk, $statusOk, $domainId, $_SESSION['user_id'])
    );
    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

    if ($row['domain_status'] == '') {
        set_page_message(tr("The domain you are trying to edit doesn't exist."), 'error');
        redirectTo('users.php');
    }

    if (($row['domain_status'] != 'ok' && $row['domain_status'] != 'disabled') || $row['status_not_ok'] > 0) {
        set_page_message(tr("The domain or at least one of its entities has a different status than 'ok'."), 'warning');
        redirectTo('users.php');
    }

    if ($row['domain_status'] == 'disabled') {
        set_page_message(tr('The domain is currently deactivated. The modification of some of its properties will result by a complete or partial reactivation of it.'), 'warning');
    }

    $domainProps = reseller_getDomainProps($domainId);
    $resellerProps = reseller_getResellerProps($_SESSION['user_id']);

    list($subCount, $alsCount, $mailCount, $ftpCount, $sqlDbCount, $sqlUsersCount) = get_domain_running_props_cnt($domainId);

    $data['nbSubdomains'] = $subCount;
    $data['nbAliasses'] = $alsCount;
    $data['nbMailAccounts'] = $mailCount;
    $data['nbFtpAccounts'] = $ftpCount;
    $data['nbSqlDatabases'] = $sqlDbCount;
    $data['nbSqlUsers'] = $sqlUsersCount;
    $data = array_merge($data, $domainProps, $resellerProps);

    // Fallback values
    $data['fallback_domain_expires'] = $data['domain_expires'];
    $data['fallback_domain_ip_id'] = $data['domain_ip_id'];
    $data['fallback_domain_subd_limit'] = $data['domain_subd_limit'];
    $data['fallback_domain_alias_limit'] = $data['domain_alias_limit'];
    $data['fallback_domain_mailacc_limit'] = $data['domain_mailacc_limit'];
    $data['fallback_domain_ftpacc_limit'] = $data['domain_ftpacc_limit'];
    $data['fallback_domain_sqld_limit'] = $data['domain_sqld_limit'];
    $data['fallback_domain_sqlu_limit'] = $data['domain_sqlu_limit'];
    $data['fallback_domain_traffic_limit'] = $data['domain_traffic_limit'];
    $data['fallback_domain_disk_limit'] = $data['domain_disk_limit'];
    $data['fallback_domain_php'] = $data['domain_php'];
    $data['fallback_domain_cgi'] = $data['domain_cgi'];
    $data['fallback_domain_dns'] = $data['domain_dns'];
    $data['fallback_domain_software_allowed'] = $data['domain_software_allowed'];
    $data['fallback_allowbackup'] = $data['allowbackup'] = explode('|', $data['allowbackup']);
    $data['fallback_domain_external_mail'] = $data['domain_external_mail'];
    $data['fallback_web_folder_protection'] = $data['web_folder_protection'];
    $data['fallback_mail_quota'] = $data['mail_quota'];
    $data['domain_expires_ok'] = true;
    $data['domain_never_expires'] = ($data['domain_expires'] == 0) ? 'on' : 'off';

    $phpini = iMSCP_PHPini::getInstance();
    $phpini->loadResellerPermissions($_SESSION['user_id']); // Load reseller PHP permissions
    $phpini->loadClientPermissions($data['admin_id']); // Load client PHP permissions
    $phpini->loadDomainIni($data['admin_id'], $data['domain_id'], 'dmn'); // Load domain PHP configuration options

    if ($forUpdate) { // Post request
        foreach (
            array(
                'domain_subd_limit' => 'max_sub_cnt',
                'domain_alias_limit' => 'max_als_cnt',
                'domain_mailacc_limit' => 'max_mail_cnt',
                'mail_quota' => 'max_mail_cnt',
                'domain_ftpacc_limit' => 'max_ftp_cnt',
                'domain_sqld_limit' => 'max_sql_db_cnt',
                'domain_sqlu_limit' => 'max_sql_user_cnt',
                'domain_traffic_limit' => 'max_traff_amnt',
                'domain_disk_limit' => 'max_disk_amnt'
            ) as $customerLimit => $resellerMaxLimit
        ) {
            if (array_key_exists($customerLimit, $_POST) && $data[$resellerMaxLimit] != -1) {
                $data[$customerLimit] = clean_input($_POST[$customerLimit]);
            }
        }

        $data['domain_ip_id'] = isset($_POST['domain_ip_id']) ? clean_input($_POST['domain_ip_id']) : $data['domain_ip_id'];
        $data['domain_expires'] = isset($_POST['domain_expires']) ? clean_input($_POST['domain_expires']) : $data['domain_expires'];
        $data['domain_never_expires'] = isset($_POST['domain_never_expires']) ? clean_input($_POST['domain_never_expires']) : 'off';
        $data['domain_php'] = isset($_POST['domain_php']) ? clean_input($_POST['domain_php']) : $data['domain_php'];
        $data['domain_cgi'] = isset($_POST['domain_cgi']) ? clean_input($_POST['domain_cgi']) : $data['domain_cgi'];
        $data['domain_dns'] = isset($_POST['domain_dns']) ? clean_input($_POST['domain_dns']) : $data['domain_dns'];

        if ($data['software_allowed'] === 'yes') {
            $data['domain_software_allowed'] = isset($_POST['domain_software_allowed']) ? clean_input($_POST['domain_software_allowed']) : $data['domain_software_allowed'];
        } else {
            $data['domain_software_allowed'] = 'no';
        }

        if ($cfg['BACKUP_DOMAINS'] === 'yes') {
            $data['allowbackup'] = isset($_POST['allowbackup']) && is_array($_POST['allowbackup']) ? array_intersect($_POST['allowbackup'], array('dmn', 'sql', 'mail')) : array();
        } else {
            $data['allowbackup'] = array();
        }

        $data['domain_external_mail'] = isset($_POST['domain_external_mail']) ? clean_input($_POST['domain_external_mail']) : $data['domain_external_mail'];
        $data['web_folder_protection'] = isset($_POST['web_folder_protection']) ? clean_input($_POST['web_folder_protection']) : $data['web_folder_protection'];
    }


    return $data;
} // end reseller_getData()

/**
 * Generate edit form
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array &$data Domain related data
 * @return void
 */
function reseller_generateForm($tpl, &$data)
{
    _reseller_generateLimitsForm($tpl, $data);
    _reseller_generateFeaturesForm($tpl, $data);
}

/**
 * Generates domain limits form
 *
 * Note: Only shows the limits on which the domain reseller has permissions.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array $data Domain data
 * @return void
 */
function _reseller_generateLimitsForm($tpl, &$data)
{
    $tpl->assign(array(
        'TR_DOMAIN_LIMITS' => tr('Domain Limit'),
        'TR_LIMIT_VALUE' => tr('Limit value'),
        'TR_CUSTOMER_CONSUMPTION' => tr('Customer consumption'),
        'TR_RESELLER_CONSUMPTION' => isset($_SESSION['logged_from']) ? tr('Reseller consumption') : tr('Your consumption')
    ));

    // Subdomains limit
    if ($data['max_sub_cnt'] == -1) { // Reseller has no permissions on this service
        $tpl->assign('SUBDOMAIN_LIMIT_BLOCK', '');
    } else {
        $tpl->assign(array(
            'TR_SUBDOMAINS_LIMIT' => tr('Subdomain limit') . '<br /><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
            'SUBDOMAIN_LIMIT' => tohtml($data['domain_subd_limit']),
            'TR_CUSTOMER_SUBDOMAINS_COMSUPTION' => $data['fallback_domain_subd_limit'] != -1 ? tohtml($data['nbSubdomains']) . ' / ' . ($data['fallback_domain_subd_limit'] != 0 ? tohtml($data['fallback_domain_subd_limit']) : tr('Unlimited')) : tr('Disabled'),
            'TR_RESELLER_SUBDOMAINS_COMSUPTION' => tohtml($data['current_sub_cnt']) . ' / ' . ($data['max_sub_cnt'] != 0 ? tohtml($data['max_sub_cnt']) : tr('Unlimited'))
        ));
    }

    // Domain aliases limit
    if ($data['max_als_cnt'] == -1) { // Reseller has no permissions on this service
        $tpl->assign('DOMAIN_ALIASES_LIMIT_BLOCK', '');
    } else {
        $tpl->assign(array(
            'TR_ALIASSES_LIMIT' => tr('Domain alias limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
            'DOMAIN_ALIASSES_LIMIT' => tohtml($data['domain_alias_limit']),
            'TR_CUSTOMER_DOMAIN_ALIASSES_COMSUPTION' => $data['fallback_domain_alias_limit'] != -1 ? tohtml($data['nbAliasses']) . ' / ' . ($data['fallback_domain_alias_limit'] != 0 ? tohtml($data['fallback_domain_alias_limit']) : tr('Unlimited')) : tr('Disabled'),
            'TR_RESELLER_DOMAIN_ALIASSES_COMSUPTION' => tohtml($data['current_als_cnt']) . ' / ' . ($data['max_als_cnt'] != 0 ? tohtml($data['max_als_cnt']) : tr('Unlimited'))
        ));
    }

    // Mail accounts limit
    if ($data['max_mail_cnt'] == -1) { // Reseller has no permissions on this service
        $tpl->assign('MAIL_ACCOUNTS_LIMIT_BLOCK', '');
    } else {
        $mailData = reseller_getMailData($data['domain_id'], $data['fallback_mail_quota']);

        $tpl->assign(array(
            'TR_MAIL_ACCOUNTS_LIMIT' => tr('Email account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
            'MAIL_ACCOUNTS_LIMIT' => tohtml($data['domain_mailacc_limit']),
            'TR_CUSTOMER_MAIL_ACCOUNTS_COMSUPTION' => $data['fallback_domain_mailacc_limit'] != -1 ? tohtml($data['nbMailAccounts']) . ' / ' . ($data['fallback_domain_mailacc_limit'] != 0 ? tohtml($data['fallback_domain_mailacc_limit']) : tr('Unlimited')) : tr('Disabled'),
            'TR_RESELLER_MAIL_ACCOUNTS_COMSUPTION' => tohtml($data['current_mail_cnt']) . ' / ' . ($data['max_mail_cnt'] != 0 ? tohtml($data['max_mail_cnt']) : tr('Unlimited')),
            'TR_MAIL_QUOTA' => tr('Email quota [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
            'MAIL_QUOTA' => $data['mail_quota'] != 0 ? tohtml(floor($data['mail_quota'])) : '0',
            'TR_CUSTOMER_MAIL_QUOTA_COMSUPTION' => $mailData['quota_sum'] . ' / ' . $mailData['quota_limit'],
            'TR_NO_AVAILABLE' => tr('No available')
        ));
    }

    // Ftp accounts limit
    if ($data['max_ftp_cnt'] == -1) { // Reseller has no permissions on this service
        $tpl->assign('FTP_ACCOUNTS_LIMIT_BLOCK', '');
    } else {
        $tpl->assign(array(
            'TR_FTP_ACCOUNTS_LIMIT' => tr('FTP account limit') . '<br /><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
            'FTP_ACCOUNTS_LIMIT' => tohtml($data['domain_ftpacc_limit']),
            'TR_CUSTOMER_FTP_ACCOUNTS_COMSUPTION' => $data['fallback_domain_ftpacc_limit'] != -1 ? tohtml($data['nbFtpAccounts']) . ' / ' . ($data['fallback_domain_ftpacc_limit'] != 0 ? tohtml($data['fallback_domain_ftpacc_limit']) : tr('Unlimited')) : tr('Disabled'),
            'TR_RESELLER_FTP_ACCOUNTS_COMSUPTION' => tohtml($data['current_ftp_cnt']) . ' / ' . ($data['max_ftp_cnt'] != 0 ? tohtml($data['max_ftp_cnt']) : tr('Unlimited'))
        ));
    }

    // SQL Database - Sql Users limits
    if ($data['max_sql_db_cnt'] == -1 || $data['max_sql_user_cnt'] == -1) { // Reseller has no permissions on this service
        $tpl->assign('SQL_DB_AND_USERS_LIMIT_BLOCK', '');
    } else {
        $tpl->assign(array(
            'TR_SQL_DATABASES_LIMIT' => tr('SQL database limit') . '<br /><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
            'SQL_DATABASES_LIMIT' => tohtml($data['domain_sqld_limit']),
            'TR_CUSTOMER_SQL_DATABASES_COMSUPTION' => $data['fallback_domain_sqld_limit'] != -1 ? tohtml($data['nbSqlDatabases']) . ' / ' . ($data['fallback_domain_sqld_limit'] != 0 ? tohtml($data['fallback_domain_sqld_limit']) : tr('Unlimited')) : tr('Disabled'),
            'TR_RESELLER_SQL_DATABASES_COMSUPTION' => tohtml($data['current_sql_db_cnt']) . ' / ' . ($data['max_sql_db_cnt'] != 0 ? tohtml($data['max_sql_db_cnt']) : tr('Unlimited')),
            'TR_SQL_USERS_LIMIT' => tr('SQL user limit') . '<br /><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
            'SQL_USERS_LIMIT' => tohtml($data['domain_sqlu_limit']),
            'TR_CUSTOMER_SQL_USERS_COMSUPTION' => $data['fallback_domain_sqlu_limit'] != -1 ? tohtml($data['nbSqlUsers']) . ' / ' . ($data['fallback_domain_sqlu_limit'] != 0 ? tohtml($data['fallback_domain_sqlu_limit']) : tr('Unlimited')) : tr('Disabled'),
            'TR_RESELLER_SQL_USERS_COMSUPTION' => tohtml($data['current_sql_user_cnt']) . ' / ' . ($data['max_sql_user_cnt'] != 0 ? tohtml($data['max_sql_user_cnt']) : tr('Unlimited'))
        ));
    }

    // Traffic limit
    $tpl->assign(array(
        'TR_TRAFFIC_LIMIT' => tr('Monthly traffic limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
        'TRAFFIC_LIMIT' => tohtml($data['domain_traffic_limit']),
        'TR_CUSTOMER_TRAFFIC_COMSUPTION' => tohtml(bytesHuman($data['domainTraffic'], 'MiB')) . ' / ' . ($data['fallback_domain_traffic_limit'] != 0 ? tohtml(bytesHuman($data['fallback_domain_traffic_limit'] * 1048576)) : tr('Unlimited')),
        'TR_RESELLER_TRAFFIC_COMSUPTION' => tohtml(bytesHuman($data['current_traff_amnt'] * 1048576)) . ' / ' . ($data['max_traff_amnt'] != 0 ? tohtml(bytesHuman($data['max_traff_amnt'] * 1048576)) : tr('Unlimited')),

        // Disk space limit
        'TR_DISK_LIMIT' => tr('Disk space limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</span>',
        'DISK_LIMIT' => tohtml($data['domain_disk_limit']),
        'TR_CUSTOMER_DISKPACE_COMSUPTION' => tohtml(bytesHuman($data['domain_disk_usage'], 'MiB')) . ' / ' . ($data['fallback_domain_disk_limit'] != 0 ? tohtml(bytesHuman($data['fallback_domain_disk_limit'] * 1048576)) : tr('Unlimited')),
        'TR_RESELLER_DISKPACE_COMSUPTION' => tohtml(bytesHuman($data['current_disk_amnt'] * 1048576)) . ' / ' . ($data['max_disk_amnt'] != 0 ? tohtml(bytesHuman($data['max_disk_amnt'] * 1048576)) : tr('Unlimited'))
    ));
} // end _reseller_generateLimitsForm()

/**
 * Generates features form
 *
 * Note: For now most block for the features are always show. That will change when
 * admin will be able to disable them for a specific reseller.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array $data Domain data
 * @return void
 */
function _reseller_generateFeaturesForm($tpl, &$data)
{
    $cfg = iMSCP_Registry::get('config');

    $tpl->assign(array(
        'TR_FEATURES' => tr('Features'),

        // PHP support
        'TR_PHP' => tr('PHP'),
        'PHP_YES' => $data['domain_php'] == 'yes' ? ' checked' : '',
        'PHP_NO' => $data['domain_php'] != 'yes' ? ' checked' : ''
    ));

    $phpini = iMSCP_PHPini::getInstance();

    // PHP editor - begin
    if (!$phpini->resellerHasPermission('phpiniSystem')) {
        $tpl->assign('PHP_EDITOR_BLOCK', '');
    } else {
        $tpl->assign(array(
            'TR_SETTINGS' => tr('PHP Settings'),
            'TR_PHP_EDITOR' => tr('PHP Editor'),
            'TR_PHP_EDITOR_SETTINGS' => tr('PHP Settings'),
            'TR_PERMISSIONS' => tr('PHP Permissions'),
            'TR_DIRECTIVES_VALUES' => tr('PHP directives values'),
            'TR_FIELDS_OK' => tr('All fields are valid.'),
            'TR_MIB' => tr('MiB'),
            'TR_SEC' => tr('Sec.'),
            'PHP_EDITOR_YES' => $phpini->clientHasPermission('phpiniSystem') ? ' checked' : '',
            'PHP_EDITOR_NO' => $phpini->clientHasPermission('phpiniSystem') ? '' : ' checked'
        ));

        $permissionsBlock = false;

        if (!$phpini->resellerHasPermission('phpiniAllowUrlFopen')) {
            $tpl->assign('PHP_EDITOR_ALLOW_URL_FOPEN_BLOCK', '');
        } else {
            $tpl->assign(array(
                'TR_CAN_EDIT_ALLOW_URL_FOPEN' => tr('Can edit the PHP %s configuration option', '<b>allow_url_fopen</b>'),
                'ALLOW_URL_FOPEN_YES' => $phpini->clientHasPermission('phpiniAllowUrlFopen') ? ' checked' : '',
                'ALLOW_URL_FOPEN_NO' => $phpini->clientHasPermission('phpiniAllowUrlFopen') ? '' : ' checked'
            ));
            $permissionsBlock = true;
        }

        if (!$phpini->resellerHasPermission('phpiniDisplayErrors')) {
            $tpl->assign('PHP_EDITOR_DISPLAY_ERRORS_BLOCK', '');
        } else {
            $tpl->assign(array(
                'TR_CAN_EDIT_DISPLAY_ERRORS' => tr('Can edit the PHP %s configuration option', '<b>display_errors</b>'),
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
                    'TR_CAN_EDIT_DISABLE_FUNCTIONS' => tr('Can edit the PHP %s configuration option', '<b>disable_functions</b>'),
                    'DISABLE_FUNCTIONS_YES' => $phpini->getClientPermission('phpiniDisableFunctions') == 'yes' ? ' checked' : '',
                    'DISABLE_FUNCTIONS_NO' => $phpini->getClientPermission('phpiniDisableFunctions') == 'no' ? ' checked' : '',
                    'TR_ONLY_EXEC' => tr('Only exec'),
                    'DISABLE_FUNCTIONS_EXEC' => ($phpini->getClientPermission('phpiniDisableFunctions') == 'exec') ? ' checked' : ''
                ));
            } else {
                $tpl->assign('PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK', '');
            }

            if ($phpini->resellerHasPermission('phpiniMailFunction')) {
                $tpl->assign(array(
                    'TR_CAN_USE_MAIL_FUNCTION' => tr('Can use the PHP %s function', '<b>mail</b>'),
                    'MAIL_FUNCTION_YES' => $phpini->clientHasPermission('phpiniMailFunction') == 'yes' ? ' checked' : '',
                    'MAIL_FUNCTION_NO' => $phpini->clientHasPermission('phpiniMailFunction') == 'no' ? '' : ' checked'
                ));
            } else {
                $tpl->assign('PHP_EDITOR_MAIL_FUNCTION_BLOCK', '');
            }

            $permissionsBlock = true;
        }

        if (!$permissionsBlock) {
            $tpl->assign('PHP_EDITOR_PERMISSIONS_BLOCK', '');
        }

        $tpl->assign(array(
            'TR_POST_MAX_SIZE' => tr('PHP %s configuration option', '<b>post_max_size</b>'),
            'POST_MAX_SIZE' => tohtml($phpini->getDomainIni('phpiniPostMaxSize'), 'htmlAttr'),
            'TR_UPLOAD_MAX_FILEZISE' => tr('PHP %s configuration option', '<b>upload_max_filesize</b>'),
            'UPLOAD_MAX_FILESIZE' => tohtml($phpini->getDomainIni('phpiniUploadMaxFileSize'), 'htmlAttr'),
            'TR_MAX_EXECUTION_TIME' => tr('PHP %s configuration option', '<b>max_execution_time</b>'),
            'MAX_EXECUTION_TIME' => tohtml($phpini->getDomainIni('phpiniMaxExecutionTime'), 'htmlAttr'),
            'TR_MAX_INPUT_TIME' => tr('PHP %s configuration option', '<b>max_input_time</b>'),
            'MAX_INPUT_TIME' => tohtml($phpini->getDomainIni('phpiniMaxInputTime'), 'htmlAttr'),
            'TR_MEMORY_LIMIT' => tr('PHP %s configuration option', '<b>memory_limit</b>'),
            'MEMORY_LIMIT' => tohtml($phpini->getDomainIni('phpiniMemoryLimit'), 'htmlAttr'),
            'POST_MAX_SIZE_LIMIT' => tohtml($phpini->getResellerPermission('phpiniPostMaxSize'), 'htmlAttr'),
            'UPLOAD_MAX_FILESIZE_LIMIT' => tohtml($phpini->getResellerPermission('phpiniUploadMaxFileSize'), 'htmlAttr'),
            'MAX_EXECUTION_TIME_LIMIT' => tohtml($phpini->getResellerPermission('phpiniMaxExecutionTime'), 'htmlAttr'),
            'MAX_INPUT_TIME_LIMIT' => tohtml($phpini->getResellerPermission('phpiniMaxInputTime'), 'htmlAttr'),
            'MEMORY_LIMIT_LIMIT' => tohtml($phpini->getResellerPermission('phpiniMemoryLimit'), 'htmlAttr')
        ));
    }

    iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
        /** @var iMSCP_Events_Event $e */
        $translations = $e->getParam('translations');
        $translations['core']['close'] = tr('Close');
        $translations['core']['fields_ok'] = tr('All fields are valid.');
        $translations['core']['out_of_range_value_error'] = tr('Value for the PHP %%s directive must be in range %%d to %%d.');
        $translations['core']['lower_value_expected_error'] = tr('%%s must be lower than %%s.');
        $translations['core']['error_field_stack'] = iMSCP_Registry::isRegistered('errFieldsStack') ? iMSCP_Registry::get('errFieldsStack') : array();
    });

    // PHP editor - end

    // CGI support
    $tpl->assign(array(
        'TR_CGI' => tr('CGI'),
        'CGI_YES' => $data['domain_cgi'] == 'yes' ? ' checked' : '',
        'CGI_NO' => $data['domain_cgi'] != 'yes' ? ' checked' : ''
    ));

    // Custom DNS records
    if (resellerHasFeature('custom_dns_records')) {
        $tpl->assign(array(
            'TR_DNS' => tr('Custom DNS records'),
            'DNS_YES' => $data['domain_dns'] == 'yes' ? ' checked' : '',
            'DNS_NO' => $data['domain_dns'] != 'yes' ? ' checked' : ''
        ));
    } else {
        $tpl->assign('CUSTOM_DNS_RECORDS_FEATURE', '');
    }

    // APS support
    if ($data['software_allowed'] == 'no') {
        $tpl->assign('APS_BLOCK', '');
    } else {
        $tpl->assign(array(
            'TR_APS' => tr('Software installer'),
            'APS_YES' => $data['domain_software_allowed'] == 'yes' ? ' checked' : '',
            'APS_NO' => $data['domain_software_allowed'] != 'yes' ? ' checked' : ''
        ));
    }

    // External mail support
    if ($data['max_mail_cnt'] == '-1') {
        $tpl->assign('EXT_MAIL_BLOCK', '');
    } else {
        $tpl->assign(array(
            'TR_EXTMAIL' => tr('External mail server'),
            'EXTMAIL_YES' => $data['domain_external_mail'] == 'yes' ? ' checked' : '',
            'EXTMAIL_NO' => $data['domain_external_mail'] != 'yes' ? ' checked' : ''
        ));
    }

    if ($cfg['BACKUP_DOMAINS'] == 'yes') {
        // Backup support
        $tpl->assign(array(
            'TR_BACKUP' => tr('Backup'),
            'TR_BACKUP_DOMAIN' => tr('Domain'),
            'BACKUP_DOMAIN' => in_array('dmn', $data['allowbackup']) ? ' checked' : '',
            'TR_BACKUP_SQL' => tr('SQL'),
            'BACKUP_SQL' => in_array('sql', $data['allowbackup']) ? ' checked' : '',
            'TR_BACKUP_MAIL' => tr('Mail'),
            'BACKUP_MAIL' => in_array('mail', $data['allowbackup']) ? ' checked' : ''
        ));
    } else {
        $tpl->assign('BACKUP_BLOCK', '');
    }


    $tpl->assign(array(
        'TR_WEB_FOLDER_PROTECTION' => tr('Web folder protection'),
        'TR_WEB_FOLDER_PROTECTION_HELP' => tr('If set to `yes`, Web folders will be protected against deletion.'),
        'WEB_FOLDER_PROTECTION_YES' => $data['web_folder_protection'] == 'yes' ? ' checked' : '',
        'WEB_FOLDER_PROTECTION_NO' => $data['web_folder_protection'] != 'yes' ? ' checked' : '',
        'TR_YES' => tr('Yes'),
        'TR_NO' => tr('No')
    ));
}

/**
 * Check and updates domain data
 *
 * @throws iMSCP_Exception_Database
 * @param int $domainId Domain unique identifier
 * @return bool TRUE on success, FALSE otherwise
 */
function reseller_checkAndUpdateData($domainId)
{
    $db = iMSCP_Database::getInstance();
    $errFieldsStack = array();

    try {
        // Getting domain data
        $data =& reseller_getData($domainId, true);

        // Check for expires date
        if ($data['domain_never_expires'] == 'off') {
            if (!preg_match('%^\d{2}/\d{2}/\d{4}$%', $data['domain_expires'])
                || ($timestamp = strtotime($data['domain_expires'])) === false
            ) {
                $data['domain_expires_ok'] = false;
                set_page_message(tr('Wrong syntax for new expire date.'), 'error');
                $errFieldsStack[] = 'domain_expires';
            } elseif ($timestamp != 0 && $timestamp <= time()) {
                $data['domain_expires'] = $timestamp;
                set_page_message(tr('You cannot set expire date in past.'), 'error');
                $errFieldsStack[] = 'domain_expires';
            } else {
                $data['domain_expires'] = $timestamp;
            }
        } else {
            $data['domain_expires'] = 0;
        }

        // Check for the subdomains limit
        if ($data['fallback_domain_subd_limit'] != -1) {
            if (!imscp_limit_check($data['domain_subd_limit'])) {
                set_page_message(tr('Wrong syntax for the %s limit.', tr('subdomains')), 'error');
                $errFieldsStack[] = 'domain_subd_limit';
            } elseif (!_reseller_isValidServiceLimit(
                $data['domain_subd_limit'], $data['nbSubdomains'], $data["fallback_domain_subd_limit"],
                $data['current_sub_cnt'], $data['max_sub_cnt'],
                $data['nbSubdomains'] > 1 ? tr('subdomains') : tr('subdomain'))
            ) {
                $errFieldsStack[] = 'domain_subd_limit';
            }
        }

        // Check for the domain aliases limit
        if ($data['fallback_domain_alias_limit'] != -1) {
            if (!imscp_limit_check($data['domain_alias_limit'])) {
                set_page_message(tr('Wrong syntax for the %s limit.', tr('domain aliases')), 'error');
                $errFieldsStack[] = 'domain_alias_limit';
            } elseif (!_reseller_isValidServiceLimit(
                $data['domain_alias_limit'], $data['nbAliasses'], $data["fallback_domain_alias_limit"],
                $data['current_als_cnt'], $data['max_als_cnt'],
                $data['nbAliasses'] > 1 ? tr('domain aliases') : tr('domain alias'))
            ) {
                $errFieldsStack[] = 'domain_alias_limit';
            }
        }

        // Check for the mail accounts limit
        if ($data['fallback_domain_mailacc_limit'] != -1) {
            if (!imscp_limit_check($data['domain_mailacc_limit'])) {
                set_page_message(tr('Wrong syntax for the %s limit.', tr('email accounts')), 'error');
                $errFieldsStack[] = 'domain_mailacc_limit';
            } elseif (!_reseller_isValidServiceLimit(
                $data['domain_mailacc_limit'], $data['nbMailAccounts'], $data["fallback_domain_mailacc_limit"],
                $data['current_mail_cnt'], $data['max_mail_cnt'],
                $data["nbMailAccounts"] > 1 ? tr('email accounts') : tr('email account'))
            ) {
                $errFieldsStack[] = 'domain_mailacc_limit';
            }
        }

        // Check for the Ftp accounts limit
        if ($data['fallback_domain_ftpacc_limit'] != -1) {
            if (!imscp_limit_check($data['domain_ftpacc_limit'])) {
                set_page_message(tr('Wrong syntax for the %s limit.', tr('Ftp accounts')), 'error');
                $errFieldsStack[] = 'domain_ftpacc_limit';
            } elseif (!_reseller_isValidServiceLimit(
                $data['domain_ftpacc_limit'], $data['nbFtpAccounts'], $data["fallback_domain_ftpacc_limit"],
                $data['current_ftp_cnt'], $data['max_ftp_cnt'],
                $data['nbFtpAccounts'] > 1 ? tr('Ftp accounts') : tr('Ftp account'))
            ) {
                $errFieldsStack[] = 'domain_ftpacc_limit';
            }
        }

        // Check for the Sql databases limit
        if ($data['fallback_domain_sqld_limit'] != -1) {
            if (!imscp_limit_check($data['domain_sqld_limit'])) {
                set_page_message(tr('Wrong syntax for the %s limit.', tr('SQL databases')), 'error');
                $errFieldsStack[] = 'domain_sqld_limit';
            } elseif (!_reseller_isValidServiceLimit(
                $data['domain_sqld_limit'], $data['nbSqlDatabases'], $data["fallback_domain_sqld_limit"],
                $data['current_sql_db_cnt'], $data['max_sql_db_cnt'],
                $data['nbSqlDatabases'] > 1 ? tr('SQL databases') : tr('SQL database'))
            ) {
                $errFieldsStack[] = 'domain_sqld_limit';
            } elseif ($data['domain_sqld_limit'] != -1 && $data['domain_sqlu_limit'] == -1) {
                set_page_message(tr('SQL user limit is disabled.'), 'error');
                $errFieldsStack[] = 'domain_sqld_limit';
                $errFieldsStack[] = 'domain_sqlu_limit';
            }
        }

        // Check for the Sql users limit
        if ($data['fallback_domain_sqlu_limit'] != -1) {
            if (!imscp_limit_check($data['domain_sqlu_limit'])) {
                set_page_message(tr('Wrong syntax for the %s limit.', tr('SQL users')), 'error');
                $errFieldsStack[] = 'domain_sqlu_limit';
            } elseif (!_reseller_isValidServiceLimit(
                $data['domain_sqlu_limit'], $data['nbSqlUsers'], $data["fallback_domain_sqlu_limit"],
                $data['current_sql_user_cnt'], $data['max_sql_user_cnt'],
                $data['nbSqlUsers'] > 1 ? tr('SQL users') : tr('SQL user'))
            ) {
                $errFieldsStack[] = 'domain_sqlu_limit';
            } elseif ($data['domain_sqlu_limit'] != -1 && $data['domain_sqld_limit'] == -1) {
                set_page_message(tr('SQL database limit is disabled.'), 'error');
                $errFieldsStack[] = 'domain_sqlu_limit';
                $errFieldsStack[] = 'domain_sqld_limit';
            }
        }

        // Check for the monthly traffic limit
        if (!imscp_limit_check($data['domain_traffic_limit'], null)) {
            set_page_message(tr('Wrong syntax for the %s limit.', tr('traffic')), 'error');
            $errFieldsStack[] = 'domain_traffic_limit';
        } elseif (!_reseller_isValidServiceLimit(
            $data['domain_traffic_limit'], $data['domainTraffic'] / 1048576, $data["fallback_domain_traffic_limit"],
            $data['current_traff_amnt'], $data['max_traff_amnt'], tr('traffic'))
        ) {
            $errFieldsStack[] = 'domain_traffic_limit';
        }

        // Check for the disk space limit
        if (!imscp_limit_check($data['domain_disk_limit'], null)) {
            set_page_message(tr('Wrong syntax for the %s limit.', tr('disk space')), 'error');
            $errFieldsStack[] = 'domain_disk_limit';
        } elseif (!_reseller_isValidServiceLimit(
            $data['domain_disk_limit'], $data['domain_disk_usage'] / 1048576, $data["fallback_domain_disk_limit"],
            $data['current_disk_amnt'], $data['max_disk_amnt'], tr('disk space'))
        ) {
            $errFieldsStack[] = 'domain_disk_limit';
        }

        // Check for mail quota
        if ($data['fallback_domain_mailacc_limit'] != -1) {
            if (!imscp_limit_check($data['mail_quota'], null)) {
                set_page_message(tr('Wrong syntax for the mail quota value.'), 'error');
                $errFieldsStack[] = 'mail_quota';
            } elseif ($data['domain_disk_limit'] != 0 && $data['mail_quota'] > $data['domain_disk_limit']) {
                set_page_message(tr('Email quota cannot be bigger than disk space limit.'), 'error');
                $errFieldsStack[] = 'mail_quota';
            } elseif ($data['domain_disk_limit'] != 0 && $data['mail_quota'] == 0) {
                set_page_message(tr('Email quota cannot be unlimited. Max value is %d MiB.', $data['domain_disk_limit']), 'error');
                $errFieldsStack[] = 'mail_quota';
            } else {
                $mailData = reseller_getMailData($data['domain_id'], $data['fallback_mail_quota']);

                if ($data['mail_quota'] != 0 && $data['mail_quota'] < $mailData['nb_mailboxes']) {
                    set_page_message(tr('Email quota cannot be lower than %d. Each mailbox should have a least 1 MiB quota.', $mailData['nb_mailboxes']), 'error');
                    $errFieldsStack[] = 'mail_quota';
                }
            }
        } else {
            $data['mail_quota'] = 0;
        }

        // Check for PHP support
        $data['domain_php'] = in_array($data['domain_php'], array('no', 'yes')) ? $data['domain_php'] : $data['fallback_domain_php'];

        // PHP editor
        $phpini = iMSCP_PHPini::getInstance();

        // Needed to track changes
        $phpiniClientPerms = $phpini->getClientPermission();
        $phpiniDomainConf = $phpini->getDomainIni();

        if (isset($_POST['php_ini_system']) && $data['domain_php'] == 'yes' && $phpini->resellerHasPermission('phpiniSystem')) {
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

                if (isset($_POST['upload_max_filezize'])) {
                    $phpini->setDomainIni('phpiniUploadMaxFileSize', clean_input($_POST['upload_max_filezize']));
                }

                if (isset($_POST['max_execution_time'])) {
                    $phpini->setDomainIni('phpiniMaxExecutionTime', clean_input($_POST['max_execution_time']));
                }

                if (isset($_POST['max_input_time'])) {
                    $phpini->setDomainIni('phpiniMaxInputTime', clean_input($_POST['max_input_time']));
                }
            } else {
                $phpini->loadClientPermissions(); // Reset client PHP permissions
                $phpini->loadDomainIni(); // Reset domain PHP configuration options
            }
        } else {
            $phpini->loadClientPermissions(); // Reset client PHP permissions
            $phpini->loadDomainIni(); // Reset domain PHP configuration options
        }

        // Check for CGI support
        $data['domain_cgi'] = in_array($data['domain_cgi'], array('no', 'yes')) ? $data['domain_cgi'] : $data['fallback_domain_cgi'];

        // Check for custom DNS records support
        $data['domain_dns'] = in_array($data['domain_dns'], array('no', 'yes')) ? $data['domain_dns'] : $data['fallback_domain_dns'];

        // Check for APS support
        $data['domain_software_allowed'] = in_array($data['domain_software_allowed'], array('no', 'yes')) ? $data['domain_software_allowed'] : $data['fallback_domain_software_allowed'];

        // Check for External mail server support
        $data['domain_external_mail'] = in_array($data['domain_external_mail'], array('no', 'yes')) ? $data['domain_external_mail'] : $data['fallback_domain_external_mail'];

        // Check for backup support
        $data['allowbackup'] = is_array($data['allowbackup']) ? (array_intersect($data['allowbackup'], array('dmn', 'sql', 'mail'))) : $data['fallback_allowbackup'];

        // Check for Web folder protection support
        $data['web_folder_protection'] = in_array($data['web_folder_protection'], array('no', 'yes')) ? $data['web_folder_protection'] : $data['fallback_web_folder_protection'];

        if (empty($errFieldsStack) && !Zend_Session::namespaceIsset('pageMessages')) { // Update process begin here
            $oldValues = array();
            $newValues = array();

            foreach ($data as $property => $value) {
                if (strpos($property, 'fallback_') !== false) {
                    $property = substr($property, 9);
                    $oldValues[$property] = $value;
                    $newValues[$property] = $data[$property];
                }
            }

            $needDaemonRequest = false;

            if ($newValues == $oldValues
                && $phpiniClientPerms == $phpini->getClientPermission()
                && $phpiniDomainConf == $phpini->getDomainIni()
            ) {
                set_page_message(tr('Nothing has been changed.'), 'info');
                return true;
            }

            iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditDomain, array(
                'domainId' => $domainId
            ));

            $db->beginTransaction();

            if ($phpiniClientPerms != $phpini->getClientPermission() || $phpiniDomainConf != $phpini->getDomainIni()) {
                $phpini->updateDomainConfigOptions($data['admin_id']);
                $needDaemonRequest = true;
            }

            // PHP or CGI was either enabled or disabled or PHP Settings were changed, web folder protection
            // properties have been updated, or domain IP was changed, so we must update the vhosts files
            // of all domain entities (dmn, sub, als, alssub)
            if ($needDaemonRequest || $data['domain_php'] != $data['fallback_domain_php'] ||
                $data['domain_cgi'] != $data['fallback_domain_cgi'] ||
                $data['web_folder_protection'] != $data['fallback_web_folder_protection'] ||
                $data['domain_ip_id'] != $data['fallback_domain_ip_id']
            ) {
                if ($data['domain_alias_limit'] != '-1') {
                    exec_query(
                        'UPDATE domain_aliasses SET alias_status = ? WHERE domain_id = ? AND alias_status <> ?',
                        array('tochange', $domainId, 'ordered')
                    );
                }

                $needDaemonRequest = true;
            }

            if ($data['domain_dns'] != $data['fallback_domain_dns'] && $data['domain_dns'] == 'no') {
                // Support for custom DNS records is now disabled - We must delete all custom DNS entries
                // (except those that are protected), and update the DNS zone file
                exec_query('DELETE FROM domain_dns WHERE domain_id = ? AND owned_by = ?', array(
                    $domainId, 'custom_dns_feature'
                ));
                $needDaemonRequest = true;
            }

            // Update domain properties
            exec_query(
                '
                    UPDATE
                        domain
                    SET
                        domain_expires = ?, domain_last_modified = ?, domain_mailacc_limit = ?, domain_ftpacc_limit = ?,
                        domain_traffic_limit = ?, domain_sqld_limit = ?, domain_sqlu_limit = ?, domain_status = ?,
                        domain_alias_limit = ?, domain_subd_limit = ?, domain_ip_id = ?, domain_disk_limit = ?,
                        domain_php = ?, domain_cgi = ?, allowbackup = ?, domain_dns = ?,  domain_software_allowed = ?,
                        phpini_perm_system = ?, phpini_perm_allow_url_fopen = ?, phpini_perm_display_errors = ?,
                        phpini_perm_disable_functions = ?, domain_external_mail = ?, web_folder_protection = ?,
                        mail_quota = ?
                    WHERE
                        domain_id = ?
                ',
                array(
                    $data['domain_expires'], time(), $data['domain_mailacc_limit'], $data['domain_ftpacc_limit'],
                    $data['domain_traffic_limit'], $data['domain_sqld_limit'], $data['domain_sqlu_limit'],
                    $needDaemonRequest ? 'tochange' : 'ok', $data['domain_alias_limit'], $data['domain_subd_limit'],
                    $data['domain_ip_id'], $data['domain_disk_limit'], $data['domain_php'], $data['domain_cgi'],
                    implode('|', $data['allowbackup']), $data['domain_dns'], $data['domain_software_allowed'],
                    $phpini->getClientPermission('phpiniSystem'),
                    $phpini->getClientPermission('phpiniAllowUrlFopen'),
                    $phpini->getClientPermission('phpiniDisplayErrors'),
                    $phpini->getClientPermission('phpiniDisableFunctions'),
                    $data['domain_external_mail'], $data['web_folder_protection'], $data['mail_quota'] * 1048576,
                    $domainId
                )
            );
            //print 'ouch'; exit;

            // Sync mailboxes quota if needed
            if ($data['fallback_mail_quota'] != ($data['mail_quota'] * 1048576)) {
                sync_mailboxes_quota($domainId, $data['mail_quota'] * 1048576);
            }

            // Update domain alias IP if needed
            if ($data['domain_ip_id'] != $data['fallback_domain_ip_id']) {
                if ($data['domain_alias_limit'] != '-1') {
                    exec_query('UPDATE domain_aliasses SET alias_ip_id = ? WHERE domain_id = ?', array(
                        $data['domain_ip_id'], $domainId
                    ));
                }
            }

            // Update Ftp quota limit if needed
            if ($data['domain_disk_limit'] != $data['fallback_domain_disk_limit']) {
                exec_query(
                    '
                        REPLACE INTO quotalimits (
                            name, quota_type, per_session, limit_type, bytes_in_avail, bytes_out_avail,
                            bytes_xfer_avail, files_in_avail, files_out_avail, files_xfer_avail
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                        )
                    ',
                    array(
                        $data['domain_name'], 'group', 'false', 'hard', $data['domain_disk_limit'] * 1048576, 0, 0, 0,
                        0, 0
                    )
                );
            }

            // Update reseller properties
            update_reseller_c_props($data['reseller_id']);

            $db->commit();

            iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditDomain, array(
                'domainId' => $domainId
            ));

            if ($needDaemonRequest) {
                send_request();
                set_page_message(tr('Domain scheduled for update.'), 'success');
            } else {
                set_page_message(tr('Domain successfully updated.'), 'success');
            }

            $userLogged = isset($_SESSION['logged_from']) ? $_SESSION['logged_from'] : $_SESSION['user_logged'];
            write_log("Domain " . decode_idna($data['domain_name']) . " has been updated by $userLogged", E_USER_NOTICE);
            return true;
        }
    } catch (iMSCP_Exception_Database $e) {
        $db->rollBack();
        throw $e;
    }

    if (!empty($errFieldsStack)) {
        iMSCP_Registry::set('errFieldsStack', $errFieldsStack);
    }

    return false;
} // end reseller_updateData()

/**
 * Validates a new service limit
 *
 * @param int $newCustomerLimit New customer service limit
 * @param int $customerConsumption Customer consumption
 * @param int $customerLimit Limit for customer
 * @param int $resellerConsumption Reseller consumption
 * @param int $resellerLimit Limit for reseller
 * @param int $translatedServiceName Translation of service name
 * @return bool TRUE if new limit is valid, FALSE otherwise
 */
function _reseller_isValidServiceLimit($newCustomerLimit, $customerConsumption, $customerLimit, $resellerConsumption, $resellerLimit, $translatedServiceName)
{
    // Please, don't change test order.
    if (($resellerLimit == -1 || $resellerLimit > 0) && $newCustomerLimit == 0) {
        set_page_message(tr('The %s limit for this customer cannot be unlimited because your are limited for this service.', $translatedServiceName), 'error');
        return false;
    } elseif ($newCustomerLimit == -1 && $customerConsumption > 0) {
        set_page_message(tr("The %s limit for this customer cannot be set to 'disabled' because he has already %d %s.", $translatedServiceName, $customerConsumption, $translatedServiceName), 'error');
        return false;
    } elseif ($resellerLimit != 0 && $newCustomerLimit > ($resellerLimit - $resellerConsumption) + $customerLimit) {
        set_page_message(tr('The %s limit for this customer cannot be greater than %d, your calculated limit.', $translatedServiceName, ($resellerLimit - $resellerConsumption) + $customerLimit), 'error');
        return false;
    } elseif ($newCustomerLimit != -1 && $newCustomerLimit != 0 && $newCustomerLimit < $customerConsumption) {
        set_page_message(tr('The %s limit for this customer cannot be lower than %d, the total of %s already used by him.', $translatedServiceName, round($customerConsumption), $translatedServiceName), 'error');
        return false;
    }

    return true;
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);
check_login('reseller');

$cfg = iMSCP_Registry::get('config');

if (!isset($_GET['edit_id'])) {
    showBadRequestErrorPage();
}

$domainId = intval($_GET['edit_id']);

if (!empty($_POST) && reseller_checkAndUpdateData($domainId)) {
    redirectTo('users.php');
}

$data =& reseller_getData($domainId);
$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'reseller/domain_edit.tpl',
    'page_message' => 'layout',
    'ip_entry' => 'page',
    'subdomain_limit_block' => 'page',
    'domain_aliasses_limit_block' => 'page',
    'mail_accounts_limit_block' => 'page',
    'ftp_accounts_limit_block' => 'page',
    'sql_db_and_users_limit_block' => 'page',
    'ext_mail_block' => 'page',
    'php_block' => 'page',
    'php_editor_block' => 'php_block',
    'php_editor_permissions_block' => 'php_editor_block',
    'php_editor_allow_url_fopen_block' => 'php_editor_permissions_block',
    'php_editor_display_errors_block' => 'php_editor_permissions_block',
    'php_editor_disable_functions_block' => 'php_editor_permissions_block',
    'php_editor_mail_function_block' => 'php_editor_permissions_block',
    'php_editor_default_values_block' => 'php_directives_editor_block',
    'cgi_block' => 'page',
    'custom_dns_records_feature' => 'page',
    'aps_block' => 'page',
    'backup_block' => 'page'
));

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Reseller / Customers / Overview / Edit Domain'),
    'EDIT_ID' => tohtml($domainId),
    'TR_DOMAIN_OVERVIEW' => tr('Domain overview'),
    'TR_DOMAIN_NAME' => tr('Domain name'),
    'DOMAIN_NAME' => tohtml(decode_idna($data['domain_name'])),
    'TR_DOMAIN_EXPIRE_DATE' => tr('Domain expiration date'),
    'DOMAIN_EXPIRE_DATE' => ($data['fallback_domain_expires'] != 0) ? date($cfg['DATE_FORMAT'], $data['fallback_domain_expires']) : tr('N/A'),
    'TR_DOMAIN_NEW_EXPIRE_DATE' => tr('Domain new expiration date'),
    'TR_DOMAIN_EXPIRE_HELP' => tr("In case domain expiration date is 'N/A', the expiration date will be set from today."),
    'DOMAIN_NEW_EXPIRE_DATE' => tohtml(($data['domain_expires'] != 0) ? ($data['domain_expires_ok'] ? date('m/d/Y', $data['domain_expires']) : $data['domain_expires']) : ''),
    'DOMAIN_NEW_EXPIRE_DATE_DISABLED' => ($data['domain_never_expires'] == 'on') ? 'disabled="disabled"' : '',
    'TR_DOMAIN_NEVER_EXPIRES' => tr('Never'),
    'DOMAIN_NEVER_EXPIRES_CHECKED' => ($data['domain_never_expires'] == 'on') ? ' checked' : '',
    'TR_DOMAIN_IP' => tr('Domain IP'),
    'TR_UPDATE' => tr('Update'),
    'TR_CANCEL' => tr('Cancel')
));

reseller_generate_ip_list($tpl, $_SESSION['user_id'], $data['domain_ip_id']);
generateNavigation($tpl);
reseller_generateForm($tpl, $data);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
