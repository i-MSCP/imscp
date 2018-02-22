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
        $resellersCount = execute_query(
            "SELECT COUNT(admin_id) FROM admin WHERE admin_type = 'reseller'"
        )->fetchColumn();
    }

    return $resellersCount >= $minNbResellers;
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
        $customersCount = execute_query(
            "SELECT COUNT(admin_id) FROM admin WHERE admin_type = 'user' AND admin_status <> 'todelete'"
        )->fetchColumn();
    }

    return $customersCount >= $minNbCustomers;
}

/**
 * Whether or not system has registered admins (many), resellers or customers
 *
 * @return bool
 */
function systemHasAdminsOrResellersOrCustomers()
{
    return systemHasManyAdmins() || systemHasResellers() || systemHasCustomers();
}

/**
 * Whether or not system has registered resellers or customers
 *
 * @return bool
 */
function systemHasResellersOrCustomers()
{
    return systemHasResellers() || systemHasCustomers();
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
        $stmt = execute_query("SELECT COUNT(admin_id) FROM admin WHERE admin_type = 'admin'");
        $hasManyAdmins = $stmt->fetchColumn() > 1;
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
    $config = Registry::get('config');

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
