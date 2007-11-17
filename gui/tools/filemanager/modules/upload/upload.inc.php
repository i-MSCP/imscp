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

	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;

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
	echo "//--></script>\n";

// Include
	echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/upload/upload.js\"></script>\n";

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
// This function prints the upload screen
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result, $net2ftp_output;

	$file_counter = 0;
	$archive_counter = 0;

// Normal upload
	if (isset($_FILES["file"]) == true && is_array($_FILES["file"]) == true) {
		foreach ($_FILES["file"]["name"] as $key => $val) {
			if ($val != "") {
				$file_counter = $file_counter + 1;
				$uploadedFilesArray["$file_counter"]["name"]     = validateEntry($val);
				$uploadedFilesArray["$file_counter"]["tmp_name"] = $_FILES["file"]["tmp_name"][$key];
				$uploadedFilesArray["$file_counter"]["size"]     = $_FILES["file"]["size"][$key];
			} // end if
		} // end foreach
	}
		
	if (isset($_FILES["archive"]) == true && is_array($_FILES["archive"]) == true) {
		foreach ($_FILES["archive"]["name"] as $key => $val) {
			if ($val != "") {
				$archive_counter = $archive_counter + 1;
				$uploadedArchivesArray["$archive_counter"]["name"]     = validateEntry($val);
				$uploadedArchivesArray["$archive_counter"]["tmp_name"] = $_FILES["archive"]["tmp_name"][$key];
				$uploadedArchivesArray["$archive_counter"]["size"]     = $_FILES["archive"]["size"][$key];
			} // end if
		} // end foreach
	}

// Upload via SWFUpload Flash applet
	if (isset($_FILES["Filedata"]) == true && is_array($_FILES["Filedata"]) == true) {
		$file_counter = $file_counter + 1;
		$uploadedFilesArray["$file_counter"]["name"]     = $_FILES["Filedata"]["name"];
		$uploadedFilesArray["$file_counter"]["tmp_name"] = $_FILES["Filedata"]["tmp_name"];
		$uploadedFilesArray["$file_counter"]["size"]     = $_FILES["Filedata"]["size"];
	}

// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Title
// The title is different for screen 1 and screen 2 - see below

// Form name, back and forward buttons
	$formname = "UploadForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='browse';document.forms['" . $formname . "'].state2.value='main';document.forms['" . $formname . "'].submit();";
	$forward_onclick = "document.forms['" . $formname . "'].submit();";

// Encoding type
	$enctype = "enctype=\"multipart/form-data\"";

// Next screen
	$nextscreen = 2;

// Maxima
	$max_filesize         = $net2ftp_settings["max_filesize"];
	$max_filesize_net2ftp = $max_filesize / 1024;
	$max_upload_filesize_php     = @ini_get("upload_max_filesize");
	$max_execution_time          = @ini_get("max_execution_time");


// -------------------------------------------------------------------------
// Variables for screen 1
// -------------------------------------------------------------------------
	if ($net2ftp_globals["screen"] == 1) {

// Title
		$title = __("Upload files and archives");

	} // end if


// -------------------------------------------------------------------------
// Variables for screen 2
// -------------------------------------------------------------------------
	elseif ($net2ftp_globals["screen"] == 2) {

// Title
		$title = __("Upload more files and archives");

// ---------------------------------------
// Check the files and move them to the net2ftp temp directory
// The .txt extension is added
// ---------------------------------------
		if (sizeof($uploadedFilesArray) > 0 || sizeof($uploadedArchivesArray) > 0) {
			setStatus(1, 10, __("Checking files"));
			if (isset($uploadedFilesArray) == true) {
				$acceptedFilesArray = acceptFiles($uploadedFilesArray);
				if ($net2ftp_result["success"] == false) { return false; }
			}
			if (isset($uploadedArchivesArray) == true) {
				$acceptedArchivesArray = acceptFiles($uploadedArchivesArray);
				if ($net2ftp_result["success"] == false) { return false; }
			}
		}

// ---------------------------------------
// Transfer files
// ---------------------------------------
		if (isset($acceptedFilesArray) == true && $acceptedFilesArray != "all_uploaded_files_are_too_big" && sizeof($acceptedFilesArray) > 0) {
			setStatus(0, 10, __("Transferring files to the FTP server"));
			ftp_transferfiles($acceptedFilesArray, $net2ftp_globals["directory"]);
			if ($net2ftp_result["success"] == false) { return false; }
		}

// ---------------------------------------
// Unzip archives and transfer the files (create subdirectories if needed)
// ---------------------------------------
		if (isset($acceptedArchivesArray) == true && $acceptedArchivesArray != "all_uploaded_files_are_too_big" && sizeof($acceptedArchivesArray) > 0) {

// Set the status
			setStatus(0, 10, __("Decompressing archives and transferring files"));

// Add information to $acceptedArchivesArray 
			for ($i=1; $i<=sizeof($acceptedArchivesArray); $i=$i+1) {
				$acceptedArchivesArray[$i]["targetdirectory"] = $net2ftp_globals["directory"];
			}

// Unzip the archive and transfer the files
			ftp_unziptransferfiles($acceptedArchivesArray);
			if ($net2ftp_result["success"] == false)  { return false; }
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