<?php
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/

require '../include/ispcp-lib.php';

check_login(__FILE__);


$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/users.tpl');

$tpl -> define_dynamic('users_list', 'page');

$tpl -> define_dynamic('user_entry', 'users_list');

$tpl -> define_dynamic('user_details', 'users_list');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$tpl -> define_dynamic('scroll_prev_gray', 'page');

$tpl -> define_dynamic('scroll_prev', 'page');

$tpl -> define_dynamic('scroll_next_gray', 'page');

$tpl -> define_dynamic('scroll_next', 'page');

$tpl -> define_dynamic('edit_option', 'page');



global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE' => tr('ispCP - Users'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                     )
              );

if (isset($_SESSION['dmn_name']))

    unset($_SESSION['dmn_name']);

if (isset($_SESSION['ch_hpprops']))

    unset($_SESSION['ch_hpprops']);

if (isset($_SESSION['local_data']))

    unset($_SESSION['local_data']);

if (isset($_SESSION['dmn_ip']))

    unset($_SESSION['dmn_ip']);

if (isset($_SESSION['dmn_id']))

    unset($_SESSION['dmn_id']);

if (isset($GLOBALS['dmn_name']))

    unset($GLOBALS['dmn_name']);

if (isset($GLOBALS['ch_hpprops']))

    unset($GLOBALS['ch_hpprops']);

if (isset($GLOBALS['local_data']))

    unset($GLOBALS['local_data']);

if (isset($GLOBALS['rau3_added']))

    unset($GLOBALS['rau3_added']);

if (isset($GLOBALS['rau3_added']))

    unset($GLOBALS['rau3_added']);

if (isset($GLOBALS['dmn_ip']))

    unset($GLOBALS['dmn_ip']);

if (isset($GLOBALS['dmn_id']))

    unset($GLOBALS['dmn_id']);


/*
 *
 * static page messages.
 *
 */

gen_reseller_mainmenu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/main_menu_manage_users.tpl');
gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_manage_users.tpl');

gen_logged_from($tpl);

$crnt_month = date("m");
$crnt_year = date("Y");

$tpl -> assign(
                array(
							'TR_MANAGE_USERS' => tr('Manage users'),
							'TR_USERS' => tr('Users'),
						    'TR_USER_STATUS' => tr('Status'),
							'TR_DETAILS' => tr('Details'),
							'TR_SEARCH' => tr('Search'),
							'TR_USERNAME' => tr('Username'),
							'TR_ACTION' => tr('Action'),
							'TR_CREATION_DATE' => tr('Creation date'),
							'TR_CHANGE_USER_INTERFACE' => tr('Change user interface'),
							'TR_BACK' => tr('Back'),
                            'TR_TITLE_BACK' => tr('Return to previous menu'),
                            'TR_TABLE_NAME' => tr('Users list'),
							'TR_MESSAGE_CHANGE_STATUS' => tr('Are you sure you want to change the status of domain account?', true),
							'TR_MESSAGE_DELETE_ACCOUNT' => tr('Are you sure you want to delete this account?', true),
							'TR_STAT' => tr('Stats'),
							'VL_MONTH' => $crnt_month,
                            'VL_YEAR' => $crnt_year,
							'TR_EDIT' => tr('Edit properties'),

                     )
              );

if (isset($cfg['HOSTING_PLANS_LEVEL']) && $cfg['HOSTING_PLANS_LEVEL'] === 'admin') {
	$tpl -> assign('EDIT_OPTION', '');
}

generate_users_list($tpl, $_SESSION['user_id']);

check_externel_events($tpl);

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();

//
// Begin function block
//






function generate_users_list ( &$tpl, $admin_id ) {

    global $sql, $externel_event, $cfg;

	$start_index = 0;

	$rows_per_page = $cfg['DOMAIN_ROWS_PER_PAGE'];


		if (isset($_POST['details']) AND !empty($_POST['details'])){

			$_SESSION['details'] = $_POST['details'];

		} else {
			if (!isset($_SESSION['details']))
			{
				$_SESSION['details'] = "hide";
			}
		}



	if (isset($_GET['psi'])) $start_index = $_GET['psi'];

	//
	//  Search requet generated ?!
	//

	if (isset($_POST['uaction']) && !empty($_POST['uaction'])) {

			$_SESSION['search_for'] = trim(clean_input($_POST['search_for']));

			$_SESSION['search_common'] = $_POST['search_common'];

			$_SESSION['search_status'] = $_POST['search_status'];

			$start_index = 0;

	} else {

		if (isset($_SESSION['search_for']) && !isset($_GET['psi'])) {

			//
			// He have not got scroll through patient records.
			//

			unset($_SESSION['search_for']);

			unset($_SESSION['search_common']);

			unset($_SESSION['search_status']);

		}

	}




	$search_query = '';
	$count_query = '';

	if (isset($_SESSION['search_for'])) {

		gen_manage_domain_query(
									$search_query,
									$count_query,
									$admin_id,
									$start_index,
									$rows_per_page,
									$_SESSION['search_for'],
									$_SESSION['search_common'],
									$_SESSION['search_status']
								 );

		gen_manage_domain_search_options($tpl, $_SESSION['search_for'], $_SESSION['search_common'], $_SESSION['search_status']);

	} else {

		gen_manage_domain_query(
									$search_query,
									$count_query,
									$admin_id,
									$start_index,
									$rows_per_page,
									'n/a',
									'n/a',
									'n/a'
								 );

		gen_manage_domain_search_options($tpl, 'n/a', 'n/a', 'n/a');

	}

	$rs = execute_query($sql, $count_query);

	$records_count = $rs -> fields['cnt'];

	$rs = execute_query($sql, $search_query);

	if ($records_count == 0) {

		if (isset($_SESSION['search_for'])) {

			$tpl -> assign(
							array(
									'USERS_LIST' => '',
									'SCROLL_PREV' => '',
									'SCROLL_NEXT' => '',
									'TR_VIEW_DETAILS' => tr('view aliases'),
									'SHOW_DETAILS' => "show"
								 )
						  );

			set_page_message(tr('Not found user records matching the search criteria!'));

			unset($_SESSION['search_for']);

			unset($_SESSION['search_common']);

			unset($_SESSION['search_status']);

		} else {

			$tpl -> assign(
							array(
									'USERS_LIST' => '',
									'SCROLL_PREV' => '',
									'SCROLL_NEXT' => '',
									'TR_VIEW_DETAILS' => tr('view aliases'),
									'SHOW_DETAILS' => "show",
								 )
						  );

			set_page_message(tr('You have no users.'));

		}

	} else {

		$prev_si = $start_index - $rows_per_page;

		if ($start_index == 0) {

				$tpl -> assign('SCROLL_PREV', '');

		} else {

				$tpl -> assign(
								array(
										'SCROLL_PREV_GRAY' => '',
										'PREV_PSI' => $prev_si
									 )
							  );

		}

		$next_si = $start_index + $rows_per_page;

		if ($next_si + 1 > $records_count) {

				$tpl -> assign('SCROLL_NEXT', '');

		} else {

				$tpl -> assign(
								array(
										'SCROLL_NEXT_GRAY' => '',
										'NEXT_PSI' => $next_si
									 )
							  );

		}
		$i = 1;


		while (!$rs -> EOF) {

			if ($rs -> fields['domain_status'] == $cfg['ITEM_OK_STATUS']){

				$status_icon = "ok.png";

			} else if ($rs -> fields['domain_status'] == $cfg['ITEM_DISABLED_STATUS']) {

				$status_icon = "disabled.png";

			} else if ($rs -> fields['domain_status'] == $cfg['ITEM_ADD_STATUS'] ||
					   $rs -> fields['domain_status'] == $cfg['ITEM_CHANGE_STATUS'] ||
					   $rs -> fields['domain_status'] == $cfg['ITEM_TOENABLE_STATUS'] ||
					   $rs -> fields['domain_status'] == $cfg['ITEM_RESTORE_STATUS'] ||
					   $rs -> fields['domain_status'] == $cfg['ITEM_TODISABLED_STATUS'] ||
					   $rs -> fields['domain_status'] == $cfg['ITEM_DELETE_STATUS']) {

				$status_icon = "reload.png";


			} else {

				$status_icon = "error.png";

			}
			$status_url = $rs -> fields['domain_id'];



				$tpl -> assign(
					array(
						'STATUS_ICON' => $status_icon,
						'URL_CHANGE_STATUS' => $status_url,
						)
					);

		$admin_name = decode_idna($rs -> fields['domain_name']);

			if ($i % 2 == 0) {
				$tpl -> assign(
							array(
								'CLASS_TYPE_ROW' => 'content',
							)
						);
			}else {
				$tpl -> assign(
							array(
								'CLASS_TYPE_ROW' => 'content2',
							)
						);
			}

			$dom_created = $rs -> fields['domain_created'];

			if ($dom_created == 0) {
				$dom_created = tr('N/A');
			} else {
				$date_formt = $cfg['DATE_FORMAT'];
				$dom_created = date($date_formt, $dom_created);
			}



		    $tpl -> assign(
                            array(
                                    'CREATION_DATE' => $dom_created,
									'DOMAIN_ID' => $rs -> fields['domain_id'],
                                    'NAME' => $admin_name,
                                    'ACTION' => tr('Delete'),
                                    'USER_ID' => $rs -> fields['domain_admin_id'],
									'CHANGE_INTERFACE' => tr('Change'),
                                 )
                          );

			gen_domain_details($tpl, $sql, $rs -> fields['domain_id']);
			$tpl -> parse('USER_ENTRY', '.user_entry');
			$i ++;
			$rs -> MoveNext();
        }

		$tpl -> parse('USER_LIST', 'users_list');

	}
}

function check_externel_events (&$tpl) {

    global $rau3_added, $externel_event, $edit, $es_sbmt, $user_has_domain, $user_deleted;

    if (isset($_SESSION["rau3_added"])) {

        if ($_SESSION["rau3_added"] === '_yes_') {

            set_page_message(tr('User added!'));

            $externel_event = '_on_';
			unset($_SESSION["rau3_added"]);
		}
    } else if (isset($_SESSION["edit"])) {

        if ('_yes_'=== $_SESSION["edit"]) {

            set_page_message(tr('User data updated!'));

        }else{
			set_page_message(tr('User data not updated!'));
		}
		unset($_SESSION["edit"]);

    } else if (isset($_SESSION["user_has_domain"])) {

        if ($_SESSION["user_has_domain"] == '_yes_') {

            set_page_message(tr('This user has domain record !<br>First remove the domain from the system!'));
		}

		unset($_SESSION["user_has_domain"]);

    } else if (isset($_SESSION['user_deleted'])) {

        if ($_SESSION['user_deleted'] == '_yes_') {

            set_page_message(tr('User terminated!'));

        }else{
			set_page_message(tr('User not terminated!'));
		}

		unset($_SESSION['user_deleted']);

    }

}

?>