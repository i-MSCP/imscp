<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
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

if (strtolower(Config::get('HOSTING_PLANS_LEVEL')) != 'admin') {
	user_goto('index.php');
}

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/hosting_plan_add.tpl');
$tpl->define_dynamic('page_message', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
			'TR_RESELLER_MAIN_INDEX_PAGE_TITLE'	=> tr('ispCP - Administrator/Add hosting plan'),
			'THEME_COLOR_PATH'					=> "../themes/$theme_color",
			'THEME_CHARSET'						=> tr('encoding'),
			'ISP_LOGO'							=> get_logo($_SESSION['user_id'])
	)
);

/*
 *
 * static page messages.
 *
 */

gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_hosting_plan.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_hosting_plan.tpl');

$tpl->assign(
		array(
				'TR_ADD_HOSTING_PLAN'		=> tr('Add hosting plan'),
				'TR_HOSTING PLAN PROPS'		=> tr('Hosting plan properties'),
				'TR_TEMPLATE_NAME'			=> tr('Template name'),
				'TR_MAX_SUBDOMAINS'			=> tr('Max subdomains<br><i>(-1 disabled, 0 unlimited)</i>'),
				'TR_MAX_ALIASES'			=> tr('Max aliases<br><i>(-1 disabled, 0 unlimited)</i>'),
				'TR_MAX_MAILACCOUNTS'		=> tr('Mail accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
				'TR_MAX_FTP'				=> tr('FTP accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
				'TR_MAX_SQL'				=> tr('SQL databases limit<br><i>(-1 disabled, 0 unlimited)</i>'),
				'TR_MAX_SQL_USERS'			=> tr('SQL users limit<br><i>(-1 disabled, 0 unlimited)</i>'),
				'TR_MAX_TRAFFIC'			=> tr('Traffic limit [MB]<br><i>(0 unlimited)</i>'),
				'TR_DISK_LIMIT'				=> tr('Disk limit [MB]<br><i>(0 unlimited)</i>'),
				'TR_PHP'					=> tr('PHP'),
				'TR_CGI'					=> tr('CGI / Perl'),
				'TR_DNS'					=> tr('Allow adding records to DNS zone (EXPERIMENTAL)'),
				'TR_BACKUP'					=> tr('Backup'),
				'TR_BACKUP_DOMAIN'			=> tr('Domain'),
				'TR_BACKUP_SQL'				=> tr('SQL'),
				'TR_BACKUP_FULL'			=> tr('Full'),
				'TR_BACKUP_NO'				=> tr('No'),
				'TR_APACHE_LOGS'			=> tr('Apache logfiles'),
				'TR_AWSTATS'				=> tr('AwStats'),
				'TR_YES'					=> tr('yes'),
				'TR_NO'						=> tr('no'),
				'TR_BILLING_PROPS'			=> tr('Billing Settings'),
				'TR_PRICE'					=> tr('Price'),
				'TR_SETUP_FEE'				=> tr('Setup fee'),
				'TR_VALUE'					=> tr('Currency'),
				'TR_PAYMENT'				=> tr('Payment period'),
				'TR_STATUS'					=> tr('Available for purchasing'),
				'TR_TEMPLATE_DESCRIPTON'	=> tr('Description'),
				'TR_EXAMPLE'				=> tr('(e.g. EUR)'),
				'TR_ADD_PLAN'				=> tr('Add plan')
		)
);

if (isset($_POST['uaction']) && ('add_plan' === $_POST['uaction'])) {
	// Process data
	if (check_data_correction($tpl))
		save_data_to_db($tpl, $_SESSION['user_id']);

	gen_data_ahp_page($tpl);
} else {
	gen_empty_ahp_page($tpl);
}

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
// Function definitions

/**
 * Generate empty form
 */
function gen_empty_ahp_page(&$tpl) {

	$tpl->assign(
			array(
					'HP_NAME_VALUE'			=> '',
					'TR_MAX_SUB_LIMITS'		=> '',
					'TR_MAX_ALS_VALUES'		=> '',
					'HP_MAIL_VALUE'			=> '',
					'HP_FTP_VALUE'			=> '',
					'HP_SQL_DB_VALUE'		=> '',
					'HP_SQL_USER_VALUE'		=> '',
					'HP_TRAFF_VALUE'		=> '',
					'HP_PRICE'				=> '',
					'HP_SETUPFEE'			=> '',
					'HP_VELUE'				=> '',
					'HP_PAYMENT'			=> '',
					'HP_DESCRIPTION_VALUE'	=> '',
					'HP_DISK_VALUE'			=> '',
					'TR_PHP_YES'			=> '',
					'TR_PHP_NO'				=> 'checked="checked"',
					'TR_CGI_YES'			=> '',
					'TR_CGI_NO'				=> 'checked="checked"',
					'VL_BACKUPD'			=> '',
					'VL_BACKUPS'			=> '',
					'VL_BACKUPF'			=> '',
					'VL_BACKUPN'			=> 'checked="checked"',
					'TR_DNS_YES'			=> '',
					'TR_DNS_NO'				=> 'checked="checked"',
					'TR_STATUS_YES'			=> 'checked="checked"',
					'TR_STATUS_NO'			=> ''
			)
	);

	$tpl->assign('MESSAGE', '');

} // end of gen_empty_hp_page()

/**
 * Show last entered data for new hp
 */
function gen_data_ahp_page(&$tpl) {

	global $hp_name, $description, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk;
	global $price, $setup_fee, $value, $payment, $status;
	global $hp_backup, $hp_dns;

	$tpl->assign(
			array(
					'HP_NAME_VALUE'			=> $hp_name,
					'TR_MAX_SUB_LIMITS'		=> $hp_sub,
					'TR_MAX_ALS_VALUES'		=> $hp_als,
					'HP_MAIL_VALUE'			=> $hp_mail,
					'HP_FTP_VALUE'			=> $hp_ftp,
					'HP_SQL_DB_VALUE'		=> $hp_sql_db,
					'HP_SQL_USER_VALUE'		=> $hp_sql_user,
					'HP_TRAFF_VALUE'		=> $hp_traff,
					'HP_DISK_VALUE'			=> $hp_disk,
					'HP_DESCRIPTION_VALUE'	=> $description,
					'HP_PRICE'				=> $price,
					'HP_SETUPFEE'			=> $setup_fee,
					'HP_VELUE'				=> $value,
					'HP_PAYMENT'			=> $payment
			)
	);

	$tpl->assign(
			array(
					'TR_PHP_YES'	=> ($hp_php == '_yes_') ? 'checked="checked"' : '',
					'TR_PHP_NO'		=> ($hp_php == '_no_') ? 'checked="checked"' : '',
					'TR_CGI_YES'	=> ($hp_cgi == '_yes_') ? 'checked="checked"' : '',
					'TR_CGI_NO'		=> ($hp_cgi == '_no_') ? 'checked="checked"' : '',
					'VL_BACKUPD'	=> ($hp_backup == '_dmn_') ? 'checked="checked"' : '',
					'VL_BACKUPS'	=> ($hp_backup == '_sql_') ? 'checked="checked"' : '',
					'VL_BACKUPF'	=> ($hp_backup == '_full_') ? 'checked="checked"' : '',
					'VL_BACKUPN'	=> ($hp_backup == '_no_') ? 'checked="checked"' : '',
					'TR_DNS_YES'	=> ($hp_dns == '_yes_') ? 'checked="checked"' : '',
					'TR_DNS_NO'		=> ($hp_dns == '_no_') ? 'checked="checked"' : '',
					'TR_STATUS_YES'	=> ($status) ? 'checked="checked"' : '',
					'TR_STATUS_NO'	=> (!$status) ? 'checked="checked"' : ''
			)
	);

} // End of gen_data_ahp_page()

/**
 * Check correction of input data
 */
function check_data_correction(&$tpl) {

	global $hp_name, $description, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk;
	global $price, $setup_fee, $value, $payment, $status;
	global $hp_backup, $hp_dns;

	$ahp_error 		= array();

	$hp_name		= clean_input($_POST['hp_name'], true);
	$hp_sub			= clean_input($_POST['hp_sub'], true);
	$hp_als			= clean_input($_POST['hp_als'], true);
	$hp_mail		= clean_input($_POST['hp_mail'], true);
	$hp_ftp			= clean_input($_POST['hp_ftp'], true);
	$hp_sql_db		= clean_input($_POST['hp_sql_db'], true);
	$hp_sql_user	= clean_input($_POST['hp_sql_user'], true);
	$hp_traff		= clean_input($_POST['hp_traff'], true);
	$hp_disk		= clean_input($_POST['hp_disk'], true);
	$description	= clean_input($_POST['hp_description'], true);
	$price			= empty($_POST['hp_price']) ? 0 : clean_input($_POST['hp_price'], true);
	$setup_fee		= empty($_POST['hp_setupfee']) ? 0 : clean_input($_POST['hp_setupfee'], true);
	$value			= clean_input($_POST['hp_value'], true);
	$payment		= clean_input($_POST['hp_payment'], true);
	$status			= $_POST['status'];

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

	if (empty($hp_name)) {
		$ahp_error[] = tr('Incorrect template name length!');
	}

	if (empty($description)) {
		$ahp_error[] = tr('Incorrect template description length!');
	}
	if (!is_numeric($price)) {
		$ahp_error[] = tr('Incorrect price syntax!');
	}

	if (!is_numeric($setup_fee)) {
		$ahp_error[] = tr('Incorrect setup fee syntax!');
	}

	if (!ispcp_limit_check($hp_sub, -1)) {
		$ahp_error[] = tr('Incorrect subdomains limit!');
	} 
	if (!ispcp_limit_check($hp_als, -1)) {
		$ahp_error[] = tr('Incorrect aliases limit!');
	} 
	if (!ispcp_limit_check($hp_mail, -1)) {
		$ahp_error[] = tr('Incorrect mail accounts limit!');
	} 
	if (!ispcp_limit_check($hp_ftp, -1)) {
		$ahp_error[] = tr('Incorrect FTP accounts limit!');
	} 
	if (!ispcp_limit_check($hp_sql_user, -1)) {
		$ahp_error[] = tr('Incorrect SQL databases limit!');
	} 
	if (!ispcp_limit_check($hp_sql_db, -1)) {
		$ahp_error[] = tr('Incorrect SQL users limit!');
	} 
	if (!ispcp_limit_check($hp_traff, null)) {
		$ahp_error[] = tr('Incorrect traffic limit!');
	} 
	if (!ispcp_limit_check($hp_disk, null)) {
		$ahp_error[] = tr('Incorrect disk quota limit!');
	}

	if (empty($ahp_error)) {
		$tpl->assign('MESSAGE', '');
		return true;
	} else {
		set_page_message(format_message($ahp_error));
		return false;
	}

} // end of check_data_correction()

/**
 * Add new host plan to DB
 */
function save_data_to_db(&$tpl, $admin_id) {

	global $hp_name, $description, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk;
	global $price, $setup_fee, $value, $payment, $status;
	global $hp_backup, $hp_dns;

	$sql = Database::getInstance();

	$query = "SELECT `id` FROM `hosting_plans` WHERE `name` = ? AND `reseller_id` = ?";
	$query = "
		SELECT
			t1.`id`, t1.`name`, t1.`reseller_id`, t1.`name`, t1.`props`,
			t1.`status`, t2.`admin_id`, t2.`admin_type`
		FROM
			`hosting_plans` AS t1,
			`admin` AS t2
		WHERE
			t2.`admin_type` = ?
		AND
			t1.`reseller_id` = t2.`admin_id`
		AND
			t1.`name` = ?
	";
	$res = exec_query($sql, $query, array('admin', $hp_name));

	if ($res->RowCount() == 1) {
		$tpl->assign('MESSAGE', tr('Hosting plan with entered name already exists!'));
		// $tpl->parse('AHP_MESSAGE', 'ahp_message');
	} else {
		$hp_props = "$hp_php;$hp_cgi;$hp_sub;$hp_als;$hp_mail;$hp_ftp;$hp_sql_db;$hp_sql_user;$hp_traff;$hp_disk;$hp_backup;$hp_dns";
		$query = "
			INSERT INTO
				hosting_plans(
					reseller_id,
					name,
					description,
					props,
					price,
					setup_fee,
					value,
					payment,
					status)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
		";

		$res = exec_query($sql, $query, array($admin_id, $hp_name, $description, $hp_props, $price, $setup_fee, $value, $payment, $status));

		$_SESSION['hp_added'] = '_yes_';
		user_goto('hosting_plan.php');
	}
} // end of save_data_to_db()
