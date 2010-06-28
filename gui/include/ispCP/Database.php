<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
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
 * @category	ispCP
 * @package		ispCP_Database
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @author		ispCP Team
 * @version		SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 * @filesource
 */

/**
 * This class wrap the PDO abstraction layer
 *
 * @category	ispCP
 * @package		ispCP_Database
 * @author		ispCP Team
 * @todo		Use Exceptions for all errors (will be activated when the
 *	ispCP_ExceptionHandler class will be ready (test in progress)
 */
final class ispCP_Database {

	/**
	 * Pool of Database instances
	 *
	 * @var array
	 */
	private static $_instances = array();

	/**
	 * PDO instance
	 *
	 * @var PDO
	 */
	private $_db = null;

	/**
	 * Character used to quotes a string
	 *
	 * @var string
	 */
	public $nameQuote = '`';

	/**
	 * This class implemente the Singleton design pattern
	 *
	 * According the PDO implementation, an exception is raised on error
	 * See {@link http://www.php.net/manual/en/pdo.construct.php} for more
	 * information about this issue.
	 *
	 * @throws PDOException
	 * @return void
	 */
	private function __construct($user, $pass, $type, $host, $name) {

		$this->_db = new PDO(
			$type . ':host=' . $host . ';dbname=' . $name, $user, $pass
		);

		$this->_db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
	}

	/**
	 * This class implements the Singleton design pattern
	 */
	private function __clone() {}

	/**
	 * Return an instance of this class
	 *
	 * @throws ispCP_Exception
	 * @param string $connection Connection key name
	 * @return Database A Database instance
	 */
	public static function getInstance($connection = 'default') {

		if (!isset(self::$_instances[$connection])) {
			throw new ispCP_Exception(
				'Error: Database error: Not connected to ' . $connection
			);
		}

		return self::$_instances[$connection];
	}

	/**
	 * Return the PDO instance used by a specific instance of this class
	 *
	 * @since 1.0.6
	 * @author Laurent Declercq <laurent.declercq@ispcp.net>
	 * @throws ispCP_Exception
	 * @param $string $connection Connection key name
	 * @return PDO A PDO instance
	 */
	public static function getRawInstance($connection = 'default') {

		if (!isset(self::$_instances[$connection])) {
			throw new ispCP_Exception(
				'Error: Database error: Not connected to ' . $connection
			);
		}

		return self::$_instances[$connection]->_db;
	}

	/**
	 * Get a Database instance
	 *
	 * Create and returns an instance of this class. If one with the same key
	 * name already exists, close it before.
	 *
	 * @see __construct()
	 * @param string $user Sql username
	 * @param string $pass Sql password
	 * @param string $type PDO driver
	 * @param string $host Mysql server hostname
	 * @param string $name Database name
	 * @param string $connection Connection key name
	 * @return Database A Database instance
	 */
	public static function connect($user, $pass, $type, $host, $name,
		$connection = 'default') {

		if (isset(self::$_instances[$connection])) {
			$_instances[$connection]->close();
		}

		return self::$_instances[$connection] = new self(
			$user, $pass, $type, $host, $name
		);
	}

	/**
	 * Sets an attribute
	 *
	 * Sets an attribute on the database handle.
	 *
	 * See @link http://www.php.net/manual/en/book.pdo.php} PDO guideline for
	 * more information about this.
	 *
	 * @since r2013
	 * @author Laurent Declercq <laurent.declercq@ispcp.net>
	 * @param int $attribute Attribute uid
	 * @param mixed $value Attribute value
	 * @return boolean TRUE on success, FALSE on failure
	 */
	public function setAttribute($attribute, $value) {

		return $this->_db->setAttribute($attribute, $value);
	}

	/**
	 * Stringified error information
	 *
	 * This method returns a stringified version of the error information
	 * associated with the last database operation.
	 *
	 * @return string Error information
	 */
	public function ErrorMsg() {

		return implode(' - ', $this->_db->errorInfo());
	}

	/**
	 * Error information associated with the last operation on the database
	 *
	 * This method returns a array that containt error information associated
	 * with the last database operation.
	 *
	 * @return array Error information
	 */
	public function errorInfo() {

		return $this->_db->errorInfo();
	}

	/**
	 * All-In-One method to execute a query
	 *
	 * This method can be used both for execute the prepared queries and the
	 * normal queries. For normal queries, the first parameter should be a
	 * string that represent a SQL statement. For prepared queries, the first
	 * argument should be a PDOstatement instance that represent prepared query.
	 *
	 * The optional second parameter is only used for prepared queries. It can
	 * be a integer or string that represent a parameter value or an array that
	 * contain parameter values.
	 *
	 * @param string|PDOStatement PDOstatement instance or a SQL statement
	 * @param string|int|array parameter values.
	 * @return boolean|int|DatabaseResult Depending of the query type and result
	 *	the returned value can be a DatabaseResult instance or FALSE on failure
	 *	(prepared queries) ; A DatabaseResult or FALSE on faillure for queries
	 *	such as SELECT, EXPLAIN ; An integer that represent the number of
	 *	affected line or A FALSE on failure for queries such as INSERT, UPDATE
	 */
	public function Execute($stmt, $param = array()) {

		if ($stmt instanceof PDOStatement) {
			$rs = $stmt->execute((array) $param);
			$rs = $rs ? new DatabaseResult($stmt) : $rs;
		} else {
			$rs = $this->_db->query($stmt);
			$rs =  is_object($rs) ? new DatabaseResult($rs) : $rs;
		}

		return $rs;
	}

	/**
	 * Prepares an SQL statement
	 *
	 * The SQL statement can contain zero or more named (:name) or question
	 * mark (?) parameter markers for which real values will be substituted when
	 * the statement is executed.
	 *
	 * See {@link http://www.php.net/manual/en/pdo.prepare.php}
	 *
	 * @param string $query SQL statement
	 * @param array $attributes Attribute values for the PDOStatement object 
	 * @return PDOStatement A PDOStatement instance
	 */
	public function Prepare($statement, array $attributes = array()) {

		if (version_compare(PHP_VERSION, '5.2.5', '<')) {
			if (preg_match("/(ALTER |CREATE |DROP |GRANT |REVOKE |FLUSH )/i",
				$statement, $matches) > 0) {

				$this->_db->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, true);
			} else {
				$this->_db->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, false);
			}
		}

		return $this->_db->prepare($statement);
	}

	/**
	 * Returns the list of the permanent tables from the database
	 *
	 * @return array An array that represent a list of the permanent tables
	 */
	public function MetaTables() {

		$tables = array();

		$result = $this->_db->query('SHOW TABLES');

		while ($result instanceof PDOStatement
			&& $row = $result->fetch(PDO::FETCH_NUM)) {
			$tables[] = $row[0];
		}

		return $tables;
	}

	/**
	 * Returns the ID of the last inserted row
	 *
	 * @return string Row ID of the last row that was inserted in database
	 */
	public function Insert_ID() {

		return $this->_db->lastInsertId();
	}

	/**
	 * Retrieve a PDO database connection attribute
	 *
	 * @return mixed Attribute value or NULL on failure
	 */
	public function getAttribute($attribute) {

		return $this->_db->getAttribute($attribute);
	}

	/**
	 *  Initiates a transaction
	 *
	 * @return boolean TRUE on success, FALSE on failure
	 */
	public function StartTrans() {

		$this->_db->beginTransaction();
	}

	/**
	 * Commits a transaction
	 *
	 * @return boolean TRUE on success, FALSE on failure
	 */
	public function CompleteTrans() {

		$this->_db->commit();
	}

	/**
	 * Rolls back the current transaction
	 *
	 * Rolls back the current transaction, as initiated by {@link StartTrans()}.
	 *
	 * @since r2013
	 * @author Laurent Declercq <laurent.declerq@ispcp.net>
	 * @param int $attribute Attribute uid
	 * @param mixed $value Attribute value
	 * @return boolean TRUE on success or FALSE on failure
	 */
	public function RollbackTrans() {

		return $this->_db->rollback();
	}

	/**
	 * This method is not currently used
	 *
	 * @return false
	 */
	public function HasFailedTrans() {

		return false;
	}
}
