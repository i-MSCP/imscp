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

use iMSCP_pTemplate as TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * @param TemplateEngine $tpl
 */
function gen_page_data(TemplateEngine $tpl)
{
    if (isset($_POST['uaction']) && $_POST['uaction'] === 'send_delmessage') {
        $tpl->assign('DELETE_MESSAGE_TEXT', clean_input($_POST['delete_msg_text']));
        return;
    }

    $tpl->assign([
        'DELETE_MESSAGE_TEXT' => '',
        'MESSAGE'             => ''
    ]);
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

isset($_REQUEST['id']) or showBadRequestErrorPage();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/software_delete.tpl',
    'page_message' => 'page'
]);

$softwareId = intval($_REQUEST['id']);
$stmt = exec_query(
    '
        SELECTsoftware_name, software_version, software_archive, reseller_id, software_depot
        FROM web_software
        WHERE software_id = ?
    ',
    [$softwareId]
);

if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

$row = $stmt->fetch();

$stmt = exec_query('SELECT admin_name, email FROM admin WHERE admin_id = ?', [$row['reseller_id']]);
$row2 = $stmt->fetch();
$tpl->assign('DELETE_SOFTWARE_RESELLER', tr('%1$s (%2$s)', $row2['admin_name'], $row2['email']));

if ($row['software_depot'] == 'yes') {
    $cfg = iMSCP_Registry::get('config');
    @unlink($cfg['GUI_APS_DEPOT_DIR'] . '/' . $row['software_archive'] . '-' . $softwareId . '.tar.gz');
    exec_query('UPDATE  web_software_inst SET software_res_del = 1 WHERE software_master_id = ?', [$softwareId]);
    exec_query('DELETE FROM web_software WHERE software_id = ?', [$softwareId]);
    exec_query('DELETE FROM web_software WHERE software_master_id = ?', [$softwareId]);
    set_page_message(tr('Software was deleted.'), 'success');
    redirectTo('software_manage.php');
}

if (isset($_POST['id']) && $_POST['uaction'] === 'send_delmessage') {
    if (!empty($_POST['id']) && !empty($_POST['delete_msg_text'])) {
        $cfg = iMSCP_Registry::get('config');
        send_deleted_sw(
            $row['reseller_id'], $row['software_archive'] . '.tar.gz',
            $row['software_id'],
            clean_input($_POST['delete_msg_text'])
        );
        update_existing_client_installations_res_upload(
            $row['software_id'], $row['reseller_id'], $row['software_id'], true
        );
        @unlink($cfg['GUI_APS_DIR'] . "/" . $row['reseller_id'] . '/' . $row['software_archive'] . '-' . $row['software_id'] . '.tar.gz');
        exec_query('DELETE FROM web_software WHERE software_id = ?', [$row['software_id']]);
        set_page_message(tr('Software has been deleted.'), 'success');
        redirectTo('software_manage.php');
    } else {
        set_page_message(tr('Fill out a message text.'), 'error');
    }
}

$tpl->assign([
    'TR_MANAGE_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Software Management'),
    'TR_DELETE_SEND_TO'             => tr('Send message to'),
    'TR_DELETE_MESSAGE_TEXT'        => tr('Message'),
    'TR_DELETE_SOFTWARE'            => tr('Message to reseller before deleting the software'),
    'TR_DELETE_RESELLER_SOFTWARE'   => tr('Delete reseller software'),
    'TR_DELETE_DATA'                => tr('Reseller data'),
    'TR_DELETE'                     => tr('Delete'),
    'SOFTWARE_ID'                   => $softwareId,
    'RESELLER_ID'                   => $row['reseller_id']
]);

generateNavigation($tpl);
gen_page_data($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
$tpl->prnt();
unsetMessages();
