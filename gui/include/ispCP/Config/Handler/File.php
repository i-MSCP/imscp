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
 * @subpackage	Handler
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @version		SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * @see ispCP_Config_Handler
 */
require_once  INCLUDEPATH . '/ispCP/Config/Handler.php';

/**
 * Class to handle configuration parameters from a flat file
 *
 * ispCP_Config_Handler adapter class to handle configuration parameters that are
 * stored in a flat file where each pair of key-values are separated by the
 * equal sign.
 *
 * @package		ispCP_Config
 * @subpackage	Handler
 * @author		Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @since		1.0.6
 * @version		1.0.5
 */
class ispCP_Config_Handler_File extends ispCP_Config_Handler {

	/**
	 * Configuration file path
	 *
	 * @var string
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
	 * {@link ispCP_Config_Hangler::parameters} array.
	 *
	 * @throws ispCP_Exception
	 * @return array A array that contain all Configuration parameters
	 * @todo Don't use error operator
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
