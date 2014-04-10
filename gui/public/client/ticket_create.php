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

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

customerHasFeature('support') or showBadRequestErrorPage();

if (isset($_POST['uaction'])) {
	if (empty($_POST['subject'])) {
		set_page_message(tr('Please specify a message subject.'), 'error');
	} elseif (empty($_POST['user_message'])) {
		set_page_message(tr('Please type your message.'), 'error');
	} else {
		createTicket($_SESSION['user_id'], $_SESSION['user_created_by'],
				$_POST['urgency'], $_POST['subject'], $_POST['user_message'], 1);
		redirectTo('ticket_system.php');
	}
}

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

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic(
	array(
		 'page' => 'client/ticket_create.tpl',
		 'page_message' => 'layout'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Client / Support / New Ticket'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_NEW_TICKET' => tr('New ticket'),
		 'TR_LOW' => tr('Low'),
		 'TR_MEDIUM' => tr('Medium'),
		 'TR_HIGH' => tr('High'),
		 'TR_VERY_HIGH' => tr('Very high'),
		 'TR_URGENCY' => tr('Priority'),
		 'TR_EMAIL' => tr('Email'),
		 'TR_SUBJECT' => tr('Subject'),
		 'TR_YOUR_MESSAGE' => tr('Your message'),
		 'TR_SEND_MESSAGE' => tr('Send message'),
		 'TR_OPEN_TICKETS' => tr('Open tickets'),
		 'TR_CLOSED_TICKETS' => tr('Closed tickets')));

$tpl->assign($userdata);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
