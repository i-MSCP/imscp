<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/i-mscp-lib.php';

check_login(__FILE__);

$cfg = ispCP_Registry::get('Config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/ticket_view.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('tickets_list', 'page');
$tpl->define_dynamic('tickets_item', 'tickets_list');

// common page data

$tpl->assign(
	array(
		'TR_CLIENT_VIEW_TICKET_PAGE_TITLE' => tr('ispCP - Client: Support System: View Ticket'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

// dynamic page data

if (!hasTicketSystem()) {
	user_goto('index.php');
}

if (isset($_GET['ticket_id'])) {
	$user_id = $_SESSION['user_id'];
	$ticket_id = $_GET['ticket_id'];
	$screenwidth = 1024;

	if (isset($_GET['screenwidth'])) {
		$screenwidth = $_GET['screenwidth'];
	} else if(isset($_POST['screenwidth'])) {
		$screenwidth = $_POST['screenwidth'];
	}

	if ($screenwidth < 639) {
		$screenwidth = 1024;
	}
	$tpl->assign('SCREENWIDTH', $screenwidth);

	// if status "new" or "Answer by client" set to "read"
	$status = getTicketStatus($ticket_id);
	if ($status == 1 || $status == 4) {
		changeTicketStatus($ticket_id, 3);
	}

	if (isset($_POST['uaction'])) {
		if ($_POST['uaction'] == "close") {
			// close ticket
			closeTicket($ticket_id);
		} elseif ($_POST['uaction'] == "open") {
			// open ticket
			openTicket($ticket_id);
		} elseif (empty($_POST['user_message'])) {
			// no message check->error
			set_page_message(tr('Please type your message!'));
		} else {
			updateTicket($ticket_id, $user_id, $_POST['urgency'],
					$_POST['subject'], $_POST['user_message'], 2, 3);
			user_goto('ticket_system.php');
		}
	}

	showTicketContent($tpl, $ticket_id, $user_id, $screenwidth);
} else {
	set_page_message(tr('Ticket not found!'));

	user_goto('ticket_system.php');
}

// static page messages

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_ticket_system.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_ticket_system.tpl');

$tpl->assign(
	array(
		'TR_SUPPORT_SYSTEM' => tr('ispCP - Admin: Support System: View Ticket'),
		'TR_VIEW_SUPPORT_TICKET' => tr('View support ticket'),
		'TR_TICKET_URGENCY' => tr('Priority'),
		'TR_TICKET_SUBJECT' => tr('Subject'),
		'TR_TICKET_DATE' => tr('Date'),
		'TR_DELETE' => tr('Delete'),
		'TR_NEW_TICKET_REPLY' => tr('Send message reply'),
		'TR_REPLY' => tr('Send reply'),
		'TR_TICKET_FROM' => tr('From'),
		'TR_OPEN_TICKETS' => tr('Open tickets'),
		'TR_CLOSED_TICKETS' => tr('Closed tickets'),
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();