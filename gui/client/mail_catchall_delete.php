<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
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
