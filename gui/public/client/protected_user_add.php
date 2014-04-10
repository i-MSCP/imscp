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

/*************************************************************
 * Script functions
 */

/**
 * Add Htaccess user.
 *
 * @param int $domainId Domain unique identifier
 * @return
 */
function client_addHtaccessUser($domainId)
{
	if (isset($_POST['uaction']) && $_POST['uaction'] == 'add_user') {
		// we have to add the user
		if (isset($_POST['username']) && isset($_POST['pass']) && isset($_POST['pass_rep'])) {
			if (!validates_username($_POST['username'])) {
				set_page_message(tr('Wrong username.'), 'error');
				return;
			}

			if (!checkPasswordSyntax($_POST['pass'])) {
				return;
			}

			if ($_POST['pass'] !== $_POST['pass_rep']) {
				set_page_message(tr("Passwords do not match."), 'error');
				return;
			}

			$status = 'toadd';
			$uname = clean_input($_POST['username']);
			$upass = cryptPasswordWithSalt($_POST['pass'], generateRandomSalt(true));

			$query = "
				SELECT
					`id`
				FROM
					`htaccess_users`
				WHERE
					`uname` = ?
				AND
					`dmn_id` = ?
			";
			$rs = exec_query($query, array($uname, $domainId));

			if ($rs->rowCount() == 0) {
				$query = "
					INSERT INTO `htaccess_users` (
					    `dmn_id`, `uname`, `upass`, `status`
					) VALUES (
					    ?, ?, ?, ?
					)
				";
				exec_query($query, array($domainId, $uname, $upass, $status));

				send_request();

				set_page_message(tr('Htaccess user successfully scheduled for addition.'), 'success');

				$admin_login = $_SESSION['user_logged'];
				write_log("$admin_login: added new htaccess user: $uname", E_USER_NOTICE);
				redirectTo('protected_user_manage.php');
			} else {
				set_page_message(tr('This htaccess user already exist.'), 'error');
				return;
			}
		}
	} else {
		return;
	}
}

/******************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('protected_areas') or showBadRequestErrorPage();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/puser_uadd.tpl',
		'page_message' => 'layout',
		'usr_msg' => 'page',
		'grp_msg' => 'page',
		'pusres' => 'page',
		'pgroups' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Webtools / Protected Areas / Manage Users and Groups / Add User'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_HTACCESS_USER' => tr('Htaccess user'),
		'TR_USERS' => tr('User'),
		'TR_USERNAME' => tr('Username'),
		'TR_PASSWORD' => tr('Password'),
		'TR_PASSWORD_REPEAT' => tr('Repeat password'),
		'TR_ADD_USER' => tr('Add'),
		'TR_CANCEL' => tr('Cancel')));

generateNavigation($tpl);
client_addHtaccessUser(get_user_domain_id($_SESSION['user_id']));
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
