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
 * @author Laurent declercq (nuxwin) <laurent.declercq@ispcp.net>
 * @since 1.0.6
 * @version 1.0.1
 */
class IspCP_Registry {

	/**
	 * Instance of this class that provides storage for shared data
	 *
	 * @var IspCP_Registry
	 */
	protected static $_instance = null;

	/**
	 * This class implement the Singleton design pattern
	 *
	 * @return void
	 */
	private function __construct(){}

	/**
	 * This class implement the Singleton design pattern
	 *
	 * @return void
	 */
	private function __clone(){}

	/**
	 * Get an IspCP_Registry instance
	 *
	 * Returns a reference to {@link IspCP_Registry} instance, only creating
	 * it if it doesn't already exist.
	 *
	 * @return IspCP_Registry
	 */
	public static function getInstance() {

		if(self::$_instance == null) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Getter method to get data that is stored in the register
	 *
	 * Note: If you want get a reference to one data registered that is not an
	 * object, you should always use this method and not accessed it directly
	 * like an object member.
	 *
	 * To get an reference, use the following syntax:
	 *
	 * $data = &IspCP_Register::get('name');
	 *
	 * @param string $index Data key name
	 * @throws Exception
	 * @return mixed Data
	 */
	public static function &get($index) {

		$instance = self::getInstance();

		if (!isset($instance->$index)) {
			throw new Exception("Data `$index` is not registered!");
		}

		return $instance->$index;
	}

	/**
	 * Overloading on inaccessible members
	 *
	 * @param string $index Data key name
	 * @throws Exception
	 * @return void
	 */
	public function __get($index) {

		throw new Exception("Data `$index` is not registered!");
	}

	/**
	 * Setter method to register new data
	 *
	 * For conveniences reasons, this method return the data registered
	 *
	 * Note: This method can return a reference for data that are not objects
	 * like array. For this use the following syntax:
	 *
	 * $data = &IspCP_Register::set('name', array());
	 *
	 * @param string $index Data key name
	 * @param mixed $value Data value
	 * @return mixed
	 */
	public static function &set($index, $value) {

		$instance = self::getInstance();
		$instance->$index = $value;

		return $instance->$index;
	}

	/**
	 * Check if a data is registered
	 * 
	 * @param string $index Data key name
	 * @return boolean TRUE if the data is registered, FALSE otherwise
	 */
	public static function isRegistered($index) {

		return array_key_exists($index, self::getInstance());
	}
}
