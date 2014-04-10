<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Login
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 */

/**
 * Session garbage collector
 *
 * @return void
 */
function do_session_timeout()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// We must not remove bruteforce plugin data (AND `user_name` IS NOT NULL)
	$query = "DELETE FROM login WHERE lastaccess < ? AND user_name IS NOT NULL";
	exec_query($query, time() - $cfg->SESSION_TIMEOUT * 60);
}

/**
 * Initialize login
 *
 * @param iMSCP_Events_Manager_Interface $events Events Manager
 * @return void
 */
function init_login($events)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($cfg->BRUTEFORCE) {
		$bruteforce = new iMSCP_Authentication_Bruteforce();
		$bruteforce->register($events);
	}

	// Register listener method to check domain status and expire date when the onBeforeSetIdentity event is triggered
	$events->registerListener(iMSCP_Events::onBeforeSetIdentity, 'login_checkDomainAccount');
}

/**
 * Check domain account state (status and expires date)
 *
 * Note: Listen to the onBeforeSetIdentity event triggered in the iMSCP_Authentication component.
 *
 * @param iMSCP_Events_Event $event An iMSCP_Events_Events object representing an onBeforeSetIdentity event.
 * @return void
 */
function login_checkDomainAccount($event)
{
	/** @var $identity stdClass */
	$identity = $event->getParam('identity');

	if ($identity->admin_type == 'user') {
		$query = '
			SELECT
				domain_expires, domain_status, admin_status
			FROM
				domain
			INNER JOIN
				admin ON(domain_admin_id = admin_id)
			WHERE
				domain_admin_id = ?
        ';
		$stmt = exec_query($query, $identity->admin_id);

		$isAccountStateOk = true;

		if (($stmt->fields['admin_status'] != 'ok') || ($stmt->fields['domain_status'] != 'ok')) {
			$isAccountStateOk = false;
			set_page_message(
				tr('Your account is currently under maintenance or disabled. Please, contact your reseller.'), 'error'
			);
		} else {
			$domainExpireDate = $stmt->fields['domain_expires'];

			if ($domainExpireDate && $domainExpireDate < time()) {
				$isAccountStateOk = false;
				set_page_message(tr('Your account has expired.'), 'error');
			}
		}

		if (!$isAccountStateOk) {
			redirectTo('index.php');
		}
	}
}

/**
 * Check login
 *
 * @param string $userLevel User level (admin|reseller|user)
 * @param bool $preventExternalLogin If TRUE, external login is disallowed
 */
function check_login($userLevel = '', $preventExternalLogin = true)
{
	do_session_timeout();

	$auth = iMSCP_Authentication::getInstance();

	if (!$auth->hasIdentity()) {
		$auth->unsetIdentity(); // Ensure deletion of all entity data

		if (is_xhr()) {
			header('HTTP/1.0 403 Forbidden');
			exit;
		}

		redirectTo('/index.php');
	}

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$identity = $auth->getIdentity();

	if ($cfg->MAINTENANCEMODE && $identity->admin_type != 'admin' &&
		(!isset($_SESSION['logged_from_type']) || $_SESSION['logged_from_type'] != 'admin')
	) {
		$auth->unsetIdentity();
		redirectTo('/index.php');
	}

	// Check user level
	if (!empty($userLevel) && ($userType = $identity->admin_type) != $userLevel) {
		if ($userType != 'admin' && (!isset($_SESSION['logged_from']) || $_SESSION['logged_from'] != 'admin')) {
			$loggedUser = isset($_SESSION['logged_from']) ? $_SESSION['logged_from'] : $identity->admin_name;
			write_log('Warning! user |' . $loggedUser . '| requested |' . tohtml($_SERVER['REQUEST_URI']) .
				'| with REQUEST_METHOD |' . $_SERVER['REQUEST_METHOD'] . '|', E_USER_WARNING);
		}

		redirectTo('/index.php');
	}

	// prevent external login / check for referer
	if ($preventExternalLogin && !empty($_SERVER['HTTP_REFERER'])) {
		// Extracting hostname from referer URL
		// Note2: We remove any braket in referer (ipv6 issue)
		$refererHostname = str_replace(array('[', ']'), '', parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST));

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

	// If all goes fine update session and lastaccess
	$_SESSION['user_login_time'] = time();
	exec_query(
		'UPDATE login SET lastaccess = ? WHERE session_id = ?', array($_SESSION['user_login_time'], session_id())
	);
}

/**
 * Switch between user's interfaces
 *
 * @param int $fromId User ID to switch from
 * @param int $toId User ID to switch on
 * @return void
 */
function change_user_interface($fromId, $toId)
{
	$toActionScript = false;

	while (1) { // We loop over nothing here, it's just a way to avoid code repetition
		$query = '
			SELECT
				admin_id, admin_name, admin_type, email, created_by
			FROM
				admin
			WHERE
				admin_id IN(?, ?)
			ORDER BY
				FIELD(admin_id, ?, ?)
			LIMIT
				2
		';
		$stmt = exec_query($query, array($fromId, $toId, $fromId, $toId));

		if ($stmt->rowCount() < 2) {
			set_page_message(tr('Wrong request.'), 'error');
		}

		list($from, $to) = $stmt->fetchAll(PDO::FETCH_OBJ);

		$fromToMap = array();
		$fromToMap['admin']['BACK'] = 'manage_users.php';
		$fromToMap['admin']['reseller'] = 'index.php';
		$fromToMap['admin']['user'] = 'index.php';
		$fromToMap['reseller']['user'] = 'index.php';
		$fromToMap['reseller']['BACK'] = 'users.php';

		if (!isset($fromToMap[$from->admin_type][$to->admin_type]) || ($from->admin_type == $to->admin_type)) {
			if (isset($_SESSION['logged_from_id']) && $_SESSION['logged_from_id'] == $to->admin_id) {
				$toActionScript = $fromToMap[$to->admin_type]['BACK'];
			} else {
				set_page_message(tr('Wrong request.'), 'error');
				write_log(sprintf("%s tried to switch onto %s's interface", $from->admin_name, decode_idna($to->admin_name)), E_USER_WARNING);
				break;
			}
		}

		$toActionScript = ($toActionScript) ? $toActionScript : $fromToMap[$from->admin_type][$to->admin_type];

		// Set new identity
		$auth = iMSCP_Authentication::getInstance();
		$auth->unsetIdentity();
		$auth->setIdentity($to);

		if ($from->admin_type != 'user' && $to->admin_type != 'admin') {
			// Set additional data about user from wich we are logged from
			$_SESSION['logged_from_type'] = $from->admin_type;
			$_SESSION['logged_from'] = $from->admin_name;
			$_SESSION['logged_from_id'] = $from->admin_id;

			write_log(sprintf("%s switched onto %s's interface", $from->admin_name, decode_idna($to->admin_name)), E_USER_NOTICE);
		} else {
			write_log(sprintf("%s switched back from %s's interface", $to->admin_name, decode_idna($from->admin_name)), E_USER_NOTICE);
		}

		break;
	}

	redirectToUiLevel($toActionScript);
}

/**
 * Redirects to user ui level
 *
 * @throws iMSCP_Exception in case ui level is unknow
 * @param string $actionScript Action script on which user should be redirected
 * @return void
 */
function redirectToUiLevel($actionScript = 'index.php')
{
	$auth = iMSCP_Authentication::getInstance();

	if ($auth->hasIdentity()) {
		$userType = $auth->getIdentity()->admin_type;
		switch ($userType) {
			case 'user':
			case 'admin':
			case 'reseller':
				// Prevents display of any old message when switching to another user level
				Zend_Session::namespaceUnset('pageMessages');
				redirectTo('/' . (($userType == 'user') ? 'client' : $userType . '/' . $actionScript));
				exit;
			default:
				throw new iMSCP_Exception('Unknown UI level');
		}
	}
}

/**
 * Returns the user Ip address
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @return string User's Ip address
 * @todo move this function
 */
function getIpAddr()
{
	$ipAddr = (!empty($_SERVER['HTTP_CLIENT_IP'])) ? $_SERVER['HTTP_CLIENT_IP'] : false;

	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ipAddrs = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);

		if ($ipAddr) {
			array_unshift($ipAddrs, $ipAddr);
			$ipAddr = false;
		}

		$countIpAddrs = count($ipAddrs);

		// Loop over ip stack as long an ip out of private range is not found
		for ($i = 0; $i < $countIpAddrs; $i++) {
			if (filter_var($ipAddrs[$i], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
				$ipAddr = $ipAddrs[$i];
				break;
			}
		}
	}

	return ($ipAddr ? $ipAddr : $_SERVER['REMOTE_ADDR']);
}
