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




/*

	The consumption of server and network resources is logged per client IP 
	address, and per target FTP server. The 2 database tables which contain 
	the logs are:
		net2ftp_log_consumption_ipaddress: date, ipaddress, datatransfer, executiontime
		net2ftp_log_consumption_ftpserver: date, ftpserver, datatransfer, executiontime
	
	The database is read at the beginning of the script, and updated at the end 
	of the script. There are 6 global variables:
	
	These variables
		$consumption_ipaddress_datatransfer, 
		$consumption_ipaddress_executiontime, 
		$consumption_ftpserver_datatransfer, 
		$consumption_ftpserver_executiontime; 
	contain the data transfer volume, script execution time and number of FTP 
	servers that were accessed. They are inititialized at the beginning of the 
	script and updated each time data is read/written from/to the FTP server. 
	
	The variable 
		$consumption_datatransfer_changeflag;
	is initialized as 0 and changed to 1 if data was transferred. 
	This is to avoid having to run an SQL query if no data was transferred, e.g. when
	logging in, browsing the FTP server, when confirming an action or even for some 
	actions like delete or chmod.
	
	The variable 
		$consumption_database_updated;
	is initialized as 0 and changed to 1 when the database is updated with the consumption
	in putConsumption(). This is to avoid updating the database twice. The putConsumption()
	function is called from index.php and from shutdown() in filesystem.inc.php. On Windows
	the shutdown() function is called after *every* script execution.

*/


// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function getConsumption() {

// --------------
// This function reads the consumption from the database.
// It is run at the beginning of the script.
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result;


// -------------------------------------------------------------------------
// Initial checks
// -------------------------------------------------------------------------

// Verify if a database is used, and if consumption checking is turned on. If not: don't continue.
	if ($net2ftp_settings["use_database"] != "yes" || $net2ftp_settings["check_consumption"] != "yes") { return true; }

// When user is not logged in, the FTP server is not set
	if ($net2ftp_globals["ftpserver"] == "") { return true; }

// If the REMOTE_ADDR is not filled in, then there is a problem (IP spoofing), so return an error
	if ($net2ftp_globals["REMOTE_ADDR"] == "") { 
		setErrorVars(false, __("Unable to determine your IP address."), debug_backtrace(), __FILE__, __LINE__);
		return false; 
	}

// Add slashes to variables which are used in a SQL query, and which are
// potentially unsafe (supplied by the user).
	// $date is calculated in this function
	// $time is calculated in this function
	$REMOTE_ADDR_safe       = addslashes($net2ftp_globals["REMOTE_ADDR"]);
	$net2ftp_ftpserver_safe = addslashes($net2ftp_globals["ftpserver"]);
	
// -------------------------------------------------------------------------
// Set the change flags to the initial value
// -------------------------------------------------------------------------
	$net2ftp_globals["consumption_datatransfer_changeflag"] = 0;
	$net2ftp_globals["consumption_database_updated"] = 0;

// -------------------------------------------------------------------------
// Get date
// -------------------------------------------------------------------------
	$date = date("Y-m-d");

// -------------------------------------------------------------------------
// Connect
// -------------------------------------------------------------------------
	$mydb = connect2db();
	if ($net2ftp_result["success"] == false) { return false; }

// -------------------------------------------------------------------------
// Get consumed data volume and execution time by the current IP address
// -------------------------------------------------------------------------
	$sqlquery1 = "SELECT datatransfer, executiontime FROM net2ftp_log_consumption_ipaddress WHERE date = '$date' AND ipaddress = '$REMOTE_ADDR_safe';";
	$result1   = mysql_query("$sqlquery1") or die("Unable to execute SQL SELECT query (getConsumption > sqlquery1) <br /> $sqlquery1");
	$nrofrows1 = mysql_num_rows($result1);

	if     ($nrofrows1 == 0) { 
		$net2ftp_globals["consumption_ipaddress_datatransfer"] = 0;
		$net2ftp_globals["consumption_ipaddress_executiontime"] = 0; 
	}
	elseif ($nrofrows1 == 1) { 
		$resultRow1 = mysql_fetch_row($result1); 
		$net2ftp_globals["consumption_ipaddress_datatransfer"] = $resultRow1[0];
		$net2ftp_globals["consumption_ipaddress_executiontime"] = $resultRow1[1]; 
	}
	else { 
		setErrorVars(false, __("Table net2ftp_log_consumption_ipaddress contains duplicate rows."), debug_backtrace(), __FILE__, __LINE__);
		return false; 
	}

// -------------------------------------------------------------------------
// Get consumed data volume and execution time to the current FTP server
// -------------------------------------------------------------------------
	$sqlquery2 = "SELECT datatransfer, executiontime FROM net2ftp_log_consumption_ftpserver WHERE date = '$date' AND ftpserver = '$net2ftp_ftpserver_safe';";
	$result2   = mysql_query("$sqlquery2") or die("Unable to execute SQL SELECT query (getConsumption > sqlquery2) <br /> $sqlquery2");
	$nrofrows2 = mysql_num_rows($result2);

	if     ($nrofrows2 == 0) { 
		$net2ftp_globals["consumption_ftpserver_datatransfer"] = 0;
		$net2ftp_globals["consumption_ftpserver_executiontime"] = 0; 
	}
	elseif ($nrofrows2 == 1) { 
		$resultRow2 = mysql_fetch_row($result2); 
		$net2ftp_globals["consumption_ftpserver_datatransfer"] = $resultRow2[0];
		$net2ftp_globals["consumption_ftpserver_executiontime"] = $resultRow2[1]; 
	}
	else { 
		setErrorVars(false, __("Table net2ftp_log_consumption_ftpserver contains duplicate rows."), debug_backtrace(), __FILE__, __LINE__);
		return false; 
	}


// Return true
	return true;

} // End getConsumption

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function putConsumption() {

// --------------
// This function writes the consumption to the database.
// It is run at the end of the script.
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result;

// -------------------------------------------------------------------------
// Initial checks
// -------------------------------------------------------------------------

// Verify if a database is used, and if consumption checking is turned on. If not: don't continue.
	if ($net2ftp_settings["use_database"] != "yes" || $net2ftp_settings["check_consumption"] != "yes") { return true; }

// When user is not logged in, the FTP server is not set
	if ($net2ftp_globals["ftpserver"] == "") { return true; }

// If the REMOTE_ADDR is not filled in, then there is a problem (IP spoofing), so return an error
	if ($net2ftp_globals["REMOTE_ADDR"] == "") { 
		setErrorVars(false, __("Unable to determine your IP address."), debug_backtrace(), __FILE__, __LINE__);
		return false; 
	}

// If the database has already been updated, don't do it a second time.
// This is to avoid updating the database twice. The putConsumption() function
// is called from index.php and from shutdown() in filesystem.inc.php. On Windows
// the shutdown() function is called after *every* script execution.
	if ($net2ftp_globals["consumption_database_updated"] == 1) { return true; }

// Add slashes to variables which are used in a SQL query, and which are
// potentially unsafe (supplied by the user).
	// $date is calculated in this function
	// $time is calculated in this function
	$REMOTE_ADDR_safe       = addslashes($net2ftp_globals["REMOTE_ADDR"]);
	$net2ftp_ftpserver_safe = addslashes($net2ftp_globals["ftpserver"]);
	
// -------------------------------------------------------------------------
// Check the input
// -------------------------------------------------------------------------

//	if (preg_match("/^[0-9]+$/", $net2ftp_globals["consumption_ipaddress_datatransfer) == FALSE) { 
//			setErrorVars(false, __("The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."), debug_backtrace(), __FILE__, __LINE__);
//			return false;
//	}

// -------------------------------------------------------------------------
// Connect
// -------------------------------------------------------------------------
	$mydb = connect2db();
	if ($net2ftp_result["success"] == false) { return false; }

// -------------------------------------------------------------------------
// Get date
// -------------------------------------------------------------------------
	$date = date("Y-m-d");


// -------------------------------------------------------------------------
// Put consumed data volume and execution time by the current IP address
// -------------------------------------------------------------------------
	$sqlquery1 = "SELECT * FROM net2ftp_log_consumption_ipaddress WHERE date = '$date' AND ipaddress = '$REMOTE_ADDR_safe';";
	$result1   = mysql_query("$sqlquery1");
	$nrofrows1 = mysql_num_rows($result1);

	if ($nrofrows1 == 1) { 
		$sqlquery2 = "UPDATE net2ftp_log_consumption_ipaddress SET datatransfer = '" . $net2ftp_globals["consumption_ipaddress_datatransfer"] . "', executiontime = '" . round($net2ftp_globals["consumption_ipaddress_executiontime"]) . "' WHERE date = '$date' AND ipaddress = '$REMOTE_ADDR_safe';";
		$result2   = mysql_query("$sqlquery2");
		$nrofrows2 = mysql_affected_rows($mydb);
// Don't check on the UPDATE nr of rows, because when the values in the variables and in the table are the same,
// the $nrofrows2 is set to 0. (This happens on the Browse screen, when the loading is fast: the datatransfer is 0
// and the executiontime is the same as in the table.)
//		if ($nrofrows2 != 1) { 
//			setErrorVars(false, __("Table net2ftp_log_consumption_ipaddress could not be updated."), debug_backtrace(), __FILE__, __LINE__);
//			return false; 
//		}
	}
	elseif ($nrofrows1 == 0) { 
		$sqlquery3 = "INSERT INTO net2ftp_log_consumption_ipaddress VALUES('$date', '$REMOTE_ADDR_safe', '" . $net2ftp_globals["consumption_ipaddress_datatransfer"] . "', '" . round($net2ftp_globals["consumption_ipaddress_executiontime"]) . "');";
		$result3   = mysql_query("$sqlquery3");
		$nrofrows3 = mysql_affected_rows($mydb);
		if ($nrofrows3 != 1) { 
			setErrorVars(false, __("Table net2ftp_log_consumption_ipaddress could not be updated."), debug_backtrace(), __FILE__, __LINE__);
			return false; 
		}
	}
	else {
		setErrorVars(false, __("Table net2ftp_log_consumption_ipaddress contains duplicate entries."), debug_backtrace(), __FILE__, __LINE__);
		return false; 
	}

// MySQL > 4.1.0
//	$sqlquery1 = "INSERT INTO net2ftp_log_consumption_ipaddress VALUES('$date', '$REMOTE_ADDR_safe', '" . $net2ftp_globals["consumption_ipaddress_datatransfer"] . "', '" . round($net2ftp_globals["consumption_ipaddress_executiontime"])  . "') ON DUPLICATE KEY UPDATE datatransfer = '" . $net2ftp_globals["consumption_ipaddress_datatransfer"] . "', executiontime = '" . round($net2ftp_globals["consumption_ipaddress_executiontime"]) . "';";

	
// -------------------------------------------------------------------------
// Put consumed data volume and execution time to the current FTP server
// -------------------------------------------------------------------------
	$sqlquery4 = "SELECT * FROM net2ftp_log_consumption_ftpserver WHERE date = '$date' AND ftpserver = '$net2ftp_ftpserver_safe';";
	$result4   = mysql_query("$sqlquery4");
	$nrofrows4 = mysql_num_rows($result4);

	if ($nrofrows4 == 1) { 
		$sqlquery5 = "UPDATE net2ftp_log_consumption_ftpserver SET datatransfer = '" . $net2ftp_globals["consumption_ftpserver_datatransfer"] . "', executiontime = '" . round($net2ftp_globals["consumption_ftpserver_executiontime"]) . "' WHERE date = '$date' AND ftpserver = '$net2ftp_ftpserver_safe';";
		$result5   = mysql_query("$sqlquery5");
		$nrofrows5 = mysql_affected_rows($mydb);
// Don't check on the UPDATE nr of rows, because when the values in the variables and in the table are the same,
// the $nrofrows2 is set to 0. (This happens on the Browse screen, when the loading is fast: the datatransfer is 0
// and the executiontime is the same as in the table.)
//		if ($nrofrows5 != 1) { 
//			setErrorVars(false, __("Table net2ftp_log_consumption_ftpserver could not be updated."), debug_backtrace(), __FILE__, __LINE__);
//			return false; 
//		}
	}
	elseif ($nrofrows4 == 0) { 
		$sqlquery6 = "INSERT INTO net2ftp_log_consumption_ftpserver VALUES('$date', '$net2ftp_ftpserver_safe', '" . $net2ftp_globals["consumption_ftpserver_datatransfer"] . "', '" . round($net2ftp_globals["consumption_ftpserver_executiontime"]) . "');";
		$result6   = mysql_query("$sqlquery6");
		$nrofrows6 = mysql_affected_rows($mydb);
		if ($nrofrows6 != 1) { 
			setErrorVars(false, __("Table net2ftp_log_consumption_ftpserver could not be updated."), debug_backtrace(), __FILE__, __LINE__);
			return false; 
		}
	}
	else {
		setErrorVars(false, __("Table net2ftp_log_consumption_ftpserver contains duplicate entries."), debug_backtrace(), __FILE__, __LINE__);
		return false; 
	}

// -------------------------------------------------------------------------
// Update the net2ftp_log_access record with the consumed data volume and execution time
// -------------------------------------------------------------------------
	$sqlquery7 = "SELECT * FROM net2ftp_log_access WHERE id = '" . $net2ftp_globals["log_access_id"] . "';";
	$result7   = mysql_query("$sqlquery7");
	$nrofrows7 = mysql_num_rows($result7);

	if ($nrofrows7 == 1) { 
		$sqlquery8 = "UPDATE net2ftp_log_access SET datatransfer = '" . $net2ftp_globals["consumption_datatransfer"] . "', executiontime = '" . round($net2ftp_globals["consumption_executiontime"]) . "' WHERE id = '" . $net2ftp_globals["log_access_id"] . "'";
		$result8   = mysql_query("$sqlquery8");
		$nrofrows8 = mysql_affected_rows($mydb);
// Don't check on the UPDATE nr of rows, because when the values in the variables and in the table are the same,
// the $nrofrows2 is set to 0. (This happens on the Browse screen, when the loading is fast: the datatransfer is 0
// and the executiontime is the same as in the table.)
//		if ($nrofrows8 != 1) { 
//			setErrorVars(false, __("Table net2ftp_log_access could not be updated."), debug_backtrace(), __FILE__, __LINE__);
//			return false; 
//		}
	}
	elseif ($nrofrows7 == 0) { 
		$sqlquery9 = "INSERT INTO net2ftp_log_access VALUES('$date', '$REMOTE_ADDR_safe', '" . $net2ftp_globals["consumption_ipaddress_datatransfer"] . "', '" . round($net2ftp_globals["consumption_ipaddress_executiontime"]) . "');";
		$result9   = mysql_query("$sqlquery9");
		$nrofrows9 = mysql_affected_rows($mydb);
		if ($nrofrows9 != 1) { 
			setErrorVars(false, __("Table net2ftp_log_access could not be updated."), debug_backtrace(), __FILE__, __LINE__);
			return false; 
		}
	}
	else {
		setErrorVars(false, __("Table net2ftp_log_access contains duplicate entries."), debug_backtrace(), __FILE__, __LINE__);
		return false; 
	}

// -------------------------------------------------------------------------
// If all 3 tables have been updated, set the flag to 1
// -------------------------------------------------------------------------
	$net2ftp_globals["consumption_database_updated"] = 1;

// Return true
	return true;

} // End putConsumption

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function addConsumption($data, $time) {

// --------------
// This function adds the $data and $time given in the argument of the function
// to the global variables
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result;


// -------------------------------------------------------------------------
// Initial checks
// -------------------------------------------------------------------------

// Verify if a database is used, and if consumption checking is turned on. If not: don't continue.
	if ($net2ftp_settings["use_database"] != "yes" || $net2ftp_settings["check_consumption"] != "yes") { return true; }

// Initialize variables if needed
	if (isset($net2ftp_globals["consumption_datatransfer"])  == false)  { $net2ftp_globals["consumption_datatransfer"] = 0; }
	if (isset($net2ftp_globals["consumption_executiontime"]) == false)  { $net2ftp_globals["consumption_executiontime"] = 0; }
	if (isset($net2ftp_globals["consumption_ipaddress_datatransfer"])     == false) { $net2ftp_globals["consumption_ipaddress_datatransfer"] = 0; }
	if (isset($net2ftp_globals["consumption_ipaddress_executiontime"])    == false) { $net2ftp_globals["consumption_ipaddress_executiontime"] = 0; }
	if (isset($net2ftp_globals["consumption_ftpserver_datatransfer"])     == false) { $net2ftp_globals["consumption_ftpserver_datatransfer"] = 0; }
	if (isset($net2ftp_globals["consumption_ftpserver_executiontime"])    == false) { $net2ftp_globals["consumption_ftpserver_executiontime"] = 0; }


// -------------------------------------------------------------------------
// Add the consumption to the global variables
// -------------------------------------------------------------------------
	if ($data != "" && $data > 0) {
		$net2ftp_globals["consumption_datatransfer_changeflag"] = 1;
		$net2ftp_globals["consumption_datatransfer"] = $net2ftp_globals["consumption_datatransfer"] + $data;
		$net2ftp_globals["consumption_ipaddress_datatransfer"] = $net2ftp_globals["consumption_ipaddress_datatransfer"] + $data;
		$net2ftp_globals["consumption_ftpserver_datatransfer"] = $net2ftp_globals["consumption_ftpserver_datatransfer"] + $data;
	}

	if ($time != "" && $time > 0) {
		$net2ftp_globals["consumption_executiontime"] = $net2ftp_globals["consumption_executiontime"] + $time;
		$net2ftp_globals["consumption_ipaddress_executiontime"] = $net2ftp_globals["consumption_ipaddress_executiontime"] + $time;
		$net2ftp_globals["consumption_ftpserver_executiontime"] = $net2ftp_globals["consumption_ftpserver_executiontime"] + $time;
	}

// Return true
	return true;

} // End addConsumption

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************

// **                                                                                  **
// **                                                                                  **

function printConsumption() {

// --------------
// This function prints the global consumption variables.
// It is only used for debugging.
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result;


// -------------------------------------------------------------------------
// Print the variables
// -------------------------------------------------------------------------
	echo "FTP server: "     . $net2ftp_globals["ftpserver"]   . "<br />\n";
	echo "Remote address: " . $net2ftp_globals["REMOTE_ADDR"] . "<br />\n";

	echo "consumption_datatransfer: "               . $net2ftp_globals["consumption_datatransfer"]  . "<br />\n";
	echo "consumption_executiontime: "              . $net2ftp_globals["consumption_executiontime"] . "<br />\n";

	echo "consumption_ipaddress_datatransfer: "     . $net2ftp_globals["consumption_ipaddress_datatransfer"]  . "<br />\n";
	echo "consumption_ipaddress_executiontime: "    . $net2ftp_globals["consumption_ipaddress_executiontime"] . "<br />\n";

	echo "consumption_ftpserver_datatransfer: "     . $net2ftp_globals["consumption_ftpserver_datatransfer"]  . "<br />\n";
	echo "consumption_ftpserver_executiontime: "    . $net2ftp_globals["consumption_ftpserver_executiontime"] . "<br />\n";

	echo "consumption_datatransfer_changeflag: "    . $net2ftp_globals["consumption_datatransfer_changeflag"] . "<br />\n";
	echo "consumption_ipaddress_executiontime: "    . $net2ftp_globals["consumption_ipaddress_executiontime"] . "<br />\n";


} // End printConsumption() 

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************




// **************************************************************************************
// **************************************************************************************

// **                                                                                  **
// **                                                                                  **

function checkConsumption() {

// --------------
// This function checks the consumption and returns an error message if
// the limit has been reached.
// It returns true if all is OK, false if the limit has been reached.
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings, $net2ftp_result;


// -------------------------------------------------------------------------
// Initial checks
// -------------------------------------------------------------------------

// Verify if a database is used, and if consumption checking is turned on. If not: don't continue.
	if ($net2ftp_settings["use_database"] != "yes" || $net2ftp_settings["check_consumption"] != "yes") { return true; }


// -------------------------------------------------------------------------
// Check if the limit has been reached
// -------------------------------------------------------------------------
	if ($net2ftp_globals["consumption_ipaddress_datatransfer"]     > $net2ftp_settings["max_consumption_ipaddress_datatransfer"])     { return false; }
	if ($net2ftp_globals["consumption_ipaddress_executiontime"]    > $net2ftp_settings["max_consumption_ipaddress_executiontime"])    { return false; }
	if ($net2ftp_globals["consumption_ftpserver_datatransfer"]     > $net2ftp_settings["max_consumption_ftpserver_datatransfer"])     { return false; }
	if ($net2ftp_globals["consumption_ftpserver_executiontime"]    > $net2ftp_settings["max_consumption_ftpserver_executiontime"])    { return false; }

	return true;

} // End checkConsumption() 

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>