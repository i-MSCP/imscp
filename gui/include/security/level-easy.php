<?php
/************************************************************************
 * IP-Filter v0.5                                     Start: 10/20/2006 *
 * ==============                               Last change: 10/20/2006 *
 *                                                                      *
 * -------------------------------------------------------------------- *
 * File              : level-easy.php                                   *
 * -------------------------------------------------------------------- *
 * Short description : Blocks spoofed IP numbers or invalid hostnames   *
 * -------------------------------------------------------------------- *
 *                                                                      *
 * -------------------------------------------------------------------- *
 * Copyright (c) 2006 by Roland Haeder                                  *
 * For more information visit: http://blog.mxchange.org                 *
 *                                                                      *
 * This program is free software. You can redistribute it and/or modify *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2 of the License.       *
 *                                                                      *
 * This program is distributed in the hope that it will be useful,      *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        *
 * GNU General Public License for more details.                         *
 *                                                                      *
 * You should have received a copy of the GNU General Public License    *
 * along with this program; if not, write to the Free Software          *
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               *
 * MA  02110-1301  USA                                                  *
 ************************************************************************/

// Check selected security level
if ($cfg['SECURITY_LEVEL'] == "none") {
	// Skip this test (bad bad bad...)
	return;
}

// Strip tags and secure special characters including single-quotes (')
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

// Validate all array parts
$isValid = true;
foreach ($ipCheck as $var) {
	// Check if valid...                                   hostname                                                                                                      ip-number
	$isValid = ((preg_match("/(?=^.{1,254}$)(^(?:(?!\d+\.)[a-zA-Z0-9_\-]{1,63}\.?)+(?:[a-zA-Z]{2,})$)/", $_SERVER[$var]) || preg_match("/^(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$/", $_SERVER[$var])) && $isValid);
}

if (!$isValid) {
	// Invalid hostname (by syntax)
	ipfilter_send(4);
	ipfilter_die();
} else {
	// Initialization
	$ip = "0.0.0.0";

	// Trim (maybe?) included spaces from user agent
	$user = trim($_SERVER['HTTP_USER_AGENT']);

	// Check the syntax of SERVER_NAME
	if (preg_match("/(?=^.{1,254}$)(^(?:(?!\d+\.)[a-zA-Z0-9_\-]{1,63}\.?)+(?:[a-zA-Z]{2,})$)/", $_SERVER['SERVER_NAME'])) {
		// Get IP from hostname
		$ip = gethostbyname($_SERVER['SERVER_NAME']);
	}

	// Reject non-solvable addresses and when IP is different to ours or spoofed or server accesses itself
	// Also reject empty user-agent strings
	if ((($ip == $_SERVER['SERVER_NAME']) && ($ip != $_SERVER['SERVER_ADDR']) && ($ip != "0.0.0.0") && ($_SERVER['SERVER_NAME'] != "0.0.0.0")) || (empty($user))) {
		// No resolvable hostname or see above...
		if (!$whiteListed) {
			if (empty($user) || !isset($user)) {
				// Empty user-agent
				ipfilter_send(11);
				ipfilter_die();
			} else {
				// Hostname was not resolvable
				ipfilter_send(5);
				ipfilter_die();
			}
		}
	} else {
		// Probe remote host (spoofing!)
		$remote_host = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
		if ((empty($remote_host)) || ($remote_host == $_SERVER['REMOTE_ADDR'])) {
			// Remote IP is (maybe?) spoofed!
			ipfilter_send(6);
			/******* This has blocked also some "legal attempts" made by normal surfers even like me *******/
			//ipfilter_die();
		} else {
			//Verify remote's IP number
			$remote_ip = @gethostbyname($remote_host);
			if ($remote_ip != $_SERVER['REMOTE_ADDR']) {
				// Not matching!
				/********** This has blocked too much IPs inluding my own out... :-( *********/
				ipfilter_send(7);
				//ipfilter_die();
			}
		}
	}
}

//
?>
