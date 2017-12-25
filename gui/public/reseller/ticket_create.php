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

if (isset($_POST['uaction'])) {
    if (empty($_POST['subject'])) {
        set_page_message(tr('You must specify a subject.'), 'error');
    } elseif (empty($_POST['user_message'])) {
        set_page_message(tr('You must specify a message.'), 'error');
    } else {
        createTicket($_SESSION['user_id'], $_SESSION['user_created_by'],
            $_POST['urgency'], $_POST['subject'], $_POST['user_message'], 2);
        redirectTo('ticket_system.php');
    }
}

$userdata = [
    'OPT_URGENCY_1' => '',
    'OPT_URGENCY_2' => '',
    'OPT_URGENCY_3' => '',
    'OPT_URGENCY_4' => ''];

if (isset($_POST['urgency'])) {
    $userdata['URGENCY'] = intval($_POST['urgency']);
} else {
    $userdata['URGENCY'] = 2;
}

switch ($userdata['URGENCY']) {
    case 1:
        $userdata['OPT_URGENCY_1'] = ' selected';
        break;
    case 3:
        $userdata['OPT_URGENCY_3'] = ' selected';
        break;
    case 4:
        $userdata['OPT_URGENCY_4'] = ' selected';
        break;
    default:
        $userdata['OPT_URGENCY_2'] = ' selected';
}

$userdata['SUBJECT'] = isset($_POST['subject']) ? clean_input($_POST['subject']) : '';
$userdata['USER_MESSAGE'] = isset($_POST['user_message']) ? clean_input($_POST['user_message']) : '';

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'reseller/ticket_create.tpl',
    'page_message' => 'layout'
]);
$tpl->assign(
    [
        'TR_PAGE_TITLE'     => tr('Reseller / Support / New Ticket'),
        'TR_NEW_TICKET'     => tr('New ticket'),
        'TR_LOW'            => tr('Low'),
        'TR_MEDIUM'         => tr('Medium'),
        'TR_HIGH'           => tr('High'),
        'TR_VERY_HIGH'      => tr('Very high'),
        'TR_URGENCY'        => tr('Priority'),
        'TR_EMAIL'          => tr('Email'),
        'TR_SUBJECT'        => tr('Subject'),
        'TR_YOUR_MESSAGE'   => tr('Message'),
        'TR_CREATE'         => tr('Create'),
        'TR_OPEN_TICKETS'   => tr('Open tickets'),
        'TR_CLOSED_TICKETS' => tr('Closed tickets')]);

$tpl->assign($userdata);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
