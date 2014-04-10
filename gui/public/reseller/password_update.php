<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Reseller
 * @copyright   2010-2014 by -MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/************************************************************************************
 * Script function script
 */

/**
 * Update admin password.
 *
 * @return void
 */
function reseller_updatePassword()
{
	if (is_xhr()) { // Passsword generation (AJAX request)
		header('Content-Type: text/plain; charset=utf-8');
		header('Cache-Control: no-cache, private');
		header('Pragma: no-cache');
		header("HTTP/1.0 200 Ok");
		echo passgen();
		exit;
	} elseif(!empty($_POST)) {
		$userId = $_SESSION['user_id'];

		iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditUser, array('userId' => $userId));

		if (empty($_POST['current_password']) || empty($_POST['password']) || empty($_POST['password_confirmation'])) {
			set_page_message(tr('All fields are required.'), 'error');
		} else if (!_reseller_checkCurrentPassword($_POST['current_password'])) {
			set_page_message(tr('Current password is invalid.'), 'error');
		} else if ($_POST['password'] !== $_POST['password_confirmation']) {
			set_page_message(tr("Passwords do not match."), 'error');
		} elseif (checkPasswordSyntax($_POST['password'])) {
			$query = 'UPDATE `admin` SET `admin_pass` = ? WHERE `admin_id` = ?';
			exec_query($query, array(cryptPasswordWithSalt($_POST['password']), $userId));

			iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditUser, array('userId' => $userId));

			write_log($_SESSION['user_logged'] . ': updated password.', E_USER_NOTICE);
			set_page_message(tr('Password successfully updated.'), 'success');
		}
	}
}

/**
 * Check admin current password.
 *
 * @access private
 * @param string $password Admin current password
 * @return bool TRUE if current password is valid, FALSE otherwise
 */
function _reseller_checkCurrentPassword($password)
{
	$stmt = exec_query('SELECT `admin_pass` FROM `admin` WHERE `admin_id` = ?', $_SESSION['user_id']);

	if (!$stmt->rowCount()) {
		set_page_message(tr('Unable to retrieve your password from the database.'), 'error');
		return false;
	} elseif (cryptPasswordWithSalt($password, $stmt->fields['admin_pass']) !== $stmt->fields['admin_pass']) {
		return false;
	}

	return true;
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

reseller_updatePassword();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'shared/partials/forms/password_update.tpl',
		'page_message' => 'layout'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Reseller / Profile / Password'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_PASSWORD_DATA' => tr('Password data'),
		'TR_CURRENT_PASSWORD' => tr('Current password'),
		'TR_PASSWORD' => tr('Password'),
		'TR_PASSWORD_CONFIRMATION' => tr('Password confirmation'),
		'TR_UPDATE' => tr('Update'),
		'TR_GENERATE' => tr('Generate'),
		'TR_SHOW' => tr('Show'),
		'TR_PASSWORD_GENERATION_NEEDED' => tr('You must first generate a password'),
		'TR_NEW_PASSWORD_IS' => tr('New password is'),
		'TR_RESET' => tr('Reset')
	)
);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
