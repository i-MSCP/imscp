<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware		            		|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------

/**************************************
*                                     *
* 		Included for Testing !!!!     *
*                                     *
***************************************/

// Function: Shall keep requests containing invalid IP number or invalid hostname away
//           from the script. Shall prevent attacks on $_SERVER['PHP_SELF']
//
// Problems: Yahoo and MSN bot are also sometimes keept out (or do they always use valid
//           and reverse-solveable IP numbers?)

// Validate hostname (see regexlib.com for details)
if (!preg_match("/^([a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]\.)+([a-zA-Z]{2,6})$/", $_SERVER['SERVER_NAME']) AND
    !preg_match("/^(([0-2]*[0-9]*[0-9]+)\.([0-2]*[0-9]*[0-9]+)\.([0-2]*[0-9]*[0-9]+)\.([0-2]*[0-9]*[0-9]+))$/", $_SERVER['SERVER_NAME'])) {
	// Invalid hostname (by syntax)
	header("HTTP/1.1 403 Forbidden");
	die();
}
else {
	// Resolve IP address
	$ip = "0.0.0.0";
	if (preg_match("/^([a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]\.)+([a-zA-Z]{2,6})$/", $_SERVER['SERVER_NAME'])) {
		$ip = @gethostbyname($_SERVER['SERVER_NAME']);
	}

	// Trim possible included spaces from user agent
	$user = trim($_SERVER['HTTP_USER_AGENT']);

	// Reject non-solvable addresses and when IP is different to ours or spoofed or server accesses itself
	// Also reject empty user-agent strings
	if (($ip == $_SERVER['SERVER_NAME']) OR (empty($user)) OR
		(($ip != $_SERVER['SERVER_ADDR']) AND ($ip != "0.0.0.0") AND ($_SERVER['SERVER_NAME'] != "0.0.0.0"))) {
		// No resolvable hostname or see above...
		header("HTTP/1.1 403 Forbidden");
		die();
	}
	else {
		// Probe remote host (spoofing!)
		$remote_host = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
		if ((empty($remote_host)) OR ($remote_host == $_SERVER['REMOTE_ADDR'])) {
			// Remote IP is maybe spoofed!
			header("HTTP/1.1 403 Forbidden");
			die();
		}
		else {
			// Verify remote's IP number
			$remote_ip = @gethostbyname($remote_host);
			if ($remote_ip != $_SERVER['REMOTE_ADDR'])
			{
				// Not matching!
				header("HTTP/1.1 403 Forbidden");
				die();
			}
		}
	}
}

// At last secure the $_SERVER['PHP_SELF'] element
$_SERVER['PHP_SELF'] = htmlentities(strip_tags($_SERVER['PHP_SELF']), ENT_QUOTES);

// Split it up into path and filename
$SELF_DIR  = dirname($_SERVER['PHP_SELF']);
$SELF_FILE = basename($_SERVER['PHP_SELF']);

// Check for a .php inside the $SELF_DIR...
while (ereg(".php", $SELF_DIR)) {
	// Correct the dirname
	$SELF_DIR = substr($SELF_DIR, 0, (strpos($SELF_DIR, ".php") + 4));
	// Rewrite filename...
	$SELF_FILE = basename($SELF_DIR);
	// ... and dirname
	$SELF_DIR = dirname($SELF_DIR);
}

// Put both together again and let's pray it is secured now...
$_SERVER['PHP_SELF'] = $SELF_DIR."/".$SELF_FILE;

// Remove uneccessary variables
unset($SELF_DIR);
unset($SELF_FILE);

?>
