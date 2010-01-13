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
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/alias_add.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('user_entry', 'page');
$tpl->define_dynamic('ip_entry', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'THEME_COLOR_PATH' => '../themes/' . $theme_color,
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id']),
	)
);

/*
 *
 * static page messages.
 *
 */

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_manage_domains.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_manage_domains.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_CLIENT_ADD_ALIAS_PAGE_TITLE' => tr('ispCP Client : Add Alias'),
		'TR_MANAGE_DOMAIN_ALIAS' => tr('Manage domain alias'),
		'TR_ADD_ALIAS' => tr('Add domain alias'),
		'TR_DOMAIN_NAME' => tr('Domain name'),
		'TR_DOMAIN_ACCOUNT' => tr('User account'),
		'TR_MOUNT_POINT' => tr('Directory mount point'),
		'TR_DOMAIN_IP' => tr('Domain IP'),
		'TR_FORWARD' => tr('Forward to URL'),
		'TR_ADD' => tr('Add alias'),
		'TR_DMN_HELP' => tr("You do not need 'www.' ispCP will add it on its own."),
		'TR_JS_EMPTYDATA' => tr("Empty data or wrong field!"),
		'TR_JS_WDNAME' => tr("Wrong domain name!"),
		'TR_JS_MPOINTERROR' => tr("Please write mount point!")
	)
);

check_domainalias_permissions($sql, $_SESSION['user_id']);

$err_txt = '_off_';
if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_alias') {
	add_domain_alias($sql, $err_txt);
} else {
	// Init fields
	init_empty_data();
	$tpl-> assign("PAGE_MESSAGE", "");
}

/*
 * Begin function declaration lines
 */

function check_domainalias_permissions($sql, $user_id) {

	list($dmn_id,
		$dmn_name,
		$dmn_gid,
		$dmn_uid,
		$dmn_created_id,
		$dmn_created,
		$dmn_expires,
		$dmn_last_modified,
		$dmn_mailacc_limit,
		$dmn_ftpacc_limit,
		$dmn_traff_limit,
		$dmn_sqld_limit,
		$dmn_sqlu_limit,
		$dmn_status,
		$dmn_als_limit,
		$dmn_subd_limit,
		$dmn_ip_id,
		$dmn_disk_limit,
		$dmn_disk_usage,
		$dmn_php,
		$dmn_cgi,
		$allowbackup,
		$dmn_dns
	) = get_domain_default_props($sql, $user_id);

		$als_cnt = get_domain_running_als_cnt($sql, $dmn_id);

		if ($dmn_als_limit != 0 && $als_cnt >= $dmn_als_limit) {
			set_page_message(tr('Domain alias limit reached!'));
			user_goto('domains_manage.php');
		}
}

function init_empty_data() {
	global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point;

	$cr_user_id = $alias_name = $domain_ip = $forward = $mount_point = '';

} // End of init_empty_data()


/**
 * Show data fields
 */
function gen_al_page(&$tpl, $reseller_id) {
	global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point;

	if (isset($_POST['forward'])) {
		$forward = $_POST['forward'];
	} else {
		$forward = 'no';
	}
	$tpl->assign(
		array(
			'DOMAIN'	=> decode_idna($alias_name),
			'MP'		=> decode_idna($mount_point),
			'FORWARD'	=> $forward
		)
	);

} // End of gen_al_page()

function add_domain_alias(&$sql, &$err_al) {
	global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point;
	global $validation_err_msg;

	$cr_user_id = $domain_id = get_user_domain_id($sql, $_SESSION['user_id']);
	$alias_name	= strtolower($_POST['ndomain_name']);
	$mount_point = strtolower($_POST['ndomain_mpoint']);
	$forward = strtolower(clean_input($_POST['forward']));

	$query = "
		SELECT
			`domain_ip_id`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
	";

	$rs = exec_query($sql, $query, array($cr_user_id));
	$domain_ip = $rs->fields['domain_ip_id'];

	// Should be perfomed after domain names syntax validation now
	//$alias_name = encode_idna($alias_name);

	$mount_point = array_encode_idna($mount_point, true);

	//$mount_point = "/".$mount_point;

	// First check if input string is a valid domain names
	if (!validates_dname($alias_name)) {
		$err_al = $validation_err_msg;
		return;
	}

	// Should be perfomed after domain names syntax validation now
	$alias_name = encode_idna($alias_name);

	if (ispcp_domain_exists($alias_name, 0)) {
	 $err_al = tr('Domain with that name already exists on the system!');
	} else if (!validates_mpoint($mount_point) && $mount_point != '/') {
		$err_al = tr("Incorrect mount point syntax");
	} else if ($alias_name == Config::get('BASE_SERVER_VHOST')) {
		$err_al = tr('Master domain cannot be used!');
	} else if ($forward != 'no') {
		if (!chk_forward_url($forward)) {
			$err_al = tr("Incorrect forward syntax");
		}
		/** @todo test and remove if no bugs encounter
		if (!preg_match("/\/$/", $forward) && !preg_match("/\?/", $forward)) {
			$forward .= "/";
		}*/
	} else {
		// now let's fix the mountpoint
		$mount_point = array_decode_idna($mount_point, true);

		$query = "SELECT `domain_id` FROM `domain_aliasses` WHERE `alias_name` = ?";
		$res = exec_query($sql, $query, array($alias_name));
		$query = "SELECT `domain_id` FROM `domain` WHERE `domain_name` = ?";
		$res2 = exec_query($sql, $query, array($alias_name));
		if ($res->RowCount() > 0 || $res2->RowCount() > 0) {
			// we already have domain with this name
			$err_al = tr("Domain with this name already exist");
		}

		$query = "SELECT COUNT(`subdomain_id`) AS cnt FROM `subdomain` WHERE `domain_id` = ? AND `subdomain_mount` = ?";
		$subdomres = exec_query($sql, $query, array($cr_user_id, $mount_point));
		$subdomdata = $subdomres->FetchRow();
		$query = "SELECT COUNT(`subdomain_alias_id`) AS alscnt FROM `subdomain_alias` WHERE `alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?) AND `subdomain_alias_mount` = ?";
		$alssubdomres = exec_query($sql, $query, array($cr_user_id, $mount_point));
		$alssubdomdata = $alssubdomres->FetchRow();
		if ($subdomdata['cnt'] > 0 || $alssubdomdata['alscnt'] > 0) {
			$err_al = tr("There is a subdomain with the same mount point!");
		}
	}

	if ('_off_' !== $err_al) {
		return;
	}

	// Begin add new alias domain
	$alias_name = htmlspecialchars($alias_name, ENT_QUOTES, "UTF-8");

	$status = Config::get('ITEM_ORDERED_STATUS');

	$query = "INSERT INTO `domain_aliasses` (`domain_id`, `alias_name`, `alias_mount`, `alias_status`, `alias_ip_id`, `url_forward`) VALUES (?, ?, ?, ?, ?, ?)";
	exec_query($sql, $query, array($cr_user_id, $alias_name, $mount_point, $status, $domain_ip, $forward));

	$als_id = $sql->Insert_ID();

	update_reseller_c_props(get_reseller_id($cr_user_id));

	$admin_login = $_SESSION['user_logged'];

	if ($status == Config::get('ITEM_ORDERED_STATUS')) {
		// notify the reseller:
		send_alias_order_email($alias_name);

		write_log("$admin_login: add domain alias for activation: $alias_name.");
		set_page_message(tr('Alias scheduled for activation!'));
	} else {
		send_request();
		write_log("$admin_login: domain alias scheduled for addition: $alias_name.");
		set_page_message(tr('Alias scheduled for addition!'));
	}

	user_goto('domains_manage.php');
} // End of add_domain_alias();


function gen_page_msg(&$tpl, $erro_txt) {

	if ($erro_txt != '_off_') {
		$tpl->assign('MESSAGE', $erro_txt);
		$tpl->parse('PAGE_MESSAGE', 'page_message');
	} else {
		$tpl->assign('PAGE_MESSAGE', '');
	}

} // End of gen_page_msg()

gen_al_page($tpl, $_SESSION['user_id']);
gen_page_msg($tpl, $err_txt);

//gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
