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

if (isset($_GET['id']) && $_GET['id'] !== '') {
	$ftp_id = $_GET['id'];
	$dmn_name = $_SESSION['user_logged'];

	$query = <<<SQL_QUERY
		SELECT
			t1.`userid`,
			t1.`uid`,
			t2.`domain_gid`
		FROM
			`ftp_users` AS t1,
			`domain` AS t2
		WHERE
			t1.`userid` = ?
		AND
			t1.`uid` = t2.`domain_gid`
		AND
			t2.`domain_name` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($ftp_id, $dmn_name));
	$ftp_name = $rs->fields['userid'];

	if ($rs->RecordCount() == 0) {
		user_goto('ftp_accounts.php');
	}

	$query = <<<SQL_QUERY
		SELECT
			t1.`gid`,
			t2.`members`
		FROM
			`ftp_users` AS t1,
			`ftp_group` AS t2
		WHERE
			t1.`gid` = t2.`gid`
		AND
			t1.`userid` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($ftp_id));

	$ftp_gid = $rs->fields['gid'];
	$ftp_members = $rs->fields['members'];
	$members = preg_replace("/$ftp_id/", "", "$ftp_members");
	$members = preg_replace("/,,/", ",", "$members");
	$members = preg_replace("/^,/", "", "$members");
	$members = preg_replace("/,$/", "", "$members");

	if (strlen($members) == 0) {
		$query = <<<SQL_QUERY
			DELETE FROM
				`ftp_group`
			WHERE
				`gid` = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($ftp_gid));

	} else {
		$query = <<<SQL_QUERY
			UPDATE
				`ftp_group`
			SET
				`members` = ?
			WHERE
				`gid` = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($members, $ftp_gid));
	}

	$query = <<<SQL_QUERY
		DELETE FROM
			`ftp_users`
		WHERE
			`userid` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($ftp_id));

	write_log($_SESSION['user_logged'].": deletes FTP account: ".$ftp_name);
	set_page_message(tr('FTP account deleted successfully!'));
	user_goto('ftp_accounts.php');

} else {
	user_goto('ftp_accounts.php');
}

?>