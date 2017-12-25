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

use iMSCP_Registry as Registry;
use iMSCP_Events_Listener_ResponseCollection as ResponseCollection;
use iMSCP_Events_Description as EventDescription;
use iMSCP_Events_Event as Event;
use iMSCP_Events_Manager_Interface as EventsManagerInterface;
use iMSCP_Events_Listener_PriorityQueue as PriorityQueue;
use iMSCP_Events_Exception as Exception;
use iMSCP_Events_Listener as Listener;

/**
 * Class iMSCP_Events_Manager
 */
class iMSCP_Events_Manager implements EventsManagerInterface
{
    /**
     * @var PriorityQueue[] Array that contains events listeners stacks.
     */
    protected $events = [];

    /**
     * Return iMSCP_Events_Manager instance
     *
     * @return EventsManagerInterface
     * @deprecated Will be removed in later version
     */
    public static function getInstance()
    {
        return Registry::get('iMSCP_Application')->getEventsManager();
    }

    /**
     * Dispatches an event to all registered listeners
     *
     * @param string|EventDescription $event Event name or EventDescription object
     * @param array|ArrayAccess $arguments Array of arguments (eg. an associative array)
     * @return ResponseCollection
     */
    public function dispatch($event, $arguments = [])
    {
        $responses = new ResponseCollection();

        if ($event instanceof EventDescription) {
            $eventObject = $event;
            $event = $eventObject->getName();
        } else {
            $eventObject = new Event($event, $arguments);
        }

        $listeners = $this->getListeners($event);

        if($listeners->isEmpty()) {
            return $responses;
        }
        
        /** @var $listener Listener */
        foreach ($listeners as $listener) {
            $responses->push(call_user_func($listener->getListener(), $eventObject));

            if ($eventObject->propagationIsStopped()) {
                $responses->setStopped(true);
                break;
            }
        }

        return $responses;
    }

    /**
     * Retrieve all listeners which listen to a particular event
     *
     * @param string $event Event name
     * @return PriorityQueue
     */
    public function getListeners($event)
    {
        #if (!array_key_exists($event, $this->events)) {
        if (!isset($this->events[$event])) {
            return new PriorityQueue();
        }

        return $this->events[$event];
    }

    /**
     * Registers an event listener that listens on the specified events
     *
     * @param string|array $event The event(s) to listen on
     * @param callable|object $listener PHP callback or object which implement method with same name as event
     * @param int $priority Higher values have higher priority
     * @return Listener|Listener[]
     */
    public function registerListener($event, $listener, $priority = 1)
    {
        if (is_array($event)) {
            $listeners = [];
            foreach ($event as $name) {
                $listeners[] = $this->registerListener($name, $listener, $priority);
            }

            return $listeners;
        }

        if (empty($this->events[$event])) {
            $this->events[$event] = new PriorityQueue();
        }

        $listener = new Listener($listener, ['event' => $event, 'priority' => $priority]);
        $this->events[$event]->addListener($listener, $priority);
        return $listener;
    }

    /**
     * Unregister all listeners which listen on the given event
     *
     * @throws Exception If $event is not a string
     * @param  string $event The event for which any event must be removed.
     * @return void
     */
    public function unregisterListeners($event)
    {
        if (!is_string($event)) {
            throw new Exception(
                sprintf(__CLASS__ . '::' . __FUNCTION__ . '() expects a string, %s given.', gettype($event))
            );
        }

        unset($this->events[$event]);
    }

    /**
     * Unregister a listener from an event
     *
     * @param Listener $listener The listener object to remove
     * @return bool TRUE if $listener is found and unregistered, FALSE otherwise
     */
    public function unregisterListener(Listener $listener)
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
