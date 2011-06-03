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
 * Encode a string to be valid as mail header.
 *
 * @source php.net/manual/en/function.mail.php
 *
 * @param string $string String to be encoded [should be in the $charset charset]
 * @param string $charset OPTIONAL charset in that string will be encoded (defaulted to UTF-8)
 * @return string encoded string
 *
 * @todo need to check emails with ? and space in subject - some probs can occur
 */
function encode($string, $charset = 'UTF-8')
{
    $string = (string)$string;

    if ($string && $charset) {
        // define start delimiter, end delimiter and spacer
        $end = '?=';
        $start = '=?' . $charset . '?B?';
        $spacer = $end . "\r\n " . $start;

        // determine length of encoded text within chunks
        // and ensure length is even
        $length = 75 - strlen($start) - strlen($end);
        $length = floor($length / 4) * 4;

        // encode the string and split it into chunks
        // with spacers after each chunk
        $string = base64_encode($string);
        $string = chunk_split($string, $length, $spacer);

        // remove trailing spacer and
        // add start and end delimiters
        $spacer = preg_quote($spacer);
        $string = preg_replace('/' . $spacer . '$/', '', $string);
        $string = $start . $string . $end;
    }

    return $string;
}

/**
 * Returns count of SQL users.
 *
 * @param iMSCP_Database $db iMSCP_Database instance
 * @return int Number of SQL users
 */
function get_sql_user_count($db)
{
    $query = "SELECT DISTINCT `sqlu_name` FROM `sql_user`;";
    $rs = exec_query($db, $query);

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
    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $query = "SELECT * FROM `reseller_props` WHERE `reseller_id` = ?;";
    $rs = exec_query($db, $query, $reseller_id);

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
    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $rdmn_current = $rdmn_max = $rsub_current = $rsub_max = $rals_current =
    $rals_max = $rmail_current = $rmail_max = $rftp_current = $rftp_max =
    $rsql_db_current = $rsql_db_max = $rsql_user_current = $rsql_user_max =
    $rtraff_current = $rtraff_max = $rdisk_current = $rdisk_max = 0;

    $rdmn_uf = $rsub_uf = $rals_uf = $rmail_uf = $rftp_uf = $rsql_db_uf =
    $rsql_user_uf = $rtraff_uf = $rdisk_uf = '_off_';

    $query = "SELECT `admin_id` FROM `admin` WHERE `created_by` = ?;";
    $rs = exec_query($db, $query, $reseller_id);

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

        $dres = exec_query($db, $query, $admin_id);
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
 * Returns properties for a specific domain.
 *
 * @param  int $domainId Domain unique identifier
 * @return array An array that contains domain properties
 * @todo rename this function
 */
function generate_user_props($domainId)
{
     /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $query = "SELECT * FROM `domain` WHERE `domain_id` = ?;";
    $rs = exec_query($db, $query, $domainId);

    if ($rs->rowCount() == 0) {
        return array_fill(0, 14, 0);
    }

    // Retrieves number of subdomains belong to the domain
    $sub_current = records_count('subdomain', 'domain_id', $domainId);

    // Retrieves subdomains limit (max count of subdomain that the domain owner can have)
    $sub_max = $rs->fields['domain_subd_limit'];

    // Retrieves number of aliasses belong to the domain
    $als_current = records_count('domain_aliasses', 'domain_id', $domainId);

    // Retrieves aliasses limit (max count of aliasses that the domain owner can have)
    $als_max = $rs->fields['domain_alias_limit'];

    // This works with the admin option (Count default E-Mail addresses)
    if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES) {
        $mail_current = records_count('mail_users',
                                      "mail_type NOT RLIKE '_catchall' AND domain_id",
                                      $domainId);
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
			;
		";

        $mail_current = records_count('mail_users', $where, $domainId);
    }

    // Retrieves mail limit (max count of mail that the domain owner can have)
    $mail_max = $rs->fields['domain_mailacc_limit'];

    // Retrieves number of ftp account belong to the domain
    $ftp_current = sub_records_rlike_count(
        'domain_name', 'domain', 'domain_id', $domainId, 'userid', 'ftp_users',
        'userid', '@', ''
    );

    // Re
    $ftp_current += sub_records_rlike_count(
        'alias_name', 'domain_aliasses', 'domain_id', $domainId, 'userid',
        'ftp_users', 'userid', '@', ''
    );

    $ftp_max = $rs->fields['domain_ftpacc_limit'];
    $sql_db_current = records_count('sql_database', 'domain_id', $domainId);
    $sql_db_max = $rs->fields['domain_sqld_limit'];

    // Retrieves number of SQL user
    $sql_user_current = sub_records_count(
        'sqld_id', 'sql_database', 'domain_id', $domainId, 'sqlu_id', 'sql_user',
        'sqld_id', 'sqlu_name', ''
    );

    $sql_user_max = $rs->fields['domain_sqlu_limit'];
    $traff_max = $rs->fields['domain_traffic_limit'];
    $disk_max = $rs->fields['domain_disk_limit'];

    return array(
        $sub_current, $sub_max, $als_current, $als_max, $mail_current, $mail_max,
        $ftp_current, $ftp_max, $sql_db_current, $sql_db_max, $sql_user_current,
        $sql_user_max, $traff_max, $disk_max);
}

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
    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    if ($where != '') {
        if ($bind != '') {
            $query = "SELECT COUNT(*) AS `cnt` FROM `$table` WHERE $where = ?;";
            $rs = exec_query($db, $query, $bind);
        } else {
            $query = "SELECT COUNT(*) AS `cnt` FROM $table WHERE $where;";
            $rs = exec_query($db, $query);
        }
    } else {
        $query = "SELECT COUNT(*) AS `cnt` FROM `$table`;";
        $rs = exec_query($db, $query);
    }

    return (int)$rs->fields['cnt'];
}

/**
 * Must be documented
 *
 * Returns number of items in a database  table with optional search criterias
 *
 * @param  $field
 * @param  $table
 * @param  $where
 * @param  $value
 * @param  $subfield
 * @param  $subtable
 * @param  $subwhere
 * @param  $subgroupname
 * @return int
 */
function sub_records_count($field, $table, $where, $value, $subfield, $subtable, $subwhere, $subgroupname)
{
    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    if ($where != '') {
        $query = "SELECT $field AS `field` FROM $table WHERE $where = ?;";
        $rs = exec_query($db, $query, $value);
    } else {
        $query = "SELECT $field AS `field` FROM $table;";
        $rs = exec_query($db, $query);
    }

    $result = 0;

    if ($rs->rowCount() == 0) {
        return $result;
    }

    if ($subgroupname != '') {
        $sqld_ids = array();

        while (!$rs->EOF) {
            array_push($sqld_ids, $rs->fields['field']);
            $rs->moveNext();
        }

        $sqld_ids = implode(',', $sqld_ids);

        if ($subwhere != '') {
            $query = "
                SELECT
                    COUNT(DISTINCT $subgroupname) AS `cnt`
                FROM
                    $subtable
                WHERE
                    `sqld_id` IN ($sqld_ids)
                ;
            ";
            $subres = exec_query($db, $query);
            $result = $subres->fields['cnt'];
        } else {
            return $result;
        }
    } else {
        while (!$rs->EOF) {
            $contents = $rs->fields['field'];

            if ($subwhere != '') {
                $query = "
                    SELECT
                        COUNT(*) AS `cnt`
                    FROM
                        $subtable
                    WHERE
                        $subwhere = ?
                    ;
                ";
            } else {
                return $result;
            }

            $subres = exec_query($db, $query, $contents);
            $result += $subres->fields['cnt'];
            $rs->moveNext();
        }
    }

    return $result;
}

/**
 * Generate user traffic.
 *
 * @param  int $domainId Domain unique identifier
 * @return array An array that contains traffic usage information
 */
function generate_user_traffic($domainId)
{
    global $crnt_month, $crnt_year;

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $from_timestamp = mktime(0, 0, 0, $crnt_month, 1, $crnt_year);

    if ($crnt_month == 12) {
        $to_timestamp = mktime(0, 0, 0, 1, 1, $crnt_year + 1);
    } else {
        $to_timestamp = mktime(0, 0, 0, $crnt_month + 1, 1, $crnt_year);
    }

    $query = "
		SELECT
			`domain_id`, IFNULL(`domain_disk_usage`, 0) AS `domain_disk_usage`,
			IFNULL(`domain_traffic_limit`, 0) AS `domain_traffic_limit`,
			IFNULL(`domain_disk_limit`, 0) AS `domain_disk_limit`, `domain_name`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		ORDER BY
			`domain_name`
		;
	";

    $rs = exec_query($db, $query, $domainId);

    if ($rs->rowCount() == 0 || $rs->rowCount() > 1) {
        write_log(
            'TRAFFIC WARNING: ' . $rs->fields['domain_name'] .
            ' manages incorrect number of domains: ' . $rs->rowCount()
        );

        return array('n/a', 0, 0, 0, 0, 0, 0, 0, 0, 0);
    } else {
        $domain_id = $rs->fields['domain_id'];
        $domain_disk_usage = $rs->fields['domain_disk_usage'];
        $domain_traff_limit = $rs->fields['domain_traffic_limit'];
        $domain_disk_limit = $rs->fields['domain_disk_limit'];
        $domain_name = $rs->fields['domain_name'];

        $query = "
			SELECT
				IFNULL(SUM(`dtraff_web`), 0) AS web,
				IFNULL(SUM(`dtraff_ftp`), 0) AS ftp,
				IFNULL(SUM(`dtraff_mail`), 0) AS smtp,
				IFNULL(SUM(`dtraff_pop`), 0) AS pop,
				IFNULL(SUM(`dtraff_web`), 0) + IFNULL(SUM(`dtraff_ftp`), 0) +
				IFNULL(SUM(`dtraff_mail`), 0) + IFNULL(SUM(`dtraff_pop`), 0) AS total
			FROM
				`domain_traffic`
			WHERE
				`domain_id` = ?
			AND
				`dtraff_time` >= ?
			AND
				`dtraff_time` < ?
			;
		";
        $rs1 = exec_query($db, $query, array($domain_id, $from_timestamp, $to_timestamp));

        return array(
            $domain_name, $domain_id, $rs1->fields['web'], $rs1->fields['ftp'],
            $rs1->fields['smtp'], $rs1->fields['pop'], $rs1->fields['total'],
            $domain_disk_usage, $domain_traff_limit, $domain_disk_limit);
    }
}

/**
 * Must be documented
 *
 * @param  int $current
 * @param  int $max
 * @return int
 */
function make_usage_vals($current, $max)
{
    if ($max == 0) {
        // 1 TeraByte Limit ;) for Unlimited Value
        $max = 1024 * 1024 * 1024 * 1024;
    }

    $percent = 100 * $current / $max;
    $percent = sprintf("%.2f", $percent);
    $red = (int)$percent;

    return ($red > 100) ? array($percent, 100, 0) : array($percent, $red, 100 - $red);
}

/**
 * Must be documented
 *
 * @param  $field
 * @param  $table
 * @param  $where
 * @param  $value
 * @param  $subfield
 * @param  $subtable
 * @param  $subwhere
 * @param  $a
 * @param  $b
 * @return int
 */
function sub_records_rlike_count($field, $table, $where, $value, $subfield,
    $subtable, $subwhere, $a, $b)
{
    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    if ($where != '') {
        $query = "SELECT $field AS `field` FROM $table WHERE $where = ?;";

        $rs = exec_query($db, $query, $value);
    } else {
        $query = "SELECT $field AS `field` FROM $table;";

        $rs = exec_query($db, $query);
    }

    $result = 0;

    if ($rs->rowCount() == 0) {
        return $result;
    }

    while (!$rs->EOF) {
        $contents = $rs->fields['field'];

        if ($subwhere != '') {
            $query = "SELECT COUNT(*) AS `cnt` FROM $subtable WHERE $subwhere RLIKE ?;";
        } else {
            return $result;
        }

        $subres = exec_query($db, $query, $a . $contents . $b);
        $result += $subres->fields['cnt'];
        $rs->moveNext();
    }

    return $result;
}



/**
 * Returns user name matching identifier.
 *
 * @param  int $user_id User unique identifier
 * @return string Username
 */
function get_user_name($user_id)
{
    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');
    $query = "SELECT `admin_name` FROM `admin` WHERE `admin_id` = ?;";
    $rs = exec_query($db, $query, $user_id);

    return $rs->fields('admin_name');
}

/**
 * Returns user logo path
 *
 * @param  int $user_id User unique identifier
 * @return string
 */
function get_logo($user_id)
{
     /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    // check which logo we should return:
    $query = "
        SELECT
            `admin_id`, `created_by`, `admin_type`
        FROM
            `admin`
        WHERE
            `admin_id` = ?
        ;
    ";

    $rs = exec_query($db, $query, $user_id);

    if ($rs->fields['admin_type'] == 'admin') {
        return get_admin_logo($user_id);
    } else {
        if (get_admin_logo($rs->fields['created_by']) == $cfg->ISP_LOGO_PATH . '/isp_logo.gif') {
            return get_admin_logo($user_id);
        } else {
            return get_admin_logo($rs->fields['created_by']);
        }
    }
}

/**
 * Returns admin logo path.
 *
 * @param  int $user_id User unique identifier
 * @return string
 */
function get_own_logo($user_id)
{
    return get_admin_logo($user_id);
}

/**
 * Returns admin logo path.
 *
 * @param  int $user_id User unique identifier
 * @return string Admin logo path
 */
function get_admin_logo($user_id)
{
     /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $query = "SELECT `logo` FROM `user_gui_props` WHERE `user_id`= ?;";
    $rs = exec_query($db, $query, $user_id);

    $user_logo = $rs->fields['logo'];

    if (empty($user_logo)) { // default logo
        return $cfg->ISP_LOGO_PATH . '/isp_logo.gif';
    } else {
        return $cfg->ISP_LOGO_PATH . '/' . $user_logo;
    }
}

/**
 * Must be documented
 *
 * @param  $value
 * @param  $value_max
 * @param  $bar_width
 * @return int
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

/**
 * Writes a log message in the database and sends it to the administrator by email.
 *
 * @param  string $msg Message to log
 * @param int $level Log level
 * @return void
 */
function write_log($msg, $level = E_USER_WARNING)
{
    global $send_log_to;

     /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    if (isset($_SERVER['REMOTE_ADDR'])) {
        $client_ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $client_ip = "unknown";
    }

    $msg = replace_html($msg . '<br /><small>User IP: ' . $client_ip . '</small>',
                        ENT_COMPAT, tr('encoding'));

    $query = "INSERT INTO `log` (`log_time`,`log_message`) VALUES(NOW(), ?);";
    exec_query($db, $query, $msg, false);

    $msg = strip_tags(str_replace('<br />', "\n", $msg));
    $send_log_to = $cfg->DEFAULT_ADMIN_ADDRESS;

    // now send email if DEFAULT_ADMIN_ADDRESS != ''
    if ($send_log_to != '' && $level <= $cfg->LOG_LEVEL) {
        global $default_hostname, $default_base_server_ip, $Version, $BuildDate,

        $admin_login;
        $admin_email = $cfg->DEFAULT_ADMIN_ADDRESS;
        $default_hostname = $cfg->SERVER_HOSTNAME;
        $default_base_server_ip = $cfg->BASE_SERVER_IP;
        $Version = $cfg->Version;
        $BuildDate = $cfg->BuildDate;
        $subject = "i-MSCP $Version on $default_hostname ($default_base_server_ip)";
        $to = $send_log_to;
        $message = <<<AUTO_LOG_MSG

i-MSCP Log

Server: $default_hostname ($default_base_server_ip)
Version: i-MSCP $Version ($BuildDate)

Message: ----------------[BEGIN]--------------------------

$msg

Message: ----------------[END]----------------------------

AUTO_LOG_MSG;

        $headers = "From: \"i-MSCP Logging Daemon\" <" . $admin_email . ">\n";
        $headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 7bit\n";
        $headers .= "X-Mailer: i-MSCP $Version Logging Mailer";
        $mail_result = mail($to, $subject, $message, $headers);

        // Reduce admin log entries by only logging email notification if not
        // successful
        if (!$mail_result) {
            $mail_status = ($mail_result) ? 'OK' : 'NOT OK';
            $log_message = "$admin_login: Logging Daemon Mail To: |$to|, " .
                           "From: |$admin_email|, Status: |$mail_status|!";
            $query = "INSERT INTO `log` (`log_time`,`log_message`) VALUES(NOW(), ?);";

            // Change this to be compatible with PDO Exception only
            exec_query($db, $query, $log_message, false);
        }
    }
}

/**
 * Must be documented
 *
 * @param  $admin_id
 * @param  $uname
 * @param  $upass
 * @param  $uemail
 * @param  $ufname
 * @param  $ulname
 * @param  $utype
 * @param string $gender
 * @return void
 */
function send_add_user_auto_msg($admin_id, $uname, $upass, $uemail, $ufname,
    $ulname, $utype, $gender = '')
{
     /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $admin_login = $_SESSION['user_logged'];
    $data = get_welcome_email($admin_id, 'user');
    $from_name = $data['sender_name'];
    $from_email = $data['sender_email'];
    $message = $data['message'];
    $base_vhost = $cfg->BASE_SERVER_VHOST;

    if ($from_name) {
        $from = '"' . encode($from_name) . "\" <" . $from_email . ">";
    } else {
        $from = $from_email;
    }

    if ($ufname && $ulname) {
        $to = '"' . encode($ufname . ' ' . $ulname) . "\" <" . $uemail . ">";
        $name = "$ufname $ulname";
    } else {
        $name = $uname;
        $to = $uemail;
    }

    $username = $uname;
    $password = $upass;
    $subject = $data['subject'];
    $search = array();
    $replace = array();
    $search [] = '{USERNAME}';
    $replace[] = decode_idna($username);
    $search [] = '{USERTYPE}';
    $replace[] = $utype;
    $search [] = '{NAME}';
    $replace[] = decode_idna($name);
    $search [] = '{PASSWORD}';
    $replace[] = $password;
    $search [] = '{BASE_SERVER_VHOST}';
    $replace[] = $base_vhost;
    $search [] = '{BASE_SERVER_VHOST_PREFIX}';
    $replace[] = $cfg->BASE_SERVER_VHOST_PREFIX;
    $subject = str_replace($search, $replace, $subject);
    $message = str_replace($search, $replace, $message);
    $subject = encode($subject);
    $headers = "From: " . $from . "\n";
    $headers .= "MIME-Version: 1.0\nContent-Type: text/plain; " .
                "charset=utf-8\nContent-Transfer-Encoding: 8bit\n";
    $headers .= "X-Mailer: i-MSCP {$cfg->Version} Service Mailer";
    $mail_result = mail($to, $subject, $message, $headers);
    $mail_status = ($mail_result) ? 'OK' : 'NOT OK';

    $name = tohtml($name);
    $from_name = tohtml($from_name);

    write_log("$admin_login: Auto Add User To: |$name <$uemail>|, From: " .
              "|$from_name <$from_email>|, Status: |$mail_status|!");
}

/**
 * Update reseller properties.
 *
 * @param  int $reseller_id Reseller unique identifier.
 * @param  array $props Array that contain new properties values
 * @return iMSCP_Database_ResultSet|null
 */
function update_reseller_props($reseller_id, $props)
{
    $props = (array)$props;

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    if (empty($props)) {
        return null;
    }

    list($dmn_current, $dmn_max, $sub_current, $sub_max, $als_current, $als_max,
        $mail_current, $mail_max, $ftp_current, $ftp_max, $sql_db_current,
        $sql_db_max, $sql_user_current, $sql_user_max, $traff_current, $traff_max,
        $disk_current, $disk_max) = explode(';', $props);

    $query = "
		UPDATE
			`reseller_props`
		SET
			`current_dmn_cnt` = ?, `max_dmn_cnt` = ?, `current_sub_cnt` = ?,
			`max_sub_cnt` = ?, `current_als_cnt` = ?, `max_als_cnt` = ?,
			`current_mail_cnt` = ?, `max_mail_cnt` = ?, `current_ftp_cnt` = ?,
			`max_ftp_cnt` = ?, `current_sql_db_cnt` = ?, `max_sql_db_cnt` = ?,
			`current_sql_user_cnt` = ?, `max_sql_user_cnt` = ?,
			`current_traff_amnt` = ?, `max_traff_amnt` = ?, `current_disk_amnt` = ?,
			`max_disk_amnt` = ?
		WHERE
			`reseller_id` = ?
		;
	";

    $res = exec_query($db, $query, array(
                                         $dmn_current, $dmn_max, $sub_current,
                                         $sub_max, $als_current, $als_max,
                                         $mail_current, $mail_max, $ftp_current,
                                         $ftp_max, $sql_db_current, $sql_db_max,
                                         $sql_user_current, $sql_user_max,
                                         $traff_current, $traff_max, $disk_current,
                                         $disk_max, $reseller_id));

    return $res;
}



/**
 * Change domain status (eg. Schedule an action to be performed by engine).
 *
 * @param  iMSCP_Database $db iMSCP_Database instance
 * @param  int $domain_id Domain unique identifier
 * @param  string $domain_name Domain name
 * @param  string $action Action to schedule
 * @param  string $location Location to go back after action scheduling
 * @return void
 */
function change_domain_status($db, $domain_id, $domain_name, $action, $location)
{
     /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if ($action == 'disable') {
        $new_status = $cfg->ITEM_TODISABLED_STATUS;
    } else if ($action == 'enable') {
        $new_status = $cfg->ITEM_TOENABLE_STATUS;
    } else {
        return;
    }

    $query = "
        SELECT
            `mail_id`, `mail_pass`, `mail_type`
        FROM
            `mail_users`
        WHERE
            `domain_id` = ?
        ;
    ";
    $rs = exec_query($db, $query, $domain_id);

    while (!$rs->EOF) {
        $mail_id = $rs->fields['mail_id'];
        $mail_pass = $rs->fields['mail_pass'];
        $mail_type = $rs->fields['mail_type'];

        if ($cfg->HARD_MAIL_SUSPENSION) {
            $mail_status = $new_status;
        } else {
            if ($action == 'disable') {
                $timestamp = time();
                $pass_prefix = substr(md5($timestamp), 0, 4);

                if (preg_match('/^' . MT_NORMAL_MAIL . '/', $mail_type)
                    || preg_match('/^' . MT_ALIAS_MAIL . '/', $mail_type)
                    || preg_match('/^' . MT_SUBDOM_MAIL . '/', $mail_type)
                    || preg_match('/^' . MT_ALSSUB_MAIL . '/', $mail_type)
                ) {

                    $mail_pass = decrypt_db_password($mail_pass);
                    $mail_pass = $pass_prefix . $mail_pass;
                    $mail_pass = encrypt_db_password($mail_pass);
                }
            } else if ($action == 'enable') {
                if (preg_match('/^' . MT_NORMAL_MAIL . '/', $mail_type)
                    || preg_match('/^' . MT_ALIAS_MAIL . '/', $mail_type)
                    || preg_match('/^' . MT_SUBDOM_MAIL . '/', $mail_type)
                    || preg_match('/^' . MT_ALSSUB_MAIL . '/', $mail_type)
                ) {

                    $mail_pass = decrypt_db_password($mail_pass);
                    $mail_pass = substr($mail_pass, 4, 50);
                    $mail_pass = encrypt_db_password($mail_pass);
                }
            } else {
                return;
            }

            $mail_status = $cfg->ITEM_CHANGE_STATUS;
        }

        $query = "
            UPDATE
                `mail_users`
            SET
                `mail_pass` = ?, `status` = ?
            WHERE
                `mail_id` = ?
            ;
        ";
        exec_query($db, $query, array($mail_pass, $mail_status, $mail_id));
        $rs->moveNext();
    }

    $query = "UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?;";

    exec_query($db, $query, array($new_status, $domain_id));
    send_request();

    // let's get back to user overview after the system changes are finished
    $user_logged = $_SESSION['user_logged'];
    update_reseller_c_props(get_reseller_id($domain_id));

    if ($action == 'disable') {
        write_log("$user_logged: suspended domain: $domain_name");
        $_SESSION['user_disabled'] = 1;
    } else if ($action == 'enable') {
        write_log("$user_logged: enabled domain: $domain_name");
        $_SESSION['user_enabled'] = 1;
    } else {
        return;
    }

    if ($location == 'admin') {
        header('Location: manage_users.php');
    } else if ($location == 'reseller') {
        header('Location: users.php?psi=last');
    }

    exit();
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
 * Delete domain with all sub items (usage in admin and reseller).
 *
 * @param integer $domain_id Domain unique identifier
 * @param string $goto users.php or manage_users.php
 * @param boolean $breseller double check by reseller=current user
 */
function delete_domain($domain_id, $goto, $breseller = false)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    // Get uid and gid of domain user
    $query = "
		SELECT
			`domain_uid`, `domain_gid`, `domain_admin_id`, `domain_name`,
			`domain_created_id`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		;
	";

    if ($breseller) {
        $reseller_id = $_SESSION['user_id'];
        $query .= " AND `domain_created_id` = ?";
        $res = exec_query($db, $query, array($domain_id, $reseller_id));
    } else {
        $res = exec_query($db, $query, $domain_id);
    }

    $data = $res->fetchRow();

    if (empty($data['domain_uid']) || empty($data['domain_admin_id'])) {
        set_page_message(tr('Wrong domain ID!'));
        user_goto($goto);
    }

    $domain_admin_id = $data['domain_admin_id'];
    $domain_name = $data['domain_name'];
    $domain_uid = $data['domain_uid'];
    $domain_gid = $data['domain_gid'];

    if (!$breseller) {
        $reseller_id = $data['domain_created_id'];
    }

    // Mail users:
    $query = "UPDATE `mail_users` SET `status` = ? WHERE `domain_id` = ?;";
    exec_query($db, $query, array($cfg->ITEM_DELETE_STATUS, $domain_id));

    // Delete all protected areas related data (areas, groups and users)
    $query = "
		DELETE
			`areas`, `users`, `groups`
		FROM
			`domain` AS `dmn`
		LEFT JOIN
			`htaccess` AS `areas` ON `areas`.`dmn_id` = `dmn`.`domain_id`
		LEFT JOIN
			`htaccess_users` AS `users` ON `users`.`dmn_id` = `dmn`.`domain_id`
		LEFT JOIN
			`htaccess_groups` AS `groups` ON `groups`.`dmn_id` = `dmn`.`domain_id`
		WHERE
			`dmn`.`domain_id` = ?
		;
	";
    exec_query($db, $query, $domain_id);

    // Delete subdomain aliases:
    $alias_a = array();

    $query = "SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?;";
    $res = exec_query($db, $query, $domain_id);

    while (!$res->EOF) {
        $alias_a[] = $res->fields['alias_id'];
        $res->moveNext();
    }

    if (count($alias_a) > 0) {
        $query = "
            UPDATE
                `subdomain_alias`
            SET
                `subdomain_alias_status` = ?
            WHERE
                `alias_id` IN (";
        $query .= implode(',', $alias_a);
        $query .= ")";
        exec_query($db, $query, $cfg->ITEM_DELETE_STATUS);
    }

    // Delete SQL databases and users
    $query = "SELECT `sqld_id` FROM `sql_database` WHERE `domain_id` = ?;";
    $res = exec_query($db, $query, $domain_id);

    while (!$res->EOF) {
        delete_sql_database($db, $domain_id, $res->fields['sqld_id']);
        $res->moveNext();
    }

    // Domain aliases:
    $query = "
        UPDATE
            `domain_aliasses`
        SET
            `alias_status` =  ?
        WHERE
            `domain_id` = ?
        ;
    ";
    exec_query($db, $query, array($cfg->ITEM_DELETE_STATUS, $domain_id));

    // Remove domain traffic
    $query = "DELETE FROM `domain_traffic` WHERE`domain_id` = ?;";
    exec_query($db, $query, $domain_id);

    // Delete domain DNS entries
    $query = "DELETE FROM `domain_dns` WHERE `domain_id` = ?;";
    exec_query($db, $query, $domain_id);

    // Set domain deletion status
    $query = "UPDATE `domain` SET `domain_status` = 'delete' WHERE `domain_id` = ?;";
    exec_query($db, $query, $domain_id);

    // Set domain subdomains deletion status
    $query = "UPDATE `subdomain` SET `subdomain_status` = ? WHERE `domain_id` = ?;";
    exec_query($db, $query, array($cfg->ITEM_DELETE_STATUS, $domain_id));

    // --- Send request to the daemon
    send_request();

    // Delete FTP users:
    $query = "DELETE FROM `ftp_users` WHERE `uid` = ?;";
    exec_query($db, $query, $domain_uid);

    // Delete FTP groups:
    $query = "DELETE FROM `ftp_group` WHERE `gid` = ?;";
    exec_query($db, $query, $domain_gid);

    // Delete i-MSCP login:
    $query = "DELETE FROM `admin` WHERE `admin_id` = ?;";
    exec_query($db, $query, $domain_admin_id);

    // Delete the quota section:
    $query = "DELETE FROM `quotalimits` WHERE `name` = ?;";
    exec_query($db, $query, $domain_name);

    // Delete the quota section:
    $query = "DELETE FROM `quotatallies` WHERE `name` = ?;";
    exec_query($db, $query, $domain_name);

    // Remove support tickets:
    $query = "DELETE FROM `tickets` WHERE ticket_from = ? OR ticket_to = ?;";
    exec_query($db, $query, array($domain_admin_id, $domain_admin_id));

    // Delete user gui properties
    $query = "DELETE FROM `user_gui_props` WHERE `user_id` = ?;";

    exec_query($db, $query, $domain_admin_id);
    write_log($_SESSION['user_logged'] . ': deletes domain ' . $domain_name);

    if(isset($reseller_id)) {
        update_reseller_c_props($reseller_id);
    }

    $_SESSION['ddel'] = '_yes_';
    user_goto($goto);
}



/**
 * Returns token.
 *
 * @return string
 * @todo must be generic.
 */
function generate_software_upload_token()
{
    $token = md5(uniqid(microtime(), true));
    $_SESSION['software_upload_token'] = $token;

    return $token;
}

/**
 * Must be documented
 *
 * @param  $software_id
 * @param  $software_name
 * @param  $software_version
 * @param  $software_language
 * @param int $reseller_id Reseller unique identifier
 * @param int $software_master_id
 * @param bool $sw_depot
 * @return void
 */
function update_existing_client_installations_res_upload($software_id, $software_name, $software_version,
    $software_language, $reseller_id, $software_master_id = 0, $sw_depot = false)
{
    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

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
    $res = exec_query($db, $query, $reseller_id);

    if ($res->RecordCount() > 0) {
        while (!$res->EOF) {
            if ($sw_depot) {
                $updatequery = "
					UPDATE
						`web_software_inst`
					SET
						`software_id` = ?, `software_master_id` = ?,
						`software_res_del` = 0
					WHERE
						`software_name` = ?
					AND
						`software_version` = ?
					AND
						`software_language` = ?
					AND
						`software_res_del` = 1
					AND
						`domain_id` = ?
					;
				";
                exec_query($db, $updatequery, array(
                                                    $software_id, $software_master_id,
                                                    $software_name, $software_version,
                                                    $software_language,
                                                    $res->fields['domain_id']));
            } else {
                $updatequery = "
					UPDATE
						`web_software_inst`
					SET
						`software_id` = ?, `software_res_del` = 0
					WHERE
						`software_name` = ?
					AND
						`software_version` = ?
					AND
						`software_language` = ?
					AND
						`software_res_del` = 1
					AND
						`domain_id` = ?
					;
				";
                exec_query($db, $updatequery, array(
                                                    $software_id, $software_name,
                                                    $software_version,
                                                    $software_language,
                                                    $res->fields['domain_id']));
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
function update_existing_client_installations_sw_depot($software_id, $software_master_id, $reseller_id)
{
    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

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
    $res = exec_query($db, $query, $reseller_id);

    if ($res->RecordCount() > 0) {
        while (!$res->EOF) {
            $updatequery = "
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

            exec_query($db, $updatequery, array(
                                                $software_id, $software_master_id,
                                                $res->fields['domain_id']));
            $res->MoveNext();
        }
    }
}

/**
 * Tells whether or not the software installer is available for a reseller.
 *
 * @param  int $reseller_id Reseller unique identifier
 * @return string 'yes' if software installer is available, 'no' otherwise
 */
function get_reseller_sw_installer($reseller_id)
{
    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $query = "
        SELECT
            `software_allowed`
        FROM
            `reseller_props`
        WHERE
            `reseller_id` = ?
        ;
    ";
    $stmt = exec_query($db, $query, $reseller_id);

    return $stmt->fields['software_allowed'];
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

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $query = "
        SELECT
            `admin_name` as `reseller`, `created_by`, `email` as `res_email`
        FROM
            `admin`
        WHERE
            `admin_id` = ?;
        ;
    ";
    $res = exec_query($db, $query, $reseller_id);

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
    $res = exec_query($db, $query, $admin_id);

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
function send_deleted_sw($reseller_id, $file_name, $sw_id, $subjectinput, $messageinput)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $query = "
        SELECT
            `admin_name` as reseller, `created_by`, `email` as res_email
        FROM
            `admin`
        WHERE `admin_id` = ?
        ;
    ";
    $res = exec_query($db, $query, $reseller_id);

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
    $res = exec_query($db, $query, $admin_id);

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
    $subject = tr($subjectinput . ' was deleted by {ADMIN}!');
    $message = tr('Dear {RESELLER},
		Your uploaded software was deleted by {ADMIN}.

	Details:
	Package Name: {SOFTWARE}
	Package ID: {SOFTWARE_ID}

	Message from {ADMIN}:
	' . $messageinput, true);

    $subject = str_replace($search, $replace, $subject);
    $message = str_replace($search, $replace, $message);
    $subject = encode($subject);
    mail($to_email, $subject, $message, $headers);
}
