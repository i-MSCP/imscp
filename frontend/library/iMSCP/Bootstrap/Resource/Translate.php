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
 * Translate plugin resource
 *
 * @category    iMSCP
 * @package     iMSCP_Boostrap
 * @subpackage  Resource
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @since       1.0.0
 * @version     1.0.0
 * @todo: Add manual language selection support
 */
class iMSCP_Bootstrap_Resource_Translate extends Zend_Application_Resource_ResourceAbstract
{
	/**
	 * Initialize translate resource
	 *
	 * @return Zend_Translate
	 */
	public function init() {
		return $this->_getTranslate();
	}

	/**
	 * Get translate object
	 *
	 * @return Zend_Translate
	 */
	protected function _getTranslate() {
		// Retrieve localization (client browser or en_US)
		$locale = $this->_getLanguage();

		// Initialize translator with default localization (en_US)
		$translate = new Zend_Translate(
			'gettext', // We use gettext adapter
			APPLICATION_PATH . DS . 'languages', 'en_US',
			array(
				'scan' => Zend_Translate::LOCALE_FILENAME,
				'disableNotices' => true // Disable notices for unavailable languages
			)
		);

		//$langLocale = isset($session->lang) ? $session->lang : $locale;
		$langLocale = $locale;

		// Hmm...
		$translate->setLocale($langLocale . '_' . strtoupper($langLocale));

		// Adding translate object in registry
		Zend_Registry::set('Zend_Translate', $translate);

		// TODO import localization file for validators
		// Passing translate object to some components
		//Zend_Validate::setDefaultTranslator($translate);
		//Zend_Form::setDefaultTranslator($translate);

		return $translate;
	}

	/**
	 * Get Language (auto detection from client browser)
	 *
	 * @return
	 */
	protected function _getLanguage() {
		$bootstrap = $this->getBootstrap();
		$bootstrap->bootstrap('Locale');

		return $bootstrap->getResource('Locale')->getLanguage();
	}
}
