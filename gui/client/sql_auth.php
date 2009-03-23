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

require '../include/ispcp-lib.php';

check_login(__FILE__);

// page functions.

function get_db_user_passwd(&$sql, $db_user_id) {
	$query = "
		SELECT
			`sqlu_name`, `sqlu_pass`
		FROM
			`sql_user`
		WHERE
			`sqlu_id` = ?
	";

	$rs = exec_query($sql, $query, $db_user_id);

	$user_mysql = $rs->fields['sqlu_name'];
	$pass_mysql = decrypt_db_password($rs->fields['sqlu_pass']);
	$data="pma_username=".rawurlencode($user_mysql)."&pma_password=".rawurlencode(stripslashes($pass_mysql));

	$out  = "POST /pma/ HTTP/1.0\r\n";
	$out .= "Host: ".Config::get('BASE_SERVER_VHOST')."\r\n";
	$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$out .= "Content-length: ".strlen($data)."\r\n";
	$out .= "Connection: Close\r\n\r\n";
	$out .= $data;

	$rs='';

	$fp = fsockopen(Config::get('BASE_SERVER_IP'), 80, $errno, $errstr, 5);
	if (!$fp) {
		auth_error();
	} else {
		fwrite($fp, $out);
		$header = null;
		while (!feof($fp)) {
			$line = fgets($fp, 2048);
			$rs.=$line;
			if (preg_match("/^Location.+/",$line,$results)) $header=$line;
		}
		fclose($fp);
		preg_match_all("/(?:Set-Cookie: )(?:(?U)(.+)=(.+)(?:;))(?:(?U)( expires=)(.+)(?:;))?(?:( path=)(.+))?/", $rs, $results, PREG_SET_ORDER);
		foreach ($results as $result) {
			setcookie(rawurldecode($result[1]), rawurldecode($result[2]), strtotime(rawurldecode($result[4])), rawurldecode($result[6]));
		}
		if ($header) {
			header($header);
			die();
		} else {
			auth_error();
		}
	}
}

function auth_error() {
	set_page_message(tr("Error while authenticating!"));
	header("Location: sql_manage.php");
	die();
}

// check User sql permission
if (isset($_SESSION['sql_support']) && $_SESSION['sql_support'] == "no") {
	header("Location: index.php");
	exit;
}

if (isset($_GET['id'])) {
	$db_user_id = $_GET['id'];
} else {
	user_goto('sql_manage.php');
}

check_usr_sql_perms($sql, $db_user_id);
get_db_user_passwd($sql, $db_user_id);

?>
