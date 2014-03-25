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
 * @copyright    2001-2006 by moleSoftware GmbH
 * @copyright    2006-2010 by ispCP | http://isp-control.net
 * @copyright    2010-2014 by i-MSCP | http://i-mscp.net
 * @link         http://i-mscp.net
 * @author       ispCP Team
 * @author       i-MSCP Team
 */

/**
 * Returns Ip list.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function generate_ip_list($tpl)
{
	global $domainIp;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = execute_query("SELECT * FROM `server_ips`");

	while ($data = $stmt->fetchRow()) {
		$ipId = $data['ip_id'];

		$selected = ($domainIp === $ipId) ? $cfg->HTML_SELECTED : '';

		$tpl->assign(
			array(
				'IP_NUM' => $data['ip_number'],
				'IP_VALUE' => $ipId,
				'IP_SELECTED' => $selected
			)
		);

		$tpl->parse('IP_ENTRY', '.ip_entry');
	}
}

/**
 * Helper function to generate admin list template part.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function gen_admin_list($tpl)
{
	/** @var $cfg  iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			t1.`admin_id`, t1.`admin_name`, t1.`domain_created`, IFNULL(t2.`admin_name`, '') AS `created_by`
		FROM
			`admin` AS `t1`
		LEFT JOIN
			`admin` AS `t2` ON (`t1`.`created_by` = `t2`.`admin_id`)
		WHERE
			`t1`.`admin_type` = 'admin'
		ORDER BY
			`t1`.`admin_name` ASC
	";
	$stmt = execute_query($query);

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'ADMIN_MESSAGE' => tr('No administrator accounts found.'),
				'ADMIN_LIST' => ''
			)
		);

		$tpl->parse('ADMIN_MESSAGE', 'admin_message');
	} else {
		$tpl->assign(
			array(
				'TR_ADMIN_USERNAME' => tr('Username'),
				'TR_ADMIN_CREATED_ON' => tr('Creation date'),
				'TR_ADMIN_CREATED_BY' => tr('Created by'),
				'TR_ADMIN_ACTIONS' => tr('Actions')
			)
		);

		while (!$stmt->EOF) {
			$adminCreated = $stmt->fields['domain_created'];

			if ($adminCreated == 0) {
				$adminCreated = tr('N/A');
			} else {
				$dateFormat = $cfg->DATE_FORMAT;
				$adminCreated = date($dateFormat, $adminCreated);
			}

			if ($stmt->fields['created_by'] == '' || $stmt->fields['admin_id'] == $_SESSION['user_id']) {

				$tpl->assign('ADMIN_DELETE_LINK', '');
				$tpl->parse('ADMIN_DELETE_SHOW', 'admin_delete_show');
			} else {
				$tpl->assign(
					array(
						'ADMIN_DELETE_SHOW' => '',
						'TR_DELETE' => tr('Delete'),
						'URL_DELETE_ADMIN' => 'user_delete.php?delete_id=' . $stmt->fields['admin_id'],
						'ADMIN_USERNAME' => tohtml($stmt->fields['admin_name'])
					)
				);

				$tpl->parse('ADMIN_DELETE_LINK', 'admin_delete_link');
			}

			$tpl->assign(
				array(
					'ADMIN_USERNAME' => tohtml($stmt->fields['admin_name']),
					'ADMIN_CREATED_ON' => tohtml($adminCreated),
					'ADMIN_CREATED_BY' => ($stmt->fields['created_by'] != null)
						? tohtml($stmt->fields['created_by']) : tr("System"),
					'URL_EDIT_ADMIN' => 'admin_edit.php?edit_id=' . $stmt->fields['admin_id']
				)
			);

			$tpl->parse('ADMIN_ITEM', '.admin_item');
			$stmt->moveNext();
		}

		$tpl->parse('ADMIN_LIST', 'admin_list');
		$tpl->assign('ADMIN_MESSAGE', '');
	}
}

/**
 * Helper function to generate reseller list template part.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function gen_reseller_list($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`t1`.`admin_id`, `t1`.`admin_name`, `t1`.`domain_created`, IFNULL(t2.`admin_name`, '') AS `created_by`
		FROM
			`admin` AS `t1`
		LEFT JOIN
			`admin` AS `t2` ON (`t1`.`created_by` = `t2`.`admin_id`)
		WHERE
			`t1`.`admin_type` = 'reseller'
		ORDER BY
			`t1`.`admin_name` ASC
	";
	$stmt = execute_query($query);

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'RSL_MESSAGE' => tr('No reseller accounts found.'),
				'RSL_LIST' => ''
			)
		);

		$tpl->parse('RSL_MESSAGE', 'rsl_message');
	} else {
		$tpl->assign(
			array(
				'TR_RSL_USERNAME' => tr('Username'),
				'TR_RSL_CREATED_BY' => tr('Created by'),
				'TR_RSL_ACTIONS' => tr('Actions')
			)
		);

		while (!$stmt->EOF) {
			if ($stmt->fields['created_by'] == '') {
				$tpl->assign(
					array(
						'TR_DELETE' => tr('Delete'),
						'RSL_DELETE_LINK' => ''
					)
				);

				$tpl->parse('RSL_DELETE_SHOW', 'rsl_delete_show');
			} else {
				$tpl->assign(
					array(
						'RSL_DELETE_SHOW' => '',
						'TR_DELETE' => tr('Delete'),
						'URL_DELETE_RSL' => 'user_delete.php?delete_id=' . $stmt->fields['admin_id'],
						'TR_CHANGE_USER_INTERFACE' => tr('Switch to user interface'),
						'GO_TO_USER_INTERFACE' => tr('Switch'),
						'URL_CHANGE_INTERFACE' => 'change_user_interface.php?to_id=' .
						$stmt->fields['admin_id']
					)
				);

				$tpl->parse('RSL_DELETE_LINK', 'rsl_delete_link');
			}

			$resellerCreated = $stmt->fields['domain_created'];

			if ($resellerCreated == 0) {
				$resellerCreated = tr('N/A');
			} else {
				$date_formt = $cfg->DATE_FORMAT;
				$resellerCreated = date($date_formt, $resellerCreated);
			}

			$tpl->assign(
				array(
					'RSL_USERNAME' => tohtml($stmt->fields['admin_name']),
					'RESELLER_CREATED_ON' => tohtml($resellerCreated),
					'RSL_CREATED_BY' => tohtml($stmt->fields['created_by']),
					'URL_EDIT_RSL' => 'reseller_edit.php?edit_id=' . $stmt->fields['admin_id']
				)
			);

			$tpl->parse('RSL_ITEM', '.rsl_item');
			$stmt->moveNext();
		}

		$tpl->parse('RSL_LIST', 'rsl_list');
		$tpl->assign('RSL_MESSAGE', '');
	}
}

/**
 * Helper function to generate a user list.
 *
 * @param iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function gen_user_list($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$startIndex = 0;
	$rowsPerPage = $cfg->DOMAIN_ROWS_PER_PAGE;

	if (isset($_GET['psi']) && $_GET['psi'] == 'last') {
		if (isset($_SESSION['search_page'])) {
			$_GET['psi'] = $_SESSION['search_page'];
		} else {
			unset($_GET['psi']);
		}
	}

	if (isset($_GET['psi'])) {
		$startIndex = $_GET['psi'];
	}

	// Search request generated ?
	if (isset($_POST['uaction']) && !empty($_POST['uaction'])) {
		$_SESSION['search_for'] = clean_input($_POST['search_for']);
		$_SESSION['search_common'] = $_POST['search_common'];
		$_SESSION['search_status'] = $_POST['search_status'];
		$startIndex = 0;
	} elseif (isset($_SESSION['search_for']) && !isset($_GET['psi'])) {
		// He have not got scroll through patient records
		unset($_SESSION['search_for']);
		unset($_SESSION['search_common']);
		unset($_SESSION['search_status']);
	}

	$searchQuery = $countQuery = '';

	if (isset($_SESSION['search_for'])) {
		gen_admin_domain_query(
			$searchQuery, $countQuery, $startIndex, $rowsPerPage, $_SESSION['search_for'], $_SESSION['search_common'],
			$_SESSION['search_status']
		);

		gen_admin_domain_search_options(
			$tpl, $_SESSION['search_for'], $_SESSION['search_common'], $_SESSION['search_status']
		);

		$stmt = exec_query($countQuery);
	} else {
		gen_admin_domain_query($searchQuery, $countQuery, $startIndex, $rowsPerPage, 'n/a', 'n/a', 'n/a');
		gen_admin_domain_search_options($tpl, 'n/a', 'n/a', 'n/a');
		$stmt = exec_query($countQuery);
	}

	$recordCount = $stmt->fields['cnt'];
	$stmt = execute_query($searchQuery);

	if (!$stmt->rowCount()) {
		if (isset($_SESSION['search_for'])) {
			$tpl->assign(
				array(
					'USR_MESSAGE' => tr('No records found matching the search criteria.'),
					'USR_LIST' => '',
					'SCROLL_PREV' => '',
					'SCROLL_NEXT' => '',
					'TR_VIEW_DETAILS' => tr('view aliases'),
					'SHOW_DETAILS' => 'show'
				)
			);

			unset($_SESSION['search_for']);
			unset($_SESSION['search_common']);
			unset($_SESSION['search_status']);
		} else {
			$tpl->assign(
				array(
					'SEARCH_FORM' => '',
					'USR_MESSAGE' => tr('No customer accounts found.'),
					'USR_LIST' => '',
					'SCROLL_PREV' => '',
					'SCROLL_NEXT' => '',
					'TR_VIEW_DETAILS' => tr('view aliases'),
					'SHOW_DETAILS' => 'show'
				)
			);
		}

		$tpl->parse('USR_MESSAGE', 'usr_message');
	} else {
		$prevSi = $startIndex - $rowsPerPage;

		if ($startIndex == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(array(
				'SCROLL_PREV_GRAY' => '',
				'PREV_PSI' => $prevSi));
		}

		$nextSi = $startIndex + $rowsPerPage;

		if ($nextSi + 1 > $recordCount) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_NEXT_GRAY' => '',
					'NEXT_PSI' => $nextSi
				)
			);
		}

		$tpl->assign(
			array(
				'TR_USR_USERNAME' => tr('Username'),
				'TR_USR_CREATED_BY' => tr('Created by'),
				'TR_USR_ACTIONS' => tr('Actions'),
				'TR_USER_STATUS' => tr('Status'),
				'TR_DETAILS' => tr('Details')
			)
		);

		while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			// user status icon
			$domainCreatedBy = $data['created_by'];

			$stmt2 = exec_query('SELECT admin_name, admin_status FROM admin WHERE admin_id = ?', $domainCreatedBy);

			if (!isset($stmt2->fields['admin_name'])) {
				$createdByName = tr('N/A');
			} else {
				$createdByName = $stmt2->fields['admin_name'];
			}

			$tpl->assign(
				array(
					'USR_DELETE_SHOW' => '',
					'USER_ID' =>$data['admin_id'],
					'DOMAIN_ID' => $data['domain_id'],
					'TR_DELETE' => tr('Delete'),
					'URL_DELETE_USR' => 'user_delete.php?domain_id=' . $data['domain_id'],
					'TR_CHANGE_USER_INTERFACE' => tr('Switch to user interface'),
					'GO_TO_USER_INTERFACE' => tr('Switch'),
					'URL_CHANGE_INTERFACE' => 'change_user_interface.php?to_id=' . $data['domain_admin_id'],
					'USR_USERNAME' => tohtml($data['domain_name']),
					'TR_EDIT_DOMAIN' => tr('Edit domain'),
					'TR_EDIT_USR' => tr('Edit user')
				)
			);

			$tpl->parse('USR_DELETE_LINK', 'usr_delete_link');

			if ($data['admin_status'] == 'ok' && $data['domain_status'] == 'ok') {
				$status = 'ok';
				$statusTxt = translate_dmn_status($data['domain_status']);
				$statusUrl = 'domain_status_change.php?domain_id=' . $data['domain_id'];
				$statusBool = true;
			} elseif ($data['domain_status'] == 'disabled') {
				$status = 'disabled';
				$statusTxt = translate_dmn_status($data['domain_status']);
				$statusUrl = 'domain_status_change.php?domain_id=' . $data['domain_id'];
				$statusBool = false;
			} elseif (
				(
					$data['admin_status'] == 'toadd' ||
					$data['admin_status'] == 'tochange' ||
					$data['admin_status'] == 'todelete'
				) || (
					$data['domain_status'] == 'toadd' ||
					$data['domain_status'] == 'torestore' ||
					$data['domain_status'] == 'tochange' ||
					$data['domain_status'] == 'toenable' ||
					$data['domain_status'] == 'todisable' ||
					$data['domain_status'] == 'todelete'
				)
			) {

				$status = 'reload';
				$statusTxt = translate_dmn_status(
					($data['admin_status'] != 'ok') ? $data['admin_status'] : $data['domain_status']
				);
				$statusUrl = '#';
				$statusBool = false;
			} else {
				$status = 'error';
				$statusTxt = translate_dmn_status(
					($data['admin_status'] != 'ok') ? $data['admin_status'] : $data['domain_status']
				);
				$statusUrl = 'domain_details.php?domain_id=' . $data['domain_id'];
				$statusBool = false;
			}

			$tpl->assign(
				array(
					'STATUS' => $status,
					'TR_STATUS' => $statusTxt,
					'URL_CHANGE_STATUS' => $statusUrl
				)
			);

			$adminName = decode_idna($data['domain_name']);
			$domainCreated = $data['domain_created'];

			if ($domainCreated == 0) {
				$domainCreated = tr('N/A');
			} else {
				$date_formt = $cfg->DATE_FORMAT;
				$domainCreated = date($date_formt, $domainCreated);
			}

			$domainExpires = $data['domain_expires'];

			if ($domainExpires == 0) {
				$domainExpires = tr('Not Set');
			} else {
				$date_formt = $cfg->DATE_FORMAT;
				$domainExpires = date($date_formt, $domainExpires);
			}

			if ($statusBool == false) { // reload
				$tpl->assign('USR_STATUS_RELOAD_TRUE', '');
				$tpl->assign('USR_USERNAME', tohtml($adminName));
				$tpl->parse('USR_STATUS_RELOAD_FALSE', 'usr_status_reload_false');
			} else {
				$tpl->assign('USR_STATUS_RELOAD_FALSE', '');
				$tpl->assign('USR_USERNAME', tohtml($adminName));
				$tpl->parse('USR_STATUS_RELOAD_TRUE', 'usr_status_reload_true');
			}

			$tpl->assign(
				array(
					'USER_CREATED_ON' => tohtml($domainCreated),
					'USER_EXPIRES_ON' => $domainExpires,
					'USR_CREATED_BY' => tohtml($createdByName),
					'USR_OPTIONS' => '',
					'URL_EDIT_USR' => 'admin_edit.php?edit_id=' . $data['domain_admin_id'],
					'TR_MESSAGE_CHANGE_STATUS' => tr('Are you sure you want to change the status of %s domain account?', '%s'),
					'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', '%s'
					)
				)
			);

			gen_domain_details($tpl, $data['domain_id']);

			$tpl->parse('USR_ITEM', '.usr_item');
		}

		$tpl->parse('USR_LIST', 'usr_list');
		$tpl->assign('USR_MESSAGE', '');
	}
}

/**
 * Helper function to generate manage users template part.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function get_admin_manage_users($tpl)
{
	$tpl->assign(
		array(
			'TR_MANAGE_USERS' => tr('Manage users'),
			'TR_ADMINISTRATORS' => tr('Administrators'),
			'TR_RESELLERS' => tr('Resellers'),
			'TR_CUSTOMERS' => tr('Customers'),
			'TR_SEARCH' => tr('Search'),
			'TR_CREATED_ON' => tr('Creation date'),
			'TR_EXPIRES_ON' => tr('Expire date'),
			'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
			'TR_EDIT' => tr('Edit')
		)
	);

	gen_admin_list($tpl);
	gen_reseller_list($tpl);
	gen_user_list($tpl);
}

/**
 * Helper function to generate domain search form template part.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @param  string $searchFor Object to search for
 * @param  string $searchCommon Common object to search for
 * @param  $searchStatus Object status to search for
 * @return void
 */
function gen_admin_domain_search_options($tpl, $searchFor, $searchCommon, $searchStatus)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$domainSelected = $customerIdSelected = $lastnameSelected = $companySelected = $citySelected = $stateSelected =
	$countrySelected = $allSelected = $okSelected = $suspendedSelected = '';

	if ($searchFor == 'n/a' && $searchCommon == 'n/a' && $searchStatus == 'n/a') {
		// we have no search and let's generate search fields empty
		$domainSelected = $cfg->HTML_SELECTED;
		$allSelected = $cfg->HTML_SELECTED;
	}

	if ($searchCommon == 'domain_name') {
		$domainSelected = $cfg->HTML_SELECTED;
	} elseif ($searchCommon == 'customer_id') {
		$customerIdSelected = $cfg->HTML_SELECTED;
	} elseif ($searchCommon == 'lname') {
		$lastnameSelected = $cfg->HTML_SELECTED;
	} elseif ($searchCommon === 'firm') {
		$companySelected = $cfg->HTML_SELECTED;
	} elseif ($searchCommon == 'city') {
		$citySelected = $cfg->HTML_SELECTED;
	} elseif ($searchCommon == 'state') {
		$stateSelected = $cfg->HTML_SELECTED;
	} elseif ($searchCommon == 'country') {
		$countrySelected = $cfg->HTML_SELECTED;
	}

	if ($searchStatus == 'all') {
		$allSelected = $cfg->HTML_SELECTED;
	} elseif ($searchStatus == 'ok') {
		$okSelected = $cfg->HTML_SELECTED;
	} elseif ($searchStatus == 'disabled') {
		$suspendedSelected = $cfg->HTML_SELECTED;
	}

	if ($searchFor == 'n/a' || $searchFor == '') {
		$tpl->assign(array('SEARCH_FOR' => ''));
	} else {
		$tpl->assign(array('SEARCH_FOR' => $searchFor));
	}

	$tpl->assign(
		array(
			'M_DOMAIN_NAME' => tr('Domain name'),
			'M_CUSTOMER_ID' => tr('Customer ID'),
			'M_LAST_NAME' => tr('Last name'),
			'M_COMPANY' => tr('Company'),
			'M_CITY' => tr('City'),
			'M_STATE' => tr('State/Province'),
			'M_COUNTRY' => tr('Country'),
			'M_ALL' => tr('All'),
			'M_OK' => tr('OK'),
			'M_SUSPENDED' => tr('Suspended'),
			'M_ERROR' => tr('Error'),
			'M_DOMAIN_NAME_SELECTED' => $domainSelected,
			'M_CUSTOMER_ID_SELECTED' => $customerIdSelected,
			'M_LAST_NAME_SELECTED' => $lastnameSelected,
			'M_COMPANY_SELECTED' => $companySelected,
			'M_CITY_SELECTED' => $citySelected,
			'M_STATE_SELECTED' => $stateSelected,
			'M_COUNTRY_SELECTED' => $countrySelected,
			'M_ALL_SELECTED' => $allSelected,
			'M_OK_SELECTED' => $okSelected,
			'M_SUSPENDED_SELECTED' => $suspendedSelected
		)
	);
}
