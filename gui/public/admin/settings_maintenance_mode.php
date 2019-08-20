<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\Registry;
use iMSCP\TemplateEngine;

require 'imscp-lib.php';

check_login('admin');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptStart);

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/settings_maintenance_mode.tpl',
    'page_message' => 'layout'
]);

$cfg = Registry::get('config');

if (isset($_POST['uaction']) and $_POST['uaction'] == 'apply') {
    $maintenancemode = $_POST['maintenancemode'];
    $maintenancemode_message = clean_input($_POST['maintenancemode_message']);
    $db_cfg = Registry::get('dbConfig');
    $db_cfg->MAINTENANCEMODE = $maintenancemode;
    $db_cfg->MAINTENANCEMODE_MESSAGE = $maintenancemode_message;
    $cfg->merge($db_cfg);
    set_page_message(tr('Settings saved.'), 'success');
}

$selected_on = '';
$selected_off = '';

if ($cfg['MAINTENANCEMODE']) {
    $selected_on = ' selected';
    set_page_message(tr('Maintenance mode is activated. In this mode, only administrators can login.'), 'static_info');
} else {
    $selected_off = ' selected';
    set_page_message(tr('In maintenance mode, only administrators can login.'), 'static_info');
}

$tpl->assign([
    'TR_PAGE_TITLE'          => tr('Admin / System Tools / Maintenance Settings'),
    'TR_MAINTENANCEMODE'     => tr('Maintenance mode'),
    'TR_MESSAGE'             => tr('Message'),
    'MESSAGE_VALUE'          => (isset($cfg['MAINTENANCEMODE_MESSAGE']))
        ? tohtml($cfg['MAINTENANCEMODE_MESSAGE'])
        : tr("We are sorry, but the system is currently under maintenance.\nPlease try again later."),
    'SELECTED_ON'            => $selected_on,
    'SELECTED_OFF'           => $selected_off,
    'TR_ENABLED'             => tr('Enabled'),
    'TR_DISABLED'            => tr('Disabled'),
    'TR_APPLY'               => tr('Apply'),
    'TR_MAINTENANCE_MESSAGE' => tr('Maintenance message')
]);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
