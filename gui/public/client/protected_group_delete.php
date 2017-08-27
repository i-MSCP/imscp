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

if (!customerHasFeature('protected_areas') || !isset($_GET['gname'])) {
    showBadRequestErrorPage();
}

try {
    iMSCP_Database::getInstance()->beginTransaction();

    $htgroupId = intval($_GET['gname']);
    $domainId = get_user_domain_id($_SESSION['user_id']);

    // Schedule deletion or update of any .htaccess files in which the htgroup was used
    $stmt = exec_query('SELECT * FROM htaccess WHERE dmn_id = ?', $domainId);

    while ($row = $stmt->fetchRow()) {
        $htgroupList = explode(',', $row['group_id']);
        $candidate = array_search($htgroupId, $htgroupList);

        if ($candidate === false)
            continue;

        unset($htgroupList[$candidate]);

        if (empty($htgroupList)) {
            $status = 'todelete';
        } else {
            $htgroupList = implode(',', $htgroupList);
            $status = 'tochange';
        }

        exec_query('UPDATE htaccess SET group_id = ?, status = ? WHERE id = ?', [
            $htgroupList, $status, $row['id']
        ]);
    }

    // Schedule htgroup deletion
    exec_query('UPDATE htaccess_groups SET status = ? WHERE id = ? AND dmn_id = ?', [
        'todelete', $htgroupId, $domainId
    ]);

    iMSCP_Database::getInstance()->commit();

    set_page_message(tr('Htaccess group successfully scheduled for deletion.'), 'success');
    send_request();
    write_log(sprintf('%s deleted Htaccess group ID: %s', $_SESSION['user_logged'], $htgroupId), E_USER_NOTICE);
} catch (iMSCP_Exception_Database $e) {
    iMSCP_Database::getInstance()->rollBack();
    set_page_message(tr('An unexpected error occurred. Please contact your reseller.'), 'error');
    write_log(sprintf('Could not delete htaccess group: %s', $e->getMessage()));
}

redirectTo('protected_user_manage.php');
