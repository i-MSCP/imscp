<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;

require_once 'imscp-lib.php';
require_once GUI_ROOT_DIR . '/include/Tickets.php';

check_login('user');
EventAggregator::getInstance()->dispatch(Events::onClientScriptStart);
customerHasFeature('support') or showBadRequestErrorPage();

$userId = $_SESSION['user_id'];
$previousPage = 'ticket_system';

if (isset($_GET['ticket_id']) && !empty($_GET['ticket_id'])) {
    $ticketId = intval($_GET['ticket_id']);

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
    $stmt = exec_query($query, [$ticketId, $userId, $userId]);

    if ($stmt->rowCount() == 0) {
        set_page_message(tr("Ticket with Id '%d' was not found.", $ticketId), 'error');
        redirectTo($previousPage . '.php');
    }

    // The ticket status was 0 so we come from ticket_closed.php
    if ($stmt->fields['ticket_status'] == 0) {
        $previousPage = 'ticket_closed';
    }

    deleteTicket($ticketId);
    set_page_message(tr('Ticket successfully deleted.'), 'success');
    write_log(sprintf("%s: deleted ticket %d", $_SESSION['user_logged'], $ticketId), E_USER_NOTICE);
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'open') {
    deleteTickets('open', $userId);
    set_page_message(tr('All open tickets were successfully deleted.'), 'success');
    write_log(sprintf("%s: deleted all open tickets.", $_SESSION['user_logged']), E_USER_NOTICE);
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'closed') {
    deleteTickets('closed', $userId);
    set_page_message(tr('All closed tickets were successfully deleted.'), 'success');
    write_log(sprintf("%s: deleted all closed tickets.", $_SESSION['user_logged']), E_USER_NOTICE);
    $previousPage = 'ticket_closed';
} else {
    set_page_message(tr('Unknown action requested.'), 'error');
}

redirectTo($previousPage . '.php');
