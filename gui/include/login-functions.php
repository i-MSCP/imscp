<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

function username_exists($username) {
	$sql = Database::getInstance();

	$query = 'SELECT `admin_id` FROM `admin` WHERE `admin_name` = ?';
	$res = exec_query($sql, $query, array($username));

	return ($res->RecordCount() == 1);
}

function get_userdata($username) {
	$sql = Database::getInstance();

	$query = 'SELECT * FROM `admin` WHERE `admin_name` = ?';
	$res = exec_query($sql, $query, array($username));

	return $res->FetchRow();

}

function is_userdomain_ok($username) {
	$sql = Database::getInstance();

	$udata = get_userdata($username);

	if (!is_array($udata)) {
		return false;
	}

	if ($udata['admin_type'] != 'user') {
		return true;
	}

	$query = 'SELECT `domain_status` FROM `domain` WHERE `domain_admin_id` = ?';

	$res = exec_query($sql, $query, array($udata['admin_id']));

	$row = $res->FetchRow();

	return ($row['domain_status'] == Config::get('ITEM_OK_STATUS'));
}

function unblock($timeout = null, $type = 'bruteforce') {
	$sql = Database::getInstance();

	if ($timeout === null) {
		$timeout = Config::get('BRUTEFORCE_BLOCK_TIME');
	}

	$max = 0;

	$timeout = time() - ($timeout * 60);

	switch ($type) {
		case 'bruteforce':
			$query = "UPDATE `login` SET `login_count` = '1' WHERE `login_count` >= ? AND `lastaccess` < ? AND `user_name` is NULL";
			$max = Config::get('BRUTEFORCE_MAX_LOGIN');
			break;
		case 'captcha':
			$query = "UPDATE `login` SET `captcha_count` = '1' WHERE `captcha_count` >= ? AND `lastaccess` < ? AND `user_name` is NULL";
			$max = Config::get('BRUTEFORCE_MAX_CAPTCHA');
			break;
		default:
			die('FIXME: '.__FILE__.':'.__LINE__);
			break;
	}

	exec_query($sql, $query, array($max, $timeout));

}

function is_ipaddr_blocked($ipaddr = null, $type = 'bruteforce', $autodeny = false) {
	$sql = Database::getInstance();

	if ($ipaddr === null) {
		$ipaddr = getipaddr();
	}

	$max = 0;

	switch ($type) {
		case 'bruteforce':
			$query = "SELECT * FROM `login` WHERE `ipaddr` = ? AND `login_count` = ?";
			$max = Config::get('BRUTEFORCE_MAX_LOGIN');
			break;
		case 'captcha':
			$query = "SELECT * FROM `login` WHERE `ipaddr` = ? AND `captcha_count` = ?";
			$max = Config::get('BRUTEFORCE_MAX_CAPTCHA');
			break;
		default:
			die('FIXME: '.__FILE__.':'.__LINE__);
			break;
	}
	$res = exec_query($sql, $query, array($ipaddr, $max));

	if ($res->RecordCount() == 0) {
		return false;
	} else if (!$autodeny) {
		return true;
	}

	deny_access();
	return true;
}

function shall_user_wait($ipaddr = null, $displayMessage = true) {
	$sql = Database::getInstance();

	if (!Config::get('BRUTEFORCE')) {
		return false;
	}
	if ($ipaddr === null) {
		$ipaddr = getipaddr();
	}

	$query = 'SELECT `lastaccess` FROM `login` WHERE `ipaddr` = ? AND `user_name` is NULL';
	$res = exec_query($sql, $query, array($ipaddr));

	if ($res->RecordCount() == 0) {
		return false;
	}

	$data = $res->FetchRow();

	$lastaccess = $data['lastaccess'];

	if (Config::get('BRUTEFORCE_BETWEEN')) {
		$btime = $lastaccess + Config::get('BRUTEFORCE_BETWEEN_TIME');
	} else {
		return false;
	}

	if ($btime > time()) {
		if ($displayMessage) {
			$backButtonDestination = Config::get('BASE_SERVER_VHOST_PREFIX') . Config::get('BASE_SERVER_VHOST');
			system_message(tr('You have to wait %d seconds.', $btime - time()), $backButtonDestination);
		}
		return true;
	} else {
		return false;
	}

}

function check_ipaddr($ipaddr = null, $type = "bruteforce") {
	$sql = Database::getInstance();

	if ($ipaddr === null) {
		$ipaddr = getipaddr();
	}

	$sess_id = session_id();
	$query = "SELECT `session_id`, `ipaddr`, `user_name`, `lastaccess`, `login_count`, `captcha_count` FROM `login` WHERE `ipaddr` = ? AND `user_name` IS NULL";
	$res = exec_query($sql, $query, array($ipaddr));

	if ($res->RecordCount() == 0) {
		$query = "REPLACE INTO `login` (`session_id`, `ipaddr`, `lastaccess`, `login_count`, `captcha_count`) VALUES (?,?,UNIX_TIMESTAMP(),?,?)";
		exec_query($sql, $query, array($sess_id, $ipaddr, (int)($type == 'bruteforce'), (int)($type == 'captcha')));
		return false;
	}

	$data = $res->FetchRow();

	$lastaccess = $data['lastaccess'];
	$logincount = $data['login_count'];
	$captchacount = $data['captcha_count'];

	if ($type == 'bruteforce' && Config::get('BRUTEFORCE')
		&& $logincount > Config::get('BRUTEFORCE_MAX_LOGIN')) {
		block_ipaddr($ipaddr, 'Login');
	}

	if ($type == 'captcha' && Config::get('BRUTEFORCE')
		&& $captchacount > Config::get('BRUTEFORCE_MAX_CAPTCHA') && Config::get('BRUTEFORCE')) {
		block_ipaddr($ipaddr, 'CAPTCHA');
	}

	if (Config::get('BRUTEFORCE_BETWEEN')) {
		$btime = $lastaccess + Config::get('BRUTEFORCE_BETWEEN_TIME');
	} else {
		$btime = 0;
	}

	if ($btime < time()) {
		if ($type == "bruteforce") {
			$query = "UPDATE `login` SET `lastaccess` = UNIX_TIMESTAMP(), `login_count` = `login_count`+1 WHERE `ipaddr` = ? AND `user_name` IS NULL";
		} else if ($type == "captcha") {
			$query = "UPDATE `login` SET `lastaccess` = UNIX_TIMESTAMP(), `captcha_count` = `captcha_count`+1 WHERE `ipaddr` = ? AND `user_name` IS NULL";
		}

		exec_query($sql, $query, $ipaddr);
		return false;
	} else {
		$backButtonDestination = Config::get('BASE_SERVER_VHOST_PREFIX') . Config::get('BASE_SERVER_VHOST');

		write_log("Login error, <b><i>$ipaddr</i></b> wait " . ($btime - time()) . " seconds", E_USER_NOTICE);
		system_message(tr('You have to wait %d seconds.', $btime - time()), $backButtonDestination);

		return false;
	}
}

function block_ipaddr($ipaddr, $type = 'General') {
	write_log("$type protection, <b><i> " . htmlspecialchars($ipaddr, ENT_QUOTES, "UTF-8") . "</i></b> blocked for " . Config::get('BRUTEFORCE_BLOCK_TIME') . " minutes.");
	deny_access();
}

function deny_access() {
	$backButtonDestination =Config::get('BASE_SERVER_VHOST_PREFIX') . Config::get('BASE_SERVER_VHOST');
	system_message(tr('You have been blocked for %d minutes.', Config::get('BRUTEFORCE_BLOCK_TIME')), $backButtonDestination);
}

function getipaddr() {
	return $_SERVER['REMOTE_ADDR'];
}

function do_session_timeout() {
	$sql = Database::getInstance();

	$ttl = time() - Config::get('SESSION_TIMEOUT') * 60;

	$query = "DELETE FROM `login` WHERE `lastaccess` < ?";
	exec_query($sql, $query, array($ttl));

	if (!session_exists(session_id())) {
		unset($_SESSION['user_logged']);
		unset_user_login_data();
	}
}

function session_exists($sess_id) {
	$sql = Database::getInstance();

	$query = "SELECT `session_id` FROM `login` WHERE `session_id` = ?";
	$res = exec_query($sql, $query, array($sess_id));

	return ($res->RecordCount() == 1);
}
