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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2015 by i-MSCP | http://i-mscp.net
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
 * Generates user's properties
 *
 * @param int $resellerId Reseller unique identifier
 * @return array An array that contains user's properties
 */
function generate_reseller_user_props($resellerId)
{
	$rdmnCurrent = $rdmnMax = $rsubCurrent = $rsubMax = $ralsCurrent = $ralsMax = $rmailCurrent = $rmailMax =
	$rftpCurrent = $rftpMax = $rsqlDbCurrent = $rsqlDbMax = $rsqlUserCurrent = $rsqlUserMax = $rtraffCurrent =
	$rtraffMax = $rdiskCurrent = $rdiskMax = 0;

	$rdmnUf = $rsubUf = $ralsUf = $rmailUf = $rftpUf = $rsqlDbUf = $rsqlUserUf = $rtraffUf = $rdiskUf = '_off_';

	$stmt = exec_query(
		'
			SELECT
				admin_id, domain_id
			FROM
				admin
			INNER JOIN
				domain ON(domain_admin_id = admin_id)
			WHERE
				created_by = ?
		',
		$resellerId
	);

	if(!$stmt->rowCount()) {
		return array_fill(0, 27, 0);
	}

	while($data = $stmt->fetchRow()) {
		$adminId = $data['admin_id'];
		$domainId = $data['domain_id'];

		list(
			$subCurrent, $subMax, $alsCurrent, $alsMax, $mailCurrent, $mailMax, $ftpCurrent, $ftpMax, $sqlDbCurrent,
			$sqlDbMax, $sqlUserCurrent, $sqlUserMax, $traffMax, $diskMax
		) = get_user_props($adminId);

		list(, , , , , , $traffCurrent, $diskCurrent) = shared_getCustomerStats($domainId);

		$rdmnCurrent += 1;

		if($subMax != -1) {
			if($subMax == 0) {
				$rsubUf = '_on_';
			}

			$rsubCurrent += $subCurrent;
			$rsubMax += $subMax;
		}

		if($alsMax == 0) {
			$ralsUf = '_on_';
		}

		$ralsCurrent += $alsCurrent;
		$ralsMax += $alsMax;

		if($mailMax != -1) {
			if($mailMax == 0) {
				$rmailUf = '_on_';
			}

			$rmailCurrent += $mailCurrent;
			$rmailMax += $mailMax;
		}

		if($ftpMax != -1) {
			if($ftpMax == 0) {
				$rftpUf = '_on_';
			}

			$rftpCurrent += $ftpCurrent;
			$rftpMax += $ftpMax;
		}

		if($sqlDbMax != -1) {
			if($sqlDbMax == 0) {
				$rsqlDbUf = '_on_';
			}

			$rsqlDbCurrent += $sqlDbCurrent;
			$rsqlDbMax += $sqlDbMax;
		}

		if($sqlUserMax != -1) {
			if($sqlUserMax == 0) {
				$rsqlUserUf = '_on_';
			}

			$rsqlUserCurrent += $sqlUserCurrent;
			$rsqlUserMax += $sqlUserMax;
		}

		if($traffMax == 0) $rtraffUf = '_on_';

		$rtraffCurrent += $traffCurrent;
		$rtraffMax += $traffMax;

		if($diskMax == 0) {
			$rdiskUf = '_on_';
		}

		$rdiskCurrent += $diskCurrent;
		$rdiskMax += $diskMax;
	}

	return array(
		$rdmnCurrent, $rdmnMax, $rdmnUf, $rsubCurrent, $rsubMax, $rsubUf, $ralsCurrent, $ralsMax, $ralsUf,
		$rmailCurrent, $rmailMax, $rmailUf, $rftpCurrent, $rftpMax, $rftpUf, $rsqlDbCurrent, $rsqlDbMax,  $rsqlDbUf,
		$rsqlUserCurrent, $rsqlUserMax, $rsqlUserUf, $rtraffCurrent, $rtraffMax, $rtraffUf, $rdiskCurrent, $rdiskMax,
		$rdiskUf
	);
}

/**
 * Returns information about customer traffic and disk usage
 *
 * @throws iMSCP_Exception in case customer main domain is not found
 * @param int $customerId Customer unique identifier
 * @return array An array containing information about customer traffic and disk usage
 */
function get_user_trafficAndDiskUsage($customerId)
{
	$query = "
		SELECT
			domain_id,
			IFNULL(domain_disk_usage, 0) AS diskspace_usage,
			IFNULL(domain_traffic_limit, 0) AS monthly_traffic_limit,
			IFNULL(domain_disk_limit, 0) AS diskspace_limit
		FROM
			domain
		WHERE
			domain_admin_id = ?
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
				YEAR(FROM_UNIXTIME(dtraff_time)) AS tyear,
				MONTH(FROM_UNIXTIME(dtraff_time)) AS tmonth,
				SUM(dtraff_web) AS web,
				SUM(dtraff_ftp) AS ftp,
				SUM(dtraff_mail) AS smtp,
				SUM(dtraff_pop) AS pop,
				SUM(dtraff_web) + SUM(dtraff_ftp) + SUM(dtraff_mail) + SUM(dtraff_pop) AS total
			FROM
				domain_traffic
			WHERE
				domain_id = ?
			GROUP BY
				tyear, tmonth
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
 * @param int $adminId Customer unique identifier
 * @return array An array that contain user's properties
 */
function get_user_props($adminId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query('SELECT * FROM domain WHERE domain_id = ?', $adminId);

	if (!$stmt->rowCount()) {
		return array_fill(0, 14, 0);
	}

	$data = $stmt->fetchRow();
	$sub_current = get_domain_running_sub_cnt($adminId);
	$sub_max = $data['domain_subd_limit'];
	$als_current = records_count('domain_aliasses', 'domain_id', $adminId);
	$als_max = $data['domain_alias_limit'];

	if ($cfg['COUNT_DEFAULT_EMAIL_ADDRESSES']) {
		// Catch all is not a mailbox and haven't to be count
		$mail_current = records_count('mail_users',
			'mail_type NOT RLIKE \'_catchall\' AND domain_id',
			$adminId);
	} else {
		$where = "
				mail_acc != 'abuse'
			AND
				mail_acc != 'postmaster'
			AND
				mail_acc != 'webmaster'
			AND
				mail_type NOT RLIKE '_catchall'
			AND
				domain_id
		";

		$mail_current = records_count('mail_users', $where, $adminId);
	}

	$mail_max = $data['domain_mailacc_limit'];

	$ftp_current = sub_records_rlike_count(
		'domain_name', 'domain', 'domain_id', $adminId, 'userid', 'ftp_users', 'userid', '@', ''
	);

	$ftp_current += sub_records_rlike_count(
		'alias_name', 'domain_aliasses', 'domain_id', $adminId, 'userid', 'ftp_users', 'userid', '@', ''
	);

	$ftp_max = $data['domain_ftpacc_limit'];
	$sql_db_current = records_count('sql_database', 'domain_id', $adminId);
	$sql_db_max = $data['domain_sqld_limit'];
	$sql_user_current = get_domain_running_sqlu_acc_cnt($adminId);
	$sql_user_max = $data['domain_sqlu_limit'];
	$traff_max = $data['domain_traffic_limit'];
	$disk_max = $data['domain_disk_limit'];

	return array(
		$sub_current, $sub_max, $als_current, $als_max, $mail_current, $mail_max, $ftp_current, $ftp_max,
		$sql_db_current, $sql_db_max, $sql_user_current, $sql_user_max, $traff_max, $disk_max
	);
}

/**
 * Must be documented
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
		$countQuery = "
			SELECT
				COUNT(domain_id) AS cnt
			FROM
				domain
			INNER JOIN
				admin ON(admin_id = domain_admin_id)
			WHERE
				created_by = '$resellerId'
		";

		$searchQuery = "
			SELECT
				*
			FROM
				domain
			INNER JOIN
				admin ON(admin_id = domain_admin_id)
			WHERE
				created_by = '$resellerId'
			ORDER BY
				domain_name ASC
			LIMIT
				$startIndex, $rowsPerPage
		";
	} elseif ($searchFor == '' && $searchStatus != '') {
		if ($searchStatus == 'all') {
			$addQuery = "created_by = '$resellerId'";
		} else {
			$addQuery = "created_by = '$resellerId' AND domain_status = '$searchStatus'";
		}

		$countQuery = "SELECT COUNT(domain_id) AS cnt FROM domain WHERE $addQuery";

		$searchQuery = "
			SELECT
				*
			FROM
				domain
			INNER JOIN
				admin ON(admin_id = domain_admin_id)
			WHERE
				$addQuery
			ORDER BY
				domain_name ASC
			LIMIT
				$startIndex, $rowsPerPage
		";
	} elseif ($searchFor != '') {
		if ($searchCommon == 'domain_name') {
			$searchFor = encode_idna($searchFor);
			$addQuery = "WHERE admin_name RLIKE '" . addslashes($searchFor) . "' %s";
		} elseif ($searchCommon == 'customer_id') {
			$addQuery = "WHERE customer_id RLIKE '" . addslashes($searchFor) . "' %s";
		} elseif ($searchCommon == 'lname') {
			$addQuery = "WHERE (lname RLIKE '" . addslashes($searchFor) .
				"' OR fname RLIKE '" . addslashes($searchFor) . "') %s";
		} elseif ($searchCommon == 'firm') {
			$addQuery = "WHERE firm RLIKE '" . addslashes($searchFor) . "' %s";
		} elseif ($searchCommon == 'city') {
			$addQuery = "WHERE city RLIKE '" . addslashes($searchFor) . "' %s";
		} elseif ($searchCommon == 'state') {
			$addQuery = "WHERE state RLIKE '" . addslashes($searchFor) . "' %s";
		} elseif ($searchCommon == 'country') {
			$addQuery = "WHERE country RLIKE '" . addslashes($searchFor) . "' %s";
		}

		if (isset($addQuery)) {
			if ($searchStatus != 'all') {
				$addQuery = sprintf(
					$addQuery, " AND created_by = '$resellerId' AND domain_status = '$searchStatus'"
				);

				$countQuery = "
				    SELECT
					    COUNT(admin_id) AS cnt
				    FROM
					    admin AS t1, domain AS t2
				    $addQuery
				AND
					t1.admin_id = t2.domain_admin_id
			";
			} else {
				$addQuery = sprintf($addQuery, " AND `created_by` = '$resellerId'");
				$countQuery = "SELECT COUNT(admin_id) AS cnt FROM admin $addQuery";
			}

			$searchQuery = "
			    SELECT
				    t1.admin_id, t1.admin_status, t2.*
			    FROM
				    admin AS t1, domain AS t2
			    $addQuery
			    AND
				    t1.admin_id = t2.domain_admin_id
			    ORDER BY
				    t2.domain_name ASC
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
			$stmt = exec_query('SELECT props FROM hosting_plans WHERE id = ?', $hp);

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

	$stmt = exec_query('SELECT * FROM reseller_props WHERE reseller_id = ?', $resellerId);
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
			set_page_message(tr('You have reached your domain limit.<br />You cannot add more domains.'), 'error');
		}
	}

	if ($maxSubLimit != 0) {
		if ($newSubLimit != -1) {
			if ($newSubLimit == 0) {
				set_page_message(tr('You have a subdomain limit.<br />You cannot add a user with unlimited subdomains.'), 'error');
			} else if ($currentSubLimit + $newSubLimit > $maxSubLimit) {
				set_page_message(tr('You are exceeding your subdomain limit.'), 'error');
			}
		}
	}

	if ($maxAlsLimit != 0) {
		if ($newAlsLimit != -1) {
			if ($newAlsLimit == 0) {
				set_page_message(tr('You have a domain alias limit.<br />You cannot add a user with unlimited domain aliases.'), 'error');
			} else if ($currentAlsLimit + $newAlsLimit > $maxAlsLimit) {
				set_page_message(tr('You are exceeding you domain alias limit.'), 'error');
			}
		}
	}

	if ($maxMailLimit != 0) {
		if ($newMailLimit == 0) {
			set_page_message(tr('You have an email account limit.<br />You cannot add a user with unlimited email accounts.'), 'error');
		} else if ($currentMailLimit + $newMailLimit > $maxMailLimit) {
			set_page_message(tr('You are exceeding your email account limit.'), 'error');
		}
	}

	if ($ftpMaxLimit != 0) {
		if ($newFtpLimit == 0) {
			set_page_message(tr('You have a FTP account limit!<br />You cannot add a user with unlimited FTP accounts.'), 'error');
		} else if ($currentFtpLimit + $newFtpLimit > $ftpMaxLimit) {
			set_page_message(tr('You are exceeding your FTP account limit.'), 'error');
		}
	}

	if ($maxSqlDbLimit != 0) {
		if ($newSqlDbLimit != -1) {
			if ($newSqlDbLimit == 0) {
				set_page_message(tr('You have a SQL database limit.<br />You cannot add a user with unlimited SQL databases.'), 'error');
			} else if ($currentSqlDbLimit + $newSqlDbLimit > $maxSqlDbLimit) {
				set_page_message(tr('You are exceeding your SQL database limit.'), 'error');
			}
		}
	}

	if ($maxSqlUserLimit != 0) {
		if ($newSqlUserLimit != -1) {
			if ($newSqlUserLimit == 0) {
				set_page_message(tr('You have a SQL user limit.<br />You cannot add a user with unlimited SQL users.'), 'error');
			} else if ($newSqlDbLimit == -1) {
				set_page_message(tr('You have disabled SQL databases for this user.<br />You cannot have SQL users here.'), 'error');
			} else if ($currentSqlUserLimit + $newSqlUserLimit > $maxSqlUserLimit) {
				set_page_message(tr('You are exceeding your SQL user limit.'), 'error');
			}
		}
	}

	if ($maxTrafficLimit != 0) {
		if ($newTrafficLimit == 0) {
			set_page_message(tr('You have a monthly traffic limit.<br />You cannot add a user with unlimited monthly traffic.'), 'error');
		} else if ($currentTrafficLimit + $newTrafficLimit > $maxTrafficLimit) {
			set_page_message(tr('You are exceeding your monthly traffic limit.'), 'error');
		}
	}

	if ($maxDiskspaceLimit != 0) {
		if ($newDiskspaceLimit == 0) {
			set_page_message(tr('You have a disk space limit.<br />You cannot add a user with unlimited disk space.'), 'error');
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
 * Send alias order email
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

	$stmt = exec_query('SELECT fname, lname FROM admin WHERE admin_id = ?', $userId);
	$userFirstname = $stmt->fields['fname'];
	$userLastname = $stmt->fields['lname'];
	$userEmail = $_SESSION['user_email'];
	$data = get_alias_order_email($resellerId);
	$toName = $data['sender_name'];
	$toEmail = $data['sender_email'];
	$subject = $data['subject'];
	$message = $data['message'];

	$to = ($toName) ? encode_mime_header($toName) . " <$toEmail>" : $toEmail;

	if ($userFirstname && $userLastname) {
		$fromName = "$userFirstname $userLastname";
		$from = encode_mime_header($fromName) . " <$userEmail>";
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

	$subject = encode_mime_header($subject);

	$headers = "From: $from\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
	$headers .= "Content-Transfer-Encoding: 8bit\r\n";
	$headers .= "X-Mailer: i-MSCP Mailer";

	mail($to, $subject, $message, $headers, "-f $userEmail");
}

/**
 * Add default emails accounts for domain or domain alias.
 *
 * @throws iMSCP_Exception_Database
 * @param int $dmnId Domain unique identifier
 * @param string $userEmail User email
 * @param string $dmnName Domain name
 * @param string $dmnType Domain type
 * @param int $subId
 * @return void
 */
function client_mail_add_default_accounts($dmnId, $userEmail, $dmnName, $dmnType = 'domain', $subId = 0)
{
	$forwardType = ($dmnType == 'alias') ? 'alias_forward' : 'normal_forward';
	$resellerEmail = $_SESSION['user_email'];

	$db = iMSCP_Database::getInstance();

	try {
		$db->beginTransaction();

		// Prepare the statement once
		$stmt = $db->getRawInstance()->prepare(
			'
				INSERT INTO mail_users (
					mail_acc, mail_pass, mail_forward, domain_id, mail_type, sub_id, status, mail_auto_respond, quota,
					mail_addr
				) VALUES (
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				)
			'
		);

		foreach(
			array(
				'webmaster' => $userEmail, 'postmaster' => $resellerEmail, 'abuse' => $resellerEmail
			) as $umail => $forwardTo
		) {
			$stmt->execute(
				array(
					$umail, '_no_', $forwardTo, $dmnId, $forwardType, $subId, 'toadd', 0, NULL,
					$umail . '@' . $dmnName
				)
			);
		}

		$db->commit();
	} catch(PDOException $e) {
		$db->rollBack();
		throw new iMSCP_Exception_Database($e->getMessage(), $e->getCode(), null, $e);
	}
}

/**
 * Recalculates the reseller's current properties
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
	exec_query(
		'
			UPDATE
    			reseller_props AS t1
			INNER JOIN(
    			SELECT
					COUNT(domain_id) As dmnCount,
					IFNULL(SUM(IF(domain_subd_limit >= 0, domain_subd_limit, 0)), 0) AS subCount,
					IFNULL(SUM(IF(domain_alias_limit >= 0, domain_alias_limit, 0)), 0) AS alsLimit,
					IFNULL(SUM(IF(domain_mailacc_limit >= 0, domain_mailacc_limit, 0)), 0) AS mailLimit,
					IFNULL(SUM(IF(domain_ftpacc_limit >= 0, domain_ftpacc_limit, 0)), 0) AS ftpLimit,
					IFNULL(SUM(IF(domain_sqld_limit >= 0, domain_sqld_limit, 0)), 0) AS sqldLimit,
					IFNULL(SUM(IF(domain_sqlu_limit >= 0, domain_sqlu_limit, 0)), 0) AS sqluLimit,
					IFNULL(SUM(domain_disk_limit), 0) AS diskLimit,
					IFNULL(SUM(domain_traffic_limit), 0) AS trafficLimit,
    				created_by
    			FROM
        			domain
    			INNER JOIN
        			admin ON(admin_id = domain_admin_id)
    			GROUP BY created_by
			) t2 ON t1.reseller_id = t2.created_by
			SET
    			t1.current_dmn_cnt = t2.dmnCount,
				t1.current_sub_cnt = t2.subCount,
				t1.current_als_cnt = t2.alsLimit,
				t1.current_mail_cnt = t2.mailLimit,
				t1.current_ftp_cnt = t2.ftpLimit,
				t1.current_sql_db_cnt = t2.sqldLimit,
				t1.current_sql_user_cnt = t2.sqluLimit,
				t1.current_disk_amnt = t2.diskLimit,
				t1.current_traff_amnt = t2.trafficLimit
			WHERE
    			t1.reseller_id = ?
    	',
		$resellerId
	);
}

/**
 * Convert datepicker date to Unix-Timestamp
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
			'custom_dns_records' => ($cfg['NAMED_SERVER'] != 'external_server') ? true : false,
			'aps' => ($resellerProps['software_allowed'] != 'no') ? true : false, // aps feature check must be revisted
			'external_mail' => true,
			'backup' => ($cfg['BACKUP_DOMAINS'] != 'no') ? true : false,
			'support' => ($cfg['IMSCP_SUPPORT_SYSTEM'] && $resellerProps['support_system'] == 'yes') ? true : false
		);
	}

	if (!array_key_exists($featureName, $availableFeatures)) {
		throw new iMSCP_Exception(
			sprintf("Feature %s is not known by the resellerHasFeature() function.", $featureName)
		);
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
		$stmt = exec_query(
			'
				SELECT
					COUNT(admin_id) AS cnt
				FROM
					admin
				WHERE
					admin_type = ?
				AND
					created_by = ?
				AND
					admin_status <> ?
			',
			array('user', $_SESSION['user_id'], 'todelete')
		);

		$customerCount = $stmt->fields['cnt'];
	}

	return ($customerCount >= $minNbCustomers);
}

/**
 * Check user data
 *
 * @param  bool $noPass If true skip password check
 * @return bool True if user data are valid, false otherwise
 */
function check_ruser_data($noPass = false)
{
	global $password, $passwordRepeat, $email, $customerId, $firstName, $lastName, $gender, $firm, $street1,
		$street2, $zip, $city, $state, $country, $phone, $fax, $domainIp;

	// Get data for fields from previous page
	if (isset($_POST['userpassword'])) {
		$password = clean_input($_POST['userpassword']);
	} else {
		$password = '';
	}

	if (isset($_POST['userpassword_repeat'])) {
		$passwordRepeat = clean_input($_POST['userpassword_repeat']);
	} else {
		$passwordRepeat = '';
	}

	if (isset($_POST['useremail'])) {
		$email = clean_input($_POST['useremail']);
	} else {
		$email = '';
	}

	if (isset($_POST['useruid'])) {
		$customerId = clean_input($_POST['useruid']);
	} else {
		$customerId = '';
	}

	if (isset($_POST['userfname'])) {
		$firstName = clean_input($_POST['userfname']);
	} else {
		$firstName = '';
	}

	if (isset($_POST['userlname'])) {
		$lastName = clean_input($_POST['userlname']);
	}  else {
		$lastName = '';
	}

	if (isset($_POST['gender']) && get_gender_by_code($_POST['gender'], true) !== null) {
		$gender = $_POST['gender'];
	} else {
		$gender = 'U';
	}

	if (isset($_POST['userfirm'])) {
		$firm = clean_input($_POST['userfirm']);
	} else {
		$firm = '';
	}

	if (isset($_POST['userstreet1'])) {
		$street1 = clean_input($_POST['userstreet1']);
	} else {
		$street1 = '';
	}

	if (isset($_POST['userstreet2'])) {
		$street2 = clean_input($_POST['userstreet2']);
	} else {
		$street2 = '';
	}

	if (isset($_POST['userzip'])) {
		$zip = clean_input($_POST['userzip']);
	} else {
		$zip = '';
	}

	if (isset($_POST['usercity'])) {
		$city = clean_input($_POST['usercity']);
	} else {
		$city = '';
	}

	if (isset($_POST['userstate'])) {
		$state = clean_input($_POST['userstate']);
	} else {
		$state = '';
	}

	if (isset($_POST['usercountry'])) {
		$country = clean_input($_POST['usercountry']);
	} else {
		$country = '';
	}

	if (isset($_POST['userphone'])) {
		$phone = clean_input($_POST['userphone']);
	} else {
		$phone = '';
	}

	if (isset($_POST['userfax'])) {
		$fax = clean_input($_POST['userfax']);
	} else {
		$fax = '';
	}

	if (isset($_POST['domain_ip'])) {
		$domainIp = clean_input($_POST['domain_ip']);
	} else {
		$domainIp = '';
	}

	if (!$noPass) {
		if ('' === $passwordRepeat || '' === $password) {
			set_page_message(tr('Please fill up both data fields for password.'), 'error');
		} elseif ($passwordRepeat !== $password) {
			set_page_message(tr("Passwords do not match."), 'error');
		} else {
			checkPasswordSyntax($password);
		}
	}

	if (!chk_email($email)) {
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
		set_page_message(tr('Incorrect post code length or syntax!'), 'error');
	}

	if (Zend_Session::namespaceIsset('pageMessages')) {
		return false;
	}

	return true;
}
