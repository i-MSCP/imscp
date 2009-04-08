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

if (strtolower(Config::get('HOSTING_PLANS_LEVEL')) != 'admin') {
	header('Location: index.php');
	die();
}

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/hosting_plan_edit.tpl');
$tpl->define_dynamic('page_message', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

/*
 *
 * static page messages.
 *
 */

global $hpid;
// Show main menu
gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_hosting_plan.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_hosting_plan.tpl');

$tpl->assign(
	array(
		'TR_RESELLER_MAIN_INDEX_PAGE_TITLE' => tr('ispCP - Administrator/Edit hosting plan'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

$tpl->assign(
	array(
		'TR_HOSTING PLAN PROPS' => tr('Hosting plan properties'),
		'TR_TEMPLATE_NAME' => tr('Template name'),
		'TR_MAX_SUBDOMAINS' => tr('Max subdomains<br><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_ALIASES' => tr('Max aliases<br><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_MAILACCOUNTS' => tr('Mail accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_FTP' => tr('FTP accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_SQL' => tr('SQL databases limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_SQL_USERS' => tr('SQL users limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_TRAFFIC' => tr('Traffic limit [MB]<br><i>(0 unlimited)</i>'),
		'TR_DISK_LIMIT' => tr('Disk limit [MB]<br><i>(0 unlimited)</i>'),
		'TR_PHP' => tr('PHP'),
		'TR_CGI' => tr('CGI / Perl'),
		'TR_BACKUP_RESTORE' => tr('Backup and restore'),
		'TR_APACHE_LOGS' => tr('Apache logfiles'),
		'TR_AWSTATS' => tr('AwStats'),
		'TR_YES' => tr('yes'),
		'TR_NO' => tr('no'),
		'TR_BILLING_PROPS' => tr('Billing Settings'),
		'TR_PRICE_STYLE' => tr('Price Style'),
		'TR_PRICE' => tr('Price'),
		'TR_SETUP_FEE' => tr('Setup fee'),
		'TR_VALUE' => tr('Currency'),
		'TR_PAYMENT' => tr('Payment period'),
		'TR_STATUS' => tr('Available for purchasing'),
		'TR_TEMPLATE_DESCRIPTON' => tr('Description'),
		'TR_EXAMPLE' => tr('(e.g. EUR)'),
		'TR_UPDATE_PLAN' => tr('Update plan')
	)
);

/*
 * Dynamic page process
 */
if (isset($_POST['uaction']) && ('add_plan' === $_POST['uaction'])) {
	// Process data
	if (check_data_iscorrect($tpl)) { // Save data to db
		save_data_to_db();
	} else {
		restore_form($tpl);
	}
} else {
	// Get hosting plan id that comes for edit
	if (isset($_GET['hpid'])) {
		$hpid = $_GET['hpid'];
	}

	gen_load_ehp_page($tpl, $sql, $hpid, $_SESSION['user_id']);
	$tpl->assign('MESSAGE', "");
}

gen_page_message($tpl);
$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

/*
 * Function definitions
 */

/**
 * Restore form on any error
 */
function restore_form(&$tpl) {
	$tpl->assign(
		array(
			'HP_NAME_VALUE' => clean_input($_POST['hp_name'], true),
			'HP_DESCRIPTION_VALUE' => clean_input($_POST['hp_description'], true),
			'TR_MAX_SUB_LIMITS' => clean_input($_POST['hp_sub'], true),
			'TR_MAX_ALS_VALUES' => clean_input($_POST['hp_als'], true),
			'HP_MAIL_VALUE' => clean_input($_POST['hp_mail'], true),
			'HP_FTP_VALUE' => clean_input($_POST['hp_ftp'], true),
			'HP_SQL_DB_VALUE' => clean_input($_POST['hp_sql_db'], true),
			'HP_SQL_USER_VALUE' => clean_input($_POST['hp_sql_user'], true),
			'HP_TRAFF_VALUE' => clean_input($_POST['hp_traff'], true),
			'HP_TRAFF' => clean_input($_POST['hp_traff'], true),
			'HP_DISK_VALUE' => clean_input($_POST['hp_disk'], true),
			'HP_PRICE_STYLE' => format_price(clean_input($_POST['hp_style'], true)),
			'HP_PRICE' => clean_input($_POST['hp_price'], true),
			'HP_SETUPFEE' => clean_input($_POST['hp_setupfee'], true),
			'HP_CURRENCY' => clean_input($_POST['hp_currency'], true),
			'HP_PAYMENT' => clean_input($_POST['hp_payment'], true)
		)
	);

	$tpl->assign(array('TR_PHP_YES'	=> ($_POST['php'] === '_yes_') ? 'checked="checked"' : ''));
	$tpl->assign(array('TR_PHP_NO'	=> ($_POST['php'] === '_yes_') ? '' : 'checked="checked"'));

	$tpl->assign(array('TR_CGI_YES'	=> ($_POST['cgi'] === '_yes_') ? 'checked="checked"' : ''));
	$tpl->assign(array('TR_CGI_NO'	=> ($_POST['cgi'] === '_yes_') ? '' : 'checked="checked"'));

	$tpl->assign(array('TR_STATUS_YES'	=> ($_POST['status'] == 1) ? 'checked="checked"' : ''));
	$tpl->assign(array('TR_STATUS_NO'	=> ($_POST['status'] == 1) ? '' : 'checked="checked"'));

// Generate load data from sql for requested hosting plan
function gen_load_ehp_page(&$tpl, &$sql, $hpid, $admin_id) {
	$_SESSION['hpid'] = $hpid;

	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`hosting_plans`
		WHERE
			`id` = ?;
SQL_QUERY;
	$res = exec_query($sql, $query, array($hpid));

	$readonly = '';
	$disabled = '';
	$edit_hp = tr('Edit hosting plan');
	// $tpl->parse('FORM', 'page');
	if ($res->RowCount() !== 1) { // Error
		header('Location: hosting_plan.php');
		die();
	}

	$data = $res->FetchRow();
	$props = $data['props'];
	$description = $data['description'];
	$price = $data['price'];
	$setup_fee = $data['setup_fee'];
	$value = $data['value'];
	$payment = $data['payment'];
	$status = $data['status'];
	list($hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db, $hp_sql_user, $hp_traff, $hp_disk) = explode(";", $props);
	$hp_name = $data['name'];

	if ($description == '')
		$description = '';

	if ($payment == '')
		$payment = '';

	if ($value == '')
		$value = '';

	$tpl->assign(
		array(
			'HP_NAME_VALUE' => $hp_name,
			'TR_EDIT_HOSTING_PLAN' => $edit_hp,
			'TR_MAX_SUB_LIMITS' => $hp_sub,
			'TR_MAX_ALS_VALUES' => $hp_als,
			'HP_MAIL_VALUE' => $hp_mail,
			'HP_FTP_VALUE' => $hp_ftp,
			'HP_SQL_DB_VALUE' => $hp_sql_db,
			'HP_SQL_USER_VALUE' => $hp_sql_user,
			'HP_TRAFF_VALUE' => $hp_traff,
			'HP_DISK_VALUE' => $hp_disk,
			'HP_DESCRIPTION_VALUE' => $description,
			'HP_PRICE' => $price,
			'HP_SETUPFEE' => $setup_fee,
			'HP_CURRENCY' => $value,
			'READONLY' => $readonly,
			'DISBLED' => $disabled,
			'HP_PAYMENT' => $payment
		)
	);

	$tpl->assign(
		array(
			'TR_PHP_YES'	=> ($hp_php === '_yes_') ? 'checked="checked"' : '',
			'TR_PHP_NO'		=> ($hp_php === '_yes_') ? '' : 'checked="checked"',
			'TR_CGI_YES'	=> ($hp_cgi === '_yes_') ? 'checked="checked"' : '',
			'TR_CGI_NO'		=> ($hp_cgi === '_yes_') ? '' : 'checked="checked"',
			'TR_STATUS_YES'	=> ($status == 1) ? 'checked="checked"' : '',
			'TR_STATUS_NO'	=> ($status == 1) ? '' : 'checked="checked"'
		)
	);
} // End of gen_load_ehp_page()

/**
 * Check correction of input data
 */
function check_data_iscorrect(&$tpl) {
	global $hp_name, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk;
	global $hpid;

	$ahp_error		= '_off_';
	$hp_name		= clean_input($_POST['hp_name']);
	$hp_sub			= clean_input($_POST['hp_sub']);
	$hp_als			= clean_input($_POST['hp_als']);
	$hp_mail		= clean_input($_POST['hp_mail']);
	$hp_ftp			= clean_input($_POST['hp_ftp']);
	$hp_sql_db		= clean_input($_POST['hp_sql_db']);
	$hp_sql_user	= clean_input($_POST['hp_sql_user']);
	$hp_traff		= clean_input($_POST['hp_traff']);
	$hp_disk		= clean_input($_POST['hp_disk']);

	if (isset($_SESSION['hpid']))
		$hpid = $_SESSION['hpid'];
	else
		$ahp_error = tr('Undefined reference to data!');
	// put hosting plan id into session value
	$_SESSION['hpid'] = $hpid;
	// Get values from previous page and check him correction
	if (isset($_POST['php']))
		$hp_php = $_POST['php'];

	if (isset($_POST['cgi']))
		$hp_cgi = $_POST['cgi'];;

	if (!is_numeric($_POST['hp_price'])) {
		$ahp_error = tr('Incorrect price. Example: 9.99');
	}

	if (!is_numeric($_POST['hp_setupfee'])) {
		$ahp_error = tr('Incorrect setup fee. Example: 19.99');
	}

	if (!ispcp_limit_check($hp_sub, -1)) {
		$ahp_error = tr('Incorrect subdomains limit!');
	} else if (!ispcp_limit_check($hp_als, -1)) {
		$ahp_error = tr('Incorrect aliases limit!');
	} else if (!ispcp_limit_check($hp_mail, -1)) {
		$ahp_error = tr('Incorrect mail accounts limit!');
	} else if (!ispcp_limit_check($hp_ftp, -1)) {
		$ahp_error = tr('Incorrect FTP accounts limit!');
	} else if (!ispcp_limit_check($hp_sql_user, -1)) {
		$ahp_error = tr('Incorrect SQL databases limit!');
	} else if (!ispcp_limit_check($hp_sql_db, -1)) {
		$ahp_error = tr('Incorrect SQL users limit!');
	} else if (!ispcp_limit_check($hp_traff, null)) {
		$ahp_error = tr('Incorrect traffic limit!');
	} else if (!ispcp_limit_check($hp_disk, null)) {
		$ahp_error = tr('Incorrect disk quota limit!');
	}

	if ($ahp_error == '_off_') {
		$tpl->assign('MESSAGE', '');
		return true;
	} else {
		set_page_message($ahp_error);
		return false;
	}
} // End of check_data_iscorrect()

/**
 * Add new host plan to DB
 */
function save_data_to_db() {
	$sql = Database::getInstance();
	global $hp_name, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk;
	global $hpid;

	$description	= clean_input($_POST['hp_description']);
	$price			= clean_input($_POST['hp_price']);
	$setup_fee		= clean_input($_POST['hp_setupfee']);
	$value			= clean_input($_POST['hp_currency']);
	$payment		= clean_input($_POST['hp_payment']);
	$status			= clean_input($_POST['status']);

	$hp_props = "$hp_php;$hp_cgi;$hp_sub;$hp_als;$hp_mail;$hp_ftp;$hp_sql_db;$hp_sql_user;$hp_traff;$hp_disk;";

	$query = <<<SQL_QUERY
		UPDATE
			`hosting_plans`
		SET
			`name` = ?,
			`description` = ?,
			`props` = ?,
			`price` = ?,
			`setup_fee` = ?,
			`value` = ?,
			`payment` = ?,
			`status` = ?
		WHERE
			`id` = ?
SQL_QUERY;
	$res = exec_query($sql, $query, array($hp_name, $description, $hp_props, $price, $setup_fee, $value, $payment, $status, $hpid));
	$_SESSION['hp_updated'] = "_yes_";
	header("Location: hosting_plan.php");
	die();
} // End of save_data_to_db()

die();

?>