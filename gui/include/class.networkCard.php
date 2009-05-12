<?php
/* ispCP ω (OMEGA) a Virtual Hosting Control Panel
 * Copyright (c) 2006-2009 by isp Control Panel
 * http://isp-control.net
 *
 *
 * License:
 *	This program is free software;  you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by 
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You may have received a copy of theGNU General Public License
 *	along with this program; if not, write to the
 *	Free Software Foundation, Inc.,
 *	59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * The ispCP ω Home Page is at:
 *
 *	http://isp-control.net
 *
 * @author Daniel Andreca, sci2tech@gmail.com
 * @version $Id$
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

	function read($filename) {
		if (($result = @file_get_contents($filename)) === false) {
			$this->errors .= sprintf(tr("File %s does not exists or cannot be reached!"), $filename);
			return '';
		} else {
			return $result;
		}
	}

	function network() {
		$file = $this->read('/proc/net/dev');
		preg_match_all('/(.+):.+/', $file, $dev_name);
		return $dev_name[1];
	}

	protected function _getInterface() {
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

	protected function _populateInterfaces() {
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
