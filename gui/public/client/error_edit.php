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

use iMSCP\VirtualFileSystem as VirtualFileSystem;
use iMSCP_Registry as Registry;
use iMSCP\TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Write error page
 *
 * @param int $eid Error page unique identifier
 * @return bool TRUE on success, FALSE otherwise
 */
function writeErrorPage($eid)
{
    $vfs = new VirtualFileSystem($_SESSION['user_logged'], '/errors');
    return $vfs->put($eid . '.html', $_POST['error']);
}

/**
 * Edit an error page
 *
 * @param int $eid Error page unique identifier
 * @return TRUE on success, FALSE on failure
 */
function editErrorPage($eid)
{
    if (!isset($_POST['error'])) {
        showBadRequestErrorPage();
    }

    if (in_array($eid, [401, 403, 404, 500, 503]) && writeErrorPage($eid)) {
        set_page_message(tr('Custom error page updated.'), 'success');
        return true;
    }

    set_page_message(tr('System error - custom error page was not updated.'), 'error');
    return false;
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @param int $eid Error page unique identifier
 * @return void
 */
function generatePage($tpl, $eid)
{
    $vfs = new VirtualFileSystem($_SESSION['user_logged'], '/errors');
    $errorPageContent = $vfs->get($eid . '.html');
    $tpl->assign('ERROR', ($errorPageContent !== false) ? tohtml($errorPageContent) : '');
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);

if (!customerHasFeature('custom_error_pages') || !isset($_REQUEST['eid'])) {
    showBadRequestErrorPage();
}

$eid = intval($_REQUEST['eid']);

if (!in_array($eid, ['401', '403', '404', '500', '503'])) {
    showBadRequestErrorPage();
}

if (!empty($_POST) && editErrorPage($eid)) {
    redirectTo('error_pages.php');
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'client/error_edit.tpl',
    'page_message' => 'layout'
]);
$tpl->assign([
    'TR_PAGE_TITLE'      => tr(' Client / Webtools / Custom Error Pages / Edit Custom Error Page'),
    'TR_ERROR_EDIT_PAGE' => tr('Edit error page'),
    'TR_SAVE'            => tr('Save'),
    'TR_CANCEL'          => tr('Cancel'),
    'EID'                => $eid
]);

generateNavigation($tpl);
generatePage($tpl, $eid);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
