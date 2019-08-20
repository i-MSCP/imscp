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

use iMSCP\Crypt;
use iMSCP\Database\DatabaseMySQL;
use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\Registry;
use iMSCP\TemplateEngine;

/**
 * Add admin user
 *
 * @param Zend_Form $form
 * @return void
 */
function addAdminUser(Zend_Form $form)
{
    if (!$form->isValid($_POST)) {
        foreach ($form->getMessages() as $fieldname => $msgsStack) {
            set_page_message(reset($msgsStack), 'error');
        }

        return;
    }

    $db = DatabaseMySQL::getInstance();

    try {
        $db->beginTransaction();

        EventAggregator::getInstance()->dispatch(Events::onBeforeAddUser, [
            'userData' => $form->getValues()
        ]);

        exec_query(
            "
                INSERT INTO admin (
                    admin_name, admin_pass, admin_type, domain_created, created_by, fname, lname, firm, zip, city,
                    state, country, email, phone, fax, street1, street2, gender
                ) VALUES (
                    ?, ?, 'admin', unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ",
            [
                $form->getValue('admin_name'), Crypt::apr1MD5($form->getValue('admin_pass')), $_SESSION['user_id'],
                $form->getValue('fname'), $form->getValue('lname'), $form->getValue('firm'), $form->getValue('zip'),
                $form->getValue('city'), $form->getValue('state'), $form->getValue('country'),
                encode_idna($form->getValue('email')), $form->getValue('phone'), $form->getValue('fax'),
                $form->getValue('street1'), $form->getValue('street2'), $form->getValue('gender')
            ]
        );

        $adminId = $db->insertId();
        $cfg = Registry::get('config');

        exec_query('INSERT INTO user_gui_props (user_id, lang, layout) VALUES (?, ?, ?)', [
            $adminId, $cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME']
        ]);

        EventAggregator::getInstance()->dispatch(Events::onAfterAddUser, [
            'userId'   => $adminId,
            'userData' => $form->getValues()
        ]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    send_add_user_auto_msg(
        $_SESSION['user_id'], $form->getValue('admin_name'), $form->getValue('admin_pass'), $form->getValue('email'),
        $form->getValue('fname'), $form->getValue('lname'), tr('Administrator')
    );
    write_log(
        sprintf('The %s administrator has been added by %s', $form->getValue('admin_name'), $_SESSION['user_logged']),
        E_USER_NOTICE
    );
    set_page_message('Administrator has been added.', 'success');
    redirectTo('users.php');
}

require 'imscp-lib.php';

check_login('admin');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptStart);

$form = getUserLoginDataForm(true, true)->addElements(getUserPersonalDataForm()->getElements());
$form->setDefault('gender', 'U');

if (!empty($_POST)) {
    addAdminUser($form);
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/admin_add.phtml',
    'page_message' => 'layout'
]);
$tpl->assign('TR_PAGE_TITLE', tohtml(tr('Admin / Users / Add Admin')));

generateNavigation($tpl);
generatePageMessage($tpl);

/** @noinspection PhpUndefinedFieldInspection */
$tpl->form = $form;

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
