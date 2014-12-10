<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP team
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
 * @copyright	2010-2014 by i-MSCP team
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * Bruteforce detection plugin.
 *
 * This plugin allows to increase system security by detecting any dictionary attacks and blocking them according a set
 * of configuration parameters.
 *
 * This plugin can be used in two different ways:
 *
 * - As an action plugin that listen to some events triggered in i-MSCP core code and that doing some specific actions
 *   related to bruteforce detection
 * - As a simple object queried by hand in external components.
 */
class iMSCP_Authentication_Bruteforce extends iMSCP_Plugin_Action
{
	/**
	 * @var int Tells whether or not bruteforce detection is enabled
	 */
	protected $bruteForceEnabled = 0;

	/**
	 * @var int Tells whether or not waiting time between login|captcha attempts is enabled
	 */
	protected $waitTimeEnabled = 0;

	/**
	 * @var int Blocking time in minutes
	 */
	protected $blockTime = 0;

	/**
	 * @var int Waiting time in seconds between each login|captcha attempts
	 */
	protected $waitTime = 0;

	/**
	 * @var int Max attempts before an IP address is blocked
	 */
	protected $maxAttempts = 0;

	/**
	 * @var string IP address (The subject)
	 */
	protected $ipAddr = '';

	/**
	 * @var string Bruteforce detection type (login|captcha)
	 */
	protected $type = 'login';

	/**
	 * @var int Time during which an IP address is blocked
	 */
	protected $isBlockedFor = 0;

	/**
	 * @var int Time to wait before a new login|captcha attempts is allowed
	 */
	protected $isWaitingFor = 0;
	
	/**
	 * 
	 * @var int Max attemps before IP is forced to wait.
	 */
	protected $maxAttemptsBeforeWait = 0;

	/**
	 * @var bool Tells whether or not a bruteforce detection record exists for $_ipAddr
	 */
	protected $recordExists = false;

	/**
	 * @var string Session unique identifier
	 */
	protected $sessionId = '';

	/**
	 * @var string Last message raised
	 */
	protected $message = '';

	/**
	 * Constructor
	 *
	 * @param string $type Bruteforce detection type (login|captcha) (defaulted to login)
	 */
	public function __construct($type = 'login')
	{
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		$this->sessionId = session_id();
		$this->type = $type;
		$this->ipAddr = getIpAddr();

		if ($type == 'login') {
			$this->maxAttempts = $cfg['BRUTEFORCE_MAX_LOGIN'];
		} else {
			$this->maxAttempts = $cfg['BRUTEFORCE_MAX_CAPTCHA'];
		}

		$this->blockTime = $cfg['BRUTEFORCE_BLOCK_TIME'];
		$this->waitTime = $cfg['BRUTEFORCE_BETWEEN_TIME'];
		$this->maxAttemptsBeforeWait = $cfg['BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT'];
		
		$this->unblock();

		// Plugin initialization
		parent::__construct(iMSCP_Registry::get('pluginManager'));
	}

	/**
	 * Initialization
	 *
	 * @return void
	 */
	protected function init()
	{
		$stmt = exec_query('SELECT * FROM login WHERE ipaddr = ? AND user_name IS NULL', $this->ipAddr);

		if ($stmt->rowCount()) {
			$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
			$this->recordExists = true;

			if ($row[$this->type . '_count'] >= $this->maxAttempts) {
				$this->isBlockedFor = $row['lastaccess'] + $this->blockTime * 60;
				$this->isWaitingFor = 0;
			} else {
				$this->isBlockedFor = 0;
				if ($row[$this->type . '_count'] >= $this->maxAttemptsBeforeWait) {
					$this->isWaitingFor = $row['lastaccess'] + $this->waitTime;
				} else {
					$this->isWaitingFor = 0;
				}
			}
		} else {
			$this->recordExists = false;
		}
	}

	/**
	 * Returns plugin general information
	 *
	 * @return array
	 */
	public function getInfo()
	{
		return array(
			'author' => array('Daniel Andreca', 'Laurent Declercq'),
			'email' => 'sci2tech@gmail.com',
			'version' => '0.0.4',
			'date' => '2012-03-20',
			'name' => 'Bruteforce',
			'desc' => 'Allow to improve system security by detecting any dictionary attacks and blocking them according a set of configuration parameters',
			'url' => 'http://www.i-mscp.net'
		);
	}

	/**
	 * Register a callback for the given event(s)
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$eventsManager->registerListener(
			array(iMSCP_Events::onBeforeAuthentication, iMSCP_Events::onBeforeSetIdentity), $this, -999
		);
	}

	/**
	 * Implements the onBeforeAuthentication listener method
	 *
	 * @param iMSCP_Events_Event $event Represent an onBeforeAuthentication event that is triggered in the
	 *									iMSCP_Authentication component.
	 * @return null|string
	 */
	public function onBeforeAuthentication($event)
	{
		if ($this->isWaiting() || $this->isBlocked()) {
			$event->stopPropagation();
			return $this->getLastMessage();
		}

		if($event->getParam('context')->getUsername()) {
			$this->recordAttempt();
		}

		return null;
	}

	/**
	 * Implement the onBeforeSetIdentity listener method
	 *
	 * @return void
	 */
	public function onBeforeSetIdentity()
	{
		exec_query('DELETE FROM login WHERE session_id = ?', $this->sessionId);
	}

	/**
	 * Is blocked IP address?
	 *
	 * @return bool TRUE if $_ipAddr is blocked, FALSE otherwise
	 */
	public function isBlocked()
	{
		if ($this->isBlockedFor - time() > 0) {
			$this->message = tr('Ip %s is blocked for %s minutes.', $this->ipAddr, $this->isBlockedFor());
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
		if ($this->isWaitingFor - time() > 0) {
			$this->message = tr('Ip %s is waiting %s seconds.', $this->ipAddr, $this->isWaitingFor());
			return true;
		}

		return false;
	}

	/**
	 * Create/Update bruteforce detection record for $_ipAddr
	 *
	 * @return void
	 */
	public function recordAttempt()
	{
		if (!$this->recordExists) {
			$this->createRecord();
		} else {
			$this->updateRecord();
		}
	}

	/**
	 * Returns last message raised
	 *
	 * @return string
	 */
	public function getLastMessage()
	{
		return $this->message;
	}

	/**
	 * Returns human readable blocking time
	 *
	 * @return string
	 */
	protected function isBlockedFor()
	{
		return strftime("%M:%S", ($this->isBlockedFor - time() > 0) ? $this->isBlockedFor - time() : 0);
	}

	/**
	 * Returns human readable waiting time
	 *
	 * @return string
	 */
	protected function isWaitingFor()
	{
		return strftime("%M:%S", ($this->isWaitingFor - time() > 0) ? $this->isWaitingFor - time() : 0);
	}

	/**
	 * Increase login|captcha attempts by 1 for $_ipAddr
	 *
	 * @return void
	 */
	protected function updateRecord()
	{
		exec_query(
			"
				UPDATE
					login
				SET
					lastaccess = UNIX_TIMESTAMP(), {$this->type}_count = {$this->type}_count + 1
				WHERE
					ipaddr= ? AND user_name IS NULL
			",
			($this->ipAddr)
		);
	}

	/**
	 * Create bruteforce detection record
	 *
	 * @return void
	 */
	protected function createRecord()
	{
		exec_query(
			"
				REPLACE INTO login (
					session_id, ipaddr, {$this->type}_count, user_name, lastaccess
					) VALUES (
						?, ?, 1, NULL, UNIX_TIMESTAMP()
				)
			",
			array($this->sessionId, $this->ipAddr)
		);
	}

	/**
	 * Unblock any Ip address for which blocking time is expired
	 *
	 * @return void
	 */
	protected function unblock()
	{
		$timeout = time() - ($this->blockTime * 60);
		exec_query("DELETE FROM login WHERE lastaccess < ? AND `{$this->type}_count` > 0", $timeout);
	}
}
