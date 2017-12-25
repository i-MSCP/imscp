<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function generatePage($tpl)
{
    $stmt = exec_query('SELECT * FROM htaccess WHERE dmn_id = ?', [get_user_domain_id($_SESSION['user_id'])]);

    if (!$stmt->rowCount()) {
        $tpl->assign('PROTECTED_AREAS', '');
        set_page_message(tr('You do not have protected areas.'), 'static_info');
        return;
    }

    while ($row = $stmt->fetch()) {
        $tpl->assign([
            'AREA_NAME' => tohtml($row['auth_name']),
            'AREA_PATH' => tohtml($row['path']),
            'STATUS'    => translate_dmn_status($row['status'])
        ]);

        if (!in_array($row['status'], ['toadd', 'tochange', 'todelete'])) {
            $tpl->assign([
                'ID'             => tohtml($row['id'], 'htmlAttr'),
                'DATA_AREA_NAME' => tohtml($row['auth_name'], 'htmlAttr'),
            ]);
            $tpl->parse('ACTION_LINKS', 'action_links');
        } else {
            $tpl->assign('ACTION_LINKS', tr('N/A'));
        }

        $tpl->parse('DIR_ITEM', '.dir_item');
    }
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('protected_areas') or showBadRequestErrorPage();

$tpl = new TemplateEngine();
$tpl->define([
    'layout'          => 'shared/layouts/ui.tpl',
    'page'            => 'client/protected_areas.tpl',
    'page_message'    => 'layout',
    'protected_areas' => 'page',
    'dir_item'        => 'protected_areas',
    'action_links'    => 'dir_item'
]);
$tpl->assign([
    'TR_PAGE_TITLE'              => tr('Client / Webtools / Protected Areas'),
    'TR_NAME'                    => tr('Name'),
    'TR_PATH'                    => tr('Path'),
    'TR_STATUS'                  => tr('Status'),
    'TR_ACTIONS'                 => tr('Actions'),
    'TR_EDIT'                    => tr('Edit'),
    'TR_DELETE'                  => tr('Delete'),
    'TR_ADD_PROTECTED_AREA'      => tr('Add new protected area'),
    'TR_MANAGE_USERS_AND_GROUPS' => tr('Manage users and groups')
]);

Registry::get('iMSCP_Application')->getEventsManager()->registerListener('onGetJsTranslations', function ($e) {
    /* @var $e iMSCP_Events_Event */
    $translations = $e->getParam('translations');
    $translations['core']['dataTable'] = getDataTablesPluginTranslations();
    $translations['core']['deletion_confirm_msg'] = tr('Are you sure you want to delete the `%%s` protected area?');
});

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
