<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2012 by i-MSCP team
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
 *
 * @category	iMSCP
 * @package		iMSCP_Authentication
 * @subpackage	Bruteforce
 * @copyright	2010-2012 by i-MSCP team
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * Bruteforce detection component.
 *
 * This component provides a sublayer for the authentication process that allows to increase system security by
 * detecting any dictionary attacks and blocking them according a set of configuration parameters.
 *
 * This component can be used in two different ways:
 *
 * - As an action plugin that listen to some events triggered in i-MSCP core code and that doing some specific actions
 *   related to bruteforce detection
 * - As a simple object queried by hand in external components such as the i-MSCP WHMCS bridge.
 *
 * @category	iMSCP
 * @package		iMSCP_Authentication
 * @subpackage	Bruteforce
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @version		0.0.2
 */
class iMSCP_Authentication_Bruteforce extends iMSCP_Plugin_Action implements iMSCP_Events_Listeners_Interface
{
	/**
	 * @var string listened event.
	 */
	protected $_listenedEvents = iMSCP_Events::onBeforeAuthentication;

	/**
	 * @var int Tells whether or not bruteforce detection is enabled
	 */
	protected $_bruteForceEnabled = 0;

	/**
	 * @var int Tells whether or not waiting time between login|captcha attempts is enabled
	 */
	protected $_waitTimeEnabled = 0;

	/**
	 * @var int Blocking time in minutes
	 */
	protected $_blockTime = 0;

	/**
	 * @var int Waiting time in seconds between each login|captcha attempts
	 */
	protected $_waitTime = 0;

	/**
	 * @var int Max attempts before an IP address is blocked
	 */
	protected $_maxAttempts = 0;

	/**
	 * @var string IP address (The subject)
	 */
	protected $_ipAddr = '';

	/**
	 * @var string Bruteforce detection type (login|captcha)
	 */
	protected $_type = 'login';

	/**
	 * @var int Login attempts count
	 */
	protected $_loginCount = 0;

	/**
	 * @var int Captcha attempts count
	 */
	protected $_captchaCount = 0;

	/**
	 * @var int Time during which an IP address is blocked
	 */
	protected $_isBlockedFor = 0;

	/**
	 * @var int Time to wait before a new login|captcha attempts is allowed
	 */
	protected $_isWaitingFor = 0;

	/**
	 * @var bool Tells whether or not a bruteforce detection record exists for $_ipAddr
	 */
	protected $_recordExists = false;

	/**
	 * @var string Session unique identifier
	 */
	protected $_sessionId = '';

	/**
	 * @var array Messages raised
	 */
	protected $_message;

	/**
	 * Constructor.
	 *
	 * @param string $type Bruteforce detection type (defaulted to login)
	 */
	public function __construct($type = 'login')
	{
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		$this->_sessionId = session_id();
		$this->_type = $type;
		$this->_ipAddr = $_SERVER['REMOTE_ADDR'];

		if ($this->_type == 'login') {
			$this->_maxAttempts = $cfg->BRUTEFORCE_MAX_LOGIN;
		} else {
			$this->_maxAttempts = $cfg->BRUTEFORCE_MAX_CAPTCHA;
		}

		$this->_blockTime = $cfg->BRUTEFORCE_BLOCK_TIME;
		$this->_waitTime = $cfg->BRUTEFORCE_BETWEEN_TIME;

		$this->_unblock();

		// Component / Plugin initialization
		parent::__construct();
	}

	/**
	 * Initialization.
	 *
	 * @return void
	 */
	public function init()
	{
		$query = 'SELECT * FROM `login` WHERE `ipaddr` = ? AND `user_name` is NULL';
		$stmt = exec_query($query, $this->_ipAddr);

		if (!$stmt->rowCount()) {
			$this->_recordExists = false;
		} else {
			$this->_recordExists = true;

			if ($stmt->fields($this->_type . '_count') >= $this->_maxAttempts) {
				$this->_isBlockedFor = $stmt->fields('lastaccess') + $this->_blockTime * 60;
				$this->_isWaitingFor = 0;
				$this->_message[] = tr('Found 1 record. ip %s is blocked for another %s minutes.', $this->_ipAddr, $this->isBlockedFor());
			} else {
				$this->_message[] = tr('Found records for ip %s', $this->_ipAddr);
				$this->_isBlockedFor = 0;
				$this->_isWaitingFor = $stmt->fields('lastaccess') + $this->_waitTime;
			}
		}
	}

	/**
	 * Returns plugin general information.
	 *
	 * @return array
	 */
	public function getInfo()
	{
		return array(
			'author' => 'Daniel Andreca',
			'email' => 'sci2tech@gmail.com',
			'version' => '0.0.2',
			'date' => '2012-02-24',
			'name' => 'Bruteforce',
			'desc' => 'Allow to improve system security by detecting any dictionnary attacks and blocking them according a set of configuration parameters',
			'url' => 'http://www.i-mscp.net'
		);
	}

	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $controller
	 */
	public function register(iMSCP_Events_Manager_Interface $controller)
	{
		$controller->registerListener($this->getListenedEvents(), $this);
		$this->_controller = $controller;
	}

	/**
	 * Implements the onBeforeAuthentication listener method.
	 *
	 * @param iMSCP_Events_Event $event Represent an onBeforeAuthentication event that is triggered in the
	 * 									iMSCP_Authentication component.
	 * @return null|string
	 */
	public function onBeforeAuthentication($event)
	{
		if ($this->isWaiting() || $this->isBlocked()) {
			$event->stopPropagation();
			return $this->getLastMessage();
		}

		$this->recordAttempt();

		return null;
	}

	/**
	 * Returns listened events.
	 *
	 * @return array
	 */
	public function getListenedEvents()
	{
		return $this->_listenedEvents;
	}

	/**
	 * Create/Update bruteforce detection record for $_ipAddr.
	 *
	 * @return void
	 */
	public function recordAttempt()
	{
		if (!$this->_recordExists) {
			$this->_message[] = tr('No records found for ip %s with username not set.', $this->_ipAddr);
			$this->_createRecord();
		} else {
			$this->_updateRecord($this->{$this->_type . 'Count'});
		}
	}

	/**
	 * Is blocked IP address?
	 *
	 * @return bool TRUE if $_ipAddr is blocked, FALSE otherwise
	 */
	public function isBlocked()
	{
		if ($this->_isBlockedFor - time() > 0) {
			$this->_message[] = tr('Ip %s is blocked for %s minutes.', $this->_ipAddr, $this->isBlockedFor());
			return true;
		}

		return false;
	}

	/**
	 * Is waiting IP address?
	 *
	 * @return bool TRUE if $_ipAddr is waiting, FALSE otherwise
	 */
	public function isWaiting()
	{
		if ($this->_isWaitingFor - time() > 0) {
			$this->_message[] = tr('Ip %s is waiting %s seconds.', $this->_ipAddr, $this->isWaitingFor());
			return true;
		}

		return false;
	}

	/**
	 * Returns human readable blocking time.
	 *
	 * @return string
	 */
	public function isBlockedFor()
	{
		return strftime("%M:%S", ($this->_isBlockedFor - time() > 0) ? $this->_isBlockedFor - time() : 0);
	}

	/**
	 * Returns human readable waiting time.
	 *
	 * @return string
	 */
	public function isWaitingFor()
	{
		return strftime("%M:%S", ($this->_isWaitingFor - time() > 0) ? $this->_isWaitingFor - time() : 0);
	}

	/**
	 * Returns last message raised.
	 *
	 * @return string
	 */
	public function getLastMessage()
	{
		return array_key_exists(count($this->_message) - 1, $this->_message) ? $this->_message[count($this->_message) - 1] : '';
	}

	/**
	 * Increase login|captcha attempts by 1 for $_ipAddr.
	 *
	 * @param int $count
	 */
	protected function _updateRecord($count)
	{
		if ($count < $this->_maxAttempts) {
			$this->_message[] = tr('Increasing %s attempts by 1 for ip %s.', $this->_type, $this->_ipAddr);

			$query = "
				UPDATE
					`login`
				SET
					`lastaccess` = UNIX_TIMESTAMP(), `{$this->_type}_count` = `{$this->_type}_count` + 1
				WHERE
					`ipaddr`= ? AND `user_name` IS NULL
			";
			exec_query($query, ($this->_ipAddr));
		}
	}

	/**
	 * Create bruteforce detection record.
	 *
	 * @return void
	 */
	protected function _createRecord()
	{
		//$this->_message[] = tr('Creating record for ip %s.', $this->_ipAddr);

		$query = '
			REPLACE INTO `login` (
				`session_id`, `ipaddr`, `user_name`, `lastaccess`
				) VALUES (
					?, ?, NULL, UNIX_TIMESTAMP()
			)
		';
		exec_query($query, array($this->_sessionId, $this->_ipAddr));
	}

	/**
	 * Unblock any Ip address for which blocking time is expired.
	 *
	 * @return void
	 */
	protected function _unblock()
	{
		$this->_message[] = tr('Unblocking expired sessions.');
		$timeout = time() - ($this->_blockTime * 60);
		$query = "UPDATE `login` SET `{$this->_type}_count` = 0 WHERE `lastaccess` < ? AND `user_name` IS NULL";
		exec_query($query, array($timeout));
	}
}
