<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * @param int $userId Id of the user created the current user or null if admin
 * @return boolean
 */
function hasTicketSystem($userId = null)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if (!$cfg->IMSCP_SUPPORT_SYSTEM) {
        return false;
    } elseif ($userId !== null) {
        $query = "SELECT`support_system` FROM `reseller_props` WHERE `reseller_id` = ?";
        $stmt = exec_query($query, $userId);

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
 * @param int $ticketId the ticket ID
 * @return int ticket status ID
 */
function getTicketStatus($ticketId)
{
    $userId = $_SESSION['user_id'];

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
    $stmt = exec_query($query, array($ticketId, $userId, $userId));

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
 * @param int $ticketId ticket Id
 * @param int $ticketStatus New status id
 */
function changeTicketStatus($ticketId, $ticketStatus)
{
    $userId = $_SESSION['user_id'];

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
    exec_query($query, array($ticketStatus, $ticketId, $ticketId, $userId, $userId));
}

/**
 * Creates the ticket and informs the recipient.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $userId The ID of the user
 * @param int $adminId The ID of the user's creator
 * @param int $urgency The ticket's urgency
 * @param String $subject The ticket's subject
 * @param String $message The ticket's message
 * @param int $userLevel The user's level (client = 1; reseller = 2)
 */
function createTicket($userId, $adminId, $urgency, $subject, $message,
    $userLevel)
{
    if ($userLevel < 1 || $userLevel > 2) {
        throw new iMSCP_Exception('User level is not valid.');
    }

    $ticketDate = time();
    $subject = clean_input($subject);
    $userMessage = clean_input($message);
    $ticketStatus = 1;
    $ticketReply = 0;

    $query = "
        INSERT INTO `tickets` (
            `ticket_level`, `ticket_from`,	`ticket_to`, `ticket_status`,
            `ticket_reply`, `ticket_urgency`, `ticket_date`, `ticket_subject`,
            `ticket_message`
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ";
    exec_query($query, array($userLevel, $userId, $adminId, $ticketStatus,
                            $ticketReply, $urgency, $ticketDate, $subject,
                            $userMessage));

    set_page_message(tr('Your message has been sent.'), 'success');
    sendTicketNotification($adminId, $userId, $subject, $userMessage, $ticketReply,
                           $urgency);
}

/**
 * Updates the ticket with a new answer and informs the recipient.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticketId id of the ticket's parent ticket
 * @param int $user_id Id of the user
 * @param int $urgency The parent ticket's urgency
 * @param String $subject The parent ticket's subject
 * @param String $message The ticket replys' message
 * @param int $ticketLevel The tickets's level (1 = user; 2 = super)
 * @param int $userLevel The user's level (1 = client; 2 = reseller; 3 = admin)
 */
function updateTicket($ticketId, $user_id, $urgency, $subject, $message,
    $ticketLevel, $userLevel)
{
    $ticketDate = time();
    $subject = clean_input($subject);
    $userMessage = clean_input($message);

    $query = "SELECT `ticket_from`, `ticket_to` FROM `tickets` WHERE `ticket_id` = ?";
    $stmt = exec_query($query, $ticketId);

    /* Ticket levels:
     *  1:      Client -> Reseller
     *  2:      Reseller -> Admin
     *  NULL:   Reply
     */
    if (($ticketLevel == 1 && $userLevel == 1) ||
        ($ticketLevel == 2 && $userLevel == 2)
    ) {
        $ticketTo = $stmt->fields['ticket_to'];
        $ticketFrom = $stmt->fields['ticket_from'];
    } else {
        $ticketTo = $stmt->fields['ticket_from'];
        $ticketFrom = $stmt->fields['ticket_to'];
    }

    $query = "
        INSERT INTO `tickets` (
            `ticket_from`, `ticket_to`, `ticket_status`, `ticket_reply`,
            `ticket_urgency`, `ticket_date`, `ticket_subject`, `ticket_message`
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?
        )
    ";

    exec_query($query, array($ticketFrom, $ticketTo, null, $ticketId, $urgency,
                            $ticketDate, $subject, $userMessage));

    $ticketStatus = getTicketStatus($ticketId);

    if ($userLevel != 2) {
        // Level User: Set ticket status to "client answered"
        if ($ticketLevel == 1 && ($ticketStatus == 0 || $ticketStatus == 3)) {
            changeTicketStatus($ticketId, 4);
        }
            // Level Super: set ticket status to "reseller answered"
        else if ($ticketLevel == 2 && ($ticketStatus == 0 || $ticketStatus == 3)) {
            changeTicketStatus($ticketId, 2);
        }
    } else {
        // Set ticket status to "reseller answered" or "client answered" depending
        // on ticket
        if ($ticketLevel == 1 && ($ticketStatus == 0 || $ticketStatus == 3)) {
            changeTicketStatus($ticketId, 2);
        } elseif ($ticketLevel == 2 && ($ticketStatus == 0 || $ticketStatus == 3)) {
            changeTicketStatus($ticketId, 4);
        }
    }

    set_page_message(tr('Your message has been sent.'), 'success');

    sendTicketNotification($ticketTo, $ticketFrom, $subject, $userMessage, $ticketId,
                           $urgency);
}

/**
 * Reads the user's level from ticket info.
 *
 * @param int $ticketId Ticket id
 * @return int User's level (1 = user, 2 = super)
 */
function getUserLevel($ticketId)
{
    // Get info about the type of message
    $query = "SELECT `ticket_level` FROM `tickets` WHERE `ticket_id` = ?";
    $stmt = exec_query($query, $ticketId);

    return $stmt->fields['ticket_level'];
}

/**
 * Close the given ticket.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticketId Ticket id
 */
function closeTicket($ticketId)
{
    changeTicketStatus($ticketId, 0);
    set_page_message(tr('Ticket successfully closed.'), 'success');
}

/**
 * Open the given ticket.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticketId Ticket id
 */
function openTicket($ticketId)
{
    changeTicketStatus($ticketId, 3);
    set_page_message(tr('Ticket successfully reopened.'), 'success');
}

/**
 * Get priority as translated string.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticketUrgency values from 1 to 4
 * @return string translated priority string
 */
function getTicketUrgency($ticketUrgency)
{
    switch ($ticketUrgency) {
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
 * @param int $ticketId Id of the ticket to display
 * @return string Formatted ticket sender
 */
function getTicketSender($ticketId)
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
    $stmt = exec_query($query, $ticketId);

    $fromUsername = decode_idna($stmt->fields['admin_name']);
    $fromFirstname = $stmt->fields['fname'];
    $fromLastname = $stmt->fields['lname'];

    return $fromFirstname . ' ' . $fromLastname . ' (' . $fromUsername . ')';
}

/**
 * Gets the last modifikation date of a ticket.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param int $ticketId Ticket to get last date for
 * @return string|date Last date translated 'Never' string if no date is set
 */
function ticketGetLastDate($ticketId)
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
    $stmt = exec_query($query, $ticketId);

    if (null == $stmt->fields['ticket_date']) {
        return tr('Never');
    }

    return date($cfg->DATE_FORMAT, $stmt->fields['ticket_date']);
}

/**
 * Generates the list with all closed tickets
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $userId The ID of the admin
 * @param int $start The first ticket to show
 * @param int $count The maximal count of shown tickets
 * @param String $userLevel The user level
 * @param String $status Status of the tickets: 'open' and 'closed' allowed
 */
function generateTicketList($tpl, $userId, $start, $count, $userLevel, $status)
{
    $countQuery = "
        SELECT
            COUNT(`ticket_id`) AS `cnt`
        FROM
            `tickets`
        WHERE
            (`ticket_from` = ? OR `ticket_to` = ?)
        AND
            `ticket_reply` = 0
        AND
    ";

    if ($status == 'open') {
        $countQuery .= '`ticket_status` != 0';
    } else {
        $countQuery .= '`ticket_status` = 0';
    }

    $stmt = exec_query($countQuery, array($userId, $userId));
    $recordsCount = $stmt->fields['cnt'];

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
        $query .= '`ticket_status` != 0';
    } else {
        $query .= '`ticket_status` = 0';
    }

    $query .= " ORDER BY `ticket_date` DESC LIMIT $start,$count";
    $stmt = exec_query($query, array($userId, $userId));

    if ($stmt->recordCount() == 0) {
        $tpl->assign(array(
                          'TICKETS_LIST' => '',
                          'SCROLL_PREV' => '',
                          'SCROLL_NEXT' => ''));

        set_page_message(tr("You don't have support tickets."));
    } else {
        $prevSi = $start - $count;
        if ($start == 0) {
            $tpl->assign('SCROLL_PREV', '');
        } else {
            $tpl->assign(array(
                              'SCROLL_PREV_GRAY' => '',
                              'PREV_PSI' => $prevSi));
        }

        $nextSi = $start + $count;

        if ($nextSi + 1 > $recordsCount) {
            $tpl->assign('SCROLL_NEXT', '');
        } else {
            $tpl->assign(array(
                              'SCROLL_NEXT_GRAY' => '',
                              'NEXT_PSI' => $nextSi));
        }

        while (!$stmt->EOF) {
            $ticketStatus = $stmt->fields['ticket_status'];
            $ticketLevel = $stmt->fields['ticket_level'];

            if ($ticketStatus == 1) {
                $tpl->assign('NEW', tr('[New]'));
            } elseif ($ticketStatus == 2 &&
                     (($ticketLevel == 1 && $userLevel == 'client') ||
                     ($ticketLevel == 2 && $userLevel == 'reseller'))
            ) {
                $tpl->assign('NEW', tr('[Re]'));
            } elseif ($ticketStatus == 4 && (($ticketLevel == 1
                                               && $userLevel == 'reseller') ||
                                              ($ticketLevel == 2 &&
                                               $userLevel == 'admin'))
            ) {
                $tpl->assign('NEW', tr('[Re]'));
            } else {
                $tpl->assign('NEW', ' ');
            }

            $tpl->assign(array(
                              'URGENCY' => getTicketUrgency($stmt->fields['ticket_urgency']),
                              'FROM' => tohtml(getTicketSender($stmt->fields['ticket_id'])),
                              'LAST_DATE' => ticketGetLastDate($stmt->fields['ticket_id']),
                              'SUBJECT' => tohtml($stmt->fields['ticket_subject']),
                              'SUBJECT2' => addslashes(clean_html($stmt->fields['ticket_subject'])),
                              'ID' => $stmt->fields['ticket_id']));

            $tpl->parse('TICKETS_ITEM', '.tickets_item');
            $stmt->moveNext();
        }
    }
}

/**
 * Gets the content of the selected ticket and generates its output.
 *
 * @author    Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $ticketId Id of the ticket to display
 * @param int $userId Id of the user
 * @param int $screenWidth The width of the display
 */
function showTicketContent($tpl, $ticketId, $userId, $screenWidth)
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
    $stmt = exec_query($query, array($ticketId, $userId, $userId));

    if (!$stmt->recordCount()) {
        $tpl->assign('TICKETS_LIST', '');
        set_page_message(tr('Ticket not found.'));
    } else {
        $ticketUrgency = $stmt->fields['ticket_urgency'];
        $ticketSubject = $stmt->fields['ticket_subject'];
        $ticketStatus = $stmt->fields['ticket_status'];

        if ($ticketStatus == 0) {
            $trAction = tr("Open ticket");
            $action = 'open';
        } else {
            $trAction = tr("Close ticket");
            $action = 'close';
        }

        $from = getTicketSender($ticketId);
        $ticketContent = wordwrap($stmt->fields['ticket_message'],
                                   round(($screenWidth - 200) / 7), "\n");

        $tpl->assign(array(
                          'TR_ACTION' => $trAction,
                          'ACTION' => $action,
                          'DATE' => date($cfg->DATE_FORMAT, $stmt->fields['ticket_date']),
                          'SUBJECT' => tohtml($ticketSubject),
                          'TICKET_CONTENT' => nl2br(tohtml($ticketContent)),
                          'ID' => $stmt->fields['ticket_id'],
                          'URGENCY' => getTicketUrgency($ticketUrgency),
                          'URGENCY_ID' => $ticketUrgency,
                          'FROM' => tohtml($from)));

        $tpl->parse('TICKETS_ITEM', 'tickets_item');
        showTicketReplies($tpl, $ticketId, $screenWidth);
    }
}

/**
 * Gets the answers of the selected ticket and generates its output.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @param iMSCP_pTemplate $tpl The Template object
 * @param int $ticketId Id of the ticket to display
 * @param int $screenWidth The width of the display
 */
function showTicketReplies($tpl, $ticketId, $screenWidth)
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
    $stmt = exec_query($query, $ticketId);

    if ($stmt->recordCount()) {
        while (!$stmt->EOF) {
            $ticketId = $stmt->fields['ticket_id'];
            $ticketDate = $stmt->fields['ticket_date'];
            $ticketMessage = wordwrap($stmt->fields['ticket_message'],
                                       round(($screenWidth - 200) / 7), "\n");

            $tpl->assign(array(
                              'FROM' => getTicketSender($ticketId),
                              'DATE' => date($cfg->DATE_FORMAT, $ticketDate),
                              'TICKET_CONTENT' => nl2br(tohtml($ticketMessage))));

            $tpl->parse('TICKETS_ITEM', '.tickets_item');
            $stmt->moveNext();
        }
    }
}

/**
 * Informs an user about a ticket creation/update and writes a line to the log.
 *
 * @param int $toId ticket recipient
 * @param int $fromId ticket sender
 * @param string $ticketSubject ticket subject
 * @param string $ticketMessage ticket content / message
 * @param int $ticketStatus ticket status
 * @param int $urgency ticket urgency
 */
function sendTicketNotification($toId, $fromId, $ticketSubject, $ticketMessage,
    $ticketStatus, $urgency)
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
    $stmt = exec_query($query, $toId);

    $toEmail = $stmt->fields['email'];
    $toFname = $stmt->fields['fname'];
    $toLname = $stmt->fields['lname'];
    $toUname = $stmt->fields['admin_name'];

    // From information
    $query = "
        SELECT
            `fname`, `lname`, `email`, `admin_name`
        FROM
            `admin`
        WHERE
            `admin_id` = ?
    ";
    $stmt = exec_query($query, $fromId);

    $fromEmail = $stmt->fields['email'];
    $fromFname = $stmt->fields['fname'];
    $fromLname = $stmt->fields['lname'];
    $fromUname = $stmt->fields['admin_name'];

    // Prepare message
    $subject = tr('[Ticket]') . ' {SUBJ}';

    if ($ticketStatus == 0) {
        $message = tr("Hello %s,\n\nYou have a new ticket:\n", '{TO_NAME}');
    } else {
        $message = tr("Hello %s,\n\nYou have an answer for this ticket:\n", '{TO_NAME}');
    }

    $message .= "\n" . tr("Priority: %s\n", '{PRIORITY}');
    $message .= "\n" . $ticketMessage;
    $message .= "\n\n" . tr('Log in to answer') . ' ' . $cfg->BASE_SERVER_VHOST_PREFIX .
                $cfg->BASE_SERVER_VHOST;

    // Format addresses
    if ($fromFname && $fromLname) {
        $from = '"' . encode($fromFname . ' ' . $fromLname) . '" <' . $fromEmail . '>';
        $fromname = "$fromFname $fromLname";
    } else {
        $from = $fromEmail;
        $fromname = $fromUname;
    }

    if ($toFname && $toLname) {
        $to = '"' . encode($toFname . ' ' . $toLname) . '" <' . $toEmail . '>';
        $toname = "$toFname $toLname";
    } else {
        $toname = $toUname;
        $to = $toEmail;
    }

    $priority = getTicketUrgency($urgency);

    // Prepare and send mail
    $search = array();
    $replace = array();

    $search [] = '{SUBJ}';
    $replace[] = $ticketSubject;
    $search [] = '{TO_NAME}';
    $replace[] = $toname;
    $search [] = '{FROM_NAME}';
    $replace[] = $fromname;
    $search [] = '{PRIORITY}';
    $replace[] = $priority;

    $subject = str_replace($search, $replace, $subject);
    $message = str_replace($search, $replace, $message);

    $headers = 'From: ' . $from . "\n" .
               "MIME-Version: 1.0\nContent-Type: text/plain;" .
               "charset=utf-8\nContent-Transfer-Encoding: 8bit\n" .
               'X-Mailer: i-MSCP ' . $cfg->Version . ' Tickets Mailer';

    $mail_result = mail($to, encode($subject), $message, $headers);
    $mail_status = ($mail_result) ? 'OK' : 'NOT OK';

    $toname = tohtml($toname);
    $fromname = tohtml($fromname);

    write_log(sprintf('%s send ticket To: %s, From: %s, Status: %s.',
                      $_SESSION['user_logged'],
                      $toname . ': ' . $toEmail, $fromname . ': ' . $fromEmail,
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
        $query .= '`ticket_status` != 0';
    } else {
        $query .= '`ticket_status` = 0';
    }

    exec_query($query, array($user_id, $user_id));
}
