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

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('reseller');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

if (!resellerHasFeature('domain_aliases') || !isset($_GET['id'])) {
    showBadRequestErrorPage();
}

$id = intval($_GET['id']);

$stmt = exec_query(
    '
        SELECT t1.domain_id, t2.domain_admin_id, t1.alias_name, t1.alias_mount
        FROM domain_aliasses AS t1
        JOIN domain AS t2 USING (domain_id)
        JOIN admin AS t3 ON(t3.admin_id = t2.domain_admin_id)
        WHERE t1.alias_id = ?
        AND t3.created_by = ?
    ',
    [$id, $_SESSION['user_id']]
);

if ($stmt->rowCount()) {
    $row = $stmt->fetchRow();
    deleteDomainAlias($row['domain_admin_id'], $row['domain_id'], $id, $row['alias_name'], $row['alias_mount']);
    redirectTo('alias.php');
}

showBadRequestErrorPage();
