<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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

/**
 * Class versionUpdate is
 * Implementing abstract class ispcpUpdate for future online version update functions
 *
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		1.0
 * @see			Other Functions (in other Files)
 * @since		r1355
 */
class versionUpdate extends ispcpUpdate {

	/**
	 * Database variable name for the update version
	 * @var string
	 */
	protected $databaseVariableName = "VERSION_UPDATE";

	/**
	 * @todo Please descibe this variable!
	 */
	protected $errorMessage = "Version update %s failed";

	/**
	 * @todo Please descibe this method!
	 */
	public static function getInstance() {
		static $instance = null;
		if ($instance === null) $instance = new self();

		return $instance;
	}

	/**
	 * @todo Please descibe this method!
	 */
	protected function getCurrentVersion() {
		return (int)Config::getInstance()->get('BuildDate');
	}

	/**
	 * @todo Please descibe this method!
	 */
	protected function getNextVersion() {
		$last_update = "http://www.isp-control.net/latest.txt";
		ini_set('user_agent', 'Mozilla/5.0');
		$timeout = 2;
		$old_timeout = ini_set('default_socket_timeout', $timeout);
		$dh2 = @fopen($last_update, 'r');
		ini_set('default_socket_timeout', $old_timeout);

		if (!is_resource($dh2)) {
			$this->addErrorMessage(tr("Couldn't check for updates! Website not reachable."));
			return false;
		}

		$last_update_result = (int)fread($dh2, 8);
		fclose($dh2);

		return $last_update_result;
	}

	/**
	 * @todo Please descibe this method!
	 */
	public function checkUpdateExists() {
		return ($this->getNextVersion()>$this->currentVersion) ? true : false;
	}

	/**
	 * @todo Please descibe this method!
	 */
	protected function returnFunctionName($version) {
		return "dummyFunctionThatAllwaysExists";
	}

	/**
	 * @todo Please descibe this method!
	 */
	protected function dummyFunctionThatAllwaysExists(&$engine_run_request) {
		// uncomment when engine part will be ready
		/*
		setConfig_Value('VERSION_UPDATE', $this->getNextVersion());
		$engine_run_request = true;
		*/
	}
}
