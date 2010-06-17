<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 *
 * @license
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
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

defined('INCLUDEPATH') or define('INCLUDEPATH', dirname(dirname(__FILE__)));

/**
 * Bootsrap class for IspCP
 *
 * This class provide a very small program to boot IspCP
 *
 * Note: Will be improved later
 *
 * @since 1.0.6
 * @author Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 */
class IspCP_Bootstrap {

	/**
	 * Boot IspCP environment and, configuration
	 *
	 * @return void
	 */
	public static function boot() {

		if(!self::is_booted()) {
			$boot = new self;
			$boot->run();
		} else {
			system_message('Error: IspCP is already booted!');
		}
	}

	/**
	 * Shouldn't be instanciated directly
	 *
	 * @return void
	 */
	private function __construct() {}

	/**
	 * This class shouldn't be cloned
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Check if IspCP is already booted
	 *
	 * @return TRUE if booted, FALSE othewise
	 */
	private static function is_booted() {

		return class_exists('IspCP_Initializer', false);
	}

	/**
	 * Load the initializer and set the include_path
	 *
	 * @return void
	 */
	private function run() {

		$this->load_initializer();
		IspCP_Initializer::run('set_include_path');
	}

	/**
	 * Load the initializer
	 *
	 * @return void
	 */
	function load_initializer() {

      require INCLUDEPATH . '/IspCP/Initializer.php';
	}
}
