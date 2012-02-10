<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

/* @var $phpini iMSCP_PHPini */
$phpini = iMSCP_PHPini::getInstance();

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
	redirectTo('hosting_plan.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/hosting_plan_add.tpl',
		'page_message' => 'layout',
		'subdomain_add' => 'page',
		'alias_add' => 'page',
		'mail_add' => 'page',
		'ftp_add' => 'page',
		'sql_db_add' => 'page',
		'sql_user_add' => 'page',
		't_software_support' => 'page',
		't_phpini_system' => 'page',
		't_phpini_register_globals' => 'page',
		't_phpini_allow_url_fopen' => 'page',
		't_phpini_display_errors' => 'page',
		't_phpini_disable_functions' => 'page'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Reseller / Add hosting plan'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()));

generateNavigation($tpl);

$tpl->assign(
	array(
		 'TR_ADD_HOSTING_PLAN' => tr('Add hosting plan'),
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
		 'TR_SOFTWARE_SUPP' => tr('Softwares installer'),
		 'TR_CGI' => tr('CGI'),
		 'TR_DNS' => tr('Custom DNS records'),
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
		 // BEGIN TOS
		 'TR_TOS_PROPS' => tr('Term Of Service'),
		 'TR_TOS_NOTE' => tr('<b>Optional:</b> Leave this field empty if you do not want term of service for this hosting plan.'),
		 'TR_TOS_DESCRIPTION' => tr('Text Only'),
		 // END TOS
		 'TR_PHPINI_SYSTEM' => tr('PHP Editor'),
		 'TR_USER_EDITABLE_EXEC' => tr('Only exec'),
		 'TR_PHPINI_AL_REGISTER_GLOBALS' => tr('Can edit the PHP %s directive', true, '<span class="bold">register_globals</span>'),
		 'TR_PHPINI_AL_ALLOW_URL_FOPEN' => tr('Can edit the PHP %s directive', true, '<span class="bold">allow_url_fopen</span>'),
		 'TR_PHPINI_AL_DISPLAY_ERRORS' => tr('Can edit the PHP %s directive', true, '<span class="bold">display_errors</span>'),
		 'TR_PHPINI_AL_DISABLE_FUNCTIONS' => tr('Can edit the PHP %s directive', true, '<span class="bold">disable_functions</span>'),
		 'TR_PHPINI_POST_MAX_SIZE' => tr('PHP %s directive', true, '<span class="bold">post_max_size</span>'),
		 'TR_PHPINI_UPLOAD_MAX_FILESIZE' => tr('PHP %s directive', true, '<span class="bold">upload_max_filezize</span>'),
		 'TR_PHPINI_MAX_EXECUTION_TIME' => tr('PHP %s directive', true, '<span class="bold">max_execution_time</span>'),
		 'TR_PHPINI_MAX_INPUT_TIME' => tr('PHP %s directive', true, '<span class="bold">max_input_time</span>'),
		 'TR_PHPINI_MEMORY_LIMIT' => tr('PHP %s directive', true, '<span class="bold">memory_limit</span>'),
		 'TR_MIB' => tr('MiB'),
		 'TR_SEC' => tr('Sec.'),
		 'TR_ADD_PLAN' => tr('Add plan')));

$phpini->loadRePerm($_SESSION['user_id']);

if (isset($_POST['uaction']) && ('add_plan' === $_POST['uaction'])) {
	// Process data
	if (check_data_correction($tpl, $phpini)) {
		save_data_to_db($tpl, $_SESSION['user_id'], $phpini);
	}

	gen_data_ahp_page($tpl, $phpini);
} else {
	gen_empty_ahp_page($tpl, $phpini);
}

if ($phpini->checkRePerm('phpiniSystem')) { //if reseller has permission to use php.ini feature
	if ($phpini->checkRePerm('phpiniRegisterGlobals')) {
		$tpl->parse('T_PHPINI_REGISTER_GLOBALS', 't_phpini_register_globals');
	} else {
		$tpl->assign(array('T_PHPINI_REGISTER_GLOBALS' => ''));
		$tpl->assign(array('PHPINI_AL_REGISTER_GLOBALS_YES' => '', 'PHPINI_AL_REGISTER_GLOBALS_NO' => $cfg->HTML_CHECKED));
	}
	if ($phpini->checkRePerm('phpiniAllowUrlFopen')) {
		$tpl->parse('T_PHPINI_ALLOW_URL_FOPEN', 't_phpini_allow_url_fopen');
	} else {
		$tpl->assign(array('T_PHPINI_ALLOW_URL_FOPEN' => ''));
		$tpl->assign(array('PHPINI_AL_ALLOW_URL_FOPEN_YES' => '', 'PHPINI_AL_ALLOW_URL_FOPEN_NO' => $cfg->HTML_CHECKED));
	}
	if ($phpini->checkRePerm('phpiniDisplayErrors')) {
		$tpl->parse('T_PHPINI_DISPLAY_ERRORS', 't_phpini_display_errors');
	} else {
		$tpl->assign(array('T_PHPINI_DISPLAY_ERRORS' => ''));
		$tpl->assign(array('PHPINI_AL_DISPLAY_ERRORS_YES' => '', 'PHPINI_AL_DISPLAY_ERRORS_NO' => $cfg->HTML_CHECKED));
	}
	if ($phpini->checkRePerm('phpiniDisableFunctions')) {
		$tpl->parse('T_PHPINI_DISABLE_FUNCTIONS', 't_phpini_disable_functions');
	} else {
		$tpl->assign(array('T_PHPINI_DISABLE_FUNCTIONS' => ''));
		$tpl->assign(array('PHPINI_AL_DISABLE_FUNCTIONS_YES' => '',
						  'PHPINI_AL_DISABLE_FUNCTIONS_NO' => $cfg->HTML_CHECKED,
						  'PHPINI_AL_DISABLE_FUNCTIONS_EXEC' => ''));
	}


} else { //if no permission at all
	$tpl->assign(array('T_PHPINI_SYSTEM' => ''));
	$tpl->assign(array('PHPINI_SYSTEM_YES' => '', 'PHPINI_SYSTEM_NO' => $cfg->HTML_CHECKED));
}

generatePageMessage($tpl);

list(
	$rsub_max,
	$rals_max,
	$rmail_max,
	$rftp_max,
	$rsql_db_max,
	$rsql_user_max
	) = check_reseller_permissions($_SESSION['user_id'], 'all_permissions');

if ($rsub_max == "-1") $tpl->assign('ALIAS_ADD', '');
if ($rals_max == "-1") $tpl->assign('SUBDOMAIN_ADD', '');
if ($rmail_max == "-1") $tpl->assign('MAIL_ADD', '');
if ($rftp_max == "-1") $tpl->assign('FTP_ADD', '');
if ($rsql_db_max == "-1") $tpl->assign('SQL_DB_ADD', '');
if ($rsql_user_max == "-1") $tpl->assign('SQL_USER_ADD', '');

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

// Function definitions

/**
 * Generate empty form
 */
function gen_empty_ahp_page($tpl, $phpini)
{
	$cfg = iMSCP_Registry::get('config');

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
			 'TR_PHP_YES' => '',
			 'TR_PHP_NO' => $cfg->HTML_CHECKED,
			 'VL_SOFTWAREY' => '',
			 'VL_SOFTWAREN' => $cfg->HTML_CHECKED,
			 'TR_CGI_YES' => '',
			 'TR_CGI_NO' => $cfg->HTML_CHECKED,
			 'VL_BACKUPD' => '',
			 'VL_BACKUPS' => '',
			 'VL_BACKUPF' => '',
			 'VL_BACKUPN' => $cfg->HTML_CHECKED,
			 'TR_DNS_YES' => '',
			 'TR_DNS_NO' => $cfg->HTML_CHECKED,
			 'HP_DISK_VALUE' => '',
			 'TR_STATUS_YES' => $cfg->HTML_CHECKED,
			 'TR_STATUS_NO' => '',
			 'HP_TOS_VALUE' => '',
			 'PHPINI_SYSTEM_YES' => $cfg->HTML_CHECKED,
			 'PHPINI_SYSTEM_NO' => '',
			 'PHPINI_AL_REGISTER_GLOBALS_YES' => $cfg->HTML_CHECKED,
			 'PHPINI_AL_REGISTER_GLOBALS_NO' => '',
			 'PHPINI_AL_ALLOW_URL_FOPEN_YES' => $cfg->HTML_CHECKED,
			 'PHPINI_AL_ALLOW_URL_FOPEN_NO' => '',
			 'PHPINI_AL_DISPLAY_ERRORS_YES' => $cfg->HTML_CHECKED,
			 'PHPINI_AL_DISPLAY_ERRORS_NO' => '',
			 'PHPINI_AL_DISABLE_FUNCTIONS_YES' => '',
			 'PHPINI_AL_DISABLE_FUNCTIONS_NO' => '',
			 'PHPINI_AL_DISABLE_FUNCTIONS_EXEC' => $cfg->HTML_CHECKED,
			 'PHPINI_POST_MAX_SIZE' => $phpini->getDataVal('phpiniPostMaxSize'), //Fill with default php.ini values
			 'PHPINI_UPLOAD_MAX_FILESIZE' => $phpini->getDataVal('phpiniUploadMaxFileSize'),
			 'PHPINI_MAX_EXECUTION_TIME' => $phpini->getDataVal('phpiniMaxExecutionTime'),
			 'PHPINI_MAX_INPUT_TIME' => $phpini->getDataVal('phpiniMaxInputTime'),
			 'PHPINI_MEMORY_LIMIT' => $phpini->getDataVal('phpiniMemoryLimit'),

		)
	);
} // end of gen_empty_hp_page()

/**
 * Show last entered data for new hp
 */
function gen_data_ahp_page($tpl, $phpini)
{
	global $hp_name, $description, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk;
	global $price, $setup_fee, $value, $payment, $status;
	global $hp_backup, $hp_dns, $hp_allowsoftware;
	global $tos;

	/** @var $cfg iMSCP_pTemplate */
	$cfg = iMSCP_Registry::get('config');

	$tpl->assign(
		array(
			 'HP_NAME_VALUE' => tohtml($hp_name),
			 'TR_MAX_SUB_LIMITS' => tohtml($hp_sub),
			 'TR_MAX_ALS_VALUES' => tohtml($hp_als),
			 'HP_MAIL_VALUE' => tohtml($hp_mail),
			 'HP_FTP_VALUE' => tohtml($hp_ftp),
			 'HP_SQL_DB_VALUE' => tohtml($hp_sql_db),
			 'HP_SQL_USER_VALUE' => tohtml($hp_sql_user),
			 'HP_TRAFF_VALUE' => tohtml($hp_traff),
			 'HP_DISK_VALUE' => tohtml($hp_disk),
			 'HP_DESCRIPTION_VALUE' => tohtml($description),
			 'HP_PRICE' => tohtml($price),
			 'HP_SETUPFEE' => tohtml($setup_fee),
			 'HP_VELUE' => tohtml($value),
			 'HP_PAYMENT' => tohtml($payment),
			 'HP_TOS_VALUE' => tohtml($tos)
		)
	);

	$tpl->assign(
		array(
			 'TR_PHP_YES' => ($hp_php == '_yes_') ? $cfg->HTML_CHECKED : '',
			 'TR_PHP_NO' => ($hp_php == '_no_') ? $cfg->HTML_CHECKED : '',
			 'VL_SOFTWAREY' => ($hp_allowsoftware == '_yes_') ? $cfg->HTML_CHECKED
				 : '',
			 'VL_SOFTWAREN' => ($hp_allowsoftware == '_no_') ? $cfg->HTML_CHECKED
				 : '',
			 'TR_CGI_YES' => ($hp_cgi == '_yes_') ? $cfg->HTML_CHECKED : '',
			 'TR_CGI_NO' => ($hp_cgi == '_no_') ? $cfg->HTML_CHECKED : '',
			 'VL_BACKUPD' => ($hp_backup == '_dmn_') ? $cfg->HTML_CHECKED : '',
			 'VL_BACKUPS' => ($hp_backup == '_sql_') ? $cfg->HTML_CHECKED : '',
			 'VL_BACKUPF' => ($hp_backup == '_full_') ? $cfg->HTML_CHECKED : '',
			 'VL_BACKUPN' => ($hp_backup == '_no_') ? $cfg->HTML_CHECKED : '',
			 'TR_DNS_YES' => ($hp_dns == '_yes_') ? $cfg->HTML_CHECKED : '',
			 'TR_DNS_NO' => ($hp_dns == '_no_') ? $cfg->HTML_CHECKED : '',
			 'TR_STATUS_YES' => ($status) ? $cfg->HTML_CHECKED : '',
			 'TR_STATUS_NO' => (!$status) ? $cfg->HTML_CHECKED : '',
			 'PHPINI_SYSTEM_YES' => ($phpini->getClPermVal('phpiniSystem') == 'yes')
				 ? $cfg->HTML_CHECKED : '',
			 'PHPINI_SYSTEM_NO' => ($phpini->getClPermVal('phpiniSystem') != 'yes')
				 ? $cfg->HTML_CHECKED : '',
			 'PHPINI_AL_REGISTER_GLOBALS_YES' => ($phpini->getClPermVal('phpiniRegisterGlobals') == 'yes')
				 ? $cfg->HTML_CHECKED : '',
			 'PHPINI_AL_REGISTER_GLOBALS_NO' => ($phpini->getClPermVal('phpiniRegisterGlobals') != 'yes')
				 ? $cfg->HTML_CHECKED : '',
			 'PHPINI_AL_ALLOW_URL_FOPEN_YES' => ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'yes')
				 ? $cfg->HTML_CHECKED : '',
			 'PHPINI_AL_ALLOW_URL_FOPEN_NO' => ($phpini->getClPermVal('phpiniAllowUrlFopen') != 'yes')
				 ? $cfg->HTML_CHECKED : '',
			 'PHPINI_AL_DISPLAY_ERRORS_YES' => ($phpini->getClPermVal('phpiniDisplayErrors') == 'yes')
				 ? $cfg->HTML_CHECKED : '',
			 'PHPINI_AL_DISPLAY_ERRORS_NO' => ($phpini->getClPermVal('phpiniDisplayErrors') != 'yes')
				 ? $cfg->HTML_CHECKED : '',
			 'PHPINI_AL_DISABLE_FUNCTIONS_YES' => ($phpini->getClPermVal('phpiniDisableFunctions') == 'yes')
				 ? $cfg->HTML_CHECKED : '',
			 'PHPINI_AL_DISABLE_FUNCTIONS_NO' => ($phpini->getClPermVal('phpiniDisableFunctions') == 'no')
				 ? $cfg->HTML_CHECKED : '',
			 'PHPINI_AL_DISABLE_FUNCTIONS_EXEC' => ($phpini->getClPermVal('phpiniDisableFunctions') == 'exec')
				 ? $cfg->HTML_CHECKED : '',
			 'PHPINI_POST_MAX_SIZE' => $phpini->getDataVal('phpiniPostMaxSize'),
			 'PHPINI_UPLOAD_MAX_FILESIZE' => $phpini->getDataVal('phpiniUploadMaxFileSize'),
			 'PHPINI_MAX_EXECUTION_TIME' => $phpini->getDataVal('phpiniMaxExecutionTime'),
			 'PHPINI_MAX_INPUT_TIME' => $phpini->getDataVal('phpiniMaxInputTime'),
			 'PHPINI_MEMORY_LIMIT' => $phpini->getDataVal('phpiniMemoryLimit')
		)
	);

} // end of gen_data_ahp_page()

/**
 * Check correction of input data
 */
function check_data_correction($tpl, $phpini)
{
	global $hp_name, $description, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk;
	global $price, $setup_fee, $value, $payment, $status;
	global $hp_backup, $hp_dns, $hp_allowsoftware;
	global $tos;

	$hp_name = clean_input($_POST['hp_name']);
	$hp_sub = clean_input($_POST['hp_sub']);
	$hp_als = clean_input($_POST['hp_als']);
	$hp_mail = clean_input($_POST['hp_mail']);
	$hp_ftp = clean_input($_POST['hp_ftp']);
	$hp_sql_db = clean_input($_POST['hp_sql_db']);
	$hp_sql_user = clean_input($_POST['hp_sql_user']);
	$hp_traff = clean_input($_POST['hp_traff']);
	$hp_disk = clean_input($_POST['hp_disk']);
	$value = clean_input($_POST['hp_value']);
	$payment = clean_input($_POST['hp_payment']);
	$status = $_POST['status'];
	$description = clean_input($_POST['hp_description']);
	$tos = clean_input($_POST['hp_tos']);

	if ($phpini->checkRePerm('phpiniSystem') && isset($_POST['phpini_system'])) {
		$phpini->setClPerm('phpiniSystem', clean_input($_POST['phpini_system']));
	}

	if ($phpini->checkRePerm('phpiniRegisterGlobals') && isset($_POST['phpini_al_register_globals'])) {
		$phpini->setClPerm('phpiniRegisterGlobals', clean_input($_POST['phpini_al_register_globals']));
	}

	if ($phpini->checkRePerm('phpiniAllowUrlFopen') && isset($_POST['phpini_al_allow_url_fopen'])) {
		$phpini->setClPerm('phpiniAllowUrlFopen', clean_input($_POST['phpini_al_allow_url_fopen']));
	}

	if ($phpini->checkRePerm('phpiniDisplayErrors') && isset($_POST['phpini_al_display_errors'])) {
		$phpini->setClPerm('phpiniDisplayErrors', clean_input($_POST['phpini_al_display_errors']));
	}

	if ($phpini->checkRePerm('phpiniDisplayErrors') && isset($_POST['phpini_al_error_reporting'])) {
		$phpini->setClPerm('phpiniErrorReporting', clean_input($_POST['phpini_al_error_reporting']));
	}

	if ($phpini->checkRePerm('phpiniDisableFunctions') && isset($_POST['phpini_al_disable_functions'])) {
		$phpini->setClPerm('phpiniDisableFunctions', clean_input($_POST['phpini_al_disable_functions']));
	}

	//use phpini->phpiniData as datastore for the following values - should be better in something like hostingPlan class/object later
	if (isset($_POST['phpini_post_max_size']) && (!$phpini->setDataWithPermCheck('phpiniPostMaxSize', $_POST['phpini_post_max_size']))) {
		set_page_message(tr('post_max_size out of range.'), 'error');
	}

	if (isset($_POST['phpini_upload_max_filesize']) && (!$phpini->setDataWithPermCheck('phpiniUploadMaxFileSize', $_POST['phpini_upload_max_filesize']))) {
		set_page_message(tr('upload_max_filesize out of range.'), 'error');
	}

	if (isset($_POST['phpini_max_execution_time']) && (!$phpini->setDataWithPermCheck('phpiniMaxExecutionTime', $_POST['phpini_max_execution_time']))) {
		set_page_message(tr('max_execution_time out of range.'), 'error');
	}

	if (isset($_POST['phpini_max_input_time']) && (!$phpini->setDataWithPermCheck('phpiniMaxInputTime', $_POST['phpini_max_input_time']))) {
		set_page_message(tr('max_input_time out of range.'), 'error');
	}

	if (isset($_POST['phpini_memory_limit']) && (!$phpini->setDataWithPermCheck('phpiniMemoryLimit', $_POST['phpini_memory_limit']))) {
		set_page_message(tr('memory_limit out of range.'), 'error');
	}

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

	(isset($_POST['software_allowed']))
		? $hp_allowsoftware = $_POST['software_allowed']
		: $hp_allowsoftware = "_no_";
	if ($hp_php == "_no_" && $hp_allowsoftware == "_yes_") {
		set_page_message(tr('The i-MSCP application installer needs PHP to enable it.'), 'error');
	}

	if ($hp_name == '') {
		set_page_message(tr('Incorrect template name length.'), 'error');
	}

	if ($description == '') {
		set_page_message(tr('Incorrect template description length.'), 'error');
	}

	if (!is_numeric($price)) {
		set_page_message(tr('Price must be a number.'), 'error');
	}

	if (!is_numeric($setup_fee)) {
		set_page_message(tr('Setup fee must be a number.'), 'error');
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
		set_page_message(tr('Incorrect subdomains limit.'), 'error');
	}

	if ($rals_max == "-1") {
		$hp_als = "-1";
	} elseif (!imscp_limit_check($hp_als, -1)) {
		set_page_message(tr('Incorrect aliases limit.'), 'error');
	}

	if ($rmail_max == "-1") {
		$hp_mail = "-1";
	} elseif (!imscp_limit_check($hp_mail, -1)) {
		set_page_message(tr('Incorrect mail accounts limit.'), 'error');
	}

	if ($rftp_max == "-1") {
		$hp_ftp = "-1";
	} elseif (!imscp_limit_check($hp_ftp, -1)) {
		set_page_message(tr('Incorrect FTP accounts limit.'), 'error');
	}

	if ($rsql_db_max == "-1") {
		$hp_sql_db = "-1";
	} elseif (!imscp_limit_check($hp_sql_db, -1)) {
		set_page_message(tr('Incorrect SQL users limit.'), 'error');
	} else if ($hp_sql_user != -1 && $hp_sql_db == -1) {
		set_page_message(tr('SQL users limit is <i>disabled</i>.'), 'error');
	}

	if ($rsql_user_max == "-1") {
		$hp_sql_user = "-1";
	} elseif (!imscp_limit_check($hp_sql_user, -1)) {
		set_page_message(tr('Incorrect SQL databases limit.'), 'error');
	} else if ($hp_sql_user == -1 && $hp_sql_db != -1) {
		set_page_message(tr('SQL databases limit is not <i>disabled</i>.'), 'error');
	}

	if (!imscp_limit_check($hp_traff, null)) {
		set_page_message(tr('Incorrect traffic limit.'), 'error');
	}
	if (!imscp_limit_check($hp_disk, null)) {
		set_page_message(tr('Incorrect disk quota limit.'), 'error');
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		return true;
	} else {
		return false;
	}
} // end of check_data_correction()

/**
 * Add new host plan to DB
 */
function save_data_to_db($tpl, $admin_id, $phpini)
{
	global $hp_name, $description, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk;
	global $price, $setup_fee, $value, $payment, $status;
	global $hp_backup, $hp_dns, $hp_allowsoftware;
	global $tos;

	$query = "SELECT `id` FROM `hosting_plans` WHERE `name` = ? AND `reseller_id` = ?";
	$res = exec_query($query, array($hp_name, $admin_id));

	if ($res->rowCount() == 1) {
		set_page_message(tr('Hosting plan with same name already exists.'), 'error');
		return false;
	} else {
		$hp_props = "$hp_php;$hp_cgi;$hp_sub;$hp_als;$hp_mail;$hp_ftp;$hp_sql_db;$hp_sql_user;$hp_traff;$hp_disk;$hp_backup;$hp_dns;$hp_allowsoftware";
		$hp_props .= ";" . $phpini->getClPermVal('phpiniSystem') . ";" . $phpini->getClPermVal('phpiniRegisterGlobals') . ";" . $phpini->getClPermVal('phpiniAllowUrlFopen');
		$hp_props .= ";" . $phpini->getClPermVal('phpiniDisplayErrors') . ";" . $phpini->getClPermVal('phpiniDisableFunctions');
		$hp_props .= ";" . $phpini->getDataVal('phpiniPostMaxSize') . ";" . $phpini->getDataVal('phpiniUploadMaxFileSize') . ";" . $phpini->getDataVal('phpiniMaxExecutionTime');
		$hp_props .= ";" . $phpini->getDataVal('phpiniMaxInputTime') . ";" . $phpini->getDataVal('phpiniMemoryLimit');

		// this id is just for fake and is not used in reseller_limits_check.
		$hpid = 0;

		if (reseller_limits_check($admin_id, $hpid, $hp_props)) {
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

				exec_query($query, array($admin_id, $hp_name, $description, $hp_props,
										$price, $setup_fee, $value, $payment, $status,
										$tos));

				$_SESSION['hp_added'] = '_yes_';
				redirectTo('hosting_plan.php');
				exit; // // Useless but avoid stupid IDE warning about missing return statement
		} else {
			set_page_message(tr("Hosting plan values exceed reseller maximum values."), 'error');
			return false;
		}
	}
} // end of save_data_to_db()
