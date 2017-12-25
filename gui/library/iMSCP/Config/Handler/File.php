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

use iMSCP_Config_Handler as ConfigHandler;
use iMSCP_Exception as iMSCPException;

/**
 * Class to handle configuration parameters from a flat file.
 *
 * ConfigHandler adapter class to handle configuration parameters that are
 * stored in a flat file where each pair of key-values are separated by the
 * equal sign.
 */
class iMSCP_Config_Handler_File extends ConfigHandler
{
    /**
     * Configuration file path
     *
     * @var string
     */
    protected $pathFile;

    /**
     * Loads all configuration parameters from a flat file
     *
     * Default file path is set to {/usr/local}/etc/imscp/imscp.conf depending
     * of distribution.
     *
     * @param string $pathFile Configuration file path
     */
    public function __construct($pathFile = NULL)
    {
        if (is_null($pathFile)) {
            if (getenv('IMSCP_CONF')) {
                $pathFile = getenv('IMSCP_CONF');
            } else {
                switch (PHP_OS) {
                    case 'FreeBSD':
                    case 'OpenBSD':
                    case 'NetBSD':
                        $pathFile = '/usr/local/etc/imscp/imscp.conf';
                        break;
                    default:
                        $pathFile = '/etc/imscp/imscp.conf';
                }
            }
        }

        $this->pathFile = $pathFile;
        $this->parseFile();
    }

    /**
     * Opens a configuration file and parses its Key = Value pairs
     *
     * @throws iMSCPException
     * @return void
     */
    protected function parseFile()
    {
        if (($fd = @file_get_contents($this->pathFile)) == false) {
            throw new iMSCPException(sprintf("Couldn't open the %s configuration file", $this->pathFile));
        }

        foreach (explode(PHP_EOL, $fd) as $line) {
            if (!empty($line) && $line[0] != '#' && strpos($line, '=')) {
                list($key, $value) = explode('=', $line, 2);
                $this[trim($key)] = trim($value);
            }
        }
    }
}
