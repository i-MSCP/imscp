<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

use Pimple\ServiceProviderInterface;

/**
 * Class iMSCP_Plugin_Action
 *
 * All i-MSCP plugins which interfere with the event system need to inherit
 * from this class.
 */
abstract class iMSCP_Plugin_Action extends iMSCP_Plugin
{
    /**
     * Register a callback for the given event(s)
     *
     * @param iMSCP_Events_Manager_Interface $events
     * @return void
     */
    public function register(iMSCP_Events_Manager_Interface $events)
    {
    }

    /**
     * Get routes
     *
     * This method allow the plugin to register its routes. For instance:
     *
     * Old (deprecated) way (prior plugin API 1.5.1)
     * <code>
     * $pluginDir = $this->getPluginManager()->pluginGetDirectory() . '/'
     *  . $this->getName();
     *
     * return array(
     *  '/admin/mailgraph.php' => $pluginDir . '/frontend/mailgraph.php',
     *    '/admin/mailgraphics.php' => $pluginDir . '/frontend/mailgraphics.php'
     * );
     * </code>
     * 
     * New way (since plugin API 1.5.1)
     * <code>
     * 
     * return [
     *  // TODO Documentation
     * ];
     * </code>
     *
     * @return array An array containing action script paths
     */
    public function getRoutes()
    {
        return [];
    }

    /**
     * Route an URL
     *
     * This method allow the plugin to provide its own routing logic. If a
     * route match the given URL, this method MUST return a string representing
     * the action script to load, else, NULL must be returned. For instance:
     *
     * <code>
     * if (strpos($urlComponents['path'], '/mydns/api/') === 0) {
     *  return $this->getPluginManager()->pluginGetDirectory() . '/'
     *   . $this->getName() . '/api.php';
     * }
     *
     * return null;
     * </code>
     *
     * @param array $urlComponents Associative array containing URL components
     * @return string|null Either a string representing an action script path
     *                     or null if not route match the URL
     * @deprecated since v1.5.3 (build 2019*) - backward compatibility ensured
     *             through duck-typing in the iMSCP\Plugin\PluginRoutesInjector
     */
    /*
    public function route( $urlComponents)
    {
        return NULL;
    }
    */

    /**
     * @return ServiceProviderInterface|null
     */
    public function getServiceProvider() :?ServiceProviderInterface
    {
        return null;
    }
}
