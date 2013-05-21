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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2013 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate PHP editor block
 *
 * @param iMSCP_pTemplate $tpl
 * @param iMSCP_PHPini $phpini
 * @return void
 */
function _generatePhpBlock($tpl, $phpini)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$htmlChecked = $cfg->HTML_CHECKED;

	$tplVars = array();

	if ($phpini->checkRePerm('phpiniSystem')) {
		$tplVars['PHP_EDITOR_YES'] = ($phpini->getClPermVal('phpiniSystem') == 'yes') ? $htmlChecked : '';
		$tplVars['PHP_EDITOR_NO'] = ($phpini->getClPermVal('phpiniSystem') != 'yes') ? $htmlChecked : '';
		$tplVars['TR_PHP_EDITOR'] = tr('PHP Editor');
		$tplVars['TR_PHP_EDITOR_SETTINGS'] = tr('PHP Editor Settings');
		$tplVars['TR_SETTINGS'] = tr('Settings');
		$tplVars['TR_DIRECTIVES_VALUES'] = tr('Directive values');
		$tplVars['TR_FIELDS_OK'] = tr('All fields seem to be valid.');
		$tplVars['TR_VALUE_ERROR'] = tr('Value for the PHP <strong>%%s</strong> directive must be between %%d and %%d.', true);
		$tplVars['TR_CLOSE'] = tr('Close');
		$tplVars['TR_PHP_POST_MAX_SIZE_DIRECTIVE'] = tr('PHP %s directive', true, '<b>post_max_size</b>');
		$tplVars['PHP_UPLOAD_MAX_FILESIZE_DIRECTIVE'] = tr('PHP %s directive', true, '<b>upload_max_filezize</b>');
		$tplVars['TR_PHP_MAX_EXECUTION_TIME_DIRECTIVE'] = tr('PHP %s directive', true, '<b>max_execution_time</b>');
		$tplVars['TR_PHP_MAX_INPUT_TIME_DIRECTIVE'] = tr('PHP %s directive', true, '<b>max_input_time</b>');
		$tplVars['TR_PHP_MEMORY_LIMIT_DIRECTIVE'] = tr('PHP %s directive', true, '<b>memory_limit</b>');
		$tplVars['TR_MIB'] = tr('MiB');
		$tplVars['TR_SEC'] = tr('Sec.');

		$permissionsBlock = false;

		if (!$phpini->checkRePerm('phpiniAllowUrlFopen')) {
			$tplVars['PHP_EDITOR_ALLOW_URL_FOPEN_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_ALLOW_URL_FOPEN'] = tr('Can edit the PHP %s directive', true, '<b>allow_url_fopen</b>');
			$tplVars['ALLOW_URL_FOPEN_YES'] = ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'yes') ? $htmlChecked : '';
			$tplVars['ALLOW_URL_FOPEN_NO'] = ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'no') ? $htmlChecked : '';
			$permissionsBlock = true;
		}

		if (!$phpini->checkRePerm('phpiniDisplayErrors')) {
			$tplVars['PHP_EDITOR_DISPLAY_ERRORS_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_DISPLAY_ERRORS'] = tr('Can edit the PHP %s directive', true, '<b>display_errors</b>');
			$tplVars['DISPLAY_ERRORS_YES'] = ($phpini->getClPermVal('phpiniDisplayErrors') == 'yes') ? $htmlChecked : '';
			$tplVars['DISPLAY_ERRORS_NO'] = ($phpini->getClPermVal('phpiniDisplayErrors') == 'no') ? $htmlChecked : '';
			$permissionsBlock = true;
		}

		if (PHP_SAPI == 'apache2handler' || !$phpini->checkRePerm('phpiniDisableFunctions')) {
			$tplVars['PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_DISABLE_FUNCTIONS'] = tr('Can edit the PHP %s directive', true, '<b>disable_functions</b>');
			$tplVars['DISABLE_FUNCTIONS_YES'] = ($phpini->getClPermVal('phpiniDisableFunctions') == 'yes') ? $htmlChecked : '';
			$tplVars['DISABLE_FUNCTIONS_NO'] = ($phpini->getClPermVal('phpiniDisableFunctions') == 'no') ? $htmlChecked : '';
			$tplVars['TR_ONLY_EXEC'] = tr('Only exec');
			$tplVars['DISABLE_FUNCTIONS_EXEC'] = ($phpini->getClPermVal('phpiniDisableFunctions') == 'exec') ? $htmlChecked : '';
			$permissionsBlock = true;
		}

		if (!$permissionsBlock) {
			$tplVars['PHP_EDITOR_PERMISSIONS_BLOCK'] = '';
		} else {
			$tplVars['TR_PERMISSIONS'] = tr('Permissions');
			$tplVars['TR_ONLY_EXEC'] = tr('Only exec');
		}

		if (!empty($_POST)) {
			$tplVars['POST_MAX_SIZE'] = $phpini->getDataVal('phpiniPostMaxSize');
			$tplVars['UPLOAD_MAX_FILESIZE'] = $phpini->getDataVal('phpiniUploadMaxFileSize');
			$tplVars['MAX_EXECUTION_TIME'] =$phpini->getDataVal('phpiniMaxExecutionTime');
			$tplVars['MAX_INPUT_TIME'] = $phpini->getDataVal('phpiniMaxInputTime');
			$tplVars['MEMORY_LIMIT'] = $phpini->getDataVal('phpiniMemoryLimit');
		} else {
			$tplVars['POST_MAX_SIZE'] = $phpini->getReDefaultPermVal('phpiniPostMaxSize');
			$tplVars['UPLOAD_MAX_FILESIZE'] = $phpini->getRePermVal('phpiniUploadMaxFileSize');
			$tplVars['MAX_EXECUTION_TIME'] = $phpini->getRePermVal('phpiniMaxExecutionTime');
			$tplVars['MAX_INPUT_TIME'] = $phpini->getRePermVal('phpiniMaxInputTime');
			$tplVars['MEMORY_LIMIT'] = $phpini->getReDefaultPermVal('phpiniMemoryLimit');
		}

		$tplVars['PHP_DIRECTIVES_MAX_VALUES'] = json_encode(
			array(
				'post_max_size' => $phpini->getRePermVal('phpiniPostMaxSize'),
				'upload_max_filesize' => $phpini->getRePermVal('phpiniUploadMaxFileSize'),
				'max_execution_time' => $phpini->getRePermVal('phpiniMaxExecutionTime'),
				'max_input_time' => $phpini->getRePermVal('phpiniMaxInputTime'),
				'memory_limit' => $phpini->getRePermVal('phpiniMemoryLimit')
			)
		);
	} else {
		$tplVars['PHP_EDITOR_JS'] = '';
		$tplVars['PHP_EDITOR_BLOCK'] = '';
	}

	$tpl->assign($tplVars);
}

/**
 * Generate form
 *
 * @param iMSCP_pTemplate $tpl
 * @param iMSCP_PHPini $phpini
 * @return void
 */
function generateForm($tpl, $phpini)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$htmlChecked = $cfg->HTML_CHECKED;

	$tpl->assign(
		array(
			'HP_NAME_VALUE' => '',
			'HP_DESCRIPTION_VALUE' => '',

			'TR_MAX_SUB_LIMITS' => '0',
			'TR_MAX_ALS_VALUES' => '0',
			'HP_MAIL_VALUE' => '0',
			'HP_FTP_VALUE' => '0',
			'HP_SQL_DB_VALUE' => '0',
			'HP_SQL_USER_VALUE' => '0',
			'HP_TRAFF_VALUE' => '0',
			'HP_DISK_VALUE' => '0',

			'TR_PHP_YES' => '',
			'TR_PHP_NO' => $htmlChecked,
			'TR_CGI_YES' => '',
			'TR_CGI_NO' => $htmlChecked,
			'TR_DNS_YES' => '',
			'TR_DNS_NO' => $htmlChecked,
			'VL_BACKUPD' => '',
			'VL_BACKUPS' => '',
			'VL_BACKUPF' => '',
			'VL_BACKUPN' => $htmlChecked,
			'TR_SOFTWARE_YES' => '',
			'TR_SOFTWARE_NO' => $htmlChecked,
			'TR_EXTMAIL_YES' => '',
			'TR_EXTMAIL_NO' => $htmlChecked,

			'TR_STATUS_YES' => $htmlChecked,
			'TR_STATUS_NO' => '',

			'READONLY' => '',
		)
	);

	_generatePhpBlock($tpl, $phpini);
}

/**
 * Generate error form
 *
 * @param iMSCP_pTemplate $tpl
 * @param iMSCP_PHPini $phpini
 * @return void
 */
function generateErrorForm($tpl, $phpini)
{
	global $hpName, $description, $hpSub, $hpAls, $hpMail, $hpFtp, $hpSqlDb, $hpSqlUsers, $hpTraffic, $hpDiskspace,
		$hpPhp, $hpCgi, $hpBackup, $hpDns, $hpSoftwaresInstaller, $hpExtMail, $status;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$htmlChecked = $cfg->HTML_CHECKED;

	$tpl->assign(
		array(
			'HP_NAME_VALUE' => tohtml($hpName),
			'HP_DESCRIPTION_VALUE' => tohtml($description),

			'TR_MAX_SUB_LIMITS' => tohtml($hpSub),
			'TR_MAX_ALS_VALUES' => tohtml($hpAls),
			'HP_MAIL_VALUE' => tohtml($hpMail),
			'HP_FTP_VALUE' => tohtml($hpFtp),
			'HP_SQL_DB_VALUE' => tohtml($hpSqlDb),
			'HP_SQL_USER_VALUE' => tohtml($hpSqlUsers),
			'HP_TRAFF_VALUE' => tohtml($hpTraffic),
			'HP_DISK_VALUE' => tohtml($hpDiskspace),

			'TR_PHP_YES' => ($hpPhp == '_yes_') ? $htmlChecked : '',
			'TR_PHP_NO' => ($hpPhp == '_no_') ? $cfg->HTML_CHECKED : '',
			'TR_CGI_YES' => ($hpCgi == '_yes_') ? $htmlChecked : '',
			'TR_CGI_NO' => ($hpCgi == '_no_') ? $htmlChecked : '',
			'TR_DNS_YES' => ($hpDns == '_yes_') ? $htmlChecked : '',
			'TR_DNS_NO' => ($hpDns == '_no_') ? $htmlChecked : '',
			'VL_BACKUPD' => ($hpBackup == '_dmn_') ? $htmlChecked : '',
			'VL_BACKUPS' => ($hpBackup == '_sql_') ? $htmlChecked : '',
			'VL_BACKUPF' => ($hpBackup == '_full_') ? $htmlChecked : '',
			'VL_BACKUPN' => ($hpBackup == '_no_') ? $htmlChecked : '',
			'TR_SOFTWARE_YES' => ($hpSoftwaresInstaller == '_yes_') ? $htmlChecked : '',
			'TR_SOFTWARE_NO' => ($hpSoftwaresInstaller == '_no_') ? $htmlChecked : '',
			'TR_EXTMAIL_YES' => ($hpExtMail == '_yes_') ? $htmlChecked : '',
			'TR_EXTMAIL_NO' => ($hpExtMail == '_no_') ? $htmlChecked : '',

			'TR_STATUS_YES' => ($status) ? $htmlChecked : '',
			'TR_STATUS_NO' => (!$status) ? $htmlChecked : '',

			'READONLY' => ''
		)
	);

	_generatePhpBlock($tpl, $phpini);
}

/**
 * Check input data
 *
 * @param iMSCP_PHPini $phpini
 * @return bool TRUE if data are valid, FALSE otherwise
 */
function checkInputData($phpini)
{
	global $hpName, $description, $hpSub, $hpAls, $hpMail, $hpFtp, $hpSqlDb, $hpSqlUsers, $hpTraffic, $hpDiskspace,
		$hpPhp, $hpCgi, $hpDns, $hpBackup, $hpSoftwaresInstaller, $hpExtMail, $status;

	$hpName = isset($_POST['hp_name']) ? clean_input($_POST['hp_name']) : '';
	$description = isset($_POST['hp_description']) ? clean_input($_POST['hp_description']) : '';

	$hpSub = isset($_POST['hp_sub']) ? clean_input($_POST['hp_sub']) : '-1';
	$hpAls = isset($_POST['hp_als']) ? clean_input($_POST['hp_als']) : '-1';
	$hpMail = isset($_POST['hp_mail']) ? clean_input($_POST['hp_mail']) : '-1';
	$hpFtp = isset($_POST['hp_ftp']) ? clean_input($_POST['hp_ftp']) : '-1';
	$hpSqlDb = isset($_POST['hp_sql_db']) ? clean_input($_POST['hp_sql_db']) : '-1';
	$hpSqlUsers = isset($_POST['hp_sql_user']) ? clean_input($_POST['hp_sql_user']) : '-1';
	$hpTraffic = isset($_POST['hp_traff']) ? clean_input($_POST['hp_traff']) : '';
	$hpDiskspace = isset($_POST['hp_disk']) ? clean_input($_POST['hp_disk']) : '';

	$hpPhp = isset($_POST['hp_php']) ? clean_input($_POST['hp_php']) : '_no_';
	$hpCgi = isset($_POST['hp_cgi']) ? clean_input($_POST['hp_cgi']) : '_no_';
	$hpDns = isset($_POST['hp_dns']) ? clean_input($_POST['hp_dns']) : '_no_';
	$hpBackup = isset($_POST['hp_backup']) ? clean_input($_POST['hp_backup']) : '_no_';
	$hpSoftwaresInstaller = isset($_POST['hp_softwares_installer']) ? clean_input($_POST['hp_softwares_installer']) : '_no_';
	$hpExtMail = isset($_POST['hp_external_mail']) ? clean_input($_POST['hp_external_mail']) : '_no_';

	$status = isset($_POST['hp_status']) ? clean_input($_POST['hp_status']) : '0';

	$hpPhp = ($hpPhp == '_yes_') ? '_yes_' : '_no_';
	$hpCgi = ($hpCgi == '_yes_') ? '_yes_' : '_no_';
	$hpDns = ($hpDns == '_yes_') ? '_yes_' : '_no_';
	$hpBackup = (in_array($hpBackup, array('_full_', '_dmn_', '_sql_'))) ? $hpBackup : '_no_';
	$hpSoftwaresInstaller = ($hpSoftwaresInstaller == '_yes_') ? '_yes_' : '_no_';
	$hpExtMail = ($hpExtMail == '_yes_') ? '_yes_' : '_no_';

	if ($hpName == '') set_page_message(tr('Name cannot be empty.'), 'error');
	if ($description == '') set_page_message(tr('Description cannot be empty.'), 'error');

	if (!resellerHasFeature('subdomains')) {
		$hpSub = '-1';
	} elseif (!imscp_limit_check($hpSub, -1)) {
		set_page_message(tr('Incorrect subdomains limit.'), 'error');
	}

	if (!resellerHasFeature('domain_aliases')) {
		$hpAls = '-1';
	} elseif (!imscp_limit_check($hpAls, -1)) {
		set_page_message(tr('Incorrect domain aliases limit.'), 'error');
	}

	if (!resellerHasFeature('mail')) {
		$hpMail = '-1';
	} elseif (!imscp_limit_check($hpMail, -1)) {
		set_page_message(tr('Incorrect mail accounts limit.'), 'error');
	}

	if (!resellerHasFeature('ftp')) {
		$hpFtp = '-1';
	} elseif (!imscp_limit_check($hpFtp, -1)) {
		set_page_message(tr('Incorrect FTP accounts limit.'), 'error');
	}

	if (!resellerHasFeature('sql_db')) {
		$hpSqlDb = '-1';
	} elseif (!imscp_limit_check($hpSqlDb, -1)) {
		set_page_message(tr('Incorrect SQL users limit.'), 'error');
	} elseif ($hpSqlUsers != -1 && $hpSqlDb == -1) {
		set_page_message(tr('SQL users limit is <i>disabled</i>.'), 'error');
	}

	if (!resellerHasFeature('sql_user')) {
		$hpSqlUsers = '-1';
	} elseif (!imscp_limit_check($hpSqlUsers, -1)) {
		set_page_message(tr('Incorrect SQL databases limit.'), 'error');
	} elseif ($hpSqlUsers == -1 && $hpSqlDb != -1) {
		set_page_message(tr('SQL databases limit is not <i>disabled</i>.'), 'error');
	}

	if (!imscp_limit_check($hpTraffic, null)) {
		set_page_message(tr('Incorrect monthly traffic limit.'), 'error');
	}

	if (!imscp_limit_check($hpDiskspace, null)) {
		set_page_message(tr('Incorrect disk space limit.'), 'error');
	}

	if ($phpini->checkRePerm('phpiniSystem') && isset($_POST['phpiniSystem'])) {
		$phpini->setClPerm('phpiniSystem', clean_input($_POST['phpiniSystem']));

		if ($phpini->checkRePerm('phpiniAllowUrlFopen') && isset($_POST['phpini_perm_allow_url_fopen'])) {
			$phpini->setClPerm('phpiniAllowUrlFopen', clean_input($_POST['phpini_perm_allow_url_fopen']));
		}

		if ($phpini->checkRePerm('phpiniDisplayErrors') && isset($_POST['phpini_perm_display_errors'])) {
			$phpini->setClPerm('phpiniDisplayErrors', clean_input($_POST['phpini_perm_display_errors']));
		}

		if (PHP_SAPI != 'apache2handler' && $phpini->checkRePerm('phpiniDisableFunctions') &&
			isset($_POST['phpini_perm_disable_functions'])
		) {
			$phpini->setClPerm('phpiniDisableFunctions', clean_input($_POST['phpini_perm_disable_functions']));
		}

		if (
			isset($_POST['post_max_size']) &&
			! $phpini->setDataWithPermCheck('phpiniPostMaxSize', clean_input($_POST['post_max_size']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'post_max_size'), 'error');
		}

		if (
			isset($_POST['upload_max_filesize']) &&
			! $phpini->setDataWithPermCheck('phpiniUploadMaxFileSize', clean_input($_POST['upload_max_filesize']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'upload_max_filesize'), 'error');
		}

		if (
			isset($_POST['max_execution_time']) &&
			! $phpini->setDataWithPermCheck('phpiniMaxExecutionTime', clean_input($_POST['max_execution_time']))
		) {
				set_page_message(tr('Value for the PHP %s directive is out of range.', 'max_execution_time'), 'error');
		}

		if (
			isset($_POST['max_input_time']) &&
			!$phpini->setDataWithPermCheck('phpiniMaxInputTime', clean_input($_POST['max_input_time']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'max_input_time'), 'error');
		}

		if (
			isset($_POST['memory_limit']) &&
			!$phpini->setDataWithPermCheck('phpiniMemoryLimit', clean_input($_POST['memory_limit']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'memory_limit'), 'error');
		}
	}

	if ($hpPhp == '_no_' && $hpSoftwaresInstaller == '_yes_') {
		set_page_message(tr('The software installer require PHP.'), 'error');
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		return true;
	} else {
		return false;
	}
}

/**
 * Save new hosting plan.
 *
 * @param int $resellerId Reseller unique identifier
 * @param iMSCP_PHPini $phpini
 * @return bool TRUE on success, FALSE otherwise
 */
function saveData($resellerId, $phpini)
{
	global $hpName, $description, $hpSub, $hpAls, $hpMail, $hpFtp, $hpSqlDb, $hpSqlUsers, $hpTraffic, $hpDiskspace,
		$hpPhp, $hpCgi, $hpDns, $hpBackup, $hpSoftwaresInstaller, $hpExtMail, $status;

	$query = "SELECT `id` FROM `hosting_plans` WHERE `name` = ? AND `reseller_id` = ? LIMIT 1";
	$stmt = exec_query($query, array($hpName, $resellerId));

	if ($stmt->rowCount()) {
		set_page_message(tr('An hosting plan with same name already exists.'), 'error');
		return false;
	} else {
		$hpProps = "$hpPhp;$hpCgi;$hpSub;$hpAls;$hpMail;$hpFtp;$hpSqlDb;$hpSqlUsers;$hpTraffic;$hpDiskspace;$hpBackup;";
		$hpProps .= "$hpDns;$hpSoftwaresInstaller";
		$hpProps .= ';' . $phpini->getClPermVal('phpiniSystem') . ';' . $phpini->getClPermVal('phpiniAllowUrlFopen');
		$hpProps .= ';' . $phpini->getClPermVal('phpiniDisplayErrors') . ';' . $phpini->getClPermVal('phpiniDisableFunctions');
		$hpProps .= ';' . $phpini->getDataVal('phpiniPostMaxSize') . ';' . $phpini->getDataVal('phpiniUploadMaxFileSize');
		$hpProps .= ';' . $phpini->getDataVal('phpiniMaxExecutionTime') . ';' . $phpini->getDataVal('phpiniMaxInputTime');
		$hpProps .= ';' . $phpini->getDataVal('phpiniMemoryLimit') . ';' . $hpExtMail;

		if (reseller_limits_check($resellerId, $hpProps)) {
			$query = "
				INSERT INTO `hosting_plans`(
					`reseller_id`, `name`, `description`, `props`, `status`
				) VALUES (?, ?, ?, ?, ?)
			";
			exec_query($query, array($resellerId, $hpName, $description, $hpProps, $status));

			return true;
		} else {
			set_page_message(tr("Hosting plan values exceed reseller maximum values."), 'error');
			return false;
		}
	}
}

/***********************************************************************************************************************
 * Functions
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
	showBadRequestErrorPage();
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'shared/partials/forms/hosting_plan_add.tpl',
		'page_message' => 'layout',
		'subdomain_add' => 'page',
		'alias_add' => 'page',
		'mail_add' => 'page',
		'ftp_add' => 'page',
		'sql_db_add' => 'page',
		'sql_user_add' => 'page',
		'backup_support' => 'page',
		'php_editor_js' => 'page',
		'php_editor_block' => 'page',
		'php_editor_permissions_block' => 'php_editor_block',
		'php_editor_allow_url_fopen_block' => 'php_editor_permissions_block',
		'php_editor_display_errors_block' => 'php_editor_permissions_block',
		'php_editor_disable_functions_block' => 'php_editor_permissions_block',
		'php_editor_default_values_block' => 'php_editor_block',
		't_software_support' => 'page'
	)
);

/* @var $phpini iMSCP_PHPini */
$phpini = iMSCP_PHPini::getInstance();
$phpini->loadRePerm($_SESSION['user_id']);

if (!empty($_POST)) {
	if (checkInputData($phpini) && saveData($_SESSION['user_id'], $phpini)) {
		set_page_message(tr('Hosting plan successfully created.'), 'success');
		redirectTo('hosting_plan.php');
	} else {
		generateErrorForm($tpl, $phpini);
	}
} else {
	generateForm($tpl, $phpini);
}

get_reseller_software_permission($tpl, $_SESSION['user_id']);

generateNavigation($tpl);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Reseller / Add hosting plan'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),

		'TR_ADD_HOSTING_PLAN' => tr('Add hosting plan'),
		'TR_HOSTING_PLAN_PROPS' => tr('Hosting plan properties'),

		'TR_NAME' => tr('Name'),
		'TR_DESCRIPTON' => tr('Description'),

		'TR_MAX_SUBDOMAINS' => tr('Max subdomains<br/><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_ALIASES' => tr('Max aliases<br/><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_MAILACCOUNTS' => tr('Mail accounts limit<br/><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_FTP' => tr('FTP accounts limit<br/><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_SQL' => tr('SQL databases limit<br/><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_SQL_USERS' => tr('SQL users limit<br/><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_TRAFFIC' => tr('Monthly traffic limit [MiB]<br/><i>(0 unlimited)</i>'),
		'TR_DISK_LIMIT' => tr('Disk space limit [MiB]<br/><i>(0 unlimited)</i>'),

		'TR_PHP' => tr('PHP'),
		'TR_CGI' => tr('CGI'),
		'TR_DNS' => tr('Custom DNS records'),
		'TR_BACKUP' => tr('Backup'),
		'TR_BACKUP_DOMAIN' => tr('Domain'),
		'TR_BACKUP_SQL' => tr('Sql'),
		'TR_BACKUP_FULL' => tr('Full'),
		'TR_BACKUP_NO' => tr('No'),
		'TR_SOFTWARE_SUPP' => tr('Software installer'),
		'TR_EXTMAIL' => tr('External mail server'),

		'TR_HP_AVAILABILITY' => tr('Hosting plan availability'),
		'TR_STATUS' => tr('Available'),

		'TR_YES' => tr('yes'),
		'TR_NO' => tr('no'),
		'TR_ADD_PLAN' => tr('Add')
	)
);

generatePageMessage($tpl);

if (!resellerHasFeature('subdomains')) $tpl->assign('SUBDOMAIN_ADD', '');
if (!resellerHasFeature('domain_aliases')) $tpl->assign('ALIAS_ADD', '');
if (!resellerHasFeature('mail')) $tpl->assign('MAIL_ADD', '');
if (!resellerHasFeature('ftp')) $tpl->assign('FTP_ADD', '');
if (!resellerHasFeature('sql_db')) $tpl->assign('SQL_DB_ADD', '');
if (!resellerHasFeature('sql_user')) $tpl->assign('SQL_USER_ADD', '');

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
