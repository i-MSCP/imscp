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

	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;

// Code
//	echo "<script type=\"text/javascript\"><!--\n";	
//	echo "//--></script>\n";

// Include
		echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/findstring/findstring.js\"></script>\n";

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
// This function prints the search screen
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result, $net2ftp_output;

	if (isset($_POST["list"]) == true)          { $list = getSelectedEntries($_POST["list"]); }
	else                                        { $list = ""; }
	if (isset($_POST["searchoptions"])          == true)  { $searchoptions                   = $_POST["searchoptions"]; }
	if (isset($searchoptions["string"])         == false) { $searchoptions["string"]         = ""; }
	if (isset($searchoptions["case_sensitive"]) == false) { $searchoptions["case_sensitive"] = ""; }
	if (isset($searchoptions["filename"])       == false) { $searchoptions["filename"]       = ""; }
	if (isset($searchoptions["size_from"])      == false) { $searchoptions["size_from"]      = ""; }
	if (isset($searchoptions["size_to"])        == false) { $searchoptions["size_to"]        = ""; }
	if (isset($searchoptions["modified_from"])  == false) { $searchoptions["modified_from"]  = ""; }
	if (isset($searchoptions["modified_to"])    == false) { $searchoptions["modified_to"]    = ""; }

// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Title
// See below

// Form name, back and forward buttons
	$formname = "FindstringForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='browse';document.forms['" . $formname . "'].state2.value='main';document.forms['" . $formname . "'].submit();";
	$forward_onclick = "document.forms['" . $formname . "'].submit();";

// Next screen
	$nextscreen = 2;

// -------------------------------------------------------------------------
// Variables for screen 1
// -------------------------------------------------------------------------
	if ($net2ftp_globals["screen"] == 1) {

// Title
	$title = __("Search directories and files");

// From and to dates
	$tomorrow = date("Y-m-d", time() + 3600*24);
	$oneweekago = date("Y-m-d", time() - 3600*24*7);
	$modified_from = $oneweekago;
	$modified_to   = $tomorrow;

	} // end if


// -------------------------------------------------------------------------
// Variables for screen 2
// -------------------------------------------------------------------------
	elseif ($net2ftp_globals["screen"] == 2) {

// Title
	$title = __("Search results");

// Check if $searchoptions["string"] is a valid string
		if (is_string($searchoptions["string"]) == false) { 
			$errormessage = __("Please enter a valid search word or phrase.");
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}

// Check if $searchoptions["filename"] is a valid filename with a possible wildcard character *
		if ($searchoptions["filename"] != "" && preg_match("/^[a-zA-Z0-9_ *\.-]*$/", $searchoptions["filename"]) == 0) { 
			$errormessage = __("Please enter a valid filename.");
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}

// Check if $searchoptions["size_from"] and $searchoptions["size_to"] are valid numbers
		if ($searchoptions["size_from"] != "" && is_numeric($searchoptions["size_from"]) == false) { 
			$errormessage = __("Please enter a valid file size in the \"from\" textbox, for example 0.");
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
		if ($searchoptions["size_to"] != "" && is_numeric($searchoptions["size_to"]) == false) { 
			$errormessage = __("Please enter a valid file size in the \"to\" textbox, for example 500000.");
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}

// Check if $searchoptions["modified_from"] and $searchoptions["modified_to"] are valid dates
		if ($searchoptions["modified_from"] != "" && preg_match("/^[0-9-]*$/", $searchoptions["modified_from"]) == 0) { 
			$errormessage = __("Please enter a valid date in Y-m-d format in the \"from\" textbox.");
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
		if ($searchoptions["modified_to"] != "" && preg_match("/^[0-9-]*$/", $searchoptions["modified_to"]) == 0) { 
			$errormessage = __("Please enter a valid date in Y-m-d format in the \"to\" textbox."); 
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}

// ------------
// CONVERSIONS
// ------------
// Convert the wildcard character * in the filename by the wildcard .* that can be read by preg_match
// So this *.* becomes this .*..*
		$searchoptions["filename"] = str_replace("*", ".*", $searchoptions["filename"]);

// Convert the mtime to a unix timestamp
		$searchoptions["modified_from"] = strtotime($searchoptions["modified_from"]);
		$searchoptions["modified_to"] = strtotime($searchoptions["modified_to"]);

// Open connection
		setStatus(2, 10, __("Connecting to the FTP server"));
		$conn_id = ftp_openconnection();
		if ($net2ftp_result["success"] == false)  { return false; }

// Find the files
		$result = array();
		setStatus(4, 10, __("Searching the files..."));
		$result = ftp_processfiles("findstring", $conn_id, $net2ftp_globals["directory"], $list, $searchoptions, $result, 0);
		if ($net2ftp_result["success"] == false)  { return false; }

// Close connection
		ftp_closeconnection($conn_id);

		if (sizeof($result) == 0) {
			$net2ftp_output["findstring"][] = __("The word <b>%1\$s</b> was not found in the selected directories and files.", $searchoptions["string"]);
		}
		else {
			$net2ftp_output["findstring"][] = __("The word <b>%1\$s</b> was found in the following files:", $searchoptions["string"]);
		}

	} // end if


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