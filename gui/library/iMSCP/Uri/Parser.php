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
 * @package     iMSCP_URI
 * @subpackage  parser
 * @copyright   2010 - 2011 by the i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-mscp Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/** @see iMSCP_Uri_Parser_ParseResult*/
require_once 'iMSCP/Uri/Parser/ParseResult.php';

/** @see  iMSCP_Uri_Parser_SplitResult*/
require_once 'iMSCP/Uri/Parser/SplitResult.php';

/**
 * iMSCP Uri Parser class
 *
 * This class defines a standart interface to break Uniform Resource Identifier
 * string up in components (addressing scheme, authority, path etc...), to combine
 * the components back into an URI string, and to convert a relative URI to
 * an absolute URI given a base URI.
 *
 * This class has been designed to match the Internet RFC on Relative Uniform
 * Resource Identifier. It's support the following URI schemes:
 *
 * file, ftp, gopher, hdl, http, https, imap, mailto, mms, news, nntp, prospero,
 * rsync, rtsp, rtspu, sftp, shttp, sip, sips, snews, svn, svn+ssh, telnet, wais...
 *
 * This class is based upon the following RFC specifications:
 *
 * RFC 3986 (STD66): Uniform Resource Identifiers
 * RFC 2732: Format for Literal IPv6 Addresses in URL's
 * RFC 2396: Uniform Resource Identifiers (URI)
 * RFC 2368: The mailto URL scheme
 * RFC 1808: Relative Uniform Resource Locators
 * RFC 1738: Uniform Resource Locators (URL)
 *
 * RFC 3986 is considered the current standard and any future changes to
 * iMSCP_Uri_Parser class should conform with it. The iMSCP_Uri_Parser class is
 * currently notentirely compliant with this RFC due to defacto scenarios for parsing,
 * and for backward compatibility purposes, some parsing quirks from older RFCs 
 * are retained.
 *
 * @category    iMSCP
 * @package     iMSCP_URI
 * @subpackage  Parser
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @todo        Review documentation
 * @todo        Unit tests
 */
class iMSCP_Uri_Parser
{
    /**
     * Instance of this class
     *
     * @var iMSCP_Uri_Parser
     */
    private static $_instance;

    /**
     * List of schemes that use relative.
     *
     * @var array
     */
    private $_usesRelative = array(
        'ftp', 'http', 'gopher', 'nntp', 'imap', 'wais', 'file', 'https', 'shttp',
        'mms', 'prospero', 'rtsp', 'rtspu', '', 'sftp'
    );

    /**
     * List of schemes that use authority.
     *
     * @var array
     */
    private $_usesAuthority = array(
        'ftp', 'http', 'gopher', 'nntp', 'telnet', 'imap', 'wais', 'file', 'mms',
        'https', 'shttp', 'snews', 'prospero', 'rtsp', 'rtspu', 'rsync', '', 'svn',
        'svn+ssh', 'sftp', 'nfs', 'git', 'git+ssh'
    );

    /**
     * List of schemes that are non hierarchical (opaque).
     *
     * @var array
     */
    private $_nonHierarchical = array(
        'gopher', 'hdl', 'mailto', 'news', 'telnet', 'wais', 'imap', 'snews', 'sip',
        'sips'
    );

    /**
     * List of schemes that use parameters.
     *
     * @var array
     */
    private $_usesParams = array(
        'ftp', 'hdl', 'prospero', 'http', 'imap', 'https', 'shttp', 'rtsp', 'rtspu',
        'sip', 'sips', 'mms', '', 'sftp'
    );

    /**
     * List of schemes that use query.
     *
     * @var array
     */
    private $_usesQuery = array(
        'http', 'wais', 'imap', 'https', 'shttp', 'mms', 'gopher', 'rtsp', 'rtspu',
        'sip', 'sips', ''
    );

    private $_usesFragment = array(
        'ftp', 'hdl', 'http', 'gopher', 'news', 'nntp', 'wais', 'https', 'shttp',
        'snews', 'file', 'prospero', ''
    );

    /**
     * Characters list allowed into scheme.
     *
     * @var array
     */
    private $_schemeCharacters =
    'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+-.';

    /**
     * Singleton pattern implementation makes "new" unavailable.
     */
    private function __construct()
    {
    }

    /**
     * Singleton pattern implementation makes "clone" unavailable.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Returns an instance of this class.
     * 
     * @static
     * @return iMSCP_Uri_Parser
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Parse an URI into 6 components.
     *
     * This methods split an URI into 6 components:
     *
     * <scheme>://<authority>/<path>;<param>?<query>#<fragment>
     *
     * This corresponds to the general structure of an URL like described in RFC
     * 1808: scheme://authority/path;parameters?query#fragment.
     *
     * Modifications from RFC 1808 by RFC 2396:
     *     The BNF term <net_loc> has been replaced with <authority>
     *
     * Each array item is a string, possibly empty. The components are not broken up
     * in smaller parts (for example, the authority is a single string), and %
     * escapes are not expanded. The delimiters as shown above are not part of the
     * result, except for a leading slash in the path component, which is retained
     * if present.
     *
     * Following the syntax specifications in RFC 1808, this method recognizes an
     * authority only if it is properly introduced by '//'. Otherwise the input is
     * presumed to be a relative URL and thus to start with a path component.
     *
     * If the $scheme argument is specified, it gives the default addressing scheme,
     * to be used only if the URI does not specify one. The default value for this
     * argument is the empty string.
     *
     * If the $allowFragments argument is false, fragment identifiers are not allowed,
     * even if the URI’s addressing scheme normally does support them. The default
     * value for this argument is true.
     *
     * The return value is actually an instance of iMSCP_Uri_Parser_ParseResult, An
     * ArrayObject that has the following additional read-only convenience
     * attributes:
     *
     * scheme: URI scheme specifier - defaulted to empty string
     * authority: Network location part - defaulted to empty string
     * path: Hierarchical path - defaulted to empty string
     * params: Parameters for last path element - defaulted to empty string
     * query: Query component - defaulted to empty string
     * fragment: Fragment identifier - defaulted to empty string
     * username: User name - defaulted to null
     * password: Password - defaulted to null
     * hostname: Host name (lower case) defaulted to null
     * port: Port number as integer, if present - defaulted to null
     *
     * @param  string $uri URI reference
     * @param string $scheme URI scheme
     * @param bool $allowFragments
     * @return iMSCP_Uri_Parser_ParseResult
     */
    public function parseUri($uri, $scheme = '', $allowFragments = true)
    {
        list($s, $a, $u, $q, $f) = $this->splitUri($uri, $scheme, $allowFragments);

        if (in_array($s, $this->_usesParams) && strpos($u, ';') !== false) {
            list($u, $p) = $this->_splitParams($u);
        } else {
            $p = '';
        }

        return new iMSCP_Uri_Parser_ParseResult($s, $a, $u, $p, $q, $f);
    }

    /**
     * Split params from an URI.
     *
     * @param string $uri
     * @return array
     */
    protected function _splitParams($uri)
    {
        if (strpos($uri, '/') !== false) {
            $i = strpos($uri, ';', strrpos($uri, '/'));

            if ($i < 0) {
                return array($uri, '');
            }
        } else {
            $i = strpos($uri, ';');
        }

        return array(substr($uri, 0, $i), substr($uri, $i + 1));
    }

    /**
     * Split authority component from an URI.
     *
     * @param string $uri
     * @param int $start OPTIONAL Position from which start the split
     * @return array
     */
    protected function _splitAuthority($uri, $start = 0)
    {
        $delim = strlen($uri);

        foreach (array('/', '?', '#') as $c) {
            if (($wdelim = strpos($uri, $c, $start)) !== false) {
                $delim = ($delim > $wdelim) ? $wdelim : $delim;
            }
        }

        return array(substr($uri, $start, $delim - $start), substr($uri, $delim));
    }

    /**
     * Split an URI into 5 components.
     *
     * This methods split an URI into 5 components:
     *
     * <scheme>://<authority>/<path>?<query>#<fragment>
     *
     * This corresponds to the general structure of an URI like described i RFC 3986.
     *
     * @throws iMSCP_Uri_Exception
     * @param string $uri URI reference
     * @param string $scheme OPTIONAL scheme
     * @param bool $allowFragments
     * @return array Tells whether or not fragments are allowed
     */
    public function splitUri($uri, $scheme = '', $allowFragments = true)
    {
        $allowFragments = (bool)$allowFragments;
        $authority = $query = $fragment = '';
        $i = strpos($uri, ':');

        if ($i > 0) {
            // Optimize common cases (http, https)
            if (substr($uri, 0, $i) == 'http' || substr($uri, 0, $i) == 'https') {
                $scheme = strtolower(substr($uri, 0, $i));
                $uri = substr($uri, $i + 1);

                if (substr($uri, 0, 2) == '//') {
                    list($authority, $uri) = $this->_splitAuthority($uri, 2);

                    if (strpos($authority, '[' !== false) &&
                        strpos($authority, ']') === false
                    ) {
                        require_once 'iMSCP/Uri/Parser/Exception.php';
                        throw new iMSCP_Uri_Parser_Exception(
                            'Invalid literal IPv6 address detected in URL.');
                    }
                }
                if ($allowFragments && strpos($uri, '#') !== false) {
                    list($uri, $fragment) = explode('#', $uri, 2);
                }
                if (strpos($uri, '?') !== false) {
                    list($uri, $query) = explode('?', $uri, 2);
                }

                return new iMSCP_Uri_Parser_SplitResult(
                    $scheme, $authority, $uri, $query, $fragment);
            }

            if (substr($uri, -1) == ':' || false == ctype_digit(substr($uri, $i + 1))) {
                $c = substr($uri, 0, $i);
                $count = strlen($c);
                $ret = true;

                for ($i = 0; $i < $count; $i++) {
                    if (false === strpos($this->_schemeCharacters, $c[$i])) {
                        $ret = false;
                        break;
                    }
                }

                if ($ret) {
                    list($scheme, $uri) = array(strtolower(substr($uri, 0, $i)),
                                                substr($uri, $i + 1));
                }
            }
        }

        if (substr($uri, 0, 2) == '//') {
            list($authority, $uri) = $this->_splitAuthority($uri, 2);

            if (strpos($authority, '[' !== false)
                && strpos($authority, ']') === false
            ) {
                require_once 'iMSCP/Uri/Parser/Exception.php';
                throw new iMSCP_Uri_Parser_Exception(
                    'Invalid literal IPv6 address detected in URL.');
            }
        }

        if ($allowFragments && in_array($scheme, $this->_usesFragment) &&
            strpos($uri, '#') !== false
        ) {
            list($uri, $fragment) = explode('#', $uri, 2);
        }

        if (in_array($scheme, $this->_usesQuery) && strpos($uri, '?') !== false) {
            list($uri, $query) = explode('?', $uri, 2);
        }

        return new iMSCP_Uri_Parser_SplitResult(
            $scheme, $authority, $uri, $query, $fragment);
    }

    /**
     * Put a parsed URI back together again.
     *
     * This may result in a slightly different, but equivalent URI, if the URI that
     * was parsed originally had redundant delimiters, e.g. a ? with an empty query
     * (the draft states that these are equivalent).
     *
     * @param array|iMSCP_Uri_Parser_ParseResult $data An array like returned
     *                                                 by {@link self::parseUri()}
     * @return string A string that represent an URI
     */
    public function unparseUri($data)
    {
        list($scheme, $authority, $uri, $params, $query, $fragment) = $data;

        if ($params) {
            $uri = $uri . ';' . $params;
        }

        return $this->unsplitUri(array($scheme, $authority, $uri, $query,
                                      $fragment));
    }

    /**
     * Unsplit an URI.
     *
     * Combine the elements of an array as returned by {@link self::splitUri()} into
     * a complete URI as a string. The data argument can be any five-item iterable.
     * This may result in a slightly different, but equivalent URI, if the URI that
     * was parsed originally had unnecessary delimiters (for example, a ? with an
     * empty query; the RFC states that these are equivalent).
     *
     * @param array|iMSCP_Uri_Parser_SplitResut $data An array like returned
     *                                                by {@link self::splitUri()}
     * @return string A string that represent an URI
     */
    public function unsplitUri($data)
    {
        list($scheme, $authority, $uri, $query, $fragment) = $data;

        if ($authority || ($scheme && in_array($scheme, $this->_usesAuthority) &&
                           substr($uri, 0, 2) != '//')
        ) {
            $uri = '//' . $authority . $uri;
        }
        if ($scheme) {
            $uri = $scheme . ':' . $uri;
        }
        if ($query) {
            $uri = $uri . '?' . $query;
        }
        if ($fragment) {
            $uri = $uri . '#' . $fragment;
        }

        return $uri;
    }

    /**
     * Join a base URL and a possibly relative URL to form an absolute interpretation
     * of the latter.
     *
     * Note: Not Yet Fully Implemented.
     *
     * @param string $base
     * @param string $url
     * @param bool $allowFragments
     * @return string An absolute URI
     */
    public function joinUri($base, $url, $allowFragments = true)
    {
        if (empty($base)) {
            return $url;
        } elseif (empty($url)) {
            return $base;
        }

        list(
            $bscheme, $bauthority, $bpath, $bparams, $bquery, $bfragment
            ) = $this->parseUri($base, '', $allowFragments);

        list($scheme, $authority, $path, $params, $query, $fragment) = $this->parseUri(
            $url, $bscheme, $allowFragments
        );

        if ($scheme != $bscheme || !in_array($scheme, $this->_usesRelative)) {
            return $url;
        }

        if (in_array($scheme, $this->_usesAuthority)) {
            if ($authority) {
                return $this->unparseUri(
                    $scheme, $authority, $path, $params, $query, $fragment);
            }
            $authority = $bauthority;
        }

        if (substr($path, 0, 1) == '/') {
            return $this->unparseUri(
                $scheme, $authority, $path, $params, $query, $fragment);
        }

        if (empty($path) && empty($params)) {
            $path = $bpath;
            $params = $bparams;
            if (empty($query)) {
                $query = $bquery;
            }

            return $this->unparseUri(
                $scheme, $authority, $path, $params, $query, $fragment);
        }

        return '';
    }

    /**
     * Removes any existing fragment from an URI.
     *
     * If $uri contains a fragment identifier, returns a modified version of $uri
     * with no fragment identifier, and the fragment identifier as a separate
     * string. If there is no fragment identifier in $uri, returns $uri unmodified
     * and an empty string.
     *
     * @param string $uri Uri to defragment.
     * @return array
     */
    public function defragUri($uri)
    {
        if (strpos($uri, '#') !== false) {
            list($s, $n, $p, $a, $q, $frag) = $this->parseUri($uri);
            $defrag = $this->unparseUri($s, $n, $p, $a, $q, '');

            return array($defrag, $frag);
        }

        return array($uri, '');
    }

    /**
     * @param  $string
     * @return void
     */
    public function unquotes($string)
    {
    }
}
