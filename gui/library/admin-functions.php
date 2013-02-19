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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
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

/**
 * Returns count of SQL users.
 *
 * @return int Number of SQL users
 */
function get_sql_user_count()
{
    $query = "SELECT DISTINCT `sqlu_name` FROM `sql_user`";
    $rs = execute_query($query);

    return $rs->recordCount();
}

/**
 * Returns the total number of consumed and assigned items for the given reseller.
 *
 * @param  int $resellerId Reseller unique indentifier
 * @return array
 */
function generate_reseller_users_props($resellerId)
{
    $rdmnConsumed = $rdmnAssigned = $rsubConsumed = $rsubAssigned = $ralsConsumed = $ralsAssigned = $rmailConsumed =
	$rmailAssigned = $rftpConsumed = $rftpAssigned = $rsqlDbConsumed = $rsqlDbAssigned = $rsqlUserConsumed =
	$rsqlUserAssigned = $rtraffConsumed = $rtraffAssigned = $rdiskConsumed = $rdiskAssigned = 0;

    $query = "SELECT `admin_id` FROM `admin` WHERE `created_by` = ?";
    $stmt = exec_query($query, $resellerId);

	$rdmnUnlimited = $rsubUnlimited = $ralsUnlimited = $rmailUnlimited = $rftpUnlimited = $rsqlDbUnlimited =
	$rsqlUserUnlimited = $rtraffUnlimited = $rdiskUnlimited = false;


    if (!$stmt->rowCount()) { // Case in reseller has not customer yet
        return array(
            $rdmnConsumed, $rdmnAssigned, $rdmnUnlimited,
			$rsubConsumed, $rsubAssigned, $rsubUnlimited,
			$ralsConsumed, $ralsAssigned, $ralsUnlimited,
			$rmailConsumed, $rmailAssigned, $rmailUnlimited,
			$rftpConsumed, $rftpAssigned, $rftpUnlimited,
			$rsqlDbConsumed, $rsqlDbAssigned, $rsqlDbUnlimited,
			$rsqlUserConsumed, $rsqlUserAssigned, $rsqlUserUnlimited,
			$rtraffConsumed, $rtraffAssigned, $rtraffUnlimited,
			$rdiskConsumed, $rdiskAssigned, $rdiskUnlimited
        );
    }

    while (!$stmt->EOF) {
        $stmt2 = exec_query("SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?", $stmt->fields['admin_id']);
        $userId = $stmt2->fields['domain_id'];

        list(
			$subConsumed, $subAssigned, $alsConsumed, $alsAssigned, $mailConsumed, $mailAssigned, $ftpConsumed,
			$ftpAssigned, $sqlDbConsumed, $sqlDbAssigned, $sqlUserConsumed, $sqlUserAssigned, $traffAssigned, $diskAssigned
        ) = generate_user_props($userId);

        list(, , , , , , $traffConsumed, $diskConsumed) = generate_user_traffic($userId);

        $rdmnConsumed += 1;

		// Compute subdomains
        if ($subAssigned != -1) {
            $rsubConsumed += $subConsumed;
			$rsubAssigned += $subAssigned;

			if(!$subAssigned) $rsubUnlimited = true;
        }

		// Compute domain aliases
        if ($alsAssigned != -1) {
            $ralsConsumed += $alsConsumed;
			$ralsAssigned += $alsAssigned;

			if(!$alsAssigned) $ralsUnlimited = true;
        }

		// Compute mail accounts
		if ($sqlDbAssigned != -1) {
        	$rmailConsumed += $mailConsumed;
			$rmailAssigned += $mailAssigned;

			if(!$mailAssigned) $rmailUnlimited = true;
		}

		// Compute Ftp account
		if ($ftpAssigned != -1) {
        	$rftpConsumed += $ftpConsumed;
			$rftpAssigned += $ftpAssigned;

			if(!$ftpAssigned) $rftpUnlimited = true;
		}

		// Compute Sql databases
        if ($sqlDbAssigned != -1) {
            $rsqlDbConsumed += $sqlDbConsumed;
			$rsqlDbAssigned += $sqlDbAssigned;

			if(!$sqlDbAssigned) $rsqlDbUnlimited = true;
        }

		// Compute Sql users
        if ($sqlUserAssigned != -1) {
            $rsqlUserConsumed += $sqlUserConsumed;
			$rsqlUserAssigned += $sqlUserAssigned;

			if(!$sqlUserAssigned) $rsqlUserUnlimited = true;
        }

		// Compute Monthly traffic volume
        $rtraffConsumed += $traffConsumed;
        $rtraffAssigned += $traffAssigned;
		if(!$rtraffAssigned) $rtraffUnlimited = true;

		// Compute diskspace
        $rdiskConsumed += $diskConsumed;
        $rdiskAssigned += $diskAssigned;
		if(!$rdiskAssigned) $rdiskUnlimited = true;

        $stmt->moveNext();
    }

    return array(
		$rdmnConsumed, $rdmnAssigned, $rdmnUnlimited,
		$rsubConsumed, $rsubAssigned, $rsubUnlimited,
		$ralsConsumed, $ralsAssigned, $ralsUnlimited,
		$rmailConsumed, $rmailAssigned, $rmailUnlimited,
		$rftpConsumed, $rftpAssigned, $rftpUnlimited,
		$rsqlDbConsumed, $rsqlDbAssigned, $rsqlDbUnlimited,
		$rsqlUserConsumed, $rsqlUserAssigned, $rsqlUserUnlimited,
		$rtraffConsumed, $rtraffAssigned, $rtraffUnlimited,
		$rdiskConsumed, $rdiskAssigned, $rdiskUnlimited
	);
}

/**
 * Must be documented
 *
 * @param  $search_query
 * @param  $count_query
 * @param  $start_index
 * @param  $rows_per_page
 * @param  $search_for
 * @param  $search_common
 * @param  $search_status
 * @return void
 */
function gen_admin_domain_query(&$search_query, &$count_query, $start_index,
    $rows_per_page, $search_for, $search_common, $search_status)
{
    if ($search_for == 'n/a' && $search_common == 'n/a' && $search_status == 'n/a') {
        // We have pure list query;
        $count_query = "SELECT COUNT(*) AS `cnt` FROM `domain`";

        $search_query = "
            SELECT
                *
            FROM
                `domain`
            ORDER BY
                `domain_name` ASC
            LIMIT
                $start_index, $rows_per_page
            ;
        ";
    } elseif ($search_for == '' && $search_status != '') {
        if ($search_status == 'all') {
            $add_query = '';
        } else {
            $add_query = " WHERE `domain_status` = '$search_status'";
        }

        $count_query = "SELECT COUNT(*) AS `cnt` FROM `domain` $add_query";
        $search_query = "
            SELECT
                *
            FROM
                `domain` $add_query
            ORDER BY
                `domain_name` ASC
            LIMIT
                $start_index, $rows_per_page
        ";
    } elseif ($search_for != '') {
        if ($search_common == 'domain_name') {
            $add_query = " WHERE `admin_name` RLIKE '$search_for' %s";
        } elseif ($search_common == 'customer_id') {
            $add_query = " WHERE `customer_id` RLIKE '$search_for' %s";
        } elseif ($search_common == 'lname') {
            $add_query = "WHERE (`lname` RLIKE '$search_for' OR `fname` RLIKE '$search_for') %s";
        } elseif ($search_common == 'firm') {
            $add_query = "WHERE `firm` RLIKE '$search_for' %s";
        } elseif ($search_common == 'city') {
            $add_query = "WHERE `city` RLIKE '$search_for' %s";
        } elseif ($search_common == 'state') {
            $add_query = "WHERE `state` ,RLIKE '$search_for' %s ";
        } elseif ($search_common == 'country') {
            $add_query = "WHERE `country` RLIKE '$search_for' %s";
        }

        if(isset($add_query)) {
            if ($search_status != 'all') {
                $add_query = sprintf($add_query,
                                     " AND t2.`domain_status` = '$search_status'");

                $count_query = "
				    SELECT
					    COUNT(*) AS cnt
				    FROM
					    `admin` AS t1, `domain` AS t2
				        $add_query
				    AND
					    t1.`admin_id` = t2.`domain_admin_id`
			    ";
            } else {
                $add_query = sprintf($add_query, ' ');
                $count_query = "SELECT COUNT(*) AS cnt FROM `admin` $add_query";
            }

            $search_query = "
			SELECT
				t1.`admin_id`, t2.*
			FROM
				`admin` AS t1, `domain` AS t2
				$add_query
			AND
				t1.`admin_id` = t2.`domain_admin_id`
			ORDER BY
				t2.`domain_name` ASC
			LIMIT
				$start_index, $rows_per_page
		";
        }
    }
}
