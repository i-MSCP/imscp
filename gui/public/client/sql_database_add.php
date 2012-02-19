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
$tpl->define_dynamic('page', 'client/sql_database_add.tpl');
$tpl->define_dynamic('page_message', 'layout');
$tpl->define_dynamic('mysql_prefix_no', 'page');
$tpl->define_dynamic('mysql_prefix_yes', 'page');
$tpl->define_dynamic('mysql_prefix_infront', 'page');
$tpl->define_dynamic('mysql_prefix_behind', 'page');
$tpl->define_dynamic('mysql_prefix_all', 'page');


/**
 * @param $tpl
 * @return void
 */
function gen_page_post_data($tpl)
{
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

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_db') {
		$tpl->assign(
			array(
				'DB_NAME' => clean_input($_POST['db_name'], true),
				'USE_DMN_ID' => (isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on') ? $cfg->HTML_CHECKED : '',
				'START_ID_POS_CHECKED' => (isset($_POST['id_pos']) && $_POST['id_pos'] !== 'end') ? $cfg->HTML_CHECKED : '',
				'END_ID_POS_CHECKED' => (isset($_POST['id_pos']) && $_POST['id_pos'] === 'end') ? $cfg->HTML_CHECKED : ''));
	} else {
		$tpl->assign(
			array(
				'DB_NAME' => '',
				'USE_DMN_ID' => '',
				'START_ID_POS_CHECKED' => $cfg->HTML_CHECKED,
				'END_ID_POS_CHECKED' => ''));
	}
}

/**
 * Check if a database with same name already exists
 *
 * @param  string $db_name database name to be checked
 * @return boolean TRUE if database exists, false otherwise
 */
function check_db_name($db_name) {

	$rs = exec_query('SHOW DATABASES');

	while (!$rs->EOF) {
		if ($db_name == $rs->fields['Database']) {
			return true;
		}

		$rs->moveNext();
	}

	return false;
}

/**
 * @param $user_id
 * @return
 */
function add_sql_database($user_id)
{
	if (!isset($_POST['uaction'])) return;

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeAddSqlDb);

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// let's generate database name.

	if (empty($_POST['db_name'])) {
		set_page_message(tr('Please type database name.'), 'error');
		return;
	}

	$dmn_id = get_user_domain_id($user_id);

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

	if (strlen($db_name) > $cfg->MAX_SQL_DATABASE_LENGTH) {
		set_page_message(tr('Database name is too long.'), 'error');
		return;
	}

	// have we such database in the system!?
	if (check_db_name($db_name)) {
		set_page_message(tr('Specified database name already exists.'), 'error');
		return;
	}
	// are wildcards used?
	if (preg_match("/[%|\?]+/", $db_name)) {
		set_page_message(tr('Wildcards such as %% and ? are not allowed.'), 'error');
		return;
	}

	// Here we cannot start transaction before the CREATE DATABABSE statement because its cause an implicit commit
	try {

		$dbCreated = false;

		execute_query('CREATE DATABASE IF NOT EXISTS ' . quoteIdentifier($db_name));

		$dbCreated = true;

		iMSCP_Database::getInstance()->beginTransaction();

		$query = "INSERT INTO `sql_database` (`domain_id`, `sqld_name`) VALUES (?, ?)";
		exec_query($query, array($dmn_id, $db_name));

		update_reseller_c_props(get_reseller_id($dmn_id));

		iMSCP_Database::getInstance()->commit();

		iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterAddSqlDb);

		set_page_message(tr('SQL database successfully added.'), 'success');
		write_log($_SESSION['user_logged'] . ": added new SQL database: " . tohtml($db_name), E_USER_NOTICE);

	} catch (iMSCP_Exception_Database $e) {
		if($dbCreated) { // Our transaction failed so we rollback and we remove the database previously created
			iMSCP_Database::getInstance()->rollBack();
			execute_query('DROP DATABASE IF EXISTS ' . quoteIdentifier($db_name));
		}

		set_page_message(tr('System was unable to add the SQL database.'), 'error');
		write_log(sprintf("System was unable to add the '%s' SQL database. Message was: %s", $db_name, $e->getMessage()), E_USER_ERROR);
	}


	redirectTo('sql_manage.php');
}

// common page data.

/**
 * @param $user_id
 * @return void
 */
function check_sql_permissions($user_id) {
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

	if ($dmn_sqld_limit != 0 && $sqld_acc_cnt >= $dmn_sqld_limit) {
		set_page_message(tr('SQL accounts limit reached.'), 'error');
		redirectTo('sql_manage.php');
	}
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Client/Add SQL Database'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

check_sql_permissions($_SESSION['user_id']);
gen_page_post_data($tpl);
add_sql_database($_SESSION['user_id']);
generateNavigation($tpl);

$tpl->assign(
	array(
		'TR_TITLE_ADD_DATABASE' => tr('Add SQL database'),
		'TR_DATABASE' => tr('Database'),
		'TR_DB_NAME' => tr('Database name'),
		'TR_USE_DMN_ID' => tr('Use numeric ID'),
		'TR_START_ID_POS' => tr('Before the name'),
		'TR_END_ID_POS' => tr('After the name'),
		'TR_ADD' => tr('Add')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
