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
 * Update user data
 *
 * @param int $userId Customer unique identifier
 * @return void
 */
function admin_updateUserData($userId)
{
    global $userData;

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditUser, array('userId' => $userId));

    $fname = isset($_POST['fname']) ? clean_input($_POST['fname']) : '';
    $lname = isset($_POST['lname']) ? clean_input($_POST['lname']) : '';
    $firm = isset($_POST['firm']) ? clean_input($_POST['firm']) : '';
    $gender = isset($_POST['gender']) ? clean_input($_POST['gender']) : '';
    $zip = isset($_POST['zip']) ? clean_input($_POST['zip']) : '';
    $city = isset($_POST['city']) ? clean_input($_POST['city']) : '';
    $state = isset($_POST['state']) ? clean_input($_POST['state']) : '';
    $country = isset($_POST['country']) ? clean_input($_POST['country']) : '';
    $email = clean_input($_POST['email']);
    $phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';
    $fax = isset($_POST['fax']) ? clean_input($_POST['fax']) : '';
    $street1 = isset($_POST['street1']) ? clean_input($_POST['street1']) : '';
    $street2 = isset($_POST['street2']) ? clean_input($_POST['street2']) : '';
    $userName = get_user_name($userId);
    $password = clean_input($_POST['password']);


    if ($password === '') {
        exec_query(
            '
              UPDATE admin
              SET fname = ?, lname = ?, firm = ?, zip = ?, city = ?, state = ?, country = ?, email = ?, phone = ?,
                fax = ?, street1 = ?, street2 = ?, gender = ?
              WHERE admin_id = ?
            ',
            array(
                $fname, $lname, $firm, $zip, $city, $state, $country, $email, $phone, $fax, $street1, $street2,
                $gender, $userId
            )
        );
    } else {
        exec_query(
            "
              UPDATE admin
              SET admin_pass = ?, fname = ?, lname = ?, firm = ?, zip = ?, city = ?, state = ?, country = ?, email = ?,
                phone = ?, fax = ?, street1 = ?, street2 = ?, gender = ?,
                admin_status = IF(admin_type = 'user', 'tochangepwd', admin_status)
              WHERE
                admin_id = ?
            ",
            array(
                iMSCP\Crypt::apr1MD5($password), $fname, $lname, $firm, $zip, $city, $state, $country, $email,
                $phone, $fax, $street1, $street2, $gender, $userId
            ));

        exec_query('DELETE FROM login WHERE user_name = ?', $userName);
    }

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditUser, array('userId' => $userId));

    if (isset($_POST['send_data']) && !empty($_POST['password'])) {
        if ($userData['admin_type'] == 'admin') {
            $userType = tr('Administrator');
        } elseif ($userData['admin_type'] == 'reseller') {
            $userType = tr('Reseller');
        } else {
            $userType = tr('Customer');
        }

        send_add_user_auto_msg($userId, $userName, $password, $email, $fname, $lname, $userType);
    }

    send_request();
}

/**
 * Validate input data
 *
 * @access private
 * @return bool TRUE if data are valid, FALSE otherwise
 */
function admin_isValidData()
{
    if (!isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['password_confirmation'])) {
        showBadRequestErrorPage();
    }

    if (!chk_email($_POST['email'])) {
        set_page_message(tr('Incorrect email length or syntax.'), 'error');
    }

    if ($_POST['password'] !== '') {
        if ($_POST['password'] !== $_POST['password_confirmation']) {
            set_page_message(tr("Passwords do not match."), 'error');
        }

        checkPasswordSyntax($_POST['password']);
    }

    if (Zend_Session::namespaceIsset('pageMessages')) {
        return false;
    }

    return true;
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);
check_login('admin');

if (!isset($_GET['edit_id'])) {
    showBadRequestErrorPage();
}

$userId = intval($_GET['edit_id']);

if ($userId == $_SESSION['user_id']) {
    redirectTo('personal_change.php');
}

$stmt = exec_query(
    '
      SELECT admin_name, admin_type, fname, lname, firm, zip, city, state, country, phone, fax, street1, street2, email,
        gender
      FROM admin
      WHERE admin_id = ?
    ',
    $userId
);

if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

$userData = $stmt->fetchRow();

if (!empty($_POST) && admin_isValidData()) {
    admin_updateUserData($userId);
    set_page_message(tr('User data successfully updated.'), 'success');
    redirectTo('manage_users.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout'        => 'shared/layouts/ui.tpl',
    'page'          => 'admin/admin_edit.tpl',
    'page_message'  => 'layout',
    'hosting_plans' => 'page'
));

$tpl->assign(array(
    'TR_PAGE_TITLE'          => tr('Admin / Users / Overview / Edit Admin'),
    'TR_EMPTY_OR_WORNG_DATA' => tr('Empty data or wrong field.'),
    'TR_PASSWORD_NOT_MATCH'  => tr("Passwords do not match."),
    'TR_CORE_DATA'           => tr('Core data'),
    'TR_USERNAME'            => tr('Username'),
    'TR_PASSWORD'            => tr('Password'),
    'TR_PASSWORD_REPEAT'     => tr('Password confirmation'),
    'TR_EMAIL'               => tr('Email'),
    'TR_ADDITIONAL_DATA'     => tr('Additional data'),
    'TR_FIRST_NAME'          => tr('First name'),
    'TR_LAST_NAME'           => tr('Last name'),
    'TR_COMPANY'             => tr('Company'),
    'TR_ZIP_POSTAL_CODE'     => tr('Zip/Postal code'),
    'TR_CITY'                => tr('City'),
    'TR_STATE_PROVINCE'      => tr('State/Province'),
    'TR_COUNTRY'             => tr('Country'),
    'TR_STREET_1'            => tr('Street 1'),
    'TR_STREET_2'            => tr('Street 2'),
    'TR_PHONE'               => tr('Phone'),
    'TR_FAX'                 => tr('Fax'),
    'TR_GENDER'              => tr('Gender'),
    'TR_MALE'                => tr('Male'),
    'TR_FEMALE'              => tr('Female'),
    'TR_UNKNOWN'             => tr('Unknown'),
    'TR_UPDATE'              => tr('Update'),
    'TR_SEND_DATA'           => tr('Send new login data'),
    'FIRST_NAME'             => isset($_POST['fname']) ? tohtml($_POST['fname']) : tohtml($userData['fname']),
    'LAST_NAME'              => isset($_POST['lname']) ? tohtml($_POST['lname']) : tohtml($userData['lname']),
    'FIRM'                   => isset($_POST['firm']) ? tohtml($_POST['firm']) : tohtml($userData['firm']),
    'ZIP'                    => isset($_POST['zip']) ? tohtml($_POST['zip']) : tohtml($userData['zip']),
    'CITY'                   => isset($_POST['city']) ? tohtml($_POST['city']) : tohtml($userData['city']),
    'STATE_PROVINCE'         => isset($_POST['state']) ? tohtml($_POST['state']) : tohtml($userData['state']),
    'COUNTRY'                => isset($_POST['country']) ? tohtml($_POST['country']) : tohtml($userData['country']),
    'STREET_1'               => isset($_POST['street1']) ? tohtml($_POST['street1']) : tohtml($userData['street1']),
    'STREET_2'               => isset($_POST['street2']) ? tohtml($_POST['street2']) : tohtml($userData['street2']),
    'PHONE'                  => isset($_POST['phone']) ? tohtml($_POST['phone']) : tohtml($userData['phone']),
    'FAX'                    => isset($_POST['fax']) ? tohtml($_POST['fax']) : tohtml($userData['fax']),
    'USERNAME'               => tohtml(decode_idna($userData['admin_name'])),
    'EMAIL'                  => isset($_POST['email']) ? tohtml($_POST['email']) : tohtml($userData['email']),
    'VL_MALE'                => (isset($_POST['gender']) && $_POST['gender'] == 'M' || $userData['gender'] == 'M') ? ' selected' : '',
    'VL_FEMALE'              => (isset($_POST['gender']) && $_POST['gender'] == 'F' || $userData['gender'] == 'F') ? ' selected' : '',
    'VL_UNKNOWN'             => (
        isset($_POST['gender']) && $_POST['gender'] == 'U'
        || ((!isset($_POST['gender']) && ($userData['gender'] == 'U') || empty($userData['gender'])))
    ) ? ' selected' : '',
    'SEND_DATA_CHECKED'      => (isset($_POST['send_data'])) ? ' checked' : '',
    'EDIT_ID'                => $userId
));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

