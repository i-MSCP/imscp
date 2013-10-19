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
 * @subpackage  Plugin
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

// Include core library
require_once 'imscp-lib.php';

if (iMSCP_Registry::isRegistered('pluginManager')) {
	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforePluginsRoute);

	/** @var iMSCP_Plugin_Manager $pluginManager */
	$pluginManager = iMSCP_Registry::get('pluginManager');
	$plugins = $pluginManager->getLoadedPlugins('Action');
	$actionScript = null;
	$urlComponents = parse_url($_SERVER['REQUEST_URI']);

	if (!empty($plugins)) {
		/** @var $plugin iMSCP_Plugin_Action */
		foreach ($plugins as $plugin) {
			if (is_callable(array($plugin, 'route'))) {
				if ($plugin->route($urlComponents, $actionScript)) {
					break;
				}
			}

			$pluginRoutes = $plugin->getRoutes();

			if (!empty($pluginRoutes)) {
				foreach ($pluginRoutes as $pluginRoute => $pluginActionScript) {
					if ($pluginRoute == $urlComponents['path']) {
						$actionScript = $pluginActionScript;
						$_SERVER['SCRIPT_NAME'] = $pluginRoute;
						break;
					}
				}
			}
		}
	}

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterPluginsRoute);

	if ($actionScript !== null) {
		require $actionScript;
	} else {
		showNotFoundErrorPage();
	}
} else {
	throw new iMSCP_Plugin_Exception('An unexpected error occurred');
}
