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

/** @See iMSCP_Controller_Request_Interface */
require_once 'iMSCP/Controller/Request/Interface.php';

/**
 * Request class
 *
 * Some methods are based upon on the Zend Framework Zend_Controller_Request_Http class and
 * the linked code is subject to the new BSD license (http://framework.zend.com/license/new-bsd).
 *
 * @category    iMSCP
 * @package     iMSCP_Controller
 * @subpackage  Request
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Controller_Request implements iMSCP_Controller_Request_Interface
{
    /**
     * HTTP scheme
     */
    const SCHEME_HTTP = 'http';

    /**
     * HTTPS scheme
     */
    const SCHEME_HTTPS = 'https';

    /**
     * Tells whether or not the request was dispatched.
     *
     * @var bool
     */
    protected $_dispatched = false;

    /**
     * Allowed HTTP methods
     *
     * @var array
     */
    protected $_httpMethods = array('get', 'head', 'put', 'post', 'delete', 'options');

    /**
     * Request Uri
     *
     * @var string
     */
    protected $_requestUri;

    /**
     * Constructor
     *
     * @param string|iMSCP_Uri_Http $uri OPTIONAL URI on which operates
     * @todo it's not better to populate the request object only with URI object ?
     */
    public function __construct($uri = null)
    {
        if(null == $uri) {
            $this->setRequestUri();
        } else {
            if(!$uri instanceof iMSCP_Uri_Http) {
                $uri = new iMSCP_Uri_Http($uri);
            }

            if($uri->isValid()) {
                $query = $uri->getQuery();
                $this->setRequestUri($uri->getPath() . ($query !== '') ? "?{$query}" : '');
            } else {
                require_once 'iMSCP/Controller/Request/Exception.php';
                throw new iMSCP_Controller_Request_Exception('Invalid URI provided.');
            }
        }
    }

    /**
     * Returns the true HTTP request \method as a lowercase.
     *
     * If the request method is not listed in the $_httpMethods property
     * above, an exception is thrown.
     *
     * @return string the request method
     */
    public function requestMethod()
    {

    }

    /**
     * Returns the HTTP request method used for action processing, such as 'get'.
     *
     * Unlike {@link self::requestMethod}, this method returns 'get' for a HEAD
     * request because the two are functionally equivalent from the application's
     * perspective.
     *
     * @return void
     */
    public function method()
    {
      $this->requestMethod() == 'head' ? 'get' : $this->requestMethod();
    }

    /**
     * Is this a GET (or HEAD) request?
     *
     * @return bool
     */
    public function isGet()
    {
        return ($this->requestMethod() == 'get');
    }

    /**
     * Is this a POST request?
     *
     * @return bool
     */
    public function isPost()
    {
        return ($this->requestMethod() == 'post');
    }

    /**
     * Is this a PUT request?
     *
     * @return bool
     */
    public function isPut()
    {
        return ($this->requestMethod() == 'put');
    }

    /**
     * Is this a DELETE request?
     * 
     * @return bool
     */
    public function isDelete()
    {
        return ($this->requestMethod() == 'delete');
    }

    /**
     * Is this a HEAD request?
     *
     * Since {@link self::requestMethod()} sees HEAD as 'get', this method checks the actual HTTP method directly.
     *
     * @return bool
     */
    public function isHead()
    {
        return ($this->requestMethod() == 'head');
    }

    /**
     * Set flag indicating whether or not request has been dispatched.
     *
     * @license http://framework.zend.com/license/new-bsd
     * @param bool $flag
     * @return iMSCP_Controller_Request_Interface
     */
    public function setDispatched($flag = true)
    {
        $this->_dispatched = $flag ? true : false;

        return $this;
    }

    /**
     * Determine if the request has been dispatched.
     *
     * @license http://framework.zend.com/license/new-bsd
     * @return bool
     */
    public function isDispatched()
    {
        return $this->_dispatched;
    }

    /**
     * Returns request uri
     * 
     * @return string
     */
    public function getRequestUri()
    {
        if(empty($this->_requestUri)) {
            $this->setRequestUri();
        }

        return $this->_requestUri;
    }

    /**
     * Sets URI on which operates
     *
     * @param string|iMSCP_Uri_HTTP $requestUri OPTIONNAL URI on which operates
     * @return iMSCP_Controller_Request
     */
    public function setRequestUri($requestUri = null)
    {
        if (null === $requestUri) {
            if(isset($_SERVER['REQUEST_URI'])) {
                $requestUri = $_SERVER['REQUEST_URI'];
                // Http proxy reqs setup request uri with scheme and host [and port] + the url path, only use url path
                $schemeAndHttpHost = $this->getScheme() . '://' . $this->getHttpHost();
                if (strpos($requestUri, $schemeAndHttpHost) === 0) {
                    $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
                }
            } else {
                return $this;
            }
        } elseif (!is_string($requestUri)) {
            return $this;
        } else {
            // Set GET items, if available
            if (false !== ($pos = strpos($requestUri, '?'))) {
                // Get key => value pairs and set $_GET
                $query = substr($requestUri, $pos + 1);
                parse_str($query, $vars);
                $this->setQuery($vars);
            }
        }

        $this->_requestUri = $requestUri;
        return $this;
    }

    /**
     * Returns the request URI scheme
     *
     * @license http://framework.zend.com/license/new-bsd
     * @return string
     */
    public function getScheme()
    {
        return ($this->getServer('HTTPS') == 'on') ? self::SCHEME_HTTPS : self::SCHEME_HTTP;
    }

    /**
     * Retrieve a member of the $_SERVER superglobal
     *
     * If no $key is passed, returns the entire $_SERVER array.
     *
     * @param string $key OPTIONAL Keyname to be returned
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getServer($key = null, $default = null)
    {
        if (null === $key) {
            return $_SERVER;
        }

        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }

    /**
     * Get the HTTP host.
     *
     * "Host" ":" host [ ":" port ] ; Section 3.2.2
     * Note the HTTP Host header is not the same as the URI host.
     * It includes the port while the URI host doesn't.
     *
     * @return string
     */
    public function getHttpHost()
    {
        $host = $this->getServer('HTTP_HOST');

        if (!empty($host)) {
            return $host;
        }

        $scheme = $this->getScheme();
        $name   = $this->getServer('SERVER_NAME');
        $port   = $this->getServer('SERVER_PORT');

        if(null === $name) {
            return '';
        } elseif (($scheme == self::SCHEME_HTTP && $port == 80) || ($scheme == self::SCHEME_HTTPS && $port == 443)) {
            return $name;
        } else {
            return $name . ':' . $port;
        }
    }
}
