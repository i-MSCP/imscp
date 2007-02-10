<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware		            		|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------



include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();
$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/email_setup.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('hosting_plans', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$user_id = $_SESSION['user_id'];

$data = get_welcome_email($user_id);

if (isset($_POST['uaction']) && $_POST['uaction'] == 'email_setup') {

	$data['subject'] = clean_input($_POST['auto_subject']);

  $data['message'] = clean_input($_POST['auto_message']);

  if ($data['subject'] == '') {

		set_page_message(tr('Please specify a subject!'));

  } else if ($data['message'] == '') {

  	set_page_message(tr('Please specify message!'));

  } else {

		set_welcome_email($user_id, $data);

  	set_page_message (tr('Auto email template data updated!'));

	}

}

/*
 *
 * static page messages.
 *
 */

$tpl -> assign(array(
        	'TR_ADMIN_MANAGE_EMAIL_SETUP_PAGE_TITLE' => tr('VHCS - Admin/Manage users/Email setup'),
        	'THEME_COLOR_PATH' => "../themes/$theme_color",
        	'THEME_CHARSET' => tr('encoding'),
        	'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
					'ISP_LOGO' => get_logo($_SESSION['user_id'])));

gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_settings.tpl');

$tpl -> assign(array(
        	'TR_EMAIL_SETUP' => tr('Email setup'),
        	'TR_MESSAGE_TEMPLATE_INFO' => tr('Message template info'),
        	'TR_USER_LOGIN_NAME' => tr('User login (system) name'),
        	'TR_USER_PASSWORD' => tr('User password'),
        	'TR_USER_REAL_NAME' => tr('User real (first and last) Name'),
        	'TR_MESSAGE_TEMPLATE' => tr('Message template'),
        	'TR_SUBJECT' => tr('Subject'),
        	'TR_MESSAGE' => tr('Message'),
        	'TR_SENDER_EMAIL' => tr('Senders email'),
        	'TR_SENDER_NAME' => tr('Senders name'),
        	'TR_APPLY_CHANGES' => tr('Apply changes'),
        	'SUBJECT_VALUE' => $data['subject'],
        	'MESSAGE_VALUE' => $data['message'],
        	'SENDER_EMAIL_VALUE' => $data['sender_email'],
        	'SENDER_NAME_VALUE' => $data['sender_name']));

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();

?>
