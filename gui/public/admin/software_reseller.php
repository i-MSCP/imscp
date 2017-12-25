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
use iMSCP\TemplateEngine;

require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptStart);

isset($_GET['id']) or showBadRequestErrorPage();

$resellerId = intval($_GET['id']);

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                           => 'shared/layouts/ui.tpl',
    'page'                             => 'admin/software_reseller.tpl',
    'page_message'                     => 'layout',
    'list_software'                    => 'page',
    'no_software_list'                 => 'page',
    'list_softwaredepot'               => 'page',
    'no_softwaredepot_list'            => 'page',
    'no_reseller_list'                 => 'page',
    'list_reseller'                    => 'page',
    'software_is_in_softwaredepot'     => 'page',
    'software_is_not_in_softwaredepot' => 'page'
]);


$tpl->assign([
    'TR_PAGE_TITLE'                => tr('Admin / Software Management / Reseller Software'),
    'RESELLER_ID'                  => $resellerId,
    'TR_SOFTWARE_INSTALLED'        => tr('Installed on'),
    'TR_SOFTWARE_RIGHTS'           => tr('Permissions'),
    'TR_SOFTWAREDEPOT_COUNT'       => tr('Total Software'),
    'TR_SOFTWAREDEPOT_NUM'         => get_installed_res_software($tpl, $_GET['id']),
    'TR_AWAITING_ACTIVATION'       => tr('Awaiting activation'),
    'TR_ACTIVATED_SOFTWARE'        => tr('Reseller list'),
    'TR_SOFTWARE_NAME'             => tr('Software name'),
    'TR_SOFTWARE_VERSION'          => tr('Version'),
    'TR_SOFTWARE_LANGUAGE'         => tr('Language'),
    'TR_SOFTWARE_TYPE'             => tr('Type'),
    'TR_RESELLER_NAME'             => tr('Reseller'),
    'TR_RESELLER_ACT_COUNT'        => tr('Reseller total'),
    'TR_RESELLER_ACT_NUM'          => get_reseller_software($tpl),
    'TR_RESELLER_COUNT_SWDEPOT'    => tr('Software repository'),
    'TR_RESELLER_COUNT_WAITING'    => tr('Awaiting activation'),
    'TR_RESELLER_COUNT_ACTIVATED'  => tr('Activated software'),
    'TR_RESELLER_SOFTWARE_IN_USE'  => tr('Total installations'),
    'TR_ADMIN_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Software Installer / Management')
]);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
