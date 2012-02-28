<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/************************************************************************************
 * Script functions
 */

/**
 * Get data from previous page.
 *
 * @return bool
 */
function init_in_values()
{
	global $dmn_name, $dmn_expire, $dmn_user_name, $hpid;

	if (isset($_SESSION['dmn_expire'])) {
		$dmn_expire = $_SESSION['dmn_expire'];
	}

	if (isset($_SESSION['step_one'])) {
		$step_two = $_SESSION['dmn_name'] . ";" . $_SESSION['dmn_tpl'];
		$hpid = $_SESSION['dmn_tpl'];
		unset($_SESSION['dmn_name']);
		unset($_SESSION['dmn_tpl']);
		unset($_SESSION['chtpl']);
		unset($_SESSION['step_one']);
	} elseif (isset($_SESSION['step_two_data'])) {
		$step_two = $_SESSION['step_two_data'];
		unset($_SESSION['step_two_data']);
	} elseif (isset($_SESSION['local_data'])) {
		$step_two = $_SESSION['local_data'];
		unset($_SESSION['local_data']);
	} else {
		$step_two = "'';0";
	}

	list($dmn_name, $hpid) = explode(";", $step_two);

	$dmn_user_name = $dmn_name;

	if (!validates_dname(decode_idna($dmn_name)) || ($hpid == '')) {
		return false;
	}

	return true;
}

/**
 * Generates page add user 3.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function gen_user_add3_page(&$tpl)
{
	global $dmn_name, $hpid, $dmn_user_name, $user_email, $customer_id, $first_name,
		$last_name, $gender, $firm, $zip, $city, $state, $country, $street_one,
		$street_two, $mail, $phone, $fax;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$dmn_user_name = decode_idna($dmn_user_name);

	$tpl->assign(
		array(
			 'VL_USERNAME' => tohtml($dmn_user_name),
			 'VL_USR_PASS' => passgen(),
			 'VL_MAIL' => tohtml($user_email),
			 'VL_USR_ID' => $customer_id,
			 'VL_USR_NAME' => tohtml($first_name),
			 'VL_LAST_USRNAME' => tohtml($last_name),
			 'VL_USR_FIRM' => tohtml($firm),
			 'VL_USR_POSTCODE' => tohtml($zip),
			 'VL_USRCITY' => tohtml($city),
			 'VL_USRSTATE' => tohtml($state),
			 'VL_MALE' => ($gender == 'M') ? $cfg->HTML_SELECTED : '',
			 'VL_FEMALE' => ($gender == 'F') ? $cfg->HTML_SELECTED : '',
			 'VL_UNKNOWN' => ($gender == 'U') ? $cfg->HTML_SELECTED : '',
			 'VL_COUNTRY' => tohtml($country),
			 'VL_STREET1' => tohtml($street_one),
			 'VL_STREET2' => tohtml($street_two),
			 'VL_MAIL' => tohtml($mail),
			 'VL_PHONE' => tohtml($phone),
			 'VL_FAX' => tohtml($fax)));

	generate_ip_list($tpl, $_SESSION['user_id']);
	$_SESSION['local_data'] = "$dmn_name;$hpid";
}

/**
 * Init global value with empty values.
 *
 * @return void
 */
function gen_empty_data()
{
	global $user_email, $customer_id, $first_name, $last_name, $gender, $firm, $zip,
		$city, $state, $country, $street_one, $street_two, $mail, $phone, $fax, $domain_ip;

	$user_email = $customer_id = $first_name = $last_name = $firm = $zip = $city =
	$state = $country = $street_one = $street_two = $phone = $mail = $fax =
	$domain_ip = '';
	$gender = 'U';
}

/**
 * Save data for new user in db.
 *
 * @param  int $reseller_id Reseller unique identifier
 * @return bool TRUE on success, FALSE otherwiser
 */
function add_user_data($reseller_id)
{
	global $hpid, $dmn_name, $dmn_expire, $dmn_user_name, $admin_login, $user_email,
	$customer_id, $first_name, $last_name, $gender, $firm, $zip, $city, $state,
	$country, $street_one, $street_two, $mail, $phone, $fax, $inpass, $domain_ip,
	$dns, $backup, $software_allowed;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($_SESSION['ch_hpprops'])) {
		$props = $_SESSION['ch_hpprops'];
		unset($_SESSION['ch_hpprops']);
	} else {

		if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL === 'admin') {
			$query = 'SELECT `props` FROM `hosting_plans` WHERE `id` = ?';
			$stmt = exec_query($query, $hpid);
		} else {
			$query = "SELECT `props` FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?";
			$stmt = exec_query($query, array($reseller_id, $hpid));
		}

		$data = $stmt->fetchRow();
		$props = $data['props'];
	}

	list(
		$php, $cgi, $sub, $als, $mail, $ftp, $sql_db, $sql_user, $traff, $disk,
		$backup, $dns, $software_allowed, $phpini_system, $phpini_al_register_globals,
		$phpini_al_allow_url_fopen, $phpini_al_display_errors, $phpini_al_disable_functions,
		$phpini_post_max_size, $phpini_upload_max_filesize, $phpini_max_execution_time,
		$phpini_max_input_time, $phpini_memory_limit
	) = array_pad(explode(';', $props), 23, 'no');

	$php = preg_replace("/\_/", '', $php);
	$cgi = preg_replace("/\_/", '', $cgi);
	$backup = preg_replace("/\_/", '', $backup);
	$dns = preg_replace("/\_/", '', $dns);
	$software_allowed = preg_replace("/\_/", '', $software_allowed);
	$pure_user_pass = $inpass;
	$inpass = crypt_user_pass($inpass);
	$first_name = clean_input($first_name);
	$last_name = clean_input($last_name);
	$firm = clean_input($firm);
	$zip = clean_input($zip);
	$city = clean_input($city);
	$state = clean_input($state);
	$country = clean_input($country);
	$phone = clean_input($phone);
	$fax = clean_input($fax);
	$street_one = clean_input($street_one);
	$street_two = clean_input($street_two);
	$customer_id = clean_input($customer_id);

	if (!validates_dname(decode_idna($dmn_user_name))) {
		return false;
	}

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeAddDomain,
		array(
			'domainName' => $dmn_name,
			'createdBy' => $reseller_id,
			'customerId' => $customer_id,
			'customerEmail' => $user_email

		)
	);

	$query = "
		INSERT INTO
		    `admin` (
			    `admin_name`, `admin_pass`, `admin_type`, `domain_created`,
			    `created_by`, `fname`, `lname`, `firm`, `zip`, `city`, `state`,
			    `country`, `email`, `phone`, `fax`, `street1`, `street2`,
			    `customer_id`, `gender`
			) VALUES (
			    ?, ?, 'user', unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
			)
	";

	exec_query($query, array(
							$dmn_user_name, $inpass, $reseller_id, $first_name,
							$last_name, $firm, $zip, $city, $state, $country,
							$user_email, $phone, $fax, $street_one, $street_two,
							$customer_id, $gender));

	/** @var $db iMSCP_Database */
	$db = iMSCP_Registry::get('db');

	$record_id = $db->insertId();

	$query = "
		INSERT INTO
		    `domain` (
			    `domain_name`, `domain_admin_id`, `domain_created_id`, `domain_created`,
			    `domain_expires`, `domain_mailacc_limit`, `domain_ftpacc_limit`,
			    `domain_traffic_limit`, `domain_sqld_limit`, `domain_sqlu_limit`,
			    `domain_status`, `domain_subd_limit`, `domain_alias_limit`,
			    `domain_ip_id`, `domain_disk_limit`, `domain_disk_usage`,
			    `domain_php`, `domain_cgi`, `allowbackup`, `domain_dns`,
			    `domain_software_allowed`, `phpini_perm_system`, `phpini_perm_register_globals`,
			    `phpini_perm_allow_url_fopen`, `phpini_perm_display_errors`, `phpini_perm_disable_functions`
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
	";

	exec_query($query, array($dmn_name, $record_id, $reseller_id, time(), $dmn_expire,
							$mail, $ftp, $traff, $sql_db, $sql_user,
							$cfg->ITEM_ADD_STATUS, $sub, $als, $domain_ip, $disk, 0,
							$php, $cgi, $backup, $dns, $software_allowed,
							$phpini_system, $phpini_al_register_globals, $phpini_al_allow_url_fopen,
							$phpini_al_display_errors, $phpini_al_disable_functions));


	$dmn_id = $db->insertId();

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterAddDomain,
		array(
			'domainName' => $dmn_name,
			'createdBy' => $reseller_id,
			'customerId' => $record_id,
			'customerEmail' => $user_email,
			'domainId' => $dmn_id
		)
	);

	// save php.ini if exist
	if ($phpini_system == 'yes') {
		/* @var $phpini iMSCP_PHPini */
		$phpini = iMSCP_PHPini::getInstance();

		//fill it with the custom values - other thake from default
		$phpini->setData('phpiniSystem', 'yes');
		$phpini->setData('phpiniPostMaxSize', $phpini_post_max_size);
		$phpini->setData('phpiniUploadMaxFileSize', $phpini_upload_max_filesize);
		$phpini->setData('phpiniMaxExecutionTime', $phpini_max_execution_time);
		$phpini->setData('phpiniMaxInputTime', $phpini_max_input_time);
		$phpini->setData('phpiniMemoryLimit', $phpini_memory_limit);

		// save it to php_ini table
		$phpini->saveCustomPHPiniIntoDb($dmn_id);
	}

	$query = "
		INSERT INTO
		    `htaccess_users` (
		        `dmn_id`, `uname`, `upass`, `status`
            ) VALUES (
                ?, ?, ?, ?
            )
	";

	exec_query($query, array($dmn_id, $dmn_name,
							crypt_user_pass_with_salt($pure_user_pass),
							$cfg->ITEM_ADD_STATUS));

	$user_id = $db->insertId();

	$query = "
		INSERT INTO
		    `htaccess_groups` (
		        `dmn_id`, `ugroup`, `members`, `status`
            ) VALUES (
                ?, ?, ?, ?
            )
	";

	exec_query($query, array($dmn_id, $cfg->AWSTATS_GROUP_AUTH, $user_id, $cfg->ITEM_ADD_STATUS));

	// Create default addresses if needed
	if ($cfg->CREATE_DEFAULT_EMAIL_ADDRESSES) {
		client_mail_add_default_accounts($dmn_id, $user_email, $dmn_name);
	}

	// let's send mail to user
	send_add_user_auto_msg($reseller_id, $dmn_user_name, $pure_user_pass, $user_email,
						   $first_name, $last_name, tr('Domain account'));

	$user_def_lang = $cfg->USER_INITIAL_LANG;
	$user_theme_color = $cfg->USER_INITIAL_THEME;

	$query = "
		INSERT INTO
		    `user_gui_props` (
		        `user_id`, `lang`, `layout`
		    ) VALUES (
		        ?, ?, ?
            )
	";

	exec_query($query, array($record_id, $user_def_lang, $user_theme_color));

	send_request();

	$admin_login = $_SESSION['user_logged'];
	write_log("$admin_login: add user: $dmn_user_name (for domain $dmn_name)", E_USER_NOTICE);
	write_log("$admin_login: add domain: $dmn_name", E_USER_NOTICE);

	update_reseller_c_props($reseller_id);

	if (isset($_POST['add_alias']) && $_POST['add_alias'] === 'on') {
		$_SESSION['dmn_id'] = $dmn_id;
		$_SESSION['dmn_ip'] = $domain_ip;
		redirectTo('user_add4.php');
	} else {
		$_SESSION['user_add3_added'] = '_yes_';
		redirectTo('users.php?psi=last');
	}

	return true;
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/user_add3.tpl',
		'page_message' => 'layout',
		'ip_entry' => 'page',
		'alias_feature' => 'page'
	)
);

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - User/Add domain account - step 3'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_ADD_USER' => tr('Add user'),
		 'TR_CORE_DATA' => tr('Core data'),
		 'TR_USERNAME' => tr('Username'),
		 'TR_PASSWORD' => tr('Password'),
		 'TR_REP_PASSWORD' => tr('Repeat password'),
		 'TR_DMN_IP' => tr('Domain IP'),
		 'TR_USREMAIL' => tr('Email'),
		 'TR_ADDITIONAL_DATA' => tr('Additional data'),
		 'TR_CUSTOMER_ID' => tr('Customer ID'),
		 'TR_FIRSTNAME' => tr('First name'),
		 'TR_LASTNAME' => tr('Last name'),
		 'TR_GENDER' => tr('Gender'),
		 'TR_MALE' => tr('Male'),
		 'TR_FEMALE' => tr('Female'),
		 'TR_UNKNOWN' => tr('Unknown'),
		 'TR_COMPANY' => tr('Company'),
		 'TR_POST_CODE' => tr('Zip/Postal code'),
		 'TR_CITY' => tr('City'),
		 'TR_STATE_PROVINCE' => tr('State/Province'),
		 'TR_COUNTRY' => tr('Country'),
		 'TR_STREET1' => tr('Street 1'),
		 'TR_STREET2' => tr('Street 2'),
		 'TR_MAIL' => tr('Email'),
		 'TR_PHONE' => tr('Phone'),
		 'TR_FAX' => tr('Fax'),
		 'TR_BTN_ADD_USER' => tr('Add user'),
		 'TR_ADD_ALIASES' => tr('Add other domains to this account'),
		 'VL_USR_PASS' => passgen()));


generateNavigation($tpl);

if (!init_in_values()) {
	set_page_message(tr('Data were been altered. Please try again.'), 'error');
	unsetMessages();
	redirectTo('user_add1.php');
}

if (isset($_POST['uaction']) && ($_POST['uaction'] === 'user_add3_nxt') &&
	!isset($_SESSION['step_two_data'])
) {
	if (check_ruser_data($tpl, '_no_')) {
		add_user_data($_SESSION['user_id']);
	}
} else {
	unset($_SESSION['step_two_data']);
	gen_empty_data();
}

gen_user_add3_page($tpl);

if (!resellerHasFeature('domain_aliases')) {
	$tpl->assign('ALIAS_FEATURE', '');
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
