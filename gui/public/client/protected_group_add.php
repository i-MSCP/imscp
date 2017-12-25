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

/***********************************************************************************************************************
 * Functions
 */

/**
 * Adds Htaccess group
 *
 * @return void
 */
function client_addHtaccessGroup()
{
    if (empty($_POST))
        return;

    if (!isset($_POST['groupname'])) {
        showBadRequestErrorPage();
    }

    $htgroupName = clean_input($_POST['groupname']);

    if (!validates_username($htgroupName)) {
        set_page_message(tr('Invalid group name!'), 'error');
        return;
    }

    $domainId = get_user_domain_id($_SESSION['user_id']);

    $stmt = exec_query('SELECT id FROM htaccess_groups WHERE ugroup = ? AND dmn_id = ?', [$htgroupName, $domainId]);
    if ($stmt->rowCount()) {
        set_page_message(tr('This htaccess group already exists.'), 'error');
    }

    exec_query("INSERT INTO htaccess_groups (dmn_id, ugroup, status) VALUES (?, ?, 'toadd')", [
        $domainId, $htgroupName
    ]);
    send_request();
    set_page_message(tr('Htaccess group successfully scheduled for addition.'), 'success');
    write_log(sprintf('%s added htaccess group: %s', $_SESSION['user_logged'], $htgroupName), E_USER_NOTICE);
    redirectTo('protected_user_manage.php');
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('protected_areas') or showBadRequestErrorPage();
client_addHtaccessGroup();

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'client/puser_gadd.tpl',
    'page_message' => 'layout',
]);

$tpl->assign([
    'TR_PAGE_TITLE'     => tr('Client / Webtools / Protected Areas / Manage Users and Groups / Add Group'),
    'TR_HTACCESS_GROUP' => tr('Htaccess group'),
    'TR_GROUPNAME'      => tr('Group name'),
    'GROUPNAME'         => (isset($_POST['groupname'])) ? tohtml($_POST['groupname']) : '',
    'TR_ADD_GROUP'      => tr('Add'),
    'TR_CANCEL'         => tr('Cancel')
]);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
