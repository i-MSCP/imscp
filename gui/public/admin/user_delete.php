<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

use iMSCP\Database\DatabaseException;
use iMSCP\Database\DatabaseMySQL;
use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\Exception\Exception;
use iMSCP\Registry;

/**
 * Deletes an admin or reseller user
 *
 * @param int $userId User unique identifier
 */
function admin_deleteUser($userId)
{
    $userId = intval($userId);
    $cfg = Registry::get('config');
    $db = DatabaseMySQL::getInstance();
    $stmt = exec_query(
        '
            SELECT a.admin_type, b.logo FROM admin a LEFT JOIN user_gui_props b ON (b.user_id = a.admin_id)
            WHERE admin_id = ?
        ',
        $userId
    );
    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
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

    // Note: Admin can also have they own hosting_plans bug must not be considered
    // as common item since first admin must be never removed
    if ($userType == 'reseller') {
        // Getting custom reseller isp logo if set
        $resellerLogo = $row['logo'];

        // Add specific reseller items to remove
        $itemsToDelete = array_merge(
            [
                'hosting_plans'  => 'reseller_id = ?',
                'reseller_props' => 'reseller_id = ?'
            ],
            $itemsToDelete
        );
    }

    // We are using transaction to ensure data consistency and prevent any garbage in
    // the database. If one query fail, the whole process is reverted.

    try {
        // Cleanup database
        $db->beginTransaction();

        EventAggregator::getInstance()->dispatch(Events::onBeforeDeleteUser, ['userId' => $userId]);

        foreach ($itemsToDelete as $table => $where) {
            $query = "DELETE FROM " . quoteIdentifier($table) . ($where ? " WHERE $where" : '');
            exec_query($query, array_fill(0, substr_count($where, '?'), $userId));
        }

        EventAggregator::getInstance()->dispatch(Events::onAfterDeleteUser, ['userId' => $userId]);

        $db->commit();

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
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    redirectTo('users.php');
}

/**
 * Validates admin or reseller deletion
 *
 * @param int $userId User unique identifier
 * @return bool TRUE if deletion can be done, FALSE otherwise
 */
function admin_validateUserDeletion($userId)
{
    $stmt = exec_query('SELECT admin_type, created_by FROM admin WHERE admin_id = ?', $userId);
    if (!$stmt->rowCount()) {
        showBadRequestErrorPage(); # No user found; assume a bad request
    }

    $row = $stmt->fetchRow();

    if ($row['created_by'] == 0) {
        set_page_message(tr('You cannot delete the default administrator.'), 'error');
    }

    if (!in_array($row['admin_type'], ['admin', 'reseller'])) {
        showBadRequestErrorPage(); # Not an administrator, nor a reseller; assume a bad request
    }

    $stmt = exec_query('SELECT COUNT(admin_id) AS user_count FROM admin WHERE created_by = ?', $userId);
    $row2 = $stmt->fetchRow();

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

require 'imscp-lib.php';

check_login('admin');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptStart);

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
    } catch (Exception $e) {
        if (($previous = $e->getPrevious()) && $previous instanceof DatabaseException) {
            $queryMessagePart = ' Query was: ' . $previous->getQuery();
        } elseif ($e instanceof DatabaseException) {
            $queryMessagePart = ' Query was: ' . $e->getQuery();
        } else {
            $queryMessagePart = '';
        }

        set_page_message(tr('Unable to schedule deletion of the customer account. Please consult admin logs or your mail for more information.'), 'error');
        write_log(
            sprintf(
                "System was unable to schedule deletion of customer account with ID %s. Message was: %s.",
                $userId, $e->getMessage() . $queryMessagePart
            ),
            E_USER_ERROR
        );
    }
}

redirectTo('users.php');
