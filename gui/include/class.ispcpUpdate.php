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

/**
 * Implementing abstract class ispcpUpdate for future online version update functions
 *
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		1.0
 * @see			Other Functions (in other Files)
 * @since		r1355
 */
class versionUpdate extends ispcpUpdate {

	/**
	 * Database variable name for the update version
	 * @var string
	 */
	protected $databaseVariableName = "VERSION_UPDATE";

	/**
	 * @todo Please descibe this variable!
	 */
	protected $errorMessage = "Version update %s failed";

	/**
	 * @todo Please descibe this method!
	 */
	public static function getInstance() {
		static $instance = null;
		if ($instance === null) $instance = new self();

		return $instance;
	}

	/**
	 * @todo Please descibe this method!
	 */
	protected function getCurrentVersion() {
		return (int)Config::get('BuildDate');
	}

	/**
	 * @todo Please descibe this method!
	 */
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

	/**
	 * @todo Please descibe this method!
	 */
	public function checkUpdateExists() {
		return ($this->getNextVersion()>$this->currentVersion) ? true : false;
	}

	/**
	 * @todo Please descibe this method!
	 */
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
