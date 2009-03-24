<?php
/**
 * ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

if (isset($_GET['id']) && $_GET['id'] !== '') {
	$sub_id = $_GET['id'];
	$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

	$query = <<<SQL_QUERY
		SELECT
			`subdomain_alias_id`,
			`subdomain_alias_name`
		FROM
			`subdomain_alias` JOIN `domain_aliasses`
		ON
			subdomain_alias.`alias_id` = domain_aliasses.`alias_id`
		WHERE
			`domain_id` = ?
		AND
			`subdomain_alias_id` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id, $sub_id));
	$sub_name = $rs->fields['subdomain_alias_name'];

	if ($rs->RecordCount() == 0) {
		user_goto('domains_manage.php');
	}

	// check for mail accounts
	$query = "SELECT COUNT(`mail_id`) AS cnt FROM `mail_users` WHERE (`mail_type` LIKE '".MT_ALSSUB_MAIL."%' OR `mail_type` = '".MT_ALSSUB_FORWARD."') AND `sub_id` = ?";
	$rs = exec_query($sql, $query, array($sub_id));

	if ($rs->fields['cnt'] > 0) {
		set_page_message(tr('Subdomain you are trying to remove has email accounts !<br>First remove them!'));
		header('Location: domains_manage.php');
		exit(0);
	}

	check_for_lock_file();

	$query = <<<SQL_QUERY
		UPDATE
			`subdomain_alias`
		SET
			`subdomain_alias_status` = 'delete'
		WHERE
			`subdomain_alias_id` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($sub_id));
	send_request();
	write_log($_SESSION['user_logged'].": delete alias subdomain: ".$sub_name);
	set_page_message('Alias '.tr('Subdomain scheduled for deletion!'));
	header('Location: domains_manage.php');
	exit(0);

} else {
	header('Location: domains_manage.php');
	exit(0);
}

?>
