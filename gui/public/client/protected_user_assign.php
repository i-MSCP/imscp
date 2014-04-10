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

/**************************************************************************
 * Script functions
 */

/**
 * Return htaccess username.
 *
 * @param int $uuser_id Htaccess user unique identifier
 * @param int $dmn_id Domain unique identifier
 * @return string
 */
function client_getHtaccessUsername(&$uuser_id, &$dmn_id)
{
	$query = "SELECT `uname` FROM `htaccess_users` WHERE `dmn_id` = ? AND `id` = ?";
	$stmt = exec_query($query, array($dmn_id, $uuser_id));

	if ($stmt->rowCount() == 0) {
		redirectTo('protected_user_manage.php');
		exit;
	} else {
		return $stmt->fields['uname'];
	}
}

/**
 * Generates page.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $dmn_id Domain unique identifier
 * @return void
 */
function client_generatePage($tpl, &$dmn_id)
{
	if (isset($_GET['uname']) && $_GET['uname'] !== '' && is_numeric($_GET['uname'])) {
		$uuser_id = $_GET['uname'];

		$tpl->assign('UNAME', tohtml(client_getHtaccessUsername($uuser_id, $dmn_id)));
		$tpl->assign('UID', $uuser_id);
	} else if (isset($_POST['nadmin_name']) && !empty($_POST['nadmin_name']) && is_numeric($_POST['nadmin_name'])) {
		$uuser_id = $_POST['nadmin_name'];
		$tpl->assign('UNAME', tohtml(client_getHtaccessUsername($uuser_id, $dmn_id)));
		$tpl->assign('UID', $uuser_id);
	} else {
		redirectTo('protected_user_manage.php');
		exit; // Useless but avoid stupid IDE warning about possibled undefined variable
	}
	// get groups
	$query = "SELECT * FROM `htaccess_groups` WHERE `dmn_id` = ?";
	$stmt = exec_query($query, $dmn_id);

	if ($stmt->rowCount() == 0) {
		set_page_message(tr('You have no groups.'), 'error');
		redirectTo('protected_user_manage.php');
	} else {
		$added_in = 0;
		$not_added_in = 0;

		while (!$stmt->EOF) {
			$group_id = $stmt->fields['id'];
			$group_name = $stmt->fields['ugroup'];
			$members = $stmt->fields['members'];

			$members = explode(",", $members);
			$grp_in = 0;
			// let's generete all groups wher the user is assigned
			for ($i = 0, $cnt_members = count($members); $i < $cnt_members; $i++) {
				if ($uuser_id == $members[$i]) {
					$tpl->assign(
						array(
							'GRP_IN' => tohtml($group_name),
							'GRP_IN_ID' => $group_id,
						)
					);

					$tpl->parse('ALREADY_IN', '.already_in');
					$grp_in = $group_id;
					$added_in++;
				}
			}
			if ($grp_in !== $group_id) {
				$tpl->assign(
					array(
						'GRP_NAME' => tohtml($group_name),
						'GRP_ID' => $group_id));

				$tpl->parse('GRP_AVLB', '.grp_avlb');
				$not_added_in++;
			}

			$stmt->moveNext();
		}

		// generate add/remove buttons
		if ($added_in < 1) {
			$tpl->assign('IN_GROUP', '');
		}
		if ($not_added_in < 1) {
			$tpl->assign('NOT_IN_GROUP', '');
		}
	}
}

/**
 * Assign a specific htaccess user to a specific htaccess group.
 *
 * @param int $dmn_id Domain unique identifier
 * @return
 */
function client_addHtaccessUserToHtaccessGroup(&$dmn_id)
{
	if (isset($_POST['uaction']) && $_POST['uaction'] == 'add' &&
		isset($_POST['groups']) && !empty($_POST['groups']) &&
		isset($_POST['nadmin_name']) && is_numeric($_POST['groups']) &&
		is_numeric($_POST['nadmin_name'])
	) {
		$uuser_id = clean_input($_POST['nadmin_name']);
		$group_id = $_POST['groups'];

		$query = "
			SELECT
				`id`, `ugroup`, `members`
			FROM
				`htaccess_groups`
			WHERE
				`dmn_id` = ?
			AND
				`id` = ?
		";
		$rs = exec_query($query, array($dmn_id, $group_id));

		$members = $rs->fields['members'];
		if ($members == '') {
			$members = $uuser_id;
		} else {
			$members = $members . "," . $uuser_id;
		}

		$change_status = 'tochange';

		$update_query = "
			UPDATE
				`htaccess_groups`
			SET
				`members` = ?, `status` = ?
			WHERE
				`id` = ?
			AND
				`dmn_id` = ?
		";
		exec_query($update_query, array($members, $change_status, $group_id, $dmn_id));

		send_request();
		set_page_message(tr('Htaccess user successfully assigned to the %s htaccess group', $rs->fields['ugroup']), 'success');
	} else {
		return;
	}
}

/**
 * Remove user from a specific group.
 *
 * @param int $dmn_id Domain unique identifier
 * @return
 */
function client_removeHtaccessUserFromHtaccessGroup(&$dmn_id)
{
	if (isset($_POST['uaction']) && $_POST['uaction'] == 'remove' &&
		isset($_POST['groups_in']) && !empty($_POST['groups_in']) &&
		isset($_POST['nadmin_name']) && is_numeric($_POST['groups_in']) &&
		is_numeric($_POST['nadmin_name'])
	) {
		$group_id = $_POST['groups_in'];
		$uuser_id = clean_input($_POST['nadmin_name']);

		$query = "
			SELECT
				`id`, `ugroup`, `members`
			FROM
				`htaccess_groups`
			WHERE
				`dmn_id` = ?
			AND
				`id` = ?
		";
		$stmt = exec_query($query, array($dmn_id, $group_id));

		$members = explode(',', $stmt->fields['members']);
		$key = array_search($uuser_id, $members);
		if ($key !== false) {
			unset($members[$key]);
			$members = implode(",", $members);
			$change_status = 'tochange';
			$update_query = "
				UPDATE
					`htaccess_groups`
				SET
					`members` = ?, `status` = ?
				WHERE
					`id` = ?
				AND
					`dmn_id` = ?
			";
			exec_query($update_query, array($members, $change_status, $group_id, $dmn_id));

			send_request();
			set_page_message(tr('Htaccess user successfully deleted from the %s htaccess group ', $stmt->fields['ugroup']), 'success');
		} else {
			return;
		}
	} else {
		return;
	}
}

/*************************************************************************
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
		'page' => 'client/puser_assign.tpl',
		'page_message' => 'layout',
		'already_in' => 'page',
		'grp_avlb' => 'page',
		'add_button' => 'page',
		'remove_button' => 'page',
		'in_group' => 'page',
		'not_in_group' => 'page'));

$tpl->assign(
	array(
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_PAGE_TITLE' => 'Client / Webtools / Protected Areas / Manage Users and Groups / Assign Group',
		'TR_SELECT_GROUP' => tr('Select group'),
		'TR_MEMBER_OF_GROUP' => tr('Member of group'),
		'TR_ADD' => tr('Add'),
		'TR_REMOVE' => tr('Remove'),
		'TR_CANCEL' => tr('Cancel')));

generateNavigation($tpl);
$dmn_id = get_user_domain_id($_SESSION['user_id']);
client_addHtaccessUserToHtaccessGroup($dmn_id);
client_removeHtaccessUserFromHtaccessGroup($dmn_id);
client_generatePage($tpl, $dmn_id);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
