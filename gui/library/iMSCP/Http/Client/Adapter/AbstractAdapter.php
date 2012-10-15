<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2012 by i-MSCP Team
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
 *
 * @category    iMSCP
 * @package     iMSCP_Http
 * @subpackage  Client
 * @copyright   2010 - 2012 by -MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iMSCP\Http\Client\Adapter;

use iMSCP\Http\Client;

/**
 * iMSCP_Http_Client_Adapter_Abstract abstract class
 *
 * @category    iMSCP
 * @package     iMSCP_Http
 * @subpackage  Client
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
abstract class AbstractAdapter
{
    /**
     * @var array Default options
     */
    protected $options = array();

    /**
     * Set options by merging them with default and any previously set options
     *
     * @param array $options OPTIONAL options
     * @return AbstractAdapter
     */
    function setOptions(array $options = array())
    {
        foreach ($options as $name => $value) {
            $this->options[strtolower($name)] = $value;
        }

        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Returns options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Proxy to iMSCP_Http_Client::parseRawResponse()
     *
     * @see iMSCP_Http_Client::parseRawResponse
     * @param string $response Raw server response
     * @return array An array containing the response parts including http version, status code, reason phrase, headers, cookies and body
     */
    public function parseRawResponse($response)
    {
        return Client::parseRawResponse($response);
    }

    /**
     * Do an HTTP request
     *
     * @param array $url URL components
     * @param bool $secure Flag indicating whether it's a secure HTTP request
     * @param array $headers Request headers
     * @param string $body Request body
     * @return string Raw server response
     */
    abstract public function doRequest($url, $secure = false, $headers = array(), $body = '');


    /**
     * Close server connection
     *
     * @return void
     */
    abstract public function close();
}
