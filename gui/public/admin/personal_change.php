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

use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_pTemplate as TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Update administrator personal data
 *
 * @return void
 */
function updatePersonalData()
{
    $input = getUserPersonalDataInputFilter($_POST);

    if (!$input->isValid()) {
        if ($input->hasMissing() || !$input->isValid('gender')) {
            showBadRequestErrorPage();
        }

        foreach ($input->getMessages() as $msgStack => $msg) {
            set_page_message(reset($msg), 'error');
        }

        return;
    }

    EventsManager::getInstance()->dispatch(Events::onBeforeEditUser, [
        'userId'   => $_SESSION['user_id'],
        'userData' => $input
    ]);
    exec_query(
        "
            UPDATE admin
            SET fname = ?, lname = ?, firm = ?, zip = ?, city = ?, state = ?, country = ?, street1 = ?, street2 = ?,
                email = ?, phone = ?, fax = ?, gender = ?
            WHERE admin_id = ?
        ",
        [
            $input->fname, $input->lname, $input->firm, $input->zip, $input->city, $input->state, $input->country,
            $input->street1, $input->street2, encode_idna($input->email), $input->phone, $input->fax, $input->gender,
            $_SESSION['user_id']
        ]
    );
    EventsManager::getInstance()->dispatch(Events::onAfterEditUser, [
        'userId'   => $_SESSION['user_id'],
        'userData' => $input
    ]);
    set_page_message(tr('Personal data successfully updated.'), 'success');
    redirectTo('personal_change.php');
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function generatePage(TemplateEngine $tpl)
{
    if (empty($_POST)) {
        $stmt = exec_query(
            '
                SELECT fname, lname, gender, firm, zip, city, state, country, street1, street2, email, phone, fax
                FROM admin
                WHERE admin_id = ?
            ',
            $_SESSION['user_id']
        );

        $data = $stmt->fetchRow(PDO::FETCH_ASSOC);
    } else {
        $data =& $_POST;
    }

    $tpl->assign([
        'FIRST_NAME' => tohtml($data['fname'], 'htmlAttr'),
        'LAST_NAME'  => tohtml($data['lname'], 'htmlAttr'),
        'VL_MALE'    => ($data['gender'] === 'M') ? ' selected' : '',
        'VL_FEMALE'  => ($data['gender'] === 'F') ? ' selected' : '',
        'VL_UNKNOWN' => ($data['gender'] === 'U' || !in_array($data['gender'], ['M', 'F'], true)) ? ' selected' : '',
        'FIRM'       => tohtml($data['firm'], 'htmlAttr'),
        'STREET_1'   => tohtml($data['street1'], 'htmlAttr'),
        'STREET_2'   => tohtml($data['street2'], 'htmlAttr'),
        'ZIP'        => tohtml($data['zip'], 'htmlAttr'),
        'CITY'       => tohtml($data['city'], 'htmlAttr'),
        'STATE'      => tohtml($data['state'], 'htmlAttr'),
        'COUNTRY'    => tohtml($data['country'], 'htmlAttr'),
        'EMAIL'      => tohtml(decode_idna($data['email']), 'htmlAttr'),
        'PHONE'      => tohtml($data['phone'], 'htmlAttr'),
        'FAX'        => tohtml($data['fax'], 'htmlAttr')
    ]);
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

EventsManager::getInstance()->dispatch(Events::onAdminScriptStart);
check_login('admin');

if (!empty($_POST)) {
    updatePersonalData();
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'shared/partials/personal_change.phtml',
    'page_message' => 'layout'
]);
$tpl->assign('TR_PAGE_TITLE', tohtml(tr('Admin / Profile / Personal Data')));

generateNavigation($tpl);
generatePageMessage($tpl);
generatePage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventsManager::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
