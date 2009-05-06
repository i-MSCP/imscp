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

if (isset($_GET['id']) && $_GET['id'] !== '') {
	$mail_id = $_GET['id'];
	$item_delete_status = Config::get('ITEM_DELETE_STATUS');
	$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

	$query = "
		SELECT
			`mail_id`
		FROM
			`mail_users`
		WHERE
			`domain_id` = ?
		AND
			`mail_id` = ?
	";

	$rs = exec_query($sql, $query, array($dmn_id, $mail_id));

	if ($rs->RecordCount() == 0) {
		user_goto('mail_catchall.php');
	}

	check_for_lock_file();

	$query = "
		UPDATE
			`mail_users`
		SET
			`status` = ?
		WHERE
			`mail_id` = ?
	";

	$rs = exec_query($sql, $query, array($item_delete_status, $mail_id));

	send_request();
	write_log($_SESSION['user_logged'].': deletes email catch all!');
	set_page_message(tr('Catch all account scheduled for deletion!'));
	user_goto('mail_catchall.php');

} else {
	user_goto('mail_catchall.php');
}
