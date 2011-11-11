<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage	client
 * @copyright   2010-2011 by i-MSCP team
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/************************************************************************************
 * script functions
 */

/**
 * Returns domain related data.
 *
 * @param int $domainId Domain unique identifier
 * @param bool $forUpdate Tell whether or not data are fetched for update
 * @param bool $recoveryMode
 * @return array reference to array of data
 */
function &admin_getData($domainId, $forUpdate = false, $recoveryMode = false)
{
	static $data = null;

	if(null == $data || $recoveryMode) {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		$statusOk = "$cfg->ITEM_OK_STATUS|$cfg->ITEM_DISABLED_STATUS";

		// Until we have not a jobs manager, we must do those checks (status)
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
		";
		$stmt = exec_query($query, array($statusOk, $statusOk, $statusOk, $domainId));

		// Check for domain existence and its status
		if($stmt->fields['domain_status'] == '') {
			set_page_message(tr('Domain not found.'), 'error');
			redirectTo('manage_users.php');
		} elseif(($stmt->fields['domain_status'] != $cfg->ITEM_OK_STATUS && $stmt->fields['domain_status'] != $cfg->ITEM_DISABLED_STATUS)
				 || $stmt->fields('statusNotOk') > 0
		) {
			set_page_message(tr("The domain or at least one of its entities has a different status than 'ok'."), 'error');
			redirectTo('manage_users.php');
		} elseif($stmt->fields['domain_status'] == $cfg->ITEM_DISABLED_STATUS) {
			set_page_message(tr('The domain is currently deactivated. The modification of some of its properties will result by a complete or partial reactivation of it.'), 'warning');
		}

		$bindParams = array();
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
				`t1`.`domain_software_allowed`, `t1`.`allowbackup`,

				-- domain reseller props
				`t2`.`reseller_id`, `t2`.`current_sub_cnt`, `t2`.`max_sub_cnt`,
				`t2`.`current_als_cnt`, `t2`.`max_als_cnt`, `t2`.`current_mail_cnt`,
				`t2`.`max_mail_cnt`, `t2`.`current_ftp_cnt`, `t2`.`max_ftp_cnt`,
				`t2`.`current_sql_db_cnt`, `t2`.`max_sql_db_cnt`, `t2`.`current_sql_user_cnt`,
				`t2`.`max_sql_user_cnt`, `t2`.`current_disk_amnt`, `t2`.`max_disk_amnt`,
				`t2`.`current_traff_amnt`, `t2`.`max_traff_amnt`, `t2`.`software_allowed`,

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
				`domain_aliasses` `t4` ON (`t1`.`domain_id` = `t4`.`domain_id`)
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

		if ($forUpdate) { // Post request
			foreach (
				array(
					'domain_subd_limit', 'domain_alias_limit', 'domain_mailacc_limit',
					'domain_ftpacc_limit', 'domain_sqld_limit', 'domain_sqlu_limit',
					'domain_traffic_limit', 'domain_disk_limit',
				) as $property
			) {
				if (array_key_exists($property, $_POST) && $data[$property] != -1) {
					$data[$property] = clean_input($_POST[$property]);
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

			$data['domain_software_allowed'] = isset($_POST['domain_software_allowed'])
				? clean_input($_POST['domain_software_allowed']) : $data['domain_software_allowed'];

			$data['allowbackup'] = isset($_POST['allowbackup'])
				? clean_input($_POST['allowbackup']) : $data['allowbackup'];
		}
	}

	return $data;
} // end admin_getData()

/**
 * Generate edit form.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param array &$data Domain related data
 * @return void
 */
function admin_generateForm($tpl, &$data)
{
	_admin_generateLimitsForm($tpl, $data);
	_admin_generateFeaturesForm($tpl, $data);
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
function _admin_generateLimitsForm($tpl, &$data)
{
	$tplVars = array();

	$tplVars['TR_DOMAIN_LIMITS'] = tr('Domain Limit');
	$tplVars['TR_MAX_LIMIT'] = tr('Limit value');
	$tplVars['TR_CUSTOMER_CONSUMPTION'] = tr('Customer current consumption');
	$tplVars['TR_RESELLER_CONSUMPTION'] = tr('Reseller current consumption');

	// Subdomains limit
	if ($data['max_sub_cnt'] == -1) { // Reseller has no permissions on this service
		$tplVars['SUBDOMAIN_LIMIT_BLOCK'] = '';
	} else {
		$tplVars['TR_SUBDOMAINS_LIMIT'] = tr('Subdomains limit<br /><i>(-1 disabled, 0 unlimited)</i>');
		$tplVars['SUBDOMAIN_LIMIT'] = tohtml($data['domain_subd_limit']);
		$tplVars['TR_CUSTOMER_SUBDOMAINS_COMSUPTION'] = tohtml($data['nbSubdomains']) . ' / ' . tohtml($data['fallback_domain_subd_limit']);
		$tplVars['TR_RESELLER_SUBDOMAINS_COMSUPTION'] = tohtml($data['current_sub_cnt']) . ' / ' . tohtml($data['max_sub_cnt']);
	}

	// Domain aliasses limit
	if ($data['max_als_cnt'] == -1) { // Reseller has no permissions on this service
		$tpl->assign('DOMAIN_ALIASSES_LIMIT_BLOCK', '');
	} else {
		$tplVars['TR_ALIASSES_LIMIT'] = tr('Domain aliases limit<br /><i>(-1 disabled, 0 unlimited)</i>');
		$tplVars['DOMAIN_ALIASSES_LIMIT'] = tohtml($data['domain_alias_limit']);
		$tplVars['TR_CUSTOMER_DOMAIN_ALIASSES_COMSUPTION'] = tohtml($data['nbAliasses']) . ' / ' . tohtml($data['fallback_domain_alias_limit']);
		$tplVars['TR_RESELLER_DOMAIN_ALIASSES_COMSUPTION'] = tohtml($data['current_als_cnt']) . ' / ' . tohtml($data['max_als_cnt']);
	}

	// Mail accounts limit
	if ($data['max_mail_cnt'] == -1) { // Reseller has no permissions on this service
		$tplVars['MAIL_ACCOUNTS_LIMIT_BLOCK'] = '';
	} else {
		$tplVars['TR_MAIL_ACCOUNTS_LIMIT'] = tr('Mail accounts limit <br /><i>(-1 disabled, 0 unlimited)</i>');
		$tplVars['MAIL_ACCOUNTS_LIMIT'] = tohtml($data['domain_mailacc_limit']);
		$tplVars['TR_CUSTOMER_MAIL_ACCOUNTS_COMSUPTION'] = tohtml($data['nbMailAccounts']) . ' / ' . tohtml($data['fallback_domain_mailacc_limit']);
		$tplVars['TR_RESELLER_MAIL_ACCOUNTS_COMSUPTION'] = tohtml($data['current_mail_cnt']) . ' / ' . tohtml($data['max_mail_cnt']);
	}

	// Ftp accounts limit
	if ($data['max_ftp_cnt'] == -1) { // Reseller has no permissions on this service
		$tplVars['FTP_ACCOUNTS_LIMIT_BLOCK'] = '';
	} else {
		$tplVars['TR_FTP_ACCOUNTS_LIMIT'] = tr('FTP accounts limit <br /><i>(-1 disabled, 0 unlimited)</i>');
		$tplVars['FTP_ACCOUNTS_LIMIT'] = tohtml($data['domain_ftpacc_limit']);
		$tplVars['TR_CUSTOMER_FTP_ACCOUNTS_COMSUPTION'] = tohtml($data['nbFtpAccounts']) . ' / ' . tohtml($data['fallback_domain_ftpacc_limit']);
		$tplVars['TR_RESELLER_FTP_ACCOUNTS_COMSUPTION'] = tohtml($data['current_ftp_cnt']) . ' / ' . tohtml($data['max_ftp_cnt']);
	}

	// SQL Database - Sql Users limits
	if ($data['max_sql_db_cnt'] == -1 || $data['max_sql_user_cnt'] == -1) { // Reseller has no permissions on this service
		$tplVars['SQL_BD_AND_USERS_LIMIT_BLOCK'] = '';
	} else {
		$tplVars['TR_SQL_DATABASES_LIMIT'] = tr('SQL databases limit <br /><i>(-1 disabled, 0 unlimited)</i>');
		$tplVars['SQL_DATABASES_LIMIT'] = tohtml($data['domain_sqld_limit']);
		$tplVars['TR_CUSTOMER_SQL_DATABASES_COMSUPTION'] = tohtml($data['nbSqlDatabases']) . ' / ' . tohtml($data['fallback_domain_sqld_limit']);
		$tplVars['TR_RESELLER_SQL_DATABASES_COMSUPTION'] = tohtml($data['current_sql_db_cnt']) . ' / ' . tohtml($data['max_sql_db_cnt']);

		$tplVars['TR_SQL_USERS_LIMIT'] = tr('SQL users limit <br /><i>(-1 disabled, 0 unlimited)</i>');
		$tplVars['SQL_USERS_LIMIT'] = tohtml($data['domain_sqlu_limit']);
		$tplVars['TR_CUSTOMER_SQL_USERS_COMSUPTION'] = tohtml($data['nbSqlUsers']) . ' / ' . tohtml($data['fallback_domain_sqlu_limit']);
		$tplVars['TR_RESELLER_SQL_USERS_COMSUPTION'] = tohtml($data['current_sql_user_cnt']) . ' / ' . tohtml($data['max_sql_user_cnt']);
	}

	// Traffic limit
	$tplVars['TR_TRAFFIC_LIMIT'] = tr('Traffic limit [MiB] <br /><i>(0 unlimited)</i>');
	$tplVars['TRAFFIC_LIMIT'] = tohtml($data['domain_traffic_limit']);

	$tplVars['TR_CUSTOMER_TRAFFIC_COMSUPTION'] = tohtml(numberBytesHuman($data['domainTraffic'], 'MiB')) . ' / ' .
												 tohtml(numberBytesHuman($data['fallback_domain_traffic_limit'] * 1048576));

	$tplVars['TR_RESELLER_TRAFFIC_COMSUPTION'] = tohtml(numberBytesHuman($data['current_traff_amnt'] * 1048576))  . ' / ' .
												 tohtml(numberBytesHuman($data['max_traff_amnt'] * 1048576));

	// Disk space limit
	$tplVars['TR_DISK_LIMIT'] = tr('Disk space limit [MiB] <br /><i>(0 unlimited)</i>');
	$tplVars['DISK_LIMIT'] = tohtml($data['domain_disk_limit']);

	$tplVars['TR_CUSTOMER_DISKPACE_COMSUPTION'] = tohtml(numberBytesHuman($data['domain_disk_usage'], 'MiB')) . ' / ' .
												  tohtml(numberBytesHuman($data['fallback_domain_disk_limit'] * 1048576));

	$tplVars['TR_RESELLER_DISKPACE_COMSUPTION'] = tohtml(numberBytesHuman($data['current_disk_amnt'] * 1048576))  . ' / ' .
												  tohtml(numberBytesHuman($data['max_disk_amnt'] * 1048576));

	if(!empty($tplVars)) {
		$tpl->assign($tplVars);
	}
} // end _admin_generateLimitsForm()

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
function _admin_generateFeaturesForm($tpl, &$data)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlSelected = $cfg->HTML_SELECTED;
	$tplVars = array();

	$tplVars['TR_FEATURES'] = tr('Features');

	// PHP support
	$tplVars['TR_PHP_SUPPORT'] = tr('PHP support');
	$tplVars['PHP_SUPPORT_YES'] = ($data['domain_php'] == 'yes') ? $htmlSelected : '';
	$tplVars['PHP_SUPPORT_NO'] = ($data['domain_php'] != 'yes') ? $htmlSelected : '';

	// CGI support
	$tplVars['TR_CGI_SUPPORT'] = tr('CGI support');
	$tplVars['CGI_SUPPORT_YES'] = ($data['domain_cgi'] == 'yes') ? $htmlSelected : '';
	$tplVars['CGI_SUPPORT_NO'] = ($data['domain_cgi'] != 'yes') ? $htmlSelected : '';

	// Custom DNS records
	$tplVars['TR_DNS_SUPPORT'] = tr('Custom DNS records support');
	$tplVars['DNS_SUPPORT_YES'] = ($data['domain_dns'] == 'yes') ? $htmlSelected : '';
	$tplVars['DNS_SUPPORT_NO'] = ($data['domain_dns'] != 'yes') ? $htmlSelected : '';

	// APS support
	if($data['software_allowed'] == 'no') {
		$tpl->assign('APS_SUPPORT_BLOCK', '');
	} else {
		$tplVars['TR_APS_SUPPORT'] = tr('Software installer support');
		$tplVars['APS_SUPPORT_YES'] = ($data['domain_software_allowed'] == 'yes') ? $htmlSelected : '';
		$tplVars['APS_SUPPORT_NO'] = ($data['domain_software_allowed'] != 'yes') ? $htmlSelected : '';
	}

	// Backup support
	$tplVars['TR_BACKUP_SUPPORT'] = tr('Backup support');
	$tplVars['TR_BACKUP_DOMAIN'] = tr('Domain');
	$tplVars['BACKUP_DOMAIN'] = ($data['allowbackup'] == 'dmn') ? $htmlSelected : '';
	$tplVars['TR_BACKUP_SQL'] = tr('Sql');
	$tplVars['BACKUP_SQL'] = ($data['allowbackup'] == 'sql') ? $htmlSelected : '';
	$tplVars['TR_BACKUP_FULL'] = tr('Full');
	$tplVars['BACKUP_FULL'] = ($data['allowbackup'] == 'full') ? $htmlSelected : '';
	$tplVars['TR_BACKUP_NO'] = tr('No');
	$tplVars['BACKUP_NO'] = ($data['allowbackup'] == 'no') ? $htmlSelected : '';

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
function admin_checkAndUpdateData($domainId, $recoveryMode = false)
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
		$data =& admin_getData($domainId, true, $recoveryMode);

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
			} elseif (!_admin_isValidServiceLimit($data['domain_subd_limit'], $data['nbSubdomains'],
												  $data["fallback_domain_subd_limit"], $data['current_sub_cnt'],
												  $data['max_sub_cnt'], tr('subdomains'))
			) {
				$errFieldsStack[] = 'domain_subd_limit';
			}
		}

		// Check for the domain aliasses limit
		if ($data["fallback_domain_alias_limit"] != -1) {
			if (!imscp_limit_check($data['domain_alias_limit'])) {
				set_page_message(tr('Wrong syntax for the %s limit.', tr('domain aliasses')), 'error');
				$errFieldsStack[] = 'domain_alias_limit';
			} elseif (!_admin_isValidServiceLimit($data['domain_alias_limit'], $data['nbAliasses'],
												  $data["fallback_domain_alias_limit"], $data['current_als_cnt'],
												  $data['max_als_cnt'], tr('domain aliasses'))
			) {
				$errFieldsStack[] = 'domain_alias_limit';
			}
		}

		// Check for the mail accounts limit
		if ($data["fallback_domain_mailacc_limit"] != -1) {
			if (!imscp_limit_check($data['domain_mailacc_limit'])) {
				set_page_message(tr('Wrong syntax for the %s limit.', tr('mail accounts')), 'error');
				$errFieldsStack[] = 'domain_mailacc_limit';
			} elseif (!_admin_isValidServiceLimit($data['domain_mailacc_limit'], $data['nbMailAccounts'],
												  $data["fallback_domain_mailacc_limit"], $data['current_mail_cnt'],
												  $data['max_mail_cnt'], tr('mail accounts'))
			) {
				$errFieldsStack[] = 'domain_mailacc_limit';
			}
		}

		// Check for the Ftp accounts limit
		if ($data["fallback_domain_ftpacc_limit"] != -1) {
			if (!imscp_limit_check($data['domain_ftpacc_limit'])) {
				set_page_message(tr('Wrong syntax for the %s limit.', tr('Ftp accounts')), 'error');
				$errFieldsStack[] = 'domain_ftpacc_limit';
			} elseif (!_admin_isValidServiceLimit($data['domain_ftpacc_limit'], $data['nbFtpAccounts'],
												  $data["fallback_domain_ftpacc_limit"], $data['current_ftp_cnt'],
												  $data['max_ftp_cnt'], tr('Ftp accounts'))
			) {
				$errFieldsStack[] = 'domain_ftpacc_limit';
			}
		}

		// Check for the Sql databases limit
		if ($data["fallback_domain_sqld_limit"] != -1) {
			if (!imscp_limit_check($data['domain_sqld_limit'])) {
				set_page_message(tr('Wrong syntax for the %s limit.', tr('Sql databases')), 'error');
				$errFieldsStack[] = 'domain_sqld_limit';
			} elseif (!_admin_isValidServiceLimit($data['domain_sqld_limit'], $data['nbSqlDatabases'],
												  $data["fallback_domain_sqld_limit"], $data['current_sql_db_cnt'],
												  $data['max_sql_db_cnt'], tr('Sql databases'))
			) {
				$errFieldsStack[] = 'domain_sqld_limit';
			}
		}

		// Check for the Sql users limit
		if ($data["fallback_domain_sqlu_limit"] != -1) {
			if (!imscp_limit_check($data['domain_sqlu_limit'])) {
				set_page_message(tr('Wrong syntax for the %s limit.', tr('Sql users')), 'error');
				$errFieldsStack[] = 'domain_sqlu_limit';
			} elseif (!_admin_isValidServiceLimit($data['domain_sqlu_limit'], $data['nbSqlUsers'],
												  $data["fallback_domain_sqlu_limit"], $data['current_sql_user_cnt'],
												  $data['max_sql_user_cnt'], tr('Sql users'))
			) {
				$errFieldsStack[] = 'domain_sqlu_limit';
			}
		}

		// Check for the traffic limit
		if (!imscp_limit_check($data['domain_traffic_limit'], null)) {
			set_page_message(tr('Wrong syntax for the %s limit.', tr('traffic')), 'error');
			$errFieldsStack[] = 'domain_traffic_limit';
		} elseif (!_admin_isValidServiceLimit($data['domain_traffic_limit'], $data['domainTraffic'] / 1048576,
											  $data["fallback_domain_traffic_limit"], $data['current_traff_amnt'],
											  $data['max_traff_amnt'], tr('traffic'))
		) {
			$errFieldsStack[] = 'domain_traffic_limit';
		}

		// Check for the disk space limit
		if (!imscp_limit_check($data['domain_disk_limit'], null)) {
			set_page_message(tr('Wrong syntax for the %s limit.', tr('disk space')), 'error');
			$errFieldsStack[] = 'domain_disk_limit';
		} elseif (!_admin_isValidServiceLimit($data['domain_disk_limit'], $data['domain_disk_usage'] / 1048576,
											  $data["fallback_domain_disk_limit"], $data['current_disk_amnt'],
											  $data['max_disk_amnt'], tr('disk space'))
		) {
			$errFieldsStack[] = 'domain_disk_limit';
		}

		// Check for PHP support (we are safe here)
		$data['domain_php'] = (in_array($data['domain_php'], array('no', 'yes')))
			? $data['domain_php'] : $data['fallback_domain_php'];

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

		if (empty($errFieldsStack)) { // Update process begin here

			$oldValues = array();
			$newValues = array();

			foreach ($data as $property => $value) {
				if (strpos($property, 'fallback_') !== false) {
					$oldValues[$property] = $value;
					$property = substr($property, 9);
					$newValues[$property] = $data[$property];
				}
			}

			// Nothing's been changed?
			if (array_values($newValues) == array_values($oldValues)) {
				set_page_message(tr("Nothing's been changed."), 'info');
				return true;
			}

			$daemonRequest = false;

			// Support for custom DNS records is now disabled - We must delete
			// any related entries in the database and update the DNS zone file
			// TODO What about protected entries?
			if ($data['domain_dns'] != $data['fallback_domain_dns'] && $data['domain_dns'] == 'no') {
				$query = 'DELETE FROM `domain_dns` WHERE `domain_id` = ?';
				exec_query($query, $domainId);
				$daemonRequest = true;
			}

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

			// Support for PHP or CGI was either enabled or disabled - We must
			// update the vhosts files of all domain entities (dmn, sub, als, alssub)
			if ($data['domain_php'] != $data['fallback_domain_php'] ||
				$data['domain_cgi'] != $data['fallback_domain_cgi']
			) {
				$daemonRequest = true;

				$query = "UPDATE `subdomain` SET `subdomain_status` = ? WHERE `domain_id` = ?";
				exec_query($query, array($cfg->ITEM_CHANGE_STATUS, $domainId));

				$query = "UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `domain_id` = ?";
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

			// Update domain properties
			$query = "
				UPDATE
					`domain`
				SET
					`domain_expires` = ?, `domain_last_modified` = ?, `domain_mailacc_limit` = ?,
					`domain_ftpacc_limit` = ?, `domain_traffic_limit` = ?, `domain_sqld_limit` = ?,
					`domain_sqlu_limit` = ?, `domain_status` = ?, `domain_alias_limit` = ?,
					`domain_subd_limit` = ?, `domain_disk_limit` = ?, `domain_php` = ?,
					`domain_cgi` = ?, `domain_dns` = ?, `domain_software_allowed` = ?,
					`allowbackup` = ?
				WHERE
					`domain_id` = ?
			";
			exec_query($query, array(
									$data['domain_expires'], time(), $data['domain_mailacc_limit'],
									$data['domain_ftpacc_limit'], $data['domain_traffic_limit'], $data['domain_sqld_limit'],
									$data['domain_sqlu_limit'], ($daemonRequest) ? $cfg->ITEM_CHANGE_STATUS : $cfg->ITEM_OK_STATUS,
									$data['domain_alias_limit'], $data['domain_subd_limit'], $data['domain_disk_limit'],
									$data['domain_php'], $data['domain_cgi'], $data['domain_dns'], $data['domain_software_allowed'],
									$data['allowbackup'], $domainId));

			// Update reseller properties
			update_reseller_c_props($data['reseller_id']);

			$db->commit();

			if ($daemonRequest) {
				send_request();
				set_page_message(tr('Domain scheduled for update.'), 'success');
				return true;
			} else {
				set_page_message(tr('Domain successfully updated.'), 'success');
			}

			write_log("Domain ". decode_idna($data['domain_name']) . " was updated by {$_SESSION['user_logged']}", E_USER_NOTICE);

			return true;
		}
	} catch (iMSCP_Exception_Database $e) {
		$db->rollBack();

		if($e->getCode() == 40001) { // Deadlock error management
			if(isset($data)) { // $data is tested here only to avoid IDE warning about possible indefined variable
				if(admin_checkAndUpdateData($domainId, true)) {
					set_page_message(tr('Domain data were modified by another person before your update. The update process was successfully done but in recovery mode. We recommend you to check the result of it.'), 'warning');
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
} // end admin_updateData()

/**
 * Validates a new service limit.
 *
 * @param int $newCustomerLimit New customer service limit
 * @param int $customerConsumption Customer consumption
 * @param int $customerLimit  Limit for customer
 * @param int $resellerConsumption Reseller consumption
 * @param int $resellerLimit Limit for reseller
 * @param int $translatedServiceName Translation of service name
 * @return bool TRUE if new limit is valid, FALSE otherwise
 */
function _admin_isValidServiceLimit($newCustomerLimit, $customerConsumption,
	$customerLimit, $resellerConsumption, $resellerLimit, $translatedServiceName
) {
	// Please, don't change test order.
	if(($resellerLimit == -1 || $resellerLimit > 0) && $newCustomerLimit == 0) {
		set_page_message(tr("The %s limit for this customer cannot be unlimited because his reseller is limited for this service.", $translatedServiceName), 'error');
		return false;
	} elseif($newCustomerLimit == -1 && $customerConsumption > 0) {
		set_page_message(tr("The %s limit for this customer cannot be set to 'disabled' because he has already <strong>%d</strong> %s.", $translatedServiceName, $customerConsumption, $translatedServiceName), 'error');
		return false;
	} elseif($resellerLimit != 0 && $newCustomerLimit > ($resellerLimit - $resellerConsumption) + $customerLimit) {
		set_page_message(tr('The %s limit for this customer cannot be greater than <strong>%d</strong>, the calculated limit for his reseller.', $translatedServiceName, ($resellerLimit - $resellerConsumption) + $customerLimit), 'error');
		return false;
	} elseif($newCustomerLimit != -1 && $newCustomerLimit != 0 && $newCustomerLimit < $customerConsumption) {
		set_page_message(tr('The %s limit for this customer cannot be lower than <strong>%d</strong>, the total of %s already used for him.', $translatedServiceName, round($customerConsumption), $translatedServiceName), 'error');
		return false;
	}

	return true;
}

/************************************************************************************
 * main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL != 'admin') {
	redirectTo('manage_users.php');
}

// dispatches the request
if(!isset($_GET['edit_id'])) {
	set_page_message(tr('wrong request.'));
	redirectTo('manage_users.php');
} else {
	$domainId = (int) $_GET['edit_id'];

	if(!empty($_POST) && admin_checkAndUpdateData($domainId)) {
		redirectTo('manage_users.php');
	}
}

// Getting domain data
$data =& admin_getData($domainId);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		 'page' => $cfg->ADMIN_TEMPLATE_PATH . '/domain_edit.tpl',
		 'page_message' => 'page',
		 'subdomain_limit_block' => 'page',
		 'domain_aliasses_limit_block' => 'page',
		 'mail_accounts_limit_block' => 'page',
		 'ftp_accounts_limit_block' => 'page',
		 'sql_db_and_users_limit_block' => 'page',
		 'php_support_block' => 'page',
		 'cgi_support_block' => 'page',
		 'dns_support_block' => 'page',
		 'aps_support_block' => 'page',
		 'dns_support_block' => 'page'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Domain/Edit'),
		 'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_EDIT_DOMAIN' => tr('Edit domain'),

		 'EDIT_ID' => tohtml($domainId),

		 'TR_HELP' => tr('Help'),

		 'TR_DOMAIN_DATA' => tr('Domain data'),
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

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_users_manage.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_users_manage.tpl');
admin_generateForm($tpl, $data);
generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd,
											  new iMSCP_Events_Response($tpl));

$tpl->prnt();
