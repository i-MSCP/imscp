<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP Team
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

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);
check_login('admin');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'admin/manage_users.tpl',
    'page_message' => 'layout',
    'admin_message' => 'page',
    'admin_list' => 'page',
    'admin_item' => 'admin_list',
    'admin_delete_link' => 'admin_item',
    'rsl_message' => 'page',
    'rsl_list' => 'page',
    'rsl_item' => 'rsl_list',
    'usr_message' => 'page',
    'search_form' => 'page',
    'usr_list' => 'page',
    'usr_item' => 'usr_list',
    'domain_status_change' => 'usr_item',
    'domain_status_nochange' => 'usr_item',
    'user_details' => 'usr_list',
    'usr_status_reload_true' => 'usr_item',
    'usr_status_reload_false' => 'usr_item',
    'usr_delete_show' => 'usr_item',
    'usr_delete_link' => 'usr_item',
    'icon' => 'usr_item',
    'scroll_prev_gray' => 'page',
    'scroll_prev' => 'page',
    'scroll_next_gray' => 'page',
    'scroll_next' => 'page'
));

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Admin / Users / Overview'),
    'TR_NEXT' => tr('Next'),
    'TR_PREVIOUS' => tr('Previous')
));

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
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
