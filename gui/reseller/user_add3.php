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

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('RESELLER_TEMPLATE_PATH') . '/user_add3.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('ip_entry', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_ADD_USER_PAGE_TITLE'	=> tr('ispCP - User/Add user'),
		'THEME_COLOR_PATH'			=> "../themes/$theme_color",
		'THEME_CHARSET'				=> tr('encoding'),
		'ISP_LOGO'					=> get_logo($_SESSION['user_id']),
	)
);

/*
 *
 * static page messages.
 *
 */

gen_reseller_mainmenu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/main_menu_users_manage.tpl');
gen_reseller_menu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/menu_users_manage.tpl');

gen_logged_from($tpl);

$tpl->assign(
	array(
		'TR_ADD_USER'			=> tr('Add user'),
		'TR_CORE_DATA'			=> tr('Core data'),
		'TR_USERNAME'			=> tr('Username'),
		'TR_PASSWORD'			=> tr('Password'),
		'TR_REP_PASSWORD'		=> tr('Repeat password'),
		'TR_DMN_IP'				=> tr('Domain IP'),
		'TR_USREMAIL'			=> tr('Email'),
		'TR_ADDITIONAL_DATA'	=> tr('Additional data'),
		'TR_CUSTOMER_ID'		=> tr('Customer ID'),
		'TR_FIRSTNAME'			=> tr('First name'),
		'TR_LASTNAME'			=> tr('Last name'),
		'TR_GENDER'				=> tr('Gender'),
		'TR_MALE'				=> tr('Male'),
		'TR_FEMALE'				=> tr('Female'),
		'TR_UNKNOWN'			=> tr('Unknown'),
		'TR_COMPANY'			=> tr('Company'),
		'TR_POST_CODE'			=> tr('Zip/Postal code'),
		'TR_CITY'				=> tr('City'),
		'TR_STATE_PROVINCE'		=> tr('State/Province'),
		'TR_COUNTRY'			=> tr('Country'),
		'TR_STREET1'			=> tr('Street 1'),
		'TR_STREET2'			=> tr('Street 2'),
		'TR_MAIL'				=> tr('Email'),
		'TR_PHONE'				=> tr('Phone'),
		'TR_FAX'				=> tr('Fax'),
		'TR_BTN_ADD_USER'		=> tr('Add user'),
		'TR_ADD_ALIASES'		=> tr('Add other domains to this account'),
		'VL_USR_PASS'			=> passgen()
	)
);

if (!init_in_values()) {
	set_page_message(tr("Domain data has been altered. Please enter again"));
	unset_messages();
	header("Location: user_add1.php");
	die();
}

// Process the action ...
if (isset($_POST['uaction']) && ("user_add3_nxt" === $_POST['uaction']) && !isset($_SESSION['step_two_data'])) {
	if (check_ruser_data($tpl, '_no_')) {
		add_user_data($_SESSION['user_id']);
	}
	set_page_message($_SESSION['Message']);
	unset($_SESSION['Message']);
} else {
	unset($_SESSION['step_two_data']);
	gen_empty_data();
	$tpl->assign('MESSAGE', "");
}

gen_user_add3_page($tpl);
gen_page_message($tpl);
$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();
// unset_messages();

// FUNCTION declaration

// Get data from previus page
function init_in_values() {
	global $dmn_name, $dmn_user_name, $hpid;

	if (isset($_SESSION['step_one'])) {
		$step_two = $_SESSION['dmn_name'] . ";" . $_SESSION['dmn_tpl'];
		$hpid = $_SESSION['dmn_tpl'];
		unset($_SESSION['dmn_name']);
		unset($_SESSION['dmn_tpl']);
		unset($_SESSION['chtpl']);
		unset($_SESSION['step_one']);
	} else if (isset($_SESSION['step_two_data'])) {
		$step_two = $_SESSION['step_two_data'];
		unset($_SESSION['step_two_data']);
	} else if (isset($_SESSION['local_data'])) {
		$step_two = $_SESSION['local_data'];
		unset($_SESSION['local_data']);
	} else {
		$step_two = "'';0";
	}

	list($dmn_name, $hpid) = explode(";", $step_two);
	// $dmn_user_name = preg_replace("/\./", "_", $dmn_name);
	$dmn_user_name = $dmn_name;
	if(!chk_dname($dmn_name) || ($hpid==''))return false;
	return true;
} // End of init_in_values()

// generate page add user 3
function gen_user_add3_page(&$tpl) {
	global $dmn_name, $hpid, $dmn_user_name;
	global $user_email, $customer_id, $first_name;
	global $last_name, $gender, $firm, $zip;
	global $city, $state, $country, $street_one;
	global $street_two, $mail, $phone;
	global $fax;

	$dmn_user_name = decode_idna($dmn_user_name);
	// Fill in the fields
	$tpl->assign(
		array(
			'VL_USERNAME'		=> $dmn_user_name,
			'VL_USR_PASS'		=> passgen(),
			'VL_MAIL'			=> $user_email,
			'VL_USR_ID'			=> $customer_id,
			'VL_USR_NAME'		=> $first_name,
			'VL_LAST_USRNAME'	=> $last_name,
			'VL_USR_FIRM'		=> $firm,
			'VL_USR_POSTCODE'	=> $zip,
			'VL_USRCITY'		=> $city,
			'VL_USRSTATE'		=> $state,
			'VL_MALE'			=> (($gender == 'M') ? 'selected="selected"' : ''),
			'VL_FEMALE'			=> (($gender == 'F') ? 'selected="selected"' : ''),
			'VL_UNKNOWN'		=> ((($gender == 'U') || (empty($gender))) ? 'selected="selected"' : ''),
			'VL_COUNTRY'		=> $country,
			'VL_STREET1'		=> $street_one,
			'VL_STREET2'		=> $street_two,
			'VL_MAIL'			=> $mail,
			'VL_PHONE'			=> $phone,
			'VL_FAX'			=> $fax
		)
	);

	generate_ip_list($tpl, $_SESSION['user_id']);
	$_SESSION['local_data'] = "$dmn_name;$hpid";
} // End of gen_user_add3_page()

// Init global value with empty values
function gen_empty_data() {
	global $user_email, $customer_id, $first_name;
	global $last_name, $gender, $firm, $zip;
	global $city, $state, $country, $street_one;
	global $street_two, $mail, $phone, $fax;

	$user_email		= '';
	$customer_id	= '';
	$first_name		= '';
	$last_name		= '';
	$gender			= 'U';
	$firm			= '';
	$zip			= '';
	$city			= '';
	$state			= '';
	$country		= '';
	$street_one		= '';
	$street_two		= '';
	$phone			= '';
	$mail			= '';
	$fax			= '';
	$domain_ip		= '';
} // End of gen_empty_data()

// Save data for new user in db
function add_user_data($reseller_id) {
	$sql = Database::getInstance();
	global $hpid;
	global $dmn_name, $dmn_user_name, $admin_login;
	global $user_email, $customer_id, $first_name;
	global $last_name, $gender, $firm, $zip;
	global $city, $state, $country, $street_one;
	global $street_two, $mail, $phone;
	global $fax, $inpass, $domain_ip;
	// Let's get Desired Hosting Plan Data;
	$err_msg = '';

	if (!empty($err_msg)) {
		set_page_message($err_msg);
		return false;
	}

	if (isset($_SESSION["ch_hpprops"])) {
		$props = $_SESSION["ch_hpprops"];
		unset($_SESSION["ch_hpprops"]);
	} else {
		if (Config::exists('HOSTING_PLANS_LEVEL') && strtolower(Config::get('HOSTING_PLANS_LEVEL')) == 'admin') {
			$query = 'SELECT `props` FROM `hosting_plans` WHERE `id` = ?';
			$res = exec_query($sql, $query, array($hpid));
		} else {
			$query = "SELECT `props` FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?";
			$res = exec_query($sql, $query, array($reseller_id, $hpid));
		}
		$data = $res->FetchRow();
		$props = $data['props'];
	}

	list($php, $cgi, $sub,
		$als, $mail, $ftp,
		$sql_db, $sql_user,
		$traff, $disk) = explode(";", $props);

	$php			= preg_replace("/\_/", "", $php);
	$cgi			= preg_replace("/\_/", "", $cgi);
	$pure_user_pass	= $inpass;
	$inpass			= crypt_user_pass($inpass, true);
	$first_name		= clean_input($first_name, true);
	$last_name		= clean_input($last_name, true);
	$firm			= clean_input($firm, true);
	$zip			= clean_input($zip, true);
	$city			= clean_input($city, true);
	$state			= clean_input($state, true);
	$country		= clean_input($country, true);
	$phone			= clean_input($phone, true);
	$fax			= clean_input($fax, true);
	$street_one		= clean_input($street_one, true);
	$street_two		= clean_input($street_two, true);
	$customer_id	= clean_input($customer_id, true);
	if (!chk_dname($dmn_user_name)) {
		// set_page_message(tr("Wrong domain name syntax!"));
		return;
	}

	check_for_lock_file();

	$query = "
		INSERT INTO `admin` (
			`admin_name`, `admin_pass`, `admin_type`, `domain_created`,
			`created_by`, `fname`, `lname`,
			`firm`, `zip`, `city`, `state`,
			`country`, `email`, `phone`,
			`fax`, `street1`, `street2`,
			`customer_id`, `gender`
		)
		VALUES (
			?, ?, 'user', unix_timestamp(),
			?, ?, ?,
			?, ?, ?, ?,
			?, ?, ?,
			?, ?, ?,
			?, ?
		)
	";

	$res = exec_query($sql, $query, array(
										$dmn_user_name, $inpass,
										$reseller_id, $first_name, $last_name,
										$firm, $zip, $city, $state,
										$country, $user_email, $phone,
										$fax, $street_one, $street_two,
										$customer_id, $gender
									));

	print $sql->ErrorMsg();

	$record_id = $sql->Insert_ID();

	$status = Config::get('ITEM_ADD_STATUS');

	$query = "
		INSERT INTO `domain` (
			`domain_name`, `domain_admin_id`,
			`domain_created_id`, `domain_created`,
			`domain_mailacc_limit`, `domain_ftpacc_limit`,
			`domain_traffic_limit`, `domain_sqld_limit`,
			`domain_sqlu_limit`, `domain_status`,
			`domain_subd_limit`, `domain_alias_limit`,
			`domain_ip_id`, `domain_disk_limit`,
			`domain_disk_usage`, `domain_php`, `domain_cgi`
		)
		VALUES (
			?, ?,
			?, unix_timestamp(),
			?, ?,
			?, ?,
			?, ?,
			?, ?,
			?, ?, '0',
			?, ?
		)
	";

	$res = exec_query($sql, $query, array(
		$dmn_name, $record_id, $reseller_id, $mail, $ftp, $traff, $sql_db,
		$sql_user, $status, $sub, $als, $domain_ip, $disk, $php, $cgi));
	$dmn_id = $sql->Insert_ID();

	//Add statistics group

	$query = "
		INSERT INTO `htaccess_users`
			(dmn_id, uname, upass, status)
		VALUES
			(?, ?, ?, ?)
	";
	$rs = exec_query($sql, $query, array($dmn_id, $dmn_name, crypt_user_pass_with_salt($pure_user_pass), $status));

	$user_id = $sql->Insert_ID();

	$awstats_auth = Config::get('AWSTATS_GROUP_AUTH');

	$query = "
		INSERT INTO `htaccess_groups`
			(dmn_id, ugroup, members, status)
		VALUES
			(?, ?, ?, ?)
	";
	$rs = exec_query($sql, $query, array($dmn_id, $awstats_auth, $user_id, $status));

	// Create the 3 default addresses if wanted
	if (Config::get('CREATE_DEFAULT_EMAIL_ADDRESSES'))
		client_mail_add_default_accounts($dmn_id, $user_email, $dmn_name); // 'domain', 0

	// add_domain_extras($dmn_id, $record_id, $sql);
	// lets send mail to user
	send_add_user_auto_msg ($reseller_id,
		$dmn_user_name,
		$pure_user_pass,
		$user_email,
		$first_name,
		$last_name,
		tr('Domain account')
		);
	// add user into user_gui_props => domain looser needs language and skin too :-)
	$user_def_lang = $_SESSION['user_def_lang'];
	$user_theme_color = $_SESSION['user_theme'];

	$query = "
		INSERT INTO `user_gui_props`
			(`user_id`, `lang`, `layout`)
		VALUES
			(?, ?, ?)
	";

	$res = exec_query($sql, $query, array($record_id,
			$user_def_lang,
			$user_theme_color));
	// send request to daemon
	send_request();

	$admin_login = $_SESSION['user_logged'];
	write_log("$admin_login: add user: $dmn_user_name (for domain $dmn_name)");
	write_log("$admin_login: add domain: $dmn_name");

	au_update_reseller_props($reseller_id, $props);

	if (isset($_POST['add_alias']) && $_POST['add_alias'] === 'on') {
		// we have to add some aliases for this looser
		$_SESSION['dmn_id'] = $dmn_id;
		$_SESSION['dmn_ip'] = $domain_ip;
		header("Location: user_add4.php?accout=$dmn_id");
		die();
	} else {
		// we have not to add alias
		$_SESSION['user_add3_added'] = "_yes_";
		header("Location: users.php");
		die();
	}
} // End of add_user_data()

?>