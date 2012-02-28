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

// Note to developers: When editing this script, don't forget to also
// edit the admin/domain_edit.php script.

/************************************************************************************
 * script functions
 */

/**
 * Returns domain related data.
 *
 * @param int $domainId Domain unique identifier
 * @param bool $forUpdate Tell whether or not data are fetched for update
 * @param bool $recoveryMode If set to TRUE, will force data to be fetched from database
 * @return array Reference to array of data
 */
function &reseller_getData($domainId, $forUpdate = false, $recoveryMode = false)
{
	static $data = null;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if(null == $data || $recoveryMode) {
		$statusOk = "$cfg->ITEM_OK_STATUS|$cfg->ITEM_DISABLED_STATUS|$cfg->ITEM_ORDERED_STATUS";

		// Checks for domain existence and status

		$query = "
			SELECT
				`t1`.`domain_status`,
				COUNT(`t2`.`subdomain_status`) + COUNT(`t3`.`alias_status`) +
				COUNT(`t4`.`subdomain_alias_status`) `statusNotOk`
			FROM
				`domain` `t1`
			LEFT JOIN `subdomain` `t2` ON (`t1`.`domain_id` = `t2`.`domain_id` AND `t2`.`subdomain_status` NOT RLIKE ?)
			LEFT JOIN `domain_aliasses` `t3` ON (`t1`.`domain_id` = `t3`.`domain_id` AND `t3`.`alias_status` NOT RLIKE ?)
			LEFT JOIN `subdomain_alias` `t4` ON (`t4`.`alias_id` = `t3`.`alias_id` AND `t4`.`subdomain_alias_status` NOT RLIKE ?)
			WHERE
				`t1`.`domain_id` = ?
			AND
				`t1`.`domain_created_id` = ?
		";
		$stmt = exec_query($query, array($statusOk, $statusOk, $statusOk, $domainId, $_SESSION['user_id']));

		if($stmt->fields['domain_status'] == '') {
			set_page_message(tr("The domain you are trying to edit doesn't exist."), 'error');
			redirectTo('users.php');
		} elseif(($stmt->fields['domain_status'] != $cfg->ITEM_OK_STATUS && $stmt->fields['domain_status'] != $cfg->ITEM_DISABLED_STATUS)
				 || $stmt->fields('statusNotOk') > 0
		) {
			set_page_message(tr("The domain or at least one of its entities has a different status than 'ok'."), 'warning');
			redirectTo('users.php');
		} elseif($stmt->fields['domain_status'] == $cfg->ITEM_DISABLED_STATUS) {
			set_page_message(tr('The domain is currently deactivated. The modification of some of its properties will result by a complete or partial reactivation of it.'), 'warning');
		}

		// Getting domain data

		$bindParams = array();
		$bindParams[] = $cfg->ITEM_ORDERED_STATUS;

		$notRlikeCondition = '';

		if(!$cfg->COUNT_DEFAULT_EMAIL_ADDRESSES) {
			$notRlikeCondition = "AND `t7`.`mail_addr` NOT RLIKE ?";
			$bindParams[] = '^(webmaster|abuse|postmaster)@';
		}

		$bindParams[] = $domainId;

		// Request for update ?
		$lockInShareMode = ($forUpdate) ? 'LOCK IN SHARE MODE' : '';

		$query = "
			SELECT
				-- domain data
				`t1`.`domain_id`, `t1`.`domain_name`, `t1`.`domain_expires`,
				`t1`.`domain_status`, `t1`.`domain_subd_limit`, `t1`.`domain_alias_limit`,
				`t1`.`domain_mailacc_limit`, `t1`.`domain_ftpacc_limit`, `t1`.`domain_sqld_limit`,
				`t1`.`domain_sqlu_limit`, `t1`.`domain_disk_limit`, `t1`.`domain_disk_usage`,
				`t1`.`domain_traffic_limit`, `t1`.`domain_php`, `t1`.`domain_cgi`, `t1`.`domain_dns`,
				`t1`.`domain_software_allowed`, `t1`.`allowbackup`, `t1`.`phpini_perm_system` `customer_php_ini_system`,

				-- domain reseller props
				`t2`.`reseller_id`, `t2`.`current_sub_cnt`, `t2`.`max_sub_cnt`,
				`t2`.`current_als_cnt`, `t2`.`max_als_cnt`, `t2`.`current_mail_cnt`,
				`t2`.`max_mail_cnt`, `t2`.`current_ftp_cnt`, `t2`.`max_ftp_cnt`,
				`t2`.`current_sql_db_cnt`, `t2`.`max_sql_db_cnt`, `t2`.`current_sql_user_cnt`,
				`t2`.`max_sql_user_cnt`, `t2`.`current_disk_amnt`, `t2`.`max_disk_amnt`,
				`t2`.`current_traff_amnt`, `t2`.`max_traff_amnt`, `t2`.`software_allowed`,
				`t2`.`php_ini_system` `reseller_php_ini_system`,

				-- domain ip info
				`t3`.`ip_number`, `t3`.`ip_domain`,

				-- count domain aliasses
				COUNT(DISTINCT `t4`.`alias_id`) `nbAliasses`,

				-- count subdomains (belong to domain and domain aliasses)
				COUNT(DISTINCT `t5`.`subdomain_id`) + COUNT(DISTINCT `t6`.`subdomain_alias_id`) `nbSubdomains`,

				-- count mail accounts
				COUNT(DISTINCT `t7`.`mail_id`) `nbMailAccounts`,

				-- count ftp accounts
				COUNT(DISTINCT `t8`.`userid`) `nbFtpAccounts`,

				-- count Sql databases
				COUNT(DISTINCT `t9`.`sqld_id`) `nbSqlDatabases`,

				-- count Sql users
				COUNT(DISTINCT `t10`.`sqlu_id`) `nbSqlUsers`,

				-- domain traffic
				IFNULL(
					SUM(DISTINCT `t11`.`dtraff_web`) +
					SUM(DISTINCT `t11`.`dtraff_ftp`) +
					SUM(DISTINCT `t11`.`dtraff_mail`) +
					SUM(DISTINCT `t11`.`dtraff_pop`),
					0
				) `domainTraffic`
			FROM
				`domain` `t1`
			INNER JOIN
				`reseller_props` `t2` ON (`t1`.`domain_created_id` = `t2`.`reseller_id`)
			INNER JOIN
				`server_ips` `t3` ON (`t3`.`ip_id` = `t1`.`domain_ip_id`)
			LEFT JOIN
				`domain_aliasses` `t4` ON (`t1`.`domain_id` = `t4`.`domain_id` AND `t4`.`alias_status` != ?)
			LEFT JOIN
				`subdomain` `t5` ON (`t1`.`domain_id` = `t5`.`domain_id`)
			LEFT JOIN
				`subdomain_alias` `t6` ON (`t4`.`alias_id` = `t6`.`alias_id`)
			LEFT JOIN
				`mail_users` `t7` ON (`t1`.`domain_id` = `t7`.`domain_id` {$notRlikeCondition} AND t7.`mail_type` NOT RLIKE '_catchall')
			LEFT JOIN
				`ftp_users` `t8` ON (`t8`.`userid` RLIKE CONCAT('@', `t1`.`domain_name`, '$') OR `t8`.`userid` RLIKE CONCAT('@', `t4`.`alias_name`, '$'))
			LEFT JOIN
				`sql_database` `t9` ON (`t1`.`domain_id` = `t9`.`domain_id`)
			LEFT JOIN
				`sql_user` `t10` ON (`t9`.`sqld_id` = `t10`.`sqld_id`)
			LEFT JOIN
				`domain_traffic` `t11` ON (`t1`.`domain_id` = `t11`.`domain_id`)
			WHERE
				`t1`.`domain_id` = ?

			-- prevent inconsistency data
			{$lockInShareMode}
		";
		$stmt = exec_query($query, $bindParams);
		$data = $stmt->fetchRow();

		// Fallback values
		$data['fallback_domain_expires'] = $data['domain_expires'];
		$data['fallback_domain_subd_limit'] = $data['domain_subd_limit'];
		$data['fallback_domain_alias_limit'] = $data['domain_alias_limit'];
		$data['fallback_domain_mailacc_limit'] = $data['domain_mailacc_limit'];
		$data['fallback_domain_ftpacc_limit'] = $data['domain_ftpacc_limit'];
		$data['fallback_domain_sqld_limit'] = $data['domain_sqld_limit'];
		$data['fallback_domain_sqlu_limit'] = $data['domain_sqlu_limit'];
		$data['fallback_domain_traffic_limit'] = $data['domain_traffic_limit'];
		$data['fallback_domain_disk_limit'] = $data['domain_disk_limit'];
		$data['fallback_domain_php'] = $data['domain_php'];
		$data['fallback_domain_cgi'] = $data['domain_cgi'];
		$data['fallback_domain_dns'] = $data['domain_dns'];
		$data['fallback_domain_software_allowed'] = $data['domain_software_allowed'];
		$data['fallback_allowbackup'] = $data['allowbackup'];

		$data['domain_expires_ok'] = true;
		$data['domain_never_expires'] = ($data['domain_expires'] == 0) ? 'on' : 'off';

		// Load reseller and customer permissions if needed
		if($data['reseller_php_ini_system'] == 'yes') {
			$phpEditor = iMSCP_PHPini::getInstance();
			$phpEditor->loadRePerm($data['reseller_id']);

			if($data['customer_php_ini_system'] == 'yes') {
				$phpEditor->loadClPerm($data['domain_id']);
				// Try to load the custom PHP directive values for the customer
				// If they exists, they will replace the default values
				$phpEditor->loadCustomPHPini($data['domain_id']);
			}
		}

		if ($forUpdate) { // Post request
			foreach (array(
				'domain_subd_limit' => 'max_sub_cnt', 'domain_alias_limit' => 'max_als_cnt',
				'domain_mailacc_limit' => 'max_mail_cnt', 'domain_ftpacc_limit' => 'max_ftp_cnt',
				'domain_sqld_limit' => 'max_sql_db_cnt', 'domain_sqlu_limit' => 'max_sql_user_cnt',
				'domain_traffic_limit' => 'max_traff_amnt', 'domain_disk_limit' => 'max_disk_amnt'
				) as $customerLimit => $resellerMaxLimit
			) {
				if (array_key_exists($customerLimit, $_POST) && $data[$resellerMaxLimit] != -1) {
						$data[$customerLimit] = clean_input($_POST[$customerLimit]);
				}
			}

			$data['domain_expires'] = (isset($_POST['domain_expires']))
				? clean_input($_POST['domain_expires']) : $data['domain_expires'];

			$data['domain_never_expires'] = (isset($_POST['domain_never_expires']))
				? clean_input($_POST['domain_never_expires']) : 'off';

			$data['domain_php'] = isset($_POST['domain_php'])
				? clean_input($_POST['domain_php']) : $data['domain_php'];

			$data['domain_cgi'] = isset($_POST['domain_cgi'])
				? clean_input($_POST['domain_cgi']) : $data['domain_cgi'];

			$data['domain_dns'] = isset($_POST['domain_dns'])
				? clean_input($_POST['domain_dns']) : $data['domain_software_allowed'];

			if ($data['software_allowed'] == 'yes') {
				$data['domain_software_allowed'] = isset($_POST['domain_software_allowed'])
					? clean_input($_POST['domain_software_allowed']) : $data['domain_software_allowed'];
			} else {
				$data['domain_software_allowed'] = 'no';
			}

			if($cfg->BACKUP_DOMAINS == 'yes') {
				$data['allowbackup'] = isset($_POST['allowbackup'])
					? clean_input($_POST['allowbackup']) : $data['allowbackup'];
			} else {
				$data['allowbackup'] = 'no';
			}
		}
	}

	return $data;
} // end reseller_getData()

/**
 * Generate edit form.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array &$data Domain related data
 * @return void
 */
function reseller_generateForm($tpl, &$data)
{
	_reseller_generateLimitsForm($tpl, $data);
	_reseller_generateFeaturesForm($tpl, $data);
}

/**
 * Generates domain limits form.
 *
 * Note: Only shows the limits on which the domain reseller has permissions.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array $data Domain data
 * @return void
 */
function _reseller_generateLimitsForm($tpl, &$data)
{
	$tplVars = array();

	$tplVars['TR_DOMAIN_LIMITS'] = tr('Domain Limit');
	$tplVars['TR_LIMIT_VALUE'] = tr('Limit value');
	$tplVars['TR_CUSTOMER_CONSUMPTION'] = tr('Customer consumption');
	$tplVars['TR_RESELLER_CONSUMPTION'] = isset($_SESSION['logged_from'])
		? tr('Reseller consumption') : tr('Your consumption');

	// Subdomains limit
	if ($data['max_sub_cnt'] == -1) { // Reseller has no permissions on this service
		$tplVars['SUBDOMAIN_LIMIT_BLOCK'] = '';
	} else {
		$tplVars['TR_SUBDOMAINS_LIMIT'] = tr('Subdomains limit<br /><span class="italic">(-1 disabled, 0 unlimited)</span>', true);
		$tplVars['SUBDOMAIN_LIMIT'] = tohtml($data['domain_subd_limit']);
		$tplVars['TR_CUSTOMER_SUBDOMAINS_COMSUPTION'] =  ($data['fallback_domain_subd_limit'] != -1) ? tohtml($data['nbSubdomains']) . ' / ' . (($data['fallback_domain_subd_limit'] != 0) ? tohtml($data['fallback_domain_subd_limit']) : tr('Unlimited')) : tr('Disabled');
		$tplVars['TR_RESELLER_SUBDOMAINS_COMSUPTION'] = tohtml($data['current_sub_cnt']) . ' / ' . (($data['max_sub_cnt'] != 0) ? tohtml($data['max_sub_cnt']) : tr('Unlimited'));
	}

	// Domain aliasses limit
	if ($data['max_als_cnt'] == -1) { // Reseller has no permissions on this service
		$tpl->assign('DOMAIN_ALIASES_LIMIT_BLOCK', '');
	} else {
		$tplVars['TR_ALIASSES_LIMIT'] = tr('Domain aliases limit<br /><span class="italic">(-1 disabled, 0 unlimited)</span>', true);
		$tplVars['DOMAIN_ALIASSES_LIMIT'] = tohtml($data['domain_alias_limit']);
		$tplVars['TR_CUSTOMER_DOMAIN_ALIASSES_COMSUPTION'] = ($data['fallback_domain_alias_limit'] != -1) ? tohtml($data['nbAliasses']) . ' / ' . (($data['fallback_domain_alias_limit'] != 0) ? tohtml($data['fallback_domain_alias_limit']) : tr('Unlimited')) : tr('Disabled');
		$tplVars['TR_RESELLER_DOMAIN_ALIASSES_COMSUPTION'] = tohtml($data['current_als_cnt']) . ' / ' . (($data['max_als_cnt'] != 0) ? tohtml($data['max_als_cnt']) : tr('Unlimited'));
	}

	// Mail accounts limit
	if ($data['max_mail_cnt'] == -1) { // Reseller has no permissions on this service
		$tplVars['MAIL_ACCOUNTS_LIMIT_BLOCK'] = '';
	} else {
		$tplVars['TR_MAIL_ACCOUNTS_LIMIT'] = tr('Mail accounts limit <br /><span class="italic">(-1 disabled, 0 unlimited)</span>', true);
		$tplVars['MAIL_ACCOUNTS_LIMIT'] = tohtml($data['domain_mailacc_limit']);
		$tplVars['TR_CUSTOMER_MAIL_ACCOUNTS_COMSUPTION'] = ($data['fallback_domain_mailacc_limit'] != -1) ? tohtml($data['nbMailAccounts']) . ' / ' . (($data['fallback_domain_mailacc_limit'] != 0) ? tohtml($data['fallback_domain_mailacc_limit']) : tr('Unlimited')): tr('Disabled');
		$tplVars['TR_RESELLER_MAIL_ACCOUNTS_COMSUPTION'] = tohtml($data['current_mail_cnt']) . ' / ' . (($data['max_mail_cnt'] != 0) ? tohtml($data['max_mail_cnt']) : tr('Unlimited'));
	}

	// Ftp accounts limit
	if ($data['max_ftp_cnt'] == -1) { // Reseller has no permissions on this service
		$tplVars['FTP_ACCOUNTS_LIMIT_BLOCK'] = '';
	} else {
		$tplVars['TR_FTP_ACCOUNTS_LIMIT'] = tr('FTP accounts limit <br /><span class="italic">(-1 disabled, 0 unlimited)</span>', true);
		$tplVars['FTP_ACCOUNTS_LIMIT'] = tohtml($data['domain_ftpacc_limit']);
		$tplVars['TR_CUSTOMER_FTP_ACCOUNTS_COMSUPTION'] = ($data['fallback_domain_ftpacc_limit'] != -1) ? tohtml($data['nbFtpAccounts']) . ' / ' . (($data['fallback_domain_ftpacc_limit'] != 0) ? tohtml($data['fallback_domain_ftpacc_limit']) : tr('Unlimited')) : tr('Disabled');
		$tplVars['TR_RESELLER_FTP_ACCOUNTS_COMSUPTION'] = tohtml($data['current_ftp_cnt']) . ' / ' . (($data['max_ftp_cnt'] != 0) ? tohtml($data['max_ftp_cnt']) : tr('Unlimited'));
	}

	// SQL Database - Sql Users limits
	if ($data['max_sql_db_cnt'] == -1 || $data['max_sql_user_cnt'] == -1) { // Reseller has no permissions on this service
		$tplVars['SQL_DB_AND_USERS_LIMIT_BLOCK'] = '';
	} else {
		$tplVars['TR_SQL_DATABASES_LIMIT'] = tr('SQL databases limit <br /><span class="italic">(-1 disabled, 0 unlimited)</span>', true);
		$tplVars['SQL_DATABASES_LIMIT'] = tohtml($data['domain_sqld_limit']);
		$tplVars['TR_CUSTOMER_SQL_DATABASES_COMSUPTION'] = ($data['fallback_domain_sqld_limit'] != -1) ? tohtml($data['nbSqlDatabases']) . ' / ' .(($data['fallback_domain_sqld_limit'] != 0) ? tohtml($data['fallback_domain_sqld_limit']) : tr('Unlimited')) : tr('Disabled');
		$tplVars['TR_RESELLER_SQL_DATABASES_COMSUPTION'] = tohtml($data['current_sql_db_cnt']) . ' / ' . (($data['max_sql_db_cnt'] != 0) ? tohtml($data['max_sql_db_cnt']) : tr('Unlimited'));

		$tplVars['TR_SQL_USERS_LIMIT'] = tr('SQL users limit <br /><span class="italic">(-1 disabled, 0 unlimited)</span>', true);
		$tplVars['SQL_USERS_LIMIT'] = tohtml($data['domain_sqlu_limit']);
		$tplVars['TR_CUSTOMER_SQL_USERS_COMSUPTION'] = ($data['fallback_domain_sqlu_limit'] != -1) ? tohtml($data['nbSqlUsers']) . ' / ' . (($data['fallback_domain_sqlu_limit'] != 0) ? tohtml($data['fallback_domain_sqlu_limit']) : tr('Unlimited')) : tr('Disabled');
		$tplVars['TR_RESELLER_SQL_USERS_COMSUPTION'] = tohtml($data['current_sql_user_cnt']) . ' / ' . (($data['max_sql_user_cnt'] != 0) ? tohtml($data['max_sql_user_cnt']) : tr('Unlimited'));
	}

	// Traffic limit
	$tplVars['TR_TRAFFIC_LIMIT'] = tr('Traffic limit [MiB] <br /><span class="italic">(0 unlimited)</span>', true);
	$tplVars['TRAFFIC_LIMIT'] = tohtml($data['domain_traffic_limit']);

	$tplVars['TR_CUSTOMER_TRAFFIC_COMSUPTION'] = tohtml(bytesHuman($data['domainTraffic'], 'MiB')) . ' / ' .
												 (($data['fallback_domain_traffic_limit'] != 0) ? tohtml(bytesHuman($data['fallback_domain_traffic_limit'] * 1048576)) : tr('Unlimited'));

	$tplVars['TR_RESELLER_TRAFFIC_COMSUPTION'] = tohtml(bytesHuman($data['current_traff_amnt'] * 1048576))  . ' / ' .
												 (($data['max_traff_amnt'] != 0) ? tohtml(bytesHuman($data['max_traff_amnt'] * 1048576)) : tr('Unlimited'));

	// Disk space limit
	$tplVars['TR_DISK_LIMIT'] = tr('Disk space limit [MiB] <br /><span class="italic">(0 unlimited)</span>', true);
	$tplVars['DISK_LIMIT'] = tohtml($data['domain_disk_limit']);

	$tplVars['TR_CUSTOMER_DISKPACE_COMSUPTION'] = tohtml(bytesHuman($data['domain_disk_usage'], 'MiB')) . ' / ' .
												  (($data['fallback_domain_disk_limit'] != 0) ? tohtml(bytesHuman($data['fallback_domain_disk_limit'] * 1048576)) : tr('Unlimited'));

	$tplVars['TR_RESELLER_DISKPACE_COMSUPTION'] = tohtml(bytesHuman($data['current_disk_amnt'] * 1048576))  . ' / ' .
												  (($data['max_disk_amnt'] != 0) ? tohtml(bytesHuman($data['max_disk_amnt'] * 1048576)) : tr('Unlimited'));

	if(!empty($tplVars)) {
		$tpl->assign($tplVars);
	}
} // end _reseller_generateLimitsForm()

/**
 * Generates features form.
 *
 * Note: For now most block for the features are always show. That will change when
 * admin will be able to disable them for a specific reseller.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array $data Domain data
 * @return void
 */
function _reseller_generateFeaturesForm($tpl, &$data)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlSelected = $cfg->HTML_SELECTED;
	$htmlChecked = $cfg->HTML_CHECKED;

	$tplVars = array();

	$tplVars['TR_FEATURE'] = tr('Feature');
	$tplVars['TR_STATUS'] = tr('Status');

	// PHP support
	$tplVars['TR_PHP'] = tr('PHP');
	$tplVars['PHP_YES'] = ($data['domain_php'] == 'yes') ? $htmlSelected : '';
	$tplVars['PHP_NO'] = ($data['domain_php'] != 'yes') ? $htmlSelected : '';

	// PHP editor - begin
	if ($data['reseller_php_ini_system'] == 'no') {
		$tplVars['PHP_EDITOR_JS'] = '';
		$tplVars['PHP_EDITOR_BLOCK'] = '';
	} else {
		$phpEditor = iMSCP_PHPini::getInstance();

		$tplVars['TR_SETTINGS'] = tr('Settings');
		$tplVars['TR_PHP_EDITOR'] = tr('PHP Editor');
		$tplVars['TR_PHP_EDITOR_SETTINGS'] = tr('PHP Editor Settings');
		$tplVars['TR_PERMISSIONS'] = tr('Permissions');
		$tplVars['TR_DIRECTIVES_VALUES'] = tr('PHP directives values');
		$tplVars['TR_FIELDS_OK'] = tr('All fields seem to be valid.');
		$tplVars['TR_VALUE_ERROR'] = tr('Value for the PHP <strong>%%s</strong> directive must be between %%d and %%d.', true);
		$tplVars['TR_CLOSE'] = tr('Close');
		$tplVars['TR_MIB'] = tr('MiB');
		$tplVars['TR_SEC'] = tr('Sec.');

		$tplVars['PHP_EDITOR_YES'] = ($phpEditor->getClPermVal('phpiniSystem') == 'yes') ? $htmlSelected : '';
		$tplVars['PHP_EDITOR_NO'] = ($phpEditor->getClPermVal('phpiniSystem') == 'no') ? $htmlSelected : '';

		$permissionsBlock = false;

		if (!$phpEditor->checkRePerm('phpiniRegisterGlobals')) {
			$tplVars['PHP_EDITOR_REGISTER_GLOBALS_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_REGISTER_GLOBALS'] = tr('Can edit the PHP %s directive', true, '<span class="bold">register_globals</span>');
			$tplVars['REGISTER_GLOBALS_YES'] = ($phpEditor->getClPermVal('phpiniRegisterGlobals') == 'yes') ? $htmlChecked : '';
			$tplVars['REGISTER_GLOBALS_NO'] = ($phpEditor->getClPermVal('phpiniRegisterGlobals') == 'no') ? $htmlChecked : '';
			$permissionsBlock = true;
		}

		if (!$phpEditor->checkRePerm('phpiniAllowUrlFopen')) {
			$tplVars['PHP_EDITOR_ALLOW_URL_FOPEN_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_ALLOW_URL_FOPEN'] = tr('Can edit the PHP %s directive', true, '<span class="bold">allow_url_fopen</span>');
			$tplVars['ALLOW_URL_FOPEN_YES'] = ($phpEditor->getClPermVal('phpiniAllowUrlFopen') == 'yes') ? $htmlChecked : '';
			$tplVars['ALLOW_URL_FOPEN_NO'] = ($phpEditor->getClPermVal('phpiniAllowUrlFopen') == 'no') ? $htmlChecked : '';
			$permissionsBlock = true;
		}

		if (!$phpEditor->checkRePerm('phpiniDisplayErrors')) {
			$tplVars['PHP_EDITOR_DISPLAY_ERRORS_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_DISPLAY_ERRORS'] = tr('Can edit the PHP %s directive', true, '<span class="bold">display_errors</span>');
			$tplVars['DISPLAY_ERRORS_YES'] = ($phpEditor->getClPermVal('phpiniDisplayErrors') == 'yes') ? $htmlChecked : '';
			$tplVars['DISPLAY_ERRORS_NO'] = ($phpEditor->getClPermVal('phpiniDisplayErrors') == 'no') ? $htmlChecked : '';
			$permissionsBlock = true;
		}

		if (PHP_SAPI == 'apache2handler' || !$phpEditor->checkRePerm('phpiniDisableFunctions')) {
			$tplVars['PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK'] = '';
		} else {
			$tplVars['TR_CAN_EDIT_DISABLE_FUNCTIONS'] = tr('Can edit the PHP %s directive', true, '<span class="bold">disable_functions</span>');
			$tplVars['DISABLE_FUNCTIONS_YES'] = ($phpEditor->getClPermVal('phpiniDisableFunctions') == 'yes') ? $htmlChecked : '';
			$tplVars['DISABLE_FUNCTIONS_NO'] = ($phpEditor->getClPermVal('phpiniDisableFunctions') == 'no') ? $htmlChecked : '';
			$tplVars['TR_ONLY_EXEC'] = tr('Only exec');
			$tplVars['DISABLE_FUNCTIONS_EXEC'] = ($phpEditor->getClPermVal('phpiniDisableFunctions') == 'exec') ? $htmlChecked : '';
			$permissionsBlock = true;
		}

		if (!$permissionsBlock) {
			$tplVars['PHP_EDITOR_PERMISSIONS_BLOCK'] = '';
		}

		$tplVars['TR_PHP_POST_MAX_SIZE_DIRECTIVE'] = tr('PHP %s directive', true, '<span class="bold">post_max_size</span>');
		$tplVars['POST_MAX_SIZE'] = tohtml($phpEditor->getDataVal('phpiniPostMaxSize'));

		$tplVars['PHP_UPLOAD_MAX_FILEZISE_DIRECTIVE'] = tr('PHP %s directive', true, '<span class="bold">upload_max_filezize</span>');
		$tplVars['UPLOAD_MAX_FILESIZE'] = tohtml($phpEditor->getDataVal('phpiniUploadMaxFileSize'));

		$tplVars['TR_PHP_MAX_EXECUTION_TIME_DIRECTIVE'] = tr('PHP %s directive', true, '<span class="bold">max_execution_time</span>');
		$tplVars['MAX_EXECUTION_TIME'] = tohtml($phpEditor->getDataVal('phpiniMaxExecutionTime'));

		$tplVars['TR_PHP_MAX_INPUT_TIME_DIRECTIVE'] = tr('PHP %s directive', true, '<span class="bold">max_input_time</span>');
		$tplVars['MAX_INPUT_TIME'] = tohtml($phpEditor->getDataVal('phpiniMaxInputTime'));

		$tplVars['TR_PHP_MEMORY_LIMIT_DIRECTIVE'] = tr('PHP %s directive', true, '<span class="bold">memory_limit</span>');
		$tplVars['MEMORY_LIMIT'] = tohtml($phpEditor->getDataVal('phpiniMemoryLimit'));


		// We make those values available for client side validation
		$tplVars['PHP_DIRECTIVES_RESELLER_MAX_VALUES'] = json_encode(
			array(
				'post_max_size' => $phpEditor->getRePermVal('phpiniPostMaxSize'),
				'upload_max_filezize' => $phpEditor->getRePermVal('phpiniUploadMaxFileSize'),
				'max_execution_time' => $phpEditor->getRePermVal('phpiniMaxExecutionTime'),
				'max_input_time' => $phpEditor->getRePermVal('phpiniMaxInputTime'),
				'memory_limit' => $phpEditor->getRePermVal('phpiniMemoryLimit')
			));
	}
	// PHP editor - end

	// CGI support
	$tplVars['TR_CGI'] = tr('CGI');
	$tplVars['CGI_YES'] = ($data['domain_cgi'] == 'yes') ? $htmlSelected : '';
	$tplVars['CGI_NO'] = ($data['domain_cgi'] != 'yes') ? $htmlSelected : '';

	// Custom DNS records
	$tplVars['TR_DNS'] = tr('Custom DNS records');
	$tplVars['DNS_YES'] = ($data['domain_dns'] == 'yes') ? $htmlSelected : '';
	$tplVars['DNS_NO'] = ($data['domain_dns'] != 'yes') ? $htmlSelected : '';

	// APS support
	if($data['software_allowed'] == 'no') {
		$tplVars['APS_BLOCK'] =  '';
	} else {
		$tplVars['TR_APS'] = tr('Softwares installer');
		$tplVars['APS_YES'] = ($data['domain_software_allowed'] == 'yes') ? $htmlSelected : '';
		$tplVars['APS_NO'] = ($data['domain_software_allowed'] != 'yes') ? $htmlSelected : '';
	}

	if ($cfg->BACKUP_DOMAINS == 'yes') {
		// Backup support
		$tplVars['TR_BACKUP'] = tr('Backup');
		$tplVars['TR_BACKUP_DOMAIN'] = tr('Domain');
		$tplVars['BACKUP_DOMAIN'] = ($data['allowbackup'] == 'dmn') ? $htmlSelected : '';
		$tplVars['TR_BACKUP_SQL'] = tr('Sql');
		$tplVars['BACKUP_SQL'] = ($data['allowbackup'] == 'sql') ? $htmlSelected : '';
		$tplVars['TR_BACKUP_FULL'] = tr('Full');
		$tplVars['BACKUP_FULL'] = ($data['allowbackup'] == 'full') ? $htmlSelected : '';
		$tplVars['TR_BACKUP_NO'] = tr('No');
		$tplVars['BACKUP_NO'] = ($data['allowbackup'] == 'no') ? $htmlSelected : '';
	} else {
		$tplVars['BACKUP_BLOCK'] = '';
	}

	// Shared strings
	$tplVars['TR_YES'] = tr('Yes');
	$tplVars['TR_NO'] = tr('No');

	$tpl->assign($tplVars);
}

/**
 * Check and updates domain data.
 *
 * @param int $domainId Domain unique identifier
 * @param bool $recoveryMode
 * @return bool TRUE on success, FALSE otherwise
 */
function reseller_checkAndUpdateData($domainId, $recoveryMode = false)
{
	/** @var $db iMSCP_Database */
	$db = iMSCP_Registry::get('db');

	$errFieldsStack = array();

	try {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		// Start transaction
		$db->beginTransaction();

		// Getting domain data
		$data =& reseller_getData($domainId, true, $recoveryMode);

		// Check for expires date
		if ($data['domain_never_expires'] == 'off') {
			if (!preg_match('%^\d{2}/\d{2}/\d{4}$%', $data['domain_expires']) ||
				($timestamp = strtotime($data['domain_expires'])) === false
			) {
				$data['domain_expires_ok'] = false;
				set_page_message(tr('Wrong syntax for new expire date.'), 'error');
				$errFieldsStack[] = 'domain_expires';
			} elseif ($timestamp != 0 && $timestamp <= time()) {
				$data['domain_expires'] = $timestamp;
				set_page_message(tr('You cannot set expire date in past.'), 'error');
				$errFieldsStack[] = 'domain_expires';
			} else {
				$data['domain_expires'] = $timestamp;
			}
		} else {
			$data['domain_expires'] = 0;
		}

		// Check for the subdomains limit
		if ($data["fallback_domain_subd_limit"] != -1) {
			if (!imscp_limit_check($data['domain_subd_limit'])) {
				set_page_message(tr('Wrong syntax for the %s limit.', tr('subdomains')), 'error');
				$errFieldsStack[] = 'domain_subd_limit';
			} elseif (!_reseller_isValidServiceLimit($data['domain_subd_limit'], $data['nbSubdomains'],
													 $data["fallback_domain_subd_limit"], $data['current_sub_cnt'],
													 $data['max_sub_cnt'], ($data['nbSubdomains'] > 1) ? tr('subdomains') : tr('subdomain'))
			) {
				$errFieldsStack[] = 'domain_subd_limit';
			}
		}

		// Check for the domain aliasses limit
		if ($data["fallback_domain_alias_limit"] != -1) {
			if (!imscp_limit_check($data['domain_alias_limit'])) {
				set_page_message(tr('Wrong syntax for the %s limit.', tr('domain aliasses')), 'error');
				$errFieldsStack[] = 'domain_alias_limit';
			} elseif (!_reseller_isValidServiceLimit($data['domain_alias_limit'], $data['nbAliasses'],
													 $data["fallback_domain_alias_limit"], $data['current_als_cnt'],
													 $data['max_als_cnt'], ($data['nbAliasses'] > 1) ? tr('domain aliasses') : tr('domain alias'))
			) {
				$errFieldsStack[] = 'domain_alias_limit';
			}
		}

		// Check for the mail accounts limit
		if ($data["fallback_domain_mailacc_limit"] != -1) {
			if (!imscp_limit_check($data['domain_mailacc_limit'])) {
				set_page_message(tr('Wrong syntax for the %s limit.', tr('mail accounts')), 'error');
				$errFieldsStack[] = 'domain_mailacc_limit';
			} elseif (!_reseller_isValidServiceLimit($data['domain_mailacc_limit'], $data['nbMailAccounts'],
													 $data["fallback_domain_mailacc_limit"], $data['current_mail_cnt'],
													 $data['max_mail_cnt'], ($data["nbMailAccounts"] > 1) ? tr('mail accounts') : tr('mail account'))
			) {
				$errFieldsStack[] = 'domain_mailacc_limit';
			}
		}

		// Check for the Ftp accounts limit
		if ($data["fallback_domain_ftpacc_limit"] != -1) {
			if (!imscp_limit_check($data['domain_ftpacc_limit'])) {
				set_page_message(tr('Wrong syntax for the %s limit.', tr('Ftp accounts')), 'error');
				$errFieldsStack[] = 'domain_ftpacc_limit';
			} elseif (!_reseller_isValidServiceLimit($data['domain_ftpacc_limit'], $data['nbFtpAccounts'],
													 $data["fallback_domain_ftpacc_limit"], $data['current_ftp_cnt'],
													 $data['max_ftp_cnt'], ($data['nbFtpAccounts'] > 1) ? tr('Ftp accounts') : tr('Ftp account'))
			) {
				$errFieldsStack[] = 'domain_ftpacc_limit';
			}
		}

		// Check for the Sql databases limit
		if ($data["fallback_domain_sqld_limit"] != -1) {
			if (!imscp_limit_check($data['domain_sqld_limit'])) {
				set_page_message(tr('Wrong syntax for the %s limit.', tr('Sql databases')), 'error');
				$errFieldsStack[] = 'domain_sqld_limit';
			} elseif (!_reseller_isValidServiceLimit($data['domain_sqld_limit'], $data['nbSqlDatabases'],
													 $data["fallback_domain_sqld_limit"], $data['current_sql_db_cnt'],
													 $data['max_sql_db_cnt'], ($data['nbSqlDatabases'] > 1 ) ? tr('Sql databases'): tr('Sql database'))
			) {
				$errFieldsStack[] = 'domain_sqld_limit';
			}
		}

		// Check for the Sql users limit
		if ($data["fallback_domain_sqlu_limit"] != -1) {
			if (!imscp_limit_check($data['domain_sqlu_limit'])) {
				set_page_message(tr('Wrong syntax for the %s limit.', tr('Sql users')), 'error');
				$errFieldsStack[] = 'domain_sqlu_limit';
			} elseif (!_reseller_isValidServiceLimit($data['domain_sqlu_limit'], $data['nbSqlUsers'],
													 $data["fallback_domain_sqlu_limit"], $data['current_sql_user_cnt'],
													 $data['max_sql_user_cnt'], ($data['nbSqlUsers'] > 1) ?  tr('Sql users') : tr('Sql user'))
			) {
				$errFieldsStack[] = 'domain_sqlu_limit';
			}
		}

		// Check for the traffic limit
		if (!imscp_limit_check($data['domain_traffic_limit'], null)) {
			set_page_message(tr('Wrong syntax for the %s limit.', tr('traffic')), 'error');
			$errFieldsStack[] = 'domain_traffic_limit';
		} elseif (!_reseller_isValidServiceLimit($data['domain_traffic_limit'], $data['domainTraffic'] / 1048576,
												 $data["fallback_domain_traffic_limit"], $data['current_traff_amnt'],
												 $data['max_traff_amnt'], tr('traffic'))
		) {
			$errFieldsStack[] = 'domain_traffic_limit';
		}

		// Check for the disk space limit
		if (!imscp_limit_check($data['domain_disk_limit'], null)) {
			set_page_message(tr('Wrong syntax for the %s limit.', tr('disk space')), 'error');
			$errFieldsStack[] = 'domain_disk_limit';
		} elseif (!_reseller_isValidServiceLimit($data['domain_disk_limit'], $data['domain_disk_usage'] / 1048576,
												 $data["fallback_domain_disk_limit"], $data['current_disk_amnt'],
												 $data['max_disk_amnt'], tr('disk space'))
		) {
			$errFieldsStack[] = 'domain_disk_limit';
		}

		// Check for PHP support (we are safe here)
		$data['domain_php'] = (in_array($data['domain_php'], array('no', 'yes')))
			? $data['domain_php'] : $data['fallback_domain_php'];

		// Check for PHP editor values - Begin
		$phpEditor = iMSCP_PHPini::getInstance();

		// Needed to check if something changed (see below)
		$phpEditorOld = array_merge($phpEditor->getData(), $phpEditor->getClPerm());

		if ($data['domain_php'] == 'yes' && $phpEditor->checkRePerm('phpiniSystem')
			&& isset($_POST['phpiniSystem'])
		) {
			$phpEditor->setClPerm('phpiniSystem', clean_input($_POST['phpiniSystem']));

			if ($phpEditor->getClPermVal('phpiniSystem') == 'yes') {
				if ($phpEditor->checkRePerm('phpiniRegisterGlobals') && isset($_POST['phpini_perm_register_globals'])) {
					$phpEditor->setClPerm('phpiniRegisterGlobals', clean_input($_POST['phpini_perm_register_globals']));
				}

				if ($phpEditor->checkRePerm('phpiniAllowUrlFopen') && isset($_POST['phpini_perm_allow_url_fopen'])) {
					$phpEditor->setClPerm('phpiniAllowUrlFopen', clean_input($_POST['phpini_perm_allow_url_fopen']));
				}

				if ($phpEditor->checkRePerm('phpiniDisplayErrors') && isset($_POST['phpini_perm_display_errors'])) {
					$phpEditor->setClPerm('phpiniDisplayErrors', clean_input($_POST['phpini_perm_display_errors']));
				}

				if ($phpEditor->checkRePerm('phpiniDisplayErrors') && isset($_POST['phpini_al_error_reporting'])) {
					$phpEditor->setClPerm('phpiniErrorReporting', clean_input($_POST['phpini_al_error_reporting']));
				}

				if(PHP_SAPI != 'apache2handler') {
					if ($phpEditor->checkRePerm('phpiniDisableFunctions') && isset($_POST['phpini_perm_disable_functions'])) {
						if($phpEditor->getClPerm('phpiniDisableFunctions') != $_POST['phpini_perm_disable_functions']) {
							$phpEditor->setClPerm('phpiniDisableFunctions', clean_input($_POST['phpini_perm_disable_functions']));
							$phpEditor->setData('phpiniDisableFunctions', $phpEditor->getDataDefaultVal('phpiniDisableFunctions'));
						}
					}
				} else {
					$phpEditor->setClPerm('phpiniDisableFunctions', 'no');
					$phpEditor->setData('phpiniDisableFunctions', $phpEditor->getDataDefaultVal('phpiniDisableFunctions'));
				}

				if (isset($_POST['post_max_size']) && (!$phpEditor->setDataWithPermCheck('phpiniPostMaxSize', $_POST['post_max_size']))) {
					$phpEditor->setData('phpiniPostMaxSize', $_POST['post_max_size'], false);
				}

				if (isset($_POST['upload_max_filezize']) && (!$phpEditor->setDataWithPermCheck('phpiniUploadMaxFileSize', $_POST['upload_max_filezize']))) {
					$phpEditor->setData('phpiniUploadMaxFileSize', $_POST['upload_max_filezize'], false);
				}

				if (isset($_POST['max_execution_time']) && (!$phpEditor->setDataWithPermCheck('phpiniMaxExecutionTime', $_POST['max_execution_time']))) {
					$phpEditor->setData('phpiniMaxExecutionTime', $_POST['max_execution_time'], false);
				}

				if (isset($_POST['max_input_time']) && (!$phpEditor->setDataWithPermCheck('phpiniMaxInputTime', $_POST['max_input_time']))) {
					$phpEditor->setData('phpiniMaxInputTime', $_POST['max_input_time'], false);
				}

				if (isset($_POST['memory_limit']) && (!$phpEditor->setDataWithPermCheck('phpiniMemoryLimit', $_POST['memory_limit']))) {
					$phpEditor->setData('phpiniMemoryLimit', $_POST['memory_limit'], false);
				}

				if($phpEditor->flagValueError) {
					set_page_message(tr('Please, check the PHP Editor settings.'), 'error');
				}
			} else {
				// PHP Editor is disabled - back to the default values
				$phpEditor->loadDefaultData();
				$phpEditor->loadClDefaultPerm();
			}
		} else { // PHP is disabled or reseller has not longer permission on PHP Editor - back to the default value
			$phpEditor->loadDefaultData();
			$phpEditor->loadClDefaultPerm();
		}

		// Needed to check if something changed (see below)
		$phpEditorNew = array_merge($phpEditor->getData(), $phpEditor->getClPerm());
		// Check for PHP editor values - End

		// Check for CGI support (we are safe here)
		$data['domain_cgi'] = (in_array($data['domain_cgi'], array('no', 'yes')))
			? $data['domain_cgi'] : $data['fallback_domain_cgi'];

		// Check for custom DNS records support (we are safe here)
		$data['domain_dns'] = (in_array($data['domain_dns'], array('no', 'yes')))
			? $data['domain_dns'] : $data['fallback_domain_dns'];

		// Check for APS support (we are safe here)
		$data['domain_software_allowed'] = (in_array($data['domain_software_allowed'], array('no', 'yes')))
			? $data['domain_software_allowed'] : $data['fallback_domain_software_allowed'];

		// Check for backup support (we are safe here)
		$data['allowbackup'] = (in_array($data['allowbackup'], array('dmn', 'sql', 'full', 'no')))
			? $data['allowbackup'] : $data['fallback_allowbackup'];

		if (empty($errFieldsStack) && !Zend_Session::namespaceIsset('pageMessages')) { // Update process begin here
			$oldValues = array();
			$newValues = array();

			foreach ($data as $property => $value) {
				if (strpos($property, 'fallback_') !== false) {
					$property = substr($property, 9);
					$oldValues[$property] = $value;
					$newValues[$property] = $data[$property];
				}
			}

			// Whether or not we must send backend request
			$daemonRequest = false;

			// Nothing's been changed?
			if ($newValues == $oldValues && $phpEditorNew == $phpEditorOld) {
				set_page_message(tr("Nothing's been changed."), 'info');
				return true;
			}

			if ($phpEditorNew != $phpEditorOld) {
				if ($phpEditor->getClPermVal('phpiniSystem') == 'yes') {
					$phpEditor->saveCustomPHPiniIntoDb($domainId);
				} else {
					$query = "DELETE FROM `php_ini` WHERE `domain_id` = ?";
					exec_query($query, $domainId);
				}

				$daemonRequest = true;
			}

			// PHP or CGI was either enabled or disabled or PHP Settings were changed
			// We must update the vhosts files of all domain entities (dmn, sub, als, alssub)
			if ($daemonRequest || $data['domain_php'] != $data['fallback_domain_php'] ||
				$data['domain_cgi'] != $data['fallback_domain_cgi']
			) {
				if($data['domain_alias_limit'] != '-1') {
					$query = "
						UPDATE
							`domain_aliasses`
						SET
							`alias_status` = ?
						WHERE
							`domain_id` = ?
						AND
							`alias_status` != ?
					";
					exec_query($query, array($cfg->ITEM_CHANGE_STATUS, $domainId, $cfg->ITEM_ORDERED_STATUS));
				}

				if($data['domain_subd_limit'] != '-1') {
					$query = "UPDATE `subdomain` SET `subdomain_status` = ? WHERE `domain_id` = ?";
					exec_query($query, array($cfg->ITEM_CHANGE_STATUS, $domainId));

					$query = "
						UPDATE
							`subdomain_alias`
						SET
							`subdomain_alias_status` = ?
						WHERE
							`alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)
					";
					exec_query($query, array($cfg->ITEM_CHANGE_STATUS, $domainId));
				}

				$daemonRequest = true;
			}

			// Support for custom DNS records is now disabled - We must delete
			// any related entries in the database and update the DNS zone file
			// TODO Check for protected entries
			if ($data['domain_dns'] != $data['fallback_domain_dns'] &&
				$data['domain_dns'] == 'no'
			) {
				$query = 'DELETE FROM `domain_dns` WHERE `domain_id` = ?';
				exec_query($query, $domainId);

				$daemonRequest= true;
			}

			iMSCP_Events_Manager::getInstance()->dispatch(
				iMSCP_Events::onBeforeEditDomain, array('domainId' => $domainId)
			);

			// Update domain properties
			$query = "
				UPDATE
					`domain`
				SET
					`domain_expires` = ?, `domain_last_modified` = ?, `domain_mailacc_limit` = ?,
					`domain_ftpacc_limit` = ?, `domain_traffic_limit` = ?, `domain_sqld_limit` = ?,
					`domain_sqlu_limit` = ?, `domain_status` = ?, `domain_alias_limit` = ?,
					`domain_subd_limit` = ?, `domain_disk_limit` = ?, `domain_php` = ?, `domain_cgi` = ?,
					`allowbackup` = ?, `domain_dns` = ?,  `domain_software_allowed` = ?,
					`phpini_perm_system` = ?, `phpini_perm_register_globals` = ?,
					`phpini_perm_allow_url_fopen` = ?, `phpini_perm_display_errors` = ?,
					`phpini_perm_disable_functions` = ?
				WHERE
					`domain_id` = ?
			";
			exec_query($query, array(
									$data['domain_expires'], time(), $data['domain_mailacc_limit'],
									$data['domain_ftpacc_limit'], $data['domain_traffic_limit'],
									$data['domain_sqld_limit'], $data['domain_sqlu_limit'],
									($daemonRequest) ? $cfg->ITEM_CHANGE_STATUS : $cfg->ITEM_OK_STATUS,
									$data['domain_alias_limit'], $data['domain_subd_limit'],
									$data['domain_disk_limit'], $data['domain_php'], $data['domain_cgi'],
									$data['allowbackup'], $data['domain_dns'],
									$data['domain_software_allowed'], $phpEditor->getClPermVal('phpiniSystem'),
									$phpEditor->getClPermVal('phpiniRegisterGlobals'),
									$phpEditor->getClPermVal('phpiniAllowUrlFopen'),
									$phpEditor->getClPermVal('phpiniDisplayErrors'),
									$phpEditor->getClPermVal('phpiniDisableFunctions'),
									$domainId));

			iMSCP_Events_Manager::getInstance()->dispatch(
				iMSCP_Events::onAfterEditDomain, array('domainId' => $domainId)
			);

			// Update Ftp quota limit if needed
			if ($data['domain_disk_limit'] != $data['fallback_domain_disk_limit']) {
				$query = "
					REPLACE INTO `quotalimits` (
						`name`, `quota_type`, `per_session`, `limit_type`,
						`bytes_in_avail`, `bytes_out_avail`, `bytes_xfer_avail`,
						`files_in_avail`, `files_out_avail`, `files_xfer_avail`
					) VALUES (
						?, ?, ?, ?, ?, ?, ?, ?, ?, ?
					)
				";
				exec_query($query, array(
										$data['domain_name'], 'group', 'false', 'hard',
										$data['domain_disk_limit'] * 1048576, 0, 0, 0, 0, 0));
			}

			// Update reseller properties
			update_reseller_c_props($data['reseller_id']);

			$db->commit();

			if ($daemonRequest) {
				send_request();
				set_page_message(tr('Domain scheduled for update.'), 'success');
			} else {
				set_page_message(tr('Domain successfully updated.'), 'success');
			}

			$userLogged = isset($_SESSION['logged_from']) ? $_SESSION['logged_from'] : $_SESSION['user_logged'];
			write_log("Domain ". decode_idna($data['domain_name']) . " was updated by $userLogged", E_USER_NOTICE);

			return true;
		}
	} catch (iMSCP_Exception_Database $e) {
		$db->rollBack();

		if($e->getCode() == 40001) { // Deadlock error management
			if(isset($data)) { // $data is tested here only to avoid IDE warning about possible indefined variable
				if(reseller_checkAndUpdateData($domainId, true)) {
					set_page_message(tr('Domain data were modified by another person before your changes. The update process was successfully done but in recovery mode. We recommend you to check the result of it.'), 'warning');
					return true;
				} else {
					return false;
				}
			}
		} else {
			throw new iMSCP_Exception_Database($e->getMessage(),  $e->getQuery(), $e->getCode(), $e);
		}
	}

	if(!empty($errFieldsStack)) {
		iMSCP_Registry::set('errFieldsStack', $errFieldsStack);
	}

	return false;
} // end reseller_updateData()

/**
 * Validates a new service limit.
 *
 * @param int $newCustomerLimit New customer service limit
 * @param int $customerConsumption Customer consumption
 * @param int $customerLimit Limit for customer
 * @param int $resellerConsumption Reseller consumption
 * @param int $resellerLimit Limit for reseller
 * @param int $translatedServiceName Translation of service name
 * @return bool TRUE if new limit is valid, FALSE otherwise
 */
function _reseller_isValidServiceLimit($newCustomerLimit, $customerConsumption,
	$customerLimit, $resellerConsumption, $resellerLimit, $translatedServiceName
) {
	// Please, don't change test order.
	if(($resellerLimit == -1 || $resellerLimit > 0) && $newCustomerLimit == 0) {
		set_page_message(tr('The %s limit for this customer cannot be unlimited because your are limited for this service.', $translatedServiceName), 'error');
		return false;
	} elseif($newCustomerLimit == -1 && $customerConsumption > 0) {
		set_page_message(tr("The %s limit for this customer cannot be set to 'disabled' because he has already <strong>%d</strong> %s.", $translatedServiceName, $customerConsumption, $translatedServiceName), 'error');
		return false;
	} elseif($resellerLimit != 0 && $newCustomerLimit > ($resellerLimit - $resellerConsumption) + $customerLimit) {
		set_page_message(tr('The %s limit for this customer cannot be greater than <strong>%d</strong>, your calculated limit.', $translatedServiceName, ($resellerLimit - $resellerConsumption) + $customerLimit), 'error');
		return false;
	} elseif($newCustomerLimit != -1 && $newCustomerLimit != 0 && $newCustomerLimit < $customerConsumption) {
		set_page_message(tr('The %s limit for this customer cannot be lower than <strong>%d</strong>, the total of %s already used by him.', $translatedServiceName, round($customerConsumption), $translatedServiceName), 'error');
		return false;
	}

	return true;
}

/************************************************************************************
 * main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL != 'reseller') {
	redirectTo('users.php');
}

// Dispatches the request
if(!isset($_GET['edit_id'])) {
	set_page_message(tr('wrong request.'));
	redirectTo('users.php');
} else {
	$domainId = (int) $_GET['edit_id'];

	if(!empty($_POST) && reseller_checkAndUpdateData($domainId)) {
		redirectTo('users.php');
	}
}

// Getting domain data
$data =& reseller_getData($domainId);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/domain_edit.tpl',
		'page_message' => 'layout',
		'subdomain_limit_block' => 'page',
		'domain_aliasses_limit_block' => 'page',
		'mail_accounts_limit_block' => 'page',
		'ftp_accounts_limit_block' => 'page',
		'sql_db_and_users_limit_block' => 'page',
		'php_block' => 'page',
		'php_editor_js' => 'page',
		'php_editor_block' => 'php_block',
		'php_editor_permissions_block' => 'php_editor_block',
		'php_editor_register_globals_block' => 'php_editor_permissions_block',
		'php_editor_allow_url_fopen_block' => 'php_editor_permissions_block',
		'php_editor_display_errors_block' => 'php_editor_permissions_block',
		'php_editor_disable_functions_block' => 'php_editor_permissions_block',
		'php_editor_default_values_block' => 'php_directives_editor_block',
		'cgi_block' => 'page',
		'dns_block' => 'page',
		'aps_block' => 'page',
		'dns_block' => 'page',
		'backup_block' => 'page',
	)
);

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Domain / Edit'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_EDIT_DOMAIN' => tr('Edit domain'),
		 'EDIT_ID' => tohtml($domainId),
		 'TR_HELP' => tr('Help'),
		 'TR_DOMAIN_OVERVIEW' => tr('Domain overview'),
		 'TR_DOMAIN_NAME' => tr('Domain name'),
		 'DOMAIN_NAME' => tohtml(decode_idna($data['domain_name'])),
		 'TR_DOMAIN_EXPIRE_DATE' => tr('Domain expires date'),
		 'DOMAIN_EXPIRE_DATE' => ($data['fallback_domain_expires'] != 0) ? date($cfg->DATE_FORMAT, $data['fallback_domain_expires']) : tr('N/A'),
		 'TR_DOMAIN_NEW_EXPIRE_DATE' => tr('Domain new expires date'),
		 'TR_DOMAIN_EXPIRE_HELP' => tr("In case domain expires date is 'N/A', the expiration date will be set from today."),
		 'DOMAIN_NEW_EXPIRE_DATE' => tohtml(($data['domain_expires'] != 0) ? ($data['domain_expires_ok'] ? date('m/d/Y', $data['domain_expires'])  : $data['domain_expires']) : ''),
		 'DOMAIN_NEW_EXPIRE_DATE_DISABLED' => ($data['domain_never_expires'] == 'on') ? 'disabled="disabled"' : '',
		 'TR_DOMAIN_NEVER_EXPIRES' => tr('Never expires'),
		 'DOMAIN_NEVER_EXPIRES_CHECKED' => ($data['domain_never_expires'] == 'on') ? 'checked="checked"' : '',
		 'TR_DOMAIN_IP' => tr('Domain IP'),
		 'DOMAIN_IP' => tohtml($data['ip_number']),
		 'IP_DOMAIN' => ($data['ip_domain'] != null) ? '(' . tohtml(decode_idna($data['ip_domain'])) . ')' : '',
		 'TR_UPDATE' => tr('Update'),
		 'TR_CANCEL' => tr('Cancel'),
		 'ERR_FIELDS_STACK' => (iMSCP_Registry::isRegistered('errFieldsStack'))
			? json_encode(iMSCP_Registry::get('errFieldsStack')) : '[]'));

generateNavigation($tpl);
reseller_generateForm($tpl, $data);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
