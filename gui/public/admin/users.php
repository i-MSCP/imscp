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
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptStart);

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                         => 'shared/layouts/ui.tpl',
    'page'                           => 'admin/users.phtml',
    'page_message'                   => 'layout',
    'administrator_message'          => 'page',
    'administrator_list'             => 'page',
    'administrator_item'             => 'administrator_list',
    'administrator_delete_link'      => 'administrator_item',
    'reseller_message'               => 'page',
    'reseller_list'                  => 'page',
    'reseller_item'                  => 'reseller_list',
    'client_search_form'             => 'page',
    'client_show_domain_aliases_blk' => 'client_search_form',
    'client_domain_aliases_switch'   => 'client_search_form',
    'client_domain_aliases_show'     => 'client_domain_aliases_switch',
    'client_domain_aliases_hide'     => 'client_domain_aliases_switch',
    'client_message'                 => 'page',
    'client_list'                    => 'page',
    'client_item'                    => 'client_list',
    'client_domain_status_ok'        => 'client_item',
    'client_domain_status_not_ok'    => 'client_item',
    'client_restricted_links'        => 'client_item',
    'client_domain_alias_blk'        => 'client_item',
    'client_scroll_prev'             => 'client_list',
    'client_scroll_prev_gray'        => 'client_list',
    'client_scroll_next_gray'        => 'client_list',
    'client_scroll_next'             => 'client_list'
]);

$tpl->assign([
    'TR_PAGE_TITLE' => tr('Admin / Users / Overview'),
]);

if (isset($_POST['details']) && !empty($_POST['details'])) {
    $_SESSION['details'] = $_POST['details'];
} elseif (!isset($_SESSION['details'])) {
    $_SESSION['details'] = 'hide';
}

if (isset($_SESSION['user_added'])) {
    unset($_SESSION['user_added']);
    set_page_message(tr('Customer successfully scheduled for addition.'), 'success');
} elseif (isset($_SESSION['reseller_added'])) {
    unset($_SESSION['reseller_added']);
    set_page_message(tr('Reseller successfully added.'), 'success');
} elseif (isset($_SESSION['user_updated'])) {
    unset($_SESSION['user_updated']);
    set_page_message(tr('Customer account successfully updated.'), 'success');
} elseif (isset($_SESSION['user_deleted'])) {
    unset($_SESSION['user_deleted']);
    set_page_message(tr('Customer successfully scheduled for deletion.'), 'success');
} elseif (isset($_SESSION['email_updated'])) {
    unset($_SESSION['email_updated']);
    set_page_message(tr('Email successfully updated.'), 'success');
} elseif (isset($_SESSION['hdomain'])) {
    unset($_SESSION['hdomain']);
    set_page_message(tr('The reseller you want to remove has one or more customers accounts. Please remove them first.'), 'error');
}

generateNavigation($tpl);
get_admin_manage_users($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
