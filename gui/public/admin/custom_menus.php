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
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
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
			$menuOrder = $stmt->fields['menu_order'];
			$menuName = $stmt->fields['menu_name'];
			$menuLink = $stmt->fields['menu_link'];

			if ($menuLevel == 'A') {
				$menuLevel = tr('Administrator');
			} elseif ($menuLevel == 'R') {
				$menuLevel = tr('Reseller');
			} elseif ($menuLevel == 'C') {
				$menuLevel = tr('Customer');
			} elseif ($menuLevel == 'AR') {
				$menuLevel = tr('Administrator and reseller');
			} elseif ($menuLevel == 'AC') {
				$menuLevel = tr('Administrator and customer');
			} elseif ($menuLevel == 'RC') {
				$menuLevel = tr('Reseller and customer');
			} elseif ($menuLevel == 'ARC') {
				$menuLevel = tr('All');
			}

			$tpl->assign(
				array(
					'MENU_LINK' => tohtml($menuLink),
					'MENU_ID' => $menuId,
					'LEVEL' => tohtml($menuLevel),
					'ORDER' => $menuOrder,
					'MENU_NAME' => tohtml($menuName),
					'LINK' => tohtml($menuLink)
				)
			);

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
	$selected = $cfg->HTML_SELECTED;

	$customMenu = array(
		'menu_id' => '', 'menu_name' => '', 'menu_link' => '', 'menu_target' => '_self', 'menu_level' => 'a',
		'menu_order' => ''
	);

	if (empty($_POST) && isset($_GET['edit_id'])) {
		$query = "SELECT * FROM `custom_menus` WHERE `menu_id` = ?";
		$stmt = exec_query($query, (int)$_GET['edit_id']);

		if (!$stmt->rowCount()) {
			set_page_message(tr("The menu you are trying to edit doesn't exist."), 'error');
			redirectTo('custom_menus.php');
		}

		$customMenu = $stmt->fetchRow();
	} elseif (!empty($_POST)) {
		$customMenu = $_POST;
	}

	if (isset($_REQUEST['edit_id'])) {
		$tpl->assign(
			array(
				'TR_DYNAMIC_TITLE' => tr('Edit custom menu'),
				'TR_UPDATE' => tr('Update'),
				'EDIT_ID' => tohtml($_REQUEST['edit_id']),
				'ADD_MENU' => ''
			)
		);
	} else {
		$tpl->assign(
			array(
				'TR_DYNAMIC_TITLE' => tr('Add custom menu'),
				'TR_ADD' => tr('Add'),
				'EDIT_MENU' => ''
			)
		);
	}

	foreach (array('_blank', '_parent', '_self', '_top') as $target) {
		$tpl->assign(
			array(
				'TR_TARGET' => tr('%s page', str_replace('_', '', $target)),
				'TARGET_VALUE' => $target,
				'SELECTED_TARGET' => ($customMenu['menu_target'] == $target) ? $selected : ''
			)
		);

		$tpl->parse('MENU_TARGET_BLOCK', '.menu_target_block');
	}

	foreach (
		array(
			'A' => tr('Administrator level'), 'R' => tr('Reseller level'), 'C' => tr('Customer level'),
			'AR' => tr('Administrator and Reseller levels'), 'AC' => tr('Administrator and customer levels'),
			'RC' => tr('Reseller and customer levels'), 'ARC' => tr('All levels')
		) as $level => $trLevel
	) {
		$tpl->assign(
			array(
				'TR_LEVEL' => $trLevel,
				'LEVEL_VALUE' => $level,
				'SELECTED_LEVEL' => ($customMenu['menu_level'] == $level) ? $selected : ''
			)
		);

		$tpl->parse('MENU_LEVEL_BLOCK', '.menu_level_block');
	}

	$tpl->assign(
		array(
			'MENU_NAME' => tohtml($customMenu['menu_name']),
			'MENU_LINK' => tohtml($customMenu['menu_link']),
			'MENU_ORDER' => $customMenu['menu_order']
		)
	);
}

/**
 * Check if menu is valid.
 *
 * @param string $menuName Menu name
 * @param string $menuLink Menu link
 * @param string $menuTarget Menu target
 * @param string $menuLevel Menu level
 * @param int $menuOrder Menu order
 * @return bool TRUE if menu data are valid, FALSE otherwise
 */
function admin_isValidMenu($menuName, $menuLink, $menuTarget, $menuLevel, $menuOrder)
{

	$errorFieldsStack = array();

	if (empty($menuName)) {
		set_page_message(tr('Invalid name.'), 'error');
		$errorFieldsStack[] = 'menu_name';
	}

	if (empty($menuLink) || !filter_var(
			$menuLink, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)
	) {
		set_page_message(tr('Invalid URL.'), 'error');
		$errorFieldsStack[] = 'menu_link';
	}

	if (!empty($menuTarget) && !in_array($menuTarget, array('_blank', '_parent', '_self', '_top'))) {
		set_page_message(tr('Invalid target.'), 'error');
		$errorFieldsStack[] = 'menu_target';
	}

	if (!in_array($menuLevel, array('A', 'R', 'C', 'AR', 'AC', 'RC', 'ARC'))) {
		showBadRequestErrorPage();
	}

	if (!empty($menuOrder) && !is_numeric($menuOrder)) {
		set_page_message(tr('Invalid menu order.'), 'error');
		$errorFieldsStack[] = 'menu_order';
	}

	if (Zend_Session::namespaceIsset('pageMessages')) {
		iMSCP_Registry::set('errorFieldsStack', $errorFieldsStack);
		return false;
	}

	return true;
}

/**
 * Add custom menu.
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function admin_addMenu()
{
	$menuName = isset($_POST['menu_name']) ? clean_input($_POST['menu_name']) : '';
	$menuLink = isset($_POST['menu_link']) ? clean_input($_POST['menu_link']) : '';
	$menuTarget = isset($_POST['menu_target']) ? clean_input($_POST['menu_target']) : '';
	$visibilityLevel = isset($_POST['menu_level']) ? clean_input($_POST['menu_level']) : '';
	$menuOrder = isset($_POST['menu_order']) ? clean_input($_POST['menu_order']) : null;

	if (admin_isValidMenu($menuName, $menuLink, $menuTarget, $visibilityLevel, $menuOrder)) {
		$query = "
			INSERT INTO
				`custom_menus` (
					`menu_level`, `menu_order`, `menu_name`, `menu_link`, `menu_target`
				) VALUES (
					?, ?, ?, ?, ?
				)
		";
		exec_query($query, array($visibilityLevel, $menuOrder, $menuName, $menuLink, $menuTarget));

		set_page_message(tr('Custom menu successfully added.'), 'success');

		return true;
	}

	return false;
}

/**
 * Update custom menu.
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
	$menuOrder = isset($_POST['menu_order']) ? clean_input($_POST['menu_order']) : null;

	if (admin_isValidMenu($menuName, $menuLink, $menuTarget, $menuLevel, $menuOrder)) {
		$query = "
			UPDATE
				`custom_menus`
			SET
				`menu_level` = ?, `menu_order` = ?, `menu_name` = ?, `menu_link` = ?, `menu_target` = ?
			WHERE
				`menu_id` = ?
		";
		exec_query($query, array($menuLevel, $menuOrder, $menuName, $menuLink, $menuTarget, (int)$menuId));

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
	$stmt = exec_query($query, (int)$menuId);

	if ($stmt->rowCount()) {
		set_page_message(tr('Custom menu successfully deleted.'), 'success');
	}
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

if (isset($_POST['uaction'])) {
	if ($_POST['uaction'] == 'menu_add') {
		if (admin_addMenu()) {
			redirectTo('custom_menus.php');
		}
	} elseif ($_POST['uaction'] == 'menu_update' && isset($_POST['edit_id'])) {
		if (admin_updateMenu($_POST['edit_id'])) {
			redirectTo('custom_menus.php');
		}
	} else {
		showBadRequestErrorPage();
	}
} elseif (isset($_GET['delete_id'])) {
	admin_deleteMenu($_GET['delete_id']);
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/custom_menus.tpl',
		'page_message' => 'layout',
		'hosting_plans' => 'page',
		'menus_list_block' => 'page',
		'menu_block' => 'menus_list_block',
		'menu_target_block' => 'page',
		'menu_level_block' => 'page',
		'add_menu' => 'page',
		'edit_menu' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings / {TR_DYNAMIC_TITLE}'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_CUSTOM_MENU_PROPERTIES' => tr('Custom menu properties'),
		'TR_MENU_NAME' => tr('Name'),
		'TR_MENU_LINK' => tr('Link'),
		'TR_MENU_TARGET' => tr('Target'),
		'TR_VIEW_FROM' => tr('Show in'),
		'TR_MENU_NAME_AND_LINK' => tr('Custom menu name and link'),
		'TR_MENU_ORDER' => tr('Order'),
		'TR_OPTIONAL' => tr('Optional'),
		'TR_ACTIONS' => tr('Actions'),
		'TR_EDIT' => tr('Edit'),
		'TR_DELETE' => tr('Delete'),
		'TR_TH_LEVEL' => tr('Level'),
		'TR_TH_ORDER' => tr('Order'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_MESSAGE_DELETE' => json_encode(tr('Are you sure you want to delete the %s menu?', true, '%s')),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'ERR_FIELDS_STACK' => iMSCP_Registry::isRegistered('errorFieldsStack')
			? json_encode(iMSCP_Registry::get('errorFieldsStack')) : '[]'
	)
);

generateNavigation($tpl);
admin_generateMenusList($tpl);
admin_generateForm($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(
	iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl)
);

$tpl->prnt();

unsetMessages();
