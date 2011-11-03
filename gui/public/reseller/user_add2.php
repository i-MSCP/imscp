<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-msCP | http://i-mscp.net
 * @version	 SVN: $Id$
 * @link		http://i-mscp.net
 * @author	  ispCP Team
 * @author	  i-MSCP Team
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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/************************************************************************************
 * Script functions
 */

/**
 * Get parameters from previous page.
 *
 * @return bool TRUE if parameters from previous page are found, FALSE otherwise
 */
function get_pageone_param()
{
	global $dmn_name, $dmn_expire, $dmn_chp;

	if (isset($_SESSION['dmn_name'])) {
		$dmn_name = $_SESSION['dmn_name'];
		$dmn_expire = $_SESSION['dmn_expire'];
		$dmn_chp = $_SESSION['dmn_tpl'];
	} else {
		return false;
	}

	return true;
}

/**
 * Show page with initial data fields.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function get_init_au2_page($tpl, $phpini)
{
	global $hp_name, $hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp,
		$hp_sql_db, $hp_sql_user, $hp_traff, $hp_disk, $hp_backup, $hp_dns,
		$hp_allowsoftware;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$tpl->assign(array(
					  'VL_TEMPLATE_NAME' => tohtml($hp_name),
					  'MAX_DMN_CNT' => '',
					  'MAX_SUBDMN_CNT' => $hp_sub,
					  'MAX_DMN_ALIAS_CNT' => $hp_als,
					  'MAX_MAIL_CNT' => $hp_mail,
					  'MAX_FTP_CNT' => $hp_ftp,
					  'MAX_SQL_CNT' => $hp_sql_db,
					  'VL_MAX_SQL_USERS' => $hp_sql_user,
					  'VL_MAX_TRAFFIC' => $hp_traff,
					  'VL_MAX_DISK_USAGE' => $hp_disk,
					  'VL_PHPY' => ($hp_php === '_yes_') ? $cfg->HTML_CHECKED : '',
					  'VL_PHPN' => ($hp_php === '_no_') ? $cfg->HTML_CHECKED : '',
					  'VL_CGIY' => ($hp_cgi === '_yes_') ? $cfg->HTML_CHECKED : '',
					  'VL_CGIN' => ($hp_cgi === '_no_') ? $cfg->HTML_CHECKED : '',
					  'VL_BACKUPD' => ($hp_backup === '_dmn_') ? $cfg->HTML_CHECKED
						  : '',
					  'VL_BACKUPS' => ($hp_backup === '_sql_') ? $cfg->HTML_CHECKED
						  : '',
					  'VL_BACKUPF' => ($hp_backup === '_full_') ? $cfg->HTML_CHECKED
						  : '',
					  'VL_BACKUPN' => ($hp_backup === '_no_') ? $cfg->HTML_CHECKED
						  : '',
					  'VL_DNSY' => ($hp_dns === '_yes_') ? $cfg->HTML_CHECKED : '',
					  'VL_DNSN' => ($hp_dns === '_no_') ? $cfg->HTML_CHECKED : '',
					  'VL_SOFTWAREY' => ($hp_allowsoftware === '_yes_')
						  ? $cfg->HTML_CHECKED
						  : '',
					  'VL_SOFTWAREN' => ($hp_allowsoftware === '_no_')
						  ? $cfg->HTML_CHECKED : '',
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
					  'PHPINI_POST_MAX_SIZE' => ($phpini->getDataVal('phpiniPostMaxSize') != 'no') //check only to dont break with old plans without ini values
						  ? $phpini->getDataVal('phpiniPostMaxSize')
						  : $phpini->getDataDefaultVal('phpiniPostMaxSize'),
					  'PHPINI_UPLOAD_MAX_FILESIZE' => ($phpini->getDataVal('phpiniUploadMaxFileSize') != 'no')
						  ? $phpini->getDataVal('phpiniUploadMaxFileSize')
						  : $phpini->getDataDefaultVal('phpiniUploadMaxFileSize'),
					  'PHPINI_MAX_EXECUTION_TIME' => ($phpini->getDataVal('phpiniMaxExecutionTime') != 'no')
						  ? $phpini->getDataVal('phpiniMaxExecutionTime')
						  : $phpini->getDataDefaultVal('phpiniMaxExecutionTime'),
					  'PHPINI_MAX_INPUT_TIME' => ($phpini->getDataVal('phpiniMaxInputTime') != 'no')
						  ? $phpini->getDataVal('phpiniMaxInputTime')
						  : $phpini->getDataDefaultVal('phpiniMaxInputTime'),
					  'PHPINI_MEMORY_LIMIT' => ($phpini->getDataVal('phpiniMemoryLimit') != 'no')
						  ? $phpini->getDataVal('phpiniMemoryLimit')
						  : $phpini->getDataDefaultVal('phpiniMemoryLimit')
				 ));
}


/**
 * Get data for hosting plan.
 *
 * @param  $hpid Hosting plan unique identifier
 * @param  $resellerId Reseller unique identifier
 * @return void
 */
function get_hp_data($hpid, $resellerId, $phpini)
{
	global $hp_name, $hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp,
		$hp_sql_db, $hp_sql_user, $hp_traff, $hp_disk, $hp_backup, $hp_dns,
		$hp_allowsoftware;

	$stmt = exec_query(
		"SELECT `name`, `props` FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?",
		array($resellerId, $hpid));

	if (0 !== $stmt->rowCount()) {
		$data = $stmt->fetchRow();
		$props = $data['props'];

		list(
			$hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db,
			$hp_sql_user, $hp_traff, $hp_disk, $hp_backup, $hp_dns, $hp_allowsoftware,
			$phpini_system, $phpini_al_register_globals, $phpini_al_allow_url_fopen, $phpini_al_display_errors, $phpini_al_disable_functions,
			$phpini_post_max_size, $phpini_upload_max_filesize, $phpini_max_execution_time, $phpini_max_input_time, $phpini_memory_limit
			) = array_pad(explode(';', $props), 23, 'no');

		$hp_name = $data['name'];

		//write into phpini object
		$phpini->setClPerm('phpiniSystem', $phpini_system);
		$phpini->setClPerm('phpiniRegisterGlobals', $phpini_al_register_globals);
		$phpini->setClPerm('phpiniAllowUrlFopen', $phpini_al_allow_url_fopen);
		$phpini->setClPerm('phpiniDisplayErrors', $phpini_al_display_errors);
		$phpini->setClPerm('phpiniDisableFunctions', $phpini_al_disable_functions);

		//use phpini->phpiniData as datastore for the following values - should be better in something like property class/object later
		$phpini->setData('phpiniPostMaxSize', $phpini_post_max_size);
		$phpini->setData('phpiniUploadMaxFileSize', $phpini_upload_max_filesize);
		$phpini->setData('phpiniMaxExecutionTime', $phpini_max_execution_time);
		$phpini->setData('phpiniMaxInputTime', $phpini_max_input_time);
		$phpini->setData('phpiniMemoryLimit', $phpini_memory_limit);

	} else {
		$hp_name = 'Custom';
		$hp_php = $hp_cgi = $hp_backup = $hp_dns = $hp_allowsoftware = '_no_';
		$hp_sub = $hp_als = $hp_mail = $hp_ftp = $hp_sql_db = $hp_sql_user =
		$hp_traff = $hp_disk = '';
	}
}

/**
 * Check validity of input data.
 *
 * @return bool TRUE if all data are valid, FALSE otherwise
 */
function check_user_data($phpini)
{
	global $hp_name, $hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp,
		$hp_sql_db, $hp_sql_user, $hp_traff, $hp_disk, $hp_dmn, $hp_backup, $hp_dns,
		$hp_allowsoftware;

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

	if (isset($_POST['software_allowed'])) {
		$hp_allowsoftware = $_POST['software_allowed'];
	}


	if ($phpini->checkRePerm('phpiniSystem') && isset($_POST['phpini_system'])) {
		$phpini->setClPerm('phpiniSystem', clean_input($_POST['phpini_system']));

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

		//use phpini->phpiniData as datastore for the following values -
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
	}


	list(
		$rsub_max, $rals_max, $rmail_max, $rftp_max, $rsql_db_max, $rsql_user_max
		) = check_reseller_permissions($_SESSION['user_id'], 'all_permissions');

	if ($rsub_max == '-1') {
		$hp_sub = '-1';
	} elseif (!imscp_limit_check($hp_sub, -1)) {
		set_page_message(tr('Incorrect subdomains limit.'), 'error');
	}

	if ($rals_max == '-1') {
		$hp_als = '-1';
	} elseif (!imscp_limit_check($hp_als, -1)) {
		set_page_message(tr('Incorrect aliases limit.'), 'error');
	}

	if ($rmail_max == '-1') {
		$hp_mail = '-1';
	} elseif (!imscp_limit_check($hp_mail, -1)) {
		set_page_message(tr('Incorrect mail accounts limit.'), 'error');
	}

	if ($rftp_max == '-1') {
		$hp_ftp = '-1';
	} elseif (!imscp_limit_check($hp_ftp, -1)) {
		set_page_message(tr('Incorrect FTP accounts limit.'), 'error');
	}

	if ($rsql_db_max == '-1') {
		$hp_sql_db = '-1';
	} elseif (!imscp_limit_check($hp_sql_db, -1)) {
		set_page_message(tr('Incorrect SQL databases limit.'), 'error');
	} elseif ($hp_sql_user != -1 && $hp_sql_db == -1) {
		set_page_message(tr('SQL users limit is <i>disabled</i>!'), 'error');
	}

	if ($rsql_user_max == '-1') {
		$hp_sql_user = '-1';
	} elseif (!imscp_limit_check($hp_sql_user, -1)) {
		set_page_message(tr('Incorrect SQL users limit.'), 'error');
	} elseif ($hp_sql_user == -1 && $hp_sql_db != -1) {
		set_page_message(tr('SQL databases limit is not <i>disabled</i>.'), 'error');
	}

	if (!imscp_limit_check($hp_traff, null)) {
		set_page_message(tr('Incorrect traffic limit.'), 'error');
	}

	if (!imscp_limit_check($hp_disk, null)) {
		set_page_message(tr('Incorrect disk quota limit.'), 'error');
	}

	if ($hp_php == '_no_' && $hp_allowsoftware == '_yes_') {
		set_page_message(tr('The i-MSCP application installer needs PHP to enable it.'), 'error');
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		return true;
	} else {
		return false;
	}
}

/**
 * Check if hosting plan with this name already exists!.
 *
 * @param  $resellerId Reseller unique identifier
 * @return bool TRUE if hosting with same name was found, FALSE otherwise
 */
function check_hosting_plan_name($resellerId)
{
	global $hp_name;

	$stmt = exec_query(
		"SELECT `id` FROM `hosting_plans` WHERE `name` = ? AND `reseller_id` = ?",
		array($hp_name, $resellerId));

	if ($stmt->rowCount() !== 0) {
		return false;
	}

	return true;
}

/************************************************************************************
 * Main script
 */

require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

/* @var $phpini iMSCP_PHPini */
$phpini = iMSCP_PHPini::getInstance();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
						  'page' => $cfg->RESELLER_TEMPLATE_PATH . '/user_add2.tpl',
						  'page_message' => 'page',
						  'logged_from' => 'page',
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


if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL === 'admin') {
	redirectTo('users.php?psi=last');
}

$tpl->assign(array(
				  'TR_PAGE_TITLE' => tr('i-MSCP - User/Add domain account - step 2'),
				  'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
				  'THEME_CHARSET' => tr('encoding'),
				  'ISP_LOGO' => layout_getUserLogo(),
				  'TR_ADD_USER' => tr('Add user'),
				  'TR_HOSTING_PLAN_PROPERTIES' => tr('Hosting plan properties'),
				  'TR_TEMPLATE_NAME' => tr('Template name'),
				  'TR_MAX_DOMAIN' => tr('Max domains<br><i>(-1 disabled, 0 unlimited)</i>'),
				  'TR_MAX_SUBDOMAIN' => tr('Max subdomains<br><i>(-1 disabled, 0 unlimited)</i>'),
				  'TR_MAX_DOMAIN_ALIAS' => tr('Max aliases<br><i>(-1 disabled, 0 unlimited)</i>'),
				  'TR_MAX_MAIL_COUNT' => tr('Mail accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
				  'TR_MAX_FTP' => tr('FTP accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
				  'TR_MAX_SQL_DB' => tr('SQL databases limit<br><i>(-1 disabled, 0 unlimited)</i>'),
				  'TR_MAX_SQL_USERS' => tr('SQL users limit<br><i>(-1 disabled, 0 unlimited)</i>'),
				  'TR_MAX_TRAFFIC' => tr('Traffic limit [MB]<br><i>(0 unlimited)</i>'),
				  'TR_MAX_DISK_USAGE' => tr('Disk limit [MB]<br><i>(0 unlimited)</i>'),
				  'TR_PHP' => tr('PHP'),
				  'TR_CGI' => tr('CGI / Perl'),
				  'TR_BACKUP' => tr('Backup'),
				  'TR_BACKUP_DOMAIN' => tr('Domain'),
				  'TR_BACKUP_SQL' => tr('SQL'),
				  'TR_BACKUP_FULL' => tr('Full'),
				  'TR_BACKUP_NO' => tr('No'),
				  'TR_DNS' => tr('Custom DNS support'),
				  'TR_YES' => tr('yes'),
				  'TR_NO' => tr('no'),
				  'TR_NEXT_STEP' => tr('Next step'),
				  'TR_APACHE_LOGS' => tr('Apache logs'),
				  'TR_AWSTATS' => tr('Awstats'),
				  'TR_SOFTWARE_SUPP' => tr('i-MSCP application installer'),
				  'TR_PHPINI_SYSTEM' => tr('Allow change PHP.ini'),
				  'TR_USER_EDITABLE_EXEC' => tr('Only "exec" allowed'),
				  'TR_PHPINI_AL_REGISTER_GLOBALS' => tr('Allow change value register_globals'),
				  'TR_PHPINI_AL_ALLOW_URL_FOPEN' => tr('Allow change value allow_url_fopen'),
				  'TR_PHPINI_AL_DISPLAY_ERRORS' => tr('Allow change value display_errors'),
				  'TR_PHPINI_AL_DISABLE_FUNCTIONS' => tr('Allow change value disable_functions'),
				  'TR_PHPINI_MAX_MAX_EXECUTION_TIME' => tr('MAX allowed in max_execution_time [Seconds]'),
				  'TR_PHPINI_MAX_MAX_INPUT_TIME' => tr('MAX allowed in max_input_time [Seconds]'),
				  'TR_PHPINI_POST_MAX_SIZE' => tr('Set post_max_size [MB]'),
				  'TR_PHPINI_UPLOAD_MAX_FILESIZE' => tr('Set upload_max_filesize [MB]'),
				  'TR_PHPINI_MAX_EXECUTION_TIME' => tr('Set max_execution_time [sec]'),
				  'TR_PHPINI_MAX_INPUT_TIME' => tr('Set max_input_time [sec]'),
				  'TR_PHPINI_MEMORY_LIMIT' => tr('Set memory_limit [MB]')
			 ));


gen_reseller_mainmenu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/main_menu_users_manage.tpl');
gen_reseller_menu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/menu_users_manage.tpl');
gen_logged_from($tpl);

if (!get_pageone_param()) {
	set_page_message(tr('Domain data were been altered. Please try again.'), 'error');
	unsetMessages();
	redirectTo('user_add1.php');
}

$phpini->loadRePerm($_SESSION['user_id']); // Load Reseller php.ini Permission

if (isset($_POST['uaction']) && ('user_add2_nxt' == $_POST['uaction']) &&
	(!isset($_SESSION['step_one']))
) {
	if (check_user_data($phpini)) {
		$_SESSION['step_two_data'] = "$dmn_name;0;";
		$_SESSION['ch_hpprops'] = "$hp_php;$hp_cgi;$hp_sub;$hp_als;$hp_mail;" .
								  "$hp_ftp;$hp_sql_db;$hp_sql_user;$hp_traff;" .
								  "$hp_disk;$hp_backup;$hp_dns;$hp_allowsoftware" .
								  ";" . $phpini->getClPermVal('phpiniSystem') . ";" . $phpini->getClPermVal('phpiniRegisterGlobals') . ";" . $phpini->getClPermVal('phpiniAllowUrlFopen') .
								  ";" . $phpini->getClPermVal('phpiniDisplayErrors') . ";" . $phpini->getClPermVal('phpiniDisableFunctions') .
								  ";" . $phpini->getDataVal('phpiniPostMaxSize') . ";" . $phpini->getDataVal('phpiniUploadMaxFileSize') . ";" . $phpini->getDataVal('phpiniMaxExecutionTime') .
								  ";" . $phpini->getDataVal('phpiniMaxInputTime') . ";" . $phpini->getDataVal('phpiniMemoryLimit');


		if (reseller_limits_check($_SESSION['user_id'], 0, $_SESSION['ch_hpprops'])
		) {
			redirectTo('user_add3.php');
		}
	}
} else {
	unset($_SESSION['step_one']);
	global $dmn_chp;
	get_hp_data($dmn_chp, $_SESSION['user_id'], $phpini);
}

get_init_au2_page($tpl, $phpini);
get_reseller_software_permission($tpl, $_SESSION['user_id']);

list(
	$rsub_max, $rals_max, $rmail_max, $rftp_max, $rsql_db_max, $rsql_user_max
	) = check_reseller_permissions($_SESSION['user_id'], 'all_permissions');

if ($rsub_max == '-1') {
	$tpl->assign('ALIAS_ADD', '');
}

if ($rals_max == '-1') {
	$tpl->assign('SUBDOMAIN_ADD', '');
}

if ($rmail_max == '-1') {
	$tpl->assign('MAIL_ADD', '');
}

if ($rftp_max == '-1') {
	$tpl->assign('FTP_ADD', '');
}

if ($rsql_db_max == '-1') {
	$tpl->assign('SQL_DB_ADD', '');
}

if ($rsql_user_max == '-1') {
	$tpl->assign('SQL_USER_ADD', '');
}

if (!$phpini->checkRePerm('phpiniSystem')) {
	$tpl->assign('T_PHPINI_SYSTEM', '');
}

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
	iMSCP_Events::onResellerScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();
