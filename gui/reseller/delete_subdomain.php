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

$theme_color = $cfg['USER_INITIAL_THEME'];

if (isset($_GET['del_id']))
	$del_id = $_GET['del_id'];
else {
	$_SESSION['dadel'] = '_no_';
	header("Location: subdomains.php");
	die();
}

$reseller_id = $_SESSION['user_id'];

$query = <<<SQL_QUERY
	select
		t1.subdomain_id, t1.domain_id, t2.domain_id, t2.domain_created_id
	from
		subdomain as t1,
		domain as t2
	where
			t1.subdomain_id = ?
		and
			t1.domain_id = t2.domain_id
		and
			t2.domain_created_id = ?
SQL_QUERY;

$rs = exec_query($sql, $query, array($del_id, $reseller_id));

if ($rs->RecordCount() == 0) {
	header('Location: subdomains.php');
	die();
}

/* check for mail acc in SUB domain ( MT_SUBDOM_MAIL ) */
$res = exec_query($sql,
	"select count(mail_id) as mailnum from mail_users where sub_id=? and mail_type='" . MT_SUBDOM_MAIL . "'",
	array($del_id));
$data = $res->FetchRow();
if ($data['mailnum'] > 0) {
	/* ERR - we have mail acc in this domain */
	$_SESSION['sdhavemail'] = '_yes_';
	header("Location: subdomains.php");
	die();
}

/* check for mail acc in SUB domain ( MT_SUBDOM_FORWARD ) */
$res = exec_query($sql,
	"select count(mail_id) as mailnum from mail_users where sub_id=? and mail_type='" . MT_SUBDOM_FORWARD . "'",
	array($del_id));
$data = $res->FetchRow();
if ($data['mailnum'] > 0) {
	/* ERR - we have mail acc in this domain */
	$_SESSION['sdhavemail'] = '_yes_';
	header("Location: subdomains.php");
	die();
}

/* check for ftp acc in SUB domain */
$res = exec_query($sql,
	"select count(fg.gid) as ftpnum from ftp_group fg,subdomain d where d.subdomain_id=? and fg.groupname=d.subdomain_name",
	array($del_id));
$data = $res->FetchRow();
if ($data['ftpnum'] > 0) {
	/* ERR - we have ftp acc in this domain */
	$_SESSION['sdhaveftp'] = '_yes_';
	header("Location: manage_subdomain.php");
	die();
}

$res = exec_query($sql, "select subdomain_name from subdomain where subdomain_id=?", array($del_id));
$dat = $res->FetchRow();

exec_query($sql, "update subdomain set subdomain_status='" . $cfg[ITEM_DELETE_STATUS] . "' where subdomain_id=?", array($del_id));
send_request();
$admin_login = $_SESSION['user_logged'];
write_log("$admin_login: delete subdomain: " . $dat['subdomain_name']);

$_SESSION['dadel'] = '_yes_';
header("Location: subdomains.php");
die();

?>
