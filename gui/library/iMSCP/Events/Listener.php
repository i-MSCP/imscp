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

/**
 * Class iMSCP_Events_Listener
 */
class iMSCP_Events_Listener
{
    /**
     * @var string|array|callable
     */
    protected $listener;

    /**
     * @var array Listener metadata, if any
     */
    protected $metadata;

    /**
     * Constructor
     *
     * @param string|array|object|callable $listener
     * @param array $metadata Listener metadata
     */
    public function __construct($listener, array $metadata = [])
    {
        $this->metadata = $metadata;
        $this->registerListener($listener);
    }

    /**
     * Registers the listener provided in the constructor
     *
     * @param callable $listener Listener handler
     * @throws iMSCP_Events_Listener_Exception
     * @return void
     */
    protected function registerListener($listener)
    {
        // functor
        if (is_object($listener)
            && !($listener instanceof Closure)
        ) {
            $event = $this->getMetadatum('event');

            if (is_callable([$listener, $event])) {
                $this->listener = [$listener, $event];
                return;
            }
        }

        // Callable
        if (is_callable($listener)) {
            $this->listener = $listener;
            return;
        }

        throw new iMSCP_Events_Listener_Exception('Invalid handler provided; not callable');
    }

    /**
     * Retrieve a single metadatum
     *
     * @param string $name
     * @return mixed
     */
    public function getMetadatum($name)
    {
        if (array_key_exists($name, $this->metadata)) {
            return $this->metadata[$name];
        }

        return NULL;
    }

    /**
     * Invoke as functor
     *
     * @return mixed
     */
    public function __invoke()
    {
        return $this->call(func_get_args());
    }

    /**
     * Invoke listener
     *
     * @param  array $args Arguments to pass to listener
     * @return mixed
     */
    public function call(array $args = [])
    {
        $listener = $this->getListener();
        $argCount = count($args);

        if (is_string($listener)) {
            $result = $this->validateStringCallback($listener);

            if ($result !== true
                && $argCount <= 3
            ) {
                $listener = $result;
                $this->listener = $result; // Minor performance tweak, if the listener gets called more than once
            }
        }

        switch ($argCount) {
            case 0:
                return $listener();
            case 1:
                return $listener(array_shift($args));
            case 2:
                $arg1 = array_shift($args);
                $arg2 = array_shift($args);
                return $listener($arg1, $arg2);
            case 3:
                $arg1 = array_shift($args);
                $arg2 = array_shift($args);
                $arg3 = array_shift($args);
                return $listener($arg1, $arg2, $arg3);
            default:
                return call_user_func_array($listener, $args);
        }
    }

    /**
     * Return listener handler
     *
     * @return callable
     */
    public function getListener()
    {
        return $this->listener;
    }

    /**
     * Validate a static method call
     *
     * Validates that a static method call will actually work
     *
     * @param string $listener
     * @return bool|array
     * @throws iMSCP_Events_Listener_Exception if invalid
     */
    protected function validateStringCallback($listener)
    {
        if (!strstr($listener, '::')) {
            return true;
        }

        list($class, $method) = explode('::', $listener, 2);

        if (!class_exists($class)) {
            throw new iMSCP_Events_Listener_Exception(
                sprintf('Static method call "%s" refers to a class which does not exist', $listener)
            );
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->hasMethod($method)) {
            throw new iMSCP_Events_Listener_Exception(
                sprintf('Static method call "%s" refers to a method which does not exist', $listener)
            );
        }

        $reflectionMethod = $reflection->getMethod($method);

        if (!$reflectionMethod->isStatic()) {
            throw new iMSCP_Events_Listener_Exception(
                sprintf('Static method call "%s" refers to a method which is not static', $listener)
            );
        }

        // Returning a non boolean value may not be nice for a validate
        // method, but that allows the usage of a static string listener
        // without using the call_user_func function.
        return [$class, $method];
    }

    /**
     * Get all listener metadata
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}
