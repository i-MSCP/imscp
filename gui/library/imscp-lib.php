<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by i-MSCP Team
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
 * @copyright   2010-2015 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

// Set default error reporting level
error_reporting(E_ALL | E_STRICT);

// Sets to TRUE here to ensure displaying of the base core errors
// Will be overwritten during initialization process
// @see iMSCP_Initializer::_setDisplayErrors()
ini_set('display_errors', 1);

/**
 * Check PHP version
 */
//if (version_compare(phpversion(), '5.3.2', '<') === true) {
//	die('Your PHP version is ' . phpversion() . ". i-MSCP requires PHP 5.3.2 or newer.\n");
//}

// Define paths
define('GUI_ROOT_DIR', dirname(__DIR__));
define('LIBRARY_PATH', GUI_ROOT_DIR . '/library');
define('PLUGINS_PATH', GUI_ROOT_DIR . '/plugins');
define('CACHE_PATH', GUI_ROOT_DIR . '/data/cache');
define('PERSISTENT_PATH', GUI_ROOT_DIR . '/data/persistent');
define('CONFIG_FILE_PATH', getenv('IMSCP_CONF') ?: '/etc/imscp/imscp.conf');
define('CONFIG_CACHE_FILE_PATH', CACHE_PATH . '/imscp_config.conf');
define('DBCONFIG_CACHE_FILE_PATH', CACHE_PATH . '/imscp_dbconfig.conf');

// Setup include path
set_include_path(implode(PATH_SEPARATOR, array_unique(
	array_merge(array(LIBRARY_PATH, LIBRARY_PATH . '/vendor'), explode(PATH_SEPARATOR, get_include_path()))
)));

// Setup autoloader
require_once 'Zend/Loader/AutoloaderFactory.php';
Zend_Loader_AutoloaderFactory::factory(
	array(
		'Zend_Loader_StandardAutoloader' => array(
			'prefixes' => array(
				'iMSCP_' => LIBRARY_PATH . '/iMSCP',
				'Zend_' => LIBRARY_PATH . '/vendor/Zend',
				'Crypt_' => LIBRARY_PATH . '/vendor/phpseclib/Crypt',
				'File_' => LIBRARY_PATH . '/vendor/phpseclib/File',
				'Math_' => LIBRARY_PATH . '/vendor/phpseclib/Math'
			)
		)
	)
);

// Set handler for uncaught exceptions
iMSCP_Registry::set('exceptionHandler', new iMSCP_Exception_Handler());

/**
 * Include i-MSCP common functions
 */
require_once 'vendor/idna_convert/idna_convert.class.php';
require_once 'shared-functions.php';

/**
 * Include i-MSCP app installer functions
 * @Todo move this statement at begin of related action scripts since the sw
 * functions are not needed in whole application.
 */
require_once 'sw-functions.php';

/**
 * Internationalization functions
 */
require_once 'i18n.php';

/**
 * Authentication functions
 */
require_once 'login-functions.php';

/**
 * User level functions
 *
 * @todo: Must be refactored to be able to load only files that are needed
 */
require_once 'admin-functions.php';
require_once 'reseller-functions.php';
require_once 'client-functions.php';

/**
 * Some others shared libraries
 */
require_once 'input-checks.php';
require_once 'emailtpl-functions.php';
require_once 'layout-functions.php';

/**
 * Bootstrap the i-MSCP environment, and default configuration
 *
 * @see {@link iMSCP_Bootstrap} class
 * @see {@link iMSCP_Initializer} class
 */
require_once 'environment.php';

/**
 * View helpers functions
 */
require_once 'iMSCP/View/Helpers/Functions.php';
