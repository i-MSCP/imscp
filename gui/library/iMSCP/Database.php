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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Database
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Class iMSCP_Database.
 *
 * @category    i-MSCP
 * @package     iMSCP_Database
 * @author      ispCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * TODO: use platform adapters (Mysql, Postgresql...)
 */
class iMSCP_Database
{
	/**
	 * @var iMSCP_Database[] Array which contain Database objects, indexed by connection name
	 */
	protected static $_instances = array();

	/**
	 * @var iMSCP_Events_Manager
	 */
	protected $_events;

	/**
	 * @var PDO PDO instance.
	 */
	protected $_db = null;

	/**
	 * @var int Error code from last error occurred
	 */
	protected $_lastErrorCode = '';

	/**
	 * @var string Message from last error occurred
	 */
	protected $_lastErrorMessage = '';

	/**
	 * @var string Character used to quotes a string
	 */
	public $nameQuote = '`';

	/**
	 * @var int Transaction counter which allow nested transactions
	 */
	protected $transactionCounter = 0;

	/**
	 * Singleton - Make new unavailable
	 *
	 * Creates a PDO object and connects to the database.
	 *
	 * According the PDO implementation, a PDOException is raised on error
	 * See {@link http://www.php.net/manual/en/pdo.construct.php} for more information about this issue.
	 *
	 * @throws PDOException
	 * @param string $user Sql username
	 * @param string $pass Sql password
	 * @param string $type PDO driver
	 * @param string $host Mysql server hostname
	 * @param string $name Database name
	 * @param array $options OPTIONAL Driver options
	 * @return iMSCP_Database
	 */
	private function __construct($user, $pass, $type, $host, $name, $options = array())
	{
		$this->_db = new PDO($type . ':host=' . $host . ';dbname=' . $name, $user, $pass, $options);

		// Set error mode
		$this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
	public function events(iMSCP_Events_Manager $events = null)
	{
		if (null !== $events) {
			$this->_events = $events;
		} elseif (null === $this->_events) {
			$this->_events = iMSCP_Events_Aggregator::getInstance();
		}

		return $this->_events;
	}

	/**
	 * Establishes the connection to the database
	 *
	 * Create and returns an new iMSCP_Database object which represents the connection to the database. If a connection
	 * with the same identifier is already referenced, the connection is automatically closed and then, the object is
	 * recreated.
	 *
	 * @param string $user Sql username
	 * @param string $pass Sql password
	 * @param string $type PDO driver
	 * @param string $host Mysql server hostname
	 * @param string $name Database name
	 * @param string $connection OPTIONAL Connection key name
	 * @param array $options OPTIONAL Driver options
	 * @return iMSCP_Database An iMSCP_Database instance that represents the connection to the database
	 */
	public static function connect($user, $pass, $type, $host, $name, $connection = 'default', $options = null)
	{
		if (is_array($connection)) {
			$options = $connection;
			$connection = 'default';
		}

		if (isset(self::$_instances[$connection])) {
			self::$_instances[$connection] = null;
		}

		return self::$_instances[$connection] = new self($user, $pass, $type, $host, $name, (array)$options);
	}

	/**
	 * Returns a database connection object
	 *
	 * Each database connection object are referenced by an unique identifier. The default identifier, if not one is
	 * provided, is 'default'.
	 *
	 * @throws iMSCP_Exception_Database
	 * @param string $connection Connection key name
	 * @return iMSCP_Database A Database instance that represents the connection to the database
	 * @todo Rename the method name to 'getConnection' (Sounds better)
	 */
	public static function getInstance($connection = 'default')
	{
		if (!isset(self::$_instances[$connection])) {
			throw new iMSCP_Exception_Database(sprintf("The Database connection %s doesn't exist.", $connection));
		}

		return self::$_instances[$connection];
	}

	/**
	 * Returns the PDO object linked to the current database connection object
	 *
	 * @throws iMSCP_Exception
	 * @param string $connection Connection unique identifier
	 * @return PDO A PDO instance
	 */
	public static function getRawInstance($connection = 'default')
	{
		if (!isset(self::$_instances[$connection])) {
			throw new iMSCP_Exception_Database(sprintf("The Database connection %s doesn't exist.", $connection));
		}

		return self::$_instances[$connection]->_db;
	}

	/**
	 * Prepares an SQL statement
	 *
	 * The SQL statement can contains zero or more named or question mark parameters markers for which real values will
	 * be substituted when the statement will be executed.
	 *
	 * See {@link http://www.php.net/manual/en/pdo.prepare.php}
	 *
	 * @param string $sql Sql statement to prepare
	 * @param array $options OPTIONAL Attribute values for the PDOStatement object
	 * @return PDOStatement A PDOStatement instance or FALSE on failure. If prepared statements are emulated by PDO,
	 *                        FALSE is never returned.
	 */
	public function prepare($sql, $options = null)
	{
		$this->events()->dispatch(
			new iMSCP_Database_Events_Database(
				iMSCP_Events::onBeforeQueryPrepare, array('context' => $this, 'query' => $sql)
			)
		);

		if (is_array($options)) {
			$stmt = $this->_db->prepare($sql, $options);
		} else {
			$stmt = $this->_db->prepare($sql);
		}

		$this->events()->dispatch(
			new iMSCP_Database_Events_Statement(
				iMSCP_Events::onAfterQueryPrepare, array('context' => $this, 'statement' => $stmt)
			)
		);

		if (!$stmt) {
			$errorInfo = $this->errorInfo();
			$this->_lastErrorMessage = $errorInfo[2];

			return false;
		}

		return $stmt;
	}

	/**
	 * Executes a SQL Statement or a prepared statement
	 *
	 * <b>SQL Statement:</b>
	 *
	 * For a SQL statement, the first argument should be a string that represents SQL statement to prepare and execute.
	 * All data inside the query should be properly escaped for prevent any SQL code injection.
	 *
	 * For a SQL statement, you may also pass additional arguments. They will be treated as though you called
	 * PDOStatement::setFetchMode() on the resultant PDOStatement object that is wrapped by the DatabaseResult object.
	 *
	 * <i>example:</i>
	 * <code>
	 * $db->execute('SELECT * FROM `config`;', PDO::FETCH_INTO, new stdClass);
	 * </code>
	 *
	 * <b>Prepared statement:</b>
	 *
	 * For a prepared statement, the first argument should be a PDOStatement object that represents a prepared statement.
	 * As second argument, and only if the prepared statement has parameter markers, you must pass an array, an integer
	 * or a string that represents data to bind to the placeholders.
	 *
	 * <b>Note:</b> string or integer can only be used when only one parameter marker is present in the prepared
	 * statement and only for question mark placeholder. For named placeholders you must always pass data in an array.
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
	 * @param  string|PDOStatement $stmt A PDOStatement objet or a string that represents an SQL statement.
	 * @param null|string|array $parameters OPTIONAL data to bind to the placeholders, or an integer that represents the
	 *                                      Fetch mode for SQL statement. The fetch mode must be one of the PDO::FETCH_*
	 *                                      constants.
	 *
	 * @internal param mixed $colno OPTIONAL parameter for SQL statement only. Can be a colum number, an object, a
	 *                              classname (depending of the Fetch mode used).
	 *
	 * @internal param array $object OPTIONAL parameter for SQL statements only. Can be an array that contains
	 *                               constructor arguments. (See PDO::FETCH_CLASS)
	 * @return bool|iMSCP_Database_ResultSet
	 */
	public function execute($stmt, $parameters = null)
	{
		if ($stmt instanceof PDOStatement) {
			$this->events()->dispatch(
				new iMSCP_Database_Events_Statement(
					iMSCP_Events::onBeforeQueryExecute, array('context' => $this, 'statement' => $stmt)
				)
			);

			if (null === $parameters) {
				$rs = $stmt->execute();
			} else {
				$rs = $stmt->execute((array)$parameters);
			}
		} else {
			$this->events()->dispatch(
				new iMSCP_Database_Events_Database(
					iMSCP_Events::onBeforeQueryExecute, array('context' => $this, 'query' => $stmt)
				)
			);

			if (is_null($parameters)) {
				$rs = $this->_db->query($stmt);
			} else {
				$parameters = func_get_args();
				$rs = call_user_func_array(array($this->_db, 'query'), $parameters);
			}
		}

		if ($rs) {
			$stmt = ($rs === true) ? $stmt : $rs;

			$this->events()->dispatch(
				new iMSCP_Database_Events_Statement(
					iMSCP_Events::onAfterQueryExecute, array('context' => $this, 'statement' => $stmt)
				)
			);

			return new iMSCP_Database_ResultSet($stmt);
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
	 * Returns the list of the permanent tables from the database
	 *
	 * @param $string|null $like
	 * @return array An array which hold list of database tables
	 */
	public function getTables($like = null)
	{
		if($like) {
			$stmt = $this->_db->prepare('SHOW TABLES LIKE ?');
			$stmt->execute(array($like));
		} else {
			$stmt = $this->_db->query('SHOW TABLES');
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
		return $this->_db->lastInsertId();
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
	 * @param null|int $parameterType Provides a data type hint for drivers that have alternate quoting styles.
	 * @return string A quoted string that is theoretically safe to pass into an SQL statement
	 */
	public function quote($string, $parameterType = null)
	{
		return $this->_db->quote($string, $parameterType);
	}

	/**
	 * Sets an attribute on the database handle
	 *
	 * See @link http://www.php.net/manual/en/book.pdo.php} PDO guideline for more information about this.
	 *
	 * @param int $attribute Attribute identifier
	 * @param mixed $value Attribute value
	 * @return boolean TRUE on success, FALSE on failure
	 */
	public function setAttribute($attribute, $value)
	{
		return $this->_db->setAttribute($attribute, $value);
	}

	/**
	 * Retrieves a PDO database connection attribute
	 *
	 * @param $attribute
	 * @return mixed Attribute value or NULL on failure
	 */
	public function getAttribute($attribute)
	{
		return $this->_db->getAttribute($attribute);
	}

	/**
	 * Initiates a transaction
	 *
	 * @link http://php.net/manual/en/pdo.begintransaction.php
	 * @return bool Returns true on success or false on failure.
	 */
	public function beginTransaction()
	{
		if(!$this->transactionCounter) {
			$this->transactionCounter++;
			return $this->_db->beginTransaction();
		}

		return true;
	}

	/**
	 * Commits a transaction
	 *
	 * @link http://php.net/manual/en/pdo.commit.php
	 * @return bool Returns true on success or false on failure.
	 */
	public function commit()
	{
		if($this->transactionCounter) {
			if(!--$this->transactionCounter) {
				return $this->_db->commit();
			}

			return true;
		}

		return false;
	}

	/**
	 * Rolls back a transaction
	 *
	 * @link http://php.net/manual/en/pdo.rollback.php
	 * @return bool Returns true on success or false on failure.
	 */
	public function rollBack()
	{
		if($this->transactionCounter) {
			$this->transactionCounter = 0;
			return $this->_db->rollBack();
		}

		return false;
	}

	/**
	 * Gets the last SQLSTATE error code
	 *
	 * @return mixed  The last SQLSTATE error code
	 */
	public function getLastErrorCode()
	{
		return $this->_lastErrorCode;
	}

	/**
	 * Gets the last error message
	 *
	 * This method returns the last error message set by the {@link execute()} or {@link prepare()} methods.
	 *
	 * @return string Last error message set by the {@link execute()} or {@link prepare()} methods.
	 */
	public function getLastErrorMessage()
	{
		return $this->_lastErrorMessage;
	}

	/**
	 * Stringified error information
	 *
	 * This method returns a stringified version of the error information associated with the last database operation.
	 *
	 * @return string Error information associated with the last database operation
	 */
	public function errorMsg()
	{
		return implode(' - ', $this->_db->errorInfo());
	}

	/**
	 * Error information associated with the last operation on the database
	 *
	 * This method returns a array that contains error information associated with the last database operation.
	 *
	 * @return array Array that contains error information associated with the last
	 *               database operation
	 */
	public function errorInfo()
	{
		return $this->_db->errorInfo();
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
