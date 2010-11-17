<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/**
 * Checks if the ticket system is globally enabled and if the specific user has
 * the right to access it.
 *
 * @author	Benedikt Heintel <benedikt.heintel@i-mscp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param int $user_id		the ID of the user created the current user or null
 *							if admin
 * @return boolean
 */
function hasTicketSystem($user_id = null) {
	$cfg = iMSCP_Registry::get('Config');

	if (!$cfg->IMSCP_SUPPORT_SYSTEM) {
		return false;
	} elseif ($user_id !== null) {
		$sql = iMSCP_Registry::get('Db');

		$query = "
		  SELECT
			`support_system`
		  FROM
			`reseller_props`
		  WHERE
			`reseller_id` = ?
		;";

		$rs = exec_query($sql, $query, $user_id);

		if ($rs->fields['support_system'] == 'no') {
			return false;
		}
	}

	return true;
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
 * @author	Benedikt Heintel <benedikt.heintel@i-mscp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param int $ticket_id	the ticket ID
 * @return int				ticket status ID
 */
function getTicketStatus($ticket_id) {
	$sql = iMSCP_Registry::get('Db');

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

	$rs = exec_query($sql, $query, array(
			$ticket_id,
			$_SESSION['user_id'],
			$_SESSION['user_id']
		));

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
 * @author	Benedikt Heintel <benedikt.heintel@i-mscp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param int $ticket_id		the ticket ID
 * @param int $ticket_status	new status ID
 */
function changeTicketStatus($ticket_id, $ticket_status) {
	$sql = iMSCP_Registry::get('Db');

	$query = "
		UPDATE
			`tickets`
		SET
			`ticket_status` = ?
		WHERE
			`ticket_id` = ?
		OR
			`ticket_reply` = ?
		AND
			(`ticket_from` = ? OR `ticket_to` = ?)
	;";

	$rs = exec_query($sql, $query, array(
			$ticket_status,
			$ticket_id,
			$ticket_id,
			$_SESSION['user_id'],
			$_SESSION['user_id']
		));
}

/**
 * Creates the ticket and informs the recipient.
 *
 * @author	Benedikt Heintel <benedikt.heintel@i-mscp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param int $user_id	    the ID of the user
 * @param int $admin_id 	the ID of the user's creator
 * @param int $urgency		the ticket's urgency
 * @param String $subject	the ticket's subject
 * @param String $message	the ticket's message
 * @param int $userLevel	the user's level (client = 1; reseller = 2)
 */
function createTicket($user_id, $admin_id, $urgency, $subject, $message,
		$userLevel) {
	$sql = iMSCP_Registry::get('Db');

	if ($userLevel < 1 || $userLevel > 2)
		throw imscp_Exception("ERROR: User level is not valid!");

	$ticket_date = time();
	$subject = clean_input($subject);
	$user_message = clean_input($message);
	$ticket_status = 1;
	$ticket_reply = 0;

	$query = "
		INSERT INTO `tickets`
			(`ticket_level`, `ticket_from`,	`ticket_to`,
			 `ticket_status`, `ticket_reply`, `ticket_urgency`,
			 `ticket_date`, `ticket_subject`, `ticket_message`)
		VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?)
	;";

	exec_query($sql, $query,
		array(
			$userLevel, $user_id, $admin_id, $ticket_status,
			$ticket_reply, $urgency, $ticket_date, $subject, $user_message
		)
	);

	set_page_message(tr('Your message has been sent!'));
	sendTicketNotification($admin_id, $user_id, $subject, $user_message,
        $ticket_reply, $urgency);
}

/**
 * Updates the ticket with a new answer and informs the recipient.
 *
 * @author	Benedikt Heintel <benedikt.heintel@i-mscp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param int $ticket_id	the ID of the ticket's parent ticket
 * @param int $user_id		the ID of the user
 * @param int $urgency		the parent ticket's urgency
 * @param String $subject	the parent ticket's subject
 * @param String $message	the ticket replys' message
 * @param int $ticketLevel	the tickets's level (1 = user; 2 = super)
 * @param int $userLevel	the user's level (1 = client; 2 = reseller; 3 = admin)
 */
function updateTicket($ticket_id, $user_id, $urgency,
		$subject, $message, $ticketLevel, $userLevel) {
	$sql = iMSCP_Registry::get('Db');

	$ticket_date = time();
	$subject = clean_input($subject);
	$user_message = clean_input($message);

	$query = "
		SELECT
			`ticket_from`,
			`ticket_to`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
	";

	$rs = exec_query($sql, $query, $ticket_id);

    /* Ticket levels:
     *  1:      Client -> Reseller
     *  2:      Reseller -> Admin
     *  NULL:   Reply
     */
	if (($ticketLevel == 1 && $userLevel == 1) || 
		($ticketLevel == 2 && $userLevel == 2)) {
		$ticket_to   = $rs->fields['ticket_to'];
		$ticket_from = $rs->fields['ticket_from'];
	} else {
		$ticket_to   = $rs->fields['ticket_from'];
		$ticket_from = $rs->fields['ticket_to'];
	}

	$query = "
		INSERT INTO
			`tickets`
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
	";

	$rs = exec_query($sql, $query, array($ticket_from, $ticket_to, null,
			$ticket_id, $urgency, $ticket_date, $subject, $user_message)
	);

	$ticket_status = getTicketStatus($ticket_id);

	if ($userLevel != 2) {
		// Level User: Set ticket status to "client answered"
		if ($ticketLevel == 1 && ($ticket_status == 0 || $ticket_status == 3)) {
			changeTicketStatus($ticket_id, 4);
		}
		// Level Super: set ticket status to "reseller answered"
		else if ($ticketLevel == 2 && ($ticket_status == 0 || $ticket_status == 3)) {
			changeTicketStatus($ticket_id, 2);
		}
	} else {
		// Set ticket status to "reseller answered" or "client answered" depending
		// on ticket
		if ($ticketLevel == 1 && ($ticket_status == 0 || $ticket_status == 3)) {
			changeTicketStatus($ticket_id, 2);
		} elseif ($ticketLevel == 2 && ($ticket_status == 0 || $ticket_status == 3)) {
			changeTicketStatus($ticket_id, 4);
		}
	}

	set_page_message(tr('Your message has been sent'));
	sendTicketNotification($ticket_to, $ticket_from, $subject, $user_message,
			$ticket_id, $urgency);
}

/**
 * Reads the user's level from ticket info.
 *
 * @param int $ticket_id		the ticket ID
 * @return int					the user's level (1 = user, 2 = super)
 */
function getUserLevel($ticket_id) {
	$sql = iMSCP_Registry::get('Db');

    // Get info about the type of message
    $query = "
		SELECT
			`ticket_level`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
	;";

	$rs = exec_query($sql, $query, $ticket_id);

	return $rs->fields['ticket_level'];
}

/**
 * Close the given ticket (status 0).
 *
 * @author	Benedikt Heintel <benedikt.heintel@i-mscp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param int $ticket_id		the ticket ID
 */
function closeTicket($ticket_id) {
	changeTicketStatus($ticket_id, 0);
	set_page_message(tr('Ticket was closed!'));
}

/**
 * Open the given ticket (Status 3).
 *
 * @author	Benedikt Heintel <benedikt.heintel@i-mscp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param int $ticket_id		the ticket ID
 */
function openTicket($ticket_id) {
	changeTicketStatus($ticket_id, 3);
	set_page_message(tr('Ticket was reopened!'));
}

/**
 * Get priority as translated string.
 *
 * @author		ispCP Team
 * @author		Benedikt Heintel <benedikt.heintel@i-mscp.net>
 * @version		1.0.1
 *
 * @param int $ticket_urgency	values from 1 to 4
 * @return string				translated priority string
 */
function getTicketUrgency($ticket_urgency) {

	switch($ticket_urgency) {
		case 1:
			return tr('Low');
		case 3:
			return tr('High');
		case 4:
			return tr('Very high');
		case 2:
		default:
			return tr('Medium');
	}
}

/**
 * Gets the sender of a ticket answer.
 *
 * @author	Benedikt Heintel <benedikt.heintel@i-mscp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param int $ticket_id	the ID of the ticket to display
 * @return String			the formatted ticket sender
 */
function getTicketSender($ticket_id) {
	$sql = iMSCP_Registry::get('Db');

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

	return $from_first_name . " " . $from_last_name . " (" . $from_user_name . ")";
}

/**
 * Gets the last modifikation date of a ticket.
 *
 * @author		ispCP Team
 * @author		Benedikt Heintel
 * @version		1.0.0
 *
 * @access	public
 * @param int $ticket_id	ticket to get last date for
 * @return date				last date
 */
function ticketGetLastDate($ticket_id) {
	$cfg = iMSCP_Registry::get('Config');
	$sql = iMSCP_Registry::get('Db');

	$query = "
		SELECT
			`ticket_date`
		FROM
			`tickets`
		WHERE
			`ticket_reply` = ?
		ORDER BY
			`ticket_date` DESC
	;";

	$rs = exec_query($sql, $query, array($ticket_id));

	if($rs->fields['ticket_date'] == NULL) {
		return tr('Never');
	}

	$date_format = $cfg->DATE_FORMAT;
	return date($date_format, $rs->fields['ticket_date']); // last date
}

/**
 * Generates the list with all closed tickets
 *
 * @author	Benedikt Heintel <benedikt.heintel@i-mscp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $tpl    the TPL object
 * @param int $user_id      the ID of the admin
 * @param int $start		the first ticket to show
 * @param int $count		the maximal count of shown tickets
 * @param String $userLevel	the user level
 * @param String $status	status of the tickets: 'open' and 'closed' allowed
 */
function generateTicketList(&$tpl, $user_id, $start, $count, $userLevel, $status) {
	$sql = iMSCP_Registry::get('Db');

	$count_query = "
		SELECT
			COUNT(`ticket_id`) AS count
		FROM
			`tickets`
		WHERE
			(`ticket_from` = ? OR `ticket_to` = ?)
		AND
			`ticket_reply` = 0
		AND ";

	if ($status == 'open') {
		$count_query .= "`ticket_status` != 0;";
	} else {
		$count_query .= "`ticket_status` = 0;";
	}

	$rs = exec_query($sql, $count_query, array($user_id, $user_id));
	$records_count = $rs->fields['count'];

	$query = "
		SELECT
			`ticket_id`,
			`ticket_status`,
			`ticket_urgency`,
			`ticket_level`,
			`ticket_date`,
			`ticket_subject`
		FROM
			`tickets`
		WHERE
			(`ticket_from` = ? OR `ticket_to` = ?)
		AND
			`ticket_reply` = 0
		AND ";

	if ($status == 'open') {
		$query .= "`ticket_status` != 0 ";
	} else {
		$query .= "`ticket_status` = 0 ";
	}

	$query .= "
		ORDER BY
			`ticket_date` DESC
		LIMIT " .
			$start . ", " . $count
    . ";";

	$rs = exec_query($sql, $query, array($user_id, $user_id));

	if ($rs->recordCount() == 0) {
		$tpl->assign(
			array(
				'TICKETS_LIST'	=> '',
				'SCROLL_PREV'	=> '',
				'SCROLL_NEXT'	=> ''
			)
		);
		set_page_message(tr('You don\'t have support tickets.'));
	} else {
		$prev_si = $start - $count;
		if ($start == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_PREV_GRAY'	=> '',
					'PREV_PSI'			=> $prev_si
				)
			);
		}

		$next_si = $start + $count;
		if ($next_si + 1 > $records_count) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_NEXT_GRAY'	=> '',
					'NEXT_PSI'			=> $next_si
				)
			);
		}

		$i = 0;
		while (!$rs->EOF) {
			$ticket_status = $rs->fields['ticket_status'];
			$ticket_level = $rs->fields['ticket_level'];
			if ($ticket_status == 1) {
				$tpl->assign(array('NEW' => tr("[New]")));
			} elseif ($ticket_status == 2 && (($ticket_level == 1 &&
					$userLevel == "client") || ($ticket_level == 2 &&
					$userLevel == "reseller"))) {
				$tpl->assign(array('NEW' => tr("[Re]")));
			} elseif ($ticket_status == 4 && (($ticket_level == 1 &&
					$userLevel == "reseller") || ($ticket_level == 2 &&
					$userLevel == "admin"))) {
				$tpl->assign(array('NEW' => tr("[Re]")));
			} else {
				$tpl->assign(array('NEW' => " "));
			}


			$tpl->assign(
				array(
					'URGENCY'	=> getTicketUrgency($rs->fields['ticket_urgency']),
					'FROM'		=> tohtml(getTicketSender($rs->fields['ticket_id'])),
					'LAST_DATE'	=> ticketGetLastDate($rs->fields['ticket_id']),
					'SUBJECT'	=> tohtml($rs->fields['ticket_subject']),
					'SUBJECT2'	=> addslashes(clean_html($rs->fields['ticket_subject'])),
					'ID'		=> $rs->fields['ticket_id'],
					'CONTENT'	=> ($i % 2 == 0) ? 'content' : 'content2'
				)
			);

			$tpl->parse('TICKETS_ITEM', '.tickets_item');
			$rs->moveNext();
			$i++;
		}
	}
}

/**
 * Gets the content of the selected ticket and generates its output.
 *
 * @author	Benedikt Heintel <benedikt.heintel@i-mscp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $tpl	the Template object
 * @param int $ticket_id	the ID of the ticket to display
 * @param int $user_id		the ID of the user
 * @param int $screenwidth	the width of the display
 */
function showTicketContent(&$tpl, $ticket_id, $user_id, $screenwidth) {
	$cfg = iMSCP_Registry::get('Config');
	$sql = iMSCP_Registry::get('Db');

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
		$ticket_subject = $rs->fields['ticket_subject'];
		$ticket_status = $rs->fields['ticket_status'];

		if ($ticket_status == 0) {
			$tr_action = tr("Open ticket");
			$action = "open";
		} else {
			$tr_action = tr("Close ticket");
			$action = "close";
		}

		$from = getTicketSender($ticket_id);
		$ticket_content = wordwrap($rs->fields['ticket_message'],
				round(($screenwidth-200) / 7), "\n");

		$tpl->assign(
			array(
				'TR_ACTION'			=> $tr_action,
				'ACTION'			=> $action,
				'DATE'				=> date($cfg->DATE_FORMAT, $rs->fields['ticket_date']),
				'SUBJECT'			=> tohtml($ticket_subject),
				'TICKET_CONTENT'	=> nl2br(tohtml($ticket_content)),
				'ID'				=> $rs->fields['ticket_id'],
				'URGENCY'			=> getTicketUrgency($ticket_urgency),
				'URGENCY_ID'		=> $ticket_urgency,
				'FROM'		        => tohtml($from)
			)
		);

		$tpl->parse('TICKETS_ITEM', 'tickets_item');
		showTicketReplies($tpl, $ticket_id, $screenwidth);
	}
}

/**
 * Gets the answers of the selected ticket and generates its output.
 *
 * @author	Benedikt Heintel <benedikt.heintel@i-mscp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $tpl	the Template object
 * @param int $ticket_id	the ID of the ticket to display
 * @param int $screenwidth	the width of the display
 */
function showTicketReplies(&$tpl, $ticket_id, $screenwidth) {
	$cfg = iMSCP_Registry::get('Config');
	$sql = iMSCP_Registry::get('Db');

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
		$ticket_id		= $rs->fields['ticket_id'];
		$ticket_date	= $rs->fields['ticket_date'];
		$ticket_message = wordwrap($rs->fields['ticket_message'],
				round(($screenwidth-200) / 7), "\n");

		$tpl->assign(
			array(
				'FROM'				=> getTicketSender($ticket_id),
				'DATE'				=> date($cfg->DATE_FORMAT, $ticket_date),
				'TICKET_CONTENT'	=> nl2br(tohtml($ticket_message))
			)
		);
		$tpl->parse('TICKETS_ITEM', '.tickets_item');
		$rs->moveNext();
	}
}

/**
 * Informs an user about a ticket creation/update and writes a line to the log.
 *
 * @author		ispCP Team
 * @version		1.0.0
 *
 * @access	public
 * @param int $to_id				ticket recipient
 * @param int $from_id				ticket sender
 * @param string $ticket_subject	ticket subject
 * @param string $ticket_message	ticket content / message
 * @param int $ticket_status		ticket status
 * @param int $urgency				ticket urgency
 */
function sendTicketNotification($to_id, $from_id, $ticket_subject, 
		$ticket_message, $ticket_status, $urgency) {

	$cfg = iMSCP_Registry::get('Config');
	$sql = iMSCP_Registry::get('Db');

	// To information
	$query = "SELECT
			`fname`, `lname`, `email`, `admin_name`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
		;";

	$res = exec_query($sql, $query, $to_id);
	$to_email = $res->fields['email'];
	$to_fname = $res->fields['fname'];
	$to_lname = $res->fields['lname'];
	$to_uname = $res->fields['admin_name'];

	// From information
	$query = "SELECT
			`fname`, `lname`, `email`, `admin_name`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
		;";

	$res = exec_query($sql, $query, $from_id);
	$from_email = $res->fields['email'];
	$from_fname = $res->fields['fname'];
	$from_lname = $res->fields['lname'];
	$from_uname = $res->fields['admin_name'];

	// Prepare message
	$subject = tr("[Ticket]") . " {SUBJ}";
	if ($ticket_status == 0) {
		$message = tr("Hello %s!\n\nYou have a new ticket:\n", "{TO_NAME}");
	} else {
		$message = tr("Hello %s!\n\nYou have an answer for this ticket:\n", "{TO_NAME}");
	}
	$message .= "\n" . tr("Priority: %s\n", "{PRIORITY}");
	$message .= "\n" . $ticket_message;
	$message .= "\n\n" . tr("Log in to answer") . ' ' .
				$cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST;

	// Format addresses
	if ($from_fname && $from_lname) {
		$from = '"' . encode($from_fname . ' ' . $from_lname) . "\" <" . $from_email . ">";
		$fromname = "$from_fname $from_lname";
	} else {
		$from = $from_email;
		$fromname = $from_uname;
	}

	if ($to_fname && $to_lname) {
		$to = '"' . encode($to_fname . ' ' . $to_lname) . "\" <" . $to_email . ">";
		$toname = "$to_fname $to_lname";
	} else {
		$toname = $to_uname;
		$to = $to_email;
	}

	$priority = getTicketUrgency($urgency);

	// Prepare and send mail
	$search = array();
	$replace = array();

	$search [] = '{SUBJ}';
	$replace[] = $ticket_subject;
	$search [] = '{TO_NAME}';
	$replace[] = $toname;
	$search [] = '{FROM_NAME}';
	$replace[] = $fromname;
	$search [] = '{PRIORITY}';
	$replace[] = $priority;

	$subject = str_replace($search, $replace, $subject);
	$message = str_replace($search, $replace, $message);

	$headers = "From: " . $from . "\n" .
				"MIME-Version: 1.0\nContent-Type: text/plain; " .
				"charset=utf-8\nContent-Transfer-Encoding: 8bit\n" .
				"X-Mailer: i-MSCP " . $cfg->Version . " Tickets Mailer";

	$mail_result = mail($to, encode($subject), $message, $headers);
	$mail_status = ($mail_result) ? 'OK' : 'NOT OK';
	
	$toname = tohtml($toname);
	$fromname = tohtml($fromname);
	
	write_log(sprintf(
						"%s send ticket To: %s, From: %s, Status: %s!",
						$_SESSION['user_logged'],
						$toname . ": " . $to_email, $fromname . ": " . $from_email,
						$mail_status
					));
}

/**
 * Deletes the specified ticket.
 *
 * @param int $ticket_id	Ticket ID
 */
function deleteTicket($ticket_id) {
	$sql = iMSCP_Registry::get('Db');

	$query = "
		DELETE FROM
			`tickets`
		WHERE
			`ticket_id` = ?
		OR
			`ticket_reply` = ?
	;";

	$rs = exec_query($sql, $query, array($ticket_id, $ticket_id));

	while (!$rs->EOF) {
		$rs->moveNext();
	}
}

/**
 * Delets all open or closed tickets of an user.
 *
 * @param String $status	status of the action 'opend' or 'closed' allowed
 * @param int $user_id		the user's ID
 */
function deleteTickets($status, $user_id) {
	$sql = iMSCP_Registry::get('Db');

	$query = "
		DELETE FROM
			`tickets`
		WHERE
			(`ticket_from` = ? OR `ticket_to` = ?)
		AND";

	if ($status == 'open') {
		$query .= "`ticket_status` != 0;";
	} else {
		$query .= "`ticket_status` = 0;";
	}

	$rs = exec_query($sql, $query, array($user_id, $user_id));

	while (!$rs->EOF) {
		$rs->moveNext();
	}
}