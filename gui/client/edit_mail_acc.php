<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'] . '/edit_mail_acc.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('normal_mail', 'page');
$tpl->define_dynamic('forward_mail', 'page');

// page functions

function edit_mail_account(&$tpl, &$sql) {
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

	if ($rs->RecordCount() == 0) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
		header('Location: email_accounts.php');
		die();
	} else {
		$mail_acc = $rs->fields['mail_acc'];
		$domain_id = $rs->fields['domain_id'];
		$mail_type_list = $rs->fields['mail_type'];
		$mail_forward = $rs->fields['mail_forward'];
		$sub_id = $rs->fields['sub_id'];

		foreach (explode(',', $mail_type_list) as $mail_type) {
			if ($mail_type == MT_NORMAL_MAIL) {
				$mtype[] = 1;
				$res1 = exec_query($sql, "SELECT domain_name FROM domain WHERE domain_id=?", array($domain_id));
				$tmp1 = $res1->FetchRow(0);
 				$maildomain = $tmp1['domain_name'];
			} else if ($mail_type == MT_NORMAL_FORWARD) {
				$mtype[] = 4;
				$res1 = exec_query($sql, "SELECT domain_name FROM domain WHERE domain_id=?", array($domain_id));
				$tmp1 = $res1->FetchRow(0);
				$maildomain = $tmp1['domain_name'];
			} else if ($mail_type == MT_ALIAS_MAIL) {
				$mtype[] = 2;
				$res1 = exec_query($sql, "SELECT alias_name FROM domain_aliasses WHERE alias_id=?", array($sub_id));
				$tmp1 = $res1->FetchRow(0);
				$maildomain = $tmp1['alias_name'];
			} else if ($mail_type == MT_ALIAS_FORWARD) {
				$mtype[] = 5;
				$res1 = exec_query($sql, "SELECT alias_name FROM domain_aliasses WHERE alias_id=?", array($sub_id));
				$tmp1 = $res1->FetchRow();
				$maildomain = $tmp1['alias_name'];
			} else if ($mail_type == MT_SUBDOM_MAIL) {
				$mtype[] = 3;
				$res1 = exec_query($sql, "SELECT subdomain_name FROM subdomain WHERE subdomain_id=?", array($sub_id));
 				$tmp1 = $res1->FetchRow();
				$maildomain = $tmp1['subdomain_name'];
				$res1 = exec_query($sql, "SELECT domain_name FROM domain WHERE domain_id=?", array($domain_id));
				$tmp1 = $res1->FetchRow(0);
				$maildomain = $maildomain . "." . $tmp1['domain_name'];
			} else if ($mail_type == MT_SUBDOM_FORWARD) {
 				$mtype[] = 6;
				$res1 = exec_query($sql, "SELECT subdomain_name FROM subdomain WHERE subdomain_id=?", array($sub_id));
				$tmp1 = $res1->FetchRow();
				$maildomain = $tmp1['subdomain_name'];
				$res1 = exec_query($sql, "SELECT domain_name FROM domain WHERE domain_id=?", array($domain_id));
				$tmp1 = $res1->FetchRow(0);
				$maildomain = $maildomain . "." . $tmp1['domain_name'];
			}
		}

		if (isset($_POST['forward_list'])) {
			$mail_forward = clean_input($_POST['forward_list']);
		}
		$mail_acc = decode_idna($mail_acc);
		$maildomain = decode_idna($maildomain);
		$tpl->assign(
				array(
					'EMAIL_ACCOUNT' => $mail_acc . "@" . $maildomain,
					'FORWARD_LIST' => str_replace(',', "\n", $mail_forward),
					'MTYPE' => implode(',', $mtype),
					'MAIL_TYPE' => $mail_type_list,
					'MAIL_ID' => $mail_id
					)
				);

		if (($mail_forward !== '_no_') && (count($mtype) > 1)) {
			$tpl->assign(
					array(
						'ACTION' => 'update_pass,update_forward',
						'FORWARD_MAIL' => '',
						'FORWARD_MAIL_CHECKED' => 'checked="checked"',
						'FORWARD_LIST_DISABLED' => 'false'
						)
					);
			$tpl->parse('NORMAL_MAIL', '.normal_mail');
		} else if ($mail_forward === '_no_') {
			$tpl->assign(
					array(
						'ACTION' => 'update_pass',
						'FORWARD_MAIL' => '',
						'FORWARD_MAIL_CHECKED' => '',
						'FORWARD_LIST' => '',
						'FORWARD_LIST_DISABLED' => 'true'
						)
					);
			$tpl->parse('NORMAL_MAIL', '.normal_mail');
		} else {
			$tpl->assign(
					array(
						'ACTION' => 'update_forward',
						'NORMAL_MAIL' => '',
						'FORWARD_LIST_DISABLED' => 'false'
						)
					);
			$tpl->parse('FORWARD_MAIL', '.forward_mail');
		}
	}
}

function update_email_pass($sql) {
	if (!isset($_POST['uaction'])) {
		return false;
	}
	if (preg_match('/update_pass/', $_POST['uaction']) == 0) {
		return true;
	}
	if (preg_match('/update_forward/', $_POST['uaction']) == 1 || isset($_POST['mail_forward'])) {
		// The user only wants to update the forward list, not the password
		if ($_POST['pass'] === '' || $_POST['pass_rep'] === '') {
			return true;
		}
	}

	$pass = escapeshellcmd($_POST['pass']);
	$pass_rep = escapeshellcmd($_POST['pass_rep']);
	$mail_id = $_GET['id'];
	$mail_account = clean_input($_POST['mail_account']);

	if (trim($pass) === '' || trim($pass_rep) === '' || $mail_id === '' || !is_numeric($mail_id)) {
		set_page_message(tr('Missing or wrong data!'));
		return false;
	} else if ($pass !== $pass_rep) {
		set_page_message(tr('Entered passwords differ!'));
		return false;
	} else if (preg_match("/[`\xB4'\"\\|<>^\x00-\x1f]/i", $pass)) { // Not permitted chars
		set_page_message(tr('Password data includes not valid signs!'));
		return false;
	} else {
		global $cfg;
		$status = $cfg['ITEM_CHANGE_STATUS'];

		check_for_lock_file();

		$query = <<<SQL_QUERY
          UPDATE
              `mail_users`
          SET
              `mail_pass` = ?,
              `status` = ?
          WHERE
              `mail_id` = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($pass, $status, $mail_id));

		write_log($_SESSION['user_logged'] . ": change mail account password: $mail_account");

		return true;
	}
}

function update_email_forward(&$tpl, &$sql) {
	if (!isset($_POST['uaction'])) {
		return false;
	}
	if (preg_match('/update_forward/', $_POST['uaction']) == 0 && !isset($_POST['mail_forward'])) {
		return true;
	}

	$mail_account = $_POST['mail_account'];
	$mail_id = $_GET['id'];
	$forward_list = clean_input($_POST['forward_list']);
	$mail_accs = array();

	if (isset($_POST['mail_forward']) || $_POST['uaction'] == 'update_forward') {
		$faray = preg_split ('/[\n\s,]+/', $forward_list);

		foreach ($faray as $value) {
			$value = trim($value);
			if (!chk_email($value) && $value !== '') {
				/* ERR .. strange :) not email in this line - warrning */
				set_page_message(tr("Mail forward list error!"));
				return false;
			} else if ($value === '') {
				set_page_message(tr("Mail forward list error!"));
				return false;
			}
			$mail_accs[] = $value;
		}

		$forward_list = implode(',', $mail_accs);

		// Check if the mail type doesn't contain xxx_forward and append it
		if (preg_match('/_forward/', $_POST['mail_type']) == 0) {
			// Get mail account type and append the corresponding xxx_forward
			if ($_POST['mail_type'] == MT_NORMAL_MAIL) {
				$mail_type = $_POST['mail_type'] . ',' . MT_NORMAL_FORWARD;
			} else if ($_POST['mail_type'] == MT_ALIAS_MAIL) {
				$mail_type = $_POST['mail_type'] . ',' . MT_ALIAS_FORWARD;
			} else if ($_POST['mail_type'] == MT_SUBDOM_MAIL) {
				$mail_type = $_POST['mail_type'] . ',' . MT_SUBDOM_FORWARD;
			}
		} else {
			// The mail type already contains xxx_forward, so we can use $_POST['mail_type']
			$mail_type = $_POST['mail_type'];
		}
	} else {
		$forward_list = '_no_';
		// Check if mail type was a forward type and remove it
		if (preg_match('/_forward/', $_POST['mail_type']) == 1) {
			$mail_type = preg_replace('/,[a-z]+_forward$/', '', $_POST['mail_type']);
		}
	}

	global $cfg;
	$status = $cfg['ITEM_CHANGE_STATUS'];

	check_for_lock_file();

	$query = <<<SQL_QUERY
          UPDATE
              `mail_users`
          SET
              `mail_forward` = ?,
              `mail_type` = ?,
              `status` = ?
          WHERE
              `mail_id` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($forward_list, $mail_type, $status, $mail_id));

	write_log($_SESSION['user_logged'] . ": change mail forward: $mail_account");
	return true;
}

// end page functions.

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl->assign(
		array(
			'TR_CLIENT_EDIT_EMAIL_PAGE_TITLE' => tr('ispCP - Manage Mail and FTP / Edit mail account'),
			'THEME_COLOR_PATH' => "../themes/$theme_color",
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => get_logo($_SESSION['user_id'])
			)
		);

// dynamic page data.

edit_mail_account($tpl, $sql);
$ok  = update_email_pass($sql);
$ok2 = update_email_forward($tpl, $sql);

if ($ok && $ok2) {
	set_page_message(tr("Mail were updated successfully!"));
}
if ($ok || $ok2) {
	send_request();
	header("Location: email_accounts.php");
	die();
}

// static page messages.

gen_client_mainmenu($tpl, $cfg['CLIENT_TEMPLATE_PATH'] . '/main_menu_email_accounts.tpl');
gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'] . '/menu_email_accounts.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
		array(
			'TR_EDIT_EMAIL_ACCOUNT' => tr('Edit email account'),
			'TR_SAVE' => tr('Save'),
			'TR_PASSWORD' => tr('Password'),
			'TR_PASSWORD_REPEAT' => tr('Repeat password'),
			'TR_FORWARD_MAIL' => tr('Forward mail'),
			'TR_FORWARD_TO' => tr('Forward to'),
			'TR_FWD_HELP' => tr("Separate multiple email addresses with a line-break."),
			'TR_EDIT' => tr('Edit')
			)
		);

gen_page_message($tpl);
$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg['DUMP_GUI_DEBUG'])
	dump_gui_debug();

unset_messages();

?>
