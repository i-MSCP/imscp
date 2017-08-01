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
 * Generates support questions notice for administrator
 *
 * @return void
 */
function admin_generateSupportQuestionsMessage()
{
    $ticketsCount = exec_query(
        'SELECT COUNT(ticket_id) FROM tickets WHERE ticket_to = ? AND ticket_status IN (1, 2) AND ticket_reply = 0',
        $_SESSION['user_id']
    )->fetchRow(PDO::FETCH_COLUMN);

    if ($ticketsCount > 0) {
        set_page_message(
            ntr('You have a new support ticket.', 'You have %d new support tickets.', $ticketsCount), 'static_info'
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
    if (!iMSCP_Registry::get('config')['CHECK_FOR_UPDATES']
        || stripos(iMSCP_Registry::get('config')['Version'], 'git') !== false
    ) {
        return;
    }

    $updateVersion = iMSCP_Update_Version::getInstance();

    if ($updateVersion->isAvailableUpdate()) {
        set_page_message('<a href="imscp_updates.php" class="link">' . tr('A new i-MSCP version is available') . '</a>', 'static_info');
    } elseif (($error = $updateVersion->getError())) {
        set_page_message($error, 'error');
    }
}

/**
 * Generates admin general informations
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function admin_getAdminGeneralInfo($tpl)
{
    $where = "mail_type NOT LIKE '%catchall%'";

    if (!iMSCP_Registry::get('config')['COUNT_DEFAULT_EMAIL_ADDRESSES']) {
        # A default mail account is composed of a name matching with:
        # - abuse, hostmaster, postmaster or webmaster for a domain
        # - webmaster for a subdomain
        # and is set as forward mail account. If the customeer turn a default
        # mail account into a normal mail account, it is no longer seen as
        # default mail account.
        $where .= "
            AND ! (
                (
                    mail_acc IN('abuse', 'hostmaster', 'postmaster', 'webmaster')
                    AND
                    mail_type IN('" . MT_NORMAL_FORWARD . "', '" . MT_ALIAS_FORWARD . "')
                )    
                OR
                (
                    mail_acc = 'webmaster'
                    AND
                    mail_type IN('" . MT_SUBDOM_FORWARD . "', '" . MT_ALSSUB_FORWARD . "')
                )
            )
        ";
    }

    $tpl->assign([
        'ADMIN_USERS'     => tohtml(records_count('admin', 'admin_type', 'admin')),
        'RESELLER_USERS'  => tohtml(records_count('admin', 'admin_type', 'reseller')),
        'NORMAL_USERS'    => tohtml(records_count('admin', 'admin_type', 'user')),
        'DOMAINS'         => tohtml(records_count('domain')),
        'SUBDOMAINS'      => tohtml(records_count('subdomain') + records_count('subdomain_alias', 'subdomain_alias_id')),
        'DOMAINS_ALIASES' => tohtml(records_count('domain_aliasses')),
        'MAIL_ACCOUNTS'   => tohtml(records_count('mail_users', $where)),
        'FTP_ACCOUNTS'    => tohtml(records_count('ftp_users')),
        'SQL_DATABASES'   => tohtml(records_count('sql_database')),
        'SQL_USERS'       => tohtml(get_sql_user_count())
    ]);
}

/**
 * Generates server traffic bar
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function admin_generateServerTrafficInfo($tpl)
{
    $cfg = iMSCP_Registry::get('config');
    $trafficLimitBytes = filter_digits($cfg['SERVER_TRAFFIC_LIMIT']) * 1048576;
    $trafficWarningBytes = filter_digits($cfg['SERVER_TRAFFIC_WARN']) * 1048576;

    if (!$trafficWarningBytes) {
        $trafficWarningBytes = $trafficLimitBytes;
    }

    // Get server traffic usage in bytes for the current month
    $stmt = exec_query(
        '
            SELECT SUM(bytes_in) + SUM(bytes_out) AS serverTrafficUsage
            FROM server_traffic
            WHERE traff_time BETWEEN ?
            AND ?
        ',
        [getFirstDayOfMonth(), getLastDayOfMonth()]
    );

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow();
        $trafficUsageBytes = $row['serverTrafficUsage'];
    } else {
        $trafficUsageBytes = 0;
    }

    // Get traffic usage in percent
    $trafficUsagePercent = make_usage_vals($trafficUsageBytes, $trafficLimitBytes);

    if ($trafficLimitBytes) {
        $trafficMessage = tohtml(tr('%s%% [%s / %s]', $trafficUsagePercent, bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes)));
    } else {
        $trafficMessage = tohtml(tr('%s%% [%s / ∞]', $trafficUsagePercent, bytesHuman($trafficUsageBytes)));
    }

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
        'TRAFFIC_WARNING' => tohtml($trafficMessage),
        'TRAFFIC_PERCENT' => tohtml($trafficUsagePercent, 'htmlAttr')
    ]);
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);
check_login('admin', iMSCP_Registry::get('config')['PREVENT_EXTERNAL_LOGIN_ADMIN']);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
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
generatePageMessage($tpl);
admin_generateSupportQuestionsMessage();
admin_generateUpdateMessages();
admin_getAdminGeneralInfo($tpl);
admin_generateServerTrafficInfo($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
