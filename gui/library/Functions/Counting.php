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

use iMSCP_Registry as Registry;

// Global counting functions

/**
 * Retrieve count of administrator accounts, excluding those that are being
 * deleted
 *
 * @return int Count of administrator accounts
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function get_administrators_count()
{
    return get_objects_count('admin', 'admin_id', "WHERE admin_type = 'admin' AND admin_status <> 'todelete'");
}

/**
 * Retrieve count of reseller accounts, excluding those that are being deleted
 *
 * @return int Count of reseller accounts
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function get_resellers_count()
{
    return get_objects_count('admin', 'admin_id', "WHERE admin_type = 'reseller' AND admin_status <> 'todelete'");
}

/**
 * Retrieve count of customers accounts, excluding those that are being deleted
 *
 * @return int Count of customer accounts
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function get_customers_count()
{
    return get_objects_count('admin', 'admin_id', "WHERE admin_type = 'user' AND admin_status <> 'todelete'");
}

/**
 * Retrieve count of domains, excluding those that are being deleted
 *
 * @return int Count of domains
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function get_domains_count()
{
    return get_objects_count('domain', 'domain_id', "WHERE domain_status <> 'todelete'");
}

/**
 * Retrieve count of subdomains, excluding those that are being deleted
 *
 *
 * @return int Count of subdomains
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function get_subdomains_count()
{
    return get_objects_count('subdomain', 'subdomain_id', "WHERE subdomain_status <> 'todelete'")
        + get_objects_count('subdomain_alias', 'subdomain_alias_id', "WHERE subdomain_alias_status <> 'todelete'");
}

/**
 * Retrieve count of domain aliases, excluding those that are ordered or being deleted
 *
 * @return int Count of domain aliases
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function get_domain_aliases_count()
{
    return get_objects_count('domain_aliasses', 'alias_id', "WHERE alias_status NOT IN('ordered', 'todelete')");
}

/**
 * Retrieve count of mail accounts, excluding those that are being deleted
 *
 * Default mail accounts are counted or not, depending of administrator settings.
 *
 * @return int Count of mail accounts
 * @throws Zend_Exception
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function get_mail_accounts_count()
{
    $where = '';

    if (!Registry::get('config')['COUNT_DEFAULT_EMAIL_ADDRESSES']) {
        # A default mail account is composed of a name matching with:
        # - abuse, hostmaster, postmaster or webmaster for a domain
        # - webmaster for a subdomain
        # and is set as forward mail account. If the customeer turn a default
        # mail account into a normal mail account, it is no longer seen as
        # default mail account.
        $where .= "
            WHERE ! (
                mail_acc IN('abuse', 'hostmaster', 'postmaster', 'webmaster')
                AND
                mail_type IN('" . MT_NORMAL_FORWARD . "', '" . MT_ALIAS_FORWARD . "')
            )
            AND !(mail_acc = 'webmaster' AND mail_type IN('" . MT_SUBDOM_FORWARD . "', '" . MT_ALSSUB_FORWARD . "'))
        ";
    }

    $where .= ($where == '' ? 'WHERE ' : 'AND ') . "status <> 'todelete'";

    return get_objects_count('mail_users', 'mail_id', $where);
}

/**
 * Retrieve count of FTP users, excluding those that are being deleted
 *
 * @return int Count of FTP users
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function get_ftp_users_count()
{
    return get_objects_count('ftp_users', 'userid', "WHERE status <> 'todelete'");
}

/**
 * Retrieve count of SQL databases
 *
 * @return int Count of SQL databases;
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function get_sql_databases_count()
{
    return get_objects_count('sql_database', 'sqld_id');
}

/**
 * Retrieve count of SQL users
 *
 * @return int Count of SQL users
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function get_sql_users_count()
{
    return get_objects_count('sql_user', 'sqlu_name');
}

/**
 * Retrieve count of objects from the given table using the given identifier
 * field and optional WHERE clause
 *
 * @param string $table
 * @param string $idField Identifier field
 * @param string $where OPTIONAL Where clause
 * @return int Count of objects
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_objects_count($table, $idField, $where = '')
{
    $table = quoteIdentifier($table);
    $idField = quoteIdentifier($idField);

    return execute_query("SELECT COUNT(DISTINCT $idField) FROM $table $where")->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of subdomains, domain aliases, mail accounts, FTP users,
 * SQL database and SQL users that belong to the given reseller, excluding
 * those that are being deleted
 *
 * @return array An array containing count of administrators, resellers,
 *              customers, domains, subdomains, domain aliases, mail accounts,
 *              FTP users, SQL databases and SQL users
 * @throws Zend_Exception
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function get_objects_counts()
{
    return [
        get_administrators_count(),
        get_resellers_count(),
        get_customers_count(),
        get_domains_count(),
        get_subdomains_count(),
        get_domain_aliases_count(),
        get_mail_accounts_count(),
        get_ftp_users_count(),
        get_sql_databases_count(),
        get_sql_users_count()
    ];
}

// Per reseller counting functions

/**
 * Retrieve count of customer accounts that belong to the given reseller,
 * excluding those that are being deleted
 *
 * @param int $resellerId Reseller unique identifier
 * @return int Count of subdomains
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_reseller_customers_count($resellerId)
{
    return exec_query(
        "SELECT COUNT(admin_id) FROM admin WHERE created_by = ? AND admin_status <> 'todelete'", $resellerId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of domains that belong to the given reseller, excluding those
 * that are being deleted
 *
 * @param int $resellerId Reseller unique identifier
 * @return int Count of subdomains
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_reseller_domains_count($resellerId)
{
    return exec_query(
        "
            SELECT COUNT(domain_id)
            FROM domain
            JOIN admin ON(admin_id = domain_admin_id)
            WHERE created_by = ?
            AND domain_status <> 'todelete'
        ", $resellerId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of subdomains that belong to the given reseller, excluding
 * those that are being deleted
 *
 * @param int $resellerId Reseller unique identifier
 * @return int Count of subdomains
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_reseller_subdomains_count($resellerId)
{
    return exec_query(
            "
                SELECT COUNT(subdomain_id)
                FROM subdomain
                JOIN domain USING(domain_id)
                JOIN admin ON(admin_id = domain_admin_id)
                WHERE created_by = ?
                AND subdomain_status <> 'todelete'
            ",
            $resellerId
        )->fetchRow(PDO::FETCH_COLUMN)
        +
        exec_query(
            "
                SELECT COUNT(subdomain_alias_id)
                FROM subdomain_alias
                JOIN domain_aliasses USING(alias_id)
                JOIN domain USING(domain_id)
                JOIN admin ON(admin_id = domain_admin_id)
                WHERE created_by = ?
                AND subdomain_alias_status <> 'todelete'
            ",
            $resellerId
        )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of domain aliases that belong to the given reseller,
 * excluding those that are ordered or being deleted
 *
 * @param int $resellerId Reseller unique identifier
 * @return int Count of domain aliases
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_reseller_domain_aliases_count($resellerId)
{
    return exec_query(
        "
            SELECT COUNT(alias_id)
            FROM domain_aliasses
            JOIN domain USING(domain_id)
            JOIN admin ON(admin_id = domain_admin_id)
            WHERE created_by = ?
            AND alias_status <> 'todelete'
        ",
        $resellerId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of mail accounts that belong to the given reseller, excluding
 * those that are being deleted
 *
 * Default mail accounts are counted or not, depending of administrator settings.
 *
 * @param int $resellerId Domain unique identifier
 * @return int Count of mail accounts
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_reseller_mail_accounts_count($resellerId)
{
    $query = '
        SELECT COUNT(mail_id)
        FROM mail_users
        JOIN domain USING(domain_id)
        JOIN admin ON(admin_id = domain_admin_id)
        WHERE created_by = ?
    ';

    if (!Registry::get('config')['COUNT_DEFAULT_EMAIL_ADDRESSES']) {
        # A default mail account is composed of a name matching with:
        # - abuse, hostmaster, postmaster or webmaster for a domain
        # - webmaster for a subdomain
        # and is set as forward mail account. If the customeer turn a default
        # mail account into a normal mail account, it is no longer seen as
        # default mail account.
        $query .= "
            AND !(
                mail_acc IN('abuse', 'hostmaster', 'postmaster', 'webmaster')
                AND
                mail_type IN('" . MT_NORMAL_FORWARD . "', '" . MT_ALIAS_FORWARD . "')
            )    
            AND !(mail_acc = 'webmaster' AND mail_type IN('" . MT_SUBDOM_FORWARD . "', '" . MT_ALSSUB_FORWARD . "'))
        ";
    }

    $query .= "AND status <> 'todelete'";

    return exec_query($query, $resellerId)->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of FTP users that belong to the given reseller, excluding
 * those that are being deleted
 *
 * @param int $resellerId Reseller unique identifier
 * @return int Count of FTP users
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_reseller_ftp_users_count($resellerId)
{
    return exec_query(
        "SELECT COUNT(userid) FROM ftp_users JOIN admin USING(admin_id) WHERE created_by = ? AND status <> 'todelete'",
        $resellerId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of SQL databases that belong to the given reseller
 *
 * @param int $resellerId Reseller unique identifier
 * @return int Count of SQL databases
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_reseller_sql_databases_count($resellerId)
{
    return exec_query(
        '
            SELECT COUNT(sqld_id)
            FROM sql_database
            JOIN domain USING(domain_id)
            JOIN admin ON(admin_id = domain_admin_id)
            WHERE created_by = ?
        ',
        $resellerId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of SQL users that belong to the given reseller
 *
 * @param int $resellerId Domain unique identifier
 * @return int Count of SQL users
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_reseller_sql_users_count($resellerId)
{
    return exec_query(
        '
            SELECT COUNT(DISTINCT sqlu_name)
            FROM sql_user
            JOIN sql_database USING(sqld_id)
            JOIN domain USING(domain_id)
            JOIN admin ON(admin_id = domain_admin_id)
            WHERE created_by = ?
        ',
        $resellerId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of subdomains, domain aliases, mail accounts, FTP users,
 * SQL database and SQL users that belong to the given reseller, excluding
 * those that are being deleted
 *
 * @param int $resellerId Customer unique identifier
 * @return array An array containing count of customers, domains, subdomains,
 *               domain aliases, mail accounts, FTP users, SQL databases and
 *               SQL users
 * @throws Zend_Exception
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function get_reseller_objects_counts($resellerId)
{
    return [
        get_reseller_customers_count($resellerId),
        get_reseller_domains_count($resellerId),
        get_reseller_subdomains_count($resellerId),
        get_reseller_domain_aliases_count($resellerId),
        get_reseller_mail_accounts_count($resellerId),
        get_reseller_ftp_users_count($resellerId),
        get_reseller_sql_databases_count($resellerId),
        get_reseller_sql_users_count($resellerId)
    ];
}

// Per domain/customer counting functions

/**
 * Retrieve count of subdomains that belong to the given customer, excluding
 * those that are being deleted
 *
 * @param int $domainId Customer main domain unique identifier
 * @return int Count of subdomains
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_customer_subdomains_count($domainId)
{
    return exec_query(
            "SELECT COUNT(subdomain_id) FROM subdomain WHERE domain_id = ? AND subdomain_status <> 'todelete'",
            $domainId
        )->fetchRow(PDO::FETCH_COLUMN)
        + exec_query(
            "
                SELECT COUNT(subdomain_alias_id)
                FROM subdomain_alias
                JOIN domain_aliasses USING(alias_id)
                WHERE domain_id = ?
                AND subdomain_alias_status <> 'todelete'
            ",
            $domainId
        )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of domain aliases that belong to the given customer,
 * excluding those that are ordered or being deleted
 *
 * @param int $domainId Customer main domain unique identifier
 * @return int Count of domain aliases
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_customer_domain_aliases_count($domainId)
{
    return exec_query(
        "SELECT COUNT(alias_id) FROM domain_aliasses WHERE domain_id = ? AND alias_status NOT IN('ordered', 'todelete')",
        $domainId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of mail accounts that belong to the given customer, excluding
 * those that are being deleted
 *
 * Default mail accounts are counted or not, depending of administrator settings.
 *
 * @param int $domainId Customer main domain unique identifier
 * @return int Count of mail accounts
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_customer_mail_accounts_count($domainId)
{
    $query = 'SELECT COUNT(mail_id) FROM mail_users WHERE domain_id = ?';

    if (!Registry::get('config')['COUNT_DEFAULT_EMAIL_ADDRESSES']) {
        # A default mail account is composed of a name matching with:
        # - abuse, hostmaster, postmaster or webmaster for a domain
        # - webmaster for a subdomain
        # and is set as forward mail account. If the customeer turn a default
        # mail account into a normal mail account, it is no longer seen as
        # default mail account.
        $query .= "
            AND !(
                mail_acc IN('abuse', 'hostmaster', 'postmaster', 'webmaster')
                AND
                mail_type IN('" . MT_NORMAL_FORWARD . "', '" . MT_ALIAS_FORWARD . "')
            )    
            AND !(mail_acc = 'webmaster' AND mail_type IN('" . MT_SUBDOM_FORWARD . "', '" . MT_ALSSUB_FORWARD . "'))
        ";
    }

    $query .= "AND status <> 'todelete'";

    return exec_query($query, $domainId)->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of FTP users that belong to the given customer, excluding
 * those that are being deleted
 *
 * @param int $customerId Customer unique identifier
 * @return int Count of FTP users
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_customer_ftp_users_count($customerId)
{
    return exec_query(
        "SELECT COUNT(userid) FROM ftp_users WHERE admin_id = ? AND status <> 'todelete'", $customerId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of SQL databases that belong to the given customer
 *
 * @param int $domainId Customer main domain unique identifier
 * @return int Count of SQL databases
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_customer_sql_databases_count($domainId)
{
    return exec_query(
        'SELECT COUNT(sqld_id) FROM sql_database WHERE domain_id = ?', $domainId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of SQL users that belong to the given customer
 *
 * @param int $domainId Customer main domain unique identifier
 * @return int Count of SQL users
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_customer_sql_users_count($domainId)
{
    return exec_query(
        'SELECT COUNT(DISTINCT sqlu_name) FROM sql_user JOIN sql_database USING(sqld_id) WHERE domain_id = ?',
        $domainId
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Retrieve count of subdomains, domain aliases, mail accounts, FTP users,
 * SQL database and SQL users that belong to the given customer, excluding
 * those that are being deleted
 *
 * @param int $customerId Customer unique identifier
 * @return array An array containing count of subdomains, domain aliases, mail
 *               accounts, FTP users, SQL databases and SQL users
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function get_customer_objects_counts($customerId)
{
    $domainId = get_user_domain_id($customerId);

    return [
        get_customer_subdomains_count($domainId),
        get_customer_domain_aliases_count($domainId),
        get_customer_mail_accounts_count($domainId),
        get_customer_ftp_users_count($customerId),
        get_customer_sql_databases_count($domainId),
        get_customer_sql_users_count($domainId)
    ];
}
