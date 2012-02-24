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
 * @param iMSCP_PHPini $phpini
 * @return void
 */
function get_init_au2_page($tpl, $phpini)
{
	global $hp_name, $hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db,
		$hp_sql_user, $hp_traff, $hp_disk, $hp_backup, $hp_dns, $hp_allowsoftware;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlChecked = $cfg->HTML_CHECKED;

	$tplVars = array();

	$tplVars['VL_TEMPLATE_NAME'] = tohtml($hp_name);
	$tplVars['MAX_DMN_CNT'] = '';
	$tplVars['MAX_SUBDMN_CNT'] = tohtml($hp_sub);
	$tplVars['MAX_DMN_ALIAS_CNT'] = tohtml($hp_als);
	$tplVars['MAX_MAIL_CNT'] = tohtml($hp_mail);
	$tplVars['MAX_FTP_CNT'] = tohtml($hp_ftp);
	$tplVars['MAX_SQL_CNT'] = tohtml($hp_sql_db);
	$tplVars['VL_MAX_SQL_USERS'] = tohtml($hp_sql_user);
	$tplVars['VL_MAX_TRAFFIC'] = tohtml($hp_traff);
	$tplVars['VL_MAX_DISK_USAGE'] = tohtml($hp_disk);
	$tplVars['VL_PHPY'] = ($hp_php == '_yes_') ? $htmlChecked : '';
	$tplVars['VL_PHPN'] = ($hp_php == '_no_') ? $htmlChecked : '';
	$tplVars['VL_CGIY'] = ($hp_cgi == '_yes_') ? $htmlChecked : '';
	$tplVars['VL_CGIN'] = ($hp_cgi == '_no_') ? $htmlChecked : '';
	$tplVars['VL_BACKUPD'] = ($hp_backup == '_dmn_') ? $htmlChecked : '';
	$tplVars['VL_BACKUPS'] = ($hp_backup == '_sql_') ? $htmlChecked : '';
	$tplVars['VL_BACKUPF'] = ($hp_backup == '_full_') ? $htmlChecked : '';
	$tplVars['VL_BACKUPN'] = ($hp_backup == '_no_') ? $htmlChecked : '';
	$tplVars['VL_DNSY'] = ($hp_dns == '_yes_') ? $htmlChecked : '';
	$tplVars['VL_DNSN'] = ($hp_dns == '_no_') ? $htmlChecked : '';
	$tplVars['VL_SOFTWAREY'] = ($hp_allowsoftware == '_yes_') ? $htmlChecked : '';
	$tplVars['VL_SOFTWAREN'] = ($hp_allowsoftware == '_no_') ? $htmlChecked : '';

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
		$tplVars['TR_PHP_POST_MAX_SIZE_DIRECTIVE'] = tr('PHP %s directive', true, '<span class="bold">post_max_size</span>');
		$tplVars['PHP_UPLOAD_MAX_FILEZISE_DIRECTIVE'] = tr('PHP %s directive', true, '<span class="bold">upload_max_filezize</span>');
		$tplVars['TR_PHP_MAX_EXECUTION_TIME_DIRECTIVE'] = tr('PHP %s directive', true, '<span class="bold">max_execution_time</span>');
		$tplVars['TR_PHP_MAX_INPUT_TIME_DIRECTIVE'] = tr('PHP %s directive', true, '<span class="bold">max_input_time</span>');
		$tplVars['TR_PHP_MEMORY_LIMIT_DIRECTIVE'] = tr('PHP %s directive', true, '<span class="bold">memory_limit</span>');
		$tplVars['TR_MIB'] = tr('MiB');
		$tplVars['TR_SEC'] = tr('Sec.');

		$permissionsBlock = false;

		if (!$phpini->checkRePerm('phpiniRegisterGlobals')) {
			$tplVars['PHP_EDITOR_REGISTER_GLOBALS_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_REGISTER_GLOBALS'] = tr('Can edit the PHP %s directive', true, '<span class="bold">register_globals</span>');
			$tplVars['REGISTER_GLOBALS_YES'] = ($phpini->getClPermVal('phpiniRegisterGlobals') == 'yes') ? $htmlChecked : '';
			$tplVars['REGISTER_GLOBALS_NO'] = ($phpini->getClPermVal('phpiniRegisterGlobals') == 'no') ? $htmlChecked : '';
			$permissionsBlock = true;
		}

		if (!$phpini->checkRePerm('phpiniAllowUrlFopen')) {
			$tplVars['PHP_EDITOR_ALLOW_URL_FOPEN_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_ALLOW_URL_FOPEN'] = tr('Can edit the PHP %s directive', true, '<span class="bold">allow_url_fopen</span>');
			$tplVars['ALLOW_URL_FOPEN_YES'] = ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'yes') ? $htmlChecked : '';
			$tplVars['ALLOW_URL_FOPEN_NO'] = ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'no') ? $htmlChecked : '';
			$permissionsBlock = true;
		}

		if (!$phpini->checkRePerm('phpiniDisplayErrors')) {
			$tplVars['PHP_EDITOR_DISPLAY_ERRORS_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_DISPLAY_ERRORS'] = tr('Can edit the PHP %s directive', true, '<span class="bold">display_errors</span>');
			$tplVars['DISPLAY_ERRORS_YES'] = ($phpini->getClPermVal('phpiniDisplayErrors') == 'yes') ? $htmlChecked : '';
			$tplVars['DISPLAY_ERRORS_NO'] = ($phpini->getClPermVal('phpiniDisplayErrors') == 'no') ? $htmlChecked : '';
			$permissionsBlock = true;
		}

		if (PHP_SAPI == 'apache2handler' || !$phpini->checkRePerm('phpiniDisableFunctions')) {
			$tplVars['PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_DISABLE_FUNCTIONS'] = tr('Can edit the PHP %s directive', true, '<span class="bold">disable_functions</span>');
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
				 'memory_limit' => $phpini->getRePermVal('phpiniMemoryLimit')));
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
function get_hp_data($hpid, $resellerId, $phpini)
{
	global $hp_name, $hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db,
		$hp_sql_user, $hp_traff, $hp_disk, $hp_backup, $hp_dns, $hp_allowsoftware;

	$query = 'SELECT `name`, `props` FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?';
	$stmt = exec_query($query, array($resellerId, $hpid));

	if (0 !== $stmt->rowCount()) {
		$data = $stmt->fetchRow();
		$props = $data['props'];

		list(
			$hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db,
			$hp_sql_user, $hp_traff, $hp_disk, $hp_backup, $hp_dns, $hp_allowsoftware,
			$phpini_system, $phpini_al_register_globals, $phpini_al_allow_url_fopen,
			$phpini_al_display_errors, $phpini_al_disable_functions, $phpini_post_max_size,
			$phpini_upload_max_filesize, $phpini_max_execution_time, $phpini_max_input_time,
			$phpini_memory_limit
		) = array_pad(explode(';', $props), 23, 'no');

		$hp_name = $data['name'];

		// Write into phpini object
		$phpini->setClPerm('phpiniSystem', $phpini_system);
		$phpini->setClPerm('phpiniRegisterGlobals', $phpini_al_register_globals);
		$phpini->setClPerm('phpiniAllowUrlFopen', $phpini_al_allow_url_fopen);
		$phpini->setClPerm('phpiniDisplayErrors', $phpini_al_display_errors);
		$phpini->setClPerm('phpiniDisableFunctions', $phpini_al_disable_functions);

		// Use phpini->phpiniData as datastore for the following values - should be better in something like property class/object later
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
 * @param iMSCP_PHPini $phpini
 * @return bool TRUE if all data are valid, FALSE otherwise
 */
function check_user_data($phpini)
{
	global $hp_name, $hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db,
		$hp_sql_user, $hp_traff, $hp_disk, $hp_dmn, $hp_backup, $hp_dns, $hp_allowsoftware;

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

	if (isset($_POST['software_allowed']) && resellerHasFeature('aps')) {
		$hp_allowsoftware = $_POST['software_allowed'];
	} else {
		$hp_allowsoftware = 'no';
	}

	if ($phpini->checkRePerm('phpiniSystem') && isset($_POST['phpiniSystem'])) {
		$phpini->setClPerm('phpiniSystem', clean_input($_POST['phpiniSystem']));

		if ($phpini->checkRePerm('phpiniRegisterGlobals') && isset($_POST['phpini_perm_register_globals'])) {
			$phpini->setClPerm('phpiniRegisterGlobals', clean_input($_POST['phpini_perm_register_globals']));
		}

		if ($phpini->checkRePerm('phpiniAllowUrlFopen') && isset($_POST['phpini_perm_allow_url_fopen'])) {
			$phpini->setClPerm('phpiniAllowUrlFopen', clean_input($_POST['phpini_perm_allow_url_fopen']));
		}

		if ($phpini->checkRePerm('phpiniDisplayErrors') && isset($_POST['phpini_perm_display_errors'])) {
			$phpini->setClPerm('phpiniDisplayErrors', clean_input($_POST['phpini_perm_display_errors']));
		}

		if ($phpini->checkRePerm('phpiniDisplayErrors') && isset($_POST['phpini_al_error_reporting'])) {
			$phpini->setClPerm('phpiniErrorReporting', clean_input($_POST['phpini_al_error_reporting']));
		}

		if (PHP_SAPI != 'apache2handler' && $phpini->checkRePerm('phpiniDisableFunctions') &&
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
		set_page_message(tr('The softwares installer needs PHP.'), 'error');
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
 * @param  int $resellerId Reseller unique identifier
 * @return bool TRUE if hosting with same name was found, FALSE otherwise
 */
function check_hosting_plan_name($resellerId)
{
	global $hp_name;

	$query = 'SELECT `id` FROM `hosting_plans` WHERE `name` = ? AND `reseller_id` = ?';
	$stmt = exec_query($query, array($hp_name, $resellerId));

	if ($stmt->rowCount() !== 0) {
		return false;
	}

	return true;
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

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
		'ftp_feature' => 'page',
		'sql_feature' => 'page',
		'aps_feature' => 'page',
		'backup_feature' => 'page',
		'php_editor_js' => 'page',
		'php_editor_block' => 'page',
		'php_editor_permissions_block' => 'php_editor_block',
		'php_editor_register_globals_block' => 'php_editor_permissions_block',
		'php_editor_allow_url_fopen_block' => 'php_editor_permissions_block',
		'php_editor_display_errors_block' => 'php_editor_permissions_block',
		'php_editor_disable_functions_block' => 'php_editor_permissions_block',
		'php_editor_default_values_block' => 'php_editor_block'));

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
	redirectTo('users.php?psi=last');
}

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - User/Add domain account - step 2'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_ADD_USER' => tr('Add user'),
		 'TR_HOSTING_PLAN' => tr('Hosting plan'),
		 'TR_NAME' => tr('Name'),
		 'TR_MAX_DOMAIN' => tr('Max domains<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_SUBDOMAIN' => tr('Max subdomains<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_DOMAIN_ALIAS' => tr('Max aliases<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_MAIL_COUNT' => tr('Mail accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_FTP' => tr('Ftp accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_SQL_DB' => tr('Sql databases limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_SQL_USERS' => tr('Sql users limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		 'TR_MAX_TRAFFIC' => tr('Traffic limit [MiB]<br><i>(0 unlimited)</i>'),
		 'TR_MAX_DISK_USAGE' => tr('Disk limit [MiB]<br><i>(0 unlimited)</i>'),
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
		 'TR_SOFTWARE_SUPP' => tr('Softwares installer')));

generateNavigation($tpl);

if (!get_pageone_param()) {
	set_page_message(tr('Domain data were been altered. Please try again.'), 'error');
	unsetMessages();
	redirectTo('user_add1.php');
}

if (isset($_POST['uaction']) && ('user_add2_nxt' == $_POST['uaction']) &&
	(!isset($_SESSION['step_one']))
) {
	if (check_user_data($phpini)) {
		$_SESSION['step_two_data'] = "$dmn_name;0;";
		$_SESSION['ch_hpprops'] = "$hp_php;$hp_cgi;$hp_sub;$hp_als;$hp_mail;" .
								  "$hp_ftp;$hp_sql_db;$hp_sql_user;$hp_traff;" .
								  "$hp_disk;$hp_backup;$hp_dns;$hp_allowsoftware;" .
								  $phpini->getClPermVal('phpiniSystem') . ';' .
								  $phpini->getClPermVal('phpiniRegisterGlobals') . ';' .
								  $phpini->getClPermVal('phpiniAllowUrlFopen') . ';' .
								  $phpini->getClPermVal('phpiniDisplayErrors') . ';' .
								  $phpini->getClPermVal('phpiniDisableFunctions') . ';' .
								  $phpini->getDataVal('phpiniPostMaxSize') . ";" .
								  $phpini->getDataVal('phpiniUploadMaxFileSize') . ';' .
								  $phpini->getDataVal('phpiniMaxExecutionTime') . ';' .
								  $phpini->getDataVal('phpiniMaxInputTime') . ';' .
								  $phpini->getDataVal('phpiniMemoryLimit');


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

// TODO check the resellerHasFeature('aps') according the function below
//get_reseller_software_permission($tpl, $_SESSION['user_id']);

if (!resellerHasFeature('subdomains')) {
	$tpl->assign('SUBDOMAIN_FEATURE', '');
}

if (!resellerHasFeature('domain_aliases')) {
	$tpl->assign('ALIAS_FEATURE', '');
}

if (!resellerHasFeature('mail')) {
	$tpl->assign('MAIL_FEATURE', '');
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

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
