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
 * Generate custom menus list.
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
			} else if ($menuLevel == 'reseller') {
				$menuLevel = tr('Reseller');
			} else if ($menuLevel == 'user') {
				$menuLevel = tr('User');
			} else if ($menuLevel == 'all') {
				$menuLevel = tr('All');
			}

			$tpl->assign(
				array(
					'BUTTON_LINK' => tohtml($menuLink),
					'BUTONN_ID' => $menuId,
					'LEVEL' => tohtml($menuLevel),
					'MENU_NAME' => tohtml($menuName),
					'MENU_NAME2' => addslashes(clean_html($menuName)),
					'LINK' => tohtml($menuLink)));

			$tpl->parse('BUTTON_BLOCK', '.button_block');
			$stmt->moveNext();
		}
	}
}

/**
 * Add custom menu.
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function admin_addMenu()
{
	if (!isset($_POST['uaction'])) {
		return false;
	} else if ($_POST['uaction'] != 'add_menu') {
		return false;
	}

	$menuName = clean_input($_POST['bname']);
	$menuLink = clean_input($_POST['blink']);
	$menuTarget = clean_input($_POST['btarget']);
	$menuView = clean_input($_POST['bview']);

	if (empty($menuName) || empty($menuLink)) {
		set_page_message(tr('Wrong request.'), 'error');
		return false;
	}

	if (!filter_var($menuLink, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)) {
		set_page_message(tr('Invalid URL.'), 'error');
		return false;
	}

	if (!empty($menuTarget) &&
		!in_array($menuTarget, array('_blank', '_parent', '_self', '_top'))
	) {
		set_page_message(tr('Invalid target.'), 'error');
		return false;
	}

	$query = "
		INSERT INTO
			`custom_menus` (
				`menu_level`, `menu_name`, `menu_link`, `menu_target`
			) VALUES (
				?, ?, ?, ?
			)
	";
	exec_query($query, array($menuView, $menuName, $menuLink, $menuTarget));

	set_page_message(tr('Custom button successfully added.'), 'success');

	return true;
}

/**
 * Delete custom menu.
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function admin_deleteMenu()
{
	if (empty($_GET['delete_id']) || !is_numeric($_GET['delete_id'])) {
		set_page_message(tr('Wrong request.'), 'error');
		return false;
	}

	$query = "DELETE FROM `custom_menus` WHERE `menu_id` = ?";
	exec_query($query, (int)$_GET['delete_id']);

	set_page_message(tr('Custom menu successfully deleted.'), 'success');
	return true;
}

/**
 * Update custom menu.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return bool TRUE on success, FALSE otherwise
 */
function admin_editMenu($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($_GET['edit_id'] === '' || !is_numeric($_GET['edit_id'])) {
		set_page_message(tr('Wrong request.'), 'error');
		return false;
	}

	$query = "SELECT * FROM `custom_menus` WHERE `menu_id` = ?";
	$stmt = exec_query($query, (int)$_GET['edit_id']);

	if ($stmt->rowCount() == 0) {
		set_page_message(tr("The menu you trying to edit doesn't exist"), 'error');
		$tpl->assign('EDIT_MENU', '');
		return FALSE;
	} else {
		$tpl->assign('ADD_MENU', '');

		$menuName = $stmt->fields['menu_name'];
		$menuLink = $stmt->fields['menu_link'];
		$menuTarget = $stmt->fields['menu_target'];
		$menuView = $stmt->fields['menu_level'];

		if ($menuView == 'admin') {
			$adminView = $cfg->HTML_SELECTED;
			$resellerView = '';
			$userView = '';
			$allView = '';
		} else if ($menuView == 'reseller') {
			$adminView = '';
			$resellerView = $cfg->HTML_SELECTED;
			$userView = '';
			$allView = '';
		} else if ($menuView == 'user') {
			$adminView = '';
			$resellerView = '';
			$userView = $cfg->HTML_SELECTED;
			$allView = '';
		} else {
			$adminView = '';
			$resellerView = '';
			$userView = '';
			$allView = $cfg->HTML_SELECTED;
		}

		$tpl->assign(
			array(
				'MENU_NAME' => tohtml($menuName),
				'MENU_LINK' => tohtml($menuLink),
				'MENU_TARGET' => tohtml($menuTarget),
				'ADMIN_VIEW' => $adminView,
				'RESELLER_VIEW' => $resellerView,
				'USER_VIEW' => $userView,
				'ALL_VIEW' => $allView,
				'EID' => $_GET['edit_id']));

		$tpl->parse('EDIT_MENU', '.edit_menu');
	}

	return true;
}

/**
 * Update custom menu.
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function admin_updateMenu()
{
	if (!isset($_POST['uaction'])) {
		return false;
	} elseif ($_POST['uaction'] != 'edit_menu') {
		return false;
	}

	$menuName = clean_input($_POST['bname']);
	$menuLink = clean_input($_POST['blink']);
	$menuTarget = clean_input($_POST['btarget']);
	$menuView = clean_input($_POST['bview']);
	$menuId = clean_input($_POST['eid']);

	if (empty($menuName) || empty($menuLink) || empty($menuId)) {
		set_page_message(tr('Wrong request.'), 'error');
		return false;
	}

	if (!filter_var($menuLink, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)) {
		set_page_message(tr('Invalid URL.'), 'error');
		return false;
	}

	if (!empty($menuTarget) &&
		!in_array($menuTarget, array('_blank', '_parent', '_self', '_top'))
	) {
		set_page_message(tr('Invalid target.'), 'error');
		return false;
	}

	$query = "
		UPDATE
			`custom_menus`
		SET
			`menu_level` = ?, `menu_name` = ?, `menu_link` = ?, `menu_target` = ?
		WHERE
			`menu_id` = ?
	";
	exec_query($query, array($menuView, $menuName, $menuLink, $menuTarget, $menuId));

	set_page_message(tr('Custom menu successfully updated.'), 'success');

	return true;
}

/*************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'page' => $cfg->ADMIN_TEMPLATE_PATH . '/custom_menus.tpl',
		'page_message' => 'page',
		'hosting_plans' => 'page',
		'menus_list_block' => 'page',
		'menu_block' => 'buttons_list_block',
		'add_menu' => 'page',
		'edit_menu' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin - Manage custom menus'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_TITLE_CUSTOM_MENUS' => tr('Manage custom menus'),
		'TR_ADD_NEW_MENU' => tr('Add custom menu'),
		'TR_MENU_NAME' => tr('Menu name'),
		'TR_MENU_LINK' => tr('Menu link'),
		'TR_MENU_TARGET' => tr('Menu target'),
		'TR_VIEW_FROM' => tr('Show in'),
		'ADMIN' => tr('Administrator level'),
		'RESELLER' => tr('Reseller level'),
		'USER' => tr('Enduser level'),
		'RESSELER_AND_USER' => tr('Reseller and end-user level'),
		'TR_ADD' => tr('Add'),
		'TR_MENU_NAME' => tr('Menu button'),
		'TR_ACTON' => tr('Action'),
		'TR_EDIT' => tr('Edit'),
		'TR_DELETE' => tr('Delete'),
		'TR_LEVEL' => tr('Level'),
		'TR_SAVE' => tr('Save'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_EDIT_MENU' => tr('Edit menu'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete the %s menu?', true, '%s')));

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');

admin_addMenu();

if (isset($_GET['delete_id'])) {
	admin_deleteMenu();
}

if (isset($_GET['edit_id'])) {
	admin_editMenu($tpl);
}

admin_updateMenu();
admin_generateMenusList($tpl);

generatePageMessage($tpl);

if (isset($_GET['edit_id'])) {
	$tpl->assign('ADD_MENU', '');
} else {
	$tpl->assign('EDIT_MENU', '');
}

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
