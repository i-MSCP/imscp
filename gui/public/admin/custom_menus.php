<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 *
 * @license
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

// site functions
function gen_button_list($tpl) {
	$query = "SELECT * FROM `custom_menus`";

	$rs = execute_query($query);
	if ($rs->recordCount() == 0) {
		$tpl->assign('BUTTON_LIST', '');

		set_page_message(tr('You have no custom menus.'), 'info');
	} else {
		global $i;

		while (!$rs->EOF) {
			$menu_id = $rs->fields['menu_id'];
			$menu_level = $rs->fields['menu_level'];
			$menu_name = $rs->fields['menu_name'];
			$menu_link = $rs->fields['menu_link'];

			if ($menu_level === 'admin') {
				$menu_level = tr('Administrator');
			} else if ($menu_level === 'reseller') {
				$menu_level = tr('Reseller');
			} else if ($menu_level === 'user') {
				$menu_level = tr('User');
			} else if ($menu_level === 'all') {
				$menu_level = tr('All');
			}

			$tpl->assign(
				array(
					'BUTTON_LINK'		=> tohtml($menu_link),
					'BUTONN_ID'			=> $menu_id,
					'LEVEL'				=> tohtml($menu_level),
					'MENU_NAME'			=> tohtml($menu_name),
					'MENU_NAME2'		=> addslashes(clean_html($menu_name)),
					'LINK'				=> tohtml($menu_link),
					'CONTENT'			=> ($i % 2 == 0) ? 'content' : 'content2'
				)
			);

			$tpl->parse('BUTTON_LIST', '.button_list');
			$rs->moveNext();
			$i++;
		} // end while
	} // end else
}

function add_new_button() {
	if (!isset($_POST['uaction'])) {
		return;
	} else if ($_POST['uaction'] != 'new_button') {
		return;
	} else {
		$button_name = clean_input($_POST['bname']);
		$button_link = clean_input($_POST['blink']);
		$button_target = clean_input($_POST['btarget']);
		$button_view = $_POST['bview'];

		if (empty($button_name) || empty($button_link)) {
			set_page_message(tr('Missing or incorrect data input!'), 'error');
			return;
		}

		if (!filter_var($button_link, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)) {
			set_page_message(tr('Invalid URL!'), 'error');
			return;
		}

		if (!empty($button_target)
			&& !in_array($button_target, array('_blank', '_parent', '_self', '_top'))) {
			set_page_message(tr('Invalid target!'), 'error');
			return;
		}

		$query = "
			INSERT INTO `custom_menus`
				(
				`menu_level`,
				`menu_name`,
				`menu_link`,
				`menu_target`
				)
			VALUES (?, ?, ?, ?)
		";

		$rs = exec_query($query, array($button_view,
				$button_name,
				$button_link,
				$button_target));

		set_page_message(tr('Custom menu data updated successful!'), 'success');
		return;
	}
}

function delete_button() {
	if ($_GET['delete_id'] === '' || !is_numeric($_GET['delete_id'])) {
		set_page_message(tr('Missing or incorrect data input!'), 'error');
		return;
	} else {
		$delete_id = $_GET['delete_id'];

		$query = "
			DELETE FROM
				`custom_menus`
			WHERE
				`menu_id` = ?
		";

		$rs = exec_query($query, $delete_id);

		set_page_message(tr('Custom menu deleted successful!'), 'success');
		return;
	}
}

function edit_button(&$tpl) {

	$cfg = iMSCP_Registry::get('config');

	if ($_GET['edit_id'] === '' || !is_numeric($_GET['edit_id'])) {
		set_page_message(tr('Missing or incorrect data input!'), 'error');
		return;
	} else {
		$edit_id = $_GET['edit_id'];

		$query = "
			SELECT
				*
			FROM
				`custom_menus`
			WHERE
				`menu_id` = ?
		";

		$rs = exec_query($query, $edit_id);
		if ($rs->recordCount() == 0) {
			set_page_message(tr('Missing or incorrect data input!'), 'error');
			$tpl->assign('EDIT_BUTTON', '');
			return;
		} else {
			$tpl->assign('ADD_BUTTON', '');

			$button_name = $rs->fields['menu_name'];
			$button_link = $rs->fields['menu_link'];
			$button_target = $rs->fields['menu_target'];
			$button_view = $rs->fields['menu_level'];

			if ($button_view === 'admin') {
				$admin_view = $cfg->HTML_SELECTED;
				$reseller_view = '';
				$user_view = '';
				$all_view = '';
			} else if ($button_view === 'reseller') {
				$admin_view = '';
				$reseller_view = $cfg->HTML_SELECTED;
				$user_view = '';
				$all_view = '';
			} else if ($button_view === 'user') {
				$admin_view = '';
				$reseller_view = '';
				$user_view = $cfg->HTML_SELECTED;
				$all_view = '';
			} else {
				$admin_view = '';
				$reseller_view = '';
				$user_view = '';
				$all_view = $cfg->HTML_SELECTED;
			}

			$tpl->assign(
				array(
					'BUTON_NAME'	=> tohtml($button_name),
					'BUTON_LINK'	=> tohtml($button_link),
					'BUTON_TARGET'	=> tohtml($button_target),
					'ADMIN_VIEW'	=> $admin_view,
					'RESELLER_VIEW'	=> $reseller_view,
					'USER_VIEW'		=> $user_view,
					'ALL_VIEW'		=> $all_view,
					'EID'			=> $_GET['edit_id']
				)
			);

			$tpl->parse('EDIT_BUTTON', '.edit_button');
		}
	}
}

function update_button() {

	if (!isset($_POST['uaction'])) {
		return;
	} else if ($_POST['uaction'] != 'edit_button') {
		return;
	} else {
		$button_name = clean_input($_POST['bname']);
		$button_link = clean_input($_POST['blink']);
		$button_target = clean_input($_POST['btarget']);
		$button_view = $_POST['bview'];
		$button_id = $_POST['eid'];

		if (empty($button_name) || empty($button_link) || empty($button_id)) {
			set_page_message(tr('Missing or incorrect data input!'), 'error');
			return;
		}

		if (!filter_var($button_link, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)) {
			set_page_message(tr('Invalid URL!'), 'error');
			return;
		}

		if (!empty($button_target)
			&& !in_array($button_target, array('_blank', '_parent', '_self', '_top'))) {
			set_page_message(tr('Invalid target!'), 'error');
			return;
		}

		$query = "
			UPDATE
				`custom_menus`
			SET
				`menu_level` = ?,
				`menu_name` = ?,
				`menu_link` = ?,
				`menu_target` = ?
			WHERE
				`menu_id` = ?
		";

		$rs = exec_query($query, array(
				$button_view,
				$button_name,
				$button_link,
				$button_target,
				$button_id
			)
		);

		set_page_message(tr('Custom menu data updated successful!'), 'success');
		return;
	}
}
// end site functions
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/custom_menus.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('button_list', 'page');
$tpl->define_dynamic('button_list', 'page');
$tpl->define_dynamic('add_button', 'page');
$tpl->define_dynamic('edit_button', 'page');

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin - Manage custom menus'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()
	)
);
gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');

add_new_button();

if (isset($_GET['delete_id'])) {
	delete_button();
}

if (isset($_GET['edit_id'])) {
	edit_button($tpl);
}

update_button();

gen_button_list($tpl);

$tpl->assign(
	array(
		'TR_TITLE_CUSTOM_MENUS' => tr('Manage custom menus'),
		'TR_ADD_NEW_BUTTON' => tr('Add new button'),
		'TR_BUTTON_NAME' => tr('Button name'),
		'TR_BUTTON_LINK' => tr('Button link'),
		'TR_BUTTON_TARGET' => tr('Button target'),
		'TR_VIEW_FROM' => tr('Show in'),
		'ADMIN' => tr('Administrator level'),
		'RESELLER' => tr('Reseller level'),
		'USER' => tr('Enduser level'),
		'RESSELER_AND_USER' => tr('Reseller and enduser level'),
		'TR_ADD' => tr('Add'),
		'TR_MENU_NAME' => tr('Menu button'),
		'TR_ACTON' => tr('Action'),
		'TR_EDIT' => tr('Edit'),
		'TR_DELETE' => tr('Delete'),
		'TR_LEVEL' => tr('Level'),
		'TR_SAVE' => tr('Save'),
		'TR_EDIT_BUTTON' => tr('Edit button'),
		'TR_MESSAGE_DELETE'	=> tr('Are you sure you want to delete %s?', true, '%s')
	)
);

generatePageMessage($tpl);

if (isset($_GET['edit_id'])) {
	$tpl->assign('ADD_BUTTON', '');
} else {
	$tpl->assign('EDIT_BUTTON', '');
}

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
