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

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::getInstance()->get('ADMIN_TEMPLATE_PATH') . '/server_status.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('service_status', 'page');

$theme_color = Config::getInstance()->get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_ADMIN_SERVER_STATUS_PAGE_TITLE' => tr('ispCP Admin / System Tools / Server Status'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

/*
 * Site functions
 */

/**
 * @todo respect naming convention: getSth not GetSth and class Status not status
 */
class status {
	var $all = array();
	var $log = false;

	/**
	 * AddService adds a service to a multi-dimensional array
	 */
	function AddService($ip, $port, $service, $type) {
		$small_array = array('ip' => $ip, 'port' => $port, 'service' => $service, 'type' => $type, 'status' => '');
		array_push($this->all, $small_array);
		return $this->all;
	}

	/**
	 * GetCount returns the number of services added
	 */
	function GetCount() {
		return count($this->all);
	}

	/**
	 * CheckStatus checks the status
	 */
	function CheckStatus($timeout = 5) {
		for ($i = 0, $x = $this->GetCount() - 1; $i <= $x; $i++) {
			$ip = $this->all[$i]['ip'];
			$port = $this->all[$i]['port'];
			$errno = null;
			$errstr = null;

			if ($this->all[$i]['type'] == 'tcp') {
				$fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
			}
			else if ($this->all[$i]['type'] == 'udp') {
				$fp = @fsockopen('udp://' . $ip, $port, $errno, $errstr, $timeout);
			}
			else {
				write_log(sprintf('FIXME: %s:%d' . "\n" . 'Unknown connection type %s',__FILE__, __LINE__, $this->all[$i]['type']));
				die('FIXME: ' . __FILE__ . ':' . __LINE__);
			}

			if ($fp) {
				$this->all[$i]['status'] = true;
				if ($this->log) {
					$this->AddLog($this->all[$i]['ip'], $this->all[$i]['port'], $this->all[$i]['service'], $this->all[$i]['type'], 'TRUE');
					// $this->StatusUp(mysql_insert_id());
				}
			}
			else {
				$this->all[$i]['status'] = false;
				if ($this->log) {
					$this->AddLog($this->all[$i]['ip'], $this->all[$i]['port'], $this->all[$i]['service'], $this->all[$i]['type'], 'FALSE');
					// $this->StatusDown(mysql_insert_id());
				}
			}

			if ($fp)
				fclose($fp);
		}
	}

	/**
	 * GetStatus a unecessary function to return the status
	 */
	function GetStatus() {
		return $this->all;
	}

	/**
	 * GetSingleStatus will get the status of single address
	 */
	function GetSingleStatus($ip, $port, $type, $timeout = 5) {
		$errno = null;
		$errstr = null;
		if ($type == 'tcp') {
			$fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
		}
		else if ($type == 'udp') {
			$fp = @fsockopen('udp://' . $ip, $port, $errno, $errstr, $timeout);
		}
		else {
			write_log(sprintf('FIXME: %s:%d' . "\n" . 'Unknown connection type %s',__FILE__, __LINE__, $type));
			die('FIXME: ' . __FILE__ . ':' . __LINE__);
		}

		if (!$fp)
			return false;

		fclose($fp);
		return true;
	}
}

function get_server_status(&$tpl, &$sql) {
	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`config`
		WHERE
			`name` LIKE 'PORT_%'
		ORDER BY
			`name` ASC
SQL_QUERY;

	$rs = exec_query($sql, $query, array());

	$ispcp_status = new status;

	// Enable logging?
	$ispcp_status->log = false; // Default is false
	$ispcp_status->AddService('localhost', 9876, 'ispCP Daemon', 'tcp');

	// Dynamic added Ports
	while (!$rs->EOF) {
		$value = (count(explode(";", $rs->fields['value'])) < 6)
			? $rs->fields['value'].';'
			: $rs->fields['value'];
		list($port, $protocol, $name, $status, $custom, $ip) = explode(";", $value);
		if ($status) {
			$ispcp_status->AddService(($ip == '127.0.0.1' ? 'localhost' : (empty($ip) ? Config::getInstance()->get('BASE_SERVER_IP') : $ip)), (int)$port, $name, $protocol);
		}

		$rs->MoveNext();
	} // end while

	$ispcp_status->CheckStatus(5);
	$data = $ispcp_status->GetStatus();
	$up = tr('UP');
	$down = tr('DOWN');

	for ($i = 0, $cnt_data = count($data); $i < $cnt_data; $i++) {
		if ($data[$i]['status']) {
			$img = $up;
			$class = "content up";
		} else {
			$img = '<b>' . $down . '</b>';
			$class = "content down";
		}

		if ($data[$i]['port'] == 23) { // 23 = telnet
			if ($data[$i]['status']) {
				$class = 'content2 down';
				$img = '<b>' . $up . '</b>';
			} else {
				$class = 'content2 up';
				$img = $down;
			}
		}

		$tpl->assign(
			array(
				'HOST' => $data[$i]['ip'],
				'PORT' => $data[$i]['port'],
				'SERVICE' => $data[$i]['service'],
				'STATUS' => $img,
				'CLASS' => $class,
			)
		);

		$tpl->parse('SERVICE_STATUS', '.service_status');
	}
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, Config::getInstance()->get('ADMIN_TEMPLATE_PATH') . '/main_menu_general_information.tpl');
gen_admin_menu($tpl, Config::getInstance()->get('ADMIN_TEMPLATE_PATH') . '/menu_general_information.tpl');

$tpl->assign(
	array(
		'TR_HOST' => tr('Host'),
		'TR_SERVICE' => tr('Service'),
		'TR_STATUS' => tr('Status'),
		'TR_SERVER_STATUS' => tr('Server status'),
	)
);

get_server_status($tpl, $sql);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::getInstance()->get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
