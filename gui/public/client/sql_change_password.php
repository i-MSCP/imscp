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
$tpl->define_dynamic('page', 'client/sql_change_password.tpl');
$tpl->define_dynamic('page_message', 'layout');

if (isset($_GET['id'])) {
	$db_user_id = $_GET['id'];
} else if (isset($_POST['id'])) {
	$db_user_id = $_POST['id'];
} else {
	redirectTo('sql_manage.php');
}

/**
 * @param $db_user_id
 * @param $db_user_name
 * @return
 */
function change_sql_user_pass($db_user_id, $db_user_name)
{
	if (!isset($_POST['uaction'])) {
		return;
	}

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeEditSqlUser, array('sqlUserId' => $db_user_id));

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');


	if ($_POST['pass'] === '' && $_POST['pass_rep'] === '') {
		set_page_message(tr('Please type user password.'), 'error');
		return;
	}

	if ($_POST['pass'] !== $_POST['pass_rep']) {
		set_page_message(tr('Entered passwords do not match.'), 'error');
		return;
	}

	if (strlen($_POST['pass']) > $cfg->MAX_SQL_PASS_LENGTH) {
		set_page_message(tr('Too long user password.'), 'error');
		return;
	}

	if (isset($_POST['pass']) && !preg_match('/^[[:alnum:]:!\*\+\#_.-]+$/', $_POST['pass'])) {
		set_page_message(tr('Don\'t use special chars like "@, $, %..." in the password.'), 'error');
		return;
	}

	if (!chk_password($_POST['pass'])) {
		if ($cfg->PASSWD_STRONG) {
			set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), $cfg->PASSWD_CHARS), 'error');
		} else {
			set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), $cfg->PASSWD_CHARS), 'error');
		}
		return;
	}

	$user_pass = $_POST['pass'];

	try {
		// Update SQL user password in the mysql system tables;

		$passwordUpdated = false;

		exec_query("SET PASSWORD FOR ?@'%' = PASSWORD(?)", array($db_user_name, $user_pass));
		exec_query("SET PASSWORD FOR ?@'localhost' = PASSWORD(?)", array($db_user_name, $user_pass));

		$passwordUpdated = true;

		iMSCP_Database::getInstance()->beginTransaction();

		$stmt = exec_query('SELECT `sqlu_pass` FROM `sql_user` WHERE `sqlu_name` = ? LIMIT 1', $db_user_name);

		if(!$stmt->rowCount()) {
			throw new iMSCP_Exception('SQL user to update no found.');
		}

		$oldPassword = $stmt->fields['sqlu_pass'];

		// Update user password in the i-MSCP sql_user table;

		$query = "UPDATE `sql_user` SET `sqlu_pass` = ? WHERE `sqlu_name` = ?";
		exec_query($query, array($user_pass, $db_user_name));

		iMSCP_Database::getInstance()->commit();

		iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterEditSqlUser, array('sqlUserId' => $db_user_id));

		set_page_message(tr('SQL user password successfully updated.'), 'success');
		write_log(sprintf("%s updated password for the '%s' SQL user.", $_SESSION['user_logged'], tohtml($db_user_name)), E_USER_NOTICE);
	} catch (iMSCP_Exception $e) {
		if($passwordUpdated) {
			iMSCP_Database::getInstance()->rollBack();

			if(isset($oldPassword)) {
				// Our transaction failed so we try to rollback by restoring old password
				try { // We don't care about result here - An exception is throw in case the user do not exists
					exec_query("SET PASSWORD FOR ?@'%' = PASSWORD(?)", array($db_user_name, $oldPassword));
					exec_query("SET PASSWORD FOR ?@'localhost' = PASSWORD(?)", array($db_user_name, $oldPassword));
				} catch(iMSCP_Exception_Database $e) {}
			}
		}

		set_page_message(tr('System was unable to update the SQL user password.'), 'error');
		write_log(sprintf("System was unable to update password for the '%s' SQL user. Message was: %s", tohtml($db_user_name), $e->getMessage()), E_USER_ERROR);
	}

	redirectTo('sql_manage.php');
}

/**
 * @param $tpl
 * @param $db_user_id
 * @return
 */
function gen_page_data($tpl, $db_user_id)
{
	$query = "SELECT `sqlu_name` FROM `sql_user` WHERE `sqlu_id` = ?";
	$rs = exec_query($query, $db_user_id);

	$tpl->assign(
		array(
			'USER_NAME' => tohtml($rs->fields['sqlu_name']),
			'ID' => $db_user_id));

	return $rs->fields['sqlu_name'];
}

if (isset($_SESSION['sql_support']) && $_SESSION['sql_support'] == "no") {
	redirectTo('index.php');
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Client/Change SQL User Password'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

$db_user_name = gen_page_data($tpl, $db_user_id);

if(!check_user_sql_perms($db_user_id))
{
    set_page_message(tr('User does not exist or you do not have permission to access this interface.'), 'error');
    redirectTo('sql_manage.php');
}

check_user_sql_perms($db_user_id);
change_sql_user_pass($db_user_id, $db_user_name);
generateNavigation($tpl);

$tpl->assign(
	array(
		 'TR_CHANGE_SQL_USER_PASSWORD' => tr('Change SQL user password'),
		 'TR_USER_NAME' => tr('User name'),
		 'TR_PASS' => tr('Password'),
		 'TR_PASS_REP' => tr('Repeat password'),
		 'TR_CHANGE' => tr('Change'),
		 // The entries below are for Demo versions only
		 'PASSWORD_DISABLED' => tr('Password change is deactivated!'),
		 'DEMO_VERSION' => tr('Demo Version!')));

generatePageMessage($tpl);
$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
