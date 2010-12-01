<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-msCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

require '../include/imscp-lib.php';

check_login(__FILE__);

$cfg = iMSCP_Registry::get('Config');

if (isset($cfg->HOSTING_PLANS_LEVEL)
	&& $cfg->HOSTING_PLANS_LEVEL === 'admin') {
		user_goto('hosting_plan.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->RESELLER_TEMPLATE_PATH . '/hosting_plan_add.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('subdomain_add', 'page');
$tpl->define_dynamic('alias_add', 'page');
$tpl->define_dynamic('mail_add', 'page');
$tpl->define_dynamic('ftp_add', 'page');
$tpl->define_dynamic('sql_db_add', 'page');
$tpl->define_dynamic('sql_user_add', 'page');
$tpl->define_dynamic('t_software_support', 'page');

$tpl->assign(
	array(
		'TR_RESELLER_MAIN_INDEX_PAGE_TITLE'	=> tr('i-MSCP - Reseller/Add hosting plan'),
		'THEME_COLOR_PATH'					=> "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'						=> tr('encoding'),
		'ISP_LOGO'							=> get_logo($_SESSION['user_id'])
	)
);

/*
 *
 * static page messages.
 *
 */

gen_reseller_mainmenu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/main_menu_hosting_plan.tpl');
gen_reseller_menu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/menu_hosting_plan.tpl');

gen_logged_from($tpl);

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
		'TR_SOFTWARE_SUPP'			=> tr('i-MSCP application installer'),
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
		// BEGIN TOS
		'TR_TOS_PROPS'				=> tr('Term Of Service'),
		'TR_TOS_NOTE'				=> tr('<b>Optional:</b> Leave this field empty if you do not want term of service for this hosting plan.'),
		'TR_TOS_DESCRIPTION'		=> tr('Text Only'),
		// END TOS
		'TR_ADD_PLAN'				=> tr('Add plan')
	)
);

if (isset($_POST['uaction']) && ('add_plan' === $_POST['uaction'])) {
	// Process data
	if (check_data_correction($tpl)) {
		save_data_to_db($tpl, $_SESSION['user_id']);
	}

	gen_data_ahp_page($tpl);
} else {
	gen_empty_ahp_page($tpl);
}

get_reseller_software_permission ($tpl,$sql,$_SESSION['user_id']);
gen_page_message($tpl);

list(
	$rsub_max,
	$rals_max,
	$rmail_max,
	$rftp_max,
	$rsql_db_max,
	$rsql_user_max
	) = check_reseller_permissions($_SESSION['user_id'], 'all_permissions');

if ($rsub_max       == "-1") $tpl->assign('ALIAS_ADD', '');
if ($rals_max       == "-1") $tpl->assign('SUBDOMAIN_ADD', '');
if ($rmail_max      == "-1") $tpl->assign('MAIL_ADD', '');
if ($rftp_max       == "-1") $tpl->assign('FTP_ADD', '');
if ($rsql_db_max    == "-1") $tpl->assign('SQL_DB_ADD', '');
if ($rsql_user_max  == "-1") $tpl->assign('SQL_USER_ADD', '');

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

// Function definitions

/**
 * Generate empty form
 */
function gen_empty_ahp_page(&$tpl) {
	$cfg = iMSCP_Registry::get('Config');

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
			'TR_PHP_YES'			=> '',
			'TR_PHP_NO'				=> $cfg->HTML_CHECKED,
			'VL_SOFTWAREY'			=> '',
			'VL_SOFTWAREN'			=> $cfg->HTML_CHECKED,
			'TR_CGI_YES'			=> '',
			'TR_CGI_NO'				=> $cfg->HTML_CHECKED,
			'VL_BACKUPD'			=> '',
			'VL_BACKUPS'			=> '',
			'VL_BACKUPF'			=> '',
			'VL_BACKUPN'			=> $cfg->HTML_CHECKED,
			'TR_DNS_YES'			=> '',
			'TR_DNS_NO'				=> $cfg->HTML_CHECKED,
			'HP_DISK_VALUE'			=> '',
			'TR_STATUS_YES'			=> $cfg->HTML_CHECKED,
			'TR_STATUS_NO'			=> '',
			'HP_TOS_VALUE'			=> ''
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
	global $hp_backup, $hp_dns, $hp_allowsoftware;
	global $tos;

	$cfg = iMSCP_Registry::get('Config');

	$tpl->assign(
		array(
			'HP_NAME_VALUE'			=> tohtml($hp_name),
			'TR_MAX_SUB_LIMITS'		=> tohtml($hp_sub),
			'TR_MAX_ALS_VALUES'		=> tohtml($hp_als),
			'HP_MAIL_VALUE'			=> tohtml($hp_mail),
			'HP_FTP_VALUE'			=> tohtml($hp_ftp),
			'HP_SQL_DB_VALUE'		=> tohtml($hp_sql_db),
			'HP_SQL_USER_VALUE'		=> tohtml($hp_sql_user),
			'HP_TRAFF_VALUE'		=> tohtml($hp_traff),
			'HP_DISK_VALUE'			=> tohtml($hp_disk),
			'HP_DESCRIPTION_VALUE'	=> tohtml($description),
			'HP_PRICE'				=> tohtml($price),
			'HP_SETUPFEE'			=> tohtml($setup_fee),
			'HP_VELUE'				=> tohtml($value),
			'HP_PAYMENT'			=> tohtml($payment),
			'HP_TOS_VALUE'			=> tohtml($tos)
		)
	);

	$tpl->assign(
		array(
			'TR_PHP_YES'	=> ($hp_php == '_yes_') ? $cfg->HTML_CHECKED : '',
			'TR_PHP_NO'		=> ($hp_php == '_no_') ? $cfg->HTML_CHECKED : '',
			'VL_SOFTWAREY'	=> ($hp_allowsoftware == '_yes_') ? $cfg->HTML_CHECKED : '',
			'VL_SOFTWAREN'	=> ($hp_allowsoftware == '_no_') ? $cfg->HTML_CHECKED : '',
			'TR_CGI_YES'	=> ($hp_cgi == '_yes_') ? $cfg->HTML_CHECKED : '',
			'TR_CGI_NO'		=> ($hp_cgi == '_no_') ? $cfg->HTML_CHECKED : '',
			'VL_BACKUPD'	=> ($hp_backup == '_dmn_') ? $cfg->HTML_CHECKED : '',
			'VL_BACKUPS'	=> ($hp_backup == '_sql_') ? $cfg->HTML_CHECKED : '',
			'VL_BACKUPF'	=> ($hp_backup == '_full_') ? $cfg->HTML_CHECKED : '',
			'VL_BACKUPN'	=> ($hp_backup == '_no_') ? $cfg->HTML_CHECKED : '',
			'TR_DNS_YES'	=> ($hp_dns == '_yes_') ? $cfg->HTML_CHECKED : '',
			'TR_DNS_NO'		=> ($hp_dns == '_no_') ? $cfg->HTML_CHECKED : '',
			'TR_STATUS_YES'	=> ($status) ? $cfg->HTML_CHECKED : '',
			'TR_STATUS_NO'	=> (!$status) ? $cfg->HTML_CHECKED : ''
		)
	);

} // end of gen_data_ahp_page()

/**
 * Check correction of input data
 */
function check_data_correction(&$tpl) {
	global $hp_name, $description, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk;
	global $price, $setup_fee, $value, $payment, $status;
	global $hp_backup, $hp_dns, $hp_allowsoftware;
	global $tos;

	$ahp_error 		= array();

	$hp_name		= clean_input($_POST['hp_name']);
	$hp_sub			= clean_input($_POST['hp_sub']);
	$hp_als			= clean_input($_POST['hp_als']);
	$hp_mail		= clean_input($_POST['hp_mail']);
	$hp_ftp			= clean_input($_POST['hp_ftp']);
	$hp_sql_db		= clean_input($_POST['hp_sql_db']);
	$hp_sql_user	= clean_input($_POST['hp_sql_user']);
	$hp_traff		= clean_input($_POST['hp_traff']);
	$hp_disk		= clean_input($_POST['hp_disk']);
	$value			= clean_input($_POST['hp_value']);
	$payment		= clean_input($_POST['hp_payment']);
	$status			= $_POST['status'];
	$description	= clean_input($_POST['hp_description']);
	$tos			= clean_input($_POST['hp_tos']);

	if (empty($_POST['hp_price'])) {
		$price = 0;
	} else {
		$price = clean_input($_POST['hp_price']);
	}

	if (empty($_POST['hp_setupfee'])) {
		$setup_fee = 0;
	} else {
		$setup_fee = clean_input($_POST['hp_setupfee']);
	}

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
	
	(isset($_POST['software_allowed'])) ? $hp_allowsoftware = $_POST['software_allowed'] : $hp_allowsoftware = "_no_";
	if($hp_php == "_no_" && $hp_allowsoftware == "_yes_") {
		$ahp_error[] = tr('The i-MSCP application installer needs PHP to enable it!');
	}

	if ($hp_name == '') {
		$ahp_error[] = tr('Incorrect template name length!');
	}
	if ($description == '') {
		$ahp_error[] = tr('Incorrect template description length!');
	}
	if (!is_numeric($price)) {
		$ahp_error[] = tr('Price must be a number!');
	}
	if (!is_numeric($setup_fee)) {
		$ahp_error[] = tr('Setup fee must be a number!');
	}

	list(
		$rsub_max,
		$rals_max,
		$rmail_max,
		$rftp_max,
		$rsql_db_max,
		$rsql_user_max
		) = check_reseller_permissions($_SESSION['user_id'], 'all_permissions');

	if ($rsub_max == "-1") {
		$hp_sub = "-1";
	} elseif (!imscp_limit_check($hp_sub, -1)) {
		$ahp_error[] = tr('Incorrect subdomains limit!');
	}

	if ($rals_max == "-1") {
		$hp_als = "-1";
	} elseif (!imscp_limit_check($hp_als, -1)) {
		$ahp_error[] = tr('Incorrect aliases limit!');
	}

	if ($rmail_max == "-1") {
		$hp_mail = "-1";
	} elseif (!imscp_limit_check($hp_mail, -1)) {
		$ahp_error[] = tr('Incorrect mail accounts limit!');
	}

	if ($rftp_max == "-1") {
		$hp_ftp = "-1";
	} elseif (!imscp_limit_check($hp_ftp, -1)) {
		$ahp_error[] = tr('Incorrect FTP accounts limit!');
	}

	if ($rsql_db_max == "-1") {
		$hp_sql_db = "-1";
	} elseif (!imscp_limit_check($hp_sql_db, -1)) {
		$ahp_error[] = tr('Incorrect SQL users limit!');
	} else if ($hp_sql_user != -1 && $hp_sql_db == -1) {
		$ahp_error[] = tr('SQL users limit is <i>disabled</i>!');
	}

	if ($rsql_user_max == "-1") {
		$hp_sql_user = "-1";
	} elseif (!imscp_limit_check($hp_sql_user, -1)) {
		$ahp_error[] = tr('Incorrect SQL databases limit!');
	} else if ($hp_sql_user == -1 && $hp_sql_db != -1) {
		$ahp_error[] = tr('SQL databases limit is not <i>disabled</i>!');
	}

	if (!imscp_limit_check($hp_traff, null)) {
		$ahp_error[] = tr('Incorrect traffic limit!');
	}
	if (!imscp_limit_check($hp_disk, null)) {
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
	global $hp_backup, $hp_dns, $hp_allowsoftware;
	global $tos;

	$sql = iMSCP_Registry::get('Db');
	$err_msg = '';

	$query = "SELECT `id` FROM `hosting_plans` WHERE `name` = ? AND `reseller_id` = ?";
	$res = exec_query($sql, $query, array($hp_name, $admin_id));

	if ($res->rowCount() == 1) {
		$tpl->assign('MESSAGE', tr('Hosting plan with entered name already exists!'));
		// $tpl->parse('AHP_MESSAGE', 'ahp_message');
	} else {
		$hp_props = "$hp_php;$hp_cgi;$hp_sub;$hp_als;$hp_mail;$hp_ftp;$hp_sql_db;$hp_sql_user;$hp_traff;$hp_disk;$hp_backup;$hp_dns;$hp_allowsoftware";
		// this id is just for fake and is not used in reseller_limits_check.
		$hpid = 0;

		if (reseller_limits_check($sql, $err_msg, $admin_id, $hpid, $hp_props)) {
			if (!empty($err_msg)) {
				set_page_message($err_msg);
				return false;
			} else {
				$query = "
					INSERT INTO
						`hosting_plans`(
							`reseller_id`,
							`name`,
							`description`,
							`props`,
							`price`,
							`setup_fee`,
							`value`,
							`payment`,
							`status`,
							`tos`
						)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
				";

				$res = exec_query($sql, $query, array($admin_id, $hp_name, $description, $hp_props, $price, $setup_fee, $value, $payment, $status, $tos));

				$_SESSION['hp_added'] = '_yes_';
				user_goto('hosting_plan.php');
			}
		} else {
			set_page_message(tr("Hosting plan values exceed reseller maximum values!"));
			return false;
		}
	}
} // end of save_data_to_db()
