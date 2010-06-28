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
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @author		ispCP Team
 * @version		SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 * @filesource
 */

/**
 * This class wraps the creation and manipulation of the ispCP_Config_Handler
 * objects
 *
 * Important consideration:
 *
 * This class implement the Singleton design pattern, so, each type of
 * {@link ispCP_Config_Handler} objects are instanciated only once.
 *
 * If you want use several instances of an ispCP_Config_Handler object (e.g: To
 * handle separate configuration parameters that are stored in another container
 * such as a configuration file linked to a specific plugin) you should not use
 * this class. Instead of this, register your own ispCP_Config_Handler objects
 * into the ispCP_Registry object to be able to use them from all contexts.
 *
 * Example:
 *
 * $parameters = array('PLUGIN_NAME' => 'billing', 'PLUGIN_VERSION' => '1.0.0');
 * ispCP_Registry::set('My_ConfigHandler', new ispCP_Config_Handler($parameters));
 *
 * From another context:
 * 
 * $my_cfg = ispCP_Registry::get('My_ConfigHandler');
 * echo $my_cfg->PLUGIN_NAME; // billing
 * echo $my_cfg->PLUGIN_VERSION; // 1.0.0
 *
 * See {@link ispCP_Registry} for more information.
 *
 * To resume, the Config class acts as a registry for the ispCP_Config_Handler
 * objects where the registered values (that are ispCP_Config_Handler objects)
 * are indexed by they class name.
 *
 * @category	ispCP
 * @package		ispCP_Config
 * @author		ispCP Team
 * @version		1.0.7
 */
class Config {

	/**#@+
 	 * ispCP_Config_Handler class name
 	 */
	const
		ARR = 'ispCP_Config_Handler',
		DB = 'ispCP_Config_Handler_Db',
		FILE = 'ispCP_Config_Handler_File',
		INI = false,
		XML = false,
		YAML = false;
	/**#@-*/

	/**
	 * Array that contain references to {@link ispCP_Config_Handler} objects
	 * indexed by they class name
	 *
	 * @staticvar array
	 */
	private static $_instances = array();

	/**
	 * Get a ispCP_Config_Handler instance
	 *
	 * Returns a reference to a {@link ispCP_Config_Handler} instance, only
	 * creating it if it doesn't already exist.
	 *
	 * The default handler object is set to {@link ispCP_Config_Handler_File}
	 *
	 * @throws ispCP_Exception
	 * @param string $className ispCP_Config_Handler class name
	 * @param mixed $params Parameters that are passed to ispCP_Config_Handler
	 * 	object constructor
	 * @return ispCP_Config_Handler An ispCP_Config_Handler instance
	 */
	public static function getInstance($className = self::FILE, $params = null) {

		if(!array_key_exists($className, self::$_instances)) {

			if($className === false) {
				throw new ispCP_Exception(
					'Error: The ispCP_Config_Handler object you trying to create is not ' .
						'yet implemented!'
				);
			} elseif (!class_exists($className, true)) {
				throw new ispCP_Exception(
					"Error: The class $className is not reachable!"
				);
    		} elseif (!is_subclass_of($className, 'ispCP_Config_Handler')) {
				throw new ispCP_Exception(
					'Error: Only ispCP_Config_Handler objects can be handling by the ' .
						__CLASS__ . ' class!'
				);
			}

			self::$_instances[$className] = new $className($params);
		}

		return self::$_instances[$className];
	}

	/**
	 * Wrapper for getter method of an ispCP_Config_Handler object
	 *
	 * @see ispCP_Config_Handler::get()
	 * @param string $index Configuration parameter key name
	 * @param string $className ispCP_Config_Handler class name
	 * @return Configuration parameter value
	 */
	public static function get($index, $className = self::FILE) {

		return self::getInstance($className)->get($index);
	}

	/**
	 * Wrapper for setter method of an ispCP_Config_Handler object
	 *
	 * @see ispCP_Config_Handler::set()
	 * @param string $index Configuration parameter key name
	 * @param mixed $value Configuration parameter value
	 * @param string $className ispCP_Config_Handler class name
	 * @return void
	 */
	public static function set($index, $value, $className = self::FILE) {

		self::getInstance($className)->set($index, $value);
	}

	/**
	 * Wrapper for {@link ispCP_Config_Handler::del()} method
	 *
	 * @see ispCP_Config_Handler::del()
	 * @param string $index Configuration parameter key name
	 * @param string $className ispCP_Config_Handler class name
	 * @return void
	 */
	public static function del($index, $className = self::FILE) {

		self::getInstance($className)->del($index);
	}
}
