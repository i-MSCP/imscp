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
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Admin
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2014 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @link		http://i-mscp.net
 */

/******************************************************************************
 * Script functions
 */

/**
 * Return server traffic information for the given day.
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $year Year
 * @param int $month Month
 * @param int $day Day
 * @return void
 */
function admin_generatePage($tpl, $year, $month, $day)
{
	$firstHourOfDay = mktime(0, 0, 0, $month, $day, $year);
	$lastHourOfDay = mktime(23, 59, 59, $month, $day, $year);

	$query = "
		SELECT
			traff_time AS ttime, bytes_in AS sbin, bytes_out AS sbout, bytes_mail_in AS smbin,
			bytes_mail_out AS smbout, bytes_pop_in AS spbin, bytes_pop_out AS spbout, bytes_web_in AS swbin,
			bytes_web_out AS swbout
		FROM
			server_traffic
		WHERE
			traff_time BETWEEN ? AND ?
	";
	$stmt = exec_query($query, array($firstHourOfDay, $lastHourOfDay));

	if ($stmt->rowCount()) {
		$all = array_fill(0, 8, 0);

		while (!$stmt->EOF) {
			// Compute other traffic statistics
			$otherIn = $stmt->fields['sbin'] - ($stmt->fields['swbin'] + $stmt->fields['smbin'] + $stmt->fields['spbin']);
			$otherOut = $stmt->fields['sbout'] - ($stmt->fields['swbout'] + $stmt->fields['smbout'] + $stmt->fields['spbout']);

			$tpl->assign(
				array(
					'HOUR' => date('H:i', $stmt->fields['ttime']),
					'WEB_IN' => bytesHuman($stmt->fields['swbin']),
					'WEB_OUT' => bytesHuman($stmt->fields['swbout']),
					'SMTP_IN' => bytesHuman($stmt->fields['smbin']),
					'SMTP_OUT' => bytesHuman($stmt->fields['smbout']),
					'POP_IN' => bytesHuman($stmt->fields['spbin']),
					'POP_OUT' => bytesHuman($stmt->fields['spbout']),
					'OTHER_IN' => bytesHuman($otherIn),
					'OTHER_OUT' => bytesHuman($otherOut),
					'ALL_IN' => bytesHuman($stmt->fields['sbin']),
					'ALL_OUT' => bytesHuman($stmt->fields['sbout']),
					'ALL' => bytesHuman($stmt->fields['sbin'] + $stmt->fields['sbout']),));

			$all[0] = $all[0] + $stmt->fields['swbin'];
			$all[1] = $all[1] + $stmt->fields['swbout'];
			$all[2] = $all[2] + $stmt->fields['smbin'];
			$all[3] = $all[3] + $stmt->fields['smbout'];
			$all[4] = $all[4] + $stmt->fields['spbin'];
			$all[5] = $all[5] + $stmt->fields['spbout'];
			$all[6] = $all[6] + $stmt->fields['sbin'];
			$all[7] = $all[7] + $stmt->fields['sbout'];

			$tpl->parse('HOUR_LIST', '.hour_list');
			$stmt->moveNext();
		}

		$allOtherIn = $all[6] - ($all[0] + $all[2] + $all[4]);
		$allOtherOut = $all[7] - ($all[1] + $all[3] + $all[5]);

		$tpl->assign(
			array(
				'WEB_IN_ALL' => bytesHuman($all[0]),
				'WEB_OUT_ALL' => bytesHuman($all[1]),
				'SMTP_IN_ALL' => bytesHuman($all[2]),
				'SMTP_OUT_ALL' => bytesHuman($all[3]),
				'POP_IN_ALL' => bytesHuman($all[4]),
				'POP_OUT_ALL' => bytesHuman($all[5]),
				'OTHER_IN_ALL' => bytesHuman($allOtherIn),
				'OTHER_OUT_ALL' => bytesHuman($allOtherOut),
				'ALL_IN_ALL' => bytesHuman($all[6]),
				'ALL_OUT_ALL' => bytesHuman($all[7]),
				'ALL_ALL' => bytesHuman($all[6] + $all[7])
			)
		);
	} else {
		set_page_message(tr('No statistics found for the given period. Try another period.'), 'info');
		$tpl->assign('DAY_SERVER_STATISTICS_BLOCK', '');
	}
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/server_statistic_day.tpl',
		'page_message' => 'layout',
		'day_server_statistics_block' => 'page',
		'hour_list' => 'day_server_statistics_block'
	)
);

if (isset($_GET['month']) && isset($_GET['year']) && isset($_GET['day'])) {
	$year = intval($_GET['year']);
	$month = intval($_GET['month']);
	$day = intval($_GET['day']);
} else {
	showBadRequestErrorPage();
	exit;
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Statistics / Server Statistics / Day Statistics'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_MONTH' => tr('Month'),
		'TR_YEAR' => tr('Year'),
		'TR_DAY' => tr('Day'),
		'TR_HOUR' => tr('Hour'),
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
		'TR_ALL' => tr('All'),
		'MONTH' => $month,
		'YEAR' => date('Y', mktime(0, 0, 0, $month, $day, $year)),
		'DAY' => $day,
	)
);

generateNavigation($tpl);
admin_generatePage($tpl, $year, $month, $day);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
