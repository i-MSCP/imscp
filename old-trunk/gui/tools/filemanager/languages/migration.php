<?php

echo "This script is used for net2ftp development... nothing to see here! ;-)";
exit();

// -------------------------------------------------------------------------
// Settings
// -------------------------------------------------------------------------

$languagesArray[] = "ar";
$languagesArray[] = "ar-utf";
$languagesArray[] = "cs";
$languagesArray[] = "da-utf";
$languagesArray[] = "de";
$languagesArray[] = "en";
$languagesArray[] = "en-utf";
$languagesArray[] = "es";
$languagesArray[] = "fr";
$languagesArray[] = "fi";
$languagesArray[] = "he-utf";
$languagesArray[] = "it";
$languagesArray[] = "ja";
$languagesArray[] = "nl";
$languagesArray[] = "pl";
$languagesArray[] = "pt";
$languagesArray[] = "ru";
$languagesArray[] = "sv";
$languagesArray[] = "tc";
$languagesArray[] = "tr";
$languagesArray[] = "ua";
$languagesArray[] = "vi";
$languagesArray[] = "zh"; 

$directory_old = "";
$directory_new = "../languages-new";

$extension = ".inc.php";

// -------------------------------------------------------------------------
// LOOP: for all language files
// -------------------------------------------------------------------------
for ($i=0; $i<sizeof($languagesArray); $i++) {

// ------------------------------------
// Send status
// ------------------------------------
	echo "Language $i - " . $languagesArray[$i] . "<br />\n";
	flush();

// ------------------------------------
// Read the English file and get the lines
// ------------------------------------
	$en_string = local_readfile($directory_old . "en" . $extension);
	$en_lines  = explode_lines($en_string);

// ------------------------------------
// Read the old translated file
// ------------------------------------
	$translated_old_string = local_readfile($directory_old . $languagesArray[$i] . $extension);
	$translated_old_lines  = explode_lines($translated_old_string);

// $en_lines contains:
//    [694] => $net2ftp_messages["Zip"] = "Zip";
//    [695] => $net2ftp_messages["Size"] = "Size";
//    [696] => $net2ftp_messages["Search"] = "Search";

// ------------------------------------
// Initialize the translated string
// ------------------------------------
	$translated_new_string = "";
	$translated_new_lines  = array();

// -------------------------------------------------------------------------
// SUBLOOP: for all lines
// -------------------------------------------------------------------------
	for ($j=1; $j<sizeof($en_lines); $j++) {

// ------------------------------------
// Send status
// ------------------------------------
		if ($j % 20 == 0) { 
			echo "Language $i line $j <br />\n"; 
			flush();
		}

// ------------------------------------
// Real messages
// ------------------------------------
		if (substr($en_lines[$j], 0, 1) == "$") { 

// Find the position of the first = character
			$equalsign_position = strpos($en_lines[$j], "] =");
			if ($equalsign_position === false || $equalsign_position == false) { 
				echo "Language $i, line $j: Equal sign not found or at first position. String is " . $en_lines[$j] . ". Continuing.<br />\n";
				$translated_new_lines[$j] = $en_lines[$j];
				continue;
			}

// Add 3 because we looked for "] =" instead of "="
			else {
				$equalsign_position = $equalsign_position + 3;
			}

// Get the message "$n2f["abd"] ="
			$message = substr($en_lines[$j], 0, $equalsign_position);

// Search for the translation
			$translation = search_translation($message, $translated_old_lines);

// Debugging info
//			echo "Language $i, line $j: message is $message, translation is $translation. <br />\n";

// If the translation is not found, use the English message
			if ($translation == false) { 
				$translated_new_lines[$j] = $en_lines[$j];
			}

// If the translation is found
			else {
				$translated_new_lines[$j] = "$message $translation";
			}

		} // end if

// ------------------------------------
// Comments and control structures
// ------------------------------------
		else { 
				$translated_new_lines[$j] = $en_lines[$j];
		} // end else

	} // end for

// ------------------------------------
// Write the new translated string to a file
// ------------------------------------
//print_r($en_lines);
//print_r($translated_new_lines);

// Glue the array to a string
	$translated_new_string = implode("\n", $translated_new_lines);

// Write the string to a file
	local_writefile($directory_new . "/" . $languagesArray[$i] . $extension, $translated_new_string);

} // end for

// -------------------------------------------------------------------------
// Done
// -------------------------------------------------------------------------

echo "Done. <br />\n";

// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function search_translation($message, $translated_old_lines) {

// --------------
// Search an array for a message
// --------------

// Go over all the lines of the old translated file
	for ($k=0; $k<sizeof($translated_old_lines); $k++) {
		if (substr($translated_old_lines[$k], 0, strlen($message)) == $message) { 
			$toreturn = substr($translated_old_lines[$k], strlen($message)+1);
			return $toreturn;
		}
	}

// If nothing is found, return false
	return false;

} // end search_translation

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function local_readfile($file) {

// --------------
// Open the local file $file and return its content as a string
// --------------

	$handle = fopen($file, "rb"); // Open the file for reading only
	if ($handle == false) { echo "Unable to execute fopen() in local_readfile. Exiting.<br />\n"; exit(); }

	clearstatcache(); // for filesize

	$filesize = filesize($file);
	if ($filesize == 0) { return ""; }

	$string = fread($handle, $filesize);
	if ($string == false && filesize($file)>0) { echo "Unable to execute fread() in local_readfile. Exiting.<br />\n"; exit(); }

	$success3 = fclose($handle);
	if ($success3 == false) { echo "Unable to execute fclose() in local_readfile. Exiting.<br />\n"; exit(); }

	return $string;

} // end local_readfile

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************




// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function local_writefile($file, $string) {

// --------------
// Open the local file $file and write the $string to it
// --------------

	$handle = fopen($file, "wb");
	if ($handle == false) { echo "Unable to execute fopen() in local_writefile. Exiting.<br />\n"; exit(); }

	$success1 = fwrite($handle, $string);
	if ($success1 == false && strlen($string)>0) { echo "Unable to execute fwrite() in local_writefile. Exiting.<br />\n"; exit(); }

	$success2 = fclose($handle);
	if ($success2 == false) { echo "Unable to execute fclose() in local_writefile. Exiting.<br />\n"; exit(); }

} // end local_writefile

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function explode_lines($string) {

// --------------
// Input:  $string which may have Windows or Unix end-of-line characters
// Output: $lines array with the lines
// --------------

//	$string = standardize_eol($string);

// Add a \n in the beginning of the strings so that the first line of the string would
// be in the first element of the exploded array
	$lines  = explode("\n", "\n" . $string);

	return $lines;

} // explode_lines

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>