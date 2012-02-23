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
 * @param  int $reseller_id Reseller unique identifier
 * @return array An array that contains user's properties
 */
function generate_reseller_user_props($reseller_id)
{
    $rdmn_current = $rdmn_max = $rsub_current = $rsub_max = $rals_current =
    $rals_max = $rmail_current = $rmail_max = $rftp_current = $rftp_max =
    $rsql_db_current = $rsql_db_max = $rsql_user_current = $rsql_user_max =
    $rtraff_current = $rtraff_max = $rdisk_current = $rdisk_max = 0;

    $rdmn_uf = $rsub_uf = $rals_uf = $rmail_uf = $rftp_uf = $rsql_db_uf =
    $rsql_user_uf = $rtraff_uf = $rdisk_uf = '_off_';

    $query = "SELECT `admin_id` FROM `admin` WHERE `created_by` = ?";
    $stmt = exec_query($query, $reseller_id);

    if ($stmt->rowCount() == 0) {
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

    return array($rdmn_current, $rdmn_max, $rdmn_uf, $rsub_current, $rsub_max,
                 $rsub_uf, $rals_current, $rals_max, $rals_uf, $rmail_current,
                 $rmail_max, $rmail_uf, $rftp_current, $rftp_max, $rftp_uf,
                 $rsql_db_current, $rsql_db_max, $rsql_db_uf, $rsql_user_current,
                 $rsql_user_max, $rsql_user_uf, $rtraff_current, $rtraff_max,
                 $rtraff_uf, $rdisk_current, $rdisk_max, $rdisk_uf);
}

/**
 * Returns user's traffic information.
 *
 * @param  int $user_id User unique identifier
 * @return array An array that contains user's traffic information
 */
function get_user_traffic($user_id)
{
    // Todo Really needed ?
    global $crnt_month, $crnt_year;

    $query = "
		SELECT
			`domain_id`, IFNULL(`domain_disk_usage`, 0) AS `domain_disk_usage`,
			IFNULL(`domain_traffic_limit`, 0) AS `domain_traffic_limit`,
			IFNULL(`domain_disk_limit`,0) AS `domain_disk_limit`, `domain_name`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		ORDER BY
			`domain_id`
	";
    $stmt = exec_query($query, $user_id);

    if ($stmt->rowCount() == 0 || $stmt->rowCount() > 1) {
        return array('n/a', 0, 0, 0, 0, 0, 0, 0, 0, 0);
    } else {
        $data = $stmt->fetchRow();
        $domain_id = $data['domain_id'];
        $domain_disk_usage = $data['domain_disk_usage'];
        $domain_traff_limit = $data['domain_traffic_limit'];
        $domain_disk_limit = $data['domain_disk_limit'];
        $domain_name = $data['domain_name'];

        $query = "
			SELECT
				YEAR(FROM_UNIXTIME(`dtraff_time`)) AS `tyear`,
				MONTH(FROM_UNIXTIME(`dtraff_time`)) AS `tmonth`,
				SUM(`dtraff_web`) AS `web`, SUM(`dtraff_ftp`) AS `ftp`,
				SUM(`dtraff_mail`) AS `smtp`, SUM(`dtraff_pop`) AS `pop`,
				SUM(`dtraff_web`) + SUM(`dtraff_ftp`) + SUM(`dtraff_mail`) +
				SUM(`dtraff_pop`) AS `total`
			FROM
				`domain_traffic`
			WHERE
				`domain_id` = ?
			GROUP BY
				`tyear`, `tmonth`
		";
        $stmt = exec_query($query, $domain_id);

        $max_traffic_month =
        $data['web'] = $data['ftp'] = $data['smtp'] =
        $data['pop'] = $data['total'] = 0;

        while ($row = $stmt->fetchRow()) {
            $data['web'] += $row['web'];
            $data['ftp'] += $row['ftp'];
            $data['smtp'] += $row['smtp'];
            $data['pop'] += $row['total'];

            if ($row['total'] > $max_traffic_month) {
                $max_traffic_month = $row['total'];
            }
        }

        return array($domain_name, $domain_id, $data['web'], $data['ftp'],
                     $data['smtp'], $data['pop'], $data['total'], $domain_disk_usage,
                     $domain_traff_limit, $domain_disk_limit, $max_traffic_month);
    }
}

/**
 * Returns user's properties from database.
 *
 * @param  int $user_id User unique identifier
 * @return array An array that contain user's properties
 */
function get_user_props($user_id)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $query = "SELECT * FROM `domain` WHERE `domain_id` = ?";
    $stmt = exec_query($query, $user_id);

    if ($stmt->rowCount() == 0) {
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
    $ftp_current = sub_records_rlike_count('domain_name', 'domain', 'domain_id',
                                           $user_id, 'userid', 'ftp_users', 'userid',
                                           '@', '');

    $ftp_current += sub_records_rlike_count('alias_name', 'domain_aliasses',
                                            'domain_id', $user_id, 'userid',
                                            'ftp_users', 'userid', '@', '');

    $ftp_max = $data['domain_ftpacc_limit'];
    $sql_db_current = records_count('sql_database', 'domain_id', $user_id);
    $sql_db_max = $data['domain_sqld_limit'];
    $sql_user_current = get_domain_running_sqlu_acc_cnt($user_id);
    $sql_user_max = $data['domain_sqlu_limit'];
    $traff_max = $data['domain_traffic_limit'];
    $disk_max = $data['domain_disk_limit'];

    return array($sub_current, $sub_max, $als_current, $als_max, $mail_current,
                 $mail_max, $ftp_current, $ftp_max, $sql_db_current, $sql_db_max,
                 $sql_user_current, $sql_user_max, $traff_max, $disk_max
    );
}

/**
 * Returns translated status.
 *
 * @param string $status Status to translated
 * @return string Translated status
 */
function translate_dmn_status($status)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    switch ($status) {
        case $cfg->ITEM_OK_STATUS:
            return tr('OK');
        case $cfg->ITEM_ADD_STATUS:
            return tr('Addition in progress');
        case $cfg->ITEM_CHANGE_STATUS:
		case $cfg->ITEM_DNSCHANGE_STATUS:
            return tr('Modification in progress');
        case $cfg->ITEM_DELETE_STATUS:
            return tr('Deletion in progress');
        case $cfg->ITEM_DISABLED_STATUS:
            return tr('Suspended');
        case $cfg->ITEM_TOENABLE_STATUS:
            return tr('Being enabled');
        case $cfg->ITEM_TODISABLED_STATUS:
            return tr('Being suspended');
        case $cfg->ITEM_ORDERED_STATUS:
            return tr('Awaiting approval');
        default:
            return tr('Unknown error');
    }
}

/**
 * Checks if a domain name already exist.
 *
 * @param  string $domain_name Domain name to be checked
 * @param  int $reseller_id Reseller unique identifier
 * @return bool TRUE if the domain already exist, FALSE otherwise
 */
function imscp_domain_exists($domain_name, $reseller_id)
{
    $query_domain = "
		SELECT
			COUNT(`domain_id`) AS `cnt`
		FROM
			`domain`
		WHERE
			`domain_name` = ?
	";
    $stmt_domain = exec_query($query_domain, $domain_name);

    // query to check if the domain name exists in the table for domain aliases
    $query_alias = "
		SELECT
			COUNT(`t1`.`alias_id`) AS `cnt`
		FROM
			`domain_aliasses` AS `t1`, `domain` AS `t2`
		WHERE
			`t1`.`domain_id` = `t2`.`domain_id`
		AND
			`t1`.`alias_name` = ?
	";
    $stmt_aliases = exec_query($query_alias, $domain_name);

    // redefine query to check in the table domain/acounts if 3rd level for this reseller is allowed
    $query_domain = "
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
    $query_alias = "
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
    $split_domain = explode('.', trim($domain_name));
    //$dom_cnt = strlen(trim($domain_name));
    $dom_part_cnt = 0;
    $error = 0;
    // here starts a loop to check if the splitted domain is available for other resellers
    for ($i = 0, $cnt_split_domain = count($split_domain) - 1; $i < $cnt_split_domain; $i++) {
        $dom_part_cnt = $dom_part_cnt + strlen($split_domain[$i]) + 1;
        $idom = substr($domain_name, $dom_part_cnt);
        // execute query the redefined queries for domains/accounts and aliases tables
        $stmt2 = exec_query($query_domain, array($idom, $reseller_id));
        $stmt3 = exec_query($query_alias, array($idom, $reseller_id));

        // do we have available record. id yes => the variable error get value different 0
        if ($stmt2->fields['cnt'] > 0 || $stmt3->fields['cnt'] > 0) {
            $error++;
        }
    }

    // if we have : db entry in the tables domain AND no problem with 3rd level domains
    // AND enduser (no reseller) => the function returns OK => domain can be added
    if ($stmt_domain->fields['cnt'] == 0 && $stmt_aliases->fields['cnt'] == 0 &&
        $error == 0 && $reseller_id == 0
    ) {
        return false;
    }

    // if we have domain add one by end user OR some error => the funcion returns ERROR
    if ($reseller_id == 0 || $error) {
        return true;
    }
    // ok we do not have end user and we do not have error => the fun goes on :-)
    // query to check if the domain does not exist as subdomain
    $query_build_subdomain = "
		SELECT
			`t1`.`subdomain_name`, `t2`.`domain_name`
		FROM
			`subdomain` AS `t1`, `domain` AS `t2`
		WHERE
			`t1`.`domain_id` = `t2`.`domain_id`
		AND
			`t2`.`domain_created_id` = ?
	";
    $subdomains = array();

    $res_build_sub = exec_query($query_build_subdomain, $reseller_id);

    while (!$res_build_sub->EOF) {
        $subdomains[] = $res_build_sub->fields['subdomain_name'] . '.' .
                        $res_build_sub->fields['domain_name'];
        $res_build_sub->moveNext();
    }

    if ($stmt_domain->fields['cnt'] == 0 && $stmt_aliases->fields['cnt'] == 0
        && !in_array($domain_name, $subdomains)
    ) {
        return false;
    } else {
        return true;
    }
}

/**
 * Must be documented.
 *
 * @param  $search_query
 * @param  $count_query
 * @param  int $reseller_id Reseller unique identifier
 * @param  $start_index
 * @param  $rows_per_page
 * @param  $search_for
 * @param  $search_common
 * @param  $search_status
 * @return void
 */
function gen_manage_domain_query(&$search_query, &$count_query, $reseller_id,
    $start_index, $rows_per_page, $search_for, $search_common,
    $search_status)
{
    // IMHO, this code is an unmaintainable mess and should be replaced - Cliff
    if ($search_for === 'n/a' && $search_common === 'n/a' && $search_status === 'n/a') {
        // We have pure list query;
        $count_query = "
			SELECT
				COUNT(`domain_id`) AS `cnt`
			FROM
				`domain`
			WHERE
				`domain_created_id` = '$reseller_id'
		";

        $search_query = "
			SELECT
				*
			FROM
				`domain`
			WHERE
				`domain_created_id` = '$reseller_id'
			ORDER BY
				`domain_name` ASC
			LIMIT
				$start_index, $rows_per_page
		";
    } elseif ($search_for === '' && $search_status != '') {
        if ($search_status === 'all') {
            $add_query = "`domain_created_id` = '$reseller_id'";
        } else {
            $add_query = "
					`domain_created_id` = '$reseller_id'
				AND
					`domain_status` = '$search_status'
			";
        }

        $count_query = "
			SELECT
				COUNT(`domain_id`) AS `cnt`
			FROM
				`domain`
			WHERE
				$add_query
		";

        $search_query = "
			SELECT
				*
			FROM
				`domain`
			WHERE
				$add_query
			ORDER BY
				`domain_name` ASC
			LIMIT
				$start_index, $rows_per_page
		";
    } elseif ($search_for != '') {
        if ($search_common === 'domain_name') {
            $add_query = "WHERE `admin_name` RLIKE '" . addslashes($search_for) . "' %s";
        } elseif ($search_common === 'customer_id') {
            $add_query = "WHERE `customer_id` RLIKE '" . addslashes($search_for) . "' %s";
        } elseif ($search_common === 'lname') {
            $add_query = "WHERE (`lname` RLIKE '" . addslashes($search_for) .
                         "' OR `fname` RLIKE '" . addslashes($search_for) . "') %s";
        } elseif ($search_common === 'firm') {
            $add_query = "WHERE `firm` RLIKE '" . addslashes($search_for) . "' %s";
        } elseif ($search_common === 'city') {
            $add_query = "WHERE `city` RLIKE '" . addslashes($search_for) . "' %s";
        } elseif ($search_common === 'state') {
            $add_query = "WHERE `state` RLIKE '" . addslashes($search_for) . "' %s";
        } elseif ($search_common === 'country') {
            $add_query = "WHERE `country` RLIKE '" . addslashes($search_for) . "' %s";
        }

        if (isset($add_query)) {
            if ($search_status != 'all') {
                $add_query = sprintf($add_query," AND t1.`created_by` = '$reseller_id' AND t2.`domain_status` = '$search_status'");
                $count_query = "
				    SELECT
					    COUNT(`admin_id`) AS `cnt`
				    FROM
					    `admin` AS `t1`, `domain` AS `t2`
				    $add_query
				AND
					`t1`.`admin_id` = `t2`.`domain_admin_id`
			";
            } else {
                $add_query = sprintf($add_query, " AND `created_by` = '$reseller_id'");
                $count_query = "
				    SELECT
					    COUNT(`admin_id`) AS `cnt`
				    FROM
					    `admin`
				    $add_query
			    ";
            }

            $search_query = "
			    SELECT
				    `t1`.`admin_id`, `t2`.*
			    FROM
				    `admin` AS `t1`, `domain` AS `t2`
			    $add_query
			    AND
				    `t1`.`admin_id` = `t2`.`domain_admin_id`
			    ORDER BY
				    `t2`.`domain_name` ASC
			    LIMIT
				    $start_index, $rows_per_page
		    ";
        }
    }
}

/**
 * Must be documented.
 *
 * @param  $err_msg
 * @param  int $reseller_id
 * @param  int $hpid
 * @param string $newprops
 * @return bool
 */
function reseller_limits_check($reseller_id, $hpid, $newprops = '')
{
    if (empty($newprops)) {
        // this hosting plan exists
        if (isset($_SESSION["ch_hpprops"])) {
            $props = $_SESSION["ch_hpprops"];
        } else {
            $query = "SELECT `props` FROM `hosting_plans` WHERE `id` = ?";
            $stmt = exec_query($query, $hpid);
            $data = $stmt->fetchRow();
            $props = $data['props'];
        }
    } else {
        $props = $newprops;
    }

    list(
        , , $sub_new, $als_new, $mail_new, $ftp_new, $sql_db_new, $sql_user_new,
        $traff_new, $disk_new
        ) = explode(';', $props);

    $query = "SELECT * FROM `reseller_props` WHERE `reseller_id` = ?";

    $res = exec_query($query, $reseller_id);
    $data = $res->fetchRow();
    $dmn_current = $data['current_dmn_cnt'];
    $dmn_max = $data['max_dmn_cnt'];
    $sub_current = $data['current_sub_cnt'];
    $sub_max = $data['max_sub_cnt'];
    $als_current = $data['current_als_cnt'];
    $als_max = $data['max_als_cnt'];
    $mail_current = $data['current_mail_cnt'];
    $mail_max = $data['max_mail_cnt'];
    $ftp_current = $data['current_ftp_cnt'];
    $ftp_max = $data['max_ftp_cnt'];
    $sql_db_current = $data['current_sql_db_cnt'];
    $sql_db_max = $data['max_sql_db_cnt'];
    $sql_user_current = $data['current_sql_user_cnt'];
    $sql_user_max = $data['max_sql_user_cnt'];
    $traff_current = $data['current_traff_amnt'];
    $traff_max = $data['max_traff_amnt'];
    $disk_current = $data['current_disk_amnt'];
    $disk_max = $data['max_disk_amnt'];

    if ($dmn_max != 0) {
        if ($dmn_current + 1 > $dmn_max) {
            set_page_message(tr('You have reached your domains limit.<br />You cannot add more domains.'), 'error');
        }
    }

    if ($sub_max != 0) {
        if ($sub_new != -1) {
            if ($sub_new == 0) {
                set_page_message(tr('You have a subdomains limit.<br />You cannot add an user with unlimited subdomains.'), 'error');
            } else if ($sub_current + $sub_new > $sub_max) {
                set_page_message(tr('You are exceeding your subdomains limit.'), 'error');
            }
        }
    }

    if ($als_max != 0) {
        if ($als_new != -1) {
            if ($als_new == 0) {
                set_page_message(tr('You have an aliases limit.<br />You cannot add an user with unlimited aliases.'), 'error');
            } else if ($als_current + $als_new > $als_max) {
                set_page_message(tr('You Are Exceeding Your Alias Limit.'));
            }
        }
    }

    if ($mail_max != 0) {
        if ($mail_new == 0) {
            set_page_message(tr('You have a mail accounts limit.<br />You cannot add an user with unlimited mail accounts.'), 'error');
        } else if ($mail_current + $mail_new > $mail_max) {
            set_page_message(tr('You are exceeding your mail accounts limit.'), 'error');
        }
    }

    if ($ftp_max != 0) {
        if ($ftp_new == 0) {
            set_page_message(tr('You have a FTP accounts limit!<br />You cannot add an user with unlimited FTP accounts.'), 'error');
        } else if ($ftp_current + $ftp_new > $ftp_max) {
            set_page_message(tr('You are exceeding your FTP accounts limit.'), 'error');
        }
    }

    if ($sql_db_max != 0) {
        if ($sql_db_new != -1) {
            if ($sql_db_new == 0) {
                set_page_message(tr('You have a SQL databases limit.<br />You cannot add an user with unlimited SQL databases.'), 'error');
            } else if ($sql_db_current + $sql_db_new > $sql_db_max) {
                set_page_message(tr('You are exceeding your SQL databases limit.'), 'error');
            }
        }
    }

    if ($sql_user_max != 0) {
        if ($sql_user_new != -1) {
            if ($sql_user_new == 0) {
                set_page_message(tr('You have an SQL users limit.<br />You cannot add an user with unlimited SQL users.'), 'error');
            } else if ($sql_db_new == -1) {
                set_page_message(tr('You have disabled SQL databases for this user.<br />You cannot have SQL users here.'), 'error');
            } else if ($sql_user_current + $sql_user_new > $sql_user_max) {
                set_page_message(tr('You are exceeding your SQL database limit.'), 'error');
            }
        }
    }

    if ($traff_max != 0) {
        if ($traff_new == 0) {
            set_page_message(tr('You have a traffic limit.<br />You cannot add an user with unlimited traffic.'), 'error');
        } else if ($traff_current + $traff_new > $traff_max) {
            set_page_message(tr('You are exceeding your traffic limit.'), 'error');
        }
    }

    if ($disk_max != 0) {
        if ($disk_new == 0) {
            set_page_message(tr('You have a disk limit.<br />You cannot add an user with unlimited disk.'), 'error');
        } else if ($disk_current + $disk_new > $disk_max) {
            set_page_message(tr('You are exceeding your disk limit.'), 'error');
        }
    }

    if (Zend_Session::namespaceIsset('pageMessages')) {
        return false;
    }

    return true;
}

/**
 * Sends order emails to customer.
 *
 * @param int $resellerId Resller unique identifier
 * @param string $domainName Domain name ordered
 * @param string $userFirstName Customer first name
 * @param string $userLastName Customer last name
 * @param string $userEmail Customer email
 * @param int $orderId Order unique identifier
 * @return void
 */
function send_order_emails($resellerId, $domainName, $userFirstName, $userLastName,
    $userEmail, $orderId)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $data = get_order_email($resellerId);
    $fromName = $data['sender_name'];
    $fromEmail = $data['sender_email'];
    $subject = $data['subject'];
    $message = $data['message'];

    if ($fromName) {
        $from = '"' . encode($fromName) . "\" <" . $fromEmail . ">";
    } else {
        $from = $fromEmail;
    }

    if ($userFirstName && $userLastName) {
        $name = "$userFirstName $userLastName";
        $to = '"' . encode($name) . "\" <" . $userEmail . ">";
    } else {
        if ($userFirstName) {
            $name = $userFirstName;
        } else if ($userLastName) {
            $name = $userLastName;
        } else {
            $name = $userEmail;
        }

        $to = $userEmail;
    }

    $activateLink = $cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST;
    $coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';
    $key = sha1($orderId . '-' . $domainName . '-' . $resellerId . '-' . $coid);
    $activateLink .= '/orderpanel/activate.php?id=' . $orderId . '&k=' . $key;

    $search = array();
    $replace = array();
    $search [] = '{DOMAIN}';
    $replace[] = $domainName;
    $search [] = '{MAIL}';
    $replace[] = $userEmail;
    $search [] = '{NAME}';
    $replace[] = $name;
    $search [] = '{ACTIVATION_LINK}';
    $replace[] = $activateLink;
    $search[]  = '{EXPIRE_DATE}';
    $replace[] = date('d/m/Y', time() + $cfg->ORDERS_EXPIRE_TIME);

    $subject = str_replace($search, $replace, $subject);
    $message = str_replace($search, $replace, $message);
    $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
    $subject = encode($subject);

    $headers = "From: " . $from . "\n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\n";
    $headers .= "Content-Transfer-Encoding: 8bit\n";
    $headers .= "X-Mailer: i-MSCP " . $cfg->Version . " Service Mailer";

    mail($to, $subject, $message, $headers);
}

/**
 * Must be documented.
 *
 * @param  string $alias_name
 * @return void
 */
function send_alias_order_email($alias_name)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $user_id = $_SESSION['user_id'];
    $reseller_id = who_owns_this($user_id, 'user');

    $query = 'SELECT `fname`, `lname` FROM `admin` WHERE `admin_id` = ?;';
    $stmt = exec_query($query, $user_id);
    $ufname = $stmt->fields['fname'];
    $ulname = $stmt->fields['lname'];
    $uemail = $_SESSION['user_email'];
    $data = get_alias_order_email($reseller_id);
    $to_name = $data['sender_name'];
    $to_email = $data['sender_email'];
    $subject = $data['subject'];
    $message = $data['message'];

    // to
    $to = ($to_name) ? '"' . encode($to_name) . "\" <" . $to_email . ">" : $to_email;

    // from
    if ($ufname && $ulname) {
        $from_name = "$ufname $ulname";
        $from = '"' . encode($from_name) . "\" <" . $uemail . ">";
    } else {
        if ($ufname) {
            $from_name = $ufname;
        } else if ($ulname) {
            $from_name = $ulname;
        } else {
            $from_name = $uemail;
        }
        $from = $uemail;
    }
    $search = array();
    $replace = array();

    $search [] = '{RESELLER}';
    $replace[] = $to_name;
    $search [] = '{CUSTOMER}';
    $replace[] = $from_name;
    $search [] = '{ALIAS}';
    $replace[] = $alias_name;
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
 * Adds the 3 mail accounts/forwardings to a new domain....
 * @param int $dmn_id
 * @param string $user_email
 * @param string $dmn_part
 * @param string $dmn_type
 * @param int $sub_id
 * @return void
 */
function client_mail_add_default_accounts($dmn_id, $user_email, $dmn_part,
    $dmn_type = 'domain', $sub_id = 0)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if ($cfg->CREATE_DEFAULT_EMAIL_ADDRESSES) {
        $forward_type = ($dmn_type == 'alias') ? 'alias_forward' : 'normal_forward';

        // prepare SQL
        $query = "
			INSERT INTO
			    mail_users (
				    `mail_acc`, `mail_pass`, `mail_forward`,`domain_id`, `mail_type`,
				    `sub_id`, `status`, `mail_auto_respond`,`quota`, `mail_addr`
				) VALUES (
				    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				)
		";

        // create default forwarder for webmaster@domain.tld to the account's owner
        exec_query($query, array('webmaster', '_no_', $user_email, $dmn_id,
                                $forward_type, $sub_id, $cfg->ITEM_ADD_STATUS,
                                '_no_', 10485760, 'webmaster@' . $dmn_part));

        // create default forwarder for postmaster@domain.tld to the account's reseller
        exec_query($query, array('postmaster', '_no_', $_SESSION['user_email'],
                                $dmn_id, $forward_type, $sub_id,
                                $cfg->ITEM_ADD_STATUS, '_no_', 10485760,
                                'postmaster@' . $dmn_part));

        // create default forwarder for abuse@domain.tld to the account's reseller
        exec_query($query, array('abuse', '_no_', $_SESSION['user_email'],
                                $dmn_id, $forward_type, $sub_id,
                                $cfg->ITEM_ADD_STATUS, '_no_', 10485760,
                                'abuse@' . $dmn_part));
    }
}

/**
 * Recalculate current_ properties of reseller
 *
 * @param int $reseller_id unique reseller identifiant
 * @return array list of properties
 */
function recalc_reseller_c_props($reseller_id)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $delstatus = $cfg->ITEM_DELETE_STATUS;

    // Get all users of reseller:
    $query = "
		SELECT
			COUNT(`domain_id`) AS crn_domains,
			IFNULL(SUM(IF(`domain_subd_limit` >= 0, `domain_subd_limit`, 0)), 0) AS current_sub_cnt,
			IFNULL(SUM(IF(`domain_alias_limit` >= 0, `domain_alias_limit`, 0)), 0) AS current_als_cnt,
			IFNULL(SUM(IF(`domain_mailacc_limit` >= 0, `domain_mailacc_limit`, 0)), 0) AS current_mail_cnt,
			IFNULL(SUM(IF(`domain_ftpacc_limit` >= 0, `domain_ftpacc_limit`, 0)), 0) AS current_ftp_cnt,
			IFNULL(SUM(IF(`domain_sqld_limit` >= 0, `domain_sqld_limit`, 0)), 0) AS current_sql_db_cnt,
			IFNULL(SUM(IF(`domain_sqlu_limit` >= 0, `domain_sqlu_limit`, 0)), 0) AS current_sql_user_cnt,
			IFNULL(SUM(`domain_disk_limit`), 0) AS current_disk_amnt,
			IFNULL(SUM(`domain_traffic_limit`), 0) AS current_traff_amnt
		FROM
			`domain`
		WHERE
			`domain_created_id` = ?
		AND
			`domain_status` != ?
	";
    $stmt = exec_query($query, array($reseller_id, $delstatus));

    $current_dmn_cnt = $stmt->fields['crn_domains'];

    if ($current_dmn_cnt > 0) {
        $current_sub_cnt = $stmt->fields['current_sub_cnt'];
        $current_als_cnt = $stmt->fields['current_als_cnt'];
        $current_mail_cnt = $stmt->fields['current_mail_cnt'];
        $current_ftp_cnt = $stmt->fields['current_ftp_cnt'];
        $current_sql_db_cnt = $stmt->fields['current_sql_db_cnt'];
        $current_sql_user_cnt = $stmt->fields['current_sql_user_cnt'];
        $current_disk_amnt = $stmt->fields['current_disk_amnt'];
        $current_traff_amnt = $stmt->fields['current_traff_amnt'];
    } else {
        $current_sub_cnt = $current_als_cnt = $current_mail_cnt = $current_ftp_cnt =
        $current_sql_db_cnt = $current_sql_user_cnt = $current_disk_amnt =
        $current_traff_amnt = 0;
    }

    return array($current_dmn_cnt, $current_sub_cnt, $current_als_cnt,
                 $current_mail_cnt, $current_ftp_cnt, $current_sql_db_cnt,
                 $current_sql_user_cnt, $current_disk_amnt, $current_traff_amnt);
}

/**
 * Recalculates the reseller's current properties.

 * @param int $reseller_id unique reseller identifiant
 * @return void
 */
function update_reseller_c_props($reseller_id)
{
    $query = "
		UPDATE
			`reseller_props`
		SET
			`current_dmn_cnt` = ?, `current_sub_cnt` = ?, `current_als_cnt` = ?,
			`current_mail_cnt` = ?, `current_ftp_cnt` = ?, `current_sql_db_cnt` = ?,
			`current_sql_user_cnt` = ?, `current_disk_amnt` = ?, `current_traff_amnt` = ?
		WHERE
			`reseller_id` = ?
	";

    $props = recalc_reseller_c_props($reseller_id);
    $props[] = $reseller_id;
    exec_query($query, $props);
}

/**
 * Returns the reseller id from a specific domain.
 *
 * @param int $domain_id Domain unique identifier
 * @return int Reseller unique identifier or 0 in on error
 */
function get_reseller_id($domain_id)
{
    $query = "
		SELECT
			a.`created_by`
		FROM
			`domain` d, `admin` a
		WHERE
			d.`domain_id` = ?
		AND
			d.`domain_admin_id` = a.`admin_id`
	";
    $stmt = exec_query($query, $domain_id);

    if ($stmt->recordCount() == 0) {
        return 0;
    }

    $data = $stmt->fetchRow();
    return $data['created_by'];
}

/**
 * Checks if a reseller has the rights to an option.
 *
 * @param  int $resellerId Reseller unique identifier
 * @param  string $permission Permission to check
 * @return bool|array boolean option permissions or array with all options
 */
function check_reseller_permissions($resellerId, $permission)
{
	$resellerProperties = imscp_getResellerProperties($resellerId);

	if ($permission == 'all_permissions') {
		return array(
			$resellerProperties['max_sub_cnt'], $resellerProperties['max_als_cnt'],
			$resellerProperties['max_mail_cnt'], $resellerProperties['max_ftp_cnt'],
			$resellerProperties['max_sql_db_cnt'], $resellerProperties['max_sql_user_cnt']);
	} elseif ($permission == 'subdomain' && $resellerProperties['max_sub_cnt'] == '-1') {
		return false;
	} elseif ($permission == 'alias' && $resellerProperties['max_als_cnt'] == '-1') {
		return false;
	} elseif ($permission == 'mail' && $resellerProperties['max_mail_cnt'] == '-1') {
		return false;
	} elseif ($permission == 'ftp' && $resellerProperties['max_ftp_cnt'] == '-1') {
		return false;
	} elseif ($permission == 'sql_db' && $resellerProperties['max_sql_db_cnt'] == '-1') {
		return false;
	} elseif ($permission == 'sql_user' && $resellerProperties['max_sql_user_cnt'] == '-1') {
		return false;
	}

	return true;
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
 */
function resellerHasFeature($featureName, $forceReload = false)
{
	static $availableFeatures = null;
	$featureName = strtolower($featureName);

	if (null == $availableFeatures || $forceReload) {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		$resellerProps = imscp_getResellerProperties((int)$_SESSION['user_id'], true);

		$availableFeatures = array(
			'domains' => ($resellerProps['max_dmn_cnt'] != '-1') ? true : false,
			'subdomains' => ($resellerProps['max_sub_cnt'] != '-1') ? true : false,
			'domain_aliases' => ($resellerProps['max_als_cnt'] != '-1') ? true : false,
			'mail' => ($resellerProps['max_mail_cnt'] != '-1') ? true : false,
			'ftp' => ($resellerProps['max_ftp_cnt'] != '-1') ? true : false,
			'sql' => ($resellerProps['max_sql_db_cnt'] != '-1') ? true : false,
			'php_editor' => ($resellerProps['php_ini_system'] == 'yes') ? true : false,
			'backup' => ($cfg->BACKUP_DOMAINS != 'no') ? true : false,
			'aps' => ($resellerProps['software_allowed'] != 'no') ? true : false, // aps feature check must be revisted
			'support' => ($cfg->IMSCP_SUPPORT_SYSTEM && $resellerProps['support_system'] == 'yes') ? true : false
		);
	}

	if (!array_key_exists($featureName, $availableFeatures)) {
		throw new iMSCP_Exception(sprintf("Feature %s is not known by the resellerHasFeature() function.", $featureName));
	}

	return $availableFeatures[$featureName];
}
