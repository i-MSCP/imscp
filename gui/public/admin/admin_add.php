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

/**
 * Add user
 *
 * @param  $tpl iMSCP_pTemplate
 * @return void
 */
function add_user($tpl)
{
    $cfg = iMSCP_Registry::get('config');

    if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_user') {
        iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeAddUser);

        if (check_user_data()) {
            $upass = \iMSCP\Crypt::apr1MD5($_POST['password']);
            $user_id = $_SESSION['user_id'];
            $username = clean_input($_POST['username']);
            $fname = clean_input($_POST['fname']);
            $lname = clean_input($_POST['lname']);
            $gender = clean_input($_POST['gender']);
            $firm = clean_input($_POST['firm']);
            $zip = clean_input($_POST['zip']);
            $city = clean_input($_POST['city']);
            $state = clean_input($_POST['state']);
            $country = clean_input($_POST['country']);
            $email = clean_input($_POST['email']);
            $phone = clean_input($_POST['phone']);
            $fax = clean_input($_POST['fax']);
            $street1 = clean_input($_POST['street1']);
            $street2 = clean_input($_POST['street2']);

            if (get_gender_by_code($gender, true) === NULL) {
                $gender = '';
            }

            exec_query(
                "
                  INSERT INTO admin (
                    admin_name, admin_pass, admin_type, domain_created, created_by, fname, lname, firm,
                    zip, city, state, country, email, phone, fax, street1, street2, gender
                ) VALUES (
                  ?, ?, 'admin', unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
                ",
                array(
                    $username, $upass, $user_id, $fname, $lname, $firm, $zip, $city, $state, $country, $email, $phone,
                    $fax, $street1, $street2, $gender
                ));

            /** @var $db iMSCP_Database */
            $db = iMSCP_Registry::get('db');
            $new_admin_id = $db->insertId();
            $user_logged = $_SESSION['user_logged'];
            write_log("$user_logged: add admin: $username", E_USER_NOTICE);
            $user_def_lang = $cfg['USER_INITIAL_LANG'];
            $user_theme_color = $cfg['USER_INITIAL_THEME'];

            exec_query('REPLACE INTO user_gui_props (user_id, lang, layout) VALUES (?, ?, ?)', array(
                $new_admin_id, $user_def_lang, $user_theme_color
            ));

            iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterAddUser);
            send_add_user_auto_msg(
                $user_id,
                clean_input($_POST['username']),
                clean_input($_POST['password']),
                clean_input($_POST['email']),
                clean_input($_POST['fname']),
                clean_input($_POST['lname']),
                tr('Administrator')
            );
            set_page_message(tr('Admin account successfully created.'), 'success');
            redirectTo('manage_users.php');
        } else { // check user data
            $tpl->assign(array(
                'EMAIL'      => clean_input($_POST['email']),
                'USERNAME'   => clean_input($_POST['username']),
                'FIRST_NAME' => clean_input($_POST['fname']),
                'LAST_NAME'  => clean_input($_POST['lname']),
                'FIRM'       => clean_input($_POST['firm']),
                'ZIP'        => clean_input($_POST['zip']),
                'CITY'       => clean_input($_POST['city']),
                'STATE'      => clean_input($_POST['state']),
                'COUNTRY'    => clean_input($_POST['country']),
                'STREET_1'   => clean_input($_POST['street1']),
                'STREET_2'   => clean_input($_POST['street2']),
                'PHONE'      => clean_input($_POST['phone']),
                'FAX'        => clean_input($_POST['fax']),
                'VL_MALE'    => ($_POST['gender'] == 'M') ? ' selected' : '',
                'VL_FEMALE'  => ($_POST['gender'] == 'F') ? ' selected' : '',
                'VL_UNKNOWN' => (($_POST['gender'] == 'U') || empty($_POST['gender'])) ? ' selected' : ''
            ));
        }

        return;
    }

    $tpl->assign(array(
        'EMAIL'      => '',
        'USERNAME'   => '',
        'FIRST_NAME' => '',
        'LAST_NAME'  => '',
        'FIRM'       => '',
        'ZIP'        => '',
        'CITY'       => '',
        'STATE'      => '',
        'COUNTRY'    => '',
        'STREET_1'   => '',
        'STREET_2'   => '',
        'PHONE'      => '',
        'FAX'        => '',
        'VL_MALE'    => '',
        'VL_FEMALE'  => '',
        'VL_UNKNOWN' => ' selected'
    ));
}

/**
 * Check user data
 *
 * @return bool
 */
function check_user_data()
{
    if (!validates_username($_POST['username'])) {
        set_page_message(tr('Incorrect username length or syntax.'), 'error');
        return false;
    }

    if ($_POST['password'] != $_POST['password_confirmation']) {
        set_page_message(tr("Passwords do not match."), 'error');
        return false;
    }

    if (!checkPasswordSyntax($_POST['password'])) {
        return false;
    }

    if (!chk_email($_POST['email'])) {
        set_page_message(tr("Incorrect email length or syntax."), 'error');
        return false;
    }

    $stmt = exec_query('SELECT admin_id FROM admin WHERE admin_name = ?', clean_input($_POST['username']));
    if ($stmt->rowCount()) {
        set_page_message(tr('This user name already exist.'), 'warning');
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

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/admin_add.tpl',
    'page_message' => 'layout'
));

$tpl->assign('TR_PAGE_TITLE', tr('Admin / Users / Add Admin'));


$tpl->assign(array(
    'TR_EMPTY_OR_WORNG_DATA' => tr('Empty data or wrong field.'),
    'TR_PASSWORD_NOT_MATCH'  => tr("Passwords do not match."),
    'TR_ADD_ADMIN'           => tr('Add admin'),
    'TR_CORE_DATA'           => tr('Core data'),
    'TR_USERNAME'            => tr('Username'),
    'TR_PASSWORD'            => tr('Password'),
    'TR_PASSWORD_REPEAT'     => tr('Password confirmation'),
    'TR_EMAIL'               => tr('Email'),
    'TR_ADDITIONAL_DATA'     => tr('Additional data'),
    'TR_FIRST_NAME'          => tr('First name'),
    'TR_LAST_NAME'           => tr('Last name'),
    'TR_GENDER'              => tr('Gender'),
    'TR_MALE'                => tr('Male'),
    'TR_FEMALE'              => tr('Female'),
    'TR_UNKNOWN'             => tr('Unknown'),
    'TR_COMPANY'             => tr('Company'),
    'TR_ZIP_POSTAL_CODE'     => tr('Zip/Postal code'),
    'TR_CITY'                => tr('City'),
    'TR_STATE'               => tr('State/Province'),
    'TR_COUNTRY'             => tr('Country'),
    'TR_STREET_1'            => tr('Street 1'),
    'TR_STREET_2'            => tr('Street 2'),
    'TR_PHONE'               => tr('Phone'),
    'TR_FAX'                 => tr('Fax'),
    'TR_ADD'                 => tr('Add')
));

generateNavigation($tpl);
add_user($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
unsetMessages();
