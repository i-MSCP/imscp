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
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Config
 * @subpackage  Handler
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * @see iMSCP_Config_Handler
 */
require_once 'iMSCP/Config/Handler.php';

/**
 * Class to handle configuration parameters from database
 *
 * iMSCP_Config_Handler adapter class to handle configuration parameters that
 * are stored in database.
 *
 * @property string MAIL_BODY_FOOTPRINTS Mail body footprint
 * @property int MAIL_WRITER_EXPIRY_TIME Mail writer expiry time
 * @property string PORT_POSTGREY Posgrey port
 * @property int FAILED_UPDATE Failed database update
 * @property string PORT_IMSCP_DAEMON i-MSCP daemon service properties
 * @property string USER_INITIAL_LANG User initial language
 * @property string PHPINI_OPEN_BASEDIR PHP open_basedir
 * @property int DATABASE_REVISION Database revision
 * @property  int EMAIL_QUOTA_SYNC_MODE Email quota sync mode
 *
 * @package     iMSCP_Config
 * @subpackage  Handler
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_Config_Handler_Db extends iMSCP_Config_Handler implements iterator
{
	/**
	 * PDO instance.
	 *
	 * @var PDO
	 */
	protected $_db;

	/**
	 * Array that contains all configuration parameters from the database.
	 *
	 * @var array
	 */
	protected $_parameters = array();

	/**
	 * PDOStatement to insert a configuration parameter in the database.
	 *
	 * <b>Note:</b> For performance reason, the PDOStatement instance is created only once at the first execution of the
	 * {@link _insert()} method.
	 *
	 * @var PDOStatement
	 */
	protected $_insertStmt = null;

	/**
	 * PDOStatement to update a configuration parameter in the database.
	 *
	 * <b>Note:</b> For performances reasons, the PDOStatement instance is created only once at the first execution of
	 * the {@link _update()} method.
	 *
	 * @var PDOStatement
	 */
	protected $_updateStmt = null;

	/**
	 * PDOStatement to delete a configuration parameter in the database.
	 *
	 * <b>Note:</b> For performances reasons, the PDOStatement instance is created only once at the first execution of
	 * the {@link _delete()} method.
	 *
	 * @var PDOStatement
	 */
	protected $_deleteStmt = null;

	/**
	 * Variable bound to the PDOStatement instances.
	 *
	 * This variable is bound to the PDOStatement instances that are used by {@link _insert()}, {@link _update()} and
	 * {@link _delete()} methods.
	 *
	 * @var string Configuration parameter key name
	 */
	protected $_key = null;

	/**
	 * Variable bound to the PDOStatement objects.
	 *
	 * This variable is bound to the PDOStatement instances that are used by both {@link _insert()} and
	 * {@link _update()} methods.
	 *
	 * @var mixed Configuration parameter value
	 */
	protected $_value = null;

	/**
	 * Counter for SQL update queries.
	 *
	 * @var int
	 */
	protected $_insertQueriesCounter = 0;

	/**
	 * Counter for SQL insert queries.
	 *
	 * @var int
	 */
	protected $_updateQueriesCounter = 0;

	/**
	 * Counter for SQL delete queries.
	 *
	 * @var int
	 */
	protected $_deleteQueriesCounter = 0;

	/**
	 * Database table name for configuration parameters.
	 *
	 * @var string
	 */
	protected $_tableName = 'config';

	/**
	 * Database column name for configuration parameters keys.
	 *
	 * @var string
	 */
	protected $_keysColumn = 'name';

	/**
	 * Database column name for configuration parameters values.
	 *
	 * @var string
	 */
	protected $_valuesColumn = 'value';

	/**
	 * Loads all configuration parameters from the database.
	 *
	 * <b>Parameters:</b>
	 *
	 * The constructor accepts one or more parameters passed in a array where each key represent a parameter name.
	 *
	 * For an array, the possible parameters are:
	 *
	 * - db: A PDO instance
	 * - table_name: Database table that contain configuration parameters
	 * - key_column: Database column name for configuration parameters key names
	 * - value_column: Database column name for configuration parameters values
	 *
	 * <b>Note:</b> The three last parameters are optionals.
	 *
	 * For a single parameter, only a PDO instance is accepted.
	 *
	 * @throws iMSCP_Exception
	 * @param PDO|array $params A PDO instance or an array of parameters that contains
	 * at least a PDO instance
	 */
	public function __construct($params)
	{
		if (is_array($params)) {
			if (!array_key_exists('db', $params) || !($params['db'] instanceof PDO)) {
				throw new iMSCP_Exception('A PDO instance is requested for ' . __CLASS__);
			}

			$this->_db = $params['db'];

			// Overrides the database table name for configuration parameters
			if (isset($params['table_name'])) {
				$this->_tableName = $params['table_name'];
			}

			// Override the column name for configuration parameters keys
			if (isset($params['keys_column'])) {
				$this->_keysColumn = $params['keys_column'];
			}

			// Set the column name for configuration parameters values
			if (isset($params['values_column'])) {
				$this->_valuesColumn = $params['values_column'];
			}

		} elseif (!$params instanceof PDO) {
			throw new iMSCP_Exception('PDO instance requested for ' . __CLASS__);
		}

		$this->_db = $params;
		$this->_loadAll();
	}

	/**
	 * Allow access as object properties.
	 *
	 * @see set()
	 * @param string $key Configuration parameter key name
	 * @param mixed $value Configuration parameter value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Insert or update a configuration parameter in the database.
	 *
	 * <b>Note:</b> For performances reasons, queries for updates are only done if old and new value of a parameter are
	 * not the same.
	 *
	 * @param string $key Configuration parameter key name
	 * @param mixed $value Configuration parameter value
	 * @return void
	 */
	public function set($key, $value)
	{
		$this->_key = $key;
		$this->_value = $value;

		if (!$this->exists($key)) {
			$this->_insert();
		} elseif ($this->_parameters[$key] != $value) {
			$this->_update();
		} else {
			return;
		}

		$this->_parameters[$key] = $value;
	}

	/**
	 * Retrieve a configuration parameter value.
	 *
	 * @throws iMSCP_Exception
	 * @param string $key Configuration parameter key name
	 * @return mixed Configuration parameter value
	 */
	public function get($key)
	{
		if (!isset($this->_parameters[$key])) {
			throw new iMSCP_Exception("Configuration variable `$key` is missing.");
		}

		return $this->_parameters[$key];
	}

	/**
	 * Checks if a configuration parameters exists.
	 *
	 * @param string $key Configuration parameter key name
	 * @return boolean TRUE if configuration parameter exists, FALSE otherwise
	 */
	public function exists($key)
	{
		return array_key_exists($key, $this->_parameters);
	}

	/**
	 * Replaces all parameters of this object with parameters from another.
	 *
	 * This method replace the parameters values of this object with the same values from another
	 * {@link iMSCP_Config_Handler} object.
	 *
	 * If a key from this object exists in the second object, its value will be replaced by the value from the second
	 * object. If the key exists in the second object, and not in the first, it will be created in the first object.
	 * All keys in this object that don't exist in the second object will be left untouched.
	 *
	 * <b>Note:</b> This method is not recursive.
	 *
	 * @param iMSCP_Config_Handler $config iMSCP_Config_Handler object
	 * @return bool TRUE on success, FALSE otherwise
	 */
	public function replaceWith(iMSCP_Config_Handler $config)
	{
		try {
			$this->_db->beginTransaction();

			parent::replaceWith($config);

			$this->_db->commit();
		} catch(PDOException $e) {
			$this->_db->rollBack();

			return false;
		}

		return true;
	}

	/**
	 * PHP isset() overloading on inaccessible members.
	 *
	 * This method is triggered by calling isset() or empty() on inaccessible members.
	 *
	 * <b>Note:</b> This method will return FALSE if the configuration parameter value is NULL. To test existence of a
	 * configuration parameter, you should use the {@link exists()} method.
	 *
	 * @param string $key Configuration parameter key name
	 * @return boolean TRUE if the parameter exists and its value is not NULL
	 */
	public function __isset($key)
	{
		return isset($this->_parameters[$key]);
	}

	/**
	 * PHP unset() overloading on inaccessible members.
	 *
	 * This method is triggered by calling isset() or empty() on inaccessible members.
	 *
	 * @param  string $key Configuration parameter key name
	 * @return void
	 */
	public function __unset($key)
	{
		$this->del($key);
	}

	/**
	 * Force reload of all configuration parameters from the database.
	 *
	 * This method will remove all the current loaded parameters and reload it from the database.
	 *
	 * @return void
	 */
	public function forceReload()
	{
		$this->_parameters = array();
		$this->_loadAll();
	}

	/**
	 * Returns the count of SQL queries that were executed.
	 *
	 * This method returns the count of queries that were executed since the last call of
	 * {@link reset_queries_counter()} method.
	 *
	 * @throws iMSCP_Exception
	 * @param string $queriesCounterType Query counter type (insert|update)
	 * @return int
	 */
	public function countQueries($queriesCounterType)
	{
		switch ($queriesCounterType) {
			case 'update':
				return $this->_updateQueriesCounter;
				break;
			case 'insert':
				return $this->_insertQueriesCounter;
				break;
			case 'delete':
				return $this->_deleteQueriesCounter;
				break;
			default:
				throw new iMSCP_Exception('Unknown queries counter.');
		}
	}

	/**
	 * Reset a counter of queries.
	 *
	 * @throws iMSCP_Exception
	 * @param string $queriesCounterType Type of query counter (insert|update|delete)
	 * @return void
	 */
	public function resetQueriesCounter($queriesCounterType)
	{
		switch ($queriesCounterType) {
			case 'update':
				$this->_updateQueriesCounter = 0;
				break;
			case 'insert':
				$this->_insertQueriesCounter = 0;
				break;
			case 'delete':
				$this->_deleteQueriesCounter = 0;
				break;
			default:
				throw new iMSCP_Exception('Unknown queries counter.');
		}
	}

	/**
	 * Deletes a configuration parameters from the database.
	 *
	 * @param string $key Configuration parameter key name
	 * @return void
	 */
	public function del($key)
	{
		$this->_key = $key;
		$this->_delete();

		unset($this->_parameters[$key]);
	}

	/**
	 * Load all configuration parameters from the database.
	 *
	 * @throws iMSCP_Exception
	 * @return void
	 */
	protected function _loadAll()
	{
		$query = "SELECT `{$this->_keysColumn}`, `{$this->_valuesColumn}` FROM `{$this->_tableName}`";

		if (($stmt = $this->_db->query($query, PDO::FETCH_ASSOC))) {
			$keyColumn = $this->_keysColumn;
			$valueColumn = $this->_valuesColumn;

			foreach ($stmt->fetchAll() as $row) {
				$this->_parameters[$row[$keyColumn]] = $row[$valueColumn];
			}
		} else {
			throw new iMSCP_Exception('Could not get configuration parameters from database.');
		}
	}

	/**
	 * Store a new configuration parameter in the database.
	 *
	 * @throws iMSCP_Exception_Database
	 * @return void
	 */
	protected function _insert()
	{
		if (!$this->_insertStmt instanceof PDOStatement) {

			$query = "
				INSERT INTO `{$this->_tableName}` (
					`{$this->_keysColumn}`, `{$this->_valuesColumn}`
				) VALUES (
					:index, :value
				)
			";

			$this->_insertStmt = $this->_db->prepare($query);
		}

		if (!$this->_insertStmt->execute(array(':index' => $this->_key, ':value' => $this->_value))) {
			throw new iMSCP_Exception_Database(
				"Unable to insert new entry `{$this->_key}` in config table.");
		} else {
			$this->_insertQueriesCounter++;
		}
	}

	/**
	 * Update a configuration parameter in the database.
	 *
	 * @throws iMSCP_Exception_Database
	 * @return void
	 */
	protected function _update()
	{
		if (!$this->_updateStmt instanceof PDOStatement) {
			$query = "
				UPDATE `{$this->_tableName}` SET `{$this->_valuesColumn}` = :value WHERE `{$this->_keysColumn}` = :index
			";

			$this->_updateStmt = $this->_db->prepare($query);
		}

		if (!$this->_updateStmt->execute(array(':index' => $this->_key, ':value' => $this->_value))) {
			throw new iMSCP_Exception_Database("Unable to update entry `{$this->_key}` in config table.");
		} else {
			$this->_updateQueriesCounter++;
		}
	}

	/**
	 * Deletes a configuration parameter from the database.
	 *
	 * @throws iMSCP_Exception_Database
	 * @return void
	 */
	protected function _delete()
	{
		if (!$this->_deleteStmt instanceof PDOStatement) {
			$query = "DELETE FROM `{$this->_tableName}` WHERE `{$this->_keysColumn}` = :index";
			$this->_deleteStmt = $this->_db->prepare($query);
		}

		if (!$this->_deleteStmt->execute(array(':index' => $this->_key))) {
			throw new iMSCP_Exception_Database('Unable to delete entry in config table.');
		} else {
			$this->_deleteQueriesCounter++;
		}
	}

	/**
	 * Whether or not an offset exists.
	 *
	 * @param mixed $offset An offset to check for existence
	 * @return boolean TRUE on success or FALSE on failure
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->_parameters);
	}

	/**
	 * Returns an associative array that contains all configuration parameters.
	 *
	 * @return array Array that contains configuration parameters
	 */
	public function toArray()
	{
		return $this->_parameters;
	}

	/**
	 * Returns the current element.
	 *
	 * @return mixed Returns the current element
	 */
	public function current()
	{
		return current($this->_parameters);
	}

	/**
	 * Returns the key of the current element.
	 *
	 * @return string|null Return the key of the current element or NULL on failure
	 */
	public function key()
	{
		return key($this->_parameters);
	}

	/**
	 * Moves the current position to the next element.
	 *
	 * @return void
	 */
	public function next()
	{
		next($this->_parameters);
	}

	/**
	 * Rewinds back to the first element of the Iterator.
	 *
	 * <b>Note:</b> This is the first method called when starting a foreach loop. It will not be executed after foreach
	 * loops.
	 *
	 * @return void
	 */
	public function rewind()
	{
		reset($this->_parameters);
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return boolean TRUE on success or FALSE on failure
	 */
	public function valid()
	{
		return array_key_exists(key($this->_parameters), $this->_parameters);
	}
}
