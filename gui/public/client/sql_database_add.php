<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function client_generatePage($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($cfg['MYSQL_PREFIX'] === 'yes') {
		$tpl->assign('MYSQL_PREFIX_YES', '');

		if ($cfg['MYSQL_PREFIX_TYPE'] === 'behind') {
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

	if (isset($_POST['uaction']) && $_POST['uaction'] == 'add_db') {
		$tpl->assign(
			array(
				'DB_NAME' => clean_input($_POST['db_name'], true),
				'USE_DMN_ID' => (isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] == 'on') ? $cfg->HTML_CHECKED : '',
				'START_ID_POS_SELECTED' => (isset($_POST['id_pos']) && $_POST['id_pos'] != 'end') ? $cfg->HTML_SELECTED : '',
				'END_ID_POS_SELECTED' => (isset($_POST['id_pos']) && $_POST['id_pos'] == 'end') ? $cfg->HTML_SELECTED : ''
			)
		);
	} else {
		$tpl->assign(
			array(
				'DB_NAME' => '',
				'USE_DMN_ID' => '',
				'START_ID_POS_SELECTED' => $cfg->HTML_SELECTED,
				'END_ID_POS_SELECTED' => ''
			)
		);
	}
}

/**
 * Whether or not the given database already exists
 *
 * @param string $dbName database name to be checked
 * @return boolean TRUE if database exists, false otherwise
 */
function client_isDatabase($dbName)
{
	$stmt = exec_query('SHOW DATABASES LIKE ?', $dbName);

	return (bool) $stmt->rowCount();
}

/**
 * Add SQL database
 *
 * @param int $userId
 * @return void
 */
function client_addSqlDb($userId)
{
	if (!isset($_POST['uaction'])) return;

	if (empty($_POST['db_name'])) {
		set_page_message(tr('Please type database name.'), 'error');
		return;
	}

	$mainDmnId = get_user_domain_id($userId);

	if (isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on') {
		// we'll use domain_id in the name of the database;
		if (isset($_POST['id_pos']) && $_POST['id_pos'] === 'start') {
			$dbName = $mainDmnId . "_" . clean_input($_POST['db_name']);
		} else if (isset($_POST['id_pos']) && $_POST['id_pos'] === 'end') {
			$dbName = clean_input($_POST['db_name']) . "_" . $mainDmnId;
		} else { // position of id is not defined - should normally never occurs but... - just fallback to dbname without id
			$dbName = clean_input($_POST['db_name']);
		}
	} else {
		$dbName = clean_input($_POST['db_name']);
	}

	if (strlen($dbName) > 64) {
		set_page_message(tr('Database name is too long.'), 'error');
		return;
	}

	if ($dbName == 'test' || client_isDatabase($dbName)) {
		set_page_message(tr('Database name is unavailable.'), 'error');
		return;
	}

	// Are wildcards used?
	if (preg_match("/[%|\?]+/", $dbName)) {
		set_page_message(tr('Wildcards such as %% and are not allowed.'), 'error');
		return;
	}

	$responses = iMSCP_Events_Aggregator::getInstance()->dispatch(
		iMSCP_Events::onBeforeAddSqlDb, array('dbName' => $dbName)
	);

	if (!$responses->isStopped()) {
		// Here we cannot start transaction before the CREATE DATABABSE statement because its cause an implicit commit
		$dbCreated = false;

		try {
			execute_query('CREATE DATABASE IF NOT EXISTS ' . quoteIdentifier($dbName));

			$dbCreated = true;

			exec_query('INSERT INTO sql_database (domain_id, sqld_name) VALUES (?, ?)', array($mainDmnId, $dbName));

			set_page_message(tr('SQL database successfully added.'), 'success');
			write_log($_SESSION['user_logged'] . ": added new SQL database: " . tohtml($dbName), E_USER_NOTICE);
		} catch (iMSCP_Exception_Database $e) {
			if ($dbCreated) {
				execute_query('DROP DATABASE IF EXISTS ' . quoteIdentifier($dbName));
			}

			throw $e;
		}

		iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterAddSqlDb, array('dbName' => $dbName));
	}

	redirectTo('sql_manage.php');
}

/**
 * Check SQL permissions
 *
 * @return void
 */
function client_checkSqlDbLimit()
{
	$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
	$mainDmnId = $mainDmnProps['domain_id'];
	$sqlDatabaseLimit = $mainDmnProps['domain_sqld_limit'];

	list($nbSqlDb) = get_domain_running_sql_acc_cnt($mainDmnId);

	if ($sqlDatabaseLimit != 0 && $nbSqlDb >= $sqlDatabaseLimit) {
		set_page_message(tr('SQL database limit reached.'), 'error');
		redirectTo('sql_manage.php');
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('sql') or showBadRequestErrorPage();
client_checkSqlDbLimit();

client_addSqlDb($_SESSION['user_id']);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/sql_database_add.tpl',
		'page_message' => 'layout',
		'mysql_prefix_no' => 'page',
		'mysql_prefix_yes' => 'page',
		'mysql_prefix_infront' => 'page',
		'mysql_prefix_behind' => 'page',
		'mysql_prefix_all' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Databases / Add SQL Database'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_DATABASE' => tr('Database'),
		'TR_DB_NAME' => tr('Database name'),
		'TR_USE_DMN_ID' => tr('Database prefix/suffix'),
		'TR_START_ID_POS' => tr("In front"),
		'TR_END_ID_POS' => tr("Behind"),
		'TR_ADD' => tr('Add')
	)
);

client_generatePage($tpl);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
