<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);

if (!customerHasFeature('domain_aliases') || !isset($_GET['id'])) {
    showBadRequestErrorPage();
}

$id = intval($_GET['id']);

$stmt = exec_query(
    '
        SELECT t1.domain_id, t1.alias_name, t1.alias_mount
        FROM domain_aliasses AS t1
        JOIN domain AS t2 USING(domain_id)
        WHERE t1.alias_id = ?
        AND t2.domain_admin_id = ?
    ',
    [$id, $_SESSION['user_id']]
);

if ($stmt->rowCount()) {
    $row = $stmt->fetch();
    deleteDomainAlias($_SESSION['user_id'], $row['domain_id'], $id, $row['alias_name'], $row['alias_mount']);
    redirectTo('domains_manage.php');
}

showBadRequestErrorPage();
