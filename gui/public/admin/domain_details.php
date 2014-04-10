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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate mail quota limit msg
 *
 * @param int $customerId Customer unique identifier
 * @return array
 */
function admin_gen_mail_quota_limit_mgs($customerId)
{
	$mainDmnProps = get_domain_default_props($customerId);

	$stmt = exec_query(
		'SELECT SUM(quota) AS quota FROM mail_users WHERE domain_id = ? AND quota IS NOT NULL',
		$mainDmnProps['domain_id']
	);

	if ($mainDmnProps['mail_quota'] == 0) {
		return array(bytesHuman($stmt->fields['quota']), tr('Unlimited'));
	} else {
		return array(bytesHuman($stmt->fields['quota']), bytesHuman($mainDmnProps['mail_quota']));
	}
}

/**
 * Generates page
 *
 * @param iMSCP_pTemplate $tpl Template instance engine
 * @param int $domainId Domain unique identifier
 * @return void
 */
function admin_generatePage($tpl, $domainId)
{
	$stmt = exec_query('SELECT domain_admin_id FROM domain WHERE domain_id = ?', $domainId);

	if (!$stmt->rowCount()) {
		showBadRequestErrorPage();
	}

	$domainAdminId = $stmt->fields['domain_admin_id'];

	$domainProperties = get_domain_default_props($domainAdminId);

	// Domain IP address info
	$stmt = exec_query("SELECT ip_number FROM server_ips WHERE ip_id = ?", $domainProperties['domain_ip_id']);

	if (!$stmt->rowCount()) {
		$domainIpAddr = tr('Not found.');
	} else {
		$domainIpAddr = $stmt->fields['ip_number'];
	}

	$domainStatus = $domainProperties['domain_status'];

	// Domain status

	if (
		$domainStatus == 'ok' || $domainStatus == 'disabled' ||
		$domainStatus == 'todelete' || $domainStatus == 'toadd' ||
		$domainStatus == 'torestore' || $domainStatus == 'tochange' ||
		$domainStatus == 'toenable' || $domainStatus == 'todisable'
	) {
		$domainStatus = '<span style="color:green">' . tohtml(translate_dmn_status($domainStatus)) . '</span>';
	} else {
		$domainStatus = '<b><font size="3" color="red">' . $domainStatus . "</font></b>";
	}

	// Get total domain traffic usage in bytes

	$query = "
		SELECT
			IFNULL(SUM(dtraff_web), 0) AS dtraff_web, IFNULL(SUM(dtraff_ftp), 0) AS dtraff_ftp,
			IFNULL(SUM(dtraff_mail), 0) AS dtraff_mail, IFNULL(SUM(dtraff_pop), 0) AS dtraff_pop
		FROM
			domain_traffic
		WHERE
			domain_id = ?
		AND
			dtraff_time BETWEEN ? AND ?
	";
	$stmt = exec_query($query, array($domainProperties['domain_id'], getFirstDayOfMonth(), getLastDayOfMonth()));

	if($stmt->rowCount()) {
		$trafficUsageBytes = $stmt->fields['dtraff_web'] + $stmt->fields['dtraff_ftp'] + $stmt->fields['dtraff_mail'] +
			$stmt->fields['dtraff_pop'];
	} else {
		$trafficUsageBytes = 0;
	}

	// Get limits in bytes
	$trafficLimitBytes = $domainProperties['domain_traffic_limit'] * 1048576;
	$diskspaceLimitBytes = $domainProperties['domain_disk_limit'] * 1048576;

	// Get usages in percent
	$trafficUsagePercent = make_usage_vals($trafficUsageBytes, $trafficLimitBytes);
	$diskspaceUsagePercent = make_usage_vals($domainProperties['domain_disk_usage'], $diskspaceLimitBytes);

	// Get Email quota info
	list($quota, $quotaLimit) = admin_gen_mail_quota_limit_mgs($domainAdminId);

	# Features

	$tpl->assign(
		array(
			'DOMAIN_ID' => $domainId,
			'VL_DOMAIN_NAME' => tohtml(decode_idna($domainProperties['domain_name'])),
			'VL_DOMAIN_IP' => tohtml($domainIpAddr),
			'VL_STATUS' => $domainStatus,
			'VL_PHP_SUPP' => translate_limit_value($domainProperties['domain_php']),
			'VL_PHP_EDITOR_SUPP' => translate_limit_value($domainProperties['phpini_perm_system']),
			'VL_CGI_SUPP' => translate_limit_value($domainProperties['domain_cgi']),
			'VL_DNS_SUPP' => translate_limit_value($domainProperties['domain_dns']),
			'VL_EXT_MAIL_SUPP' => translate_limit_value($domainProperties['domain_external_mail']),
			'VL_SOFTWARE_SUPP' => translate_limit_value($domainProperties['domain_software_allowed']),
			'VL_BACKUP_SUP' => translate_limit_value($domainProperties['allowbackup']),
			'VL_TRAFFIC_PERCENT' => $trafficUsagePercent,
			'VL_TRAFFIC_USED' => bytesHuman($trafficUsageBytes),
			'VL_TRAFFIC_LIMIT' => bytesHuman($trafficLimitBytes),
			'VL_DISK_PERCENT' => $diskspaceUsagePercent,
			'VL_DISK_USED' => bytesHuman($domainProperties['domain_disk_usage']),
			'VL_DISK_LIMIT' => bytesHuman($diskspaceLimitBytes),
			'VL_MAIL_ACCOUNTS_USED' => get_domain_running_mail_acc_cnt($domainId),
			'VL_MAIL_ACCOUNTS_LIMIT' => translate_limit_value($domainProperties['domain_mailacc_limit']),
			'VL_MAIL_QUOTA_USED' => $quota,
			'VL_MAIL_QUOTA_LIMIT' => ($domainProperties['domain_mailacc_limit'] != '-1') ? $quotaLimit : tr('Disabled'),
			'VL_FTP_ACCOUNTS_USED' => get_customer_running_ftp_acc_cnt($domainAdminId),
			'VL_FTP_ACCOUNTS_LIMIT' => translate_limit_value($domainProperties['domain_ftpacc_limit']),
			'VL_SQL_DB_ACCOUNTS_USED' => get_domain_running_sqld_acc_cnt($domainId),
			'VL_SQL_DB_ACCOUNTS_LIMIT' => translate_limit_value($domainProperties['domain_sqld_limit']),
			'VL_SQL_USER_ACCOUNTS_USED' => get_domain_running_sqlu_acc_cnt($domainId),
			'VL_SQL_USER_ACCOUNTS_LIMIT' => translate_limit_value($domainProperties['domain_sqlu_limit']),
			'VL_SUBDOM_ACCOUNTS_USED' => get_domain_running_sub_cnt($domainId),
			'VL_SUBDOM_ACCOUNTS_LIMIT' => translate_limit_value($domainProperties['domain_subd_limit']),
			'VL_DOMALIAS_ACCOUNTS_USED' => get_domain_running_als_cnt($domainId),
			'VL_DOMALIAS_ACCOUNTS_LIMIT' => translate_limit_value($domainProperties['domain_alias_limit']),
		)
	);
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

// Get user id that comes for manage domain
if (!isset($_GET['domain_id'])) {
	redirectTo('manage_users.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/domain_details.tpl',
		'page_messages' => 'layout',
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Users / Overview / Domain Details'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_DOMAIN_DETAILS' => tr('Domain details'),
		'TR_DOMAIN_NAME' => tr('Domain name'),
		'TR_DOMAIN_IP' => tr('Domain IP'),
		'TR_STATUS' => tr('Status'),
		'TR_PHP_SUPP' => tr('PHP'),
		'TR_PHP_EDITOR_SUPP' => tr('PHP Editor'),
		'TR_CGI_SUPP' => tr('CGI'),
		'TR_DNS_SUPP' => tr('Custom DNS records'),
		'TR_EXT_MAIL_SUPP' => tr('Ext. mail server'),
		'TR_BACKUP_SUPP' => tr('Backup'),
		'TR_TRAFFIC' => tr('Traffic'),
		'TR_DISK' => tr('Disk'),
		'TR_FEATURE' => tr('Feature'),
		'TR_USED' => tr('Used'),
		'TR_LIMIT' => tr('Limit'),
		'TR_SUBDOM_ACCOUNTS' => tr('Subdomains'),
		'TR_DOMALIAS_ACCOUNTS' => tr('Domain aliases'),
		'TR_MAIL_ACCOUNTS' => tr('Email accounts'),
		'TR_MAIL_QUOTA' => tr('Email quota'),
		'TR_FTP_ACCOUNTS' => tr('FTP accounts'),
		'TR_SQL_DB_ACCOUNTS' => tr('SQL databases'),
		'TR_SQL_USER_ACCOUNTS' => tr('SQL users'),
		'TR_UPDATE_DATA' => tr('Submit changes'),
		'TR_SOFTWARE_SUPP' => tr('Software installer'),
		'TR_BACK' => tr('Back')
	)
);

generateNavigation($tpl);
admin_generatePage($tpl, intval($_GET['domain_id']));
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(
	iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl)
);

$tpl->prnt();

unsetMessages();
