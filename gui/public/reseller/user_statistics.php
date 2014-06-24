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
 * @subpackage  Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
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
		$domainName, $domainId, $webTraffic, $ftpTraffic, $smtpTraffic, $popImapTraffic, $trafficUsageBytes,
		$diskspaceUsageBytes
	) = shared_getCustomerStats($domainId);

	list(
		$subCount, $subMax, $alsCount, $alsMax, $mailCount, $mailMax, $ftpUserCount, $FtpUserMax, $sqlDbCount,
		$sqlDbMax, $sqlUserCount, $sqlUserMax, $trafficLimit, $diskspaceLimit
	) = shared_getCustomerProps($domainId);

	$trafficLimitBytes = $trafficLimit * 1048576;
	$diskspaceLimitBytes = $diskspaceLimit * 1048576;

	$trafficPercent = make_usage_vals($trafficUsageBytes, $trafficLimitBytes);
	$diskPercent = make_usage_vals($diskspaceUsageBytes, $diskspaceLimitBytes);

	$tpl->assign(
		array(
			'DOMAIN_NAME' => tohtml(decode_idna($domainName)),
			'DOMAIN_ID' => $domainId,
			'TRAFF_PERCENT' => $trafficPercent,
			'TRAFF_MSG' => ($trafficLimitBytes)
				? tr('%1$s / %2$s', bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes))
				: tr('%s / unlimited', bytesHuman($trafficUsageBytes)),
			'DISK_PERCENT' => $diskPercent,
			'DISK_MSG' => ($diskspaceLimitBytes)
				? tr('%1$s / %2$s', bytesHuman($diskspaceUsageBytes), bytesHuman($diskspaceLimitBytes))
				: tr('%s / unlimited', bytesHuman($diskspaceUsageBytes)),
			'WEB' => bytesHuman($webTraffic),
			'FTP' => bytesHuman($ftpTraffic),
			'SMTP' => bytesHuman($smtpTraffic),
			'POP3' => bytesHuman($popImapTraffic),
			'SUB_MSG' => ($subMax)
				? (($subMax > 0) ? tr('%1$d / %2$d', $subCount, $subMax)
					: tr('disabled')) : tr('%d / unlimited', $subCount),
			'ALS_MSG' => ($alsMax)
				? (($alsMax > 0) ? tr('%1$d / %2$d', $alsCount, $alsMax) : tr('disabled'))
				: tr('%d / unlimited', $alsCount),
			'MAIL_MSG' => ($mailMax)
				? (($mailMax > 0) ? tr('%1$d / %2$d', $mailCount, $mailMax) : tr('disabled'))
				: tr('%d / unlimited', $mailCount),
			'FTP_MSG' => ($FtpUserMax)
				? (($FtpUserMax > 0) ? tr('%1$d / %2$d', $ftpUserCount, $FtpUserMax) : tr('disabled'))
				: tr('%d / unlimited', $ftpUserCount),
			'SQL_DB_MSG' => ($sqlDbMax)
				? (($sqlDbMax > 0) ? tr('%1$d / %2$d', $sqlDbCount, $sqlDbMax) : tr('disabled'))
				: tr('%d / unlimited', $sqlDbCount),
			'SQL_USER_MSG' => ($sqlUserMax)
				? (($sqlUserMax > 0) ? tr('%1$d / %2$d', $sqlUserCount, $sqlUserMax) : tr('disabled'))
				: tr('%d / unlimited', $sqlUserCount)
		)
	);
}

/**
 * Generate page
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function reseller_generatePage($tpl)
{
	$query = 'SELECT domain_id FROM domain INNER JOIN admin ON (admin_id = domain_admin_id) WHERE created_by = ?';
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

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/user_statistics.tpl',
		'page_message' => 'layout',
		'domain_statistics_entries_block' => 'page',
		'domain_statistics_entry_block' => 'domain_statistics_entries_block'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr("Reseller / Statistics / Overview"),
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
		'TR_MAIL' => tr('Email accounts'),
		'TR_FTP' => tr('FTP accounts'),
		'TR_SQL_DB' => tr('SQL databases'),
		'TR_SQL_USER' => tr('SQL users'),
		'VALUE_NAME' => tohtml($_SESSION['user_logged']),
		'VALUE_RID' => $_SESSION['user_id'],
		'TR_DOMAIN_TOOLTIP' => tr('Show detailed statistics for this domain'),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()
	)
);

generateNavigation($tpl);
reseller_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
