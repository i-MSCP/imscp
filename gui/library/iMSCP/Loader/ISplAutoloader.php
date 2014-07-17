<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 * @package     iMSCP_Loader
 * @copyright   2010-2014 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iMSCP\Loader;

/**
 * ISplAutoloader interface
 *
 * @package     iMSCP_Loader
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
interface ISplAutoloader
{
    /**
     * Set autoloader options
     *
     * @param array $options Autoloader options
     * @return ISplAutoloader
     */
    public function setOptions(array $options);

    /**
     * Register the autoloader onto the spl_autoload registry
     *
     * @return bool true on success or false on failure
     */
    public function register();

    /**
     * Unregister the autoloader from the spl_autoload registry
     *
     * @return bool true on success or false on failure
     */
    public function unregister();

    /**
     * Try to autoload the given class
     *
     * @param string $class Class name
     * @return bool true on success, false otherwise
     */
    public function autoload($class);
}
