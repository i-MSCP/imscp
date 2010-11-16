<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

require '../include/imscp-lib.php';

check_login(__FILE__);

$cfg = iMSCP_Registry::get('Config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/ticket_create.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

// common page data

$tpl->assign(
	array(
		'TR_CLIENT_NEW_TICKET_PAGE_TITLE' => tr('i-MSCP - Support System - New ticket'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

// dynamic page data

$reseller_id = $_SESSION['user_created_by'];

if (!hasTicketSystem($reseller_id)) {
	user_goto('index.php');
}

if (isset($_POST['uaction'])) {
	if (empty($_POST['subj'])) {
		set_page_message(tr('Please specify message subject!'));
	} else if (empty($_POST['user_message'])) {
		set_page_message(tr('Please type your message!'));
	} else {
		createTicket($_SESSION['user_id'], $_SESSION['user_created_by'],
				$_POST['urgency'], $_POST['subj'], $_POST['user_message'], 1);
		user_goto('ticket_system.php');
	}
}

// static page messages

gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_ticket_system.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_ticket_system.tpl');

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
		'TR_CLOSED_TICKETS' => tr('Closed tickets')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
