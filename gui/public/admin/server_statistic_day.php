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
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $year Year
 * @param int $month Month
 * @param int $day Day
 * @return void
 */
function generatePage($tpl, $year, $month, $day)
{
	$firstHourOfDay = mktime(0, 0, 0, $month, $day, $year);
	$lastHourOfDay = mktime(23, 59, 59, $month, $day, $year);

	$stmt = exec_query(
		'
			SELECT
				traff_time AS ttime, bytes_in AS sbin, bytes_out AS sbout, bytes_mail_in AS smbin,
				bytes_mail_out AS smbout, bytes_pop_in AS spbin, bytes_pop_out AS spbout, bytes_web_in AS swbin,
				bytes_web_out AS swbout
			FROM
				server_traffic
			WHERE
				traff_time BETWEEN ? AND ?
		',
		array($firstHourOfDay, $lastHourOfDay)
	);

	if ($stmt->rowCount()) {
		$all = array_fill(0, 8, 0);

		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$otherIn = $row['sbin'] - ($row['swbin'] + $row['smbin'] + $row['spbin']);
			$otherOut = $row['sbout'] - ($row['swbout'] + $row['smbout'] + $row['spbout']);

			$tpl->assign(array(
				'HOUR' => tohtml(date('H:i', $row['ttime'])),
				'WEB_IN' => tohtml(bytesHuman($row['swbin'])),
				'WEB_OUT' => tohtml(bytesHuman($row['swbout'])),
				'SMTP_IN' => tohtml(bytesHuman($row['smbin'])),
				'SMTP_OUT' => tohtml(bytesHuman($row['smbout'])),
				'POP_IN' => tohtml(bytesHuman($row['spbin'])),
				'POP_OUT' => tohtml(bytesHuman($row['spbout'])),
				'OTHER_IN' => tohtml(bytesHuman($otherIn)),
				'OTHER_OUT' => tohtml(bytesHuman($otherOut)),
				'ALL_IN' => tohtml(bytesHuman($row['sbin'])),
				'ALL_OUT' => tohtml(bytesHuman($row['sbout'])),
				'ALL' => tohtml(bytesHuman($row['sbin'] + $row['sbout']))
			));

			$all[0] += $row['swbin'];
			$all[1] += $row['swbout'];
			$all[2] += $row['smbin'];
			$all[3] += $row['smbout'];
			$all[4] += $row['spbin'];
			$all[5] += $row['spbout'];
			$all[6] += $row['sbin'];
			$all[7] += $row['sbout'];

			$tpl->parse('HOUR_LIST', '.hour_list');
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
		$tpl->assign('DAY_SERVER_STATISTICS_BLOCK', '');
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
	'page' => 'admin/server_statistic_day.tpl',
	'page_message' => 'layout',
	'day_server_statistics_block' => 'page',
	'hour_list' => 'day_server_statistics_block'
));

if (isset($_GET['month']) && isset($_GET['year']) && isset($_GET['day'])) {
	$year = intval($_GET['year']);
	$month = intval($_GET['month']);
	$day = intval($_GET['day']);

	$tpl->assign(array(
		'TR_PAGE_TITLE' => tohtml(tr('Admin / Statistics / Server Statistics / Day Statistics')),
		'TR_MONTH' => tohtml(tr('Month')),
		'TR_YEAR' => tohtml(tr('Year')),
		'TR_DAY' => tohtml(tr('Day')),
		'TR_HOUR' => tohtml(tr('Hour')),
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
		'TR_ALL' => tohtml(tr('All')),
		'MONTH' => tohtml($month),
		'YEAR' => tohtml(date('Y', mktime(0, 0, 0, $month, $day, $year))),
		'DAY' => tohtml($day)
	));

	generateNavigation($tpl);
	generatePage($tpl, $year, $month, $day);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');
	$eventManager->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));
	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
