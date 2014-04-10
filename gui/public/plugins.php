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
 * @package     iMSCP_Core
 * @subpackage  Plugin
 * @copyright   2010-2014 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

// Include core library
require_once 'imscp-lib.php';

/** @var iMSCP_Plugin_Manager $pluginManager */
$pluginManager = iMSCP_Registry::get('pluginManager');
$plugins = $pluginManager->getLoadedPlugins('Action');
$controllerPath = null;

if (!empty($plugins)) {
	$eventsManager = iMSCP_Events_Aggregator::getInstance();

	if (($urlComponents = parse_url($_SERVER['REQUEST_URI'])) !== false) {
		$responses = $eventsManager->dispatch(
			iMSCP_Events::onBeforePluginsRoute, array('pluginManager' => $pluginManager)
		);

		if (!$responses->isStopped()) {
			foreach ($plugins as $plugin) {
				if (($controllerPath = $plugin->route($urlComponents))) {
					break;
				}

				foreach ($plugin->getRoutes() as $pluginRoute => $pluginControllerPath) {
					if ($pluginRoute == $urlComponents['path']) {
						$controllerPath = $pluginControllerPath;
						$_SERVER['SCRIPT_NAME'] = $pluginRoute;
						break;
					}
				}

				if ($controllerPath) {
					break;
				}
			}

			$eventsManager->dispatch(
				iMSCP_Events::onAfterPluginsRoute,
				array('pluginManager' => $pluginManager, 'controllerPath' => $controllerPath)
			);

			if ($controllerPath) {
				include_once $controllerPath;
				exit;
			}
		}
	} else {
		throw new iMSCP_Exception(sprintf('Unable to parse URL: %s', $_SERVER['REQUEST_URI']));
	}
}

showNotFoundErrorPage();
