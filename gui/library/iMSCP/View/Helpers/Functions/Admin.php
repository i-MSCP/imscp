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
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2012 by i-MSCP | http://i-mscp.net
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 */

/************************************************************************************
 * This file contains view helpers functions that are responsible to generate
 * template parts for admin interface.
 */

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
			t1.`admin_id`, t1.`admin_name`, t1.`domain_created`,
			IFNULL(t2.`admin_name`, '') AS `created_by`
		FROM
			`admin` AS `t1`
		LEFT JOIN
			`admin` AS `t2` ON `t1`.`created_by` = t2.`admin_id`
		WHERE
			`t1`.`admin_type` = 'admin'
		ORDER BY
			`t1`.`admin_name` ASC
	";
	$rs = execute_query($query);

	if ($rs->recordCount() == 0) {
		$tpl->assign(array(
						  'ADMIN_MESSAGE' => tr('No administrator accounts found.'),
						  'ADMIN_LIST' => ''));

		$tpl->parse('ADMIN_MESSAGE', 'admin_message');
	} else {
		$tpl->assign(array(
						  'TR_ADMIN_USERNAME' => tr('Username'),
						  'TR_ADMIN_CREATED_ON' => tr('Creation date'),
						  'TR_ADMIN_CREATED_BY' => tr('Created by'),
						  'TR_ADMIN_ACTIONS' => tr('Actions')));

		$i = 0;
		while (!$rs->EOF) {
			$tpl->assign('ADMIN_CLASS', ($i % 2 == 0) ? 'content' : 'content2');
			$admin_created = $rs->fields['domain_created'];

			if ($admin_created == 0) {
				$admin_created = tr('N/A');
			} else {
				$date_formt = $cfg->DATE_FORMAT;
				$admin_created = date($date_formt, $admin_created);
			}

			if ($rs->fields['created_by'] == '' ||
				$rs->fields['admin_id'] == $_SESSION['user_id']
			) {

				$tpl->assign('ADMIN_DELETE_LINK', '');
				$tpl->parse('ADMIN_DELETE_SHOW', 'admin_delete_show');
			} else {
				$tpl->assign(array(
								  'ADMIN_DELETE_SHOW' => '',
								  'TR_DELETE' => tr('Delete'),
								  'URL_DELETE_ADMIN' => 'user_delete.php?delete_id='
														. $rs->fields['admin_id'] .
														'&amp;delete_username=' .
														$rs->fields['admin_name'],
								  'ADMIN_USERNAME' => tohtml($rs->fields['admin_name'])));

				$tpl->parse('ADMIN_DELETE_LINK', 'admin_delete_link');
			}

			$tpl->assign(array(
							  'ADMIN_USERNAME' => tohtml($rs->fields['admin_name']),
							  'ADMIN_CREATED_ON' => tohtml($admin_created),
							  'ADMIN_CREATED_BY' => ($rs->fields['created_by'] != null)
								  ? tohtml($rs->fields['created_by']) : tr("System"),
							  'URL_EDIT_ADMIN' => 'admin_edit.php?edit_id=' .
												  $rs->fields['admin_id']));

			$tpl->parse('ADMIN_ITEM', '.admin_item');
			$rs->moveNext();
			$i++;
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
				`t1`.`admin_id`, `t1`.`admin_name`, `t1`.`domain_created`,
				IFNULL(t2.`admin_name`, '') AS created_by
		FROM
				`admin` AS `t1`
		LEFT JOIN
				`admin` AS `t2` ON `t1`.`created_by` = t2.`admin_id`
		WHERE
				`t1`.`admin_type` = 'reseller'
		ORDER BY
				`t1`.`admin_name` ASC
	";
	$rs = execute_query($query);

	if ($rs->recordCount() == 0) {
		$tpl->assign(array(
						  'RSL_MESSAGE' => tr('No reseller accounts found.'),
						  'RSL_LIST' => ''));

		$tpl->parse('RSL_MESSAGE', 'rsl_message');
	} else {
		$tpl->assign(array(
						  'TR_RSL_USERNAME' => tr('Username'),
						  'TR_RSL_CREATED_BY' => tr('Created by'),
						  'TR_RSL_ACTIONS' => tr('Actions')));

		while (!$rs->EOF) {
			if ($rs->fields['created_by'] == '') {
				$tpl->assign(array(
								  'TR_DELETE' => tr('Delete'),
								  'RSL_DELETE_LINK' => ''));

				$tpl->parse('RSL_DELETE_SHOW', 'rsl_delete_show');
			} else {
				$tpl->assign(array(
								  'RSL_DELETE_SHOW' => '',
								  'TR_DELETE' => tr('Delete'),
								  'URL_DELETE_RSL' => 'user_delete.php?delete_id=' .
													  $rs->fields['admin_id'] .
													  '&amp;delete_username=' .
													  $rs->fields['admin_name'],
								  'TR_CHANGE_USER_INTERFACE' => tr('Switch to user interface'),
								  'GO_TO_USER_INTERFACE' => tr('Switch'),
								  'URL_CHANGE_INTERFACE' => 'change_user_interface.php?to_id=' .
															$rs->fields['admin_id']));

				$tpl->parse('RSL_DELETE_LINK', 'rsl_delete_link');
			}

			$reseller_created = $rs->fields['domain_created'];

			if ($reseller_created == 0) {
				$reseller_created = tr('N/A');
			} else {
				$date_formt = $cfg->DATE_FORMAT;
				$reseller_created = date($date_formt, $reseller_created);
			}

			$tpl->assign(array(
							  'RSL_USERNAME' => tohtml($rs->fields['admin_name']),
							  'RESELLER_CREATED_ON' => tohtml($reseller_created),
							  'RSL_CREATED_BY' => tohtml($rs->fields['created_by']),
							  'URL_EDIT_RSL' => 'reseller_edit.php?edit_id=' .
												$rs->fields['admin_id']));

			$tpl->parse('RSL_ITEM', '.rsl_item');
			$rs->moveNext();
		}

		$tpl->parse('RSL_LIST', 'rsl_list');
		$tpl->assign('RSL_MESSAGE', '');
	}
}

/**
 * Helper function to generate an user list.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function gen_user_list($tpl){

	 /** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$start_index = 0;
	$rows_per_page = $cfg->DOMAIN_ROWS_PER_PAGE;

    if (isset($_GET['psi']) && $_GET['psi'] == 'last') {
        if (isset($_SESSION['search_page'])) {
            $_GET['psi'] = $_SESSION['search_page'];
        } else {
            unset($_GET['psi']);
        }
    }

	if (isset($_GET['psi'])) {
		$start_index = $_GET['psi'];
	}

	// Search request generated ?
	if (isset($_POST['uaction']) && !empty($_POST['uaction'])) {
		$_SESSION['search_for'] = trim(clean_input($_POST['search_for']));
		$_SESSION['search_common'] = $_POST['search_common'];
		$_SESSION['search_status'] = $_POST['search_status'];
		$start_index = 0;
	} elseif (isset($_SESSION['search_for']) && !isset($_GET['psi'])) {
		// He have not got scroll through patient records
		unset($_SESSION['search_for']);
		unset($_SESSION['search_common']);
		unset($_SESSION['search_status']);
	}

	$search_query = '';
	$count_query = '';

	if (isset($_SESSION['search_for'])) {
		gen_admin_domain_query($search_query, $count_query, $start_index,
							   $rows_per_page, $_SESSION['search_for'],
							   $_SESSION['search_common'], $_SESSION['search_status']);

		gen_admin_domain_search_options($tpl, $_SESSION['search_for'],
										$_SESSION['search_common'],
										$_SESSION['search_status']);

		$rs = exec_query($count_query);
	} else {
		gen_admin_domain_query($search_query, $count_query, $start_index,
							   $rows_per_page, 'n/a', 'n/a', 'n/a');

		gen_admin_domain_search_options($tpl, 'n/a', 'n/a', 'n/a');
		$rs = exec_query($count_query);
	}

	$records_count = $rs->fields['cnt'];
	$rs = execute_query($search_query);

	if ($rs->recordCount() == 0) {
		if (isset($_SESSION['search_for'])) {
			$tpl->assign(array(
							'USR_MESSAGE' => tr('Not found records matching the search criteria.'),
							'USR_LIST' => '',
							'SCROLL_PREV' => '',
							'SCROLL_NEXT' => '',
							'TR_VIEW_DETAILS' => tr('view aliases'),
							'SHOW_DETAILS' => 'show'));

			unset($_SESSION['search_for']);
			unset($_SESSION['search_common']);
			unset($_SESSION['search_status']);
		} else {
			$tpl->assign(array(
							'USR_MESSAGE' => tr('No customer accounts found.'),
							'USR_LIST' => '',
							'SCROLL_PREV' => '',
							'SCROLL_NEXT' => '',
							'TR_VIEW_DETAILS' => tr('view aliases'),
							'SHOW_DETAILS' => 'show'));
		}

		$tpl->parse('USR_MESSAGE', 'usr_message');
	} else {
		$prev_si = $start_index - $rows_per_page;

		if ($start_index == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(array(
							'SCROLL_PREV_GRAY' => '',
							'PREV_PSI' => $prev_si));
		}

		$next_si = $start_index + $rows_per_page;

		if ($next_si + 1 > $records_count) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(array(
							'SCROLL_NEXT_GRAY' => '',
							'NEXT_PSI' => $next_si));
		}

		$tpl->assign(array(
						  'TR_USR_USERNAME' => tr('Username'),
						  'TR_USR_CREATED_BY' => tr('Created by'),
						  'TR_USR_ACTIONS' => tr('Actions'),
						  'TR_USER_STATUS' => tr('Status'),
						  'TR_DETAILS' => tr('Details')));

		while (!$rs->EOF) {
			// user status icon
			$domain_created_id = $rs->fields['domain_created_id'];

			$query = "
				SELECT
					`admin_name`
				FROM
					`admin`
				WHERE
					`admin_id` = ?
				ORDER BY
					`admin_name` ASC
			";
			$rs2 = exec_query($query, $domain_created_id);

			if (!isset($rs2->fields['admin_name'])) {
				$created_by_name = tr('N/A');
			} else {
				$created_by_name = $rs2->fields['admin_name'];
			}

			$tpl->assign(array(
							  'USR_DELETE_SHOW' => '',
							  'DOMAIN_ID' => $rs->fields['domain_id'],
							  'TR_DELETE' => tr('Delete'),
							  'URL_DELETE_USR' => 'user_delete.php?domain_id=' . $rs->fields['domain_id'],
							  'TR_CHANGE_USER_INTERFACE' => tr('Switch to user interface'),
							  'GO_TO_USER_INTERFACE' => tr('Switch'),
							  'URL_CHANGE_INTERFACE' => 'change_user_interface.php?to_id=' . $rs->fields['domain_admin_id'],
							  'USR_USERNAME' => tohtml($rs->fields['domain_name']),
							  'TR_EDIT_DOMAIN' => tr('Edit domain'),
							  'TR_EDIT_USR' => tr('Edit user')));

			$tpl->parse('USR_DELETE_LINK', 'usr_delete_link');

			if ($rs->fields['domain_status'] == $cfg->ITEM_OK_STATUS) {
				$status = 'ok';
				$status_txt = tr('Ok');
				$status_url = 'domain_status_change.php?domain_id=' .
							  $rs->fields['domain_id'];
				$status_bool = true;
			} elseif ($rs->fields['domain_status'] == $cfg->ITEM_DISABLED_STATUS) {
				$status = 'disabled';
				$status_txt = tr('Disabled');
				$status_url = 'domain_status_change.php?domain_id=' .
							  $rs->fields['domain_id'];
				$status_bool = false;
			} elseif ($rs->fields['domain_status'] == $cfg->ITEM_ADD_STATUS
					  || $rs->fields['domain_status'] == $cfg->ITEM_RESTORE_STATUS
					  || $rs->fields['domain_status'] == $cfg->ITEM_CHANGE_STATUS
					  || $rs->fields['domain_status'] == $cfg->ITEM_TOENABLE_STATUS
					  || $rs->fields['domain_status'] == $cfg->ITEM_TODISABLED_STATUS
					  || $rs->fields['domain_status'] == $cfg->ITEM_DELETE_STATUS
			) {
				$status = 'reload';
				$status_txt = tr('Reload');
				$status_url = '#';
				$status_bool = false;
			} else {
				$status = 'error';
				$status_txt = tr('Error');
				$status_url = 'domain_details.php?domain_id=' . $rs->fields['domain_id'];
				$status_bool = false;
			}

			$tpl->assign(array(
							  'STATUS' => $status,
							  'TR_STATUS' => $status_txt,
							  'URL_CHANGE_STATUS' => $status_url));


			$admin_name = decode_idna($rs->fields['domain_name']);
			$domain_created = $rs->fields['domain_created'];

			if ($domain_created == 0) {
				$domain_created = tr('N/A');
			} else {
				$date_formt = $cfg->DATE_FORMAT;
				$domain_created = date($date_formt, $domain_created);
			}

			$domain_expires = $rs->fields['domain_expires'];

			if ($domain_expires == 0) {
				$domain_expires = tr('Not Set');
			} else {
				$date_formt = $cfg->DATE_FORMAT;
				$domain_expires = date($date_formt, $domain_expires);
			}

			if($status_bool == false) { // reload
				$tpl->assign('USR_STATUS_RELOAD_TRUE', '');
				$tpl->assign('USR_USERNAME', tohtml($admin_name));
				$tpl->parse('USR_STATUS_RELOAD_FALSE', 'usr_status_reload_false');
			} else {
				$tpl->assign('USR_STATUS_RELOAD_FALSE', '');
				$tpl->assign('USR_USERNAME', tohtml($admin_name));
				$tpl->parse('USR_STATUS_RELOAD_TRUE', 'usr_status_reload_true');
			}

			$tpl->assign(array(
							  'USER_CREATED_ON' => tohtml($domain_created),
							  'USER_EXPIRES_ON' => $domain_expires,
							  'USR_CREATED_BY' => tohtml($created_by_name),
							  'USR_OPTIONS' => '',
							  'URL_EDIT_USR' => 'admin_edit.php?edit_id=' . $rs->fields['domain_admin_id'],
							  'TR_MESSAGE_CHANGE_STATUS' => tr('Are you sure you want to change the status of %s domain account?', '%s'),
							  'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', '%s')));

			gen_domain_details($tpl, $rs->fields['domain_id']);
			$tpl->parse('USR_ITEM', '.usr_item');
			$rs->moveNext();
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
	$tpl->assign(array(

					'TR_MANAGE_USERS' => tr('Manage users'),
					'TR_ADMINISTRATORS' => tr('Administrators'),
					'TR_RESELLERS' => tr('Resellers'),
					'TR_CUSTOMERS' => tr('Customers'),
					'TR_SEARCH' => tr('Search'),
					'TR_CREATED_ON' => tr('Creation date'),
					'TR_EXPIRES_ON' => tr('Expire date'),
					'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
					'TR_EDIT' => tr('Edit')));

	gen_admin_list($tpl);
	gen_reseller_list($tpl);
	gen_user_list($tpl);
}

/**
 * Helper function to generate domain search form template part.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @param  string $search_for Object to search for
 * @param  $search_common Commone object to search for
 * @param  $search_status Object status to search for
 * @return void
 */
function gen_admin_domain_search_options($tpl, $search_for, $search_common, $search_status)
{
	 /** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	 $domain_selected = $customerid_selected = $lastname_selected =
	 $company_selected = $city_selected = $state_selected = $country_selected =
	 $all_selected = $ok_selected = $suspended_selected = '';

	if ($search_for == 'n/a' && $search_common == 'n/a' && $search_status == 'n/a') {
		// we have no search and let's generate search fields empty
		$domain_selected = $cfg->HTML_SELECTED;
		$all_selected = $cfg->HTML_SELECTED;
	}

	if ($search_common == 'domain_name') {
		$domain_selected = $cfg->HTML_SELECTED;
	} elseif ($search_common == 'customer_id') {
		$customerid_selected = $cfg->HTML_SELECTED;
	} elseif ($search_common == 'lname') {
		$lastname_selected = $cfg->HTML_SELECTED;
	} elseif ($search_common === 'firm') {
		$company_selected = $cfg->HTML_SELECTED;
	} elseif ($search_common == 'city') {
		$city_selected = $cfg->HTML_SELECTED;
	} elseif ($search_common == 'state') {
		$state_selected = $cfg->HTML_SELECTED;
	} elseif ($search_common == 'country') {
		$country_selected = $cfg->HTML_SELECTED;
	}

	if ($search_status == 'all') {
		$all_selected = $cfg->HTML_SELECTED;
	} elseif ($search_status == 'ok') {
		$ok_selected = $cfg->HTML_SELECTED;
	} elseif ($search_status == 'disabled') {
		$suspended_selected = $cfg->HTML_SELECTED;
	}

	if ($search_for == 'n/a' || $search_for == '') {
		$tpl->assign(array('SEARCH_FOR' => ''));
	} else {
		$tpl->assign(array('SEARCH_FOR' => $search_for));
	}

	$tpl->assign(array(
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

					// selected area
					'M_DOMAIN_NAME_SELECTED' => $domain_selected,
					'M_CUSTOMER_ID_SELECTED' => $customerid_selected,
					'M_LAST_NAME_SELECTED' => $lastname_selected,
					'M_COMPANY_SELECTED' => $company_selected,
					'M_CITY_SELECTED' => $city_selected,
					'M_STATE_SELECTED' => $state_selected,
					'M_COUNTRY_SELECTED' => $country_selected,
					'M_ALL_SELECTED' => $all_selected,
					'M_OK_SELECTED' => $ok_selected,
					'M_SUSPENDED_SELECTED' => $suspended_selected,));
}

