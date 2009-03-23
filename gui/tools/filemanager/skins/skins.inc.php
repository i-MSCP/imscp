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

function getSkinArray() {

// --------------
// This function returns an array of skin names, file names, ...
// --------------

	global $net2ftp_globals;

// -------------------------------------------------------------------------
// Blue
// -------------------------------------------------------------------------
	$skinArray["blue"]["name"]             = __("Blue");
	$skinArray["blue"]["iconset"]          = "nuvola";
	$skinArray["blue"]["image_url"]        = $net2ftp_globals["application_rootdir_url"] . "/skins/blue/images";
	$skinArray["blue"]["icon_size_mime"]   = "16";

// -------------------------------------------------------------------------
// Openlaszlo
// -------------------------------------------------------------------------
//	$skinArray["openlaszlo"]["name"]           = "OpenLaszlo";
//	$skinArray["openlaszlo"]["iconset"]        = "";
//	$skinArray["openlaszlo"]["image_url"]      = $net2ftp_globals["application_rootdir_url"] . "/skins/openlaszlo/images";
//	$skinArray["openlaszlo"]["icon_size_mime"] = "16";

// -------------------------------------------------------------------------
// India
// -------------------------------------------------------------------------
	$skinArray["india"]["name"]              = "India";
	$skinArray["india"]["iconset"]           = "nuvola";
	$skinArray["india"]["image_url"]         = $net2ftp_globals["application_rootdir_url"] . "/skins/india/images";
	$skinArray["india"]["icon_size_mime"]    = "32";

// -------------------------------------------------------------------------
// Mobile
// -------------------------------------------------------------------------
	$skinArray["mobile"]["name"]           = "Mobile";
	$skinArray["mobile"]["iconset"]        = "nuvola";
	$skinArray["mobile"]["image_url"]      = $net2ftp_globals["application_rootdir_url"] . "/skins/mobile/images";
	$skinArray["mobile"]["icon_size_mime"] = "0";

// -------------------------------------------------------------------------
// Mambo
// -------------------------------------------------------------------------
	if (defined("_VALID_MOS") == true) {
		$skinArray["mambo"]["name"]            = "Mambo";
		$skinArray["mambo"]["iconset"]         = "nuvola";
		$skinArray["mambo"]["image_url"]       = $net2ftp_globals["application_rootdir_url"] . "/skins/blue/images";
		$skinArray["mambo"]["icon_size_mime"]  = "16";
	}

// -------------------------------------------------------------------------
// Xoops
// -------------------------------------------------------------------------
	if (defined("XOOPS_ROOT_PATH") == true) {
		$skinArray["xoops"]["name"]            = "Xoops";
		$skinArray["xoops"]["iconset"]         = "nuvola";
		$skinArray["xoops"]["image_url"]       = $net2ftp_globals["application_rootdir_url"] . "/skins/blue/images";
		$skinArray["xoops"]["icon_size_mime"]  = "16";
	}

// -------------------------------------------------------------------------
// Drupal
// -------------------------------------------------------------------------
	if (defined("CACHE_PERMANENT") == true) {
		$skinArray["drupal"]["name"]           = "Drupal";
		$skinArray["drupal"]["iconset"]        = "nuvola";
		$skinArray["drupal"]["image_url"]      = $net2ftp_globals["application_rootdir_url"] . "/skins/blue/images";
		$skinArray["drupal"]["icon_size_mime"] = "16";
	}

	return $skinArray;

} // End function getSkinArray

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **
function printSkinSelect($fieldname, $onchange, $style, $class) {


// --------------
// This function prints a select with the available skins
// Skin nr 1 is the default skin
// --------------

	global $net2ftp_globals;
	$skinArray = getSkinArray();

	if     ($net2ftp_globals["skin"] != "")        { $currentskin = $net2ftp_globals["skin"]; }
	elseif ($net2ftp_globals["cookie_skin"] != "") { $currentskin = $net2ftp_globals["cookie_skin"]; }
	else                                           { $currentskin = "blue"; }

	if ($onchange == "") { $onchange_full = ""; }
	else                 { $onchange_full = "onchange=\"$onchange\""; }

	if ($style == "")    { $style_full = ""; }
	else                 { $style_full = "style=\"$style\""; }

	if ($class == "")    { $class_full = ""; }
	else                 { $class_full = "class=\"$class\""; }

	echo "<select name=\"$fieldname\" id=\"$fieldname\" $onchange_full $style_full $class_full>\n";

	while (list($key,$value) = each($skinArray)) {
	// $key loops over "blue", "pastel", ...
	// $value will be an array like $value["name"] = "Blue"
		if ($key == $currentskin) { $selected = "selected=\"selected\""; }
		else                      { $selected = ""; }
		echo "<option value=\"" . $key . "\" $selected>" . $value["name"] . "</option>\n";
	} // end while

	echo "</select>\n";

} // End function printSkinSelect

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function getMime($listline) {

// --------------
// Checks the extension of a file to determine which is the type of the file and the icon
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings;
	$skinArray = getSkinArray();

	if     ($listline["dirorfile"] == "d") { $last = "directory"; }
	elseif ($listline["dirorfile"] == "l") { $last = "symlink"; }
	else                                   { $last = get_filename_extension($listline["dirfilename"]); }

// -------------------------------------------------------------------------
// Icon names
// -------------------------------------------------------------------------
	if ($last == "directory") {
		$icon = "folder";
		$type = __("Directory");
	}
	elseif ($last == "symlink") {
		$icon = "folder_grey";
		$type = __("Symlink");
	}

// Web files
	elseif ($last == "asp") {
		$icon = "";
		$type = __("ASP script");
	}
	elseif ($last == "css") {
		$icon = "stylesheet";
		$type = __("Cascading Style Sheet");
	}
	elseif ($last == "htm" || $last == "html") {
		$icon = "html";
		$type = __("HTML file");
	}
	elseif ($last == "java") {
		$icon = "source_java";
		$type = __("Java source file");
	}
	elseif ($last == "js") {
		$icon = "";
		$type = __("JavaScript file");
	}
	elseif ($last == "phps") {
		$icon = "php";
		$type = __("PHP Source");
	}
	elseif (substr($last,0,3) == "php") {
		$icon = "php";
		$type = __("PHP script");
	}
	elseif ($last == "txt") {
		$icon = "document";
		$type = __("Text file");
	}

// Images
	elseif ($last == "bmp") {
		$icon = "colors";
		$type = __("Bitmap file");
	}
	elseif ($last == "gif") {
		$icon = "colors";
		$type = __("GIF file");
	}
	elseif ($last == "jpg" || $last == "jpeg") {
		$icon = "colors";
		$type = __("JPEG file");
	}
	elseif ($last == "png") {
		$icon = "colors";
		$type = __("PNG file");
	}
	elseif ($last == "tif" || $last == "tiff") {
		$icon = "colors";
		$type = __("TIF file");
	}
	elseif ($last == "xcf") {
		$icon = "gimp";
		$type = __("GIMP file");
	}

// Executables and scripts
	elseif ($last == "exe" || $last == "com") {
		$icon = "exec";
		$type = __("Executable");
	}
	elseif ($last == "sh") {
		$icon = "terminal";
		$type = __("Shell script");
	}

// MS Office
	elseif ($last == "doc") {
		$icon = "";
		$type = __("MS Office - Word document");
	}
	elseif ($last == "xls") {
		$icon = "";
		$type = __("MS Office - Excel spreadsheet");
	}
	elseif ($last == "ppt") {
		$icon = "";
		$type = __("MS Office - PowerPoint presentation");
	}
	elseif ($last == "mdb") {
		$icon = "";
		$type = __("MS Office - Access database");
	}
	elseif ($last == "vsd") {
		$icon = "";
		$type = __("MS Office - Visio drawing");
	}
	elseif ($last == "mpp") {
		$icon = "";
		$type = __("MS Office - Project file");
	}

// OpenOffice 6
	elseif ($last == "sxw") {
		$icon = "openoffice";
		$type = __("OpenOffice - Writer 6.0 document");
	}
	elseif ($last == "stw") {
		$icon = "openoffice";
		$type = __("OpenOffice - Writer 6.0 template");
	}
	elseif ($last == "sxc") {
		$icon = "openoffice";
		$type = __("OpenOffice - Calc 6.0 spreadsheet");
	}
	elseif ($last == "stc") {
		$icon = "openoffice";
		$type = __("OpenOffice - Calc 6.0 template");
	}
	elseif ($last == "sxd") {
		$icon = "openoffice";
		$type = __("OpenOffice - Draw 6.0 document");
	}
	elseif ($last == "std") {
		$icon = "openoffice";
		$type = __("OpenOffice - Draw 6.0 template");
	}
	elseif ($last == "sxi") {
		$icon = "openoffice";
		$type = __("OpenOffice - Impress 6.0 presentation");
	}
	elseif ($last == "sti") {
		$icon = "openoffice";
		$type = __("OpenOffice - Impress 6.0 template");
	}
	elseif ($last == "sxg") {
		$icon = "openoffice";
		$type = __("OpenOffice - Writer 6.0 global document");
	}
	elseif ($last == "sxm") {
		$icon = "openoffice";
		$type = __("OpenOffice - Math 6.0 document");
	}

// StarOffice 5
	elseif ($last == "sdw") {
		$icon = "openoffice";
		$type = __("StarOffice - StarWriter 5.x document");
	}
	elseif ($last == "sgl") {
		$icon = "openoffice";
		$type = __("StarOffice - StarWriter 5.x global document");
	}
	elseif ($last == "sdc") {
		$icon = "openoffice";
		$type = __("StarOffice - StarCalc 5.x spreadsheet");
	}
	elseif ($last == "sda") {
		$icon = "openoffice";
		$type = __("StarOffice - StarDraw 5.x document");
	}
	elseif ($last == "sdd") {
		$icon = "openoffice";
		$type = __("StarOffice - StarImpress 5.x presentation");
	}
	elseif ($last == "sdp") {
		$icon = "openoffice";
		$type = __("StarOffice - StarImpress Packed 5.x file");
	}
	elseif ($last == "smf") {
		$icon = "openoffice";
		$type = __("StarOffice - StarMath 5.x document");
	}
	elseif ($last == "sds") {
		$icon = "openoffice";
		$type = __("StarOffice - StarChart 5.x document");
	}
	elseif ($last == "sdm") {
		$icon = "openoffice";
		$type = __("StarOffice - StarMail 5.x mail file");
	}

// PDF and PS
	elseif ($last == "pdf") {
		$icon = "acroread";
		$type = __("Adobe Acrobat document");
	}

// Archives
	elseif ($last == "arc") {
		$icon = "tgz";
		$type = __("ARC archive");
	}
	elseif ($last == "arj") {
		$icon = "tgz";
		$type = __("ARJ archive");
	}
	elseif ($last == "rpm") {
		$icon = "rpm";
		$type = __("RPM");
	}
	elseif ($last == "gz") {
		$icon = "tgz";
		$type = __("GZ archive");
	}
	elseif ($last == "tar") {
		$icon = "tar";
		$type = __("TAR archive");
	}
	elseif ($last == "zip") {
		$icon = "tgz";
		$type = __("Zip archive");
	}

// Movies
	elseif ($last == "mov") {
		$icon = "video";
		$type = __("MOV movie file");
	}
	elseif ($last == "mpg" || $last == "mpeg") {
		$icon = "video";
		$type = __("MPEG movie file");
	}
	elseif ($last == "rm" || $last == "ram") {
		$icon = "realplayer";
		$type = __("Real movie file");
	}
	elseif ($last == "qt") {
		$icon = "quicktime";
		$type = __("Quicktime movie file");
	}

// Flash
	elseif ($last == "fla") {
		$icon = "";
		$type = __("Shockwave flash file");
	}
	elseif ($last == "swf") {
		$icon = "";
		$type = __("Shockwave file");
	}


// Sound
	elseif ($last == "wav") {
		$icon = "sound";
		$type = __("WAV sound file");
	}

// Fonts
	elseif ($last == "ttf") {
		$icon = "fonts";
		$type = __("Font file");
	}

// Default Extension
	elseif ($last) {
		$icon = "mime";
		$type = __("%1\$s File", strtoupper($last));
	}

// Default File
	else {
		$icon = "mime";
		$type = __("File");
	}

	if ($icon == "") { $icon = "mime"; }
	if ($type == "") { $type = __("File"); }

// -------------------------------------------------------------------------
// Return mime icon and mime type
// -------------------------------------------------------------------------

	// OpenLaszlo skin doesn't need HTML tags
	if ($net2ftp_globals["skin"] == "openlaszlo") {
		$mime["mime_icon"] = $icon . "_icon";
	}
	// Internet Explorer does not display transparent PNG images correctly.
	// A solution is described here: http://support.microsoft.com/default.aspx?scid=kb;en-us;Q294714
	elseif ($net2ftp_settings["fix_png"] == "yes" && $net2ftp_globals["browser_agent"] == "IE" && $net2ftp_globals["browser_platform"] == "Win" && ($net2ftp_globals["browser_version"] == "5.5" || $net2ftp_globals["browser_version"] == "6.0" || $net2ftp_globals["browser_version"] == "7.0")) { 
		$icon .= ".png";
		$icon_directory = $skinArray[$net2ftp_globals["skin"]]["image_url"] . "/mime";
		$mime["mime_icon"] = "<img src=\"$icon_directory/spacer.gif\" alt=\"icon\" style=\"width: " . $skinArray[$net2ftp_globals["skin"]]["icon_size_mime"] . "px; height: " . $skinArray[$net2ftp_globals["skin"]]["icon_size_mime"] . "px; border: 0px; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='$icon_directory/$icon', sizingMethod='scale')\" />\n"; 
	}
	else { 
		$icon .= ".png";
		$icon_directory = $skinArray[$net2ftp_globals["skin"]]["image_url"] . "/mime";
		$mime["mime_icon"] = "<img src=\"$icon_directory/$icon\"      alt=\"icon\" style=\"width: " . $skinArray[$net2ftp_globals["skin"]]["icon_size_mime"] . "px; height: " . $skinArray[$net2ftp_globals["skin"]]["icon_size_mime"] . "px; border: 0px;\" />\n"; 
	}

	$mime["mime_type"] = $type;

	return $mime;

} // end getMime

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function printMime($what, $listline) {

// --------------
// Prints the Mime icon
// --------------

	$mime = getMime($listline);

	if     ($what == "icon") {
		echo $mime["mime_icon"];
	}
	elseif ($what == "type") {
		echo $mime["mime_type"];
	}

} // end printMimeIcon

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function printActionIcon($action, $onclick) {

// --------------
// Checks the icon related to an action
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings;
	$skinArray = getSkinArray();

// -------------------------------------------------------------------------
// Icon name
// -------------------------------------------------------------------------
	if ($action == "back") {
		$alt = __("Back");
		$icon = "back";
		$accesskey = "b";
	}
	elseif ($action == "forward") {
		$alt = __("Submit");
		$icon = "button_ok";
		$accesskey = "v";
	}
	elseif ($action == "refresh") {
		$alt = __("Refresh");
		$icon = "reload";
		$accesskey = "r";
	}
	elseif ($action == "view_details") {
		$alt = __("Details");
		$icon = "view_detailed";
		$accesskey = "";
	}
	elseif ($action == "view_icons") {
		$alt = __("Icons");
		$icon = "view_icon";
		$accesskey = "";
	}
	elseif ($action == "listdirectories") {
		$alt = __("List");
		$icon = "view_tree";
		$accesskey = "";
	}
	elseif ($action == "logout") {
		$alt = __("Logout");
		$icon = "exit";
		$accesskey = "l";
	}
	elseif ($action == "help") {
		$alt = __("Help");
		$icon = "info";
		$accesskey = "i";
	}
	elseif ($action == "bookmark") {
		$alt = __("Bookmark");
		$icon = "bookmark";
		$accesskey = "h";
	}
	elseif ($action == "save") {
		$alt = __("Save");
		$icon = "filesave";
		$accesskey = "s";
	}
	elseif ($action == "up") {
		$alt = __("Up");
		$icon = "up";
		$accesskey = "u";
	}
	else {
		$alt = __("Default");
		$icon = "mime";
		$accesskey = "";
	}

	$icon .= ".png";
	if ($accesskey != "") { 
		$alt = $alt . " (accesskey $accesskey)";
		$accesskeytag = "accesskey=\"$accesskey\"" ; 
	}
	else {
		$accesskeytag = "";
	}

// -------------------------------------------------------------------------
// Icon directory
// -------------------------------------------------------------------------
	$icon_directory = $skinArray[$net2ftp_globals["skin"]]["image_url"] . "/actions";

// -------------------------------------------------------------------------
// URL
// Do not include a URL if $onclick is empty
// -------------------------------------------------------------------------
	if ($onclick != "") { 
		$href_start = "<a href=\"javascript:$onclick\" title=\"$alt\" $accesskeytag>"; 
		$href_end   = "</a>";
	}
	else {
		$href_start = ""; 
		$href_end   = "";
	}

// -------------------------------------------------------------------------
// Return text (for mobile skin) or icon (for all other skins)
// -------------------------------------------------------------------------

	if ($skinArray[$net2ftp_globals["skin"]]["icon_size_mime"] == 0) {
		$icon_total = "$href_start$action ($accesskey)$href_end\n"; 
	}

	// Internet Explorer does not display transparent PNG images correctly.
	// A solution is described here: http://support.microsoft.com/default.aspx?scid=kb;en-us;Q294714

	elseif ($net2ftp_settings["fix_png"] == "yes" && $net2ftp_globals["browser_agent"] == "IE" && $net2ftp_globals["browser_platform"] == "Win" && ($net2ftp_globals["browser_version"] == "5.5" || $net2ftp_globals["browser_version"] == "6.0" || $net2ftp_globals["browser_version"] == "7.0")) { 
		$icon_total = "$href_start<img src=\"$icon_directory/spacer.gif\" alt=\"$alt\" onmouseover=\"this.style.margin='0px';this.style.width='34px';this.style.height='34px';\" onmouseout=\"this.style.margin='1px';this.style.width='32px';this.style.height='32px';\" style=\"border: 0px; margin: 1px; width: 32px; height: 32px; vertical-align: middle; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='$icon_directory/$icon', sizingMethod='scale');\" />$href_end\n"; 
	}
	else { 
		$icon_total = "$href_start<img src=\"$icon_directory/$icon\"      alt=\"$alt\" onmouseover=\"this.style.margin='0px';this.style.width='34px';this.style.height='34px';\" onmouseout=\"this.style.margin='1px';this.style.width='32px';this.style.height='32px';\" style=\"border: 0px; margin: 1px; width: 32px; height: 32px; vertical-align: middle;\" />$href_end\n"; 
	}

	echo $icon_total;


/* -------------------------------------------------------------------------

---------------------------
  Accesskey documentation
---------------------------

Tutorial
http://www.cs.tut.fi/~jkorpela/forms/accesskey.html
ALT-A and ALT-F may not be used on IE

Login page
---------------------------
l login

Logout page
---------------------------
l link to login page

Browse page
---------------------------
See icons above.
Used: b, v, r, l, i, h, s

g directory textbox

Actions
w new dir
y new file
e install template
u upload, up
n advanced
c copy
m move
d delete
o rename
p chmod
x download
z zip
q size
j search

Headers
k up
t all

Items
1 item 1
2 item 2
...
9 item 9


------------------------------------------------------------------------- */

} // end printActionIcon

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************







// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function printModeIcon($setting, $on_off, $onclick) {

// --------------
// Checks the icon related to a mode
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings;

	if ($setting == "details") {
		$alt = __("Details");
		$icon = "view_detailed";
	}
	elseif ($setting == "icons") {
		$alt = __("Icons");
		$icon = "view_icon";
	}

// Default
	else {
		$alt = __("Default");
		$icon = "mime";
	}

// Default
	if ($alt  == "") { $alt  = "Default"; }
	if ($icon == "") { $icon = "mime"; }

// On or off: icon and style
	if ($on_off == "on") {
		$icon_normal      = $icon;
		$icon_onmouseover = $icon;
	}
	else {
		$icon_normal      = $icon . "_light";
		$icon_onmouseover = $icon;
	}

// -------------------------------------------------------------------------
// Icon directory
// -------------------------------------------------------------------------
	$icon_directory = $skinArray[$net2ftp_globals["skin"]]["image_url"] . "/settings";

// -------------------------------------------------------------------------
// Return icon
// -------------------------------------------------------------------------

// DO NOT CLOSE THE IMAGE TAG TO ALLOW ADDITIONAL ACTIONS
	if ($on_off == "on") {
		if ($net2ftp_settings["fix_png"] == "yes" && $net2ftp_globals["browser_agent"] == "IE" && $net2ftp_globals["browser_platform"] == "Win" && ($net2ftp_globals["browser_version"] == "5.5" || $net2ftp_globals["browser_version"] == "6.0" || $net2ftp_globals["browser_version"] == "7.0")) { 
			$icon_total = "<img src=\"$icon_directory/spacer.gif\"   alt=\"$alt\" style=\"border: 2px solid #000000; padding-top: 1px; padding-left: 2px; width: 32px; height: 32px; vertical-align: middle; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='$icon_directory/$icon', sizingMethod='scale');\" />\n"; 
		}
		else {
			$icon_total = "<img src=\"$icon_directory/$icon_normal\" alt=\"$alt\" style=\"border: 2px solid #000000; padding-top: 1px; padding-left: 2px; width: 32px; height: 32px; vertical-align: middle;\" />\n"; 
		}
	}
	else {
		if ($net2ftp_settings["fix_png"] == "yes" && $net2ftp_globals["browser_agent"] == "IE" && $net2ftp_globals["browser_platform"] == "Win" && ($net2ftp_globals["browser_version"] == "5.5" || $net2ftp_globals["browser_version"] == "6.0" || $net2ftp_globals["browser_version"] == "7.0")) {
			$icon_total = "<a href=\"javascript:$onClick\"><img src=\"$icon_directory/spacer.gif\"   alt=\"$alt\" onmouseover=\"this.style.margin='0px';this.style.width='34px';this.style.height='34px';\" onmouseout=\"this.style.margin='1px';this.style.width='32px';this.style.height='32px';\" style=\"border: 0px; margin: 1px; width: 32px; height: 32px; vertical-align: middle; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='$icon_directory/$icon', sizingMethod='scale');\" /></a>\n"; 
		}
		else { 
			$icon_total = "<a href=\"javascript:$onClick\" title=\"$alt\"><img src=\"$icon_directory/$icon_normal\" alt=\"$alt\" onmouseover=\"this.style.margin='0px';this.style.width='34px';this.style.height='34px';\" onmouseout=\"this.style.margin='1px';this.style.width='32px';this.style.height='32px';\" style=\"border: 0px; margin: 1px; width: 32px; height: 32px; vertical-align: middle;\" /></a>\n"; 
		}
	}

	return $icon_total;

} // end printModeIcon

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************







// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function printTitleIcon() {

// --------------
// This function returns the title icon based on the $state and $state2 variables
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings;
	$skinArray = getSkinArray();
	
// -------------------------------------------------------------------------
// Icon names
// -------------------------------------------------------------------------
	if     ($net2ftp_globals["state"] == "admin" || $net2ftp_globals["state"] == "admin_createtables" || $net2ftp_globals["state"] == "admin_emptylogs" || $net2ftp_globals["state"] == "admin_viewlogs") {
		$icon = "kcontrol";
	}
	elseif ($net2ftp_globals["state"] == "advanced" || $net2ftp_globals["state"] == "advanced_ftpserver" || $net2ftp_globals["state"] == "advanced_parsing" || $net2ftp_globals["state"] == "advanced_webserver") {
		$icon = "misc";
	}
	elseif ($net2ftp_globals["state"] == "bookmark") {
		$icon = "bookmark";
	}
	elseif ($net2ftp_globals["state"] == "calculatesize") {
		$icon = "";
	}
	elseif ($net2ftp_globals["state"] == "chmod") {
		$icon = "kgpg_info";
	}
	elseif ($net2ftp_globals["state"] == "copymovedelete") {
		if     ($net2ftp_globals["state2"] == "copy")   { $icon = "editcopy"; }
		elseif ($net2ftp_globals["state2"] == "move")   { $icon = "editcut"; }
		elseif ($net2ftp_globals["state2"] == "delete") { $icon = "edittrash"; }
	}
	elseif ($net2ftp_globals["state"] == "easywebsite") {
		$icon = "colorize";
	}
	elseif ($net2ftp_globals["state"] == "edit") {
		$icon = "package_editors";
	}
	elseif ($net2ftp_globals["state"] == "findstring") {
		$icon = "viewmag";
	}
	elseif ($net2ftp_globals["state"] == "jupload" || $net2ftp_globals["state"] == "upload") {
		$icon = "konquest";
	}
	elseif ($net2ftp_globals["state"] == "login" || $net2ftp_globals["state"] == "login_small") {
		$icon = "kgpg_identity";
	}
	elseif ($net2ftp_globals["state"] == "newdir") {
		$icon = "folder_new";
	}
	elseif ($net2ftp_globals["state"] == "rename") {
		$icon = "folder_txt";
	}
	elseif ($net2ftp_globals["state"] == "updatefile") {
		$icon = "view_left_right";
	}
	elseif ($net2ftp_globals["state"] == "view") {
		if     ($net2ftp_globals["state2"] == "image") { $icon = "thumbnail"; }
		elseif ($net2ftp_globals["state2"] == "flash") { $icon = "aktion"; }
		elseif ($net2ftp_globals["state2"] == "text")  { $icon = "terminal"; }
	}
	elseif ($net2ftp_globals["state"] == "zip") {
		$icon = "ark";
	}

// Default File
	else {
		$icon = "misc";
	}

	if ($icon == "") { $icon = "misc"; }

// -------------------------------------------------------------------------
// Return title icon
// -------------------------------------------------------------------------
	$icon .= ".png";
	$icon_directory = $skinArray[$net2ftp_globals["skin"]]["image_url"] . "/titles";
	
	// Internet Explorer does not display transparent PNG images correctly.
	// A solution is described here: http://support.microsoft.com/default.aspx?scid=kb;en-us;Q294714
	if ($net2ftp_settings["fix_png"] == "yes" && $net2ftp_globals["browser_agent"] == "IE" && $net2ftp_globals["browser_platform"] == "Win" && ($net2ftp_globals["browser_version"] == "5.5" || $net2ftp_globals["browser_version"] == "6.0" || $net2ftp_globals["browser_version"] == "7.0")) { 
		$icon_total = "<img src=\"$icon_directory/spacer.gif\" alt=\"icon\" style=\"width: 48px; height: 48px; vertical-align: middle; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='$icon_directory/$icon', sizingMethod='scale')\" />\n"; 
	}
	else { 
		$icon_total = "<img src=\"$icon_directory/$icon\"      alt=\"icon\" style=\"width: 48px; height: 48px; vertical-align: middle;\" />\n"; 
	}

	echo $icon_total;

} // end printTitleIcon

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************



// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function printPngImage($src, $alt, $style) {

// --------------
// This function prints a .png image with or without the fix for IE
// Prerequisite: spacer.gif must exist in the same directory as the image
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings;

// -------------------------------------------------------------------------
// Calculate the src of spacer.gif
// -------------------------------------------------------------------------
	$last_slash_position = strrpos($src, "/");
	if ($last_slash_position === false) { $src_spacer = "spacer.gif"; }
	else { 
		$src_spacer = substr($src, 0, $last_slash_position+1) . "spacer.gif";
	}

// -------------------------------------------------------------------------
// Form the HTML
// -------------------------------------------------------------------------

	// Internet Explorer does not display transparent PNG images correctly.
	// A solution is described here: http://support.microsoft.com/default.aspx?scid=kb;en-us;Q294714
	if ($net2ftp_settings["fix_png"] == "yes" && $net2ftp_globals["browser_agent"] == "IE" && $net2ftp_globals["browser_platform"] == "Win" && ($net2ftp_globals["browser_version"] == "5.5" || $net2ftp_globals["browser_version"] == "6.0" || $net2ftp_globals["browser_version"] == "7.0")) { 
		$image = "<img src=\"$src_spacer\" alt=\"$alt\" style=\"$style; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='$src', sizingMethod='scale')\" />\n"; 
	}
	else { 
		$image = "<img src=\"$src\" alt=\"$alt\" style=\"$style\" />\n"; 
	}

	echo $image;

} // end printPngImage

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************


?>