<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
 * @category    i-MSCP
 * @copyright   2010 by i-MSCP | http://i-mscp.net
 * @author      i-MSCP Team
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Class that allow to manage plug-ing configuration
 */
class iMSCP_Core_Plugin_Config {

	/**
	 * Get plugin information from a plugin about.xml file
	 * 
	 * @static
	 * @param  string $pluginName The plugin name
	 * @return array An array that contain plugin information
	 */
	public static function getInfo($pluginName) {}

	/**
	 * Get a plugin parameter
	 *
	 * @static
	 * @param  string $pluginName The plugin name
	 * @return void
	 */
	public static function getParam($pluginName) {}

	/**
	 * Store a plugin parameter
	 *
	 * @static
	 * @param  string $plugin The plugin name
	 * @param  mixed $params
	 * @return void
	 */
	public static function storeParam($plugin, $params) {}

	/**
	 * Load a plugin xml configuration file
	 * @static
	 * @param  string $fileName The plugin xml configuration file to load
	 * @return void
	 */
	private static function getXmlInfo($fileName) {}

}
