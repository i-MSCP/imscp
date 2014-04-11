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
 * @category    iMSCP
 * @package     iMSCP_Events
 * @subpackage  Manager
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/** @see iMSCP_Events_Manager_Interface */
require_once 'iMSCP/Events/Manager/Interface.php';

/** @see iMSCP_Listener_PriorityQueue */
require_once 'iMSCP/Events/Listener/PriorityQueue.php';

/** @see iMSCP_Events_Manager_Interface */
require_once 'iMSCP/Events/Listener.php';

/**
 * Class iMSCP_Events_Aggregator
 */
class iMSCP_Events_Aggregator implements iMSCP_Events_Manager_Interface
{
	/**
	 * @var iMSCP_Events_Aggregator
	 */
	protected static $instance;

	/**
	 * @var array map event to event type
	 */
	protected $events = array();

	/**
	 * @var iMSCP_Events_Manager_Interface[]
	 */
	protected $eventManagers;

	/**
	 * Constructor
	 */
	protected function __construct()
	{
		// Event Manager used for events which are not explicitely declared
		$this->eventManagers['application'] = new iMSCP_Events_Manager();
	}

	/**
	 * Singleton object - Make clone unavailable
	 *
	 * @return iMSCP_Events_Aggregator
	 */
	public static function getInstance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Reset instance
	 *
	 * @static
	 * @return void
	 */
	public static function resetInstance()
	{
		self::$instance = null;
	}

	/**
	 * Make new event availables
	 *
	 * @param string $type
	 * @param array $events
	 */
	public function addEvents($type, array $events = array())
	{
		if (isset($this->events[$type])) {
			$this->events[$type] += $events;
		} else {
			$this->events[$type] = $events;
			$this->eventManagers[$type] = new iMSCP_Events_Manager();
		}

		return $this;
	}

	/**
	 * Dispatches an event to all registered listeners
	 *
	 * @throws iMSCP_Events_Manager_Exception When an listener is an object that do not implement the listener method or
	 *                                        when the listener is not a valid PHP callback
	 * @param string $event The name of the event to dispatch.
	 * @param mixed $arguments OPTIONAL The data to pass to the event listener method.
	 * @return iMSCP_Events_Listener_ResponseCollection
	 */
	public function dispatch($event, $arguments = array())
	{
		if (($eventType = $this->getEventType($event))) {
			return $this->eventManagers[$eventType]->dispatch($event, $arguments);
		} else {
			return $this->eventManagers['application']->dispatch($event, $arguments);
		}
	}

	/**
	 * Registers an event listener that listens on the specified events
	 *
	 * @param string|array $event The event(s) to listen on
	 * @param callable|object $listener PHP callback or object which implement method with same name as event
	 * @param int $priority Higher values have higher priority
	 * @return iMSCP_Events_Manager_Interface Provide fluent interface, returns self
	 */
	public function registerListener($event, $listener, $priority = 1)
	{
		if (($eventType = $this->getEventType($event))) {
			$this->eventManagers[$eventType]->registerListener($event, $listener, $priority);
		} else {
			$this->addEvents('application', (array) $event);
			$this->eventManagers['application']->registerListener($event, $listener, $priority);
		}

		return $this;
	}

	/**
	 * Unregister an event listener from an event
	 *
	 * @param iMSCP_Listener $listener The listener object to remove
	 * @return bool TRUE if $listener is found and unregistered, FALSE otherwise
	 */
	public function unregisterListener(iMSCP_Listener $listener)
	{
		$event = $listener->getMetadatum('event');

		if (($eventType = $this->getEventType($event))) {
			$this->eventManagers[$eventType]->unregisterListener($listener);
		}

		return false;
	}

	/**
	 * Retrieve all registered events
	 *
	 * @param string $type Event type
	 * @return array
	 */
	public function getEvents($type = null)
	{
		$type = (string) $type;

		if (!$type) {
			return $this->events['application'];
		} elseif(isset($this->events[$type])) {
			return $this->events[$type];
		} else {
			return array();
		}
	}

	/**
	 * Get event type
	 *
	 * @param $event
	 * @return string|null
	 */
	public function getEventType($event)
	{
		foreach($this->events as $eventType => $events) {
			if(in_array($event, $events)) {
				return $eventType;
			}
		}

		return null;
	}

	/**
	 * Retrieve all listeners which listen to a particular event
	 *
	 * @param string|null $event Event name
	 * @return SplPriorityQueue
	 */
	public function getListeners($event)
	{
		if (($eventType = $this->getEventType($event))) {
			return $this->eventManagers[$eventType]->getListeners($event);
		}

		return new iMSCP_Listener_PriorityQueue();
	}

	/**
	 * Clear all listeners for a given event
	 *
	 * @param string $event Event name
	 * @return void
	 */
	public function clearListeners($event)
	{
		if (($eventType = $this->getEventType($event))) {
			$this->eventManagers[$eventType]->clearListeners($event);
		}
	}

	/**
	 * Checks whether an event has any registered listeners
	 *
	 * @param string $event The name of the event
	 * @return bool TRUE if the specified event has any listeners, FALSE otherwise
	 */
	public function hasListener($event)
	{
		if (($eventType = $this->getEventType($event))) {
			return $this->eventManagers[$eventType]->hasListener($event);
		}

		return false;
	}
}
