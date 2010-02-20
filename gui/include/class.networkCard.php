<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
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
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

class networkCard {

	protected $interfaces_info = array();
	protected $interfaces = array();
	protected $offline_interfaces = array();
	protected $virtual_interfaces = array();
	protected $available_interfaces = array();
	protected $errors = '';

	public function __construct() {
		$this->_getInterface();
		$this->_populateInterfaces();
	}

	public function read($filename) {
		if (($result = @file_get_contents($filename)) === false) {
			$this->errors .= sprintf(tr("File %s does not exists or cannot be reached!"), $filename);
			return '';
		} else {
			return $result;
		}
	}

	public function network() {
		$file = $this->read('/proc/net/dev');
		preg_match_all('/(.+):.+/', $file, $dev_name);
		return $dev_name[1];
	}

	private function _getInterface() {
		$interfaces_info = array();
		foreach ($this->network() as $key => $value) {
			$this->interfaces[] = trim($value);
		}
	}

	protected function executeExternal($strProgram, &$strError) {
		$strBuffer = '';

		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
		);
		$process = proc_open($strProgram, $descriptorspec, $pipes);
		if (is_resource($process)) {
			while (!feof($pipes[1])) {
				$strBuffer .= fgets($pipes[1], 1024);
			}
			fclose($pipes[1]);
			while (!feof($pipes[2])) {
				$strError .= fgets($pipes[2], 1024);
			}
			fclose($pipes[2]);
		}
		$return_value = proc_close($process);
		$strError = trim($strError);
		$strBuffer = trim($strBuffer);

		if (!empty($strError) || $return_value <> 0) {
			$strError .= "\nReturn value: " . $return_value;
			return false;
		}
		return $strBuffer;
	}

	private function _populateInterfaces() {
		$err = '';
		$message = $this->executeExternal(config::Get('CMD_IFCONFIG'), $err);

		if (!$message) {
			$this->errors .= tr("Error while trying to obtain list of network cards!") . $err;
			return false;
		}

		preg_match_all("/(?isU)([^ ]{1,}) {1,}.+(?:(?:\n\n)|$)/", $message, $this->interfaces_info);

		foreach ($this->interfaces_info[0] as $a) {
			if (preg_match("/inet addr\:([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/",$a,$b)) {
				$this->interfaces_info[2][] = trim($b[1]);
			} else {
				$this->interfaces_info[2][] = '';
			}
		}
		$this->offline_interfaces = array_diff($this->interfaces, $this->interfaces_info[1]);
		$this->virtual_interfaces = array_diff($this->interfaces_info[1], $this->interfaces);
		$this->available_interfaces = array_diff($this->interfaces, $this->offline_interfaces, $this->virtual_interfaces, array('lo'));
	}

	public function getAvailableInterface() {
		return $this->available_interfaces;
	}

	public function getErrors() {
		return nl2br($this->errors);
	}

	public function ip2NetworkCard($ip) {
		$key = array_search($ip,$this->interfaces_info[2]);
		if ($key === false) {
			$this->errors .= sprintf(tr("This IP (%s) is not assigned to any network card!"), $ip);
		} else {
			return $this->interfaces_info[1][$key];
		}
	}
}
