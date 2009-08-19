<?php

//   -------------------------------------------------------------------------------
//  |                  net2ftp: a web based FTP client                              |
//  |              Copyright (c) 2003-2009 by David Gartner                         |
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the GNU General Public License                   |
//  | as published by the Free Software Foundation; either version 2                |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//   -------------------------------------------------------------------------------





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function timer() {

// --------------
// This function calculates the time between starttime and endtime in milliseconds
// --------------

	global $net2ftp_globals;

	list($start_usec, $start_sec) = explode(' ', $net2ftp_globals["starttime"]);
	$starttime  = ((float)$start_usec + (float)$start_sec);
	list($end_usec, $end_sec) = explode(' ', $net2ftp_globals["endtime"]);
	$endtime    = ((float)$end_usec + (float)$end_sec);
	$time_taken = ($endtime - $starttime);   // to convert from microsec to sec
	$time_taken = number_format($time_taken, 2);     // optional

	return $time_taken;

} // End function timer

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function mytime() {

	$datetime = date("Y-m-d H:i:s");
	return $datetime;
}

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function mytime_short() {

	$datetime = date("H:i");
	return $datetime;
}

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function getBrowser($what) {

// --------------
// This function returns the browser name, version and platform using the http_user_agent string
// --------------

// Original code comes from http://www.phpbuilder.com/columns/tim20000821.php3?print_mode=1
// Written by Tim Perdue, and released under the GPL license
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: tim20000821.php3,v 1.2 2001/05/22 19:22:47 tim Exp $


// -------------------------------------------------------------------------
// If no information is available, return ""
// -------------------------------------------------------------------------
	if (isset($_SERVER["HTTP_USER_AGENT"]) == false) { return ""; }

// -------------------------------------------------------------------------
// Remove XSS code
// -------------------------------------------------------------------------
	$http_user_agent = validateGenericInput($_SERVER["HTTP_USER_AGENT"]);

// -------------------------------------------------------------------------
// Determine browser and version
// -------------------------------------------------------------------------
	if ($what == "version" || $what == "agent") {

// !!! If a new browser is added, add is also in the plugin properties
// Else, functionality will be broken when loading the plugin in printTextareaSelect().

		if (ereg('MSIE ([0-9].[0-9]{1,2})', $http_user_agent, $regs)) {
			$BROWSER_VERSION = $regs[1];
			$BROWSER_AGENT = 'IE';
		}
		elseif (ereg('Safari/([0-9].[0-9]{1,2})', $http_user_agent, $regs)) {
			$BROWSER_VERSION = $regs[1];
			$BROWSER_AGENT = 'Safari';
		}
		elseif (ereg('Opera ([0-9].[0-9]{1,2})', $http_user_agent, $regs)) {
			$BROWSER_VERSION = $regs[1];
			$BROWSER_AGENT = 'Opera';
		}
		elseif (ereg('Mozilla/([0-9].[0-9]{1,2})', $http_user_agent, $regs)) {
			$BROWSER_VERSION = $regs[1];
			$BROWSER_AGENT = 'Mozilla';
		}
		else {
			$BROWSER_VERSION = 0;
			$BROWSER_AGENT = 'Other';
		}

		if ($what == "version") { return $BROWSER_VERSION; }
		elseif ($what == "agent")   { return $BROWSER_AGENT; }

	} // end if

// -------------------------------------------------------------------------
// Determine platform
// -------------------------------------------------------------------------

	elseif ($what == "platform") {

		if (	strstr($http_user_agent, 'BlackBerry') || 
			strstr($http_user_agent, 'DoCoMo') || 
			strstr($http_user_agent, 'Nokia') || 
			strstr($http_user_agent, 'Palm') || 
			strstr($http_user_agent, 'SonyEricsson') || 
			strstr($http_user_agent, 'SymbianOS') || 
			strstr($http_user_agent, 'Windows CE')) {
			$BROWSER_PLATFORM = 'Mobile';
		}
		elseif (strstr($http_user_agent, 'iPhone') || strstr($http_user_agent, 'iPod')) {
			$BROWSER_PLATFORM = 'iPhone';
		}
		elseif (strstr($http_user_agent, 'Win')) {
			$BROWSER_PLATFORM = 'Win';
		}
		else if (strstr($http_user_agent, 'Mac')) {
			$BROWSER_PLATFORM = 'Mac';
		}
		else if (strstr($http_user_agent, 'Linux')) {
			$BROWSER_PLATFORM = 'Linux';
		}
		else if (strstr($http_user_agent, 'Unix')) {
			$BROWSER_PLATFORM = 'Unix';
		}
		else {
			$BROWSER_PLATFORM = 'Other';
		}

		return $BROWSER_PLATFORM;

	} // end if elseif

} // End function getBrowser

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>