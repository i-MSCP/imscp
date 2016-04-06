<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP Team
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
 * Is allowed directory?
 *
 * @param string $directory Directory path
 * @return bool
 */
function isAllowedDir($directory)
{
    global $mountpoints;
    $disallowedDirs = implode('|', array_merge($mountpoints, array(
        '/', '00_private', 'backups', 'disabled', 'domain_disable_page', 'errors', 'logs', 'phptmp', 'statistics')
    ));

    foreach ($mountpoints as $mountpoint) {
        if (preg_match("%^($mountpoint/(?:$disallowedDirs)|$disallowedDirs)$%", $directory)) {
            return false;
        }
    }

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
    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);
    $error = false;

    if (!isset($_POST['protected_area_name'])
        || !isset($_POST['protected_area_path'])
        || !isset($_POST['protection_type'])
        || !in_array($_POST['protection_type'], array('user', 'group'), true)
    ) {
        showBadRequestErrorPage();
    }

    if ($_POST['protected_area_name'] === '') {
        set_page_message(tr('Please enter a name for the protected area.'), 'error');
        $error = true;
    }

    if ($_POST['protected_area_path'] === '') {
        set_page_message(tr('Please enter protected area path.'), 'error');
        $error = true;
    }

    if (!isset($_POST['users']) && !isset($_POST['groups'])) {
        set_page_message(tr('Please choose htaccess user or htaccess group.'), 'error');
        $error = true;
    }

    if ($error) {
        return;
    }

    $protectedAreaName = clean_input($_POST['protected_area_name']);
    $protectedAreaPath = clean_input($_POST['protected_area_path']);

    // Cleanup path:
    // - Ensure that path start by a slash
    // - Removes double slashes
    // - Remove trailing slash if any
    if ($protectedAreaPath !== '/') {
        $cleanPath = array();
        foreach (explode(DIRECTORY_SEPARATOR, $protectedAreaPath) as $dir) {
            if ($dir != '') {
                $cleanPath[] = $dir;
            }
        }

        $protectedAreaPath = '/' . implode(DIRECTORY_SEPARATOR, $cleanPath);
    }


    $vfs = new iMSCP_VirtualFileSystem($mainDmnProps['domain_name']);
    if ($protectedAreaPath !== '/' && !$vfs->exists($protectedAreaPath)) {
        set_page_message(tr("Directory '%s' doesn't exists.", $protectedAreaPath), 'error');
        return;
    } elseif (strpos($protectedAreaPath, '..') !== false || !isAllowedDir($protectedAreaPath)) {
        set_page_message(tr("Directory '%s' is not allowed or invalid.", $protectedAreaPath), 'error');
        return;
    }

    $userId = '';
    $groupId = '';

    if ($_POST['protection_type'] === 'user') {
        $users = $_POST['users'];
        for ($i = 0, $cnt_users = count($users); $i < $cnt_users; $i++) {
            if ($cnt_users == 1 || $cnt_users == $i + 1) {
                $userId .= $users[$i];
                if ($userId == '-1' || $userId == '') {
                    set_page_message(tr('You cannot protect an area without selected htaccess user(s).'), 'error');
                    return;
                }
            } else {
                $userId .= $users[$i] . ',';
            }
        }

        $groupId = 0;
    } else {
        $groups = $_POST['groups'];
        for ($i = 0, $cnt_groups = count($groups); $i < $cnt_groups; $i++) {
            if ($cnt_groups == 1 || $cnt_groups == $i + 1) {
                $groupId .= $groups[$i];
                if ($groupId == '-1' || $groupId == '') {
                    set_page_message(tr('You cannot protect an area without selected htaccess group(s).'), 'error');
                    return;
                }
            } else {
                $groupId .= $groups[$i] . ',';
            }
        }

        $userId = 0;
    }

    // Let's check if we have to update or to make new enrie
    $rs = exec_query('SELECT id FROM htaccess WHERE dmn_id = ? AND (path = ? OR path = ?)', array(
        $mainDmnProps['domain_id'], $protectedAreaPath, $protectedAreaPath . '/'
    ));

    if ($rs->rowCount()) {
        $row = $rs->fetchRow();
        exec_query(
            'UPDATE htaccess SET user_id = ?, group_id = ?, auth_name = ?, path = ?, status = ? WHERE id = ?',
            array($userId, $groupId, $protectedAreaName, $protectedAreaPath, 'tochange', $row['id'])
        );
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
            array($mainDmnProps['domain_id'], $userId, $groupId, 'Basic', $protectedAreaName, $protectedAreaPath, 'toadd')
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

    # Set hidden and unselectable directories for FTP chooser
    $_SESSION['vftp_hidden_dirs'] = array(
        '00_private', 'backups', 'disabled', 'domain_disable_page', 'errors', 'logs', 'phptmp', 'statistics'
    );
    $_SESSION['vftp_unselectable_dirs'] = $mountpoints;

    if (!isset($_GET['id'])) {
        $edit = 'no';
        $type = 'user';
        $userIds = 0;
        $groupIds = 0;
        $tpl->assign(array(
            'AREA_NAME' => isset($_POST['protected_area_name']) ? tohtml($_POST['protected_area_name'], 'htmlAttr') : '',
            'PATH' => isset($_POST['protected_area_path']) ? tohtml($_POST['protected_area_path'], 'htmlAttr') : '/htdocs'
        ));
    } else {
        $edit = 'yes';

        $stmt = exec_query('SELECT * FROM htaccess WHERE dmn_id = ? AND id = ?', array(
            $mainDmnProps['domain_id'], intval($_GET['id'])
        ));
        if (!$stmt->rowCount()) {
            showBadRequestErrorPage();
        }

        $row = $stmt->fetchRow();
        $userIds = $row['user_id'];
        $groupIds = $row['group_id'];

        $tpl->assign(array(
            'PATH' => tohtml($row['path']),
            'AREA_NAME' => tohtml($row['auth_name'])
        ));

        if ($userIds !== 0) {
            $type = 'user';
        } else {
            $type = 'group';
        }
    }

    if ($edit == 'no' || $type == 'user') {
        $tpl->assign(array(
            'USER_CHECKED' => ' checked',
            'GROUP_CHECKED' => ''
        ));
    }

    if ($type == 'group') {
        $tpl->assign(array(
            'USER_CHECKED' => '',
            'GROUP_CHECKED' => ' checked'
        ));
    }

    $stmt = exec_query('SELECT * FROM htaccess_users WHERE dmn_id = ?', $mainDmnProps['domain_id']);
    if (!$stmt->rowCount()) {
        set_page_message(tr('You must first create a user.'), 'error');
        redirectTo('protected_areas.php');
    }

    while ($row = $stmt->fetchRow()) {
        $userIds = explode(',', $userIds);
        $userSelected = '';
        for ($i = 0, $countUserIds = count($userIds); $i < $countUserIds; $i++) {
            if ($edit == 'yes' && $userIds[$i] == $row['id']) {
                $i = $countUserIds + 1;
                $userSelected = ' selected';
            } else {
                $userSelected = '';
            }
        }

        $tpl->assign(array(
            'USER_VALUE' => $row['id'],
            'USER_LABEL' => tohtml($row['uname']),
            'USER_SELECTED' => $userSelected
        ));
        $tpl->parse('USER_ITEM', '.user_item');
    }

    $stmt = exec_query('SELECT * FROM htaccess_groups WHERE dmn_id = ?', $mainDmnProps['domain_id']);
    if (!$stmt->rowCount()) {
        $tpl->assign(array(
            'GROUP_VALUE' => '-1',
            'GROUP_LABEL' => tr('You have no groups.'),
            'GROUP_SELECTED' => ''
        ));
        $tpl->parse('GROUP_ITEM', 'group_item');
    } else {
        while ($row = $stmt->fetchRow()) {
            $groupIds = explode(',', $groupIds);
            $groupSelected = '';
            for ($i = 0, $countGroupIds = count($groupIds); $i < $countGroupIds; $i++) {
                if ($edit == 'yes' && $groupIds[$i] == $row['id']) {
                    $i = $countGroupIds + 1;
                    $groupSelected = 'selected';
                } else {
                    $groupSelected = '';
                }
            }

            $tpl->assign(array(
                'GROUP_VALUE' => $row['id'],
                'GROUP_LABEL' => tohtml($row['ugroup']),
                'GROUP_SELECTED' => $groupSelected
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
array_pop($mountpoints);

if (!empty($_POST)) {
    handleProtectedArea();
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/protect_it.tpl',
    'page_message' => 'layout',
    'group_item' => 'page',
    'user_item' => 'page'
));

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Client / Webtools / Protected Areas / {TR_DYNAMIC_TITLE}'),
    'TR_DYNAMIC_TITLE' => isset($_GET['id']) ? tr('Edit protected area') : tr('Add protected area'),
    'TR_PROTECTED_AREA_DATA' => tr('Protected area data'),
    'TR_AREA_NAME' => tr('Protected area name'),
    'TR_PATH' => tr('Protected area path'),
    'TR_CHOOSE_DIR' => tr('Choose dir'),
    'TR_AUTHENTICATION_DATA' => tr('Authentication data'),
    'TR_USER_AUTH' => tr('Authentication by user'),
    'TR_GROUP_AUTH' => tr('Authentication by group'),
    'TR_PROTECT_IT' => isset($_GET['id']) ? tr('Update protected area') : tr('Create protected area'),
    'TR_CANCEL' => tr('Cancel')
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

unsetMessages();
