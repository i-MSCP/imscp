<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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
	$data = "pma_username=".rawurlencode($user_mysql)."&pma_password=".rawurlencode(stripslashes($pass_mysql));

	$out = "POST /pma/ HTTP/1.0\r\n";
	$out .= "Host: {$_SERVER['SERVER_NAME']}\r\n";
	$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$out .= "Content-length: ".strlen($data)."\r\n";
	$out .= "Connection: Close\r\n\r\n";
	$out .= $data;

	$rs = '';

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
	user_goto('sql_manage.php');
}

// check User sql permission
if (isset($_SESSION['sql_support']) && $_SESSION['sql_support'] == "no") {
	user_goto('index.php');
}

if (isset($_GET['id'])) {
	$db_user_id = $_GET['id'];
} else {
	user_goto('sql_manage.php');
}

check_usr_sql_perms($sql, $db_user_id);
get_db_user_passwd($sql, $db_user_id);
