<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

require '../include/ispcp-lib.php';

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('PURCHASE_TEMPLATE_PATH') . '/address.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('purchase_header', 'page');
$tpl->define_dynamic('purchase_footer', 'page');

/*
* Functions start
*/

function gen_address(&$tpl, &$sql, $user_id, $plan_id) {
    if (isset($_POST['fname'])) {
        $first_name = $_POST['fname'];
    } else if (isset($_SESSION['fname'])) {
        $first_name = $_SESSION['fname'];
    } else {
        $first_name = '';
    }

    if (isset($_POST['lname'])) {
        $last_name = $_POST['lname'];
    } else if (isset($_SESSION['lname'])) {
        $last_name = $_SESSION['lname'];
    } else {
        $last_name = '';
    }

    if (isset($_POST['email'])) {
        $email = $_POST['email'];
    } else if (isset($_SESSION['email'])) {
        $email = $_SESSION['email'];
    } else {
        $email = '';
    }

    if (isset($_POST['gender'])) {
        $gender = $_POST['gender'];
    } else if (isset($_SESSION['gender'])) {
        $gender = $_SESSION['gender'];
    } else {
        $gender = 'U';
    }

    if (isset($_POST['firm'])) {
        $company = $_POST['firm'];
    } else if (isset($_SESSION['firm'])) {
        $company = $_SESSION['firm'];
    } else {
        $company = '';
    }

    if (isset($_POST['zip'])) {
        $postal_code = $_POST['zip'];
    } else if (isset($_SESSION['zip'])) {
        $postal_code = $_SESSION['zip'];
    } else {
        $postal_code = '';
    }

    if (isset($_POST['city'])) {
        $city = $_POST['city'];
    } else if (isset($_SESSION['city'])) {
        $city = $_SESSION['city'];
    } else {
        $city = '';
    }

    if (isset($_POST['country'])) {
        $country = $_POST['country'];
    } else if (isset($_SESSION['country'])) {
        $country = $_SESSION['country'];
    } else {
        $country = '';
    }

    if (isset($_POST['street1'])) {
        $street1 = $_POST['street1'];
    } else if (isset($_SESSION['street1'])) {
        $street1 = $_SESSION['street1'];
    } else {
        $street1 = '';
    }

    if (isset($_POST['street2'])) {
        $street2 = $_POST['street2'];
    } else if (isset($_SESSION['street2'])) {
        $street2 = $_SESSION['street2'];
    } else {
        $street2 = '';
    }

    if (isset($_POST['phone'])) {
        $phone = $_POST['phone'];
    } else if (isset($_SESSION['phone'])) {
        $phone = $_SESSION['phone'];
    } else {
        $phone = '';
    }

    if (isset($_POST['fax'])) {
        $fax = $_POST['fax'];
    } else if (isset($_SESSION['fax'])) {
        $fax = $_SESSION['fax'];
    } else {
        $fax = '';
    }

    $tpl->assign(
        array('VL_USR_NAME' => $first_name,
            'VL_LAST_USRNAME' => $last_name,
            'VL_EMAIL' => $email,
            'VL_USR_FIRM' => $company,
            'VL_USR_POSTCODE' => $postal_code,
            'VL_USRCITY' => $city,
            'VL_COUNTRY' => $country,
            'VL_STREET1' => $street1,
            'VL_STREET2' => $street2,
            'VL_PHONE' => $phone,
            'VL_FAX' => $fax,
            'VL_MALE' => (($gender === 'M') ? 'checked' : ''),
            'VL_FEMALE' => (($gender === 'F') ? 'checked' : ''),
            'VL_UNKNOWN' => (($gender == 'U') ? 'checked' : '')
            )
        );
}

function check_address_data(&$tpl) {
    if (isset($_GET['edit']))
        unset($_GET['edit']);
    if (
        (isset($_POST['fname']) && $_POST['fname'] != '') and
            (isset($_POST['email']) && $_POST['email'] != '') and
            chk_email($_POST['email']) and
            (isset($_POST['lname']) && $_POST['lname'] != '') and
            (isset($_POST['zip']) && $_POST['zip'] != '') and
            (isset($_POST['city']) && $_POST['city'] != '') and
            (isset($_POST['country']) && $_POST['country'] != '') and
            (isset($_POST['street1']) && $_POST['street1'] != '') and
            (isset($_POST['phone']) && $_POST['phone'] != '')
            ) {
        $_SESSION['fname'] = $_POST['fname'];
        $_SESSION['lname'] = $_POST['lname'];
        $_SESSION['email'] = $_POST['email'];
        $_SESSION['zip'] = $_POST['zip'];
        $_SESSION['city'] = $_POST['city'];
        $_SESSION['country'] = $_POST['country'];
        $_SESSION['street1'] = $_POST['street1'];
        $_SESSION['phone'] = $_POST['phone'];

        if (isset($_POST['firm']) && $_POST['firm'] != '') {
            $_SESSION['firm'] = $_POST['firm'];
        }

        if (isset($_POST['gender']) && get_gender_by_code($_POST['gender'], true) !== null) {
            $_SESSION['gender'] = $_POST['gender'];
        } else {
            $_SESSION['gender'] = '';
        }

        if (isset($_POST['street2']) && $_POST['street2'] != '') {
            $_SESSION['street2'] = $_POST['street2'];
        }

        if (isset($_POST['fax']) && $_POST['fax'] != '') {
            $_SESSION['fax'] = $_POST['fax'];
        }

        header("Location: chart.php");
        die();
    } else {
        set_page_message(tr('Please fill out all needed fields!'));
        $_GET['edit'] = "yes";
    }
}

/*
* Functions end
*/

/*
*
* static page messages.
*
*/

if (isset($_SESSION['user_id']) && $_SESSION['plan_id']) {
    $user_id = $_SESSION['user_id'];
    $plan_id = $_SESSION['plan_id'];
} else {
    system_message(tr('You do not have permission to access this interface!'));
}

if (isset($_POST['uaction']) && $_POST['uaction'] == 'address')
    check_address_data($tpl);

if (
    (isset($_SESSION['fname']) && $_SESSION['fname'] != '') and
        (isset($_SESSION['email']) && $_SESSION['email'] != '') and
        (isset($_SESSION['lname']) && $_SESSION['lname'] != '') and
        (isset($_SESSION['zip']) && $_SESSION['zip'] != '') and
        (isset($_SESSION['city']) && $_SESSION['city'] != '') and
        (isset($_SESSION['country']) && $_SESSION['country'] != '') and
        (isset($_SESSION['street1']) && $_SESSION['street1'] != '') and
        (isset($_SESSION['phone']) && $_SESSION['phone'] != '') and
        !isset($_GET['edit'])
        ) {
    header("Location: chart.php");
    die();
}

gen_purchase_haf($tpl, $sql, $user_id);
gen_address($tpl, $sql, $user_id, $plan_id);

gen_page_message($tpl);

$tpl->assign(
    array('TR_ADDRESS' => tr('Enter Address'),
        'TR_FIRSTNAME' => tr('First name'),
        'TR_LASTNAME' => tr('Last name'),
        'TR_COMPANY' => tr('Company'),
        'TR_POST_CODE' => tr('Zip/Postal code'),
        'TR_CITY' => tr('City'),
        'TR_COUNTRY' => tr('Country'),
        'TR_STREET1' => tr('Street 1'),
        'TR_STREET2' => tr('Street 2'),
        'TR_EMAIL' => tr('Email'),
        'TR_PHONE' => tr('Phone'),
        'TR_GENDER' => tr('Gender'),
        'TR_MALE' => tr('Male'),
        'TR_FEMALE' => tr('Female'),
        'TR_UNKNOWN' => tr('Unknown'),
        'TR_FAX' => tr('Fax'),
        'TR_CONTINUE' => tr('Continue'),
        'NEED_FILLED' => tr('* denotes mandatory field.'),
        'THEME_CHARSET' => tr('encoding')
        )
    );

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
    dump_gui_debug();

unset_messages();

?>