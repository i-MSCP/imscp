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
 * @copyright   2011 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-mscp Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/** @see iMSCP_Uri_Interface */
require_once 'iMSCP/Uri/Interface.php';

/**
 * Abstract class for parsed result objects.
 *
 * This provides the attributes shared by the derived result objects as read-only
 * properties. The derived classes are responsible for checking the right number
 * of arguments were supplied to the constructor.
 *
 * @category    iMSCP
 * @package     iMSCP_URI
 * @subpackage  Parser
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
abstract class iMSCP_Uri_Parser_ResultMixin extends ArrayObject
    implements iMSCP_Uri_Interface
{

    /**
     * Implements PHP magic getter to allow to retrieve frozen properties.
     *
     * @param string $property
     * @return string
     */
    public function __get($property)
    {
        $method = 'get' . strtolower($property);

        if (!method_exists($this, $method)) {
            // Mimic default behavior on undefined property
            trigger_error('Undefined property: ' . __CLASS__ . '::' . $property,
                          E_USER_NOTICE);
        }

        return $this->$method();
    }

    /**
     * Implements PHP magic setter to disallow any further modification (frozen
     * object)..
     *
     * @throw iMSCP_Uri_Result_Exception Since result objects are defaulted frozen..
     * @param string $name
     * @param string $value
     * @return void
     */
    public function __set($name, $value)
    {
        require_once 'iMSCP/Uri/Parser/Exception.php';
        throw new iMSCP_Uri_Parser_Exception(
            'You cannot set or change properties on a frozen object.');
    }

    /**
     * Returns URI scheme component.
     *
     * @return string Uri scheme component
     */
    public function getScheme()
    {
        return $this[0];
    }

    /**
     * Returns URI authority component.
     *
     * @return string Uri authority component
     */
    public function getAuthority()
    {
        return $this[1];
    }

    /**
     * Returns URI path component.
     *
     * @return string Uri path component
     */
    public function getPath()
    {
        return $this[2];
    }

    /**
     * Returns URI query component.
     *
     * @return string Uri query component
     */
    public function getQuery()
    {
        end($this);
        return prev($this);
    }

    /**
     * Returns URI fragment component.
     *
     * @return string Uri fragment component
     */
    public function getFragment()
    {
        return end($this);
    }

    /**
     * Returns user name from userinfo subcomponent.
     *
     * @return string|null User name if set, null otherwise
     */
    protected function getUsername()
    {
        $authority = $this['authority'];

        if (strpos($authority, '@') !== false) {
            $userinfo = explode('@', $authority, 2);

            if (strpos($userinfo[0], ':') !== false) {
                $userinfo = explode(':', $userinfo[0], 2);
            }

            return $userinfo[0];
        }

        return null;
    }

    /**
     * Returns password from userinfo subcomponent.
     *
     * @return string|null Password if set, null otherwise
     */
    protected function getPassword()
    {
        $authority = $this['authority'];

        if (strpos($authority, '@') !== false) {
            $userinfo = explode('@', $authority, 2);
            if (strpos($userinfo[0], ':') !== false) {
                $userinfo = explode(':', $userinfo[0], 2);

                return $userinfo[1];
            }
        }

        return null;
    }

    /**
     * Returns hostname from authority component.
     *
     * @return string|null Hostname if set, null otherwise
     */
    protected function getHostname()
    {
        $authority = explode('@', $this['authority'], 2);

        if (strpos($authority[0], '[') !== false
            && strpos($authority[0], ']') !== false
        ) {
            // Todo
        } elseif (strpos($authority[0], ':') !== false) {
            // Todo
        } elseif ($authority[0] == '') {
            return null;
        }

        return strtolower($authority[0]);

    }

    /**
     * Returns port from authority.
     *
     * @return int|null
     */
    protected function getPort()
    {
        // Todo
    }

    /**
     * Returns string representation of this object (An URI string)
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUri();
    }
}
