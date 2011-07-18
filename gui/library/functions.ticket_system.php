<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-MSCP | http://i-mscp.net
 * @version     SVN: $Id$
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/**
 * Checks if the ticket system is globally enabled and if the specific user has
 * the right to access it.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $user_id Id of the user created the current user or null if admin
 * @return boolean
 */
function hasTicketSystem($user_id = null)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if (!$cfg->IMSCP_SUPPORT_SYSTEM) {
        return false;
    } elseif ($user_id !== null) {
        $query = "SELECT`support_system`  FROM `reseller_props` WHERE `reseller_id` = ?";
        $stmt = exec_query($query, $user_id);

        if ($stmt->fields['support_system'] == 'no') {
            return false;
        }
    }

    return true;
}

/**
 * Gets the status of the ticket.
 *
 * Possible status values:
 *    0 - closed
 *    1 - new
 *    2 - answered by reseller
 *    3 - read (if status was 2 or 4)
 *    4 - answered by client
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticket_id the ticket ID
 * @return int ticket status ID
 */
function getTicketStatus($ticket_id)
{
    $query = "
        SELECT
            `ticket_status`
        FROM
			`tickets`
        WHERE
            `ticket_id` = ?
        AND
            (`ticket_from` = ? OR `ticket_to` = ?)
    ";
    $stmt = exec_query($query, array(
                                        $ticket_id,
                                        $_SESSION['user_id'],
                                        $_SESSION['user_id']
                                   ));

    return $stmt->fields['ticket_status'];
}

/**
 * Changes the status of the ticket.
 *
 * Possible status values are:
 *
 *    0 - closed
 *    1 - new
 *    2 - answered by reseller
 *    3 - read (if status was 2 or 4)
 *    4 - answered by client
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticket_id ticket Id
 * @param int $ticket_status New status id
 */
function changeTicketStatus($ticket_id, $ticket_status)
{
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
	";
    exec_query($query, array(
                                  $ticket_status,
                                  $ticket_id,
                                  $ticket_id,
                                  $_SESSION['user_id'],
                                  $_SESSION['user_id']));
}

/**
 * Creates the ticket and informs the recipient.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $user_id The ID of the user
 * @param int $admin_id The ID of the user's creator
 * @param int $urgency The ticket's urgency
 * @param String $subject The ticket's subject
 * @param String $message The ticket's message
 * @param int $userLevel The user's level (client = 1; reseller = 2)
 */
function createTicket($user_id, $admin_id, $urgency, $subject, $message,
    $userLevel)
{
    if ($userLevel < 1 || $userLevel > 2)
        throw new iMSCP_Exception('User level is not valid.');

    $ticket_date = time();
    $subject = clean_input($subject);
    $user_message = clean_input($message);
    $ticket_status = 1;
    $ticket_reply = 0;

    $query = "
		INSERT INTO
		    `tickets` (
		        `ticket_level`, `ticket_from`,	`ticket_to`, `ticket_status`,
		        `ticket_reply`, `ticket_urgency`, `ticket_date`, `ticket_subject`,
		        `ticket_message`
		    ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
	";
    exec_query($query, array(
                                 $userLevel, $user_id, $admin_id, $ticket_status,
                                 $ticket_reply, $urgency, $ticket_date, $subject,
                                 $user_message));

    set_page_message(tr('Your message has been sent.'), 'success');
    sendTicketNotification($admin_id, $user_id, $subject, $user_message,
                           $ticket_reply, $urgency);
}

/**
 * Updates the ticket with a new answer and informs the recipient.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticket_id id of the ticket's parent ticket
 * @param int $user_id Id of the user
 * @param int $urgency The parent ticket's urgency
 * @param String $subject The parent ticket's subject
 * @param String $message The ticket replys' message
 * @param int $ticketLevel The tickets's level (1 = user; 2 = super)
 * @param int $userLevel The user's level (1 = client; 2 = reseller; 3 = admin)
 */
function updateTicket($ticket_id, $user_id, $urgency, $subject, $message,
    $ticketLevel, $userLevel)
{
    $ticket_date = time();
    $subject = clean_input($subject);
    $user_message = clean_input($message);

    $query = "
		SELECT
			`ticket_from`, `ticket_to`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
	";
    $stmt = exec_query($query, $ticket_id);

    /* Ticket levels:
     *  1:      Client -> Reseller
     *  2:      Reseller -> Admin
     *  NULL:   Reply
     */
    if (($ticketLevel == 1 && $userLevel == 1) ||
        ($ticketLevel == 2 && $userLevel == 2)
    ) {
        $ticket_to = $stmt->fields['ticket_to'];
        $ticket_from = $stmt->fields['ticket_from'];
    } else {
        $ticket_to = $stmt->fields['ticket_from'];
        $ticket_from = $stmt->fields['ticket_to'];
    }

    $query = "
		INSERT INTO
			`tickets` (
			    `ticket_from`, `ticket_to`, `ticket_status`, `ticket_reply`,
			    `ticket_urgency`, `ticket_date`, `ticket_subject`, `ticket_message`
			) VALUES (
			    ?, ?, ?, ?, ?, ?, ?, ?
			)
	";

    exec_query($query, array($ticket_from, $ticket_to, null, $ticket_id,
                                  $urgency, $ticket_date, $subject, $user_message));

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

    set_page_message(tr('Your message has been sent'), 'success');

    sendTicketNotification($ticket_to, $ticket_from, $subject, $user_message,
                           $ticket_id, $urgency);
}

/**
 * Reads the user's level from ticket info.
 *
 * @param int $ticket_id Ticket id
 * @return int User's level (1 = user, 2 = super)
 */
function getUserLevel($ticket_id)
{
    // Get info about the type of message
    $query = "SELECT `ticket_level` FROM `tickets` WHERE `ticket_id` = ?";
    $stmt = exec_query($query, $ticket_id);

    return $stmt->fields['ticket_level'];
}

/**
 * Close the given ticket.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticket_id Ticket id
 */
function closeTicket($ticket_id)
{
    changeTicketStatus($ticket_id, 0);
    set_page_message(tr('Ticket was closed!'));
}

/**
 * Open the given ticket.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticket_id Ticket id
 */
function openTicket($ticket_id)
{
    changeTicketStatus($ticket_id, 3);
    set_page_message(tr('Ticket was reopened!'));
}

/**
 * Get priority as translated string.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticket_urgency values from 1 to 4
 * @return string translated priority string
 */
function getTicketUrgency($ticket_urgency)
{
    switch ($ticket_urgency) {
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
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticket_id Id of the ticket to display
 * @return string Formatted ticket sender
 */
function getTicketSender($ticket_id)
{
    $query = "
		SELECT
            `a`.`admin_name`, `a`.`fname`, `a`.`lname`
		FROM
			`tickets` `t`
	    LEFT JOIN
	        `admin` `a` ON (`t`.`ticket_from` = `a`.`admin_id`)
		WHERE
			`ticket_id` = ?
	";
    $stmt = exec_query($query, $ticket_id);

    $from_user_name = decode_idna($stmt->fields['admin_name']);
    $from_first_name = $stmt->fields['fname'];
    $from_last_name = $stmt->fields['lname'];

    return $from_first_name . " " . $from_last_name . " (" . $from_user_name . ")";
}

/**
 * Gets the last modifikation date of a ticket.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticket_id Ticket to get last date for
 * @return string|date Last date translated 'Never' string if no date is set
 */
function ticketGetLastDate($ticket_id)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $query = "
		SELECT
			`ticket_date`
		FROM
			`tickets`
		WHERE
			`ticket_reply` = ?
		ORDER BY
			`ticket_date` DESC
	";
    $stmt = exec_query($query, $ticket_id);

    if (null == $stmt->fields['ticket_date']) {
        return tr('Never');
    }

    $date_format = $cfg->DATE_FORMAT;
    return date($date_format, $stmt->fields['ticket_date']);
}

/**
 * Generates the list with all closed tickets
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $user_id The ID of the admin
 * @param int $start The first ticket to show
 * @param int $count The maximal count of shown tickets
 * @param String $userLevel The user level
 * @param String $status Status of the tickets: 'open' and 'closed' allowed
 */
function generateTicketList($tpl, $user_id, $start, $count, $userLevel, $status)
{
    $count_query = "
		SELECT
			COUNT(`ticket_id`) AS count
		FROM
			`tickets`
		WHERE
			(`ticket_from` = ? OR `ticket_to` = ?)
		AND
			`ticket_reply` = 0
		AND
	";

    if ($status == 'open') {
        $count_query .= " `ticket_status` != 0";
    } else {
        $count_query .= " `ticket_status` = 0";
    }

    $rs = exec_query($count_query, array($user_id, $user_id));
    $records_count = $rs->fields['count'];

    $query = "
		SELECT
			`ticket_id`, `ticket_status`, `ticket_urgency`, `ticket_level`,
			`ticket_date`, `ticket_subject`
		FROM
			`tickets`
		WHERE
			(`ticket_from` = ? OR `ticket_to` = ?)
		AND
			`ticket_reply` = 0
		AND
	";

    if ($status == 'open') {
        $query .= " `ticket_status` != 0";
    } else {
        $query .= " `ticket_status` = 0";
    }

    $query .= " ORDER BY `ticket_date` DESC LIMIT $start,$count";

    $rs = exec_query($query, array($user_id, $user_id));

    if ($rs->recordCount() == 0) {
        $tpl->assign(array(
                          'TICKETS_LIST' => '',
                          'SCROLL_PREV' => '',
                          'SCROLL_NEXT' => ''));

        set_page_message(tr('You don\'t have support tickets.'));
    } else {
        $prev_si = $start - $count;
        if ($start == 0) {
            $tpl->assign('SCROLL_PREV', '');
        } else {
            $tpl->assign(array(
                              'SCROLL_PREV_GRAY' => '',
                              'PREV_PSI' => $prev_si));
        }

        $next_si = $start + $count;
        if ($next_si + 1 > $records_count) {
            $tpl->assign('SCROLL_NEXT', '');
        } else {
            $tpl->assign(array(
                              'SCROLL_NEXT_GRAY' => '',
                              'NEXT_PSI' => $next_si));
        }

        $i = 0;
        while (!$rs->EOF) {
            $ticket_status = $rs->fields['ticket_status'];
            $ticket_level = $rs->fields['ticket_level'];

            if ($ticket_status == 1) {
                $tpl->assign('NEW', tr('[New]'));
            } elseif ($ticket_status == 2 &&
                      (($ticket_level == 1 && $userLevel == "client") ||
                       ($ticket_level == 2 && $userLevel == "reseller"))
            ) {
                $tpl->assign('NEW', tr('[Re]'));
            } elseif ($ticket_status == 4 && (($ticket_level == 1
                                               && $userLevel == "reseller") ||
                                              ($ticket_level == 2 &&
                                               $userLevel == "admin"))
            ) {
                $tpl->assign('NEW', tr('[Re]'));
            } else {
                $tpl->assign('NEW', ' ');
            }

            $tpl->assign(array(
                              'URGENCY' => getTicketUrgency($rs->fields['ticket_urgency']),
                              'FROM' => tohtml(getTicketSender($rs->fields['ticket_id'])),
                              'LAST_DATE' => ticketGetLastDate($rs->fields['ticket_id']),
                              'SUBJECT' => tohtml($rs->fields['ticket_subject']),
                              'SUBJECT2' => addslashes(clean_html($rs->fields['ticket_subject'])),
                              'ID' => $rs->fields['ticket_id'],
                              'CONTENT' => ($i % 2 == 0) ? 'content' : 'content2'));

            $tpl->parse('TICKETS_ITEM', '.tickets_item');
            $rs->moveNext();
            $i++;
        }
    }
}

/**
 * Gets the content of the selected ticket and generates its output.
 *
 * @author    Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $ticket_id Id of the ticket to display
 * @param int $user_id Id of the user
 * @param int $screenwidth The width of the display
 */
function showTicketContent($tpl, $ticket_id, $user_id, $screenwidth)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $query = "
		SELECT
			`ticket_id`, `ticket_status`, `ticket_reply`, `ticket_urgency`,
			`ticket_date`, `ticket_subject`, `ticket_message`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
		AND
			(`ticket_from` = ? OR `ticket_to` = ?)
	";
    $stmt = exec_query($query, array($ticket_id, $user_id, $user_id));

    if (!$stmt->recordCount()) {
        $tpl->assign('TICKETS_LIST', '');
        set_page_message(tr('Ticket not found!'));
    } else {
        $ticket_urgency = $stmt->fields['ticket_urgency'];
        $ticket_subject = $stmt->fields['ticket_subject'];
        $ticket_status = $stmt->fields['ticket_status'];

        if ($ticket_status == 0) {
            $tr_action = tr("Open ticket");
            $action = "open";
        } else {
            $tr_action = tr("Close ticket");
            $action = "close";
        }

        $from = getTicketSender($ticket_id);
        $ticket_content = wordwrap($stmt->fields['ticket_message'],
                                   round(($screenwidth - 200) / 7), "\n");

        $tpl->assign(array(
                          'TR_ACTION' => $tr_action,
                          'ACTION' => $action,
                          'DATE' => date($cfg->DATE_FORMAT, $stmt->fields['ticket_date']),
                          'SUBJECT' => tohtml($ticket_subject),
                          'TICKET_CONTENT' => nl2br(tohtml($ticket_content)),
                          'ID' => $stmt->fields['ticket_id'],
                          'URGENCY' => getTicketUrgency($ticket_urgency),
                          'URGENCY_ID' => $ticket_urgency,
                          'FROM' => tohtml($from)));

        $tpl->parse('TICKETS_ITEM', 'tickets_item');
        showTicketReplies($tpl, $ticket_id, $screenwidth);
    }
}

/**
 * Gets the answers of the selected ticket and generates its output.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param iMSCP_pTemplate $tpl The Template object
 * @param int $ticket_id Id of the ticket to display
 * @param int $screenwidth The width of the display
 */
function showTicketReplies($tpl, $ticket_id, $screenwidth)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $query = "
		SELECT
			`ticket_id`, `ticket_urgency`, `ticket_date`, `ticket_message`
		FROM
			`tickets`
		WHERE
			`ticket_reply` = ?
		ORDER BY
			`ticket_date` ASC
	";

    $stmt = exec_query($query, $ticket_id);

    if ($stmt->recordCount()) {
        while (!$stmt->EOF) {
            $ticket_id = $stmt->fields['ticket_id'];
            $ticket_date = $stmt->fields['ticket_date'];
            $ticket_message = wordwrap($stmt->fields['ticket_message'],
                                       round(($screenwidth - 200) / 7), "\n");

            $tpl->assign(array(
                              'FROM' => getTicketSender($ticket_id),
                              'DATE' => date($cfg->DATE_FORMAT, $ticket_date),
                              'TICKET_CONTENT' => nl2br(tohtml($ticket_message))));
            $tpl->parse('TICKETS_ITEM', '.tickets_item');
            $stmt->moveNext();
        }
    }
}

/**
 * Informs an user about a ticket creation/update and writes a line to the log.
 *
 * @param int $to_id ticket recipient
 * @param int $from_id ticket sender
 * @param string $ticket_subject ticket subject
 * @param string $ticket_message ticket content / message
 * @param int $ticket_status ticket status
 * @param int $urgency ticket urgency
 */
function sendTicketNotification($to_id, $from_id, $ticket_subject, $ticket_message,
    $ticket_status, $urgency)
{

    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    // To information
    $query = "
        SELECT
			`fname`, `lname`, `email`, `admin_name`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";

    $stmt = exec_query($query, $to_id);
    $to_email = $stmt->fields['email'];
    $to_fname = $stmt->fields['fname'];
    $to_lname = $stmt->fields['lname'];
    $to_uname = $stmt->fields['admin_name'];

    // From information
    $query = "
        SELECT
			`fname`, `lname`, `email`, `admin_name`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
    ";

    $stmt = exec_query($query, $from_id);
    $from_email = $stmt->fields['email'];
    $from_fname = $stmt->fields['fname'];
    $from_lname = $stmt->fields['lname'];
    $from_uname = $stmt->fields['admin_name'];

    // Prepare message
    $subject = tr("[Ticket]") . " {SUBJ}";
    if ($ticket_status == 0) {
        $message = tr("Hello %s!\n\nYou have a new ticket:\n", "{TO_NAME}");
    } else {
        $message = tr("Hello %s!\n\nYou have an answer for this ticket:\n", "{TO_NAME}");
    }
    $message .= "\n" . tr("Priority: %s\n", "{PRIORITY}");
    $message .= "\n" . $ticket_message;
    $message .= "\n\n" . tr("Log in to answer") . ' ' . $cfg->BASE_SERVER_VHOST_PREFIX .
                $cfg->BASE_SERVER_VHOST;

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

    write_log(sprintf("%s send ticket To: %s, From: %s, Status: %s!",
                  $_SESSION['user_logged'], $toname . ": " . $to_email, $fromname .
                    ": " . $from_email,
                  $mail_status), E_USER_NOTICE);
}

/**
 * Deletes a ticket.
 *
 * @param int $ticket_id Ticket ID
 * @return void
 */
function deleteTicket($ticket_id)
{
    $query = "DELETE FROM `tickets` WHERE `ticket_id` = ? OR `ticket_reply` = ?";
    exec_query($query, array($ticket_id, $ticket_id));
}

/**
 * Deletes all open/closed tickets that are belong to an user.
 *
 * @param string $status Status of the action 'open' or 'closed' allowed
 * @param int $user_id The user's ID
 * @return void
 */
function deleteTickets($status, $user_id)
{
    $query = "
		DELETE FROM
			`tickets`
		WHERE
			(`ticket_from` = ? OR `ticket_to` = ?)
		AND
	";

    if ($status == 'open') {
        $query .= " `ticket_status` != 0";
    } else {
        $query .= " `ticket_status` = 0";
    }

    exec_query($query, array($user_id, $user_id));
}
