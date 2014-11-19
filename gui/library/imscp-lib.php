<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package	    i-MSCP
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
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
if (version_compare(phpversion(), '5.3.2', '<') === true) {
	die('Your PHP version is ' . phpversion() . ". i-MSCP requires PHP 5.3.2 or newer.\n");
}

// Define paths
define('GUI_ROOT_DIR', dirname(__DIR__));
define('LIBRARY_PATH', GUI_ROOT_DIR . '/library');
define('PLUGINS_PATH', GUI_ROOT_DIR .'/plugins');
define('CACHE_PATH', GUI_ROOT_DIR .'/data/cache');
define('PERSISTENT_PATH', GUI_ROOT_DIR .'/data/persistent');

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
