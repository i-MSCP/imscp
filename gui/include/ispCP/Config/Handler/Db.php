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
 * Class to handle configuration parameters from database
 *
 * ispCP_Config_Handler adapter class to handle configuration parameters that
 * are stored in database.
 *
 * @package		ispCP_Config
 * @subpackage	Handler
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @since		1.0.6
 * @version		1.0.5
 */
class ispCP_Config_Handler_Db extends ispCP_Config_Handler {

	/**
	 * PDO instance
	 *
	 * @var PDO
	 */
	protected $_db;

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
	 * <b>Note:</b> For performance reason, the PDOStatement instance is created
	 * only once at the first execution of the {@link _update()} method.
	 *
	 * @var PDOStatement
	 */
	protected $_updateStmt = null;

	/**
	 * PDOStatement to delete a configuration parameter in the database
	 *
	 * <b>Note:</b> For performance reason, the PDOStatement instance is created
	 * only once at the first execution of the {@link _delete()} method.
	 *
	 * @var PDOStatement
	 */
	protected $_deleteStmt = null;

	/**
	 * Variable bound to the PDOStatement instances
	 *
	 * This variable is bound to the PDOStatement instances that are used by
	 * {@link _insert()} , {@link _update()} and {@link _delete()} methods.
	 *
	 * @var string Configuration parameter key name
	 */
	protected $_index = null;

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
	 * Database table for configuration parameters
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
	 * Loads all configuration parameters from database
	 *
	 * <b>Parameters:</b>
	 *
	 * The constructor accept one or more parameters passed in a array where
	 * each key represent a parameter name.
	 *
	 * For an array, the possible parameters are:
	 *
	 * - db: Reference to PDO instance
	 * - table_name: Database configuration table name
	 * - key_column: Database configuration key column name
	 * - value_column: Database configuration value column name
	 *
	 * <b>Note:</b> The three last parameters are optionals.
	 *
	 * For a single parameter, only a {@link PDO} instance is accepted.
	 *
	 * @throws ispCP_Exception
	 * @param PDO|array A PDO instance or an array of parameters that contain at
	 *	least a PDO instance
	 * @return void
	 */
	public function __construct($params) {

		if(is_array($params)) {

			if(!array_key_exists('db', $params) || !($params['db'] instanceof PDO)) {
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

		parent::__construct($this->_loadAll());
	}

	/**
	 * Setter method to set or change a configuration parameter in the database
	 *
	 * <b>Note:</b> For performance reasons, queries for updates are only done
	 * if old and new value of a parameter are not the same.
	 *
	 * @param string $index Configuration parameter key name
	 * @param mixed $value Configuration parameter value
	 * @return void
	 */
	public function set($index, $value) {

		$this->_index = $index;
		$this->_value = $value;

		if(!array_key_exists($index, $this->_parameters)) {
			$this->_insert();
		} elseif($this->_parameters[$index] != $value) {
			$this->_update();
		} else {
			return;
		}

		parent::set($index, $value);
	}

	/**
	 * Force reload of all configuration parameters from the database
	 *
	 * @return void
	 */
	public function forceReload() {

		$this->_parameters = $this->_loadAll();
	}

	/**
	 * Returns the count of SQL queries that were executed
	 *
	 * This method returns the count of queries that were executed since the
	 * last call of {@link reset_queries_counter()} method.
	 *
	 * @throws ispCP_Exception
	 * @param string $queriesCounterType Type of query counter (insert|update)
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
	 * Defined by SPL ArrayAccess interface
	 *
	 * See {@link http://www.php.net/~helly/php/ext/spl}
	 */
	public function offsetUnset($index) {

		$this->_index = $index;
		$this->_delete();

		parent::offsetUnset($index);
	}

	/**
	 * PHP Overloading for call of unset() on inaccessible members
	 *
	 * @param string $index Configuration parameter key name
	 * @return void
	 */
	public function __unset($index) {

			$this->_index = $index;
			$this->_delete();

			parent::__unset($index);
	}

	/**
	 * Load all the configuration parameters from the database
	 *
	 * @throws ispCP_Exception
	 * @return array An Array that contain all configuration parameters
	 */
	protected function _loadAll() {

		$query = "
			SELECT
				`{$this->_keysColumn}`,
				`{$this->_valuesColumn}`
			FROM
				`{$this->_tableName}`
			;
		";

		if(($stmt = $this->_db->query($query, PDO::FETCH_ASSOC)) !== false) {
			foreach($stmt->fetchAll() as $row) {
				$parameters[$row[$this->_keysColumn]] =
					$row[$this->_valuesColumn];
			}
		} else {
			throw new ispCP_Exception(
				'Error: Could not get configuration parameters from database!'
			);
		}

		return $parameters;
	}

	/**
	 * Store a new configuration parameter in the database
	 *
	 * @throws ispCP_Exception
	 * @return void
	 */
	protected function _insert() {
		if(!$this->_insertStmt instanceof PDOStatement) {

			$query = "
				INSERT INTO
					`{$this->_tableName}`
					(`{$this->_keysColumn}`, `{$this->_valuesColumn}`)
				VALUES
					(:index, :value)
				;
			";

			$this->_insertStmt = $this->_db->prepare($query);
			$this->_insertStmt->BindParam(':index', $this->_index);
			$this->_insertStmt->BindParam(':value', $this->_value);
		}

		if($this->_insertStmt->execute() === false) {
			throw new ispCP_Exception(
				'Error: Unable to insert the configuration parameter in the database!'
			);
		}
	}

	/**
	 * Update a configuration parameter in the database
	 *
	 * @throws ispCP_Exception
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
			$this->_updateStmt->BindParam(':index', $this->_index);
			$this->_updateStmt->BindParam(':value', $this->_value);
		}

		if($this->_updateStmt->execute() === false) {
			throw new ispCP_Exception(
				'Error: Unable to update the configuration parameter in the database!'
			);
		} else {
			$this->_updateQueriesCounter++;
		}
	}

	/**
	 * Delete a configuration parameter in the database
	 *
	 * @throws ispCP_Exception
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
			$this->_deleteStmt->BindParam(':index', $this->_index);
		}

		if($this->_deleteStmt->execute() === false) {
			throw new ispCP_Exception(
				'Error: Unable to delete the configuration parameter in the database!'
			);
		}
	}
}
