<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-msCP | http://i-mscp.net
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

require 'include/imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->RESELLER_TEMPLATE_PATH . '/ticket_system.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('tickets_list', 'page');
$tpl->define_dynamic('tickets_item', 'tickets_list');
$tpl->define_dynamic('scroll_prev_gray', 'page');
$tpl->define_dynamic('scroll_prev', 'page');
$tpl->define_dynamic('scroll_next_gray', 'page');
$tpl->define_dynamic('scroll_next', 'page');

// common page data

$tpl->assign(
	array(
		'TR_CLIENT_QUESTION_PAGE_TITLE' => tr('i-MSCP - Client/Questions & Comments'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id']),
	)
);

// dynamic page data

$admin_id = $_SESSION['user_created_by'];

if (!hasTicketSystem($admin_id)) {
	user_goto('index.php');
}
if (isset($_GET['psi'])) {
	$start = $_GET['psi'];
} else {
	$start = 0;
}

generateTicketList($tpl, $_SESSION['user_id'], $start,
		$cfg->DOMAIN_ROWS_PER_PAGE, 'reseller', 'open');

// static page messages

gen_reseller_mainmenu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/main_menu_ticket_system.tpl');
gen_reseller_menu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/menu_ticket_system.tpl');

gen_logged_from($tpl);

$tpl->assign(array(
                  'TR_SUPPORT_SYSTEM' => tr('Support system'),
                  'TR_SUPPORT_TICKETS' => tr('Support tickets'),
                  'TR_TICKET_FROM' => tr('From'),
                  'TR_STATUS' => tr('Status'),
                  'TR_NEW' => ' ',
                  'TR_ACTION' => tr('Action'),
                  'TR_URGENCY' => tr('Priority'),
                  'TR_SUBJECT' => tr('Subject'),
                  'TR_LAST_DATA' => tr('Last reply'),
                  'TR_DELETE_ALL' => tr('Delete all'),
                  'TR_OPEN_TICKETS' => tr('Open tickets'),
                  'TR_CLOSED_TICKETS' => tr('Closed tickets'),
                  'TR_DELETE' => tr('Delete'),
                  'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
                  'TR_PREVIOUS' => tr('Previous'),
                  'TR_NEXT' => tr('Next')));

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onResellerScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
