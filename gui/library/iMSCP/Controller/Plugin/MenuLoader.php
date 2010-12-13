<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * Copyright (C) 2010 by internet Multi Server Control Panel - http://i-mscp.net
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 * The Original Code is "i-MSCP - internet Multi Server Control Panel".
 *
 * The Initial Developer of the Original Code is i-MSCP Team.
 * Portions created by Initial Developer are Copyright (C) 2010 by
 * internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @copyright   2010 by i-MSCP | http://i-mscp.net
 * @author      i-MSCP Team
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id: MenuLoaderPlugin.php 4100 2010-12-12 08:16:01Z nuxwin $
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/ GPL v2
 * @version 1.0.0
 */
class iMSCP_Controller_Plugin_MenuLoader extends Zend_Controller_plugin_Abstract
{

	/**
	 * Load menu according user level
	 *
	 * @param Zend_Controller_Request_Abstract $request
	 * @return Zend_Controller_Request_Abstract
	 */
	public function routeShutdown(Zend_Controller_Request_Abstract $request) {

		$moduleName = $request->getModuleName();

		switch($moduleName) {
			case 'admin':
			case 'reseller':
			case 'client':
				Zend_Registry::get('view')->navigation(
					new Zend_Navigation(
						new Zend_Config_Xml(APPLICATION_PATH . "/configs/menus/$moduleName.xml", 'nav')
					)
				);

			break;
		}

		return $request;
	}
}
