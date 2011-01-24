<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
 * Script short description
 *
 *  This is the single entry point of the iMSCP Front end. This script will load Zend_Application and instantiate
 *  it by passing:
 * 
 *  - The current environment
 *  - Options for bootstrapping
 */

// Error reporting
error_reporting(E_ALL|E_STRICT);

define('APPLICATION_ENV', 'development');

// Define application environment
defined('APPLICATION_ENV')
	|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

/**
 * Check PHP version (5.2.4 or newer since ZF 1.7.0)
 */
if (version_compare(phpversion(), '5.2.4', '<') === true) {
	if(APPLICATION_ENV != 'production') {
		die('Error: Your PHP version is ' . phpversion() . '. i-MSCP requires PHP 5.2.4 or newer.');
	} else {
		header('Location: /500.html');
		exit;
	}
}

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
	if(APPLICATION_ENV != 'production') {
		die('Error: Unable to found i-MSCP system configuration file!');
	} else {
		header('Location: /500.html');
		exit;
	}
}

$sysCfgFile = $configDir . DS  . 'imscp.xml';
$cachedCfgFile = 'imscp.' . filemtime($sysCfgFile) . '.php';

if(!file_exists(ROOT_PATH . DS . 'data' . DS . 'cache' . DS . $cachedCfgFile) || APPLICATION_ENV != 'production') {
	try {
		// Load local configuration file
		require_once 'Zend/Config/Ini.php';
		$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'configs' . DS . 'imscp.ini', 'frontend', true);

		// Load system configuration file
		require_once 'Zend/Config/Xml.php';
		$sysCfg = new Zend_Config_Xml($sysCfgFile);

		// Load imscp key and initialization vector for encryption
		$key =  $iv = '';
		if(($keysFile = @file_get_contents($configDir . DS . 'common' . DS . 'imscp-kv')) && eval($keysFile) !== false) {
			if(strlen($key) == 32 && strlen($iv) == 8) {
				$config->encryption = array('key' => $key, 'vector' => $iv);
			} else {
				throw new Exception('The given key or initialization vector has a wrong size!');
			}
		} else {
			throw new Exception('Unable to reach or evaluate the imscp-kv file!');
		}

		// Merge system and local configuration files (only needed sections)
		$config->merge($sysCfg->get('product'));
		$config->merge($sysCfg->get('frontend'));

		// Process configuration file caching only in production
		if(APPLICATION_ENV == 'production') {
			require_once 'Zend/Config/Writer/Array.php';
			$writer = new Zend_Config_Writer_Array();
			$writer->write(ROOT_PATH . DS . 'data' . DS . 'cache' . DS . $cachedCfgFile, $config, true);

			// Fixing correct permissions (This file should not be readable by everyone)
			chmod(ROOT_PATH . DS . 'data' . DS . 'cache' . DS . $cachedCfgFile, 0640);

			// Removing old cached configuration file if one exists
			foreach(scandir(ROOT_PATH . DS . 'data' . DS . 'cache') as $fileName) {
				if($fileName != $cachedCfgFile && preg_match('/^imscp\.[0-9]+\.php$/', $fileName)) {
					@unlink(ROOT_PATH . DS . 'data' . DS . 'cache' . DS . $fileName);
				}
			}
		}

		// Process some cleanup
		unset($sysCfgFile, $cachedCfgFile, $sysCfg, $fileName, $key, $iv);

	} catch(Exception $e) {
		if(APPLICATION_ENV != 'production') {
			die('Error: ' . $e->getMessage());
		} else {
			header('Location: /500.html');
			exit;
		}
	}
} else {
	$config = include_once(ROOT_PATH . DS . 'data' . DS . 'cache' . DS . $cachedCfgFile);
	$config = $config['frontend'];
}

// Zend_Application
require_once 'Zend/Application.php';

// Create application,
$imscp = new Zend_Application(APPLICATION_ENV, $config);

// Process some cleanup
unset($config);

// Bootstrap and run
$imscp->bootstrap()->run();
