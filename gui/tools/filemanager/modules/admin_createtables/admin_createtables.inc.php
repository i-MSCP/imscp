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

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
//	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;

// -------------------------------------------------------------------------
// Check Admin username and password
// Redirect to the login_small page if needed
// -------------------------------------------------------------------------
	checkAdminUsernamePassword();
	
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

//	global $net2ftp_settings, $net2ftp_globals;

// Code
//	echo "<script type=\"text/javascript\"><!--\n";	
//	echo "//--></script>\n";

// Include
//	echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/login/login.js\"></script>\n";

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
// This function prints the login screen
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;

	if (isset($_POST["input_admin_username"]) == true) { $input_admin_username = htmlEncode2(validateGenericInput($_POST["input_admin_username"])); }
	else                                               { $input_admin_username = ""; }
	if (isset($_POST["input_admin_password"]) == true) { $input_admin_password = htmlEncode2(validateGenericInput($_POST["input_admin_password"])); }
	else                                               { $input_admin_password = ""; }
	if (isset($_POST["dbusername2"]) == true) { $dbusername2 = validateUsername($_POST["dbusername2"]); }
	else                                      { $dbusername2 = ""; }
	if (isset($_POST["dbpassword2"]) == true) { $dbpassword2 = validatePassword($_POST["dbpassword2"]); }
	else                                      { $dbpassword2 = ""; }
	if (isset($_POST["dbname2"]) == true)     { $dbname2     = validateGenericInput($_POST["dbname2"]); }
	else                                      { $dbname2     = ""; }
	if (isset($_POST["dbserver2"]) == true)   { $dbserver2   = validateGenericInput($_POST["dbserver2"]); }
	else                                      { $dbserver2   = ""; }
	$dbusername2_html = htmlEncode2($dbusername2); 
	$dbpassword2_html = htmlEncode2($dbpassword2); 
	$dbname2_html     = htmlEncode2($dbname2); 
	$dbserver2_html   = htmlEncode2($dbserver2); 

	if ($dbserver2 == "") { $dbserver2 = "localhost"; }

// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Output variable
	$net2ftp_output["admin_createtables"][] = "";

// Title
	$title = __("Admin functions");

// Form name
	$formname = "AdminForm";

// Read the SQL file
	$filename = glueDirectories($net2ftp_globals["application_rootdir"], "create_tables.sql");
	$handle = fopen($filename, "rb"); // Open the file for reading only
	if ($handle == false) { $net2ftp_output["admin_createtables"][] = __("The handle of file %1\$s could not be opened.", $filename); }

	clearstatcache(); // for filesize

	$sqlquerystring = fread($handle, filesize($filename));
	if ($sqlquerystring == false) { $net2ftp_output["admin_createtables"][] = __("The file %1\$s could not be opened.", $filename); }

	$result1 = fclose($handle);
	if ($result1 == false) { $net2ftp_output["admin_createtables"][] = __("The handle of file %1\$s could not be closed.", $filename); }

// Split the SQL file in individual queries
	$sqlquerypieces = explode("\n", $sqlquerystring);

// -------------------------------------------------------------------------
// Variables for screen 1
// -------------------------------------------------------------------------
	if ($net2ftp_globals["screen"] == 1) {

// Next screen
		$nextscreen = 2;

// Back and forward buttons
		$back_onclick = "document.forms['" . $formname . "'].state.value='admin';document.forms['" . $formname . "'].screen.value='1';document.forms['" . $formname . "'].submit();";
		$forward_onclick = "document.forms['" . $formname . "'].submit();";

	} // end if

// -------------------------------------------------------------------------
// Variables for screen 2
// -------------------------------------------------------------------------
	elseif ($net2ftp_globals["screen"] == 2) {

// Next screen
		$nextscreen = 1;

// Back and forward buttons
		$back_onclick = "document.forms['" . $formname . "'].state.value='admin';document.forms['" . $formname . "'].screen.value='1';document.forms['" . $formname . "'].submit();";

		$dbpassword2_length = strlen($dbpassword2);

// ------------------------------------
// Connect
// ------------------------------------
		$mydb = mysql_connect($dbserver2, $dbusername2, $dbpassword2);
		if ($mydb == false) { $net2ftp_output["admin_createtables"][] = __("The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered.", $dbserver2_html) . "\n"; }

// ------------------------------------
// Select
// ------------------------------------
		if ($mydb != false) {
			$mysql_select_db_result = mysql_select_db($dbname2);
			if ($mysql_select_db_result == false) { $net2ftp_output["admin_createtables"][] = __("Unable to select the database <b>%1\$s</b>.", $dbserver2_html) . "\n"; }
		}

// ------------------------------------
// Query
// ------------------------------------
		if ($mydb != false && $mysql_select_db_result != false) {
			for ($i=0; $i<sizeof($sqlquerypieces); $i++) {
				$mysql_query_results[$i] = mysql_query($sqlquerypieces[$i]);
				if ($mysql_query_results[$i] == false) { $net2ftp_output["admin_createtables"][] = __("The SQL query nr <b>%1\$s</b> could not be executed.", $i+1) . "\n"; }
				else                                   { $net2ftp_output["admin_createtables"][] = __("The SQL query nr <b>%1\$s</b> was executed successfully.", $i+1) . "\n"; }
			}
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