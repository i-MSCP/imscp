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

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
    [
        'layout'             => 'shared/layouts/ui.tpl',
        'page'               => 'admin/software_rights.tpl',
        'page_message'       => 'layout',
        'list_reseller'      => 'page',
        'no_reseller_list'   => 'page',
        'no_select_reseller' => 'page',
        'select_reseller'    => 'page',
        'reseller_item'      => 'select_reseller']);

if (isset($_GET['id']) || isset($_POST['id'])) {
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $software_id = $_GET['id'];
    } elseif (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $software_id = $_POST['id'];
    } else {
        set_page_message(tr('Wrong software id.'), 'error');
        redirectTo('software_manage.php');
        exit;
    }
} else {
    set_page_message(tr('Wrong software id.'), 'error');
    redirectTo('software_manage.php');
    exit;
}

$tpl->assign('TR_PAGE_TITLE', tr('i-MSCP - Application Management (Permissions)'));

$res_cnt = get_reseller_rights($tpl, $software_id);
get_reseller_list($tpl, $software_id);

$query = "SELECT `software_name`, `software_version`, `software_language` FROM `web_software` WHERE `software_id` = ?";
$rs = exec_query($query, $software_id);

$tpl->assign(
    [
        'SOFTWARE_RIGHTS_ID'           => $software_id,
        'TR_SOFTWARE_DEPOT'            => tr('Softwaredepot'),
        'TR_SOFTWARE_NAME'             => tr('%1$s - (Version: %2$s, Language: %3$s)', $rs->fields['software_name'], $rs->fields['software_version'], $rs->fields['software_language']),
        'TR_ADD_RIGHTS'                => tr('Add permissions for reseller to software:'),
        'TR_RESELLER'                  => tr('Reseller'),
        'TR_REMOVE_RIGHTS'             => tr('Remove permissions'),
        'TR_RESELLER_COUNT'            => tr('Reseller with permissions total'),
        'TR_RESELLER_NUM'              => $res_cnt,
        'TR_ADDED_BY'                  => tr('Added by'),
        'TR_ADD_RIGHTS_BUTTON'         => tr('Add permissions'),
        'TR_SOFTWARE_RIGHTS'           => tr('Software permissions'),
        'TR_ADMIN_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Software Management (Permissions)')]);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
