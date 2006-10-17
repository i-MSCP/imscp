<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware		            		|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------


function gen_reseller_personal_data(&$tpl, &$sql, $user_id)
{
  $query = <<<SQL_QUERY
        select
            fname,
            lname,
            firm,
            zip,
            city,
            country,
            email,
            phone,
            fax,
            street1,
            street2
        from
            admin
        where
            admin_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($user_id));

  $tpl -> assign(
                    array(
                            'FIRST_NAME' => $rs -> fields['fname'],
                            'LAST_NAME' => $rs -> fields['lname'],
                            'FIRM' => $rs -> fields['firm'],
                            'ZIP' => $rs -> fields['zip'],
                            'CITY' => $rs -> fields['city'],
                            'COUNTRY' => $rs -> fields['country'],
                            'EMAIL' => $rs -> fields['email'],
                            'PHONE' => $rs -> fields['phone'],
                            'FAX' => $rs -> fields['fax'],
							'STREET1' => $rs -> fields['street1'],
                            'STREET2' => $rs -> fields['street2']

                         )
                  );

}




function update_reseller_personal_data(&$sql, $user_id)
{

    $fname 		= clean_input($_POST['fname']);
    $lname 		= clean_input($_POST['lname']);
    $firm 		= clean_input($_POST['firm']);
    $zip 		= clean_input($_POST['zip']);
    $city 		= clean_input($_POST['city']);
    $country	= clean_input($_POST['country']);
    $street1 	= clean_input($_POST['street1']);
    $street2 	= clean_input($_POST['street2']);
    $email 		= clean_input($_POST['email']);
    $phone 		= clean_input($_POST['phone']);
    $fax 		= clean_input($_POST['fax']);

    $query = <<<SQL_QUERY
        update
            admin
        set
            fname = ?,
            lname = ?,
            firm = ?,
            zip = ?,
            city = ?,
            country = ?,
            email = ?,
            phone = ?,
            fax = ?,
            street1 = ?,
            street2 = ?
        where
            admin_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($fname, $lname, $firm, $zip, $city, $country, $email, $phone, $fax, $street1, $street2, $user_id));

    set_page_message(tr('Personal data updated successfully!'));

}


//
// Begin process page lines
//

include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/change_personal.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE' => tr('VHCS - Reseller/Change Personal Data'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                     )
              );

if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_data') {

	update_reseller_personal_data($sql, $_SESSION['user_id']);

}

gen_reseller_personal_data($tpl, $sql, $_SESSION['user_id']);

/*
 *
 * static page messages.
 *
 */

gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_general_information.tpl');

gen_logged_from($tpl);

$tpl -> assign(
                array(
                        'TR_CHANGE_PERSONAL_DATA' => tr('Change personal data'),
                        'TR_PERSONAL_DATA' => tr('Personal data'),
                        'TR_FIRST_NAME' => tr('First name'),
                        'TR_LAST_NAME' => tr('Last name'),
                        'TR_COMPANY' => tr('Company'),
                        'TR_ZIP_POSTAL_CODE' => tr('Zip/Postal code'),
                        'TR_CITY' => tr('City'),
                        'TR_COUNTRY' => tr('Country'),
                        'TR_STREET_1' => tr('Street 1'),
                        'TR_STREET_2' => tr('Street 2'),
                        'TR_EMAIL' => tr('Email'),
                        'TR_PHONE' => tr('Phone'),
                        'TR_FAX' => tr('Fax'),
                        'TR_UPDATE_DATA' => tr('Update data'),
                     )
              );

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>
