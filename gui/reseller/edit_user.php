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



include '../include/vhcs-lib.php';

check_login();


$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/edit_user.tpl');

$tpl -> define_dynamic('ip_entry', 'page');

$tpl -> define_dynamic('custom_buttons', 'page');

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];


$tpl -> assign(
                array(
                        'TR_EDIT_USER_PAGE_TITLE' => tr('VHCS - Users/Edit'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                     )
              );

/*
 *
 * static page messages.
 *
 */
	$tpl -> assign(
					array(
							'TR_EDIT_USER' => tr('Edit user'),
							'TR_CORE_DATA' => tr('Core data'),
							'TR_USERNAME' => tr('Username'),
							'TR_PASSWORD' => tr('Password'),
							'TR_REP_PASSWORD' => tr('Password(repeat)'),
							'TR_DMN_IP' => tr('Domain IP'),
							'TR_USREMAIL' => tr('Email'),
							'TR_ADDITIONAL_DATA' => tr('Additional data'),
							'TR_CUSTOMER_ID' => tr('Customer ID'),
							'TR_FIRSTNAME' => tr('First name'),
							'TR_LASTNAME' => tr('Last name'),
							'TR_COMPANY' => tr('Company'),
							'TR_POST_CODE' => tr('Zip/Postal code'),
							'TR_CITY' => tr('City'),
							'TR_COUNTRY' => tr('Country'),
							'TR_STREET1' => tr('Street 1'),
							'TR_STREET2' => tr('Street 2'),
							'TR_MAIL' => tr('Email'),
							'TR_PHONE' => tr('Phone'),
							'TR_FAX' => tr('Fax'),
							'TR_BTN_ADD_USER' => tr('Submit changes')
						)
				);

gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_manage_users.tpl');

gen_logged_from($tpl);

$tpl -> assign(
                array(
							'TR_MANAGE_USERS' => tr('Manage users'),
							'TR_USERS' => tr('Users'),
							'TR_NO' => tr('No.'),
							'TR_USERNAME' => tr('Username'),
							'TR_ACTION' => tr('Action'),
							'TR_BACK' => tr('Back'),
                            'TR_TITLE_BACK' => tr('Return to previous menu'),
                            'TR_TABLE_NAME' => tr('Users list'),
                     )
              );

gen_page_message($tpl);

if (isset($_POST['uaction']) && ('save_changes' === $_POST['uaction'])) {
// Process data
	global $dmn_user_name;

	if(isset($_SESSION['edit_ID']))
		$hpid = $_SESSION['edit_ID'];
	else{
		unset($_SESSION['edit_ID']);
		$_SESSION['edit'] = '_no_';

		Header('Location: users.php');
		die();
	}

	if(isset($_SESSION['user_name']))
		$dmn_user_name = $_SESSION['user_name'];
	else{
		unset($_SESSION['user_name']);
		$_SESSION['edit'] = '_no_';

		Header('Location: users.php');
		die();
	}


    if (check_ruser_data($tpl, '_yes_')) { // Save data to db
		update_data_in_db($hpid);
	}

}else{
	// Get user id that come for edit
	if(isset($_GET['edit_id'])){
		$hpid = $_GET['edit_id'];
	}

	load_user_data_page($hpid);

	$_SESSION['edit_ID'] = $hpid;
	$tpl -> assign('MESSAGE', "");
}
gen_edituser_page($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

//unset_messages();

//
// Begin function block
//

// Load data from sql
function load_user_data_page($user_id)
{
	global $sql;
	global $dmn_user_name;
	global $user_email, $customer_id, $first_name;
    global $last_name, $firm, $zip;
    global $city, $country, $street_one;
	global $street_two, $mail, $phone;
	global $fax;

	$reseller_id = $_SESSION['user_id'];

	$query = <<<SQL_QUERY
		select
			admin_name, created_by, fname, lname, firm,zip,city,country,email,phone,fax,street1,street2, customer_id
		from
			admin
		where
			admin_id = ?
			and
			created_by = ?
SQL_QUERY;

	$res  = exec_query($sql, $query, array($user_id, $reseller_id));
	$data = $res->FetchRow();

	if ($res->RecordCount() == 0) {

		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
		header('Location: users.php');
		die();
	} else {
	// Get data from sql
		$_SESSION['user_name'] = $data['admin_name'];

		$dmn_user_name	=	$data['admin_name'];
		$user_email		=	$data['email'];
		$customer_id	=	$data['customer_id'];
		$first_name		=	$data['fname'];
    	$last_name		=	$data['lname'];
		$firm			=	$data['firm'];
		$zip			=	$data['zip'];
    	$city			=	$data['city'];
		$country		=	$data['country'];
		$street_one		=	$data['street1'];
		$street_two		=	$data['street2'];
		$mail			=	$data['email'];
		$phone			=	$data['phone'];
		$fax			=	$data['fax'];


	}

}//End of gen_load_ehp_page()


// Show user data
function gen_edituser_page(&$tpl)
{
	global $dmn_user_name;
	global $user_email, $customer_id, $first_name;
    global $last_name, $firm, $zip;
    global $city, $country, $street_one;
	global $street_two, $mail, $phone;
	global $fax;
	
	if ($customer_id == NULL){
		$customer_id = '';
	}

	// Fill in the fileds
	$tpl -> assign(
                array(
                       	'VL_USERNAME' => $dmn_user_name,
						'VL_USR_PASS' => '',
						'VL_USR_PASS_REP' => '',
						'VL_MAIL' => $user_email,
						'VL_USR_ID' => $customer_id,
						'VL_USR_NAME' => $first_name,
						'VL_LAST_USRNAME' => $last_name,
						'VL_USR_FIRM' => $firm,
						'VL_USR_POSTCODE' => $zip,
						'VL_USRCITY' => $city,
						'VL_COUNTRY' => $country,
						'VL_STREET1' => $street_one,
						'VL_STREET2' => $street_two,
						'VL_MAIL' => $mail,
						'VL_PHONE' => $phone,
						'VL_FAX' => $fax
					)
			);

	generate_ip_list($tpl, $_SESSION['user_id']);

}// End of gen_edituser_page()


// Function to update changes into db
function update_data_in_db($hpid)
{
  global $sql;
  global $dmn_user_name;
  global $user_email, $customer_id, $first_name;
  global $last_name, $firm, $zip;
  global $city, $country, $street_one;
  global $street_two, $mail, $phone;
  global $fax, $inpass, $domain_ip;
  global $admin_login;

  /*
  $first_name = escape_user_data($first_name);
  $last_name = escape_user_data($last_name);
  $firm = escape_user_data($firm);
  $zip = escape_user_data($zip);
  $city = escape_user_data($city);
  $country = escape_user_data($country);
  $phone = escape_user_data($phone);
  $fax = escape_user_data($fax);
  $street_one = escape_user_data($street_one);
  $street_two = escape_user_data($street_two);
  */

  if ($inpass === '') {// Save with out password
    $query = <<<SQL_QUERY
            update
                admin
            set
                fname=?,
                lname=?,
                firm=?,
                zip=?,
                city=?,
                country=?,
                email=?,
                phone=?,
                fax=?,
                street1=?,
                street2=?,
                customer_id=?
            where
                admin_id=?
SQL_QUERY;
    exec_query($sql, $query, array($first_name,
                                   $last_name,
                                   $firm,
                                   $zip,
                                   $city,
                                   $country,
                                   $mail,
                                   $phone,
                                   $fax,
                                   $street_one,
                                   $street_two,
                                   $customer_id,
                                   $hpid));
  } else {
    // Change password
    $inpass = crypt_user_pass($inpass);

    $query = <<<SQL_QUERY
            update
                admin
            set
                admin_pass=?,
                fname=?,
                lname=?,
                firm=?,
                zip=?,
                city=?,
                country=?,
                email=?,
                phone=?,
                fax=?,
                street1=?,
                street2=?,
                customer_id=?
            where
                admin_id=?
SQL_QUERY;
    exec_query($sql, $query, array($inpass,
                                   $first_name,
                                   $last_name,
                                   $firm,
                                   $zip,
                                   $city,
                                   $country,
                                   $mail,
                                   $phone,
                                   $fax,
                                   $street_one,
                                   $street_two,
                                   $customer_id,
                                   $hpid));
  }

	$admin_login = $_SESSION['user_logged'];
    write_log("$admin_login change data/password for $dmn_user_name!");

	unset($_SESSION['edit_ID']);
	unset($_SESSION['user_name']);

	$_SESSION['edit'] = "_yes_";
	Header("Location: users.php");
	die();
}// End of update_data_in_db()

?>
