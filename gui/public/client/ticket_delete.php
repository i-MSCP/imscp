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
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';
require_once 'tickets-functions.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('support') or showBadRequestErrorPage();

$userId = $_SESSION['user_id'];
$previousPage = 'ticket_system';

if (isset($_GET['ticket_id']) && !empty($_GET['ticket_id'])) {
	$ticketId = (int) $_GET['ticket_id'];

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

	if ($stmt->rowCount() == 0) {
        set_page_message(tr("Ticket with Id '%d' was not found.", $ticketId), 'error');
		redirectTo($previousPage . '.php');
	}

    // The ticket status was 0 so we come from ticket_closed.php
    if($stmt->fields['ticket_status'] == 0 ) {
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
