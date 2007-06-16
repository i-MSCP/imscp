<?php
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/

include '../include/ispcp-lib.php';

check_login();

$tpl = new pTemplate();
$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/rau3.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('ip_entry', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADD_USER_PAGE_TITLE' => tr('ISPCP - User/Add user'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
                        'ISP_LOGO' => get_logo($_SESSION['user_id']),
                     )
              );

/*
 *
 * static page messages.
 *
 */

gen_reseller_mainmenu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/main_menu_manage_users.tpl');
gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_manage_users.tpl');

gen_logged_from($tpl);

$tpl -> assign(
                array(
                        'TR_ADD_USER' => tr('Add user'),
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
                        'TR_BTN_ADD_USER' => tr('Add user'),
                        'TR_ADD_ALIASES' => tr('Add other domains to this account'),
                        'VL_USR_PASS' => passgen()
                     )
              );

init_in_values();

// Process the action ...
if (isset($_POST['uaction']) && ("rau3_nxt" === $_POST['uaction']) && !isset($_SESSION['step_two_data']) ) {
  if(check_ruser_data($tpl, '_no_')) {
    add_user_data($_SESSION['user_id']);
  }
  set_page_message($_SESSION['Message']);
  unset($_SESSION['Message']);
} else {
	unset($_SESSION['step_two_data']);
	gen_empty_data();
	$tpl -> assign('MESSAGE', "");
}


gen_rau3_page($tpl);
gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

//unset_messages();
//
// FUNCTION declaration
//


// Get data from previus page
function init_in_values()
{
	global $dmn_name, $dmn_user_name, $hpid;

  if (isset($_SESSION['step_one']) ) {
    $step_two	= $_SESSION['dmn_name'].";".$_SESSION['dmn_tpl'];
    unset($_SESSION['dmn_name']);
    unset($_SESSION['dmn_tpl']);
    unset($_SESSION['chtpl']);
    unset($_SESSION['step_one']);
  } else if (isset($_SESSION['step_two_data']) ) {
    $step_two	= $_SESSION['step_two_data'];
    unset($_SESSION['step_two_data']);
  } else if (isset($_SESSION['local_data']) ) {
    $step_two	= $_SESSION['local_data'];
    unset($_SESSION['local_data']);
  } else
    $step_two	= "'';0";

  list($dmn_name, $hpid) = explode(";", $step_two);
  //$dmn_user_name = preg_replace("/\./", "_", $dmn_name);
  $dmn_user_name = $dmn_name;
} // End of init_in_values()

// generate page add user 3
function gen_rau3_page(&$tpl)
{
  global $dmn_name, $hpid , $dmn_user_name;
  global $user_email, $customer_id, $first_name;
  global $last_name, $firm, $zip;
  global $city, $country, $street_one;
  global $street_two, $mail, $phone;
  global $fax;

  $dmn_user_name = decode_idna($dmn_user_name);

  // Fill in the fileds
  $tpl -> assign(
                array(
                      'VL_USERNAME' => $dmn_user_name,
                      'VL_USR_PASS' => passgen(),
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
  $_SESSION['local_data'] = "$dmn_name;$hpid";

}// End of gen_rau3_page()


// Init global value with empty values
function gen_empty_data()
{
  global $user_email, $customer_id, $first_name;
  global $last_name, $firm, $zip;
  global $city, $country, $street_one;
  global $street_two, $mail, $phone, $fax;

    $user_email = '';
    $customer_id = '';
    $first_name = '';
    $last_name = '';
    $firm = '';
    $zip = '';
    $city = '';
    $country = '';
    $street_one = '';
    $street_two = '';
    $phone = '';
    $mail = '';
    $fax = '';
    $domain_ip = '';
} // End of gen_empty_data()


// Save data for new user in db
function add_user_data ($reseller_id)
{
  global $sql, $cfg;
  global $dmn_name, $hpid , $dmn_user_name;
  global $user_email, $customer_id, $first_name;
  global $last_name, $firm, $zip;
  global $city, $country, $street_one;
  global $street_two, $mail, $phone;
  global $fax, $inpass, $domain_ip;
  global $admin_login;


  // Let's get Desired Hosting Plan Data;
  //

  $err_msg = '_off_';

  reseller_limits_check($sql, $err_msg, $reseller_id, $hpid);

  if ($err_msg != '_off_') {
       set_page_message($err_msg);
        return;
  }

  if(isset($_SESSION["ch_hpprops"])) {
    $props = $_SESSION["ch_hpprops"];
    unset($_SESSION["ch_hpprops"]);
  } else {
    $query = "select props from hosting_plans where reseller_id = ? and id = ?";
    $res = exec_query($sql, $query, array($reseller_id, $hpid));
    $data = $res -> FetchRow();
    $props = $data['props'];
  }

  list($php, $cgi, $sub,
       $als, $mail, $ftp,
       $sql_db, $sql_user,
       $traff, $disk) = explode(";", $props);

  $php = preg_replace("/\_/", "", $php);
  $cgi = preg_replace("/\_/", "", $cgi);
  $pure_user_pass = $inpass;
  $inpass = crypt_user_pass($inpass);
    // $first_name = escape_user_data($first_name);
    // $last_name = escape_user_data($last_name);
    // $firm = escape_user_data($firm);
    // $zip = escape_user_data($zip);
    // $city = escape_user_data($city);
    // $country = escape_user_data($country);
    // $phone = escape_user_data($phone);
    // $fax = escape_user_data($fax);
    // $street_one = escape_user_data($street_one);
    // $street_two = escape_user_data($street_two);
    // $customer_id = escape_user_data($customer_id);

  if (!ispcp_domain_check($dmn_user_name)) {
//    set_page_message(tr("Wrong domain name syntax!"));
    return;
  }

  check_for_lock_file();

   //check again if a user like that exits
  $query = <<<OMEGA_SQL_QUERY
	select count(*) as count
            	from admin
		where admin_name = ?
		limit 1
OMEGA_SQL_QUERY;

  $res = exec_query($sql, $query, $dmn_user_name);
  $data = $res -> FetchRow();

  if ($data['count'] > 0 ) {
	set_page_message(tr("There's a conflicting admin / reseller fix that first!"));
	return;
  }



  $query = <<<ISPCP_SQL_QUERY
            insert into admin
                      (
                        admin_name, admin_pass, admin_type, domain_created,
                        created_by, fname, lname,
                        firm, zip, city,
                        country, email, phone,
                        fax, street1, street2, customer_id
                      )
                values
                      (
                        ?, ?, 'user', unix_timestamp(),
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                      )
ISPCP_SQL_QUERY;

    $res = exec_query($sql, $query, array($dmn_user_name, $inpass, $reseller_id, $first_name, $last_name,
                      $firm, $zip, $city, $country, $user_email, $phone, $fax, $street_one, $street_two, $customer_id));

    print $sql -> ErrorMsg();

    $record_id = $sql -> Insert_ID();

    $query = <<<ISPCP_SQL_QUERY
            insert into domain (
                        domain_name, domain_admin_id,
                        domain_created_id, domain_created,
                        domain_mailacc_limit, domain_ftpacc_limit,
                        domain_traffic_limit, domain_sqld_limit,
                        domain_sqlu_limit, domain_status,
                        domain_subd_limit, domain_alias_limit,
                        domain_ip_id, domain_disk_limit,
                        domain_disk_usage, domain_php, domain_cgi
                       )
                values (
                        ?, ?,
                        ?, unix_timestamp(),
                        ?, ?,
                        ?, ?,
                        ?, 'toadd',
                        ?, ?,
                        ?, ?, '0',
                        ?, ?
                       )
ISPCP_SQL_QUERY;

    $res = exec_query($sql, $query, array($dmn_name,
                                          $record_id,
                                          $reseller_id,
                                          $mail,
                                          $ftp,
                                          $traff,
                                          $sql_db,
                                          $sql_user,
                                          $sub,
                                          $als,
                                          $domain_ip,
                                          $disk,
                                          $php,
                                          $cgi));
    $dmn_id = $sql -> Insert_ID();

	// ispcp 2.5 feature
	//add_domain_extras($dmn_id, $record_id, $sql);


	// lets send mail to user
	send_add_user_auto_msg (
                                $reseller_id,
                                $dmn_user_name,
                                $pure_user_pass,
                                $user_email,
                                $first_name,
                                $last_name,
                                 tr('Domain account')
                               );

    // send query to the ispcp daemon


  // add user into user_gui_props => domain looser needs language and skin too :-)

  $user_def_lang = $_SESSION['user_def_lang'];
  $user_theme_color = $_SESSION['user_theme'];

  $query = <<<SQL_QUERY
                insert into
                  user_gui_props
                      (user_id, lang, layout)
                  values
                      (?, ?, ?)
SQL_QUERY;

  $res = exec_query($sql, $query, array($record_id,
                                        $user_def_lang,
                                        $user_theme_color));

  send_request();

  $admin_login = $_SESSION['user_logged'];
  write_log("$admin_login: add user: $dmn_user_name (for domain $dmn_name)");
  write_log("$admin_login: add domain: $dmn_name");

  au_update_reseller_props($reseller_id, $props);

  if (isset($_POST['add_alias']) && $_POST['add_alias'] === 'on'){
    //we have to add some aliases for this looser
    $_SESSION['dmn_id'] = $dmn_id;
    $_SESSION['dmn_ip'] = $domain_ip;
    header("Location: rau4.php?accout=$dmn_id");
    die();
  } else {
    //we have not to add alias
    $_SESSION['rau3_added'] = "_yes_";
    header("Location: users.php");
    die();
  }
} // End of add_user_data()

?>