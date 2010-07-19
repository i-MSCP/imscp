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
 * @category    ispCP
 * @package     ispCP_Config
 * @subpackage  Handler
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @author      Laurent Declercq <laurent.declercq@ispcp.net>
 * @version     SVN: $Id$
 * @link        http://isp-control.net ispCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * @see ispCP_Config_Handler
 */
require_once  INCLUDEPATH . '/ispCP/Config/Handler.php';

/**
 * Class to handle configuration parameters from database
 *
 * ispCP_Config_Handler adapter class to handle configuration parameters that
 * are stored in database.
 *
 * @package     ispCP_Config
 * @subpackage  Handler
 * @author      Laurent Declercq <laurent.declercq@ispcp.net>
 * @since       1.0.6
 * @version     1.0.6
 */
class ispCP_Config_Handler_Db extends ispCP_Config_Handler implements iterator {

	/**
	 * PDO instance
	 *
	 * @var PDO
	 */
	protected $_db;

	/**
	 * Array that contains all configuration parameters from the database
	 *
	 * @var array
	 */
	protected $_parameters = array();

	/**
	 * PDOStatement to insert a configuration parameter in the database
	 *
	 * <b>Note:</b> For performance reason, the PDOStatement instance is created
	 * only once at the first execution of the {@link _insert()} method.
	 *
	 * @var PDOStatement
	 */
	protected $_insertStmt = null;

	/**
	 * PDOStatement to update a configuration parameter in the database
	 *
	 * <b>Note:</b> For performances reasons, the PDOStatement instance is
	 * created only once at the first execution of the {@link _update()} method.
	 *
	 * @var PDOStatement
	 */
	protected $_updateStmt = null;

	/**
	 * PDOStatement to delete a configuration parameter in the database
	 *
	 * <b>Note:</b> For performances reasons, the PDOStatement instance is
	 * created only once at the first execution of the {@link _delete()} method.
	 *
	 * @var PDOStatement
	 */
	protected $_deleteStmt = null;

	/**
	 * Variable bound to the PDOStatement instances
	 *
	 * This variable is bound to the PDOStatement instances that are used by
	 * {@link _insert()}, {@link _update()} and {@link _delete()} methods.
	 *
	 * @var string Configuration parameter key name
	 */
	protected $_key = null;

	/**
	 * Variable bound to the PDOStatement objects
	 *
	 * This variable is bound to the PDOStatement instances that are used by
	 * both {@link _insert()} and {@link _update()} methods.
	 *
	 * @var mixed Configuration parameter value
	 */
	protected $_value = null;

	/**
	 * Counter for SQL update queries
	 *
	 * @var int
	 */
	protected $_insertQueriesCounter = 0;

	/**
	 * Counter for SQL insert queries
	 *
	 * @var int
	 */
	protected $_updateQueriesCounter = 0;

	/**
	 * Database table name for configuration parameters
	 *
	 * @var string
	 */
	protected $_tableName = 'config';

	/**
	 * Database column name for configuration parameters keys
	 *
	 * @var string
	 */
	protected $_keysColumn = 'name';

	/**
	 * Database column name for configuration parameters values
	 *
	 * @var string
	 */
	protected $_valuesColumn = 'value';

	/**
	 * Loads all configuration parameters from the database
	 *
	 * <b>Parameters:</b>
	 *
	 * The constructor accepts one or more parameters passed in a array where
	 * each key represent a parameter name.
	 *
	 * For an array, the possible parameters are:
	 *
	 * - db: A PDO instance
	 * - table_name: Database table for configuration parameters
	 * - key_column: Database column name for configuration parameters keys
	 * - value_column: Database column name for configuration parameters values
	 *
	 * <b>Note:</b> The three last parameters are optionals.
	 *
	 * For a single parameter, only a PDO instance is accepted.
	 *
	 * @throws ispCP_Exception
	 * @param PDO|array A PDO instance or an array of parameters that contains
	 * at least a PDO instance
	 * @return void
	 */
	public function __construct($params) {

		if(is_array($params)) {
			if(!array_key_exists('db', $params) ||
				!($params['db'] instanceof PDO)) {

				throw new ispCP_Exception(
					'Error: A PDO instance is requested for ' . __CLASS__
				);
			}

			$this->_db = $params['db'];

			// Overrides the database table name for configuration parameters
			if(isset($params['table_name'])) {
					$this->_tableName = $params['table_name'];
			}

			// Override the column name for configuration parameters keys
			if(isset($params['keys_column'])) {
				$this->_keysColumn = $params['keys_column'];
			}

			// Set the column name for configuration parameters values
			if(isset($params['values_column'])) {
				$this->_valuesColumn = $params['values_column'];
			}

		} elseif(!$params instanceof PDO) {
			throw new ispCP_Exception(
				'Error: PDO instance requested for ' . __CLASS__
			);
		}

		$this->_db = $params;
		$this->_loadAll();
	}

	/**
	 * Allow access as object properties
	 *
	 * @see set()
	 * @param string $key Configuration parameter key name
	 * @param mixed  $value Configuration parameter value
	 * @return void
	 */
	public function __set($key, $value) {

		$this->set($key, $value);
	}

	/**
	 * Insert or update a configuration parameter in the database
	 *
	 * <b>Note:</b> For performance reasons, queries for updates are only done
	 * if old and new value of a parameter are not the same.
	 *
	 * @param string $key Configuration parameter key name
	 * @param mixed $value Configuration parameter value
	 * @return void
	 */
	public function set($key, $value) {

		$this->_key = $key;
		$this->_value = $value;

		if(!$this->exists($key)) {
			$this->_insert();
		} elseif($this->_parameters[$key] != $value) {
			$this->_update();
		} else {
			return;
		}

		$this->_parameters[$key] = $value;
	}

	/**
	 * Retrieve a configuration parameter value
	 *
	 * @throws ispCP_Exception
	 * @param string $key Configuration parameter key name
	 * @return mixed Configuration parameter value
	 */
	public function get($key) {

		if (!isset($this->_parameters[$key])) {
			throw new ispCP_Exception(
				"Error: Configuration variable `$key` is missing!"
			);
		}

		return $this->_parameters[$key];
	}

	/**
	 * Checks if a configuration parameters exists
	 *
	 * <b>Note:</b> This method  will no longer supported. Direct usage of
	 * isset() is better for performance.
	 *
	 * @param string $key Configuration parameter key name
	 * @return boolean TRUE if configuration parameter exists, FALSE otherwise
	 */
	public function exists($key) {

		return array_key_exists($key, $this->_parameters);
	}

	/**
	 * PHP isset() overloading on inaccessible members
	 *
	 * This method is triggered by calling isset() or empty() on inaccessible
	 * members.
	 *
	 * <b>Note:</b> This method will return FALSE if the configuration parameter
	 * value is NULL. To test existence of a configuration parameter, you should
	 * use the {@link exists()} method.
	 *
	 * @param string $key Configuration parameter key name
	 * @return boolean TRUE if the parameter exists and its value is not NULL
	 */
	public function __isset($key) {

		return isset($this->_parameters[$key]);
	}

	/**
	 * PHP unset() overloading on inaccessible members
	 *
	 * This method is triggered by calling isset() or empty() on inaccessible
	 * members.
	 *
	 * @param  string $key Configuration parameter key name
	 * @return void
	 */
	public function __unset($key) {

		unset($this->_parameters[$key]);
	}

	/**
	 * Force reload of all configuration parameters from the database
	 *
	 * This method will remove all the current loaded parameters and reload it
	 * from the database.
	 *
	 * @return void
	 */
	public function forceReload() {

		$this->_parameters = array();
		$this->_loadAll();
	}

	/**
	 * Returns the count of SQL queries that were executed
	 *
	 * This method returns the count of queries that were executed since the
	 * last call of {@link reset_queries_counter()} method.
	 *
	 * @throws ispCP_Exception
	 * @param string $queriesCounter Query counter type (insert|update)
	 * @return void
	 */
	public function countQueries($queriesCounterType) {

		if($queriesCounterType == 'update') {

			return $this->_updateQueriesCounter;

		} elseif($queriesCounterType == 'insert') {

			return $this->_insertQueriesCounter;

		} else {
			throw new ispCP_Exception('Error: Unknown queries counter!');
		}
	}

	/**
	 * Reset a counter of queries
	 *
	 * @throws ispCP_Exception
	 * @param string $queriesCounterType Type of query counter (insert|update)
	 * @return void
	 */
	public function resetQueriesCounter($queriesCounterType) {

		if($queriesCounterType == 'update') {

			$this->_updateQueriesCounter = 0;

		} elseif($queriesCounterType == 'insert') {

			 $this->_insertQueriesCounter = 0;

		} else {
			throw new ispCP_Exception('Error: Unknown queries counter!');
		}
	}

	/**
	 * Deletes a configuration parameters from the database
	 *
	 * @param string $index Configuration parameter key name
	 * @return void
	 */
	public function del($key) {

		$this->_key = $key;
		$this->_delete();

		unset($this->_parameters[$key]);
	}

	/**
	 * Load all configuration parameters from the database
	 *
	 * @throws ispCP_Exception
	 * @return void
	 */
	protected function _loadAll() {

		$query = "
			SELECT
				`{$this->_keysColumn}`, `{$this->_valuesColumn}`
			FROM
				`{$this->_tableName}`
			;
		";

		if(($stmt = $this->_db->query($query, PDO::FETCH_ASSOC))) {

			$keyColumn = $this->_keysColumn;
			$valueColumn = $this->_valuesColumn;

			foreach($stmt->fetchAll() as $row) {
				$this->_parameters[$row[$keyColumn]] = $row[$valueColumn];
			}
		} else {
			throw new ispCP_Exception(
				'Error: Could not get configuration parameters from database!'
			);
		}
	}

	/**
	 * Store a new configuration parameter in the database
	 *
	 * @throws ispCP_Exception_Database
	 * @return void
	 */
	protected function _insert() {

		if(!$this->_insertStmt instanceof PDOStatement) {

			$query = "
				INSERT INTO
					`{$this->_tableName}` (
						`{$this->_keysColumn}`, `{$this->_valuesColumn}`
					) VALUES (
						:index, :value
					)
				;
			";

			$this->_insertStmt = $this->_db->prepare($query);
			$this->_insertStmt->BindParam(':index', $this->_key);
			$this->_insertStmt->BindParam(':value', $this->_value);
		}

		if(!$this->_insertStmt->execute()) {
			throw new ispCP_Exception_Database(
				"Error: Unable to insert the configuration parameter `{$this->_key}` in the database"
			);
		}
	}

	/**
	 * Update a configuration parameter in the database
	 *
	 * @throws ispCP_Exception_Database
	 * @return void
	 */
	protected function _update() {

		if(!$this->_updateStmt instanceof PDOStatement) {

			$query = "
				UPDATE
					`{$this->_tableName}`
				SET
					`{$this->_valuesColumn}` = :value
				WHERE
					`{$this->_keysColumn}` = :index
				;
			";

			$this->_updateStmt = $this->_db->prepare($query);
			$this->_updateStmt->BindParam(':index', $this->_key);
			$this->_updateStmt->BindParam(':value', $this->_value);
		}

		if(!$this->_updateStmt->execute()) {
			throw new ispCP_Exception_Database(
				"Error: Unable to update the configuration parameter `{$this->_key}` in the database!"
			);
		} else {
			$this->_updateQueriesCounter++;
		}
	}

	/**
	 * Deletes a configuration parameter from the database
	 *
	 * @throws ispCP_Exception_Database
	 * @return void
	 */
	protected function _delete() {

		if(!$this->_deleteStmt instanceof PDOStatement) {

			$query = "
				DELETE FROM
					`{$this->_tableName}`
				WHERE
					`{$this->_keysColumn}` = :index
				;
			";

			$this->_deleteStmt = $this->_db->prepare($query);
			$this->_deleteStmt->BindParam(':index', $this->_key);
		}

		if(!$this->_deleteStmt->execute()) {
			throw new ispCP_Exception_Database(
				'Error: Unable to delete the configuration parameter in the database!'
			);
		}
	}

	/**
	 * Whether or not an offset exists
	 *
	 * @param mixed $offset An offset to check for existence
	 * @return boolean TRUE on success or FALSE on failure
	 */
	public function offsetExists($offset) {

		return array_key_exists($this->_parameters, $offset);
	}

	/**
	 * Returns an associative array that contains all configuration parameters
	 *
	 * @return array Array that contains configuration parameters
	 */
	public function toArray() {

		return $this->_parameters;
	}

	/**
	 * Returns the current element
	 *
	 * @return mixed Returns the current element
	 */
	public function current() {

		return current($this->_parameters);
	}

	/**
	 * Returns the key of the current element
	 *
	 * @return scalar Return the key of the current element or NULL on failure
	 */
	public function key() {

		return key($this->_parameters);
	}

	/**
	 * Moves the current position to the next element
	 *
	 * @return void
	 */
	public function next() {

		next($this->_parameters);
	}

	/**
	 * Rewinds back to the first element of the Iterator
	 *
	 * <b>Note:</b> This is the first method called when starting a foreach
	 * loop. It will not be executed after foreach loops.
	 *
	 * @return void
	 */
	public function rewind() {

		reset($this->_parameters);
	}

	/**
	 * Checks if current position is valid
	 *
	 * @return boolean TRUE on success or FALSE on failure
	 */
	public function valid() {

		return array_key_exists(key($this->_parameters), $this->_parameters);
	}
}
