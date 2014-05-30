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
 * Check SQL permissions
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $databaseId Database unique identifier
 */
function client_checkSqlUserPermissions($tpl, $databaseId)
{
	$domainProperties = get_domain_default_props($_SESSION['user_id']);
	$domainSqlUsersLimit = $domainProperties['domain_sqlu_limit'];
	$limits = get_domain_running_sql_acc_cnt($domainProperties['domain_id']);

	if ($domainSqlUsersLimit != 0 && $limits[1] >= $domainSqlUsersLimit) {
		$tpl->assign('CREATE_SQLUSER', '');
	}

	$stmt = exec_query(
		'
			SELECT
				domain_id
			FROM
				domain
			INNER JOIN
				sql_database USING(domain_id)
			WHERE
				domain_id = ?
			AND
				sqld_id = ?
			LIMIT 1
		',
		array($domainProperties['domain_id'], $databaseId)
	);

	if (!$stmt->rowCount()) {
		showBadRequestErrorPage();
	}
}

/**
 * Get list of SQL user which belong to the given database
 *
 * @param int $databaseId Database unique identifier
 * @return array
 */
function client_getSqlUserList($databaseId)
{
	$stmt = exec_query('SELECT sqlu_name FROM sql_user WHERE sqld_id = ?', $databaseId);

	$userList = array();

	while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
		$userList[] = $row['sqlu_name'];
	}

	return $userList;
}

/**
 * Get SQL user list
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $customeId Customer id
 * @param int $databaseId Database id
 * @return bool
 */
function client_generateSqlUserList($tpl, $customeId, $databaseId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$domainId = get_user_domain_id($customeId);

	// Let's select all SQL users of the current domain except the users of the current database
	$stmt = exec_query(
		'
			SELECT
				t1.sqlu_name, t1.sqlu_host, t1.sqlu_id
			FROM
				sql_user AS t1, sql_database AS t2
			WHERE
				t1.sqld_id = t2.sqld_id
			AND
				t2.domain_id = ?
			AND
				t1.sqld_id <> ?
			ORDER BY
				t1.sqlu_name
		',
		array($domainId, $databaseId)
	);

	$firstPassed = true;
	$sqlUserFound = false;
	$prevSeenName = '';

	$userList = client_getSqlUserList($databaseId);

	while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
		// Checks if it's the first element of the combo box and set it as selected
		if ($firstPassed) {
			$select = $cfg->HTML_SELECTED;
			$firstPassed = false;
		} else {
			$select = '';
		}

		// 1. Compares the sqluser name with the record before (Is set as '' at the first time, see above)
		// 2. Compares the sqluser name with the userlist of the current database
		if ($prevSeenName != $row['sqlu_name'] && !in_array($row['sqlu_name'], $userList)) {
			$sqlUserFound = true;
			$prevSeenName = $row['sqlu_name'];

			$tpl->assign(
				array(
					'SQLUSER_ID' => $row['sqlu_id'],
					'SQLUSER_SELECTED' => $select,
					'SQLUSER_NAME' => tohtml($row['sqlu_name']),
					'SQLUSER_HOST' => tohtml(decode_idna($row['sqlu_host'])),
				)
			);

			$tpl->parse('SQLUSER_LIST', '.sqluser_list');
		}
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
 * Does the given SQL user already exists?
 *
 * @param string $sqlUser SQL user name
 * @param string $sqlUserHost SQL user host
 * @return bool TRUE if the given sql user already exists, FALSE otherwise
 */
function client_isSqlUser($sqlUser, $sqlUserHost)
{
	$stmt = exec_query('SELECT User FROM mysql.user WHERE User = ? AND Host = ?', array($sqlUser, $sqlUserHost));

	return (bool)($stmt->rowCount());
}

/**
 * Add SQL user for the given database
 *
 * @throws Exception
 * @throws iMSCP_Exception_Database
 * @param int $customerId
 * @param int $databaseId
 * @return void
 */
function client_addSqlUser($customerId, $databaseId)
{
	if (!empty($_POST)) {
		if (!isset($_POST['uaction'])) {
			showBadRequestErrorPage();
		}

		$domainId = get_user_domain_id($customerId);

		if (!isset($_POST['Add_Exist'])) { // Add new SQL user as specified in input data
			if (empty($_POST['user_name'])) {
				set_page_message(tr('Please enter an username.'), 'error');
				return;
			}

			if (empty($_POST['user_host'])) {
				set_page_message(tr('Please enter an SQL user host.'), 'error');
				return;
			}

			$sqlUserHost = encode_idna(clean_input($_POST['user_host']));

			if(
				$sqlUserHost != '%' && $sqlUserHost != 'localhost' &&
				!iMSCP_Validate::getInstance()->hostname(
					$sqlUserHost, array('allow' => Zend_Validate_Hostname::ALLOW_DNS|Zend_Validate_Hostname::ALLOW_IP)
				)
			) {
				set_page_message(
					tr('Invalid SQL user host: %s', iMSCP_Validate::getInstance()->getLastValidationMessages()),
					'error'
				);

				return;
			}

			if (empty($_POST['pass'])) {
				set_page_message(tr('Please enter a password.'), 'error');
				return;
			}

			if (!isset($_POST['pass_rep']) || $_POST['pass'] !== $_POST['pass_rep']) {
				set_page_message(tr("Passwords do not match."), 'error');
				return;
			}

			if (strlen($_POST['pass']) > 32) {
				set_page_message(tr('Password is too long.'), 'error');
				return;
			}

			if (!checkPasswordSyntax($_POST['pass'], '/[^\x21-\x7e]/')) {
				set_page_message(
					tr(
						'Only printable characters from the ASCII table (not extended), excepted the space, are allowed.'
					),
					'error'
				);
				return;
			}

			$sqlUserPassword = $_POST['pass'];

			// we'll use domain_id in the name of the database;
			if (
				isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] == 'on' && isset($_POST['id_pos'])
				&& $_POST['id_pos'] == 'start'
			) {
				$sqlUser = $domainId . '_' . clean_input($_POST['user_name']);
			} elseif (
				isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] == 'on' && isset($_POST['id_pos']) &&
				$_POST['id_pos'] == 'end'
			) {
				$sqlUser = clean_input($_POST['user_name']) . '_' . $domainId;
			} else {
				$sqlUser = clean_input($_POST['user_name']);
			}
		} else { // Using existing SQL user as specified in input data
			$stmt = exec_query(
				'SELECT sqlu_name, sqlu_host, sqlu_pass FROM sql_user WHERE sqlu_id = ?', intval($_POST['sqluser_id'])
			);

			if (!$stmt->rowCount()) {
				showBadRequestErrorPage();
			}

			$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

			$sqlUser = $row['sqlu_name'];
			$sqlUserHost = $row['sqlu_host'];
			$sqlUserPassword = $row['sqlu_pass'];
		}

		# Check for username length
		if (strlen($sqlUser) > 16) {
			set_page_message(tr('Username is too long.'), 'error');
			return;
		}

		// Check for unallowed character in username
		if (preg_match('/[%|\?]+/', $sqlUser)) {
			set_page_message(tr('Wildcards such as %% and ? are not allowed in username.'), 'error');
			return;
		}

		// Ensure that SQL user doesn't already exists
		if (!isset($_POST['Add_Exist']) && client_isSqlUser($sqlUser, $sqlUserHost)) {
			set_page_message(tr('SQL user %s already exits.', $sqlUser . '@' . decode_idna($sqlUserHost)), 'error');
			return;
		}

		# Retrieve database to which SQL user should be assigned
		$stmt = exec_query(
			'SELECT sqld_name FROM sql_database WHERE sqld_id = ? AND domain_id = ?', array($databaseId, $domainId)
		);

		if (!$stmt->rowCount()) {
			showBadRequestErrorPage();
		} else {
			$dbName = $stmt->fields['sqld_name'];
			$dbName = preg_replace('/([_%\?\*])/', '\\\$1', $dbName);

			$sqlUserCreated = false;

			iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeAddSqlUser);

			// Here we cannot use transaction because the GRANT statement cause an implicit commit
			// We execute the GRANT statements first to let the i-MSCP database in clean state if one of them fails.
			try {
				exec_query(
					'GRANT ALL PRIVILEGES ON ' . quoteIdentifier($dbName) . '.* TO ?@? IDENTIFIED BY ?',
					array($sqlUser, $sqlUserHost, $sqlUserPassword)
				);

				$sqlUserCreated = true;

				exec_query(
					'INSERT INTO sql_user (sqld_id, sqlu_name, sqlu_host, sqlu_pass) VALUES (?, ?, ?,?)',
					array($databaseId, $sqlUser, $sqlUserHost, $sqlUserPassword)
				);
			} catch (iMSCP_Exception_Database $e) {
				if ($sqlUserCreated) {
					try { // We don't care about result here - An exception is throw in case the user do not exists
						exec_query('DROP USER ?@?', $sqlUser, $sqlUserHost);
					} catch (iMSCP_Exception_Database $x) {

					}
				}

				throw $e;
			}

			iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterAddSqlUser);

			set_page_message(tr('SQL user successfully added.'), 'success');
			write_log(sprintf("%s added new SQL user: %s", $_SESSION['user_logged'], tohtml($sqlUser)), E_USER_NOTICE);
		}

		redirectTo('sql_manage.php');
	}
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $databaseId
 * @return void
 */
function client_generatePage($tpl, $databaseId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($cfg['MYSQL_PREFIX'] == 'yes') {
		$tpl->assign('MYSQL_PREFIX_YES', '');

		if ($cfg['MYSQL_PREFIX_TYPE'] == 'behind') {
			$tpl->assign('MYSQL_PREFIX_INFRONT', '');
			$tpl->parse('MYSQL_PREFIX_BEHIND', 'mysql_prefix_behind');
			$tpl->assign('MYSQL_PREFIX_ALL', '');
		} else {
			$tpl->parse('MYSQL_PREFIX_INFRONT', 'mysql_prefix_infront');
			$tpl->assign(
				array(
					'MYSQL_PREFIX_BEHIND' => '',
					'MYSQL_PREFIX_ALL' => ''
				)
			);
		}
	} else {
		$tpl->assign(
			array(
				'MYSQL_PREFIX_NO' => '',
				'MYSQL_PREFIX_INFRONT' => '',
				'MYSQL_PREFIX_BEHIND' => ''
			)
		);
		$tpl->parse('MYSQL_PREFIX_ALL', 'mysql_prefix_all');
	}

	if (isset($_POST['uaction']) && $_POST['uaction'] == 'add_user') {
		$htmlChecked = $cfg['HTML_CHECKED'];

		$tpl->assign(
			array(
				'USER_NAME' => (isset($_POST['user_name'])) ? tohtml($_POST['user_name'], true) : '',
				'USER_HOST' => (isset($_POST['user_host'])) ? tohtml($_POST['user_host'], true) : '',
				'USE_DMN_ID' => (isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on') ? $htmlChecked : '',
				'START_ID_POS_SELECTED' => (isset($_POST['id_pos']) && $_POST['id_pos'] !== 'end') ? $htmlChecked : '',
				'END_ID_POS_SELECTED' => (isset($_POST['id_pos']) && $_POST['id_pos'] === 'end') ? $$htmlChecked : ''
			)
		);
	} else {
		$tpl->assign(
			array(
				'USER_NAME' => '',
				'USER_HOST' => tohtml(
					($cfg['DATABASE_USER_HOST'] == '127.0.0.1') ? 'localhost' : (decode_idna($cfg['DATABASE_USER_HOST']))
				),
				'USE_DMN_ID' => '',
				'START_ID_POS_SELECTED' => '',
				'END_ID_POS_SELECTED' => $cfg['HTML_CHECKED']
			)
		);
	}

	$tpl->assign('ID', $databaseId);
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('sql') or showBadRequestErrorPage();

if (!isset($_REQUEST['id'])) {
	showBadRequestErrorPage();
	exit;
}

$databaseId = intval($_REQUEST['id']);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/sql_user_add.tpl',
		'page_message' => 'layout',
		'mysql_prefix_no' => 'page',
		'mysql_prefix_yes' => 'page',
		'mysql_prefix_infront' => 'page',
		'mysql_prefix_behind' => 'page',
		'mysql_prefix_all' => 'page',
		'sqluser_list' => 'page',
		'show_sqluser_list' => 'page',
		'create_sqluser' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Databases / Overview / Add SQL User'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_ADD_SQL_USER' => tr('Add SQL user'),
		'TR_USER_NAME' => tr('SQL user name'),
		'TR_USER_HOST' => tr('SQL user host'),
		'TR_USER_HOST_TIP' => tr("This is the host from which this SQL user must be allowed to connect to the SQL server. Enter the % character to allow this SQL user to connect from any host."),
		'TR_USE_DMN_ID' => tr('SQL user prefix/suffix'),
		'TR_START_ID_POS' => tr('In front'),
		'TR_END_ID_POS' => tr('Behind'),
		'TR_ADD' => tr('Add'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_ADD_EXIST' => tr('Assign'),
		'TR_PASS' => tr('Password'),
		'TR_PASS_REP' => tr('Repeat password'),
		'TR_SQL_USER_NAME' => tr('SQL users'),
		'TR_ASSIGN_EXISTING_SQL_USER' => tr('Assign existing SQL user'),
		'TR_NEW_SQL_USER_DATA' => tr('New SQL user data')
	)
);

client_checkSqlUserPermissions($tpl, $databaseId);
client_generateSqlUserList($tpl, $_SESSION['user_id'], $databaseId);
client_generatePage($tpl, $databaseId);
client_addSqlUser($_SESSION['user_id'], $databaseId);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
