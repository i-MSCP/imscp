<?php
/************************************************************************
 * IP-Filter v0.5                                     Start: 10/20/2006 *
 * ==============                               Last change: 10/20/2006 *
 *                                                                      *
 * -------------------------------------------------------------------- *
 * File              : core.inc.php                                     *
 * -------------------------------------------------------------------- *
 * Short description : Constants and functions for the security layer   *
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

// Security levels (mixed, we need to sort this from worst to whitelists and ignored IPs)
$secLevels = array(
	0  => "disallowed_method",
	1  => "servername_via_match",
	2  => "server_addr_host_mismatch",
	3  => "xss_csrf_attack",
	4  => "syntax",
	5  => "hostname_unresolvable",
	6  => "spoofed_ip_unblocked",
	7  => "ip_mismatch_unblocked",
	8  => "empty_http_accept",
	9  => "foreign_domain",
	10 => "forbidden_server_protocol",
	11 => "empty_user_agent"
);

// In which order the security levels shall be loaded
$secOrder = array(
	'easy'     => 0,
	'medium'   => 1,
	'hard'     => 2,
	'paranoid' => 3
);

# Convert some strings from config in arrays
$ipCheck = explode(",", $cfg['SEC_VALIDATE_IP_HOSTNAME']);
$tagsFilter = explode(",", $cfg['SEC_FILTER_TAGS']);

// Check if SERVER_NAME is in domain list
function isOwnDomain() {
	global $_SERVER, $cfg;
	// Check for hostname/IP number
	if ($_SERVER['SERVER_NAME'] == $cfg['BASE_SERVER_IP']) {
		// Is direct IP number
		if ($cfg['SEC_ALLOW_IP_ACCESS']) {
			// Pass attempt
			return true;
		} else {
			// Block attempt
			return false;
		}
	}
	if ($_SERVER['SERVER_NAME'] == $cfg['SERVER_HOSTNAME']) {
		// Is direct hostname
		if ($cfg['SEC_ALLOW_HOSTNAME_ACCESS']) {
			// Pass attempt
			return true;
		} else {
			// Block attempt
			return false;
		}
	}

	// First every attempt is invalid...
	$status = false;

	// Open the vhcs2.conf file (open_basedir must allow this!)
	$fp = @fopen($cfg['SERVER_VHOST_FILE'], 'r') or ipfilter_message(sprintf("Cannot read virtual hostname list <strong>%s</strong>.", $cfg['SERVER_VHOST_FILE']));

	// Read until EOF
	while (!feof($fp)) {
		// Read 4096 chars from the file
		$row = fgets($fp, 4096);
		// Is there a ServerName entry? (please improve this!)
		if ( strpos($row, "ServerName") !== false) {
			// ServerName found so let's get the hostname
			$hostname = explode(",", trim($row));
			$hostname = $hostname[1];

			// Is not the default entry
			if ($hostname == $_SERVER['SERVER_NAME']) {
				// Direct match (e.g. subdomain.domain.tld)
				$status = true;
			} elseif (strpos($_SERVER['SERVER_NAME'], $hostname) == 4 && (substr($_SERVER['SERVER_NAME'], 0, 4) == "www.")) {
				// www.domain detected
				$status = true;
			}
		}
	}
	fclose($fp);
	return $status;
}

// Send a detailed email to the given address
function ipfilter_send($level) {
	global $_SERVER, $_POST, $_GET, $cfg, $secLevels;

	if ( (in_array($_SERVER['REMOTE_ADDR'], explode(",", $cfg['SEC_IP_NO_EMAIL'])) && ($level > 5)) || (!$cfg['SECURITY_SEND_EMAIL']) ) {
		// Do not send mail
		return;
	}

	// All elements from _SERVER we want to see in email
	$srvArray = array(
		"HTTP_HOST",
		"HTTP_USER_AGENT",
		"HTTP_ACCEPT",
		"HTTP_ACCEPT_LANGUAGE",
		"HTTP_ACCEPT_ENCODING",
		"HTTP_ACCEPT_CHARSET",
		"HTTP_KEEP_ALIVE",
		"HTTP_COOKIE",
		"HTTP_VIA",
		"HTTP_X_FORWARD_FOR",
		"SERVER_NAME",
		"SERVER_ADDR",
		"REMOTE_ADDR",
		"SCRIPT_FILENAME",
		"SERVER_PROTOCOL",
		"REQUEST_METHOD",
		"REQUEST_URI",
		"PHP_SELF",
		"PATH_TRANSLATED",
	);

	// Add all requested data from _SERVER to the mail
	$DATA = "--- Server data: ---\n";
	foreach ($srvArray as $el) {
		if (isset($_SERVER[$el])) {
			$DATA .= $el." = ".$_SERVER[$el]."\n";
		} else {
			$DATA .= $el." = ???\n";
		}
	}
	// Add all data from _POST and _GET to the mail
	$DATA .= "\n--- POST data: ---\n";
	foreach ($_POST as $el=>$val) {
		$DATA .= $el." = ".$val."\n";
	}
	$DATA .= "\n--- GET data: ---\n";
	foreach ($_GET as $el=>$val) {
		$DATA .= $el." = ".$val."\n";
	}

	// Prepare mail and send it
	$mail_text = "Hello!

A possible attack was detected:

Alert-Level: ".$secLevels[$level]."

----- Begin data -----
".$DATA."
----- End data -----
";

	// Generate subject line
	$subject = sprintf("IP-Filtered: %s on %s: %s", $_SERVER['REMOTE_ADDR'], $_SERVER['SERVER_NAME'], $secLevels[$level]);

	// And send the email...
	mail($cfg['DEFAULT_ADMIN_ADDRESS'], $subject, $mail_text, sprintf("From: %s", $cfg['DEFAULT_ADMIN_ADDRESS']));
}

// Access forbidden with tar pit function
function ipfilter_die ( $sleep = 0 ) {

	// Let pass only numbers
	$sleep = intval($sleep);

	// More than zero seconds?
	if ($sleep > 0) {
		// Bigger than 300 secs is way too much...
		if ($sleep > 300) $sleep = 300; // 5 min. shall be enougth

		// "Endless" execution (pardon, sleep) time
		@set_time_limit(0);

		// Let's sleep a little
		if ($sleep < 10) {
			// Small than 10 secs than only sleep this time
			sleep( $sleep );
		} else {
			// Sleep between 10 and $sleep seconds
			sleep( rand( 10, $sleep ) );
		}
	}

	// Block the attempt and close connection
	header("HTTP/1.1 403 Forbidden");
	header("Connection: close");
	die();
}

// Output messages and die...
function ipfilter_message ($message) {
	set_page_message( tr($message) );
	die(); // Bye, bye... ;-)
}

// Start including all other files
$dirPointer = @opendir(dirname(__FILE__)) or ipfilter_message("Cannot read from securit folder!");

// The array for the include files
$secFiles = array();

// Begin reading the directory by only accepting level-xxxx.php scripts
while ($dir = readdir($dirPointer)) {
	// Generate FQFN (Full Qualified FileName)
	$entry = dirname(__FILE__)."/".$dir;

	// Is it a readable file which begins with "level-" and ends with ".php" ?
	if ( is_file($entry) && is_readable($entry) && (substr($entry, 0, 6) == "level-") && (substr($entry, -4, 4) == ".php") ) {
		// Extract the level part (easy, medium, ...)
		$level = substr($dir, 6, -4);

		// Include this file in the order we setuped it (see second array after header!)
		$secFiles[$secOrder[$level]] = $entry;
	}
}

// Close the directory handler
closedir($dirPointer);

// Sort everthing and load all
ksort($secFiles);
foreach ($secFiles as $inc) {
	require_once($inc);
}

// Remove the array
unset($secFiles);

?>