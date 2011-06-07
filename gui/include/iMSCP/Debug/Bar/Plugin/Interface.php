<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP Team.
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
 * @package     iMSCP
 * @package     iMSCP_Debug
 * @subpackage  Bar_Plugin
 * @copyright   2010-2011 by i-MSCP team
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Interface for i-MSCP Debug Bar component's plugins.
 *
 * @package     iMSCP
 * @package     iMSCP_Debug
 * @subpackage  Bar_Plugin
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     0.0.1
 */
interface iMSCP_Debug_Bar_Plugin_Interface
{
    /**
     * Returns list of events that the plugin listens on.
     *
     * @abstract
     * @return array
     */
    public function getListenedEvents();
}
