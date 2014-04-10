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
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/**********************************************************************
 * Script functions
 *
 */

/**
 * Updates htaccess user.
 *
 * @param int $dmn_id Domain unique identifier
 * @param int $uuser_id Htaccess user unique identifier
 * @return
 */
function client_updateHtaccessUser(&$dmn_id, &$uuser_id)
{
	if (isset($_POST['uaction']) && $_POST['uaction'] == 'modify_user') {
		// we have to add the user
		if (isset($_POST['pass']) && isset($_POST['pass_rep'])) {
			if (!checkPasswordSyntax($_POST['pass'])) {
				return;
			}

			if ($_POST['pass'] !== $_POST['pass_rep']) {
				set_page_message(tr("Passwords do not match."), 'error');
				return;
			}

			$nadmin_password = cryptPasswordWithSalt($_POST['pass'], generateRandomSalt(true));

			$change_status = 'tochange';

			$query = "
				UPDATE
					`htaccess_users`
				SET
					`upass` = ?, `status` = ?
				WHERE
					`dmn_id` = ?
				AND
					`id` = ?
			";
			exec_query($query, array($nadmin_password, $change_status, $dmn_id, $uuser_id,));

			send_request();

			$query = "
				SELECT
					`uname`
				FROM
					`htaccess_users`
				WHERE
					`dmn_id` = ?
				AND
					`id` = ?
			";
			$rs = exec_query($query, array($dmn_id, $uuser_id));
			$uname = $rs->fields['uname'];

			$admin_login = $_SESSION['user_logged'];
			write_log("$admin_login: updated htaccess user ID: $uname", E_USER_NOTICE);
			redirectTo('protected_user_manage.php');
		}
	} else {
		return;
	}
}

/**
 * @param $get_input
 * @return int
 */
function check_get(&$get_input)
{
	if (!is_numeric($get_input)) {
		return 0;
	} else {
		return 1;
	}
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('protected_areas') or showBadRequestErrorPage();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/puser_edit.tpl',
		'page_message' => 'layout',
		'usr_msg' => 'page',
		'grp_msg' => 'page',
		'pusres' => 'page',
		'pgroups' => 'page'));

$dmn_id = get_user_domain_id($_SESSION['user_id']);

if (isset($_GET['uname']) && $_GET['uname'] !== '' && is_numeric($_GET['uname'])) {
	$uuser_id = $_GET['uname'];

	$query = "
		SELECT
			`uname`
		FROM
			`htaccess_users`
		WHERE
			`dmn_id` = ?
		AND
			`id` = ?
	";
	$rs = exec_query($query, array((int)$dmn_id, (int)$uuser_id));

	if ($rs->rowCount() == 0) {
		redirectTo('protected_user_manage.php');
	} else {
		$tpl->assign(
			array(
				'UNAME' => tohtml($rs->fields['uname']),
				'UID' => $uuser_id));
	}
} elseif (isset($_POST['nadmin_name']) && !empty($_POST['nadmin_name']) && is_numeric($_POST['nadmin_name'])) {
	$uuser_id = clean_input($_POST['nadmin_name']);

	$query = "
		SELECT
			`uname`
		FROM
			`htaccess_users`
		WHERE
			`dmn_id` = ?
		AND
			`id` = ?
	";
	$rs = exec_query($query, array((int)$dmn_id, (int)$uuser_id));

	if ($rs->rowCount() == 0) {
		redirectTo('protected_user_manage.php');
	} else {
		$tpl->assign(
			array(
				'UNAME' => tohtml($rs->fields['uname']),
				'UID' => $uuser_id));

		client_updateHtaccessUser($dmn_id, $uuser_id);
	}
} else {
	redirectTo('protected_user_manage.php');
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Webtools / Protected Areas / Manage Users and Groups / Edit User'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_HTACCESS_USER' => tr('Htaccess user'),
		'TR_USERS' => tr('User'),
		'TR_USERNAME' => tr('Username'),
		'TR_PASSWORD' => tr('Password'),
		'TR_PASSWORD_REPEAT' => tr('Repeat password'),
		'TR_UPDATE' => tr('Update'),
		'TR_CANCEL' => tr('Cancel')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
