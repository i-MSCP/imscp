<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/manage_users.tpl',
		'page_message' => 'layout',
		'admin_message' => 'page',
		'admin_list' => 'page',
		'admin_item' => 'admin_list',
		'admin_delete_show' => 'admin_item',
		'admin_delete_link' => 'admin_item',
		'rsl_message' => 'page',
		'rsl_list' => 'page',
		'rsl_item' => 'rsl_list',
		'rsl_delete_show' => 'rsl_item',
		'rsl_delete_link' => 'rsl_item',
		'usr_message' => 'page',
		'search_form' => 'page',
		'usr_list' => 'page',
		'usr_item' => 'usr_list',
		'user_details' => 'usr_list',
		'usr_status_reload_true' => 'usr_item',
		'usr_status_reload_false' => 'usr_item',
		'usr_delete_show' => 'usr_item',
		'usr_delete_link' => 'usr_item',
		'icon' => 'usr_item',
		'scroll_prev_gray' => 'page',
		'scroll_prev' => 'page',
		'scroll_next_gray' => 'page',
		'scroll_next' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Users / Overview'),
		'TR_NEXT' => tr('Next'),
		'TR_PREVIOUS' => tr('Previous'),
		'ISP_LOGO' => layout_getUserLogo()));

if (isset($_POST['details']) && !empty($_POST['details'])) {
	$_SESSION['details'] = $_POST['details'];
} else {
	if (!isset($_SESSION['details'])) {
		$_SESSION['details'] = "hide";
	}
}

if (isset($_SESSION['user_added'])) {
	unset($_SESSION['user_added']);
	set_page_message(tr('Customer scheduled for addition.'), 'success');
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
	set_page_message(tr('Email updated.'), 'success');
} elseif (isset($_SESSION['hdomain'])) {
	unset($_SESSION['hdomain']);
	set_page_message(tr('The reseller you want to remove has one or more customers accounts.<br />Remove them first.'), 'error');
}

if (!$cfg->exists('HOSTING_PLANS_LEVEL') || strtolower($cfg->HOSTING_PLANS_LEVEL) !== 'admin') {
	$tpl->assign('EDIT_OPTION', '');
}

generateNavigation($tpl);
get_admin_manage_users($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
