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
 * @param int $sqlUserId Sql user id
 * @return array
 */
function client_generatePage($tpl, $sqlUserId)
{
	$stmt = exec_query('SELECT sqlu_name, sqlu_host, sqlu_pass FROM sql_user WHERE sqlu_id = ?', $sqlUserId);

	if($stmt->rowCount()) {
		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

		$sqlUserName = $row['sqlu_name'] . '@' . decode_idna($row['sqlu_host']);

		$tpl->assign(
			array(
				'USER_NAME' => tohtml($sqlUserName),
				'ID' => $sqlUserId
			)
		);

		return array($row['sqlu_name'], $row['sqlu_host'], $row['sqlu_pass']);
	}

	showBadRequestErrorPage();
	exit;
}

/**
 * Update SQL user password
 *
 * @param int $sqlUserId Sql user id
 * @param string $sqlUserName Sql user name
 * @poaram string Sql user host
 * @param string $oldSqlPassword Sql user old password
 * @return
 */
function client_updateSqlUserPassword($sqlUserId, $sqlUserName, $sqlUserHost, $oldSqlPassword)
{
	if (!isset($_POST['uaction'])) {
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

	if (!preg_match('/^[[:alnum:]:!*+#_.-]+$/', $_POST['pass'])) {
		set_page_message(tr("Please don't use special chars such as '@, $, %...' in password."), 'error');
		return;
	}

	if (!checkPasswordSyntax($_POST['pass'])) {
		return;
	}

	$password = $_POST['pass'];
	$passwordUpdated = false;

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditSqlUser, array('sqlUserId' => $sqlUserId));

	try {
		// Update SQL user password in the mysql system tables;

		exec_query("SET PASSWORD FOR ?@? = PASSWORD(?)", array($sqlUserName, $sqlUserHost, $password));

		$passwordUpdated = true;

		// Update user password in the i-MSCP sql_user table;

		exec_query(
			'UPDATE sql_user SET sqlu_pass = ? WHERE sqlu_name = ? AND sqlu_host = ?',
			array($password, $sqlUserName, $sqlUserHost)
		);

		set_page_message(tr('SQL user password successfully updated.'), 'success');
		write_log(
			sprintf("%s updated password for the '%s' SQL user.", $_SESSION['user_logged'], tohtml($sqlUserName)),
			E_USER_NOTICE
		);
	} catch (iMSCP_Exception_Database $e) {
		if($passwordUpdated) {
			try {
				exec_query("SET PASSWORD FOR ?@? = PASSWORD(?)", array($sqlUserName, $sqlUserHost, $oldSqlPassword));
			} catch(iMSCP_Exception_Database $f) { }
		}

		throw $e;
	}

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditSqlUser, array('sqlUserId' => $sqlUserId));

	redirectTo('sql_manage.php');
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('sql') or showBadRequestErrorPage();

if (isset($_REQUEST['id'])) {
	$sqlUserId = intval($_REQUEST['id']);

	if(!check_user_sql_perms($sqlUserId))  {
		showBadRequestErrorPage();
	}
} else {
	showBadRequestErrorPage();
	exit;
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/sql_change_password.tpl',
		'page_message' => 'layout'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Databases / Overview / Update SQL User Password'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_USER_NAME' => tr('User name'),
		'TR_PASS' => tr('Password'),
		'TR_PASS_REP' => tr('Repeat password'),
		'TR_CHANGE' => tr('Update')
	)
);

list($sqlUserName, $sqlUserhost, $oldSqlPassword) = client_generatePage($tpl, $sqlUserId);

client_updateSqlUserPassword($sqlUserId, $sqlUserName, $sqlUserhost, $oldSqlPassword);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
