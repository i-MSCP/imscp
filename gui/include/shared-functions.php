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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */


/************************************************************************************
 * This file contains funtion that are used at many level (eg. admin, reseller, client)
 */

/************************************************************************************
 * Account functions
 */

/**
 * Returns user name matching identifier.
 *
 * @param  int $user_id User unique identifier
 * @return string Username
 */
function get_user_name($user_id)
{
    $query = "SELECT `admin_name` FROM `admin` WHERE `admin_id` = ?;";
    $rs = exec_query($query, $user_id);

    return $rs->fields('admin_name');
}

/************************************************************************************
 * Domain related functions
 */

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

    $query = "SELECT * FROM `domain` WHERE `domain_id` = ?;";
    $rs = exec_query($query, $domainId);

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
 * Updates client's domain properties.
 *
 * @param  int $domainId Domain unique identifier
 * @param  string $props String that contain new properties values
 * @return void
 */
function update_user_props($domainId, $props)
{
    /** @var $cfg iMSCP_Config_Handler_File $cfg **/
    $cfg = iMSCP_Registry::get('config');

    list(, $subMaxValue, , $alsMaxValue, , $mailMaxValue, , $ftpMaxValue, ,
        $sqlDbMaxValue, , $sqlUserMaxValue, $trafficMaxValue, $diskMaxValue,
        $phpSupport, $cgiSupport, , $customDnsSupport, $softwareInstallerSupport
    ) = explode(';', $props);


    $domainLastModified = time();

    // We must check the previous values of some properties (eg. php, cgi, dns,
    // software installer) to determine if we must send a request to the ispCP daemon

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
		;
	";
    $stmt = exec_query($query, array($domainId, $phpSupport, $cgiSupport,
                                    $customDnsSupport, $softwareInstallerSupport));

    // No record found. That mean that a least one propertie value was changed
    if ($stmt->recordCount() == 0) {

        $updateStatus = $cfg->ITEM_CHANGE_STATUS;

        // Updating client's domain properties
        $query = "
			UPDATE
				`domain`
			SET
				`domain_last_modified` = ?, `domain_mailacc_limit` = ?,
				`domain_ftpacc_limit` = ?, `domain_traffic_limit` = ?,
				`domain_sqld_limit` = ?, `domain_sqlu_limit` = ?,
				`domain_status` = ?, `domain_alias_limit` = ?,
				`domain_subd_limit` = ?, `domain_disk_limit` = ?,
				`domain_php` = ?, `domain_cgi` = ?,
				`domain_dns` = ?, `domain_software_allowed` = ?
			WHERE
				`domain_id` = ?
			;
		";
        exec_query($query, array($domainLastModified, $mailMaxValue, $ftpMaxValue,
                                $trafficMaxValue, $sqlDbMaxValue, $sqlUserMaxValue,
                                $updateStatus, $alsMaxValue, $subMaxValue,
                                $diskMaxValue, $phpSupport, $cgiSupport,
                                $customDnsSupport, $softwareInstallerSupport,
                                $domainId));

        // Let's update all alias domains for this domain
        $query = "
			UPDATE
				`domain_aliasses`
			SET
				`alias_status` = ?
			WHERE
				`domain_id` = ?
			;
		";
        exec_query($query, array($updateStatus, $domainId));

        // Let's update all subdomains for this domain
        $query = "
			UPDATE
				`subdomain`
			SET
				`subdomain_status` = ?
			WHERE
				`domain_id` = ?
			;
		";
        exec_query($query, array($updateStatus, $domainId));

        // Let's update all alias subdomains for this domain
        $query = "
			UPDATE
				`subdomain_alias`
			SET
				`subdomain_alias_status` = ?
			WHERE
				`alias_id` IN (
					SELECT
						`alias_id`
					FROM
						`domain_aliasses`
					WHERE
						`domain_id` = ?
				)
			;
		";
        exec_query($query, array($updateStatus, $domainId));

        // Send a request to the i-MSCP daemon
        send_request();
    } else {
        // We do not have changes for the PHP and/or CGI and/or CustomDNS and/or
        // SoftwareInstaller properties. We have to update only the client's domain
        // properties.
        $query = "
			UPDATE
				`domain`
			SET
			    `domain_last_modified` = ?, `domain_subd_limit` = ?,
			    `domain_alias_limit` = ?, `domain_mailacc_limit` = ?,
			    `domain_ftpacc_limit` = ?, `domain_sqld_limit` = ?,
			    `domain_sqlu_limit` = ?, `domain_traffic_limit` = ?,
			    `domain_disk_limit` = ?
			WHERE
				domain_id = ?
			;
		";
        exec_query($query, array($domainLastModified, $subMaxValue, $alsMaxValue,
                                $mailMaxValue, $ftpMaxValue, $sqlDbMaxValue,
                                $sqlUserMaxValue, $trafficMaxValue, $diskMaxValue,
                                $domainId));
    }
}

/**
 * Updates dommain expiration date.
 *
 * @param  int $user_id User unique identifier
 * @param  int $domain_new_expire New expiration date
 * @return void
 */
function update_expire_date($user_id, $domain_new_expire)
{
    $query = "
		UPDATE
			`domain`
		SET
			`domain_expires` = ?
		WHERE
			`domain_id` = ?
		;
	";
    exec_query($query, array($domain_new_expire, $user_id));
}

/**
 * Change domain status (eg. Schedule an action to be performed by engine).
 *
 * @param  int $domain_id Domain unique identifier
 * @param  string $domain_name Domain name
 * @param  string $action Action to schedule
 * @param  string $location Location to go back after action scheduling
 * @return void
 */
function change_domain_status($domain_id, $domain_name, $action, $location)
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
    $rs = exec_query($query, $domain_id);

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
                    $mail_pass = $pass_prefix . $mail_pass;
                }
            } else if ($action == 'enable') {
                if (preg_match('/^' . MT_NORMAL_MAIL . '/', $mail_type)
                    || preg_match('/^' . MT_ALIAS_MAIL . '/', $mail_type)
                    || preg_match('/^' . MT_SUBDOM_MAIL . '/', $mail_type)
                    || preg_match('/^' . MT_ALSSUB_MAIL . '/', $mail_type)
                ) {
                    $mail_pass = substr($mail_pass, 4, 50);
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
        exec_query($query, array($mail_pass, $mail_status, $mail_id));
        $rs->moveNext();
    }

    $query = "UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?;";

    exec_query($query, array($new_status, $domain_id));
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
        $res = exec_query($query, array($domain_id, $reseller_id));
    } else {
        $res = exec_query($query, $domain_id);
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
    exec_query($query, array($cfg->ITEM_DELETE_STATUS, $domain_id));

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
    exec_query($query, $domain_id);

    // Delete subdomain aliases:
    $alias_a = array();

    $query = "SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?;";
    $res = exec_query($query, $domain_id);

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
        exec_query($query, $cfg->ITEM_DELETE_STATUS);
    }

    // Delete SQL databases and users
    $query = "SELECT `sqld_id` FROM `sql_database` WHERE `domain_id` = ?;";
    $res = exec_query($query, $domain_id);

    while (!$res->EOF) {
        delete_sql_database($domain_id, $res->fields['sqld_id']);
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
    exec_query($query, array($cfg->ITEM_DELETE_STATUS, $domain_id));

    // Remove domain traffic
    $query = "DELETE FROM `domain_traffic` WHERE`domain_id` = ?;";
    exec_query($query, $domain_id);

    // Delete domain DNS entries
    $query = "DELETE FROM `domain_dns` WHERE `domain_id` = ?;";
    exec_query($query, $domain_id);

    // Set domain deletion status
    $query = "UPDATE `domain` SET `domain_status` = 'delete' WHERE `domain_id` = ?;";
    exec_query($query, $domain_id);

    // Set domain subdomains deletion status
    $query = "UPDATE `subdomain` SET `subdomain_status` = ? WHERE `domain_id` = ?;";
    exec_query($query, array($cfg->ITEM_DELETE_STATUS, $domain_id));

    // --- Send request to the daemon
    send_request();

    // Delete FTP users:
    $query = "DELETE FROM `ftp_users` WHERE `uid` = ?;";
    exec_query($query, $domain_uid);

    // Delete FTP groups:
    $query = "DELETE FROM `ftp_group` WHERE `gid` = ?;";
    exec_query($query, $domain_gid);

    // Delete i-MSCP login:
    $query = "DELETE FROM `admin` WHERE `admin_id` = ?;";
    exec_query($query, $domain_admin_id);

    // Delete the quota section:
    $query = "DELETE FROM `quotalimits` WHERE `name` = ?;";
    exec_query($query, $domain_name);

    // Delete the quota section:
    $query = "DELETE FROM `quotatallies` WHERE `name` = ?;";
    exec_query($query, $domain_name);

    // Remove support tickets:
    $query = "DELETE FROM `tickets` WHERE ticket_from = ? OR ticket_to = ?;";
    exec_query($query, array($domain_admin_id, $domain_admin_id));

    // Delete user gui properties
    $query = "DELETE FROM `user_gui_props` WHERE `user_id` = ?;";

    exec_query($query, $domain_admin_id);
    write_log($_SESSION['user_logged'] . ': deletes domain ' . $domain_name);

    if(isset($reseller_id)) {
        update_reseller_c_props($reseller_id);
    }

    $_SESSION['ddel'] = '_yes_';
    user_goto($goto);
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
    if ($where != '') {
        $query = "SELECT $field AS `field` FROM $table WHERE $where = ?;";
        $rs = exec_query($query, $value);
    } else {
        $query = "SELECT $field AS `field` FROM $table;";
        $rs = exec_query($query);
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
            $subres = exec_query($query);
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

            $subres = exec_query($query, $contents);
            $result += $subres->fields['cnt'];
            $rs->moveNext();
        }
    }

    return $result;
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

    if ($where != '') {
        $query = "SELECT $field AS `field` FROM $table WHERE $where = ?;";

        $rs = exec_query($query, $value);
    } else {
        $query = "SELECT $field AS `field` FROM $table;";

        $rs = exec_query($query);
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

        $subres = exec_query($query, $a . $contents . $b);
        $result += $subres->fields['cnt'];
        $rs->moveNext();
    }

    return $result;
}

/************************************************************************************
 * Reseller related functions
 */

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

    $res = exec_query($query, array(
                                         $dmn_current, $dmn_max, $sub_current,
                                         $sub_max, $als_current, $als_max,
                                         $mail_current, $mail_max, $ftp_current,
                                         $ftp_max, $sql_db_current, $sql_db_max,
                                         $sql_user_current, $sql_user_max,
                                         $traff_current, $traff_max, $disk_current,
                                         $disk_max, $reseller_id));

    return $res;
}

/************************************************************************************
 * Mail functions
 */

/**
 * Encode a string to be valid as mail header.
 *
 * @source php.net/manual/en/function.mail.php
 *
 * @param string $string String to be encoded [should be in the $charset charset]
 * @param string $charset OPTIONAL charset in that string will be encoded
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

/************************************************************************************
 * Utils functions
 */

/**
 * goto the given destination file
 *
 * @param string $dest destination for header location (path + filename + params)
 */
function user_goto($dest) {
	header('Location: ' . $dest);
	exit;
}

/**
 * Redirect to other page.
 *
 * @param  string $destination URL to redirect to
 * @return void
 */
function redirectTo($destination) {
    user_goto($destination);
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
 * Must be documented.
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
 * Convert domain name to IDNA ASCII form.
 *
 * @param  $domain Domain to convert.
 * @return string Domain name encoded in ASCII-compatible form
 */
function encode_idna($domain)
{
    if (function_exists('idn_to_ascii')) {
        return idn_to_ascii($domain);
    }

    $idn = new idna_convert();
    return $idn->encode($domain);
}

/**
 * Convert domain name from IDNA ASCII to Unicode.
 *
 * @param  string $domain Domain to convert in IDNA ASCII-compatible format.
 * @return string Domain name in Unicode.
 */
function decode_idna($domain)
{
    if (function_exists('idn_to_unicode')) {
        return idn_to_utf8($domain, IDNA_USE_STD3_RULES);
    }

    $idn = new idna_convert();
    $result = $idn->decode($domain);

    return ($result == false) ? $domain : $result;
}

/************************************************************************************
 * Checks functions
 */

/**
 * Checks if all of the characters in the provided string are numerical.
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
 * Checks for XMLHttpRequest.
 *
 * Returns true if the request‘s "X-Requested-With" header contains "XMLHttpRequest".
 *
 * Note: jQuery and Prototype Javascript libraries sends this header with every Ajax
 * request.
 *
 * @author Laurent Declercq (nuxwin) <l.declercq@nuxwin.com>
 * @Since r2587
 * @return boolean  TRUE if the request‘s "X-Requested-With" header contains
 *                  "XMLHttpRequest", FALSE otherwise
 */
function is_xhr()
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        stristr($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') !== false
    ) {
        return true;
    }

    return false;
}

/**
 * Check if a data is serialized
 *
 * @since ispCP 1.0.7
 * @author Laurent Declercq (nuxwin) <l.declercq@nuxwin.com>
 * @param mixed $data Data to be checked
 * @return boolean TRUE if serialized data, FALSE otherwise
 */
function is_serialized($data)
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
 * Must be documented
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function check_permissions($tpl)
{
    if (isset($_SESSION['sql_support']) && $_SESSION['sql_support'] == 'no') {
        $tpl->assign('SQL_SUPPORT', '');
    }
    if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == 'no') {
        $tpl->assign('ADD_EMAIL', '');
    }
    if (isset($_SESSION['subdomain_support'])
        && $_SESSION['subdomain_support'] == 'no'
    ) {
        $tpl->assign('SUBDOMAIN_SUPPORT', '');
    }
    if (isset($_SESSION['alias_support']) && $_SESSION['alias_support'] == 'no') {
        $tpl->assign('DOMAINALIAS_SUPPORT', '');
    }
    if (isset($_SESSION['subdomain_support'])
        && $_SESSION['subdomain_support'] == 'no') {
        $tpl->assign('SUBDOMAIN_SUPPORT_CONTENT', '');
    }
    if (isset($_SESSION['alias_support']) && $_SESSION['alias_support'] == 'no') {
        $tpl->assign('DOMAINALIAS_SUPPORT_CONTENT', '');
    }
    if (isset($_SESSION['alias_support']) && $_SESSION['alias_support'] == 'no'
        && isset($_SESSION['subdomain_support'])
        && $_SESSION['subdomain_support'] == 'no'
    ) {
        $tpl->assign('DMN_MNGMNT', '');
    }
    if (isset($_SESSION['software_support'])
        && $_SESSION['software_support'] == 'no'
    ) {
        $tpl->assign('NO_SOFTWARE', '');
    }
}

/************************************************************************************
 * Accounting related functions
 */

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
 * Generate user traffic.
 *
 * @param  int $domainId Domain unique identifier
 * @return array An array that contains traffic usage information
 */
function generate_user_traffic($domainId)
{
    global $crnt_month, $crnt_year;

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

    $rs = exec_query($query, $domainId);

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
        $rs1 = exec_query($query, array($domain_id, $from_timestamp, $to_timestamp));

        return array(
            $domain_name, $domain_id, $rs1->fields['web'], $rs1->fields['ftp'],
            $rs1->fields['smtp'], $rs1->fields['pop'], $rs1->fields['total'],
            $domain_disk_usage, $domain_traff_limit, $domain_disk_limit);
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

/************************************************************************************
 * Layout related functions
 */

/**
 * Returns user logo path
 *
 * @param  int $user_id User unique identifier
 * @return string
 */
function get_logo($user_id)
{

    # See #90 Defect - Custom logo feature is broken (since a lot)
    return 0;

     /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

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

    $rs = exec_query($query, $user_id);

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

    $query = "SELECT `logo` FROM `user_gui_props` WHERE `user_id`= ?;";
    $rs = exec_query($query, $user_id);

    $user_logo = $rs->fields['logo'];

    if (empty($user_logo)) { // default logo
        return $cfg->ISP_LOGO_PATH . '/isp_logo.gif';
    } else {
        return $cfg->ISP_LOGO_PATH . '/' . $user_logo;
    }
}

/************************************************************************************
 * Logging related functions
 */

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

    if (isset($_SERVER['REMOTE_ADDR'])) {
        $client_ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $client_ip = "unknown";
    }

    $msg = replace_html($msg . '<br /><small>User IP: ' . $client_ip . '</small>',
                        ENT_COMPAT, tr('encoding'));

    $query = "INSERT INTO `log` (`log_time`,`log_message`) VALUES(NOW(), ?);";
    exec_query($query, $msg, false);

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
            exec_query($query, $log_message, false);
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

/************************************************************************************
 * Software installer related functions
 */

/**
 * Returns client software permissions.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  $user_id User unique identifier
 * @return void
 */
function get_client_software_permission($tpl, $user_id)
{
    $query = "
		SELECT
			`domain_software_allowed`,
			`domain_ftpacc_limit`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
		;
	";
    $rs = exec_query($query, array($user_id));

    if ($rs->fields('domain_software_allowed') == 'yes' && $rs->fields('domain_ftpacc_limit') != "-1") {
        $tpl->assign(array(
                          'SOFTWARE_SUPPORT' => tr('yes'),
                          'TR_SOFTWARE_MENU' => tr('i-MSCP application installer'),
                          'SOFTWARE_MENU' => tr('yes'),
                          'TR_INSTALLATION' => tr('Installation details'),
                          'TR_INSTALLATION_INFORMATION' => tr('Please set now the Username and Password for the later Login in the Application. (Required fiels!)'),
                          'TR_INSTALL_USER' => tr('Login username'),
                          'TR_INSTALL_PWD' => tr('Login password'),
                          'TR_INSTALL_EMAIL' => tr('Emailadress'),
                          'SW_MSG' => tr('enabled'),
                          'SW_ALLOWED' => tr('i-MSCP application installer'),
                          'TR_SOFTWARE_DESCRIPTION' => tr('Application Description')));

        $tpl->parse('T_SOFTWARE_SUPPORT', '.t_software_support');
        $tpl->parse('T_SOFTWARE_MENU', '.t_software_menu');
    } else {
        $tpl->assign(array(
                          'T_SOFTWARE_SUPPORT' => '',
                          'T_SOFTWARE_MENU' => '',
                          'SOFTWARE_ITEM' => '',
                          'TR_INSTALLATION' => tr('You do not have permissions to install application yet'),
                          'TR_SOFTWARE_DESCRIPTION' => tr('You do not have permissions to install application yet'),
                          'SW_MSG' => tr('disabled'),
                          'SW_ALLOWED' => tr('i-MSCP application installer')));
    }
}

/**
 * Returns reseller software permissions.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  $reseller_id Reseller unique identifier
 * @return void
 */
function get_reseller_software_permission($tpl, $reseller_id)
{
    $query = "
		SELECT
			`software_allowed`
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
		;
	";
    $rs = exec_query($query, array($reseller_id));

    if ($rs->fields('software_allowed') == 'yes') {
        $tpl->assign(array(
                          'SOFTWARE_SUPPORT' => tr('yes'),
                          'SW_ALLOWED' => tr('i-MSCP application installer'),
                          'SW_MSG' => tr('enabled')));
        $tpl->parse('T_SOFTWARE_SUPPORT', '.t_software_support');
    } else {
        $tpl->assign(array(
                          'SOFTWARE_SUPPORT' => tr('no'),
                          'SW_ALLOWED' => tr('i-MSCP application installer'),
                          'SW_MSG' => tr('disabled'),
                          'T_SOFTWARE_SUPPORT' => ''));
    }
}

/**
 * Get all config data from i-MSCP application installer
 *
 * @since 1.0.0
 * @author Sascha Bay (TheCry) <sascha.bay@i-mscp.net>
 * @return array
 */
function get_application_installer_conf()
{
    $query = "SELECT * FROM `web_software_options`;";
    $rs = exec_query($query);

    return array(
        $rs->fields['use_webdepot'], $rs->fields['webdepot_xml_url'],
        $rs->fields['webdepot_last_update']);
}

/**
 * Check wheter the package is still installed this system
 *
 * @since 1.0.0
 * @author Sascha Bay (TheCry) <sascha.bay@i-mscp.net>
 * @param $package_installtype
 * @param $package_name
 * @param $package_version
 * @param $package_language
 * @param $user_id
 * @return result array
 */
function check_package_is_installed($package_installtype, $package_name,
    $package_version, $package_language, $user_id)
{
    $query = "
        SELECT
            `admin_type`,
            `admin_name`
        FROM
            `admin`
        WHERE
            `admin_id` = '" . $user_id . "'
        ;
    ";
    $rs_admin_type = exec_query($query);
    if ($rs_admin_type->fields['admin_type'] == "admin") {
        $query = "
            SELECT
                `software_id`
            FROM
                `web_software`
            WHERE
                `software_installtype`  = '" . $package_installtype . "'
            AND
                `software_name`         = '" . $package_name . "'
            AND
                `software_version`      = '" . $package_version . "'
            AND
                `software_language`     = '" . $package_language . "'
            AND
                `software_depot`        = 'no'
            ;
        ";
    } else {
        $query = "
            SELECT
                `software_id`
            FROM
                `web_software`
            WHERE
                `software_installtype`  = '" . $package_installtype . "'
            AND
                `software_name`         = '" . $package_name . "'
            AND
                `software_version`      = '" . $package_version . "'
            AND
                `software_language`     = '" . $package_language . "'
            AND
                `reseller_id`           = '" . $user_id . "'
            AND
                `software_depot`        = 'no'
            ;
        ";
    }
    $rs = exec_query($query);
    $sw_count_res = $rs->recordCount();

    $query = "
        SELECT
            `software_id`
        FROM
            `web_software`
        WHERE
            `software_installtype`  = '" . $package_installtype . "'
        AND
            `software_name`         = '" . $package_name . "'
        AND
            `software_version`      = '" . $package_version . "'
        AND
            `software_language`     = '" . $package_language . "'
        AND
            `software_master_id`    = '0'
        AND
            `software_depot`        = 'yes'
        ;
    ";
    $rs = exec_query($query);
    $sw_count_swdepot = $rs->recordCount();

    if ($sw_count_res > 0 || $sw_count_swdepot > 0) {
        if ($sw_count_res > 0) {
            return array(true, 'reseller');
        } else {
            return array(true, 'sw_depot');
        }
    } else {
        return array(false, 'not_installed');
    }
}

/**
 * Get all software packages from database since last update from the websoftware
 * depot.
 *
 * @since 1.0.0
 * @author Sascha Bay (TheCry) <sascha.bay@i-mscp.net>
 * @param iMSCP_pTemplate $tpl Template engine
 * @param $user_id User unique identifier
 * @return int
 */
function get_webdepot_software_list($tpl, $user_id)
{
    $query = "
		SELECT
			*
		FROM
			`web_software_depot`
		ORDER BY
		    `package_install_type` ASC,
		    `package_title` ASC
		;
	";
    $rs = exec_query($query);

    if ($rs->recordCount() > 0) {
        while (!$rs->EOF) {
            $tpl->assign(array(
                              'TR_PACKAGE_NAME' => $rs->fields['package_title'],
                              'TR_PACKAGE_TOOLTIP' => $rs->fields['package_description'],
                              'TR_PACKAGE_INSTALL_TYPE' => $rs->fields['package_install_type'],
                              'TR_PACKAGE_VERSION' => $rs->fields['package_version'],
                              'TR_PACKAGE_LANGUAGE' => $rs->fields['package_language'],
                              'TR_PACKAGE_TYPE' => $rs->fields['package_type'],
                              'TR_PACKAGE_VENDOR_HP' => ($rs->fields['package_vendor_hp'] == '')
                                  ? tr('N/A')
                                  : '<a href="' . $rs->fields['package_vendor_hp'] . '" target="_blank">' . tr('Vendor hompage') . '</a>'));

            list($is_installed,$installed_on) = check_package_is_installed(
                $rs->fields['package_install_type'], $rs->fields['package_title'],
                $rs->fields['package_version'], $rs->fields['package_language'],
                $user_id
            );

            if ($is_installed) {
                $tpl->assign(array(
                                  'PACKAGE_HTTP_URL' => '',
                                  'TR_PACKAGE_INSTALL' => ($installed_on == "sw_depot")
                                      ? tr('Installed in software depot')
                                      : tr('Installed in reseller depot'),
                                  'TR_MESSAGE_INSTALL' => ''));
                $tpl->parse('PACKAGE_INFO_LINK', 'package_info_link');
                $tpl->assign('PACKAGE_INSTALL_LINK', '');
            } else {
                $tpl->assign(array(
                                  'PACKAGE_HTTP_URL' => $rs->fields['package_download_link'],
                                  'TR_PACKAGE_INSTALL' => tr('Start installation'),
                                  'TR_MESSAGE_INSTALL' => tr('Are you sure to install this package from the webdepot?', true)));
                $tpl->parse('PACKAGE_INSTALL_LINK', 'package_install_link');
                $tpl->assign('PACKAGE_INFO_LINK', '');
            }

            $tpl->parse('LIST_WEBDEPOTSOFTWARE', '.list_webdepotsoftware');
            $rs->moveNext();
        }
        $tpl->assign('NO_WEBDEPOTSOFTWARE_LIST', '');
    } else {
        $tpl->assign('NO_WEBDEPOTSOFTWARE_AVAILABLE',
                     tr('No software in webdepot found!'));

        $tpl->parse('NO_WEBDEPOTSOFTWARE_LIST',
                    '.no_webdepotsoftware_list');

        $tpl->assign('LIST_WEBDEPOTSOFTWARE', '');
    }

    return $rs->recordCount();
}

/**
 * Update database from the websoftware depot xml file list.
 *
 * @since iMSCP 1.0.0
 * @author Sascha Bay (TheCry) <sascha.bay@i-mscp.net>
 * @param string $XML_URL
 * @param string $webdepot_last_update
 */
function update_webdepot_software_list($XML_URL, $webdepot_last_update)
{
    $opts = array('http' => array('user_agent' => 'PHP libxml agent'));
    $context = stream_context_create($opts);
    libxml_set_streams_context($context);

    $webdepot_xml_file = new DOMDocument('1.0', 'iso-8859-1');
    $webdepot_xml_file->load($XML_URL);
    $XML_FILE = simplexml_import_dom($webdepot_xml_file);

    if (utf8_decode($XML_FILE->LAST_UPDATE->DATE) != $webdepot_last_update) {
        $truncatequery = "TRUNCATE TABLE `web_software_depot`;";
        exec_query($truncatequery);

        foreach ($XML_FILE->PACKAGE as $output) {
            if (!empty($output->INSTALL_TYPE) && !empty($output->INSTALL_TYPE) &&
                !empty($output->INSTALL_TYPE) && !empty($output->INSTALL_TYPE) &&
                !empty($output->INSTALL_TYPE) && !empty($output->INSTALL_TYPE) &&
                !empty($output->INSTALL_TYPE) && !empty($output->INSTALL_TYPE) &&
                !empty($output->INSTALL_TYPE)
            ) {
                $query = "
                    INSERT INTO
                        `web_software_depot` (
                            `package_install_type`, `package_title`, `package_version`,
                            `package_language`, `package_type`, `package_description`,
                            `package_vendor_hp`, `package_download_link`,
                            `package_signature_link`
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?
                        )
                    ;
                ";
                exec_query($query,
                           array(
                                utf8_decode(clean_input($output->INSTALL_TYPE)),
                                utf8_decode(clean_input($output->TITLE)),
                                utf8_decode(clean_input($output->VERSION)),
                                utf8_decode(clean_input($output->LANGUAGE)),
                                utf8_decode(clean_input($output->TYPE)),
                                utf8_decode(clean_input($output->DESCRIPTION)),
                                encode_idna(utf8_decode(strtolower(clean_input($output->VENDOR_HP)))),
                                encode_idna(utf8_decode(strtolower(clean_input($output->DOWNLOAD_LINK)))),
                                encode_idna(utf8_decode(strtolower(clean_input($output->SIGNATURE_LINK))))));
            }
        }
        $query = "
            UPDATE
                `web_software_options`
            SET
                `webdepot_last_update` = '" . $XML_FILE->LAST_UPDATE->DATE . "'
            ;
        ";
        exec_query($query);

        set_page_message(tr("Websoftware depot list was updated"), 'info');
    } else {
        set_page_message(tr("No update for the websoftware depot list available"), 'warning');
    }
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
 * Tells whether or not the software installer is available for a reseller.
 *
 * @param  int $reseller_id Reseller unique identifier
 * @return string 'yes' if software installer is available, 'no' otherwise
 */
function get_reseller_sw_installer($reseller_id)
{
    $query = "
        SELECT
            `software_allowed`
        FROM
            `reseller_props`
        WHERE
            `reseller_id` = ?
        ;
    ";
    $stmt = exec_query($query, $reseller_id);

    return $stmt->fields['software_allowed'];
}

/************************************************************************************
 * iMSCP daemon related functions
 */

/**
 * Reads line from the socket resource.
 *
 * @param resource &$socket
 * @return string A line read from the socket resource
 */
function read_line(&$socket)
{
    $line = '';

    do {
        $ch = socket_read($socket, 1);
        $line = $line . $ch;
    } while ($ch != "\r" && $ch != "\n");

    return $line;
}

/**
 * Send a request to the daemon.
 *
 * @return string Daemon answer
 * @todo Remove error operator
 */
function send_request()
{
    /** @var $cfg  iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    //$code = 999;

    @$socket = socket_create(AF_INET, SOCK_STREAM, 0);
    if ($socket < 0) {
        $errno = "socket_create() failed.\n";
        return $errno;
    }

    @$result = socket_connect($socket, '127.0.0.1', 9876);
    if ($result == false) {
        $errno = "socket_connect() failed.\n";
        return $errno;
    }

    // read one line with welcome string
    $out = read_line($socket);

    list($code) = explode(' ', $out);
    if ($code == 999) {
        return $out;
    }

    // send hello query
    $query = "helo  {$cfg->Version}\r\n";
    socket_write($socket, $query, strlen($query));

    // read one line with helo answer
    $out = read_line($socket);

    list($code) = explode(' ', $out);
    if ($code == 999) {
        return $out;
    }

    // send reg check query
    $query = "execute query\r\n";
    socket_write($socket, $query, strlen($query));
    // read one line key replay
    $execute_reply = read_line($socket);

    list($code) = explode(' ', $execute_reply);
    if ($code == 999) {
        return $out;
    }

    // send quit query
    $quit_query = "bye\r\n";
    socket_write($socket, $quit_query, strlen($quit_query));

    // read quit answer
    $quit_reply = read_line($socket);

    list($code) = explode(' ', $quit_reply);

    if ($code == 999) {
        return $out;
    }

    list($answer) = explode(' ', $execute_reply);

    socket_close($socket);

    return $answer;
}

/************************************************************************************
 * Database related functions
 */

/**
 * Decrypte database password.
 *
 * @throws iMSCP_Exception
 * @param  string $password Encrypted database password
 * @return string Decrypted database password
 * @todo Remove error operator
 */
function decrypt_db_password($password)
{
    if ($password == '') {
        return '';
    }

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
 * Executes a SQL statement.
 *
 * Note: You may pass additional parameters. They will be treated as though you
 * called PDOStatement::setFetchMode() on the resultant statement object that is
 * wrapped by the iMSCP_Database_ResultSet object.
 *
 * @see iMSCP_Database::execute()
 * @throws iMSCP_Exception_Database
 * @param string $query                 SQL statement to be executed
 * @param array|int|string $parameters  OPTIONAL parameters - See iMSCP_Database::execute()
 * @return iMSCP_Database_ResultSet     An iMSCP_Database_ResultSet object
 */
function execute_query($query, $parameters = null)
{
    static $db = null;

    if(null === $db) {
        /** @var $db iMSCP_Database */
        $db = iMSCP_Registry::get('db');
    }

    if (null !== $parameters) {
        $parameters = func_get_args();
        array_shift($parameters);
        $stmt = call_user_func_array(array($db, 'execute'), $parameters);
    } else {
        $stmt = $db->execute($query);
    }

    if ($stmt == false) {
        throw new iMSCP_Exception_Database($db->getLastErrorMessage());
    }

    return $stmt;
}

/**
 * Convenience method to prepare and execute a query.
 *
 * <b>Note:</b> On failure, and if the $failDie parameter is set to TRUE, this
 * function sends a mail to the administrator with some relevant information
 * such as the debug information if the
 * {@link iMSCP_Exception_Writer_Mail writer} is active.
 *
 * @throws iMSCP_Exception_Database
 * @param string $query             SQL statement
 * @param string|int|array $bind    Data to bind to the placeholders
 * @param boolean $failDie          If TRUE, throws an iMSCP_Exception_Database
 *                                  exception on failure
 * @return iMSCP_Database_ResultSet A iMSCP_Database_ResultSet object that represents
 *                                  a result set or FALSE on failure if $failDie is
 *                                  set to FALSE
 */
function exec_query($query, $bind = null, $failDie = true)
{
    static $db = null;

    if(null === $db) {
        /** @var $db iMSCP_Database */
        $db = iMSCP_Registry::get('db');
    }

    //echo $query . '<br />';
    if (!($stmt = $db->prepare($query)) || !($stmt = $db->execute($stmt, $bind))) {
        if ($failDie) {
            throw new iMSCP_Exception_Database(
                $db->getLastErrorMessage() . " - Query: $query"
            );
        }
    }

    return $stmt;
}

/**
 * Quote SQL identifier.
 *
 * Note: An Identifier is essentially a name of a database, table, or table column.
 *
 * @param  $identifier Identifier to quote
 * @return string quoted identifier
 */
function quoteIdentifier($identifier)
{
    static $db = null;

    if(null === $db) {
        /** @var $db iMSCP_Database */
        $db = iMSCP_Registry::get('db');
    }

    $quoteIdentifierSymbol = $db->getQuoteIdentifierSymbol();

    $identifier = str_replace($quoteIdentifierSymbol,
                              '\\' .
                              $quoteIdentifierSymbol, $identifier);

    return $quoteIdentifierSymbol . $identifier . $quoteIdentifierSymbol;
}

/************************************************************************************
 * Debugging related functions
 */

/************************************************************************************
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
            $query = "SELECT COUNT(*) AS `cnt` FROM `$table` WHERE $where = ?;";
            $rs = exec_query($query, $bind);
        } else {
            $query = "SELECT COUNT(*) AS `cnt` FROM $table WHERE $where;";
            $rs = exec_query($query);
        }
    } else {
        $query = "SELECT COUNT(*) AS `cnt` FROM `$table`;";
        $rs = exec_query($query);
    }

    return (int)$rs->fields['cnt'];
}

/**
 * Unset global variables
 *
 * @return void
 */
function unsetMessages()
{
    $glToUnset = array();
    $glToUnset[] = 'user_page_message';
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
    $glToUnset[] = 'hp_added';
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
    $sessToUnset[] = 'hp_added';
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
