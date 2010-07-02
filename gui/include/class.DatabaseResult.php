<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
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
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

/**
 * DatabaseResult class -  Wrap the PDOStatement class
 *
 */
final class DatabaseResult {

	/**
	 * PDOStatement object
	 *
	 * @var PDOStatement
	 */
	protected $_result = null;

	/**
	 * 
	 */
	protected $_fields = null;

	/**
	 * Should be documented
	 *
	 * @param PDOStatement $result A PDOStatement instance
	 * @return bool FALSE if $result is not a PDOStatement instance
	 */
	public function __construct($result) {

		if (!$result instanceof PDOStatement) {
			return false;
		}

		$this->_result = $result;
	}

	/**
	 * Should be documented
	 *
	 * @throws Exception
	 * @param  $param
	 * @return bool
	 */
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

	/**
	 * Should be documented
	 *
	 * @param  $param
	 * @return
	 */
	public function fields($param) {

		return $this->fields[$param];
	}

	/**
	 * Should be documented
	 *
	 * @return
	 */
	public function rowCount() {

		return $this->_result->rowCount();
	}

	/**
	 * Should be documented
	 *
	 * @return
	 */
	public function recordCount() {

		return $this->_result->rowCount();
	}

	/**
	 * Should be documented
	 *
	 * @return
	 */
	public function fetchRow() {

		return $this->_result->fetch();
	}

	/**
	 * Should be documented
	 *
	 * @return void
	 */
	public function moveNext() {

		$this->_fields = $this->_result->fetch();
	}

}
