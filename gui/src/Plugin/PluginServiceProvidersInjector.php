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

/** @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

namespace iMSCP\Plugin;

use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use Psr\Container\ContainerInterface;

/**
 * Class PluginServiceProvidersInjector
 * @package iMSCP\Plugin
 */
class PluginServiceProvidersInjector
{
    /**
     * Register plugin service providers with the dependency container
     *
     * @param ContainerInterface $container
     * @param EventAggregator $events
     * @param PluginManager $pm
     * @return void
     */
    public function __invoke(
        ContainerInterface $container,
        EventAggregator $events,
        PluginManager $pm
    ): void
    {
        $events->dispatch(Events::onBeforeInjectPluginServiceProviders, [
            'pluginManager' => $pm
        ]);

        foreach ($pm->pluginGetLoaded() as $plugin) {
            if ($serviceProvider = $plugin->getServiceProvider()) {
                $serviceProvider->register($container);
            }
        }
    }
}
