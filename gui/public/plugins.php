<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Events as Events;
use iMSCP_Registry as Registry;

require_once 'imscp-lib.php';

if (($urlComponents = parse_url($_SERVER['REQUEST_URI'])) === false
    || !isset($urlComponents['path'])
) {
    showBadRequestErrorPage();
}

$urlComponents['path'] = rtrim($urlComponents['path'], '/');

/** @var iMSCP_Plugin_Manager $pluginManager */
$pluginManager = Registry::get('iMSCP_Application')->getPluginManager();
$plugins = $pluginManager->pluginGetLoaded();

if (empty($plugins)) {
    showNotFoundErrorPage();
}

/** @var iMSCP_Events_Manager_Interface $eventsManager */
$eventsManager = Registry::get('iMSCP_Application')->getEventsManager();

/** @var iMSCP_Events_Listener_ResponseCollection $responses */
$responses = $eventsManager->dispatch(Events::onBeforePluginsRoute, [
    'pluginManager' => $pluginManager
]);

if ($responses->isStopped()) {
    showNotFoundErrorPage();
}

$pluginActionScriptPath = NULL;
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

if (NULL === $pluginActionScriptPath) {
    showNotFoundErrorPage();
}

$eventsManager->dispatch(Events::onAfterPluginsRoute, [
    'pluginManager' => $pluginManager,
    'scriptPath'    => $pluginActionScriptPath
]);

if (!is_file($pluginActionScriptPath)) {
    showNotFoundErrorPage();
}

include $pluginActionScriptPath;
