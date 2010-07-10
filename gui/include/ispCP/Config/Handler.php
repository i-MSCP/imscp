<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
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
 *
 * @category	ispCP
 * @package		ispCP_Config
 * @subpackage	Handler
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @version		SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
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
 * @package		ispCP_Config
 * @subpackage	Handler
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @since		1.0.6
 * @version		1.0.4
 */
class ispCP_Config_Handler implements ArrayAccess, Iterator {

	/**
	 * Array that contain all configuration parameters
	 *
	 * @var array
	 */
	protected $_parameters = array();

	/**
	 * Loads all configuration parameters from an array
	 *
	 * @param array $parameters Configuration parameters
 	 * @return void
	 */
	public function __construct(array $parameters) {

		$this->_parameters = $parameters;
	}

	/**
	 * Setter method to set a new configuration parameter
	 *
	 * @param string $index Configuration parameter key name
	 * @param mixed $value Configuration parameter value
	 * @return void
	 */
	public function set($index, $value) {

		$this->_parameters[$index] = $value;
	}

	/**
	 * Allow access as object properties
	 *
	 * @see set()
	 * @param string $name Configuration parameter key name
	 * @param mixed  $value Configuration parameter value
	 * @return void
	 */
	 public function __set($index, $value) {

		$this->set($index, $value);
	}

	/**
	 * Getter method to retrieve a configuration parameter value
	 *
	 * @param string $index Configuration parameter key name
	 * @throws ispCP_Exception
	 * @return mixed Configuration parameter value
	 */
	public function get($index) {

		if (!$this->exists($index)) {
			throw new ispCP_Exception("Error: Configuration variable `$index` is missing!");
		}

		return $this->_parameters[$index];
	}

	/**
	 * Allow access as object properties
	 *
	 * @see get();
	 * @param string Configuration parameter key name
	 * @return mixed Configuration parameter value
	 */
	public function __get($index) {

		return $this->get($index);
	}

	/**
	 * Methods to delete a configuration parameters
	 *
	 * @param $string $index Configuration parameter key name
	 * @return void
	 */
	public function del($index) {
		unset($this->_parameters[$index]);
	}

	/**
	 * PHP Overloading for call isset() on inaccessible members
	 *
	 * @param string Configuration parameter key name
	 * @return boolean TRUE if configuration parameter exists, FALSE otherwise
	 */	
	public function __isset($index) {

		return isset($this->_parameters[$index]);
	}

	/**
	 * PHP Overloading for call of unset() on inaccessible members
	 *
	 * @param string Configuration parameter key name
	 * @return void
	 */
	public function __unset($index) {

		$this->del($index);
	}

	/**
	 * Checks if a configuration parameters exists
	 *
	 * @param string $index Configuration parameter key name
	 * @return boolean TRUE if configuration parameter exists, FALSE otherwise
	 */
	public function exists($index) {
		
		return isset($this->_parameters[$index]);
	}

	/**
	 * Replaces all parameters of this object with parameters from another
	 *
	 * This method replace the parameters values of this object with the same
	 * values from another {@link ispCP_Config_Handler} object.
	 *
	 * If a key from this object exists in the second object, its value will be
	 * replaced by the value from the second object. If the key exists in the
	 * second object, and not in the first, it will be created in the first
	 * object. All keys in this object that don't exist in the second object
	 * will be left untouched.
	 *
	 * <b>Note:</b> This method is not recursive.
	 *
	 * @param ispCP_Config_Handler $config ispCP_Config_Handler object
	 * @return void
	 */
	public function replaceWith(ispCP_Config_Handler $config) {

		foreach($config as $index => $value) {
			$this->set($index, $value);
		}
	}

	/**
	 * Return an associative array that contain all configuration parameters
	 *
	 * @return array Array that contains configuration parameters
	 */
	public function toArray() {

		return $this->_parameters;
	}

	/**
	 * Defined by SPL Iterator interface
	 *
	 * See {@link http://www.php.net/~helly/php/ext/spl}
	 */
	public function current() {

		return current($this->_parameters);
	}

	/**
	 * Defined by SPL Iterator interface
	 *
	 * See {@link http://www.php.net/~helly/php/ext/spl}
	 */
	public function next() {

		next($this->_parameters);
	}

	/**
	 * Defined by SPL Iterator interface
	 *
	 * See {@link http://www.php.net/~helly/php/ext/spl}
	 */
	public function valid() {

		return array_key_exists(key($this->_parameters), $this->_parameters);
	}

	/**
	 * Defined by SPL Iterator interface
	 *
	 * See {@link http://www.php.net/~helly/php/ext/spl}
	 */
	public function rewind() {

		reset($this->_parameters);
        return $this;
	}

	/**
	 * Defined by SPL Iterator interface
	 *
	 * See {@link http://www.php.net/~helly/php/ext/spl}
	 */
	public function key() {

		return key($this->_parameters);
	}

	/**
	 * Defined by SPL ArrayAccess interface
	 *
	 * See {@link http://www.php.net/~helly/php/ext/spl}
	 */
	public function offsetExists($index) {

		return $this->exists($index);
	}

	/**
	 * Defined by SPL ArrayAccess interface
	 *
	 * See {@link http://www.php.net/~helly/php/ext/spl}
	 */
	public function offsetGet($index) {

		return $this->get($index);
	}

	/**
	 * Defined by SPL ArrayAccess interface
	 *
	 * See {@link http://www.php.net/~helly/php/ext/spl}
	 */
	public function offsetSet($index, $value) {

		$this->set($index, $value);
	}

	/**
	 * Defined by SPL ArrayAccess interface
	 *
	 * See {@link http://www.php.net/~helly/php/ext/spl}
	 */
	public function offsetUnset($index) {

		$this->del($index);
	}
}
