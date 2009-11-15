<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

if (isset($_GET['uname'])
	&& $_GET['uname'] !== ''
	&& is_numeric($_GET['uname'])) {
	$uuser_id = $_GET['uname'];
} else {
	user_goto('protected_areas.php');
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
		$key = array_search($uuser_id, $members);
		if ($key !== false) {
			unset($members[$key]);
			$members = implode(",", $members);
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

// let's delete or update htaccess files if this user is assigned
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

	$key = array_search($uuser_id,$usr_id_splited);
	if ($key !== false) {
		unset($usr_id_splited[$key]);
		if (count($usr_id_splited) == 0) {
			$status = Config::get('ITEM_DELETE_STATUS');
		} else {
			$usr_id = implode(",", $usr_id_splited);
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

send_request();

$admin_login = $_SESSION['user_logged'];
write_log("$admin_login: deletes user ID (protected areas): $uname");
user_goto('protected_user_manage.php');
