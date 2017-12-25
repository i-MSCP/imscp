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

use iMSCP\Crypt as Crypt;
use iMSCP_Registry as Registry;
use iMSCP\TemplateEngine;

/***********************************************************************************************************************
 * Functions
 *
 */

/**
 * Updates htaccess user
 *
 * @param int $domainId Domain unique identifier
 * @param int $htuserId Htaccess user unique identifier
 * @return void
 */
function client_updateHtaccessUser($domainId, $htuserId)
{
    if (empty($_POST))
        return;

    if (!isset($_POST['pass']) || !isset($_POST['pass_rep'])) {
        showBadRequestErrorPage();
    }

    if ($_POST['pass'] !== $_POST['pass_rep']) {
        set_page_message(tr('Passwords do not match.'), 'error');
        return;
    }

    if (!checkPasswordSyntax($_POST['pass'])) {
        return;
    }

    exec_query('UPDATE htaccess_users SET upass = ?, status = ? WHERE id = ? AND dmn_id = ?', [
        Crypt::apr1MD5($_POST['pass']), 'tochange', $htuserId, $domainId
    ]);

    send_request();
    write_log(sprintf('%s updated htaccess user ID: %s', $_SESSION['user_logged'], $htuserId), E_USER_NOTICE);
    redirectTo('protected_user_manage.php');
}

/***********************************************************************************************************************
 * Main script
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('protected_areas') or showBadRequestErrorPage();

if (!isset($_REQUEST['uname'])) {
    showBadRequestErrorPage();
}

$htuserId = intval($_REQUEST['uname']);
$domainId = get_user_domain_id($_SESSION['user_id']);

$stmt = exec_query('SELECT uname FROM htaccess_users WHERE id = ? AND dmn_id = ?', [$htuserId, $domainId]);

if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

$row = $stmt->fetch();

client_updateHtaccessUser($domainId, $htuserId);

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'client/puser_edit.tpl',
    'page_message' => 'layout'
]);
$tpl->assign([
    'TR_PAGE_TITLE'      => tr('Client / Webtools / Protected Areas / Manage Users and Groups / Edit User'),
    'TR_HTACCESS_USER'   => tr('Htaccess user'),
    'TR_USERNAME'        => tr('Username'),
    'UNAME'              => tohtml($row['uname']),
    'TR_PASSWORD'        => tr('Password'),
    'TR_PASSWORD_REPEAT' => tr('Repeat password'),
    'UID'                => tohtml($htuserId),
    'TR_UPDATE'          => tr('Update'),
    'TR_CANCEL'          => tr('Cancel')
]);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
