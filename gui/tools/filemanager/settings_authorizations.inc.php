<?php

//   -------------------------------------------------------------------------------
//  |                  net2ftp: a web based FTP client                              |
//  |              Copyright (c) 2003-2009 by David Gartner                         |
//  |                                                                               |
//   -------------------------------------------------------------------------------
//  |                                                                               |
//  |  Enter your settings and preferences below.                                   |
//  |                                                                               |
//  |  The structure of each line is like this:                                     |
//  |     $net2ftp_settings["setting_name"] = "setting_value";                      |
//  |                                                                               |
//  |  BE CAREFUL WHEN EDITING THE FILE: ONLY EDIT THE setting_value, AND DO NOT    |
//  |  ERASE THE " OR THE ; CHARACTERS.                                             |
//   -------------------------------------------------------------------------------


// ----------------------------------------------------------------------------------
// Check the authorizations?
// Set to yes or no.
// ----------------------------------------------------------------------------------
$net2ftp_settings["check_authorization"] = "yes";


// ----------------------------------------------------------------------------------
// Allowed FTP servers
// Either set it to ALL, or else provide a list of allowed servers
// This will automatically change the layout of the login page:
//    - if ALL is entered, then the FTP server input field will be free text
//    - if only 1 entry is entered, then the FTP server input field will not be shown
//    - if more than 1 entry is entered, then the FTP server will have to be chosen from a drop-down list
// ----------------------------------------------------------------------------------

$net2ftp_settings["allowed_ftpservers"][1] = "ALL";
//$net2ftp_settings["allowed_ftpservers"][1] = "localhost";
//$net2ftp_settings["allowed_ftpservers"][2] = "192.168.1.1";
//$net2ftp_settings["allowed_ftpservers"][3] = "ftp.mydomain2.org";


// ----------------------------------------------------------------------------------
// Banned FTP servers
// Set the first entry to NONE, or enter a list of banned servers
// ----------------------------------------------------------------------------------

$net2ftp_settings["banned_ftpservers"][1] = "NONE";
//$net2ftp_settings["banned_ftpservers"][1] = "127.0.0.1";
//$net2ftp_settings["banned_ftpservers"][2] = "192.168.1.2";
//$net2ftp_settings["banned_ftpservers"][3] = "192.168.1.3";


// ----------------------------------------------------------------------------------
// Allowed FTP server port
// Set it either to ALL, or to a fixed number
// ----------------------------------------------------------------------------------

$net2ftp_settings["allowed_ftpserverport"] = "ALL";
//$net2ftp_settings["allowed_ftpserverport"] = "21";


// ----------------------------------------------------------------------------------
// Allowed IP addresses or IP address ranges from which a user can connect
// Set the first entry to ALL, or enter a list of allowed IP addresses
// ----------------------------------------------------------------------------------

$net2ftp_settings["allowed_addresses"][1] = "ALL";
//$net2ftp_settings["allowed_addresses"][1] = "127.0.0.1";   // IP address
//$net2ftp_settings["allowed_addresses"][2] = "192.168.100"; // IP address range
//$net2ftp_settings["allowed_addresses"][3] = "10.0.0.1";


// ----------------------------------------------------------------------------------
// Banned IP addresses or IP address ranges from which a user may not connect
// Set the first entry to NONE, or enter a list of banned IP addresses
// ----------------------------------------------------------------------------------

$net2ftp_settings["banned_addresses"][1] = "NONE";
//$net2ftp_settings["banned_addresses"][1] = "127.0.0.1";
//$net2ftp_settings["banned_addresses"][2] = "192.168.1.2";
//$net2ftp_settings["banned_addresses"][3] = "192.168.1.3";


// ----------------------------------------------------------------------------------
// Banned directory and filename keywords
// Set the first entry to NONE, or enter a list of banned keywords
// ----------------------------------------------------------------------------------

//$net2ftp_settings["banned_keywords"][1] = "NONE";
$net2ftp_settings["banned_keywords"][1] = "paypal";
$net2ftp_settings["banned_keywords"][2] = "ebay";
$net2ftp_settings["banned_keywords"][3] = "wachoviabank";
$net2ftp_settings["banned_keywords"][4] = "wellsfargo";
$net2ftp_settings["banned_keywords"][5] = "bankwest";
$net2ftp_settings["banned_keywords"][6] = "hsbc";
$net2ftp_settings["banned_keywords"][7] = "halifax-online";
$net2ftp_settings["banned_keywords"][8] = "lloydstsb";
$net2ftp_settings["banned_keywords"][9] = "egg.com";

?>