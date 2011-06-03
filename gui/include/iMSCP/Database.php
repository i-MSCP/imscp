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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Database
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @version     SVN: $Id$
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * This class wrap the PDO abstraction layer.
 *
 * @category    i-MSCP
 * @package     iMSCP_Database
 * @author      ispCP Team
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 */
class iMSCP_Database
{
    /**
     * Database connections objects.
     *
     * @var array
     */
    protected static $_instances = array();

    /**
     * PDO instance.
     *
     * @var PDO
     */
    protected $_db = null;


    /**
     * Error code from last error occurred.
     *
     * @var int
     */
    protected $_lastErrorCode = '';

    /**
     * Message from last error occurred.
     *
     * @var string
     */
    protected $_lastErrorMessage = '';

    /**
     * Character used to quotes a string.
     *
     * @var string
     */
    public $nameQuote = '`';

    /**
     * Creates a PDO object and connects to the database.
     *
     * According the PDO implementation, a PDOException is raised on error
     * See {@link http://www.php.net/manual/en/pdo.construct.php} for more
     * information about this issue.
     *
     * <b>Note:</b> This class implements the Singleton design pattern
     *
     * @throws PDOException
     * @param string $user          Sql username
     * @param string $pass          Sql password
     * @param string $type          PDO driver
     * @param string $host          Mysql server hostname
     * @param string $name          Database name
     * @param array $driver_options OPTIONAL Driver options
     * @return iMSCP_Database
     */
    private function __construct($user, $pass, $type, $host, $name,
        $driver_options = array())
    {

        $this->_db = new PDO($type . ':host=' . $host . ';dbname=' . $name, $user,
            $pass, $driver_options);

        // @todo: Bad for future support of another RDBMS.
        $this->_db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }

    /**
     * This class implements the Singleton design pattern
     */
    private function __clone()
    {
    }

    /**
     * Establishes the connection to the database.
     *
     * Create and returns an new iMSCP_Database object that represents the
     * connection to the database. If a connection with the same identifier is
     * already referenced, the connection is automatically closed and then, the
     * object is recreated.
     *
     * @see iMSCP_Bootstrap()
     * @param string $user          Sql username
     * @param string $pass          Sql password
     * @param string $type          PDO driver
     * @param string $host          Mysql server hostname
     * @param string $name          Database name
     * @param string $connection    OPTIONAL Connection key name
     * @param array $driver_options OPTIONAL Driver options
     * @return iMSCP_Database       An iMSCP_Database instance that represents the
     *                              connection to the database
     */
    public static function connect($user, $pass, $type, $host, $name,
        $connection = 'default', $driver_options = null)
    {
        if (is_array($connection)) {
            $driver_options = $connection;
            $connection = 'default';
        }

        if (isset(self::$_instances[$connection])) {
            self::$_instances[$connection] = null;
        }

        return self::$_instances[$connection] = new self(
            $user, $pass, $type, $host, $name, (array)$driver_options
        );
    }

    /**
     * Returns a database connection object.
     *
     * Each database connection object are referenced by an unique identifier.
     * The default identifier, if not one is provided, is 'default'.
     *
     * @throws iMSCP_Exception_Database
     * @param string $connection    Connection key name
     * @return iMSCP_Database       A Database instance that represents the
     *                              connection to the database
     * @todo Rename the method name to 'getConnection' (Sounds better)
     */
    public static function getInstance($connection = 'default')
    {
        if (!isset(self::$_instances[$connection])) {
            throw new iMSCP_Exception_Database("The Database connection $connection doesn't exists!");
        }

        return self::$_instances[$connection];
    }

    /**
     * Returns the PDO object linked to the current database connection object
     *
     * @since 1.0.7
     * @author Laurent Declercq <laurent.declercq@i-mscp.net>
     * @throws iMSCP_Exception
     * @param string $connection    Connection unique identifier
     * @return PDO                  A PDO instance
     */
    public static function getRawInstance($connection = 'default')
    {
        if (!isset(self::$_instances[$connection])) {
            throw new iMSCP_Exception_Database(
                "The Database connection $connection doesn't exists!");
        }

        return self::$_instances[$connection]->_db;
    }

    /**
     * Prepares a SQL statement
     *
     * The SQL statement can contains zero or more named or question mark parameters
     * markers for which real values willbe substituted when the statement will be
     * executed.
     *
     * See {@link http://www.php.net/manual/en/pdo.prepare.php}
     *
     * @param string $sql           Sql statement to be prepared
     * @param array $driver_options OPTIONAL Attribute values for the PDOStatement
     *                              object
     * @return PDOStatement         A PDOStatement instance or FALSE on failure. If
     *                              prepared statements are emulated by PDO, FALSE
     *                              is never returned.
     */
    public function prepare($sql, $driver_options = null)
    {
        if (version_compare(PHP_VERSION, '5.2.5', '<')) {
            if (preg_match('/(ALTER |CREATE |DROP |GRANT |REVOKE |FLUSH )/i', $sql)) {
                $this->_db->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, true);
            } else {
                $this->_db->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, false);
            }
        }

        if (is_array($driver_options)) {
            $stmt = $this->_db->prepare($sql, $driver_options);
        } else {
            $stmt = $this->_db->prepare($sql);
        }

        if (!$stmt) {
            $errorInfo = $this->errorInfo();
            $this->_lastErrorMessage = $errorInfo[2];

            return false;
        }

        return $stmt;
    }

    /**
     * Executes a SQL Statement or a prepared statement.
     *
     * <b>SQL Statement:</b>
     *
     * For a SQL statement, the first argument should be a string that represents SQL
     * statement to prepare and execute. All data inside the query should be properly
     * escaped for prevent any SQL code injection.
     *
     * For a SQL statement, you may also pass additional arguments. They will be
     * treated as though you called PDOStatement::setFetchMode() on the resultant
     * PDOStatement object that is wrapped by the DatabaseResult object.
     *
     * <i>example:</i>
     * <code>
     * $db->execute('SELECT * FROM `config`;', PDO::FETCH_INTO, new stdClass);
     * </code>
     *
     * <b>Prepared statement:</b>
     *
     * For a prepared statement, the first argument should be a PDOStatement object
     * that represents a prepared statement. As second argument, and only if the
     * prepared statement has parameter markers, you must pass an array, an integer
     * or a string that represents data to bind to the placeholders.
     *
     * <b>Note:</b> string or integer can only be used when only one parameter marker
     * is present in the prepared statement and only for question mark placeholder.
     * For named placeholders you must always pass data in an array.
     *
     * Important:
     *  You cannot mix both parameters markers type in the same SQL statement.
     *
     * <i>example:</i>
     * <code>
     * // With only one question mark:
     * $db->execute('SELECT * FROM `config` WHERE `name` = ?;', 'NAME'));
     *
     * // With many question marks:
     * $db->execute(
     *         'SELECT * FROM `config` WHERE `name` = ? AND `value` = ?;',
     *         array('NAME', 'VALUE')
     * );
     *
     * // With named placeholders:
     * $db->execute(
     *         'SELECT * FROM `config` WHERE `name` = :name;',
     *         array(':name' => 'NAME')
     * );
     * </code>
     *
     * @param  string|PDOStatement $stmt    A PDOStatement objet or a string that
     *                                      represents an SQL statement.
     * @param null|string|array $parameters OPTIONAL data to bind to the placeholders,
     *                                      or an integer that represents the Fetch
     *                                      mode for SQL statement. The fetch mode
     *                                      must be one of the PDO::FETCH_* constants.
     * @internal param mixed $colno         OPTIONAL parameter for SQL statement only.
     *                                      Can be a colum number, an object, a class
     *                                      name (depending of the Fetch mode used).
     *
     * @internal param array $object        OPTIONAL parameter for SQL statements only.
     *                                      Can be an array that contains constructor
     *                                      arguments. (See PDO::FETCH_CLASS)
     * @return false|iMSCP_Database_ResultSet
     */
    public function execute($stmt, $parameters = null)
    {
        if ($stmt instanceof PDOStatement) {
            if (null === $parameters) {
                $rs = $stmt->execute();
            } else {
                $rs = $stmt->execute((array)$parameters);
            }
        } elseif (null == $parameters) {
            $rs = $this->_db->query($stmt);
        } else {
            $parameters = func_get_args();
            $rs = call_user_func_array(array($this->_db, 'query'), $parameters);
        }

        if ($rs) {
            return new iMSCP_Database_ResultSet($rs === true ? $stmt : $rs);
        } else {
            $errorInfo = is_string($stmt) ? $this->errorInfo() : $stmt->errorInfo();

            if (isset($errorInfo[2])) {
                $this->_lastErrorCode = $errorInfo[0];
                $this->_lastErrorMessage = $errorInfo[2];
            } else { // WARN (HY093)
                $errorInfo = error_get_last();
                $this->_lastErrorMessage = $errorInfo['message'];
            }

            return false;
        }
    }

    /**
     * Returns the list of the permanent tables from the database.
     *
     * @return array    An array that represents a list of the permanent tables
     */
    public function metaTables()
    {
        $tables = array();

        $result = $this->_db->query('SHOW TABLES');

        while ($result instanceof PDOStatement &&
               ($row = $result->fetch(PDO::FETCH_NUM))
        ) {
            $tables[] = $row[0];
        }

        return $tables;
    }

    /**
     * Returns the Id of the last inserted row.
     *
     * @return string   Last row identifier that was inserted in database
     */
    public function insertId()
    {
        return $this->_db->lastInsertId();
    }

    /**
     * Quotes a string for use in a query.
     *
     * @author Laurent Declercq <laurent.declercq@i-mscp.net>
     * @since 1.0.0 (iMSCP)
     * @link i-mscp.net
     * @param $string                   The string to be quoted
     * @param null|int $parameterType   Provides a data type hint for drivers that
     *                                  have alternate quoting styles.
     * @return string                   A quoted string that is theoretically safe to
     *                                  pass into an SQL statement
     */
    public function quote($string, $parameterType = null)
    {
        return $this->_db->quote($string, $parameterType);
    }

    /**
     * Sets an attribute on the database handle.
     *
     * See @link http://www.php.net/manual/en/book.pdo.php} PDO guideline for more
     * information about this.
     *
     * @since r2013
     * @author Laurent Declercq <laurent.declercq@i-mscp.net>
     * @param int $attribute    Attribute uid
     * @param mixed $value      Attribute value
     * @return boolean          TRUE on success, FALSE on failure
     */
    public function setAttribute($attribute, $value)
    {
        return $this->_db->setAttribute($attribute, $value);
    }

    /**
     * Retrieves a PDO database connection attribute.
     *
     * @param $attribute
     * @return mixed    Attribute value or NULL on failure
     */
    public function getAttribute($attribute)
    {
        return $this->_db->getAttribute($attribute);
    }

    /**
     *  Initiates a transaction.
     *
     * @return boolean  TRUE on success, FALSE on failure
     */
    public function startTransaction()
    {
        $this->_db->beginTransaction();
    }

    /**
     * Commits a transaction.
     *
     * @return boolean  TRUE on success, FALSE on failure
     */
    public function completeTransaction()
    {
        $this->_db->commit();
    }

    /**
     * Rolls back the current transaction.
     *
     * @return bool TRUE on success or FALSE on failure
     */
    public function rollbackTransaction()
    {

        return $this->_db->rollback();
    }

    /**
     * Gets the last SQLSTATE error code.
     *
     * @since 1.0.7
     * @author Laurent Declercq <laurent.declercq@i-mscp.net>
     * @return mixed    The last SQLSTATE error code
     */
    public function getLastErrorCode()
    {
        return $this->_lastErrorCode;
    }

    /**
     * Gets the last error message.
     *
     * This method returns the last error message set by the {@link execute()} or
     * {@link prepare()} methods.
     *
     * @author Laurent Declercq <laurent.declercq@i-mscp.net>
     * @since 1.0.7
     * @return string   Last error message set by the {@link execute()} or
     *                  {@link prepare()} methods.
     */
    public function getLastErrorMessage()
    {
        return $this->_lastErrorMessage;
    }

    /**
     * Stringified error information.
     *
     * This method returns a stringified version of the error information associated
     * with the last database operation.
     *
     * @return string   Error information associated with the last database operation
     */
    public function errorMsg()
    {
        return implode(' - ', $this->_db->errorInfo());
    }

    /**
     * Error information associated with the last operation on the database.
     *
     * This method returns a array that contains error information associated with
     * the last database operation.
     *
     * @return array    Array that contains error information associated with the
     *                  last database operation
     */
    public function errorInfo()
    {

        return $this->_db->errorInfo();
    }

    /**
     * Returns quote identifier symbol.
     *
     * @author Laurent Declercq <l.declercq@nuxwin.com>
     * @since iMSCP 1.0.0
     * @return string Quote identifier symbol
     */
    public function getQuoteIdentifierSymbol()
    {
        return $this->nameQuote;
    }
}
