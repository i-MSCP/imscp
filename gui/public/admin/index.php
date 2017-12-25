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

use iMSCP\Update\UpdateVersion;
use iMSCP_Events as Events;
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generates support questions notice for administrator
 *
 * @return void
 */
function admin_generateSupportQuestionsMessage()
{
    $ticketsCount = exec_query(
        'SELECT COUNT(ticket_id) FROM tickets WHERE ticket_to = ? AND ticket_status IN (1, 2) AND ticket_reply = 0',
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
 * Generates update messages
 *
 * Generates update messages for both database updates and i-MSCP updates.
 *
 * @return void
 */
function admin_generateUpdateMessages()
{
    if (!Registry::get('config')['CHECK_FOR_UPDATES']
        || stripos(Registry::get('config')['Version'], 'git') !== false
    ) {
        return;
    }

    $updateVersion = new UpdateVersion();

    if ($updateVersion->isAvailableUpdate()) {
        set_page_message('<a href="imscp_updates.php" class="link">' . tr('A new i-MSCP version is available') . '</a>', 'static_info');
    } elseif (($error = $updateVersion->getError())) {
        set_page_message($error, 'error');
    }
}

/**
 * Generates admin general informations
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function admin_getAdminGeneralInfo($tpl)
{
    $tpl->assign([
        'ADMIN_USERS'     => tohtml(get_administrators_count()),
        'RESELLER_USERS'  => tohtml(get_resellers_count()),
        'NORMAL_USERS'    => tohtml(get_customers_count()),
        'DOMAINS'         => tohtml(get_domains_count()),
        'SUBDOMAINS'      => tohtml(get_subdomains_count()),
        'DOMAINS_ALIASES' => tohtml(get_domain_aliases_count()),
        'MAIL_ACCOUNTS'   => tohtml(get_mail_accounts_count())
            . (!Registry::get('config')['COUNT_DEFAULT_EMAIL_ADDRESSES']
                ? ' (' . tohtml('Excl. default mail accounts') . ')' : ''
            ),
        'FTP_ACCOUNTS'    => tohtml(get_ftp_users_count()),
        'SQL_DATABASES'   => tohtml(get_sql_databases_count()),
        'SQL_USERS'       => tohtml(get_sql_users_count())
    ]);
}

/**
 * Generates server traffic bar
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function admin_generateServerTrafficInfo($tpl)
{
    $cfg = Registry::get('config');
    $trafficLimitBytes = filter_digits($cfg['SERVER_TRAFFIC_LIMIT']) * 1048576;
    $trafficWarningBytes = filter_digits($cfg['SERVER_TRAFFIC_WARN']) * 1048576;

    if (!$trafficWarningBytes) {
        $trafficWarningBytes = $trafficLimitBytes;
    }

    // Get server traffic usage in bytes for the current month
    $trafficUsageBytes = $stmt = exec_query(
        '
            SELECT IFNULL(SUM(bytes_in), 0) + IFNULL(SUM(bytes_out), 0)
            FROM server_traffic
            WHERE traff_time BETWEEN ? AND ?
        ',
        [getFirstDayOfMonth(), getLastDayOfMonth()]
    )->fetchColumn();

    // Get traffic usage in percent
    $trafficUsagePercent = getPercentUsage($trafficUsageBytes, $trafficLimitBytes);
    $trafficMessage = ($trafficLimitBytes > 0)
        ? sprintf('[%s / %s]', bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes))
        : sprintf('[%s / ∞]', bytesHuman($trafficUsageBytes));

    // traffic warning 
    if ($trafficUsageBytes
        && (
            $trafficWarningBytes && $trafficUsageBytes > $trafficWarningBytes
            || $trafficLimitBytes && $trafficUsageBytes > $trafficLimitBytes
        )
    ) {
        set_page_message(tr('You are exceeding the monthly server traffic limit.'), 'static_warning');
    }

    $tpl->assign([
        'TRAFFIC_WARNING'       => tohtml($trafficMessage),
        'TRAFFIC_PERCENT_WIDTH' => tohtml($trafficUsagePercent, 'htmlAttr'),
        'TRAFFIC_PERCENT'       => tohtml($trafficUsagePercent)
    ]);
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin', Registry::get('config')['PREVENT_EXTERNAL_LOGIN_ADMIN']);
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAdminScriptStart);

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                  => 'shared/layouts/ui.tpl',
    'page'                    => 'admin/index.tpl',
    'page_message'            => 'layout',
    'traffic_warning_message' => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'      => tohtml(tr('Admin / General / Overview')),
    'TR_PROPERTIES'      => tohtml(tr('Properties')),
    'TR_VALUES'          => tohtml(tr('Values')),
    'TR_ADMIN_USERS'     => tohtml(tr('Admin users')),
    'TR_RESELLER_USERS'  => tohtml(tr('Reseller users')),
    'TR_NORMAL_USERS'    => tohtml(tr('Client users')),
    'TR_DOMAINS'         => tohtml(tr('Domains')),
    'TR_SUBDOMAINS'      => tohtml(tr('Subdomains')),
    'TR_DOMAINS_ALIASES' => tohtml(tr('Domain aliases')),
    'TR_MAIL_ACCOUNTS'   => tohtml(tr('Mail accounts')),
    'TR_FTP_ACCOUNTS'    => tohtml(tr('FTP accounts')),
    'TR_SQL_DATABASES'   => tohtml(tr('SQL databases')),
    'TR_SQL_USERS'       => tohtml(tr('SQL users')),
    'TR_SERVER_TRAFFIC'  => tohtml(tr('Monthly server traffic'))
]);

generateNavigation($tpl);
admin_generateSupportQuestionsMessage();
admin_generateUpdateMessages();
admin_getAdminGeneralInfo($tpl);
admin_generateServerTrafficInfo($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
