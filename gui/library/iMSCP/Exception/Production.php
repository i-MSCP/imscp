<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * Class iMSCP_Exception_Production
 */
class iMSCP_Exception_Production extends iMSCP_Exception
{
    /**
     * Constructor
     *
     * Note: We cannot enforce type declaration of Exception since we want be compatible with both PHP5 and PHP 7.
     *       See http://php.net/manual/fr/migration70.incompatible.php
     *
     * @param string $message
     * @param int $code
     * @param Exception|Throwable $previous OPTIONAL Previous exception
     * @throws Zend_Exception
     */
    public function __construct($message = '', $code = 0, $previous = null)
    {
        if (function_exists('tr')) {
            $message = tr('An unexpected error occurred. Please contact your administrator.');
        } else {
            $message = 'An unexpected error occurred. Please contact your administrator.';
        }

        parent::__construct($message, $code, $previous);
    }
}
