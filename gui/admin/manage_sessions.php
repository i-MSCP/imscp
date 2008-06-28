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
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/manage_sessions.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('user_session', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array('TR_ADMIN_MANAGE_SESSIONS_PAGE_TITLE' => tr('ispCP - Admin/Manage Sessions'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
		)
	);

function kill_session($sql) {
	if (isset($_GET['kill']) && $_GET['kill'] !== '' && $_GET['kill'] !== $_SESSION['user_logged']) {
		$admin_name = $_GET['kill'];
		$query = <<<SQL_QUERY
        delete from
            login
        where
            session_id = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($admin_name));
		set_page_message(tr('User session was killed!'));
		write_log($_SESSION['user_logged'] . ": killed user session: $admin_name!");
	}
}

function gen_user_sessions(&$tpl, &$sql) {
	$query = <<<SQL_QUERY
                select
                    *
                from
                    login
SQL_QUERY;

	$rs = exec_query($sql, $query, array());

	$row = 1;
	while (!$rs->EOF) {
		if ($row++ % 2 == 0) {
			$tpl->assign(
				array('ADMIN_CLASS' => 'content2',
					)
				);
		} else {
			$tpl->assign(
				array('ADMIN_CLASS' => 'content',
					)
				);
		}

		$tpl->assign(
			array('ADMIN_USERNAME' => $rs->fields['user_name'],
				'LOGIN_TIME' => date("G:i:s", $rs->fields['lastaccess']),
				)
			);

		$sess_id = session_id();

		if ($sess_id === $rs->fields['session_id']) {
			$tpl->assign('KILL_LINK', 'manage_sessions.php');
		} else {
			$tpl->assign('KILL_LINK', 'manage_sessions.php?kill=' . $rs->fields['session_id']);
		}

		$tpl->parse('USER_SESSION', '.user_session');

		$rs->MoveNext();
	}
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_manage_users.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_manage_users.tpl');

kill_session($sql);

gen_user_sessions($tpl, $sql);

$tpl->assign(
	array('TR_MANAGE_USER_SESSIONS' => tr('Manage user sessions'),
		'TR_USERNAME' => tr('Username'),
		'TR_USERTYPE' => tr('User type'),
		'TR_LOGIN_ON' => tr('Last access'),
		'TR_OPTIONS' => tr('Options'),
		'TR_DELETE' => tr('Kill session'),
		)
	);
// gen_page_message($tpl);
gen_page_message($tpl);

$tpl->parse('PAGE', 'page');

$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) dump_gui_debug();

unset_messages();

?>
