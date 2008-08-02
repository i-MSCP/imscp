<?php

//   -------------------------------------------------------------------------------
//  |                  net2ftp: a web based FTP client                              |
//  |              Copyright (c) 2003-2008 by David Gartner                         |
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
// Functions on the Browse screen - TOP LEFT
// Indicate which functions are enabled or disabled
// ----------------------------------------------------------------------------------

// Create new directory
$net2ftp_settings["functionuse_newdir"] = "yes";

// Create new file
$net2ftp_settings["functionuse_newfile"] = "yes";

// Upload (upload, upload-and-unzip)
$net2ftp_settings["functionuse_upload"] = "yes";

// Java upload
$net2ftp_settings["functionuse_jupload"] = "yes";

// Flash upload
$net2ftp_settings["functionuse_swfupload"] = "yes";

// Create a website from pre-made HTML templates
$net2ftp_settings["functionuse_easyWebsite"] = "no";

// Bookmark a page
$net2ftp_settings["functionuse_bookmark"] = "yes";

// Install functions
$net2ftp_settings["functionuse_install"] = "no";

// Advanced functions
$net2ftp_settings["functionuse_advanced"] = "yes";


// ----------------------------------------------------------------------------------
// Functions on the Browse screen - TOP RIGHT
// Indicate which functions are enabled or disabled
// ----------------------------------------------------------------------------------

// Copy, move and delete directories and files
$net2ftp_settings["functionuse_copy"]   = "yes";
$net2ftp_settings["functionuse_move"]   = "yes";
$net2ftp_settings["functionuse_delete"] = "yes";

// Rename
$net2ftp_settings["functionuse_rename"] = "yes";

// Chmod
$net2ftp_settings["functionuse_chmod"] = "yes";

// Zip-and-download
$net2ftp_settings["functionuse_downloadzip"] = "yes";

// Unzip
$net2ftp_settings["functionuse_unzip"] = "yes";

// Zip-and-save, zip-and-email
$net2ftp_settings["functionuse_zip"] = "yes";

// Calculate size
$net2ftp_settings["functionuse_calculatesize"] = "yes";

// Find string
$net2ftp_settings["functionuse_findstring"] = "yes";


// ----------------------------------------------------------------------------------
// Functions on the Browse screen - ROW LEVEL
// Indicate which functions are enabled or disabled
// ----------------------------------------------------------------------------------

// Download file
$net2ftp_settings["functionuse_downloadfile"] = "yes";

// View file
$net2ftp_settings["functionuse_view"] = "yes";

// Edit file
$net2ftp_settings["functionuse_edit"] = "yes";

// Update file (beta function)
$net2ftp_settings["functionuse_update"] = "no";

// Open file
$net2ftp_settings["functionuse_open"] = "yes";

?>