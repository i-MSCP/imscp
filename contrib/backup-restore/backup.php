<?php
/**
 * ispCP ω (OMEGA) complete domain backup/restore tool
 * Restore application
 *
 * @copyright 	2010 Thomas Wacker
 * @author 		Thomas Wacker <zuhause@thomaswacker.de>
 * @version 	SVN: $Id$
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
 */

require_once dirname(__FILE__).'/includes/boot.php';
require_once dirname(__FILE__).'/includes/BackupPackage_ispCP.php';

if ($argc < 3) {
	echo "Usage: php backup.php [OPTIONS] domain archive-password\n";
	echo "OPTIONS:\n";
	echo " -v n ......... verbose mode 0..3 (0 = only errors, 1 = +warnings,\n";
	echo "                                   2 = +informations, 3 = +debug)\n";
	echo "\n";
	echo "Please ensure, there is enough free disk space available for\n";
	echo "this operation (approx. triple size of htdocs and databases)!\n";
	exit(1);
}

$log_level = 0;
for ($i = 1; $i < $argc-1; $i++) {
	if ($argv[$i] == '-v') {
		$log_level = $argv[++$i];
	}
}

$domain_name = $argv[$argc-2];
$password = $argv[$argc-1];

$exitcode = 0;

// start packager for domain
$handler = new BackupPackage_ispCP($domain_name, $password, $log_level);
if ($handler->runPackager() == false) {
	echo "Error executing packager\n";
	$exitcode = 9;
}

exit($exitcode);
