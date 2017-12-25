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

namespace iMSCP\Json;

/**
 * Class LazyDecoder
 * @package iMSCP\Json
 */
class LazyDecoder implements \ArrayAccess, \Countable
{
    /**
     * @var array Json data
     */
    protected $container;

    /**
     * @var bool Whether or not Json string has been decoded
     */
    protected $decoded = false;

    /**
     * @var array json_decode parameters
     */
    protected $parameters;

    /**
     * LazyDecoder constructor.
     *
     * @param string $json Json string
     * @param int $depth User specified recursion depth.
     * @param int $options Bitmask of JSON decode options.
     */
    public function __construct($json, $depth = 512, $options = 0)
    {
        $this->parameters = [$json, true, $depth, $options];
    }

    /**
     * {@inheritdoc}
     */
    public function &offsetGet($key)
    {
        if (!$this->decoded) {
            $this->decode();
        }

        $ret = NULL;
        if (!$this->offsetExists($key)) {
            return $ret;
        }
        $ret =& $this->container[$key];
        return $ret;
    }

    /**
     * Decode json data
     *
     * @return void
     */
    protected function decode()
    {
        $this->container = call_user_func_array('json_decode', $this->parameters);
        $this->parameters = NULL;
        $this->decoded = true;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        if (!$this->decoded) {
            $this->decode();
        }

        return isset($this->container[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        if (!$this->decoded) {
            $this->decode();
        }

        $this->container[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if (!$this->decoded) {
            $this->decode();
        }

        return count($this->container);
    }

    /**
     * Returns whether the requested key exists
     *
     * @param  mixed $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unsets the value at the specified key
     *
     * @param  mixed $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        if (!$this->decoded) {
            $this->decode();
        }

        if ($this->offsetExists($key)) {
            unset($this->container[$key]);
        }
    }

    /**
     * Return array representation of this object
     *
     * @return array|string
     */
    public function toArray()
    {
        if (!$this->decoded) {
            $this->decode();
        }

        return $this->container;
    }
}
