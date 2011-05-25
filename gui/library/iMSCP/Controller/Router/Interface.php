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
     * Sets Front Controller object.
     *
     * @abstract
     * @param iMSCP_Controller_Front $controller
     * @return Zend_Controller_Router_Interface
     */
    public function setFrontController(iMSCP_Controller_Front $controller);

    /**
     * Returns Front Controller object.
     *
     * @abstract
     * @return iMSCP_Controller_Front
     */
    public function getFrontController();

    /**
     * Route a request and sets its controller and action.
     *
     * If not route was found, an exception is thrown.
     *
     * @abstract
     * @throws iMSCP_Controller_Router_Exception
     * @param iMSCP_Controller_Request_Interface $dispatcher
     * @return iMSCP_Controller_Request_Interface
     */
    public function route(iMSCP_Controller_Request_Interface $dispatcher);
}
