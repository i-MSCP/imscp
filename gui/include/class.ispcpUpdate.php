<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GPL General Public License
 *   as published by the Free Software Foundation; either version 2.0
 *   of the License, or (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *   GPL General Public License for more details.
 *
 *   You may have received a copy of the GPL General Public License
 *   along with this program.
 *
 *   An on-line copy of the GPL General Public License can be found
 *   http://www.fsf.org/licensing/licenses/gpl.txt
 */

/**
 * @todo separate classes ispcpUpdate + versionUpdate into two separate files
 */

/**
 * Abstract class to implement general update functions
 *
 * @author	Jochen Manz <zothos@zothos.net>
 * @author	Daniel Andreca <sci2tech@gmail.com>
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version	1.0
 * @see		critical-update-functions.php, database-update-functions.php
 * @since	r1355
 */
abstract class ispcpUpdate {
	protected $currentVersion = 0;
	protected $errorMessages = '';
	protected $databaseVariableName = '';
	protected $functionName = '';
	protected $errorMessage = '';

	protected function __construct() {
		$this->currentVersion = $this->getCurrentVersion();
	}

	protected function getCurrentVersion() {
		$sql	= Database::getInstance();
		$query	= "SELECT * FROM `config` WHERE `name` = '". $this->databaseVariableName ."'";
		$rs		= $sql->Execute($query);
		return	(int)$rs->fields['value'];
	}

	protected function getNextVersion() {
		return $this->currentVersion + 1;
	}

	public function checkUpdateExists() {
		$functionName = $this->returnFunctionName($this->getNextVersion());

		return (method_exists($this, $functionName)) ? true : false;
	}

	protected function returnFunctionName($version) {
		return $this->functionName . $version;
	}

	protected function sendEngineRequest() {
		send_request();
	}

	protected function addErrorMessage($message) {
		$this->errorMessages .= $message;
	}

	public function getErrorMessage() {
		return $this->errorMessages;
	}

	public function executeUpdates() {
		$engine_run_request = false;
		$sql = Database::getInstance();
		$failedUpdate = false;

		while ($this->checkUpdateExists()) {
			// Get the next database update Version
			$newVersion		= $this->getNextVersion();

			// Get the needed function name
			$functionName	= $this->returnFunctionName($newVersion);

			// Pull the query from the update function using a variable function
			$queryArray		= $this->$functionName($engine_run_request);

			// Add the query, to set the new Database Version, to our queryArray
			$queryArray[]	= "UPDATE `config` SET `value` = '$newVersion' WHERE `name` = '{$this->databaseVariableName}'";

			// Start the Transaction
			$sql->StartTrans();

			// Execute every query in our queryArray
			foreach ($queryArray as $query) {
				$sql->Execute($query);
			}

			// Set failedUpdate to true if an databaseUpdate failed
			if ($sql->HasFailedTrans())
				$failedUpdate = true;

			// Complete the Transaction and rollback if necessary
			$sql->CompleteTrans();

			// Display an error if necessary
			if ($failedUpdate) {
				$this->addErrorMessage(tr($this->errorMessage, $newVersion));
				break;
			} else {
				$this->currentVersion=$newVersion;
			}
		}
		if ($engine_run_request) {
			$this->sendEngineRequest();
		}
	}
}

/**
 * Implementing abstract class ispcpUpdate for future online version update functions
 *
 * @author	Daniel Andreca <sci2tech@gmail.com>
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version	1.0
 * @see		Other Functions (in other Files)
 * @since	r1355
 */
class versionUpdate extends ispcpUpdate {
	protected $databaseVariableName = "VERSION_UPDATE";
	protected $errorMessage = "Version update %s failed";

	public static function getInstance() {
		static $instance = null;
		if ($instance === null) $instance = new self();
		return $instance;
	}

	protected function getCurrentVersion() {
		return (int)Config::get('BuildDate');
	}

	protected function getNextVersion() {
		$last_update = "http://www.isp-control.net/latest.txt";
		ini_set('user_agent', 'Mozilla/5.0');
		$timeout = 2;
		$old_timeout = ini_set('default_socket_timeout', $timeout);
		$dh2 = @fopen($last_update, 'r');
		ini_set('default_socket_timeout', $old_timeout);
		if (!is_resource($dh2)) {
			$this->addErrorMessage(tr("Couldn't check for updates! Website not reachable."));
			return false;
		}
		$last_update_result = (int)fread($dh2, 8);
		fclose($dh2);
		return $last_update_result;
	}

	public function checkUpdateExists() {
		return ($this->getNextVersion()>$this->currentVersion) ? true : false;
	}

	protected function returnFunctionName($version) {
		return "dummyFunctionThatAllwaysExists";
	}

	/**
	 * @todo Please descibe this method!
	 */
	protected function dummyFunctionThatAllwaysExists(&$engine_run_request) {
		// uncomment when engine part will be ready
		/*
		setConfig_Value('VERSION_UPDATE', $this->getNextVersion());
		$engine_run_request = true;
		*/
	}
}
