<?php

//   -------------------------------------------------------------------------------
//  |                  net2ftp: a web based FTP client                              |
//  |              Copyright (c) 2003-2008 by David Gartner                         |
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

	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result, $net2ftp_output;

// ------------------------------------
// 1. Register the global variables
// ------------------------------------

	if ($net2ftp_globals["screen"] == 2) {

		$file_counter = 0;
		foreach($_FILES as $tagname=>$object) {
			if ($object['name'] != "") {
				$file_counter = $file_counter + 1;
				$uploadedFilesArray["$file_counter"]["name"]               = $object['name'];
				$uploadedFilesArray["$file_counter"]["tmp_name"]           = $object['tmp_name'];
				$uploadedFilesArray["$file_counter"]["size"]               = $object['size'];
				$uploadedFilesArray["$file_counter"]["error"]              = $object['error'];
				// Look for special encoded jupload files
				$contentType = $object['type'];
				if (substr($contentType,0,7) == "jupload") {
					$base64_encoded_path = substr($contentType,8);
					$base64_decoded_path = base64_decode($base64_encoded_path);
					$uploadedFilesArray["$file_counter"]["absolute_directory"] = $base64_decoded_path;
				} // end if
			} // end if
		} // end foreach

		echo "Please wait, the files are being transferred to the FTP server...<br />\n";
		flush();

// ------------------------------------
// 2. POST METHOD: Move files from the *webserver's* temporary directory to *net2ftp's*
// temporary directory (move_uploaded_files).
// ------------------------------------
		if ($_SERVER["REQUEST_METHOD"] == "POST" && sizeof($uploadedFilesArray) > 0) {

			$moved_counter = 0;
			for ($j=1; $j<=sizeof($uploadedFilesArray); $j++) {
				$file_name               = $uploadedFilesArray["$j"]["name"];
				$file_tmp_name           = $uploadedFilesArray["$j"]["tmp_name"];
				$file_size               = $uploadedFilesArray["$j"]["size"];
				$file_error              = $uploadedFilesArray["$j"]["error"];
				$file_absolute_directory = $uploadedFilesArray["$j"]["absolute_directory"];
		
				if (($file_name != "" && $file_tmp_name == "") || $file_size > $net2ftp_settings["max_filesize"]) {
// The case ($file_name != "" && $file_tmp_name == "") occurs when the file is bigger than the directives set in php.ini
// In that case, only $uploadedFilesArray["$j"]["name"] is filled in.
					echo "WARNING: File <b>$file_name</b> skipped: this file is too big.<br />\n"; 
					@unlink($file_tmp_name); 
					continue;
				}
				elseif (checkAuthorizedName($file_name) == false || checkAuthorizedName($file_absolute_directory) == false) {
					echo "WARNING: File <b>$file_absolute_directory</b> skipped: it contains a banned keyword.<br />\n";
					$skipped = $skipped + 1;
					@unlink($file_tmp_name); 
					continue;
				}

// Create the temporary filename as follows: (from left to right)
// - Use prefix "upload__", to be able to identify from where this temporary file comes from
// - Create a random filename
// - Add the original filename extension, to be able to identify the filetype
// - Add suffix ".txt" to avoid that the file would be executed on the webserver
				$extension = get_filename_extension($file_name);
				if (substr($file_name, -6) == "tar.gz") { $extension = "tar.gz"; }
				$tempfilename = tempnam2($net2ftp_globals["application_tempdir"], "upload__", "." . $extension . ".txt");
				if ($tempfilename == false) { 
					// If you get this warning message, you've probably forgotten to chmod 777 the /temp directory
					echo "WARNING: File <b>$file_name</b> skipped: unable to create a temporary file on the webserver.<br />\n"; 
					@unlink($file_tmp_name); 
					continue;
				}

// Move the uploaded file
				$move_uploaded_file_result = move_uploaded_file($uploadedFilesArray["$j"]["tmp_name"], $tempfilename);
				if ($move_uploaded_file_result == false) { 
					echo "WARNING: File <b>$file_name</b> skipped: unable to move the uploaded file to the webserver's temporary directory.<br />\n"; 
					@unlink($file_tmp_name); 
					@unlink($tempfilename); 
					continue;
				}
				else {
					$moved_counter = $moved_counter + 1;
					$acceptedFilesArray["$moved_counter"] = $uploadedFilesArray["$j"]; // Copy all parameters for this file from the $uploadedFilesArray to the $acceptedFilesArray
					$acceptedFilesArray["$moved_counter"]["tmp_name"] = $tempfilename; // Overwrite the old temporary name by the new one
				}
	
			} // end for j

			flush();

		} // end if elseif


// ------------------------------------
// 3. Move the files from net2ftp's temporary directory to the FTP server.
// ------------------------------------
		if     (sizeof($acceptedFilesArray) == 0 && sizeof($uploadedFilesArray) != 0) { echo "WARNING: No files were accepted (see messages above), so nothing will be transferred to the FTP server.<br />\n"; }
		elseif (sizeof($acceptedFilesArray) > 0) {

// ------------------------------
// 3.1 Open connection
// ------------------------------

// Open connection
			echo __("Connecting to the FTP server") . "<br />\n";
			$conn_id = ftp_openconnection();
			if ($net2ftp_result["success"] == false) { 
				echo "ERROR: " . $net2ftp_result["errormessage"] . "<br />\n";
				return false;
			}

// ------------------------------
// For loop (loop over all the files)
// ------------------------------
			for ($k=1; $k<=sizeof($acceptedFilesArray); $k++)  {
				$file_name               = $acceptedFilesArray["$k"]["name"];
				$file_tmp_name           = $acceptedFilesArray["$k"]["tmp_name"];
				$file_size               = $acceptedFilesArray["$k"]["size"];
				$file_error              = $acceptedFilesArray["$k"]["error"];
				$file_absolute_directory = $acceptedFilesArray["$k"]["absolute_directory"];
		
				$ftpmode = ftpAsciiBinary($file_name);
				if ($ftpmode == FTP_ASCII)      { $printftpmode = "FTP_ASCII"; }
				elseif ($ftpmode == FTP_BINARY) { $printftpmode = "FTP_BINARY"; }

// ------------------------------
// 3.2 Within the for loop: create the subdirectory if needed
// ------------------------------
// Replace Windows-style backslashes \ by Unix-style slashes /
				$file_absolute_directory = str_replace("\\", "/", trim($file_absolute_directory));
				
// Get the names of the subdirectories by splitting the string using slashes /
				$file_subdirectories = explode("/", $file_absolute_directory);
				
// $targetdirectory contains the successive directories to be created
				$targetdirectory = $net2ftp_globals["directory"];

// Loop over sizeof()-1 because the last part is the filename itself:
				for ($m=0; $m<sizeof($file_subdirectories)-1; $m++) {
// Create the targetdirectory string
					$targetdirectory = glueDirectories($targetdirectory, $file_subdirectories[$m]);
// Check if the subdirectories exist
					if ($targetdirectory != "") {
						$result = @ftp_chdir($conn_id, $targetdirectory);
						if ($result == false) {
							$ftp_mkdir_result = ftp_mkdir($conn_id, $targetdirectory);
							if ($ftp_mkdir_result == false) { 
								echo "WARNING: Unable to create the directory <b>$targetdirectory</b>. The script will try to continue...<br />\n";
								continue;
							}
							echo "Directory $targetdirectory created.<br />\n";
						} // end if
						flush();
					} // end if
				} // end for m

// Store the $targetdirectory in the $acceptedFilesArray
				if ($targetdirectory != "" && $targetdirectory != "/") { 
					$acceptedFilesArray["$k"]["targetdirectory"] = $targetdirectory; 
				}

// ------------------------------
// 3.3 Within the for loop: put local file to remote file
// ------------------------------
				ftp_putfile($conn_id, "", $acceptedFilesArray["$k"]["tmp_name"], $acceptedFilesArray["$k"]["targetdirectory"], $acceptedFilesArray["$k"]["name"], $ftpmode, "move");
				if ($net2ftp_result["success"] == false) { 
					echo "WARNING: File <b>$file_name</b> skipped. Message: " . $net2ftp_result["errormessage"] . "<br />\n"; 
					setErrorVars(true, "", "", "", "");
					continue;
				} // end if
				else { echo "File <b>$file_name</b> transferred.<br />\n"; }
	
				flush();

			} // End for k

// ------------------------------
// 3.4 Close connection
// ------------------------------
			ftp_quit($conn_id);

		} // end if

	} // end if $screen == 2
	
} // end net2ftp_module_sendHttpHeaders

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
//	echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/upload/upload.js\"></script>\n";

} // end net2ftp_module_printJavascript

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
	
} // end net2ftp_module_printCssInclude

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

} // end net2ftp_module_printBodyOnload

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

// -------------------------------------------------------------------------
// Variables for screen 1
// -------------------------------------------------------------------------
	if ($net2ftp_globals["screen"] == 1) {

// Title
		$title = __("Upload directories and files using a Java applet");

// Next screen
		$nextscreen = 2;

// Form name, back and forward buttons
	$formname = "JavaUploadForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='browse';document.forms['" . $formname . "'].state2.value='main';document.forms['" . $formname . "'].submit();";

// Action URL
		$actionURL = printPHP_SELF("jupload");

// Maxima
		$maxFreeSpaceOnServer        = 5 * $net2ftp_settings["max_filesize"];
		$maxTotalRequestSize         = 5 * $net2ftp_settings["max_filesize"];
		$maxNumberFiles              = "500";
		$maxFilesPerRequest          = "100";
		$max_filesize                = $net2ftp_settings["max_filesize"];
		$max_filesize_net2ftp        = $net2ftp_settings["max_filesize"] / 1024;
		$max_upload_filesize_php     = @ini_get("upload_max_filesize");
		$max_execution_time          = @ini_get("max_execution_time");

// Print the output
		require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/manage.template.php");

	} // end if


// -------------------------------------------------------------------------
// Variables for screen 2
// -------------------------------------------------------------------------
//	elseif ($net2ftp_globals["screen"] == 2) {

	// THE OUTPUT IS GENERATED HIGHER UP, IN net2ftp_module_sendHttpHeaders().
	// This is because the Applet only takes text - no HTML.

//	} // end if

// -------------------------------------------------------------------------
// Print the output
// -------------------------------------------------------------------------
// Is done higher up, as for screen 1 a template is used, but not for screen 2.

} // End net2ftp_module_printBody

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>