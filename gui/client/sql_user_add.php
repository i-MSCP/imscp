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
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/sql_user_add.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('mysql_prefix_no', 'page');
$tpl->define_dynamic('mysql_prefix_yes', 'page');
$tpl->define_dynamic('mysql_prefix_infront', 'page');
$tpl->define_dynamic('mysql_prefix_behind', 'page');
$tpl->define_dynamic('mysql_prefix_all', 'page');
$tpl->define_dynamic('sqluser_list', 'page');
$tpl->define_dynamic('show_sqluser_list', 'page');
$tpl->define_dynamic('create_sqluser', 'page');

if (isset($_GET['id'])) {
	$db_id = $_GET['id'];
} else if (isset($_POST['id'])) {
	$db_id = $_POST['id'];
} else {
	user_goto('sql_manage.php');
}

// page functions.

function check_sql_permissions(&$tpl, $sql, $user_id, $db_id, $sqluser_available) {
	list($dmn_id,
		$dmn_name,
		$dmn_gid,
		$dmn_uid,
		$dmn_created_id,
		$dmn_created,
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
		$dmn_cgi) = get_domain_default_props($sql, $user_id);

	list($sqld_acc_cnt,
		$sqlu_acc_cnt) = get_domain_running_sql_acc_cnt($sql, $dmn_id);

	if ($dmn_sqlu_limit != 0 && $sqlu_acc_cnt >= $dmn_sqlu_limit) {
		if (!$sqluser_available) {
			set_page_message(tr('SQL users limit reached!'));
			header("Location: sql_manage.php");
			die();
		} else {
			$tpl->assign('CREATE_SQLUSER', '');
		}
	}

	$dmn_name = $_SESSION['user_logged'];

	$query = <<<SQL_QUERY
        select
            t1.sqld_id, t2.domain_id, t2.domain_name
        from
            sql_database as t1,
            domain as t2
        where
            t1.sqld_id = ?
          and
            t2.domain_id = t1.domain_id
          and
            t2.domain_name = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($db_id, $dmn_name));

	if ($rs->RecordCount() == 0) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
		header('Location: sql_manage.php');
		die();
	}
}
// Returns an array with a list of the sqlusers of the current database
function get_sqluser_list_of_current_db(&$sql, $db_id) {
	$query = "SELECT sqlu_name
			  FROM sql_user
			  WHERE sqld_id = ?";

	$rs = exec_query($sql, $query, array($db_id));

	if ($rs->RecordCount() == 0) {
		return false;
	} else {
		while (!$rs->EOF) {
			$userlist[] = $rs->fields['sqlu_name'];
			$rs->MoveNext();
		}
	}

	return $userlist;
}

function gen_sql_user_list(&$sql, &$tpl, $user_id, $db_id) {
	$first_passed = true;
	$user_found = false;
	$oldrs_name = '';
	$userlist = get_sqluser_list_of_current_db($sql, $db_id);
	$dmn_id = get_user_domain_id($sql, $user_id);
	// Lets select all sqlusers of the current domain except the users of the current database
	$query = <<<SQL_QUERY
	SELECT
		t1.sqlu_name,
		t1.sqlu_id
	FROM
		sql_user AS t1,
		sql_database AS t2
    WHERE
	    t1.sqld_id = t2.sqld_id
	  AND
	    t2.domain_id = ?
	  AND
	    t1.sqld_id  <> ?
	ORDER BY
	    t1.sqlu_name
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id, $db_id));

	while (!$rs->EOF) {
		// Checks if it's the first element of the combobox and set it as selected
		if ($first_passed) {
			$select = "SELECTED";
			$first_passed = false;
		} else {
			$select = '';
		}
		// 1. Compares the sqluser name with the record before (Is set as '' at the first time, see above)
		// 2. Compares the sqluser name with the userlist of the current database
		if ($oldrs_name != $rs->fields['sqlu_name'] && @!in_array($rs->fields['sqlu_name'], $userlist)) {
			$user_found = true;
			$oldrs_name = $rs->fields['sqlu_name'];
			$tpl->assign(array('SQLUSER_ID' => $rs->fields['sqlu_id'],
					'SQLUSER_SELECTED' => $select,
					'SQLUSER_NAME' => $rs->fields['sqlu_name']));
			$tpl->parse('SQLUSER_LIST', '.sqluser_list');
		}
		$rs->MoveNext();
	}
	// Lets hide the combobox in case there are no other sqlusers
	if (!$user_found) {
		$tpl->assign('SHOW_SQLUSER_LIST', '');
		return false;
	} else {
		return true;
	}
}

function check_db_user(&$sql, $db_user) {
	$query = <<<SQL_QUERY
        select count(User) as cnt from mysql.user where User=?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($db_user));
	return $rs->fields['cnt'];
}

function add_sql_user(&$sql, $user_id, $db_id) {
	if (!isset($_POST['uaction'])) return;

	// let's check user input;

	if (empty($_POST['user_name']) && !isset($_POST['Add_Exist'])) {
		set_page_message(tr('Please type user name!'));
		return;
	}

	if (empty($_POST['pass']) && empty($_POST['pass_rep']) && !isset($_POST['Add_Exist'])) {
		set_page_message(tr('Please type user password!'));
		return;
	}

	if ((isset($_POST['pass']) AND isset($_POST['pass_rep'])) && $_POST['pass'] !== $_POST['pass_rep'] AND !isset($_POST['Add_Exist'])) {
		set_page_message(tr('Entered passwords do not match!'));
		return;
	}

	if (isset($_POST['pass']) AND strlen($_POST['pass']) > Config::get('MAX_SQL_PASS_LENGTH') && !isset($_POST['Add_Exist'])) {
		set_page_message(tr('Too user long password!'));
		return;
	}

	if (isset($_POST['pass']) AND !chk_password($_POST['pass']) AND !isset($_POST['Add_Exist'])) {
		set_page_message(tr("Incorrect password length or syntax!"));
		return;
	}

	if (isset($_POST['Add_Exist'])) {
		$query = "SELECT sqlu_pass FROM sql_user WHERE sqlu_id = ?";
		$rs = exec_query($sql, $query, array($_POST['sqluser_id']));

		if ($rs->RecordCount() == 0) {
			set_page_message(tr('SQL-user not found! Maybe it was deleted by another user!'));
			return;
		}
		$user_pass = $rs->fields['sqlu_pass'];
	} else {
		$user_pass = $_POST['pass'];
	}

	$dmn_id = get_user_domain_id($sql, $user_id);

	if (!isset($_POST['Add_Exist'])) {

		// we'll use domain_id in the name of the database;
		if (isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on' && isset($_POST['id_pos']) && $_POST['id_pos'] === 'start') {
			$db_user = $dmn_id . "_" . clean_input($_POST['user_name']);
		} else if (isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on' && isset($_POST['id_pos']) && $_POST['id_pos'] === 'end') {
			$db_user = clean_input($_POST['user_name']) . "_" . $dmn_id;
		} else {
			$db_user = clean_input($_POST['user_name']);
		}
	} else {
		$query = "SELECT sqlu_name FROM sql_user WHERE sqlu_id = ?";
		$rs = exec_query($sql, $query, array($_POST['sqluser_id']));
		$db_user = $rs->fields['sqlu_name'];
	}

	if (strlen($db_user) > Config::get('MAX_SQL_USER_LENGTH')) {
		set_page_message(tr('User name too long!'));
		return;
	}
	// are wildcards used?

	if (preg_match("/[%|\?]+/", $db_user)) {
		set_page_message(tr('Wildcards as %% and ? are not allowed!'));
		return;
	}

	// have we such sql user in the system?!

	if (check_db_user($sql, $db_user) && !isset($_POST['Add_Exist'])) {
		set_page_message(tr('Specified SQL username name already exists!'));
		return;
	}

	// add user in the ispcp table;

	$query = <<<SQL_QUERY
        insert into sql_user
            (sqld_id, sqlu_name, sqlu_pass)
        values
            (?, ?, ?)
SQL_QUERY;

	$rs = exec_query($sql, $query, array($db_id, $db_user, $user_pass));

	$query = <<<SQL_QUERY
        select
            sqld_name as db_name
        from
            sql_database
        where
            sqld_id = ?
          and
            domain_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($db_id, $dmn_id));
	$db_name = $rs->fields['db_name'];

	// add user in the mysql system tables;

	$new_db_name = ereg_replace("_", "\\_", $db_name);
	$query = 'grant all on ' . quoteIdentifier($new_db_name) . '.* to ?@\'localhost\' identified by ?';
	$rs = exec_query($sql, $query, array($db_user, $user_pass));
	$query = 'grant all on ' . quoteIdentifier($new_db_name) . '.* to ?@\'%\' identified by ?';
	$rs = exec_query($sql, $query, array($db_user, $user_pass));

	write_log($_SESSION['user_logged'] . ": add SQL user: " . $db_user);
	set_page_message(tr('SQL user successfully added!'));
	user_goto('sql_manage.php');
}

function gen_page_post_data(&$tpl, $db_id) {
	if (Config::get('MYSQL_PREFIX') === 'yes') {
		$tpl->assign('MYSQL_PREFIX_YES', '');
	} else {
		$tpl->assign('MYSQL_PREFIX_NO', '');
	}

	if (Config::get('MYSQL_PREFIX_TYPE') === 'infront') {
		$tpl->parse('MYSQL_PREFIX_INFRONT', 'mysql_prefix_infront');
		$tpl->assign('MYSQL_PREFIX_BEHIND', '');
		$tpl->assign('MYSQL_PREFIX_ALL', '');
	} else if (Config::get('MYSQL_PREFIX_TYPE') === 'behind') {
		$tpl->assign('MYSQL_PREFIX_INFRONT', '');
		$tpl->parse('MYSQL_PREFIX_BEHIND', 'mysql_prefix_behind');
		$tpl->assign('MYSQL_PREFIX_ALL', '');
	} else {
		$tpl->assign('MYSQL_PREFIX_INFRONT', '');
		$tpl->assign('MYSQL_PREFIX_BEHIND', '');
		$tpl->parse('MYSQL_PREFIX_ALL', 'mysql_prefix_all');
	}

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_user') {
		$tpl->assign(array('USER_NAME' => (isset($_POST['user_name'])) ? $_POST['user_name'] : '',
				'USE_DMN_ID' => (isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on') ? 'checked' : '',
				'START_ID_POS_CHECKED' => (isset($_POST['id_pos']) && $_POST['id_pos'] !== 'end') ? 'checked' : '',
				'END_ID_POS_CHECKED' => (isset($_POST['id_pos']) && $_POST['id_pos'] === 'end') ? 'checked' : ''));
	} else {
		$tpl->assign(array('USER_NAME' => '',
				'USE_DMN_ID' => '',
				'START_ID_POS_CHECKED' => '',
				'END_ID_POS_CHECKED' => 'checked'));
	}

	$tpl->assign('ID', $db_id);
}

// common page data.

if (isset($_SESSION['sql_support']) && $_SESSION['sql_support'] == "no") {
	user_goto('index.php');
}

$theme_color = Config::get('USER_INITIAL_THEME');
$tpl->assign(array('TR_CLIENT_SQL_ADD_USER_PAGE_TITLE' => tr('ispCP - Client/Add SQL User'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])));

// dynamic page data.

$sqluser_available = gen_sql_user_list($sql, $tpl, $_SESSION['user_id'], $db_id);
check_sql_permissions($tpl, $sql, $_SESSION['user_id'], $db_id, $sqluser_available);
gen_page_post_data($tpl, $db_id);
add_sql_user($sql, $_SESSION['user_id'], $db_id);

// static page messages.

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_manage_sql.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_manage_sql.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(array('TR_ADD_SQL_USER' => tr('Add SQL user'),
		'TR_USER_NAME' => tr('SQL user name'),
		'TR_USE_DMN_ID' => tr('Use numeric ID'),
		'TR_START_ID_POS' => tr('In front the name'),
		'TR_END_ID_POS' => tr('Behind the name'),
		'TR_ADD' => tr('Add'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_ADD_EXIST' => tr('Add existing user'),
		'TR_PASS' => tr('Password'),
		'TR_PASS_REP' => tr('Repeat password'),
		'TR_SQL_USER_NAME' => tr('Existing SQL users')));

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>