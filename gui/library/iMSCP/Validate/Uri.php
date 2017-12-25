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
 * Class iMSCP_Validate_Uri
 */
class iMSCP_Validate_Uri extends Zend_Validate_Abstract
{
    const INVALID_URI = 'invalidURI';

    protected $_messageTemplates = [
        self::INVALID_URI => "'%value%' is not a valid URI.",
    ];

    /**
     * Returns true if the $uri is valid
     *
     * If $uri is not a valid URI, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @throws Zend_Validate_Exception If validation of $value is impossible
     * @param  string $uri URI to be validated
     * @return boolean
     */
    public function isValid($uri)
    {
        $uri = (string)$uri;
        $this->_setValue($uri);

        try {
            Zend_Uri::factory($uri, 'iMSCP_Uri_Redirect');
        } catch (Exception $e) {
            $this->_error(self::INVALID_URI);
            return false;
        }

        return true;
    }
}
