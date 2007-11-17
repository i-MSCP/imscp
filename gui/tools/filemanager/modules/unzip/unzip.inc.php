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
	echo "	d.writeln('<bo' + 'dy on' + 'load=\"document.forms[\\'DirectoryTreeForm\\'].submit();\">');\n";
//	echo "	d.writeln('<body>');\n";
	echo "	d.writeln('" . __("Please wait...") . "<br /><br />');\n";
	echo "	d.writeln('<form id=\"DirectoryTreeForm\" action=\"" . printPHP_SELF("createDirectoryTreeWindow") . "\" method=\"post\" />');\n";
	printLoginInfo_javascript();
	echo "	d.writeln('<input type=\"hidden\" name=\"state\" value=\"browse\">');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"state2\" value=\"popup\">');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"directory\" value=\"' + directory + '\">');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"FormAndFieldName\" value=\"' + FormAndFieldName + '\">');\n";
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
//	echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/unzip/unzip.js\"></script>\n";

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
// This function prints the unzip screen
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result, $net2ftp_output;

	if (isset($_POST["list"]) == true)       { $list = getSelectedEntries($_POST["list"]); }
	else                                     { $list = ""; }

// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Title
	$title = __("Unzip archives");

// Form name, back and forward buttons
	$formname = "UnzipForm";
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

	$net2ftp_output["unzip"] = array();
	$net2ftp_output["ftp_unziptransferfiles"] = array();

// ---------------------------------------
// Initialize variables
// ---------------------------------------
		$moved_ok = 0;    // Index of the archives that have been treated successfully
		$moved_notok = 0; // Index of the archives that have been treated unsuccessfully

// ---------------------------------------
// Open connection to the FTP server
// ---------------------------------------
		setStatus(2, 10, __("Connecting to the FTP server"));
		$conn_id = ftp_openconnection();
		if ($net2ftp_result["success"] == false) { return false; }

// ---------------------------------------
// Get the archives from the FTP server
// ---------------------------------------
		for ($i=1; $i<=$list["stats"]["files"]["total_number"]; $i=$i+1) {

// Set the status
			$message = __("Getting archive %1\$s of %2\$s from the FTP server", $i, $list["stats"]["files"]["total_number"]);
			setStatus($i, $list["stats"]["files"]["total_number"], $message);

// Get the archive from the FTP server
			$localtargetdir = $net2ftp_globals["application_tempdir"];
			$localtargetfile = $list["files"][$i]["dirfilename"] . ".txt";
			$remotesourcedir = $net2ftp_globals["directory"];
			$remotesourcefile = $list["files"][$i]["dirfilename"];
			$ftpmode = ftpAsciiBinary($list["files"][$i]["dirfilename"]);
			$copymove = "copy";

			ftp_getfile($conn_id, $localtargetdir, $localtargetfile, $remotesourcedir, $remotesourcefile, $ftpmode, $copymove);
			if ($net2ftp_result["success"] == false) { 
				setErrorVars(true, "", "", "", "");
				$net2ftp_output["unzip"][] = __("Unable to get the archive <b>%1\$s</b> from the FTP server", htmlEncode2($list["files"][$i]["dirfilename"]));
				$moved_notok = $moved_notok + 1;
				continue; 
			}

// Register the temporary file
			registerTempfile("register", glueDirectories($localtargetdir, $localtargetfile));

// Enter the temporary filename and the real filename in the array
			$moved_ok = $moved_ok + 1;
			$acceptedArchivesArray[$moved_ok]["name"] = $list["files"][$i]["dirfilename"];
			$acceptedArchivesArray[$moved_ok]["tmp_name"] = glueDirectories($localtargetdir, $localtargetfile);
			$acceptedArchivesArray[$moved_ok]["targetdirectory"] = $list["files"][$i]["targetdirectory"];
			$acceptedArchivesArray[$moved_ok]["use_folder_names"] = $list["files"][$i]["use_folder_names"];

		} // end for

// ---------------------------------------
// Unzip archives and transfer the files (create subdirectories if needed)
// ---------------------------------------
		if (isset($acceptedArchivesArray) == true && sizeof($acceptedArchivesArray) > 0) {
			ftp_unziptransferfiles($acceptedArchivesArray);
			$net2ftp_output["unzip"] = $net2ftp_output["unzip"] + $net2ftp_output["ftp_unziptransferfiles"];
			if ($net2ftp_result["success"] == false)  { return false; }
		}

// ---------------------------------------
// Close the connection to the FTP server
// ---------------------------------------
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