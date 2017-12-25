<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by i-MSCP Team
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

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('aps') && isset($_GET['id']) or showBadRequestErrorPage();

$softwareId = intval($_GET['id']);
$domainProps = get_domain_default_props($_SESSION['user_id']);
$stmt = exec_query('SELECT software_res_del FROM web_software_inst WHERE software_id = ? AND domain_id = ?', [
    $softwareId, $domainProps['domain_id']
]);

if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

if ($stmt->fetchColumn() == 1) {
    exec_query('DELETE FROM web_software_inst WHERE software_id = ? AND domain_id = ?', [
        $softwareId, $domainProps['domain_id']
    ]);
    set_page_message(tr('Software deleted.'), 'success');
    redirectTo('software.php');
}

exec_query("UPDATE web_software_inst SET software_status = 'todelete' WHERE software_id = ? AND domain_id = ?", [
    $softwareId, $domainProps['domain_id']
]);
send_request();
set_page_message(tr('Software successfully scheduled for deletion.'), 'success');
redirectTo('software.php');
