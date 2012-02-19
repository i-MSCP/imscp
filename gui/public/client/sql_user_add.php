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
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $customerId Customer unique identifier
 * @param int $databaseId Database unique identifier
 * @param array $sqlUserList
 */
function check_sql_permissions($tpl, $customerId, $databaseId, $sqlUserList)
{
	$domainProperties = get_domain_default_props($customerId, true);
	$domainSqlUsersLimit = $domainProperties['domain_sqlu_limit'];

	$limits =  get_domain_running_sql_acc_cnt($domainProperties['domain_id']);

	if ($domainSqlUsersLimit != 0 && $limits[1] >= $domainSqlUsersLimit) {
		if (!$sqlUserList) {
			set_page_message(tr('SQL users limit reached.'), 'error');
			redirectTo('sql_manage.php');
		} else {
			$tpl->assign('CREATE_SQLUSER', '');
		}
	}

	$query = "
		SELECT
			`t1`.`domain_id`
		FROM
			`domain` `t1`
		INNER JOIN
			`sql_database` `t2` ON(t2.domain_id = t1.domain_id)
		WHERE
			`t1`.`domain_id` = ?
		AND
			`t2`.`sqld_id` = ?
		LIMIT 1
	";
	$stmt = exec_query($query, array($domainProperties['domain_id'], $databaseId));

	if (!$stmt->rowCount()) {
		set_page_message(tr('Wrong request'), 'error');
		redirectTo('sql_manage.php');
	}
}

/**
 *
 * @param int $databaseId Database unique identifier
 * @return array
 */
function get_sqluser_list_of_current_db($databaseId)
{
	$query = "SELECT `sqlu_name` FROM `sql_user` WHERE `sqld_id` = ?";
	$stmt = exec_query($query, $databaseId);

	$userlist = array();

	while (!$stmt->EOF) {
			$userlist[] = $stmt->fields['sqlu_name'];
			$stmt->moveNext();
	}

	return $userlist;
}

/**
 *
 * @param iMSCP_pTemplate $tpl
 * @param $sqlUserId
 * @param $databaseId
 * @return bool
 */
function gen_sql_user_list($tpl, $sqlUserId, $databaseId) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$firstPassed = true;
	$sqlUserFound = false;
	$oldrsName = '';
	$userlist = get_sqluser_list_of_current_db($databaseId);
	$domainId = get_user_domain_id($sqlUserId);

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
	$stmt = exec_query($query, array($domainId, $databaseId));

	while (!$stmt->EOF) {
		// Checks if it's the first element of the combobox and set it as selected
		if ($firstPassed) {
			$select = $cfg->HTML_SELECTED;
			$firstPassed = false;
		} else {
			$select = '';
		}
		// 1. Compares the sqluser name with the record before (Is set as '' at the first time, see above)
		// 2. Compares the sqluser name with the userlist of the current database
		if ($oldrsName != $stmt->fields['sqlu_name'] && !in_array($stmt->fields['sqlu_name'], $userlist)) {
			$sqlUserFound = true;
			$oldrsName = $stmt->fields['sqlu_name'];

			$tpl->assign(
				array(
					'SQLUSER_ID' => $stmt->fields['sqlu_id'],
					'SQLUSER_SELECTED' => $select,
					'SQLUSER_NAME' => tohtml($stmt->fields['sqlu_name'])
				)
			);

			$tpl->parse('SQLUSER_LIST', '.sqluser_list');
		}

		$stmt->moveNext();
	}

	// let's hide the combobox in case there are no other sqlusers
	if (!$sqlUserFound) {
		$tpl->assign('SHOW_SQLUSER_LIST', '');
		return false;
	} else {
		return true;
	}
}

/**
 *
 * @param $db_user
 * @return mixed
 */
function check_db_user($db_user)
{
	$query = "SELECT COUNT(`User`) `cnt` FROM `mysql`.`user` WHERE `User` = ?";
	$stmt = exec_query($query, $db_user);

	return $stmt->fields['cnt'];
}

/**
 *
 * @param $user_id
 * @param $databaseId
 * @return mixed
 */
function add_sql_user($user_id, $databaseId)
{
	if (!isset($_POST['uaction'])) {
		return;
	}

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeAddSqlUser);

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// let's check user input

	if (empty($_POST['user_name']) && !isset($_POST['Add_Exist'])) {
		set_page_message(tr('Please enter an username.'), 'error');
		return;
	}

	if (empty($_POST['pass']) && empty($_POST['pass_rep']) && !isset($_POST['Add_Exist'])) {
		set_page_message(tr('Please enter a password.'), 'error');
		return;
	}

	if ((isset($_POST['pass']) && isset($_POST['pass_rep'])) && $_POST['pass'] !== $_POST['pass_rep']
		&& !isset($_POST['Add_Exist'])) {
		set_page_message(tr("Entered passwords doesn't matches."), 'error');
		return;
	}

	if (isset($_POST['pass']) && strlen($_POST['pass']) > $cfg->MAX_SQL_PASS_LENGTH && !isset($_POST['Add_Exist'])) {
		set_page_message(tr('The password is too long.'), 'error');
		return;
	}

	if (isset($_POST['pass']) && !preg_match('/^[[:alnum:]:!*+#_.-]+$/', $_POST['pass'])
		&& !isset($_POST['Add_Exist'])) {
		set_page_message(tr("Please, don't use special chars such as '@, $, %...' in the password."), 'error');
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
		$stmt = exec_query($query, $_POST['sqluser_id']);

		if (!$stmt->rowCount()) {
			set_page_message(tr('SQL-user not found.'), 'error');
			return;
		}

		$user_pass = $stmt->fields['sqlu_pass'];
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
		$stmt = exec_query($query, $_POST['sqluser_id']);
		$db_user = $stmt->fields['sqlu_name'];
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

	$query = "SELECT `sqld_name` FROM `sql_database` WHERE `sqld_id` = ? AND `domain_id` = ?";
	$stmt = exec_query($query, array($databaseId, $dmn_id));

	if(!$stmt->rowCount()) {
		set_page_message('wrong_request');
	} else {
		$db_name = $stmt->fields['sqld_name'];
		$db_name = preg_replace("/([_%\?\*])/", '\\\$1', $db_name);

		// Here we cannot use transaction because the GRANT statement cause an implicit commit
		// We execute the GRANT statements first to let the i-MSCP database in clean state if one of them fails.
		try {

			$sqlUserCreated = false;

			$query = "GRANT ALL PRIVILEGES ON " . quoteIdentifier($db_name) . ".* TO ?@? IDENTIFIED BY ?";

			// Todo prepare the statement only once here
			exec_query($query, array($db_user, 'localhost', $user_pass));
			exec_query($query, array($db_user, '%', $user_pass));

			$sqlUserCreated = true;

			iMSCP_Database::getInstance()->beginTransaction();

			$query = "INSERT INTO `sql_user` (`sqld_id`, `sqlu_name`, `sqlu_pass`) VALUES (?, ?, ?)";
			exec_query($query, array($databaseId, $db_user, $user_pass));

			update_reseller_c_props(get_reseller_id($dmn_id));

			iMSCP_Database::getInstance()->commit();

			iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterAddSqlUser);

			set_page_message(tr('SQL user successfully added.'), 'success');
			write_log(sprintf("%s added new SQL user: %s", $_SESSION['user_logged'], tohtml($db_user)), E_USER_NOTICE);
		} catch (iMSCP_Exception_Database $e) {
			if($sqlUserCreated) {
				// Our transaction failed so we rollback and we remove the user and all its privileges from
				// the mysql tables
				iMSCP_Database::getInstance()->rollBack();

				try { // We don't care about result here - An exception is throw in case the user do not exists
					exec_query("DROP USER ?@'localhost'", $db_user);
					exec_query("DROP USER ?@'%'", $db_user);
				} catch(iMSCP_Debug_Bar_Exception $e) {}
			}

			set_page_message(tr('System was unable to add the SQL user.'), 'error');
			write_log(sprintf("System was unable to add the '%s' SQL user. Message was: %s", $db_user, $e->getMessage()), E_USER_ERROR);
		}
	}

	redirectTo('sql_manage.php');
}

/**
 * @param iMSCP_pTemplate $tpl
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

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
