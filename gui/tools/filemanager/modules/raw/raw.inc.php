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
// This function prints the raw FTP command screen
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result, $net2ftp_output;

	if (isset($_POST["command"]) == true) { $command = $_POST["command"]; }
	else                                  { $command = "CWD $directory_html\nPWD\n"; }


// -------------------------------------------------------------------------
// Variables
// -------------------------------------------------------------------------

// Title
	$title = __("Send arbitrary FTP commands");

// Form name, back and forward buttons
	$formname = "RawForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='advanced';document.forms['" . $formname . "'].screen.value='1';document.forms['" . $formname . "'].submit();";
	$forward_onclick = "document.forms['" . $formname . "'].submit();";

// Open connection
	setStatus(2, 10, __("Connecting to the FTP server"));
	$conn_id = ftp_openconnection();
	if ($net2ftp_result["success"] == false) { return false; }

// Explode list of commands to an array
	$command_exploded = explode("\n", $command);

// Remove the empty lines
	$new_command_nr = 0;
	for ($command_nr=0; $command_nr<sizeof($command_exploded); $command_nr++) {
		if (trim($command_exploded[$command_nr]) != "") { 
			$command_exploded_trimmed[$new_command_nr] = trim($command_exploded[$command_nr]); 
			$new_command_nr++;
		}
	}

// Send arbitrary FTP command
	$command_total = sizeof($command_exploded_trimmed);
	for ($command_nr=0; $command_nr<$command_total; $command_nr++) {
		setStatus($command_nr+1, $command_total, __("Sending FTP command %1\$s of %2\$s", $command_nr+1, $command_total));
		$ftp_raw_result[$command_nr] = ftp_raw($conn_id, trim($command_exploded_trimmed[$command_nr]));
	}

// Close connection
	ftp_closeconnection($conn_id);

// Get the messages
	for ($command_nr=0; $command_nr<sizeof($command_exploded_trimmed); $command_nr++) {
		for ($line_nr=0; $line_nr<sizeof($ftp_raw_result[$command_nr]); $line_nr++) {
			$net2ftp_output["ftp_raw"][] = $ftp_raw_result[$command_nr][$line_nr] . "\n";
		}
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