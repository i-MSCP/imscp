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
$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/circular.tpl');


$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('hosting_plans', 'page');

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADMIN_CIRCULAR_PAGE_TITLE' => tr('ispCP - Admin - Email Marketing'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE']
                     )
              );

function gen_page_data ( &$tpl, &$sql)
{

    if (isset($_POST['uaction']) && $_POST['uaction'] === 'send_circular') {

        $tpl -> assign(
            array(
                'MESSAGE_SUBJECT' => clean_input($_POST['msg_subject']),
                'MESSAGE_TEXT' => clean_input($_POST['msg_text']),
                'SENDER_EMAIL' => clean_input($_POST['sender_email']),
                'SENDER_NAME' => clean_input($_POST['sender_name'])
                )
            );
    } else {

        $user_id= $_SESSION['user_id'];


        $query = <<<SQL_QUERY
            select
                fname, lname, email
            from
                admin
            where
                admin_id = ?
            group by
                email
SQL_QUERY;

        $rs = exec_query($sql, $query, array($user_id));

        $tpl -> assign(
            array(
                'MESSAGE_SUBJECT' => '',
                'MESSAGE_TEXT' => '',
                'SENDER_EMAIL' => $rs->fields['email'],
                'SENDER_NAME' => $rs->fields['fname']." ".$rs->fields['lname']
                )
            );
    }
}

function check_user_data ( &$tpl ) {

    global $msg_subject;

    global $msg_text;

    global $sender_email;

    global $sender_name;

    $err_message = '_off_';

    $msg_subject = clean_input($_POST['msg_subject']);

    $msg_text = clean_input($_POST['msg_text']);

    $sender_email = clean_input($_POST['sender_email']);

    $sender_name = clean_input($_POST['sender_name']);

    if ($msg_subject == '') {

        $err_message = tr('Please specify a message subject!');

    } else if ($msg_text == '') {

        $err_message = tr('Please specify a message content!');

    } else if ($sender_name == '') {

        $err_message = tr('Please specify a sender name!');

    } else if ($sender_email == '') {

        $err_message = tr('Please specify a sender email!');

    } else if (!chk_email($sender_email)) {

        set_page_message( tr("Incorrect email length or syntax!"));

        return false;
    }

    if ($err_message != '_off_') {

        set_page_message($err_message);

        return false;

    } else {

        return true;
    }

}


function send_reseller_message ( &$sql) {

    $user_id = $_SESSION['user_id'];

    $msg_subject = clean_input($_POST['msg_subject']);

    $msg_text = clean_input($_POST['msg_text']);

    $sender_email = clean_input($_POST['sender_email']);

    $sender_name = clean_input($_POST['sender_name']);

    $query = <<<SQL_QUERY
        select
            admin_id, fname, lname, email
        from
            admin
        where
            admin_type = 'reseller' and created_by = ?
        group by
            email
SQL_QUERY;


    $rs = exec_query($sql, $query, array($user_id));

    while (!$rs -> EOF) {


        if ($_POST['rcpt_to'] == 'rslrs' || $_POST['rcpt_to'] == 'usrs_rslrs') {

            $to = encode($rs->fields['fname']." ".$rs->fields['lname'])." <".$rs->fields['email'].">";
            send_circular_email($to, encode($sender_name)." <$sender_email>", stripslashes($msg_subject),
			                    stripslashes($msg_text));

        }

        if ($_POST['rcpt_to'] == 'usrs' || $_POST['rcpt_to'] == 'usrs_rslrs') {

            send_reseller_users_message($sql, $rs->fields['admin_id']);

        }


        $rs -> MoveNext();
    }

    set_page_message(tr('You send email to your users successfully!'));
	write_log('Mass email was sent from '.$sender_name.'<'.$sender_email.'>!');


}


function send_circular(&$tpl, &$sql)
{

    if (isset($_POST['uaction']) && $_POST['uaction'] === 'send_circular') {

        if (check_user_data($tpl)) {

            send_reseller_message( &$sql);

			unset($_POST['uaction']);

			gen_page_data($tpl, $sql);

        }
    }
}


function send_reseller_users_message ( &$sql, $admin_id)
{

    $msg_subject = clean_input($_POST['msg_subject']);

    $msg_text = clean_input($_POST['msg_text']);

    $sender_email = clean_input($_POST['sender_email']);

    $sender_name = clean_input($_POST['sender_name']);

    $query = <<<SQL_QUERY
        select
            fname, lname, email
        from
            admin
        where
            admin_type = 'user' and created_by = ?
        group by
            email
SQL_QUERY;

    $rs = exec_query($sql, $query, array($admin_id));

    while (!$rs -> EOF) {

        $to = encode($rs->fields['fname']." ".$rs->fields['lname'])." <".$rs->fields['email'].">";

        send_circular_email($to, encode($sender_name)." <$sender_email>", stripslashes($msg_subject), stripslashes($msg_text));

        $rs -> MoveNext();
    }

}


function send_circular_email ($to, $from, $subject, $message)
{
    $subject = encode($subject);

    $headers = "From: $from\r\n";

    $headers .= "MIME-Version: 1.0\nContent-Type: text/plain\nContent-Transfer-Encoding: 8bit\n";

    $headers .= "X-Mailer: ispCP marketing mailer";

	mail($to, $subject, $message, $headers);

}


/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/main_menu_manage_users.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_manage_users.tpl');

$tpl -> assign(
    array(
        'TR_CIRCULAR' => tr('Email marketing'),
        'TR_CORE_DATA' => tr('Core data'),
        'TR_SEND_TO' => tr('Send message to'),
        'TR_ALL_USERS' => tr('All users'),
        'TR_ALL_RESELLERS' => tr('All resellers'),
        'TR_ALL_USERS_AND_RESELLERS' => tr('All users & resellers'),
        'TR_MESSAGE_SUBJECT' => tr('Message subject'),
        'TR_MESSAGE_TEXT' => tr('Message'),
        'TR_ADDITIONAL_DATA' => tr('Additional data'),
        'TR_SENDER_EMAIL' => tr('Senders email'),
        'TR_SENDER_NAME' => tr('Senders name'),
        'TR_SEND_MESSAGE' => tr('Send message'),
        'TR_SENDER_NAME' => tr('Senders name'),
        )
    );

send_circular($tpl, $sql);

gen_page_data ($tpl, $sql);

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();
?>