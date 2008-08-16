<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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

$theme_color = Config::get('USER_INITIAL_THEME');

if (isset($_GET['del_id']))
	$del_id = $_GET['del_id'];
else {
	$_SESSION['aldel'] = '_no_';
	header("Location: alias.php");
	die();
}
$reseller_id = $_SESSION['user_id'];

$query = <<<SQL_QUERY
	select
		t1.domain_id, t1.alias_id, t1.alias_name, t2.domain_id, t2.domain_created_id
	from
		domain_aliasses as t1,
		domain as t2
	where
			t1.alias_id = ?
		and
			t1.domain_id = t2.domain_id
		and
			t2.domain_created_id = ?
SQL_QUERY;

$rs = exec_query($sql, $query, array($del_id, $reseller_id));

if ($rs->RecordCount() == 0) {
	header('Location: alias.php');
	die();
}

$alias_name = $rs->fields['alias_name'];
$delete_status = Config::get('ITEM_DELETE_STATUS');

/* check for mail acc in ALIAS domain (ALIAS MAIL) and delete them */
$query = <<<SQL_QUERY
	update
		mail_users
	set
		status = ?
	where
    sub_id = ?
SQL_QUERY;

$rs = exec_query($sql, $query, array($delete_status, $del_id));

while (!$rs->EOF) {
	$rs->MoveNext();
}

$res = exec_query($sql, "select alias_name from domain_aliasses where alias_id=?", array($del_id));
$dat = $res->FetchRow();

exec_query($sql, "update domain_aliasses set alias_status='" . Config::get('ITEM_DELETE_STATUS') . "' where alias_id=?", array($del_id));
send_request();
$admin_login = $_SESSION['user_logged'];
write_log("$admin_login: deletes domain alias: " . $dat['alias_name']);

$_SESSION['aldel'] = '_yes_';
header("Location: alias.php");
die()

?>