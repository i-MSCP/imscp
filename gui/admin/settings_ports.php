<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

// Include all needed libraries
require '../include/ispcp-lib.php';

// Check for login
check_login(__FILE__);

/*******************************************************************************
 * Functions
 */

/**
 * Gets and prepares the template part for services ports
 *
 * @param pTemplate &$tpl Reference to a pTemplate instance
 * @return void;
 */
function show_services(&$tpl) {

	$cfg = IspCP_Registry::get('Config');
	$db_cfg = IspCP_Registry::get('Db_Config');

	$filter = create_function('$v', 'if(substr($v,0,5) == "PORT_") return $v;');
	$services = array_filter(array_keys($db_cfg->toArray()), $filter);

	if(empty($services)) {
		$tpl->assign('SERVICE_PORTS', '');

		set_page_message(tr('You have no custom service ports defined.'));
	} else {
		sort($services);

		foreach($services as $i => $service) {

			$tpl->assign('CLASS', ($i % 2 == 0) ? 'content' : 'content2');

			$v = (count(explode(';', $db_cfg->$service)) < 6)
				? $db_cfg->$service . ';' : $db_cfg->$service;

			list($port, $proto, $name, $status, $custom, $ip) = explode(';', $v);

			$selected_udp = $proto == 'udp' ? $cfg->HTML_SELECTED : '';
			$selected_tcp = $proto == 'udp' ? '' : $cfg->HTML_SELECTED;

			$selected_on = $status == '1' ? $cfg->HTML_SELECTED : '';
			$selected_off = $status == '1' ? '' : $cfg->HTML_SELECTED;

			if ($custom == 0) {
				$tpl->assign(
					array(
						'SERVICE' => tohtml($name) .
							'<input name="name[]" type="hidden" id="name' .
							$i . '" value="' . tohtml($name) . '" />',

						'PORT_READONLY' => $cfg->HTML_READONLY,
						'PROTOCOL_READONLY' => $cfg->HTML_DISABLED,
						'TR_DELETE' => '-',
						'PORT_DELETE_LINK' => '',
						'NUM' => $i
					)
				);

				$tpl->parse('PORT_DELETE_SHOW', '');
			} else {

				$tpl->assign(
					array(
						'SERVICE' => 
							'<input name="name[]" type="text" id="name' .
								$i . '" value="' . tohtml($name) .
								'" class="textinput" maxlength="25" />',

						'NAME' => tohtml($name),
						'PORT_READONLY' => '',
						'PROTOCOL_READONLY' => '',
						'TR_DELETE' => tr('Delete'),
						'URL_DELETE' => "?delete=$service",
						'PORT_DELETE_SHOW' => '',
						'NUM' => $i
					)
				);

				$tpl->parse('PORT_DELETE_LINK', 'port_delete_link');
			}

			$tpl->assign(
				array(
					'CUSTOM' => tohtml($custom),
					'VAR_NAME' => tohtml($service),
					'IP' => (($ip == '127.0.0.1')
						? 'localhost'
						: (empty($ip) ? $cfg->BASE_SERVER_IP : tohtml($ip))),
					'PORT' => $port,
					'SELECTED_UDP' => $selected_udp,
					'SELECTED_TCP' => $selected_tcp,
					'SELECTED_ON' => $selected_on,
					'SELECTED_OFF' => $selected_off
				)
			);

			$tpl->parse('SERVICE_PORTS', '.service_ports');
		}
	}
} // end show_services()


/**
 * Validates a service port
 *
 * @since 1.0.6
 * @author Laurent declercq (nuxwin) <laurent.declercq@ispcp.net>
 * @param string $name Service port name
 * @param string $ip Ip address
 * @param int $port Service port
 * @param string $proto Service port protocol
 * @param int $show
 * @param boolean $on_updt True: validates for update
 */
function validates_service($name, $ip, $port, $proto, $show, $on_updt = false) {

	$db_cfg = IspCP_Registry::get('Db_Config');

	$db_sname = "PORT_$name";
	$ip = ($ip == 'localhost') ? '127.0.0.1' : $ip;

	if (!is_basicString($name)) {
		$e = tr('ERROR: Only letters, numbers, dash and underscore are allowed!');
	} elseif(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
		$e = tr('ERROR: Wrong Ip number!');
	} elseif(!is_number($port) || $port <= 0) {
		$e = tr('ERROR: Only positive numbers are allowed!');
	} elseif(isset($db_cfg->$db_sname) && !$on_updt) {
		$e = tr('ERROR: Service port already exists!');
	} elseif($proto != 'tcp' && $proto != 'udp') {
		$e = tr('ERROR: Unallowed protocol!');
	} elseif($show != '0' && $show != '1') {
		$e = tr('ERROR: Bad value for show entry!');
	} else {
		return true;
	}

	set_page_message($e);

	return false;
}

/**
 * Adds or updates a service port
 *
 * @return void
 */
function add_update_services() {

	$cfg = IspCP_Registry::get('Config');
	$db_cfg = IspCP_Registry::get('Db_Config');

	// Adds a service port
	if(isset($_POST['name_new']) && !empty($_POST['name_new'])) {

		$name = strtoupper($_POST['name_new']);
		$ip = $_POST['ip_new'];
		$port = $_POST['port_new'];
		$proto = $_POST['port_type_new'];
		$show = $_POST['show_val_new'];

		if(validates_service($name, $ip, $port, $proto, $show)) {
			$db_sname = "PORT_$name";

			// Add the service port in the database
			// See the {@link IspCP_ConfigHandler_Db} adapter class to learn
			// how it work
			$db_cfg->$db_sname = "$port;$proto;$name;$show;1;$ip";

			write_log(
					get_session('user_logged') .
						": Added service port $name ($port)!"
			);

			set_page_message(tr('Service port was added!'));
		} else {
			return;
		}

	// Updates one or more services ports
	} elseif(isset($_POST['name']) && !empty($_POST['name'])) {
		foreach($_POST['name'] as $index => $name) {

			$ip = $_POST['ip'][$index];
			$port = $_POST['port'][$index];
			$proto = $_POST['port_type'][$index];
			$show = $_POST['show_val'][$index];

			if(validates_service($name, $ip, $port, $proto, $show, true)) {
				$db_sname = "PORT_$name";

				// Update the service port in the database
				// See the {@link IspCP_ConfigHandler_Db} adapter class to learn
				// how it work
				$db_cfg->$db_sname = "$port;$proto;$name;$show;1;$ip";
			} else {
				return;
			}
		}

		set_page_message(tr('Service(s) port was updated !'));
	}
} // end add_update_services()

/**
 * Remove a service port from the database
 *
 * @param string service name
 * return void
 */
function delete_service($port_name) {

	$db_cfg = IspCP_Registry::get('Db_Config');

	if (!isset($db_cfg->$port_name)) {
		set_page_message(tr('ERROR: Unknown service port name!'));

		return;
	}

	$values = (count(explode(';', $db_cfg->$port_name)) < 6)
		? $db_cfg->$port_name . ';' : $db_cfg->$port_name;

	list(,,,,$custom,) = explode(';', $values);

	if($custom == 1) {
		// Remove the service from the database
		// see the {@link IspCP_ConfigHandler_Db} adapter class to learn how
		// it work
		unset($db_cfg->$port_name);

		write_log(
			get_session('user_logged') . ": Removed service port $port_name!"
		);

		set_page_message('Service port was removed!');
	} else {
		set_page_message(
			'ERROR: You are not allowed to remove this port entry!'
		);
	}
}


/*******************************************************************************
 * Main program
 */

/**
 * Dispatches the request
 */

// Adds a service port or updates one or more services ports
if (isset($_POST['uaction']) && $_POST['uaction'] == 'apply') {
	
	add_update_services();
	user_goto('settings_ports.php');

// Deletes a service port
} elseif(isset($_GET['delete'])) {

	delete_service($_GET['delete']);
	user_goto('settings_ports.php');

// Show all services ports
} else {

	$cfg = IspCP_Registry::get('Config');

	$tpl = new pTemplate();
	$tpl->define_dynamic(
		'page', $cfg->ADMIN_TEMPLATE_PATH . '/settings_ports.tpl'
	);
	$tpl->define_dynamic('service_ports', 'page');
	$tpl->define_dynamic('port_delete_link', 'service_ports');
	$tpl->define_dynamic('port_delete_show', 'service_ports');

	$tpl->assign(
		array(
			'TR_ADMIN_SETTINGS_PAGE_TITLE' => tr('ispCP - Admin/Settings'),
			'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => get_logo(get_session('user_id'))
		)
	);

	gen_admin_mainmenu(
		$tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl'
	);
	gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');

	show_services($tpl);

	$tpl->assign(
		array(
		'TR_ACTION' => tr('Action'),
		'TR_UDP' => tr('udp'),
		'TR_TCP' => tr('tcp'),
		'TR_ENABLED' => tr('Yes'),
		'TR_DISABLED' => tr('No'),
		'TR_APPLY_CHANGES' => tr('Apply changes'),
		'TR_SERVERPORTS' => tr('Server ports'),
		'TR_SERVICES' => tr('Services'),
		'TR_SERVICE' => tr('Service'),
		'TR_IP' => tr('IP'),
		'TR_PORT' => tr('Port'),
		'TR_PROTOCOL' => tr('Protocol'),
		'TR_SHOW' => tr('Show'),
		'TR_ACTION' => tr('Action'),
		'TR_DELETE' => tr('Delete'),
		'TR_ADD' => tr('Add'),
		'TR_MESSAGE_DELETE' =>
			tr('Are you sure you want to delete %s?', true, '%s')
		)
	);

	gen_page_message($tpl);

	$tpl->parse('PAGE', 'page');
	$tpl->prnt();
}

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
