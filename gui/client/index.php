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

check_login(__FILE__, Config::get('PREVENT_EXTERNAL_LOGIN_CLIENT'));

$tpl = new pTemplate();

$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/index.tpl');
$tpl->define_dynamic('def_language', 'page');
$tpl->define_dynamic('def_layout', 'page');
$tpl->define_dynamic('no_messages', 'page');
$tpl->define_dynamic('msg_entry', 'page');
$tpl->define_dynamic('sql_support', 'page');
$tpl->define_dynamic('t_sql1_support', 'page');
$tpl->define_dynamic('t_sql2_support', 'page');
$tpl->define_dynamic('t_php_support', 'page');
$tpl->define_dynamic('t_cgi_support', 'page');
$tpl->define_dynamic('t_dns_support', 'page');
$tpl->define_dynamic('t_backup_support', 'page');
$tpl->define_dynamic('t_sdm_support', 'page');
$tpl->define_dynamic('t_alias_support', 'page');
$tpl->define_dynamic('t_mails_support', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('traff_warn', 'page');
$tpl->define_dynamic('disk_warn', 'page');
$tpl->define_dynamic('dmn_mngmnt', 'page');


function gen_num_limit_msg($num, $limit) {
	if ($limit == -1) {
		return tr('disabled');
	}
	if ($limit == 0) {
		return $num . '&nbsp;/&nbsp;' . tr('unlimited');
	}
	return $num . '&nbsp;/&nbsp;' . $limit;
}

function gen_system_message(&$tpl, &$sql) {
	$user_id = $_SESSION['user_id'];

	$query = "
		SELECT
			COUNT(`ticket_id`) AS cnum
		FROM
			`tickets`
		WHERE
			(`ticket_to` = ? OR `ticket_from` = ?)
		AND
			`ticket_status` IN ('1', '2')
		AND
			`ticket_reply` = 0
	";

	$rs = exec_query($sql, $query, array($user_id, $user_id));

	$num_question = $rs->fields('cnum');

	if ($num_question == 0) {
		$tpl->assign(array('MSG_ENTRY' => ''));
	} else {
		$tpl->assign(
			array(
				'TR_NEW_MSGS' => tr('You have <b>%d</b> new answer to your support questions', $num_question),
				'TR_VIEW' => tr('View')
			)
		);

		$tpl->parse('MSG_ENTRY', 'msg_entry');
	}
}

function gen_traff_usage(&$tpl, $usage, $max_usage, $bars_max) {
	list($percent, $bars) = calc_bars($usage, $max_usage, $bars_max);
	if ($max_usage != 0) {
		$traffic_usage_data = tr('%1$d%% [%2$s of %3$s]', $percent, sizeit($usage), sizeit($max_usage));
	} else {
		$traffic_usage_data = tr('%1$d%% [%2$s of unlimited]', $percent, sizeit($usage));
	}

	$tpl->assign(
		array(
			'TRAFFIC_USAGE_DATA' => $traffic_usage_data,
			'TRAFFIC_BARS'	   => $bars,
			'TRAFFIC_PERCENT'	=> $percent,
		)
	);

	if ($max_usage != 0 && $usage > $max_usage) {
		$tpl->assign('TR_TRAFFIC_WARNING', tr('You are exceeding your traffic limit!'));
	} else {
		$tpl->assign('TRAFF_WARN', '');
	}
}

function gen_disk_usage(&$tpl, $usage, $max_usage, $bars_max) {
	list($percent, $bars) = calc_bars($usage, $max_usage, $bars_max);

	if ($max_usage != 0) {
		$traffic_usage_data = tr('%1$s%% [%2$s of %3$s]', $percent, sizeit($usage), sizeit($max_usage));
	} else {
		$traffic_usage_data = tr('%1$s%% [%2$s of unlimited]', $percent, sizeit($usage));
	}

	$tpl->assign(
		array(
			'DISK_USAGE_DATA' => $traffic_usage_data,
			'DISK_BARS'	   => $bars,
			'DISK_PERCENT'	=> $percent,
		)
	);
	if ($max_usage != 0 && $usage > $max_usage) {
		$tpl->assign('TR_DISK_WARNING', tr('You are exceeding your disk limit!'));
	} else {
		$tpl->assign('DISK_WARN', '');
	}
}

function check_user_permissions(&$tpl, $dmn_sqld_limit, $dmn_sqlu_limit, $dmn_php,
	$dmn_cgi,$backup, $dns, $dmn_subd_limit, $als_cnt, $dmn_mailacc_limit) {

	// check if mail accouts available are available for this user
	if ($dmn_mailacc_limit == -1) {
		$_SESSION['email_support'] = "no";
		$tpl->assign('T_MAILS_SUPPORT', '');
	} else {
		$tpl->parse('T_MAILS_SUPPORT', '.t_mails_support');
	}

	// check if alias are available for this user
	if ($als_cnt == -1) {
		$_SESSION['alias_support'] = "no";
		$tpl->assign('T_ALIAS_SUPPORT', '');
	} else {
		$tpl->parse('T_ALIAS_SUPPORT', '.t_alias_support');
	}

	// check if subdomains are available for this user
	if ($dmn_subd_limit == -1) {
		$_SESSION['subdomain_support'] = "no";
		$tpl->assign('T_SDM_SUPPORT', '');
	} else {
		$tpl->parse('T_SDM_SUPPORT', '.t_sdm_support');
	}

	// check if SQL Support is available for this user
	if ($dmn_sqld_limit == -1 || $dmn_sqlu_limit == -1) {
		$_SESSION['sql_support'] = "no";
		$tpl->assign('SQL_SUPPORT', '');
		$tpl->assign('T_SQL1_SUPPORT', '');
		$tpl->assign('T_SQL2_SUPPORT', '');
	} else {
		$tpl->parse('T_SQL1_SUPPORT', '.t_sql1_support');
		$tpl->parse('T_SQL2_SUPPORT', '.t_sql2_support');
	}

	// check if PHP Support is available for this user
	if ($dmn_php == 'no') {
		$tpl->assign('T_PHP_SUPPORT', '');
	} else {
		$tpl->assign( array('PHP_SUPPORT' => tr('yes')));
		$tpl->parse('T_PHP_SUPPORT', '.t_php_support');
	}

	// check if CGI Support is available for this user
	if ($dmn_cgi == 'no') {
		$tpl->assign('T_CGI_SUPPORT', '');
	} else {
		$tpl->assign( array('CGI_SUPPORT' => tr('yes')));
		$tpl->parse('T_CGI_SUPPORT', '.t_cgi_support');
	}

	// Check if Backup support is available for this user
	switch($backup){
	case "full":
		$tpl->assign( array('BACKUP_SUPPORT' => tr('Full')));
		break;
	case "sql":
		$tpl->assign( array('BACKUP_SUPPORT' => tr('SQL')));
		break;
	case "domain":
		$tpl->assign( array('BACKUP_SUPPORT' => tr('Domain')));
		break;
	default:
		$tpl->assign('T_BACKUP_SUPPORT', '');
	}
	if ($tpl->is_namespace('BACKUP_SUPPORT')) {
		$tpl->parse('T_BACKUP_SUPPORT', '.t_backup_support');
	}

	// Check if Manual DNS support is available for this user
	if ($dns == 'no') {
		$tpl->assign('T_DNS_SUPPORT', '');
	} else {
		$tpl->assign(
		array('DNS_SUPPORT' => tr('yes')));
		$tpl->parse('T_DNS_SUPPORT', '.t_dns_support');
	}

} // end check_user_permissions()

/**
 * Calculate the usege traffic/ return array (persent/value)
 */
function make_traff_usege($domain_id) {
	$sql = Database::getInstance();

	$res = exec_query($sql, "SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?", array($domain_id));
	$dom_id = $res->FetchRow();
	$domain_id = $dom_id['domain_id'];

	$res = exec_query($sql, "SELECT `domain_traffic_limit` FROM `domain` WHERE `domain_id` = ?", array($domain_id));
	$dat = $res->FetchRow();

	$fdofmnth = mktime(0, 0, 0, date("m"), 1, date("Y"));
	$ldofmnth = mktime(1, 0, 0, date("m") + 1, 0, date("Y"));
	$res = exec_query($sql,
		"SELECT IFNULL(SUM(`dtraff_web`) + SUM(`dtraff_ftp`) + SUM(`dtraff_mail`) + SUM(`dtraff_pop`), 0) "
		. "AS traffic FROM `domain_traffic` " . "WHERE `domain_id` = ? AND `dtraff_time` > ? AND `dtraff_time` < ?",
		array($domain_id, $fdofmnth, $ldofmnth));
	$data = $res->FetchRow();
	$traff = ($data['traffic'] / 1024) / 1024;
	$mtraff = sprintf("%.2f", $traff);
	if ($dat['domain_traffic_limit'] == 0) {
		$pr = 0;
	} else {
		$pr = ($traff / $dat['domain_traffic_limit']) * 100;
		$pr = sprintf("%.2f", $pr);
	}

	return array($pr, $traff);

} // End of make_traff_usege()

function gen_user_messages_label(&$tpl, &$sql, &$user_id) {
	$query = "
		SELECT
			COUNT(`ticket_id`) AS cnum
		FROM
			`tickets`
		WHERE
			`ticket_from` = ?
		AND
			`ticket_status` = '2'
	";

	$rs = exec_query($sql, $query, array($user_id));
	$num_question = $rs->fields('cnum');

	if ($num_question == 0) {
		$tpl->assign(
			array(
				'TR_NO_NEW_MESSAGES' => tr('You have no new support questions!'),
				'MSG_ENTRY' => ''
			)
		);
	} else {
		$tpl->assign(
			array(
				'NO_MESSAGES' => '',
				'TR_NEW_MSGS' => tr('You have <b>%d</b> new support questions', $num_question),
				'TR_VIEW' => tr('View')
			)
		);
		$tpl->parse('MSG_ENTRY', '.msg_entry');
	}
}

function gen_remain_time($dbtime){
        
        // needed for calculation
        $mi	= 60;
        $h	= $mi * $mi;
        $d	= $h * 24;
        $mo = $d * 30;
        $y	= $d * 365;
        
        // calculation of: years, month, days, hours, minutes, seconds
        $difftime = $dbtime - time();
        $years = floor($difftime / $y);
        $difftime = $difftime % $y;
        $month = floor($difftime / $mo);
        $difftime = $difftime % $mo;
        $days = floor($difftime / $d);
        $difftime = $difftime % $d;
        $hours = floor($difftime / $h);
        $difftime = $difftime % $h;
        $minutes = $difftime % $mi;
        $seconds = $difftime;
        
        // put into array and return
        return array($years, $month, $days, $hours, $minutes, $seconds);
}

/*
 *
 * page actions.
 *
 */

$theme_color = Config::get('USER_INITIAL_THEME');

if (isset($_POST['uaction']) && $_POST['uaction'] === 'save_layout') {
	$user_id = $_SESSION['user_id'];

	$user_layout = $_POST['def_layout'];

	$query = "
		UPDATE
			`user_gui_props`
		SET
			`layout` = ?
		WHERE
			`user_id` = ?
	";
	$rs = exec_query($sql, $query, array($user_layout, $user_id));
	$theme_color = $user_layout;
}

list(
		$dmn_id,
		$dmn_name,
		$dmn_gid,
		$dmn_uid,
		$dmn_created_id,
		$dmn_created,
		$dmn_expire,
		$dmn_last_modified,
		$dmn_mailacc_limit,
		$dmn_ftpacc_limit,
		$dmn_traff_limit,
		$dmn_sqld_limit,
		$dmn_sqlu_limit,
		$dmn_status,
		$dmn_als_limit,
		$dmn_subd_limit,
		$dmn_ip_id,
		$dmn_disk_limit,
		$dmn_disk_usage,
		$dmn_php,
		$dmn_cgi,
		$backup,
		$dns
	) = get_domain_default_props($sql, $_SESSION['user_id']);

list(
		$sub_cnt,
		$als_cnt,
		$mail_acc_cnt,
		$ftp_acc_cnt,
		$sqld_acc_cnt,
		$sqlu_acc_cnt
	) = get_domain_running_props_cnt($sql, $dmn_id);

$dtraff_pr = 0;
$dmn_traff_usege = 0;
$dmn_traff_limit = $dmn_traff_limit * 1024 * 1024;

list($dtraff_pr, $dmn_traff_usege) = make_traff_usege($_SESSION['user_id']);

$dmn_disk_limit = $dmn_disk_limit * 1024 * 1024;

gen_traff_usage($tpl, $dmn_traff_usege * 1024 * 1024, $dmn_traff_limit, 400);

gen_disk_usage($tpl, $dmn_disk_usage, $dmn_disk_limit, 400);

gen_user_messages_label($tpl, $sql, $_SESSION['user_id']);

check_user_permissions(
						$tpl, $dmn_sqld_limit, $dmn_sqlu_limit, $dmn_php,
						$dmn_cgi, $backup, $dns, $dmn_subd_limit, $dmn_als_limit,
						$dmn_mailacc_limit
);

$account_name = decode_idna($_SESSION['user_logged']);

if ($dmn_expire == 0) {
	$dmn_expire = tr('N/A');
} else {
	$date_formt = Config::get('DATE_FORMAT');
	$dmn_expires = date($date_formt, $dmn_expire);
}

list(
	$years, 
	$month, 
	$days, 
	$hours, 
	$minutes,
	$seconds
		) = gen_remain_time($dmn_expire);

if (($years > 0) && ($month > 0) && ($days <= 14)) {
	$tpl->assign(
		array('DMN_EXPIRES' => $years." Years, ".$month." Month, ".$days." Days")
	);
} else {
	$tpl->assign(
		array('DMN_EXPIRES' => "<span style=\"color:red\">".$years." Years, ".
								$month." Month, ".$days." Days</span>")
	);
}

$tpl->assign(
	array(
		'ACCOUNT_NAME'		=> $account_name,
		'MAIN_DOMAIN'		=> $dmn_name,
		'DMN_EXPIRES_DATE'	=> $dmn_expires,
		'MYSQL_SUPPORT'		=> ($dmn_sqld_limit != -1 && $dmn_sqlu_limit != -1) ? tr('yes') : tr('no'),
		'SUBDOMAINS'		=> gen_num_limit_msg($sub_cnt, $dmn_subd_limit),
		'DOMAIN_ALIASES'	=> gen_num_limit_msg($als_cnt, $dmn_als_limit),
		'MAIL_ACCOUNTS'		=> gen_num_limit_msg($mail_acc_cnt, $dmn_mailacc_limit),
		'FTP_ACCOUNTS'		=> gen_num_limit_msg($ftp_acc_cnt, $dmn_ftpacc_limit),
		'SQL_DATABASES'		=> gen_num_limit_msg($sqld_acc_cnt, $dmn_sqld_limit),
		'SQL_USERS'			=> gen_num_limit_msg($sqlu_acc_cnt, $dmn_sqlu_limit)
	)
);

/*
 *
 * static page messages.
 *
 */

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_general_information.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_general_information.tpl');

gen_logged_from($tpl);

gen_system_message($tpl, $sql);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_CLIENT_MAIN_INDEX_PAGE_TITLE' => tr('ispCP - Client/Main Index'),
		'THEME_COLOR_PATH'			=> "../themes/$theme_color",
		'THEME_CHARSET'				=> tr('encoding'),
		'ISP_LOGO'					=> get_logo($_SESSION['user_id']),
		'TR_GENERAL_INFORMATION' 	=> tr('General information'),
		'TR_ACCOUNT_NAME'			=> tr('Account name'),
		'TR_DOMAIN_EXPIRE' 			=> tr('Domain expire'),
		'TR_MAIN_DOMAIN'			=> tr('Main domain'),
		'TR_PHP_SUPPORT' 			=> tr('PHP support'),
		'TR_CGI_SUPPORT' 			=> tr('CGI support'),
		'TR_DNS_SUPPORT' 			=> tr('Manual DNS support'),
		'TR_BACKUP_SUPPORT' 		=> tr('Backup support'),
		'TR_MYSQL_SUPPORT' 			=> tr('SQL support'),
		'TR_SUBDOMAINS' 			=> tr('Subdomains'),
		'TR_DOMAIN_ALIASES' 		=> tr('Domain aliases'),
		'TR_MAIL_ACCOUNTS' 			=> tr('Mail accounts'),
		'TR_FTP_ACCOUNTS' 			=> tr('FTP accounts'),
		'TR_SQL_DATABASES' 			=> tr('SQL databases'),
		'TR_SQL_USERS' 				=> tr('SQL users'),
		'TR_MESSAGES' 				=> tr('Support system'),
		'TR_LANGUAGE' 				=> tr('Language'),
		'TR_CHOOSE_DEFAULT_LANGUAGE' => tr('Choose default language'),
		'TR_SAVE' 					=> tr('Save'),
		'TR_LAYOUT' 				=> tr('Layout'),
		'TR_CHOOSE_DEFAULT_LAYOUT' 	=> tr('Choose default layout'),
		'TR_TRAFFIC_USAGE' 			=> tr('Traffic usage'),
		'TR_DISK_USAGE'				=> tr('Disk usage')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
