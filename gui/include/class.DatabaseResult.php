<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
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
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

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
