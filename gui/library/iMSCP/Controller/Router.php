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

/** @See iMSCP_Controller_Router_Interface */
require_once 'iMSCP/Controller/Router/Interface.php';

/**
 * Router class
 *
 * @category    iMSCP
 * @package     iMSCP_Controller
 * @subpackage  Router
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Controller_Router implements iMSCP_Controller_Router_Interface
{
    /**
     * Routes stack to match against.
     *
     * @var array()
     */
    protected $_routes = array();


    /**
     * Matched route.
     *
     * @var iMSCP_Controller_Router_Route_Interface|null
     */
    protected $_matchedRoute = null;

    /**
     * Adds route to the routes stack.
     * 
     * @param iMSCP_Controller_Router_Route_Interface $route
     * @return iMSCP_Controller_Router Provides fluent interface
     */
    public function addRoute(iMSCP_Controller_Router_Route_Interface $route)
    {
        $this->_routes[$route->getName()] = $route;

        return $this;
    }

    /**
     * Adds routes to the routes stack.
     * 
     * @param array $routes 
     * @return iMSCP_Controller_Router Provides fluent interface
     */
    public function addRoutes(array $routes)
    {
        foreach($routes as $route) {
            $this->addRoute($route);
        }

        return $this;
    }

    /**
     * Remove a route from the routes stack.
     *
     * @thrown iMSCP_Controller_Router if the route name is not defined in the routes stack.
     * @param  string $name Route name
     * @return iMSCP_Controller_Router Provides fluent interface
     */
    public function removeRoute($name)
    {
        if (!isset($this->_routes[$name])) {
            require_once 'iMSCP/Controller/Router/Exception.php';
            throw new iMSCP_Controller_Router_Exception("Route $name is not defined in the routes stack.");
        }

        unset($this->_routes[$name]);

        return $this;
    }

    /**
     * Check if a named route is defined in the routes stack.
     *
     * @param  string $name Route name
     * @return bool
     */
    public function hasRoute($name)
    {
        return isset($this->_routes[$name]);
    }

    /**
     * Returns a route from the routes stack.
     * 
     * @throws iMSCP_Controller_Router_Exception f the route name is not defined in the routes stack.
     * @param  string $name Route name
     * @return iMSCP_Controller_Router_Route_Interface
     */
    public function getRoute($name)
    {
        if (!isset($this->_routes[$name])) {
            require_once 'iMSCP/Controller/Router/Exception.php';
            throw new iMSCP_Controller_Router_Exception("Route $name is not defined in the routes stack.");
        }

        return $this->_routes[$name];
    }

    /**
     * Route a request and sets its controller and action.
     *
     * If not route was found, an exception is thrown.
     *
     * @throws iMSCP_Controller_Router_Exception
     * @param iMSCP_Controller_Request_Interface $request
     * @return iMSCP_Controller_Request_Interface
     */
    public function route(iMSCP_Controller_Request_Interface $request)
    {
        // Ensure we have a default route
        if(empty($this->_routes)) {
            require_once 'iMSCP/Controller/Router/Route/catchall.php';
            $route = new iMSCP_Controller_Router_Route_Catchall();
            $this->addRoute($route);
        }

        // retrieve URI path info from request object
        $pathInfo = $request->getPathInfo();

        foreach(array_reverse($this->_routes, true) as $route) {
            // Check if the route match against the URI
            if($route->match($pathInfo)) {
                $this->_matchedRoute = $route;
            }
        }

        if(null === $this->_matchedRoute) {
            require_once 'iMSCP/Controller/Router/Exception.php';
            throw new iMSCP_Controller_Router_Exception('No route matched the request', 404);
        }
    }
}
