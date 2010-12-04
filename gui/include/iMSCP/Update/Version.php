<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Update
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 * @version 	SVN: $Id$
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Class iMSCP_Update_Version implements the iMSCP_Update abstract class for
 * future online version update functions
 *
 * @package		iMSCP_Update
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		1.0.1
 * @since		r1355
 */
class iMSCP_Update_Version extends iMSCP_Update {

	/**
	 * iMSCP_Update_Version instance
	 *
	 * @var iMSCP_Update_Version
	 */
	protected static $_instance = null;

	/**
	 * Database variable name for the update version
	 *
	 * @var string
	 */
	protected $_databaseVariableName = 'VERSION_UPDATE';

	/**
	 * Error message string
	 *
	 * @var string
	 */
	protected $_errorMessage = 'Version update %s failed';

	/**
	 * Gets a iMSCP_Update_Version instance
	 *
	 * @return iMSCP_Update_Version
	 */
	public static function getInstance() {

		if (is_null(self::$_instance)) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Return the current i-MSCP installed version
	 *
	 * @return int Current i-MSCP installed version
	 */
	protected function _getCurrentVersion() {

		/**
		 * @var $cfg iMSCP_Config_Handler_File
		 */
		$cfg = iMSCP_Registry::get('config');

		return (int) $cfg->BuildDate;
	}

	/**
	 * Gets the last available i-MSCP version
	 *
	 * @return bool|int Returns the last i-MSCP version available or FALSE on
	 * failure
	 * @todo Rename this function name that don't reflects the real purpose
	 */
	protected function _getNextVersion() {

		$last_update = "http://i-mscp.net/latest.txt";
		ini_set('user_agent', 'Mozilla/5.0');
		$timeout = 2;
		$old_timeout = ini_set('default_socket_timeout', $timeout);
		$dh2 = @fopen($last_update, 'r');
		ini_set('default_socket_timeout', $old_timeout);

		if (!is_resource($dh2)) {
			$this->_addErrorMessage(tr("Couldn't check for updates! Website not reachable."));

			return false;
		}

		$last_update_result = (int) fread($dh2, 8);
		fclose($dh2);

		return $last_update_result;
	}

	/**
	 * Check for i-MSCP update
	 *
	 * @return boolean TRUE if a new i-MSCP version is available FALSE otherwise
	 * @todo Rename this function name that don't reflects the real purpose
	 */
	public function checkUpdateExists() {

		return ($this->_getNextVersion() > $this->_currentVersion) ? true : false;
	}

	/**
	 * Should be documented
	 *
	 * @param  $version
	 * @return string
	 */
	protected function _returnFunctionName($version) {

		return 'dummyFunctionThatAllwaysExists';
	}

	/**
	 * Should be documented
	 *
	 * @param  $engine_run_request
	 * @return void
	 */
	protected function dummyFunctionThatAllwaysExists(&$engine_run_request) {
		// uncomment when engine part will be ready
		/*
		$dbConfig = iMSCP_Registry::get(DbConfig);
		$dbConfig->VERSION_UPDATE = $this->getNextVersion();
		$engine_run_request = true;
		 */
	}
}
