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

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;


// -------------------------------------------------------------------------
// Construct the symlink target
// -------------------------------------------------------------------------

// A symlink has $entry = FreeBSD -> mirror/ftp.freebsd.org/pub/FreeBSD
// Get the 2nd part, after the ->
	$pos = strpos($net2ftp_globals["entry"], " -> ");
	$entry_part2 = substr($net2ftp_globals["entry"], $pos+4);

// Glue the current directory with the symlink
// and resolve the .. which it may contain (this is done by validateDirectory)
	$symlinktarget = validateDirectory(glueDirectories($net2ftp_globals["directory"], $entry_part2));

// -------------------------------------------------------------------------
// Check if the symlink points to a directory
// -------------------------------------------------------------------------

// ------------------------------------
// Open connection
// ------------------------------------
	$conn_id = ftp_openconnection();
	if ($net2ftp_result["success"] == false) { return false; }

// ------------------------------------
// Get raw list of directories and files
// ------------------------------------
	$list = ftp_getlist($conn_id, $symlinktarget);
	if ($net2ftp_result["success"] == false) { 
		$is_directory = false; 
		setErrorVars(true, "", "", "", ""); 
	}
	else {
		$is_directory = true;
	}

// ------------------------------------
// Close connection
// ------------------------------------
	ftp_closeconnection($conn_id);


// -------------------------------------------------------------------------
// Directory (main or popup): redirect to Browse page 
// -------------------------------------------------------------------------
	if ($is_directory == true) {
		$action_url = printPHP_SELF("actions");
		$action_url = str_replace("&amp;", "&", $action_url);
		header("Location: " . $action_url . "&state=browse&state2=" . $net2ftp_globals["state2"] . "&directory=" . $symlinktarget);
	}

// -------------------------------------------------------------------------
// File (popup): redirect to Browse page of same directory 
// -------------------------------------------------------------------------
	elseif ($net2ftp_globals["state2"] == "popup") {
		$action_url = printPHP_SELF("actions");
		$action_url = str_replace("&amp;", "&", $action_url);
		header("Location: " . $action_url . "&state=browse&state2=" . $net2ftp_globals["state2"] . "&directory=" . $net2ftp_globals["directory"]);
	}

// -------------------------------------------------------------------------
// File (main): download the file
// -------------------------------------------------------------------------
	elseif ($net2ftp_globals["state2"] == "main") {
		if ($net2ftp_settings["functionuse_downloadfile"] == "yes") {
			$newdirectory = dirname($symlinktarget);
			$newfile      = basename($symlinktarget);
			ftp_downloadfile($newdirectory, $newfile);
		}
		else {
			$errormessage = __("This function has been disabled by the Administrator of this website.");
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
	}



} // end net2ftp_sendHttpHeaders

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************


?>