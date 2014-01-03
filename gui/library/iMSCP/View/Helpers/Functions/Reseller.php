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
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
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
	global $domainIp;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query("SELECT `reseller_ips` FROM `reseller_props` WHERE `reseller_id` = ?", $resellerId);

	$data = $stmt->fetchRow();

	$resellerIps = $data['reseller_ips'];

	$stmt = execute_query("SELECT * FROM `server_ips`");

	while ($data = $stmt->fetchRow()) {
		$ipId = $data['ip_id'];

		if (preg_match("/$ipId;/", $resellerIps) == 1) {
			$selected = ($domainIp === $ipId) ? $cfg->HTML_SELECTED : '';

			$tpl->assign(
				array(
					'IP_NUM' => $data['ip_number'],
					'IP_VALUE' => $ipId,
					'IP_SELECTED' => $selected
				)
			);

			$tpl->parse('IP_ENTRY', '.ip_entry');
		}
	}
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
		$tpl->assign('SEARCH_FOR', '');
	} else {
		$tpl->assign('SEARCH_FOR', tohtml($searchFor));
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
