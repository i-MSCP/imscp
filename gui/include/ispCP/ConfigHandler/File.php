<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		Benedikt Heintel <benedikt@heintel.org>
 * @author		laurent declercq <laurent.declercq@ispcp.net>
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
 * @see ispCP_ConfigHandler
 */
require_once  INCLUDEPATH . '/ispCP/ConfigHandler.php';

/**
 * Class to handle configuration parameters from a flat file
 *
 * ispCP_ConfigHandler adapter class to handle configuration parameters that are
 * stored in a flat file where each pair of key-values are separated by the
 * equal sign.
 *
 * @since 1.0.6
 * @version 1.0.3
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author laurent declercq <laurent.declercq@ispcp.net>
 * @see ispCP_ConfigHandler
 */
class ispCP_ConfigHandler_File extends ispCP_ConfigHandler {

	/**
	 * Configuration file path
	 *
	 * @var string Configuration file path
	 */
	protected $_pathFile;

	/**
	 * Loads all configuration parameters from a flat file
	 *
	 * Note: default file path is set to: {/usr/local}/etc/ispcp/ispcp.conf
	 * depending of the used distribution.
	 *
	 * @param string $pathFile Configuration file path
	 * @return void
	 * @todo Should be more generic (path file shouldn't be hardcoded here)
	 */
	public function __construct($pathFile = null) {

		if(is_null($pathFile)) {
			switch (PHP_OS) {
				case 'FreeBSD':
				case 'OpenBSD':
				case 'NetBSD':
					$pathFile = '/usr/local/etc/ispcp/ispcp.conf';
					break;
				default: 
					$pathFile = '/etc/ispcp/ispcp.conf';
			}
		}

		$this->_pathFile = $pathFile;

		parent::__construct($this->_parseFile());
	}

	/**
	 * Opens a configuration file and parses its Key = Value pairs into the
	 * {@link ispCP_ConfigHangler::parameters} array.
	 *
	 * @throws ispCP_Exception
	 * @return Array that contain all Configuration parameters
	 * @todo Don't use '@' error operator
	 */
	protected function _parseFile() {

		$fd = @file_get_contents($this->_pathFile);

		if ($fd === false) {
			throw new ispCP_Exception(
				"Error: Unable to open the configuration file `{$this->_pathFile}`!"
			);
		}

		$lines = explode(PHP_EOL, $fd);

		foreach ($lines as $line) {
			if (!empty($line) && $line[0] != '#' && strpos($line, '=')) {
				list($key, $value) = explode('=', $line, 2);

				$parameters[trim($key)] = trim($value);
			}
		}

		return $parameters;
	}
}
