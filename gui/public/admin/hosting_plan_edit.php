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
function _admin_generatePhpBlock($tpl, $phpini)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$checked = $cfg->HTML_CHECKED;

	$tplVars = array();

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
	$tplVars['TR_CAN_EDIT_ALLOW_URL_FOPEN'] = tr('Can edit the PHP %s directive', true, '<b>allow_url_fopen</b>');
	$tplVars['ALLOW_URL_FOPEN_YES'] = ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'yes') ? $checked : '';
	$tplVars['ALLOW_URL_FOPEN_NO'] = ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'no') ? $checked : '';
	$tplVars['TR_CAN_EDIT_DISPLAY_ERRORS'] = tr('Can edit the PHP %s directive', true, '<b>display_errors</b>');
	$tplVars['DISPLAY_ERRORS_YES'] = ($phpini->getClPermVal('phpiniDisplayErrors') == 'yes') ? $checked : '';
	$tplVars['DISPLAY_ERRORS_NO'] = ($phpini->getClPermVal('phpiniDisplayErrors') == 'no') ? $checked : '';

	if ($cfg['HTTPD_SERVER'] == 'apache_itk') {
		$tplVars['PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK'] = '';
	} else {
		$tplVars['TR_CAN_EDIT_DISABLE_FUNCTIONS'] = tr('Can edit the PHP %s directive', true, '<b>disable_functions</b>');
		$tplVars['DISABLE_FUNCTIONS_YES'] = ($phpini->getClPermVal('phpiniDisableFunctions') == 'yes') ? $checked : '';
		$tplVars['DISABLE_FUNCTIONS_NO'] = ($phpini->getClPermVal('phpiniDisableFunctions') == 'no') ? $checked : '';
		$tplVars['TR_ONLY_EXEC'] = tr('Only exec');
		$tplVars['DISABLE_FUNCTIONS_EXEC'] = ($phpini->getClPermVal('phpiniDisableFunctions') == 'exec') ? $checked : '';
	}

	$tplVars['TR_PERMISSIONS'] = tr('Permissions');
	$tplVars['TR_ONLY_EXEC'] = tr('Only exec');
	$tplVars['POST_MAX_SIZE'] = $phpini->getDataVal('phpiniPostMaxSize');
	$tplVars['UPLOAD_MAX_FILESIZE'] = $phpini->getDataVal('phpiniUploadMaxFileSize');
	$tplVars['MAX_EXECUTION_TIME'] = $phpini->getDataVal('phpiniMaxExecutionTime');
	$tplVars['MAX_INPUT_TIME'] = $phpini->getDataVal('phpiniMaxInputTime');
	$tplVars['MEMORY_LIMIT'] = $phpini->getDataVal('phpiniMemoryLimit');

	$tplVars['PHP_DIRECTIVES_MAX_VALUES'] = json_encode(
		array(
			'post_max_size' => 10000,
			'upload_max_filesize' => 10000,
			'max_execution_time' => 10000,
			'max_input_time' => 10000,
			'memory_limit' => 10000
		)
	);

	$tpl->assign($tplVars);
}

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @param int $id Hosting plan unique identifier
 * @param $phpini iMSCP_PHPini
 * @return void
 */
function admin_generatePage($tpl, $id, $phpini)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			*
		FROM
			`hosting_plans`
		WHERE
			`id` = ?
		AND
			`reseller_id` IN (SELECT `admin_id` FROM `admin` WHERE `admin_type` = 'admin')
	";
	$stmt = exec_query($query, $id);

	if (!$stmt->rowCount()) {
		showBadRequestErrorPage();
	}

	$data = $stmt->fetchRow();

	$description = $data['description'];
	$status = $data['status'];


	list(
		$php, $cgi, $sub, $als, $mail, $ftp, $sqld, $sqlu, $monthlyTraffic, $diskspace, $backup, $dns, $aps, $phpEditor,
		$phpAllowUrlFopenPerm, $phpDisplayErrorsPerm, $phpDisableFunctionsPerm, $phpPostMaxSizeValue,
		$phpUploadMaxFilesizeValue, $phpMaxExecutionTimeValue, $phpMaxInputTimeValue, $phpMemoryLimitValue,
		$hpExtMail, $hpProtectedWebFolders, $mailQuota
		) = explode(';', $data['props']);

	$mailQuota = $mailQuota / 1048576;

	$phpini->setClPerm('phpiniSystem', $phpEditor);
	$phpini->setClPerm('phpiniAllowUrlFopen', $phpAllowUrlFopenPerm);
	$phpini->setClPerm('phpiniDisplayErrors', $phpDisplayErrorsPerm);
	$phpini->setClPerm('phpiniDisableFunctions', $phpDisableFunctionsPerm);

	$phpini->setData('phpiniPostMaxSize', $phpPostMaxSizeValue, false);
	$phpini->setData('phpiniUploadMaxFileSize', $phpUploadMaxFilesizeValue, false);
	$phpini->setData('phpiniMaxExecutionTime', $phpMaxExecutionTimeValue, false);
	$phpini->setData('phpiniMaxInputTime', $phpMaxInputTimeValue, false);
	$phpini->setData('phpiniMemoryLimit', $phpMemoryLimitValue, false);

	$checked = $cfg->HTML_CHECKED;

	$tpl->assign(
		array(
			'ID' => tohtml($id),
			'NAME' => tohtml($data['name']),
			'DESCRIPTION' => tohtml($description),
			'MAX_SUB' => tohtml($sub),
			'MAX_ALS' => tohtml($als),
			'MAX_MAIL' => tohtml($mail),
			'MAIL_QUOTA' => tohtml($mailQuota),
			'MAX_FTP' => tohtml($ftp),
			'MAX_SQLD' => tohtml($sqld),
			'MAX_SQLU' => tohtml($sqlu),
			'MONTHLY_TRAFFIC' => tohtml($monthlyTraffic),
			'MAX_DISKSPACE' => tohtml($diskspace),
			'PHP_YES' => ($php == '_yes_') ? $checked : '',
			'PHP_NO' => ($php == '_no_') ? $checked : '',
			'CGI_YES' => ($cgi == '_yes_') ? $checked : '',
			'CGI_NO' => ($cgi == '_no_') ? $checked : '',
			'DNS_YES' => ($dns == '_yes_') ? $checked : '',
			'DNS_NO' => ($dns == '_no_') ? $checked : '',
			'SOFTWARE_YES' => ($aps == '_yes_') ? $checked : '',
			'SOFTWARE_NO' => ($aps == '_no_') ? $checked : '',
			'EXTMAIL_YES' => ($hpExtMail == '_yes_') ? $checked : '',
			'EXTMAIL_NO' => ($hpExtMail == '_no_') ? $checked : '',
			'PROTECT_WEB_FOLDERS_YES' => ($hpProtectedWebFolders == '_yes_') ? $checked : '',
			'PROTECT_WEB_FOLDERS_NO' => ($hpProtectedWebFolders == '_no_') ? $checked : '',
			'STATUS_YES' => ($status) ? $checked : '',
			'STATUS_NO' => (!$status) ? $checked : ''
		)
	);

	if($cfg['NAMED_SERVER'] == 'external_server') {
		$tpl->assign('CUSTOM_DNS_RECORDS_FEATURE', '');
	}

	if ($cfg->BACKUP_DOMAINS != 'no') {
		$tpl->assign(
			array(
				'BACKUPD' => ($backup == '_dmn_') ? $checked : '',
				'BACKUPS' => ($backup == '_sql_') ? $checked : '',
				'BACKUPF' => ($backup == '_full_') ? $checked : '',
				'BACKUPN' => ($backup == '_no_') ? $checked : ''
			)
		);
	} else {
		$tpl->assign('BACKUP_FEATURE', '');
	}

	_admin_generatePhpBlock($tpl, $phpini);
}

/**
 * Generate error page
 *
 * @param iMSCP_pTemplate $tpl
 * @param iMSCP_PHPini $phpini
 * @return void
 */
function admin_generateErrorPage($tpl, $phpini)
{
	global $id, $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $monthlyTraffic, $diskspace,
		   $php, $cgi, $backup, $dns, $aps, $hpExtMail, $hpProtectedWebFolders, $status;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$checked = $cfg->HTML_CHECKED;

	$tpl->assign(
		array(
			'ID' => tohtml($id),
			'NAME' => tohtml($name),
			'DESCRIPTION' => tohtml($description),
			'MAX_SUB' => tohtml($sub),
			'MAX_ALS' => tohtml($als),
			'MAX_MAIL' => tohtml($mail),
			'MAIL_QUOTA' => tohtml($mailQuota),
			'MAX_FTP' => tohtml($ftp),
			'MAX_SQLD' => tohtml($sqld),
			'MAX_SQLU' => tohtml($sqlu),
			'MONTHLY_TRAFFIC' => tohtml($monthlyTraffic),
			'MAX_DISKSPACE' => tohtml($diskspace),
			'PHP_YES' => ($php == '_yes_') ? $checked : '',
			'PHP_NO' => ($php == '_no_') ? $cfg->HTML_CHECKED : '',
			'CGI_YES' => ($cgi == '_yes_') ? $checked : '',
			'CGI_NO' => ($cgi == '_no_') ? $checked : '',
			'DNS_YES' => ($dns == '_yes_') ? $checked : '',
			'DNS_NO' => ($dns == '_no_') ? $checked : '',
			'SOFTWARE_YES' => ($aps == '_yes_') ? $checked : '',
			'SOFTWARE_NO' => ($aps == '_no_') ? $checked : '',
			'EXTMAIL_YES' => ($hpExtMail == '_yes_') ? $checked : '',
			'EXTMAIL_NO' => ($hpExtMail == '_no_') ? $checked : '',
			'PROTECT_WEB_FOLDERS_YES' => ($hpProtectedWebFolders == '_yes_') ? $checked : '',
			'PROTECT_WEB_FOLDERS_NO' => ($hpProtectedWebFolders == '_no_') ? $checked : '',
			'STATUS_YES' => ($status) ? $checked : '',
			'STATUS_NO' => (!$status) ? $checked : ''
		)
	);

	if($cfg['NAMED_SERVER'] == 'external_server') {
		$tpl->assign('CUSTOM_DNS_RECORDS_FEATURE', '');
	}

	if ($cfg->BACKUP_DOMAINS != 'no') {
		$tpl->assign(
			array(
				'BACKUPD' => ($backup == '_dmn_') ? $checked : '',
				'BACKUPS' => ($backup == '_sql_') ? $checked : '',
				'BACKUPF' => ($backup == '_full_') ? $checked : '',
				'BACKUPN' => ($backup == '_no_') ? $checked : '',
			)
		);
	} else {
		$tpl->assign('BACKUP_FEATURE', '');
	}

	_admin_generatePhpBlock($tpl, $phpini);
}

/**
 * Check input data
 *
 * @param iMSCP_PHPini $phpini
 * @return bool TRUE if data are valid, FALSE otherwise
 */
function admin_checkData($phpini)
{
	global $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $monthlyTraffic, $diskspace, $php,
		   $cgi, $dns, $backup, $aps, $hpExtMail, $hpProtectedWebFolders, $status;

	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	$name = isset($_POST['hp_name']) ? clean_input($_POST['hp_name']) : '';
	$description = isset($_POST['hp_description']) ? clean_input($_POST['hp_description']) : '';

	$sub = isset($_POST['hp_sub']) ? clean_input($_POST['hp_sub']) : '-1';
	$als = isset($_POST['hp_als']) ? clean_input($_POST['hp_als']) : '-1';
	$mail = isset($_POST['hp_mail']) ? clean_input($_POST['hp_mail']) : '-1';
	$mailQuota = isset($_POST['hp_mail_quota']) ? clean_input($_POST['hp_mail_quota']) : '';
	$ftp = isset($_POST['hp_ftp']) ? clean_input($_POST['hp_ftp']) : '-1';
	$sqld = isset($_POST['hp_sql_db']) ? clean_input($_POST['hp_sql_db']) : '-1';
	$sqlu = isset($_POST['hp_sql_user']) ? clean_input($_POST['hp_sql_user']) : '-1';
	$monthlyTraffic = isset($_POST['hp_traff']) ? clean_input($_POST['hp_traff']) : '';
	$diskspace = isset($_POST['hp_disk']) ? clean_input($_POST['hp_disk']) : '';

	$php = isset($_POST['hp_php']) ? clean_input($_POST['hp_php']) : '_no_';
	$cgi = isset($_POST['hp_cgi']) ? clean_input($_POST['hp_cgi']) : '_no_';
	$dns = isset($_POST['hp_dns']) ? clean_input($_POST['hp_dns']) : '_no_';
	$backup = isset($_POST['hp_backup']) ? clean_input($_POST['hp_backup']) : '_no_';
	$aps = isset($_POST['hp_softwares_installer']) ? clean_input($_POST['hp_softwares_installer']) : '_no_';
	$hpExtMail = isset($_POST['hp_external_mail']) ? clean_input($_POST['hp_external_mail']) : '_no_';

	$hpProtectedWebFolders = isset($_POST['hp_external_mail'])
		? clean_input($_POST['hp_external_mail']) : '_no_';

	$status = isset($_POST['hp_status']) ? clean_input($_POST['hp_status']) : '0';

	$php = ($php == '_yes_') ? '_yes_' : '_no_';
	$cgi = ($cgi == '_yes_') ? '_yes_' : '_no_';
	$dns = ($dns == '_yes_') ? '_yes_' : '_no_';
	$backup = ($cfg->BACKUP_DOMAINS != 'no' && in_array($backup, array('_full_', '_dmn_', '_sql_'))) ? $backup : '_no_';
	$aps = ($aps == '_yes_') ? '_yes_' : '_no_';
	$hpExtMail = ($hpExtMail == '_yes_') ? '_yes_' : '_no_';
	$hpProtectedWebFolders = ($hpProtectedWebFolders == '_yes_') ? '_yes_' : '_no_';

	if ($name == '') set_page_message(tr('Name cannot be empty.'), 'error');
	if ($description == '') set_page_message(tr('Description cannot be empty.'), 'error');

	if (!imscp_limit_check($sub, -1)) {
		set_page_message(tr('Incorrect subdomain limit.'), 'error');
	}

	if (!imscp_limit_check($als, -1)) {
		set_page_message(tr('Incorrect domain alias limit.'), 'error');
	}

	if (!imscp_limit_check($mail, -1)) {
		set_page_message(tr('Incorrect email account limit.'), 'error');
	}

	if (!imscp_limit_check($ftp, -1)) {
		set_page_message(tr('Incorrect FTP account limit.'), 'error');
	}

	if (!imscp_limit_check($sqld, -1)) {
		set_page_message(tr('Incorrect SQL user limit.'), 'error');
	} else if ($sqlu != -1 && $sqld == -1) {
		set_page_message(tr('SQL user limit is <i>disabled</i>.'), 'error');
	}

	if (!imscp_limit_check($sqlu, -1)) {
		set_page_message(tr('Incorrect SQL database limit.'), 'error');
	} else if ($sqlu == -1 && $sqld != -1) {
		set_page_message(tr('SQL database limit is not <i>disabled</i>.'), 'error');
	}

	if (!imscp_limit_check($monthlyTraffic, null)) {
		set_page_message(tr('Incorrect monthly traffic limit.'), 'error');
	}

	if (!imscp_limit_check($diskspace, null)) {
		set_page_message(tr('Incorrect disk space limit.'), 'error');
	}

	// Check for mail quota
	if (!imscp_limit_check($mailQuota, null)) {
		set_page_message(tr('Wrong syntax for the mail quota value.'), 'error');
	} elseif ($diskspace != 0 && $mailQuota > $diskspace) {
		set_page_message(tr('Email quota cannot be bigger than disk space limit.'), 'error');
	} elseif($diskspace != 0 && $mailQuota == 0) {
		set_page_message(
			tr('Email quota cannot be unlimited. Max value is %d MiB.', $diskspace), 'error'
		);
	}

	if (isset($_POST['phpiniSystem'])) {
		$phpini->setClPerm('phpiniSystem', clean_input($_POST['phpiniSystem']));

		if (isset($_POST['phpini_perm_allow_url_fopen'])) {
			$phpini->setClPerm('phpiniAllowUrlFopen', clean_input($_POST['phpini_perm_allow_url_fopen']));
		}

		if (isset($_POST['phpini_perm_display_errors'])) {
			$phpini->setClPerm('phpiniDisplayErrors', clean_input($_POST['phpini_perm_display_errors']));
		}

		if (PHP_SAPI != 'apache2handler' && isset($_POST['phpini_perm_disable_functions'])) {
			$phpini->setClPerm('phpiniDisableFunctions', clean_input($_POST['phpini_perm_disable_functions']));
		}

		if (
			isset($_POST['post_max_size']) &&
			!$phpini->setData('phpiniPostMaxSize', clean_input($_POST['post_max_size']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'post_max_size'), 'error');
		}

		if (
			isset($_POST['upload_max_filesize']) &&
			!$phpini->setData('phpiniUploadMaxFileSize', clean_input($_POST['upload_max_filesize']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'upload_max_filesize'), 'error');
		}

		if (
			isset($_POST['max_execution_time']) &&
			!$phpini->setData('phpiniMaxExecutionTime', clean_input($_POST['max_execution_time']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'max_execution_time'), 'error');
		}

		if (
			isset($_POST['max_input_time']) &&
			!$phpini->setData('phpiniMaxInputTime', clean_input($_POST['max_input_time']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'max_input_time'), 'error');
		}

		if (
			isset($_POST['memory_limit']) &&
			!$phpini->setData('phpiniMemoryLimit', clean_input($_POST['memory_limit']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'memory_limit'), 'error');
		}
	}

	if ($php == '_no_' && $aps == '_yes_') {
		set_page_message(tr('The software installer require the PHP support.'), 'error');
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		return true;
	} else {
		return false;
	}
}

/**
 * Update hosting plan
 *
 * @param iMSCP_PHPini $phpini
 * @return bool TRUE on success, FALSE otherwise
 */
function admin_UpdateHostingPlan($phpini)
{
	global $id, $name, $description, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu, $monthlyTraffic, $diskspace,
		   $php, $cgi, $dns, $backup, $aps, $hpExtMail, $hpProtectedWebFolders, $status;

	$query = "
		SELECT
			`id`
		FROM
			`hosting_plans`
		WHERE
			`name` = ?
		AND
			`id` <> ?
		AND
			`reseller_id` IN(SELECT `admin_id` FROM `admin` WHERE `admin_type` = 'admin')
		LIMIT 1
	";
	$stmt = exec_query($query, array($name, $id));

	if ($stmt->rowCount()) {
		set_page_message(tr('A hosting plan with same name already exists.'), 'error');
		return false;
	}

	$hpProps = "$php;$cgi;$sub;$als;$mail;$ftp;$sqld;$sqlu;$monthlyTraffic;$diskspace;$backup;$dns;$aps";
	$hpProps .= ';' . $phpini->getClPermVal('phpiniSystem') . ';' . $phpini->getClPermVal('phpiniAllowUrlFopen');
	$hpProps .= ';' . $phpini->getClPermVal('phpiniDisplayErrors') . ';' . $phpini->getClPermVal('phpiniDisableFunctions');
	$hpProps .= ';' . $phpini->getDataVal('phpiniPostMaxSize') . ';' . $phpini->getDataVal('phpiniUploadMaxFileSize');
	$hpProps .= ';' . $phpini->getDataVal('phpiniMaxExecutionTime') . ';' . $phpini->getDataVal('phpiniMaxInputTime');
	$hpProps .= ';' . $phpini->getDataVal('phpiniMemoryLimit') . ';' . $hpExtMail . ';' . $hpProtectedWebFolders;
	$hpProps .= ';' . $mailQuota * 1048576;

	$query = "UPDATE `hosting_plans` SET `name` = ?, `description` = ?, `props` = ?, `status` = ? WHERE `id` = ?";
	exec_query($query, array($name, $description, $hpProps, $status, $id));

	return true;
}

/***********************************************************************************************************************
 * Functions
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('admin');

/**
 * @var $cfg iMSCP_Config_Handler_File
 */
$cfg = iMSCP_Registry::get('config');

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'shared/partials/forms/hosting_plan_edit.tpl',
			'page_message' => 'layout',
			'php_editor_disable_functions_block' => 'php_editor_feature',
			'custom_dns_records_feature' => 'page',
			'backup_feature' => 'page'
		)
	);

	if (isset($_GET['id'])) {
		global $id;
		$id = clean_input($_GET['id']);

		/* @var $phpini iMSCP_PHPini */
		$phpini = iMSCP_PHPini::getInstance();

		if (!empty($_POST)) {
			if (admin_checkData($phpini) && admin_UpdateHostingPlan($phpini)) {
				set_page_message(tr('Hosting plan successfully updated.'), 'success');
				redirectTo('hosting_plan.php');
			} else {
				admin_generateErrorPage($tpl, $phpini);
			}
		} else {
			admin_generatePage($tpl, $id, $phpini);
		}

		generateNavigation($tpl);

		$tpl->assign(
			array(
				'TR_PAGE_TITLE' => tr('Admin / Hosting Plans / Overview / Edit Hosting Plan'),
				'ISP_LOGO' => layout_getUserLogo(),

				'TR_HOSTING_PLAN' => tr('Hosting plan'),
				'TR_NAME' => tr('Name'),
				'TR_DESCRIPTON' => tr('Description'),

				'TR_HOSTING_PLAN_LIMITS' => tr('Limits'),
				'TR_MAX_SUB' => tr('Subdomain limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
				'TR_MAX_ALS' => tr('Domain alias limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
				'TR_MAX_MAIL' => tr('Email account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
				'TR_MAIL_QUOTA' => tr('Email quota [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
				'TR_MAX_FTP' => tr('FTP account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
				'TR_MAX_SQLD' => tr('SQL database limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
				'TR_MAX_SQLU' => tr('SQL user limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
				'TR_MONTHLY_TRAFFIC' => tr('Monthly traffic limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
				'TR_MAX_DISKSPACE' => tr('Disk space limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',

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

				'TR_AVAILABILITY' => tr('Hosting plan availability'),
				'TR_STATUS' => tr('Available'),

				'TR_YES' => tr('yes'),
				'TR_NO' => tr('no'),
				'TR_UPDATE' => tr('Update'),

				'READONLY' => '',
				'DISABLED' => ''
			)
		);

		generatePageMessage($tpl);

		$tpl->parse('LAYOUT_CONTENT', 'page');

		iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

		$tpl->prnt();
	} else {
		showBadRequestErrorPage();
	}
} else {
	showBadRequestErrorPage();
}
