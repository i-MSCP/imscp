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

/***********************************************************************************************************************
 * Script short description
 *
 * This script allow to add/update/remove services port properties
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * get Protocol data fro $_POST array.
 * @param integer $index
 * @return protocol
 */
function getProtocol($index) {

	/** @var $dbConfig iMSCP_Config_Handler_Db */
	$dbConfig = iMSCP_Registry::get('dbConfig');

	if(isset($_POST['port_type'][$index])){
		$protocol = $_POST['port_type'][$index];
	} else {
		try{
			$sData = $dbConfig->{$_POST['var_name'][$index]};
			$sData = explode(';', $sData);
			$protocol = $sData[1];
		} catch(Exception $e){
			$protocol = 'notexistingone';
		}
	}
	return $protocol;
}
/**
 * Prepare and put data in session on error(s).
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param boolean TRUE on add, FALSE otherwise
 * @return void
 */
function toSession($mode) {

	/** @var $dbConfig iMSCP_Config_Handler_Db */
	$dbConfig = iMSCP_Registry::get('dbConfig');

	// Get a reference to the array that contain all error fields ids
	$errorFieldsIds = &iMSCP_Registry::get('errorFieldsIds');

	// Create a json object that will be used by client browser for fields
	// highlighting
	$_SESSION['errorFieldsIds'] = json_encode($errorFieldsIds);

	// Data for error on add
	if($mode == 'add') {
		$values = array(
			'name_new' => $_POST['name_new'],
			'ip_new' => $_POST['ip_new'],
			'port_new' => $_POST['port_new'],
			'port_type_new' => $_POST['port_type_new'],
			'show_val_new' => $_POST['show_val_new']);

		$_SESSION['error_on_add'] = $values;

	// Data for error on update
	} else {
		foreach($_POST['var_name'] as $index => $service) {
			$port = $_POST['port'][$index];
			$protocol = getProtocol($index);
			$name = $_POST['name'][$index];
			$show = $_POST['show_val'][$index];
			$custom = $_POST['custom'][$index];
			$ip = $_POST['ip'][$index];
			$values[$service] = "$port;$protocol;$name;$show;$custom;$ip";
			$_SESSION['error_on_updt'] = $values;
		}
	}
}

/**
 * Validates a service port and sets an appropriate message on error.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param string $name Service name
 * @param string $ip Ip address
 * @param int $port Port
 * @param string $protocol Protocle
 * @param bool $show Tell whether or not service must be show on status page
 * @param string $index Item index on update, empty value otherwise
 * @return bool TRUE if valid, FALSE otherwise
 */
function admin_validatesService($name, $ip, $port, $protocol, $show, $index = '')
{
	/** @var $dbConfig iMSCP_Config_Handler_Db */
	$dbConfig = iMSCP_Registry::get('dbConfig');

	// Get a reference to the array that contain all error fields ids
	$errorFieldsIds = &iMSCP_Registry::get('errorFieldsIds');

	// Todo check for ipv6
	$dbServiceName = "PORT_$name";
	$ip = ($ip == 'localhost') ? '127.0.0.1' : $ip;

	// Check for service name syntax
	if (!is_basicString($name)) {
		set_page_message(tr("Error with '$name': Only letters, numbers, dash and underscore are allowed for services names."), 'error');
		$errorFieldsIds[] = "name$index";
	}

	// Check for IP syntax
	if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
		set_page_message(tr('Error: Wrong Ip number.'), 'error');
		$errorFieldsIds[] = "ip$index";
	}

	// Check for port syntax
	if(!is_number($port) || $port <= 0) {
		set_page_message(tr('Only positive numbers are allowed for services ports.'), 'error');
		$errorFieldsIds[] = "port$index";
	}

	// Check for service port existences
	if(!is_int($index) && isset($dbConfig->$dbServiceName)) {
		set_page_message(tr('Error: Service port with same name already exists.'), 'error');
		$errorFieldsIds[] = "name$index";
	}

	// Check for protocol
	if($protocol != 'tcp' && $protocol != 'udp') {
		set_page_message(tr('Error: Unallowed protocol.'), 'error');
		$errorFieldsIds[] = "port_type$index";
	}

	// Check for show entry
	if($show != '0' && $show != '1') {
		set_page_message(tr('Error: Wrong value for show entry.'), 'error');
		$errorFieldsIds[] = "show_val$index";
	}

	return (Zend_Session::namespaceIsset('pageMessages')) ? false : true;
}

/**
 * Adds or updates services ports
 *
 * @since 1.0.7 (ispCP)
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param string $mode Mode in witch act (add or update)
 * @return void
 */
function admin_addUpdateServices($mode = 'add')
{
	/** @var $dbConfig iMSCP_Config_Handler_Db */
	$dbConfig = iMSCP_Registry::get('dbConfig');

	// Create a pool for error fields ids and gets a reference to him
	$errorFieldsIds = &iMSCP_Registry::set('errorFieldsIds', array());

	// Adds a service port
	if($mode == 'add') {
		$port = $_POST['port_new'];
		$protocol = $_POST['port_type_new'];
		$name = strtoupper($_POST['name_new']);
		$show = $_POST['show_val_new'];
		$ip = $_POST['ip_new'];

		if(admin_validatesService($name, $ip, $port, $protocol, $show)) {
			$dbServiceName = "PORT_$name";
			$dbConfig->$dbServiceName = "$port;$protocol;$name;$show;1;$ip";
			write_log(get_session('user_logged') . ": Added service port $name ($port)!", E_USER_NOTICE);
		}

	// Updates one or more services ports
	} elseif($mode == 'update') {
		// Reset counter of update queries
		$dbConfig->resetQueriesCounter('update');

		foreach($_POST['name'] as $index => $name) {
			$port = $_POST['port'][$index];
			$protocol = getProtocol($index);
			$name = strtoupper($name);
			$show = $_POST['show_val'][$index];
			$custom = $_POST['custom'][$index];
			$ip = $_POST['ip'][$index];

			if(admin_validatesService($name, $ip, $port, $protocol, $show, $index)) {
				$dbServiceName = $_POST['var_name'][$index];
				$dbConfig->$dbServiceName = "$port;$protocol;$name;$show;$custom;$ip";
			}
		}
	} else {
		throw new iMSCP_Exception('admin_addUpdateServices(): Wrong argument for $mode');
	}

	if(!empty($errorFieldsIds)) {
		toSession($mode);
	} elseif($mode == 'add') {
		set_page_message(tr('Service port was successfully added'), 'success');
	} else {
		$updateCount = $dbConfig->countQueries('update');

		if($updateCount > 0) {
			set_page_message(tr('%d Service(s) port was successfully updated', $updateCount), 'success');
		} else {
			set_page_message(tr("Nothing's been changed"));
		}
	}
}

/**
 * Gets and prepares the template part for services ports
 *
 * This function is used for generation of both pages (show page and error page)
 *
 * @since 1.0.7 (ispCP)
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void;
 */
function admin_showServices($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if(isset($_SESSION['error_on_updt'])) {
		$values = new iMSCP_Config_Handler($_SESSION['error_on_updt']);
		unset($_SESSION['error_on_updt']);
		$services = array_keys($values->toArray());
	} else {
		$values = iMSCP_Registry::get('dbConfig');

		// Filter function to get only the services ports names
		$filter = create_function('$value', 'if(substr($value, 0, 5) == \'PORT_\') return $value;');

		// Gets list of services port names
		$services = array_filter(array_keys($values->toArray()), $filter);

		if(isset($_SESSION['errorOnAdd'])) {
			$errorOnAdd = new iMSCP_Config_Handler($_SESSION['errorOnAdd']);
			unset($_SESSION['errorOnAdd']);
		}
	}

	if(empty($services)) {
		$tpl->assign('SERVICE_PORTS', '');
		set_page_message(tr('You have no custom service ports defined.'));
	} else {
		sort($services);

		foreach($services as $index => $service) {
			$v = (count(explode(';', $values->$service)) < 6) ? $values->$service . ';' : $values->$service;
			list($port, $protocol, $name, $status, $custom, $ip) = explode(';', $v);

			$htmlSelected = $cfg->HTML_SELECTED;

			$selectedUdp = $protocol == 'udp' ? $htmlSelected : '';
			$selectedTcp = $protocol == 'udp' ? '' : $htmlSelected;
			$selectedOn = $status == '1' ? $htmlSelected : '';
			$selectedOff = $status == '1' ? '' : $htmlSelected;

			if ($custom == 0) {
				$tpl->assign(
					array(
						'SERVICE' => tohtml($name) . '<input name="name[]" type="hidden" id="name' . $index . '" value="' . tohtml($name) . '" />',
						'PORT_READONLY' => $cfg->HTML_READONLY,
						'PROTOCOL_READONLY' => $cfg->HTML_DISABLED,
						'TR_DELETE' => '-',
						'PORT_DELETE_LINK' => '',
						'NUM' => $index));

				$tpl->parse('PORT_DELETE_SHOW', '');
			} else {
				$tpl->assign(
					array(
						'SERVICE' => '<input name="name[]" type="text" id="name' . $index . '" value="' . tohtml($name) . '" class="textinput" maxlength="25" />',
						'NAME' => tohtml($name),
						'PORT_READONLY' => '',
						'PROTOCOL_READONLY' => '',
						'TR_DELETE' => tr('Delete'),
						'URL_DELETE' => "?delete=$service",
						'PORT_DELETE_SHOW' => '',
						'NUM' => $index));

				$tpl->parse('PORT_DELETE_LINK', 'port_delete_link');
			}

			$tpl->assign(
				array(
					'CUSTOM' => tohtml($custom),
					'VAR_NAME' => tohtml($service),
					'IP' => (($ip == '127.0.0.1') ? 'localhost' : (empty($ip) ? $cfg->BASE_SERVER_IP : tohtml($ip))),
					'PORT' => $port,
					'SELECTED_UDP' => $selectedUdp,
					'SELECTED_TCP' => $selectedTcp,
					'SELECTED_ON' => $selectedOn,
					'SELECTED_OFF' => $selectedOff));

			$tpl->parse('SERVICE_PORTS', '.service_ports');
		}

		// Add fields
		$tpl->assign(
			isset($errorOnAdd) ? array(
				'VAL_FOR_NAME_NEW' =>  $errorOnAdd['name_new'],
				'VAL_FOR_IP_NEW' => $errorOnAdd['ip_new'],
				'VAL_FOR_PORT_NEW' => $errorOnAdd['port_new']
			) : array(
				'VAL_FOR_NAME_NEW' => '',
				'VAL_FOR_IP_NEW' => '',
				'VAL_FOR_PORT_NEW' => ''));

		// Error fields ids
		$tpl->assign('ERROR_FIELDS_IDS', isset($_SESSION['errorFieldsIds']) ? $_SESSION['errorFieldsIds'] : '[]');

		unset($_SESSION['errorFieldsIds']);
	}
}

/**
 * Remove a service port from the database
 *
 * @param string $serviceName Service name
 * @return bool TRUE on success, FALSE otherwise
 */
function deleteService($serviceName)
{
	/** @var $dbConfig iMSCP_Config_Handler_Db */
	$dbConfig = iMSCP_Registry::get('dbConfig');

	if (!isset($dbConfig->$serviceName)) {
		set_page_message(tr("Unknown service name '$serviceName'."), 'error');
		return false;
	}

	$values = (count(explode(';', $dbConfig->$serviceName)) < 6) ? $dbConfig->$serviceName . ';' : $dbConfig->$serviceName;
	list(,,,,$custom) = explode(';', $values);

	if($custom == 1) {
		// Remove the service port from the database
		unset($dbConfig->$serviceName);
		write_log(get_session('user_logged') . ": Removed port for '$serviceName'.", E_USER_NOTICE);
		set_page_message(tr('Service port was successfully removed.'), 'success');
	} else {
		set_page_message(tr('You are not allowed to remove this service port entry.'), 'error');
		return false;
	}

	return true;
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

// Check for login
check_login(__FILE__);

/**
 * Dispatches the request
 */

// Adds a service port or updates one or more services ports
if (isset($_POST['uaction']) && $_POST['uaction'] != 'reset') {
	admin_addUpdateServices((clean_input($_POST['uaction'])));
// Deletes a service port
} elseif(isset($_GET['delete'])) {
	deleteService(clean_input($_GET['delete']));
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/settings_ports.tpl',
		'page_message' => 'layout',
		'service_ports' => 'page',
		'port_delete_link' => 'service_ports',
		'port_delete_show' => 'service_ports'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin / Settings / Service ports'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_ACTION' => tr('Action'),
		'TR_UDP' => tr('udp'),
		'TR_TCP' => tr('tcp'),
		'TR_ENABLED' => tr('Yes'),
		'TR_DISABLED' => tr('No'),
		'TR_SERVERPORTS' => tr('Server ports'),
		'TR_SERVICE' => tr('Service'),
		'TR_IP' => tr('IP'),
		'TR_PORT' => tr('Port'),
		'TR_PROTOCOL' => tr('Protocol'),
		'TR_SHOW' => tr('Show'),
		'TR_ACTION' => tr('Action'),
		'TR_DELETE' => tr('Delete'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s service port ?', true, '%s'),
		'TR_SHOW_UPDATE_SERVICE_PORT' => tr('View / Update service(s) port'),
		'TR_ADD_NEW_SERVICE_PORT' => tr('Add new service port'),
		'VAL_FOR_SUBMIT_ON_UPDATE' => tr('Update'),
		'VAL_FOR_SUBMIT_ON_ADD' => tr('Add'),
		'VAL_FOR_SUBMIT_ON_RESET' => tr('Reset')));

generateNavigation($tpl);
admin_showServices($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
