<?php

class networkCard {

	protected $interfaces_info = array();
	protected $interfaces = array();
	protected $offline_interfaces = array();
	protected $virtual_interfaces = array();
	protected $available_interfaces = array();
	protected $errors = '';

	public function __construct() {
		define('IN_PHPSYSINFO', true);
		require_once('phpsysinfo/class.error.inc.php');
		require_once('phpsysinfo/common_functions.php');
		require_once('phpsysinfo/class.' . PHP_OS . '.inc.php');
		$this->sysinfo = new sysinfo;
		$this->sysinfoerror = new error;

		$this->_getInterface();
		$this->_populateInterfaces();
	}

	protected function _getInterface() {
		foreach ($this->sysinfo->network() as $key => $value) {
			$interfaces_info[trim($key)]=$value;
		}
		$this->interfaces = array_keys($interfaces_info);
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
			$this->errors .= tr("Error while trying to obtain list of network card!\n") . $err;
			return false;
		}

		preg_match_all("/(?isU)([^ ]{1,}) {1,}.+(?:(?:\n\n)|$)/",$message,$this->interfaces_info);
		
		foreach ($this->interfaces_info[0] as $a) {
			if (preg_match("/inet addr\:([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/",$a,$b)) {
				$this->interfaces_info[2][] = trim($b[1]);
			} else {
				$this->interfaces_info[2][] = '';
			}
		}
		$this->offline_interfaces=array_diff($this->interfaces, $this->interfaces_info[1]);
		$this->virtual_interfaces=array_diff($this->interfaces_info[1], $this->interfaces);
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
			$this->errors .= tr("This ip ({$ip}) is not assignet to any network card!\n");
		} else {
			return $this->interfaces_info[1][$key];
		}
	}
}
