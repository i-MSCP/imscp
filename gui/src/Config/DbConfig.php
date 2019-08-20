<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpMissingParentConstructorInspection
 * PhpUnused
 */

declare(strict_types=1);

namespace iMSCP\Config;

use iMSCP\Database\DatabaseException;
use iMSCP\Database\DatabaseMySQL;
use iMSCP\Exception\Exception;
use iMSCP\Registry;
use Iterator;
use PDOException;
use PDOStatement;
use Serializable;

/**
 * Class DbConfig
 *
 * Class to handle configuration parameters from a database.
 *
 * @property string MAIL_BODY_FOOTPRINTS Mail body footprint
 * @property int FAILED_UPDATE Failed database update
 * @property string PORT_IMSCP_DAEMON i-MSCP daemon service properties
 * @property string USER_INITIAL_LANG User initial language
 * @property int DATABASE_REVISION Database revision
 * @property  int EMAIL_QUOTA_SYNC_MODE Email quota sync mode
 * @package iMSCP\Config
 */
class DbConfig extends ArrayConfig implements Iterator, Serializable
{
    /**
     * @var DatabaseMySQL Database instance
     */
    protected $db;

    /**
     * @var array Configuration parameters
     */
    protected $parameters = [];

    /**
     * @var PDOStatement to insert a configuration parameter in the database
     */
    protected $insertStmt = NULL;

    /**
     * @var PDOStatement to update a configuration parameter in the database
     */
    protected $updateStmt = NULL;

    /**
     * @var PDOStatement PDOStatement to delete a configuration parameter in the
     *                   database
     */
    protected $deleteStmt = NULL;

    /**
     * Variable bound to the PDOStatement instances
     *
     * This variable is bound to the PDOStatement instances that are used by
     * {@link _insert()}, {@link _update()} and {@link _delete()} methods.
     *
     * @var string Configuration parameter key name
     */
    protected $_key = NULL;

    /**
     * Variable bound to the PDOStatement objects
     *
     * This variable is bound to the PDOStatement instances that are used by
     * both {@link _insert()} and {@link _update()} methods.
     *
     * @var mixed Configuration parameter value
     */
    protected $_value = NULL;

    /**
     * @var int Counter for SQL update queries
     */
    protected $_insertQueriesCounter = 0;

    /**
     * @var int Counter for SQL insert queries
     */
    protected $_updateQueriesCounter = 0;

    /**
     * @var int Counter for SQL delete queries
     */
    protected $_deleteQueriesCounter = 0;

    /**
     * @var string Database table name for configuration parameters
     */
    protected $_tableName = 'config';

    /**
     * @var string Database column name for configuration parameters keys
     */
    protected $_keysColumn = 'name';

    /**
     * @var string Database column name for configuration parameters values
     */
    protected $_valuesColumn = 'value';

    /**
     * @var bool Internal flag indicating whether or not cached DbConfig object
     *           must be flushed
     */
    protected $flushCache = false;

    /**
     * Loads all configuration parameters from the database.
     *
     * <b>Parameters:</b>
     *
     * The constructor accepts one or more parameters passed in a array where
     * each key represent a parameter name.
     *
     * For an array, the possible parameters are:
     *
     * - db: A Database instance
     * - table_name: Table that contain configuration parameters
     * - key_column: Column name for configuration parameters key names
     * - value_column: Column name for configuration parameters values
     *
     * <b>Note:</b> The three last parameters are optionals.
     *
     * For a single parameter, only a Database instance is accepted.
     *
     * @param DatabaseMySQL|array $params A Database instance or an array of
     *                                    parameters that contains at least a
     *                                    Database instance
     * @throws Exception
     */
    public function __construct($params)
    {
        if (is_array($params)) {
            if (!array_key_exists('db', $params)
                || !($params['db'] instanceof DatabaseMySQL)
            ) {
                throw new Exception(
                    'A Database instance is requested for ' . __CLASS__
                );
            }

            $this->db = (string)$params['db'];

            // Overrides the database table name for configuration parameters
            if (isset($params['table_name'])) {
                $this->_tableName = (string)$params['table_name'];
            }

            // Override the column name for configuration parameters keys
            if (isset($params['keys_column'])) {
                $this->_keysColumn = (string)$params['keys_column'];
            }

            // Set the column name for configuration parameters values
            if (isset($params['values_column'])) {
                $this->_valuesColumn = (string)$params['values_column'];
            }

        } elseif (!$params instanceof DatabaseMySQL) {
            throw new Exception('Database instance requested for ' . __CLASS__);
        }

        $this->db = $params;
        $this->_loadAll();
    }

    /**
     * Load all configuration parameters from the database.
     *
     * @return void
     * @throws Exception
     */
    protected function _loadAll()
    {
        $stmt = $this->db->execute(
            "
                SELECT `{$this->_keysColumn}`, `{$this->_valuesColumn}`
                FROM `{$this->_tableName}`
            "
        );

        if (!$stmt) {
            throw new Exception(
                "Couldn't load configuration parameters from database."
            );
        }

        $keyColumn = $this->_keysColumn;
        $valueColumn = $this->_valuesColumn;

        foreach ($stmt->fetchAll() as $row) {
            $this->parameters[$row[$keyColumn]] = $row[$valueColumn];
        }

    }

    /**
     * Set Database instance.
     *
     * @param DatabaseMySQL $db
     */
    public function setDb(DatabaseMySQL $db)
    {
        $this->db = $db;
    }

    /**
     * Set table name onto operate.
     *
     * @param $tableName
     */
    public function setTable($tableName)
    {
        $this->_tableName = (string)$tableName;
    }

    /**
     * Set key column.
     *
     * @param $columnName
     */
    public function setKeyColumn($columnName)
    {
        $this->_keysColumn = (string)$columnName;
    }

    /**
     * Set value column.
     *
     * @param $columnName
     */
    public function setValueColumn($columnName)
    {
        $this->_valuesColumn = (string)$columnName;
    }

    /**
     * Allow access as object properties.
     *
     * @param string $key Configuration parameter key name
     * @param mixed $value Configuration parameter value
     * @return void
     * @throws DatabaseException
     * @see set()
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Insert or update a configuration parameter in the database.
     *
     * @param string $key Configuration parameter key name
     * @param mixed $value Configuration parameter value
     * @return void
     * @throws DatabaseException
     */
    public function set($key, $value)
    {
        $this->_key = $key;
        $this->_value = $value;

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
     * Checks if a configuration parameters exists.
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
     * @return void
     * @throws DatabaseException
     */
    protected function _insert()
    {
        if (!$this->insertStmt instanceof PDOStatement) {
            $this->insertStmt = $this->db->prepare(
                "
                    INSERT INTO `{$this->_tableName}` (
                        `{$this->_keysColumn}`, `{$this->_valuesColumn}`
                    ) VALUES (
                        ?, ?
                    )
                "
            );
        }

        if (!$this->db->execute(
            $this->insertStmt, [$this->_key, $this->_value]
        )) {
            throw new DatabaseException(
                "Couldn't insert new entry `{$this->_key}` in config table."
            );
        }

        $this->flushCache = true;
        $this->_insertQueriesCounter++;
    }

    /**
     * Update a configuration parameter in the database
     *
     * @return void
     * @throws DatabaseException
     */
    protected function _update()
    {
        if (!$this->updateStmt instanceof PDOStatement) {
            $this->updateStmt = $this->db->prepare(
                "
                    UPDATE `{$this->_tableName}`
                    SET `{$this->_valuesColumn}` = ?
                    WHERE `{$this->_keysColumn}` = ?
                "
            );
        }

        if (!$this->db->execute(
            $this->updateStmt, [$this->_value, $this->_key]
        )) {
            throw new DatabaseException(
                "Couldn't update entry `{$this->_key}` in config table."
            );
        }

        $this->flushCache = true;
        $this->_updateQueriesCounter++;
    }

    /**
     * Retrieve a configuration parameter value.
     *
     * @param string $key Configuration parameter key name
     * @return mixed Configuration parameter value
     * @throws Exception
     */
    public function get($key)
    {
        if (!isset($this->parameters[$key])) {
            throw new Exception("Configuration variable `$key` is missing.");
        }

        return $this->parameters[$key];
    }

    /**
     * Replaces all parameters of this object with parameters from another.
     *
     * This method replace the parameters values of this object with the same
     * values from another {@link ConfigHandler} object.
     *
     * If a key from this object exists in the second object, its value will be
     * replaced by the value from the second object. If the key exists in the
     * second object, and not in the first, it will be created in the first
     * object.
     * All keys in this object that don't exist in the second object will be
     * left untouched.
     *
     * This method is not recursive.
     *
     * @param ArrayConfig $config ConfigHandler object
     * @return bool TRUE on success, FALSE otherwise
     */
    public function merge(ArrayConfig $config)
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
     * PHP unset() overloading on inaccessible members.
     *
     * This method is triggered by calling isset() or empty() on inaccessible
     * members.
     *
     * @param string $key Configuration parameter key name
     * @return void
     * @throws Exception
     */
    public function __unset($key)
    {
        $this->del($key);
    }

    /**
     * Deletes a configuration parameters from the database.
     *
     * @param string $key Configuration parameter key name
     * @return void
     * @throws Exception
     */
    public function del($key)
    {
        $this->_key = $key;
        $this->_delete();

        unset($this->parameters[$key]);
    }

    /**
     * Deletes a configuration parameter from the database
     *
     * @return void
     * @throws Exception
     */
    protected function _delete()
    {
        if (!$this->deleteStmt instanceof PDOStatement) {
            $this->deleteStmt = $this->db->prepare(
                "
                    DELETE FROM `{$this->_tableName}`
                    WHERE `{$this->_keysColumn}` = ?
                "
            );
        }

        if (!$this->db->execute($this->deleteStmt, $this->_key)) {
            throw new Exception(
                "Couldn't delete entry in config table."
            );
        }

        $this->flushCache = true;
        $this->_deleteQueriesCounter++;
    }

    /**
     * Force reload of all configuration parameters from the database.
     *
     * This method will remove all the current loaded parameters and reload it
     * from the database.
     *
     * @return void
     * @throws Exception
     */
    public function forceReload()
    {
        $this->parameters = [];
        $this->_loadAll();
    }

    /**
     * Returns the count of SQL queries that were executed.
     *
     * This method returns the count of queries that were executed since the
     * last call of {@link reset_queries_counter()} method.
     *
     * @param string $queriesCounterType Query counter type (insert|update)
     * @return int
     * @throws Exception
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
                throw new Exception('Unknown queries counter.');
        }
    }

    /**
     * Reset a counter of queries
     *
     * @param string $queriesCounterType Query counter (insert|update|delete)
     * @return void
     * @throws Exception
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
                throw new Exception('Unknown queries counter.');
        }
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function current()
    {
        return current($this->parameters);
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return key($this->parameters);
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        next($this->parameters);
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        reset($this->parameters);
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        return array_key_exists(key($this->parameters), $this->parameters);
    }


    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return serialize($this->parameters);
    }

    /**
     * @inheritDoc
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
            Registry::get('iMSCP_Application')
                ->getCache()
                ->remove('iMSCP_DbConfig');
        }
    }
}
