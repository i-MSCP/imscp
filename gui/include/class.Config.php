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
 * Interface for a Configuration Handler objects
 *
 * This interface describes all the methods that a ispCP_ConfigHandler objects
 * must implement.
 *
 * Note: All ispCP_ConfigHandler must implement the Singleton design pattern
 *
 * @Since 1.0.6
 * @author Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 */
interface ispCP_ConfigHandler {

	/**
	 * Get a Config_Handler object
     *
     * Returns a reference to a Config_Handler object, only creating it
	 * if it doesn't already exist.
	 *
	 * @param mixed $params for the ispCP_ConfigHandler object
	 * @return ispCP_ConfigHandler
	 */
	public static function getInstance($params);

	/**
	 * Setter method to register a new configuration value
	 *
	 * @param $index Key name of the parameter to be registered
	 * @param mixed $value Parameter value
	 * @return void
	 */
	public function set($index, $value);

	/**
	 * Getter method to retrieve a configuration value
	 *
	 * @param string $index Key name of the parameter
	 * @return mixed Configuration parameter value
	 */
	public function get($index);

	/**
	 * Method to check if a configuration parameter exists
	 *
	 * @param $index Key name of the configuration parameter
	 * @return boolean TRUE if the parameter exists, FALSE otherwise
	 */
	public function exists($index);
}

/**
 * This class wraps the creation and manipulation of an ispCP_ConfigHandler
 * objects
 *
 * @since 	r154
 * @todo add ArrayObject support
 */
final class Config {

	/**
	 * References to ispCP_ConfigHandler objects indexed by they class name
	 *
	 * @staticvar array
	 */
	private static $_instances = array();

	/**
	 * Get a Config_Handler object
	 *
	 * Returns a reference to a Config_Handler object, only creating it
	 * if it doesn't already exist.
	 *
	 * The default ispCP_ConfigHandler object is set to ConfigHandlerFile
	 *
	 * @param string $type Type of Config_Handler object that should be returned
	 * @param mixed Options that are passed to the ispCP_ConfigHandler object
	 * 	constructor
	 * @return ispCP_ConfigHandler object
	 */
	public static function getInstance($type = 'ConfigHandlerFile',
		$params = null) {

		if(!array_key_exists($type, self::$_instances)) {

			$refl = new ReflectionClass($type);

    		if (!$refl->implementsInterface('ispCP_ConfigHandler')) {
				throw new Exception(
					'Only objects that implement the `ispCP_ConfigHandler` ' .
					'interface can be handling by the ' . __CLASS__ . ' class!'
				);
			}

			self::$_instances[$type] = call_user_func(
				array($type, 'getInstance'),
				$params
			);
		}

		return self::$_instances[$type];
	}

	/**
	 * Wrapper for getter method of a ispCP_ConfigHandler object
	 *
	 * @static
	 * @param string $param Key name of the configuration parameters
	 * @return mixed configuration parameter value
	 */
	public static function get($index, $type = 'ConfigHandlerFile') {
		return self::getInstance($type)->get($index);
	}

	/**
	 * Wrapper for getter method of a ispCP_ConfigHandler object
	 *
	 * @static
	 * @param string $param Key name of the configuration parameter
	 * @param mixed $value Value name of the configuration parameter
	 * @return void
	 */
	public static function set($index, $value, $type = 'ConfigHandlerFile') {
		self::getInstance($type)->set($index, $value);
	}
}

/**
 * Class that implements ispCP_ConfigHandler interface
 *
 * ispCP_ConfigHandler Object to handle configuration parameters that are stored
 * in a flat file where each pair of key values are separated by the equal sign.
 *
 * By default, this object parse the default ispCP configuration file.
 *
 * @See ispCP_ConfigHandler
 */
class ConfigHandlerFile implements ispCP_ConfigHandler {

	/**
	 * Configuration file path
	 *
	 * @var string Configuration file path
	 */
	private $file_path;
	
	/**
	 * Array that contain all configuration parameters parsed from the
	 * configuration file.
	 *
	 * @var Array of key - value pair
	 */
	private $values = array();
	
	/**
	 * Reference to an object of this class
	 *
	 * @var Reference to an object of this class
	 */
	private static $_instance = null;
	
	/**
	 * Loads the ispCP config file (default directory: /etc/ispcp/ispcp.conf)
	 *
	 * @param String $cfg path to ispcp.conf
	 */
	private function __construct($path_file = null) {

		if(is_null($path_file)) {
			switch (PHP_OS) {
				case 'FreeBSD':
				case 'OpenBSD':
				case 'NetBSD':
					$path_file = '/usr/local/etc/ispcp/ispcp.conf';
					break;
				default: 
					$path_file = '/etc/ispcp/ispcp.conf';
			}
		}

		$this->file_path = $path_file;

		if (!$this->parseFile()) {
			throw new Exception(
				"Unable to open the configuration file `$path_file`!");
		}
	}

	/**
	 * Returns a reference to an object of this class, only creating it
	 * if it doesn't already exist
	 *
	 * @param string $path_file Path of configuration file
	 */
	public static function getInstance($path_file) {

		if(self::$_instance == null) {
			self::$_instance = new self($path_file);
		}

		return self::$_instance;
	}

	/**
	 * Getter method to retrieve a configuration parameter value
	 *
	 * @param string $param Key name of the configuration parameter
	 */
	public function get($index) {

		if (!$this->exists($index)) {
			throw new Exception("Config variable `$index` is missing!");
		}

		return $this->values[$index];
	}

	/**
	 * Setter method to set a new configuration parameter
	 *
	 * @param string $param Key name of the configuration parameter
	 * @param mixed $value Value of the configuration parameter
	 */
	public function set($index, $value) {

		$this->values[$index] = $value;
	}

	/**
	 * Checks if a key of a configuration parameters exists
	 *
	 * @param string $param Key name of the configuration parameter
	 * @return boolean TRUE if the configuration parameter is registered, FALSE
	 * 	otherwise
	 */
	public function exists($index) {
		
		return isset($this->values[$index]);
	}

	/**
	 * Opens a configuration file and parses its KEY = Value pairs into the
	 * $_values array.
	 *
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	private function parseFile() {

		$fd = @file_get_contents($this->file_path);

		if ($fd === false) {
			return false;
		}

		$lines = explode(PHP_EOL, $fd);

		foreach ($lines as $line) {
			if (!empty($line) && $line[0] != '#' && strpos($line, '=')) {
				list($key, $value) = explode('=', $line, 2);

				$this->values[trim($key)] = trim($value);
			}
		}

		return true;
	}
}
