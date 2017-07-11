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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2017 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/**
 * Returns count of SQL users
 *
 * @return int Number of SQL users
 */
function get_sql_user_count()
{
    return execute_query('SELECT COUNT(DISTINCT sqlu_name) FROM sql_user')->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Returns the total number of consumed and assigned items for the given reseller
 *
 * @param  int $resellerId Reseller unique indentifier
 * @return array
 */
function generate_reseller_users_props($resellerId)
{
    $rdmnConsumed = $rdmnAssigned = $rsubConsumed = $rsubAssigned = $ralsConsumed = $ralsAssigned = $rmailConsumed =
    $rmailAssigned = $rftpConsumed = $rftpAssigned = $rsqlDbConsumed = $rsqlDbAssigned = $rsqlUserConsumed =
    $rsqlUserAssigned = $rtraffConsumed = $rtraffAssigned = $rdiskConsumed = $rdiskAssigned = 0;

    $stmt = exec_query('SELECT admin_id FROM admin WHERE created_by = ?', $resellerId);

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

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        list(
            $subConsumed, $subAssigned, $alsConsumed, $alsAssigned, $mailConsumed, $mailAssigned, $ftpConsumed,
            $ftpAssigned, $sqlDbConsumed, $sqlDbAssigned, $sqlUserConsumed, $sqlUserAssigned, $traffAssigned, $diskAssigned
            ) = shared_getCustomerProps($row['admin_id']);

        list(, , , , , , $traffConsumed, $diskConsumed) = shared_getCustomerStats($row['admin_id']);

        $rdmnConsumed += 1;

        // Compute subdomains
        if ($subAssigned != -1) {
            $rsubConsumed += $subConsumed;
            $rsubAssigned += $subAssigned;

            if (!$subAssigned) {
                $rsubUnlimited = true;
            }
        }

        // Compute domain aliases
        if ($alsAssigned != -1) {
            $ralsConsumed += $alsConsumed;
            $ralsAssigned += $alsAssigned;

            if (!$alsAssigned) {
                $ralsUnlimited = true;
            }
        }

        // Compute mail accounts
        if ($sqlDbAssigned != -1) {
            $rmailConsumed += $mailConsumed;
            $rmailAssigned += $mailAssigned;

            if (!$mailAssigned) {
                $rmailUnlimited = true;
            }
        }

        // Compute Ftp account
        if ($ftpAssigned != -1) {
            $rftpConsumed += $ftpConsumed;
            $rftpAssigned += $ftpAssigned;

            if (!$ftpAssigned) {
                $rftpUnlimited = true;
            }
        }

        // Compute Sql databases
        if ($sqlDbAssigned != -1) {
            $rsqlDbConsumed += $sqlDbConsumed;
            $rsqlDbAssigned += $sqlDbAssigned;

            if (!$sqlDbAssigned) {
                $rsqlDbUnlimited = true;
            }
        }

        // Compute Sql users
        if ($sqlUserAssigned != -1) {
            $rsqlUserConsumed += $sqlUserConsumed;
            $rsqlUserAssigned += $sqlUserAssigned;

            if (!$sqlUserAssigned) {
                $rsqlUserUnlimited = true;
            }
        }

        // Compute Monthly traffic volume
        $rtraffConsumed += $traffConsumed;
        $rtraffAssigned += $traffAssigned;

        if (!$rtraffAssigned) {
            $rtraffUnlimited = true;
        }

        // Compute diskspace
        $rdiskConsumed += $diskConsumed;
        $rdiskAssigned += $diskAssigned;

        if (!$rdiskAssigned) {
            $rdiskUnlimited = true;
        }
    }

    return array(
        $rdmnConsumed, $rdmnAssigned, $rdmnUnlimited, $rsubConsumed, $rsubAssigned, $rsubUnlimited, $ralsConsumed,
        $ralsAssigned, $ralsUnlimited, $rmailConsumed, $rmailAssigned, $rmailUnlimited, $rftpConsumed, $rftpAssigned,
        $rftpUnlimited, $rsqlDbConsumed, $rsqlDbAssigned, $rsqlDbUnlimited, $rsqlUserConsumed, $rsqlUserAssigned,
        $rsqlUserUnlimited, $rtraffConsumed, $rtraffAssigned, $rtraffUnlimited, $rdiskConsumed, $rdiskAssigned,
        $rdiskUnlimited
    );
}

/**
 * Generate query for user search form
 *
 * @param  string &$searchQuery
 * @param  string &$countQuery
 * @param  int $startIndex
 * @param  int $rowsPerPage
 * @param  string $searchFor
 * @param  string $searchCommon
 * @param  string $searchStatus
 * @return void
 */
function gen_admin_domain_query(
    &$searchQuery, &$countQuery, $startIndex, $rowsPerPage, $searchFor, $searchCommon, $searchStatus
)
{
    $condition = '';
    $startIndex = filter_digits($startIndex);
    $rowsPerPage = filter_digits($rowsPerPage);

    if ($searchFor == 'n/a' && $searchCommon == 'n/a' && $searchStatus == 'n/a') {
        $countQuery = '
          SELECT COUNT(*)
          FROM domain AS t1
          INNER JOIN admin AS t2 ON (t2.admin_id = t1.domain_admin_id)
          INNER JOIN admin AS t3 ON(t3.admin_id = t2.created_by)
        ';
        $searchQuery = "
            SELECT t1.*, t2.admin_id, t2.admin_status, t3.admin_name AS reseller_name
            FROM domain AS t1
            INNER JOIN admin AS t2 ON (t2.admin_id = t1.domain_admin_id)
            INNER JOIN admin AS t3 ON(t3.admin_id = t2.created_by)
            ORDER BY t1.domain_name ASC
            LIMIT $startIndex, $rowsPerPage
        ";
        return;
    }

    $db = iMSCP_Database::getInstance();
    $searchFor = str_replace(array('!', '_', '%'), array('!!', '!_', '!%'), $searchFor);

    if ($searchFor == '' && $searchStatus != '') {
        if ($searchStatus != 'all') {
            $condition = 'WHERE t1.domain_status = ' . $db->quote($searchStatus);
        }

        $countQuery = "
          SELECT COUNT(*)
          FROM domain AS t1
          INNER JOIN admin AS t2 ON (t2.admin_id = t1.domain_admin_id)
          INNER JOIN admin AS t3 ON(t3.admin_id = t2.created_by)
          $condition
        ";
        $searchQuery = "
            SELECT t1.*, t2.admin_id, t2.admin_status, t3.admin_name AS reseller_name
            FROM domain AS t1
            INNER JOIN admin AS t2 ON (t2.admin_id = t1.domain_admin_id)
            INNER JOIN admin AS t3 ON(t3.admin_id = t2.created_by)
            $condition
            ORDER BY t1.domain_name ASC
            LIMIT $startIndex, $rowsPerPage
        ";
    } elseif ($searchFor != '') {
        $searchFor = str_replace(array('!', '_', '%'), array('!!', '!_', '!%'), $searchFor);
        $searchFor = ($searchCommon == 'domain_name')
            ? $db->quote('%' . encode_idna($searchFor) . '%') : $db->quote("%$searchFor%");

        if ($searchCommon == 'domain_name') {
            $condition = "WHERE t1.domain_name LIKE $searchFor ESCAPE '!'";
        } elseif ($searchCommon == 'customer_id') {
            $condition = "WHERE t2.customer_id LIKE $searchFor ESCAPE '!'";
        } elseif ($searchCommon == 'fname') {
            $condition = "WHERE t2.fname LIKE $searchFor ESCAPE '='";
        } elseif ($searchCommon == 'lname') {
            $condition = "WHERE t2.lname LIKE $searchFor ESCAPE '='";
        } elseif ($searchCommon == 'firm') {
            $condition = "WHERE t2.firm LIKE $searchFor ESCAPE '!'";
        } elseif ($searchCommon == 'city') {
            $condition = "WHERE t2.city LIKE $searchFor ESCAPE '!'";
        } elseif ($searchCommon == 'state') {
            $condition = "WHERE t2.state LIKE $searchFor ESCAPE '!'";
        } elseif ($searchCommon == 'country') {
            $condition = "WHERE t2.country LIKE $searchFor ESCAPE '!'";
        } elseif ($searchCommon == 'reseller_name') {
            $condition = "WHERE t3.admin_name LIKE $searchFor ESCAPE '!'";
        }

        if ($condition != '') {
            if ($searchStatus != 'all') {
                $condition .= ' AND t1.domain_status = ' . $db->quote($searchStatus);
            }

            $countQuery = "
                SELECT COUNT(*)
                FROM domain AS t1
                INNER JOIN admin AS t2 ON(t2.admin_id = t1.domain_admin_id)
                INNER JOIN admin AS t3 ON(t3.admin_id = t2.created_by)
                $condition
            ";

            $searchQuery = "
                SELECT t1.*, t2.admin_id, t2.admin_status, t3.admin_name AS reseller_name
                FROM domain AS t1
                INNER JOIN admin AS t2 ON(t2.admin_id = t1.domain_admin_id)
                INNER JOIN admin AS t3 ON(t3.admin_id = t2.created_by)
                $condition
                ORDER BY t1.domain_name ASC
                LIMIT $startIndex, $rowsPerPage
            ";
        }
    }
}

/**
 * Whether or not the system has a least the given number of registered resellers
 *
 * @param int $minNbResellers Minimum number of resellers
 * @return bool TRUE if the system has a least the given number of registered resellers, FALSE otherwise
 */
function systemHasResellers($minNbResellers = 1)
{
    static $resellersCount = NULL;

    if (NULL === $resellersCount) {
        $stmt = execute_query("SELECT COUNT(admin_id) FROM admin WHERE admin_type = 'reseller'");
        $resellersCount = $stmt->fetchRow(PDO::FETCH_COLUMN);
    }

    return ($resellersCount >= $minNbResellers);
}

/**
 * Whether or not the system has a least the given number of registered customers
 *
 * @param int $minNbCustomers Minimum number of customers
 * @return bool TRUE if system has a least the given number of registered customers, FALSE otherwise
 */
function systemHasCustomers($minNbCustomers = 1)
{
    static $customersCount = NULL;

    if (NULL === $customersCount) {
        $stmt = execute_query("SELECT COUNT(admin_id) FROM admin WHERE admin_type = 'user' AND admin_status <> 'todelete'");
        $customersCount = $stmt->fetchRow(PDO::FETCH_COLUMN);
    }

    return ($customersCount >= $minNbCustomers);
}

/**
 * Whether or not system has registered admins (many), resellers or customers
 *
 * @return bool
 */
function systemHasAdminsOrResellersOrCustomers()
{
    if (systemHasManyAdmins() || systemHasResellers() || systemHasCustomers()) {
        return true;
    }

    return false;
}

/**
 * Whether or not system has registered resellers or customers
 *
 * @return bool
 */
function systemHasResellersOrCustomers()
{
    if (systemHasResellers() || systemHasCustomers()) {
        return true;
    }

    return false;
}

/**
 * Whether or not system as many admins
 *
 * @return bool
 */
function systemHasManyAdmins()
{
    static $hasManyAdmins = NULL;

    if (NULL === $hasManyAdmins) {
        $stmt = exec_query('SELECT admin_id FROM admin WHERE admin_type = ? LIMIT 2', 'admin');
        $hasManyAdmins = ($stmt->rowCount() > 1) ? true : false;
    }

    return $hasManyAdmins;
}

/**
 * Whether or not system has anti-rootkits
 *
 * @return bool
 */
function systemHasAntiRootkits()
{
    $config = iMSCP_Registry::get('config');

    if ((isset($config['ANTI_ROOTKITS_PACKAGES']) && $config['ANTI_ROOTKITS_PACKAGES'] != 'No'
            && $config['ANTI_ROOTKITS_PACKAGES'] != ''
            && ((isset($config['CHKROOTKIT_LOG']) && $config['CHKROOTKIT_LOG'] != '')
                || (isset($config['RKHUNTER_LOG']) && $config['RKHUNTER_LOG'] != '')))
        || isset($config['OTHER_ROOTKIT_LOG']) && $config['OTHER_ROOTKIT_LOG'] != ''
    ) {
        return true;
    }

    return false;
}
