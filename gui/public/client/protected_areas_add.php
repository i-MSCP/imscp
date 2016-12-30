<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * Is allowed directory?
 *
 * @param string $directory Directory path
 * @return bool
 */
function isAllowedDir($directory)
{
    global $mountpoints;

    $disallowedDirs = implode('|', array_map(function ($dir) {
        return quotemeta(utils_normalizePath('/' . $dir));
    }, array('/', '00_private', 'backups', 'errors', 'logs', 'phptmp')));

    $mountpointsReg = implode('|', array_map(function ($dir) {
        $path = utils_normalizePath('/' . $dir);
        if ($path == '/') return '';
        return quotemeta($path);
    }, $mountpoints));

    if (preg_match("%^(?:$mountpointsReg)(?:$disallowedDirs)?$%", $directory))
        return false;

    return true;
}

/**
 * Add/update protected area
 *
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @return void
 */
function handleProtectedArea()
{
    if (!isset($_POST['protected_area_name']) || !isset($_POST['protected_area_path'])) {
        showBadRequestErrorPage();
    }

    $protectionType = (
        isset($_POST['protection_type']) && in_array($_POST['protection_type'], array('user', 'group'), true)
    ) ? $_POST['protection_type'] : 'user';

    $error = false;

    if ($_POST['protected_area_name'] === '') {
        set_page_message(tr('Please enter a name for the protected area.'), 'error');
        $error = true;
    }

    if ($_POST['protected_area_path'] === '') {
        set_page_message(tr('Please enter protected area path.'), 'error');
        $error = true;
    }

    if ($protectionType == 'user' && empty($_POST['users'])) {
        set_page_message(tr('Please choose at least one htaccess user.'), 'error');
        $error = true;
    } elseif ($protectionType == 'group' && empty($_POST['groups'])) {
        set_page_message(tr('Please choose at least one htaccess user/group.'), 'error');
        $error = true;
    }

    if ($error)
        return;

    $protectedAreaName = clean_input($_POST['protected_area_name']);
    $protectedAreaPath = utils_normalizePath('/' . clean_input($_POST['protected_area_path']));

    if (!isAllowedDir($protectedAreaPath)) {
        set_page_message(tr("Directory '%s' is not allowed or invalid.", $protectedAreaPath), 'error');
        return;
    }

    $vfs = new VirtualFileSystem($_SESSION['user_logged']);
    if ($protectedAreaPath !== '/' && !$vfs->exists($protectedAreaPath, VirtualFileSystem::VFS_TYPE_DIR)) {
        set_page_message(tr("Directory '%s' doesn't exists.", $protectedAreaPath), 'error');
        return;
    }

    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);

    if ($protectionType === 'user') {
        $stmt = exec_query(
            '
              SELECT id
              FROM htaccess_users
              WHERE id IN(' . implode(',', array_map('quoteValue', (array)$_POST['users'])) . ')
              AND dmn_id = ?
            ',
            $mainDmnProps['domain_id']
        );
        if (!$stmt->rowCount()) {
            showBadRequestErrorPage();
        }

        $userIdList = implode(',', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $groupIdList = 0;
    } else {
        $stmt = exec_query(
            '
              SELECT id
              FROM htaccess_groups
              WHERE id IN(' . implode(',', array_map('quoteValue', (array)$_POST['groups'])) . ')
              AND dmn_id = ?
            ',
            $mainDmnProps['domain_id']
        );
        if (!$stmt->rowCount()) {
            showBadRequestErrorPage();
        }

        $groupIdList = implode(',', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $userIdList = 0;
    }

    if (isset($_REQUEST['id']) && $_REQUEST['id'] > 0) {
        $stmt = exec_query(
            '
              UPDATE htaccess
              SET user_id = ?, group_id = ?, auth_name = ?, path = ?, status = ?
              WHERE id = ?
              AND dmn_id = ?
            ',
            array(
                $userIdList, $groupIdList, $protectedAreaName, $protectedAreaPath, 'tochange', $_REQUEST['id'],
                $mainDmnProps['domain_id']
            )
        );
        if (!$stmt->rowCount()) {
            showBadRequestErrorPage();
        }

        send_request();
        set_page_message(tr('Protected area successfully scheduled for update.'), 'success');
    } else {
        exec_query(
            '
                INSERT INTO htaccess (
                    dmn_id, user_id, group_id, auth_type, auth_name, path, status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?
                )
            ',
            array(
                $mainDmnProps['domain_id'], $userIdList, $groupIdList, 'Basic', $protectedAreaName, $protectedAreaPath,
                'toadd'
            )
        );
        send_request();
        set_page_message(tr('Protected area successfully scheduled for addition.'), 'success');
    }

    redirectTo('protected_areas.php');
}

/**
 * Generates page
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return void
 */
function generatePage($tpl)
{
    global $mountpoints;

    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);

    # Set parameters for the FTP chooser
    $_SESSION['ftp_chooser_domain_id'] = $mainDmnProps['domain_id'];
    $_SESSION['ftp_chooser_user'] = $_SESSION['user_logged'];
    $_SESSION['ftp_chooser_root_dir'] = '/';
    $_SESSION['ftp_chooser_hidden_dirs'] = array('00_private', 'backups', 'errors', 'logs', 'phptmp');
    $_SESSION['ftp_chooser_unselectable_dirs'] = $mountpoints;

    if (isset($_REQUEST['id']) && $_REQUEST['id'] > 0) {
        $stmt = exec_query('SELECT * FROM htaccess WHERE dmn_id = ? AND id = ?', array(
            $mainDmnProps['domain_id'], intval($_REQUEST['id'])
        ));
        if (!$stmt->rowCount()) {
            showBadRequestErrorPage();
        }

        $row = $stmt->fetchRow();
        $tpl->assign('ID', $row['id']);
        $userIds = $row['user_id'];
        $groupIds = $row['group_id'];
        $authType = ((isset($_POST['protection_type']) && $_POST['protection_type'] === 'group') || $userIds == 0) ? 'group' : 'user';
        $tpl->assign(array(
            'AREA_NAME' => (isset($_POST['protected_area_name']))
                ? tohtml($_POST['protected_area_name'], 'htmlAttr') : tohtml($row['auth_name'], 'htmlAttr'),
            'PATH'      => (isset($_POST['protected_area_path']))
                ? tohtml($_POST['protected_area_path'], 'htmlAttr') : tohtml($row['path'], 'htmlAttr')
        ));
    } else {
        $userIds = 0;
        $groupIds = 0;
        $authType = ((isset($_POST['protection_type']) && $_POST['protection_type'] === 'group')) ? 'group' : 'user';
        $tpl->assign(array(
            'ID'        => 0,
            'AREA_NAME' => (isset($_POST['protected_area_name']))
                ? tohtml($_POST['protected_area_name'], 'htmlAttr') : '',
            'PATH'      => (isset($_POST['protected_area_path']))
                ? tohtml($_POST['protected_area_path'], 'htmlAttr')
                : tohtml(utils_normalizePath('/' . $mainDmnProps['document_root']), 'htmlAttr')
        ));
    }

    if ($authType == 'user') {
        $tpl->assign(array(
            'USER_CHECKED'  => ' checked',
            'GROUP_CHECKED' => ''
        ));
    }

    if ($authType == 'group') {
        $tpl->assign(array(
            'USER_CHECKED'  => '',
            'GROUP_CHECKED' => ' checked'
        ));
    }

    $stmt = exec_query('SELECT * FROM htaccess_users WHERE dmn_id = ?', $mainDmnProps['domain_id']);
    if (!$stmt->rowCount()) {
        set_page_message(tr('You must first create a user.'), 'error');
        redirectTo('protected_areas.php');
    }

    # Create htuser list
    $userIds = isset($_POST['users']) ? (array)$_POST['users'] : explode(',', $userIds);
    while ($row = $stmt->fetchRow()) {
        $tpl->assign(array(
            'USER_VALUE'    => tohtml($row['id']),
            'USER_LABEL'    => tohtml($row['uname']),
            'USER_SELECTED' => ($authType === 'user' && in_array($row['id'], $userIds)) ? ' selected' : ''
        ));
        $tpl->parse('USER_ITEM', '.user_item');
    }

    # Create htgroup list
    $stmt = exec_query('SELECT * FROM htaccess_groups WHERE dmn_id = ?', $mainDmnProps['domain_id']);
    if (!$stmt->rowCount()) {
        $tpl->assign(array(
            'AUTH_SELECTORS_JS'      => '',
            'AUTH_SELECTORS'         => '',
            'AUTH_GROUP_LIST'        => '',
            'TR_AUTHENTICATION_DATA' => tr('Authentication users'),
        ));
    } else {
        $tpl->assign('TR_AUTHENTICATION_DATA', tr('Authentication users/groups'));
        $groupIds = isset($_POST['groups']) ? (array)$_POST['groups'] : explode(',', $groupIds);
        while ($row = $stmt->fetchRow()) {
            $tpl->assign(array(
                'GROUP_VALUE'    => tohtml($row['id']),
                'GROUP_LABEL'    => tohtml($row['ugroup']),
                'GROUP_SELECTED' => ($authType == 'group' && in_array($row['id'], $groupIds)) ? ' selected' : ''
            ));
            $tpl->parse('GROUP_ITEM', '.group_item');
        }
    }
}

/***********************************************************************************************************************
 * main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');
customerHasFeature('protected_areas') or showBadRequestErrorPage();

$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
$mountpoints = getMountpoints($mainDmnProps['domain_id']);

if (!empty($_POST))
    handleProtectedArea();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout'              => 'shared/layouts/ui.tpl',
    'page'                => 'client/protect_it.tpl',
    'page_message'        => 'layout',
    'auth_selectors_js'   => 'page',
    'auth_selectors'      => 'page',
    'auth_group_selector' => 'auth_selectors',
    'auth_group_list'     => 'page',
    'group_item'          => 'auth_group_list',
    'user_item'           => 'page'
));
$tpl->assign(array(
    'TR_PAGE_TITLE'          => tr('Client / Webtools / Protected Areas / {TR_DYNAMIC_TITLE}'),
    'TR_DYNAMIC_TITLE'       => (isset($_REQUEST['id']) && $_REQUEST['id'] > 0)
        ? tr('Edit protected area') : tr('Add protected area'),
    'TR_PROTECTED_AREA_DATA' => tr('Protected area data'),
    'TR_AREA_NAME'           => tr('Protected area name'),
    'TR_PATH'                => tr('Protected area path'),
    'TR_CHOOSE_DIR'          => tr('Choose dir'),
    'TR_USER_AUTH'           => tr('Authentication by user'),
    'TR_GROUP_AUTH'          => tr('Authentication by group'),
    'TR_PROTECT_IT'          => (isset($_REQUEST['id']) && $_REQUEST['id'] > 0)
        ? tr('Edit protected area') : tr('Add protected area'),
    'TR_CANCEL'              => tr('Cancel')
));

iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
    /** @var $e iMSCP_Events_Event */
    $translations = $e->getParam('translations');
    $translations['core']['close'] = tr('Close');
    $translations['core']['ftp_directories'] = tr('Protected area path');
});

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
