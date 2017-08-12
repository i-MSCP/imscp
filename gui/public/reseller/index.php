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
 * Generates support questions notice for reseller
 *
 * Notice reseller about any new support questions and answers.
 *
 * @return void
 */
function generateSupportQuestionsMessage()
{
    $ticketsCount = exec_query(
        'SELECT count(ticket_id) FROM tickets WHERE ticket_to = ? AND ticket_status IN (1, 4) AND ticket_reply = 0',
        $_SESSION['user_id']
    )->fetchRow(PDO::FETCH_COLUMN);

    if ($ticketsCount > 0) {
        set_page_message(
            ntr('You have a new support ticket.', 'You have %d new support tickets.', $ticketsCount, $ticketsCount),
            'static_info'
        );
    }
}

/**
 * Generates message for new domain aliases orders.
 *
 * @return void
 */
function generateOrdersAliasesMessage()
{
    $stmt = exec_query(
        '
            SELECT COUNT(alias_id) AS cnt
            FROM domain_aliasses
            JOIN domain USING(domain_id)
            JOIN admin ON(admin_id = domain_admin_id)
            WHERE alias_status = ?
            AND created_by = ?
        ',
        ['ordered', $_SESSION['user_id']]
    );
    $row = $stmt->fetchRow();

    if ($row['cnt'] > 0) {
        set_page_message(
            ntr('You have a new domain alias order.', 'You have %d new domain alias orders', $row['cnt']), 'static_info'
        );
    }
}

/**
 * Generates traffic usage bar
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $trafficUsageBytes Current traffic usage
 * @param int $trafficLimitBytes Traffic max usage
 * @return void
 */
function generateTrafficUsageBar($tpl, $trafficUsageBytes, $trafficLimitBytes)
{
    $trafficUsagePercent = make_usage_vals($trafficUsageBytes, $trafficLimitBytes);

    if ($trafficLimitBytes) {
        $trafficUsageData = tr(
            '%s%% [%s / %s]', $trafficUsagePercent, bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes)
        );
    } else {
        $trafficUsageData = tr(
            '%s%% [%s / ∞]', $trafficUsagePercent, bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes)
        );
    }

    $tpl->assign([
        'TRAFFIC_USAGE_DATA' => $trafficUsageData,
        'TRAFFIC_PERCENT'    => $trafficUsagePercent
    ]);
}

/**
 * Generates disk usage bar
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $diskspaceUsageBytes Disk usage
 * @param int $diskspaceLimitBytes Max disk usage
 * @return void
 */
function generateDiskUsageBar($tpl, $diskspaceUsageBytes, $diskspaceLimitBytes)
{
    $diskspaceUsagePercent = make_usage_vals($diskspaceUsageBytes, $diskspaceLimitBytes);

    if ($diskspaceLimitBytes) {
        $diskUsageData = tr(
            '%s%% [%s / %s]', $diskspaceUsagePercent, bytesHuman($diskspaceUsageBytes), bytesHuman($diskspaceLimitBytes)
        );
    } else {
        $diskUsageData = tr('%s%% [%s / ∞]', $diskspaceUsagePercent, bytesHuman($diskspaceUsageBytes));
    }

    $tpl->assign([
        'DISK_USAGE_DATA' => $diskUsageData,
        'DISK_PERCENT'    => $diskspaceUsagePercent
    ]);
}

/**
 * Generates page
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $resellerId Reseller unique identifier
 * @param string $resellerName Reseller name
 * @return void
 */
function generatePage($tpl, $resellerId, $resellerName)
{
    generateSupportQuestionsMessage();
    generateOrdersAliasesMessage();

    $resellerProperties = imscp_getResellerProperties($resellerId);
    $udmnCurrent = get_reseller_domains_count($resellerId);
    $usubCurrent = get_reseller_subdomains_count($resellerId);
    $ualsCurrent = get_reseller_domain_aliases_count($resellerId);
    $umailCurrent = get_reseller_mail_accounts_count($resellerId);
    $uftpCurrent = get_reseller_ftp_users_count($resellerId);
    $usqlDbCurrent = get_reseller_sql_databases_count($resellerId);
    $usqlUserCurrent = get_reseller_sql_users_count($resellerId);

    $stmt = exec_query(
        '
            SELECT
                IFNULL(SUM(domain_disk_usage), 0) AS disk_usage,
                IFNULL(SUM(dtraff_web), 0) + IFNULL(SUM(dtraff_ftp), 0) + IFNULL(SUM(dtraff_mail), 0) +
                IFNULL(SUM(dtraff_pop), 0) AS monthly_traffic
            FROM domain AS t1
            JOIN admin AS t2 ON(t2.admin_id = t1.domain_admin_id)
            LEFT JOIN domain_traffic AS t3 ON(t3.domain_id = t1.domain_id AND t3.dtraff_time BETWEEN ? AND ?)
            WHERE created_by = ?
        ',
        [getFirstDayOfMonth(), getLastDayOfMonth(), $_SESSION['user_id']]
    );

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

    // Convert into Mib values
    $monthlyTrafficLimit = $resellerProperties['max_traff_amnt'] * 1024 * 1024;
    $diskUsageLimit = $resellerProperties['max_disk_amnt'] * 1024 * 1024;

    generateTrafficUsageBar($tpl, $row['monthly_traffic'], $monthlyTrafficLimit);
    generateDiskUsageBar($tpl, $row['disk_usage'], $diskUsageLimit);

    if ($monthlyTrafficLimit > 0 && $row['monthly_traffic'] > $monthlyTrafficLimit) {
        $tpl->assign('TR_TRAFFIC_WARNING', tr('You are exceeding your monthly traffic limit.'));
    } else {
        $tpl->assign('TRAFFIC_WARNING_MESSAGE', '');
    }

    if ($diskUsageLimit > 0 && $row['disk_usage'] > $diskUsageLimit) {
        $tpl->assign('TR_DISK_WARNING', tr('You are exceeding your disk space limit.'));
    } else {
        $tpl->assign('DISK_WARNING_MESSAGE', '');
    }

    $tpl->assign([
        'TR_ACCOUNT_LIMITS' => tr('Account limits'),
        'TR_FEATURES'       => tr('Features'),
        'DOMAINS'           => tr('Domain accounts'),
        'SUBDOMAINS'        => tr('Subdomains'),
        'ALIASES'           => tr('Domain aliases'),
        'MAIL_ACCOUNTS'     => tr('Mail accounts'),
        'TR_FTP_ACCOUNTS'   => tr('FTP accounts'),
        'SQL_DATABASES'     => tr('SQL databases'),
        'SQL_USERS'         => tr('SQL users'),
        'TR_TRAFFIC_USAGE'  => tr('Monthly traffic usage'),
        'TR_DISK_USAGE'     => tr('Disk usage'),
        'RESELLER_NAME'     => tohtml($resellerName),
        'DMN_MSG'           => ($resellerProperties['max_dmn_cnt'])
            ? tr('%s / %s', $udmnCurrent, $resellerProperties['max_dmn_cnt']) : tr('%s / ∞', $udmnCurrent),
        'SUB_MSG'           => ($resellerProperties['max_sub_cnt'] > 0)
            ? tr('%s / %s', $usubCurrent, $resellerProperties['max_sub_cnt'])
            : (($resellerProperties['max_sub_cnt'] == '-1') ? '-' : tr('%s / ∞', $usubCurrent)),
        'ALS_MSG'           => ($resellerProperties['max_als_cnt'] > 0)
            ? tr('%s / %s', $ualsCurrent, $resellerProperties['max_als_cnt'])
            : (($resellerProperties['max_als_cnt'] == '-1') ? '-' : tr('%s / ∞', $ualsCurrent)),
        'MAIL_MSG'          => ($resellerProperties['max_mail_cnt'] > 0)
            ? tr('%s / %s', $umailCurrent, $resellerProperties['max_mail_cnt'])
            : (($resellerProperties['max_mail_cnt'] == '-1') ? '-' : tr('%s / ∞', $umailCurrent)),
        'FTP_MSG'           => ($resellerProperties['max_ftp_cnt'] > 0)
            ? tr('%s / %s', $uftpCurrent, $resellerProperties['max_ftp_cnt'])
            : (($resellerProperties['max_ftp_cnt'] == '-1') ? '-' : tr('%s / ∞', $uftpCurrent)),
        'SQL_DB_MSG'        => ($resellerProperties['max_sql_db_cnt'] > 0)
            ? tr('%s / %s', $usqlDbCurrent, $resellerProperties['max_sql_db_cnt'])
            : (($resellerProperties['max_sql_db_cnt'] == '-1') ? '-' : tr('%s / ∞', $usqlDbCurrent)),
        'SQL_USER_MSG'      => ($resellerProperties['max_sql_db_cnt'] > 0)
            ? tr('%s / %s', $usqlUserCurrent, $resellerProperties['max_sql_user_cnt'])
            : (($resellerProperties['max_sql_user_cnt'] == '-1') ? '-' : tr('%s / ∞', $usqlUserCurrent)),
        'TR_SUPPORT'        => tr('Support system'),
        'SUPPORT_STATUS'    => ($resellerProperties['support_system'] == 'yes')
            ? '<span style="color:green;">' . tr('Enabled') . '</span>'
            : '<span style="color:red;">' . tr('Disabled') . '</span>',
        'TR_PHP_EDITOR'     => tr('PHP Editor'),
        'PHP_EDITOR_STATUS' => ($resellerProperties['php_ini_system'] == 'yes')
            ? '<span style="color:green;">' . tr('Enabled') . '</span>'
            : '<span style="color:red;">' . tr('Disabled') . '</span>',
        'TR_APS'            => tr('Software installer'),
        'APS_STATUS'        => ($resellerProperties['software_allowed'] == 'yes')
            ? '<span style="color:green;">' . tr('Enabled') . '</span>'
            : '<span style="color:red;">' . tr('Disabled') . '</span>'
    ]);
}

/***********************************************************************************************************************
 * Main script
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);
check_login('reseller', iMSCP_Registry::get('config')['PREVENT_EXTERNAL_LOGIN_RESELLER']);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'                  => 'shared/layouts/ui.tpl',
    'page'                    => 'reseller/index.tpl',
    'page_message'            => 'layout',
    'traffic_warning_message' => 'page',
    'disk_warning_message'    => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE' => tr('Reseller / General / Overview'),
    'TR_SAVE'       => tr('Save')
]);

generateNavigation($tpl);
generatePage($tpl, $_SESSION['user_id'], $_SESSION['user_logged']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
