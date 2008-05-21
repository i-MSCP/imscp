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

	global $net2ftp_globals;

	$cookie_expire = time()+60*60*24*30; // 30 days

	setcookie("net2ftpcookie_ftpserver",     $net2ftp_globals["ftpserver"],     $cookie_expire);
	setcookie("net2ftpcookie_ftpserverport", $net2ftp_globals["ftpserverport"], $cookie_expire);
	setcookie("net2ftpcookie_username",      $net2ftp_globals["username"],      $cookie_expire);
	setcookie("net2ftpcookie_language",      $net2ftp_globals["language"],      $cookie_expire);
	setcookie("net2ftpcookie_skin",          $net2ftp_globals["skin"],          $cookie_expire);
	setcookie("net2ftpcookie_ftpmode",       $net2ftp_globals["ftpmode"],       $cookie_expire);
	setcookie("net2ftpcookie_passivemode",   $net2ftp_globals["passivemode"],   $cookie_expire);
	setcookie("net2ftpcookie_sslconnect",    $net2ftp_globals["sslconnect"],    $cookie_expire);
	setcookie("net2ftpcookie_viewmode",      $net2ftp_globals["viewmode"],      $cookie_expire);
	setcookie("net2ftpcookie_sort",          $net2ftp_globals["sort"],          $cookie_expire);
	setcookie("net2ftpcookie_sortorder",     $net2ftp_globals["sortorder"],     $cookie_expire);
	setcookie("net2ftpcookie_directory",     $net2ftp_globals["directory"],     $cookie_expire);
	
} // end function net2ftp_sendHttpHeaders

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

// -------------------------------------------------------------------------
// Do not print anything for Mobile skins
// -------------------------------------------------------------------------
	if ($net2ftp_globals["skin"] == "mobile") {
		echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/browse/browse_main_mobile.js\"></script>\n";
	}

// -------------------------------------------------------------------------
// For the other skins, do print more Javascript functions
// -------------------------------------------------------------------------
	else {
// ------------------------------------
// Code
// ------------------------------------
		echo "<script type=\"text/javascript\"><!--\n";	
		echo "function createDirectoryTreeWindow(directory, FormAndFieldName) {\n";
		echo "	directoryTreeWindow = window.open(\"\",\"directoryTreeWindow\",\"height=450,width=300,resizable=yes,scrollbars=yes\");\n";
		echo "	var d = directoryTreeWindow.document;\n";
		echo "	d.writeln('<html>');\n";
		echo "	d.writeln('<head>');\n";
		echo "	d.writeln('<title>" . __("Choose a directory") . "<\/title>');\n";
		echo "	d.writeln('<\/head>');\n";
		echo "	d.writeln('<bo' + 'dy on' + 'load=\"document.DirectoryTreeForm.submit();\">');\n";
//		echo "	d.writeln('<body>');\n";
		echo "	d.writeln('" . __("Please wait...") . "<br /><br />');\n";
		echo "	d.writeln('<form name=\"DirectoryTreeForm\" id=\"DirectoryTreeForm\" action=\"" . printPHP_SELF("createDirectoryTreeWindow") . "\" method=\"post\" />');\n";
		printLoginInfo_javascript();
		echo "	d.writeln('<input type=\"hidden\" name=\"state\" value=\"browse\" />');\n";
		echo "	d.writeln('<input type=\"hidden\" name=\"state2\" value=\"popup\" />');\n";
		echo "	d.writeln('<input type=\"hidden\" name=\"directory\" value=\"' + directory + '\"  />');\n";
		echo "	d.writeln('<input type=\"hidden\" name=\"FormAndFieldName\" value=\"' + FormAndFieldName + '\"  />');\n";
		echo "	d.writeln('<\/form>');\n";
		echo "	d.writeln('<\/div>');\n";
		echo "	d.writeln('<\/body>');\n";
		echo "	d.writeln('<\/html>');\n";
		echo "	d.close();\n";
		echo "} // end function createDirectoryTreeWindow\n";
		echo "//--></script>\n";
// ------------------------------------
// Include
// ------------------------------------
		if ($net2ftp_globals["state2"] == "main") {
			echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/browse/browse_main.js.php?skin=" . $net2ftp_globals["skin"] . "\"></script>\n";
		}

		if ($net2ftp_globals["state2"] == "popup") {
			echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/browse/browse_popup.js\"></script>\n";
		}

	}

} // end function net2ftp_printJavascript

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
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"". $net2ftp_globals["application_rootdir_url"] . "/plugins/swfupload/swfupload.css.php?ltr=" . __("ltr") . "&amp;image_url=" . urlEncode2($net2ftp_globals["application_rootdir_url"] . "/plugins/swfupload") . "\" />\n";

} // end function net2ftp_printCssInclude

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function net2ftp_module_printBodyonload() {

// --------------
// This function prints the <body onload="" actions
// --------------

//	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;
//	echo "";

} // end function net2ftp_printBodyonload

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
// This function prints the browse screen ($state2=="main") or the directory popup screen ($state2=="popup")
// For the browse screen ($state2=="main"), 2 template files are called
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;


// -------------------------------------------------------------------------
// Check if the directory name contains \' and if it does, print an error message
// Note: these directories cannot be browsed, but can be deleted
// -------------------------------------------------------------------------
//	if (strstr($directory, "\'") != false) {
//		$errormessage = __("Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory.");
//		setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
//		return false;
//	}


// -------------------------------------------------------------------------
// Print the statusbar
// -------------------------------------------------------------------------
	if ($net2ftp_globals["state2"] == "main") {
		require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/statusbar.template.php");
	}


// -------------------------------------------------------------------------
// Variables
// With status update if $state2=="main"
// -------------------------------------------------------------------------

// ------------------------------------
// Open connection
// ------------------------------------
	if ($net2ftp_globals["state2"] == "main") { setStatus(2, 10, __("Connecting to the FTP server")); }

	$conn_id = ftp_openconnection();
	if ($net2ftp_result["success"] == false) { return false; }

// ------------------------------------
// Get raw list of directories and files; parse the raw list and return a nice list
// This function may change the current $directory; a warning message is returned in that case
// ------------------------------------
	if ($net2ftp_globals["state2"] == "main") { setStatus(4, 10, __("Getting the list of directories and files")); }

	$list = ftp_getlist($conn_id, $net2ftp_globals["directory"]);
	if ($net2ftp_result["success"] == false) { return false; }

// ------------------------------------
// Close connection
// ------------------------------------
	ftp_closeconnection($conn_id);

// ------------------------------------
// Sort the list
// ------------------------------------
	$list_directories  = sort_list($list["directories"]);
	$list_files        = sort_list($list["files"]);
	$list_symlinks     = sort_list($list["symlinks"]);
	$list_unrecognized = sort_list($list["unrecognized"]);
	$warning_directory = $list["stats"]["warnings"];
	$directory         = $list["stats"]["newdirectory"];
	$directory_html    = htmlEncode2($directory);
	$directory_url     = urlEncode2($directory);
	$directory_js      = javascriptEncode2($directory);
	$updirectory       = upDir($directory);
	$updirectory_html  = htmlEncode2($updirectory);
	$updirectory_url   = urlEncode2($updirectory);
	$updirectory_js    = javascriptEncode2($updirectory);

// ------------------------------------
// Calculate the list of HTTP URLs 
// ------------------------------------
	if ($net2ftp_globals["state2"] == "main") { 
		$list_links_js  = ftp2http($net2ftp_globals["directory"], $list_files, "no");
		$list_links_url = ftp2http($net2ftp_globals["directory"], $list_files, "yes");
	}

// ------------------------------------
// Consumption message
// ------------------------------------
	$warning_consumption = "";
	if (checkConsumption() == false) { 
		$warning_consumption .= "<b>" . __("Daily limit reached: you will not be able to transfer data") . "</b><br /><br />\n";
		$warning_consumption .= __("In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it.") . "<br /><br />\n";
		$warning_consumption .= __("If you need unlimited usage, please install net2ftp on your own web server.") . "<br />\n";
	}

// ------------------------------------
// Browse message
// ------------------------------------
	if ($net2ftp_settings["message_browse"] != "" && $net2ftp_settings["message_browse"] != "Setting message_browse does not exist") { 
		$warning_message = $net2ftp_settings["message_browse"];
	}

// ------------------------------------
// Directory tree
// ------------------------------------
	$directory_exploded = explode("/", stripDirectory($directory));

	if ($directory != "/" && checkAuthorizedDirectory("/") == true) { $directory_tree = "<a href=\"javascript:submitBrowseForm('/','','browse','main');\">root</a> "; }
	else                                                            { $directory_tree = "root "; }

	$directory_goto = "";
	for ($i=0; $i<sizeof($directory_exploded)-1; $i++) {
		$directory_goto = glueDirectories($directory_goto, $directory_exploded[$i]);
		$directory_goto_url = urlEncode2($directory_goto);
		if (checkAuthorizedDirectory($directory_goto) == true) { $directory_tree .= "/<a href=\"javascript:submitBrowseForm('" . $directory_goto_url . "','','browse','main');\">" . htmlEncode2($directory_exploded[$i]) . "</a> "; }
		else                                                   { $directory_tree .= "/" . $directory_exploded[$i] . " "; }
	}

	$directory_tree .= "/" . $directory_exploded[sizeof($directory_exploded)-1];

// ------------------------------------
// Language
// ------------------------------------
	$language_onchange = "document.BrowseForm.language.value=document.forms['BrowseForm'].language2.options[document.forms['BrowseForm'].language2.selectedIndex].value; submitBrowseForm('$directory_js', '', 'browse', 'main');";

// ------------------------------------
// Skin
// ------------------------------------
	$skin_onchange = "document.BrowseForm.skin.value=document.forms['BrowseForm'].skin2.options[document.forms['BrowseForm'].skin2.selectedIndex].value; submitBrowseForm('$directory_js', '', 'browse', 'main');";

// ------------------------------------
// $rowcounter counts the total nr of rows
// ------------------------------------
	$rowcounter = 0;

// ------------------------------------
// Column spans
// ------------------------------------
	$action_colspan = 1; 
	if ($net2ftp_settings["functionuse_view"] == "yes")   { $action_colspan++; }
	if ($net2ftp_settings["functionuse_edit"] == "yes")   { $action_colspan++; }
	if ($net2ftp_settings["functionuse_update"] == "yes") { $action_colspan++; }

// Total nr of columns
	$total_colspan = $action_colspan + 9;


// ------------------------------------
// Name, Type, Size, ...
// Determine the sort criteria and direction (ascending/descending)
// ------------------------------------
	$sortArray["dirfilename"]["text"]  = __("Name");
	$sortArray["type"]["text"]         = __("Type");
	$sortArray["size"]["text"]         = __("Size");
	$sortArray["owner"]["text"]        = __("Owner");
	$sortArray["group"]["text"]        = __("Group");
	$sortArray["permissions"]["text"]  = __("Perms");
	$sortArray["mtime"]["text"]        = __("Mod Time");

	$icon_directory = $net2ftp_globals["application_rootdir_url"] . "/skins/" . $net2ftp_globals["skin"] . "/images/mime";

// Loop over all the sort possibilities
	while(list($key, $value) = each($sortArray)) {

// The list is sorted by the current $key
// Print the icon representing the current sortorder
// Print the link to sort using the other sortorder
		if ($net2ftp_globals["sort"] == $key) {
			// Ascending
			if ($net2ftp_globals["sortorder"] == "ascending") { 
				$sortArray[$key]["title"]   = __("Click to sort by %1\$s in descending order", $value["text"]); 
				$sortArray[$key]["onclick"] = "do_sort('" . $key . "','descending');";
				$icon = "ascend.png";
				$alt  = __("Ascending order"); 
			}
			// Descending
			else {
				$sortArray[$key]["title"]   = __("Click to sort by %1\$s in ascending order", $value["text"]); 
				$sortArray[$key]["onclick"] = "do_sort('" . $key . "','ascending');";
				$icon = "descend.png";
				$alt  = __("Descending order"); 
			}
		}
// The list is not sorted by the current $key
// Do not print an icon
// Print the link to sort ascending
		else {
			$sortArray[$key]["title"]   = __("Click to sort by %1\$s in ascending order", $value["text"]); 
			$sortArray[$key]["onclick"] = "do_sort('" . $key . "','ascending');";
			$icon = "";  
			$alt  = "";
		}

// The icon to be printed is determined above
// Now, print the full HTML depending on the browser agent, version and platform
		if ($icon != "") {
			if ($net2ftp_globals["browser_agent"] == "IE" && ($net2ftp_globals["browser_version"] == "5.5" || $net2ftp_globals["browser_version"] == "6") && $net2ftp_globals["browser_platform"] == "Win") {
				$sortArray[$key]["icon"] = "<img src=\"$icon_directory/spacer.gif\"   alt=\"$alt\" style=\"border: 0px; width: 16px; height: 16px; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='$icon_directory/$icon', sizingMethod='scale');\" />\n";
			} 
			else { 
				$sortArray[$key]["icon"] = "<img src=\"$icon_directory/$icon\"        alt=\"$alt\" style=\"border: 0px; width: 16px; height: 16px;\" />\n"; 
			}
		}
		else {
				$sortArray[$key]["icon"] = "";
		}
	}

// ------------------------------------
// popup - FormAndFieldname
// ------------------------------------
	if (isset($_POST["FormAndFieldName"]) == true) { $FormAndFieldName = validateGenericInput($_POST["FormAndFieldName"]); }
	else                                           { $FormAndFieldName = ""; }

// ------------------------------------
// Action URL
// Used for Up, Subdirectories, Files (download + actions)
// ------------------------------------
	$action_url = printPHP_SELF("actions");

// ------------------------------------
// Data transfer statistics
// Print this only if the consumption statistics are available (logging must be on, using a MySQL database)
// ------------------------------------
	if (isset($net2ftp_globals["consumption_ipaddress_datatransfer"]) == true || isset($net2ftp_globals["consumption_ftpserver_datatransfer"]) == true) {
		$print_consumption = true;
		$consumption_ipaddress_datatransfer = formatFilesize($net2ftp_globals["consumption_ipaddress_datatransfer"]);
		$consumption_ftpserver_datatransfer = formatFilesize($net2ftp_globals["consumption_ftpserver_datatransfer"]);
	}
	else {
		$print_consumption = false;
	}

// -------------------------------------------------------------------------
// Print the output - part 2
// -------------------------------------------------------------------------
	if ($net2ftp_globals["state2"] == "main") {
		setStatus(6, 10, __("Printing the list of directories and files"));
		require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/browse_main.template.php");
	}
	elseif ($net2ftp_globals["state2"] == "popup") {
		require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/browse_popup.template.php");
	}

} // end function net2ftp_printBody

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **

function sort_list($list) {

// --------------
// This function sorts the list of directories and files
// Written by Slynderdale
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals;


// -------------------------------------------------------------------------
// If the list is empty, return immediately
// -------------------------------------------------------------------------
	if ($net2ftp_globals["sort"] == "" || is_array($list) == false || sizeof($list) <= 1) { return $list; }


// -------------------------------------------------------------------------
// Default values
// -------------------------------------------------------------------------

// Sort flags
	if ($net2ftp_globals["sort"] == "size") { $sortflag = SORT_NUMERIC; }
	else                                    { $sortflag = SORT_REGULAR; }

// Sort ascending or descending
	if ($net2ftp_globals["sortorder"] == "ascending") { $sortfunction = "asort"; }
	else                                              { $sortfunction = "arsort"; }

// -------------------------------------------------------------------------
// Create a temporary array $temp which contains only the key $i and the value based on which the sorting is done
// -------------------------------------------------------------------------

// ------------------------------------
// Sorting according to name, size, owner, group, permissions
// ------------------------------------
	if ($net2ftp_globals["sort"] != "mtime" && $net2ftp_globals["sort"] != "type") {
		for($i=1; $i<=sizeof($list); $i++) {
			$temp[$i] = strtolower($list[$i][$net2ftp_globals["sort"]]);
		}
	}

// ------------------------------------
// When sorting according to the modification time, do not sort alphabetically (April, February, January, March),
// but according to the corresponding Unix timestamp
// ------------------------------------
	elseif ($net2ftp_globals["sort"] == "mtime") {
		for($i=1; $i<=sizeof($list); $i++) {

// Some FTP servers return the date and time in a non-standard format
// For example: "Apr 06 12:57". Transform this to "06 April 2005 12:57"
			if (preg_match("/([a-zA-Z]{3})[ ]+([0-9]{1,2})[ ]+([0-9]{1,2}:[0-9]{2})/", $list[$i]["mtime"], $regs) == true) {
				$month = $regs[1];
				$day   = $regs[2];
				$hour  = $regs[3];
				$year  = date("Y");
				if     ($month == "Jan") { $month = "January"; }
				elseif ($month == "Feb") { $month = "February"; }
				elseif ($month == "Mar") { $month = "March"; }
				elseif ($month == "Apr") { $month = "April"; }
				elseif ($month == "May") { $month = "May"; }
				elseif ($month == "Jun") { $month = "June"; }
				elseif ($month == "Jul") { $month = "July"; }
				elseif ($month == "Aug") { $month = "August"; }
				elseif ($month == "Sep") { $month = "September"; }
				elseif ($month == "Oct") { $month = "October"; }
				elseif ($month == "Nov") { $month = "November"; }
				elseif ($month == "Dec") { $month = "December"; }
				$mtime_correct = "$day $month $year $hour";
				$temp[$i] = strtotime($mtime_correct);
			}
			else {
				$temp[$i] = strtotime($list[$i]["mtime"]);
			}
		} // end for
	}

// ------------------------------------
// When sorting according to the file type, get the mime type for each entry
// ------------------------------------
	elseif ($net2ftp_globals["sort"] == "type") {
		for($i=1; $i<=sizeof($list); $i++) {
			$mime = getMime($list[$i]);
			$temp[$i] = $mime["mime_type"];
		} // end for
	}

// -------------------------------------------------------------------------
// Execute the sorting on the $temp array
// -------------------------------------------------------------------------
	$sortfunction($temp, $sortflag);

// -------------------------------------------------------------------------
// Fill the $return array
// -------------------------------------------------------------------------
	$i=1;
	while(list($tname, $tvalue) = each($temp)) {
		$return[$i] = $list[$tname];
		$i++;
	}

// -------------------------------------------------------------------------
// Return the result
// -------------------------------------------------------------------------
	return $return;

} // end function sort_list

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>