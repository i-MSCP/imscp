<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware		            		|
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
$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/order_email.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('custom_buttons', 'page');
global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

function gen_email_data(&$tpl, &$sql)
{
  if (isset($_POST['uaction']) && $_POST['uaction'] === 'order_email') {
    $tpl -> assign(array('SUBJECT_VALUE' => $_POST['auto_subject'],
                         'MESSAGE_VALUE' => $_POST['auto_message'],
                         'SENDER_EMAIL_VALUE' => $_POST['sender_email'],
                         'SENDER_NAME_VALUE' => $_POST['sender_name']));
  } else {
    $user_id = $_SESSION['user_id'];

    $query = <<<SQL_QUERY
            select
                fname, lname, email
            from
                admin
            where
                admin_id = ?
SQL_QUERY;
    $rs = exec_query($sql, $query, array($user_id));

    $sender_name = '';

    if ($rs->fields('fname') !='' && $rs->fields('lname') !='') {
      $sender_name = $rs->fields('fname') ." " . $rs->fields('lname');
    }

    $sender_email = $rs->fields['email'];
    $query = <<<SQL_QUERY
            select
                subject, message
            from
                email_tpls
            where
                owner_id = ? and name = 'after-order-msg'
SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_id));

    if ($rs ->RowCount() == 0 ) {
      insert_order_email_tpl($sql, $user_id);
      $rs = exec_query($sql, $query, array($user_id));
    }

    $subject = $rs->fields['subject'];
    $message = $rs->fields['message'];

    $tpl -> assign(array('SUBJECT_VALUE' => $subject,
                         'MESSAGE_VALUE' => $message,
                         'SENDER_EMAIL_VALUE' => $sender_email,
                         'SENDER_NAME_VALUE' => $sender_name,
                         'PAGE_MESSAGE' =>''));
  }
}


function check_user_data ( &$tpl )
{
  global $sender_email, $sender_name;
  global $auto_message, $auto_subject;

  $sender_name= $_POST['sender_name'];
  $sender_email= $_POST['sender_email'];
  $auto_message = $_POST['auto_message'];
  $auto_subject= $_POST['auto_subject'];
  $err_msg = '_off_';

  if ($auto_subject == '') {
      $err_msg = tr('Please specify a subject!');
  } else if ($auto_message == '') {
      $err_msg = tr('Please specify message!');
  } else if ($sender_email == '' || preg_match("/^ *$/", $sender_email)) {
      $err_msg = tr('Please specify email address!');
  } /*else if (chk_email($sender_email)) {
      set_page_message( tr("Incorrect email range or syntax!"));
      return false;
  }
  
  else if ($sender_name == '' || preg_match("/^ *$/", $sender_name)) {

      $err_msg = tr('Please specify sender name!');

  } else if (!preg_match("/ /", $sender_name)) {

      $err_msg = tr('Havent you got more then one name?');

  } */

  if ($err_msg == '_off_') {
      return true;
  } else {
      set_page_message($err_msg);
      return false;
  }
}


function update_email_data(&$tpl, &$sql)
{
  global $sender_name,$sender_email, $auto_message, $auto_subject;

  $user_id = $_SESSION['user_id'];

  if (isset($_POST['uaction']) && $_POST['uaction'] === 'order_email') {
    $sender_name= $_POST['sender_name'];
    $sender_email= $_POST['sender_email'];
    $auto_message = $_POST['auto_message'];
    $auto_subject= $_POST['auto_subject'];

    if (check_user_data($tpl)) {
      // list($fname, $lname) = explode(" ", $sender_name);
      $query = <<<SQL_QUERY
                 update email_tpls set
                    subject = ?,
                    message = ?
                where
                    owner_id = ?
                  and
                    name = 'after-order-msg'
SQL_QUERY;
      $rs = exec_query($sql, $query, array($auto_subject, $auto_message, $user_id));
      set_page_message (tr('Auto email template data updated!'));
      //  Header("Location: users.php");
      //  die();
    }
  }
}

/*
 *
 * static page messages.
 *
 */

$tpl -> assign(array('TR_RESELLER_ORDER_EMAL' => tr('VHCS - Reseller/Order email setup'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_orders.tpl');

gen_logged_from($tpl);

update_email_data($tpl, $sql);

gen_email_data($tpl, $sql);

$tpl -> assign(array('TR_EMAIL_SETUP' => tr('Email setup'),
					  'TR_MANAGE_ORDERS' => tr('Manage orders'),
                     'TR_MESSAGE_TEMPLATE_INFO' => tr('Message template info'),
                     'TR_USER_LOGIN_NAME' => tr('User login (system) name'),
                     'TR_USER_DOMAIN' => tr('Domain name'),
                     'TR_USER_REAL_NAME' => tr('User (first and last) name'),
                     'TR_MESSAGE_TEMPLATE' => tr('Message template'),
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
