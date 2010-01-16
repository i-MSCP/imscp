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

$query = "
  SELECT
    `support_system`
  FROM
    `reseller_props`
  WHERE
    `reseller_id` = ?
";

$rs = exec_query($sql, $query, array($_SESSION['user_created_by']));

if (!Config::get('ISPCP_SUPPORT_SYSTEM') || $rs->fields['support_system'] == 'no') {
	user_goto('index.php');
}

if (isset($_GET['ticket_id']) && $_GET['ticket_id'] !== '') {

	$ticket_id = $_GET['ticket_id'];
	$user_id = $_SESSION['user_id'];

	$query = <<<SQL_QUERY
		SELECT
			`ticket_status`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
		AND
			(`ticket_from` = ? OR `ticket_to` = ?)
SQL_QUERY;

	$rs = exec_query($sql, $query, array($ticket_id, $user_id, $user_id));
	if ($rs->RecordCount() == 0) {
		user_goto('ticket_system.php');
	}

	$back_url = ($rs->fields['ticket_status'] == 0) ? "ticket_closed.php" : "ticket_system.php";

	$ticket_id = $_GET['ticket_id'];

	$query = <<<SQL_QUERY
		DELETE FROM
			`tickets`
		WHERE
			`ticket_id` = ?
		OR
			`ticket_reply` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($ticket_id, $ticket_id));
	while (!$rs->EOF) {
		$rs->MoveNext();
	}

	write_log($_SESSION['user_logged'].": deletes support ticket");
	set_page_message(tr('Support ticket deleted successfully!'));
	user_goto($back_url);
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'open') {
	$user_id = $_SESSION['user_id'];

	$query = <<<SQL_QUERY
		DELETE FROM
			`tickets`
		WHERE
			`ticket_from` = ?
		AND
			`ticket_status` != '0'
SQL_QUERY;

	$rs = exec_query($sql, $query, array($user_id));

	while (!$rs->EOF) {
		$rs->MoveNext();
	}
	set_page_message(tr('All open support tickets deleted successfully!'));
	user_goto('ticket_system.php');
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'closed') {
	$user_id = $_SESSION['user_id'];

	$query = <<<SQL_QUERY
		DELETE FROM
			`tickets`
		WHERE
			`ticket_from` = ?
		AND
			`ticket_status` = '0'
SQL_QUERY;

	$rs = exec_query($sql, $query, array($user_id));

	while (!$rs->EOF) {
		$rs->MoveNext();
	}
	set_page_message(tr('All closed support tickets deleted successfully!'));
	user_goto('ticket_closed.php');
} else {
	user_goto('ticket_system.php');
}
