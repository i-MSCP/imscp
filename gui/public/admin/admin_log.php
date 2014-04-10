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
 * Functions
 */

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

	$stmt = execute_query($query);
	$nbDeletedEntries = $stmt->rowCount();

	if($nbDeletedEntries) {
		set_page_message(tr('Log entries successfully deleted.'), 'success');
		write_log($msg, E_USER_NOTICE);
	} else {
		set_page_message(tr('Nothing has been deleted.'), 'info');
	}
}

/**
 * Generate page data
 *
 * @param  iMSCP_pTemplate $tpl
 * @return void
 */
function admin_generatePageData($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$startIndex = 0;
	$rowPerPage = $cfg->DOMAIN_ROWS_PER_PAGE;

	if (isset($_GET['psi']) && is_numeric($_GET['psi'])) {
		$startIndex = intval($_GET['psi']);
	}

	$countQuery = "SELECT COUNT(`log_id`) `cnt` FROM `log`";
	$stmt = exec_query($countQuery);
	$recordsCount = $stmt->fields['cnt'];

	if(!$recordsCount) {
		$tpl->assign('LOGS', '');
		set_page_message(tr('No logs found.'), 'info');
	} else {
		$query = "
			SELECT
				DATE_FORMAT(`log_time`, '%Y-%m-%d %H:%i') `date`, `log_message`
			FROM
				`log`
			ORDER BY
				`log_time` DESC
			LIMIT
				$startIndex, $rowPerPage
		";
		$stmt = execute_query($query);

		if (!$stmt->rowCount()) {
			$tpl->assign(
				array(
					'LOG_ROW' => '',
					'PAG_MESSAGE' => tr('No logs found.'),
					'USERS_LIST' => '',
					'SCROLL_PREV' => '',
					'SCROLL_NEXT' => '',
					'CLEAR_LOG' => ''
				)
			);
		} else {
			$prevSi = $startIndex - $rowPerPage;

			if ($startIndex == 0) {
				$tpl->assign('SCROLL_PREV', '');
			} else {
				$tpl->assign(
					array(
						'SCROLL_PREV_GRAY' => '',
						'PREV_PSI' => $prevSi));
			}

			$nextSi = $startIndex + $rowPerPage;

			if ($nextSi + 1 > $recordsCount) {
				$tpl->assign('SCROLL_NEXT', '');
			} else {
				$tpl->assign(
					array(
						'SCROLL_NEXT_GRAY' => '',
						'NEXT_PSI' => $nextSi
					)
				);
			}

			$dateFormat = $cfg->DATE_FORMAT . ' H:i';

			while (!$stmt->EOF) {
				$logMessage = $stmt->fields['log_message'];

				$replaces = array(
					'/[^a-zA-Z](deactivated|delete[sd]?|deletion|deactivation|failed)[^a-zA-Z]/i' => ' <strong style="color:#FF0000">\\1</strong> ',
					'/[^a-zA-Z](remove[sd]?)[^a-zA-Z]/i' => ' <strong style="color:#FF0000">\\1</strong> ',
					'/[^a-zA-Z](unable?)[^a-zA-Z]/i' => ' <strong style="color:#FF0000">\\1</strong> ',
					'/[^a-zA-Z](activated|activation|addition|add(s|ed)?|switched)[^a-zA-Z]/i' => ' <strong style="color:#33CC66">\\1</strong> ',
					'/[^a-zA-Z](created|ordered)[^a-zA-Z]/i' => ' <strong style="color:#3300FF">\\1</strong> ',
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
						'DATE' => date($dateFormat, strtotime($stmt->fields['date'])
						)
					)
				);

				$tpl->parse('LOG_ROW', '.log_row');
				$stmt->moveNext();
			}
		}
	}
}

/******************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

// Dispatch the request
if (isset($_POST['uaction']) && $_POST['uaction'] == 'clear_log') {
	if (isset($_POST['uaction_clear']) && in_array($_POST['uaction_clear'], array(0, 2, 4, 12, 26, 52))) {
		admin_ClearLogs();
	} else {
		showBadRequestErrorPage();
	}
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/admin_log.tpl',
		'page_message' => 'layout',
		'logs' => 'page',
		'clear_log' => 'logs',
		'log_row' => 'logs',
		'scroll_prev_gray' => 'logs',
		'scroll_prev' => 'logs',
		'scroll_next_gray' => 'logs',
		'scroll_next' => 'logs'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / General / Admin Log'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_ADMIN_LOG' => tr('Admin Log'),
		'TR_CLEAR_LOG' => tr('Clear log'),
		'TR_DATE' => tr('Date'),
		'TR_MESSAGE' => tr('Message'),
		'TR_NEXT' => tr('Next'),
		'TR_PREVIOUS' => tr('Previous'),
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
admin_generatePageData($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(
	iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl)
);

$tpl->prnt();

unsetMessages();
