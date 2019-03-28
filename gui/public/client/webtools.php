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

/** @noinspection PhpUnhandledExceptionInspection PhpDocMissingThrowsInspection */

/**
 * Hide disabled features
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 */
function client_hideDisabledFeatures($tpl)
{
    if (!customerHasFeature('backup')) {
        $tpl->assign('BACKUP_FEATURE', '');
    }

    if (!customerHasFeature('aps')) {
        $tpl->assign('APS_FEATURE', '');
    }
}

require_once 'imscp-lib.php';

check_login('user');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'         => 'shared/layouts/ui.tpl',
    'page'           => 'client/webtools.tpl',
    'page_message'   => 'layout',
    'backup_feature' => 'page',
    'aps_feature'    => 'page',
]);
$tpl->assign([
    'TR_PAGE_TITLE'             => tohtml(tr('Client / Webtools / Overview')),
    'TR_FEATURE'                => tohtml(tr('Feature')),
    'TR_DESCRIPTION'            => tohtml(tr('Description')),
    'TR_HTACCESS_TOOLTIPS'      => tohtml(tr('Protected areas'), 'htmlAttr'),
    'TR_HTACCESS_TXT'           => tohtml(tr('Manage your protected areas, users and groups.')),
    'TR_ERROR_PAGES_TOOLTIPS'   => tohtml(tr('Error pages'), 'htmlAttr'),
    'TR_ERROR_PAGES_TXT'        => tohtml(tr('Customize error pages for your domain.')),
    'TR_BACKUP_TOOLTIPS'        => tohtml(tr('Backup'), 'htmlAttr'),
    'TR_BACKUP_TXT'             => tohtml(tr('Backup and restore settings.')),
    'TR_APP_INSTALLER_TOOLTIPS' => tohtml(tr('Application installer'), 'htmlAttr'),
    'TR_APP_INSTALLER_TXT'      => tohtml(tr('Install various Web applications with a few clicks.'))
]);

generateNavigation($tpl);
client_hideDisabledFeatures($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();
unsetMessages();
