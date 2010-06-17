<?php
/**
 * ispCP ω (OMEGA) complete domain backup/restore tool
 * Restore application
 *
 * @copyright 	2010 Thomas Wacker
 * @author 		Thomas Wacker <zuhause@thomaswacker.de>
 * @version 	SVN: $Id$
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
 */

define('ISPCP_LOG_ERROR', 	0);
define('ISPCP_LOG_WARNING',	1);
define('ISPCP_LOG_INFO',	2);
define('ISPCP_LOG_DEBUG',	3);

abstract class BaseController
{
	/**
	 * current log level
	 */
	public $log_level = ISPCP_LOG_ERROR;
	/**
	 * number of error messages
	 */
	protected $errorCount = 0;

	/**
	 * output message dependend to log level
	 * @param string $message message to log
	 * @param integer $level log level (see literals ISPCP_LOG_*)
	 */
	protected function logMessage($message, $level = ISPCP_LOG_ERROR)
	{
	    if ($this->log_level >= $level) {
	        echo $message . "\n";
			flush();
	    }
	    if ($level == ISPCP_LOG_ERROR) {
	    	$this->errorCount++;
	    }
	}

	/**
	 * Execute shell command and return exit code
	 * @param string $cmd complete command line
	 * @param array $a returned lines
	 * @return integer exit code
	 */
	protected function shellExecute($cmd, &$a)
	{
		$this->logMessage($cmd, ISPCP_LOG_INFO);
		return exec($cmd, $a);
	}

	/**
	 * Get array for SQL prepared statements
	 * @param array $a array with values
	 * @param array $what array of parameter names
	 * @param array $defaults array of default values
	 * @return array parameterized keys
	 */
	protected function paramDBArray($a, $what)
	{
		$result = array();

		foreach ($what as $key => $default) {
			$result[':'.$key] = isset($a[$key]) ? $a[$key] : $default;
		}

		return $result;
	}

	protected function arrayOfDefault($a, $key, $default='')
	{
		return isset($a[$key]) ? $a[$key] : $default;
	}
}

