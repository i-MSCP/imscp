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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2015 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';
require_once 'tickets-functions.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

// Checks if support ticket system is activated
if (!hasTicketSystem()) {
	redirectTo('index.php');
}

if (isset($_GET['ticket_id']) && !empty($_GET['ticket_id'])) {
	$userId = $_SESSION['user_id'];
	$ticketId = (int)$_GET['ticket_id'];

	$status = getTicketStatus($ticketId);

	if ($status == 1 || $status == 4) {
		if (!changeTicketStatus($ticketId, 3)) {
			redirectTo('ticket_system.php');
		}
	}

	if (isset($_POST['uaction'])) {
		if ($_POST['uaction'] == 'close') {
			closeTicket($ticketId);
		} elseif (isset($_POST['user_message'])) {
			if (empty($_POST['user_message'])) {
				set_page_message(tr('Please type your message.'), 'error');
			} else {
				updateTicket($ticketId, $userId, $_POST['urgency'], $_POST['subject'], $_POST['user_message'], 2, 3);
			}
		}

		redirectTo('ticket_system.php');
	}
} else {
	set_page_message(tr('Ticket not found.'), 'error');
	redirectTo('ticket_system.php');
	exit;
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/ticket_view.tpl',
		'page_message' => 'layout',
		'tickets_list' => 'page',
		'tickets_item' => 'tickets_list'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Support / View Ticket'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_VIEW_SUPPORT_TICKET' => tr('View Support Ticket'),
		'TR_TICKET_INFO' => tr('Ticket Information'),
		'TR_TICKET_URGENCY' => tr('Priority'),
		'TR_TICKET_SUBJECT' => tr('Subject'),
		'TR_TICKET_MESSAGES' => tr('Messages'),
		'TR_TICKET_FROM' => tr('From'),
		'TR_TICKET_DATE' => tr('Date'),
		'TR_TICKET_CONTENT' => tr('Message'),
		'TR_REPLY' => tr('Reply'),
		'TR_TICKET_NEW_REPLY' => tr('Send new reply'),
		'TR_TICKET_REPLY' => tr('Send reply')
	)
);

generateNavigation($tpl);
showTicketContent($tpl, $ticketId, $userId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
unsetMessages();
