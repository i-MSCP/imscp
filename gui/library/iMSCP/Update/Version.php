<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2012 by i-MSCP team
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
 * @package     iMSCP_Update
 * @subpackage  Version
 * @copyright   2010-2012 by i-MSCP team
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/** @see iMSCP_Update */
require_once 'iMSCP/Update.php';

/**
 * Update version class.
 *
 * @category    iMSCP
 * @package     iMSCP_Update
 * @subpackage  Version
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Update_Version extends iMSCP_Update
{
    /**
     * @var iMSCP_Update
     */
    protected static $_instance;

    /**
     * Singleton - Make new unavailable.
     */
    protected function __construct()
    {

    }

    /**
     * Singleton - Make clone unavailable.
     *
     * @return void
     */
    protected function __clone()
    {

    }

    /**
     * Implements Singleton design pattern.
     *
     * @return iMSCP_Update
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Return next update.
     *
     * @return int 0 if not update or server not reachable.
     */
    protected function _getNextUpdate()
    {
        ini_set('user_agent', 'Mozilla/5.0');

        $timeout = ini_set('default_socket_timeout', 3);
        $fh = @fopen('https://raw.github.com/i-MSCP/imscp/master/latest.txt', 'r');

        // Restore previous timeout
        ini_set('default_socket_timeout', $timeout);

        if (!is_resource($fh)) {
            $this->_lastError = tr("Couldn't check for updates. Website not reachable.");

            return 0;
        }

        $nextUpdate = (int)fread($fh, 8);
        fclose($fh);

        return $nextUpdate;
    }

    /**
     * Check for available update.
     *
     * @return bool TRUE if an update is available, FALSE otherwise.
     */
    public function isAvailableUpdate()
    {
        if ($this->_getLastAppliedUpdate() < $this->_getNextUpdate()) {
            return true;
        }

        return false;
    }

    /**
     * Returns last applied update.
     *
     * @throws iMSCP_Update_Exception When unable to retrieve last applied update
     * @return int
     */
    protected function _getLastAppliedUpdate()
    {
        /** @var $cfg iMSCP_Config_Handler_File */
        $cfg = iMSCP_Registry::get('config');

        if (isset($cfg->BuildDate)) {
            return (int)$cfg->BuildDate;
        } else {
            require_once 'iMSCP/Update/Exception.php';
            throw new iMSCP_Update_Exception('Unable to retrieve last applied update.');
        }
    }

    /**
     * Apply all available update.
     *
     * @throws iMSCP_Update_Exception Since this method is not implemented
     * @return void
     */
    public function applyUpdates()
    {
        require_once 'iMSCP/Update/Exception.php';
        throw new iMSCP_Update_Exception('Method not implemented.');
    }
}
