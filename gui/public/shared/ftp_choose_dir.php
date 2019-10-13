<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

declare(strict_types=1);

use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\TemplateEngine;
use iMSCP\VirtualFileSystem;

/**
 * Is the given directory hidden inside the mountpoints?
 *
 * @param string $directory Directory path
 * @return bool
 */
function isHiddenDir(string $directory): bool
{
    global $vfsHiddenDirs, $mountPoints;

    if ($vfsHiddenDirs === '') {
        return false;
    }

    if (preg_match("%^(?:$mountPoints)(?:$vfsHiddenDirs)$%", $directory)) {
        return true;
    }

    return false;
}

/**
 * Is the given directory unselectable inside the mountpoints?
 *
 * @param string $directory Directory path
 * @return bool
 */
function isUnselectable(string $directory): bool
{
    global $vfsUnselectableDirs, $mountPoints;

    if ($vfsUnselectableDirs === '') {
        return false;
    }

    if (preg_match(
        "%^(?:$mountPoints)(?:$vfsUnselectableDirs)$%", $directory
    )) {
        return true;
    }

    return false;
}

/**
 * Generates directory list
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function generateDirectoryList(TemplateEngine $tpl): void
{
    global $vfsUser, $vfsRootDir;

    try {
        $curDir = isset($_GET['cur_dir']) ? clean_input($_GET['cur_dir'] ?: '/') : '/';
        
        $vfs = new VirtualFileSystem($vfsUser, $vfsRootDir);
        $list = $vfs->ls($curDir);
        $parentDir = $curDir != '/' ? dirname($curDir) : $curDir;

        $tpl->assign([
            'LINK'  => tohtml(
                "/shared/ftp_choose_dir.php?cur_dir=$parentDir", 'htmlAttr'
            ),
            'ICON'     => 'parent',
            'DIR_NAME' => tr('Parent directory')
        ]);

        // Only check for unselectable parent directory when needed
        if (substr_count($parentDir, '/') < 2 && isUnselectable($parentDir)) {
            $tpl->assign('ACTION_LINK', '');
        } else {
            $tpl->assign('DIRECTORY', tohtml($parentDir, 'htmlAttr'));
        }

        $tpl->parse('DIR_ITEM', '.dir_item');
        
        foreach ($list as $entry) {
            if ($entry['type'] != VirtualFileSystem::VFS_TYPE_DIR) {
                continue;
            }

            $directory = utils_normalizePath(
                '/' . $curDir . '/' . $entry['basename']
            );

            // Only check for hidden/unselectable directories when needed
            if (substr_count($directory, '/') < 3) {
                if (isHiddenDir($directory)) {
                    continue;
                }

                if (isUnselectable($directory)) {
                    $tpl->assign([
                        'ICON'      => 'locked',
                        'DIR_NAME'  => tohtml($entry['basename']),
                        'LINK'      => tohtml(
                            '/shared/ftp_choose_dir.php?cur_dir=' . $directory,
                            'htmlAttr'
                        ),
                        'DIRECTORY' => tohtml($directory, 'htmlAttr'),
                    ]);
                    $tpl->assign('ACTION_LINK', '');
                    $tpl->parse('DIR_ITEM', '.dir_item');
                    continue;
                }
            }

            $tpl->assign([
                'ICON'      => 'folder',
                'DIR_NAME'  => tohtml($entry['basename']),
                'LINK'      => tohtml(
                    '/shared/ftp_choose_dir.php?cur_dir=' . $directory,
                    'htmlAttr'
                ),
                'DIRECTORY' => tohtml($directory, 'htmlAttr'),
            ]);
            $tpl->parse('ACTION_LINK', 'action_link');
            $tpl->parse('DIR_ITEM', '.dir_item');
        }
    } catch (Throwable $exception) {
        set_page_message(tr('Could not retrieve directories: %s', $exception->getMessage()), 'error');
        $tpl->assign('FTP_CHOOSER', '');
    }
}

require_once 'imscp-lib.php';

check_login('all');
EventAggregator::getInstance()->dispatch(Events::onSharedScriptStart);

$tpl = new TemplateEngine();
$tpl->define_dynamic([
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

if (!isset($_SESSION['ftp_chooser_user'])
    || !isset($_SESSION['ftp_chooser_domain_id'])
) {
    $tpl->assign('FTP_CHOOSER', '');
    throw new LogicException('Missing parameters for the FTP chooser.');
} else {
    $vftpDomainId = $_SESSION['ftp_chooser_domain_id'];
    $vfsUser = $_SESSION['ftp_chooser_user'];
    $vfsRootDir = !empty($_SESSION['ftp_chooser_root_dir'])
        ? $_SESSION['ftp_chooser_root_dir'] : '/';
    $vfsHiddenDirs = !empty($_SESSION['ftp_chooser_hidden_dirs'])
        ? implode('|', array_map(function ($dir) {
            return quotemeta(utils_normalizePath('/' . $dir));
        }, (array)$_SESSION['ftp_chooser_hidden_dirs']))
        : '';
    $vfsUnselectableDirs = !empty($_SESSION['ftp_chooser_unselectable_dirs'])
        ? implode('|', array_map(function ($dir) {
            return quotemeta(utils_normalizePath('/' . $dir));
        }, (array)$_SESSION['ftp_chooser_unselectable_dirs']))
        : '';
    $mountPoints = implode('|', array_map(function ($dir) {
        $path = utils_normalizePath('/' . $dir);
        if ($path == '/') {
            return '';
        }

        return quotemeta($path);
    }, getMountpoints($vftpDomainId)));

    generateDirectoryList($tpl);
}

generatePageMessage($tpl);

$tpl->parse('PARTIAL', 'partial');
EventAggregator::getInstance()->dispatch(
    Events::onSharedScriptEnd, ['templateEngine' => $tpl]
);
$tpl->prnt();
