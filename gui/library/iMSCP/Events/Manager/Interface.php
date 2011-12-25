<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP team
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
 * @copyright	2010-2011 by i-MSCP team
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
 * @version		0.0.2
 */
interface iMSCP_Events_Manager_Interface
{
	/**
	 * Dispatches an event to all registered listeners.
	 *
	 * @abstract
	 * @param string $eventName			The name of the event to dispatch. The name
	 *									of the event is the name of the method that
	 *									is invoked on listeners objects. Callbacks
	 *									functions can have arbitrary names.
	 * @param mixed $argument OPTIONAL	The data to pass to the event listener method.
	 *									If not supplied, an empty iMSCP_Events_Event
	 *									instance is created.
	 * @return iMSCP_Events_Manager_Interface Provide fluent interface, returns self
	 * @todo allow to pass multiple arguments to listeners methods
	 */
	public function dispatch($eventName, $argument = null);

	/**
	 * Registers an event listener that listens on the specified events.
	 *
	 * @abstract
	 * @param  string|array $eventName		The event(s) to listen on.
	 * @param  callback|object $listener	Listener callback or listener object.
	 * @param  int $stackIndex OPTIONAL		The higher this value, the earlier an event
	 * 										listener will be triggered in the chain.
	 * @return iMSCP_Events_Manager_Interface Provide fluent interface, returns self
	 */
	public function registerListener($eventName, $listener, $stackIndex = null);

	/**
	 * Unregister an event listener from the specified events.
	 *
	 * @abstract
	 * @param  string|array $eventNames The event(s) to remove a listener from.
	 * @param  callback|object $listener The listener callback or object to remove.
	 * @return iMSCP_Events_Manager_Interface Provide fluent interface, returns self
	 */
	public function unregisterListener($eventNames, $listener);

	/**
	 * Returns the listeners of a specific event or all listeners.
	 *
	 * @abstract
	 * @param  string $eventName The name of the event.
	 * @return array The event listeners for the specified event, or all event listeners by event name.
	 */
	public function getListeners($eventName);

	/**
	 * Checks whether an event has any registered listeners.
	 *
	 * @abstract
	 * @param string $eventName The name of the event.
	 * @param string $listener listener classname or callback name
	 * @return bool TRUE if the specified event has any listeners, FALSE otherwise.
	 */
	public function hasListener($eventName, $listener);
}
