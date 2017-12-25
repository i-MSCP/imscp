<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
 */

use iMSCP_Events_Listener as Listener;
use iMSCP_Events_Listener_PriorityQueue as PriorityQueue;
use iMSCP_Events_Listener_ResponseCollection as ResponseCollection;
use iMSCP_Events_Manager_Exception as Exception;


/**
 * Events Manager interface
 *
 * The Events Manager interface is the central point of i-MSCP's event listener
 * system. The listeners are registered on the manager, and events are
 * dispatched through the manager.
 *
 * A listener is an object or a callback function that listen on a particular
 * event. The events are defined in many places in the core code or components.
 * When a event is dispatched, the listener methods of all the listeners that
 * listens this event are executed.
 */
interface iMSCP_Events_Manager_Interface
{
    /**
     * Dispatches an event to all registered listeners
     *
     * @throws Exception When an listener is an object that do not implement
     *                   the listener method or when the listener is not a
     *                   valid PHP callback
     * @param string $event The name of the event to dispatch
     * @param mixed $arguments OPTIONAL The data to pass to the event listener method
     * @return ResponseCollection
     */
    public function dispatch($event, $arguments = []);

    /**
     * Registers an event listener that listens on the specified events
     *
     * @param string|array $event The event(s) to listen on
     * @param callable|object $listener PHP callback or object which implement
     *                                  method with same name as event
     * @param int $priority Higher values have higher priority
     * @return iMSCP_Events_Manager_Interface Provide fluent interface, returns self
     */
    public function registerListener($event, $listener, $priority = 1);

    /**
     * Unregister an event listener from an event
     *
     * @param Listener $listener The listener object to remove
     * @return bool TRUE if $listener is found and unregistered, FALSE otherwise
     */
    public function unregisterListener(Listener $listener);

    /**
     * Retrieve all registered events
     *
     * @return array
     */
    public function getEvents();

    /**
     * Retrieve all listener which listen to a particular event
     *
     * @param string|null $event Event name
     * @return PriorityQueue
     */
    public function getListeners($event);

    /**
     * Clear all listeners for a given event
     *
     * @param string $event Event name
     * @return void
     */
    public function clearListeners($event);

    /**
     * Checks whether an event has any registered listeners
     *
     * @param string $event The name of the event.
     * @return bool TRUE if the specified event has any listeners, FALSE otherwise.
     */
    public function hasListener($event);
}
