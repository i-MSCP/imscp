<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

use iMSCP_Config_Handler as ConfigHandler;
use iMSCP_Database as Database;
use iMSCP_Exception as iMSCPException;
use iMSCP_Exception_Database as DatabaseException;
use iMSCP_Registry as Registry;

/**
 * Class to handle configuration parameters from database
 *
 * ConfigHandler adapter class to handle configuration parameters that are
 * stored in database.
 *
 * @property string MAIL_BODY_FOOTPRINTS Mail body footprint
 * @property int FAILED_UPDATE Failed database update
 * @property string PORT_IMSCP_DAEMON i-MSCP daemon service properties
 * @property string USER_INITIAL_LANG User initial language
 * @property int DATABASE_REVISION Database revision
 * @property  int EMAIL_QUOTA_SYNC_MODE Email quota sync mode
 */
class iMSCP_Config_Handler_Db extends ConfigHandler implements Iterator, Serializable
{
    /**
     * @var Database Database instance
     */
    protected $db;

    /**
     * @var array Configuration parameters
     */
    protected $parameters = [];

    /**
     * @var PDOStatement to insert a configuration parameter in the database
     */
    protected $insertStmt;

    /**
     * @var PDOStatement to update a configuration parameter in the database
     */
    protected $updateStmt;

    /**
     * @var PDOStatement PDOStatement to delete a configuration parameter in the database
     */
    protected $deleteStmt;

    /**
     * Variable bound to the PDOStatement instances
     *
     * This variable is bound to the PDOStatement instances that are used by
     * {@link _insert()}, {@link _update()} and {@link _delete()} methods.
     *
     * @var string Configuration parameter key name
     */
    protected $key;

    /**
     * Variable bound to the PDOStatement objects
     *
     * This variable is bound to the PDOStatement instances that are used by
     * both {@link _insert()} and {@link _update()} methods.
     *
     * @var mixed Configuration parameter value
     */
    protected $value;

    /**
     * @var int Counter for SQL update queries
     */
    protected $insertQueriesCounter = 0;

    /**
     * @var int Counter for SQL insert queries
     */
    protected $updateQueriesCounter = 0;

    /**
     * @var int Counter for SQL delete queries
     */
    protected $deleteQueriesCounter = 0;

    /**
     * @var string Database table name for configuration parameters
     */
    protected $tableName = 'config';

    /**
     * @var string Database column name for configuration parameters keys
     */
    protected $keyColumn = 'name';

    /**
     * @var string Database column name for configuration parameters values
     */
    protected $valueColumn = 'value';

    /**
     * @var bool Internal flag indicating whether or not cached dbconfig object
     *           must be flushed
     */
    protected $flushCache = false;

    /**
     * Loads all configuration parameters from the database
     *
     * Parameters:
     *
     * The constructor accepts one or more parameters passed in a array where
     * each key represent a parameter name.
     *
     * For an array, the possible parameters are:
     *
     * - db: A Database name
     * - table_name: Table that contain configuration parameters
     * - key_column: Column name for configuration parameters key names
     * - value_column: Column name for configuration parameters values
     *
     * Note: The three last parameters are optionals.
     *
     * For a single parameter, only a Database instance is accepted.
     *
     * @throws iMSCPException
     * @param Database|array $params A Database instance or an array of
     *                               parameters that contains at least a
     *                               Database instance
     */
    public function __construct($params)
    {
        if (is_array($params)) {
            if (!array_key_exists('db', $params) || !($params['db'] instanceof Database)) {
                throw new iMSCPException('A Database instance is requested for ' . __CLASS__);
            }

            $this->db = $params['db'];

            // Overrides the database table name for configuration parameters
            if (isset($params['table_name'])) {
                $this->tableName = $params['table_name'];
            }

            // Override the column name for configuration parameters keys
            if (isset($params['keys_column'])) {
                $this->keyColumn = $params['keys_column'];
            }

            // Set the column name for configuration parameters values
            if (isset($params['values_column'])) {
                $this->valueColumn = $params['values_column'];
            }
        } elseif (!$params instanceof Database) {
            throw new iMSCPException('Database instance requested for ' . __CLASS__);
        }

        $this->db = $params;
        $this->_loadAll();
    }

    /**
     * Load all configuration parameters from the database
     *
     * @throws iMSCPException
     * @return void
     */
    protected function _loadAll()
    {
        $stmt = $this->db->query(
            sprintf(
                'SELECT %s, %s FROM %s',
                $this->db->quoteIdentifier($this->keyColumn),
                $this->db->quoteIdentifier($this->valueColumn),
                $this->db->quoteIdentifier($this->tableName)
            )
        );

        if (!$stmt) {
            throw new iMSCPException("Couldn't get configuration parameters from database.");
        }

        $this->parameters = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Set Database instance
     *
     * @param Database $db
     */
    public function setDb(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Set table name onto operate
     *
     * @param $tableName
     */
    public function setTable($tableName)
    {
        $this->tableName = (string)$tableName;
    }

    /**
     * Set key column
     *
     * @param $columnName
     */
    public function setKeyColumn($columnName)
    {
        $this->keyColumn = (string)$columnName;
    }

    /**
     * Set value column
     *
     * @param $columnName
     */
    public function setValueColumn($columnName)
    {
        $this->valueColumn = (string)$columnName;
    }

    /**
     * Allow access as object properties
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
     * Insert or update a configuration parameter in the database
     *
     * @param string $key Configuration parameter key name
     * @param mixed $value Configuration parameter value
     * @return void
     */
    public function set($key, $value)
    {
        $this->key = $key;
        $this->value = $value;

        if (!$this->exists($key)) {
            $this->_insert();
        } elseif ($this->parameters[$key] != $value) {
            $this->_update();
        } else {
            return;
        }

        $this->parameters[$key] = $value;
    }

    /**
     * Checks if a configuration parameters exists
     *
     * @param string $key Configuration parameter key name
     * @return boolean TRUE if configuration parameter exists, FALSE otherwise
     */
    public function exists($key)
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Store a new configuration parameter in the database
     *
     * @throws DatabaseException
     * @return void
     */
    protected function _insert()
    {
        if (!$this->insertStmt instanceof PDOStatement) {
            $this->insertStmt = $this->db->prepare(
                sprintf(
                    'INSERT INTO %s (%s, %s) VALUES (?,?)',
                    $this->db->quoteIdentifier($this->tableName),
                    $this->db->quoteIdentifier($this->keyColumn),
                    $this->db->quoteIdentifier($this->valueColumn)
                )
            );

            $this->insertStmt->bindParam(1, $this->key, PDO::PARAM_STR);
            $this->insertStmt->bindParam(2, $this->value, PDO::PARAM_STR);
        }

        if (!$this->insertStmt->execute()) {
            throw new DatabaseException("Couldn't insert new entry `{$this->key}` in config table.");
        }

        $this->flushCache = true;
        $this->insertQueriesCounter++;
    }

    /**
     * Update a configuration parameter in the database
     *
     * @throws DatabaseException
     * @return void
     */
    protected function _update()
    {
        if (!$this->updateStmt instanceof PDOStatement) {
            $this->updateStmt = $this->db->prepare(
                sprintf(
                    'UPDATE %s SET %s = ? WHERE %s = ?',
                    $this->db->quoteIdentifier($this->tableName),
                    $this->db->quoteIdentifier($this->valueColumn),
                    $this->db->quoteIdentifier($this->keyColumn)
                )
            );

            $this->updateStmt->bindParam(1, $this->value, PDO::PARAM_STR);
            $this->updateStmt->bindParam(2, $this->key, PDO::PARAM_STR);
        }

        if (!$this->updateStmt->execute()) {
            throw new DatabaseException("Couldn't update entry `{$this->key}` in config table.");
        }

        $this->flushCache = true;
        $this->updateQueriesCounter++;
    }

    /**
     * Retrieve a configuration parameter value
     *
     * @throws iMSCPException
     * @param string $key Configuration parameter key name
     * @return mixed Configuration parameter value
     */
    public function get($key)
    {
        if (!isset($this->parameters[$key])) {
            throw new iMSCPException("Configuration variable `$key` is missing.");
        }

        return $this->parameters[$key];
    }

    /**
     * Replaces all parameters of this object with parameters from another
     *
     * This method replace the parameters values of this object with the same
     * values from another {@link ConfigHandler} object.
     *
     * If a key from this object exists in the second object, its value will be
     * replaced by the value from the second object. If the key exists in the
     * second object, and not in the first, it will be created in the first
     * object.
     * All keys in this object that don't exist in the second object will be left untouched.
     *
     * This method is not recursive.
     *
     * @param ConfigHandler $config ConfigHandler object
     * @return bool TRUE on success, FALSE otherwise
     */
    public function merge(ConfigHandler $config)
    {
        try {
            $this->db->beginTransaction();
            parent::merge($config);
            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }

        return true;
    }

    /**
     * PHP isset() overloading on inaccessible members
     *
     * This method is triggered by calling isset() or empty() on inaccessible
     * members.
     *
     * This method will return FALSE if the configuration parameter value is
     * NULL. To test existence of a configuration parameter, you should use the
     * {@link exists()} method.
     *
     * @param string $key Configuration parameter key name
     * @return boolean TRUE if the parameter exists and its value is not NULL
     */
    public function __isset($key)
    {
        return isset($this->parameters[$key]);
    }

    /**
     * PHP unset() overloading on inaccessible members
     *
     * This method is triggered by calling isset() or empty() on inaccessible
     * members.
     *
     * @param string $key Configuration parameter key name
     * @return void
     */
    public function __unset($key)
    {
        $this->del($key);
    }

    /**
     * Deletes a configuration parameters from the database
     *
     * @param string $key Configuration parameter key name
     * @return void
     */
    public function del($key)
    {
        $this->key = $key;
        $this->_delete();

        unset($this->parameters[$key]);
    }

    /**
     * Deletes a configuration parameter from the database
     *
     * @throws DatabaseException
     * @return void
     */
    protected function _delete()
    {
        if (!$this->deleteStmt instanceof PDOStatement) {
            $this->deleteStmt = $this->db->prepare(
                sprintf(
                    'DELETE FROM %s WHERE %s = ?',
                    $this->db->quoteIdentifier($this->tableName),
                    $this->db->quoteIdentifier($this->keyColumn)
                )
            );

            $this->deleteStmt->bindParam(1, $this->key, PDO::PARAM_STR);
        }

        if (!$this->deleteStmt->execute()) {
            throw new DatabaseException("Couldn't delete entry in config table.");
        }

        $this->flushCache = true;
        $this->deleteQueriesCounter++;
    }

    /**
     * Force reload of all configuration parameters from the database
     *
     * This method will remove all the current loaded parameters and reload it
     * from the database.
     *
     * @return void
     */
    public function forceReload()
    {
        $this->parameters = [];
        $this->_loadAll();
    }

    /**
     * Returns the count of SQL queries that were executed
     *
     * This method returns the count of queries that were executed since the
     * last call of {@link reset_queries_counter()} method.
     *
     * @throws iMSCPException
     * @param string $queriesCounterType Query counter type (insert|update)
     * @return int
     */
    public function countQueries($queriesCounterType)
    {
        switch ($queriesCounterType) {
            case 'update':
                return $this->updateQueriesCounter;
                break;
            case 'insert':
                return $this->insertQueriesCounter;
                break;
            case 'delete':
                return $this->deleteQueriesCounter;
                break;
            default:
                throw new iMSCPException('Unknown queries counter.');
        }
    }

    /**
     * Reset a counter of queries
     *
     * @throws iMSCPException
     * @param string $queriesCounterType Query counter (insert|update|delete)
     * @return void
     */
    public function resetQueriesCounter($queriesCounterType)
    {
        switch ($queriesCounterType) {
            case 'update':
                $this->updateQueriesCounter = 0;
                break;
            case 'insert':
                $this->insertQueriesCounter = 0;
                break;
            case 'delete':
                $this->deleteQueriesCounter = 0;
                break;
            default:
                throw new iMSCPException('Unknown queries counter.');
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->parameters);
    }

    /**
     * Returns an associative array that contains all configuration parameters
     *
     * @return array Array that contains configuration parameters
     */
    public function toArray()
    {
        return $this->parameters;
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        return current($this->parameters);
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return key($this->parameters);
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        next($this->parameters);
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        reset($this->parameters);
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return array_key_exists(key($this->parameters), $this->parameters);
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->parameters);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->parameters = unserialize($serialized);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if ($this->flushCache) {
            Registry::get('iMSCP_Application')->getCache()->remove('iMSCP_DbConfig');
        }
    }
}
