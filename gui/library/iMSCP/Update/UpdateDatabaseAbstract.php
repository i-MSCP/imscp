<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

namespace iMSCP\Update;

use iMSCP_Config_Handler_Db as ConfigDb;
use iMSCP_Config_Handler_File as ConfigFile;
use iMSCP_Registry as Registry;

/**
 * Class UpdateDatabaseAbstract
 * @package iMSCP\Update
 */
abstract class UpdateDatabaseAbstract extends UpdateAbstract
{
    /**
     * @var UpdateDatabaseAbstract
     */
    protected static $instance;

    /**
     * @var ConfigFile
     */
    protected $config;

    /**
     * @var ConfigDb
     */
    protected $dbConfig;

    /**
     * Database name being updated
     *
     * @var string
     */
    protected $databaseName;

    /**
     * Tells whether or not a request must be send to the i-MSCP daemon after that
     * all database updates were applied.
     *
     * @var bool
     */
    protected $daemonRequest = false;

    /**
     * @var int Last database update revision
     */
    protected $lastUpdate = 0;

    /**
     * Singleton - Make new unavailable
     */
    public function __construct()
    {
        $this->config = Registry::get('config');
        $this->dbConfig = Registry::get('dbConfig');

        if (!isset($this->config['DATABASE_NAME'])) {
            throw new UpdateException('Database name not found.');
        }

        $this->databaseName = $this->config['DATABASE_NAME'];
    }

    /**
     * Return last database update revision
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * @inheritdoc
     */
    public function applyUpdates()
    {
        ignore_user_abort(true);

        /** @var \iMSCP_Database $db */
        $db = Registry::get('iMSCP_Application')->getDatabase();

        while ($this->isAvailableUpdate()) {
            $revision = $this->getNextUpdate();

            try {
                $updateMethod = 'r' . $revision;
                $queries = (array)$this->$updateMethod();

                if (empty($queries)) {
                    $this->dbConfig['DATABASE_REVISION'] = $revision;
                    continue;
                }

                $db->beginTransaction();

                foreach ($queries as $query) {
                    if (!empty($query)) {
                        $stmt = $db->prepare($query);
                        $stmt->execute($stmt);

                        /** @noinspection PhpStatementHasEmptyBodyInspection */
                        while ($stmt->nextRowset()) {
                            /* https://bugs.php.net/bug.php?id=61613 */
                        };
                    }
                }

                $this->dbConfig['DATABASE_REVISION'] = $revision;

                # Make sure that we are still in transaction due to possible implicite commit
                # See https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html
                if ($db->inTransaction()) {
                    $db->commit();
                }
            } catch (\Exception $e) {
                # Make sure that we are still in transaction due to possible implicite commit
                # See https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                $this->setError(sprintf('Database update %s failed: %s', $revision, $e->getMessage()));
                return false;
            }
        }

        if (PHP_SAPI != 'cli' && $this->daemonRequest) {
            send_request();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function isAvailableUpdate()
    {
        if ($this->getLastAppliedUpdate() < $this->getNextUpdate()) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getLastAppliedUpdate()
    {
        if (!isset($this->dbConfig['DATABASE_REVISION'])) {
            $this->dbConfig['DATABASE_REVISION'] = 1;
        }

        return $this->dbConfig['DATABASE_REVISION'];
    }

    /**
     * @inheritdoc
     */
    public function getNextUpdate()
    {
        $lastAvailableUpdateRevision = $this->lastUpdate;
        $nextUpdateRevision = $this->getLastAppliedUpdate();
        if ($nextUpdateRevision < $lastAvailableUpdateRevision) {
            return ++$nextUpdateRevision;
        }

        return 0;
    }

    /**
     * Catch any database updates that were removed
     *
     * @throws UpdateException
     * @param  string $updateMethod Database update method name
     * @param array $params Params
     * @return null
     */
    public function __call($updateMethod, $params)
    {
        if (!preg_match('/^r[0-9]+$/', $updateMethod)) {
            throw new UpdateException(sprintf('%s is not a valid database update method', $updateMethod));
        }

        return NULL;
    }

    /**
     * Add column in the given table
     *
     * @param string $table Table name
     * @param string $column Column name
     * @param string $columnDefinition Column definition
     * @return null|string SQL statement to be executed
     */
    protected function addColumn($table, $column, $columnDefinition)
    {
        $table = quoteIdentifier($table);
        $stmt = exec_query("SHOW COLUMNS FROM $table LIKE ?", [$column]);

        if (!$stmt->rowCount()) {
            return sprintf('ALTER TABLE %s ADD %s %s', $table, quoteIdentifier($column), $columnDefinition);
        }

        return NULL;
    }

    /**
     * Change column from the given table
     *
     * @param string $table Table name
     * @param string $column Column name
     * @param string $columnDefinition Column definition
     * @return null|string SQL statement to be executed
     */
    protected function changeColumn($table, $column, $columnDefinition)
    {
        $table = quoteIdentifier($table);
        $stmt = exec_query("SHOW COLUMNS FROM $table LIKE ?", [$column]);

        if ($stmt->rowCount()) {
            return sprintf('ALTER TABLE %s CHANGE %s %s', $table, quoteIdentifier($column), $columnDefinition);
        }

        return NULL;
    }

    /**
     * Drop column
     *
     * @param string $table Table name
     * @param string $column Column name
     * @return null|string SQL statement to be executed
     */
    protected function dropColumn($table, $column)
    {
        $table = quoteIdentifier($table);
        $stmt = exec_query("SHOW COLUMNS FROM $table LIKE ?", [$column]);

        if ($stmt->rowCount()) {
            return sprintf('ALTER TABLE %s DROP %s', $table, quoteIdentifier($column));
        }

        return NULL;
    }

    /**
     * Does the given table is known?
     *
     * @param string $table Table name
     * @return bool TRUE if the given table is know, FALSE otherwise
     */
    protected function isKnownTable($table)
    {
        return exec_query('SHOW TABLES LIKE ?', [$table])->rowCount() > 0;
    }

    /**
     * Drop a table
     *
     * @param string $table Table name
     * @return string SQL statement to be executed
     */
    public function dropTable($table)
    {
        return sprintf('DROP TABLE IF EXISTS %s', quoteIdentifier($table));
    }

    /**
     * Rename a table
     *
     * @param string $table Table name
     * @param string $newTableName New table name
     * @return null|string SQL statement to be executed
     */
    protected function renameTable($table, $newTableName)
    {
        $stmt = exec_query('SHOW TABLES LIKE ?', [$table]);

        if ($stmt->rowCount()) {
            $stmt = exec_query('SHOW TABLES LIKE ?', [$newTableName]);

            if (!$stmt->rowCount()) {
                return sprintf('ALTER TABLE %s RENAME TO %s', quoteIdentifier($table), quoteIdentifier($newTableName));
            }
        }

        return NULL;
    }

    /**
     * Add index
     *
     * Be aware that no check is made for duplicate rows. Thus, if you want to add an UNIQUE contraint, you must make
     * sure to remove duplicate rows first. We don't make use of the IGNORE clause for the following reasons:
     *
     * - The IGNORE clause is no standard and do not work with Fast Index Creation (MySQL #Bug #40344)
     * - The IGNORE clause has been removed in MySQL 5.7
     *
     * @param string $table Database table name
     * @param array|string $columns Column name(s) with OPTIONAL key length
     * @param string $indexType Index type (PRIMARY KEY (default), INDEX|KEY, UNIQUE)
     * @param string $indexName Index name (default is autogenerated)
     * @return null|string SQL statement to be executed
     */
    protected function addIndex($table, $columns, $indexType = 'PRIMARY KEY', $indexName = '')
    {
        $table = quoteIdentifier($table);
        $indexType = strtoupper($indexType);
        $columnsTmp = (array)$columns;
        $columns = [];

        // Parse column definitions
        foreach ($columnsTmp as $columnDef) {
            if (preg_match('/^(?P<name>[^(]+)(?P<length>\(\d+\))$/', $columnDef, $matches)) {
                $columns[$matches['name']] = $matches['length'];
            } else {
                $columns[$columnDef] = '';
            }
        }
        unset($columnsTmp);

        $indexName = $indexType == 'PRIMARY KEY' ? 'PRIMARY' : ($indexName == '' ? key($columns) : $indexName);
        $stmt = exec_query("SHOW INDEX FROM $table WHERE KEY_NAME = ?", [$indexName]);

        if (!$stmt->rowCount()) {
            $columnsStr = '';
            foreach ($columns as $column => $length) {
                $columnsStr .= quoteIdentifier($column) . $length . ',';
            }
            unset($columns);

            $indexName = $indexName == 'PRIMARY' ? '' : quoteIdentifier($indexName);
            return sprintf('ALTER TABLE %s ADD %s %s (%s)', $table, $indexType, $indexName, rtrim($columnsStr, ','));
        }

        return NULL;
    }

    /**
     * Drop the given index from the given table
     *
     * @param string $table Table name
     * @param string $indexName Index name
     * @return null|string SQL statement to be executed
     */
    protected function dropIndexByName($table, $indexName = 'PRIMARY')
    {
        $table = quoteIdentifier($table);
        $stmt = exec_query("SHOW INDEX FROM $table WHERE KEY_NAME = ?", [$indexName]);

        if ($stmt->rowCount()) {
            return sprintf('ALTER TABLE %s DROP INDEX %s', $table, quoteIdentifier($indexName));
        }

        return NULL;
    }

    /**
     * Drop any index which belong to the given column in the given table
     *
     * @param string $table Table name
     * @param string $column Column name
     * @return array SQL statements to be executed
     */
    protected function dropIndexByColumn($table, $column)
    {
        $sqlQueries = [];
        $table = quoteIdentifier($table);
        $stmt = exec_query("SHOW INDEX FROM $table WHERE COLUMN_NAME = ?", [$column]);

        if ($stmt->rowCount()) {
            while ($row = $stmt->fetch()) {
                $row = array_change_key_case($row, CASE_UPPER);
                $sqlQueries[] = sprintf('ALTER TABLE %s DROP INDEX %s', $table, quoteIdentifier($row['KEY_NAME']));
            }
        }

        return $sqlQueries;
    }
}
