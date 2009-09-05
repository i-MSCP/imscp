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

function init_login() {
	// just make sure to expire counters in case BRUTEFORCE is turned off
	unblock(Config::get('BRUTEFORCE_BLOCK_TIME'));

	if (Config::get('BRUTEFORCE')) {
		is_ipaddr_blocked(null, 'bruteforce', true);
	}
}

/**
 * @todo use more secure hash algorithm (see PHP mcrypt extension)
 */
function register_user($uname, $upass) {
	$sql = Database::getInstance();
	$backButtonDestination = Config::get('BASE_SERVER_VHOST_PREFIX') . Config::get('BASE_SERVER_VHOST');

	check_ipaddr();

	if (!username_exists($uname)) {
		write_log("Login error, <b><i>".$uname."</i></b> unknown username");
		system_message(tr('You entered an incorrect username/password.'), $backButtonDestination);
		return false;
	}

	$udata = array();
	$udata = get_userdata($uname);

	if ((
		criticalUpdate::getInstance()->checkUpdateExists()
		|| databaseUpdate::getInstance()->checkUpdateExists()
		|| (Config::get('MAINTENANCEMODE'))
		) && $udata['admin_type'] != 'admin') {
		write_log("Login error, <b><i>".$uname."</i></b> system currently in maintenance mode");
		system_message(tr('System is currently under maintenance! Only administrators can login.'));
		return false;
	}

	if (crypt($upass, $udata['admin_pass']) == $udata['admin_pass']
		|| md5($upass) == $udata['admin_pass']) {

		if (isset($_SESSION['user_logged'])) {
			write_log(tr("%s user already logged or session sharing problem! Aborting...", $uname));
			system_message(tr('User already logged or session sharing problem! Aborting...'));
			unset_user_login_data();
			return false;
		}

		if (!is_userdomain_ok($uname)) {
			write_log(tr("%s's account status is not ok!", $uname));
			system_message(tr("%s's account status is not ok!", $uname));
			return false;
		}

		if ($udata['admin_type'] == 'user' && is_userdomain_expired($uname)) {
			write_log(tr("%s's domain expired!", $uname));
			system_message(tr("%s's domain expired!", $uname));
			return false;
		}

		$sess_id = session_id();

		$query = <<<SQL_QUERY
			UPDATE
				`login`
			SET
				`user_name` = ?,
				`lastaccess` = ?
			WHERE
				`session_id` = ?
SQL_QUERY;

		exec_query($sql, $query, array($uname, time(), $sess_id));

		$_SESSION['user_logged'] = $udata['admin_name'];
		$_SESSION['user_pass'] = $udata['admin_pass'];
		$_SESSION['user_type'] = $udata['admin_type'];
		$_SESSION['user_id'] = $udata['admin_id'];
		$_SESSION['user_email'] = $udata['email'];
		$_SESSION['user_created_by'] = $udata['created_by'];
		$_SESSION['user_login_time'] = time();

		write_log($uname." logged in.");
		return true;
	} else {
		write_log($uname . ' entered incorrect password.');
		system_message(tr('You entered an incorrect username/password.'), $backButtonDestination);
		return false;
	}

}

function check_user_login() {
	$sql = Database::getInstance();

	$sess_id = session_id();
	/* kill timed out sessions */
	do_session_timeout();
	$user_logged = isset($_SESSION['user_logged']) ? $_SESSION['user_logged'] : false;

	if (!$user_logged) {
		return false;
	}

	$user_pass = $_SESSION['user_pass'];
	$user_type = $_SESSION['user_type'];
	$user_id = $_SESSION['user_id'];

	// verify session data with database
	$query = <<<SQL_QUERY
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
SQL_QUERY;

	$rs = exec_query($sql, $query, array($user_logged, $user_pass, $user_type, $user_id, $sess_id));

	if ($rs->RecordCount() != 1) {
		write_log("Detected session manipulation on $user_logged's session!");
		unset_user_login_data();
		return false;
	}

	if (( criticalUpdate::getInstance()->checkUpdateExists() || databaseUpdate::getInstance()->checkUpdateExists() || (Config::get('MAINTENANCEMODE')) ) && $user_type != 'admin') {
		unset_user_login_data(true);
		write_log("System is currently in maintenance mode. Logging out <b><i>".$user_logged."</i></b>");
		user_goto('/index.php');
	}
	/* if user login data correct - update session and lastaccess */
	$_SESSION['user_login_time'] = time();

	$query = <<<SQL_QUERY
		UPDATE
			`login`
		SET
			`lastaccess` = ?
		WHERE
			`session_id` = ?
SQL_QUERY;

	exec_query($sql, $query, array(time(), $sess_id));
	return true;
}

/**
 * check for valid user login and valid file request/call
 *
 * @param string $fName full path and filename of the file ie. with magic constant __FILE__
 * @param boolean $preventExternalLogin check HTTP Referer for valid request/call, ie. to prevent login from external websites
 */
function check_login($fName = null, $preventExternalLogin = true) {

	// session-type check:
	if (!check_user_login()) {
		user_goto('/index.php');
	}

	if ($fName != null) {
		$levels = explode('/', realpath(dirname($fName)));
		$level = $levels[count($levels) - 1];

		switch ($level) {
			case 'user':
				$level = 'client';
			case 'admin':
			case 'reseller':
				if ($level != $_SESSION['user_type']) {
					write_log('Warning! user |'.$_SESSION['user_logged'].'| requested |'.$_SERVER["REQUEST_URI"].'| with REQUEST_METHOD |'.$_SERVER["REQUEST_METHOD"].'|');
					user_goto('/index.php');
				}
				break;
		}
	}

	// prevent external login / check for referer
	if ($preventExternalLogin) {
		if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {

			$info = parse_url($_SERVER['HTTP_REFERER']);
			if (isset($info['host']) && !empty($info['host'])) {
				if ($info['host'] != $_SERVER['HTTP_HOST']
					|| $info['host'] != $_SERVER['SERVER_NAME']) {
					set_page_message(tr('Request from foreign host was blocked!'));
					if (!(substr($_SERVER['SCRIPT_FILENAME'], (int)-strlen($_SERVER['REDIRECT_URL']), strlen($_SERVER['REDIRECT_URL'])) === $_SERVER['REDIRECT_URL'])) {
						redirect_to_level_page();
					}
				}
			}
		}
	}
}

function change_user_interface($from_id, $to_id) {
	$sql = Database::getInstance();

	$index = null;
	while (1) { // used to easily exit
		$query = 'SELECT `admin_id`, `admin_name`, `admin_pass`, `admin_type`, `email`, `created_by` FROM `admin` WHERE binary `admin_id` = ?';

		$rs_from	= exec_query($sql, $query, array($from_id));
		$rs_to		= exec_query($sql, $query, array($to_id));

		if (($rs_from->RecordCount()) != 1 || ($rs_to->RecordCount()) != 1) {
			set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
			break;
		}

		$from_udata	= $rs_from->FetchRow();
		$to_udata	= $rs_to->FetchRow();

		if (!is_userdomain_ok($to_udata['admin_name'])) {
			set_page_message(tr("%s's account status is not ok!", decode_idna($to_udata['admin_name'])));
			break;
		}

		$to_admin_type		= strtolower($to_udata['admin_type']);
		$from_admin_type	= strtolower($from_udata['admin_type']);

		$allowed_changes	= array();

		$allowed_changes['admin']['admin']		= 'manage_users.php';
		$allowed_changes['admin']['BACK']		= 'manage_users.php';
		$allowed_changes['admin']['reseller']	= 'index.php';
		$allowed_changes['admin']['user']		= 'index.php';
		$allowed_changes['reseller']['user']	= 'index.php';
		$allowed_changes['reseller']['BACK']	= 'users.php';

		if (!isset($allowed_changes[$from_admin_type][$to_admin_type])
			|| ($to_admin_type == $from_admin_type && $from_admin_type != 'admin')) {

		if (isset($_SESSION['logged_from_id']) && $_SESSION['logged_from_id'] == $to_id) {
			$index = $allowed_changes[$to_admin_type]['BACK'];
		} else {
			set_page_message(tr('You do not have permission to access this interface!'));
			break;
		}
	}

	$index = $index ? $index : $allowed_changes[$from_admin_type][$to_admin_type];

	unset_user_login_data();

	if (($to_admin_type != 'admin' &&
		((isset($_SESSION['logged_from_id']) && $_SESSION['logged_from_id'] != $to_id)
		|| !isset($_SESSION['logged_from_id'])))
		|| ($from_admin_type == 'admin' && $to_admin_type == 'admin')) {

		$_SESSION['logged_from'] = $from_udata['admin_name'];
		$_SESSION['logged_from_id'] = $from_udata['admin_id'];

	}
	if ($from_admin_type == 'user') { // Ticket 830 - remove the 'logged_from' if back from user
		unset($_SESSION['logged_from']); // maybe integrated in the construction above...
		unset($_SESSION['logged_from_id']);
	}

		// we gonna kill all sessions and globals if user get back to admin level
		unset($_SESSION['admin_name']);
		unset($_SESSION['admin_id']);

		unset($GLOBALS['admin_name']);
		unset($GLOBALS['admin_id']);
		// no more sessions and globals to kill - they were always killed - rest in peace

		$_SESSION['user_logged'] = $to_udata['admin_name'];
		$_SESSION['user_pass'] = $to_udata['admin_pass'];
		$_SESSION['user_type'] = $to_udata['admin_type'];
		$_SESSION['user_id'] = $to_udata['admin_id'];
		$_SESSION['user_email'] = $to_udata['email'];
		$_SESSION['user_created_by'] = $to_udata['created_by'];
		$_SESSION['user_login_time'] = time();

		$query = 'INSERT INTO login (`session_id`, `user_name`, `lastaccess`) VALUES (?, ?, ?)';

		exec_query($sql, $query, array(session_id(), $to_udata['admin_name'], $_SESSION['user_login_time']));

		write_log(sprintf("%s changes into %s's interface", decode_idna($from_udata['admin_name']), decode_idna($to_udata['admin_name'])));
		break;
	}

	redirect_to_level_page($index);
}

function unset_user_login_data($ignorePreserve = false) {
	$sql = Database::getInstance();

	if (isset($_SESSION['user_logged'])) {

		$sess_id = session_id();

		$admin_name = $_SESSION['user_logged'];

		$query = <<<SQL_QUERY
			DELETE FROM
				`login`
			WHERE
				`session_id` = ?
			AND
				`user_name` = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($sess_id, $admin_name));

	}

	$preserve_list = array('user_def_lang', 'user_theme');
	$preserve_vals = array();

	if (!$ignorePreserve) {
		foreach ($preserve_list as $p) {
			if (isset($_SESSION[$p])) {
				$preserve_vals[$p] = $_SESSION[$p];
			}
		}
	}

	$_SESSION = array();

	foreach ($preserve_list as $p) {
		if (isset($preserve_vals[$p])) {
			$_SESSION[$p] = $preserve_vals[$p];
		}
	}

}

function redirect_to_level_page($file = null, $force = false) {

	if (!isset($_SESSION['user_type']) && !$force)
		return false;

	if (!$file) {
		$file = 'index.php';
	}

	$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

	switch ($user_type) {
		case 'user':
			$user_type = 'client';
		case 'admin':
		case 'reseller':
			header('Location: /' . $user_type . '/' . $file);
			break;
		case '':
			header('Location: /index.php');
			break;
		default:
			die("FIXME! " . __FILE__ . ":" . __LINE__);
			break;
	}
	die();
}
