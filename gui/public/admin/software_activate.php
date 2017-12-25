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

require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptStart);

isset($_GET['id']) or showBadRequestErrorPage();

$softwareId = intval($_GET['id']);
$stmt = exec_query(
    'SELECT software_id, software_archive, reseller_id FROM web_software WHERE software_id = ? AND software_active = 0',
    [intval($softwareId)]
);

$stmt->rowCount() or showBadRequestErrorPage();

exec_query('UPDATE web_software SET software_active = 1 WHERE software_id = ?', [$softwareId]);

$stmt = exec_query(
    '
        SELECT software_id, software_name, software_version, software_language, reseller_id, software_archive
        FROM web_software
        WHERE software_id = ?
    ',
    [$softwareId]
);

$row = $stmt->fetch();

send_activated_sw($row['reseller_id'], $row['software_archive'] . '.tar.gz', $row['software_id']);
set_page_message(tr('Software was activated.'), 'success');
redirectTo('software_manage.php');
