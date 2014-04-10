<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP team
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
 * @package	    iMSCP_Core
 * @subpackage  Client_Ftp
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

// Include core library
require_once 'imscp-lib.php';

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page data
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param string $ftpUserId Ftp userid
 * @param string $mainDomainName Main domain name
 * @return void
 */
function generatePageData($tpl, $ftpUserId, $mainDomainName)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT `homedir` FROM `ftp_users` WHERE `userid` = ?";
	$stmt = exec_query($query, $ftpUserId);

	$ftpHomeDir = $stmt->fields['homedir'];
	$customerHomeDir = $cfg->FTP_HOMEDIR . '/' . $mainDomainName;

	if ($ftpHomeDir == $customerHomeDir) {
		$customFtpHomeDir = '/';
	} else {
		$customFtpHomeDir = substr($ftpHomeDir, strlen($customerHomeDir));
	}

	$tpl->assign(
		array(
			'USERNAME' => tohtml($ftpUserId),
			'PASSWORD' => '',
			'PASSWORD_REPEAT' => '',
			'HOME_DIR' => (isset($_POST['home_dir'])) ? tohtml($_POST['home_dir']) : tohtml($customFtpHomeDir),
			'ID' => tohtml($ftpUserId),
		)
	);
}

/**
 * Update Ftp account
 *
 * @param string $userid Ftp userid
 * @param string $mainDomainName Main domain name
 * @return bool TRUE on success, FALSE on failure
 */
function updateFtpAccount($userid, $mainDomainName)
{
	$ret = true;

	if (!empty($_POST['password'])) {
		if (empty($_POST['password_repeat']) || $_POST['password'] !== $_POST['password_repeat']) {
			set_page_message(tr("Passwords do not match."), 'error');
			$ret = false;
		}

		if (!checkPasswordSyntax($_POST['password'])) {
			$ret = false;
		}

		$rawPassword = $_POST['password'];
		$password = cryptPasswordWithSalt($rawPassword);
	}

	if (isset($_POST['home_dir'])) {
		$homeDir = clean_input($_POST['home_dir']);

		if($homeDir != '/' && $homeDir != '') {
			// Strip possible double-slashes
			$homeDir = str_replace('//', '/', $homeDir);

			// Check for updirs '..'
			if (strpos($homeDir, '..') !== false) {
				set_page_message(tr('Invalid home directory.'), 'error');
				$ret = false;
			}

			if($ret) {
				$vfs = new iMSCP_VirtualFileSystem($mainDomainName);

				// Check for directory existence
				if (!$vfs->exists($homeDir)) {
					set_page_message(tr("Home directory '%s' doesn't exist", $homeDir), 'error');
					$ret = false;
				}
			}
		}
	} else {
		showBadRequestErrorPage();
		exit;
	}

	if($ret) {
		iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditFtp, array('ftpUserId' => $userid));

		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');
		$homeDir = rtrim(str_replace('//', '/', $cfg->FTP_HOMEDIR . '/' . $mainDomainName . '/' . $homeDir), '/');

		if (isset($rawPassword) && isset($password) && isset($homeDir)) {
			$query = "UPDATE `ftp_users` SET `passwd` = ?, `rawpasswd` = ?, `homedir` = ? WHERE `userid` = ?";
			exec_query($query, array($password, $rawPassword, $homeDir, $userid));
		} else {
			$query = "UPDATE `ftp_users` SET `homedir` = ? WHERE `userid` = ?";
			exec_query($query, array($homeDir, $userid));
		}

		iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditFtp, array('ftpUserId' => $userid));

		write_log(sprintf("%s updated Ftp account: %s", $_SESSION['user_logged'], $userid), E_USER_NOTICE);
		set_page_message(tr('FTP account successfully updated.'), 'success');
	}

	return $ret;
}

/***********************************************************************************************************************
 * Main
 */
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('ftp') or showBadRequestErrorPage();

if (isset($_GET['id'])) {
	$userid = clean_input($_GET['id']);

	if (who_owns_this($userid, 'ftpuser') != $_SESSION['user_id']) {
		showBadRequestErrorPage();
	}

	$stmt = exec_query("SELECT `domain_name` FROM `domain` WHERE`domain_admin_id` = ?", $_SESSION['user_id']);
	$mainDomainName = $stmt->fields['domain_name'];

	if (!empty($_POST)) {
		if(updateFtpAccount($userid, $mainDomainName)) {
			redirectTo('ftp_accounts.php');
		}
	}

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'client/ftp_edit.tpl',
			'page_message' => 'layout'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Client / FTP / Overview / Edit FTP Account'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_FTP_DIRECTORIES' => tojs(('Ftp directories')),
			'TR_CLOSE' => tojs(tr('Close')),
			'TR_FTP_USER_DATA' => tr('Ftp account data'),
			'TR_USERNAME' => tr('Username'),
			'TR_PASSWORD' => tr('Password'),
			'TR_PASSWORD_REPEAT' => tr('Repeat password'),
			'TR_HOME_DIR' => tr('Home directory'),
			'CHOOSE_DIR' => tr('Choose dir'),
			'TR_CHANGE' => tr('Update'),
			'TR_CANCEL' => tr('Cancel')
		)
	);

	generatePageData($tpl, $userid, $mainDomainName);
	generateNavigation($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
