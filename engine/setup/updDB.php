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
 * The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * @category    ispCP
 * @package     ispCP_Setup
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @author      Laurent Declercq <laurent.declercq@ispcp.net>
 * @version     SVN: $Id$
 * @link        http://isp-control.net ispCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

// GUI root directory absolute path
$gui_root_dir = '{GUI_ROOT_DIR}';

if(preg_match('/^\{GUI_ROOT_DIR\}$/', $gui_root_dir)) {
	print 'Error: The gui root directory is not defined in the ' . __FILE__ .
		" file!\n";

	exit(1);
}

try {
	// Include ispCP core libraries and initialize the environment
	require_once $gui_root_dir . '/include/ispcp-lib.php';

	// Gets an ispCP_Update_Database instance
	$dbUpdate = ispCP_Update_Database::getInstance();

	if(!$dbUpdate->executeUpdates()) {
		print "\n[ERROR]: " .$dbUpdate->getErrorMessage() . "\n\n";

		exit(1);
	}

} catch(Exception $e) {

	$message = "\n[ERROR]: " . $e->getMessage() . "\n\nStackTrace:\n" .
		$e->getTraceAsString() . "\n\n";

	print "$message\n\n";

	exit(1);
}

print "\n[INFO]: ispCP database update succeeded!\n\n";

exit(0);
