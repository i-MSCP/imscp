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

	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;

	if ($net2ftp_globals["state2"] != "") { 

// Get file
		$text = ftp_readfile("", $net2ftp_globals["directory"], $net2ftp_globals["entry"]);
		if ($net2ftp_result["success"] == false)  { return false; }

// Send headers
		header("Content-Type: " . getContentType($net2ftp_globals["entry"]));
		header("Content-Disposition: inline; filename=\"" . $net2ftp_globals["entry"] . "\""); 

// Send file
		echo $text;
		flush();

// Close the connection
		header("Connection: close");

	}

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
//	echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/view/view.js\"></script>\n";

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


// -------------------------------------------------------------------------
// Variables
// -------------------------------------------------------------------------

	$filename_extension = get_filename_extension($net2ftp_globals["entry"]);

// ------------------------
// Set the state2 variable depending on the file extension !!!!!
// ------------------------
	if     (getFileType($net2ftp_globals["entry"]) == "IMAGE") { $filetype = "image"; }
	elseif ($filename_extension == "swf")                      { $filetype = "flash"; }
	else                                                       { $filetype = "text"; }

// Form name, back and forward buttons
	$formname = "ViewForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='browse';document.forms['" . $formname . "'].state2.value='main';document.forms['" . $formname . "'].submit();";

// Next screen
	$nextscreen = 2;

// -------------------------------------------------------------------------
// Text
// -------------------------------------------------------------------------
	if ($filetype == "text") {

// Title
		$title = __("View file %1\$s", $net2ftp_globals["entry"]);


// ------------------------
// geshi_text
// ------------------------
		setStatus(2, 10, __("Reading the file"));
		$geshi_text = ftp_readfile("", $net2ftp_globals["directory"], $net2ftp_globals["entry"]);
		if ($net2ftp_result["success"] == false)  { return false; }

// ------------------------
// geshi_language
// ------------------------
		$geshi_language = "";

		$list_language_extensions = array(
// List the most popular languages first for speed reasons 
			'html4strict' => array('html', 'htm'),
			'javascript'  => array('js'),
			'css'  => array('css'),
			'php'  => array('php', 'php5', 'phtml', 'phps'),
			'perl' => array('pl', 'pm', 'cgi'),
			'sql'  => array('sql'),
			'java' => array('java'),
// Other languages in alphabetic order 
			'actionscript' => array('as'),
			'ada' => array('a', 'ada', 'adb', 'ads'),
			'apache' => array('conf'),
			'asm' => array('ash', 'asm'),
			'asp' => array('asp'),
			'bash' => array('sh'),
			'c' => array('c', 'h'),
			'c_mac' => array('c'),
			'caddcl' => array(),
			'cadlisp' => array(),
			'cpp' => array('cpp'),
			'csharp' => array(),
			'd' => array(''),
			'delphi' => array('dpk'),
			'diff' => array(''),
			'lisp' => array('lisp'),
			'lua' => array('lua'),
			'matlab' => array(),
			'mpasm' => array(),
			'nsis' => array(),
			'objc' => array(),
			'oobas' => array(),
			'oracle8' => array(),
			'pascal' => array('pas'),
			'python' => array('py'),
			'qbasic' => array('bi'),
			'smarty' => array('tpl'),
			'vb' => array('bas'),
			'vbnet' => array(),
			'vhdl' => array(),
			'visualfoxpro' => array(),
			'xml' => array('xml')
		);

		while(list($language, $extensions) = each($list_language_extensions)) {
			if (in_array($filename_extension, $extensions)) {
				$geshi_language = $language;
				break;
			}
		} 

// ------------------------
// geshi_path
// ------------------------
		$geshi_path = NET2FTP_APPLICATION_ROOTDIR . "/plugins/geshi/geshi/";
		
// ------------------------
// Call geshi
// ------------------------
		setStatus(4, 10, __("Parsing the file"));

		$geshi = new GeSHi($geshi_text, $geshi_language, $geshi_path);
		$geshi->set_encoding(__("iso-8859-1"));
		$geshi->set_header_type(GESHI_HEADER_PRE);
		$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 10);
//		$geshi->enable_classes();
		$geshi->set_overall_style('border: 2px solid #d0d0d0; background-color: #f6f6f6; color: #000066; padding: 10px;', true);
		$geshi->set_link_styles(GESHI_LINK, 'color: #000060;');
		$geshi->set_link_styles(GESHI_HOVER, 'background-color: #f0f000;');
		$geshi->set_tab_width(4); 
		$geshi_text = $geshi->parse_code();
	}

// -------------------------------------------------------------------------
// Image
// -------------------------------------------------------------------------
	elseif ($filetype == "image") {
		$title = __("View image %1\$s", htmlEncode2($net2ftp_globals["entry"]));
		$image_url = printPHP_SELF("view");
		$image_alt = __("Image") . $net2ftp_globals["entry"];
	}

// -------------------------------------------------------------------------
// Flash movie
// -------------------------------------------------------------------------
	elseif ($filetype == "flash") {
		$title = __("View Macromedia ShockWave Flash movie %1\$s", htmlEncode2($net2ftp_globals["entry"]));
		$flash_url = printPHP_SELF("view");
	}

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