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

/** @noinspection
 * PhpUnusedParameterInspection
 * PhpUnhandledExceptionInspection
 * PhpDocMissingThrowsInspection
 */

declare(strict_types=1);

namespace iMSCP;

use countable;
use iterator;
use Zend_Cache_Core;

/**
 * Class Services
 * @package iMSCP
 */
class Services implements iterator, countable
{
    /**
     * @var array[] Array of services where keys are service names and values
     *              are arrays containing service properties
     */
    private $services = [];

    /**
     * @var string Service name currently queried
     */
    private $queriedService;

    /**
     * @var Zend_Cache_Core $cache
     */
    private $cache;

    /**
     * @var bool Whether or not data need to be refreshed
     */
    private $refresh;

    /**
     * Services constructor.
     *
     * @param bool $refresh Whether or not data need to be refreshed
     * @return void
     */
    public function __construct(bool $refresh = false)
    {
        $this->refresh = $refresh;
        $this->cache = Registry::get('iMSCP_Application')->getCache();
        $values = Registry::get('dbConfig')->toArray();

        // Gets list of services port names
        $services = array_filter(array_keys($values), function ($name) {
            return (strlen($name) > 5 && substr($name, 0, 5) == 'PORT_');
        });

        foreach ($services as $name) {
            $this->services[$name] = explode(';', $values[$name]);
        }
    }

    /**
     * Get service name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getProperty(2);
    }

    /**
     * Check if the service is visible.
     *
     * @return bool TRUE if the service is visible, FALSE otherwise
     * @throws Exception
     */
    public function isVisible(): bool
    {
        return (bool)$this->getProperty(3);
    }

    /**
     * Check if a service is running.
     *
     * @return bool TRUE if the service is currently running, FALSE otherwise
     */
    public function isRunning(): bool
    {
        return $this->getStatus();
    }

    /**
     * Get service status
     *
     * @return bool TRUE if the service is currently running, FALSE otherwise
     */
    private function getStatus(): bool
    {
        $identifier = static::class
            . '_'
            . preg_replace('/[^a-zA-Z0-9_]/', '_', $this->key());

        if ($this->refresh || !($this->cache->test($identifier))) {
            $ip = $this->getIp();

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ip = '[' . $ip . ']';
            }

            $status = false;
            if ($fp = @fsockopen(
                $this->getProtocol() . '://' . $ip,
                $this->getPort(),
                $errno,
                $errstr,
                0.5
            )) {
                fclose($fp);
                $status = true;
            }

            $this->cache->save($status, $identifier, [], 300);
        } else {
            $status = $this->cache->load($identifier);
        }

        return (bool)$status;
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return key($this->services);
    }

    /**
     * Get service IP address
     *
     * @return string
     */
    public function getIp(): string
    {
        return $this->getProperty(4);
    }

    /**
     * Get service protocol.
     *
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->getProperty(1);
    }

    /**
     * Get service listening port.
     *
     * @return int
     */
    public function getPort(): int
    {
        return (int)$this->getProperty(0);
    }

    /**
     * Get a service property value
     *
     * @param int $index Service property index
     * @return mixed Service property value
     */
    private function getProperty(int $index)
    {
        if (NULL === $this->queriedService) {
            throw new Exception('Name of service to query is not set');
        }

        return $this->services[$this->queriedService][$index];
    }

    /**
     * Check if a service is down
     *
     * @return bool TRUE if the service is currently down, FALSE otherwise
     */
    public function isDown(): bool
    {
        return !$this->getStatus();
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        $this->setService($this->key(), false);

        return current($this->services);
    }

    /**
     * Set service to be queried.
     *
     * @param string $serviceName Service name
     * @param bool $normalize Tell whether or not the service name must be
     *                        normalized
     * @return void
     */
    public function setService(
        string $serviceName, bool $normalize = true
    ): void
    {
        // Normalise service name (ex. 'dns' to 'PORT_DNS')
        if ($normalize) {
            $serviceName = 'PORT_' . strtoupper($serviceName);
        }

        if (array_key_exists($serviceName, $this->services)) {
            $this->queriedService = $serviceName;
        } else {
            throw new Exception("Unknown Service: $serviceName");
        }
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        next($this->services);
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        reset($this->services);
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        return array_key_exists(key($this->services), $this->services);
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->services);
    }
}
