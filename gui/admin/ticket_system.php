<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

if (!Config::get('ISPCP_SUPPORT_SYSTEM')) {
	user_goto('index.php');
}

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/ticket_system.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('tickets_list', 'page');
$tpl->define_dynamic('tickets_item', 'tickets_list');
$tpl->define_dynamic('scroll_prev_gray', 'page');
$tpl->define_dynamic('scroll_prev', 'page');
$tpl->define_dynamic('scroll_next_gray', 'page');
$tpl->define_dynamic('scroll_next', 'page');

// page functions.

function gen_tickets_list(&$tpl, &$sql, $user_id) {
	$start_index = 0;

	$rows_per_page = Config::get('DOMAIN_ROWS_PER_PAGE');

	if (isset($_GET['psi']))
		$start_index = $_GET['psi'];

	$count_query = "
		SELECT
			COUNT(`ticket_id`) AS cnt
		FROM
			`tickets`
		WHERE
			`ticket_status` != 0
		AND
			`ticket_reply` = 0
	";

	$rs = exec_query($sql, $count_query, array());
	$records_count = $rs->fields['cnt'];

	$query = "
		SELECT
			`ticket_id`,
			`ticket_status`,
			`ticket_urgency`,
			`ticket_date`,
			`ticket_subject`,
			`ticket_message`
		FROM
			`tickets`
		WHERE
			`ticket_status` != 0
		AND
			`ticket_reply` = 0
		ORDER BY
			`ticket_date` DESC
		LIMIT
			$start_index, $rows_per_page
	";

	$rs = exec_query($sql, $query, array());

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'TICKETS_LIST'	=> '',
				'SCROLL_PREV'	=> '',
				'SCROLL_NEXT'	=> ''
			)
		);

		set_page_message(tr('You have no support tickets.'));
	} else {
		$prev_si = $start_index - $rows_per_page;

		if ($start_index == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_PREV_GRAY'	=> '',
					'PREV_PSI'			=> $prev_si
				)
			);
		}

		$next_si = $start_index + $rows_per_page;

		if ($next_si + 1 > $records_count) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_NEXT_GRAY'	=> '',
					'NEXT_PSI'	=> $next_si
				)
			);
		}
		global $i;

		while (!$rs->EOF) {
			$ticket_id		= $rs->fields['ticket_id'];
			$from			= get_ticket_from($sql, $ticket_id);
			$to				= get_ticket_to($sql, $ticket_id, $user_id);
			$date			= ticketGetLastDate($sql, $ticket_id);
			$ticket_urgency = $rs->fields['ticket_urgency'];

			$ticket_status = $rs->fields['ticket_status'];

			$tpl->assign(array('URGENCY' => get_ticket_urgency($ticket_urgency)));

			if ($ticket_status == 1 || $ticket_status == 2) {
				$tpl->assign(array('NEW' => tr("[New]")));
			} else if ($ticket_status == 4 || $ticket_status == 5) {
				$tpl->assign(array('NEW' => tr("[Re]")));
			} else {
				$tpl->assign(array('NEW' => " "));
			}

			$tpl->assign(
				array(
					'ID'		=> $ticket_id,
					'FROM'		=> htmlspecialchars($from),
					'TO'		=> htmlspecialchars($to),
					'LAST_DATE'	=> $date,
					'SUBJECT'	=> htmlspecialchars($rs->fields['ticket_subject']),
					'SUBJECT2'	=> addslashes(clean_html($rs->fields['ticket_subject'])),
					'MESSAGE'	=> htmlspecialchars($rs->fields['ticket_message']),
					'CONTENT'	=> ($i % 2 == 0) ? 'content' : 'content2'
				)
			);

			$tpl->parse('TICKETS_ITEM', '.tickets_item');
			$rs->MoveNext();
			$i++;
		}
	}
}

function get_ticket_from(&$sql, $ticket_id) {
	$query = "
		SELECT
			`ticket_from`,
			`ticket_to`,
			`ticket_status`,
			`ticket_reply`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
	";

	$rs = exec_query($sql, $query, array($ticket_id));
	$ticket_from = $rs->fields['ticket_from'];
	$ticket_to = $rs->fields['ticket_to'];
	$ticket_status = $rs->fields['ticket_status'];
	$ticket_reply = clean_html($rs->fields['ticket_reply']);

	$query = "
		SELECT
			`admin_name`,
			`admin_type`,
			`fname`,
			`lname`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";

	$rs = exec_query($sql, $query, array($ticket_from));
	$from_user_name = decode_idna($rs->fields['admin_name']);
	$admin_type = $rs->fields['admin_type'];
	$from_first_name = $rs->fields['fname'];
	$from_last_name = $rs->fields['lname'];

	$from_name = $from_first_name . " " . $from_last_name . " (" . $from_user_name . ")";

	return $from_name;
}

function get_ticket_to(&$sql, $ticket_id, $user_id) {
	$query = "
		SELECT
			`ticket_from`,
			`ticket_to`,
			`ticket_status`,
			`ticket_reply`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
	";

	$rs = exec_query($sql, $query, array($ticket_id));
	$ticket_from = $rs->fields['ticket_from'];
	$ticket_to = $rs->fields['ticket_to'];
	$ticket_status = $rs->fields['ticket_status'];
	$ticket_reply = clean_html($rs->fields['ticket_reply']);

	$query = "
		SELECT
			`admin_id`,
			`admin_name`,
			`admin_type`,
			`fname`,
			`lname`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";

	$rs = exec_query($sql, $query, array($ticket_to));
	$to_user_name = decode_idna($rs->fields['admin_name']);
	$admin_type = $rs->fields['admin_type'];
	$to_first_name = $rs->fields['fname'];
	$to_last_name = $rs->fields['lname'];

	if ($rs->fields['admin_id'] == $user_id) {
		$to_name = "<b>". $to_first_name . " " . $to_last_name . " (" . $to_user_name . ")</b>";
	} else {
		$to_name = $to_first_name . " " . $to_last_name . " (" . $to_user_name . ")";
	}

	return $to_name;
}

// common page data.

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_ENABLE_AUTORESPOND_PAGE_TITLE'	=> tr('ispCP - Client/Enable Mail Autoresponder'),
		'THEME_COLOR_PATH'							=> "../themes/$theme_color",
		'THEME_CHARSET'								=> tr('encoding'),
		'ISP_LOGO'									=> get_logo($_SESSION['user_id'])
	)
);

// dynamic page data.

gen_tickets_list($tpl, $sql, $_SESSION['user_id']);

// static page messages.

gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_ticket_system.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_ticket_system.tpl');

$tpl->assign(
	array(
		'TR_SUPPORT_SYSTEM'	=> tr('Support system'),
		'TR_SUPPORT_TICKETS'=> tr('Support tickets'),
		'TR_TICKET_FROM'	=> tr('From'),
		'TR_TICKET_TO'		=> tr('To'),
		'TR_STATUS'			=> tr('Status'),
		'TR_NEW'			=> ' ',
		'TR_ACTION'			=> tr('Action'),
		'TR_URGENCY'		=> tr('Priority'),
		'TR_SUBJECT'		=> tr('Subject'),
		'TR_LAST_DATA'		=> tr('Last reply'),
		'TR_DELETE_ALL'		=> tr('Delete all'),
		'TR_OPEN_TICKETS'	=> tr('Open tickets'),
		'TR_CLOSED_TICKETS'	=> tr('Closed tickets'),
		'TR_DELETE'			=> tr('Delete'),
		'TR_MESSAGE_DELETE'	=> tr('Are you sure you want to delete %s?', true, '%s') ,
		)
	);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
