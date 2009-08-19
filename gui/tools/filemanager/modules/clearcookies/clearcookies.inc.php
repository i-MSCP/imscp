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

	setcookie("net2ftpcookie_ftpserver",     "", 1);
	setcookie("net2ftpcookie_ftpserverport", "", 1);
	setcookie("net2ftpcookie_username",      "", 1);
	setcookie("net2ftpcookie_language",      "", 1);
	setcookie("net2ftpcookie_skin",          "", 1);
	setcookie("net2ftpcookie_ftpmode",       "", 1);
	setcookie("net2ftpcookie_passivemode",   "", 1);
	setcookie("net2ftpcookie_sslconnect",    "", 1);
	setcookie("net2ftpcookie_viewmode",      "", 1);
	setcookie("net2ftpcookie_sort",          "", 1);
	setcookie("net2ftpcookie_sortorder",     "", 1);
	setcookie("net2ftpcookie_directory",     "", 1);

	header("Location: index.php");
	
} // end net2ftp_sendHttpHeaders

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************


?>