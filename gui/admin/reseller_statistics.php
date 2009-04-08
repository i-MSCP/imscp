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

$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/reseller_statistics.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('traffic_table', 'page');
$tpl->define_dynamic('month_list', 'traffic_table');
$tpl->define_dynamic('year_list', 'traffic_table');
$tpl->define_dynamic('reseller_entry', 'traffic_table');
$tpl->define_dynamic('scroll_prev_gray', 'page');
$tpl->define_dynamic('scroll_prev', 'page');
$tpl->define_dynamic('scroll_next_gray', 'page');
$tpl->define_dynamic('scroll_next', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_ADMIN_RESELLER_STATISTICS_PAGE_TITLE' => tr('ispCP - Reseller statistics'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

$year = 0;
$month = 0;

if (isset($_POST['month']) && isset($_POST['year'])) {
	$year = $_POST['year'];

	$month = $_POST['month'];
} else if (isset($_GET['month']) && isset($_GET['year'])) {
	$month = $_GET['month'];

	$year = $_GET['year'];
}

function generate_page(&$tpl) {
	global $month, $year;
	$sql = Database::getInstance();

	$start_index = 0;

	$rows_per_page = Config::get('DOMAIN_ROWS_PER_PAGE');

	if (isset($_GET['psi']) && is_numeric($_GET['psi'])) {
		$start_index = $_GET['psi'];
	} else if (isset($_POST['psi']) && is_numeric($_GET['psi'])) {
		$start_index = $_POST['psi'];
	}

	$tpl->assign(
		array(
			'POST_PREV_PSI' => $start_index
		)
	);

	// count query
	$count_query = <<<SQL_QUERY
		SELECT
			COUNT(`admin_id`) AS cnt
		FROM
			`admin`
		WHERE
			`admin_type` = 'reseller'
SQL_QUERY;

	$query = <<<SQL_QUERY
		SELECT
			`admin_id`, `admin_name`
		FROM
			`admin`
		WHERE
			`admin_type` = 'reseller'
		ORDER BY
			`admin_name` DESC
		LIMIT
			$start_index, $rows_per_page
SQL_QUERY;

	$rs = exec_query($sql, $count_query, array());
	$records_count = $rs->fields['cnt'];

	$rs = exec_query($sql, $query, array());

	if ($rs->RowCount() == 0) {

		$tpl->assign(
			array(
				'TRAFFIC_TABLE' => '',
				'SCROLL_PREV' => '',
				'SCROLL_NEXT' => ''
			)
		);

		set_page_message(tr('Not found reseller(s) in your system!'));
		return;
	} else {
		$prev_si = $start_index - $rows_per_page;

		if ($start_index == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_PREV_GRAY' => '',
					'PREV_PSI' => $prev_si
				)
			);
		}

		$next_si = $start_index + $rows_per_page;

		if ($next_si + 1 > $records_count) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_NEXT_GRAY' => '',
					'NEXT_PSI' => $next_si
				)
			);
		}

		$tpl->assign(
			array(
				'PAGE_MESSAGE' => ''
			)
		);

		gen_select_lists($tpl, @$month, @$year);

		$row = 1;

		while (!$rs->EOF) {
			generate_reseller_entry($tpl, $rs->fields['admin_id'], $rs->fields['admin_name'], $row++);

			$rs->MoveNext();
		}
	}

	$tpl->parse('TRAFFIC_TABLE', 'traffic_table');
}

function generate_reseller_entry(&$tpl, $reseller_id, $reseller_name, $row) {
	global $crnt_month, $crnt_year;

	list($rdmn_current, $rdmn_max,
		$rsub_current, $rsub_max,
		$rals_current, $rals_max,
		$rmail_current, $rmail_max,
		$rftp_current, $rftp_max,
		$rsql_db_current, $rsql_db_max,
		$rsql_user_current, $rsql_user_max,
		$rtraff_current, $rtraff_max,
		$rdisk_current, $rdisk_max
	) = generate_reseller_props($reseller_id);

	list($udmn_current, $udmn_max, $udmn_uf,
		$usub_current, $usub_max, $usub_uf,
		$uals_current, $uals_max, $uals_uf,
		$umail_current, $umail_max, $umail_uf,
		$uftp_current, $uftp_max, $uftp_uf,
		$usql_db_current, $usql_db_max, $usql_db_uf,
		$usql_user_current, $usql_user_max, $usql_user_uf,
		$utraff_current, $utraff_max, $utraff_uf,
		$udisk_current, $udisk_max, $udisk_uf
	) = generate_reseller_users_props($reseller_id);

	$rtraff_max = $rtraff_max * 1024 * 1024;

	$rtraff_current = $rtraff_current * 1024 * 1024;

	$rdisk_max = $rdisk_max * 1024 * 1024;

	$rdisk_current = $rdisk_current * 1024 * 1024;

	$utraff_max = $utraff_max * 1024 * 1024;

	$udisk_max = $udisk_max * 1024 * 1024;

	$traff_percent = calc_bar_value($utraff_current, $rtraff_max , 400);

	list($traff_percent, $traff_red, $traff_green) = make_usage_vals($utraff_current, $rtraff_max);

	list($disk_percent, $disk_red, $disk_green) = make_usage_vals($udisk_current, $rdisk_max);

	$traff_show_percent = $traff_percent;

	$disk_show_percent = $disk_percent;

	if ($traff_percent > 100) {
		$traff_percent = 100;
	}

	if ($disk_percent > 100) {
		$disk_percent = 100;
	}

	$tpl->assign(
		array('ITEM_CLASS' => ($row % 2 == 0) ? 'content' : 'content2')
	);

	$tpl->assign(
		array(
			'RESELLER_NAME' => $reseller_name,
			'RESELLER_ID' => $reseller_id,
			'MONTH' => $crnt_month,
			'YEAR' => $crnt_year,

			'TRAFF_SHOW_PERCENT' => $traff_show_percent,
			'TRAFF_PERCENT' => $traff_percent,

			'TRAFF_MSG' => ($rtraff_max)
				? tr('%1$s / %2$s <br/>of<br/> <b>%3$s</b>', sizeit($utraff_current), sizeit($rtraff_current), sizeit($rtraff_max))
				: tr('%1$s / %2$s <br/>of<br/> <b>unlimited</b>', sizeit($utraff_current), sizeit($rtraff_current)),

			'DISK_SHOW_PERCENT' => $disk_show_percent,
			'DISK_PERCENT' => $disk_percent,

			'DISK_MSG' => ($rdisk_max)
				? tr('%1$s / %2$s <br/>of<br/> <b>%3$s</b>', sizeit($udisk_current), sizeit($rdisk_current), sizeit($rdisk_max))
				: tr('%1$s / %2$s <br/>of<br/> <b>unlimited</b>', sizeit($udisk_current), sizeit($rdisk_current)),

			'DMN_MSG' => ($rdmn_max)
				? tr('%1$d / %2$d <br/>of<br/> <b>%3$d</b>', $udmn_current, $rdmn_current, $rdmn_max)
				: tr('%1$d / %2$d <br/>of<br/> <b>unlimited</b>', $udmn_current, $rdmn_current),

			'SUB_MSG' => ($rsub_max)
				? tr('%1$d / %2$d <br/>of<br/> <b>%3$d</b>', $usub_current, $rsub_current, $rsub_max)
				: tr('%1$d / %2$d <br/>of<br/> <b>unlimited</b>', $usub_current, $rsub_current),

			'ALS_MSG' => ($rals_max)
				? tr('%1$d / %2$d <br/>of<br/> <b>%3$d</b>', $uals_current, $rals_current, $rals_max)
				: tr('%1$d / %2$d <br/>of<br/> <b>unlimited</b>', $uals_current, $rals_current),

			'MAIL_MSG' => ($rmail_max)
				? tr('%1$d / %2$d <br/>of<br/> <b>%3$d</b>', $umail_current, $rmail_current, $rmail_max)
				: tr('%1$d / %2$d <br/>of<br/> <b>unlimited</b>', $umail_current, $rmail_current),

			'FTP_MSG' => ($rftp_max)
				? tr('%1$d / %2$d <br/>of<br/> <b>%3$d</b>', $uftp_current, $rftp_current, $rftp_max)
				: tr('%1$d / %2$d <br/>of<br/> <b>unlimited</b>', $uftp_current, $rftp_current),

			'SQL_DB_MSG' => ($rsql_db_max)
				? tr('%1$d / %2$d <br/>of<br/> <b>%3$d</b>', $usql_db_current, $rsql_db_current, $rsql_db_max)
				: tr('%1$d / %2$d <br/>of<br/> <b>unlimited</b>', $usql_db_current, $rsql_db_current),

			'SQL_USER_MSG' => ($rsql_user_max)
				? tr('%1$d / %2$d <br/>of<br/> <b>%3$d</b>', $usql_user_current, $rsql_user_current, $rsql_user_max)
				: tr('%1$d / %2$d <br/>of<br/> <b>unlimited</b>', $usql_user_current, $rsql_user_current)
		)
	);

	$tpl->parse('RESELLER_ENTRY', '.reseller_entry');
}

/*
 *
 * static page messages.
 *
 */

$crnt_month = '';
$crnt_year = '';

gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_statistics.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_statistics.tpl');

generate_page ($tpl);

$tpl->assign(
	array(
		'TR_RESELLER_STATISTICS' => tr('Reseller statistics table'),
		'TR_MONTH' => tr('Month'),
		'TR_YEAR' => tr('Year'),
		'TR_SHOW' => tr('Show'),
		'TR_RESELLER_NAME' => tr('Reseller name'),
		'TR_TRAFF' => tr('Traffic'),
		'TR_DISK' => tr('Disk'),
		'TR_DOMAIN' => tr('Domain'),
		'TR_SUBDOMAIN' => tr('Subdomain'),
		'TR_ALIAS' => tr('Alias'),
		'TR_MAIL' => tr('Mail'),
		'TR_FTP' => tr('FTP'),
		'TR_SQL_DB' => tr('SQL database'),
		'TR_SQL_USER' => tr('SQL user'),
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>