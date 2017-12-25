<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
 */

/**
 * HTTP(S)/Ftp URI handler
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
            throw new Zend_Uri_Exception(sprintf('%s is not a string', $uri));
        }

        $uri = explode(':', $uri, 2);
        $scheme = strtolower($uri[0]);
        $schemeSpecific = isset($uri[1]) === true ? $uri[1] : '';

        if (in_array($scheme, ['http', 'https', 'ftp']) === false) {
            throw new iMSCP_Uri_Exception(sprintf('Invalid scheme: %s', $scheme));
        }

        $schemeHandler = new iMSCP_Uri_Redirect($scheme, $schemeSpecific);
        return $schemeHandler;
    }
}
