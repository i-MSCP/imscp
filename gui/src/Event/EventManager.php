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

use ArrayAccess;
use iMSCP\Event\Listener\EventListener;
use iMSCP\Event\Listener\ListenerException;
use iMSCP\Event\Listener\PriorityQueue;
use iMSCP\Event\Listener\ResponseCollection;

/**
 * Class EventManager
 * @package iMSCP\Event
 */
class EventManager implements EventManagerInterface
{
    /**
     * @var PriorityQueue[] Array that contains events listeners stacks.
     */
    protected $events = [];

    /**
     * Return iMSCP_Events_Aggregator instance.
     *
     * @return EventAggregator
     * @deprecated 1.1.6 (will be removed in later version
     */
    public static function getInstance()
    {
        return EventAggregator::getInstance();
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param string|EventDescription $event
     * @param array|ArrayAccess $arguments Array of arguments
     * @return ResponseCollection
     * @throws EventException
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
        //$listeners = clone $listeners;

        /** @var $listener EventListener */
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
     * Retrieve all listeners which listen to a particular event.
     *
     * @param string $event Event name
     * @return PriorityQueue
     */
    public function getListeners($event)
    {
        if (!array_key_exists($event, $this->events)) {
            return new PriorityQueue();
        }

        return $this->events[$event];
    }

    /**
     * Registers an event listener that listens on the specified events.
     *
     * @param string|array $event The event(s) to listen on
     * @param callable|object $listener PHP callback or object which implement
     *                        method with same name as event
     * @param int $priority Higher values have higher priority
     * @return EventListener|EventListener[]
     * @throws ListenerException
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

        $listener = new EventListener(
            $listener, ['event' => $event, 'priority' => $priority]
        );
        $this->events[$event]->addListener($listener, $priority);

        return $listener;
    }

    /**
     * Unregister all listeners which listen on the given event.
     *
     * @param string $event The event for which any event must be removed.
     * @return void
     * @throws EventException If $event is not a string
     */
    public function unregisterListeners($event)
    {
        if (is_string($event)) {
            unset($this->events[$event]);
        } else {
            throw new EventException(
                sprintf(
                    __CLASS__ . '::' . __FUNCTION__
                    . '() expects a string, %s given.',
                    gettype($event)
                )
            );
        }
    }

    /**
     * Unregister a listener from an event.
     *
     * @param EventListener $listener The listener object to remove
     * @return bool TRUE if $listener is found and unregistered, FALSE otherwise
     */
    public function unregisterListener(EventListener $listener)
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
     * Retrieve all registered events.
     *
     * @return array
     */
    public function getEvents()
    {
        return array_keys($this->events);
    }

    /**
     * Clear all listeners for a given event.
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
     * Checks whether an event has any registered listeners.
     *
     * @param string $eventName The name of the event.
     * @return bool TRUE if the specified event has any listeners, FALSE
     *              otherwise.
     */
    public function hasListener($eventName)
    {
        return (bool)count($this->getListeners($eventName));
    }
}
