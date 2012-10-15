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

namespace iMSCP\Http;

use iMSCP\Http\Client\Adapter\AbstractAdapter;
use iMSCP\Http\Client\Adapter\Socket;

/**
 * iMSCP_Http_Client class
 *
 * Class allowing to make HTTP requests.
 *
 * Note: This is a first implementation that doesn't provide full options such as streaming and files upload.
 *
 * @category    iMSCP
 * @package     iMSCP_Http
 * @subpackage  Client
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class Client
{
    /**
     * @var array Supported HTTP authentication methods (digest still not implemented)
     */
    protected $supportedAuthMethod = array('basic', 'digest');

    /**
     * @var array Supported HTTP version
     */
    protected $httpVersions = array('1.0', '1.1');

    /**
     * @var array Allowed HTTP methods
     */
    protected $httpMethods = array('HEAD', 'GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'TRACE');

    /**
     * @var array Default options
     */
    protected $options = array(
        'maxredirects' => 5,
        'strictredirects' => false,
        'useragent' => 'iMSCP/1.1.0 iMSCP_Http_Client Mozilla/5.0',
        'timeout' => 10,
        'httpversion' => '1.1',
        'keepalive' => false,
        'rfc3986strict' => false,
        'encodecookies' => true,

        // HTTP parameters
        'method' => 'GET', // HTTP method to use (see above)
        'headers' => null, // OPTIONAL An array containing raw header string or an associative array of header fieldname/fieldvalue
        'cookies' => null, // OPTIONAL an array of cookies
        'body' => null, // OPTIONAL Either a raw string representing request body or an array|object describing POST parameters
    );

    /**
     * @var AbstractAdapter
     */
    protected $adapter = null;

    /**
     * @var array|null Holds authentication data used to create authentication header (OPTIONAL)
     */
    protected $authData = null;

    /**
     * @var string|null Enctype
     */
    protected $encType = null;

    /**
     * @var int Number of redirections made
     */
    protected $redirectCount = 0;

    /**
     * Constructor
     *
     * See above and in client adapter class for list of available options.
     *
     * Examples for the headers, cookies and body options:
     *
     * array(
     *  'headers' => array('
     *        'User-Agent' => 'iMSCP',
     *        'Accept-Encoding' => 'gzip, deflate',
     *        'Connection' => Keepalive'
     *    ),
     *  'cookies => array(
     *        array(
     *            'name' => 'cookie1',
     *            'value' => 'cookie value',
     *            'expires' => '1381791292',
     *            'path' => '/',
     *            'domain' => '.nuxwin.com'
     *         ...
     *     ),
     *  'body' => array(
     *        'param1' => 'value'
     *        'param2 => 'value'
     *  )
     * )
     *
     * Also possible for header and body options:
     *
     * array(
     *  'headers' => "User-Agent: iMSCP\r\nAccept-Encoding: gzip, deflate\r\nConnection: Keepalive\r\n",
     *  'body' => 'param1=value&param2=&value'
     * )
     *
     * @param null $options OPTIONAL Options
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            $this->setOptions($options);
        }
    }

    /**
     * Set options by merging them with default and any previously set options
     *
     * @param array $options Options
     * @return Client
     */
    public function setOptions(array $options = array())
    {
        // Process some normalization
        $options = array_change_key_case($options, CASE_LOWER);

        if (isset($options['headers']) && is_array($options['headers'])) {
            $options['headers'] = array_change_key_case($options['headers'], CASE_LOWER);
        }

        if (isset($options['cookies']) && is_array($options['cookies'])) {
            foreach ($options['cookies'] as &$cookie) {
                $cookie = array_change_key_case($cookie, CASE_LOWER);
            }
        }

        $this->options = array_merge($this->options, $options);

        // Pass configuration options to the adapter if it exists
        if ($this->adapter) {
            $this->adapter->setOptions($this->options);
        }

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
     * Set client adapter
     *
     * @param AbstractAdapter $adapter
     * @return Client
     */
    public function setAdapter(AbstractAdapter $adapter)
    {
        $this->adapter = $adapter;
        $this->adapter->setOptions($this->options);

        return $this;
    }

    /**
     * Returns client adapter
     *
     * @return AbstractAdapter
     */
    protected function getAdapter()
    {
        if (null === $this->adapter) {
            $this->setAdapter(new Socket());
        }

        return $this->adapter;
    }

    /**
     * Do an HTTP request
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @param string $url URL
     * @param array $options OPTIONAL Options
     * @return array Array reprensenting HTTP response
     */
    public function doRequest($url, array $options = null)
    {
        if (null !== $options) {
            $this->setOptions($options);
        }

        // Get client adapter
        $adapter = $this->getAdapter();

        // Parse URL
        $url = $this->parseUrl($url);

        // Check HTTP version
        if (!in_array($this->options['httpversion'], $this->httpVersions)) {
            throw new \InvalidArgumentException(
                sprintf("Http request failed: Http version '%s' not supported", $this->options['httpversion'])
            );
        }

        do {
            // Check HTTP method
            $this->options['method'] = strtoupper($this->options['method']);
            if (!in_array($this->options['method'], $this->httpMethods)) {
                throw new \InvalidArgumentException(
                    sprintf("Http request failed: HTTP method '%s' not supported", $this->options['method'])
                );
            }

            // Prepare HTTP request body
            $body = $this->prepareBody($this->options['body']);

            // Prepare HTTP request headers
            $headers = $this->prepareHeaders($body, $url);

            // Is a secure request?
            $secure = ($url['scheme'] == 'https');

            // Prepare cookie header
            $cookieHeaderValue = $this->prepareCookieHeader($url);
            if (!empty($cookieHeaderValue)) {
                $headers['Cookie'] = $cookieHeaderValue;
            }

            // Process the request by using the client adapter
            $response = $adapter->doRequest($url, $secure, $headers, $body);

            if (!$response) {
                throw new \RuntimeException('Http request failed: Unable to read the response or response is empty');
            }

            ########################################## Response treatment ##############################################

            // Parse the server response from the client adapter
            $response = static::parseRawResponse($response);

            if (!empty($response['cookies'])) {
                array_merge((array)$this->options['cookies'], $response['cookies']);
            }

            // If we got redirected, look for the 'Location' header
            $statusCode = $response['status_code'];
            if ((300 <= $statusCode && 400 > $statusCode) && isset($response['headers']['location'])) {
                // Avoid problems with buggy servers that add whitespace at the end of some headers
                $location = trim($response['headers']['location']);

                // Check whether we send the exact same request again, or drop the parameters and send a GET request
                if ($statusCode == 303 || ((!$this->options['strictredirects']) && ($statusCode == 302 || $statusCode == 301))) {
                    $this->resetParameters();
                }

                // If we got a well formed absolute URI
                if (($scheme = substr($location, 0, 6)) && ($scheme == 'http:/' || $scheme == 'https:')) {
                    $url = $this->parseUrl($location);
                } else { // We god only relative path
                    // Split into path and query and set the query
                    if (strpos($location, '?') !== false) {
                        list($location, $query) = explode('?', $location, 2);
                    } else {
                        $query = null;
                    }
                    $url['query'] = $query;

                    // Replace the current URL path
                    $url['path'] = rtrim(substr($url['path'], 0, strrpos($url['path'], '/')), '/') . '/' . $location;
                }

                ++$this->redirectCount;
            } else {
                // If we didn't get any location, stop redirecting
                break;
            }
        } while ($this->redirectCount < $this->options['maxredirects']);

        $adapter->close();

        return $response;
    }

    /**
     * Do an HEAD request
     *
     * @param string $url URL
     * @param array $options Request options
     * @return array An array containing all HTTP response elements (HTTP status code, Headers and Cookies)
     */
    public function doHeadRequest($url, array $options = array())
    {
        $options['method'] = 'HEAD';
        return $this->doRequest($url, $options);
    }

    /**
     * Do an HTTP GET request
     *
     * @param string $url URL
     * @param array $options Request options
     * @return array An array containing all HTTP response elements (HTTP status code, Headers and Cookies)
     */
    public function doGetRequest($url, array $options = array())
    {
        $options['method'] = 'GET';
        return $this->doRequest($url, $options);
    }

    /**
     * Do an HTTP POST request
     *
     * @param string $url URL
     * @param array $options Request options
     * @return array An array containing all HTTP response elements (HTTP status code, Headers and Cookies)
     */
    public function doPostRequest($url, array $options = array())
    {
        $options['method'] = 'POST';
        return $this->doRequest($url, $options);
    }

    /**
     * Do an HTTP PUT request
     *
     * @param string $url URL
     * @param array $options Request options
     * @return array An array containing all HTTP response elements (HTTP status code, Headers and Cookies)
     */
    public function doPutRequest($url, array $options = array())
    {
        $options['method'] = 'PUT';
        return $this->doRequest($url, $options);
    }

    /**
     * Do an HTTP DELETE request
     *
     * @param string $url URL
     * @param array $options Request options
     * @return array An array containing all HTTP response elements (HTTP status code, Headers and Cookies)
     */
    public function doDeleteRequest($url, array $options = array())
    {
        $options['method'] = 'DELETE';
        return $this->doRequest($url, $options);
    }

    /**
     * Do an HTTP PATCH request
     *
     * @param string $url URL
     * @param array $options Request options
     * @return array An array containing all HTTP response elements (HTTP status code, Headers and Cookies)
     */
    public function doPatchRequest($url, array $options = array())
    {
        $options['method'] = 'PATCH';
        return $this->doRequest($url, $options);
    }

    /**
     * Do an HTTP TRACE request
     *
     * @param string $url URL
     * @param array $options Request options
     * @return array An array containing all HTTP response elements (HTTP status code, Headers and Cookies)
     */
    public function doTraceRequest($url, array $options = array())
    {
        $options['method'] = 'TRACE';
        return $this->doRequest($url, $options);
    }

    /**
     * Parse a raw HTTP response to extract all its parts (Status-Line, headers and body)
     *
     * Assume an HTTP message such as:
     *
     * -------------------------------------------
     * | Status-Line                             |
     * -------------------------------------------
     * | *(message-header CRLF)                  |
     * -------------------------------------------
     * | CRLF                                    |
     * -------------------------------------------
     * | [ message-body ]                        |
     * -------------------------------------------
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @param string $response Raw response
     * @return array An array containing the response parts including http version, status code, reason phrase, headers, cookies and body
     */
    public static function parseRawResponse($response)
    {
        if (!is_string($response)) {
            throw new \InvalidArgumentException('Http request failed: Expects a string representing server response');
        }

        // Extract response stanza (Ensure last stanza will not comme with CRLF at end by cutting it if any)
        $response = explode("\r\n\r\n", $response, 3);

        list($statusLine, $headers) = explode("\n", str_replace("\n\r", "\n", $response[0]), 2);

        // Retrieve headers
        $headers = static::parseRawHeaders($headers);

        // Retrieve cookies
        $cookies = array();

        if (!empty($headers['set-cookie'])) {
            foreach ((array)$headers['set-cookie'] as $cookie) {
                $cookie = static::parseSetCookieHeader($cookie);
                $cookies[$cookie['name']] = $cookie;
            }
            // Remove the Set-Cookie header from headers stack since the cookies have their own stack
            unset($headers['set-cookie']);
        }

        $body = $response[1];

        // Try to extract status-line parts (Http version, Status code and reason phrase
        if (!preg_match('/^HTTP\/(?P<version>1\.[01]) (?P<status>\d{3})(?:[ ]+(?P<reason>.*))?$/', $statusLine, $statusLine)) {
            throw new \RuntimeException('Http request failed: Response Status line was not found or is invalid');
        }

        if (!empty($body)) {
            // Decode chunked response
            if (isset($headers['Transfer-Encoding']) && strtolower($headers['Transfer-Encoding']) == 'chunked') {
                $body = static::decodeChunckedMessage($body);
            }

            if (isset($headers['content-encoding'])) {
                if ($headers['content-encoding'] == 'gzip') {
                    // Decode gzip encoded message
                    $body = static::decodeGzipMessage($body);
                } elseif ($headers['content-encoding'] == 'deflate') {
                    // Decode zlib deflated message
                    $body = static::decodeDeflateMessage($body);
                }
            }
        }

        return array(
            'http_version' => $statusLine['version'],
            'status_code' => $statusLine['status'],
            'reason_phrase' => $statusLine['reason'],
            'headers' => $headers,
            'cookies' => $cookies,
            'body' => $body
        );
    }

    /**
     * Parse the given URL
     *
     * @throws \InvalidArgumentException
     * @param string $url URL
     * @return array Array containing URL components
     */
    protected function parseUrl($url)
    {
        if (!is_string($url)) {
            throw new \InvalidArgumentException('Http request failed: URL must be a string');
        }

        $url = @parse_url($url);

        // Check url scheme
        if (empty($url) || empty($url['scheme'])) {
            throw new \InvalidArgumentException('Http request failed: Invalid URL provided; Scheme not found');
        } elseif ($url['scheme'] != 'http' && $url['scheme'] != 'https') {
            throw new \InvalidArgumentException(sprintf("Http request failed: Scheme '%s' not supported", $url['scheme']));
        }

        // Set auth data if any
        if (isset($url['user']) && isset($url['password'])) {
            $this->setAuthData($url['user'], $url['password']);
        }

        // Todo Should we convert idn hosts to ASCII?

        // Set port if not arlready there
        if (empty($url['port'])) {
            $url['port'] = ($url['scheme'] == 'https' || $url['scheme'] == 'ssl') ? 443 : 80;
        }

        if (empty($url['path'])) {
            $url['path'] = '/';
        }

        // Make the URL query strickly RFC compliant if needed
        if (!empty($url['query']) && $this->options['rfc3986strict']) {
            $url['query'] = str_replace('+', '%20', $url['query']);
        } elseif (empty($url['query'])) {
            $url['query'] = null;
        }

        // Todo: Should we support fragment?

        return $url;
    }

    /**
     * Set authentication data
     *
     * Authentication data are used to create an "Authorization" header according to the specified user, password and
     * authentication method (for now, only the HTTP Basic authentication is implemented).
     *
     * @throws \InvalidArgumentException
     * @param string $user Username
     * @param string $password Password
     * @param string $authType HTTP Authentication type
     * @return Client
     */
    protected function setAuthData($user, $password, $authType = 'basic')
    {
        if (!in_array($authType, $this->supportedAuthMethod)) {
            throw new \InvalidArgumentException(
                sprintf("Http request failed: HTTP authentication type '%s' not supported", $authType)
            );
        }

        if (empty($user) || empty($password)) {
            throw new \InvalidArgumentException('Http request failed: The username and the password cannot be empty');
        }

        $this->authData = array('user' => $user, 'password' => $password, 'type' => $authType);

        return $this;
    }

    /**
     * Parse raw headers
     *
     * Parse a raw headers string into an array of headers. If an header is found multiple time it's returned as
     * numeric array.
     *
     * @throws \InvalidArgumentException
     * @param string $headers Raw headers
     * @return array Array containing headers
     */
    protected static function parseRawHeaders($headers)
    {
        // Split headers, one per array element
        if (!is_string($headers)) {
            throw new \InvalidArgumentException('Http request failed: Raw headers must be a string.');
        } else {
            // 1. Tolerate line terminator: CRLF = LF  - (Tolerant Applications See RFC 2616 19.3)
            // 2. Unfold folded header fields. See RFC 2616 2.2
            // 3. Create the headers array
            $headers = explode("\n", preg_replace('/\n[ \t]/', ' ', str_replace("\r\n", "\n", $headers)));
            $headersArr = array();

            // If a redirection has taken place, The headers for each page request may have been passed.
            // In this case, determine the final HTTP header and parse from there.
            //for ($i = count($headers) - 1; $i >= 0; $i--) {
            //	if (!empty($headers[$i]) && false === strpos($headers[$i], ':')) {
            //		$headers = array_splice($headers, $i);
            //		break;
            //	}
            //}

            foreach ((array)$headers as $header) {
                if (!empty($header)) {
                    list($key, $value) = explode(':', $header, 2);

                    if (!empty($value)) {
                        $key = strtolower($key);

                        if (isset($headersArr[$key])) {
                            if (!is_array($headersArr[$key])) {
                                $headersArr[$key] = array($headersArr[$key]);
                            }

                            $headersArr[$key][] = trim($value);
                        } else {
                            $headersArr[$key] = trim($value);
                        }
                    }
                }
            }
        }

        return $headersArr;
    }

    /**
     * Prepare request headers
     *
     * @throws \InvalidArgumentException
     * @param string $body Request body
     * @param array $url URL components
     * @return array Request headers
     */
    protected function prepareHeaders($body, $url)
    {
        $headers = $this->options['headers'];

        if (!empty($headers)) {
            // If we got raw headers string, we parse it
            if (is_string($headers)) {
                $headers = $this->parseRawHeaders($headers);
                $this->options['headers'] = $headers; // Avoid to repeat this job many times in case we got redirects
            }
        } else {
            $headers = array();
        }

        $newHeaders = array();

        // Set Host header
        if ($this->options['httpversion'] == '1.1') {
            $host = $url['host'];
            // If the port is not default, add it
            if (!(($url['scheme'] == 'http' && $url['port'] == 80) || ($url['scheme'] == 'https' && $url['port'] == 443))) {
                $host .= ':' . $url['port'];
            }

            $newHeaders['Host'] = $host;
        }

        // Set Connection header if not already there
        if (!isset($headers['connection'])) {
            if (!$this->options['keepalive']) {
                $newHeaders['connection'] = 'close';
            }
        }

        // Set the Accept-Encoding header if not already there - depending on whether zlib is available or not.
        // Todo should we add qvalue?
        if (!isset($headers['accept-encoding'])) {
            if (function_exists('gzinflate')) {
                $newHeaders['accept-encoding'] = 'gzip, deflate';
            } else {
                $newHeaders['accept-encoding'] = 'identity';
            }
        }

        // Set User-Agent header of not already there
        if (!isset($headers['user-agent']) && isset($this->options['useragent'])) {
            $newHeaders['user-agent'] = $this->options['useragent'];
        }

        // Set HTTP authentication if needed
        if ($this->authData) {
            switch ($this->authData['type']) {
                case 'basic' :
                    // In basic authentication, the user name cannot contain ":"
                    if (strpos($this->authData['user'], ':') !== false) {
                        throw new \InvalidArgumentException("Http request failed: The user name cannot contain ':' in Basic HTTP authentication");
                    }
                    $newHeaders['authorization'] = 'Basic ' . base64_encode($this->authData['user'] . ':' . $this->authData['password']);
                    break;
                case 'digest' :
                    throw new \InvalidArgumentException("Http request failed: The digest authentication is not implemented yet");
            }
        }

        // Set Content-type header
        if ($this->encType) {
            $newHeaders['content-type'] = $this->encType;
        }

        // Set Content-Length if body is not empty
        // Todo check if that header is not requested by some servers even if the body is empty.
        if (!empty($body)) {
            $newHeaders['content-length'] = strlen($body);
        }

        // Merge the headers of the request (if any)
        foreach ($headers as $key => $value) {
            $newHeaders[$key] = $value;
        }

        return $newHeaders;
    }

    /**
     * Parse a Set-Cookie header
     *
     * Expects a string such as:
     *  Set-Cookie: mybb[lastvisit]=1350268035; expires=Tue, 15-Oct-2013 02:27:15 GMT; path=/; domain=.i-mscp.net
     *
     * Will return an array representation of the cookie as follow;
     * Array (
     *   'name' => 'mybb[lastvisit]'
     *   'value' => '1350268035'
     *   'expires => '1381804035'
     *   'path' => '/
     *   'domain => '.i-mscp.net'
     *   ...
     * )
     *
     * @param string $cookieString
     * @return array Array representation of a cookie
     * @throws \InvalidArgumentException
     */
    protected static function parseSetCookieHeader($cookieString)
    {
        if (!is_string($cookieString)) {
            throw new \InvalidArgumentException('Http request failed: Invalid header provided.');
        }

        $cookie = array();

        if (is_string($cookieString)) {
            // Assume it's a header string direct from a previous request
            $pairs = explode(';', $cookieString);

            // Special handling for first pair; name=value. Also be careful of "=" in value
            $name = trim(substr($pairs[0], 0, strpos($pairs[0], '=')));
            $value = substr($pairs[0], strpos($pairs[0], '=') + 1);
            $cookie['name'] = $name;
            $cookie['value'] = urldecode($value);
            array_shift($pairs); // Removes name=value from items.

            // Set everything else as a property
            foreach ($pairs as $pair) {
                $pair = rtrim($pair);

                if (empty($pair)) { // Handles the cookie ending in ; which results in a empty final pair
                    continue;
                }

                list($key, $value) = strpos($pair, '=') ? explode('=', $pair) : array($pair, '');

                $key = strtolower(trim($key));

                if ('expires' == $key && is_string($value)) {
                    $value = strtotime($value);
                }

                $cookie[$key] = $value;
            }
        }

        return $cookie;
    }

    /**
     * Prepare Cookie header
     *
     * Will return a string such as: cookie1=value; cookie2=value
     *
     * @throws \InvalidArgumentException
     * @param array $url URL components
     * @return string
     */
    protected function prepareCookieHeader($url)
    {
        $cookies =& $this->options['cookies'];

        if (null !== $cookies && !is_array($cookies)) {
            throw new \InvalidArgumentException('HTTP request failed: Cookies options must be an array');
        }

        $cookieString = '';

        if (!empty($cookies)) {
            foreach ($cookies as $name => &$cookie) {
                if (!is_array($cookie)) {
                    throw new \InvalidArgumentException('HTTP request failed: Any cookie definition should be provided as an array');
                } elseif (!isset($cookie['name'])) {
                    throw new \InvalidArgumentException('A cookie name is required to generate a field value for this cookie');
                }

                $name = (is_int($name)) ? $cookie['name'] : $name; // Ensure we have proper identifier for the cookie

                // If cookie is expired, unset it
                if (isset($cookies['expires']) && is_int($cookie['expires']) && $cookies['expires'] < time()) {
                    unset($cookies[$name]);
                    continue;
                }

                // Todo Encode value ?
                $cookieString .= $cookie['name'] . '=' . $cookie['value'] . '; ';
            }

            $cookieString = substr($cookieString, 0, -2);
        }

        return $cookieString;
    }

    /**
     * Prepare request body (for PATCH, POST and PUT requests)
     *
     * If a string is given, it's assumed that it's already a properly encoded request body and will be returned as this.
     *
     * @throws \RuntimeException
     * @param string|array|object $body Request body
     * @return string Request body as expected by servers
     */
    protected function prepareBody($body)
    {
        // No body for HTTP TRACE method
        if ($this->options['method'] == 'TRACE') {
            return '';
        }

        if (is_string($body) && $body != '') { // No empty string mean raw body
            return $body;
        }

        $headers =& $this->options['headers'];

        if (isset($headers['content-type'])) {
            $this->encType = $headers['content-type'];
        }

        // Handle POST parameters (Accept either an associative array or an object)
        if (!empty($body)) {
            if (!isset($this->encType)) {
                $this->encType = 'application/x-www-form-urlencoded';
            }

            if (stripos($this->encType, 'application/x-www-form-urlencoded') === 0) {
                $body = http_build_query($body);
            } else {
                throw new \RuntimeException(
                    printf("HTTP request failed: Cannot handle content type '%s' automatically", $this->encType)
                );
            }
        }

        return $body;
    }

    /**
     * Decode a chunked HTTP message body
     *
     * @throws \RuntimeException
     * @param string $body Chunked HTTP message body
     * @return string Decoded HTTP message body
     */
    protected static function decodeChunckedMessage($body)
    {
        $decodedMessage = '';

        while (trim($body)) {
            if (!preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $body, $matches)) {
                throw new \RuntimeException('HTTP request failed: Invalid chunked message');
            }

            $length = hexdec(trim($matches[1]));
            $cut = strlen($matches[0]);
            $decodedMessage .= substr($body, $cut, $length);
            $body = substr($body, $cut + $length + 2);
        }

        return $decodedMessage;
    }

    /**
     * Decode a gzip encoded HTTP message body
     *
     * @throws \RuntimeException
     * @param  string $body Gzip encoded HTTP message body
     * @return string Decoded HTTP message body
     */
    protected static function decodeGzipMessage($body)
    {
        if (!function_exists('gzinflate')) {
            throw new \RuntimeException('Http request failed: zlib extension is required in order to decode "gzip" encoding');
        }

        return gzinflate(substr($body, 10));
    }

    /**
     * Decode a zlib deflated HTTP message body
     *
     * @throws \RuntimeException
     * @param  string $body zlib deflated HTTP message body
     * @return string Decoded HTTP message body
     */
    protected static function decodeDeflateMessage($body)
    {
        if (!function_exists('gzuncompress')) {
            throw new \RuntimeException('Http request failed: zlib extension is required in order to decode "deflate" encoding');
        }

        /**
         * Some servers send a broken deflate response, without the RFC-required zlib header. We try to detect the zlib
         * header, and if it does not exsit we teat the body is plain DEFLATE content.
         */
        $zlibHeader = unpack('n', substr($body, 0, 2));

        if ($zlibHeader[1] % 31 == 0) {
            return gzuncompress($body);
        }

        return gzinflate($body);
    }

    /**
     * Reset all HTTP parameters
     *
     * @return Client
     */
    protected function resetParameters()
    {
        $this->authData = null;
        $this->encType = null;
        $this->options['method'] = 'GET';
        $this->options['headers'] = null;
        $this->options['cookies'] = null;
        $this->options['body'] = null;

        return $this;
    }
}
