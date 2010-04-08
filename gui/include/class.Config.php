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
 * This class will parse the config file ispcp.conf and save the variables
 * in a stratic array.
 * 
 * @version	2.0
 * @since 	r154
 */
final class Config {
	
	/**
	 * Config filename.
	 * 
	 * @var String file name
	 */
	private static $_file;

	/**
	 * Array with all options parsed from config file.
	 * 
	 * @var Array key - value pair
	 */
	private static $_values = array();

	/**
	 * Set to true if class is inizialized
	 * 
	 * @var boolean Status
	 */
	private static $_status = false;

	/**
	 * Loads the ispCP config file (default directory: /etc/ispcp/ispcp.conf)
	 * 
	 * @param String $cfg path to ispcp.conf
	 */
	public static function load($cfg = '/etc/ispcp/ispcp.conf') {
		self::$_file = $cfg;

		if (self::$_status === false) {
			if (!self::_parseFile()) {
				throw new Exception('Cannot open the ispcp.conf config file!');
			}
		}

		self::$_status = true;
	}

	/**
	 * 
	 * @param unknown_type $param
	 */
	public static function get($param) {
		if (!isset(self::$_values[$param]))
			throw new Exception("Config variable '".$param."' is missing!");

		if (!self::$_status)
			throw new Exception('Config not loaded!');

		return self::$_values[$param];
	}

	/**
	 * 
	 * @param unknown_type $param
	 * @param unknown_type $value
	 */
	public static function set($param, $value) {
		self::$_values[$param] = $value;
	}

	/**
	 * Checks if a key exists
	 * 
	 * @param String $param
	 * @return boolean
	 */
	public static function exists($param) {
		return isset(self::$_values[$param]);
	}

	/**
	 * Opens the config file and parses its KEY = Value pairs into the $_values
	 * Array.
	 * 
	 * @return boolean true on success
	 */
	private static function _parseFile() {
		$fd = file_get_contents(self::$_file);
		if ($fd === false) {
			return false;
		}

		$lines = explode("\n", $fd);
		foreach ($lines as $line) {
			trim($line);
			if (!empty($line) && $line[0] != '#' && strpos($line, '=')) {
				list($key, $value) = explode('=', $line, 2);

				self::$_values[trim($key)] = trim($value);
			}
		}

		print_r(self::$_values);
		return true;
	}
}
