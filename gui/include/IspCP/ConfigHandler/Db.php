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
 * IspCP_ConfigHandler adapter to handle configuration parameters that are
 * stored in a database.
 *
 * Note: Only raw PDO instance is currently supported.
 *
 * @author Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 * @since 1.0.6
 * @see ispCP_ConfigHandler
 */
class IspCP_ConfigHandler_Db extends IspCP_ConfigHandler {

	/**
	 * Reference to a raw PDO instance
	 *
	 * @var Reference to a PDO instance
	 */
	private $_db;

	/**
	 * PDOStatement to insert a configuration parameter in the database
	 *
	 * For performance reason, the PDOStatement object is created only once at
	 * the first execution of the {@link insert_to_db()} method.
	 *
	 * @var Reference to a PDOStatement instance
	 */
	private $insert_stmt = null;

	/**
	 * PDOStatement to update a configuration parameter in the database
	 *
	 * For performance reason, the PDOStatement object is created only once at
	 * the first execution of the {@link update_to_db()} method.
	 *
	 * @var Reference to a PDOStatement instance
	 */
	private $update_stmt = null;

	/**
	 * Variable bound to the PDOStatement objects
	 *
	 * The value of this variable is bound to the PDOStatement that are used by
	 * the both method {@link insert_to_db()} and {@link update_to_db()}
	 *
	 * @var Configuration parameter key name
	 */
	private $index = null;

	/**
	 * Variable bound to the PDOStatement objects
	 *
	 * The value of this variable is bound to the PDOStatement that are used by
	 * the both method {@link insert_to_db()} and {@link update_to_db()}
	 *
	 * @var Configuration parameter value
	 */
	private $value = null;

	/**
	 * Database table where the configuration parameters are stored
	 * @var
	 */
	protected $table_name = 'config';

	/**
	 * Database column name for configuration parameters keys
	 *
	 * @var string Column name
	 */
	protected $keys_column = 'name';

	/**
	 * Database column name for configuration parameters values
	 *
	 * @var string Column name
	 */
	protected $values_column = 'value';

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
	 * db: Reference to a raw PDO (unwrapped) instance
	 * table_name: Database configuration table name
	 * key_column: Database configuration key column name
	 * value_column: Database configuration value column name
	 *
	 * Note: The three last parameters are optionals.
	 *
	 * for a single parameter, only a raw PDO (unwrapped) instance is accepted.
	 *
	 * @param PDO|array A PDO instance or an array of parameter that contain at
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
					$this->table_name = $params['table_name'];
			}

			// Override the column name for configuration parameters keys
			if(isset($params['keys_column'])) {
				$this->keys_column = $params['keys_column'];
			}

			// Set the column name for configuration parameters values
			if(isset($params['values_column'])) {
				$this->values_column = $params['values_column'];
			}

		} elseif(!$params instanceof PDO) {
			throw new Exception('PDO instance requested for ' . __CLASS__);
		}

		$this->_db = $params;

		parent::__construct($this->load_all());
	}

	/**
	 * Setter method to set a new configuration parameter in the database
	 *
	 * For performance reasons, queries for updates are only done if the old and
	 * the new value of a parameter are not the same.
	 *
	 * @param string $index Configuration parameter key name
	 * @param string|int $value Configuration parameter value
	 * @return void
	 */
	public function set($index, $value) {

		$this->index = $index;
		$this->value = $value;

		if(!array_key_exists($index, $this->parameters)) {
			$this->insert_to_db();
		} elseif($this->parameters[$index] != $value) {
			$this->update_to_db();
		} else {
			return;
		}

		parent::set($index, $value);
	}

	/**
	 * Load all the configuration parameters from the database
	 *
	 * @throws Exception
	 * @return void
	 */
	private function load_all() {

		$query = "
			SELECT
				`{$this->keys_column}`,
				`{$this->values_column}`
			FROM
				`{$this->table_name}`
			;
		";

		if(($stmt = $this->_db->query($query, PDO::FETCH_ASSOC)) !== false) {
			foreach($stmt->fetchAll() as $row) {
				$parameters[$row[$this->keys_column]] =
					$row[$this->values_column];
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
	private function insert_to_db() {
		if(!$this->insert_stmt instanceof PDOStatement) {

			$query = "
				INSERT INTO
					`{$this->table_name}`
					({$this->keys_column}, `{$this->values_column}`)
				VALUES
					(:index, :value)
				;
			";

			$this->insert_stmt = $this->_db->prepare($query);
			$this->insert_stmt->BindParam(':index', $this->index);
			$this->insert_stmt->BindParam(':value', $this->value);
		}

		if($this->insert_stmt->execute() === false) {
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
	private function update_to_db() {

		if(!$this->update_stmt instanceof PDOStatement) {

			$query = "
				UPDATE
					`{$this->table_name}`
				SET
					`{$this->values_column}` = :value
				WHERE
					`{$this->keys_column}` = :index
				;
			";

			$this->update_stmt = $this->_db->prepare($query);
			$this->update_stmt->BindParam(':index', $this->index);
			$this->update_stmt->BindParam(':value', $this->value);
		}

		if($this->update_stmt->execute() === false) {
			throw new Exception(
				'Unable to update the configuration parameter in the database!'
			);
		}
	}

	/**
	 * Force reload of all configuration parameters from the database
	 *
	 * @return void
	 */
	public function force_reload() {
		$this->parameters = $this->load_all();
	}
}
