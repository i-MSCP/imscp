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
 * PhpUnusedParameterInspection
 */

declare(strict_types=1);

namespace iMSCP;

use iMSCP\Handlers\ExceptionHandler;
use iMSCP\Handlers\NotAllowedHandler;
use iMSCP\Handlers\NotFoundHandler;
use Psr\Container\ContainerInterface;

/**
 * Class ServiceProvider
 *
 * Service provider that overrides default services as provided by the
 * Slim\DefaultServicesProvider
 *
 * @package iMSCP
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * Override default Slim services
     *
     * @inheritdoc
     */
    public function register(ContainerInterface $c): void
    {
        $c['phpErrorHandler'] = function (ContainerInterface $c) {
            return new ExceptionHandler();
        };
        $c['errorHandler'] = function (ContainerInterface $c) {
            return new ExceptionHandler();
        };
        $c['notFoundHandler'] = function (ContainerInterface $c) {
            return new NotFoundHandler();
        };
        $c['notAllowedHandler'] = function (ContainerInterface $c) {
            return new NotAllowedHandler();
        };
    }
}
