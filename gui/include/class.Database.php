<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
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
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 */

/**
 * This class wrap the PDO abstraction layer
 */
final class Database {

	private static $_instances = array();
	private $_db = null;
	public $nameQuote = '`';

	/**
	 * Constructor is a Singleton ans can only called by itsself
	 */
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
	 * Sets an attribute
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
