<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Reseller
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2012 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @link		http://i-mscp.net
 */

/***********************************************************************************
 * Script functions
 */

/**
 * Generates statistics entry for the given domain.
 *
 * @access private
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId Domain unique identifier
 * @return void
 */
function _reseller_generateDomainStatisticsEntry($tpl, $domainId)
{
	list(
		$domainName, $domainId, $web, $ftp, $smtp, $pop3, $trafficUsageBytes, $diskspaceUsageBytes
		) = generate_user_traffic($domainId);

	list(
		$usub_current, $usub_max, $uals_current, $uals_max, $umail_current, $umail_max, $uftp_current, $uftp_max,
		$usql_db_current, $usql_db_max, $usql_user_current, $usql_user_max, $trafficLimit, $diskspaceLimit
		) = generate_user_props($domainId);

	$trafficLimitBytes = $trafficLimit * 1048576;
	$diskspaceLimitBytes = $diskspaceLimit * 1048576;

	$trafficPercent = make_usage_vals($trafficUsageBytes, $trafficLimitBytes);
	$diskPercent = make_usage_vals($diskspaceUsageBytes, $diskspaceLimitBytes);

	$tpl->assign(
		array(
			'DOMAIN_NAME' => tohtml(decode_idna($domainName)),
			'DOMAIN_ID' => $domainId,
			'TRAFF_PERCENT' => $trafficPercent,
			'MONTH' => date('m'),
			'YEAR' => date('y'),
			'TRAFF_MSG' => ($trafficLimitBytes)
				? tr('%1$s of %2$s', bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes))
				: tr('%s of unlimited', bytesHuman($trafficUsageBytes)),
			'DISK_PERCENT' => $diskPercent,
			'DISK_MSG' => ($diskspaceLimitBytes)
				? tr('%1$s of %2$s', bytesHuman($diskspaceUsageBytes), bytesHuman($diskspaceLimitBytes))
				: tr('%s of unlimited', bytesHuman($diskspaceUsageBytes)),
			'WEB' => bytesHuman($web),
			'FTP' => bytesHuman($ftp),
			'SMTP' => bytesHuman($smtp),
			'POP3' => bytesHuman($pop3),
			'SUB_MSG' => ($usub_max)
				? (($usub_max > 0) ? tr('%1$d of %2$d', $usub_current, $usub_max)
					: tr('disabled')) : tr('%d of unlimited', $usub_current),
			'ALS_MSG' => ($uals_max)
				? (($uals_max > 0) ? tr('%1$d of %2$d', $uals_current, $uals_max) : tr('disabled'))
				: tr('%d of unlimited', $uals_current),
			'MAIL_MSG' => ($umail_max)
				? (($umail_max > 0) ? tr('%1$d of %2$d', $umail_current, $umail_max) : tr('disabled'))
				: tr('%d of unlimited', $umail_current),
			'FTP_MSG' => ($uftp_max)
				? (($uftp_max > 0) ? tr('%1$d of %2$d', $uftp_current, $uftp_max) : tr('disabled'))
				: tr('%d of unlimited', $uftp_current),
			'SQL_DB_MSG' => ($usql_db_max)
				? (($usql_db_max > 0) ? tr('%1$d of %2$d', $usql_db_current, $usql_db_max) : tr('disabled'))
				: tr('%d of unlimited', $usql_db_current),
			'SQL_USER_MSG' => ($usql_user_max)
				? (($usql_user_max > 0) ? tr('%1$d of %2$d', $usql_user_current, $usql_user_max) : tr('disabled'))
				: tr('%d of unlimited', $usql_user_current)));
}

/**
 * Generate page.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function reseller_generatePage($tpl)
{
	$query = 'SELECT `domain_id` FROM `domain` WHERE `domain_created_id` = ?';
	$stmt = exec_query($query, $_SESSION['user_id']);

	if ($stmt->rowCount()) {
		foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $domainId) {
			_reseller_generateDomainStatisticsEntry($tpl, $domainId);
			$tpl->parse('DOMAIN_STATISTICS_ENTRY_BLOCK', '.domain_statistics_entry_block');
		}
	} else {
		$tpl->assign('DOMAIN_STATISTICS_ENTRIES_BLOCK', '');
		set_page_message(tr('No domain statistics to display.'), 'info');
	}
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/reseller_user_statistics.tpl',
		'page_message' => 'layout',
		'domain_statistics_entries_block' => 'page',
		'domain_statistics_entry_block' => 'domain_statistics_entries_block'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr("i-MSCP - Reseller / Statistics /  Customers statistics"),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_DOMAIN_NAME' => tr('Domain'),
		'TR_TRAFF' => tr('Traffic usage'),
		'TR_DISK' => tr('Disk usage'),
		'TR_WEB' => tr('Http traffic'),
		'TR_FTP_TRAFF' => tr('FTP traffic'),
		'TR_SMTP' => tr('SMTP traffic'),
		'TR_POP3' => tr('POP3/IMAP traffic'),
		'TR_SUBDOMAIN' => tr('Subdomains'),
		'TR_ALIAS' => tr('Aliases'),
		'TR_MAIL' => tr('Mail accounts'),
		'TR_FTP' => tr('FTP accounts'),
		'TR_SQL_DB' => tr('SQL databases'),
		'TR_SQL_USER' => tr('SQL users'),
		'VALUE_NAME' => tohtml($_SESSION['user_logged']),
		'VALUE_RID' => $_SESSION['user_id'],
		'TR_DOMAIN_TOOLTIP' => tr('Show detailed statistics for this domain'),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()));

generateNavigation($tpl);
reseller_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
