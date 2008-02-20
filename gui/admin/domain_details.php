<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2007 by ispCP | http://isp-control.net
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
$tpl->define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'] . '/domain_details.tpl');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('custom_buttons', 'page');

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl->assign(
		array(
			'TR_DETAILS_DOMAIN_PAGE_TITLE' => tr('ispCP - Domain/Details'),
			'THEME_COLOR_PATH' => "../themes/$theme_color",
			'THEME_CHARSET' => tr('encoding'),
	
			'ISP_LOGO' => get_logo($_SESSION['user_id']),
			)
	);

/*
 *
 * static page messages.
 *
 */

$tpl->assign(
		array(
			'TR_DOMAIN_DETAILS' => tr('Domain details'),
			'TR_DOMAIN_NAME' => tr('Domain name'),
			'TR_DOMAIN_IP' => tr('Domain IP'),
			'TR_STATUS' => tr('Status'),
			'TR_PHP_SUPP' => tr('PHP support'),
			'TR_CGI_SUPP' => tr('CGI support'),
			'TR_MYSQL_SUPP' => tr('MySQL support'),
			'TR_TRAFFIC' => tr('Traffic in MB'),
			'TR_DISK' => tr('Disk in MB'),
			'TR_FEATURE' => tr('Feature'),
			'TR_USED' => tr('Used'),
			'TR_LIMIT' => tr('Limit'),
			'TR_MAIL_ACCOUNTS' => tr('Mail accounts'),
			'TR_FTP_ACCOUNTS' => tr('FTP accounts'),
			'TR_SQL_DB_ACCOUNTS' => tr('SQL databases'),
			'TR_SQL_USER_ACCOUNTS' => tr('SQL users'),
			'TR_SUBDOM_ACCOUNTS' => tr('Subdomains'),
			'TR_DOMALIAS_ACCOUNTS' => tr('Domain aliases'),
			'TR_UPDATE_DATA' => tr('Submit changes'),
			'TR_BACK' => tr('Back'),
			)
	);

gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'] . '/main_menu_manage_users.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'] . '/menu_manage_users.tpl');

gen_page_message($tpl);
// Get user id that come for manage domain
if (!isset($_GET['domain_id'])) {
	header('Location: manage_users.php');
	die();
}

$editid = $_GET['domain_id'];
gen_detaildom_page($tpl, $_SESSION['user_id'], $editid);

$tpl->parse('PAGE', 'page');

$tpl->prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();

// Begin function block

function gen_detaildom_page(&$tpl, $user_id, $domain_id) {
	global $sql;
	// Get domain data
	$query = <<<SQL_QUERY
      select
          *,
          IFNULL(domain_disk_usage, 0) as domain_disk_usage
      from
          domain
      where
          domain_id = ?;
SQL_QUERY;

	$res = exec_query($sql, $query, array($domain_id));
	$data = $res->FetchRow();

	if ($res->RecordCount() <= 0) {
		header('Location: manage_users.php');
		die();
	}
	// Get admin data
	$query = "select admin_name from admin where admin_id = ?";
	$res1 = exec_query($sql, $query, array($data['domain_admin_id']));
	$data1 = $res1->FetchRow();
	if ($res1->RecordCount() <= 0) {
		header('Location: manage_users.php');
		die();
	}
	// Get IP-info
	$query = "select * from server_ips where ip_id = ?";
	$ipres = exec_query($sql, $query, array($data['domain_ip_id']));
	$ipdat = $ipres->FetchRow();
	// Get staus name
	$dstatus = $data['domain_status'];
	global $cfg;

	if ($dstatus == $cfg['ITEM_OK_STATUS'] || $dstatus == $cfg['ITEM_DISABLED_STATUS'] || $dstatus == $cfg['ITEM_DELETE_STATUS'] || $dstatus == $cfg['ITEM_ADD_STATUS'] || $dstatus == $cfg['ITEM_RESTORE_STATUS'] || $dstatus == $cfg['ITEM_CHANGE_STATUS'] || $dstatus == $cfg['ITEM_TOENABLE_STATUS'] || $dstatus == $cfg['ITEM_TODISABLED_STATUS']) {
		$dstatus = translate_dmn_status($data['domain_status']);
	} else {
		$dstatus = "<b><font size=3 color=red>" . $data['domain_status'] . "</font></b>";
	}

	// Traffic diagram
	$fdofmnth = mktime(0, 0, 0, date("m"), 1, date("Y"));
	$ldofmnth = mktime(1, 0, 0, date("m") + 1, 0, date("Y"));
	$query = <<<SQL_QUERY
        select
            IFNULL(sum(dtraff_web),0) as dtraff_web,
            IFNULL(sum(dtraff_ftp), 0) as dtraff_ftp,
            IFNULL(sum(dtraff_mail), 0) as dtraff_mail,
            IFNULL(sum(dtraff_pop),0) as dtraff_pop
        from
            domain_traffic
        where
            domain_id = ?
          and
            dtraff_time > ?
          and
            dtraff_time < ?
SQL_QUERY;

	$res7 = exec_query($sql, $query, array($data['domain_id'], $fdofmnth, $ldofmnth));
	$dtraff = $res7->FetchRow();
	$sumtraff = $dtraff['dtraff_web'] + $dtraff['dtraff_ftp'] + $dtraff['dtraff_mail'] + $dtraff['dtraff_pop'];
	$dtraffmb = sprintf("%.1f", ($sumtraff / 1024) / 1024);

	$month = date("m");
	$year = date("Y");

	$query = "select * from server_ips where ip_id=?";
	$res8 = exec_query($sql, $query, array($data['domain_ip_id']));
	$ipdat = $res8->FetchRow();

	$domain_traffic_limit = $data['domain_traffic_limit'];
	$domain_all_traffic = $sumtraff; //$dtraff['traffic'];

	$traff = ($domain_all_traffic / 1024) / 1024;
	$mtraff = sprintf("%.2f", $traff);

	if ($domain_traffic_limit == 0) {
		$pr = 0;
	} else {
		$pr = ($traff / $domain_traffic_limit) * 100;
		$pr = sprintf("%.2f", $pr);
	}

	$indx = (int)$pr;

	list($traffic_percent, $indx, $a) = make_usage_vals($domain_all_traffic, $domain_traffic_limit * 1024 * 1024);
	// Get disk status
	$domdu = $data['domain_disk_usage'];
	$domdl = $data['domain_disk_limit'];

	$tmp = ($domdu / 1024) / 1024;

	if ($domdu == 0) {
		$dpr = 0;
	} else if ($domdl == 0) {
		$dpr = 0;
	} else {
		$dpr = ($tmp / $domdl) * 100;
		$dpr = sprintf("%.2f", $dpr);
	}

	$dindx = (int) $dpr;
	$domduh = sizeit($domdu);

	list($disk_percent, $dindx, $b) = make_usage_vals($domdu, $domdl * 1024 * 1024);
	// Get current mail count
	$res6 = exec_query($sql, "SELECT COUNT(mail_id) AS mcnt FROM mail_users WHERE domain_id = ? AND mail_type NOT RLIKE '_catchall'", array($data['domain_id']));
	$dat3 = $res6->FetchRow();
	$mail_limit = translate_limit_value($data['domain_mailacc_limit']);
	// FTP stat
	$query = "select gid from ftp_group where groupname = ?";
	$res4 = exec_query($sql, $query, array($data['domain_name']));
	$ftp_gnum = $res4->RowCount();
	if ($ftp_gnum == 0) {
		$used_ftp_acc = 0;
	} else {
		$dat1 = $res4->FetchRow();
		$query = "select count(uid) as ftp_cnt from ftp_users where gid=?";
		$res5 = exec_query($sql, $query, array($dat1['gid']));
		$dat2 = $res5->FetchRow();

		$used_ftp_acc = $dat2['ftp_cnt'];
	}
	$ftp_limit = translate_limit_value($data['domain_ftpacc_limit']);
	// Get sql database count
	$query = "select count(sqld_id) as dnum from sql_database where domain_id=?";
	$res = exec_query($sql, $query, array($data['domain_id']));
	$dat5 = $res->FetchRow();
	$sql_db = translate_limit_value($data['domain_sqld_limit']);
	// Get sql users count
	$query = "select count(u.sqlu_id) as ucnt from sql_user u,sql_database d where u.sqld_id=d.sqld_id and d.domain_id=?";
	$res = exec_query($sql, $query, array($data['domain_id']));
	$dat6 = $res->FetchRow();
	$sql_users = translate_limit_value($data['domain_sqlu_limit']);
	// Get sub domain
	$query = "select count(subdomain_id) as sub_num from subdomain where domain_id=?";
	$res1 = exec_query($sql, $query, array($data['domain_id']));
	$sub_num_data = $res1->FetchRow();
	$sub_dom = translate_limit_value($data['domain_subd_limit']);
	// Get domain aliases
	$query = "select count(alias_id) as alias_num from domain_aliasses where domain_id=?";
	$res1 = exec_query($sql, $query, array($data['domain_id']));
	$alias_num_data = $res1->FetchRow();

	$dom_alias = translate_limit_value($data['domain_alias_limit']);
	// Fill in the fileds
	$tpl->assign(
			array(
				'DOMAIN_ID' => $data['domain_id'],
				'VL_DOMAIN_NAME' => decode_idna($data['domain_name']),
				'VL_DOMAIN_IP' => $ipdat['ip_number'] . ' (' . $ipdat['ip_alias'] . ')',
				'VL_STATUS' => $dstatus,

				'VL_PHP_SUPP' => ($data['domain_php'] == 'yes')?
				                tr('Enabled') : tr('Disabled'),
				'VL_CGI_SUPP' => ($data['domain_cgi'] == 'yes')?
				                tr('Enabled') : tr('Disabled'),
				'VL_MYSQL_SUPP' => ($data['domain_sqld_limit'] >= 0)?
				                tr('Enabled') : tr('Disabled'),

				'VL_TRAFFIC_PERCENT' => $traffic_percent,
				'VL_TRAFFIC_USED' => sizeit($domain_all_traffic),
				'VL_TRAFFIC_LIMIT' => sizeit($domain_traffic_limit, 'MB'),
				'VL_DISK_PERCENT' => $disk_percent,
				'VL_DISK_USED' => $domduh,
				'VL_DISK_LIMIT' => sizeit($data['domain_disk_limit'], 'MB'),
				'VL_MAIL_ACCOUNTS_USED' => $dat3['mcnt'],
				'VL_MAIL_ACCOUNTS_LIIT' => $mail_limit,
				'VL_FTP_ACCOUNTS_USED' => $used_ftp_acc,
				'VL_FTP_ACCOUNTS_LIIT' => $ftp_limit,
				'VL_SQL_DB_ACCOUNTS_USED' => $dat5['dnum'],
				'VL_SQL_DB_ACCOUNTS_LIIT' => $sql_db,
				'VL_SQL_USER_ACCOUNTS_USED' => $dat6['ucnt'],
				'VL_SQL_USER_ACCOUNTS_LIIT' => $sql_users,
				'VL_SUBDOM_ACCOUNTS_USED' => $sub_num_data['sub_num'],
				'VL_SUBDOM_ACCOUNTS_LIIT' => $sub_dom,
				'VL_DOMALIAS_ACCOUNTS_USED' => $alias_num_data['alias_num'],
				'VL_DOMALIAS_ACCOUNTS_LIIT' => $dom_alias
				)
		);
} //End of load_user_data();

?>