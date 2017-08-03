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
        return [
            $rdmnConsumed, $rdmnAssigned, $rdmnUnlimited,
            $rsubConsumed, $rsubAssigned, $rsubUnlimited,
            $ralsConsumed, $ralsAssigned, $ralsUnlimited,
            $rmailConsumed, $rmailAssigned, $rmailUnlimited,
            $rftpConsumed, $rftpAssigned, $rftpUnlimited,
            $rsqlDbConsumed, $rsqlDbAssigned, $rsqlDbUnlimited,
            $rsqlUserConsumed, $rsqlUserAssigned, $rsqlUserUnlimited,
            $rtraffConsumed, $rtraffAssigned, $rtraffUnlimited,
            $rdiskConsumed, $rdiskAssigned, $rdiskUnlimited
        ];
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

    return [
        $rdmnConsumed, $rdmnAssigned, $rdmnUnlimited, $rsubConsumed, $rsubAssigned, $rsubUnlimited, $ralsConsumed,
        $ralsAssigned, $ralsUnlimited, $rmailConsumed, $rmailAssigned, $rmailUnlimited, $rftpConsumed, $rftpAssigned,
        $rftpUnlimited, $rsqlDbConsumed, $rsqlDbAssigned, $rsqlDbUnlimited, $rsqlUserConsumed, $rsqlUserAssigned,
        $rsqlUserUnlimited, $rtraffConsumed, $rtraffAssigned, $rtraffUnlimited, $rdiskConsumed, $rdiskAssigned,
        $rdiskUnlimited
    ];
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
    return (systemHasManyAdmins() || systemHasResellers() || systemHasCustomers());
}

/**
 * Whether or not system has registered resellers or customers
 *
 * @return bool
 */
function systemHasResellersOrCustomers()
{
    return (systemHasResellers() || systemHasCustomers());
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
        $hasManyAdmins = ($stmt->rowCount() > 1);
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
