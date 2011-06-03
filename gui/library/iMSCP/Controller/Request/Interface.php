<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2011 i-MSCP Team
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
 * @package     iMSCP_Controller
 * @subpackage  Request
 * @copyright   2011 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        http://www.i-mscp.net i-mscp Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Request interface
 *
 * @category    iMSCP
 * @package     iMSCP_Controller
 * @subpackage  Request
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
interface iMSCP_Controller_Request_Interface
{
    /**
     * Set flag indicating whether or not request has been dispatched.
     *
     * @abstract
     * @param bool $flag
     * @return iMSCP_Controller_Request_Interface
     */
    public function setDispatched($flag = true);

    /**
     * Determine if the request has been dispatched.
     *
     * @abstract
     * @return boolean
     */
    public function isDispatched();

    /**
     * Returns request uri
     *
     * @abstract
     * @return string
     */
    public function getRequestUri();

    /**
     * Sets URI on which operates
     *
     * @abstract
     * @param string|iMSCP_Uri_Http $requestUri OPTIONNAL URI on which operates
     * @return iMSCP_Controller_Request_Interface
     */
    public function setRequestUri($requestUri = null);

    /**
     * Returns the scheme component from the URI
     *
     * @abstract
     * @return string
     */
    public function getScheme();

    /**
     * Returns query component of the URI.
     *
     * @abstract
     * @return string
     */
    public function getQuery();

    /**
     * Sets the query component from the URI.
     *
     * @abstract
     * @param  $query
     * @return void
     */
    public function setQuery($query);

    /**
     * Returns the path component from the URI
     *
     * @abstract
     * @return string
     */
    public function getPath();

}
