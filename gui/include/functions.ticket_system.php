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

/**
 * Gets the last modifikation date of a ticket.
 *
 * @author		ispCP Team
 * @author		Benedikt Heintel
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		1.0
 *
 * @access	public
 * @param	reference	$sql		reference to sql connection
 * @param	int			$ticket_id	ticket to get last date for
 * @return	date					last date
 */
function ticketGetLastDate(&$sql, $ticket_id) {
	$query = <<<SQL_QUERY
		SELECT
			`ticket_date`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
		OR
			`ticket_reply` = ?
		ORDER BY
			`ticket_date` DESC
SQL_QUERY;

	$rs = exec_query($sql, $query, array($ticket_id, $ticket_id));

	$date_formt = Config::get('DATE_FORMAT');
	return date($date_formt, $rs->fields['ticket_date']); // last date
}

/**
 * Informs an user about a ticket creation/update and writes a line to the log.
 *
 * @author		ispCP Team
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		1.0
 *
 * @access	public
 * @param	string		$to_id				reference to sql connection
 * @param	string		$from_id			ticket to get last date for
 * @param	string		$ticket_subject		ticket subject
 * @param	string  	$ticket_message		ticket content / message
 * @param	int			$ticket_status		ticket status
 * @param	int			$urgency			ticket urgency
 */
function send_tickets_msg($to_id, $from_id, $ticket_subject, $ticket_message, $ticket_status, $urgency) {
	$sql = Database::getInstance();
	global $admin_login;
	// To information
	$query = "SELECT `fname`, `lname`, `email`, `admin_name` FROM `admin` WHERE `admin_id` = ?";

	$res = exec_query($sql, $query, $to_id);
	$to_email = $res->fields['email'];
	$to_fname = $res->fields['fname'];
	$to_lname = $res->fields['lname'];
	$to_uname = $res->fields['admin_name'];
	// From information
	$query = "SELECT `fname`, `lname`, `email`, `admin_name` FROM `admin` WHERE `admin_id` = ?";

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
	$message .= "\n".tr("Priority: %s\n", "{PRIORITY}");
	$message .= "\n" . $ticket_message;
	$message .= "\n\n" . tr("Log in to answer") . ' ' . Config::get('BASE_SERVER_VHOST_PREFIX') . Config::get('BASE_SERVER_VHOST');

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

	$priority = get_ticket_urgency($urgency);

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

	$headers = "From: " . $from . "\n";

	$headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit\n";

	$headers .= "X-Mailer: ispCP " . Config::get('Version') . " Tickets Mailer";

	$mail_result = mail($to, encode($subject), $message, $headers);
	$mail_status = ($mail_result) ? 'OK' : 'NOT OK';
	write_log(sprintf("%s send ticket To: %s, From: %s, Status: %s!", $_SESSION['user_logged'], $toname . ": " . $to_email, $fromname . ": " . $from_email, $mail_status));
}
