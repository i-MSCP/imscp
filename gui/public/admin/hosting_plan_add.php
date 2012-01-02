<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2011 by i-MSCP | http://i-mscp.net
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 */

/************************************************************************************
 * Script functions
 */

/**
 * Generates empty form.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return void
 */
function admin_generateEmptyForm($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlChecked = $cfg->HTML_CHECKED;

	$tpl->assign(
		array(
			 'HP_NAME_VALUE' => '',
			 'TR_MAX_SUB_LIMITS' => '',
			 'TR_MAX_ALS_VALUES' => '',
			 'HP_MAIL_VALUE' => '',
			 'HP_FTP_VALUE' => '',
			 'HP_SQL_DB_VALUE' => '',
			 'HP_SQL_USER_VALUE' => '',
			 'HP_TRAFF_VALUE' => '',
			 'HP_PRICE' => '',
			 'HP_SETUPFEE' => '',
			 'HP_VELUE' => '',
			 'HP_PAYMENT' => '',
			 'HP_DESCRIPTION_VALUE' => '',
			 'HP_DISK_VALUE' => '',
			 'TR_PHP_YES' => '',
			 'TR_PHP_NO' => $htmlChecked,
			 'TR_CGI_YES' => '',
			 'TR_CGI_NO' => $htmlChecked,
			 'VL_SOFTWAREY' => '',
			 'VL_SOFTWAREN' => $htmlChecked,
			 'VL_BACKUPD' => '',
			 'VL_BACKUPS' => '',
			 'VL_BACKUPF' => '',
			 'VL_BACKUPN' => $htmlChecked,
			 'TR_DNS_YES' => '',
			 'TR_DNS_NO' => $htmlChecked,
			 'TR_STATUS_YES' => $htmlChecked,
			 'TR_STATUS_NO' => '',
			 'HP_TOS_VALUE' => ''));
}

/**
 * Generates form on error.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return void
 */
function admin_generateOnErrorForm($tpl)
{
	global $hpName, $description, $hpPhp, $hpCgi, $hpSub, $hpAls, $hpMail, $hpFtp,
		$hpSqlDb, $hpSqlUser, $hpTraff, $hp_disk, $price, $setupFee, $value, $payment,
		$status, $hp_backup, $hpDns, $hpAllowSoftware, $tos;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$tpl->assign(
		array(
			 'HP_NAME_VALUE' => tohtml($hpName),
			 'TR_MAX_SUB_LIMITS' => tohtml($hpSub),
			 'TR_MAX_ALS_VALUES' => tohtml($hpAls),
			 'HP_MAIL_VALUE' => tohtml($hpMail),
			 'HP_FTP_VALUE' => tohtml($hpFtp),
			 'HP_SQL_DB_VALUE' => tohtml($hpSqlDb),
			 'HP_SQL_USER_VALUE' => tohtml($hpSqlUser),
			 'HP_TRAFF_VALUE' => tohtml($hpTraff),
			 'HP_DISK_VALUE' => tohtml($hp_disk),
			 'HP_DESCRIPTION_VALUE' => tohtml($description),
			 'HP_PRICE' => tohtml($price),
			 'HP_SETUPFEE' => tohtml($setupFee),
			 'HP_VELUE' => tohtml($value),
			 'HP_PAYMENT' => tohtml($payment),
			 'HP_TOS_VALUE' => tohtml($tos)));

	$htmlChecked = $cfg->HTML_CHECKED;

	$tpl->assign(
		array(
			 'TR_PHP_YES' => ($hpPhp == '_yes_') ? $htmlChecked : '',
			 'TR_PHP_NO' => ($hpPhp == '_no_') ? $htmlChecked : '',
			 'VL_SOFTWAREY' => ($hpAllowSoftware == '_yes_') ? $htmlChecked : '',
			 'VL_SOFTWAREN' => ($hpAllowSoftware == '_no_') ? $htmlChecked : '',
			 'TR_CGI_YES' => ($hpCgi == '_yes_') ? $htmlChecked : '',
			 'TR_CGI_NO' => ($hpCgi == '_no_') ? $htmlChecked : '',
			 'VL_BACKUPD' => ($hp_backup == '_dmn_') ? $htmlChecked : '',
			 'VL_BACKUPS' => ($hp_backup == '_sql_') ? $htmlChecked : '',
			 'VL_BACKUPF' => ($hp_backup == '_full_') ? $htmlChecked : '',
			 'VL_BACKUPN' => ($hp_backup == '_no_') ? $htmlChecked : '',
			 'TR_DNS_YES' => ($hpDns == '_yes_') ? $htmlChecked : '',
			 'TR_DNS_NO' => ($hpDns == '_no_') ? $htmlChecked : '',
			 'TR_STATUS_YES' => ($status) ? $htmlChecked : '',
			 'TR_STATUS_NO' => (!$status) ? $htmlChecked : ''));
}

/**
 * Validates input data.
 *
 * @return bool TRUE if data are valid, FALSE otherwise
 */
function admin_validateInputData()
{
	global $hpName, $description, $hpPhp, $hpCgi, $hpSub, $hpAls, $hpMail, $hpFtp,
		$hpSqlDb, $hpSqlUser, $hpTraff, $hpDisk, $price, $setupFee, $value, $payment,
		$status, $hpBackup, $hpDns, $hpAllowSoftware, $tos;

	$hpName = clean_input($_POST['hp_name']);
	$hpSub = clean_input($_POST['hp_sub']);
	$hpAls = clean_input($_POST['hp_als']);
	$hpMail = clean_input($_POST['hp_mail']);
	$hpFtp = clean_input($_POST['hp_ftp']);
	$hpSqlDb = clean_input($_POST['hp_sql_db']);
	$hpSqlUser = clean_input($_POST['hp_sql_user']);
	$hpTraff = clean_input($_POST['hp_traff']);
	$hpDisk = clean_input($_POST['hp_disk']);
	$description = clean_input($_POST['hp_description']);
	$value = clean_input($_POST['hp_value']);
	$payment = clean_input($_POST['hp_payment']);
	$status = $_POST['status'];
	$tos = clean_input($_POST['hp_tos']);

	if (empty($_POST['hp_price'])) {
		$price = 0;
	} else {
		$price = clean_input($_POST['hp_price']);
	}

	if (empty($_POST['hp_setupfee'])) {
		$setupFee = 0;
	} else {
		$setupFee = clean_input($_POST['hp_setupfee']);
	}

	if (isset($_POST['php'])) {
		$hpPhp = $_POST['php'];
	}

	if (isset($_POST['cgi'])) {
		$hpCgi = $_POST['cgi'];
	}

	if (isset($_POST['dns'])) {
		$hpDns = $_POST['dns'];
	}

	if (isset($_POST['software_allowed'])) {
		$hpAllowSoftware = $_POST['software_allowed'];
	}

	if (isset($_POST['backup'])) {
		$hpBackup = $_POST['backup'];
	}

	if ($hpName == '') {
		set_page_message(tr('Incorrect length for hosting plan name .'), 'error');
	}

	if ($description == '') {
		set_page_message(tr('Incorrect description length .'), 'error');
	}

	if (!is_numeric($price)) {
		set_page_message(tr('Incorrect price syntax.'), 'error');
	}

	if (!is_numeric($setupFee)) {
		set_page_message(tr('Incorrect setup fee syntax.'), 'error');
	}

	if (!imscp_limit_check($hpSub, -1)) {
		set_page_message(tr('Incorrect subdomains limit.'), 'error');
	}

	if (!imscp_limit_check($hpAls, -1)) {
		set_page_message(tr('Incorrect aliases limit.'), 'error');
	}

	if (!imscp_limit_check($hpMail, -1)) {
		set_page_message(tr('Incorrect mail accounts limit.'), 'error');
	}

	if (!imscp_limit_check($hpFtp, -1)) {
		set_page_message(tr('Incorrect FTP accounts limit.'), 'error');
	}

	if (!imscp_limit_check($hpSqlUser, -1)) {
		set_page_message(tr('Incorrect SQL databases limit.'), 'error');
	}

	if (!imscp_limit_check($hpSqlDb, -1)) {
		set_page_message(tr('Incorrect SQL users limit.'), 'error');
	}

	if (!imscp_limit_check($hpTraff, null)) {
		set_page_message(tr('Incorrect traffic limit.'), 'error');
	}

	if (!imscp_limit_check($hpDisk, null)) {
		set_page_message(tr('Incorrect disk quota limit.'), 'error');
	}

	if ($hpPhp == '_no_' && $hpAllowSoftware == '_yes_') {
		set_page_message(tr('The i-MSCP application installer needs the PHP feature.'), 'error');
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		return true;
	} else {
		return false;
	}
}

/**
 * Add new hosting plan to database.
 *
 * @param int $adminId Administrator unique identifier
 * @return void
 */
function admin_saveDataToDatabase($adminId)
{
	global $hpName, $description, $hpPhp, $hpCgi, $hpSub, $hpAls, $hpMail, $hpFtp,
		$hpSqlDb, $hpSqlUser, $hpTraff, $hpDisk, $price, $setupFee, $value, $payment,
		$status, $hpBackup, $hpDns, $hpAllowSoftware, $tos;

	$query = "
		SELECT
			`t1`.`id`, `t1`.`name`, `t1`.`reseller_id`, `t1`.`name`, `t1`.`props`,
			`t1`.`status`, `t2`.`admin_id`, `t2`.`admin_type`
		FROM
			`hosting_plans` `t1`,
			`admin` `t2`
		WHERE
			`t2`.`admin_type` = ?
		AND
			`t1`.`reseller_id` = `t2`.`admin_id`
		AND
			`t1`.`name` = ?
	";
	$stmt = exec_query($query, array('admin', $hpName));

	if ($stmt->rowCount()) {
		set_page_message(tr('Hosting plan with same name already exists.'), 'error');
	} else {
		$hp_props = "$hpPhp;$hpCgi;$hpSub;$hpAls;$hpMail;$hpFtp;$hpSqlDb;";
		$hp_props .= "$hpSqlUser;$hpTraff;$hpDisk;$hpBackup;$hpDns;$hpAllowSoftware";

		$query = "
			INSERT INTO
				hosting_plans(
					`reseller_id`, `name`, `description`, `props`, `price`, `setup_fee`,
					`value`, `payment`, `status`, `tos`
				) VALUES (
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				)
		";

		exec_query($query, array(
								$adminId, $hpName, $description, $hp_props, $price,
								$setupFee, $value, $payment, $status, $tos));

		set_page_message(tr('Hosting plan created.'), 'success');
		redirectTo('hosting_plan.php');
	}
}

/************************************************************************************
 * Main script
 */

// Incude core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (strtolower($cfg->HOSTING_PLANS_LEVEL) != 'admin') {
	redirectTo('index.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		 'layout' => 'shared/layouts/ui.tpl',
		 'page' => 'admin/hosting_plan_add.tpl',
		 'page_message' => 'layout'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Administrator/Add hosting plan'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_ADD_HOSTING_PLAN' => tr('Add hosting plan'),
		 'TR_HOSTING PLAN PROPS' => tr('Hosting plan properties'),
		 'TR_TEMPLATE_NAME' => tr('Hosting plan name'),
		 'TR_MAX_SUBDOMAINS' => tr('Max subdomains<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_ALIASES' => tr('Max aliases<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_MAILACCOUNTS' => tr('Mail accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_FTP' => tr('FTP accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_SQL' => tr('SQL databases limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_SQL_USERS' => tr('SQL users limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_TRAFFIC' => tr('Traffic limit [MB]<br><i>(0 unlimited)</i>'),
		 'TR_DISK_LIMIT' => tr('Disk limit [MB]<br><i>(0 unlimited)</i>'),
		 'TR_PHP' => tr('PHP'),
		 'TR_SOFTWARE_SUPP' => tr('i-MSCP application installer'),
		 'TR_CGI' => tr('CGI / Perl'),
		 'TR_DNS' => tr('Custom DNS records support'),
		 'TR_BACKUP' => tr('Backup'),
		 'TR_BACKUP_DOMAIN' => tr('Domain'),
		 'TR_BACKUP_SQL' => tr('SQL'),
		 'TR_BACKUP_FULL' => tr('Full'),
		 'TR_BACKUP_NO' => tr('No'),
		 'TR_APACHE_LOGS' => tr('Apache logfiles'),
		 'TR_AWSTATS' => tr('AwStats'),
		 'TR_YES' => tr('yes'),
		 'TR_NO' => tr('no'),
		 'TR_BILLING_PROPS' => tr('Billing Settings'),
		 'TR_PRICE' => tr('Price'),
		 'TR_SETUP_FEE' => tr('Setup fee'),
		 'TR_VALUE' => tr('Currency'),
		 'TR_PAYMENT' => tr('Payment period'),
		 'TR_STATUS' => tr('Available for purchasing'),
		 'TR_TEMPLATE_DESCRIPTON' => tr('Description'),
		 'TR_EXAMPLE' => tr('(e.g. EUR)'),
		 'TR_TOS_PROPS' => tr('Term Of Service'),
		 'TR_TOS_NOTE' => tr('<b>Optional:</b> Leave this field empty if you do not want term of service for this hosting plan.'),
		 'TR_TOS_DESCRIPTION' => tr('Text Only'),
		 'TR_ADD_PLAN' => tr('Add plan')));

if (isset($_POST['uaction']) && ('add_plan' === $_POST['uaction'])) {
	if (admin_validateInputData()) {
		admin_saveDataToDatabase($_SESSION['user_id']);
	}

	admin_generateOnErrorForm($tpl);
} else {
	admin_generateEmptyForm($tpl);
}

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();
