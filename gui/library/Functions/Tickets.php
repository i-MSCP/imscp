<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Creates a ticket and informs the recipient
 *
 * @param int $userId User unique identifier
 * @param int $adminId Creator unique identifier
 * @param int $urgency The ticket's urgency
 * @param String $subject Ticket's subject
 * @param String $message Ticket's message
 * @param int $userLevel User's level (client = 1; reseller = 2)
 * @return bool TRUE on success, FALSE otherwise
 */
function createTicket($userId, $adminId, $urgency, $subject, $message, $userLevel)
{
    if ($userLevel < 1 || $userLevel > 2) {
        set_page_message(tr('Wrong user level provided.'), 'error');
        return false;
    }

    $ticketDate = time();
    $subject = clean_input($subject);
    $userMessage = clean_input($message);
    $ticketStatus = 1;
    $ticketReply = 0;

    exec_query(
        '
            INSERT INTO tickets (
                ticket_level, ticket_from, ticket_to, ticket_status, ticket_reply, ticket_urgency, ticket_date,
                ticket_subject, ticket_message
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ',
        array($userLevel, $userId, $adminId, $ticketStatus, $ticketReply, $urgency, $ticketDate, $subject, $userMessage)
    );

    set_page_message(tr('Your message has been successfully sent.'), 'success');
    _sendTicketNotification($adminId, $subject, $userMessage, $ticketReply, $urgency);

    return true;
}

/**
 * Gets the content of the selected ticket and generates its output
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $ticketId Id of the ticket to display
 * @param int $userId Id of the user
 * @return bool TRUE if ticket is found, FALSE otherwise
 */
function showTicketContent($tpl, $ticketId, $userId)
{
    $cfg = iMSCP_Registry::get('config');
    $stmt = exec_query(
        '
            SELECT ticket_id, ticket_status, ticket_reply, ticket_urgency, ticket_date, ticket_subject, ticket_message
            FROM tickets WHERE ticket_id = ? AND (ticket_from = ? OR ticket_to = ?)
        ',
        array($ticketId, $userId, $userId)
    );

    if (!$stmt->rowCount()) {
        $tpl->assign('TICKETS_LIST', '');
        set_page_message(tr("Ticket with Id '%d' was not found.", $ticketId), 'error');
        return false;
    }

    $row = $stmt->fetchRow();
    $ticketUrgency = $row['ticket_urgency'];
    $ticketSubject = $row['ticket_subject'];
    $ticketStatus = $row['ticket_status'];

    if ($ticketStatus == 0) {
        $trAction = tr('Open ticket');
        $action = 'open';
    } else {
        $trAction = tr('Close the ticket');
        $action = 'close';
    }

    $from = _getTicketSender($ticketId);

    $tpl->assign(array(
        'TR_TICKET_ACTION' => $trAction,
        'TICKET_ACTION_VAL' => $action,
        'TICKET_DATE_VAL' => date($cfg['DATE_FORMAT'], $row['ticket_date']),
        'TICKET_SUBJECT_VAL' => tohtml($ticketSubject),
        'TICKET_CONTENT_VAL' => nl2br(tohtml($row['ticket_message'])),
        'TICKET_ID_VAL' => $row['ticket_id'],
        'TICKET_URGENCY_VAL' => getTicketUrgency($ticketUrgency),
        'TICKET_URGENCY_ID_VAL' => $ticketUrgency,
        'TICKET_FROM_VAL' => tohtml($from)
    ));
    $tpl->parse('TICKETS_ITEM', 'tickets_item');

    _showTicketReplies($tpl, $ticketId);
    return true;
}

/**
 * Updates a ticket with a new answer and informs the recipient
 *
 * @param int $ticketId id of the ticket's parent ticket
 * @param int $userId User unique identifier
 * @param int $urgency The parent ticket's urgency
 * @param String $subject The parent ticket's subject
 * @param String $message The ticket replys' message
 * @param int $ticketLevel The tickets's level (1 = user; 2 = super)
 * @param int $userLevel The user's level (1 = client; 2 = reseller; 3 = admin)
 * @return bool TRUE on success, FALSE otherwise
 */
function updateTicket($ticketId, $userId, $urgency, $subject, $message, $ticketLevel, $userLevel)
{
    $db = iMSCP_Database::getInstance();
    $ticketDate = time();
    $subject = clean_input($subject);
    $userMessage = clean_input($message);
    $stmt = exec_query(
        '
            SELECT ticket_from, ticket_to, ticket_status FROM tickets
            WHERE ticket_id = ? AND (ticket_from = ? OR ticket_to = ?)
        ',
        array($ticketId, $userId, $userId)
    );

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow();

        try {
            /* Ticket levels:
            *  1: Client -> Reseller
            *  2: Reseller -> Admin
            *  NULL: Reply
            */
            if (($ticketLevel == 1 && $userLevel == 1) || ($ticketLevel == 2 && $userLevel == 2)) {
                $ticketTo = $row['ticket_to'];
                $ticketFrom = $row['ticket_from'];
            } else {
                $ticketTo = $row['ticket_from'];
                $ticketFrom = $row['ticket_to'];
            }

            $ticketStatus = $row['ticket_status'];

            exec_query(
                '
                    INSERT INTO tickets (
                        ticket_from, ticket_to, ticket_status, ticket_reply, ticket_urgency, ticket_date,
                        ticket_subject, ticket_message
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ',
                array($ticketFrom, $ticketTo, null, $ticketId, $urgency, $ticketDate, $subject, $userMessage)
            );

            if ($userLevel != 2) {
                // Level User: Set ticket status to "client answered"
                if ($ticketLevel == 1 && ($ticketStatus == 0 || $ticketStatus == 3)) {
                    changeTicketStatus($ticketId, 4);
                    // Level Super: set ticket status to "reseller answered"
                } elseif ($ticketLevel == 2 && ($ticketStatus == 0 || $ticketStatus == 3)) {
                    changeTicketStatus($ticketId, 2);
                }
            } else {
                // Set ticket status to "reseller answered" or "client answered" depending on ticket
                if ($ticketLevel == 1 && ($ticketStatus == 0 || $ticketStatus == 3)) {
                    changeTicketStatus($ticketId, 2);
                } elseif ($ticketLevel == 2 && ($ticketStatus == 0 || $ticketStatus == 3)) {
                    if (!changeTicketStatus($ticketId, 4)) {
                        return false;
                    }
                }
            }

            set_page_message(tr('Your message has been successfully sent.'), 'success');
            _sendTicketNotification($ticketTo, $subject, $userMessage, $ticketId, $urgency);
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            set_page_message('System was unable to create ticket answer.', 'error');
            write_log(sprintf('System was unable to create ticket answer: %s', $e->getMessage()), E_USER_ERROR);
            return false;
        }
    } else {
        set_page_message(tr("Ticket with Id '%d' was not found.", $ticketId), 'error');
        return false;
    }
}

/**
 * Deletes a ticket
 *
 * @param int $ticketId Ticket unique identifier
 * @return void
 */
function deleteTicket($ticketId)
{
    exec_query('DELETE FROM tickets WHERE ticket_id = ? OR ticket_reply = ?', array($ticketId, $ticketId));
}

/**
 * Deletes all open/closed tickets that are belong to a user.
 *
 * @param string $status Ticket status ('open' or 'closed')
 * @param int $userId The user's ID
 * @return void
 */
function deleteTickets($status, $userId)
{
    $condition = $status == 'open' ? "ticket_status != '0'" : "ticket_status = '0'";
    exec_query("DELETE FROM tickets WHERE (ticket_from = ? OR ticket_to = ?) AND {$condition}", array($userId, $userId));
}

/**
 * Generates a ticket list
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $userId User unique identifier
 * @param int $start First ticket to show (pagination)
 * @param int $count Mmaximal count of shown tickets (pagination)
 * @param String $userLevel User level
 * @param String $status Status of the tickets to be showed: 'open' or 'closed'
 * @return void
 */
function generateTicketList($tpl, $userId, $start, $count, $userLevel, $status)
{
    $condition = ($status == 'open') ? "ticket_status != 0" : 'ticket_status = 0';
    $stmt = exec_query(
        "
            SELECT COUNT(ticket_id) AS cnt FROM tickets
            WHERE (ticket_from = ? OR ticket_to = ?) AND ticket_reply = '0' AND $condition
        "
        ,
        array($userId, $userId)
    );
    $row = $stmt->fetchRow();
    $rowsCount = $row['cnt'];

    if ($rowsCount != 0) {
        $stmt = exec_query(
            "
                SELECT ticket_id, ticket_status, ticket_urgency, ticket_level, ticket_date, ticket_subject
                FROM tickets WHERE (ticket_from = ? OR ticket_to = ?) AND ticket_reply = 0 AND $condition
                ORDER BY ticket_date DESC LIMIT {$start}, {$count}
            ",
            array($userId, $userId)
        );

        $prevSi = $start - $count;

        if ($start == 0) {
            $tpl->assign('SCROLL_PREV', '');
        } else {
            $tpl->assign(array(
                'SCROLL_PREV_GRAY' => '',
                'PREV_PSI' => $prevSi
            ));
        }

        $nextSi = $start + $count;

        if ($nextSi + 1 > $rowsCount) {
            $tpl->assign('SCROLL_NEXT', '');
        } else {
            $tpl->assign(array(
                'SCROLL_NEXT_GRAY' => '',
                'NEXT_PSI' => $nextSi
            ));
        }

        while ($row = $stmt->fetchRow()) {
            $ticketStatus = $row['ticket_status'];
            $ticketLevel = $row['ticket_level'];

            if ($ticketStatus == 1) {
                $tpl->assign('TICKET_STATUS_VAL', tr('[New]'));
            } elseif (
                $ticketStatus == 2 &&
                (($ticketLevel == 1 && $userLevel == 'client') || ($ticketLevel == 2 && $userLevel == 'reseller'))
            ) {
                $tpl->assign('TICKET_STATUS_VAL', tr('[Re]'));
            } elseif (
                $ticketStatus == 4 &&
                (($ticketLevel == 1 && $userLevel == 'reseller') || ($ticketLevel == 2 && $userLevel == 'admin'))
            ) {
                $tpl->assign('TICKET_STATUS_VAL', tr('[Re]'));
            } else {
                $tpl->assign('TICKET_STATUS_VAL', '[Read]');
            }

            $tpl->assign(array(
                'TICKET_URGENCY_VAL' => getTicketUrgency($row['ticket_urgency']),
                'TICKET_FROM_VAL' => tohtml(_getTicketSender($row['ticket_id'])),
                'TICKET_LAST_DATE_VAL' => _ticketGetLastDate($row['ticket_id']),
                'TICKET_SUBJECT_VAL' => tohtml($row['ticket_subject']),
                'TICKET_SUBJECT2_VAL' => addslashes(clean_html($row['ticket_subject'])),
                'TICKET_ID_VAL' => $row['ticket_id']
            ));
            $tpl->parse('TICKETS_ITEM', '.tickets_item');
        }
    } else { // no ticket to display
        $tpl->assign(array(
            'TICKETS_LIST' => '',
            'SCROLL_PREV' => '',
            'SCROLL_NEXT' => ''
        ));

        if ($status == 'open') {
            set_page_message(tr('You have no open tickets.'), 'static_info');
        } else {
            set_page_message(tr('You have no closed tickets.'), 'static_info');
        }
    }
}

/**
 * Closes the given ticket.
 *
 * @param int $ticketId Ticket id
 * @return bool TRUE on success, FALSE otherwise
 */
function closeTicket($ticketId)
{
    if (!changeTicketStatus($ticketId, 0)) {
        set_page_message(tr("Unable to close the ticket with Id '%s'.", $ticketId), 'error');
        write_log(sprintf("Unable to close the ticket with Id '%s'.", $ticketId), E_USER_ERROR);
        return false;
    }

    set_page_message(tr('Ticket successfully closed.'), 'success');
    return true;
}

/**
 * Reopens the given ticket
 *
 * @param int $ticketId Ticket id
 * @return bool TRUE on success, FALSE otherwise
 */
function reopenTicket($ticketId)
{
    if (!changeTicketStatus($ticketId, 3)) {
        set_page_message(tr("Unable to reopen ticket with Id '%s'.", $ticketId), 'error');
        write_log(sprintf("Unable to reopen ticket with Id '%s'.", $ticketId), E_USER_ERROR);
        return false;
    }

    set_page_message(tr('Ticket successfully reopened.'), 'success');
    return true;
}

/**
 * Returns ticket status
 *
 * Possible status values are:
 *  0 - closed
 *  1 - new
 *  2 - answered by reseller
 *  3 - read (if status was 2 or 4)
 *  4 - answered by client
 *
 * @param int $ticketId Ticket unique identifier
 * @return int ticket status identifier
 */
function getTicketStatus($ticketId)
{
    $stmt = exec_query(
        'SELECT ticket_status FROM tickets WHERE ticket_id = ? AND (ticket_from = ? OR ticket_to = ?)',
        array($ticketId, $_SESSION['user_id'], $_SESSION['user_id'])
    );

    $row = $stmt->fetchRow();
    return $row['ticket_status'];
}

/**
 * Changes ticket status
 *
 * Possible status values are:
 *
 *    0 - closed
 *    1 - new
 *    2 - answered by reseller
 *    3 - read (if status was 2 or 4)
 *    4 - answered by client
 *
 * @param int $ticketId Ticket unique identifier
 * @param int $ticketStatus New status identifier
 * @return bool TRUE if ticket status was changed, FALSE otherwise (eg. if ticket was not found)
 */
function changeTicketStatus($ticketId, $ticketStatus)
{
    $stmt = exec_query(
        '
            UPDATE tickets SET ticket_status = ?
            WHERE ticket_id = ? OR ticket_reply = ? AND (ticket_from = ? OR ticket_to = ?)
        ',
        array($ticketStatus, $ticketId, $ticketId, $_SESSION['user_id'], $_SESSION['user_id'])
    );

    if (!$stmt->rowCount()) {
        return false;
    }

    return true;
}

/**
 * Reads the user's level from ticket info
 *
 * @param int $ticketId Ticket id
 * @return int User's level (1 = user, 2 = super) or FALSE if ticket is not found
 */
function getUserLevel($ticketId)
{
    // Get info about the type of message
    $stmt = exec_query('SELECT ticket_level FROM tickets WHERE ticket_id = ?', $ticketId);
    if (!$stmt->rowCount()) {
        set_page_message(tr("Ticket with Id '%d' was not found.", $ticketId), 'error');
        return false;
    }

    $row = $stmt->fetchRow();
    return $row['ticket_level'];
}

/**
 * Returns translated ticket priority
 *
 * @param int $ticketUrgency Values from 1 to 4
 * @return string Translated priority string
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
 * Returns ticket'ssender of a ticket answer
 *
 * @access private
 * @usedby showTicketContent
 * @usedby generateTicketList
 * @usedby _showTicketReplies()
 * @param int $ticketId Id of the ticket to display
 * @return mixed Formatted ticket sender or FALSE if ticket is not found.
 */
function _getTicketSender($ticketId)
{
    $stmt = exec_query(
        '
            SELECT a.admin_name, a.fname, a.lname, a.admin_type
            FROM tickets t LEFT JOIN admin a ON (t.ticket_from = a.admin_id) WHERE ticket_id = ?
        ',
        $ticketId
    );

    if (!$stmt->rowCount()) {
        set_page_message(tr("Ticket with Id '%d' was not found.", $ticketId), 'error');
        return false;
    }

    $row = $stmt->fetchRow();

    if ($row['admin_type'] == 'user') {
        $fromUsername = decode_idna($row['admin_name']);
    } else {
        $fromUsername = $row['admin_name'];
    }

    $fromFirstname = $row['fname'];
    $fromLastname = $row['lname'];
    return $fromFirstname . ' ' . $fromLastname . ' (' . $fromUsername . ')';
}

/**
 * Returns the last modification date of a ticket
 *
 * @access private
 * @usedby generateTicketList
 * @param int $ticketId Ticket to get last date for
 * @return string Last modification date of a ticket
 */
function _ticketGetLastDate($ticketId)
{
    $cfg = iMSCP_Registry::get('config');
    $stmt = exec_query(
        'SELECT ticket_date FROM tickets WHERE ticket_reply = ? ORDER BY ticket_date DESC LIMIT 1', $ticketId
    );

    if (!$stmt->rowCount()) {
        return tr('Never');
    }

    $row = $stmt->fetchRow();
    return date($cfg['DATE_FORMAT'], $row['ticket_date']);
}

/**
 * Checks if the support ticket system is globally enabled and (optionaly) if a specific reseller has permissions to
 * access to it
 *
 * Note: If a reseller has not access to the support ticket system, it's means that
 * all his customers have not access to it too.
 *
 * @param int $userId OPTIONAL Id of the user created the current user or null if admin
 * @return bool TRUE if support ticket system is available, FALSE otherwise
 * @Todo: Allows to provides support ticket system as hosting plan option for clients
 */
function hasTicketSystem($userId = null)
{
    $cfg = iMSCP_Registry::get('config');

    if (!$cfg['IMSCP_SUPPORT_SYSTEM']) {
        return false;
    } elseif ($userId !== null) {
        $stmt = exec_query('SELECT support_system FROM reseller_props WHERE reseller_id = ?', $userId);
        if ($stmt->rowCount()) {
            $row = $stmt->fetchRow();
            if ($row['support_system'] == 'no') {
                return false;
            }
        } else {
            return false;
        }
    }

    return true;
}

/**
 * Gets the answers of the selected ticket and generates its output.
 *
 * @access private
 * @usedby showTicketContent()
 * @param iMSCP_pTemplate $tpl The Template object
 * @param int $ticketId Id of the ticket to display
 */
function _showTicketReplies($tpl, $ticketId)
{
    $cfg = iMSCP_Registry::get('config');
    $stmt = exec_query(
        '
            SELECT ticket_id, ticket_urgency, ticket_date, ticket_message FROM tickets
            WHERE ticket_reply = ? ORDER BY ticket_date ASC
        ',
        $ticketId
    );

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetchRow()) {
            $ticketId = $row['ticket_id'];
            $ticketDate = $row['ticket_date'];
            $tpl->assign(array(
                'TICKET_FROM_VAL' => _getTicketSender($ticketId),
                'TICKET_DATE_VAL' => date($cfg['DATE_FORMAT'], $ticketDate),
                'TICKET_CONTENT_VAL' => nl2br(tohtml($row['ticket_message']))
            ));
            $tpl->parse('TICKETS_ITEM', '.tickets_item');
        }
    }
}

/**
 * Notify users about new tickets and ticket answers
 *
 * @access private
 * @usedby updateTicket()
 * @usedby createTicket()
 * @param int $toId ticket recipient
 * @param string $ticketSubject ticket subject
 * @param string $ticketMessage ticket content / message
 * @param int $ticketStatus ticket status
 * @param int $urgency ticket urgency
 * @return bool TRUE on success, FALSE on failure
 */
function _sendTicketNotification($toId, $ticketSubject, $ticketMessage, $ticketStatus, $urgency)
{
    $stmt = exec_query('SELECT admin_name, fname, lname, email, admin_name FROM admin WHERE admin_id = ?', $toId);
    $toData = $stmt->fetchRow();

    if ($ticketStatus == 0) {
        $message = tr('Dear {NAME},

You have a new support ticket:

==========================================================================
Priority: {PRIORITY}

{MESSAGE}
==========================================================================

You can login at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}{BASE_SERVER_VHOST_PORT} to answer.

Please do not reply to this email.

___________________________
i-MSCP Mailer');
    } else {
        $message = tr('Dear {NAME},

You have a new answer to a support ticket:

==========================================================================
Priority: {PRIORITY}

{MESSAGE}
==========================================================================

You can login at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}{BASE_SERVER_VHOST_PORT} to answer.

Please do not reply to this email.

___________________________
i-MSCP Mailer');
    }

    $ret = send_mail(array(
        'mail_id' => 'support-ticket-notification',
        'fname' => $toData['fname'],
        'lname' => $toData['lname'],
        'username' => $toData['admin_name'],
        'email' => $toData['email'],
        'subject' => "i-MSCP - [Ticket] $ticketSubject",
        'message' => $message,
        'placeholders' => array(
            '{PRIORITY}' => getTicketUrgency($urgency),
            '{MESSAGE}' => $ticketMessage,
        )
    ));

    if (!$ret) {
        write_log(sprintf('Could not send ticket notification to %s', $toData['admin_name']), E_USER_ERROR);
        return false;
    }

    return true;
}
