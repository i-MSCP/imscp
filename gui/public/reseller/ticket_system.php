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

check_login('reseller');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptStart);
resellerHasFeature('support') or showBadRequestErrorPage();

$_SESSION['previousPage'] = 'ticket_system.php';

if (isset($_GET['ticket_id'])) {
    closeTicket(intval($_GET['ticket_id']));
}

if (isset($_GET['psi'])) {
    $start = $_GET['psi'];
} else {
    $start = 0;
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'           => 'shared/layouts/ui.tpl',
    'page'             => 'reseller/ticket_system.tpl',
    'page_message'     => 'layout',
    'tickets_list'     => 'page',
    'tickets_item'     => 'tickets_list',
    'scroll_prev_gray' => 'page',
    'scroll_prev'      => 'page',
    'scroll_next_gray' => 'page',
    'scroll_next'      => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'                 => tr('Reseller / Support / Open Tickets'),
    'TR_TICKET_STATUS'              => tr('Status'),
    'TR_TICKET_FROM'                => tr('From'),
    'TR_TICKET_SUBJECT'             => tr('Subject'),
    'TR_TICKET_URGENCY'             => tr('Priority'),
    'TR_TICKET_LAST_ANSWER_DATE'    => tr('Last reply date'),
    'TR_TICKET_ACTIONS'             => tr('Actions'),
    'TR_TICKET_DELETE'              => tr('Delete'),
    'TR_TICKET_CLOSE'               => tr('Close'),
    'TR_TICKET_READ_LINK'           => tr('Read ticket'),
    'TR_TICKET_DELETE_LINK'         => tr('Delete ticket'),
    'TR_TICKET_CLOSE_LINK'          => tr('Close ticket'),
    'TR_TICKET_DELETE_ALL'          => tr('Delete all tickets'),
    'TR_TICKETS_DELETE_MESSAGE'     => tr("Are you sure you want to delete the '%s' ticket?", '%s'),
    'TR_TICKETS_DELETE_ALL_MESSAGE' => tr('Are you sure you want to delete all tickets?'),
    'TR_PREVIOUS'                   => tr('Previous'),
    'TR_NEXT'                       => tr('Next')
]);

generateNavigation($tpl);
generateTicketList(
    $tpl, $_SESSION['user_id'], $start, Registry::get('config')['DOMAIN_ROWS_PER_PAGE'], 'reseller', 'open'
);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
