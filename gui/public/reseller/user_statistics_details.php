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
 * Get traffic information for the given period
 *
 * @param int $domainId User main domain unique identifier
 * @param int $beginTime An UNIX timestamp representing a begin time period
 * @param int $endTime An UNIX timestamp representing an end time period
 * @return array
 */
function _getDomainTraffic($domainId, $beginTime, $endTime)
{
	$stmt = exec_query(
		'
			SELECT
				IFNULL(SUM(dtraff_web), 0) AS web_traffic, IFNULL(SUM(dtraff_ftp), 0) AS ftp_traffic,
				IFNULL(SUM(dtraff_mail), 0) AS mail_traffic, IFNULL(SUM(dtraff_pop), 0) AS pop_traffic
			FROM
				domain_traffic
			WHERE
				domain_id = ?
			AND
				dtraff_time BETWEEN ? AND ?
		',
		array($domainId, $beginTime, $endTime)
	);

	if ($stmt->rowCount()) {
		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
		return array($row['web_traffic'], $row['ftp_traffic'], $row['mail_traffic'], $row['pop_traffic']);
	}

	return array(0, 0, 0, 0);
}

/**
 * Generate domain statistics for the given period
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $userId User unique identifier
 * @return void
 */
function generatePage($tpl, $userId)
{
	$stmt = exec_query(
		'
			SELECT
				admin_name, domain_id
			FROM
				admin
			INNER JOIN
				domain ON(domain_admin_id = admin_id)
			WHERE
				admin_id = ?
			AND
				created_by = ?
		',
		array($userId, $_SESSION['user_id'])
	);

	if (!$stmt->rowCount()) {
		showBadRequestErrorPage();
	}

	$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
	$domainId = $row['domain_id'];
	$adminName = decode_idna($row['admin_name']);

	if (isset($_POST['month']) && isset($_POST['year'])) {
		$year = intval($_POST['year']);
		$month = intval($_POST['month']);
	} else {
		$month = date('m');
		$year = date('Y');
	}

	$stmt = exec_query(
		'SELECT dtraff_time FROM domain_traffic WHERE domain_id = ? ORDER BY dtraff_time ASC LIMIT 1', $domainId
	);

	if ($stmt->rowCount()) {
		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
		$numberYears = date('y') - date('y', $row['dtraff_time']);
		$numberYears = $numberYears ? $numberYears + 1 : 1;
	} else {
		$numberYears = 1;
	}

	generateMonthsAndYearsHtmlList($tpl, $month, $year, $numberYears);

	$stmt = exec_query(
		'SELECT domain_id FROM domain_traffic WHERE dtraff_time BETWEEN ? AND ? LIMIT 1',
		array(getFirstDayOfMonth($month, $year), getLastDayOfMonth($month, $year))
	);

	if ($stmt->rowCount()) {
		$requestedPeriod = getLastDayOfMonth($month, $year);
		$toDay = ($requestedPeriod < time()) ? date('j', $requestedPeriod) : date('j');
		$all = array_fill(0, 8, 0);

		$dateFormat = iMSCP_Registry::get('config')->DATE_FORMAT;

		for ($fromDay = 1; $fromDay <= $toDay; $fromDay++) {
			$beginTime = mktime(0, 0, 0, $month, $fromDay, $year);
			$endTime = mktime(23, 59, 59, $month, $fromDay, $year);

			list($webTraffic, $ftpTraffic, $smtpTraffic, $popTraffic) = _getDomainTraffic(
				$domainId, $beginTime, $endTime
			);

			$tpl->assign(array(
				'DATE' => date($dateFormat, strtotime($year . '-' . $month . '-' . $fromDay)),
				'WEB_TRAFFIC' => bytesHuman($webTraffic),
				'FTP_TRAFFIC' => bytesHuman($ftpTraffic),
				'SMTP_TRAFFIC' => bytesHuman($smtpTraffic),
				'POP3_TRAFFIC' => bytesHuman($popTraffic),
				'ALL_TRAFFIC' => bytesHuman($webTraffic + $ftpTraffic + $smtpTraffic + $popTraffic),
			));

			$all[0] += $webTraffic;
			$all[1] += $ftpTraffic;
			$all[2] += $smtpTraffic;
			$all[3] += $popTraffic;

			$tpl->parse('TRAFFIC_TABLE_ITEM', '.traffic_table_item');
		}

		$tpl->assign(array(
			'USER_ID' => tohtml($userId),
			'USERNAME' => tohtml($adminName),
			'ALL_WEB_TRAFFIC' => tohtml(bytesHuman($all[0])),
			'ALL_FTP_TRAFFIC' => tohtml(bytesHuman($all[1])),
			'ALL_SMTP_TRAFFIC' => tohtml(bytesHuman($all[2])),
			'ALL_POP3_TRAFFIC' => tohtml(bytesHuman($all[3])),
			'ALL_ALL_TRAFFIC' => tohtml(bytesHuman(array_sum($all)))
		));
	} else {
		set_page_message(tr('No statistics found for the given period. Try another period.'), 'static_info');
		$tpl->assign(array(
			'USERNAME' => tohtml($adminName),
			'USER_ID' => tohtml($userId),
			'USER_STATISTICS_DETAILS_BLOCK' => ''
		));
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

$eventManager = iMSCP_Events_Aggregator::getInstance();
$eventManager->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

if (resellerHasCustomers()) {
	if (isset($_GET['user_id'])) {
		$userId = intval($_GET['user_id']);
		$_SESSION['stats_user_id'] = $userId;
	} elseif (isset($_SESSION['reseller_stats_user_id'])) {
		redirectTo('user_statistics_details.php?user_id=' . $_SESSION['reseller_stats_user_id']);
		exit;
	} else {
		showBadRequestErrorPage();
		exit;
	}

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/user_statistics_details.tpl',
		'page_message' => 'layout',
		'month_list' => 'page',
		'year_list' => 'page',
		'user_statistics_details_block' => 'page',
		'traffic_table_item' => 'user_statistics_details_block'
	));

	$tpl->assign(array(
		'TR_PAGE_TITLE' => tohtml(tr('Reseller / Statistics / Overview / {USERNAME} USER Statistics')),
		'TR_MONTH' => tohtml(tr('Month')),
		'TR_YEAR' => tohtml(tr('Year')),
		'TR_SHOW' => tohtml(tr('Show')),
		'TR_WEB_TRAFFIC' => tohtml(tr('Web traffic')),
		'TR_FTP_TRAFFIC' => tohtml(tr('FTP traffic')),
		'TR_SMTP_TRAFFIC' => tohtml(tr('SMTP traffic')),
		'TR_POP3_TRAFFIC' => tohtml(tr('POP3/IMAP traffic')),
		'TR_ALL_TRAFFIC' => tohtml(tr('All traffic')),
		'TR_ALL' => tohtml(tr('All')),
		'TR_DAY' => tohtml(tr('Day'))
	));

	generateNavigation($tpl);
	generatePage($tpl, $userId);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');
	$eventManager->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));
	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
