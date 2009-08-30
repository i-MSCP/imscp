<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
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

/**
 * @todo use db prepared statements
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/puser_edit.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('usr_msg', 'page');
$tpl->define_dynamic('grp_msg', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('pusres', 'page');
$tpl->define_dynamic('pgroups', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_WEBTOOLS_PAGE_TITLE'	=> tr('ispCP - Client/Webtools'),
		'THEME_COLOR_PATH'				=> "../themes/$theme_color",
		'THEME_CHARSET'					=> tr('encoding'),
		'ISP_LOGO'						=> get_logo($_SESSION['user_id'])
	)
);

function pedit_user(&$tpl, &$sql, &$dmn_id, &$uuser_id) {
	if (isset($_POST['uaction']) && $_POST['uaction'] == 'modify_user') {
		// we have to add the user
		if (isset($_POST['pass']) && isset($_POST['pass_rep'])) {
			if (!chk_password($_POST['pass'])) {
				if (Config::get('PASSWD_STRONG')) {
					set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), Config::get('PASSWD_CHARS')));
				} else {
					set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), Config::get('PASSWD_CHARS')));
				}
				return;
			}
			if ($_POST['pass'] !== $_POST['pass_rep']) {
				set_page_message(tr('Passwords do not match!'));
				return;
			}

			$nadmin_password = crypt_user_pass_with_salt($_POST['pass']);

			$change_status = Config::get('ITEM_CHANGE_STATUS');

			$query = "
				UPDATE
					`htaccess_users`
				SET
					`upass` = ?,
					`status` = ?
				WHERE
					`dmn_id` = ?
				AND
					`id` = ?
			";
			$rs = exec_query($sql, $query, array($nadmin_password, $change_status, $dmn_id, $uuser_id,));

			send_request();

			$query = "
				SELECT
					`uname`
				FROM
					`htaccess_users`
				WHERE
					`dmn_id` = ?
				AND
					`id` = ?
			";
			$rs = exec_query($sql, $query, array($dmn_id, $uuser_id));
			$uname = $rs->fields['uname'];

			$admin_login = $_SESSION['user_logged'];
			write_log("$admin_login: modify user ID (protected areas): $uname");
			user_goto('protected_user_manage.php');
		}
	} else {
		return;
	}
}

function check_get(&$get_input) {
	if (!is_numeric($get_input)) {
		return 0;
	} else {
		return 1;
	}
}

/*
 *
 * static page messages.
 *
 */

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_webtools.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

if (isset($_GET['uname'])
	&& $_GET['uname'] !== ''
	&& is_numeric($_GET['uname'])) {
	$uuser_id = $_GET['uname'];

/**
 * @todo use DB prepared statements
 */
	$query = "
		SELECT
			`uname`
		FROM
			`htaccess_users`
		WHERE
			`dmn_id` = '$dmn_id'
		AND
			`id` = '$uuser_id'
	";

	$rs = execute_query($sql, $query);

	if ($rs->RecordCount() == 0) {
		user_goto('protected_user_manage.php');
	} else {
		$tpl->assign(
			array(
				'UNAME'	=> $rs->fields['uname'],
				'UID'	=> $uuser_id,
			)
		);
	}
} else if (isset($_POST['nadmin_name'])
	&& !empty($_POST['nadmin_name'])
	&& is_numeric($_POST['nadmin_name'])) {
	$uuser_id = clean_input($_POST['nadmin_name']);

/**
 * @todo use DB prepared statements
 */
	$query = "
		SELECT
			`uname`
		FROM
			`htaccess_users`
		WHERE
			`dmn_id` = '$dmn_id'
		AND
			`id` = '$uuser_id'
	";

	$rs = execute_query($sql, $query);

	if ($rs->RecordCount() == 0) {
		user_goto('protected_user_manage.php');
	} else {
		$tpl->assign(
			array(
				'UNAME'	=> $rs->fields['uname'],
				'UID'	=> $uuser_id,
			)
		);
		pedit_user($tpl, $sql, $dmn_id, $uuser_id);
	}
} else {
	user_goto('protected_user_manage.php');
}

$tpl->assign(
	array(
		'TR_HTACCESS'			=> tr('Protected areas'),
		'TR_ACTION'				=> tr('Action'),
		'TR_UPDATE_USER'		=> tr('Update user'),
		'TR_USERS'				=> tr('User'),
		'TR_USERNAME'			=> tr('Username'),
		'TR_ADD_USER'			=> tr('Add user'),
		'TR_GROUPNAME'			=> tr('Group name'),
		'TR_GROUP_MEMBERS'		=> tr('Group members'),
		'TR_ADD_GROUP'			=> tr('Add group'),
		'TR_EDIT'				=> tr('Edit'),
		'TR_GROUP'				=> tr('Group'),
		'TR_DELETE'				=> tr('Delete'),
		'TR_UPDATE'				=> tr('Modify'),
		'TR_PASSWORD'			=> tr('Password'),
		'TR_PASSWORD_REPEAT'	=> tr('Repeat password'),
		'TR_CANCEL'				=> tr('Cancel'),
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');

$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
