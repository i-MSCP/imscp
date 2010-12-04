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
 * The Original Code is "i-MSCP - Multi Server Control panel".
 *
 * The Initial Developer of the Original Code is i-MSCP Team.
 * Portions created by the i-MSCP Team are Copyright (C) 2006-2010 by
 * internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package     iMSCP_Services
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Class that allows to get services properties and their status
 *
 * @category	ispCP
 * @package     iMSCP_Initializer
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @author		Laurent Declercq <laurent.declercq@i-mscp.net>
 * @Since		i-MSCP 1.0.0
 * @version		1.0.0
 */
class iMSCP_Services implements iterator {

	/**
	 * Array of services where each key is a service name and each associated value is
	 * an array that contain all properties
	 *
	 * @var array Services
	 */
	private $_services = array();

	/**
	 * Service name currently queried
	 *
	 * @var string
	 */
	private $_queriedService = null;

	/**
	 * Constructor
	 * 
	 * @return void
	 */
	public function __construct() {

		/**
		 * @var $cfg iMSCP_Config_Handler_File
		 */
		$cfg = iMSCP_Registry::get('Config');

		/**
		 * @var $dbConfig iMSCP_Config_Handler_Db
		 */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		// Retrieve all services
		foreach($dbConfig as $service => $serviceProperties) {

			if(substr($service, 0, 5) == 'PORT_') {
				$this->_services[$service] = explode(';', $serviceProperties);

				if($this->_services[$service][5] == '') {
					$this->_services[$service][5] = $cfg->BASE_SERVER_IP;
				} elseif($this->_services[$service][5] == '127.0.0.1') {
					$this->_services[$service][5] = 'localhost';
				}
			}
		}

		ksort($this->_services);
	}

	/**
	 * Get a service property value
	 *
	 * @throws iMSCP_Exception
	 * @param  string $serviceName Service name
	 * @param  int $index Service property index
	 * @return mixed Service property value
	 */
	private function _getProperty($serviceName, $index) {

		if(!is_null($this->_queriedService)) {
			return $this->_services[$this->_queriedService][$index];
		} else {
			throw new iMSCP_Exception('Service name to be queried is not set!');
		}
	}

	/**
	 * Get service status
	 *
	 * @param  $serviceName
	 * @return bool TRUE if the service is currently running, FALSE otherwise
	 */
	private function _getStatus() {

		ini_set('default_socket_timeout', 3);

		if(($fp = @fsockopen($this->getProtocol() . '://' . $this->getIp(), $this->getPort()))) {
			fclose($fp);

			return true;
		}

		return false;
	}

	/**
	 * Set service to be queried
	 * 
	 * @throws iMSCP_Exception
	 * @param  string $serviceName Service name
	 * @param  bool $normalize Tell whether or not the service name must be normalized
	 * @return void
	 */
	public function setService($serviceName, $normalize = true) {

		// Normalise service name
		if($normalize) {
			$normalizedServiceName = 'PORT_' . strtoupper($serviceName);
		} else {
			$normalizedServiceName = $serviceName;
		}

		if(array_key_exists($normalizedServiceName, $this->_services)) {
			$this->_queriedService = $normalizedServiceName;
		} else {
			throw new iMSCP_Exception("Unknown Service '$serviceName'!");
		}
	}

	/**
	 * Get service port
	 *
	 * @param  string $serviceName Service name
	 * @return array
	 */
	public function getPort($serviceName = null) {

		if(!is_null($serviceName)) {
			$this->setService($serviceName);
		}

		return $this->_getProperty($this->_queriedService, 0);
	}

	/**
	 * Get service protocol
	 *
	 * @param  string $serviceName Service name
	 * @return array
	 */
	public function getProtocol($serviceName = null) {

		if(!is_null($serviceName)) {
			$this->setService($serviceName);
		}

		return $this->_getProperty($this->_queriedService, 1);
	}

	/**
	 * Get service name
	 * 
	 * @param  $serviceName
	 * @return mixed
	 */
	public function getName($serviceName = null) {

		if(!is_null($serviceName)) {
			$this->setService($serviceName);
		}

		return $this->_getProperty($this->_queriedService, 2);
	}

	/**
	 * Get service type
	 *
	 * @param  string $serviceName Service name
	 * @return array
	 */
	public function getType($serviceName = null) {

		if(!is_null($serviceName)) {
			$this->setService($serviceName);
		}

		return $this->_getProperty($this->_queriedService, 4);
	}

	/**
	 * Get service IP
	 *
	 * @param  string $serviceName Service name
	 * @return array
	 */
	public function getIp($serviceName = null) {

		if(!is_null($serviceName)) {
			$this->setService($serviceName);
		}

		return $this->_getProperty($this->_queriedService, 5);
	}

	/**
	 * Check if a service is running
	 *
	 * @param  $serviceName Service name
	 * @return bool return TRUE if the service is currently running, FALSE otherwise
	 */
	public function isRunning($serviceName = null) {

		return $this->_getStatus();
	}

	/**
	 * Check if a service is down
	 *
	 * @param  $serviceName Service name
	 * @return bool return TRUE if the service is currently down, FALSE otherwise
	 */
	public function isDown($serviceName = null) {

		return (!($this->_getStatus()));
	}

	/**
	 * Returns the current element
	 *
	 * @return mixed Returns the current element
	 */
	public function current() {

		return current($this->_services);
	}

	/**
	 * Returns the key of the current element
	 *
	 * Note: For convenience reason, this method also set the current service to be queried. That allows this
	 * construction:
	 * 
	 * <code>
	 * $services = new iMSCP_ServicesStatus();
	 *
	 * foreach($services as $serviceName => properties) {
	 * 	echo $services->getPort();
	 *  echo $services->getIp();
	 * }
	 * <code>
	 *
	 * @return string Return the key of the current element or NULL on failure
	 */
	public function key() {

		$key = key($this->_services);
		$this->setService($key, false);

		return $key;
	}

	/**
	 * Moves the current position to the next element
	 *
	 * @return void
	 */
	public function next() {

		next($this->_services);
	}

	/**
	 * Rewinds back to the first element of the Iterator
	 *
	 * <b>Note:</b> This is the first method called when starting a foreach
	 * loop. It will not be executed after foreach loops.
	 *
	 * @return void
	 */
	public function rewind() {

		reset($this->_services);
	}

	/**
	 * Checks if current position is valid
	 *
	 * @return boolean TRUE on success or FALSE on failure
	 */
	public function valid() {

		return array_key_exists(key($this->_services), $this->_services);
	}
}
