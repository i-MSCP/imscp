<?php
/**
 *  ispCP ω (OMEGA) a Virtual Hosting Control Panel
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 *
 **/

function username_exists($username) {
	global $sql;

	$query = "SELECT * FROM admin WHERE admin_name='" . addslashes(htmlspecialchars($username)) . "'";
	$res = exec_query($sql, $query, array());

	return  ($res -> RecordCount() == 1);
}

function get_userdata($username) {
	global $sql;

	$query = "SELECT * FROM admin WHERE admin_name='" . addslashes(htmlspecialchars($username)) . "'";
	$res = exec_query($sql, $query, array());

	return $res -> FetchRow();

}

function is_userdomain_ok($username) {
	global $sql, $cfg;

	$udata = get_userdata($username);

	if (is_array($udata)) {

		if ($udata['admin_type'] == "user") {

			$query = "SELECT domain_status FROM domain WHERE domain_admin_id='" . $udata['admin_id'] . "'";

			$res = exec_query($sql, $query, array());

			$row = $res -> FetchRow();

			if ($row['domain_status'] != $cfg['ITEM_OK_STATUS']) {
				return FALSE;
			}
		}
		return TRUE;
	}
	else {
		return FALSE;
	}
}

function unblock($ttl = 30) {
	global $sql;

	$boundary = time() - $ttl * 60;
	$query = "DELETE FROM login WHERE user_name is NULL AND	lastaccess < '" . $boundary . "'";

	exec_query($sql, $query, array());

}

function is_ipaddr_blocked($ipaddr) {
	global $sql, $cfg;

	$query = "SELECT * FROM login WHERE ipaddr='" . $ipaddr . "' AND login_count='" .  $cfg['BRUTEFORCE_MAX_LOGIN'] . "'";
	$res = exec_query($sql, $query, array());

	if ($res -> RecordCount() == 1) {
		return TRUE;
	}

	return FALSE;
}

function check_ipaddr($ipaddr, $more_logins = 0) {
	global $sql, $cfg;

	$sess_id = session_id();
	$query = "SELECT session_id, ipaddr, user_name, lastaccess, login_count FROM login WHERE ipaddr='" . $ipaddr . "' AND user_name is NULL";
	$res = exec_query($sql, $query, array());

	if ($res->RecordCount() == 0) {
   		$query = "INSERT INTO login	(session_id, ipaddr, lastaccess, login_count) VALUES ('" . $sess_id . "','" . $ipaddr . "',UNIX_TIMESTAMP(),'1')";
   		exec_query($sql, $query, array());
	   	return false;
	}

	if (!$cfg['BRUTEFORCE'])
		return false;

	$data = $res->FetchRow();

	$lastaccess = $data['lastaccess'];
	$logincount = $data['login_count'];

	if ($logincount > ($cfg['BRUTEFORCE_MAX_LOGIN'] + $more_logins)) {

		if (($lastaccess + 1800) < time()) {
			$query = "UPDATE login SET lastaccess=UNIX_TIMESTAMP(),	login_count='1' WHERE ipaddr='" . $ipaddr . "' AND user_name is NULL";
   			exec_query($sql, $query, array());
			return false;
		}
		block_ipaddr($ipaddr);
	}

	if ($cfg['BRUTEFORCE_BETWEEN']) {
		$btime = $lastaccess + $cfg['BRUTEFORCE_BETWEEN_TIME'];
	}
	else {
		$btime = 0;
	}

	if ($btime < time()) {
		$query = "UPDATE login SET lastaccess=UNIX_TIMESTAMP(),	login_count=login_count+1 WHERE ipaddr='" . $ipaddr . "' AND user_name is NULL";
   		exec_query($sql, $query, array());
		return false;
	} else {
		write_log("Login error, <b><i>".htmlspecialchars($ipaddr, ENT_QUOTES, "UTF-8")."</i></b> wait " . $cfg['BRUTEFORCE_BETWEEN_TIME'] . " seconds");
		system_message(tr('You have to wait') . ' ' . $cfg['BRUTEFORCE_BETWEEN_TIME'] . ' ' . tr('seconds'));
		return false;
	}
}

function block_ipaddr($ipaddr) {
	global $cfg;

	write_log("Login error, <b><i>".htmlspecialchars($ipaddr, ENT_QUOTES, "UTF-8")."</i></b> blocked for " . $cfg['BRUTEFORCE_BLOCK_TIME'] . " minutes.");
	system_message(tr('You have been blocked for') . ' ' . $cfg['BRUTEFORCE_BLOCK_TIME'] . ' ' . tr('minutes'));
	die();
}

function getipaddr() {
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	else
		return $_SERVER['REMOTE_ADDR'];
}

function do_session_timeout() {
	global $sql, $cfg;

	$ttl = time() - $cfg['SESSION_TIMEOUT'] * 60;

	$query = "DELETE FROM login WHERE lastaccess < '" . $ttl . "'";
	exec_query($sql, $query, array());

	if (!session_exists(session_id())) {
	    if (isset($_SESSION['user_logged']))
	    unset($_SESSION['user_logged']);
        unset_user_login_data();
	}
}

function session_exists($sess_id) {
	global $sql;

	$query = "SELECT session_id FROM login WHERE session_id='" . $sess_id . "'";

	$res = exec_query($sql, $query, array());

	if ($res -> RecordCount() == 0) {
  		return FALSE;
  	}
	return TRUE;
}

?>