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
 * @category    iMSCP
 * @package     iMSCP_Scripts
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Doctrine CLI script
 */

// Error reporting
error_reporting(E_ALL|E_STRICT);

define('APPLICATION_ENV', 'development');

/**
 * Check PHP version (5.2.4 or newer since ZF 1.7.0)
 */
if (version_compare(phpversion(), '5.2.4', '<') === true) {
	die('Error: Your PHP version is ' . phpversion() . ". i-MSCP requires PHP 5.2.4 or newer.\n");
}

// Define environment
define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Path and directory separators
defined('DS') ||define('DS', DIRECTORY_SEPARATOR);
defined('PS') || define('PS', PATH_SEPARATOR);

// Define path to application and public directories
defined('ROOT_PATH') || define('ROOT_PATH', realpath(dirname(__FILE__) . DS . '..'));
defined('PUBLIC_PATH') || define('PUBLIC_PATH', ROOT_PATH . DS . 'public');
defined('APPLICATION_PATH') || define('APPLICATION_PATH', ROOT_PATH . DS .'application');

// Ensure library/ is in include_path
set_include_path(implode(PS, array(ROOT_PATH . DS . 'library', get_include_path())));

// Determine system i-MSCP configuration file path
if(is_dir('/etc/imscp/')) {
	$configDir = '/etc/imscp';
} elseif(is_dir('/usr/local/etc/imscp')) {
	$configDir = '/usr/local/etc/imscp';
}

if(!file_exists($sysCfgFile = $configDir . DS  . 'imscp.xml')) {
	die("Error: Unable to found i-MSCP system configuration file!\n");
}

$sysCfgFile = $configDir . DS  . 'imscp.xml';

try {
	// Load local configuration file
	require_once 'Zend/Config/Ini.php';
	$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'configs' . DS . 'imscp.ini', 'frontend', true);

	// Load system configuration file
	require_once 'Zend/Config/Xml.php';
	$sysCfg = new Zend_Config_Xml($sysCfgFile);

	// Load imscp key and initialization vector for encryption
	$key =  $iv = '';
	if(($keysFile = file_get_contents($configDir . DS . 'common' . DS . 'imscp-keys')) && eval($keysFile) !== false) {
		$config->encryption = array('key' => $key, 'vector' => $iv, 'salt' => true);
	} else {
		throw new Zend_Exception('Unable to reach or evaluate the imscp-keys file!');
	}

	// Merge system and local configuration files (only needed sections)
	$config->merge($sysCfg->get('product'));
	$config->merge($sysCfg->get('frontend'));

	// Process some cleanup
	unset($sysCfgFile, $sysCfg, $fileName, $key, $iv);

} catch(Exception $e) {
	die('Error: ' . $e->getMessage() . "\n");
}

require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$imscp = new Zend_Application(APPLICATION_ENV, $config);

// Load only needed resources
$imscp->getBootstrap()
	->bootstrap('config') // Setting configuration object - See Bootstrap::_initConfig()
	->bootstrap('DbPassword') // Decrypt database password - See Bootstrap::_initDbPassword()
	->bootstrap('doctrine'); // Initialize Doctrine - See iMSCP_Bootstrap_Resource_Doctrine::init()
	//->bootstrap('autoload'); // See Bootstrap::_initAutoload()

// Set aggressive loading to make sure migrations are working
Doctrine_Manager::getInstance()->setAttribute(
    Doctrine_Core::ATTR_MODEL_LOADING, Doctrine_Core::MODEL_LOADING_AGGRESSIVE
);

$options = $imscp->getBootstrap()->getOptions();

$cli = new Doctrine_Cli($options['resources']['doctrine']);
$cli->run($_SERVER['argv']);
