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

require '../include/ispcp-lib.php';

check_login(__FILE__);

$cfg = ispCP_Registry::get('Config');

$tpl = new ispCP_pTemplate();
$tpl->define_dynamic('page', $cfg->RESELLER_TEMPLATE_PATH . '/ticket_create.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

// page functions

/**
 * Checks if the client's reseller has a support system.
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $sql	the SQL object
 * @param int $reseller_id	the ID of the client's reseller
 * @return boolean
 */
function hasTicketSystem(&$sql, $admin_id) {
	$cfg = ispCP_Registry::get('Config');

	$query = "
	  SELECT
		`support_system`
	  FROM
		`reseller_props`
	  WHERE
		`reseller_id` = ?
	;";

	$rs = exec_query($sql, $query, $admin_id);

	if (!$cfg->ISPCP_SUPPORT_SYSTEM || $rs->fields['support_system'] == 'no')
		return false;

	return true;
}

/**
 * Creates the ticket and informs the recipient.
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $sql    the SQL object
 * @param int $user_id	    the ID of the reseller
 * @param int $admin_id 	the ID of the reseller's admin
 */
function createTicket(&$sql, $user_id, $admin_id) {
	if (empty($_POST['subj'])) {
		set_page_message(tr('Please specify message subject!'));
		return;
	}

	if (empty($_POST['user_message'])) {
		set_page_message(tr('Please type your message!'));
		return;
	}

	$ticket_date = time();
	$urgency = $_POST['urgency'];
	$subject = clean_input($_POST['subj']);
	$user_message = clean_input($_POST["user_message"]);
	$ticket_status = 1;
	$ticket_reply = 0;
	$ticket_level = 1;

	$query = "
		INSERT INTO `tickets`
			(`ticket_level`, `ticket_from`,	`ticket_to`,
			 `ticket_status`, `ticket_reply`, `ticket_urgency`,
			 `ticket_date`, `ticket_subject`, `ticket_message`)
		VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?)
	;";

	exec_query($sql, $query,
		array(
			$ticket_level, $user_id, $admin_id, $ticket_status,
			$ticket_reply, $urgency, $ticket_date, $subject, $user_message
		)
	);

	set_page_message(tr('Your message has been sent!'));
	send_tickets_msg($admin_id, $user_id, $subject, $user_message,
        $ticket_reply, $urgency);
	user_goto('ticket_system.php');
}

// common page data.

$tpl->assign(
	array(
		'TR_CLIENT_NEW_TICKET_PAGE_TITLE' => tr('ispCP - Support system - New ticket'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id']),
	)
);

// dynamic page data

$admin_id = $_SESSION['user_created_by'];

if (!hasTicketSystem($sql, $admin_id)) {
	user_goto('index.php');
}

if (isset($_POST['uaction'])) {
	createTicket($sql, $_SESSION['user_id'], $_SESSION['user_created_by']);
}

// static page messages.

gen_reseller_mainmenu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/main_menu_ticket_system.tpl');
gen_reseller_menu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/menu_ticket_system.tpl');

gen_logged_from($tpl);

$userdata = array(
	'OPT_URGENCY_1' => '',
	'OPT_URGENCY_2' => '',
	'OPT_URGENCY_3' => '',
	'OPT_URGENCY_4' => ''
);

if (isset($_POST['urgency'])) {
	$userdata['URGENCY'] = intval($_POST['urgency']);
} else {
	$userdata['URGENCY'] = 2;
}

switch ($userdata['URGENCY']) {
	case 1:
		$userdata['OPT_URGENCY_1'] = $cfg->HTML_SELECTED;
		break;
	case 3:
		$userdata['OPT_URGENCY_3'] = $cfg->HTML_SELECTED;
		break;
	case 4:
		$userdata['OPT_URGENCY_4'] = $cfg->HTML_SELECTED;
		break;
	default:
		$userdata['OPT_URGENCY_2'] = $cfg->HTML_SELECTED;
}

$userdata['SUBJECT'] = isset($_POST['subj']) ? clean_input($_POST['subj'], true) : '';
$userdata['USER_MESSAGE'] = isset($_POST['user_message']) ?
    clean_input($_POST['user_message'], true) : '';
$tpl->assign($userdata);

$tpl->assign(
	array(
		'TR_NEW_TICKET' => tr('New ticket'),
		'TR_LOW' => tr('Low'),
		'TR_MEDIUM' => tr('Medium'),
		'TR_HIGH' => tr('High'),
		'TR_VERI_HIGH' => tr('Very high'),
		'TR_URGENCY' => tr('Priority'),
		'TR_EMAIL' => tr('Email'),
		'TR_SUBJECT' => tr('Subject'),
		'TR_YOUR_MESSAGE' => tr('Your message'),
		'TR_SEND_MESSAGE' => tr('Send message'),
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
