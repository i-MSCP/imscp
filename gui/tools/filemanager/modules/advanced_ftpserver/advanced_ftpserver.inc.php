<?php

//   -------------------------------------------------------------------------------
//  |                  net2ftp: a web based FTP client                              |
//  |              Copyright (c) 2003-2007 by David Gartner                         |
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

function net2ftp_module_sendHttpHeaders() {

// --------------
// This function sends HTTP headers
// --------------

//	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;
	
} // end net2ftp_sendHttpHeaders

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function net2ftp_module_printJavascript() {

// --------------
// This function prints Javascript code and includes
// --------------

//	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;

// Code
//	echo "<script type=\"text/javascript\"><!--\n";	
//	echo "//--></script>\n";

// Include
//	echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/login/login.js\"></script>\n";

} // end net2ftp_printJavascript

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function net2ftp_module_printCss() {

// --------------
// This function prints CSS code and includes
// --------------

	global $net2ftp_settings, $net2ftp_globals;

// Include
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"". $net2ftp_globals["application_rootdir_url"] . "/skins/" . $net2ftp_globals["skin"] . "/css/main.css.php?ltr=" . __("ltr") . "&amp;image_url=" . urlEncode2($net2ftp_globals["image_url"]) . "\" />\n";

} // end net2ftp_printCssInclude

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function net2ftp_module_printBodyOnload() {

// --------------
// This function prints the <body onload="" actions
// --------------

//	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;
//	echo "";

} // end net2ftp_printBodyOnload

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function net2ftp_module_printBody() {

// --------------
// This function prints the login screen
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result, $net2ftp_output;

	if (isset($_POST["troubleshoot_ftpserver"]) == true)     { $troubleshoot_ftpserver = validateFtpserver($_POST["troubleshoot_ftpserver"]); }
	else                                                     { $troubleshoot_ftpserver = ""; }
	if (isset($_POST["troubleshoot_ftpserverport"]) == true) { $troubleshoot_ftpserverport = validateFtpserverport($_POST["troubleshoot_ftpserverport"]); }
	else                                                     { $troubleshoot_ftpserverport = ""; }
	if (isset($_POST["troubleshoot_username"]) == true)      { $troubleshoot_username = validateUsername($_POST["troubleshoot_username"]); }
	else                                                     { $troubleshoot_username = ""; }
	if (isset($_POST["troubleshoot_password"]) == true)      { $troubleshoot_password = validatePassword($_POST["troubleshoot_password"]); }
	else                                                     { $troubleshoot_password = ""; }
	if (isset($_POST["troubleshoot_directory"]) == true)     { $troubleshoot_directory = validateDirectory($_POST["troubleshoot_directory"]); }
	else                                                     { $troubleshoot_directory = ""; }
	if (isset($_POST["troubleshoot_passivemode"]) == true)   { $troubleshoot_passivemode = validatePassivemode($_POST["troubleshoot_passivemode"]); }
	else                                                     { $troubleshoot_passivemode = ""; }

	$troubleshoot_ftpserver_html     = htmlEncode2($troubleshoot_ftpserver);
	$troubleshoot_ftpserverport_html = htmlEncode2($troubleshoot_ftpserverport);
	$troubleshoot_username_html      = htmlEncode2($troubleshoot_username);
	$troubleshoot_directory_html     = htmlEncode2($troubleshoot_directory);
	$troubleshoot_passivemode_html   = htmlEncode2($troubleshoot_passivemode);

// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Title
	$title = __("Troubleshoot an FTP server");

// Form name
	$formname = "AdvancedForm";


// -------------------------------------------------------------------------
// Variables for screen 1
// -------------------------------------------------------------------------
	if ($net2ftp_globals["screen"] == 1) {

// Next screen
		$nextscreen = 2;

// Back and forward buttons
		$back_onclick = "document.forms['" . $formname . "'].state.value='advanced';document.forms['" . $formname . "'].screen.value='1';document.forms['" . $formname . "'].submit();";
		$forward_onclick = "document.forms['" . $formname . "'].submit();";

	} // end if

// -------------------------------------------------------------------------
// Variables for screen 2
// -------------------------------------------------------------------------
	elseif ($net2ftp_globals["screen"] == 2) {

// Back and forward buttons
		$back_onclick = "document.forms['" . $formname . "'].state.value='advanced_ftpserver'; document.forms['" . $formname . "'].submit();";

// Initial checks
		if ($troubleshoot_passivemode != "yes") { $troubleshoot_passivemode = "no"; }

// Connect
		setStatus(1, 10, __("Connecting to the FTP server"));
		$conn_id = ftp_connect("$troubleshoot_ftpserver", $troubleshoot_ftpserverport);

// Login with username and password
		setStatus(2, 10, __("Logging into the FTP server"));
		$ftp_login_result = ftp_login($conn_id, $troubleshoot_username, $troubleshoot_password);

// Passive mode
		if ($troubleshoot_passivemode == "yes") {
			setStatus(3, 10, __("Setting the passive mode"));
			$ftp_pasv_result = ftp_pasv($conn_id, TRUE);
		}
		else {
			$ftp_pasv_result = true;
		}

// Get the FTP system type
		setStatus(4, 10, __("Getting the FTP system type"));
		$ftp_systype_result = ftp_systype($conn_id);

// Change the directory
		setStatus(5, 10, __("Changing the directory"));
		$ftp_chdir_result = ftp_chdir($conn_id, $troubleshoot_directory);

// Get the current directory from the FTP server
		setStatus(6, 10, __("Getting the current directory"));
		$ftp_pwd_result = ftp_pwd($conn_id);

// Try to get a raw list
		setStatus(7, 10, __("Getting the list of directories and files"));
		$ftp_rawlist_result = ftp_rawlist($conn_id, "-a");
		if (sizeof($ftp_rawlist_result) <= 1) { 
			$ftp_rawlist_result = ftp_rawlist($conn_id, ""); 
		}

// Parse the list
		setStatus(8, 10, __("Parsing the list of directories and files"));
		for($i=0; $i<sizeof($ftp_rawlist_result); $i++) {
			$parsedlist[$i] = ftp_scanline($troubleshoot_directory, $ftp_rawlist_result[$i]);
		} // end for

// Quiting; ftp_quit doesn't return a value
		setStatus(9, 10, __("Logging out of the FTP server"));
		ftp_quit($conn_id);

	} // end if

// -------------------------------------------------------------------------
// Print the output
// -------------------------------------------------------------------------
	setStatus(10, 10, __("Printing the result"));
	require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/manage.template.php");

} // End net2ftp_printBody

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>