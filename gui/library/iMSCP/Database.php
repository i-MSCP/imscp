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

use iMSCP_Database_Events_Database as DatabaseEvents;
use iMSCP_Database_Events_Statement as DatabaseEventsStatement;
use iMSCP_Database_ResultSet as ResultSet;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsAggregator;
use iMSCP_Events_Manager as EventsManager;
use iMSCP_Exception_Database as DatabaseException;

/**
 * Class iMSCP_Database
 */
class iMSCP_Database
{
    /**
     * @var iMSCP_Database[] Array which contain Database objects, indexed by connection name
     */
    protected static $instance = [];

    /**
     * @var EventsManager
     */
    protected $events;

    /**
     * @var PDO PDO instance.
     */
    protected $db = NULL;

    /**
     * @var int Error code from last error occurred
     */
    protected $lastErrorCode = '';

    /**
     * @var string Message from last error occurred
     */
    protected $lastErrorMessage = '';

    /**
     * @var string Character used to quotes a string
     */
    public $nameQuote = '`';

    /**
     * @var int Transaction save point counter
     */
    protected $transactionSavePointCounter = 0;

    /**
     * Singleton - Make new unavailable
     *
     * Creates a PDO object and connects to the database.
     *
     * According the PDO implementation, a PDOException is raised on error
     * See {@link http://www.php.net/manual/en/pdo.construct.php} for more information about this issue.
     *
     * @throws PDOException|iMSCP_Exception
     * @param string $user Sql username
     * @param string $pass Sql password
     * @param string $type PDO driver
     * @param string $host Mysql server hostname
     * @param string $name Database name
     * @param array $driverOptions OPTIONAL Driver options
     */
    private function __construct($user, $pass, $type, $host, $name, $driverOptions = [])
    {
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
            PDO::ATTR_STATEMENT_CLASS          => ['iMSCP_Database_ResultSet']
        ];

        $this->db = new PDO("$type:host=$host;dbname=$name;charset=utf8", $user, $pass, $driverOptions);
    }

    /**
     * Singleton - Make clone unavailable.
     */
    private function __clone()
    {

    }

    /**
     * Return an event manager instance
     *
     * @param iMSCP_Events_Manager $events
     * @return iMSCP_Events_Manager
     */
    public function events(EventsManager $events = NULL)
    {
        if (NULL !== $events) {
            $this->events = $events;
        } elseif (NULL === $this->events) {
            $this->events = EventsAggregator::getInstance();
        }

        return $this->events;
    }

    /**
     * Establishes the connection to the database
     *
     * Create and returns an new iMSCP_Database object which represents the
     * connection to the database. If a connection with the same identifier
     * is already referenced, the connection is automatically closed and then,
     * the object is recreated.
     *
     * @param string $user SQL username
     * @param string $pass SQL password
     * @param string $type PDO driver
     * @param string $host Mysql server hostname
     * @param string $name Database name
     * @param string $connection OPTIONAL Connection key name
     * @param array $options OPTIONAL Driver options
     * @return iMSCP_Database
     */
    public static function connect($user, $pass, $type, $host, $name, $connection = 'default', $options = NULL)
    {
        if (is_array($connection)) {
            $options = $connection;
            $connection = 'default';
        }

        if (isset(self::$instance[$connection])) {
            self::$instance[$connection] = NULL;
        }

        return self::$instance[$connection] = new self($user, $pass, $type, $host, $name, (array)$options);
    }

    /**
     * Returns a database connection object
     *
     * Each database connection object are referenced by an unique identifier.
     * The default identifier, if not one is provided, is 'default'.
     *
     * @throws DatabaseException
     * @param string $connection Connection key name
     * @return iMSCP_Database
     */
    public static function getInstance($connection = 'default')
    {
        if (!isset(self::$instance[$connection])) {
            throw new DatabaseException(sprintf("The Database connection %s doesn't exist.", $connection));
        }

        return self::$instance[$connection];
    }

    /**
     * Same as getInstance()
     *
     * @throws iMSCP_Exception
     * @param string $connection Connection unique identifier
     * @return self
     * @deprecated Will be removed in a later release; now return self instead of underlying PDO instance
     */
    public static function getRawInstance($connection = 'default')
    {
        return self::getInstance($connection);
    }

    /**
     * Prepares an SQL statement
     *
     * The SQL statement can contains zero or more named or question mark
     * parameters markers for which real values will be substituted when
     * the statement will be executed.
     *
     * See {@link http://www.php.net/manual/en/pdo.prepare.php}
     *
     * @param string $sql Sql statement to prepare
     * @param array $options OPTIONAL Attribute values for the PDOStatement
     *                       object
     * @return PDOStatement|false A PDOStatement instance or FALSE on failure.
     *         If prepared statements are emulated by PDO, FALSE is never
     *         returned.
     */
    public function prepare($sql, $options = NULL)
    {
        $this->events()->dispatch(
            new DatabaseEvents(Events::onBeforeQueryPrepare, ['context' => $this, 'query' => $sql])
        );

        if (is_array($options)) {
            $stmt = $this->db->prepare($sql, $options);
        } else {
            $stmt = $this->db->prepare($sql);
        }

        $this->events()->dispatch(
            new DatabaseEventsStatement(Events::onAfterQueryPrepare, ['context' => $this, 'statement' => $stmt])
        );

        if (!$stmt) {
            $errorInfo = $this->errorInfo();
            $this->lastErrorMessage = $errorInfo[2];

            return false;
        }

        return $stmt;
    }

    /**
     * Executes a SQL Statement or a prepared statement
     *
     * @throws DatabaseException
     * @param ResultSet|string $stmt
     * @param null $parameters
     * @return ResultSet|false
     */
    public function execute($stmt, $parameters = NULL)
    {
        if ($stmt instanceof ResultSet) {
            $this->events()->dispatch(
                new DatabaseEventsStatement(Events::onBeforeQueryExecute, ['context' => $this, 'statement' => $stmt])
            );

            if (NULL === $parameters) {
                $rs = $stmt->execute();
            } else {
                $rs = $stmt->execute((array)$parameters);
            }
        } elseif (is_string($stmt)) {
            $this->events()->dispatch(
                new DatabaseEvents(Events::onBeforeQueryExecute, ['context' => $this, 'query' => $stmt])
            );

            if (is_null($parameters)) {
                $rs = $this->db->query($stmt);
            } else {
                $parameters = func_get_args();
                $rs = call_user_func_array([$this->db, 'query'], $parameters);
            }
        } else {
            throw new DatabaseException('Wrong parameter. Expects either a string or iMSCP_Database_ResultSet object');
        }

        if ($rs) {
            $stmt = ($rs === true) ? $stmt : $rs;
            $this->events()->dispatch(new DatabaseEventsStatement(
                Events::onAfterQueryExecute, ['context' => $this, 'statement' => $stmt]
            ));

            return $stmt;
        }

        $errorInfo = is_string($stmt) ? $this->errorInfo() : $stmt->errorInfo();

        if (isset($errorInfo[2])) {
            $this->lastErrorCode = $errorInfo[0];
            $this->lastErrorMessage = $errorInfo[2];
        } else { // WARN (HY093)
            $errorInfo = error_get_last();
            $this->lastErrorMessage = $errorInfo['message'];
        }

        return false;
    }

    /**
     * Returns the list of the permanent tables from the database
     *
     * @param string|null $like
     * @return array An array which hold list of database tables
     */
    public function getTables($like = NULL)
    {
        if ($like) {
            $stmt = $this->db->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$like]);
        } else {
            $stmt = $this->db->query('SHOW TABLES');
        }

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Returns the Id of the last inserted row
     *
     * @return string Last row identifier that was inserted in database
     */
    public function insertId()
    {
        return $this->db->lastInsertId();
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
     * Quotes a string for use in a query
     *
     * @param string $string The string to be quoted
     * @param null|int $parameterType Provides a data type hint for drivers
     *                                that have alternate quoting styles.
     * @return string A quoted string that is theoretically safe to pass into
     *                an SQL statement
     */
    public function quote($string, $parameterType = NULL)
    {
        return $this->db->quote($string, $parameterType);
    }

    /**
     * Sets an attribute on the database handle
     *
     * See @link http://www.php.net/manual/en/book.pdo.php} PDO guideline for
     * more information about this.
     *
     * @param int $attribute Attribute identifier
     * @param mixed $value Attribute value
     * @return boolean TRUE on success, FALSE on failure
     */
    public function setAttribute($attribute, $value)
    {
        return $this->db->setAttribute($attribute, $value);
    }

    /**
     * Retrieves a PDO database connection attribute
     *
     * @param $attribute
     * @return mixed Attribute value or NULL on failure
     */
    public function getAttribute($attribute)
    {
        return $this->db->getAttribute($attribute);
    }

    /**
     * Initiates a transaction
     *
     * @link http://php.net/manual/en/pdo.begintransaction.php
     * @return void
     */
    public function beginTransaction()
    {
        if ($this->transactionSavePointCounter == 0) {
            $this->db->beginTransaction();
            $this->transactionSavePointCounter++;
            return;
        }

        $this->db->exec("SAVEPOINT TRANSACTION{$this->transactionSavePointCounter}");
        $this->transactionSavePointCounter++;
    }

    /**
     * Commits a transaction
     *
     * @link http://php.net/manual/en/pdo.commit.php
     * @return void
     */
    public function commit()
    {
        if ($this->transactionSavePointCounter < 0) {
            return;
        }

        $this->transactionSavePointCounter--;

        if ($this->transactionSavePointCounter == 0) {
            $this->db->commit();
            return;
        }

        $this->db->exec("RELEASE SAVEPOINT TRANSACTION{$this->transactionSavePointCounter}");
    }

    /**
     * Rolls back a transaction
     *
     * @link http://php.net/manual/en/pdo.rollback.php
     * @return void
     */
    public function rollBack()
    {
        if ($this->transactionSavePointCounter == 0) {
            return;
        }

        $this->transactionSavePointCounter--;

        if ($this->transactionSavePointCounter == 0) {
            try {
                $this->db->rollBack();
            } catch (PDOException $e) {
                // Ignore rollback exception
            }

            return;
        }

        $this->db->exec("ROLLBACK TO SAVEPOINT TRANSACTION{$this->transactionSavePointCounter}");
    }

    /**
     * Checks if inside a transaction
     *
     *
     * @return bool TRUE if a transaction is currently active, FALSE otherwise
     */
    public function inTransaction()
    {
        return $this->db->inTransaction();
    }

    /**
     * Gets the last SQLSTATE error code
     *
     * @return mixed  The last SQLSTATE error code
     */
    public function getLastErrorCode()
    {
        return $this->lastErrorCode;
    }

    /**
     * Gets the last error message
     *
     * This method returns the last error message set by the {@link execute()}
     * or {@link prepare()} methods.
     *
     * @return string Last error message set by the {@link execute()} or
     *                {@link prepare()} methods.
     */
    public function getLastErrorMessage()
    {
        return $this->lastErrorMessage;
    }

    /**
     * Stringified error information
     *
     * This method returns a stringified version of the error information
     * associated with the last database operation.
     *
     * @return string Error information associated with the last database
     *                operation
     */
    public function errorMsg()
    {
        return implode(' - ', $this->db->errorInfo());
    }

    /**
     * Error information associated with the last operation on the database
     *
     * This method returns a array that contains error information associated
     * with the last database operation.
     *
     * @return array Array that contains error information associated with the
     *               last database operation
     */
    public function errorInfo()
    {
        return $this->db->errorInfo();
    }

    /**
     * Returns quote identifier symbol
     *
     * @return string Quote identifier symbol
     */
    public function getQuoteIdentifierSymbol()
    {
        return $this->nameQuote;
    }
}
