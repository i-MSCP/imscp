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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2017 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Deletes an admin or reseller user
 *
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @param int $userId User unique identifier
 */
function admin_deleteUser($userId)
{
    $userId = intval($userId);
    $cfg = Registry::get('config');

    /** @var iMSCP_Database $db */
    $db = Registry::get('iMSCP_Application')->getDatabase();

    $stmt = exec_query('SELECT a.admin_type, b.logo FROM admin a LEFT JOIN user_gui_props b ON (b.user_id = a.admin_id) WHERE admin_id = ?', [
        $userId
    ]);
    $row = $stmt->fetch();
    $userType = $row['admin_type'];

    if (empty($userType) || $userType == 'user') {
        showBadRequestErrorPage();
    }

    // Users (admins/resellers) common items to delete
    $itemsToDelete = [
        'admin'          => 'admin_id = ?',
        'email_tpls'     => 'owner_id = ?',
        'tickets'        => 'ticket_from = ? OR ticket_to = ?',
        'user_gui_props' => 'user_id = ?'
    ];

    if ($userType == 'reseller') {
        // Getting reseller's software packages to remove if any
        $stmt = exec_query('SELECT software_id, software_archive FROM web_software WHERE reseller_id = ?', [$userId]);
        $swPackages = $stmt->fetchAll();

        // Getting custom reseller isp logo if set
        $resellerLogo = $row['logo'];

        // Add specific reseller items to remove
        $itemsToDelete = array_merge(
            [
                'hosting_plans'  => 'reseller_id = ?',
                'reseller_props' => 'reseller_id = ?',
                'web_software'   => 'reseller_id = ?'
            ],
            $itemsToDelete
        );
    }

    // We are using transaction to ensure data consistency and prevent any garbage in
    // the database. If one query fail, the whole process is reverted.

    try {
        // Cleanup database
        $db->beginTransaction();

        Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onBeforeDeleteUser, ['userId' => $userId]);

        foreach ($itemsToDelete as $table => $where) {
            $query = "DELETE FROM " . quoteIdentifier($table) . ($where ? " WHERE $where" : '');
            exec_query($query, array_fill(0, substr_count($where, '?'), $userId));
        }

        Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAfterDeleteUser, ['userId' => $userId]);

        $db->commit();

        // Cleanup files system

        // We are safe here. We don't stop the process even if files cannot be removed. That can result in garbages but
        // the sysadmin can easily delete them through ssh.

        // Deleting reseller software installer local repository
        if (isset($swPackages) && !empty($swPackages)) {
            _admin_deleteResellerSwPackages($userId, $swPackages);
        } elseif ($userType == 'reseller' && is_dir($cfg['GUI_APS_DIR'] . '/' . $userId) && @rmdir($cfg['GUI_APS_DIR'] . '/' . $userId) == false) {
            write_log(sprintf('Could not remove reseller software directory: %s', $cfg['GUI_APS_DIR'] . '/' . $userId), E_USER_ERROR);
        }

        // Deleting user logo
        if (isset($resellerLogo) && !empty($resellerLogo)) {
            $logoPath = $cfg['GUI_ROOT_DIR'] . '/data/persistent/ispLogos/' . $resellerLogo;

            if (file_exists($logoPath) && @unlink($logoPath) == false) {
                write_log(sprintf('Could not remove user logo %s', $logoPath), E_USER_ERROR);
            }
        }

        $userTr = $userType == 'reseller' ? tr('Reseller') : tr('Admin');
        set_page_message(tr('%s account successfully deleted.', $userTr), 'success');
        write_log($_SESSION['user_logged'] . ": deletes user " . $userId, E_USER_NOTICE);
    } catch (iMSCP_Exception $e) {
        $db->rollBack();
        throw $e;
    }

    redirectTo('users.php');
}

/**
 * Delete reseller software
 *
 * @param int $userId Reseller unique identifier
 * @param array $swPackages Array that contains software package to remove
 * @return void
 */
function _admin_deleteResellerSwPackages($userId, array $swPackages)
{
    $cfg = Registry::get('config');

    // Remove all reseller's software packages if any
    foreach ($swPackages as $package) {
        $packagePath = $cfg['GUI_APS_DIR'] . '/' . $userId . '/' . $package['software_archive'] . '-' . $package['software_id'] . '.tar.gz';
        if (file_exists($packagePath) && !@unlink($packagePath)) {
            write_log('Unable to remove reseller package ' . $packagePath, E_USER_ERROR);
        }
    }

    // Remove reseller software installer local repository directory
    $resellerSwDirectory = $cfg['GUI_APS_DIR'] . '/' . $userId;
    if (is_dir($resellerSwDirectory) && @rmdir($resellerSwDirectory) == false) {
        write_log('Unable to remove reseller software repository: ' . $resellerSwDirectory, E_USER_ERROR);
    }
}

/**
 * Validates admin or reseller deletion
 *
 * @param int $userId User unique identifier
 * @return bool TRUE if deletion can be done, FALSE otherwise
 */
function admin_validateUserDeletion($userId)
{
    $stmt = exec_query('SELECT admin_type, created_by FROM admin WHERE admin_id = ?', [$userId]);
    $stmt->rowCount() or showBadRequestErrorPage(); # No user found; assume a bad request
    $row = $stmt->fetch();

    if ($row['created_by'] == 0) {
        set_page_message(tr('You cannot delete the default administrator.'), 'error');
    }

    if (!in_array($row['admin_type'], ['admin', 'reseller'])) {
        showBadRequestErrorPage(); # Not an administrator, nor a reseller; assume a bad request
    }

    $stmt = exec_query('SELECT COUNT(admin_id) AS user_count FROM admin WHERE created_by = ?', [$userId]);
    $row2 = $stmt->fetch();

    if ($row2['user_count'] > 0) {
        if ($row['admin_type'] == 'admin') {
            set_page_message(tr('Prior to removing this administrator, please move his resellers to another administrator.'), 'error');
        } else {
            set_page_message(tr('You cannot delete a reseller that has customer accounts.'), 'error');
        }

        return false;
    }

    return true;
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptStart);

if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) { # admin/reseller deletion
    if (admin_validateUserDeletion($_GET['delete_id'])) {
        admin_deleteUser($_GET['delete_id']);
    }
} elseif (isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);

    try {
        if (!deleteCustomer($userId)) {
            showBadRequestErrorPage();
        }

        set_page_message(tr('Customer account successfully scheduled for deletion.'), 'success');
        write_log(sprintf('%s scheduled deletion of the customer account with ID %d', $_SESSION['user_logged'], $userId), E_USER_NOTICE);
    } catch (iMSCP_Exception $e) {
        if (($previous = $e->getPrevious()) && $previous instanceof iMSCP_Exception_Database) {
            $queryMsgPart = ' Query was: ' . $previous->getQuery();
        } elseif ($e instanceof iMSCP_Exception_Database) {
            $queryMsgPart = ' Query was: ' . $e->getQuery();
        } else {
            $queryMsgPart = '';
        }

        set_page_message(tr('Unable to schedule deletion of the customer account. Please consult admin logs or your mail for more information.'), 'error');
        write_log(
            sprintf("System was unable to schedule deletion of customer account with ID %s. Message was: %s.", $userId, $e->getMessage() . $queryMsgPart),
            E_USER_ERROR
        );
    }
}

redirectTo('users.php');
