<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 *
 * @license
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
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
 * Prepare and put data in session on error(s)
 *
 * @since 1.0.7 (ispCP)
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param boolean TRUE on add, FALSE otherwise
 * @return void
 */
function toSession($mode) {

	// Get a reference to the array that contain all error fields ids
	$errorFieldsIds = &iMSCP_Registry::get('errorFieldsIds');

	// Create a json object that will be used by client browser for fields
	// highlighting
	$_SESSION['errorFieldsIds'] = json_encode($errorFieldsIds);

	// Data for error on add
	if($mode) {
		$values = array(
			'name_new' => $_POST['name_new'],
			'ip_new' => $_POST['ip_new'],
			'port_new' => $_POST['port_new'],
			'port_type_new' => $_POST['port_type_new'],
			'show_val_new' => $_POST['show_val_new']
		);

		$_SESSION['error_on_add'] = $values;

	// Data for error on update
	} else {
		foreach($_POST['var_name'] as $index => $service) {
			$port = $_POST['port'][$index];
			$protocol = $_POST['port_type'][$index];
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
 * Validates a service port and sets an appropriate message on error
 *
 * @since 1.0.7 (ispCP)
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param string $name Service port name
 * @param string $ip Ip address
 * @param int $port Service port
 * @param string $protocol Service port protocol
 * @param int $show
 * @param int $index Item index on update, empty value otherwise
 * @return TRUE if valid, FALSE otherwise
 */
function validatesService($name, $ip, $port, $protocol, $show, $index = '') {

	/**
	 * @var $dbConfig iMSCP_Config_Handler_Db
	 */
	$dbConfig = iMSCP_Registry::get('dbConfig');

	// Get a reference to the array that contain all errors messages
	$messages = &iMSCP_Registry::get('pageMessages');

	// Get a reference to the array that contain all error fields ids
	$errorFieldsIds = &iMSCP_Registry::get('errorFieldsIds');

	// Accounting for errors messages
	static $msgCount = 0;

	$dbServiceName = "PORT_$name";
	$ip = ($ip == 'localhost') ? '127.0.0.1' : $ip;

	// Check for service name syntax
	if (!is_basicString($name)) {
		$messages[] = tr("Error with '$name': Only letters, numbers, dash and underscore are allowed for services names!");
		$errorFieldsIds[] = "name$index";
	}

	// Check for IP syntax
	if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
		$messages[] = tr('Error: Wrong Ip number!');
		$errorFieldsIds[] = "ip$index";
	}

	// Check for port syntax
	if(!is_number($port) || $port <= 0) {
		$messages[] = tr('Error: Only positive numbers are allowed for services ports!');
		$errorFieldsIds[] = "port$index";
	}

	// Check for service port existences
	if(!is_int($index) && isset($dbConfig->$dbServiceName)) {
		$messages[] = tr('Error: Service port with same name already exists!');
		$errorFieldsIds[] = "name$index";
	}

	// Check for protocol
	if($protocol != 'tcp' && $protocol != 'udp') {
		$messages[] = tr('Error: Unallowed protocol!');
		$errorFieldsIds[] = "port_type$index";
	}

	// Check for show entry
	if($show != '0' && $show != '1') {
		$messages[] = tr('Error: Wrong value for show entry!');
		$errorFieldsIds[] = "show_val$index";
	}

	return ($msgCount = count($messages) != $msgCount) ? false : true;

}

/**
 * Adds or updates services ports
 *
 * @since 1.0.7 (ispCP)
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param boolean $mode TRUE on add, FALSE on update
 * @return void
 */
function addUpdateServices($mode) {

	/** @var $dbConfig iMSCP_Config_Handler_Db */
	$dbConfig = iMSCP_Registry::get('dbConfig');

	// Create a pool for messages on error and gets a reference to him
	$messages = &iMSCP_Registry::set('pageMessages', array());

	// Create a pool for error fields ids and gets a reference to him
	$errorFieldsIds = &iMSCP_Registry::set('errorFieldsIds', array());

	// Adds a service port
	if($mode) {
		$port = $_POST['port_new'];
		$protocol = $_POST['port_type_new'];
		$name = strtoupper($_POST['name_new']);
		$show = $_POST['show_val_new'];
		$ip = $_POST['ip_new'];

		if(validatesService($name, $ip, $port, $protocol, $show)) {
			$dbServiceName = "PORT_$name";

			// Add the service port in the database
			// See iMSCP_ConfigHandler_Db adapter class to learn how it work
			$dbConfig->$dbServiceName = "$port;$protocol;$name;$show;1;$ip";

			write_log(get_session('user_logged') . ": Added service port $name ($port)!", E_USER_NOTICE);
		}

	// Updates one or more services ports
	} else {
		// Reset counter of update queries
		$dbConfig->resetQueriesCounter('update');

		foreach($_POST['name'] as $index => $name) {

			$port = $_POST['port'][$index];
			$protocol = $_POST['port_type'][$index];
			$name = strtoupper($name);
			$show = $_POST['show_val'][$index];
			$custom = $_POST['custom'][$index];
			$ip = $_POST['ip'][$index];

			if(validatesService($name, $ip, $port, $protocol, $show, $index)) {
				$dbServiceName = $_POST['var_name'][$index];

				// Update the service port in the database
				// See iMSCP_ConfigHandler_Db adapter class to learn how it work
				$dbConfig->$dbServiceName = "$port;$protocol;$name;$show;$custom;$ip";
			}
		}
	}

	// Prepare data and messages for error page
	if(!empty($errorFieldsIds)) {
		toSession($mode);
		set_page_message(implode('<br />', array_unique($messages)), 'error');
	} elseif($mode) { // Prepares message for page on add
		set_page_message(tr('Service port was successfully added'), 'success');
	} else { // Prepares message for page on update

		// gets the number of queries that were been executed
		$updateCount = $dbConfig->countQueries('update');

		// An Update was been made in the database ?
		if($updateCount > 0) {
			set_page_message(tr('%d Service(s) port was successfully updated', $updateCount), 'success');
		} else {
			set_page_message(tr("Nothing's been changed"));
		}
	}
} // end add_update_services()

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
function showServices($tpl) {

	/**
	 * @var $cfg iMSCP_Config_Handler_File
	 */
	$cfg = iMSCP_Registry::get('config');

	// Gets the needed data

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

	// Prepares tpl

	if(empty($services)) {
		$tpl->assign('SERVICE_PORTS', '');
		set_page_message(tr('You have no custom service ports defined.'));
	} else {
		sort($services);

		foreach($services as $index => $service) {
			$v = (count(explode(';', $values->$service)) < 6) ? $values->$service . ';' : $values->$service;
			list($port, $protocol, $name, $status, $custom, $ip) = explode(';', $v);

			$selectedUdp = $protocol == 'udp' ? $cfg->HTML_SELECTED : '';
			$selectedTcp = $protocol == 'udp' ? '' : $cfg->HTML_SELECTED;
			$selectedOn = $status == '1' ? $cfg->HTML_SELECTED : '';
			$selectedOff = $status == '1' ? '' : $cfg->HTML_SELECTED;

			if ($custom == 0) {
				$tpl->assign(
					array(
						'SERVICE' => tohtml($name) . '<input name="name[]" type="hidden" id="name' . $index .
							'" value="' . tohtml($name) . '" />',
						'PORT_READONLY' => $cfg->HTML_READONLY,
						'PROTOCOL_READONLY' => $cfg->HTML_DISABLED,
						'TR_DELETE' => '-',
						'PORT_DELETE_LINK' => '',
						'NUM' => $index
					)
				);

				$tpl->parse('PORT_DELETE_SHOW', '');
			} else {

				$tpl->assign(
					array(
						'SERVICE' => '<input name="name[]" type="text" id="name' . $index . '" value="' . tohtml($name) .
							'" class="textinput" maxlength="25" />',
						'NAME' => tohtml($name),
						'PORT_READONLY' => '',
						'PROTOCOL_READONLY' => '',
						'TR_DELETE' => tr('Delete'),
						'URL_DELETE' => "?delete=$service",
						'PORT_DELETE_SHOW' => '',
						'NUM' => $index
					)
				);

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
					'SELECTED_OFF' => $selectedOff
				)
			);

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
				'VAL_FOR_PORT_NEW' => ''
			)
		);

		// Error fields ids
		$tpl->assign(
			array('ERROR_FIELDS_IDS' => isset($_SESSION['errorFieldsIds']) ? $_SESSION['errorFieldsIds'] : "[]")
		);

		unset($_SESSION['errorFieldsIds']);
	}
} // end show_services()

/**
 * Remove a service port from the database
 *
 * @param string $serviceName Service name
 * return void
 */
function deleteService($serviceName) {

	/**
	 * @var $dbConfig iMSCP_Config_Handler_Db
	 */
	$dbConfig = iMSCP_Registry::get('dbConfig');

	if (!isset($dbConfig->$serviceName)) {
		set_page_message(tr("Error: Unknown service name '$serviceName'!"), 'error');

		return;
	}

	$values = (count(explode(';', $dbConfig->$serviceName)) < 6)
		? $dbConfig->$serviceName . ';' : $dbConfig->$serviceName;

	list(,,,,$custom) = explode(';', $values);

	if($custom == 1) {
		// Remove the service port from the database
		// see iMSCP_ConfigHandler_Db adapter class to learn how it work
		unset($dbConfig->$serviceName);

		write_log(get_session('user_logged') . ": Removed port for '$serviceName'!", E_USER_NOTICE);

		set_page_message(tr('Service port was successfully removed!'), 'success');
	} else {
		set_page_message(tr('Error: You are not allowed to remove this service port entry!', 'error'));
	}
}

/***********************************************************************************************************************
 * Main script
 */

// Include all needed libraries
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

// Check for login
check_login(__FILE__);

/**
 * Dispatches the request
 */

// Adds a service port or updates one or more services ports
if (isset($_POST['uaction']) && $_POST['uaction'] != 'reset') {
	addUpdateServices(($_POST['uaction']) == 'add' ? true : false);
// Deletes a service port
} elseif(isset($_GET['delete'])) {
	deleteService(clean_input($_GET['delete']));
}

/**
 * @var $cfg iMSCP_Config_Handler_File
 */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/settings_ports.tpl');
$tpl->define_dynamic('service_ports', 'page');
$tpl->define_dynamic('port_delete_link', 'service_ports');
$tpl->define_dynamic('port_delete_show', 'service_ports');

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin / Settings'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()
	)
);

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');

// Statics page variables
$tpl->assign(
	array(
		'TR_ACTION' => tr('Action'),
		'TR_UDP' => tr('udp'),
		'TR_TCP' => tr('tcp'),
		'TR_ENABLED' => tr('Yes'),
		'TR_DISABLED' => tr('No'),
		'TR_APPLY_CHANGES' => tr('Apply changes'),
		'TR_SERVERPORTS' => tr('Server ports'),
		'TR_SERVICE' => tr('Service'),
		'TR_IP' => tr('IP'),
		'TR_PORT' => tr('Port'),
		'TR_PROTOCOL' => tr('Protocol'),
		'TR_SHOW' => tr('Show'),
		'TR_ACTION' => tr('Action'),
		'TR_DELETE' => tr('Delete'),
		'TR_MESSAGE_DELETE' =>
			tr('Are you sure you want to delete %s service port ?', true, '%s'),
		'TR_SHOW_UPDATE_SERVICE_PORT' => tr('View / Update service(s) port'),
		'TR_ADD_NEW_SERVICE_PORT' => tr('Add new service port'),
		'VAL_FOR_SUBMIT_ON_UPDATE' => tr('Update'),
		'VAL_FOR_SUBMIT_ON_ADD' => tr('Add'),
		'VAL_FOR_SUBMIT_ON_RESET' => tr('Reset')
	)
);

showServices($tpl);
generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
