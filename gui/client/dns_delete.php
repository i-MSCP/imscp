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

if (isset($_GET['edit_id']) && $_GET['edit_id'] !== '') {
	$dns_id = $_GET['edit_id'];
	$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

	$query = "
		SELECT
			`domain_dns`.`domain_dns_id`,
			`domain_dns`.`domain_dns`,
			`domain_dns`.`alias_id`,
			IFNULL(`domain_aliasses`.`alias_name`, `domain`.`domain_name`) AS domain_name,
			IFNULL(`domain_aliasses`.`alias_id`, `domain_dns`.`domain_id`) AS id
		FROM
			`domain_dns`
			LEFT JOIN `domain_aliasses` USING (`alias_id`, `domain_id`),
			`domain`
		WHERE
			`domain_dns`.`domain_id` = ?
		AND
			`domain_dns`.`domain_dns_id` = ?
		AND
			`domain`.`domain_id` = `domain_dns`.`domain_id`
		";

	$rs = exec_query($sql, $query, array($dmn_id, $dns_id));
	$dom_name = $rs->fields['domain_name'];
	$dns_name = $rs->fields['domain_dns'];
	$id =  $rs->fields['id'];

	if ($rs->RecordCount() == 0) {
		user_goto('domains_manage.php');
	}


	$query = "
		DELETE FROM
			`domain_dns`
		WHERE
			`domain_dns_id` = ?
		LIMIT 1
	";

	$rs = exec_query($sql, $query, array($dns_id));

	$table = empty($rs->fields['alias_id']) ? 'domain' : 'domain_aliasses';

	$query = "
		UPDATE
			`{$table}`
		SET
			`{$table}_status` = ?
		WHERE
			`{$table}_id` = ?
		LIMIT 1
	";

	$rs = exec_query($sql, $query, array( Config::get('ITEM_CHANGE_STATUS'), $id));

	send_request();

	write_log($_SESSION['user_logged'] . ': deletes dns zone record: ' . $dns_name . ' of domain ' . $dom_name);
	set_page_message(tr('DNS zone record scheduled for deletion!'));
	user_goto('domains_manage.php');
} else {
	user_goto('domains_manage.php');
}
