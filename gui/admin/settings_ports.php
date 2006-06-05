<?php
//   -------------------------------------------------------------------------------
//  |			 VHCS(tm) - Virtual Hosting Control System							|
//  |			  Copyright (c) 2001-2006 by moleSoftware							|
//  |			http://vhcs.net | http://www.molesoftware.com						|
//  |																				|
//  | This program is free software; you can redistribute it and/or					|
//  | modify it under the terms of the MPL General Public License					|
//  | as published by the Free Software Foundation; either version 1.1				|
//  | of the License, or (at your option) any later version.						|
//  |																				|
//  | You should have received a copy of the MPL Mozilla Public License				|
//  | along with this program; if not, write to the Open Source Initiative (OSI)	|
//  | http://opensource.org | osi@opensource.org									|
//  |																				|
//   -------------------------------------------------------------------------------

include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/settings_ports.tpl');

$tpl -> define_dynamic('service_ports', 'page');

$tpl -> define_dynamic('port_delete_link', 'service_ports');

$tpl -> define_dynamic('port_delete_show', 'service_ports');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
				array(
						'TR_ADMIN_SETTINGS_PAGE_TITLE' => tr('VHCS - Admin/Settings'),
						'THEME_COLOR_PATH' => "../themes/$theme_color",
						'THEME_CHARSET' => tr('encoding'),
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
						'VHCS_LICENSE' => $cfg['VHCS_LICENSE']
					 )
			  );


function update_services(&$sql) {

	if (isset($_POST['uaction']) && $_POST['uaction'] == "apply") {

		$count 			= count($_POST['name']);
		$break			= FALSE;
		$service_name	= $_POST['name'];
		$var_name		= $_POST['var_name'];
		$port			= $_POST['port'];
		$protocol	 	= $_POST['port_type'];
		$status 		= $_POST['show_val'];
		$custom			= $_POST['custom'];

		for ($j = 0; $j < $count; $j++) {
			if (!is_number($port[$j]) OR $port[$j] <= 0) {
				set_page_message(tr('ERROR: Only positive numbers are allowed !'));
				$break = TRUE;
				break;
			}
		}

		if (!$break) {
			for ($j = 0; $j < $count; $j++) {
					$var = $var_name[$j];
					$name = strtoupper(strip_tags($service_name[$j]));
					$value = implode(";", array($port[$j], $protocol[$j], $name, $status[$j], $custom[$j]));
					setConfig_Value($var, $value);
			}

			// Adding new Ports!
			if (isset($_POST['name_new']) AND !empty($_POST['name_new'])) {
				$port 		= $_POST['port_new'];
				$name		= strtoupper(strip_tags($_POST['name_new']));
				$protocol	= $_POST['port_type_new'];
				$status		= $_POST['show_val_new'];
				if (!is_number($port) OR $port <= 0) {
					set_page_message(tr('ERROR: Only positive numbers are allowed !'));
					break;
				}
				elseif (!is_basicString($name)) {
					set_page_message(tr('ERROR: Only Letters, Numbers, Dash and Underscore are allowed!'));
					break;
				}
				else {
					// Check if PORT exists
					$query = <<<SQL_QUERY
							SELECT
								COUNT(*)
							FROM
								config
							WHERE
								name = ?
SQL_QUERY;

					$rs = exec_query($sql, $query, array($name));
					if ($rs == 0) {
						$var = "PORT_".$name;
						$value = implode(";", array($port, $protocol, $name, $status, 1));
						setConfig_Value($var, $value);
						write_log($_SESSION['user_logged'].": add service port $name ({$port})!");
					}
					else {
						set_page_message(tr('ERROR: Port allready exists!'));
					}
				}
			}
			set_page_message(tr('Settings saved !'));
		}
	}
}

function delete_service($port_name) {
	global $sql, $cfg;

	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			config
		WHERE
			name = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($port_name));
	list($port, $protocol, $name, $status, $custom) = explode(";", $rs->fields['value']);

	if ($custom == 1) {
		$query = <<<SQL_QUERY
		DELETE FROM
			config
		WHERE
			name = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($port_name));
		write_log($_SESSION['user_logged'].": remove service port $port_name!");

		set_page_message('Service port was removed!');

	}
	else {
		set_page_message('ERROR: You are not allowed to remove Systemports!');
	}

	header( "Location: settings_ports.php" );
	exit();
}

function show_services(&$tpl, &$sql) {

	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			config
		WHERE
			name
		  LIKE
		  	'PORT_%'
		ORDER BY
			name ASC
SQL_QUERY;

	$rs = exec_query($sql, $query, array());

	$row = 1;

	while(!$rs->EOF){

		if ($row++ % 2 == 0) {
			$tpl->assign('CLASS', 'content');
		}
		else{
			$tpl->assign('CLASS', 'content2');
		}

		list($port, $protocol, $name, $status, $custom) = explode(";", $rs->fields['value']);

		if ($protocol == 'udp') {
			$selected_udp = "selected=\"selected\"";
			$selected_tcp = "";
		}
		else {
			$selected_udp = "";
			$selected_tcp = "selected=\"selected\"";
		}

		if ($status == '1') {
			$selected_on = "selected=\"selected\"";
			$selected_off = "";
		}
		else {
			$selected_on = "";
			$selected_off = "selected=\"selected\"";
		}

		if ($custom == 0) {
			$tpl -> assign(array('SERVICE' => $name."<input name=\"name[]\" type=\"hidden\" id=\"name\" value=\"".$name."\" />"));
			$tpl -> assign(
							array(
								'TR_DELETE' => tr('Delete'),
								'PORT_DELETE_LINK' => ''
							)
					);
			$tpl -> parse('PORT_DELETE_SHOW', 'port_delete_show');
		}
		else {
			$tpl -> assign(array('SERVICE' => "<input name=\"name[]\" type=\"text\" id=\"name\" value=\"".$name."\" class=\"textinput\" maxlength=\"25\" />"));
			$tpl -> assign(
							array(
								'TR_DELETE' => tr('Delete'),
								'URL_DELETE' => 'settings_ports.php?delete=PORT_'.$name,
								'PORT_DELETE_SHOW' => ''
							)
					);
			$tpl -> parse('PORT_DELETE_LINK', 'port_delete_link');
		}


		$tpl -> assign(
					array(
						'CUSTOM' => $custom,
						'VAR_NAME' => $rs->fields['name'],
						'PORT' => $port,
						'TR_UDP' => tr('udp'),
						'TR_TCP' => tr('tcp'),
						'TR_ENABLED' => tr('Enabled'),
						'TR_DISABLED' => tr('Disabled'),
						'SELECTED_UDP' => $selected_udp,
						'SELECTED_TCP' => $selected_tcp,
						'SELECTED_ON' => $selected_on,
						'SELECTED_OFF' => $selected_off,
						'TR_ACTION' => tr('Action'),
						)
				);

		$tpl->parse('SERVICE_PORTS', '.service_ports');

		$rs->MoveNext();
	}//while

}


// Fetch delete request
if (isset($_GET['delete'])) {
	delete_service($_GET['delete']);
}

/*
 *
 * static page messages.
 *
 */

update_services($sql);

gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_settings.tpl');

show_services($tpl, $sql);

$tpl -> assign(
				array(
					'TR_APPLY_CHANGES' => tr('Apply changes'),
					'TR_SERVERPORTS' => tr('Serverports'),
					'TR_SERVICES' => tr('Services'),
					'TR_SERVICE' => tr('Service'),
					'TR_PORT' => tr('Port'),
					'TR_PROTOCOL' => tr('Protocol'),
					'TR_SHOW' => tr('Show'),
					'TR_ACTION' => tr('Action'),
					'TR_DELETE' => tr('Delete'),
					'TR_ADD' => tr('Add'),
					'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete')
					)
			);


gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG']))
	dump_gui_debug();

unset_messages();
?>
