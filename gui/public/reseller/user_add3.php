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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Get data from previous page.
 *
 * @return bool
 */
function getPreviousPageData()
{
	global $dmnName, $dmnExpire, $dmnUsername, $hpId;

	if (isset($_SESSION['dmn_expire'])) {
		$dmnExpire = $_SESSION['dmn_expire'];
	}

	if (isset($_SESSION['step_one'])) {
		$stepTwo = "{$_SESSION['dmn_name']};{$_SESSION['dmn_tpl']}";
		$hpId = $_SESSION['dmn_tpl'];
		unset($_SESSION['dmn_name']);
		unset($_SESSION['dmn_tpl']);
		unset($_SESSION['chtpl']);
		unset($_SESSION['step_one']);
	} elseif (isset($_SESSION['step_two_data'])) {
		$stepTwo = $_SESSION['step_two_data'];
		unset($_SESSION['step_two_data']);
	} elseif (isset($_SESSION['local_data'])) {
		$stepTwo = $_SESSION['local_data'];
		unset($_SESSION['local_data']);
	} else {
		$stepTwo = "'';0";
	}

	list($dmnName, $hpId) = explode(';', $stepTwo);

	$dmnUsername = $dmnName;

	if (!isValidDomainName($dmnName) || $hpId == '') {
		return false;
	}

	return true;
}

/**
 * Init global value with empty values.
 *
 * @return void
 */
function reseller_generateEmptyPage()
{
	global $userEmail, $customerId, $firstName, $lastName, $gender, $firm, $zip, $city, $state, $country, $street1,
		$street2, $mail, $phone, $fax, $domainIp;

	$userEmail = $customerId = $firstName = $lastName = $firm = $zip = $city = $state = $country = $street1 = $street2 =
	$phone = $mail = $fax = $domainIp = '';
	$gender = 'U';
}

/**
 * Generates page.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function reseller_generatePage($tpl)
{
	global $dmnName, $hpId, $dmnUsername, $userEmail, $customerId, $firstName, $lastName, $gender, $firm, $zip, $city,
		$state, $country, $street1, $street2, $phone, $fax;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$dmnUsername = decode_idna($dmnUsername);

	$tpl->assign(
		array(
			'VL_USERNAME' => tohtml($dmnUsername),
			'VL_USR_PASS' => tohtml(passgen()),
			'VL_MAIL' => tohtml($userEmail),
			'VL_USR_ID' => $customerId,
			'VL_USR_NAME' => tohtml($firstName),
			'VL_LAST_USRNAME' => tohtml($lastName),
			'VL_USR_FIRM' => tohtml($firm),
			'VL_USR_POSTCODE' => tohtml($zip),
			'VL_USRCITY' => tohtml($city),
			'VL_USRSTATE' => tohtml($state),
			'VL_MALE' => ($gender == 'M') ? $cfg->HTML_SELECTED : '',
			'VL_FEMALE' => ($gender == 'F') ? $cfg->HTML_SELECTED : '',
			'VL_UNKNOWN' => ($gender == 'U') ? $cfg->HTML_SELECTED : '',
			'VL_COUNTRY' => tohtml($country),
			'VL_STREET1' => tohtml($street1),
			'VL_STREET2' => tohtml($street2),
			'VL_PHONE' => tohtml($phone),
			'VL_FAX' => tohtml($fax)
		)
	);

	generate_ip_list($tpl, $_SESSION['user_id']);
	$_SESSION['local_data'] = "$dmnName;$hpId";
}

/**
 * Save data for new user in db.
 *
 * @throws iMSCP_Exception_Database
 * @param  int $resellerId Reseller unique identifier
 * @return void
 */
function reseller_addCustomer($resellerId)
{
	global $hpId, $dmnName, $dmnExpire, $dmnUsername, $userEmail, $customerId, $firstName, $lastName, $gender, $firm,
		$zip, $city, $state, $country, $street1, $street2, $mail, $phone, $fax, $password, $domainIp, $dns, $backup,
		$aps, $extMailServer, $webFolderProtection;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($_SESSION['ch_hpprops'])) {
		$props = $_SESSION['ch_hpprops'];
		unset($_SESSION['ch_hpprops']);
	} else {
		if ($cfg->HOSTING_PLANS_LEVEL == 'admin') {
			$stmt = exec_query('SELECT `props` FROM `hosting_plans` WHERE `id` = ?', $hpId);
		} else {
			$stmt = exec_query(
				'SELECT `props` FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?', array($resellerId, $hpId)
			);
		}

		$data = $stmt->fetchRow();
		$props = $data['props'];
	}

	list(
		$php, $cgi, $sub, $als, $mail, $ftp, $sql_db, $sql_user, $traff, $disk, $backup, $dns, $aps, $phpEditor,
		$phpiniAllowUrlFopen, $phpiniDisplayErrors, $phpiniDisableFunctions, $phpiniPostMaxSize,
		$phpiniUploadMaxFileSize, $phpiniMaxExecutionTime, $phpiniMaxInputTime, $phpiniMemoryLimit, $extMailServer,
		$webFolderProtection, $mailQuota
	) = explode(';', $props);

	$php = str_replace('_', '', $php);
	$cgi = str_replace('_', '', $cgi);
	$backup = str_replace('_', '', $backup);
	$dns = str_replace('_', '', $dns);
	$aps = str_replace('_', '', $aps);
	$extMailServer = str_replace('_', '', $extMailServer);
	$webFolderProtection = str_replace('_', '', $webFolderProtection);
	$encryptedPassword = cryptPasswordWithSalt($password);
	$firstName = clean_input($firstName);
	$lastName = clean_input($lastName);
	$firm = clean_input($firm);
	$zip = clean_input($zip);
	$city = clean_input($city);
	$state = clean_input($state);
	$country = clean_input($country);
	$phone = clean_input($phone);
	$fax = clean_input($fax);
	$street1 = clean_input($street1);
	$street2 = clean_input($street2);
	$customerId = clean_input($customerId);

	if (!isValidDomainName($dmnUsername)) {
		return;
	}

	/** @var $db iMSCP_Database */
	$db = iMSCP_Database::getInstance();

	try {
		iMSCP_Events_Aggregator::getInstance()->dispatch(
			iMSCP_Events::onBeforeAddDomain,
			array(
				'domainName' => $dmnName,
				'createdBy' => $resellerId,
				'customerId' => $customerId,
				'customerEmail' => $userEmail
			)
		);

		$db->beginTransaction();

		$query = "
			INSERT INTO `admin` (
				`admin_name`, `admin_pass`, `admin_type`, `domain_created`, `created_by`, `fname`, `lname`, `firm`,
				`zip`, `city`, `state`, `country`, `email`, `phone`, `fax`, `street1`, `street2`, `customer_id`,
				`gender`, `admin_status`
			) VALUES (
				?, ?, 'user', unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
			)
		";
		exec_query(
			$query,
			array(
				$dmnUsername, $encryptedPassword, $resellerId, $firstName, $lastName, $firm, $zip, $city, $state,
				$country, $userEmail, $phone, $fax, $street1, $street2, $customerId, $gender, 'toadd'
			)
		);

		$recordId = $db->insertId();

		$query = "
			INSERT INTO `domain` (
				`domain_name`, `domain_admin_id`, `domain_created`, `domain_expires`,
				`domain_mailacc_limit`, `domain_ftpacc_limit`, `domain_traffic_limit`, `domain_sqld_limit`,
				`domain_sqlu_limit`, `domain_status`, `domain_alias_limit`, `domain_subd_limit`, `domain_ip_id`,
				`domain_disk_limit`, `domain_disk_usage`, `domain_php`, `domain_cgi`, `allowbackup`, `domain_dns`,
				`domain_software_allowed`, `phpini_perm_system`, `phpini_perm_allow_url_fopen`,
				`phpini_perm_display_errors`, `phpini_perm_disable_functions`, `domain_external_mail`,
				`web_folder_protection`, `mail_quota`
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
			)
		";
		exec_query(
			$query,
			array(
				$dmnName, $recordId, time(), $dmnExpire, $mail, $ftp, $traff, $sql_db, $sql_user,
				'toadd', $als, $sub, $domainIp, $disk, 0, $php, $cgi, $backup, $dns, $aps, $phpEditor,
				$phpiniAllowUrlFopen, $phpiniDisplayErrors, $phpiniDisableFunctions, $extMailServer,
				$webFolderProtection, $mailQuota
			)
		);

		$dmnId = $db->insertId();

		// save php.ini if exist
		if ($phpEditor == 'yes') {
			/* @var $phpini iMSCP_PHPini */
			$phpini = iMSCP_PHPini::getInstance();

			// fill it with the custom values - other take from default
			$phpini->setData('phpiniSystem', 'yes');
			$phpini->setData('phpiniPostMaxSize', $phpiniPostMaxSize);
			$phpini->setData('phpiniUploadMaxFileSize', $phpiniUploadMaxFileSize);
			$phpini->setData('phpiniMaxExecutionTime', $phpiniMaxExecutionTime);
			$phpini->setData('phpiniMaxInputTime', $phpiniMaxInputTime);
			$phpini->setData('phpiniMemoryLimit', $phpiniMemoryLimit);

			// save it to php_ini table
			$phpini->saveCustomPHPiniIntoDb($dmnId);
		}

		$query = 'INSERT INTO `htaccess_users` (`dmn_id`, `uname`, `upass`, `status`) VALUES (?, ?, ?, ?)';
		exec_query($query, array($dmnId, $dmnName, $encryptedPassword, 'toadd'));

		$user_id = $db->insertId();

		$query = 'INSERT INTO `htaccess_groups` (`dmn_id`, `ugroup`, `members`, `status`) VALUES (?, ?, ?, ?)';
		exec_query($query, array($dmnId, $cfg->WEBSTATS_GROUP_AUTH, $user_id, 'toadd'));

		// Create default addresses if needed
		if ($cfg['CREATE_DEFAULT_EMAIL_ADDRESSES']) {
			client_mail_add_default_accounts($dmnId, $userEmail, $dmnName);
		}

		// let's send mail to user
		send_add_user_auto_msg($resellerId, $dmnUsername, $password, $userEmail, $firstName, $lastName, tr('Customer', true));

		$query = 'INSERT INTO `user_gui_props` (`user_id`, `lang`, `layout`) VALUES (?, ?, ?)';
		exec_query($query, array($recordId, $cfg->USER_INITIAL_LANG, $cfg->USER_INITIAL_THEME));

		update_reseller_c_props($resellerId);

		$db->commit();

		iMSCP_Events_Aggregator::getInstance()->dispatch(
			iMSCP_Events::onAfterAddDomain,
			array(
				'domainName' => $dmnName,
				'createdBy' => $resellerId,
				'customerId' => $recordId,
				'customerEmail' => $userEmail,
				'domainId' => $dmnId
			)
		);

		send_request();

		write_log("{$_SESSION['user_logged']} added new customer: $dmnUsername", E_USER_NOTICE);
		set_page_message(tr('Customer account successfully scheduled for creation.'), 'success');

		redirectTo('users.php');
	} catch (iMSCP_Exception_Database $e) {
		$db->rollBack();
		throw $e;
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

if (!getPreviousPageData()) {
	set_page_message(tr('Data were been altered. Please try again.'), 'error');
	unsetMessages();
	redirectTo('user_add1.php');
}

if (isset($_POST['uaction']) && ($_POST['uaction'] == 'user_add3_nxt') && !isset($_SESSION['step_two_data'])) {
	if (check_ruser_data(false)) {
		reseller_addCustomer($_SESSION['user_id']);
	}
} else {
	unset($_SESSION['step_two_data']);
	reseller_generateEmptyPage();
}

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
		'TR_PAGE_TITLE' => tr('Reseller / Customers / Add Customer - Next Step'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_ADD_USER' => tr('Add user'),
		'TR_CORE_DATA' => tr('Core data'),
		'TR_USERNAME' => tr('Username'),
		'TR_PASSWORD' => tr('Password'),
		'TR_REP_PASSWORD' => tr('Repeat password'),
		'TR_DOMAIN_IP' => tr('Domain IP'),
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
		'TR_POST_CODE' => tr('Zip'),
		'TR_CITY' => tr('City'),
		'TR_STATE_PROVINCE' => tr('State/Province'),
		'TR_COUNTRY' => tr('Country'),
		'TR_STREET1' => tr('Street 1'),
		'TR_STREET2' => tr('Street 2'),
		'TR_MAIL' => tr('Email'),
		'TR_PHONE' => tr('Phone'),
		'TR_FAX' => tr('Fax'),
		'TR_BTN_ADD_USER' => tr('Add user'),
		'VL_USR_PASS' => passgen()
	)
);

if (!resellerHasFeature('domain_aliases')) {
	$tpl->assign('ALIAS_FEATURE', '');
}

generateNavigation($tpl);
reseller_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
