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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Login
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2011 by i-MSCP | http://i-mscp.net
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 */

/**
 * Should be documented.
 *
 * @return void
 */
function do_session_timeout()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$ttl = time() - $cfg->SESSION_TIMEOUT * 60;

	$query = "DELETE FROM `login` WHERE `lastaccess` < ?";

	exec_query($query, $ttl);

	if (!session_exists(session_id())) {
		unset($_SESSION['user_logged']);
		unset_user_login_data();
	}
}

/**
 * Checks if an session already exists and the IP address is matching.
 *
 * @param string $sessionId User session id from cookie
 * @return bool TRUE if session is valid
 */
function session_exists($sessionId)
{
	$ip = getipaddr();

	$query = "
		SELECT
			`session_id`, `ipaddr`
		FROM
			`login`
		WHERE
			`session_id` = ?
		AND
			`ipaddr` = ?
	 ";
	$stmt = exec_query($query, array($sessionId, $ip));

	return (bool)$stmt->recordCount();
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

/**
 * Should be documented.
 *
 * @return void
 */
function init_login()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// Just make sure to expire counters in case BRUTEFORCE is turned off
	unblock($cfg->BRUTEFORCE_BLOCK_TIME);

	if ($cfg->BRUTEFORCE) {
		is_ipaddr_blocked(null, 'bruteforce', true);
	}
}

/**
 * Checks if an user account name exists.
 *
 * @param  string $userName User account name
 * @return bool TRUE if the user account name exists, FALSE otherwise
 */
function username_exists($userName)
{
	$userName = encode_idna($userName);

	$query = 'SELECT `admin_id` FROM `admin` WHERE `admin_name` = ?;';
	$stmt = exec_query($query, $userName);

	return (bool)$stmt->recordCount();
}

/**
 * Returns the stored data related to an user account.
 *
 * @param string $userName User Account name
 * @return array An array that contains user account related data
 */
function get_userdata($userName)
{
	$query = 'SELECT * FROM `admin` WHERE `admin_name` = ?;';
	$stmt = exec_query($query, $userName);

	return $stmt->fetchRow();
}

/**
 * Checks if an user account is expired.
 *
 * @param string $userName User account name
 * @return bool TRUE user account is not expired, FALSE otherwise
 */
function is_userdomain_expired($userName)
{
	$userData = get_userdata($userName);

	if (!is_array($userData)) {
		return false;
	}

	if ($userData['admin_type'] != 'user') {
		return true;
	}

	$query = 'SELECT `domain_expires` FROM `domain` WHERE `domain_admin_id` = ?;';
	$stmt = exec_query($query, $userData['admin_id']);

	$row = $stmt->fetchRow();

	if (!empty($row) && $row['domain_expires'] > 0 && time() > $row['domain_expires']) {
		return true;
	}

	return false;
}

/**
 * Checks if an user account's status is 'ok'
 *
 * @param string $userName User account name to be checked
 * @return bool TRUE if the user account's status is 'ok', FALSE otherwise
 */
function is_userdomain_ok($userName)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$userData = get_userdata($userName);

	if (!is_array($userData)) {
		return false;
	} elseif ($userData['admin_type'] != 'user') {
		return true;
	}

	$query = 'SELECT `domain_status` FROM `domain` WHERE `domain_admin_id` = ?;';
	$stmt = exec_query($query, $userData['admin_id']);
	$row = $stmt->fetchRow();

	return (bool)($row['domain_status'] == $cfg->ITEM_OK_STATUS);
}

/**
 * Unblock Ip address.
 *
 * @throw iMSCP_Exception
 * @param  $timeout
 * @param string $type
 * @return void
 */
function unblock($timeout = null, $type = 'bruteforce')
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (is_null($timeout)) {
		$timeout = $cfg->BRUTEFORCE_BLOCK_TIME;
	}

	$timeout = time() - ($timeout * 60);

	switch ($type) {
		case 'bruteforce':
			$query = "
				UPDATE
					`login`
				SET
					`login_count` = '1'
				WHERE
					`login_count` >= ?
				AND
					`lastaccess` < ?
				AND
					`user_name` is NULL
			";
			$max = $cfg->BRUTEFORCE_MAX_LOGIN;
			break;
		case 'captcha':
			$query = "
				UPDATE
					`login`
				SET
					`captcha_count` = '1'
				WHERE
					`captcha_count` >= ?
				AND
					`lastaccess` < ?
				AND
					`user_name` is NULL
			";
			$max = $cfg->BRUTEFORCE_MAX_CAPTCHA;
			break;
		default:
			write_log(sprintf('FIXME: %s:%d' . "\n" . 'Unknown unblock reason %s', __FILE__, __LINE__, $type), E_USER_ERROR);
			throw new iMSCP_Exception('FIXME: ' . __FILE__ . ':' . __LINE__);
	}

	exec_query($query, array($max, $timeout));
}

/**
 * Checks if an user Ip address is blocked.
 *
 * @throw iMSCP_Exception_Production
 * @param string $ipAddress Ip Address to be checked
 * @param string $type Checking type (bruteforce|captcha)
 * @param bool $autoDeny
 * @return boolean TRUE if the user Ip address is blocked, FALSE otherwise
 */
function is_ipaddr_blocked($ipAddress = null, $type = 'bruteforce', $autoDeny = false)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// Fix for #47: Enhancement - You are blocked for 30 minutes
	// Read the ticket for further explainations
	if (isset($cfg->GUI_BYPASS_BRUTEFORCE) && intval($cfg->GUI_BYPASS_BRUTEFORCE)) {
		$ipAddress = getipaddr();
		$query = '
			UPDATE
		 		`login`
		 	SET
		 		`lastaccess` = UNIX_TIMESTAMP(),
		 		`login_count` = ?,
		 		`captcha_count` = ?

		 	WHERE
		 		ipaddr = ?
		 ';
		exec_query($query, array(0, 0, $ipAddress));
		return false;
	}

	if (is_null($ipAddress)) {
		$ipAddress = getipaddr();
	}

	switch ($type) {
		case 'bruteforce':
			$query = "SELECT * FROM `login` WHERE `ipaddr` = ? AND `login_count` = ?";
			$max = $cfg->BRUTEFORCE_MAX_LOGIN;
			break;
		case 'captcha':
			$query = "SELECT * FROM `login` WHERE `ipaddr` = ? AND `captcha_count` = ?";
			$max = $cfg->BRUTEFORCE_MAX_CAPTCHA;
			break;
		default:
			write_log(sprintf('FIXME: %s:%d' . "\n" . 'Unknown block reason %s', __FILE__, __LINE__, $type), E_USER_ERROR);
			throw new iMSCP_Exception('FIXME: ' . __FILE__ . ':' . __LINE__);
	}

	$stmt = exec_query($query, array($ipAddress, $max));

	if (!$stmt->recordCount()) {
		return false;
	} elseif (!$autoDeny) {
		return true;
	}

	deny_access();
	return false; // Only to make some IDE happy

}

/**
 * Determine if the user should wait for login
 *
 * @throw iMSCP_Exception_Production
 * @param string $ipAddress User ip address
 * @param boolean $displayMessage
 * @return TRUE if...
 */
function shall_user_wait($ipAddress = null, $displayMessage = true)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (!$cfg->BRUTEFORCE) {
		return false;
	}

	if (is_null($ipAddress)) {
		$ipAddress = getipaddr();
	}

	$query = 'SELECT `lastaccess` FROM `login` WHERE `ipaddr` = ? AND `user_name` is NULL;';
	$res = exec_query($query, $ipAddress);

	if ($res->recordCount() == 0) {
		return false;
	}

	$data = $res->fetchRow();
	$lastaccess = $data['lastaccess'];

	if ($cfg->BRUTEFORCE_BETWEEN) {
		$btime = $lastaccess + $cfg->BRUTEFORCE_BETWEEN_TIME;
	} else {
		return false;
	}

	if ($btime > time()) {
		if ($displayMessage) {
			iMSCP_Registry::set('backButtonDestination', $cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST);
			throw new iMSCP_Exception_Production(tr('You have to wait %d seconds.', $btime - time()));
		}

		return true;
	}

	return false;
}

/**
 * Check/block IP for login/lostpassword if the bruteforce feature is enabled
 *
 * @throw iMSCP_Exception_Production
 * @param string $ipAddress User address ip
 * @param string $type Type of bruteforce detection (login|captcha)
 * @return void
 */
function check_ipaddr($ipAddress = null, $type = 'bruteforce')
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (is_null($ipAddress)) {
		$ipAddress = getipaddr();
	}

	$sessionId = session_id();

	$query = "
		SELECT
			`session_id`, `ipaddr`, `user_name`, `lastaccess`, `login_count`, `captcha_count`
		FROM
			`login`
		WHERE
			`ipaddr` = ?
		AND
			`user_name` IS NULL
	";
	$stmt = exec_query($query, $ipAddress);

	// First attempt ?
	if ($stmt->recordCount() == 0) {
		$query = "
			REPLACE INTO
				`login` (
					`session_id`, `ipaddr`, `lastaccess`, `login_count`, `captcha_count`
				) VALUES (
					?, ?, UNIX_TIMESTAMP(), ?, ?
				)
		";

		exec_query($query, array($sessionId, $ipAddress, (int)($type == 'bruteforce'), (int)($type == 'captcha')));

		return;
	} elseif ($cfg->BRUTEFORCE) {
		$data = $stmt->fetchRow();
		$lastAccess = $data['lastaccess'];
		$loginCount = $data['login_count'];
		$captchaCount = $data['captcha_count'];

		if ($type == 'bruteforce' && $loginCount > $cfg->BRUTEFORCE_MAX_LOGIN) {
			block_ipaddr($ipAddress, 'Login');
		}

		if ($type == 'captcha' && $captchaCount > $cfg->BRUTEFORCE_MAX_CAPTCHA) {
			block_ipaddr($ipAddress, 'CAPTCHA');
		}

		if ($cfg->BRUTEFORCE_BETWEEN) {
			$btime = $lastAccess + $cfg->BRUTEFORCE_BETWEEN_TIME;
		} else {
			$btime = 0;
		}

		// Updating brute force counters
		if ($btime < time()) {
			if ($type == 'bruteforce') {
				$query = "
					UPDATE
						`login`
					SET
						`lastaccess` = UNIX_TIMESTAMP(),
						`login_count` = `login_count` +1
					WHERE
						`ipaddr` = ?
					AND
						`user_name` IS NULL
				";
			} else if ($type == 'captcha') {
				$query = "
					UPDATE
						`login`
					SET
						`lastaccess` = UNIX_TIMESTAMP(),
						`captcha_count` = `captcha_count` +1
					WHERE
						`ipaddr` = ?
					AND
						`user_name` IS NULL
				";
			}

			exec_query($query, $ipAddress);
		} else {
			write_log("Login error, <b><i>$ipAddress</i></b> wait " . ($btime - time()) . " seconds", E_USER_WARNING);
			iMSCP_Registry::set('backButtonDestination', $cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST);
			throw new iMSCP_Exception_Production(tr('You have to wait %d seconds.', $btime - time()));
		}
	}
}

/**
 * Block an user Ip Address.
 *
 * @param string $ipAddress User Ip Address
 * @param string $type Tyoe if blockage
 */
function block_ipaddr($ipAddress, $type = 'General')
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	write_log("$type protection, <b><i> " . tohtml($ipAddress) .
		"</i></b> blocked for " . $cfg->BRUTEFORCE_BLOCK_TIME . " minutes.", E_USER_WARNING);

	deny_access();
}

/**
 * Display an informational deny message
 *
 * @throw iMSCP_Exception_Production
 * @return void
 */
function deny_access()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	iMSCP_Registry::set('backButtonDestination', $cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST);
	throw new iMSCP_Exception_Production(tr('You have been blocked for %d minutes.', $cfg->BRUTEFORCE_BLOCK_TIME));
}

/**
 * Authenticate an user and redirect it to his interface
 *
 * @throw iMSCP_Exception|iMSCP_Exception_Production
 * @param string $userName User name
 * @param string $userPassword User password
 * @return FALSE on error
 */
function register_user($userName, $userPassword)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	check_ipaddr();

	if (!username_exists($userName)) {
		write_log(tr('Login error, <b><i>%s</i></b> unknown username', tohtml($userName)), E_USER_NOTICE);

		set_page_message(tr('You entered an incorrect username.'), 'error');
		return false;
	}

	$userData = get_userdata($userName);

	if ((iMSCP_Update_Database::getInstance()->isAvailableUpdate() ||
		($cfg->MAINTENANCEMODE)) && $userData['admin_type'] != 'admin'
	) {
		write_log(tr('Login error, <b><i>%s</i></b> system currently in maintenance mode', tohtml($userName)), E_USER_NOTICE);
		set_page_message(tr('System is currently under maintenance. Only administrators can login.'));
		return false;
	}

	if (crypt($userPassword, $userData['admin_pass']) == $userData['admin_pass'] || md5($userPassword) == $userData['admin_pass']) {

		if (isset($_SESSION['user_logged'])) {
			write_log(tr('%s user already logged or session sharing problem! Aborting...', $userName), E_USER_WARNING);
			throw new iMSCP_Exception(tr('User already logged or session sharing problem! Aborting...'));
		}

		if (!is_userdomain_ok($userName)) {
			write_log(tr('%s\'s account status is not ok!', $userName), E_USER_WARNING);
			throw new iMSCP_Exception(tr('%s\'s account status is not ok.', $userName));
		}

		if ($userData['admin_type'] == 'user' && is_userdomain_expired($userName)) {
			write_log(tr('%s\'s domain expired!', $userName), E_USER_NOTICE);
			throw new iMSCP_Exception(tr('%s\'s domain expired.', tohtml($userName)));
		}

		$sessionId = session_id();
		$query = 'UPDATE `login` SET `user_name` = ?, `lastaccess` = ? WHERE `session_id` = ?';
		exec_query($query, array($userName, time(), $sessionId));

		$_SESSION['user_logged'] = $userData['admin_name'];
		$_SESSION['user_pass'] = $userData['admin_pass'];
		$_SESSION['user_type'] = $userData['admin_type'];
		$_SESSION['user_id'] = $userData['admin_id'];
		$_SESSION['user_email'] = $userData['email'];
		$_SESSION['user_created_by'] = $userData['created_by'];
		$_SESSION['user_login_time'] = time();

		write_log(tr('%s logged in.', tohtml($userName)), E_USER_NOTICE);
	} else {
		write_log(tr('%s entered incorrect password.', tohtml($userName)), E_USER_NOTICE);
		set_page_message('You entered an incorrect password.', 'error');
		return false;
	}

	// Redirect the user to his level interface
	redirect_to_level_page();
}

/**
 * Check user login.
 *
 * @return boolean
 */
function check_user_login()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$sessionId = session_id();

	// kill timed out sessions
	do_session_timeout();

	$userLogged = isset($_SESSION['user_logged']) ? $_SESSION['user_logged'] : false;

	if (!$userLogged) return false;

	$userPassword = $_SESSION['user_pass'];
	$userType = $_SESSION['user_type'];
	$userId = $_SESSION['user_id'];

	// verify session data with database
	$query = "
		SELECT
			*
		FROM
			`admin`, `login`
		WHERE
			admin.`admin_name` = ?
		AND
			admin.`admin_pass` = ?
		AND
			admin.`admin_type` = ?
		AND
			admin.`admin_id` = ?
		AND
			login.`session_id` = ?
	";

	$rs = exec_query($query, array($userLogged, $userPassword, $userType, $userId, $sessionId));

	if ($rs->recordCount() != 1) {
		write_log("Detected session manipulation on " . $userLogged . "'s session!", E_USER_WARNING);
		unset_user_login_data();

		return false;
	}

	if ((iMSCP_Update_Database::getInstance()->isAvailableUpdate() || ($cfg->MAINTENANCEMODE)) &&
		$userType != 'admin'
	) {
		unset_user_login_data(true);
		write_log("System is currently in maintenance mode. Logging out <b><i>" . $userLogged . "</i></b>", E_USER_NOTICE);
		redirectTo('/index.php');
	}

	// If user login data correct - update session and lastaccess
	$_SESSION['user_login_time'] = time();

	$query = "UPDATE `login` SET `lastaccess` = ? WHERE `session_id` = ?";

	exec_query($query, array(time(), $sessionId));

	return true;
}

/**
 * check for valid user login and valid file request/call
 *
 * @param string $fileName Full file path (ie. the magic __FILE__ constant value)
 * @param boolean $preventExternalLogin Check HTTP Referer for valid
 * request/call (ie. to prevent login from external websites)
 */
function check_login($fileName = null, $preventExternalLogin = true)
{
	if (!check_user_login()) {
		if (is_xhr()) {
			header('HTTP/1.0 403 Forbidden');
			exit;
		}

		redirectTo('/index.php');
	}

	// Check user level
	if (!is_null($fileName)) {

		$levels = explode('/', realpath(dirname($fileName)));
		$level = $levels[count($levels) - 1];

		$userType = ($_SESSION['user_type'] == 'user') ? 'client'
			: $_SESSION['user_type'];

		if ($userType != $level) {
			if ($userType != 'admin' &&
				(!isset($_SESSION['logged_from']) ||
					$_SESSION['logged_from'] != 'admin')
			) {

				$userLoggued = isset($_SESSION['logged_from'])
					? $_SESSION['logged_from'] : $_SESSION['user_logged'];

				write_log('Warning! user |' . $userLoggued . '| requested |' .
					tohtml($_SERVER['REQUEST_URI']) .
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
						redirect_to_level_page();
					}
				}
			}
		}
	}
}

/**
 * Switch between user's interfaces
 *
 * This function allows to switch between user's interfaces for admin and
 * reseller user accounts.
 *
 * @param  $fromId User's id that want switch to an other user's interface
 * @param  $toId User identifier that represents the destination interface
 * @return void
 */
function change_user_interface($fromId, $toId)
{
	$index = null;

	while (1) {
		$query = "
			SELECT
				`admin_id`, `admin_name`, `admin_pass`, `admin_type`, `email`,
				`created_by`
			FROM
				`admin`
			WHERE
				binary `admin_id` = ?
		";

		$rsFrom = exec_query($query, $fromId);
		$rsTo = exec_query($query, $toId);

		if (($rsFrom->recordCount()) != 1 || ($rsTo->recordCount()) != 1) {
			set_page_message(tr('User does not exist or you do not have permission to access this interface.'), 'warning');
			break;
		}

		$fromUserData = $rsFrom->fetchRow();
		$toUserData = $rsTo->fetchRow();

		if (!is_userdomain_ok($toUserData['admin_name'])) {
			set_page_message(tr("%s's account status is not ok.", decode_idna($toUserData['admin_name'])), 'warning');
			break;
		}

		$toAdminType = strtolower($toUserData['admin_type']);
		$fromAdminType = strtolower($fromUserData['admin_type']);

		$allowedChanges = array();

		$allowedChanges['admin']['admin'] = 'manage_users.php';
		$allowedChanges['admin']['BACK'] = 'manage_users.php';
		$allowedChanges['admin']['reseller'] = 'index.php';
		$allowedChanges['admin']['user'] = 'index.php';
		$allowedChanges['reseller']['user'] = 'index.php';
		$allowedChanges['reseller']['BACK'] = 'users.php?psi=last';

		if (!isset($allowedChanges[$fromAdminType][$toAdminType]) ||
			($toAdminType == $fromAdminType && $fromAdminType != 'admin')
		) {

			if (isset($_SESSION['logged_from_id']) && $_SESSION['logged_from_id'] == $toId) {
				$index = $allowedChanges[$toAdminType]['BACK'];
				//$restore = true;
			} else {
				set_page_message(tr('You do not have permission to access this interface.'), 'error');
				break;
			}
		}

		$index = $index ? $index : $allowedChanges[$fromAdminType][$toAdminType];

		//unset_user_login_data(false, $restore);
		unset_user_login_data(false, true);

		if (($toAdminType != 'admin' && ((isset($_SESSION['logged_from_id']) &&
			$_SESSION['logged_from_id'] != $toId)
			|| !isset($_SESSION['logged_from_id'])))
			|| ($fromAdminType == 'admin' && $toAdminType == 'admin')
		) {

			$_SESSION['logged_from'] = $fromUserData['admin_name'];
			$_SESSION['logged_from_id'] = $fromUserData['admin_id'];

		}

		if ($fromAdminType == 'user') {
			unset($_SESSION['logged_from'], $_SESSION['logged_from_id']);
		}

		unset($_SESSION['admin_name'], $_SESSION['admin_id'], $GLOBALS['admin_name'], $GLOBALS['admin_id']);

		$_SESSION['user_logged'] = $toUserData['admin_name'];
		$_SESSION['user_pass'] = $toUserData['admin_pass'];
		$_SESSION['user_type'] = $toUserData['admin_type'];
		$_SESSION['user_id'] = $toUserData['admin_id'];
		$_SESSION['user_email'] = $toUserData['email'];
		$_SESSION['user_created_by'] = $toUserData['created_by'];
		$_SESSION['user_login_time'] = time();

		$query = "
			REPLACE INTO
				`login` (
					`session_id`, `ipaddr`, `user_name`, `lastaccess`
				) VALUES (
					?, ?, ?, ?
				)
			";

		exec_query($query, array(session_id(), getipaddr(), $toUserData['admin_name'], $_SESSION['user_login_time']));

		write_log(sprintf("%s changes into %s's interface",
			decode_idna($fromUserData['admin_name']),
			decode_idna($toUserData['admin_name'])), E_USER_NOTICE);

		break;
	}

	redirect_to_level_page($index);
}

/**
 * Unset user login data.
 *
 * @param bool $ignorePreserve
 * @param bool $restore restore rembered user data
 * @return void
 */
function unset_user_login_data($ignorePreserve = false, $restore = false)
{
	if (isset($_SESSION['user_logged'])) {

		$sessionId = session_id();
		$adminName = $_SESSION['user_logged'];

		$query = "DELETE FROM `login` WHERE `session_id` = ? AND `user_name` = ?";
		exec_query($query, array($sessionId, $adminName));
	}

	$_SESSION['user_id'] = isset($_SESSION['logged_from_id']) ? $_SESSION['logged_from_id'] : $_SESSION['user_id'];

	$preserveList = array(
		'user_id', 'user_def_lang', 'user_theme', 'user_theme_color',
		'uistack', 'user_page_message', 'user_page_message_cls'
	);

	$preserveVals = array();

	if (!$ignorePreserve) {
		foreach ($preserveList as $p) {
			if (isset($_SESSION[$p])) {
				$preserveVals[$p] = $_SESSION[$p];
			}
		}
	}

	$_SESSION = array();

	foreach ($preserveList as $p) {
		if (isset($preserveVals[$p])) {
			$_SESSION[$p] = $preserveVals[$p];
		}
	}

	if ($restore && isset($_SESSION['uistack'])) {
		foreach ($_SESSION['uistack'] as $key => $value) {
			$_SESSION[$key] = $value;
		}

		unset($_SESSION['uistack']);
	}
}

/**
 * Redirects to user level page
 *
 * @param  $file
 * @param bool $force
 * @return bool
 */
function redirect_to_level_page($file = null, $force = false)
{
	if (!isset($_SESSION['user_type']) && !$force)
		return false;

	if (!$file) {
		$file = 'index.php';
	}

	$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

	switch ($userType) {
		case 'user':
			$userType = 'client';
		case 'admin':
		case 'reseller':
			redirectTo('/' . $userType . '/' . $file);
			exit;
		default:
			redirectTo('/index.php');
			exit;
	}
}
