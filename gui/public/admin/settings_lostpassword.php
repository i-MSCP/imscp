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
 * @subpackage	Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/settings_lostpassword.tpl',
		'page_message' => 'layout',
		'logged_from' => 'page',
		'custom_buttons' => 'page'));

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
		$err_message = tr('Please specify a message subject.');
	}
	if (empty($data_1['message']) || empty($data_2['message'])) {
		$err_message = tr('Please specify a message content.');
	}

	if (!empty($err_message)) {
		set_page_message($err_message, 'error');
	} else {
		set_lostpassword_activation_email($user_id, $data_1);
		set_lostpassword_password_email($user_id, $data_2);
		set_page_message(tr('Auto email template data updated!'), 'success');
	}
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings / Lost Password Email'),
		'ISP_LOGO' => layout_getUserLogo()));

generateNavigation($tpl);
generateLoggedFrom($tpl);

$tpl->assign(
	array(
		'TR_LOSTPW_EMAIL' => tr('Lost password email'),
		'TR_MESSAGE_TEMPLATE_INFO' => tr('Message template info'),
		'TR_MESSAGE_TEMPLATE' => tr('Message template'),
		'SUBJECT_VALUE1' => clean_input($data_1['subject'], true),
		'MESSAGE_VALUE1' => tohtml($data_1['message']),
		'SUBJECT_VALUE2' => clean_input($data_2['subject'], true),
		'MESSAGE_VALUE2' => tohtml($data_2['message']),
		'SENDER_EMAIL_VALUE' => tohtml($data_1['sender_email']),
		'SENDER_NAME_VALUE' => tohtml($data_1['sender_name']),
		'TR_ACTIVATION_EMAIL' => tr('Activation email'),
		'TR_PASSWORD_EMAIL' => tr('Password email'),
		'TR_USER_LOGIN_NAME' => tr('User login (system) name'),
		'TR_USER_PASSWORD' => tr('User password'),
		'TR_USER_REAL_NAME' => tr('User (first and last) name'),
		'TR_LOSTPW_LINK' => tr('Lost password link'),
		'TR_SUBJECT' => tr('Subject'),
		'TR_MESSAGE' => tr('Message'),
		'TR_SENDER_EMAIL' => tr('Sender email'),
		'TR_SENDER_NAME' => tr('Sender name'),
		'TR_APPLY_CHANGES' => tr('Apply changes'),
		'TR_BASE_SERVER_VHOST' => tr('URL to this admin panel'),
		'TR_BASE_SERVER_VHOST_PREFIX' => tr('URL protocol')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
