<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		Laurent Declercq <l.declercq@nuxwin.com>
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
 * The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 */

error_reporting(E_ALL|E_STRICT);

// GUI root directory absolute path
$gui_root_dir = '{GUI_ROOT_DIR}';

// Include all needed files
require_once $gui_root_dir . '/include/ispcp-lib.php';

// Perfom all database critical updates if exists
if(criticalUpdate::getInstance()->checkUpdateExists()) {

	criticalUpdate::getInstance()->executeUpdates();

	if( ($msg = criticalUpdate::getInstance()->getErrorMessage()) != '') {
		print $msg;
		exit(1);
	}

}

# Perform all database normal updates if exists
if(databaseUpdate::getInstance()->checkUpdateExists()) {

	databaseUpdate::getInstance()->executeUpdates();

	if( ($msg = databaseUpdate::getInstance()->getErrorMessage() != '')) {
		print $msg;
		exit(1);
	}
}

exit(0);
?>
