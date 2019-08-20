<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

namespace iMSCP\Event;

use iMSCP\Event\Listener\EventListener;
use iMSCP\Event\Listener\PriorityQueue;
use iMSCP\Event\Listener\ResponseCollection;

/**
 * Class EventAggregator
 * @package iMSCP\Event
 */
class EventAggregator implements EventManagerInterface
{
    /**
     * @var EventAggregator
     */
    protected static $instance;

    /**
     * @var array map event to event type
     */
    protected $events = [];

    /**
     * @var EventManagerInterface[]
     */
    protected $eventManagers;

    /**
     * Constructor
     */
    protected function __construct()
    {
        // Event Manager used for events which are not explicitly declared
        $this->eventManagers['application'] = new EventManager();
    }

    /**
     * Singleton object - Make clone unavailable
     *
     * @return EventAggregator
     */
    public static function getInstance()
    {
        if (NULL === self::$instance) {
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
        self::$instance = NULL;
    }

    /**
     * Get the given event manager
     *
     * @param string $name Event manager unique name
     * @return EventManagerInterface|null
     */
    public function getEventManager($name)
    {
        if (isset($this->eventManagers[$name])) {
            return $this->eventManagers[$name];
        }

        return NULL;
    }

    /**
     * Dispatches an event to all registered listeners
     *
     * @param string $event The name of the event to dispatch.
     * @param mixed $arguments OPTIONAL The data to pass to the event listener
     *                         method.
     * @return ResponseCollection
     * @throws EventManagerException When an listener is an object that do not
     *                               implement the listener method, or when the
     *                               listener is not a valid PHP callback
     */
    public function dispatch($event, $arguments = [])
    {
        if (($eventType = $this->getEventType($event))) {
            return $this->eventManagers[$eventType]->dispatch(
                $event, $arguments
            );
        } else {
            return $this->eventManagers['application']->dispatch(
                $event, $arguments
            );
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
        foreach ($this->events as $eventType => $events) {
            if (in_array($event, $events)) {
                return $eventType;
            }
        }

        return NULL;
    }

    /**
     * Registers an event listener that listens on the specified events
     *
     * @param string|array $event The event(s) to listen on
     * @param callable|object $listener PHP callback or object which implement
     *                                  method with same name as event
     * @param int $priority Higher values have higher priority
     * @return EventManagerInterface Provide fluent interface, returns self
     */
    public function registerListener($event, $listener, $priority = 1)
    {
        if (is_array($event)) {
            foreach ($event as $e) {
                $this->registerListener($e, $listener, $priority);
            }
        } elseif (($eventType = $this->getEventType($event))) {
            $this->eventManagers[$eventType]->registerListener(
                $event, $listener, $priority
            );
        } else {
            $this->addEvents('application', (array)$event);
            $this->eventManagers['application']->registerListener(
                $event, $listener, $priority
            );
        }

        return $this;
    }

    /**
     * Add events
     * @param $type
     * @param array $events
     * @return EventManagerInterface
     */
    public function addEvents($type, array $events = [])
    {
        if (isset($this->events[$type])) {
            $this->events[$type] = array_merge($this->events[$type], $events);
        } else {
            $this->events[$type] = $events;
            $this->eventManagers[$type] = new EventManager();
        }

        return $this;
    }

    /**
     * Unregister an event listener from an event
     *
     * @param EventListener $listener The listener object to remove
     * @return bool TRUE if $listener is found and unregistered, FALSE otherwise
     */
    public function unregisterListener(EventListener $listener)
    {
        $event = $listener->getMetadatum('event');

        if (($eventType = $this->getEventType($event))) {
            $this->eventManagers[$eventType]->unregisterListener($listener);
        }

        return false;
    }

    /**
     * Get all known events
     *
     * @param string $type Events type
     * @return array
     */
    public function getEvents($type = NULL)
    {
        $type = (string)$type;

        if (!$type) {
            $events = [];

            foreach ($this->events as $type) {
                $events = array_merge($events, $type);
            }

            return $events;
        }

        if (isset($this->events[$type])) {
            return $this->events[$type];
        }

        return [];
    }

    /**
     * Retrieve all listeners which listen to a particular event
     *
     * @param string|null $event Event name
     * @return PriorityQueue
     */
    public function getListeners($event)
    {
        if (($eventType = $this->getEventType($event))) {
            return $this->eventManagers[$eventType]->getListeners($event);
        }

        return new PriorityQueue();
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
     * @return bool TRUE if the specified event has any listeners, FALSE
     *              otherwise
     */
    public function hasListener($event)
    {
        if (($eventType = $this->getEventType($event))) {
            return $this->eventManagers[$eventType]->hasListener($event);
        }

        return false;
    }
}
