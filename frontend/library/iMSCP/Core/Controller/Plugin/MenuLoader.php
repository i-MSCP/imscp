<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 by internet Multi Server Control Panel
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
 * Plugin that Load menu according user level
 *
 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version DRAFT (to be finished)
 * @TODO build menus per modules
 */
class iMSCP_Core_Controller_Plugin_MenuLoader extends Zend_Controller_Plugin_Abstract
{

	/**
	 * Load menu according user level
	 *
	 * @param Zend_Controller_Request_Abstract $request
	 * @return Zend_Controller_Request_Abstract
	 */
	public function routeShutdown(Zend_Controller_Request_Abstract $request) {

		$view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
		$url = $view->url();
		$moduleName = substr($url, 1, strpos($url, '/', 1) - 1);

		switch($moduleName) {
			case 'admin':
			case 'reseller':
			case 'client':
				$view->navigation(
					new Zend_Navigation(
						new Zend_Config_Xml(APPLICATION_PATH . "/configs/menus/$moduleName.xml", 'nav')
					)
				);

			break;
		}

		return $request;
	}
}
