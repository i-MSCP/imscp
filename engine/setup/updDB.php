<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
 * Copyright (C) 2010-2013 by internet Multi Server Control Panel - http://i-mscp.net
 *
 * Author:  Laurent Declercq <l.declercq@nuxwin.com>
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
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2013 by
 * internet Multi Server Control Panel. All Rights Reserved.
 *
 * The i-MSCP Home Page is:
 *
 *    http://i-mscp.net
 */

// GUI root directory absolute path
$guiRootDir = '{GUI_ROOT_DIR}';

if(strpos($guiRootDir, 'GUI_ROOT_DIR') !== false) {
	fwrite(STDERR, '[ERROR] gui root directory is not defined at ' . __FILE__ . ' line ' . __LINE__ . "\n");
	exit(1);
}

// Sets include path
set_include_path('.' . PATH_SEPARATOR . $guiRootDir . '/library');

// Include core library
require_once 'imscp-lib.php';

try {
	$databaseUpdate = iMSCP_Update_Database::getInstance();

	if(!$databaseUpdate->applyUpdates()) {
		fwrite(STDERR, "[ERROR] Database update failed: " . $databaseUpdate->getError() . "\n");
		exit(1);
	}
} catch(Exception $e) {
	fwrite(STDERR, "[ERROR] " . $e->getMessage() . "\n\nStackTrace:\n" . $e->getTraceAsString() . "\n");
	exit(1);
}

fwrite(STDOUT, "[INFO] i-MSCP database has been successfully updated\n");
exit;
