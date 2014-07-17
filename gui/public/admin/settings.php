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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

/* @var $phpini iMSCP_PHPini */
$phpini = iMSCP_PHPini::getInstance();

if (isset($_POST['uaction']) && $_POST['uaction'] == 'apply') {

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditAdminGeneralSettings);

	$lostPasswd = $_POST['lostpassword'];
	$lostPasswdTimeout = clean_input($_POST['lostpassword_timeout']);
	$passwdChars = clean_input($_POST['passwd_chars']);
	$passwdStrong = $_POST['passwd_strong'];
	$bruteforce = $_POST['bruteforce'];
	$bruteforce_between = $_POST['bruteforce_between'];
	$bruteforce_max_login = clean_input($_POST['bruteforce_max_login']);
	$bruteforce_block_time = clean_input($_POST['bruteforce_block_time']);
	$bruteforce_between_time = clean_input($_POST['bruteforce_between_time']);
	$bruteforce_max_capcha = clean_input($_POST['bruteforce_max_capcha']);
	$bruteforce_max_attempts_before_wait = clean_input($_POST['bruteforce_max_attempts_before_wait']);
	$createDefaultEmails = $_POST['create_default_email_addresses'];
	$countDefaultEmails = $_POST['count_default_email_addresses'];
	$hardMailSuspension = $_POST['hard_mail_suspension'];
	$emailQuotaSyncMode = $_POST['email_quota_sync_mode'];
	$userInitialLang = $_POST['def_language'];
	$supportSystem = $_POST['support_system'];
	$hostingPlanLevel = $_POST['hosting_plan_level'];
	$domainRowsPerPage = clean_input($_POST['domain_rows_per_page']);
	$checkForUpdate = $_POST['checkforupdate'];
	$enableSSL = $_POST['enableSSL'];
	$compressOutput = intval($_POST['compress_output']);
	$showCompressionSize = intval($_POST['show_compression_size']);
	$prevExtLoginAdmin = $_POST['prevent_external_login_admin'];
	$prevExtLoginReseller = $_POST['prevent_external_login_reseller'];
	$prevExtLoginClient = $_POST['prevent_external_login_client'];
	$logLevel = defined($_POST['log_level']) ? constant($_POST['log_level']) : false;
	$phpini->setData('phpiniAllowUrlFopen', clean_input($_POST['phpini_allow_url_fopen']));
	$phpini->setData('phpiniDisplayErrors', clean_input($_POST['phpini_display_errors']));
	$phpini->setData('phpiniErrorReporting', clean_input($_POST['phpini_error_reporting']));
	$phpini->setData('phpiniPostMaxSize', clean_input($_POST['phpini_post_max_size']));
	$phpini->setData('phpiniUploadMaxFileSize', clean_input($_POST['phpini_upload_max_filesize']));
	$phpini->setData('phpiniMaxExecutionTime', clean_input($_POST['phpini_max_execution_time']));
	$phpini->setData('phpiniMaxInputTime', clean_input($_POST['phpini_max_input_time']));
	$phpini->setData('phpiniMemoryLimit', clean_input($_POST['phpini_memory_limit']));
	$phpini_open_basedir = isset($_POST['phpini_open_basedir'])
		? clean_input($_POST['phpini_open_basedir']) : $cfg->PHPINI_OPEN_BASEDIR;

	if ($cfg['HTTPD_SERVER'] != 'apache_itk') {
		$disabledFunctions = array();

		foreach (
			array(
				'show_source', 'system', 'shell_exec', 'shell_exec', 'passthru', 'exec',  'phpinfo', 'shell', 'symlink',
				'proc_open', 'popen'
			) as $function
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
	} elseif (
		! is_number($lostPasswdTimeout) ||
		! is_number($passwdChars) ||
		! is_number($bruteforce_max_login) ||
		! is_number($bruteforce_block_time) ||
		! is_number($bruteforce_between_time) ||
		! is_number($bruteforce_max_capcha) ||
		! is_number($bruteforce_max_attempts_before_wait) ||
		! is_number($domainRowsPerPage)
	) {
		set_page_message(tr('Only positive numbers are allowed.'), 'error');
	} elseif ($domainRowsPerPage < 1) {
		$domainRowsPerPage = 1;
	} elseif ($phpini->flagValueError) { // if a php value was out of range or simple wrong type
		set_page_message(tr('Error in php.ini values.'), 'error');
	} else {
		/** @var $dbCfg iMSCP_Config_Handler_Db */
		$dbCfg = iMSCP_Registry::get('dbConfig');

		$dbCfg->LOSTPASSWORD = $lostPasswd;
		$dbCfg->LOSTPASSWORD_TIMEOUT = $lostPasswdTimeout;
		$dbCfg->PASSWD_CHARS = $passwdChars;
		$dbCfg->PASSWD_STRONG = $passwdStrong;
		$dbCfg->BRUTEFORCE = $bruteforce;
		$dbCfg->BRUTEFORCE_BETWEEN = $bruteforce_between;
		$dbCfg->BRUTEFORCE_MAX_LOGIN = $bruteforce_max_login;
		$dbCfg->BRUTEFORCE_BLOCK_TIME = $bruteforce_block_time;
		$dbCfg->BRUTEFORCE_BETWEEN_TIME = $bruteforce_between_time;
		$dbCfg->BRUTEFORCE_MAX_CAPTCHA = $bruteforce_max_capcha;
		$dbCfg->BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT = $bruteforce_max_attempts_before_wait;
		$dbCfg->CREATE_DEFAULT_EMAIL_ADDRESSES = $createDefaultEmails;
		$dbCfg->COUNT_DEFAULT_EMAIL_ADDRESSES = $countDefaultEmails;
		$dbCfg->HARD_MAIL_SUSPENSION = $hardMailSuspension;
		$dbCfg->EMAIL_QUOTA_SYNC_MODE = $emailQuotaSyncMode;
		$dbCfg->USER_INITIAL_LANG = $userInitialLang;
		$dbCfg->IMSCP_SUPPORT_SYSTEM = $supportSystem;
		$dbCfg->HOSTING_PLANS_LEVEL = $hostingPlanLevel;
		$dbCfg->DOMAIN_ROWS_PER_PAGE = $domainRowsPerPage;
		$dbCfg->LOG_LEVEL = $logLevel;
		$dbCfg->CHECK_FOR_UPDATES = $checkForUpdate;
		$dbCfg->ENABLE_SSL = $enableSSL;
		$dbCfg->COMPRESS_OUTPUT = $compressOutput;
		$dbCfg->SHOW_COMPRESSION_SIZE = $showCompressionSize;
		$dbCfg->PREVENT_EXTERNAL_LOGIN_ADMIN = $prevExtLoginAdmin;
		$dbCfg->PREVENT_EXTERNAL_LOGIN_RESELLER = $prevExtLoginReseller;
		$dbCfg->PREVENT_EXTERNAL_LOGIN_CLIENT = $prevExtLoginClient;
		$dbCfg->PHPINI_ALLOW_URL_FOPEN = $phpini->getDataVal('phpiniAllowUrlFopen');
		$dbCfg->PHPINI_DISPLAY_ERRORS = $phpini->getDataVal('phpiniDisplayErrors');
		$dbCfg->PHPINI_ERROR_REPORTING = $phpini->getDataVal('phpiniErrorReporting');
		$dbCfg->PHPINI_POST_MAX_SIZE = $phpini->getDataVal('phpiniPostMaxSize');
		$dbCfg->PHPINI_UPLOAD_MAX_FILESIZE = $phpini->getDataVal('phpiniUploadMaxFileSize');
		$dbCfg->PHPINI_MAX_EXECUTION_TIME = $phpini->getDataVal('phpiniMaxExecutionTime');
		$dbCfg->PHPINI_MAX_INPUT_TIME = $phpini->getDataVal('phpiniMaxInputTime');
		$dbCfg->PHPINI_MEMORY_LIMIT = $phpini->getDataVal('phpiniMemoryLimit');
		$dbCfg->PHPINI_OPEN_BASEDIR = $phpini_open_basedir;
		$dbCfg->PHPINI_DISABLE_FUNCTIONS = $phpini->getDataVal('phpiniDisableFunctions');

		if($cfg->replaceWith($dbCfg)) {
			iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditAdminGeneralSettings);

			// gets the number of queries that were been executed
			$updtCount = $dbCfg->countQueries('update');
			$newCount = $dbCfg->countQueries('insert');

			// An Update was been made in the database ?
			if ($updtCount > 0) {
				set_page_message(tr('%d configuration parameter(s) have/has been updated.', $updtCount), 'success');
			}

			if ($newCount > 0) {
				set_page_message(tr('%d configuration parameter(s) have/has been created.', $newCount), 'success');
			}

			if ($newCount == 0 && $updtCount == 0) {
				set_page_message(tr("Nothing has been changed."), 'info');
			} else {
				write_log("{$_SESSION['user_logged']} updated settings.", E_USER_NOTICE);
			}
		} else {
			set_page_message(tr('An unexpected error occured. Please retry'), 'error');
		}
	}

	// Fix to see changes on next load
	redirectTo('settings.php');
}

$coid = '';

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/settings.tpl',
		'page_message' => 'layout',
		'def_language' => 'page',
		'php_editor_disable_functions_block' => 'page'
	)
);

// Grab the value only once to improve performances
$htmlSelected = $cfg->HTML_SELECTED;

if ($cfg->LOSTPASSWORD) {
	$tpl->assign(
		array(
			 'LOSTPASSWORD_SELECTED_ON' => $htmlSelected,
			 'LOSTPASSWORD_SELECTED_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'LOSTPASSWORD_SELECTED_ON' => '',
			 'LOSTPASSWORD_SELECTED_OFF', $htmlSelected
		)
	);
}

if ($cfg->PASSWD_STRONG) {
	$tpl->assign(
		array(
			 'PASSWD_STRONG_ON' => $htmlSelected,
			 'PASSWD_STRONG_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'PASSWD_STRONG_ON' => '',
			 'PASSWD_STRONG_OFF' => $htmlSelected
		)
	);
}

if ($cfg->BRUTEFORCE) {
	$tpl->assign(
		array(
			 'BRUTEFORCE_SELECTED_ON' => $htmlSelected,
			 'BRUTEFORCE_SELECTED_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'BRUTEFORCE_SELECTED_ON' => '',
			 'BRUTEFORCE_SELECTED_OFF' => $htmlSelected
		)
	);
}

if ($cfg->BRUTEFORCE_BETWEEN) {
	$tpl->assign(
		array(
			 'BRUTEFORCE_BETWEEN_SELECTED_ON' => $htmlSelected,
			 'BRUTEFORCE_BETWEEN_SELECTED_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'BRUTEFORCE_BETWEEN_SELECTED_ON' => '',
			 'BRUTEFORCE_BETWEEN_SELECTED_OFF' => $htmlSelected
		)
	);
}

if ($cfg->IMSCP_SUPPORT_SYSTEM) {
	$tpl->assign(
		array(
			 'SUPPORT_SYSTEM_SELECTED_ON' => $htmlSelected,
			 'SUPPORT_SYSTEM_SELECTED_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'SUPPORT_SYSTEM_SELECTED_ON' => '',
			 'SUPPORT_SYSTEM_SELECTED_OFF' => $htmlSelected
		)
	);
}

if ($cfg->CREATE_DEFAULT_EMAIL_ADDRESSES) {
	$tpl->assign(
		array(
			 'CREATE_DEFAULT_EMAIL_ADDRESSES_ON' => $htmlSelected,
			 'CREATE_DEFAULT_EMAIL_ADDRESSES_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'CREATE_DEFAULT_EMAIL_ADDRESSES_ON' => '',
			 'CREATE_DEFAULT_EMAIL_ADDRESSES_OFF' => $htmlSelected
		)
	);
}

if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES) {
	$tpl->assign(
		array(
			 'COUNT_DEFAULT_EMAIL_ADDRESSES_ON' => $htmlSelected,
			 'COUNT_DEFAULT_EMAIL_ADDRESSES_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'COUNT_DEFAULT_EMAIL_ADDRESSES_ON' => '',
			 'COUNT_DEFAULT_EMAIL_ADDRESSES_OFF' => $htmlSelected
		)
	);
}

if ($cfg->HARD_MAIL_SUSPENSION) {
	$tpl->assign(
		array(
			 'HARD_MAIL_SUSPENSION_ON' => $htmlSelected,
			 'HARD_MAIL_SUSPENSION_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'HARD_MAIL_SUSPENSION_ON' => '',
			 'HARD_MAIL_SUSPENSION_OFF' => $htmlSelected
		)
	);
}

if (isset($cfg['EMAIL_QUOTA_SYNC_MODE']) && $cfg['EMAIL_QUOTA_SYNC_MODE']) {
	$tpl->assign(
		array(
			'REDISTRIBUTE_EMAIl_QUOTA_YES' => $htmlSelected,
			'REDISTRIBUTE_EMAIl_QUOTA_NO' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			'REDISTRIBUTE_EMAIl_QUOTA_YES' => '',
			'REDISTRIBUTE_EMAIl_QUOTA_NO' => $htmlSelected
		)
	);
}

if ($cfg->HOSTING_PLANS_LEVEL == 'admin') {
	$tpl->assign(
		array(
			 'HOSTING_PLANS_LEVEL_ADMIN' => $htmlSelected,
			 'HOSTING_PLANS_LEVEL_RESELLER' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'HOSTING_PLANS_LEVEL_ADMIN' => '',
			 'HOSTING_PLANS_LEVEL_RESELLER' => $htmlSelected
		)
	);
}

if ($cfg->CHECK_FOR_UPDATES) {
	$tpl->assign(
		array(
			 'CHECK_FOR_UPDATES_SELECTED_ON' => $htmlSelected,
			 'CHECK_FOR_UPDATES_SELECTED_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'CHECK_FOR_UPDATES_SELECTED_ON' => '',
			 'CHECK_FOR_UPDATES_SELECTED_OFF' => $htmlSelected
		)
	);
}

if ($cfg->ENABLE_SSL) {
	$tpl->assign(
		array(
			 'ENABLE_SSL_ON' => $htmlSelected,
			 'ENABLE_SSL_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'ENABLE_SSL_ON' => '',
			 'ENABLE_SSL_OFF' => $htmlSelected
		)
	);
}

if ($cfg->COMPRESS_OUTPUT) {
	$tpl->assign(
		array(
			 'COMPRESS_OUTPUT_ON' => $htmlSelected,
			 'COMPRESS_OUTPUT_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'COMPRESS_OUTPUT_ON' => '',
			 'COMPRESS_OUTPUT_OFF' => $htmlSelected
		)
	);
}

if ($cfg->SHOW_COMPRESSION_SIZE) {
	$tpl->assign(
		array(
			 'SHOW_COMPRESSION_SIZE_SELECTED_ON' => $htmlSelected,
			 'SHOW_COMPRESSION_SIZE_SELECTED_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'SHOW_COMPRESSION_SIZE_SELECTED_ON' => '',
			 'SHOW_COMPRESSION_SIZE_SELECTED_OFF' => $htmlSelected
		)
	);
}

if ($cfg->PREVENT_EXTERNAL_LOGIN_ADMIN) {
	$tpl->assign(
		array(
			 'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_ON' => $htmlSelected,
			 'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_ON' => '',
			 'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_OFF' => $htmlSelected
		)
	);
}

if ($cfg->PREVENT_EXTERNAL_LOGIN_RESELLER) {
	$tpl->assign(
		array(
			 'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON' => $htmlSelected,
			 'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON' => '',
			 'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF' => $htmlSelected
		)
	);
}

if ($cfg->PREVENT_EXTERNAL_LOGIN_CLIENT) {
	$tpl->assign(
		array(
			 'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON' => $htmlSelected,
			 'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON' => '',
			 'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF' => $htmlSelected
		)
	);
}

if ($phpini->getDataVal('phpiniAllowUrlFopen') == 'on') {
	$tpl->assign(
		array(
			 'PHPINI_ALLOW_URL_FOPEN_ON' => $htmlSelected,
			 'PHPINI_ALLOW_URL_FOPEN_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'PHPINI_ALLOW_URL_FOPEN_ON' => '',
			 'PHPINI_ALLOW_URL_FOPEN_OFF' => $htmlSelected
		)
	);
}

if ($phpini->getDataVal('phpiniDisplayErrors') == 'on') {
	$tpl->assign(
		array(
			 'PHPINI_DISPLAY_ERRORS_ON' => $htmlSelected,
			 'PHPINI_DISPLAY_ERRORS_OFF' => ''
		)
	);
} else {
	$tpl->assign(
		array(
			 'PHPINI_DISPLAY_ERRORS_ON' => '',
			 'PHPINI_DISPLAY_ERRORS_OFF' => $htmlSelected
		)
	);
}

$errorReportingValue = $phpini->errorReportingToLitteral($phpini->getDataVal('phpiniErrorReporting'));

switch ($errorReportingValue) {
	case 'E_ALL & ~E_NOTICE':
		$tpl->assign(
			array(
				 'PHPINI_ERROR_REPORTING_0' => $htmlSelected,
				 'PHPINI_ERROR_REPORTING_1' => '',
				 'PHPINI_ERROR_REPORTING_2' => '',
				 'PHPINI_ERROR_REPORTING_3' => ''
			)
		);
		break;
	case 'E_ALL | E_STRICT':
		$tpl->assign(
			array(
				 'PHPINI_ERROR_REPORTING_0' => '',
				 'PHPINI_ERROR_REPORTING_1' => $htmlSelected,
				 'PHPINI_ERROR_REPORTING_2' => '',
				 'PHPINI_ERROR_REPORTING_3' => ''
			)
		);
		break;
	case 'E_ALL & ~E_DEPRECATED':
		$tpl->assign(
			array(
				 'PHPINI_ERROR_REPORTING_0' => '',
				 'PHPINI_ERROR_REPORTING_1' => '',
				 'PHPINI_ERROR_REPORTING_2' => $htmlSelected,
				 'PHPINI_ERROR_REPORTING_3' => ''
			)
		);
		break;
	case '0':
		$tpl->assign(
			array(
				 'PHPINI_ERROR_REPORTING_0' => '',
				 'PHPINI_ERROR_REPORTING_1' => '',
				 'PHPINI_ERROR_REPORTING_2' => '',
				 'PHPINI_ERROR_REPORTING_3' => $htmlSelected
			)
		);
		break;
}

$htmlChecked = $cfg->HTML_CHECKED;

if (PHP_SAPI != 'apache2handler') {
	$disabledFunctions = explode(',', $phpini->getDataVal('phpiniDisableFunctions'));
	$disabledFunctionsAll = array(
		'SHOW_SOURCE', 'SYSTEM', 'SHELL_EXEC', 'PASSTHRU', 'EXEC', 'PHPINFO', 'SHELL', 'SYMLINK', 'PROC_OPEN', 'POPEN',
	);

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
				 'LOG_LEVEL_SELECTED_OFF' => $htmlSelected,
				 'LOG_LEVEL_SELECTED_NOTICE' => '',
				 'LOG_LEVEL_SELECTED_WARNING' => '',
				 'LOG_LEVEL_SELECTED_ERROR' => ''
			)
		);
		break;
	case E_USER_NOTICE:
		$tpl->assign(
			array(
				 'LOG_LEVEL_SELECTED_OFF' => '',
				 'LOG_LEVEL_SELECTED_NOTICE' => $htmlSelected,
				 'LOG_LEVEL_SELECTED_WARNING' => '',
				 'LOG_LEVEL_SELECTED_ERROR' => ''
			)
		);
		break;
	case E_USER_WARNING:
		$tpl->assign(
			array(
				 'LOG_LEVEL_SELECTED_OFF' => '',
				 'LOG_LEVEL_SELECTED_NOTICE' => '',
				 'LOG_LEVEL_SELECTED_WARNING' => $htmlSelected,
				 'LOG_LEVEL_SELECTED_ERROR' => ''
			)
		);
		break;
	default:
		$tpl->assign(
			array(
				 'LOG_LEVEL_SELECTED_OFF' => '',
				 'LOG_LEVEL_SELECTED_NOTICE' => '',
				 'LOG_LEVEL_SELECTED_WARNING' => '',
				 'LOG_LEVEL_SELECTED_ERROR' => $htmlSelected
			)
		);
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_UPDATES' => tr('Updates'),
		'LOSTPASSWORD_TIMEOUT_VALUE' => $cfg->LOSTPASSWORD_TIMEOUT,
		'PASSWD_CHARS' => $cfg->PASSWD_CHARS,
		'BRUTEFORCE_MAX_LOGIN_VALUE' => $cfg->BRUTEFORCE_MAX_LOGIN,
		'BRUTEFORCE_BLOCK_TIME_VALUE' => $cfg->BRUTEFORCE_BLOCK_TIME,
		'BRUTEFORCE_BETWEEN_TIME_VALUE' => $cfg->BRUTEFORCE_BETWEEN_TIME,
		'BRUTEFORCE_MAX_CAPTCHA' => $cfg->BRUTEFORCE_MAX_CAPTCHA,
		'BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT' => $cfg->BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT,
		'DOMAIN_ROWS_PER_PAGE' => $cfg->DOMAIN_ROWS_PER_PAGE,
		'PHPINI_POST_MAX_SIZE' => $phpini->getDataVal('phpiniPostMaxSize'),
		'PHPINI_UPLOAD_MAX_FILESIZE' => $phpini->getDataVal('phpiniUploadMaxFileSize'),
		'PHPINI_MAX_EXECUTION_TIME' => $phpini->getDataVal('phpiniMaxExecutionTime'),
		'PHPINI_MAX_INPUT_TIME' => $phpini->getDataVal('phpiniMaxInputTime'),
		'PHPINI_MEMORY_LIMIT' => $phpini->getDataVal('phpiniMemoryLimit'),
		'PHPINI_OPEN_BASEDIR' => $cfg->PHPINI_OPEN_BASEDIR,
		'TR_SETTINGS' => tr('Settings'),
		'TR_MESSAGE' => tr('Message'),
		'TR_LOSTPASSWORD' => tr('Lost password'),
		'TR_LOSTPASSWORD_TIMEOUT' => tr('Activation link expire time <small>(In minutes)</small>'),
		'TR_PASSWORD_SETTINGS' => tr('Password settings'),
		'TR_PASSWD_STRONG' => tr('Use strong Passwords'),
		'TR_PASSWD_CHARS' => tr('Password length'),
		'TR_BRUTEFORCE' => tr('Bruteforce detection'),
		'TR_BRUTEFORCE_BETWEEN' => tr('Blocking time between logins and captcha attempts'),
		'TR_BRUTEFORCE_MAX_LOGIN' => tr('Max number of login attempts'),
		'TR_BRUTEFORCE_BLOCK_TIME' => tr('Blocktime <small>(in minutes)</small>'),
		'TR_BRUTEFORCE_BETWEEN_TIME' => tr('Blocking time between login/captcha attempts <small>(In seconds)</small>'),
		'TR_BRUTEFORCE_MAX_CAPTCHA' => tr('Maximum number of captcha validation attempts'),
		'TR_BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT' => tr('Maximum number of validation attempts before waiting restriction intervenes'),
		'TR_OTHER_SETTINGS' => tr('Other settings'),
		'TR_MAIL_SETTINGS' => tr('Email settings'),
		'TR_CREATE_DEFAULT_EMAIL_ADDRESSES' => tr('Create default email addresses'),
		'TR_COUNT_DEFAULT_EMAIL_ADDRESSES' => tr('Count default email addresses'),
		'TR_HARD_MAIL_SUSPENSION' => tr('Email accounts are hard suspended'),
		'TR_EMAIL_QUOTA_SYNC_MODE' => tr('Redistribute unused quota across existing mailboxes'),
		'TR_USER_INITIAL_LANG' => tr('Panel default language'),
		'TR_SUPPORT_SYSTEM' => tr('Support system'),
		'TR_ENABLED' => tr('Enabled'),
		'TR_DISABLED' => tr('Disabled'),
		'TR_YES' => tr('Yes'),
		'TR_NO' => tr('No'),
		'TR_UPDATE' => tr('Update'),
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
		'TR_SSL_HELP' => tr('Defines whether or not customers can add/change SSL certificates for their domains.'),
		'TR_COMPRESS_OUTPUT' => tr('Compress HTML output'),
		'TR_SHOW_COMPRESSION_SIZE' => tr('Show HTML output compression size comment'),
		'TR_PREVENT_EXTERNAL_LOGIN_ADMIN' => tr('Prevent external login for admins'),
		'TR_PREVENT_EXTERNAL_LOGIN_RESELLER' => tr('Prevent external login for resellers'),
		'TR_PREVENT_EXTERNAL_LOGIN_CLIENT' => tr('Prevent external login for clients'),
		'TR_PHPINI_BASE_SETTINGS' => tr('PHP Settings (system default)'),
		'TR_PHPINI_ALLOW_URL_FOPEN' => tr('Value for the %s directive', true, '<b>allow_url_fopen</b>'),
		'TR_PHPINI_DISPLAY_ERRORS' => tr('Value for the %s directive', true, '<b>display_errors</b>'),
		'TR_PHPINI_ERROR_REPORTING' => tr('Value for the %s directive', true, '<b>error_reporting</b>'),
		'TR_PHPINI_ERROR_REPORTING_DEFAULT' => tr('Show all errors, except for notices and coding standards warnings (Default)'),
		'TR_PHPINI_ERROR_REPORTING_DEVELOPEMENT' => tr('Show all errors, warnings and notices including coding standards (Development)'),
		'TR_PHPINI_ERROR_REPORTING_PRODUCTION' => tr(' Show all errors, except for warnings about deprecated code (Production)'),
		'TR_PHPINI_ERROR_REPORTING_NONE' => tr('Do not show any error'),
		'TR_PHPINI_POST_MAX_SIZE' => tr('Value for the %s directive', true, '<b>post_max_size</b>'),
		'TR_PHPINI_UPLOAD_MAX_FILESIZE' => tr('Value for the %s directive', true, '<b>upload_max_filesize</b>'),
		'TR_PHPINI_MAX_EXECUTION_TIME' => tr('Value for the %s directive', true, '<b>max_execution_time</b>'),
		'TR_PHPINI_MAX_INPUT_TIME' => tr('Value for the %s directive', true, '<b>max_input_time</b>'),
		'TR_PHPINI_MEMORY_LIMIT' => tr('Value for the %s directive', true, '<b>memory_limit</b>'),
		'TR_PHPINI_OPEN_BASEDIR' => tr('Value for the %s directive', true, '<b>open_basedir</b>'),
		'TR_PHPINI_OPEN_BASEDIR_TOOLTIP' => json_encode(tr('Paths are appended to the default PHP open_basedir directive of customers. Each of them must be separated by PATH_SEPARATOR. See the PHP documentation for more information.')),
		'TR_PHPINI_DISABLE_FUNCTIONS' => tr('Value for the %s directive', true, '<b>disable_functions</b>'),
		'TR_MIB' => tr('MiB'),
		'TR_SEC' => tr('Sec.')
	)
);

generateNavigation($tpl);
gen_def_language($tpl, $cfg->USER_INITIAL_LANG);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
