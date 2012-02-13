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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
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
 * Returns reseller user's properties.
 *
 * @param  int $reseller_id Reseller unique indentifier
 * @return array
 */
function generate_reseller_users_props($reseller_id)
{
    $rdmn_current = $rdmn_max = $rsub_current = $rsub_max = $rals_current =
    $rals_max = $rmail_current = $rmail_max = $rftp_current = $rftp_max =
    $rsql_db_current = $rsql_db_max = $rsql_user_current = $rsql_user_max =
    $rtraff_current = $rtraff_max = $rdisk_current = $rdisk_max = 0;

    $rdmn_uf = $rsub_uf = $rals_uf = $rmail_uf = $rftp_uf = $rsql_db_uf =
    $rsql_user_uf = $rtraff_uf = $rdisk_uf = '_off_';

    $query = "SELECT `admin_id` FROM `admin` WHERE `created_by` = ?";
    $rs = exec_query($query, $reseller_id);

    if ($rs->rowCount() == 0) {
        return array(
            $rdmn_current, $rdmn_max, $rdmn_uf, $rsub_current, $rsub_max, $rsub_uf,
            $rals_current, $rals_max, $rals_uf, $rmail_current, $rmail_max, $rmail_uf,
            $rftp_current, $rftp_max, $rftp_uf, $rsql_db_current, $rsql_db_max,
            $rsql_db_uf, $rsql_user_current, $rsql_user_max, $rsql_user_uf,
            $rtraff_current, $rtraff_max, $rtraff_uf, $rdisk_current, $rdisk_max,
            $rdisk_uf);
    }

    while (!$rs->EOF) {
        $admin_id = $rs->fields['admin_id'];

        $query = "SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?";

        $dres = exec_query($query, $admin_id);
        $user_id = $dres->fields['domain_id'];

        list($sub_current, $sub_max, $als_current, $als_max, $mail_current,
            $mail_max, $ftp_current, $ftp_max, $sql_db_current, $sql_db_max,
            $sql_user_current, $sql_user_max, $traff_max, $disk_max
            ) = generate_user_props($user_id);

        list(, , , , , , $traff_current, $disk_current) = generate_user_traffic($user_id);

        $rdmn_current += 1;

        if ($sub_max != -1) {
            if ($sub_max == 0) {
                $rsub_uf = '_on_';
            }

            $rsub_current += $sub_current;
            $rsub_max += ($sub_max > 0) ? $sub_max : 0;
        }

        if ($als_max != -1) {
            if ($als_max == 0) {
                $rals_uf = '_on_';
            }

            $rals_current += $als_current;
            $rals_max += ($als_max > 0) ? $als_max : 0;
        }

        if ($mail_max == 0) {
            $rmail_uf = '_on_';
        }

        $rmail_current += $mail_current;
        $rmail_max += ($mail_max > 0) ? $mail_max : 0;

        if ($ftp_max == 0) {
            $rftp_uf = '_on_';
        }

        $rftp_current += $ftp_current;
        $rftp_max += ($ftp_max > 0) ? $ftp_max : 0;

        if ($sql_db_max != -1) {
            if ($sql_db_max == 0) {
                $rsql_db_uf = '_on_';
            }

            $rsql_db_current += $sql_db_current;
            $rsql_db_max += ($sql_db_max > 0) ? $sql_db_max : 0;
        }

        if ($sql_user_max != -1) {
            if ($sql_user_max == 0) {
                $rsql_user_uf = '_on_';
            }

            $rsql_user_current += $sql_user_current;
            $rsql_user_max += ($sql_user_max > 0) ? $sql_user_max : 0;
        }

        if ($traff_max == 0) {
            $rtraff_uf = '_on_';
        }

        $rtraff_current += $traff_current;
        $rtraff_max += $traff_max;

        if ($disk_max == 0) {
            $rdisk_uf = '_on_';
        }

        $rdisk_current += $disk_current;
        $rdisk_max += $disk_max;
        $rs->moveNext();
    }

    return array(
        $rdmn_current, $rdmn_max, $rdmn_uf, $rsub_current, $rsub_max, $rsub_uf,
        $rals_current, $rals_max, $rals_uf, $rmail_current, $rmail_max, $rmail_uf,
        $rftp_current, $rftp_max, $rftp_uf, $rsql_db_current, $rsql_db_max,
        $rsql_db_uf, $rsql_user_current, $rsql_user_max, $rsql_user_uf,
        $rtraff_current, $rtraff_max, $rtraff_uf, $rdisk_current, $rdisk_max,
        $rdisk_uf);
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
