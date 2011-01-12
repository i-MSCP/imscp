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
 * @category    iMSCP
 * @package     iMSCP_Bootstap
 * @subpackage  Resource
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Router plugin resource that initialize the routes
 *
 * @category    iMSCP
 * @package     iMSCP_Boostrap
 * @subpackage  Resource
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @since       1.0.0
 * @version     1.0.0
 */
class iMSCP_Bootstrap_Resource_Router extends Zend_Application_Resource_ResourceAbstract
{
	/**
	 * @var Zend_Controller_Router_Rewrite
	 */
	protected $_router;

	/**
	 * @var Array modules list
	 */
	protected $_modules;

	/**
	 * iDefined by Zend_Application_Resource_Resource
	 * 
	 * @return Zend_Controller_Router_Rewrite
	 */
	public function init()
	{
		if(APPLICATION_ENV == 'production' && file_exists(ROOT_PATH . DS . 'data' . DS . 'cache' . DS . 'routes.php')) {
			$routesConfig = new Zend_Config(include_once(ROOT_PATH . DS . 'data' . DS . 'cache' . DS . 'routes.php'));
		} else {
			$routesConfig = new Zend_Config(array(), true);
			// Retrieves each routes definition file for all modules and add them to the router
			foreach($this->_getModules() as $module) {
				$routesDirectory = APPLICATION_PATH . DS . 'modules' . DS . $module . DS . 'config' . DS . 'routes';
				if (!is_dir($routesDirectory)) continue;
				$directoryIterator = new DirectoryIterator($routesDirectory);
				foreach ($directoryIterator as $file) {
					if ($file->isDot() || $file->isDir()) continue;
					$routesConfigFilesName = $file->getFilename();
					if (preg_match('/^[^a-z]/i', $routesConfigFilesName)) continue;
					$routesConfig->merge(new Zend_Config_Ini($routesDirectory . DS . $routesConfigFilesName, 'routes'));
                }
			}

			// Process configuration file caching only in production
			if(APPLICATION_ENV == 'production') {
				$this->_writeCacheFile($routesConfig);
			}
		}

		// Getting router object
		$router = $this->_getRouter();
		// Add all routes in it
		$router->addConfig($routesConfig, 'routes');
		// Removing default routes
		$router->removeDefaultRoutes();

		return $router;
	}

	/**
	 * Retrieve router object
	 *
	 * @return Zend_Controller_Router_Rewrite
	 */
	protected function _getRouter() {
		if (null === $this->_router) {
			$bootstrap = $this->getBootstrap();
			$bootstrap->bootstrap('FrontController');
			$this->_router = $bootstrap->getContainer()->frontcontroller->getRouter();
		}

		return $this->_router;
	}

	/**
	 * Retrieve all modules
	 * 
	 * @return void
	 */
	protected function _getModules() {
		if(null === $this->_modules) {
			$bootstrap = $this->getBootstrap();
			$bootstrap->bootstrap('FrontController');
			$frontController = $bootstrap->getResource('FrontController');
			$this->_modules = array_keys($frontController->getControllerDirectory());
		}

		return $this->_modules;
	}

	/**
	 * Write and store routes cache file
	 *
	 * @param Zend_Config $routesConfig
	 * @return void
	 */
	protected function _writeCacheFile(Zend_Config $routesConfig) {
		$writer = new Zend_Config_Writer_Array();
		$writer->write(ROOT_PATH . DS . 'data' . DS . 'cache' . DS . 'routes.php', $routesConfig, true);
		// Fixing correct permissions (This file should not be readable by everyone)
		chmod(ROOT_PATH . DS . 'data' . DS . 'cache' . DS . 'routes.php', 0640);
	}
}
