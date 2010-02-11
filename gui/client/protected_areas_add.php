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

//dirty hack (disable HTMLPurifier until figure out how to let pass post arrays)
define('OVERRIDE_PURIFIER', null);

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/protect_it.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('group_item', 'page');
$tpl->define_dynamic('user_item', 'page');
$tpl->define_dynamic('unprotect_it', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_WEBTOOLS_PAGE_TITLE' => tr('ispCP - Client/Webtools'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

/**
 * @todo use db prepared statements
 */
function protect_area(&$tpl, &$sql, $dmn_id) {
	if (!isset($_POST['uaction']) || $_POST['uaction'] != 'protect_it') {
		return;
	}

	if (!isset($_POST['users']) && !isset($_POST['groups'])) {
		set_page_message(tr('Please choose user or group'));
		return;
	}

	if (empty($_POST['paname'])) {
		set_page_message(tr('Please enter area name'));
		return;
	}

	if (empty($_POST['other_dir'])) {
		set_page_message(tr('Please enter area path'));
		return;
	}
	// Check for existing directory
	$path = clean_input($_POST['other_dir'], false);
	$domain = $_SESSION['user_logged'];
	// We need to use the virtual file system
	$vfs = new vfs($domain, $sql);
	$res = $vfs->exists($path);
	if (!$res) {
		set_page_message(tr("%s doesn't exist", $path));
		return;
	}

	$ptype = $_POST['ptype'];

	if (isset($_POST['users']))
		$users = $_POST['users'];

	if (isset($_POST['groups']))
		$groups = $_POST['groups'];

	$area_name = $_POST['paname'];

	$user_id = '';
	$group_id = '';
	if ($ptype == 'user') {
		for ($i = 0, $cnt_users = count($users); $i < $cnt_users; $i++) {
			if ($cnt_users == 1 || $cnt_users == $i + 1) {
				$user_id .= $users[$i];
				if ($user_id == '-1' || $user_id == '') {
					set_page_message(tr('You cannot protect area without selected user(s)!'));
					return;
				}
			} else {
				$user_id .= $users[$i] . ',';
			}
		}
		$group_id = 0;
	} else {
		for ($i = 0, $cnt_groups = count($groups); $i < $cnt_groups; $i++) {
			if ($cnt_groups == 1 || $cnt_groups == $i + 1) {
				$group_id .= $groups[$i];
				if ($group_id == '-1' || $group_id == '') {
					set_page_message(tr('You cannot protect area without selected group(s)'));
					return;
				}
			} else {
				$group_id .= $groups[$i] . ',';
			}
		}
		$user_id = 0;
	}
	// let's check if we have to update or to make new enrie
	$alt_path = $path . "/";
	$query = <<<SQL_QUERY
		SELECT
			`id`
		FROM
			`htaccess`
		WHERE
			`dmn_id` = ?
		AND
			(`path` = ? OR `path` = ?)
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id, $path, $alt_path));
	$toadd_status = Config::get('ITEM_ADD_STATUS');
	$tochange_status = Config::get('ITEM_CHANGE_STATUS');

	if ($rs->RecordCount() !== 0) {
		$update_id = $rs->fields['id'];
		$query = <<<SQL_QUERY
			UPDATE
				`htaccess`
			SET
				`user_id` = ?,
				`group_id` = ?,
				`auth_name` = ?,
				`path` = ?,
				`status` = ?
			WHERE
				`id` = '$update_id';
SQL_QUERY;

		$rs = exec_query($sql, $query, array($user_id, $group_id, $area_name, $path, $tochange_status));
		send_request();
		set_page_message(tr('Protected area updated successfully!'));
	} else {
		$query = <<<SQL_QUERY
			INSERT INTO `htaccess`
				(`dmn_id`, `user_id`, `group_id`, `auth_type`, `auth_name`, `path`, `status`)
			VALUES
				(?, ?, ?, ?, ?, ?, ?);
SQL_QUERY;

		$rs = exec_query($sql, $query, array($dmn_id, $user_id, $group_id, 'Basic' , $area_name, $path, $toadd_status));
		send_request();
		set_page_message(tr('Protected area created successfully!'));
	}

	user_goto('protected_areas.php');
}

function gen_protect_it(&$tpl, &$sql, &$dmn_id) {
	if (!isset($_GET['id'])) {
		$edit = 'no';
		$type = 'user';
		$user_id = 0;
		$group_id = 0;
		$tpl->assign(
			array(
				'PATH' => '',
				'AREA_NAME' => '',
				'UNPROTECT_IT' => '',
			)
		);
	} else {
		$edit = 'yes';
		$ht_id = $_GET['id'];

		$tpl->assign('CDIR', $ht_id);
		$tpl->parse('UNPROTECT_IT', 'unprotect_it');

		$query = <<<SQL_QUERY
			SELECT
				*
			FROM
				`htaccess`
			WHERE
				`dmn_id` = ?
			AND
				`id` = ?;
SQL_QUERY;

		$rs = exec_query($sql, $query, array($dmn_id, $ht_id));

		if ($rs->RecordCount() == 0) {
			user_goto('protected_areas_add.php');
		}

		$user_id = $rs->fields['user_id'];
		$group_id = $rs->fields['group_id'];
		$status = $rs->fields['status'];
		$path = $rs->fields['path'];
		$auth_name = $rs->fields['auth_name'];
		$ok_status = Config::get('ITEM_OK_STATUS');
		if ($status !== $ok_status) {
			set_page_message(tr('Protected area status should be OK if you want to edit it!'));
			user_goto('protected_areas.php');
		}

		$tpl->assign(
			array(
				'PATH' => $path,
				'AREA_NAME' => $auth_name,
			)
		);
		// let's get the htaccess management type
		if ($user_id !== 0 && $group_id == 0) {
			// we have only user htaccess
			$type = 'user';
		} else if ($group_id !== 0 && $user_id == 0) {
			// we have only groups htaccess
			$type = 'group';
		} else if ($group_id == 0 && $user_id == 0) {
			// we have unsr and groups htaccess
			$type = 'both';
		}
	}
	// this area is not secured by htaccess
	if ($edit = 'no' || $rs->RecordCount() == 0 || $type == 'user') {
		$tpl->assign(
			array(
				'USER_CHECKED' => 'checked="checked"',
				'GROUP_CHECKED' => "",
				'USER_FORM_ELEMENS' => "false",
				'GROUP_FORM_ELEMENS' => "true",
			)
		);
	}

	if ($type == 'group') {
		$tpl->assign(
			array(
				'USER_CHECKED' => "",
				'GROUP_CHECKED' => 'checked="checked"',
				'USER_FORM_ELEMENS' => "true",
				'GROUP_FORM_ELEMENS' => "false",
			)
		);
	}

	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`htaccess_users`
		WHERE
			`dmn_id` = ?;
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id));

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'USER_VALUE' => "-1",
				'USER_LABEL' => tr('You have no users !'),
				'USER_SELECTED' => ''
			)
		);
		$tpl->parse('USER_ITEM', 'user_item');
	} else {
		while (!$rs->EOF) {
			$usr_id = explode(',', $user_id);
			for ($i = 0, $cnt_usr_id = count($usr_id); $i < $cnt_usr_id; $i++) {
				if ($edit == 'yes' && $usr_id[$i] == $rs->fields['id']) {
					$i = $cnt_usr_id + 1;
					$usr_selected = " selected ";
				} else {
					$usr_selected = '';
				}
			}

			$tpl->assign(
				array(
					'USER_VALUE' => $rs->fields['id'],
					'USER_LABEL' => $rs->fields['uname'],
					'USER_SELECTED' => $usr_selected,
				)
			);

			$tpl->parse('USER_ITEM', '.user_item');

			$rs->MoveNext();
		}
	}

	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`htaccess_groups`
		WHERE
			`dmn_id` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id));

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'GROUP_VALUE' => "-1",
				'GROUP_LABEL' => tr('You have no groups!'),
				'GROUP_SELECTED' => ''
			)
		);
		$tpl->parse('GROUP_ITEM', 'group_item');
	} else {
		while (!$rs->EOF) {
			$grp_id = explode(',', $group_id);
			for ($i = 0, $cnt_grp_id = count($grp_id); $i < $cnt_grp_id; $i++) {
				if ($edit == 'yes' && $grp_id[$i] == $rs->fields['id']) {
					$i = $cnt_grp_id + 1;
					$grp_selected = 'selected="selected"';
				} else {
					$grp_selected = '';
				}
			}

			$tpl->assign(
				array(
					'GROUP_VALUE' => $rs->fields['id'],
					'GROUP_LABEL' => $rs->fields['ugroup'],
					'GROUP_SELECTED' => $grp_selected,
				)
			);
			$tpl->parse('GROUP_ITEM', '.group_item');
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

protect_area($tpl, $sql, $dmn_id);

gen_protect_it($tpl, $sql, $dmn_id);

$tpl->assign(
	array(
		'TR_HTACCESS' => tr('Protected areas'),
		'TR_PROTECT_DIR' => tr('Protect this area'),
		'TR_PATH' => tr('Path'),
		'TR_USER' => tr('Users'),
		'TR_GROUPS' => tr('Groups'),
		'TR_PROTECT_IT' => tr('Protect it'),
		'TR_USER_AUTH' => tr('User auth'),
		'TR_GROUP_AUTH' => tr('Group auth'),
		'TR_AREA_NAME' => tr('Area name'),
		'TR_PROTECT_IT' => tr('Protect it'),
		'TR_UNPROTECT_IT' => tr('Unprotect it'),
		'TR_AREA_NAME' => tr('Area name'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_MANAGE_USRES' => tr('Manage users and groups'),
		'CHOOSE_DIR' => tr('Choose dir'),
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
