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

send_request();

set_page_message(tr('IP was deleted!'));

user_goto('ip_manage.php');
