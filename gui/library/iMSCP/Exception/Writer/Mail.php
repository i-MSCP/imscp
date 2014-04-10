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
 * @category	i-MSCP
 * @package		iMSCP_Exception
 * @subpackage	Writer
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2014 by i-MSCP | http://i-mscp.net
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/** @see iMSCP_Exception_Writer */
require_once  'iMSCP/Exception/Writer.php';

/**
 * Exception Mail writer
 *
 * This writer writes a mail that contains the exception messages and some debug
 * backtrace information.
 *
 * @category	iMSCP
 * @package		iMSCP_Exception
 * @subpackage	Writer
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.6
 */
class iMSCP_Exception_Writer_Mail extends iMSCP_Exception_Writer
{
	/**
	 * Exception writer name
	 *
	 * @var string
	 */
	const NAME = 'i-MSCP Exception Mail Writer';

	/**
	 * Mail recipient
	 *
	 * @var string
	 */
	protected $_to = '';

	/**
	 * Mail header
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
	 * Mail body md5 footprint
	 *
	 * @var string
	 */
	protected $_footprint = '';

	/**
	 * Mail footprints expiry time (in hours)
	 *
	 * @var int
	 */
	protected $_expiryTime = 6;

	/**
	 * Mail body footprints cache
	 *
	 * @var array
	 */
	protected $_cache = array();

	/**
	 * Constructor - Create a new mail writer object.
	 *
	 * @throws iMSCP_Exception
	 * @param string $to A mail adresse
	 */
	public function __construct($to)
	{
		if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
			throw new iMSCP_Exception('iMSCP_Exception_Writer_Mail error: Invalid email address.');
		} else {
			$this->_to = $to;
		}



		$em = iMSCP_Events_Aggregator::getInstance();

		// Set Mail body footprints expiry time
		$em->registerListener(iMSCP_Events::onAfterInitialize, array($this, 'setExpiryTime'));

		// Delete expired mail body footprints
		$em->registerListener(iMSCP_Events::onAfterInitialize, array($this, 'cleanCache'));
	}

	/**
	 * This methods is called from the subject (i.e. when an event occur).
	 *
	 * @param SplSubject $exceptionHandler An SplSubject object
	 * @return void
	 */
	public function update(SplSubject $exceptionHandler)
	{
		/** @var $exceptionHandler iMSCP_Exception_Handler */
		/** @var $exception iMSCP_Exception */
		$exception = $exceptionHandler->getException();

		$this->_message = preg_replace('#([\t\n]+|<br \/>)#', ' ', $exception->getMessage());

		/** @var $exception iMSCP_Exception_Database */
		if ($exception instanceof iMSCP_Exception_Database) {
			$this->_message .= "\n\nQuery was:\n\n" . $exception->getQuery();
		}

		$this->prepareMail($exception);
		$this->_loadCache();

		if (!$this->_isAlreadySent()) {
			$this->_write();
			$this->_cacheFootprint();
			$this->_updateCache();
		}
	}

	/**
	 * Load cached mail body footprints from the database.
	 *
	 * @return void
	 * @todo using file instead of database to store mail body footprints
	 */
	protected function _loadCache()
	{
		/**
		 * @var $dbConfig iMSCP_Config_Handler_Db
		 */
		if (iMSCP_Registry::isRegistered('dbConfig')) {
			$dbConfig = iMSCP_Registry::get('dbConfig');

			if (isset($dbConfig->MAIL_BODY_FOOTPRINTS) && isSerialized($dbConfig->MAIL_BODY_FOOTPRINTS)) {
				$this->_cache = unserialize($dbConfig->MAIL_BODY_FOOTPRINTS);
			}
		}
	}

	/**
	 * Updates the mail body footprints cache.
	 *
	 * @return void
	 */
	protected function _updateCache()
	{
		if (!empty($this->_cache) && iMSCP_Registry::isRegistered('dbConfig')) {
			/** @var $dbConfig iMSCP_Config_Handler_Db */
			$dbConfig = iMSCP_Registry::get('dbConfig');
			$dbConfig->MAIL_BODY_FOOTPRINTS = serialize($this->_cache);
		}
	}

	/**
	 * Checks if the mail with same body footprint was already sents.
	 *
	 * @return bool TRUE if the message was already sent, FALSE otherwise
	 */
	protected function _isAlreadySent()
	{
		if (array_key_exists($this->_footprint, $this->_cache) && $this->_cache[$this->_footprint] > time()) {
			return true;
		}

		return false;
	}

	/**
	 * Mail body footprints cache cleanup.
	 *
	 * Remove all expirated mail body footprints from the cache.
	 *
	 * @return void
	 */
	public function cleanCache()
	{
		if (iMSCP_Registry::isRegistered('dbConfig')) {
			/** @var $dbConfig iMSCP_Config_Handler_Db */
			$dbConfig = iMSCP_Registry::get('dbConfig');

			if (isset($dbConfig->MAIL_BODY_FOOTPRINTS) && isSerialized($dbConfig->MAIL_BODY_FOOTPRINTS)) {
				$cache = unserialize($dbConfig->MAIL_BODY_FOOTPRINTS);
				$now = time();

				foreach ($cache as $footprint => $expireTime) {
					if ($expireTime <= $now) {
						unset($cache[$footprint]);
					}
				}

				if (!empty($cache)) {
					$dbConfig->MAIL_BODY_FOOTPRINTS = serialize($cache);
				} else {
					unset($dbConfig->MAIL_BODY_FOOTPRINTS);
				}
			}
		}
	}

	/**
	 * Cache the mail body footprint.
	 *
	 * Both footprint and expiration time are used to avoid multiple sending of
	 * mail for the same exception raised in interval of {@link _expiryTime}
	 * hours.
	 *
	 * @todo Flat file
	 * @return void
	 */
	protected function _cacheFootprint()
	{
		$this->_cache[$this->_footprint] = strtotime("+{$this->_expiryTime} hour");
	}

	/**
	 * Writes the mail.
	 *
	 * @return boolean TRUE on sucess, FALSE otherwise
	 */
	protected function _write()
	{
		if (mail($this->_to, $this->_subject, $this->_body, $this->_header)) {
			return true;
		}

		return false;
	}

	/**
	 * Prepare the mail to be send.
	 *
	 * @param Exception $exception An exception object
	 * @return void
	 */
	protected function prepareMail($exception)
	{
		// Header
		$this->_header = 'From: "' . self::NAME . "\" <{$this->_to}>\n";
		$this->_header .= "MIME-Version: 1.0\n";
		$this->_header .= "Content-Type: text/plain; charset=utf-8\n";
		$this->_header .= "Content-Transfer-Encoding: 8bit\n";
		$this->_header .= 'X-Mailer: ' . self::NAME;

		// Subject
		$this->_subject = self::NAME . ' - Exception raised';

		// Body
		$this->_body = "Dear admin,\n\n";
		$this->_body .= 'An exception with the following message has been thrown in file ' .
			$exception->getFile() . ' (Line: ' . $exception->getLine() . "):\n\n";

		$this->_body .= str_repeat('=', 65) . "\n\n";
		$this->_body .= "{$this->_message}\n";
		$this->_body .= str_repeat('=', 65) . "\n\n";

		// Debug Backtrace
		$this->_body .= "Debug backtrace:\n";
		$this->_body .= str_repeat('-', 15) . "\n\n";

		if (count($exception->getTrace()) != 0) {
			foreach ($exception->getTrace() as $trace) {
				if (isset($trace['file'])) {
					$this->_body .= "File: {$trace['file']} (Line: {$trace['line']})\n";
				}

				if (isset($trace['class'])) {
					$this->_body .= "Method: {$trace['class']}::{$trace['function']}()\n";
				} elseif (isset($trace['function'])) {
					$this->_body .= "Function: {$trace['function']}()\n";
				}
			}
		} else {
			$this->_body .= 'File: ' . $exception->getFile() . ' (Line: ' .
				$exception->getLine() . ")\n";
			$this->_body .= "Function: main()\n";
		}

		// Get the static mail body footprint
		$this->_footprint = md5($this->_body);

		// Additional information
		$this->_body .= "\nAdditional information:\n";
		$this->_body .= str_repeat('-', 22) . "\n\n";

		foreach (array('HTTP_USER_AGENT', 'REQUEST_URI', 'HTTP_REFERER', 'REMOTE_ADDR', 'SERVER_ADDR') as $key) {
			if (isset($_SERVER[$key]) && $_SERVER[$key] != '') {
				$this->_body .= ucwords(strtolower(str_replace('_', ' ', $key))) . ": {$_SERVER["$key"]}\n";
			}
		}

		$this->_body .= "\n" . str_repeat('_', 60) . "\n";
		$this->_body .= self::NAME . "\n";
		$this->_body .= "\n\nNote: If an exception of same type is thrown again, you will not receive this mail before" .
			" an expiration time that is currently fixed at {$this->_expiryTime} hour(s).\n";
		$this->_body = wordwrap($this->_body, 70, "\n");
	}

	/**
	 * Set mail body footprints expiry time.
	 *
	 * @return void
	 */
	public function setExpiryTime()
	{
		if (iMSCP_Registry::isRegistered('dbConfig')) {
			/** @var $dbConfig iMSCP_Config_Handler_Db */
			$dbConfig = iMSCP_Registry::get('dbConfig');
			if ($dbConfig->exists('MAIL_WRITER_EXPIRY_TIME')) {
				$this->_expiryTime = $dbConfig->MAIL_WRITER_EXPIRY_TIME;
			}
		}
	}
}
