<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
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
 * Class to store shared data (Better than global variables usage)
 *
 * Note: This class implement the Singleton design pattern
 *
 * @author: Laurent declercq (nuxwin) <laurent.declercq@ispcp.net>
 * @since: 1.0.6
 */
class IspCP_Registry extends ArrayObject {

	/**
	 * Instance of this class that provides storage for shared data
	 *
	 * @var IspCP_Registry
	 */
	private static $_instance = null;

	/**
	 * This class implement the Singleton design pattern
	 *
	 * Note: This class will be improved later. For the moment, we use a small
	 * workaround to implement the singleton design pattern because the access
	 * level for the constructor must be public (as in class ArrayObject).
	 *
	 * Dev note: I've used ArrayObject because I'm a lazy developer but not
	 * worry, I'll replace it by ArrayAccess interface (ASAP)
	 *
	 * @throws Exception
	 * @see getInstance()
	 */
	public function __construct() {

			if(!is_null(self::$_instance)) {
				throw new Exception(
					'This class implements the singleton design pattern. ' .
						'Use the getInstance() static method!'
				);
			}
	}

	/**
	 * This class implement the Singleton design pattern
	 */
	private function __clone() {}

	/**
	 * Get an IspCP_Registry instance
	 *
	 * Returns a reference to {@link IspCP_Registry} instance, only creating
	 * it if it doesn't already exist.
	 *
	 * @return object IspCP_Registry instance
	 */
	public static function getInstance() {

		if(!self::$_instance instanceof self) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Getter method to get data that is stored in the register
	 *
	 * @param string Data key name
	 * @throws Exception
	 * @return mixed Data
	 */
	public static function get($name) {

		$instance = self::getInstance();

		if (!$instance->offsetExists($name)) {
			throw new Exception(
				"Unable to retrieve data indexed by the `$name` name!"
			);
		}

		return $instance->offsetGet($name);
	}

	/**
	 * Setter method to register new data in the register
	 *
	 * @param string Data key name
	 * @param mixed $value Data
	 * @return mixed The value that was registered
	 */
	public static function set($name, $value) {

		$instance = self::getInstance();
		$instance->offsetSet($name, $value);

		return $value;
	}

	/**
	 * Check if data exists in the registry
	 *
	 * @param  string $name Data key name
	 * @return TRUE if the data exists, FALSE otherwise
	 */
	public static function exists($name) {

		if (self::$_instance instanceof self) {
			return false;
		}

		return self::$_instance->offsetExists($name);
	}

	/**
	 * Overrides {@link ArrayObject::offsetExists()} to fix know bug
	 * 
	 * @param string $name Data key name
	 * @return mixed
	 *
	 * Fix for http://bugs.php.net/bug.php?id=40442
	 */
	public function offsetExists($name) {

		return array_key_exists($name, $this);
	}
}
