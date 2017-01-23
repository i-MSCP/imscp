<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by i-MSCP team
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
    $homeDir = utils_normalizePath('/' . clean_input($_POST['home_dir']));

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

    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);

    $vfs = new VirtualFileSystem($_SESSION['user_logged']);
    if ($homeDir !== '/' && !$vfs->exists($homeDir, VirtualFileSystem::VFS_TYPE_DIR)) {
        set_page_message(tr("Directory '%s' doesn't exists.", $homeDir), 'error');
        return false;
    }

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditFtp, array(
        'ftpUserId' => $userid
    ));

    $cfg = iMSCP_Registry::get('config');
    $homeDir = utils_normalizePath($cfg['USER_WEB_DIR'] . '/' . $mainDmnProps['domain_name'] . '/' . $homeDir);

    if ($passwd !== '') {
        exec_query(
            'UPDATE ftp_users SET passwd = ?, homedir = ?, status = ? WHERE userid = ? AND admin_id = ?',
            array(\iMSCP\Crypt::sha512($passwd), $homeDir, 'tochange', $userid, $_SESSION['user_id'])
        );
    } else {
        exec_query('UPDATE ftp_users SET homedir = ?, status = ? WHERE userid = ? AND admin_id = ?', array(
            $homeDir, 'tochange', $userid, $_SESSION['user_id']
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

    # Set parameters for the FTP chooser
    $_SESSION['ftp_chooser_domain_id'] = $mainDmnProps['domain_id'];
    $_SESSION['ftp_chooser_user'] = $_SESSION['user_logged'];
    $_SESSION['ftp_chooser_root_dir'] = '/';
    $_SESSION['ftp_chooser_hidden_dirs'] = array();
    $_SESSION['ftp_chooser_unselectable_dirs'] = array();

    $cfg = iMSCP_Registry::get('config');
    $stmt = exec_query('SELECT homedir FROM ftp_users WHERE userid = ?', $ftpUserId);
    $row = $stmt->fetchRow();

    $ftpHomeDir = utils_normalizePath('/' . $row['homedir']);
    $customerHomeDir = utils_normalizePath('/' . $cfg['USER_WEB_DIR'] . '/' . $mainDmnProps['domain_name']);

    if ($ftpHomeDir == $customerHomeDir) {
        $customFtpHomeDir = '/';
    } else {
        $customFtpHomeDir = substr($ftpHomeDir, strlen($customerHomeDir));
    }

    $tpl->assign(array(
        'USERNAME' => tohtml(decode_idna($ftpUserId), 'htmlAttr'),
        'HOME_DIR' => (isset($_POST['home_dir'])) ? tohtml($_POST['home_dir']) : tohtml($customFtpHomeDir),
        'ID'       => tohtml($ftpUserId, 'htmlAttr'),
    ));
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

$eventManager = iMSCP_Events_Aggregator::getInstance();
$eventManager->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');

if (!customerHasFeature('ftp') || !isset($_GET['id'])) {
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
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'client/ftp_edit.tpl',
    'page_message' => 'layout'
));
$tpl->assign(array(
    'TR_PAGE_TITLE'      => tr('Client / FTP / Overview / Edit FTP Account'),
    'TR_FTP_USER_DATA'   => tr('Ftp account data'),
    'TR_USERNAME'        => tr('Username'),
    'TR_PASSWORD'        => tr('Password'),
    'TR_PASSWORD_REPEAT' => tr('Repeat password'),
    'TR_HOME_DIR'        => tr('Home directory'),
    'TR_CHOOSE_DIR'      => tr('Choose dir'),
    'TR_CHANGE'          => tr('Update'),
    'TR_CANCEL'          => tr('Cancel')
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
