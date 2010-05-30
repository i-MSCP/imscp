<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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
 * This class wraps the creation and manipulation of the ispCP_ConfigHandler
 * objects
 *
 * Important consideration:
 *
 * This class implement the Singleton design pattern, so, each type of
 * {@link IspCP_ConfigHandler} objects are instanciated only once.
 *
 * If you want use several instances of an IspCP_ConfigHandler object (e.g: To
 * handle separate configuration parameters that are stored in another container
 * such as a configuration file linked to a specific plugin) you should not use
 * this class. Instead of this, register your own IspCP_ConfigHandler objects
 * into the ispCP_Registry object to be able to use them from all contexts.
 *
 * Example:
 *
 * $parameters = array('PLUGIN_NAME' => 'billing', 'PLUGIN_VERSION' => '1.0.0');
 * IspCP_Registry::set('My_ConfigHandler', new IspCP_ConfigHandler($parameters));
 *
 * From another context:
 * 
 * $my_cfg = IspCP_Registry::get('My_ConfigHandler');
 * echo $my_cfg->PLUGIN_NAME; // billing
 * echo $my_cfg->PLUGIN_VERSION; // 1.0.0
 *
 * See {@link IspCP_Registry} for more information.
 *
 * To resume, the Config class acts as a registry for the IspCP_ConfigHandler
 * objects where the registered values (that are IspCP_ConfigHandler objects)
 * are indexed by they class name.
 */
final class Config {

	/**
	 * List of all the IspCP_ConfigHandler objects that this class can handle
	 */
	const
		ARR = 'IspCP_ConfigHandler',
		DB = 'IspCP_ConfigHandler_Db',
		FILE = 'IspCP_ConfigHandler_File',
		INI = false,
		XML = false,
		YAML = false;

	/**
	 * Array that contain references to {@link IspCP_ConfigHandler} objects
	 * indexed by they class name
	 *
	 * @staticvar array
	 */
	private static $_instances = array();

	/**
	 * Get a IspCP_ConfigHandler instance
	 *
	 * Returns a reference to a {@link IspCP_ConfigHandler} instance, only
	 * creating it if it doesn't already exist.
	 *
	 * The default handler object is set to {@link IspCP_ConfigHandler_File}
	 *
	 * @param string $classname IspCP_ConfigHandler class name
	 * @param mixed $params Parameters that are passed to IspCP_ConfigHandler
	 * 	object constructor
	 * @throws Exception
	 * @return IspCP_ConfigHandler
	 */
	public static function &getInstance($classname = self::FILE, $params = null) {

		if(!array_key_exists($classname, self::$_instances)) {

			if($classname === false) {
				throw new Exception(
					'The IspCP_ConfigHandler object you trying to use is not ' .
						'yet implemented!'
				);
			} elseif (!class_exists($classname, true)) {
				throw new Exception(
					"The class `$classname` is not reachable!"
				);
    		} elseif (!is_subclass_of($classname, 'IspCP_ConfigHandler')) {
				throw new Exception(
					'Only IspCP_ConfigHandler objects can be handling by the ' .
						__CLASS__ . ' class!'
				);
			}

			self::$_instances[$classname] = new $classname($params);
		}

		return self::$_instances[$classname];
	}

	/**
	 * Wrapper for getter method of an IspCP_ConfigHandler object
	 *
	 * @see IspCP_ConfigHandler::get()
	 * @param string $index Configuration parameter key name
	 * @param string $classname IspCP_ConfigHandler class name
	 * @return Configuration parameter value
	 */
	public static function get($index, $classname = self::FILE) {

		return self::getInstance($classname)->get($index);
	}

	/**
	 * Wrapper for setter method of an IspCP_ConfigHandler object
	 *
	 * @see IspCP_ConfigHandler::set()
	 * @param string $index Configuration parameter key name
	 * @param mixed $value Configuration parameter value
	 * @param string $classname IspCP_ConfigHandler class name
	 * @return void
	 */
	public static function set($index, $value, $classname = self::FILE) {

		self::getInstance($classname)->set($index, $value);
	}

	/**
	 * Wrapper for {@link IspCP_ConfigHandler::del()} method
	 *
	 * @see IspCP_ConfigHandler::del()
	 * @param string $index Configuration parameter key name
	 * @param string $classname IspCP_ConfigHandler class name
	 * @return void
	 */
	public static function del($index, $classname = self::FILE) {

		self::getInstance($classname)->del($index);
	}
}
