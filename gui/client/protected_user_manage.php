<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/puser_manage.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('usr_msg', 'page');
$tpl->define_dynamic('grp_msg', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('pusres', 'page');
$tpl->define_dynamic('pgroups', 'page');
$tpl->define_dynamic('group_members', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_WEBTOOLS_PAGE_TITLE'	=> tr('ispCP - Client/Webtools'),
		'THEME_COLOR_PATH'				=> "../themes/$theme_color",
		'THEME_CHARSET'					=> tr('encoding'),
		'ISP_LOGO'						=> get_logo($_SESSION['user_id'])
	)
);

function gen_user_action($id, $status) {
	if ($status === Config::get('ITEM_OK_STATUS')) {
		return array(tr('Delete'), "action_delete('protected_user_delete.php?uname={USER_ID}', '{UNAME}')", tr('Edit'), "protected_user_edit.php?uname={USER_ID}");
	} else {
		return array(tr('N/A'), '', tr('N/A'), '#');
	}
}

function gen_group_action($id, $status, $group) {
	if ($status === Config::get('ITEM_OK_STATUS')
		&& $group != Config::get('AWSTATS_GROUP_AUTH')) {
		return array(tr('Delete'), "action_delete('protected_group_delete.php?gname={GROUP_ID}', '{GNAME}')");
	} else {
		return array(tr('N/A'), '');
	}
}

function gen_pusres(&$tpl, &$sql, &$dmn_id) {
	$query = "
		SELECT
			*
		FROM
			`htaccess_users`
		WHERE
			`dmn_id` = ?
		ORDER BY
			`dmn_id` DESC
	";

	$rs = exec_query($sql, $query, array($dmn_id));

	if ($rs->RecordCount() == 0) {
		$tpl->assign('PUSRES', '');
		$tpl->assign('USER_MESSAGE', tr('You have no users!'));
		$tpl->parse('USR_MSG', 'usr_msg');
	} else {
		$tpl->assign('USR_MSG', '');
		while (!$rs->EOF) {
			list($user_delete, $user_delete_script, $user_edit, $user_edit_script) = gen_user_action($rs->fields['id'], $rs->fields['status']);
			$tpl->assign(
				array(
					'UNAME'					=> $rs->fields['uname'],
					'USTATUS'				=> translate_dmn_status($rs->fields['status']),
					'USER_ID'				=> $rs->fields['id'],
					'USER_DELETE'			=> $user_delete,
					'USER_DELETE_SCRIPT'	=> $user_delete_script,
					'USER_EDIT'				=> $user_edit,
					'USER_EDIT_SCRIPT'		=> $user_edit_script
				)
			);

			$tpl->parse('PUSRES', '.pusres');
			$rs->MoveNext();

		}
	}
}

/**
 * @todo Why is $member = ... out commented?
 */
function gen_pgroups(&$tpl, &$sql, &$dmn_id) {
	$query = "
		SELECT
			*
		FROM
			`htaccess_groups`
		WHERE
			`dmn_id` = ?
		ORDER BY
			`dmn_id` DESC
	";

	$rs = exec_query($sql, $query, array($dmn_id));

	if ($rs->RecordCount() == 0) {
		$tpl->assign('GROUP_MESSAGE', tr('You have no groups!'));
		$tpl->parse('GRP_MSG', 'grp_msg');
		$tpl->assign('PGROUPS', '');
	} else {
		$tpl->assign('GRP_MSG', '');
		while (!$rs->EOF) {
//			$members = $rs->fields['members'];

			list($group_delete, $group_delete_script) = gen_group_action($rs->fields['id'], $rs->fields['status'], $rs->fields['ugroup']);
			$tpl->assign(
				array(
					'GNAME'					=> $rs->fields['ugroup'],
					'GSTATUS'				=> translate_dmn_status($rs->fields['status']),
					'GROUP_ID'				=> $rs->fields['id'],
					'GROUP_DELETE'			=> $group_delete,
					'GROUP_DELETE_SCRIPT'	=> $group_delete_script
				)
			);

			if ($rs->fields['members'] == '') {
				$tpl->assign('GROUP_MEMBERS', '');
			} else {
				$members = explode(',', $rs->fields['members']);

				for ($i = 0, $cnt_members = count($members); $i < $cnt_members; $i++) {
					$query = "
						SELECT
							`uname`
						FROM
							`htaccess_users`
						WHERE
							`id` = ?
					";

					$rs_members = exec_query($sql, $query, array($members[$i]));

					if ($cnt_members == 1 || $cnt_members == $i + 1) {
						$tpl->assign('MEMBER', $rs_members->fields['uname']);
					} else {
						$tpl->assign('MEMBER', $rs_members->fields['uname'] . ", ");
					}

					$tpl->parse('GROUP_MEMBERS', '.group_members');
				}
			}

			$tpl->parse('PGROUPS', '.pgroups');
			$tpl->assign('GROUP_MEMBERS', '');
			$rs->MoveNext();
		}
	}
}

/*
 *
 * static page messages.
 *
 */

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_webtools.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

gen_pusres($tpl, $sql, $dmn_id);

gen_pgroups($tpl, $sql, $dmn_id);

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
		'TR_GROUP'				=> tr('Group'),
		'TR_GROUPS'				=> tr('Groups'),
		'TR_PASSWORD'			=> tr('Password'),
		'TR_STATUS'				=> tr('Status'),
		'TR_PASSWORD_REPEAT'	=> tr('Repeat password'),
		'TR_MESSAGE_DELETE'		=> tr('Are you sure you want to delete %s?', true, '%s')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
