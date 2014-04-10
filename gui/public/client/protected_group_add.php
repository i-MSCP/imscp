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

/******************************************************************
 * Script functions
 */

/**
 * Adds Htaccess group.
 *
 * @param int $domainId Domain unique identifier
 * @return
 */
function client_addHtaccessGroup($domainId)
{
	if (isset($_POST['uaction']) && $_POST['uaction'] == 'add_group') {
		// we have to add the group
		if (isset($_POST['groupname'])) {
			if (!validates_username($_POST['groupname'])) {
				set_page_message(tr('Invalid group name!'), 'error');
				return;
			}

			$groupname = $_POST['groupname'];

			$query = "
				SELECT
					`id`
				FROM
					`htaccess_groups`
				WHERE
					`ugroup` = ?
				AND
					`dmn_id` = ?
			";
			$rs = exec_query($query, array($groupname, $domainId));

			if ($rs->rowCount() == 0) {
				$change_status = 'toadd';

				$query = "
					INSERT INTO `htaccess_groups` (
					    `dmn_id`, `ugroup`, `status`
					) VALUES (
					    ?, ?, ?
					)
				";
				exec_query($query, array($domainId, $groupname, $change_status));

				send_request();
				set_page_message(tr('Htaccess group successfully scheduled for addition.'), 'success');

				$admin_login = $_SESSION['user_logged'];
				write_log("$admin_login: added htaccess group: $groupname", E_USER_NOTICE);
				redirectTo('protected_user_manage.php');
			} else {
				set_page_message(tr('This htaccess group already exists.'), 'error');
				return;
			}
		} else {
			set_page_message(tr('Invalid htaccess group name.'), 'error');
			return;
		}
	} else {
		return;
	}
}

/************************************************************************
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
		'page' => 'client/puser_gadd.tpl',
		'page_message' => 'layout',
		'usr_msg' => 'page',
		'grp_msg' => 'page',
		'pusres' => 'page',
		'pgroups' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Webtools / Protected Areas / Manage Users and Groups / Add Group'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_HTACCESS_GROUP' => tr('Htaccess group'),
		'TR_GROUPNAME' => tr('Group name'),
		'TR_ADD_GROUP' => tr('Add'),
		'TR_CANCEL' => tr('Cancel')));

generateNavigation($tpl);
client_addHtaccessGroup(get_user_domain_id($_SESSION['user_id']));
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
