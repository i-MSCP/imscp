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
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Config
 * @subpackage  Handler
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * This class provides an interface to manage easily a set of configuration
 * parameters from an array.
 *
 * This class implements the ArrayAccess and Iterator interfaces to improve
 * the access to the configuration parameters.
 *
 * With this class, you can access to your data like:
 *
 * - An array
 * - Via object properties
 * - Via setter and getter methods
 *
 * @package     iMSCP_Config
 * @subpackage  Handler
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_Config_Handler implements ArrayAccess
{
	/**
	 * Loads all configuration parameters from an array.
	 *
	 * @param array $parameters Configuration parameters
	 */
	public function __construct(array $parameters)
	{
		foreach ($parameters as $parameter => $value) {
			$this->$parameter = $value;
		}
	}

	/**
	 * Sets a configuration parameter.
	 *
	 * @param string $key Configuration parameter key name
	 * @param mixed $value Configuration parameter value
	 * @return void
	 */
	public function set($key, $value)
	{
		$this->$key = $value;
	}

	/**
	 * PHP overloading on inaccessible members.
	 *
	 * @param string $key Configuration parameter key name
	 * @return mixed Configuration parameter value
	 */
	public function __get($key)
	{
		return $this->get($key);
	}

	/**
	 * Getter method to retrieve a configuration parameter value.
	 *
	 * @throws iMSCP_Exception
	 * @param string $key Configuration parameter key name
	 * @return mixed Configuration parameter value
	 */
	public function get($key)
	{
		if (!$this->exists($key)) {
			throw new iMSCP_Exception("Configuration variable `$key` is missing.");
		}

		return $this->$key;
	}

	/**
	 * Deletes a configuration parameters.
	 *
	 * @param string $key Configuration parameter key name
	 * @return void
	 */
	public function del($key)
	{
		unset($this->$key);
	}

	/**
	 * Checks whether configuration parameters exists.
	 *
	 * @param string $key Configuration parameter key name
	 * @return boolean TRUE if configuration parameter exists, FALSE otherwise
	 * @todo Remove this method
	 */
	public function exists($key)
	{
		return property_exists($this, $key);
	}

	/**
	 * Replaces all parameters of this object with parameters from another.
	 *
	 * This method replace the parameters values of this object with the same values from another
	 * {@link iMSCP_Config_Handler} object.
	 *
	 * If a key from this object exists in the second object, its value will be replaced by the value from the second
	 * object. If the key exists in the second object, and not in the first, it will be created in the first object.
	 * All keys in this object that don't exist in the second object will be left untouched.
	 *
	 * <b>Note:</b> This method is not recursive.
	 *
	 * @param iMSCP_Config_Handler $config iMSCP_Config_Handler object
	 * @return bool TRUE on success, FALSE otherwise
	 */
	public function replaceWith(iMSCP_Config_Handler $config)
	{
		foreach ($config as $key => $value) {
			$this->set($key, $value);
		}

		return true;
	}

	/**
	 * Return an associative array that contains all configuration parameters.
	 *
	 * @return array Array that contains configuration parameters
	 */
	public function toArray()
	{
		$ref = new ReflectionObject($this);
		$properties = $ref->getProperties(ReflectionProperty::IS_PUBLIC);
		$array = array();

		foreach ($properties as $property) {
			$name = $property->name;
			$array[$name] = $this->$name;
		}

		return $array;
	}

	/**
	 * Assigns a value to the specified offset.
	 *
	 * @param mixed $offset The offset to assign the value to
	 * @param mixed $value The value to set.
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}

	/**
	 * Returns the value at specified offset.
	 *
	 * @param  mixed $offset The offset to retrieve
	 * @return mixed Offset value
	 */
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	/**
	 * Whether or not an offset exists.
	 *
	 * @param mixed $offset An offset to check for existence
	 * @return boolean TRUE on success or FALSE on failure
	 */
	public function offsetExists($offset)
	{
		return property_exists($this, $offset);
	}

	/**
	 * Unsets an offset.
	 *
	 * @param  mixed $offset The offset to unset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->$offset);
	}
}
