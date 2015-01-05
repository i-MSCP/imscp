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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Services
 * @copyright   2010-2015 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Class that allows to get services properties and their status
 */
class iMSCP_Services implements iterator, countable
{
	/**
	 * @var array[] Array of services where keys are service names and valus are arrays containing service properties
	 */
	private $services = array();

	/**
	 * @var string Service name currently queried
	 */
	private $queriedService = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$values = iMSCP_Registry::get('dbConfig')->toArray();

		// Gets list of services port names
		$services = array_filter(
			array_keys($values),
			function ($name) {
				return (strlen($name) > 5 && substr($name, 0, 5) == 'PORT_');
			}
		);

		foreach($services as $name) {
			$this->services[$name] = explode(';', $values[$name]);
		}

		ksort($this->services);
	}

	/**
	 * Set service to be queried
	 *
	 * @throws iMSCP_Exception
	 * @param  string $serviceName Service name
	 * @param  bool $normalize Tell whether or not the service name must be normalized
	 * @return void
	 */
	public function setService($serviceName, $normalize = true)
	{
		// Normalise service name (ex. 'dns' to 'PORT_DNS')
		if($normalize) {
			$serviceName = 'PORT_' . strtoupper($serviceName);
		}

		if(array_key_exists($serviceName, $this->services)) {
			$this->queriedService = $serviceName;
		} else {
			throw new iMSCP_Exception("Unknown Service: $serviceName");
		}
	}

	/**
	 * Get service listening port
	 *
	 * @return int
	 */
	public function getPort()
	{
		return $this->getProperty(0);
	}

	/**
	 * Get service protocol
	 *
	 * @return string
	 */
	public function getProtocol()
	{
		return $this->getProperty(1);
	}

	/**
	 * Get service name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->getProperty(2);
	}

	/**
	 * Check if the service is visible
	 *
	 * @return bool TRUE if the service is visible, FALSE otherwise
	 */
	public function isVisible()
	{
		return (bool)$this->getProperty(3);
	}

	/**
	 * Get service IP
	 *
	 * @return array
	 */
	public function getIp()
	{
		return $this->getProperty(4);
	}

	/**
	 * Check if a service is running
	 *
	 * @return bool return TRUE if the service is currently running, FALSE otherwise
	 */
	public function isRunning()
	{
		return $this->getStatus();
	}

	/**
	 * Check if a service is down
	 *
	 * @return bool return TRUE if the service is currently down, FALSE otherwise
	 */
	public function isDown()
	{
		return (!($this->getStatus()));
	}

	/**
	 * Returns the current element
	 *
	 * @return mixed Returns the current element
	 */
	public function current()
	{
		$this->setService($this->key(), false);

		return current($this->services);
	}

	/**
	 * Returns the key of the current element
	 *
	 * @return string Return the key of the current element or NULL on failure
	 */
	public function key()
	{
		return key($this->services);
	}

	/**
	 * Moves the current position to the next element
	 *
	 * @return void
	 */
	public function next()
	{
		next($this->services);
	}

	/**
	 * Rewinds back to the first element of the Iterator
	 *
	 * <b>Note:</b> This is the first method called when starting a foreach
	 * loop. It will not be executed after foreach loops.
	 *
	 * @return void
	 */
	public function rewind()
	{
		reset($this->services);
	}

	/**
	 * Checks if current position is valid
	 *
	 * @return boolean TRUE on success or FALSE on failure
	 */
	public function valid()
	{
		return array_key_exists(key($this->services), $this->services);
	}

	/**
	 * Count number of service
	 *
	 * @return int The custom count as an integer
	 */
	public function count()
	{
		return count($this->services);
	}

	/**
	 * Get a service property value
	 *
	 * @throws iMSCP_Exception
	 * @param int $index Service property index
	 * @return mixed Service property value
	 */
	private function getProperty($index)
	{
		if(!is_null($this->queriedService)) {
			return $this->services[$this->queriedService][$index];
		} else {
			throw new iMSCP_Exception('Name of service to query is not set');
		}
	}

	/**
	 * Get service status
	 *
	 * @return bool TRUE if the service is currently running, FALSE otherwise
	 */
	private function getStatus()
	{
		$ip = $this->getIp();

		if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$ip = '[' . $ip . ']';
		}

		if(($fp = @fsockopen($this->getProtocol() . '://' . $ip, $this->getPort(), $errno, $errstr, 0.5))) {
			fclose($fp);
			return true;
		}

		return false;
	}
}
