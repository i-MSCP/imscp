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
 * @subpackage  Router
 * @copyright   2011 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        http://www.i-mscp.net i-mscp Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Router interface
 *
 * @category    iMSCP
 * @package     iMSCP_Controller
 * @subpackage  Router
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
interface iMSCP_Controller_Router_Interface
{
    /**
     * Adds route to the routes stack.
     *
     * @abstract
     * @param iMSCP_Controller_Router_Route_Interface $route
     * @return iMSCP_Controller_Router_Interface
     */
    public function addRoute(iMSCP_Controller_Router_Route_Interface $route);

    /**
     * Adds routes to the routes stack.
     *
     * @abstract
     * @param array $routes
     * @return iMSCP_Controller_Router_Interface
     */
    public function addRoutes(array $routes);

    /**
     * Remove a route from the routes stack.
     *
     * @abstract
     * @thrown iMSCP_Controller_Router if the route name is not defined in the routes stack.
     * @param  string $name route name
     * @return iMSCP_Controller_Router_Interface
     */
    public function removeRoute($name);

    /**
     * Check if a named route is defined in the routes stack.
     *
     * @abstract
     * @param  string $name route name
     * @return bool
     */
    public function hasRoute($name);

    /**
     * Returns a route from the routes stack.
     *
     * @abstract
     * @throws iMSCP_Controller_Router_Exception if the route name is not defined in the routes stack.
     * @param  string $name route name
     * @return iMSCP_Controller_Router_Route_Interface
     */
    public function getRoute($name);

    /**
     * Route a request and sets its controller and action.
     *
     * If not route match the request, an exception is thrown.
     *
     * @abstract
     * @throws iMSCP_Controller_Router_Exception
     * @param iMSCP_Controller_Request_Interface $request
     * @return iMSCP_Controller_Request_Interface
     */
    public function route(iMSCP_Controller_Request_Interface $request);
}
