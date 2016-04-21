<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
 */

require_once 'imscp-lib.php';

if (($urlComponents = parse_url($_SERVER['REQUEST_URI'])) === false) {
    showBadRequestErrorPage();
}

/** @var iMSCP_Plugin_Manager $pluginManager */
$pluginManager = iMSCP_Registry::get('pluginManager');
$plugins = $pluginManager->pluginGetLoaded('Action');

if (empty($plugins)) {
    showNotFoundErrorPage();
}

$eventsManager = iMSCP_Events_Aggregator::getInstance();
$responses = $eventsManager->dispatch(iMSCP_Events::onBeforePluginsRoute, array(
    'pluginManager' => $pluginManager
));

if ($responses->isStopped()) {
    showNotFoundErrorPage();
}

$pluginActionScriptPath = null;
foreach ($plugins as $plugin) {
    if ($pluginActionScriptPath = $plugin->route($urlComponents)) {
        break;
    }

    foreach ($plugin->getRoutes() as $pluginRoute => $scriptPath) {
        if ($pluginRoute == $urlComponents['path']) {
            $pluginActionScriptPath = $scriptPath;
            $_SERVER['SCRIPT_NAME'] = $pluginRoute;
            break;
        }
    }

    if ($pluginActionScriptPath) {
        break;
    }
}

if (null === $pluginActionScriptPath) {
    showBadRequestErrorPage();
}

$eventsManager->dispatch(iMSCP_Events::onAfterPluginsRoute, array(
    'pluginManager' => $pluginManager, 'scriptPath' => $pluginActionScriptPath
));

include $pluginActionScriptPath;
