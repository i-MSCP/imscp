<?php
/************************************************************************
 * IP-Filter v0.5                                     Start: 05/01/2006 *
 * ==============                               Last change: 10/18/2006 *
 *                                                                      *
 * -------------------------------------------------------------------- *
 * File              : ipfilter.php                                     *
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
if ($cfg['SECURITY_LEVEL'] != "paranoid") {
	// Abort on any other levels
	return;
}

// Check if IP is whitelisted
$whiteListed = (in_array($_SERVER['REMOTE_ADDR'], explode(",", $cfg['SEC_IP_WHITELIST'])));

// Check if SERVER_NAME does not exist in HTTP_VIA
if (isset($_SERVER['HTTP_VIA']) && $filterPassed && !empty($_SERVER['HTTP_VIA']) && !empty($_SERVER['SERVER_NAME'])) {
	// Make all lower-case cos some "tricky" spammer may use upper/lower-case in hostname
	$via = strtolower(trim($_SERVER['HTTP_VIA']));
	$name = strtolower(trim($_SERVER['SERVER_NAME']));
	if (strpos($via, $name) !== false) {
		// HTTP_VIA and SERVER_NAME cannot be the same!
		ipfilter_send(1);
		ipfilter_die();
	}
} elseif ($filterPassed && ($_SERVER['HTTP_HOST'] != $_SERVER['SERVER_ADDR']) && (preg_match("/^(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$/", $_SERVER['HTTP_HOST']))) {
	// Wrong IP number in SERVER_HOST found (did not filter wrong hostnames ("virtual domains")
	ipfilter_send(2);
	ipfilter_die();
} elseif (!$filterPassed) {
	// The tags filter found some bad things
	ipfilter_send(3);
	ipfilter_die();
} elseif (!isOwnDomain() && $_SERVER['REMOTE_ADDR'] != $cfg['BASE_SERVER_IP']) {
	// Is not listed in domain list
	if (!in_array($_SERVER['SERVER_NAME'], explode(": ", $cfg['SEC_DOMAIN_NO_EMAIL']))) {
		// Send mail when missing domain is not ignored
		ipfilter_send(9);
	}
	// ... but block the request anyway
	ipfilter_die();
} elseif (((empty($_SERVER['HTTP_ACCEPT'])) || (!isset($_SERVER['HTTP_ACCEPT']))) && (!$whiteListed)) {
	// no HTTP_ACCEPT and not whitelisted
	ipfilter_send(8);
	ipfilter_die();
}

//
?>
