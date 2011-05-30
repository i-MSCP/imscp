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
 * @copyright   2011 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-mscp Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Provide generic interface for URI (Uniform Resource Identifier) according rfc 3986
 *
 * @category    iMSCP
 * @package     iMSCP_URI
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
interface iMSCP_Uri_Interface
{

    # Getter that access the basic components of the URI:

    /**
     * Returns the scheme component.
     *
     * @abstract
     * @return string
     */
    public function getScheme();

    /**
     * Returns the authority component.
     *
     * @abstract
     * @return string
     */
    public function getAuthority();

    /**
     * Returns the path component.
     *
     * @abstract
     * @return string
     */
    public function getPath();

    /**
     * Returns the URI query component.
     *
     * @abstract
     * @return string
     */
    public function getQuery();

    /**
     * Returns the URI fragment identifier component.
     *
     * @abstract
     * @return string
     */
    public function getFragment();

    # Additional attributes that provide access to parsed-out portions
    # of the authority:

    /**
     * Returns username from userinfo subcomponent.
     *
     * @abstract
     * @return string|null
     */
    public function getUsername();

    /**
     * Returns password from userinfo subcomponent.
     *
     * @abstract
     * @return string|null
     */
    public function getPassword();

    /**
     * Returns the hostname from authority component.
     *
     * Returns host from
     * @abstract
     * @return string
     */
    public function getHostname();

    /**
     * Returns the port from authority component.
     *
     * @abstract
     * @return int
     */
    public function getPort();

    /**
     * Returns a string representation of an URI.
     *
     * @abstract
     * @return string
     */
    public function getUri();
}
