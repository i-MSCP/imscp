<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
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
 * Also, this class implements a helper method replace_with() that allow to
 * replace all parameters of object of this class with parameters from another
 * object of this class.
 *
 * @since 1.0.6
 * @version 1.0.2
 * @author Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 */
class IspCP_ConfigHandler implements ArrayAccess, Iterator {

	/**
	 * Array that contain all configuration parameters
	 *
	 * @var Array Configuration parameters
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
	 * @throws Exception
	 * @return Configuration parameter value
	 */
	public function get($index) {

		if (!$this->exists($index)) {
			throw new Exception("Configuration variable `$index` is missing!");
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
	 * PHP Overloading for call of isset() on inaccessible members.
	 *
	 * @param string Configuration parameter key name
	 * @return TRUE if the configuration parameter exists, FALSE otherwise
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
	 * @return TRUE if the configuration parameter exists, FALSE otherwise
	 */
	public function exists($index) {
		
		return isset($this->_parameters[$index]);
	}

	/**
	 * Replaces all parameters of this object with parameters from another
	 *
	 * This method replace the parameters values of this object with the same
	 * values from another {@link IspCP_ConfigHandler} object.
	 *
	 * If a key from this object exists in the second object, its value will be
	 * replaced by the value from the second object. If the key exists in the
	 * second object, and not in the first, it will be created in the first
	 * object. All keys in this object that don't exist in the second object
	 * will be left untouched.
	 *
	 * This method is not recursive.
	 *
	 * @param IspCP_ConfigHandler $config IspCP_ConfigHandler object
	 * @return void
	 */
	public function replaceWith(IspCP_ConfigHandler $config) {

		foreach($config as $index => $value) {
			$this->set($index, $value);
		}
	}

	/**
	 * Return an associative array that contain all configuration parameters
	 *
	 * @return array
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
