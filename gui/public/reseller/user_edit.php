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

use iMSCP\Crypt as Crypt;
use iMSCP_Events as Events;
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;
use Zend_Form as Form;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Update user data
 *
 * @throws Exception
 * @param Form $form
 * @param int $userId User unique identifier
 * @return void
 */
function updateUserData(Form $form, $userId)
{
    $data = exec_query(
        'SELECT admin_name FROM admin WHERE admin_id = ? AND created_by = ?', [$userId, $_SESSION['user_id']]
    )->fetch();

    if (!$data) {
        showBadRequestErrorPage();
    }

    if (!$form->isValid($_POST)) {
        foreach ($form->getMessages() as $msgsStack) {
            foreach ($msgsStack as $msg) {
                set_page_message(tohtml($msg), 'error');
            }
        }

        return;
    }

    $passwordUpdated = $form->getValue('admin_pass') !== '';

    /** @var iMSCP_Database $db */
    $db = Registry::get('iMSCP_Application')->getDatabase();

    try {
        $db->beginTransaction();

        Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onBeforeEditUser, [
            'userId'   => $userId,
            'userData' => $form->getValues()
        ]);

        exec_query(
            "
                UPDATE admin
                SET admin_pass = IFNULL(?, admin_pass), fname = ?, lname = ?, firm = ?, zip = ?, city = ?, state = ?,
                    country = ?, email = ?, phone = ?, fax = ?, street1 = ?, street2 = ?, gender = ?,
                    admin_status = IF(?, 'tochangepwd', admin_status)
                WHERE admin_id = ?
            ",
            [
                $passwordUpdated ? Crypt::apr1MD5($form->getValue('admin_pass')) : NULL, $form->getValue('fname'),
                $form->getValue('lname'), $form->getValue('firm'), $form->getValue('zip'), $form->getValue('city'),
                $form->getValue('state'), $form->getValue('country'), encode_idna($form->getValue('email')),
                $form->getValue('phone'), $form->getValue('fax'), $form->getValue('street1'), $form->getValue('street2'),
                $form->getValue('gender'), $passwordUpdated ? 1 : 0, $userId
            ]
        );


        // Force user to login again (needed due to possible password or email change)
        exec_query('DELETE FROM login WHERE user_name = ?', [$data['admin_name']]);

        Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAfterEditUser, [
            'userId'   => $userId,
            'userData' => $form->getValues()
        ]);
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    $ret = false;

    if ($passwordUpdated) {
        $ret = send_add_user_auto_msg(
            $userId, $data['admin_name'], $form->getValue('admin_pass'), $form->getValue('email'),
            $form->getValue('fname'), $form->getValue('lname'), tr('Customer')
        );
    }

    send_request();
    write_log(
        sprintf('The %s user has been updated by %s', $data['admin_name'], $_SESSION['user_logged']),
        E_USER_NOTICE
    );
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
 * @param Form $form
 * @param int $userId User unique identifier
 *
 * @return void
 */
function generatePage(TemplateEngine $tpl, Form $form, $userId)
{
    $tpl->form = $form;
    $tpl->editId = $userId;

    if (!empty($_POST)) {
        $form->setDefault('admin_name', get_user_name($userId));
        return;
    }

    $stmt = exec_query(
        "
            SELECT admin_name, fname, lname, IFNULL(gender, 'U') as gender, firm, zip, city, state, country, street1,
                street2, email, phone, fax
            FROM admin
            WHERE admin_id = ?
            AND created_by = ?
        ",
        [$userId, $_SESSION['user_id']]
    );

    if (!($data = $stmt->fetch())) {
        showBadRequestErrorPage();
    }

    $form->setDefaults($data);
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('reseller');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onResellerScriptStart);

if (!isset($_GET['edit_id'])) {
    showBadRequestErrorPage();
}

$userId = intval($_GET['edit_id']);

if ($userId == $_SESSION['user_id']) {
    redirectTo('personal_change.php');
}

$form = getUserLoginDataForm(false, false)->addElements(getUserPersonalDataForm()->getElements());

if (!empty($_POST)) {
    updateUserData($form, $userId);
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'shared/partials/user_edit.phtml',
    'page_message' => 'layout'
]);
$tpl->assign('TR_PAGE_TITLE', tohtml(tr('Reseller / Customers / Overview / Edit Customer')));

generateNavigation($tpl);
generatePage($tpl, $form, $userId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
