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

/**
 * This is the primarly file that should be included in all the i-MSCP's user
 * levels scripts such as all scripts that live under gui/{admin,reseller,client}
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

define('GUI_ROOT_DIR', dirname(__DIR__));

// Define path for the i-MSCP library directory
define('LIBRARY_PATH', GUI_ROOT_DIR . '/library');

// Define path of the plugins directory
define('PLUGINS_PATH', GUI_ROOT_DIR .'/plugins');

// Define cache directory path
define('CACHE_PATH', GUI_ROOT_DIR .'/data/cache');

// Define persistent directory path
define('PERSISTENT_PATH', GUI_ROOT_DIR .'/data/persistent');

// Set include path
set_include_path(
	implode(
        PATH_SEPARATOR,
        array_unique(
            array(
                LIBRARY_PATH, LIBRARY_PATH . '/vendor',
                LIBRARY_PATH, LIBRARY_PATH . '/vendor/phpseclib',
                DEFAULT_INCLUDE_PATH
            )
        )
    )
);

// Autoloader
// TODO generate a classmap on first load and cache it for better performances
require_once 'iMSCP/Loader/AutoloaderFactory.php';
iMSCP\Loader\AutoloaderFactory::factory(
    array(
        'iMSCP\Loader\UniversalLoader' => array(
            'prefixes' => array(
                'iMSCP' => __DIR__, // Setup namespace for iMSCP classes using PHP5.3 namespaces
                'Zend_' => __DIR__ . '/vendor', // Setup prefix for Zend class using Pear naming convention
            ),
            'useIncludePath' => true
        )
    )
);

/**
 * Attach the primary exception writer to write uncaught exceptions messages to the client browser.
 *
 * The exception writer writes all exception messages to the client browser. In production, all messages are replaced by
 * a specific message to avoid revealingimportant information about the i-MSCP application environment if the user is
 * not an administrator.
 *
 * Another writers will be attached to this object during initialization process if enabled in the application wide
 * configuration file.
 */
iMSCP_Exception_Handler::getInstance()->attach(new iMSCP_Exception_Writer_Browser('message.tpl'));

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
require_once 'iMSCP/View/Helpers/Functions/Common.php';

if (isset($_SESSION['user_type'])) {
	$helperFileName = ucfirst(strtolower($_SESSION['user_type']));
	require_once 'iMSCP/View/Helpers/Functions/' . $helperFileName . '.php';
}
