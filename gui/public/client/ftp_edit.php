<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP team
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
    global $mainDmnProps;
    $mountpoints = getMountpoints($mainDmnProps['domain_id']);

    foreach ($mountpoints as $mountpoint) {
        if (preg_match("%^$mountpoint/(?:disabled|domain_disable_page|errors|phptmp|statistics)$%", $directory)) {
            return false;
        }
    }

    return true;
}

/**
 * Update Ftp account
 *
 * @param string $userid Ftp userid
 * @return bool TRUE on success, FALSE on failure
 */
function updateFtpAccount($userid)
{
    if (!isset($_POST['password'])
        || !isset($_POST['password_repeat'])
        || !isset($_POST['home_dir'])
    ) {
        showBadRequestErrorPage();
    }

    $error = false;
    $passwd = clean_input($_POST['password']);
    $passwdRepeat = clean_input($_POST['password_repeat']);
    $homeDir = clean_input($_POST['home_dir']);


    if ($passwd !== '') {
        if ($passwd !== $passwdRepeat) {
            set_page_message(tr('Passwords do not match.'), 'error');
            $error = true;
        }

        if (!checkPasswordSyntax($_POST['password'])) {
            $error = true;
        }
    }

    if ($homeDir === '') {
        set_page_message(tr('FTP home directory cannot be empty.'), 'error');
        $error = true;
    }

    if ($error) {
        return false;
    }

    // Cleanup path:
    // - Ensure that path start by a slash
    // - Removes double slashes
    // - Remove trailing slash if any
    if ($homeDir != '/') {
        $cleanPath = array();
        foreach (explode(DIRECTORY_SEPARATOR, $homeDir) as $dir) {
            if ($dir != '') {
                $cleanPath[] = $dir;
            }
        }

        $homeDir = '/' . implode(DIRECTORY_SEPARATOR, $cleanPath);
    }

    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);

    $vfs = new iMSCP_VirtualFileSystem($mainDmnProps['domain_name']);
    if ($homeDir !== '/' && !$vfs->exists($homeDir)) {
        set_page_message(tr("Directory '%s' doesn't exists.", $homeDir), 'error');
        return false;
    } elseif (strpos($homeDir, '..') !== false || !isAllowedDir($homeDir)) {
        set_page_message(tr("Directory '%s' is not allowed or invalid.", $homeDir), 'error');
        return false;
    }

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditFtp, array(
        'ftpUserId' => $userid
    ));

    $cfg = iMSCP_Registry::get('config');
    $homeDir = $cfg['USER_WEB_DIR'] . '/' . $mainDmnProps['domain_name'] . $homeDir;

    if ($passwd !== '') {
        $encryptedPassword = cryptPasswordWithSalt($passwd);
        exec_query(
            'UPDATE ftp_users SET passwd = ?, rawpasswd = ?, homedir = ?, status = ? WHERE userid = ?',
            array($encryptedPassword, $passwd, $homeDir, 'tochange', $userid)
        );
    } else {
        exec_query('UPDATE ftp_users SET homedir = ?, status = ? WHERE userid = ?', array(
            $homeDir, 'tochange', $userid
        ));
    }

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditFtp, array(
        'ftpUserId' => $userid
    ));

    send_request();
    write_log(sprintf('%s updated Ftp account: %s', $_SESSION['user_logged'], $userid), E_USER_NOTICE);
    set_page_message(tr('FTP account successfully updated.'), 'success');
    return true;
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param string $ftpUserId Ftp userid
 * @return void
 */
function generatePage($tpl, $ftpUserId)
{
    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);

    # Set hidden and unselectable directories for FTP chooser
    $_SESSION['vftp_hidden_dirs'] = array('disabled', 'errors', 'phptmp', 'statistics', 'domain_disable_page');
    $_SESSION['vftp_unselectable_dirs'] = array();

    $cfg = iMSCP_Registry::get('config');
    $stmt = exec_query('SELECT homedir FROM ftp_users WHERE userid = ?', $ftpUserId);
    $row = $stmt->fetchRow();

    $ftpHomeDir = $row['homedir'];
    $customerHomeDir = $cfg['USER_WEB_DIR'] . '/' . $mainDmnProps['domain_name'];

    if ($ftpHomeDir == $customerHomeDir) {
        $customFtpHomeDir = '/';
    } else {
        $customFtpHomeDir = substr($ftpHomeDir, strlen($customerHomeDir));
    }

    $tpl->assign(array(
        'USERNAME' => tohtml(decode_idna($ftpUserId), 'htmlAttr'),
        'HOME_DIR' => isset($_POST['home_dir']) ? tohtml($_POST['home_dir']) : tohtml($customFtpHomeDir),
        'ID' => tohtml($ftpUserId, 'htmlAttr'),
    ));
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

$eventManager = iMSCP_Events_Aggregator::getInstance();
$eventManager->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');
customerHasFeature('ftp') or showBadRequestErrorPage();

if (!isset($_GET['id'])) {
    showBadRequestErrorPage();
}

$userid = clean_input($_GET['id']);
if (who_owns_this($userid, 'ftpuser') != $_SESSION['user_id']) {
    showBadRequestErrorPage();
}

if (!empty($_POST)) {
    if (updateFtpAccount($userid)) {
        redirectTo('ftp_accounts.php');
    }
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/ftp_edit.tpl',
    'page_message' => 'layout'
));
$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Client / FTP / Overview / Edit FTP Account'),
    'TR_FTP_USER_DATA' => tr('Ftp account data'),
    'TR_USERNAME' => tr('Username'),
    'TR_PASSWORD' => tr('Password'),
    'TR_PASSWORD_REPEAT' => tr('Repeat password'),
    'TR_HOME_DIR' => tr('Home directory'),
    'TR_CHOOSE_DIR' => tr('Choose dir'),
    'TR_CHANGE' => tr('Update'),
    'TR_CANCEL' => tr('Cancel')
));

$eventManager->registerListener('onGetJsTranslations', function ($e) {
    /** @var $e iMSCP_Events_Event */
    $translations = $e->getParam('translations');
    $translations['core']['close'] = tr('Close');
    $translations['core']['ftp_directories'] = tr('FTP home directory');
});


generateNavigation($tpl);
generatePage($tpl, $userid);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
$eventManager->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
