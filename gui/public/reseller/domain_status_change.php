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

use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;

require 'imscp-lib.php';

check_login('reseller');
EventAggregator::getInstance()->dispatch(Events::onResellerScriptStart);

if (isset($_GET['domain_id'])) {
    $domainId = intval($_GET['domain_id']);
    $resellerId = intval($_SESSION['user_id']);

    $stmt = exec_query(
        '
            SELECT admin_id, created_by, domain_status
            FROM domain
            JOIN admin ON(admin_id = domain_admin_id)
            WHERE domain_id = ?
            AND created_by = ?
        ',
        [$domainId, $resellerId]
    );

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        if ($row['domain_status'] == 'ok') {
            change_domain_status($row['admin_id'], 'deactivate');
        } elseif ($row['domain_status'] == 'disabled') {
            change_domain_status($row['admin_id'], 'activate');
        } else {
            showBadRequestErrorPage();
        }

        redirectTo('users.php');
    }
}

showBadRequestErrorPage();
