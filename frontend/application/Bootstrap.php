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
	 * Initialize routes
	 *
	 * @return Zend_Controller_Router_Interface
	 * @todo Merge all routes and create cache file to improve IO performances - Move to resource plugin
	 */
	protected function _initRoutes()
	{
		$this->bootstrap('FrontController');
		$frontController = $this->getResource('FrontController');
		$modules = $frontController->getControllerDirectory();

		// Getting router object
		$router = $frontController->getRouter();

		// Retrieves each routes definition file for all modules and add them to the router
		foreach(array_keys($modules) as $module) {
			$routesDirectory = APPLICATION_PATH . DS . 'modules' . DS . $module . DS . 'config' . DS . 'routes';
			if (!is_dir($routesDirectory)) continue;
			$directoryIterator = new DirectoryIterator($routesDirectory);
			foreach ($directoryIterator as $file) {
				if ($file->isDot() || $file->isDir()) continue;
				$routesConfigFilesName = $file->getFilename();
				if (preg_match('/^[^a-z]/i', $routesConfigFilesName)) continue;
				$routesConfig = new Zend_Config_Ini($routesDirectory . DS . $routesConfigFilesName, 'routes');
				$router->addConfig($routesConfig, 'routes');
            }
		}

		// Removing default routes
		$router->removeDefaultRoutes();

		return $router;
	}

	/**
	 * Initialize database password by decrypting it
	 * 
	 * @return string Decrypted database password
	 */
	protected function _initDbPassword()
	{
		$config = Zend_Registry::get('config');
		$filter = new iMSCP_Filter_Encrypt_McryptBase64($config->encryption);
		$decryptedPassword = $filter->decrypt($config->resources->doctrine->params->password);
		$config->resources->doctrine->params->password = $decryptedPassword;

		return $decryptedPassword;
	}

	/**
	 * Initialize loaders for modules resource classes
	 *
	 * @return Zend_Loader_Autoloader
	 * @todo Create our own Autoloader since we not need all default resource types for modules resource classes
	 */
	protected function _initAutoloader() {

		$this->bootstrap('FrontController');
		$frontController = $this->getResource('FrontController');

		$modules = $frontController->getControllerDirectory();
		$default = $frontController->getDefaultModule();

		foreach(array_keys($modules) as $module) {
			if ($module === $default) continue;
			$moduleloader = new Zend_Application_Module_Autoloader(array(
				'namespace' => $module,
				'basePath'  => $front->getModuleDirectory($module))
			);
		}

		return Zend_Loader_Autoloader::getInstance();
	}
}
