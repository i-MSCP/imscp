<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2017 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate admin personal data
 * 
 * @param iMSCP_pTemplate $tpl
 * @param int $userId
 */
function gen_admin_personal_data($tpl, $userId)
{
    $stmt = exec_query(
        '
            SELECT fname, lname, gender, firm, zip, city, state, country, street1, street2, email, phone, fax
            FROM admin
            WHERE admin_id = ?
        ',
        $userId
    );

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

    $tpl->assign([
        'FIRST_NAME' => tohtml(strval($row['fname'])),
        'LAST_NAME'  => tohtml(strval($row['lname'])),
        'FIRM'       => tohtml(strval($row['firm'])),
        'ZIP'        => tohtml(strval($row['zip'])),
        'CITY'       => tohtml(strval($row['city'])),
        'STATE'      => tohtml(strval($row['state'])),
        'COUNTRY'    => tohtml(strval($row['country'])),
        'STREET_1'   => tohtml(strval($row['street1'])),
        'STREET_2'   => tohtml(strval($row['street2'])),
        'EMAIL'      => tohtml(strval($row['email'])),
        'PHONE'      => tohtml(strval($row['phone'])),
        'FAX'        => tohtml(strval($row['fax'])),
        'VL_MALE'    => ((strval($row['gender']) == 'M') ? ' selected' : ''),
        'VL_FEMALE'  => ((strval($row['gender']) == 'F') ? ' selected' : ''),
        'VL_UNKNOWN' => (((strval($row['gender']) == 'U') || (empty($row['gender']))) ? ' selected' : '')
    ]);
}

/**
 * Update administrator personal data
 * 
 * @param int $adminId
 */
function update_admin_personal_data($adminId)
{
    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditUser, ['userId' => $adminId]);

    $fname = clean_input($_POST['fname']);
    $lname = clean_input($_POST['lname']);
    $gender = clean_input($_POST['gender']);
    $firm = clean_input($_POST['firm']);
    $zip = clean_input($_POST['zip']);
    $city = clean_input($_POST['city']);
    $state = clean_input($_POST['state']);
    $country = clean_input($_POST['country']);
    $street1 = clean_input($_POST['street1']);
    $street2 = clean_input($_POST['street2']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $fax = clean_input($_POST['fax']);

    exec_query(
        "
            UPDATE admin
            SET fname = ?, lname = ?, firm = ?, zip = ?, city = ?, state = ?, country = ?, street1 = ?, street2 = ?,
                email = ?, phone = ?, fax = ?, gender = ?
            WHERE admin_id = ?
        ",
        [$fname, $lname, $firm, $zip, $city, $state, $country, $street1, $street2, $email, $phone, $fax, $gender, $adminId]
    );
    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditUser, ['userId' => $adminId]);
    set_page_message(tr('Personal data successfully updated.'), 'success');
    redirectTo('profile.php');
}

/***********************************************************************************************************************
 * Main
 */
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);
check_login('admin');

if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_data') {
    update_admin_personal_data($_SESSION['user_id']);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/personal_change.tpl',
    'page_message' => 'layout'
]);
$tpl->assign('TR_PAGE_TITLE', tr('Admin / Profile / Personal Data'));
$tpl->assign([
    'TR_PERSONAL_DATA'   => tr('Personal data'),
    'TR_FIRST_NAME'      => tr('First name'),
    'TR_LAST_NAME'       => tr('Last name'),
    'TR_COMPANY'         => tr('Company'),
    'TR_ZIP_POSTAL_CODE' => tr('Zip/Postal code'),
    'TR_CITY'            => tr('City'),
    'TR_STATE'           => tr('State/Province'),
    'TR_COUNTRY'         => tr('Country'),
    'TR_STREET_1'        => tr('Street 1'),
    'TR_STREET_2'        => tr('Street 2'),
    'TR_EMAIL'           => tr('Email'),
    'TR_PHONE'           => tr('Phone'),
    'TR_FAX'             => tr('Fax'),
    'TR_GENDER'          => tr('Gender'),
    'TR_MALE'            => tr('Male'),
    'TR_FEMALE'          => tr('Female'),
    'TR_UNKNOWN'         => tr('Unknown'),
    'TR_UPDATE_DATA'     => tr('Update data')
]);

generateNavigation($tpl);
generatePageMessage($tpl);
gen_admin_personal_data($tpl, $_SESSION['user_id']);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
