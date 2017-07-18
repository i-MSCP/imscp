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
    protected $devices = [];

    /**
     * @var array IP addresses data
     */
    protected $ipAddresses = [];

    /**
     * @var bool Whether or not NIC and IP data were loaded
     */
    protected $loadedData = false;

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
     * @return Net
     */
    static public function getInstance()
    {
        if (NULL === self::$instance) {
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
        $this->loadData();
        return array_keys($this->devices);
    }

    /**
     * Get IP addresses list
     *
     * @return array List of IP addresses
     */
    public function getIpAddresses()
    {
        $this->loadData();
        return array_keys($this->ipAddresses);
    }

    /**
     * Get version of the given IP address
     *
     * @param string $ipAddr IP address
     * @return int IP address version
     */
    public function getVersion($ipAddr)
    {
        return (strpos($ipAddr, ':') !== false) ? 6 : 4;
    }

    /**
     * Get prefix length of the given IP address
     *
     * @param string $ipAddr IP address
     * @return array|null
     */
    public function getIpPrefixLength($ipAddr)
    {
        $this->loadData();
        $ipAddr = $this->compress($ipAddr);

        if (isset($this->ipAddresses[$ipAddr])) {
            return $this->ipAddresses[$ipAddr]['prefix_length'];
        }

        return NULL;
    }

    /**
     * Compress the given IP
     *
     * @param string $ipAddr IP address
     * @return mixed
     */
    public function compress($ipAddr)
    {
        $ipAddr = $this->expand($ipAddr);

        $ipp = explode(':', $ipAddr);

        for ($i = 0; $i < count($ipp); $i++) {
            $ipp[$i] = dechex(hexdec($ipp[$i]));
        }

        $ipAddr = ':' . join(':', $ipp) . ':';
        preg_match_all('/(:0)(:0)+/', $ipAddr, $zeros);

        if (count($zeros[0]) > 0) {
            $match = '';
            foreach ($zeros[0] as $zero) {
                if (strlen($zero) > strlen($match)) {
                    $match = $zero;
                }
            }

            $ipAddr = preg_replace('/' . $match . '/', ':', $ipAddr, 1);
        }

        $ipAddr = preg_replace('/((^:)|(:$))/', '', $ipAddr);
        return preg_replace('/((^:)|(:$))/', '::', $ipAddr);
    }

    /**
     * Expand the given IP address
     *
     * @param string $ipAddr IP address
     * @return string
     */
    public function expand($ipAddr)
    {
        if (false !== strpos($ipAddr, '::')) {
            list($ip1, $ip2) = explode('::', $ipAddr);

            if ('' == $ip1) {
                $c1 = -1;
            } else {
                $c1 = (0 < ($pos = substr_count($ip1, ':'))) ? $pos : 0;
            }

            if ('' == $ip2) {
                $c2 = -1;
            } else {
                $c2 = (0 < ($pos = substr_count($ip2, ':'))) ? $pos : 0;
            }

            if (strstr($ip2, '.')) {
                $c2++;
            }

            if (-1 == $c1 && -1 == $c2) {
                $ipAddr = '0:0:0:0:0:0:0:0';
            } elseif (-1 == $c1) {
                $fill = str_repeat('0:', 7 - $c2);
                $ipAddr = str_replace('::', $fill, $ipAddr);
            } elseif (-1 == $c2) {
                $fill = str_repeat(':0', 7 - $c1);
                $ipAddr = str_replace('::', $fill, $ipAddr);
            } else {
                $fill = str_repeat(':0:', 6 - $c2 - $c1);
                $ipAddr = str_replace('::', $fill, $ipAddr);
                $ipAddr = str_replace('::', ':', $ipAddr);
            }
        }

        $uipT = [];
        $uiparts = explode(':', $ipAddr);

        foreach ($uiparts as $p) {
            $uipT[] = sprintf('%04s', $p);
        }

        return implode(':', $uipT);
    }

    /**
     * Load IP data
     *
     * @return void
     */
    protected function loadData()
    {
        if ($this->loadedData) {
            return;
        }

        $this->extractDevices();
        $this->extractIpAddresses();
        $this->loadedData = true;
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
            throw new \RuntimeException("Couldn't extract network device.");
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
                $this->devices[$matches[1]] = [
                    'flags' => $matches[2]
                ];
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
            throw new \RuntimeException("Couldn't extract IP addresses.");
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
                $this->ipAddresses[$this->compress($matches[3])] = [
                    'device'        => $matches[1],
                    'version'       => $matches[2] == 'inet' ? 'ipv4' : 'ipv6',
                    'prefix_length' => $matches[4],
                    'device_label'  => isset($matches[5]) ? $matches[5] : ''
                ];
            }
        }
    }
}
