<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GPL General Public License
 *   as published by the Free Software Foundation; either version 2.0
 *   of the License, or (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *   GPL General Public License for more details.
 *
 *   You may have received a copy of the GPL General Public License
 *   along with this program.
 *
 *   An on-line copy of the GPL General Public License can be found
 *   http://www.fsf.org/licensing/licenses/gpl.txt
 */

/**
 * @todo separate the 2 classes Database + DatabaseResult in two different files
 */

/**
 * This class wrap the PDO abstraction layer
 */
final class Database {

	protected static $_instances = array();
	protected $_db = null;
	public $nameQuote = '`';

	private function __construct($user, $pass, $type, $host, $name) {
		// Avoid stacktrace and revelation of DB Password with try-catch block
		try {
			$this->_db = new PDO($type . ':host=' . $host . ';dbname=' . $name, $user, $pass);
		} catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();
		}
		$this->_db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
	}

	public static function getInstance($connection = 'default') {
		if (!isset(self::$_instances[$connection])) {
			throw new Exception('Database error: Not connected to ' . $connection);
		}
		return self::$_instances[$connection];
	}

	public static function connect($user, $pass, $type, $host, $name, $connection = 'default') {
		if (isset(self::$_instances[$connection])) {
			$_instances[$connection]->close();
		}
		return self::$_instances[$connection] = new Database($user, $pass, $type, $host, $name);
	}

	/**
	 * Set an attribute
	 *
	 * Sets an attribute on the database handle.
	 * See the PDO guideline for more information about this.
	 *
	 * @since	r2013
	 * @author	Laurent Declercq <l.declercq@nuxwin.com>
	 *
	 * @param	int $attribute Attribute uid
	 * @param	mixed $value Attribute value
	 * @return	boolean Returns TRUE on success or FALSE on failure.
	 */
	public function setAttribute($attribute, $value) {
		return $this->_db->setAttribute($attribute, $value);
	}

	public function ErrorMsg() {
		return implode(' - ', $this->_db->errorInfo());
	}

	public function errorInfo() {
		return $this->_db->errorInfo();
	}

	public function Execute($sql, $param = null) {
		if ($sql instanceof PDOStatement) {
			if (is_array($param)) {
				$ret = $sql->execute($param);
			} elseif (is_string($param) || is_int($param)) {
				$ret = $sql->execute(array($param));
			} else {
				$ret = $sql->execute();
			}
			if ($ret) return new DatabaseResult($sql);
		} else {
			$ret = $this->_db->query($sql);
			if ($ret instanceof PDOStatement) return new DatabaseResult($ret);
		}

		return $ret;
	}

	public function Prepare($sql) {
		if (version_compare(PHP_VERSION, '5.2.5', '<')) {
			if (preg_match("/(ALTER |CREATE |DROP |GRANT |REVOKE |FLUSH )/i", $sql, $matches) > 0) {
				$this->_db->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, true);
			} else {
				$this->_db->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, false);
			}
		}
		return $this->_db->prepare($sql);
	}

	public function MetaTables() {
		$tables = array();
		$result = $this->_db->query('SHOW TABLES');
		while ($result instanceof PDOStatement
			&& $row = $result->fetch(PDO::FETCH_NUM)) {
			$tables[] = $row[0];
		}
		return $tables;
	}

	public function Insert_ID() {
		return $this->_db->lastInsertId();
	}

	public function getAttribute($attribute) {
		return $this->_db->getAttribute($attribute);
	}

	public function StartTrans() {
		$this->_db->beginTransaction();
	}

	public function CompleteTrans() {
		$this->_db->commit();
	}

	/**
	 * Rolls back the current transaction
	 *
	 * Rolls back the current transaction, as initiated
	 * by Database::StartTrans().
	 *
	 * @since	r2013
	 * @author	Laurent Declercq <l.declercq@nuxwin.com>
	 *
	 * @param	int $attribute Attribute uid
	 * @param	mixed $value Attribute value
	 * @return	boolean Returns TRUE on success or FALSE on failure.
	 *
	 * @return void
	 */
	public function RollbackTrans() {
		$this->_db->rollback();
	}

	public function HasFailedTrans() {
		return false;
	}

}

final class DatabaseResult {

	protected $_result = null;
	protected $_fields = null;

	public function __construct($result) {
		if (!$result instanceof PDOStatement) {
			return false;
		}
		$this->_result = $result;
	}

	public function __get($param) {
		if ($param == 'fields') {
			if ($this->_fields === null) {
				$this->_fields = $this->_result->fetch();
			}
			return $this->_fields;
		}
		if ($param == 'EOF') {
			if ($this->_result->rowCount() == 0) {
				return true;
			}
			return !is_null($this->_fields) && !is_array($this->_fields);
		}

		throw new Exception('Unknown parameter: ' . $param);
	}

	public function fields($param) {
		return $this->fields[$param];
	}

	public function RowCount() {
		return $this->_result->rowCount();
	}

	public function RecordCount() {
		return $this->_result->rowCount();
	}

	public function FetchRow() {
		return $this->_result->fetch();
	}

	public function MoveNext() {
		$this->_fields = $this->_result->fetch();
	}

}
