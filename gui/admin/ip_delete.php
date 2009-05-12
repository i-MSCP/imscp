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

/* Do we have a proper delete_id? */
if (!isset($_GET['delete_id'])) {
	user_goto('ip_manage.php');
}

if (!is_numeric($_GET['delete_id'])) {
	set_page_message(tr('You cannot delete the last active IP address!'));
	user_goto('ip_manage.php');
}

$delete_id = $_GET['delete_id'];

/* check for domains that use this IP */
$query = "
	SELECT
		COUNT(`domain_id`) AS dcnt
	FROM
		`domain`
	WHERE
		`domain_ip_id` = ?
";

$rs = exec_query($sql, $query, array($delete_id));

if ($rs->fields['dcnt'] > 0) {
	/* ERROR - we have domain(s) that use this IP */

	set_page_message(tr('Error: we have a domain using this IP!'));

	user_goto('ip_manage.php');
}
// check if the IP is assigned to reseller
$query = "SELECT `reseller_ips` FROM `reseller_props`";

$res = exec_query($sql, $query, array());

while (($data = $res->FetchRow())) {
	if (preg_match("/$delete_id;/", $data['reseller_ips'])) {
		set_page_message(tr('Error: we have a reseller using this IP!'));
		user_goto('ip_manage.php');
	}
}

$query = "
	SELECT
		*
	FROM
		`server_ips`
	WHERE
		`ip_id` = ?
";

$rs = exec_query($sql, $query, array($delete_id));

$user_logged = $_SESSION['user_logged'];

$ip_number = $rs->fields['ip_number'];

write_log("$user_logged: deletes IP address $ip_number");

/* delete it ! */
$query = "
	UPDATE
		`server_ips`
	SET
		`ip_status` = ?
	WHERE
		`ip_id` = ?
	LIMIT 1
";
$rs = exec_query($sql, $query, array(Config::get('ITEM_DELETE_STATUS'), $delete_id));

check_for_lock_file();
send_request();

set_page_message(tr('IP was deleted!'));

user_goto('ip_manage.php');
