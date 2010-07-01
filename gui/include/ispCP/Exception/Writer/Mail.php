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
 * @package		ispCP_Exception
 * @subpackage	Writer
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @version		SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 * @filesource
 */

/**
 * Exception Mail writer
 *
 * This writer writes a mail that contain the exception messages and some
 * debug backtrace information.
 *
 * Note: Will be improved later.
 *
 * @category	ispCP
 * @package		ispCP_Exception
 * @subpackage	Writer
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @since		1.0.6
 * @version		1.0.0
 * @todo		Avoid sending multiple email for same exception
 */
class ispCP_Exception_Writer_Mail extends ispCP_Exception_Writer {

	/**
	 * Exception Writer name
	 *
	 * @var string
	 */
	const NAME = 'IspCP Exception Mail Writer';

	/**
	 * Mail recipient
	 *
	 * @var string
	 */
	protected $_to = '';

	/**
	 * Mail Header
	 *
	 * @var string
	 */
	protected $_header = '';

	/**
	 * Mail subject
	 *
	 * @var string
	 */
	protected $_subject = '';

	/**
	 * Mail body
	 *
	 * @var string
	 */
	protected $_body = '';

	/**
	 * Constructor - Create a new ispCP_Exception_Writer_Mail object
	 *
	 * @throws ispCP_Exception
	 * @param string $to An valid adresse email
	 * @return void
	 */
	public function __construct($to) {

		// filter_var() is only available with PHP >= 5.2
		if(function_exists('filter_var')) {
			$ret = filter_var($to, FILTER_VALIDATE_EMAIL);
		} else {
			$ret = (bool) preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $to);
		}
	
		if($ret === false) {
			throw new ispCP_Exception(
				'ispCP_Exception_Writer_Mail error: Invalid email address!'
			);
		} else {
			$this->_to = $to;
		}
	}

	/**
	 * This methods is called from the subject (i.e. when an event occur)
	 *
	 * @param ispCP_Exception_Handler $exceptionHandler ispCP_Exception_Handler
	 * @return void
	 */
	public function update(SplSubject $exceptionHandler) {

		$exception = $exceptionHandler->getException();
		$this->_message = $exception->getMessage() ."\n";
		$this->prepareMail($exception);

		$this->_write();
	}

	/**
	 * Writes the mail
	 *
	 * @return void
	 */
	protected function _write() {

		mail($this->_to, $this->_subject, $this->_body, $this->_header);
	}

	/**
	 * Prepare the mail to be send
	 *
	 * @param Exception $exception An exception
	 * @return void
	 */
	protected function prepareMail($exception) {

		// Header
		$this->_header = 'From: "' . self::NAME . "\" <{$this->_to}>\n";
		$this->_header .= "MIME-Version: 1.0\n";
		$this->_header .= "Content-Type: text/plain; charset=utf-8\n";
		$this->_header .= "Content-Transfer-Encoding: 8bit\n";
		$this->_header .= 'X-Mailer: ' . self::NAME . "\n";

		// Subject
		$this->_subject = self::NAME . ' - Exception raised!';

		// Body
		$this->_body = "An exception with the following message was raised:\n\n";
		$this->_body .= "{$this->_message}\n\n";
		$this->_body .= "Debug backtrace:\n\n";

		foreach ($exception->getTrace() as $trace) {
				$this->_body .=
					"File: {$trace['file']} (Line: {$trace['line']})\n";

				if(isset($trace['class'])) {
					$this->_body .=
						"Method: {$trace['class']}::{$trace['function']}()\n";
				} else {
					$this->_body .= "Function: {$trace['function']}()\n";
				}
		}

		$this->_body .= "\n\n---------------------------------------\n";
		$this->_body .= self::NAME;
		$this->_body = wordwrap($this->_body, 70);
	}
}
