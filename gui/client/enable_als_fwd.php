<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware							|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------



include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/enable_als_fwd.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];


$tpl -> assign(
                array(
                        'TR_EDIT_ALIAS_PAGE_TITLE' => tr('VHCS - Manage Domain Alias/Edit Alias'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                     )
              );

/*
 *
 * static page messages.
 *
 */
	$tpl -> assign(
					array(
							'TR_MANAGE_DOMAIN_ALIAS' => tr('Manage domain alias'),
							'TR_EDIT_ALIAS' => tr('Edit domain alias'),
							'TR_ALIAS_NAME' => tr('Alias name'),
							'TR_DOMAIN_IP' => tr('Domain IP'),
							'TR_FORWARD' => tr('Forward to URL'),
							'TR_MODIFY' => tr('Modify')
						)
				);

gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_manage_domains.tpl');

gen_logged_from($tpl);


if (isset($_POST['uaction']) && ('modify' === $_POST['uaction'])) {
// Process data

	if(isset($_SESSION['edit_ID'])) {
		$editid = $_SESSION['edit_ID'];

	} else if (isset($_GET['id'])) {
		$editid = $_GET['id'];

	} else {
		unset($_SESSION['edit_ID']);

		$_SESSION['aledit'] = '_no_';
		//Header('Location: domain_alias.php');
		//die();
	}


	if(check_user_data($tpl, $editid))
	{// Save data to db

		$_SESSION['aledit'] = "_yes_";
		set_page_message(tr('Alias scheduled for modification!'));
		Header("Location: manage_domains.php");
		die();

	}


} else {
	// Get user id that come for edit
	if(isset($_GET['id'])){
		$editid = $_GET['id'];
	}

	$_SESSION['edit_ID'] = $editid;
	$tpl -> assign('MESSAGE', "");
}
gen_editalias_page($tpl, $editid);

check_permissions($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();

//
// Begin function block
//



// Show user data
function gen_editalias_page(&$tpl, $edit_id)
{
	global $sql;


	//Get data from sql
	list($domain_id) = get_domain_default_props($sql, $_SESSION['user_id']);
	$res = exec_query($sql, "select * from domain_aliasses where alias_id = ? and domain_id = ?", array($edit_id, $domain_id));

	if ($res->RecordCount() <= 0 ) {
		$_SESSION['aledit'] = '_no_';
    	header( 'Location: manage_domains.php' );
    	die();
	}
	$data = $res->FetchRow();
	$alias_ip_id = $data['alias_ip_id'];

	// Get ip -data
	$ipres = exec_query($sql, "select * from server_ips where ip_id=?", array($alias_ip_id));
	$ipdat = $ipres->FetchRow();
	$ip_data =  $ipdat['ip_number'].' ('.$ipdat['ip_alias'].')';

	if(isset($_POST['uaction']) && ($_POST['uaction'] == 'modify'))
		$url_forward = clean_input($_POST['forward']);
	else
		$url_forward = $data['url_forward'];

	// Fill in the fileds
	$tpl -> assign(
                array(
                       	'ALIAS_NAME' => $data['alias_name'],
						'DOMAIN_IP' => $ip_data,
						'FORWARD' => $url_forward == 'no' ? '' : $url_forward,
						'ID' => $edit_id
					)
			);

}// End of gen_editalias_page()




//Check input data
function check_user_data ( &$tpl, $alias_id) {

	global $sql,$cfg;

	$forward_url = get_punny($_POST['forward']);

    $ed_error = '_off_';
	$admin_login = '';
	if ($forward_url != 'no') {
		if (chk_url($forward_url) > 0 ) {
			$ed_error = tr("Incorrect forward syntax");
		}
	}

	if('_off_' === $ed_error){

		exec_query($sql,
               "update domain_aliasses set url_forward=?, alias_status='" . $cfg['ITEM_CHANGE_STATUS'] . "' where alias_id=?",
               array($forward_url, $alias_id));
		send_request();
		$admin_login = $_SESSION['user_logged'];
		write_log("$admin_login: change domain alias forward: ".$data['alias_name']);

		unset($_SESSION['edit_ID']);
		return true;

    } else {

        $tpl -> assign('MESSAGE', $ed_error);
		$tpl -> parse('PAGE_MESSAGE', 'page_message');
	    return false;

    }

}//End of check_user_data()

?>
