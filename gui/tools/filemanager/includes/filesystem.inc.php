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

function ftp_openconnection() {

// --------------
// This function opens an ftp connection
// --------------

// Global variables
	global $net2ftp_globals;

// Check if the FTP module of PHP is installed
	if (function_exists("ftp_connect") == false) { 
		$errormessage = __("The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />");
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Decrypt password
	if (isset($_SESSION["net2ftp_password_encrypted_" . $net2ftp_globals["ftpserver"] . $net2ftp_globals["username"]]) == true) { 
		$net2ftp_password = decryptPassword($_SESSION["net2ftp_password_encrypted_" . $net2ftp_globals["ftpserver"] . $net2ftp_globals["username"]]); 
	}
	else { 
		$net2ftp_password = decryptPassword($net2ftp_globals["password_encrypted"]); 
	}

// Check if port nr is filled in
	if ($net2ftp_globals["ftpserverport"] < 1 || $net2ftp_globals["ftpserverport"] > 65535 || $net2ftp_globals["ftpserverport"] == "") { $net2ftp_globals["ftpserverport"] = 21; }

// Set up basic connection
	$ftp_connect = "ftp_connect";
	if ($net2ftp_globals["sslconnect"] == "yes" && function_exists("ftp_ssl_connect")) { $ftp_connect = "ftp_ssl_connect"; }

	$conn_id = $ftp_connect($net2ftp_globals["ftpserver"], $net2ftp_globals["ftpserverport"]);
	if ($conn_id == false) {
		$errormessage = __("Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />", $net2ftp_globals["ftpserver"], $net2ftp_globals["ftpserverport"]); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Login with username and password
	$login_result = ftp_login($conn_id, $net2ftp_globals["username"], $net2ftp_password);
	if ($login_result == false) { 
		$errormessage = __("Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />", $net2ftp_globals["ftpserver"], $net2ftp_globals["username"]);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Set passive mode
	if ($net2ftp_globals["passivemode"] == "yes") { 
		$success = ftp_pasv($conn_id, TRUE); 
		if ($success == false) { 
			$errormessage = __("Unable to switch to the passive mode on FTP server <b>%1\$s</b>.", $net2ftp_globals["ftpserver"]);
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
	}

// Get the system type
//	$net2ftp_globals["systype"] = ftp_systype($conn_id);

// Return the connection ID
	return $conn_id;

} // End function ftp_openconnection

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_openconnection2() {

// --------------
// This function opens an ftp connection to the secondary FTP server, to which
// files can be copied or moved.
// --------------

// Global variables
	global $net2ftp_globals;

// Check if the FTP module of PHP is installed
	if (function_exists("ftp_connect") == false) { 
		$errormessage = __("The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />");
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Check if port nr is correct
	if ($net2ftp_globals["ftpserverport2"] < 1 || $net2ftp_globals["ftpserverport2"] > 65535 || $net2ftp_globals["ftpserverport2"] == "") { $net2ftp_globals["ftpserverport2"] = 21; }

// Set up basic connection
	$conn_id = ftp_connect($net2ftp_globals["ftpserver2"], $net2ftp_globals["ftpserverport2"]);
	if ($conn_id == false) { 
		$errormessage = __("Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />", $net2ftp_globals["ftpserver2"], $net2ftp_globals["ftpserverport2"]);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Login with username and password
	$login_result = ftp_login($conn_id, $net2ftp_globals["username2"], $net2ftp_globals["password2"]);
	if ($login_result == false) { 
		$errormessage = __("Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />", $net2ftp_globals["ftpserver2"], $net2ftp_globals["username2"]);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Set passive mode
	if ($net2ftp_globals["passivemode"] == "yes") { 
		$success = ftp_pasv($conn_id, TRUE); 
		if ($success == false) { 
			$errormessage = __("Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>.", $net2ftp_globals["ftpserver2"]); 
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
	}

// Get the system type
//	$net2ftp_globals["systype2"] = ftp_systype($conn_id);

// Return the connection ID
	return $conn_id;

} // End function ftp_openconnection2

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_closeconnection($conn_id) {

// --------------
// This function closes an ftp connection
// --------------

	ftp_quit($conn_id);

} // End function ftp_closeconnection

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************






// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_rename2($conn_id, $directory, $entry, $newName) {

// --------------
// This function renames a directory
// --------------

	$old = glueDirectories($directory, $entry);
	$new = glueDirectories($directory, $newName);

	$ftp_rename_result = ftp_rename($conn_id, $old, $new);
	if ($ftp_rename_result == false) { 
		$errormessage = __("Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>", $old, $new);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

} // End function ftp_rename2

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************







// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_chmod2($conn_id, $directory, $list, $divelevel) {

// --------------
// This function chmods a directory or file
//
// $list[$i]["dirorfile"] contains d or - which indicates if the entry is a directory or a file
// $list[$i]["dirfilename"] contains the name of the entry
// $list[$i]["chmodoctal"] contains the 3-digit nr 
//
// If the entry is a directory, $list[$i]["chmod_subdirectories"] and $list[$i]["chmod_subfiles"] are "yes" if 
// the subdirectories and files within the chmodded directory should also be chmodded
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_result, $net2ftp_output;

// -------------------------------------------------------------------------
// Initialization
// -------------------------------------------------------------------------
	if ($divelevel == 0) { 
		$net2ftp_output["ftp_chmod2"][] = "<ul>";
	}

// -------------------------------------------------------------------------
// For all directories
// -------------------------------------------------------------------------
	for ($i=1; $i<=$list["stats"]["directories"]["total_number"]; $i=$i+1) {


		$currentdirectory = glueDirectories($directory, $list["directories"][$i]["dirfilename"]);

// ------------------------------------
// Chmod the directory
// If the $divelevel == 0 then chmod it in any case as it is the top directory
// If the $divelevel > 0  then chmod it only if chmod_subdirectories == "yes"
// ------------------------------------
		if ($list["directories"][$i]["chmod_subdirectories"] == "yes" || $divelevel == 0) {
			$sitecommand = "chmod 0" . $list["directories"][$i]["chmodoctal"] . " $currentdirectory";
			$success1 = ftp_site($conn_id, $sitecommand);
			if ($success1 == false) { 
				$errormessage =  __("Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers.", $sitecommand); 
				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false; 
			}
			elseif ($success1 == true)  { 
				$net2ftp_output["ftp_chmod2"][] = __("Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>", $currentdirectory, $list["directories"][$i]["chmodoctal"]); 
			}
		}

// ------------------------------------
// If the subdirectories and files within the current directory also have to be chmodded...
// ------------------------------------
		if ($list["directories"][$i]["chmod_subdirectories"] == "yes" || $list["directories"][$i]["chmod_subfiles"] == "yes") {

// Get a new list
		$newlist = ftp_getlist($conn_id, $currentdirectory);
		if ($net2ftp_result["success"] == false) { return false; }

// Add information to the list
		for ($j=1; $j<=$newlist["stats"]["directories"]["total_number"]; $j++) {
			$newlist["directories"][$j]["chmodoctal"]           = $list["directories"][$i]["chmodoctal"];
			$newlist["directories"][$j]["chmod_subdirectories"] = $list["directories"][$i]["chmod_subdirectories"];
			$newlist["directories"][$j]["chmod_subfiles"]       = $list["directories"][$i]["chmod_subfiles"];
		}
		for ($j=1; $j<=$newlist["stats"]["files"]["total_number"]; $j++) {
			$newlist["files"][$j]["chmodoctal"]           = $list["directories"][$i]["chmodoctal"];
			$newlist["files"][$j]["chmod_subdirectories"] = $list["directories"][$i]["chmod_subdirectories"];
			$newlist["files"][$j]["chmod_subfiles"]       = $list["directories"][$i]["chmod_subfiles"];
		}

// Call the function recursively
			$net2ftp_output["ftp_chmod2"][] = __("Processing entries within directory <b>%1\$s</b>:", $currentdirectory);
			$net2ftp_output["ftp_chmod2"][] = "<ul>";

			$newdivelevel = $divelevel + 1;
			ftp_chmod2($conn_id, $currentdirectory, $newlist, $newdivelevel);

			$net2ftp_output["ftp_chmod2"][] = "</ul>";
			
		} // end if subdirectories and files

	} // end for list_directories

// -------------------------------------------------------------------------
// Process the files
// -------------------------------------------------------------------------
	for ($i=1; $i<=$list["stats"]["files"]["total_number"]; $i=$i+1) {

		$currentfile = glueDirectories($directory, $list["files"][$i]["dirfilename"]);

// Chmod the files
// If the $divelevel == 0 then chmod them in any case as they are the top files
// If the $divelevel > 0  then chmod them only if chmod_subfiles == "yes"

		if ($list["files"][$i]["chmod_subfiles"] == "yes" || $divelevel == 0) {
			$sitecommand = "chmod 0" . $list["files"][$i]["chmodoctal"] . " $currentfile";
			$success2 = ftp_site($conn_id, $sitecommand);
			if ($success2 == false) { 
				$errormessage =  __("Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers.", $sitecommand); 
				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false; 
			}
			elseif ($success2 == true)  { 
				$net2ftp_output["ftp_chmod2"][] = __("File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>", $currentfile, $list["files"][$i]["chmodoctal"]);
			}
		}

	} // end for list_files

// Print message
	if ($divelevel == 0) { $net2ftp_output["ftp_chmod2"][] = __("All the selected directories and files have been processed."); }

} // End function ftp_chmod2

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************







// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_rmdir2($conn_id, $directory) {

// --------------
// This function deletes a directory
// --------------

// Replace \' by \\' to be able to delete directories with names containing \'
	$directory = str_replace("\'", "\\\'", $directory);

// QUICK WAY TO DELETE A DIRECTORY
	$success1 = ftp_rmdir($conn_id, $directory);

// THE FTP_RMDIR MAY NOT WORK WITH ALL FTP SERVERS, AS DESCRIBED ON THE FORUM
// http://www.net2ftp.org/forums/index.php?showtopic=658
// Solution: to delete /dir/parent/dirtodelete
//    1. chdir to the parent directory  /dir/parent
//    2. delete the subdirectory, but use only its name (dirtodelete), not the full path (/dir/parent/dirtodelete)

	if ($success1 == false) {
		ftp_chdir($conn_id, upDir($directory));
		$parts = explode("/", $directory);
		$lastpartnr = sizeof($parts)-1;
		$success2 = ftp_rmdir($conn_id, $parts[$lastpartnr]);
		if ($success2 == false) { 
			$errormessage = __("Unable to delete the directory <b>%1\$s</b>", $directory); 
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
	}

} // End function ftp_rmdir2

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_delete2($conn_id, $file) {

// --------------
// This function deletes a file
// --------------

// Replace \' by \\' to be able to delete directories with names containing \'
	$file = str_replace("\'", "\\\'", $file);

	$success1 = ftp_delete($conn_id, $file);

// THE FTP_RMDIR MAY NOT WORK WITH ALL FTP SERVERS, AS DESCRIBED ON THE FORUM
// http://www.net2ftp.org/forums/index.php?showtopic=658
// Solution: to delete /dir/parent/dirtodelete
//    1. chdir to the parent directory  /dir/parent
//    2. delete the subdirectory, but use only its name (dirtodelete), not the full path (/dir/parent/dirtodelete)

	if ($success1 == false) {
		ftp_chdir($conn_id, upDir($file));
		$parts = explode("/", $file);
		$lastpartnr = sizeof($parts)-1;
		$success2 = ftp_delete($conn_id, $parts[$lastpartnr]);
		if ($success2 == false) { 
			$errormessage = __("Unable to delete the file <b>%1\$s</b>", $file); 
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
	}

} // End function ftp_delete2

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_newdirectory($conn_id, $directory) {

// --------------
// This function creates a new remote directory
// --------------

	$success1 = ftp_mkdir($conn_id, $directory);
	if ($success1 == false) { 
		$errormessage = __("Unable to create the directory <b>%1\$s</b>", $directory);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

} // End function ftp_newdirectory

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_readfile($conn_id, $directory, $file) {

// --------------
// This function opens a remote text file and it returns a string
// It can be used stand-alone (with conn_id = "") and then a new connection is opened
// Else it can also be used in a loop (with conn_id != false) and then the existing connection is opened
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_result;

	$source = glueDirectories($directory, $file);

// Step 1/4: Create a temporary filename
	$tempfilename = tempnam($net2ftp_globals["application_tempdir"], "read__");
	if ($tempfilename == false)  { 
		@unlink($tempfilename); 
		$errormessage = __("Unable to create the temporary file. Check the permissions of the %1\$s directory.", $net2ftp_globals["application_tempdir"]);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}
	registerTempfile("register", $tempfilename);

// Step 2/4: Copy remote file to the temporary file
// Open connection if needed
	if ($conn_id == "") {
		$conn_id = ftp_openconnection();
		if ($net2ftp_result["success"] == false)  { 
			@unlink($tempfilename); 
			return false;
		}
		$leave_conn_open = "no";
	}
	else {
		$leave_conn_open = "yes";
	}

// Check the consumption
	if(checkConsumption() == false) {
		$errormessage = __("Daily limit reached: the file <b>%1\$s</b> will not be transferred", $source);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Get file
	$ftpmode = ftpAsciiBinary($source);

	$ftp_get_result = ftp_get($conn_id, "$tempfilename", "$source", $ftpmode);
	if ($ftp_get_result == false) { 
		@unlink($tempfilename); 
		$errormessage = __("Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />", $source, $tempfilename, $net2ftp_globals["application_tempdir"]);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Add the filesize to the global consumption variables
	addConsumption(filesize($tempfilename), 0);

// Close connection
	if ($leave_conn_open == "no") { ftp_closeconnection($conn_id); }

// Step 3/4: Read temporary file
	$string = local_readfile($tempfilename);
	if ($net2ftp_result["success"] == false) { return false; }

// Step 4/4: Delete temporary file
	$unlink_result = @unlink($tempfilename);
	if ($unlink_result == false) {  
		$errormessage = __("Unable to delete the temporary file"); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	} 
	registerTempfile("unregister", $tempfilename);

// Change CarriageReturn+LineFeed by LineFeed
// This is because some FTP servers replace LineFeed by CarriageReturn+LineFeed
//	if ($ftpmode == FTP_ASCII) {
//		$string = standardize_eol($string);
//	}

	return $string;

} // End function ftp_readfile

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************







// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_writefile($conn_id, $directory, $file, $string) {

// --------------
// This function writes a string to a remote text file.
// If it already existed, it will be overwritten without asking for a confirmation.
// It can be used stand-alone (with conn_id = "") and then a new connection is opened
// Else it can also be used in a loop (with conn_id != false) and then the existing connection is opened
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_result;
	$target = glueDirectories($directory, $file);

	$ftpmode = ftpAsciiBinary($file);

// Change CarriageReturn+LineFeed by LineFeed
// This is because some FTP servers replace LineFeed by CarriageReturn+LineFeed
//	if ($ftpmode == FTP_ASCII) {
//		$string = standardize_eol($string);
//	}

// Step 1/4: Create a temporary filename
	$tempfilename = tempnam($net2ftp_globals["application_tempdir"], "write__");
	if ($tempfilename == false)  { 
		@unlink($tempfilename); 
		$errormessage = __("Unable to create the temporary file. Check the permissions of the %1\$s directory.", $net2ftp_globals["application_tempdir"]); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}
	registerTempfile("register", $tempfilename);

// Step 2/4: Write the string to the temporary file
	local_writefile($tempfilename, $string);
	if ($net2ftp_result["success"] == false) { return false; }

// Step 3/4: Copy temporary file to remote file
// Open connection if needed
	if ($conn_id == "") {
		$conn_id = ftp_openconnection();
		if ($net2ftp_result["success"] == false) {
			@unlink($tempfilename);
			return false;
		}
		$leave_conn_open = "no";
	}

// Add the filesize to the global consumption variables
	addConsumption(filesize($tempfilename), 0);

// Check the consumption
	if(checkConsumption() == false) {
		addConsumption((-1)*filesize($tempfilename), 0);
		$errormessage = __("Daily limit reached: the file <b>%1\$s</b> will not be transferred", $target);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Put file
	// The FTP mode is calculated a few lines above
	//$ftpmode = ftpAsciiBinary($file);

	$success3 = ftp_put($conn_id, $target, $tempfilename, $ftpmode);
	if ($success3 == false) { 
		@unlink($tempfilename); 
		$errormessage = __("Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory.", $target);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Close connection
	if ($leave_conn_open == "no") { ftp_closeconnection($conn_id); }

// Step 4/4: Delete temporary file
	$success4 = @unlink($tempfilename);
	if ($success4 == false) { 
		$errormessage = __("Unable to delete the temporary file"); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	} 
	registerTempfile("unregister", $tempfilename);

} // End function ftp_writefile

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_copymovedelete($conn_id_source, $conn_id_target, $list, $copymovedelete, $divelevel) {

// --------------
// This function copies/moves/deletes directories and files from an FTP server to the same
// or another FTP server. Files are first transferred from the source FTP server to the webserver, 
// and then transferred to the target FTP server.
//
// $list[$i]["dirorfile"] contains d or - which indicates if its a directory or a file
// $list[$i]["dirfilename"] contains the entry name
// $list[$i]["sourcedirectory"] contains the source directory
// $list[$i]["targetdirectory"] contains the target directory
// $list[$i]["newname"] contains the new name if divelevel = 0; for deeper levels the newname is the entry name itself
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result, $net2ftp_output;

// -------------------------------------------------------------------------
// Initialization
// -------------------------------------------------------------------------
	if ($divelevel == 0) {
		$net2ftp_output["ftp_copymovedelete"][] = "<ul>";
	}

// Total number of directories and files on level 0 (chosen by the user)
	$total_dirs_files = $list["stats"]["directories"]["total_number"] + $list["stats"]["files"]["total_number"];

// -------------------------------------------------------------------------
// For all directories
// -------------------------------------------------------------------------
	for ($i=1; $i<=$list["stats"]["directories"]["total_number"]; $i=$i+1) {

// Set the status
		$message = __("Processing entry %1\$s", javascriptEncode2($list["directories"][$i]["dirfilename"])) . " ($i/$total_dirs_files)";
		setStatus($i, $total_dirs_files, $message);

// Source and target
		$source = glueDirectories($list["directories"][$i]["sourcedirectory"], $list["directories"][$i]["dirfilename"]);
		if ($copymovedelete == "copy" || $copymovedelete == "move") {
			if ($divelevel > 0) { $target = glueDirectories($list["directories"][$i]["targetdirectory"], $list["directories"][$i]["dirfilename"]); } // Subdirectories keep their original names
			else                { $target = glueDirectories($list["directories"][$i]["targetdirectory"], $list["directories"][$i]["newname"]); }     // First-level user-selected directories can have been renamed 
		}
		else {
			$target = "";
		}

// Print starting message
		$net2ftp_output["ftp_copymovedelete"][] = __("Processing directory <b>%1\$s</b>", $source);
		$net2ftp_output["ftp_copymovedelete"][] = "<ul>";


// Check that the targetdirectory is not a subdirectory of the sourcedirectory
		if (($conn_id_source == $conn_id_target) && ($copymovedelete != "delete") && (isSubdirectory($source, $target) == true)) { 
			$net2ftp_output["ftp_copymovedelete"][] = __("The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped", $target, $source); 
			$net2ftp_output["ftp_copymovedelete"][] = "</ul>";
			continue;			
		}

// Check if the directory contains a banned keyword
// If banned keyword - copy: continue
// If banned keyword - move: abort
		if ($list["directories"][$i]["selectable"] == "banned_keyword") { 
			if ($copymovedelete == "copy") {
				$net2ftp_output["ftp_copymovedelete"][] = __("The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped", $source);
				$net2ftp_output["ftp_copymovedelete"][] = "</ul>";
				continue;
			}
			elseif ($copymovedelete == "move") {
				$net2ftp_output["ftp_copymovedelete"][] = __("The directory <b>%1\$s</b> contains a banned keyword, aborting the move", $source);
				$net2ftp_output["ftp_copymovedelete"][] = "</ul>";
				return false;
			}
		}


// Create the targetdirectory
		if ($copymovedelete == "copy" || $copymovedelete == "move") {
			$success1 = ftp_mkdir($conn_id_target, $target);
			if ($success1 == false) { $net2ftp_output["ftp_copymovedelete"][] = __("Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process...", $target); }
			else                    { $net2ftp_output["ftp_copymovedelete"][] = __("Created target subdirectory <b>%1\$s</b>", $target); }
		}

// Get a new list
		$newlist = ftp_getlist($conn_id_source, $source);
		if ($net2ftp_result["success"] == false) { 
			$net2ftp_output["ftp_copymovedelete"][] = __("The directory <b>%1\$s</b> could not be selected, so this directory will be skipped", $source);
			$net2ftp_output["ftp_copymovedelete"][] = "</ul>";
			setErrorVars(true, "", "", "", ""); 
			continue;
		}

// Add information to the list
		for ($j=1; $j<=$newlist["stats"]["directories"]["total_number"]; $j++) {
			$newlist["directories"][$j]["sourcedirectory"] = $source;
			$newlist["directories"][$j]["targetdirectory"] = $target;
		}
		for ($j=1; $j<=$newlist["stats"]["files"]["total_number"]; $j++) {
			$newlist["files"][$j]["sourcedirectory"] = $source;
			$newlist["files"][$j]["targetdirectory"] = $target;
		}

// Call the function recursively
		$newdivelevel = $divelevel + 1;
		$ftp_copymovedelete_result = ftp_copymovedelete($conn_id_source, $conn_id_target, $newlist, $copymovedelete, $newdivelevel);

// Delete the source directory
// (Only if there were no problems in the recursive call to ftp_copymovedelete() above)
		if ($ftp_copymovedelete_result == true && ($copymovedelete == "move" || $copymovedelete == "delete")) { 
			ftp_rmdir2($conn_id_source, $source);
 			if ($net2ftp_result["success"] == false) { 
				setErrorVars(true, "", "", "", ""); 
				$net2ftp_output["ftp_copymovedelete"][] = __("Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty", $source); 
			}
			else { 
				$net2ftp_output["ftp_copymovedelete"][] = __("Deleted subdirectory <b>%1\$s</b>", $source);
			}
		}

// Print ending message
		$net2ftp_output["ftp_copymovedelete"][] = __("Processing of directory <b>%1\$s</b> completed", $source);
		$net2ftp_output["ftp_copymovedelete"][] = "</ul>";

	} // end for list_directories

// -------------------------------------------------------------------------
// Process the files
// -------------------------------------------------------------------------
	for ($i=1; $i<=$list["stats"]["files"]["total_number"]; $i=$i+1) {

// Set the status
		$j = $list["stats"]["directories"]["total_number"] + $i;
		$message = __("Processing entry %1\$s", javascriptEncode2($list["files"][$i]["dirfilename"])) . " ($j/$total_dirs_files)";
		setStatus($j, $total_dirs_files, $message);

// ------------------------------------
// Copy and move
// ------------------------------------
		if ($copymovedelete == "copy" || $copymovedelete == "move") {

// Source and target
		$source = glueDirectories($list["files"][$i]["sourcedirectory"], $list["files"][$i]["dirfilename"]);
		if (isset($list["files"][$i]["newname"])) { $target = glueDirectories($list["files"][$i]["targetdirectory"], $list["files"][$i]["newname"]); }
		else                                      { $target = glueDirectories($list["files"][$i]["targetdirectory"], $list["files"][$i]["dirfilename"]); }

// Check that the target is not the same as the source file
			if (($conn_id_source == $conn_id_target) && ($target == $source)) { 
				$net2ftp_output["ftp_copymovedelete"][] = __("The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped", $source);
				continue;
			}

// Check if the file contains a banned keyword, and if it is not bigger than the limit
// If banned keyword or too big - copy: continue with the other files
// If banned keyword or too big - move: abort
			if ($list["files"][$i]["selectable"] == "banned_keyword") { 
				if ($copymovedelete == "copy") {
					$net2ftp_output["ftp_copymovedelete"][] = __("The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped", $source);
					continue;
				}
				elseif ($copymovedelete == "move") {
					$net2ftp_output["ftp_copymovedelete"][] = __("The file <b>%1\$s</b> contains a banned keyword, aborting the move", $source);
					return false;
				}
			}
			elseif ($list["files"][$i]["selectable"] == "too_big") { 
				if ($copymovedelete == "copy") {
					$net2ftp_output["ftp_copymovedelete"][] = __("The file <b>%1\$s</b> is too big to be copied, so this file will be skipped", $source);
					continue;
				}
				elseif ($copymovedelete == "move") {
					$net2ftp_output["ftp_copymovedelete"][] = __("The file <b>%1\$s</b> is too big to be moved, aborting the move", $source);
					return false;
				}
			}

// Get file from remote sourcedirectory to local temp directory
// Don't delete the source file yet
			$localtargetdir = $net2ftp_globals["application_tempdir"];
			$localtargetfile = $list["files"][$i]["dirfilename"] . ".txt";
			$remotesourcedir = $list["files"][$i]["sourcedirectory"];
			$remotesourcefile = $list["files"][$i]["dirfilename"];
			$ftpmode = ftpAsciiBinary($list["files"][$i]["dirfilename"]);
			$copymove = "copy";

			ftp_getfile($conn_id_source, $localtargetdir, $localtargetfile, $remotesourcedir, $remotesourcefile, $ftpmode, $copymove);
			if ($net2ftp_result["success"] == false) { 
				setErrorVars(true, "", "", "", "");
				if ($copymovedelete == "copy") { 
					$net2ftp_output["ftp_copymovedelete"][] = __("Unable to copy the file <b>%1\$s</b>", $list["files"][$i]["dirfilename"]); 
					continue; 
				}
				elseif ($copymovedelete == "move") { 
					$net2ftp_output["ftp_copymovedelete"][] = __("Unable to move the file <b>%1\$s</b>, aborting the move", $list["files"][$i]["dirfilename"]); 
					return false;
				}
			}

// Put file from local temp directory to remote targetdirectory
// Delete the temporary file
			$localsourcedir = $net2ftp_globals["application_tempdir"];
			$localsourcefile = $list["files"][$i]["dirfilename"] . ".txt";
			$remotetargetdir = $list["files"][$i]["targetdirectory"];
			if (isset($list["files"][$i]["newname"])) { $remotetargetfile  = $list["files"][$i]["newname"]; }
			else                                      { $remotetargetfile  = $list["files"][$i]["dirfilename"]; }
			$copymove = "move";

			ftp_putfile($conn_id_target, $localsourcedir, $localsourcefile, $remotetargetdir, $remotetargetfile, $ftpmode, $copymove);
			if ($net2ftp_result["success"] == false) { 
				setErrorVars(true, "", "", "", "");
				if ($copymovedelete == "copy") { 
					$net2ftp_output["ftp_copymovedelete"][] = __("Unable to copy the file <b>%1\$s</b>", $list["files"][$i]["dirfilename"]); 
					continue; 
				}
				elseif ($copymovedelete == "move") { 
					$net2ftp_output["ftp_copymovedelete"][] = __("Unable to move the file <b>%1\$s</b>, aborting the move", $list["files"][$i]["dirfilename"]); 
					return false;
				}
			}

// Copy: if the operation is successful, print a message
			elseif ($net2ftp_result["success"] == true && $copymovedelete == "copy") { 
				$net2ftp_output["ftp_copymovedelete"][] = __("Copied file <b>%1\$s</b>", $list["files"][$i]["dirfilename"]); 
			}

// Move: only if the operation is successful, delete the source file
			elseif ($copymovedelete == "move") { 
				$remotesource = glueDirectories($list["files"][$i]["sourcedirectory"], $list["files"][$i]["dirfilename"]);

				ftp_delete2($conn_id_source, $remotesource);
				if ($net2ftp_result["success"] == false) { 
					setErrorVars(true, "", "", "", ""); 
					$net2ftp_output["ftp_copymovedelete"][] = __("Unable to move the file <b>%1\$s</b>", $list["files"][$i]["dirfilename"]);
				}
				else { 
					$net2ftp_output["ftp_copymovedelete"][] = __("Moved file <b>%1\$s</b>", $list["files"][$i]["dirfilename"]); 
				}
			}

		} // end copy or move

// ------------------------------------
// Delete
// ------------------------------------
		elseif ($copymovedelete == "delete") {
			$remotesource = glueDirectories($list["files"][$i]["sourcedirectory"], $list["files"][$i]["dirfilename"]);

			ftp_delete2($conn_id_source, $remotesource);
			if ($net2ftp_result["success"] == false) { 
				setErrorVars(true, "", "", "", ""); 
				$net2ftp_output["ftp_copymovedelete"][] = __("Unable to delete the file <b>%1\$s</b>", $list["files"][$i]["dirfilename"]); 
				continue;
			}
			else { 
				$net2ftp_output["ftp_copymovedelete"][] = __("Deleted file <b>%1\$s</b>", $list["files"][$i]["dirfilename"]); 
			}

		} // end delete

	} // end for list_files

	if ($divelevel == 0) { $net2ftp_output["ftp_copymovedelete"][] = "</ul>"; }

// Print message
	if ($divelevel == 0) { $net2ftp_output["ftp_copymovedelete"][] = __("All the selected directories and files have been processed."); }

	return true;

} // End function ftp_copymovedelete

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_processfiles($dowhat, $conn_id, $directory, $list, $options, $result, $divelevel) {

// --------------
// This function does something with files (get size, find string, ...)
// The $list contains both directories and files. The files are simply processed; the 
// directories are parsed recursively.
//
// $list[$i]["dirorfile"] contains d or - which indicates if the entry is a directory or a file
// $list[$i]["dirfilename"] contains the name of the entry
// $list[$i]["size"] contains the size of the entry
// 
// OPTIONS:
// if ($dowhat == "calculatesize") then
// 	$options = array()						doesn't contain anything
// if ($dowhat == "findstring") then
// 	$options["string"] 						a string
//	$options["case_sensitive"] 					blank or yes
//	$options["filename"] 						a filename with possible wildcard character * (it should match this preg_match regular expression: "/^[a-zA-Z0-9_ *-]*$/")
//	$options["size_from"], $options["size_to"] 		a number (in Bytes)
//	$options["modified_from"], $options["modified_to"]	unix timestamps of the modification dates
//
// RESULT:
// if ($dowhat == "calculatesize") then
// 	$result["size"]
//	$result["skipped"]
// if ($dowhat == "findstring") then
// 	$result[$k]["directory"] contains the directory
// 	$result[$k]["dirfilename"] contains the filename
// 	$result[$k]["line"] contains the line nr 
//
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_result;

// -------------------------------------------------------------------------
// Initialization
// -------------------------------------------------------------------------
	if ($divelevel == 0) { 

	}

// -------------------------------------------------------------------------
// For all directories
// -------------------------------------------------------------------------

	for ($i=1; $i<=$list["stats"]["directories"]["total_number"]; $i=$i+1) {

		$currentdirectory = glueDirectories($directory, $list["directories"][$i]["dirfilename"]);

// Check if the directory contains a banned keyword
		if ($list["directories"][$i]["selectable"] != "ok") { continue; }

// Get a new list
		$newlist = ftp_getlist($conn_id, $currentdirectory);
		if ($net2ftp_result["success"] == false) { return false; }

// Call the function recursively
		$newdivelevel = $divelevel + 1;
		$result = ftp_processfiles($dowhat, $conn_id, $currentdirectory, $newlist, $options, $result, $newdivelevel);
		if ($net2ftp_result["success"] == false) { return false; }

	} // end for list_directories

// -------------------------------------------------------------------------
// Process the files
// -------------------------------------------------------------------------
	for ($i=1; $i<=$list["stats"]["files"]["total_number"]; $i=$i+1) {

// -------------------------------
// Calculate size
// -------------------------------
		if ($dowhat == "calculatesize") {
// Check if the size information is entered
// Check also if the size is numeric
			if (isset($list["files"][$i]["size"]) && is_numeric($list["files"][$i]["size"])) { $result["size"]    = $result["size"] + $list["files"][$i]["size"]; }
			else                                                                             { $result["skipped"] = $result["skipped"] + 1; }
		} // end if calculatesize

// -------------------------------
// Find string
// -------------------------------
		elseif ($dowhat == "findstring") {

// Check that the file is smaller than the maximum file size that can be processed with net2ftp
			if ($list["files"][$i]["selectable"] != "ok") { 
				continue;
			}

// Check that the file is within the limits indicated on the selection screen
			if ($list["files"][$i]["size"] < $options["size_from"] || $list["files"][$i]["size"] > $options["size_to"]) { 
//				echo "File $i skipped";
				continue;
			}

// Check modification date (if that data is returned by the FTP server in the correct format)
			$mtime_file = strtotime($list["files"][$i]["mtime"]);
			// If strtotime cannot interprete the data returned by the FTP server it returns -1
			if (($mtime_file != -1) && (($mtime_file < $options["modified_from"]) || ($mtime_file > $options["modified_to"]))) { continue; }

// Check the filename
			$pattern = "/^" . $options["filename"] . "$/i"; // i at the end is for a case-insensitive match
			if (preg_match($pattern, $list["files"][$i]["dirfilename"]) == 0) { continue; }

// Read the file
			$text = ftp_readfile("", $directory, $list["files"][$i]["dirfilename"]);

			// If the file could not be read correctly, continue to the next one
			if ($net2ftp_result["success"] == false) { setErrorVars(true, "", "", "", ""); continue; }
			elseif ($text == "") { continue; }

// Split the file in an array, each element of the array containing one line of the file
			$text_lines = explode_lines($text);

// For each line, check if the string occurs
			for ($line=0; $line<sizeof($text_lines); $line++) {

// STRSTR AND STRISTR
				if ($options["case_sensitive"] == "yes") { $found = strstr($text_lines[$line], $options["string"]); }
				else                                     { $found = stristr($text_lines[$line], $options["string"]); }

				if ($found != false) {
					$tempresult["directory"]        = $directory;
					$tempresult["directory_html"]   = htmlEncode2($directory);
					$tempresult["directory_js"]     = javascriptEncode2($directory);
					$tempresult["dirfilename"]      = $list["files"][$i]["dirfilename"];
					$tempresult["dirfilename_html"] = $list["files"][$i]["dirfilename_html"];
					$tempresult["dirfilename_js"]   = $list["files"][$i]["dirfilename_js"];
					$tempresult["line"]             = $line+1; // $text_lines[0] contains the line 1, etc...
					array_push($result, $tempresult);
				}

			} // end for 

		} // end if findstring

	} // end for list_files

	return $result;

} // End function ftp_processfiles

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_getfile($conn_id, $localtargetdir, $localtargetfile, $remotesourcedir, $remotesourcefile, $ftpmode, $copymove) {

// --------------
// This function copies or moves a remote file to a local file
// $ftpmode is used to specify whether the file is to be transferred in ASCII or BINARY mode
// $copymove is used to specify whether to delete (move) or not (copy) the local source
//
// True or false is returned
//
// The opposite function is ftp_putfile
// --------------

// Global variables
	global $net2ftp_settings;

// Source and target
	if ($ftpmode == FTP_ASCII)      { $printftpmode = "FTP_ASCII"; }
	elseif ($ftpmode == FTP_BINARY) { $printftpmode = "FTP_BINARY"; }

	$remotesource = glueDirectories($remotesourcedir, $remotesourcefile);
	$localtarget  = glueDirectories($localtargetdir, $localtargetfile);

// Check if the filesize is smaller than the allowed filesize
//	$ftp_size_result = ftp_size($conn_id, $remotesource);
//	if ($ftp_size_result > $net2ftp_settings["max_filesize"]) { 
//		$errormessage = __("The file is too big to be transferred");
//		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
//		return false;
//	}

// Check the consumption
	if(checkConsumption() == false) {
		$errormessage = __("Daily limit reached: the file <b>%1\$s</b> will not be transferred", $remotesource);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Get file
	$success1 = ftp_get($conn_id, $localtarget, $remotesource, $ftpmode);
	if ($success1 == false) { 
		$errormessage = __("Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>", $remotesource, $printftpmode); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}
	else { registerTempfile("register", $localtarget); }

// Add the filesize to the global consumption variables
	addConsumption(filesize($localtarget), 0);

// Copy ==> do nothing
// Move ==> delete remote source file
	if ($copymove != "copy") {
		$success2 = ftp_delete2($conn_id, $remotesource);
		if ($success2 == false) { 
			$errormessage = __("Unable to delete file <b>%1\$s</b>", $remotesource);
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
	}

} // End function ftp_getfile

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************






// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_putfile($conn_id, $localsourcedir, $localsourcefile, $remotetargetdir, $remotetargetfile, $ftpmode, $copymove) {

// --------------
// This function copies or moves a local file to a remote file
// $ftpmode is used to specify whether the file is to be transferred in ASCII or BINARY mode
// $copymove is used to specify whether to delete (move) or not (copy) the local source
//
// The opposite function is ftp_getfile
// --------------

// Global variables
	global $net2ftp_settings;

// Source and target
	$localsource  = glueDirectories($localsourcedir, $localsourcefile);
	$remotetarget = glueDirectories($remotetargetdir, $remotetargetfile);

// In the function ftp_put, use FTP_BINARY without the double quotes, otherwhise ftp_put assumes FTP_ASCII
// DO NOT REMOVE THIS OR THE BINARY FILES WILL BE CORRUPTED (when copying, moving, uploading,...)
	if ($ftpmode == "FTP_BINARY") { $ftpmode = FTP_BINARY; } 

	if ($ftpmode == FTP_ASCII) { $printftpmode = "FTP_ASCII"; }
	elseif ($ftpmode == FTP_BINARY) { $printftpmode = "FTP_BINARY"; }

// Check if the filesize is smaller than the allowed filesize
	if (filesize($localsource) > $net2ftp_settings["max_filesize"]) { 
		$errormessage = __("The file is too big to be transferred");
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Add the filesize to the global consumption variables
	addConsumption(filesize($localsource), 0);

// Check the consumption
	if(checkConsumption() == false) {
		addConsumption((-1)*filesize($localsource), 0);
		$errormessage = __("Daily limit reached: the file <b>%1\$s</b> will not be transferred", $remotetarget);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Put local file to remote file
// int ftp_put (int ftp_stream, string remote_file, string local_file, int mode)
	$success1 = ftp_put($conn_id, $remotetarget, $localsource, $ftpmode);
	if ($success1 == false) { 
		$errormessage = __("Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>", $remotetarget, $printftpmode); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// If ftp_put fails, this function returns an error message and does not delete the temporary file.
// In case the file was copied, a copy exists in the source directory.
// In case the file was moved, the only copy is in the temporary directory, and so this has to be moved back to the source directory.

// Copy ==> do nothing
// Move ==> delete local source file
	if ($copymove != "copy") {
		$success2 = unlink($localsource);
		if ($success2 == false) { 
			$errormessage = __("Unable to delete the local file"); 
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
		else { registerTempfile("unregister", $localsource); }
	}

} // End function ftp_putfile

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function getContentType($entry) {

// --------------------
// Content-type, for a complete list, see http://www.isi.edu/in-notes/iana/assignments/media-types/media-types
// Content-disposition: attachment. See http://www.w3.org/Protocols/HTTP/Issues/content-disposition.txt
// --------------------

// Default values
	$fileType = getFileType($entry);
	$content_type = "application/octet-stream";

	if (get_filename_extension($entry)=="swf")      { $content_type = "application/x-shockwave-flash"; }

	elseif ($fileType == "TEXT")                    { $content_type = "text/plain"; }
	elseif ($fileType == "IMAGE") {
		if     (strpos($entry, ".jpg") !== false) { $content_type = "image/jpeg"; }
		elseif (strpos($entry, ".png") !== false) { $content_type = "image/png";  }
		elseif (strpos($entry, ".gif") !== false) { $content_type = "image/gif";  }
	}
	elseif ($fileType == "ARCHIVE") {
		if     (strpos($entry, ".zip") !== false) { $content_type = "application/zip"; }
	}
	elseif ($fileType == "OFFICE") {
		if     (strpos($entry, ".doc") !== false) { $content_type = "application/msword"; }
		elseif (strpos($entry, ".xls") !== false) { $content_type = "application/vnd.ms-excel"; }
		elseif (strpos($entry, ".ppt") !== false) { $content_type = "application/vnd.ms-powerpoint"; }

		elseif (strpos($entry, ".mpp") !== false) { $content_type = "application/vnd.ms-project"; }
	}
	
	return $content_type;
}

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************




// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_downloadfile($directory, $entry) {


// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_result, $net2ftp_settings;

// -------------------------------------------------------------------------
// Get the file from the FTP server to the web server
// -------------------------------------------------------------------------

// Open connection
	$conn_id = ftp_openconnection();
	if ($net2ftp_result["success"] == false)  { return false; }

// Check if the filesize is smaller than the allowed filesize
//	$ftp_size_result = ftp_size($conn_id, "$directory/$entry");
//	if ($ftp_size_result > $net2ftp_settings["max_filesize"]) { 
//		$errormessage = __("The file is too big to be transferred");
//		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
//		return false;
//	}

// FTP mode
	$ftpmode = ftpAsciiBinary($entry);

// Temporary filename
	$tempfilename = tempnam($net2ftp_globals["application_tempdir"], "downl__");
	if ($tempfilename == false)  { 
		@unlink($tempfilename); 
		$errormessage = __("Unable to create the temporary file. Check the permissions of the %1\$s directory.", $net2ftp_globals["application_tempdir"]);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}
	registerTempfile("register", $tempfilename);

// Get the file
//          ftp_getfile($conn_id, $localtargetdir, $localtargetfile, $remotesourcedir, $remotesourcefile, $ftpmode, $copymove)
		ftp_getfile($conn_id, "", $tempfilename, $directory, $entry, $ftpmode, "copy");
		if ($net2ftp_result["success"] == false) { 
			@unlink($tempfilename); 
			$errormessage = __("Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />", $entry, $tempfilename, $net2ftp_globals["application_tempdir"]); 
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}

// Close connection
	ftp_closeconnection($conn_id);

// -------------------------------------------------------------------------
// Transfer temp file to browser
// -------------------------------------------------------------------------

// Send the download headers - this function is in httpheaders.inc.php
	sendDownloadHeaders($entry, filesize($tempfilename));

// --------------------
// Open file
// --------------------
// From the PHP manual:
// Note:  The mode may contain the letter 'b'. 
// This is useful only on systems which differentiate between binary and text 
// files (i.e. Windows. It's useless on Unix). If not needed, this will be 
// ignored. You are encouraged to include the 'b' flag in order to make your scripts 
// more portable.
// Thanks to Malte for bringing this to my attention !
	registerTempfile("register", $tempfilename);
	$handle = fopen($tempfilename , "rb"); 
	if ($handle == false) {
		@unlink($tempfilename); 
		$errormessage = __("Unable to open the temporary file. Check the permissions of the %1\$s directory.", $net2ftp_globals["application_tempdir"]); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// --------------------
// Send file to browser
// --------------------

// Old method: fpassthru. Avoid using fpassthru, as it reads the entire file into memory!
//	$success1 = fpassthru($handle);
//	if ($success1 == false) {
//		@fclose($handle);
//		@unlink($tempfilename); 
//		$errormessage = __("Unable to send the file to the browser"); 
//		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
//		return false;
//	}

// New method: read the file piece by piece and send it to the browser.
	while(!feof($handle)) {
		$buffer = fread($handle, 2048);
		echo $buffer;
	}

// --------------------
// Close file
// --------------------
	$success2 = @fclose($handle);

// --------------------
// Delete the temporary file
// --------------------
	$success3 = @unlink($tempfilename);
	if ($success3 == false) { 
		$errormessage = __("Unable to delete the temporary file"); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	} 
	registerTempfile("unregister", $tempfilename);

} // End function ftp_downloadfile

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************






// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_zip($conn_id, $directory, $list, $zipactions, $zipdir, $divelevel) {

// --------------
// This function allows to download/save/email a zipfile which contains the selected directories and files
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result, $net2ftp_output;

// -------------------------------------------------------------------------
// Initialization
// -------------------------------------------------------------------------

	if ($divelevel == 0) {

// Create the zipfile
		$net2ftp_globals["zipfile"] = new zipfile();
		$timenow = time();
		$zipdir = "";

// Open the connection
		$conn_id = ftp_openconnection();
		if ($net2ftp_result["success"] == false)  { return false; }

	}


// -------------------------------------------------------------------------
// For all directories...
// -------------------------------------------------------------------------
	for ($i=1; $i<=$list["stats"]["directories"]["total_number"]; $i=$i+1) {
		$newdir       = glueDirectories($directory, $list["directories"][$i]["dirfilename"]);
		$newzipdir    = glueDirectories($zipdir, $list["directories"][$i]["dirfilename"]);
		$newdivelevel = $divelevel + 1;

// Check if the directory contains a banned keyword
		if ($list["directories"][$i]["selectable"] == "banned_keyword") { continue; }

// Get a new list
		$newlist = ftp_getlist($conn_id, $newdir);
		if ($net2ftp_result["success"] == false) { return false; }

		ftp_zip($conn_id, $newdir, $newlist, $zipactions, $newzipdir, $newdivelevel);
		if ($net2ftp_result["success"] == false) { setErrorVars(true, "", "", "", ""); continue; }

		if ($divelevel == 0 && ($zipactions["save"] == "yes" || $zipactions["email"] == "yes")) {
			$total = $list["stats"]["directories"]["total_number"] + $list["stats"]["files"]["total_number"];
			setStatus($i, $total, __("Processing the entries"));
		}

	} // end for directories

// -------------------------------------------------------------------------
// For all files...
// -------------------------------------------------------------------------

	for ($i=1; $i<=$list["stats"]["files"]["total_number"]; $i=$i+1) {

		if ($list["files"][$i]["selectable"] != "ok") { continue; }

		$text = ftp_readfile($conn_id, $directory, $list["files"][$i]["dirfilename"]);
		if ($net2ftp_result["success"] == false) { setErrorVars(true, "", "", "", ""); continue; }

		$filename = stripDirectory(glueDirectories($zipdir, $list["files"][$i]["dirfilename"]));
		$net2ftp_globals["zipfile"]->addFile($text, $filename);

		if ($divelevel == 0 && ($zipactions["save"] == "yes" || $zipactions["email"] == "yes")) {
			$total = $list["stats"]["directories"]["total_number"] + $list["stats"]["files"]["total_number"];
			setStatus($list["stats"]["directories"]["total_number"] + $i - 1, $total, __("Processing the entries"));
		}

	} // end for files

// -------------------------------------------------------------------------
// End
// -------------------------------------------------------------------------
	if ($divelevel == 0) {

// ------------------------
// Send the zipfile to the browser
// ------------------------
		if ($zipactions["download"] == "yes") {
			$timenow = time();
			
			$filenameToSend = "net2ftp-" . $timenow . ".zip";
			$filesizeToSend = strlen($net2ftp_globals["zipfile"]->file());
			sendDownloadHeaders($filenameToSend, $filesizeToSend);

			echo $net2ftp_globals["zipfile"]->file();
			flush();
		}

// ------------------------
// Save the zipfile string to a file
// ------------------------
		if ($zipactions["save"] == "yes" || $zipactions["email"] == "yes") {
			$string = $net2ftp_globals["zipfile"]->file();

			$tempfilename = tempnam($net2ftp_globals["application_tempdir"], "zip__");
			if ($tempfilename == false)  { 
				@unlink($tempfilename); 
				$errormessage = __("Unable to create the temporary file. Check the permissions of the %1\$s directory.", $net2ftp_globals["application_tempdir"]); 
				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false;
			}
			registerTempfile("register", $tempfilename);

			local_writefile($tempfilename, $string);
			if ($net2ftp_result["success"] == false) { return false; }
		}

// ------------------------
// Save the zip file to the FTP server
// ------------------------
		if ($zipactions["save"] == "yes") {
			ftp_putfile($conn_id, "", $tempfilename, $directory, $zipactions["save_filename"], FTP_BINARY, "copy");
			if ($net2ftp_result["success"] == false) { 
				@unlink($tempfilename); 
//				$errormessage = __("Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory.", $zipactions["save_filename"]); 
//				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false;
			}
			else { $net2ftp_output["ftp_zip"][] = __("The zip file has been saved on the FTP server as <b>%1\$s</b>", $zipactions["save_filename"]) . "<br /><br />\n"; }
		}		

// ------------------------
// Close the connection
// ------------------------
		ftp_closeconnection($conn_id);

// ------------------------
// Email
// ------------------------
		if ($zipactions["email"] == "yes") {

			$FromName = "net2ftp";
			$From = $net2ftp_settings["email_feedback"];

			$ToName = "";
			$To = $zipactions["email_to"];

			$Subject = __("Requested files");

// Email message
			$Text =  __("Dear,") . "\n\n";
			$Text .= __("Someone has requested the files in attachment to be sent to this email account (%1\$s).", $To) . "\n";
			$Text .= __("If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment.") . "\n";
			$Text .= __("Note that if you don't open the Zip file, the files inside cannot harm your computer.") . "\n";
			$Text .= "\n\n---------------------------------------\n";
			$Text .= __("Information about the sender: ") . "\n";
			$Text .= __("IP address: ") . $REMOTE_ADDR . "\n";
			$Text .= __("Time of sending: ") . mytime() . "\n";
			$Text .= __("Sent via the net2ftp application installed on this website: ") . $HTTP_REFERER . "\n";
			$Text .= __("Webmaster's email: ") . $From . "\n";
			$Text .= "\n\n---------------------------------------\n";
			$Text .= __("Message of the sender: ") . "\n";
			$Text .= $zipactions["message"] . "\n";
			$Text .= "\n\n---------------------------------------\n";
			$Text .= __("net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com.") . "\n\n\n";

			$AttmFiles = array($tempfilename);

			SendMail($From, $FromName, $To, $ToName, $Subject, $Text, $Html, $AttmFiles);
			if ($net2ftp_result["success"] == false) { 
				@unlink($tempfilename); 
				return false; 
			}
			$net2ftp_output["ftp_zip"][] = __("The zip file has been sent to <b>%1\$s</b>.", $To) . "<br /><br />";
		}

// ------------------------
// Delete the temporary zipfile
// ------------------------
		if ($zipactions["save"] == "yes" || $zipactions["email"] == "yes") {
			$success4 = @unlink($tempfilename);
			if ($success4 == false) { 
				$errormessage = __("Unable to delete the temporary file"); 
				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false;
			}
			registerTempfile("unregister", $tempfilename);
		}

// Set the variable to NULL to save memory
		$net2ftp_globals["zipfile"] = NULL;

	} // end if $divelevel == 0

} // End function ftp_zip

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************






// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function acceptFiles($uploadedFilesArray) {

// --------------
// This PHP function takes files that were just uploaded with HTTP POST, 
// verifies if the size is smaller than a certain value, and moves them 
// using move_uploaded_file() from the server's temporary directory to 
// net2ftp's temporary directory
//
// $uploadedFilesArray[number]["name"] and $acceptedFilesArray[number]["name"] contain the real name of the file
// $uploadedFilesArray[number]["tmp_name"] contains the temporary name of the file in the *webserver's* temporary directory (eg C:\temp)
// $acceptedFilesArray[number]["tmp_name"] contains the temporary name of the file in *net2ftp's* temporary directory (eg C:\web\net2ftp\temp)
//
// Note 1 - $acceptedFilesArray[number]["tmp_name"] may not be the same as $uploadedFilesArray[number]["tmp_name"] because 
//          $acceptedFilesArray[number]["tmp_name"] should be unique at the moment the file is transferred to the new directory.
// Note 2 - $acceptedFilesArray[number]["tmp_name"] 
//            - starts with upload (or upl on Windows, because on that platform only the first 3 letters are kept)
//            - has the same filename extension as the real filename 
//            - ends with .txt
//     The filename extension is needed by the PCL TAR library, which needs to determine if the archive is tar, tgz or gz.
//     The additional .txt is to ensure that no temporary file would be executed on the web server, which could compromise it.
//
// For example: script.php is uploaded to the web server's temporary directory C:\temp\f9skpqri
//              Then it is moved to net2ftp's temporary directory C:\web\net2ftp\temp\upload9oeic.php.txt
//              And finally it is transferred to the FTP server as script.php in functions ftp_transferfiles() and ftp_unziptransferfiles() -- see below
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_output;
	$max_filesize = $net2ftp_settings["max_filesize"];
	
	$skipped = 0;     // Index of the files which are too big / contain a banned keyword
	$moved_ok = 0;    // Index of the files that have been treated successfully
	$moved_notok = 0; // Index of the files that have been treated unsuccessfully

	for ($i=1; $i<=sizeof($uploadedFilesArray); $i++) {

// -------------------------------------------------------------------------
// 1 -- Get the data from the filesArray (for each file, its location, name, size, ftpmode
// -------------------------------------------------------------------------
		$file_name     = $uploadedFilesArray["$i"]["name"];
		$file_tmp_name = $uploadedFilesArray["$i"]["tmp_name"];
		$file_size     = $uploadedFilesArray["$i"]["size"];

		if (($file_name != "" && $file_tmp_name == "") || $file_size > $max_filesize) {
// The case ($file_name != "" && $file_tmp_name == "") occurs when the file is bigger than the directives set in php.ini
// In that case, only $uploadedFilesArray["$i"]["name"] is filled in.
			$net2ftp_output["acceptFiles"][] = __("File <b>%1\$s</b> is too big. This file will not be uploaded.", $file_name);
			$skipped = $skipped + 1;
			@unlink($file_tmp_name); 
			continue;
		}
		elseif (checkAuthorizedName($file_name) == false) {
			$net2ftp_output["acceptFiles"][] = __("File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded.", $file_name);
			$skipped = $skipped + 1;
			@unlink($file_tmp_name); 
			continue;
		}

// -------------------------------------------------------------------------
// 3 -- upload and copy the file; if a file with the same name already exists, it is overwritten with the new file
// -------------------------------------------------------------------------
		$extension = get_filename_extension($file_name);
		if (substr($file_name, -6) == "tar.gz") { $extension = "tar.gz"; }

		$tempfilename = tempnam2($net2ftp_globals["application_tempdir"], "upload__", "." . $extension . ".txt");
		if ($tempfilename == false) { 
			@unlink($tempfilename); 
			$errormessage = __("Could not generate a temporary file."); 
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}

		$success2 = move_uploaded_file($file_tmp_name, $tempfilename);
		if ($success2 == false) { 
			$net2ftp_output["acceptFiles"][] = __("File <b>%1\$s</b> could not be moved", $file_name);
			@unlink($file_tmp_name); 
			@unlink($tempfilename);
			$moved_notok = $moved_notok + 1;
			continue;
		}

// -------------------------------------------------------------------------
// 4 -- if everything went fine, put file in acceptedFilesArray
// -------------------------------------------------------------------------
		else {
// When uploading files, print some output
// When updating files, do not print anything
			registerTempfile("register", $tempfilename);
			if ($net2ftp_globals["state"] == "upload") { 
				$net2ftp_output["acceptFiles"][] = __("File <b>%1\$s</b> is OK", $file_name);
			}
			$moved_ok = $moved_ok + 1;
			$acceptedFilesArray[$moved_ok]["name"] = $file_name;
			$acceptedFilesArray[$moved_ok]["tmp_name"] = $tempfilename;
		}

	} // End for

	if ($moved_notok > 0) { 
		$errormessage = __("Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}
	elseif ($moved_ok == 0 && $skipped == 0) { 
		$errormessage = __("You did not provide any file to upload.");
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}
	elseif ($moved_ok == 0 && $skipped > 0) { 
		return "all_uploaded_files_are_too_big"; 
	}
	else { 
		return $acceptedFilesArray; 
	}

} // End function acceptFiles

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************







// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_transferfiles($filesArray, $targetDir) {

// --------------
// This PHP function takes a file that was uploaded from a client computer via a browser to the web server, 
// and puts it on another FTP server
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_result, $net2ftp_output;

// ------------------------------
// Open connection
// ------------------------------
	$conn_id = ftp_openconnection();
	if ($net2ftp_result["success"] == false) { 
		for ($i=1; $i<=sizeof($filesArray); $i++) { @unlink($filesArray[$i]["tmp_name"]); }
		return false; 
	}

// ------------------------------
// For loop
// ------------------------------
	for ($i=1; $i<=sizeof($filesArray); $i++) {

// Set status
		setStatus($i, sizeof($filesArray), __("Transferring files to the FTP server"));

// Determine which FTP mode should be used
		$ftpmode = ftpAsciiBinary($filesArray[$i]["name"]);

		if ($ftpmode == FTP_ASCII)      { $printftpmode = "FTP_ASCII"; }
		elseif ($ftpmode == FTP_BINARY) { $printftpmode = "FTP_BINARY"; }

// Put files
		ftp_putfile($conn_id, "", $filesArray[$i]["tmp_name"], $targetDir, $filesArray[$i]["name"], $ftpmode, "move");
		if ($net2ftp_result["success"] == false) { 
			setErrorVars(true, "", "", "", "");
			@unlink($filesArray[$i]["tmp_name"]); 
			$net2ftp_output["ftp_transferfiles"][] = __("File <b>%1\$s</b> could not be transferred to the FTP server", $filesArray[$i]["name"]);
			continue;
		}
		$net2ftp_output["ftp_transferfiles"][] = __("File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>", $filesArray[$i]["name"], $printftpmode);

	} // End for

// ------------------------------
// Close connection
// ------------------------------
	ftp_closeconnection($conn_id);

} // End function ftp_transferfiles

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_unziptransferfiles($archivesArray) {

// --------------
// Extract the directories and files from the archive to a temporary directory on the web server, and 
// then create the directories and put the files on the FTP server
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_result, $net2ftp_output;

// -------------------------------------------------------------------------
// Open connection
// -------------------------------------------------------------------------
	$conn_id = ftp_openconnection();
	if ($net2ftp_result["success"] == false) { 
		for ($archive_nr=1; $archive_nr<=sizeof($archivesArray); $archive_nr++) { @unlink($archivesArray[$archive_nr]["tmp_name"]); }
		return false;
	}

// -------------------------------------------------------------------------
// For each archive...
// -------------------------------------------------------------------------
	for ($archive_nr=1; $archive_nr<=sizeof($archivesArray); $archive_nr++) {

// Set status
		setStatus($archive_nr, sizeof($archivesArray), __("Decompressing archives and transferring files"));

// -------------------------------------------------------------------------
// Determine the type of archive depending on the filename extension
// -------------------------------------------------------------------------
		$archive_name = $archivesArray[$archive_nr]["name"];
		$archive_file = $archivesArray[$archive_nr]["tmp_name"];
		$archivename_without_dottext = substr($archivesArray[$archive_nr]["tmp_name"], 0, strlen($archive)-4);
		$archive_type = get_filename_extension($archivename_without_dottext);

		$net2ftp_output["ftp_unziptransferfiles"][] = __("Processing archive nr %1\$s: <b>%2\$s</b>", $archive_nr, $archive_name);
		$net2ftp_output["ftp_unziptransferfiles"][] = "<ul>";

		if ($archive_type != "zip" && $archive_type != "tar" && $archive_type != "tgz" && $archive_type != "gz") {
			$net2ftp_output["ftp_unziptransferfiles"][] = __("Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment.", $archive_name);
			continue;
		} 

// -------------------------------------------------------------------------
// Extract directories and files
// -------------------------------------------------------------------------

// ------------------------------
// Generate random directory
// ------------------------------
		$tempdir = tempdir2($net2ftp_globals["application_tempdir"], "unzip__", "");
		registerTempfile("register", "$tempdir");

// ------------------------------
// Extract
// ------------------------------
		if ($archive_type == "zip") {
			$zip = new PclZip($archive_file);
			$list = $zip->extract($p_path = $tempdir);
		}
		elseif ($archive_type == "tar" || $archive_type == "tgz" || $archive_type == "gz") { 
			$list = PclTarExtract($archive_file, $tempdir);
		}

		if ($list <= 0) { 
			$net2ftp_output["ftp_unziptransferfiles"][] = __("Unable to extract the files and directories from the archive");
			continue;
		}

// ------------------------------
// Create the directories and put the files on the FTP server
// ------------------------------

		for ($i=0; $i<sizeof($list); $i++) {

			$source = trim($list[$i]["filename"]);
			$target_relative = substr($source, strlen($tempdir));
			$target = $net2ftp_globals["directory"] . $target_relative;
			$ftpmode = ftpAsciiBinary($source);

// Directory entry in the archive: create the directory
			if (is_dir($source) == true) {
				ftp_newdirectory($conn_id, $target);
				if ($net2ftp_result["success"] == true) { 
					$net2ftp_output["ftp_unziptransferfiles"][] = __("Created directory %1\$s", $target); 
				}
				else { 
					$net2ftp_output["ftp_unziptransferfiles"][] = __("Could not create directory %1\$s", $target);
					setErrorVars(true, "", "", "", ""); 
				}
			} // end if directory
// File entry in the archive: put the file
// If this fails, create the required directories and try again
			elseif (is_file($source) == true) {
				ftp_putfile($conn_id, dirname($source), basename($source), dirname($target), basename($target), $ftpmode, "move");
				if ($net2ftp_result["success"] == true) { $net2ftp_output["ftp_unziptransferfiles"][] = __("Copied file %1\$s", $target); }
				else { 
					setErrorVars(true, "", "", "", ""); 
					$target_relative_parts = explode("/", str_replace("\\", "/", dirname($target_relative)));
					$directory_to_create = $net2ftp_globals["directory"];
					for ($j=0; $j<sizeof($target_relative_parts); $j=$j+1) {
						$directory_to_create = $directory_to_create . "/" . $target_relative_parts[$j];
						$ftp_chdir_result = @ftp_chdir($conn_id, $directory_to_create);
						if ($ftp_chdir_result == false) {
							ftp_newdirectory($conn_id, $directory_to_create);
							if ($net2ftp_result["success"] == true) { $net2ftp_output["ftp_unziptransferfiles"][] = __("Created directory %1\$s", $directory_to_create); }
							else { setErrorVars(true, "", "", "", ""); }
						} // end if
					} // end for
					ftp_putfile($conn_id, dirname($source), basename($source), dirname($target), basename($target), $ftpmode, "copy");
					if ($net2ftp_result["success"] == true) { $net2ftp_output["ftp_unziptransferfiles"][] = __("Copied file %1\$s", $target); }
					else { 
						setErrorVars(true, "", "", "", ""); 
						$net2ftp_output["ftp_unziptransferfiles"][] = __("Could not copy file %1\$s", $target);
					}
				}
			} // end elseif file
		} // end for

// -------------------------------------------------------------------------
// Delete the uploaded archive and the temporary files
// -------------------------------------------------------------------------

// Delete the temporary directory and its contents
		$delete_dirorfile_result = delete_dirorfile($tempdir);
		if ($delete_dirorfile_result == false) { 
			$net2ftp_output["ftp_unziptransferfiles"][] = __("Unable to delete the temporary directory");
		}
		else {
			registerTempfile("unregister", "$tempdir");
		}

// Delete the archive
		$unlink_result = @unlink($archive_file);
		if ($unlink_result == false) { 
			$net2ftp_output["ftp_unziptransferfiles"][] = __("Unable to delete the temporary file %1\$s", $archive_file);
		}
		else {
			registerTempfile("unregister", "$archive_file");
		}

		$net2ftp_output["ftp_unziptransferfiles"][] = "</ul>";

	} // End for

// -------------------------------------------------------------------------
// Close connection
// -------------------------------------------------------------------------
	ftp_closeconnection($conn_id);

} // End function ftp_unziptransferfiles

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************






// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp_mysite($conn_id, $command) {

// --------------
// This function sends a site command to the FTP server
// Note:
//    - These commands vary a lot depending on the FTP server type
//    - PHP does not return any result other than TRUE or FALSE
// --------------

	$success1 = ftp_site($conn_id, $command);
	if ($success1 == false) { 
		$errormessage = __("Unable to execute site command <b>%1\$s</b>", $command); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

} // End function ftp_mysite

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************








// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function upDir($directory) {

// --------------
// This function takes a directory string and returns the parent directory string
// --------------

	if ($directory == "" || $directory == "/" || $directory == "\\") { return "/"; }

	$parentdirectory = "";
	$directory = stripDirectory($directory);
	$parts = explode("/", $directory);

	$parentdirectory = "";
	for ($i=0; $i<sizeof($parts)-1; $i++) {
		$parentdirectory = $parentdirectory . "/" . $parts[$i];
	}

	if ($parentdirectory == "") { $parentdirectory = "/"; }

	return $parentdirectory;

} // End function upDir

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************








// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function stripDirectory($directory) {

// --------------
// Removes a leading and trailing / or \ if there is one
// --------------

// Remove leading and trailing whitespaces
	$directory = trim($directory);

// Replace // by / and replace \\ by \
	$search[0]  = "//";
	$replace[0] = "/";
	$search[1]  = "\\\\";
	$replace[1] = "\\";
	$search[2]  = "/\\";
	$replace[2] = "/";
	$search[3]  = "\\/";
	$replace[3] = "/";
	$directory = str_replace($search, $replace, $directory);

// Check first and last characters
// Remove leading and trailing / or \ if needed
	$firstchar = substr($directory, 0, 1);
	$lastchar  = substr($directory, strlen($directory)-1, 1);

	if ($firstchar == "/"  || $firstchar == "\\") { $directory = substr($directory, 1, strlen($directory)-1); }
	if ($lastchar  == "/" || $lastchar == "\\")   { $directory = substr($directory, 0, strlen($directory)-1); }

	return $directory;

} // end stripDirectory

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************







// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function glueDirectories($part1, $part2) {

// --------------
// Returns the 2 dirs glued together in the format /home/dh1234/test (leading /, NO trailing /)
// --------------

// Strip leading and trailing / and \
	$part1 = stripDirectory($part1);
	$part2 = stripDirectory($part2);

// Length
	$part1_len = strlen($part1);
	$part2_len = strlen($part2);

// Check if Unix or Windows style directories are used
	if     ($part1_len > 1 && substr($part1, 1, 1) == ":") { $system = "windows"; }
	elseif ($part2_len > 1 && substr($part2, 1, 1) == ":") { $system = "windows"; }
	else                                                   { $system = "unix"; }

// Glue the 2 parts together
	if ($part1_len > 0 && $part2_len > 0) {
		if ($system == "windows") { return $part1 . "\\" . $part2; }
		else                      { return "/" . $part1 . "/" . $part2; }
	}
	elseif (($part1_len == 0 || $part1 == "/" || $part1 == "\\") && ($part2_len > 0)) {
		if ($system == "windows") { return $part2; }
		else                      { return "/" . $part2; }
	}
	elseif (($part2_len == 0 || $part2 == "/" || $part2 == "\\") && ($part1_len > 0)) {
		if ($system == "windows") { return $part1; }
		else                      { return "/" . $part1; }
	}
	else {
		return "";
	}

} // end glueDirectories

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************




// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function get_filename_extension($filename) {

// --------------
// This function returns the extension of a filename:
// 	name.ext1.ext2.ext3 --> ext3
// 	name --> name
// 	.name --> name
//	.name.ext --> ext
// It also converts the result to lower case:
// 	name.ext1.EXT2 --> ext2
// --------------

	$lastdotposition = strrpos($filename,".");

	if ($lastdotposition === 0)      { $extension = substr($filename, 1); }
	elseif ($lastdotposition == "")  { $extension = $filename; }
	else                             { $extension = substr($filename, $lastdotposition + 1); }

	return strtolower($extension);

} // End get_filename_extension

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************



// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function get_filename_name($filename) {

// --------------
// This function returns the name part of a filename:
// 	name.ext1.ext2.ext3 --> name
// 	name --> name
// 	.name --> name
//	.name.ext --> name.ext
// It also converts the result to lower case:
// 	NAME.ext --> name
// --------------

	$firstdotposition = strpos($filename,".");

	if ($firstdotposition === 0)      { $name = substr($filename, 1); }
	elseif ($firstdotposition == "")  { $name = $filename; }
	else                              { $name = substr($filename, 0, $firstdotposition); }

	return strtolower($name);

} // End get_filename_name

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftpAsciiBinary($filename) {

// --------------
// Checks the first character of a file and its extension to see if it should be 
// transferred in ASCII or Binary mode
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals;

// -------------------------------------------------------------------------
// If $net2ftp_globals["ftpmode"] == "binary" then return FTP_BINARY
// -------------------------------------------------------------------------
	if ($net2ftp_globals["ftpmode"] != "automatic") { return FTP_BINARY; }

// -------------------------------------------------------------------------
// If $net2ftp_globals["ftpmode"] == "automatic" then return return 
// FTP_ASCII or FTP_BINARY
// -------------------------------------------------------------------------
	$firstcharacter = substr($filename, 0, 1);

	if ($firstcharacter == ".") { 
		$ftpmode = FTP_ASCII; 
		return $ftpmode;
	}

// -------------------------------------------------------------------------
// If the first character is not a dot, check the extension
// -------------------------------------------------------------------------
	$last = get_filename_extension($filename);

	if (
		$last == "1st"  		||
		$last == "asp"  		||
		$last == "bas"  		||
		$last == "bat"  		||
		$last == "c"  		||
		$last == "cfg"  		||
		$last == "cfm"  		||
		$last == "cgi"  		||
		$last == "conf"  		||
		$last == "cpp"  		||
		$last == "css"  		||
		$last == "csv"  		||
		$last == "dhtml"		||
		$last == "diz"		||
		$last == "default"	||
		$last == "file"  		||
		$last == "h"  		||
		$last == "hpp"  		||
		$last == "htaccess"	||
		$last == "htpasswd"	||
		$last == "htm"  		||
		$last == "html"  		||
		$last == "inc"  		||
		$last == "ini"  		||
		$last == "js"  		||
		$last == "jsp"  		||
		$last == "log"  		||
		$last == "m3u" 		||
		$last == "mak" 		||
		$last == "msg" 		||
		$last == "nfo" 		||
		$last == "old" 		||
		$last == "pas" 		||
		$last == "patch" 		||
		$last == "perl" 		||
		$last == "php" 		||
		$last == "php3" 		||
		$last == "phps" 		||
		$last == "phtml" 		||
		$last == "pinerc"		||
		$last == "pl" 		||

		$last == "pm" 		||
		$last == "qmail" 		||
		$last == "readme"		||
		$last == "setup" 		||
		$last == "seq" 		||
		$last == "sh" 		|| 
		$last == "sql" 		|| 
		$last == "style" 		|| 
		$last == "tcl" 		|| 
		$last == "tex"		|| 
		$last == "threads"	|| 
		$last == "tmpl"  		||
		$last == "tpl"  		|| 
		$last == "txt"  		|| 
		$last == "ubb"  		||
		$last == "vbs"  		|| 
		$last == "xml"  		||
		strstr($last, "htm")
							)	{ $ftpmode = FTP_ASCII; }
	else 							{ $ftpmode = FTP_BINARY; }

	return $ftpmode;

} // end ftpAsciiBinary

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************








// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function standardize_eol($string) {

// --------------
// Input:  text with Windows (\r\n), Unix (\n), or weird (\r\r) end-of-line characters
// Output: text with Unix style end-of-line characters (\n)
// --------------

	$patterns[0] = "/\\r\\r/"; // this is \r\r
	$patterns[1] = "/\\r\\n/"; // this is \r\n

	$replacements[0] = "\r\n";
	$replacements[1] = "\n";

	$string = preg_replace($patterns, $replacements, $string);

	return $string;

}

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************







// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function explode_lines($string) {

// --------------
// Input:  $string which may have Windows or Unix end-of-line characters
// Output: $lines array with the lines
// --------------

//	$string = standardize_eol($string);

// Add a \n in the beginning of the strings so that the first line of the string would
// be in the first element of the exploded array
	$lines  = explode("\n", "\n" . $string);

	return $lines;

} // explode_lines

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************







// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function getFileType($filename) {

// --------------
// Checks the extension of a file to determine what should be done with it in the View and Edit functions
// Default: TEXT
// Exceptions (see list below): IMAGE, EXECUTABLE, OFFICE, ARCHIVE
// --------------

	$last = get_filename_extension($filename);

	if (
		$last == "asp"  		||
		$last == "bas"  		||
		$last == "bat"  		||
		$last == "c"  		||
		$last == "cfg"  		||
		$last == "cfm"  		||
		$last == "cgi"  		||
		$last == "conf"  		||
		$last == "cpp"  		||
		$last == "css"  		||
		$last == "dhtml"		||
		$last == "diz"		||
		$last == "default"	||
		$last == "file"  		||
		$last == "h"  		||
		$last == "hpp"  		||
		$last == "htaccess"	||
		$last == "htpasswd"	||
		$last == "htm"  		||
		$last == "html"  		||
		$last == "inc"  		||
		$last == "ini"  		||
		$last == "js"  		||
		$last == "jsp"  		||
		$last == "mak" 		||
		$last == "msg" 		||
		$last == "nfo" 		||
		$last == "old" 		||
		$last == "pas" 		||
		$last == "patch" 		||
		$last == "perl" 		||
		$last == "php" 		||
		$last == "php3" 		||
		$last == "phps" 		||
		$last == "phtml" 		||
		$last == "pinerc"		||
		$last == "pl" 		||
		$last == "pm" 		||
		$last == "qmail" 		||
		$last == "readme"		||
		$last == "setup" 		||
		$last == "sh" 		|| 
		$last == "shtml" 		|| 
		$last == "sql" 		|| 
		$last == "style" 		|| 
		$last == "tcl" 		|| 
		$last == "tex"		|| 
		$last == "threads"	|| 
		$last == "tmpl"  		||
		$last == "tpl"  		|| 
		$last == "txt"  		|| 
		$last == "ubb"  		||
		$last == "vbs"  		|| 
		$last == "xml"  		||
		$last == "conf"		||
		strstr($last, "htm")) { return "TEXT"; }

	elseif (	$last == "png"  || 
			$last == "jpg"  || 
			$last == "jpeg" || 
			$last == "gif"  ||
			$last == "bmp"  ||
			$last == "tif"  ||
			$last == "tiff")    { return "IMAGE"; }

	elseif (	$last == "exe"  || 
			$last == "com")     { return "EXECUTABLE"; }

	elseif (	$last == "doc"  || 
			$last == "rtf"  || 
			$last == "xls"  || 
			$last == "ppt"  || 
			$last == "mdb"  || 
			$last == "vsd"  || 
			$last == "mpp")     { return "OFFICE"; }

	elseif (	$last == "zip"  || 
			$last == "tar"  || 
			$last == "gz"   || 
			$last == "tgz"  || 
			$last == "rar"  || 
			$last == "arj"  || 
			$last == "arc")     { return "ARCHIVE"; }

	else					  { return "OTHER"; }
	

} // end getFileType

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************








// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function getSystemType() {

// --------------
// Gets the WEBSERVER system type on which PHP is running 
// (Not the one for which is was built)
// --------------

	$systemInfo = php_uname();

	if     (stristr($systemInfo, "Linux") != false) {
		$system = "Linux";
	}
	elseif (stristr($systemInfo, "BSD") != false) {
		$system = "BSD";
	}
	elseif (stristr($systemInfo, "Unix") != false) {
		$system = "Unix";
	}
	elseif (stristr($systemInfo, "Win") != false) {
		$system = "Windows";
	}

	return $system;

} // end getSystemType

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function checkEmailAddress($email) {

// --------------
// Returns true for valid email addresses, false for non-valid email addresses
// --------------

	if (eregi( "^" .
	           "[a-z0-9]+([_\.-][a-z0-9]+)*" .    //user
	           "@" .
	           "([a-z0-9]+([\.-][a-z0-9]+)*)+" .   //domain
	           "\\.[a-z]{2,}" .                    //sld, tld 
	           "$", $email, $regs)) { return true;	}
	else { return false; }

} // end checkEmailAddress

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function checkFilename($filename) {

// --------------
// Returns true for valid filename
// --------------

	if (preg_match("/^[a-zA-Z0-9_ \.-]*$/", $filename) == 0) { return false; }
	else { return true; }

} // end checkFilename

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function htmlEncode2($string) {

// --------------
// This function HTML-encodes a string
// --------------

	$isocode = __("iso-8859-1");
	if ($isocode == "MESSAGE NOT FOUND") { $isocode = "iso-8859-1"; }
	$string = @htmlentities($string, ENT_QUOTES, $isocode);

	return $string;

} // end htmlEncode2

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function urlEncode2($string) {

// --------------
// This function URL-encodes a string
// --------------

//	$isocode = __("iso-8859-1");
//	if ($isocode == "MESSAGE NOT FOUND") { $isocode = "iso-8859-1"; }
//	$string = @htmlentities($string, ENT_QUOTES, $isocode);
//	$string = str_replace(" ", "%20", $string);

	$string = rawurlencode($string);

	return $string;

} // end urlEncode2

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function javascriptEncode2($string) {

// --------------
// Encode string characters which cause problems in Javascript
// <input type="button" onClick="alert('single quote \' single quote');" value="Test single"> OK      <br /><br />
// <input type="button" onClick="alert('double quote &quot; double quote');"  value="Test double"> OK <br /><br />
// <input type="button" onClick="alert('bs single \\\' bs single');" value="Test bs single"> OK       <br /><br />
// <input type="button" onClick="alert('bs double \\\&quot; bs double');" value="Test bs double"> OK  <br /><br />
// --------------

	$singlequote = "'";           // '
	$doublequote = "\"";          // "
	$backslash   = "\\";          // \
	$doublequote_html = "&quot;"; // &quot;

	$string = htmlEncode2($string);

// Executing the 3 steps below in this order will convert:
//     '     -->    \'        in step 2
//     "     -->    &quot;    in step 3
//     \'    -->    \\\'      in step 1 and 2
//     \"    -->    \\\&quot; in step 1 and 3
	$string = str_replace($backslash, "$backslash$backslash", $string);
	$string = str_replace($singlequote, "$backslash$singlequote", $string);
	$string = str_replace($doublequote, $doublequote_html, $string);

	return $string;

} // end javascriptEncode2

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function registerTempfile($dowhat, $filename) {

// --------------
// This function registers and unregisters temporary files which are created and deleted
// If the script is halted, all the registered temporary files are deleted by the net2ftp_shutdown() function
//
// $dowhat can be either "register" or "unregister"
// $filename is the absolute filename (/web/net2ftp/temp/file.txt)
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals;

// -------------------------------------------------------------------------
// Add the current file to/from the $net2ftp_globals["tempfiles"] array
// Initialize $net2ftp_tempfiles if needed
// -------------------------------------------------------------------------
	if ($dowhat == "register") { 
		if (isset($net2ftp_globals["tempfiles"]) == false) { $net2ftp_globals["tempfiles"] = array(); }
		@array_push($net2ftp_globals["tempfiles"], $filename); 
	} // end if register

// -------------------------------------------------------------------------
// Remove the current file to/from the $net2ftp_globals["tempfiles"] array
// -------------------------------------------------------------------------
	elseif ($dowhat == "unregister" && isset($net2ftp_globals["tempfiles"]) == true) {
		$newindex = 0;
		$tempfiles_new = array();
		for ($i=0; $i<=sizeof($net2ftp_globals["tempfiles"]); $i++) {
			if (isset($net2ftp_globals["tempfiles"][$i]) == true && $net2ftp_globals["tempfiles"][$i] != $filename) { 
				$tempfiles_new[$newindex] = $net2ftp_globals["tempfiles"][$i]; 
				$newindex = $newindex + 1;
			}
		} // end for
		unset($net2ftp_globals["tempfiles"]);
		$net2ftp_globals["tempfiles"] = $tempfiles_new;
	} // end if unregister

	return true;

} // end registerTempfile

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function net2ftp_shutdown() {

// --------------
// This function is registered through register_shutdown_function, so that it would be
// executed when the script reaches the maximum execution time.
//
// The function displays a warning message, and deletes temporary files.
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result;


// -------------------------------------------------------------------------
// Delete the temporary files which were not deleted automatically
// -------------------------------------------------------------------------
	if (isset($net2ftp_globals["tempfiles"]) == true) {
		for ($i=0; $i<sizeof($net2ftp_globals["tempfiles"]); $i++) { delete_dirorfile($net2ftp_globals["tempfiles"][$i]); }
	} // end if


// -------------------------------------------------------------------------
// Store the consumption counter values in the database
// -------------------------------------------------------------------------
	putConsumption();


// -------------------------------------------------------------------------
// Print a message to tell the user that the script was halted
// -------------------------------------------------------------------------
	$time_taken = timer();
	$max_execution_time = @ini_get("max_execution_time");

// - Check if the $max_execution_time is > 0, because on some PHP configs it is -1 (more
// specifically: when PHP is run as CGI module).
// - Check the time taken versus the maximum execution time, because on Windows + Apache
// servers, the shutdown function is always called, even if the maximum execution time
// was not reached.
	if (($max_execution_time > 0) && ($time_taken > $max_execution_time - 1)) {
		if (isStatusbarActive() == true && function_exists("setStatus") == true) {
			setStatus(10, 10, __("Script halted")); 
		}

		$text = "";
		$text .= "<b>" . __("Your task was stopped") . "</b><br /><br />\n";
		$text .= __("The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped.", $max_execution_time) . "<br />\n";
		$text .= __("This time limit guarantees the fair use of the web server for everyone.") . "<br /><br />\n";
		$text .= __("Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files.") . "<br /><br />\n";

		if ($net2ftp_settings["net2ftpdotcom"] == "yes") {
			$text .= __("If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server.");
		}

		if ($net2ftp_globals["state"] == "jupload") { echo $text; }
		else                                        { echo "<div class=\"warning-box\"><div class=\"warning-text\">$text</div></div>\n\n"; }
	}

} // end shutdown

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function SendMail($From, $FromName, $To, $ToName, $Subject, $Text, $Html, $AttmFiles) {

// --------------
// This function taken from www.PHP.net.
// It was written by alex@bartl.net (29-Nov-2002 06:25)
// Alex was inspired by the function of kieran.huggins@rogers.com (06-Nov-2002 04:52)

// Note: it has been changed slightly to fit into the net2ftp application.
// (Mainly the way error handling is done.)
// --------------

/* Alex's comments:
This might be some useful stuff to send out emails in either text
or html or multipart version, and attach one or more files or even
none to it. Inspired by Kieran's msg above, I thought it might be 
useful to have a complete function for doing this, so it can be used 
wherever it's needed. Anyway I am not too sure how this script will
behave under Windows.
{br} represent the HTML-tag for line break and should be replaced,
but I did not know how to not get the original tag  parsed here.
function SendMail($From, $FromName, $To, $ToName, $Subject, $Text, $Html, $AttmFiles)
$From      ... sender mail address like "my@address.com"
$FromName  ... sender name like "My Name"
$To        ... recipient mail address like "your@address.com"
$ToName    ... recipients name like "Your Name"
$Subject   ... subject of the mail like "This is my first testmail"
$Text      ... text version of the mail
$Html      ... html version of the mail
$AttmFiles ... array containing the filenames to attach like array("file1","file2")
*/

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_result;

// Initial tests
//	$Html = $Html?$Html:preg_replace("/\n/","{br}",$Text) or die("neither text nor html part present.");
//	$Text = $Text?$Text:"Sorry, but you need an html mailer to read this mail.";
//	$From or die("sender address missing");
//	$To or die("recipient address missing");

	if ((strlen($Html) < 1) && (strlen($Text) < 1)) { 
		$errormessage = __("You did not provide any text to send by email!"); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}
	if (strlen($From) < 1) { 
		$errormessage = __("You did not supply a From address."); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}
	if (strlen($To) < 1) { 
		$errormessage = __("You did not supply a To address."); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}
	if (strlen($Html) < 1) { 
		$Html = preg_replace("/\n/","<br>",$Text); 
	}

// Check if the To email address is valid
	if (!eregi( "^" .
	           "[a-zA-Z0-9]+([_\.-][a-zA-Z0-9]+)*" .    //user
	           "@" .
	           "([a-zA-Z0-9]+([\.-][a-zA-Z0-9]+)*)+" .  //domain
	           "\\.[a-zA-Z]{2,}" .                      //sld, tld 
	           "$", $To, $regs)) { 
		$errormessage = __("The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>", $To); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Definition of some variables
	$OB = "----=_OuterBoundary_000";
	$IB = "----=_InnerBoundery_001";

// Headers
	$headers ="MIME-Version: 1.0\r\n"; 
	$headers.="From: ".$FromName." <".$From.">\n"; 
	$headers.="To: ".$ToName." <".$To.">\n"; 
	$headers.="Reply-To: ".$FromName." <".$From.">\n"; 
	$headers.="X-Priority: 1\n"; 
	$headers.="X-MSMail-Priority: High\n"; 
	$headers.="X-Mailer: My PHP Mailer\n"; 
	$headers.="Content-Type: multipart/mixed;\n\tboundary=\"".$OB."\"\n";

// Messages start with text/html alternatives in OB
	$Msg ="This is a multi-part message in MIME format.\n";
	$Msg.="\n--".$OB."\n";
	$Msg.="Content-Type: multipart/alternative;\n\tboundary=\"".$IB."\"\n\n";

// Plaintext section 
	$Msg.="\n--".$IB."\n";
	$Msg.="Content-Type: text/plain;\n\tcharset=\"iso-8859-1\"\n";
	$Msg.="Content-Transfer-Encoding: quoted-printable\n\n";

// Plaintext goes here
	$Msg.=$Text."\n\n";

// Html section 
	$Msg.="\n--".$IB."\n";
	$Msg.="Content-Type: text/html;\n\tcharset=\"iso-8859-1\"\n";
	$Msg.="Content-Transfer-Encoding: base64\n\n";

// Html goes here 
	$Msg.=chunk_split(base64_encode($Html))."\n\n";

// End of IB
	$Msg.="\n--".$IB."--\n";

// Attachments
	if($AttmFiles){
		foreach($AttmFiles as $AttmFile){
//			$patharray = explode ("/", $AttmFile); 
//			$FileName=$patharray[sizeof($patharray)-1];
			$FileName = "RequestedFile.zip";

			$Msg.= "\n--".$OB."\n";
			$Msg.="Content-Type: application/octet-stream;\n\tname=\"".$FileName."\"\n";
			$Msg.="Content-Transfer-Encoding: base64\n";
			$Msg.="Content-Disposition: attachment;\n\tfilename=\"".$FileName."\"\n\n";

// File goes here
			$FileContent = local_readfile($AttmFile);
			if ($net2ftp_result["success"] == false) { return false; }

			$FileContent = chunk_split(base64_encode($FileContent));
			$Msg.=$FileContent;
			$Msg.="\n\n";
		} // end for
	} // end if

// Message ends
	$Msg.="\n--".$OB."--\n";

// Send mail
	$success2 = mail($To, $Subject, $Msg, $headers); 
	if ($success2 == false) { 
		$errormessage = __("Due to technical problems the email to <b>%1\$s</b> could not be sent.", $To); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

// Logging
	//syslog(LOG_INFO,"Mail: Message sent to $ToName <$To>");

} // end function SendMail

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function printDirFileProperties($number, $entry, $checkbox_hidden, $onClick) {

// --------------
// Prints a checkbox and some hidden fields
// $onClick should be like "onClick=\"do_something_javascript();\""
// --------------

// Replace ' by &#039; to avoid errors when using this variable in an HTML value tag
//	$dirfilename_html = htmlspecialchars($dirfilename, ENT_QUOTES);

// Print checkbox or hidden field
	if ($checkbox_hidden == "checkbox") {
		echo "<input type=\"checkbox\" name=\"list[$number][dirfilename]\" id=\"list_" . $number . "_dirfilename\" value=\"" . $entry["dirfilename_html"] . "\" $onClick />\n";
	}
	else {
		echo "<input type=\"hidden\"   name=\"list[$number][dirfilename]\" value=\"" . $entry["dirfilename_html"] . "\" />\n";
	}

// Print hidden fields
	echo "<input type=\"hidden\"   name=\"list[$number][dirorfile]\"    value=\"" . $entry["dirorfile"] . "\" />\n";
	echo "<input type=\"hidden\"   name=\"list[$number][size]\"         value=\"" . $entry["size"] . "\" />\n";
	echo "<input type=\"hidden\"   name=\"list[$number][selectable]\"   value=\"" . $entry["selectable"] . "\" />\n";
	echo "<input type=\"hidden\"   name=\"list[$number][permissions]\"  value=\"" . $entry["permissions"] . "\" />\n";
	echo "<input type=\"hidden\"   name=\"list[$number][mtime]\"        value=\"" . $entry["mtime"] . "\" />\n";

} // end printDirFileProperties

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function getSelectedEntries($list) {

// --------------
// Input = array where dirfilename is set if the entry was selected, not set if not selected:
//   [1] => Array ( [dirfilename] => dir1 [dirorfile] => d [size] => 0 [selectable] => ok      [permissions] => ---rw-rw- )   <-- selected
//   [2] => Array ( [dirfilename] => dir2 [dirorfile] => d [size] => 0 [selectable] => ok      [permissions] => ---rw-rw- )   <-- selected 
//   [3] => Array ( [dirfilename] => dir3 [dirorfile] => d [size] => 0 [selectable] => too_big [permissions] => ---rw-rw- )   <-- selected
//   [4] => Array (                       [dirorfile] => d [size] => 0 [selectable] => ok      [permissions] => ---rw-rw- )   <-- not selected
//
// Output = array with only the selected entries, which are not TOO BIG or which do not contain a forbidden keyword
//   [1] => Array ( [dirfilename] => dir1 [dirorfile] => d [size] => 0 [permissions] => ---rw-rw- ) 
//   [2] => Array ( [dirfilename] => dir2 [dirorfile] => d [size] => 0 [permissions] => ---rw-rw- ) 
// --------------

// Global variables
	global $net2ftp_globals;

	$newlist = array();
	$newlist["directories"]  = array();
	$newlist["files"]        = array();
	$newlist["symlinks"]     = array();
	$newlist["unrecognized"] = array();

	$directory_index    = 1;
	$file_index         = 1;
	$symlink_index      = 1;
	$unrecognized_index = 1;
	$all_index          = 1;

	for ($i=1; $i<=sizeof($list); $i=$i+1) {
		if (isset($list[$i]["dirorfile"]) == true && isset($list[$i]["dirfilename"]) == true) {

			if (isset($list[$i]["selectable"]) == false || $list[$i]["selectable"] != "ok" && 
			($net2ftp_globals["state"] == "downloadfile" || $net2ftp_globals["state"] == "downloadzip" || 
			$net2ftp_globals["state"] == "edit" || $net2ftp_globals["state"] == "findstring" || 
			$net2ftp_globals["state"] == "unzip" || $net2ftp_globals["state"] == "view" || 
			$net2ftp_globals["state"] == "zip" || 
			$net2ftp_globals["state2"] == "copy" || $net2ftp_globals["state2"] == "move")) { 
				continue; 
			}

			$list[$i]["dirfilename"]      = validateGenericInput($list[$i]["dirfilename"]);
			$list[$i]["dirfilename_html"] = htmlEncode2($list[$i]["dirfilename"]);
			$list[$i]["dirfilename_js"]   = javascriptEncode2($list[$i]["dirfilename"]);
			if ($list[$i]["dirorfile"] == "d") {
				$newlist["directories"][$directory_index] = $list[$i]; 
				$directory_index++;
				$newlist["all"][$all_index] = $list[$i]; 
				$all_index++;
			}
			elseif ($list[$i]["dirorfile"] == "-") {
				$newlist["files"][$file_index] = $list[$i]; 
				$file_index++;
				$newlist["all"][$all_index] = $list[$i]; 
				$all_index++;
			}
			elseif ($list[$i]["dirorfile"] == "l") {
				$newlist["symlinks"][$symlink_index] = $list[$i]; 
				$symlink_index++;
				$newlist["all"][$all_index] = $list[$i]; 
				$all_index++;
			}
			elseif ($list[$i]["dirorfile"] == "u") {
				$newlist["unrecognized"][$unrecognized_index] = $list[$i]; 
				$unrecognized_index++;
				$newlist["all"][$all_index] = $list[$i]; 
				$all_index++;
			}
		}
	} // end for

// Store the statistics
	$newlist["stats"]["directories"]["total_number"]  = $directory_index - 1;
	$newlist["stats"]["files"]["total_number"]        = $file_index - 1;
	$newlist["stats"]["symlinks"]["total_number"]     = $symlink_index - 1;
	$newlist["stats"]["unrecognized"]["total_number"] = $unrecognized_index - 1;

	return $newlist;

} // end getSelectedEntries

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function formatFilesize($filesize) {

// --------------
// From php.net, code snippet submitted by sponger (10-Jun-2002 05:28)
// Edited for use in net2ftp.
//
// This may come in handy for someone.
// Returns the size of the passed file in the appropriate measurement format.
// --------------

// Setup some common file size measurements.
	$kb = 1024;         // Kilobyte
	$mb = 1048576;      // Megabyte
	$gb = 1073741824;   // Gigabyte
	$tb = 1099511627776;// Terabyte

// If it's less than a kb we just return the size, otherwise we keep going until
//   the size is in the appropriate measurement range.
	if($filesize< $kb) {
		return $filesize." B";
	}
	elseif($filesize< $mb) {
		return round($filesize/$kb,2) . " kB";
	}
	elseif($filesize< $gb) {
		return round($filesize/$mb,2) . " MB";
	}
	elseif($filesize< $tb) {
		return round($filesize/$gb,2) . " GB";
	}
	else {
		return round($filesize/$tb,2) . " TB";
	}

} // end formatFilesize

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function tempnam2($dir, $prefix, $postfix) {

// --------------
// Contributed by anonymous on http://www.php.net on 23-Jul-2003 04:56
// The tempnam() function will not let you specify a postfix to the filename created. 
// Here is a function that will create a new filename with pre and post fix'es. 
// It returns false if it can't create in the dir specified. (The function tempnam, on the contrary, creates the file in the systems temp dir.)
// --------------

	if ($dir[strlen($dir) - 1] == '/') { $trailing_slash = ""; }
	else { $trailing_slash = "/"; }
 
// Check if the $dir is a directory
	if (!is_dir($dir) || filetype($dir) != "dir") { return false; }

// Check if the directory is writeable
	if (!is_writable($dir)){ return false; }

// Create the temporary filename
	do { 
		$seed = substr(md5(microtime()), 0, 8);
		$filename = $dir . $trailing_slash . $prefix . $seed . $postfix;
		clearstatcache();
		$file_exists = file_exists($filename);
	} while ($file_exists == true);

	$fp = fopen($filename, "wb");
	fclose($fp);
	return $filename;

} // end tempnam2

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function tempdir2($dir, $prefix, $postfix) {

// --------------
// Same as tempnam2 but creates a directory instead of a file
// --------------

	if ($dir[strlen($dir) - 1] == '/') { $trailing_slash = ""; }
	else { $trailing_slash = "/"; }
 
// Check if the $dir is a directory
	if (!is_dir($dir) || filetype($dir) != "dir") { return false; }

// Check if the directory is writeable
	if (!is_writable($dir)){ return false; }

// Create the temporary filename
	do { 
		$seed = substr(md5(microtime()), 0, 8);
		$filename = $dir . $trailing_slash . $prefix . $seed . $postfix;
	} while (!mkdir($filename, 0777));

	return $filename;

} // end tempdir2

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function local_readfile($file) {

// --------------
// Open the local file $file and return its content as a string
// --------------

	global $net2ftp_globals;

// From the PHP manual:
// Note:  The mode may contain the letter 'b'. 
// This is useful only on systems which differentiate between binary and text 
// files (i.e. Windows. It's useless on Unix). If not needed, this will be 
// ignored. You are encouraged to include the 'b' flag in order to make your scripts 
// more portable.
// Thanks to Malte for bringing this to my attention !

	$handle = fopen($file, "rb"); // Open the file for reading only
	if ($handle == false) { 
		$errormessage = __("Unable to open the temporary file. Check the permissions of the %1\$s directory.", $net2ftp_globals["application_tempdir"]); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

	clearstatcache(); // for filesize

	$filesize = filesize($file);
	if ($filesize == 0) { return ""; }

	$string = fread($handle, $filesize);
	if ($string == false && filesize($file)>0) { 
		$errormessage =  __("Unable to read the temporary file"); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

	$success3 = fclose($handle);
	if ($success3 == false) { 
		$errormessage = __("Unable to close the handle of the temporary file"); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

	return $string;

} // end local_readfile

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************




// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function local_writefile($file, $string) {

// --------------
// Open the local file $file and write the $string to it
// --------------

	global $net2ftp_globals;

	$handle = fopen($file, "wb");
	if ($handle == false) { 
		$errormessage = __("Unable to open the temporary file. Check the permissions of the %1\$s directory.", $net2ftp_globals["application_tempdir"]); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

	$success1 = fwrite($handle, $string);
	if ($success1 == false && strlen($string)>0) { 
		$errormessage = __("Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory.", $file, $net2ftp_globals["application_tempdir"]); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

	$success2 = fclose($handle);
	if ($success2 == false) { 
		$errormessage = __("Unable to close the handle of the temporary file"); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}

} // end local_writefile

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************




// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function local_getlist($localdirectory) {

// --------------
// This function gets the list of subdirectories and files within a directory,
// and returns it in the same format as ftp_getlist.
// Big differences with ftp_getlist is that local_getlist:
//   - does not return a warning message;
//   - cannot change the $localdirectory.
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_result;

// -------------------------------------------------------------------------
// Initialization
// -------------------------------------------------------------------------
	$handle = opendir($localdirectory);
	$list = array();
	$list_directories = array();
	$list_files = array();
	$i = 0;
	$j = 1;

// -------------------------------------------------------------------------
// While loop 
// -------------------------------------------------------------------------
	while (false !== ($dirfilename = readdir($handle))) {
		if ($dirfilename != "." && $dirfilename != "..") {
			if (is_dir($dirfilename) == true) { 
				$listline["scanrule"]     = "local";
				$listline["dirorfile"]    = "d"; 
				$listline["dirfilename"]  = $dirfilename;
				$listline["size"]         = 0; 
				$listline["selectable"]   = "ok"; 
				array_push($list_directories, $listline);
			}
			else { 
				$listline["scanrule"]    = "local";
				$listline["dirorfile"]   = "-"; 
				$listline["dirfilename"] = $dirfilename;
				$listline["size"]        = filesize($localdirectory . "/" . $dirfilename);

				// Check if the filesize is bigger than the maximum authorized filesize
				if (isset($listline["size"]) && is_numeric($listline["size"]) && $listline["size"] > $net2ftp_settings["max_filesize"]) { 
					$listline["selectable"] = "too_big"; 
				}
				else { 
					$listline["selectable"] = "ok"; 
				}

				array_push($list_files, $listline);
			}
		}
	} // end while

	for ($i=0; $i<sizeof($list_directories); $i=$i+1)  { $list[$j] = $list_directories[$i]; $j=$j+1; }
	for ($i=0; $i<sizeof($list_files); $i=$i+1)        { $list[$j] = $list_files[$i]; $j=$j+1; }


// -------------------------------------------------------------------------
// End
// -------------------------------------------------------------------------
	closedir($handle);

	return $list;

} // End function local_getlist

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function delete_dirorfile($dirorfile) {

// --------------
// This function deletes a local directory recursively
// Credit goes to itportal at gmail dot com, 17-Jul-2006 05:29
// --------------
// THIS FUNCTION IS ALMOST IDENTICAL TO THE ONE IN /modules/install/net2ftp_installer.txt
// Difference: this one only runs in execute mode, doesn't echo any output and returns a value.
// $return = true if all went well, false if there was an error somewhere.
// --------------

	$result = true;

	if (is_dir($dirorfile)) { 
		$directory = $dirorfile;
		if(substr($dir, -1, 1) == "/"){
			$directory = substr($directory, 0, strlen($directory) - 1);
		}
		if ($handle = opendir("$directory")) {
			while (false !== ($item = readdir($handle))) {
				if ($item != "." && $item != "..") {
					if (is_dir("$directory/$item")) { 
						$delete_dirorfile_result = delete_dirorfile("$directory/$item", $mode); 
						$result = $result && $delete_dirorfile_result;
					} 
					else { 
						$unlink_result = unlink("$directory/$item"); 
						$result = $result && $unlink_result;
					}
				}
			}
			closedir($handle);
			$rmdir_result = rmdir($directory);
			$result = $result && $rmdir_result;
		}
	}
	elseif (is_file($dirorfile)) {
		$file = $dirorfile;
		$unlink_result = unlink($file); 
		$result = $result && $unlink_result;
	}

	return $result;

} // End delete_dirorfile

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function sendDownloadHeaders($filename, $filesize) {

// --------------
// This function sends download headers to the browser
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals;

// -------------------------------------------------------------------------
// Clean the input, and encode the filename with htmlentities
// -------------------------------------------------------------------------
	$filename = trim($filename);
	$filename_html = htmlEncode2($filename);

// -------------------------------------------------------------------------
// Check which is the content type and disposition
// -------------------------------------------------------------------------
	$content_type = getContentType($filename);

	$content_disposition = "attachment";
	if (strpos($filename, ".zip") !== false) { $content_disposition = "inline"; }

// -------------------------------------------------------------------------
// Send the headers - Internet Explorer
// From PhpMyAdmin 2.8.0.2 file export.php
// -------------------------------------------------------------------------
	header("Content-Type: " . $content_type);
        header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Content-Disposition: $content_disposition; filename=\"" . $filename_html . "\"");

	if ($net2ftp_globals["browser_agent"] == "IE") {
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: public");
	} 
	else {
		header("Pragma: no-cache");
	}

	header("Content-Description: $filename_html");
	header("Content-Length: $filesize"); 
	header("Connection: close");

} // End function sendDownloadHeaders

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>