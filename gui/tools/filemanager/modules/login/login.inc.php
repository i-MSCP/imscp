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

function net2ftp_module_sendHttpHeaders() {

// --------------
// This function sends HTTP headers
// --------------

//	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;

// -------------------------------------------------------------------------
// Send XHTML header
// -------------------------------------------------------------------------
//	header("Content-type: application/xhtml+xml; charset=" . __("iso-8859-1"));
	
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

	global $net2ftp_settings, $net2ftp_globals;

// Check if the user did enter an FTP server and username
	echo "<script type=\"text/javascript\"><!--\n";	

	echo "function CheckInput(form) {\n";
	echo "	var u,p1,p2,e;\n";
	echo "	s=form.ftpserver.value;\n";
	echo "	u=form.username.value;\n";
	echo "	p=form.password.value;\n";

	echo "	if (s.length==0) {\n";
	echo "		form.ftpserver.focus();\n";
	echo "		alert(\"" . __("Please enter an FTP server.") . "\");\n";
	echo "		return false;\n";
	echo "	}\n";

	echo "	if (u.length==0) {\n";
	echo "		form.username.focus();\n";
	echo "		alert(\"" . __("Please enter a username.") . "\");\n";
	echo "		return false;\n";
	echo "	}\n";

//	echo "	if (p.length==0) {\n";
//	echo "		form.password.focus();\n";
//	echo "		alert(\"" . __("Please enter a password.") . "\");\n";
//	echo "		return false;\n";
//	echo "	}\n";

	echo "	return true;\n";
	echo "}\n";

// Anonymous login
	echo "function do_anonymous(form) {\n";
	echo "	var checked = form.anonymous.checked;\n";
	echo "	if (checked == true) {\n";
	echo "		vars_defined = 'true';\n";
	echo "		last_username = form.username.value;\n";
	echo "		last_password = form.password.value;\n";
	echo "		form.username.value = \"anonymous\";\n";
	echo "		form.password.value = \"user@net2ftp.com\";\n";
	echo "	} else {\n";
	echo "		form.username.value = last_username;\n";
	echo "		form.password.value = last_password;\n";
	echo "	}\n";
	echo "	return true;\n";
	echo "}\n";
	
// Clear Cookies
	echo "function ClearCookies() {\n";
	echo "	document.forms['LoginForm'].state.value='clearcookies';\n";
	echo "	document.forms['LoginForm'].state2.value='';\n";
	echo "	document.forms['LoginForm'].submit();\n";
	echo "}\n";
	echo "//--></script>\n";

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

// -------------------------------------------------------------------------
// Variables
// -------------------------------------------------------------------------

// ------------------------------------
// Title
// ------------------------------------
	$login_title = __("Login!");

// ------------------------------------
// FTP server
//	    $ftpserver["inputType"] can be "text", "select" or "hidden"
//	    $ftpserver"][$i]["text"] is "ftp.server.com"
//	    $ftpserver"][$i]["selected"] is "selected" or ""
// ------------------------------------

// All FTP servers are allowed
// Prefill the textbox with the value that was filled in (when changing the language the page refreshes)
// or else with the value from the cookie
    	if ($net2ftp_settings["allowed_ftpservers"][1] == "ALL") {
		// Input type is textbox
    		$ftpserver["inputType"] = "text";

		// Prefill with the previous input value
		if ($net2ftp_globals["ftpserver"] != "") { $ftpserver["list"][1] = htmlEncode2($net2ftp_globals["ftpserver"]); }

		// Prefill with the cookie value
    		else { $ftpserver["list"][1] = htmlEncode2($net2ftp_globals["cookie_ftpserver"]); }
    	}
	
// Only a list of FTP servers are allowed
// Preselect the drop-down box with the value that was filled in (when changing the language the page refreshes)
// or else with the value from the cookie
	elseif (sizeof($net2ftp_settings["allowed_ftpservers"]) > 1) {
		// Input type is drop-down box
		$ftpserver["inputType"] = "select";

		// List of allowed FTP servers
		$ftpserver["list"] = $net2ftp_settings["allowed_ftpservers"];

		// Preselect the right FTP server
		// ... using the previous input value
		$array_search_result1 = array_search($net2ftp_globals["ftpserver"], $ftpserver);
		if (is_numeric($array_search_result1) == true) { $ftpserver["list"][$array_search_result1]["selected"] = "selected=\"selected\""; }

		// ... using the cookie value
		else {
			$array_search_result2 = array_search($net2ftp_globals["cookie_ftpserver"], $ftpserver);
			if (is_numeric($array_search_result2) == true) { $ftpserver["list"][$array_search_result2]["selected"] = "selected=\"selected\""; }
		}
	}

// Only 1 FTP server is allowed
	elseif (sizeof($net2ftp_settings["allowed_ftpservers"]) == 1) {
		$ftpserver["inputType"] = "hidden";
		$ftpserver["list"][1] = $net2ftp_settings["allowed_ftpservers"][1];
	}
	
// Else, there is an error!
	else {
		$errormessage = "There is an error in the net2ftp configuration file <b>settings_authorizations.inc.php</b>: variable <b>\$net2ftp_allowed_ftpservers</b> does not follow the expected format.";
		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
		return false;
	}


// ------------------------------------
// FTP server port
//	    $ftpserverport["inputType"] can be "text" or "hidden"
//	    $ftpserverport is "21"
// ------------------------------------
		
	if ($net2ftp_settings["allowed_ftpserverport"] == "ALL") {
		// Input type is textbox
		$ftpserverport["inputType"] = "text";

		// Prefill with the previous input value
		if ($net2ftp_globals["ftpserverport"] != "") { $ftpserverport["value"] = htmlEncode2($net2ftp_globals["ftpserverport"]); }

		// Prefill with the cookie value - only if it is different from empty
		elseif ($net2ftp_globals["cookie_ftpserverport"] != "") { 
			$ftpserverport["value"] = htmlEncode2($net2ftp_globals["cookie_ftpserverport"]); 
		}

		// Else, fill in 21, which is the default FTP port
		else { 
			$ftpserverport["value"] = 21; 
		}
	}
	
	else {
		$ftpserverport["inputType"] = "hidden";
		$ftpserverport["value"] = $net2ftp_settings["allowed_ftpserverport"];
	}
	

// ------------------------------------
// Username
// ------------------------------------

	// Prefill with the previous input value
	if ($net2ftp_globals["username"] != "") { $username = htmlEncode2($net2ftp_globals["username"]); }

	// Prefill with the cookie value
    	else { $username = htmlEncode2($net2ftp_globals["cookie_username"]); }


// ------------------------------------
// Password
// ------------------------------------

	// Do not prefill this field
	$password = "";

// ------------------------------------
// Passive mode
// ------------------------------------
	if     ($net2ftp_globals["passivemode"] == "yes")        { $passivemode["checked"] = "checked=\"checked\""; }
	elseif ($net2ftp_globals["cookie_passivemode"] == "yes") { $passivemode["checked"] = "checked=\"checked\""; }
	else                                                     { $passivemode["checked"] = ""; }


// ------------------------------------
// Initial directory
// ------------------------------------
	if     (strlen($net2ftp_globals["directory"]) > 1)        { $directory = $net2ftp_globals["directory_html"]; }
	elseif (strlen($net2ftp_globals["cookie_directory"]) > 1) { $directory = htmlEncode2($net2ftp_globals["cookie_directory"]); }
	else                                                      { $directory = ""; }

// ------------------------------------
// SSL connect
// ------------------------------------
	if (function_exists("ftp_ssl_connect") == false) { $sslconnect["inputType"] = "suppressed"; }
	else {
		$sslconnect["inputType"] = "checkbox";
		if     ($net2ftp_globals["sslconnect"] == "yes")        { $sslconnect["checked"] = "checked=\"checked\""; }
		elseif ($net2ftp_globals["cookie_sslconnect"] == "yes") { $sslconnect["checked"] = "checked=\"checked\""; }
		else                                                    { $sslconnect["checked"] = ""; }
	}


// ------------------------------------
// Language
// ------------------------------------	
	$language_onchange = "document.forms['LoginForm'].state.value='login'; document.forms['LoginForm'].submit();";


// ------------------------------------
// Skin
// ------------------------------------	
	$skin_onchange = "";

	
// ------------------------------------
// FTP mode
// ------------------------------------

// Determine the FTP mode
	if     ($net2ftp_globals["ftpmode"] != "")        { $ftpmode["type"] = htmlEncode2($net2ftp_globals["ftpmode"]); }
	elseif ($net2ftp_globals["cookie_ftpmode"] != "") { $ftpmode["type"] = htmlEncode2($net2ftp_globals["cookie_ftpmode"]); }
	else { 
		// Before PHP version 4.3.11, bug 27633 can cause problems in ASCII mode ==> use BINARY mode
		if (version_compare(phpversion(), "4.3.11", "<")) { $ftpmode["type"] = "binary"; }
		// As from PHP version 4.3.11, bug 27633 is fixed ==> use Automatic mode
		else                                              { $ftpmode["type"] = "automatic"; }
	}

// Fill the values that will be used in the template
	if ($ftpmode["type"] == "automatic") { 
		$ftpmode["automatic"] = "checked=\"checked\"";
		$ftpmode["binary"] = ""; 
	}
	elseif ($ftpmode["type"] == "binary") { 
		$ftpmode["automatic"] = "";
		$ftpmode["binary"] = "checked=\"checked\"";
	}

// ------------------------------------
// FTP mode
// ------------------------------------

	$admin_url = $net2ftp_globals["action_url"] . "?state=login_small&amp;state2=admin&amp;go_to_state=admin";

// ------------------------------------
// Focus
// ------------------------------------
	if   ($net2ftp_settings["allowed_ftpservers"][1] == "ALL") { $focus = "ftpserver"; }
	else                                                       { $focus = "username"; }


// -------------------------------------------------------------------------
// Print the output
// -------------------------------------------------------------------------
	require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/login.template.php");

} // End net2ftp_printBody

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>