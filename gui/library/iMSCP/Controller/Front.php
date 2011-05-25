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
 * @copyright   2011 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        http://www.i-mscp.net i-mscp Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Front controller class
 *
 * Code base based upon on Zend_Controller component from Zend Framework.
 *
 * @category    iMSCP
 * @package     iMSCP_Controller
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Controller_Front
{
    /**
     * Singleton instance
     *
     * @var iMSCP_Controller_Front
     */
    protected static $_instance;

    /**
     * @var iMSCP_Controller_Dispatcher_Interface
     */
    protected $_dispatcher;

    /**
     * Instance of iMSCP_Controller_Router_Interface
     *
     * @var iMSCP_Controller_Router_Interface
     */
    protected $_router;

    /**
     * Instance of iMSCP_Controller_Request_Interface
     *
     * @var iMSCP_Controller_Request_Interface
     */
    protected $_request;

    /**
     * Instance of iMSCP_Controller_Request_Interface
     *
     * @var iMSCP_Controller_Response_Interface
     */
    protected $_response;

    /**
     * Constructor
     *
     * iMSCP_Controller_Front is a singleton object. You must use the {@link getInstance()} to instantiate.
     */
    protected function __construct()
    {
    }

    /**
     * iMSCP_Controller_Front is a singleton object - disallow cloning.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return iMSCP_Controller_Front
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self;
        }

        return self::$_instance;

    }

    /**
     * Sets dispatcher object.
     *
     * @param  iMSCP_Controller_Dispatcher_Interface $dispatcher
     * @return iMSCP_Controller_Front
     */
    public function setDispatcher(iMSCP_Controller_Dispatcher_Interface $dispatcher)
    {
        $this->_dispatcher = $dispatcher;

        return $this;
    }

    /**
     * Returns dispatcher object.
     *
     * @return iMSCP_Controller_Dispatcher_Interface
     */
    public function getDispatcher()
    {
        if (null === $this->_dispatcher) {
            require_once 'iMSCP/Controller/Dispatcher.php';
            $this->_dispatcher = new iMSCP_Controller_Dispatcher();
        }

        return $this->_dispatcher;
    }

    /**
     * Sets request object.
     *
     * @param iMSCP_Controller_Request_Interface $request
     */
    public function setRequest(iMSCP_Controller_Request_Interface $request)
    {
        $this->_request = $request;
    }

    /**
     * Returns request object.
     *
     * @return iMSCP_Controller_Request_Interface
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Sets response object.
     *
     * @param iMSCP_Controller_Response_Interface $response
     */
    public function setResponse(iMSCP_Controller_Response_Interface $response)
    {
        $this->_response = $response;
    }

    /**
     * Returns response object.
     *
     * @return iMSCP_Controller_Response_Interface
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Sets router object.
     *
     * @throws iMSCP_Controller_Exception
     * @param  iMSCP_Controller_Router_Interface $router
     * @return iMSCP_Controller_Front
     */
    public function setRouter(iMSCP_Controller_Router_Interface $router)
    {
        $router->setFrontController($this);
        $this->_router = $router;

        return $this;
    }

    /**
     * Returns router object.
     *
     * @return iMSCP_Controller_Router_Interface
     */
    public function getRouter()
    {
        if (null == $this->_router) {
            require_once 'iMSCP/Controller/Router.php';
            $this->setRouter(new iMSCP_Controller_Router());
        }

        return $this->_router;
    }

    /**
     * Dispatch the request.
     *
     * @throws Exception
     * @param iMSCP_Controller_Request_Interface|null $request
     * @param iMSCP_Controller_Response_Interface|null $response
     * @return void
     */
    public function dispatch(iMSCP_Controller_Request_Interface $request = null,
        iMSCP_Controller_Response_Interface $response = null)
    {
        // Instantiate request object if needed
        if (null !== $request) {
            $this->setRequest($request);
        } elseif (null === $this->getRequest()) {
            require_once 'iMSCP/Controller/Request.php';
            $request = new iMSCP_Controller_Request();
            $this->setRequest($request);
        }

        // Instantiate response object if needed
        if (null !== $response) {
            $this->setResponse($response);
        } elseif (null === $this->getResponse()) {
            require_once 'iMSCP/Controller/Response.php';
            $response = new iMSCP_Controller_Response();
            $this->setResponse($response);
        }

        // Initialize the router
        $router = $this->getRouter();

        // Initialize the dispatcher
        $dispatcher = $this->getDispatcher()
            ->setResponse($this->_response);

        // Dispatching
        try {
            try {
                $router->route($this->_request);
            } catch (Exception $e) {
                throw $e;
            }

            // Starting dispath loop
            do {
                $this->_request->setDispatched(true);

                // Dispatch the request
                try {
                    $dispatcher->dispatch($this->_request, $this->_response);
                } catch (Exception $e) {
                    throw $e;
                }
            } while (!$request->isDispatched());

        } catch (Exception $e) {
            throw $e;
        }

        echo 'The request was dispatched!';
        // Send the response to the client
        //$this->_response->sendResponse();
    }
}
