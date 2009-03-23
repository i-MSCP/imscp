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

function encryptPassword($password) {

// --------------
// This function encrypts the FTP password
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_settings;

// -------------------------------------------------------------------------
// If mcrypt libraries are available, encrypt the password with the Stone PHP SafeCrypt library
// http://blog.sc.tri-bit.com/archives/101
// -------------------------------------------------------------------------
//	if (function_exists("mcrypt_module_open") == true) {
//		$packed = PackCrypt($password, DEFAULT_MD5_SALT);
//		if ($packed["success"] == true) { return $packed["output"]; }
//		else { 
//			setErrorVars(false, "An error occured when trying to encrypt the password: " . $packed["reason"], debug_backtrace(), __FILE__, __LINE__);		
//		}
//	}
// -------------------------------------------------------------------------
// Else, XOR it with a random string
// -------------------------------------------------------------------------
//	else {
		$password_encrypted = "";
		$encryption_string = sha1($net2ftp_settings["encryption_string"]);
		if ($encryption_string % 2 == 1) { // we need even number of characters
			$encryption_string .= $encryption_string{0};
		}
		for ($i=0; $i < strlen($password); $i++) { // encrypts one character - two bytes at once
			$password_encrypted .= sprintf("%02X", hexdec(substr($encryption_string, 2*$i % strlen($encryption_string), 2)) ^ ord($password{$i}));
		}
		return $password_encrypted;
//	}

} // End function encryptPassword

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function decryptPassword($password_encrypted) {

// --------------
// This function decrypts the FTP password
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_settings;

// -------------------------------------------------------------------------
// If mcrypt libraries are available, encrypt the password with the Stone PHP SafeCrypt library
// http://blog.sc.tri-bit.com/archives/101
// -------------------------------------------------------------------------
//	if (function_exists("mcrypt_module_open") == true) {
//		$unpacked = UnpackCrypt($password_encrypted, DEFAULT_MD5_SALT);
//		if ($unpacked["success"] == true) { return $unpacked["output"]; }
//		else { 
//			setErrorVars(false, "An error occured when trying to decrypt the password: " . $unpacked["reason"], debug_backtrace(), __FILE__, __LINE__);		
//		}
//	}

// -------------------------------------------------------------------------
// Else, XOR it with a random string
// -------------------------------------------------------------------------
//	else {
		$password = "";
		$encryption_string = sha1($net2ftp_settings["encryption_string"]);
		if ($encryption_string % 2 == 1) { // we need even number of characters
			$encryption_string .= $encryption_string{0};
		}
		for ($i=0; $i < strlen($password_encrypted); $i += 2) { // decrypts two bytes - one character at once
			$password .= chr(hexdec(substr($encryption_string, $i % strlen($encryption_string), 2)) ^ hexdec(substr($password_encrypted, $i, 2)));
		}
		return $password;
//	}

} // End function decryptPassword

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************






// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function printLoginInfo() {

// --------------
// This function prints the ftpserver, username and login information
// --------------

	global $net2ftp_globals;

	echo "<input type=\"hidden\" name=\"ftpserver\"          value=\"" . htmlEncode2($net2ftp_globals["ftpserver"]) . "\" />\n";
	echo "<input type=\"hidden\" name=\"ftpserverport\"      value=\"" . htmlEncode2($net2ftp_globals["ftpserverport"]) . "\" />\n";
	echo "<input type=\"hidden\" name=\"username\"           value=\"" . htmlEncode2($net2ftp_globals["username"]) . "\" />\n";
//	echo "<input type=\"hidden\" name=\"password_encrypted\" value=\"" . htmlEncode2($net2ftp_globals["password_encrypted"]) . "\" />\n";
	echo "<input type=\"hidden\" name=\"language\"           value=\"" . htmlEncode2($net2ftp_globals["language"]) . "\" />\n";
	echo "<input type=\"hidden\" name=\"skin\"               value=\"" . htmlEncode2($net2ftp_globals["skin"]) . "\" />\n";
	echo "<input type=\"hidden\" name=\"ftpmode\"            value=\"" . htmlEncode2($net2ftp_globals["ftpmode"]) . "\" />\n";
	echo "<input type=\"hidden\" name=\"passivemode\"        value=\"" . htmlEncode2($net2ftp_globals["passivemode"]) . "\" />\n";
	echo "<input type=\"hidden\" name=\"sslconnect\"         value=\"" . htmlEncode2($net2ftp_globals["sslconnect"]) . "\" />\n";
	echo "<input type=\"hidden\" name=\"viewmode\"           value=\"" . htmlEncode2($net2ftp_globals["viewmode"]) . "\" />\n";
	echo "<input type=\"hidden\" name=\"sort\"               value=\"" . htmlEncode2($net2ftp_globals["sort"]) . "\" />\n";
	echo "<input type=\"hidden\" name=\"sortorder\"          value=\"" . htmlEncode2($net2ftp_globals["sortorder"]) . "\" />\n";

} // End function printLoginInfo

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************






// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function printLoginInfo_javascript() {

// --------------
// This function prints the ftpserver, username and login information -- for javascript input
// --------------

	global $net2ftp_globals;

	echo "	d.writeln('<input type=\"hidden\" name=\"ftpserver\"          value=\"" . javascriptEncode2($net2ftp_globals["ftpserver"]) . "\" />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"ftpserverport\"      value=\"" . javascriptEncode2($net2ftp_globals["ftpserverport"]) . "\" />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"username\"           value=\"" . javascriptEncode2($net2ftp_globals["username"]) . "\" />');\n";
//	echo "	d.writeln('<input type=\"hidden\" name=\"password_encrypted\" value=\"" . javascriptEncode2($net2ftp_globals["password_encrypted"]) . "\" />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"language\"           value=\"" . javascriptEncode2($net2ftp_globals["language"]) . "\" />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"skin\"               value=\"" . javascriptEncode2($net2ftp_globals["skin"]) . "\" />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"ftpmode\"            value=\"" . javascriptEncode2($net2ftp_globals["ftpmode"]) . "\" />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"passivemode\"        value=\"" . javascriptEncode2($net2ftp_globals["passivemode"]) . "\" />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"sslconnect\"         value=\"" . javascriptEncode2($net2ftp_globals["sslconnect"]) . "\" />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"viewmode\"           value=\"" . javascriptEncode2($net2ftp_globals["viewmode"]) . "\" />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"sort\"               value=\"" . javascriptEncode2($net2ftp_globals["sort"]) . "\" />');\n";
	echo "	d.writeln('<input type=\"hidden\" name=\"sortorder\"          value=\"" . javascriptEncode2($net2ftp_globals["sortorder"]) . "\" />');\n";

} // End function printLoginInfo_javascript

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************






// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function printPHP_SELF($case) {

// --------------
// This function prints $PHP_SELF, the name of the script itself
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings;

	$ftpserver          = urlEncode2($net2ftp_globals["ftpserver"]);
	$ftpserverport      = urlEncode2($net2ftp_globals["ftpserverport"]);
	$username           = urlEncode2($net2ftp_globals["username"]);
	$language           = urlEncode2($net2ftp_globals["language"]);
	$skin               = urlEncode2($net2ftp_globals["skin"]);
	$ftpmode            = urlEncode2($net2ftp_globals["ftpmode"]);
	$passivemode        = urlEncode2($net2ftp_globals["passivemode"]);
	$sslconnect         = urlEncode2($net2ftp_globals["sslconnect"]);
	$viewmode           = urlEncode2($net2ftp_globals["viewmode"]);
	$sort               = urlEncode2($net2ftp_globals["sort"]);
	$sortorder          = urlEncode2($net2ftp_globals["sortorder"]);
	$state_html         = urlEncode2($net2ftp_globals["state"]);
	$state2_html        = urlEncode2($net2ftp_globals["state2"]);
	$directory_html     = urlEncode2($net2ftp_globals["directory"]);
	$entry_html         = urlEncode2($net2ftp_globals["entry"]);

	if (isset($_SESSION["net2ftp_password_encrypted_" . $net2ftp_globals["ftpserver"] . $net2ftp_globals["username"]]) == true) { 
		$password_encrypted = urlEncode2($_SESSION["net2ftp_password_encrypted_" . $net2ftp_globals["ftpserver"] . $net2ftp_globals["username"]]);
	}
	elseif (isset($net2ftp_globals["password_encrypted"]) == true) { 
		$password_encrypted = urlEncode2($net2ftp_globals["password_encrypted"]); 
	}
	else {
		$password_encrypted = "";
	}

// From /includes/registerglobals.inc.php
	$URL = $net2ftp_globals["action_url"];

// If the URL already contains parameters (?param1=value1&amp;param2=value2...), append &amp;
// If not, append a ?
	if (strpos($URL, "?") !== false) { $URL .= "&amp;"; }
	else                             { $URL .= "?"; }

// Append further parameters
	if     ($case == "actions") {
		$URL .= "ftpserver=$ftpserver&amp;ftpserverport=$ftpserverport&amp;username=$username&amp;language=$language&amp;skin=$skin&amp;ftpmode=$ftpmode&amp;passivemode=$passivemode&amp;sslconnect=$sslconnect&amp;viewmode=$viewmode&amp;sort=$sort&amp;sortorder=$sortorder";
	}
	elseif ($case == "bookmark") {
		$URL .= "ftpserver=$ftpserver&amp;amp;ftpserverport=$ftpserverport&amp;amp;username=$username&amp;amp;language=$language&amp;amp;skin=$skin&amp;amp;ftpmode=$ftpmode&amp;amp;passivemode=$passivemode&amp;amp;sslconnect=$sslconnect&amp;amp;viewmode=$viewmode&amp;amp;sort=$sort&amp;amp;sortorder=$sortorder&amp;amp;state=login_small&amp;amp;state2=bookmark&amp;amp;go_to_state=$state_html&amp;amp;go_to_state2=$state2_html&amp;amp;directory=$directory_html&amp;amp;entry=$entry_html";
	}
// Jupload java applet: the cookie information is added to the page using javascript (/skins/blue/jupload1.template.php)
	elseif ($case == "jupload") {
		$URL .= "ftpserver=$ftpserver&amp;ftpserverport=$ftpserverport&amp;username=$username&amp;language=$language&amp;skin=$skin&amp;ftpmode=$ftpmode&amp;passivemode=$passivemode&amp;sslconnect=$sslconnect&amp;directory=$directory_html&amp;state=jupload&amp;screen=2";
	}
// SWFUpload Flash applet: cookie information can't be used, so the password must be added to the URL
// See also http://swfupload.mammon.se/forum/viewtopic.php?pid=172
// The URL must also use & instead of &amp;
	elseif ($case == "swfupload") {
		$URL .= "ftpserver=$ftpserver&ftpserverport=$ftpserverport&username=$username&password_encrypted=$password_encrypted&language=$language&skin=$skin&ftpmode=$ftpmode&passivemode=$passivemode&sslconnect=$sslconnect&directory=$directory_html&state=upload&screen=2";
	}
	elseif ($case == "view") {
		$URL .= "ftpserver=$ftpserver&amp;ftpserverport=$ftpserverport&amp;username=$username&amp;language=$language&amp;skin=$skin&amp;ftpmode=$ftpmode&amp;passivemode=$passivemode&amp;sslconnect=$sslconnect&amp;viewmode=$viewmode&amp;sort=$sort&amp;sortorder=$sortorder&amp;state=$state_html&amp;state2=image&amp;directory=$directory_html&amp;entry=$entry_html";
	}
	elseif ($case == "createDirectoryTreeWindow") {
		$URL = $net2ftp_globals["application_rootdir_url"] . "/index.php";
	}

	return $URL;

} // End function printPHP_SELF

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************






// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function checkAuthorization($ftpserver, $ftpserverport, $directory, $username) {

// --------------
// This function
//    checks if the FTP server is in the list of those that may be accessed
//    checks if the FTP server is in the list of those that may NOT be accessed
//    checks if the IP address is in the list of banned IP addresses
//    checks if the FTP server port is in the allowed range
// If all is OK, then the user may continue...
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result;


// -------------------------------------------------------------------------
// Check if the FTP server is in the list of those that may be accessed
// -------------------------------------------------------------------------
	if ($net2ftp_settings["allowed_ftpservers"][1] != "ALL") {
		$result1 = array_search($ftpserver, $net2ftp_settings["allowed_ftpservers"]);
		if ($result1 == false) {
			$errormessage = __("The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers.", $ftpserver);
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
	}


// -------------------------------------------------------------------------
// Check if the FTP server is in the list of those that may NOT be accessed
// -------------------------------------------------------------------------
	if (isset($net2ftp_settings["banned_ftpservers"][1]) == true && $net2ftp_settings["banned_ftpservers"][1] != "NONE") {
		$result2 = array_search($ftpserver, $net2ftp_settings["banned_ftpservers"]);
		if ($result2 != false) {
			$errormessage = __("The FTP server <b>%1\$s</b> is in the list of banned FTP servers.", $ftpserver);
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
	}


// -------------------------------------------------------------------------
// Check if the FTP server port is OK
// -------------------------------------------------------------------------
// Do not perform this check if ALL ports are allowed
	if ($net2ftp_settings["allowed_ftpserverport"] != "ALL" ) {
// Report the error if another port nr has been entered than the one which is allowed
		if ($ftpserverport != $net2ftp_settings["allowed_ftpserverport"]) {
			$errormessage = __("The FTP server port %1\$s may not be used.", $ftpserverport);
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
	}


// -------------------------------------------------------------------------
// Check if the IP address is in the list of those that may be used
// -------------------------------------------------------------------------
	if ($net2ftp_settings["allowed_addresses"][1] != "ALL") {
// Old way of searching
// Does not work with IP address ranges in $net2ftp_settings["allowed_addresses"]
//		$result3 = array_search($net2ftp_globals["REMOTE_ADDR"], $net2ftp_settings["allowed_addresses"]);
// New way of searching
		$result3 = false;
		for ($i=1; $i<=sizeof($net2ftp_settings["allowed_addresses"]); $i++) {
			if (substr($net2ftp_globals["REMOTE_ADDR"], 0, strlen($net2ftp_settings["allowed_addresses"][$i])) == $net2ftp_settings["allowed_addresses"][$i]) { $result3 = true; }
		}
		if ($result3 == false) {
			$errormessage = __("Your IP address (%1\$s) is not in the list of allowed IP addresses.", $net2ftp_globals["REMOTE_ADDR"]);
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
	}


// -------------------------------------------------------------------------
// Check if the IP address is in the list of those that may NOT be used
// -------------------------------------------------------------------------
	if (isset($net2ftp_settings["banned_addresses"][1]) == true && $net2ftp_settings["banned_addresses"][1] != "NONE") {
// Old way of searching
// Does not work with IP address ranges in $net2ftp_settings["banned_addresses"]
//		$result4 = array_search($net2ftp_globals["REMOTE_ADDR"], $net2ftp_settings["banned_addresses"]);
// New way of searching
		$result4 = false;
		for ($i=1; $i<=sizeof($net2ftp_settings["banned_addresses"]); $i++) {
			if (substr($net2ftp_globals["REMOTE_ADDR"], 0, strlen($net2ftp_settings["banned_addresses"][$i])) == $net2ftp_settings["banned_addresses"][$i]) { $result4 = true; }
		}
		if ($result4 != false) {
			$errormessage = __("Your IP address (%1\$s) is in the list of banned IP addresses.", $net2ftp_globals["REMOTE_ADDR"]);
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}
	}





// -------------------------------------------------------------------------
// Check if the directory is authorised:
// 1 - Whether the current $directory name contains a banned keyword.
// 2 - If the current $directory is a subdirectory of the homedirectory. 
//     The rootdirectory is first checked for the current user; if this is not set, 
//     the default rootdirectory is checked.
// -------------------------------------------------------------------------
	$result4 = checkAuthorizedDirectory($directory);
	if ($net2ftp_result["success"] == false) { return false; }
	if ($result4 == false) {
		$net2ftp_globals["directory"]      = $net2ftp_globals["homedirectory"];
		$net2ftp_globals["directory_html"] = htmlEncode2($net2ftp_globals["directory"]);
		$net2ftp_globals["directory_js"]   = javascriptEncode2($net2ftp_globals["directory"]);
		if (strlen($net2ftp_globals["directory"]) > 0) { $net2ftp_globals["printdirectory"] = $net2ftp_globals["directory"]; }
		else                                           { $net2ftp_globals["printdirectory"] = "/"; }
	}


// -------------------------------------------------------------------------
// If everything is OK, return true
// -------------------------------------------------------------------------
	return true;

} // end checkAuthorization

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function checkAuthorizedDirectory($directory) {

// --------------
// 1 - This function checks whether the current $directory name contains a banned 
// keyword.
// 2 - It also checks if the current $directory is a subdirectory of the 
// homedirectory. The rootdirectory is first checked for the current user; 
// if this is not set, the default rootdirectory is checked.
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result;

// -------------------------------------------------------------------------
// 1 - Check if the directory name contains a banned keyword
// -------------------------------------------------------------------------
	if (checkAuthorizedName($directory) == false) { return false; }

// -------------------------------------------------------------------------
// 2 - Check if the directory is a subdirectory of the homedirectory (set in the DB)
// -------------------------------------------------------------------------

// ----------------------------------------------
// Initial checks
// ----------------------------------------------
	if ($net2ftp_settings["use_database"] != "yes" || $net2ftp_settings["check_homedirectory"] != "yes") { return true; }

// ----------------------------------------------
// Get the homedirectory from the database, then store it in a global
// variable, and from then on, don't access the database any more
// ----------------------------------------------
	$net2ftp_globals["homedirectory"] = getRootdirectory();

// ----------------------------------------------
// Check if the current directory is a subdirectory of the homedirectory
// ----------------------------------------------
	if (isSubdirectory($net2ftp_globals["homedirectory"], $directory) == false) { return false; }
	else { return true; }

} // end checkAuthorizedDirectory

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function checkAuthorizedName($dirfilename) {

// --------------
// This function checks if the directory/file/symlink name contains a forbidden keyword
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings;

// -------------------------------------------------------------------------
// Check
// -------------------------------------------------------------------------
	if (isset($net2ftp_settings["banned_keywords"][1]) == true && $net2ftp_settings["banned_keywords"][1] != "NONE") {
		for ($i=1; $i<=sizeof($net2ftp_settings["banned_keywords"]); $i++) {
			if (strpos($dirfilename, $net2ftp_settings["banned_keywords"][$i]) !== false) { return false; }
		}
	}

	return true;

} // end checkAuthorizedName

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function getRootdirectory() {

// --------------
// This function gets the user's root directory from the database and
// stores it in $net2ftp_globals["homedirectory"].
//
// If $net2ftp_globals["homedirectory"] is already filled in (cache), no connection
// is made to the DB and this value is returned.
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result;

// -------------------------------------------------------------------------
// Initial checks
// -------------------------------------------------------------------------
	if ($net2ftp_settings["use_database"] != "yes" || $net2ftp_settings["check_homedirectory"] != "yes") { 
		$net2ftp_globals["homedirectory"] = "/"; 
	}

// -------------------------------------------------------------------------
// Get the homedirectory from the database, then store it in a global
// variable, and from then on, don't access the database any more
// -------------------------------------------------------------------------
	if (isset($net2ftp_globals["homedirectory"]) == false) {

// -------------------------------------------------------------------------
// Add slashes to variables which are used in a SQL query, and which are
// potentially unsafe (supplied by the user)
// -------------------------------------------------------------------------
		$net2ftp_ftpserver_safe = addslashes($net2ftp_globals["ftpserver"]);
		$net2ftp_username_safe  = addslashes($net2ftp_globals["username"]);

// -------------------------------------------------------------------------
// Connect
// -------------------------------------------------------------------------
		$mydb = connect2db();
		if ($net2ftp_result["success"] == false) { return false; }

// -------------------------------------------------------------------------
// Get user's home directory
// -------------------------------------------------------------------------
		$sqlquery1 = "SELECT homedirectory FROM net2ftp_users WHERE ftpserver = '$net2ftp_ftpserver_safe' AND username = '$net2ftp_username_safe';";
		$result1   = mysql_query("$sqlquery1") or die("Unable to execute SQL SELECT query (isAuthorizedDirectory > sqlquery1) <br /> $sqlquery1");
		$nrofrows1 = mysql_num_rows($result1);

		if     ($nrofrows1 == 0) { 
			$net2ftp_globals["homedirectory"] = "/";
		}
		elseif ($nrofrows1 == 1) { 
			$resultRow1 = mysql_fetch_row($result1); 
			$net2ftp_globals["homedirectory"] = $resultRow1[0];
		}
		else { 
			setErrorVars(false, __("Table net2ftp_users contains duplicate rows."), debug_backtrace(), __FILE__, __LINE__);
			return false; 
		}
	}

	return $net2ftp_globals["homedirectory"];

} // end getRootdirectory

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function isSubdirectory($parentdir, $childdir) {

// --------------
// Returns true if the childdir is a subdirectory of the parentdir
// --------------

// If the parentdir is empty or the root directory, then the childdir is 
// a the same as or a subdirectory of the parentdir
	if ($parentdir == "" || $parentdir == "/" || $parentdir == "\\") { return true; }

// Strip the directories of leading and trailing slashes
	$parentdir = stripDirectory($parentdir);
	$childdir  = stripDirectory($childdir);
	$parentdir_length = strlen($parentdir);

// Check if the first characters of the childdir are different from the 
// parentdir. Example:
//     parentdir: /home/abc
//     childdir:  /home/blabla ==> false
//    childdir:  /home/abcd    ==> continue further checks
//    childdir:  /home/abc/xyz ==> continue further checks
	$childdir_firstchars = substr($childdir, 0, $parentdir_length);
	if ($childdir_firstchars != $parentdir) { return false; }

// If the first characters of the childdir are identical to the parentdir,
// check if the first next character of the childdir name is different. 
// Example:
//    parentdir: /home/abc
//    childdir:  /home/abcd    ==> false
//    childdir:  /home/abc/xyz ==> true
	$childdir_nextchar = substr($childdir, $parentdir_length, 1);
	if ($childdir_nextchar != "/" && $childdir_nextchar != "\\") { return false; }

	return true;
	
} // end isSubdirectory

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function logAccess() {

// --------------
// This function logs user accesses to the site
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result;

	if ($net2ftp_settings["log_access"] == "yes") {

// -------------------------------------------------------------------------
// Date and time
// -------------------------------------------------------------------------
		$date = date("Y-m-d");
		$time = date("H:i:s");

// -------------------------------------------------------------------------
// Logging to the database
// -------------------------------------------------------------------------

		if ($net2ftp_settings["use_database"] == "yes") {

// ----------------------------------------------
// Input checks
// ----------------------------------------------
// Add slashes to variables which are used in a SQL query, and which are
// potentially unsafe (supplied by the user).
			// $date is calculated in this function
			// $time is calculated in this function
			$REMOTE_ADDR_safe               = addslashes($net2ftp_globals["REMOTE_ADDR"]);
			$REMOTE_PORT_safe               = addslashes($net2ftp_globals["REMOTE_PORT"]);
			$HTTP_USER_AGENT_safe           = addslashes($net2ftp_globals["HTTP_USER_AGENT"]);
			$PHP_SELF_safe                  = addslashes($net2ftp_globals["PHP_SELF"]);
			if (isset($net2ftp_globals["consumption_datatransfer"]) == true) {
				$consumption_datatransfer_safe  = addslashes($net2ftp_globals["consumption_datatransfer"]);
			}
			else {
				$consumption_datatransfer_safe  = "0";
			}
			if (isset($net2ftp_globals["consumption_executiontime"]) == true) {
				$consumption_executiontime_safe = addslashes($net2ftp_globals["consumption_executiontime"]);
			}
			else {
				$consumption_executiontime_safe = "0";
			}
			$net2ftp_ftpserver_safe         = addslashes($net2ftp_globals["ftpserver"]);
			$net2ftp_username_safe          = addslashes($net2ftp_globals["username"]); 
			$state_safe                     = addslashes($net2ftp_globals["state"]);
			$state2_safe                    = addslashes($net2ftp_globals["state2"]);
			$screen_safe                    = addslashes($net2ftp_globals["screen"]);
			$directory_safe                 = addslashes($net2ftp_globals["directory"]);
			$entry_safe                     = addslashes($net2ftp_globals["entry"]);
			$HTTP_REFERER_safe              = addslashes($net2ftp_globals["HTTP_REFERER"]);

// ----------------------------------------------
// Connect to the DB
// ----------------------------------------------
			$mydb = connect2db();
			if ($net2ftp_result["success"] == false) { return false; }

// ----------------------------------------------
// Add record to the database table
// ----------------------------------------------
			$sqlquery1 = "INSERT INTO net2ftp_log_access VALUES(null, '$date', '$time', '$REMOTE_ADDR_safe', '$REMOTE_PORT_safe', '$HTTP_USER_AGENT_safe', '$PHP_SELF_safe', '$consumption_datatransfer_safe', '$consumption_executiontime_safe', '$net2ftp_ftpserver_safe', '$net2ftp_username_safe', '$state_safe', '$state2_safe', '$screen_safe', '$directory_safe', '$entry_safe', '$HTTP_REFERER_safe')";
			$result1 = @mysql_query($sqlquery1);
			if ($result1 == false) { 
				$errormessage = __("Unable to execute the SQL query.");
				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false;
			}

			$nrofrows1 = @mysql_affected_rows($mydb);

			$net2ftp_globals["log_access_id"] = mysql_insert_id();

		} // end if use_database

// -------------------------------------------------------------------------
// Logging to the system log
// -------------------------------------------------------------------------

		if ($net2ftp_settings["use_syslog"] == "yes") {

// ----------------------------------------------
// Get consumption values
// ----------------------------------------------
			if (isset($net2ftp_globals["consumption_datatransfer"]) == true) {
				$consumption_datatransfer  = $net2ftp_globals["consumption_datatransfer"];
			}
			else {
				$consumption_datatransfer  = "0";
			}
			if (isset($net2ftp_globals["consumption_executiontime"]) == true) {
				$consumption_executiontime = $net2ftp_globals["consumption_executiontime"];
			}
			else {
				$consumption_executiontime = "0";
			}

// ----------------------------------------------
// Create message
// ----------------------------------------------
			$message2log = "$date $time " . $net2ftp_globals["REMOTE_ADDR"] . " " . $net2ftp_globals["REMOTE_PORT"] . " " . $net2ftp_globals["PHP_SELF"] . " " . $consumption_datatransfer . " " . $consumption_executiontime . " " . $net2ftp_globals["ftpserver"] . " " . $net2ftp_globals["username"] . " " . $net2ftp_globals["state"] . " " . $net2ftp_globals["state2"] . " " . $net2ftp_globals["screen"] . " " . $net2ftp_globals["directory"] . " " . $net2ftp_globals["entry"];
			$result2 = openlog($net2ftp_settings["syslog_ident"], 0, $net2ftp_settings["syslog_facility"]);
			if ($result2 == false) { 
				$errormessage = __("Unable to open the system log.");
				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false;
			}

// ----------------------------------------------
// Write message to system logger
// ----------------------------------------------
			$result3 = syslog($net2ftp_settings["syslog_priority"], $message2log);
			if ($result3 == false) { 
				$errormessage = __("Unable to write a message to the system log.");
				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false;
			}

		} // end if use_syslog

	} // end if log_access

} // end logAccess()

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function logError() {

// --------------
// This function logs user accesses to the site
//
// IMPORTANT: this function uses, but does not change the global $net2ftp_result[""] variables.
// It returns true on success, false on failure.
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result;

	if ($net2ftp_settings["log_error"] == "yes") {

// -------------------------------------------------------------------------
// Take a copy of the $net2ftp_result
// If an error occurs within logError, logError will return false and reset the 
// $net2ftp_result variable to it's original value
// Also if no error occurs logError will return the variable to it's original value
// -------------------------------------------------------------------------
		$net2ftp_result_original = $net2ftp_result;
		setErrorVars(true, "", "", "", "");

// -------------------------------------------------------------------------
// Errormessage and debug backtrace
// -------------------------------------------------------------------------
		$errormessage = addslashes($net2ftp_result_original["errormessage"]);

		$debug_backtrace = "";
		$i = sizeof($net2ftp_result_original["debug_backtrace"])-1;
		if ($i > 0) {
			$debug_backtrace .= addslashes("function " . $net2ftp_result_original["debug_backtrace"][$i]["function"] . " (" . $net2ftp_result_original["debug_backtrace"][$i]["file"] . " on line " . $net2ftp_result_original["debug_backtrace"][$i]["line"] . ")\n");
			for ($j=0; $j<sizeof($net2ftp_result_original["debug_backtrace"][$i]["args"]); $j++) {
				$debug_backtrace .= addslashes("argument $j: " . $net2ftp_result_original["debug_backtrace"][$i]["args"][$j] . "\n");
			}
		}

// -------------------------------------------------------------------------
// Date and time
// -------------------------------------------------------------------------
		$date = date("Y-m-d");
		$time = date("H:i:s");

// -------------------------------------------------------------------------
// Logging to the database
// -------------------------------------------------------------------------
		if ($net2ftp_settings["use_database"] == "yes") {

// ----------------------------------------------
// Input checks
// ----------------------------------------------
// Add slashes to variables which are used in a SQL query, and which are
// potentially unsafe (supplied by the user).
			// $date is calculated in this function
			// $time is calculated in this function
			$net2ftp_ftpserver_safe = addslashes($net2ftp_globals["ftpserver"]);
			$net2ftp_username_safe  = addslashes($net2ftp_globals["username"]); 
			$state_safe             = addslashes($net2ftp_globals["state"]);
			$state2_safe            = addslashes($net2ftp_globals["state2"]);
			$directory_safe         = addslashes($net2ftp_globals["directory"]);
			$REMOTE_ADDR_safe       = addslashes($net2ftp_globals["REMOTE_ADDR"]);
			$REMOTE_PORT_safe       = addslashes($net2ftp_globals["REMOTE_PORT"]);
			$HTTP_USER_AGENT_safe   = addslashes($net2ftp_globals["HTTP_USER_AGENT"]);

// ----------------------------------------------
// Connect to the DB
// ----------------------------------------------
			$mydb = connect2db();
			if ($net2ftp_result["success"] == false) { 
				setErrorVars($net2ftp_result_original["success"], $net2ftp_result_original["errormessage"], $net2ftp_result_original["debug_backtrace"], $net2ftp_result_original["file"], $net2ftp_result_original["line"]);
				return false; 
			}

// ----------------------------------------------
// Add record to the database table
// ----------------------------------------------
			$sqlquerystring = "INSERT INTO net2ftp_log_error VALUES('$date', '$time', '$net2ftp_ftpserver_safe', '$net2ftp_username_safe', '$errormessage', '$debug_backtrace', '$state_safe', '$state2_safe', '$directory_safe', '$REMOTE_ADDR_safe', '$REMOTE_PORT_safe', '$HTTP_USER_AGENT_safe')";
			$result_mysql_query = @mysql_query($sqlquerystring);
			if ($result_mysql_query == false) { 
				setErrorVars($net2ftp_result_original["success"], $net2ftp_result_original["errormessage"], $net2ftp_result_original["debug_backtrace"], $net2ftp_result_original["file"], $net2ftp_result_original["line"]);
				return false; 
			}

		} // end if use_database

// -------------------------------------------------------------------------
// Logging to the system log
// -------------------------------------------------------------------------
		if ($net2ftp_settings["use_syslog"] == "yes") {

// ----------------------------------------------
// Get consumption values
// ----------------------------------------------
			if (isset($net2ftp_globals["consumption_datatransfer"]) == true) {
				$consumption_datatransfer  = $net2ftp_globals["consumption_datatransfer"];
			}
			else {
				$consumption_datatransfer  = "0";
			}
			if (isset($net2ftp_globals["consumption_executiontime"]) == true) {
				$consumption_executiontime = $net2ftp_globals["consumption_executiontime"];
			}
			else {
				$consumption_executiontime = "0";
			}

// ----------------------------------------------
// Create message
// ----------------------------------------------
			$message2log = "$date $time " . $net2ftp_globals["ftpserver"] . " " . $net2ftp_globals["username"] . " " . $net2ftp_result["errormessage"] . " $debug_backtrace " . $net2ftp_globals["state"] . " " . $net2ftp_globals["state2"] . " " . $net2ftp_globals["directory"] . " " . $net2ftp_globals["REMOTE_ADDR"] . " " . $net2ftp_globals["HTTP_USER_AGENT"];
			$result2 = openlog($net2ftp_settings["syslog_ident"], 0, $net2ftp_settings["syslog_facility"]);
			if ($result2 == false) { 
				$errormessage = __("Unable to open the system log.");
				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false;
			}

// ----------------------------------------------
// Write message to system logger
// ----------------------------------------------
			$result3 = syslog($net2ftp_settings["syslog_priority"], $message2log);
			if ($result3 == false) { 
				$errormessage = __("Unable to write a message to the system log.");
				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false;
			}

		} // end if use_syslog

// -------------------------------------------------------------------------
// Reset the variable to it's original value
// -------------------------------------------------------------------------
		setErrorVars($net2ftp_result_original["success"], $net2ftp_result_original["errormessage"], $net2ftp_result_original["debug_backtrace"], $net2ftp_result_original["file"], $net2ftp_result_original["line"]);

	} // end if logErrors

} // end logError()

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function emptyLogs($datefrom, $dateto) {

// --------------
// This function deletes the log records for the dates between $datefrom
// and $dateto.
// The global variable $net2ftp_output["emptyLogs"] is filled with result messages.
// The function returns true on success, false on failure.
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_result, $net2ftp_output;

	$toreturn = true;

// -------------------------------------------------------------------------
// Connect to the DB
// -------------------------------------------------------------------------
	$mydb = connect2db();
	if ($net2ftp_result["success"] == false)  { return false; }

	$tables[1] = "net2ftp_log_access";
	$tables[2] = "net2ftp_log_error";
	$tables[3] = "net2ftp_log_consumption_ftpserver";
	$tables[4] = "net2ftp_log_consumption_ipaddress";

// -------------------------------------------------------------------------
// Execute the queries
// -------------------------------------------------------------------------
	for ($i=1; $i<=sizeof($tables); $i++) {
		$sqlquery_empty   = "DELETE FROM $tables[$i] WHERE date BETWEEN '$datefrom' AND '$dateto';";
		$result_empty[$i] = mysql_query("$sqlquery_empty");

		$sqlquery_optimize   = "OPTIMIZE TABLE `" . $tables[$i] . "`;";
		$result_optimize[$i] = mysql_query("$sqlquery_optimize");

		if ($result_empty[$i] == true)    { 
			$net2ftp_output["emptyLogs"][] = __("The table <b>%1\$s</b> was emptied successfully.", $tables[$i]); 
		}
		else { 
			$toreturn = false;
			$net2ftp_output["emptyLogs"][] = __("The table <b>%1\$s</b> could not be emptied.", $tables[$i]); 
		}
		if ($result_optimize[$i] == true) { 
			$net2ftp_output["emptyLogs"][] = __("The table <b>%1\$s</b> was optimized successfully.", $tables[$i]); 
		}
		else { 
			$toreturn = false;
			$net2ftp_output["emptyLogs"][] = __("The table <b>%1\$s</b> could not be optimized.", $tables[$i]); 
		}

	} // end for

	return $toreturn;

} // end emptyLogs()

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function checkAdminUsernamePassword() {

// --------------
// This function checks the Administrator username and password.
// If one of the two is not filled in or incorrect, a header() is sent
// to redirect the user to the login_small page.
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result;
	$input_admin_username = $_POST["input_admin_username"];
	$input_admin_password = $_POST["input_admin_password"];

// -------------------------------------------------------------------------
// Check Admin username and password
// -------------------------------------------------------------------------

// Set the error message depending on the case
// Redirect the user to the login_small page

	// No username or password filled in
	if ($input_admin_username == "" || $input_admin_password == "") {
		$errormessage = htmlEncode2(__("You did not enter your Administrator username or password."));
		header("Location: " . $net2ftp_globals["action_url"] . "?state=login_small&state2=admin&go_to_state=" . $net2ftp_globals["state"] . "&go_to_state2=" . $net2ftp_globals["state2"] . "&errormessage=" . $errormessage);
		$net2ftp_result["exit"] = true;
		return false;
	}

	// Wrong username or password
	elseif ($input_admin_username != $net2ftp_settings["admin_username"] || 
              $input_admin_password != $net2ftp_settings["admin_password"]) {
		$errormessage = htmlEncode2(__("Wrong username or password. Please try again."));
		header("Location: " . $net2ftp_globals["action_url"] . "?state=login_small&state2=admin&go_to_state=" . $net2ftp_globals["state"] . "&go_to_state2=" . $net2ftp_globals["state2"] . "&errormessage=" . $errormessage);
		$net2ftp_result["exit"] = true;
		return false;
	}
	
	return true;

} // end checkAdminUsernamePassword()

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************



?>