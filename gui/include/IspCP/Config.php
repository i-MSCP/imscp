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
 * This class implement the Singleton design pattern, so, each
 * {@link ispCP_ConfigHandler} objects are instanciated only once.
 *
 * If you want use several instances of a ispCP_ConfigHandler object (e.g: To
 * handle separate configuration parameters that are stored in another container
 * such as a configuration file linked to a specific plugin) you should not use
 * this class.
 *
 * To resume, this class acts as a registry for the ispCP_ConfigHandler objects
 * where the registered values (that are ispCP_ConfigHandler objects) are
 * indexed by they class name.
 */
final class Config {

	/**
	 * List of all the spCP_ConfigHandler object that this class can/will handle
	 */
	const
		DB = 'IspCP_ConfigHandler_Db',
		FILE = 'IspCP_ConfigHandler_File',
		INI = false,
		XML = false,
		YAML = false;

	/**
	 * Array that contain references to {@link ispCP_ConfigHandler} objects
	 * indexed by they class name
	 *
	 * @staticvar array
	 */
	private static $_instances = array();

	/**
	 * Get a Config_Handler object
	 *
	 * Returns a reference to a {@link IspCP_ConfigHandler} object, only
	 * creating it if it doesn't already exist.
	 *
	 * The default handler object is set to {@link IspCP_ConfigHandler_File}
	 *
	 * @param string $type Type of IspCP_ConfigHandler object that should be
	 *	returned
	 * @param mixed $params Parameters that are passed to the
	 *	IspCP_ConfigHandler object constructor
	 * @throws Exception
	 * @return IspCP_ConfigHandler
	 */
	public static function &getInstance($type = self::FILE, $params = null) {

		if(!array_key_exists($type, self::$_instances)) {

			if($type === false) {
				throw new Exception(
					'The IspCP_ConfigHandler object you trying to use is not ' .
						'yet implemented!'
				);
			} elseif (!class_exists($type, true)) {
				throw new Exception(
					"The class `$type` is not reachable!"
				);
    		} elseif (!is_subclass_of($type, 'IspCP_ConfigHandler')) {
				throw new Exception(
					'Only IspCP_ConfigHandler objects can be handling by the ' .
						__CLASS__ . ' class!'
				);
			}

			self::$_instances[$type] = new $type($params);
		}

		return self::$_instances[$type];
	}

	/**
	 * Wrapper for getter method of a IspCP_ConfigHandler object
	 *
	 * @static
	 * @param string $index Configuration parameter key name
	 * @return Configuration parameter value
	 */
	public static function get($index, $type = self::FILE) {
		return self::getInstance($type)->get($index);
	}

	/**
	 * Wrapper for setter method of a IspCP_ConfigHandler object
	 *
	 * @static
	 * @param string $index Configuration parameter key name
	 * @param mixed $value Configuration parameter value
	 * @return void
	 */
	public static function set($index, $value, $type = self::FILE) {
		self::getInstance($type)->set($index, $value);
	}
}
