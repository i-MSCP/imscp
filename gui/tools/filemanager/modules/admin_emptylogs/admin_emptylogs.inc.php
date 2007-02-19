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

// -------------------------------------------------------------------------
// Check Admin username and password
// Redirect to the login_small page if needed
// -------------------------------------------------------------------------
	checkAdminUsernamePassword();

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

//	global $net2ftp_settings, $net2ftp_globals;

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

	if (isset($_POST["input_admin_username"]) == true) { $input_admin_username = htmlEncode2(validateGenericInput($_POST["input_admin_username"])); }
	else                                               { $input_admin_username = ""; }
	if (isset($_POST["input_admin_password"]) == true) { $input_admin_password = htmlEncode2(validateGenericInput($_POST["input_admin_password"])); }
	else                                               { $input_admin_password = ""; }
	if (isset($_POST["datefrom"]) == true) { $datefrom = addslashes(validateGenericInput($_POST["datefrom"])); }
	else                                   { $datefrom = ""; }
	if (isset($_POST["dateto"]) == true)   { $dateto   = addslashes(validateGenericInput($_POST["dateto"])); }
	else                                   { $dateto   = ""; }

// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Output variable
	$net2ftp_output["admin_emptylogs"][] = "";

// Title
	$title = __("Admin functions");

// Form name, back and forward buttons
	$formname = "AdminForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='admin';document.forms['" . $formname . "'].submit();";
	$forward_onclick = "document.forms['" . $formname . "'].submit();";


// -------------------------------------------------------------------------
// Variables for screen 1
// -------------------------------------------------------------------------

// ------------------------------------
// Input checks
// ------------------------------------
// Add slashes to variables which are used in a SQL query, and which are
// potentially unsafe (supplied by the user).
	$datefrom = addslashes($datefrom);
	$dateto   = addslashes($dateto);
	
	if ($datefrom == "" || $datefrom == 0) { return false; }
	if ($dateto == "" || $dateto == 0)     { return false; }

// ------------------------------------
// Delete empty logs
// ------------------------------------
	emptyLogs($datefrom, $dateto);
	if (isset($net2ftp_output["emptyLogs"]) == true) {
		$net2ftp_output["admin_emptylogs"] = $net2ftp_output["admin_emptylogs"] + $net2ftp_output["emptyLogs"];
	}

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