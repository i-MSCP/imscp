<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
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
 * @package     iMSCP_Core
 * @subpackage  Plugin_Action
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/** @See iMSCP_Plugin */
require_once 'iMSCP/Plugin.php';

/**
 * Base class for action plugins.
 *
 * All i-MSCP plugins to interfere with the event system need to inherit from this class.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Plugin_Action
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
abstract class iMSCP_Plugin_Action extends iMSCP_Plugin
{
	/**
	 * @var iMSCP_Events_Manager_Interface
	 */
	protected $_controller;

	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $controller
	 */
	public function register(iMSCP_Events_Manager_Interface $controller)
	{
		trigger_error(sprintf('register() not implemented in %s', get_class($this)), E_USER_WARNING);
	}

	/**
	 * Return events controller.
	 *
	 * @return null|iMSCP_Events_Manager
	 */
	public function getController()
	{
		if(!isset($this->_controller)) {
			trigger_error(sprintf('Controller is not registered in %s', get_class($this)), E_USER_WARNING);
		}

		return $this->_controller;
	}

	/**
	 * Get routes
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		return array();
	}
}
