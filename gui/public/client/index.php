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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2017 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generates limit
 *
 * @param $num
 * @param $limit
 * @return string
 * @throws Zend_Exception
 */
function gen_num_limit_msg($num, $limit)
{
    if ($limit == -1) {
        return '<span style="color: red;">' . tr('Disabled') . '</span>';
    }

    if ($limit == 0) {
        return $num . ' / ∞';
    }

    return $num . ' / ' . $limit;
}

/**
 * Generate mail quota limit msg
 *
 * @return string
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function gen_mail_quota_limit_mgs()
{
    $domainProps = get_domain_default_props($_SESSION['user_id']);
    $mailQuota = exec_query(
        'SELECT IFNULL(SUM(quota), 0) FROM mail_users WHERE domain_id = ?', $domainProps['domain_id']
    )->fetchRow(PDO::FETCH_COLUMN);

    if ($domainProps['mail_quota'] == 0) {
        return bytesHuman($mailQuota) . ' / ∞';
    }

    return bytesHuman($mailQuota) . ' / ' . bytesHuman($domainProps['mail_quota']);
}

/**
 * Generates notice for support system
 *
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function client_generateSupportSystemNotices()
{
    $ticketsCount = exec_query(
        "SELECT COUNT(ticket_id) FROM tickets WHERE ticket_from = ? AND ticket_status = '2' AND ticket_reply = '0'",
        $_SESSION['user_id']
    )->fetchRow(PDO::FETCH_COLUMN);

    if ($ticketsCount) {
        set_page_message(
            ntr(
                'You have a new answer to your support ticket.', 'You have %d new answers to your support tickets.',
                $ticketsCount, $ticketsCount
            ),
            'static_info'
        );
    }
}

/**
 * Generates traffic usage bar
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $usage Usage in bytes
 * @param int $maxUsage Max usage in bytes
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function client_generateTrafficUsageBar($tpl, $usage, $maxUsage)
{

    $trafficUsagePercent = getPercentUsage($usage, $maxUsage);
    $trafficUsageData = ($maxUsage > 0)
        ? sprintf('[%s / %s]', bytesHuman($usage), bytesHuman($maxUsage))
        : sprintf('[%s / ∞]', bytesHuman($usage));
    $tpl->assign([
        'TRAFFIC_PERCENT_WIDTH' => tohtml($trafficUsagePercent, 'htmlAttr'),
        'TRAFFIC_PERCENT'       => tohtml($trafficUsagePercent),
        'TRAFFIC_USAGE_DATA'    => tohtml($trafficUsageData)
    ]);

    if ($maxUsage > 0 && $usage > $maxUsage) {
        $tpl->assign('TR_TRAFFIC_WARNING', tr('You are exceeding your monthly traffic limit.'));
    } else {
        $tpl->assign('TRAFFIC_WARNING', '');
    }
}

/**
 * Generates disk usage bar
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $usage Usage in bytes
 * @param int $maxUsage Max usage in bytes
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function client_generateDiskUsageBar($tpl, $usage, $maxUsage)
{

    $diskUsagePercent = getPercentUsage($usage, $maxUsage);
    $diskUsageData = ($maxUsage > 0)
        ? sprintf('[%s / %s]', bytesHuman($usage), bytesHuman($maxUsage))
        : sprintf('[%s / ∞]', bytesHuman($usage));
    $tpl->assign([
        'DISK_PERCENT_WIDTH' => tohtml($diskUsagePercent, 'htmlAttr'),
        'DISK_PERCENT'       => tohtml($diskUsagePercent),
        'DISK_USAGE_DATA'    => tohtml($diskUsageData),
    ]);

    if ($maxUsage > 0 && $usage > $maxUsage) {
        $tpl->assign('TR_DISK_WARNING', tr('You are exceeding your disk space limit.'));
    } else {
        $tpl->assign('DISK_WARNING', '');
    }
}

/**
 * Generates feature status
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @todo hide features that are not available for reseller
 */
function client_generateFeatureStatus($tpl)
{
    $trYes = '<span style="color: green;">' . tr('Enabled') . '</span>';
    $trNo = '<span style="color: red;">' . tr('Disabled') . '</span>';;
    $tpl->assign(
        [
            //'DOMAIN_FEATURE_STATUS' =>  customerHasFeature('domain') ? $trYes : $trNo,
            'DOMAIN_FEATURE_STATUS'                => $trYes,
            'PHP_FEATURE_STATUS'                   => customerHasFeature('php') ? $trYes : $trNo,
            'PHP_DIRECTIVES_EDITOR_STATUS'         => customerHasFeature('php_editor') ? $trYes : $trNo,
            'CGI_FEATURE_STATUS'                   => customerHasFeature('cgi') ? $trYes : $trNo,
            'CUSTOM_DNS_RECORDS_FEATURE_STATUS'    => customerHasFeature('custom_dns_records') ? $trYes : $trNo,
            'EXTERNAL_MAIL_SERVERS_FEATURE_STATUS' => customerHasFeature('external_mail') ? $trYes : $trNo,
            'APP_INSTALLER_FEATURE_STATUS'         => customerHasFeature('aps') ? $trYes : $trNo,
            'WEBSTATS_FEATURE_STATUS'              => customerHasFeature('webstats') ? $trYes : $trNo
        ]
    );

    if (customerHasFeature('backup')) {
        $domainProperties = get_domain_default_props($_SESSION['user_id']);
        // Backup feature for customer can also be disabled by reseller via GUI
        $domainProperties['allowbackup'] = explode('|', $domainProperties['allowbackup']);

        $bkTranslation = [];
        foreach ($domainProperties['allowbackup'] as $bkvalue) {
            switch ($bkvalue) {
                case 'dmn':
                    $bkTranslation[] = tr('domain data');
                    break;
                case 'sql':
                    $bkTranslation[] = tr('SQL databases');
                    break;
                case 'mail':
                    $bkTranslation[] = tr('mail accounts');
                    break;
                default:
            }
        }

        if (count($bkTranslation) > 0) {
            $tpl->assign(
                'BACKUP_FEATURE_STATUS',
                '<span style="color:green;">' . tr('Enabled for: %s', implode(', ', $bkTranslation)) . '</span>'
            );
        } else {
            $tpl->assign('BACKUP_FEATURE_STATUS', $trNo);
        }
    } else {
        $tpl->assign('BACKUP_FEATURE_STATUS', $trNo);
    }
}

/**
 * Calculate monthly traffic usage for the given domain
 *
 * @param int $domainId Domain unique identifier
 * @return array An array that contain traffic information
 * @throws Zend_Date_Exception
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function client_makeTrafficUsage($domainId)
{
    $domainProperties = get_domain_default_props($_SESSION['user_id']);

    $trafficData = getClientMonthlyTrafficStats($domainId);
    $totalTraffic = $trafficData[4];
    unset($trafficData);

    if ($totalTraffic > 0) {
        $totalTraffic = ($totalTraffic / 1024) / 1024;
    } else {
        $totalTraffic = 0;
    }
    unset($trafficData);

    if ($domainProperties['domain_traffic_limit'] == 0) {
        $percent = 0;
    } else {
        if ($totalTraffic > 0) {
            $percent = ($totalTraffic / $domainProperties['domain_traffic_limit']) * 100;
        } else {
            $percent = 0;
        }
        $percent = sprintf('%.2f', $percent);
    }

    return [$percent, $totalTraffic];
}

/**
 * Returns domain remaining time before expire
 *
 * @access private
 * @param $domainExpireDate
 * @return array
 */
function _client_getDomainRemainingTime($domainExpireDate)
{
    $mi = 60;
    $h = $mi * $mi;
    $d = $h * 24;
    $mo = $d * 30;
    $y = $d * 365;
    $difftime = $domainExpireDate - time();
    $years = floor($difftime / $y);
    $difftime = $difftime % $y;
    $month = floor($difftime / $mo);
    $difftime = $difftime % $mo;
    $days = floor($difftime / $d);
    return [$years, $month, $days];
}

/**
 * Generates domain expires information
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function client_generateDomainExpiresInformation($tpl)
{
    $cfg = iMSCP_Registry::get('config');
    $domainProperties = get_domain_default_props($_SESSION['user_id']);

    if ($domainProperties['domain_expires'] != 0) {
        $domainRemainingTime = '';
        $domainExpiresDate = date($cfg['DATE_FORMAT'], $domainProperties['domain_expires']);

        if (time() < $domainProperties['domain_expires']) {
            list($years, $month, $days) = _client_getDomainRemainingTime($domainProperties['domain_expires']);

            if ($years == 0 && $month == 0 && $days <= 14) {
                $domainRemainingTime = '<span style="color:red">'
                    . ntr('%d day remaining until account expiration', '%d days remaining until account expiration', $days, $days)
                    . '</span>';
                $domainExpiresDate = '<strong>(' . $domainExpiresDate . ')</strong>';
            }
        } else {
            $domainExpiresDate = '<strong>(' . $domainExpiresDate . ')</strong>';
            $domainRemainingTime = '<span style="color:red">' . tr('Domain account expired.') . '</span>';
            set_page_message(tr('Your account has expired. Please renew your subscription.'), 'warning');
        }

        $tpl->assign([
            'DOMAIN_REMAINING_TIME' => $domainRemainingTime,
            'DOMAIN_EXPIRES_DATE'   => $domainExpiresDate
        ]);
    } else {
        $tpl->assign([
            'DOMAIN_REMAINING_TIME' => '',
            'DOMAIN_EXPIRES_DATE'   => tr('Never')
        ]);
    }
}

/***********************************************************************************************************************
 * Main script
 */

require_once 'imscp-lib.php';

$cfg = iMSCP_Registry::get('config');
check_login('user', $cfg['PREVENT_EXTERNAL_LOGIN_CLIENT']);
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'                 => 'shared/layouts/ui.tpl',
    'page'                   => 'client/index.tpl',
    'page_message'           => 'layout',
    'alternative_domain_url' => 'page',
    'backup_domain_feature'  => 'page',
    'traffic_warning'        => 'page',
    'disk_warning'           => 'page'
]);
$tpl->assign('TR_PAGE_TITLE', tr('Client / General / Overview'));

generateNavigation($tpl);
client_generateSupportSystemNotices();
client_generateDomainExpiresInformation($tpl);
client_generateFeatureStatus($tpl);

$domainProperties = get_domain_default_props($_SESSION['user_id']);

list($domainTrafficPercent, $domainTrafficUsage) = client_makeTrafficUsage($domainProperties['domain_id']);

client_generateTrafficUsageBar($tpl, $domainTrafficUsage * 1048576, $domainProperties['domain_traffic_limit'] * 1048576);
client_generateDiskUsageBar($tpl, $domainProperties['domain_disk_usage'], $domainProperties['domain_disk_limit'] * 1048576);

$tpl->assign('CREATE_DATE', tohtml(date($cfg['DATE_FORMAT'], $domainProperties['domain_created'])));

list(
    $subdomainCount, $domainAliasCount, $mailAccountsCount, $ftpAccountsCount, $sqlDatabasesCount, $sqlUsersCount
    ) = get_customer_objects_counts($_SESSION['user_id']);

$tpl->assign([
    'TR_DOMAIN_ACCOUNT'                        => tr('Domain account'),
    'TR_ACCOUNT_NAME'                          => tr('Account name'),
    'TR_DOMAIN_NAME'                           => tr('Domain name'),
    'DOMAIN_NAME'                              => tohtml(decode_idna($domainProperties['domain_name'])),
    'TR_DOMAIN_ALTERNATIVE_URL'                => tr('Alternative URL to reach your website'),
    'TR_CREATE_DATE'                           => tr('Creation date'),
    'TR_DOMAIN_EXPIRES_DATE'                   => tr('Domain expiration date'),
    'TR_FEATURE'                               => tr('Feature'),
    'TR_FEATURE_STATUS'                        => tr('Status'),
    'TR_DOMAIN_FEATURE'                        => tr('Domain'),
    'TR_DOMAIN_ALIASES_FEATURE'                => tr('Domain aliases'),
    'DOMAIN_ALIASES_FEATURE_STATUS'            => gen_num_limit_msg($domainAliasCount, $domainProperties['domain_alias_limit']),
    'SUBDOMAINS_FEATURE_STATUS'                => gen_num_limit_msg($subdomainCount, $domainProperties['domain_subd_limit']),
    'TR_SUBDOMAINS_FEATURE'                    => tr('Subdomains'),
    'TR_FTP_ACCOUNTS_FEATURE'                  => tr('FTP accounts'),
    'FTP_ACCOUNTS_FEATURE_STATUS'              => gen_num_limit_msg($ftpAccountsCount, $domainProperties['domain_ftpacc_limit']),
    'TR_MAIL_ACCOUNTS_FEATURE'                 => tr('Mail accounts'),
    'MAIL_ACCOUNTS_FEATURE_STATUS'             => gen_num_limit_msg($mailAccountsCount, $domainProperties['domain_mailacc_limit']),
    'TR_MAIL_QUOTA'                            => tr('Mail quota'),
    'EMAIL_QUOTA_STATUS'                       => gen_mail_quota_limit_mgs(),
    'TR_SQL_DATABASES_FEATURE'                 => tr('SQL databases'),
    'SQL_DATABASE_FEATURE_STATUS'              => gen_num_limit_msg($sqlDatabasesCount, $domainProperties['domain_sqld_limit']),
    'TR_SQL_USERS_FEATURE'                     => tr('SQL users'),
    'SQL_USERS_FEATURE_STATUS'                 => gen_num_limit_msg($sqlUsersCount, $domainProperties['domain_sqlu_limit']),
    'TR_PHP_SUPPORT_FEATURE'                   => tr('PHP'),
    'TR_PHP_DIRECTIVES_EDITOR_SUPPORT_FEATURE' => tr('PHP Editor'),
    'TR_CGI_SUPPORT_FEATURE'                   => tr('CGI'),
    'TR_CUSTOM_DNS_RECORDS_FEATURE'            => tr('Custom DNS records'),
    'TR_EXTERNAL_MAIL_SERVER_FEATURE'          => tr('External mail feature'),
    'TR_APP_INSTALLER_FEATURE'                 => tr('Software installer'),
    'TR_BACKUP_FEATURE'                        => tr('Backup'),
    'TR_WEBSTATS_FEATURE'                      => tr('Web statistics'),
    'TR_TRAFFIC_USAGE'                         => tr('Monthly traffic usage'),
    'TR_DISK_USAGE'                            => tr('Disk usage'),
    'TR_DISK_USAGE_DETAIL'                     => tr('Disk usage detail'),
    'TR_DISK_FILE_USAGE'                       => tr('File usage'),
    'DISK_FILESIZE'                            => bytesHuman($domainProperties['domain_disk_file']),
    'TR_DISK_DATABASE_USAGE'                   => tr('Database usage'),
    'DISK_SQLSIZE'                             => bytesHuman($domainProperties['domain_disk_sql']),
    'TR_DISK_MAIL_USAGE'                       => tr('Mail usage'),
    'DISK_MAILSIZE'                            => bytesHuman($domainProperties['domain_disk_mail'])
]);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
