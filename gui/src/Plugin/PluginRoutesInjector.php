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

/**
 * @noinspection
 * PhpIncludeInspection
 * PhpUnhandledExceptionInspection
 * PhpDocMissingThrowsInspection
 * PhpUnusedParameterInspection
 * PhpIncludeInspection
 */

declare(strict_types=1);

namespace iMSCP\Plugin;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Slim\App;

/**
 * Class PluginRoutesInjector
 *
 * This class provides configuration-driven routing for i-MSCP plugins.
 *
 * @package iMSCP\Plugin
 */
class PluginRoutesInjector
{
    const DEFAULT_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS'
    ];

    /**
     * Inject plugin routes into Slim application
     *
     * @param App $app
     * @param PluginManager $pm
     * @return void
     */
    public function __invoke(App $app, PluginManager $pm): void
    {
        foreach ($pm->pluginGetLoaded() as $plugin) {
            // For backward compatibility only (duck-typing).
            // the iMSCP_Plugin_Action::route() method is deprecated since
            // the plugin API version 1.5.1
            if (method_exists($plugin, 'route')) {
                /** @var RequestInterface $request */
                $request = $app->getContainer()->get('request');

                if (!($pluginActionScriptPath = $plugin->route(
                    parse_url($request->getUri())
                ))) {
                    continue;
                }

                $routes = [
                    $request->getUri()->getPath() => $pluginActionScriptPath
                ];
            } else {
                $routes = $plugin->getRoutes();
            }

            $this->injectRoutes($app, $routes, $plugin->getName());
        }
    }

    /**
     * @param App $app
     * @param array $routes An array containing route specifications
     * @param string $pluginName
     * @return void
     */
    private function injectRoutes(
        App $app,
        array $routes,
        string $pluginName
    ): void
    {
        foreach ($routes as $key => $spec) {
            if (is_array($spec)) {
                // Route group
                if (isset($spec['routes'])) {
                    $this->injectRouteGroup($app, $spec, $pluginName);
                    continue;
                }

                // Single route specification
                $this->injectRoute($app, $spec, $pluginName);
                continue;
            }

            // Route path => Route handler
            // For backward compatibility only.
            $app->any($key, function ($request, $response) use ($spec) {
                require $spec;
            });
        }
    }

    /**
     * @param App $app
     * @param array $spec
     * @param string $pluginName
     * @return void
     */
    private function injectRouteGroup(
        App $app,
        array $spec,
        string $pluginName
    ): void
    {
        if (!isset($spec['pattern']) || !is_string($spec['pattern'])) {
            throw new InvalidArgumentException(sprintf(
                'Missing "pattern" key in route group specification for the "%s" plugin.',
                $pluginName
            ));
        }

        $group = $app->group(
            $spec['pattern'],
            function (App $app) use ($spec, $pluginName) {
                foreach ($spec['routes'] as $routeSpec) {
                    $this->injectRoute($app, $routeSpec, $pluginName);
                }
            }
        );

        // Add route group middleware if any
        if (isset($spec['middleware'])) {
            foreach ((array)$spec['middleware'] as $middleware) {
                $group->add($middleware);
            }
        }
    }

    /**
     * @param App $app
     * @param array $spec
     * @param string $pluginName
     * @return void
     */
    private function injectRoute(
        App $app,
        array $spec,
        string $pluginName
    ): void
    {
        if (!isset($spec['pattern']) || !is_string($spec['pattern'])) {
            throw new InvalidArgumentException(sprintf(
                'Missing or invalid "pattern" key in route specification for the "%s" plugin.',
                $pluginName
            ));
        }

        if (!isset($spec['handler'])) {
            throw new InvalidArgumentException(sprintf(
                'Missing route "handler" key in route specification for the "%s" plugin.',
                $pluginName
            ));
        }

        $methods = isset($spec['methods']) ? $spec['methods'] : self::DEFAULT_METHODS;

        if (!is_array($methods)) {
            throw new InvalidArgumentException(sprintf(
                'Allowed HTTP methods for a route must be in form of an array; received "%s" for the "%s" plugin',
                gettype($methods),
                $pluginName
            ));
        }

        $route = $app->map($methods, $spec['pattern'], $spec['handler']);

        // Set route name if any
        if (isset($spec['name'])) {
            $route->setName($spec['name']);
        }

        // Add route middleware if any
        if (isset($routeSpec['middleware'])) {
            foreach ((array)$spec['middleware'] as $middleware) {
                $route->add($middleware);
            }
        }
    }
}
