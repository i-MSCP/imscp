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

require '../include/imscp-lib.php';

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

function save_layout() {

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'save_layout') {
		$user_id = $_SESSION['user_id'];

		$user_layout = $_POST['def_layout'];

		$query = "
			UPDATE
				`user_gui_props`
			SET
				`layout` = ?
			WHERE
				`user_id` = ?
		";
		exec_query($query, array($user_layout, $user_id));

		$_SESSION['user_theme_color'] = $user_layout;
		$theme_color = $user_layout;
		$user_def_layout = $user_layout;
	}
}

function update_logo() {

	$user_id = $_SESSION['user_id'];

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'delete_logo') {
		$logo = get_own_logo($user_id);

		if (basename($logo) == 'isp_logo.gif') { // default logo
			return;
		}

		update_user_logo('', $user_id);
		unlink($logo);

		return;
	} else if (isset($_POST['uaction']) && $_POST['uaction'] === 'upload_logo') {
		if (empty($_FILES['logo_file']['name'])) {
			set_page_message(tr('Upload file error!'), 'error');
			return;
		}

		$file_type = $_FILES['logo_file']['type'];

		switch ($file_type) {
			case 'image/gif':
				$fext = 'gif';
				break;
			case 'image/jpeg':
			case 'image/pjpeg':
				$file_type = 'image/jpeg';
				$fext = 'jpg';
				break;
			case 'image/png':
				$fext = 'png';
				break;
			default:
				set_page_message(tr('You can only upload images!'), 'error');
				return;
				break;
		}

		$fname = $_FILES['logo_file']['tmp_name'];
		// Make sure it is really an image
		if (image_type_to_mime_type(exif_imagetype($fname)) != $file_type) {
			set_page_message(tr('You can only upload images!'), 'error');
			return;
		}
		// get the size of the image to prevent over large images
		list($fwidth, $fheight, $ftype, $fattr) = getimagesize($fname);
		if ($fwidth > 195 || $fheight > 195) {
			set_page_message(tr('Images have to be smaller than 195 x 195 pixels!'), 'error');
			return;
		}

		$newFName = sha1($fname .'-'. $user_id) .'.'. $fext;

		$path = substr($_SERVER['SCRIPT_FILENAME'], 0, strpos($_SERVER['SCRIPT_FILENAME'], '/admin/settings_layout.php') + 1);

		$logoFile = $path . '/themes/user_logos/' . $newFName;
		move_uploaded_file($fname, $logoFile);
		chmod($logoFile, 0644);

		update_user_logo($newFName, $user_id);

		set_page_message(tr('Your logo was successful uploaded!'), 'success');
	}
}

function update_user_logo($file_name, $user_id) {

	$query = "
		UPDATE
			`user_gui_props`
		SET
			`logo` = ?
		WHERE
			`user_id` = ?
	";

	exec_query($query, array($file_name, $user_id));
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/settings_layout.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('def_layout', 'page');
$tpl->define_dynamic('logo_remove_button', 'page');

save_layout();

update_logo();

gen_def_layout($tpl, $_SESSION['user_theme']);

if (get_own_logo($_SESSION['user_id']) != $cfg->ISP_LOGO_PATH . '/isp_logo.gif') {
	$tpl->parse('LOGO_REMOVE_BUTTON', '.logo_remove_button');
} else {
	$tpl->assign('LOGO_REMOVE_BUTTON', '');
}

$tpl->assign(
	array(
		'TR_ADMIN_CHANGE_LAYOUT_PAGE_TITLE' => tr('i-MSCP - Multi Server Control Panel'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'ISP_LOGO' => get_logo($_SESSION['user_id']),
		'OWN_LOGO' => get_own_logo($_SESSION['user_id']),
		'THEME_CHARSET' => tr('encoding')
	)
);

/*
 *
 * static page messages.
 *
 */

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');

$tpl->assign(
	array(
		'TR_LAYOUT_SETTINGS' => tr('Layout settings'),
		'TR_INSTALLED_LAYOUTS' => tr('Installed layouts'),
		'TR_LAYOUT_NAME' => tr('Layout name'),
		'TR_DEFAULT' => tr('default'),
		'TR_YES' => tr('yes'),
		'TR_SAVE' => tr('Save'),
		'TR_UPLOAD_LOGO' => tr('Upload logo'),
		'TR_LOGO_FILE' => tr('Logo file'),
		'TR_UPLOAD' => tr('Upload'),
		'TR_REMOVE' => tr('Remove'),
		'TR_CHOOSE_DEFAULT_LAYOUT' => tr('Choose default layout'),
		'TR_LAYOUT' => tr('Layout'),
	)
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

$tpl->prnt();

unsetMessages();
