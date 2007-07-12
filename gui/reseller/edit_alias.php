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

$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/edit_alias.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];


$tpl -> assign(
                array(
                        'TR_EDIT_ALIAS_PAGE_TITLE' => tr('ISPCP - Manage Domain Alias/Edit Alias'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
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
							'TR_MODIFY' => tr('Modify'),
							'TR_CANCEL' => tr('Cancel'),
						)
				);

if (isset($_GET['change_id'])) {
	check_for_disable($_GET["change_id"]);
}

gen_reseller_mainmenu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/main_menu_manage_users.tpl');
gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_manage_users.tpl');

gen_logged_from($tpl);


if (isset($_POST['uaction']) && ('modify' === $_POST['uaction'])) {
// Process data

	if(isset($_SESSION['edit_ID'])) {
		$editid = $_SESSION['edit_ID'];

	} else if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];

	} else {
		unset($_SESSION['edit_ID']);

		$_SESSION['aledit'] = '_no_';
		//Header('Location: domain_alias.php');
		//die();
	}


    if (check_user_data($tpl, $editid)) { // Save data to db

		$_SESSION['aledit'] = "_yes_";
		Header("Location: domain_alias.php");
		die();

	}


} else {
	// Get user id that come for edit
	if(isset($_GET['edit_id'])){
		$editid = $_GET['edit_id'];
	}

	$_SESSION['edit_ID'] = $editid;
	$tpl -> assign('MESSAGE', "");
}
gen_editalias_page($tpl, $editid);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();

//
// Begin function block
//



// Show user data
function gen_editalias_page(&$tpl, $edit_id)
{
	global $sql;

	$reseller_id = $_SESSION['user_id'];

$query = <<<SQL_QUERY
	select
      t1.domain_id,
	  t1.alias_id,
	  t1.alias_name,
	  t2.domain_id,
	  t2.domain_created_id
	from
      domain_aliasses as t1,
      domain as t2
	where
			t1.alias_id = ?
		and
			t1.domain_id = t2.domain_id
		and
			t2.domain_created_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($edit_id, $reseller_id));

		if ($rs -> RecordCount() == 0) {

			set_page_message(tr('User does not exist or you do not have permission to access this interface!'));

			header('Location: domain_alias.php');
			die();
		}



	//Get data from sql
	$res = exec_query($sql, "select * from domain_aliasses where alias_id=?", array($edit_id));

	if ($res->RecordCount() <= 0 ) {
		$_SESSION['aledit'] = '_no_';
    	header( 'Location: domain_alias.php' );
    	die();
	}
	$data = $res->FetchRow();

	// Get ip -data
	$ipres = exec_query($sql, "select * from server_ips where ip_id=?", array($data['alias_ip_id']));
	$ipdat = $ipres->FetchRow();
	$ip_data =  $ipdat['ip_number'].' ('.$ipdat['ip_alias'].')';

	if(isset($_POST['uaction']) && ($_POST['uaction'] == 'modify'))
		$url_forward = get_punny($_POST['forward']);
	else
		$url_forward = $data['url_forward'];

	// Fill in the fileds
	$tpl -> assign(
                array(
                       	'ALIAS_NAME' => $data['alias_name'],
						'DOMAIN_IP' => $ip_data,
						'FORWARD' => $url_forward,
						'ID' => $edit_id
					)
			);

}// End of gen_editalias_page()




//Check input data
function check_user_data (&$tpl, $alias_id) {

	global $sql,$cfg;

	$forward_url = $_POST['forward'];

    $ed_error = '_off_';
	$admin_login = '';
	if ($forward_url != 'no') {
		if (!chk_url($forward_url)) {
			$ed_error = tr("Incorrect forward syntax");
		}
	}

	if('_off_' === $ed_error){

		exec_query($sql,
               "update domain_aliasses set url_forward=?,alias_status='".$cfg['ITEM_CHANGE_STATUS']."' where alias_id=?",
               array($forward_url, $alias_id));
		check_for_lock_file();
		send_request();

		$admin_login = $_SESSION['user_logged'];
		write_log("$admin_login: change domain alias forward: ".$rs->fields['t1.alias_name']);
		unset($_SESSION['edit_ID']);
		return true;

    } else {

        $tpl -> assign('MESSAGE', $ed_error);
		$tpl -> parse('PAGE_MESSAGE', 'page_message');
	    return false;

    }

}//End of check_user_data()

function check_for_disable($alias_id) {
        global $sql;
        //Get data from sql
        $res = exec_query($sql, "select * from domain_aliasses where alias_id=?", array($alias_id));

        if ($res->RecordCount() <= 0 ) {
            $_SESSION['aledit'] = '_no_';
            header( 'Location: domain_alias.php' );
            die();
        }
      $data = $res->FetchRow();
      if ($data['url_forward'] != 'no') {

          check_for_lock_file();


          // remove the forwarding
          exec_query($sql,
                     "UPDATE domain_aliasses SET url_forward='no',alias_status='change' WHERE alias_id=?",
                     array($alias_id));
        // print "UPDATEE domain_aliasses SET url_forward='no',alias_status='change' WHERE alias_id='$alias_id'";
          $_SESSION['aledit'] = "_yes_";

          // send request to the daemon
          send_request();

          header( 'Location: domain_alias.php' );
          die();
      }

      return;

}

?>
