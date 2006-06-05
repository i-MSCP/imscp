<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 be moleSoftware		            					|
//  |			http://vhcs.net | http://www.molesoftware.com		      						     		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org																    |
//  |                                                                               |
//   -------------------------------------------------------------------------------


include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();
$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/lostpassword.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('custom_buttons', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$user_id = $_SESSION['user_id'];

$selected_on = '';

$selected_off = '';

$data = get_email_data($user_id);


if (isset($_POST['uaction']) && $_POST['uaction'] == 'apply') {

  $data['subject_1'] = $_POST['subject1'];

  $data['message_1'] = $_POST['message1'];

  $data['subject_2'] = $_POST['subject2'];

  $data['message_2'] = $_POST['message2'];

  if ( ($data['subject_1'] == '') OR ($data['subject_2'] == '') ) {

		set_page_message(tr('Please specify a subject!'));
      
  } else if ( ($data['message_1'] == '') OR ($data['message_2'] == '') ) {

  	set_page_message(tr('Please specify message!'));
     
  } else {

		set_email_data($user_id, $data);

  	set_page_message (tr('Auto email template data updated!'));
  
	}
	
}

/*
 *
 * static page messages.
 *
 */

$sender_name = $data['sender_name'];

$sender_email = $data['sender_email'];

$subject_1 = $data['subject_1'];

$message_1 = $data['message_1'];

$subject_2 = $data['subject_2'];

$message_2 = $data['message_2'];

$tpl -> assign(array('TR_LOSTPW_EMAL_SETUP' => tr('VHCS - Reseller/Lostpw email setup'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

gen_admin_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_manage_users.tpl');

gen_logged_from($tpl);

$tpl -> assign(array(
										 'TR_LOSTPW_EMAIL' => tr('Lostpw email'),
                     'TR_MESSAGE_TEMPLATE_INFO' => tr('Message template info'),
                     'TR_MESSAGE_TEMPLATE' => tr('Message template'),
										 'SUBJECT_VALUE1' => $subject_1,
                     'MESSAGE_VALUE1' => $message_1,
										 'SUBJECT_VALUE2' => $subject_2,
                     'MESSAGE_VALUE2' => $message_2,
                     'SENDER_EMAIL_VALUE' => $sender_email,
                     'SENDER_NAME_VALUE' => $sender_name,
                     'TR_LOSTPW_MESSAGE_1' => tr('Lostpw message 1'),
                     'TR_LOSTPW_MESSAGE_2' => tr('Lostpw message 2'),
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

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();

?>