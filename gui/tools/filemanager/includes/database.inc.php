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

function connect2db() {

// --------------
// This function logs user accesses to the site
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings;

	$mydb = mysql_connect($net2ftp_settings["dbserver"], $net2ftp_settings["dbusername"], $net2ftp_settings["dbpassword"]);
	if ($mydb == false) { 
		setErrorVars(false, __("Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."), debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

	$result2 = mysql_select_db($net2ftp_settings["dbname"]);
	if ($result2 == false) { 
		setErrorVars(false, __("Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."), debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

	return $mydb;

} // End connect2db

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>