<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP Team
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

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);
check_login('reseller');

if (!resellerHasFeature('domain_aliases') || !isset($_GET['id'])) {
    showBadRequestErrorPage();
}

$îd = intval($_GET['id']);
$stmt = exec_query(
    '
        SELECT alias_name
        FROM domain_aliasses INNER JOIN domain USING (domain_id) INNER JOIN admin ON(admin_id = domain_admin_id)
        WHERE alias_id = ? AND created_by = ?
    ',
    array($îd, $_SESSION['user_id'])
);

if ($stmt->rowCount()) {
    $row = $stmt->fetchRow();
    deleteDomainAlias($îd, $row['alias_name']);
    redirectTo('alias.php');
}

showBadRequestErrorPage();
