<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 */

/**
 * Abstract class to implement general update functions
 *
 * @author		Jochen Manz <zothos@zothos.net>
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		1.2
 * @see			class.criticalUpdate.php, class.databaseUpdate.php
 * @since		r1355
 */
abstract class ispcpUpdate {

	/**
	 * Version of the last update that was applied
	 * @var int
	 */
	protected $currentVersion = 0;

	/**
	 * Error messages for updates that have failed
	 * @var string
	 */
	protected $errorMessages = '';

	/**
	 * Database variable name for the update version
	 * @var string
	 */
	protected $databaseVariableName = '';

	/**
	 * Update functions prefix
	 * @var string
	 */
	protected $functionName = '';

	/**
	 * Error message for updates that have failed
	 * @var string
	 */
	protected $errorMessage = '';

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function __construct() {
		$this->currentVersion = $this->getCurrentVersion();
	}

	/**
	 * Returns the version of the last update that was applied
	 *
	 * @return int Last update that was applied
	 */
	protected function getCurrentVersion() {
		$sql	= Database::getInstance();
		$query	= "SELECT * FROM `config` WHERE `name` = '". $this->databaseVariableName ."'";
		$rs		= $sql->Execute($query);

		return	(int)$rs->fields['value'];
	}

	/**
	 * Returns the version of the next update
	 *
	 * @return int The version of the next update
	 */
	protected function getNextVersion() {
		return $this->currentVersion + 1;
	}

	/**
	 * Checks if a new update is available
	 *
	 * @return boolean TRUE if an update is available, FALSE otherwise
	 */
	public function checkUpdateExists() {
		$functionName = $this->returnFunctionName($this->getNextVersion());

		return (method_exists($this, $functionName)) ? true : false;
	}

	/**
	 * Returns the name of the function that wraps the update
	 *
	 * @return string Update function name
	 */
	protected function returnFunctionName($version) {
		return $this->functionName . $version;
	}

	/**
	 * Send a query to the ispCP daemon
	 *
	 * @return void
	 */
	protected function sendEngineRequest() {
		send_request();
	}

	/**
	 * Adds a new message in the errors messages cache
	 *
	 * @return void
	 */
	protected function addErrorMessage($message) {
		$this->errorMessages .= $message;
	}

	/**
	 * Accessor for error messages
	 *
	 * @return Error messages
	 */
	public function getErrorMessage() {
		return $this->errorMessages;
	}

	/**
	 * Apply all available updates
	 *
	 * @return void
	 */
	public function executeUpdates() {
		$engine_run_request = false;
		$sql = Database::getInstance();

		while ($this->checkUpdateExists()) {

			// Get the next database update Version
			$newVersion = $this->getNextVersion();

			// Get the needed function name
			$functionName = $this->returnFunctionName($newVersion);

			// Pull the query from the update function using a variable function
			$queryArray = $this->$functionName($engine_run_request);

			// Adding the SQL statement to set the new Database Version, to our
			// queryArray
			$queryArray[] = "
							 UPDATE `config`
							 SET `value` = '$newVersion'
							 WHERE `name` = '{$this->databaseVariableName}'
			";

			// First, switch to exception mode for errors managment
			$sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			// We start a transaction (autocommit disabled)
			$sql->StartTrans();

			try {

					// We execute every Sql statements
					foreach($queryArray as $query) {
						$sql->execute($query);
					}

					// If all SQL statements are executed correctly, commits
					// the changes
					$sql->CompleteTrans();

			} catch(PDOException $e) {

				// Perform a rollback if a Sql statement was failed
				$sql->RollbackTrans();

				// Prepare and display an error message
				$errorMessage =  tr($this->errorMessage, $newVersion);

				// Extended error message
				if (Config::get('DEBUG')) {
					$errorMessage .= "<br />" . $e->getMessage();
					$errorMessage .=  "<br />Sql Statement was failed: $query";
				}

				$this->addErrorMessage($errorMessage);

				// An error was occured, we stop here !
				break;
			}

			$this->currentVersion=$newVersion;

		} // End while

		if ($engine_run_request) {
			$this->sendEngineRequest();
		}
	}
}
