<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
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
 * @copyright   2010-2013 by -MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iMSCP\Http\Client\Adapter;

/**
 * iMSCP_Http_Client_Adapter_Socket class
 *
 * @category    iMSCP
 * @package     iMSCP_Http
 * @subpackage  Client
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class Socket extends AbstractAdapter
{
    /**
     * @var resource Socket representing server connection
     */
    protected $socket = null;

    /**
     * @var resource Stream context
     */
    protected $streamContext = null;

    /**
     * @var string Host to which we are currently connected
     */
    protected $host = null;

    /**
     * @var int Port to which we are currently connected
     */
    protected $port;

    /**
     * @var array Options
     */
    protected $options = array(
        'persistent' => false,
        'ssltransport' => 'sslv3',
        'sslcert' => null,
        'sslpassphrase' => null,
        'sslverifypeer' => true,
        'sslcapath' => '/etc/ssl/certs', // Should work on Debian/Ubuntu
        'sslallowselfsigned' => false,
        'sslusecontext' => false
    );

    /**
     * @var array Map SSL transport wrappers to stream crypto method constants
     */
    protected static $sslCryptoTypes = array(
        'ssl' => STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
        'sslv2' => STREAM_CRYPTO_METHOD_SSLv2_CLIENT,
        'sslv3' => STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
        'tls' => STREAM_CRYPTO_METHOD_TLS_CLIENT
    );

    /**
     * Do an HTTP request
     *
     * @throws \RuntimeException
     * @param array $url URL components
     * @param bool $secure Flag indicating whether it's a secure HTTP request
     * @param array $headers Request headers
     * @param string $body Request body
     * @return string Raw server response
     */
    public function doRequest($url, $secure = false, $headers = array(), $body = '')
    {
        $this->connect($url['host'], $url['port'], $secure);

        if (!$this->socket) {
            throw new \RuntimeException('Http request failed: Trying to send request not connected');
        }

        $host = (strtolower($url['scheme']) == 'https' ? $this->options['ssltransport'] : 'tcp') . '://' . $url['host'];
        if ($this->host != $host || $this->port != $url['port']) {
            throw new \RuntimeException('Http request failed: Trying to write but connected to the wrong host');
        }

        // Add request Line
        $path = $url['path'];
        if (null !== $url['query']) {
            $path .= '?' . $url['query'];
        }
        $request = "{$this->options['method']} {$path} HTTP/{$this->options['httpversion']}\r\n"; // HTTP request line

        // Add request headers
        foreach ($headers as $name => $value) { // Headers
            if (is_string($name)) {
                $value = ucfirst($name) . ": $value";
            }

            $request .= "$value\r\n";
        }

        // Add request body
        $request .= "\r\n" . $body;

        // Write request to the server
        if (!@fwrite($this->socket, $request)) {
            throw new \RuntimeException('Http request failed: Unable to write request to server');
        }

        return $this->readServerResponse();
    }

    /**
     * Close connection
     *
     * @return void
     */
    public function close()
    {
        @fclose($this->socket);
        $this->socket = null;
    }

    /**
     * Read server response
     *
     * @see iMSCP_Http_Client::parseRawResponse
     * @throws \RuntimeException
     * @return string Raw server response
     */
    protected function readServerResponse()
    {
        $response = '';

        // First we retrieve only the first response stanza containing Status-Line and headers
        $firstStanza = false;
        while (($line = fgets($this->socket)) !== false) {
            $firstStanza = $firstStanza || (strpos($line, 'HTTP') !== false);

            if ($firstStanza) {
                $response .= $line;
                if (rtrim($line) === '') { // Only read first stanza
                    break;
                }
            }
        }

        // Check if the socket has timed out - if so close connection and throw an exception
        $this->checkSocketReadTimeout();

        // Parse the raw response to retrieve both Status-line and headers
        $responseArr = $this->parseRawResponse($response);
        $statusCode = $responseArr['status_code'];

        // Handle 100 and 101 responses internally by restarting the read again (see rfc2616 section 10)
        if ($statusCode == 100 || $statusCode == 101) {
            return $this->readServerResponse();
        }

        // Responses to HEAD requests and 204 or 304 responses are not expected to have a body (see rfc2616 section 10)
        if ($statusCode == 304 || $statusCode == 204 || $this->options['method'] == 'HEAD') {
            // Close the connection if requested by the server
            $connection = (isset($responseArr['headers']['connection'])) ? $responseArr['headers']['connection'] : false;

            if ($connection && $connection == 'close') {
                $this->close();
            }

            return $response;
        }

        // If we got a 'transfer-encoding: chunked' header
        $transferEncoding = (isset($responseArr['headers']['transfer-encoding'])) ? $responseArr['headers']['transfer-encoding'] : false;
        $contentLength = isset($responseArr['headers']['content-length']) ? $responseArr['headers']['content-length'] : false;

        if ($transferEncoding) {
            if (strtolower($transferEncoding) == 'chunked') {
                do {
                    $line = fgets($this->socket);
                    $this->checkSocketReadTimeout();
                    $chunk = $line;
                    $chunksize = trim($line);

                    if (!ctype_xdigit($chunksize)) {
                        $this->close();
                        throw new \RuntimeException(
                            sprintf("Http request failed: Invalid chunk size '%s' unable to read chunked body", $chunksize)
                        );
                    }

                    $chunksize = hexdec($chunksize);
                    $readTo = ftell($this->socket) + $chunksize;

                    do {
                        $currentPosition = ftell($this->socket);

                        if ($currentPosition >= $readTo) {
                            break;
                        }

                        $line = fread($this->socket, $readTo - $currentPosition);

                        if ($line === false || strlen($line) === 0) {
                            $this->checkSocketReadTimeout();
                            break;
                        }

                        $chunk .= $line;
                    } while (!feof($this->socket));

                    $chunk .= @fgets($this->socket);
                    $this->checkSocketReadTimeout();
                    $response .= $chunk;
                } while ($chunksize > 0);
            } else {
                $this->close();
                throw new \RuntimeException(sprintf("Cannot handle '%s' transfer encoding", $transferEncoding));
            }
        } elseif ($contentLength !== false) {
            if (is_array($contentLength)) {
                $contentLength = $contentLength[count($contentLength) - 1];
            }

            $currentPosition = ftell($this->socket);

            for ($readTo = $currentPosition + $contentLength; $readTo > $currentPosition; $currentPosition = ftell($this->socket)) {
                $chunk = fread($this->socket, $readTo - $currentPosition);
                if ($chunk === false || strlen($chunk) === 0) {
                    $this->checkSocketReadTimeout();
                    break;
                }

                $response .= $chunk;

                // Break if the connection ended prematurely
                if (feof($this->socket)) {
                    break;
                }
            }
        } else { // Fallback: just read the response until EOF
            do {
                $buff = fread($this->socket, 8192);
                if ($buff === false || strlen($buff) === 0) {
                    $this->checkSocketReadTimeout();
                    break;
                } else {
                    $response .= $buff;
                }
            } while (feof($this->socket) === false);

            $this->close();
        }

        // Close the connection if requested by the server
        $connection = (isset($responseArr['headers']['connection'])) ? $responseArr['headers']['connection'] : false;
        if ($connection && $connection == 'close') {
            $this->close();
        }

        return $response;
    }

    /**
     * Check if the socket has timed out - if so close connection and throw an exception
     *
     * @throws \RuntimeException with READ_TIMEOUT code
     * @return void
     */
    protected function checkSocketReadTimeout()
    {
        if ($this->socket) {
            $metadata = stream_get_meta_data($this->socket);

            if ($metadata['timed_out']) {
                $this->close();
                throw new \RuntimeException(
                    sprintf('Http request failed: Read timed out after %s seconds', $this->options['timeout'])
                );
            }
        }
    }

    /**
     * Connect to the remote server
     *
     * @throws \RuntimeException
     * @param string $host Host
     * @param int $port Port
     * @param bool $secure Flag indicating whether it's secure request
     * @return void
     */
    protected function connect($host, $port = 80, $secure = false)
    {
        // If we are connected to the wrong host, disconnect first
        if (($this->host != $host || $this->port != $port) && is_resource($this->socket)) {
            $this->close();
        }

        // Now, if we are not connected or keepalive option is false, connect
        if (!is_resource($this->socket) || !$this->options['keepalive']) {
            $context = stream_context_create();

            if ($secure || $this->options['sslusecontext']) {
                if ($this->options['sslverifypeer'] !== null) {
                    if (!stream_context_set_option($context, 'ssl', 'verify_peer', $this->options['sslverifypeer'])) {
                        throw new \RuntimeException('Http request failed: Unable to set sslverifypeer option');
                    }
                }

                if ($this->options['sslcapath']) {
                    if (!stream_context_set_option($context, 'ssl', 'capath', $this->options['sslcapath'])) {
                        throw new \RuntimeException('Http request failed: Unable to set sslcapath option');
                    }
                }

                if ($this->options['sslallowselfsigned'] !== null) {
                    if (!stream_context_set_option($context, 'ssl', 'allow_self_signed', $this->options['sslallowselfsigned'])) {
                        throw new \RuntimeException('Http request failed: Unable to set sslallowselfsigned option');
                    }
                }

                if ($this->options['sslcert'] !== null) {
                    if (!stream_context_set_option($context, 'ssl', 'local_cert', $this->options['sslcert'])) {
                        throw new \RuntimeException('Http request failed: Unable to set sslcert option');
                    }
                }

                if ($this->options['sslpassphrase'] !== null) {
                    if (!stream_context_set_option($context, 'ssl', 'passphrase', $this->options['sslpassphrase'])) {
                        throw new \RuntimeException('Http request failed: Unable to set sslpassphrase option');
                    }
                }
            }

            $flags = STREAM_CLIENT_CONNECT;
            if ($this->options['persistent']) {
                $flags |= STREAM_CLIENT_PERSISTENT;
            }

            $this->socket = @stream_socket_client(
                $host . ':' . $port, $errno, $errstr, (int)$this->options['timeout'], $flags, $context
            );

            if (!$this->socket) {
                $this->close();
                throw new \RuntimeException(
                    sprintf('Http request failed: Unable to connect to %s:%d (%s)', $host, $port, $errstr), $errno
                );
            }

            // Set stream timeout
            if (!stream_set_timeout($this->socket, (int)$this->options['timeout'])) {
                throw new \RuntimeException('Http request failed: Unable to set the connection timeout');
            }

            if ($secure || $this->options['sslusecontext']) {
                if ($this->options['ssltransport'] && isset(self::$sslCryptoTypes[$this->options['ssltransport']])) {
                    $sslCryptoMethod = self::$sslCryptoTypes[$this->options['ssltransport']];
                } else {
                    $sslCryptoMethod = STREAM_CRYPTO_METHOD_SSLv3_CLIENT;
                }

                if (!($ret = @stream_socket_enable_crypto($this->socket, true, $sslCryptoMethod))) {
                    $errorString = '';
                    while (($sslError = openssl_error_string()) != false) {
                        $errorString .= "; SSL error: $sslError";
                    }

                    $this->close();

                    if ((!$errorString) && $this->options['sslverifypeer']) {
                        if (!($this->options['sslcapath'] && is_dir($this->options['sslcapath']))) {
                            $errorString = 'make sure the "sslcapath" option points to a valid SSL certificate directory';
                        }
                    }

                    throw new \RuntimeException(
                        sprintf('Http request failed:: Unable to enable crypto on TCP connection %s (%s)', $host, $errorString)
                    );
                }

                $host = $this->options['ssltransport'] . '://' . $host;
            } else {
                $host = 'tcp://' . $host;
            }

            $this->host = $host;
            $this->port = $port;
        }
    }
}
