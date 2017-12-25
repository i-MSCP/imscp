<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Events as Events;
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;

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
        [$_SESSION['user_id']]
    )->fetchColumn();

    if ($ticketsCount > 0) {
        set_page_message(
            ntr('You have a new support ticket.', 'You have %d new support tickets.', $ticketsCount, $ticketsCount),
            'static_info'
        );
    }
}

/**
 * Generates message for new domain aliases orders
 *
 * @return void
 */
function generateOrdersAliasesMessage()
{
    $countAliasOrders = exec_query(
        "
            SELECT COUNT(alias_id)
            FROM domain_aliasses
            JOIN domain USING(domain_id)
            JOIN admin ON(admin_id = domain_admin_id)
            WHERE alias_status = 'ordered'
            AND created_by = ?
        ",
        [$_SESSION['user_id']]
    )->fetchColumn();

    if ($countAliasOrders > 0) {
        set_page_message(
            ntr('You have a new domain alias order.', 'You have %d new domain alias orders', $countAliasOrders), 'static_info'
        );
    }
}

/**
 * Generates traffic usage bar
 *
 * @param TemplateEngine $tpl Template engine
 * @param int $trafficUsageBytes Current traffic usage
 * @param int $trafficLimitBytes Traffic max usage
 * @return void
 */
function generateTrafficUsageBar($tpl, $trafficUsageBytes, $trafficLimitBytes)
{
    $trafficUsagePercent = getPercentUsage($trafficUsageBytes, $trafficLimitBytes);
    $trafficUsageData = ($trafficLimitBytes > 0)
        ? sprintf('[%s / %s]', bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes))
        : sprintf('[%s / ∞]', bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes));
    $tpl->assign([
        'TRAFFIC_PERCENT_WIDTH' => tohtml($trafficUsagePercent, 'htmlAttr'),
        'TRAFFIC_PERCENT'       => tohtml($trafficUsagePercent),
        'TRAFFIC_USAGE_DATA'    => tohtml($trafficUsageData)
    ]);
}

/**
 * Generates disk usage bar
 *
 * @param TemplateEngine $tpl Template engine
 * @param int $diskspaceUsageBytes Disk usage
 * @param int $diskspaceLimitBytes Max disk usage
 * @return void
 */
function generateDiskUsageBar($tpl, $diskspaceUsageBytes, $diskspaceLimitBytes)
{
    $diskspaceUsagePercent = getPercentUsage($diskspaceUsageBytes, $diskspaceLimitBytes);
    $diskUsageData = ($diskspaceLimitBytes > 0)
        ? sprintf('[%s / %s]', bytesHuman($diskspaceUsageBytes), bytesHuman($diskspaceLimitBytes))
        : sprintf('[%s / ∞]', bytesHuman($diskspaceUsageBytes));
    $tpl->assign([
        'DISK_PERCENT_WIDTH' => tohtml($diskspaceUsagePercent, 'htmlAttr'),
        'DISK_PERCENT'       => tohtml($diskspaceUsagePercent),
        'DISK_USAGE_DATA'    => tohtml($diskUsageData)
    ]);
}

/**
 * Generates page
 *
 * @param TemplateEngine $tpl Template engine
 * @param int $resellerId Reseller unique identifier
 * @param string $resellerName Reseller name
 * @return void
 */
function generatePage($tpl, $resellerId, $resellerName)
{
    generateSupportQuestionsMessage();
    generateOrdersAliasesMessage();

    $resellerProperties = imscp_getResellerProperties($resellerId);
    $domainsCount = get_reseller_domains_count($resellerId);
    $subdomainsCount = get_reseller_subdomains_count($resellerId);
    $domainAliasesCount = get_reseller_domain_aliases_count($resellerId);
    $mailAccountsCount = get_reseller_mail_accounts_count($resellerId);
    $ftpUsersCount = get_reseller_ftp_users_count($resellerId);
    $sqlDatabasesCount = get_reseller_sql_databases_count($resellerId);
    $sqlUsersCount = get_reseller_sql_users_count($resellerId);

    $domainIds = exec_query(
        'SELECT domain_id FROM domain JOIN admin ON(admin_id = domain_admin_id) WHERE created_by = ?',
        [$_SESSION['user_id']]
    )->fetchAll(PDO::FETCH_COLUMN);

    $totalConsumedMonthlyTraffic = 0;

    if (!empty($domainIds)) {
        $firstDayOfMonth = getFirstDayOfMonth();
        $lastDayOfMonth = getLastDayOfMonth();

        /** @var \iMSCP\Database\ResultSet $stmt */
        $stmt = Registry::get('iMSCP_Application')->getDatabase()->prepare(
            '
                SELECT
                    IFNULL(SUM(dtraff_web), 0) +
                    IFNULL(SUM(dtraff_ftp), 0) +
                    IFNULL(SUM(dtraff_mail), 0) +
                    IFNULL(SUM(dtraff_pop), 0)
                FROM domain_traffic
                WHERE domain_id = ?
                AND dtraff_time BETWEEN ? AND ?
            '
        );
        $stmt->bindParam(1, $domainId);
        $stmt->bindParam(2, $firstDayOfMonth);
        $stmt->bindParam(3, $lastDayOfMonth);

        /** @noinspection PhpUnusedLocalVariableInspection $domainId */
        foreach ($domainIds as $domainId) {
            $stmt->execute();
            $totalConsumedMonthlyTraffic += $stmt->fetchColumn();
        }
    }

    $monthlyTrafficLimit = $resellerProperties['max_traff_amnt'] * 1048576;

    generateTrafficUsageBar($tpl, $totalConsumedMonthlyTraffic, $monthlyTrafficLimit);

    if ($monthlyTrafficLimit > 0
        && $totalConsumedMonthlyTraffic > $monthlyTrafficLimit
    ) {
        $tpl->assign('TR_TRAFFIC_WARNING', tohtml(tr('You are exceeding your monthly traffic limit.')));
    } else {
        $tpl->assign('TRAFFIC_WARNING_MESSAGE', '');
    }

    $totalDiskUsage = exec_query(
        '
            SELECT IFNULL(SUM(domain_disk_usage), 0) AS disk_usage
            FROM domain AS t1
            JOIN admin AS t2 ON(t2.admin_id = t1.domain_admin_id)
            WHERE created_by = ?
        ',
        [$_SESSION['user_id']]
    )->fetchColumn();
    $diskUsageLimit = $resellerProperties['max_disk_amnt'] * 1048576;
    generateDiskUsageBar($tpl, $totalDiskUsage, $diskUsageLimit);

    if ($diskUsageLimit > 0
        && $totalDiskUsage > $diskUsageLimit
    ) {
        $tpl->assign('TR_DISK_WARNING', tohtml(tr('You are exceeding your disk space limit.')));
    } else {
        $tpl->assign('DISK_WARNING_MESSAGE', '');
    }

    $tpl->assign([
        'TR_ACCOUNT_LIMITS' => tohtml(tr('Account limits')),
        'TR_FEATURES'       => tohtml(tr('Features')),
        'DOMAINS'           => tohtml(tr('Domain accounts')),
        'SUBDOMAINS'        => tohtml(tr('Subdomains')),
        'ALIASES'           => tohtml(tr('Domain aliases')),
        'MAIL_ACCOUNTS'     => tohtml(tr('Mail accounts')),
        'TR_FTP_ACCOUNTS'   => tohtml(tr('FTP accounts')),
        'SQL_DATABASES'     => tohtml(tr('SQL databases')),
        'SQL_USERS'         => tohtml(tr('SQL users')),
        'RESELLER_NAME'     => tohtml($resellerName),
        'DMN_MSG'           => tohtml(($resellerProperties['max_dmn_cnt'])
            ? sprintf('%s / %s', $domainsCount, $resellerProperties['max_dmn_cnt']) : sprintf('%s / ∞', $domainsCount)),
        'SUB_MSG'           => tohtml(($resellerProperties['max_sub_cnt'] > 0)
            ? sprintf('%s / %s', $subdomainsCount, $resellerProperties['max_sub_cnt'])
            : (($resellerProperties['max_sub_cnt'] == '-1') ? '-' : sprintf('%s / ∞', $subdomainsCount))),
        'ALS_MSG'           => tohtml(($resellerProperties['max_als_cnt'] > 0)
            ? sprintf('%s / %s', $domainAliasesCount, $resellerProperties['max_als_cnt'])
            : (($resellerProperties['max_als_cnt'] == '-1') ? '-' : sprintf('%s / ∞', $domainAliasesCount))),
        'MAIL_MSG'          => tohtml(($resellerProperties['max_mail_cnt'] > 0)
            ? sprintf('%s / %s', $mailAccountsCount, $resellerProperties['max_mail_cnt'])
            : (($resellerProperties['max_mail_cnt'] == '-1') ? '-' : sprintf('%s / ∞', $mailAccountsCount))),
        'FTP_MSG'           => tohtml(($resellerProperties['max_ftp_cnt'] > 0)
            ? sprintf('%s / %s', $ftpUsersCount, $resellerProperties['max_ftp_cnt'])
            : (($resellerProperties['max_ftp_cnt'] == '-1') ? '-' : sprintf('%s / ∞', $ftpUsersCount))),
        'SQL_DB_MSG'        => tohtml(($resellerProperties['max_sql_db_cnt'] > 0)
            ? sprintf('%s / %s', $sqlDatabasesCount, $resellerProperties['max_sql_db_cnt'])
            : (($resellerProperties['max_sql_db_cnt'] == '-1') ? '-' : sprintf('%s / ∞', $sqlDatabasesCount))),
        'SQL_USER_MSG'      => tohtml(($resellerProperties['max_sql_db_cnt'] > 0)
            ? sprintf('%s / %s', $sqlUsersCount, $resellerProperties['max_sql_user_cnt'])
            : (($resellerProperties['max_sql_user_cnt'] == '-1') ? '-' : sprintf('%s / ∞', $sqlUsersCount))),
        'TR_SUPPORT'        => tohtml(tr('Support system')),
        'SUPPORT_STATUS'    => ($resellerProperties['support_system'] == 'yes')
            ? '<span style="color:green;">' . tohtml(tr('Enabled')) . '</span>'
            : '<span style="color:red;">' . tohtml(tr('Disabled')) . '</span>',
        'TR_PHP_EDITOR'     => tohtml(tr('PHP Editor')),
        'PHP_EDITOR_STATUS' => ($resellerProperties['php_ini_system'] == 'yes')
            ? '<span style="color:green;">' . tohtml(tr('Enabled')) . '</span>'
            : '<span style="color:red;">' . tohtml(tr('Disabled')) . '</span>',
        'TR_APS'            => tr('Software installer'),
        'APS_STATUS'        => ($resellerProperties['software_allowed'] == 'yes')
            ? '<span style="color:green;">' . tohtml(tr('Enabled')) . '</span>'
            : '<span style="color:red;">' . tohtml(tr('Disabled')) . '</span>',
        'TR_TRAFFIC_USAGE'  => tohtml(tr('Monthly traffic usage')),
        'TR_DISK_USAGE'     => tohtml(tr('Disk usage')),
    ]);
}

/***********************************************************************************************************************
 * Main script
 */

require 'imscp-lib.php';

check_login('reseller', Registry::get('config')['PREVENT_EXTERNAL_LOGIN_RESELLER']);
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onResellerScriptStart);

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                  => 'shared/layouts/ui.tpl',
    'page'                    => 'reseller/index.tpl',
    'page_message'            => 'layout',
    'traffic_warning_message' => 'page',
    'disk_warning_message'    => 'page'
]);
$tpl->assign('TR_PAGE_TITLE', tohtml(tr('Reseller / General / Overview')));

generateNavigation($tpl);
generatePage($tpl, $_SESSION['user_id'], $_SESSION['user_logged']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
