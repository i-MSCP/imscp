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
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */


/**
 * Bootstrap class
 *
 * @category    iMSCP
 * @package     iMSCP_Boostrap
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @since       1.0.0
 * @version     1.0.0
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	/**
	 * Store configuration object for further usage
	 *
	 * @return Zend_Config
	 */
	protected function _initConfig()
	{
		$config = new Zend_Config($this->getOptions(), true);
		Zend_Registry::set('config', $config);

		return $config;
	}

	/**
	 * Init routes
	 *
	 * @return void
	 */
	protected function _initRoutes()
	{
		$this->bootstrap('FrontController');
        $frontController = $this->getResource('FrontController');

		// Getting all modules names
		$modulesDirectory = APPLICATION_PATH . DS . 'modules';
		$modulesNames = array();
		foreach (new DirectoryIterator($modulesDirectory) as $directory) {
            if ($directory->isDot() || !$directory->isDir()) continue;
            $directory = $directory->getFilename();
            if ($directory == '.svn') continue;
            $modulesNames[] = $directory;
		}

		// Retrieving all routes config files
		$routesConfigFiles = array();
		foreach ($modulesNames as $moduleName) {
			$routesDirectory = APPLICATION_PATH . DS . 'modules' . DS . $moduleName . DS . 'config' . DS . 'routes';
			if (!is_dir($routesDirectory)) continue;
			$directoryIterator = new DirectoryIterator($routesDirectory);
			foreach ($directoryIterator as $file) {
                if ($file->isDot() || $file->isDir()) continue;
                $routesConfigFilesName = $file->getFilename();
                if (preg_match('/^[^a-z]/i', $routesConfigFilesName)) continue;
                $routesConfigFiles[] = $routesDirectory . DS . $routesConfigFilesName;
            }
		}

		// Creating new Zend_Controller_Router_Rewrite instance
		$routes = new Zend_Controller_Router_Rewrite();

		// Add routes - start
		foreach ($routesConfigFiles as $routesConfigFile) {
			$routesConfig = new Zend_Config_Ini($routesConfigFile, 'routes');
			$routes->addConfig($routesConfig, 'routes');
		}

		// Setting routes
		$frontController->setRouter($routes);

		// Don't use default route
		$frontController->getRouter()->removeDefaultRoutes();
	}


	/**
	 * Decrypt database password
	 * 
	 * @throws Zend_Exception
	 * @return Zend_Config
	 */
	protected function _initDbPassword()
	{
		$config = Zend_Registry::get('config');
		$filter = new iMSCP_Filter_Encrypt_McryptBase64($config->encryption);
		$password = $filter->decrypt($config->resources->doctrine->params->password);
		$config->resources->doctrine->params->password = $password;

		return $config;
	}
}
