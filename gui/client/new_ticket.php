<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware	|
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
$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/new_ticket.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('custom_buttons', 'page');

//
// page functions.
//
function send_user_message(&$sql, $user_id, $reseller_id)
{
  if (!isset($_POST['uaction'])) return;

  if ($_POST['subj'] === '') {
    set_page_message(tr('Please specify message subject!'));
    return;
  }

  if ($_POST['user_message'] === '') {
    set_page_message(tr('Please type your message!'));
    return;
  }

  $ticket_date = time();
  $urgency = $_POST['urgency'];
  $subj = $_POST['subj'];
  $user_message = strip_html($_POST["user_message"]);
	$ticket_status = 1;
	$ticket_reply = 0;
	$ticket_level = 1;

  $query = <<<SQL_QUERY
        insert into tickets
            (ticket_level, ticket_from, ticket_to,
             ticket_status, ticket_reply, ticket_urgency,
             ticket_date, ticket_subject, ticket_message)
        values
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL_QUERY;

  $rs = exec_query($sql, $query, array($ticket_level,
                                       $user_id,
                                       $reseller_id,
                                       $ticket_status,
                                       $ticket_reply,
                                       $urgency,
                                       $ticket_date,
                                       htmlspecialchars($subj, ENT_QUOTES, "UTF-8"),
                                       htmlspecialchars($user_message, ENT_QUOTES, "UTF-8")));

  send_tickets_msg($reseller_id,$user_id,$subj);
  set_page_message(tr('Your message was sent!'));
  header("Location: support_system.php");
  exit(0);
}


//
// common page data.
//

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(array('TR_CLIENT_NEW_TICKET_PAGE_TITLE' => tr('VHCS - Support system - New ticket'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'TID' => $_SESSION['layout_id'],
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

//
// dynamic page data.
//

if ($cfg['VHCS_SUPPORT_SYSTEM'] != 1) {

	header( "Location: index.php" );

  die();

}

send_user_message($sql, $_SESSION['user_id'],  $_SESSION['user_created_by']);

//
// static page messages.
//

gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_support_system.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl -> assign(array('TR_NEW_TICKET' => tr('New ticket'),
                     'TR_LOW' => tr('Low'),
                     'TR_MEDIUM' => tr('Medium'),
                     'TR_HIGH' => tr('High'),
                     'TR_VERI_HIGH' => tr('Very high'),
                     'TR_URGENCY' => tr('Priority'),
                     'TR_EMAIL' => tr('Email'),
                     'TR_SUBJECT' => tr('Subject'),
                     'TR_YOUR_MESSAGE' => tr('Your message'),
                     'TR_SEND_MESSAGE' => tr('Send message'),
                     'TR_OPEN_TICKETS' => tr('Open tickets'),
                     'TR_CLOSED_TICKETS' => tr('Closed tickets')));

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();

?>
