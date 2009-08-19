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
// This function prints the rename screen
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result, $net2ftp_output;

	if (isset($_POST["list"]) == true)     { $list = getSelectedEntries($_POST["list"]); }
	else                                   { $list = ""; }
	if (isset($_POST["newNames"]) == true) { $newNames = validateEntry($_POST["newNames"]); }
	else                                   { $newNames = ""; }


// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Title
	$title = __("Rename directories and files");

// Form name, back and forward buttons
	$formname = "RenameForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='browse';document.forms['" . $formname . "'].state2.value='main';document.forms['" . $formname . "'].submit();";
	$forward_onclick = "document.forms['" . $formname . "'].submit();";

// -------------------------------------------------------------------------
// Variables for screen 1
// -------------------------------------------------------------------------
	if ($net2ftp_globals["screen"] == 1) {

// Next screen
	$nextscreen = 2;

	} // end if


// -------------------------------------------------------------------------
// Variables for screen 2
// -------------------------------------------------------------------------
	elseif ($net2ftp_globals["screen"] == 2) {

// Open connection
		setStatus(2, 10, __("Connecting to the FTP server"));
		$conn_id = ftp_openconnection();
		if ($net2ftp_result["success"] == false) { return false; }

// Rename files
		setStatus(4, 10, __("Processing the entries"));
		for ($i=1; $i<=sizeof($list["all"]); $i++) {
			if (strstr($list["all"][$i]["dirfilename"], "..") != false) {
				$net2ftp_output["rename"][] = __("The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>", htmlEncode2($newNames[$i])) . "<br />";
				continue;
			}
			if (checkAuthorizedName($newNames[$i]) == false) {
				$net2ftp_output["rename"][] = __("The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>", htmlEncode2($newNames[$i])) . "<br />";
				continue;			
			}
			ftp_rename2($conn_id, $net2ftp_globals["directory"], $list["all"][$i]["dirfilename"], $newNames[$i]);
			if ($net2ftp_result["success"] == false) { 
				setErrorVars(true, "", "", "", ""); 
				$net2ftp_output["rename"][] = __("<b>%1\$s</b> could not be renamed to <b>%2\$s</b>", htmlEncode2($list["all"][$i]["dirfilename"]), htmlEncode2($newNames[$i])); 
				continue; 
			}
			else { 
				$net2ftp_output["rename"][] = __("<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>", htmlEncode2($list["all"][$i]["dirfilename"]), htmlEncode2($newNames[$i])); 
			}
		} // End for

// Close connection
		ftp_closeconnection($conn_id);

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