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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2018 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

use iMSCP_Registry as Registry;
use iMSCP\TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate mail quota limit msg
 *
 * @param int $customerId Customer unique identifier
 * @return array
 */
function reseller_gen_mail_quota_limit_mgs($customerId)
{
    $domainProps = get_domain_default_props($customerId, $_SESSION['user_id']);
    $mailQuota = exec_query('SELECT IFNULL(SUM(quota), 0) FROM mail_users WHERE domain_id = ?', [
        $domainProps['domain_id']
    ])->fetchColumn();

    return [bytesHuman($mailQuota), ($domainProps['mail_quota'] == 0) ? '∞' : bytesHuman($domainProps['mail_quota'])];
}

/**
 * Generates page
 *
 * @param TemplateEngine $tpl Template instance engine
 * @param int $domainId Domain unique identifier
 * @return void
 */
function reseller_generatePage($tpl, $domainId)
{
    $stmt = exec_query(
        '
            SELECT *
            FROM domain
            JOIN admin ON(admin_id = domain_admin_id)
            WHERE domain_id = ?
            AND created_by = ?
        ',
        [$domainId, $_SESSION['user_id']]
    );

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $domainData = $stmt->fetch();

    // Domain IP address info
    $stmt = exec_query('SELECT ip_number FROM server_ips WHERE ip_id = ?', [$domainData['domain_ip_id']]);

    if ($stmt->rowCount()) {
        $row = $stmt->fetch();
        $domainIpAddr = $row['ip_number'];
    } else {
        $domainIpAddr = tr('Not found.');
    }

    // Domain status
    if ($domainData['domain_status'] == 'ok' || $domainData['domain_status'] == 'disabled' ||
        $domainData['domain_status'] == 'todelete' || $domainData['domain_status'] == 'toadd' ||
        $domainData['domain_status'] == 'torestore' || $domainData['domain_status'] == 'tochange' ||
        $domainData['domain_status'] == 'toenable' || $domainData['domain_status'] == 'todisable'
    ) {
        $domainStatus = '<span style="color:green">' . tohtml(translate_dmn_status($domainData['domain_status'])) . '</span>';
    } else {
        $domainStatus = '<span style="font-weight:bold;color:red">' . $domainData['domain_status'] . "</span>";
    }

    // Get total monthly traffic usage in bytes
    $trafficData = getClientMonthlyTrafficStats($domainId);
    $trafficUsageBytes = $trafficData[4];
    unset($trafficData);

    $trafficLimitBytes = $domainData['domain_traffic_limit'] * 1048576;
    $diskspaceLimitBytes = $domainData['domain_disk_limit'] * 1048576;

    // Get usages in percent
    $trafficUsagePercent = getPercentUsage($trafficUsageBytes, $trafficLimitBytes);
    $diskspaceUsagePercent = getPercentUsage($domainData['domain_disk_usage'], $diskspaceLimitBytes);

    // Get Email quota info
    list($quota, $quotaLimit) = reseller_gen_mail_quota_limit_mgs($domainData['domain_admin_id']);

    # Features
    $trEnabled = '<span style="color:green">' . tr('Enabled') . '</span>';
    $trDisabled = '<span style="color:red">' . tr('Disabled') . '</span>';
    $tpl->assign([
        'DOMAIN_ID'                  => $domainId,
        'VL_DOMAIN_NAME'             => tohtml(decode_idna($domainData['domain_name'])),
        'VL_DOMAIN_IP'               => tohtml(($domainIpAddr == '0.0.0.0') ? tr('Any') : $domainIpAddr),
        'VL_STATUS'                  => $domainStatus,
        'VL_PHP_SUPP'                => ($domainData['domain_php'] == 'yes') ? $trEnabled : $trDisabled,
        'VL_PHP_EDITOR_SUPP'         => ($domainData['phpini_perm_system'] == 'yes') ? $trEnabled : $trDisabled,
        'VL_CGI_SUPP'                => ($domainData['domain_cgi'] == 'yes') ? $trEnabled : $trDisabled,
        'VL_DNS_SUPP'                => ($domainData['domain_dns'] == 'yes') ? $trEnabled : $trDisabled,
        'VL_EXT_MAIL_SUPP'           => ($domainData['domain_external_mail'] == 'yes') ? $trEnabled : $trDisabled,
        'VL_SOFTWARE_SUPP'           => ($domainData['domain_software_allowed'] == 'yes') ? $trEnabled : $trDisabled,
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
        'VL_DOMALIAS_ACCOUNTS_LIMIT' => translate_limit_value($domainData['domain_alias_limit'])
    ]);
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('reseller');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptStart);

if (!isset($_GET['domain_id'])) {
    redirectTo('users.php');
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'        => 'shared/layouts/ui.tpl',
    'page'          => 'reseller/domain_details.tpl',
    'page_message' => 'layout'
]);
$tpl->assign([
    'TR_PAGE_TITLE'        => tr('Reseller / Customers / Overview / Domain Details'),
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
    'TR_SOFTWARE_SUPP'     => tr('Software installer'),
    'TR_EDIT'              => tr('Edit'),
    'TR_BACK'              => tr('Back')
]);

generateNavigation($tpl);
reseller_generatePage($tpl, $_GET['domain_id']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
