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
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Login
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2012 by i-MSCP | http://i-mscp.net
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 */

/**
 * Purge data for expired session.
 *
 * @return void
 */
function do_session_timeout()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// We must not remove bruteforce plugin data (AND `user_name` IS NOT NULL)
	$query = "DELETE FROM `login` WHERE `lastaccess` < ? AND `user_name` IS NOT NULL";
	exec_query($query, time() - $cfg->SESSION_TIMEOUT * 60);
}

/**
 * Checks if a session already exists and the IP address is matching.
 *
 * @param string $sessionId Session id from cookie
 * @return bool TRUE if session exists, FALSE otherwise
 */
function session_exists($sessionId)
{
	$query = "SELECT COUNT(`session_id`) `cnt` FROM `login` WHERE `session_id` = ? AND `ipaddr` = ?";
	$stmt = exec_query($query, array($sessionId, getipaddr()));

	return (bool) $stmt->fields['cnt'];
}

/**
 * Initialize login.
 *
 * @param iMSCP_Events_Manager_Interface $events Events Manager
 * @return void
 */
function init_login($events)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if($cfg->BRUTEFORCE) {
		$bruteforce = new iMSCP_Authentication_Bruteforce();
		$bruteforce->register($events);
	}

	// Register listener method to check domain status and expire date when the onBeforeSetIdentity event is triggered
	$events->registerListener(iMSCP_Events::onBeforeSetIdentity, 'login_checkDomainAccount');
}

/**
 * Check domain account state (status and expires date).
 *
 * Note: Acts as a listener for the onBeforeSetIdentity event triggered in the iMSCP_Authentication component.
 *
 * @param iMSCP_Events_Event $event An iMSCP_Events_Events object that represent an onBeforeSetIdentity event.
 * @return iMSCP_Authentication_Result
 */
function login_checkDomainAccount($event)
{
	/** @var $identity stdClass */
	$identity = $event->getParam('identity');

	if ($identity->admin_type == 'user') {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		$query = 'SELECT `domain_expires`, `domain_status` FROM `domain` WHERE `domain_admin_id` = ?';
		$stmt = exec_query($query, $identity->admin_id);

		$isAccountStateOk = true;

		if ($stmt->fields['domain_status'] != $cfg->ITEM_OK_STATUS) {
			$isAccountStateOk = false;
			set_page_message(tr('Domain account is in inconsistent state.'), 'error');
		} else {
			$domainExpireDate = $stmt->fields['domain_expires'];

			if($domainExpireDate && $domainExpireDate < time()) {
				$isAccountStateOk = false;
				set_page_message(tr('Domain account expired.'), 'error');
			}
		}

		if(!$isAccountStateOk) {
			redirectTo('index.php');
		}
	}
}

/**
 * Check login.
 *
 * @param string $fileName Action script filepath
 * @param bool $preventExternalLogin If TRUE, external login is disallowed
 */
function check_login($fileName = '', $preventExternalLogin = true)
{
	do_session_timeout();

	$auth = iMSCP_Authentication::getInstance();
	$identity = $auth->getIdentity();

	if (!$auth->hasIdentity()) {
		if (is_xhr()) {
			header('HTTP/1.0 403 Forbidden');
			exit;
		}

		redirectTo('/index.php');
	}

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg =iMSCP_Registry::get('config');

	if ($cfg->MAINTENANCEMODE && $identity->admin_type != 'admin' &&
		(!isset($_SESSION['logged_from_type']) || $_SESSION['logged_from_type'] !='admin')) {
		$auth->unsetIdentity();
		redirectTo('/index.php');
	}

	// If user login data correct - update session and lastaccess
	$_SESSION['user_login_time'] = time();

	$query = "UPDATE `login` SET `lastaccess` = ? WHERE `session_id` = ?";
	exec_query($query, array(time(), session_id()));

	// Check user level
	if (!empty($fileName)) {
		$levels = explode('/', realpath(dirname($fileName)));
		$level = $levels[count($levels) - 1];

		$userType = ($identity->admin_type == 'user') ? 'client' : $identity->admin_type;

		if ($userType != $level) {
			if ($userType != 'admin' && (!isset($_SESSION['logged_from']) || $_SESSION['logged_from'] != 'admin')) {

				$userLoggued = isset($_SESSION['logged_from']) ? $_SESSION['logged_from'] : $identity->admin_name;

				write_log('Warning! user |' . $userLoggued . '| requested |' . tohtml($_SERVER['REQUEST_URI']) .
					'| with REQUEST_METHOD |' . $_SERVER['REQUEST_METHOD'] . '|', E_USER_WARNING);
			}

			redirectTo('/index.php');
		}
	}

	// prevent external login / check for referer
	if ($preventExternalLogin) {
		// An user try to access the panel from another url ?
		if (!empty($_SERVER['HTTP_REFERER'])) {

			// Extracting hostname from referer URL
			$refererHostname = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

			// The URL does contains the host element ?
			if (!is_null($refererHostname)) {
				// Note1: We don't care about the scheme, we only want make parse_url() happy
				// Note2: We remove any braket in hostname (ipv6 issue)
				$http_host = str_replace(array('[', ']'), '', parse_url("http://{$_SERVER['HTTP_HOST']}", PHP_URL_HOST));

				// The referer doesn't match the panel hostname ?
				if (!in_array($refererHostname, array($http_host, $_SERVER['SERVER_NAME']))) {
					set_page_message(tr('Request from foreign host was blocked.'), 'info');

					# Quick fix for #96 (will be rewritten ASAP)
					isset($_SERVER['REDIRECT_URL']) ? : $_SERVER['REDIRECT_URL'] = '';

					if (!(substr($_SERVER['SCRIPT_FILENAME'], (int)-strlen($_SERVER['REDIRECT_URL']),
						strlen($_SERVER['REDIRECT_URL'])) == $_SERVER['REDIRECT_URL'])
					) {
						redirectToUiLevel();
					}
				}
			}
		}
	}
}

/**
 * Switch between user's interfaces.
 *
 * @param  int $fromId Unique identifier to switch from
 * @param  int $toId Unique identifier of user to switch on
 * @return void
 */
function change_user_interface($fromId, $toId)
{
	$auth = iMSCP_Authentication::getInstance();
	$index = null;

	while (1) {
		$query = "
			SELECT
				`admin_id`, `admin_name`, `admin_pass`, `admin_type`, `email`, `created_by`
			FROM
				`admin`
			WHERE
				`admin_id` = ?
		";

		$rsFrom = exec_query($query, $fromId);
		$rsTo = exec_query($query, $toId);

		if (!$rsFrom->rowCount() || !$rsTo->rowCount()) {
			set_page_message(tr('Wrong request'), 'error');
			break;
		}

		$fromUserData = $rsFrom->fetchRow();
		$toUserData = $rsTo->fetchRow();

		$toAdminType = $toUserData['admin_type'];
		$fromAdminType = $fromUserData['admin_type'];

		$allowedChanges = array();

		$allowedChanges['admin']['BACK'] = 'manage_users.php';
		$allowedChanges['admin']['reseller'] = 'index.php';
		$allowedChanges['admin']['user'] = 'index.php';
		$allowedChanges['admin']['BACK'] = 'manage_users.php';
		$allowedChanges['reseller']['user'] = 'index.php';
		$allowedChanges['reseller']['BACK'] = 'users.php';

		if (!isset($allowedChanges[$fromAdminType][$toAdminType]) || ($toAdminType == $fromAdminType)) {
			if (isset($_SESSION['logged_from_id']) && $_SESSION['logged_from_id'] == $toId) {
				$index = $allowedChanges[$toAdminType]['BACK'];
			} else {
				set_page_message(tr('Wrong request.'), 'error');
				write_log(
					sprintf(
						"%s tried to switch onto %s's interface",
						$fromUserData['admin_name'], decode_idna($toUserData['admin_name'])
					),
					E_USER_WARNING
				);
				break;
			}
		}

		$index = ($index) ? $index : $allowedChanges[$fromAdminType][$toAdminType];

		// Create new identity to set
		$identity = new stdClass();
		$identity->admin_name = $toUserData['admin_name'];
		$identity->admin_pass = $toUserData['admin_pass'];
		$identity->admin_type = $toUserData['admin_type'];
		$identity->admin_id = $toUserData['admin_id'];
		$identity->email = $toUserData['email'];
		$identity->created_by = $toUserData['created_by'];

		// Unset previous identity
		$auth->unsetIdentity();

		// Set new identity
		$auth->setIdentity($identity);

		if($fromAdminType != 'user' && $toAdminType != 'admin') {
			// Set additional data about logged from user
			$_SESSION['logged_from_type'] = $fromUserData['admin_type'];
			$_SESSION['logged_from'] = $fromUserData['admin_name'];
			$_SESSION['logged_from_id'] = $fromUserData['admin_id'];

			write_log(
				sprintf(
					"%s switched onto %s's interface", $fromUserData['admin_name'],
					decode_idna($toUserData['admin_name'])
				),
				E_USER_NOTICE
			);
		} else {
			write_log(
				sprintf(
					"%s switched back from %s's interface", $toUserData['admin_name'],
					decode_idna($fromUserData['admin_name'])
				),
				E_USER_NOTICE
			);
		}

		break;
	}

	redirectToUiLevel($index);
}

/**
 * Redirects to user level page.
 *
 * @param  string $file action script filepath to redirect on
 * @return void
 */
function redirectToUiLevel($file = 'index.php')
{
	$auth = iMSCP_Authentication::getInstance();

	if ($auth->hasIdentity()) {
		switch (($userType = $auth->getIdentity()->admin_type)) {
			case 'user':
				$userType = 'client';
			case 'admin':
			case 'reseller':
				// Prevents display of any old message when switching to another user level
				Zend_Session::namespaceUnset('pageMessages');
				redirectTo('/' . $userType . '/' . $file);
		}
	}
}

/**
 * Returns the user's Ip address
 *
 * @return string User's Ip address
 * @todo adding proxy detection
 */
function getipaddr()
{
	return $_SERVER['REMOTE_ADDR'];
}
