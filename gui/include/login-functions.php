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

/**
 * Checks if an username exists
 *
 * @param  String $username Username to be checked
 * @return TRUE, if username exists, FALSE otherwise
 */
function username_exists($username) {

	$sql = ispCP_Registry::get('Db');

	$username = encode_idna($username);

	$query = '
		SELECT
			`admin_id`
		FROM
			`admin`
		WHERE
			`admin_name` = ?
		;
	';

	$res = exec_query($sql, $query, $username);

	return ($res->recordCount() == 1);
}

/**
 * Returns the stored data related to the username
 *
 * @param String $username Username that data should be returend
 * @return Array of user-related data
 */
function get_userdata($username) {

	$sql = ispCP_Registry::get('Db');

	$query = '
		SELECT
			*
		FROM
			`admin`
		WHERE
			`admin_name` = ?
		;
	';

	$res = exec_query($sql, $query, $username);

	return $res->fetchRow();
}

/**
 * Checks if the user account (domain name) is still vaild
 *
 * @param String $username Username that data should be checked
 * @return TRUE if still valid, FALSE otherwise
 */
function is_userdomain_expired($username) {

	$sql = ispCP_Registry::get('Db');

	$udata = get_userdata($username);

	if (!is_array($udata)) {
		return false;
	}

	if ($udata['admin_type'] != 'user') {
		return true;
	}

	$query = '
		SELECT
			`domain_expires`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
		;
	';

	$res = exec_query($sql, $query, $udata['admin_id']);

	$row = $res->fetchRow();

	$result = false;
	if (!empty($row['domain_expires'])) {
		if (time() > $row['domain_expires'])
			$result = true;
	}

	return $result;
}

/**
 * Checks if the user account's status is 'ok'
 *
 * @param string $username Username that data should be checked
 * @return TRUE if status is 'ok', FALSE otherwise
 */
function is_userdomain_ok($username) {

	$cfg = ispCP_Registry::get('Config');
	$sql = ispCP_Registry::get('Db');

	$udata = get_userdata($username);

	if (!is_array($udata)) {
		return false;
	}

	if ($udata['admin_type'] != 'user') {
		return true;
	}

	$query = '
		SELECT
			`domain_status`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
		;
	';

	$res = exec_query($sql, $query, $udata['admin_id']);

	$row = $res->fetchRow();

	return ($row['domain_status'] == $cfg->ITEM_OK_STATUS);
}

/**
 * @todo describe function
 *
 * @param  int $timeout
 * @param  string $type
 */
function unblock($timeout = null, $type = 'bruteforce') {

	$cfg = ispCP_Registry::get('Config');
	$sql = ispCP_Registry::get('Db');

	if ($timeout === null) {
		$timeout = $cfg->BRUTEFORCE_BLOCK_TIME;
	}

	$max = 0;

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
				;
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
				;
			";

			$max = $cfg->BRUTEFORCE_MAX_CAPTCHA;
			break;
		default:
			write_log(
				sprintf(
					'FIXME: %s:%d' . "\n" . 'Unknown unblock reason %s',
					__FILE__,
					__LINE__,
					$type
				)
			);

			die('FIXME: '.__FILE__.':'.__LINE__);
	}

	exec_query($sql, $query, array($max, $timeout));
}

/**
 * @todo describe function
 *
 * @param string $ipaddr
 * @param string $type
 * @param boolean $autodeny
 * @return TRUE, if...
 */
function is_ipaddr_blocked($ipaddr = null, $type = 'bruteforce',
	$autodeny = false) {

	$cfg = ispCP_Registry::get('Config');
	$sql = ispCP_Registry::get('Db');

	if ($ipaddr === null) {
		$ipaddr = getipaddr();
	}

	$max = 0;

	switch ($type) {
		case 'bruteforce':
			$query = "
				SELECT
					*
				FROM
					`login`
				WHERE
					`ipaddr` = ?
				AND
					`login_count` = ?
				;
			";

			$max = $cfg->BRUTEFORCE_MAX_LOGIN;
			break;
		case 'captcha':
			$query = "
				SELECT
					*
				FROM
					`login`
				WHERE
					`ipaddr` = ?
				AND
					`captcha_count` = ?
				;
			";

			$max = $cfg->BRUTEFORCE_MAX_CAPTCHA;
			break;
		default:
			write_log(
				sprintf(
					'FIXME: %s:%d' . "\n" . 'Unknown block reason %s',
					__FILE__,
					__LINE__,
					$type
				)
			);

			die('FIXME: '.__FILE__.':'.__LINE__);
	}

	$res = exec_query($sql, $query, array($ipaddr, $max));

	if ($res->recordCount() == 0) {
		return false;
	} else if (!$autodeny) {
		return true;
	}

	deny_access();
	return true;
}

/**
 * @todo describe function
 *
 * @param string $ipaddr
 * @param boolean $displayMessage
 * @return TRUE if...
 */
function shall_user_wait($ipaddr = null, $displayMessage = true) {

	$cfg = ispCP_Registry::get('Config');
	$sql = ispCP_Registry::get('Db');

	if (!$cfg->BRUTEFORCE) {
		return false;
	}
	if ($ipaddr === null) {
		$ipaddr = getipaddr();
	}

	$query = '
		SELECT
			`lastaccess`
		FROM
			`login`
		WHERE
			`ipaddr` = ?
		AND
			`user_name` is NULL
		;
	';

	$res = exec_query($sql, $query, $ipaddr);

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
			$backButtonDestination = $cfg->BASE_SERVER_VHOST_PREFIX .
				$cfg->BASE_SERVER_VHOST;

			system_message(
				tr(
					'You have to wait %d seconds.',
					$btime - time()
				),
				$backButtonDestination
			);
		}

		return true;
	}

	return false;
}

/**
 * Check/block IP if bruteforcing the login or captcha wrong
 *
 * @param string $ipaddr
 * @param string $type
 */
function check_ipaddr($ipaddr = null, $type = 'bruteforce') {

	$cfg = ispCP_Registry::get('Config');
	$sql = ispCP_Registry::get('Db');

	if ($ipaddr === null) {
		$ipaddr = getipaddr();
	}

	$sess_id = session_id();

	$query = "
		SELECT
			`session_id`,
			`ipaddr`,
			`user_name`,
			`lastaccess`,
			`login_count`,
			`captcha_count`
		FROM
			`login`
		WHERE
			`ipaddr` = ?
		AND
			`user_name` IS NULL
		;
	";

	$res = exec_query($sql, $query, $ipaddr);

	if ($res->recordCount() == 0) {
		$query = "
			REPLACE INTO `login` (
				`session_id`,
				`ipaddr`,
				`lastaccess`,
				`login_count`,
				`captcha_count`
			) VALUES (?,?,UNIX_TIMESTAMP(),?,?)
			;
		";

		exec_query(
			$sql,
			$query,
			array(
				$sess_id,
				$ipaddr,
				(int) ($type == 'bruteforce'),
				(int) ($type == 'captcha')
			)
		);
	}

	$data = $res->fetchRow();

	$lastaccess = $data['lastaccess'];
	$logincount = $data['login_count'];
	$captchacount = $data['captcha_count'];

	if ($type == 'bruteforce' && $cfg->BRUTEFORCE &&
		$logincount > $cfg->BRUTEFORCE_MAX_LOGIN) {
		block_ipaddr($ipaddr, 'Login');
	}

	if ($type == 'captcha' && $cfg->BRUTEFORCE &&
		$captchacount > $cfg->BRUTEFORCE_MAX_CAPTCHA && $cfg->BRUTEFORCE) {
		block_ipaddr($ipaddr, 'CAPTCHA');
	}

	if ($cfg->BRUTEFORCE_BETWEEN) {
		$btime = $lastaccess + $cfg->BRUTEFORCE_BETWEEN_TIME;
	} else {
		$btime = 0;
	}

	if ($btime < time()) {
		if ($type == 'bruteforce') {
			$query = "
				UPDATE
					`login`
				SET
					`lastaccess` = UNIX_TIMESTAMP(),
					`login_count` = `login_count`+1
				WHERE
					`ipaddr` = ?
				AND
					`user_name` IS NULL
				;
			";
		} else if ($type == 'captcha') {
			$query = "
				UPDATE
					`login`
				SET
					`lastaccess` = UNIX_TIMESTAMP(),
					`captcha_count` = `captcha_count`+1
				WHERE
					`ipaddr` = ?
				AND
					`user_name` IS NULL
				;
			";
		}

		exec_query($sql, $query, $ipaddr);
	} else {
		$backButtonDestination = $cfg->BASE_SERVER_VHOST_PREFIX . 
			$cfg->BASE_SERVER_VHOST;

		write_log(
			"Login error, <b><i>$ipaddr</i></b> wait " . ($btime - time()) .
				" seconds", E_USER_NOTICE
		);

		system_message(
			tr(
				'You have to wait %d seconds.',
				$btime - time()
			),
			$backButtonDestination
		);
	}
}

/**
 * @todo describe function
 *
 * @param string $ipaddr
 * @param string $type
 */
function block_ipaddr($ipaddr, $type = 'General') {

	$cfg = ispCP_Registry::get('Config');

	write_log(
			"$type protection, <b><i> " .
				htmlspecialchars($ipaddr, ENT_QUOTES, "UTF-8") .
					"</i></b> blocked for " . $cfg->BRUTEFORCE_BLOCK_TIME .
						" minutes."
	);

	deny_access();
}

/**
 * @todo describe function
 */
function deny_access() {

	$cfg = ispCP_Registry::get('Config');

	$backButtonDestination =
		$cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST;

	system_message(
		tr(
			'You have been blocked for %d minutes.',
			$cfg->BRUTEFORCE_BLOCK_TIME
		),
		$backButtonDestination
	);
}

/**
 * Returns the user's IP address
 *
 * @return user's IP address
 */
function getipaddr() {
	return $_SERVER['REMOTE_ADDR'];
}

/**
 * @todo describe function
 */
function do_session_timeout() {

	$cfg = ispCP_Registry::get('Config');
	$sql = ispCP_Registry::get('Db');

	$ttl = time() - $cfg->SESSION_TIMEOUT * 60;

	$query = "
		DELETE FROM
			`login`
		WHERE
			`lastaccess` < ?
		;
	";

	exec_query($sql, $query, $ttl);

	if (!session_exists(session_id())) {
		unset($_SESSION['user_logged']);
		unset_user_login_data();
	}
}

/**
 * Checks if an session already exists and the IP address is matching
 *
 * @param string $sess_id User session id from cookie
 * @return TRUE if session is valid
 */
function session_exists($sess_id) {

	$sql = ispCP_Registry::get('Db');

	$ip = getipaddr();

	$query = "
		SELECT
			`session_id`,
			`ipaddr`
		FROM
			`login`
		WHERE
			`session_id` = ?
		AND
			`ipaddr` = ?
		;
	";

	$res = exec_query($sql, $query, array($sess_id, $ip));

	return ($res->recordCount() == 1);
}
