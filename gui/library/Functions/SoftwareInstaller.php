<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by i-MSCP team
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
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * These functions are used by admin
 */

/**
 * Must be documented
 *
 * @param int $softwareId Software unique identifier
 * @param int $resellerId Reseller unique identifier
 * @param int $softwareMasterId
 * @param bool $softwareDeleted
 * @return void
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function update_existing_client_installations_res_upload(
    $softwareId, $resellerId, $softwareMasterId, $softwareDeleted = false)
{
    $stmt = exec_query(
        "
          SELECT domain_id
          FROM domain
          JOIN admin ON(admin_id = domain_admin_id)
          WHERE domain_software_allowed = 'yes'
          AND created_by = ?
        ",
        $resellerId
    );

    if (!$stmt->rowCount()) {
        return;
    }

    while ($row = $stmt->fetchRow()) {
        if ($softwareDeleted === false) {
            exec_query(
                '
                  UPDATE web_software_inst
                  SET software_id = ?, software_master_id = ?, software_depot = ?
                  WHERE software_id = ?
                  AND domain_id = ?
                ',
                [$softwareId, $softwareMasterId, 'yes', $softwareMasterId, $row['domain_id']]
            );
            continue;
        }

        exec_query(
            'UPDATE web_software_inst SET software_res_del = 1 WHERE software_id = ? AND domain_id = ?',
            [$softwareId, $row['domain_id']]
        );
    }
}

/**
 * Must be documented
 *
 * @param int $softwareId Software unique identifier
 * @param int $softwareMasterId
 * @param int $resellerId
 * @return void
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function update_existing_client_installations_sw_depot($softwareId, $softwareMasterId, $resellerId)
{
    $stmt = exec_query(
        "
          SELECT domain_id
          FROM domain
          JOIN admin ON(admin_id = domain_admin_id)
          WHERE domain_software_allowed = 'yes'
          AND created_by = ?
        ",
        $resellerId
    );

    if (!$stmt->rowCount()) {
        return;
    }

    while ($row = $stmt->fetchRow()) {
        exec_query(
            '
              UPDATE web_software_inst
              SET software_id = ?, software_res_del = 0
              WHERE software_master_id = ?
              AND software_res_del = 1
              AND domain_id = ?
            ',
            [$softwareId, $softwareMasterId, $row['domain_id']]
        );
    }
}

/**
 * Send software activation notification
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $softwarePackage Software package
 * @param int $softwareId Software unique identifier
 * @return bool TRUE on success, FALSE on failure
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @throws Zend_Exception
 */
function send_activated_sw($resellerId, $softwarePackage, $softwareId)
{
    $stmt = exec_query('SELECT admin_name, created_by, fname, lname, email FROM admin WHERE admin_id = ?', $resellerId);
    $resellerData = $stmt->fetchRow();
    $ret = send_mail([
        'mail_id'      => 'software-installer-activate-sw',
        'fname'        => $resellerData['fname'],
        'lname'        => $resellerData['lname'],
        'username'     => $resellerData['admin_name'],
        'email'        => $resellerData['email'],
        'subject'      => tr('Your software package has been activated'),
        'message'      => tr('Dear {NAME},

Your software package has been successfully activated.

Software package: {SOFTWARE_PACKAGE}
Software ID: {SOFTWARE_ID}

Please do not reply to this email.

___________________________
i-MSCP Mailer.'),
        'placeholders' => [
            '{SOFTWARE_PACKAGE}' => $softwarePackage,
            '{SOFTWARE_ID}'      => $softwareId
        ]
    ]);

    if (!$ret) {
        write_log(sprintf("Couldn'tsend software activation notification to %s", $resellerData['admin_name']), E_USER_ERROR);
        return false;
    }

    return true;
}

/**
 * Send software deletion notification
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $softwarePackage Software package
 * @param int $softwareId
 * @param string $adminMessage
 * @return bool TRUE on success, FALSE on failure
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @throws Zend_Exception
 */
function send_deleted_sw($resellerId, $softwarePackage, $softwareId, $adminMessage)
{
    $stmt = exec_query('SELECT admin_name, created_by, fname, lname, email FROM admin WHERE admin_id = ?', $resellerId);
    $resellerData = $stmt->fetchRow();
    $ret = send_mail([
        'mail_id'      => 'software-installer-delete-sw',
        'fname'        => $resellerData['fname'],
        'lname'        => $resellerData['lname'],
        'username'     => $resellerData['admin_name'],
        'email'        => $resellerData['email'],
        'subject'      => tr('Your software package has been deleted'),
        'message'      => tr('Dear {NAME},

Your software package has been deleted.

Software package: {SOFTWARE_PACKAGE}
Software ID: {SOFTWARE_ID}

Message from your administrator:

==========================================================================
{ADMIN_MESSAGE}
==========================================================================

Please do not reply to this email.

___________________________
i-MSCP Mailer'),
        'placeholders' => [
            '{SOFTWARE_PACKAGE}' => $softwarePackage,
            '{SOFTWARE_ID}'      => $softwareId,
            '{ADMIN_MESSAGE}'    => $adminMessage
        ]
    ]);

    if (!$ret) {
        write_log(sprintf("Couldn't send software activation notification to %s", $resellerData['admin_name']), E_USER_ERROR);
        return false;
    }

    return true;
}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return int
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function get_avail_software($tpl)
{
    $stmt = execute_query(
        "
          SELECT a.software_id AS id, a.software_name AS name, a.software_version AS version,
            a.software_language AS language, a.software_type AS type, a.software_desc AS description,
            a.reseller_id, b.admin_id, b.admin_name AS reseller
          FROM web_software a, admin b
          WHERE a.software_active = 0 AND a.reseller_id = b.admin_id AND a.software_depot = 'no'
          ORDER BY a.reseller_id ASC, a.software_type ASC, a.software_name ASC
        "
    );

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetchRow()) {
            $importURL = "software_import.php?id=" . $row['id'];
            $actURL = "software_activate.php?id=" . $row['id'];
            $delURL = "software_delete.php?id=" . $row['id'];
            $downloadURL = "software_download.php?id=" . $row['id'];
            $tpl->assign([
                'TR_NAME'       => $row['name'],
                'TR_TOOLTIP'    => $row['description'],
                'TR_VERSION'    => $row['version'],
                'TR_LANGUAGE'   => $row['language'],
                'TR_TYPE'       => $row['type'],
                'TR_RESELLER'   => $row['reseller'],
                'DOWNLOAD_LINK' => $downloadURL,
                'TR_DOWNLOAD'   => tr('Download'),
                'ACTIVATE_LINK' => $actURL,
                'IMPORT_LINK'   => $importURL,
                'TR_ACTIVATION' => tr('Activate'),
                'TR_IMPORT'     => tr('Import'),
                'DELETE_LINK'   => $delURL,
                'TR_DELETE'     => tr('Delete')
            ]);
            $tpl->parse('LIST_SOFTWARE', '.list_software');
        }

        $tpl->assign('NO_SOFTWARE_LIST', '');
        return $stmt->rowCount();
    }

    $tpl->assign(['NO_SOFTWARE' => tr('No software is waiting for activation')]);
    $tpl->parse('NO_SOFTWARE_LIST', '.no_software_list');
    $tpl->assign('LIST_SOFTWARE', '');
    return 0;
}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl template engine
 * @return int
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function get_avail_softwaredepot($tpl)
{
    $stmt = execute_query(
        "
          SELECT a.software_id AS id, a.software_name AS name, a.software_version AS version,
            a.software_language AS language, a.software_type AS type, a.software_desc AS description,
            a.reseller_id, a.software_archive AS filename, a.software_status AS swstatus, b.admin_id,
            b.admin_name AS admin
          FROM web_software a, admin b
          WHERE a.software_depot = 'yes' AND a.software_master_id = 0 AND a.reseller_id = b.admin_id
          ORDER BY a.reseller_id ASC, a.software_type ASC, a.software_name ASC
        "
    );

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetchRow()) {
            if ($row['swstatus'] == 'ok' || $row['swstatus'] == 'ready') {
                if ($row['swstatus'] == 'ready') {
                    exec_query("UPDATE web_software SET software_status = 'ok' WHERE software_id = ?", $row['id']);
                    set_page_message(tr('Package installed successfully!'), 'success');
                }

                $delURL = 'software_delete.php?id=' . $row['id'];
                $downloadURL = 'software_download.php?id=' . $row['id'];
                $permsURL = 'software_rights.php?id=' . $row['id'];
                $tpl->assign([
                    'TR_NAME'              => $row['name'],
                    'LINK_COLOR'           => '#000000',
                    'TR_TOOLTIP'           => $row['description'],
                    'TR_VERSION'           => $row['version'],
                    'TR_LANGUAGE'          => $row['language'],
                    'TR_TYPE'              => $row['type'],
                    'TR_ADMIN'             => $row['admin'],
                    'DOWNLOAD_LINK'        => $downloadURL,
                    'TR_DOWNLOAD'          => tr('Download'),
                    'DELETE_LINK'          => $delURL,
                    'TR_DELETE'            => tr('Delete'),
                    'SOFTWARE_ICON'        => 'edit',
                    'SOFTWARE_RIGHTS_LINK' => $permsURL,
                    'RIGHTS_LINK'          => tr('Rights'),
                    'TR_SOFTWARE_RIGHTS'   => tr('Permissions')
                ]);
            } else {
                if ($row['swstatus'] == 'toadd') {
                    $delURL = 'software_delete.php?id=' . $row['id'];
                    $tpl->assign([
                        'TR_NAME'              => tr('Installing your uploaded package. Please refresh this page.'),
                        'LINK_COLOR'           => '#FF0000',
                        'TR_VERSION'           => '',
                        'TR_LANGUAGE'          => '',
                        'TR_TOOLTIP'           => tr('The package will be installed automatically to your system after upload.<br>Refresh your site to see the new status!'),
                        'TR_DOWNLOAD'          => '',
                        'DOWNLOAD_LINK'        => '',
                        'DELETE_LINK'          => $delURL,
                        'TR_DELETE'            => tr('Delete'),
                        'TR_TYPE'              => '<span  style="color:#FF0000">' . tr('installing') . '</span>',
                        'TR_ADMIN'             => $row['admin'],
                        'SOFTWARE_ICON'        => 'disabled',
                        'RIGHTS_LINK'          => '',
                        'TR_SOFTWARE_RIGHTS'   => '',
                        'SOFTWARE_RIGHTS_LINK' => ''
                    ]);
                } else {
                    if ($row['swstatus'] == 'todelete') {
                        $tpl->assign([
                            'TR_NAME'              => tr('Failure in the package. Deleting!'),
                            'LINK_COLOR'           => '#FF0000',
                            'TR_VERSION'           => '',
                            'TR_LANGUAGE'          => '',
                            'TR_TOOLTIP'           => tr('There is an Error inside your package. Please check it!<br>Refresh your site to see the new status!'),
                            'TR_DOWNLOAD'          => '',
                            'DOWNLOAD_LINK'        => '',
                            'DELETE_LINK'          => '',
                            'TR_DELETE'            => '',
                            'TR_TYPE'              => '<span  style="color:#FF0000">' . tr('deleting') . '</span>',
                            'TR_ADMIN'             => $row['admin'],
                            'SOFTWARE_ICON'        => 'delete',
                            'RIGHTS_LINK'          => '',
                            'TR_SOFTWARE_RIGHTS'   => '',
                            'SOFTWARE_RIGHTS_LINK' => ''
                        ]);
                        set_page_message(tr('Your package is corrupt. Please correct it!'), 'error');
                    } elseif (preg_match('/double_depot_/i', $row['swstatus'])) {
                        $tpl->assign([
                            'TR_NAME'              => tr('Package already exists in the software depot!'),
                            'LINK_COLOR'           => '#FF0000',
                            'TR_VERSION'           => '',
                            'TR_LANGUAGE'          => '',
                            'TR_TOOLTIP'           => tr('Check your software depot uploads!<br>It is not allowed to upload this package two times.<br>Refresh your site to see the new status!'),
                            'TR_DOWNLOAD'          => '',
                            'DOWNLOAD_LINK'        => '',
                            'DELETE_LINK'          => '',
                            'TR_DELETE'            => '',
                            'TR_TYPE'              => '<span style="color:#FF0000">' . tr('deleting') . '</span>',
                            'TR_ADMIN'             => $row['admin'],
                            'SOFTWARE_ICON'        => 'delete',
                            'RIGHTS_LINK'          => '',
                            'TR_SOFTWARE_RIGHTS'   => '',
                            'SOFTWARE_RIGHTS_LINK' => ''
                        ]);
                        set_page_message(tr('This package already exists in the software depot!'), 'warning');
                    } elseif (preg_match('/double_res_/i', $row['swstatus'])) {
                        $tpl->assign([
                            'TR_NAME'              => tr('Package already exists in the reseller depot!'),
                            'LINK_COLOR'           => '#FF0000',
                            'TR_VERSION'           => '',
                            'TR_LANGUAGE'          => '',
                            'TR_TOOLTIP'           => tr('Check the reseller uploads!<br>It is not allowed to upload this package two times.<br>Refresh your site to see the new status!'),
                            'TR_DOWNLOAD'          => '',
                            'DOWNLOAD_LINK'        => '',
                            'DELETE_LINK'          => '',
                            'TR_DELETE'            => '',
                            'TR_TYPE'              => '<span style="color:#FF0000">' . tr('deleting') . '</span>',
                            'TR_ADMIN'             => $row['admin'],
                            'SOFTWARE_ICON'        => 'delete',
                            'RIGHTS_LINK'          => '',
                            'TR_SOFTWARE_RIGHTS'   => '',
                            'SOFTWARE_RIGHTS_LINK' => ''
                        ]);
                        $stmt2 = exec_query(
                            "
                              SELECT t1.admin_name
                              FROM admin AS t1
                              JOIN web_software AS t2 ON(t2.reseller_id = t1.admin_id)
                              WHERE t2.software_id = ?
                            ",
                            substr(strrchr($row['swstatus'], '_'), 1)
                        );
                        if ($stmt2->rowCount()) {
                            set_page_message(
                                tr(
                                    'This package already exists in the depot of the reseller "%1$s"!',
                                    $stmt2->fetchRow(PDO::FETCH_COLUMN)
                                ),
                                'warning'
                            );
                        }
                    }

                    $cfg = Registry::get('config');
                    @unlink(utils_normalizePath($cfg['GUI_APS_DEPOT_DIR'] . '/' . $row['filename'] . '-' . $row['id'] . '.tar.gz'));
                    exec_query('DELETE FROM web_software WHERE software_id = ?', $row['id']);
                }
            }

            $tpl->parse('LIST_SOFTWAREDEPOT', '.list_softwaredepot');
        }

        $tpl->assign('NO_SOFTWAREDEPOT_LIST', '');
        return $stmt->rowCount();
    }

    $tpl->assign('NO_SOFTWAREDEPOT', tr('No software in software depot available!'));
    $tpl->parse('NO_SOFTWAREDEPOT_LIST', '.no_softwaredepot_list');
    $tpl->assign('LIST_SOFTWAREDEPOT', '');
    return 0;
}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $resellerId
 * @return int
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function get_installed_res_software($tpl, $resellerId)
{
    $stmt = exec_query(
        "
          SELECT a.software_id AS id, a.software_name AS name, a.software_version AS version,
            a.software_language AS language, a.software_type AS type, a.software_desc AS description,
            a.reseller_id, a.software_archive AS filename, a.software_status AS swstatus, a.software_depot AS swdepot,
            b.admin_id, b.admin_name AS admin
          FROM web_software AS a, admin AS b
          WHERE a.reseller_id = b.admin_id
          AND a.reseller_id = ?
          AND a.software_status = 'ok'
          ORDER BY a.software_type ASC, a.software_name ASC
        ",
        $resellerId
    );

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetchRow()) {
            $stmt2 = exec_query(
                "
                  SELECT domain.domain_id AS did, domain.domain_name AS domain, web_software_inst.domain_id AS wdid,
                    web_software_inst.software_id AS sid, web_software.software_id AS wsid
                  FROM domain, web_software, web_software_inst
                  WHERE web_software_inst.software_id= ?
                  AND web_software.software_id = web_software_inst.software_id
                  AND domain.domain_id = web_software_inst.domain_id
                ",
                $row['id']
            );

            if ($stmt2->rowCount()) {
                $swInstalledDomain = tr('This package is installed on following domain(s):');
                $swInstalledDomain .= "<ul>";

                while ($row2 = $stmt->fetchRow()) {
                    $swInstalledDomain .= "<li>" . $row2['domain'] . "</li>";
                }

                $swInstalledDomain .= "</ul>";
                $tpl->assign('SW_INSTALLED', $swInstalledDomain);
            } else {
                $tpl->assign('SW_INSTALLED', tr('This package is not installed'));
            }

            if ($row['swdepot'] == "yes") {
                $tpl->assign('TR_NAME', tr('%1$s - (Softwaredepot)', $row['name']));
                $tpl->assign('SOFTWARE_IS_NOT_IN_SOFTWAREDEPOT', '');
                $tpl->parse('SOFTWARE_IS_IN_SOFTWAREDEPOT', 'software_is_in_softwaredepot');
            } else {
                $importURL = "software_import.php?id=" . $row['id'];
                $delURL = "software_delete.php?id=" . $row['id'];
                $tpl->assign([
                    'TR_NAME'     => $row['name'],
                    'IMPORT_LINK' => $importURL,
                    'DELETE_LINK' => $delURL
                ]);

                $tpl->parse('SOFTWARE_IS_NOT_IN_SOFTWAREDEPOT', 'software_is_not_in_softwaredepot');
                $tpl->assign('SOFTWARE_IS_IN_SOFTWAREDEPOT', '');
            }

            $tpl->assign([
                'LINK_COLOR'          => '#000000',
                'TR_TOOLTIP'          => $row['description'],
                'TR_VERSION'          => $row['version'],
                'TR_LANGUAGE'         => $row['language'],
                'TR_TYPE'             => $row['type'],
                'TR_ADMIN'            => 'List',
                'TR_RESELLER'         => $row['admin'],
                'TR_SOFTWARE_DEPOT'   => tr('%1$ss - Software', $row['admin']),
                'TR_IMPORT'           => tr('Import'),
                'TR_SOFTWARE_IMPORT'  => tr('Depot import'),
                'TR_SOFTWARE_DELETE'  => tr('Delete'),
                'TR_DELETE'           => tr('Delete'),
                'IS_IN_SOFTWAREDEPOT' => tr('N/A'),
                'TR_MESSAGE_IMPORT'   => tr('Are you sure you want to import this package into the software depot?'),
                'TR_MESSAGE_DELETE'   => tr('Are you sure you want to delete this package?')
            ]);
            $tpl->parse('LIST_SOFTWAREDEPOT', '.list_softwaredepot');
        }

        $tpl->assign('NO_SOFTWAREDEPOT_LIST', '');
        return $stmt->rowCount();
    }

    $stmt = exec_query('SELECT admin_name FROM admin WHERE admin_id = ?', $resellerId);

    if (!$stmt->rowCount()) {
        set_page_message(tr('Wrong reseller id.'), 'error');
        redirectTo('software_manage.php');
    }

    $tpl->assign([
        'NO_SOFTWAREDEPOT'   => tr('No software available'),
        'TR_SOFTWARE_DEPOT'  => tr('%1$ss - Software', $stmt->fetchRow(PDO::FETCH_COLUMN)),
        'TR_SOFTWARE_IMPORT' => tr('Depot import'),
        'TR_SOFTWARE_DELETE' => tr('Delete')
    ]);
    $tpl->parse('NO_SOFTWAREDEPOT_LIST', 'no_softwaredepot_list');
    $tpl->assign('LIST_SOFTWAREDEPOT', '');

    return 0;
}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return int
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function get_reseller_software($tpl)
{
    $stmt = execute_query(
        "
          SELECT t1.admin_id AS reseller_id, t1.admin_name AS reseller
          FROM admin t1
          LEFT JOIN reseller_props AS t2 ON t2.reseller_id = t1.admin_id
          WHERE t1.admin_type = 'reseller'
          AND t2.software_allowed = 'yes'
          ORDER BY t1.admin_id ASC
        "
    );

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetchRow()) {
            $stmt2 = exec_query('SELECT software_id FROM web_software WHERE reseller_id = ?', $row['reseller_id']);

            $software_ids = [];
            while ($data = $stmt2->fetchRow()) {
                $software_ids[] = $data['software_id'];
            }

            $countSwDepot = exec_query(
                "
                  SELECT count(software_id)
                  FROM web_software
                  WHERE software_active = 1
                  AND software_depot = 'yes'
                  AND reseller_id = ?
                ",
                $row['reseller_id']
            )->fetchRow(PDO::FETCH_COLUMN);

            $countWaiting = exec_query(
                "SELECT count(software_id) FROM web_software WHERE software_active = 0 AND reseller_id = ?",
                $row['reseller_id']
            )->fetchRow(PDO::FETCH_COLUMN);

            $countActivated = exec_query(
                "SELECT count(software_id) FROM web_software WHERE software_active = 1 AND reseller_id = ?",
                $row['reseller_id']
            )->fetchRow(PDO::FETCH_COLUMN);

            if (count($software_ids) > 0) {
                $swInUse = execute_query(
                    "
                      SELECT count(domain_id)
                      FROM web_software_inst
                      WHERE software_id IN (" . implode(',', $software_ids) . ") AND software_status = 'ok'
                    "
                )->fetchRow(PDO::FETCH_COLUMN);
            } else {
                $swInUse = 0;
            }

            $tpl->assign([
                'RESELLER_NAME'            => $row['reseller'],
                'RESELLER_ID'              => $row['reseller_id'],
                'RESELLER_COUNT_SWDEPOT'   => $countSwDepot,
                'RESELLER_COUNT_WAITING'   => $countWaiting,
                'RESELLER_COUNT_ACTIVATED' => $countActivated,
                'RESELLER_SOFTWARE_IN_USE' => $swInUse
            ]);
            $tpl->parse('LIST_RESELLER', '.list_reseller');
        }

        $tpl->assign('NO_RESELLER_LIST', '');
        return $stmt->rowCount();
    }

    $tpl->assign('NO_RESELLER', tr('No reseller with activated software installer found!'));
    $tpl->parse('NO_RESELLER_LIST', '.no_reseller_list');
    $tpl->assign('LIST_RESELLER', '');
    return 0;
}


/**
 * Must be documented
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $softwareId
 * @return int
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function get_reseller_rights($tpl, $softwareId)
{
    $stmt = exec_query(
        "
          SELECT a.software_id, a.software_master_id, a.reseller_id, a.rights_add_by, b.admin_name AS reseller
          FROM web_software a, admin b
          WHERE a.reseller_id = b.admin_id
          AND a.software_depot = 'yes'
          AND a.software_master_id = ?
        ",
        $softwareId
    );

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetchRow()) {
            $stmt2 = exec_query('SELECT admin_name FROM admin WHERE admin_id = ?', $row['rights_add_by']);
            $tpl->assign([
                'RESELLER'          => $row['reseller'],
                'ADMINISTRATOR'     => ($stmt2->rowCount())
                    ? $stmt2->fetchRow(PDO::FETCH_COLUMN) : tr('Admin not available'),
                'TR_REMOVE_RIGHT'   => tr('Remove'),
                'TR_MESSAGE_REMOVE' => tr('Are you sure to remove the permissions ?'),
                'REMOVE_RIGHT_LINK' => "software_change_rights.php?id=" . $row['software_master_id'] . "&reseller_id="
                    . $row['reseller_id']
            ]);
            $tpl->parse('LIST_RESELLER', '.list_reseller');
        }

        $tpl->assign('NO_RESELLER_LIST', '');
        return $stmt->rowCount();
    }

    $tpl->assign([
        'NO_RESELLER'   => tr('No reseller with permissions for this software found.'),
        'LIST_RESELLER' => ''
    ]);
    $tpl->parse('NO_RESELLER_LIST', '.no_reseller_list');
    return 0;
}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $softwareId software unique identifier
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function get_reseller_list($tpl, $softwareId)
{
    $stmt = execute_query(
        "
          SELECT a.reseller_id, b.admin_name AS reseller
          FROM reseller_props a, admin b
          WHERE a.reseller_id = b.admin_id
          AND a.software_allowed = 'yes'
          AND a.softwaredepot_allowed = 'yes'
        "
    );

    if ($stmt->rowCount()) {
        $resellerCount = 0;

        while ($row = $stmt->fetchRow()) {
            $stmt2 = exec_query(
                'SELECT reseller_id FROM web_software WHERE reseller_id = ? AND software_master_id = ?',
                [$row['reseller_id'], $softwareId]
            );

            if (!$stmt2->rowCount()) {
                $tpl->assign([
                    'ALL_RESELLER_NAME' => tr('All reseller'),
                    'RESELLER_ID'       => $row['reseller_id'],
                    'RESELLER_NAME'     => $row['reseller'],
                    'SOFTWARE_ID_VALUE' => $softwareId
                ]);
                $tpl->parse('RESELLER_ITEM', '.reseller_item');
                $resellerCount++;
            }
        }

        if ($resellerCount) {
            $tpl->parse('SELECT_RESELLER', '.select_reseller');
            $tpl->assign('NO_SELECT_RESELLER', '');
        } else {
            $tpl->assign([
                'NO_RESELLER_AVAILABLE' => tr('No reseller available to add the permissions.'),
                'SELECT_RESELLER'       => '',
                'RESELLER_ITEM'         => ''
            ]);
            $tpl->parse('NO_SELECT_RESELLER', '.no_select_reseller');
        }

        return;
    }

    $tpl->assign([
        'NO_RESELLER_AVAILABLE' => tr('No reseller available to add the permissions.'),
        'SELECT_RESELLER'       => '',
        'RESELLER_ITEM'         => ''
    ]);
    $tpl->parse('NO_SELECT_RESELLER', '.no_select_reseller');
}

/***********************************************************************************************************************
 * These functions are used by reseller
 */

/**
 * Send software upload notification
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $softwarePackage Software package
 * @param int $softwareId
 * @return bool TRUE on success, FALSE on failure
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @throws Zend_Exception
 */
function send_new_sw_upload($resellerId, $softwarePackage, $softwareId)
{
    $stmt = exec_query('SELECT created_by FROM admin WHERE admin_id = ?', $resellerId);
    $resellerData = $stmt->fetchRow();
    $stmt = exec_query(
        'SELECT admin_name, fname, lname, email FROM admin WHERE admin_id = ?', $resellerData['created_by']
    );
    $adminData = $stmt->fetchRow();
    $ret = send_mail([
        'mail_id'      => 'software-installer-upload-sw',
        'fname'        => $adminData['fname'],
        'lname'        => $adminData['lname'],
        'username'     => $adminData['admin_name'],
        'email'        => $adminData['email'],
        'subject'      => tr('A new software package has been uploaded'),
        'message'      => tr('Dear {NAME},

A new software package has been uploaded by a reseller.

Reseller ID: {RESELLER_ID}
Software package: {SOFTWARE_PACKAGE}
Software ID: {SOFTWARE_ID}

Please do not reply to this email.

___________________________
i-MSCP Mailer'),
        'placeholders' => [
            '{RESELLER_ID}'      => $resellerId,
            '{SOFTWARE_ARCHIVE}' => $softwarePackage,
            '{SOFTWARE_ID}'      => $softwareId
        ]
    ]);

    if (!$ret) {
        write_log(sprintf("Couldn't send software upload notification to %s", $adminData['admin_name']), E_USER_ERROR);
        return false;
    }

    return true;
}

/**
 * Check wheter the reseller has access to the web software depot
 *
 * @param int $userId
 * @return string yes if reseller has access to the web software repository, no otherwise
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function ask_reseller_is_allowed_web_depot($userId)
{
    return exec_query(
        'SELECT websoftwaredepot_allowed FROM reseller_props WHERE reseller_id = ?', $userId
    )->fetchRow(PDO::FETCH_COLUMN);

}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $userId Reseller unique identifier
 * @return int
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function get_avail_software_reseller($tpl, $userId)
{
    ini_set('display_errors', 1);
    $softwareAllowed = exec_query(
        'SELECT software_allowed FROM reseller_props WHERE reseller_id = ?', $userId
    )->fetchRow(PDO::FETCH_COLUMN);

    if ($softwareAllowed == 'yes') {
        $stmt = exec_query(
            "
              SELECT software_id AS id, reseller_id AS resellerid, software_name AS name, software_version AS version,
                software_language AS language, software_desc AS description, software_type AS type,
                software_active AS swactive, software_archive AS filename, software_status AS swstatus,
                software_depot AS softwaredepot
              FROM web_software
              WHERE reseller_id = ?
            ",
            $userId
        );

        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow()) {
                if ($row['swstatus'] == 'ok' || $row['swstatus'] == 'ready') {
                    if ($row['swstatus'] == 'ready') {
                        exec_query("UPDATE web_software SET software_status = 'ok' WHERE software_id = ?", $row['id']);
                        send_new_sw_upload($userId, $row['filename'] . '.tar.gz', $row['id']);
                        set_page_message(
                            tr('Package installed successfully... Awaiting release from admin!'), 'success'
                        );
                    }

                    $url = "software_delete.php?id=" . $row['id'];

                    $stmt2 = exec_query(
                        "
                          SELECT domain.domain_id AS did, domain.domain_name AS domain,
                            web_software_inst.domain_id AS wdid, web_software_inst.software_id AS sid,
                            web_software.software_id AS wsid
                          FROM domain, web_software, web_software_inst
                          WHERE web_software_inst.software_id = ?
                          AND web_software.software_id = web_software_inst.software_id
                          AND domain.domain_id = web_software_inst.domain_id
                        ",
                        $row['id']
                    );

                    if ($stmt2->rowCount()) {
                        $swInstalledDomain = tr('This software is installed on following domain(s):');
                        $swInstalledDomain .= '<ul>';

                        while ($row2 = $stmt2->fetchRow()) {
                            $swInstalledDomain .= '<li>' . $row2['domain'] . '</li>';
                        }

                        $swInstalledDomain .= '</ul>';
                        $tpl->assign('SW_INSTALLED', $swInstalledDomain);
                    } else {
                        $tpl->assign('SW_INSTALLED', tr('This package is not installed yet'));
                    }

                    $tpl->assign([
                        'SW_NAME'               => tohtml($row['name']),
                        'LINK_COLOR'            => '#000000',
                        'SW_VERSION'            => tohtml($row['version']),
                        'SW_LANGUAGE'           => tohtml($row['language']),
                        'SW_DESCRIPTION'        => tohtml($row['description'], 'htmlAttr'),
                        'SW_TYPE'               => tohtml($row['type']),
                        'DELETE'                => tohtml($url, 'htmlAttr'),
                        'TR_DELETE'             => tr('Delete'),
                        'WAITING_SOFTWARE_LIST' => '',
                        'SOFTWARE_ICON'         => 'delete'
                    ]);

                    if ($row['swactive'] == '0') {
                        $tpl->assign('SW_STATUS', tr('waiting for activation'));
                    } elseif ($row['swactive'] == '1' && $row['softwaredepot'] == 'yes') {
                        $tpl->assign('SW_STATUS', tr('activated (Softwaredepot)'));
                    } else {
                        $tpl->assign('SW_STATUS', tr('activated'));
                    }
                } else {
                    if ($row['swstatus'] == 'toadd') {
                        $url = 'software_delete.php?id=' . $row['id'];
                        $tpl->assign([
                            'SW_NAME'        => tr('Installing your uploaded package. Please refresh this page.'),
                            'LINK_COLOR'     => '#FF0000',
                            'SW_VERSION'     => '',
                            'SW_LANGUAGE'    => '',
                            'SW_DESCRIPTION' => tr('After your upload the package it will be installed on your systems.<br>Refresh your site to see the new status!'),
                            'SW_TYPE'        => '',
                            'DELETE'         => $url,
                            'TR_DELETE'      => tr('Delete'),
                            'SW_STATUS'      => tr('installing'),
                            'SOFTWARE_ICON'  => 'disabled']);
                    } else {
                        if ($row['swstatus'] == 'todelete') {
                            $tpl->assign([
                                'SW_NAME'        => tr('Failure in the package. Deleting!'),
                                'LINK_COLOR'     => '#FF0000',
                                'SW_VERSION'     => '',
                                'SW_LANGUAGE'    => '',
                                'SW_DESCRIPTION' => tr('Check your package. There is an error inside!<br>Refresh your site to see the new status!'),
                                'SW_TYPE'        => '',
                                'DELETE'         => '',
                                'TR_DELETE'      => '',
                                'SW_STATUS'      => tr('deleting'),
                                'SOFTWARE_ICON'  => 'disabled'
                            ]);
                            set_page_message(tr('The package is corrupt. Please correct it.'), 'error');
                        } elseif (preg_match('/double_depot_/i', $row['swstatus'])) {
                            $tpl->assign([
                                'SW_NAME'        => tr('Package already exists in the software repository!'),
                                'LINK_COLOR'     => '#FF0000',
                                'SW_VERSION'     => '',
                                'SW_LANGUAGE'    => '',
                                'SW_DESCRIPTION' => tr('Please contact the administrator!<br>Ask him for the permissions to use this package.<br>It is not allowed to upload this packet two times.<br>Refresh your site to see the new status!'),
                                'SW_TYPE'        => '',
                                'DELETE'         => '',
                                'TR_DELETE'      => '',
                                'SW_STATUS'      => tr('deleting'),
                                'SOFTWARE_ICON'  => 'disabled'
                            ]);
                            set_page_message(tr('This package already exists in the administrator software repository.'), 'error');
                        } elseif (preg_match('/double_res_/i', $row['swstatus'])) {
                            $tpl->assign([
                                'SW_NAME'        => tr('Package already exists in your software repository!'),
                                'LINK_COLOR'     => '#FF0000',
                                'SW_VERSION'     => '',
                                'SW_LANGUAGE'    => '',
                                'SW_DESCRIPTION' => tr('Check your own uploads!<br>Ask the administrator if you don\'t find the package.<br>It is not allowed to upload this packages two times.<br>Refresh your site to see the new status!'),
                                'SW_TYPE'        => '',
                                'DELETE'         => '',
                                'TR_DELETE'      => '',
                                'SW_STATUS'      => tr('deleting'),
                                'SOFTWARE_ICON'  => 'disabled'
                            ]);
                            set_page_message(tr('This package already exists in your software repository.'), 'error');
                        }

                        $cfg = Registry::get('config');
                        @unlink(utils_normalizePath(
                            $cfg['GUI_APS_DIR'] . '/' . $row['resellerid'] . '/' . $row['filename'] . '-'
                            . $row['id'] . '.tar.gz')
                        );
                        exec_query('DELETE FROM web_software WHERE software_id = ?', $row['id']);
                    }
                }
                $tpl->parse('LIST_SOFTWARE', '.list_software');
            }

            $tpl->assign('NO_SOFTWARE_LIST', '');
        } else {
            $tpl->assign([
                'NO_SOFTWARE'   => tr('You do not have any software uploaded yet'),
                'LIST_SOFTWARE' => ''
            ]);
            $tpl->parse('NO_SOFTWARE_LIST', '.no_software_list');
        }

        return $stmt->rowCount();
    }

    $tpl->assign([
        'NO_SOFTWARE'   => tr('You do not have permissions to upload software'),
        'LIST_SOFTWARE' => ''
    ]);
    $tpl->parse('NO_SOFTWARE_LIST', '.no_software_list');
    return 0;
}

/***********************************************************************************************************************
 * This functions are used by client
 */

/**
 * Generate user actions for a specific software
 *
 * @param int $softwareId Software unique identifier
 * @param int $dmnId Domain unique identifier
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return array
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function gen_user_software_action($softwareId, $dmnId, $tpl)
{
    $stmt = exec_query('SELECT software_status FROM web_software_inst WHERE software_id = ? AND domain_id = ?', [
        $softwareId, $dmnId
    ]);

    if (!$stmt->rowCount()) {
        $software_status = 'not installed';
        $software_icon = 'edit';
    } else {
        $row = $stmt->fetchRow();

        if ($row['software_status'] == 'ok') {
            $software_status = 'installed';
            $software_icon = 'delete';
        } elseif ($row['software_status'] == 'toadd') {
            $software_status = 'installing';
            $software_icon = 'disabled';
        } elseif ($row['software_status'] == 'todelete') {
            $software_status = 'deleting';
            $software_icon = 'delete';
        } else {
            $software_status = 'n/a';
            $software_icon = 'disabled';
        }
    }

    if ($software_status == 'installing') {
        $tpl->assign([
            'TR_MESSAGE_DELETE'  => '',
            'TR_MESSAGE_INSTALL' => ''
        ]);
        $tpl->parse('SOFTWARE_ACTION_DELETE', '');
        return [tr('Install in progress'), '', '', $software_status, $software_icon];
    }

    if ($software_status == 'deleting') {
        $tpl->assign([
            'TR_MESSAGE_DELETE'  => '',
            'TR_MESSAGE_INSTALL' => ''
        ]);
        $tpl->parse('SOFTWARE_ACTION_DELETE', '');
        return [tr('Deletion in progress'), '', '', $software_status, $software_icon];
    }

    if ($software_status == 'installed') {
        $tpl->assign([
            'TR_MESSAGE_DELETE'       => tr('Are you sure you want to delete this package?'),
            'SOFTWARE_ACTION_INSTALL' => ''
        ]);
        return [
            tr('Uninstall'),
            'software_delete.php?id=' . $softwareId,
            'software_view.php?id=' . $softwareId, $software_status, $software_icon
        ];
    }

    $tpl->assign([
        'TR_MESSAGE_INSTALL'     => tr('Are you sure to install this package?'),
        'SOFTWARE_ACTION_DELETE' => ''
    ]);

    return [
        tr('Install'),
        'software_install.php?id=' . $softwareId,
        'software_view.php?id=' . $softwareId,
        $software_status,
        $software_icon
    ];
}

/**
 * Generate available software list for a specific customer
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId Domain unique identifier
 * @param int $resellerId Reseller unique identifier
 * @return int Total number of available software
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function gen_software_list($tpl, $domainId, $resellerId)
{
    $stmt = exec_query(
        '
          SELECT software_id, software_status, software_res_del, software_name, software_version
          FROM web_software_inst
          WHERE domain_id = ?
          AND software_res_del = ?
        ',
        [$domainId, 1]
    );

    if (!$stmt->rowCount()) {
        $tpl->assign([
            'SOFTWARE_DEL_ITEM'    => '',
            'DEL_SOFTWARE_SUPPORT' => ''
        ]);
    } else {
        while ($row = $stmt->fetchRow()) {
            if ($row['software_status'] == 'ok') {
                $delsoftware_status = 'installed';
                $del_software_action_script = "software_delete.php?id=" . $row['software_id'];
                $tpl->assign([
                    'DEL_SOFTWARE_ACTION'   => tr('Uninstall'),
                    'TR_RES_MESSAGE_DELETE' => tr('Are you sure you want to delete this package?')
                ]);
            } elseif ($row['software_status'] == 'toadd') {
                $delsoftware_status = 'installing';
                $del_software_action_script = "software_delete.php?id=" . $row['software_id'];
                $tpl->assign([
                    'DEL_SOFTWARE_ACTION'   => tr('Uninstall'),
                    'TR_RES_MESSAGE_DELETE' => tr('Are you sure you want to delete this package?')
                ]);
            } elseif ($row['software_status'] == 'todelete') {
                $delsoftware_status = 'deleting';
                $del_software_action_script = '';

                $tpl->assign([
                    'DEL_SOFTWARE_ACTION'   => '',
                    'TR_RES_MESSAGE_DELETE' => ''
                ]);
            } else {
                $delsoftware_status = 'n/a';
                $del_software_action_script = '';
            }

            $software_name = $row['software_name'];
            $software_version = $row['software_version'];

            $tpl->assign([
                'SOFTWARE_DEL_RES_MESSAGE'   => tr('This Package (%s, V%s) was deleted by your reseller. You can only uninstall this package!<br>Please delete the files and database for this package manually!', $software_name, $software_version),
                'DEL_SOFTWARE_STATUS'        => $delsoftware_status,
                'DEL_SOFTWARE_ACTION_SCRIPT' => $del_software_action_script
            ]);
            $tpl->parse('DEL_SOFTWARE_ITEM', '.del_software_item');
        }

        $tpl->assign([
            'TR_DEL_SOFTWARE' => tr('Installed Package which was deleted by your reseller.'),
            'TR_DEL_STATUS'   => tr('Status'),
            'TR_DEL_ACTION'   => tr('Action')
        ]);
        $tpl->parse('DEL_SOFTWARE_SUPPORT', '.del_software_support');
    }

    $domainProperties = get_domain_default_props($_SESSION['user_id']);
    $optCond = ($domainProperties['sqld_limit'] == '-1') ? 'AND software_db <> 1' : '';
    $stmt = exec_query(
        "
          SELECT software_id, software_name, software_version, software_language, software_type, software_db,
              software_desc
          FROM web_software
          WHERE reseller_id = ?
          AND software_active = ?
          $optCond
          ORDER BY software_name
        ",
        [$resellerId, 1]
    );

    if (!$stmt->rowCount()) {
        $tpl->assign([
            'NO_SOFTWARE_AVAIL' => tr('No software available'),
            'SOFTWARE_LIST'     => ''
        ]);
        return 0;
    }

    $tpl->assign('NO_SOFTWARE_SUPPORT', '');

    while ($row = $stmt->fetchRow()) {
        list(
            $software_action, $software_action_script, $view_software_script, $software_status, $software_icon
            ) = gen_user_software_action($row['software_id'], $domainId, $tpl);

        $tpl->assign([
            'SOFTWARE_NAME'          => $row['software_name'],
            'SOFTWARE_DESCRIPTION'   => $row['software_desc'],
            'SOFTWARE_VERSION'       => $row['software_version'],
            'SOFTWARE_LANGUAGE'      => $row['software_language'],
            'SOFTWARE_TYPE'          => $row['software_type'],
            'SOFTWARE_STATUS'        => $software_status,
            'SOFTWARE_ACTION'        => $software_action,
            'SOFTWARE_ACTION_SCRIPT' => $software_action_script,
            'VIEW_SOFTWARE_SCRIPT'   => $view_software_script,
            'SOFTWARE_ICON'          => $software_icon
        ]);

        if ($row['software_db'] == '1') {
            $tpl->assign('SOFTWARE_NEED_DATABASE', tr('required'));
        } else {
            $tpl->assign('SOFTWARE_NEED_DATABASE', tr('not required'));
        }

        if ($software_status == 'installed') {
            $tpl->parse('SOFTWARE_ACTION_DELETE', 'software_action_delete');
        } elseif ($software_status == "not installed") {
            $tpl->parse('SOFTWARE_ACTION_INSTALL', 'software_action_install');
        }

        $tpl->parse('SOFTWARE_ITEM', '.software_item');
    }

    return $stmt->rowCount();
}

/**
 * Must be documented
 *
 * @param  $softwareId
 * @param $dmnCreatedId
 * @return bool
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function check_software_avail($softwareId, $dmnCreatedId)
{
    return (bool)exec_query('SELECT COUNT(software_id) FROM web_software WHERE software_id = ? AND reseller_id = ?', [
        $softwareId, $dmnCreatedId
    ])->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Check if the given software is installed for the given domain
 *
 * @param  iMSCP_pTemplate $tpl
 * @param $dmnId
 * @param int $softwareId
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function check_is_installed($tpl, $dmnId, $softwareId)
{
    $stmt = exec_query(
        '
          SELECT software_id, software_prefix, db, path
          FROM web_software_inst
          WHERE domain_id = ?
          AND software_id = ?
        ',
        [$dmnId, $softwareId]
    );

    if (!$stmt->rowCount()) {
        $tpl->assign([
            'INSTALLED_SOFTWARE_INFO' => '',
            'SOFTWARE_INSTALL_BUTTON' => 'software_install.php?id=' . $softwareId
        ]);
        $tpl->parse('SOFTWARE_INSTALL', '.software_install');
        return;
    }

    $row = $stmt->fetchRow();
    $tpl->assign([
        'SOFTWARE_INSTALL_BUTTON'      => '',
        'SOFTWARE_STATUS'              => tr('installed'),
        'SOFTWARE_INSTALL_PATH'        => $row['path'],
        'SOFTWARE_INSTALL_DATABASE'    => $row['db'],
        'TR_SOFTWARE_INFO'             => tr('Installation details'),
        'TR_SOFTWARE_STATUS'           => tr('Software status'),
        'TR_SOFTWARE_INSTALL_PATH'     => tr('Installation path'),
        'TR_SOFTWARE_INSTALL_DATABASE' => tr('Used database'),
        'SOFTWARE_INSTALL'             => ''
    ]);
    $tpl->parse('INSTALLED_SOFTWARE_INFO', '.installed_software_info');
}

/**
 * Get software properties
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $dmnId
 * @param int $softwareId
 * @param int $dmnCreatedId
 * @return void
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @throws Zend_Exception
 */
function get_software_props($tpl, $dmnId, $softwareId, $dmnCreatedId)
{
    if (!check_software_avail($softwareId, $dmnCreatedId)) {
        set_page_message(tr('Software not found!'), 'error');
        redirectTo('software.php');
    }

    $stmt = exec_query(
        '
          SELECT software_name, software_version, software_language, software_type, software_db, software_link,
            software_desc
          FROM web_software
          WHERE software_id = ?
          AND reseller_id = ?
        ',
        [$softwareId, $dmnCreatedId]
    );
    $row = $stmt->fetchRow();

    $tpl->assign('SOFTWARE_DB', ($row['software_db'] == 1) ? tr('Yes') : tr('No'));

    $swLink = $row['software_link'];

    if (!preg_match('/http:/', $swLink) && !preg_match('/https:/', $swLink)) {
        $swLink = 'http://' . $swLink;
    }

    $tpl->assign([
        'SOFTWARE_NAME'     => $row['software_name'],
        'SOFTWARE_VERSION'  => $row['software_version'],
        'SOFTWARE_LANGUAGE' => $row['software_language'],
        'SOFTWARE_TYPE'     => $row['software_type'],
        'SOFTWARE_LINK'     => $swLink,
        'SOFTWARE_DESC'     => tohtml($row['software_desc'])
    ]);

    check_is_installed($tpl, $dmnId, $softwareId);
    $tpl->parse('SOFTWARE_ITEM', 'software_item');
}

/**
 * Must be documented
 *
 * @param  iMSCP_pTemplate $tpl
 * @param int $dmnId
 * @param int $softwareId
 * @param int $dmnCreatedId
 * @param int $dmnSqldLimit
 * @return void
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @throws Zend_Exception
 */
function get_software_props_install($tpl, $dmnId, $softwareId, $dmnCreatedId, $dmnSqldLimit)
{
    if (!check_software_avail($softwareId, $dmnCreatedId)) {
        set_page_message(tr('Software not found!'), 'error');
        redirectTo('software.php');
    }

    gen_user_domain_list($tpl, $_SESSION['user_id']);

    $stmt = exec_query(
        '
          SELECT software_name, software_type, software_db FROM web_software
          WHERE software_id = ? AND reseller_id = ?
        ',
        [$softwareId, $dmnCreatedId]
    );
    $row = $stmt->fetchRow();

    check_is_installed($tpl, $dmnId, $softwareId);

    if ($row['software_db'] == 1) {
        $tpl->assign('SOFTWARE_DB', tr('yes'));

        if ($dmnSqldLimit == '-1') {
            # Customer cannot install software that require SQL database if it has not SQL feature enabled
            showBadRequestErrorPage();
        }
    } else {
        $tpl->assign([
            'SOFTWARE_DB'       => tr('no'),
            'REQUIRE_INSTALLDB' => ''
        ]);
    }

    $tpl->assign([
        'TR_SOFTWARE_NAME' => $row['software_name'],
        'SOFTWARE_TYPE'    => $row['software_type']
    ]);
    $tpl->parse('SOFTWARE_ITEM', '.software_item');
}

/**
 * Generate domain list
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $customerId Customer unique identifier
 * @return void
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @throws Zend_Exception
 */
function gen_user_domain_list($tpl, $customerId)
{
    $domainFound = false;
    $domainId = get_user_domain_id($customerId);

    if (isset($_POST['selected_domain'])) {
        list ($postDomainId, $postDomainType) = explode(';', $_POST['selected_domain']);
    } else {
        $postDomainId = $domainId;
        $postDomainType = 'dmn';
    }

    // dmn
    $stmt = exec_query(
        "
          SELECT domain_id, domain_name
          FROM domain
          WHERE domain_id = ?
          AND domain_status = 'ok'
          AND url_forward = 'no'
        ",
        $domainId
    );
    if ($stmt->rowCount()) {
        $domainFound = true;
        $row = $stmt->fetchRow();
        $tpl->assign([
            'SELECTED_DOMAIN'    => ($postDomainType == 'dmn' && $postDomainId == $row['domain_id']) ? ' selected' : '',
            'DOMAIN_NAME_VALUES' => tohtml($row['domain_id'] . ';dmn', 'htmlAttr'),
            'DOMAIN_NAME'        => tohtml(decode_idna($row['domain_name']))
        ]);
        $tpl->parse('SHOW_DOMAIN_LIST', '.show_domain_list');
    }

    // als
    $stmt = exec_query(
        "
            SELECT alias_id, alias_name
            FROM domain_aliasses
            WHERE domain_id = ?
            AND alias_status = 'ok'
            AND url_forward = 'no'
        ",
        $domainId
    );
    if ($stmt->rowCount()) {
        $domainFound = true;

        while ($row = $stmt->fetchRow()) {
            $tpl->assign([
                'SELECTED_DOMAIN'    => ($postDomainType == 'als' && $postDomainId == $row['alias_id'])
                    ? ' selected' : '',
                'DOMAIN_NAME_VALUES' => tohtml($row['alias_id'] . ';als', 'htmlAttr'),
                'DOMAIN_NAME'        => tohtml(decode_idna($row['alias_name']))
            ]);
            $tpl->parse('SHOW_DOMAIN_LIST', '.show_domain_list');
        }
    }

    // sub
    $stmt = exec_query(
        "
            SELECT subdomain_id, CONCAT(subdomain_name, '.', domain_name) AS subdomain_name
            FROM subdomain
            JOIN domain USING (domain_id)
            WHERE subdomain_url_forward = 'no'
            AND subdomain_status = 'ok'
            AND domain_id = ?
        ",
        $domainId
    );
    if ($stmt->rowCount()) {
        $domainFound = true;

        while ($row = $stmt->fetchRow()) {
            $tpl->assign([
                'SELECTED_DOMAIN'    => ($postDomainType == 'sub' && $postDomainId == $row['subdomain_id'])
                    ? ' selected' : '',
                'DOMAIN_NAME_VALUES' => tohtml($row['subdomain_id'] . ';sub', 'htmlAttr'),
                'DOMAIN_NAME'        => tohtml(decode_idna($row['subdomain_name']))
            ]);
            $tpl->parse('SHOW_DOMAIN_LIST', '.show_domain_list');
        }
    }

    // alssub
    $stmt = exec_query(
        "
            SELECT subdomain_alias_id, CONCAT(subdomain_alias_name, '.', alias_name) AS subdomain_alias_name
            FROM subdomain_alias
            JOIN domain_aliasses USING (alias_id)
            WHERE subdomain_alias_url_forward = 'no'
            AND subdomain_alias_status = 'ok'
            AND domain_id = ?
        ",
        $domainId
    );
    if ($stmt->rowCount()) {
        $domainFound = true;

        while ($row = $stmt->fetchRow()) {
            $tpl->assign([
                'SELECTED_DOMAIN'    => ($postDomainType == 'alssub' && $postDomainId == $row['subdomain_alias_id'])
                    ? ' selected' : '',
                'DOMAIN_NAME_VALUES' => tohtml($row['subdomain_alias_id'] . ';alssub', 'htmlAttr'),
                'DOMAIN_NAME'        => tohtml(decode_idna($row['subdomain_alias_name']))
            ]);
            $tpl->parse('SHOW_DOMAIN_LIST', '.show_domain_list');
        }
    }

    if (!$domainFound) { # Case where all domain are forwarded
        set_page_message(tr('No domain available for new software instance.'), 'warning');
        redirectTo('software.php');
    }
}

/**
 * Check database connection
 *
 * @param string $dbName Database name
 * @param string $dbUser Database user
 * @param string $dbPass Database password
 * @return bool
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function check_db_connection($dbName, $dbUser, $dbPass)
{
    $cfg = Registry::get('config');

    try {
        Database::connect($dbUser, $dbPass, $cfg['DATABASE_TYPE'], $cfg['DATABASE_HOST'], $dbName, 'testConn');
    } catch (PDOException $e) {
        return false;
    }

    return true;
}
