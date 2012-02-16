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
 * @package		iMSCP_Events
 * @subpackage	Manager
 * @copyright	2010-2012 by i-MSCP team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * Events Manager interface.
 *
 * The Events Manager interface is the central point of i-MSCP's event listener
 * system. The listeners are registered on the manager, and events are dispatched through
 * the manager.
 *
 * A listener is an object or a callback function that listen on a particular event. The events are defined in many
 * places in the core code or components. When a event is dispatched, the listener methods of all the listeners that
 * listens this event are executed.
 *
 * @category	iMSCP
 * @package		iMSCP_Events
 * @subpackage	Manager
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.4
 */
interface iMSCP_Events_Manager_Interface
{
	/**
	 * Dispatches an event to all registered listeners.
	 *
	 * @throws iMSCP_Events_Manager_Exception	When an listener is an object that do not implement the listener method or
	 * 											when the listener is not a valid PHP callback
	 * @param string $eventName					The name of the event to dispatch.
	 * @param mixed $arguments OPTIONAL			The data to pass to the event listener method.
	 *
	 * @return iMSCP_Events_Listeners_ResponseCollection
	 */
	public function dispatch($eventName, $arguments = array());

	/**
	 * Registers an event listener that listens on the specified events.
	 *
	 * @abstract
	 * @param  string|array $eventNames		The event(s) to listen on.
	 * @param  callback|object $listener	Listener callback or listener object.
	 * @param  int $priority				The higher this value, the earlier an event listener will be triggered in
	 * 										the chain.
	 * @return iMSCP_Events_Manager_Interface Provide fluent interface, returns self
	 */
	public function registerListener($eventNames, $listener, $priority = 1);

	/**
	 * Unregister an event listener from the given event.
	 *
	 * @abstract
	 * @param  string $eventName The event to remove a listener from.
	 * @param  callback|object $listener The listener callback or object to remove.
	 * @return bool TRUE if $listener is found and unregistered, FALSE otherwise
	 */
	public function unregisterListener($eventName, $listener);

	/**
	 * Returns the listeners for the given event or all listeners.
	 *
	 * @abstract
	 * @param  string|null $eventName The name of the event.
	 * @return array The event listeners for the specified event, or all event listeners by event name if $event is NULL.
	 */
	public function getListeners($eventName = null);

	/**
	 * Checks whether an event has any registered listeners.
	 *
	 * @abstract
	 * @param string $eventName The name of the event.
	 * @return bool TRUE if the specified event has any listeners, FALSE otherwise.
	 */
	public function hasListener($eventName);
}
