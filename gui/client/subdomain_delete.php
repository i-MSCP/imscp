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

if (isset($_GET['id']) && $_GET['id'] !== '') {
	$sub_id = $_GET['id'];
	$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

	$query = <<<SQL_QUERY
		SELECT
			`subdomain_id`,
			`subdomain_name`
		FROM
			`subdomain`
		WHERE
			`domain_id` = ?
		AND
			`subdomain_id` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id, $sub_id));
	$sub_name = $rs->fields['subdomain_name'];

	if ($rs->RecordCount() == 0) {
		user_goto('domains_manage.php');
	}

	// check for mail accounts
	$query = "SELECT COUNT(`mail_id`) AS cnt FROM `mail_users` WHERE (`mail_type` LIKE '".MT_SUBDOM_MAIL."%' OR `mail_type` = '".MT_SUBDOM_FORWARD."') AND `sub_id` = ?";
	$rs = exec_query($sql, $query, array($sub_id));

	if ($rs->fields['cnt'] > 0) {
		set_page_message(tr('Subdomain you are trying to remove has email accounts !<br>First remove them!'));
		user_goto('domains_manage.php');
	}

	$query = <<<SQL_QUERY
		UPDATE
			`subdomain`
		SET
			`subdomain_status` = 'delete'
		WHERE
			`subdomain_id` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($sub_id));

	update_reseller_c_props(get_reseller_id($dmn_id));

	send_request();
	write_log($_SESSION['user_logged'].": deletes subdomain: ".$sub_name);
	set_page_message(tr('Subdomain scheduled for deletion!'));
	user_goto('domains_manage.php');

} else {
	user_goto('domains_manage.php');
}
