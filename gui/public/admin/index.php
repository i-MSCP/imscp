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
 * Generates support questions notice for administrator
 *
 * @return void
 */
function admin_generateSupportQuestionsMessage()
{
    $stmt = exec_query(
        'SELECT COUNT(ticket_id) AS cnt FROM tickets WHERE ticket_to = ? AND ticket_status IN (1, 2) AND ticket_reply = 0',
        $_SESSION['user_id']
    );
    $row = $stmt->fetchRow();

    if ($row['cnt'] > 0) {
        set_page_message(tr('You have received %s new support ticket(s).', "<strong>{$row['cnt']}</strong>"), 'static_info');
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
    $cfg = iMSCP_Registry::get('config');

    if (iMSCP_Update_Database::getInstance()->isAvailableUpdate()) {
        set_page_message('<a href="database_update.php" class="link">' . tr('A database update is available') . '</a>', 'static_info');
    }

    if (!$cfg['CHECK_FOR_UPDATES']) {
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
    $cfg = iMSCP_Registry::get('config');
    $totalMails = records_count('mail_users', 'mail_type NOT RLIKE \'_catchall\'', '');

    if ($cfg['COUNT_DEFAULT_EMAIL_ADDRESSES']) {
        $showTotalMails = $totalMails;
    } else {
        $totalDefaultMails = records_count('mail_users', 'mail_acc', 'abuse');
        $totalDefaultMails += records_count('mail_users', 'mail_acc', 'webmaster');
        $totalDefaultMails += records_count('mail_users', 'mail_acc', 'postmaster');
        $showTotalMails = ($totalMails - $totalDefaultMails) . '/' . $totalMails;
    }

    $tpl->assign(array(
        'ACCOUNT_NAME' => tohtml($_SESSION['user_logged']),
        'ADMIN_USERS' => records_count('admin', 'admin_type', 'admin'),
        'RESELLER_USERS' => records_count('admin', 'admin_type', 'reseller'),
        'NORMAL_USERS' => records_count('admin', 'admin_type', 'user'),
        'DOMAINS' => records_count('domain', '', ''),
        'SUBDOMAINS' => records_count('subdomain', '', '') + records_count('subdomain_alias', 'subdomain_alias_id', '', ''),
        'DOMAINS_ALIASES' => records_count('domain_aliasses', '', ''),
        'MAIL_ACCOUNTS' => $showTotalMails,
        'FTP_ACCOUNTS' => records_count('ftp_users', '', ''),
        'SQL_DATABASES' => records_count('sql_database', '', ''),
        'SQL_USERS' => get_sql_user_count()
    ));
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

    $trafficLimitBytes = intval($cfg['SERVER_TRAFFIC_LIMIT']) * 1048576;
    $trafficWarningBytes = intval($cfg['SERVER_TRAFFIC_WARN']) * 1048576;

    if (!$trafficWarningBytes) {
        $trafficWarningBytes = $trafficLimitBytes;
    }

    // Get server traffic usage value in bytes for the current month
    $stmt = exec_query(
        '
            SELECT IFNULL((SUM(bytes_in) + SUM(bytes_out)), 0) AS serverTrafficUsage FROM server_traffic
            WHERE  traff_time BETWEEN ? AND ?
        ',
        array(getFirstDayOfMonth(), getLastDayOfMonth())
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
        $trafficMessage = tr('%1$s%% [%2$s of %3$s]', $trafficUsagePercent, bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes));
    } else {
        $trafficMessage = tr('%1$s%% [%2$s of unlimited]', $trafficUsagePercent, bytesHuman($trafficUsageBytes));
    }

    // Warning message about traffic
    if ($trafficUsageBytes
        && (
            $trafficWarningBytes && $trafficUsageBytes > $trafficWarningBytes
            || $trafficLimitBytes && $trafficUsageBytes > $trafficLimitBytes
        )
    ) {
        set_page_message(tr('You are exceeding the monthly server traffic limit.'), 'static_warning');
    }

    $tpl->assign(array(
        'TRAFFIC_WARNING' => $trafficMessage,
        'TRAFFIC_PERCENT' => $trafficUsagePercent
    ));
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

$cfg = iMSCP_Registry::get('config');

check_login('admin', $cfg['PREVENT_EXTERNAL_LOGIN_ADMIN']);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'admin/index.tpl',
    'page_message' => 'layout',
    'traffic_warning_message' => 'page'
));

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Admin / General / Overview'),
    'TR_PROPERTIES' => tr('Properties'),
    'TR_VALUES' => tr('Values'),
    'TR_ACCOUNT_NAME' => tr('Account name'),
    'TR_ADMIN_USERS' => tr('Admin users'),
    'TR_RESELLER_USERS' => tr('Reseller users'),
    'TR_NORMAL_USERS' => tr('Normal users'),
    'TR_DOMAINS' => tr('Domains'),
    'TR_SUBDOMAINS' => tr('Subdomains'),
    'TR_DOMAINS_ALIASES' => tr('Domain aliases'),
    'TR_MAIL_ACCOUNTS' => tr('Email accounts'),
    'TR_FTP_ACCOUNTS' => tr('FTP accounts'),
    'TR_SQL_DATABASES' => tr('SQL databases'),
    'TR_SQL_USERS' => tr('SQL users'),
    'TR_SERVER_TRAFFIC' => tr('Server traffic')
));

generateNavigation($tpl);
admin_generateSupportQuestionsMessage();
admin_generateUpdateMessages();
admin_getAdminGeneralInfo($tpl);
admin_generateServerTrafficInfo($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
