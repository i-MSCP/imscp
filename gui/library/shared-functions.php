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
 * @copyright    2001-2006 by moleSoftware GmbH
 * @copyright    2006-2010 by ispCP | http://isp-control.net
 * @copyright    2010-2014 by i-MSCP | http://i-mscp.net
 * @link         http://i-mscp.net
 * @author       ispCP Team
 * @author       i-MSCP Team
 */

/***********************************************************************************************************************
 * This file contains functions that are used at many level (eg. admin, reseller, client)
 */

/***********************************************************************************************************************
 * Account functions
 */

/**
 * Returns user name matching identifier
 *
 * @param  int $user_id User unique identifier
 * @return string Username
 */
function get_user_name($user_id)
{
	$query = "SELECT `admin_name` FROM `admin` WHERE `admin_id` = ?";
	$stmt = exec_query($query, $user_id);

	return $stmt->fields('admin_name');
}

/***********************************************************************************************************************
 * Domain related functions
 */

/**
 * Checks if the given domain name already exist
 *
 * Rules:
 *
 * A domain is considered as existing if:
 *
 * - It is found either in the domain table or in the domain_aliasses table
 * - It is a subzone of another domain which doesn't belong to the given reseller
 * - It already exist as subdomain (whatever the subdomain type (sub,alssub)
 *
 * @param string $domainName Domain name to match
 * @param int $resellerId Reseller unique identifier
 * @return bool TRUE if the domain already exist, FALSE otherwise
 */
function imscp_domain_exists($domainName, $resellerId)
{
	// Be sure to work with ASCII domain name
	$domainName = encode_idna($domainName);

	// Does the domain already exist in the domain table?
	$stmt = exec_query('SELECT COUNT(domain_id) AS cnt FROM domain WHERE domain_name = ?', $domainName);
	$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
	if($row['cnt']) return true;

	// Does the domain already exists in the domain_aliasses table?
	$stmt = exec_query(
		'SELECT COUNT(alias_id) AS cnt FROM domain_aliasses INNER JOIN domain USING(domain_id) WHERE alias_name = ?',
		$domainName
	);
	$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
	if($row['cnt']) return true;

	# Does the domain is a subzone of another domain which doesn't belong to the given reseller?

	$queryDomain = '
		SELECT
			COUNT(domain_id) AS cnt
		FROM
			domain
		INNER JOIN
			admin ON(admin_id = domain_admin_id)
		WHERE
			domain_name = ?
		AND
			created_by <> ?
	';

	$queryAliases = '
		SELECT
			COUNT(alias_id) AS cnt
		FROM
			domain_aliasses
		INNER JOIN
			domain USING(domain_id)
		INNER JOIN
			admin ON(admin_id = domain_admin_id)
		WHERE
			alias_name = ?
		AND
			created_by <> ?
	';

	$domainLabels = explode('.', trim($domainName));
	$domainPartCnt = 0;

	for ($i = 0, $countDomainLabels = count($domainLabels) - 1; $i < $countDomainLabels; $i++) {
		$domainPartCnt = $domainPartCnt + strlen($domainLabels[$i]) + 1;
		$parentDomain = substr($domainName, $domainPartCnt);

		// Execute query the redefined queries for domains/accounts and aliases tables
		$stmt = exec_query($queryDomain, array($parentDomain, $resellerId));
		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
		if($row['cnt']) return true;

		$stmt = exec_query($queryAliases, array($parentDomain, $resellerId));
		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
		if($row['cnt']) return true;
	}

	// Does the domain already exists as subdomain?

	$stmt = exec_query(
		"
			SELECT
				'found'
			FROM
				subdomain
			INNER JOIN
				domain USING(domain_id)
			WHERE
				CONCAT(subdomain_name, '.', domain_name) = ?
		",
		 $domainName
	);
	if($stmt->rowCount()) return true;

	$stmt = exec_query(
		"
			SELECT
				'found'
			FROM
				subdomain_alias
			INNER JOIN
				domain_aliasses USING(alias_id)
			WHERE
				CONCAT(subdomain_alias_name, '.', alias_name) = ?
		",
		$domainName
	);
	if($stmt->rowCount()) return true;

	return false;
}

/**
 * Returns domain default properties
 *
 * Note: For performance reasons, the data are retrieved once per request.
 *
 * @param int $domainAdminId Customer unique identifier
 * @param int|null $createdBy OPTIONAL reseller unique identifier
 * @return array Returns an associative array where each key is a domain propertie name.
 */
function get_domain_default_props($domainAdminId, $createdBy = null)
{
	static $domainProperties = null;

	if (null === $domainProperties) {
		if(is_null($createdBy)) {
			$stmt = exec_query('SELECT * FROM domain WHERE domain_admin_id = ?', $domainAdminId);
		} else {
			$stmt = exec_query(
				'
					SELECT
						*
					FROM
						domain
					INNER JOIN
						admin ON(admin_id = domain_admin_id)
					WHERE
						domain_admin_id = ?
					AND
						created_by = ?
				',
				array($domainAdminId, $createdBy)
			);
		}

		if(!$stmt->rowCount()) {
			showBadRequestErrorPage();
		}

		$domainProperties = $stmt->fetchRow(PDO::FETCH_ASSOC);
	}

	return $domainProperties;
}

/**
 * Return main domain unique identifier of the given customer
 *
 * @throws iMSCP_Exception in case the domain id cannot be found
 * @param int $customeId Customer unique identifier
 * @return int main domain unique identifier
 */
function get_user_domain_id($customeId)
{
	static $domainId = null;

	if(null === $domainId) {
		$query = 'SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?';
		$stmt = exec_query($query, $customeId);

		if($stmt->rowCount()) {
			$domainId = $stmt->fields['domain_id'];
		} else {
			throw new iMSCP_Exception("Unable to found domain ID of user with ID '$customeId''");
		}
	}

	return $domainId;
}

/**
 * Returns the total number of consumed and max available items for the given customer
 *
 * @param  int $domainId Domain unique identifier
 * @return array
 */
function shared_getCustomerProps($domainId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$rs = exec_query("SELECT * FROM domain WHERE domain_id = ?", $domainId);

	if (!$rs->recordCount()) {
		return array_fill(0, 14, 0);
	}

	// Retrieves total number of subdomains already consumed by the customer
	$subConsumed = records_count('subdomain', 'domain_id', $domainId);

	// Retrieves max available number of subdomains for the customer
	$subMax = $rs->fields['domain_subd_limit'];

	// Retrieves total number of domain aliases already consumed by the customer
	$alsConsumed = records_count('domain_aliasses', 'domain_id', $domainId);

	// Retrieves max available number of domain aliases for the customer
	$alsMax = $rs->fields['domain_alias_limit'];

	// Retrieves total number of mail accounts already consumed by the customer
	// This works with the admin option (Count default email addresses)
	if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES) {
		$mailConsumed = records_count('mail_users', "mail_type NOT RLIKE '_catchall' AND domain_id", $domainId);
	} else {
		$where = "
				`mail_acc` != 'abuse'
			AND
				`mail_acc` != 'postmaster'
			AND
				`mail_acc` != 'webmaster'
			AND
				`mail_type` NOT RLIKE '_catchall'
			AND
				`domain_id`
		";

		$mailConsumed = records_count('mail_users', $where, $domainId);
	}

	// Retrieves max available number of mail accounts for the customer
	$mailMax = $rs->fields['domain_mailacc_limit'];

	// Retrieve total number of ftp accounts already consumed by the customer
	$ftpConsumed = sub_records_rlike_count(
		'domain_name', 'domain', 'domain_id', $domainId, 'userid', 'ftp_users', 'userid', '@', ''
	);
	$ftpConsumed += sub_records_rlike_count(
		'alias_name', 'domain_aliasses', 'domain_id', $domainId, 'userid', 'ftp_users', 'userid', '@', ''
	);

	// Retrieves max available number of mail accounts for the customer
	$ftpMax = $rs->fields['domain_ftpacc_limit'];

	// Retrieves total number of SQL databases already consumed by the customer
	$sqlDbConsumed = records_count('sql_database', 'domain_id', $domainId);

	// Retrieves max available number of SQL databases for the customer
	$sqlDbMax = $rs->fields['domain_sqld_limit'];

	// Retrieves total number of SQL user already consumed by the customer
	$sqlUserConsumed = sub_records_count(
		'sqld_id', 'sql_database', 'domain_id', $domainId, 'sqlu_id', 'sql_user', 'sqld_id', 'sqlu_name', ''
	);

	// Retrieves max number of SQL user for the customer
	$sqlUserMax = $rs->fields['domain_sqlu_limit'];

	// Retrieves max available montly traffic volume for the customer
	$trafficMax = $rs->fields['domain_traffic_limit'];

	// Retrieve max available diskspace limit for the customer
	$diskMax = $rs->fields['domain_disk_limit'];

	return array(
		$subConsumed, $subMax, $alsConsumed, $alsMax, $mailConsumed, $mailMax, $ftpConsumed, $ftpMax, $sqlDbConsumed,
		$sqlDbMax, $sqlUserConsumed, $sqlUserMax, $trafficMax, $diskMax
	);
}

/**
 * Returns translated item status
 *
 * @param string $status Item status to translate
 * @return string Translated status
 */
function translate_dmn_status($status)
{
	switch ($status) {
		case 'ok':
			return tr('Ok');
		case 'toadd':
			return tr('Addition in progress');
		case 'tochange':
			return tr('Modification in progress');
		case 'todelete':
			return tr('Deletion in progress');
		case 'disabled':
			return tr('Suspended');
		case 'toenable':
			return tr('Being enabled');
		case 'todisable':
			return tr('Being suspended');
		case 'ordered':
			return tr('Awaiting approval');
		default:
			return tr('Unknown error');
	}
}

/**
 * Updates customer limits/features
 *
 * @param int $domainId Domain unique identifier
 * @param string $props String that contain new properties values
 * @return void
 */
/*
function update_user_props($domainId, $props)
{
	$cfg = iMSCP_Registry::get('config');

	list(
		, $maxSubLimit, , $maxAlsLimit, , $maxMailLimit, , $maxFtpLimit, , $maxSqlDbLimit, , $maxSqlUserLimit,
		$maxMonthlyTraffic, $maxDiskspace, $phpSupport, $cgiSupport, , $customDnsSupport, $softwareInstallerSupport
	) = explode(';', $props);

	$domainLastModified = time();

	// We must check previous values for features (eg. php, cgi, dns, software installer) to determine if we must send a
	// request to the ispCP daemon
	$query = "
		SELECT
			`domain_name`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		AND
			`domain_php` = ?
		AND
			`domain_cgi` = ?
		AND
			`domain_dns` = ?
		AND
			`domain_software_allowed` = ?
	";
	$stmt = exec_query($query, array($domainId, $phpSupport, $cgiSupport, $customDnsSupport, $softwareInstallerSupport));

	// No record found. That mean that a least one value was changed
	if (!$stmt->rowCount()) {
		$updateStatus = 'tochange';

		// Update customer limits/features and schedule domain update
		$query = "
			UPDATE
				`domain`
			SET
				`domain_last_modified` = ?, `domain_mailacc_limit` = ?, `domain_ftpacc_limit` = ?,
				`domain_traffic_limit` = ?, `domain_sqld_limit` = ?, `domain_sqlu_limit` = ?, `domain_status` = ?,
				`domain_alias_limit` = ?, `domain_subd_limit` = ?, `domain_disk_limit` = ?, `domain_php` = ?,
				`domain_cgi` = ?, `domain_dns` = ?, `domain_software_allowed` = ?
			WHERE
				`domain_id` = ?
		";
		exec_query(
			$query,
			array(
				$domainLastModified, $maxMailLimit, $maxFtpLimit, $maxMonthlyTraffic, $maxSqlDbLimit, $maxSqlUserLimit,
				$updateStatus, $maxAlsLimit, $maxSubLimit, $maxDiskspace, $phpSupport, $cgiSupport, $customDnsSupport,
				$softwareInstallerSupport, $domainId
			)
		);

		// Schedule als update
		$query = "UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `domain_id` = ?";
		exec_query($query, array($updateStatus, $domainId));

		// Schedule sub update
		$query = "UPDATE `subdomain` SET `subdomain_status` = ? WHERE `domain_id` = ?";
		exec_query($query, array($updateStatus, $domainId));

		// Let's update subals update
		$query = "
			UPDATE
				`subdomain_alias`
			SET
				`subdomain_alias_status` = ?
			WHERE
				`alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)
		";
		exec_query($query, array($updateStatus, $domainId));

		// Send a request to the i-MSCP daemon
		send_request();
	} else {
		// We do not have changes for any feature. We have to update only customer limits.
		$query = "
			UPDATE
				`domain`
			SET
				`domain_last_modified` = ?, `domain_subd_limit` = ?, `domain_alias_limit` = ?, `domain_mailacc_limit` = ?,
				`domain_ftpacc_limit` = ?, `domain_sqld_limit` = ?, `domain_sqlu_limit` = ?, `domain_traffic_limit` = ?,
				`domain_disk_limit` = ?
			WHERE
				domain_id = ?
		";
		exec_query(
			$query,
			array(
				$domainLastModified, $maxSubLimit, $maxAlsLimit, $maxMailLimit, $maxFtpLimit, $maxSqlDbLimit,
				$maxSqlUserLimit, $maxMonthlyTraffic, $maxDiskspace, $domainId
			)
		);
	}
}
*/

/**
 * Updates dommain expiration date
 *
 * @param  int $user_id User unique identifier
 * @param  int $domain_new_expire New expiration date
 * @return void
 */
/*
function update_expire_date($user_id, $domain_new_expire)
{
	exec_query("UPDATE `domain` SET `domain_expires` = ? WHERE `domain_id` = ?", array($domain_new_expire, $user_id));
}
*/

/**
 * Activate or deactivate the given customer account
 *
 * @throws iMSCP_Exception|iMSCP_Exception_Database
 * @param int $customerId Customer unique identifier
 * @param string $action Action to schedule
 * @return void
 */
function change_domain_status($customerId, $action)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($action == 'deactivate') {
		$newStatus = 'todisable';
	} else if ($action == 'activate') {
		$newStatus = 'toenable';
	} else {
		throw new iMSCP_Exception("Unknow action: $action");
	}

	$query = "SELECT `domain_id`, `domain_name` FROM `domain` WHERE `domain_admin_id` = ?";
	$stmt = exec_query($query, $customerId);

	if(!$stmt->rowCount()) {
		throw new iMSCP_Exception("Unable to found domain for user with ID $customerId");
	}

	iMSCP_Events_Aggregator::getInstance()->dispatch(
		iMSCP_Events::onBeforeChangeDomainStatus, array('customerId' => $customerId, 'action' => $action)
	);

	$domainId = $stmt->fields['domain_id'];
	$domainName = decode_idna($stmt->fields['domain_name']);

	/** @var $db iMSCP_Database */
	$db = iMSCP_Database::getInstance();

	try {
		$db->beginTransaction();

		$query = "SELECT `mail_id`, `mail_pass`, `mail_type` FROM `mail_users` WHERE `domain_id` = ?";
		$stmt = exec_query($query, $domainId);

		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$mailId = $row['mail_id'];
			$mailPassword = $row['mail_pass'];
			$mailType = $row['mail_type'];

			if ($cfg->HARD_MAIL_SUSPENSION) {
				$mailStatus = $newStatus;
			} else {
				if ($action == 'deactivate') {
					$timestamp = time();
					$passwordPrefix = substr(md5($timestamp), 0, 4);

					if (
						preg_match('/^' . MT_NORMAL_MAIL . '/', $mailType) ||
						preg_match('/^' . MT_ALIAS_MAIL . '/', $mailType) ||
						preg_match('/^' . MT_SUBDOM_MAIL . '/', $mailType) ||
						preg_match('/^' . MT_ALSSUB_MAIL . '/', $mailType)
					) {
						$mailPassword = $passwordPrefix . $mailPassword;
					}
				} elseif ($action == 'activate') {
					if (
						preg_match('/^' . MT_NORMAL_MAIL . '/', $mailType) ||
						preg_match('/^' . MT_ALIAS_MAIL . '/', $mailType) ||
						preg_match('/^' . MT_SUBDOM_MAIL . '/', $mailType) ||
						preg_match('/^' . MT_ALSSUB_MAIL . '/', $mailType)
					) {
						$mailPassword = substr($mailPassword, 4, 50);
					}
				} else {
					return;
				}

				$mailStatus = 'tochange';
			}

			$query = "UPDATE `mail_users` SET `mail_pass` = ?, `status` = ? WHERE `mail_id` = ?";
			exec_query($query, array($mailPassword, $mailStatus, $mailId));
		}

		# TODO implements customer deactivation
		#exec_query('UPDATE `admin` SET `admin_status` = ? WHERE `admin_id` = ?', array($newStatus, $customerId));
		exec_query("UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?", array($newStatus, $domainId));
		exec_query("UPDATE `subdomain` SET `subdomain_status` = ? WHERE `domain_id` = ?", array($newStatus, $domainId));
		exec_query("UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `domain_id` = ?", array($newStatus, $domainId));

		$query = "
			UPDATE
				`subdomain_alias`
			SET
				`subdomain_alias_status` = ?
			WHERE
				`alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)
		";
		exec_query($query, array($newStatus, $domainId));

		$db->commit();
	} catch(iMSCP_Exception_Database $e) {
		$db->rollBack();
		throw new iMSCP_Exception_Database($e->getMessage(), $e->getQuery(), $e->getCode(), $e);
	}

	iMSCP_Events_Aggregator::getInstance()->dispatch(
		iMSCP_Events::onAfterChangeDomainStatus, array('customerId' => $customerId, 'action' => $action)
	);

	// Send request to i-MSCP daemon
	send_request();

	if($action == 'deactivate') {
		write_log("{$_SESSION['user_logged']}: scheduled deactivation of customer account: $domainName", E_USER_NOTICE);
		set_page_message(tr('Customer account successfully scheduled for deactivation.'), 'success');
	} else {
		write_log("{$_SESSION['user_logged']}: scheduled activation of customer account: $domainName", E_USER_NOTICE);
		set_page_message(tr('Customer account successfully scheduled for activation.'), 'success');
	}
}

/**
 * Deletes an SQL user
 *
 * @throws iMSCP_Exception_Database
 * @param  int $domainId Domain unique identifier
 * @param  int $sqlUserId Sql user unique identifier
 * @param bool $flushPrivileges Whether or not privilege must be flushed
 * @return bool TRUE if $sqlUserId has been found and successfully removed, FALSE otherwise
 */
function sql_delete_user($domainId, $sqlUserId, $flushPrivileges = true)
{
	iMSCP_Events_Aggregator::getInstance()->dispatch(
		iMSCP_Events::onBeforeDeleteSqlUser, array('sqlUserId' => $sqlUserId)
	);

	$db = iMSCP_Database::getInstance();

	try {
		$db->beginTransaction();

		$stmt = exec_query(
			'
				SELECT
					sqlu_name, sqlu_host, sqld_name
				FROM
					sql_user
				INNER JOIN
					sql_database USING(sqld_id)
				WHERE
					sqlu_id = ?
				AND
					domain_id = ?
			',
			array($sqlUserId, $domainId)
		);

		if (!$stmt->rowCount()) {
			return false;
		}

		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

		$sqlUserName = $row['sqlu_name'];
		$sqlUserHost = $row['sqlu_host'];
		$sqlDbName = $row['sqld_name'];

		$stmt = exec_query(
			'SELECT COUNT(sqlu_id) AS cnt FROM sql_user WHERE sqlu_name = ? AND sqlu_host = ?',
			array($sqlUserName, $sqlUserHost)
		);

		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

		if ($row['cnt'] == '1') {
			// SQL user is assigned to one database only. We can remove it completely
			exec_query('DELETE FROM mysql.user WHERE User = ? AND Host = ?', array($sqlUserName, $sqlUserHost));
			exec_query('DELETE FROM mysql.db WHERE Host = ? AND User = ?', array($sqlUserHost, $sqlUserName));
		} else {
			// SQL user is assigned to many databases. We remove its privileges for the involved database only
			exec_query(
				'DELETE FROM mysql.db WHERE Host = ? AND Db = ? AND User = ?',
				array($sqlUserHost, $sqlDbName, $sqlUserName)
			);
		}

		// Delete SQL user from i-MSCP database
		exec_query('DELETE FROM sql_user WHERE sqlu_id = ?', $sqlUserId);

		$db->commit();

		// Flush SQL privileges
		if($flushPrivileges) {
			execute_query('FLUSH PRIVILEGES');
		}

		iMSCP_Events_Aggregator::getInstance()->dispatch(
			iMSCP_Events::onAfterDeleteSqlUser, array('sqlUserId' => $sqlUserId)
		);
	} catch(iMSCP_Exception_Database $e) {
		$db->rollBack();
		throw $e;
	}

	return true;
}

/**
 * Deletes the given SQL database
 *
 * @throws iMSCP_Exception in case an SQL user which belong to the given database cannot be removed
 * @param  int $domainId Domain unique identifier
 * @param  int $databaseId Databse unique identifier
 * @return bool TRUE when $databaseId has been found and successfully deleted, FALSE otherwise
 */
function delete_sql_database($domainId, $databaseId)
{
	$db = iMSCP_Database::getInstance();

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeDeleteSqlDb, array('sqlDbId' => $databaseId));

	try {
		$db->beginTransaction();

		// Get name of database
		$stmt = exec_query(
			'SELECT sqld_name FROM sql_database WHERE domain_id = ? AND sqld_id = ?', array($domainId, $databaseId)
		);

		if ($stmt->rowCount()) {
			$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
			$databaseName = quoteIdentifier($row['sqld_name']);

			// Get list of SQL users assigned to the database being removed

			$stmt = exec_query(
				'
					SELECT
						sqlu_id
					FROM
						sql_user
					INNER JOIN
						sql_database USING(sqld_id)
					WHERE
						sqld_id = ?
					AND
						domain_id = ?
				',
				array($databaseId, $domainId)
			);

			if ($stmt->rowCount()) {
				while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					if (!sql_delete_user($domainId, $row['sqlu_id'], false)) {
						return false;
					}
				}
			}

			exec_query('DELETE FROM sql_database WHERE domain_id = ? AND sqld_id = ?', array($domainId, $databaseId));

			// Must be done last due to the implicit commit
			execute_query("DROP DATABASE IF EXISTS $databaseName");
			execute_query('FLUSH PRIVILEGES');

			iMSCP_Events_Aggregator::getInstance()->dispatch(
				iMSCP_Events::onAfterDeleteSqlDb, array('sqlDbId' => $databaseId)
			);

			return true;
		}
	} catch(iMSCP_Exception_Database $e) {
		$db->rollBack();
		throw $e;
	}

	return false;
}

/**
 * Deletes the given customer
 *
 * @throws iMSCP_Exception
 * @param integer $customerId Customer unique identifier
 * @param boolean $checkCreatedBy Tell whether or not customer must have been created by logged-in user
 * @return bool TRUE on success, FALSE otherwise
 */
function deleteCustomer($customerId, $checkCreatedBy = false)
{
	$customerId = (int)$customerId;

	iMSCP_Events_Aggregator::getInstance()->dispatch(
		iMSCP_Events::onBeforeDeleteCustomer, array('customerId' => $customerId)
	);

	// Get username, uid and gid of domain user
	$query = '
		SELECT
			admin_name, created_by, domain_id
		FROM
			admin
		INNER JOIN
			domain ON(domain_admin_id = admin_id)
		WHERE
			admin_id = ?
	';

	if ($checkCreatedBy) {
		$query .= 'AND created_by = ?';
		$stmt = exec_query($query, array($customerId, $_SESSION['user_id']));
	} else {
		$stmt = exec_query($query, $customerId);
	}

	if (!$stmt->rowCount()) {
		return false;
	}

	$customerName = $stmt->fields['admin_name'];
	$mainDomainId = $stmt->fields['domain_id'];
	$resellerId = $stmt->fields['created_by'];
	$deleteStatus = 'todelete';

	$db = iMSCP_Database::getInstance();

	try {
		// First, remove customer sessions to prevent any problems
		exec_query('DELETE FROM login WHERE user_name = ?', $customerName);

		// Remove customer's databases and Sql users
		$stmt = exec_query('SELECT sqld_id FROM sql_database WHERE domain_id = ?', $mainDomainId);

		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			delete_sql_database($mainDomainId, $row['sqld_id']);
		}

		$db->beginTransaction();

		// Deletes all protected areas data (areas, groups and users)

		exec_query(
			'
				DELETE
					t2, t3, t4
				FROM
					domain AS t1
				LEFT JOIN
					htaccess AS t2 ON (t2.dmn_id = t1.domain_id)
				LEFT JOIN
					htaccess_users AS t3 ON (t3.dmn_id = t1.domain_id)
				LEFT JOIN
					htaccess_groups AS t4 ON (t4.dmn_id = t1.domain_id)
				WHERE
					t1.domain_id = ?
			',
			$mainDomainId
		);

		// Deletes domain traffic entries
		exec_query('DELETE FROM domain_traffic WHERE domain_id = ?', $mainDomainId);

		// Deletes custom DNS records
		exec_query('DELETE FROM domain_dns WHERE domain_id = ?', $mainDomainId);

		// Deletes FTP accounts (users and groups)
		exec_query('DELETE FROM ftp_users WHERE admin_id = ?', $customerId);
		exec_query('DELETE FROM ftp_group WHERE groupname = ?', $customerName);

		// Deletes quota entries
		exec_query('DELETE FROM quotalimits WHERE name = ?', $customerName);
		exec_query('DELETE FROM quotatallies WHERE name = ?', $customerName);

		// Deletes support tickets
		exec_query('DELETE FROM tickets WHERE ticket_from = ? OR ticket_to = ?', array($customerId, $customerId));

		// Deletes user gui properties
		exec_query('DELETE FROM user_gui_props WHERE user_id = ?', $customerId);

		// Deletes own php.ini entry
		exec_query('DELETE FROM php_ini WHERE domain_id = ?', $mainDomainId);

		// Delegated tasks - begin

		// Schedule mail accounts deletion
		exec_query('UPDATE mail_users SET status = ? WHERE domain_id = ?', array($deleteStatus, $mainDomainId));

		// Schedule subdomain's aliasses deletion

		exec_query(
			'
				UPDATE
					subdomain_alias AS t1
				JOIN
					domain_aliasses AS t2 ON(t2.domain_id = ?)
				SET
					t1.subdomain_alias_status = ?
				WHERE
					t1.alias_id = t2.alias_id
			',
			array($mainDomainId, $deleteStatus)
		);

		// Schedule Domain aliases deletion
		exec_query(
			'UPDATE domain_aliasses SET alias_status =  ? WHERE domain_id = ?', array($deleteStatus, $mainDomainId)
		);

		// Schedule domain's subdomains deletion
		exec_query(
			'UPDATE subdomain SET subdomain_status = ? WHERE domain_id = ?', array($deleteStatus, $mainDomainId)
		);

		// Schedule domain deletion
		exec_query('UPDATE domain SET domain_status = ? WHERE domain_id = ?', array($deleteStatus, $mainDomainId));

		// Schedule user deletion
		exec_query('UPDATE admin SET admin_status = ? WHERE admin_id = ?', array($deleteStatus, $customerId));

		// Schedule SSL certificates deletion
		exec_query(
			"UPDATE ssl_certs SET status = ? WHERE domain_type = 'dmn' AND domain_id = ?",
			array($deleteStatus, $mainDomainId)
		);
		exec_query(
			"
				UPDATE
					ssl_certs
				SET
					status = ?
				WHERE
					domain_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
				AND
					domain_type = ?
			",
			array($deleteStatus, $mainDomainId, 'als')
		);
		exec_query(
			"
				UPDATE
					ssl_certs SET status = ?
				WHERE
					domain_id IN (SELECT subdomain_id FROM subdomain WHERE domain_id = ?)
				AND
					domain_type = ?
			",
			array($deleteStatus, $mainDomainId, 'sub')
		);
		exec_query(
			"
				UPDATE
					ssl_certs SET status = ?
				WHERE
					domain_id IN (
						SELECT
							subdomain_alias_id
						FROM
							subdomain_alias
						WHERE
							alias_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
					)
				AND
					domain_type = ?
			",
			array($deleteStatus, $mainDomainId, 'alssub')
		);

		// Delegated tasks - end

		// Updates resellers properties
		update_reseller_c_props($resellerId);

		// Commit all changes to database server
		$db->commit();

		iMSCP_Events_Aggregator::getInstance()->dispatch(
			iMSCP_Events::onAfterDeleteCustomer, array('customerId' => $customerId)
		);
	} catch (iMSCP_Exception $e) {
		$db->rollBack();
		throw new iMSCP_Exception($e->getMessage(), $e->getCode(), $e);
	}

	// We are now ready to send a request to the daemon for delegated tasks.
	// Note: We are safe here. If the daemon doesn't answer, some entities will not be removed. In such case the
	// sysadmin will have to fix the problem causing deletion break and send a request to the daemon manually via the
	// panel, or run the imscp-rqst-mngr script manually.
	send_request();

	return true;
}

/**
 * Returns number of items in a database table with optional search criterias
 *
 * @param string $field
 * @param string $table
 * @param string $where
 * @param string $value
 * @param string $subfield
 * @param string $subtable
 * @param string $subwhere
 * @param string $subgroupname
 * @return int
 */
function sub_records_count($field, $table, $where, $value, $subfield, $subtable, $subwhere, $subgroupname)
{
	if ($where != '') {
		$query = "SELECT $field AS `field` FROM $table WHERE $where = ?";
		$stmt = exec_query($query, $value);
	} else {
		$query = "SELECT $field AS `field` FROM $table";
		$stmt = execute_query($query);
	}

	$result = 0;

	if (!$stmt->rowCount()) {
		return $result;
	}

	if ($subgroupname != '') {
		$sqld_ids = array();

		while (!$stmt->EOF) {
			array_push($sqld_ids, $stmt->fields['field']);
			$stmt->moveNext();
		}

		$sqld_ids = implode(',', $sqld_ids);

		if ($subwhere != '') {
			$query = "SELECT COUNT(DISTINCT $subgroupname) AS `cnt` FROM $subtable WHERE `sqld_id` IN ($sqld_ids)";
			$subres = execute_query($query);
			$result = $subres->fields['cnt'];
		} else {
			return $result;
		}
	} else {
		while (!$stmt->EOF) {
			$contents = $stmt->fields['field'];

			if ($subwhere != '') {
				$query = "SELECT COUNT(*) AS `cnt` FROM $subtable WHERE $subwhere = ?";
			} else {
				return $result;
			}

			$subres = exec_query($query, $contents);
			$result += $subres->fields['cnt'];
			$stmt->moveNext();
		}
	}

	return $result;
}

/**
 * Must be documented
 *
 * @param string $field
 * @param string $table
 * @param string $where
 * @param string $value
 * @param string $subfield
 * @param string $subtable
 * @param string $subwhere
 * @param string $a
 * @param string $b
 * @return int
 */
function sub_records_rlike_count($field, $table, $where, $value, $subfield, $subtable, $subwhere, $a, $b)
{

	if ($where != '') {
		$stmt = exec_query("SELECT $field AS `field` FROM $table WHERE $where = ?", $value);
	} else {
		$stmt = execute_query("SELECT $field AS `field` FROM $table");
	}

	$result = 0;

	if (!$stmt->rowCount()) {
		return $result;
	}

	while (!$stmt->EOF) {
		$contents = $stmt->fields['field'];

		if ($subwhere != '') {
			$query = "SELECT COUNT(*) AS `cnt` FROM $subtable WHERE $subwhere RLIKE ?";
		} else {
			return $result;
		}

		$stmt2 = exec_query($query, $a . $contents . $b);
		$result += $stmt2->fields['cnt'];
		$stmt->moveNext();
	}

	return $result;
}

/***********************************************************************************************************************
 * Reseller related functions
 */

/**
 * Returns properties for the given reseller
 *
 * @throws iMSCP_Exception When reseller properties are not found
 * @param int $resellerId Reseller unique identifier
 * @param bool $forceReload Whether or not force properties reload from database
 * @return array
 */
function imscp_getResellerProperties($resellerId, $forceReload = false)
{
	static $properties = null;

	if (null === $properties || $forceReload) {
		$resellerId = (int)$resellerId;
		$query = 'SELECT * FROM `reseller_props` WHERE `reseller_id` = ? LIMIT 1';
		$stmt = exec_query($query, $resellerId);

		if (!$stmt->rowCount()) {
			throw new iMSCP_Exception(tr('Properties for reseller with ID %d were not found in database.', $resellerId));
		}

		$properties = $stmt->fetchRow();
	}

	return $properties;
}

/**
 * Update reseller properties
 *
 * @param  int $reseller_id Reseller unique identifier.
 * @param  array $props Array that contain new properties values
 * @return iMSCP_Database_ResultSet|null
 */
function update_reseller_props($reseller_id, $props)
{
	if (empty($props)) {
		return null;
	}

	list(
		$dmn_current, $dmn_max, $sub_current, $sub_max, $als_current, $als_max, $mail_current, $mail_max, $ftp_current,
		$ftp_max, $sql_db_current, $sql_db_max, $sql_user_current, $sql_user_max, $traff_current, $traff_max,
		$disk_current, $disk_max
	) = explode(';', $props);

	$query = "
		UPDATE
			`reseller_props`
		SET
			`current_dmn_cnt` = ?, `max_dmn_cnt` = ?, `current_sub_cnt` = ?, `max_sub_cnt` = ?, `current_als_cnt` = ?,
			`max_als_cnt` = ?, `current_mail_cnt` = ?, `max_mail_cnt` = ?, `current_ftp_cnt` = ?, `max_ftp_cnt` = ?,
			`current_sql_db_cnt` = ?, `max_sql_db_cnt` = ?, `current_sql_user_cnt` = ?, `max_sql_user_cnt` = ?,
			`current_traff_amnt` = ?, `max_traff_amnt` = ?, `current_disk_amnt` = ?, `max_disk_amnt` = ?
		WHERE
			`reseller_id` = ?
	";

	$stmt = exec_query(
		$query,
		array(
			$dmn_current, $dmn_max, $sub_current, $sub_max, $als_current, $als_max, $mail_current, $mail_max, $ftp_current,
			$ftp_max, $sql_db_current, $sql_db_max, $sql_user_current, $sql_user_max, $traff_current, $traff_max,
			$disk_current, $disk_max, $reseller_id
		)
	);

	return $stmt;
}

/**
 * Synchronize hosting plans of the given reseller according its limits and permissions
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param int $resellerId Reseller unique identifier
 * @return void
 * TODO integrate (not tested/used yet)
 */
/*
function syncHostingPlans($resellerId)
{
	$query = "SELECT `props` FROM `hosting_plans` WHERE `reseller_id` = ?";
	$stmt = exec_query($query, $resellerId);

	if($stmt->rowCount()) {
		$query = "SELECT * FROM `reseller_props` WHERE `reseller_id` = ?";
		$stmt = exec_query($query, $resellerId);

		// Reseller properties
		$rp = $stmt->fetchRow(PDO::FETCH_ASSOC);

		while(!$stmt->EOF) {
			list(
				$php, $cgi, $sub, $als, $mail, $ftp, $sqlD, $sqlU, $traffic, $disks, $backup, $customDns, $aps,
				$phpEditor, $phpAllowUrlFopen, $phpDisplayErrors, $phpDisableFunctions, $phpPostMaxSize,
				$phpUploadMaxFilesize, $phpMaxExecutionTime, $phpMaxInputTime, $phpMemoryLimit, $extMailServer, $protectedWebFolders
				) = explode(';', $stmt->fields['props']);

			// Synced hosting plan properties
			$sp = array();

			$sp[] = $php; // Always available for reseller
			$sp[] = $cgi; // Always available for reseller
			$sp[] = ($rp['max_sub_cnt'] != 0 && $sub > $rp['max_sub_cnt']) ? $rp['max_sub_cnt'] : $sub;
			$sp[] = ($rp['max_als_cnt'] != 0 && $als > $rp['max_als_cnt']) ? $rp['max_als_cnt'] : $als;
			$sp[] = ($rp['max_mail_cnt'] != 0 && $mail > $rp['max_mail_cnt']) ? $rp['max_mail_cnt'] : $mail;
			$sp[] = ($rp['max_ftp_cnt'] != 0 && $ftp > $rp['max_ftp_cnt']) ? $rp['max_ftp_cnt'] : $ftp;
			$sp[] = ($rp['max_sql_db_cnt'] != 0 && $sqlD > $rp['max_sql_db_cnt']) ? $rp['max_sql_db_cnt'] : $sqlD;
			$sp[] = ($rp['max_sql_user_cnt'] != 0 && $sqlU > $rp['max_sql_user_cnt']) ? $rp['max_sql_user_cnt'] : $sqlU;
			$sp[] = ($rp['max_traff_amnt'] != 0 && $traffic > $rp['max_traff_amnt']) ? $rp['max_traff_amnt'] : $traffic;
			$sp[] = ($rp['max_disk_amnt'] != 0 && $disks > $rp['max_disk_amnt']) ? $rp['max_disk_amnt'] : $disks;
			$sp[] = $backup; // Always available for reseller (TODO can be disabled at system wide by admin)
			$sp[] = $customDns; // Always available for reseller
			$sp[] = $rp['software_allowed'] == 'yes' ? $aps : '_no_';
			$sp[] = ($rp['php_ini_system'] == 'yes') ? $phpEditor : 'no';
			$sp[] = ($rp['php_ini_al_allow_url_fopen'] == 'yes') ? $phpAllowUrlFopen : 'no';
			$sp[] = ($rp['php_ini_al_display_errors'] == 'yes') ? $phpDisplayErrors : 'no';
			$sp[] = ($rp['php_ini_al_disable_functions'] == 'yes') ? $phpDisableFunctions : 'no';
			$sp[] = ($phpPostMaxSize <= $rp['php_ini_max_post_max_size'])
				? $phpPostMaxSize : $rp['php_ini_max_post_max_size'];
			$sp[] = ($phpUploadMaxFilesize <= $rp['php_ini_max_upload_max_filesize'])
				? $phpUploadMaxFilesize : $rp['php_ini_max_upload_max_filesize'] ;
			$sp[] = ($phpMaxExecutionTime <= $rp['php_ini_max_max_execution_time'])
				? $phpMaxExecutionTime : $rp['php_ini_max_max_execution_time'];
			$sp[] = ($phpMaxInputTime <= $rp['php_ini_max_max_input_time'])
				? $phpMaxInputTime : $rp['php_ini_max_max_input_time'];
			$sp[] = ($phpMemoryLimit <= $rp['php_ini_max_memory_limit'])
				? $phpMemoryLimit : $rp['php_ini_max_memory_limit'];
			$sp[] = $extMailServer; // Always available for reseller
			$sp[] = $protectedWebFolders; // Always available for reseller

			$query = "UPDATE `hosting_plans` SET `props` = ? WHERE id = ?";
			exec_query($query, array(implode(';', $sp), $stmt->fields['id']));

			$stmt->moveNext();
		}
	}
}
*/

/***********************************************************************************************************************
 * Mail functions
 */

/**
 * Encode a string to be valid as mail header
 *
 * @source php.net/manual/en/function.mail.php
 *
 * @param string $string String to be encoded [should be in the $charset charset]
 * @param string $charset OPTIONAL charset in that string will be encoded
 * @return string encoded string
 */
function encode_mime_header($string, $charset = 'UTF-8')
{
	$string = (string)$string;

	if ($string && $charset) {
		if(function_exists('mb_encode_mimeheader')) {
			$string = mb_encode_mimeheader($string, $charset, 'Q', "\r\n", 8);
		} elseif ($string && $charset) {
			// define start delimiter, end delimiter and spacer
			$end = '?=';
			$start = '=?' . $charset . '?B?';
			$spacer = $end . "\r\n " . $start;

			// Determine length of encoded text withing chunks and ensure length is even
			$length = 75 - strlen($start) - strlen($end);
			$length = floor($length / 4) * 4;

			// Encode the string and split it into chunks with spacers after each chunk
			$string = base64_encode($string);
			$string = chunk_split($string, $length, $spacer);

			// Remove trailing spacer and add start and end delimiters
			$spacer = preg_quote($spacer);
			$string = preg_replace('/' . $spacer . '$/', '', $string);
			$string = $start . $string . $end;
		}
	}

	return $string;
}

/**
 * Synchronizes mailboxes quota that belong to the given domain using the given quota limit
 *
 * Algorythm:
 *
 * 1. In case the new quota limit is 0 (unlimited), equal or bigger than the sum of current quotas, we do nothing
 * 2. We have a running total, which start at zero
 * 3. We divide the quota of each mailbox by the sum of current quotas, then we multiply the result by the new quota limit
 * 4. We store the original value of the running total elsewhere, then we add the amount we have just calculated in #3
 * 5. We ensure that new quota is a least 1 MiB (each mailbox must have 1 MiB minimum quota)
 * 5. We round both old value and new value of the running total to integers, and take the difference
 * 6. We update the mailbox quota result calculated in step 5
 * 7. We repeat steps 3-6 for each quota
 *
 * This algorythm guarantees to have the total amount prorated equal to the sum of all quota after update. It also
 * ensure that each mailboxes has 1 MiB quota minimum.
 *
 * Note:  For the sum calculation of current quotas, we consider that a mailbox with a value equal to 0 (unlimited) is
 * equal to the new quota limit.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param int $domainId Customer main domain unique identifier
 * @param int $newQuota New quota limit in bytes
 * @return void
 */
function sync_mailboxes_quota($domainId, $newQuota)
{
	if ($newQuota != 0) {
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		$stmt = exec_query(
			'SELECT `mail_id`, `quota` FROM `mail_users` WHERE `domain_id` = ? AND `quota` IS NOT NULL', $domainId
		);

		if($stmt->rowCount()) {
			$mailboxes = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$totalQuota = 0;

			foreach ($mailboxes as $mailbox) {
				$totalQuota += ($mailbox['quota'] == 0) ? $newQuota : $mailbox['quota'];
			}

			$totalQuota /= 1048576;
			$newQuota /= 1048576;

			if (
				$newQuota < $totalQuota || (isset($cfg['EMAIL_QUOTA_SYNC_MODE']) && $cfg['EMAIL_QUOTA_SYNC_MODE']) ||
				$totalQuota == 0
			) {
				$db = iMSCP_Database::getRawInstance();
				$stmt = $db->prepare('UPDATE `mail_users` SET `quota` = ? WHERE `mail_id` = ?');
				$result = 0;

				foreach ($mailboxes as $mailbox) {
					$oldResult = $result;
					$mailboxQuota = (($mailbox['quota']) ? $mailbox['quota'] / 1048576  : $newQuota);
					$result += $newQuota * $mailboxQuota / $totalQuota;
					if($result < 1) $result = 1;
					$stmt->execute(array(((int)$result - (int)$oldResult) * 1048576, $mailbox['mail_id']));
				}
			}
		}
	}
}

/***********************************************************************************************************************
 * Utils functions
 */

/**
 * Redirect to the given location
 *
 * @param string $location URL to redirect to
 * @return void
 */
function redirectTo($location)
{
	header('Location: ' . $location);
	exit;
}

/**
 * Should be documented
 *
 * @param  $array
 * @param bool $asPath
 * @return string
 */
function array_decode_idna($array, $asPath = false)
{
	if ($asPath && !is_array($array)) {
		return implode('/', array_decode_idna(explode('/', $array)));
	}

	foreach ($array as $k => $v) {
		$arr[$k] = decode_idna($v);
	}

	return $array;
}

/**
 * Must be documented
 *
 * @param array $array Indexed array that containt
 * @param bool $asPath
 * @return string
 */
function array_encode_idna($array, $asPath = false)
{
	if ($asPath && !is_array($array)) {
		return implode('/', array_encode_idna(explode('/', $array)));
	}

	foreach ($array as $k => $v) {
		$array[$k] = encode_idna($v);
	}

	return $array;
}

/**
 * Convert a domain name or email to IDNA ASCII form
 *
 * @param  string String to convert
 * @return bool|string String encoded in ASCII-compatible form or FALSE on failure
 */
function encode_idna($string)
{
	$idn = new idna_convert(array('idn_version' => '2008'));

	return $idn->encode($string);
}

/**
 * Convert a domain name or email from IDNA ASCII to Unicode
 *
 * @param  string String to convert
 * @return bool|string Unicode string or FALSE on failure.
 */
function decode_idna($string)
{

	$idn = new idna_convert(array('idn_version' => '2008'));

	return $idn->decode($string);
}

/**
 * Utils function to upload file
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param string $inputFieldName upload input field name
 * @param string|Array $destPath Destination path string or an array where the first item is an anonymous function to
 *                               run before moving file and any other items the arguments passed to the anonymous
 *                               function. The anonymous function must return a string that is the destination path or
 *                               FALSE on failure.
 *
 * @return string|bool File destination path on success, FALSE otherwise
 */
function utils_uploadFile($inputFieldName, $destPath)
{
	if (isset($_FILES[$inputFieldName]) && $_FILES[$inputFieldName]['error'] == UPLOAD_ERR_OK) {
		$tmpFilePath = $_FILES[$inputFieldName]['tmp_name'];

		if (!is_readable($tmpFilePath)) {
			set_page_message(tr('File is not readable.'), 'error');
			return false;
		}

		if (!is_string($destPath) && is_array($destPath)) {
			if (!($destPath = call_user_func_array(array_shift($destPath), $destPath))) {
				return false;
			}
		}

		if (!@move_uploaded_file($tmpFilePath, $destPath)) {
			set_page_message(tr('Unable to move file.'), 'error');
			return false;
		}
	} else {
		switch ($_FILES[$inputFieldName]['error']) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				set_page_message(tr('File exceeds the size limit.'), 'error');
				break;
			case UPLOAD_ERR_PARTIAL:
				set_page_message(tr('The uploaded file was only partially uploaded.'), 'error');
				break;
			case UPLOAD_ERR_NO_FILE:
				set_page_message(tr('No file was uploaded.'), 'error');
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				set_page_message(tr('Temporary folder not found.'), 'error');
				break;
			case UPLOAD_ERR_CANT_WRITE:
				set_page_message(tr('Failed to write file to disk.'), 'error');
				break;
			case UPLOAD_ERR_EXTENSION:
				set_page_message(tr('A PHP extension stopped the file upload.'), 'error');
				break;
			default:
				set_page_message(tr('An unknown error occurred during file upload: %s', $_FILES[$inputFieldName]['error']), 'error');
		}

		return false;
	}

	return $destPath;
}

/**
 * Generates a random string
 *
 * @param int $length random string length
 * @return array|string
 */
function utils_randomString($length = 10)
{
	$base = 'ABCDEFGHKLMNOPQRSTWXYZabcdefghjkmnpqrstwxyz123456789';
	$max = strlen($base) - 1;
	$string = '';

	mt_srand((double)microtime() * 1000000);

	while (strlen($string) < $length + 1) {
		$string .= $base{mt_rand(0, $max)};
	}

	return $string;
}

/**
 * Returns Upload max file size in bytes
 *
 * @return int Upload max file size in bytes
 */
function utils_getMaxFileUpload()
{
	$uploadMaxFilesize = utils_getPhpValueInBytes(ini_get('upload_max_filesize'));
	$postMaxSize = utils_getPhpValueInBytes(ini_get('post_max_size'));
	$memoryLimit = utils_getPhpValueInBytes(ini_get('memory_limit'));

	return min($uploadMaxFilesize, $postMaxSize, $memoryLimit);
}

/**
 * Returns PHP directive value in bytes
 *
 * Note: If $value do not come with shorthand byte value, the value is retured as this.
 * See http://fr2.php.net/manual/en/faq.using.php#faq.using.shorthandbytes for further explaination
 *
 * @throws iMSCP_Exception
 * @param int|string PHP directive value
 * @return int Value in bytes
 */
function utils_getPhpValueInBytes($value)
{
	$val = trim($value);
	$last = strtolower($val[strlen($value)-1]);

	switch($last) {
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
		break;
	}

	return $val;
}

/**
 * Remove the given directory recusively
 *
 * @param string $directory Path of directory to remove
 * @return boolean TRUE on success, FALSE otherwise
 */
function utils_removeDir($directory)
{
	$directory = rtrim($directory, '/');

	if (! file_exists($directory) || ! is_dir($directory)) {
		return false;
	} elseif (is_readable($directory)) {
		$handle = opendir($directory);

		while (false !== ($item = readdir($handle))) {
			if ($item != '.' && $item != '..') {
				$path = $directory . '/' . $item;

				if (is_dir($path)) {
					utils_removeDir($path);
				} else {
					@unlink($path);
				}
			}
		}

		closedir($handle);

		if (!@rmdir($directory)) {
			return false;
		}
	}

	return true;
}

/**
 * Merge two arrays
 *
 * For duplicate keys, the following is done:
 *  - Nested arrays are recursively merged
 *  - Items in $array2 with INTEGER keys are appended
 *  - Items in $array2 with STRING keys overwrite current values
 *
 * @param array $array1
 * @param array $array2
 * @return array
 */
function utils_arrayMergeRecursive(array $array1, array $array2)
{
	foreach ($array2 as $key => $value) {
		if (array_key_exists($key, $array1)) {
			if (is_int($key)) {
				$array1[] = $value;
			} elseif (is_array($value) && is_array($array1[$key])) {
				$array1[$key] = utils_arrayMergeRecursive($array1[$key], $value);
			} else {
				$array1[$key] = $value;
			}
		} else {
			$array1[$key] = $value;
		}
	}

	return $array1;
}

/**
 * Compares array1 against array2 (recursively) and returns the difference
 *
 * @param array $array1 The array to compare from
 * @param array $array2 An array to compare against
 * @return array An array containing all the entries from array1 that are not present in $array2.
 */
function utils_arrayDiffRecursive(array $array1, array $array2)
{
	$diff = array();

	foreach ($array1 as $key => $value) {
		if (array_key_exists($key, $array2)) {
			if (is_array($value)) {
				$arrDiff = utils_arrayDiffRecursive($value, $array2[$key]);

				if (count($arrDiff)) {
					$diff[$key] = $arrDiff;
				}
			} elseif ($value != $array2[$key]) {
				$diff[$key] = $value;
			}
		} else {
			$diff[$key] = $value;
		}
	}

	return $diff;
}

/***********************************************************************************************************************
 * Checks functions
 */

/**
 * Checks if all of the characters in the provided string are numerical
 *
 * @param string $number string to be checked
 * @return bool TRUE if all characters are numerical, FALSE otherwise
 */
function is_number($number)
{
	return (bool)preg_match('/^[0-9]+$/D', $number);
}

/**
 * Checks if all of the characters in the provided string match like a basic string.
 *
 * @param  $string string to be checked
 * @return bool TRUE if all characters match like a basic string, FALSE otherwise
 */
function is_basicString($string)
{
	return (bool)preg_match('/^[\w\-]+$/D', $string);
}

/**
 * Is a XMLHttpRequest request?
 *
 * Returns true if the request‘s "X-Requested-With" header contains "XMLHttpRequest".
 *
 * Note: jQuery and Prototype Javascript libraries sends this header with every Ajax request.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @return boolean  TRUE if the request‘s "X-Requested-With" header contains "XMLHttpRequest", FALSE otherwise
 */
function is_xhr()
{
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && stristr($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') !== false) {
		return true;
	}

	return false;
}

/**
 * Check if a data is serialized.
 *
 * @author Laurent Declercq (nuxwin) <l.declercq@nuxwin.com>
 * @param string $data Data to be checked
 * @return boolean TRUE if serialized data, FALSE otherwise
 */
function isSerialized($data)
{
	if (!is_string($data)) {
		return false;
	}

	$data = trim($data);

	if ('N;' == $data) {
		return true;
	}

	if (preg_match("/^[aOs]:[0-9]+:.*[;}]\$/s", $data) ||
		preg_match("/^[bid]:[0-9.E-]+;\$/", $data)
	) {
		return true;
	}

	return false;
}

/**
 * Check if the given string look like json data
 *
 * @author Laurent Declercq (nuxwin) <l.declercq@nuxwin.com>
 * @param $string $string $string to be checked
 * @return boolean TRUE if the given string look like json data, FALSE otherwise
 */
function isJson($string)
{
	json_decode($string);
	return (json_last_error() == JSON_ERROR_NONE);
}

/***********************************************************************************************************************
 * Accounting related functions
 */

/**
 * Return usage in percent
 *
 * @param  int $amount Current value
 * @param  int $total (0 = unlimited)
 * @return int Usage in percent
 */
function make_usage_vals($amount, $total)
{
	return ($total) ? sprintf('%.2f', (($percent = ($amount / $total) * 100)) > 100 ? 100 : $percent) : 0;
}

/**
 * Generates customer statistiques
 *
 * @param int $domainId Domain unique identifier
 * @return array An array that contains traffic usage information
 */
function shared_getCustomerStats($domainId)
{
	$curMonth = date('m');
	$curYear = date('Y');

	$fromTimestamp = mktime(0, 0, 0, $curMonth, 1, $curYear);

	if ($curMonth == 12) {
		$toTImestamp = mktime(0, 0, 0, 1, 1, $curYear + 1);
	} else {
		$toTImestamp = mktime(0, 0, 0, $curMonth + 1, 1, $curYear);
	}

	$stmt = exec_query(
		'
			SELECT
				domain_id, IFNULL(domain_disk_usage, 0) AS diskspace_usage,
				IFNULL(domain_traffic_limit, 0) AS monthly_traffic_limit,
				IFNULL(domain_disk_limit, 0) AS diskspace_limit,
				domain_name
			FROM
				domain
			WHERE
				domain_id = ?
			ORDER BY
				domain_name
		',
		$domainId
	);

	if (!$stmt->rowCount()) {
		showBadRequestErrorPage();
		exit;
	} else {
		$data = $stmt->fetchRow(PDO::FETCH_ASSOC);

		$diskspaceUsage = $data['diskspace_usage'];
		$monthlyTrafficLimit = $data['monthly_traffic_limit'];
		$diskspaceLimit = $data['diskspace_limit'];
		$domainName = $data['domain_name'];

		$stmt = exec_query(
			'
				SELECT
					IFNULL(SUM(dtraff_web), 0) AS webTraffic,
					IFNULL(SUM(dtraff_ftp), 0) AS ftpTraffic,
					IFNULL(SUM(dtraff_mail), 0) AS smtpTraffic,
					IFNULL(SUM(dtraff_pop), 0) AS popTraffic,
					IFNULL(SUM(dtraff_web), 0) + IFNULL(SUM(dtraff_ftp), 0) +
					IFNULL(SUM(dtraff_mail), 0) + IFNULL(SUM(dtraff_pop), 0) AS totalTraffic
				FROM
					domain_traffic
				WHERE
					domain_id = ?
				AND
					dtraff_time >= ?
				AND
					dtraff_time < ?
			',
			array($domainId, $fromTimestamp, $toTImestamp)
		);

		$data = $stmt->fetchRow(PDO::FETCH_ASSOC);

		return array(
			$domainName, $domainId, $data['webTraffic'], $data['ftpTraffic'], $data['smtpTraffic'], $data['popTraffic'],
			$data['totalTraffic'], $diskspaceUsage, $monthlyTrafficLimit, $diskspaceLimit
		);
	}
}

/**
 * Must be documented
 *
 * @param  $value
 * @param  $value_max
 * @param  $bar_width
 * @return int
 * @deprecated
 */
function calc_bar_value($value, $value_max, $bar_width)
{
	if ($value_max == 0) {
		return 0;
	} else {
		$ret_value = ($value * $bar_width) / $value_max;
		return ($ret_value > $bar_width) ? $bar_width : $ret_value;
	}
}

/***********************************************************************************************************************
 * Logging related functions
 */

/**
 * Writes a log message in the database and sends it to the administrator by email according log level
 *
 * @param string $msg Message to log
 * @param int $logLevel Log level Loggin level from which log is sent via mail
 * @return void
 */
function write_log($msg, $logLevel = E_USER_WARNING)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$clientIp = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : 'unknown';

	$msg = replace_html($msg . '<br /><small>User IP: ' . $clientIp . '</small>', ENT_COMPAT, tr('encoding'));

	exec_query("INSERT INTO `log` (`log_time`,`log_message`) VALUES(NOW(), ?)", $msg);

	$msg = strip_tags(str_replace('<br />', "\n", $msg));
	$to = isset($cfg->DEFAULT_ADMIN_ADDRESS) ? $cfg->DEFAULT_ADMIN_ADDRESS : '';

	if ($to != '' && $logLevel <= $cfg->LOG_LEVEL) {
		$hostname = isset($cfg->SERVER_HOSTNAME) ? $cfg->SERVER_HOSTNAME : '';
		$baseServerIp = isset($cfg->BASE_SERVER_IP) ? $cfg->BASE_SERVER_IP : '';
		$version = isset($cfg->Version) ? $cfg->Version : 'unknown';
		$buildDate = isset($cfg->BuildDate) ? $cfg->BuildDate : 'unknown';
		$subject = "i-MSCP $version on $hostname ($baseServerIp)";

		if ($logLevel == E_USER_NOTICE) {
			$severity = 'Notice (You can ignore this message)';
		} elseif ($logLevel == E_USER_WARNING) {
			$severity = 'Warning';
		} elseif ($logLevel == E_USER_ERROR) {
			$severity = 'Error';
		} else {
			$severity = 'Unknown';
		}

		$message = <<<AUTO_LOG_MSG

i-MSCP Log

Server: $hostname ($baseServerIp)
Version: i-MSCP $version ($buildDate)
Message severity: $severity

Message: ----------------[BEGIN]--------------------------

$msg

Message: ----------------[END]----------------------------

_________________________
i-MSCP Log Mailer

Note: If you want no longer receive messages for this log
level, you can change it via the settings page.

AUTO_LOG_MSG;

		$headers = "From: \"i-MSCP Logging Mailer\" <" . $to . ">\n";
		$headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\n";
		$headers .= "Content-Transfer-Encoding: 7bit\n";
		$headers .= "X-Mailer: i-MSCP Mailer";

		if (!mail($to, $subject, $message, $headers)) {
			$log_message = "Logging Mailer Mail To: |$to|, From: |$to|, Status: |NOT OK|!";
			exec_query("INSERT INTO `log` (`log_time`,`log_message`) VALUES(NOW(), ?)", $log_message, false);
		}
	}
}

/**
 * Send add user email
 *
 * @param int $adminId Admin unique identifier
 * @param string $uname Username
 * @param string $upass User password
 * @param string $uemail User email
 * @param string $ufname User firstname
 * @param string $ulname User lastname
 * @param string $utype User type
 * @return void
 */
function send_add_user_auto_msg($adminId, $uname, $upass, $uemail, $ufname, $ulname, $utype)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$data = get_welcome_email($adminId, $_SESSION['user_type']);

	if ($data['sender_name']) {
		$from = encode_mime_header($data['sender_name']) . " <{$data['sender_email']}>";
	} else {
		$from = $data['sender_email'];
	}

	if ($ufname && $ulname) {
		$to = encode_mime_header($ufname . ' ' . $ulname) . " <$uemail>";
		$name = "$ufname $ulname";
	} else {
		$name = $uname;
		$to = $uemail;
	}

	$search = array();
	$replace = array();

	$search [] = '{USERNAME}';
	$replace[] = decode_idna($uname);

	$search [] = '{USERTYPE}';
	$replace[] = $utype;

	$search [] = '{NAME}';
	$replace[] = decode_idna($name);

	$search [] = '{PASSWORD}';
	$replace[] = $upass;

	$search [] = '{BASE_SERVER_VHOST}';
	$replace[] = $cfg->BASE_SERVER_VHOST;

	$search [] = '{BASE_SERVER_VHOST_PREFIX}';
	$replace[] = $cfg->BASE_SERVER_VHOST_PREFIX;

	$search[] = '{WEBSTATS_RPATH}';
	$replace[] = $cfg->WEBSTATS_RPATH;

	$data['subject'] = str_replace($search, $replace, $data['subject']);
	$message = str_replace($search, $replace, $data['message']);

	$headers = "From: $from\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
	$headers .= "Content-Transfer-Encoding: 8bit\r\n";
	$headers .= "X-Mailer: i-MSCP Mailer";

	$mailStatus = mail($to, encode_mime_header($data['subject']), $message, $headers, "-f {$data['sender_email']}")
		? 'OK' : 'NOT OK';

	$name = tohtml($name);
	$fromName = tohtml($data['sender_name']);
	
	$logEntry = (!$fromName) ? $data['sender_email'] : "$fromName - {$data['sender_email']}";

	write_log(
		"{$_SESSION['user_logged']}: Auto Add User To: |$name - $uemail |, From: |$logEntry|, Status: |$mailStatus|!",
		E_USER_NOTICE
	);
}

/***********************************************************************************************************************
 * Softwares installer functions
 */

/**
 * Returns client software permissions
 *
 * @throws iMSCP_Exception in case softwares installer permissions of the given user cannot be retrieved
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  int $userId User unique identifier
 * @return void
 */
function get_client_software_permission($tpl, $userId)
{
	$query = "SELECT `domain_software_allowed`, `domain_ftpacc_limit` FROM `domain` WHERE `domain_admin_id` = ?";
	$stmt = exec_query($query, array($userId));

	if($stmt->recordCount()) {
		if ($stmt->fields('domain_software_allowed') == 'yes' && $stmt->fields('domain_ftpacc_limit') != '1') {
			$tpl->assign(
				array(
					'SOFTWARE_SUPPORT' => tr('yes'),
					'TR_SOFTWARE_MENU' => tr('Software installer'),
					'SOFTWARE_MENU' => tr('yes'),
					'TR_INSTALLATION' => tr('Installation details'),
					'TR_INSTALLATION_INFORMATION' => tr('Please set now the username and password for the later login in the software. (Required fiels)'),
					'TR_INSTALL_USER' => tr('Login username'),
					'TR_INSTALL_PWD' => tr('Login password'),
					'TR_INSTALL_EMAIL' => tr('Email address'),
					'SW_MSG' => tr('Enabled'),
					'SW_ALLOWED' => tr('Software installer'),
					'TR_SOFTWARE_DESCRIPTION' => tr('Software Description')
				)
			);

			$tpl->parse('T_SOFTWARE_SUPPORT', '.t_software_support');
		} else {
			$tpl->assign(
				array(
					'T_SOFTWARE_SUPPORT' => '',
					'T_SOFTWARE_MENU' => '',
					'SOFTWARE_ITEM' => '',
					'TR_INSTALLATION' => tr('You do not have permission to install software yet'),
					'TR_SOFTWARE_DESCRIPTION' => tr('You do not have permission to install software yet'),
					'SW_MSG' => tr('Disabled'),
					'SW_ALLOWED' => tr('Software installer')
				)
			);
		}
	} else {
		throw new iMSCP_Exception('Unable to retrieve software installer permissions for the given user');
	}
}

/**
 * Whether or not the given reseller is allowed to use the software installer
 *
 * @throws iMSCP_Exception in case properties for the given reseller are not found
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  int $resellerId Reseller unique identifier
 * @return void
 */
function get_reseller_software_permission($tpl, $resellerId)
{
	$query = "SELECT `software_allowed` FROM `reseller_props` WHERE `reseller_id` = ?";
	$stmt = exec_query($query, array($resellerId));

	if($stmt->rowCount()) {
		if ($stmt->fields('software_allowed') == 'yes') {
			$tpl->assign(
				array(
					'SOFTWARE_SUPPORT' => tr('yes'),
					'SW_ALLOWED' => tr('Software installer'),
					'SW_MSG' => tr('enabled')
				)
			);
			$tpl->parse('T_SOFTWARE_SUPPORT', 't_software_support');
		} else {
			$tpl->assign(
				array(
					'SOFTWARE_SUPPORT' => tr('no'),
					'SW_ALLOWED' => tr('Software installer'),
					'SW_MSG' => tr('disabled'),
					'T_SOFTWARE_SUPPORT' => ''
				)
			);
		}
	} else {
		throw new iMSCP_Exception('Unable to found properties of the given reseller');
	}
}

/**
 * Get all software installer options
 *
 * @author Sascha Bay (TheCry) <sascha.bay@i-mscp.net>
 * @throws iMSCP_Exception in case software installer options cannot be retrieved
 * @return array An array containing software installer options
 */
function get_application_installer_conf()
{
	$query = "SELECT * FROM `web_software_options`";
	$stmt = execute_query($query);

	if($stmt->recordCount()) {
		return array(
			$stmt->fields['use_webdepot'], $stmt->fields['webdepot_xml_url'], $stmt->fields['webdepot_last_update']
		);
	} else {
		throw new iMSCP_Exception('Unable to retrieve software installer options in database');
	}
}

/**
 * Check wheter the package is still installed this system
 *
 * @throws iMSCP_Exception in case the given user cannot be retrieved in database
 * @param string $packageInstallType Package install type
 * @param string $packageName Package name
 * @param string $packageVersion Package version
 * @param string $packageLanguage Package language
 * @param int $userId User unique identifier
 * @return array
 */
function check_package_is_installed($packageInstallType, $packageName, $packageVersion, $packageLanguage, $userId)
{
	$query = "SELECT `admin_type` FROM `admin` WHERE `admin_id` = ?";
	$stmt = exec_query($query, $userId);

	if ($stmt->recordCount()) {
		if ($stmt->fields['admin_type'] == 'admin') {
			$query = "
				SELECT
					`software_id`
				FROM
					`web_software`
				WHERE
					`software_installtype`  = ?
				AND
					`software_name` = ?
				AND
					`software_version` = ?
				AND
					`software_language` = ?
				AND
					`software_depot` = 'no'
			";
		} else {
			$query = "
				SELECT
					`software_id`
				FROM
					`web_software`
				WHERE
					`software_installtype` = ?
				AND
					`software_name` = ?
				AND
					`software_version`= ?
				AND
					`software_language` = ?
				AND
					`reseller_id` = '" . $userId . "'
				AND
					`software_depot` = 'no'
			";
		}
		$stmt = exec_query($query, array($packageInstallType, $packageName, $packageVersion, $packageLanguage ));
		$softwaresCount = $stmt->recordCount();

		$query = "
			SELECT
				`software_id`
			FROM
				`web_software`
			WHERE
				`software_installtype`  = ?
			AND
				`software_name` = ?
			AND
				`software_version` = ?
			AND
				`software_language` = ?
			AND
				`software_master_id` = '0'
			AND
				`software_depot` = 'yes'
		";
		$stmt = exec_query($query, array($packageInstallType, $packageName, $packageVersion, $packageLanguage));
		$softwaresCountDepot = $stmt->recordCount();

		if ($softwaresCount || $softwaresCountDepot) {
			if ($softwaresCount) {
				return array(true, 'reseller');
			} else {
				return array(true, 'sw_depot');
			}
		} else {
			return array(false, 'not_installed');
		}
	} else {
		throw new iMSCP_Exception('Unable to found the given user in database');
	}
}

/**
 * Get all software packages from database since last update from the websoftware
 * depot.
 *
 * @author Sascha Bay (TheCry) <sascha.bay@i-mscp.net>
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $userId User unique identifier
 * @return int
 */
function get_webdepot_software_list($tpl, $userId)
{
	$query = "SELECT * FROM `web_software_depot` ORDER BY `package_install_type` ASC, `package_title` ASC";
	$stmt = execute_query($query);

	if ($stmt->recordCount()) {
		while (!$stmt->EOF) {
			$tpl->assign(
				array(
					'TR_PACKAGE_NAME' => $stmt->fields['package_title'],
					'TR_PACKAGE_TOOLTIP' => $stmt->fields['package_description'],
					'TR_PACKAGE_INSTALL_TYPE' => $stmt->fields['package_install_type'],
					'TR_PACKAGE_VERSION' => $stmt->fields['package_version'],
					'TR_PACKAGE_LANGUAGE' => $stmt->fields['package_language'],
					'TR_PACKAGE_TYPE' => $stmt->fields['package_type'],
					'TR_PACKAGE_VENDOR_HP' => ($stmt->fields['package_vendor_hp'] == '')
						? tr('N/A')
						: '<a href="' . $stmt->fields['package_vendor_hp'] . '" target="_blank">' . tr('Vendor hompage') . '</a>'
				)
			);

			list($isInstalled, $installedOn) = check_package_is_installed(
				$stmt->fields['package_install_type'], $stmt->fields['package_title'], $stmt->fields['package_version'],
				$stmt->fields['package_language'], $userId
			);

			if ($isInstalled) {
				$tpl->assign(
					array(
						'PACKAGE_HTTP_URL' => '',
						'TR_PACKAGE_INSTALL' => ($installedOn == "sw_depot")
							? tr('Installed in software repository')
							: tr('Installed in reseller repository'),
						'TR_MESSAGE_INSTALL' => ''
					)
				);

				$tpl->parse('PACKAGE_INFO_LINK', 'package_info_link');
				$tpl->assign('PACKAGE_INSTALL_LINK', '');
			} else {
				$tpl->assign(
					array(
						'PACKAGE_HTTP_URL' => $stmt->fields['package_download_link'],
						'TR_PACKAGE_INSTALL' => tr('Start installation'),
						'TR_MESSAGE_INSTALL' => tr('Are you sure you want to install this package from the Web software repository?', true)
					)
				);

				$tpl->parse('PACKAGE_INSTALL_LINK', 'package_install_link');
				$tpl->assign('PACKAGE_INFO_LINK', '');
			}

			$tpl->parse('LIST_WEBDEPOTSOFTWARE', '.list_webdepotsoftware');
			$stmt->moveNext();
		}

		$tpl->assign('NO_WEBDEPOTSOFTWARE_LIST', '');
	} else {
		$tpl->assign(
			array(
				'NO_WEBDEPOTSOFTWARE_AVAILABLE' => tr('No software in Web repository found!'),
				'WEB_SOFTWARE_REPOSITORY' => ''
			)
		);
	}

	return $stmt->recordCount();
}

/**
 * Update repository index
 *
 * @param string $repositoryIndexFile Repository index file URI
 * @param string $webRepositoryLastUpdate Web repository last update
 */
function update_webdepot_software_list($repositoryIndexFile, $webRepositoryLastUpdate)
{
	$options = array('http' => array('user_agent' => 'PHP libxml agent'));
	$context = stream_context_create($options);
	libxml_set_streams_context($context);

	$webRepositoryIndexFile = new DOMDocument('1.0', 'UTF-8');
	$webRepositoryIndexFile->load($repositoryIndexFile);
	$webRepositoryIndexFile = simplexml_import_dom($webRepositoryIndexFile);

	if (utf8_decode($webRepositoryIndexFile->LAST_UPDATE->DATE) != $webRepositoryLastUpdate) {
		$truncatequery = 'TRUNCATE TABLE `web_software_depot`';
		exec_query($truncatequery);

		$badSoftwarePackageDefinition = 0;

		foreach ($webRepositoryIndexFile->PACKAGE as $package) {
			if (!empty($package->INSTALL_TYPE) && !empty($package->TITLE) && !empty($package->VERSION) &&
				!empty($package->LANGUAGE) && !empty($package->TYPE) && !empty($package->DESCRIPTION) &&
				!empty($package->VENDOR_HP) && !empty($package->DOWNLOAD_LINK) && !empty($package->SIGNATURE_LINK)
			) {
				$query = '
					INSERT INTO
						`web_software_depot` (
							`package_install_type`, `package_title`, `package_version`, `package_language`,
							`package_type`, `package_description`, `package_vendor_hp`, `package_download_link`,
							`package_signature_link`
						) VALUES (
							?, ?, ?, ?, ?, ?, ?, ?, ?
						)
				';
				exec_query(
					$query,
					array(
						clean_input($package->INSTALL_TYPE), clean_input($package->TITLE), clean_input($package->VERSION),
						clean_input($package->LANGUAGE), clean_input($package->TYPE), clean_input($package->DESCRIPTION),
						encode_idna(strtolower(clean_input($package->VENDOR_HP))),
						encode_idna(strtolower(clean_input($package->DOWNLOAD_LINK))),
						encode_idna(strtolower(clean_input($package->SIGNATURE_LINK)))
					)
				);
			} else {
				$badSoftwarePackageDefinition++;
				break;
			}
		}
		if(!$badSoftwarePackageDefinition) {
			exec_query(
				'UPDATE `web_software_options` SET `webdepot_last_update` = ?',
				array($webRepositoryIndexFile->LAST_UPDATE->DATE)
			);
			set_page_message(tr('Web software repository index been successfully updated.'), 'success');
		} else {
			set_page_message(tr('Update of Web software repository index has been aborted. Missing or empty fields.'), 'error');
		}
	} else {
		set_page_message(tr('Web software repository index is already up to date.'), 'info');
	}
}

/**
 * Returns token
 *
 * @return string
 */
function generate_software_upload_token()
{
	$token = md5(uniqid(microtime(), true));
	$_SESSION['software_upload_token'] = $token;

	return $token;
}

/***********************************************************************************************************************
 * iMSCP daemon related functions
 */

/**
 * Read an answer from i-MSCP daemon
 *
 * @param resource &$socket
 * @return bool TRUE on success, FALSE otherwise
 */
function daemon_readAnswer(&$socket)
{
	if(($answer = @socket_read($socket, 1024, PHP_NORMAL_READ)) !== false) {
		list($code) = explode(' ', $answer);
		if($code == '999') {
			write_log(sprintf('i-MSCP daemon returned an unexpected answer: %s', $answer), E_USER_ERROR);
			return false;
		}
	} else {
		write_log(
			sprintf('Unable to read answer from i-MSCP daemon: %s'. socket_strerror(socket_last_error())), E_USER_ERROR
		);
		return false;
	}

	return true;
}

/**
 * Send a command to i-MSCP daemon
 *
 * @param resource &$socket
 * @param string $command Command
 * @return bool TRUE on success, FALSE otherwise
 */
function daemon_sendCommand(&$socket, $command)
{
	$command .= "\n";
	$commandLength = strlen($command);

	while (true) {
		if (($bytesSent = @socket_write($socket, $command, $commandLength)) !== false) {
			if ($bytesSent < $commandLength) {
				$command = substr($command, $bytesSent);
				$commandLength -= $bytesSent;
			} else {
				return true;
			}
		} else {
			write_log(
				sprintf('Unable to send command to i-MSCP daemon: %s', socket_strerror(socket_last_error())),
				E_USER_ERROR
			);
			return false;
		}
	}

	return false;
}

/**
 * Send a request to the daemon
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function send_request()
{
	if(
		($socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) !== false &&
		@socket_connect($socket, '127.0.0.1', 9876) !== false
	) {
		$cfg = iMSCP_Registry::get('config');

		if(
			daemon_readAnswer($socket) && // Read Welcome message from i-MSCP daemon
			daemon_sendCommand($socket, "helo {$cfg->Version}") && // Send helo command to i-MSCP daemon
			daemon_readAnswer($socket) && // Read answer from i-MSCP daemon
			daemon_sendCommand($socket, 'execute query') && // Send execute query command to i-MSCP daemon
			daemon_readAnswer($socket) && // Read answer from i-MSCP daemon
			daemon_sendCommand($socket, 'bye') && // Send bye command to i-MSCP daemon
			daemon_readAnswer($socket) // Read answer from i-MSCP daemon
		) {
			$ret = true;
		} else {
			$ret = false;
		}

		socket_close($socket);
	} else {
		write_log(sprintf('Unable to send request: %s', socket_strerror(socket_last_error())), E_USER_ERROR);
		$ret = false;
	}

	return $ret;
}

/***********************************************************************************************************************
 * Database related functions
 */

/**
 * Executes a SQL statement.
 *
 * Note: You may pass additional parameters. They will be treated as though you
 * called PDOStatement::setFetchMode() on the resultant statement object that is
 * wrapped by the iMSCP_Database_ResultSet object.
 *
 * @see iMSCP_Database::execute()
 * @throws iMSCP_Exception_Database
 * @param string $query                 Sql statement to be executed
 * @param array|int|string $parameters  OPTIONAL parameters - See iMSCP_Database::execute()
 * @return iMSCP_Database_ResultSet     An iMSCP_Database_ResultSet object
 */
function execute_query($query, $parameters = null)
{
	static $db = null;

	if (null === $db) {
		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');
	}

	try {
		if (null !== $parameters) {
			$parameters = func_get_args();
			array_shift($parameters);
			$stmt = call_user_func_array(array($db, 'execute'), $parameters);
		} else {
			$stmt = $db->execute($query);
		}

		if ($stmt == false) {
			throw new iMSCP_Exception_Database($db->getLastErrorMessage(), $query);
		}

	} catch (PDOException $e) {
		throw new iMSCP_Exception_Database($e->getMessage(), $query, $e->getCode(), $e);
	}

	return $stmt;
}

/**
 * Convenience method to prepare and execute a query.
 *
 * @throws iMSCP_Exception_Database      When query fail
 * @param string $query                  Sql statement
 * @param string|int|array $bind         Data to bind to the placeholders
 * @return iMSCP_Database_ResultSet|null A iMSCP_Database_ResultSet object that represents a result set
 */
function exec_query($query, $bind = null)
{
	static $db = null;

	if (null === $db) {
		$db = iMSCP_Database::getInstance();
	}

	try {
		$stmt = $db->execute($db->prepare($query), $bind);
	} catch (PDOException $e) {
		throw new iMSCP_Exception_Database($e->getMessage(), $query, $e->getCode(), $e);
	}

	return $stmt;
}

/**
 * Quote SQL identifier.
 *
 * Note: An Identifier is essentially a name of a database, table, or table column.
 *
 * @param  string $identifier Identifier to quote
 * @return string quoted identifier
 */
function quoteIdentifier($identifier)
{
	static $db = null;

	if (null === $db) {
		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');
	}

	return $db->quoteIdentifier($identifier);
}

/**
 * Quote value
 *
 * @param mixed $value Value to quote
 * @param int $parameterType Parameter type
 * @return mixed quoted value
 */
function quoteValue($value, $parameterType = PDO::PARAM_STR)
{
	static $db = null;

	if (null === $db) {
		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');
	}

	return $db->quote($value, $parameterType);
}

/***********************************************************************************************************************
 * Unclassified functions
 */

/**
 * Returns a count of items present in a database table with optional search criterias.
 *
 * @param  string $table Table name on which to operate
 * @param  string $where OPTIONAL SQL WHERE clause
 * @param  string $bind OPTIONAL value to bind to the placeholder
 * @return int Items count
 */
function records_count($table, $where = '', $bind = '')
{
	if ($where != '') {
		if ($bind != '') {
			$stmt = exec_query("SELECT COUNT(*) AS `cnt` FROM `$table` WHERE $where = ?", $bind);
		} else {
			$stmt = execute_query("SELECT COUNT(*) AS `cnt` FROM $table WHERE $where");
		}
	} else {
		$stmt = execute_query("SELECT COUNT(*) AS `cnt` FROM `$table`");
	}

	return (int)$stmt->fields['cnt'];
}

/**
 * Unset global variables
 *
 * @return void
 */
function unsetMessages()
{
	$glToUnset = array();
	$glToUnset[] = 'user_updated';
	$glToUnset[] = 'dmn_tpl';
	$glToUnset[] = 'chtpl';
	$glToUnset[] = 'step_one';
	$glToUnset[] = 'step_two_data';
	$glToUnset[] = 'ch_hpprops';
	$glToUnset[] = 'user_add3_added';
	$glToUnset[] = 'user_has_domain';
	$glToUnset[] = 'local_data';
	$glToUnset[] = 'reseller_added';
	$glToUnset[] = 'user_added';
	$glToUnset[] = 'aladd';
	$glToUnset[] = 'edit_ID';
	$glToUnset[] = 'aldel';
	$glToUnset[] = 'hpid';
	$glToUnset[] = 'user_deleted';
	$glToUnset[] = 'hdomain';
	$glToUnset[] = 'aledit';
	$glToUnset[] = 'acreated_by';
	$glToUnset[] = 'dhavesub';
	$glToUnset[] = 'ddel';
	$glToUnset[] = 'dhavealias';
	$glToUnset[] = 'dhavealias';
	$glToUnset[] = 'dadel';
	$glToUnset[] = 'local_data';

	foreach ($glToUnset as $toUnset) {
		if (array_key_exists($toUnset, $GLOBALS)) {
			unset($GLOBALS[$toUnset]);
		}
	}

	$sessToUnset = array();
	$sessToUnset[] = 'reseller_added';
	$sessToUnset[] = 'dmn_name';
	$sessToUnset[] = 'dmn_tpl';
	$sessToUnset[] = 'chtpl';
	$sessToUnset[] = 'step_one';
	$sessToUnset[] = 'step_two_data';
	$sessToUnset[] = 'ch_hpprops';
	$sessToUnset[] = 'user_add3_added';
	$sessToUnset[] = 'user_has_domain';
	$sessToUnset[] = 'user_added';
	$sessToUnset[] = 'aladd';
	$sessToUnset[] = 'edit_ID';
	$sessToUnset[] = 'aldel';
	$sessToUnset[] = 'hpid';
	$sessToUnset[] = 'user_deleted';
	$sessToUnset[] = 'hdomain';
	$sessToUnset[] = 'aledit';
	$sessToUnset[] = 'acreated_by';
	$sessToUnset[] = 'dhavesub';
	$sessToUnset[] = 'ddel';
	$sessToUnset[] = 'dhavealias';
	$sessToUnset[] = 'dadel';
	$sessToUnset[] = 'local_data';

	foreach ($sessToUnset as $toUnset) {
		if (array_key_exists($toUnset, $_SESSION)) {
			unset($_SESSION[$toUnset]);
		}
	}
}

if (!function_exists('http_build_url')) {
	define('HTTP_URL_REPLACE', 1); // Replace every part of the first URL when there's one of the second URL
	define('HTTP_URL_JOIN_PATH', 2); // Join relative paths
	define('HTTP_URL_JOIN_QUERY', 4); // Join query strings
	define('HTTP_URL_STRIP_USER', 8); // Strip any user authentication information
	define('HTTP_URL_STRIP_PASS', 16); // Strip any password authentication information
	define('HTTP_URL_STRIP_AUTH', 32); // Strip any authentication information
	define('HTTP_URL_STRIP_PORT', 64); // Strip explicit port numbers
	define('HTTP_URL_STRIP_PATH', 128); // Strip complete path
	define('HTTP_URL_STRIP_QUERY', 256); // Strip query string
	define('HTTP_URL_STRIP_FRAGMENT', 512); // Strip any fragments (#identifier)
	define('HTTP_URL_STRIP_ALL', 1024); // Strip anything but scheme and host

	/**
	 * Build an URL.
	 *
	 * The parts of the second URL will be merged into the first according to the flags argument.
	 *
	 * @param mixed $url (Part(s) of) an URL in form of a string or associative array like parse_url() returns
	 * @param mixed $parts Same as the first argument
	 * @param int $flags A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
	 * @param bool|array $new_url If set, it will be filled with the parts of the composed url like parse_url() would return
	 * @return string URL
	 */
	function http_build_url($url, $parts = array(), $flags = HTTP_URL_REPLACE, &$new_url = false)
	{
		$keys = array('user', 'pass', 'port', 'path', 'query', 'fragment');

		// HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
		if ($flags & HTTP_URL_STRIP_ALL) {
			$flags |= HTTP_URL_STRIP_USER;
			$flags |= HTTP_URL_STRIP_PASS;
			$flags |= HTTP_URL_STRIP_PORT;
			$flags |= HTTP_URL_STRIP_PATH;
			$flags |= HTTP_URL_STRIP_QUERY;
			$flags |= HTTP_URL_STRIP_FRAGMENT;
		} // HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
		else if ($flags & HTTP_URL_STRIP_AUTH) {
			$flags |= HTTP_URL_STRIP_USER;
			$flags |= HTTP_URL_STRIP_PASS;
		}

		// Parse the original URL
		$parse_url = parse_url($url);

		// Scheme and Host are always replaced
		if (isset($parts['scheme'])) {
			$parse_url['scheme'] = $parts['scheme'];
		}

		if (isset($parts['host'])) {
			$parse_url['host'] = $parts['host'];
		}

		// (If applicable) Replace the original URL with it's new parts
		if ($flags & HTTP_URL_REPLACE) {
			foreach ($keys as $key) {
				if (isset($parts[$key])) {
					$parse_url[$key] = $parts[$key];
				}
			}
		} else {
			// Join the original URL path with the new path
			if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
				if (isset($parse_url['path'])) {
					$parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') .
						'/' . ltrim($parts['path'], '/');
				} else {
					$parse_url['path'] = $parts['path'];
				}
			}

			// Join the original query string with the new query string
			if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
				if (isset($parse_url['query'])) {
					$parse_url['query'] .= '&' . $parts['query'];
				} else {
					$parse_url['query'] = $parts['query'];
				}
			}
		}

		// Strips all the applicable sections of the URL
		// Note: Scheme and Host are never stripped
		foreach ($keys as $key) {
			if ($flags & (int)constant('HTTP_URL_STRIP_' . strtoupper($key))) {
				unset($parse_url[$key]);
			}
		}

		$new_url = $parse_url;

		return
			((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
			. ((isset($parse_url['user']))
				? $parse_url['user'] . ((isset($parse_url['pass']))
					? ':' . $parse_url['pass'] : '') . '@' : '')
			. ((isset($parse_url['host'])) ? $parse_url['host'] : '')
			. ((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
			. ((isset($parse_url['path'])) ? $parse_url['path'] : '')
			. ((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
			. ((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '');
	}
}

/**
 * Returns translation for jQuery DataTables plugin.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @return string
 */
function getDataTablesPluginTranslations()
{
	return json_encode(
		array(
			'sLengthMenu'=> tr(
				'Show %s records per page',
				true,
				'
					<select>
					<option value="5">5</option>
					<option value="10">10</option>
					<option value="15">15</option>
					<option value="20">20</option>
					<option value="50">50</option>
					<option value="-1">'. tr('All', true) . '</option>
					</select>
				'
			),
			//'sLengthMenu' => tr('Show %s records per page', true, '_MENU_'),
			'sZeroRecords' => tr('Nothing found - sorry', true),
			'sInfo' => tr('Showing %s to %s of %s records', true, '_START_', '_END_', '_TOTAL_'),
			'sInfoEmpty' => tr('Showing 0 to 0 of 0 records', true),
			'sInfoFiltered' => tr('(filtered from %s total records)', true, '_MAX_'),
			'sSearch' => tr('Search', true),
			'oPaginate' => array('sPrevious' => tr('Previous', true), 'sNext' => tr('Next', true)),
			'sProcessing' => tr('Processing...', true),
		)
	);
}

/**
 * Show 400 error page
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @throws iMSCP_Exception_Production in case the error page is not found
 * @return void
 */
function showBadRequestErrorPage()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$filePath = $cfg->GUI_ROOT_DIR . '/public/errordocs/400.html';

	header("Status: 400 Bad Request");

	$response = '';

	if(isset($_SERVER['HTTP_ACCEPT'])) {
		if(
			(
				strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false ||
				strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml') !== false
			) && !is_xhr()
		) {
			$response = file_get_contents($filePath);
		} elseif(strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
			header("Content-type: application/json");
			$response =  json_encode(array('code' => 400, 'message' => 'Bad Request'));
		} elseif(strpos($_SERVER['HTTP_ACCEPT'], 'application/xmls') !== false) {
			header("Content-type: text/xml;charset=utf-8");
			$response = '<?xml version="1.0" encoding="utf-8"?>';
			$response = $response.'<response><code>400</code>';
			$response = $response.'<message>Bad Request</message></response>';
		} elseif(!is_xhr()) {
			include $filePath;
		}
	} elseif(!is_xhr()) {
		$response = file_get_contents($filePath);
	}

	if($response != '') {
		echo $response;
	}

	exit;
}

/**
 * Show 404 error page
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @throws iMSCP_Exception_Production in case the error page is not found
 * @return void
 */
function showNotFoundErrorPage()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$filePath = $cfg->GUI_ROOT_DIR . '/public/errordocs/404.html';

	header("Status: 404 Not Found");

	$response = '';

	if(isset($_SERVER['HTTP_ACCEPT'])) {
		if(
			(
				strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false ||
				strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml') !== false
			) && !is_xhr()
		) {
			$response = file_get_contents($filePath);
		} elseif(strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
			header("Content-type: application/json");
			$response =  json_encode(array('code' => 404, 'message' => 'Not Found'));
		} elseif(strpos($_SERVER['HTTP_ACCEPT'], 'application/xmls') !== false) {
			header("Content-type: text/xml;charset=utf-8");
			$response = '<?xml version="1.0" encoding="utf-8"?>';
			$response = $response.'<response><code>404</code>';
			$response = $response.'<message>Not Found</message></response>';
		} elseif(!is_xhr()) {
			include $filePath;
		}
	} elseif(!is_xhr()) {
		$response = file_get_contents($filePath);
	}

	if($response != '') {
		echo $response;
	}

	exit;
}

/**
 * @param  $crnt
 * @param  $max
 * @param  $bars_max
 * @return array
 */
function calc_bars($crnt, $max, $bars_max)
{
	if ($max != 0) {
		$percent_usage = (100 * $crnt) / $max;
	} else {
		$percent_usage = 0;
	}

	$bars = ($percent_usage * $bars_max) / 100;

	if ($bars > $bars_max) {
		$bars = $bars_max;
	}

	return array(
		sprintf("%.2f", $percent_usage),
		sprintf("%d", $bars)
	);
}

/**
 * Turns byte counts to human readable format.
 *
 * If you feel like a hard-drive manufacturer, you can start counting bytes by power
 * of 1000 (instead of the generous 1024). Just set power to 1000.
 *
 * But if you are a floppy disk manufacturer and want to start counting in units of
 * 1024 (for your "1.44 MB" disks ?) let the default value for power.
 *
 * The units for power 1000 are: ('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB')
 *
 * Those for power 1024 are: ('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB')
 *
 * with the horrible names: bytes, kibibytes, mebibytes, etc.
 *
 * @see http://physics.nist.gov/cuu/Units/binary.html
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @throws iMSCP_Exception if power or unit value is unknown
 * @param int|float $bytes Bytes value to convert
 * @param string $unit OPTIONAL Unit to calculate to
 * @param int $decimals OPTIONAL Number of decimal to be show
 * @param int $power OPTIONAL Power to use for conversion (1024 or 1000)
 * @return string
 */
function bytesHuman($bytes, $unit = null, $decimals = 2, $power = 1024)
{
	if ($power == 1000) {
		$units = array(
			'B' => 0, 'kB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8
		);
	} elseif ($power == 1024) {
		$units = array(
			'B' => 0, 'kiB' => 1, 'MiB' => 2, 'GiB' => 3, 'TiB' => 4, 'PiB' => 5, 'EiB' => 6, 'ZiB' => 7, 'YiB' => 8
		);
	} else {
		throw new iMSCP_Exception('Unknown power value');
	}

	$value = 0;

	if ($bytes > 0) {
		if (!array_key_exists($unit, $units)) {
			if(null === $unit) {
				$pow = floor(log($bytes) / log($power));
				$unit = array_search($pow, $units);
			} else {
				throw new iMSCP_Exception('Unknown unit value');
			}
		}

		$value = ($bytes / pow($power, floor($units[$unit])));
	} else {
		$unit = 'B';
	}

	// If decimals is not numeric or decimals is less than 0
	// then set default value
	if (!is_numeric($decimals) || $decimals < 0) {
		$decimals = 2;
	}

	// units Translation
	switch ($unit) {
		case 'B':
			$unit = tr('B');
			break;
		case 'kB':
			$unit = tr('kB');
			break;
		case 'kiB':
			$unit = tr('kiB');
			break;
		case 'MB':
			$unit = tr('MB');
			break;
		case 'MiB':
			$unit = tr('MiB');
			break;
		case 'GB':
			$unit = tr('GB');
			break;
		case 'GiB':
			$unit = tr('GiB');
			break;
		case 'TB':
			$unit = tr('TB');
			break;
		case 'TiB':
			$unit = tr('TiB');
			break;
		case 'PB':
			$unit = tr('PB');
			break;
		case 'PiB':
			$unit = tr('PiB');
			break;
		case 'EB':
			$unit = tr('EB');
			break;
		case 'EiB':
			$unit = tr('EiB');
			break;
		case 'ZB':
			$unit = tr('ZB');
			break;
		case 'ZiB':
			$unit = tr('ZiB');
			break;
		case 'YB':
			$unit = tr('YB');
			break;
		case 'YiB':
			$unit = tr('YiB');
			break;
	}

	return sprintf('%.' . $decimals . 'f ' . $unit, $value);
}

/**
 * Humanize a mebibyte value.
 *
 * @author Laurent Declercs <l.declercq@nuxwin.com>
 * @param int $value mebibyte value
 * @param string $unit OPTIONAL Unit to calculate to ('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB')
 * @return string
 */
function mebibyteHuman($value, $unit = null)
{
	return bytesHuman($value * 1048576, $unit);
}

/**
 * Translates '-1', 'no', 'yes', '0' or mebibyte value string into human readable string.
 *
 * @param int $value variable to be translated
 * @param bool $autosize calculate value in different unit (default false)
 * @param string $to OPTIONAL Unit to calclulate to ('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB')
 * @return String
 */
function translate_limit_value($value, $autosize = false, $to = null)
{
	$trEnabled = '<span style="color:green">' . tr('Enabled') . '</span>';
	$trDisabled = '<span style="color:red">' . tr('Disabled') . '</span>';

	switch ($value) {
		case '-1':
			return tr('Disabled');
		case  '0':
			return tr('Unlimited');
		case '_yes_':
		case 'yes':
			return $trEnabled;
		case '_no_':
		case 'no':
			return $trDisabled;
		case 'full':
			return '<span style="color:green">' . tr('Domain and SQL databases') . '</span>';
		case 'dmn':
			return '<span style="color:green">' . tr('Web files only') . '</span>';
		case 'sql':
			return '<span style="color:green">' . tr('SQL databases only') . '</span>';
		default:
			return (!$autosize) ? $value : mebibyteHuman($value, $to);
	}
}

/**
 * Generates a random salt for password using the best available algorithm.
 *
 * Note: Only algorithms present in the mainline glibc >= 2.7 (Debian) are supported (SHA512, SHA256, MD5 and DES)
 *
 * @throws iMSCP_Exception in case no encryption algorithm is available
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param bool $restrictCharRanges Restrict character ranges used to generate random salt (ie. for unix passwords)
 * @return string Random salt
 */
function generateRandomSalt($restrictCharRanges = false)
{
	if (defined('CRYPT_SHA512') && CRYPT_SHA512) {
		$saltLength = 16;
		$salt = '$6$rounds=' . mt_rand(1500, 5000) . '$';
	} elseif (defined('CRYPT_SHA256') && CRYPT_SHA256) {
		$saltLength = 16;
		$salt = '$5$rounds=' . mt_rand(1500, 5000) . '$';
	} elseif (defined('CRYPT_MD5') && CRYPT_MD5) {
		$saltLength = 8;
		$salt = '$1$';
	} elseif (defined('CRYPT_STD_DES') && CRYPT_STD_DES) {
		$saltLength = 2;
		$salt = '';
	} else {
		throw new iMSCP_Exception('No encryption algorithm available.');
	}

	if($restrictCharRanges) {
		 $chars = array_merge(range(0x30, 0x39), range(0x41, 0x5A), range(0x61, 0x7A), array(0x2E, 0x2F));
	} else {
		if ($saltLength > 2) {
			$chars = range(0x21, 0x7e);
		} else {
			$chars = array_merge(range(0x2E, 0x2F), range(0x30, 0x39), range(0x41, 0x5a), range(0x61, 0x7a));
		}
	}

	for ($i = 0; $i < $saltLength; $i++) {
		$salt .= chr($chars[array_rand($chars)]);
	}

	return $salt;
}

/**
 * Encrypts the given password with salt.
 *
 * @param string $password the password in clear text
 * @param string|null $salt OPTIONAL Salt to use
 * @return string the password encrypted with salt
 */
function cryptPasswordWithSalt($password, $salt = null)
{
	return crypt($password, (!is_null($salt)) ? $salt : generateRandomSalt());
}

/**
 * Generates random password of size specified in Config Var 'PASSWD_CHARS'
 *
 * @return String password
 */
function _passgen()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$passwordLength = isset($cfg->PASSWD_CHARS) ? $cfg->PASSWD_CHARS : 6;
	$password = '';

	for ($i = 0; $i <= $passwordLength; $i++) {
		do {
			$z = mt_rand(42, 123);
		} while ($z >= 91 && $z <= 96);

		$password .= chr($z);
	}

	return $password;
}

/**
 * Generates random password matching the checkPasswordSyntax() criteria.
 *
 * @see _passgen()
 * @return String password
 */
function passgen()
{
	$password = null;

	while ($password == null || ! checkPasswordSyntax($password, '', true)) {
		$password = _passgen();
	}

	return $password;
}

/**
 * Decrypte the given password using the iMSCP secret key and vector.
 *
 * @throws iMSCP_Exception
 * @param  string $password Blowfish (CBC) encrypted password
 * @return string Decrypted password
 */
function decryptBlowfishCbcPassword($password)
{
	if ($password == '') return '';

	if (extension_loaded('mcrypt')) {
		$text = @base64_decode($password . "\n");
		$td = @mcrypt_module_open('blowfish', '', 'cbc', '');
		$key = iMSCP_Registry::get('MCRYPT_KEY');
		$iv = iMSCP_Registry::get('MCRYPT_IV');

		// Initialize encryption
		@mcrypt_generic_init($td, $key, $iv);

		// Decrypt encrypted string
		$decrypted = @mdecrypt_generic($td, $text);
		@mcrypt_module_close($td);

		// Show string
		return trim($decrypted);
	} else {
		throw new iMSCP_Exception("PHP extension 'mcrypt' not loaded!");
	}
}

/**
 * Return timestamp for the first day of $month of $year
 *
 * @param int $month OPTIONAL a month
 * @param int $year OPTIONAL A year (two or 4 digits, whatever)
 * @return int
 */
function getFirstDayOfMonth($month = null, $year = null)
{
	$month = $month ? : date('m');
	$year = $year ? : date('y');

	return mktime(0, 0, 0, $month, 1, $year);

}

/**
 * Return timestamp for last day of month of $year
 *
 * @param int $month OPTIONAL a month
 * @param int $year OPTIONAL A year (two or 4 digits, whatever)
 * @return int
 */
function getLastDayOfMonth($month = null, $year = null)
{
	$month = $month ? : date('m');
	$year = $year ? : date('y');

	return mktime(23, 59, 59, $month + 1, 0, $year);
}
