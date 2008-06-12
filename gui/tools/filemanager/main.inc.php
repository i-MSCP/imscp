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
//  | This program is distributed in the hope that it will be useful,               |
//  | but WITHOUT ANY WARRANTY; without even the implied warranty of                |
//  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                 |
//  | GNU General Public License for more details.                                  |
//  |                                                                               |
//  | You should have received a copy of the GNU General Public License             |
//  | along with this program; if not, write to the Free Software                   |
//  | Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA     |
//  |                                                                               |
//   -------------------------------------------------------------------------------





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function net2ftp($action) {

// --------------
// This function is the main net2ftp function; it is the interface between 3rd party
// scripts (CMS, control panels, etc), and the internal net2ftp modules and plugins.
//
// This function is called 5 times per pageload: to send the HTTP headers, to print
// the javascript code, to print the CSS code, to print the body onload actions and
// finally to print the body content.
// --------------

// -------------------------------------------------------------------------
// Check that "sendHttpHeaders" action is only executed once
// Check that no other actions can be executed if "sendHttpHeaders" has not yet been executed
// -------------------------------------------------------------------------
	if ($action == "sendHttpHeaders") {
		if (defined("NET2FTP_SENDHTTPHEADERS") == true)  { echo "Error: please call the net2ftp(\$action) function only once with \$action = \"sendHttpHeaders\"!"; return false; }
		else                                             { define("NET2FTP_SENDHTTPHEADERS", 1); }
	}
	else {
		if (defined("NET2FTP_SENDHTTPHEADERS") == false) { echo "Error: please call the net2ftp(\$action) function first with \$action = \"sendHttpHeaders\"!"; return false; }
	}

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_result, $net2ftp_messages;

// Set the NET2FTP constant which is used to check if template files are called by net2ftp
	if (defined("NET2FTP") == false) { define("NET2FTP", 1); }

// Initialize the global variables
	if ($action == "sendHttpHeaders") {
		$net2ftp_globals = array();
		$net2ftp_messages = array();
		$net2ftp_output = array();
		$net2ftp_result["success"]         = true;
		$net2ftp_result["errormessage"]    = "";
		$net2ftp_result["debug_backtrace"] = "";
		$net2ftp_result["exit"]            = false;
		$net2ftp_settings = array();
	}

// -------------------------------------------------------------------------
// If an error occured during a previous execution of net2ftp(), return false
// and let index.php print the error message
// -------------------------------------------------------------------------
	if ($net2ftp_result["success"] == false) { return false; }

// -------------------------------------------------------------------------
// Input checks
// -------------------------------------------------------------------------
	if ($action != "sendHttpHeaders" && $action != "printJavascript" && $action != "printCss" && $action != "printBodyOnload" && $action != "printBody") {
		$net2ftp_result["success"]         = false;
		$net2ftp_result["errormessage"]    = "The \$action variable has an unknown value: $action.";
		$net2ftp_result["debug_backtrace"] = debug_backtrace();
		logError();
		return false;
	}


// -------------------------------------------------------------------------
// Read settings files
// -------------------------------------------------------------------------
	if ($action == "sendHttpHeaders") {
		require(NET2FTP_APPLICATION_ROOTDIR . "/settings.inc.php");
		require(NET2FTP_APPLICATION_ROOTDIR . "/settings_authorizations.inc.php");
		require(NET2FTP_APPLICATION_ROOTDIR . "/settings_screens.inc.php");
	}


// -------------------------------------------------------------------------
// Main directories
// -------------------------------------------------------------------------
	$net2ftp_globals["application_rootdir"]      = NET2FTP_APPLICATION_ROOTDIR;
	if (NET2FTP_APPLICATION_ROOTDIR_URL == "/") { $net2ftp_globals["application_rootdir_url"] = ""; }
	else                                        { $net2ftp_globals["application_rootdir_url"]  = NET2FTP_APPLICATION_ROOTDIR_URL; }

	$net2ftp_globals["application_includesdir"]  = $net2ftp_globals["application_rootdir"] . "/includes";
	$net2ftp_globals["application_languagesdir"] = $net2ftp_globals["application_rootdir"] . "/languages";
	$net2ftp_globals["application_modulesdir"]   = $net2ftp_globals["application_rootdir"] . "/modules";
	$net2ftp_globals["application_pluginsdir"]   = $net2ftp_globals["application_rootdir"] . "/plugins";
	$net2ftp_globals["application_skinsdir"]     = $net2ftp_globals["application_rootdir"] . "/skins";
	$net2ftp_globals["application_tempdir"]      = $net2ftp_globals["application_rootdir"] . "/temp";

// -------------------------------------------------------------------------
// Set basic settings
// -------------------------------------------------------------------------

	if ($action == "sendHttpHeaders") {

// Run the script to the end, even if the user hits the stop button
		ignore_user_abort();

// Execute function shutdown() if the script reaches the maximum execution time (usually 30 seconds)
// DON'T REGISTER IT HERE YET, as this causes errors on newer versions of PHP; first include the function libraries
//		register_shutdown_function("net2ftp_shutdown");

// Set the error reporting level
		if     ($net2ftp_settings["error_reporting"] == "ALL")  { error_reporting(E_ALL); }
		elseif ($net2ftp_settings["error_reporting"] == "NONE") { error_reporting(0); }
		else                                                    { error_reporting(E_ERROR | E_WARNING | E_PARSE); }

// Timer: start
		$net2ftp_globals["starttime"] = microtime();
		$net2ftp_globals["endtime"] = microtime();
	}

// Set the PHP temporary directory
// @change: activated by ispCP-Team
	putenv("TMPDIR=" . $net2ftp_globals["application_tempdir"]);

// -------------------------------------------------------------------------
// Function libraries:
// 1. Libraries which are always needed
// 2. Register global variables
// 3. Function libraries which are needed depending on certain variables
// // --> Do this only once, when $action == "sendHttpHeaders"
// -------------------------------------------------------------------------

	if ($action == "sendHttpHeaders") {

// 1. Libraries which are always needed
		require_once($net2ftp_globals["application_includesdir"]  . "/authorizations.inc.php");
		require_once($net2ftp_globals["application_includesdir"]  . "/consumption.inc.php");
		require_once($net2ftp_globals["application_includesdir"]  . "/database.inc.php");
		require_once($net2ftp_globals["application_includesdir"]  . "/errorhandling.inc.php");
		require_once($net2ftp_globals["application_includesdir"]  . "/filesystem.inc.php");
		require_once($net2ftp_globals["application_includesdir"]  . "/html.inc.php");
		require_once($net2ftp_globals["application_includesdir"]  . "/StonePhpSafeCrypt.php");
		require_once($net2ftp_globals["application_languagesdir"] . "/languages.inc.php");
		require_once($net2ftp_globals["application_skinsdir"]     . "/skins.inc.php");

// 1. Define functions which are used, but which did not exist before PHP version 4.3.0
		if (version_compare(phpversion(), "4.3.0", "<")) {
			require_once($net2ftp_globals["application_includesdir"] . "/before430.inc.php");
		}

// 2. Register global variables (POST, GET, GLOBAL, ...)
		require_once($net2ftp_globals["application_includesdir"] . "/registerglobals.inc.php");

// 3. Function libraries which are needed depending on certain variables
		if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "unzip") {
			require_once($net2ftp_globals["application_includesdir"] . "/pclerror.lib.php");
			require_once($net2ftp_globals["application_includesdir"] . "/pcltar.lib.php");
			require_once($net2ftp_globals["application_includesdir"] . "/pcltrace.lib.php");
			require_once($net2ftp_globals["application_includesdir"] . "/pclzip.lib.php");
		}
		if ($net2ftp_globals["state"] == "advanced_ftpserver"   || $net2ftp_globals["state"] == "advanced_parsing" ||
			$net2ftp_globals["state"] == "advanced_webserver" || $net2ftp_globals["state"] == "browse"           ||
			$net2ftp_globals["state"] == "copymovedelete"     || $net2ftp_globals["state"] == "chmod"            ||
			$net2ftp_globals["state"] == "calculatesize"      || $net2ftp_globals["state"] == "downloadzip"      ||
			$net2ftp_globals["state"] == "findstring"         || $net2ftp_globals["state"] == "followsymlink"    ||
			$net2ftp_globals["state"] == "install"            || $net2ftp_globals["state"] == "zip") {
			require_once($net2ftp_globals["application_includesdir"] . "/browse.inc.php");
		}
		if ($net2ftp_globals["state"] == "downloadzip"   || $net2ftp_globals["state"] == "zip") {
			require_once($net2ftp_globals["application_includesdir"] . "/zip.lib.php");
		}

// 4. Load the plugins
		require_once($net2ftp_globals["application_pluginsdir"] . "/plugins.inc.php");
		$net2ftp_globals["activePlugins"] = getActivePlugins();
		net2ftp_plugin_includePhpFiles();

// 5. Load the language file
		includeLanguageFile();

	}

// -------------------------------------------------------------------------
// Execute function shutdown() if the script reaches the maximum execution time (usually 30 seconds)
// -------------------------------------------------------------------------
	if ($action == "sendHttpHeaders") {
		register_shutdown_function("net2ftp_shutdown");
	}

// -------------------------------------------------------------------------
// Log access
// --> Do this only once, when $action == "sendHttpHeaders"
// -------------------------------------------------------------------------
	if ($action == "sendHttpHeaders") {
		logAccess();
		if ($net2ftp_result["success"] == false) {
			logError();
			return false;
		}
	}

// -------------------------------------------------------------------------
// Check authorizations
// --> Do this only once, when $action == "sendHttpHeaders"
// -------------------------------------------------------------------------
	if ($action == "sendHttpHeaders" && $net2ftp_settings["check_authorization"] == "yes" && $net2ftp_globals["ftpserver"] != "") {
		checkAuthorization($net2ftp_globals["ftpserver"], $net2ftp_globals["ftpserverport"], $net2ftp_globals["directory"], $net2ftp_globals["username"]);
		if ($net2ftp_result["success"] == false) {
			logError();
			return false;
		}
	}

// -------------------------------------------------------------------------
// Get the consumption counter values from the database
// This retrieves the consumption of network and server resources for the
// current IP address and FTP server from the database, and stores these
// values in global variables. See /includes/consumption.inc.php for the details.
// --> Do this only once, when $action == "sendHttpHeaders"
// -------------------------------------------------------------------------
	if ($action == "sendHttpHeaders") {
		getConsumption();
		if ($net2ftp_result["success"] == false) {
			logError();
			return false;
		}
	}

// -------------------------------------------------------------------------
// Execute the action!
// -------------------------------------------------------------------------

// ------------------------------------
// For most modules, everything must be done: send headers, print body, etc
// ------------------------------------
	if ($net2ftp_globals["state"] == "admin" ||
          $net2ftp_globals["state"] == "admin_createtables" ||
          $net2ftp_globals["state"] == "admin_emptylogs" ||
          $net2ftp_globals["state"] == "admin_viewlogs"  ||
          $net2ftp_globals["state"] == "advanced" ||
          $net2ftp_globals["state"] == "advanced_ftpserver" ||
          $net2ftp_globals["state"] == "advanced_parsing" ||
          $net2ftp_globals["state"] == "advanced_webserver" ||
          $net2ftp_globals["state"] == "bookmark" ||
          $net2ftp_globals["state"] == "browse" ||
          $net2ftp_globals["state"] == "calculatesize" ||
          $net2ftp_globals["state"] == "chmod" ||
          $net2ftp_globals["state"] == "copymovedelete" ||
          $net2ftp_globals["state"] == "edit" ||
          $net2ftp_globals["state"] == "findstring" ||
          $net2ftp_globals["state"] == "install" ||
          ($net2ftp_globals["state"] == "jupload" && $net2ftp_globals["screen"] == 1) ||
          $net2ftp_globals["state"] == "login" ||
          $net2ftp_globals["state"] == "login_small" ||
          $net2ftp_globals["state"] == "logout" ||
          $net2ftp_globals["state"] == "newdir" ||
          $net2ftp_globals["state"] == "raw" ||
          $net2ftp_globals["state"] == "rename" ||
          $net2ftp_globals["state"] == "unzip" ||
          $net2ftp_globals["state"] == "upload" ||
          ($net2ftp_globals["state"] == "view" && $net2ftp_globals["state2"] == "") ||
          $net2ftp_globals["state"] == "zip") {

		require_once($net2ftp_globals["application_modulesdir"] . "/" . $net2ftp_globals["state"] . "/" . $net2ftp_globals["state"] . ".inc.php");

		if     ($action == "sendHttpHeaders") {
			net2ftp_module_sendHttpHeaders();

			// If needed, exit to avoid sending non-header output (by net2ftp or other application)
			// Example: if a module sends a HTTP redirect header (See /includes/authorizations.inc.php function checkAdminUsernamePassword()!)
			if ($net2ftp_result["exit"] == true) { exit(); }

		}
		elseif ($action == "printJavascript") {
			net2ftp_module_printJavascript();
			net2ftp_plugin_printJavascript();
		}
		elseif ($action == "printCss")        {
			net2ftp_module_printCss();
			net2ftp_plugin_printCss();
		}
		elseif ($action == "printBodyOnload") {
			net2ftp_module_printBodyOnload();
			net2ftp_plugin_printBodyOnload();
		}
		elseif ($action == "printBody")       {

			// Print the status bar to be able to show the progress
			if (isStatusbarActive() == true) {
				require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/statusbar.template.php");
			}
			require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/status/status.inc.php");

			// Do the work and meanwhile update the progress bar
			net2ftp_module_printBody();

			// Update the consumption statistics
			$net2ftp_globals["endtime"] = microtime();
			$net2ftp_globals["time_taken"] = timer();
			addConsumption(0, $net2ftp_globals["time_taken"]);
			putConsumption();

			// Set the progress bar to "finished"
			if (isStatusbarActive() == true) {
				$statusmessage = __("Script finished in %1\$s seconds", $net2ftp_globals["time_taken"]);
				setStatus(1, 1, $statusmessage);
			}
		}
	}

// ------------------------------------
// For some modules, only headers must be sent
// ------------------------------------
	elseif ($net2ftp_globals["state"] == "clearcookies"  ||
              $net2ftp_globals["state"] == "downloadfile"  ||
              $net2ftp_globals["state"] == "downloadzip"   ||
              $net2ftp_globals["state"] == "followsymlink" ||
             ($net2ftp_globals["state"] == "jupload" && $net2ftp_globals["screen"] == 2) ||
             ($net2ftp_globals["state"] == "view" && $net2ftp_globals["state2"] != "")) {
		require_once($net2ftp_globals["application_modulesdir"] . "/" . $net2ftp_globals["state"] . "/" . $net2ftp_globals["state"] . ".inc.php");
		if     ($action == "sendHttpHeaders") {

			// Do the work - do not update the progress bar
			net2ftp_module_sendHttpHeaders();

			// Update the consumption statistics
			$net2ftp_globals["endtime"] = microtime();
			$net2ftp_globals["time_taken"] = timer();
			addConsumption(0, $net2ftp_globals["time_taken"]);
			putConsumption();

			// Exit to avoid sending non-header output (by net2ftp or other application)
			exit();

		}
		elseif ($action == "printJavascript") { }
		elseif ($action == "printCss")        { }
		elseif ($action == "printBodyOnload") { }
		elseif ($action == "printBody")       { }
	}
	elseif ($net2ftp_globals["state"] == "error") {
		logError();
		return false;
	}
	else {
		$errormessage = __("Unexpected state string: %1\$s. Exiting.", $net2ftp_globals["state"]);
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		logError();
		return false;
	}

} // end function net2ftp_main

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function isStatusbarActive() {

// --------------
// This function returns if the status bar should be shown or not, depending
// on the state and state2 variables
// --------------

	global $net2ftp_globals;

// If $net2ftp_globals["isStatusbarActive"] is not yet filled, calculate its value
// and fill it in
	if (isset($net2ftp_globals["isStatusbarActive"]) == false) {
		if ($net2ftp_globals["skin"] == "openlaszlo") { $net2ftp_globals["isStatusbarActive"] = false; }
		elseif (
		$net2ftp_globals["state"] == "admin" ||
		$net2ftp_globals["state"] == "admin_createtables" ||
		$net2ftp_globals["state"] == "admin_emptylogs"   ||
		$net2ftp_globals["state"] == "admin_viewlogs" ||
		$net2ftp_globals["state"] == "advanced" ||
		$net2ftp_globals["state"] == "advanced_ftpserver" ||
		$net2ftp_globals["state"] == "advanced_parsing"   ||
		$net2ftp_globals["state"] == "advanced_webserver" ||
		$net2ftp_globals["state"] == "bookmark" ||
		($net2ftp_globals["state"] == "browse" && $net2ftp_globals["state2"] == "main") ||
		$net2ftp_globals["state"] == "calculatesize" ||
		$net2ftp_globals["state"] == "chmod" ||
		$net2ftp_globals["state"] == "copymovedelete" ||
		$net2ftp_globals["state"] == "easywebsite" ||
		$net2ftp_globals["state"] == "findstring" ||
		$net2ftp_globals["state"] == "install" ||
		$net2ftp_globals["state"] == "jupload" ||
		$net2ftp_globals["state"] == "newdir" ||
		$net2ftp_globals["state"] == "newfile" ||
		$net2ftp_globals["state"] == "raw" ||
		$net2ftp_globals["state"] == "rename" ||
		$net2ftp_globals["state"] == "unzip" ||
		$net2ftp_globals["state"] == "updatefile" ||
		$net2ftp_globals["state"] == "upload" ||
		$net2ftp_globals["state"] == "view" ||
		$net2ftp_globals["state"] == "zip") {
			$net2ftp_globals["isStatusbarActive"] = true;
		}
		else {
			$net2ftp_globals["isStatusbarActive"] = false;
		}
	}

// Return the value of $net2ftp_globals["isStatusbarActive"]
	return $net2ftp_globals["isStatusbarActive"];

} // end function isStatusbarActive

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function stopwatch() {

// --------------
// This function prints the total time elapsed, and the time elapsed since the previous call
// --------------

	global $net2ftp_globals;

// Now
	list($now_usec, $now_sec) = explode(' ', microtime());
	$now = ((float)$now_usec + (float)$now_sec);

// Initialization
	if (isset($net2ftp_globals["stopwatch_starttime"]) == false) {
		$net2ftp_globals["stopwatch_starttime"] = $now;
	}
	if (isset($net2ftp_globals["stopwatch_endtime"]) == false) {
		$net2ftp_globals["stopwatch_endtime"] = $now;
	}

// Total time elapsed = now - starttime
	$total_elapsed = $now - $net2ftp_globals["stopwatch_starttime"];
	$total_elapsed = number_format($total_elapsed, 4);

// Time since previous stopwatch = now - previous endtime
	$delta_elapsed = $now - $net2ftp_globals["stopwatch_endtime"];
	$delta_elapsed = number_format($delta_elapsed, 4);

// Set the new value for endtime
	$net2ftp_globals["stopwatch_endtime"] = $now;

// Print $total_elapsed and $delta_elapsed
	echo $total_elapsed . " - " . $delta_elapsed . "<br />\n";

} // End function stopwatch()

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>