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
 * @subpackage  Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
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
function _reseller_generatePhpBlock($tpl, $phpini)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$checked = $cfg->HTML_CHECKED;

	$tplVars = array();

	if ($phpini->checkRePerm('phpiniSystem')) {
		$tplVars['PHP_EDITOR_YES'] = ($phpini->getClPermVal('phpiniSystem') == 'yes') ? $checked : '';
		$tplVars['PHP_EDITOR_NO'] = ($phpini->getClPermVal('phpiniSystem') != 'yes') ? $checked : '';
		$tplVars['TR_PHP_EDITOR'] = tr('PHP Editor');
		$tplVars['TR_PHP_EDITOR_SETTINGS'] = tr('PHP Editor Settings');
		$tplVars['TR_SETTINGS'] = tr('Settings');
		$tplVars['TR_DIRECTIVES_VALUES'] = tr('Directive values');
		$tplVars['TR_FIELDS_OK'] = tr('All fields seem to be valid.');
		$tplVars['TR_VALUE_ERROR'] = tr('Value for the PHP <strong>%%s</strong> directive must be between %%d and %%d.', true);
		$tplVars['TR_CLOSE'] = tr('Close');
		$tplVars['TR_PHP_POST_MAX_SIZE_DIRECTIVE'] = tr('PHP %s directive', true, '<b>post_max_size</b>');
		$tplVars['TR_PHP_UPLOAD_MAX_FILEZISE_DIRECTIVE'] = tr('PHP %s directive', true, '<b>upload_max_filezize</b>');
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
			$tplVars['ALLOW_URL_FOPEN_YES'] = ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'yes') ? $checked : '';
			$tplVars['ALLOW_URL_FOPEN_NO'] = ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'no') ? $checked : '';
			$permissionsBlock = true;
		}

		if (!$phpini->checkRePerm('phpiniDisplayErrors')) {
			$tplVars['PHP_EDITOR_DISPLAY_ERRORS_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_DISPLAY_ERRORS'] = tr('Can edit the PHP %s directive', true, '<b>display_errors</b>');
			$tplVars['DISPLAY_ERRORS_YES'] = ($phpini->getClPermVal('phpiniDisplayErrors') == 'yes') ? $checked : '';
			$tplVars['DISPLAY_ERRORS_NO'] = ($phpini->getClPermVal('phpiniDisplayErrors') == 'no') ? $checked : '';
			$permissionsBlock = true;
		}

		if (PHP_SAPI == 'apache2handler' || !$phpini->checkRePerm('phpiniDisableFunctions')) {
			$tplVars['PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_DISABLE_FUNCTIONS'] = tr('Can edit the PHP %s directive', true, '<b>disable_functions</b>');
			$tplVars['DISABLE_FUNCTIONS_YES'] = ($phpini->getClPermVal('phpiniDisableFunctions') == 'yes') ? $checked : '';
			$tplVars['DISABLE_FUNCTIONS_NO'] = ($phpini->getClPermVal('phpiniDisableFunctions') == 'no') ? $checked : '';
			$tplVars['TR_ONLY_EXEC'] = tr('Only exec');
			$tplVars['DISABLE_FUNCTIONS_EXEC'] = ($phpini->getClPermVal('phpiniDisableFunctions') == 'exec') ? $checked : '';
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
			$tplVars['MAX_EXECUTION_TIME'] = $phpini->getDataVal('phpiniMaxExecutionTime');
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
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @param iMSCP_PHPini $phpini
 * @return void
 */
function reseller_generatePage($tpl, $phpini)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$checked = $cfg->HTML_CHECKED;

	$tpl->assign(
		array(
			'HP_NAME_VALUE' => '',
			'HP_DESCRIPTION_VALUE' => '',

			'HP_MAX_SUB_LIMITS' => '0',
			'HP_MAX_ALS_VALUES' => '0',
			'HP_MAIL_VALUE' => '0',
			'HP_MAIL_QUOTA_VALUE' => '0',
			'HP_FTP_VALUE' => '0',
			'HP_SQL_DB_VALUE' => '0',
			'HP_SQL_USER_VALUE' => '0',
			'HP_TRAFF_VALUE' => '0',
			'HP_DISK_VALUE' => '0',

			'TR_PHP_YES' => '',
			'TR_PHP_NO' => $checked,
			'TR_CGI_YES' => '',
			'TR_CGI_NO' => $checked,
			'TR_DNS_YES' => '',
			'TR_DNS_NO' => $checked,

			'TR_EXTMAIL_YES' => '',
			'TR_EXTMAIL_NO' => $checked,

			'TR_PROTECT_WEB_FOLDERS_YES' => $checked,
			'TR_PROTECT_WEB_FOLDERS_NO' => '',

			'TR_STATUS_YES' => $checked,
			'TR_STATUS_NO' => '',

			'READONLY' => '',
		)
	);

	if(resellerHasFeature('aps')) {
		$tpl->assign(
			array(
				'TR_SOFTWARE_YES' => '',
				'TR_SOFTWARE_NO' => $checked,
			)
		);
	}

	if (resellerHasFeature('backup')) {
		$tpl->assign(
			array(
				'VL_BACKUPD' => '',
				'VL_BACKUPS' => '',
				'VL_BACKUPF' => '',
				'VL_BACKUPN' => $checked,
			)
		);
	}

	_reseller_generatePhpBlock($tpl, $phpini);
}

/**
 * Generate error page
 *
 * @param iMSCP_pTemplate $tpl
 * @param iMSCP_PHPini $phpini
 * @return void
 */
function reseller_generateErrorPage($tpl, $phpini)
{
	global $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $php, $cgi,
		   $backup, $dns, $aps, $extMail, $webFolderProtection, $status;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$checked = $cfg->HTML_CHECKED;

	$tpl->assign(
		array(
			'HP_NAME_VALUE' => tohtml($name),
			'HP_DESCRIPTION_VALUE' => tohtml($description),

			'HP_MAX_SUB_LIMITS' => tohtml($sub),
			'HP_MAX_ALS_VALUES' => tohtml($als),
			'HP_MAIL_VALUE' => tohtml($mail),
			'HP_MAIL_QUOTA_VALUE' => tohtml($mailQuota),
			'HP_FTP_VALUE' => tohtml($ftp),
			'HP_SQL_DB_VALUE' => tohtml($sqld),
			'HP_SQL_USER_VALUE' => tohtml($sqlu),
			'HP_TRAFF_VALUE' => tohtml($traffic),
			'HP_DISK_VALUE' => tohtml($diskSpace),

			'TR_PHP_YES' => ($php == '_yes_') ? $checked : '',
			'TR_PHP_NO' => ($php == '_no_') ? $cfg->HTML_CHECKED : '',
			'TR_CGI_YES' => ($cgi == '_yes_') ? $checked : '',
			'TR_CGI_NO' => ($cgi == '_no_') ? $checked : '',
			'TR_DNS_YES' => ($dns == '_yes_') ? $checked : '',
			'TR_DNS_NO' => ($dns == '_no_') ? $checked : '',
			'TR_EXTMAIL_YES' => ($extMail == '_yes_') ? $checked : '',
			'TR_EXTMAIL_NO' => ($extMail == '_no_') ? $checked : '',
			'TR_PROTECT_WEB_FOLDERS_YES' => ($webFolderProtection == '_yes_') ? $checked : '',
			'TR_PROTECT_WEB_FOLDERS_NO' => ($webFolderProtection == '_no_') ? $checked : '',

			'TR_STATUS_YES' => ($status) ? $checked : '',
			'TR_STATUS_NO' => (!$status) ? $checked : '',

			'READONLY' => ''
		)
	);

	if(resellerHasFeature('aps')) {
		$tpl->assign(
			array(
				'TR_SOFTWARE_YES' => ($aps == '_yes_') ? $checked : '',
				'TR_SOFTWARE_NO' => ($aps == '_no_') ? $checked : '',
			)
		);
	}

	if (resellerHasFeature('backup')) {
		$tpl->assign(
			array(
				'VL_BACKUPD' => ($backup == '_dmn_') ? $checked : '',
				'VL_BACKUPS' => ($backup == '_sql_') ? $checked : '',
				'VL_BACKUPF' => ($backup == '_full_') ? $checked : '',
				'VL_BACKUPN' => ($backup == '_no_') ? $checked : '',
			)
		);
	}

	_reseller_generatePhpBlock($tpl, $phpini);
}

/**
 * Check input data
 *
 * @param iMSCP_PHPini $phpini
 * @return bool TRUE if data are valid, FALSE otherwise
 */
function reseller_checkData($phpini)
{
	global $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $php, $cgi,
		   $dns, $backup, $aps, $extMail, $webFolderProtection, $status;

	$name = isset($_POST['hp_name']) ? clean_input($_POST['hp_name']) : '';
	$description = isset($_POST['hp_description']) ? clean_input($_POST['hp_description']) : '';

	$sub = isset($_POST['hp_sub']) ? clean_input($_POST['hp_sub']) : '-1';
	$als = isset($_POST['hp_als']) ? clean_input($_POST['hp_als']) : '-1';
	$mail = isset($_POST['hp_mail']) ? clean_input($_POST['hp_mail']) : '-1';
	$mailQuota = isset($_POST['hp_mail_quota']) ? clean_input($_POST['hp_mail_quota']) : '';
	$ftp = isset($_POST['hp_ftp']) ? clean_input($_POST['hp_ftp']) : '-1';
	$sqld = isset($_POST['hp_sql_db']) ? clean_input($_POST['hp_sql_db']) : '-1';
	$sqlu = isset($_POST['hp_sql_user']) ? clean_input($_POST['hp_sql_user']) : '-1';
	$traffic = isset($_POST['hp_traff']) ? clean_input($_POST['hp_traff']) : '';
	$diskSpace = isset($_POST['hp_disk']) ? clean_input($_POST['hp_disk']) : '';

	$php = isset($_POST['hp_php']) ? clean_input($_POST['hp_php']) : '_no_';
	$cgi = isset($_POST['hp_cgi']) ? clean_input($_POST['hp_cgi']) : '_no_';
	$dns = isset($_POST['hp_dns']) ? clean_input($_POST['hp_dns']) : '_no_';
	$backup = isset($_POST['hp_backup']) ? clean_input($_POST['hp_backup']) : '_no_';
	$aps = isset($_POST['hp_softwares_installer']) ? clean_input($_POST['hp_softwares_installer']) : '_no_';
	$extMail = isset($_POST['hp_external_mail']) ? clean_input($_POST['hp_external_mail']) : '_no_';

	$webFolderProtection = isset($_POST['hp_protected_webfolders'])
		? clean_input($_POST['hp_protected_webfolders']) : '_no_';

	$status = isset($_POST['hp_status']) ? clean_input($_POST['hp_status']) : '0';

	$php = ($php == '_yes_') ? '_yes_' : '_no_';
	$cgi = ($cgi == '_yes_') ? '_yes_' : '_no_';
	$dns = ($dns == '_yes_') ? '_yes_' : '_no_';
	$backup = (resellerHasFeature('backup') && in_array($backup, array('_full_', '_dmn_', '_sql_'))) ? $backup : '_no_';
	$aps = (resellerHasFeature('aps') && $aps == '_yes_') ? '_yes_' : '_no_';
	$extMail = ($extMail == '_yes_') ? '_yes_' : '_no_';
	$webFolderProtection = ($webFolderProtection == '_yes_') ? '_yes_' : '_no_';

	if ($name == '') set_page_message(tr('Name cannot be empty.'), 'error');
	if ($description == '') set_page_message(tr('Description cannot be empty.'), 'error');

	if (!resellerHasFeature('subdomains')) {
		$sub = '-1';
	} elseif (!imscp_limit_check($sub, -1)) {
		set_page_message(tr('Incorrect subdomain limit.'), 'error');
	}

	if (!resellerHasFeature('domain_aliases')) {
		$als = '-1';
	} elseif (!imscp_limit_check($als, -1)) {
		set_page_message(tr('Incorrect domain alias limit.'), 'error');
	}

	if (!resellerHasFeature('mail')) {
		$mail = '-1';
	} elseif (!imscp_limit_check($mail, -1)) {
		set_page_message(tr('Incorrect email account limit.'), 'error');
	}

	if (!resellerHasFeature('ftp')) {
		$ftp = '-1';
	} elseif (!imscp_limit_check($ftp, -1)) {
		set_page_message(tr('Incorrect FTP account limit.'), 'error');
	}

	if (!resellerHasFeature('sql_db')) {
		$sqld = '-1';
	} elseif (!imscp_limit_check($sqld, -1)) {
		set_page_message(tr('Incorrect SQL user limit.'), 'error');
	} elseif ($sqlu != -1 && $sqld == -1) {
		set_page_message(tr('SQL user limit is <i>disabled</i>.'), 'error');
	}

	if (!resellerHasFeature('sql_user')) {
		$sqlu = '-1';
	} elseif (!imscp_limit_check($sqlu, -1)) {
		set_page_message(tr('Incorrect SQL database limit.'), 'error');
	} elseif ($sqlu == -1 && $sqld != -1) {
		set_page_message(tr('SQL database limit is not <i>disabled</i>.'), 'error');
	}

	if (!imscp_limit_check($traffic, null)) {
		set_page_message(tr('Incorrect monthly traffic limit.'), 'error');
	}

	if (!imscp_limit_check($diskSpace, null)) {
		set_page_message(tr('Incorrect disk space limit.'), 'error');
	}

	// Check for mail quota
	if (isset($_POST['hp_mail_quota'])) {
		if (!imscp_limit_check($mailQuota, null)) {
			set_page_message(tr('Wrong syntax for the mail quota value.'), 'error');
		} elseif ($diskSpace != 0 && $mailQuota > $diskSpace) {
			set_page_message(tr('Email quota cannot be bigger than disk space limit.'), 'error');
		} elseif($diskSpace != 0 && $mailQuota == 0) {
			set_page_message(
				tr('Email quota cannot be unlimited. Max value is %d MiB.', $diskSpace), 'error'
			);
		}
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
			!$phpini->setDataWithPermCheck('phpiniPostMaxSize', clean_input($_POST['post_max_size']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'post_max_size'), 'error');
		}

		if (
			isset($_POST['upload_max_filesize']) &&
			!$phpini->setDataWithPermCheck('phpiniUploadMaxFileSize', clean_input($_POST['upload_max_filesize']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'upload_max_filesize'), 'error');
		}

		if (
			isset($_POST['max_execution_time']) &&
			!$phpini->setDataWithPermCheck('phpiniMaxExecutionTime', clean_input($_POST['max_execution_time']))
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

	if ($php == '_no_' && $aps == '_yes_') {
		set_page_message(tr('Software installer requires PHP support.'), 'error');
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		return true;
	} else {
		return false;
	}
}

/**
 * Add hosting plan
 *
 * @param int $resellerId Reseller unique identifier
 * @param iMSCP_PHPini $phpini
 * @return bool TRUE on success, FALSE otherwise
 */
function reseller_addHostingPlan($resellerId, $phpini)
{
	global $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $traffic, $diskSpace, $php, $cgi,
		   $dns, $backup, $aps, $extMail, $webFolderProtection, $status;

	$query = "SELECT `id` FROM `hosting_plans` WHERE `name` = ? AND `reseller_id` = ? LIMIT 1";
	$stmt = exec_query($query, array($name, $resellerId));

	if ($stmt->rowCount()) {
		set_page_message(tr('A hosting plan with same name already exists.'), 'error');
		return false;
	} else {
		$hpProps = "$php;$cgi;$sub;$als;$mail;$ftp;$sqld;$sqlu;$traffic;$diskSpace;$backup;$dns;$aps";
		$hpProps .= ';' . $phpini->getClPermVal('phpiniSystem') . ';' . $phpini->getClPermVal('phpiniAllowUrlFopen');
		$hpProps .= ';' . $phpini->getClPermVal('phpiniDisplayErrors') . ';' . $phpini->getClPermVal('phpiniDisableFunctions');
		$hpProps .= ';' . $phpini->getDataVal('phpiniPostMaxSize') . ';' . $phpini->getDataVal('phpiniUploadMaxFileSize');
		$hpProps .= ';' . $phpini->getDataVal('phpiniMaxExecutionTime') . ';' . $phpini->getDataVal('phpiniMaxInputTime');
		$hpProps .= ';' . $phpini->getDataVal('phpiniMemoryLimit') . ';' . $extMail . ';' . $webFolderProtection;
		$hpProps .= ';' . $mailQuota * 1048576;

		if (reseller_limits_check($resellerId, $hpProps)) {
			$query = "
				INSERT INTO `hosting_plans`(
					`reseller_id`, `name`, `description`, `props`, `status`
				) VALUES (?, ?, ?, ?, ?)
			";
			exec_query($query, array($resellerId, $name, $description, $hpProps, $status));

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

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'reseller') {

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'shared/partials/forms/hosting_plan_add.tpl',
			'page_message' => 'layout',
			'nb_subdomains' => 'page',
			'nb_domain_aliases' => 'page',
			'nb_mail' => 'page',
			'nb_ftp' => 'page',
			'nb_sqld' => 'page',
			'nb_sqlu' => 'page',
			'php_feature' => 'page',
			'php_editor_js' => 'page',
			'php_editor_feature' => 'page',
			'php_editor_permissions_block' => 'php_editor_feature',
			'php_editor_allow_url_fopen_block' => 'php_editor_permissions_block',
			'php_editor_display_errors_block' => 'php_editor_permissions_block',
			'php_editor_disable_functions_block' => 'php_editor_permissions_block',
			'php_editor_default_values_block' => 'php_editor_feature',
			'cgi_feature' => 'page',
			'custom_dns_records_feature' => 'page',
			'aps_feature' => 'page',
			'backup_feature' => 'page'
		)
	);

	/* @var $phpini iMSCP_PHPini */
	$phpini = iMSCP_PHPini::getInstance();
	$phpini->loadRePerm($_SESSION['user_id']);

	if (!empty($_POST)) {
		if (reseller_checkData($phpini) && reseller_addHostingPlan($_SESSION['user_id'], $phpini)) {
			set_page_message(tr('Hosting plan successfully created.'), 'success');
			redirectTo('hosting_plan.php');
		} else {
			reseller_generateErrorPage($tpl, $phpini);
		}
	} else {
		reseller_generatePage($tpl, $phpini);
	}

	generateNavigation($tpl);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Reseller / Hosting Plans / Add Hosting Plan'),
			'ISP_LOGO' => layout_getUserLogo(),

			'TR_HOSTING_PLAN' => tr('Hosting plan'),
			'TR_NAME' => tr('Name'),
			'TR_DESCRIPTON' => tr('Description'),

			'TR_HOSTING_PLAN_LIMITS' => tr('Limits'),
			'TR_MAX_SUBDOMAINS' => tr('Subdomain limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
			'TR_MAX_ALIASES' => tr('Domain alias limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
			'TR_MAX_MAILACCOUNTS' => tr('Email account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
			'TR_MAIL_QUOTA' => tr('Email quota [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
			'TR_MAX_FTP' => tr('FTP account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
			'TR_MAX_SQL' => tr('SQL database limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
			'TR_MAX_SQL_USERS' => tr('SQL user limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
			'TR_MAX_TRAFFIC' => tr('Monthly traffic limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
			'TR_DISK_LIMIT' => tr('Disk space limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',

			'TR_HOSTING_PLAN_FEATURES' => tr('Features'),
			'TR_PHP' => tr('PHP'),
			'TR_CGI' => tr('CGI'),
			'TR_DNS' => tr('Custom DNS records'),
			'TR_BACKUP' => tr('Backup'),
			'TR_BACKUP_DOMAIN' => tr('Domain'),
			'TR_BACKUP_SQL' => tr('SQL'),
			'TR_BACKUP_FULL' => tr('Full'),
			'TR_BACKUP_NO' => tr('No'),
			'TR_SOFTWARE_SUPP' => tr('Software installer'),
			'TR_EXTMAIL' => tr('External mail server'),
			'TR_WEB_FOLDER_PROTECTION' => tr('Web folder protection'),
			'TR_WEB_FOLDER_PROTECTION_HELP' => tr("If set to 'yes', Web folders as provisioned by i-MSCP will be protected against deletion using the immutable flag (only if supported by the file system)."),

			'TR_HP_AVAILABILITY' => tr('Hosting plan availability'),
			'TR_STATUS' => tr('Available'),

			'TR_YES' => tr('yes'),
			'TR_NO' => tr('no'),
			'TR_ADD' => tr('Add'),
		)
	);

	generatePageMessage($tpl);

	if (!resellerHasFeature('subdomains')) $tpl->assign('NB_SUBDOMAIN', '');
	if (!resellerHasFeature('domain_aliases')) $tpl->assign('NB_DOMAIN_ALIASES', '');
	if (!resellerHasFeature('mail')) $tpl->assign('NB_MAIL', '');
	if (!resellerHasFeature('ftp')) $tpl->assign('NB_FTP', '');
	if (!resellerHasFeature('sql_db')) $tpl->assign('NB_SQLD', '');
	if (!resellerHasFeature('sql_user')) $tpl->assign('NB_SQLU', '');
	if (!resellerHasFeature('php')) $tpl->assign('PHP_FEATURE', '');
	if (!resellerHasFeature('php_editor')) $tpl->assign('PHP_EDITOR_FEATURE', '');
	if (!resellerHasFeature('cgi')) $tpl->assign('CGI_FEATURE', '');
	if (!resellerHasFeature('custom_dns_records')) $tpl->assign('CUSTOM_DNS_RECORDS_FEATURE', '');
	if (!resellerHasFeature('aps')) $tpl->assign('APS_FEATURE', '');
	if (!resellerHasFeature('external_mail')) $tpl->assign('EXT_MAIL_FEATURE', '');
	if (!resellerHasFeature('backup')) $tpl->assign('BACKUP_FEATURE', '');

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();
} else {
	showBadRequestErrorPage();
}
