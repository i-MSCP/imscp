<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Exception as iMSCPException;

/**
 * This class provides an interface to manage easily a set of configuration
 * parameters from an array.
 *
 * This class implements the ArrayAccess and Iterator interfaces to improve
 * the access to the configuration parameters.
 *
 * With this class, you can access to your data like:
 *
 * - An array
 * - Via object properties
 * - Via setter and getter methods
 */
class iMSCP_Config_Handler implements ArrayAccess
{
    /**
     * Loads all configuration parameters from an array
     *
     * @param array $parameters Configuration parameters
     */
    public function __construct(array $parameters)
    {
        foreach ($parameters as $parameter => $value) {
            $this->{$parameter} = $value;
        }
    }

    /**
     * Sets a configuration parameter
     *
     * @param string $key Configuration parameter key name
     * @param mixed $value Configuration parameter value
     * @return void
     */
    public function set($key, $value)
    {
        $this->{$key} = $value;
    }

    /**
     * PHP overloading on inaccessible members
     *
     * @param string $key Configuration parameter key name
     * @return mixed Configuration parameter value
     * @throws iMSCP_Exception
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Getter method to retrieve a configuration parameter value
     *
     * @throws iMSCPException
     * @param string $key Configuration parameter key name
     * @return mixed Configuration parameter value
     */
    public function get($key)
    {
        if (!$this->exists($key)) {
            throw new iMSCPException("Configuration variable `$key` is missing.");
        }

        return $this->$key;
    }

    /**
     * Deletes a configuration parameters
     *
     * @param string $key Configuration parameter key name
     * @return void
     */
    public function del($key)
    {
        unset($this->$key);
    }

    /**
     * Checks whether configuration parameters exists.
     *
     * @param string $key Configuration parameter key name
     * @return boolean TRUE if configuration parameter exists, FALSE otherwise
     * @todo Remove this method
     */
    public function exists($key)
    {
        return property_exists($this, $key);
    }

    /**
     * Merge the given configuration object
     *
     * All keys in this object that don't exist in the second object will be
     * left untouched.
     *
     * This method is not recursive.
     *
     * @param iMSCP_Config_Handler $config iMSCP_Config_Handler object
     * @return void
     */
    public function merge(iMSCP_Config_Handler $config)
    {
        foreach ($config as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Return an associative array that contains all configuration parameters
     *
     * @return array Array that contains configuration parameters
     */
    public function toArray()
    {
        $ref = new ReflectionObject($this);
        $properties = $ref->getProperties(ReflectionProperty::IS_PUBLIC);
        $array = [];

        foreach ($properties as $property) {
            $name = $property->name;
            $array[$name] = $this->{$name};
        }

        return $array;
    }

    /**
     * Assigns a value to the specified offset
     *
     * @param mixed $offset The offset to assign the value to
     * @param mixed $value The value to set.
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Returns the value at specified offset
     *
     * @param  mixed $offset The offset to retrieve
     * @return mixed Offset value
     * @throws iMSCP_Exception
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Whether or not an offset exists
     *
     * @param mixed $offset An offset to check for existence
     * @return boolean TRUE on success or FALSE on failure
     */
    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    /**
     * Unsets an offset
     *
     * @param  mixed $offset The offset to unset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->{$offset});
    }
}
