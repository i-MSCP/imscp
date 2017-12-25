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
 * Generates page
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function generatePage($tpl)
{
    $stmt = exec_query('SELECT domain_created FROM admin WHERE admin_id = ?', [$_SESSION['user_id']]);
    $row = $stmt->fetch();
    $tpl->assign([
        'TR_ACCOUNT_SUMMARY'   => tr('Account summary'),
        'TR_USERNAME'          => tr('Username'),
        'USERNAME'             => tohtml($_SESSION['user_logged']),
        'TR_ACCOUNT_TYPE'      => tr('Account type'),
        'ACCOUNT_TYPE'         => tr('Customer'),
        'TR_REGISTRATION_DATE' => tr('Registration date'),
        'REGISTRATION_DATE'    => ($row['domain_created'] != 0)
            ? tohtml(date(Registry::get('config')['DATE_FORMAT'], $row['domain_created'])) : tr('Unknown')
    ]);
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'shared/partials/profile.tpl',
    'page_message' => 'layout'
]);
$tpl->assign('TR_PAGE_TITLE', tr('Client / Profile / Account Summary'));

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
