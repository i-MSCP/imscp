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
 * @package    iMSCP_Core
 * @subpackage  Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generates page.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function client_generatePage($tpl)
{
	// Generates IP list
	_client_generateIpsList($tpl);

	// Generates network cards list
	_client_generateNetcardsList($tpl);

	if (isset($_POST['ip_number'])) {
		$tpl->assign('VALUE_IP', tohtml($_POST['ip_number']));
	} else {
		$tpl->assign('VALUE_IP', '');
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
					 'NETWORK_CARD' => ($stmt->fields['ip_card'] === NULL) ? '' : tohtml($stmt->fields['ip_card'])
				)
			);

			$tpl->assign(
				array(
					 'ACTION_NAME' => ($cfg->BASE_SERVER_IP == $stmt->fields['ip_number'])
						 ? tr('Protected') : $actionName,
					 'ACTION_URL' => ($cfg->BASE_SERVER_IP == $stmt->fields['ip_number']) ? '#' : $actionUrl
				)
			);

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

	if ($status == 'ok') {
		return array(tr('Remove IP'), 'ip_delete.php?delete_id=' . $ipId);
	} elseif($status == 'todelete') {
		return array(translate_dmn_status('todelete'), '#');
	} elseif($status == 'toadd') {
		return array(translate_dmn_status('toadd'), '#');
	} elseif(
		!in_array(
			$status,
			array('toadd', 'tochange', 'ok', 'todelete')
		)
	) {
		return array(tr('Unknown Error'), '#');
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

	sort($networkCards);

	if (!empty($networkCards)) {
		foreach ($networkCards as $networkCard) {
			$tpl->assign('NETWORK_CARD', $networkCard);
			$tpl->parse('NETWORK_CARD_BLOCK', '.network_card_block');
		}
	} else { // Should never occur but who knows.
		set_page_message(tr('Unable to find any network interface. You cannot add new IP address.'), 'error');
		$tpl->assign('IP_ADDRESS_FORM_BLOCK', '');
	}
}

/**
 * Checks IP data.
 *
 * @param string $ipNumber IP number
 * @param string $netcard Network card
 * @return bool TRUE if data are valid, FALSE otherwise
 */
function client_checkIpData($ipNumber, $netcard)
{
	/** @var $networkCardObject iMSCP_NetworkCard */
	$networkCardObject = iMSCP_Registry::get('networkCardObject');

	$errFieldsStack = array();

	$query = "SELECT COUNT(IF(`ip_number` = ?, 1, NULL)) `isRegisteredIp` FROM `server_ips`";
	$stmt = exec_query($query, $ipNumber);

	if (filter_var($ipNumber, FILTER_VALIDATE_IP) === false) {
		set_page_message(tr('Wrong IP address.'), 'error');
		$errFieldsStack[] = 'ip_number';
	} elseif($stmt->fields['isRegisteredIp']) {
		set_page_message(tr('IP address already under the control of i-MSCP.'), 'error');
		$errFieldsStack[] = 'ip_number';
	}

	if (!in_array($netcard, $networkCardObject->getAvailableInterface())) {
		set_page_message(tr('You must select a network interface.'), 'error');
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
 * @param string $netcard Network card
 * @return void
 */
function client_registerIp($ipNumber, $netcard)
{
	$query = "INSERT INTO `server_ips` (`ip_number`, `ip_card`, `ip_status`) VALUES (?, ?, ?)";
	exec_query($query, array($ipNumber, $netcard, 'toadd'));

	send_request();
	set_page_message(tr('IP address successfully scheduled for addition.'), 'success');
	write_log("{$_SESSION['user_logged']} added new IP address: $ipNumber", E_USER_NOTICE);
	redirectTo('ip_manage.php');
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

// Register iMSCP_NetworkCard instance in registry for shared access
iMSCP_Registry::set('networkCardObject', new iMSCP_NetworkCard());

if (!empty($_POST)) {
	$ipNumber = isset($_POST['ip_number']) ? trim($_POST['ip_number']) : '';
	$netCard = isset($_POST['ip_card']) ? clean_input($_POST['ip_card']) : '';

	if (client_checkIpData($ipNumber, $netCard)) {
		client_registerIp($ipNumber, $netCard);
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
		'network_card_block' => 'ip_address_form_block'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings / IP Addresses Management'),
		'ISP_LOGO' => layout_getUserLogo(),
		#'MANAGE_IPS' => tr('Manage IP Addresses'),
		'TR_IP' => tr('IP Address'),
		'TR_ACTION' => tr('Action'),
		'TR_NETWORK_CARD' => tr('Network interface'),
		'TR_ADD' => tr('Add'),
        'TR_CANCEL' => tr('Cancel'),
		'TR_CONFIGURED_IPS' => tr('IP addresses under control of i-MSCP'),
		'TR_ADD_NEW_IP' => tr('Add new IP address'),
		'TR_IP_DATA' => tr('IP address data'),
		'TR_MESSAGE_DELETE' => json_encode(tr('Are you sure you want to delete this IP: %s?', true, '%s')),
		'TR_MESSAGE_DENY_DELETE' => json_encode(tr('You cannot remove the %s IP address.', true, '%s')),
		'ERR_FIELDS_STACK' => (iMSCP_Registry::isRegistered('errFieldsStack'))
			 ? json_encode(iMSCP_Registry::get('errFieldsStack')) : '[]',
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_TIP' => tr('This interface allow to add or remove IP addresses. IP addresses listed below are already under the control of i-MSCP. IP addresses which are added through this interface will be automatically added into the i-MSCP database, and will be available for assignment to one or many of your resellers. If an IP address is not already configured on the system, it will be attached to the selected network interface.')

	)
);

generateNavigation($tpl);
client_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
