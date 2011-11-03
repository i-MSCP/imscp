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

require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->RESELLER_TEMPLATE_PATH . '/settings_lostpassword.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('custom_buttons', 'page');

$user_id = $_SESSION['user_id'];

$selected_on = '';
$selected_off = '';

$data_1 = get_lostpassword_activation_email($user_id);
$data_2 = get_lostpassword_password_email($user_id);

if (isset($_POST['uaction']) && $_POST['uaction'] == 'apply') {

	$err_message = '';

	$data_1['subject'] = clean_input($_POST['subject1'], false);
	$data_1['message'] = clean_input($_POST['message1'], false);
	$data_2['subject'] = clean_input($_POST['subject2'], false);
	$data_2['message'] = clean_input($_POST['message2'], false);

	if (empty($data_1['subject']) || empty($data_2['subject'])) {
		set_page_message(tr('You must specify a subject.'), 'error');
	}

	if (empty($data_1['message']) || empty($data_2['message'])) {
		set_page_message(tr('You must specify a message.'), 'error');
	}

	if (Zend_Session::namespaceIsset('pageMessages')) {
		return false;
	} else {
		set_lostpassword_activation_email($user_id, $data_1);
		set_lostpassword_password_email($user_id, $data_2);
		set_page_message(tr('Template for Auto email successfully updated.'), 'success');
	}
}

/*
 *
 * static page messages.
 *
 */

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Reseller/Lostpw email setup'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()
	)
);

gen_reseller_mainmenu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/main_menu_users_manage.tpl');
gen_reseller_menu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/menu_users_manage.tpl');

gen_logged_from($tpl);

$tpl->assign(
	array(
		'TR_LOSTPW_EMAIL' => tr('Lostpw email'),
		'TR_MESSAGE_TEMPLATE_INFO' => tr('Message template info'),
		'TR_MESSAGE_TEMPLATE' => tr('Message template'),
		'SUBJECT_VALUE1' => tohtml($data_1['subject']),
		'MESSAGE_VALUE1' => tohtml($data_1['message']),
		'SUBJECT_VALUE2' => tohtml($data_2['subject']),
		'MESSAGE_VALUE2' => tohtml($data_2['message']),
		'SENDER_EMAIL_VALUE' => tohtml($data_1['sender_email']),
		'SENDER_NAME_VALUE' => tohtml($data_1['sender_name']),
		'TR_ACTIVATION_EMAIL' => tr('Activation E-Mail'),
		'TR_PASSWORD_EMAIL' => tr('Password E-Mail'),
		'TR_USER_LOGIN_NAME' => tr('User login (system) name'),
		'TR_USER_PASSWORD' => tr('User password'),
		'TR_USER_REAL_NAME' => tr('User (first and last) name'),
		'TR_LOSTPW_LINK' => tr('Lostpw link'),
		'TR_SUBJECT' => tr('Subject'),
		'TR_MESSAGE' => tr('Message'),
		'TR_SENDER_EMAIL' => tr('Senders email'),
		'TR_SENDER_NAME' => tr('Senders name'),
		'TR_APPLY_CHANGES' => tr('Apply changes'),
		'TR_BASE_SERVER_VHOST' => tr('URL to this admin panel'),
		'TR_BASE_SERVER_VHOST_PREFIX' => tr('URL protocol')
	)
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onResellerScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
