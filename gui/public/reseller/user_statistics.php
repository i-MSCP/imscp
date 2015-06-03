<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * Script functions
 */

/**
 * Generates statistics for the given user
 *
 * @access private
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $adminId User unique identifier
 * @return void
 */
function _generateUserStatistics($tpl, $adminId)
{
	list(
		$adminName, , $webTraffic, $ftpTraffic, $smtpTraffic, $popImapTraffic, $trafficUsageBytes, $diskspaceUsageBytes
	) = shared_getCustomerStats($adminId);

	list(
		$subCount, $subMax, $alsCount, $alsMax, $mailCount, $mailMax, $ftpUserCount, $FtpUserMax, $sqlDbCount,
		$sqlDbMax, $sqlUserCount, $sqlUserMax, $trafficLimit, $diskspaceLimit
	) = shared_getCustomerProps($adminId);

	$trafficLimitBytes = $trafficLimit * 1048576;
	$diskspaceLimitBytes = $diskspaceLimit * 1048576;

	$trafficPercent = make_usage_vals($trafficUsageBytes, $trafficLimitBytes);
	$diskPercent = make_usage_vals($diskspaceUsageBytes, $diskspaceLimitBytes);

	$tpl->assign(array(
		'USER_ID' => tohtml($adminId),
		'USERNAME' => tohtml(decode_idna($adminName)),
		'TRAFF_PERCENT' => tohtml($trafficPercent),
		'TRAFF_MSG' => ($trafficLimitBytes)
			? tohtml(tr('%1$s / %2$s', bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes)))
			: tohtml(tr('%s / unlimited', bytesHuman($trafficUsageBytes))),
		'DISK_PERCENT' => tohtml($diskPercent),
		'DISK_MSG' => ($diskspaceLimitBytes)
			? tohtml(tr('%1$s / %2$s', bytesHuman($diskspaceUsageBytes), bytesHuman($diskspaceLimitBytes)))
			: tohtml(tr('%s / unlimited', bytesHuman($diskspaceUsageBytes))),
		'WEB' => tohtml(bytesHuman($webTraffic)),
		'FTP' => tohtml(bytesHuman($ftpTraffic)),
		'SMTP' => tohtml(bytesHuman($smtpTraffic)),
		'POP3' => tohtml(bytesHuman($popImapTraffic)),
		'SUB_MSG' => ($subMax)
			? (($subMax > 0) ? tohtml(tr('%1$d / %2$d', $subCount, $subMax))
				: tohtml(tr('disabled'))) : tohtml(tr('%d / unlimited', $subCount)),
		'ALS_MSG' => ($alsMax)
			? (($alsMax > 0) ? tohtml(tr('%1$d / %2$d', $alsCount, $alsMax)) : tohtml(tr('disabled')))
			: tohtml(tr('%d / unlimited', $alsCount)),
		'MAIL_MSG' => ($mailMax)
			? (($mailMax > 0) ? tohtml(tr('%1$d / %2$d', $mailCount, $mailMax)) : tohtml(tr('disabled')))
			: tohtml(tr('%d / unlimited', $mailCount)),
		'FTP_MSG' => ($FtpUserMax)
			? (($FtpUserMax > 0) ? tohtml(tr('%1$d / %2$d', $ftpUserCount, $FtpUserMax)) : tohtml(tr('disabled')))
			: tohtml(tr('%d / unlimited', $ftpUserCount)),
		'SQL_DB_MSG' => ($sqlDbMax)
			? (($sqlDbMax > 0) ? tohtml(tr('%1$d / %2$d', $sqlDbCount, $sqlDbMax)) : tohtml(tr('disabled')))
			: tohtml(tr('%d / unlimited', $sqlDbCount)),
		'SQL_USER_MSG' => ($sqlUserMax)
			? (($sqlUserMax > 0) ? tohtml(tr('%1$d / %2$d', $sqlUserCount, $sqlUserMax)) : tohtml(tr('disabled')))
			: tohtml(tr('%d / unlimited', $sqlUserCount))
	));
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function generatePage($tpl)
{
	$stmt = exec_query('SELECT admin_id FROM admin WHERE created_by = ?', intval($_SESSION['user_id']));

	while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
		_generateUserStatistics($tpl, $row['admin_id']);
		$tpl->parse('USER_STATISTICS_ENTRY_BLOCK', '.user_statistics_entry_block');
	}
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

$eventManager = iMSCP_Events_Aggregator::getInstance();
$eventManager->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

if(resellerHasCustomers()) {
	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/user_statistics.tpl',
		'page_message' => 'layout',
		'user_statistics_entries_block' => 'page',
		'user_statistics_entry_block' => 'user_statistics_entries_block'
	));

	$tpl->assign(array(
		'TR_PAGE_TITLE' => tohtml(tr('Reseller / Statistics / Overview')),
		'TR_USER' => tohtml(tr('User'), 'htmlAttr'),
		'TR_TRAFF' => tohtml(tr('Traffic usage')),
		'TR_DISK' => tohtml(tr('Disk usage')),
		'TR_WEB' => tohtml(tr('Http traffic')),
		'TR_FTP_TRAFF' => tohtml(tr('FTP traffic')),
		'TR_SMTP' => tohtml(tr('SMTP traffic')),
		'TR_POP3' => tohtml(tr('POP3/IMAP')),
		'TR_SUBDOMAIN' => tohtml(tr('Subdomains')),
		'TR_ALIAS' => tohtml(tr('Domain aliases')),
		'TR_MAIL' => tohtml(tr('Email')),
		'TR_FTP' => tohtml(tr('FTP accounts')),
		'TR_SQL_DB' => tohtml(tr('SQL databases')),
		'TR_SQL_USER' => tohtml(tr('SQL users')),
		'TR_USER_TOOLTIP' => tohtml(tr('Show detailed statistics for this user'), 'htmlAttr')
	));

	$eventManager->registerListener('onGetJsTranslations', function ($e) {
		/** @var $e \iMSCP_Events_Event */
		$e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
	});

	generateNavigation($tpl);
	generatePage($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');
	$eventManager->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));
	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
