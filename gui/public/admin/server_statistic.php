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
 * functions
 */

/**
 * Get server traffic for the given period
 *
 * @param int $beginDate An UNIX timestamp representing a begin date
 * @param int $endDate An UNIX timestamp representing an end date
 * @return array
 */
function _getServerTraffic($beginDate, $endDate)
{
	$stmt = exec_query(
		'
			SELECT
				IFNULL(SUM(bytes_in), 0) AS sbin, IFNULL(SUM(bytes_out), 0) AS sbout,
				IFNULL(SUM(bytes_mail_in), 0) AS smbin, IFNULL(SUM(bytes_mail_out), 0) AS smbout,
				IFNULL(SUM(bytes_pop_in), 0) AS spbin, IFNULL(SUM(bytes_pop_out), 0) AS spbout,
				IFNULL(SUM(bytes_web_in), 0) AS swbin, IFNULL(SUM(bytes_web_out), 0) AS swbout
			FROM
				server_traffic
			WHERE
				traff_time BETWEEN ? AND ?
		',
		array($beginDate, $endDate)
	);

	if ($stmt->rowCount()) {
		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

		return array(
			$row['swbin'],
			$row['swbout'],
			$row['smbin'],
			$row['smbout'],
			$row['spbin'],
			$row['spbout'],
			$row['sbin'] - ($row['swbin'] + $row['smbin'] + $row['spbin']),
			$row['sbout'] - ($row['swbout'] + $row['smbout'] + $row['spbout']),
			$row['sbin'],
			$row['sbout']
		);
	}

	return array_fill(0, 10, 0);
}

/**
 * Generates statistics page for the given period
 *
 * @param iMSCP_pTemplate $tpl template engine instance
 * @return void
 */
function generatePage($tpl)
{
	if (isset($_GET['month']) && isset($_GET['year'])) {
		$year = intval($_GET['year']);
		$month = intval($_GET['month']);
	} else if (isset($_POST['month']) && isset($_POST['year'])) {
		$year = intval($_POST['year']);
		$month = intval($_POST['month']);
	} else {
		$month = date('m');
		$year = date('y');
	}

	$stmt = exec_query('SELECT traff_time FROM server_traffic ORDER BY traff_time ASC LIMIT 1');

	if ($stmt->rowCount()) {
		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
		$numberYears = date('y') - date('y', $row['traff_time']);
		$numberYears = $numberYears ? $numberYears + 1 : 1;
	} else {
		$numberYears = 1;
	}

	generateMonthsAndYearsHtmlList($tpl, $month, $year, $numberYears);

	$stmt = exec_query(
		'SELECT bytes_in FROM server_traffic WHERE traff_time BETWEEN ? AND ? LIMIT 1',
		array(getFirstDayOfMonth($month, $year), getLastDayOfMonth($month, $year))
	);

	if ($stmt->rowCount()) {
		if ($month == date('m') && $year == date('y')) {
			$curday = date('j');
		} else {
			$curday = date('j', getLastDayOfMonth($month, $year));
		}

		$all = array_fill(0, 8, 0);

		for ($day = 1; $day <= $curday; $day++) {
			$beginDate = mktime(0, 0, 0, $month, $day, $year);
			$endDate = mktime(23, 59, 59, $month, $day, $year);

			list(
				$webIn, $webOut, $smtpIn, $smtpOut, $popIn, $popOut, $otherIn, $otherOut, $allIn, $allOut
			) = _getServerTraffic($beginDate, $endDate);

			$tpl->assign(array(
				'DAY' => tohtml($day),
				'YEAR' => tohtml($year),
				'MONTH' => tohtml($month),
				'WEB_IN' => tohtml(bytesHuman($webIn)),
				'WEB_OUT' => tohtml(bytesHuman($webOut)),
				'SMTP_IN' => tohtml(bytesHuman($smtpIn)),
				'SMTP_OUT' => tohtml(bytesHuman($smtpOut)),
				'POP_IN' => tohtml(bytesHuman($popIn)),
				'POP_OUT' => tohtml(bytesHuman($popOut)),
				'OTHER_IN' => tohtml(bytesHuman($otherIn)),
				'OTHER_OUT' => tohtml(bytesHuman($otherOut)),
				'ALL_IN' => tohtml(bytesHuman($allIn)),
				'ALL_OUT' => tohtml(bytesHuman($allOut)),
				'ALL' => tohtml(bytesHuman($allIn + $allOut)),
				'DAY_STATS_QSTRING' => tohtml("year=$year&month=$month&day=$day", 'htmlAttr')
			));

			$all[0] += $webIn;
			$all[1] += $webOut;
			$all[2] += $smtpIn;
			$all[3] += $smtpOut;
			$all[4] += $popIn;
			$all[5] += $popOut;
			$all[6] += $allIn;
			$all[7] += $allOut;

			$tpl->parse('DAY_SERVER_STATISTICS_BLOCK', '.day_server_statistics_block');
		}

		$allOtherIn = $all[6] - ($all[0] + $all[2] + $all[4]);
		$allOtherOut = $all[7] - ($all[1] + $all[3] + $all[5]);

		$tpl->assign(array(
			'WEB_IN_ALL' => tohtml(bytesHuman($all[0])),
			'WEB_OUT_ALL' => tohtml(bytesHuman($all[1])),
			'SMTP_IN_ALL' => tohtml(bytesHuman($all[2])),
			'SMTP_OUT_ALL' => tohtml(bytesHuman($all[3])),
			'POP_IN_ALL' => tohtml(bytesHuman($all[4])),
			'POP_OUT_ALL' => tohtml(bytesHuman($all[5])),
			'OTHER_IN_ALL' => tohtml(bytesHuman($allOtherIn)),
			'OTHER_OUT_ALL' => tohtml(bytesHuman($allOtherOut)),
			'ALL_IN_ALL' => tohtml(bytesHuman($all[6])),
			'ALL_OUT_ALL' => tohtml(bytesHuman($all[7])),
			'ALL_ALL' => tohtml(bytesHuman($all[6] + $all[7]))
		));
	} else {
		set_page_message(tr('No statistics found for the given period. Try another period.'), 'static_info');
		$tpl->assign('SERVER_STATISTICS_BLOCK', '');
	}
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

$eventManager = iMSCP_Events_Aggregator::getInstance();
$eventManager->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
	'layout' => 'shared/layouts/ui.tpl',
	'page' => 'admin/server_statistic.tpl',
	'page_message' => 'layout',
	'month_list' => 'page',
	'year_list' => 'page',
	'server_statistics_block' => 'page',
	'day_server_statistics_block' => 'server_statistics_block'
));

$tpl->assign(array(
	'TR_PAGE_TITLE' => tohtml(tr('Admin / Statistics / Server Statistics')),
	'TR_MONTH' => tohtml(tr('Month')),
	'TR_YEAR' => tohtml(tr('Year')),
	'TR_SHOW' => tohtml(tr('Show')),
	'TR_DAY' => tohtml(tr('Day')),
	'TR_WEB_IN' => tohtml(tr('Web in')),
	'TR_WEB_OUT' => tohtml(tr('Web out')),
	'TR_SMTP_IN' => tohtml(tr('SMTP in')),
	'TR_SMTP_OUT' => tohtml(tr('SMTP out')),
	'TR_POP_IN' => tohtml(tr('POP3/IMAP in')),
	'TR_POP_OUT' => tohtml(tr('POP3/IMAP out')),
	'TR_OTHER_IN' => tohtml(tr('Other in')),
	'TR_OTHER_OUT' => tohtml(tr('Other out')),
	'TR_ALL_IN' => tohtml(tr('All in')),
	'TR_ALL_OUT' => tohtml(tr('All out')),
	'TR_ALL' => tohtml(tr('All'))
));

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
$eventManager->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
