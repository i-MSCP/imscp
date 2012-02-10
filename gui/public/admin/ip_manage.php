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
 * @subpackage	Admin
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

	if ($stmt->rowCount()) {
		while (!$stmt->EOF) {
			list(
				$actionName, $actionUrl
			) = _client_generateIpAction($stmt->fields['ip_id'], $stmt->fields['ip_status']);

			$tpl->assign(
				array(
					 'IP' => $stmt->fields['ip_number'],
					 'DOMAIN' => tohtml(idn_to_utf8($stmt->fields['ip_domain'])),
					 'ALIAS' => tohtml(idn_to_utf8($stmt->fields['ip_alias'])),
					 'NETWORK_CARD' => ($stmt->fields['ip_card'] === NULL) ? '' : tohtml($stmt->fields['ip_card'])));

			$tpl->assign(
				array(
					 'ACTION_NAME' => ($cfg->BASE_SERVER_IP == $stmt->fields['ip_number']) ? tr('Protected') : $actionName,
					 'ACTION_URL' => ($cfg->BASE_SERVER_IP == $stmt->fields['ip_number']) ? '#' : $actionUrl));

			$tpl->parse('IP_ADDRESS_BLOCK', '.ip_address_block');
			$stmt->moveNext();
		}
	} else { // Should never occur but who knows.
		$tpl->assign('IP_ADDRESSES_BLOCK', '');
		set_page_message(tr('No IP address found.'), 'info');
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
	} elseif($status == $cfg->ITEM_DELETE_STATUS) {
		return array(tr('Deletion in progress...'), '#');
	} elseif($status == $cfg->ITEM_ADD_STATUS) {
		return array(tr('Configuration in progress...'), '#');
	} elseif(!in_array($status, array($cfg->ITEM_ADD_STATUS, $cfg->ITEM_CHANGE_STATUS, $cfg->ITEM_OK_STATUS, $cfg->ITEM_DELETE_STATUS))) {
		return array(tr('Error state...'), '#');
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
			$tpl->parse('NETWORK_CARD_BLOCK', '.network_card_block');
		}
	} else { // Should never occur but who knows.
		set_page_message(tr('Unable to find network cards. Form to add new IP address has been disabled.'), 'error');
		$tpl->assign('IP_ADDRESS_FORM_BLOCK', '');
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

	$query = "
		SELECT
			COUNT(IF(`ip_number` = ?, 1, NULL)) `isRegisteredIp`,
			COUNT(IF(`ip_domain`= ?, 1, NULL)) `isAssignedDomain`,
			COUNT(IF(`ip_alias`= ?, 1, NULL)) `isAssignedAlias`
		FROM
			`server_ips`
	";
	$stmt = exec_query($query, array($ipNumber, idn_to_ascii($domain), idn_to_ascii($alias)));

	if (filter_var($ipNumber, FILTER_VALIDATE_IP) === false) {
		set_page_message(tr('Wrong IP address.'), 'error');
		$errFieldsStack[] = 'ip_number';
	} elseif($stmt->fields['isRegisteredIp']) {
		set_page_message(tr('IP address already known by the system.'), 'error');
		$errFieldsStack[] = 'ip_number';
	}

	if(!iMSCP_Validate::getInstance()->domainName($domain)) {
		set_page_message('Wrong domain syntax.', 'error');
		$errFieldsStack[] = 'domain';
	} elseif($stmt->fields['isAssignedDomain']) {
		set_page_message(tr('Domain already assigned to another IP address.'), 'error');
		$errFieldsStack[] = 'domain';
	}

	if(!iMSCP_Validate::getInstance()->hostname(idn_to_ascii($alias), array('allow' => Zend_Validate_Hostname::ALLOW_LOCAL)) ||
		strpos($alias, '.') !== false) {
		set_page_message('Wrong alias syntax.', 'error');
		$errFieldsStack[] = 'alias';
	} elseif($stmt->fields['isAssignedAlias']) {
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
function client_registerIp($ipNumber, $domain, $alias, $netcard)
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
	exec_query($query, array(
		$ipNumber, idn_to_ascii($domain), idn_to_ascii($alias), $netcard, null,
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

if (!empty($_POST)) {
	$ipNumber = isset($_POST['ip_number']) ? trim($_POST['ip_number']) : '';
	$domain = isset($_POST['domain']) ? clean_input($_POST['domain']) : '';
	$alias = isset($_POST['alias']) ? clean_input($_POST['alias']) : '';
	$netCard = isset($_POST['ip_card']) ? clean_input($_POST['ip_card']) : '';

	if (client_checkIpData($ipNumber, $domain, $alias, $netCard)) {
		client_registerIp($ipNumber, $domain, $alias, $netCard);
	}
}

$tpl = new iMSCP_pTemplate();

$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/ip_manage.tpl',
		'page_message' => 'layout',
		'ip_addresses_block' => 'page',
		'ip_address_block' => 'ip_addresses_block',
		'ip_address_form_block' => 'page',
		'network_card_block' => 'ip_address_form_block'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin / General settings / IPs management'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'MANAGE_IPS' => tr('Manage IPs'),
		'TR_IP' => tr('IP'),
		'TR_DOMAIN' => tr('Domain'),
		'TR_ALIAS' => tr('Alias'),
		'TR_STATUS' => tr('Status'),
		'TR_ACTION' => tr('Action'),
		'TR_NETWORK_CARD' => tr('Network interface'),
		'TR_ADD' => tr('Add'),
        'TR_CANCEL' => tr('Cancel'),
		'TR_CONFIGURED_IPS' => tr('IP addresses configured'),
		'TR_ADD_NEW_IP' => tr('Add new IP address'),
		'TR_IP_DATA' => tr('IP addresse data'),
		'TR_MESSAGE_DELETE' => json_encode(tr('Are you sure you want to delete this IP: %s?', true, '%s')),
		'TR_MESSAGE_DENY_DELETE' => json_encode(tr('You cannot remove the %s IP address.', true, '%s')),
		'ERR_FIELDS_STACK' => (iMSCP_Registry::isRegistered('errFieldsStack'))
			 ? json_encode(iMSCP_Registry::get('errFieldsStack')) : '[]',
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()));

generateNavigation($tpl);
client_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
