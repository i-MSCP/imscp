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

/*******************************************************************************
 * Script functions
 */

// Todo Customers PHP Settings synchronization

/**
 * Returns reseller data.
 *
 * @param int $resellerId Domain unique identifier
 * @param bool $forUpdate Tell whether or not data are fetched for update
 * @return array Reference to array of data
 */
function &admin_getData($resellerId, $forUpdate = false)
{
	static $data = null;

	if (null == $data) {

		$query = "
			SELECT
				t1.*, t2.*
			FROM
				`admin` `t1`
			INNER JOIN
				`reseller_props` `t2` ON(`t2`.`reseller_id` = `t1`.`admin_id`)
			WHERE
				`t1`.`admin_id` = ?
		";
		$stmt = exec_query($query, $resellerId);

		if (!($data = $stmt->fetchRow())) {
			set_page_message(tr("The reseller account that you are trying to edit has not been found."), 'error');
			redirectTo('manage_users.php');
		}

		// Getting total number of consumed items for the given reseller.
		list(
			$data['nbDomains'], , ,
			$data['nbSubdomains'], , $data['unlimitedSubdomains'],
			$data['nbDomainAliases'], , $data['unlimitedDomainAliases'],
			$data['nbMailAccounts'], , $data['unlimitedMailAccounts'],
			$data['nbFtpAccounts'], , $data['unlimitedFtpAccounts'],
			$data['nbSqlDatabases'], , $data['unlimitedSqlDatabases'],
			$data['nbSqlUsers'], , $data['unlimitedSqlUsers'],
			$data['totalTraffic'], , $data['unlimitedTraffic'],
			$data['totalDiskspace'], , $data['unlimitedDiskspace']
		) = generate_reseller_users_props($resellerId);

		$data['password'] = '';
		$data['password_confirmation'] = '';

		// Ip data begin

		// Fetch server ip list
		$query = "SELECT `ip_id`, `ip_number` FROM `server_ips`  ORDER BY `ip_number`";
		$stmt = exec_query($query);

		if ($stmt->rowCount()) {
			$data['server_ips'] = $stmt->fetchAll();
		} else {
			set_page_message(tr('Unable to get the IP address list. Please fix this problem.'), 'error');
			redirectTo('manage_users.php');
		}

		// Convert reseller ip list to array
		$data['reseller_ips'] = explode(';', trim($data['reseller_ips'], ';'));

		// Fetch all ip id used by reseller's customers
		$stmt = exec_query(
			'
				SELECT DISTINCT
					domain_ip_id
				FROM
					domain
				INNER JOIN
					admin ON(admin_id = domain_admin_id)
				WHERE
					created_by = ?
			',
			$resellerId
		);

		if ($stmt->rowCount()) {
			$data['used_ips'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
		} else {
			$data['used_ips'] = array();
		}

		$fallbackData = array();

		foreach ($data as $key => $value) {
			$fallbackData["fallback_$key"] = $value;
		}

		$data = array_merge($data, $fallbackData);

		if ($forUpdate) { // Override
			foreach (
				array(
					'password', 'password_confirmation', 'fname', 'lname', 'gender', 'firm', 'zip', 'city', 'state',
					'country', 'email', 'phone', 'fax', 'street1', 'street2', 'max_dmn_cnt', 'max_sub_cnt',
					'max_als_cnt', 'max_mail_cnt', 'max_ftp_cnt', 'max_sql_db_cnt', 'max_sql_user_cnt',
					'max_traff_amnt', 'max_disk_amnt', 'software_allowed', 'softwaredepot_allowed',
					'websoftwaredepot_allowed', 'support_system', 'customer_id',
					'php_ini_system', 'php_ini_al_disable_functions', 'php_ini_al_allow_url_fopen',
					'php_ini_al_display_errors', 'php_ini_max_post_max_size',
					'php_ini_max_upload_max_filesize', 'php_ini_max_max_execution_time',
					'php_ini_max_max_input_time', 'php_ini_max_memory_limit'
				) as $key
			) {
				if (isset($_POST[$key])) {
					$data[$key] = clean_input($_POST[$key]);
				}
			}

			if (isset($_POST['reseller_ips']) && is_array($data['reseller_ips'])) {
				foreach ($_POST['reseller_ips'] as $key => $value) {
					$_POST['reseller_ips'][$key] = clean_input($value);
				}

				$data['reseller_ips'] = $_POST['reseller_ips'];
			} else { // We are safe here
				$data['reseller_ips'] = array();
			}
		}
	}

	return $data;
}

/**
 * Generates account form.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array &$data Reseller data
 * @return void
 */
function _admin_generateAccountForm($tpl, &$data)
{
	$tpl->assign(
		array(
			'TR_ACCOUNT_DATA' => tr('Account data'),
			'TR_RESELLER_NAME' => tr('Name'),
			'RESELLER_NAME' => tohtml($data['admin_name']),
			'TR_PASSWORD' => tr('Password'),
			'PASSWORD' => tohtml($data['password']),
			'TR_GENERATE' => tr('Generate'),
			'TR_SHOW' => tr('Show'),
			'TR_PASSWORD_GENERATION_NEEDED' => tr('You must first generate a password'),
			'TR_NEW_PASSWORD_IS' => tr('New password is'),
			'TR_RESET' => tr('Reset'),
			'TR_PASSWORD_CONFIRMATION' => tr('Password confirmation'),
			'PASSWORD_CONFIRMATION' => tohtml($data['password_confirmation']),
			'TR_EMAIL' => tr('Email'),
			'EMAIL' => tohtml($data['email'])
		)
	);
}

/**
 * Generates IP list form.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array &$data Reseller data
 * @return void
 */
function _admin_generateIpListForm($tpl, &$data)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlChecked = $cfg->HTML_CHECKED;
	$htmlDisabled = "$cfg->HTML_READONLY title=\"" . tr("You cannot unassign an IP address already in use.") . '"';
	$assignedTranslation = tr("Already in use");
	$unusedTranslation = tr("Not used");

	$tpl->assign(
		array(
			'TR_IP_ADDRESS' => tr('IP address'),
			'TR_IP_LABEL' => tr('Label'),
			'TR_ASSIGN' => tr('Assign'),
			'TR_STATUS' => tr('Usage status'),
			'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()
		)
	);

	foreach ($data['server_ips'] as $ipData) {
		$resellerHasIp = in_array($ipData['ip_id'], $data['reseller_ips']);
		$isUsedIp = in_array($ipData['ip_id'], $data['used_ips']);

		$tpl->assign(
			array(
				'IP_ID' => tohtml($ipData['ip_id']),
				'IP_NUMBER' => tohtml($ipData['ip_number']),
				'IP_ASSIGNED' => ($resellerHasIp) ? $htmlChecked : '',
				'IP_STATUS' => ($isUsedIp) ? $assignedTranslation : $unusedTranslation,
				'IP_READONLY' => ($isUsedIp) ? $htmlDisabled : ''
			)
		);

		$tpl->parse('IP_BLOCK', '.ip_block');
	}
}

/**
 * Generates features form.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array &$data Reseller data
 * @return void
 */
function _admin_generateLimitsForm($tpl, &$data)
{
	$tpl->assign(
		array(
			'TR_ACCOUNT_LIMITS' => tr('Account limits'),
			'TR_MAX_DMN_CNT' => tr('Domain limit') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
			'MAX_DMN_CNT' => tohtml($data['max_dmn_cnt']),
			'TR_MAX_SUB_CNT' => tr('Subdomain limit') . '<br /><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
			'MAX_SUB_CNT' => tohtml($data['max_sub_cnt']),
			'TR_MAX_ALS_CNT' => tr('Domain alias limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
			'MAX_ALS_CNT' => tohtml($data['max_als_cnt']),
			'TR_MAX_MAIL_CNT' => tr('Email account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
			'MAX_MAIL_CNT' => tohtml($data['max_mail_cnt']),
			'TR_MAX_FTP_CNT' => tr('FTP account limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
			'MAX_FTP_CNT' => tohtml($data['max_ftp_cnt']),
			'TR_MAX_SQL_DB_CNT' => tr('SQL database limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
			'MAX_SQL_DB_CNT' => tohtml($data['max_sql_db_cnt']),
			'TR_MAX_SQL_USER_CNT' => tr('SQL user limit') . '<br/><i>(-1 ' . tr('disabled') . ', 0 ' . tr('unlimited') . ')</i>',
			'MAX_SQL_USER_CNT' => tohtml($data['max_sql_user_cnt']),
			'TR_MAX_TRAFF_AMNT' => tr('Monthly traffic limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
			'MAX_TRAFF_AMNT' => tohtml($data['max_traff_amnt']),
			'TR_MAX_DISK_AMNT' => tr('Disk space limit [MiB]') . '<br/><i>(0 ' . tr('unlimited') . ')</i>',
			'MAX_DISK_AMNT' => tohtml($data['max_disk_amnt'])
		)
	);
}

/**
 * Generates features form.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array &$data Reseller data
 * @return void
 */
function _admin_generateFeaturesForm($tpl, &$data)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlChecked = $cfg->HTML_CHECKED;

	$tpl->assign(
		array(
			'TR_FEATURES' => tr('Features'),

			'TR_SETTINGS' => tr('Settings'),
			'TR_PHP_EDITOR' => tr('PHP Editor'),
			'TR_PHP_EDITOR_SETTINGS' => tr('PHP Editor Settings'),
			'TR_PERMISSIONS' => tr('Permissions'),
			'TR_DIRECTIVES_VALUES' => tr('PHP directives values'),
			'TR_FIELDS_OK' => tr('All fields seem to be valid.'),
			'TR_VALUE_ERROR' => tr('Value for the PHP <strong>%%s</strong> directive must be between %%d and %%d.', true),
			'TR_CLOSE' => tr('Close'),

			'PHP_INI_SYSTEM_YES' => ($data['php_ini_system'] == 'yes') ? $htmlChecked : '',
			'PHP_INI_SYSTEM_NO' => ($data['php_ini_system'] != 'yes') ? $htmlChecked : '',

			'TR_PHP_INI_AL_ALLOW_URL_FOPEN' => tr('Can edit the PHP %s directive', true, '<b>allow_url_fopen</b>'),
			'PHP_INI_AL_ALLOW_URL_FOPEN_YES' => ($data['php_ini_al_allow_url_fopen'] == 'yes') ? $htmlChecked : '',
			'PHP_INI_AL_ALLOW_URL_FOPEN_NO' => ($data['php_ini_al_allow_url_fopen'] != 'yes') ? $htmlChecked : '',

			'TR_PHP_INI_AL_DISPLAY_ERRORS' => tr('Can edit the PHP %s directive', true, '<b>display_errors</b>'),
			'PHP_INI_AL_DISPLAY_ERRORS_YES' => ($data['php_ini_al_display_errors'] == 'yes') ? $htmlChecked : '',
			'PHP_INI_AL_DISPLAY_ERRORS_NO' => ($data['php_ini_al_display_errors'] != 'yes') ? $htmlChecked : '',

			'TR_PHP_INI_AL_DISABLE_FUNCTIONS' => tr('Can edit the PHP %s directive', true, '<b>disable_functions</b>'),
			'PHP_INI_AL_DISABLE_FUNCTIONS_YES' => ($data['php_ini_al_disable_functions'] == 'yes') ? $htmlChecked : '',
			'PHP_INI_AL_DISABLE_FUNCTIONS_NO' => ($data['php_ini_al_disable_functions'] != 'yes') ? $htmlChecked : '',

			'TR_PHP_INI_MAX_MEMORY_LIMIT' => tr('Max value for the %s PHP directive', true, '<b>memory_limit</b>'),
			'PHP_INI_MAX_MEMORY_LIMIT' => tohtml($data['php_ini_max_memory_limit']),

			'TR_PHP_INI_MAX_UPLOAD_MAX_FILESIZE' => tr('Max value for the %s PHP directive', true, '<b>upload_max_filesize</b>'),
			'PHP_INI_MAX_UPLOAD_MAX_FILESIZE' => tohtml($data['php_ini_max_upload_max_filesize']),

			'TR_PHP_INI_MAX_POST_MAX_SIZE' => tr('Max value for the %s PHP directive', true, '<b>post_max_size</b>'),
			'PHP_INI_MAX_POST_MAX_SIZE' => tohtml($data['php_ini_max_post_max_size']),

			'TR_PHP_INI_MAX_MAX_EXECUTION_TIME' => tr('Max value for the %s PHP directive', true, '<b>max_execution_time</b>'),
			'PHP_INI_MAX_MAX_EXECUTION_TIME' => tohtml($data['php_ini_max_max_execution_time']),

			'TR_PHP_INI_MAX_MAX_INPUT_TIME' => tr('Max value for the %s PHP directive', true, '<b>max_input_time</b>'),
			'PHP_INI_MAX_MAX_INPUT_TIME' => tohtml($data['php_ini_max_max_input_time']),

			'TR_SOFTWARES_INSTALLER' => tr('Software installer'),
			'SOFTWARES_INSTALLER_YES' => ($data['software_allowed'] == 'yes') ? $htmlChecked : '',
			'SOFTWARES_INSTALLER_NO' => ($data['software_allowed'] != 'yes') ? $htmlChecked : '',

			'TR_SOFTWARES_REPOSITORY' => tr('Software repository'),
			'SOFTWARES_REPOSITORY_YES' => ($data['softwaredepot_allowed'] == 'yes') ? $htmlChecked : '',
			'SOFTWARES_REPOSITORY_NO' => ($data['softwaredepot_allowed'] != 'yes') ? $htmlChecked : '',

			'TR_WEB_SOFTWARES_REPOSITORY' => tr('Web software repository'),
			'WEB_SOFTWARES_REPOSITORY_YES' => ($data['websoftwaredepot_allowed'] == 'yes') ? $htmlChecked : '',
			'WEB_SOFTWARES_REPOSITORY_NO' => ($data['websoftwaredepot_allowed'] != 'yes') ? $htmlChecked : '',

			'TR_SUPPORT_SYSTEM' => tr('Support system'),
			'SUPPORT_SYSTEM_YES' => ($data['support_system'] == 'yes') ? $htmlChecked : '',
			'SUPPORT_SYSTEM_NO' => ($data['support_system'] != 'yes') ? $htmlChecked : '',

			'TR_PHP_INI_PERMISSION_HELP' => tr('Yes means that the reseller can allow his customers to edit this directive'),
			'TR_YES' => tr('Yes'),
			'TR_NO' => tr('No'),
			'TR_MIB' => tr('MiB'),
			'TR_SEC' => tr('Sec.')
		)
	);

	if ($cfg['HTTPD_SERVER'] != 'apache_itk') {
		$tpl->assign(
			array(
				'TR_PHP_INI_AL_DISABLE_FUNCTIONS' => tr('Can edit the PHP %s directive', true, '<b>disable_functions</b>'),
				'PHP_INI_AL_DISABLE_FUNCTIONS_YES' => ($data['php_ini_al_disable_functions'] == 'yes') ? $htmlChecked : '',
				'PHP_INI_AL_DISABLE_FUNCTIONS_NO' => ($data['php_ini_al_disable_functions'] != 'yes') ? $htmlChecked : ''
			)
		);
	} else { // disabled_functions option is not available when using Apache ITK
		$tpl->assign('PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK', '');
	}
}

/**
 * Generates features form.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array $data Domain data
 * @return void
 */
function  _admin_generatePersonalDataFrom($tpl, &$data)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlSelected = $cfg->HTML_SELECTED;

	$tpl->assign(
		array(
			'TR_PERSONAL_DATA' => tr('Personal data'),
			'TR_CUSTOMER_ID' => tr('Customer ID'),
			'CUSTOMER_ID' => tohtml($data['customer_id']),
			'TR_FNAME' => tr('First name'),
			'FNAME' => tohtml($data['fname']),
			'TR_LNAME' => tr('Last name'),
			'LNAME' => tohtml($data['lname']),
			'TR_GENDER' => tr('Gender'),
			'TR_MALE' => tr('Male'),
			'MALE' => ($data['gender'] == 'M') ? $htmlSelected : '',
			'TR_FEMALE' => tr('Female'),
			'FEMALE' => ($data['gender'] == 'F') ? $htmlSelected : '',
			'TR_UNKNOWN' => tr('Unknown'),
			'UNKNOWN' => ($data['gender'] != 'M' && $data['gender'] != 'F') ? $htmlSelected : '',
			'TR_FIRM' => tr('Company'),
			'FIRM' => tohtml($data['firm']),
			'TR_STREET1' => tr('Street 1'),
			'STREET1' => tohtml($data['street1']),
			'TR_STREET2' => tr('Street 2'),
			'STREET2' => tohtml($data['street2']),
			'TR_ZIP' => tr('Zip code'),
			'ZIP' => tohtml($data['zip']),
			'TR_CITY' => tr('City'),
			'CITY' => tohtml($data['city']),
			'TR_STATE' => tr('State'),
			'STATE' => tohtml($data['state']),
			'TR_COUNTRY' => tr('Country'),
			'COUNTRY' => tohtml($data['country']),
			'TR_PHONE' => tr('Phone'),
			'PHONE' => tohtml($data['phone']),
			'TR_FAX' => tr('Fax'),
			'FAX' => tohtml($data['fax'])
		)
	);
}

/**
 * Generate edit form.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array &$data Reseller data
 * @return void
 */
function admin_generateForm($tpl, &$data)
{
	_admin_generateAccountForm($tpl, $data);
	_admin_generateIpListForm($tpl, $data);
	_admin_generateLimitsForm($tpl, $data);
	_admin_generateFeaturesForm($tpl, $data);
	_admin_generatePersonalDataFrom($tpl, $data);
}

/**
 * Check and updates reseller data.
 *
 * @throws iMSCP_Exception_Database
 * @param int $resellerId Reseller unique identifier
 * @return bool TRUE on success, FALSE otherwise
 */
function admin_checkAndUpdateData($resellerId)
{
	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditUser, array('userId' => $resellerId));

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$errFieldsStack = array();

	// Get needed data
	$data =& admin_getData($resellerId, true);

	/** @var $db iMSCP_Database */
	$db = iMSCP_Database::getInstance();

	try {
		$db->beginTransaction();

		// check for password

		if (!empty($data['password']) || !empty($data['pasword_confirmation'])) {
			if ($data['password'] != $data['password_confirmation']) {
				set_page_message(tr("Passwords do not match."), 'error');
			}

			checkPasswordSyntax($data['password']);

			if (Zend_Session::namespaceIsset('pageMessages')) {
				$errFieldsStack[] = 'password';
				$errFieldsStack[] = 'password_confirmation';
			}
		}

		// Check for email address

		if (!chk_email($data['email'])) {
			set_page_message(tr('Incorrect syntax for email address.'), 'error');
			$errFieldsStack[] = 'email';
		}

		// Check for ip addresses - We are safe here

		$resellerIps = array();

		foreach ($data['server_ips'] as $serverIpData) {
			if (in_array($serverIpData['ip_id'], $data['reseller_ips'])) {
				$resellerIps[] = $serverIpData['ip_id'];
			}
		}

		$resellerIps = array_unique(array_merge($resellerIps, $data['used_ips']));
		sort($resellerIps);

		if (empty($resellerIps)) {
			set_page_message(tr('You must assign at least one IP per reseller.'), 'error');
		}

		// Check for max domains limit

		if (imscp_limit_check($data['max_dmn_cnt'], null)) {
			$rs = admin_checkResellerLimit($data['max_dmn_cnt'], $data['current_dmn_cnt'], $data['nbDomains'], '0', tr('domains'));
		} else {
			set_page_message(tr('Incorrect limit for %s.', tr('domain')), 'error');
			$rs = false;
		}

		if (!$rs) $errFieldsStack[] = 'max_dmn_cnt';

		// Check for max subdomains limit

		if (imscp_limit_check($data['max_sub_cnt'])) {
			$rs = admin_checkResellerLimit(
				$data['max_sub_cnt'], $data['current_sub_cnt'], $data['nbSubdomains'], $data['unlimitedSubdomains'],
				tr('subdomains')
			);
		} else {
			set_page_message(tr('Incorrect limit for %s.', tr('subdomains')), 'error');
			$rs = false;
		}

		if (!$rs) $errFieldsStack[] = 'max_sub_cnt';

		// check for max domain aliases limit

		if (imscp_limit_check($data['max_als_cnt'])) {
			$rs = admin_checkResellerLimit(
				$data['max_als_cnt'], $data['current_als_cnt'], $data['nbDomainAliases'], $data['unlimitedDomainAliases'],
				tr('domain aliases')
			);
		} else {
			set_page_message(tr('Incorrect limit for %s.', tr('domain aliases')), 'error');
			$rs = false;
		}

		if (!$rs) $errFieldsStack[] = 'max_als_cnt';

		// Check for max mail accounts limit

		if (imscp_limit_check($data['max_mail_cnt'])) {
			$rs = admin_checkResellerLimit(
				$data['max_mail_cnt'], $data['current_mail_cnt'], $data['nbMailAccounts'], $data['unlimitedMailAccounts'],
				tr('mail')
			);
		} else {
			set_page_message(tr('Incorrect limit for %s.', tr('email accounts')), 'error');
			$rs = false;
		}

		if (!$rs) $errFieldsStack[] = 'max_mail_cnt';

		// Check for max ftp accounts limit

		if (imscp_limit_check($data['max_ftp_cnt'])) {
			$rs = admin_checkResellerLimit(
				$data['max_ftp_cnt'], $data['current_ftp_cnt'], $data['nbFtpAccounts'], $data['unlimitedFtpAccounts'],
				tr('Ftp')
			);
		} else {
			set_page_message(tr('Incorrect limit for %s.', tr('Ftp accounts')), 'error');
			$rs = false;
		}

		if (!$rs) $errFieldsStack[] = 'max_ftp_cnt';

		// Check for max Sql databases limit

		if (!$rs = imscp_limit_check($data['max_sql_db_cnt'])) {
			set_page_message(tr('Incorrect limit for %s.', tr('SQL databases')), 'error');
		} elseif ($data['max_sql_db_cnt'] == -1 && $data['max_sql_user_cnt'] != -1) {
			set_page_message(tr('SQL database limit is disabled but SQL user limit is not.'), 'error');
			$rs = false;
		} else {
			$rs = admin_checkResellerLimit(
				$data['max_sql_db_cnt'], $data['current_sql_db_cnt'], $data['nbSqlDatabases'],
				$data['unlimitedSqlDatabases'], tr('SQL databases')
			);
		}

		if (!$rs) $errFieldsStack[] = 'max_sql_db_cnt';

		// Check for max Sql users limit

		if (!$rs = imscp_limit_check($data['max_sql_user_cnt'])) {
			set_page_message(tr('Incorrect limit for %s.', tr('SQL users')), 'error');
		} elseif ($data['max_sql_db_cnt'] != -1 && $data['max_sql_user_cnt'] == -1) {
			set_page_message(tr('SQL user limit is disabled but SQL database limit is not.'), 'error');
			$rs = false;
		} else {
			$rs = admin_checkResellerLimit(
				$data['max_sql_user_cnt'], $data['current_sql_user_cnt'], $data['nbSqlUsers'], $data['unlimitedSqlUsers'],
				tr('SQL users')
			);
		}

		if (!$rs) $errFieldsStack[] = 'max_sql_user_cnt';

		// Check for max monthly traffic limit

		if (imscp_limit_check($data['max_traff_amnt'], null)) {
			$rs = admin_checkResellerLimit(
				$data['max_traff_amnt'], $data['current_traff_amnt'], $data['totalTraffic'] / 1048576,
				$data['unlimitedTraffic'], tr('traffic')
			);
		} else {
			set_page_message(tr('Incorrect limit for %s.', tr('traffic')), 'error');
			$rs = false;
		}

		if (!$rs) $errFieldsStack[] = 'max_traff_amnt';

		// Check for max disk space limit
		if (imscp_limit_check($data['max_disk_amnt'], null)) {
			$rs = admin_checkResellerLimit(
				$data['max_disk_amnt'], $data['current_disk_amnt'],$data['totalDiskspace'] / 1048576,
				$data['unlimitedDiskspace'], tr('disk space')
			);
		} else {
			set_page_message(tr('Incorrect limit for %s.', tr('disk space')), 'error');
			$rs = false;
		}

		if (!$rs) $errFieldsStack[] = 'max_disk_amnt';

		// Check for PHP editor settings

		$phpEditor = iMSCP_PHPini::getInstance();

		if ($data['php_ini_system'] == 'yes') {
			// Check for permissions - We are safe here (If a value is not accepted, we use previous value)
			$phpEditor->setRePerm('phpiniSystem', 'yes');

			if ($cfg['HTTPD_SERVER'] != 'apache_itk') {
				if (!$phpEditor->setRePerm('phpiniDisableFunctions', $data['php_ini_al_disable_functions'])) {
					$phpEditor->setRePerm('phpiniDisableFunctions', $data['fallback_php_ini_al_disable_functions']);
				}
			} else {
				$phpEditor->setRePerm('phpiniDisableFunctions', 'no');
			}

			if (!$phpEditor->setRePerm('phpiniAllowUrlFopen', $data['php_ini_al_allow_url_fopen'])) {
				$phpEditor->setRePerm('phpiniAllowUrlFopen', $data['fallback_php_ini_al_allow_url_fopen']);
			}

			if (!$phpEditor->setRePerm('phpiniDisplayErrors', $data['php_ini_al_display_errors'])) {
				$phpEditor->setRePerm('phpiniDisplayErrors', $data['fallback_php_ini_al_display_errors']);
			}

			// Check for max values
			if (!$phpEditor->setRePerm('phpiniPostMaxSize', $data['php_ini_max_post_max_size']) ||
				!$phpEditor->setRePerm('phpiniUploadMaxFileSize', $data['php_ini_max_upload_max_filesize']) ||
				!$phpEditor->setRePerm('phpiniMaxExecutionTime', $data['php_ini_max_max_execution_time']) ||
				!$phpEditor->setRePerm('phpiniMaxInputTime', $data['php_ini_max_max_input_time']) ||
				!$phpEditor->setRePerm('phpiniMemoryLimit', $data['php_ini_max_memory_limit'])
			) {
				set_page_message(tr('Please check the PHP editor settings.'), 'error');
			}
		} else {
			$phpEditor->loadReDefaultPerm();
		}

		if (empty($errFieldsStack) && !Zend_Session::namespaceIsset('pageMessages')) { // Update process begin here

			$oldValues = $newValues = array();

			foreach ($data as $property => $value) {
				if (strpos($property, 'fallback_') !== false) {
					$property = substr($property, 9);
					$oldValues[$property] = $value;
					$newValues[$property] = $data[$property];
				}
			}

			// Nothing has been changed ?
			if ($newValues == $oldValues) {
				set_page_message(tr("Nothing has been changed."), 'info');
				return true;
			}

			// Update reseller personal data (including password if needed)

			$bindParams = array(
				$data['fname'], $data['lname'], $data['gender'], $data['firm'],
				$data['zip'], $data['city'], $data['state'], $data['country'], $data['email'], $data['phone'],
				$data['fax'], $data['street1'], $data['street2'], $resellerId);

			if ($data['password'] != '') {
				$setPassword = '`admin_pass` = ?,';
				array_unshift($bindParams, cryptPasswordWithSalt($data['password']));
			} else {
				$setPassword = '';
			}

			$query = "
				UPDATE
					`admin`
				SET
					{$setPassword} `fname` = ?, `lname` = ?, `gender` = ?, `firm` = ?, `zip` = ?, `city` = ?,
					`state` = ?, `country` = ?, `email` = ?, `phone` = ?, `fax` = ?, `street1` = ?, `street2` = ?
				WHERE
					`admin_id` = ?
			";
			exec_query($query, $bindParams);

			// Update reseller properties

			$query = '
				UPDATE
					`reseller_props`
				SET
					`max_dmn_cnt` = ?, `max_sub_cnt` = ?, `max_als_cnt` = ?, `max_mail_cnt` = ?, `max_ftp_cnt` = ?,
					`max_sql_db_cnt` = ?, `max_sql_user_cnt` = ?, `max_traff_amnt` = ?, `max_disk_amnt` = ?,
					`reseller_ips` = ?, `customer_id` = ?, `software_allowed` = ?, `softwaredepot_allowed` = ?,
					`websoftwaredepot_allowed` = ?, `support_system` = ?, `php_ini_system` = ?,
					`php_ini_al_disable_functions` = ?, `php_ini_al_allow_url_fopen` = ?, `php_ini_al_display_errors` = ?,
					`php_ini_max_post_max_size` = ?, `php_ini_max_upload_max_filesize` = ?, `php_ini_max_max_execution_time` = ?,
					`php_ini_max_max_input_time` = ?, `php_ini_max_memory_limit` = ?
				WHERE
					`reseller_id` = ?
			';
			exec_query(
				$query,
				array(
					$data['max_dmn_cnt'], $data['max_sub_cnt'], $data['max_als_cnt'],
					$data['max_mail_cnt'], $data['max_ftp_cnt'], $data['max_sql_db_cnt'],
					$data['max_sql_user_cnt'], $data['max_traff_amnt'], $data['max_disk_amnt'],
					implode(';', $resellerIps) . ';', $data['customer_id'], $data['software_allowed'],
					$data['softwaredepot_allowed'], $data['websoftwaredepot_allowed'],
					$data['support_system'],
					$phpEditor->getRePermVal('phpiniSystem'),
					$phpEditor->getRePermVal('phpiniDisableFunctions'),
					$phpEditor->getRePermVal('phpiniAllowUrlFopen'),
					$phpEditor->getRePermVal('phpiniDisplayErrors'),
					$phpEditor->getRePermVal('phpiniPostMaxSize'),
					$phpEditor->getRePermVal('phpiniUploadMaxFileSize'),
					$phpEditor->getRePermVal('phpiniMaxExecutionTime'),
					$phpEditor->getRePermVal('phpiniMaxInputTime'),
					$phpEditor->getRePermVal('phpiniMemoryLimit'),
					$resellerId
				)
			);

			// Updating software installer properties

			if ($data['software_allowed'] == 'no') {
				exec_query(
					'
						UPDATE
							domain
						INNER JOIN
							admin ON(admin_id = domain_admin_id)
						SET
							domain_software_allowed = ?
						WHERE
							created_by = ?
					',
					array($data['softwaredepot_allowed'], $resellerId)
				);
			}

			if ($data['websoftwaredepot_allowed'] == 'no') {
				$query = 'SELECT `software_id` FROM `web_software` WHERE `software_depot` = ? AND `reseller_id` = ?';
				$stmt = exec_query($query, array('yes', $resellerId));

				if ($stmt->rowCount()) {
					while (!$stmt->EOF) {
						$query = 'UPDATE `web_software_inst` SET `software_res_del` = ? WHERE `software_id` = ?';
						exec_query($query, array('1', $stmt->fields['software_id']));
						$stmt->MoveNext();
					}

					$query = 'DELETE FROM `web_software` WHERE `software_depot` = ? AND `reseller_id` = ?';
					exec_query($query, array('yes', $resellerId));
				}
			}

			$db->commit();

			iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditUser, array('userId' => $resellerId));

			// Send mail to reseller for new password
			if ($data['password'] != '') {
				send_add_user_auto_msg(
					$_SESSION['user_id'], $data['admin_name'], $data['password'], $data['email'], $data['fname'],
					$data['lname'], tr('Reseller', true)
				);
			}

			write_log("The reseller account (<b>{$data['admin_name']}</b>) has been updated by {$_SESSION['user_logged']}", E_USER_NOTICE);
			set_page_message(tr('Reseller account successfully updated.'), 'success');

			return true;
		}
	} catch (iMSCP_Exception_Database $e) {
		$db->rollBack();
		throw $e;
	}

	if (!empty($errFieldsStack)) {
		iMSCP_Registry::set('errFieldsStack', $errFieldsStack);
	}

	return false;
}

/**
 * Check reseller limit.
 *
 * @param int $newLimit              New limit (-1 for deactivation, 0 for unlimited, $newLimit > 0 to limit items quantity)
 * @param int $assignedByReseller    How many items are already assigned to reseller's customers
 * @param int $consumedByCustomers   How many items are already consumed by reseller's customers.
 * @param bool $unlimitedService     Tells whether or not the service is set as unlimited for a reseller's customer
 * @param String $serviceName        Service name for which new limit is verified
 * @return bool                      TRUE if new limit is valid, FALSE otherwise
 */
function admin_checkResellerLimit($newLimit, $assignedByReseller, $consumedByCustomers, $unlimitedService, $serviceName)
{
	$retVal = true;

	// We process only if the new limit value is not equal to 0 (unlimited)
	if ($newLimit != 0) {
		// The service is limited for all customers
		if ($unlimitedService == false) {
			// If the new limit is lower than the already consomed item by customer
			if ($newLimit < $consumedByCustomers && $newLimit != -1) {
				set_page_message(tr("%s: The clients consumption (%s) for this reseller is greater than the new limit.", true, '<b>' . ucfirst($serviceName) . '</b', $consumedByCustomers), 'error');
				$retVal = false;
				// If the new limit is lower than the items already assigned by the reseller
			} elseif ($newLimit < $assignedByReseller && $newLimit != -1) {
				set_page_message(tr('%s: The total of items (%s) already assigned by the reseller is greater than the new limit.', true, '<b>' . ucfirst($serviceName) . '</b>', $assignedByReseller), 'error');
				$retVal = false;
				// If the new limit is -1 (disabled) and assigned items are already consumed by customer
			} elseif ($newLimit == -1 && $consumedByCustomers > 0) {
				set_page_message(tr("%s: You cannot disable a service already consumed by reseller's customers", true, '<b>' . ucfirst($serviceName) . '</b>'), 'error');
				$retVal = false;
				// If the new limit is -1 (disabled) and the already assigned accounts/limits by reseller is greater 0
			} elseif ($newLimit == -1 && $assignedByReseller > 0) {
				set_page_message(tr("%s: You cannot disable a service already sold to reseller's customers.", true, '<b>' . ucfirst($serviceName) . '</b>'), 'error');
				$retVal = false;
			}
			// One or more reseller's customers have unlimited items
		} elseif ($newLimit != 0) {
			set_page_message(tr('%s: This reseller has customer(s) with unlimited items.', true, '<b>' . ucfirst($serviceName) . '</b>'), 'error');
			set_page_message(tr('If you want to limit the reseller, you must first limit its customers.'), 'error');
			$retVal = false;
		}
	}

	return $retVal;
}

/*******************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login('admin');

// Dispatches the request
if (!isset($_GET['edit_id'])) {
	showBadRequestErrorPage();
} elseif (is_xhr()) { // Passsword generation (AJAX request)
	header('Content-Type: text/plain; charset=utf-8');
	header('Cache-Control: no-cache, private');
	header('Pragma: no-cache');
	header("HTTP/1.0 200 Ok");
	echo passgen();
	exit;
} else {
	$resellerId = (int)$_GET['edit_id'];

	if (!empty($_POST) && admin_checkAndUpdateData($resellerId)) {
		redirectTo('manage_users.php');
	}
}

// Getting domain data
$data =& admin_getData($resellerId);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/reseller_edit.tpl',
		'page_message' => 'layout',
		'ips_block' => 'page',
		'ip_block' => 'ips_block',
		'php_editor_disable_functions_block' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Users / Edit Reseller'),
		'ISP_LOGO' => layout_getUserLogo(),
		'EDIT_ID' => $resellerId,
		'TR_EDIT_RESELLER' => tr('Edit reseller'),
		'TR_NOTICE' => tr('i-MSCP Notice'),
		'TR_EVENT_NOTICE' => tojs(tr('The `Enter` key is disabled for performance reasons.', true)),
		'TR_UPDATE' => tr('Update'),
		'TR_CANCEL' => tr('Cancel'),
		'ERR_FIELDS_STACK' => (iMSCP_Registry::isRegistered('errFieldsStack'))
			? json_encode(iMSCP_Registry::get('errFieldsStack')) : '[]'));

generateNavigation($tpl);
admin_generateForm($tpl, $data);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
