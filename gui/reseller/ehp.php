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
$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'] . '/ehp.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];


/*
 *
 * static page messages.
 *
 */
global	$hpid;

// Show main menu

gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_hp.tpl');

gen_logged_from($tpl);

$tpl -> assign(array('TR_RESELLER_MAIN_INDEX_PAGE_TITLE' => tr('VHCS - Reseller/Edit hosting plan'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

$tpl -> assign(array('TR_HOSTING PLAN PROPS' => tr('Hosting plan properties'),
                     'TR_TEMPLATE_NAME' => tr('Template name'),
                     'TR_MAX_SUBDOMAINS' => tr('Max subdomains<br><i>(-1 disabled, 0 unlimited)'),
                     'TR_MAX_ALIASES' => tr('Max aliases<br><i>(-1 disabled, 0 unlimited)'),
                     'TR_MAX_MAILACCOUNTS' => tr('Mail account limit<br><i>(0 unlimited)</i>'),
                     'TR_MAX_FTP' => tr('FTP account limit<br><i>(0 unlimited)</i>'),
                     'TR_MAX_SQL' => tr('SQL databases Limit<br><i>(-1 disabled, 0 unlimited)</i>'),
                     'TR_MAX_SQL_USERS' => tr('SQL users limit<br><i>(-1 disabled, 0 unlimited)</i>'),
                     'TR_MAX_TRAFFIC' => tr('Traffic limit [MB]<br><i>(0 unlimited)</i>'),
                     'TR_DISK_LIMIT' => tr('Disk limit [MB]<br><i>(0 unlimited)</i>'),
                     'TR_PHP' => tr('PHP'),
                     'TR_CGI' => tr('CGI / Perl'),
                     'TR_BACKUP_RESTORE' => tr('Backup and restore'),
                     'TR_APACHE_LOGS' => tr('Apache logfiles'),
                     'TR_AWSTATS' => tr('AwStats'),
                     'TR_YES' => tr('yes'),
                     'TR_NO' => tr('no'),
					 'TR_BILLING_PROPS' => tr('Billing Settings'),
					 'TR_PRICE' => tr('Price'),
					 'TR_SETUP_FEE' => tr('Setup fee'),
					 'TR_VALUE' => tr('Currency'),
					 'TR_PAYMENT' => tr('Payment period'),
					 'TR_STATUS' => tr('Available for purchasing'),
					 'TR_TEMPLATE_DESCRIPTON' => tr('Description'),
					 'TR_EXAMPEL' => tr('(e.g. EUR)'),
					 'TR_EDIT_HOSTING_PLAN' => tr('Update plan'),
                     'TR_UPDATE_PLAN' => tr('Update plan')));

/*
* Dynamic page process
*
*/
if (isset($_POST['uaction']) && ('add_plan' === $_POST['uaction'])) {
  // Process data
  if (check_data_iscorrect($tpl)) { // Save data to db
    save_data_to_db();
  } else {
  	restore_form($tpl, $sql);
  }
} else {
  // Get hosting plan id tha come for edit
  if (isset($_GET['hpid'])) {
    $hpid = $_GET['hpid'];
  }

  gen_load_ehp_page($tpl, $sql, $hpid, $_SESSION['user_id']);
  $tpl -> assign('MESSAGE', "");
}

gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

//unset_messages();
//*******************************************************
//* Function definitions
//*

// Restore form on any error
function restore_form(&$tpl, &$sql) {
	$tpl -> assign(array(
						 'HP_NAME_VALUE' => clean_input($_POST['hp_name']),
						 'HP_DESCRIPTION_VALUE' => clean_input($_POST['hp_description']),
						 'TR_MAX_SUB_LIMITS' => clean_input($_POST['hp_sub']),
						 'TR_MAX_ALS_VALUES' => clean_input($_POST['hp_als']),
						 'HP_MAIL_VALUE' => clean_input($_POST['hp_mail']),
						 'HP_FTP_VALUE' => clean_input($_POST['hp_ftp']),
						 'HP_SQL_DB_VALUE' => clean_input($_POST['hp_sql_db']),
						 'HP_SQL_USER_VALUE' => clean_input($_POST['hp_sql_user']),
						 'HP_TRAFF_VALUE' => clean_input($_POST['hp_traff']),
						 'HP_TRAFF' => clean_input($_POST['hp_traff']),
						 'HP_DISK_VALUE' => clean_input($_POST['hp_disk']),
						 'HP_PRICE' => clean_input($_POST['hp_price']),
						 'HP_SETUPFEE' => clean_input($_POST['hp_setupfee']),
						 'HP_CURRENCY' => clean_input($_POST['hp_currency']),
						 'HP_PAYMENT' => clean_input($_POST['hp_payment'])
						 ));

	if ('_yes_' === $_POST['php']) {
		$tpl -> assign(array('TR_PHP_YES' => 'checked'));
	} else
		$tpl -> assign(array('TR_PHP_NO' => 'checked'));
	if ('_yes_' === $_POST['cgi']) {
		$tpl -> assign(array('TR_CGI_YES' => 'checked'));
	} else
		$tpl -> assign(array('TR_CGI_NO' => 'checked'));

	if (clean_input($_POST['status'] == 1)) {
    $tpl -> assign(array('TR_STATUS_YES' => 'checked'));
  } else
    $tpl -> assign(array('TR_STATUS_NO' => 'checked'));
}

// Generate load data from sql for requested hosting plan
function gen_load_ehp_page(&$tpl, &$sql, $hpid, $admin_id)
{

  global $cfg;
  $_SESSION['hpid'] = $hpid;

if (isset($cfg['HOSTING_PLANS_LEVEL']) && $cfg['HOSTING_PLANS_LEVEL'] === 'admin'){
  $query = <<<SQL_QUERY
        select
            *
        from
            hosting_plans
        where
            id = ?;
SQL_QUERY;
  $res = exec_query($sql, $query, array($hpid));
  $readonly = 'readonly';
  $disabled = 'disabled';
  $edit_hp = tr('View hosting plan');
  $tpl -> assign('FORM', "");

} else {
$query = <<<SQL_QUERY
        select
            *
        from
            hosting_plans
        where
            reseller_id = ? and id = ?;
SQL_QUERY;
  $res = exec_query($sql, $query, array($admin_id, $hpid));
  $readonly = '';
  $disabled = '';
  $edit_hp = tr('Edit hosting plan');
}

  if ($res->RowCount() !== 1) { //Error
    header('Location: hp.php');
    die();
  }

  $data = $res -> FetchRow();
  $props = $data['props'];
  $description = $data['description'];
  $price = $data['price'];
  $setup_fee = $data['setup_fee'];
  $value = $data['value'];
  $payment = $data['payment'];
  $status = $data['status'];
  list($hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db, $hp_sql_user, $hp_traff, $hp_disk) = explode(";", $props);
  $hp_name = $data['name'];

  if ($description == '')
  	$description = '';

  if ($payment == '')
  	$payment = '';

	if ($value == '')
  	$value = '';

  $tpl -> assign(array('HP_NAME_VALUE' => stripslashes($hp_name),
					   'TR_EDIT_HOSTING_PLAN' => $edit_hp,
                       'TR_MAX_SUB_LIMITS' => $hp_sub,
                       'TR_MAX_ALS_VALUES' => $hp_als,
                       'HP_MAIL_VALUE' => $hp_mail,
                       'HP_FTP_VALUE' => $hp_ftp,
                       'HP_SQL_DB_VALUE' => $hp_sql_db,
                       'HP_SQL_USER_VALUE' => $hp_sql_user,
                       'HP_TRAFF_VALUE' => $hp_traff,
                       'HP_DISK_VALUE' => $hp_disk,
					   'HP_DESCRIPTION_VALUE' => stripslashes($description),
					   'HP_PRICE' => $price,
					   'HP_SETUPFEE' => $setup_fee,
					   'HP_CURRENCY' => stripslashes($value),
					   'READONLY' => $readonly,
					   'DISBLED' => $disabled,
					   'HP_PAYMENT' => stripslashes($payment)));

  if ('_yes_' === $hp_php) {
    $tpl -> assign(array('TR_PHP_YES' => 'checked'));
  } else
    $tpl -> assign(array('TR_PHP_NO' => 'checked'));
  if ('_yes_' === $hp_cgi) {
    $tpl -> assign(array('TR_CGI_YES' => 'checked'));
  } else
    $tpl -> assign(array('TR_CGI_NO' => 'checked'));

  if ($status == 1) {
    $tpl -> assign(array('TR_STATUS_YES' => 'checked'));
  } else
    $tpl -> assign(array('TR_STATUS_NO' => 'checked'));
}// End of gen_load_ehp_page()




// Check correction of input data
function check_data_iscorrect(&$tpl)
{
  global $hp_name, $hp_php, $hp_cgi;
  global $hp_sub, $hp_als, $hp_mail;
  global $hp_ftp, $hp_sql_db, $hp_sql_user;
  global $hp_traff, $hp_disk;
  global $hpid;
  global $price, $setup_fee;

  $ahp_error	= "_off_";
  $hp_name		= clean_input($_POST['hp_name']);
  $hp_sub		= clean_input($_POST['hp_sub']);
  $hp_als		= clean_input($_POST['hp_als']);
  $hp_mail		= clean_input($_POST['hp_mail']);
  $hp_ftp		= clean_input($_POST['hp_ftp']);
  $hp_sql_db	= clean_input($_POST['hp_sql_db']);
  $hp_sql_user	= clean_input($_POST['hp_sql_user']);
  $hp_traff		= clean_input($_POST['hp_traff']);
  $hp_disk		= clean_input($_POST['hp_disk']);
  $price 		= clean_input($_POST['hp_price']);
  $setup_fee 	= clean_input($_POST['hp_setupfee']);

  if (isset($_SESSION['hpid']))
    $hpid = $_SESSION['hpid'];
  else
    $ahp_error = tr('Undefined reference to data!');

  // put hosting plan id into session value
  $_SESSION['hpid'] = $hpid;

  // Get values from previes page and check him correction
  if (isset($_POST['php']))
    $hp_php		= $_POST['php'];

  if (isset($_POST['cgi']))
    $hp_cgi		= $_POST['cgi'];;

  // if (!vhcs_name_check($hp_name, 200)) {
        // $ahp_error = tr('Incorrect template name range or syntax!');
    // } else

  if (!vhcs_limit_check($hp_sub, 999)) {
    $ahp_error = tr('Incorrect subdomain range or syntax!');
  } else if (!vhcs_limit_check($hp_als, 999)) {
    $ahp_error = tr('Incorrect alias range or syntax!');
  } else if (!vhcs_limit_check($hp_mail, 999) || $hp_mail == -1) {
    $ahp_error = tr('Incorrect mail account range or syntax!');
  } else if (!vhcs_limit_check($hp_ftp, 999) || $hp_ftp == -1) {
    $ahp_error = tr('Incorrect FTP account range or syntax!');
  } else if (!vhcs_limit_check($hp_sql_user, 999)) {
    $ahp_error = tr('Incorrect SQL database range or syntax!');
  } else if (!vhcs_limit_check($hp_sql_db, 999)) {
    $ahp_error = tr('Incorrect SQL user range or syntax!');
  } else if (!vhcs_limit_check($hp_traff, 1024*1024) || $hp_traff == -1) {
    $ahp_error = tr('Incorrect traffic range or syntax!');
  } else if (!vhcs_limit_check($hp_disk, 1024*1024) || $hp_disk == -1) {
    $ahp_error = tr('Incorrect disk range or syntax!');
  } else if (!is_numeric($price)) {
	  $ahp_error = tr('Incorrect price!');
  } else if (!is_numeric($setup_fee)) {
	  $ahp_error = tr('Incorrect setup fee!');
  }


  if ($ahp_error == '_off_') {
    $tpl -> assign('MESSAGE', '');
    return true;
  } else {
    set_page_message($ahp_error);
    return false;
  }

  return TRUE;

} // End of check_data_iscorrect()



// Add new host plan to DB
function save_data_to_db()
{
  global $sql, $tpl;
  global $hp_name, $hp_php, $hp_cgi;
  global $hp_sub, $hp_als, $hp_mail;
  global $hp_ftp, $hp_sql_db, $hp_sql_user;
  global $hp_traff, $hp_disk;
  global $hpid;

	$description 	= clean_input($_POST['hp_description']);
	$price 			= clean_input($_POST['hp_price']);
	$setup_fee 		= clean_input($_POST['hp_setupfee']);
	$currency 		= clean_input($_POST['hp_currency']);
	$payment		= clean_input($_POST['hp_payment']);
	$status 		= clean_input($_POST['status']);

	$hp_props = "$hp_php;$hp_cgi;$hp_sub;$hp_als;$hp_mail;$hp_ftp;$hp_sql_db;$hp_sql_user;$hp_traff;$hp_disk;";

  	$err_msg = '_off_';

  	$admin_id = $_SESSION['user_id'];

  	reseller_limits_check($sql, $err_msg, $admin_id, $hpid, $hp_props);

  	if ($err_msg != '_off_') {

  		set_page_message($err_msg);
  		restore_form($tpl, $sql);

  	} else {

	$query = <<<SQL_QUERY
        update
            hosting_plans
        set
            name = ?,
			description = ?,
			props = ?,
			price = ?,
			setup_fee = ?,
			value = ?,
			payment = ?,
			status = ?
        where
            id = ?
SQL_QUERY;
  $res = exec_query($sql, $query, array(htmlspecialchars($hp_name, ENT_QUOTES, "UTF-8"),
  										clean_input($description),
										$hp_props, $price, $setup_fee,
										htmlspecialchars($currency, ENT_QUOTES, "UTF-8"),
										htmlspecialchars($payment, ENT_QUOTES, "UTF-8"), $status, $hpid));

   		$_SESSION['hp_updated'] = '_yes_';
    	Header("Location: hp.php");
    	die();

  	}

} //End of save_data_to_db()


die();

?>