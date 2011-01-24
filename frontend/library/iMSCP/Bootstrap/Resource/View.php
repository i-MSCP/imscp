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
 * View plugin resource that initialize the view
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
class iMSCP_Bootstrap_Resource_View extends Zend_Application_Resource_ResourceAbstract {

	/**
	 * Initialize and register the view
	 *
	 * @return Zend_View
	 */
	public function init() {

		return $this->getView();
	}

	/**
	 * Get view
	 * 
	 * @return Zend_View
	 */
	public function getView() {

		// Create view
		$view = new Zend_View($this->getOptions());

		// Get application
		$application = $this->getBootstrap()->getApplication();

		// Product information
		$view->productShortName = $application->getOption('name');
		$view->productLongName = "internet Multi Server Control Panel";
		$view->productCodeName = $application->getOption('codename');
		$view->productVersion = $application->getOption('version');;
		$view->productBuild = $application->getOption('build');
		$view->productCopyright = '&copy; Copyright 2010 - 2011 i-MSCP Team<br/>All Rights Reserved';

		$view->doctype('XHTML1_STRICT');
		$view->headTitle($view->productShortName . ' - ' . $view->productLongName . ' - ');

		// Define common Meta
		$view->headMeta()->appendName('robots', 'nofollow, noindex')
			->appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8')
			->appendHttpEquiv('X-UA-Compatible', 'IE=8')
			->appendHttpEquiv('Content-Style-Type', 'text/css')
			->appendHttpEquiv('Content-Script-Type', 'text/javascript');

		// Define favicon
		$view->headLink(array('rel' => 'favicon', 'href' => '/favicon.ico'));

		// Define common js scripts
		$view->headScript()->appendFile(
			'/themes/default/js/DD_belatedPNG_0.0.8a-min.js', 'text/javascript', array('conditional' => 'IE 6')
		) ->appendScript("DD_belatedPNG.fix('*');", 'text/javascript', array('conditional' => 'IE 6'));

		// Activate jQuery
		$view->addHelperPath('ZendX/JQuery/View/Helper', 'ZendX_JQuery_View_Helper');

		/**
		 * @var $jquery ZendX_JQuery_View_Helper
		 */
		$jquery = $view->jQuery();

		// Set jquery core and ui versions to be used
		// See http://code.google.com/intl/fr/apis/libraries/devguide.html#jquery for available versions
		$jquery->setVersion('1.4.4');
		$jquery->setUiVersion('1.8.8');
		$jquery->addStyleSheet('https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.8/themes/smoothness/jquery-ui.css');

		// Will enable both jquery (core) and jquery (UI)
		$jquery->uiEnable();
		
		// For convenience reason, we use our own copy of jQuery library
		// $rmode = ZendX_JQuery::RENDER_JQUERY_ON_LOAD | ZendX_JQuery::RENDER_SOURCES;
		// $jquery->setRenderMode($rmode);
		// allow reusability by any other theme
		// $jquery->addJavascriptFile('/themes/common/js/jQuery/jquery.js');

        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
		// Registering the view object as default view
		$viewRenderer->setView($view);

        return $view;
	}
}
