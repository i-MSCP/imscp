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
 * Generates user table.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 */
function admin_generateCustomersTable($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = 'SELECT `admin_id`, `admin_name` FROM `admin` WHERE `admin_type` = ? ORDER BY `admin_name`';
	$stmt = exec_query($query, 'reseller');

	if (!$stmt->rowCount()) { // Should never occurs
		set_page_message(tr('Reseller list is empty.'), 'error');
		redirectTo('manage_users.php');
	}

	$resellerId = $stmt->fields['admin_id'];
	$allResellers = array();

	while (!$stmt->EOF) {
		if ((isset($_POST['uaction']) && $_POST['uaction'] == 'change_src') && (isset($_POST['src_reseller']) &&
			$_POST['src_reseller'] == $stmt->fields['admin_id'])
		) {
			$selected = $cfg->HTML_SELECTED;
			$resellerId = $_POST['src_reseller'];
		} elseif ((isset($_POST['uaction']) && $_POST['uaction'] == 'move_user') && (isset($_POST['dst_reseller']) &&
			$_POST['dst_reseller'] == $stmt->fields['admin_id'])
		) {
			$selected = $cfg->HTML_SELECTED;
			$resellerId = $_POST['dst_reseller'];
		} else {
			$selected = '';
		}

		$allResellers[] = $stmt->fields['admin_id'];

		$tpl->assign(
			array(
				'SRC_RSL_OPTION' => tohtml($stmt->fields['admin_name']),
				'SRC_RSL_VALUE' => $stmt->fields['admin_id'],
				'SRC_RSL_SELECTED' => $selected));

		$tpl->parse('SRC_RESELLER_OPTION', '.src_reseller_option');

		$tpl->assign(
			array(
				'DST_RSL_OPTION' => tohtml($stmt->fields['admin_name']),
				'DST_RSL_VALUE' => $stmt->fields['admin_id'],
				'DST_RSL_SELECTED' => ''));

		$tpl->parse('DST_RESELLER_OPTION', '.dst_reseller_option');
		$stmt->moveNext();
	}

	if (isset($_POST['src_reseller']) && $_POST['src_reseller'] == 0) {
		$selected = $cfg->HTML_SELECTED;
		$resellerId = 0;
	} else {
		$selected = '';
	}

	$tpl->assign(
		array(
			'SRC_RSL_OPTION' => tr('N/A'),
			'SRC_RSL_VALUE' => 0,
			'SRC_RSL_SELECTED' => $selected));

	$tpl->parse('SRC_RESELLER_OPTION', '.src_reseller_option');

	// Must never occur in normal usage. Any user returned here are not assigned to a reseller
	if ($resellerId == 0) {
		$db = iMSCP_Database::getRawInstance();

		$query = '
			SELECT
				`admin_id`, `admin_name`
			FROM
				`admin`
			WHERE
				`admin_type` = ?
			AND
				`created_by` NOT IN (' . implode(',', array_map(array($db, 'quote'), $allResellers)) . ')
			ORDER BY
				`admin_name`
		';
		$stmt = exec_query($query, 'user');
	} else {
		$query = 'SELECT `admin_id`, `admin_name` FROM `admin` WHERE `admin_type` = ? AND `created_by` = ? ORDER BY `admin_name`';
		$stmt = exec_query($query, array('user', $resellerId));
	}

	if (!$stmt->rowCount()) {
		if ($resellerId) {
			set_page_message(tr('No users found for this reseller.'), 'info');
		} else {
			set_page_message(tr('No unassigned users were found in the database.'), 'info');
		}
		
		$tpl->assign('RESELLER_ITEM', '');
	} else {
		while (!$stmt->EOF) {
			$adminId = $stmt->fields['admin_id'];
			$adminIdVarname = 'admin_id_' . $adminId;
			$humanAdminName = decode_idna($stmt->fields['admin_name']);

			$tpl->assign(
				array(
					'CUSTOMER_ID' => $stmt->fields['admin_id'],
					'USER_NAME' => tohtml($humanAdminName),
					'CKB_NAME' => $adminIdVarname));

			$tpl->parse('RESELLER_ITEM', '.reseller_item');
			$stmt->moveNext();
		}

		$tpl->parse('RESELLER_LIST', 'reseller_list');
	}
}

/**
 * Check input data.
 *
 * @return bool TRUE if data are valid and consistents, FALSE otherwise
 */
function check_user_data()
{
	$query = 'SELECT `admin_id` FROM `admin` WHERE `admin_type` = ? ORDER BY `admin_name`';
	$stmt = exec_query($query, 'user');

	$selectedUsers = '';

	while (!$stmt->EOF) {
		$adminId = $stmt->fields['admin_id'];
		$adminIdVarname = 'admin_id_' . $adminId;

		if (isset($_POST[$adminIdVarname]) && $_POST[$adminIdVarname] === 'on') {
			$selectedUsers .= $adminId . ';';
		}

		$stmt->Movenext();
	}

	if ($selectedUsers == '') {
		set_page_message(tr('Please select at least one user.'), 'error');
		return false;
	} else if ($_POST['src_reseller'] == $_POST['dst_reseller']) {
		set_page_message(tr('Both source and destination are identical.'), 'error');
		return false;
	}

	$toReseller = $_POST['dst_reseller'];

	$query = 'SELECT `reseller_ips` FROM `reseller_props` WHERE `reseller_id` = ?';
	$stmt = exec_query($query, $toReseller);

	$errorsStack = '_off_';

	$toResellerIpAddr = $stmt->fields['reseller_ips'];

	check_ip_sets($toResellerIpAddr, $selectedUsers, $errorsStack);

	if ($errorsStack == '_off_') {
		admin_updateResellerLimits($_POST['dst_reseller'], $_POST['src_reseller'], $selectedUsers, $errorsStack);
	}

	if ($errorsStack != '_off_') {
		set_page_message($errorsStack, 'error');
		return false;
	}

	return true;
}

/**
 * Update resellers limit.
 *
 * @param int $toReseller Reseller for which the givens customer are moved to
 * @param int $fromReseller Reseller for wich the givens customers are moved from
 * @param int[] $users List of user to move
 * @param array $errorsStack Error stack
 * @return bool
 */
function admin_updateResellerLimits($toReseller, $fromReseller, $users, &$errorsStack)
{
	$toResellerProperties = imscp_getResellerProperties($toReseller);
	$fromResellerProperties = imscp_getResellerProperties($fromReseller, true);

	$usersList = explode(';', $users);

	for ($i = 0, $countUsersList = count($usersList) - 1; $i < $countUsersList; $i++) {
		$query = 'SELECT `domain_id`, `domain_name` FROM `domain` WHERE `domain_admin_id` = ?';
		$stmt = exec_query($query, $usersList[$i]);

		$domainName = $stmt->fields['domain_name'];
		$domainId = $stmt->fields['domain_id'];

		list(
			$subdomainsLimit, , $domainAliasesLimit, , $mailAccountsLimit, , $ftpAccountsLimit, , $sqlDatabasesLimit, ,
			$sqlUsersLimit, , $trafficLimit, $diskspaceLimit
			) = shared_getCustomerProps($domainId);

		calculate_reseller_dvals(
			$toResellerProperties['current_dmn_cnt'], $toResellerProperties['max_dmn_cnt'], $src_dmn_current,
			$fromResellerProperties['max_dmn_cnt'], 1, $errorsStack, 'Domain', $domainName);

		if ($errorsStack == '_off_') {
			calculate_reseller_dvals(
				$toResellerProperties['current_sub_cnt'], $toResellerProperties['max_sub_cnt'],
				$fromResellerProperties['current_sub_cnt'], $fromResellerProperties['max_sub_cnt'],
				$subdomainsLimit, $errorsStack, 'Subdomain', $domainName
			);

			calculate_reseller_dvals(
				$toResellerProperties['current_als_cnt'], $toResellerProperties['max_als_cnt'],
				$fromResellerProperties['current_als_cnt'], $fromResellerProperties['max_als_cnt'],
				$domainAliasesLimit, $errorsStack, 'Alias', $domainName
			);

			calculate_reseller_dvals(
				$toResellerProperties['current_mail_cnt'], $toResellerProperties['max_mail_cnt'],
				$fromResellerProperties['current_mail_cnt'], $fromResellerProperties['max_mail_cnt'],
				$mailAccountsLimit, $errorsStack, 'Mail', $domainName
			);

			calculate_reseller_dvals(
				$toResellerProperties['current_ftp_cnt'], $toResellerProperties['max_ftp_cnt'],
				$fromResellerProperties['current_ftp_cnt'], $fromResellerProperties['max_ftp_cnt'],
				$ftpAccountsLimit, $errorsStack, 'FTP', $domainName
			);

			calculate_reseller_dvals(
				$toResellerProperties['current_sql_db_cnt'], $toResellerProperties['max_sql_db_cnt'],
				$fromResellerProperties['current_sql_db_cnt'], $fromResellerProperties['max_sql_db_cnt'],
				$sqlDatabasesLimit, $errorsStack, 'SQL Database', $domainName
			);

			calculate_reseller_dvals(
				$toResellerProperties['current_sql_user_cnt'], $toResellerProperties['max_sql_user_cnt'],
				$fromResellerProperties['current_sql_user_cnt'], $fromResellerProperties['max_sql_user_cnt'],
				$sqlUsersLimit, $errorsStack, 'SQL User', $domainName
			);

			calculate_reseller_dvals(
				$toResellerProperties['current_traff_amnt'], $toResellerProperties['max_traff_amnt'],
				$fromResellerProperties['current_traff_amnt'], $fromResellerProperties['max_traff_amnt'],
				$trafficLimit, $errorsStack, 'Traffic', $domainName
			);

			calculate_reseller_dvals(
				$toResellerProperties['current_disk_amnt'], $toResellerProperties['max_disk_amnt'],
				$fromResellerProperties['current_disk_amnt'], $fromResellerProperties['max_disk_amnt'],
				$diskspaceLimit, $errorsStack, 'Disk', $domainName
			);
		}

		if ($errorsStack != '_off_') {
			return false;
		}
	}

	// Update reseller properties
	/** @var $db iMSCP_Database */
	$db = iMSCP_Database::getInstance();

	try {
		$db->beginTransaction();

		$newFromResellerProperties = "{$fromResellerProperties['current_dmn_cnt']};{$fromResellerProperties['max_dmn_cnt']};";
		$newFromResellerProperties .= "{$fromResellerProperties['current_sub_cnt']};{$fromResellerProperties['max_sub_cnt']};";
		$newFromResellerProperties .= "{$fromResellerProperties['current_als_cnt']};{$fromResellerProperties['max_als_cnt']};";
		$newFromResellerProperties .= "{$fromResellerProperties['current_mail_cnt']};{$fromResellerProperties['max_mail_cnt']};";
		$newFromResellerProperties .= "{$fromResellerProperties['current_ftp_cnt']};{$fromResellerProperties['max_ftp_cnt']};";
		$newFromResellerProperties .= "{$fromResellerProperties['current_sql_db_cnt']};{$fromResellerProperties['max_sql_db_cnt']};";
		$newFromResellerProperties .= "{$fromResellerProperties['current_sql_user_cnt']};{$fromResellerProperties['max_sql_user_cnt']};";
		$newFromResellerProperties .= "{$fromResellerProperties['current_traff_amnt']};{$fromResellerProperties['max_traff_amnt']};";
		$newFromResellerProperties .= "{$fromResellerProperties['current_disk_amnt']};{$fromResellerProperties['max_disk_amnt']};";

		update_reseller_props($fromReseller, $newFromResellerProperties);

		$newToResellerProperties = "{$toResellerProperties['current_dmn_cnt']};{$toResellerProperties['max_dmn_cnt']};";
		$newToResellerProperties .= "{$toResellerProperties['current_sub_cnt']};{$toResellerProperties['max_sub_cnt']};";
		$newToResellerProperties .= "{$toResellerProperties['current_als_cnt']};{$toResellerProperties['max_als_cnt']};";
		$newToResellerProperties .= "{$toResellerProperties['current_mail_cnt']};{$toResellerProperties['max_mail_cnt']};";
		$newToResellerProperties .= "{$toResellerProperties['current_ftp_cnt']};{$toResellerProperties['max_ftp_cnt']};";
		$newToResellerProperties .= "{$toResellerProperties['current_sql_db_cnt']};{$toResellerProperties['max_sql_db_cnt']};";
		$newToResellerProperties .= "{$toResellerProperties['current_sql_user_cnt']};{$toResellerProperties['max_sql_user_cnt']};";
		$newToResellerProperties .= "{$toResellerProperties['current_traff_amnt']};{$toResellerProperties['max_traff_amnt']};";
		$newToResellerProperties .= "{$toResellerProperties['current_disk_amnt']};{$toResellerProperties['max_disk_amnt']};";

		update_reseller_props($toReseller, $newToResellerProperties);

		for ($i = 0, $countUsersList = count($usersList) - 1; $i < $countUsersList; $i++) {
			$query = 'UPDATE `admin` SET `created_by` = ? WHERE `admin_id` = ?';
			exec_query($query, array($toReseller, $usersList[$i]));
		}

		$db->commit();
	} catch (iMSCP_Exception_Database $e) {
		$db->rollBack();
		throw $e;
	}

	return true;
}

/**
 * @param $to
 * @param $toMax
 * @param $from
 * @param $fromMax
 * @param $uMax
 * @param $errorsStack
 * @param $obj
 * @param $uName
 * @return mixed
 */
function calculate_reseller_dvals(&$to, $toMax, &$from, $fromMax, $uMax, &$errorsStack, $obj, $uName)
{
	if ($toMax == 0 && $fromMax == 0 && $uMax == -1) {
		return;
	} else if ($toMax == 0 && $fromMax == 0 && $uMax == 0) {
		return;
	} else if ($toMax == 0 && $fromMax == 0 && $uMax > 0) {
		$from -= $uMax;
		$to += $uMax;
		return;
	} else if ($toMax == 0 && $fromMax > 0 && $uMax == -1) {
		return;
	} else if ($toMax == 0 && $fromMax > 0 && $uMax == 0) {
		// Impossible condition;
		return;
	} else if ($toMax == 0 && $fromMax > 0 && $uMax > 0) {
		$from -= $uMax;
		$to += $uMax;
		return;
	} else if ($toMax > 0 && $fromMax == 0 && $uMax == -1) {
		return;
	} else if ($toMax > 0 && $fromMax == 0 && $uMax == 0) {
		if ($errorsStack == '_off_') {
			$errorsStack = '';
		}

		$errorsStack .= tr('<b>%1$s</b> has unlimited rights for a <b>%2$s</b> Service.<br />', $uName, $obj);
		$errorsStack .= tr('You cannot move <b>%1$s</b> in a destination reseller,<br />which has limits for the <b>%2$s</b> service.', $uName, $obj);
		return;
	} else if ($toMax > 0 && $fromMax == 0 && $uMax > 0) {
		if ($to + $uMax > $toMax) {
			if ($errorsStack == '_off_') {
				$errorsStack = '';
			}
			$errorsStack .= tr('<b>%1$s</b> is exceeding limits for a <b>%2$s</b><br />service in destination reseller.<br />', $uName, $obj);

			$errorsStack .= tr('Moving aborted.');
		} else {
			$from -= $uMax;

			$to += $uMax;
		}

		return;
	} else if ($toMax > 0 && $fromMax > 0 && $uMax == -1) {
		return;
	} else if ($toMax > 0 && $fromMax > 0 && $uMax == 0) {
		// Impossible condition;
		return;
	} else if ($toMax > 0 && $fromMax > 0 && $uMax > 0) {
		if ($to + $uMax > $toMax) {
			if ($errorsStack == '_off_') {
				$errorsStack = '';
			}

			$errorsStack .= tr('<b>%1$s</b> is exceeding limits for a <b>%2$s</b><br />service in destination reseller.<br />', $uName, $obj);
			$errorsStack .= tr('Moving aborted.');
		} else {
			$from -= $uMax;
			$to += $uMax;
		}

		return;
	}
}

/**
 * @param $to
 * @param $customersList
 * @param $errorsStack
 * @return bool
 */
function check_ip_sets($to, $customersList, &$errorsStack)
{
	$customersList = explode(';', $customersList);

	for ($i = 0, $countCustomersList = count($customersList); $i < $countCustomersList; $i++) {
		$query = 'SELECT `domain_name`, `domain_ip_id` FROM `domain` WHERE `domain_admin_id` = ?';
		$stmt = exec_query($query, $customersList[$i]);

		$domainIpAddrId = $stmt->fields['domain_ip_id'];
		$domainName = $stmt->fields['domain_name'];

		if (!preg_match("/$domainIpAddrId;/", $to)) {
			if ($errorsStack == '_off_') {
				$errorsStack = '';
			}

			$errorsStack .= tr('<b>%s</b> has IP address that cannot be managed from the destination reseller!<br />This user cannot be moved!', $domainName);
			return false;
		}
	}

	return true;
}

/***********************************************************************************************************************
 * Main
 *
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

if(!systemHasResellers(2)) {
	showBadRequestErrorPage();
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_POST['uaction']) && $_POST['uaction'] == 'move_user' && check_user_data()) {
	set_page_message(tr('Customer(s) successfully moved.'), 'success');
	redirectTo('manage_users.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/manage_reseller_users.tpl',
		'page_message' => 'layout',
		'reseller_list' => 'page',
		'reseller_item' => 'page',
		'src_reseller' => 'page',
		'src_reseller_option' => 'src_reseller',
		'dst_reseller' => 'page',
		'dst_reseller_option' => 'dst_reseller'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Users / Customers Assignment'),
		'ISP_LOGO' => layout_getUserLogo(),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_USER_ASSIGNMENT' => tr('User assignment'),
		'TR_RESELLER_USERS' => tr('Users'),
		'TR_CUSTOMER_ID' => tr('Customer ID'),
		'TR_MARK' => tr('Mark'),
		'TR_USER_NAME' => tr('Username'),
		'TR_FROM_RESELLER' => tr('From reseller'),
		'TR_TO_RESELLER' => tr('To reseller'),
		'TR_MOVE' => tr('Move')));

generateNavigation($tpl);
admin_generateCustomersTable($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
