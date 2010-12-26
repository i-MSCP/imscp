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

/**
 * Plugin resource that initialize the view
 *
 * @author Laurent Declercq <laurent.declercq@nuxwin.com>
 * @version 1.0.0
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

		// Set doctype
		$view->doctype('XHTML1_TRANSITIONAL');

		$view->headTitle('i-MSCP - internet Multi Server Control Panel');

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

        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        return $view;
	}
}

