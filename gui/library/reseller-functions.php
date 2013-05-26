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
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 */

// Available mail types
define('MT_NORMAL_MAIL', 'normal_mail');
define('MT_NORMAL_FORWARD', 'normal_forward');
define('MT_ALIAS_MAIL', 'alias_mail');
define('MT_ALIAS_FORWARD', 'alias_forward');
define('MT_SUBDOM_MAIL', 'subdom_mail');
define('MT_SUBDOM_FORWARD', 'subdom_forward');
define('MT_ALSSUB_MAIL', 'alssub_mail');
define('MT_ALSSUB_FORWARD', 'alssub_forward');
define('MT_NORMAL_CATCHALL', 'normal_catchall');
define('MT_SUBDOM_CATCHALL', 'subdom_catchall');
define('MT_ALIAS_CATCHALL', 'alias_catchall');
define('MT_ALSSUB_CATCHALL', 'alssub_catchall');

/**
 * Generates user's properties.
 *
 * @param int $reseller_id Reseller unique identifier
 * @return array An array that contains user's properties
 */
function generate_reseller_user_props($reseller_id)
{
	$rdmn_current = $rdmn_max = $rsub_current = $rsub_max = $rals_current = $rals_max = $rmail_current = $rmail_max =
	$rftp_current = $rftp_max = $rsql_db_current = $rsql_db_max = $rsql_user_current = $rsql_user_max =
	$rtraff_current = $rtraff_max = $rdisk_current = $rdisk_max = 0;

	$rdmn_uf = $rsub_uf = $rals_uf = $rmail_uf = $rftp_uf = $rsql_db_uf =
	$rsql_user_uf = $rtraff_uf = $rdisk_uf = '_off_';

	$query = "SELECT `admin_id` FROM `admin` WHERE `created_by` = ?";
	$stmt = exec_query($query, $reseller_id);

	if (!$stmt->rowCount()) {
		return array_fill(0, 27, 0);
	}

	while ($data = $stmt->fetchRow()) {
		$admin_id = $data['admin_id'];

		$query = "SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?";
		$stmt1 = exec_query($query, $admin_id);

		$ddata = $stmt1->fetchRow();
		$user_id = $ddata['domain_id'];

		list($sub_current, $sub_max, $als_current, $als_max, $mail_current, $mail_max,
			$ftp_current, $ftp_max, $sql_db_current, $sql_db_max, $sql_user_current,
			$sql_user_max, $traff_max, $disk_max) = get_user_props($user_id);

		list(, , , , , , $traff_current, $disk_current) = generate_user_traffic($user_id);
		$rdmn_current += 1;

		if ($sub_max != -1) {
			if ($sub_max == 0) {
				$rsub_uf = '_on_';
			}

			$rsub_current += $sub_current;
			$rsub_max += $sub_max;
		}

		if ($als_max == 0) {
			$rals_uf = '_on_';
		}

		$rals_current += $als_current;
		$rals_max += $als_max;

		if ($mail_max != -1) {
			if ($mail_max == 0) {
				$rmail_uf = '_on_';
			}

			$rmail_current += $mail_current;
			$rmail_max += $mail_max;
		}

		if ($ftp_max != -1) {
			if ($ftp_max == 0) {
				$rftp_uf = '_on_';
			}

			$rftp_current += $ftp_current;
			$rftp_max += $ftp_max;
		}

		if ($sql_db_max != -1) {
			if ($sql_db_max == 0) {
				$rsql_db_uf = '_on_';
			}

			$rsql_db_current += $sql_db_current;
			$rsql_db_max += $sql_db_max;
		}

		if ($sql_user_max != -1) {
			if ($sql_user_max == 0) {
				$rsql_user_uf = '_on_';
			}

			$rsql_user_current += $sql_user_current;
			$rsql_user_max += $sql_user_max;
		}

		if ($traff_max == 0) $rtraff_uf = '_on_';

		$rtraff_current += $traff_current;
		$rtraff_max += $traff_max;

		if ($disk_max == 0) {
			$rdisk_uf = '_on_';
		}

		$rdisk_current += $disk_current;
		$rdisk_max += $disk_max;
	}

	return array(
		$rdmn_current, $rdmn_max, $rdmn_uf, $rsub_current, $rsub_max, $rsub_uf, $rals_current, $rals_max, $rals_uf,
		$rmail_current, $rmail_max, $rmail_uf, $rftp_current, $rftp_max, $rftp_uf, $rsql_db_current, $rsql_db_max,
		$rsql_db_uf, $rsql_user_current, $rsql_user_max, $rsql_user_uf, $rtraff_current, $rtraff_max, $rtraff_uf,
		$rdisk_current, $rdisk_max, $rdisk_uf
	);
}

/**
 * Returns information about customer traffic and disk usage.
 *
 * @throws iMSCP_Exception in case customer main domain is not found
 * @param int $customerId Customer unique identifier
 * @return array An array containing information about customer traffic and disk usage
 */
function get_user_trafficAndDiskUsage($customerId)
{
	$query = "
		SELECT
			`domain_id`,
			IFNULL(`domain_disk_usage`, 0) AS `diskspace_usage`,
			IFNULL(`domain_traffic_limit`, 0) AS `monthly_traffic_limit`,
			IFNULL(`domain_disk_limit`, 0) AS `diskspace_limit`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
	";
	$stmt = exec_query($query, $customerId);

	if (!$stmt->rowCount()) {
		throw new iMSCP_Exception("Unable to found main domain for customer with ID $customerId");
	} else {
		$data = $stmt->fetchRow();

		$domainId = $data['domain_id'];
		$diskspaceUsage = $data['diskspace_usage'];
		$monthlyTrafficLimit = $data['monthly_traffic_limit'];
		$diskspaceLimit = $data['diskspace_limit'];

		$query = "
			SELECT
				YEAR(FROM_UNIXTIME(`dtraff_time`)) AS `tyear`,
				MONTH(FROM_UNIXTIME(`dtraff_time`)) AS `tmonth`,
				SUM(`dtraff_web`) AS `web`,
				SUM(`dtraff_ftp`) AS `ftp`,
				SUM(`dtraff_mail`) AS `smtp`,
				SUM(`dtraff_pop`) AS `pop`,
				SUM(`dtraff_web`) + SUM(`dtraff_ftp`) + SUM(`dtraff_mail`) + SUM(`dtraff_pop`) AS `total`
			FROM
				`domain_traffic`
			WHERE
				`domain_id` = ?
			GROUP BY
				`tyear`, `tmonth`
		";
		$stmt = exec_query($query, $domainId);

		$maxMonthlyTraffic = $data['web'] = $data['ftp'] = $data['smtp'] = $data['pop'] = $data['total'] = 0;

		while ($row = $stmt->fetchRow()) {
			$data['web'] += $row['web'];
			$data['ftp'] += $row['ftp'];
			$data['smtp'] += $row['smtp'];
			$data['pop'] += $row['total'];

			if ($row['total'] > $maxMonthlyTraffic) {
				$maxMonthlyTraffic = $row['total'];
			}
		}

		return array(
			$data['web'], //
			$data['ftp'],
			$data['smtp'],
			$data['pop'],
			$data['total'],
			$diskspaceUsage, // Total diskspace usage
			$monthlyTrafficLimit, // Monthly traffic limit
			$diskspaceLimit,		// diskspace limit
			$maxMonthlyTraffic
		);
	}
}

/**
 * Returns user's properties from database.
 *
 * @param int $user_id User unique identifier
 * @return array An array that contain user's properties
 */
function get_user_props($user_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT * FROM `domain` WHERE `domain_id` = ?";
	$stmt = exec_query($query, $user_id);

	if (!$stmt->rowCount()) {
		return array_fill(0, 14, 0);
	}

	$data = $stmt->fetchRow();
	$sub_current = get_domain_running_sub_cnt($user_id);
	$sub_max = $data['domain_subd_limit'];
	$als_current = records_count('domain_aliasses', 'domain_id', $user_id);
	$als_max = $data['domain_alias_limit'];

	if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES) {
		// Catch all is not a mailbox and haven't to be count
		$mail_current = records_count('mail_users',
			'mail_type NOT RLIKE \'_catchall\' AND domain_id',
			$user_id);
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

		$mail_current = records_count('mail_users', $where, $user_id);
	}

	$mail_max = $data['domain_mailacc_limit'];

	$ftp_current = sub_records_rlike_count(
		'domain_name', 'domain', 'domain_id', $user_id, 'userid', 'ftp_users', 'userid', '@', ''
	);

	$ftp_current += sub_records_rlike_count(
		'alias_name', 'domain_aliasses', 'domain_id', $user_id, 'userid', 'ftp_users', 'userid', '@', ''
	);

	$ftp_max = $data['domain_ftpacc_limit'];
	$sql_db_current = records_count('sql_database', 'domain_id', $user_id);
	$sql_db_max = $data['domain_sqld_limit'];
	$sql_user_current = get_domain_running_sqlu_acc_cnt($user_id);
	$sql_user_max = $data['domain_sqlu_limit'];
	$traff_max = $data['domain_traffic_limit'];
	$disk_max = $data['domain_disk_limit'];

	return array(
		$sub_current, $sub_max, $als_current, $als_max, $mail_current, $mail_max, $ftp_current, $ftp_max,
		$sql_db_current, $sql_db_max, $sql_user_current, $sql_user_max, $traff_max, $disk_max
	);
}

/**
 * Checks if a domain name already exist.
 *
 * @param string $domainName Domain name to be checked
 * @param int $resellerId Reseller unique identifier
 * @return bool TRUE if the domain already exist, FALSE otherwise
 */
function imscp_domain_exists($domainName, $resellerId)
{
	$queryDomain = "SELECT COUNT(`domain_id`) AS `cnt` FROM `domain` WHERE `domain_name` = ?";
	$stmtDomain = exec_query($queryDomain, $domainName);

	// query to check if the domain name exists in the table for domain aliases
	$queryAliases = "
		SELECT
			COUNT(`t1`.`alias_id`) AS `cnt`
		FROM
			`domain_aliasses` AS `t1`, `domain` AS `t2`
		WHERE
			`t1`.`domain_id` = `t2`.`domain_id`
		AND
			`t1`.`alias_name` = ?
	";
	$stmtAliases = exec_query($queryAliases, $domainName);

	// redefine query to check in the table domain/acounts if 3rd level for this reseller is allowed
	$queryDomain = "
		SELECT
			COUNT(`domain_id`) AS `cnt`
		FROM
			`domain`
		WHERE
			`domain_name` = ?
		AND
			`domain_created_id` <> ?
	";

	// redefine query to check in the table aliases if 3rd level for this reseller is allowed
	$queryAliases = "
		SELECT
			COUNT(`t1`.`alias_id`) AS cnt
		FROM
			`domain_aliasses` AS `t1`, `domain` AS `t2`
		WHERE
			`t1`.`domain_id` = `t2`.`domain_id`
		AND
			`t1`.`alias_name` = ?
		AND
			`t2`.`domain_created_id` <> ?
	";
	// here we split the domain name by point separator
	$splitDomain = explode('.', trim($domainName));
	//$dom_cnt = strlen(trim($domain_name));
	$domainPartCnt = 0;
	$error = 0;

	// here starts a loop to check if the splitted domain is available for other resellers
	for ($i = 0, $cntSplitDomain = count($splitDomain) - 1; $i < $cntSplitDomain; $i++) {
		$domainPartCnt = $domainPartCnt + strlen($splitDomain[$i]) + 1;
		$idom = substr($domainName, $domainPartCnt);
		// execute query the redefined queries for domains/accounts and aliases tables
		$stmt2 = exec_query($queryDomain, array($idom, $resellerId));
		$stmt3 = exec_query($queryAliases, array($idom, $resellerId));

		// do we have available record. id yes => the variable error get value different 0
		if ($stmt2->fields['cnt'] > 0 || $stmt3->fields['cnt'] > 0) {
			$error++;
		}
	}

	// if we have : db entry in the tables domain AND no problem with 3rd level domains
	// AND enduser (no reseller) => the function returns OK => domain can be added
	if ($stmtDomain->fields['cnt'] == 0 && $stmtAliases->fields['cnt'] == 0 &&
		$error == 0 && $resellerId == 0
	) {
		return false;
	}

	// if we have domain add one by end user OR some error => the funcion returns ERROR
	if ($resellerId == 0 || $error) {
		return true;
	}

	// ok we do not have end user and we do not have error
	// query to check if the domain does not exist as subdomain
	$querySubdomains = "
		SELECT
			`t1`.`subdomain_name`, `t2`.`domain_name`
		FROM
			`subdomain` AS `t1`, `domain` AS `t2`
		WHERE
			`t1`.`domain_id` = `t2`.`domain_id`
		AND
			`t2`.`domain_created_id` = ?
	";
	$stmtSubdomains = exec_query($querySubdomains, $resellerId);

	$subdomains = array();

	while (!$stmtSubdomains->EOF) {
		$subdomains[] = $stmtSubdomains->fields['subdomain_name'] . '.' .
			$stmtSubdomains->fields['domain_name'];
		$stmtSubdomains->moveNext();
	}

	if ($stmtDomain->fields['cnt'] == 0 && $stmtAliases->fields['cnt'] == 0
		&& !in_array($domainName, $subdomains)
	) {
		return false;
	} else {
		return true;
	}
}

/**
 * Must be documented.
 *
 * @param $searchQuery
 * @param $countQuery
 * @param int $resellerId Reseller unique identifier
 * @param $startIndex
 * @param $rowsPerPage
 * @param $searchFor
 * @param $searchCommon
 * @param $searchStatus
 * @return void
 */
function gen_manage_domain_query(
	&$searchQuery, &$countQuery, $resellerId, $startIndex, $rowsPerPage, $searchFor, $searchCommon, $searchStatus
) {
	if ($searchFor === 'n/a' && $searchCommon === 'n/a' && $searchStatus === 'n/a') {
		// We have pure list query;
		$countQuery = "SELECT COUNT(`domain_id`) AS `cnt` FROM `domain` WHERE `domain_created_id` = '$resellerId'";

		$searchQuery = "
			SELECT
				*
			FROM
				`domain`
			LEFT JOIN `admin` ON(`domain_admin_id` = `admin_id`)
			WHERE
				`domain_created_id` = '$resellerId'
			ORDER BY
				`domain_name` ASC
			LIMIT
				$startIndex, $rowsPerPage
		";
	} elseif ($searchFor == '' && $searchStatus != '') {
		if ($searchStatus == 'all') {
			$addQuery = "`domain_created_id` = '$resellerId'";
		} else {
			$addQuery = "`domain_created_id` = '$resellerId' AND `domain_status` = '$searchStatus'";
		}

		$countQuery = " SELECT COUNT(`domain_id`) AS `cnt` FROM `domain` WHERE $addQuery";

		$searchQuery = "
			SELECT
				*
			FROM
				`domain`
			LEFT JOIN `admin` ON(`domain_admin_id` = `admin_id`)
			WHERE
				$addQuery
			ORDER BY
				`domain_name` ASC
			LIMIT
				$startIndex, $rowsPerPage
		";
	} elseif ($searchFor != '') {
		if ($searchCommon == 'domain_name') {
			$searchFor = idn_to_ascii($searchFor);
			$addQuery = "WHERE `admin_name` RLIKE '" . addslashes($searchFor) . "' %s";
		} elseif ($searchCommon == 'customer_id') {
			$addQuery = "WHERE `customer_id` RLIKE '" . addslashes($searchFor) . "' %s";
		} elseif ($searchCommon == 'lname') {
			$addQuery = "WHERE (`lname` RLIKE '" . addslashes($searchFor) .
				"' OR `fname` RLIKE '" . addslashes($searchFor) . "') %s";
		} elseif ($searchCommon == 'firm') {
			$addQuery = "WHERE `firm` RLIKE '" . addslashes($searchFor) . "' %s";
		} elseif ($searchCommon == 'city') {
			$addQuery = "WHERE `city` RLIKE '" . addslashes($searchFor) . "' %s";
		} elseif ($searchCommon == 'state') {
			$addQuery = "WHERE `state` RLIKE '" . addslashes($searchFor) . "' %s";
		} elseif ($searchCommon == 'country') {
			$addQuery = "WHERE `country` RLIKE '" . addslashes($searchFor) . "' %s";
		}

		if (isset($addQuery)) {
			if ($searchStatus != 'all') {
				$addQuery = sprintf(
					$addQuery, " AND t1.`created_by` = '$resellerId' AND t2.`domain_status` = '$searchStatus'"
				);

				$countQuery = "
				    SELECT
					    COUNT(`admin_id`) AS `cnt`
				    FROM
					    `admin` AS `t1`, `domain` AS `t2`
				    $addQuery
				AND
					`t1`.`admin_id` = `t2`.`domain_admin_id`
			";
			} else {
				$addQuery = sprintf($addQuery, " AND `created_by` = '$resellerId'");
				$countQuery = "SELECT COUNT(`admin_id`) AS `cnt` FROM `admin` $addQuery";
			}

			$searchQuery = "
			    SELECT
				    `t1`.`admin_id`, t1.`admin_status`, `t2`.*
			    FROM
				    `admin` AS `t1`, `domain` AS `t2`
			    $addQuery
			    AND
				    `t1`.`admin_id` = `t2`.`domain_admin_id`
			    ORDER BY
				    `t2`.`domain_name` ASC
			    LIMIT
				    $startIndex, $rowsPerPage
		    ";
		}
	}
}

/**
 * Check that reseller limits are not smaller than those defined by the given hosting plan
 *
 * @throws iMSCP_Exception
 * @param int $resellerId Reseller unique identifier
 * @param int|string $hp Hosting plan unique identifier or string representing hosting plan properties to check against
 * @return bool
 */
function reseller_limits_check($resellerId, $hp)
{
	if (is_number($hp)) {
		if (isset($_SESSION['ch_hpprops'])) {
			$hostingPlanProperties = $_SESSION['ch_hpprops'];
		} else {
			$stmt = exec_query("SELECT `props` FROM `hosting_plans` WHERE `id` = ?", $hp);

			if ($stmt->rowCount()) {
				$data = $stmt->fetchRow();
				$hostingPlanProperties = $data['props'];
			} else {
				throw new iMSCP_Exception('Hosting plan not found');
			}
		}
	} else {
		$hostingPlanProperties = $hp;
	}

	list(
		, , $newSubLimit, $newAlsLimit, $newMailLimit, $newFtpLimit, $newSqlDbLimit, $newSqlUserLimit, $newTrafficLimit,
		$newDiskspaceLimit
	) = explode(';', $hostingPlanProperties);

	$query = "SELECT * FROM `reseller_props` WHERE `reseller_id` = ?";

	$stmt = exec_query($query, $resellerId);
	$data = $stmt->fetchRow();
	$currentDmnLimit = $data['current_dmn_cnt'];
	$maxDmnLimit = $data['max_dmn_cnt'];
	$currentSubLimit = $data['current_sub_cnt'];
	$maxSubLimit = $data['max_sub_cnt'];
	$currentAlsLimit = $data['current_als_cnt'];
	$maxAlsLimit = $data['max_als_cnt'];
	$currentMailLimit = $data['current_mail_cnt'];
	$maxMailLimit = $data['max_mail_cnt'];
	$currentFtpLimit = $data['current_ftp_cnt'];
	$ftpMaxLimit = $data['max_ftp_cnt'];
	$currentSqlDbLimit = $data['current_sql_db_cnt'];
	$maxSqlDbLimit = $data['max_sql_db_cnt'];
	$currentSqlUserLimit = $data['current_sql_user_cnt'];
	$maxSqlUserLimit = $data['max_sql_user_cnt'];
	$currentTrafficLimit = $data['current_traff_amnt'];
	$maxTrafficLimit = $data['max_traff_amnt'];
	$currentDiskspaceLimit = $data['current_disk_amnt'];
	$maxDiskspaceLimit = $data['max_disk_amnt'];

	if ($maxDmnLimit != 0) {
		if ($currentDmnLimit + 1 > $maxDmnLimit) {
			set_page_message(tr('You have reached your domains limit.<br />You cannot add more domains.'), 'error');
		}
	}

	if ($maxSubLimit != 0) {
		if ($newSubLimit != -1) {
			if ($newSubLimit == 0) {
				set_page_message(tr('You have a subdomains limit.<br />You cannot add an user with unlimited subdomains.'), 'error');
			} else if ($currentSubLimit + $newSubLimit > $maxSubLimit) {
				set_page_message(tr('You are exceeding your subdomains limit.'), 'error');
			}
		}
	}

	if ($maxAlsLimit != 0) {
		if ($newAlsLimit != -1) {
			if ($newAlsLimit == 0) {
				set_page_message(tr('You have a domain aliases limit.<br />You cannot add an user with unlimited domain aliases.'), 'error');
			} else if ($currentAlsLimit + $newAlsLimit > $maxAlsLimit) {
				set_page_message(tr('You are exceeding you domain aliases Limit.'));
			}
		}
	}

	if ($maxMailLimit != 0) {
		if ($newMailLimit == 0) {
			set_page_message(tr('You have a mail accounts limit.<br />You cannot add an user with unlimited mail accounts.'), 'error');
		} else if ($currentMailLimit + $newMailLimit > $maxMailLimit) {
			set_page_message(tr('You are exceeding your mail accounts limit.'), 'error');
		}
	}

	if ($ftpMaxLimit != 0) {
		if ($newFtpLimit == 0) {
			set_page_message(tr('You have a FTP accounts limit!<br />You cannot add an user with unlimited FTP accounts.'), 'error');
		} else if ($currentFtpLimit + $newFtpLimit > $ftpMaxLimit) {
			set_page_message(tr('You are exceeding your FTP accounts limit.'), 'error');
		}
	}

	if ($maxSqlDbLimit != 0) {
		if ($newSqlDbLimit != -1) {
			if ($newSqlDbLimit == 0) {
				set_page_message(tr('You have a SQL databases limit.<br />You cannot add an user with unlimited SQL databases.'), 'error');
			} else if ($currentSqlDbLimit + $newSqlDbLimit > $maxSqlDbLimit) {
				set_page_message(tr('You are exceeding your SQL databases limit.'), 'error');
			}
		}
	}

	if ($maxSqlUserLimit != 0) {
		if ($newSqlUserLimit != -1) {
			if ($newSqlUserLimit == 0) {
				set_page_message(tr('You have an SQL users limit.<br />You cannot add an user with unlimited SQL users.'), 'error');
			} else if ($newSqlDbLimit == -1) {
				set_page_message(tr('You have disabled SQL databases for this user.<br />You cannot have SQL users here.'), 'error');
			} else if ($currentSqlUserLimit + $newSqlUserLimit > $maxSqlUserLimit) {
				set_page_message(tr('You are exceeding your SQL users limit.'), 'error');
			}
		}
	}

	if ($maxTrafficLimit != 0) {
		if ($newTrafficLimit == 0) {
			set_page_message(tr('You have a monthly traffic limit.<br />You cannot add an user with unlimited monthly traffic.'), 'error');
		} else if ($currentTrafficLimit + $newTrafficLimit > $maxTrafficLimit) {
			set_page_message(tr('You are exceeding your monthly traffic limit.'), 'error');
		}
	}

	if ($maxDiskspaceLimit != 0) {
		if ($newDiskspaceLimit == 0) {
			set_page_message(tr('You have a disk space limit.<br />You cannot add an user with unlimited disk space.'), 'error');
		} else if ($currentDiskspaceLimit + $newDiskspaceLimit > $maxDiskspaceLimit) {
			set_page_message(tr('You are exceeding your disk space limit.'), 'error');
		}
	}

	if (Zend_Session::namespaceIsset('pageMessages')) {
		return false;
	}

	return true;
}

/**
 * Send alias order email.
 *
 * @param  string $aliasName
 * @return void
 */
function send_alias_order_email($aliasName)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$userId = $_SESSION['user_id'];
	$resellerId = who_owns_this($userId, 'user');

	$query = 'SELECT `fname`, `lname` FROM `admin` WHERE `admin_id` = ?;';
	$stmt = exec_query($query, $userId);
	$userFirstname = $stmt->fields['fname'];
	$userLastname = $stmt->fields['lname'];
	$userEmail = $_SESSION['user_email'];
	$data = get_alias_order_email($resellerId);
	$toName = $data['sender_name'];
	$toEmail = $data['sender_email'];
	$subject = $data['subject'];
	$message = $data['message'];

	$to = ($toName) ? '"' . encode($toName) . "\" <" . $toEmail . ">" : $toEmail;

	if ($userFirstname && $userLastname) {
		$fromName = "$userFirstname $userLastname";
		$from = '"' . encode($fromName) . "\" <" . $userEmail . ">";
	} else {
		if ($userFirstname) {
			$fromName = $userFirstname;
		} else if ($userLastname) {
			$fromName = $userLastname;
		} else {
			$fromName = $userEmail;
		}
		$from = $userEmail;
	}

	$search = array();
	$replace = array();

	$search [] = '{RESELLER}';
	$replace[] = $toName;
	$search [] = '{CUSTOMER}';
	$replace[] = $fromName;
	$search [] = '{ALIAS}';
	$replace[] = $aliasName;
	$search [] = '{BASE_SERVER_VHOST}';
	$replace[] = $cfg->BASE_SERVER_VHOST;
	$search [] = '{BASE_SERVER_VHOST_PREFIX}';
	$replace[] = $cfg->BASE_SERVER_VHOST_PREFIX;

	$subject = str_replace($search, $replace, $subject);
	$message = str_replace($search, $replace, $message);

	$subject = encode($subject);

	$headers = "From: " . $from . "\n";
	$headers .= "MIME-Version: 1.0\n";
	$headers .= "Content-Type: text/plain; charset=utf-8\n";
	$headers .= "Content-Transfer-Encoding: 8bit\n";
	$headers .= "X-Mailer: i-MSCP {$cfg->Version} Service Mailer";

	mail($to, $subject, $message, $headers);
}

/**
 * Add default emails for new domain.
 *
 * @param int $domainId Domain unique identifier
 * @param string $userEmail
 * @param string $domainPart
 * @param string $domainType
 * @param int $subId
 * @return void
 */
function client_mail_add_default_accounts($domainId, $userEmail, $domainPart, $domainType = 'domain', $subId = 0)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($cfg->CREATE_DEFAULT_EMAIL_ADDRESSES) {
		$forwardType = ($domainType == 'alias') ? 'alias_forward' : 'normal_forward';

		// prepare SQL
		$query = "
			INSERT INTO
			    mail_users (
					`mail_acc`, `mail_pass`, `mail_forward`,`domain_id`, `mail_type`, `sub_id`, `status`,
					`mail_auto_respond`,`quota`, `mail_addr`
				) VALUES (
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				)
		";

		// create default forwarder for webmaster@domain.tld to the account's owner
		exec_query(
			$query,
			array(
				'webmaster', '_no_', $userEmail, $domainId, $forwardType, $subId, $cfg->ITEM_ADD_STATUS, '_no_',
				10485760, 'webmaster@' . $domainPart
			)
		);

		// create default forwarder for postmaster@domain.tld to the account's reseller
		exec_query(
			$query,
			array(
				'postmaster', '_no_', $_SESSION['user_email'], $domainId, $forwardType, $subId, $cfg->ITEM_ADD_STATUS,
				'_no_', 10485760, 'postmaster@' . $domainPart
			)
		);

		// create default forwarder for abuse@domain.tld to the account's reseller
		exec_query(
			$query,
			array(
				'abuse', '_no_', $_SESSION['user_email'], $domainId, $forwardType, $subId, $cfg->ITEM_ADD_STATUS,
				'_no_', 10485760, 'abuse@' . $domainPart
			)
		);
	}
}

/**
 * Recalculate current_ properties of reseller
 *
 * Important:
 *
 * This is not based on the objects consumed by customers. This is based on objects assigned by the reseller to its
 * customers. In other words, it's useless to call this function on client side.
 *
 *
 * @throws iMSCP_Exception in case the given reseller doesn't exists
 * @param int $resellerId Reseller unique identifier
 * @return array list of properties
 */
function recalc_reseller_c_props($resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// Get all users of reseller
	$query = "
		SELECT
			COUNT(`t1`.`domain_id`) AS `current_domain_cnt`,
			IFNULL(SUM(IF(`t1`.`domain_subd_limit` >= 0, `t1`.`domain_subd_limit`, 0)), 0) AS `current_sub_cnt`,
			IFNULL(SUM(IF(`t1`.`domain_alias_limit` >= 0, `t1`.`domain_alias_limit`, 0)), 0) AS `current_als_cnt`,
			IFNULL(SUM(IF(`t1`.`domain_mailacc_limit` >= 0, `t1`.`domain_mailacc_limit`, 0)), 0) AS `current_mail_cnt`,
			IFNULL(SUM(IF(`t1`.`domain_ftpacc_limit` >= 0, `t1`.`domain_ftpacc_limit`, 0)), 0) AS `current_ftp_cnt`,
			IFNULL(SUM(IF(`t1`.`domain_sqld_limit` >= 0, `t1`.`domain_sqld_limit`, 0)), 0) AS `current_sql_db_cnt`,
			IFNULL(SUM(IF(`t1`.`domain_sqlu_limit` >= 0, `t1`.`domain_sqlu_limit`, 0)), 0) AS `current_sql_user_cnt`,
			IFNULL(SUM(`t1`.`domain_disk_limit`), 0) AS `current_disk_amnt`,
			IFNULL(SUM(`t1`.`domain_traffic_limit`), 0) AS `current_traff_amnt`,
			IFNULL(`t2`.`admin_id`, 0) AS `reseller_id`
		FROM
			`domain` AS `t1`
		RIGHT JOIN
			`admin` AS t2 ON(`t2`.`admin_id` = ? AND `t2`.`admin_type` = ?)
		WHERE
			`t1`.`domain_created_id` = ?
		AND
			`t1`.`domain_status` != ?
	";
	$stmt = exec_query($query, array($resellerId, 'reseller', $resellerId, $cfg->ITEM_DELETE_STATUS));

	$row = $stmt->fetchRow();

	if($row['reseller_id'] == 0) {
		throw new iMSCP_Exception("Reseller with ID $resellerId has not been found.");
	}

	return array(
		$row['current_domain_cnt'], $row['current_sub_cnt'], $row['current_als_cnt'], $row['current_mail_cnt'],
		$row['current_ftp_cnt'], $row['current_sql_db_cnt'], $row['current_sql_user_cnt'], $row['current_disk_amnt'],
		$row['current_traff_amnt']
	);
}

/**
 * Recalculates the reseller's current properties.
 *
 * Important:
 *
 * This is not based on the objects consumed by customers. This is based on objects assigned by the reseller to its
 * customers.
 *
 * @param int $resellerId unique reseller identifier
 * @return void
 */
function update_reseller_c_props($resellerId)
{
	$query = "
		UPDATE
			`reseller_props`
		SET
			`current_dmn_cnt` = ?, `current_sub_cnt` = ?, `current_als_cnt` = ?, `current_mail_cnt` = ?,
			`current_ftp_cnt` = ?, `current_sql_db_cnt` = ?, `current_sql_user_cnt` = ?, `current_disk_amnt` = ?,
			`current_traff_amnt` = ?
		WHERE
			`reseller_id` = ?
	";

	$props = recalc_reseller_c_props($resellerId);
	$props[] = $resellerId;
	exec_query($query, $props);
}

/**
 * Get reseller id of given domain.
 *
 * @param int $domainId Domain unique identifier
 * @return int Reseller unique identifier or 0 in on error
 */
function get_reseller_id($domainId)
{
	$query = "
		SELECT
			`t1`.`created_by`
		FROM
			`domain` AS `t1`
		INNER JOIN
			`admin` AS `t2` ON (t2.`admin_id` = `t1`.`domain_admin_id`)
		WHERE
			t1.`domain_id` = ?
	";
	$stmt = exec_query($query, $domainId);

	if (!$stmt->rowCount()) {
		return 0;
	}

	return $stmt->fields('created_by');
}

/**
 * Convert datepicker date to Unix-Timestamp.
 *
 * @author Peter Ziergoebel <info@fisa4.de>
 * @since 1.0.0 (i-MSCP)
 * @param string $time A date/time string
 * @return mixed
 */
function datepicker_reseller_convert($time)
{
	return strtotime($time);
}

/**
 * Tells whether or not the given feature is available for the reseller.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @throws iMSCP_Exception When $featureName is not known
 * @param string $featureName Feature name
 * @param bool $forceReload If true force data to be reloaded
 * @return bool TRUE if $featureName is available for reseller, FALSE otherwise
 * TODO add hosting_plan feature
 */
function resellerHasFeature($featureName, $forceReload = false)
{
	static $availableFeatures = null;
	$featureName = strtolower($featureName);

	if (null == $availableFeatures || $forceReload) {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		$resellerProps = imscp_getResellerProperties($_SESSION['user_id'], true);

		$availableFeatures = array(
			'domains' => ($resellerProps['max_dmn_cnt'] != '-1') ? true : false,
			'subdomains' => ($resellerProps['max_sub_cnt'] != '-1') ? true : false,
			'domain_aliases' => ($resellerProps['max_als_cnt'] != '-1') ? true : false,
			'mail' => ($resellerProps['max_mail_cnt'] != '-1') ? true : false,
			'ftp' => ($resellerProps['max_ftp_cnt'] != '-1') ? true : false,
			'sql' => ($resellerProps['max_sql_db_cnt'] != '-1') ? true : false, // TODO to be removed
			'sql_db' => ($resellerProps['max_sql_db_cnt'] != '-1') ? true : false,
			'sql_user' => ($resellerProps['max_sql_user_cnt'] != '-1') ? true : false,
			'php' => true,
			'php_editor' => ($resellerProps['php_ini_system'] == 'yes') ? true : false,
			'cgi' => true,
			'custom_dns_records' => true,
			'aps' => ($resellerProps['software_allowed'] != 'no') ? true : false, // aps feature check must be revisted
			'external_mail' => true,
			'backup' => ($cfg->BACKUP_DOMAINS != 'no') ? true : false,
			'support' => ($cfg->IMSCP_SUPPORT_SYSTEM && $resellerProps['support_system'] == 'yes') ? true : false
		);
	}

	if (!array_key_exists($featureName, $availableFeatures)) {
		throw new iMSCP_Exception(sprintf("Feature %s is not known by the resellerHasFeature() function.", $featureName));
	}

	return $availableFeatures[$featureName];
}

/**
 * Whether or not the logged-in reseller has a least the given number of registered customers.
 *
 * @param int $minNbCustomers Minimum number of customers
 * @return bool TRUE if the logged-in reseller has a least the given number of registered customer, FALSE otherwise
 */
function resellerHasCustomers($minNbCustomers = 1)
{
	static $customerCount = null;

	if (null === $customerCount ) {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		$stmt = exec_query(
			'
				SELECT
					COUNT(`admin_id`) AS `count`
				FROM
					`admin`
				WHERE
					`admin_type` = ?
				AND
					`created_by` = ?
				AND
					`admin_status` <> ?
			',
			array('user', $_SESSION['user_id'], $cfg->ITEM_DELETE_STATUS)
		);

		$customerCount = $stmt->fields['count'];
	}

	return ($customerCount >= $minNbCustomers);
}

/**
 * Check user data.
 *
 * @param  bool $noPass
 * @return bool
 */
function check_ruser_data($noPass)
{
	global $userEmail, $customerId, $firstName, $lastName, $firm, $zip, $gender, $city, $state, $country, $street1,
		   $street2, $mail, $phone, $fax, $password, $domainIp;

	$passwordRepeat = '';

	// Get data for fields from previous page
	if (isset($_POST['userpassword'])) {
		$password = $_POST['userpassword'];
	}

	if (isset($_POST['userpassword_repeat'])) {
		$passwordRepeat = $_POST['userpassword_repeat'];
	}

	if (isset($_POST['domain_ip'])) {
		$domainIp = $_POST['domain_ip'];
	}

	if (isset($_POST['useremail'])) {
		$userEmail = $_POST['useremail'];
	}

	if (isset($_POST['useruid'])) {
		$customerId = $_POST['useruid'];
	}

	if (isset($_POST['userfname'])) {
		$firstName = $_POST['userfname'];
	}

	if (isset($_POST['userlname'])) {
		$lastName = $_POST['userlname'];
	}

	if (isset($_POST['userfirm'])) {
		$firm = $_POST['userfirm'];
	}

	if (isset($_POST['userzip'])) {
		$zip = $_POST['userzip'];
	}

	if (isset($_POST['usercity'])) {
		$city = $_POST['usercity'];
	}

	if (isset($_POST['userstate'])) {
		$state = $_POST['userstate'];
	}

	if (isset($_POST['usercountry'])) {
		$country = $_POST['usercountry'];
	}

	if (isset($_POST['userstreet1'])) {
		$street1 = $_POST['userstreet1'];
	}

	if (isset($_POST['userstreet2'])) {
		$street2 = $_POST['userstreet2'];
	}

	if (isset($_POST['useremail'])) {
		$mail = $_POST['useremail'];
	}

	if (isset($_POST['userphone'])) {
		$phone = $_POST['userphone'];
	}

	if (isset($_POST['userfax'])) {
		$fax = $_POST['userfax'];
	}

	if (isset($_POST['gender']) && get_gender_by_code($_POST['gender'], true) !== null) {
		$gender = $_POST['gender'];
	} else {
		$gender = 'U';
	}

	if (!$noPass) {
		if ('' === $passwordRepeat || '' === $password) {
			set_page_message(tr('Please fill up both data fields for password.'), 'error');
		} elseif ($passwordRepeat !== $password) {
			set_page_message(tr("Passwords doesn't match."), 'error');
		}

		checkPasswordSyntax($password);
	}

	if ($userEmail == NULL) { // TODO check email
		set_page_message(tr('Incorrect email length or syntax.'), 'error');
	}

	if($customerId != '' && strlen($customerId) > 200) {
		set_page_message(tr('Customer ID cannot have more than 200 characters'), 'error');
	}

	if($firstName != '' && strlen($firstName) > 200) {
		set_page_message(tr('First name cannot have more than 200 characters.'), 'error');
	}

	if($lastName != '' && strlen($lastName) > 200) {
		set_page_message(tr('Last name cannot have more than 200 characters.'), 'error');
	}

	if($zip != '' && (strlen($zip) > 200 || is_number(!$zip))) {
		set_page_message(tr('Incorrect post code length or syntax!'));
	}

	if (Zend_Session::namespaceIsset('pageMessages')) {
		return false;
	}

	return true;
}
