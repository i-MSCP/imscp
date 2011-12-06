<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2011 by i-MSCP | http://i-mscp.net
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 */

/*************************************************************************************
 * Script functions
 */

/**
 * Generates menus list.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return void
 */
function admin_generateMenusList($tpl)
{
	$query = "SELECT * FROM `custom_menus`";
	$stmt = execute_query($query);

	if (!$stmt->rowCount()) {
		$tpl->assign('MENUS_LIST_BLOCK', '');
		set_page_message(tr('No custom menu found.'), 'info');
	} else {
		while (!$stmt->EOF) {
			$menuId = $stmt->fields['menu_id'];
			$menuLevel = $stmt->fields['menu_level'];
			$menuName = $stmt->fields['menu_name'];
			$menuLink = $stmt->fields['menu_link'];

			if ($menuLevel == 'admin') {
				$menuLevel = tr('Administrator');
			} elseif ($menuLevel == 'reseller') {
				$menuLevel = tr('Reseller');
			} elseif ($menuLevel == 'user') {
				$menuLevel = tr('User');
			} elseif ($menuLevel == 'all') {
				$menuLevel = tr('All');
			}

			$tpl->assign(
				array(
					'MENU_LINK' => tohtml($menuLink),
					'MENU_ID' => $menuId,
					'LEVEL' => tohtml($menuLevel),
					'MENU_NAME' => tohtml($menuName),
					'LINK' => tohtml($menuLink)));

			$tpl->parse('MENU_BLOCK', '.menu_block');
			$stmt->moveNext();
		}
	}
}

/**
 * Generate form.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 */
function admin_generateForm($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$customMenu = array(
		'menu_id' => '', 'menu_name' => '', 'menu_link' => '',
		'menu_target' => '', 'menu_level' => 'admin');


	if (empty($_POST) && isset($_GET['edit_id'])) {
		$query = "SELECT * FROM `custom_menus` WHERE `menu_id` = ?";
		$stmt = exec_query($query, (int)$_GET['edit_id']);

		if (!$stmt->rowCount()) {
			set_page_message(tr("The menu you trying to edit doesn't exist."), 'error');
			redirectTo('custom_menus.php');
		}

		$customMenu = $stmt->fetchRow();
	} elseif(!empty($_POST)) {
		$customMenu = $_POST;
	}

	if(isset($_REQUEST['edit_id'])) {
		$tpl->assign(
			array(
				'TR_FORM_NAME' => 'Edit menu',
				'TR_UPDATE' => tr('Update'),
				'EDIT_ID' => tohtml($_REQUEST['edit_id']),
				'ADD_MENU' => ''));
	} else {
		$tpl->assign(
			array(
				'TR_FORM_NAME' => 'Add menu',
				'TR_ADD' => 'Add',
				'EDIT_MENU' => ''));
	}

	$adminView = $resellerView = $userView = $allView = '';

	if ($customMenu['menu_level'] == 'admin') {
		$adminView = $cfg->HTML_SELECTED;
	} elseif ($customMenu['menu_level'] == 'reseller') {
		$resellerView = $cfg->HTML_SELECTED;
	} elseif ($customMenu['menu_level'] == 'user') {
		$userView = $cfg->HTML_SELECTED;
	} else {
		$allView = $cfg->HTML_SELECTED;
	}

	$tpl->assign(
		array(
			'MENU_NAME' => tohtml($customMenu['menu_name']),
			'MENU_LINK' => tohtml($customMenu['menu_link']),
			'MENU_TARGET' => tohtml($customMenu['menu_target']),
			'ADMIN_VIEW' => $adminView,
			'RESELLER_VIEW' => $resellerView,
			'USER_VIEW' => $userView,
			'ALL_VIEW' => $allView));
}

/**
 * Check menu.
 *
 * @param $menuName Menu name
 * @param $menuLink Menu link
 * @param $menuTarget Menu target
 * @param $menuLevel Menu level
 * @return bool TRUE if menu data are valid, FALSE otherwise
 */
function admin_isValidMenu($menuName, $menuLink, $menuTarget, $menuLevel) {

	$errorFieldsStack = array();

	if(empty($menuName)) {
		set_page_message(tr('Invalid name.'), 'error');
		$errorFieldsStack[] = 'menu_name';
	}

	if (empty($menuLink) || !filter_var($menuLink, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)) {
		set_page_message(tr('Invalid URL.'), 'error');
		$errorFieldsStack[] = 'menu_link';
	}

	if (!empty($menuTarget) && !in_array($menuTarget, array('_blank', '_parent', '_self', '_top'))) {
		set_page_message(tr('Invalid target.'), 'error');
		$errorFieldsStack[] = 'menu_target';
	}

	if(!in_array($menuLevel, array('admin', 'reseller', 'user', 'all'))) {
		set_page_message(tr('Wrong request.'), 'error');
	}

	if(Zend_Session::namespaceIsset('pageMessages')) {
		iMSCP_Registry::set('errorFieldsStack', $errorFieldsStack);
		return false;
	}

	return true;
}

/**
 * Add menu.
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function admin_addMenu()
{
	$menuName = isset($_POST['menu_name']) ? clean_input($_POST['menu_name']) : '';
	$menuLink = isset($_POST['menu_link']) ? clean_input($_POST['menu_link']) : '';
	$menuTarget = isset($_POST['menu_target']) ? clean_input($_POST['menu_target']) : '';
	$menuView = isset($_POST['menu_level']) ? clean_input($_POST['menu_level']) : '';

	if(admin_isValidMenu($menuName, $menuLink, $menuTarget, $menuView)) {
		$query = "
			INSERT INTO
				`custom_menus` (
					`menu_level`, `menu_name`, `menu_link`, `menu_target`
				) VALUES (
					?, ?, ?, ?
				)
		";
		exec_query($query, array($menuView, $menuName, $menuLink, $menuTarget));

		set_page_message(tr('Custom menu successfully added.'), 'success');

		return true;
	}

	return false;
}

/**
 * Update menu.
 *
 * @param int $menuId menu unique identifier
 * @return bool TRUE on success, FALSE otherwise
 */
function admin_updateMenu($menuId)
{
	$menuName = isset($_POST['menu_name']) ? clean_input($_POST['menu_name']) : '';
	$menuLink = isset($_POST['menu_link']) ? clean_input($_POST['menu_link']) : '';
	$menuTarget = isset($_POST['menu_target']) ? clean_input($_POST['menu_target']) : '';
	$menuLevel = isset($_POST['menu_level']) ? clean_input($_POST['menu_level']) : '';

	if(admin_isValidMenu($menuName, $menuLink, $menuTarget, $menuLevel)) {
		$query = "
			UPDATE
				`custom_menus`
			SET
				`menu_level` = ?, `menu_name` = ?, `menu_link` = ?, `menu_target` = ?
			WHERE
				`menu_id` = ?
		";
		exec_query($query, array($menuLevel, $menuName, $menuLink, $menuTarget, (int)$menuId));

		set_page_message(tr('Custom menu successfully updated.'), 'success');

		return true;
	}

	return false;
}

/**
 * Delete custom menu.
 *
 * @param int $menuId menu unique identifier
 * @return void
 */
function admin_deleteMenu($menuId)
{
	$query = "DELETE FROM `custom_menus` WHERE `menu_id` = ?";
	$stmt = exec_query($query, (int) $menuId);

	if($stmt->rowCount()) {
		set_page_message(tr('Custom menu successfully deleted.'), 'success');
	}
}

/*************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

if(isset($_POST['uaction'])) {
	if($_POST['uaction'] == 'menu_add') {
		if(admin_addMenu()) {
			redirectTo('custom_menus.php');
		}
	} elseif($_POST['uaction'] == 'menu_update' && isset($_POST['edit_id'])) {
		if(admin_updateMenu($_POST['edit_id'])) {
			redirectTo('custom_menus.php');
		}
	} else {
		set_page_message(tr('Wrong request.'), 'error');
	}
} elseif(isset($_GET['delete_id'])) {
	admin_deleteMenu($_GET['delete_id']);
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'page' => $cfg->ADMIN_TEMPLATE_PATH . '/custom_menus.tpl',
		'page_message' => 'page',
		'hosting_plans' => 'page',
		'menus_list_block' => 'page',
		'menu_block' => 'menus_list_block',
		'add_menu' => 'page',
		'edit_menu' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin - Manage custom menus'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_TITLE_CUSTOM_MENUS' => tr('Manage custom menus'),
		'TR_MENU_NAME' => tr('Menu name'),
		'TR_MENU_LINK' => tr('Menu link'),
		'TR_MENU_TARGET' => tr('Menu target'),
		'TR_VIEW_FROM' => tr('Show in'),
		'ADMIN' => tr('Administrator level'),
		'RESELLER' => tr('Reseller level'),
		'USER' => tr('End-user level'),
		'RESSELER_AND_USER' => tr('Reseller and End-user levels'),
		'TR_MENU_NAME' => tr('Menu button'),
		'TR_ACTONS' => tr('Actions'),
		'TR_EDIT' => tr('Edit'),
		'TR_DELETE' => tr('Delete'),
		'TR_LEVEL' => tr('Level'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete the %s menu?', true, '%s'),
		'ERR_FIELDS_STACK' => iMSCP_Registry::isRegistered('errorFieldsStack')
			? json_encode(iMSCP_Registry::get('errorFieldsStack')) : '[]'));

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');
admin_generateMenusList($tpl);
admin_generateForm($tpl);
generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
