<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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

$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/password_change.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_CHANGE_PASSWORD_PAGE_TITLE' => tr('ispCP - Client/Change Password'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_pass') {
	if (empty($_POST['pass']) || empty($_POST['pass_rep']) || empty($_POST['curr_pass'])) {
		set_page_message(tr('Please fill up all data fields!'));
	} else if ($_POST['pass'] !== $_POST['pass_rep']) {
		set_page_message(tr('Passwords do not match!'));
	} else if (!chk_password($_POST['pass'])) {
		if (Config::get('PASSWD_STRONG')) {
			set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), Config::get('PASSWD_CHARS')));
		} else {
			set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), Config::get('PASSWD_CHARS')));
		}
	} else if (!check_udata($_SESSION['user_id'], $_POST['curr_pass'])) {
		set_page_message(tr('The current password is wrong!'));
	} else {
		$upass = crypt_user_pass($_POST['pass']);

		$_SESSION['user_pass'] = $upass;

		$user_id = $_SESSION['user_id'];

		$query = <<<SQL_QUERY
			UPDATE
				`admin`
			SET
				`admin_pass` = ?
			WHERE
				`admin_id` = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($upass, $user_id));
		write_log($_SESSION['user_logged'] . ": update password!");
		set_page_message(tr('User password updated successfully!'));
	}
}

function check_udata($id, $pass) {
	$sql = Database::getInstance();

	$query = <<<SQL_QUERY
		SELECT
			`admin_id`, `admin_pass`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
		AND
			`admin_pass` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($id, md5($pass)));

	return (($rs->RecordCount()) != 1) ? false : true;
}

/*
 *
 * static page messages.
 *
 */
gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_general_information.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_general_information.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_CHANGE_PASSWORD' 	=> tr('Change password'),
		'TR_PASSWORD_DATA' 		=> tr('Password data'),
		'TR_PASSWORD' 			=> tr('Password'),
		'TR_PASSWORD_REPEAT' 	=> tr('Repeat password'),
		'TR_UPDATE_PASSWORD' 	=> tr('Update password'),
		'TR_CURR_PASSWORD' 		=> tr('Current password'),
		// The entries below are for Demo versions only
		'PASSWORD_DISABLED'		=> tr('Password change is deactivated!'),
		'DEMO_VERSION'			=> tr('Demo Version!')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
