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
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Database
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Class iMSCP_Database_ResultSet
 *
 * @property mixed EOF
 * @property mixed fields
 *
 * @category    i-MSCP
 * @package     iMSCP_Database
 * @author      ispCP Team
 * @author      iMSCP team
 */
class iMSCP_Database_ResultSet
{
	/**
	 * PDOStatement object
	 *
	 * @var PDOStatement
	 */
	protected $_stmt = null;

	/**
	 * Default fetch mode
	 *
	 * Controls how the next row will be returned to the caller. This value must be one of the PDO::FETCH_* constants.
	 *
	 * @var integer
	 */
	protected $_fetchMode = PDO::FETCH_ASSOC;

	/**
	 * A row from the result set associated with the referenced PDOStatement object
	 *
	 * @see fields()
	 * @see _get()
	 * @var array
	 */
	protected $_fields = null;

	/**
	 * Create a new DatabaseResult object
	 *
	 * @throws iMSCP_Exception_Database
	 * @param PDOStatement $stmt A PDOStatement instance
	 */
	public function __construct($stmt)
	{
		if (!($stmt instanceof PDOStatement)) {
			throw new iMSCP_Exception_Database('Argument passed to ' . __METHOD__ . '() must be a PDOStatement object!');
		}

		$this->_stmt = $stmt;
	}

	/**
	 * Php overloading
	 *
	 * Php overloading method that allows to fetch the first row in the result set or check if one row exist in the
	 * result set
	 *
	 * @throws iMSCP_Exception_Database
	 * @param string $param
	 * @return mixed Depending of the $param value, this method can returns the first row of a result set or a boolean
	 *         that indicate if any rows exists in the result set
	 */
	public function __get($param)
	{
		if ($param == 'fields') {
			if (is_null($this->_fields)) {
				$this->_fields = $this->fetchRow();
			}

			return $this->_fields;
		}

		if ($param == 'EOF') {
			if ($this->_stmt->rowCount() == 0) {
				return true;
			}

			return !is_null($this->_fields) && !is_array($this->_fields);
		}

		throw new iMSCP_Exception_Database("Unknown parameter: `$param`");
	}

	/**
	 * Gets column field value from the current row
	 *
	 * @see get()
	 * @param string $param Colum field name
	 * @return mixed Column value
	 */
	public function fields($param)
	{
		return $this->fields[$param];
	}

	/**
	 * Returns the number of rows affected by the last SQL statement
	 *
	 * This method returns the number of rows affected by the last DELETE,
	 * INSERT, or UPDATE SQL statement
	 *
	 * If the last SQL statement executed by the associated PDOStatement was a SELECT statement, some RDBMS (like Mysql)
	 * may return the number of rows returned by that statement. However, this behaviour is not guaranteed for all RDBMS
	 * and should not be relied on for portable applications.
	 *
	 * @return int Number of rows affected by the last SQL statement
	 */
	public function rowCount()
	{
		return $this->_stmt->rowCount();
	}

	/**
	 * Alias of the rowCount() method
	 *
	 * @see rowCount()
	 * @return int Number of rows affected by the last SQL statement
	 */
	public function recordCount()
	{
		return $this->_stmt->rowCount();
	}

	/**
	 * Set fetch style globally
	 *
	 * This methods allows to set fetch style globally for all rows.
	 *
	 * Note: Currently, all fetch style are not implemented.
	 *
	 * @param  int $fetchStyle Controls how the next row will be returned to the caller. This value must be one of the
	 *                         PDO::FETCH_* constants
	 * @return void
	 * @todo Finish fetch style implementation
	 */
	public function setFetchStyle($fetchStyle)
	{
		$this->_fetchMode = $fetchStyle;
	}

	/**
	 * Fetches the next row from the current result set
	 *
	 * Fetches a row from the result set. The fetch_style parameter determines
	 * how the row is returned.
	 *
	 * @param int $fetchStyle Controls how the next row will be returned to the caller. This value must be one of the
	 *                        PDO::FETCH_* constants
	 * @return mixed The return value of this function on success depends on the fetch style. In all cases, FALSE is
	 *               returned on failure.
	 * @todo Finish fetch style implementation
	 */
	public function fetchRow($fetchStyle = null)
	{
		$fetchStyle = is_null($fetchStyle) ? $this->_fetchMode : $fetchStyle;

		return $this->_stmt->fetch($fetchStyle);
	}

	/**
	 * Fetches all rows from the current result set
	 *
	 * Fetches all row from the result set. The fetch_style parameter determines how the rows are returned.
	 *
	 * @param int $fetchStyle Controls how the next row will be returned to the
	 * caller. This value must be one of the PDO::FETCH_* constants
	 * @return mixed The return value of this function on success depends on the
	 * fetch style. In all cases, FALSE is returned on failure.
	 * @todo Finish fetch style implementation
	 */
	public function fetchAll($fetchStyle = null)
	{
		$fetchStyle = is_null($fetchStyle) ? $this->_fetchMode : $fetchStyle;

		return $this->_stmt->fetchAll($fetchStyle);
	}

	/**
	 * Fetches the next row from the current result set
	 *
	 * @return void
	 */
	public function moveNext()
	{
		$this->_fields = $this->fetchRow();
	}

	/**
	 * Error information associated with the last operation on the statement handle
	 *
	 * @return array Error information
	 */
	public function errorInfo()
	{
		return $this->_stmt->errorInfo();
	}

	/**
	 * Stringified error information
	 *
	 * This method returns a stringified version of the error information associated with the last statement operation.
	 *
	 * @return string Error information
	 */
	public function errorInfoToString()
	{
		return implode(' - ', $this->_stmt->errorInfo());
	}
}
