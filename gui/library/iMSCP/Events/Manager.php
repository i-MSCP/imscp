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
 * @package		iMSCP_Core
 * @subpackage	Events_Manager
 * @copyright	2010-2012 by i-MSCP team
 * @author		Laurent Declercq <laurent.declercq@i-mscp.net>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/** @see iMSCP_Events_Manager_Interface */
require_once 'iMSCP/Events/Manager/Interface.php';

/**
 * Events Manager class.
 *
 * The events manager is the central point of i-MSCP's event listener system.
 * Listeners are registered on the manager and events are dispatched through the manager.
 *
 * A listener can be an object or a callback function. The listeners objects must
 * implement listeners methods named as the events they listens on.
 *
 * A very basic example for a listener that listen on the 'AdminScriptStart' event:
 *
 * <code>
 * class HelloWorld
 * {
 *	  public function AdminScriptStart()
 *	  {
 *		  echo 'Hello World!';
 *		  exit;
 *	  }
 * }
 *
 * $eventsManager = iMSCP_Events_Manager::getInstance()->registerListener('onAdminScriptStart', new HelloWorld());
 *
 * // Later in the code
 * iMSCP_Events_Manager::getInstance()->dispatch('AdminScriptStart');
 *
 * // Result on screen will be: Hello World!
 * </code>
 *
 * @category	iMSCP
 * @package		iMSCP_Events
 * @subpackage	Manager
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.4
 */
class iMSCP_Events_Manager implements iMSCP_Events_Manager_Interface
{
	/**
	 * Instance of this class.
	 *
	 * @var iMSCP_Events_Manager
	 */
	protected static $_instance;

	/**
	 * Array that contains events listeners stacks.
	 *
	 * @var iMSCP_Events_Listeners_Stack[]
	 */
	protected $_events = array();

	/**
	 * Singleton object - Make new unavailable.
	 */
	protected function __construct()
	{

	}

	/**
	 * Singleton object - Make clone unavailable.
	 *
	 * @return void
	 */
	protected function __clone()
	{

	}

	/**
	 * Implements Singleton design pattern.
	 *
	 * @static
	 * @return iMSCP_Events_Manager
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Reset instance.
	 *
	 * @static
	 * @return void
	 */
	public static function resetInstance()
	{
		self::$_instance = null;
	}

	/**
	 * Dispatches an event to all registered listeners.
	 *
	 * @param string $eventName			The name of the event to dispatch. The name
	 *									of the event is the name of the method that
	 *									is invoked on listeners objects. Callbacks
	 *									functions can have arbitrary names.
	 *
	 * @param mixed $argument OPTIONAL	The data to pass to the event listener method.
	 *									If not supplied, an empty iMSCP_Events_Event
	 *									instance is created.
	 *
	 * @return mixed
	 * @todo allow to pass multiple arguments to listeners methods
	 */
	public function dispatch($eventName, $argument = null)
	{
		if (isset($this->_events[$eventName])) {
			if (null === $argument) {
				$argument = new iMSCP_Events_Event();
			}

			foreach ($this->_events[$eventName]->getIterator() as $listener) {
				if (is_callable($listener)) {
					call_user_func_array($listener, array($argument));
				} elseif (is_object($listener)) {
					if (is_callable(array($listener, $eventName))) {
						$listener->$eventName($argument);
					} else {
						require_once 'iMSCP/Events/Exception.php';
						throw new iMSCP_Events_Manager_Exception(sprintf(
							'The %s object must implement the %s() listener method.', get_class($listener), $eventName));

					}
				} else {
					require_once 'iMSCP/Events/Exception.php';
					throw new iMSCP_Events_Manager_Exception("Listener must be a valid callback function or an object.");
				}
			}
		}
	}

	/**
	 * Registers an event listener that listens on the specified events.
	 *
	 * Example:
	 *
	 * $eventManager = iMSCP_Events_Manager::getInstance();
	 *
	 * 1. Using object that implement event listener method(s)
	 * $eventManager->registerListener('eventName', $objectInstance)
	 *
	 * 2. Using static class method
	 * $eventManager->registerListener('eventName', 'classname::staticMethodName')
	 *
	 * 3. Using class instance method
	 * $eventManager->registerListener('eventName', array($ObjectInstance, 'methodName')
	 *
	 * 4. Using fonction name
	 * $eventManager->registerListener('eventName', 'functionName')
	 *
	 * 5. Using anonymous function,
	 * $funct = create_function('$event', 'do something here');
	 * $eventManager->registerListener('eventName', $funct)
	 *
	 * 6. Using closure
	 * $closure = function($event) { do something here };
	 * $eventManager->registerListener('eventName', $closure)
	 * ...
	 *
	 * @param  string|array $eventNames		The event(s) to listen on.
	 * @param  callback|object $listener	Listener callback function or object.
	 * @param  int $stackIndex				OPTIONAL The higher this value, the earlier
	 *										an event listener will be triggered in the
	 *										chain of the specified events.
	 *
	 * @return iMSCP_Events_Manager_Interface Provide fluent interface, returns self
	 */
	public function registerListener($eventNames, $listener, $stackIndex = null)
	{
		foreach ((array)$eventNames as $eventName) {
			if (!isset($this->_events[$eventName])) {
				$this->_events[$eventName] = new iMSCP_Events_Listeners_Stack();
			}

			$this->_events[$eventName]->addListener($listener, $stackIndex);
		}

		return $this;
	}

	/**
	 * Unregister an event listener from the specified events.
	 *
	 * Note: For now, it's only possible to remove a listener implemented as object.
	 *
	 * @param  string|array $eventNames	The event(s) to remove a listener from.
	 * @param  object $listener			The listener object to remove.
	 * @return iMSCP_Events_Manager_Interface Provide fluent interface, returns self
	 */
	public function unregisterListener($eventNames, $listener)
	{
		foreach ((array)$eventNames as $eventName) {
			if (isset($this->_events[$eventName])) {
				$this->_events[$eventName]->removeListener($listener);
			}

			if (empty($this->_events[$eventName])) {
				unset($this->_events[$eventName]);
			}
		}
	}

	/**
	 * Returns the listeners of a specific event or all listeners.
	 *
	 * @param string $eventName The name of the event.
	 * @return array The event listeners for the specified event, or all event
	 *				 listeners by event name.
	 */
	public function getListeners($eventName = null)
	{
		throw new iMSCP_Events_Manager_Exception('iMSCP_Events_Manager::getListeners() is not implemented yet');
	}

	/**
	 * Checks whether an event has any registered listeners.
	 *
	 * @param string $eventName The name of the event.
	 * @param string $listener listener classname or callback name
	 * @return bool TRUE if the specified event has any listeners, FALSE otherwise.
	 */
	public function hasListener($eventName, $listener = null)
	{
		throw new iMSCP_Events_Manager_Exception('iMSCP_Events_Manager::hasListener() is not implemented yet');
	}
}
