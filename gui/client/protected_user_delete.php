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

$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

if (isset($_GET['uname']) && $_GET['uname'] !== '' && is_numeric($_GET['uname'])) {
	$uuser_id = $_GET['uname'];
} else {
	header( 'Location: protected_areas.php' );
	die();
}

$query = "
	SELECT
		`uname`
	FROM
		`htaccess_users`
	WHERE
		`dmn_id` = ?
	AND
		`id` = ?
";

$rs = exec_query($sql, $query, array($dmn_id, $uuser_id));
$uname = $rs->fields['uname'];

$change_status = Config::get('ITEM_DELETE_STATUS');
// let's delete the user from the SQL
$query = "
	UPDATE
		`htaccess_users`
	SET
		`status` = ?
	WHERE
		`id` = ?
	AND
		`dmn_id` = ?
";

$rs = exec_query($sql, $query, array($change_status, $uuser_id, $dmn_id));

// let's delete this user if assigned to a group
$query = "
	SELECT
		`id`,
		`members`
	FROM
		`htaccess_groups`
	WHERE
		`dmn_id` = ?
";
$rs = exec_query($sql, $query, array($dmn_id));

 if ($rs->RecordCount() !== 0) {

	 while (!$rs->EOF) {
		$members = explode(',',$rs->fields['members']);
		$group_id = $rs->fields['id'];
		$key=array_search($uuser_id,$members);
		if ($key!==false) {
			unset($members[$key]);
			$members=implode(",",$members);
			$change_status = Config::get('ITEM_CHANGE_STATUS');
			$update_query = "
				UPDATE
					`htaccess_groups`
				SET
					`members` = ?,
					`status` = ?
				WHERE
					`id` = ?
			";
			$rs_update = exec_query($sql, $update_query, array($members, $change_status, $group_id));
		}
		$rs->MoveNext();
	 }
 }

// lets delete or update htaccess files if this user is assigned
$query = "
	SELECT
		*
	FROM
		`htaccess`
	WHERE
		`dmn_id` = ?
";
	
$rs = exec_query($sql, $query, array($dmn_id));

while (!$rs->EOF) {
	$ht_id = $rs->fields['id'];
	$usr_id = $rs->fields['user_id'];

	$usr_id_splited = explode(',', $usr_id);

	$key=array_search($uuser_id,$usr_id_splited);
	if ($key!==false) {
		unset($usr_id_splited[$key]);
		if (count($usr_id_splited) == 0) {
			$status = Config::get('ITEM_DELETE_STATUS');
		} else {
			$usr_id=implode(",", $usr_id_splited);
			$status = Config::get('ITEM_CHANGE_STATUS');
		}
		$update_query = "
			UPDATE
				`htaccess`
			SET
				`user_id` = ?,
				`status` = ?
			WHERE
				`id` = ?
		";

		$rs_update = exec_query($sql, $update_query, array($usr_id, $status, $ht_id));
	}

	$rs->MoveNext();
}

check_for_lock_file();
send_request();

write_log("$admin_login: deletes user ID (protected areas): $uname");
header( "Location: protected_user_manage.php" );
die();

?>