<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Bootstrap
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010-2012 by i-MSCP | http://i-mscp.net
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Defines include directory path if needed
 */
defined('LIBRARY_PATH') or define('LIBRARY_PATH', dirname(dirname(__FILE__)));

/**
 * Bootstrap class for i-MSCP
 *
 * This class provide a very small program to boot i-MSCP
 *
 * <b>Note:</b> Will be improved later
 *
 * @category	iMSCP
 * @package		iMSCP_Bootstrap
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @since		1.0.7 (ispCP)
 * @version		1.0.4
 */
class iMSCP_Bootstrap
{
	/**
	 * Boot i-MSCP environment and, configuration
	 *
	 * @throws iMSCP_Exception
	 * @return void
	 */
	public static function boot()
    {
		if(!self::_isBooted()) {
			$boot = new self;
			$boot->_run();
		} else {
			throw new iMSCP_Exception('i-MSCP is already booted.');
		}
	}

	/**
	 * This class implements the Singleton Design Pattern
	 */
	private function __construct() {}

	/**
	 * This class implements the Singleton Design Pattern
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Check if i-MSCP is already booted
	 *
	 * @return boolean TRUE if booted, FALSE otherwise
	 */
	protected static function _isBooted()
    {
		return class_exists('iMSCP_Initializer', false);
	}

	/**
	 * Load the initializer and set the include_path
	 *
	 * @return void
	 */
	protected function _run()
    {
		$this->_loadInitializer();
		iMSCP_Initializer::run('_setIncludePath');
	}

	/**
	 * Load the initializer
	 *
	 * @return void
	 */
	protected function _loadInitializer()
    {
        require LIBRARY_PATH . '/iMSCP/Initializer.php';
	}
}
