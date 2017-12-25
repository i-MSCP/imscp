<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Is the given directory hidden inside the mountpoints?
 *
 * @param string $directory Directory path
 * @return bool
 */
function isHiddenDir($directory)
{
    global $vftpHiddenDirs, $mountPoints;

    if ($vftpHiddenDirs == '')
        return false;
    if (preg_match("%^(?:$mountPoints)(?:$vftpHiddenDirs)$%", $directory))
        return true;

    return false;
}

/**
 * Is the given directory unselectable inside the mountpoints?
 *
 * @param string $directory Directory path
 * @return bool
 */
function isUnselectable($directory)
{
    global $vftpUnselectableDirs, $mountPoints;

    if ($vftpUnselectableDirs === '')
        return false;
    if (preg_match("%^(?:$mountPoints)(?:$vftpUnselectableDirs)$%", $directory))
        return true;

    return false;
}

/**
 * Generates directory list
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function generateDirectoryList($tpl)
{
    global $vftpUser, $vftpRootDir;

    $path = isset($_GET['cur_dir']) ? utils_normalizePath(clean_input($_GET['cur_dir'] ?: '/')) : '/';
    $vfs = new VirtualFileSystem($vftpUser, $vftpRootDir);
    $list = $vfs->ls($path);

    if (!$list) {
        set_page_message(tr('Could not retrieve directories.'), 'error');
        $tpl->assign('FTP_CHOOSER', '');
        return;
    }

    if ($path != '/') {
        $parent = dirname($path);
    } else {
        $parent = '/';
    }

    $tpl->assign([
        'ICON'     => 'parent',
        'DIR_NAME' => tr('Parent directory'),
        'LINK'     => tohtml("/shared/ftp_choose_dir.php?cur_dir=$parent", 'htmlAttr')
    ]);

    if (substr_count($parent, '/') < 2 // Only check for unselectable parent directory when needed
        && isUnselectable($parent)
    ) {
        $tpl->assign('ACTION_LINK', '');
    } else {
        $tpl->assign('DIRECTORY', tohtml($parent, 'htmlAttr'));
    }

    $tpl->parse('DIR_ITEM', '.dir_item');

    foreach ($list as $entry) {
        if ($entry['type'] != VirtualFileSystem::VFS_TYPE_DIR || $entry['file'] == '.' || $entry['file'] == '..')
            continue;

        $directory = utils_normalizePath('/' . $path . '/' . $entry['file']);

        if (substr_count($directory, '/') < 3) { // Only check for hidden/unselectable directories when needed
            if (isHiddenDir($directory))
                continue;

            if (isUnselectable($directory)) {
                $tpl->assign([
                    'ICON'      => 'locked',
                    'DIR_NAME'  => tohtml($entry['file']),
                    'DIRECTORY' => tohtml($directory, 'htmlAttr'),
                    'LINK'      => tohtml('/shared/ftp_choose_dir.php?cur_dir=' . $directory, 'htmlAttr')
                ]);
                $tpl->assign('ACTION_LINK', '');
                $tpl->parse('DIR_ITEM', '.dir_item');
                continue;
            }
        }

        $tpl->assign([
            'ICON'      => 'folder',
            'DIR_NAME'  => tohtml($entry['file']),
            'DIRECTORY' => tohtml($directory, 'htmlAttr'),
            'LINK'      => tohtml('/shared/ftp_choose_dir.php?cur_dir=' . $directory, 'htmlAttr')
        ]);
        $tpl->parse('ACTION_LINK', 'action_link');
        $tpl->parse('DIR_ITEM', '.dir_item');
    }
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('all');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onSharedScriptStart);

$tpl = new TemplateEngine();
$tpl->define([
    'partial'      => 'shared/partials/ftp_choose_dir.tpl',
    'page_message' => 'partial',
    'ftp_chooser'  => 'partial',
    'dir_item'     => 'ftp_chooser',
    'action_link'  => 'dir_item',
    'layout'       => ''
]);
$tpl->assign([
    'TOOLTIP_CHOOSE' => tohtml(tr('Choose'), 'htmlAttr'),
    'CHOOSE'         => tr('Choose'),
    'layout'         => ''
]);

if (!isset($_SESSION['ftp_chooser_user']) || !isset($_SESSION['ftp_chooser_domain_id'])) {
    $tpl->assign('FTP_CHOOSER', '');
    set_page_message(tr('Could not retrieve directories.'), 'error');
} else {
    $vftpDomainId = $_SESSION['ftp_chooser_domain_id'];
    $vftpUser = $_SESSION['ftp_chooser_user'];
    $vftpRootDir = !empty($_SESSION['ftp_chooser_root_dir']) ? $_SESSION['ftp_chooser_root_dir'] : '/';
    $vftpHiddenDirs = !empty($_SESSION['ftp_chooser_hidden_dirs'])
        ? implode('|', array_map(function ($dir) {
            return quotemeta(utils_normalizePath('/' . $dir));
        }, (array)$_SESSION['ftp_chooser_hidden_dirs']))
        : '';
    $vftpUnselectableDirs = !empty($_SESSION['ftp_chooser_unselectable_dirs'])
        ? implode('|', array_map(function ($dir) {
            return quotemeta(utils_normalizePath('/' . $dir));
        }, (array)$_SESSION['ftp_chooser_unselectable_dirs']))
        : '';
    $mountPoints = implode('|', array_map(function ($dir) {
        $path = utils_normalizePath('/' . $dir);
        if ($path == '/') return '';
        return quotemeta($path);
    }, getMountpoints($vftpDomainId)));

    generateDirectoryList($tpl);
}

generatePageMessage($tpl);

$tpl->parse('PARTIAL', 'partial');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onSharedScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();
