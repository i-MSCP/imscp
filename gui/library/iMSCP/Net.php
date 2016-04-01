<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

namespace iMSCP;

/**
 * Class Net
 * @package iMSCP
 */
class Net
{
    /**
     * @var Net
     */
    static $instance;

    /**
     * @var array Network device data
     */
    protected $devices = array();

    /**
     * @var array IP addresses data
     */
    protected $ippAddresses = array();

    /**
     * Singleton pattern implementation -  makes "new" unavailable
     */
    protected function __construct()
    {
        $this->extractDevices();
        $this->extractIpAddresses();
    }

    /**
     * Singleton pattern implementation -  makes "clone" unavailable
     */
    protected function __clone()
    {
    }

    /**
     * Get Net instance
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Get network devices list
     *
     * @return array List of network devices
     */
    public function getDevices()
    {
        return array_keys($this->devices);
    }

    /**
     * Get IP addresses list
     *
     * @return array List of IP addresses
     */
    public function getIpAddresses()
    {
        return array_keys($this->ippAddresses);
    }

    /**
     * Extract network device data
     *
     * @çeturn void
     */
    protected function extractDevices()
    {
        exec('/bin/ip -o link show', $output, $ret);
        if ($ret > 0) {
            throw new \RuntimeException('Could not extract network device.');
        }

        foreach ($output as $line) {
            if (preg_match(
                '/
                    ^
                    [^\s]+       # identifier
                    :
                    \s+
                    (.*?)        # device name
                    (?:@[^\s]+)? # device name prefix
                    :
                    \s+
                    <(.*)>       # flags
                /x',
                $line,
                $matches
            )) {
                $this->devices[$matches[1]] = array(
                    'flags' => $matches[2]
                );
            }
        }
    }

    /**
     * Extract IP addresses data
     *
     * @return void
     */
    protected function extractIpAddresses()
    {
        exec('/bin/ip -o addr show', $output, $ret);
        if ($ret > 0) {
            throw new \RuntimeException('Could not extract IP addresses.');
        }

        foreach ($output as $line) {
            if (preg_match(
                '/
                    ^
                    [^\s]+                    # identifier
                    :
                    \s+
                    ([^\s]+)                  # device name
                    \s+
                    ([^\s]+)                  # protocol family identifier
                    \s+
                    (?:
                        ([^\s]+)              # IP address
                        (?:\s+peer\s+[^\s]+)? # peer address (pointopoint interfaces)
                        \/
                        ([\d]+)               # netmask in CIDR notation
                    )
                    \s+
                    (?:
                        .*?                   # optional broadcast address, scope information
                        (\1(?::\d+)?)         # optional label
                        \\\\
                    )?
                /x',
                $line,
                $matches
            )) {
                $this->ippAddresses[$matches[3]] = array(
                    'device' => $matches[1],
                    'version' => $matches[2] == 'inet' ? 'ipv4' : 'ipv6',
                    'prefix_length' => $matches[4],
                    'device_label' => isset($matches[5]) ? $matches[5] : ''
                );
            }
        }
    }
}
