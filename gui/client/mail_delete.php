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

if (isset($_GET['id']) && $_GET['id'] !== '') {
	global $delete_id;
	$delete_id = $_GET['id'];
} else {
	user_goto('mail_accounts.php');
}

/* Do we have a proper delete_id? */
if (!isset($delete_id)) {
	user_goto('mail_accounts.php');
}

if (!is_numeric($delete_id)) {
	user_goto('mail_accounts.php');
}

$dmn_name = $_SESSION['user_logged'];

$query = "
	SELECT
		t1.`mail_id`, t2.`domain_id`, t2.`domain_name`
	FROM
		`mail_users` AS t1,
		`domain` AS t2
	WHERE
		t1.`mail_id` = ?
	AND
		t1.`domain_id` = t2.`domain_id`
	AND
		t2.`domain_name` = ?
";

$rs = exec_query($sql, $query, array($delete_id, $dmn_name));
if ($rs->RecordCount() == 0) {
	user_goto('mail_accounts.php');
}

/* check for catchall assigment !! */
$query = "SELECT `mail_acc`, `domain_id`, `sub_id`, `mail_type` FROM `mail_users` WHERE `mail_id` = ?";
$res = exec_query($sql, $query, array($delete_id));
$data = $res->FetchRow();

if (preg_match("/".MT_NORMAL_MAIL."/", $data['mail_type']) || preg_match("/".MT_NORMAL_FORWARD."/", $data['mail_type'])) {
	/* mail to normal domain */
	// global $domain_name;
	$mail_name = $data['mail_acc'] . '@' . $_SESSION['user_logged']; //$domain_name;
} else if (preg_match("/".MT_ALIAS_MAIL."/", $data['mail_type']) || preg_match("/".MT_ALIAS_FORWARD."/", $data['mail_type'])) {
	/* mail to domain alias*/
	$res_tmp = exec_query($sql, "SELECT `alias_name` FROM `domain_aliasses` WHERE `alias_id` = ?", array($data['sub_id']));
	$dat_tmp = $res_tmp->FetchRow();
	$mail_name = $data['mail_acc'] . '@' . $dat_tmp['alias_name'];
} else if (preg_match("/".MT_SUBDOM_MAIL."/", $data['mail_type']) || preg_match("/".MT_SUBDOM_FORWARD."/", $data['mail_type'])) {
	/* mail to subdomain*/
	$res_tmp = exec_query($sql, "SELECT `subdomain_name` FROM `subdomain` WHERE `subdomain_id` = ?", array($data['sub_id']));
	$dat_tmp = $res_tmp->FetchRow();
	$mail_name = $data['mail_acc'] . '@' . $dat_tmp['subdomain_name'].'.'.$dmn_name;
} else if (preg_match("/".MT_ALSSUB_MAIL."/", $data['mail_type']) || preg_match("/".MT_ALSSUB_FORWARD."/", $data['mail_type'])) {
	/* mail to subdomain*/
	$res_tmp = exec_query($sql, "SELECT `subdomain_alias_name`, `alias_name` FROM `subdomain_alias` AS t1, `domain_aliasses` AS t2 WHERE t1.`alias_id` = t2.`alias_id` AND `subdomain_alias_id` = ?", array($data['sub_id']));
	$dat_tmp = $res_tmp->FetchRow();
	$mail_name = $data['mail_acc'] . '@' . $dat_tmp['subdomain_alias_name'].'.'.$dat_tmp['alias_name'];
}

$query = "SELECT `mail_id` FROM `mail_users` WHERE `mail_acc` = ? OR `mail_acc` LIKE ? OR `mail_acc` LIKE ? OR `mail_acc` LIKE ?";
$res_tmp = exec_query($sql, $query, array($mail_name, "$mail_name,%", "%,$mail_name,%", "%,$mail_name"));
$num = $res_tmp->RowCount();
if ($num > 0) {
	set_page_message(tr('Please delete first CatchAll account for this email!'));
	$_SESSION['catchall_assigned'] = 1;
	user_goto('mail_accounts.php');
}

/**
 * @todo useDB prepared statements
 */
$query = "UPDATE `mail_users` SET `status` = '" . Config::get('ITEM_DELETE_STATUS') . "' WHERE `mail_id` = ?";
exec_query($sql, $query, array($delete_id));

update_reseller_c_props(get_reseller_id($data['domain_id']));

send_request();
$admin_login = decode_idna($_SESSION['user_logged']);
write_log("$admin_login: deletes mail account: " . $mail_name);
$_SESSION['maildel'] = 1;

user_goto('mail_accounts.php');
