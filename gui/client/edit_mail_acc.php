<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware							|
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
$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/edit_mail_acc.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('normal_mail', 'page');
$tpl -> define_dynamic('forward_mail', 'page');
$tpl -> define_dynamic('custom_buttons', 'page');

//
// page functions.
//
function edit_mail_account(&$tpl, &$sql)
{
  if (!isset($_GET['id']) || $_GET['id'] === '' || !is_numeric($_GET['id'])) {
    set_page_message(tr('Email account not found!'));
    header('Location: email_accounts.php');
    die();
  } else {
    $mail_id = $_GET['id'];
  }

  $dmn_name = $_SESSION['user_logged'];

  $query = <<<SQL_QUERY
          select
              t1.*, t2.domain_id, t2.domain_name
          from
              mail_users as t1,
              domain as t2
          where
              t1.mail_id = ?
            and
              t2.domain_id = t1.domain_id
            and
              t2.domain_name = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($mail_id, $dmn_name));

  if ($rs -> RecordCount() == 0) {
    set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
    header('Location: email_accounts.php');
    die();
  } else {
    $mail_acc = $rs -> fields['mail_acc'];
    $domain_id = $rs -> fields['domain_id'];
    $mail_type = $rs -> fields['mail_type'];
    $mail_forward = $rs -> fields['mail_forward'];
    $sub_id = $rs -> fields['sub_id'];
    if ($mail_type == MT_NORMAL_MAIL) {
      $mtype = 1;
      $res1 = exec_query($sql, "select domain_name from domain where domain_id=?", array($domain_id));
      $tmp1 = $res1->FetchRow(0);
      $maildomain = $tmp1['domain_name'];
    } elseif ($mail_type == MT_NORMAL_FORWARD) {
      $mtype = 4;
      $res1 = exec_query($sql, "select domain_name from domain where domain_id=?", array($domain_id));
      $tmp1 = $res1->FetchRow(0);
      $maildomain = $tmp1['domain_name'];
    } elseif ($mail_type == MT_ALIAS_MAIL) {
      $mtype = 2;
      $res1 = exec_query($sql, "select alias_name from domain_aliasses where alias_id=?", array($sub_id));
      $tmp1 = $res1->FetchRow(0);
      $maildomain = $tmp1['alias_name'];
    } elseif ($mail_type == MT_ALIAS_FORWARD) {
      $mtype = 5;
      $res1 = exec_query($sql, "select alias_name from domain_aliasses where alias_id=?", array($sub_id));
      $tmp1 = $res1->FetchRow();
      $maildomain = $tmp1['alias_name'];
    } elseif ($mail_type == MT_SUBDOM_MAIL) {
      $mtype = 3;
      $res1 = exec_query($sql, "select subdomain_name from subdomain where subdomain_id=?", array($sub_id));
      $tmp1 = $res1->FetchRow();
      $maildomain = $tmp1['subdomain_name'];
      $res1 = exec_query($sql, "select domain_name from domain where domain_id=?", array($domain_id));
      $tmp1 = $res1->FetchRow(0);
      $maildomain = $maildomain.".".$tmp1['domain_name'];
    } elseif ($mail_type == MT_SUBDOM_FORWARD) {
      $mtype = 6;
      $res1 = exec_query($sql, "select subdomain_name from subdomain where subdomain_id=?", array($sub_id));
      $tmp1 = $res1->FetchRow();
      $maildomain = $tmp1['subdomain_name'];
      $res1 = exec_query($sql, "select domain_name from domain where domain_id=?", array($domain_id));
      $tmp1 = $res1->FetchRow(0);
      $maildomain = $maildomain.".".$tmp1['domain_name'];
    }

    $mail_forward = $rs -> fields['mail_forward'];

    if (isset($_POST['forward_list'])) {
      $mail_forward = $_POST['forward_list'];
    }
    $mail_acc = decode_idna($mail_acc);
    $maildomain = decode_idna($maildomain);
    $tpl -> assign(array('EMAIL_ACCOUNT' => $mail_acc."@".$maildomain,
                         'FORWARD_LIST' => $mail_forward,
                         'MTYPE' => $mtype,
                         'MAIL_ID' => $mail_id));
    if ($mail_forward === '_no_') {
      $tpl -> assign(array('ACTION' => "update_pass"));
      $tpl -> assign('FORWARD_MAIL', '');
      $tpl -> parse('NORMAL_MAIL', '.normal_mail');
    } else {
      $tpl -> assign(array('ACTION' => "update_forward"));
      $tpl -> assign('NORMAL_MAIL', '');
      $tpl -> parse('FORWARD_MAIL', '.forward_mail');
    }
  }
}

function update_email_pass($sql)
{
  if (!isset($_POST['uaction'])) {
    return;
  }
  if ($_POST['uaction'] != 'update_pass') {
    return;
  }

  $pass = escapeshellcmd($_POST['pass']);
  $pass_rep = escapeshellcmd($_POST['pass_rep']);
  $mail_id = $_GET['id'];
  $mail_account = $_POST['mail_account'];

  if ($pass === '' || $pass_rep === '' || $mail_id === '' || !is_numeric($mail_id)) {
    set_page_message(tr('Missing or wrong data!'));
    return;
  } else if ($pass !== $pass_rep) {
    set_page_message(tr('Entered passwords differ!'));
    return;
  	// Not permitted chars
  } else if (preg_match("/[`´'\"\\|<>^\x00-\x1f]/i", $pass)) {
    set_page_message(tr('Password data includes not valid signs!'));
    return;
  }
  else {
    global $cfg;
    $status = $cfg['ITEM_CHANGE_STATUS'];

    check_for_lock_file();

    $query = <<<SQL_QUERY
          update
              mail_users
          set
              mail_pass = ?,
              status = ?
          where
              mail_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($pass, $status, $mail_id));

    send_request();
    set_page_message(tr("Mail were updated successfully!"));
    write_log($_SESSION['user_logged'].": change mail account password: $mail_account");

    header( "Location: email_accounts.php" );
    die();
  }
}

function update_email_forward(&$tpl, &$sql)
{
  if (!isset($_POST['uaction'])) {
    return;
  }
  if($_POST['uaction'] != 'update_forward') {
    return;
  }

  $mail_account = $_POST['mail_account'];
  $mail_id = $_GET['id'];
  $forward_list = $_POST['forward_list'];

  $faray = preg_split ("/[\n]+/",$forward_list);

  foreach ($faray as $value) {
    $value = trim($value);
    if (chk_email($value) > 0 && $value !== '') {
      /* ERR .. strange :) not email in this line - warrning */
      set_page_message(tr("Mail forward list error!"));
      return;
    } else if ($value === '') {
          set_page_message(tr("Mail forward list error!"));
          return;
    }
  }

  global $cfg;
  $status = $cfg['ITEM_CHANGE_STATUS'];

  check_for_lock_file();

  $query = <<<SQL_QUERY
          update
              mail_users
          set
              mail_forward = ?,
              status = ?
          where
              mail_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($forward_list, $status, $mail_id));

  send_request();
  write_log($_SESSION['user_logged'].": change mail forward: $mail_account");
  header( "Location: email_accounts.php" );
  die();
}



//
// end page functions.
//
global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(array('TR_CLIENT_EDIT_EMAIL_PAGE_TITLE' => tr('VHCS - Manage Mail and FTP / Edit mail account'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'TID' => $_SESSION['layout_id'],
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

//
// dynamic page data.
//
edit_mail_account($tpl, $sql);
update_email_pass($sql);
update_email_forward($tpl, $sql);

//
// static page messages.
//

gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_email_accounts.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl -> assign(array('TR_EDIT_EMAIL_ACCOUNT' => tr('Edit email account'),
                     'TR_SAVE' => tr('Save'),
                     'TR_PASSWORD' => tr('Password'),
                     'TR_PASSWORD_REPEAT' => tr('Password repeat'),
                     'TR_EDIT' => tr('Edit')));

gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();

?>
