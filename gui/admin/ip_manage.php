<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2007 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team (2007)
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

$tpl->define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'] . '/ip_manage.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('ip_row', 'page');

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl->assign(
		array(
			'TR_ADMIN_IP_MANAGE_PAGE_TITLE' => tr('ispCP - Admin/IP manage'),
			'THEME_COLOR_PATH' => "../themes/$theme_color",
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => get_logo($_SESSION['user_id']),
			'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE']
		)
	);

function show_IPs(&$tpl, &$sql) {
	$query = <<<SQL_QUERY
        select
            *
        from
            server_ips
SQL_QUERY;

	$rs = exec_query($sql, $query, array());

	$row = 1;
	global $del;

	if ($rs->RecordCount() < 2) {
		$del = "#";
		// $tpl -> assign('IP_ROW', '');
	} while (!$rs->EOF) {
		if ($row++ % 2 == 0) {
			$tpl->assign('IP_CLASS', 'content');
		} else {
			$tpl->assign('IP_CLASS', 'content2');
		}

		if ($del === '#') {
			$delete_ip = $del;
		} else {
			$delete_ip = $rs->fields['ip_id'];
		}

		$tpl->assign(
			array('IP' => $rs->fields['ip_number'],
				'DOMAIN' => $rs->fields['ip_domain'],
				'ALIAS' => $rs->fields['ip_alias'],
				'DELETE_ID' => $delete_ip,
				)
			);

		$tpl->parse('IP_ROW', '.ip_row');

		$rs->MoveNext();
	} //while
}

function add_ip(&$tpl, &$sql) {
	global $ip_number_1, $ip_number_2, $ip_number_3, $ip_number_4;

	global $domain, $alias;

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_ip') {
		if (check_user_data()) {
			// add ip
			global $ip_number_1, $ip_number_2, $ip_number_3, $ip_number_4;

			$ip_number = trim($ip_number_1) . '.' . trim($ip_number_2) . '.' . trim($ip_number_3) . '.' . trim($ip_number_4);

			$query = <<<SQL_QUERY
                insert into server_ips
                    (ip_number,ip_domain,ip_alias)
                values
                    (?,?,?)
SQL_QUERY;
			$rs = exec_query($sql, $query, array($ip_number, htmlspecialchars($domain, ENT_QUOTES, "UTF-8"),
					htmlspecialchars($alias, ENT_QUOTES, "UTF-8")));

			set_page_message(tr('New IP was added!'));

			$user_logged = $_SESSION['user_logged'];

			write_log("$user_logged: add new IP4 address: $ip_number!");
		}

		$tpl->assign(
				array(
					'VALUE_IP1' => $_POST['ip_number_1'],
					'VALUE_IP2' => $_POST['ip_number_2'],
					'VALUE_IP3' => $_POST['ip_number_3'],
					'VALUE_IP4' => $_POST['ip_number_4'],
					'VALUE_DOMAIN' => clean_input($_POST['domain']),
					'VALUE_ALIAS' => clean_input($_POST['alias']),
				)
			);
	} else {
		$tpl->assign(
				array(
					'VALUE_IP1' => '',
					'VALUE_IP2' => '',
					'VALUE_IP3' => '',
					'VALUE_IP4' => '',
					'VALUE_DOMAIN' => '',
					'VALUE_ALIAS' => '',
				)
			);
	}
}

function check_user_data () {
	global $ip_number_1, $ip_number_2, $ip_number_3, $ip_number_4;

	$ip_number_1 = $_POST['ip_number_1'];
	$ip_number_2 = $_POST['ip_number_2'];
	$ip_number_3 = $_POST['ip_number_3'];
	$ip_number_4 = $_POST['ip_number_4'];

	global $domain, $alias;

	$domain = clean_input($_POST['domain']);
	$alias = clean_input($_POST['alias']);

	$err_msg = '_off_';

	if ($ip_number_1 < 0 || $ip_number_1 > 255 || !is_numeric($ip_number_1) ||
		$ip_number_2 < 0 || $ip_number_2 > 255 || !is_numeric($ip_number_2) ||
		$ip_number_3 < 0 || $ip_number_3 > 255 || !is_numeric($ip_number_3) ||
		$ip_number_4 < 0 || $ip_number_4 > 255 || !is_numeric($ip_number_4)) {
		$err_msg = tr('Wrong IP number!');
	} else if ($domain == '') {
		$err_msg = tr('Please specify domain!');
	} else if ($alias == '') {
		$err_msg = tr('Please specify alias!');
	} else if (IP_exists()) {
		$err_msg = tr('This IP already exist!');
	}

	if ($err_msg == '_off_') {
		return true;
	} else {
		set_page_message($err_msg);

		return false;
	}
}

function IP_exists() {
	global $sql;

	global $ip_number_1, $ip_number_2, $ip_number_3, $ip_number_4;

	$ip_number = trim($ip_number_1) . '.' . trim($ip_number_2) . '.' . trim($ip_number_3) . '.' . trim($ip_number_4);

	$query = <<<SQL_QUERY
        select
            *
        from
            server_ips
        where
            ip_number = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($ip_number));

	if ($rs->RowCount() == 0)

		return false;

	return true;
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'] . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'] . '/menu_settings.tpl');

add_ip($tpl, $sql);

show_IPs($tpl, $sql);

$tpl->assign(
		array(
			'MANAGE_IPS' => tr('Manage IPs'),
			'TR_AVAILABLE_IPS' => tr('Available IPs'),
			'TR_IP' => tr('IP'),
			'TR_DOMAIN' => tr('Domain'),
			'TR_ALIAS' => tr('Alias'),
			'TR_ACTION' => tr('Action'),
			'TR_UNINSTALL' => tr('Remove IP'),
			'TR_ADD' => tr('Add'),
			'TR_ADD_NEW_IP' => tr('Add new IP'),
			'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete'),
			)
	);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');

$tpl->prnt();

if ($cfg['DUMP_GUI_DEBUG'])
	dump_gui_debug();

unset_messages();

?>