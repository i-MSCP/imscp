<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-MSCP | http://i-mscp.net
 * @version     SVN: $Id$
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 *
 * @license
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/**
 * Returns count of SQL users.
 *
 * @return int Number of SQL users
 */
function get_sql_user_count()
{
    $query = "SELECT DISTINCT `sqlu_name` FROM `sql_user`;";
    $rs = exec_query($query);

    return $rs->recordCount();
}

/**
 * Returns reseller properties.
 *
 * @param  int $reseller_id Reseller unique identifier
 * @return array An array that contains reseller properties
 */
function generate_reseller_props($reseller_id)
{
    $query = "SELECT * FROM `reseller_props` WHERE `reseller_id` = ?;";
    $rs = exec_query($query, $reseller_id);

    if ($rs->rowCount() == 0) {
        return array_fill(0, 18, 0);
    }

    return array(
        $rs->fields['current_dmn_cnt'], $rs->fields['max_dmn_cnt'],
        $rs->fields['current_sub_cnt'], $rs->fields['max_sub_cnt'],
        $rs->fields['current_als_cnt'], $rs->fields['max_als_cnt'],
        $rs->fields['current_mail_cnt'], $rs->fields['max_mail_cnt'],
        $rs->fields['current_ftp_cnt'], $rs->fields['max_ftp_cnt'],
        $rs->fields['current_sql_db_cnt'], $rs->fields['max_sql_db_cnt'],
        $rs->fields['current_sql_user_cnt'], $rs->fields['max_sql_user_cnt'],
        $rs->fields['current_traff_amnt'], $rs->fields['max_traff_amnt'],
        $rs->fields['current_disk_amnt'], $rs->fields['max_disk_amnt']);
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

    $query = "SELECT `admin_id` FROM `admin` WHERE `created_by` = ?;";
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

        $query = "SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?;";

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
        $count_query = "SELECT COUNT(*) AS `cnt` FROM `domain`;";

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
            $add_query = " WHERE `domain_status` = '$search_status';";
        }

        $count_query = "SELECT COUNT(*) AS `cnt` FROM `domain` $add_query;";
        $search_query = "
            SELECT
                *
            FROM
                `domain` $add_query
            ORDER BY
                `domain_name` ASC
            LIMIT
                $start_index, $rows_per_page
            ;
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
				    ;
			    ";
            } else {
                $add_query = sprintf($add_query, ' ');
                $count_query = "SELECT COUNT(*) AS cnt FROM `admin` $add_query;";
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
			;
		";
        }
    }
}

/**
 * Must be documented
 *
 * @param  $software_id
 * @param int $reseller_id Reseller unique identifier
 * @param int $software_master_id
 * @param int $software_deleted
 * @return void
 */
function update_existing_client_installations_res_upload($software_id, $reseller_id, $software_master_id, $software_deleted = FALSE)
{
    $query = "
        SELECT
            `domain_id`
        FROM
            `domain`
        WHERE
            `domain_software_allowed` = 'yes'
        AND
            `domain_created_id` = ?
        ;
    ";
    $res = exec_query($query, $reseller_id);

    if ($res->RecordCount() > 0) {
        while (!$res->EOF) {
            if($software_deleted === FALSE) {
                $update_query = "
                    UPDATE
                        `web_software_inst`
                    SET
                        `software_id` = ?, `software_master_id` = ?, `software_depot` = ?
                    WHERE
                        `software_id` = ?
                    AND
                        `domain_id` = ?
                    ;
                ";
                exec_query($update_query, array($software_id, $software_master_id, 'yes',
                                                $software_master_id, $res->fields['domain_id']
                                                )
                                            );
            } else {
                $update_query = "
                    UPDATE
                        `web_software_inst`
                    SET
                        `software_res_del` = 1
                    WHERE
                        `software_id` = ?
                    AND
                        `domain_id` = ?
                    ;
                ";
                exec_query($update_query, array($software_id, $res->fields['domain_id']));
            }
            $res->MoveNext();
        }
    }
}

/**
 * Must be documented
 *
 * @param  $software_id
 * @param  $software_master_id
 * @param  $reseller_id
 * @return void
 */
function update_existing_client_installations_sw_depot($software_id,
    $software_master_id, $reseller_id)
{
    $query = "
        SELECT
            `domain_id`
        FROM
            `domain`
        WHERE
            `domain_software_allowed` = 'yes'
        AND
            `domain_created_id` = ?
        ;
     ";
    $res = exec_query($query, $reseller_id);

    if ($res->RecordCount() > 0) {
        while (!$res->EOF) {
            $update_query = "
				UPDATE
					`web_software_inst`
				SET
					`software_id` = ?, `software_res_del` = 0
				WHERE
					`software_master_id` = ?
				AND
					`software_res_del` = 1
				AND
					`domain_id` = ?
				;
			";

            exec_query($update_query, array($software_id, $software_master_id,
                                          $res->fields['domain_id']));
            $res->MoveNext();
        }
    }
}

/**
 * Must be documented
 *
 * @param  $reseller_id Reseller unique identifier
 * @param  $file_name
 * @param  $sw_id
 * @return void
 */
function send_activated_sw($reseller_id, $file_name, $sw_id)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $query = "
        SELECT
            `admin_name` as `reseller`, `created_by`, `email` as `res_email`
        FROM
            `admin`
        WHERE
            `admin_id` = ?;
        ;
    ";
    $res = exec_query($query, $reseller_id);

    $to_name = $res->fields['reseller'];
    $to_email = $res->fields['res_email'];
    $admin_id = $res->fields['created_by'];

    $query = "
        SELECT
            `email` as adm_email, `admin_name` as `admin`
        FROM
            `admin`
        WHERE
            `admin_id` = ?
        ;
    ";
    $res = exec_query($query, $admin_id);

    $from_name = $res->fields['admin'];
    $from_email = $res->fields['adm_email'];

    if ($from_name) {
        $from = "\"" . encode($from_name) . "\" <" . $from_email . ">";
    } else {
        $from = $from_email;
    }

    $search = array();
    $replace = array();
    $search [] = '{ADMIN}';
    $replace[] = $from_name;
    $search [] = '{SOFTWARE}';
    $replace[] = $file_name;
    $search [] = '{SOFTWARE_ID}';
    $replace[] = $sw_id;
    $search [] = '{RESELLER}';
    $replace[] = $to_name;

    $headers = "From: " . $from . "\n";
    $headers .= "MIME-Version: 1.0\n" . "Content-Type: text/plain; charset=utf-8\n" .
                "Content-Transfer-Encoding: 8bit\n" . "X-Mailer: i-MSCP " .
                $cfg['Version'] . " Service Mailer";

    $subject = tr('{ADMIN} activated your software package');
    $message = tr('Dear {RESELLER},
	Your uploaded a software package was succesful activated by {ADMIN}.

	Details:
	Package Name: {SOFTWARE}
	Package ID: {SOFTWARE_ID}

	Please login into your i-MSCP control panel for more details.', true);

    $subject = str_replace($search, $replace, $subject);
    $message = str_replace($search, $replace, $message);
    $subject = encode($subject);
    mail($to_email, $subject, $message, $headers);
}

/**
 * Must be documented
 *
 * @param  $reseller_id
 * @param  $file_name
 * @param  $sw_id
 * @param  $subjectinput
 * @param  $messageinput
 * @return void
 */
function send_deleted_sw($reseller_id, $file_name, $sw_id, $subject_input, $message_input)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $query = "
        SELECT
            `admin_name` as reseller, `created_by`, `email` as res_email
        FROM
            `admin`
        WHERE `admin_id` = ?
        ;
    ";
    $res = exec_query($query, $reseller_id);

    $to_name = $res->fields['reseller'];
    $to_email = $res->fields['res_email'];
    $admin_id = $res->fields['created_by'];

    $query = "
        SELECT
            `email` as adm_email, `admin_name` as admin
        FROM
            `admin`
        WHERE
            `admin_id` = ?
        ;
    ";
    $res = exec_query($query, $admin_id);

    $from_name = $res->fields['admin'];
    $from_email = $res->fields['adm_email'];

    if ($from_name) {
        $from = "\"" . encode($from_name) . "\" <" . $from_email . ">";
    } else {
        $from = $from_email;
    }

    $search = array();
    $replace = array();
    $search [] = '{ADMIN}';
    $replace[] = $from_name;
    $search [] = '{SOFTWARE}';
    $replace[] = $file_name;
    $search [] = '{SOFTWARE_ID}';
    $replace[] = $sw_id;
    $search [] = '{RESELLER}';
    $replace[] = $to_name;

    $headers = "From: " . $from . "\n";
    $headers .= "MIME-Version: 1.0\n" . "Content-Type: text/plain; charset=utf-8\n" .
                "Content-Transfer-Encoding: 8bit\n" . "X-Mailer: i-MSCP " .
                $cfg['Version'] . " Service Mailer";

    // lets send mail to the reseller => new order
    $subject = tr($subject_input . ' was deleted by {ADMIN}!');
    $message = tr('Dear {RESELLER},
		Your uploaded software was deleted by {ADMIN}.

	Details:
	Package Name: {SOFTWARE}
	Package ID: {SOFTWARE_ID}

	Message from {ADMIN}:
	' . $message_input, true);

    $subject = str_replace($search, $replace, $subject);
    $message = str_replace($search, $replace, $message);
    $subject = encode($subject);
    mail($to_email, $subject, $message, $headers);
}
