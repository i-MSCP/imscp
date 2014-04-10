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
 * @subpackage  Admin
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
 * Return server traffic information for the given period
 *
 * @param int $beginDate An UNIX timestamp representing a begin date
 * @param int $endDate An UNIX timestamp representing an end date
 * @return array
 */
function admin_getServerTraffic($beginDate, $endDate)
{
	$query = "
		SELECT
			IFNULL(SUM(bytes_in), 0) AS sbin, IFNULL(SUM(bytes_out), 0) AS sbout,
			IFNULL(SUM(bytes_mail_in), 0) AS smbin, IFNULL(SUM(bytes_mail_out), 0) AS smbout,
			IFNULL(SUM(bytes_pop_in), 0) AS spbin, IFNULL(SUM(bytes_pop_out), 0) AS spbout,
			IFNULL(SUM(bytes_web_in), 0) AS swbin, IFNULL(SUM(bytes_web_out), 0) AS swbout
		FROM
			server_traffic
		WHERE
			traff_time BETWEEN ? AND ?
	";
	$stmt = exec_query($query, array($beginDate, $endDate));

	if (!$stmt->rowCount()) {
		return array_fill(0, 10, 0);
	} else {
		return array(
			$stmt->fields['swbin'], $stmt->fields['swbout'],
			$stmt->fields['smbin'], $stmt->fields['smbout'],
			$stmt->fields['spbin'], $stmt->fields['spbout'],
			$stmt->fields['sbin'] - ($stmt->fields['swbin'] + $stmt->fields['smbin'] + $stmt->fields['spbin']),
			$stmt->fields['sbout'] - ($stmt->fields['swbout'] + $stmt->fields['smbout'] + $stmt->fields['spbout']),
			$stmt->fields['sbin'], $stmt->fields['sbout']);
	}
}

/**
 * Generates statistics page for the given period
 *
 * @param iMSCP_pTemplate $tpl template engine instance
 * @param int $month Month of the period for which statistics are requested
 * @param int $year Year of the period for which statistics are requested
 * @return void
 */
function admin_generatePage($tpl, $month, $year)
{
	// Let see if we have any statistics available for the given periode
	$stmt = exec_query(
		"SELECT bytes_in FROM server_traffic WHERE traff_time BETWEEN ? AND ? LIMIT 1",
		array(getFirstDayOfMonth($month, $year), getLastDayOfMonth($month, $year))
	);

	if ($stmt->rowCount()) { // Statistic were found for the period
		if ($month == date('m') && $year == date('y')) { // Statistics needed only from begin of month to today
			$curday = date('j');
		} else { // Statistics needed for entire month
			$curday = date('j', getLastDayOfMonth($month, $year));
		}

		$all = array_fill(0, 8, 0);

		// Getting statistics by day for the period
		for ($day = 1; $day <= $curday; $day++) {
			$beginDate = mktime(0, 0, 0, $month, $day, $year);
			$endDate = mktime(23, 59, 59, $month, $day, $year);

			list(
				$webIn, $webOut, $smtpIn, $smtpOut, $popIn, $popOut, $otherIn, $otherOut, $allIn, $allOut
			) = admin_getServerTraffic($beginDate, $endDate);

			$tpl->assign(
				array(
					'DAY' => $day,
					'YEAR' => $year,
					'MONTH' => $month,
					'WEB_IN' => bytesHuman($webIn),
					'WEB_OUT' => bytesHuman($webOut),
					'SMTP_IN' => bytesHuman($smtpIn),
					'SMTP_OUT' => bytesHuman($smtpOut),
					'POP_IN' => bytesHuman($popIn),
					'POP_OUT' => bytesHuman($popOut),
					'OTHER_IN' => bytesHuman($otherIn),
					'OTHER_OUT' => bytesHuman($otherOut),
					'ALL_IN' => bytesHuman($allIn),
					'ALL_OUT' => bytesHuman($allOut),
					'ALL' => bytesHuman($allIn + $allOut)
				)
			);

			$all[0] = $all[0] + $webIn;
			$all[1] = $all[1] + $webOut;
			$all[2] = $all[2] + $smtpIn;
			$all[3] = $all[3] + $smtpOut;
			$all[4] = $all[4] + $popIn;
			$all[5] = $all[5] + $popOut;
			$all[6] = $all[6] + $allIn;
			$all[7] = $all[7] + $allOut;

			$tpl->parse('DAY_SERVER_STATISTICS_BLOCK', '.day_server_statistics_block');
		}

		$allOtherIn = $all[6] - ($all[0] + $all[2] + $all[4]);
		$allOtherOut = $all[7] - ($all[1] + $all[3] + $all[5]);

		$tpl->assign(
			array(
				'WEB_IN_ALL' => bytesHuman($all[0]), 'WEB_OUT_ALL' => bytesHuman($all[1]),
				'SMTP_IN_ALL' => bytesHuman($all[2]), 'SMTP_OUT_ALL' => bytesHuman($all[3]),
				'POP_IN_ALL' => bytesHuman($all[4]), 'POP_OUT_ALL' => bytesHuman($all[5]),
				'OTHER_IN_ALL' => bytesHuman($allOtherIn), 'OTHER_OUT_ALL' => bytesHuman($allOtherOut),
				'ALL_IN_ALL' => bytesHuman($all[6]), 'ALL_OUT_ALL' => bytesHuman($all[7]),
				'ALL_ALL' => bytesHuman($all[6] + $all[7])
			)
		);
	} else { // no statistic available for the given period
		set_page_message(tr('No statistics found for the given period. Try another period.'), 'info');
		$tpl->assign('SERVER_STATISTICS_BLOCK', '');
	}
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

// dispatches the request
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

// Retrieve smaller timestamp to define max number of years to show in select element
$stmt = exec_query('SELECT traff_time FROM server_traffic ORDER BY traff_time ASC LIMIT 1');

if ($stmt->rowCount()) {
	$numberYears = date('y') - date('y', $stmt->fields['traff_time']);
	$numberYears =  $numberYears ? $numberYears + 1: 1;
} else {
	$numberYears = 1;
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/server_statistic.tpl',
		'page_message' => 'layout',
		'month_list' => 'page',
		'year_list' => 'page',
		'server_statistics_block' => 'page',
		'day_server_statistics_block' => 'server_statistics_block'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Statistics / Server Statistics'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_MONTH' => tr('Month'),
		'TR_YEAR' => tr('Year'),
		'TR_SHOW' => tr('Show'),
		'TR_DAY' => tr('Day'),
		'TR_WEB_IN' => tr('Web in'),
		'TR_WEB_OUT' => tr('Web out'),
		'TR_SMTP_IN' => tr('SMTP in'),
		'TR_SMTP_OUT' => tr('SMTP out'),
		'TR_POP_IN' => tr('POP3/IMAP in'),
		'TR_POP_OUT' => tr('POP3/IMAP out'),
		'TR_OTHER_IN' => tr('Other in'),
		'TR_OTHER_OUT' => tr('Other out'),
		'TR_ALL_IN' => tr('All in'),
		'TR_ALL_OUT' => tr('All out'),
		'TR_ALL' => tr('All')
	)
);

generateNavigation($tpl);
generateSelectListForMonthsAndYears($tpl, $month, $year, $numberYears);
admin_generatePage($tpl, $month, $year);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
