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
 * Get parameters from previous page.
 *
 * @return bool TRUE if parameters from previous page are found, FALSE otherwise
 */
function get_pageone_param()
{
	global $dmnName, $dmnExpire, $hpId;

	if (isset($_SESSION['dmn_name'])) {
		$dmnName = $_SESSION['dmn_name'];
		$dmnExpire = $_SESSION['dmn_expire'];
		$hpId = $_SESSION['dmn_tpl'];
	} else {
		return false;
	}

	return true;
}

/**
 * Show page with initial data fields.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param iMSCP_PHPini $phpini
 * @return void
 */
function get_init_au2_page($tpl, $phpini)
{
	global $hpName, $php, $cgi, $sub, $als, $mail, $mailQuota, $ftp, $sqlDb, $sqlUser, $traffic, $diskSpace, $backup,
		   $dns, $aps, $extMailServer, $webFolderProtection;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlChecked = $cfg->HTML_CHECKED;

	$tplVars = array();

	$tplVars['VL_TEMPLATE_NAME'] = tohtml($hpName);
	$tplVars['MAX_DMN_CNT'] = '';
	$tplVars['MAX_SUBDMN_CNT'] = tohtml($sub);
	$tplVars['MAX_DMN_ALIAS_CNT'] = tohtml($als);
	$tplVars['MAX_MAIL_CNT'] = tohtml($mail);
	$tplVars['MAIL_QUOTA'] = tohtml($mailQuota);
	$tplVars['MAX_FTP_CNT'] = tohtml($ftp);
	$tplVars['MAX_SQL_CNT'] = tohtml($sqlDb);
	$tplVars['VL_MAX_SQL_USERS'] = tohtml($sqlUser);
	$tplVars['VL_MAX_TRAFFIC'] = tohtml($traffic);
	$tplVars['VL_MAX_DISK_USAGE'] = tohtml($diskSpace);
	$tplVars['VL_EXTMAILY'] = ($extMailServer == '_yes_') ? $htmlChecked : '';
	$tplVars['VL_EXTMAILN'] = ($extMailServer == '_no_') ? $htmlChecked : '';
	$tplVars['VL_PHPY'] = ($php == '_yes_') ? $htmlChecked : '';
	$tplVars['VL_PHPN'] = ($php == '_no_') ? $htmlChecked : '';
	$tplVars['VL_CGIY'] = ($cgi == '_yes_') ? $htmlChecked : '';
	$tplVars['VL_CGIN'] = ($cgi == '_no_') ? $htmlChecked : '';

	if(resellerHasFeature('custom_dns_records')) {
		$tplVars['VL_DNSY'] = ($dns == '_yes_') ? $htmlChecked : '';
		$tplVars['VL_DNSN'] = ($dns == '_no_') ? $htmlChecked : '';
	}


	if(resellerHasFeature('aps')) {
		$tplVars['VL_SOFTWAREY'] = ($aps == '_yes_') ? $htmlChecked : '';
		$tplVars['VL_SOFTWAREN'] = ($aps == '_no_') ? $htmlChecked : '';
	}

	if(resellerHasFeature('backup')) {
		$tplVars['VL_BACKUPD'] = ($backup == '_dmn_') ? $htmlChecked : '';
		$tplVars['VL_BACKUPS'] = ($backup == '_sql_') ? $htmlChecked : '';
		$tplVars['VL_BACKUPF'] = ($backup == '_full_') ? $htmlChecked : '';
		$tplVars['VL_BACKUPN'] = ($backup == '_no_') ? $htmlChecked : '';
	}

	$tplVars['VL_WEB_FOLDER_PROTECTION_YES'] = ($webFolderProtection == '_yes_') ? $htmlChecked : '';
	$tplVars['VL_WEB_FOLDER_PROTECTION_NO'] = ($webFolderProtection == '_no_') ? $htmlChecked : '';

	if ($phpini->getRePermVal('phpiniSystem') == 'yes') {
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
		$tplVars['PHP_UPLOAD_MAX_FILEZISE_DIRECTIVE'] = tr('PHP %s directive', true, '<b>upload_max_filezize</b>');
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

		if ($cfg['HTTPD_SERVER'] == 'apache_itk' || !$phpini->checkRePerm('phpiniDisableFunctions')) {
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
			$tplVars['TR_ONLY_EXEC'] = tr("Only exec");
		}

		// check only to dont break old plans without ini values
		$tplVars['POST_MAX_SIZE'] = tohtml(($phpini->getDataVal('phpiniPostMaxSize') != 'no')
			? $phpini->getReDefaultPermVal('phpiniPostMaxSize') : $phpini->getDataDefaultVal('phpiniPostMaxSize'));
		$tplVars['UPLOAD_MAX_FILESIZE'] = tohtml(($phpini->getDataVal('phpiniUploadMaxFileSize') != 'no')
			? $phpini->getReDefaultPermVal('phpiniUploadMaxFileSize') : $phpini->getDataDefaultVal('phpiniUploadMaxFileSize'));
		$tplVars['MAX_EXECUTION_TIME'] = tohtml(($phpini->getDataVal('phpiniMaxExecutionTime') != 'no')
			? $phpini->getReDefaultPermVal('phpiniMaxExecutionTime') : $phpini->getDataDefaultVal('phpiniMaxExecutionTime'));
		$tplVars['MAX_INPUT_TIME'] = tohtml(($phpini->getDataVal('phpiniMaxInputTime') != 'no')
			? $phpini->getReDefaultPermVal('phpiniMaxInputTime') : $phpini->getDataDefaultVal('phpiniMaxInputTime'));
		$tplVars['MEMORY_LIMIT'] = tohtml(($phpini->getDataVal('phpiniMemoryLimit') != 'no')
			? $phpini->getReDefaultPermVal('phpiniMemoryLimit') : $phpini->getDataDefaultVal('phpiniMemoryLimit'));

		$tplVars['PHP_DIRECTIVES_RESELLER_MAX_VALUES'] = json_encode(
			array(
				'post_max_size' => $phpini->getRePermVal('phpiniPostMaxSize'),
				'upload_max_filezize' => $phpini->getRePermVal('phpiniUploadMaxFileSize'),
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
 * Get hosting plan data.
 *
 * @param int $hpid Hosting plan unique identifier
 * @param int $resellerId Reseller unique identifier
 * @param iMSCP_PHPini $phpini
 * @return void
 */
function reseller_getHostingPlanData($hpid, $resellerId, $phpini)
{
	global $hpName, $php, $cgi, $sub, $als, $mail, $mailQuota, $ftp, $sqlDb, $sqlUser, $traffic, $diskSpace, $backup,
		   $dns, $aps, $extMailServer, $webFolderProtection;

	if($hpid != 0) {
		$query = 'SELECT `name`, `props` FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?';
		$stmt = exec_query($query, array($resellerId, $hpid));

		if ($stmt->rowCount()) {
			$data = $stmt->fetchRow();
			$props = $data['props'];

			list(
				$php, $cgi, $sub, $als, $mail, $ftp, $sqlDb, $sqlUser, $traffic, $diskSpace, $backup, $dns, $aps,
				$phpEditor, $phpiniAllowUrlFopen, $phpiniDisplayErrors, $phpiniDisableFunctions, $phpiniPostMaxSize,
				$phpiniUploadMaxFileSize, $phpiniMaxExecutionTime, $phpiniMaxInputTime, $phpiniMemoryLimit,
				$extMailServer, $webFolderProtection, $mailQuota
			) = explode(';', $props);

			$mailQuota = ($mailQuota != '0') ? $mailQuota / 1048576 : '0';

			$hpName = $data['name'];

			// Write into phpini object
			$phpini->setClPerm('phpiniSystem', $phpEditor);
			$phpini->setClPerm('phpiniAllowUrlFopen', $phpiniAllowUrlFopen);
			$phpini->setClPerm('phpiniDisplayErrors', $phpiniDisplayErrors);
			$phpini->setClPerm('phpiniDisableFunctions', $phpiniDisableFunctions);

			// Use phpini->phpiniData as datastore for the following values -
			// Should be better in something like property class/object later
			$phpini->setData('phpiniPostMaxSize', $phpiniPostMaxSize);
			$phpini->setData('phpiniUploadMaxFileSize', $phpiniUploadMaxFileSize);
			$phpini->setData('phpiniMaxExecutionTime', $phpiniMaxExecutionTime);
			$phpini->setData('phpiniMaxInputTime', $phpiniMaxInputTime);
			$phpini->setData('phpiniMemoryLimit', $phpiniMemoryLimit);
		} else {
			showBadRequestErrorPage();
		}
	} else {
		$hpName = 'Custom';
		$sub = $als = $mail = $mailQuota = $ftp = $sqlDb = $sqlUser = $traffic = $diskSpace = '0';
		$php = $cgi = $backup = $dns = $aps = $extMailServer = '_no_';
		$webFolderProtection = '_yes_';
	}
}

/**
 * Check validity of input data.
 *
 * @param iMSCP_PHPini $phpini
 * @return bool TRUE if all data are valid, FALSE otherwise
 */
function check_user_data($phpini)
{
	global $php, $cgi, $sub, $als, $mail, $mailQuota, $ftp, $sqlDb, $sqlUser, $traffic, $diskSpace, $backup,
		$dns, $aps, $extMailServer, $webFolderProtection;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// Subdomains limit

	if (isset($_POST['nreseller_max_subdomain_cnt'])) {
		$sub = clean_input($_POST['nreseller_max_subdomain_cnt']);
	}

	if (!resellerHasFeature('subdomains')) {
		$sub = '-1';
	} elseif (!imscp_limit_check($sub, -1)) {
		set_page_message(tr('Incorrect subdomain limit.'), 'error');
	}

	// Domain aliases limit

	if (isset($_POST['nreseller_max_alias_cnt'])) {
		$als = clean_input($_POST['nreseller_max_alias_cnt']);
	}

	if (!resellerHasFeature('domain_aliases')) {
		$als = '-1';
	} elseif (!imscp_limit_check($als, -1)) {
		set_page_message(tr('Incorrect alias limit.'), 'error');
	}

	// Mail accounts limit

	if (isset($_POST['nreseller_max_mail_cnt'])) {
		$mail = clean_input($_POST['nreseller_max_mail_cnt']);
	}

	if (!resellerHasFeature('mail')) {
		$mail = '-1';
	} elseif (!imscp_limit_check($mail, -1)) {
		set_page_message(tr('Incorrect email account limit.'), 'error');
	}

	// Ftp accounts limit

	if (isset($_POST['nreseller_max_ftp_cnt']) || $ftp == -1) {
		$ftp = clean_input($_POST['nreseller_max_ftp_cnt']);
	}

	if (!resellerHasFeature('ftp')) {
		$ftp = '-1';
	} elseif (!imscp_limit_check($ftp, -1)) {
		set_page_message(tr('Incorrect FTP account limit.'), 'error');
	}

	// SQL database limit

	if (isset($_POST['nreseller_max_sql_db_cnt'])) {
		$sqlDb = clean_input($_POST['nreseller_max_sql_db_cnt']);
	}

	if (!resellerHasFeature('sql_db')) {
		$sqlDb = -1;
	} elseif (!imscp_limit_check($sqlDb, -1)) {
		set_page_message(tr('Incorrect SQL database limit.'), 'error');
	} elseif ($sqlDb != -1 && $sqlUser == -1) {
		set_page_message(tr('SQL user limit is disabled.'), 'error');
	}

	// SQL users limit

	if (isset($_POST['nreseller_max_sql_user_cnt'])) {
		$sqlUser = clean_input($_POST['nreseller_max_sql_user_cnt']);
	}

	if (!resellerHasFeature('sql_user')) {
		$sqlUser = -1;
	} elseif (!imscp_limit_check($sqlUser, -1)) {
		set_page_message(tr('Incorrect SQL user limit.'), 'error');
	} elseif ($sqlUser != -1 && $sqlDb == -1) {
		set_page_message(tr("SQL database limit is disabled."), 'error');
	}

	// Monthly traffic limit

	if (isset($_POST['nreseller_max_traffic'])) {
		$traffic = clean_input($_POST['nreseller_max_traffic']);
	}

	if (!imscp_limit_check($traffic, null)) {
		set_page_message(tr('Incorrect monthly traffic limit.'), 'error');
	}

	// Disk space limit

	if (isset($_POST['nreseller_max_disk'])) {
		$diskSpace = clean_input($_POST['nreseller_max_disk']);
	}

	if (!imscp_limit_check($diskSpace, null)) {
		set_page_message(tr('Incorrect disk space limit.'), 'error');
	}

	if (isset($_POST['nreseller_mail_quota'])) {
		$mailQuota = clean_input($_POST['nreseller_mail_quota']);

		if(!imscp_limit_check($mailQuota, null)) {
			set_page_message(tr('Incorrect Email quota'), 'error');
		} elseif($diskSpace != '0' && $mailQuota > $diskSpace) {
			set_page_message(tr('Email quota cannot be bigger than disk space limit.'), 'error');
		} elseif($diskSpace != '0' && $mailQuota == '0') {
			set_page_message(tr('Email quota cannot be unlimited. Max value is %d MiB.', $diskSpace), 'error');
		}
	}

	// PHP feature

	if (isset($_POST['php'])) {
		$php = $_POST['php'];
	}

	// PHP Editor feature

	if ($phpini->checkRePerm('phpiniSystem') && isset($_POST['phpiniSystem'])) {
		$phpini->setClPerm('phpiniSystem', clean_input($_POST['phpiniSystem']));

		if ($phpini->checkRePerm('phpiniAllowUrlFopen') && isset($_POST['phpini_perm_allow_url_fopen'])) {
			$phpini->setClPerm('phpiniAllowUrlFopen', clean_input($_POST['phpini_perm_allow_url_fopen']));
		}

		if ($phpini->checkRePerm('phpiniDisplayErrors') && isset($_POST['phpini_perm_display_errors'])) {
			$phpini->setClPerm('phpiniDisplayErrors', clean_input($_POST['phpini_perm_display_errors']));
		}

		if ($cfg['HTTPD_SERVER'] != 'apache_itk' && $phpini->checkRePerm('phpiniDisableFunctions') &&
			isset($_POST['phpini_perm_disable_functions'])
		) {
			$phpini->setClPerm('phpiniDisableFunctions', clean_input($_POST['phpini_perm_disable_functions']));
		}

		if (isset($_POST['post_max_size']) && (!$phpini->setDataWithPermCheck('phpiniPostMaxSize', $_POST['post_max_size']))) {
			$phpini->setData('phpiniPostMaxSize', $_POST['post_max_size'], false);
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'post_max_size'), 'error');
		}

		if (isset($_POST['upload_max_filezize']) && (!$phpini->setDataWithPermCheck('phpiniUploadMaxFileSize', $_POST['upload_max_filezize']))) {
			$phpini->setData('phpiniUploadMaxFileSize', $_POST['upload_max_filezize'], false);
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'upload_max_filesize'), 'error');
		}

		if (isset($_POST['max_execution_time']) && (!$phpini->setDataWithPermCheck('phpiniMaxExecutionTime', $_POST['max_execution_time']))) {
			$phpini->setData('phpiniMaxExecutionTime', $_POST['max_execution_time'], false);
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'max_execution_time'), 'error');
		}

		if (isset($_POST['max_input_time']) && (!$phpini->setDataWithPermCheck('phpiniMaxInputTime', $_POST['max_input_time']))) {
			$phpini->setData('phpiniMaxInputTime', $_POST['max_input_time'], false);
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'max_input_time'), 'error');
		}

		if (isset($_POST['memory_limit']) && (!$phpini->setDataWithPermCheck('phpiniMemoryLimit', $_POST['memory_limit']))) {
			$phpini->setData('phpiniMemoryLimit', $_POST['memory_limit'], false);
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'memory_limit'), 'error');
		}
	}

	// CGI feature

	if (isset($_POST['cgi'])) {
		$cgi = $_POST['cgi'];
	} else {
		$cgi = '_no_';
	}

	// Custom DNS records feature

	if(resellerHasFeature('custom_dns_records')) {
		if (isset($_POST['dns'])) {
			$dns = $_POST['dns'];
		} else {
			$dns = '_no_';
		}
	} else {
		$dns = '_no_';
	}

	// External mail server feature

	if (resellerHasFeature('external_mail') && isset($_POST['external_mail'])) {
		$extMailServer = clean_input($_POST['external_mail']);
	} else {
		$extMailServer = '_no_';
	}

	// Backup feature

	if (isset($_POST['backup']) && resellerHasFeature('backup')) {
		$backup = $_POST['backup'];
	} else {
		$backup = '_no_';
	}

	// APS feature

	if (isset($_POST['software_allowed']) && resellerHasFeature('aps')) {
		$aps = $_POST['software_allowed'];
	} else {
		$aps = '_no_';
	}

	if ($php == '_no_' && $aps == '_yes_') {
		set_page_message(tr('The software installer feature requires PHP.'), 'error');
	}

	// Web folders protection

	if(isset($_POST['web_folder_protection'])) {
		$webFolderProtection = $_POST['web_folder_protection'];
	} else {
		$webFolderProtection = '_yes_';
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		return true;
	} else {
		return false;
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
	redirectTo('users.php');
}

$phpini = iMSCP_PHPini::getInstance();
$phpini->loadRePerm($_SESSION['user_id']);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/user_add2.tpl',
		'page_message' => 'layout',
		'subdomain_feature' => 'page',
		'alias_feature' => 'page',
		'mail_feature' => 'page',
		'custom_dns_records_feature' => 'page',
		'ext_mail_feature' => 'page',
		'ftp_feature' => 'page',
		'sql_feature' => 'page',
		'aps_feature' => 'page',
		'backup_feature' => 'page',
		'php_editor_js' => 'page',
		'php_editor_block' => 'page',
		'php_editor_permissions_block' => 'php_editor_block',
		'php_editor_allow_url_fopen_block' => 'php_editor_permissions_block',
		'php_editor_display_errors_block' => 'php_editor_permissions_block',
		'php_editor_disable_functions_block' => 'php_editor_permissions_block',
		'php_editor_default_values_block' => 'php_editor_block'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Reseller / Customers / Add Customer - Next Step'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_ADD_USER' => tr('Add user'),
		'TR_HOSTING_PLAN' => tr('Hosting plan'),
		'TR_NAME' => tr('Name'),
		'TR_MAX_DOMAIN' => tr('Domain limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'TR_MAX_SUBDOMAIN' => tr('Subdomain limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'TR_MAX_DOMAIN_ALIAS' => tr('Domain alias limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'TR_MAX_MAIL_COUNT' => tr('Email account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'TR_MAIL_QUOTA' => tr('Email quota [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
		'TR_MAX_FTP' => tr('FTP account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'TR_MAX_SQL_DB' => tr('SQL database limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'TR_MAX_SQL_USERS' => tr('SQL user limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
		'TR_MAX_TRAFFIC' => tr('Monthly traffic limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
		'TR_MAX_DISK_USAGE' => tr('Disk space limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
		'TR_EXTMAIL' => tr('External mail server'),
		'TR_PHP' => tr('PHP'),
		'TR_CGI' => tr('CGI'),
		'TR_BACKUP' => tr('Backup'),
		'TR_BACKUP_DOMAIN' => tr('Domain'),
		'TR_BACKUP_SQL' => tr('SQL'),
		'TR_BACKUP_FULL' => tr('Full'),
		'TR_BACKUP_NO' => tr('No'),
		'TR_DNS' => tr('Custom DNS records'),
		'TR_YES' => tr('yes'),
		'TR_NO' => tr('no'),
		'TR_NEXT_STEP' => tr('Next step'),
		'TR_FEATURES' => tr('Features'),
		'TR_LIMITS' => tr('Limits'),
		'TR_WEB_FOLDER_PROTECTION' => tr('Web folder protection'),
		'TR_WEB_FOLDER_PROTECTION_HELP' => tr("If set to 'yes', Web folders as provisioned by i-MSCP will be protected against deletion using the immutable flag (only if supported by the file system)."),
		'TR_SOFTWARE_SUPP' => tr('Software installer')
	)
);

generateNavigation($tpl);

global $dmnName, $dmnExpire, $php, $cgi, $sub, $als, $mail, $mailQuota, $ftp, $sqlDb, $sqlUser, $traffic, $diskSpace,
	   $backup, $dns, $aps, $extMailServer, $webFolderProtection;

if (!get_pageone_param()) {
	set_page_message(tr('Domain data were been altered. Please try again.'), 'error');
	unsetMessages();
	redirectTo('user_add1.php');
}

if (isset($_POST['uaction']) && ('user_add2_nxt' == $_POST['uaction']) && (!isset($_SESSION['step_one']))) {
	if (check_user_data($phpini)) {
		$_SESSION['step_two_data'] = "$dmnName;0";
		$_SESSION['ch_hpprops'] =
			"$php;$cgi;$sub;$als;$mail;$ftp;$sqlDb;$sqlUser;$traffic;$diskSpace;$backup;$dns;$aps;" .
			$phpini->getClPermVal('phpiniSystem') . ';' .
			$phpini->getClPermVal('phpiniAllowUrlFopen') . ';' .
			$phpini->getClPermVal('phpiniDisplayErrors') . ';' .
			$phpini->getClPermVal('phpiniDisableFunctions') . ';' .
			$phpini->getDataVal('phpiniPostMaxSize') . ";" .
			$phpini->getDataVal('phpiniUploadMaxFileSize') . ';' .
			$phpini->getDataVal('phpiniMaxExecutionTime') . ';' .
			$phpini->getDataVal('phpiniMaxInputTime') . ';' .
			$phpini->getDataVal('phpiniMemoryLimit') . ';' .
			$extMailServer . ';' . $webFolderProtection . ';' . $mailQuota * 1048576;

		if (reseller_limits_check($_SESSION['user_id'], $_SESSION['ch_hpprops'])) {
			redirectTo('user_add3.php');
		}
	}
} else {
	unset($_SESSION['step_one']);
	global $hpId;
	reseller_getHostingPlanData($hpId, $_SESSION['user_id'], $phpini);
}

get_init_au2_page($tpl, $phpini);

if (!resellerHasFeature('subdomains')) {
	$tpl->assign('SUBDOMAIN_FEATURE', '');
}

if (!resellerHasFeature('domain_aliases')) {
	$tpl->assign('ALIAS_FEATURE', '');
}

if (!resellerHasFeature('custom_dns_records')) {
	$tpl->assign('CUSTOM_DNS_RECORDS_FEATURE', '');
}

if (!resellerHasFeature('mail')) {
	$tpl->assign('MAIL_FEATURE', '');
	$tpl->assign('EXT_MAIL_FEATURE', '');
}

if (!resellerHasFeature('ftp')) {
	$tpl->assign('FTP_FEATURE', '');
}

if (!resellerHasFeature('sql')) {
	$tpl->assign('SQL_FEATURE', '');
}

if (!resellerHasFeature('aps')) {
	$tpl->assign('APS_FEATURE', '');
}

if (!resellerHasFeature('backup')) {
	$tpl->assign('BACKUP_FEATURE', '');
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
