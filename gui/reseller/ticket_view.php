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

$cfg = ispCP_Registry::get('Config');

$tpl = new ispCP_pTemplate();
$tpl->define_dynamic('page', $cfg->RESELLER_TEMPLATE_PATH . '/ticket_view.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('tickets_list', 'page');
$tpl->define_dynamic('tickets_item', 'tickets_list');

// page functions

/**
 * Checks if the reseller's admin has a support system.
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $sql	the SQL object
 * @param int $reseller_id	the ID of the reseller's admin
 * @return boolean
 */
function hasTicketSystem(&$sql, $admin_id) {
	$cfg = ispCP_Registry::get('Config');

	$query = "
	  SELECT
		`support_system`
	  FROM
		`reseller_props`
	  WHERE
		`reseller_id` = ?
	;";

	$rs = exec_query($sql, $query, $admin_id);

	if (!$cfg->ISPCP_SUPPORT_SYSTEM || $rs->fields['support_system'] == 'no')
		return false;

	return true;
}

/**
 * Gets the content of the selected ticket and generates its output.
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $tpl	the Template object
 * @param reference $sql	the SQL object
 * @param int $ticket_id	the ID of the ticket to display
 * @param int $screenwidth	the width of the display
 */
function showTicketContent(&$tpl, &$sql, $ticket_id, $screenwidth) {
	$cfg = ispCP_Registry::get('Config');

	$user_id = $_SESSION['user_id'];
	$query = "
		SELECT
			`ticket_id`,
			`ticket_status`,
			`ticket_reply`,
			`ticket_urgency`,
			`ticket_date`,
			`ticket_subject`,
			`ticket_message`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
		AND
			(`ticket_from` = ? OR `ticket_to` = ?)
	;";
	$rs = exec_query($sql, $query, array($ticket_id, $user_id, $user_id));

	if ($rs->recordCount() == 0) {
		$tpl->assign('TICKETS_LIST', '');
		set_page_message(tr('Ticket not found!'));
	} else {
		$ticket_urgency = $rs->fields['ticket_urgency'];
		$ticket_status = $rs->fields['ticket_status'];

		if ($ticket_status == 0) {
			$tr_action = tr("Open ticket");
			$action = "open";
		} else {
			$tr_action = tr("Close ticket");
			$action = "close";
		}

        $from = getTicketSender($tpl, $sql, $ticket_id);
        $ticket_content = wordwrap($rs->fields['ticket_message'],
                round(($screenwidth-200) / 7), "\n");

        $tpl->assign(
			array(
                'URGENCY'           => get_ticket_urgency($ticket_urgency),
			    'URGENCY_ID'        => $ticket_urgency,
				'TR_ACTION'         => $tr_action,
				'ACTION'            => $action,
				'DATE'              => date($cfg->DATE_FORMAT, $rs->fields['ticket_date']),
				'SUBJECT'           => tohtml($rs->fields['ticket_subject']),
				'TICKET_CONTENT'    => nl2br(tohtml($ticket_content)),
				'ID'                => $rs->fields['ticket_id'],
                'FROM'		        => tohtml($from)
			)
		);

		$tpl->parse('TICKETS_ITEM', '.tickets_item');
		showTicketReplies($tpl, $sql, $ticket_id, $screenwidth);
	}
}

/**
 * Gets the answers of the selected ticket and generates its output.
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $tpl	the Template object
 * @param reference $sql	the SQL object
 * @param int $ticket_id	the ID of the ticket to display
 * @param int $screenwidth	the width of the display
 */
function showTicketReplies(&$tpl, &$sql, &$ticket_id, &$screenwidth) {
	$cfg = ispCP_Registry::get('Config');

	$query = "
		SELECT
			`ticket_id`,
			`ticket_urgency`,
			`ticket_date`,
			`ticket_message`
		FROM
			`tickets`
		WHERE
			`ticket_reply` = ?
		ORDER BY
			`ticket_date` ASC
	;";

	$rs = exec_query($sql, $query, $ticket_id);

	if ($rs->recordCount() == 0) {
		return;
	}

	while (!$rs->EOF) {
		$ticket_id      = $rs->fields['ticket_id'];
		$ticket_date    = $rs->fields['ticket_date'];
		$ticket_message = $rs->fields['ticket_message'];
		$ticket_content = wordwrap($ticket_message,
                round(($screenwidth-200) / 7), "\n");

		$tpl->assign(
			array(
				'DATE'              => date($cfg->DATE_FORMAT, $ticket_date),
				'TICKET_CONTENT'    => nl2br(tohtml($ticket_content))
			)
		);
		getTicketSender($tpl, $sql, $ticket_id);
		$tpl->parse('TICKETS_ITEM', '.tickets_item');
		$rs->moveNext();
	}
}

/**
 * Gets the sender of a ticket answer.
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $tpl	the Template object
 * @param reference $sql	the SQL object
 * @param int $ticket_id	the ID of the ticket to display
 */
function getTicketSender(&$tpl, &$sql, $ticket_id) {

	$query = "
		SELECT
            `a`.`admin_name`,
			`a`.`fname`,
			`a`.`lname`
		FROM
			`tickets` AS `t` JOIN `admin` AS `a`
        ON
            `t`.`ticket_from` = `a`.`admin_id`
		WHERE
			`ticket_id` = ?
	;";

	$rs = exec_query($sql, $query, $ticket_id);
	$from_user_name = decode_idna($rs->fields['admin_name']);
	$from_first_name = $rs->fields['fname'];
	$from_last_name = $rs->fields['lname'];

	$from_name = $from_first_name . " " . $from_last_name . " (" . $from_user_name . ")";

	return $from_name;
}

/**
 * Updates the ticket with a new answer and informs the recipient.
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $sql	the SQL object
 * @param int $ticket_id	the ID of the ticket to display
 */
function updateTicket(&$sql, $ticket_id) {
	$user_id = $_SESSION['user_id'];

	if ($_POST['uaction'] == "close") {
		// close ticket
		closeTicket($sql, $ticket_id);
		return;
	} elseif ($_POST['uaction'] == "open") {
		// open ticket
		openTicket($sql, $ticket_id);
		return;
	} elseif (empty($_POST['user_message'])) {
		// no message check->error
		set_page_message(tr('Please type your message!'));
		return;
	}

	$ticket_date = time();
	$subject = clean_input($_POST['subject']);
	$user_message = clean_input($_POST["user_message"]);
	$ticket_reply = $_GET['ticket_id'];
	$urgency = $_POST['urgency'];
	$ticket_from = $user_id;

    // Get info about the type of message
    $query = "
		SELECT
			`ticket_level`,
			`ticket_from`,
			`ticket_to`,
			`ticket_status`,
			`ticket_reply`,
			`ticket_urgency`,
			`ticket_date`,
			`ticket_subject`,
			`ticket_message`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
	;";

	$rs = exec_query($sql, $query, $ticket_reply);

	$ticket_level = $rs->fields['ticket_level'];

    /* Levels:
     *  1:      <tbd>
     *  2:      <tbd>
     *  NULL:   <tbd>
     */
	if ($ticket_level != 1) {
		$ticket_to = $rs->fields['ticket_from'];
		$ticket_from = $rs->fields['ticket_to'];
	} else {
		$ticket_to = $rs->fields['ticket_to'];
		$ticket_from = $rs->fields['ticket_from'];
	}

	$urgency = $_POST['urgency'];

	$query = "
		INSERT INTO `tickets`
			(`ticket_from`,
			`ticket_to`,
			`ticket_status`,
			`ticket_reply`,
			`ticket_urgency`,
			`ticket_date`,
			`ticket_subject`,
			`ticket_message`)
		VALUES
			(?, ?, ?, ?, ?, ?, ?, ?)
	;";

	$rs = exec_query($sql, $query, array($ticket_from, $ticket_to, null,
			$ticket_reply, $urgency, $ticket_date, $subject, $user_message));

	$ticket_status = getTicketStatus($sql, $ticket_id);

	// Set ticket status to "reseller answered"
	if ($ticket_status == 0 || $ticket_status == 3) {
		changeTicketStatus($sql, $ticket_id, 2);
	}

	set_page_message(tr('Your message has been sent'));
	send_tickets_msg($ticket_to, $ticket_from, $subject, $user_message, $ticket_reply, $urgency);
	user_goto('ticket_system.php');
}

/**
 * Gets the status of the ticket.
 * Possible status values:
 *	0 - closed
 *	1 - new
 *	2 - answered by reseller
 *	3 - read (if status was 2 or 4)
 *	4 - answered by client
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $sql	the SQL object
 * @param int $ticket_id	the ticket ID
 * @return int				ticket status ID
 */
function getTicketStatus(&$sql, $ticket_id) {
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

	$rs = exec_query($sql, $query, array($ticket_id, $_SESSION['user_id'], $_SESSION['user_id']));
	return $rs->fields['ticket_status'];
}

/**
 * Changes the status of the ticket.
 * Possible status values:
 *	0 - closed
 *	1 - new
 *	2 - answered by reseller
 *	3 - read (if status was 2 or 4)
 *	4 - answered by client
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $sql		the SQL object
 * @param int $ticket_id		the ticket ID
 * @param int $ticket_status	new status ID
 */
function changeTicketStatus(&$sql, $ticket_id, $ticket_status) {
	$query = "
		UPDATE
			`tickets`
		SET
			`ticket_status` = ?
		WHERE
			`ticket_id` = ?
		AND
			(`ticket_from` = ? OR `ticket_to` = ?)
	;";

	$rs = exec_query($sql, $query, array(
			$ticket_status,
			$ticket_id,
			$_SESSION['user_id'],
			$_SESSION['user_id']
		));
}

/**
 * Close the current ticket.
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $sql		the SQL object
 * @param int $ticket_id		the ticket ID
 */
function closeTicket(&$sql, $ticket_id) {
	changeTicketStatus($sql, $ticket_id, 0);
	set_page_message(tr('Ticket was closed!'));
}

/**
 * Open the current ticket.
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $sql		the SQL object
 * @param int $ticket_id		the ticket ID
 */
function openTicket(&$sql, $ticket_id) {
	changeTicketStatus($sql, $ticket_id, 3);
	set_page_message(tr('Ticket was reopened!'));
}

// common page data

$tpl->assign(
	array(
		'TR_CLIENT_VIEW_TICKET_PAGE_TITLE'	=> tr('ispCP - Reseller: Support System: View Ticket'),
		'THEME_COLOR_PATH'					=> "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'						=> tr('encoding'),
		'ISP_LOGO'							=> get_logo($_SESSION['user_id'])
	)
);

// dynamic page data

$admin_id = $_SESSION['user_created_by'];

if (!hasTicketSystem($sql, $admin_id)) {
	user_goto('index.php');
}

if (isset($_GET['ticket_id'])) {
	$ticket_id = $_GET['ticket_id'];
	$screenwidth = 1024;

	if (isset($_GET['screenwidth'])) {
		$screenwidth = $_GET['screenwidth'];
	} else if(isset($_POST['screenwidth'])) {
		$screenwidth = $_POST['screenwidth'];
	}

	if ($screenwidth < 639) {
		$screenwidth = 1024;
	}
	$tpl->assign('SCREENWIDTH', $screenwidth);

	// if status "new" or "Answer by client" set to "read"
	$status = getTicketStatus($sql, $ticket_id);
	if ($status == 1 || $status == 4) {
		changeTicketStatus($sql, $ticket_id, 3);
	}

	if (isset($_POST['uaction'])) {
		updateTicket($sql, $ticket_id);
	}

	showTicketContent($tpl, $sql, $ticket_id, $screenwidth);
} else {
	set_page_message(tr('Ticket not found!'));

	user_goto('ticket_system.php');
}

// static page messages

gen_reseller_mainmenu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/main_menu_ticket_system.tpl');
gen_reseller_menu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/menu_ticket_system.tpl');

gen_logged_from($tpl);

$tpl->assign(
	array('TR_VIEW_SUPPORT_TICKET' => tr('View support ticket'),
		'TR_TICKET_URGENCY' => tr('Priority'),
		'TR_TICKET_SUBJECT' => tr('Subject'),
		'TR_TICKET_DATE' => tr('Date'),
		'TR_DELETE' => tr('Delete'),
		'TR_NEW_TICKET_REPLY' => tr('Send message reply'),
		'TR_REPLY' => tr('Send reply'),
		'TR_TICKET_FROM' => tr('From'),
		'TR_OPEN_TICKETS' => tr('Open tickets'),
		'TR_CLOSED_TICKETS' => tr('Closed tickets'),
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
