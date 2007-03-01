<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware		            		|
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
$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/rau3.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('ip_entry', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADD_USER_PAGE_TITLE' => tr('VHCS - User/Add user'),
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


gen_au3_page($tpl);
gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

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
function gen_au3_page(&$tpl)
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

}// End of gen_au3_page()


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



// Check validity of input data
/*function check_user_data (&$tpl) {

	global $dmn_name, $hpid , $dmn_user_name;
	global $user_email, $customer_id, $first_name;
    global $last_name, $firm, $zip;
    global $city, $country, $street_one;
	global $street_two, $mail, $phone;
	global $fax, $inpass, $domain_ip;

    $rau_error = '_off_';
	$inpass_re = '';

	// Get data for fields from previus page
	if(isset($_POST['userpassword']))
		$inpass	 = $_POST['userpassword'];

	if(isset($_POST['userpassword_repeat']))
		$inpass_re	 = $_POST['userpassword_repeat'];

	if(isset($_POST['domain_ip']))
		$domain_ip	 = $_POST['domain_ip'];

	if(isset($_POST['useremail']))
		$user_email	 = $_POST['useremail'];

	if(isset($_POST['useruid']))
		$customer_id = $_POST['useruid'];

	if(isset($_POST['userfname']))
		$first_name  = $_POST['userfname'];

	if(isset($_POST['userlname']))
		$last_name	 = $_POST['userlname'];

	if(isset($_POST['userfirm']))
		$firm	 	 = $_POST['userfirm'];

	if(isset($_POST['userzip']))
		$zip = $_POST['userzip'];

	if(isset($_POST['usercity']))
		$city = $_POST['usercity'];

	if(isset($_POST['usercountry']))
		$country = $_POST['usercountry'];

	if(isset($_POST['userstreet1']))
		$street_one = $_POST['userstreet1'];

	if(isset($_POST['userstreet2']))
		$street_two	 = $_POST['userstreet2'];

	if(isset($_POST['useremail']))
		$mail	 = $_POST['useremail'];

	if(isset($_POST['userphone']))
		$phone = $_POST['userphone'];

	if(isset($_POST['userfax']))
		$fax = $_POST['userfax'];

	//if(isset($_SESSION['local_data']) )
	//	list($dmn_name, $hpid, $dmn_user_name) = explode(";", $_SESSION['local_data']);

	// Begin checking...
	if (('' === $inpass_re) || ('' === $inpass)){

		$rau_error = tr('Please fill up both data fields for password!');

	}else if ($inpass_re !== $inpass ){

		$rau_error = tr('Passwords does not match!');

	}else if (!vhcs_password_check($inpass, 20)) {

        $rau_error = tr('Incorrect password range or ayntax!');

    }else if (!vhcs_name_check($user_email, 40)){

		$rau_error = tr('Incorrect mail account range or syntax!');

	}else if(!vhcs_limit_check($customer_id, 999)){

		$rau_error = tr('Incorrect customer ID syntax!');
	}else if(!vhcs_name_check($first_name, 40)){

		$rau_error = tr('Incorrect first name range or syntax!');
	}else if(!vhcs_name_check($last_name, 40)){

		$rau_error = tr('Incorrect second name range or syntax!');
	}else if(!vhcs_name_check($firm, 100)){

		$rau_error = tr('Incorrect company range or syntax!');
	}else if(!vhcs_limit_check($zip, 999999)){

		$rau_error = tr('Incorrect post code range or syntax!');
	}else if(!vhcs_name_check($city, 40)){

		$rau_error = tr('Incorrect city syntax!');
	}else if(!vhcs_name_check($country, 100)){

		$rau_error = tr('Incorrect country syntax!');
	}else if(!vhcs_name_check($street_one, 100)){

		$rau_error = tr('Incorrect street 1 syntax!');
	}else if(!vhcs_name_check($street_two, 100)){

		$rau_error = tr('Incorrect street 2 syntax!');
	}else if(!vhcs_name_check($mail, 100)){

		$rau_error = tr('Incorrect mail account range or syntax!');
	}else if(!vhcs_name_check($phone, 100)){

		$rau_error = tr('Incorrect phone range or syntax!');
	}else if(!vhcs_name_check($fax, 100)){

		$rau_error = tr('Incorrect fax range or syntax!');
	}


    if ($rau_error == '_off_') {

        $tpl -> assign('MESSAGE', '');

		// send data throught session
		return true;

    } else {

        $tpl -> assign('MESSAGE', $rau_error);

        return false;
    }

	return true;
}*///End of check_user_data()



//Generate ip list
/*function generate_ip_list(&$tpl, $reseller_id)
{

    global $sql;
    global $domain_ip;

    $query = <<<SQL_QUERY

        select

            reseller_ips

        from

            reseller_props

        where

            reseller_id = '$reseller_id'

SQL_QUERY;

    $res = $sql -> Execute($query);

    $data = $res -> FetchRow();

    $reseller_ips = $data['reseller_ips'];

    $query = <<<SQL_QUERY

        select * from server_ips

SQL_QUERY;

    $res = $sql -> Execute($query);

    while ($data = $res -> FetchRow()) {

        $ip_id = $data['ip_id'];

        if (preg_match("/$ip_id;/", $reseller_ips) == 1) {

            $selected = '';

            if ($domain_ip === $ip_id) {
            	$selected = 'selected';
            }

            $tpl -> assign(
                            array(
                                    'IP_NUM' => $data['ip_number'],
                                    'IP_NAME' => $data['ip_domain'],
                                    'IP_VALUE' => $ip_id,
                                    'IP_SELECTED' => "$selected"
                                 )
                          );

            $tpl -> parse('IP_ENTRY', '.ip_entry');
        }
    }// End loop

}*/// End of generate_ip_list()

// Save data for new user in db



/*
function reseller_limits_check(&$err_msg, $reseller_id)
{
  global $sql, $hpid;

  if (isset($_SESSION["ch_hpprops"])) {
    $props = $_SESSION["ch_hpprops"];
  } else {
    $query = <<<SQL_QUERY
        select
            props
        from
            hosting_plans
        where
            reseller_id = ?
          and
            id = ?
SQL_QUERY;

    $res = exec_query($sql, $query, array($reseller_id, $hpid));
    $data = $res -> FetchRow();
    $props = $data['props'];
  }

  list($php_new, $cgi_new, $sub_new,
       $als_new, $mail_new, $ftp_new,
       $sql_db_new, $sql_user_new,
       $traff_new, $disk_new) = explode(";", $props);

    $query = <<<SQL_QUERY
        select
            *
        from
            reseller_props
        where
            reseller_id = ?
SQL_QUERY;

    $res = exec_query($sql, $query, array($reseller_id));
    $data = $res -> FetchRow();
    $dmn_current = $data['current_dmn_cnt'];
    $dmn_max = $data['max_dmn_cnt'];

    $sub_current = $data['current_sub_cnt'];
    $sub_max = $data['max_sub_cnt'];

    $als_current = $data['current_als_cnt'];
    $als_max = $data['max_als_cnt'];

    $mail_current = $data['current_mail_cnt'];
    $mail_max = $data['max_mail_cnt'];

    $ftp_current = $data['current_ftp_cnt'];
    $ftp_max = $data['max_ftp_cnt'];

    $sql_db_current = $data['current_sql_db_cnt'];
    $sql_db_max = $data['max_sql_db_cnt'];

    $sql_user_current = $data['current_sql_user_cnt'];
    $sql_user_max = $data['max_sql_user_cnt'];

    $traff_current = $data['current_traff_amnt'];
    $traff_max = $data['max_traff_amnt'];

    $disk_current = $data['current_disk_amnt'];
    $disk_max = $data['max_disk_amnt'];

    if ($dmn_max != 0) {
        if ($dmn_current + 1 > $dmn_max) {
            $err_msg = tr('You have been reached your domain limit.<br>You can not add more domains ! ');
            return;
        }
    }

    if ($sub_max != 0) {
        if ($sub_new != -1) {
            if ($sub_new == 0) {
                $err_msg = tr('You have subdomain limit!<br>You can not add user with unlimited subdomain number!');
                return;
            } else if ($sub_current + $sub_new > $sub_max) {
                $err_msg = tr('You are exceeding your subdomain limit!');
                return;
            }
        }
    }


#    if ($als_max != 0) {
#        if ($als_new != -1) {
#            if ($als_new == 0) {
#                $err_msg = tr('You have alias limit!<br>You can Not Add User With Unlimited Alias Number!');
#                return;
#            } else if ($als_current + $als_new > $als_max) {
#                $err_msg = tr('You Are Exceeding Your Alias Limit!');
#                return;
#            }
#        }
#    }

    if ($mail_max != 0) {
        if ($mail_new == 0) {
            $err_msg = tr('You have mail account limit!<br>You can not add user with unlimited mail accunt number!');
            return;
        } else if ($mail_current + $mail_new > $mail_max) {
            $err_msg = tr('You are exceeding your mail account limit!');
            return;
        }
    }

    if ($ftp_max != 0) {
        if ($ftp_new == 0) {
            $err_msg = tr('You have FTP account limit!<br>You can not Add User With Unlimited FTP Accunt Number!');
            return;
        } else if ($ftp_current + $ftp_new > $ftp_max) {
            $err_msg = tr('You are exceeding your FTP account limit!');
            return;
        }
    }

    if ($sql_db_max != 0) {
        if ($sql_db_new != -1) {
            if ($sql_db_new == 0) {
                $err_msg = tr('You have SQL database limit!<br>You can not add user with unlimited SQL database number!');
                return;
            } else if ($sql_db_current + $sql_db_new > $sql_db_max) {
                $err_msg = tr('You are exceeding SQL database limit!');
                return;
            }
        }
    }

    if ($sql_user_max != 0) {
        if ($sql_user_new != -1) {
            if ($sql_user_new == 0) {
                $err_msg = tr('You have SQL user limit!<br>You can not add user with unlimited SQL users!');
                return;
            } else if ($sql_db_new == -1) {
                $err_msg = tr('You have disabled SQL databases for this user!<br>You can not have SQL users here!');
                return;
            } else if ($sql_user_current + $sql_user_new > $sql_user_max) {
                $err_msg = tr('You are exceeding SQL database limit!');
                return;
            }
        }
    }

    if ($traff_max != 0) {
        if ($traff_new == 0) {
            $err_msg = tr('You have traffic limit!<br>You can not add user with unlimited traffic number!');
            return;
        } else if ($traff_current + $traff_new > $traff_max) {
            $err_msg = tr('You are exceeding your traffic limit!');
            return;
        }
    }

    if ($disk_max != 0) {
        if ($disk_new == 0) {
            $err_msg = tr('You have disk limit!<br>You can not add user with unlimited disk number!');
            return;
        } else if ($disk_current + $disk_new > $disk_max) {
            $err_msg = tr('You are exceeding your disk limit!');
            return;
        }
    }

}

*/


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

  if (!vhcs_domain_check($dmn_user_name)) {
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



  $query = <<<VHCS_SQL_QUERY
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
VHCS_SQL_QUERY;

    $res = exec_query($sql, $query, array($dmn_user_name, $inpass, $reseller_id, $first_name, $last_name,
                      $firm, $zip, $city, $country, $user_email, $phone, $fax, $street_one, $street_two, $customer_id));

    print $sql -> ErrorMsg();

    $record_id = $sql -> Insert_ID();

    $query = <<<VHCS_SQL_QUERY
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
VHCS_SQL_QUERY;

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

	// vhcs 2.5 feature
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

    // send query to the vhcs2 daemon


  // add user into user_gui_props => domain looser needs language and skin too :-)

  $user_def_lang = $_SESSION['user_def_lang'];
  $user_theme_color = $_SESSION['user_theme_color'];

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

