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
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * @category	ispCP
 * @package		ispCP
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 */

/**
 * This is the primarly file that should be included in all the IspCP's user
 * levels scripts such as all scripts that live under gui/{admin,reseller,client}
 */
// Set default error reporting level
error_reporting(E_ALL|E_STRICT);

// Should be set to 1 only for development
ini_set('display_errors', 0);

// Define path for the ispCP include directory
define('INCLUDEPATH', dirname(__FILE__));

/**
 * Autoloading classes
 *
 * @todo switch to POO
 */
require_once INCLUDEPATH . '/ispcp-loader.php';
spl_autoload_register('autoload_class');

/**
 * Exception Handler for uncaught exceptions
 *
 * Set the exception handler for uncaught exceptions and register it in the
 * registry for shared access.
 *
 * Anothers optional writers will be attached to this object during
 * intialization process.
 */
ispCP_Registry::setAlias(
	'ExceptionHandler',
	ispCP_Exception_Handler::getInstance()->setHandler()
);

/**
 * Attach the primary writer to write uncaught exceptions messages to
 * the client browser.
 *
 * The writer writes all ispCP_Exception messages to the client browser. In
 * production, all messages sare replaced by a specific message to avoid
 * revealing important information about the ispCP application environment if
 * the user is not an administrator.
 */
ispCP_Registry::get('ExceptionHandler')->attach(
	new ispCP_Exception_Writer_Browser(
		// hardcoded here but will be improved later
		'themes/omega_original/system-message.tpl'
	)
);

/**
 * Encryption data
 */
require_once INCLUDEPATH . '/ispcp-db-keys.php';

if($ispcp_db_pass_key != '{KEY}' && $ispcp_db_pass_iv != '{IV}') {
	ispCP_Registry::set('MCRYPT_KEY', $ispcp_db_pass_key);
	ispCP_Registry::set('MCRYPT_IV', $ispcp_db_pass_iv);
} else {
	throw new ispCP_Exception(
		'Error: Database key and/or initialization vector was not genetated!'
	);

}

/**
 * Include ispCP common functions
 */
require_once INCLUDEPATH . '/ispcp-functions.php';
require_once INCLUDEPATH . '/deprecated.php';

/**
 * Bootstrap the IspCP environment, and default configuration
 *
 * @see {@link IspCP_Bootstrap} class
 * @see {@link IspCP_Initializer} class
 */
require_once INCLUDEPATH . '/environment.php';

/**
 * Internationalisation functions
 */
require_once INCLUDEPATH . '/i18n.php';

/**
 * system message functions
 * @deprecated Depreacted since 1.0.6 - Will be replaced by ispCP_Exception
 */
require_once INCLUDEPATH . '/system-message.php';

/**
 * Sql convenience functions
 */
require_once 'sql.php';

/**
 * Authentication libraries
 */
require_once 'login-functions.php';
require_once 'login.php';

/**
 * User level functions
 *
 * @todo: Must be refactored to be able to load only files that are needed
 */
require_once 'admin-functions.php';
require_once 'reseller-functions.php';
require_once 'client-functions.php';

/**
 * Some others shared libraries
 */
require_once 'date-functions.php';
require_once 'input-checks.php';
require_once 'calc-functions.php';
require_once 'net_idna/idna_convert.class.php';
require_once 'lostpassword-functions.php';
require_once 'emailtpl-functions.php';
require_once 'layout-functions.php';
require_once 'functions.ticket_system.php';
require_once 'debug.php';
