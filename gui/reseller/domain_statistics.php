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
$tpl->define_dynamic('page', Config::get('RESELLER_TEMPLATE_PATH') . '/domain_statistics.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('month_list', 'page');
$tpl->define_dynamic('year_list', 'page');
$tpl->define_dynamic('traffic_table', 'page');
$tpl->define_dynamic('traffic_table_item', 'traffic_table');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_ADMIN_DOMAIN_STATISTICS_PAGE_TITLE' => tr('ispCP - Domain Statistics Data'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

if (isset($_POST['domain_id'])) {
	$domain_id = $_POST['domain_id'];
} else if (isset($_GET['domain_id'])) {
	$domain_id = $_GET['domain_id'];
}

if (isset($_POST['month']) && isset($_POST['year'])) {
	$year = intval($_POST['year']);
	$month = intval($_POST['month']);
} else if (isset($_GET['month']) && isset($_GET['year'])) {
	$month = intval($_GET['month']);
	$year = intval($_GET['year']);
} else {
	$month = date("m");
	$year = date("Y");
}

if (!is_numeric($domain_id) || !is_numeric($month) || !is_numeric($year)) {
	header("Location: reseller_statistics.php");
	die();
}

function get_domain_trafic($from, $to, $domain_id) {

	$sql = Database::getInstance();
	$reseller_id = $_SESSION['user_id'];
	$query = <<<SQL_QUERY
		SELECT
			domain_id
		FROM
			domain
		WHERE
			domain_id = ? AND domain_created_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id, $reseller_id));
	if ($rs->RecordCount() == 0) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
		header('Location: user_statistics.php');
		die();
	}

	$query = <<<SQL_QUERY
		SELECT
			IFNULL(sum(dtraff_web), 0) AS web_dr,
			IFNULL(sum(dtraff_ftp), 0) AS ftp_dr,
			IFNULL(sum(dtraff_mail), 0) AS mail_dr,
			IFNULL(sum(dtraff_pop), 0) AS pop_dr
		FROM
			domain_traffic
		WHERE
			domain_id = ? AND dtraff_time >= ? AND dtraff_time <= ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($domain_id, $from, $to));

	if ($rs->RecordCount() == 0) {
		return array(0, 0, 0, 0);
	} else {
		return array(
			$rs->fields['web_dr'],
			$rs->fields['ftp_dr'],
			$rs->fields['pop_dr'],
			$rs->fields['mail_dr']
		);
	}
}

function generate_page (&$tpl, $domain_id) {
	$sql = Database::getInstance();
	global $month, $year;
	global $web_trf, $ftp_trf, $smtp_trf, $pop_trf,
	$sum_web, $sum_ftp, $sum_mail, $sum_pop;

	$fdofmnth = mktime(0, 0, 0, $month, 1, $year);
	$ldofmnth = mktime(1, 0, 0, $month + 1, 0, $year);

	if ($month == date('m') && $year == date('Y')) {
		$curday = date('j');
	} else {
		$tmp = mktime(1, 0, 0, $month + 1, 0, $year);
		$curday = date('j', $tmp);
	}

	$curtimestamp = time();
	$firsttimestamp = mktime(0, 0, 0, $month, 1, $year);

	$all[0] = 0;
	$all[1] = 0;
	$all[2] = 0;
	$all[3] = 0;
	$all[4] = 0;
	$all[5] = 0;
	$all[6] = 0;
	$all[7] = 0;

	$counter = 0;
	for ($i = 1; $i <= $curday; $i++) {
		$ftm = mktime(0, 0, 0, $month, $i, $year);
		$ltm = mktime(23, 59, 59, $month, $i, $year);

		$query = <<<SQL_QUERY
			SELECT
				dtraff_web, dtraff_ftp, dtraff_mail, dtraff_pop, dtraff_time
			FROM
				domain_traffic
			WHERE
				domain_id = ? AND dtraff_time >= ? AND dtraff_time <= ?
SQL_QUERY;
		$rs = exec_query($sql, $query, array($domain_id, $ftm, $ltm));

		$has_data = false;
		list($web_trf, $ftp_trf, $pop_trf, $smtp_trf) = get_domain_trafic($ftm, $ltm, $domain_id);

		$date_formt = Config::get('DATE_FORMAT');
		if ($web_trf == 0 && $ftp_trf == 0 && $smtp_trf == 0 && $pop_trf == 0) {
			$tpl->assign(
				array(
					'MONTH' => $month,
					'YEAR' => $year,
					'DOMAIN_ID' => $domain_id,
					'DATE' => date($date_formt, strtotime($year . "-" . $month . "-" . $i)),
					'WEB_TRAFFIC' => 0,
					'FTP_TRAFFIC' => 0,
					'SMTP_TRAFFIC' => 0,
					'POP3_TRAFFIC' => 0,
					'ALL_TRAFFIC' => 0
				)
			);
		} else {
			$tpl->assign('ITEM_CLASS', ($counter % 2 == 0) ? 'content' : 'content2');

			$sum_web += $web_trf;
			$sum_ftp += $ftp_trf;
			$sum_mail += $smtp_trf;
			$sum_pop += $pop_trf;

			$tpl->assign(
				array(
					'DATE' => date($date_formt, strtotime($year . "-" . $month . "-" . $i)),
					'WEB_TRAFFIC' => sizeit($web_trf),
					'FTP_TRAFFIC' => sizeit($ftp_trf),
					'SMTP_TRAFFIC' => sizeit($smtp_trf),
					'POP3_TRAFFIC' => sizeit($pop_trf),
					'ALL_TRAFFIC' => sizeit($web_trf + $ftp_trf + $smtp_trf + $pop_trf)
				)
			);
			$tpl->parse('TRAFFIC_TABLE_ITEM', '.traffic_table_item');
			$counter++;
		}

		$tpl->assign(
			array(
				'MONTH' => $month,
				'YEAR' => $year,
				'DOMAIN_ID' => $domain_id,
				'ALL_WEB_TRAFFIC' => sizeit($sum_web),
				'ALL_FTP_TRAFFIC' => sizeit($sum_ftp),
				'ALL_SMTP_TRAFFIC' => sizeit($sum_mail),
				'ALL_POP3_TRAFFIC' => sizeit($sum_pop),
				'ALL_ALL_TRAFFIC' => sizeit($sum_web + $sum_ftp + $sum_mail + $sum_pop)
			)
		);

		$tpl->parse('TRAFFIC_TABLE', 'traffic_table');
	}
}

/*
 *
 * static page messages.
 *
 */

gen_reseller_mainmenu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/main_menu_statistics.tpl');
gen_reseller_menu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/menu_statistics.tpl');

gen_logged_from($tpl);

$tpl->assign(
	array(
		'TR_DOMAIN_STATISTICS' => tr('Domain statistics'),
		'TR_MONTH' => tr('Month'),
		'TR_YEAR' => tr('Year'),
		'TR_SHOW' => tr('Show'),
		'TR_WEB_TRAFFIC' => tr('Web traffic'),
		'TR_FTP_TRAFFIC' => tr('FTP traffic'),
		'TR_SMTP_TRAFFIC' => tr('SMTP traffic'),
		'TR_POP3_TRAFFIC' => tr('POP3/IMAP traffic'),
		'TR_ALL_TRAFFIC' => tr('All traffic'),
		'TR_ALL' => tr('All'),
		'TR_DAY' => tr('Day')
	)
);

gen_select_lists($tpl, $month, $year);
generate_page($tpl, $domain_id);
gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>