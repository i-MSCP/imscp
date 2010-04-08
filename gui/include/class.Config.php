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
 * @since 	r154
 */
class Config {
    private static $config;

    public static function getInstance() {
        if(!self::$config) {
            self::$config = new ConfigHandler();
        }
        return self::$config;
    }
}
 
class ConfigHandler {	
	/**
	 * Config filename.
	 * 
	 * @var String file name
	 */
	private $file;
	
	/**
	 * Array with all options parsed from config file.
	 * 
	 * @var Array key - value pair
	 */
	private $values;

	/**
	 * Loads the ispCP config file (default directory: /etc/ispcp/ispcp.conf)
	 * 
	 * @param String $cfg path to ispcp.conf
	 */
	public function __construct() {
		switch (PHP_OS) {
			case 'FreeBSD':
			case 'OpenBSD':
			case 'NetBSD':
				$path = '/usr/local/etc/ispcp/ispcp.conf';
				break;
				
			default: 
				$path = '/etc/ispcp/ispcp.conf';
				break;
		}
		$this->file = $path;
	
		if (!$this->parseFile()) {
			throw new Exception('Cannot open the ispcp.conf config file!');
		}
	}

	/**
	 * 
	 * @param unknown_type $param
	 */
	public function get($param) {
		if (!$this->exists($param)) {
			throw new Exception("Config variable '".$param."' is missing!");
		}

		return $this->values[$param];
	}

	/**
	 * 
	 * @param unknown_type $param
	 * @param unknown_type $value
	 */
	public function set($param, $value) {
		$this->values[$param] = $value;
	}

	/**
	 * Checks if a key exists
	 * 
	 * @param String $param
	 * @return boolean
	 */
	public function exists($param) {
		return isset($this->values[$param]);
	}

	/**
	 * Opens the config file and parses its KEY = Value pairs into the $_values
	 * Array.
	 * 
	 * @return boolean true on success
	 */
	private function parseFile() {
		$fd = @file_get_contents($this->file);
		if ($fd === false) {
			return false;
		}

		$lines = explode("\n", $fd);
		foreach ($lines as $line) {
			if (!empty($line) && $line[0] != '#' && strpos($line, '=')) {
				list($key, $value) = explode('=', $line, 2);

				$this->values[trim($key)] = trim($value);
			}
		}

		return true;
	}
}
