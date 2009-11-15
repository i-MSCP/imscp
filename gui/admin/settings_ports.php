<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 */

/**
 * @todo check/replace/remove dirty HTMLPurifier hack
 */

//dirty hack (disable HTMLPurifier until figure out how to let pass post arrays)
define('OVERRIDE_PURIFIER', null);

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/settings_ports.tpl');
$tpl->define_dynamic('service_ports', 'page');
$tpl->define_dynamic('port_delete_link', 'service_ports');
$tpl->define_dynamic('port_delete_show', 'service_ports');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_ADMIN_SETTINGS_PAGE_TITLE'	=> tr('ispCP - Admin/Settings'),
		'THEME_COLOR_PATH'				=> "../themes/$theme_color",
		'THEME_CHARSET'					=> tr('encoding'),
		'ISP_LOGO'						=> get_logo(get_session('user_id'))
	)
);

function update_services(&$sql) {
	if (isset($_POST['uaction']) && $_POST['uaction'] == "apply") {
		$count = count(get_post('name'));
		$break = false;
		$service_name = get_post('name');
		$var_name = get_post('var_name');
		$ip = get_post('ip');
		$port = get_post('port');
		$protocol = get_post('port_type');
		$status = get_post('show_val');
		$custom = get_post('custom');

		for ($j = 0; $j < $count; $j++) {
			if (!is_number($port[$j]) OR $port[$j] <= 0) {
				set_page_message(tr('ERROR: Only positive numbers are allowed !'));
				$break = true;
				break;
			}
		}

		if (!$break) {
			// Adding new Ports!
			if (isset($_POST['name_new']) && !empty($_POST['name_new'])) {
				$ip = get_post('ip_new');
				$port = get_post('port_new');
				$name = strtoupper(get_post('name_new'));
				$protocol = get_post('port_type_new');
				$status = get_post('show_val_new');
				if (!is_number($port) OR $port <= 0) {
					set_page_message(tr('ERROR: Only positive numbers are allowed !'));
					return;
				} elseif (!is_basicString($name)) {
					set_page_message(tr('ERROR: Only Letters, Numbers, Dash and Underscore are allowed!'));
					return;
				} elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)===false) {
					set_page_message(tr('Wrong IP number!'));
					return;
				} else {
					// Check if PORT exists
					$query = "
						SELECT
							`name`
						FROM
							`config`
						WHERE
							`name` = ?
					";
					$var = "PORT_" . $name;
					$rs = exec_query($sql, $query, array($var));
					if ($rs->RecordCount() == 0) {
						$value = implode(";", array($port, $protocol, $name, $status, 1, $ip));
						setConfig_Value($var, $value);
						write_log(get_session('user_logged') . ": add service port $name ({$port})!");
					} else {
						set_page_message(tr('ERROR: Port already exists!'));
						return;
					}
				}
			} else {
				for ($j = 0; $j < $count; $j++) {
					$var = $var_name[$j];
					$name = strtoupper(strip_tags($service_name[$j]));
					$value = implode(";", array($port[$j], $protocol[$j], $name, $status[$j], $custom[$j], $ip[$j]));
					setConfig_Value($var, $value);
				}
			}
			set_page_message(tr('Settings saved !'));
		}
	}
}

function delete_service($port_name) {
	$sql = Database::getInstance();

	if (!is_basicString($port_name)) {
		set_page_message(tr('ERROR: Only Letters, Numbers, Dash and Underscore are allowed!'));
		return;
	}

	$query = "
		SELECT
			*
		FROM
			`config`
		WHERE
			`name` = ?
	";

	$rs = exec_query($sql, $query, array($port_name));

	$value = (count(explode(";", $rs->fields['value'])) < 6)
		? $rs->fields['value'].';'
		: $rs->fields['value'];
	list($port, $protocol, $name, $status, $custom, $ip) = explode(";", $value);

	if ($custom == 1) {
		$query = "
			DELETE FROM
				`config`
			WHERE
				`name` = ?
		";

		$rs = exec_query($sql, $query, array($port_name));
		write_log(get_session('user_logged') . ": remove service port $port_name!");

		set_page_message('Service port was removed!');
	} else {
		set_page_message('ERROR: You are not allowed to remove this port entry!');
	}

	user_goto('settings_ports.php');
}

function show_services(&$tpl, &$sql) {
	$query = "
		SELECT
			*
		FROM
			`config`
		WHERE
			`name` LIKE 'PORT_%'
		ORDER BY
			`name` ASC
	";

	$rs = exec_query($sql, $query, array());

	$row = 1;

	if ($rs->RecordCount() == 0) {
		$tpl->assign('SERVICE_PORTS', '');

		set_page_message(tr('You have no custom service ports defined.'));
	} else {
		while (!$rs->EOF) {
			$tpl->assign('CLASS', ($row++ % 2 == 0) ? 'content' : 'content2');

			$value = (count(explode(";", $rs->fields['value'])) < 6)
				? $rs->fields['value'].';'
				: $rs->fields['value'];
			list($port, $protocol, $name, $status, $custom, $ip) = explode(";", $value);

			$selected_udp	= $protocol == 'udp' ? "selected=\"selected\"" : "";
			$selected_tcp	= $protocol == 'udp' ? "" : "selected=\"selected\"";

			$selected_on	= $status == '1' ? "selected=\"selected\"" : "";
			$selected_off	= $status == '1' ? "" : "selected=\"selected\"";

			if ($custom == 0) {
				$tpl->assign(array('SERVICE' => $name . "<input name=\"name[]\" type=\"hidden\" id=\"name" . $row . "\" value=\"" . $name . "\" />"));
				$tpl->assign(
					array(
						'PORT_READONLY'		=> 'readonly="readonly"',
						'PROTOCOL_READONLY'	=> 'disabled="disabled"',
						'TR_DELETE'			=> '-',
						'PORT_DELETE_LINK'	=> '',
						'NUM'				=> $row
					)
				);
				$tpl->parse('PORT_DELETE_SHOW', '');
			} else {
				$tpl->assign(array('SERVICE' => "<input name=\"name[]\" type=\"text\" id=\"name" . $row . "\" value=\"" . $name . "\" class=\"textinput\" maxlength=\"25\" />"));
				$tpl->assign(
					array(
						'NAME'				=> $name,
						'PORT_READONLY'		=> '',
						'PROTOCOL_READONLY'	=> '',
						'TR_DELETE'			=> tr('Delete'),
						'URL_DELETE'		=> 'settings_ports.php?delete=' . $rs->fields['name'],
						'PORT_DELETE_SHOW'	=> '',
						'NUM'				=> $row
					)
				);
				$tpl->parse('PORT_DELETE_LINK', 'port_delete_link');
			}

			$tpl->assign(
				array(
					'CUSTOM'		=> $custom,
					'VAR_NAME'		=> $rs->fields['name'],
					'IP'			=> (($ip == '127.0.0.1') ? 'localhost' : (empty($ip) ? Config::get('BASE_SERVER_IP') : $ip)),
					'PORT'			=> $port,
					'SELECTED_UDP'	=> $selected_udp,
					'SELECTED_TCP'	=> $selected_tcp,
					'SELECTED_ON'	=> $selected_on,
					'SELECTED_OFF'	=> $selected_off,
				)
			);

			$tpl->parse('SERVICE_PORTS', '.service_ports');

			$rs->MoveNext();
		} // end while
	} // end else
}
// Fetch delete request
if (isset($_GET['delete'])) {
	delete_service($_GET['delete']);
}

/**
 * static page messages.
 */

update_services($sql);

gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_settings.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_settings.tpl');

show_services($tpl, $sql);

$tpl->assign(
	array(
		'TR_ACTION'			=> tr('Action'),
		'TR_UDP'			=> tr('udp'),
		'TR_TCP'			=> tr('tcp'),
		'TR_ENABLED'		=> tr('Yes'),
		'TR_DISABLED'		=> tr('No'),
		'TR_APPLY_CHANGES'	=> tr('Apply changes'),
		'TR_SERVERPORTS'	=> tr('Server ports'),
		'TR_SERVICES'		=> tr('Services'),
		'TR_SERVICE'		=> tr('Service'),
		'TR_IP'				=> tr('IP'),
		'TR_PORT'			=> tr('Port'),
		'TR_PROTOCOL'		=> tr('Protocol'),
		'TR_SHOW'			=> tr('Show'),
		'TR_ACTION'			=> tr('Action'),
		'TR_DELETE'			=> tr('Delete'),
		'TR_ADD'			=> tr('Add'),
		'TR_MESSAGE_DELETE'	=> tr('Are you sure you want to delete %s?', true, '%s')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
