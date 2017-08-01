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

use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_pTemplate as TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Update user data
 *
 * @throws Exception
 * @param Zend_Form $form
 * @param int $userId User unique identifier
 * @return void
 */
function updateUserData(Zend_Form $form, $userId)
{
    global $userType;

    $data = exec_query('SELECT admin_name, admin_type FROM admin WHERE admin_id = ?', $userId)->fetchRow();

    if (!$data) {
        showBadRequestErrorPage();
    }

    $userType = $data['admin_type'];

    if (!$form->isValid($_POST)) {
        foreach ($form->getMessages() as $msgStack => $msg) {
            set_page_message(reset($msg), 'error');
        }

        return;
    }

    $passwordUpdated = ($form->getValue('admin_pass') !== '');
    $db = Database::getInstance();

    try {
        $db->beginTransaction();

        EventsManager::getInstance()->dispatch(Events::onBeforeEditUser, [
            'userId'   => $userId,
            'userData' => $form->getValues()
        ]);

        exec_query(
            "
                UPDATE admin
                SET admin_pass = IFNULL(?, admin_pass), fname = ?, lname = ?, firm = ?, zip = ?, city = ?, state = ?,
                    country = ?, email = ?, phone = ?, fax = ?, street1 = ?, street2 = ?, gender = ?,
                    admin_status = IF(admin_type = 'user', IF(?, 'tochangepwd', admin_status), admin_status)
                WHERE admin_id = ?
            ",
            [
                $passwordUpdated ? NULL : iMSCP\Crypt::apr1MD5($form->getValue('admin_pass')), $form->getValue('fname'),
                $form->getValue('lname'), $form->getValue('firm'), $form->getValue('zip'), $form->getValue('city'),
                $form->getValue('state'), $form->getValue('country'), $form->getValue('email'),
                $form->getValue('phone'), $form->getValue('fax'), $form->getValue('street1'), $form->getValue('street2'),
                $form->getValue('gender'), $passwordUpdated ? 1 : 0, $userId
            ]
        );

        if ($passwordUpdated) {
            exec_query('DELETE FROM login WHERE user_name = ?', $data['admin_name']);
        }

        EventsManager::getInstance()->dispatch(Events::onAfterEditUser, [
            'userId'   => $userId,
            'userData' => $form->getValues(),
        ]);
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    $ret = false;

    if ($passwordUpdated) {
        # Fixme: Add specific message for login data renewal
        $ret = send_add_user_auto_msg(
            $userId, $data['admin_name'], $form->getValue('admin_pass'), $form->getValue('email'),
            $form->getValue('fname'), $form->getValue('lname'),
            ($data['admin_type'] == 'admin') ? tr('Administrator') : tr('Customer')
        );
    }

    if ($userType == 'user') {
        send_request();
    }

    write_log(sprintf('The %s user has been updated by %s', $data['admin_name'], $_SESSION['user_logged']), E_USER_NOTICE);
    set_page_message('User has been updated.', 'success');

    if ($ret) {
        set_page_message(tr('New login data were sent to the %s user.', decode_idna($data['admin_name'])), 'success');
    }

    redirectTo("user_edit.php?edit_id=$userId");
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @param Zend_Form $form
 * @param int $userId User unique identifier
 *
 * @return void
 */
function generatePage(TemplateEngine $tpl, Zend_Form $form, $userId)
{
    global $userType;

    $tpl->form = $form;
    $tpl->editId = $userId;

    if (!empty($_POST)) {
        return;
    }

    $stmt = exec_query(
        "
            SELECT admin_name, admin_type, fname, lname, IFNULL(gender, 'U') as gender, firm, zip, city, state, country,
                street1, street2, email, phone, fax
            FROM admin
            WHERE admin_id = ?
        ",
        $userId
    );

    if (!($data = $stmt->fetchRow())) {
        showBadRequestErrorPage();
    }

    $userType = $data['admin_type'];
    $form->setDefaults($data);
}


/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

EventsManager::getInstance()->dispatch(Events::onAdminScriptStart);
check_login('admin');

if (!isset($_GET['edit_id'])) {
    showBadRequestErrorPage();
}

$userId = intval($_GET['edit_id']);

if ($userId == $_SESSION['user_id']) {
    redirectTo('personal_change.php');
}

global $userType;

$form = getUserLoginDataForm()->addElements(getUserPersonalDataForm()->getElements());

if (!empty($_POST)) {
    updateUserData($form, $userId);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'shared/partials/user_edit.phtml',
    'page_message' => 'layout'
]);

generateNavigation($tpl);
generatePageMessage($tpl);
generatePage($tpl, $form, $userId);

if ($userType == 'admin') {
    $tpl->assign([
        'TR_PAGE_TITLE'       => tohtml(tr('Admin / Users / Overview / Edit Admin')),
        'TR_DYNAMIC_TITLE'    => tr('Edit admin'),
        'DYNAMIC_TITLE_CLASS' => 'user_yellow'
    ]);
} else {
    $tpl->assign([
        'TR_PAGE_TITLE'       => tohtml(tr('Admin / Users / Overview / Edit Customer')),
        'TR_DYNAMIC_TITLE'    => tr('Edit customer'),
        'DYNAMIC_TITLE_CLASS' => 'user_blue'
    ]);
}

$tpl->parse('LAYOUT_CONTENT', 'page');
EventsManager::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
