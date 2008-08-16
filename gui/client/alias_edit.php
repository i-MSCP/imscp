<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/alias_edit.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
		array(
			'TR_EDIT_ALIAS_PAGE_TITLE' => tr('ispCP - Manage Domain Alias/Edit Alias'),
			'THEME_COLOR_PATH' => "../themes/$theme_color",
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => get_logo($_SESSION['user_id'])
		)
	);

/*
 *
 * static page messages.
 *
 */
$tpl->assign(
		array(
			'TR_MANAGE_DOMAIN_ALIAS' => tr('Manage domain alias'),
			'TR_EDIT_ALIAS' => tr('Edit domain alias'),
			'TR_ALIAS_NAME' => tr('Alias name'),
			'TR_DOMAIN_IP' => tr('Domain IP'),
			'TR_FORWARD' => tr('Forward to URL'),
			'TR_MOUNT_POINT' => tr('Mount Point'),
			'TR_MODIFY' => tr('Modify'),
			'TR_CANCEL' => tr('Cancel'),
			'TR_ENABLE_FWD' => tr("Enable Forward"),
			'TR_ENABLE' => tr("Enable"),
			'TR_DISABLE' => tr("Disable"),
			'TR_FWD_HELP' => tr("A Forward URL has to start with 'http://'")
		)
	);

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_manage_domains.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_manage_domains.tpl');

gen_logged_from($tpl);

// "Modify" button has ben pressed
if (isset($_POST['uaction']) && ($_POST['uaction'] === 'modify')) {
	if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];
	} else if (isset($_SESSION['edit_ID'])) {
		$editid = $_SESSION['edit_ID'];
	} else {
		unset($_SESSION['edit_ID']);

		$_SESSION['aledit'] = '_no_';
		header('Location: domains_manage.php');
		die();
	}
	// Save data to db
	if (check_fwd_data($tpl, $editid)) {
		$_SESSION['aledit'] = "_yes_";
		header("Location: domains_manage.php");
		die();
	}
} else {
	// Get user id that come for edit
	if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];
	}

	$_SESSION['edit_ID'] = $editid;
	$tpl->assign('PAGE_MESSAGE', "");
}
gen_editalias_page($tpl, $editid);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) dump_gui_debug();

unset_messages();

// Begin function block

// Show user data
function gen_editalias_page(&$tpl, $edit_id) {
	$sql = Database::getInstance();
	// Get data from sql
	list($domain_id) = get_domain_default_props($sql, $_SESSION['user_id']);
	$res = exec_query($sql, "select * from domain_aliasses where alias_id = ? and domain_id = ?", array($edit_id, $domain_id));

	if ($res->RecordCount() <= 0) {
		$_SESSION['aledit'] = '_no_';
		header('Location: domains_manage.php');
		die();
	}
	$data = $res->FetchRow();
	// Get ip-data
	$ipres = exec_query($sql, "select * from server_ips where ip_id=?", array($data['alias_ip_id']));
	$ipdat = $ipres->FetchRow();
	$ip_data = $ipdat['ip_number'] . ' (' . $ipdat['ip_alias'] . ')';

	if (isset($_POST['uaction']) && ($_POST['uaction'] == 'modify'))
		$url_forward = decode_idna($_POST['forward']);
	else
		$url_forward = decode_idna($data['url_forward']);

	if ($data["url_forward"] == "no") {
		$check_en = "";
		$check_dis = "checked=\"checked\"";
		$url_forward = "";
	} else {
		$check_en = "checked=\"checked\"";
		$check_dis = "";
	}
	// Fill in the fileds
	$tpl->assign(
			array(
				'ALIAS_NAME' => decode_idna($data['alias_name']),
				'DOMAIN_IP' => $ip_data,
				'FORWARD' => $url_forward,
				'MOUNT_POINT' => $data['alias_mount'],
				'CHECK_EN' => $check_en,
				'CHECK_DIS' => $check_dis,
				'ID' => $edit_id
			)
		);
} // End of gen_editalias_page()

// Check input data
function check_fwd_data(&$tpl, $alias_id) {
	$sql = Database::getInstance();

	$forward_url = encode_idna($_POST['forward']);
	$status = $_POST['status'];
	// unset errors
	$ed_error = '_off_';
	$admin_login = '';

	if ($forward_url != 'no') {
		if (!chk_forward_url($forward_url)) {
			$ed_error = tr("Incorrect forward syntax");
		}
		if (!preg_match("/\/$/", $forward_url)) {
	    	$forward_url .= "/";
	    }
	}

	if ($ed_error === '_off_') {
		if ($_POST['status'] == 0) {
			$forward_url = "no";
		}

		$query = <<<SQL
			UPDATE
				domain_aliasses
			SET
				url_forward = ?,
				alias_status = ?
			WHERE
				alias_id = ?
SQL;

		exec_query($sql, $query, array($forward_url, Config::get('ITEM_CHANGE_STATUS'), $alias_id));
		check_for_lock_file();
		send_request();

		$admin_login = $_SESSION['user_logged'];
		write_log("$admin_login: change domain alias forward: " . $rs->fields['t1.alias_name']);
		unset($_SESSION['edit_ID']);
		$tpl->assign('MESSAGE', "");
		return true;
	} else {
		$tpl->assign('MESSAGE', $ed_error);
		$tpl->parse('PAGE_MESSAGE', 'page_message');
		return false;
	}
} //End of check_user_data()

?>