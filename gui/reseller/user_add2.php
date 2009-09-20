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
$tpl->define_dynamic('page', Config::get('RESELLER_TEMPLATE_PATH') . '/user_add2.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

// check if we have only hosting plans for admins - reseller should not edit them
if (Config::exists('HOSTING_PLANS_LEVEL')
	&& Config::get('HOSTING_PLANS_LEVEL') === 'admin') {
	user_goto('users.php');
}

$tpl->assign(
	array(
			'TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE' => tr('ispCP - User/Add user(step2)'),
			'THEME_COLOR_PATH'							=> "../themes/$theme_color",
			'THEME_CHARSET'								=> tr('encoding'),
			'ISP_LOGO'									=> get_logo($_SESSION['user_id'])
	)
);

/*
 * static page messages.
 */

gen_reseller_mainmenu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/main_menu_users_manage.tpl');
gen_reseller_menu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/menu_users_manage.tpl');

gen_logged_from($tpl);

$tpl->assign(
		array(
			'TR_ADD_USER'					=> tr('Add user'),
			'TR_HOSTING_PLAN_PROPERTIES'	=> tr('Hosting plan properties'),
			'TR_TEMPLATE_NAME'				=> tr('Template name'),
			'TR_MAX_DOMAIN'					=> tr('Max domains<br><i>(-1 disabled, 0 unlimited)</i>'),
			'TR_MAX_SUBDOMAIN'				=> tr('Max subdomains<br><i>(-1 disabled, 0 unlimited)</i>'),
			'TR_MAX_DOMAIN_ALIAS'			=> tr('Max aliases<br><i>(-1 disabled, 0 unlimited)</i>'),
			'TR_MAX_MAIL_COUNT'				=> tr('Mail accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
			'TR_MAX_FTP'					=> tr('FTP accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
			'TR_MAX_SQL_DB'					=> tr('SQL databases limit<br><i>(-1 disabled, 0 unlimited)</i>'),
			'TR_MAX_SQL_USERS'				=> tr('SQL users limit<br><i>(-1 disabled, 0 unlimited)</i>'),
			'TR_MAX_TRAFFIC'				=> tr('Traffic limit [MB]<br><i>(0 unlimited)</i>'),
			'TR_MAX_DISK_USAGE'				=> tr('Disk limit [MB]<br><i>(0 unlimited)</i>'),
			'TR_PHP'						=> tr('PHP'),
			'TR_CGI'						=> tr('CGI / Perl'),
			'TR_BACKUP'						=> tr('Backup'),
			'TR_BACKUP_DOMAIN'				=> tr('Domain'),
			'TR_BACKUP_SQL'					=> tr('SQL'),
			'TR_BACKUP_FULL'				=> tr('Full'),
			'TR_BACKUP_NO'					=> tr('No'),
			'TR_DNS'						=> tr('Manual DNS support'),
			'TR_YES'						=> tr('yes'),
			'TR_NO'							=> tr('no'),
			'TR_NEXT_STEP'					=> tr('Next step'),
			'TR_APACHE_LOGS'				=> tr('Apache logs'),
			'TR_AWSTATS'					=> tr('Awstats')
		)
);

if (!get_pageone_param()) {
	set_page_message(tr("Domain data has been altered. Please enter again"));
	unset_messages();
	user_goto('user_add1.php');
}

if (isset($_POST['uaction'])
	&& ("user_add2_nxt" === $_POST['uaction'])
	&& (!isset($_SESSION['step_one']))) {
	if (check_user_data($tpl)) {
		$_SESSION["step_two_data"] = "$dmn_name;0;";
		$_SESSION["ch_hpprops"] = "$hp_php;$hp_cgi;$hp_sub;$hp_als;$hp_mail;$hp_ftp;$hp_sql_db;$hp_sql_user;$hp_traff;$hp_disk;$hp_backup;$hp_dns";

		if (reseller_limits_check($sql, $ehp_error, $_SESSION['user_id'], 0, $_SESSION["ch_hpprops"])) {
			user_goto('user_add3.php');
		}
	}
} else {
	unset($_SESSION['step_one']);
	global $dmn_chp;
	get_hp_data($dmn_chp, $_SESSION['user_id']);
	$tpl->assign('MESSAGE', '');
}

get_init_au2_page($tpl);
gen_page_message($tpl);
$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
//unset_messages();

// Function declaration

/**
 * get param of previous page
 */
function get_pageone_param() {

	global $dmn_name;
	global $dmn_expire;
	global $dmn_chp;

	if (isset($_SESSION['dmn_name'])) {
		$dmn_name = $_SESSION['dmn_name'];
		$dmn_expire = $_SESSION['dmn_expire'];
		$dmn_chp = $_SESSION['dmn_tpl'];
	} else {
		return false;
	}

	return true;
} // End of get_pageone_param()

/**
 * Show page with initial data fields
 */
function get_init_au2_page(&$tpl) {

	global $hp_name, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk, $hp_backup, $hp_dns;

	$tpl->assign(
			array(
				'VL_TEMPLATE_NAME'	=> $hp_name,
				'MAX_DMN_CNT'		=> '',
				'MAX_SUBDMN_CNT'	=> $hp_sub,
				'MAX_DMN_ALIAS_CNT'	=> $hp_als,
				'MAX_MAIL_CNT'		=> $hp_mail,
				'MAX_FTP_CNT'		=> $hp_ftp,
				'MAX_SQL_CNT'		=> $hp_sql_db,
				'VL_MAX_SQL_USERS'	=> $hp_sql_user,
				'VL_MAX_TRAFFIC'	=> $hp_traff,
				'VL_MAX_DISK_USAGE'	=> $hp_disk,
				'VL_PHPY'			=> ($hp_php === '_yes_') ? 'checked="checked"' : '',
				'VL_PHPN'			=> ($hp_php === '_no_') ? 'checked="checked"' : '',
				'VL_CGIY'			=> ($hp_cgi === '_yes_') ? 'checked="checked"' : '',
				'VL_CGIN'			=> ($hp_cgi === '_no_') ? 'checked="checked"' : '',
				'VL_BACKUPD'		=> ($hp_backup === '_dmn_') ? 'checked="checked"' : '',
				'VL_BACKUPS'		=> ($hp_backup === '_sql_') ? 'checked="checked"' : '',
				'VL_BACKUPF'		=> ($hp_backup === '_full_') ? 'checked="checked"' : '',
				'VL_BACKUPN'		=> ($hp_backup === '_no_') ? 'checked="checked"' : '',
				'VL_DNSY'			=> ($hp_dns === '_yes_') ? 'checked="checked"' : '',
				'VL_DNSN'			=> ($hp_dns === '_no_') ? 'checked="checked"' : ''
			)
	);

} // End of get_init_au2_page()

/**
 * Get data for hosting plan
 */
function get_hp_data($hpid, $admin_id) {

	global $hp_name, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk, $hp_backup, $hp_dns;

	$sql = Database::getInstance();

	$query = "SELECT `name`, `props` FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?";

	$res = exec_query($sql, $query, array($admin_id, $hpid));

	if (0 !== $res->RowCount()) {
		$data = $res->FetchRow();

		$props = $data['props'];

		list($hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db, $hp_sql_user, $hp_traff, $hp_disk, $hp_backup, $hp_dns) = explode(";", $props);

		$hp_name = $data['name'];
	} else {
			$hp_name = 'Custom';
			$hp_php = '_no_';
			$hp_cgi = '_no_';
			$hp_sub = '';
			$hp_als = '';
			$hp_mail = '';
			$hp_ftp = '';
			$hp_sql_db = '';
			$hp_sql_user = '';
			$hp_traff = '';
			$hp_disk = '';
			$hp_backup = '_no_';
			$hp_dns = '_no_';
	}
} // End of get_hp_data()

/**
 * Check validity of input data
 */
function check_user_data(&$tpl) {

	global $hp_name, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk, $hp_dmn, $hp_backup, $hp_dns;
	global $dmn_chp;
	
	//$sql = Database::getInstance();

	$ehp_error = '';

	// Get data for fields from previous page
	if (isset($_POST['template'])) {
		$hp_name = $_POST['template'];
	}

	if (isset($_POST['nreseller_max_domain_cnt'])) {
		$hp_dmn = clean_input($_POST['nreseller_max_domain_cnt']);
	}

	if (isset($_POST['nreseller_max_subdomain_cnt'])) {
		$hp_sub = clean_input($_POST['nreseller_max_subdomain_cnt']);
	}

	if (isset($_POST['nreseller_max_alias_cnt'])) {
		$hp_als = clean_input($_POST['nreseller_max_alias_cnt']);
	}

	if (isset($_POST['nreseller_max_mail_cnt'])) {
		$hp_mail = clean_input($_POST['nreseller_max_mail_cnt']);
	}
	if (isset($_POST['nreseller_max_ftp_cnt']) || $hp_ftp == -1) {
		$hp_ftp = clean_input($_POST['nreseller_max_ftp_cnt']);
	}

	if (isset($_POST['nreseller_max_sql_db_cnt'])) {
		$hp_sql_db = clean_input($_POST['nreseller_max_sql_db_cnt']);
	}

	if (isset($_POST['nreseller_max_sql_user_cnt'])) {
		$hp_sql_user = clean_input($_POST['nreseller_max_sql_user_cnt']);
	}
	if (isset($_POST['nreseller_max_traffic'])) {
		$hp_traff = clean_input($_POST['nreseller_max_traffic']);
	}

	if (isset($_POST['nreseller_max_disk'])) {
		$hp_disk = clean_input($_POST['nreseller_max_disk']);
	}

	if (isset($_POST['php'])) {
		$hp_php = $_POST['php'];
	}

	if (isset($_POST['cgi'])) {
		$hp_cgi = $_POST['cgi'];
	}

	if (isset($_POST['backup'])) {
		$hp_backup = $_POST['backup'];
	}

	if (isset($_POST['dns'])) {
		$hp_dns = $_POST['dns'];
	}

	// Begin checking...
	if (!ispcp_limit_check($hp_sub, -1)) {
		set_page_message(tr('Incorrect subdomains limit!'));
	}

	if (!ispcp_limit_check($hp_als, -1)) {
		set_page_message(tr('Incorrect aliases limit!'));
	}

	if (!ispcp_limit_check($hp_mail, -1)) {
		set_page_message(('Incorrect mail accounts limit!'));
	}

	if (!ispcp_limit_check($hp_ftp, -1)) {
		set_page_message(tr('Incorrect FTP accounts limit!'));
	}

	if (!ispcp_limit_check($hp_sql_db, -1)) {
		set_page_message(tr('Incorrect SQL databases limit!'));
	} else if ($hp_sql_user != -1 && $hp_sql_db == -1) {
		set_page_message(tr('SQL users limit is <i>disabled</i>!'));
	}

	if (!ispcp_limit_check($hp_sql_user, -1)) {
		set_page_message(tr('Incorrect SQL users limit!'));
	} else if ($hp_sql_user == -1 && $hp_sql_db != -1) {
		set_page_message(tr('SQL databases limit is not <i>disabled</i>!'));
	}

	if (!ispcp_limit_check($hp_traff, null)) {
		set_page_message(tr('Incorrect traffic limit!'));
	}

	if (!ispcp_limit_check($hp_disk, null)) {
		set_page_message(tr('Incorrect disk quota limit!'));
	}

	if (empty($ehp_error) && empty($_SESSION['user_page_message'])) {
		$tpl->assign('MESSAGE', '');
		// send data through session
		return true;
	} else {
		$tpl->assign('MESSAGE', $ehp_error);
		return false;
	}
} // End of check_user_data()

/**
 * Check if hosting plan with this name already exists!
 */
function check_hosting_plan_name($admin_id) {

	global $hp_name;
	$sql = Database::getInstance();

	$query = "SELECT `id` FROM `hosting_plans` WHERE `name` = ? AND `reseller_id` = ?";
	$res = exec_query($sql, $query, array($hp_name, $admin_id));

	if ($res->RowCount() !== 0) {
		return false;
	}

	return true;
} // End of check_hosting_plan_name()
