<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * Copyright (C) 2010 by internet Multi Server Control Panel - http://i-mscp.net
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 * The Original Code is "i-MSCP - internet Multi Server Control Panel".
 *
 * The Initial Developer of the Original Code is i-MSCP Team.
 * Portions created by Initial Developer are Copyright (C) 2010 by
 * internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @copyright   2010 by i-MSCP | http://i-mscp.net
 * @author      Zend Tools
 * @author      i-MSCP Team
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/ GPL v2
 */

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

	/**
	 * initialize the html Doctype to be used in all views
	 *
	 * @return void
	 */
	protected function _initDoctype() {

		$this->bootstrap('view');
		$view = $this->getResource('view');
		$view->doctype('XHTML1_TRANSITIONAL');
	}

	/**
	 * Initialize the main and left menus
	 *
	 * @return void
	 * @todo per module menu
	 */
	protected function _initMenus()    {

        $view = $this->bootstrap('layout')->getResource('layout')->getView();
        $view->mainMenu = new Zend_Navigation(include(APPLICATION_PATH . '/configs/menus/main_admin.php'));
        //$view->leftMenu = new Zend_Navigation(include(APPLICATION_PATH . '/configs/menus/left_admin.php'));
	}
}
