<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2011-2011 by i-MSCP Team
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
 * @package     iMSCP_Core
 * @subpackage  Uri
 * @copyright   2010-2014 by by i-MSCP team
 * @author      Laurent Declercq <l.declercq@i-mscp.net>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license	    http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/** @see Zend_Uri_Http */
require_once 'Zend/Uri/Http.php';

/**
 * Redirect URI handler (Like supported in i-MSCP engine)
 *
 * @category    iMSCP
 * @package	    iMSCP_Core
 * @subpackage  Uri
 * @author      Laurent Declercq <l.declercq@i-mscp.net>
 */
class iMSCP_Uri_Redirect extends Zend_Uri_Http
{
	/**
	 * Creates a iMSCP_Uri_Redirect from the given string
	 *
	 * @param  string $uri String to create URI from, must start with prefix http://, https:// or 'ftp://
	 * @throws iMSCP_Uri_Exception When the given URI is not a string or is not valid
	 * @throws Zend_Uri_Exception
	 * @return iMSCP_Uri_Redirect
	 */
	public static function fromString($uri)
	{
		if (is_string($uri) === false) {
			require_once 'iMSCP/Uri/Exception.php';
			throw new Zend_Uri_Exception('$uri is not a string');
		}

		$uri = explode(':', $uri, 2);
		$scheme = strtolower($uri[0]);
		$schemeSpecific = isset($uri[1]) === true ? $uri[1] : '';

		if (in_array($scheme, array('http', 'https', 'ftp')) === false) {
			require_once 'iMSCP/Uri/Exception.php';
			throw new iMSCP_Uri_Exception("Invalid scheme: '$scheme'");
		}

		$schemeHandler = new iMSCP_Uri_Redirect($scheme, $schemeSpecific);
		return $schemeHandler;
	}

	/**
	 * Returns true if and only if the host string passes validation. If no host is passed, then the host contained in
	 * the instance variable is used.
	 *
	 * @param  string $host The HTTP host
	 * @return boolean
	 * @uses   Zend_Filter
	 */
	public function validateHost($host = null)
	{
		if ($host === null) {
			$host = $this->_host;
		}

		// If the host is empty, then it is considered invalid
		if (strlen($host) === 0) {
			return false;
		}

		return isValidDomainName($host);
	}
}
