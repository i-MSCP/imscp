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
 * Get traffic
 *
 * @param int $domainId User main domain unique identifier
 * @param int $beginTime An UNIX timestamp representing a begin time period
 * @param int $endTime An UNIX timestamp representing an end time period
 * @return array
 */
function _getUserTraffic($domainId, $beginTime, $endTime)
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
 * Generate statistics for the given period
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return void
 */
function generatePage($tpl)
{
	$domainId = get_user_domain_id($_SESSION['user_id']);

	if (isset($_POST['month']) && isset($_POST['year'])) {
		$year = intval($_POST['year']);
		$month = intval($_POST['month']);
	} else if (isset($_GET['month']) && isset($_GET['year'])) {
		$month = intval($_GET['month']);
		$year = intval($_GET['year']);
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
		'SELECT domain_id FROM domain_traffic WHERE domain_id = ? AND dtraff_time >= ? AND dtraff_time <= ? LIMIT 1',
		array($domainId, getFirstDayOfMonth($month, $year), getLastDayOfMonth($month, $year))
	);

	if ($stmt->rowCount()) {
		$requestedPeriod = getLastDayOfMonth($month, $year);
		$toDay = ($requestedPeriod < time()) ? date('j', $requestedPeriod) : date('j');
		$all = array_fill(0, 8, 0);
		$dateFormat = iMSCP_Registry::get('config')->DATE_FORMAT;

		for ($fromDay = 1; $fromDay <= $toDay; $fromDay++) {
			$beginTime = mktime(0, 0, 0, $month, $fromDay, $year);
			$endTime = mktime(23, 59, 59, $month, $fromDay, $year);

			list($webTraffic, $ftpTraffic, $smtpTraffic, $popTraffic) = _getUserTraffic(
				$domainId, $beginTime, $endTime
			);

			$tpl->assign(array(
				'DATE' => tohtml(date($dateFormat, strtotime($year . '-' . $month . '-' . $fromDay))),
				'WEB_TRAFF' => tohtml(bytesHuman($webTraffic)),
				'FTP_TRAFF' => tohtml(bytesHuman($ftpTraffic)),
				'SMTP_TRAFF' => tohtml(bytesHuman($smtpTraffic)),
				'POP_TRAFF' => tohtml(bytesHuman($popTraffic)),
				'SUM_TRAFF' => tohtml(bytesHuman($webTraffic + $ftpTraffic + $smtpTraffic + $popTraffic))
			));

			$all[0] += $webTraffic;
			$all[1] += $ftpTraffic;
			$all[2] += $smtpTraffic;
			$all[3] += $popTraffic;

			$tpl->parse('TRAFFIC_TABLE_ITEM', '.traffic_table_item');
		}

		$tpl->assign(array(
			'WEB_ALL' => tohtml(bytesHuman($all[0])),
			'FTP_ALL' => tohtml(bytesHuman($all[1])),
			'SMTP_ALL' => tohtml(bytesHuman($all[2])),
			'POP_ALL' => tohtml(bytesHuman($all[3])),
			'SUM_ALL' => tohtml(bytesHuman(array_sum($all)))
		));
	} else {
		set_page_message(tr('No statistics found for the given period. Try another period.'), 'static_info');
		$tpl->assign('STATISTICS_BLOCK', '');
	}
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

$eventManager = iMSCP_Events_Aggregator::getInstance();
$eventManager->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
	'layout' => 'shared/layouts/ui.tpl',
	'page' => 'client/traffic_statistics.tpl',
	'page_message' => 'layout',
	'month_list' => 'page',
	'year_list' => 'page',
	'statistics_block' => 'page',
	'traffic_table_item' => 'statistics_block'
));

$tpl->assign(array(
	'TR_PAGE_TITLE' => tr('Client / Statistics'),
	'TR_STATISTICS' => tr('Statistics'),
	'TR_MONTH' => tr('Month'),
	'TR_YEAR' => tr('Year'),
	'TR_SHOW' => tr('Show'),
	'TR_WEB_TRAFF' => tr('Web traffic'),
	'TR_FTP_TRAFF' => tr('FTP traffic'),
	'TR_SMTP_TRAFF' => tr('SMTP traffic'),
	'TR_POP_TRAFF' => tr('POP3/IMAP traffic'),
	'TR_SUM' => tr('All traffic'),
	'TR_ALL' => tr('All'),
	'TR_DATE' => tr('Date')
));

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
$eventManager->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
