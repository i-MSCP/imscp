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

use iMSCP_Database as Database;
use iMSCP_Exception_Database as DatabaseException;

require 'imscp-lib.php';

check_login('admin');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

isset($_GET['id']) or showBadRequestErrorPage();

$softwareId = intval($_GET['id']);
$stmt = exec_query('SELECT * FROM web_software WHERE software_id = ?', $softwareId);

if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

$row = $stmt->fetch();
$cfg = iMSCP_Registry::get('config');
$srcFile = $cfg['GUI_APS_DIR'] . '/' . $row['reseller_id'] . '/' . $row['software_archive'] . '-' . $row['software_id'] . '.tar.gz';
$destFile = $cfg['GUI_APS_DEPOT_DIR'] . '/' . $row['software_archive'] . '-' . $row['software_id'] . '.tar.gz';

@copy($srcFile, $destFile);
@unlink($srcFile);

$db = Database::getInstance();

try {
    $db->beginTransaction();
    exec_query(
        "UPDATE web_software SET reseller_id = ?, software_active = 1, software_depot = 'yes' WHERE software_id = ?", [
        $_SESSION['user_id'], $softwareId
    ]);
    exec_query(
        "
            INSERT INTO web_software (
                software_master_id, reseller_id, software_name, software_version, software_language, software_type,
                software_db, software_archive, software_installfile, software_prefix, software_link, software_desc,
                software_active, software_status, rights_add_by, software_depot
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'ok', ?, 'yes'
            )
        ",
        [
            $row['software_id'], $row['reseller_id'], $row['software_name'], $row['software_version'],
            $row['software_language'], $row['software_type'], $row['software_db'], $row['software_archive'],
            $row['software_installfile'], $row['software_prefix'], $row['software_link'], $row['software_desc'],
            $_SESSION['user_id']
        ]
    );
    update_existing_client_installations_res_upload($db->insertId(), $row['reseller_id'], $row['software_id']);
    $db->commit();
    set_page_message(tr('Software has been successfully imported.'), 'success');
    redirectTo('software_manage.php');
} catch (DatabaseException $e) {
    $db->rollBack();
    throw $e;
}
