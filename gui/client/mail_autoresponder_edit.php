<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
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
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/mail_autoresponder_enable.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

// page functions.

function check_email_user(&$sql) {
	$dmn_name = $_SESSION['user_logged'];
	$mail_id = $_GET['id'];

	$query = <<<SQL_QUERY
		SELECT
			t1.*,
			t2.domain_id,
			t2.domain_name
		FROM
			mail_users AS t1,
			domain AS t2
		WHERE
			t1.mail_id = ?
		AND
			t2.domain_id = t1.domain_id
		AND
			t2.domain_name = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($mail_id, $dmn_name));

	if ($rs->RecordCount() == 0) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
		header('Location: mail_accounts.php');
		die();
	}
}

function gen_page_dynamic_data(&$tpl, &$sql, $mail_id, $read_from_db) {
	// Get Message
	if ($read_from_db) {
		$query = <<<SQL_QUERY
			SELECT
				mail_auto_respond_text, mail_acc
			FROM
				mail_users
			WHERE
				mail_id = ?
SQL_QUERY;
		$rs = exec_query($sql, $query, array($mail_id));
		$mail_name = $rs->fields['mail_acc'];

		$tpl->assign('ARSP_MESSAGE', $rs->fields['mail_auto_respond_text']);
		return;
	} else {
		$arsp_message = clean_input($_POST['arsp_message']);
	}

	$item_change_status = Config::get('ITEM_CHANGE_STATUS');
	check_for_lock_file();

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'enable_arsp') {
		if (empty($_POST['arsp_message'])) {
			$tpl->assign('ARSP_MESSAGE', '');
			set_page_message(tr('Please type your mail autorespond message!'));
			return;
		}

		$query = <<<SQL_QUERY
			UPDATE
				`mail_users`
			SET
				`status` = ?,
				`mail_auto_respond_text` = ?
			WHERE
				`mail_id` = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($item_change_status, $arsp_message, $mail_id));

		send_request();
		$query = "
			SELECT
				`mail_type`,
				IF(`mail_type` like 'normal_%',t2.`domain_name`,
					IF(`mail_type` like 'alias_%',t3.`alias_name`,
						IF(`mail_type` like 'subdom_%', CONCAT(t4.`subdomain_name`,'.',t6.`domain_name`), CONCAT(t5.`subdomain_alias_name`,'.',t7.`alias_name`))
					)
				) AS mailbox
			FROM
				`mail_users` AS t1
			LEFT JOIN (domain AS t2) ON (t1.domain_id = t2.domain_id)
			LEFT JOIN (domain_aliasses AS t3) ON (sub_id = alias_id)
			LEFT JOIN (subdomain AS t4) ON (sub_id = subdomain_id)
			LEFT JOIN (subdomain_alias AS t5) ON (sub_id = subdomain_alias_id)
			LEFT JOIN (domain AS t6) ON (t4.domain_id = t6.domain_id)
			LEFT JOIN (domain_aliasses AS t7) ON (t5.alias_id = t7.alias_id)
			WHERE
				`mail_id` = ?
		";

		$rs = exec_query($sql, $query, array($mail_id));
		$mail_name = $rs->fields['mailbox'];
		write_log($_SESSION['user_logged'] . ": changes mail autoresponder: " . $mail_name);
		set_page_message(tr('Mail account scheduler for modification!'));
		header("Location: mail_accounts.php");
		exit(0);
	} else {
		$tpl->assign('ARSP_MESSAGE', '');
	}
}

// common page data.

if (isset($_GET['id'])) {
	$mail_id = $_GET['id'];
} else if (isset($_POST['id'])) {
	$mail_id = $_POST['id'];
} else {
	header("Location: mail_accounts.php");
	exit(0);
}

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no") {
	header("Location: index.php");
}

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_ENABLE_AUTORESPOND_PAGE_TITLE'	=> tr('ispCP - Client/Enable Mail Auto Responder'),
		'THEME_COLOR_PATH'							=> "../themes/$theme_color",
		'THEME_CHARSET'								=> tr('encoding'),
		'ISP_LOGO'									=> get_logo($_SESSION['user_id'])
	)
);

// dynamic page data.

check_email_user($sql);
gen_page_dynamic_data($tpl, $sql, $mail_id, !isset($_POST['uaction']));

// static page messages.

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_email_accounts.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_email_accounts.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_ENABLE_MAIL_AUTORESPONDER'	=> tr('Edit mail auto responder'),
		'TR_ARSP_MESSAGE'				=> tr('Your message'),
		'TR_ENABLE'						=> tr('Save'),
		'TR_CANCEL'						=> tr('Cancel'),
		'ID'							=> $mail_id
	)
);
gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>