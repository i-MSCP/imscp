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

use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_PHPini as PhpIni;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get mail data
 *
 * @param int $domainId Domain id
 * @param int $mailQuota Mail quota limit
 * @return array An array which contain in order sum of all mailbox quota, current quota limit, number of mailboxes)
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function getMailData($domainId, $mailQuota)
{
    static $mailData = NULL;

    if (NULL === $mailData) {
        $stmt = exec_query(
            '
                SELECT IFNULL(SUM(quota), 0) AS quota, COUNT(mail_id) AS nb_mailboxes
                FROM mail_users
                WHERE domain_id = ?
            ',
            $domainId
        );
        $row = $stmt->fetchRow();

        $mailData = [
            'quota_sum'    => bytesHuman($row['quota']),
            'quota_limit'  => $mailQuota == 0 ? '∞' : mebibytesHuman($mailQuota),
            'nb_mailboxes' => $row['nb_mailboxes']
        ];
    }

    return $mailData;
}

/**
 * Return properties for the given reseller
 *
 * @param int $resellerId Reseller id
 * @return array Reseller properties
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function reseller_getResellerProps($resellerId)
{
    $stmt = exec_query(
        '
            SELECT reseller_id, current_sub_cnt, max_sub_cnt, current_als_cnt, max_als_cnt, current_mail_cnt,
                max_mail_cnt, current_ftp_cnt, max_ftp_cnt, current_sql_db_cnt, max_sql_db_cnt, current_sql_user_cnt,
                max_sql_user_cnt, current_disk_amnt, max_disk_amnt, current_traff_amnt, max_traff_amnt, reseller_ips,
                software_allowed
            FROM reseller_props WHERE reseller_id = ?
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
 * @throws Zend_Date_Exception
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function reseller_getDomainProps($domainId)
{
    $stmt = exec_query(
        '
            SELECT admin_id, domain_id, domain_name, domain_expires, domain_status, domain_ip_id, domain_subd_limit,
                domain_alias_limit, domain_mailacc_limit, domain_ftpacc_limit, domain_sqld_limit,
                domain_sqlu_limit, domain_disk_limit, domain_disk_usage, domain_traffic_limit, domain_php,
                domain_cgi, domain_dns, domain_software_allowed, allowbackup, domain_external_mail,
                web_folder_protection, mail_quota
            FROM domain
            JOIN admin ON(admin_id = domain_admin_id)
            WHERE domain_id = ?
        ',
        $domainId
    );
    $data = $stmt->fetchRow();
    $data['mail_quota'] = $data['mail_quota'] / 1048576;

    $trafficData = getClientMonthlyTrafficStats($domainId);
    $data['domainTraffic'] = $trafficData[4];
    return $data;
}

/**
 * Returns domain data
 *
 * @param int $domainId Domain unique identifier
 * @param bool $forUpdate Tell whether or not data are fetched for update
 * @return array Reference to array of data
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function &getData($domainId, $forUpdate = false)
{
    static $data = NULL;

    if (NULL !== $data) {
        return $data;
    }

    $statusOk = 'ok|disabled|ordered';

    // Checks for domain existence and status
    $stmt = exec_query(
        '
            SELECT
                t1.domain_status, t2.admin_id, COUNT(t3.subdomain_status) + COUNT(t4.alias_status) +
                COUNT(t5.subdomain_alias_status) AS status_not_ok
            FROM domain AS t1
            JOIN admin AS t2 ON(admin_id = domain_admin_id)
            LEFT JOIN subdomain AS t3 ON (t1.domain_id = t3.domain_id AND t3.subdomain_status NOT RLIKE ?)
            LEFT JOIN domain_aliasses AS t4 ON (t1.domain_id = t4.domain_id AND t4.alias_status NOT RLIKE ?)
            LEFT JOIN subdomain_alias AS t5 ON (t5.alias_id = t4.alias_id AND t5.subdomain_alias_status NOT RLIKE ?)
            WHERE t1.domain_id = ?
            AND t2.created_by = ?
        ',
        [$statusOk, $statusOk, $statusOk, $domainId, $_SESSION['user_id']]
    );
    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

    if ($row['domain_status'] == '') {
        set_page_message(tr("The domain you are trying to edit doesn't exist."), 'error');
        redirectTo('users.php');
    }

    if (($row['domain_status'] != 'ok'
        && ($row['domain_status'] != 'disabled') || $row['status_not_ok'] > 0)
    ) {
        set_page_message(tr("The domain or at least one of its entities has a different status than 'ok'."), 'warning');
        redirectTo('users.php');
    }

    if ($row['domain_status'] == 'disabled') {
        set_page_message(tr('The domain is currently deactivated. The modification of some of its properties will result by a complete or partial reactivation of it.'), 'warning');
    }

    $domainProps = reseller_getDomainProps($domainId);
    $resellerProps = reseller_getResellerProps($_SESSION['user_id']);
    $resellerProps['reseller_ips'] = explode(';', rtrim($resellerProps['reseller_ips'], ';'));

    list($subCount, $alsCount, $mailCount, $ftpCount, $sqlDbCount, $sqlUsersCount) = get_customer_objects_counts(
        $row['admin_id']
    );

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

    $phpini = PhpIni::getInstance();
    $phpini->loadResellerPermissions($_SESSION['user_id']); // Load reseller PHP permissions
    $phpini->loadClientPermissions($data['admin_id']); // Load client PHP permissions
    $phpini->loadDomainIni($data['admin_id'], $data['domain_id'], 'dmn'); // Load domain PHP configuration options

    if ($forUpdate) { // Post request
        foreach (
            [
                'domain_subd_limit'    => 'max_sub_cnt',
                'domain_alias_limit'   => 'max_als_cnt',
                'domain_mailacc_limit' => 'max_mail_cnt',
                'mail_quota'           => 'max_mail_cnt',
                'domain_ftpacc_limit'  => 'max_ftp_cnt',
                'domain_sqld_limit'    => 'max_sql_db_cnt',
                'domain_sqlu_limit'    => 'max_sql_user_cnt',
                'domain_traffic_limit' => 'max_traff_amnt',
                'domain_disk_limit'    => 'max_disk_amnt'
            ] as $customerLimit => $resellerMaxLimit
        ) {
            if (array_key_exists($customerLimit, $_POST)
                && $data[$resellerMaxLimit] != -1
            ) {
                $data[$customerLimit] = clean_input($_POST[$customerLimit]);
            }
        }

        $data['domain_ip_id'] = isset($_POST['domain_ip_id'])
            ? clean_input($_POST['domain_ip_id']) : $data['domain_ip_id'];
        $data['domain_expires'] = isset($_POST['domain_expires'])
            ? clean_input($_POST['domain_expires']) : $data['domain_expires'];
        $data['domain_never_expires'] = isset($_POST['domain_never_expires'])
            ? clean_input($_POST['domain_never_expires']) : 'off';
        $data['domain_php'] = isset($_POST['domain_php']) ? clean_input($_POST['domain_php']) : $data['domain_php'];
        $data['domain_cgi'] = isset($_POST['domain_cgi']) ? clean_input($_POST['domain_cgi']) : $data['domain_cgi'];
        $data['domain_dns'] = isset($_POST['domain_dns']) ? clean_input($_POST['domain_dns']) : $data['domain_dns'];

        if ($data['software_allowed'] == 'yes') {
            $data['domain_software_allowed'] = isset($_POST['domain_software_allowed'])
                ? clean_input($_POST['domain_software_allowed']) : $data['domain_software_allowed'];
        } else {
            $data['domain_software_allowed'] = 'no';
        }

        if (Registry::get('config')['BACKUP_DOMAINS'] == 'yes') {
            $data['allowbackup'] = isset($_POST['allowbackup']) && is_array($_POST['allowbackup'])
                ? array_intersect($_POST['allowbackup'], ['dmn', 'sql', 'mail']) : [];
        } else {
            $data['allowbackup'] = [];
        }

        $data['domain_external_mail'] = isset($_POST['domain_external_mail'])
            ? clean_input($_POST['domain_external_mail']) : $data['domain_external_mail'];
        $data['web_folder_protection'] = isset($_POST['web_folder_protection'])
            ? clean_input($_POST['web_folder_protection']) : $data['web_folder_protection'];
    }

    return $data;
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @param array &$data Domain related data
 * @return void
 */
function generatePage(TemplateEngine $tpl, &$data)
{
    generateLimitsForm($tpl, $data);
    generateFeaturesForm($tpl, $data);
}

/**
 * Generates domain limits form
 *
 * Note: Only shows the limits on which the domain reseller has permissions.
 *
 * @param TemplateEngine $tpl
 * @param array $data Domain data
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function generateLimitsForm(TemplateEngine $tpl, &$data)
{
    list(, $subdomainCount, $domainAliasesCount, $mailsCount, $ftpUsersCount, $sqlDbCount, $sqlUserCount, $trafficUsage,
        $diskUsage
        ) = getResellerStats($_SESSION['user_id']);

    $tpl->assign([
        'TR_DOMAIN_LIMITS'        => tohtml(tr('Domains limit')),
        'TR_LIMIT_VALUE'          => tohtml(tr('Limit value')),
        'TR_CUSTOMER_CONSUMPTION' => tohtml(tr('Customer consumption')),
        'TR_RESELLER_CONSUMPTION' => tohtml(isset($_SESSION['logged_from'])
            ? tr('Reseller consumption') : tr('Your consumption'))
    ]);

    // Subdomains limit
    if ($data['max_sub_cnt'] == -1) { // Reseller has no permissions on this service
        $tpl->assign('SUBDOMAIN_LIMIT_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_SUBDOMAINS_LIMIT'               => tohtml(tr('Subdomains limit')) . '<br><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
            'SUBDOMAIN_LIMIT'                   => tohtml($data['domain_subd_limit']),
            'TR_CUSTOMER_SUBDOMAINS_COMSUPTION' => $data['fallback_domain_subd_limit'] != -1
                ? tohtml($data['nbSubdomains']) . ' / ' . (
                $data['fallback_domain_subd_limit'] != 0 ? tohtml($data['fallback_domain_subd_limit']) : '∞'
                ) : tohtml(tr('Disabled')),
            'TR_RESELLER_SUBDOMAINS_COMSUPTION' => tohtml($subdomainCount) . ' / ' . (
                $data['max_sub_cnt'] != 0 ? tohtml($data['max_sub_cnt']) : '∞'
                )
        ]);
    }

    // Domain aliases limit
    if ($data['max_als_cnt'] == -1) { // Reseller has no permissions on this service
        $tpl->assign('DOMAIN_ALIASES_LIMIT_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_ALIASSES_LIMIT'                      => tohtml(tr('Domain aliases limit')) . '<br><i>(-1 ' . tohtml(tr('disabled'))
                . ', 0 ∞)</i>',
            'DOMAIN_ALIASSES_LIMIT'                  => tohtml($data['domain_alias_limit']),
            'TR_CUSTOMER_DOMAIN_ALIASSES_COMSUPTION' => $data['fallback_domain_alias_limit'] != -1
                ? tohtml($data['nbAliasses']) . ' / ' . ($data['fallback_domain_alias_limit'] != 0
                    ? tohtml($data['fallback_domain_alias_limit']) : '∞') : tohtml(tr('Disabled')),
            'TR_RESELLER_DOMAIN_ALIASSES_COMSUPTION' => tohtml($domainAliasesCount) . ' / '
                . ($data['max_als_cnt'] != 0 ? tohtml($data['max_als_cnt']) : '∞')
        ]);
    }

    // Mail accounts limit
    if ($data['max_mail_cnt'] == -1) { // Reseller has no permissions on this service
        $tpl->assign('MAIL_ACCOUNTS_LIMIT_BLOCK', '');
    } else {
        $mailData = getMailData($data['domain_id'], $data['fallback_mail_quota']);

        $tpl->assign([
            'TR_MAIL_ACCOUNTS_LIMIT'               => tohtml(tr('Mail accounts limit')) . '<br><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
            'MAIL_ACCOUNTS_LIMIT'                  => tohtml($data['domain_mailacc_limit']),
            'TR_CUSTOMER_MAIL_ACCOUNTS_COMSUPTION' => $data['fallback_domain_mailacc_limit'] != -1
                ? tohtml($data['nbMailAccounts']) . ' / ' . ($data['fallback_domain_mailacc_limit'] != 0
                    ? tohtml($data['fallback_domain_mailacc_limit']) : '∞') : tr('Disabled'),
            'TR_RESELLER_MAIL_ACCOUNTS_COMSUPTION' => tohtml($mailsCount) . ' / '
                . ($data['max_mail_cnt'] != 0 ? tohtml($data['max_mail_cnt']) : '∞'),

            'TR_MAIL_QUOTA'                     => tohtml(tr('Mail quota [MiB]')) . '<br/><i>(0 ∞)</i>',
            'MAIL_QUOTA'                        => $data['mail_quota'] != 0 ? tohtml($data['mail_quota']) : 0,
            'TR_CUSTOMER_MAIL_QUOTA_COMSUPTION' => $mailData['quota_sum'] . ' / ' . $mailData['quota_limit'],
            'TR_NO_AVAILABLE'                   => tohtml(tr('N/A'))
        ]);
    }

    // Ftp accounts limit
    if ($data['max_ftp_cnt'] == -1) { // Reseller has no permissions on this service
        $tpl->assign('FTP_ACCOUNTS_LIMIT_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_FTP_ACCOUNTS_LIMIT'               => tohtml(tr('FTP accounts limit')) . '<br><i>(-1 ' . tohtml(tr('disabled'))
                . ', 0 ∞)</i>',
            'FTP_ACCOUNTS_LIMIT'                  => tohtml($data['domain_ftpacc_limit']),
            'TR_CUSTOMER_FTP_ACCOUNTS_COMSUPTION' => $data['fallback_domain_ftpacc_limit'] != -1
                ? tohtml($data['nbFtpAccounts']) . ' / ' . ($data['fallback_domain_ftpacc_limit'] != 0
                    ? tohtml($data['fallback_domain_ftpacc_limit']) : '∞') : tohtml(tr('Disabled')),
            'TR_RESELLER_FTP_ACCOUNTS_COMSUPTION' => tohtml($ftpUsersCount) . ' / '
                . ($data['max_ftp_cnt'] != 0 ? tohtml($data['max_ftp_cnt']) : '∞')
        ]);
    }

    // SQL Database - Sql Users limits
    if ($data['max_sql_db_cnt'] == -1 || $data['max_sql_user_cnt'] == -1) { // Reseller has no permissions on this service
        $tpl->assign('SQL_DB_AND_USERS_LIMIT_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_SQL_DATABASES_LIMIT'               => tohtml(tr('SQL databases limit')) . '<br><i>(-1 ' . tohtml(tr('disabled'))
                . ', 0 ∞)</i>',
            'SQL_DATABASES_LIMIT'                  => tohtml($data['domain_sqld_limit']),
            'TR_CUSTOMER_SQL_DATABASES_COMSUPTION' => $data['fallback_domain_sqld_limit'] != -1
                ? tohtml($data['nbSqlDatabases']) . ' / ' . ($data['fallback_domain_sqld_limit'] != 0
                    ? tohtml($data['fallback_domain_sqld_limit']) : '∞') : tr('Disabled'),
            'TR_RESELLER_SQL_DATABASES_COMSUPTION' => tohtml($sqlDbCount) . ' / '
                . ($data['max_sql_db_cnt'] != 0 ? tohtml($data['max_sql_db_cnt']) : '∞'),
            'TR_SQL_USERS_LIMIT'                   => tohtml(tr('SQL users limit')) . '<br><i>(-1 ' . tohtml(tr('disabled'))
                . ', 0 ∞)</i>',
            'SQL_USERS_LIMIT'                      => tohtml($data['domain_sqlu_limit']),
            'TR_CUSTOMER_SQL_USERS_COMSUPTION'     => $data['fallback_domain_sqlu_limit'] != -1
                ? tohtml($data['nbSqlUsers']) . ' / ' . ($data['fallback_domain_sqlu_limit'] != 0
                    ? tohtml($data['fallback_domain_sqlu_limit']) : '∞') : tohtml(tr('Disabled')),
            'TR_RESELLER_SQL_USERS_COMSUPTION'     => tohtml($sqlUserCount) . ' / '
                . ($data['max_sql_user_cnt'] != 0 ? tohtml($data['max_sql_user_cnt']) : '∞')
        ]);
    }

    // Traffic limit
    $tpl->assign([
        'TR_TRAFFIC_LIMIT'                => tohtml(tr('Monthly traffic limit [MiB]')) . '<br/><i>(0 ∞)</i>',
        'TRAFFIC_LIMIT'                   => tohtml($data['domain_traffic_limit']),
        'TR_CUSTOMER_TRAFFIC_COMSUPTION'  => tohtml(bytesHuman($data['domainTraffic'])) . ' / '
            . ($data['fallback_domain_traffic_limit'] != 0
                ? tohtml(mebibytesHuman($data['fallback_domain_traffic_limit'])) : '∞'),
        'TR_RESELLER_TRAFFIC_COMSUPTION'  => tohtml(bytesHuman($trafficUsage)) . ' / '
            . ($data['max_traff_amnt'] != 0 ? tohtml(mebibytesHuman($data['max_traff_amnt'])) : '∞'),

        // Disk space limit
        'TR_DISK_LIMIT'                   => tohtml(tr('Disk space limit [MiB]')) . '<br/><i>(0 ∞)</span>',
        'DISK_LIMIT'                      => tohtml($data['domain_disk_limit']),
        'TR_CUSTOMER_DISKPACE_COMSUPTION' => tohtml(bytesHuman($data['domain_disk_usage'])) . ' / '
            . ($data['fallback_domain_disk_limit'] != 0
                ? tohtml(mebibytesHuman($data['fallback_domain_disk_limit'])) : '∞'),
        'TR_RESELLER_DISKPACE_COMSUPTION' => tohtml(bytesHuman($diskUsage)) . ' / '
            . ($data['max_disk_amnt'] != 0 ? tohtml(mebibytesHuman($data['max_disk_amnt'])) : '∞')
    ]);
} // end _reseller_generateLimitsForm()

/**
 * Generates features form
 *
 * Note: For now most block for the features are always show. That will change when
 * admin will be able to disable them for a specific reseller.
 *
 * @param TemplateEngine $tpl
 * @param array $data Domain data
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function generateFeaturesForm(TemplateEngine $tpl, &$data)
{
    $tpl->assign([
        'TR_FEATURES' => tr('Features'),
        'TR_PHP'      => tr('PHP'),
        'PHP_YES'     => $data['domain_php'] == 'yes' ? ' checked' : '',
        'PHP_NO'      => $data['domain_php'] != 'yes' ? ' checked' : ''
    ]);

    $phpini = PhpIni::getInstance();

    // PHP editor - begin
    if (!$phpini->resellerHasPermission('phpiniSystem')) {
        $tpl->assign('PHP_EDITOR_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_SETTINGS'            => tohtml(tr('PHP Settings')),
            'TR_PHP_EDITOR'          => tohtml(tr('PHP Editor')),
            'TR_PHP_EDITOR_SETTINGS' => tohtml(tr('PHP Settings')),
            'TR_PERMISSIONS'         => tohtml(tr('PHP Permissions')),
            'TR_DIRECTIVES_VALUES'   => tohtml(tr('PHP directives values')),
            'TR_FIELDS_OK'           => tohtml(tr('All fields are valid.')),
            'TR_MIB'                 => tohtml(tr('MiB')),
            'TR_SEC'                 => tohtml(tr('Sec.')),
            'PHP_EDITOR_YES'         => $phpini->clientHasPermission('phpiniSystem') ? ' checked' : '',
            'PHP_EDITOR_NO'          => $phpini->clientHasPermission('phpiniSystem') ? '' : ' checked'
        ]);

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

        if (Registry::get('config')['HTTPD_SERVER'] == 'apache_itk') {
            $tpl->assign([
                'PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK' => '',
                'PHP_EDITOR_MAIL_FUNCTION_BLOCK'     => ''
            ]);
        } else {
            if ($phpini->resellerHasPermission('phpiniDisableFunctions')) {
                $tpl->assign([
                    'TR_CAN_EDIT_DISABLE_FUNCTIONS' => tr('Can edit the PHP %s configuration option', '<b>disable_functions</b>'),
                    'DISABLE_FUNCTIONS_YES'         => $phpini->getClientPermission('phpiniDisableFunctions') == 'yes'
                        ? ' checked' : '',
                    'DISABLE_FUNCTIONS_NO'          => $phpini->getClientPermission('phpiniDisableFunctions') == 'no'
                        ? ' checked' : '',
                    'TR_ONLY_EXEC'                  => tr('Only exec'),
                    'DISABLE_FUNCTIONS_EXEC'        => ($phpini->getClientPermission('phpiniDisableFunctions') == 'exec')
                        ? ' checked' : ''
                ]);
            } else {
                $tpl->assign('PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK', '');
            }

            if ($phpini->resellerHasPermission('phpiniMailFunction')) {
                $tpl->assign([
                    'TR_CAN_USE_MAIL_FUNCTION' => tr('Can use the PHP %s function', '<b>mail</b>'),
                    'MAIL_FUNCTION_YES'        => $phpini->clientHasPermission('phpiniMailFunction') == 'yes'
                        ? ' checked' : '',
                    'MAIL_FUNCTION_NO'         => $phpini->clientHasPermission('phpiniMailFunction') == 'no'
                        ? '' : ' checked'
                ]);
            } else {
                $tpl->assign('PHP_EDITOR_MAIL_FUNCTION_BLOCK', '');
            }

            $permissionsBlock = true;
        }

        if (!$permissionsBlock) {
            $tpl->assign('PHP_EDITOR_PERMISSIONS_BLOCK', '');
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

    // PHP editor - end

    // CGI support
    $tpl->assign([
        'TR_CGI'  => tr('CGI'),
        'CGI_YES' => $data['domain_cgi'] == 'yes' ? ' checked' : '',
        'CGI_NO'  => $data['domain_cgi'] != 'yes' ? ' checked' : ''
    ]);

    // Custom DNS records
    if (resellerHasFeature('custom_dns_records')) {
        $tpl->assign([
            'TR_DNS'  => tr('Custom DNS records'),
            'DNS_YES' => $data['domain_dns'] == 'yes' ? ' checked' : '',
            'DNS_NO'  => $data['domain_dns'] != 'yes' ? ' checked' : ''
        ]);
    } else {
        $tpl->assign('CUSTOM_DNS_RECORDS_FEATURE', '');
    }

    // APS support
    if ($data['software_allowed'] == 'no') {
        $tpl->assign('APS_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_APS'  => tr('Software installer'),
            'APS_YES' => $data['domain_software_allowed'] == 'yes' ? ' checked' : '',
            'APS_NO'  => $data['domain_software_allowed'] != 'yes' ? ' checked' : ''
        ]);
    }

    // External mail support
    if ($data['max_mail_cnt'] == '-1') {
        $tpl->assign('EXT_MAIL_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_EXTMAIL'  => tr('External mail server'),
            'EXTMAIL_YES' => $data['domain_external_mail'] == 'yes' ? ' checked' : '',
            'EXTMAIL_NO'  => $data['domain_external_mail'] != 'yes' ? ' checked' : ''
        ]);
    }

    if (Registry::get('config')['BACKUP_DOMAINS'] == 'yes') {
        // Backup support
        $tpl->assign([
            'TR_BACKUP'        => tohtml(tr('Backup')),
            'TR_BACKUP_DOMAIN' => tohtml(tr('Domain')),
            'BACKUP_DOMAIN'    => in_array('dmn', $data['allowbackup']) ? ' checked' : '',
            'TR_BACKUP_SQL'    => tohtml(tr('SQL')),
            'BACKUP_SQL'       => in_array('sql', $data['allowbackup']) ? ' checked' : '',
            'TR_BACKUP_MAIL'   => tohtml(tr('Mail')),
            'BACKUP_MAIL'      => in_array('mail', $data['allowbackup']) ? ' checked' : ''
        ]);
    } else {
        $tpl->assign('BACKUP_BLOCK', '');
    }

    $tpl->assign([
        'TR_WEB_FOLDER_PROTECTION'      => tohtml(tr('Web folder protection')),
        'TR_WEB_FOLDER_PROTECTION_HELP' => tohtml(tr('If set to `yes`, Web folders will be protected against deletion.'), 'htmlAttr'),
        'WEB_FOLDER_PROTECTION_YES'     => $data['web_folder_protection'] == 'yes' ? ' checked' : '',
        'WEB_FOLDER_PROTECTION_NO'      => $data['web_folder_protection'] != 'yes' ? ' checked' : '',
        'TR_YES'                        => tohtml(tr('Yes')),
        'TR_NO'                         => tohtml(tr('No'))
    ]);
}

/**
 * Check and updates domain data
 *
 * @param int $domainId Domain unique identifier
 * @return bool
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function reseller_checkAndUpdateData($domainId)
{
    $db = Database::getInstance();
    $errFieldsStack = [];

    try {
        // Getting domain data
        $data =& getData($domainId, true);

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

        // Check for domain IP id
        if (!in_array($data['domain_ip_id'], $data['reseller_ips'])) {
            $data['domain_ip_id'] = $data['fallback_domain_ip_id'];
        }

        // Check for the subdomains limit
        if ($data['fallback_domain_subd_limit'] != -1) {
            if (!imscp_limit_check($data['domain_subd_limit'])) {
                set_page_message(tr('Wrong syntax for the %s limit.', tr('subdomains')), 'error');
                $errFieldsStack[] = 'domain_subd_limit';
            } elseif (!isValidServiceLimit(
                $data['domain_subd_limit'], $data['nbSubdomains'], $data['fallback_domain_subd_limit'],
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
            } elseif (!isValidServiceLimit(
                $data['domain_alias_limit'], $data['nbAliasses'], $data['fallback_domain_alias_limit'],
                $data['current_als_cnt'], $data['max_als_cnt'],
                $data['nbAliasses'] > 1 ? tr('domain aliases') : tr('domain alias'))
            ) {
                $errFieldsStack[] = 'domain_alias_limit';
            }
        }

        // Check for the mail accounts limit
        if ($data['fallback_domain_mailacc_limit'] != -1) {
            if (!imscp_limit_check($data['domain_mailacc_limit'])) {
                set_page_message(tr('Wrong syntax for the %s limit.', tr('mail accounts')), 'error');
                $errFieldsStack[] = 'domain_mailacc_limit';
            } elseif (!isValidServiceLimit(
                $data['domain_mailacc_limit'], $data['nbMailAccounts'], $data['fallback_domain_mailacc_limit'],
                $data['current_mail_cnt'], $data['max_mail_cnt'],
                $data["nbMailAccounts"] > 1 ? tr('mail accounts') : tr('mail account'))
            ) {
                $errFieldsStack[] = 'domain_mailacc_limit';
            }
        }

        // Check for the Ftp accounts limit
        if ($data['fallback_domain_ftpacc_limit'] != -1) {
            if (!imscp_limit_check($data['domain_ftpacc_limit'])) {
                set_page_message(tr('Wrong syntax for the %s limit.', tr('Ftp accounts')), 'error');
                $errFieldsStack[] = 'domain_ftpacc_limit';
            } elseif (!isValidServiceLimit(
                $data['domain_ftpacc_limit'], $data['nbFtpAccounts'], $data['fallback_domain_ftpacc_limit'],
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
            } elseif (!isValidServiceLimit(
                $data['domain_sqld_limit'], $data['nbSqlDatabases'], $data['fallback_domain_sqld_limit'],
                $data['current_sql_db_cnt'], $data['max_sql_db_cnt'],
                $data['nbSqlDatabases'] > 1 ? tr('SQL databases') : tr('SQL database'))
            ) {
                $errFieldsStack[] = 'domain_sqld_limit';
            } elseif ($data['domain_sqld_limit'] != -1 && $data['domain_sqlu_limit'] == -1) {
                set_page_message(tr('SQL users limit is disabled.'), 'error');
                $errFieldsStack[] = 'domain_sqld_limit';
                $errFieldsStack[] = 'domain_sqlu_limit';
            }
        }

        // Check for the Sql users limit
        if ($data['fallback_domain_sqlu_limit'] != -1) {
            if (!imscp_limit_check($data['domain_sqlu_limit'])) {
                set_page_message(tr('Wrong syntax for the %s limit.', tr('SQL users')), 'error');
                $errFieldsStack[] = 'domain_sqlu_limit';
            } elseif (!isValidServiceLimit(
                $data['domain_sqlu_limit'], $data['nbSqlUsers'], $data['fallback_domain_sqlu_limit'],
                $data['current_sql_user_cnt'], $data['max_sql_user_cnt'],
                $data['nbSqlUsers'] > 1 ? tr('SQL users') : tr('SQL user'))
            ) {
                $errFieldsStack[] = 'domain_sqlu_limit';
            } elseif ($data['domain_sqlu_limit'] != -1 && $data['domain_sqld_limit'] == -1) {
                set_page_message(tr('SQL databases limit is disabled.'), 'error');
                $errFieldsStack[] = 'domain_sqlu_limit';
                $errFieldsStack[] = 'domain_sqld_limit';
            }
        }

        // Check for the monthly traffic limit
        if (!imscp_limit_check($data['domain_traffic_limit'], NULL)) {
            set_page_message(tr('Wrong syntax for the %s limit.', tr('traffic')), 'error');
            $errFieldsStack[] = 'domain_traffic_limit';
        } elseif (!isValidServiceLimit(
            $data['domain_traffic_limit'], $data['domainTraffic'] / 1048576, $data['fallback_domain_traffic_limit'],
            $data['current_traff_amnt'], $data['max_traff_amnt'], tr('traffic'))
        ) {
            $errFieldsStack[] = 'domain_traffic_limit';
        }

        // Check for the disk space limit
        if (!imscp_limit_check($data['domain_disk_limit'], NULL)) {
            set_page_message(tr('Wrong syntax for the %s limit.', tr('disk space')), 'error');
            $errFieldsStack[] = 'domain_disk_limit';
        } elseif (!isValidServiceLimit(
            $data['domain_disk_limit'], $data['domain_disk_usage'] / 1048576, $data['fallback_domain_disk_limit'],
            $data['current_disk_amnt'], $data['max_disk_amnt'], tr('disk space'))
        ) {
            $errFieldsStack[] = 'domain_disk_limit';
        }

        // Check for mail quota
        if ($data['fallback_domain_mailacc_limit'] != -1) {
            if (!imscp_limit_check($data['mail_quota'], NULL)) {
                set_page_message(tr('Wrong syntax for the mail quota value.'), 'error');
                $errFieldsStack[] = 'mail_quota';
            } elseif ($data['domain_disk_limit'] != 0 && $data['mail_quota'] > $data['domain_disk_limit']) {
                set_page_message(tr('Mail quota cannot be bigger than disk space limit.'), 'error');
                $errFieldsStack[] = 'mail_quota';
            } elseif ($data['domain_disk_limit'] != 0 && $data['mail_quota'] == 0) {
                set_page_message(
                    tr('Mail quota cannot be unlimited. Max value is %d MiB.', $data['domain_disk_limit']), 'error'
                );
                $errFieldsStack[] = 'mail_quota';
            } else {
                $mailData = getMailData($data['domain_id'], $data['fallback_mail_quota']);

                if ($data['mail_quota'] != 0 && $data['mail_quota'] < $mailData['nb_mailboxes']) {
                    set_page_message(
                        tr(
                            'Mail quota cannot be lower than %d. Each mail account must have a least 1 MiB quota.',
                            $mailData['nb_mailboxes']
                        ),
                        'error'
                    );
                    $errFieldsStack[] = 'mail_quota';
                }
            }
        } else {
            $data['mail_quota'] = 0;
        }

        // Check for PHP support
        $data['domain_php'] = in_array($data['domain_php'], ['no', 'yes'])
            ? $data['domain_php'] : $data['fallback_domain_php'];

        // PHP editor
        $phpini = PhpIni::getInstance();

        // Needed to track changes
        $phpiniClientPerms = $phpini->getClientPermission();
        $phpiniDomainConf = $phpini->getDomainIni();

        if (isset($_POST['php_ini_system'])
            && $data['domain_php'] == 'yes'
            && $phpini->resellerHasPermission('phpiniSystem')
        ) {
            $phpini->setClientPermission('phpiniSystem', clean_input($_POST['php_ini_system']));

            if ($phpini->clientHasPermission('phpiniSystem')) {
                if (isset($_POST['phpini_perm_allow_url_fopen'])) {
                    $phpini->setClientPermission(
                        'phpiniAllowUrlFopen', clean_input($_POST['phpini_perm_allow_url_fopen'])
                    );
                }

                if (isset($_POST['phpini_perm_display_errors'])) {
                    $phpini->setClientPermission(
                        'phpiniDisplayErrors', clean_input($_POST['phpini_perm_display_errors'])
                    );
                }

                if (isset($_POST['phpini_perm_disable_functions'])) {
                    $phpini->setClientPermission(
                        'phpiniDisableFunctions', clean_input($_POST['phpini_perm_disable_functions'])
                    );
                }

                if (isset($_POST['phpini_perm_mail_function'])) {
                    $phpini->setClientPermission(
                        'phpiniMailFunction', clean_input($_POST['phpini_perm_mail_function'])
                    );
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
                $phpini->loadClientPermissions(); // Reset client PHP permissions
                $phpini->loadDomainIni(); // Reset domain PHP configuration options
            }
        } else {
            $phpini->loadClientPermissions(); // Reset client PHP permissions
            $phpini->loadDomainIni(); // Reset domain PHP configuration options
        }

        // Check for CGI support
        $data['domain_cgi'] = in_array($data['domain_cgi'], ['no', 'yes'])
            ? $data['domain_cgi'] : $data['fallback_domain_cgi'];

        // Check for custom DNS records support
        $data['domain_dns'] = in_array($data['domain_dns'], ['no', 'yes'])
            ? $data['domain_dns'] : $data['fallback_domain_dns'];

        // Check for APS support
        $data['domain_software_allowed'] = in_array($data['domain_software_allowed'], ['no', 'yes'])
            ? $data['domain_software_allowed'] : $data['fallback_domain_software_allowed'];

        // Check for External mail server support
        $data['domain_external_mail'] = in_array($data['domain_external_mail'], ['no', 'yes'])
            ? $data['domain_external_mail'] : $data['fallback_domain_external_mail'];

        // Check for backup support
        $data['allowbackup'] = is_array($data['allowbackup'])
            ? (array_intersect($data['allowbackup'], ['dmn', 'sql', 'mail'])) : $data['fallback_allowbackup'];

        // Check for Web folder protection support
        $data['web_folder_protection'] = in_array($data['web_folder_protection'], ['no', 'yes'])
            ? $data['web_folder_protection'] : $data['fallback_web_folder_protection'];

        if (empty($errFieldsStack)) { // Update process begin here
            $oldValues = [];
            $newValues = [];

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

            $db->beginTransaction();

            EventsManager::getInstance()->dispatch(Events::onBeforeEditDomain, ['domainId' => $domainId]);

            if ($phpini->updateClientDomainIni($phpini->getDomainIni(), $data['admin_id'])) {
                $needDaemonRequest = true;
            }

            // Domain IP has been changed
            // Mail feature has been enabled/disabled or
            // PHP feature has been enabled/disabled or
            // CGI feature has been enabled/disabled or
            // Web folder protection feature has been enabled or disabled
            if ($data['domain_ip_id'] != $data['fallback_domain_ip_id']
                || ($data['domain_mailacc_limit'] == '-1' && $data['fallback_domain_mailacc_limit'] != '-1'
                    || $data['domain_mailacc_limit'] != '-1' && $data['fallback_domain_mailacc_limit'] == '-1'
                )
                || $data['domain_php'] != $data['fallback_domain_php']
                || $data['domain_cgi'] != $data['fallback_domain_cgi']
                || $data['web_folder_protection'] != $data['fallback_web_folder_protection']
            ) {
                $needDaemonRequest = true;
            }

            if ($data['domain_dns'] != $data['fallback_domain_dns'] && $data['domain_dns'] == 'no') {
                // Support for custom DNS records is now disabled - We must delete all custom DNS entries
                // (except those that are protected), and update the DNS zone file
                exec_query('DELETE FROM domain_dns WHERE domain_id = ? AND owned_by = ?', [
                    $domainId, 'custom_dns_feature'
                ]);
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
                        phpini_perm_disable_functions = ?, phpini_perm_mail_function = ?, domain_external_mail = ?,
                        web_folder_protection = ?,
                        mail_quota = ?
                    WHERE
                        domain_id = ?
                ',
                [
                    $data['domain_expires'], time(), $data['domain_mailacc_limit'], $data['domain_ftpacc_limit'],
                    $data['domain_traffic_limit'], $data['domain_sqld_limit'], $data['domain_sqlu_limit'],
                    $needDaemonRequest ? 'tochange' : 'ok', $data['domain_alias_limit'], $data['domain_subd_limit'],
                    $data['domain_ip_id'], $data['domain_disk_limit'], $data['domain_php'], $data['domain_cgi'],
                    implode('|', $data['allowbackup']), $data['domain_dns'], $data['domain_software_allowed'],
                    $phpini->getClientPermission('phpiniSystem'),
                    $phpini->getClientPermission('phpiniAllowUrlFopen'),
                    $phpini->getClientPermission('phpiniDisplayErrors'),
                    $phpini->getClientPermission('phpiniDisableFunctions'),
                    $phpini->getClientPermission('phpiniMailFunction'),
                    $data['domain_external_mail'], $data['web_folder_protection'], $data['mail_quota'] * 1048576,
                    $domainId
                ]
            );

            if ($needDaemonRequest) {
                exec_query(
                    '
                        UPDATE domain_aliasses
                        SET alias_ip_id = ?, alias_status = ?
                        WHERE domain_id = ?
                        AND alias_status <> ?
                    ',
                    [$data['domain_ip_id'], 'tochange', $domainId, 'ordered']
                );
                exec_query(
                    'UPDATE domain_aliasses SET alias_ip_id = ? WHERE domain_id = ? AND alias_status = ?',
                    [$data['domain_ip_id'], $domainId, 'ordered']
                );
            }

            // Sync mailboxes quota if needed
            if ($data['fallback_mail_quota'] != ($data['mail_quota'] * 1048576)) {
                sync_mailboxes_quota($domainId, $data['mail_quota'] * 1048576);
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
                    [
                        $data['domain_name'], 'group', 'false', 'hard', $data['domain_disk_limit'] * 1048576, 0, 0, 0,
                        0, 0
                    ]
                );
            }

            // Update reseller properties
            update_reseller_c_props($data['reseller_id']);

            EventsManager::getInstance()->dispatch(Events::onAfterEditDomain, ['domainId' => $domainId]);

            $db->commit();

            if ($needDaemonRequest) {
                send_request();
                set_page_message(tr('Domain scheduled for update.'), 'success');
            } else {
                set_page_message(tr('Domain successfully updated.'), 'success');
            }

            $userLogged = isset($_SESSION['logged_from']) ? $_SESSION['logged_from'] : $_SESSION['user_logged'];
            write_log(
                sprintf('Domain %s has been updated by %s', decode_idna($data['domain_name']), $userLogged),
                E_USER_NOTICE
            );
            return true;
        }

        Registry::set('errFieldsStack', $errFieldsStack);
        return false;
    } catch (iMSCP_Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

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
 * @throws Zend_Exception
 */
function isValidServiceLimit(
    $newCustomerLimit, $customerConsumption, $customerLimit, $resellerConsumption, $resellerLimit,
    $translatedServiceName
)
{
    // Please, don't change test order.
    if (($resellerLimit == -1 || $resellerLimit > 0) && $newCustomerLimit == 0) {
        set_page_message(
            tr(
                'The %s limit for this customer cannot be unlimited because your are limited for this service.',
                $translatedServiceName
            ),
            'error'
        );
        return false;
    }

    if ($newCustomerLimit == -1 && $customerConsumption > 0) {
        set_page_message(
            tr(
                "The %s limit for this customer cannot be set to 'disabled' because he has already %d %s.",
                $translatedServiceName,
                $customerConsumption,
                $translatedServiceName
            ),
            'error'
        );
        return false;
    }

    if ($resellerLimit != 0
        && $newCustomerLimit > ($resellerLimit - $resellerConsumption) + $customerLimit
    ) {
        set_page_message(
            tr(
                'The %s limit for this customer cannot be greater than %d, your calculated limit.',
                $translatedServiceName,
                ($resellerLimit - $resellerConsumption) + $customerLimit
            ),
            'error'
        );
        return false;
    }

    if ($newCustomerLimit != -1 && $newCustomerLimit != 0 && $newCustomerLimit < $customerConsumption) {
        set_page_message(
            tr(
                'The %s limit for this customer cannot be lower than %d, the total of %s already used by him.',
                $translatedServiceName,
                round($customerConsumption),
                $translatedServiceName
            ),
            'error'
        );
        return false;
    }

    return true;
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('reseller');
EventsManager::getInstance()->dispatch(Events::onResellerScriptStart);

$cfg = Registry::get('config');

if (!isset($_GET['edit_id'])) {
    showBadRequestErrorPage();
}

$domainId = intval($_GET['edit_id']);

if (!empty($_POST) && reseller_checkAndUpdateData($domainId)) {
    redirectTo('users.php');
}

$data =& getData($domainId);
$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'                             => 'shared/layouts/ui.tpl',
    'page'                               => 'reseller/domain_edit.tpl',
    'page_message'                       => 'layout',
    'ip_entry'                           => 'page',
    'subdomain_limit_block'              => 'page',
    'domain_aliasses_limit_block'        => 'page',
    'mail_accounts_limit_block'          => 'page',
    'ftp_accounts_limit_block'           => 'page',
    'sql_db_and_users_limit_block'       => 'page',
    'ext_mail_block'                     => 'page',
    'php_block'                          => 'page',
    'php_editor_block'                   => 'php_block',
    'php_editor_permissions_block'       => 'php_editor_block',
    'php_editor_allow_url_fopen_block'   => 'php_editor_permissions_block',
    'php_editor_display_errors_block'    => 'php_editor_permissions_block',
    'php_editor_disable_functions_block' => 'php_editor_permissions_block',
    'php_editor_mail_function_block'     => 'php_editor_permissions_block',
    'php_editor_default_values_block'    => 'php_directives_editor_block',
    'cgi_block'                          => 'page',
    'custom_dns_records_feature'         => 'page',
    'aps_block'                          => 'page',
    'backup_block'                       => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'                   => tohtml(tr('Reseller / Customers / Overview / Edit Domain')),
    'EDIT_ID'                         => tohtml($domainId, 'htmlAttr'),
    'TR_DOMAIN_OVERVIEW'              => tohtml(tr('Domain overview')),
    'TR_DOMAIN_NAME'                  => tohtml(tr('Domain name')),
    'DOMAIN_NAME'                     => tohtml(decode_idna($data['domain_name'])),
    'TR_DOMAIN_EXPIRE_DATE'           => tohtml(tr('Domain expiration date')),
    'DOMAIN_EXPIRE_DATE'              => tohtml($data['fallback_domain_expires'] != 0
        ? date($cfg['DATE_FORMAT'], $data['fallback_domain_expires']) : tr('N/A')),
    'TR_DOMAIN_NEW_EXPIRE_DATE'       => tohtml(tr('Domain new expiration date')),
    'DOMAIN_NEW_EXPIRE_DATE'          => tohtml(
        ($data['domain_expires'] != 0)
            ? ($data['domain_expires_ok'] ? date('m/d/Y', $data['domain_expires']) : $data['domain_expires'])
            : '',
        'htmlAttr'
    ),
    'DOMAIN_NEW_EXPIRE_DATE_DISABLED' => $data['domain_never_expires'] == 'on' ? ' disabled' : '',
    'TR_DOMAIN_NEVER_EXPIRES'         => tohtml(tr('Never')),
    'DOMAIN_NEVER_EXPIRES_CHECKED'    => $data['domain_never_expires'] == 'on' ? ' checked' : '',
    'TR_DOMAIN_IP'                    => tohtml(tr('Domain IP')),
    'TR_UPDATE'                       => tohtml(tr('Update'), 'htmlAttr'),
    'TR_CANCEL'                       => tohtml(tr('Cancel'))
]);

reseller_generate_ip_list($tpl, $_SESSION['user_id'], $data['domain_ip_id']);
generateNavigation($tpl);
generatePage($tpl, $data);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventsManager::getInstance()->dispatch(Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
