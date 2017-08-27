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

check_login('admin');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

if (!isset($_REQUEST['id'])) {
    showBadRequestErrorPage();
}

$softwareId = intval($_REQUEST['id']);

if (isset($_POST['change']) && $_POST['change'] == 'add') {
    $resellerId = clean_input($_POST['selected_reseller']);
    $rs = exec_query('SELECT * FROM web_software WHERE software_id = ?', $softwareId);

    if ($resellerId == "all") {
        $rs2 = execute_query("SELECT reseller_id FROM reseller_props WHERE software_allowed = 'yes' AND softwaredepot_allowed = 'yes'");

        if ($rs2->rowCount()) {
            while (!$rs2->EOF) {
                $query3 = "SELECT reseller_id FROM web_software WHERE reseller_id = ? AND software_master_id = ?";
                $rs3 = exec_query($query3, [$rs2->fields['reseller_id'], $softwareId]);

                if (!$rs3->rowCount()) {
                    exec_query(
                        "
                            INSERT INTO web_software (
                            software_master_id, reseller_id, software_name, software_version, software_language, software_type,
                            software_db, software_archive, software_installfile, software_prefix, software_link, software_desc,
                            software_active, software_status, rights_add_by, software_depot
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                        )
                        ",
                        [
                            $softwareId, $rs2->fields['reseller_id'], $rs->fields['software_name'],
                            $rs->fields['software_version'], $rs->fields['software_language'], $rs->fields['software_type'],
                            $rs->fields['software_db'], $rs->fields['software_archive'], $rs->fields['software_installfile'],
                            $rs->fields['software_prefix'], $rs->fields['software_link'], $rs->fields['software_desc'],
                            $rs->fields['software_active'], "ok", $_SESSION['user_id'], "yes"
                        ]
                    );
                    /** @var $db iMSCP_Database */
                    $db = iMSCP_Registry::get('db');
                    $sw_id = $db->insertId();
                    update_existing_client_installations_sw_depot($sw_id, $softwareId, $rs2->fields['reseller_id']);
                }
                $rs2->MoveNext();
            }
        } else {
            set_page_message(tr('No resellers found.'), 'error');
            redirectTo('software_rights.php?id=' . $softwareId);
        }
    } else {
        exec_query(
            "
                INSERT INTO web_software (
                    software_master_id, reseller_id, software_name, software_version, software_language, software_type,
                    software_db, software_archive, software_installfile, software_prefix, software_link, software_desc,
                    software_active, software_status, rights_add_by, software_depot
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ",
            [
                $softwareId, $resellerId, $rs->fields['software_name'], $rs->fields['software_version'],
                $rs->fields['software_language'], $rs->fields['software_type'], $rs->fields['software_db'],
                $rs->fields['software_archive'], $rs->fields['software_installfile'], $rs->fields['software_prefix'],
                $rs->fields['software_link'], $rs->fields['software_desc'], $rs->fields['software_active'],
                "ok", $_SESSION['user_id'], "yes"
            ]
        );

        update_existing_client_installations_sw_depot(
            iMSCP_Database::getInstance()->insertId(), $softwareId, $resellerId
        );
    }

    set_page_message(tr('Rights successfully added.'), 'success');
    redirectTo('software_rights.php?id=' . $softwareId);
} else {
    exec_query('DELETE FROM web_software WHERE software_master_id = ? AND reseller_id = ?', [
        $softwareId, intval($_GET['reseller_id'])
    ]);
    exec_query('UPDATE web_software_inst SET software_res_del = 1 WHERE software_master_id = ?', $softwareId);
    set_page_message(tr('Rights successfully removed.'), 'success');
    redirectTo('software_rights.php?id=' . $softwareId);
}
