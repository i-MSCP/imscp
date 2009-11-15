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

$theme_color = Config::get('USER_INITIAL_THEME');

if (isset($_GET['del_id']))
	$del_id = $_GET['del_id'];
else {
	$_SESSION['aldel'] = '_no_';
	user_goto('alias.php');
}
$reseller_id = $_SESSION['user_id'];

$query = <<<SQL_QUERY
	SELECT
		t1.`domain_id`, t1.`alias_id`, t1.`alias_name`,
		t2.`domain_id`, t2.`domain_created_id`
	FROM
		`domain_aliasses` AS t1,
		`domain` AS t2
	WHERE
		t1.`alias_id` = ?
	AND
		t1.`domain_id` = t2.`domain_id`
	AND
		t2.`domain_created_id` = ?
SQL_QUERY;

$rs = exec_query($sql, $query, array($del_id, $reseller_id));

if ($rs->RecordCount() == 0) {
	user_goto('alias.php');
}

$alias_name = $rs->fields['alias_name'];
$delete_status = Config::get('ITEM_DELETE_STATUS');

/* check for mail acc in ALIAS domain (ALIAS MAIL) and delete them */
$query = <<<SQL_QUERY
	UPDATE
		`mail_users`
	SET
		`status` = ?
	WHERE
		(`sub_id` = ?
		AND
		`mail_type` LIKE '%alias_%')
	OR
		(`sub_id` IN (SELECT `subdomain_alias_id` FROM `subdomain_alias` WHERE `alias_id` = ?)
		AND
		`mail_type` LIKE '%alssub_%')

SQL_QUERY;

exec_query($sql, $query, array($delete_status, $del_id, $del_id));

$res = exec_query($sql, "SELECT `alias_name` FROM `domain_aliasses` WHERE `alias_id` = ?", array($del_id));
$dat = $res->FetchRow();

exec_query($sql, "UPDATE `subdomain_alias` SET `subdomain_alias_status` = '" . Config::get('ITEM_DELETE_STATUS') . "' WHERE `alias_id` = ?", array($del_id));
exec_query($sql, "UPDATE `domain_aliasses` SET `alias_status` = '" . Config::get('ITEM_DELETE_STATUS') . "' WHERE `alias_id` = ?", array($del_id));

update_reseller_c_props($reseller_id);

send_request();
$admin_login = $_SESSION['user_logged'];
write_log("$admin_login: deletes domain alias: " . $dat['alias_name']);

$_SESSION['aldel'] = '_yes_';

user_goto('alias.php');
