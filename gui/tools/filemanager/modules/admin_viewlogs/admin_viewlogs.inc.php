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
	if (isset($_POST["datefrom"]) == true) { $datefrom = addslashes(validateGenericInput($_POST["datefrom"])); }
	else                                   { $datefrom = ""; }
	if (isset($_POST["dateto"]) == true)   { $dateto   = addslashes(validateGenericInput($_POST["dateto"])); }
	else                                   { $dateto   = ""; }

// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Output variable
	$net2ftp_output["admin_viewlogs"][] = "";

// Title
	$title = __("Admin functions");

// Form name, back and forward buttons
	$formname = "AdminForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='admin';document.forms['" . $formname . "'].submit();";
	$forward_onclick = "document.forms['" . $formname . "'].submit();";

// -------------------------------------------------------------------------
// Variables for screen 1
// -------------------------------------------------------------------------

// ------------------------------------
// Connect to the database
// ------------------------------------
	$mydb = connect2db();
	if ($net2ftp_result["success"] == false) { return false; }

// ------------------------------------
// Execute the SQL query and print the data
// ------------------------------------

// Query 1
	$sqlquery1 = "SELECT * FROM net2ftp_log_access WHERE date BETWEEN '$datefrom' AND '$dateto' ORDER BY date DESC, time DESC;";
	$table1 = printTable($sqlquery1);
	if ($net2ftp_result["success"] == false) { return false; }

// Query 2
	$sqlquery2 = "SELECT * FROM net2ftp_log_error WHERE date BETWEEN '$datefrom' AND '$dateto' ORDER BY date DESC, time DESC;";
	$table2 = printTable($sqlquery2);
	if ($net2ftp_result["success"] == false) { return false; }
	
// Query 3
	$sqlquery3 = "SELECT * FROM net2ftp_log_consumption_ftpserver WHERE date BETWEEN '$datefrom' AND '$dateto' ORDER BY datatransfer DESC, date DESC;";
	$table3 = printTable($sqlquery3);
	if ($net2ftp_result["success"] == false) { return false; }
	
// Query 4
	$sqlquery4 = "SELECT * FROM net2ftp_log_consumption_ipaddress WHERE date BETWEEN '$datefrom' AND '$dateto' ORDER BY datatransfer DESC, date DESC;";
	$table4 = printTable($sqlquery4);
	if ($net2ftp_result["success"] == false) { return false; }

// -------------------------------------------------------------------------
// Print the output
// -------------------------------------------------------------------------
	require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/manage.template.php");

} // End net2ftp_printBody

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// ************************************************************************************** 
// ************************************************************************************** 
// **                                                                                  ** 
// **                                                                                  ** 

function printTable($sqlquery) {

// --------------
// This function executes the SQL query and prints a nice HTML table with the results
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
//	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result, $net2ftp_output;

// -------------------------------------------------------------------------
// Execute the SQL query
// -------------------------------------------------------------------------
	$result = mysql_query("$sqlquery");
	if ($result == false) { 
		$errormessage = __("Unable to execute the SQL query <b>%1\$s</b>.", $sqlquery); 
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;	
	}
	$nrofrows = mysql_num_rows($result);
	$nrofcolumns_withindex = mysql_num_fields($result) + 1;
	 

// ------------------------------------------------------------------------- 
// Print the table
// ------------------------------------------------------------------------- 

// Table begin
	$output = "<table border=\"1\">\n";

// First row: SQL query
	$output .= "<tr><td colspan=\"$nrofcolumns_withindex\" class=\"tdheader1\" style=\"font-size: 120%;\">$sqlquery</td></tr>\n";


	if ($nrofrows != 0) {
// Second row: header
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$output .= "<tr>\n";
		$output .= "<td>Index</td>\n";
		while(list($fieldname, $fieldvalue) = each($row) ) { $output .= "<td>$fieldname</td>\n"; }
		$output .= "</tr>\n";
		mysql_data_seek($result, 0); // reset row pointer to the first row

// 3rd and next rows: data
		$rowcounter = 1;
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$output .= "<tr>\n";
			$output .= "<td>$rowcounter</td>\n";
			while(list($fieldname, $fieldvalue) = each($row) ) { $output .= "<td>" . htmlEncode2($fieldvalue) . "</td>\n"; }
			$output .= "</tr>\n";
			$rowcounter++;
		}
	}

// If there is no data
	else { 
		$output .= "<tr><td colspan=\"$nrofcolumns_withindex\">" . __("No data") . "</td></tr>"; 
	}

// Table end
	$output .= "</table>\n";

// ------------------------------------------------------------------------- 
// Free the $result
// ------------------------------------------------------------------------- 
	mysql_free_result($result);

	return $output;
	
} // End printTable

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>