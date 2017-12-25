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

use iMSCP_Authentication as Authentication;
use iMSCP_Events as Events;
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;
use Zend_Form as Form;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Update personal data
 *
 * @param Form $form
 * @return void
 */
function updatePersonalData(Form $form)
{
    if (!$form->isValid($_POST)) {
        foreach ($form->getMessages() as $msgsStack) {
            foreach ($msgsStack as $msg) {
                set_page_message(tohtml($msg), 'error');
            }
        }

        return;
    }

    $idnaEmail = $form->getValue('email');

    Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onBeforeEditUser, [
        'userId'   => $_SESSION['user_id'],
        'userData' => $form->getValues()
    ]);
    exec_query(
        "
            UPDATE admin
            SET fname = ?, lname = ?, firm = ?, zip = ?, city = ?, state = ?, country = ?, email = ?, phone = ?,
                fax = ?, street1 = ?, street2 = ?, gender = ?
            WHERE admin_id = ?
        ",
        [
            $form->getValue('fname'), $form->getValue('lname'), $form->getValue('firm'), $form->getValue('zip'),
            $form->getValue('city'), $form->getValue('state'), $form->getValue('country'),
            $idnaEmail, $form->getValue('phone'), $form->getValue('fax'), $form->getValue('street1'),
            $form->getValue('street2'), $form->getValue('gender'), $_SESSION['user_id']
        ]
    );

    # We need also update user email in session
    Authentication::getInstance()->getIdentity()->email = $idnaEmail;
    $_SESSION['user_email'] = $idnaEmail; // Only for backward compatibility

    Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAfterEditUser, [
        'userId'   => $_SESSION['user_id'],
        'userData' => $form->getValues()
    ]);
    write_log(sprintf('The %s user data were updated', $_SESSION['user_logged']), E_USER_NOTICE);
    set_page_message(tr('Personal data were updated.'), 'success');
    redirectTo('personal_change.php');
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @param Form $form
 * @return void
 */
function generatePage(TemplateEngine $tpl, Form $form)
{
    $tpl->form = $form;

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
        [$_SESSION['user_id']]
    );

    if (!($data = $stmt->fetch())) {
        showBadRequestErrorPage();
    }

    $form->setDefaults($data);
}

/***********************************************************************************************************************
 * Main
 */

$form = getUserPersonalDataForm();

if (!empty($_POST)) {
    updatePersonalData($form);
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'shared/partials/personal_change.phtml',
    'page_message' => 'layout'
]);

generateNavigation($tpl);
generatePage($tpl, $form);
generatePageMessage($tpl);
