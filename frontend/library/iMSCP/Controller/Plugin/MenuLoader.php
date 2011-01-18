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
 * @category    iMSCP
 * @package     iMSCP_Controller
 * @subpackage  Plugin
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Plugin to load menu according user level
 *
 * @category    iMSCP
 * @package     iMSCP_Controller
 * @subpackage  Plugin
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @since       1.0.0
 * @version     1.0.0
 */
class iMSCP_Controller_Plugin_MenuLoader extends Zend_Controller_Plugin_Abstract
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
		$level = substr($url, 1, strpos($url, '/', 1) - 1);

		switch($level) {
			case 'admin':
			case 'reseller':
			case 'customer':
				$view->navigation(
					new Zend_Navigation(new Zend_Config_Xml(APPLICATION_PATH . "/configs/menus/$level.xml", 'nav'))
				);

			break;
		}

		return $request;
	}
}
