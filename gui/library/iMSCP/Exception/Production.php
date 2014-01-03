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
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Exception
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2014 by i-MSCP | http://i-mscp.net
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * @see iMSCP_Exception
 */
require_once  'iMSCP/Exception.php';

/**
 * Exception used on production by iMSCP_Exception_Handler
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Exception
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.2
 */
class iMSCP_Exception_Production extends iMSCP_Exception
{
	/**
	 * Constructor
	 *
	 * @param string $message
	 * @param int $code
	 * @return iMSCP_Exception_Production
	 */
	public function __construct($message = '', $code = 0)
	{

		if ($message == '') {
			if (function_exists('tr') && iMSCP_Registry::isRegistered('Pdo')) {
				$message = tr('An error occurred. Please contact your administrator.');
			} else {
				$message = 'An error occurred. Please contact your administrator.';
			}
		}

		parent::__construct($message, $code);
	}
}
