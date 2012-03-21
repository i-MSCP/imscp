<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/************************************************************************************
 * Script function script
 */

/**
 * Update password.
 *
 * @return void
 */
function admin_updatePassword()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['uaction']) && $_POST['uaction'] == 'updt_pass') {

		iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeEditUser, array('userId' => $_SESSION['user_id']));

		if (empty($_POST['pass']) || empty($_POST['pass_rep']) || empty($_POST['curr_pass'])) {
			set_page_message(tr('All fields are required.'), 'error');
		} else if (!chk_password($_POST['pass'])) {
			if ($cfg->PASSWD_STRONG) {
				set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), $cfg->PASSWD_CHARS), 'error');
			} else {
				set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs.'), $cfg->PASSWD_CHARS), 'error');
			}
		} elseif ($_POST['pass'] !== $_POST['pass_rep']) {
			set_page_message(tr("Passwords doesn't match."), 'error');
		} elseif (_admin_checkCurrentPassword($_SESSION['user_id'], $_POST['curr_pass']) === false) {
			set_page_message(tr('The current password is wrong.'), 'error');
		} else {
			$upass = crypt_user_pass($_POST['pass']);
			$user_id = $_SESSION['user_id'];

			$query = "UPDATE `admin` SET `admin_pass` = ? WHERE `admin_id` = ? ";
			exec_query($query, array($upass, $user_id));

			iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterEditUser, array('userId' => $_SESSION['user_id']));

			set_page_message(tr('Password successfully updated.'), 'success');
			redirectTo('profile.php');
		}
	}
}

/**
 * Check current password.
 *
 * @access private
 * @param int $id Admin unique identifier
 * @param string $pass Admin current password
 * @return bool TRUE if current password is valid, FALSE otherwise
 */
function _admin_checkCurrentPassword($id, $pass)
{
	$query = "SELECT `admin_name`, `admin_pass` FROM `admin` WHERE `admin_id` = ?";
	$stmt = exec_query($query, $id);

	if ($stmt->rowCount()) {
		$stmt = $stmt->fetchRow();

		if ((crypt($pass, $stmt['admin_pass']) == $stmt['admin_pass']) || (md5($pass) == $stmt['admin_pass'])) {
			return true;
		}
	}

	return false;
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/password_change.tpl',
		'page_message' => 'layout'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin / Profile /  Password'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_CHANGE_PASSWORD' => tr('Change password'),
		'TR_PASSWORD_DATA' => tr('Password data'),
		'TR_PASSWORD' => tr('Password'),
		'TR_PASSWORD_REPEAT' => tr('Repeat password'),
		'TR_CURR_PASSWORD' => tr('Current password'),
		'TR_UPDATE' => tr('Update')));

generateNavigation($tpl);
admin_updatePassword();
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
