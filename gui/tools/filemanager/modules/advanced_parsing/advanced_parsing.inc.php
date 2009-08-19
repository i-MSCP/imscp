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

//	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result;

// Code
//	echo "<script type=\"text/javascript\"><!--\n";	
//	echo "//--></script>\n";

// Include
//	echo "<script type=\"text/javascript\" src=\"". $net2ftp_globals["application_rootdir_url"] . "/modules/advanced_parsing/advanced_parsing.js\"></script>\n";

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
// This function prints the chmod screen
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_settings, $net2ftp_globals, $net2ftp_messages, $net2ftp_result, $net2ftp_output;

// -------------------------------------------------------------------------
// Variables for all screens
// -------------------------------------------------------------------------

// Title
	$title = __("Test the net2ftp list parsing rules");

// Form name, back and forward buttons
	$formname = "AdvancedParsingForm";
	$back_onclick = "document.forms['" . $formname . "'].state.value='advanced';document.forms['" . $formname . "'].screen.value='1';document.forms['" . $formname . "'].submit();";

// Initialize output variable
	$net2ftp_output["advanced_parsing"] = array();

// -------------------------------------------------------------------------
// Open the file with the samples
// -------------------------------------------------------------------------
	require_once($net2ftp_globals["application_rootdir"] . "/modules/advanced_parsing/list_samples.inc.php"); 

// -------------------------------------------------------------------------
// For each sample
// -------------------------------------------------------------------------
	while(list($sampleName, $sampleLines) = each($list_samples)) {
		$net2ftp_output["advanced_parsing"][] = "<span style=\"font-size: 120%; font-weight: bold;\">" . $sampleName . "</span><br />\n";
// ------------------------------------
// Input
// ------------------------------------
		$net2ftp_output["advanced_parsing"][] = "<span style=\"text-decoration: underline;\">Sample input</span>:<br />\n";
		for ($i=1; $i<=sizeof($sampleLines); $i++) {
			 $net2ftp_output["advanced_parsing"][] = "Line $i: " . htmlEncode2($sampleLines[$i]) . "<br />\n";
		}
		$net2ftp_output["advanced_parsing"][] = "<br />\n";

// ------------------------------------
// Output
// ------------------------------------
		$net2ftp_output["advanced_parsing"][] = "<span style=\"text-decoration: underline;\">Parsed output</span>:<br />\n";
		for ($i=1; $i<=sizeof($sampleLines); $i++) {

// Scan the sample
			$outputArray = ftp_scanline("", $sampleLines[$i]);
			while(list($fieldName, $fieldValue) = each($outputArray)) {
				$net2ftp_output["advanced_parsing"][] = "Line $i: " . $fieldName . ": " . htmlEncode2($fieldValue) . "<br />\n";
			} // end while
			$net2ftp_output["advanced_parsing"][] = "<br />\n";
		}
		$net2ftp_output["advanced_parsing"][] = "<br /><br />\n";
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