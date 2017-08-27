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

require 'imscp-lib.php';

check_login('reseller');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

if (!resellerHasFeature('aps') || !isset($_GET['id'])) {
    showBadRequestErrorPage();
}

$softwareId = intval($_GET['id']);
$stmt = exec_query(
    '
        SELECT software_id, software_archive, software_depot
        FROM web_software
        WHERE software_id = ?
        AND reseller_id = ?
    ',
    [$softwareId, $_SESSION['user_id']]
);

if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

if ($stmt->fields['software_depot'] == 'no') {
    $del_path = iMSCP_Registry::get('config')['GUI_APS_DIR'] . '/' . $_SESSION['user_id'] . '/'
        . $rs->fields['software_archive'] . '-' . $rs->fields['software_id'] . '.tar.gz';
    @unlink($del_path);
}

exec_query('UPDATE web_software_inst SET software_res_del = 1 WHERE software_id = ?', $rs->fields['software_id']);
exec_query('DELETE FROM web_software WHERE software_id = ? AND reseller_id = ?', [$softwareId, $_SESSION['user_id']]);
set_page_message(tr('Software successfully scheduled for deletion.'), 'success');
redirectTo('software_upload.php');
