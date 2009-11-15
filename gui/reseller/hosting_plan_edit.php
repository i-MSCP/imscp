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
 * Portions created by the ispCP Team are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('RESELLER_TEMPLATE_PATH') . '/hosting_plan_edit.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

/*
 *
 * static page messages.
 *
 */

global $hpid;
// Show main menu
gen_reseller_mainmenu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/main_menu_hosting_plan.tpl');
gen_reseller_menu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/menu_hosting_plan.tpl');

gen_logged_from($tpl);

$tpl->assign(
		array(
				'TR_RESELLER_MAIN_INDEX_PAGE_TITLE'	=> tr('ispCP - Reseller/Edit hosting plan'),
				'THEME_COLOR_PATH'					=> "../themes/$theme_color",
				'THEME_CHARSET'						=> tr('encoding'),
				'ISP_LOGO'							=> get_logo($_SESSION['user_id'])
		)
);

$tpl->assign(
		array(
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
				'TR_DNS'					=> tr('Allow adding records to DNS zone'),
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
				'TR_EDIT_HOSTING_PLAN'		=> tr('Update plan'),
				'TR_UPDATE_PLAN'			=> tr('Update plan')
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
		restore_form($tpl, $sql);
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

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
/*
 * Function definitions
 */

/**
 * Restore form on any error
 */
function restore_form(&$tpl, &$sql) {
	$tpl->assign(
			array(
					'HP_NAME_VALUE'			=> clean_input($_POST['hp_name'], true),
					'HP_DESCRIPTION_VALUE'	=> clean_input($_POST['hp_description'], true),
					'TR_MAX_SUB_LIMITS'		=> clean_input($_POST['hp_sub'], true),
					'TR_MAX_ALS_VALUES'		=> clean_input($_POST['hp_als'], true),
					'HP_MAIL_VALUE'			=> clean_input($_POST['hp_mail'], true),
					'HP_FTP_VALUE'			=> clean_input($_POST['hp_ftp'], true),
					'HP_SQL_DB_VALUE'		=> clean_input($_POST['hp_sql_db'], true),
					'HP_SQL_USER_VALUE'		=> clean_input($_POST['hp_sql_user'], true),
					'HP_TRAFF_VALUE'		=> clean_input($_POST['hp_traff'], true),
					'HP_TRAFF'				=> clean_input($_POST['hp_traff'], true),
					'HP_DISK_VALUE'			=> clean_input($_POST['hp_disk'], true),
					'HP_PRICE'				=> clean_input($_POST['hp_price']),
					'HP_SETUPFEE'			=> clean_input($_POST['hp_setupfee'], true),
					'HP_CURRENCY'			=> clean_input($_POST['hp_currency'], true),
					'HP_PAYMENT'			=> clean_input($_POST['hp_payment'], true)
			)
	);

	$tpl->assign(
			array(
					'TR_PHP_YES'	=> ($_POST['php'] == '_yes_') ? 'checked="checked"' : '',
					'TR_PHP_NO'		=> ($_POST['php'] == '_no_') ? 'checked="checked"' : '',
					'TR_CGI_YES'	=> ($_POST['cgi'] == '_yes_') ? 'checked="checked"' : '',
					'TR_CGI_NO'		=> ($_POST['cgi'] == '_no_') ? 'checked="checked"' : '',
					'TR_DNS_YES'	=> ($_POST['dns'] == '_yes_') ? 'checked="checked"' : '',
					'TR_DNS_NO'		=> ($_POST['dns'] == '_no_') ? 'checked="checked"' : '',
					'VL_BACKUPD'	=> ($hp_backup == '_dmn_') ? 'checked="checked"' : '',
					'VL_BACKUPS'	=> ($hp_backup == '_sql_') ? 'checked="checked"' : '',
					'VL_BACKUPF'	=> ($hp_backup == '_full_') ? 'checked="checked"' : '',
					'VL_BACKUPN'	=> ($hp_backup == '_no_') ? 'checked="checked"' : '',
					'TR_STATUS_YES'	=> ($_POST['status']) ? 'checked="checked"' : '',
					'TR_STATUS_NO'	=> (!$_POST['status']) ? 'checked="checked"' : ''
			)
	);

} // end of function restore_form()

/**
 * Generate load data from sql for requested hosting plan
 */
function gen_load_ehp_page(&$tpl, &$sql, $hpid, $admin_id) {

	$_SESSION['hpid'] = $hpid;

	if (Config::exists('HOSTING_PLANS_LEVEL')
		&& Config::get('HOSTING_PLANS_LEVEL') === 'admin') {
		$query = "
			SELECT
				*
			FROM
				`hosting_plans`
			WHERE
				`id` = ?;
		";

		$res = exec_query($sql, $query, array($hpid));
		$readonly = 'readonly="readonly"';
		$disabled = 'disabled="disabled"';
		$edit_hp = tr('View hosting plan');
		$tpl->assign('FORM', "");

	} else {
		$query = "
			SELECT
				*
			FROM
				`hosting_plans`
			WHERE
				`reseller_id` = ?
			AND
				`id` = ?;
		";

		$res = exec_query($sql, $query, array($admin_id, $hpid));
		$readonly = '';
		$disabled = '';
		$edit_hp = tr('Edit hosting plan');
	}

	if ($res->RowCount() !== 1) { // Error
		user_goto('hosting_plan.php');
	}

	$data = $res->FetchRow();
	$props = $data['props'];
	$description = $data['description'];
	$price = $data['price'];
	$setup_fee = $data['setup_fee'];
	$value = $data['value'];
	$payment = $data['payment'];
	$status = $data['status'];
	
	list(
			$hp_php,
			$hp_cgi,
			$hp_sub,
			$hp_als,
			$hp_mail,
			$hp_ftp,
			$hp_sql_db,
			$hp_sql_user,
			$hp_traff,
			$hp_disk,
			$hp_backup,
			$hp_dns

	) = explode(";", $props);
	
	$hp_name = $data['name'];

	if ($description == '')
		$description = '';

	if ($payment == '')
		$payment = '';

	if ($value == '')
		$value = '';

	$tpl->assign(
			array(
					'HP_NAME_VALUE'			=> stripslashes($hp_name),
					'TR_EDIT_HOSTING_PLAN'	=> $edit_hp,
					'TR_MAX_SUB_LIMITS'		=> $hp_sub,
					'TR_MAX_ALS_VALUES'		=> $hp_als,
					'HP_MAIL_VALUE'			=> $hp_mail,
					'HP_FTP_VALUE'			=> $hp_ftp,
					'HP_SQL_DB_VALUE'		=> $hp_sql_db,
					'HP_SQL_USER_VALUE'		=> $hp_sql_user,
					'HP_TRAFF_VALUE'		=> $hp_traff,
					'HP_DISK_VALUE'			=> $hp_disk,
					'HP_DESCRIPTION_VALUE'	=> stripslashes($description),
					'HP_PRICE'				=> $price,
					'HP_SETUPFEE'			=> $setup_fee,
					'HP_CURRENCY'			=> stripslashes($value),
					'READONLY'				=> $readonly,
					'DISBLED'				=> $disabled,
					'HP_PAYMENT'			=> stripslashes($payment)
			)
	);

	$tpl->assign(
			array(
					'TR_PHP_YES'	=> ($hp_php == '_yes_') ? 'checked="checked"' : '',
					'TR_PHP_NO'		=> ($hp_php == '_no_')	? 'checked="checked"' : '',
					'TR_CGI_YES'	=> ($hp_cgi == '_yes_') ? 'checked="checked"' : '',
					'TR_CGI_NO'		=> ($hp_cgi == '_no_') ? 'checked="checked"' : '',
					'TR_DNS_YES'	=> ($hp_dns == '_yes_') ? 'checked="checked"' : '',
					'TR_DNS_NO'		=> ($hp_dns == '_no_') ? 'checked="checked"' : '',
					'VL_BACKUPD'	=> ($hp_backup == '_dmn_') ? 'checked="checked"' : '',
					'VL_BACKUPS'	=> ($hp_backup == '_sql_') ? 'checked="checked"' : '',
					'VL_BACKUPF'	=> ($hp_backup == '_full_') ? 'checked="checked"' : '',
					'VL_BACKUPN'	=> ($hp_backup == '_no_') ? 'checked="checked"' : '',
					'TR_STATUS_YES'	=> ($status) ? 'checked="checked"' : '',
					'TR_STATUS_NO'	=> (!$status) ? 'checked="checked"' : '',
			)
	);
} // end of gen_load_ehp_page()

/**
 * Check correction of input data
 */
function check_data_iscorrect(&$tpl) {
	global $hp_name, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk;
	global $hpid;
	global $price, $setup_fee;
	global $hp_backup, $hp_dns;

	$ahp_error		= "_off_";
	$hp_name		= clean_input($_POST['hp_name']);
	$hp_sub			= clean_input($_POST['hp_sub']);
	$hp_als			= clean_input($_POST['hp_als']);
	$hp_mail		= clean_input($_POST['hp_mail']);
	$hp_ftp			= clean_input($_POST['hp_ftp']);
	$hp_sql_db		= clean_input($_POST['hp_sql_db']);
	$hp_sql_user	= clean_input($_POST['hp_sql_user']);
	$hp_traff		= clean_input($_POST['hp_traff']);
	$hp_disk		= clean_input($_POST['hp_disk']);
	$price			= clean_input($_POST['hp_price']);
	$setup_fee		= clean_input($_POST['hp_setupfee']);

	if (isset($_SESSION['hpid']))
	{
		$hpid = $_SESSION['hpid'];
	}
	else
	{
		$ahp_error = tr('Undefined reference to data!');
	}
	
	// put hosting plan id into session value
	$_SESSION['hpid'] = $hpid;
	
	// Get values from previous page and check him correction
	if (isset($_POST['php'])) {
		$hp_php = $_POST['php'];
	}

	if (isset($_POST['cgi'])) {
		$hp_cgi = $_POST['cgi'];
	}

	if (isset($_POST['dns'])) {
		$hp_dns = $_POST['dns'];
	}

    if (isset($_POST['backup'])) {
    	$hp_backup = $_POST['backup'];
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
	} else if (!is_numeric($price)) {
		$ahp_error = tr('Price must be a number!');
	} else if (!is_numeric($setup_fee)) {
		$ahp_error = tr('Setup fee must be a number!');
	}

	if ($ahp_error == '_off_') {
		$tpl->assign('MESSAGE', '');
		return true;
	} else {
		set_page_message($ahp_error);
		return false;
	}
} // end of check_data_iscorrect()

/**
 * Add new host plan to DB
 */
function save_data_to_db() {

	global $tpl;
	global $hp_name, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk;
	global $hpid;
	global $hp_backup, $hp_dns;
	
	$sql = Database::getInstance();

	$err_msg		= '';
	$description	= clean_input($_POST['hp_description']);
	$price			= clean_input($_POST['hp_price']);
	$setup_fee		= clean_input($_POST['hp_setupfee']);
	$currency		= clean_input($_POST['hp_currency']);
	$payment		= clean_input($_POST['hp_payment']);
	$status			= clean_input($_POST['status']);

	$hp_props = "$hp_php;$hp_cgi;$hp_sub;$hp_als;$hp_mail;$hp_ftp;$hp_sql_db;$hp_sql_user;$hp_traff;$hp_disk;$hp_backup;$hp_dns";

	$admin_id = $_SESSION['user_id'];

	if (reseller_limits_check($sql, $err_msg, $admin_id, $hpid, $hp_props)) {

		if (!empty($err_msg)) {
			set_page_message($err_msg);
			restore_form($tpl, $sql);
			return false;
		} else {
			$query = "
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
			";

			$res = exec_query($sql, $query, array($hp_name, $description, $hp_props, $price,
				$setup_fee, $currency, $payment, $status, $hpid));

			$_SESSION['hp_updated'] = '_yes_';
			user_goto('hosting_plan.php');
		}

	} else {
		set_page_message(tr("Hosting plan values exceed reseller maximum values!"));
		return false;
	}
} // end of save_data_to_db()

die();
