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

	global $net2ftp_globals, $net2ftp_settings;

	if ($net2ftp_settings["functionuse_downloadfile"] == "yes") {
		ftp_downloadfile($net2ftp_globals["directory"], $net2ftp_globals["entry"]);
	}
	else {
		$errormessage = __("This function has been disabled by the Administrator of this website.");
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}
	
} // end net2ftp_sendHttpHeaders

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************


?>