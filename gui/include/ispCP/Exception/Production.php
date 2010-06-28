<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
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
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * @category	ispCP
 * @package		ispCP_Exception
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @version		SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 * @filesource
 */

/**
 * @see ispCP_Exception
 */
require_once  INCLUDEPATH . '/ispCP/Exception.php';

/**
 * Exception used on production by ispCP_Exception_Handler
 *
 * @category	ispCP
 * @package		ispCP_Exception
 * @author		Laurent declercq <laurent.declercq@ispcp.net>
 * @since		1.0.6
 * @version		1.0.0
 */
class ispCP_Exception_Production extends ispCP_Exception {

	/**
	 * __Constructor
	 * 
	 * @return void
	 */
	public function __construct($message = '', $code = 0) {

		if($message == '') {
			if(function_exists('tr') && ispCP_Registry::isRegistered('Pdo')) {
				$message =
					tr('An error occured! Please, contact your administrator!');
			} else {
				$message =
					'An error occured! Please, contact your administrator!';
			}
		}

		parent::__construct($message, $code);
	}
}
