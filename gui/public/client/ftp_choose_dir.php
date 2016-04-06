<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * Functions
 */

/**
 * Is the given directory visible?
 *
 * @param string $directory Directory path
 * @return bool
 */
function isVisibleDir($directory)
{
    global $hiddenDirs, $mountpoints;

    foreach ($mountpoints as $mountpoint) {
        if (preg_match("%^($mountpoint/(?:$hiddenDirs)|$hiddenDirs)$%", $directory)) {
            return false;
        }
    }

    return true;
}

/**
 * Is the given directory selectable?
 *
 * @param string $directory Directory path
 * @return bool
 */
function isSelectableDir($directory)
{
    global $unselectableDirs, $mountpoints;

    foreach ($mountpoints as $mountpoint) {
        if (preg_match("%^($mountpoint/(?:$unselectableDirs)|$unselectableDirs)$%", $directory)) {
            return false;
        }
    }

    return true;
}

/**
 * Generates directory list
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return void
 */
function generateDirectoryList($tpl)
{
    // Initialize variables
    $path = isset($_GET['cur_dir']) ? clean_input($_GET['cur_dir']) : '';
    $domain = $_SESSION['user_logged'];

    $vfs = new iMSCP_VirtualFileSystem($domain);
    $list = $vfs->ls($path);

    if (!$list) {
        if ($path == '/') {
            set_page_message(tr('Could not retrieve directories. Please contact your reseller.'), 'error');
            $tpl->assign('FTP_CHOOSER', '');
        } else {
            showBadRequestErrorPage();
        }
    }

    $parent = explode('/', $path);
    array_pop($parent);
    $parent = implode('/', $parent);
    $tpl->assign(array(
        'ACTION_LINK' => '',
        'ACTION' => '',
        'ICON' => 'parent',
        'DIR_NAME' => tr('Parent directory'),
        'LINK' => tohtml("ftp_choose_dir.php?cur_dir=$parent", 'htmlAttr')
    ));
    $tpl->parse('DIR_ITEM', '.dir_item');

    foreach ($list as $entry) {
        if ($entry['type'] != iMSCP_VirtualFileSystem::VFS_TYPE_DIR
            || $entry['file'] == '.'
            || $entry['file'] == '..'
        ) {
            continue;
        }

        $directory = $path . '/' . $entry['file'];
        if (substr_count($directory, '/') < 4) { // Only check for hidden/unselectable directories when needed
            if (!isVisibleDir($directory)) {
                continue;
            }

            if (!isSelectableDir($directory)) {
                $tpl->assign(array(
                    'ICON' => 'locked',
                    'DIR_NAME' => tohtml($entry['file']),
                    'DIRECTORY' => tohtml($directory, 'htmlAttr'),
                    'LINK' => tohtml('ftp_choose_dir.php?cur_dir=' . $directory, 'htmlAttr')
                ));
                $tpl->assign('ACTION_LINK', '');
                $tpl->parse('DIR_ITEM', '.dir_item');
                continue;
            }
        }

        $tpl->assign(array(
            'ICON' => 'folder',
            'DIR_NAME' => tohtml($entry['file']),
            'DIRECTORY' => tohtml($directory, 'htmlAttr'),
            'LINK' => tohtml('ftp_choose_dir.php?cur_dir=' . $directory, 'htmlAttr')
        ));
        $tpl->parse('ACTION_LINK', 'action_link');
        $tpl->parse('DIR_ITEM', '.dir_item');
    }
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'partial' => 'client/ftp_choose_dir.tpl',
    'page_message' => 'partial',
    'ftp_chooser' => 'partial',
    'dir_item' => 'ftp_chooser',
    'action_link' => 'dir_item',
    'layout' => ''
));
$tpl->assign(array(
    'TR_DIRECTORY_TREE' => tr('Directory tree'),
    'TR_DIRECTORIES' => tr('Directories'),
    'CHOOSE' => tr('Choose'),
    'layout' => ''
));

$mountpoints = getMountpoints(get_user_domain_id($_SESSION['user_id']));
$hiddenDirs = isset($_SESSION['vftp_hidden_dirs']) ? implode('|', $_SESSION['vftp_hidden_dirs']) : '';
$unselectableDirs = isset($_SESSION['vftp_unselectable_dirs']) ? implode('|', $_SESSION['vftp_unselectable_dirs']) : '';

generateDirectoryList($tpl);
generatePageMessage($tpl);

$tpl->parse('PARTIAL', 'partial');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
