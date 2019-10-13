<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\TemplateEngine;

/**
 * Generate mail quota limit msg
 *
 * @param int $customerId Customer unique identifier
 * @return array
 */
function admin_gen_mail_quota_limit_mgs($customerId)
{
    $domainProps = get_domain_default_props($customerId);
    $mailQuota = exec_query(
        'SELECT IFNULL(SUM(quota), 0) FROM mail_users WHERE domain_id = ?', $domainProps['domain_id']
    )->fetchRow(PDO::FETCH_COLUMN);

    return [bytesHuman($mailQuota), ($domainProps['mail_quota'] == 0) ? '∞' : bytesHuman($domainProps['mail_quota'])];
}

/**
 * Generates page
 *
 * @param TemplateEngine $tpl Template instance engine
 * @param int $domainId Domain unique identifier
 * @return void
 */
function admin_generatePage($tpl, $domainId)
{
    $stmt = exec_query('SELECT * FROM domain WHERE domain_id = ?', $domainId);

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $domainData = $stmt->fetchRow();

    // Domain IP address info
    $stmt = exec_query("SELECT ip_number FROM server_ips WHERE ip_id = ?", $domainData['domain_ip_id']);
    if (!$stmt->rowCount()) {
        $domainIpAddr = tr('Not found.');
    } else {
        $row = $stmt->fetchRow();
        $domainIpAddr = $row['ip_number'];
    }

    // Domain status
    if ($domainData['domain_status'] == 'ok' || $domainData['domain_status'] == 'disabled' ||
        $domainData['domain_status'] == 'todelete' || $domainData['domain_status'] == 'toadd' ||
        $domainData['domain_status'] == 'torestore' || $domainData['domain_status'] == 'tochange' ||
        $domainData['domain_status'] == 'toenable' || $domainData['domain_status'] == 'todisable'
    ) {
        $domainStatus = '<span style="color:green;">' . tohtml(translate_dmn_status($domainData['domain_status'])) . '</span>';
    } else {
        $domainStatus = '<span style="color:red;font-weight: bold;">' . $domainData['domain_status'] . "</font>";
    }

    // Get total monthly traffic usage in bytes
    $trafficData = getClientMonthlyTrafficStats($domainId);
    $trafficUsageBytes = $trafficData[4];
    unset($trafficData);

    // Get limits in bytes
    $trafficLimitBytes = $domainData['domain_traffic_limit'] * 1048576;
    $diskspaceLimitBytes = $domainData['domain_disk_limit'] * 1048576;

    // Get usages in percent
    $trafficUsagePercent = getPercentUsage($trafficUsageBytes, $trafficLimitBytes);
    $diskspaceUsagePercent = getPercentUsage($domainData['domain_disk_usage'], $diskspaceLimitBytes);

    // Get mail quota info
    list($quota, $quotaLimit) = admin_gen_mail_quota_limit_mgs($domainData['domain_admin_id']);

    # Features
    $tpl->assign([
        'DOMAIN_ID'                  => $domainId,
        'VL_DOMAIN_NAME'             => tohtml(decode_idna($domainData['domain_name'])),
        'VL_DOMAIN_IP'               => tohtml(($domainIpAddr == '0.0.0.0') ? tr('Any') : $domainIpAddr),
        'VL_STATUS'                  => $domainStatus,
        'VL_PHP_SUPP'                => translate_limit_value($domainData['domain_php']),
        'VL_PHP_EDITOR_SUPP'         => translate_limit_value($domainData['phpini_perm_system']),
        'VL_CGI_SUPP'                => translate_limit_value($domainData['domain_cgi']),
        'VL_DNS_SUPP'                => translate_limit_value($domainData['domain_dns']),
        'VL_EXT_MAIL_SUPP'           => translate_limit_value($domainData['domain_external_mail']),
        'VL_BACKUP_SUP'              => translate_limit_value($domainData['allowbackup']),
        'VL_TRAFFIC_PERCENT'         => $trafficUsagePercent,
        'VL_TRAFFIC_USED'            => bytesHuman($trafficUsageBytes),
        'VL_TRAFFIC_LIMIT'           => bytesHuman($trafficLimitBytes),
        'VL_DISK_PERCENT'            => $diskspaceUsagePercent,
        'VL_DISK_USED'               => bytesHuman($domainData['domain_disk_usage']),
        'VL_DISK_LIMIT'              => bytesHuman($diskspaceLimitBytes),
        'VL_MAIL_ACCOUNTS_USED'      => get_customer_mail_accounts_count($domainId),
        'VL_MAIL_ACCOUNTS_LIMIT'     => translate_limit_value($domainData['domain_mailacc_limit']),
        'VL_MAIL_QUOTA_USED'         => tohtml($quota),
        'VL_MAIL_QUOTA_LIMIT'        => ($domainData['domain_mailacc_limit'] != '-1') ? $quotaLimit : tr('Disabled'),
        'VL_FTP_ACCOUNTS_USED'       => get_customer_ftp_users_count($domainData['domain_admin_id']),
        'VL_FTP_ACCOUNTS_LIMIT'      => translate_limit_value($domainData['domain_ftpacc_limit']),
        'VL_SQL_DB_ACCOUNTS_USED'    => get_customer_sql_databases_count($domainId),
        'VL_SQL_DB_ACCOUNTS_LIMIT'   => translate_limit_value($domainData['domain_sqld_limit']),
        'VL_SQL_USER_ACCOUNTS_USED'  => get_customer_sql_users_count($domainId),
        'VL_SQL_USER_ACCOUNTS_LIMIT' => translate_limit_value($domainData['domain_sqlu_limit']),
        'VL_SUBDOM_ACCOUNTS_USED'    => get_customer_subdomains_count($domainId),
        'VL_SUBDOM_ACCOUNTS_LIMIT'   => translate_limit_value($domainData['domain_subd_limit']),
        'VL_DOMALIAS_ACCOUNTS_USED'  => get_customer_domain_aliases_count($domainId),
        'VL_DOMALIAS_ACCOUNTS_LIMIT' => translate_limit_value($domainData['domain_alias_limit']),
    ]);
}

require 'imscp-lib.php';

check_login('admin');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptStart);

isset($_GET['domain_id']) or showBadRequestErrorPage();

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'        => 'shared/layouts/ui.tpl',
    'page'          => 'admin/domain_details.tpl',
    'page_message' => 'layout',
]);
$tpl->assign([
        'TR_PAGE_TITLE'        => tr('Admin / Users / Overview / Domain Details'),
        'TR_DOMAIN_DETAILS'    => tr('Domain details'),
        'TR_DOMAIN_NAME'       => tr('Domain name'),
        'TR_DOMAIN_IP'         => tr('Domain IP'),
        'TR_STATUS'            => tr('Status'),
        'TR_PHP_SUPP'          => tr('PHP'),
        'TR_PHP_EDITOR_SUPP'   => tr('PHP Editor'),
        'TR_CGI_SUPP'          => tr('CGI'),
        'TR_DNS_SUPP'          => tr('Custom DNS records'),
        'TR_EXT_MAIL_SUPP'     => tr('Ext. mail server'),
        'TR_BACKUP_SUPP'       => tr('Backup'),
        'TR_TRAFFIC'           => tr('Traffic'),
        'TR_DISK'              => tr('Disk'),
        'TR_FEATURE'           => tr('Feature'),
        'TR_USED'              => tr('Used'),
        'TR_LIMIT'             => tr('Limit'),
        'TR_SUBDOM_ACCOUNTS'   => tr('Subdomains'),
        'TR_DOMALIAS_ACCOUNTS' => tr('Domain aliases'),
        'TR_MAIL_ACCOUNTS'     => tr('Mail accounts'),
        'TR_MAIL_QUOTA'        => tr('Mail quota'),
        'TR_FTP_ACCOUNTS'      => tr('FTP accounts'),
        'TR_SQL_DB_ACCOUNTS'   => tr('SQL databases'),
        'TR_SQL_USER_ACCOUNTS' => tr('SQL users'),
        'TR_UPDATE_DATA'       => tr('Submit changes'),
        'TR_BACK'              => tr('Back')]
);

generateNavigation($tpl);
admin_generatePage($tpl, intval($_GET['domain_id']));
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
