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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010-2011 by i-MSCP | http://i-mscp.net
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('ftp')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/ftp_choose_dir.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('ftp_chooser', 'page');
$tpl->define_dynamic('dir_item', 'ftp_chooser');
$tpl->define_dynamic('list_item', 'dir_item');
$tpl->define_dynamic('action_link', 'list_item');

/**
 * @param $tpl
 * @return
 */
function gen_directories($tpl) {

	// Initialize variables
	$path = isset($_GET['cur_dir']) ? $_GET['cur_dir'] : '';
	$domain = $_SESSION['user_logged'];

	// Create the virtual file system and open it so it can be used
	$vfs = new iMSCP_VirtualFileSystem($domain);

	// Get the directory listing
	$list = $vfs->ls($path);

	if (!$list) {
		set_page_message(tr('Cannot open directory. Please contact your administrator.'), 'error');
		$tpl->assign('FTP_CHOOSER', '');
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
			 'ICON' => 'parent',
			 'DIR_NAME' => tr('Parent Directory'),
			 'LINK' => 'ftp_choose_dir.php?cur_dir=' . $parent,));

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

		// Create the directory link
		$tpl->assign(
			array(
				 'DIR_NAME' => tohtml($entry['file']),
				 'CHOOSE_IT' => $dr,
				 'LINK' => 'ftp_choose_dir.php?cur_dir=' . $dr));

		$forbidden_Dir_Names = ('/backups|disabled|errors|logs|phptmp/i');
		$forbidden = preg_match($forbidden_Dir_Names, $entry['file']);
		($forbidden == 1) ? $tpl->assign('ACTION_LINK', '') : $tpl->parse('ACTION_LINK', 'action_link');

		$tpl->parse('DIR_ITEM' , '.dir_item');
	}
}

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Ftp / Choose directory'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()));

gen_directories($tpl);

$tpl->assign(
	array(
		 'TR_DIRECTORY_TREE' => tr('Directory tree'),
		 'TR_DIRS' => tr('Directories'),
		 'TR_ACTION' => tr('Action'),
		 'CHOOSE' => tr('Choose')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
