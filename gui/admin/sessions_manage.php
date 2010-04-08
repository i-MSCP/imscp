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
$tpl->define_dynamic('page', Config::getInstance()->get('ADMIN_TEMPLATE_PATH') . '/sessions_manage.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('user_session', 'page');

$theme_color = Config::getInstance()->get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_ADMIN_MANAGE_SESSIONS_PAGE_TITLE' => tr('ispCP - Admin/Manage Sessions'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

function kill_session($sql) {
	if (isset($_GET['kill']) && $_GET['kill'] !== ''
		&& $_GET['kill'] !== $_SESSION['user_logged']) {
		$admin_name = $_GET['kill'];
		$query = <<<SQL_QUERY
		DELETE FROM
			`login`
		WHERE
			`session_id` = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($admin_name));
		set_page_message(tr('User session was killed!'));
		write_log($_SESSION['user_logged'] . ": killed user session: $admin_name!");
	}
}

function gen_user_sessions(&$tpl, &$sql) {
	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`login`
SQL_QUERY;

	$rs = exec_query($sql, $query, array());

	$row = 1;
	while (!$rs->EOF) {
		$tpl->assign(
			array(
				'ADMIN_CLASS' => ($row++ % 2 == 0) ? 'content2' : 'content',
			)
		);

		if ($rs->fields['user_name'] === NULL) {
			$tpl->assign(
				array(
					'ADMIN_USERNAME' => tr('Unknown'),
					'LOGIN_TIME' => date("G:i:s", $rs->fields['lastaccess'])
				)
			);
		} else {
			$tpl->assign(
				array(
					'ADMIN_USERNAME' => $rs->fields['user_name'],
					'LOGIN_TIME' => date("G:i:s", $rs->fields['lastaccess'])
				)
			);
		}

		$sess_id = session_id();

		if ($sess_id === $rs->fields['session_id']) {
			$tpl->assign('KILL_LINK', 'sessions_manage.php');
		} else {
			$tpl->assign('KILL_LINK', 'sessions_manage.php?kill=' . $rs->fields['session_id']);
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
gen_admin_mainmenu($tpl, Config::getInstance()->get('ADMIN_TEMPLATE_PATH') . '/main_menu_users_manage.tpl');
gen_admin_menu($tpl, Config::getInstance()->get('ADMIN_TEMPLATE_PATH') . '/menu_users_manage.tpl');

kill_session($sql);

gen_user_sessions($tpl, $sql);

$tpl->assign(
	array(
		'TR_MANAGE_USER_SESSIONS' => tr('Manage user sessions'),
		'TR_USERNAME' => tr('Username'),
		'TR_USERTYPE' => tr('User type'),
		'TR_LOGIN_ON' => tr('Last access'),
		'TR_OPTIONS' => tr('Options'),
		'TR_DELETE' => tr('Kill session'),
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');

$tpl->prnt();

if (Config::getInstance()->get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
