<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::getInstance()->getInstance()->get('CLIENT_TEMPLATE_PATH') . '/alias_edit.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

$theme_color = Config::getInstance()->getInstance()->get('USER_INITIAL_THEME');

$tpl->assign(array(
	'TR_EDIT_ALIAS_PAGE_TITLE' => tr('ispCP - Manage Domain Alias/Edit Alias'),
	'THEME_COLOR_PATH' => "../themes/$theme_color",
	'THEME_CHARSET' => tr('encoding'),
	'ISP_LOGO' => get_logo($_SESSION['user_id'])
));

/*
 *
 * static page messages.
 *
 */
$tpl->assign(array(
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
	'TR_PREFIX_HTTP' => 'http://',
	'TR_PREFIX_HTTPS' => 'https://',
	'TR_PREFIX_FTP' => 'ftp://'
));

gen_client_mainmenu($tpl, Config::getInstance()->getInstance()->get('CLIENT_TEMPLATE_PATH') . '/main_menu_manage_domains.tpl');
gen_client_menu($tpl, Config::getInstance()->getInstance()->get('CLIENT_TEMPLATE_PATH') . '/menu_manage_domains.tpl');

gen_logged_from($tpl);

// "Modify" button has been pressed
if (isset($_POST['uaction']) && ($_POST['uaction'] === 'modify')) {
	if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];
	} else if (isset($_SESSION['edit_ID'])) {
		$editid = $_SESSION['edit_ID'];
	} else {
		unset($_SESSION['edit_ID']);

		$_SESSION['aledit'] = '_no_';
		user_goto('domains_manage.php');
	}
	// Save data to db
	if (check_fwd_data($tpl, $editid)) {
		$_SESSION['aledit'] = "_yes_";
		user_goto('domains_manage.php');
	}
} else {
	// Get user id that comes for edit
	if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];
	}

	$_SESSION['edit_ID'] = $editid;
	$tpl->assign('PAGE_MESSAGE', "");
}
gen_editalias_page($tpl, $editid);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::getInstance()->getInstance()->get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();

// Begin function block

/**
 * Show user data
 */
function gen_editalias_page(&$tpl, $edit_id) {
	$sql = Database::getInstance();
	// Get data from sql
	list($domain_id) = get_domain_default_props($sql, $_SESSION['user_id']);
	$res = exec_query($sql, "SELECT * FROM `domain_aliasses` WHERE `alias_id` = ? AND `domain_id` = ?", array($edit_id, $domain_id));

	if ($res->RecordCount() <= 0) {
		$_SESSION['aledit'] = '_no_';
		user_goto('domains_manage.php');
	}
	$data = $res->FetchRow();
	// Get IP data
	$ipres = exec_query($sql, "SELECT * FROM `server_ips` WHERE `ip_id` = ?", array($data['alias_ip_id']));
	$ipdat = $ipres->FetchRow();
	$ip_data = $ipdat['ip_number'] . ' (' . $ipdat['ip_alias'] . ')';

	if (isset($_POST['uaction']) && ($_POST['uaction'] == 'modify')) {
		$url_forward = strtolower(clean_input($_POST['forward']));
	} else {
		$url_forward = decode_idna(preg_replace("(ftp://|https://|http://)", "", $data['url_forward']));

		if ($data["url_forward"] == "no") {
			$check_en = '';
			$check_dis = 'checked="checked"';
			$url_forward = '';
			$tpl->assign(array(
				'READONLY_FORWARD' => ' readonly',
				'DISABLE_FORWARD' => ' disabled="disabled"'
			));
		} else {
			$check_en = 'checked="checked"';
			$check_dis = '';
			$tpl->assign(array(
				'READONLY_FORWARD' => '',
				'DISABLE_FORWARD' => '',
				'HTTP_YES' => (preg_match("/http:\/\//", $data['url_forward'])) ? 'selected="selected"' : '',
				'HTTPS_YES' => (preg_match("/https:\/\//", $data['url_forward'])) ? 'selected="selected"' : '',
				'FTP_YES' => (preg_match("/ftp:\/\//", $data['url_forward'])) ? 'selected="selected"' : ''
			));
		}
		$tpl->assign(array(
			'CHECK_EN' => $check_en,
			'CHECK_DIS' => $check_dis
		));
	}
	// Fill in the fields
	$tpl->assign(array(
		'ALIAS_NAME' => decode_idna($data['alias_name']),
		'DOMAIN_IP' => $ip_data,
		'FORWARD' => $url_forward,
		'MOUNT_POINT' => $data['alias_mount'],
		'ID' => $edit_id
	));
} // End of gen_editalias_page()

/**
 * Check input data
 */
function check_fwd_data(&$tpl, $alias_id) {
	$sql = Database::getInstance();

	$forward_url = strtolower(clean_input($_POST['forward']));
	$status = $_POST['status'];
	// unset errors
	$ed_error = '_off_';
	$admin_login = '';

	if (isset($_POST['status']) && $_POST['status'] == 1) {
		$forward_prefix = clean_input($_POST['forward_prefix']);
		if(substr_count($forward_url, '.') <= 2) {
			$ret = validates_dname($forward_url);
		} else {
			$ret = validates_dname($forward_url, true);
		}
		if(!$ret) {
			$ed_error = tr("Wrong domain part in forward URL!");
		} else {
			$forward_url = encode_idna($forward_prefix.$forward_url);
		}
		
		$check_en = 'checked="checked"';
		$check_dis = '';
		$tpl->assign(array(
			'FORWARD' => $forward_url,
			'HTTP_YES' => ($forward_prefix === 'http://') ? 'selected="selected"' : '',
			'HTTPS_YES' => ($forward_prefix === 'https://') ? 'selected="selected"' : '',
			'FTP_YES' => ($forward_prefix === 'ftp://') ? 'selected="selected"' : '',
			'CHECK_EN' => $check_en,
			'CHECK_DIS' => $check_dis,
		));
	} else {
		$check_en = '';
		$check_dis = 'checked="checked"';
		$forward_url = 'no';
		$tpl->assign(array(
			'READONLY_FORWARD' => ' readonly',
			'DISABLE_FORWARD' => ' disabled="disabled"',
			'CHECK_EN' => $check_en,
			'CHECK_DIS' => $check_dis,
		));
	}

	if ($ed_error === '_off_') {
		$query = "
			UPDATE
				`domain_aliasses`
			SET
				`url_forward` = ?,
				`alias_status` = ?
			WHERE
				`alias_id` = ?
		";
		exec_query($sql, $query, array($forward_url, Config::getInstance()->getInstance()->get('ITEM_CHANGE_STATUS'), $alias_id));

		$query = "
			UPDATE
				`subdomain_alias`
			SET
				`subdomain_alias_status` = ?
			WHERE
				`alias_id` = ?
		";
		exec_query($sql, $query, array(Config::getInstance()->getInstance()->get('ITEM_CHANGE_STATUS'), $alias_id));

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
} // End of check_user_data()
