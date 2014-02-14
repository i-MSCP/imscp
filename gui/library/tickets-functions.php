<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 */

/**
 * Creates a ticket and informs the recipient
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
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

	$query = '
		INSERT INTO `tickets` (
			`ticket_level`, `ticket_from`,	`ticket_to`, `ticket_status`, `ticket_reply`, `ticket_urgency`,
			`ticket_date`, `ticket_subject`, `ticket_message`
		) VALUES (
			?, ?, ?, ?, ?, ?, ?, ?, ?
		)
	';
	exec_query(
		$query,
		array($userLevel, $userId, $adminId, $ticketStatus, $ticketReply, $urgency, $ticketDate, $subject,$userMessage)
	);

	set_page_message(tr('Your message has been successfully sent.'), 'success');
	_sendTicketNotification($adminId, $userId, $subject, $userMessage, $ticketReply, $urgency);

	return true;
}

/**
 * Gets the content of the selected ticket and generates its output
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $ticketId Id of the ticket to display
 * @param int $userId Id of the user
 * @return bool TRUE if ticket is found, FALSE otherwise
 */
function showTicketContent($tpl, $ticketId, $userId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = '
		SELECT
			`ticket_id`, `ticket_status`, `ticket_reply`, `ticket_urgency`, `ticket_date`, `ticket_subject`,
			`ticket_message`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
		AND
			(`ticket_from` = ? OR `ticket_to` = ?)
	';
	$stmt = exec_query($query, array($ticketId, $userId, $userId));

	if (!$stmt->rowCount()) {
		$tpl->assign('TICKETS_LIST', '');
		set_page_message(tr("Ticket with Id '%d' was not found.", $ticketId), 'error');
		return false;
	}

	$ticketUrgency = $stmt->fields['ticket_urgency'];
	$ticketSubject = $stmt->fields['ticket_subject'];
	$ticketStatus = $stmt->fields['ticket_status'];

	if ($ticketStatus == 0) {
		$trAction = tr('Open ticket');
		$action = 'open';
	} else {
		$trAction = tr('Close the ticket');
		$action = 'close';
	}

	$from = _getTicketSender($ticketId);

	$tpl->assign(
		array(
			'TR_TICKET_ACTION' => $trAction,
			'TICKET_ACTION_VAL' => $action,
			'TICKET_DATE_VAL' => date($cfg->DATE_FORMAT, $stmt->fields['ticket_date']),
			'TICKET_SUBJECT_VAL' => tohtml($ticketSubject),
			'TICKET_CONTENT_VAL' => nl2br(tohtml($stmt->fields['ticket_message'])),
			'TICKET_ID_VAL' => $stmt->fields['ticket_id'],
			'TICKET_URGENCY_VAL' => getTicketUrgency($ticketUrgency),
			'TICKET_URGENCY_ID_VAL' => $ticketUrgency,
			'TICKET_FROM_VAL' => tohtml($from)
		)
	);

	$tpl->parse('TICKETS_ITEM', 'tickets_item');
	_showTicketReplies($tpl, $ticketId);

	return true;
}

/**
 * Updates a ticket with a new answer and informs the recipient
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
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
	/** @var $db iMSCP_Database */
	$db = iMSCP_Registry::get('db');

	$ticketDate = time();
	$subject = clean_input($subject);
	$userMessage = clean_input($message);

	$query = '
		SELECT
			`ticket_from`, `ticket_to`, `ticket_status`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
		AND
			(`ticket_from` = ? OR `ticket_to` = ?)
	';
	$stmt = exec_query($query, array($ticketId, $userId, $userId));

	if ($stmt->rowCount()) {
		try {
			/* Ticket levels:
			*  1: Client -> Reseller
			*  2: Reseller -> Admin
			*  NULL: Reply
			*/
			if (($ticketLevel == 1 && $userLevel == 1) || ($ticketLevel == 2 && $userLevel == 2)) {
				$ticketTo = $stmt->fields['ticket_to'];
				$ticketFrom = $stmt->fields['ticket_from'];
			} else {
				$ticketTo = $stmt->fields['ticket_from'];
				$ticketFrom = $stmt->fields['ticket_to'];
			}

			$query = "
				INSERT INTO `tickets` (
					`ticket_from`, `ticket_to`, `ticket_status`, `ticket_reply`, `ticket_urgency`, `ticket_date`,
					`ticket_subject`, `ticket_message`
				) VALUES (
					?, ?, ?, ?, ?, ?, ?, ?
				)
			";

			exec_query(
				$query,
				array($ticketFrom, $ticketTo, null, $ticketId, $urgency, $ticketDate, $subject, $userMessage)
			);

			$ticketStatus = $stmt->fields['ticket_status'];

			if ($userLevel != 2) {
				// Level User: Set ticket status to "client answered"
				if ($ticketLevel == 1 && ($ticketStatus == 0 || $ticketStatus == 3)) {
					changeTicketStatus($ticketId, 4);
				} // Level Super: set ticket status to "reseller answered"
				elseif ($ticketLevel == 2 && ($ticketStatus == 0 || $ticketStatus == 3)) {
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
			_sendTicketNotification($ticketTo, $ticketFrom, $subject, $userMessage, $ticketId, $urgency);
			return true;
		} catch (PDOException $e) {
			$db->rollBack();
			set_page_message('System was unable to create ticket answer.', 'error');
			write_log('System was unable to create ticket answer: ' . $e->getMessage(), E_USER_ERROR);
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
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param int $ticketId Ticket unique identifier
 * @return void
 */
function deleteTicket($ticketId)
{
	exec_query('DELETE FROM `tickets` WHERE `ticket_id` = ? OR `ticket_reply` = ?', array($ticketId, $ticketId));
}

/**
 * Deletes all open/closed tickets that are belong to a user.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param string $status Ticket status ('open' or 'closed')
 * @param int $userId The user's ID
 * @return void
 */
function deleteTickets($status, $userId)
{
	$condition = ($status == 'open') ? "`ticket_status` != '0'" : "`ticket_status` = '0'";
	$query = "DELETE FROM `tickets` WHERE (`ticket_from` = ? OR `ticket_to` = ?) AND {$condition}";
	exec_query($query, array($userId, $userId));
}

/**
 * Generates a ticket list
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
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
	$condition = ($status == 'open') ? "`ticket_status` != 0" : '`ticket_status` = 0';

	$countQuery = "
		SELECT
			COUNT(`ticket_id`) AS `cnt`
		FROM
			`tickets`
		WHERE
			(`ticket_from` = ? OR `ticket_to` = ?)
		AND
			`ticket_reply` = '0'
		AND
			{$condition}
	";

	$stmt = exec_query($countQuery, array($userId, $userId));
	$recordsCount = $stmt->fields['cnt'];

	if ($recordsCount != 0) {
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
                {$condition}
            ORDER BY
                `ticket_date` DESC LIMIT {$start}, {$count}
        ";
		$stmt = exec_query($query, array($userId, $userId));

		$prevSi = $start - $count;

		if ($start == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_PREV_GRAY' => '',
					'PREV_PSI' => $prevSi
				)
			);
		}

		$nextSi = $start + $count;

		if ($nextSi + 1 > $recordsCount) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_NEXT_GRAY' => '',
					'NEXT_PSI' => $nextSi
				)
			);
		}

		while (!$stmt->EOF) {
			$ticketStatus = $stmt->fields['ticket_status'];
			$ticketLevel = $stmt->fields['ticket_level'];

			if ($ticketStatus == 1) {
				$tpl->assign('TICKET_STATUS_VAL', tr('[New]'));
			} elseif (
				$ticketStatus == 2 &&
				(
					($ticketLevel == 1 && $userLevel == 'client') ||
					($ticketLevel == 2 && $userLevel == 'reseller')
				)
			) {
				$tpl->assign('TICKET_STATUS_VAL', tr('[Re]'));
			} elseif (
				$ticketStatus == 4 &&
				(
					($ticketLevel == 1 && $userLevel == 'reseller') || ($ticketLevel == 2 && $userLevel == 'admin')
				)
			) {
				$tpl->assign('TICKET_STATUS_VAL', tr('[Re]'));
			} else {
				$tpl->assign('TICKET_STATUS_VAL', '[Read]');
			}

			$tpl->assign(
				array(
					'TICKET_URGENCY_VAL' => getTicketUrgency($stmt->fields['ticket_urgency']),
					'TICKET_FROM_VAL' => tohtml(_getTicketSender($stmt->fields['ticket_id'])),
					'TICKET_LAST_DATE_VAL' => _ticketGetLastDate($stmt->fields['ticket_id']),
					'TICKET_SUBJECT_VAL' => tohtml($stmt->fields['ticket_subject']),
					'TICKET_SUBJECT2_VAL' => addslashes(clean_html($stmt->fields['ticket_subject'])),
					'TICKET_ID_VAL' => $stmt->fields['ticket_id']
				)
			);

			$tpl->parse('TICKETS_ITEM', '.tickets_item');
			$stmt->moveNext();
		}
	} else { // no ticket to display
		$tpl->assign(
			array(
				'TICKETS_LIST' => '',
				'SCROLL_PREV' => '',
				'SCROLL_NEXT' => ''
			)
		);

		if ($status == 'open') {
			set_page_message(tr('You have no open tickets.'), 'info');
		} else {
			set_page_message(tr('You have no closed tickets.'), 'info');
		}
	}
}

/**
 * Closes the given ticket.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
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
 * Reopens the given ticket.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
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
 * Returns ticket status.
 *
 * Possible status values are:
 *  0 - closed
 *  1 - new
 *  2 - answered by reseller
 *  3 - read (if status was 2 or 4)
 *  4 - answered by client
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param int $ticketId Ticket unique identifier
 * @return int ticket status identifier
 */
function getTicketStatus($ticketId)
{
	$userId = $_SESSION['user_id'];

	$query = 'SELECT `ticket_status` FROM `tickets` WHERE `ticket_id` = ? AND (`ticket_from` = ? OR `ticket_to` = ?)';
	$stmt = exec_query($query, array($ticketId, $userId, $userId));

	return $stmt->fields['ticket_status'];
}

/**
 * Changes ticket status.
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
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param int $ticketId Ticket unique identifier
 * @param int $ticketStatus New status identifier
 * @return bool TRUE if ticket status was changed, FALSE otherwise (eg. if ticket was
 *              not found)
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
	$stmt = exec_query($query, array($ticketStatus, $ticketId, $ticketId, $userId, $userId));

	if (!$stmt->rowCount()) {
		return false;
	}

	return true;
}

/**
 * Reads the user's level from ticket info.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param int $ticketId Ticket id
 * @return int User's level (1 = user, 2 = super) or FALSE if ticket is not found
 */
function getUserLevel($ticketId)
{
	// Get info about the type of message
	$stmt = exec_query('SELECT `ticket_level` FROM `tickets` WHERE `ticket_id` = ?', $ticketId);

	if (!$stmt->rowCount()) {
		set_page_message(tr("Ticket with Id '%d' was not found.", $ticketId), 'error');
		return false;
	}

	return $stmt->fields['ticket_level'];
}

/**
 * Returns translated ticket priority.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
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
 * Returns ticket'ssender of a ticket answer.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @usedby showTicketContent
 * @usedby generateTicketList
 * @usedby _showTicketReplies()
 * @param int $ticketId Id of the ticket to display
 * @return mixed Formatted ticket sender or FALSE if ticket is not found.
 */
function _getTicketSender($ticketId)
{
	$ticketId = (int)$ticketId;

	$query = "
		SELECT
			`a`.`admin_name`, `a`.`fname`, `a`.`lname`, `a`.`admin_type`
		FROM
			`tickets` `t`
		LEFT JOIN
			`admin` `a` ON (`t`.`ticket_from` = `a`.`admin_id`)
		WHERE
			`ticket_id` = ?
    ";
	$stmt = exec_query($query, $ticketId);

	if (!$stmt->rowCount()) {
		set_page_message(tr("Ticket with Id '%d' was not found.", $ticketId), 'error');
		return false;
	}

	if ($stmt->fields['admin_type'] == 'user') {
		$fromUsername = decode_idna($stmt->fields['admin_name']);
	} else {
		$fromUsername = $stmt->fields['admin_name'];
	}

	$fromFirstname = $stmt->fields['fname'];
	$fromLastname = $stmt->fields['lname'];

	return $fromFirstname . ' ' . $fromLastname . ' (' . $fromUsername . ')';
}

/**
 * Returns the last modification date of a ticket.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @usedby generateTicketList
 * @param int $ticketId Ticket to get last date for
 * @return string Last modification date of a ticket
 */
function _ticketGetLastDate($ticketId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT `ticket_date` FROM `tickets` WHERE `ticket_reply` = ? ORDER BY `ticket_date` DESC";
	$stmt = exec_query($query, $ticketId);

	if (!$stmt->rowCount()) {
		return tr('Never');
	}

	return date($cfg->DATE_FORMAT, $stmt->fields['ticket_date']);
}

/**
 * Checks if the support ticket system is globally enabled and (optionaly) if a
 * specific reseller has permissions to access to it.
 *
 * Note: If a reseller has not access to the support ticket system, it's means that
 * all his customers have not access to it too.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param int $userId OPTIONAL Id of the user created the current user or null if admin
 * @return bool TRUE if support ticket system is available, FALSE otherwise
 * @Todo: Allows to provides support ticket system as hosting plan option for clients
 */
function hasTicketSystem($userId = null)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (!$cfg->IMSCP_SUPPORT_SYSTEM) {
		return false;
	} elseif ($userId !== null) {
		$stmt = exec_query('SELECT `support_system` FROM `reseller_props` WHERE `reseller_id` = ?', $userId);

		if (!$stmt->rowCount() || $stmt->fields['support_system'] == 'no') {
			return false;
		}
	}

	return true;
}

/**
 * Gets the answers of the selected ticket and generates its output.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @usedby showTicketContent()
 * @param iMSCP_pTemplate $tpl The Template object
 * @param int $ticketId Id of the ticket to display
 */
function _showTicketReplies($tpl, $ticketId)
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

	if ($stmt->rowCount()) {
		while (!$stmt->EOF) {
			$ticketId = $stmt->fields['ticket_id'];
			$ticketDate = $stmt->fields['ticket_date'];

			$tpl->assign(
				array(
					'TICKET_FROM_VAL' => _getTicketSender($ticketId),
					'TICKET_DATE_VAL' => date($cfg->DATE_FORMAT, $ticketDate),
					'TICKET_CONTENT_VAL' => nl2br(tohtml($stmt->fields['ticket_message']))
				)
			);

			$tpl->parse('TICKETS_ITEM', '.tickets_item');
			$stmt->moveNext();
		}
	}
}

/**
 * Informs a user about a ticket creation/update and writes a line to the log.
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @usedby updateTicket()
 * @usedby createTicket()
 * @param int $toId ticket recipient
 * @param int $fromId ticket sender
 * @param string $ticketSubject ticket subject
 * @param string $ticketMessage ticket content / message
 * @param int $ticketStatus ticket status
 * @param int $urgency ticket urgency
 */
function _sendTicketNotification($toId, $fromId, $ticketSubject, $ticketMessage, $ticketStatus, $urgency)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// To information
	$stmt = exec_query('SELECT `fname`, `lname`, `email`, `admin_name` FROM `admin` WHERE `admin_id` = ?', $toId);

	$toEmail = $stmt->fields['email'];
	$toFname = $stmt->fields['fname'];
	$toLname = $stmt->fields['lname'];
	$toUname = $stmt->fields['admin_name'];

	// From information
	$stmt = exec_query('SELECT `fname`, `lname`, `email`, `admin_name` FROM `admin` WHERE `admin_id` = ?', $fromId);

	$fromEmail = $stmt->fields['email'];
	$fromFname = $stmt->fields['fname'];
	$fromLname = $stmt->fields['lname'];
	$fromUname = $stmt->fields['admin_name'];

	// Prepare message
	$subject = tr('[Ticket]') . ' {SUBJ}';

	if ($ticketStatus == 0) {
		$message = tr("Dear %s,\n\nYou have a new ticket:\n", '{TO_NAME}');
	} else {
		$message = tr("Dear %s,\n\nYou have an answer for this ticket:\n", '{TO_NAME}');
	}

	$message .= "\n" . tr("Priority: %s\n", '{PRIORITY}');
	$message .= "\n" . $ticketMessage;
	$message .= "\n\n" . tr('Log in to answer') . ' ' . $cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST;

	// Format addresses
	if ($fromFname && $fromLname) {
		$from = encode_mime_header($fromFname . ' ' . $fromLname) . " <$fromEmail>";
		$fromname = "$fromFname $fromLname";
	} else {
		$from = $fromEmail;
		$fromname = $fromUname;
	}

	if ($toFname && $toLname) {
		$to = encode_mime_header($toFname . ' ' . $toLname) . " <$toEmail>";
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

	$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

	$headers = "From: $from\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
	$headers .= "Content-Transfer-Encoding: 8bit\r\n";
	$headers .= 'X-Mailer: i-MSCP Mailer';

	$mail_result = mail($to, encode_mime_header($subject), $message, $headers);
	$mail_status = ($mail_result) ? 'OK' : 'NOT OK';

	$toname = tohtml($toname);
	$fromname = tohtml($fromname);

	write_log(
		sprintf(
			'%s send ticket To: %s, From: %s, Status: %s.',
			$_SESSION['user_logged'],
			$toname . ': ' . $toEmail, $fromname . ': ' . $fromEmail,
			$mail_status
		),
		E_USER_NOTICE
	);
}
