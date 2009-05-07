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


if (isset($_GET['gname'])
	&& $_GET['gname'] !== ''
	&& is_numeric($_GET['gname'])) {
	$group_id = $_GET['gname'];
} else {
	user_goto('protected_areas.php');
}

$change_status = Config::get('ITEM_DELETE_STATUS');
$awstats_auth = Config::get('AWSTATS_GROUP_AUTH');

$query = "
	UPDATE
		`htaccess_groups`
	SET
		`status` = ?
	WHERE
		`id` = ?
	AND
		`dmn_id` = ?
	AND
		`ugroup` != ?
";

$rs = exec_query($sql, $query, array($change_status, $group_id, $dmn_id, $awstats_auth));


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
	$grp_id = $rs->fields['group_id'];

	$grp_id_splited = explode(',', $grp_id);

	$key = array_search($group_id,$grp_id_splited);
	if ($key !== false) {
		unset($grp_id_splited[$key]);
		if (count($grp_id_splited) == 0) {
			$status = Config::get('ITEM_DELETE_STATUS');
		} else {
			$grp_id = implode(",", $grp_id_splited);
			$status = Config::get('ITEM_CHANGE_STATUS');
		}
		$update_query = "
			UPDATE
				`htaccess`
			SET
				`group_id` = ?,
				`status` = ?
			WHERE
				`id` = ?
		";
		$rs_update = exec_query($sql, $update_query, array($grp_id, $status, $ht_id));
	}

	$rs->MoveNext();
}

check_for_lock_file();
send_request();

write_log($_SESSION['user_logged'].": deletes group ID (protected areas): $group_id");
user_goto('protected_user_manage.php');
