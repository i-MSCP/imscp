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
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Admin
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2014 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @link		http://i-mscp.net
 */

/*******************************************************************************
 * Script functions
 */

/**
 * Generates page.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $resellerId Reseller unique identifier
 * @return void
 */
function admin_generatePage($tpl, $resellerId)
{
	$stmt = exec_query(
		'
			SELECT
				domain_id
			FROM
				domain
			INNER JOIN
				admin ON (admin_id = domain_admin_id)
			WHERE
				created_by = ?
		',
		$resellerId
	);

	if ($stmt->rowCount()) {
		foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $domainId) {
			_admin_generateDomainStatisticsEntry($tpl, $domainId);
			$tpl->parse('DOMAIN_STATISTICS_ENTRY_BLOCK', '.domain_statistics_entry_block');
		}
	} else {
		$tpl->assign('DOMAIN_STATISTICS_ENTRIES_BLOCK', '');
		set_page_message(tr('No domain statistics to display for this reseller.'), 'info');
	}
}

/**
 * Genrate statistics entry for the given domain.
 *
 * @access private
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId Domain unique identifier
 * @return void
 */
function _admin_generateDomainStatisticsEntry($tpl, $domainId)
{
	list(
		$domain_name, $domainId, $web, $ftp, $smtp, $pop3, $trafficUsageBytes, $diskspaceUsageBytes
	) = shared_getCustomerStats($domainId);

	list(
		$usub_current, $usub_max, $uals_current, $uals_max, $umail_current, $umail_max, $uftp_current, $uftp_max,
		$usql_db_current, $usql_db_max, $usql_user_current, $usql_user_max, $trafficMaxMebimytes, $diskspaceMaxMebibytes
	) = shared_getCustomerProps($domainId);


	$trafficLimitBytes = $trafficMaxMebimytes * 1048576;
	$diskspaceLimitBytes = $diskspaceMaxMebibytes * 1048576;

	$trafficUsagePercent = make_usage_vals($trafficUsageBytes, $trafficLimitBytes);
	$diskspaceUsagePercent = make_usage_vals($diskspaceUsageBytes, $diskspaceLimitBytes);

	$tpl->assign(
		array(
			'DOMAIN_NAME' => tohtml(decode_idna($domain_name)),
			'DOMAIN_ID' => $domainId,
			'TRAFF_PERCENT' => $trafficUsagePercent,
			'TRAFF_MSG' => ($trafficLimitBytes)
				? tr('%s of %s', bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes))
				: tr('%s of unlimited', bytesHuman($trafficUsageBytes)),
			'DISK_PERCENT' => $diskspaceUsagePercent,
			'DISK_MSG' => ($diskspaceLimitBytes)
				? tr('%s of %s', bytesHuman($diskspaceUsageBytes), bytesHuman($diskspaceLimitBytes))
				: tr('%s of unlimited', bytesHuman($diskspaceUsageBytes)),
			'WEB' => bytesHuman($web),
			'FTP' => bytesHuman($ftp),
			'SMTP' => bytesHuman($smtp),
			'POP3' => bytesHuman($pop3),
			'SUB_MSG' => ($usub_max)
				? tr('%d of %s', $usub_current, translate_limit_value($usub_max))
				: translate_limit_value($usub_max),
			'ALS_MSG' => ($uals_max)
				? tr('%d of %s', $uals_current, translate_limit_value($uals_max))
				: translate_limit_value($uals_max),
			'MAIL_MSG' => ($umail_max)
				? tr('%d of %s', $umail_current, translate_limit_value($umail_max))
				: translate_limit_value($umail_max),
			'FTP_MSG' => ($uftp_max)
				? tr('%d of %s', $uftp_current, translate_limit_value($uftp_max))
				: translate_limit_value($uftp_max),
			'SQL_DB_MSG' => ($usql_db_max)
				? tr('%d of %s', $usql_db_current, translate_limit_value($usql_db_max))
				: translate_limit_value($usql_db_max),
			'SQL_USER_MSG' => ($usql_user_max)
				? tr('%1$d of %2$d', $usql_user_current, translate_limit_value($usql_user_max))
				: translate_limit_value($usql_user_max)
		)
	);
}

/*******************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

$resellerId = (isset($_GET['rid'])) ? intval($_GET['rid']) : (isset($_SESSION['rid']) ? $_SESSION['rid'] : null);

if (!$resellerId) {
	showBadRequestErrorPage();
	exit; // Useless but avoid IDE warning about possible undefined variable
} else {
	$_SESSION['rid'] = $resellerId;
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/reseller_user_statistics.tpl',
		'page_message' => 'layout',
		'domain_statistics_entries_block' => 'page',
		'domain_statistics_entry_block' => 'domain_statistics_entries_block'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Statistics / Reseller Statistics / Customer Statistics'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_DOMAIN_NAME' => tr('Domain name'),
		'TR_TRAFF' => tr('Traffic usage'),
		'TR_DISK' => tr('Disk usage'),
		'TR_WEB' => tr('HTTP traffic'),
		'TR_FTP_TRAFF' => tr('FTP traffic'),
		'TR_SMTP' => tr('SMTP traffic'),
		'TR_POP3' => tr('POP3/IMAP traffic'),
		'TR_SUBDOMAIN' => tr('Subdomains'),
		'TR_ALIAS' => tr('Aliases'),
		'TR_MAIL' => tr('Email accounts'),
		'TR_FTP' => tr('FTP accounts'),
		'TR_SQL_DB' => tr('SQL databases'),
		'TR_SQL_USER' => tr('SQL users'),
		'VALUE_RID' => $resellerId,
		'TR_DOMAIN_TOOLTIP' => tr('Show detailed statistics for this domain'),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()));

generateNavigation($tpl);
admin_generatePage($tpl, $resellerId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
