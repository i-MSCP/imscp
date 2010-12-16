<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 by internet Multi Server Control Panel
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
 * @category    i-MSCP
 * @copyright   2010 by i-MSCP | http://i-mscp.net
 * @author      i-MSCP Team
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Script shot description
 *
 *  This is the entry point of the iMSCP Application (frontend). This script will load Zend_Application and instantiate
 *  it by passing:
 * 
 *  - The current environment
 *  - Options for bootstrapping
 *
 *  The options for bootstrapping can include the path to the file containing the bootstrap class and optionally:
 *
 *  - Any extra include path to set
 *  - Any php.ini setting to initialize
 *  - The class name for the bootstrap (if not Bootstrap)
 *  - Resource prefix to path pairs to use
 *  - Any resources to use (by class name or short name)
 *  - Additional path to a configuration file to load
 *  - Additional configuration options 
 *
 * Note:
 *  Options may be an array, a Zend_Config object, or the path to a configuration file. For now, it's the application.ini
 *  configuration file.
 */

/**
 * Check PHP version
 */
if (version_compare(phpversion(), '5.2.0', '<') === true) {
    die('ERROR: Your PHP version is ' . phpversion() . '. i-MSCP requires PHP 5.2.0 or newer.');
}

// Error reporting
error_reporting(E_ALL|E_STRICT);

// Path and directory separators
defined('DS') ||define('DS', DIRECTORY_SEPARATOR);
defined('PS') || define('PS', PATH_SEPARATOR);

// Define path to application and public directories
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . DS . '..' . DS .'application'));
defined('PUBLIC_PATH')      || define('PUBLIC_PATH', realpath(APPLICATION_PATH . DS . '..' . DS . 'public'));

// Define application environment
defined('APPLICATION_ENV')
	|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PS, array(realpath(APPLICATION_PATH . DS . '..' . DS . 'library'), get_include_path())));

/**
 * Load main i-MSCP configuration file
 */
if(file_exists('/etc/imscp/imscp.xml')) {
	$config = '/etc/imscp/imscp.xml';
} elseif(file_exists('/usr/local/etc/imscp.xml')) {
	$config = '/usr/local/etc/imscp.xml';
} else {
	die('Error: Unable to reach the main i-MSCP configuration file!');
}

/** Zend_Config_Xml **/
require_once 'Zend/Config/Xml.php';

try {
	$config = new Zend_Config_Xml($config, 'all');
	$config = $config->toArray();
} catch(Exception $e) {
	die('Error: Unable to parse the main i-MSCP configuration file. Please check syntax.');
}

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application bootstrap, and run
$imscp = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . DS . 'configs' . DS . 'imscp.ini');

// Merge main configuration with current configuration, bootstrap, and run
$config = $imscp->mergeOptions($imscp->getOptions(), $config);
$imscp->setOptions($config)->bootstrap()->run();
