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

//   -------------------------------------------------------------------------------
//  | For credits, see the credits.txt file                                         |
//   -------------------------------------------------------------------------------
//  |                                                                               |
//  |                              INSTRUCTIONS                                     |
//  |                                                                               |
//  |  The messages to translate are listed below.                                  |
//  |  The structure of each line is like this:                                     |
//  |     $message["Hello world!"] = "Hello world!";                                |
//  |                                                                               |
//  |  Keep the text between square brackets [] as it is.                           |
//  |  Translate the 2nd part, keeping the same punctuation and HTML tags.          |
//  |                                                                               |
//  |  The English message, for example                                             |
//  |     $message["net2ftp is written in PHP!"] = "net2ftp is written in PHP!";    |
//  |  should become after translation:                                             |
//  |     $message["net2ftp is written in PHP!"] = "net2ftp est ecrit en PHP!";     |
//  |     $message["net2ftp is written in PHP!"] = "net2ftp is geschreven in PHP!"; |
//  |                                                                               |
//  |  Note that the variable starts with a dollar sign $, that the value is        |
//  |  enclosed in double quotes " and that the line ends with a semi-colon ;       |
//  |  Be careful when editing this file, do not erase those special characters.    |
//  |                                                                               |
//  |  Some messages also contain one or more variables which start with a percent  |
//  |  sign, for example %1\$s or %2\$s. The English message, for example           |
//  |     $messages[...] = ["The file %1\$s was copied to %2\$s "]                  |
//  |  should becomes after translation:                                            |
//  |     $messages[...] = ["Le fichier %1\$s a été copié vers %2\$s "]             |
//  |                                                                               |
//  |  When a real percent sign % is needed in the text it is entered as %%         |
//  |  otherwise it is interpreted as a variable. So no, it's not a mistake.        |
//  |                                                                               |
//  |  Between the messages to translate there is additional PHP code, for example: |
//  |      if ($net2ftp_globals["state2"] == "rename") {           // <-- PHP code  |
//  |          $net2ftp_messages["Rename file"] = "Rename file";   // <-- message   |
//  |      }                                                       // <-- PHP code  |
//  |  This code is needed to load the messages only when they are actually needed. |
//  |  There is no need to change or delete any of that PHP code; translate only    |
//  |  the message.                                                                 |
//  |                                                                               |
//  |  Thanks in advance to all the translators!                                    |
//  |  David.                                                                       |
//  |                                                                               |
//   -------------------------------------------------------------------------------


// -------------------------------------------------------------------------
// Language settings
// -------------------------------------------------------------------------

// HTML lang attribute
$net2ftp_messages["en"] = "zh-TW";

// HTML dir attribute: left-to-right (LTR) or right-to-left (RTL)
$net2ftp_messages["ltr"] = "ltr";

// CSS style: align left or right (use in combination with LTR or RTL)
$net2ftp_messages["left"] = "left";
$net2ftp_messages["right"] = "right";

// Encoding
$net2ftp_messages["iso-8859-1"] = "big5";


// -------------------------------------------------------------------------
// Status messages
// -------------------------------------------------------------------------

// When translating these messages, keep in mind that the text should not be too long
// It should fit in the status textbox

$net2ftp_messages["Connecting to the FTP server"] = "³s±µ¨ì FTP ¦øªA¾¹";
$net2ftp_messages["Logging into the FTP server"] = "Logging into the FTP server";
$net2ftp_messages["Setting the passive mode"] = "Setting the passive mode";
$net2ftp_messages["Getting the FTP system type"] = "Getting the FTP system type";
$net2ftp_messages["Changing the directory"] = "Changing the directory";
$net2ftp_messages["Getting the current directory"] = "Getting the current directory";
$net2ftp_messages["Getting the list of directories and files"] = "¨ú±o¸ê®Æ§¨¤ÎÀÉ®×¦Cªí...";
$net2ftp_messages["Parsing the list of directories and files"] = "Parsing the list of directories and files";
$net2ftp_messages["Logging out of the FTP server"] = "Logging out of the FTP server";
$net2ftp_messages["Getting the list of directories and files"] = "¨ú±o¸ê®Æ§¨¤ÎÀÉ®×¦Cªí...";
$net2ftp_messages["Printing the list of directories and files"] = "¦C¥X¸ê®Æ§¨¤ÎÀÉ®×¦Cªí...";
$net2ftp_messages["Processing the entries"] = "³B²z¿é¤J¶µ¥Ø...";
$net2ftp_messages["Processing entry %1\$s"] = "Processing entry %1\$s";
$net2ftp_messages["Checking files"] = "ÀË¬dÀÉ®×...";
$net2ftp_messages["Transferring files to the FTP server"] = "¶Ç°e¨ì FTP ¦øªA¾¹...";
$net2ftp_messages["Decompressing archives and transferring files"] = "¸ÑÀ£¤Î¶Ç°eÀÉ®×...";
$net2ftp_messages["Searching the files..."] = "·j¯ÁÀÉ®×...";
$net2ftp_messages["Uploading new file"] = "¤W¶Ç·sÀÉ®×...";
$net2ftp_messages["Reading the file"] = "Reading the file";
$net2ftp_messages["Parsing the file"] = "Parsing the file";
$net2ftp_messages["Reading the new file"] = "Åª¨ú·sÀÉ®×...";
$net2ftp_messages["Reading the old file"] = "Åª¨úÂÂÀÉ®×...";
$net2ftp_messages["Comparing the 2 files"] = "¤ñ¸û 2 ­ÓÀÉ®×...";
$net2ftp_messages["Printing the comparison"] = "¦C¥X¤ñ¸ûµ²ªG...";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "Sending FTP command %1\$s of %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Getting archive %1\$s of %2\$s from the FTP server";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Creating a temporary directory on the FTP server";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Setting the permissions of the temporary directory";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Copying the net2ftp installer script to the FTP server";
$net2ftp_messages["Script finished in %1\$s seconds"] = "¹B¦æ®É¶¡: %1\$s ¬í";
$net2ftp_messages["Script halted"] = "¹B¦æ¤¤¤î";

// Used on various screens
$net2ftp_messages["Please wait..."] = "½Ðµy«J...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Unexpected state string: %1\$s. Exiting.";
$net2ftp_messages["This beta function is not activated on this server."] = "¥»¦øªA¾¹©|¥¼±Ò¥Î¦¹ BETA ¥\¯à.";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "This function has been disabled by the Administrator of this website.";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead.";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "Your root directory <b>%1\$s</b> does not exist or could not be selected.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted.";
$net2ftp_messages["Execute %1\$s in a new window"] = "¦b·sµøµ¡¶}±Ò %1\$s";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "½Ð¿ï¾Ü³Ì¤Ö¤@­Ó¸ê®Æ§¨©ÎÀÉ®×!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "FTP ¦øªA¾¹ <b>%1\$s</b> ¤£¦b¤¹³\³s½u¦Cªí¤¤.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "FTP ¦øªA¾¹ <b>%1\$s</b> ¦b³Q¸T³s½u¦Cªí¤¤.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "FTP ¦øªA¾¹³s±µ°ð %1\$s ¥i¯à¥¼¨Ï¥Î.";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "Your IP address (%1\$s) is not in the list of allowed IP addresses.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "§Aªº IP (%1\$s) ¦b³Q¸T³s½u¦Cªí¤¤.";

// isAuthorizedDirectory()
$net2ftp_messages["Table net2ftp_users contains duplicate rows."] = "Table net2ftp_users contains duplicate rows.";

// logAccess(), logLogin(), logError()
$net2ftp_messages["Unable to execute the SQL query."] = "Unable to execute the SQL query.";

// checkAdminUsernamePassword()
$net2ftp_messages["You did not enter your Administrator username or password."] = "You did not enter your Administrator username or password.";
$net2ftp_messages["Wrong username or password. Please try again."] = "Wrong username or password. Please try again.";


// -------------------------------------------------------------------------
// /includes/consumption.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to determine your IP address."] = "Unable to determine your IP address.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "Table net2ftp_log_consumption_ipaddress contains duplicate rows.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "Table net2ftp_log_consumption_ftpserver contains duplicate rows.";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "The variable <b>consumption_ipaddress_datatransfer</b> is not numeric.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "Table net2ftp_log_consumption_ipaddress could not be updated.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "Table net2ftp_log_consumption_ipaddress contains duplicate entries.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "Table net2ftp_log_consumption_ftpserver could not be updated.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "Table net2ftp_log_consumption_ftpserver contains duplicate entries.";
$net2ftp_messages["Table net2ftp_log_access could not be updated."] = "Table net2ftp_log_access could not be updated.";
$net2ftp_messages["Table net2ftp_log_access contains duplicate entries."] = "Table net2ftp_log_access contains duplicate entries.";


// -------------------------------------------------------------------------
// /includes/database.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php.";
$net2ftp_messages["Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php.";


// -------------------------------------------------------------------------
// /includes/errorhandling.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["An error has occured"] = "¿ù»~";
$net2ftp_messages["Go back"] = "ªð¦^";
$net2ftp_messages["Go to the login page"] = "Âà¨ìµn¤J­¶­±";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "PHP ªº<a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP ¼Ò²Õ</a> ¨Ã¥¼¦w¸Ë.<br /><br /> ½ÐÁpµ¸¦øªA¾¹ºÞ²z­û¥ý¦w¸Ë¦¹¼Ò²Õ. ¸Ô²Ó¦w¸Ë¸ê®Æ¦b <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "¥¼¯à³s±µ FTP ¦øªA¾¹ <b>%1\$s</b>, ³s±µ°ð <b>%2\$s</b>.<br /><br />½Ð½T©w FTP ¦øªA¾¹¦a§}¥¿½T. ¤j³¡¥÷»P HTTP (web) ¦øªA¾¹¤£¦P. ½ÐÁpµ¸¦øªA¾¹ºÞ²z­û±oª¾¸Ô²Ó¸ê®Æ.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "¥¼¯àµn¤J FTP ¦øªA¾¹ <b>%1\$s</b> ¦¹¥Î¤á¦WºÙ <b>%2\$s</b> ¥i¯à¿ù»~.<br /><br />½Ð½T©w¥Î¤á¦WºÙ¤Î±K½X¥¿½T. ½ÐÁpµ¸¦øªA¾¹ºÞ²z­û±oª¾¸Ô²Ó¸ê®Æ.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "µLªk¨Ï¥Î³Q°Ê³s½u¤è¦¡(PASV MODE)³s±µ¨ì <b>%1\$s</b>.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "¥¼¯à³s±µ¥Ø¼Ð FTP ¦øªA¾¹ <b>%1\$s</b>, ³s±µ°ð <b>%2\$s</b>.<br /><br />½Ð½T©w¥Ø¼Ð FTP ¦øªA¾¹¦a§}¥¿½T. ¤j³¡¥÷»P HTTP (web) ¦øªA¾¹¤£¦P. ½ÐÁpµ¸¦øªA¾¹ºÞ²z­û±oª¾¸Ô²Ó¸ê®Æ.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "¥¼¯àµn¤J¥Ø¼Ð FTP ¦øªA¾¹ <b>%1\$s</b> ¦¹¥Î¤á¦WºÙ <b>%2\$s</b> ¥i¯à¿ù»~.<br /><br />½Ð½T©w¥Î¤á¦WºÙ¤Î±K½X¥¿½T. ½ÐÁpµ¸¦øªA¾¹ºÞ²z­û±oª¾¸Ô²Ó¸ê®Æ.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "µLªk¨Ï¥Î³Q°Ê³s½u¤è¦¡(PASV MODE)³s±µ¨ì <b>%1\$s</b>.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "µLªk¦b¸ê®Æ§¨ <b>%2\$s</b> §ó§ï¸ê®Æ§¨ <b>%1\$s</b> ªº¦WºÙ";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "µLªk°õ¦æ©R¥O¦æ <b>%1\$s</b>. ª`·N: ÄÝ©Ê(CHMOD) «ü¥O¥u¾A¥Î©ó Unix FTP ¦øªA¾¹, ¤£¾A¥Î©ó WINDOWS FTP ¦øªA¾¹.";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "¸ê®Æ§¨ <b>%1\$s</b> ¦¨¥\§ó§ïÄÝ©Ê¨ì <b>%2\$s</b>";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "Processing entries within directory <b>%1\$s</b>:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "ÀÉ®× <b>%1\$s</b> ¦¨¥\§ó§ïÄÝ©Ê¨ì <b>%2\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "¥þ³¡¿ï¨úªº¸ê®Æ§¨¤ÎÀÉ®×¤w¸g³B²z.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "µLªk§R°£¸ê®Æ§¨ <b>%1\$s</b>";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Unable to delete the file <b>%1\$s</b>";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "µLªk«Ø¥ß¸ê®Æ§¨ <b>%1\$s</b>";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "µLªk«Ø¥ß¼È¦sÀÉ®×";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "µLªk±q FTP ¦øªA¾¹¨ú±oÀÉ®× <b>%1\$s</b> ¨ÃÀx¦s¬°¼È¦sÀÉ®× <b>%2\$s</b>.<br />½ÐÀË¬d¸ê®Æ§¨ %3\$s ªºÄÝ©Ê .<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "µLªk¶}±Ò¼È¦sÀÉ®×. ½ÐÀË¬d¸ê®Æ§¨ %1\$s ªºÄÝ©Ê.";
$net2ftp_messages["Unable to read the temporary file"] = "µLªkÅª¨ú¼È¦sÀÉ®×";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "µLªkÃö³¬¼È¦sÀÉ®×ªº³B²z";
$net2ftp_messages["Unable to delete the temporary file"] = "µLªk§R°£¼È¦sÀÉ®×";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "µLªk«Ø¥ß¼È¦sÀÉ®×. ½ÐÀË¬d¸ê®Æ§¨ %1\$s ªºÄÝ©Ê.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "µLªk¶}±Ò¼È¦sÀÉ®×. ½ÐÀË¬d¸ê®Æ§¨ %1\$s ªºÄÝ©Ê.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "µLªk¼g¤J¦r¦ê¨ì¼È¦sÀÉ®× <b>%1\$s</b>.<br />½ÐÀË¬d¸ê®Æ§¨ %2\$s ªºÄÝ©Ê.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "µLªkÃö³¬¼È¦sÀÉ®×ªº³B²z";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "µLªk¤W¶ÇÀÉ®×¨ì¸ê®Æ§¨ <b>%1\$s</b>.<br />§A¥i¯à¨S¦³¦¹Åv­­.";
$net2ftp_messages["Unable to delete the temporary file"] = "µLªk§R°£¼È¦sÀÉ®×";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "³B²z¸ê®Æ§¨ <b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "¥Ø¼Ð¸ê®Æ§¨ <b>%1\$s</b> ©M¤l¸ê®Æ§¨©Î­ì¸ê®Æ§¨ <b>%2\$s</b> ¤@¼Ë, ©Ò¥H±N·|¸õ¹L";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The directory <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "µLªk«Ø¥ß¸ê®Æ§¨ <b>%1\$s</b>. ¥i¯à¤w¦s¦b. Ä~Äò½Æ»s/²¾°Ê¾Þ§@...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Created target subdirectory <b>%1\$s</b>";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "The directory <b>%1\$s</b> could not be selected, so this directory will be skipped";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "µLªk§R°£¸ê®Æ§¨ <b>%1\$s</b> - ¥i¯à¸ê®Æ§¨¬°ªÅ";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "¤w§R°£¸ê®Æ§¨ <b>%1\$s</b>";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "¸ê®Æ§¨ <b>%1\$s</b> ¾Þ§@§¹¦¨";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "¥Ø¼Ð¸ê®Æ§¨ <b>%1\$s</b> ©M­ì¸ê®Æ§¨¤@¼Ë, ¦¹ÀÉ®×±N¸õ¹L";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The file <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "The file <b>%1\$s</b> is too big to be copied, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "The file <b>%1\$s</b> is too big to be moved, aborting the move";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "µLªk½Æ»sÀÉ®× <b>%1\$s</b>";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Copied file <b>%1\$s</b>";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Unable to move the file <b>%1\$s</b>, aborting the move";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "¤w²¾°ÊÀÉ®× <b>%1\$s</b>";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Unable to delete the file <b>%1\$s</b>";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "¤w§R°£ÀÉ®× <b>%1\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "¥þ³¡¿ï¨úªº¸ê®Æ§¨¤ÎÀÉ®×¤w¸g³B²z.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "µLªk¨Ï¥Î FTP ¼Ò¦¡ <b>%2\$s</b> ½Æ»s»·ºÝÀÉ®× <b>%1\$s</b> ¨ì¥»¦aÀÉ®×";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "µLªk§R°£ÀÉ®× <b>%1\$s</b>";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "The file is too big to be transferred";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "Daily limit reached: the file <b>%1\$s</b> will not be transferred";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "µLªk¨Ï¥Î FTP ¼Ò¦¡ <b>%2\$s</b> ½Æ»s¥»¦aÀÉ®× <b>%1\$s</b> ¨ì»·ºÝÀÉ®×";
$net2ftp_messages["Unable to delete the local file"] = "µLªk§R°£¥»¦aÀÉ®×";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "µLªk§R°£¼È¦sÀÉ®×";
$net2ftp_messages["Unable to send the file to the browser"] = "Unable to send the file to the browser";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "µLªk«Ø¥ß¼È¦sÀÉ®×";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "À£ÁYÀÉ®× <b>%1\$s</b> ¤w¸g¦b FTP ¦øªA¾¹«Ø¥ß";
$net2ftp_messages["Requested files"] = "³Q½Ð¨DÀÉ®×";

$net2ftp_messages["Dear,"] = "¿Ë·Rªº,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "¦³¤H­n¨D§âªþ¥óÀÉ®×¶Ç°e¨ì email ±b¤á (%1\$s).";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "¦pªG§A¤£¬Û«H³o­Ó¤H©Î¤£ª¾¹DÀÉ®×¤º®e, ¤£­n¶}±ÒªþÀÉ¤Î¥ß§Y§R°£¦¹¶l¥ó.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "ª`·N: ¦pªG§A¨S¦³¶}±ÒªþÀÉ, ¹ï§Aªº¹q¸£¬O¨S¦³·l®`ªº.";
$net2ftp_messages["Information about the sender: "] = "¶Ç°eªÌ¸ê®Æ: ";
$net2ftp_messages["IP address: "] = "IP ¦a§}: ";
$net2ftp_messages["Time of sending: "] = "¶Ç°e®É¶¡: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "¸g ªº net2ftp µ{¦¡¶Ç°e: ";
$net2ftp_messages["Webmaster's email: "] = "¯¸ªø¹q¶l: ";
$net2ftp_messages["Message of the sender: "] = "¶Ç°eªÌªþ¥[°T®§: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "¦¹À£ÁYÀÉ®×¤w¸g¶Ç°e¨ì <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "ÀÉ®× <b>%1\$s</b> ¤Ó¤j. ±N¤£·|³Q¤W¶Ç.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded.";
$net2ftp_messages["Could not generate a temporary file."] = "µLªk²£¥Í¼È¦sÀÉ®×.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "ÀÉ®× <b>%1\$s</b> µLªk²¾°Ê";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "ÀÉ®× <b>%1\$s</b> ¨S¦³°ÝÃD";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "µLªk²¾°Ê¤W¶ÇªºÀÉ®×¨ì¼È¦s¸ê®Æ§¨.<br /><br />¥»ºô¯¸ªººÞ²z­û»Ý­n <b>chmod 777</b> net2ftp ªº ¸ê®Æ§¨ /temp.";
$net2ftp_messages["You did not provide any file to upload."] = "§A¨S¦³´£¨Ñ¥ô¦ó¤W¶ÇªºÀÉ®×.";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "ÀÉ®× <b>%1\$s</b> µLªk¶Ç°e¨ì FTP ¦øªA¾¹";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "ÀÉ®× <b>%1\$s</b> ¤w¸g¦¨¥\¨Ï¥Î FTP mode <b>%2\$s</b> ¤W¶Ç¨ì FTP ¦øªA¾¹. ";
$net2ftp_messages["Transferring files to the FTP server"] = "¶Ç°e¨ì FTP ¦øªA¾¹...";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "³B²zÀ£ÁYÀÉ®× nr %1\$s: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "¥Ñ©ó°ÆÀÉ¦W¤£©ú, À£ÁYÀÉ®× <b>%1\$s</b> ¥¼¯à³B²z. ²{¶¥¬q¥u±µ¨ü zip, tar, tgz and gz ®æ¦¡.";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Unable to extract the files and directories from the archive";
$net2ftp_messages["Created directory %1\$s"] = "Created directory %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "Could not create directory %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "Copied file %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "Could not copy file %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "Unable to delete the temporary directory";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Unable to delete the temporary file %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "µLªk°õ¦æ©R¥O¦æ <b>%1\$s</b>";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "¤u§@°±¤î";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "¤u§@®É¶¡¶W¹L¤F­­©wªº %1\$s ¬í, ©Ò¥H¤u§@°±¤î.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "¦¹­­¨îºûÅ@¦U¨Ï¥ÎªÌªº¤½¥­­ì«h.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "¹Á¸Õ§â¤u§@©î¤À¬°§ó¤pªº¤u§@: ´î¤Ö¿ï¨úªºÀÉ®×, ©Î¬Ù²¤Åé¿n¤ñ¸û¤jªºÀÉ®×.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "¦pªG§A§Æ±æ net2ftp ¥i³B²z¤j«¬¤u§@, ¥i§â net2ftp ¦w¸Ë¨ì§Aªº¹q¸£.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "§A¨S¦³¦b email ´£¨Ñ¥ô¦ó¤å¦r!";
$net2ftp_messages["You did not supply a From address."] = "§A¨S¦³´£¨Ñ From ¦a§}.";
$net2ftp_messages["You did not supply a To address."] = "§A¨S¦³´£¨Ñ To ¦a§}.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "¥Ñ©ó§Þ³N©Ê°ÝÃD, ¹q¶l <b>%1\$s</b> µLªk°e¥X.";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "Please enter your username and password for FTP server ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below.";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page.";
$net2ftp_messages["Please enter your Admin username and password"] = "Please enter your Admin username and password"; 
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below.";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php.";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "ÂÅ¦â¼Òª©";
$net2ftp_messages["Grey"] = "¦Ç¦â¼Òª©";
$net2ftp_messages["Black"] = "¶Â¦â¼Òª©";
$net2ftp_messages["Yellow"] = "¶À¦â¼Òª©";
$net2ftp_messages["Pastel"] = "Pastel";

// getMime()
$net2ftp_messages["Directory"] = "¸ê®Æ§¨";
$net2ftp_messages["Symlink"] = "Symlink";
$net2ftp_messages["ASP script"] = "ASP ÀÉ®×";
$net2ftp_messages["Cascading Style Sheet"] = "CSS ½d¥»ÀÉ®×";
$net2ftp_messages["HTML file"] = "HTML ÀÉ®×";
$net2ftp_messages["Java source file"] = "Java source ÀÉ®×";
$net2ftp_messages["JavaScript file"] = "JavaScript ÀÉ®×";
$net2ftp_messages["PHP Source"] = "PHP source ÀÉ®×";
$net2ftp_messages["PHP script"] = "PHP script ÀÉ®×";
$net2ftp_messages["Text file"] = "¤å¦rÀÉ®×";
$net2ftp_messages["Bitmap file"] = "Bitmap ¹Ï¤ùÀÉ";
$net2ftp_messages["GIF file"] = "GIF ¹Ï¤ùÀÉ";
$net2ftp_messages["JPEG file"] = "JPEG ¹Ï¤ùÀÉ";
$net2ftp_messages["PNG file"] = "PNG ¹Ï¤ùÀÉ";
$net2ftp_messages["TIF file"] = "TIF ¹Ï¤ùÀÉ";
$net2ftp_messages["GIMP file"] = "GIMP ¹Ï¤ùÀÉ";
$net2ftp_messages["Executable"] = "¥i°õ¦æÀÉ®×";
$net2ftp_messages["Shell script"] = "Shell script";
$net2ftp_messages["MS Office - Word document"] = "MS Office - Word ¤å¥ó";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - Excel ªí®æ";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - PowerPoint ¤å¥ó";
$net2ftp_messages["MS Office - Access database"] = "MS Office - Access ¼Æ¾Ú®w";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - Visio drawing";
$net2ftp_messages["MS Office - Project file"] = "MS Office - Project ¤å¥ó";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - Writer 6.0 ¤å¥ó";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - Writer 6.0 ¼Òª©¤å¥ó";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - Calc 6.0 ªí®æ";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - Calc 6.0 ¼Òª©¤å¥ó";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - Draw 6.0 ¤å¥ó";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - Draw 6.0 ¼Òª©¤å¥ó";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - Impress 6.0 ¤å¥ó";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - Impress 6.0 ¼Òª©¤å¥ó";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - Writer 6.0 global ¤å¥ó";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - Math 6.0 ¤å¥ó";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - StarWriter 5.x ¤å¥ó";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - StarWriter 5.x global ¤å¥ó";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - StarCalc 5.x ªí®æ";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - StarDraw 5.x ¤å¥ó";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - StarImpress 5.x ¤å¥ó";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - StarImpress Packed 5.x ÀÉ®×";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - StarMath 5.x ¤å¥ó";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - StarChart 5.x ¤å¥ó";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - StarMail 5.x mail ÀÉ®×";
$net2ftp_messages["Adobe Acrobat document"] = "Adobe Acrobat ¤å¥ó";
$net2ftp_messages["ARC archive"] = "ARC À£ÁYÀÉ";
$net2ftp_messages["ARJ archive"] = "ARJ À£ÁYÀÉ";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "GZ À£ÁYÀÉ";
$net2ftp_messages["TAR archive"] = "TAR À£ÁYÀÉ";
$net2ftp_messages["Zip archive"] = "Zip À£ÁYÀÉ";
$net2ftp_messages["MOV movie file"] = "MOV ¼v¹³ÀÉ";
$net2ftp_messages["MPEG movie file"] = "MPEG ¼v¹³ÀÉ";
$net2ftp_messages["Real movie file"] = "Real ¼v¹³ÀÉ";
$net2ftp_messages["Quicktime movie file"] = "Quicktime ¼v¹³ÀÉ";
$net2ftp_messages["Shockwave flash file"] = "Shockwave flash ÀÉ®×";
$net2ftp_messages["Shockwave file"] = "Shockwave ÀÉ®×";
$net2ftp_messages["WAV sound file"] = "WAV ­µ®ÄÀÉ";
$net2ftp_messages["Font file"] = "Font ÀÉ®×";
$net2ftp_messages["%1\$s File"] = "%1\$s ÀÉ®×";
$net2ftp_messages["File"] = "ÀÉ®×";

// getAction()
$net2ftp_messages["Back"] = "Back";
$net2ftp_messages["Submit"] = "Submit";
$net2ftp_messages["Refresh"] = "Refresh";
$net2ftp_messages["Details"] = "Details";
$net2ftp_messages["Icons"] = "Icons";
$net2ftp_messages["List"] = "List";
$net2ftp_messages["Logout"] = "Logout";
$net2ftp_messages["Help"] = "Help";
$net2ftp_messages["Bookmark"] = "Bookmark";
$net2ftp_messages["Save"] = "Save";
$net2ftp_messages["Default"] = "Default";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Help Guide";
$net2ftp_messages["Forums"] = "Forums";
$net2ftp_messages["License"] = "License";
$net2ftp_messages["Powered by"] = "Powered by";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions.";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "Admin functions";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "Version information";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "This version of net2ftp is up-to-date.";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server.";
$net2ftp_messages["Logging"] = "Logging";
$net2ftp_messages["Date from:"] = "Date from:";
$net2ftp_messages["to:"] = "to:";
$net2ftp_messages["Empty logs"] = "Empty";
$net2ftp_messages["View logs"] = "View logs";
$net2ftp_messages["Go"] = "Go";
$net2ftp_messages["Setup MySQL tables"] = "Setup MySQL tables";
$net2ftp_messages["Create the MySQL database tables"] = "Create the MySQL database tables";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "Admin functions";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "The handle of file %1\$s could not be opened.";
$net2ftp_messages["The file %1\$s could not be opened."] = "The file %1\$s could not be opened.";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "The handle of file %1\$s could not be closed.";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered.";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "Unable to select the database <b>%1\$s</b>.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "The SQL query nr <b>%1\$s</b> could not be executed.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "The SQL query nr <b>%1\$s</b> was executed successfully.";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "Please enter your MySQL settings:";
$net2ftp_messages["MySQL username"] = "MySQL username";
$net2ftp_messages["MySQL password"] = "MySQL password";
$net2ftp_messages["MySQL database"] = "MySQL database";
$net2ftp_messages["MySQL server"] = "MySQL server";
$net2ftp_messages["This SQL query is going to be executed:"] = "This SQL query is going to be executed:";
$net2ftp_messages["Execute"] = "°õ¦æ";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "Settings used:";
$net2ftp_messages["MySQL password length"] = "MySQL password length";
$net2ftp_messages["Results:"] = "Results:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "Admin functions";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "Unable to execute the SQL query <b>%1\$s</b>.";
$net2ftp_messages["No data"] = "No data";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "Admin functions";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "The table <b>%1\$s</b> was emptied successfully.";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "The table <b>%1\$s</b> could not be emptied.";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "The table <b>%1\$s</b> was optimized successfully.";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "The table <b>%1\$s</b> could not be optimized.";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "¶i¶¥¥\¯à";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "Go";
$net2ftp_messages["Disabled"] = "Disabled";
$net2ftp_messages["Advanced FTP functions"] = "Advanced FTP functions";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "Send arbitrary FTP commands to the FTP server";
$net2ftp_messages["This function is available on PHP 5 only"] = "This function is available on PHP 5 only";
$net2ftp_messages["Troubleshooting functions"] = "Troubleshooting functions";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "¦øªA¾¹ªº net2ftp ºÃÃø±Æ¸Ñ";
$net2ftp_messages["Troubleshoot an FTP server"] = "FTP ¦øªA¾¹ºÃÃø±Æ¸Ñ";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Test the net2ftp list parsing rules";
$net2ftp_messages["Translation functions"] = "Translation functions";
$net2ftp_messages["Introduction to the translation functions"] = "Introduction to the translation functions";
$net2ftp_messages["Extract messages to translate from code files"] = "Extract messages to translate from code files";
$net2ftp_messages["Check if there are new or obsolete messages"] = "Check if there are new or obsolete messages";

$net2ftp_messages["Beta functions"] = "Beta functions";
$net2ftp_messages["Send a site command to the FTP server"] = "Send a site command to the FTP server";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: password-protect a directory, create custom error pages";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: execute an SQL query";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "¦øªA¾¹¤£¤ä´©¹B¦æ¥\¯à.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "¦øªA¾¹¤£¤ä´©¦¹ Apache ¥\¯à.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "¦øªA¾¹¤£¤ä´©¦¹ MYSQL ¥\¯à.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "¤£¥i¹w­pªºª¬ºA 2 ¦r¦ê. µ{¦¡µ²§ô¤¤...";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "FTP ¦øªA¾¹ºÃÃø±Æ¸Ñ";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "³s½u³]©w:";
$net2ftp_messages["FTP server"] = "FTP ¦øªA¾¹";
$net2ftp_messages["FTP server port"] = "FTP ¦øªA¾¹³s±µ°ð";
$net2ftp_messages["Username"] = "¥Î¤á¦WºÙ";
$net2ftp_messages["Password"] = "¥Î¤á±K½X";
$net2ftp_messages["Password length"] = "±K½Xªø«×";
$net2ftp_messages["Passive mode"] = "³Q°Ê³s½u(Passive mode)";
$net2ftp_messages["Directory"] = "¸ê®Æ§¨";
$net2ftp_messages["Printing the result"] = "Printing the result";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "³s±µ FTP ¦øªA¾¹: ";
$net2ftp_messages["Logging into the FTP server: "] = "µn¤J¨ì FTP ¦øªA¾¹: ";
$net2ftp_messages["Setting the passive mode: "] = "³]©w³Q°Ê³s±µ¼Ò¦¡(PASV):";
$net2ftp_messages["Getting the FTP server system type: "] = "Getting the FTP server system type: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "ÅÜ§ó¸ê®Æ§¨¨ì %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "FTP ¦øªA¾¹ªº¸ê®Æ§¨¬O: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "¨ú±o­ì©l¸ê®Æ§¨¤ÎÀÉ®×¦Cªí: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "­«¸Õ¨ú±o­ì©l¸ê®Æ§¨¤ÎÀÉ®×¦Cªí: ";
$net2ftp_messages["Closing the connection: "] = "Ãö³¬³s½u: ";
$net2ftp_messages["Raw list of directories and files:"] = "­ì©l¸ê®Æ§¨¤ÎÀÉ®×¦Cªí:";
$net2ftp_messages["Parsed list of directories and files:"] = "¤ÀªR¸ê®Æ§¨¤ÎÀÉ®×¦Cªí:";

$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "not OK";

} // end advanced_ftpserver


// -------------------------------------------------------------------------
// Advanced_parsing module
if ($net2ftp_globals["state"] == "advanced_parsing") {
// -------------------------------------------------------------------------

$net2ftp_messages["Test the net2ftp list parsing rules"] = "Test the net2ftp list parsing rules";
$net2ftp_messages["Sample input"] = "Sample input";
$net2ftp_messages["Parsed output"] = "Parsed output";

} // end advanced_parsing


// -------------------------------------------------------------------------
// Advanced_webserver module
if ($net2ftp_globals["state"] == "advanced_webserver") {
// -------------------------------------------------------------------------

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "net2ftp ¦w¸Ëµ{¦¡ºÃÃø±Æ¸Ñ";
$net2ftp_messages["Printing the result"] = "Printing the result";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "ÀË¬d¬O§_¥¿½T¦w¸Ë¤F PHP ªº FTP ¼Ò²Õ: ";
$net2ftp_messages["yes"] = "¬O";
$net2ftp_messages["no - please install it!"] = "§_ - ½Ð¥ý¦w¸Ë!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "ÀË¬d¸ê®Æ§¨ªºÄÝ©Ê: ¤@­Ó¤pÀÉ®×·|¼g¤J /TEMP ¸ê®Æ§¨µM«á§R°£.";
$net2ftp_messages["Creating filename: "] = "«Ø¥ßÀÉ®×¦WºÙ: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "¦¨¥\\. ÀÉ®×¦WºÙ: %tempfilename";
$net2ftp_messages["not OK"] = "not OK";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "¥¢±Ñ. ½ÐÀË¬d %1\$s ¸ê®Æ§¨ªºÄÝ©Ê";
$net2ftp_messages["Opening the file in write mode: "] = "Opening the file in write mode: ";
$net2ftp_messages["Writing some text to the file: "] = "¼g¤J¤å¦r¨ì: ";
$net2ftp_messages["Closing the file: "] = "Ãö³¬ÀÉ®×: ";
$net2ftp_messages["Deleting the file: "] = "§R°£ÀÉ®×: ";

$net2ftp_messages["Testing the FTP functions"] = "Testing the FTP functions";
$net2ftp_messages["Connecting to a test FTP server: "] = "Connecting to a test FTP server: ";
$net2ftp_messages["Connecting to the FTP server: "] = "³s±µ FTP ¦øªA¾¹: ";
$net2ftp_messages["Logging into the FTP server: "] = "µn¤J¨ì FTP ¦øªA¾¹: ";
$net2ftp_messages["Setting the passive mode: "] = "³]©w³Q°Ê³s±µ¼Ò¦¡(PASV):";
$net2ftp_messages["Getting the FTP server system type: "] = "Getting the FTP server system type: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "ÅÜ§ó¸ê®Æ§¨¨ì %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "FTP ¦øªA¾¹ªº¸ê®Æ§¨¬O: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "¨ú±o­ì©l¸ê®Æ§¨¤ÎÀÉ®×¦Cªí: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "­«¸Õ¨ú±o­ì©l¸ê®Æ§¨¤ÎÀÉ®×¦Cªí: ";
$net2ftp_messages["Closing the connection: "] = "Ãö³¬³s½u: ";
$net2ftp_messages["Raw list of directories and files:"] = "­ì©l¸ê®Æ§¨¤ÎÀÉ®×¦Cªí:";
$net2ftp_messages["Parsed list of directories and files:"] = "¤ÀªR¸ê®Æ§¨¤ÎÀÉ®×¦Cªí:";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "not OK";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "¥[¤J§Úªº³Ì·R:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: ¦b³sµ²¤W«ö·Æ¹«¥kÁä¨Ã¿ï¾Ü \"¥[¨ì§Úªº³Ì·R...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: ¦b³sµ²¤W«ö·Æ¹«¥kÁä¨Ã¿ï¾Ü \"Bookmark This Link...\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "ª`·N: ·í§A¨Ï¥Î¦¹®ÑÅÒ®É, ±N·|¦³¤@­Ó¼u¥Xµøµ¡¸ß°Ý§Aªº±K½X.";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "½Ð¿ï¾Ü¸ê®Æ§¨";
$net2ftp_messages["Please wait..."] = "½Ðµy«J...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "¸ê®Æ§¨¦WºÙ¥]§t \' ±NµLªk¥¿½TÅã¥Ü. ¥u¥i§R°£. ½Ðªð¦^¿ï¨ú¥t¤@­Ó¤l¸ê®Æ§¨.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "Daily limit reached: you will not be able to transfer data";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "If you need unlimited usage, please install net2ftp on your own web server.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "¥[¤J·s¸ê®Æ§¨";
$net2ftp_messages["New file"] = "¥[¤J·sÀÉ®×";
$net2ftp_messages["HTML templates"] = "HTML templates";
$net2ftp_messages["Upload"] = "¤W¶ÇÀÉ®×";
$net2ftp_messages["Java Upload"] = "Java ¤W¶ÇÀÉ®×";
$net2ftp_messages["Flash Upload"] = "Flash Upload";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Advanced"] = "¶i¶¥¿ï¶µ";
$net2ftp_messages["Copy"] = "½Æ»s";
$net2ftp_messages["Move"] = "²¾°Ê";
$net2ftp_messages["Delete"] = "§R°£";
$net2ftp_messages["Rename"] = "­«·s©R¦W";
$net2ftp_messages["Chmod"] = "ÄÝ©Ê(CHOMD)";
$net2ftp_messages["Download"] = "¤U¸ü";
$net2ftp_messages["Unzip"] = "Unzip";
$net2ftp_messages["Zip"] = "À£ÁY";
$net2ftp_messages["Size"] = "­pºâ¤j¤p";
$net2ftp_messages["Search"] = "·j¯Á";
$net2ftp_messages["Go to the parent directory"] = "Go to the parent directory";
$net2ftp_messages["Go"] = "Go";
$net2ftp_messages["Transform selected entries: "] = "¾Þ§@¿ï¶µ: ";
$net2ftp_messages["Transform selected entry: "] = "Transform selected entry: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "¦b %1\$s «Ø¥ß¤@­Ó·s¤l¸ê®Æ§¨";
$net2ftp_messages["Create a new file in directory %1\$s"] = "¦b %1\$s ¸ê®Æ§¨«Ø¥ß¤@­Ó·sÀÉ®×";
$net2ftp_messages["Create a website easily using ready-made templates"] = "Create a website easily using ready-made templates";
$net2ftp_messages["Upload new files in directory %1\$s"] = "¦b %1\$s ¸ê®Æ§¨¤W¶Ç·sÀÉ®×";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Upload directories and files using a Java applet";
$net2ftp_messages["Upload files using a Flash applet"] = "Upload files using a Flash applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Install software packages (requires PHP on web server)";
$net2ftp_messages["Go to the advanced functions"] = "¨Ï¥Î¶i¶¥¥\¯à";
$net2ftp_messages["Copy the selected entries"] = "½Æ»s¿ï¨úªº¸ê®Æ§¨©ÎÀÉ®×";
$net2ftp_messages["Move the selected entries"] = "²¾°Ê¿ï¨úªº¸ê®Æ§¨©ÎÀÉ®×";
$net2ftp_messages["Delete the selected entries"] = "§R°£¿ï¨úªº¸ê®Æ§¨©ÎÀÉ®×";
$net2ftp_messages["Rename the selected entries"] = "­«·s©R¦W¿ï¨úªº¸ê®Æ§¨©ÎÀÉ®×";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "§ïÅÜ¿ï¨úªº¸ê®Æ§¨©ÎÀÉ®×ªºÄÝ©Ê (¥u¾A¥Î©ó Unix/Linux/BSD ¦øªA¾¹)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "À£ÁY¤U¸ü¿ï¨úªº¸ê®Æ§¨©ÎÀÉ®×";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Unzip the selected archives on the FTP server";
$net2ftp_messages["Zip the selected entries to save or email them"] = "À£ÁY¿ï¨úªº¸ê®Æ§¨©ÎÀÉ®×§@Àx¦s©Î¶Ç°e¶l¥ó";
$net2ftp_messages["Calculate the size of the selected entries"] = "­pºâ¿ï¨úªº¸ê®Æ§¨©ÎÀÉ®×ªº¤j¤p";
$net2ftp_messages["Find files which contain a particular word"] = "¨Ï¥ÎÃöÁä¦r·j¯ÁÀÉ®×";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "%1\$s ­°§Ç±Æ¦C";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "%1\$s ¤É§Ç±Æ¦C";
$net2ftp_messages["Ascending order"] = "¤É§Ç±Æ¦C";
$net2ftp_messages["Descending order"] = "­°§Ç±Æ¦C";
$net2ftp_messages["Upload files"] = "Upload files";
$net2ftp_messages["Up"] = "¤W¯Å¸ê®Æ§¨";
$net2ftp_messages["Click to check or uncheck all rows"] = "Click to check or uncheck all rows";
$net2ftp_messages["All"] = "All";
$net2ftp_messages["Name"] = "¦WºÙ";
$net2ftp_messages["Type"] = "Ãþ«¬";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "¾Ö¦³ªÌ";
$net2ftp_messages["Group"] = "¸s²Õ";
$net2ftp_messages["Perms"] = "ÄÝ©Ê";
$net2ftp_messages["Mod Time"] = "³Ì«á­×§ï®É¶¡";
$net2ftp_messages["Actions"] = "°Ê§@";
$net2ftp_messages["Select the directory %1\$s"] = "Select the directory %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "Select the file %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "Select the symlink %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "Go to the subdirectory %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "Download the file %1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "Follow symlink %1\$s";
$net2ftp_messages["View"] = "¬d¬Ý";
$net2ftp_messages["Edit"] = "½s¿è";
$net2ftp_messages["Update"] = "§ó·s";
$net2ftp_messages["Open"] = "¶}±Ò";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "°ª«GÅã¥Ü %1\$s ÀÉ®×ªº­ì©l½X";
$net2ftp_messages["Edit the source code of file %1\$s"] = "½s¿è %1\$s ÀÉ®×ªº­ì©l½X";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "¤W¶Ç§ó·sª©¥»ªº %1\$s ¤Î¦X¨Ö§ó§ï";
$net2ftp_messages["View image %1\$s"] = "¬d¬Ý¹Ï¤ù %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "±q HTTP ¦øªA¾¹¬d¬ÝÀÉ®× %1\$s";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(ª`·N: ¦pªG§A¨S¦³¦Û¤vªº°ì¦W, ¦¹³sµ²¥i¯à¿ù»~.)";
$net2ftp_messages["This folder is empty"] = "¦¹¸ê®Æ§¨¬OªÅªº";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "¸ê®Æ§¨";
$net2ftp_messages["Files"] = "ÀÉ®×";
$net2ftp_messages["Symlinks"] = "³sµ²";
$net2ftp_messages["Unrecognized FTP output"] = "µLªk½T»{ªº FTP ¿é¥X";
$net2ftp_messages["Number"] = "Number";
$net2ftp_messages["Size"] = "­pºâ¤j¤p";
$net2ftp_messages["Skipped"] = "Skipped";
$net2ftp_messages["Data transferred from this IP address today"] = "Data transferred from this IP address today";
$net2ftp_messages["Data transferred to this FTP server today"] = "Data transferred to this FTP server today";

// printLocationActions()
$net2ftp_messages["Language:"] = "»y¨¥:";
$net2ftp_messages["Skin:"] = "¼Òª©:";
$net2ftp_messages["View mode:"] = "ª©­±¼Ò¦¡:";
$net2ftp_messages["Directory Tree"] = "¸ê®Æ§¨¦ì¸m";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "¦b·sµøµ¡¶}±Ò %1\$s";
$net2ftp_messages["This file is not accessible from the web"] = "This file is not accessible from the web";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "Âù«ö·Æ¹«¶i¤J¤l¸ê®Æ§¨:";
$net2ftp_messages["Choose"] = "¿ï¨ú";
$net2ftp_messages["Up"] = "¤W¯Å¸ê®Æ§¨";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "¿ï¨ú¸ê®Æ§¨¤ÎÀÉ®×ªº¤j¤p";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "¿ï¨ú¸ê®Æ§¨¤ÎÀÉ®×ªºÁ`¤j¤p¬°:";
$net2ftp_messages["The number of files which were skipped is:"] = "The number of files which were skipped is:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "§ó§ï¸ê®Æ§¨¤ÎÀÉ®×ªºÄÝ©Ê";
$net2ftp_messages["Set all permissions"] = "©Ò¦³Åv­­";
$net2ftp_messages["Read"] = "Åª¨ú";
$net2ftp_messages["Write"] = "¼g¤J";
$net2ftp_messages["Execute"] = "°õ¦æ";
$net2ftp_messages["Owner"] = "¾Ö¦³ªÌ";
$net2ftp_messages["Group"] = "¸s²Õ";
$net2ftp_messages["Everyone"] = "©Ò¦³¤H";
$net2ftp_messages["To set all permissions to the same values, enter those permissions above and click on the button \"Set all permissions\""] = "¦p­n³]©w©Ò¦³Åv­­¤@¼Ë, ¦b¤W­±¿é¤JÅv­­¨Ã¿ï \"©Ò¦³Åv­­\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "³]©w¸ê®Æ§¨ <b>%1\$s</b> ªºÄÝ©Ê¬°: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "³]©wÀÉ®× <b>%1\$s</b> ªºÄÝ©Ê¬°: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "³]©w symlink <b>%1\$s</b> ªºÄÝ©Ê¬°: ";
$net2ftp_messages["Chmod value"] = "Chmod ¼Æ­È";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "¨Ã³]©w¦¹¸ê®Æ§¨¤º§tªº©Ò¦³¤l¸ê®Æ§¨";
$net2ftp_messages["Chmod also the files within this directory"] = "¨Ã³]©w¦¹¸ê®Æ§¨¤º§tªº©Ò¦³ÀÉ®×";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "ÄÝ©Ê¼Æ­È <b>%1\$s</b> ¥²¶·¦b 0-777 ¤§¶¡.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "½Ð¿ï¾Ü¸ê®Æ§¨";
$net2ftp_messages["Copy directories and files"] = "½Æ»s¸ê®Æ§¨¤ÎÀÉ®×";
$net2ftp_messages["Move directories and files"] = "²¾°Ê¸ê®Æ§¨¤ÎÀÉ®×";
$net2ftp_messages["Delete directories and files"] = "§R°£¸ê®Æ§¨¤ÎÀÉ®×";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "¬O§_½T©w­n§R°£¿ï¨úªº¸ê®Æ§¨¤ÎÀÉ®×?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "©Ò¦³¤º§tªº¤l¸ê®Æ§¨¤ÎÀÉ®×³£·|¤@¨Ö§R°£!";
$net2ftp_messages["Set all targetdirectories"] = "³]©w¦@¦P¥Ø¼Ð¸ê®Æ§¨";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "³]©w¦@¦Pªº¥Ø¼Ð¸ê®Æ§¨, ½Ð¦b¤W­±ªº¤å¦r®Ø¶ñ¼g¥Ø¼Ð¸ê®Æ§¨¦WºÙ¨Ã«ö \"³]©w¦@¦P¥Ø¼Ð¸ê®Æ§¨\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "ª`·N: ½Æ»sÀÉ®×«e, ½Ð½T©w¥Ø¼Ð¸ê®Æ§¨¥²¶·¦s¦b.";
$net2ftp_messages["Different target FTP server:"] = "¥Ø¼Ð FTP ¦øªA¾¹:";
$net2ftp_messages["Username"] = "¥Î¤á¦WºÙ";
$net2ftp_messages["Password"] = "¥Î¤á±K½X";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "¦pªG½Æ»s¨ì­ì¥» FTP ¦øªA¾¹, ¤£¥Î¶ñ¼g.";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "¦pªG§A§Æ±æ½Æ»sÀÉ®×¨ì¥t¤@­Ó FTP ¦øªA¾¹, ½Ð¶ñ¼gµn¤J¸ê®Æ.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "¦pªG²¾°Ê¨ì­ì¥» FTP ¦øªA¾¹, ¤£¥Î¶ñ¼g.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "¦pªG§A§Æ±æ²¾°ÊÀÉ®×¨ì¥t¤@­Ó FTP ¦øªA¾¹, ½Ð¶ñ¼gµn¤J¸ê®Æ.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "½Æ»s¸ê®Æ§¨ <b>%1\$s</b> ¨ì:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "²¾°Ê¸ê®Æ§¨ <b>%1\$s</b> ¨ì:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "¸ê®Æ§¨ <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "½Æ»sÀÉ®× <b>%1\$s</b> ¨ì:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "²¾°ÊÀÉ®× <b>%1\$s</b> ¨ì:";
$net2ftp_messages["File <b>%1\$s</b>"] = "ÀÉ®× <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "½Æ»s symlink <b>%1\$s</b> ¨ì:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "²¾°Ê symlink <b>%1\$s</b> ¨ì:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Symlink <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "¥Ø¼Ð¸ê®Æ§¨:";
$net2ftp_messages["Target name:"] = "¥Ø¼Ð¦WºÙ:";
$net2ftp_messages["Processing the entries:"] = "³B²z¶µ¥Ø:";

} // end copymovedelete


// -------------------------------------------------------------------------
// Download file module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// EasyWebsite module
if ($net2ftp_globals["state"] == "easyWebsite") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create a website in 4 easy steps"] = "Create a website in 4 easy steps";
$net2ftp_messages["Template overview"] = "Template overview";
$net2ftp_messages["Template details"] = "Template details";
$net2ftp_messages["Files are copied"] = "Files are copied";
$net2ftp_messages["Edit your pages"] = "Edit your pages";

// Screen 1 - printTemplateOverview
$net2ftp_messages["Click on the image to view the details of a template."] = "Click on the image to view the details of a template.";
$net2ftp_messages["Back to the Browse screen"] = "Back to the Browse screen";
$net2ftp_messages["Template"] = "Template";
$net2ftp_messages["Copyright"] = "Copyright";
$net2ftp_messages["Click on the image to view the details of this template"] = "Click on the image to view the details of this template";

// Screen 2 - printTemplateDetails
$net2ftp_messages["The template files will be copied to your FTP server. Existing files with the same filename will be overwritten. Do you want to continue?"] = "The template files will be copied to your FTP server. Existing files with the same filename will be overwritten. Do you want to continue?";
$net2ftp_messages["Install template to directory: "] = "Install template to directory: ";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Size"] = "­pºâ¤j¤p";
$net2ftp_messages["Preview page"] = "Preview page";
$net2ftp_messages["opens in a new window"] = "opens in a new window";

// Screen 3
$net2ftp_messages["Please wait while the template files are being transferred to your server: "] = "Please wait while the template files are being transferred to your server: ";
$net2ftp_messages["Done."] = "Done.";
$net2ftp_messages["Continue"] = "Continue";

// Screen 4 - printEasyAdminPanel
$net2ftp_messages["Edit page"] = "Edit page";
$net2ftp_messages["Browse the FTP server"] = "Browse the FTP server";
$net2ftp_messages["Add this link to your favorites to return to this page later on!"] = "Add this link to your favorites to return to this page later on!";
$net2ftp_messages["Edit website at %1\$s"] = "Edit website at %1\$s";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: ¦b³sµ²¤W«ö·Æ¹«¥kÁä¨Ã¿ï¾Ü \"¥[¨ì§Úªº³Ì·R...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: ¦b³sµ²¤W«ö·Æ¹«¥kÁä¨Ã¿ï¾Ü \"Bookmark This Link...\"";

// ftp_copy_local2ftp
$net2ftp_messages["WARNING: Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing..."] = "WARNING: Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Created target subdirectory <b>%1\$s</b>";
$net2ftp_messages["WARNING: Unable to copy the file <b>%1\$s</b>. Continuing..."] = "WARNING: Unable to copy the file <b>%1\$s</b>. Continuing...";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Copied file <b>%1\$s</b>";
}


// -------------------------------------------------------------------------
// Edit module
if ($net2ftp_globals["state"] == "edit") {
// -------------------------------------------------------------------------

// /modules/edit/edit.inc.php
$net2ftp_messages["Unable to open the template file"] = "µLªk¶}±Ò¼Òª©ÀÉ®×";
$net2ftp_messages["Unable to read the template file"] = "µLªkÅª¨ú¼Òª©ÀÉ®×";
$net2ftp_messages["Please specify a filename"] = "½Ð«ü©wÀÉ®×¦WºÙ";
$net2ftp_messages["Status: This file has not yet been saved"] = "ª¬ºA: ¦¹ÀÉ®×¨Ã¥¼Àx¦s";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "ª¬ºA: ©ó <b>%1\$s</b> ¨Ï¥Î %2\$s ¼Ò¦¡Àx¦s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "ª¬ºA: <b>¦¹ÀÉ®×µLªkÀx¦s</b>";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "¸ê®Æ§¨: ";
$net2ftp_messages["File: "] = "ÀÉ®×¦WºÙ: ";
$net2ftp_messages["New file name: "] = "·sÀÉ®×¦WºÙ: ";
$net2ftp_messages["Character encoding: "] = "Character encoding: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "ª`·N: §ïÅÜ¤å¦r¤è®æ·|Àx¦s§ó§ï";
$net2ftp_messages["Copy up"] = "Copy up";
$net2ftp_messages["Copy down"] = "Copy down";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "·j¯Á¸ê®Æ§¨¤ÎÀÉ®×";
$net2ftp_messages["Search again"] = "­«·s·j¯Á";
$net2ftp_messages["Search results"] = "·j¯Áµ²ªG";
$net2ftp_messages["Please enter a valid search word or phrase."] = "½Ð¿é¤J¥¿½TªºÃöÁä¦r©Îµü.";
$net2ftp_messages["Please enter a valid filename."] = "½Ð¿é¤J¥¿½TªºÀÉ®×¦WºÙ.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "½Ð¦b \"from\" ¤å¦r®Ø¿é¤J¥¿½TªºÀÉ®×¤j¤p, ¨Ò¦p 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "½Ð¦b \"to\" ¤å¦r®Ø¿é¤J¥¿½TªºÀÉ®×¤j¤p, ¨Ò¦p 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "½Ð¦b \"from\" ¤å¦r®Ø¿é¤J¥¿½Tªº¤é´Á®æ¦¡ Y-m-d.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "½Ð¦b \"to\" ¤å¦r®Ø¿é¤J¥¿½Tªº¤é´Á®æ¦¡ Y-m-d.";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "¦b¿ï¨úªº¸ê®Æ§¨¤ÎÀÉ®×¸Ì, ÃöÁä¦r <b>%1\$s</b> ¨S¦³¥ô¦ó§ä¨ìªºÀÉ®×.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "ÃöÁä¦r <b>%1\$s</b> ³Q§ä¨ì¦b:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "·j¯Á¦r©Îµü";
$net2ftp_messages["Case sensitive search"] = "°Ï¤À¤j¤p¼g·j¯Á";
$net2ftp_messages["Restrict the search to:"] = "­­¨î·j¯Á©ó:";
$net2ftp_messages["files with a filename like"] = "ÀÉ®×¦WºÙ¹³";
$net2ftp_messages["(wildcard character is *)"] = "(wildcard ¤å¦r¬O *)";
$net2ftp_messages["files with a size"] = "ÀÉ®×¤j¤p";
$net2ftp_messages["files which were last modified"] = "³Ì«á­×§ïªºÀÉ®×";
$net2ftp_messages["from"] = "±q";
$net2ftp_messages["to"] = "¨ì";

$net2ftp_messages["Directory"] = "¸ê®Æ§¨";
$net2ftp_messages["File"] = "ÀÉ®×";
$net2ftp_messages["Line"] = "Line";
$net2ftp_messages["Action"] = "Action";
$net2ftp_messages["View"] = "¬d¬Ý";
$net2ftp_messages["Edit"] = "½s¿è";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "°ª«GÅã¥Ü %1\$s ÀÉ®×ªº­ì©l½X";
$net2ftp_messages["Edit the source code of file %1\$s"] = "½s¿è %1\$s ÀÉ®×ªº­ì©l½X";

} // end findstring


// -------------------------------------------------------------------------
// Help module
// -------------------------------------------------------------------------
// No messages yet


// -------------------------------------------------------------------------
// Install size module
if ($net2ftp_globals["state"] == "install") {
// -------------------------------------------------------------------------

// /modules/install/install.inc.php
$net2ftp_messages["Install software packages"] = "Install software packages";
$net2ftp_messages["Unable to open the template file"] = "µLªk¶}±Ò¼Òª©ÀÉ®×";
$net2ftp_messages["Unable to read the template file"] = "µLªkÅª¨ú¼Òª©ÀÉ®×";
$net2ftp_messages["Unable to get the list of packages"] = "Unable to get the list of packages";

// /skins/blue/install1.template.php
$net2ftp_messages["The net2ftp installer script has been copied to the FTP server."] = "The net2ftp installer script has been copied to the FTP server.";
$net2ftp_messages["This script runs on your web server and requires PHP to be installed."] = "This script runs on your web server and requires PHP to be installed.";
$net2ftp_messages["In order to run it, click on the link below."] = "In order to run it, click on the link below.";
$net2ftp_messages["net2ftp has tried to determine the directory mapping between the FTP server and the web server."] = "net2ftp has tried to determine the directory mapping between the FTP server and the web server.";
$net2ftp_messages["Should this link not be correct, enter the URL manually in your web browser."] = "Should this link not be correct, enter the URL manually in your web browser.";

} // end install


// -------------------------------------------------------------------------
// Java upload module
if ($net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload directories and files using a Java applet"] = "Upload directories and files using a Java applet";
$net2ftp_messages["Number of files:"] = "Number of files:";
$net2ftp_messages["Size of files:"] = "Size of files:";
$net2ftp_messages["Add"] = "Add";
$net2ftp_messages["Remove"] = "Remove";
$net2ftp_messages["Upload"] = "¤W¶ÇÀÉ®×";
$net2ftp_messages["Add files to the upload queue"] = "Add files to the upload queue";
$net2ftp_messages["Remove files from the upload queue"] = "Remove files from the upload queue";
$net2ftp_messages["Upload the files which are in the upload queue"] = "Upload the files which are in the upload queue";
$net2ftp_messages["Maximum server space exceeded. Please select less/smaller files."] = "Maximum server space exceeded. Please select less/smaller files.";
$net2ftp_messages["Total size of the files is too big. Please select less/smaller files."] = "Total size of the files is too big. Please select less/smaller files.";
$net2ftp_messages["Total number of files is too high. Please select fewer files."] = "Total number of files is too high. Please select fewer files.";
$net2ftp_messages["Note: to use this applet, Sun's Java plugin must be installed (version 1.4 or newer)."] = "Note: to use this applet, Sun's Java plugin must be installed (version 1.4 or newer).";

} // end jupload



// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login") {
// -------------------------------------------------------------------------
$net2ftp_messages["Login!"] = "Login!";
$net2ftp_messages["Once you are logged in, you will be able to:"] = "Once you are logged in, you will be able to:";
$net2ftp_messages["Navigate the FTP server"] = "Navigate the FTP server";
$net2ftp_messages["Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."] = "Once you have logged in, you can browse from directory to directory and see all the subdirectories and files.";
$net2ftp_messages["Upload files"] = "Upload files";
$net2ftp_messages["There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet."] = "There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet.";
$net2ftp_messages["Download files"] = "Download files";
$net2ftp_messages["Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive."] = "Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive.";
$net2ftp_messages["Zip files"] = "Zip files";
$net2ftp_messages["... and save the zip archive on the FTP server, or email it to someone."] = "... and save the zip archive on the FTP server, or email it to someone.";
$net2ftp_messages["Unzip files"] = "Unzip files";
$net2ftp_messages["Different formats are supported: .zip, .tar, .tgz and .gz."] = "Different formats are supported: .zip, .tar, .tgz and .gz.";
$net2ftp_messages["Install software"] = "Install software";
$net2ftp_messages["Choose from a list of popular applications (PHP required)."] = "Choose from a list of popular applications (PHP required).";
$net2ftp_messages["Copy, move and delete"] = "Copy, move and delete";
$net2ftp_messages["Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted."] = "Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted.";
$net2ftp_messages["Copy or move to a 2nd FTP server"] = "Copy or move to a 2nd FTP server";
$net2ftp_messages["Handy to import files to your FTP server, or to export files from your FTP server to another FTP server."] = "Handy to import files to your FTP server, or to export files from your FTP server to another FTP server.";
$net2ftp_messages["Rename and chmod"] = "Rename and chmod";
$net2ftp_messages["Chmod handles directories recursively."] = "Chmod handles directories recursively.";
$net2ftp_messages["View code with syntax highlighting"] = "View code with syntax highlighting";
$net2ftp_messages["PHP functions are linked to the documentation on php.net."] = "PHP functions are linked to the documentation on php.net.";
$net2ftp_messages["Plain text editor"] = "Plain text editor";
$net2ftp_messages["Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server."] = "Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server.";
$net2ftp_messages["HTML editors"] = "HTML editors";
$net2ftp_messages["Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from."] = "Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from.";
$net2ftp_messages["Code editor"] = "Code editor";
$net2ftp_messages["Edit HTML and PHP in an editor with syntax highlighting."] = "Edit HTML and PHP in an editor with syntax highlighting.";
$net2ftp_messages["Search for words or phrases"] = "Search for words or phrases";
$net2ftp_messages["Filter out files based on the filename, last modification time and filesize."] = "Filter out files based on the filename, last modification time and filesize.";
$net2ftp_messages["Calculate size"] = "Calculate size";
$net2ftp_messages["Calculate the size of directories and files."] = "Calculate the size of directories and files.";

$net2ftp_messages["FTP server"] = "FTP ¦øªA¾¹";
$net2ftp_messages["Example"] = "½d¨Ò";
$net2ftp_messages["Port"] = "Port";
$net2ftp_messages["Username"] = "¥Î¤á¦WºÙ";
$net2ftp_messages["Password"] = "¥Î¤á±K½X";
$net2ftp_messages["Anonymous"] = "Anonymous";
$net2ftp_messages["Passive mode"] = "³Q°Ê³s½u(Passive mode)";
$net2ftp_messages["Initial directory"] = "¶i¤J¸ê®Æ§¨";
$net2ftp_messages["Language"] = "»y¨¥";
$net2ftp_messages["Skin"] = "¼Òª©";
$net2ftp_messages["FTP mode"] = "FTP mode";
$net2ftp_messages["Automatic"] = "Automatic";
$net2ftp_messages["Login"] = "µn¤J";
$net2ftp_messages["Clear cookies"] = "²M°£ Cookies";
$net2ftp_messages["Admin"] = "Admin";
$net2ftp_messages["Please enter an FTP server."] = "Please enter an FTP server.";
$net2ftp_messages["Please enter a username."] = "Please enter a username.";
$net2ftp_messages["Please enter a password."] = "Please enter a password.";

} // end login


// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login_small") {
// -------------------------------------------------------------------------

$net2ftp_messages["Please enter your Administrator username and password."] = "Please enter your Administrator username and password.";
$net2ftp_messages["Please enter your username and password for FTP server <b>%1\$s</b>."] = "Please enter your username and password for FTP server <b>%1\$s</b>.";
$net2ftp_messages["Username"] = "¥Î¤á¦WºÙ";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Password"] = "¥Î¤á±K½X";
$net2ftp_messages["Login"] = "µn¤J";
$net2ftp_messages["Continue"] = "Continue";

} // end login_small


// -------------------------------------------------------------------------
// Logout module
if ($net2ftp_globals["state"] == "logout") {
// -------------------------------------------------------------------------

// logout.inc.php
$net2ftp_messages["Login page"] = "Login page";

// logout.template.php
$net2ftp_messages["You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>."] = "You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>.";
$net2ftp_messages["Note: other users of this computer could click on the browser's Back button and access the FTP server."] = "Note: other users of this computer could click on the browser's Back button and access the FTP server.";
$net2ftp_messages["To prevent this, you must close all browser windows."] = "To prevent this, you must close all browser windows.";
$net2ftp_messages["Close"] = "Close";
$net2ftp_messages["Click here to close this window"] = "Click here to close this window";

} // end logout


// -------------------------------------------------------------------------
// New directory module
if ($net2ftp_globals["state"] == "newdir") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create new directories"] = "«Ø¥ß·s¸ê®Æ§¨";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "·s¸ê®Æ§¨±N·|«Ø¥ß¦b <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "·s¸ê®Æ§¨¦WºÙ:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "¸ê®Æ§¨ <b>%1\$s</b> ¤w¸g¦¨¥\«Ø¥ß.";
$net2ftp_messages["Directory <b>%1\$s</b> could not be created."] = "Directory <b>%1\$s</b> could not be created.";

} // end newdir


// -------------------------------------------------------------------------
// Raw module
if ($net2ftp_globals["state"] == "raw") {
// -------------------------------------------------------------------------

// /modules/raw/raw.inc.php
$net2ftp_messages["Send arbitrary FTP commands"] = "Send arbitrary FTP commands";


// /skins/[skin]/raw1.template.php
$net2ftp_messages["List of commands:"] = "List of commands:";
$net2ftp_messages["FTP server response:"] = "FTP server response:";

} // end raw


// -------------------------------------------------------------------------
// Rename module
if ($net2ftp_globals["state"] == "rename") {
// -------------------------------------------------------------------------
$net2ftp_messages["Rename directories and files"] = "­«·s©R¦W¸ê®Æ§¨¤ÎÀÉ®×";
$net2ftp_messages["Old name: "] = "ÂÂ¦WºÙ: ";
$net2ftp_messages["New name: "] = "·s¦WºÙ: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "·s¦WºÙ¤£¥i¥]§t¥ô¦ó <b>\".\"</b>¦r²Å ¦¹¿é¤JµLªk­«·s©R¦W¬° <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> ¤w¸g¦¨¥\­«·s©R¦W¬° <b>%2\$s</b>";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> could not be renamed to <b>%2\$s</b>";

} // end rename


// -------------------------------------------------------------------------
// Unzip module
if ($net2ftp_globals["state"] == "unzip") {
// -------------------------------------------------------------------------

// /modules/unzip/unzip.inc.php
$net2ftp_messages["Unzip archives"] = "Unzip archives";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Getting archive %1\$s of %2\$s from the FTP server";
$net2ftp_messages["Unable to get the archive <b>%1\$s</b> from the FTP server"] = "Unable to get the archive <b>%1\$s</b> from the FTP server";

// /skins/[skin]/unzip1.template.php
$net2ftp_messages["Set all targetdirectories"] = "³]©w¦@¦P¥Ø¼Ð¸ê®Æ§¨";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "³]©w¦@¦Pªº¥Ø¼Ð¸ê®Æ§¨, ½Ð¦b¤W­±ªº¤å¦r®Ø¶ñ¼g¥Ø¼Ð¸ê®Æ§¨¦WºÙ¨Ã«ö \"³]©w¦@¦P¥Ø¼Ð¸ê®Æ§¨\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "ª`·N: ½Æ»sÀÉ®×«e, ½Ð½T©w¥Ø¼Ð¸ê®Æ§¨¥²¶·¦s¦b.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Unzip archive <b>%1\$s</b> to:";
$net2ftp_messages["Target directory:"] = "¥Ø¼Ð¸ê®Æ§¨:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "¨Ï¥Î¸ê®Æ§¨¦WºÙ (¦Û°Ê«Ø¥ß¤l¸ê®Æ§¨)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "Update file";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>ª`·N: ¦¹¥\¯à©|¦b´ú¸Õ¶¥¬q. ¥u¨Ï¥Î¦b´ú¸ÕªºÀÉ®×!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "¤wª¾ bugs: - erases tab characters - ¥¼¯à¥¿½T¦bÅé¿n¤jªºÀÉ®×¹B§@ (> 50kB) - ¨Ã¥¼¦b§t¦³¤£¼Ð·Ç¦r²ÅªºÀÉ®×´ú¸Õ</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "¦¹¥\¯à®e³\§A¤W¶Ç¸û·sª©¥»ªºÀÉ®×, ¬d¬Ý¦³¤°»ò­×§ï¤Î¤¹³\§A±µ¨ü©Î©Úµ´¨C¤@­Ó­×§ï. Àx¦s«e, §A¥i¥H¥ý½s¿è¦X¨Ö¤FªºÀÉ®×.";
$net2ftp_messages["Old file:"] = "ÂÂÀÉ®×:";
$net2ftp_messages["New file:"] = "·sÀÉ®×:";
$net2ftp_messages["Restrictions:"] = "­­¨î:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "net2ftp ®e³\³Ì¤j³æÀÉ®×ªºÅé¿n¬° <b>%1\$s kB</b>, PHP ®e³\³Ì¤j³æÀÉ®×ªºÅé¿n¬° <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "³Ì¤j®e³\¾Þ§@®É¶¡¬° <b>%1\$s ¬í</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "FTP¶Ç°e¼Ò¦¡ (ASCII ©Î BINARY) ±N·|®Ú¾Ú°ÆÀÉ¦W¦Û°Ê¤À¿ë";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "¦pªG¥Ø¼ÐÀÉ®×¦s¦b, ±N·|³QÂÐ»\\";
$net2ftp_messages["You did not provide any files or archives to upload."] = "§A¨S¦³´£¨Ñ»Ý­n¤W¶ÇªºÀÉ®×©ÎÀ£ÁYÀÉ®×.";
$net2ftp_messages["Unable to delete the new file"] = "µLªk§R°£·sÀÉ®×";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "½Ðµy«J...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "¿ï¾Ü¤U­±ªº¦æ¦C, ±µ¨ü©Î©Úµ´­×§ï¨Ã´£¥æªí®æ.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "¤W¶Ç¨ì¸ê®Æ§¨:";
$net2ftp_messages["Files"] = "ÀÉ®×";
$net2ftp_messages["Archives"] = "Archives";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "¦b³o¸Ì¿é¤JªºÀÉ®×±N·|¶Ç°e¨ì FTP ¦øªA¾¹.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "¦b³o¸Ì¿é¤JªºÀ£ÁYÀÉ®×±N·|³Q¸ÑÀ£¨Ã¥B§â¤º§tªºÀÉ®×¶Ç°e¨ì FTP ¦øªA¾¹.";
$net2ftp_messages["Add another"] = "¥[¤J¨ä¥L";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "¨Ï¥Î¸ê®Æ§¨¦WºÙ (¦Û°Ê«Ø¥ß¤l¸ê®Æ§¨)";

$net2ftp_messages["Choose a directory"] = "½Ð¿ï¾Ü¸ê®Æ§¨";
$net2ftp_messages["Please wait..."] = "½Ðµy«J...";
$net2ftp_messages["Uploading... please wait..."] = "¤W¶Ç¤¤... ½Ðµy«J...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "¦pªG¤W¶Ç®É¶¡¶W¹L <b>%1\$s ¬í<\/b>, §A»Ý­n´î¤Ö¤W¶ÇÀÉ®×¼Æ¶q¤Î¤W¶ÇÅé¿n¸û¤ÖªºÀÉ®×.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "¦¹µøµ¡±N©ó¼Æ¬í«á¦Û°ÊÃö³¬.";
$net2ftp_messages["Close window now"] = "¥ß§YÃö³¬µøµ¡";

$net2ftp_messages["Upload files and archives"] = "¤W¶ÇÀÉ®×¤ÎÀ£ÁYÀÉ®×";
$net2ftp_messages["Upload results"] = "¤W¶Çµ²ªG";
$net2ftp_messages["Checking files:"] = "ÀË¬dÀÉ®×:";
$net2ftp_messages["Transferring files to the FTP server:"] = "¶Ç°e¨ì FTP ¦øªA¾¹ªºÀÉ®×:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "¸ÑÀ£¨Ã¶Ç°e¨ì FTP ¦øªA¾¹ªºÀÉ®×:";
$net2ftp_messages["Upload more files and archives"] = "¤W¶Ç§ó¦hÀÉ®×©ÎÀ£ÁYÀÉ®×";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "­­¨î:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "net2ftp ®e³\³Ì¤j³æÀÉ®×ªºÅé¿n¬° <b>%1\$s kB</b>, PHP ®e³\³Ì¤j³æÀÉ®×ªºÅé¿n¬° <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "³Ì¤j®e³\¾Þ§@®É¶¡¬° <b>%1\$s ¬í</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "FTP¶Ç°e¼Ò¦¡ (ASCII ©Î BINARY) ±N·|®Ú¾Ú°ÆÀÉ¦W¦Û°Ê¤À¿ë";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "¦pªG¥Ø¼ÐÀÉ®×¦s¦b, ±N·|³QÂÐ»\\";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "View file %1\$s";
$net2ftp_messages["View image %1\$s"] = "¬d¬Ý¹Ï¤ù %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "View Macromedia ShockWave Flash movie %1\$s";
$net2ftp_messages["Image"] = "Image";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "To save the image, right-click on it and choose 'Save picture as...'";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "À£ÁY¶µ¥Ø";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "¦b FTP ¦øªA¾¹¤WÀx¦sÀ£ÁYÀÉ®×¬°:";
$net2ftp_messages["Email the zip file in attachment to:"] = "¹q¶lÀ£ÁYÀÉ®×¬°ªþÀÉ¨ì:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "ª`·N: ¶Ç°eÀÉ®×·|°O¿ý§Aªº IP ¦a§}©M®É¶¡¨Ã§â³o¨Ç¸ê®Æ¤@¨Ö°e¥X.";
$net2ftp_messages["Some additional comments to add in the email:"] = "¶Ç°e¹q¶lªþ¥[°T®§:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "§A¨S¦³¿é¤JÀ£ÁYÀÉ®×ªº¦WºÙ. ½Ðªð¦^¨Ã¿é¤J.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "¦¹¹q¶l¦a§} (%1\$s) ®æ¦¡¤£¥¿½T.<br />½Ð¨Ï¥Î¦¹®æ¦¡ <b>username@domain.com</b>";

} // end zip

?>