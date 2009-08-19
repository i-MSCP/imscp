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
	echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/chmod/chmod.js\"></script>\n";

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
// This function prints the chmod screen
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result, $net2ftp_output;

	if (isset($_POST["list"]) == true) { $list = getSelectedEntries($_POST["list"]); }
	else                               { $list = ""; }


// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Title
	$title = __("Chmod directories and files");

// Form name, back and forward buttons
	$formname = "ChmodForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='browse';document.forms['" . $formname . "'].state2.value='main';document.forms['" . $formname . "'].submit();";
	$forward_onclick = "document.forms['" . $formname . "'].submit();";


// -------------------------------------------------------------------------
// Variables for screen 1
// -------------------------------------------------------------------------
	if ($net2ftp_globals["screen"] == 1) {

// Next screen
		$nextscreen = 2;

// Initialize variables
		$directory_index    = 1;
		$file_index         = 1;
		$symlink_index      = 1;

		for ($i=1; $i<=count($list["all"]); $i++) {

			if     ($list["all"][$i]["dirorfile"] == "d")   { $list["all"][$i]["message"] = __("Set the permissions of directory <b>%1\$s</b> to: ", $list["all"][$i]["dirfilename"]) . "<br />\n"; }
			elseif ($list["all"][$i]["dirorfile"] == "-")   { $list["all"][$i]["message"] = __("Set the permissions of file <b>%1\$s</b> to: ", $list["all"][$i]["dirfilename"]) . "<br />\n"; }
			elseif ($list["all"][$i]["dirorfile"] == "l")   { $list["all"][$i]["message"] = __("Set the permissions of symlink <b>%1\$s</b> to: ", $list["all"][$i]["dirfilename"]) . "<br />\n"; }

			$owner_chmod = 0;
			if (substr($list["all"][$i]["permissions"], 0, 1) == "r") { $owner_chmod+=4; $list["all"][$i]["owner_read"]    = "checked=\"checked\""; }
			else                                                      {                  $list["all"][$i]["owner_read"]    = ""; }
			if (substr($list["all"][$i]["permissions"], 1, 1) == "w") { $owner_chmod+=2; $list["all"][$i]["owner_write"]   = "checked=\"checked\""; }
			else                                                      {                  $list["all"][$i]["owner_write"]   = ""; }
			if (substr($list["all"][$i]["permissions"], 2, 1) == "x") { $owner_chmod+=1; $list["all"][$i]["owner_execute"] = "checked=\"checked\""; }
			else                                                      {                  $list["all"][$i]["owner_execute"] = ""; }

			$group_chmod = 0;
			if (substr($list["all"][$i]["permissions"], 3, 1) == "r") { $group_chmod+=4; $list["all"][$i]["group_read"]    = "checked=\"checked\""; }
			else                                                      {                  $list["all"][$i]["group_read"]    = ""; }
			if (substr($list["all"][$i]["permissions"], 4, 1) == "w") { $group_chmod+=2; $list["all"][$i]["group_write"]   = "checked=\"checked\""; }
			else                                                      {                  $list["all"][$i]["group_write"]   = ""; }
			if (substr($list["all"][$i]["permissions"], 5, 1) == "x") { $group_chmod+=1; $list["all"][$i]["group_execute"] = "checked=\"checked\""; }
			else                                                      {                  $list["all"][$i]["group_execute"] = ""; }

			$other_chmod = 0;
			if (substr($list["all"][$i]["permissions"], 6, 1) == "r") { $other_chmod+=4; $list["all"][$i]["other_read"]    = "checked=\"checked\""; }
			else                                                      {                  $list["all"][$i]["other_read"]    = ""; }
			if (substr($list["all"][$i]["permissions"], 7, 1) == "w") { $other_chmod+=2; $list["all"][$i]["other_write"]   = "checked=\"checked\""; }
			else                                                      {                  $list["all"][$i]["other_write"]   = ""; }
			if (substr($list["all"][$i]["permissions"], 8, 1) == "x") { $other_chmod+=1; $list["all"][$i]["other_execute"] = "checked=\"checked\""; }
			else                                                      {                  $list["all"][$i]["other_execute"] = ""; }

			$list["all"][$i]["chmodvalue"] = $owner_chmod.$group_chmod.$other_chmod;
			if     ($list["all"][$i]["dirorfile"] == "d")   { $list["directories"][$directory_index]["chmodvalue"] = $list["all"][$i]["chmodvalue"]; $directory_index++; }
			elseif ($list["all"][$i]["dirorfile"] == "-")   { $list["files"][$file_index]["chmodvalue"]            = $list["all"][$i]["chmodvalue"]; $file_index++; }
			elseif ($list["all"][$i]["dirorfile"] == "l")   { $list["symlinks"][$symlink_index]["chmodvalue"]      = $list["all"][$i]["chmodvalue"]; $symlink_index++; }

		} // end for

	} // end if


// -------------------------------------------------------------------------
// Variables for screen 2
// -------------------------------------------------------------------------
	elseif ($net2ftp_globals["screen"] == 2) {

// Initialize variables
		$directory_index    = 1;
		$file_index         = 1;
		$symlink_index      = 1;

// Calculate the chmod octal
		for ($i=1; $i<=count($list["all"]); $i++) {
			if (isset($list["all"][$i]["owner_read"]) == false)    { $list["all"][$i]["owner_read"] = 0; }
			if (isset($list["all"][$i]["owner_write"]) == false)   { $list["all"][$i]["owner_write"] = 0; }
			if (isset($list["all"][$i]["owner_execute"]) == false) { $list["all"][$i]["owner_execute"] = 0; }
			if (isset($list["all"][$i]["group_read"]) == false)    { $list["all"][$i]["group_read"] = 0; }
			if (isset($list["all"][$i]["group_write"]) == false)   { $list["all"][$i]["group_write"] = 0; }
			if (isset($list["all"][$i]["group_execute"]) == false) { $list["all"][$i]["group_execute"] = 0; }
			if (isset($list["all"][$i]["other_read"]) == false)    { $list["all"][$i]["other_read"] = 0; }
			if (isset($list["all"][$i]["other_write"]) == false)   { $list["all"][$i]["other_write"] = 0; }
			if (isset($list["all"][$i]["other_execute"]) == false) { $list["all"][$i]["other_execute"] = 0; }

			$ownerOctal = $list["all"][$i]["owner_read"] + $list["all"][$i]["owner_write"] + $list["all"][$i]["owner_execute"];
			$groupOctal = $list["all"][$i]["group_read"] + $list["all"][$i]["group_write"] + $list["all"][$i]["group_execute"];
			$otherOctal = $list["all"][$i]["other_read"] + $list["all"][$i]["other_write"] + $list["all"][$i]["other_execute"];

			$chmodOctal = $ownerOctal . $groupOctal . $otherOctal;

			if ($chmodOctal > 777 || $chmodOctal < 0) {
				$errormessage = __("The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again.", $chmodOctal);
				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false;
			}
			else { 
				$list["all"][$i]["chmodoctal"] = $chmodOctal; 
				if     ($list["all"][$i]["dirorfile"] == "d")   { $list["directories"][$directory_index]["chmodoctal"] = $list["all"][$i]["chmodoctal"]; $directory_index++; }
				elseif ($list["all"][$i]["dirorfile"] == "-")   { $list["files"][$file_index]["chmodoctal"]            = $list["all"][$i]["chmodoctal"]; $file_index++; }
				elseif ($list["all"][$i]["dirorfile"] == "l")   { $list["symlinks"][$symlink_index]["chmodoctal"]      = $list["all"][$i]["chmodoctal"]; $symlink_index++; }
			}

		} // End for

// Open connection
		setStatus(2, 10, __("Connecting to the FTP server"));
		$conn_id = ftp_openconnection();
		if ($conn_id == false) { return false; }

// Chmod the entries
		setStatus(4, 10, __("Processing the entries"));
		ftp_chmod2($conn_id, $net2ftp_globals["directory"], $list, 0);
		if ($net2ftp_result["success"] == false) { return false; }

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