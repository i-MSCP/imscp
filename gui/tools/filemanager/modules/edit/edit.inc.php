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

	if (isset($_POST["textareaType"]) == true) { $textareaType = validateTextareaType($_POST["textareaType"]); }
	else                                       { $textareaType = ""; }

	if ($textareaType == "" || $textareaType == "plain") {
		echo "<script type=\"text/javascript\"><!--\n";	
		echo "function TabText() {\n";
		echo "	if (event != null) {\n";
		echo "		if (event.srcElement) {\n";
		echo "			if (event.srcElement.value) {\n";
		echo "				if (event.keyCode == 9) {\n";
		echo "					if (document.selection != null) {\n";
		echo "						document.selection.createRange().text = '\\t';\n";
		echo "						event.returnValue = false;\n";
		echo "					}\n";
		echo "					else {\n";
		echo "						event.srcElement.value += '\\t';\n";
		echo "						return false;\n";
		echo "					}\n";
		echo "				}\n";
		echo "			}\n";
		echo "		}\n";
		echo "	}\n";
		echo "}\n";
		echo "//--></script>\n";
	}

// Include
	echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/edit/edit.js\"></script>\n";

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
// This function prints the edit screen
// For screen == 1, the file is read from the FTP server
// For screen == 2, the textarea is changed, the file is not read from the FTP server but comes from the HTML form
// For screen == 3, the file is saved to the FTP server
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;
	if (isset($_POST["textareaType"]) == true)   { $textareaType = validateTextareaType($_POST["textareaType"]); }
	else                                         { $textareaType = ""; }
	if (isset($_POST["text"]) == true)           { $text = $_POST["text"]; }
	else                                         { $text = ""; }
	if (isset($_POST["text_splitted"]) == true)  { $text_splitted = $_POST["text_splitted"]; }
	else                                         { $text_splitted = ""; }
	if (isset($_POST["encodingSelect"]) == true) { $encodingSelect = $_POST["encodingSelect"]; }
	else                                         { $encodingSelect = ""; }	
	if (isset($_POST["breakSelect"]) == true)    { $breakSelect = $_POST["breakSelect"]; }
	else                                         { $breakSelect = ""; }
	$text_encoding_selected = "";
	$line_break_selected    = "";

// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Form name, back and forward buttons
	$formname = "EditForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='browse';document.forms['" . $formname . "'].state2.value='main';document.forms['" . $formname . "'].submit();";

// Language 
	if     ($net2ftp_globals["language"] == "ar") { $fckeditor_language = "ar"; }
	elseif ($net2ftp_globals["language"] == "ar-utf") { $fckeditor_language = "ar"; }
	elseif ($net2ftp_globals["language"] == "cs") { $fckeditor_language = "cs"; }
	elseif ($net2ftp_globals["language"] == "de") { $fckeditor_language = "de"; }
	elseif ($net2ftp_globals["language"] == "es") { $fckeditor_language = "es"; }
	elseif ($net2ftp_globals["language"] == "fr") { $fckeditor_language = "fr"; }
	elseif ($net2ftp_globals["language"] == "it") { $fckeditor_language = "it"; }
	elseif ($net2ftp_globals["language"] == "ja") { $fckeditor_language = "ja"; }
	elseif ($net2ftp_globals["language"] == "nl") { $fckeditor_language = "nl"; }
	elseif ($net2ftp_globals["language"] == "pl") { $fckeditor_language = "pl"; }
	elseif ($net2ftp_globals["language"] == "pt") { $fckeditor_language = "pt"; }
	elseif ($net2ftp_globals["language"] == "ru") { $fckeditor_language = "ru"; }
	elseif ($net2ftp_globals["language"] == "sv") { $fckeditor_language = "sv"; }
	elseif ($net2ftp_globals["language"] == "tc") { $fckeditor_language = "zh"; }
	elseif ($net2ftp_globals["language"] == "tr") { $fckeditor_language = "tr"; }
	elseif ($net2ftp_globals["language"] == "vi") { $fckeditor_language = "vi"; }
	elseif ($net2ftp_globals["language"] == "zh") { $fckeditor_language = "zh-cn"; }
	else                                          { $fckeditor_language = "en"; }

// URL
	$fckeditor_basepath = $net2ftp_globals["application_rootdir_url"] . "/plugins/fckeditor/";

// Directory + file name
	$dirfilename = htmlEncode2(glueDirectories($net2ftp_globals["directory"], $net2ftp_globals["entry"]));

// TextareaSelect onchange
	$onchange = "document.forms['EditForm'].screen.value=2;document.forms['EditForm'].textareaType.value=document.forms['EditForm'].textareaSelect.options[document.forms['EditForm'].textareaSelect.selectedIndex].value;document.forms['EditForm'].submit();";

// Character encoding (requires multibyte string module to be installed)
// With this, you can save a text with specified encoding and line break sequence
// http://www.net2ftp.org/forums/viewtopic.php?id=2449

	if (($net2ftp_globals["language"] == "ja" || $net2ftp_globals["language"] == "tc" || $net2ftp_messages["iso-8859-1"] == "UTF-8") && function_exists("mb_detect_encoding") == true) {

		// $textarea_encodings is an array which contains the possible character encodings
		$textarea_encodings = getTextareaEncodingsArray();

		// $textarea_breaks is an array which contains the possible line breaks
		$textarea_breaks[] = "CRLF";
		$textarea_breaks[] = "CR";
		$textarea_breaks[] = "LF";

		// $text_encoding_old is the original encoding which is detected when the file is first read
		// $text_encoding_new is the requested encoding from the drop-down box
		// Default = encoding used for the page, which is defined by the language file in /languages/xx.inc.php
		// HTML uses BIG5, PHP uses BIG-5 (Traditional Chinese)
		// If the HTML encoding is not foreseen in the PHP function, set it to the default ISO-8859-1
		// $text_encoding is changed further on too
		if($encodingSelect != "" && in_array($encodingSelect, $textarea_encodings)) { $text_encoding_new = $encodingSelect; }
		else { $text_encoding_new = ""; }

		// $line_break_old is the original line break which is detected when the file is first read
		// $line_break is the requested line break from the drop-down box
		if($breakSelect != "" && in_array($breakSelect, $textarea_breaks) == true) { $line_break_new = $breakSelect; }
		else { $line_break_new = "LF"; }

	}

// Programming language (for CodePress syntax highlighting)
	if ($textareaType == "codepress") {
		$filename_extension = get_filename_extension($net2ftp_globals["entry"]);
		if     ($filename_extension == "asp")        { $codepress_programming_language = "asp"; }
		elseif ($filename_extension == "css")        { $codepress_programming_language = "css"; }
		elseif ($filename_extension == "cgi")        { $codepress_programming_language = "perl"; }
		elseif ($filename_extension == "htm")        { $codepress_programming_language = "html"; }
		elseif ($filename_extension == "html")       { $codepress_programming_language = "html"; }
		elseif ($filename_extension == "java")       { $codepress_programming_language = "java"; }
		elseif ($filename_extension == "js")         { $codepress_programming_language = "javascript"; }
		elseif ($filename_extension == "javascript") { $codepress_programming_language = "javascript"; }
		elseif ($filename_extension == "pl")         { $codepress_programming_language = "perl"; }
		elseif ($filename_extension == "perl")       { $codepress_programming_language = "perl"; }
		elseif ($filename_extension == "php")        { $codepress_programming_language = "php"; }
		elseif ($filename_extension == "phps")       { $codepress_programming_language = "php"; }
		elseif ($filename_extension == "phtml")      { $codepress_programming_language = "php"; }
		elseif ($filename_extension == "ruby")       { $codepress_programming_language = "ruby"; }
		elseif ($filename_extension == "sql")        { $codepress_programming_language = "sql"; }
		elseif ($filename_extension == "txt")        { $codepress_programming_language = "text"; }
		else                                         { $codepress_programming_language = "generic"; }
		$codepress_onclick = "text.toggleEditor();";
	}
	else {
		$codepress_programming_language = "";
		$codepress_onclick = "";
	}

// -------------------------------------------------------------------------
// Variables for screen 1
// Read the remote file (edit), or read the local template (new file)
// -------------------------------------------------------------------------
	if ($net2ftp_globals["screen"] == 1) {

// Template file
		$templatefile = $net2ftp_globals["application_rootdir"] . "/modules/edit/template.txt";

// Edit: read the file from the FTP server
		if ($net2ftp_globals["state2"] == "") {
			$text = ftp_readfile("", $net2ftp_globals["directory"], $net2ftp_globals["entry"]);
			if ($net2ftp_result["success"] == false) { return false; }

// Character encoding (requires multibyte string module to be installed)
// Detect the original encoding of the text, and change the encoding of the text to the encoding of the page
			if (($net2ftp_globals["language"] == "ja" || $net2ftp_globals["language"] == "tc" || $net2ftp_messages["iso-8859-1"] == "UTF-8") && function_exists("mb_detect_encoding") == true) {
				// Detect original encoding
				$text_encoding_old = mb_detect_encoding($text, $textarea_encodings);
				$text_encoding_selected = $text_encoding_old;
				// If original encoding is detected and different from the page encoding, convert the text to the page encoding
				if($text_encoding_old != "" && strcasecmp($text_encoding_old, $net2ftp_messages["iso-8859-1"]) != 0) {
					$text = mb_convert_encoding($text, $net2ftp_messages["iso-8859-1"], $text_encoding_old);
				}
				// Detect original line break
				if     (strpos($text, "\r\n") !== false) { $line_break_old = "CRLF"; }
				elseif (strpos($text, "\n") !== false)   { $line_break_old = "LF"; }
				elseif (strpos($text, "\r") !== false)   { $line_break_old = "CR"; }
				else                                     { $line_break_old = "LF"; }
				$line_break_selected = $line_break_old;
			}

		}

// New file: read the template file
		elseif ($net2ftp_globals["state2"] == "newfile") {
			$handle = fopen($templatefile, "r"); // Open the local template file for reading only
			if ($handle == false) { 
				$errormessage = __("Unable to open the template file");
				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false;
			}

			clearstatcache(); // for filesize

			$text = trim(fread($handle, filesize($templatefile)));
			if ($text == false) { 
				$errormessage = __("Unable to read the template file");
				setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
				return false;
			}

			@fclose($handle);
		}

// Save status
		$savestatus = __("Status: This file has not yet been saved");

	}

// -------------------------------------------------------------------------
// Variables for screen 2
// Change the textarea without saving the changes to the FTP server
// -------------------------------------------------------------------------
	elseif ($net2ftp_globals["screen"] == 2) {

// For HTML WYSIWYG editors, split the HTML
		if (($textareaType == "tinymce" || $textareaType == "fckeditor") && $text_splitted == "") {
			$text_splitted = splitHtml($text, $textareaType);
		}
// For other textareas, join the HTML
		elseif (($textareaType == "plain" || $textareaType == "codepress") && $text == "" && isset($text_splitted["top"]) == true) {
			$text  = $text_splitted["top"];
			$text .= $text_splitted["middle"];
			$text .= $text_splitted["bottom"];
		}

// Save status
		$savestatus = __("Status: This file has not yet been saved");
	
	}

// -------------------------------------------------------------------------
// Variables for screen 3
// Save the changes to the FTP server
// -------------------------------------------------------------------------
	elseif ($net2ftp_globals["screen"] == 3) {

// Check if a filename is specified
		if (strlen($net2ftp_globals["entry"])<1) { 
			$errormessage = __("Please specify a filename"); 
			setErrorVars(false, $errormessage, debug_backtrace(), __FILE__, __LINE__);
			return false;
		}

// For HTML WYSIWYG editors, join the HTML
		if ($textareaType == "tinymce" || $textareaType == "fckeditor") {
			$text  = $text_splitted["top"];
			$text .= $text_splitted["middle"];
			$text .= $text_splitted["bottom"];
		}

// $text_file contains the text which is written to the FTP server
// It is equal to the text shown on screen, except if a different character encoding is chosen
	$text_file = $text;

// Character encoding (requires multibyte string module to be installed)
// Change the encoding of the text from the original or page encoding to the selected encoding
		if (($net2ftp_globals["language"] == "ja" || $net2ftp_globals["language"] == "tc" || $net2ftp_messages["iso-8859-1"] == "UTF-8") && function_exists("mb_detect_encoding") == true) {
			$break_map = array("CRLF" => "\r\n", "CR" => "\r", "LF" => "\n");
			if(isset($break_map[$line_break_new]) == true) {
				$text_file = preg_replace('/(\\r\\n)|\\r|\\n/', $break_map[$line_break_new], $text_file);
			}
			if($text_encoding_new != "" && strcasecmp($text_encoding_new, $net2ftp_messages["iso-8859-1"]) != 0) {
				$text_file = mb_convert_encoding($text_file, $text_encoding_new, $net2ftp_messages["iso-8859-1"]);
			}
			$text_encoding_selected = $text_encoding_new;
			$line_break_selected = $line_break_new;
		}

// Write the string to the FTP server
// Note: this function also replaces CarriageReturn+LineFeed by LineFeed
		ftp_writefile("", $net2ftp_globals["directory"], $net2ftp_globals["entry"], $text_file);
		if ($net2ftp_result["success"] == false) { 
			setErrorVars(true, "", "", "", ""); // Continue anyway and print warning message
			$savestatus = __("Status: <b>This file could not be saved</b>"); 
		}
		else { 
			$mytime = mytime();
			$ftpmode = ftpAsciiBinary($net2ftp_globals["entry"]);
			if ($ftpmode == FTP_ASCII)      { $printftpmode = "FTP_ASCII"; }
			elseif ($ftpmode == FTP_BINARY) { $printftpmode = "FTP_BINARY"; }
			$savestatus = __("Status: Saved on <b>%1\$s</b> using mode %2\$s", $mytime, $printftpmode); 
		}

	}


// -------------------------------------------------------------------------
// Convert special characters to HTML entities
// -------------------------------------------------------------------------

// Plain textarea
	if ($textareaType == "" || $textareaType == "plain") {
		$text = htmlspecialchars($text, ENT_QUOTES);
	}

// FCKEditor
	elseif ($textareaType == "fckeditor") {
		$text_splitted["top"] = htmlspecialchars($text_splitted["top"], ENT_QUOTES);
		$text_splitted["bottom"] = htmlspecialchars($text_splitted["bottom"], ENT_QUOTES);
// Do not encode the middle part, this is done by FCKEditor itself
//		$text_splitted["middle"] = htmlspecialchars($text_splitted["middle"], ENT_QUOTES);
	}

// TinyMCE
	elseif ($textareaType == "tinymce") {
		$text_splitted["top"] = htmlspecialchars($text_splitted["top"], ENT_QUOTES);
		$text_splitted["middle"] = htmlspecialchars($text_splitted["middle"], ENT_QUOTES);
		$text_splitted["bottom"] = htmlspecialchars($text_splitted["bottom"], ENT_QUOTES);
	}

// CodePress
	elseif ($textareaType == "codepress") {
		$text = htmlspecialchars($text, ENT_QUOTES);
	}


// -------------------------------------------------------------------------
// Print the output
// -------------------------------------------------------------------------
	require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/edit.template.php");


} // End net2ftp_printBody

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function printTextareaSelect($onchange) {

// --------------
// This function prints a select with the available textareas
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals;
	$pluginProperties = getPluginProperties();
	if (isset($_POST["textareaType"]) == true) { $textareaType = validateTextareaType($_POST["textareaType"]); }
	else                                       { $textareaType = ""; }

	$filename_extension = get_filename_extension($net2ftp_globals["entry"]);

// Convert *htm* to html
	if (strpos($filename_extension, "htm") !== false) { $filename_extension = "html"; }

	echo "<select name=\"textareaSelect\" id=\"textareaSelect\" onchange=\"$onchange\">\n";

	if ($textareaType == "" || $textareaType == "plain") { $plainselected = "selected=\"selected\""; }
	echo "<option value=\"plain\" $plainselected>Normal textarea</option>\n";

	while(list($pluginName, $value) = each($pluginProperties)) {
// Print only the plugins which have 'use' set to yes
//                        which are textareas
//                        which are suitable for this browser
//                        which are suitable for this type of file
		if ($pluginProperties[$pluginName]["use"] == "yes" && $pluginProperties[$pluginName]["type"] == "textarea" && in_array($net2ftp_globals["browser_agent"], $pluginProperties[$pluginName]["browsers"]) == true && in_array($filename_extension, $pluginProperties[$pluginName]["filename_extensions"]) == true) {
			if ($pluginName == $textareaType) { $selected = "selected=\"selected\""; }
			else                              { $selected = ""; }
			echo "<option value=\"$pluginName\" $selected>" . $pluginProperties[$pluginName]["label"] . "</option>\n";
		} // end if
	} // end while

	echo "</select>\n";

} // End function printTextareaSelect

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function printEncodingSelect($text_encoding) {

// --------------
// This function prints a select with the available encodings
// --------------

	global $net2ftp_globals, $net2ftp_messages;

	if (($net2ftp_globals["language"] == "ja" || $net2ftp_globals["language"] == "tc" || $net2ftp_messages["iso-8859-1"] == "UTF-8") && function_exists("mb_detect_encoding") == true) {

		$textarea_encodings = getTextareaEncodingsArray();

		echo "<select name=\"encodingSelect\" id=\"encodingSelect\" style=\"width: 100px;\">\n";
		foreach($textarea_encodings as $value) {
			if(strcasecmp($value, $text_encoding) == 0) { $selected = "selected=\"selected\""; }
			else                                        { $selected = ""; }
			echo "<option value=\"$value\" $selected>$value</option>\n";
		}
		echo "</select>\n";
	
	}

} // End function printEncodingSelect

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function printLineBreakSelect($line_break) {

// --------------
// This function prints a select with the available line-break sequences.
// --------------

	global $net2ftp_messages, $net2ftp_globals;

	if (($net2ftp_globals["language"] == "ja" || $net2ftp_globals["language"] == "tc" || $net2ftp_messages["iso-8859-1"] == "UTF-8") && function_exists("mb_detect_encoding") == true) {

		echo "<select name=\"breakSelect\" id=\"breakSelect\" style=\"width: 60px;\">\n";
		foreach(array("CRLF", "CR", "LF") as $value) {
			if(strcasecmp($value, $line_break) == 0) { $selected = "selected=\"selected\""; }
			else                                     { $selected = ""; }
			echo "<option value=\"$value\" $selected>$value</option>\n";
		}
		echo "</select>\n";

	}

} // End function printLineBreakSelect

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **
function getTextareaEncodingsArray() {

// --------------
// This function returns an array which contains the possible character encodings
// See the "Supported Character Encodings" section at http://www.php.net/manual/en/ref.mbstring.php
// --------------

	global $net2ftp_globals;

	if ($net2ftp_globals["language"] == "ja") {
		$textarea_encodings[] = "UTF-8";
		$textarea_encodings[] = "EUC-JP";
		$textarea_encodings[] = "SJIS";
	}
	elseif ($net2ftp_globals["language"] == "tc" || $net2ftp_globals["language"] == "zh") {
		$textarea_encodings[] = "UTF-8";
		$textarea_encodings[] = "BIG-5";
	}
	else {
// BIG-5 must be before SJIS, otherwise BIG-5 text is incorrectly identified as SJIS
		$textarea_encodings[] = "UTF-8";
		$textarea_encodings[] = "ISO-8859-1";
	}

/*
	$textarea_encodings[] = "ISO-8859-1";
	$textarea_encodings[] = "UCS-4";
	$textarea_encodings[] = "UCS-4BE";
	$textarea_encodings[] = "UCS-4LE";
	$textarea_encodings[] = "UCS-2";
	$textarea_encodings[] = "UCS-2BE";
	$textarea_encodings[] = "UCS-2LE";
	$textarea_encodings[] = "UTF-32";
	$textarea_encodings[] = "UTF-32BE";
	$textarea_encodings[] = "UTF-32LE";
	$textarea_encodings[] = "UTF-16";
	$textarea_encodings[] = "UTF-16BE";
	$textarea_encodings[] = "UTF-16LE";
	$textarea_encodings[] = "UTF-7";
	$textarea_encodings[] = "UTF7-IMAP";
	$textarea_encodings[] = "UTF-8";
	$textarea_encodings[] = "ASCII";
	$textarea_encodings[] = "EUC-JP";
	$textarea_encodings[] = "SJIS";
	$textarea_encodings[] = "eucJP-win";
	$textarea_encodings[] = "SJIS-win";
	$textarea_encodings[] = "ISO-2022-JP";
	$textarea_encodings[] = "JIS";
	$textarea_encodings[] = "ISO-8859-2";
	$textarea_encodings[] = "ISO-8859-3";
	$textarea_encodings[] = "ISO-8859-4";
	$textarea_encodings[] = "ISO-8859-5";
	$textarea_encodings[] = "ISO-8859-6";
	$textarea_encodings[] = "ISO-8859-7";
	$textarea_encodings[] = "ISO-8859-8";
	$textarea_encodings[] = "ISO-8859-9";
	$textarea_encodings[] = "ISO-8859-10";
	$textarea_encodings[] = "ISO-8859-13";
	$textarea_encodings[] = "ISO-8859-14";
	$textarea_encodings[] = "ISO-8859-15";
	$textarea_encodings[] = "byte2be";
	$textarea_encodings[] = "byte2le";
	$textarea_encodings[] = "byte4be";
	$textarea_encodings[] = "byte4le";
	$textarea_encodings[] = "BASE64";
	$textarea_encodings[] = "HTML-ENTITIES";
	$textarea_encodings[] = "7bit";
	$textarea_encodings[] = "8bit";
	$textarea_encodings[] = "EUC-CN";
	$textarea_encodings[] = "CP936";
	$textarea_encodings[] = "HZ";
	$textarea_encodings[] = "EUC-TW";
	$textarea_encodings[] = "CP950";
	$textarea_encodings[] = "EUC-KR";
	$textarea_encodings[] = "UHC (CP949)";
	$textarea_encodings[] = "ISO-2022-KR";
	$textarea_encodings[] = "Windows-1251 (CP1251)";
	$textarea_encodings[] = "Windows-1252 (CP1252)";
	$textarea_encodings[] = "CP866 (IBM866)";
	$textarea_encodings[] = "KOI8-R";
*/

	return $textarea_encodings;

} // End function getTextareaEncodingsArray

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function syntaxTextareaEncode($string) {

// --------------
// Replace tabs, line-feeds and carriage-returns by \t and \n respectively.
// --------------

	$tab = chr(9);
	$lf = chr(10);
	$cr = chr(13);

	$newstring = $string;

// Replace \' and '
	$newstring = str_replace("\'", "\\\'", $newstring);
	$newstring = str_replace("'", "\'", $newstring);

// Replace $cr$lf by $lf
	$newstring = str_replace("$cr$lf", "$lf", $newstring);

// Replace $lf and $tab
	$newstring = str_replace($lf, "\\n", $newstring);
	$newstring = str_replace($tab, "\\t", $newstring);

	return $newstring;

} // end syntaxTextareaEncode

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function splitHtml($text, $textareaType) {

// --------------
// This function reads an HTML file, and returns the head and the body parts.
// --------------

	$pattern = "/(.*?)<body([^\\>]*)\\>(.*)\\<\\/body\\>(.*)/is";
//                 Everything before the <body tag ==> {1}
//                      Start of the body tag <body
//                           Body tag contents ==> {2}
//                                       Body contents ==> {3}
//                                             </body> tag
//                                                        Stuff after the </body> tag ==> {4}

	$preg_match_result = preg_match($pattern, $text, $matches);
	if ($preg_match_result != 0) {
		$text_splitted["top"]    = $matches[1];
		$text_splitted["top"]   .= "<body" . $matches[2] . ">";
		$text_splitted["middle"] = $matches[3];
		$text_splitted["bottom"] = "</body>" . $matches[4];
	}
	else {
		$text_splitted["top"]    = "";
		$text_splitted["middle"] = $text;
		$text_splitted["bottom"] = "";
	}

	return $text_splitted;

} // end splitHtml

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>