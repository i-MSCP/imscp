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
	$tplVars['TR_CAN_EDIT_ALLOW_URL_FOPEN'] = tr('Can edit the PHP %s directive', true, '<b>allow_url_fopen</b>');
	$tplVars['ALLOW_URL_FOPEN_YES'] = ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'yes') ? $htmlChecked : '';
	$tplVars['ALLOW_URL_FOPEN_NO'] = ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'no') ? $htmlChecked : '';
	$tplVars['TR_CAN_EDIT_DISPLAY_ERRORS'] = tr('Can edit the PHP %s directive', true, '<b>display_errors</b>');
	$tplVars['DISPLAY_ERRORS_YES'] = ($phpini->getClPermVal('phpiniDisplayErrors') == 'yes') ? $htmlChecked : '';
	$tplVars['DISPLAY_ERRORS_NO'] = ($phpini->getClPermVal('phpiniDisplayErrors') == 'no') ? $htmlChecked : '';

	if (PHP_SAPI == 'apache2handler') {
		$tplVars['PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK'] = '';
	} else {
		$tplVars['TR_CAN_EDIT_DISABLE_FUNCTIONS'] = tr('Can edit the PHP %s directive', true, '<b>disable_functions</b>');
		$tplVars['DISABLE_FUNCTIONS_YES'] = ($phpini->getClPermVal('phpiniDisableFunctions') == 'yes') ? $htmlChecked : '';
		$tplVars['DISABLE_FUNCTIONS_NO'] = ($phpini->getClPermVal('phpiniDisableFunctions') == 'no') ? $htmlChecked : '';
		$tplVars['TR_ONLY_EXEC'] = tr('Only exec');
		$tplVars['DISABLE_FUNCTIONS_EXEC'] = ($phpini->getClPermVal('phpiniDisableFunctions') == 'exec') ? $htmlChecked : '';
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
 * Generate form
 *
 * @param $tpl iMSCP_pTemplate
 * @param int $hpid Hosting plan unique identifier
 * @param $phpini iMSCP_PHPini
 * @return void
 */
function generateForm($tpl, $hpid, $phpini)
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
	$stmt = exec_query($query, $hpid);

	if (!$stmt->rowCount()) {
		showBadRequestErrorPage();
	}

	$data = $stmt->fetchRow();

	$props = $data['props'];
	$description = $data['description'];
	$price = $data['price'];
	$setupFee = $data['setup_fee'];
	$currency = $data['value'];
	$vat = $data['vat'];
	$payment = $data['payment'];
	$status = $data['status'];
	$tos = $data['tos'];

	list(
		$hpPhp, $hpCgi, $hpSub, $hpAls, $hpMail, $hpFtp, $hpSqlDb, $hpSqlUsers, $hpTraffic, $hpDiskspace, $hpBackup,
		$hpDns, $hpSoftwaresInstaller, $phpiniSystem, $phpAllowUrlFopenPerm, $phpDisplayErrorsPerm, $phpDisableFunctionsPerm,
		$phpPostMaxSizeValue, $phpUploadMaxFilesizeValue, $phpMaxExecutionTimeValue, $phpMaxInputTimeValue,
		$phpMemoryLimitValue, $hpExtMail
	) = array_pad(explode(';', $props), 24, 'no');

	$phpini->setClPerm('phpiniSystem', $phpiniSystem);
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
			'HOSTING_PLAN_ID' => tohtml($hpid),

			'HP_NAME_VALUE' => tohtml($data['name']),
			'HP_DESCRIPTION_VALUE' => tohtml($description),

			'TR_MAX_SUB_LIMITS' => tohtml($hpSub),
			'TR_MAX_ALS_VALUES' => tohtml($hpAls),
			'HP_MAIL_VALUE' => tohtml($hpMail),
			'HP_FTP_VALUE' => tohtml($hpFtp),
			'HP_SQL_DB_VALUE' => tohtml($hpSqlDb),
			'HP_SQL_USER_VALUE' => tohtml($hpSqlUsers),
			'HP_TRAFF_VALUE' => tohtml($hpTraffic),
			'HP_DISK_VALUE' => tohtml($hpDiskspace),

			'TR_PHP_YES' => ($hpPhp == '_yes_') ? $checked : '',
			'TR_PHP_NO' => ($hpPhp == '_no_') ? $checked : '',
			'TR_CGI_YES' => ($hpCgi == '_yes_') ? $checked : '',
			'TR_CGI_NO' => ($hpCgi == '_no_') ? $checked : '',
			'TR_DNS_YES' => ($hpDns == '_yes_') ? $checked : '',
			'TR_DNS_NO' => ($hpDns == '_no_') ? $checked : '',
			'VL_BACKUPD' => ($hpBackup == '_dmn_') ? $checked : '',
			'VL_BACKUPS' => ($hpBackup == '_sql_') ? $checked : '',
			'VL_BACKUPF' => ($hpBackup == '_full_') ? $checked : '',
			'VL_BACKUPN' => ($hpBackup == '_no_') ? $checked : '',
			'TR_SOFTWARE_YES' => ($hpSoftwaresInstaller == '_yes_') ? $checked : '',
			'TR_SOFTWARE_NO' => ($hpSoftwaresInstaller == '_no_' || ! $hpSoftwaresInstaller) ? $checked : '',
			'TR_EXTMAIL_YES' => ($hpExtMail == '_yes_') ? $checked : '',
			'TR_EXTMAIL_NO' => ($hpExtMail == '_no_') ? $checked : '',

			'HP_PRICE' => tohtml($price),
			'HP_SETUP_FEE' => tohtml($setupFee),
			'HP_CURRENCY' => tohtml($currency),
			'HP_VAT' => tohtml($vat),

			'HP_TOS_VALUE' => tohtml($tos),
			'TR_STATUS_YES' => ($status) ? $checked : '',
			'TR_STATUS_NO' => (!$status) ? $checked : '',

			'EDIT_HOSTING_PLAN' => (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL === 'admin')
				? tr('View') : tr('Edit')
		)
	);

	_generatePhpBlock($tpl, $phpini);


	$tpl->assign('HP_PAYMENT_DISABLED', '');

	foreach (
		array(
			'monthly' => tr('Monthly'), 'annually' => tr('Annually'), 'biennially' => tr('Biennially'),
			'triennially' => tr('Triennially')
		) as $period => $trPeriod
	) {
		$tpl->assign(
			array(
				'HP_PAYMENT_VALUE' => $period,
				'TR_HP_PAYMENT_VALUE' => $trPeriod,
				'HP_PAYMENT_SELECTED' => ($period == $payment) ? $cfg->HTML_SELECTED : ''
			)
		);

		$tpl->parse('HP_PAYMENT_OPTION', '.hp_payment_option');
	}
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
		   $hpPhp, $hpCgi, $hpBackup, $hpDns, $hpSoftwaresInstaller, $hpExtMail, $price, $setupFee, $vat, $currency,
		   $payment, $status,  $tos, $hpid;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$htmlChecked = $cfg->HTML_CHECKED;

	$tpl->assign(
		array(
			'HOSTING_PLAN_ID' => tohtml($hpid),

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

			'HP_PRICE' => tohtml($price),
			'HP_SETUP_FEE' => tohtml($setupFee),
			'HP_CURRENCY' => tohtml($currency),
			'HP_VAT' => tohtml($vat),

			'HP_TOS_VALUE' => tohtml($tos),

			'TR_STATUS_YES' => ($status) ? $htmlChecked : '',
			'TR_STATUS_NO' => (!$status) ? $htmlChecked : ''
		)
	);

	_generatePhpBlock($tpl, $phpini);

	$tpl->assign('HP_PAYMENT_DISABLED', '');

	foreach (
		array(
			'monthly' => tr('Monthly'), 'annually' => tr('Annually'), 'biennially' => tr('Biennially'),
			'triennially' => tr('Triennially')
		) as $period => $trPeriod
	) {
		$tpl->assign(
			array(
				'HP_PAYMENT_VALUE' => $period,
				'TR_HP_PAYMENT_VALUE' => $trPeriod,
				'HP_PAYMENT_SELECTED' => ($period == $payment) ? $cfg->HTML_SELECTED : ''
			)
		);

		$tpl->parse('HP_PAYMENT_OPTION', '.hp_payment_option');
	}
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
		   $hpPhp, $hpCgi, $hpDns, $hpBackup, $hpSoftwaresInstaller, $hpExtMail, $price, $setupFee, $vat, $currency,
		   $payment, $status, $tos;

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

	$price = isset($_POST['hp_price']) ? clean_input($_POST['hp_price']) : '0';
	$setupFee = isset($_POST['hp_setup_fee']) ? clean_input($_POST['hp_setup_fee']) : '0';
	$vat = isset($_POST['hp_vat']) ? clean_input($_POST['hp_vat']) : '0';
	$currency = isset($_POST['hp_currency']) ? clean_input($_POST['hp_currency']) : '';
	$payment = isset($_POST['hp_payment']) ? clean_input($_POST['hp_payment']) : 'monthly';

	$status = isset($_POST['hp_status']) ? clean_input($_POST['hp_status']) : '0';
	$tos = isset($_POST['hp_tos']) ? clean_input($_POST['hp_tos']) : '';

	if (!in_array($payment, array('monthly', 'annually', 'biennially', 'triennially'))) $payment = 'monthly';

	$hpPhp = ($hpPhp == '_yes_') ? '_yes_' : '_no_';
	$hpCgi = ($hpCgi == '_yes_') ? '_yes_' : '_no_';
	$hpDns = ($hpDns == '_yes_') ? '_yes_' : '_no_';
	$hpBackup = (in_array($hpBackup, array('_full_', '_dmn_', '_sql_'))) ? $hpBackup : '_no_';
	$hpSoftwaresInstaller = ($hpSoftwaresInstaller == '_yes_') ? '_yes_' : '_no_';
	$hpExtMail = ($hpExtMail == '_yes_') ? '_yes_' : '_no_';

	if ($hpName == '') set_page_message(tr('Name cannot be empty.'), 'error');
	if ($description == '') set_page_message(tr('Description cannot be empty.'), 'error');

	if (!imscp_limit_check($hpSub, -1)) {
		set_page_message(tr('Incorrect subdomains limit.'), 'error');
	}

	if (!imscp_limit_check($hpAls, -1)) {
		set_page_message(tr('Incorrect domain aliases limit.'), 'error');
	}

	if (!imscp_limit_check($hpMail, -1)) {
		set_page_message(tr('Incorrect mail accounts limit.'), 'error');
	}

	if (!imscp_limit_check($hpFtp, -1)) {
		set_page_message(tr('Incorrect FTP accounts limit.'), 'error');
	}

	if (!imscp_limit_check($hpSqlDb, -1)) {
		set_page_message(tr('Incorrect SQL users limit.'), 'error');
	} else if ($hpSqlUsers != -1 && $hpSqlDb == -1) {
		set_page_message(tr('SQL users limit is <i>disabled</i>.'), 'error');
	}

	if (!imscp_limit_check($hpSqlUsers, -1)) {
		set_page_message(tr('Incorrect SQL databases limit.'), 'error');
	} else if ($hpSqlUsers == -1 && $hpSqlDb != -1) {
		set_page_message(tr('SQL databases limit is not <i>disabled</i>.'), 'error');
	}

	if (!imscp_limit_check($hpTraffic, null)) {
		set_page_message(tr('Incorrect monthly traffic limit.'), 'error');
	}

	if (!imscp_limit_check($hpDiskspace, null)) {
		set_page_message(tr('Incorrect disk space limit.'), 'error');
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
			! $phpini->setData('phpiniPostMaxSize', clean_input($_POST['post_max_size']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'post_max_size'), 'error');
		}

		if (
			isset($_POST['upload_max_filesize']) &&
			! $phpini->setData('phpiniUploadMaxFileSize', clean_input($_POST['upload_max_filesize']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'upload_max_filesize'), 'error');
		}

		if (
			isset($_POST['max_execution_time']) &&
			! $phpini->setData('phpiniMaxExecutionTime', clean_input($_POST['max_execution_time']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'max_execution_time'), 'error');
		}

		if (
			isset($_POST['max_input_time']) &&
			! $phpini->setData('phpiniMaxInputTime', clean_input($_POST['max_input_time']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'max_input_time'), 'error');
		}

		if (
			isset($_POST['memory_limit']) &&
			! $phpini->setData('phpiniMemoryLimit', clean_input($_POST['memory_limit']))
		) {
			set_page_message(tr('Value for the PHP %s directive is out of range.', 'memory_limit'), 'error');
		}
	}

	if ($hpPhp == '_no_' && $hpSoftwaresInstaller == '_yes_') {
		set_page_message(tr('The software installer require the PHP support.'), 'error');
	}

	if (!is_numeric($price)) {
		set_page_message(tr('Price must be a number.'), 'error');
	}

	if (!is_numeric($setupFee)) {
		set_page_message(tr('Setup fee must be a number.'), 'error');
	}

	if (!is_numeric($vat)) {
		set_page_message(tr('Vat must be a number.'), 'error');
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		return true;
	} else {
		return false;
	}
}

/**
 * Save new hosting plan
 *
 * @param iMSCP_PHPini $phpini
 * @return bool TRUE on success, FALSE otherwise
 */
function saveData($phpini)
{
	global $hpName, $description, $hpSub, $hpAls, $hpMail, $hpFtp, $hpSqlDb, $hpSqlUsers, $hpTraffic, $hpDiskspace,
		   $hpPhp, $hpCgi, $hpDns, $hpBackup, $hpSoftwaresInstaller, $hpExtMail, $price, $setupFee, $currency, $vat,
		   $payment, $status, $tos, $hpid;

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
	$stmt = exec_query($query, array($hpName, $hpid));

	if ($stmt->rowCount()) {
		set_page_message(tr('An hosting plan with same name already exists.'), 'error');
		return false;
	}

	$hpProps = "$hpPhp;$hpCgi;$hpSub;$hpAls;$hpMail;$hpFtp;$hpSqlDb;$hpSqlUsers;$hpTraffic;$hpDiskspace;$hpBackup;";
	$hpProps.= "$hpDns;$hpSoftwaresInstaller";
	$hpProps .= ';' . $phpini->getClPermVal('phpiniSystem') . ';' . $phpini->getClPermVal('phpiniAllowUrlFopen');
	$hpProps .= ';' . $phpini->getClPermVal('phpiniDisplayErrors') . ';' . $phpini->getClPermVal('phpiniDisableFunctions');
	$hpProps .= ';' . $phpini->getDataVal('phpiniPostMaxSize') . ';' . $phpini->getDataVal('phpiniUploadMaxFileSize');
	$hpProps .= ';' . $phpini->getDataVal('phpiniMaxExecutionTime') . ';' . $phpini->getDataVal('phpiniMaxInputTime');
	$hpProps .= ';' . $phpini->getDataVal('phpiniMemoryLimit') . ';' . $hpExtMail;

	$query = "
		UPDATE
			`hosting_plans`
		SET
			`name` = ?, `description` = ?, `props` = ?, `price` = ?, `setup_fee` = ?, `value` = ?, `vat` = ?,
			`payment` = ?, `status` = ?, `tos` = ?
		WHERE
			`id` = ?
	";
	exec_query(
		$query,
		array($hpName, $description, $hpProps, $price, $setupFee, $currency, $vat, $payment, $status, $tos, $hpid)
	);

	return true;
}

/***********************************************************************************************************************
 * Functions
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('admin');

/**
 * @var $cfg iMSCP_Config_Handler_File
 */
$cfg = iMSCP_Registry::get('config');

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL != 'admin') {
	showBadRequestErrorPage();
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'shared/partials/forms/hosting_plan_edit.tpl',
		'page_message' => 'layout',
		'subdomain_edit' => 'page',
		'alias_edit' => 'page',
		'mail_edit' => 'page',
		'ftp_edit' => 'page',
		'sql_db_edit' => 'page',
		'sql_user_edit' => 'page',
		'php_editor_js' => 'page',
		'php_editor_block' => 'page',
		'php_editor_permissions_block' => 'php_editor_block',
		'php_editor_allow_url_fopen_block' => 'php_editor_permissions_block',
		'php_editor_display_errors_block' => 'php_editor_permissions_block',
		'php_editor_disable_functions_block' => 'php_editor_permissions_block',
		'php_editor_default_values_block' => 'php_editor_block',
		't_software_support' => 'page',
		'hp_payment_option' => 'page',
		'submit_button' => 'page'
	)
);

if(isset($_GET['hpid'])) {
	global $hpid;
	$hpid = clean_input($_GET['hpid']);
} else {
	showBadRequestErrorPage();
}

/* @var $phpini iMSCP_PHPini */
$phpini = iMSCP_PHPini::getInstance();

if (!empty($_POST)) {
	if (checkInputData($phpini) && saveData($phpini)) {
		set_page_message(tr('Hosting plan successfully updated.'), 'success');
		redirectTo('hosting_plan.php');
	} else {
		generateErrorForm($tpl, $phpini);
	}
} else {
	generateForm($tpl, $hpid, $phpini);
}

generateNavigation($tpl);

$tpl->assign(
	array(
		'THEME_CHARSET' => tr('encoding'),
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin / Edit hosting plan'),
		'ISP_LOGO' => layout_getUserLogo(),

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

		'TR_BILLING_PROPS' => tr('Billing Settings'),
		'TR_VAT' => tr('Vat'),
		'TR_PRICE' => tr('Price'),
		'TR_SETUP_FEE' => tr('Setup fee'),
		'TR_CURRENCY' => tr('Currency'),
		'TR_PAYMENT' => tr('Billing cycle'),
		'TR_STATUS' => tr('Available for purchasing'),
		'TR_TAX_FREE' => tr('Excl. tax'),
		'TR_EXAMPLE' => tr('(e.g. EUR)'),

		'TR_TOS_PROPS' => tr('Terms of Service'),
		'TR_TOS_NOTE' => tr('<b>Optional:</b> Leave this field empty if you do not want terms of service for this hosting plan.'),
		'TR_TOS_DESCRIPTION' => tr('Text Only'),

		'TR_YES' => tr('yes'),
		'TR_NO' => tr('no'),

		'TR_UPDATE_PLAN' => tr('Update'),
	)
);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
