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

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/sql_manage.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('db_list', 'page');
$tpl->define_dynamic('db_message', 'db_list');
$tpl->define_dynamic('user_list', 'db_list');

$count = -1;

// page functions.
function gen_db_user_list(&$tpl, &$sql, $db_id) {
	global $count;

	$query = "
		SELECT
			`sqlu_id`, `sqlu_name`
		FROM
			`sql_user`
		WHERE
			`sqld_id` = ?
		ORDER BY
			`sqlu_name`
	";

	$rs = exec_query($sql, $query, array($db_id));

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'DB_MSG'	=> tr('Database user list is empty!'),
				'USER_LIST'	=> ''
			)
		);
		$tpl->parse('DB_MESSAGE', 'db_message');
	} else {
		$tpl->assign(
			array(
				'USER_LIST'		=> '',
				'DB_MESSAGE'	=> ''
			)
		);

		while (!$rs->EOF) {
			$count++;
			$user_id = $rs->fields['sqlu_id'];
			$user_mysql = $rs->fields['sqlu_name'];
			$tpl->assign(
				array(
					'DB_USER'	=> $user_mysql,
					'USER_ID'	=> $user_id
				)
			);
			$tpl->parse('USER_LIST', '.user_list');
			$rs->MoveNext();
		}
	}
}

function gen_db_list(&$tpl, &$sql, $user_id) {
	$dmn_id = get_user_domain_id($sql, $user_id);

	$query = "
		SELECT
			`sqld_id`, `sqld_name`
		FROM
			`sql_database`
		WHERE
			`domain_id` = ?
		ORDER BY
			`sqld_name`
	";

	$rs = exec_query($sql, $query, array($dmn_id));

	if ($rs->RecordCount() == 0) {
		set_page_message(tr('Database list is empty!'));
		$tpl->assign('DB_LIST', '');
	} else {
		while (!$rs->EOF) {
			$db_id = $rs->fields['sqld_id'];
			$db_name = $rs->fields['sqld_name'];
			gen_db_user_list($tpl, $sql, $db_id);
			$tpl->assign(
				array(
					'DB_ID'		=> "$db_id",
					'DB_NAME'	=> "$db_name"
				)
			);
			$tpl->parse('DB_LIST', '.db_list');
			$rs->MoveNext();
		}
	}
}

// common page data.

// check User sql permission
if (isset($_SESSION['sql_support']) && $_SESSION['sql_support'] == "no") {
	header("Location: index.php");
	exit;
}

$theme_color = Config::get('USER_INITIAL_THEME');
$tpl->assign(
	array(
		'TR_CLIENT_MANAGE_SQL_PAGE_TITLE' => tr('ispCP - Client/Manage SQL'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

// dynamic page data.

gen_db_list($tpl, $sql, $_SESSION['user_id']);

// static page messages.

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_manage_sql.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_manage_sql.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_MANAGE_SQL'			=> tr('Manage SQL'),
		'TR_DELETE'				=> tr('Delete'),
		'TR_DATABASE'			=> tr('Database Name and Users'),
		'TR_CHANGE_PASSWORD'	=> tr('Change password'),
		'TR_ACTION'				=> tr('Action'),
		'TR_PHP_MYADMIN'		=> tr('phpMyAdmin'),
		'TR_DATABASE_USERS'		=> tr('Database users'),
		'TR_ADD_USER'			=> tr('Add SQL user'),
		'TR_EXECUTE_QUERY'		=> tr('Execute query'),
		'TR_CHANGE_PASSWORD'	=> tr('Change password'),
		'TR_LOGIN_PMA'			=> tr('Login phpMyAdmin'),
		'TR_MESSAGE_DELETE'		=> tr('This database will be permanently deleted. This process cannot be recovered. All users linked to this database will also be deleted if not linked to another database.\n\nAre you sure you want to delete %s?')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>
