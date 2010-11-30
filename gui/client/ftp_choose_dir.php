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

$cfg = iMSCP_Registry::get('Config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('dir_item', 'page');
$tpl->define_dynamic('action_link', 'page');
$tpl->define_dynamic('list_item', 'page');
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/ftp_choose_dir.tpl');


function gen_directories(&$tpl) {

	$sql = iMSCP_Registry::get('Db');
	// Initialize variables
	$path = isset($_GET['cur_dir']) ? $_GET['cur_dir'] : '';
	$domain = $_SESSION['user_logged'];
	// Create the virtual file system and open it so it can be used
	$vfs = new iMSCP_VirtualFileSystem($domain, $sql);
	// Get the directory listing
	$list = $vfs->ls($path);
	if (!$list) {
		set_page_message(tr('Cannot open directory!<br>Please contact your administrator!'));
		return;
	}
	// Show parent directory link
	$parent = explode('/', $path);
	array_pop($parent);
	$parent = implode('/', $parent);
	$tpl->assign('ACTION_LINK', '');
	$tpl->assign(
		array(
			'ACTION' => '',
			'ICON' => "parent",
			'DIR_NAME' => tr('Parent Directory'),
			'LINK' => 'ftp_choose_dir.php?cur_dir=' . $parent,
		)
	);
	$tpl->parse('DIR_ITEM', '.dir_item');
	// Show directories only
	foreach ($list as $entry) {
		// Skip non-directory entries
		if ($entry['type'] != iMSCP_VirtualFileSystem::VFS_TYPE_DIR) {
			continue;
		}
		// Skip '.' and '..'
		if ($entry['file'] == '.' || $entry['file'] == '..') {
			continue;
		}
		// Check for .htaccess existence to display another icon
		$dr = $path . '/' . $entry['file'];
		$tfile = $dr . '/.htaccess';
		if ($vfs->exists($tfile)) {
			$image = "locked";
		} else {
			$image = "folder";
		}
		$forbidden_Dir_Names = ('/backups|disabled|errors|logs|phptmp/i');
		$forbidden = preg_match($forbidden_Dir_Names, $entry['file']);
		($forbidden === 1) ? $tpl->assign('ACTION_LINK', '') : $tpl->parse('ACTION_LINK', 'action_link');
		// Create the directory link
		$tpl->assign(
			array(
				'PROTECT_IT' => "protected_areas_add.php?file=".$dr,
				'ICON' => $image,
				'DIR_NAME' => tohtml($entry['file']),
				'CHOOSE_IT' => $dr,
				'LINK' => "ftp_choose_dir.php?cur_dir=".$dr,
			)
		);
		$tpl->parse('DIR_ITEM' , '.dir_item');
	}
}

// functions end

$tpl->assign(
	array(
		'TR_CLIENT_WEBTOOLS_PAGE_TITLE' => tr('i-MSCP - Client/Webtools'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

gen_directories($tpl);

$tpl->assign(
	array(
		'TR_DIRECTORY_TREE' => tr('Directory tree'),
		'TR_DIRS' => tr('Directories'),
		'TR__ACTION' => tr('Action')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
