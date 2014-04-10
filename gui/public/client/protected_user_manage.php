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

/************************************************************************************
 * Script functions
 */

/**
 * Generates user action.
 *
 * @access private
 * @param string $status User status
 * @return array
 */
function _client_generateUserAction($status)
{
	if ($status == 'ok') {
		return array(tr('Delete'), "action_delete('protected_user_delete.php?uname={USER_ID}', '{UNAME}')", tr('Edit'), "protected_user_edit.php?uname={USER_ID}");
	} else {
		return array(tr('N/A'), '', tr('N/A'), '#');
	}
}

/**
 * Generates group actions.
 *
 * @access private
 * @param string $status Group status
 * @param string $group Group name
 * @return array
 */
function _client_generateHtgroupAction($status, $group)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($status == 'ok' && $group != $cfg->WEBSTATS_GROUP_AUTH) {
		return array(tr('Delete'), "action_delete('protected_group_delete.php?gname={GROUP_ID}', '{GNAME}')");
	} else {
		return array(tr('N/A'), '');
	}
}

/**
 * Generates users list.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId Domain unique identifier
 * @return void
 */
function client_generateUsersList($tpl, $domainId)
{
	$query = "SELECT * FROM `htaccess_users` WHERE `dmn_id` = ? ORDER BY `dmn_id` DESC";
	$stmt = exec_query($query, $domainId);

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				 'USERS_BLOCK' => '',
				 'USERS_MESSAGE' => tr('No user found.')));
	} else {
		$tpl->assign('USERS_MESSAGE_BLOCK', '');

		while (!$stmt->EOF) {
			list(
				$userDeleteTranslation, $userDeleteJsScript, $userEditTranslation,
				$htuserEditJsScript
			) = _client_generateUserAction($stmt->fields['status']);

			$tpl->assign(
				array(
					 'UNAME' => tohtml($stmt->fields['uname']),
					 'USTATUS' => translate_dmn_status($stmt->fields['status']),
					 'USER_ID' => $stmt->fields['id'],
					 'USER_DELETE' => $userDeleteTranslation,
					 'USER_DELETE_SCRIPT' => $userDeleteJsScript,
					 'USER_EDIT' => $userEditTranslation,
					 'USER_EDIT_SCRIPT' => $htuserEditJsScript));

			$tpl->parse('USER_BLOCK', '.user_block');
			$stmt->moveNext();
		}
	}
}

/**
 * Generates groups list.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId Domain unique identifier
 * @return void
 */
function client_genetateGroupsList($tpl, $domainId)
{
	$query = "SELECT * FROM `htaccess_groups` WHERE `dmn_id` = ? ORDER BY `dmn_id` DESC";
	$stmt = exec_query($query, $domainId);

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
			  'GROUPS_MESSAGE' => tr('No group found.'),
			  'GROUPS_BLOCK' => ''));
	} else {
		$tpl->assign('GROUPS_MESSAGE_BLOCK', '');

		foreach($stmt->fetchAll() as $group) {
			list(
				$groupDeleteTranslation, $groupDeleteJsScript
			) = _client_generateHtgroupAction($group['status'], $group['ugroup']);

			$tpl->assign(
				array(
					 'GNAME' => tohtml($group['ugroup']),
					 'GSTATUS' => translate_dmn_status($group['status']),
					 'GROUP_ID' => $group['id'],
					 'GROUP_DELETE' => $groupDeleteTranslation,
					 'GROUP_DELETE_SCRIPT' => $groupDeleteJsScript));

			if (empty($group['members'])) {
				$tpl->assign('GROUP_MEMBERS', '');
			} else {
				$query = "SELECT `uname` FROM `htaccess_users` WHERE `id` IN({$group['members']})";
				$stmt = execute_query($query);

				$tpl->assign('MEMBER', tohtml(implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN))));
				$tpl->parse('GROUP_MEMBERS', '.group_members');
			}

			$tpl->parse('GROUP_BLOCK', '.group_block');
			$tpl->assign('GROUP_MEMBERS', '');
			$stmt->moveNext();
		}
	}
}

/************************************************************************************
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
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic(
	array(
		 'page' => 'client/puser_manage.tpl',
		 'page_message' => 'layout',
		 'users_message_block' => 'page',
		 'users_block' => 'page',
		 'user_block' => 'users_block',
		 'groups_message_block' => 'page',
		 'groups_block' => 'page',
		 'group_block' => 'groups_block',
		 'group_members' => 'group_block'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Client / Webtools / Protected Areas / Manage Users and Groups'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_HTACCESS' => tr('Protected areas'),
		 'TR_ACTIONS' => tr('Actions'),
		 'TR_USERS_GROUPS_MANAGE' => tr('Manage users and groups'),
		 'TR_USERS' => tr('Users'),
		 'TR_USERNAME' => tr('Username'),
		 'TR_ADD_USER' => tr('Add user'),
		 'TR_GROUPNAME' => tr('Group name'),
		 'TR_GROUP_MEMBERS' => tr('Group members'),
		 'TR_ADD_GROUP' => tr('Add group'),
		 'TR_GROUP' => tr('Group'),
		 'TR_GROUPS' => tr('Groups'),
		 'TR_PASSWORD' => tr('Password'),
		 'TR_STATUS' => tr('Status'),
		 'TR_PASSWORD_REPEAT' => tr('Repeat password'),
		 'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
		 'TR_HTACCESS_USER' => tr('Manage users and groups')));

generateNavigation($tpl);

$domainId = get_user_domain_id($_SESSION['user_id']);
client_generateUsersList($tpl, $domainId);
client_genetateGroupsList($tpl, $domainId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
