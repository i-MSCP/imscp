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
 */

/**
 * Add Htaccess user
 *
 * @return void
 */
function client_addHtaccessUser()
{
    if (empty($_POST))
        return;

    if (!isset($_POST['username']) || !isset($_POST['pass']) || !isset($_POST['pass_rep'])) {
        showBadRequestErrorPage();
    }

    $uname = clean_input($_POST['username']);

    if (!validates_username($_POST['username'])) {
        set_page_message(tr('Wrong username.'), 'error');
        return;
    }

    $passwd = clean_input($_POST['pass']);

    if ($passwd !== $_POST['pass_rep']) {
        set_page_message(tr('Passwords do not match.'), 'error');
        return;
    }

    if (!checkPasswordSyntax($passwd)) {
        return;
    }

    $domainId = get_user_domain_id($_SESSION['user_id']);

    $stmt = exec_query('SELECT id FROM htaccess_users WHERE uname = ? AND dmn_id = ?', [$uname, $domainId]);
    if ($stmt->rowCount()) {
        set_page_message(tr('This htaccess user already exist.'), 'error');
        return;
    }

    exec_query("INSERT INTO htaccess_users (dmn_id, uname, upass, status) VALUES (?, ?, ?, 'toadd')", [
        $domainId, $uname, Crypt::apr1MD5($passwd)
    ]);
    send_request();
    set_page_message(tr('Htaccess user successfully scheduled for addition.'), 'success');
    write_log(sprintf('%s added new htaccess user: %s', $uname, $_SESSION['user_logged']), E_USER_NOTICE);
    redirectTo('protected_user_manage.php');
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('protected_areas') or showBadRequestErrorPage();
client_addHtaccessUser();

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'client/puser_uadd.tpl',
    'page_message' => 'layout'
]);
$tpl->assign([
    'TR_PAGE_TITLE'      => tr('Client / Webtools / Protected Areas / Manage Users and Groups / Add User'),
    'TR_HTACCESS_USER'   => tr('Htaccess user'),
    'TR_USERNAME'        => tr('Username'),
    'USERNAME'           => (isset($_POST['username'])) ? tohtml($_POST['username']) : '',
    'TR_PASSWORD'        => tr('Password'),
    'TR_PASSWORD_REPEAT' => tr('Repeat password'),
    'TR_ADD_USER'        => tr('Add'),
    'TR_CANCEL'          => tr('Cancel')
]);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
