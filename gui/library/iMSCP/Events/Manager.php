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
 * @package     iMSCP_Core
 * @subpackage  Events_Manager
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
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
 * Class iMSCP_Events_Manager
 */
class iMSCP_Events_Manager implements iMSCP_Events_Manager_Interface
{
	/**
	 * @var iMSCP_Listener_PriorityQueue[] Array that contains events listeners stacks.
	 */
	protected $events = array();

	/**
	 * Return iMSCP_Events_Aggregator instance
	 *
	 * @return iMSCP_Events_Aggregator
	 * @deprecated 1.1.6 (will be removed in later version
	 */
	public static function getInstance()
	{
		return iMSCP_Events_Aggregator::getInstance();
	}

	/**
	 * Dispatches an event to all registered listeners
	 *
	 * @param string|iMSCP_Events_Description $event Event name or iMSCP_Events_Description object
	 * @param array|ArrayAccess $arguments Array of arguments (eg. an associative array)
	 * @return iMSCP_Events_Listener_ResponseCollection
	 */
	public function dispatch($event, $arguments = array())
	{
		$responses = new iMSCP_Events_Listener_ResponseCollection();

		if ($event instanceof iMSCP_Events_Description) {
			$eventObject = $event;
			$event = $eventObject->getName();
		} else {
			$eventObject = new iMSCP_Events_Event($event, $arguments);
		}

		$listeners = $this->getListeners($event);
		//$listeners = clone $listeners;

		/** @var $listener iMSCP_Listener */
		foreach ($listeners as $listener) {
			$responses->push(call_user_func($listener->getHandler(), $eventObject));

			if ($eventObject->propagationIsStopped()) {
				$responses->setStopped(true);
				break;
			}
		}

		return $responses;
	}

	/**
	 * Registers an event listener that listens on the specified events
	 *
	 * @param string|array $event  The event(s) to listen on
	 * @param callable|object $listener PHP callback or object which implement method with same name as event
	 * @param int $priority Higher values have higher priority
	 * @return iMSCP_Listener|iMSCP_Listener[]
	 */
	public function registerListener($event, $listener, $priority = 1)
	{
		if (is_array($event)) {
			$listeners = array();

			foreach ($event as $name) {
				$listeners[] = $this->registerListener($name, $listener, $priority);
			}

			return $listeners;
		}

		if (empty($this->events[$event])) {
			$this->events[$event] = new iMSCP_Listener_PriorityQueue();
		}

		$listener = new iMSCP_Listener($listener, array('event' => $event, 'priority' => $priority));
		$this->events[$event]->addListener($listener, $priority);

		return $listener;
	}

	/**
	 * Unregister all listeners which listen on the given event
	 *
	 * @throws iMSCP_Events_Exception If $event is not a string
	 * @param  string $event The event for which any event must be removed.
	 * @return void
	 */
	public function unregisterListeners($event)
	{
		if (is_string($event)) {
			unset($this->events[$event]);
		} else {
			throw new iMSCP_Events_Exception(
				sprintf(__CLASS__ . '::' . __FUNCTION__ . '() expects a string, %s given.', gettype($event))
			);
		}
	}

	/**
	 * Unregister a listener from an event
	 *
	 * @param iMSCP_Listener $listener The listener object to remove
	 * @return bool TRUE if $listener is found and unregistered, FALSE otherwise
	 */
	public function unregisterListener(iMSCP_Listener $listener)
	{
		$event = $listener->getMetadatum('event');

		if (!$event || empty($this->events[$event])) {
			return false;
		}

		if (!($this->events[$event]->removeListener($listener))) {
			return false;
		}

		if (!count($this->events[$event])) {
			unset($this->events[$event]);
		}

		return true;
	}

	/**
	 * Retrieve all registered events
	 *
	 * @return array
	 */
	public function getEvents()
	{
		return array_keys($this->events);
	}

	/**
	 * Retrieve all listeners which listen to a particular event
	 *
	 * @param string $event Event name
	 * @return iMSCP_Listener_PriorityQueue
	 */
	public function getListeners($event)
	{
		if (!array_key_exists($event, $this->events)) {
			return new iMSCP_Listener_PriorityQueue();
		}

		return $this->events[$event];
	}

	/**
	 * Clear all listeners for a given event
	 *
	 * @param string $event Event name
	 * @return void
	 */
	public function clearListeners($event)
	{
		if (!empty($this->events[$event])) {
			unset($this->events[$event]);
		}
	}

	/**
	 * Checks whether an event has any registered listeners
	 *
	 * @param string $eventName The name of the event.
	 * @return bool TRUE if the specified event has any listeners, FALSE otherwise.
	 */
	public function hasListener($eventName)
	{
		return (bool)count($this->getListeners($eventName));
	}
}
