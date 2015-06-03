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
 * Functions
 */

/**
 * Genrate statistics entry for the given user
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $adminId User unique identifier
 * @return void
 */
function _generateUserStatistics($tpl, $adminId)
{
	list(
		$adminName, $domainId, $web, $ftp, $smtp, $pop3, $trafficUsageBytes, $diskspaceUsageBytes
	) = shared_getCustomerStats($adminId);

	list(
		$usub_current, $usub_max, $uals_current, $uals_max, $umail_current, $umail_max, $uftp_current, $uftp_max,
		$usql_db_current, $usql_db_max, $usql_user_current, $usql_user_max, $trafficMaxMebimytes, $diskspaceMaxMebibytes
	) = shared_getCustomerProps($adminId);

	$trafficLimitBytes = $trafficMaxMebimytes * 1048576;
	$diskspaceLimitBytes = $diskspaceMaxMebibytes * 1048576;
	$trafficUsagePercent = make_usage_vals($trafficUsageBytes, $trafficLimitBytes);
	$diskspaceUsagePercent = make_usage_vals($diskspaceUsageBytes, $diskspaceLimitBytes);

	$tpl->assign(array(
		'USER_NAME' => tohtml(decode_idna($adminName)),
		'USER_ID' => tohtml($adminId),
		'TRAFF_PERCENT' => tohtml($trafficUsagePercent),
		'TRAFF_MSG' => ($trafficLimitBytes)
			? tohtml(tr('%s of %s', bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes)))
			: tohtml(tr('%s of unlimited', bytesHuman($trafficUsageBytes))),
		'DISK_PERCENT' => tohtml($diskspaceUsagePercent),
		'DISK_MSG' => ($diskspaceLimitBytes)
			? tohtml(tr('%s of %s', bytesHuman($diskspaceUsageBytes), bytesHuman($diskspaceLimitBytes)))
			: tohtml(tr('%s of unlimited', bytesHuman($diskspaceUsageBytes))),
		'WEB' => tohtml(bytesHuman($web)),
		'FTP' => tohtml(bytesHuman($ftp)),
		'SMTP' => tohtml(bytesHuman($smtp)),
		'POP3' => tohtml(bytesHuman($pop3)),
		'SUB_MSG' => ($usub_max)
			? tohtml(tr('%d of %s', $usub_current, translate_limit_value($usub_max)))
			: tohtml(translate_limit_value($usub_max)),
		'ALS_MSG' => ($uals_max)
			? tohtml(tr('%d of %s', $uals_current, translate_limit_value($uals_max)))
			: tohtml(translate_limit_value($uals_max)),
		'MAIL_MSG' => ($umail_max)
			? tohtml(tr('%d of %s', $umail_current, translate_limit_value($umail_max)))
			: tohtml(translate_limit_value($umail_max)),
		'FTP_MSG' => ($uftp_max)
			? tohtml(tr('%d of %s', $uftp_current, translate_limit_value($uftp_max)))
			: tohtml(translate_limit_value($uftp_max)),
		'SQL_DB_MSG' => ($usql_db_max)
			? tohtml(tr('%d of %s', $usql_db_current, translate_limit_value($usql_db_max)))
			: tohtml(translate_limit_value($usql_db_max)),
		'SQL_USER_MSG' => ($usql_user_max)
			? tohtml(tr('%1$d of %2$d', $usql_user_current, translate_limit_value($usql_user_max)))
			: tohtml(translate_limit_value($usql_user_max))
	));
}

/**
 * Generates page
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $resellerId Reseller unique identifier
 * @return void
 */
function generatePage($tpl, $resellerId)
{
	$stmt = exec_query(
		'SELECT admin_id FROM admin WHERE created_by = ?', $resellerId
	);

	if ($stmt->rowCount()) {
		while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			_generateUserStatistics($tpl, $row['admin_id']);
			$tpl->parse('RESELLER_USER_STATISTICS_BLOCK', '.reseller_user_statistics_block');
		}
	} else {
		$tpl->assign('RESELLER_USER_STATISTICS_BLOCK', '');
	}
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

$eventManager = iMSCP_Events_Aggregator::getInstance();
$eventManager->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

if(isset($_GET['reseller_id'])) {
	$resellerId = intval($_GET['reseller_id']);
	$_SESSION['stats_reseller_id'] = $resellerId;
} elseif(isset($_SESSION['stats_reseller_id'])) {
	redirectTo('reseller_user_statistics.php?reseller_id=' . $_SESSION['stats_reseller_id']);
	exit;
} else {
	showBadRequestErrorPage();
	exit;
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
	'layout' => 'shared/layouts/ui.tpl',
	'page' => 'admin/reseller_user_statistics.tpl',
	'page_message' => 'layout',
	'reseller_user_statistics_block' => 'page'
));

$tpl->assign(array(
	'TR_PAGE_TITLE' => tohtml(tr('Admin / Statistics / Reseller Statistics / User Statistics')),
	'TR_USERNAME' => tohtml(tr('User')),
	'TR_TRAFF' => tohtml(tr('Traffic usage')),
	'TR_DISK' => tohtml(tr('Disk usage')),
	'TR_WEB' => tohtml(tr('HTTP traffic')),
	'TR_FTP_TRAFF' => tohtml(tr('FTP traffic')),
	'TR_SMTP' => tohtml(tr('SMTP traffic')),
	'TR_POP3' => tohtml(tr('POP3/IMAP traffic')),
	'TR_SUBDOMAIN' => tohtml(tr('Subdomains')),
	'TR_ALIAS' => tohtml(tr('Aliases')),
	'TR_MAIL' => tohtml(tr('Email accounts')),
	'TR_FTP' => tohtml(tr('FTP accounts')),
	'TR_SQL_DB' => tohtml(tr('SQL databases')),
	'TR_SQL_USER' => tohtml(tr('SQL users')),
	'TR_DETAILED_STATS_TOOLTIP' => tohtml(tr('Show detailed statistics for this user'), 'htmlAttr')
));

$eventManager->registerListener('onGetJsTranslations', function ($e) {
	/** @var $e \iMSCP_Events_Event */
	$e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
});

generateNavigation($tpl);
generatePage($tpl, $resellerId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
$eventManager->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
