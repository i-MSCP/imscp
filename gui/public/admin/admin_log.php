<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2012 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @link		http://i-mscp.net
 */

/******************************************************************************
 * Script function
 */

/**
 *
 * @param  iMSCP_pTemplate $tpl
 * @return void
 */
function admin_generatePage($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$startIndex = 0;
	$RowsPerPage = $cfg->DOMAIN_ROWS_PER_PAGE;

	if (isset($_GET['psi']) && is_numeric($_GET['psi'])) {
		$startIndex = intval($_GET['psi']);
	}

	$count_query = "SELECT COUNT(`log_id`) `cnt` FROM `log`";

	$query = "
		SELECT
			DATE_FORMAT(`log_time`, '%Y-%m-%d %H:%i') `date`, `log_message`
		FROM
			`log`
		ORDER BY
			`log_time` DESC
		LIMIT
			$startIndex, $RowsPerPage
	";

	$stmt = exec_query($count_query);
	$recordsCount = $stmt->fields['cnt'];
	$stmt = execute_query($query);

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'LOG_ROW' => '',
				'PAG_MESSAGE' => tr('No logs found.'),
				'USERS_LIST' => '',
				'SCROLL_PREV' => '',
				'SCROLL_NEXT' => '',
				'CLEAR_LOG' => ''));
	} else {
		$prev_si = $startIndex - $RowsPerPage;

		if ($startIndex == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_PREV_GRAY' => '',
					'PREV_PSI' => $prev_si));
		}

		$nextSi = $startIndex + $RowsPerPage;

		if ($nextSi + 1 > $recordsCount) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_NEXT_GRAY' => '',
					'NEXT_PSI' => $nextSi));
		}

		$dateFormat = $cfg->DATE_FORMAT . ' H:i';

		while (!$stmt->EOF) {
			$logMessage = $stmt->fields['log_message'];

			$replaces = array(
				'/[^a-zA-Z](delete[sd]?|failed)[^a-zA-Z]/i' => ' <strong style="color:#FF0000">\\1</strong> ',
				'/[^a-zA-Z](remove[sd]?)[^a-zA-Z]/i' => ' <strong style="color:#FF0000">\\1</strong> ',
				'/[^a-zA-Z](unable?)[^a-zA-Z]/i' => ' <strong style="color:#FF0000">\\1</strong> ',
				'/[^a-zA-Z](add(s|ed)?)[^a-zA-Z]/i' => ' <strong style="color:#33CC66">\\1</strong> ',
				'/[^a-zA-Z](add(s|ed)?)[^a-zA-Z]/i' => ' <strong style="color:#33CC66">\\1</strong> ',
				'/[^a-zA-Z](created)[^a-zA-Z]/i' => ' <strong style="color:#3300FF">\\1</strong> ',
				'/[^a-zA-Z](update[sd]?)[^a-zA-Z]/i' => ' <strong style="color:#3300FF">\\1</strong> ',
				'/[^a-zA-Z](edit(s|ed)?)[^a-zA-Z]/i' => ' <strong style="color:#33CC66">\\1</strong> ',
				'/[^a-zA-Z](unknown)[^a-zA-Z]/i' => ' <strong style="color:#CC00FF">\\1</strong> ',
				'/[^a-zA-Z](logged)[^a-zA-Z]/i' => ' <strong style="color:#336600">\\1</strong> ',
				'/[^a-zA-Z]((session )?manipulation)[^a-zA-Z]/i' => ' <strong style="color:#FF0000">\\1</strong> ',
				'/[^a-zA-Z]*(Warning[\!]?)[^a-zA-Z]/i' => ' <strong style="color:#FF0000">\\1</strong> ',
				'/(bad password login data)/i' => ' <strong style="color:#FF0000">\\1</strong> '
			);

			foreach ($replaces as $pattern => $replacement) {
				$logMessage = preg_replace($pattern, $replacement, $logMessage);
			}

			$tpl->assign(
				array(
					'MESSAGE' => $logMessage,
					'DATE' => date($dateFormat, strtotime($stmt->fields['date']))));

			$tpl->parse('LOG_ROW', '.log_row');
			$stmt->moveNext();
		}
	}
}

/**
 * Clear logs.
 *
 * @throws iMSCP_Exception
 * @return void
 */
function admin_ClearLogs()
{
	switch ($_POST['uaction_clear']) {
		case 0:
			$query = "DELETE FROM `log`";
			$msg = tr('%s deleted the full admin log.', $_SESSION['user_logged']);
			break;
		case 2:
			$query = "DELETE FROM `log` WHERE DATE_SUB(CURDATE(), INTERVAL 14 DAY) >= `log_time`";
			$msg = tr('%s deleted the admin log older than two weeks!', $_SESSION['user_logged']);
			break;
		case 4:
			$query = "DELETE FROM `log` WHERE DATE_SUB(CURDATE(), INTERVAL 1 MONTH) >= `log_time`";
			$msg = tr('%s deleted the admin log older than one month.', $_SESSION['user_logged']);
			break;
		case 12:
			$query = "DELETE FROM `log` WHERE DATE_SUB(CURDATE(), INTERVAL 3 MONTH) >= `log_time`";
			$msg = tr('%s deleted the admin log older than three months.', $_SESSION['user_logged']);
			break;

		case 26:
			$query = "DELETE FROM `log` WHERE DATE_SUB(CURDATE(), INTERVAL 6 MONTH) >= `log_time`";
			$msg = tr('%s deleted the admin log older than six months.', $_SESSION['user_logged']);
			break;
		case 52;
			$query = "DELETE FROM `log` WHERE DATE_SUB(CURDATE(), INTERVAL 1 YEAR) >= `log_time`";
			$msg = tr('%s deleted the admin log older than one year.', $_SESSION['user_logged']);
			break;
		default:
			return;
	}

	execute_query($query);
	write_log($msg, E_USER_NOTICE);

}

/******************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

// Dispatch the request
if(isset($_POST['uaction']) && $_POST['uaction'] == 'clear_log') {
	if(isset($_POST['uaction_clear']) && in_array($_POST['uaction_clear'], array(0, 2, 4, 12, 26, 52))) {
		admin_ClearLogs();
		set_page_message(tr('Logs successfully deleted.'), 'success');
	} else {
		set_page_message(tr('Wrong request'), 'error');
	}
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/admin_log.tpl',
		'page_message' => 'layout',
		'log_row' => 'page',
		'scroll_prev_gray' => 'page',
		'scroll_prev' => 'page',
		'scroll_next_gray' => 'page',
		'scroll_next' => 'page',
		'clear_log' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('iMSCP - Admin/Admin Log'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_ADMIN_LOG' => tr('Admin Log'),
		'TR_CLEAR_LOG' => tr('Clear log'),
		'TR_DATE' => tr('Date'),
		'TR_MESSAGE' => tr('Message'),
		'TR_CLEAR_LOG_MESSAGE' => tr('Delete from log:'),
		'TR_CLEAR_LOG_EVERYTHING' => tr('everything'),
		'TR_CLEAR_LOG_LAST2' => tr('older than 2 weeks'),
		'TR_CLEAR_LOG_LAST4' => tr('older than 1 month'),
		'TR_CLEAR_LOG_LAST12' => tr('older than 3 months'),
		'TR_CLEAR_LOG_LAST26' => tr('older than 6 months'),
		'TR_CLEAR_LOG_LAST52' => tr('older than 12 months')
	)
);

generateNavigation($tpl);
admin_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
