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
 * @subpackage  Dispatcher
 * @copyright   2011 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        http://www.i-mscp.net i-mscp Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Dispatcher interface
 *
 * @category    iMSCP
 * @package     iMSCP_Controller
 * @subpackage  Dispatcher
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
interface iMSCP_Controller_Dispatcher_Interface
{
    /**
     * Set the response object to use, if any.
     *
     * @abstract
     * @param iMSCP_Controller_Response_Interface|null $response
     * @return void
     */
    public function setResponse(iMSCP_Controller_Response_Interface $response = null);

    /**
     * Returns response object if one is set.
     *
     * @abstract
     * @return iMSCP_Controller_Response_Abstract|null
     */
    public function getResponse();

    /**
     * Dispatches a request object to a controller/action.
     *
     * If the action requests a forward to another action, a new request will be returned.
     *
     * @abstract
     * @param iMSCP_Controller_Request_Interface $request
     * @param iMSCP_Controller_Response_Interface $response
     * @return void
     */
    public function dispatch(iMSCP_Controller_Request_Interface $request,
        iMSCP_Controller_Response_Interface $response);
}
