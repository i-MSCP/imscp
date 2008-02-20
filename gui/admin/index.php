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

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl = new pTemplate();
$tpl->define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'] . '/index.tpl');
$tpl->define_dynamic('def_language', 'page');
$tpl->define_dynamic('def_layout', 'page');
$tpl->define_dynamic('no_messages', 'page');
$tpl->define_dynamic('msg_entry', 'page');
$tpl->define_dynamic('update_message', 'page');
$tpl->define_dynamic('traff_warn', 'page');

function gen_system_message(&$tpl, &$sql) {
	$user_id = $_SESSION['user_id'];

	$query = <<<SQL_QUERY
        select
            count(ticket_id) as cnum
        from
            tickets
        where
            ticket_to = ?
          and
            (ticket_status = '2' or ticket_status = '5')
          and
            ticket_reply = '0'
SQL_QUERY;

	$rs = exec_query($sql, $query, array($user_id));

	$num_question = $rs->fields('cnum');

	if ($num_question == 0) {
		$tpl->assign(array('MSG_ENTRY' => ''));
	} else {
		$tpl->assign(
				array(
					'TR_NEW_MSGS' => tr('You have <b>%d</b> new support questions', $num_question),
					'TR_VIEW' => tr('View')
					)
			);

		$tpl->parse('MSG_ENTRY', 'msg_entry');
	}
}

function get_update_infos(&$tpl) {
	global $cfg;

	$last_update = "http://www.isp-control.net/latest.txt";
	// Fake the browser type
	ini_set('user_agent', 'Mozilla/5.0');

	$timeout = 2;
	$old_timeout = ini_set('default_socket_timeout', $timeout);
	$dh2 = @fopen($last_update, 'r');
	ini_set('default_socket_timeout', $old_timeout);

	if (!is_resource($dh2)) {
		$tpl->assign(array('UPDATE' => tr("Couldn't check for updates! Website not reachable.")));
		$tpl->parse('UPDATE_MESSAGE', 'update_message');
		return false;
	}

	$last_update_result = (int)fread($dh2, 8);
	fclose($dh2);

	$current_version = (int)$cfg['BuildDate'];
	if ($current_version < $last_update_result) {
		$tpl->assign(array('UPDATE' => '<a href="ispcp_updates.php" class=\"link\">' . tr('New ispCP update is now available') . '</a>'));
		$tpl->parse('UPDATE_MESSAGE', 'update_message');
	} else {
		$tpl->assign(array('UPDATE_MESSAGE' => ''));
	}
}

function gen_server_trafic(&$tpl, &$sql) {
	$query = <<<SQL_QUERY
        select
            straff_max,straff_warn
        from
            straff_settings
SQL_QUERY;

	$rs = exec_query($sql, $query, array());

	$straff_max = (($rs->fields['straff_max']) * 1024) * 1024;

	$fdofmnth = mktime(0, 0, 0, date("m"), 1, date("Y"));

	$ldofmnth = mktime(1, 0, 0, date("m") + 1, 0, date("Y"));

	$query = <<<SQL_QUERY
        select
            IFNULL((sum(bytes_in) + sum(bytes_out)), 0)  as traffic
        from
            server_traffic
        where
            traff_time > ?
          and
            traff_time < ?
SQL_QUERY;

	$rs1 = exec_query($sql, $query, array($fdofmnth, $ldofmnth));

	$traff = $rs1->fields['traffic'];

	$mtraff = sprintf("%.2f", $traff);

	if ($straff_max == 0) {
		$pr = 0;
	} else {
		$pr = ($traff / $straff_max) * 100;
	}

	if (($straff_max != 0 || $straff_max != '') && ($mtraff > $straff_max)) {
		$tpl->assign('TR_TRAFFIC_WARNING', tr('You are exceeding your traffic limit!')
			);
	} else {
		$tpl->assign('TRAFF_WARN', '');
	}

	$bar_value = calc_bar_value($traff, $straff_max , 400);

	$traff_msg = '';
	if ($straff_max == 0) {
		$traff_msg = tr('%1$d%% [%2$s of unlimited]', $pr, sizeit($mtraff));
	} else {
		$traff_msg = tr('%1$d%% [%2$s of %3$s]', $pr, sizeit($mtraff), sizeit($straff_max));
	}

	$tpl->assign(
		array('TRAFFIC_WARNING' => $traff_msg,
			'BAR_VALUE' => $bar_value,
			)
		);
}

/*
 *
 * static page messages.
 *
 */

$tpl->assign(
		array(
			'TR_ADMIN_MAIN_INDEX_PAGE_TITLE' => tr('ispCP - Admin/Main Index'),
			'THEME_COLOR_PATH' => "../themes/$theme_color",
			'ISP_LOGO' => get_logo($_SESSION['user_id']),
			'THEME_CHARSET' => tr('encoding'),
			)
	);

gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'] . '/main_menu_general_information.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'] . '/menu_general_information.tpl');

get_admin_general_info($tpl, $sql);

get_update_infos($tpl);

gen_system_message($tpl, $sql);

gen_server_trafic($tpl, $sql);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg['DUMP_GUI_DEBUG'])
	dump_gui_debug();

unset_messages();

?>