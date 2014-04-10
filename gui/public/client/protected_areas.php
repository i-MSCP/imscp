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
		'page' => 'client/protected_areas.tpl',
		'page_message' => 'layout',
		'dir_item' => 'page',
		'action_link' => 'page',
		'protected_areas' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Webtools / Protected Areas'),
		'ISP_LOGO' => layout_getUserLogo()));

/**
 * @param iMSCP_pTemplate $tpl
 * @param $dmn_id
 * @return void
 */
function gen_htaccess_entries($tpl, &$dmn_id)
{
	$query = "SELECT * FROM `htaccess` WHERE `dmn_id` = ?";
	$rs = exec_query($query, $dmn_id);

	if ($rs->recordCount() == 0) {
		$tpl->assign('PROTECTED_AREAS', '');
		set_page_message(tr('You do not have protected areas.'), 'info');
	} else {
		$counter = 0;
		while (!$rs->EOF) {
			$tpl->assign('CLASS', ($counter % 2 == 0) ? 'content' : 'content2');

			$id = $rs->fields['id'];
			$user_id = $rs->fields['user_id'];
			$group_id = $rs->fields['group_id'];
			$status = $rs->fields['status'];
			$path = $rs->fields['path'];
			$auth_name = $rs->fields['auth_name'];

			$tpl->assign(
				array(
					'AREA_NAME' => tohtml($auth_name),
					'JS_AREA_NAME' => addslashes($auth_name),
					'AREA_PATH' => tohtml($path),
					'PID' => $id,
					'STATUS' => translate_dmn_status($status)
				)
			);
			$tpl->parse('DIR_ITEM', '.dir_item');
			$rs->moveNext();
			$counter++;
		}
	}
}

generateNavigation($tpl);

$dmn_id = get_user_domain_id($_SESSION['user_id']);

gen_htaccess_entries($tpl, $dmn_id);

$tpl->assign(
	array(
		'TR_HTACCESS' => tr('Protected areas'),
		'TR_DIRECTORY_TREE' => tr('Directory tree'),
		'TR_DIRS' => tr('Name'),
		'TR__ACTION' => tr('Action'),
		'TR_MANAGE_USRES' => tr('Manage users and groups'),
		'TR_USERS' => tr('User'),
		'TR_USERNAME' => tr('Username'),
		'TR_ADD_USER' => tr('Add user'),
		'TR_GROUPNAME' => tr('Group name'),
		'TR_GROUP_MEMBERS' => tr('Group members'),
		'TR_ADD_GROUP' => tr('Add group'),
		'TR_EDIT' => tr('Edit'),
		'TR_GROUP' => tr('Group'),
		'TR_DELETE' => tr('Delete'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
		'TR_STATUS' => tr('Status'),
		'TR_ADD_AREA' => tr('Add new protected area')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
