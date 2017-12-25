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

use iMSCP\Database\Events\Statement as StatementEvent;
use iMSCP\Database\ResultSet as ResultSet;
use iMSCP_Events as Events;
use iMSCP_Events_Manager as EventsManager;
use iMSCP_Registry as Registry;

/**
 * Class iMSCP_Database
 */
class iMSCP_Database extends PDO
{
    /**
     * @var EventsManager
     */
    protected $em;

    /**
     * @var int Transaction save point counter
     */
    protected $transactionSavePointCounter = 0;

    /**
     * iMSCP_Database constructor.
     *
     * @throws PDOException
     * @param string $user Sql username
     * @param string $pass Sql password
     * @param string $type PDO driver
     * @param string $host Mysql server hostname
     * @param string $name Database name
     * @param array $driverOptions OPTIONAL Driver options
     */
    public function __construct($user, $pass, $type, $host, $name, $driverOptions = [])
    {
        $this->em = Registry::get('iMSCP_Application')->getEventsManager();

        $driverOptions += [
            PDO::ATTR_CASE                     => PDO::CASE_NATURAL,
            PDO::ATTR_DEFAULT_FETCH_MODE       => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES         => true, # FIXME should be FALSE but we must first review all SQL queries
            PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
            // Useless as long PDO::ATTR_EMULATE_PREPARES is TRUE
            // As long as ATTR_EMULATE_PREPARES is TRUE, numeric type will be returned as string
            // PDO::ATTR_STRINGIFY_FETCHES     => true,
            PDO::MYSQL_ATTR_INIT_COMMAND       => "SET SESSION sql_mode = 'NO_AUTO_CREATE_USER', SESSION group_concat_max_len = 65535",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // FIXME should be FALSE but we must first review all SQL queries
            PDO::ATTR_STATEMENT_CLASS          => ['iMSCP\Database\ResultSet', [$this->em]]
        ];

        parent::__construct("$type:host=$host;dbname=$name;charset=utf8", $user, $pass, $driverOptions);
    }

    /**
     * Returns main i-MSCP application database object (transitional)
     *
     * @return iMSCP_Database
     * @deprecated WIll be removed in a later release
     */
    public static function getRawInstance()
    {
        return static::getInstance();
    }

    /**
     * Returns main i-MSCP application database object (transitional)
     *
     * @return iMSCP_Database
     * @deprecated WIll be removed in a later release
     */
    public static function getInstance()
    {
        return Registry::get('iMSCP_Application')->getDatabase();
    }

    /**
     * Executes a SQL Statement or a prepared statement
     *
     * @param ResultSet $stmt
     * @param array $parameters OPTIONAL Input parameters
     * @return ResultSet|false
     * @deprecated Will be removed in a later release
     */
    public function execute($stmt, $parameters = NULL)
    {
        return $stmt->execute($parameters);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object
     *
     * @param string $statement The SQL statement to prepare and execute. Data
     *                          inside the query should be properly escaped.
     * @param int $mode The fetch mode must be one of the PDO::FETCH_*
     *                  constants
     * @param mixed $arg3 The second and following parameters are the same as
     *                    the parameters for PDOStatement::setFetchMode.
     * @param array $ctorargs [optional] Arguments of custom class constructor
     *                        when the $mode parameter is set to
     *                        PDO::FETCH_CLASS
     * @return ResultSet|false a ResultSet object, or FALSE on failure
     */
    public function query($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = NULL, array $ctorargs = [])
    {
        $event = new StatementEvent($statement);
        $event->setName(Events::onBeforeQueryExecute);
        $this->em->dispatch($event);
        /** @var ResultSet $stmt */
        $stmt = func_num_args() > 1
            ? call_user_func_array(['parent', 'query'], func_get_args())
            : parent::query($statement);
        $event->setName(Events::onAfterQueryExecute);
        $this->em->dispatch($event);
        return $stmt;
    }

    /**
     * @inheritdoc
     */
    public function exec($statement)
    {
        $event = new StatementEvent($statement);
        $event->setName(Events::onBeforeQueryExecute);
        $this->em->dispatch($event);
        $ret = parent::exec($statement);
        $event->setName(Events::onAfterQueryExecute);
        $this->em->dispatch($event);
        return $ret;
    }

    /**
     * Returns the Id of the last inserted row
     *
     * @param string $name OPTIONAL Name of the sequence object from which the
     *                     ID should be returned
     * @return string Last row identifier that was inserted in database
     * @deprecated Will be removed in a later release. Use lastInsertId() instead.
     */
    public function insertId($name = NULL)
    {
        return $this->lastInsertId($name);
    }

    /**
     * Quote identifier
     *
     * @param string $identifier Identifier (table or column name)
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * Initiates a transaction
     *
     * @return void
     */
    public function beginTransaction()
    {
        if ($this->transactionSavePointCounter == 0) {
            parent::beginTransaction();
            $this->transactionSavePointCounter++;
            return;
        }

        parent::exec("SAVEPOINT TRANSACTION_{$this->transactionSavePointCounter}");
        $this->transactionSavePointCounter++;
    }

    /**
     * Commits a transaction
     *
     * @return void
     */
    public function commit()
    {
        $this->transactionSavePointCounter--;

        if ($this->transactionSavePointCounter == 0) {
            parent::commit();
            return;
        }

        parent::exec("RELEASE SAVEPOINT TRANSACTION_{$this->transactionSavePointCounter}");
    }

    /**
     * Rolls back a transaction
     *
     * @return void
     */
    public function rollBack()
    {
        if ($this->transactionSavePointCounter == 0) {
            return;
        }

        $this->transactionSavePointCounter--;

        if ($this->transactionSavePointCounter == 0) {
            parent::rollBack();
            return;
        }

        parent::exec("ROLLBACK TO SAVEPOINT TRANSACTION_{$this->transactionSavePointCounter}");
    }
}
