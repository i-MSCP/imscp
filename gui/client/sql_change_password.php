<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/sql_change_password.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

if (isset($_GET['id'])) {
	$db_user_id = $_GET['id'];
} else if (isset($_POST['id'])) {
	$db_user_id = $_POST['id'];
} else {
	user_goto('sql_manage.php');
}

// page functions.
function change_sql_user_pass(&$sql, $db_user_id, $db_user_name) {
	if (!isset($_POST['uaction'])) {
		return;
	}

	if ($_POST['pass'] === '' && $_POST['pass_rep'] === '') {
		set_page_message(tr('Please type user password!'));
		return;
	}

	if ($_POST['pass'] !== $_POST['pass_rep']) {
		set_page_message(tr('Entered passwords do not match!'));
		return;
	}

	if (strlen($_POST['pass']) > Config::get('MAX_SQL_PASS_LENGTH')) {
		set_page_message(tr('Too long user password!'));
		return;
	}

	if (!chk_password($_POST['pass'])) {
		if (Config::get('PASSWD_STRONG')) {
			set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), Config::get('PASSWD_CHARS')));
		} else {
			set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), Config::get('PASSWD_CHARS')));
		}
		return;
	}

	$user_pass = $_POST['pass'];

	// update user pass in the ispcp sql_user table;
	$query = "
		UPDATE
			`sql_user`
		SET
			`sqlu_pass` = ?
		WHERE
			`sqlu_name` = ?
	";

	$rs = exec_query($sql, $query, array(encrypt_db_password($user_pass), $db_user_name));

	// update user pass in the mysql system tables;

	$query = "SET PASSWORD FOR '$db_user_name'@'%' = PASSWORD('$user_pass')";

	$rs = execute_query($sql, $query);

	$query = "SET PASSWORD FOR '$db_user_name'@localhost = PASSWORD('$user_pass')";
	$rs = execute_query($sql, $query);

	write_log($_SESSION['user_logged'] . ": update SQL user password: " . $db_user_name);
	set_page_message(tr('SQL user password was successfully changed!'));
	user_goto('sql_manage.php');
}

function gen_page_data(&$tpl, &$sql, $db_user_id) {
	$query = <<<SQL_QUERY
		SELECT
			`sqlu_name`
		FROM
			`sql_user`
		WHERE
			`sqlu_id` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($db_user_id));
	$tpl->assign(
		array(
			'USER_NAME' => $rs->fields['sqlu_name'],
			'ID' => $db_user_id
		)
	);
	return $rs->fields['sqlu_name'];
}

// common page data.

if (isset($_SESSION['sql_support']) && $_SESSION['sql_support'] == "no") {
	user_goto('index.php');
}

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_SQL_CHANGE_PASSWORD_PAGE_TITLE' => tr('ispCP - Client/Change SQL User Password'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);


// dynamic page data.
$db_user_name = gen_page_data($tpl, $sql, $db_user_id);
check_usr_sql_perms($sql, $db_user_id);
change_sql_user_pass($sql, $db_user_id, $db_user_name);

// static page messages.
gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_manage_sql.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_manage_sql.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_CHANGE_SQL_USER_PASSWORD' => tr('Change SQL user password'),
		'TR_USER_NAME' => tr('User name'),
		'TR_PASS' => tr('Password'),
		'TR_PASS_REP' => tr('Repeat password'),
		'TR_CHANGE' => tr('Change')
	)
);

gen_page_message($tpl);
$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
