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
 * @package     iMSCP_Controller
 * @subpackage  Plugin
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Plugin to schedule controller's actions defined for a specific route
 *
 * This plugin allows to schedule execution of a stack of actions controllers, defined for the current route. Each
 * action must be defined like this in the route configuration:
 *
 * Example:
 * <code>
 *  routes.hosting_services_customers_create.type = "Zend_Controller_Router_Route"
 *  routes.hosting_services_customers_create.route = "admin/hostingServices/customer/create"
 *  routes.hosting_services_customers_create.defaults.controller = "user"
 *  routes.hosting_services_customers_create.defaults.action = "create"
 *  ; We add the 'create' action from the 'http' controller which belong to the 'domain' module
 *  routes.hosting_services_customers_create.defaults.actionStack[] = "create:http:domain"
 * </code>
 *
 * In this example, we schedule the execution of action 'create' of the 'http' controller which belongs to the 'domain'
 * module. This action will be automatically executed after the default 'create' action from the user controller which
 * belongs to the default (core) module. This way to work allows to perform several actions from several modules without
 * create a direct dependency between each of them.
 *
 * @category    iMSCP
 * @package     iMSCP_Controller
 * @subpackage  Plugin
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @since       1.0.0
 * @version     1.0.0
 */
class iMSCP_Controller_Plugin_ActionStack extends Zend_Controller_Plugin_ActionStack
{
	/**
	 * @var string Initial controller name
	 */
	private $_controllerName;

	/**
	 * @var string Initial module name
	 */
	private $_moduleName;

	/**
	 * Schedule actions for current route
	 *
	 * @param Zend_Controller_Request_Abstract $request
	 * @return Zend_Controller_Request_Abstract
	 */
	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request) {
		$frontController = Zend_Controller_Front::getInstance();

		try {
			$currentRoute = $frontController->getRouter()->getCurrentRoute();
		} catch(Zend_Exception $e) {
			return;
		}

		$defaults = $currentRoute->getDefaults();

		if(isset($defaults['actionStack'])) {
			$defaults['actionStack'] = (array) $defaults['actionStack'];

			$request = $this->getRequest();
			$this->_controllerName = $request->getControllerName();
			$this->_moduleName = $request->getModuleName();

			foreach($defaults['actionStack'] as $action) {
				list($action, $controller, $module) = $this->_getOptions($request, $action);
				$this->pushStack(
					new Zend_Controller_Request_Simple($action, $controller, $module, $request->getParams())
				);
			}
		}

		return $request;
	}

	/**
	 * Get options
	 *
	 * @throws Zend_Exception
	 * @param  Zend_Controller_Request_Abstract $request
	 * @param  string $action
	 * @return array
	 */
	protected function _getOptions($request, $action) {
		$options = explode(':', $action);
		$count = sizeof($options);

		if($count == 0) {
			throw new Zend_Exception('Error: The actionStack plugin requires a least an action name!');
		} elseif($count == 1) {
			$options[] = $this->_controllerName;
			$options[] = $this->_moduleName;
		} elseif($count == 2) {
			$options[] = $this->_moduleName;
		}

		return $options;
	}
}
