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
 * Class iMSCP_Exception_Event
 */
class iMSCP_Exception_Event extends iMSCP_Events_Event
{
    /**
     * Constructor
     *
     * Note: We cannot enforce type declaraction of Exception since we want be compatible with both PHP5 and PHP 7.
     *       See http://php.net/manual/fr/migration70.incompatible.php
     *
     * @param Exception|Throwable $exception
     */
    public function __construct($exception)
    {
        parent::__construct('onUncaughtException', ['exception' => $exception]);
    }

    /**
     * Get Exception
     *
     * @return Exception
     */
    public function getException()
    {
        return $this->getParam('exception');
    }
}
