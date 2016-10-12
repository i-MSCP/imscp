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

use iMSCP\VirtualFileSystem as VirtualFileSystem;

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
    global $vftpHiddenDirs, $mountpoints;

    if ($vftpHiddenDirs == '') {
        return true;
    }

    foreach ($mountpoints as $mountpoint) {
        if (substr($mountpoint, -1) != '/') {
            $mountpoint .= '/';
        }

        if (preg_match("%^(?:$mountpoint(?:$vftpHiddenDirs)|$vftpHiddenDirs)/?$%", $directory)) {
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
    global $vftpUnselectableDirs, $mountpoints;

    if ($vftpUnselectableDirs === '') {
        return true;
    }

    foreach ($mountpoints as $mountpoint) {
        if (substr($mountpoint, -1) != '/') {
            $mountpoint .= '/';
        }

        if (preg_match("%^(?:$mountpoint(?:$vftpUnselectableDirs)|$vftpUnselectableDirs)/?$%", $directory)) {
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
    global $vftpRootDir;

    $path = isset($_GET['cur_dir']) ? utils_normalizePath(clean_input($_GET['cur_dir'] ?: '/')) : '/';
    $vfs = new VirtualFileSystem($_SESSION['user_logged'], $vftpRootDir);
    $list = $vfs->ls($path);

    if (!$list) {
        set_page_message(tr('Could not retrieve directories. Please contact your reseller.'), 'error');
        $tpl->assign('FTP_CHOOSER', '');
        return;
    }

    if ($path != '/') {
        $parent = dirname($path);
    } else {
        $parent = '/';
    }

    $tpl->assign(array(
        'ICON' => 'parent',
        'DIR_NAME' => tr('Parent directory'),
        'LINK' => tohtml("ftp_choose_dir.php?cur_dir=$parent", 'htmlAttr')
    ));

    if (substr_count($parent, '/') < 2 // Only check for unselectable parent directory when needed
        && !isSelectableDir($parent)
    ) {
        $tpl->assign('ACTION_LINK', '');
    } else {
        $tpl->assign('DIRECTORY', tohtml($parent, 'htmlAttr'));
    }

    $tpl->parse('DIR_ITEM', '.dir_item');

    foreach ($list as $entry) {
        if ($entry['type'] != VirtualFileSystem::VFS_TYPE_DIR
            || $entry['file'] == '.'
            || $entry['file'] == '..'
        ) {
            continue;
        }

        $directory = utils_normalizePath($path . '/' . $entry['file']);

        if (substr_count($directory, '/') < 3) { // Only check for hidden/unselectable directories when needed
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
    'TOOLTIP_CHOOSE' => tohtml(tr('Choose'), 'htmlAttr'),
    'CHOOSE' => tr('Choose'),
    'layout' => ''
));

$mountpoints = getMountpoints(get_user_domain_id($_SESSION['user_id']));
$vftpRootDir = !empty($_SESSION['vftp_root_dir']) ? (string)$_SESSION['vftp_root_dir'] : '/';
$vftpHiddenDirs = !empty($_SESSION['vftp_hidden_dirs'])
    ? implode('|', array_map('quotemeta', (array)$_SESSION['vftp_hidden_dirs']))
    : '';
$vftpUnselectableDirs = !empty($_SESSION['vftp_unselectable_dirs'])
    ? implode('|', array_map('quotemeta', (array)$_SESSION['vftp_unselectable_dirs']))
    : '';

generateDirectoryList($tpl);
generatePageMessage($tpl);

$tpl->parse('PARTIAL', 'partial');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
