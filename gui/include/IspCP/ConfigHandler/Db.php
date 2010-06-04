<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		Laurent Declercq (nuxwin) <laurent.declercq@nuxwin.com>
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
 * @see IspCP_ConfigHandler
 */
require_once  INCLUDEPATH . '/IspCP/ConfigHandler.php';

/**
 * Class to handle configuration parameters from a database
 *
 * IspCP_ConfigHandler adapter class to handle configuration parameters that are
 * stored in a database.
 *
 * Note: Only PDO is currently supported.
 *
 * @author Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 * @since 1.0.6
 * @see ispCP_ConfigHandler
 */
class IspCP_ConfigHandler_Db extends IspCP_ConfigHandler {

	/**
	 * Reference to a PDO instance
	 *
	 * @var PDO
	 */
	protected $_db;

	/**
	 * PDOStatement to insert a configuration parameter in the database
	 *
	 * For performance reason, the PDOStatement object is created only once at
	 * the first execution of the {@link _insert()} method.
	 *
	 * @var PDOStatement
	 */
	protected $_insert_stmt = null;

	/**
	 * PDOStatement to update a configuration parameter in the database
	 *
	 * For performance reason, the PDOStatement object is created only once at
	 * the first execution of the {@link _update()} method.
	 *
	 * @var PDOStatement
	 */
	protected $_update_stmt = null;

	/**
	 * PDOStatement to delete a configuration parameter in the database
	 *
	 * For performance reason, the PDOStatement object is created only once at
	 * the first execution of the {@link _delete()} method.
	 *
	 * @var PDOStatement
	 */
	protected $_delete_stmt = null;

	/**
	 * Variable bound to the PDOStatement objects
	 *
	 * This variable is bound to the PDOStatement objects that are used by
	 * {@link _insert()} , {@link _update()} and {@link _delete()} methods.
	 *
	 * @var string Configuration parameter key name
	 */
	protected $_index = null;

	/**
	 * Variable bound to the PDOStatement objects
	 *
	 * This variable is bound to the PDOStatement objects that are used by both
	 * {@link _insert()} and {@link _update()} methods.
	 *
	 * @var mixed Configuration parameter value
	 */
	protected $_value = null;

	/**
	 * Counter for sql update queries that were been executed
	 *
	 * @var int Sql queries count
	 */
	protected $_insert_queries_counter = 0;

	/**
	 * Counter for sql insert queries that were been executed
	 *
	 * @var int Sql queries count
	 */
	protected $_update_queries_counter = 0;

	/**
	 * Database table where the configuration parameters are stored
	 *
	 * @var string Table name
	 */
	protected $_table_name = 'config';

	/**
	 * Database column name for configuration parameters keys
	 *
	 * @var string Column name
	 */
	protected $_keys_column = 'name';

	/**
	 * Database column name for configuration parameters values
	 *
	 * @var string Column name
	 */
	protected $_values_column = 'value';

	/**
	 * Loads all configuration parameters from database
	 *
	 * Parameters:
	 *
	 * The constructor accept one or more parameters passed in a array where
	 * each key represent a parameter name.
	 *
	 * For an array, the possible parameters are:
	 *
	 * db: Reference to PDO instance
	 * table_name: Database configuration table name
	 * key_column: Database configuration key column name
	 * value_column: Database configuration value column name
	 *
	 * Note: The three last parameters are optionals.
	 *
	 * For a single parameter, only a PDO instance is accepted.
	 *
	 * @param PDO|array A PDO instance or an array of parameters that contain at
	 *	least a PDO instance
	 * @throws Exception
	 * @return void
	 */
	public function __construct($params) {

		if(is_array($params)) {

			if(!array_key_exists('db', $params) || !$params['db'] instanceof PDO) {
				throw new Exception(
					'A PDO instance is requested for ' . __CLASS__
				);
			}

			$this->_db = $param('db');

			// Overrides the database table name for configuration parameters
			if(isset($params['table_name'])) {
					$this->_table_name = $params['table_name'];
			}

			// Override the column name for configuration parameters keys
			if(isset($params['keys_column'])) {
				$this->_keys_column = $params['keys_column'];
			}

			// Set the column name for configuration parameters values
			if(isset($params['values_column'])) {
				$this->_values_column = $params['values_column'];
			}

		} elseif(!$params instanceof PDO) {
			throw new Exception('PDO instance requested for ' . __CLASS__);
		}

		$this->_db = $params;

		parent::__construct($this->_load_all());
	}

	/**
	 * Setter method to set or change a configuration parameter in the database
	 *
	 * For performance reasons, queries for updates are only done if the old and
	 * the new value of a parameter are not the same.
	 *
	 * @param string $index Configuration parameter key name
	 * @param mixed $value Configuration parameter value
	 * @return void
	 */
	public function set($index, $value) {

		$this->_index = $index;
		$this->_value = $value;

		if(!array_key_exists($index, $this->parameters)) {
			$this->_insert();
		} elseif($this->parameters[$index] != $value) {
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
	public function force_reload() {

		$this->parameters = $this->_load_all();
	}

	/**
	 * Returns the count of sql queries that were executed
	 *
	 * This method returns the count of queries that were executed since the
	 * last call of {@link reset_queries_counter()} method.
	 *
	 * @throws Exception
	 * @param string $query_counter_type type of query counter (insert|update)
	 * @return void
	 */
	public function count_queries($queries_counter_type) {

		if($queries_counter_type == 'update') {

			return $this->_update_queries_counter;

		} elseif($queries_counter_type == 'insert') {

			return $this->_insert_queries_counter;

		} else {
			throw new Exception('Unknown queries counter!');
		}
	}

	/**
	 * Reset a counter of queries
	 *
	 * @throws Exception
	 * @param string $query_counter_type type of query counter (insert|update)
	 * @return void
	 */
	public function reset_queries_counter($queries_counter_type) {

		if($queries_counter_type == 'update') {

			$this->_update_queries_counter = 0;

		} elseif($queries_counter_type == 'insert') {

			 $this->_insert_queries_counter = 0;

		} else {
			throw new Exception('Unknown queries counter!');
		}
	}

	/**
	 * Load all the configuration parameters from the database
	 *
	 * @throws Exception
	 * @return Array that contain all configuration parameters
	 */
	protected function _load_all() {

		$query = "
			SELECT
				`{$this->_keys_column}`,
				`{$this->_values_column}`
			FROM
				`{$this->_table_name}`
			;
		";

		if(($stmt = $this->_db->query($query, PDO::FETCH_ASSOC)) !== false) {
			foreach($stmt->fetchAll() as $row) {
				$parameters[$row[$this->_keys_column]] =
					$row[$this->_values_column];
			}
		} else {
			throw new Exception(
				'Could not get configuration parameters from database!'
			);
		}

		return $parameters;
	}

	/**
	 * Store a new configuration parameter in the database
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function _insert() {
		if(!$this->_insert_stmt instanceof PDOStatement) {

			$query = "
				INSERT INTO
					`{$this->_table_name}`
					(`{$this->_keys_column}`, `{$this->_values_column}`)
				VALUES
					(:index, :value)
				;
			";

			$this->_insert_stmt = $this->_db->prepare($query);
			$this->_insert_stmt->BindParam(':index', $this->_index);
			$this->_insert_stmt->BindParam(':value', $this->_value);
		}

		if($this->_insert_stmt->execute() === false) {
			throw new Exception(
				'Unable to insert the configuration parameter in the database!'
			);
		}
	}

	/**
	 * Update a configuration parameter in the database
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function _update() {

		if(!$this->_update_stmt instanceof PDOStatement) {

			$query = "
				UPDATE
					`{$this->_table_name}`
				SET
					`{$this->_values_column}` = :value
				WHERE
					`{$this->_keys_column}` = :index
				;
			";

			$this->_update_stmt = $this->_db->prepare($query);
			$this->_update_stmt->BindParam(':index', $this->_index);
			$this->_update_stmt->BindParam(':value', $this->_value);
		}

		if($this->_update_stmt->execute() === false) {
			throw new Exception(
				'Unable to update the configuration parameter in the database!'
			);
		} else {
			$this->_update_queries_counter++;
		}
	}

	/**
	 * Delete a configuration parameter in the database
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function _delete() {

		if(!$this->_delete_stmt instanceof PDOStatement) {

			$query = "
				DELETE FROM
					`{$this->_table_name}`
				WHERE
					`{$this->_keys_column}` = :index
				;
			";

			$this->_delete_stmt = $this->_db->prepare($query);
			$this->_delete_stmt->BindParam(':index', $this->_index);
		}

		if($this->_delete_stmt->execute() === false) {
			throw new Exception(
				'Unable to delete the configuration parameter in the database!'
			);
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
}
