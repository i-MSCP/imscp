<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by i-MSCP Team
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

require_once 'imscp-lib.php';

check_login('user');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

if (!customerHasFeature('domain_aliases') || !isset($_GET['del_id'])) {
    showBadRequestErrorPage();
}

$stmt = exec_query('DELETE FROM domain_aliasses WHERE alias_id = ? AND domain_id = ? AND alias_status = ?', [
    intval($_GET['del_id']), get_user_domain_id($_SESSION['user_id']), 'ordered'
]);

if ($stmt->rowCount()) {
    set_page_message(tr('Order successfully deleted.'), 'success');
    redirectTo('domains_manage.php');
}

showBadRequestErrorPage();
