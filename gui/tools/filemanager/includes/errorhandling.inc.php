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



/* -------------------------------------------------------------------------
   This is how error-handing works within net2ftp
   -------------------------------------------------------------------------

There are 3 global variables:
- $net2ftp_result["success"], which is true or false
- $net2ftp_result["errormessage"], which contains an error message
- $net2ftp_result["debug_backtrace"], which contains the debugging backtrace (to indicate *where* the error happened)

---------------------------------
Low-level function executes a standard PHP function
- If everything goes OK, the low-level function simply returns its $finalresult
- If there is an error, the global variable $net2ftp_result["success"] is set to false, and 
  $net2ftp_result["errormessage"] will be filled with the error message
---------------------------------
function low_level {
	$result = php_function();
	if ($result == false) { setErrorVars(false, "errormessage", debug_backtrace(), __FILE, __LINE__); return false; }
	...
	return $finalresult;
}

---------------------------------
Middle-level function executes a low-level function (it may also execute standard PHP functions)
- If everything goes OK, the middle-level function simply returns its $finalresult
- If there is an error, the function can either return to its parent, or continue
---------------------------------
function middle_level {
	global $net2ftp_result;
	$result = low_level();
// Return to its parent, leave the error message as is:
	if ($net2ftp_result["success"] == false) { return false; }
// Return to its parent, change the error message (leave the debug backtrace as is):
	if ($net2ftp_result["success"] == false) { setErrorVars(false, "errormessage2", $net2ftp_result["debug_backtrace"], __FILE, __LINE__); return false; }
// Reset the error variables and continue:
	if ($net2ftp_result["success"] == false) { setErrorVars(true, "", "", "", ""); }
	...
	return $finalresult;
// Print error message and exit -- THIS IS NOT DONE ANY MORE, as exit() calls must be avoided at all cost to
// keep net2ftp integrateable within other web applications.
// This case is replaced by case 1: return to the parent function, and leave the error message as is.
////	if ($net2ftp_result["success"] == false) { printErrorMessage(); }
}

---------------------------------
High-level function executes a middle-level function (it may also execute standard PHP functions)
- If everything goes OK, the high-level function simply returns its $finalresult
- If there is an error, the function returs to its parent (the script which called the net2ftp() function). It is
  up to the parent to see if and how an error message should be printed -- see index.php for an example.
---------------------------------
function high_level {
	global $net2ftp_result;
	$result = middle_level();
	if ($net2ftp_result["success"] == false) { return false; }
	...
	return $finalresult;
} 

------------------------------------------------------------------------- */






// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function setErrorVars($success, $errormessage, $debug_backtrace, $file, $line) {

// --------------
// This function modifies the 3 global error-handling variables
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_result, $net2ftp_settings;

// -------------------------------------------------------------------------
// Set the error-handling variables
// -------------------------------------------------------------------------
	$net2ftp_result["success"]         = $success;
	$net2ftp_result["errormessage"]    = $errormessage;
	$net2ftp_result["debug_backtrace"] = $debug_backtrace;
	$net2ftp_result["file"]            = $file;
	$net2ftp_result["line"]            = $line;

// -------------------------------------------------------------------------
// Log the error if an error occured ($success == false)
// If the error vars are set to true again ($success == true), don't log the error once more
// -------------------------------------------------------------------------
// DON'T LOG THE ERROR HERE, AS THE FUNCTION logError() MAY CALL setErrorVars() AGAIN,
// CAUSING AN INFINITE LOOP!
//	if ($success == false) {
//		logError();
//	}

} // end setErrorVars

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>