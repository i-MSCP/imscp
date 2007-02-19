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

// The 2 go_to_state variables come from the bookmark, or from registerglobals.inc.php
	if (isset($_GET["go_to_state"]) == true)  { $go_to_state  = $_GET["go_to_state"]; }
	else                                      { $go_to_state  = $net2ftp_globals["go_to_state"]; }
	if (isset($_GET["go_to_state2"]) == true) { $go_to_state2 = $_GET["go_to_state2"]; }
	else                                      { $go_to_state2 = $net2ftp_globals["go_to_state2"]; }
	if (isset($_GET["errormessage"]) == true) { $errormessage = $_GET["errormessage"]; }

// Most actions
	if (isset($_POST["list"]) == true) { $list = getSelectedEntries($_POST["list"]); }
	else                               { $list = ""; }

// Bookmark
	if (isset($_POST["url"]) == true)  { $url = $_POST["url"]; 	}
	else                               { $url = ""; }
	if (isset($_POST["text"]) == true) { $text = validateGenericInput($_POST["text"]); }
	else                               { $text = ""; }

// Copy, move, delete
	if (isset($_POST["ftpserver2"]) == true)     { $net2ftp_globals["ftpserver2"]     = validateFtpserver($_POST["ftpserver2"]); }
	else                                         { $net2ftp_globals["ftpserver2"]     = ""; }
	if (isset($_POST["ftpserverport2"]) == true) { $net2ftp_globals["ftpserverport2"] = validateFtpserverport($_POST["ftpserverport2"]); }
	else                                         { $net2ftp_globals["ftpserverport2"] = ""; }
	if (isset($_POST["username2"]) == true)      { $net2ftp_globals["username2"]      = validateUsername($_POST["username2"]); }
	else                                         { $net2ftp_globals["username2"]      = ""; }
	if (isset($_POST["password2"]) == true)      { $net2ftp_globals["password2"]      = validatePassword($_POST["password2"]); }
	else                                         { $net2ftp_globals["password2"]      = ""; }

// Edit
	if (isset($_POST["textareaType"]) == true)  { $textareaType = validateTextareaType($_POST["textareaType"]); }
	else                                        { $textareaType = ""; }
	if (isset($_POST["text"]) == true)          { $text = $_POST["text"]; }
	else                                        { $text = ""; }
	if (isset($_POST["text_splitted"]) == true) { $text_splitted = $_POST["text_splitted"]; }
	else                                        { $text_splitted = ""; }

// Find string
	if (isset($_POST["searchoptions"]) == true) { $searchoptions = $_POST["searchoptions"]; }

// New directory
// Rename
	if (isset($_POST["newNames"]) == true) { $newNames = validateEntry($_POST["newNames"]); }
	else                                   { $newNames = ""; }

// Raw FTP command
	if (isset($_POST["command"]) == true) { $command = $_POST["command"]; }
	else                                  { $command = "CWD $directory_html\nPWD\n"; }

// Zip
	if (isset($_POST["zipactions"]) == true) { $zipactions = $_POST["zipactions"]; }
	else                                     { $zipactions = ""; }

// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

	$formname = "LoginForm";
	$enctype = "";

	if ($net2ftp_globals["state2"] == "admin") {
		$message = __("Please enter your Administrator username and password.");
		$button_text = __("Login");
		$username_fieldname = "input_admin_username";
		$password_fieldname = "input_admin_password";
		$username_value = "";
		$password_value = "";
		$focus = $username_fieldname;
	}
	elseif ($net2ftp_globals["state2"] == "bookmark") {
		$message = __("Please enter your username and password for FTP server <b>%1\$s</b>.", htmlEncode2($net2ftp_globals["ftpserver"]));
		$button_text = __("Login");
		$username_fieldname = "username";
		$password_fieldname = "password";
		if (isset($net2ftp_globals["username"]) == true) { 
			$username_value = htmlEncode2($net2ftp_globals["username"]); 
			$focus = $password_fieldname;
		}
		else { 
			$username_value = ""; 
			$focus = $username_fieldname;
		}
		$password_value = "";
	}
	elseif ($net2ftp_globals["state2"] == "session_expired") {
		$message = __("Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue.", htmlEncode2($net2ftp_globals["ftpserver"]));
		$button_text = __("Continue");
		$username_fieldname = "username";
		$password_fieldname = "password";
		if (isset($net2ftp_globals["username"]) == true) { 
			$username_value = htmlEncode2($net2ftp_globals["username"]); 
			$focus = $password_fieldname;
		}
		else { 
			$username_value = ""; 
			$focus = $username_fieldname;
		}
		$password_value = "";
	}
	elseif ($net2ftp_globals["state2"] == "session_ipchange") {
		$message = __("Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue.", htmlEncode2($net2ftp_globals["ftpserver"]));
		$button_text = __("Continue");
		$username_fieldname = "username";
		$password_fieldname = "password";
		if (isset($net2ftp_globals["username"]) == true) { 
			$username_value = htmlEncode2($net2ftp_globals["username"]); 
			$focus = $password_fieldname;
		}
		else { 
			$username_value = ""; 
			$focus = $username_fieldname;
		}
		$password_value = "";
	}

// -------------------------------------------------------------------------
// Print the output
// -------------------------------------------------------------------------
	require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/login_small.template.php");

} // End net2ftp_printBody

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>