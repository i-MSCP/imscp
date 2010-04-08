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
$tpl->define_dynamic('page', Config::getInstance()->get('CLIENT_TEMPLATE_PATH') . '/sql_database_add.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('mysql_prefix_no', 'page');
$tpl->define_dynamic('mysql_prefix_yes', 'page');
$tpl->define_dynamic('mysql_prefix_infront', 'page');
$tpl->define_dynamic('mysql_prefix_behind', 'page');
$tpl->define_dynamic('mysql_prefix_all', 'page');

// page functions.

function gen_page_post_data(&$tpl) {
	if (Config::getInstance()->get('MYSQL_PREFIX') === 'yes') {
		$tpl->assign('MYSQL_PREFIX_YES', '');
		if (Config::getInstance()->get('MYSQL_PREFIX_TYPE') === 'behind') {
			$tpl->assign('MYSQL_PREFIX_INFRONT', '');
			$tpl->parse('MYSQL_PREFIX_BEHIND', 'mysql_prefix_behind');
			$tpl->assign('MYSQL_PREFIX_ALL', '');
		} else {
			$tpl->parse('MYSQL_PREFIX_INFRONT', 'mysql_prefix_infront');
			$tpl->assign('MYSQL_PREFIX_BEHIND', '');
			$tpl->assign('MYSQL_PREFIX_ALL', '');
		}
	} else {
		$tpl->assign('MYSQL_PREFIX_NO', '');
		$tpl->assign('MYSQL_PREFIX_INFRONT', '');
		$tpl->assign('MYSQL_PREFIX_BEHIND', '');
		$tpl->parse('MYSQL_PREFIX_ALL', 'mysql_prefix_all');
	}

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_db') {
		$tpl->assign(
			array(
				'DB_NAME' => clean_input($_POST['db_name']),
				'USE_DMN_ID' => (isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on') ? 'checked="checked"' : '',
				'START_ID_POS_CHECKED' => (isset($_POST['id_pos']) && $_POST['id_pos'] !== 'end') ? 'checked="checked"' : '',
				'END_ID_POS_CHECKED' => (isset($_POST['id_pos']) && $_POST['id_pos'] === 'end') ? 'checked="checked"' : ''
			)
		);
	} else {
		$tpl->assign(
			array(
				'DB_NAME' => '',
				'USE_DMN_ID' => '',
				'START_ID_POS_CHECKED' => 'checked="checked"',
				'END_ID_POS_CHECKED' => ''
			)
		);
	}
}

function check_db_name(&$sql, $db_name) {
	$query = "SHOW DATABASES";

	$rs = exec_query($sql, $query, array());

	while (!$rs->EOF) {
		if ($db_name === $rs->fields[0]) return 1;
		$rs->MoveNext();
	}

	return 0;
}

function add_sql_database(&$sql, $user_id) {
	if (!isset($_POST['uaction'])) return;

	// let's generate database name.

	if (empty($_POST['db_name'])) {
		set_page_message(tr('Please type database name!'));
		return;
	}

	$dmn_id = get_user_domain_id($sql, $user_id);

	if (isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on') {

		// we'll use domain_id in the name of the database;
		if (isset($_POST['id_pos']) && $_POST['id_pos'] === 'start') {
			$db_name = $dmn_id . "_" . clean_input($_POST['db_name']);
		} else if (isset($_POST['id_pos']) && $_POST['id_pos'] === 'end') {
			$db_name = clean_input($_POST['db_name']) . "_" . $dmn_id;
		}
	} else {
		$db_name = clean_input($_POST['db_name']);
	}

	if (strlen($db_name) > Config::getInstance()->get('MAX_SQL_DATABASE_LENGTH')) {
		set_page_message(tr('Database name is too long!'));
		return;
	}

	// have we such database in the system!?
	if (check_db_name($sql, $db_name)) {
		set_page_message(tr('Specified database name already exists!'));
		return;
	}
	// are wildcards used?
	if (preg_match("/[%|\?]+/", $db_name)) {
		set_page_message(tr('Wildcards such as %% and ? are not allowed!'));
		return;
	}

	$query = 'create database ' . quoteIdentifier($db_name);
	$rs = exec_query($sql, $query, array());

	$query = <<<SQL_QUERY
		INSERT INTO `sql_database`
			(`domain_id`, `sqld_name`)
		VALUES
			(?, ?)
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id, $db_name));

	update_reseller_c_props(get_reseller_id($dmn_id));

	write_log($_SESSION['user_logged'] . ": adds new SQL database: " . $db_name);
	set_page_message(tr('SQL database created successfully!'));
	user_goto('sql_manage.php');
}

// common page data.

/**
 * check user sql permission
 */
function check_sql_permissions($sql, $user_id) {
	if (isset($_SESSION['sql_support']) && $_SESSION['sql_support'] == "no") {
		header("Location: index.php");
	}

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

	list($sqld_acc_cnt, $sqlu_acc_cnt) = get_domain_running_sql_acc_cnt($sql, $dmn_id);

	if ($dmn_sqld_limit != 0 && $sqld_acc_cnt >= $dmn_sqld_limit) {
		set_page_message(tr('SQL accounts limit reached!'));
		user_goto('sql_manage.php');
	}
}

$theme_color = Config::getInstance()->get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_ADD_SQL_DATABASE_PAGE_TITLE' => tr('ispCP - Client/Add SQL Database'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

// dynamic page data.

check_sql_permissions($sql, $_SESSION['user_id']);

gen_page_post_data($tpl);

add_sql_database($sql, $_SESSION['user_id']);

gen_client_mainmenu($tpl, Config::getInstance()->get('CLIENT_TEMPLATE_PATH') . '/main_menu_manage_sql.tpl');
gen_client_menu($tpl, Config::getInstance()->get('CLIENT_TEMPLATE_PATH') . '/menu_manage_sql.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_ADD_DATABASE' => tr('Add SQL database'),
		'TR_DB_NAME' => tr('Database name'),
		'TR_USE_DMN_ID' => tr('Use numeric ID'),
		'TR_START_ID_POS' => tr('Before the name'),
		'TR_END_ID_POS' => tr('After the name'),
		'TR_ADD' => tr('Add')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::getInstance()->get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
