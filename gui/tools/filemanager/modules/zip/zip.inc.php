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

function net2ftp_module_printBodyonload() {

// --------------
// This function prints the <body onload="" actions
// --------------

//	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;
//	echo "";

} // end net2ftp_printBodyonload

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
// This function prints the zip screen
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result, $net2ftp_output;

	if (isset($_POST["list"]) == true)       { $list = getSelectedEntries($_POST["list"]); }
	else                                     { $list = ""; }
	if (isset($_POST["zipactions"]) == true) { $zipactions = $_POST["zipactions"]; }
	else                                     { $zipactions = ""; }


// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Title
	$title = __("Zip entries");

// Form name, back and forward buttons
	$formname = "ZipForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='browse';document.forms['" . $formname . "'].state2.value='main';document.forms['" . $formname . "'].submit();";
	$forward_onclick = "document.forms['" . $formname . "'].submit();";


// -------------------------------------------------------------------------
// Variables for screen 1
// -------------------------------------------------------------------------
	if ($net2ftp_globals["screen"] == 1) {

// Next screen
	$nextscreen = 2;

// Default filename of the zip file
	$zipfilename = get_filename_name($list["files"][1]["dirfilename"]) . ".zip";

	} // end if


// -------------------------------------------------------------------------
// Variables for screen 2
// -------------------------------------------------------------------------
	elseif ($net2ftp_globals["screen"] == 2) {

// --------------------
// Check the input data
// --------------------
// Filename
		if ($zipactions["save"] == "yes" && $zipactions["save"]["filename"] == "") {
			$errormessage = __("You did not enter a filename for the zipfile. Go back and enter a filename.") . "<br />";
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}

// Email address
		if ($zipactions["email"] == "yes" && checkEmailAddress($zipactions["email_to"]) == false) {
			$errormessage = __("The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>", $zipactions["email_to"]) . "<br />";
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}

// --------------------
// Execute the function
// --------------------
		setStatus(4, 10, __("Processing the entries"));
		$zipactions["download"] == "no";
		ftp_zip("", $net2ftp_globals["directory"], $list, $zipactions, "", 0);
		if ($net2ftp_result["success"] == false) { 
			return false;
		}

	} // end elseif

// -------------------------------------------------------------------------
// Print the output
// -------------------------------------------------------------------------
	require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/manage.template.php");

} // End net2ftp_printBody

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>