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

require '../include/i-mscp-lib.php';

check_login(__FILE__);

$admin_id = $_SESSION['user_created_by'];

if (!hasTicketSystem($admin_id)) {
	user_goto('index.php');
}

$back_url = 'ticket_system.php';
$user_id = $_SESSION['user_id'];

if (isset($_GET['ticket_id']) && $_GET['ticket_id'] !== '') {

	$ticket_id = $_GET['ticket_id'];

	$query = "
		SELECT
			`ticket_status`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
		AND
			(`ticket_from` = ? OR `ticket_to` = ?)
	;";

	$rs = exec_query($sql, $query, array($ticket_id, $user_id, $user_id));

	if ($rs->recordCount() == 0) {
		user_goto('ticket_system.php');
	}

	$back_url = (getTicketStatus($ticket_id) == 0) ?
		'ticket_closed.php' : 'ticket_system.php';

	deleteTicket($ticket_id);

	write_log(sprintf("%s: deletes support ticket %d", $_SESSION['user_logged'],
			$ticket_id));
	set_page_message(tr('Support ticket deleted successfully!'));
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'open') {

	deleteTickets('open', $user_id);

	write_log(sprintf("%s: deletes all open support tickets.", $_SESSION['user_logged']));
	set_page_message(tr('All open support tickets deleted successfully!'));
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'closed') {

	deleteTickets('closed', $user_id);

	write_log(sprintf("%s: deletes all closed support ticket.", $_SESSION['user_logged']));
	set_page_message(tr('All closed support tickets deleted successfully!'));
	$back_url = 'ticket_closed.php';
}

user_goto($back_url);
