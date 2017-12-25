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
use iMSCP\TemplateEngine;

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';
require_once LIBRARY_PATH . '/Functions/Tickets.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('support') && isset($_GET['ticket_id']) or showBadRequestErrorPage();

$ticketId = intval($_GET['ticket_id']);
$status = getTicketStatus($ticketId);
$ticketLevel = getUserLevel($ticketId);

if (getTicketStatus($ticketId) == 2) {
    changeTicketStatus($ticketId, 3);
}

if (isset($_POST['uaction'])) {
    if ($_POST['uaction'] == 'close') {
        closeTicket($ticketId);
        redirectTo('ticket_system.php');
    }

    if (isset($_POST['user_message'])) {
        if (empty($_POST['user_message'])) {
            set_page_message(tr('Please type your message.'), 'error');
        } else {
            updateTicket($ticketId, $_SESSION['user_id'], $_POST['urgency'], $_POST['subject'], $_POST['user_message'], 1, 1);
            redirectTo("ticket_view.php?ticket_id=$ticketId");
        }
    }
}

$tpl = new TemplateEngine();
$tpl->define('layout', 'shared/layouts/ui.tpl');
$tpl->define([
    'page'           => 'client/ticket_view.tpl',
    'page_message'   => 'layout',
    'ticket'         => 'page',
    'ticket_message' => 'ticket'
]);
$tpl->assign([
    'TR_PAGE_TITLE'       => tr('Client / Support / View Ticket'),
    'TR_TICKET_INFO'      => tr('Ticket information'),
    'TR_TICKET_URGENCY'   => tr('Priority'),
    'TR_TICKET_SUBJECT'   => tr('Subject'),
    'TR_TICKET_FROM'      => tr('From'),
    'TR_TICKET_DATE'      => tr('Date'),
    'TR_TICKET_CONTENT'   => tr('Message'),
    'TR_TICKET_NEW_REPLY' => tr('Reply'),
    'TR_TICKET_REPLY'     => tr('Send reply')
]);

generateNavigation($tpl);
showTicketContent($tpl, $ticketId, $_SESSION['user_id']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
