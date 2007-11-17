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
//	echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/install/install.js\"></script>\n";

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
	$title = __("Install software packages");

// Form name, back and forward buttons
	$formname = "InstallForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='browse';document.forms['" . $formname . "'].state2.value='main';document.forms['" . $formname . "'].submit();";


// -------------------------------------------------------------------------
// Screen 1
// -------------------------------------------------------------------------
	if ($net2ftp_globals["screen"] == 1) {

// ----------------------------------------------
// Read the net2ftp installer script template $text
// ----------------------------------------------
		$templatefile = $net2ftp_globals["application_rootdir"] . "/modules/install/net2ftp_installer.txt";

		$handle = fopen($templatefile, "r"); // Open the local template file for reading only
		if ($handle == false) { 
			$errormessage = __("Unable to open the template file");
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}

		clearstatcache(); // for filesize

		$text = fread($handle, filesize($templatefile));
		if ($text == false) { 
			$errormessage = __("Unable to read the template file");
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}

		@fclose($handle);

// ----------------------------------------------
// Read the list of packages
// ----------------------------------------------

		$packagelistfile_net2ftp = "http://www.net2ftp.com/package_list.txt";
		$packagelistfile_local = $net2ftp_globals["application_rootdir"] . "/modules/install/package_list.txt";

// Get the list of packages from net2ftp.com
		$handle_net2ftp = @fopen($packagelistfile_net2ftp, "r");
		clearstatcache(); // for filesize
		$packagelist_net2ftp = @fread($handle_net2ftp, filesize($packagelistfile_net2ftp));
		@fclose($handle_net2ftp);

// If net2ftp.com can't be reached, get it from the local installation
		if ($packagelist_net2ftp == false) { 
			$handle_local = @fopen($packagelistfile_local, "r");
			clearstatcache(); // for filesize
			$packagelist_local = @fread($handle_local, filesize($packagelistfile_local));
			@fclose($handle_local);
		}

// Issue an error message if no list could be read
		if     ($packagelist_net2ftp != "") { $packagelist = $packagelist_net2ftp; }
		elseif ($packagelist_local   != "") { $packagelist = $packagelist_local; }
		else {
			$errormessage = __("Unable to get the list of packages");
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}

// ----------------------------------------------
// Security code
// Random key generator by goochivasquez -at- gmail (15-Apr-2005 11:53)
// ----------------------------------------------

// Random key generator
		$keychars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$length = 20;
		$security_code = "";
		for ($i=0;$i<$length;$i++) { $security_code .= substr($keychars,rand(1,strlen($keychars)),1); }

// Random key generator
		$keychars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$length = 5;
		$tempdir_extension = "";
		for ($i=0;$i<$length;$i++) { $tempdir_extension .= substr($keychars,rand(1,strlen($keychars)),1); }

		$tempdir_ftp = glueDirectories($net2ftp_globals["directory"], "net2ftp_temp_") . $tempdir_extension;

// ----------------------------------------------
// Replace certain values
// ----------------------------------------------
		$text = str_replace("NET2FTP_SECURITY_CODE",     $security_code, $text);
		$text = str_replace("NET2FTP_TEMPDIR_EXTENSION", $tempdir_extension, $text);
		$text = str_replace("NET2FTP_PACKAGELIST",       $packagelist, $text);
		$text = str_replace("NET2FTP_FTP_SERVER",        $net2ftp_globals["ftpserver"], $text);
		$text = str_replace("NET2FTP_FTPSERVER_PORT",    $net2ftp_globals["ftpserverport"], $text);
		$text = str_replace("NET2FTP_USERNAME",          $net2ftp_globals["username"], $text);
		$text = str_replace("NET2FTP_DIRECTORY",         $net2ftp_globals["directory"], $text);

// ----------------------------------------------
// Open connection
// ----------------------------------------------
		setStatus(2, 10, __("Connecting to the FTP server"));
		$conn_id = ftp_openconnection();
		if ($conn_id == false) { return false; }

// ----------------------------------------------
// Create temporary /net2ftp_temp directory
// ----------------------------------------------
		setStatus(4, 10, __("Creating a temporary directory on the FTP server"));
		ftp_newdirectory($conn_id, $tempdir_ftp);
		if ($net2ftp_result["success"] == false) { setErrorVars(true, "", "", "", ""); }

// ----------------------------------------------
// Chmodding the temporary /net2ftp_temp directory to 777
// ----------------------------------------------
		setStatus(6, 10, __("Setting the permissions of the temporary directory"));
		$sitecommand = "chmod 0777 " . $tempdir_ftp;
		$ftp_site_result = @ftp_site($conn_id, $sitecommand);

// ----------------------------------------------
// Put a .htaccess in the /net2ftp_temp directory to avoid anyone else reading the contents it
// (Works only for Apache web servers...)
// ----------------------------------------------
		ftp_writefile($conn_id, $tempdir_ftp, ".htaccess", "deny from all");
		if ($net2ftp_result["success"] == false) { setErrorVars(true, "", "", "", ""); }

// ----------------------------------------------
// Write the net2ftp installer script to the FTP server
// ----------------------------------------------
		setStatus(8, 10, __("Copying the net2ftp installer script to the FTP server"));
		ftp_writefile($conn_id, $net2ftp_globals["directory"], "net2ftp_installer.php", $text);
		if ($net2ftp_result["success"] == false) { return false; }

// ----------------------------------------------
// Close connection
// ----------------------------------------------
		ftp_closeconnection($conn_id);

// ----------------------------------------------
// Variables for screen 1
// ----------------------------------------------

// URL to the installer script
		$list_files[1]["dirfilename_js"] = "net2ftp_installer.php?security_code=" . $security_code;
		$ftp2http_result = ftp2http($net2ftp_globals["directory"], $list_files, "no");
		$net2ftp_installer_url = $ftp2http_result[1];

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