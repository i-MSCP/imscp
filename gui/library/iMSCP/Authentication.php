<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 * @package		Authentication
 * @copyright	2010-2014 by -MSCP Team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Authentication class
 *
 * This component is responsible to authenticate users using authentication handlers. An authentication handler is an
 * event listener which is listening to the onAuthenticate event that is triggered when the authentication process
 * occurs.
 *
 * By default, only one authentication handler is registered, which authenticate user using an username and password.
 * Plugin's developers can register their own authentication handler by registering them on the event manager.
 *
 * An authentication handler which was successful, must short-circuit the execution of any other authentication handlers
 * by stopping the onAuthentication event propagation. Each authentication handler must return an
 * iMSCP_Authentication_Result object which allows the authentication component to know if the authentication process
 * was successful.
 *
 * Each authentication handler is receiving the onAuthentication event as context parameter.
 *
 */
class iMSCP_Authentication
{
	/**
	 * Singleton instance
	 *
	 * @var iMSCP_Authentication
	 */
	protected static $instance = null;

	/**
	 * @var iMSCP_Events_Manager_Interface
	 */
	protected $events = null;

	/**
	 * @var string Username to match against
	 */
	protected $username = null;

	/**
	 * @var string Clear text password to match against
	 */
	protected $password = null;

	/**
	 * Singleton pattern implementation -  makes "new" unavailable
	 */
	protected function __construct()
	{

	}

	/**
	 * Singleton pattern implementation -  makes "clone" unavailable
	 *
	 * @return void
	 */
	protected function __clone()
	{

	}

	/**
	 * Implements singleton design pattern
	 *
	 * @return iMSCP_Authentication Provides a fluent interface, returns self
	 */
	public static function getInstance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return an iMSCP_Events_Manager instance
	 *
	 * @param iMSCP_Events_Manager_Interface $events
	 * @return iMSCP_Events_Manager_Interface
	 */
	public function getEvents(iMSCP_Events_Manager_Interface $events = null)
	{
		if (null !== $events) {
			$this->events = $events;
		} elseif (null === $this->events) {
			$this->events = iMSCP_Events_Aggregator::getInstance();
		}

		return $this->events;
	}

	/**
	 * User password authentication handler
	 *
	 * This is the default authentication handler which authenticate an user using its username and password.
	 *
	 * @return iMSCP_Authentication_Result
	 * @param iMSCP_Events_Event $event
	 */
	public function __invoke($event)
	{
		$username = encode_idna($this->getUsername());
		$password = clean_input($this->getPassword());

		if (empty($username) || empty($password)) {
			if (empty($username)) {
				$message[] = tr('The username field is empty.');
			}

			if (empty($password)) {
				$message[] = tr('The password field is empty.');
			}
		}

		if (!isset($message)) {
			$query = "
              SELECT admin_id, admin_name, admin_pass, admin_type, email, created_by FROM admin WHERE admin_name = ?
     	    ";
			$stmt = exec_query($query, $username);

			if (!$stmt->rowCount()) {
				$result = new iMSCP_Authentication_Result(
					iMSCP_Authentication_Result::FAILURE_IDENTITY_NOT_FOUND, null, tr('Unknown username.')
				);
			} else {
				$identity = $stmt->fetchRow(PDO::FETCH_OBJ);
				$dbPassword = $identity->admin_pass;

				if ($dbPassword != md5($password) && crypt($password, $dbPassword) != $dbPassword) {
					$result = new iMSCP_Authentication_Result(
						iMSCP_Authentication_Result::FAILURE_CREDENTIAL_INVALID, null, tr('Bad password.')
					);
				} else {
					if(strpos($dbPassword, '$') !== 0) { # Not a password encrypted with crypt(), then re-encrypt it
						exec_query(
							'UPDATE admin SET admin_pass = ? WHERE admin_id = ?',
							array(cryptPasswordWithSalt($password), $identity->admin_id)
						);
						write_log(
							"Info: Password for user <b>'$identity->admin_name'</b> has been re-encrypted using the best available algorithm",
							E_USER_NOTICE
						);
					}

					$result = new iMSCP_Authentication_Result(iMSCP_Authentication_Result::SUCCESS, $identity);
					$event->stopPropagation();
				}
			}
		} else {
			$result = new iMSCP_Authentication_Result(
				count($message) == 2
					? iMSCP_Authentication_Result::FAILURE_CREDENTIAL_EMPTY
					: iMSCP_Authentication_Result::FAILURE_CREDENTIAL_INVALID
				, null, $message
			);
		}

		return $result;
	}

	/**
	 * Process authentication
	 *
	 * @trigger onBeforeAuthentication
	 * @trigger onAuthentication
	 * @trigger onAfterAuthentication
	 * @return iMSCP_Authentication_Result
	 */
	public function authenticate()
	{
		$em = $this->getEvents();

		$response = $em->dispatch(iMSCP_Events::onBeforeAuthentication, array('context' => $this));

		if (!$response->isStopped()) {
			// Registers the default authentication handler
			$em->registerListener(iMSCP_Events::onAuthentication, $this);

			// Process authentication through available handlers
			$response = $em->dispatch(iMSCP_Events::onAuthentication, array('context' => $this));

			if (!($resultAuth = $response->last()) instanceof iMSCP_Authentication_Result) {
				// Should never occurs since any authentication handler must return an iMSCP_Authentication_Result object
				$resultAuth = new iMSCP_Authentication_Result(
					iMSCP_Authentication_Result::FAILURE_UNCATEGORIZED, tr('Unknown reason.')
				);
			}

			if ($resultAuth->isValid()) {
				$this->unsetIdentity(); // Prevent multiple successive calls from storing inconsistent results
				$this->setIdentity($resultAuth->getIdentity());
			} elseif ($resultAuth->getCode() === iMSCP_Authentication_Result::FAILURE_CREDENTIAL_EMPTY) {
				$resultAuth->setMessage(null);
			}
		} else {
			$resultAuth = new iMSCP_Authentication_Result(
				iMSCP_Authentication_Result::FAILURE_UNCATEGORIZED, null, $response->last()
			);
		}

		$em->dispatch(iMSCP_Events::onAfterAuthentication, array('context' => $this, 'authResult' => $resultAuth));

		return $resultAuth;
	}

	/**
	 * Get username
	 *
	 * @param string $username Username
	 * @return string|null
	 */
	public function getUsername()
	{
		return $this->username;
	}

	/**
	 * Sets username to match against
	 *
	 * @param string $username Username
	 * @return iMSCP_Authentication Provides fluent interface, returns self
	 */
	public function setUsername($username)
	{
		$this->username = (string)$username;

		return $this;
	}

	/**
	 * Get password
	 *
	 * @return string|null
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * Set password to match against
	 *
	 * @param string $password Password
	 * @return iMSCP_Authentication Provides fluent interface, returns self
	 */
	public function setPassword($password)
	{
		$this->password = (string)$password;

		return $this;
	}

	/**
	 * Returns true if and only if an identity is available from storage
	 *
	 * @return boolean
	 */
	public function hasIdentity()
	{
		$query = "SELECT COUNT(session_id) AS cnt FROM login WHERE session_id = ? AND ipaddr = ?";
		$stmt = exec_query($query, array(session_id(), getipaddr()));
		return ($stmt->fields['cnt'] && isset($_SESSION['user_id']));
	}

	/**
	 * Returns the identity from storage or null if no identity is available
	 *
	 * @return stdClass|null
	 */
	public function getIdentity()
	{
		$identity = null;

		if ($this->hasIdentity()) {
			$identity = new stdClass();
			$identity->admin_id = $_SESSION['user_id'];
			$identity->admin_name = $_SESSION['user_logged'];
			$identity->admin_type = $_SESSION['user_type'];
			$identity->email = $_SESSION['user_email'];
			$identity->created_by = $_SESSION['user_created_by'];
		}

		return $identity;
	}

	/**
	 * Set the given identity
	 *
	 * @trigger onBeforeSetIdentity
	 * @trigger onAfterSetIdentify
	 * @param stdClass $identity Identity data
	 */
	public function setIdentity($identity)
	{
		$this->getEvents()->dispatch(iMSCP_Events::onBeforeSetIdentity, array('context' => $this, 'identity' => $identity));

		// We'll change permission level so we regenerate the session identifier to enforce security
		session_regenerate_id();

		$lastAccess = time();

		$query = 'INSERT INTO login (session_id, ipaddr, lastaccess, user_name) VALUES (?, ?, ?, ?)';
		exec_query($query, array(session_id(), getIpAddr(), $lastAccess, $identity->admin_name));

		$_SESSION['user_logged'] = $identity->admin_name;
		$_SESSION['user_type'] = $identity->admin_type;
		$_SESSION['user_id'] = $identity->admin_id;
		$_SESSION['user_email'] = $identity->email;
		$_SESSION['user_created_by'] = $identity->created_by;
		$_SESSION['user_login_time'] = $lastAccess;

		$this->getEvents()->dispatch(iMSCP_Events::onAfterSetIdentity, array('context' => $this));
	}

	/**
	 * Unset the current identity
	 *
	 * @trigger onBeforeUnsetIdentity
	 * @trigger onAfterUnserIdentity
	 * @return void
	 */
	public function unsetIdentity()
	{
		$this->getEvents()->dispatch(iMSCP_Events::onBeforeUnsetIdentity, array('context' => $this));

		$query = "DELETE FROM login WHERE session_id = ?";
		exec_query($query, session_id());

		$preserveList = array(
			'user_def_lang', 'user_theme', 'user_theme_color', 'show_main_menu_labels', 'pageMessages'
		);

		foreach (array_keys($_SESSION) as $sessionVariable) {
			if (!in_array($sessionVariable, $preserveList)) {
				unset($_SESSION[$sessionVariable]);
			}
		}

		$this->getEvents()->dispatch(iMSCP_Events::onAfterUnsetIdentity, array('context' => $this));
	}
}
