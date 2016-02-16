<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * Generates statistics for the given reseller
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $resellerId Reseller unique identifier
 * @param string $resellerName Reseller name
 * @return void
 */
function _generateResellerStatistics($tpl, $resellerId, $resellerName)
{
	$resellerProps = imscp_getResellerProperties($resellerId, true);

	list(
		$udmn_current, , , $usub_current, , , $uals_current, , , $umail_current, , , $uftp_current, , ,
		$usql_db_current, , , $usql_user_current, , , $utraff_current, , , $udisk_current,
	) = generate_reseller_users_props($resellerId);

	$trafficLimitBytes = $resellerProps['max_traff_amnt'] * 1048576;
	$trafficUsageBytes = $resellerProps['current_traff_amnt'] * 1048576;
	$diskspaceLimitBytes = $resellerProps['max_disk_amnt'] * 1048576;
	$diskspaceUsageBytes = $resellerProps['current_disk_amnt'] * 1048576;
	$trafficUsagePercent = make_usage_vals($trafficUsageBytes, $trafficLimitBytes);
	$diskspaceUsagePercent = make_usage_vals($diskspaceUsageBytes, $diskspaceLimitBytes);

	$tpl->assign(array(
		'RESELLER_NAME' => tohtml($resellerName),
		'RESELLER_ID' => tohtml($resellerId),
		'TRAFFIC_PERCENT' => tohtml($trafficUsagePercent),
		'TRAFFIC_MSG' => ($trafficLimitBytes)
			? tohtml(tr(
				'%1$s / %2$s of %3$s', bytesHuman($utraff_current), bytesHuman($trafficUsageBytes),
				bytesHuman($trafficLimitBytes))
			)
			: tohtml(tr('%1$s / %2$s of unlimited', bytesHuman($utraff_current), bytesHuman($trafficUsageBytes))),
		'DISK_PERCENT' => tohtml($diskspaceUsagePercent),
		'DISK_MSG' => ($diskspaceLimitBytes)
			? tohtml(tr(
				'%1$s / %2$s of %3$s', bytesHuman($udisk_current), bytesHuman($diskspaceUsageBytes),
				bytesHuman($diskspaceLimitBytes))
			)
			: tohtml(tr('%1$s / %2$s of unlimited', bytesHuman($udisk_current), bytesHuman($diskspaceUsageBytes))),
		'DMN_MSG' => ($resellerProps['max_dmn_cnt'])
			? tohtml(tr(
				'%1$d / %2$d of %3$d', $udmn_current, $resellerProps['current_dmn_cnt'], $resellerProps['max_dmn_cnt'])
			)
			: tohtml(tr('%1$d / %2$d of unlimited', $udmn_current, $resellerProps['current_dmn_cnt'])),
		'SUB_MSG' => ($resellerProps['max_sub_cnt'] > 0)
			? tohtml(tr(
				'%1$d / %2$d of %3$d', $usub_current, $resellerProps['current_sub_cnt'], $resellerProps['max_sub_cnt'])
			)
			: (
				($resellerProps['max_sub_cnt'] == '-1')
					? tohtml(tr('disabled'))
					: tohtml(tr('%1$d / %2$d of unlimited', $usub_current, $resellerProps['current_sub_cnt']))
			),
		'ALS_MSG' => ($resellerProps['max_als_cnt'] > 0)
			? tohtml(tr(
				'%1$d / %2$d of %3$d', $uals_current, $resellerProps['current_als_cnt'], $resellerProps['max_als_cnt'])
			)
			: (
				($resellerProps['max_als_cnt'] == '-1')
					? tohtml(tr('disabled'))
					: tohtml(tr('%1$d / %2$d of unlimited', $uals_current, $resellerProps['current_als_cnt']))
			),
		'MAIL_MSG' => ($resellerProps['max_mail_cnt'] > 0)
			? tohtml(tr(
				'%1$d / %2$d of %3$d', $umail_current, $resellerProps['current_mail_cnt'],
				$resellerProps['max_mail_cnt'])
			)
			: (
				($resellerProps['max_mail_cnt'] == '-1')
					? tohtml(tr('disabled'))
					: tohtml(tr('%1$d / %2$d of unlimited', $umail_current, $resellerProps['current_mail_cnt']))
			),
		'FTP_MSG' => ($resellerProps['max_ftp_cnt'] > 0)
			? tohtml(tr(
				'%1$d / %2$d of %3$d', $uftp_current, $resellerProps['current_ftp_cnt'], $resellerProps['max_ftp_cnt'])
			)
			: (
				($resellerProps['max_ftp_cnt'] == '-1')
					? tohtml(tr('disabled'))
					: tohtml(tr('%1$d / %2$d of unlimited', $uftp_current, $resellerProps['current_ftp_cnt']))
			),
		'SQL_DB_MSG' => ($resellerProps['max_sql_db_cnt'] > 0)
			? tohtml(tr(
				'%1$d / %2$d of %3$d', $usql_db_current, $resellerProps['current_sql_db_cnt'],
					$resellerProps['max_sql_db_cnt'])
			)
			: (
				($resellerProps['max_sql_db_cnt'] == '-1')
					? tohtml(tr('disabled'))
					: tohtml(tr('%1$d / %2$d of unlimited', $usql_db_current, $resellerProps['current_sql_db_cnt']))
			),
		'SQL_USER_MSG' => ($resellerProps['max_sql_user_cnt'] > 0)
			? tohtml(tr(
				'%1$d / %2$d of %3$d', $usql_user_current, $resellerProps['current_sql_user_cnt'],
					$resellerProps['max_sql_user_cnt'])
			)
			: (
				($resellerProps['max_sql_user_cnt'] == '-1')
					? tohtml(tr('disabled'))
					: tohtml(tr('%1$d / %2$d of unlimited', $usql_user_current, $resellerProps['current_sql_user_cnt']))
			)
	));
}

/**
 * Generates page
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return void
 */
function generatePage($tpl)
{
	$stmt = execute_query("SELECT admin_id, admin_name FROM admin WHERE admin_type = 'reseller'");

	while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
		_generateResellerStatistics($tpl, $row['admin_id'], $row['admin_name']);
		$tpl->parse('RESELLER_STATISTICS_BLOCK', '.reseller_statistics_block');
	}
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

$eventManager = iMSCP_Events_Aggregator::getInstance();
$eventManager->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

if (systemHasResellers()) {
	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/reseller_statistics.tpl',
		'page_message' => 'layout',
		'reseller_statistics_block' => 'page'
	));

	$tpl->assign(array(
		'TR_PAGE_TITLE' => tohtml(tr('Admin / Statistics / Reseller Statistics')),
		'TR_RESELLER_NAME' => tohtml(tr('Reseller')),
		'TR_TRAFFIC_USAGE' => tohtml(tr('Traffic usage')),
		'TR_DISK_USAGE' => tohtml(tr('Disk usage')),
		'TR_DOMAINS' => tohtml(tr('Domains')),
		'TR_SUBDOMAINS' => tohtml(tr('Subdomains')),
		'TR_DOMAIN_ALIASES' => tohtml(tr('Domain aliases')),
		'TR_MAIL_ACCOUNTS' => tohtml(tr('Email accounts')),
		'TR_FTP_ACCOUNTS' => tohtml(tr('FTP accounts')),
		'TR_SQL_DATABASES' => tohtml(tr('SQL databases')),
		'TR_SQL_USERS' => tohtml(tr('SQL users')),
		'TR_DETAILED_STATS_TOOLTIPS' => tohtml(tr('Show detailed statistics for this reseller'), 'htmlAttr')
	));

	$eventManager->registerListener('onGetJsTranslations', function ($e) {
		/** @var $e \iMSCP_Events_Event */
		$e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
	});

	generateNavigation($tpl);
	generatePage($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');
	$eventManager->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));
	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
