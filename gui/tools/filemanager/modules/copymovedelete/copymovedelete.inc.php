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

	global $net2ftp_settings, $net2ftp_globals, $net2ftp_result;

// Code
	echo "<script type=\"text/javascript\"><!--\n";	
	echo "function createDirectoryTreeWindow(directory, FormAndFieldName) {\n";
	echo "	directoryTreeWindow = window.open(\"\",\"directoryTreeWindow\",\"height=450,width=300,resizable=yes,scrollbars=yes\");\n";
	echo "	var d = directoryTreeWindow.document;\n";
	echo "	d.writeln('<html>');\n";
	echo "	d.writeln('<head>');\n";
	echo "	d.writeln('<title>" . __("Choose a directory") . "<\/title>');\n";
	echo "	d.writeln('<\/head>');\n";
	echo "	d.writeln('<bo' + 'dy on' + 'load=\"document.DirectoryTreeForm.submit();\">');\n";
//	echo "	d.writeln('<body>');\n";
	echo "	d.writeln('" . __("Please wait...") . "<br /><br />');\n";
	echo "	d.writeln('<form name=\"DirectoryTreeForm\" id=\"DirectoryTreeForm\" action=\"" . printPHP_SELF("createDirectoryTreeWindow") . "\" method=\"post\" />');\n";
	printLoginInfo_javascript();
	echo "	d.writeln('<input type=\"hidden\" name=\"state\" value=\"browse\" />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"state2\" value=\"popup\" />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"directory\" value=\"' + directory + '\"  />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"FormAndFieldName\" value=\"' + FormAndFieldName + '\"  />');\n";
	echo "	d.writeln('<\/form>');\n";
	echo "	d.writeln('<\/div>');\n";
	echo "	d.writeln('<\/body>');\n";
	echo "	d.writeln('<\/html>');\n";
	echo "	d.close();\n";
	echo "} // end function createDirectoryTreeWindow\n";
	echo "\n";
	echo "function CopyValueToAll(myform, mysourcefield, mytargetfieldname) {\n";
	echo "	for (var i = 0; i < myform.elements.length; i++) {\n";
	echo "		if (myform.elements[i].name.indexOf(mytargetfieldname) >= 0) {\n";
	echo "			myform.elements[i].value = mysourcefield.value;\n";
	echo "		}\n";
	echo "	}\n";
	echo "}\n";
	echo "//--></script>\n";

// Include
//	echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/copymovedelete/copymovedelete.js\"></script>\n";

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

	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages;

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

//	global $net2ftp_settings, $net2ftp_globals, $net2ftp_result;
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
// This function prints the copy/move/delete screen
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result, $net2ftp_output;

	if (isset($_POST["list"]) == true)           { $list                              = getSelectedEntries($_POST["list"]); }
	else                                         { $list                              = ""; }
	if (isset($_POST["ftpserver2"]) == true)     { $net2ftp_globals["ftpserver2"]     = validateFtpserver($_POST["ftpserver2"]); }
	else                                         { $net2ftp_globals["ftpserver2"]     = ""; }
	if (isset($_POST["ftpserverport2"]) == true) { $net2ftp_globals["ftpserverport2"] = validateFtpserverport($_POST["ftpserverport2"]); }
	else                                         { $net2ftp_globals["ftpserverport2"] = ""; }
	if (isset($_POST["username2"]) == true)      { $net2ftp_globals["username2"]      = validateUsername($_POST["username2"]); }
	else                                         { $net2ftp_globals["username2"]      = ""; }
	if (isset($_POST["password2"]) == true)      { $net2ftp_globals["password2"]      = validatePassword($_POST["password2"]); }
	else                                         { $net2ftp_globals["password2"]      = ""; }

// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Title
	if     ($net2ftp_globals["state2"] == "copy") {
		$title = __("Copy directories and files");
	}
	elseif ($net2ftp_globals["state2"] == "move") {
		$title = __("Move directories and files");
	}
	elseif ($net2ftp_globals["state2"] == "delete") {
		$title = __("Delete directories and files");
	}

// Form name, back and forward buttons
	$formname = "CopyMoveDeleteForm";
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

// ---------------------------------------
// Open connection to the source server
// ---------------------------------------
		setStatus(2, 10, __("Connecting to the FTP server"));
		$conn_id_source = ftp_openconnection();
		if ($net2ftp_result["success"] == false) { return false; }

// ---------------------------------------
// Open connection to the target server, if it is different from the source server, or if the username
// is different (different users may have different authorizations on the same FTP server)
// ---------------------------------------
		if (($net2ftp_globals["ftpserver2"] != "" || $net2ftp_globals["username2"] != "") &&
                ($net2ftp_globals["ftpserver2"] != $net2ftp_globals["ftpserver"] || $net2ftp_globals["username2"] != $net2ftp_globals["username"])) {
			$conn_id_target = ftp_openconnection2();       // Note: ftp_openconnection2 cleans the input values
			if ($net2ftp_result["success"] == false) { return false; }
		}
		else { $conn_id_target = $conn_id_source; }

// ---------------------------------------
// Copy, move or delete the files and directories
// ---------------------------------------
		ftp_copymovedelete($conn_id_source, $conn_id_target, $list, $net2ftp_globals["state2"], 0);

// ---------------------------------------
// Close the connection to the source server
// ---------------------------------------
		ftp_closeconnection($conn_id_source);

// ---------------------------------------
// Close the connection to the target server, if it is different from the source server
// ---------------------------------------
		if ($conn_id_source != $conn_id_target) { ftp_closeconnection($conn_id_target); }

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