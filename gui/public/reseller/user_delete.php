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
 * @subpackage  Reseller
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
 * Generates customer account deletion validation page.
 *
 * @param int $customerId Customer unique identifier
 * @return iMSCP_pTemplate
 */
function reseller_generateCustomerAcountDeletionValidationPage($customerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT `admin_name` FROM `admin` WHERE `admin_id` = ? AND `created_by` = ?";
	$stmt = exec_query($query, array($customerId, $_SESSION['user_id']));

	if (!$stmt->rowCount()) {
		showBadRequestErrorPage();
	}

	$adminName = decode_idna($stmt->fields['admin_name']);

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'reseller/user_delete.tpl',
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
			'TR_PAGE_TITLE' => tr('Reseller / Customers / Overview / Delete Customer'),
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
			'USER_ID' => $customerId,
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
	$stmt = exec_query($query, $customerId);

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
					'MAIL_TYPE' => $mailTypesdisplayTxt
				)
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');
		}
	} else {
		$tpl->assign('MAIL_LIST', '');
	}

	// Checks for FTP accounts in domain

	$query = "SELECT `userid`, `homedir` FROM `ftp_users` WHERE `admin_id` = ?";
	$stmt = exec_query($query, $customerId);

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
	// NOTE: Currently, we have only one domain but that will change ASAP
	$query = "SELECT `domain_id`, `domain_name` FROM `domain` WHERE `domain_admin_id` = ?";
	$stmt = exec_query($query, $customerId);

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

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

if (isset($_GET['id']) && !empty($_GET['id'])) {
	$tpl = reseller_generateCustomerAcountDeletionValidationPage($_GET['id']);
} elseif (isset($_POST['id']) && isset($_POST['delete']) && $_POST['delete'] == 1) {
	$customerId = clean_input($_POST['id']);

	try {
		if (!deleteCustomer($customerId, true)) {
			showBadRequestErrorPage();
		}

		set_page_message(tr('Customer account successfully scheduled for deletion.'), 'success');
		write_log(
			sprintf('%s scheduled deletion of the customer account with ID %d', $_SESSION['user_logged'], $customerId),
			E_USER_NOTICE
		);
	} catch (iMSCP_Exception $e) {
		set_page_message(
			tr('Unable to schedule deletion of the customer account. A message has been sent to the administrator.'),
			'error'
		);
		write_log(
			sprintf(
				"System was unable to schedule deletion of the customer account with ID %s. Message was: %s",
				$customerId, $e->getMessage()
			),
			E_USER_ERROR
		);
	}

	redirectTo('users.php');
} else {
	if (isset($_GET['delete'])) {
		showBadRequestErrorPage();
	} else {
		set_page_message(tr('You must confirm customer account deletion.'), 'error');
		redirectTo('user_delete.php?id=' . $_POST['id']);
	}

	redirectTo('users.php');
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
