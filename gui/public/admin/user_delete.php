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
 * Deletes an admin or reseller user.
 *
 * @throws iMSCP_Exception_Database
 * @param int $userId User unique identifier
 */
function admin_deleteUser($userId)
{
	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeDeleteUser, array('userId' => $userId));

	$userId = (int)$userId;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	/** @var $db iMSCP_Database */
	$db = iMSCP_Database::getInstance();

	$query = "
		SELECT
			a.`admin_type`, b.`logo`
		FROM
		    `admin` a
		LEFT JOIN
			`user_gui_props` b ON (b.`user_id` = a.`admin_id`)
		WHERE
			`admin_id` = ?
	";

	$stmt = exec_query($query, $userId);
	$data = $stmt->fetchRow();
	$userType = $data['admin_type'];

	if (empty($userType) || $userType == 'user') {
		showBadRequestErrorPage();
	}

	// Users (admins/resellers) common items to delete
	$itemsToDelete = array(
		'admin' => 'admin_id = ?',
		'email_tpls' => 'owner_id = ?',
		'tickets' => 'ticket_from = ? OR ticket_to = ?',
		'user_gui_props' => 'user_id = ?'
	);

	// Note: Admin can also have they own hosting_plans bug must not be considerated
	// as common item since first admin must be never removed
	if ($userType == 'reseller') {
		// Getting reseller's software packages to remove if any
		$query = 'SELECT `software_id`, `software_archive` FROM `web_software` WHERE `reseller_id` = ?';
		$stmt = exec_query($query, $userId);
		$swPackages = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Getting custom reseller isp logo if set
		$resellerLogo = $data['logo'];

		// Add specific reseller items to remove
		$itemsToDelete = array_merge(
			array(
				'hosting_plans' => 'reseller_id = ?',
				'reseller_props' => 'reseller_id = ?',
				'web_software' => 'reseller_id = ?'
			),
			$itemsToDelete
		);
	}

	// We are using transaction to ensure data consistency and prevent any garbage in
	// the database. If one query fail, the whole process is reverted.

	try {
		// Cleanup database
		$db->beginTransaction();

		foreach ($itemsToDelete as $table => $where) {
			// Build the DELETE statement
			$query = "DELETE FROM "
				. quoteIdentifier($table)
				. (($where) ? " WHERE $where" : '');

			exec_query($query, array_fill(0, substr_count($where, '?'), $userId));
		}

		$db->commit();

		// Cleanup files system

		// We are safe here. We don't stop the process even if files cannot be removed. That can result in garbages but
		// the sysadmin can easily delete them through ssh.

		// Deleting reseller software instaler local repository
		if (isset($swPackages) && !empty($swPackages)) {
			_admin_deleteResellerSwPackages($userId, $swPackages);
		} elseif (
			$userType == 'reseller' && is_dir($cfg->GUI_APS_DIR . '/' . $userId) &&
			@rmdir($cfg->GUI_APS_DIR . '/' . $userId) == false
		) {
			write_log('Unable to remove reseller software directory: ' . $cfg->GUI_APS_DIR . '/' .
				$userId, E_USER_ERROR);
		}

		// Deleting user logo
		if (isset($resellerLogo) && !empty($resellerLogo)) {
			$logoPath = $cfg->GUI_ROOT_DIR . '/data/persistent/ispLogos/' . $resellerLogo;

			if (file_exists($logoPath) && @unlink($logoPath) == false) {
				write_log('Unable to remove user logo ' . $logoPath, E_USER_ERROR);
			}
		}

		$userTr = ($userType == 'reseller') ? tr('Reseller') : tr('Admin');
		set_page_message(tr('%s account successfully deleted.', $userTr), 'success');
		write_log($_SESSION['user_logged'] . ": deletes user " . $userId, E_USER_NOTICE);
	} catch (iMSCP_Exception_Database $e) {
		$db->rollBack();
		throw $e;
	}

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterDeleteUser, array('userId' => $userId));

	redirectTo('manage_users.php');
}

/**
 * Delete reseller software.
 *
 * @param int $userId Reseller unique identifier
 * @param array $swPackages Array that contains software package to remove
 * @return void
 */
function _admin_deleteResellerSwPackages($userId, array $swPackages)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// Remove all reseller's software packages if any
	foreach ($swPackages as $package) {
		$packagePath = $cfg->GUI_APS_DIR . '/' . $userId . '/' .
			$package['software_archive'] . '-' .
			$package['software_id'] . '.tar.gz';


		if (file_exists($packagePath) && !@unlink($packagePath)) {
			write_log('Unable to remove reseller package ' . $packagePath, E_USER_ERROR);
		}
	}

	// Remove reseller software installer local repository directory
	$resellerSwDirectory = $cfg->GUI_APS_DIR . '/' . $userId;

	if (is_dir($resellerSwDirectory) && @rmdir($resellerSwDirectory) == false) {
		write_log('Unable to remove reseller software repository: ' . $resellerSwDirectory, E_USER_ERROR);
	}
}

/**
 * Validates admin or reseller user deletion.
 *
 * @param int $userId User unique identifier
 * @return bool TRUE if deletion can be done, FALSE otherwise
 */
function admin_validateUserDeletion($userId)
{
	$userId = (int)$userId;
	$retVal = true;

	// User is super admin
	if ($userId == 1) {
		showBadRequestErrorPage();
	} else {
		$query = "
			SELECT
				`t1`.`admin_type`, `t1`.`admin_id`, COUNT(`t2`.`admin_id`) AS `customers_count`
			FROM
				`admin` AS `t1`
			LEFT JOIN
				`admin` AS `t2` ON
				(
					`t1`.`admin_id` <> `t2`.`admin_id`
					AND
					`t1`.`admin_id` = `t2`.`created_by`
					AND
						`t2`.`admin_type` <> 'reseller'
				)
			WHERE
				`t1`.admin_id = ?
	   ";
		$stmt = exec_query($query, $userId);

		// User has not been found or it's a customer
		if (!$stmt->rowCount() || $stmt->fields['admin_type'] == 'user') {
			showBadRequestErrorPage();
			// user (reseller) has customer accounts
		} elseif ($stmt->fields['customers_count']) {
			set_page_message(tr("You cannot delete a reseller that has customer accounts."), 'error');
			$retVal = false;
		}
	}

	return $retVal;
}

/**
 * Generates customer account deletion validation page.
 *
 * @param int $userId Customer account unique identifier
 * @return iMSCP_pTemplate
 */
function admin_generateCustomerAcountDeletionValidationPage($userId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT `admin_name` FROM `admin` WHERE `admin_id` = ?";
	$stmt = exec_query($query, $userId);

	if (!$stmt->rowCount()) {
		showBadRequestErrorPage();
	}

	$adminName = decode_idna($stmt->fields['admin_name']);

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'admin/user_delete.tpl',
			'page_message' => 'layout',
			'mail_list' => 'page',
			'mail_item' => 'mail_list',
			'ftp_list' => 'page',
			'ftp_item' => 'ftp_list',
			'dmn_list' => 'page',
			'dmn_item' => 'dmn_list',
			'als_list' => 'page',
			'als_item' => 'als_list',
			'sub_list' => 'page',
			'sub_item' => 'sub_list',
			'db_list' => 'page',
			'db_item' => 'db_list'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Admin / Users / Overview / Delete Customer'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_ACCOUNT_SUMMARY' => tr('Customer account summary'),
			'TR_EMAILS' => tr('Emails'),
			'TR_FTP_ACCOUNTS' => tr('Ftp accounts'),
			'TR_DOMAINS' => tr('Domains'),
			'TR_DOMAIN_ALIASES' => tr('Domain aliases'),
			'TR_SUBDOMAINS' => tr('Subdomains'),
			'TR_DATABASES' => tr('SQL databases'),
			'TR_REALLY_WANT_TO_DELETE_CUSTOMER_ACCOUNT' => tr(
				"Do you really want to delete the entire %s customer account? This operation cannot be undone.",
				true,
				"<strong>$adminName</strong>"
			),
			'USER_ID' => $userId,
			'TR_YES_DELETE_ACCOUNT' => tr('Yes, delete this account.'),
			'TR_DELETE' => tr('Delete'),
			'TR_CANCEL' => tr('Cancel')
		)
	);

	generateNavigation($tpl);

	// Checks for mail accounts

	$query = "
		SELECT
			`mail_type`, `mail_addr`
		FROM
			`mail_users`
		WHERE
			`domain_id` IN (SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?)
	";
	$stmt = exec_query($query, $userId);

	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$mailTypes = explode(',', $data['mail_type']);
			$mailTypesdisplayArray = array();

			foreach ($mailTypes as $mtype) {
				$mailTypesdisplayArray[] = user_trans_mail_type($mtype);
			}

			$mailTypesdisplayTxt = implode(', ', $mailTypesdisplayArray);
			$addr = explode('@', $data['mail_addr']);

			$tpl->assign(
				array(
					'MAIL_ADDR' => tohtml($addr[0] . '@' . decode_idna($addr[1])),
					'MAIL_TYPE' => $mailTypesdisplayTxt)
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');
		}
	} else {
		$tpl->assign('MAIL_LIST', '');
	}

	// Checks for FTP accounts

	$query = "SELECT `userid`, `homedir` FROM `ftp_users` WHERE `admin_id` = ?";
	$stmt = exec_query($query, $userId);

	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$username = explode('@', $data['userid']);

			$tpl->assign(
				array(
					'FTP_USER' => tohtml($username[0] . '@' . decode_idna($username[1])),
					'FTP_HOME' => tohtml(substr($data['homedir'], strlen($cfg->FTP_HOMEDIR)))
				)
			);

			$tpl->parse('FTP_ITEM', '.ftp_item');
		}
	} else {
		$tpl->assign('FTP_LIST', '');
	}

	// Check for domains
	// NOTE: Currently, each customer has only one domain but that will change in near future
	$query = "SELECT `domain_id`, `domain_name` FROM `domain` WHERE `domain_admin_id` = ?";
	$stmt = exec_query($query, $userId);

	$domainId = $stmt->fields['domain_id'];
	$domainName = tohtml(decode_idna($stmt->fields['domain_name']));

	$tpl->assign('DOMAIN_NAME', $domainName);
	$tpl->parse('DMN_ITEM', '.dmn_item');

	// Checks for domain's aliases

	$aliasIds = array();
	$query = "SELECT `alias_id`, `alias_name`, `alias_mount` FROM `domain_aliasses` WHERE `domain_id` = ?";
	$stmt = exec_query($query, $domainId);

	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$aliasIds[] = $data['alias_id'];

			$tpl->assign(
				array(
					'ALS_NAME' => tohtml(decode_idna($data['alias_name'])),
					'ALS_MNT' => tohtml($data['alias_mount'])
				)
			);

			$tpl->parse('ALS_ITEM', '.als_item');
		}
	} else {
		$tpl->assign('ALS_LIST', '');
	}

	// Checks for subdomains

	$query = "SELECT `subdomain_name`, `subdomain_mount` FROM `subdomain` WHERE `domain_id` = ?";
	$stmt = exec_query($query, $domainId);

	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$tpl->assign(
				array(
					'SUB_NAME' => tohtml(decode_idna($data['subdomain_name'])),
					'SUB_MNT' => tohtml($data['subdomain_mount'])
				)
			);

			$tpl->parse('SUB_ITEM', '.sub_item');
		}
	} else {
		$tpl->assign('SUB_LIST', '');
	}

	// Checks subdomain_alias

	if (count($aliasIds)) {
		$aliasIds = implode(',', $aliasIds);

		$query = "
			SELECT
				`subdomain_alias_name`, `subdomain_alias_mount`
			FROM
				`subdomain_alias`
			WHERE
				`alias_id` IN ($aliasIds)
		";
		$stmt = execute_query($query);

		if ($stmt->rowCount()) {
			while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$tpl->assign(
					array(
						'SUB_NAME' => tohtml(decode_idna($data['subdomain_alias_name'])),
						'SUB_MNT' => tohtml($data['subdomain_alias_mount'])
					)
				);

				$tpl->parse('SUB_ITEM', '.sub_item');
			}
		}
	}

	// Checks for databases and SQL users

	$stmt = exec_query('SELECT sqld_id, sqld_name FROM sql_database WHERE domain_id = ?', $domainId);

	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$stmt2 = exec_query('SELECT sqlu_name FROM sql_user WHERE sqld_id = ?', $data['sqld_id']);

			$sqlUsersList = array();

			if ($stmt2->rowCount()) {
				while ($data2 = $stmt2->fetchRow(PDO::FETCH_ASSOC)) {
					$sqlUsersList[] = $data2['sqlu_name'];
				}
			}

			$tpl->assign(
				array(
					'DB_NAME' => tohtml($data['sqld_name']),
					'DB_USERS' => tohtml(implode(', ', $sqlUsersList))
				)
			);

			$tpl->parse('DB_ITEM', '.db_item');
		}
	} else {
		$tpl->assign('DB_LIST', '');
	}

	return $tpl;
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

if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) { # admin/reseller deletion
	if (admin_validateUserDeletion($_GET['delete_id'])) {
		admin_deleteUser($_GET['delete_id']);
	}

	redirectTo('manage_users.php');

} elseif (isset($_GET['user_id'])) { # customer deletion validation page
	$tpl = admin_generateCustomerAcountDeletionValidationPage($_GET['user_id']);
} elseif (isset($_POST['user_id']) && isset($_POST['delete']) && $_POST['delete'] == 1) { # Customer deletion
	$userId = clean_input($_POST['user_id']);

	try {
		if (!deleteCustomer($userId)) {
			showBadRequestErrorPage();
		}

		set_page_message(tr('Customer account successfully scheduled for deletion.'), 'success');
		write_log(
			sprintf('%s scheduled deletion of the customer account with ID %d', $_SESSION['user_logged'], $userId),
			E_USER_NOTICE
		);
	} catch (iMSCP_Exception $e) {
		if (($previous = $e->getPrevious()) && ($previous instanceof iMSCP_Exception_Database)) {
			/** @var $previous iMSCP_Exception_Database */
			$queryMessagePart = ' Query was: ' . $previous->getQuery();
		} else {
			$queryMessagePart = '';
		}

		set_page_message(
			tr('Unable to schedule deletion of the customer account. Please consult admin logs or your mail for more information.'),
			'error'
		);
		write_log(
			sprintf(
				"System was unable to schedule deletion of customer account with ID %s. Message was: %s.",
				$userId, $e->getMessage() . $queryMessagePart
			),
			E_USER_ERROR
		);
	}

	redirectTo('manage_users.php');
} else {
	if (isset($_GET['delete'])) {
		showBadRequestErrorPage();
	} else {
		set_page_message(tr('You must confirm customer account deletion.'), 'error');
		redirectTo('user_delete.php?user_id=' . intval($_POST['user_id']));
	}

	redirectTo('manage_users.php');
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
