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

function ftp_getlist($conn_id, $directory) {

// --------------
// This function connects to the FTP server and returns an array with a list of directories and files.
// One row per directory or file, with rows from index 1 to n
//
// Step 1: send ftp_rawlist request to the FTP server; this returns a string
// Step 2: parse that string and get a first array ($templist)
// Step 3: move the rows to another array, to index 1 to n ($list)
//
// This function is used in all functions which process directories recursively.
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings;

// -------------------------------------------------------------------------
// Initialization
// -------------------------------------------------------------------------
	$warnings = "";

// -------------------------------------------------------------------------
// Step 1: Chdir to the directory and get the current directory
// -------------------------------------------------------------------------

// ----------------------------------------------
// Step 1a - Directory is "/"
// Chdir to the directory because otherwise the ftp_rawlist does not work on some FTP servers
// ----------------------------------------------
	if ($directory == "/") { 
		$result1a = @ftp_chdir($conn_id, $directory);	
	}

// ----------------------------------------------
// Step 1b - Directory is ""
// If the directory is "" then the user can be directed to his home directory
// We don't know which directory it is, so we request it from the FTP server
// ----------------------------------------------
	elseif ($directory == "") { 
		$result1b = @ftp_chdir($conn_id, $directory);
		$directory = @ftp_pwd($conn_id);
	}

// ----------------------------------------------
// Step 1c - Directory is not "/" or ""
// ----------------------------------------------
	else {

// 1c1 - Replace \' by \\' to be able to delete directories with names containing \'
		$directory1 = str_replace("\'", "\\\'", $directory); 

// 1c2 - Chdir to the directory
// This is to check if the directory exists, but also because otherwise
// the ftp_rawlist does not work on some FTP servers.
		$result1c = @ftp_chdir($conn_id, $directory1);

// 1c3 - If the first ftp_chdir returns false, try a second time without the leading /
// Some Windows FTP servers do not work when you use a leading /
		if ($result1c == false) {
			$directory2 = stripDirectory($directory1);
			$result2 = @ftp_chdir($conn_id, $directory2);

// 1c3 - If the second ftp_chdir also does not work:
//           For the Browse screen ==> go to the user's root directory
//           For all other screens ==> return error
			if ($result2 == false) {
				if ($net2ftp_globals["state"] == "browse") {
					$rootdirectory = getRootdirectory();

					// User's root directory is different from the current directory, so switch to it
					if ($directory != $rootdirectory) {
						$warnings .= __("The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead.", $directory, $rootdirectory);
						$directory = $rootdirectory;
						$result3 = @ftp_chdir($conn_id, $directory);
					}

					// The current directory *is* the user's root directory!
					// We cannot display any other directory (like /), so print an error message.
					else {
						$errormessage = __("Your root directory <b>%1\$s</b> does not exist or could not be selected.", $directory);
						setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
					}
				}
				else {
					$errormessage = __("The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist.", $directory);
					setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				}
			} // end if result2

		} // end if result1
	
	} // end if / or "" or else


// -------------------------------------------------------------------------
// Step 2 - Get list of directories and files
// The -a option is used to show the hidden files as well on some FTP servers
// Some servers do not return anything when using -a, so in that case try again without the -a option
// -------------------------------------------------------------------------
	$rawlist = ftp_rawlist($conn_id, "-a");
	if (sizeof($rawlist) <= 1) { $rawlist = ftp_rawlist($conn_id, ""); }


// -------------------------------------------------------------------------
// Step 3 - Parse the raw list
// -------------------------------------------------------------------------

// ----------------------------------------------
// Initialize variables
// ----------------------------------------------
	$list["directories"]  = array();
	$list["files"]        = array();
	$list["symlinks"]     = array();
	$list["unrecognized"] = array();
	$directory_index    = 1;
	$file_index         = 1;
	$symlink_index      = 1;
	$unrecognized_index = 1;
	$list["stats"]["directories"]["total_number"]   = 0;
	$list["stats"]["directories"]["total_size"]     = 0;
	$list["stats"]["directories"]["total_skipped"]  = 0;
	$list["stats"]["files"]["total_number"]         = 0;
	$list["stats"]["files"]["total_size"]           = 0;
	$list["stats"]["files"]["total_skipped"]        = 0;
	$list["stats"]["symlinks"]["total_number"]      = 0;
	$list["stats"]["symlinks"]["total_size"]        = 0;
	$list["stats"]["symlinks"]["total_skipped"]     = 0;
	$list["stats"]["unrecognized"]["total_number"]  = 0;
	$list["stats"]["unrecognized"]["total_size"]    = 0;
	$list["stats"]["unrecognized"]["total_skipped"] = 0;

// ----------------------------------------------
// Loop over the raw list lines
// ----------------------------------------------
	$nr_entries_banned_keyword = 0;
	$nr_entries_too_big        = 0;

	for($i=0; $i<sizeof($rawlist); $i++) {

// ----------------------------------------------
// Scan each line
// ----------------------------------------------
		$listline = ftp_scanline($directory, $rawlist[$i]);

// If $listline is empty (e.g. if it contained ".."), continue to the next line
		if ($listline == "") { continue; }

// Encode the name for HTML and Javascript
		if (isset($listline["dirfilename"])) { 
			$listline["dirfilename_html"] = htmlEncode2($listline["dirfilename"]);
			$listline["dirfilename_url"]  = urlEncode2($listline["dirfilename"]);
			$listline["dirfilename_js"]   = javascriptEncode2($listline["dirfilename"]);
		}

// Check if the filename contains a forbidden keyword
// If it does, then this line will not be selectable on the Browse screen
// Note: even if "selectable" is set to true here, it can still be set to false just below if the filesize is too big
		if (checkAuthorizedName($listline["dirfilename"]) == true) { $listline["selectable"] = "ok"; }
		else                                                       { $listline["selectable"] = "banned_keyword"; $nr_entries_banned_keyword++; }

// Check if the filesize is bigger than the maximum authorized filesize
		if ($listline["dirorfile"] == "-" && isset($listline["size"]) && is_numeric($listline["size"])) { 
			if ($listline["selectable"] == "ok" && $listline["size"] > $net2ftp_settings["max_filesize"]) { $listline["selectable"] = "too_big"; $nr_entries_too_big++; }
		}

// Form the new directory filename and encode it too
		if ($listline["dirorfile"] == "d") {
			$listline["newdir"]      = glueDirectories($directory, $listline["dirfilename"]); 
			$listline["newdir_html"] = htmlEncode2($listline["newdir"]);
			$listline["newdir_url"]  = urlEncode2($listline["newdir"]);
			$listline["newdir_js"]   = javascriptEncode2($listline["newdir"]);
		}

// ----------------------------------------------
// Depending on if the line contained a directory/file/symlink/unrecognized 
// row, store the result in different variables
// ----------------------------------------------

		if ($listline["dirorfile"] == "d") { 
			$list["directories"][$directory_index] = $listline;
			$directory_index++;
			if (isset($listline["size"]) && is_numeric($listline["size"])) { 
				$list["stats"]["directories"]["total_size"] = $list["stats"]["directories"]["total_size"] + $listline["size"]; 
			}
			else { 
				$list["stats"]["directories"]["total_skipped"] = $list["stats"]["directories"]["total_skipped"] + 1; 
			}
		} // end if
		elseif ($listline["dirorfile"] == "-") { 
			$list["files"][$file_index] = $listline;
			$file_index++;
			if (isset($listline["size"]) && is_numeric($listline["size"])) { 
				$list["stats"]["files"]["total_size"] = $list["stats"]["files"]["total_size"] + $listline["size"]; 
			}
			else { 
				$list["stats"]["files"]["total_skipped"] = $list["stats"]["files"]["total_skipped"] + 1; 
			}
		} // end elseif
		elseif ($listline["dirorfile"] == "l") { 
			$list["symlinks"][$symlink_index] = $listline;
			$symlink_index++;
		} // end elseif
		elseif ($listline["dirorfile"] == "u") { 
			$list["unrecognized"][$unrecognized_index] = $listline;
			$unrecognized_index++;
		} // end elseif

	} // end for

// Print a warning message if some directories, files or symlinks contain a banned keyword or if a file is 
// too big to be downloaded
	if ($nr_entries_banned_keyword > 0) {
		$warnings .= __("Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp.");
		$warnings .= "<br />\n";
	}
	if ($nr_entries_too_big > 0) {
		$warnings .= __("Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted.");
		$warnings .= "<br />\n";
	}


// Store the warnings and new directory in $list["stats"]
	if (isset($warnings) == true) { $list["stats"]["warnings"] = $warnings; }
	else                          { $list["stats"]["warnings"] = ""; }
	$list["stats"]["newdirectory"] = $directory;

// Store the statistics
	$list["stats"]["directories"]["total_size_formated"] = formatFilesize($list["stats"]["directories"]["total_size"]);
	$list["stats"]["files"]["total_size_formated"]       = formatFilesize($list["stats"]["files"]["total_size"]);
	$list["stats"]["directories"]["total_number"]  = $directory_index - 1;
	$list["stats"]["files"]["total_number"]        = $file_index - 1;
	$list["stats"]["symlinks"]["total_number"]     = $symlink_index - 1;
	$list["stats"]["unrecognized"]["total_number"] = $unrecognized_index - 1;

// Put everything together in $list["all"]
	$list["all"] = $list["directories"] + $list["files"] + $list["symlinks"] + $list["unrecognized"];

// -------------------------------------------------------------------------
// Step 4 - Return the result
// -------------------------------------------------------------------------
	return $list;




// -------------------------------------------------------------------------
// Some documentation:
// 1 - Some FTP servers return a total on the first line
// 2 - Some FTP servers return . and .. in their list of directories
// ftp_scanline does not return those entries.
// -------------------------------------------------------------------------


// 1 - After doing some tests on different public FTP servers, it appears that
// they reply differently to the ftp_rawlist request:
//      - some FTP servers, like ftp.belnet.be, start with a line summarizing how
//        many subdirectories and files there are in the current directory. The
//        real list of subdirectories and files starts on the second line.
//               [0] => total 15
//               [1] => drwxr-xr-x 11 BELNET Archive 512 Feb 6 2000 BELNET
//               [2] => drwxr-xr-x 2 BELNET Archive 512 Oct 29 2001 FVD-SFI
//      - some other FTP servers, like ftp.redhat.com/pub, start directly with the
//        list of subdirectories and files.
//               [0] => drwxr-xr-x 9 ftp ftp 4096 Jan 11 06:34 contrib
//               [1] => drwxr-xr-x 13 ftp ftp 4096 Jan 29 21:59 redhat
//               [2] => drwxrwsr-x 6 ftp ftp 4096 Jun 05 2002 up2date


// 2 - Some FTP servers return "." and ".." as directories. These fake entries
// have to be eliminated!
// They would cause infinite loops in the copy/move/delete functions.
//               [0] => drwxr-xr-x 5 80 www 512 Apr 10 09:39 .
//               [1] => drwxr-xr-x 16 80 www 512 Apr 9 08:51 ..
//               [2] => -rw-r--r-- 1 80 www 5647 Apr 9 08:12 _CHANGES_v0.5
//               [3] => -rw-r--r-- 1 80 www 1239 Apr 9 08:12 _CREDITS_v0.5


} // End function ftp_getlist

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **
function ftp_scanline($directory, $rawlistline) {

// --------------
// This function scans an ftp_rawlist line string and returns its parts (directory/file, name, size,...) using preg_match()
//
//  !!! Documentation about preg_match and FTP server's outputs are now at the end of the function !!!
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_messages;


// -------------------------------------------------------------------------
// Scanning:
//   1. first scan with strict rules
//   2. if that does not match, scan with less strict rules
//   3. if that does not match, scan with rules for specific FTP servers (AS400)
//   4. and if that does not match, return the raw line
// -------------------------------------------------------------------------

// ----------------------------------------------
// 1. Strict rules
// ----------------------------------------------
	if (preg_match("/([-dl])([rwxsStT-]{9})[ ]+([0-9]+)[ ]+([^ ]+)[ ]+(.+)[ ]+([0-9]+)[ ]+([a-zA-Z]+[ ]+[0-9]+)[ ]+([0-9:]+)[ ]+(.*)/", $rawlistline, $regs) == true) {
//                  permissions             number      owner      group   size        month         day        year/hour    filename
		$listline["scanrule"]         = "rule-1";
		$listline["dirorfile"]        = "$regs[1]";		// Directory ==> d, File ==> -
		$listline["dirfilename"]      = "$regs[9]";		// Filename
		$listline["size"]             = "$regs[6]";		// Size
		$listline["owner"]            = "$regs[4]";		// Owner
		$listline["group"]            = "$regs[5]";		// Group
		$listline["permissions"]      = "$regs[2]";		// Permissions
		$listline["mtime"]            = "$regs[7] $regs[8]";	// Mtime -- format depends on what FTP server returns (year, month, day, hour, minutes... see above)
	}

// ----------------------------------------------
// 2. Less strict rules
// ----------------------------------------------
	elseif (preg_match("/([-dl])([rwxsStT-]{9})[ ]+(.*)[ ]+([a-zA-Z0-9 ]+)[ ]+([0-9:]+)[ ]+(.*)/", $rawlistline, $regs) == true) {
//                      permissions             number/owner/group/size
//                                                      month-day          year/hour    filename
		$listline["scanrule"]         = "rule-2";
		$listline["dirorfile"]        = "$regs[1]";		// Directory ==> d, File ==> -
		$listline["dirfilename"]      = "$regs[6]";		// Filename
		$listline["size"]             = "$regs[3]";		// Number/Owner/Group/Size
		$listline["permissions"]      = "$regs[2]";		// Permissions
		$listline["mtime"]            = "$regs[4] $regs[5]";	// Mtime -- format depends on what FTP server returns (year, month, day, hour, minutes... see above)
	}

// ----------------------------------------------
// 3. Specific FTP server rules
// ----------------------------------------------

// ---------------
// 3.1 Windows
// ---------------
	elseif (preg_match("/([0-9\\/-]+)[ ]+([0-9:AMP]+)[ ]+([0-9]*|<DIR>)[ ]+(.*)/", $rawlistline, $regs) == true) {
//                      date          time            size              filename

		$listline["scanrule"]         = "rule-3.1";
		if ($regs[3] == "<DIR>") { $listline["size"] = ""; }
		else                     { $listline["size"] = "$regs[3]"; } // Size
		$listline["dirfilename"] = "$regs[4]";		// Filename
		$listline["owner"]            = "";			// Owner
		$listline["group"]            = "";			// Group
		$listline["permissions"]      = "";			// Permissions
		$listline["mtime"]            = "$regs[1] $regs[2]";	// Mtime -- format depends on what FTP server returns (year, month, day, hour, minutes... see above)

		if ($listline["size"] != "") { $listline["dirorfile"] = "-"; }
		else                         { $listline["dirorfile"] = "d"; }

	}

// ---------------
// 3.2 Netware
// Thanks to Danny!
// ---------------
	elseif (preg_match("/([-]|[d])[ ]+(.{10})[ ]+([^ ]+)[ ]+([0-9]*)[ ]+([a-zA-Z]*[ ]+[0-9]*)[ ]+([0-9:]*)[ ]+(.*)/", $rawlistline, $regs) == true) {
//                      dir/file perms          owner      size        month        day         hour         filename
		$listline["scanrule"]         = "rule-3.2";
		$listline["dirorfile"]        = "$regs[1]";		// Directory ==> d, File ==> -
		$listline["dirfilename"]      = "$regs[7]";		// Filename
		$listline["size"]             = "$regs[4]";		// Size
		$listline["owner"]            = "$regs[3]";		// Owner
		$listline["group"]            = "";			// Group
		$listline["permissions"]      = "$regs[2]";		// Permissions
		$listline["mtime"]            = "$regs[5] $regs6";	// Mtime -- format depends on what FTP server returns (year, month, day, hour, minutes... see above)
	}

// ---------------
// 3.3 AS400
// ---------------
	elseif (preg_match("/([a-zA-Z0-9_-]+)[ ]+([0-9]+)[ ]+([0-9\\/-]+)[ ]+([0-9:]+)[ ]+([a-zA-Z0-9_ -\*]+)[ \\/]+([^\\/]+)/", $rawlistline, $regs) == true) {
//                      owner               size        date          time         type                    filename

		if ($regs[5] != "*STMF") { $directory_or_file = "d"; }
		elseif ($regs[5] == "*STMF") { $directory_or_file = "-"; }

		$listline["scanrule"]         = "rule-3.3";
		$listline["dirorfile"]        = "$directory_or_file";// Directory ==> d, File ==> -
		$listline["dirfilename"]      = "$regs[6]";		// Filename
		$listline["size"]             = "$regs[2]";		// Size
		$listline["owner"]            = "$regs[1]";		// Owner
		$listline["group"]            = "";			// Group
		$listline["permissions"]      = "";			// Permissions
		$listline["mtime"]            = "$regs[3] $regs[4]";	// Mtime -- format depends on what FTP server returns (year, month, day, hour, minutes... see above)
	}

// ---------------
// 3.4 Titan
// Owner, group are modified compared to rule 1
// TO DO: integrate this rule in rule 1 itself
// ---------------
	elseif (preg_match("/([-dl])([rwxsStT-]{9})[ ]+([0-9]+)[ ]+([a-zA-Z0-9]+)[ ]+([a-zA-Z0-9]+)[ ]+([0-9]+)[ ]+([a-zA-Z]+[ ]+[0-9]+)[ ]+([0-9:]+)[ ](.*)/", $rawlistline, $regs) == true) {
//                      dir/file permissions    number      owner             group             size         month       date        time       file
		$listline["scanrule"]         = "rule-3.4";
		$listline["dirorfile"]        = "$regs[1]";        // Directory ==> d, File ==> -
		$listline["dirfilename"]      = "$regs[9]";        // Filename
		$listline["size"]             = "$regs[6]";        // Size
		$listline["owner"]            = "$regs[4]";        // Owner
		$listline["group"]            = "$regs[5]";        // Group
		$listline["permissions"]      = "$regs[2]";        // Permissions
		$listline["mtime"]            = "$regs[7] $regs[8]";    // Mtime -- format depends on what FTP server returns (year, month, day, hour, minutes... see above)
	}

// ----------------------------------------------
// 4. If nothing matchs, return the raw line
// ----------------------------------------------
	else {
		$listline["scanrule"]         = "rule-4";
		$listline["dirorfile"]        = "u";
		$listline["dirfilename"]      = $rawlistline;
	}

// -------------------------------------------------------------------------
// Remove the . and .. entries
// Remove the total line that some servers return
// -------------------------------------------------------------------------
	if ($listline["dirfilename"] == "." || $listline["dirfilename"] == "..") { return ""; }
	elseif (substr($rawlistline,0,5) == "total") { return ""; }

// -------------------------------------------------------------------------
// And finally... return the nice list!
// -------------------------------------------------------------------------
	return $listline;




// -------------------------------------------------------------------------
// Documentation
// -------------------------------------------------------------------------

/*

mholdgate@wakefield.co.uk
11-Jan-2002 11:51

^                Start of String
$                End of string

n*               Zero or more of 'n'
n+               One or more of 'n'
n?               A possible 'n'

n{2}             Exactly two of 'n'
n{2,}            At least 2 or more of 'n'
n{2,4}           From 2 to 4 of 'n'

()               Parenthesis to group expressions
(n|a)            Either 'n' or 'a'

.                Any single character

[1-6]            A number between 1 and 6
[c-h]            A lower case character between c and h
[D-M]            An upper case character between D and M
[^a-z]           Absence of lower case a to z
[_a-zA-Z]        An underscore or any letter of the alphabet

^.{2}[a-z]{1,2}_?[0-9]*([1-6]|[a-f])[^1-9]{2}a+$

A string beginning with any two characters
Followed by either 1 or 2 lower case alphabet letters
Followed by an optional underscore
Followed by zero or more digits
Followed by either a number between 1 and 6 or a character between a and f (Lowercase)
Followed by a two characters which are not digits between 1 and 9
Followed by one or more n characters at the end of a string

// $regs can contain a maximum of 10 elements !! (regs[0] to regs[9])
// To specify what you really want back from ereg, use (). Only what is within () will be returned. See below.

*/

// ----------------------------------------------
// Sample FTP server's output
// ----------------------------------------------

// ---------------
// 1. "Standard" FTP servers output
// ---------------
// ftp.redhat.com
//drwxr-xr-x    6 0        0            4096 Aug 21  2001 pub (one or more spaces between entries)
//
// ftp.suse.com
//drwxr-xr-x   2 root     root         4096 Jan  9  2001 bin
//-rw-r--r--    1 suse     susewww       664 May 23 16:24 README.txt
//
// ftp.belnet.be
//-rw-r--r--   1 BELNET   Mirror        162 Aug  6  2000 HEADER.html
//drwxr-xr-x  53 BELNET   Archive      2048 Nov 13 12:03 mirror
//
// ftp.microsoft.com
//-r-xr-xr-x   1 owner    group               0 Nov 27  2000 dirmap.htm
//
// ftp.sourceforge.net
//-rw-r--r--   1 root     staff    29136068 Apr 21 22:07 ls-lR.gz
//
// ftp.nec.com
//dr-xr-xr-x  12 other        512 Apr  3  2002 pub
//
// ftp.intel.com
//drwxr-sr-x   11 root     ftp          4096 Sep 23 16:36 pub

// ---------------
// 3.1 Windows
// ---------------
//06-10-04  07:56PM                 8175 garantie.html
//04-09-04  04:27PM       <DIR>          images
//05-25-04  09:18AM                 9505 index.html

// ---------------
// 3.2 Netware
// ---------------
// total 0
// - [RWCEAFMS] USER 12 Mar 08 10:48 check.txt
// d [RWCEAFMS] USER 512 Mar 18 17:55 latest


// ---------------
// 3.3 AS400
// ---------------
// RGOVINDAN 932 03/29/01 14:59:53 *STMF /cert.txt
// QSYS 77824 12/17/01 15:33:14 *DIR /QOpenSys/
// QDOC 24576 12/31/69 20:00:00 *FLR /QDLS/
// QSYS 12832768 04/14/03 16:47:25 *LIB /QSYS.LIB/
// QDFTOWN 2147483647 12/31/69 20:00:00 *DDIR /QOPT/
// QSYS 2144 04/12/03 12:49:00 *DDIR /QFileSvr.400/
// QDFTOWN 1136 04/12/03 12:49:01 *DDIR /QNTC/

// ---------------
// 3.4 Titan FTP server
// ---------------
// total 6
// drwxrwx--- 1 owner group 512 Apr 19 11:44 .
// drwxrwx--- 1 owner group 512 Apr 19 11:44 ..
// -rw-rw---- 1 owner group 13171 Apr 15 13:50 default.asp
// drwxrwx--- 1 owner group 512 Apr 19 11:44 forum
// drwxrwx--- 1 owner group 512 Apr 15 13:32 images
// -rw-rw---- 1 owner group 764 Apr 15 11:07 styles.css

} // End function ftp_scanline

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function ftp2http($directory, $list_files, $htmltags) {

// --------------
// This function calculates the HTTP URL based on the FTP URL
//
// Given the FTP server (ftp.name.com),
//       the directory and file (/directory/file.php)
// It has to return
//       http://www.name.com/directory/file.php
//
// $htmltags indicates whether the url should be returned enclosed in HTML tags or not
//
// For efficiency reasons, this function processes a list of files
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals;

// -------------------------------------------------------------------------
// If no list is supplied, return ""
// -------------------------------------------------------------------------
	if (sizeof($list_files) == 0) { return ""; }

// -------------------------------------------------------------------------
// Prepare the variables
// -------------------------------------------------------------------------

// Directory
	if ($directory == "/") { $directory = ""; }

	// Convert single quotes from ' to &#039;
	if ($htmltags == "no") { $directory = javascriptEncode2($directory); }
	else                   { $directory = urlEncode2($directory); }

// Filenames
	if ($htmltags == "no") { $encoding = "dirfilename_js"; }
	else                   { $encoding = "dirfilename_url"; }	

// Username
	if ($htmltags == "no") { $username = javascriptEncode2($net2ftp_globals["username"]); }
	else                   { $username = htmlEncode2($net2ftp_globals["username"]); }

// -------------------------------------------------------------------------
// "ftp.t35.com" -----> "http://username"  (username = username.t35.com)
// "ftp.t35.net" -----> "http://username"  (username = username.t35.net)
// -------------------------------------------------------------------------
	if (strpos($net2ftp_globals["ftpserver"], "ftp.t35") !== false) {
		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://" . $username . $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for
	}

// -------------------------------------------------------------------------
// "ftp-www.earthlink.net/webdocs/directory" -----> "http://home.earthlink.net/~username/directory"
// -------------------------------------------------------------------------
	elseif (strpos($net2ftp_globals["ftpserver"], "ftp-www.earthlink.net") !== false) {
		if (strlen($directory) < 8) {
			for ($i=1; $i<=sizeof($list_files); $i++) {
				if ($htmltags == "no") { $list_links[$i] = "javascript:alert('" . __("This file is not accessible from the web") . "');"; }
				else                   { $list_links[$i] = "<a title=\"" . __("This file is not accessible from the web") . "\" onclick=\"alert('" . __("This file is not accessible from the web") . "');\">" . $list_files[$i][$encoding] . "</a>"; }
			} // end for
		}
		else {
			// Transform directory from /webdocs/dir to /dir  --> remove the first 4 characters
			$directory = substr($directory, 8);

			for ($i=1; $i<=sizeof($list_files); $i++) {
				$URL = "http://home.earthlink.net/~" . $username . $directory . "/" . $list_files[$i][$encoding];
				if ($htmltags == "no") { $list_links[$i] = $URL; }
				else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
			} // end for
		} // end if else strlen
	}

// -------------------------------------------------------------------------
// "ftpperso.free.fr" -----> "http://username.free.fr"
// -------------------------------------------------------------------------
	elseif (strpos($net2ftp_globals["ftpserver"], "ftpperso.free.fr") !== false) {
		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://" . $username . ".free.fr" . $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for
	}

// -------------------------------------------------------------------------
// "ftp.membres.lycos.fr" -----> "http://membres.lycos.fr/username"
// -------------------------------------------------------------------------
	elseif (strpos($net2ftp_globals["ftpserver"],"ftp.membres.lycos.fr") !== false) {
		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://membres.lycos.fr/" . $username . $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for
	}

// -------------------------------------------------------------------------
// "home.planetinternet.be" -----> "http://home.planetinternet.be/~username"
// -------------------------------------------------------------------------
	elseif (strpos($net2ftp_globals["ftpserver"], "home.planetinternet.be") !== false) {
		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://home.planetinternet.be/~" . $username . $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for
	}

// -------------------------------------------------------------------------
// "home.planet.nl" -----> "http://home.planet.nl/~username"
// -------------------------------------------------------------------------
	elseif (strpos($net2ftp_globals["ftpserver"], "home.planet.nl") !== false) {
		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://home.planet.nl/~" . $username . $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for
	}

// -------------------------------------------------------------------------
// "users.skynet.be" -----> "http://users.skynet.be/username"
// -------------------------------------------------------------------------
	elseif (strpos($net2ftp_globals["ftpserver"], "users.skynet.be") !== false) {
		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://users.skynet.be/" . $username . $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for
	}

// -------------------------------------------------------------------------
// "ftp.tripod.com" -----> "http://username.tripod.com"
// -------------------------------------------------------------------------
	elseif (strpos($net2ftp_globals["ftpserver"], "ftp.tripod.com") !== false) {
		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://" . $username . ".tripod.com" . $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for
	}

// -------------------------------------------------------------------------
// "ftp.wanadoo.es" -----> "http://perso.wanadoo.es/username"
// -------------------------------------------------------------------------
	elseif (strpos($net2ftp_globals["ftpserver"], "ftp.wanadoo.es") !== false) {
		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://perso.wanadoo.es/" . $username . $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for
	}

// -------------------------------------------------------------------------
// "perso-ftp.wanadoo.fr" -----> "http://perso.wanadoo.fr/username"
// -------------------------------------------------------------------------
	elseif (strpos($net2ftp_globals["ftpserver"], "perso-ftp.wanadoo.fr") !== false) {
		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://perso.wanadoo.fr/" . $username . $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for
	}

// -------------------------------------------------------------------------
// "home.wanadoo.nl" -----> "http://home.wanadoo.nl/username"
// -------------------------------------------------------------------------
	elseif (strpos($net2ftp_globals["ftpserver"], "home.wanadoo.nl") !== false) {
		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://home.wanadoo.nl/" . $username . $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for
	}

// -------------------------------------------------------------------------
// wanadoo uk
// "uploads.webspace.freeserve.net" -----> "http://www.username.freeserve.co.uk"
// -------------------------------------------------------------------------
	elseif (strpos($net2ftp_globals["ftpserver"], "uploads.webspace.freeserve.net") !== false) {
		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://www." . $username . ".freeserve.co.uk" . $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for
	}

// -------------------------------------------------------------------------
// "ftp.xs4all.nl/WWW/directory" -----> "http://www.xs4all.nl/~username/directory"
// -------------------------------------------------------------------------
	elseif (strpos($net2ftp_globals["ftpserver"], "ftp.xs4all.nl") !== false) {
		if (strlen($directory) < 4) {
			for ($i=1; $i<=sizeof($list_files); $i++) {
				if ($htmltags == "no") { $list_links[$i] = "javascript:alert('" . __("This file is not accessible from the web") . "');"; }
				else                   { $list_links[$i] = "<a title=\"" . __("This file is not accessible from the web") . "\" onclick=\"alert('" . __("This file is not accessible from the web") . "');\">" . $list_files[$i][$encoding] . "</a>"; }
			} // end for
		}
		else {
			// Transform directory from /WWW/dir to /dir  --> remove the first 4 characters
			$directory = substr($directory, 4);

			for ($i=1; $i<=sizeof($list_files); $i++) {
				$URL = "http://www.xs4all.nl/~" . $username . $directory . "/" . $list_files[$i][$encoding];
				if ($htmltags == "no") { $list_links[$i] = $URL; }
				else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
			} // end for
		}
	}

// -------------------------------------------------------------------------
// "ftp.server.com/directory/file" -----> "http://www.server.com/directory/file"
// -------------------------------------------------------------------------
	elseif (preg_match("/ftp.(.+)(.{2,4})/", $net2ftp_globals["ftpserver"], $regs)) {

// Check if the FTP directory contains "htdocs", "httpdocs" or "public_html"
// If it does, the HTTP directory root starts from there on
// Example: /srv/www/htdocs/directory1 ==> /directory1

		$specialdirectories[1] = "htdocs";
		$specialdirectories[2] = "httpdocs";
		$specialdirectories[3] = "public_html";

		for ($i=1; $i<=sizeof($specialdirectories); $i++) {
			$pos = strpos($directory, $specialdirectories[$i]);
			if ($pos !== false) { 
				$directory = substr($directory, $pos + strlen($specialdirectories[$i])); 
				break;
			}
		}

// Calculate all the URLs on the Browse screen

		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://www." . $regs[1] . $regs[2] . $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for

	}

// -------------------------------------------------------------------------
// "http://192.168.0.1/directory/file" can be determined using "192.168.0.1/directory/file":
// -------------------------------------------------------------------------
	else {

// Check if the FTP directory contains "htdocs", "httpdocs" or "public_html"
// If it does, the HTTP directory root starts from there on
// Example: /srv/www/htdocs/directory1 ==> /directory1

		$specialdirectories[1] = "htdocs";
		$specialdirectories[2] = "httpdocs";
		$specialdirectories[3] = "public_html";

		for ($i=1; $i<=sizeof($specialdirectories); $i++) {
			$pos = strpos($directory, $specialdirectories[$i]);
			if ($pos !== false) { 
				$directory = substr($directory, $pos + strlen($specialdirectories[$i])); 
				break;
			}
		}

// Calculate all the URLs on the Browse screen
		for ($i=1; $i<=sizeof($list_files); $i++) {
			$URL = "http://" . $net2ftp_globals["ftpserver"]. $directory . "/" . $list_files[$i][$encoding];
			if ($htmltags == "no") { $list_links[$i] = $URL; }
			else                   { $list_links[$i] = "<a href=\"" . $URL . "\" target=\"_blank\" title=\"" . __("Execute %1\$s in a new window", $list_files[$i][$encoding]) . "\">" . $list_files[$i][$encoding] . "</a>"; }
		} // end for
	}

	return $list_links;

} // end function ftp2http

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>