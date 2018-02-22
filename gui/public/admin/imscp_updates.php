<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP\Update\UpdateException;
use iMSCP\Update\UpdateVersion;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param  iMSCP_pTemplate $tpl
 * @return void
 */
function admin_generatePage($tpl)
{
    try {
        $cfg = iMSCP_Registry::get('config');

        if (!$cfg['CHECK_FOR_UPDATES']) {
            set_page_message(tr('i-MSCP version update checking is disabled.'), 'static_info');
            $tpl->assign('UPDATE_INFO', '');
            return;
        }

        $updateVersion = new UpdateVersion();

        if (!$updateVersion->isAvailableUpdate()) {
            set_page_message(tr('No update available'), 'static_info');
            $tpl->assign('UPDATE_INFO', '');
            return;
        }

        $updateInfo = $updateVersion->getUpdateInfo();
        $date = new DateTime($updateInfo['published_at']);
        $tpl->assign(
            [
                'TR_UPDATE_INFO'         => tr('Update info'),
                'TR_RELEASE_VERSION'     => tr('Release version'),
                'RELEASE_VERSION'        => tohtml($updateInfo['tag_name']),
                'TR_RELEASE_DATE'        => tr('Release date'),
                'RELEASE_DATE'           => tohtml($date->format($cfg['DATE_FORMAT'])),
                'TR_RELEASE_DESCRIPTION' => tr('Release description'),
                'RELEASE_DESCRIPTION'    => tohtml($updateInfo['body']),
                'TR_DOWNLOAD_LINKS'      => tr('Download links'),
                'TR_DOWNLOAD_ZIP'        => tr('Download ZIP'),
                'TR_DOWNLOAD_TAR'        => tr('Download TAR'),
                'TARBALL_URL'            => tohtml($updateInfo['tarball_url']),
                'ZIPBALL_URL'            => tohtml($updateInfo['zipball_url'])
            ]
        );
    } catch (UpdateException $e) {
        write_log($e->getMessage(), E_USER_ERROR);
        set_page_message(tr("Couldn't get update information from Github. Consult the admin logs for more details."), 'static_error');
        $tpl->assign('UPDATE_INFO', '');
    }
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);
stripos(iMSCP_Registry::get('config')['Version'], 'git') === false or showBadRequestErrorPage();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
    [
        'layout'       => 'shared/layouts/ui.tpl',
        'page'         => 'admin/imscp_updates.tpl',
        'page_message' => 'layout',
        'update_info'  => 'page'
    ]
);
$tpl->assign('TR_PAGE_TITLE', tohtml(tr('Admin / System Tools / i-MSCP Updates')));

generateNavigation($tpl);
admin_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
