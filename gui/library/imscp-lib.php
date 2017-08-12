<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

namespace iMSCP;

// Define application paths
defined('GUI_ROOT_DIR') || define('GUI_ROOT_DIR', dirname(__DIR__));
defined('LIBRARY_PATH') || define('LIBRARY_PATH', GUI_ROOT_DIR . '/library');
defined('CACHE_PATH') || define('CACHE_PATH', GUI_ROOT_DIR . '/data/cache');
defined('PERSISTENT_PATH') || define('PERSISTENT_PATH', GUI_ROOT_DIR . '/data/persistent');
defined('CONFIG_FILE_PATH') || define('CONFIG_FILE_PATH', getenv('IMSCP_CONF') ?: '/etc/imscp/imscp.conf');

// Define application environment
defined('APPLICATION_ENV') || define(
    'APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production')
);

// Setup include path
set_include_path(implode(PATH_SEPARATOR, array_unique(array_merge(
    [LIBRARY_PATH, LIBRARY_PATH . '/vendor/Zend/library'], explode(PATH_SEPARATOR, get_include_path())
))));

require_once 'iMSCP/Application.php';

// Bootstrap application
$application = new Application(APPLICATION_ENV);
$application->bootstrap(CONFIG_FILE_PATH);
