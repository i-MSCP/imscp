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
require_once dirname(__FILE__).'/includes/RestorePackage_iMSCP.php';

if ($argc < 3) {
	echo "Usage: php restore.php [OPTIONS] domain archive-password\n";
	echo "OPTIONS:\n";
	echo " -i IP ........ use IP for domain (default = first found)\n";
	echo " -r RES ....... use reseller for domain (default = first found)\n";
	echo " -v n ......... verbose mode 0..3 (0 = only errors, 1 = +warnings,\n";
	echo "                                   2 = +informations, 3 = +debug)\n";
	echo "\n";
	echo "Please ensure, there is enough free disk space available for\n";
	echo "this operation (approx. triple size of htdocs and databases)!\n";
	exit(1);
}

$log_level = 0;
$option_ip = $option_res = false;
for ($i = 1; $i < $argc-1; $i++) {
	if ($argv[$i] == '-v') {
		$log_level = $argv[++$i];
	} elseif ($argv[$i] == '-i') {
		$option_ip = $argv[++$i];
	} elseif ($argv[$i] == '-r') {
		$option_res = $argv[++$i];
	}
}

$domain_name = $argv[$argc-2];
$password = $argv[$argc-1];

$exitcode = 0;

// start restore for domain
$handler = new RestorePackage_iMSCP($domain_name, $password, $option_ip, $option_res, $log_level);
if ($handler->runRestore() == false) {
	echo "Error executing restore\n";
	$exitcode = 9;
}

exit($exitcode);
