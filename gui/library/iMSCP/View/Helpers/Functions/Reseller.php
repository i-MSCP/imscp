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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2013 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 */

/**********************************************************************************************************************
 * This file contains view helpers functions that are responsible to generate template parts for reseller interface.
 */

/**
 * Returns Ip list.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  int $resellerId Reseller unique identifier
 * @return void
 */
function generate_ip_list($tpl, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	global $domainIp;

	$query = "SELECT `reseller_ips` FROM `reseller_props` WHERE `reseller_id` = ?";

	$stmt = exec_query($query, $resellerId);

	$data = $stmt->fetchRow();

	$resellerIps = $data['reseller_ips'];

	$query = "SELECT * FROM `server_ips`";

	$stmt = execute_query($query);

	while ($data = $stmt->fetchRow()) {
		$ipId = $data['ip_id'];

		if (preg_match("/$ipId;/", $resellerIps) == 1) {
			$selected = ($domainIp === $ipId) ? $cfg->HTML_SELECTED : '';

			$tpl->assign(
				array(
					'IP_NUM' => $data['ip_number'],
					'IP_NAME' => tohtml($data['ip_domain']),
					'IP_VALUE' => $ipId,
					'IP_SELECTED' => $selected
				)
			);

			$tpl->parse('IP_ENTRY', '.ip_entry');
		}
	}
}

/**
 * Check validity of input data.
 *
 * @param  bool $noPass
 * @return bool
 */
function check_ruser_data($noPass)
{
	global $dmn_name, $hpid, $dmn_user_name, $user_email, $customer_id, $first_name,
	       $last_name, $firm, $zip, $gender, $city, $state, $country, $street_one,
	       $street_two, $mail, $phone, $fax, $inpass, $domainIp;

	$cfg = iMSCP_Registry::get('config');

	$inpass_re = '';

	// Get data for fields from previous page
	if (isset($_POST['userpassword']))
		$inpass = $_POST['userpassword'];

	if (isset($_POST['userpassword_repeat']))
		$inpass_re = $_POST['userpassword_repeat'];

	if (isset($_POST['domain_ip']))
		$domainIp = $_POST['domain_ip'];

	if (isset($_POST['useremail']))
		$user_email = $_POST['useremail'];

	if (isset($_POST['useruid']))
		$customer_id = $_POST['useruid'];

	if (isset($_POST['userfname']))
		$first_name = $_POST['userfname'];

	if (isset($_POST['userlname']))
		$last_name = $_POST['userlname'];

	if (isset($_POST['userfirm']))
		$firm = $_POST['userfirm'];

	if (isset($_POST['userzip']))
		$zip = $_POST['userzip'];

	if (isset($_POST['usercity']))
		$city = $_POST['usercity'];

	if (isset($_POST['userstate']))
		$state = $_POST['userstate'];

	if (isset($_POST['usercountry']))
		$country = $_POST['usercountry'];

	if (isset($_POST['userstreet1']))
		$street_one = $_POST['userstreet1'];

	if (isset($_POST['userstreet2']))
		$street_two = $_POST['userstreet2'];

	if (isset($_POST['useremail']))
		$mail = $_POST['useremail'];

	if (isset($_POST['userphone']))
		$phone = $_POST['userphone'];

	if (isset($_POST['userfax']))
		$fax = $_POST['userfax'];

	if (isset($_POST['gender']) && get_gender_by_code($_POST['gender'], true) !== null
	) {
		$gender = $_POST['gender'];
	} else {
		$gender = '';
	}

	// Begin checking...
	if (!$noPass) {
		if ('' === $inpass_re || '' === $inpass) {
			set_page_message(tr('Please fill up both data fields for password.'), 'error');
		} else if ($inpass_re !== $inpass) {
			set_page_message(tr("Passwords doesn't match."), 'error');
		}

		checkPasswordSyntax($inpass);
	}

	if ($user_email == NULL) {
		set_page_message(tr('Incorrect email length or syntax.'), 'error');
	}
	/* we don't want to validate Customer ID, First and Second name and also ZIP

	   else if (!imscp_limit_check($customer_id)) {
		 $user_add_error = tr('Incorrect customer ID syntax!');
	 } else if (!chk_username($first_name, 40)) {

		 $user_add_error = tr('Incorrect first name length or syntax!');
	 } else if (!chk_username($last_name, 40)) {

		 $user_add_error = tr('Incorrect second name length or syntax!');
	 } else if (!imscp_limit_check($zip)) {

		 $user_add_error = tr('Incorrect post code length or syntax!');
	 } */

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		return true;
	}

	return false;
}

/**
 * Generate reseller domain search form
 *
 * @param iMSCP_pTemplate $tpl
 * @param string $searchFor
 * @param string $searchCommon
 * @param string $searchStatus
 * @return void
 */
function gen_manage_domain_search_options($tpl, $searchFor, $searchCommon, $searchStatus)
{

	$cfg = iMSCP_Registry::get('config');

	if ($searchFor === 'n/a' && $searchCommon === 'n/a' && $searchStatus === 'n/a') {
		// we have no search and let's genarate search fields empty
		$domainSelected = $cfg->HTML_SELECTED;
		$customerIdSelected = '';
		$lastnameSelected = '';
		$companySelected = '';
		$citySelected = '';
		$stateSelected = '';
		$countrySelected = '';

		$allSelected = $cfg->HTML_SELECTED;
		$okSelected = '';
		$suspendedSelected = '';
	} else {
		if ($searchCommon === 'domain_name') {
			$domainSelected = $cfg->HTML_SELECTED;
			$customerIdSelected = '';
			$lastnameSelected = '';
			$companySelected = '';
			$citySelected = '';
			$stateSelected = '';
			$countrySelected = '';
		} elseif ($searchCommon === 'customer_id') {
			$domainSelected = '';
			$customerIdSelected = $cfg->HTML_SELECTED;
			$lastnameSelected = '';
			$companySelected = '';
			$citySelected = '';
			$stateSelected = '';
			$countrySelected = '';
		} elseif ($searchCommon === 'lname') {
			$domainSelected = '';
			$customerIdSelected = '';
			$lastnameSelected = $cfg->HTML_SELECTED;
			$companySelected = '';
			$citySelected = '';
			$stateSelected = '';
			$countrySelected = '';
		} elseif ($searchCommon === 'firm') {
			$domainSelected = '';
			$customerIdSelected = '';
			$lastnameSelected = '';
			$companySelected = $cfg->HTML_SELECTED;
			$citySelected = '';
			$stateSelected = '';
			$countrySelected = '';
		} elseif ($searchCommon === 'city') {
			$domainSelected = '';
			$customerIdSelected = '';
			$lastnameSelected = '';
			$companySelected = '';
			$citySelected = $cfg->HTML_SELECTED;
			$stateSelected = '';
			$countrySelected = '';
		} elseif ($searchCommon === 'state') {
			$domainSelected = '';
			$customerIdSelected = '';
			$lastnameSelected = '';
			$companySelected = '';
			$citySelected = '';
			$stateSelected = $cfg->HTML_SELECTED;
			$countrySelected = '';
		} elseif ($searchCommon === 'country') {
			$domainSelected = '';
			$customerIdSelected = '';
			$lastnameSelected = '';
			$companySelected = '';
			$citySelected = '';
			$stateSelected = '';
			$countrySelected = $cfg->HTML_SELECTED;
		} else {
			showBadRequestErrorPage();
			exit;
		}

		if ($searchStatus === 'all') {
			$allSelected = $cfg->HTML_SELECTED;
			$okSelected = '';
			$suspendedSelected = '';
		} else if ($searchStatus === 'ok') {
			$allSelected = '';
			$okSelected = $cfg->HTML_SELECTED;
			$suspendedSelected = '';
		} else if ($searchStatus === 'disabled') {
			$allSelected = '';
			$okSelected = '';
			$suspendedSelected = $cfg->HTML_SELECTED;
		} else {
			showBadRequestErrorPage();
			exit;
		}
	}

	if ($searchFor === 'n/a' || $searchFor === '') {
		$tpl->assign(
			array('SEARCH_FOR' => "")
		);
	} else {
		$tpl->assign(
			array('SEARCH_FOR' => tohtml($searchFor))
		);
	}

	$tpl->assign(
		array(
			'M_DOMAIN_NAME' => tr('Domain name'),
			'M_CUSTOMER_ID' => tr('Customer ID'),
			'M_LAST_NAME' => tr('Last name'),
			'M_COMPANY' => tr('Company'),
			'M_CITY' => tr('City'),
			'M_STATE' => tr('State/Province'),
			'M_COUNTRY' => tr('Country'),

			'M_ALL' => tr('All'),
			'M_OK' => tr('OK'),
			'M_SUSPENDED' => tr('Suspended'),
			'M_ERROR' => tr('Error'),

			'M_DOMAIN_NAME_SELECTED' => $domainSelected,
			'M_CUSTOMER_ID_SELECTED' => $customerIdSelected,
			'M_LAST_NAME_SELECTED' => $lastnameSelected,
			'M_COMPANY_SELECTED' => $companySelected,
			'M_CITY_SELECTED' => $citySelected,
			'M_STATE_SELECTED' => $stateSelected,
			'M_COUNTRY_SELECTED' => $countrySelected,

			'M_ALL_SELECTED' => $allSelected,
			'M_OK_SELECTED' => $okSelected,
			'M_SUSPENDED_SELECTED' => $suspendedSelected,
		)
	);
}
