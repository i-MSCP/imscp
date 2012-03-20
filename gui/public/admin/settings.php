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
 * @subpackage	Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

/* @var $phpini iMSCP_PHPini */
$phpini = iMSCP_PHPini::getInstance();

if (isset($_POST['uaction']) && $_POST['uaction'] == 'apply') {

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeEditAdminGeneralSettings);

	$lostpwd = $_POST['lostpassword'];
	$lostpwd_timeout = clean_input($_POST['lostpassword_timeout']);
	$pwd_chars = clean_input($_POST['passwd_chars']);
	$pwd_strong = $_POST['passwd_strong'];
	$bruteforce = $_POST['bruteforce'];
	$bruteforce_between = $_POST['bruteforce_between'];
	$bruteforce_max_login = clean_input($_POST['bruteforce_max_login']);
	$bruteforce_block_time = clean_input($_POST['bruteforce_block_time']);
	$bruteforce_between_time = clean_input($_POST['bruteforce_between_time']);
	$bruteforce_max_capcha = clean_input($_POST['bruteforce_max_capcha']);
	$create_default_emails = $_POST['create_default_email_addresses'];
	$count_default_emails = $_POST['count_default_email_addresses'];
	$hard_mail_suspension = $_POST['hard_mail_suspension'];
	$user_initial_lang = $_POST['def_language'];
	$support_system = $_POST['support_system'];
	$hosting_plan_level = $_POST['hosting_plan_level'];
	$domain_rows_per_page = clean_input($_POST['domain_rows_per_page']);
	$checkforupdate = $_POST['checkforupdate'];
	$enableSSL = $_POST['enableSSL'];
	$compress_output = intval($_POST['compress_output']);
	$show_compression_size = intval($_POST['show_compression_size']);
	$prev_ext_login_admin = $_POST['prevent_external_login_admin'];
	$prev_ext_login_reseller = $_POST['prevent_external_login_reseller'];
	$prev_ext_login_client = $_POST['prevent_external_login_client'];
	$custom_orderpanel_id = clean_input($_POST['coid']);
	$tld_strict_validation = $_POST['tld_strict_validation'];
	$sld_strict_validation = $_POST['sld_strict_validation'];
	$max_dnames_labels = clean_input($_POST['max_dnames_labels']);
	$max_subdnames_labels = clean_input($_POST['max_subdnames_labels']);
	$log_level = defined($_POST['log_level']) ? constant($_POST['log_level']) : false;
	$ordersExpireTime = clean_input($_POST['ordersExpireTime']);
	$phpini->setData('phpiniRegisterGlobals', clean_input($_POST['phpini_register_globals']));
	$phpini->setData('phpiniAllowUrlFopen', clean_input($_POST['phpini_allow_url_fopen']));
	$phpini->setData('phpiniDisplayErrors', clean_input($_POST['phpini_display_errors']));
	$phpini->setData('phpiniErrorReporting', clean_input($_POST['phpini_error_reporting']));
	$phpini->setData('phpiniPostMaxSize', clean_input($_POST['phpini_post_max_size']));
	$phpini->setData('phpiniUploadMaxFileSize', clean_input($_POST['phpini_upload_max_filesize']));
	$phpini->setData('phpiniMaxExecutionTime', clean_input($_POST['phpini_max_execution_time']));
	$phpini->setData('phpiniMaxInputTime', clean_input($_POST['phpini_max_input_time']));
	$phpini->setData('phpiniMemoryLimit', clean_input($_POST['phpini_memory_limit']));
	$phpini_open_basedir = isset($_POST['phpini_open_basedir']) ? clean_input($_POST['phpini_open_basedir']) : $cfg->PHPINI_OPEN_BASEDIR;


	if (PHP_SAPI != 'apache2handler') {
		$disabledFunctions = array();

		foreach (array(
					 'show_source', 'system', 'shell_exec', 'shell_exec', 'passthru', 'exec',
					 'phpinfo', 'shell', 'symlink') as $function
		) {
			if (isset($_POST[$function])) { // we are safe here
				array_push($disabledFunctions, $function);
			}
		}

		// Builds the PHP disable_function directive with a pre-check on functions that can be disabled
		$phpini->setData('phpiniDisableFunctions', $phpini->assembleDisableFunctions($disabledFunctions));
	} else {
		$phpini->setData('phpiniDisableFunctions', $cfg->PHPINI_DISABLE_FUNCTIONS);
	}

	if(!is_scalar($phpini_open_basedir)) { // No more check here - Admin must know what he do...
		set_page_message(tr('Wrong value for the PHP open_basedir directive.'), 'error');
	} elseif ((!is_number($lostpwd_timeout))
		|| (!is_number($pwd_chars)) || (!is_number($bruteforce_max_login))
		|| (!is_number($bruteforce_block_time)) || (!is_number($bruteforce_between_time))
		|| (!is_number($bruteforce_max_capcha)) || (!is_number($domain_rows_per_page))
		|| (!is_number($max_dnames_labels)) || (!is_number($max_subdnames_labels))
		|| (!is_number($ordersExpireTime))
	) {
		set_page_message(tr('Only positive numbers are allowed.'), 'error');
	} elseif ($domain_rows_per_page < 1) {
		$domain_rows_per_page = 1;
	} elseif ($max_dnames_labels < 1) {
		$max_dnames_labels = 1;
	} elseif ($max_subdnames_labels < 1) {
		$max_subdnames_labels = 1;
	} elseif ($phpini->flagValueError) { // if a php value was out of range or simple wrong type
		set_page_message(tr('Error in php.ini values.'), 'error');
	} else {
		/** @var $db_cfg iMSCP_Config_Handler_Db */
		$db_cfg = iMSCP_Registry::get('dbConfig');

		$db_cfg->LOSTPASSWORD = $lostpwd;
		$db_cfg->LOSTPASSWORD_TIMEOUT = $lostpwd_timeout;
		$db_cfg->PASSWD_CHARS = $pwd_chars;
		$db_cfg->PASSWD_STRONG = $pwd_strong;
		$db_cfg->BRUTEFORCE = $bruteforce;
		$db_cfg->BRUTEFORCE_BETWEEN = $bruteforce_between;
		$db_cfg->BRUTEFORCE_MAX_LOGIN = $bruteforce_max_login;
		$db_cfg->BRUTEFORCE_BLOCK_TIME = $bruteforce_block_time;
		$db_cfg->BRUTEFORCE_BETWEEN_TIME = $bruteforce_between_time;
		$db_cfg->BRUTEFORCE_MAX_CAPTCHA = $bruteforce_max_capcha;
		$db_cfg->CREATE_DEFAULT_EMAIL_ADDRESSES = $create_default_emails;
		$db_cfg->COUNT_DEFAULT_EMAIL_ADDRESSES = $count_default_emails;
		$db_cfg->HARD_MAIL_SUSPENSION = $hard_mail_suspension;
		$db_cfg->USER_INITIAL_LANG = $user_initial_lang;
		$db_cfg->IMSCP_SUPPORT_SYSTEM = $support_system;
		$db_cfg->HOSTING_PLANS_LEVEL = $hosting_plan_level;
		$db_cfg->DOMAIN_ROWS_PER_PAGE = $domain_rows_per_page;
		$db_cfg->LOG_LEVEL = $log_level;
		$db_cfg->CHECK_FOR_UPDATES = $checkforupdate;
		$db_cfg->ENABLE_SSL = $enableSSL;
		$db_cfg->COMPRESS_OUTPUT = $compress_output;
		$db_cfg->SHOW_COMPRESSION_SIZE = $show_compression_size;
		$db_cfg->PREVENT_EXTERNAL_LOGIN_ADMIN = $prev_ext_login_admin;
		$db_cfg->PREVENT_EXTERNAL_LOGIN_RESELLER = $prev_ext_login_reseller;
		$db_cfg->PREVENT_EXTERNAL_LOGIN_CLIENT = $prev_ext_login_client;
		$db_cfg->CUSTOM_ORDERPANEL_ID = $custom_orderpanel_id;
		$db_cfg->TLD_STRICT_VALIDATION = $tld_strict_validation;
		$db_cfg->SLD_STRICT_VALIDATION = $sld_strict_validation;
		$db_cfg->MAX_DNAMES_LABELS = $max_dnames_labels;
		$db_cfg->MAX_SUBDNAMES_LABELS = $max_subdnames_labels;
		$db_cfg->ORDERS_EXPIRE_TIME = $ordersExpireTime * 86400;
		$db_cfg->PHPINI_ALLOW_URL_FOPEN = $phpini->getDataVal('phpiniAllowUrlFopen');
		$db_cfg->PHPINI_REGISTER_GLOBALS = $phpini->getDataVal('phpiniRegisterGlobals');
		$db_cfg->PHPINI_DISPLAY_ERRORS = $phpini->getDataVal('phpiniDisplayErrors');
		$db_cfg->PHPINI_ERROR_REPORTING = $phpini->getDataVal('phpiniErrorReporting');
		$db_cfg->PHPINI_POST_MAX_SIZE = $phpini->getDataVal('phpiniPostMaxSize');
		$db_cfg->PHPINI_UPLOAD_MAX_FILESIZE = $phpini->getDataVal('phpiniUploadMaxFileSize');
		$db_cfg->PHPINI_MAX_EXECUTION_TIME = $phpini->getDataVal('phpiniMaxExecutionTime');
		$db_cfg->PHPINI_MAX_INPUT_TIME = $phpini->getDataVal('phpiniMaxInputTime');
		$db_cfg->PHPINI_MEMORY_LIMIT = $phpini->getDataVal('phpiniMemoryLimit');
		$db_cfg->PHPINI_OPEN_BASEDIR = $phpini_open_basedir;
		$db_cfg->PHPINI_DISABLE_FUNCTIONS = $phpini->getDataVal('phpiniDisableFunctions');
		$cfg->replaceWith($db_cfg);

		iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterEditAdminGeneralSettings);

		// gets the number of queries that were been executed
		$updt_count = $db_cfg->countQueries('update');
		$new_count = $db_cfg->countQueries('insert');

		// An Update was been made in the database ?
		if ($updt_count > 0) {
			set_page_message(tr('%d configuration parameter(s) have/has been updated.', $updt_count), 'success');
		}

		if ($new_count > 0) {
			set_page_message(tr('%d configuration parameter(s) have/has been created.', $new_count), 'success');
		}

		if ($new_count == 0 && $updt_count == 0) {
			set_page_message(tr("Nothing's been changed."), 'info');
		}
	}

	// Fix to see changes on next load
	redirectTo('settings.php');
}

$coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/settings.tpl',
		'page_message' => 'layout',
		'def_language' => 'page',
		'php_editor_disable_functions_block' => 'page'
	));

// Grab the value only once to improve performances
$html_selected = $cfg->HTML_SELECTED;

if ($cfg->LOSTPASSWORD) {
	$tpl->assign(
		array(
			 'LOSTPASSWORD_SELECTED_ON' => $html_selected,
			 'LOSTPASSWORD_SELECTED_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'LOSTPASSWORD_SELECTED_ON' => '',
			 'LOSTPASSWORD_SELECTED_OFF', $html_selected));
}

if ($cfg->PASSWD_STRONG) {
	$tpl->assign(
		array(
			 'PASSWD_STRONG_ON' => $html_selected,
			 'PASSWD_STRONG_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'PASSWD_STRONG_ON' => '',
			 'PASSWD_STRONG_OFF' => $html_selected));
}

if ($cfg->BRUTEFORCE) {
	$tpl->assign(
		array(
			 'BRUTEFORCE_SELECTED_ON' => $html_selected,
			 'BRUTEFORCE_SELECTED_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'BRUTEFORCE_SELECTED_ON' => '',
			 'BRUTEFORCE_SELECTED_OFF' => $html_selected));
}

if ($cfg->BRUTEFORCE_BETWEEN) {
	$tpl->assign(
		array(
			 'BRUTEFORCE_BETWEEN_SELECTED_ON' => $html_selected,
			 'BRUTEFORCE_BETWEEN_SELECTED_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'BRUTEFORCE_BETWEEN_SELECTED_ON' => '',
			 'BRUTEFORCE_BETWEEN_SELECTED_OFF' => $html_selected));
}

if ($cfg->IMSCP_SUPPORT_SYSTEM) {
	$tpl->assign(
		array(
			 'SUPPORT_SYSTEM_SELECTED_ON' => $html_selected,
			 'SUPPORT_SYSTEM_SELECTED_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'SUPPORT_SYSTEM_SELECTED_ON' => '',
			 'SUPPORT_SYSTEM_SELECTED_OFF' => $html_selected));
}

if ($cfg->TLD_STRICT_VALIDATION) {
	$tpl->assign(
		array(
			 'TLD_STRICT_VALIDATION_ON' => $html_selected,
			 'TLD_STRICT_VALIDATION_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'TLD_STRICT_VALIDATION_ON' => '',
			 'TLD_STRICT_VALIDATION_OFF' => $html_selected));
}

if ($cfg->SLD_STRICT_VALIDATION) {
	$tpl->assign(
		array(
			 'SLD_STRICT_VALIDATION_ON' => $html_selected,
			 'SLD_STRICT_VALIDATION_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'SLD_STRICT_VALIDATION_ON' => '',
			 'SLD_STRICT_VALIDATION_OFF' => $html_selected));
}

if ($cfg->CREATE_DEFAULT_EMAIL_ADDRESSES) {
	$tpl->assign(
		array(
			 'CREATE_DEFAULT_EMAIL_ADDRESSES_ON' => $html_selected,
			 'CREATE_DEFAULT_EMAIL_ADDRESSES_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'CREATE_DEFAULT_EMAIL_ADDRESSES_ON' => '',
			 'CREATE_DEFAULT_EMAIL_ADDRESSES_OFF' => $html_selected));
}

if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES) {
	$tpl->assign(
		array(
			 'COUNT_DEFAULT_EMAIL_ADDRESSES_ON' => $html_selected,
			 'COUNT_DEFAULT_EMAIL_ADDRESSES_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'COUNT_DEFAULT_EMAIL_ADDRESSES_ON' => '',
			 'COUNT_DEFAULT_EMAIL_ADDRESSES_OFF' => $html_selected));
}

if ($cfg->HARD_MAIL_SUSPENSION) {
	$tpl->assign(
		array(
			 'HARD_MAIL_SUSPENSION_ON' => $html_selected,
			 'HARD_MAIL_SUSPENSION_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'HARD_MAIL_SUSPENSION_ON' => '',
			 'HARD_MAIL_SUSPENSION_OFF' => $html_selected));
}

if ($cfg->HOSTING_PLANS_LEVEL == 'admin') {
	$tpl->assign(
		array(
			 'HOSTING_PLANS_LEVEL_ADMIN' => $html_selected,
			 'HOSTING_PLANS_LEVEL_RESELLER', ''));
} else {
	$tpl->assign(
		array(
			 'HOSTING_PLANS_LEVEL_ADMIN' => '',
			 'HOSTING_PLANS_LEVEL_RESELLER' => $html_selected));
}

if ($cfg->CHECK_FOR_UPDATES) {
	$tpl->assign(
		array(
			 'CHECK_FOR_UPDATES_SELECTED_ON' => $html_selected,
			 'CHECK_FOR_UPDATES_SELECTED_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'CHECK_FOR_UPDATES_SELECTED_ON' => '',
			 'CHECK_FOR_UPDATES_SELECTED_OFF' => $html_selected));
}

if ($cfg->ENABLE_SSL) {
	$tpl->assign(
		array(
			 'ENABLE_SSL_ON' => $html_selected,
			 'ENABLE_SSL_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'ENABLE_SSL_ON' => '',
			 'ENABLE_SSL_OFF' => $html_selected));
}

if ($cfg->COMPRESS_OUTPUT) {
	$tpl->assign(
		array(
			 'COMPRESS_OUTPUT_ON' => $html_selected,
			 'COMPRESS_OUTPUT_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'COMPRESS_OUTPUT_ON' => '',
			 'COMPRESS_OUTPUT_OFF' => $html_selected));
}

if ($cfg->SHOW_COMPRESSION_SIZE) {
	$tpl->assign(
		array(
			 'SHOW_COMPRESSION_SIZE_SELECTED_ON' => $html_selected,
			 'SHOW_COMPRESSION_SIZE_SELECTED_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'SHOW_COMPRESSION_SIZE_SELECTED_ON' => '',
			 'SHOW_COMPRESSION_SIZE_SELECTED_OFF' => $html_selected));
}

if ($cfg->PREVENT_EXTERNAL_LOGIN_ADMIN) {
	$tpl->assign(
		array(
			 'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_ON' => $html_selected,
			 'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_ON' => '',
			 'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_OFF' => $html_selected));
}

if ($cfg->PREVENT_EXTERNAL_LOGIN_RESELLER) {
	$tpl->assign(
		array(
			 'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON' => $html_selected,
			 'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON' => '',
			 'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF' => $html_selected));
}

if ($cfg->PREVENT_EXTERNAL_LOGIN_CLIENT) {
	$tpl->assign(
		array(
			 'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON' => $html_selected,
			 'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON' => '',
			 'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF' => $html_selected));
}

if ($phpini->getDataVal('phpiniAllowUrlFopen') == 'On') {
	$tpl->assign(
		array(
			 'PHPINI_ALLOW_URL_FOPEN_ON' => $html_selected,
			 'PHPINI_ALLOW_URL_FOPEN_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'PHPINI_ALLOW_URL_FOPEN_ON' => '',
			 'PHPINI_ALLOW_URL_FOPEN_OFF' => $html_selected));
}

if ($phpini->getDataVal('phpiniRegisterGlobals') == 'On') {
	$tpl->assign(
		array(
			 'PHPINI_REGISTER_GLOBALS_ON' => $html_selected,
			 'PHPINI_REGISTER_GLOBALS_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'PHPINI_REGISTER_GLOBALS_ON' => '',
			 'PHPINI_REGISTER_GLOBALS_OFF' => $html_selected));
}

if ($phpini->getDataVal('phpiniDisplayErrors') == 'On') {
	$tpl->assign(
		array(
			 'PHPINI_DISPLAY_ERRORS_ON' => $html_selected,
			 'PHPINI_DISPLAY_ERRORS_OFF' => ''));
} else {
	$tpl->assign(
		array(
			 'PHPINI_DISPLAY_ERRORS_ON' => '',
			 'PHPINI_DISPLAY_ERRORS_OFF' => $html_selected));
}

switch ($phpini->getDataVal('phpiniErrorReporting')) {
	case 'E_ALL & ~E_NOTICE':
		$tpl->assign(
			array(
				 'PHPINI_ERROR_REPORTING_0' => $html_selected,
				 'PHPINI_ERROR_REPORTING_1' => '',
				 'PHPINI_ERROR_REPORTING_2' => '',
				 'PHPINI_ERROR_REPORTING_3' => ''));
		break;
	case 'E_ALL | E_STRICT':
		$tpl->assign(
			array(
				 'PHPINI_ERROR_REPORTING_0' => '',
				 'PHPINI_ERROR_REPORTING_1' => $html_selected,
				 'PHPINI_ERROR_REPORTING_2' => '',
				 'PHPINI_ERROR_REPORTING_3' => ''));
		break;
	case 'E_ALL & ~E_DEPRECATED':
		$tpl->assign(
			array(
				 'PHPINI_ERROR_REPORTING_0' => '',
				 'PHPINI_ERROR_REPORTING_1' => '',
				 'PHPINI_ERROR_REPORTING_2' => $html_selected,
				 'PHPINI_ERROR_REPORTING_3' => ''));
		break;
	case '0':
		$tpl->assign(
			array(
				 'PHPINI_ERROR_REPORTING_0' => '',
				 'PHPINI_ERROR_REPORTING_1' => '',
				 'PHPINI_ERROR_REPORTING_2' => '',
				 'PHPINI_ERROR_REPORTING_3' => $html_selected));
		break;
}

$htmlChecked = $cfg->HTML_CHECKED;

if (PHP_SAPI != 'apache2handler') {
	$disabledFunctions = explode(',', $phpini->getDataVal('phpiniDisableFunctions'));
	$disabledFunctionsAll = array('SHOW_SOURCE', 'SYSTEM', 'SHELL_EXEC', 'PASSTHRU', 'EXEC', 'PHPINFO', 'SHELL', 'SYMLINK');

	foreach ($disabledFunctionsAll as $function) {
		$tpl->assign($function, in_array(strtolower($function), $disabledFunctions) ? $htmlChecked : '');
	}
} else {
	$tpl->assign('PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK', '');
}

switch ($cfg->LOG_LEVEL) {
	case false:
		$tpl->assign(
			array(
				 'LOG_LEVEL_SELECTED_OFF' => $html_selected,
				 'LOG_LEVEL_SELECTED_NOTICE' => '',
				 'LOG_LEVEL_SELECTED_WARNING' => '',
				 'LOG_LEVEL_SELECTED_ERROR' => ''));
		break;
	case E_USER_NOTICE:
		$tpl->assign(
			array(
				 'LOG_LEVEL_SELECTED_OFF' => '',
				 'LOG_LEVEL_SELECTED_NOTICE' => $html_selected,
				 'LOG_LEVEL_SELECTED_WARNING' => '',
				 'LOG_LEVEL_SELECTED_ERROR' => ''));
		break;
	case E_USER_WARNING:
		$tpl->assign(
			array(
				 'LOG_LEVEL_SELECTED_OFF' => '',
				 'LOG_LEVEL_SELECTED_NOTICE' => '',
				 'LOG_LEVEL_SELECTED_WARNING' => $html_selected,
				 'LOG_LEVEL_SELECTED_ERROR' => ''));
		break;
	default:
		$tpl->assign(
			array(
				 'LOG_LEVEL_SELECTED_OFF' => '',
				 'LOG_LEVEL_SELECTED_NOTICE' => '',
				 'LOG_LEVEL_SELECTED_WARNING' => '',
				 'LOG_LEVEL_SELECTED_ERROR' => $html_selected));
}

$tpl->assign(
	array(
		 'THEME_CHARSET' => tr('encoding'),
		 'TR_PAGE_TITLE' => tr('i-MSCP - Admin/Settings'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_UPDATES' => tr('Updates'),
		 'LOSTPASSWORD_TIMEOUT_VALUE' => $cfg->LOSTPASSWORD_TIMEOUT,
		 'PASSWD_CHARS' => $cfg->PASSWD_CHARS,
		 'BRUTEFORCE_MAX_LOGIN_VALUE' => $cfg->BRUTEFORCE_MAX_LOGIN,
		 'BRUTEFORCE_BLOCK_TIME_VALUE' => $cfg->BRUTEFORCE_BLOCK_TIME,
		 'BRUTEFORCE_BETWEEN_TIME_VALUE' => $cfg->BRUTEFORCE_BETWEEN_TIME,
		 'BRUTEFORCE_MAX_CAPTCHA' => $cfg->BRUTEFORCE_MAX_CAPTCHA,
		 'DOMAIN_ROWS_PER_PAGE' => $cfg->DOMAIN_ROWS_PER_PAGE,
		 'CUSTOM_ORDERPANEL_ID' => tohtml($coid),
		 'MAX_DNAMES_LABELS_VALUE' => $cfg->MAX_DNAMES_LABELS,
		 'MAX_SUBDNAMES_LABELS_VALUE' => $cfg->MAX_SUBDNAMES_LABELS,
		 'ORDERS_EXPIRATION_TIME_VALUE' => $cfg->ORDERS_EXPIRE_TIME / 86400,
		 'PHPINI_POST_MAX_SIZE' => $phpini->getDataVal('phpiniPostMaxSize'),
		 'PHPINI_UPLOAD_MAX_FILESIZE' => $phpini->getDataVal('phpiniUploadMaxFileSize'),
		 'PHPINI_MAX_EXECUTION_TIME' => $phpini->getDataVal('phpiniMaxExecutionTime'),
		 'PHPINI_MAX_INPUT_TIME' => $phpini->getDataVal('phpiniMaxInputTime'),
		 'PHPINI_MEMORY_LIMIT' => $phpini->getDataVal('phpiniMemoryLimit'),
		 'PHPINI_OPEN_BASEDIR' => $cfg->PHPINI_OPEN_BASEDIR,
		 'TR_GENERAL_SETTINGS' => tr('General settings'),
		 'TR_SETTINGS' => tr('Settings'),
		 'TR_MESSAGE' => tr('Message'),
		 'TR_LOSTPASSWORD' => tr('Lost password'),
		 'TR_LOSTPASSWORD_TIMEOUT' => tr('Activation link expire time (minutes)'),
		 'TR_PASSWORD_SETTINGS' => tr('Password settings'),
		 'TR_PASSWD_STRONG' => tr('Use strong Passwords'),
		 'TR_PASSWD_CHARS' => tr('Password length'),
		 'TR_BRUTEFORCE' => tr('Bruteforce detection'),
		 'TR_BRUTEFORCE_BETWEEN' => tr('Blocking time between logins and captcha attempts'),
		 'TR_BRUTEFORCE_MAX_LOGIN' => tr('Max number of login attempts'),
		 'TR_BRUTEFORCE_BLOCK_TIME' => tr('Blocktime (minutes)'),
		 'TR_BRUTEFORCE_BETWEEN_TIME' => tr('Blocking time between login/captcha attempts (seconds)'),
		 'TR_BRUTEFORCE_MAX_CAPTCHA' => tr('Maximum number of captcha validation attempts'),
		 'TR_OTHER_SETTINGS' => tr('Other settings'),
		 'TR_MAIL_SETTINGS' => tr('E-Mail settings'),
		 'TR_CREATE_DEFAULT_EMAIL_ADDRESSES' => tr('Create default E-Mail addresses'),
		 'TR_COUNT_DEFAULT_EMAIL_ADDRESSES' => tr('Count default E-Mail addresses'),
		 'TR_HARD_MAIL_SUSPENSION' => tr('E-Mail accounts are hard suspended'),
		 'TR_USER_INITIAL_LANG' => tr('Panel default language'),
		 'TR_SUPPORT_SYSTEM' => tr('Support System'),
		 'TR_ENABLED' => tr('Enabled'),
		 'TR_DISABLED' => tr('Disabled'),
		 'TR_APPLY_CHANGES' => tr('Apply changes'),
		 'TR_SERVERPORTS' => tr('Server ports'),
		 'TR_HOSTING_PLANS_LEVEL' => tr('Hosting plans available for'),
		 'TR_ADMIN' => tr('Admin'),
		 'TR_RESELLER' => tr('Reseller'),
		 'TR_DOMAIN_ROWS_PER_PAGE' => tr('Domains per page'),
		 'TR_LOG_LEVEL' => tr('Mail Log Level'),
		 'TR_E_USER_OFF' => tr('Disabled'),
		 'TR_E_USER_NOTICE' => tr('Notices, Warnings and Errors'),
		 'TR_E_USER_WARNING' => tr('Warnings and Errors'),
		 'TR_E_USER_ERROR' => tr('Errors'),
		 'TR_CHECK_FOR_UPDATES' => tr('Check for update'),
		 'TR_ENABLE_SSL' => tr('Enable SSL'),
		 'TR_SSL_HELP' => tr('Tells whether or not customers can add/change SSL certificates for their domains.'),
		 'TR_COMPRESS_OUTPUT' => tr('Compress HTML output'),
		 'TR_SHOW_COMPRESSION_SIZE' => tr('Show HTML output compression size comment'),
		 'TR_PREVENT_EXTERNAL_LOGIN_ADMIN' => tr('Prevent external login for admins'),
		 'TR_PREVENT_EXTERNAL_LOGIN_RESELLER' => tr('Prevent external login for resellers'),
		 'TR_PREVENT_EXTERNAL_LOGIN_CLIENT' => tr('Prevent external login for clients'),
		 'TR_CUSTOM_ORDERPANEL_ID' => tr('Custom orderpanel Id'),
		 'TR_DNAMES_VALIDATION_SETTINGS' => tr('Domain names validation'),
		 'TR_TLD_STRICT_VALIDATION' => tr('Top Level Domain name strict validation'),
		 'TR_TLD_STRICT_VALIDATION_HELP' => tr('Only Top Level Domains (TLD) listed in IANA root zone database can be used.'),
		 'TR_SLD_STRICT_VALIDATION' => tr('Second Level Domain name strict validation'),
		 'TR_SLD_STRICT_VALIDATION_HELP' => tr('Single letter Second Level Domains (SLD) are not allowed under the most Top Level Domains (TLD). There is a small list of exceptions, e.g. the TLD .de.'),
		 'TR_MAX_DNAMES_LABELS' => tr('Maximal number of labels for domain names<br />(<small>Excluding SLD & TLD</small>)'),
		 'TR_MAX_SUBDNAMES_LABELS' => tr('Maximum number of labels for subdomains'),
		 'TR_PHPINI_BASE_SETTINGS' => tr('PHP Settings (system default)'),
		 'TR_PHPINI_ALLOW_URL_FOPEN' => tr('Value for the %s directive', true, '<span class="bold">allow_url_fopen</span>'),
		 'TR_PHPINI_REGISTER_GLOBALS' => tr('Value for the %s directive', true, '<span class="bold">register_globals</span>'),
		 'TR_PHPINI_DISPLAY_ERRORS' => tr('Value for the %s directive', true, '<span class="bold">display_errors</span>'),
		 'TR_PHPINI_ERROR_REPORTING' => tr('Value for the %s directive', true, '<span class="bold">error_reporting</span>'),
		 'TR_PHPINI_ERROR_REPORTING_DEFAULT' => tr('Show all errors, except for notices and coding standards warnings (Default)'),
		 'TR_PHPINI_ERROR_REPORTING_DEVELOPEMENT' => tr('Show all errors, warnings and notices including coding standards (Development)'),
		 'TR_PHPINI_ERROR_REPORTING_PRODUCTION' => tr(' Show all errors, except for warnings about deprecated code (Production)'),
		 'TR_PHPINI_ERROR_REPORTING_NONE' => tr('Do not show any error'),
		 'TR_PHPINI_POST_MAX_SIZE' => tr('Value for the %s  directive', true, '<span class="bold">post_max_size</span>'),
		 'TR_PHPINI_UPLOAD_MAX_FILESIZE' => tr('Value for the %s directive', true, '<span class="bold">upload_max_filesize</span>'),
		 'TR_PHPINI_MAX_EXECUTION_TIME' => tr('Value for the %s directive', true, '<span class="bold">max_execution_time</span>'),
		 'TR_PHPINI_MAX_INPUT_TIME' => tr('Value for the %s directive', true, '<span class="bold">max_input_time</span>'),
		 'TR_PHPINI_MEMORY_LIMIT' => tr('Value for the %s directive', true, '<span class="bold">memory_limit</span>'),
		 'TR_PHPINI_OPEN_BASEDIR' => tr('Value for the %s directive', true, '<span class="bold">open_basedir</span>'),
		 'TR_PHPINI_OPEN_BASEDIR_TOOLTIP' => json_encode(tr('The directory/file paths are appended to the default PHP open_basedir directive of customers. Each of them must be separated by PATH_SEPARATOR. See the PHP documentation for more information.')),
		 'TR_PHPINI_DISABLE_FUNCTIONS' => tr('Value for the %s directive', true, '<span class="bold">disable_functions</span>'),
		 'TR_ORDERS_SETTINGS' => tr('Orders settings'),
		 'TR_ORDERS_EXPIRE_TIME' => tr('Expire time for unconfirmed orders<br /><small>(In days)</small>', true),
		 'TR_MIB' => tr('MiB'),
		 'TR_SEC' => tr('Sec.')));

generateNavigation($tpl);
gen_def_language($tpl, $cfg->USER_INITIAL_LANG);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
