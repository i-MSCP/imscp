<?php
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/lostpassword.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$user_id = $_SESSION['user_id'];

$selected_on = '';

$selected_off = '';

$data_1 = get_lostpassword_activation_email($user_id);

$data_2 = get_lostpassword_password_email($user_id);

if (isset($_POST['uaction']) && $_POST['uaction'] == 'apply') {

  $data_1['subject'] = clean_input($_POST['subject1']);

  $data_1['message'] = clean_input($_POST['message1']);

  $data_2['subject'] = clean_input($_POST['subject2']);

  $data_2['message'] = clean_input($_POST['message2']);

  if ( ($data_1['subject'] == '') OR ($data_2['subject'] == '') ) {

		set_page_message(tr('Please specify a subject!'));

  } else if ( ($data_1['message'] == '') OR ($data_2['message'] == '') ) {

  	set_page_message(tr('Please specify message!'));

  } else {

		set_lostpassword_activation_email($user_id, $data_1);

		set_lostpassword_password_email($user_id, $data_2);

  	set_page_message (tr('Auto email template data updated!'));

	}

}

/*
 *
 * static page messages.
 *
 */

$tpl -> assign(array('TR_LOSTPW_EMAL_SETUP' => tr('ispCP - Reseller/Lostpw email setup'),
					 'THEME_COLOR_PATH' => "../themes/$theme_color",
					 'THEME_CHARSET' => tr('encoding'),
					 'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
					 'ISP_LOGO' => get_logo($_SESSION['user_id'])));

gen_reseller_mainmenu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/main_menu_manage_users.tpl');
gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_manage_users.tpl');

gen_logged_from($tpl);

$tpl -> assign(array('TR_LOSTPW_EMAIL' => tr('Lostpw email'),
					 'TR_MESSAGE_TEMPLATE_INFO' => tr('Message template info'),
					 'TR_MESSAGE_TEMPLATE' => tr('Message template'),
					 'SUBJECT_VALUE1' => $data_1['subject'],
					 'MESSAGE_VALUE1' => $data_1['message'],
					 'SUBJECT_VALUE2' => $data_2['subject'],
					 'MESSAGE_VALUE2' => $data_2['message'],
					 'SENDER_EMAIL_VALUE' => $data_1['sender_email'],
					 'SENDER_NAME_VALUE' => $data_1['sender_name'],
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
					 'TR_APPLY_CHANGES' => tr('Apply changes')));

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();

?>