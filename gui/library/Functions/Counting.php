<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Global counting functions

/**
 * Returns count of SQL users
 *
 * @return int Number of SQL users
 */
function get_sql_user_count()
{
    return execute_query('SELECT COUNT(DISTINCT sqlu_name) FROM sql_user')->fetchRow(PDO::FETCH_COLUMN);
}

// Per criteria counting functions

/**
 * Returns a count of items present in a database table with optional search criterias.
 *
 * @param string $table Table name on which to operate
 * @param string $where OPTIONAL SQL WHERE clause
 * @param string $bind OPTIONAL value to bind to the placeholder
 * @return int Items count
 */
function records_count($table, $where = '', $bind = '')
{
    $table = quoteIdentifier($table);

    if ($where != '') {
        if ($bind != '') {
            $stmt = exec_query("SELECT COUNT(*) FROM $table WHERE $where = ?", $bind);
        } else {
            $stmt = execute_query("SELECT COUNT(*) FROM $table WHERE $where");
        }
    } else {
        $stmt = execute_query("SELECT COUNT(*) FROM $table");
    }

    return $stmt->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Returns number of items in a database table with optional search criterias
 *
 * @param string $field
 * @param string $table
 * @param string $where
 * @param string $value
 * @param string $subtable
 * @param string $subwhere
 * @param string $subgroupname
 * @return int
 */
function sub_records_count($field, $table, $where, $value, $subtable, $subwhere, $subgroupname)
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

    if ($subgroupname != '') {
        $sqldIds = [];

        while (!$stmt->EOF) {
            array_push($sqldIds, $stmt->fields['field']);
            $stmt->moveNext();
        }

        $sqldIds = implode(',', $sqldIds);

        if ($subwhere != '') {
            $subres = execute_query(
                "SELECT COUNT(DISTINCT $subgroupname) AS `cnt` FROM $subtable WHERE `sqld_id` IN ($sqldIds)"
            );
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
 * @param string $subtable
 * @param string $subwhere
 * @param string $a
 * @param string $b
 * @return int
 */
function sub_records_rlike_count($field, $table, $where, $value, $subtable, $subwhere, $a, $b)
{
    if ($where !== '') {
        $stmt = exec_query("SELECT $field AS `field` FROM $table WHERE $where = ?", $value);
    } else {
        $stmt = execute_query("SELECT $field AS `field` FROM $table");
    }

    $result = 0;

    if (!$stmt->rowCount()) {
        return $result;
    }

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        if ($subwhere === '') {
            return $result;

        }

        $result += exec_query(
            "SELECT COUNT(*) FROM $subtable WHERE $subwhere RLIKE ?", $a . $row['field'] . $b
        )->fetchRow(PDO::FETCH_COLUMN);
    }

    return $result;
}

// Per domain/customer counting functions

/**
 * Retrieve count of subdomains that belong to the given domain
 *
 * @param int $domainId Domain unique identifier
 * @return int Count of subdomains that belong to the given domain
 */
function get_domain_running_sub_cnt($domainId)
{
    $subCount = exec_query(
        'SELECT COUNT(subdomain_id) FROM subdomain WHERE domain_id = ?', $domainId
    )->fetchRow(PDO::FETCH_COLUMN);

    $subCount += exec_query(
        'SELECT COUNT(subdomain_alias_id) FROM subdomain_alias JOIN domain_aliasses USING(alias_id) WHERE domain_id = ?',
        $domainId
    )->fetchRow(PDO::FETCH_COLUMN);

    return $subCount;
}

/**
 * Retrieve count of domain aliases that belong to the given domain
 *
 * @param int $domainId Domain unique identifier
 * @return int Count of domain_aliases that belong to the given domain
 */
function get_domain_running_als_cnt($domainId)
{
    return exec_query(
        "SELECT COUNT(alias_id) FROM domain_aliasses WHERE domain_id = ? AND alias_status <> 'ordered'", $domainId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of mail accounts that belong to the given domain
 *
 * @param int $domainId Domain unique identifier
 * @return int Count of mail accounts that belong to the given domain
 */
function get_domain_running_mail_acc_cnt($domainId)
{
    $query = 'SELECT COUNT(mail_id) FROM mail_users WHERE domain_id = ?';

    if (!iMSCP_Registry::get('config')['COUNT_DEFAULT_EMAIL_ADDRESSES']) {
        # A default mail account is composed of a name matching with:
        # - abuse, hostmaster, postmaster or webmaster for a domain
        # - webmaster for a subdomain
        # and is set as forward mail account. If the customeer turn a default
        # mail account into a normal mail account, it is no longer seen as
        # default mail account.
        $query .= "
            AND ! (
                (
                    mail_acc IN('abuse', 'hostmaster', 'postmaster', 'webmaster')
                    AND
                    mail_type IN('" . MT_NORMAL_FORWARD . "', '" . MT_ALIAS_FORWARD . "')
                )    
                OR
                (
                    mail_acc = 'webmaster'
                    AND
                    mail_type IN('" . MT_SUBDOM_FORWARD . "', '" . MT_ALSSUB_FORWARD . "')
                )
            )
        ";
    }

    return exec_query($query, $domainId)->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of FTP account that belong to the given customer
 *
 * @param int $customerId Customer unique identifier
 * @return int Total number of FTP accounts that belong to the given domain
 */
function get_customer_running_ftp_acc_cnt($customerId)
{
    return exec_query(
        'SELECT COUNT(userid) FROM ftp_users WHERE admin_id = ?', $customerId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of SQL databases that belong to the given domain
 *
 * @param int $domainId Domain unique identifier
 * @return int Total number of SQL databases that belong to the given domain
 */
function get_domain_running_sqld_acc_cnt($domainId)
{
    return exec_query(
        'SELECT COUNT(sqld_id) FROM sql_database WHERE domain_id = ?', $domainId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of SQL users that belong to the given domain
 *
 * @param int $domainId Domain unique identifier
 * @return int Total number of SQL users that belong to the given domain
 */
function get_domain_running_sqlu_acc_cnt($domainId)
{
    return exec_query(
        'SELECT COUNT(DISTINCT sqlu_name) FROM sql_user JOIN sql_database USING(sqld_id) WHERE domain_id = ?',
        $domainId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of both SQL databases and SQL users that belong to the given domain
 *
 * @param int $domainId Domain unique identifier
 * @return array An array containing total number of SQL databases and users
 *               that belong to the given domain
 */
function get_domain_running_sql_acc_cnt($domainId)
{
    return [get_domain_running_sqld_acc_cnt($domainId), get_domain_running_sqlu_acc_cnt($domainId)];
}

/**
 * Retrieve count of subdomains, domain aliases, mail accounts, FTP accounts,
 * SQL database and SQL users that belong to the given customer
 *
 * @param int $customerId Customer unique identifier
 * @return array An array containing count of subdomain, domain aliases, mail
 *               accounts, FTP accounts, SQL databases and SQL users for the
 *               given customer
 */
function get_customer_running_props_cnt($customerId)
{
    $domainId = get_user_domain_id($customerId);

    return [
        get_domain_running_sub_cnt($domainId),
        get_domain_running_als_cnt($domainId),
        get_domain_running_mail_acc_cnt($domainId),
        get_customer_running_ftp_acc_cnt($customerId),
        get_domain_running_sqld_acc_cnt($domainId),
        get_domain_running_sqlu_acc_cnt($domainId)
    ];
}
