<?php

// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function setStatus($current, $total, $string) {

// --------------
// This function prints the Javascript which will update the status in the top table
// See also the Javascript function setStatus_js defined in the PHP function printJavascriptFunctions.
// --------------

// Sometimes the progress bar does not need updating
	if ($total == 0) { return true; }

// HTML encode the $string
	$string = floor($current/$total*100) . "% " . javascriptEncode2($string);

// Convert $current (5) out of $total (15) to a number between 1 and 10 (5/15 = 33% ==> 3)
	$number = floor($current/$total*10);

	echo "<script type=\"text/javascript\"><!--\n";
	echo "	self.setprogress(\"p_561b57_\",$number,\"$string\",0);\n";
	echo "//--></script>\n";

	flush();

} // End function setStatus

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>