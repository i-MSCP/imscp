<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/puser_assign.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('already_in', 'page');
$tpl->define_dynamic('grp_avlb', 'page');
$tpl->define_dynamic('add_button', 'page');
$tpl->define_dynamic('remove_button', 'page');
$tpl->define_dynamic('in_group', 'page');
$tpl->define_dynamic('not_in_group', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'THEME_COLOR_PATH'	=> "../themes/$theme_color",
		'THEME_CHARSET'		=> tr('encoding'),
		'ISP_LOGO'			=> get_logo($_SESSION['user_id'])
	)
);

/*
 * functions
 */

function get_htuser_name(&$sql, &$uuser_id, &$dmn_id) {
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

	$rs = exec_query($sql, $query, array($dmn_id, $uuser_id));

	if ($rs->RecordCount() == 0) {
		user_goto('protected_user_manage.php');
	} else {
		return $rs->fields['uname'];
	}
}

function gen_user_assign(&$tpl, &$sql, &$dmn_id) {
	if (isset($_GET['uname'])
		&& $_GET['uname'] !== ''
		&& is_numeric($_GET['uname'])) {
		$uuser_id = $_GET['uname'];

		$tpl->assign('UNAME', get_htuser_name($sql, $uuser_id, $dmn_id));
		$tpl->assign('UID', $uuser_id);
	} else if (isset($_POST['nadmin_name'])
		&& !empty($_POST['nadmin_name'])
		&& is_numeric($_POST['nadmin_name'])) {
		$uuser_id = $_POST['nadmin_name'];

		$tpl->assign('UNAME', get_htuser_name($sql, $uuser_id, $dmn_id));
		$tpl->assign('UID', $uuser_id);
	} else {
		user_goto('protected_user_manage.php');
	}
	// get groups
	$query = "
		SELECT
			*
		FROM
			`htaccess_groups`
		WHERE
			`dmn_id` = ?
	";

	$rs = exec_query($sql, $query, array($dmn_id));

	if ($rs->RecordCount() == 0) {
		set_page_message(tr('You have no groups!'));
		user_goto('protected_user_manage.php');
	} else {
		$added_in = 0;
		$not_added_in = 0;

		while (!$rs->EOF) {
			$group_id = $rs->fields['id'];
			$group_name = $rs->fields['ugroup'];
			$members = $rs->fields['members'];

			$members = explode(",", $members);
			$grp_in = 0;
			// let's generete all groups wher the user is assigned
			for ($i = 0, $cnt_members = count($members); $i < $cnt_members; $i++) {
				if ($uuser_id == $members[$i]) {
					$tpl->assign(
						array(
							'GRP_IN' => $group_name,
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
						'GRP_NAME' => $group_name,
						'GRP_ID' => $group_id,
					)
				);
				$tpl->parse('GRP_AVLB', '.grp_avlb');
				$not_added_in++;
			}

			$rs->MoveNext();
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

function add_user_to_group(&$tpl, &$sql, &$dmn_id) {
	if (isset($_POST['uaction']) && $_POST['uaction'] == 'add'
		&& isset($_POST['groups']) && !empty($_POST['groups'])
		&& isset($_POST['nadmin_name']) && is_numeric($_POST['groups'])
		&& is_numeric($_POST['nadmin_name'])) {
		$uuser_id = clean_input($_POST['nadmin_name']);
		$group_id = $_POST['groups'];

		$query = "
			SELECT
				`id`,
				`ugroup`,
				`members`
			FROM
				`htaccess_groups`
			WHERE
				`dmn_id` = ?
			AND
				`id` = ?
		";

		$rs = exec_query($sql, $query, array($dmn_id, $group_id));

		$members = $rs->fields['members'];
		if ($members == '') {
			$members = $uuser_id;
		} else {
			$members = $members . "," . $uuser_id;
		}

		$change_status = Config::get('ITEM_CHANGE_STATUS');

		$update_query = "
			UPDATE
				`htaccess_groups`
			SET
				`members` = ?,
				`status` = ?
			WHERE
				`id` = ?
			AND
				`dmn_id` = ?
		";

		$rs_update = exec_query($sql, $update_query, array($members, $change_status, $group_id, $dmn_id));

		send_request();
		set_page_message(tr('User was assigned to the %s group', $rs->fields['ugroup']));
	} else {
		return;
	}
}

function delete_user_from_group(&$tpl, &$sql, &$dmn_id) {
	if (isset($_POST['uaction']) && $_POST['uaction'] == 'remove'
		&& isset($_POST['groups_in']) && !empty($_POST['groups_in'])
		&& isset($_POST['nadmin_name']) && is_numeric($_POST['groups_in'])
		&& is_numeric($_POST['nadmin_name'])) {
		$group_id = $_POST['groups_in'];
		$uuser_id = clean_input($_POST['nadmin_name']);

		$query = "
			SELECT
				`id`,
				`ugroup`,
				`members`
			FROM
				`htaccess_groups`
			WHERE
				`dmn_id` = ?
			AND
				`id` = ?
		";

		$rs = exec_query($sql, $query, array($dmn_id, $group_id));

		$members = explode(',', $rs->fields['members']);
		$key = array_search($uuser_id, $members);
		if ($key !== false) {
			unset($members[$key]);
			$members = implode(",", $members);
			$change_status = Config::get('ITEM_CHANGE_STATUS');
			$update_query = "
				UPDATE
					`htaccess_groups`
				SET
					`members` = ?,
					`status` = ?
				WHERE
					`id` = ?
				AND
					`dmn_id` = ?
			";

			$rs_update = exec_query($sql, $update_query, array($members, $change_status, $group_id, $dmn_id));
			send_request();

			set_page_message(tr('User was deleted from the %s group ', $rs->fields['ugroup']));
		} else {
			return;
		}
	} else {
		return;
	}
}

// ** end of funcfions

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_webtools.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

add_user_to_group($tpl, $sql, $dmn_id);

delete_user_from_group($tpl, $sql, $dmn_id);

gen_user_assign($tpl, $sql, $dmn_id);

$tpl->assign(
	array(
		'TR_HTACCESS'			=> tr('Protected areas'),
		'TR_DELETE'				=> tr('Delete'),
		'TR_USER_ASSIGN'		=> tr('User assign'),
		'TR_ALLREADY'			=> tr('Already in:'),
		'TR_MEMBER_OF_GROUP'	=> tr('Member of group:'),
		'TR_BACK'				=> tr('Back'),
		'TR_REMOVE'				=> tr('Remove'),
		'TR_ADD'				=> tr('Add'),
		'TR_SELECT_GROUP'		=> tr('Select group:')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
