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

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('RESELLER_TEMPLATE_PATH') . '/order_email.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$user_id = $_SESSION['user_id'];

$data = get_order_email($user_id);

if (isset($_POST['uaction']) && $_POST['uaction'] == 'order_email') {
	$data['subject'] = clean_input($_POST['auto_subject']);

	$data['message'] = clean_input($_POST['auto_message']);

	if ($data['subject'] == '') {
		set_page_message(tr('Please specify a subject!'));
	} else if ($data['message'] == '') {
		set_page_message(tr('Please specify message!'));
	} else {
		set_order_email($user_id, $data);

		set_page_message (tr('Auto email template data updated!'));
	}
}

/*
 *
 * static page messages.
 *
 */

$tpl->assign(
	array(
		'TR_RESELLER_ORDER_EMAL' => tr('ispCP - Reseller/Order email setup'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

gen_reseller_mainmenu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/main_menu_orders.tpl');
gen_reseller_menu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/menu_orders.tpl');

gen_logged_from($tpl);

$tpl->assign(
	array(
		'TR_EMAIL_SETUP' => tr('Email setup'),
		'TR_MANAGE_ORDERS' => tr('Manage orders'),
		'TR_MESSAGE_TEMPLATE_INFO' => tr('Message template info'),
		'TR_USER_LOGIN_NAME' => tr('User login (system) name'),
		'TR_USER_DOMAIN' => tr('Domain name'),
		'TR_USER_REAL_NAME' => tr('User (first and last) name'),
		'TR_ACTIVATION_LINK' => tr('Activation Link'),
		'TR_MESSAGE_TEMPLATE' => tr('Message template'),
		'TR_SUBJECT' => tr('Subject'),
		'TR_MESSAGE' => tr('Message'),
		'TR_SENDER_EMAIL' => tr('Senders email'),
		'TR_SENDER_NAME' => tr('Senders name'),
		'TR_APPLY_CHANGES' => tr('Apply changes'),
		'SUBJECT_VALUE' => $data['subject'],
		'MESSAGE_VALUE' => $data['message'],
		'SENDER_EMAIL_VALUE' => $data['sender_email'],
		'SENDER_NAME_VALUE' => $data['sender_name']
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
