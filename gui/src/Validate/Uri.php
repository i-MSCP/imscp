<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

declare(strict_types=1);

namespace iMSCP\Validate;

use iMSCP\Uri\UriRedirect;
use Zend_Exception;
use Zend_Uri;
use Zend_Validate_Abstract;

/**
 * Class Uri
 * @package iMSCP\Validate
 */
class Uri extends Zend_Validate_Abstract
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
     * @param string $uri URI to be validated
     * @return boolean
     */
    public function isValid($uri)
    {
        $uri = (string)$uri;
        $this->_setValue($uri);

        try {
            // We need pre-load the class as the Zend_Loader cannot load
            // PSR-4 classname...
            class_exists(UriRedirect::class, true);
            Zend_Uri::factory($uri, UriRedirect::class);
        } catch (Zend_Exception $e) {
            $this->_error(self::INVALID_URI);
            return false;
        }

        return true;
    }
}
