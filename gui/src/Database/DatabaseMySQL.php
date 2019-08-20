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
 * PhpUnhandledExceptionInspection
 * PhpDocMissingThrowsInspection
 */

declare(strict_types=1);

namespace iMSCP\Database;

use iMSCP\Event\EventAggregator;
use iMSCP\Event\EventManagerInterface;
use iMSCP\Event\Events;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Class iMSCP_Database
 */
class DatabaseMySQL
{
    /**
     * @var DatabaseMySQL[] Array which contain Database objects, indexed by
     *                      connection name
     */
    protected static $instances = [];

    /**
     * @var string Character used to quotes a string
     */
    public $nameQuote = '`';

    /**
     * @var EventAggregator
     */
    protected $events;

    /**
     * @var PDO PDO instance.
     */
    protected $pdo = NULL;

    /**
     * @var int Error code from last error occurred
     */
    protected $lastErrorCode = '';

    /**
     * @var string Message from last error occurred
     */
    protected $lastErrorMessage = '';

    /**
     * @var int Transaction counter which allow nested transactions
     */
    protected $transactionCounter = 0;

    /**
     * Singleton - Make new unavailable.
     *
     * Creates a PDO object and connects to the database.
     *
     * According the PDO implementation, a PDOException is raised on error
     * See {@link http://www.php.net/manual/en/pdo.construct.php} for more
     * information about this issue.
     *
     * @param string $user Sql username
     * @param string $pass Sql password
     * @param string $type PDO driver
     * @param string $host Mysql server hostname
     * @param string $name Database name
     * @param array $driverOptions OPTIONAL Driver options
     * @throws PDOException
     */
    private function __construct(
        $user, $pass, $type, $host, $name, $driverOptions = []
    )
    {
        $driverOptions += [
            PDO::ATTR_CASE                     => PDO::CASE_NATURAL,
            PDO::ATTR_DEFAULT_FETCH_MODE       => PDO::FETCH_ASSOC,
            # FIXME should be FALSE but we must first review all SQL queries
            PDO::ATTR_EMULATE_PREPARES         => true,
            PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
            // Useless as long PDO::ATTR_EMULATE_PREPARES is TRUE
            // As long as ATTR_EMULATE_PREPARES is TRUE, numeric type will be
            // returned as string
            // PDO::ATTR_STRINGIFY_FETCHES     => true,
            PDO::MYSQL_ATTR_INIT_COMMAND       =>
                "SET SESSION sql_mode = 'NO_AUTO_CREATE_USER', "
                . "SESSION group_concat_max_len = 65535",
            // FIXME should be FALSE but we must first review all SQL queries
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ];

        $this->pdo = new PDO(
            "$type:host=$host;dbname=$name;charset=utf8mb4",
            $user,
            $pass,
            $driverOptions
        );
    }

    /**
     * Establishes the connection to the database.
     *
     * Create and returns an new iMSCP_Database object which represents the
     * connection to the database. If a connection with the same identifier is
     * already referenced, the connection is automatically closed and then, the
     * object is recreated.
     *
     * @param string $user Sql username
     * @param string $pass Sql password
     * @param string $type PDO driver
     * @param string $host Mysql server hostname
     * @param string $name Database name
     * @param string $connection OPTIONAL Connection key name
     * @param array $options OPTIONAL Driver options
     * @return DatabaseMySQL
     */
    public static function connect(
        $user,
        $pass,
        $type,
        $host,
        $name,
        $connection = 'default',
        $options = NULL
    )
    {
        if (is_array($connection)) {
            $options = $connection;
            $connection = 'default';
        }

        if (isset(self::$instances[$connection])) {
            self::$instances[$connection] = NULL;
        }

        return self::$instances[$connection] = new self(
            $user, $pass, $type, $host, $name, (array)$options
        );
    }

    /**
     * Same as getInstance().
     *
     * @param string $connection Connection unique identifier
     * @return self
     * @deprecated Will be removed in a later release; now return self instead
     *             of underlying PDO instance
     */
    public static function getRawInstance($connection = 'default')
    {
        return self::getInstance($connection);
    }

    /**
     * Returns a database connection object.
     *
     * Each database connection object are referenced by an unique identifier.
     * The default identifier, if not one is provided, is 'default'.
     *
     * @param string $connection Connection key name
     * @return DatabaseMySQL
     */
    public static function getInstance($connection = 'default')
    {
        if (!isset(self::$instances[$connection])) {
            throw new DatabaseException(sprintf(
                "The Database connection %s doesn't exist.", $connection
            ));
        }

        return self::$instances[$connection];
    }

    /**
     * Return underlying PDO instance.
     *
     * @param string $connection
     * @return PDO
     */
    public static function getPDO($connection = 'default')
    {
        return self::getInstance($connection)->pdo;
    }

    /**
     * Prepares an SQL statement.
     *
     * The SQL statement can contains zero or more named or question mark
     * parameters markers for which real values will be substituted when the
     * statement will be executed.
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
        $this->events()->dispatch(new DatabaseEvent(
            Events::onBeforeQueryPrepare, ['context' => $this, 'query' => $sql]
        ));

        if (is_array($options)) {
            $stmt = $this->pdo->prepare($sql, $options);
        } else {
            $stmt = $this->pdo->prepare($sql);
        }

        $this->events()->dispatch(new DatabaseStatementEvent(
            Events::onAfterQueryPrepare,
            ['context' => $this, 'statement' => $stmt]
        ));

        if (!$stmt) {
            $errorInfo = $this->errorInfo();
            $this->lastErrorMessage = $errorInfo[2];

            return false;
        }

        return $stmt;
    }

    /**
     * Return an event aggregator instance.
     *
     * @param EventAggregator $events
     * @return EventManagerInterface
     */
    public function events(EventAggregator $events = NULL)
    {
        if (NULL !== $events) {
            $this->events = $events;
        } elseif (NULL === $this->events) {
            $this->events = EventAggregator::getInstance();
        }

        return $this->events;
    }

    /**
     * Error information associated with the last operation on the database.
     *
     * This method returns a array that contains error information associated
     * with the last database operation.
     *
     * @return array Array that contains error information associated with the
     *               last database operation
     */
    public function errorInfo()
    {
        return $this->pdo->errorInfo();
    }

    /**
     * Executes a SQL Statement or a prepared statement.
     *
     * @param PDOStatement|string $stmt
     * @param null $parameters
     * @return DatabaseResultSet|false
     */
    public function execute($stmt, $parameters = NULL)
    {
        if ($stmt instanceof PDOStatement) {
            $this->events()->dispatch(new DatabaseStatementEvent(
                Events::onBeforeQueryExecute,
                ['context' => $this, 'statement' => $stmt]
            ));

            if (NULL === $parameters) {
                $rs = $stmt->execute();
            } else {
                $rs = $stmt->execute((array)$parameters);
            }
        } elseif (is_string($stmt)) {
            $this->events()->dispatch(new DatabaseEvent(
                Events::onBeforeQueryExecute,
                ['context' => $this, 'query' => $stmt]
            ));

            if (is_null($parameters)) {
                $rs = $this->pdo->query($stmt);
            } else {
                $parameters = func_get_args();
                $rs = call_user_func_array([$this->pdo, 'query'], $parameters);
            }
        } else {
            throw new DatabaseException(
                'Wrong parameter. Expects either a string or PDOStatement object'
            );
        }

        if ($rs) {
            $stmt = ($rs === true) ? $stmt : $rs;
            $this->events()->dispatch(new DatabaseStatementEvent(
                Events::onAfterQueryExecute,
                ['context' => $this, 'statement' => $stmt]
            ));

            return new DatabaseResultSet($stmt);
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
            $stmt = $this->pdo->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$like]);
        } else {
            $stmt = $this->pdo->query('SHOW TABLES');
        }

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Returns the Id of the last inserted row.
     *
     * @return string Last row identifier that was inserted in database
     */
    public function insertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Quote identifier.
     *
     * @param string $identifier Identifier (table or column name)
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * Quotes a string for use in a query.
     *
     * @param string $string The string to be quoted
     * @param null|int $parameterType Provides a data type hint for drivers that
     *                                have alternate quoting styles.
     * @return string A quoted string that is theoretically safe to pass into an
     *                SQL statement
     */
    public function quote($string, $parameterType = NULL)
    {
        return $this->pdo->quote($string, $parameterType);
    }

    /**
     * Sets an attribute on the database handle.
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
        return $this->pdo->setAttribute($attribute, $value);
    }

    /**
     * Retrieves a PDO database connection attribute.
     *
     * @param $attribute
     * @return mixed Attribute value or NULL on failure
     */
    public function getAttribute($attribute)
    {
        return $this->pdo->getAttribute($attribute);
    }

    /**
     * Initiates a transaction.
     *
     * @link http://php.net/manual/en/pdo.begintransaction.php
     * @return void
     */
    public function beginTransaction()
    {
        if ($this->transactionCounter == 0) {
            $this->pdo->beginTransaction();
        } else {
            $this->pdo->exec(
                "SAVEPOINT TRANSACTION{$this->transactionCounter}"
            );
        }

        $this->transactionCounter++;
    }

    /**
     * Commits a transaction.
     *
     * @link http://php.net/manual/en/pdo.commit.php
     * @return void
     */
    public function commit()
    {
        $this->transactionCounter--;

        if ($this->transactionCounter == 0) {
            $this->pdo->commit();
            return;
        }

        $this->pdo->exec(
            "RELEASE SAVEPOINT TRANSACTION{$this->transactionCounter}"
        );
    }

    /**
     * Rolls back a transaction.
     *
     * @link http://php.net/manual/en/pdo.rollback.php
     * @return void
     */
    public function rollBack()
    {
        $this->transactionCounter--;

        if ($this->transactionCounter == 0) {
            try {
                $this->pdo->rollBack();
            } catch (PDOException $e) {
                // Ignore rollback exception
            }

            return;
        }

        $this->pdo->exec(
            "ROLLBACK TO SAVEPOINT TRANSACTION{$this->transactionCounter}"
        );
    }

    /**
     * Checks if inside a transaction.
     *
     *
     * @return bool TRUE if a transaction is currently active, FALSE otherwise
     */
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
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
     * Gets the last error message.
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
        return implode(' - ', $this->pdo->errorInfo());
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

    /**
     * Singleton - Make clone unavailable.
     */
    private function __clone()
    {

    }
}
