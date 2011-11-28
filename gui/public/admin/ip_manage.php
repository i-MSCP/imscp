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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2011 by i-MSCP | http://i-mscp.net
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 */

/************************************************************************************
 * Script functions
 */

/**
 * Generates page.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function client_generatePage($tpl) {
	// Generates IP list
	_client_generateIpsList($tpl);

	// Generates network cards list
	_client_generateNetcardsList($tpl);

	if (isset($_POST['ip_number'])) {
		$tpl->assign(
			array(
				'VALUE_IP' => tohtml($_POST['ip_number']),
				'VALUE_DOMAIN' => clean_input($_POST['domain'], true),
				'VALUE_ALIAS' => clean_input($_POST['alias'], true)));
	} else {
		$tpl->assign(
			array(
				'VALUE_IP' => '',
				'VALUE_DOMAIN' => '',
				'VALUE_ALIAS' => ''));
	}
}

/**
 * Generates IPs list.
 *
 * @access private
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function _client_generateIpsList($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT * FROM `server_ips`";
	$stmt = execute_query($query);

	if ($stmt->recordCount() > 0) {
		while (!$stmt->EOF) {
			list(
				$ip_action, $ip_action_script
			) = _client_generateIpAction($stmt->fields['ip_id'], $stmt->fields['ip_status']);

			$tpl->assign(
				array(
					 'IP' => $stmt->fields['ip_number'],
					 'DOMAIN' => tohtml($stmt->fields['ip_domain']),
					 'ALIAS' => tohtml($stmt->fields['ip_alias']),
					 'NETWORK_CARD' => ($stmt->fields['ip_card'] === NULL) ? '' : tohtml($stmt->fields['ip_card'])));

			$tpl->assign(
				array(
					 'IP_DELETE_SHOW' => '',
					 'IP_ACTION' => ($cfg->BASE_SERVER_IP == $stmt->fields['ip_number']) ? tr('N/A') : $ip_action,
					 'IP_ACTION_SCRIPT' => ($cfg->BASE_SERVER_IP == $stmt->fields['ip_number']) ? '#' : $ip_action_script));

			$tpl->parse('IP_ROW', '.ip_row');
			$stmt->moveNext();
		}
	} else { // Should never occur but who knows.
		$tpl->assign('IPS_LIST', '');
		set_page_message(tr('No IP found in database.'), 'info');
	}
}

/**
 * Generates Ips action.
 *
 * @access private
 * @param int $ipId Ip address unique identifier
 * @param string $status
 * @return array
 */
function _client_generateIpAction($ipId, $status)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($status == $cfg->ITEM_OK_STATUS) {
		return array(tr('Remove IP'), 'ip_delete.php?delete_id=' . $ipId);
	} else {
		return array(tr('N/A'), '#');
	}
}

/**
 * Generates network cards list.
 *
 * @access private
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function _client_generateNetcardsList($tpl)
{
	/** @var $networkCardObject iMSCP_NetworkCard */
	$networkCardObject = iMSCP_Registry::get('networkCardObject');

	if ($networkCardObject->getErrors() != '') {
		set_page_message($networkCardObject->getErrors(), 'error');
	}

	$networkCards = $networkCardObject->getAvailableInterface();

	if (!empty($networkCards)) {
		foreach ($networkCards as $networkCard) {
			$tpl->assign('NETWORK_CARD', $networkCard);
			$tpl->parse('CARDS_LIST', '.cards_list');
		}
	} else { // Should never occur but who knows.
		set_page_message(tr('Unable to find network cards. Form to add new IP address has been disabled.'), 'error');
		$tpl->assign('ADD_IP', '');
	}
}

/**
 * Checks IP data.
 *
 * @param string $ipNumber IP number
 * @param string $domain Domain
 * @param string $alias Alias
 * @param string $netcard Network card
 * @return bool TRUE if data are valid, FALSE otherwise
 */
function client_checkIpData($ipNumber, $domain, $alias, $netcard)
{
	/** @var $networkCardObject iMSCP_NetworkCard */
	$networkCardObject = iMSCP_Registry::get('networkCardObject');

	$errFieldsStack = array();

	if (filter_var($ipNumber, FILTER_VALIDATE_IP) === false) {
		set_page_message(tr('Wrong IP address.'), 'error');
		$errFieldsStack[] = 'ip_number';
	}

	$query = "
		SELECT
			COUNT(IF(`ip_number` = ?, 1, NULL)) `isRegisteredIp`,
			COUNT(IF(`ip_domain`= ?, 1, NULL)) `isAssignedDomain`,
			COUNT(IF(`ip_alias`= ?, 1, NULL)) `isAssignedAlias`
		FROM
			`server_ips`
	";
	$stmt = exec_query($query, array($ipNumber, $domain, $alias));

	if($stmt->fields['isRegisteredIp']) {
		set_page_message(tr('IP address already known by the system.'), 'error');
		$errFieldsStack[] = 'ip_number';
	}

	if($stmt->fields['isAssignedDomain']) {
		set_page_message(tr('Domain already assigned to another IP address.'), 'error');
		$errFieldsStack[] = 'domain';
	}

	if($stmt->fields['isAssignedAlias']) {
		set_page_message(tr('Alias already assigned to another IP address.'), 'error');
		$errFieldsStack[] = 'alias';
	}

	if (!in_array($netcard, $networkCardObject->getAvailableInterface())) {
		set_page_message(tr('You must select a network card.'), 'error');
	}

	if (Zend_Session::namespaceIsset('pageMessages')) {
		if(!empty($errFieldsStack)) {
			iMSCP_Registry::set('errFieldsStack', $errFieldsStack);
		}

		return false;
	}

	return true;
}

/**
 * Register new IP.
 *
 * @param string $ipNumber IP number (dot notation)
 * @param string $domain Domain
 * @param string $alias Alias
 * @param string $netcard Network card
 * @return void
 */
function client_registerIp(&$ipNumber, &$domain, &$alias, $netcard)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		INSERT INTO `server_ips` (
			`ip_number`, `ip_domain`, `ip_alias`, `ip_card`, `ip_ssl_domain_id`,
			`ip_status`
		) VALUES (
			?, ?, ?, ?, ?, ?
		)
	";

	// Todo: Review the domain and alias (must be compatible with /etc/hosts syntax)
	exec_query($query, array(
							$ipNumber,
							htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'),
							htmlspecialchars($alias, ENT_QUOTES, 'UTF-8'),
							htmlspecialchars($netcard, ENT_QUOTES, 'UTF-8'),
							NULL,
							$cfg->ITEM_ADD_STATUS));

	send_request();
	set_page_message(tr('IP address scheduled for addition.'), 'success');
	write_log("IP address {$ipNumber} was added by {$_SESSION['user_logged']}", E_USER_NOTICE);
	redirectTo('ip_manage.php');
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

// Register iMSCP_NetworkCard instance in registry for shared access
iMSCP_Registry::set('networkCardObject', new iMSCP_NetworkCard());

if (isset($_POST['uaction']) && $_POST['uaction'] == 'addIpAddress') {
	$ipNumber = trim($_POST['ip_number']);
	$domain = clean_input($_POST['domain']);
	$alias = clean_input($_POST['alias']);
	$netcard = clean_input($_POST['ip_card']);

	if (client_checkIpData($ipNumber, $domain, $alias, $netcard)) {
		client_registerIp($ipNumber, $domain, $alias, $netcard);
	}
}

$tpl = new iMSCP_pTemplate();

$tpl->define_dynamic(
	array(
		'page' => $cfg->ADMIN_TEMPLATE_PATH . '/ip_manage.tpl',
		'page_message' => 'page',
		'ips_list' => 'page',
		'ip_row' => 'ips_list',
		'add_ip' => 'page',
		'cards_list' => 'add_ip'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin / General settings / IPs management'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'MANAGE_IPS' => tr('Manage IPs'),
		'TR_AVAILABLE_IPS' => tr('Available IPs'),
		'TR_IP' => tr('IP'),
		'TR_DOMAIN' => tr('Domain'),
		'TR_ALIAS' => tr('Alias'),
		'TR_ACTION' => tr('Action'),
		'TR_NETWORK_CARD' => tr('Network interface'),
		'TR_ADD' => tr('Add'),
		'TR_REGISTERED_IPS' => tr('Registered IPs'),
		'TR_ADD_NEW_IP' => tr('Add new IP'),
		'TR_IP_DATA' => tr('IP data'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete this IP: %s?', true, '%s'),
		'TR_MESSAGE_DENY_DELETE' => tr('You cannot remove the %s IP address.', true, '%s'),
		'ERR_FIELDS_STACK' => (iMSCP_Registry::isRegistered('errFieldsStack'))
			 ? json_encode(iMSCP_Registry::get('errFieldsStack')) : '[]'));

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');
client_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
