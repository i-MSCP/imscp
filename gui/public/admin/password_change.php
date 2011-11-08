<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/password_change.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin/Change Password'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()
	)
);

function update_password() {

	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_pass') {
		if (empty($_POST['pass']) || empty($_POST['pass_rep']) || empty($_POST['curr_pass'])) {
			set_page_message(tr('Please fill up all data fields!'), 'error');
		} else if (!chk_password($_POST['pass'])) {
			if ($cfg->PASSWD_STRONG) {
				set_page_message(
					sprintf(
						tr('The password must be at least %s long and contain letters and numbers to be valid.'),
						$cfg->PASSWD_CHARS
					),
					'error'
				);
			} else {
				set_page_message(
					sprintf(
						tr('Password data is shorter than %s signs or includes not permitted signs!'),
						$cfg->PASSWD_CHARS
					), 'error'
				);
			}
		} else if ($_POST['pass'] !== $_POST['pass_rep']) {
			set_page_message(tr('Passwords do not match!'), 'error');
		} else if (check_udata($_SESSION['user_id'], $_POST['curr_pass']) === false) {
			set_page_message(tr('The current password is wrong!'), 'error');
		} else {
			$upass = crypt_user_pass($_POST['pass']);

			$_SESSION['user_pass'] = $upass;

			$user_id = $_SESSION['user_id'];

			$query = "
				UPDATE
					`admin`
				SET
					`admin_pass` = ?
				WHERE
					`admin_id` = ?
			";
			exec_query($query, array($upass, $user_id));

			set_page_message(tr('User password updated successfully!'), 'success');
		}
	}
}

function check_udata($id, $pass) {

	$query = "
		SELECT
			`admin_name`, `admin_pass`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";

	$rs = exec_query($query, $id);

	if ($rs->recordCount() == 1) {
		$rs = $rs->fetchRow();

		if ((crypt($pass, $rs['admin_pass']) == $rs['admin_pass'])
			|| (md5($pass) == $rs['admin_pass'])) {
			return true;
		}
	}

	return false;
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_general_information.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_general_information.tpl');

$tpl->assign(
	array(
		'TR_CHANGE_PASSWORD' 	=> tr('Change password'),
		'TR_PASSWORD_DATA' 		=> tr('Password data'),
		'TR_PASSWORD' 			=> tr('Password'),
		'TR_PASSWORD_REPEAT' 	=> tr('Repeat password'),
		'TR_UPDATE_PASSWORD' 	=> tr('Update password'),
		'TR_CURR_PASSWORD' 		=> tr('Current password'),
		// The entries below are for Demo versions only
		'PASSWORD_DISABLED'		=> tr('Password change is deactivated!'),
		'DEMO_VERSION'			=> tr('Demo Version!')
	)
);

update_password();

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
