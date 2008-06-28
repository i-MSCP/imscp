<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2007 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team (2007)
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

/* do we have a proper delete_id ? */

if (!isset($_GET['delete_id'])) {
	header("Location: ip_manage.php");
	die();
}

if (!is_numeric($_GET['delete_id'])) {
	set_page_message(tr('You cannot delete the last active IP address!'));
	header("Location: ip_manage.php");
	die();
}

$delete_id = $_GET['delete_id'];

/* check for domain that user this ip */

$query = <<<SQL_QUERY
    select
        count(domain_id) as dcnt
    from
        domain
    where
        domain_ip_id=?
SQL_QUERY;

$rs = exec_query($sql, $query, array($delete_id));

if ($rs->fields['dcnt'] > 0) {
	/* ERR - we have domain that use this ip */

	set_page_message(tr('Error: we have a domain using this IP!'));

	header("Location: ip_manage.php");
	die();
}
// check if the IP is assignet to reseller
$query = <<<SQL_QUERY
        select
            reseller_ips
        from
            reseller_props
SQL_QUERY;

$res = exec_query($sql, $query, array());

while (($data = $res->FetchRow())) {
	if (preg_match("/$delete_id;/", $data['reseller_ips'])) {
		set_page_message(tr('Error: we have a reseller using this IP!'));
		header("Location: ip_manage.php");
		die();
	}
}

$query = <<<SQL_QUERY
    select
        *
    from
        server_ips
    where
        ip_id=?
SQL_QUERY;

$rs = exec_query($sql, $query, array($delete_id));

$user_logged = $_SESSION['user_logged'];

$ip_number = $rs->fields['ip_number'];

write_log("$user_logged: delete IP address $ip_number");

/* delete it ! */
$query = <<<SQL_QUERY
    delete from
        server_ips
    where
        ip_id = ?
SQL_QUERY;

$rs = exec_query($sql, $query, array($delete_id));

set_page_message(tr('IP was deleted!'));

header("Location: ip_manage.php");
die();

?>