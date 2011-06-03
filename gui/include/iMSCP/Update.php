<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package     iMSCP_Update
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @version     SVN: $Id$
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Abstract class to implement update functions
 *
 * @package     iMSCP_Update
 * @author      Jochen Manz <zothos@zothos.net>
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     1.0.4
 * @since		r1355
 */
abstract class iMSCP_Update {

	/**
	 * Version of the last update that was applied
	 *
	 * @var int
	 */
	protected $_currentVersion = 0;

	/**
	 * Error messages for updates that have failed
	 *
	 * @var string
	 */
	protected $_errorMessages = '';

	/**
	 * Database variable name for the update version
	 *
	 * @var string
	 */
	protected $_databaseVariableName = '';

	/**
	 * Update functions prefix
	 *
	 * @var string
	 */
	protected $_functionName = '';

	/**
	 * Error message for updates that have failed
	 *
	 * @var string
	 */
	protected $_errorMessage = '';

	/**
	 * This class implements the singleton design pattern
	 *
	 * @return void
	 */
	protected function __construct() {

		$this->_currentVersion = $this->_getCurrentVersion();
	}

	/**
	 * This class implements the singleton design pattern
	 *
	 * @return void
	 */
	protected function __clone() {}

	/**
	 * Returns the version of the last update that was applied
	 *
	 * @return int Last update that was applied
	 */
	protected function _getCurrentVersion() {

		$dbConfig = iMSCP_Registry::get('dbConfig');

		return (int) $dbConfig->get($this->_databaseVariableName);
	}

	/**
	 * Returns the version of the next update
	 *
	 * @return int The version of the next update
	 */
	protected function _getNextVersion() {

		return $this->_currentVersion + 1;
	}

	/**
	 * Checks if a new update is available
	 *
	 * @return boolean TRUE if a new update is available, FALSE otherwise
	 */
	public function checkUpdateExists() {

		$functionName = $this->_returnFunctionName($this->_getNextVersion());

		return (method_exists($this, $functionName)) ? true : false;
	}

	/**
	 * Returns the name of the function that provides the update
	 *
	 * @return string Update function name
	 */
	protected function _returnFunctionName($version) {

		return $this->_functionName . $version;
	}

	/**
	 * Sends a request to the i-MSCP daemon
	 *
	 * @return void
	 */
	protected function _sendEngineRequest() {

		send_request();
	}

	/**
	 * Adds a new message in the errors messages cache
	 *
	 * @return void
	 */
	protected function _addErrorMessage($message) {

		$this->_errorMessages .= $message;
	}

	/**
	 * Accessor for error messages
	 *
	 * @return string Error messages
	 */
	public function getErrorMessage() {

		return $this->_errorMessages;
	}

	/**
	 * Executes all available updates
	 *
	 * This method executes all available updates. If a query provided by an update fail, the succeeded queries from
	 * this update will not executed again.
	 *
	 * @return boolean TRUE on success, FALSE otherwise
	 * @todo Should be more generic (Only the database variable should be
	 * updated here. Other stuff should be implemented by the concrete class
	 */
	public function executeUpdates() {

		/**
		 * @var $sql PDO
		 */
		$sql = iMSCP_Registry::get('pdo');

		/**
		 * @var $dbConfig iMSCP_Config_Handler_Db
		 */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		$engine_run_request = false;

		while ($this->checkUpdateExists()) {

			// Get the next database update Version
			$newVersion = $this->_getNextVersion();

			// Get the needed function name
			$functionName = $this->_returnFunctionName($newVersion);

			// Pull the query from the update function using a variable function
			$queryArray = $this->$functionName($engine_run_request);

			// First, switch to exception mode for errors management
			$sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			if(isset($dbConfig->FAILED_UPDATE)) {
				list($failedUpdate, $queryNb) = $dbConfig->FAILED_UPDATE;
			} else {
				$failedUpdate = 'inexistent';
				$index = -1;
			}

			try {

				// We execute all SQL statements
				foreach($queryArray as $index => $query) {
					// Query was already applied with success ?
					if($functionName == $failedUpdate && $index < $queryNb) {
						continue;
					}

					$sql->query($query);
				}

				unset($dbConfig->FAILED_UPDATE);

				// Update revision
				$dbConfig->set($this->_databaseVariableName, $newVersion);

			} catch (PDOException $e) {

				// Store the query number and function name that wraps it
				$dbConfig->FAILED_UPDATE = "$functionName;$index";

				// Prepare error message
				$errorMessage =  sprintf($this->_errorMessage, $newVersion);

				// Extended error message
				if(PHP_SAPI != 'cli') {
					$errorMessage .= ':<br /><br />' . $e->getMessage() . '<br /><br />Query: ' . trim($query);
				} else {
					$errorMessage .= ":\n\n" . $e->getMessage() . "\nQuery: " . trim($query);
				}

				$this->_addErrorMessage($errorMessage);

				// An error occurred, we stop here !
				return false;
			}

			$this->_currentVersion = $newVersion;

		} // End while

		// We should never run the backend scripts from the CLI update script
		if(PHP_SAPI != 'cli' && $engine_run_request) {
			$this->_sendEngineRequest();
		}

		return true;
	}
}
