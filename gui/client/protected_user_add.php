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
$tpl->define_dynamic('page', Config::getInstance()->get('CLIENT_TEMPLATE_PATH') . '/puser_uadd.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('usr_msg', 'page');
$tpl->define_dynamic('grp_msg', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('pusres', 'page');
$tpl->define_dynamic('pgroups', 'page');

$theme_color = Config::getInstance()->get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_WEBTOOLS_PAGE_TITLE'	=> tr('ispCP - Client/Webtools'),
		'THEME_COLOR_PATH'				=> "../themes/$theme_color",
		'THEME_CHARSET'					=> tr('encoding'),
		'ISP_LOGO'						=> get_logo($_SESSION['user_id'])
	)
);

function padd_user(&$tpl, &$sql, $dmn_id) {
	if (isset($_POST['uaction']) && $_POST['uaction'] == 'add_user') {
		// we have to add the user
		if (isset($_POST['username']) && isset($_POST['pass']) && isset($_POST['pass_rep'])) {
			if (!validates_username($_POST['username'])) {
				set_page_message(tr('Wrong username!'));
				return;
			}
			if (!chk_password($_POST['pass'])) {
				if (Config::getInstance()->get('PASSWD_STRONG')) {
					set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), Config::getInstance()->get('PASSWD_CHARS')));
				} else {
					set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), Config::getInstance()->get('PASSWD_CHARS')));
				}
				return;
			}
			if ($_POST['pass'] !== $_POST['pass_rep']) {
				set_page_message(tr('Passwords do not match!'));
				return;
			}
			$status = Config::getInstance()->get('ITEM_ADD_STATUS');

			$uname = clean_input($_POST['username']);

			$upass = crypt_user_pass_with_salt($_POST['pass']);

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
			$rs = exec_query($sql, $query, array($uname, $dmn_id));

			if ($rs->RecordCount() == 0) {

				$query = "
					INSERT INTO `htaccess_users`
						(`dmn_id`, `uname`, `upass`, `status`)
					VALUES
						(?, ?, ?, ?)
				";
				$rs = exec_query($sql, $query, array($dmn_id, $uname, $upass, $status));

				send_request();

				$admin_login = $_SESSION['user_logged'];
				write_log("$admin_login: add user (protected areas): $uname");
				user_goto('protected_user_manage.php');
			} else {
				set_page_message(tr('User already exist !'));
				return;
			}
		}
	} else {
		return;
	}
}

/*
 *
 * static page messages.
 *
 */

gen_client_mainmenu($tpl, Config::getInstance()->get('CLIENT_TEMPLATE_PATH') . '/main_menu_webtools.tpl');
gen_client_menu($tpl, Config::getInstance()->get('CLIENT_TEMPLATE_PATH') . '/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

padd_user($tpl, $sql, get_user_domain_id($sql, $_SESSION['user_id']));

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
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::getInstance()->get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
