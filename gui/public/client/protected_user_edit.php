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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010-2011 by i-MSCP | http://i-mscp.net
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('protected_areas')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', $cfg->CLIENT_TEMPLATE_PATH . '/../shared/layouts/ui.tpl');
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/puser_edit.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('usr_msg', 'page');
$tpl->define_dynamic('grp_msg', 'page');
$tpl->define_dynamic('pusres', 'page');
$tpl->define_dynamic('pgroups', 'page');

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Client / Webtools / Protected areas / Edit user'),
		 'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()));

/**
 * @param $tpl
 * @param $dmn_id
 * @param $uuser_id
 * @return
 */
function pedit_user($tpl, &$dmn_id, &$uuser_id) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['uaction']) && $_POST['uaction'] == 'modify_user') {
		// we have to add the user
		if (isset($_POST['pass']) && isset($_POST['pass_rep'])) {
			if (!chk_password($_POST['pass'])) {
				if ($cfg->PASSWD_STRONG) {
					set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), $cfg->PASSWD_CHARS), 'error');
				} else {
					set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), $cfg->PASSWD_CHARS), 'error');
				}

				return;
			}

			if ($_POST['pass'] !== $_POST['pass_rep']) {
				set_page_message(tr('Passwords do not match.'), 'error');
				return;
			}

			$nadmin_password = crypt_user_pass_with_salt($_POST['pass']);

			$change_status = $cfg->ITEM_CHANGE_STATUS;

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
			write_log("$admin_login: modify user ID (protected areas): $uname", E_USER_NOTICE);
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
function check_get(&$get_input) {
	if (!is_numeric($get_input)) {
		return 0;
	} else {
		return 1;
	}
}

generateNavigation($tpl);

$dmn_id = get_user_domain_id($_SESSION['user_id']);

if (isset($_GET['uname']) && $_GET['uname'] !== '' && is_numeric($_GET['uname'])) {
	$uuser_id = $_GET['uname'];

/**
 * @todo use DB prepared statements
 */
	$query = "
		SELECT
			`uname`
		FROM
			`htaccess_users`
		WHERE
			`dmn_id` = '$dmn_id'
		AND
			`id` = '$uuser_id'
	";
	$rs = execute_query($query);

	if ($rs->recordCount() == 0) {
		redirectTo('protected_user_manage.php');
	} else {
		$tpl->assign(
			array(
				'UNAME'	=> tohtml($rs->fields['uname']),
				'UID'	=> $uuser_id));
	}
} elseif (isset($_POST['nadmin_name']) && !empty($_POST['nadmin_name'])
	&& is_numeric($_POST['nadmin_name'])) {
	$uuser_id = clean_input($_POST['nadmin_name']);

/**
 * @todo use DB prepared statements
 */
	$query = "
		SELECT
			`uname`
		FROM
			`htaccess_users`
		WHERE
			`dmn_id` = '$dmn_id'
		AND
			`id` = '$uuser_id'
	";
	$rs = execute_query($query);

	if ($rs->recordCount() == 0) {
		redirectTo('protected_user_manage.php');
	} else {
		$tpl->assign(
			array(
				'UNAME'	=> tohtml($rs->fields['uname']),
				'UID'	=> $uuser_id));

		pedit_user($tpl, $dmn_id, $uuser_id);
	}
} else {
	redirectTo('protected_user_manage.php');
}

$tpl->assign(
	array(
		 'TR_HTACCESS' => tr('Protected areas'),
		 'TR_ACTION' => tr('Action'),
		 'TR_EDIT_USER' => tr('Edit user'),
		 'TR_USERS' => tr('User'),
		 'TR_USERNAME' => tr('Username'),
		 'TR_ADD_USER' => tr('Add user'),
		 'TR_GROUPNAME' => tr('Group name'),
		 'TR_GROUP_MEMBERS' => tr('Group members'),
		 'TR_ADD_GROUP' => tr('Add group'),
		 'TR_EDIT' => tr('Edit'),
		 'TR_GROUP' => tr('Group'),
		 'TR_DELETE' => tr('Delete'),
		 'TR_UPDATE' => tr('Modify'),
		 'TR_PASSWORD' => tr('Password'),
		 'TR_PASSWORD_REPEAT' => tr('Repeat password'),
		 'TR_HTACCESS_USER' => tr('Manage users and groups'),
		 'TR_CANCEL' => tr('Cancel')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
