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
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

namespace iMSCP\Database;

use PDO;
use PDOStatement;

/**
 * Class DatabaseResultSet
 *
 * @property mixed EOF
 * @property mixed fields
 *
 * @package iMSCP\Database
 */
class DatabaseResultSet
{
    /**
     * PDOStatement object
     *
     * @var PDOStatement
     */
    protected $_stmt = NULL;

    /**
     * A row from the result set associated with the referenced PDOStatement
     * object.
     *
     * @see fields()
     * @see _get()
     * @var array
     */
    protected $_fields = NULL;

    /**
     * Create a new DatabaseResult object
     *
     * @param PDOStatement $stmt A PDOStatement instance
     * @throws DatabaseException
     */
    public function __construct($stmt)
    {
        if (!($stmt instanceof PDOStatement)) {
            throw new DatabaseException(
                'Argument passed to ' . __METHOD__
                . '() must be a PDOStatement object!'
            );
        }

        $this->_stmt = $stmt;
    }

    /**
     * Php overloading.
     *
     * Php overloading method that allows to fetch the first row in the result
     * set or check if one row exist in the result set.
     *
     * @param string $param
     * @return mixed Depending of the $param value, this method can returns the
     *               first row of a result set or a boolean that indicate if any
     *               rows exists in the result set
     * @throws DatabaseException
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

        throw new DatabaseException("Unknown parameter: `$param`");
    }

    /**
     * Fetches the next row from the current result set.
     *
     * Fetches a row from the result set. The fetch_style parameter determines
     * how the row is returned.
     *
     * @param int $fetchStyle Controls how the next row will be returned to the
     *                        caller. This value must be one of the
     *                        PDO::FETCH_* constants
     * @return mixed The return value of this function on success depends on the
     *               fetch style. In all cases, FALSE is returned on failure.
     * @todo Finish fetch style implementation
     */
    public function fetchRow($fetchStyle = null)
    {
        return $this->_stmt->fetch(
            $fetchStyle ?? DatabaseMySQL::getInstance()->getAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE
            )
        );
    }

    /**
     * Gets column field value from the current row.
     *
     * @param string $param Colum field name
     * @return mixed Column value
     * @see get()
     */
    public function fields($param)
    {
        return $this->fields[$param];
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * This method returns the number of rows affected by the last DELETE,
     * INSERT, or UPDATE SQL statement.
     *
     * If the last SQL statement executed by the associated PDOStatement was a
     * SELECT statement, some RDBMS (like Mysql) may return the number of rows
     * returned by that statement. However, this behaviour is not guaranteed for
     * all RDBMS and should not be relied on for portable applications.
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
     * @return int Number of rows affected by the last SQL statement
     * @see rowCount()
     */
    public function recordCount()
    {
        return $this->_stmt->rowCount();
    }

    /**
     * Fetches all rows from the current result set.
     *
     * Fetches all row from the result set. The fetch_style parameter determines
     * how the rows are returned.
     *
     * @param int $fetchStyle Controls how the next row will be returned to the
     *                        caller. This value must be one of the PDO::FETCH_*
     *                        constants
     * @return mixed The return value of this function on success depends on the
     *               fetch style. In all cases, FALSE is returned on failure.
     * @todo Finish fetch style implementation
     */
    public function fetchAll($fetchStyle = null)
    {
        return $this->_stmt->fetchAll(
            $fetchStyle ?? DatabaseMySQL::getInstance()->getAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE
            ),
        );
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
     * Error information associated with the last operation on the statement
     * handle.
     *
     * @return array Error information
     */
    public function errorInfo()
    {
        return $this->_stmt->errorInfo();
    }

    /**
     * Stringified error information.
     *
     * This method returns a stringified version of the error information
     * associated with the last statement operation.
     *
     * @return string Error information
     */
    public function errorInfoToString()
    {
        return implode(' - ', $this->_stmt->errorInfo());
    }
}
