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
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/puser_gadd.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('usr_msg', 'page');
$tpl->define_dynamic('grp_msg', 'page');
$tpl->define_dynamic('pusres', 'page');
$tpl->define_dynamic('pgroups', 'page');


$tpl->assign(
	array(
		'TR_PAGE_TITLE'	=> tr('i-MSCP - Client / Webtools Protected areas / Add group'),
		'THEME_COLOR_PATH'				=> "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'					=> tr('encoding'),
		'ISP_LOGO'						=> layout_getUserLogo()
	)
);

/**
 * @param $tpl
 * @param $dmn_id
 * @return
 */
function padd_group($tpl, $dmn_id) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

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

			$rs = exec_query($query, array($groupname, $dmn_id));

			if ($rs->recordCount() == 0) {
				$change_status = $cfg->ITEM_ADD_STATUS;

				$query = "
					INSERT INTO `htaccess_groups` (
					    `dmn_id`, `ugroup`, `status`
					) VALUES (
					    ?, ?, ?
					)
				";

				exec_query($query, array($dmn_id, $groupname, $change_status));

				send_request();

				set_page_message(tr('Group scheduled for addition.'), 'success');

				$admin_login = $_SESSION['user_logged'];
				write_log("$admin_login: add group (protected areas): $groupname", E_USER_NOTICE);
				redirectTo('protected_user_manage.php');
			} else {
				set_page_message(tr('Group already exists.'), 'error');
				return;
			}
		} else {
			set_page_message(tr('Invalid group name.'), 'error');
			return;
		}
	} else {
		return;
	}
}

gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_webtools.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_webtools.tpl');
padd_group($tpl, get_user_domain_id($_SESSION['user_id']));

$tpl->assign(
	array(
		'TR_HTACCESS'			=> tr('Protected areas'),
		'TR_ACTION'				=> tr('Action'),
		'TR_USER_MANAGE'		=> tr('Manage user'),
		'TR_USERS'				=> tr('User'),
		'TR_USERNAME'			=> tr('Username'),
		'TR_ADD_USER'			=> tr('Add user'),
		'TR_GROUPNAME'			=> tr('Group name'),
		'TR_GROUP_MEMBERS'		=> tr('Group members'),
		'TR_ADD_GROUP'			=> tr('Add group'),
		'TR_EDIT'				=> tr('Edit'),
		'TR_GROUP'				=> tr('Group'),
		'TR_DELETE'				=> tr('Delete'),
		'TR_GROUPS'				=> tr('Groups'),
		'TR_PASSWORD'			=> tr('Password'),
		'TR_PASSWORD_REPEAT'	=> tr('Repeat password'),
		'TR_CANCEL'				=> tr('Cancel'),
		'TR_HTACCESS_USER' => tr('Manage users and groups')
	)
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
