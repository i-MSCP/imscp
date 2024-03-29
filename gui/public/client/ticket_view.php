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
use iMSCP\TemplateEngine;

require_once 'imscp-lib.php';
require_once GUI_ROOT_DIR . '/include/Tickets.php';

check_login('user');
EventAggregator::getInstance()->dispatch(Events::onClientScriptStart);

if (!customerHasFeature('support') || !isset($_GET['ticket_id'])) {
    showBadRequestErrorPage();
}

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
$tpl->define_dynamic([
    'layout'         => 'shared/layouts/ui.tpl',
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
EventAggregator::getInstance()->dispatch(
    Events::onClientScriptEnd, ['templateEngine' => $tpl]
);
$tpl->prnt();

unsetMessages();
