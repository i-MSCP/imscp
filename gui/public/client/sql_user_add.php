<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('sql')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/sql_user_add.tpl');
$tpl->define_dynamic('page_message', 'layout');
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
	redirectTo('sql_manage.php');
}

/**
 * @param $tpl
 * @param $user_id
 * @param $db_id
 * @param $sqluser_available
 * @return void
 */
function check_sql_permissions($tpl, $user_id, $db_id, $sqluser_available) {
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
	) = get_domain_default_props($user_id);

	list($sqld_acc_cnt, $sqlu_acc_cnt) = get_domain_running_sql_acc_cnt($dmn_id);

	if ($dmn_sqlu_limit != 0 && $sqlu_acc_cnt >= $dmn_sqlu_limit) {
		if (!$sqluser_available) {
			set_page_message(tr('SQL users limit reached.'), 'error');
			redirectTo('sql_manage.php');
		} else {
			$tpl->assign('CREATE_SQLUSER', '');
		}
	}

	$dmn_name = $_SESSION['user_logged'];

	$query = "
		SELECT
			t1.`sqld_id`, t2.`domain_id`, t2.`domain_name`
		FROM
			`sql_database` AS t1,
			`domain` AS t2
		WHERE
			t1.`sqld_id` = ?
		AND
			t2.`domain_id` = t1.`domain_id`
		AND
			t2.`domain_name` = ?
	";

	$rs = exec_query($query, array($db_id, $dmn_name));

	if (!$rs->recordCount()) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'), 'error');
		redirectTo('sql_manage.php');
	}
}

/**
 * @param $db_id
 * @return array|bool
 */
function get_sqluser_list_of_current_db($db_id) {
	$query = "SELECT `sqlu_name` FROM `sql_user` WHERE `sqld_id` = ?";

	$rs = exec_query($query, $db_id);

	if ($rs->recordCount() == 0) {
		return false;
	} else {
		while (!$rs->EOF) {
			$userlist[] = $rs->fields['sqlu_name'];
			$rs->moveNext();
		}
	}

	return $userlist;
}

/**
 * @param $tpl
 * @param $user_id
 * @param $db_id
 * @return bool
 */
function gen_sql_user_list($tpl, $user_id, $db_id) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$first_passed = true;
	$user_found = false;
	$oldrs_name = '';
	$userlist = get_sqluser_list_of_current_db($db_id);
	$dmn_id = get_user_domain_id($user_id);
	// Let's select all sqlusers of the current domain except the users of the current database
	$query = "
		SELECT
			t1.`sqlu_name`, t1.`sqlu_id`
		FROM
			`sql_user` AS t1, `sql_database` AS t2
		WHERE
			t1.`sqld_id` = t2.`sqld_id`
		AND
			t2.`domain_id` = ?
		AND
			t1.`sqld_id` <> ?
		ORDER BY
			t1.`sqlu_name`
	";
	$rs = exec_query($query, array($dmn_id, $db_id));

	while (!$rs->EOF) {
		// Checks if it's the first element of the combobox and set it as selected
		if ($first_passed) {
			$select = $cfg->HTML_SELECTED;
			$first_passed = false;
		} else {
			$select = '';
		}
		// 1. Compares the sqluser name with the record before (Is set as '' at the first time, see above)
		// 2. Compares the sqluser name with the userlist of the current database
		if ($oldrs_name != $rs->fields['sqlu_name'] && @!in_array($rs->fields['sqlu_name'], $userlist)) {
			$user_found = true;
			$oldrs_name = $rs->fields['sqlu_name'];
			$tpl->assign(
				array(
					'SQLUSER_ID' => $rs->fields['sqlu_id'],
					'SQLUSER_SELECTED' => $select,
					'SQLUSER_NAME' => tohtml($rs->fields['sqlu_name'])
				)
			);
			$tpl->parse('SQLUSER_LIST', '.sqluser_list');
		}
		$rs->moveNext();
	}
	// let's hide the combobox in case there are no other sqlusers
	if (!$user_found) {
		$tpl->assign('SHOW_SQLUSER_LIST', '');
		return false;
	} else {
		return true;
	}
}

/**
 * @param $db_user
 * @return
 */
function check_db_user($db_user) {
	$query = "SELECT COUNT(`User`) AS cnt FROM mysql.`user` WHERE `User` = ?";

	$rs = exec_query($query, $db_user);
	return $rs->fields['cnt'];
}

/**
 * @todo
 * 	* Database user with same name can be added several times
 *  * If creation of database user fails in MySQL-Table, database user is already
 * 		in local i-MSCP table -> Error handling
 */
function add_sql_user($user_id, $db_id)
{
	if (!isset($_POST['uaction'])) {
		return;
	}

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeAddSqlUser);

	$cfg = iMSCP_Registry::get('config');

	// let's check user input

	if (empty($_POST['user_name']) && !isset($_POST['Add_Exist'])) {
		set_page_message(tr('Please type user name!'), 'error');
		return;
	}

	if (empty($_POST['pass']) && empty($_POST['pass_rep']) && !isset($_POST['Add_Exist'])) {
		set_page_message(tr('Please type user password.'), 'error');
		return;
	}

	if ((isset($_POST['pass']) && isset($_POST['pass_rep'])) && $_POST['pass'] !== $_POST['pass_rep']
		&& !isset($_POST['Add_Exist'])) {
		set_page_message(tr('Entered passwords do not match.'), 'error');
		return;
	}

	if (isset($_POST['pass']) && strlen($_POST['pass']) > $cfg->MAX_SQL_PASS_LENGTH && !isset($_POST['Add_Exist'])) {
		set_page_message(tr('Too user long password.'), 'error');
		return;
	}

	if (isset($_POST['pass']) && !preg_match('/^[[:alnum:]:!*+#_.-]+$/', $_POST['pass'])
		&& !isset($_POST['Add_Exist'])) {
		set_page_message(tr("Please, don't use special chars like '@, $, %...' in the password."), 'error');
		return;
	}

	if (isset($_POST['pass']) && !chk_password($_POST['pass']) && !isset($_POST['Add_Exist'])) {
		if ($cfg->PASSWD_STRONG) {
			set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), $cfg->PASSWD_CHARS), 'error');
		} else {
			set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), $cfg->PASSWD_CHARS), 'error');
		}
		return;
	}

	if (isset($_POST['Add_Exist'])) {
		$query = "SELECT `sqlu_pass` FROM `sql_user` WHERE `sqlu_id` = ?";
		$rs = exec_query($query, $_POST['sqluser_id']);

		if ($rs->recordCount() == 0) {
			set_page_message(tr('SQL-user not found.'), 'error');
			return;
		}
		$user_pass = $rs->fields['sqlu_pass'];
	} else {
		$user_pass = $_POST['pass'];
	}

	$dmn_id = get_user_domain_id($user_id);

	if (!isset($_POST['Add_Exist'])) {

		// we'll use domain_id in the name of the database;
		if (isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on'
			&& isset($_POST['id_pos'])
			&& $_POST['id_pos'] === 'start') {
			$db_user = $dmn_id . "_" . clean_input($_POST['user_name']);
		} else if (isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on'
			&& isset($_POST['id_pos'])
			&& $_POST['id_pos'] === 'end') {
			$db_user = clean_input($_POST['user_name']) . "_" . $dmn_id;
		} else {
			$db_user = clean_input($_POST['user_name']);
		}
	} else {
		$query = "SELECT `sqlu_name` FROM `sql_user` WHERE `sqlu_id` = ?";
		$rs = exec_query($query, $_POST['sqluser_id']);
		$db_user = $rs->fields['sqlu_name'];
	}

	if (strlen($db_user) > $cfg->MAX_SQL_USER_LENGTH) {
		set_page_message(tr('User name too long!'), 'error');
		return;
	}

	// are wildcards used?
	if (preg_match("/[%|\?]+/", $db_user)) {
		set_page_message(tr('Wildcards such as %% and ? are not allowed.'), 'error');
		return;
	}

	// have we such sql user in the system?!

	if (check_db_user($db_user) && !isset($_POST['Add_Exist'])) {
		set_page_message(tr('Specified SQL username name already exists.'), 'error');
		return;
	}

	// add user in the i-MSCP table;

	$query = "
		INSERT INTO `sql_user` (
		    `sqld_id`, `sqlu_name`, `sqlu_pass`
		) VALUES (
		    ?, ?, ?
		)
	";
	exec_query($query, array($db_id, $db_user, $user_pass));

	update_reseller_c_props(get_reseller_id($dmn_id));

	$query = "
		SELECT
			`sqld_name` AS `db_name`
		FROM
			`sql_database`
		WHERE
			`sqld_id` = ?
		AND
			`domain_id` = ?
	";

	$rs = exec_query($query, array($db_id, $dmn_id));
	$db_name = $rs->fields['db_name'];
	$db_name = preg_replace("/([_%\?\*])/",'\\\$1',$db_name);

	// add user in the mysql system tables
	$query = "GRANT ALL PRIVILEGES ON ". quoteIdentifier($db_name) .".* TO ?@? IDENTIFIED BY ?";
	exec_query($query, array($db_user, "localhost", $user_pass));
	exec_query($query, array($db_user, "%", $user_pass));

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterAddSqlUser);

	write_log($_SESSION['user_logged'] . ": add SQL user: " . tohtml($db_user), E_USER_NOTICE);
	set_page_message(tr('SQL user created.'), 'success');
	redirectTo('sql_manage.php');
}

/**
 * @param $tpl
 * @param $db_id
 * @return void
 */
function gen_page_post_data($tpl, $db_id) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($cfg->MYSQL_PREFIX === 'yes') {
		$tpl->assign('MYSQL_PREFIX_YES', '');
		if ($cfg->MYSQL_PREFIX_TYPE === 'behind') {
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

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_user') {
		$tpl->assign(
			array(
				'USER_NAME' => (isset($_POST['user_name'])) ? clean_html($_POST['user_name'], true) : '',
				'USE_DMN_ID' => (isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on') ? $cfg->HTML_CHECKED : '',
				'START_ID_POS_CHECKED' => (isset($_POST['id_pos']) && $_POST['id_pos'] !== 'end') ? $cfg->HTML_CHECKED : '',
				'END_ID_POS_CHECKED' => (isset($_POST['id_pos']) && $_POST['id_pos'] === 'end') ? $cfg->HTML_CHECKED : ''));
	} else {
		$tpl->assign(
			array(
				'USER_NAME' => '',
				'USE_DMN_ID' => '',
				'START_ID_POS_CHECKED' => '',
				'END_ID_POS_CHECKED' => $cfg->HTML_CHECKED));
	}

	$tpl->assign('ID', $db_id);
}

if (isset($_SESSION['sql_support']) && $_SESSION['sql_support'] == "no") {
	redirectTo('index.php');
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Client/Add SQL User'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));


$sqluser_available = gen_sql_user_list($tpl, $_SESSION['user_id'], $db_id);
check_sql_permissions($tpl, $_SESSION['user_id'], $db_id, $sqluser_available);
gen_page_post_data($tpl, $db_id);
add_sql_user($_SESSION['user_id'], $db_id);
generateNavigation($tpl);

$tpl->assign(
	array(
		'TR_ADD_SQL_USER' => tr('Add SQL user'),
		'TR_USER_NAME' => tr('SQL user name'),
		'TR_USE_DMN_ID' => tr('Use numeric ID'),
		'TR_START_ID_POS' => tr('In front the name'),
		'TR_END_ID_POS' => tr('Behind the name'),
		'TR_ADD' => tr('Add'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_ADD_EXIST' => tr('Assign'),
		'TR_PASS' => tr('Password'),
		'TR_PASS_REP' => tr('Repeat password'),
		'TR_SQL_USER_NAME' => tr('SQL users'),
		'TR_ASSIGN_EXISTING_SQL_USER' => tr('Assign existing SQL user'),
		'TR_NEW_SQL_USER_DATA' => tr('New Sql user data')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
