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
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 */

/**
 * Returns total number of subdomains that belong to a specific domain
 *
 * Note, this function doesn't make any differentiation between sub domains and the
 * aliasses subdomains. The result is simply the sum of both.
 *
 * @param  int $domain_id Domain unique identifier
 * @return int Total number of subdomains
 */
function get_domain_running_sub_cnt($domain_id)
{
	$stmt1 = exec_query('SELECT COUNT(*) AS cnt FROM subdomain WHERE domain_id = ?', $domain_id);

	$stmt2 = exec_query(
		'
			SELECT
				COUNT(subdomain_alias_id) AS cnt
			FROM
				subdomain_alias
			WHERE
				alias_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
		',
		$domain_id
	);

	return $stmt1->fields['cnt'] + $stmt2->fields['cnt'];
}

/**
 * Returns number of domain aliases that belong to a specific domain
 *
 * @param  int $domain_id Domain unique identifier
 * @return int Total number of domain aliases
 */
function get_domain_running_als_cnt($domain_id)
{
	$stmt = exec_query(
		'SELECT COUNT(alias_id) AS cnt FROM domain_aliasses WHERE domain_id = ? AND alias_status != ?',
		array($domain_id, 'ordered')
	);

	return $stmt->fields['cnt'];
}

/**
 * Returns information about number of mail account for a specific domain
 *
 * @param  int $domainId Domain unique identifier
 * @return array An array of values where the first item is the sum of all other items, and where each other item
 *               represents total number of a specific Mail account type
 */
function get_domain_running_mail_acc_cnt($domainId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			COUNT(mail_id) AS cnt
		FROM
			mail_users
		WHERE
			mail_type RLIKE ?
		AND
			mail_type NOT LIKE ?
		AND
			domain_id = ?
	";

	if ($cfg['COUNT_DEFAULT_EMAIL_ADDRESSES'] == 0) {
		$query .=
			"
				AND
					mail_acc != 'abuse'
				AND
					mail_acc != 'postmaster'
				AND
					mail_acc != 'webmaster'
			";
	}

	$stmt = exec_query($query, array('normal_', 'normal_catchall', $domainId));
	$dmnMailAcc = $stmt->fields['cnt'];

	$stmt = exec_query($query, array('alias_', 'alias_catchall', $domainId));
	$alsMailAcc = $stmt->fields['cnt'];

	$stmt = exec_query($query, array('subdom_', 'subdom_catchall', $domainId));
	$subMailAcc = $stmt->fields['cnt'];

	$stmt = exec_query($query, array('alssub_', 'alssub_catchall', $domainId));
	$alssubMailAcc = $stmt->fields['cnt'];

	return array(
		$dmnMailAcc + $alsMailAcc + $subMailAcc + $alssubMailAcc, $dmnMailAcc, $alsMailAcc, $subMailAcc, $alssubMailAcc
	);
}

/**
 * Returns total number of Ftp account owned by the given customer
 *
 * @param  int $customerId Customer unique identifier
 * @return int Number of Ftp account owned by the given customer
 */
function get_customer_running_ftp_acc_cnt($customerId)
{
	$stmt = exec_query('SELECT COUNT(userid) AS cnt FROM ftp_users WHERE admin_id = ?', $customerId);

	return $stmt->fields['cnt'];
}

/**
 * Returns total number of databases that belong to a specific domain
 *
 * @param  int $domainId Domain unique identifier
 * @return int Total number of databases for a specific domain
 */
function get_domain_running_sqld_acc_cnt($domainId)
{
	$stmt = exec_query('SELECT COUNT(*) AS cnt FROM sql_database WHERE domain_id = ?', $domainId);

	return $stmt->fields['cnt'];
}

/**
 * Returns total number of SQL user that belong to a specific domain
 *
 * @param  int $domainId Domain unique identifier
 * @return int Total number of SQL users for a specific domain
 */
function get_domain_running_sqlu_acc_cnt($domainId)
{
	$stmt = exec_query(
		'SELECT DISTINCT sqlu_name FROM sql_user INNER JOIN sql_database USING(sqld_id) WHERE domain_id = ?',
		$domainId
	);

	return $stmt->rowCount();
}

/**
 * Returns both total number of database and SQL user that belong to a specific domain
 *
 * @param  int $domainId Domain unique identifier
 * @return array An array where the first item is the Database total number, and the second the SQL users total number.
 */
function get_domain_running_sql_acc_cnt($domainId)
{
	return array(get_domain_running_sqld_acc_cnt($domainId), get_domain_running_sqlu_acc_cnt($domainId));
}

/**
 * Get domain limit properties
 *
 * @param  int $domainId Domain unique identifier
 * @return array
 */
function get_domain_running_props_cnt($domainId)
{
	$subCount = get_domain_running_sub_cnt($domainId);
	$alsCount = get_domain_running_als_cnt($domainId);

	list($mailAccCount) = get_domain_running_mail_acc_cnt($domainId);

	// Transitional query - Will be removed asap
	$stmt = exec_query('SELECT domain_admin_id FROM domain WHERE domain_id = ?', $domainId);

	$ftpAccCount = get_customer_running_ftp_acc_cnt($stmt->fields['domain_admin_id']);
	list($sqlDbCount, $sqlUserCount) = get_domain_running_sql_acc_cnt($domainId);

	return array($subCount, $alsCount, $mailAccCount, $ftpAccCount, $sqlDbCount, $sqlUserCount);
}

/**
 * Translate mail type
 *
 * @param  string $mail_type
 * @return string Translated mail type
 */
function user_trans_mail_type($mail_type)
{
	if ($mail_type === MT_NORMAL_MAIL) {
		return tr('Domain mail');
	} else if ($mail_type === MT_NORMAL_FORWARD) {
		return tr('Email forward');
	} else if ($mail_type === MT_ALIAS_MAIL) {
		return tr('Alias mail');
	} else if ($mail_type === MT_ALIAS_FORWARD) {
		return tr('Alias forward');
	} else if ($mail_type === MT_SUBDOM_MAIL) {
		return tr('Subdomain mail');
	} else if ($mail_type === MT_SUBDOM_FORWARD) {
		return tr('Subdomain forward');
	} else if ($mail_type === MT_ALSSUB_MAIL) {
		return tr('Alias subdomain mail');
	} else if ($mail_type === MT_ALSSUB_FORWARD) {
		return tr('Alias subdomain forward');
	} else if ($mail_type === MT_NORMAL_CATCHALL) {
		return tr('Domain mail');
	} else if ($mail_type === MT_ALIAS_CATCHALL) {
		return tr('Domain mail');
	} else {
		return tr('Unknown type.');
	}
}

/**
 * Checks if an user has permissions on a specific SQL user
 *
 * @param  int $sqlUserId SQL user unique identifier
 * @return bool TRUE if the logged in user has permission on SQL user, FALSE otherwise
 */
function check_user_sql_perms($sqlUserId)
{
	return (who_owns_this($sqlUserId, 'sqlu_id') == $_SESSION['user_id']);
}

/**
 * Returns translated gender code
 *
 * @param string $code Gender code to be returned
 * @param bool $nullOnBad Tells whether or not null must be returned on unknow $code
 * @return null|string Translated gender or null in some circonstances.
 */
function get_gender_by_code($code, $nullOnBad = false)
{
	switch (strtolower($code)) {
		case 'm':
		case 'M':
			return tr('Male');
		case 'f':
		case 'F':
			return tr('Female');
		default:
			return (!$nullOnBad) ? tr('Unknown') : null;
	}
}

/**
 * Tells whether or not the current customer can access to the given feature(s)
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @throws iMSCP_Exception When $featureName is not known
 * @param array|string $featureNames Feature name(s) (insensitive case)
 * @param bool $forceReload If true force data to be reloaded
 * @return bool TRUE if $featureName is available for customer, FALSE otherwise
 */
function customerHasFeature($featureNames, $forceReload = false)
{
	static $availableFeatures = null;
	static $debug = false;

	if (null === $availableFeatures || $forceReload) {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');
		$debug = (bool)$cfg->DEBUG;
		$dmnProps = get_domain_default_props($_SESSION['user_id']);

		$availableFeatures = array(
			'domain' => ($dmnProps['domain_alias_limit'] != '-1'
				|| $dmnProps['domain_subd_limit'] != '-1'
				|| $dmnProps['domain_dns'] == 'yes'
				|| $dmnProps['phpini_perm_system'] == 'yes') ? true : false,
			'external_mail' => ($dmnProps['domain_external_mail'] == 'yes') ? true : false,
			'php' => ($dmnProps['domain_php'] == 'yes') ? true : false,
			'php_editor' => ($dmnProps['phpini_perm_system'] == 'yes' &&
				($dmnProps['phpini_perm_allow_url_fopen'] == 'yes'
					|| $dmnProps['phpini_perm_display_errors'] == 'yes'
					|| in_array($dmnProps['phpini_perm_disable_functions'], array('yes', 'exec')))) ? true : false,
			'cgi' => ($dmnProps['domain_cgi'] == 'yes') ? true : false,
			'ftp' => ($dmnProps['domain_ftpacc_limit'] != '-1') ? true : false,
			'sql' => ($dmnProps['domain_sqld_limit'] != '-1') ? true : false,
			'mail' => ($dmnProps['domain_mailacc_limit'] != '-1') ? true : false,
			'subdomains' => ($dmnProps['domain_subd_limit'] != '-1') ? true : false,
			'domain_aliases' => ($dmnProps['domain_alias_limit'] != '-1') ? true : false,
			'custom_dns_records' =>
				($dmnProps['domain_dns'] != 'no' && $cfg['NAMED_SERVER'] != 'external_server') ? true : false,
			'webstats' => ($cfg->WEBSTATS_ADDONS != 'No') ? true : false,
			'backup' => ($cfg->BACKUP_DOMAINS != 'no' && $dmnProps['allowbackup'] != 'no') ? true : false,
			'protected_areas' => true,
			'custom_error_pages' => true,
			'aps' => ($dmnProps['domain_software_allowed'] != 'no' && $dmnProps['domain_ftpacc_limit'] != '-1')
					? true : false,
            'ssl' => ($cfg['ENABLE_SSL']) ? true : false
		);

		if (($cfg->IMSCP_SUPPORT_SYSTEM)) {
			$query = "SELECT support_system FROM reseller_props WHERE reseller_id = ?";
			$stmt = exec_query($query, $_SESSION['user_created_by']);
			$availableFeatures['support'] = ($stmt->fields['support_system'] == 'yes') ? true : false;
		} else {
			$availableFeatures['support'] = false;
		}
	}

	$canAccess = true;
	foreach ((array)$featureNames as $featureName) {
		$featureName = strtolower($featureName);

		if ($debug && !array_key_exists($featureName, $availableFeatures)) {
			throw new iMSCP_Exception(
				sprintf("Feature %s is not known by the customerHasFeature() function.", $featureName)
			);
		}

		if (!$availableFeatures[$featureName]) {
			$canAccess = false;
			break;
		}
	}

	return $canAccess;
}

/**
 * Tells whether or not the current customer can access the mail or external mail feature.
 * @return bool
 */
function customerHasMailOrExtMailFeatures()
{
	return (customerHasFeature('mail') || customerHasFeature('external_mail'));
}

/**
 * Does the given customer is the owner of the given domain?
 *
 * @param string $domainName Domain name (dmn,sub,als,alssub)
 * @param int $customerId Customer unique identifier
 * @return bool TRUE if the given customer is the owner of the given domain, FALSE otherwise
 * TODO add admin_id as foreign key in all domain tables too avoid too many jointures
 */
function customerHasDomain($domainName, $customerId)
{
	$domainName = encode_idna($domainName);

	// Check in domain table
	$stmt = exec_query(
		"SELECT 'found' FROM domain WHERE domain_admin_id = ? AND domain_name = ?", array($customerId, $domainName)
	);

	if ($stmt->rowCount()) {
		return true;
	}

	// Check in domain_aliasses table
	$stmt = exec_query(
		"
			SELECT
				'found'
			FROM
				domain AS t1
			INNER JOIN
				domain_aliasses AS t2 ON(t2.domain_id = t1.domain_id)
			WHERE
				t1.domain_admin_id = ?
			AND
				t2.alias_name = ?
		",
		array($customerId, $domainName)
	);

	if ($stmt->rowCount()) {
		return true;
	}

	// Check in subdomain table
	$stmt = exec_query(
		"
			SELECT
				'found'
			FROM
				domain AS t1
			INNER JOIN
				subdomain AS t2 ON (t2.domain_id = t1.domain_id)
			WHERE
				t1.domain_admin_id = ?
			AND
				CONCAT(t2.subdomain_name, '.', t1.domain_name) = ?
		",
		array($customerId, $domainName)
	);

	if ($stmt->rowCount()) {
		return true;
	}

	// Check in subdomain_alias table
	$stmt = exec_query(
		"
			SELECT
				'found'
			FROM
				domain AS t1
			INNER JOIN
				domain_aliasses AS t2 ON(t2.domain_id = t1.domain_id)
			INNER JOIN
			 	subdomain_alias AS t3 ON(t3.alias_id = t2.alias_id)
			WHERE
				t1.domain_admin_id = ?
			AND
				CONCAT(t3.subdomain_alias_name, '.', t2.alias_name) = ?
		",
		array($customerId, $domainName)
	);

	if ($stmt->rowCount()) {
		return true;
	}

	return false;
}

/**
 * Delete all autoreplies log for which not mail address is found in the mail_users database table
 *
 * @return void
 */
function delete_autoreplies_log_entries()
{
	exec_query("DELETE FROM autoreplies_log WHERE `from` NOT IN (SELECT mail_addr FROM mail_users)");
}
