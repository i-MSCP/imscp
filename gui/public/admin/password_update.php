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

use iMSCP\Crypt as Crypt;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Update admin password
 *
 * @return void
 */
function admin_updatePassword()
{
    if (empty($_POST))
        return;

    if (!isset($_POST['password']) || !isset($_POST['password_confirmation'])) {
        showBadRequestErrorPage();
    }

    if ($_POST['password'] === '' || $_POST['password_confirmation'] === '') {
        set_page_message(tr('All fields are required.'), 'error');
        return;
    }

    if ($_POST['password'] !== $_POST['password_confirmation']) {
        set_page_message(tr("Passwords do not match."), 'error');
        return;
    }

    if (!checkPasswordSyntax($_POST['password'])) {
        return;
    }

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditUser, array(
        'userId' => $_SESSION['user_id']
    ));
    exec_query('UPDATE admin SET admin_pass = ? WHERE admin_id = ?', array(
        Crypt::apr1MD5($_POST['password']), $_SESSION['user_id']
    ));
    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditUser, array(
        'userId' => $_SESSION['user_id']
    ));
    write_log(sprintf('%s: updated password.', $_SESSION['user_logged']), E_USER_NOTICE);
    set_page_message(tr('Password successfully updated.'), 'success');
    redirectTo('password_update.php');
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);
check_login('admin');
admin_updatePassword();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'shared/partials/password_update.tpl',
    'page_message' => 'layout'
));
$tpl->assign(array(
    'TR_PAGE_TITLE'            => tr('Admin / Profile / Password'),
    'TR_PASSWORD_DATA'         => tr('Password data'),
    'TR_PASSWORD'              => tr('Password'),
    'TR_PASSWORD_CONFIRMATION' => tr('Password confirmation'),
    'TR_UPDATE'                => tr('Update')
));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
