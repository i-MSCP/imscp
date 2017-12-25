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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2018 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';
require_once LIBRARY_PATH . '/Functions/Tickets.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('support') or showBadRequestErrorPage();

$userId = $_SESSION['user_id'];
$previousPage = 'ticket_system';

if (isset($_GET['ticket_id'])) {
    $ticketId = intval($_GET['ticket_id']);
    $stmt = exec_query(
        'SELECT ticket_status FROM tickets WHERE ticket_id = ? AND (ticket_from = ? OR ticket_to = ?)',
        [$ticketId, $userId, $userId]
    );

    if ($stmt->rowCount() == 0) {
        set_page_message(tr("Ticket with Id '%d' was not found.", $ticketId), 'error');
        redirectTo($previousPage . '.php');
    }

    // The ticket status was 0 so we come from ticket_closed.php
    if ($stmt->fetchColumn() == 0) {
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
