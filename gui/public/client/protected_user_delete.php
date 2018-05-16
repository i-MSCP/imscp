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

require_once 'imscp-lib.php';

check_login('user');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

if (!customerHasFeature('protected_areas') || !isset($_GET['uname'])) {
    showBadRequestErrorPage();
}

try {
    iMSCP_Database::getInstance()->beginTransaction();

    $htuserId = intval($_GET['uname']);
    $domainId = get_user_domain_id($_SESSION['user_id']);

    $stmt = exec_query('SELECT uname FROM htaccess_users WHERE dmn_id = ? AND id = ?', [$domainId, $htuserId]);

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow();
    $htuserName = $row['uname'];

    // Remove the user from any group for which it is member and schedule .htgroup file change
    $stmt = exec_query('SELECT id, members FROM htaccess_groups WHERE dmn_id = ?', $domainId);

    while ($row = $stmt->fetchRow()) {
        $htuserList = explode(',', $row['members']);
        $candidate = array_search($row['id'], $htuserList);

        if ($candidate === false)
            continue;

        unset($htuserList[$candidate]);

        exec_query('UPDATE htaccess_groups SET members = ?, status = ? WHERE id = ?', [
            implode(',', $htuserList), 'tochange', $row['id']
        ]);
    }

    // Schedule deletion or update of any .htaccess files in which the htuser was used
    $stmt = exec_query('SELECT * FROM htaccess WHERE dmn_id = ?', $domainId);

    while ($row = $stmt->fetchRow()) {
        $htuserList = explode(',', $row['user_id']);
        $candidate = array_search($htuserId, $htuserList);

        if ($candidate == false)
            continue;

        unset($htuserList[$candidate]);

        if (empty($htuserList)) {
            $status = 'todelete';
        } else {
            $htuserList = implode(',', $htuserList);
            $status = 'tochange';
        }

        exec_query('UPDATE htaccess SET user_id = ?, status = ? WHERE id = ?', [$htuserList, $status, $row['id']]);
    }

    // Schedule htuser deletion
    $stmt = exec_query('UPDATE htaccess_users SET status = ? WHERE id = ? AND dmn_id = ?', [
        'todelete', $htuserId, $domainId
    ]);

    iMSCP_Database::getInstance()->commit();

    set_page_message(tr('User scheduled for deletion.'), 'success');
    send_request();
    write_log(sprintf('%s deletes user ID (protected areas): %s', $_SESSION['user_logged'], $htuserName), E_USER_NOTICE);
} catch (iMSCP_Exception_Database $e) {
    iMSCP_Database::getInstance()->rollBack();
    set_page_message(tr('An unexpected error occurred. Please contact your reseller.'), 'error');
    write_log(sprintf('Could not delete htaccess user: %s', $e->getMessage()));
}

redirectTo('protected_user_manage.php');
