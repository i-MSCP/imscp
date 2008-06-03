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
$net2ftp_messages["en"] = "ja";

// HTML dir attribute: left-to-right (LTR) or right-to-left (RTL)
$net2ftp_messages["ltr"] = "ltr";

// CSS style: align left or right (use in combination with LTR or RTL)
$net2ftp_messages["left"] = "left";
$net2ftp_messages["right"] = "right";

// Encoding
$net2ftp_messages["iso-8859-1"] = "euc-jp";


// -------------------------------------------------------------------------
// Status messages
// -------------------------------------------------------------------------

// When translating these messages, keep in mind that the text should not be too long
// It should fit in the status textbox

$net2ftp_messages["Connecting to the FTP server"] = "FTP¥µ¡¼¥Ð¤ËÀÜÂ³¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Logging into the FTP server"] = "FTP¥µ¡¼¥Ð¤Ë¥í¥°¥¤¥ó¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Setting the passive mode"] = "¥Ñ¥Ã¥·¥Ö¥â¡¼¥É¤ÎÀßÄê¤ò¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Getting the FTP system type"] = "FTP¥·¥¹¥Æ¥à¼ïÊÌ¤ò¼èÆÀ¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Changing the directory"] = "¥Ç¥£¥ì¥¯¥È¥ê¤òÊÑ¹¹¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Getting the current directory"] = "¥«¥ì¥ó¥È¥Ç¥£¥ì¥¯¥È¥ê¤ò¼èÆÀ¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Getting the list of directories and files"] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Î¥ê¥¹¥È¤ò¼èÆÀ¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Parsing the list of directories and files"] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Î¥ê¥¹¥È¤ò²òÀÏ¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Logging out of the FTP server"] = "FTP¥µ¡¼¥Ð¤«¤é¥í¥°¥¢¥¦¥È¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Getting the list of directories and files"] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Î¥ê¥¹¥È¤ò¼èÆÀ¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Printing the list of directories and files"] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Î¥ê¥¹¥È¤òÉ½¼¨¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Processing the entries"] = "¥¨¥ó¥È¥ê¤Î½èÍý¤ò¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Processing entry %1\$s"] = "¥¨¥ó¥È¥ê %1\$s ¤ò½èÍý¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Checking files"] = "¥Õ¥¡¥¤¥ë¥Á¥§¥Ã¥¯¤ò¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Transferring files to the FTP server"] = "¥Õ¥¡¥¤¥ë¤ò FTP¥µ¡¼¥Ð¤ËÅ¾Á÷¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Decompressing archives and transferring files"] = "¥¢¡¼¥«¥¤¥Ö¤ò²òÅà¤·¡¢¥Õ¥¡¥¤¥ë¤òÅ¾Á÷¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Searching the files..."] = "¥Õ¥¡¥¤¥ë¤Î¸¡º÷Ãæ...";
$net2ftp_messages["Uploading new file"] = "¿·¤·¤¤¥Õ¥¡¥¤¥ë¤ò¥¢¥Ã¥×¥í¡¼¥É¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Reading the file"] = "¥Õ¥¡¥¤¥ë¤òÆÉ¤ß¹þ¤ó¤Ç¤¤¤Þ¤¹";
$net2ftp_messages["Parsing the file"] = "¥Õ¥¡¥¤¥ë¤ò²òÀÏ¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Reading the new file"] = "¿·¤·¤¤¥Õ¥¡¥¤¥ë¤ÎÆÉ¤ß¹þ¤ó¤Ç¤¤¤Þ¤¹";
$net2ftp_messages["Reading the old file"] = "¸Å¤¤¥Õ¥¡¥¤¥ë¤òÆÉ¤ß¹þ¤ó¤Ç¤¤¤Þ¤¹";
$net2ftp_messages["Comparing the 2 files"] = "2¤Ä¤Î¥Õ¥¡¥¤¥ë¤òÈæ³Ó¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Printing the comparison"] = "º¹Ê¬¤òÉ½¼¨¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "FTP¥³¥Þ¥ó¥É¤òÁ÷¿®Ãæ %2\$s ¤Î %1\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Getting archive %1\$s of %2\$s from the FTP server";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Creating a temporary directory on the FTP server";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Setting the permissions of the temporary directory";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Copying the net2ftp installer script to the FTP server";
$net2ftp_messages["Script finished in %1\$s seconds"] = "¥¹¥¯¥ê¥×¥È¤Ï %1\$s ÉÃ¤Ç½ªÎ»¤·¤Þ¤·¤¿";
$net2ftp_messages["Script halted"] = "¥¹¥¯¥ê¥×¥È¤ÏÄä»ß¤·¤Þ¤·¤¿";

// Used on various screens
$net2ftp_messages["Please wait..."] = "¤·¤Ð¤é¤¯¤ªÂÔ¤Á²¼¤µ¤¤...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Í½´ü¤·¤Ê¤¤¥¹¥Æ¡¼¥ÈÊ¸»ú: %1\$s ½ªÎ»¤·¤Þ¤¹¡£";
$net2ftp_messages["This beta function is not activated on this server."] = "¤³¤Î²¾µ¡Ç½¤Ï¤³¤Î¥µ¡¼¥Ð¤Ç¤Ï²ÔÆ°¤·¤Þ¤»¤ó¡£";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "¤³¤Îµ¡Ç½¤Ï¥µ¥¤¥È¤Î´ÉÍý¼Ô¤Ë¤è¤Ã¤ÆÌµ¸ú¤Ë¤µ¤ì¤Æ¤¤¤Þ¤¹¡£";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤ÏÂ¸ºß¤·¤Ê¤¤¤«¡¢ÁªÂò¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£¥Ç¥£¥ì¥¯¥È¥ê <b>%2\$s</b> ¤¬Âå¤ï¤ê¤ËÉ½¼¨¤µ¤ì¤Þ¤·¤¿¡£";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "¤¢¤Ê¤¿¤Î¥ë¡¼¥È¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤ÏÂ¸ºß¤·¤Ê¤¤¤«¡¢ÁªÂò¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤òÁªÂò¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó - ¤¢¤Ê¤¿¤Ë¤Ï¤³¤Î¥Ç¥£¥ì¥¯¥È¥ê¤ò±ÜÍ÷¤¹¤ë½½Ê¬¤Ê»ñ³Ê¤¬¤Ê¤¤¤«¡¢¤Þ¤¿¤Ï¥Ç¥£¥ì¥¯¥È¥ê¤¬Â¸ºß¤·¤Þ¤»¤ó¡£";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted.";
$net2ftp_messages["Execute %1\$s in a new window"] = "%1\$s ¤ò¿·¤·¤¤¥¦¥£¥ó¥É¥¦¤Ç¼Â¹Ô";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "ºÇÄã1¤Ä°Ê¾å¤Î¥Ç¥£¥ì¥¯¥È¥êËô¤Ï¥Õ¥¡¥¤¥ë¤òÁªÂò¤·¤Æ²¼¤µ¤¤!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "FTP¥µ¡¼¥Ð <b>%1\$s</b> ¤Ï¡¢µö²Ä¤µ¤ì¤¿FTP¥µ¡¼¥Ð¤Î¥ê¥¹¥È¤ËÆþ¤Ã¤Æ¤¤¤Þ¤»¤ó¡£";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "FTP¥µ¡¼¥Ð<b>%1\$s</b>¤Ï¡¢ ¶Ø»ß¤µ¤ì¤¿FTP¥µ¡¼¥Ð¤Î¥ê¥¹¥È¤ËÆþ¤Ã¤Æ¤¤¤Þ¤¹¡£";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "FTP¥µ¡¼¥Ð¥Ý¡¼¥È %1\$s ¤Ï»ÈÍÑ¤Ç¤­¤Þ¤»¤ó¡£";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "Your IP address (%1\$s) is not in the list of allowed IP addresses.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "¤¢¤Ê¤¿¤ÎIP¥¢¥É¥ì¥¹(%1\$s)¤Ï¡¢¶Ø»ß¤µ¤ì¤¿IP¥¢¥É¥ì¥¹¤Î¥ê¥¹¥È¤ËÆþ¤Ã¤Æ¤¤¤Þ¤¹¡£";

// isAuthorizedDirectory()
$net2ftp_messages["Table net2ftp_users contains duplicate rows."] = "¥Æ¡¼¥Ö¥ë net2ftp_users ¤Ë½ÅÊ£¤·¤¿¹Ô¤¬´Þ¤Þ¤ì¤Æ¤¤¤Þ¤¹¡£";

// logAccess(), logLogin(), logError()
$net2ftp_messages["Unable to execute the SQL query."] = "SQL¥¯¥¨¥ê¤ò¼Â¹Ô¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£";

// checkAdminUsernamePassword()
$net2ftp_messages["You did not enter your Administrator username or password."] = "´ÉÍý¼Ô¤Î¥æ¡¼¥¶Ì¾¤«¥Ñ¥¹¥ï¡¼¥É¤òÆþÎÏ¤·¤Æ¤Þ¤»¤ó¡£";
$net2ftp_messages["Wrong username or password. Please try again."] = "¸í¤Ã¤¿¥æ¡¼¥¶Ì¾¤«¥Ñ¥¹¥ï¡¼¥É¤Ç¤¹¡£¤ä¤êÄ¾¤·¤Æ¤¯¤À¤µ¤¤¡£";


// -------------------------------------------------------------------------
// /includes/consumption.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to determine your IP address."] = "¤¢¤Ê¤¿¤Î IP¥¢¥É¥ì¥¹¤òÈ½ÊÌ¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "¥Æ¡¼¥Ö¥ë net2ftp_log_consumption_ipaddress ¤Ë½ÅÊ£¤·¤¿¹Ô¤¬´Þ¤Þ¤ì¤Æ¤¤¤Þ¤¹¡£";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "¥Æ¡¼¥Ö¥ë net2ftp_log_consumption_ftpserver ¤Ë½ÅÊ£¤·¤¿¹Ô¤¬´Þ¤Þ¤ì¤Æ¤¤¤Þ¤¹¡£";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "ÊÑ¿ô <b>consumption_ipaddress_datatransfer</b> ¤Ï¿ôÃÍ¤Ç¤Ï¤¢¤ê¤Þ¤»¤ó¡£";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "¥Æ¡¼¥Ö¥ë net2ftp_log_consumption_ipaddress ¤Ï¹¹¿·ÉÔ²Ä¤Ç¤¹¡£";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "¥Æ¡¼¥Ö¥ë net2ftp_log_consumption_ipaddress ¤Ë½ÅÊ£¤·¤¿¥¨¥ó¥È¥ê¤¬´Þ¤Þ¤ì¤Æ¤¤¤Þ¤¹¡£";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "¥Æ¡¼¥Ö¥ë net2ftp_log_consumption_ftpserver ¤Ï¹¹¿·ÉÔ²Ä¤Ç¤¹¡£";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "¥Æ¡¼¥Ö¥ë net2ftp_log_consumption_ftpserver ¤Ë½ÅÊ£¤·¤¿¥¨¥ó¥È¥ê¤¬´Þ¤Þ¤ì¤Æ¤¤¤Þ¤¹¡£";
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
$net2ftp_messages["An error has occured"] = "¥¨¥é¡¼¤Ç¤¹";
$net2ftp_messages["Go back"] = "Ìá¤ë";
$net2ftp_messages["Go to the login page"] = "¥í¥°¥¤¥ó¥Ú¡¼¥¸¤ËÌá¤ë";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "<a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">PHP ¤Î FTP¥â¥¸¥å¡¼¥ë</a> ¤¬¥¤¥ó¥¹¥È¡¼¥ë¤µ¤ì¤Æ¤¤¤Þ¤»¤ó¡£<br /><br /> ¤³¤Î web¥µ¥¤¥È¤Î´ÉÍý¼Ô¤¬¤³¤Î FTP¥â¥¸¥å¡¼¥ë ¤ò¥¤¥ó¥¹¥È¡¼¥ë¤·¤Ê¤±¤ì¤Ð¤Ê¤ê¤Þ¤»¤ó¡£¥¤¥ó¥¹¥È¡¼¥ëÊýË¡¤Ï <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a> ¤ÇÄó¶¡¤µ¤ì¤Æ¤¤¤Þ¤¹<br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "FTP¥µ¡¼¥Ð <b>%1\$s</b> ¤Î¥Ý¡¼¥È <b>%2\$s</b> ¤ËÀÜÂ³¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£<br /><br />¤³¤Î¥¢¥É¥ì¥¹¤ÏËÜÅö¤Ë FTP¥µ¡¼¥Ð ¤Î¤â¤Î¤Ç¤¹¤«? ¤³¤ì¤ÏÂçÄñ HTTP (web) ¥µ¡¼¥Ð¤Î¤â¤Î¤È¤Ï°Û¤Ê¤Ã¤Æ¤¤¤Þ¤¹¡£¤¢¤Ê¤¿¤Î ISP¤Î¥µ¥Ý¡¼¥È¥Ç¥¹¥¯ ¤Þ¤¿¤Ï ¥·¥¹¥Æ¥à´ÉÍý¼Ô ¤ËÌä¤¤¹ç¤ï¤»¤Æ¤¯¤À¤µ¤¤¡£<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "FTP¥µ¡¼¥Ð <b>%1\$s</b> ¤Ë¥æ¡¼¥¶Ì¾ <b>%2\$s</b> ¤Ç¥í¥°¥¤¥ó¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£<br /><br />¤¢¤Ê¤¿¤Î¥æ¡¼¥¶Ì¾¤È¥Ñ¥¹¥ï¡¼¥É¤ÏËÜÅö¤ËÀµ¤·¤¤¤Ç¤¹¤«? ¤¢¤Ê¤¿¤Î ISP¤Î¥µ¥Ý¡¼¥È¥Ç¥¹¥¯ ¤Þ¤¿¤Ï ¥·¥¹¥Æ¥à´ÉÍý¼Ô ¤ËÌä¤¤¹ç¤ï¤»¤Æ¤¯¤À¤µ¤¤¡£<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "FTP¥µ¡¼¥Ð <b>%1\$s</b> ¤Î¥Ñ¥Ã¥·¥Ö¥â¡¼¥É¤ËÀÚ¤êÂØ¤¨¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Âè2 (ÂÐ¾Ý) FTP¥µ¡¼¥Ð <b>%1\$s</b> ¤Î¥Ý¡¼¥È <b>%2\$s</b> ¤ËÀÜÂ³¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£<br /><br />¤³¤Î¥¢¥É¥ì¥¹¤ÏËÜÅö¤Ë Âè2 (ÂÐ¾Ý) FTP¥µ¡¼¥Ð ¤Î¤â¤Î¤Ç¤¹¤«? ¤³¤ì¤ÏÂçÄñ HTTP (web) ¥µ¡¼¥Ð¤Î¤â¤Î¤È¤Ï°Û¤Ê¤Ã¤Æ¤¤¤Þ¤¹¡£¤¢¤Ê¤¿¤Î ISP¤Î¥µ¥Ý¡¼¥È¥Ç¥¹¥¯ ¤Þ¤¿¤Ï ¥·¥¹¥Æ¥à´ÉÍý¼Ô ¤ËÌä¤¤¹ç¤ï¤»¤Æ¤¯¤À¤µ¤¤¡£<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Âè2 (ÂÐ¾Ý) FTP¥µ¡¼¥Ð <b>%1\$s</b> ¤Ë¥æ¡¼¥¶Ì¾ <b>%2\$s</b> ¤Ç¥í¥°¥¤¥ó¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£<br /><br />¤¢¤Ê¤¿¤Î¥æ¡¼¥¶Ì¾¤È¥Ñ¥¹¥ï¡¼¥É¤ÏËÜÅö¤ËÀµ¤·¤¤¤Ç¤¹¤«? ¤¢¤Ê¤¿¤Î ISP¤Î¥µ¥Ý¡¼¥È¥Ç¥¹¥¯ ¤Þ¤¿¤Ï ¥·¥¹¥Æ¥à´ÉÍý¼Ô ¤ËÌä¤¤¹ç¤ï¤»¤Æ¤¯¤À¤µ¤¤¡£<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "Âè2 (ÂÐ¾Ý) FTP¥µ¡¼¥Ð <b>%1\$s</b> ¤Î¥Ñ¥Ã¥·¥Ö¥â¡¼¥É¤ËÀÚ¤êÂØ¤¨¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "¥Ç¥£¥ì¥¯¥È¥ê¡Ê¤Þ¤¿¤Ï¥Õ¥¡¥¤¥ë¡Ë¤ÎÌ¾Á°¤ò <b>%1\$s</b> ¤«¤é <b>%2\$s</b> ÊÑ¹¹¤Ç¤­¤Þ¤»¤ó";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "¥µ¥¤¥È¥³¥Þ¥ó¥É<b>%1\$s</b> ¤Ï¼Â¹Ô¤Ç¤­¤Þ¤»¤ó¡£CHMOD ¥³¥Þ¥ó¥É¤Ï Windows FTP¥µ¡¼¥Ð¤Ç¤Ï¤Ê¤¯ Unix FTP¥µ¡¼¥Ð¤Ç¤Î¤ßÍ­¸ú¤Ç¤¢¤ë¤³¤È¤ËÃí°Õ¤·¤Æ¤¯¤À¤µ¤¤¡£";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤Îµö²Ä¾ðÊó¤Ï <b>%2\$s</b> ¤ËÊÑ¹¹¤µ¤ì¤Þ¤·¤¿";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> Æâ¤Î¥¨¥ó¥È¥ê¤ò½èÍý¤·¤Æ¤¤¤Þ¤¹:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Îµö²Ä¾ðÊó¤Ï <b>%2\$s</b> ¤ËÊÑ¹¹¤µ¤ì¤Þ¤·¤¿";
$net2ftp_messages["All the selected directories and files have been processed."] = "ÁªÂò¤µ¤ì¤¿¤¹¤Ù¤Æ¤Î¥Ç¥£¥ì¥¯¥È¥ê¡¢¥Õ¥¡¥¤¥ë¤Î½èÍý¤¬´°Î»¤·¤Þ¤·¤¿¡£";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤Ïºï½ü¤Ç¤­¤Þ¤»¤ó";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Ïºï½ü¤Ç¤­¤Þ¤»¤ó";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤ÎºîÀ®¤Ï¤Ç¤­¤Þ¤»¤ó";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "¥Æ¥ó¥Ý¥é¥ê¥Õ¥¡¥¤¥ë¤ÎºîÀ®¤¬¹Ô¤¨¤Þ¤»¤ó";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "FTP¥µ¡¼¥Ð ¤«¤é¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤ò¼èÆÀ¤· °ì»þ¥Õ¥¡¥¤¥ë <b>%2\$s</b> ¤È¤·¤ÆÊÝÂ¸¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£<br />¥Ç¥£¥ì¥¯¥È¥ê %3\$s ¤Îµö²Ä¾ðÊó¤ò³ÎÇ§¤·¤Æ¤¯¤À¤µ¤¤¡£<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "¥Æ¥ó¥Ý¥é¥ê¥Õ¥¡¥¤¥ë¤ò³«¤¯¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£¥Ç¥£¥ì¥¯¥È¥ê %1\$s ¤Îµö²Ä¾ðÊó¤ò³ÎÇ§¤·¤Æ¤¯¤À¤µ¤¤¡£";
$net2ftp_messages["Unable to read the temporary file"] = "¥Æ¥ó¥Ý¥é¥ê¥Õ¥¡¥¤¥ë¤òÆÉ¤ß¹þ¤à¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "¥Æ¥ó¥Ý¥é¥ê¥Õ¥¡¥¤¥ë¤òÊÄ¤¸¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";
$net2ftp_messages["Unable to delete the temporary file"] = "¥Æ¥ó¥Ý¥é¥ê¥Õ¥¡¥¤¥ë¤òºï½ü¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "¥Æ¥ó¥Ý¥é¥ê¥Õ¥¡¥¤¥ë¤ÎºîÀ®¤¬¹Ô¤¨¤Þ¤»¤ó¡£¥Ç¥£¥ì¥¯¥È¥ê %1\$s ¤Îµö²Ä¾ðÊó¤ò³ÎÇ§¤·¤Æ¤¯¤À¤µ¤¤¡£";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "¥Æ¥ó¥Ý¥é¥ê¥Õ¥¡¥¤¥ë¤ò³«¤¯¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£¥Ç¥£¥ì¥¯¥È¥ê %1\$s ¤Îµö²Ä¾ðÊó¤ò³ÎÇ§¤·¤Æ¤¯¤À¤µ¤¤¡£";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "¥Æ¥ó¥Ý¥é¥ê¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Ë½ñ¤­¹þ¤à¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£<br />¥Ç¥£¥ì¥¯¥È¥ê %2\$s ¤Îµö²Ä¾ðÊó¤ò³ÎÇ§¤·¤Æ¤¯¤À¤µ¤¤¡£";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "¥Æ¥ó¥Ý¥é¥ê¥Õ¥¡¥¤¥ë¤òÊÄ¤¸¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "FTP¥µ¡¼¥Ð¾å¤Ë¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤òÃÖ¤¯¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£<br />¥Ç¥£¥ì¥¯¥È¥ê¤Ø¤Î½ñ¤­¹þ¤ßµö²Ä¤¬¤¢¤ê¤Þ¤»¤ó¡£";
$net2ftp_messages["Unable to delete the temporary file"] = "¥Æ¥ó¥Ý¥é¥ê¥Õ¥¡¥¤¥ë¤òºï½ü¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤Î½èÍýÃæ";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "ÂÐ¾Ý¤Î¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤Ï¸µ¤Î¥Ç¥£¥ì¥¯¥È¥ê <b>%2\$s</b> ¤Î¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê¤«¡¢¤Þ¤¿¤ÏÆ±¤¸¥Ç¥£¥ì¥¯¥È¥ê¤Ç¤¹¡£¤³¤Î¥Ç¥£¥ì¥¯¥È¥ê¤Î½èÍý¤ò¥¹¥­¥Ã¥×¤·¤Þ¤¹¡£";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The directory <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤Ï¤¹¤Ç¤ËÂ¸ºß¤¹¤ë¤¿¤á¡¢ºîÀ®¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£¥³¥Ô¡¼¡¿°ÜÆ°½èÍý¤ò·ÑÂ³¤·¤Þ¤¹...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "ÂÐ¾Ý¤Î¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤òºîÀ®";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤òÁªÂò¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£¤³¤Î¥Ç¥£¥ì¥¯¥È¥ê¤Ï¥¹¥­¥Ã¥×¤µ¤ì¤Þ¤¹¡£";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤òºï½ü¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó - ¤³¤Î¥Ç¥£¥ì¥¯¥È¥ê¤Ï¶õ¤Ç¤Ï¤¢¤ê¤Þ¤»¤ó";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤òºï½ü";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤Î½èÍý¤¬´°Î»";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "ÂÐ¾Ý¤Î¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Ï¸µ¤Î¥Õ¥¡¥¤¥ë¤ÈÆ±Ì¾¤Ç¤¹¡£¤³¤Î¥Õ¥¡¥¤¥ë¤Î½èÍý¤ò¥¹¥­¥Ã¥×¤·¤Þ¤¹¡£";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The file <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "The file <b>%1\$s</b> is too big to be copied, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "The file <b>%1\$s</b> is too big to be moved, aborting the move";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤ò¥³¥Ô¡¼¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤ò¥³¥Ô¡¼";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Unable to move the file <b>%1\$s</b>, aborting the move";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤ò°ÜÆ°";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Ïºï½ü¤Ç¤­¤Þ¤»¤ó";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤òºï½ü";
$net2ftp_messages["All the selected directories and files have been processed."] = "ÁªÂò¤µ¤ì¤¿¤¹¤Ù¤Æ¤Î¥Ç¥£¥ì¥¯¥È¥ê¡¢¥Õ¥¡¥¤¥ë¤Î½èÍý¤¬´°Î»¤·¤Þ¤·¤¿¡£";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "FTP¥â¡¼¥É <b>%2\$s</b> ¤ò»ÈÍÑ¤·¤Æ¥ê¥â¡¼¥È¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤ò¥í¡¼¥«¥ë¥Õ¥¡¥¤¥ë¤Ë¥³¥Ô¡¼¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤òºï½ü¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "The file is too big to be transferred";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "1Æü¤ÎÀ©¸Â¤ËÅþÃ£: ¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤ÏÅ¾Á÷¤µ¤ì¤Þ¤»¤ó";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "FTP¥â¡¼¥É <b>%2\$s</b> ¤ò»ÈÍÑ¤·¤Æ¥í¡¼¥«¥ë¥Õ¥¡¥¤¥ë¤ò¥ê¥â¡¼¥È¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Ë¥³¥Ô¡¼¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";
$net2ftp_messages["Unable to delete the local file"] = "¥í¡¼¥«¥ë¥Õ¥¡¥¤¥ë¤òºï½ü¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "¥Æ¥ó¥Ý¥é¥ê¥Õ¥¡¥¤¥ë¤òºï½ü¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";
$net2ftp_messages["Unable to send the file to the browser"] = "¥Õ¥¡¥¤¥ë¤ò¥Ö¥é¥¦¥¶¤ËÁ÷¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "¥Æ¥ó¥Ý¥é¥ê¥Õ¥¡¥¤¥ë¤ÎºîÀ®¤¬¹Ô¤¨¤Þ¤»¤ó";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "°µ½Ì¥Õ¥¡¥¤¥ë¤Ï¤¹¤Ç¤ËFTP¥µ¡¼¥Ð¾å¤Ç <b>%1\$s</b> ¤È¤·¤ÆÊÝÂ¸¤µ¤ì¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Requested files"] = "Requested files";

$net2ftp_messages["Dear,"] = "Dear,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "Someone has requested the files in attachment to be sent to this email account (%1\$s).";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "Note that if you don't open the Zip file, the files inside cannot harm your computer.";
$net2ftp_messages["Information about the sender: "] = "Information about the sender: ";
$net2ftp_messages["IP address: "] = "IP address: ";
$net2ftp_messages["Time of sending: "] = "Time of sending: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "Sent via the net2ftp application installed on this website: ";
$net2ftp_messages["Webmaster's email: "] = "Webmaster's email: ";
$net2ftp_messages["Message of the sender: "] = "Message of the sender: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "°µ½Ì¥Õ¥¡¥¤¥ë¤Ï <b>%1\$s</b> ¤ØÁ÷¿®¤µ¤ì¤Þ¤·¤¿¡£";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤ÏÂç¤­¤¹¤®¤Þ¤¹¡£¤³¤Î¥Õ¥¡¥¤¥ë¤Ï¥¢¥Ã¥×¥í¡¼¥É¤µ¤ì¤Þ¤»¤ó¡£";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded.";
$net2ftp_messages["Could not generate a temporary file."] = "°ì»þ¥Õ¥¡¥¤¥ë¤òÀ¸À®¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Ï°ÜÆ°¤Ç¤­¤Þ¤»¤ó";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Ï OK";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "¥¢¥Ã¥×¥í¡¼¥É¤·¤¿¥Õ¥¡¥¤¥ë¤ò¥Æ¥ó¥Ý¥é¥ê¥Ç¥£¥ì¥¯¥È¥ê¤Ø°ÜÆ°¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£<br /><br />¤³¤Î¥µ¥¤¥È¤Î´ÉÍý¼Ô¤¬ net2ftp ¤Î /temp ¥Ç¥£¥ì¥¯¥È¥ê¤Îµö²Ä¾ðÊó¤ò <b>777</b> ¤ËÀßÄê¤¹¤ëÉ¬Í×¤¬¤¢¤ê¤Þ¤¹¡£";
$net2ftp_messages["You did not provide any file to upload."] = "¥¢¥Ã¥×¥í¡¼¥É¤¹¤ë¥Õ¥¡¥¤¥ë¤¬ÀßÄê¤µ¤ì¤Æ¤¤¤Þ¤»¤ó¡£";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤ò FTP¥µ¡¼¥Ð¤ËÅ¾Á÷¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¤Ç¤·¤¿";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Ï FTP¥â¡¼¥É <b>%2\$s</b> ¤ò»ÈÍÑ¤·¤Æ FTP¥µ¡¼¥Ð¤ËÅ¾Á÷¤µ¤ì¤Þ¤·¤¿";
$net2ftp_messages["Transferring files to the FTP server"] = "¥Õ¥¡¥¤¥ë¤ò FTP¥µ¡¼¥Ð¤ËÅ¾Á÷¤·¤Æ¤¤¤Þ¤¹";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "°µ½Ì¥Õ¥¡¥¤¥ë¤Î½èÍý¥¨¥é¡¼ %1\$s: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "¥Õ¥¡¥¤¥ë³ÈÄ¥»Ò¤¬È½ÊÌÉÔÇ½¤Ê¤¿¤á¡¢°µ½Ì¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Ï½èÍý¤µ¤ì¤Þ¤»¤ó¤Ç¤·¤¿¡£¸½ºß¥µ¥Ý¡¼¥È¤µ¤ì¤Æ¤¤¤ë°µ½Ì¥Õ¥¡¥¤¥ë¤Ï zip, tar, tgz, gz ¤À¤±¤Ç¤¹¡£";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Unable to extract the files and directories from the archive";
$net2ftp_messages["Created directory %1\$s"] = "Created directory %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "Could not create directory %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "Copied file %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "Could not copy file %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "Unable to delete the temporary directory";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Unable to delete the temporary file %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "¥µ¥¤¥È¥³¥Þ¥ó¥É <b>%1\$s</b> ¤ò¼Â¹Ô¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "ºî¶È¤ÏÃæ»ß¤·¤Þ¤·¤¿";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "¤¢¤Ê¤¿¤¬ net2ftp¤Ç¼Â¹Ô¤·¤è¤¦¤È¤·¤¿ºî¶È¤Ï¡¢µöÍÆ»þ´Ö¤Î %1\$s ÉÃ¤è¤ê¤âÄ¹¤¤»þ´Ö¤¬É¬Í×¤Ç¤¹¡£¤·¤¿¤¬¤Ã¤Æ¡¢ºî¶È¤ÏÃæ»ß¤·¤Þ¤·¤¿¡£";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "¤³¤Î»þ´ÖÀ©¸Â¤Ï¡¢³§ÍÍ¤¬¸øÊ¿¤Ë web¥µ¡¼¥Ð¤òÍøÍÑ¤Ç¤­¤ë¤è¤¦ÊÝ¾ã¤¹¤ë¤â¤Î¤Ç¤¹¡£";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "ºî¶È¤ò¤è¤ê¾®¤µ¤Êºî¶È¤ËÊ¬³ä¤·¤Æ¤ß¤Æ²¼¤µ¤¤: ¥Õ¥¡¥¤¥ë¤ÎÁªÂò¤òÀ©¸Â¤·¡¢Âç¤­¤Ê¥Õ¥¡¥¤¥ë¤ò¾Ê¤¤¤Æ¤¯¤À¤µ¤¤¡£";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "¤â¤·¤É¤¦¤·¤Æ¤â net2ftp¤ò»È¤Ã¤ÆÄ¹»þ´ÖÂç¤­¤Êºî¶È¤ò¤¹¤ëÉ¬Í×¤¬¤¢¤ë¾ì¹ç¤Ï¡¢¤´¼«Ê¬¤Î¥µ¡¼¥Ð¤Ë net2ftp¤òÆ³Æþ¤¹¤ë¤³¤È¤ò¸¡Æ¤¤·¤Æ²¼¤µ¤¤¡£";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "ÅÅ»Ò¥á¡¼¥ë¤ÇÁ÷¿®¤¹¤ë¥á¥Ã¥»¡¼¥¸¤¬¤¢¤ê¤Þ¤»¤ó!";
$net2ftp_messages["You did not supply a From address."] = "º¹½Ð¿Í¥¢¥É¥ì¥¹¤¬Ì¤ÆþÎÏ¤Ç¤¹¡£";
$net2ftp_messages["You did not supply a To address."] = "°¸Àè¥¢¥É¥ì¥¹¤¬Ì¤ÆþÎÏ¤Ç¤¹¡£";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "µ»½ÑÅª¤ÊÌäÂê¤Ç <b>%1\$s</b> ¤Ø¤Î ÅÅ»Ò¥á¡¼¥ë¤ÏÁ÷¿®¤µ¤ì¤Þ¤»¤ó¤Ç¤·¤¿¡£";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "FTP¥µ¡¼¥Ð¾å¤Î¤¢¤Ê¤¿¤Î¥æ¡¼¥¶Ì¾¤È¥Ñ¥¹¥ï¡¼¥É¤òÆþÎÏ¤·¤Æ²¼¤µ¤¤";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "¥Ý¥Ã¥×¥¢¥Ã¥×¥¦¥£¥ó¥É¥¦¤Ë¤¢¤Ê¤¿¤Î¥í¥°¥¤¥ó¾ðÊó¤¬ÆþÎÏ¤µ¤ì¤Æ¤¤¤Þ¤»¤ó¡£<br />²¼¤Î \"¥í¥°¥¤¥ó¥Ú¡¼¥¸¤Ø°ÜÆ°\" ¤ò¥¯¥ê¥Ã¥¯¤·¤Æ¤¯¤À¤µ¤¤¡£";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "¥Õ¥¡¥¤¥ë settings.inc.php ¤Ë¥Ñ¥¹¥ï¡¼¥É¤¬ÀßÄê¤µ¤ì¤Æ¤¤¤Ê¤¤¤¿¤á¡¢net2ftp ´ÉÍýÍÑ¥Ñ¥Í¥ë¤Ø¤Î¥¢¥¯¥»¥¹¤ÏÌµ¸ú¤Ç¤¹¡£¥Õ¥¡¥¤¥ë¤Ë¥Ñ¥¹¥ï¡¼¥É¤òÆþÎÏ¤·¡¢¤³¤Î¥Ú¡¼¥¸¤ò¥ê¥í¡¼¥É¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Please enter your Admin username and password"] = "´ÉÍý¼Ô¤Î¥æ¡¼¥¶Ì¾¤È¥Ñ¥¹¥ï¡¼¥É¤òÆþÎÏ¤·¤Æ²¼¤µ¤¤"; 
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "¥Ý¥Ã¥×¥¢¥Ã¥×¥¦¥£¥ó¥É¥¦¤Ë¤¢¤Ê¤¿¤Î¥í¥°¥¤¥ó¾ðÊó¤¬ÆþÎÏ¤µ¤ì¤Æ¤¤¤Þ¤»¤ó¡£<br />²¼¤Î \"¥í¥°¥¤¥ó¥Ú¡¼¥¸¤Ø°ÜÆ°\" ¤ò¥¯¥ê¥Ã¥¯¤·¤Æ¤¯¤À¤µ¤¤¡£";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "net2ftp ´ÉÍýÍÑ¥Ñ¥Í¥ë¤Î¥æ¡¼¥¶Ì¾¤«¥Ñ¥¹¥ï¡¼¥É¤¬´Ö°ã¤Ã¤Æ¤¤¤Þ¤¹¡£¥æ¡¼¥¶Ì¾¤È¥Ñ¥¹¥ï¡¼¥É¤Ï¥Õ¥¡¥¤¥ë settings.inc.php ¤ÇÀßÄê¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤¹¡£";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "ÀÄ¿§";
$net2ftp_messages["Grey"] = "³¥¿§";
$net2ftp_messages["Black"] = "¹õ¿§";
$net2ftp_messages["Yellow"] = "²«¿§";
$net2ftp_messages["Pastel"] = "Ã¸¿§";

// getMime()
$net2ftp_messages["Directory"] = "¥Õ¥©¥ë¥À";
$net2ftp_messages["Symlink"] = "¥·¥ó¥Ü¥ê¥Ã¥¯¥ê¥ó¥¯";
$net2ftp_messages["ASP script"] = "ASP ¥¹¥¯¥ê¥×¥È";
$net2ftp_messages["Cascading Style Sheet"] = "CSS ¥¹¥¿¥¤¥ë¥·¡¼¥È";
$net2ftp_messages["HTML file"] = "HTML ¥É¥­¥å¥á¥ó¥È";
$net2ftp_messages["Java source file"] = "Java ¥½¡¼¥¹¥Õ¥¡¥¤¥ë";
$net2ftp_messages["JavaScript file"] = "JavaScript ¥Õ¥¡¥¤¥ë";
$net2ftp_messages["PHP Source"] = "PHP ¥½¡¼¥¹";
$net2ftp_messages["PHP script"] = "PHP ¥¹¥¯¥ê¥×¥È";
$net2ftp_messages["Text file"] = "¥×¥ì¡¼¥ó¥Æ¥­¥¹¥È ¥É¥­¥å¥á¥ó¥È";
$net2ftp_messages["Bitmap file"] = "BMP ¥¤¥á¡¼¥¸";
$net2ftp_messages["GIF file"] = "GIF ¥¤¥á¡¼¥¸";
$net2ftp_messages["JPEG file"] = "JPEG ¥¤¥á¡¼¥¸";
$net2ftp_messages["PNG file"] = "PNG ¥¤¥á¡¼¥¸";
$net2ftp_messages["TIF file"] = "TIFF ¥¤¥á¡¼¥¸";
$net2ftp_messages["GIMP file"] = "GIMP ¥Í¥¤¥Æ¥£¥Ö¥¤¥á¡¼¥¸¥Õ¥©¡¼¥Þ¥Ã¥È";
$net2ftp_messages["Executable"] = "¼Â¹Ô²ÄÇ½¥Õ¥¡¥¤¥ë";
$net2ftp_messages["Shell script"] = "¥·¥§¥ë¥¹¥¯¥ê¥×¥È";
$net2ftp_messages["MS Office - Word document"] = "MS Office - Word ¥É¥­¥å¥á¥ó¥È";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - Excel ¥ï¡¼¥¯¥·¡¼¥È";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - PowerPoint ¥×¥ì¥¼¥ó¥Æ¡¼¥·¥ç¥ó";
$net2ftp_messages["MS Office - Access database"] = "MS Office - Access ¥Ç¡¼¥¿¥Ù¡¼¥¹";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - Visio ¥É¥í¡¼¥¤¥ó¥°";
$net2ftp_messages["MS Office - Project file"] = "MS Office - Project ¥Õ¥¡¥¤¥ë";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - Writer 6.0 ¥Æ¥­¥¹¥È¥É¥­¥å¥á¥ó¥È";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - Writer 6.0 ¥Æ¥ó¥×¥ì¡¼¥È";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - Calc 6.0 ¥¹¥×¥ì¥Ã¥É¥·¡¼¥È";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - Calc 6.0 ¥Æ¥ó¥×¥ì¡¼¥È";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - Draw 6.0 ¥É¥í¡¼¥¤¥ó¥°";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - Draw 6.0 ¥Æ¥ó¥×¥ì¡¼¥È";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - Impress 6.0 ¥×¥ì¥¼¥ó¥Æ¡¼¥·¥ç¥ó";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - Impress 6.0 ¥Æ¥ó¥×¥ì¡¼¥È";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - Writer 6.0 ¥Þ¥¹¥¿¡¼¥É¥­¥å¥á¥ó¥È";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - Math 6.0 ¿ô¼°";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - StarWriter 5.x ¥Æ¥­¥¹¥È¥É¥­¥å¥á¥ó¥È";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - StarWriter 5.x ¥Þ¥¹¥¿¡¼¥É¥­¥å¥á¥ó¥È";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - StarCalc 5.x ¥¹¥×¥ì¥Ã¥É¥·¡¼¥È";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - StarDraw 5.x ¥É¥í¡¼¥¤¥ó¥°";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - StarImpress 5.x ¥×¥ì¥¼¥ó¥Æ¡¼¥·¥ç¥ó";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - StarImpress Packed 5.x ¥Õ¥¡¥¤¥ë";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - StarMath 5.x ¿ô¼°";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - StarChart 5.x ¥É¥­¥å¥á¥ó¥È";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - StarMail 5.x mail ¥Õ¥¡¥¤¥ë";
$net2ftp_messages["Adobe Acrobat document"] = "Adobe Acrobat ¥É¥­¥å¥á¥ó¥È";
$net2ftp_messages["ARC archive"] = "ARC ¥¢¡¼¥«¥¤¥Ö";
$net2ftp_messages["ARJ archive"] = "ARJ ¥¢¡¼¥«¥¤¥Ö";
$net2ftp_messages["RPM"] = "RPM ¥Ñ¥Ã¥±¡¼¥¸¥Õ¥¡¥¤¥ë";
$net2ftp_messages["GZ archive"] = "Gzip ¥Õ¥¡¥¤¥ë";
$net2ftp_messages["TAR archive"] = "TAR ¥¢¡¼¥«¥¤¥Ö";
$net2ftp_messages["Zip archive"] = "Zip ¥¢¡¼¥«¥¤¥Ö";
$net2ftp_messages["MOV movie file"] = "MOV ¥Ó¥Ç¥ª";
$net2ftp_messages["MPEG movie file"] = "MPEG ¥Ó¥Ç¥ª";
$net2ftp_messages["Real movie file"] = "¥ê¥¢¥ë¥Ó¥Ç¥ª ¥Õ¥¡¥¤¥ë";
$net2ftp_messages["Quicktime movie file"] = "Quicktime ¥Ó¥Ç¥ª";
$net2ftp_messages["Shockwave flash file"] = "Shockwave flash ¥á¥Ç¥£¥¢";
$net2ftp_messages["Shockwave file"] = "Shockwave ¥Õ¥¡¥¤¥ë";
$net2ftp_messages["WAV sound file"] = "WAV ¥ª¡¼¥Ç¥£¥ª";
$net2ftp_messages["Font file"] = "¥Õ¥©¥ó¥È¥Õ¥¡¥¤¥ë";
$net2ftp_messages["%1\$s File"] = "%1\$s ¥Õ¥¡¥¤¥ë";
$net2ftp_messages["File"] = "¥Õ¥¡¥¤¥ë";

// getAction()
$net2ftp_messages["Back"] = "Ìá¤ë";
$net2ftp_messages["Submit"] = "Á÷¿®";
$net2ftp_messages["Refresh"] = "¹¹¿·";
$net2ftp_messages["Details"] = "¾ÜºÙ";
$net2ftp_messages["Icons"] = "¥¢¥¤¥³¥ó";
$net2ftp_messages["List"] = "¥ê¥¹¥È";
$net2ftp_messages["Logout"] = "¥í¥°¥¢¥¦¥È";
$net2ftp_messages["Help"] = "¥Ø¥ë¥×";
$net2ftp_messages["Bookmark"] = "¥Ö¥Ã¥¯¥Þ¡¼¥¯";
$net2ftp_messages["Save"] = "ÊÝÂ¸";
$net2ftp_messages["Default"] = "¥Ç¥Õ¥©¥ë¥È";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "¥Ø¥ë¥×¥¬¥¤¥É";
$net2ftp_messages["Forums"] = "¥Õ¥©¡¼¥é¥à";
$net2ftp_messages["License"] = "¥é¥¤¥»¥ó¥¹";
$net2ftp_messages["Powered by"] = "¶¡µë¸µ";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "¤¢¤Ê¤¿¤Ï net2ftp ¥Õ¥©¡¼¥é¥à¤Ø¹Ô¤³¤¦¤È¤·¤Æ¤¤¤Þ¤¹¡£¤³¤Î¥Õ¥©¡¼¥é¥à¤Ï net2ftp ¤Ë´ØÏ¢¤¹¤ëÏÃÂê¤À¤±¤Î¤â¤Î¤Ç¤¹ - °ìÈÌÅª¤Ê¥¦¥§¥Ö¥Û¥¹¥Æ¥£¥ó¥°¤Ë¤Ä¤¤¤Æ¤Î¼ÁÌä¤Ï¤ä¤á¤Æ²¼¤µ¤¤¡£";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "´ÉÍýÍÑµ¡Ç½";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "¥Ð¡¼¥¸¥ç¥ó¾ðÊó";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "¤³¤Î¥Ð¡¼¥¸¥ç¥ó¤Î net2ftp ¤ÏºÇ¿·¤Î¤â¤Î¤Ç¤¹¡£";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "ºÇ¿·¥Ð¡¼¥¸¥ç¥ó¾ðÊó¤Ï net2ftp.com ¥µ¡¼¥Ð¤«¤é¸¡º÷¤µ¤ì¤Þ¤»¤ó¤Ç¤·¤¿¡£¤´»ÈÍÑ¤Î¥Ö¥é¥¦¥¶¤¬¡¢ net2ftp.com ¥µ¡¼¥Ð¤«¤é¾®¥Õ¥¡¥¤¥ë¤¬ÆÉ¤ß¹þ¤Þ¤ì¤ë¤Î¤òËÉ»ß¤·¤Æ¤¤¤ë¶²¤ì¤¬¤¢¤ê¤Þ¤¹¡£¥Ö¥é¥¦¥¶¤Î¥»¥­¥å¥ê¥Æ¥£ÀßÄê¤ò³ÎÇ§¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Logging"] = "µ­Ï¿Ãæ";
$net2ftp_messages["Date from:"] = "µ¯ÅÀÆü»þ:";
$net2ftp_messages["to:"] = "½ªÅÀÆü»þ:";
$net2ftp_messages["Empty logs"] = "¥í¥°¤Î¾Ãµî";
$net2ftp_messages["View logs"] = "¥í¥°¤ÎÉ½¼¨";
$net2ftp_messages["Go"] = "°ÜÆ°";
$net2ftp_messages["Setup MySQL tables"] = "MySQL ¥Æ¡¼¥Ö¥ë¤ÎÀßÄê";
$net2ftp_messages["Create the MySQL database tables"] = "MySQL ¥Ç¡¼¥¿¥Ù¡¼¥¹¥Æ¡¼¥Ö¥ë¤ÎºîÀ®";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "´ÉÍýÍÑµ¡Ç½";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "¥Õ¥¡¥¤¥ë %1\$s ¤Î¥Ï¥ó¥É¥ë¤ò³«¤¯¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¤Ç¤·¤¿¡£";
$net2ftp_messages["The file %1\$s could not be opened."] = "¥Õ¥¡¥¤¥ë %1\$s ¤ò³«¤¯¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¤Ç¤·¤¿¡£";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "¥Õ¥¡¥¤¥ë %1\$s ¤Î¥Ï¥ó¥É¥ë¤òÊÄ¤¸¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¤Ç¤·¤¿¡£";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "¥µ¡¼¥Ð <b>%1\$s</b> ¤Ø¤ÎÀÜÂ³¤ÏÀßÄê¤µ¤ì¤Þ¤»¤ó¤Ç¤·¤¿¡£¤¢¤Ê¤¿¤¬ÆþÎÏ¤·¤¿¥Ç¡¼¥¿¥Ù¡¼¥¹¤ÎÀßÄêÃÍ¤ò³ÎÇ§¤·¤Æ¤¯¤À¤µ¤¤¡£";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "¥Ç¡¼¥¿¥Ù¡¼¥¹ <b>%1\$s</b> ¤òÁªÂò¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "SQL¥¯¥¨¥ê¥¨¥é¡¼ <b>%1\$s</b> ¤ò¼Â¹Ô¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¤Ç¤·¤¿¡£";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "SQL¥¯¥¨¥ê <b>%1\$s</b> ¤ÏÀµ¾ï¤Ë¼Â¹Ô¤µ¤ì¤Þ¤·¤¿¡£";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "¤¢¤Ê¤¿¤Î MySQL ¤ÎÀßÄêÃÍ¤òÆþÎÏ¤·¤Æ¤¯¤À¤µ¤¤:";
$net2ftp_messages["MySQL username"] = "MySQL ¥æ¡¼¥¶Ì¾";
$net2ftp_messages["MySQL password"] = "MySQL ¥Ñ¥¹¥ï¡¼¥É";
$net2ftp_messages["MySQL database"] = "MySQL ¥Ç¡¼¥¿¥Ù¡¼¥¹";
$net2ftp_messages["MySQL server"] = "MySQL ¥µ¡¼¥Ð";
$net2ftp_messages["This SQL query is going to be executed:"] = "¤³¤Î SQL¥¯¥¨¥ê¤¬¼Â¹Ô¤µ¤ì¤è¤¦¤È¤·¤Æ¤¤¤Þ¤¹:";
$net2ftp_messages["Execute"] = "¼Â¹Ô";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "»ÈÍÑ¤µ¤ì¤ëÀßÄê:";
$net2ftp_messages["MySQL password length"] = "MySQL ¥Ñ¥¹¥ï¡¼¥ÉÄ¹";
$net2ftp_messages["Results:"] = "·ë²Ì:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "´ÉÍýÍÑµ¡Ç½";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "SQL¥¯¥¨¥ê <b>%1\$s</b> ¤ò¼Â¹Ô¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£";
$net2ftp_messages["No data"] = "¥Ç¡¼¥¿Ìµ¤·";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "´ÉÍýÍÑµ¡Ç½";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "¥Æ¡¼¥Ö¥ë <b>%1\$s</b> ¤ÏÀµ¾ï¤Ë¶õ¤Ë¤Ê¤ê¤Þ¤·¤¿¡£";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "¥Æ¡¼¥Ö¥ë <b>%1\$s</b> ¤ò¶õ¤Ë¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¤Ç¤·¤¿¡£";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "¥Æ¡¼¥Ö¥ë <b>%1\$s</b> ¤ÏÀµ¾ï¤ËºÇÅ¬²½¤µ¤ì¤Þ¤·¤¿¡£";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "¥Æ¡¼¥Ö¥ë <b>%1\$s</b> ¤òºÇÅ¬²½¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¤Ç¤·¤¿¡£";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "³ÈÄ¥µ¡Ç½";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "°ÜÆ°";
$net2ftp_messages["Disabled"] = "Ìµ¸ú";
$net2ftp_messages["Advanced FTP functions"] = "³ÈÄ¥ FTP µ¡Ç½";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "FTP¥µ¡¼¥Ð¤ØÇ¤°Õ¤Î FTP¥³¥Þ¥ó¥É¤òÁ÷¿®¤¹¤ë";
$net2ftp_messages["This function is available on PHP 5 only"] = "¤³¤Îµ¡Ç½¤Ï PHP 5 ¤Ç¤Î¤ßÍ­¸ú¤Ç¤¹";
$net2ftp_messages["Troubleshooting functions"] = "¥È¥é¥Ö¥ë¥·¥å¡¼¥Æ¥£¥ó¥°µ¡Ç½";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "¤³¤Îweb¥µ¡¼¥Ð¤Î net2ftp ¤Î¥È¥é¥Ö¥ë¥·¥å¡¼¥È";
$net2ftp_messages["Troubleshoot an FTP server"] = "FTP¥µ¡¼¥Ð¤Î¥È¥é¥Ö¥ë¥·¥å¡¼¥È";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "net2ftp ¤Î¥ê¥¹¥È²òÀÏ¥ë¡¼¥ë¤ò¥Æ¥¹¥È";
$net2ftp_messages["Translation functions"] = "ËÝÌõµ¡Ç½";
$net2ftp_messages["Introduction to the translation functions"] = "ËÝÌõµ¡Ç½¤Î¼ê°ú¤­";
$net2ftp_messages["Extract messages to translate from code files"] = "¥³¡¼¥É¥Õ¥¡¥¤¥ë¤òËÝÌõ¤¹¤ë¤¿¤á¥á¥Ã¥»¡¼¥¸¤ò¼è¤ê½Ð¤¹";
$net2ftp_messages["Check if there are new or obsolete messages"] = "¥á¥Ã¥»¡¼¥¸¤¬¿·¤·¤¤¤â¤Î¤«µì¼°¤Î¤â¤Î¤«¤òÄ´¤Ù¤ë";

$net2ftp_messages["Beta functions"] = "³«È¯Ãæ¤Îµ¡Ç½";
$net2ftp_messages["Send a site command to the FTP server"] = "FTP¥µ¡¼¥Ð¤Ø¥µ¥¤¥È¥³¥Þ¥ó¥É¤òÁ÷¤ë";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: ¥Ç¥£¥ì¥¯¥È¥ê¤Î¥Ñ¥¹¥ï¡¼¥ÉÊÝ¸î¡¢¥«¥¹¥¿¥à¥¨¥é¡¼¥Ú¡¼¥¸¤ÎºîÀ®";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: SQL¥¯¥¨¥ê¤ò¼Â¹Ô";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "¤³¤Îweb¥µ¡¼¥Ð¤Ç¤Ï¡¢¥µ¥¤¥È¥³¥Þ¥ó¥Éµ¡Ç½¤ÏÍøÍÑ¤Ç¤­¤Þ¤»¤ó¡£";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "¤³¤Îweb¥µ¡¼¥Ð¤Ç¤Ï¡¢ Apacheµ¡Ç½¤ÏÍøÍÑ¤Ç¤­¤Þ¤»¤ó¡£";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "¤³¤Îweb¥µ¡¼¥Ð¤Ç¤Ï¡¢ MySQLµ¡Ç½¤ÏÍøÍÑ¤Ç¤­¤Þ¤»¤ó¡£";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "Í½´ü¤·¤Ê¤¤ state2 ¥¹¥È¥ê¥ó¥°¤Ç¤¹¡£½ªÎ»¤·¤Æ¤¤¤Þ¤¹¡£";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "FTP¥µ¡¼¥Ð¤Î¥È¥é¥Ö¥ë¥·¥å¡¼¥È";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "ÀÜÂ³ÀßÄê:";
$net2ftp_messages["FTP server"] = "FTP ¥µ¡¼¥Ð";
$net2ftp_messages["FTP server port"] = "FTP¥µ¡¼¥Ð¥Ý¡¼¥È";
$net2ftp_messages["Username"] = "¥æ¡¼¥¶Ì¾";
$net2ftp_messages["Password"] = "¥Ñ¥¹¥ï¡¼¥É";
$net2ftp_messages["Password length"] = "¥Ñ¥¹¥ï¡¼¥ÉÄ¹";
$net2ftp_messages["Passive mode"] = "Passive ¥â¡¼¥É";
$net2ftp_messages["Directory"] = "¥Õ¥©¥ë¥À";
$net2ftp_messages["Printing the result"] = "·ë²Ì¤ò½ÐÎÏ¤·¤Æ¤¤¤Þ¤¹";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "FTP¥µ¡¼¥Ð¤ËÀÜÂ³Ãæ: ";
$net2ftp_messages["Logging into the FTP server: "] = "FTP¥µ¡¼¥Ð¤Ë¥í¥°¥¤¥óÃæ: ";
$net2ftp_messages["Setting the passive mode: "] = "¥Ñ¥Ã¥·¥Ö¥â¡¼¥É¤ÎÀßÄêÃæ: ";
$net2ftp_messages["Getting the FTP server system type: "] = "FTP¥µ¡¼¥Ð¤Î¥·¥¹¥Æ¥à¼ïÊÌ¤ò¼èÆÀÃæ: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "¥Ç¥£¥ì¥¯¥È¥ê %1\$s ¤ØÊÑ¹¹Ãæ: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "FTP¥µ¡¼¥Ð¤«¤é¤Î¥Ç¥£¥ì¥¯¥È¥ê: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤ÎÀ¸¥ê¥¹¥È¤ò¼èÆÀÃæ: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤ÎÀ¸¥ê¥¹¥È¤òºÆ¼èÆÀÃæ: ";
$net2ftp_messages["Closing the connection: "] = "ÀÜÂ³¤òÀÚÃÇÃæ: ";
$net2ftp_messages["Raw list of directories and files:"] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤ÎÀ¸¥ê¥¹¥È:";
$net2ftp_messages["Parsed list of directories and files:"] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Î²òÀÏºÑ¤ß¥ê¥¹¥È:";

$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "¼ºÇÔ";

} // end advanced_ftpserver


// -------------------------------------------------------------------------
// Advanced_parsing module
if ($net2ftp_globals["state"] == "advanced_parsing") {
// -------------------------------------------------------------------------

$net2ftp_messages["Test the net2ftp list parsing rules"] = "net2ftp ¤Î¥ê¥¹¥È²òÀÏ¥ë¡¼¥ë¤ò¥Æ¥¹¥È";
$net2ftp_messages["Sample input"] = "¥µ¥ó¥×¥ëÆþÎÏ";
$net2ftp_messages["Parsed output"] = "²òÀÏ¸å¤Î½ÐÎÏ";

} // end advanced_parsing


// -------------------------------------------------------------------------
// Advanced_webserver module
if ($net2ftp_globals["state"] == "advanced_webserver") {
// -------------------------------------------------------------------------

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "¤¢¤Ê¤¿¤Î net2ftp Æ³Æþ¤ò¥È¥é¥Ö¥ë¥·¥å¡¼¥È";
$net2ftp_messages["Printing the result"] = "·ë²Ì¤ò½ÐÎÏ¤·¤Æ¤¤¤Þ¤¹";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "PHP ¤Î FTP¥â¥¸¥å¡¼¥ë¤¬¥¤¥ó¥¹¥È¡¼¥ë¤µ¤ì¤Æ¤¤¤ë¤«Ä´¤Ù¤Æ¤¤¤Þ¤¹: ";
$net2ftp_messages["yes"] = "¤Ï¤¤";
$net2ftp_messages["no - please install it!"] = "¤¤¤¤¤¨ - ¥¤¥ó¥¹¥È¡¼¥ë¤·¤Æ¤¯¤À¤µ¤¤!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "web¥µ¡¼¥Ð¾å¤Î¥Ç¥£¥ì¥¯¥È¥ê¤Îµö²Ä¾ðÊó¤òÄ´¤Ù¤Æ¤¤¤Þ¤¹: /temp ¥Õ¥©¥ë¥À¤Ë¾®¤µ¤Ê¥Õ¥¡¥¤¥ë¤¬ºîÀ®¤µ¤ì¤Þ¤¹¤¬¡¢¸å¤Çºï½ü¤µ¤ì¤Þ¤¹¡£";
$net2ftp_messages["Creating filename: "] = "¥Õ¥¡¥¤¥ëÌ¾¤òºîÀ®Ãæ: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "OK. ¥Õ¥¡¥¤¥ëÌ¾: %1\$s";
$net2ftp_messages["not OK"] = "¼ºÇÔ";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "¼ºÇÔ¡£¥Ç¥£¥ì¥¯¥È¥ê %1\$s ¤Îµö²Ä¾ðÊó¤ò³ÎÇ§¤·¤Æ¤¯¤À¤µ¤¤";
$net2ftp_messages["Opening the file in write mode: "] = "¥Õ¥¡¥¤¥ë¤ò½ñ¤­¹þ¤ß¥â¡¼¥É¤Ç³«¤¤¤Æ¤¤¤Þ¤¹: ";
$net2ftp_messages["Writing some text to the file: "] = "¥Õ¥¡¥¤¥ë¤Ë¥Æ¥­¥¹¥È¤ò½ñ¤­¹þ¤ó¤Ç¤¤¤Þ¤¹: ";
$net2ftp_messages["Closing the file: "] = "¥Õ¥¡¥¤¥ë¤òÊÄ¤¸¤Æ¤¤¤Þ¤¹: ";
$net2ftp_messages["Deleting the file: "] = "¥Õ¥¡¥¤¥ë¤òºï½ü¤·¤Æ¤¤¤Þ¤¹: ";

$net2ftp_messages["Testing the FTP functions"] = "FTPµ¡Ç½¤ò¥Æ¥¹¥È¤·¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["Connecting to a test FTP server: "] = "¥Æ¥¹¥ÈÍÑFTP¥µ¡¼¥Ð¤ËÀÜÂ³Ãæ: ";
$net2ftp_messages["Connecting to the FTP server: "] = "FTP¥µ¡¼¥Ð¤ËÀÜÂ³Ãæ: ";
$net2ftp_messages["Logging into the FTP server: "] = "FTP¥µ¡¼¥Ð¤Ë¥í¥°¥¤¥óÃæ: ";
$net2ftp_messages["Setting the passive mode: "] = "¥Ñ¥Ã¥·¥Ö¥â¡¼¥É¤ÎÀßÄêÃæ: ";
$net2ftp_messages["Getting the FTP server system type: "] = "FTP¥µ¡¼¥Ð¤Î¥·¥¹¥Æ¥à¼ïÊÌ¤ò¼èÆÀÃæ: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "¥Ç¥£¥ì¥¯¥È¥ê %1\$s ¤ØÊÑ¹¹Ãæ: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "FTP¥µ¡¼¥Ð¤«¤é¤Î¥Ç¥£¥ì¥¯¥È¥ê: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤ÎÀ¸¥ê¥¹¥È¤ò¼èÆÀÃæ: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤ÎÀ¸¥ê¥¹¥È¤òºÆ¼èÆÀÃæ: ";
$net2ftp_messages["Closing the connection: "] = "ÀÜÂ³¤òÀÚÃÇÃæ: ";
$net2ftp_messages["Raw list of directories and files:"] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤ÎÀ¸¥ê¥¹¥È:";
$net2ftp_messages["Parsed list of directories and files:"] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Î²òÀÏºÑ¤ß¥ê¥¹¥È:";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "¼ºÇÔ";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "¤³¤Î¥ê¥ó¥¯¤ò¥Ö¥Ã¥¯¥Þ¡¼¥¯¤ËÄÉ²Ã¤·¤Þ¤¹:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: ¥ê¥ó¥¯¤ò±¦¥¯¥ê¥Ã¥¯¤·\"¤ªµ¤¤ËÆþ¤ê¤ËÄÉ²Ã...\"¤òÁªÂò";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: ¥ê¥ó¥¯¤ò±¦¥¯¥ê¥Ã¥¯¤·\"Bookmark This Link...\"¤òÁªÂò";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "Ãí¼á: ¤³¤Î¥Ö¥Ã¥¯¥Þ¡¼¥¯¤òÍøÍÑ¤¹¤ë¤È¡¢¥Ý¥Ã¥×¥¢¥Ã¥×¥¦¥£¥ó¥É¥¦¤Ç¥æ¡¼¥¶Ì¾¤È¥Ñ¥¹¥ï¡¼¥É¤òÆþÎÏ¤·¤Þ¤¹¡£";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "¥Ç¥£¥ì¥¯¥È¥ê¤òÁªÂò";
$net2ftp_messages["Please wait..."] = "¤·¤Ð¤é¤¯¤ªÂÔ¤Á²¼¤µ¤¤...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "Ì¾Á°¤Ë \' ¤ò´Þ¤à¥Ç¥£¥ì¥¯¥È¥ê¤ÏÀµ¤·¤¯É½¼¨¤µ¤ì¤Þ¤»¤ó¡£ºï½ü¤µ¤ì¤Æ¤·¤Þ¤¤¤Þ¤¹¡£Ìá¤Ã¤ÆÊÌ¤Î¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê¤òÁªÂò¤·¤Æ¤¯¤À¤µ¤¤¡£";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "1Æü¤ÎÅ¾Á÷À©¸Â¤ËÅþÃ£: ¥Ç¡¼¥¿¤òÅ¾Á÷¤¹¤ë¤³¤È¤Ï¤Ç¤­¤Þ¤»¤ó";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "web¥µ¡¼¥Ð¤ò³§ÍÍ¤Ç¸øÊ¿¤ËÍøÍÑ¤¹¤ë¤³¤È¤òÊÝ¾ã¤¹¤ë¤¿¤á¡¢¥Ç¡¼¥¿¤ÎÅ¾Á÷ÎÌ¤È¥¹¥¯¥ê¥×¥È¤Î¼Â¹Ô»þ´Ö¤Ï¥æ¡¼¥¶¤´¤È¤Ë1ÆüÃ±°Ì¤ÇÀ©¸Â¤µ¤ì¤Æ¤¤¤Þ¤¹¡£¾å¸Â¤ËÅþÃ£¤·¤¿¾ì¹ç¤â FTP¥µ¡¼¥Ð¤òÉ½¼¨¤¹¤ë¤³¤È¤Ï¤Ç¤­¤Þ¤¹¤¬¡¢¥Ç¡¼¥¿¤ÎÁ÷¼õ¿®¤Ï¤Ç¤­¤Þ¤»¤ó¡£";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "ÌµÀ©¸Â¤Ë»ÈÍÑ¤·¤¿¤¤Êý¤Ï¡¢¤´¼«Ê¬¤Î web¥µ¡¼¥Ð¤Ë net2ftp ¤ò¥¤¥ó¥¹¥È¡¼¥ë¤·¤Æ²¼¤µ¤¤¡£";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "¿·µ¬¥Ç¥£¥ì¥¯¥È¥ê";
$net2ftp_messages["New file"] = "¿·µ¬¥Õ¥¡¥¤¥ë";
$net2ftp_messages["HTML templates"] = "HTML ¥Æ¥ó¥×¥ì¡¼¥È";
$net2ftp_messages["Upload"] = "¥¢¥Ã¥×¥í¡¼¥É";
$net2ftp_messages["Java Upload"] = "Java ¥¢¥Ã¥×¥í¡¼¥É";
$net2ftp_messages["Flash Upload"] = "Flash Upload";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Advanced"] = "³ÈÄ¥µ¡Ç½";
$net2ftp_messages["Copy"] = "¥³¥Ô¡¼";
$net2ftp_messages["Move"] = "°ÜÆ°";
$net2ftp_messages["Delete"] = "ºï½ü";
$net2ftp_messages["Rename"] = "Ì¾Á°¤ÎÊÑ¹¹";
$net2ftp_messages["Chmod"] = "µö²Ä¾ðÊó¤ÎÊÑ¹¹";
$net2ftp_messages["Download"] = "¥À¥¦¥ó¥í¡¼¥É";
$net2ftp_messages["Unzip"] = "Unzip";
$net2ftp_messages["Zip"] = "°µ½Ì";
$net2ftp_messages["Size"] = "¥µ¥¤¥º";
$net2ftp_messages["Search"] = "¸¡º÷";
$net2ftp_messages["Go to the parent directory"] = "¤Ò¤È¤Ä¾å¤Ø°ÜÆ°";
$net2ftp_messages["Go"] = "°ÜÆ°";
$net2ftp_messages["Transform selected entries: "] = "ÁªÂò¤µ¤ì¤¿¥¨¥ó¥È¥ê¤Î: ";
$net2ftp_messages["Transform selected entry: "] = "Transform selected entry: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "¥Ç¥£¥ì¥¯¥È¥ê %1\$s ¤Î²¼¤Ë¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê¤òºîÀ®";
$net2ftp_messages["Create a new file in directory %1\$s"] = "¥Ç¥£¥ì¥¯¥È¥ê %1\$s ¤Ë¿·¤·¤¤¥Õ¥¡¥¤¥ë¤òºîÀ®";
$net2ftp_messages["Create a website easily using ready-made templates"] = "´ûÀ®¤Î¥Æ¥ó¥×¥ì¡¼¥È¤òÍøÍÑ¤·¤Æ´ÊÃ±¤Ë web¥µ¥¤¥È¤òºîÀ®";
$net2ftp_messages["Upload new files in directory %1\$s"] = "¥Ç¥£¥ì¥¯¥È¥ê %1\$s Æâ¤Ë¿·¤·¤¤¥Õ¥¡¥¤¥ë¤ò¥¢¥Ã¥×¥í¡¼¥É";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Java¥¢¥×¥ì¥Ã¥È¤òÍøÍÑ¤·¤Æ¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤ò¥¢¥Ã¥×¥í¡¼¥É";
$net2ftp_messages["Upload files using a Flash applet"] = "Upload files using a Flash applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Install software packages (requires PHP on web server)";
$net2ftp_messages["Go to the advanced functions"] = "³ÈÄ¥µ¡Ç½¤Î²èÌÌ¤ò³«¤¯";
$net2ftp_messages["Copy the selected entries"] = "ÁªÂò¤µ¤ì¤¿¥¨¥ó¥È¥ê¤ò¥³¥Ô¡¼";
$net2ftp_messages["Move the selected entries"] = "ÁªÂò¤µ¤ì¤¿¥¨¥ó¥È¥ê¤ò°ÜÆ°";
$net2ftp_messages["Delete the selected entries"] = "ÁªÂò¤µ¤ì¤¿¥¨¥ó¥È¥ê¤òºï½ü";
$net2ftp_messages["Rename the selected entries"] = "ÁªÂò¤µ¤ì¤¿¥¨¥ó¥È¥ê¤ÎÌ¾Á°¤òÊÑ¹¹";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "ÁªÂò¤µ¤ì¤¿¥¨¥ó¥È¥ê¤Îµö²Ä¾ðÊó¤òÊÑ¹¹¡ÊUnix/Linux/BSD ¥µ¡¼¥Ð¤Î¤ßÍ­¸ú¡Ë";
$net2ftp_messages["Download a zip file containing all selected entries"] = "ÁªÂò¤µ¤ì¤¿¥¨¥ó¥È¥ê¤ò¤¹¤Ù¤Æ´Þ¤à°µ½Ì¥Õ¥¡¥¤¥ë¤ò¥À¥¦¥ó¥í¡¼¥É";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Unzip the selected archives on the FTP server";
$net2ftp_messages["Zip the selected entries to save or email them"] = "ÁªÂò¤µ¤ì¤¿¥¨¥ó¥È¥ê¤ò°µ½Ì";
$net2ftp_messages["Calculate the size of the selected entries"] = "ÁªÂò¤µ¤ì¤¿¥¨¥ó¥È¥ê¤Î¥Õ¥¡¥¤¥ë¥µ¥¤¥º¤ò·×»»";
$net2ftp_messages["Find files which contain a particular word"] = "ÆÃÄê¤ÎÊ¸»úÎó¤ò´Þ¤à¥Õ¥¡¥¤¥ë¤ò¸¡º÷";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "¥¯¥ê¥Ã¥¯¤¹¤ë¤È %1\$s ¤Ç¹ß½ç¥½¡¼¥È";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "¥¯¥ê¥Ã¥¯¤¹¤ë¤È %1\$s ¤Ç¾º½ç¥½¡¼¥È";
$net2ftp_messages["Ascending order"] = "¾º½ç";
$net2ftp_messages["Descending order"] = "¹ß½ç";
$net2ftp_messages["Upload files"] = "¥Õ¥¡¥¤¥ë¤Î¥¢¥Ã¥×¥í¡¼¥É";
$net2ftp_messages["Up"] = "¾å¤Ø";
$net2ftp_messages["Click to check or uncheck all rows"] = "¥¯¥ê¥Ã¥¯¤¹¤ë¤ÈÁ´¹àÌÜ¤ÎÁªÂò¡¿ÈóÁªÂò¤òÀÚ¤êÂØ¤¨";
$net2ftp_messages["All"] = "Á´¤Æ";
$net2ftp_messages["Name"] = "Ì¾Á°";
$net2ftp_messages["Type"] = "¥¿¥¤¥×";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "½êÍ­¼Ô";
$net2ftp_messages["Group"] = "¥°¥ë¡¼¥×";
$net2ftp_messages["Perms"] = "µö²Ä¾ðÊó";
$net2ftp_messages["Mod Time"] = "¹¹¿·Æü»þ";
$net2ftp_messages["Actions"] = "Áàºî";
$net2ftp_messages["Select the directory %1\$s"] = "¥Ç¥£¥ì¥¯¥È¥ê %1\$s ¤òÁªÂò";
$net2ftp_messages["Select the file %1\$s"] = "¥Õ¥¡¥¤¥ë %1\$s ¤òÁªÂò";
$net2ftp_messages["Select the symlink %1\$s"] = "¥·¥ó¥Ü¥ê¥Ã¥¯¥ê¥ó¥¯ %1\$s ¤òÁªÂò";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê %1\$s ¤Ø°ÜÆ°";
$net2ftp_messages["Download the file %1\$s"] = "¥Õ¥¡¥¤¥ë %1\$s ¤Î¥À¥¦¥ó¥í¡¼¥É";
$net2ftp_messages["Follow symlink %1\$s"] = "Follow symlink %1\$s";
$net2ftp_messages["View"] = "±ÜÍ÷";
$net2ftp_messages["Edit"] = "ÊÔ½¸";
$net2ftp_messages["Update"] = "¹¹¿·";
$net2ftp_messages["Open"] = "³«¤¯";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "¥Õ¥¡¥¤¥ë %1\$s ¤Î¥½¡¼¥¹¥³¡¼¥É¤ò¿§ÉÕ¤­¤ÇÉ½¼¨";
$net2ftp_messages["Edit the source code of file %1\$s"] = "¥Õ¥¡¥¤¥ë %1\$s ¤Î¥½¡¼¥¹¥³¡¼¥É¤òÊÔ½¸";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "¿·¤·¤¤¥Ð¡¼¥¸¥ç¥ó¤Î¥Õ¥¡¥¤¥ë %1\$s ¤ò¥¢¥Ã¥×¥í¡¼¥É¤·ÊÑ¹¹ÉôÊ¬¤òÊ»¹ç";
$net2ftp_messages["View image %1\$s"] = "²èÁü %1\$s ¤ÎÉ½¼¨";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "¥Õ¥¡¥¤¥ë %1\$s ¤ò¤¢¤Ê¤¿¤Î HTTP web¥µ¡¼¥Ð¤ÇÉ½¼¨";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(Ãí¼á: ¤´¼«Ê¬¤Î¥É¥á¥¤¥ó¤ò½êÍ­¤·¤Æ¤ª¤é¤ì¤Ê¤¤Êý¤Ë¤Ï¡¢¤³¤Î¥ê¥ó¥¯¤Ïµ¡Ç½¤·¤Þ¤»¤ó¡£)";
$net2ftp_messages["This folder is empty"] = "¤³¤Î¥Õ¥©¥ë¥À¤Ï¶õ¤Ç¤¹";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "¥Ç¥£¥ì¥¯¥È¥ê";
$net2ftp_messages["Files"] = "¥Õ¥¡¥¤¥ë";
$net2ftp_messages["Symlinks"] = "¥·¥ó¥Ü¥ê¥Ã¥¯¥ê¥ó¥¯";
$net2ftp_messages["Unrecognized FTP output"] = "ÉÔÌÀ¤Ê FTP ½ÐÎÏ";
$net2ftp_messages["Number"] = "¿ô";
$net2ftp_messages["Size"] = "¥µ¥¤¥º";
$net2ftp_messages["Skipped"] = "¾ÊÎ¬";
$net2ftp_messages["Data transferred from this IP address today"] = "Data transferred from this IP address today";
$net2ftp_messages["Data transferred to this FTP server today"] = "Data transferred to this FTP server today";

// printLocationActions()
$net2ftp_messages["Language:"] = "¸À¸ì:";
$net2ftp_messages["Skin:"] = "¥Æ¡¼¥Þ:";
$net2ftp_messages["View mode:"] = "É½¼¨¥â¡¼¥É:";
$net2ftp_messages["Directory Tree"] = "¥Ä¥ê¡¼É½¼¨";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "%1\$s ¤ò¿·¤·¤¤¥¦¥£¥ó¥É¥¦¤Ç¼Â¹Ô";
$net2ftp_messages["This file is not accessible from the web"] = "¤³¤Î¥Õ¥¡¥¤¥ë¤Ï web¾å¤«¤é¥¢¥¯¥»¥¹¤Ç¤­¤Þ¤»¤ó";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "¥À¥Ö¥ë¥¯¥ê¥Ã¥¯¤Ç¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê¤Ø°ÜÆ°:";
$net2ftp_messages["Choose"] = "ÁªÂò";
$net2ftp_messages["Up"] = "¾å¤Ø";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "ÁªÂò¤µ¤ì¤¿¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Î¥µ¥¤¥º";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "ÁªÂò¤µ¤ì¤¿¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Î¹ç·×¥µ¥¤¥º:";
$net2ftp_messages["The number of files which were skipped is:"] = "¾ÊÎ¬¤µ¤ì¤¿¥Õ¥¡¥¤¥ë¤Î¸Ä¿ô:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Îµö²Ä¾ðÊó¤ÎÊÑ¹¹";
$net2ftp_messages["Set all permissions"] = "Á´¤ÆÀßÄê";
$net2ftp_messages["Read"] = "ÆÉ¤ß¼è¤ê";
$net2ftp_messages["Write"] = "½ñ¤­¹þ¤ß";
$net2ftp_messages["Execute"] = "¼Â¹Ô";
$net2ftp_messages["Owner"] = "½êÍ­¼Ô";
$net2ftp_messages["Group"] = "¥°¥ë¡¼¥×";
$net2ftp_messages["Everyone"] = "Á´°÷";
$net2ftp_messages["To set all permissions to the same values, enter those permissions above and click on the button \"Set all permissions\""] = "Á´¤Æ¤Îµö²Ä¾ðÊó¤òÆ±¤¸ÃÍ¤Ë¤¹¤ë¤Ë¤Ï¡¢¾å¤ÎÍó¤Çµö²Ä¾ðÊó¤òÆþÎÏ¤· \"Á´¤ÆÀßÄê\" ¥Ü¥¿¥ó¤ò¥¯¥ê¥Ã¥¯¤·¤Æ¤¯¤À¤µ¤¤";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤Îµö²Ä¾ðÊó¤ÎÊÑ¹¹: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Îµö²Ä¾ðÊó¤ÎÊÑ¹¹: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "¥·¥ó¥Ü¥ê¥Ã¥¯¥ê¥ó¥¯ <b>%1\$s</b> ¤Îµö²Ä¾ðÊó¤ÎÊÑ¹¹: ";
$net2ftp_messages["Chmod value"] = "ÀßÄêÃÍ";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "¤³¤Î¥Ç¥£¥ì¥¯¥È¥ê¤ÎÃæ¤Î¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê¤âÊÑ¹¹";
$net2ftp_messages["Chmod also the files within this directory"] = "¤³¤Î¥Ç¥£¥ì¥¯¥È¥ê¤ÎÃæ¤Î¥Õ¥¡¥¤¥ë¤âÊÑ¹¹";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "ÀßÄêÃÍ¥¨¥é¡¼: <b>%1\$s</b> ¤Ï 000 ¡Á 777 ¤ÎÃÍ¤Ç¤Ï¤¢¤ê¤Þ¤»¤ó¡£ºÆÀßÄê¤·¤Æ²¼¤µ¤¤¡£";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "¥Ç¥£¥ì¥¯¥È¥ê¤òÁªÂò";
$net2ftp_messages["Copy directories and files"] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Î¥³¥Ô¡¼";
$net2ftp_messages["Move directories and files"] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Î°ÜÆ°";
$net2ftp_messages["Delete directories and files"] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Îºï½ü";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "¤³¤ì¤é¤Î¥Õ¥¡¥¤¥ë¤òºï½ü¤·¤Æ¤â¤è¤í¤·¤¤¤Ç¤¹¤«?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "ÁªÂò¤µ¤ì¤¿¥Ç¥£¥ì¥¯¥È¥êÆâ¤Î¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤âºï½ü¤µ¤ì¤Þ¤¹!";
$net2ftp_messages["Set all targetdirectories"] = "Á´¤Æ¤ÎÂÐ¾Ý¥Ç¥£¥ì¥¯¥È¥ê¤òÁªÂò";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "¶¦ÄÌ¤ÎÂÐ¾Ý¥Ç¥£¥ì¥¯¥È¥ê¤òÀßÄê¤¹¤ë¤Ë¤Ï¡¢¾å¤Î¥Æ¥­¥¹¥È¥Ü¥Ã¥¯¥¹¤ËÂÐ¾Ý¥Ç¥£¥ì¥¯¥È¥ê¤òÆþÎÏ¤· \"Á´¤Æ¤ÎÂÐ¾Ý¥Ç¥£¥ì¥¯¥È¥ê¤òÁªÂò\" ¥Ü¥¿¥ó¤ò¥¯¥ê¥Ã¥¯¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Ãí¼á: ¥³¥Ô¡¼¤¹¤ëÁ°¤Ë¡¢ÂÐ¾Ý¥Ç¥£¥ì¥¯¥È¥ê¤¬´û¤ËÂ¸ºß¤·¤Æ¤¤¤Ê¤±¤ì¤Ð¤Ê¤ê¤Þ¤»¤ó¡£";
$net2ftp_messages["Different target FTP server:"] = "ÊÌ¤ÎÂÐ¾Ý FTP ¥µ¡¼¥Ð:";
$net2ftp_messages["Username"] = "¥æ¡¼¥¶Ì¾";
$net2ftp_messages["Password"] = "¥Ñ¥¹¥ï¡¼¥É";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "Æ±¤¸ FTP ¥µ¡¼¥Ð¤Ë¥Õ¥¡¥¤¥ë¤ò¥³¥Ô¡¼¤¹¤ë¤È¤­¤Ï¡¢²¿¤âÆþÎÏ¤·¤Ê¤¤¤Ç²¼¤µ¤¤¡£";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "ÊÌ¤Î FTP ¥µ¡¼¥Ð¤Ë¥Õ¥¡¥¤¥ë¤ò¥³¥Ô¡¼¤¹¤ë¤È¤­¤Ï¡¢¤¢¤Ê¤¿¤Î¥í¥°¥¤¥ó¾ðÊó¤òÆþÎÏ¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "Æ±¤¸ FTP ¥µ¡¼¥Ð¤Ë¥Õ¥¡¥¤¥ë¤ò°ÜÆ°¤¹¤ë¤È¤­¤Ï¡¢²¿¤âÆþÎÏ¤·¤Ê¤¤¤Ç²¼¤µ¤¤¡£";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "ÊÌ¤Î FTP ¥µ¡¼¥Ð¤Ë¥Õ¥¡¥¤¥ë¤ò°ÜÆ°¤¹¤ë¤È¤­¤Ï¡¢¤¢¤Ê¤¿¤Î¥í¥°¥¤¥ó¾ðÊó¤òÆþÎÏ¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤Î¥³¥Ô¡¼Àè:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤Î°ÜÆ°Àè:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Î¥³¥Ô¡¼Àè:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤Î°ÜÆ°Àè:";
$net2ftp_messages["File <b>%1\$s</b>"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "¥·¥ó¥Ü¥ê¥Ã¥¯¥ê¥ó¥¯ <b>%1\$s</b> ¤Î¥³¥Ô¡¼Àè:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "¥·¥ó¥Ü¥ê¥Ã¥¯¥ê¥ó¥¯ <b>%1\$s</b> ¤Î°ÜÆ°Àè:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "¥·¥ó¥Ü¥ê¥Ã¥¯¥ê¥ó¥¯ <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "ÂÐ¾Ý¥Ç¥£¥ì¥¯¥È¥ê:";
$net2ftp_messages["Target name:"] = "ÂÐ¾ÝÌ¾:";
$net2ftp_messages["Processing the entries:"] = "¥¨¥ó¥È¥ê¤ò½èÍý¤·¤Æ¤¤¤Þ¤¹:";

} // end copymovedelete


// -------------------------------------------------------------------------
// Download file module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// EasyWebsite module
if ($net2ftp_globals["state"] == "easyWebsite") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create a website in 4 easy steps"] = "web¥µ¥¤¥È¤ò´ÊÃ±¤Ê4¤Ä¤Î¥¹¥Æ¥Ã¥×¤ÇºîÀ®";
$net2ftp_messages["Template overview"] = "¥Æ¥ó¥×¥ì¡¼¥È°ìÍ÷";
$net2ftp_messages["Template details"] = "¥Æ¥ó¥×¥ì¡¼¥È¾ÜºÙ";
$net2ftp_messages["Files are copied"] = "¥³¥Ô¡¼¤·¤¿¥Õ¥¡¥¤¥ë";
$net2ftp_messages["Edit your pages"] = "¥Ú¡¼¥¸¤òÊÔ½¸¤¹¤ë";

// Screen 1 - printTemplateOverview
$net2ftp_messages["Click on the image to view the details of a template."] = "²èÁü¤ò¥¯¥ê¥Ã¥¯¤¹¤ë¤È¥Æ¥ó¥×¥ì¡¼¥È¤Î¾ÜºÙ¤òÉ½¼¨¤·¤Þ¤¹¡£";
$net2ftp_messages["Back to the Browse screen"] = "¥Ö¥é¥¦¥¶²èÌÌ¤ËÌá¤ë";
$net2ftp_messages["Template"] = "¥Æ¥ó¥×¥ì¡¼¥È";
$net2ftp_messages["Copyright"] = "Copyright";
$net2ftp_messages["Click on the image to view the details of this template"] = "²èÁü¤ò¥¯¥ê¥Ã¥¯¤¹¤ë¤È¤³¤Î¥Æ¥ó¥×¥ì¡¼¥È¤Î¾ÜºÙ¤òÉ½¼¨¤·¤Þ¤¹";

// Screen 2 - printTemplateDetails
$net2ftp_messages["The template files will be copied to your FTP server. Existing files with the same filename will be overwritten. Do you want to continue?"] = "¥Æ¥ó¥×¥ì¡¼¥È¥Õ¥¡¥¤¥ë¤¬¤¢¤Ê¤¿¤Î FTP¥µ¡¼¥Ð¤Ë¥³¥Ô¡¼¤µ¤ì¤Þ¤¹¡£Æ±Ì¾¤Î¥Õ¥¡¥¤¥ë¤¬Â¸ºß¤¹¤ë¤È¾å½ñ¤­¤·¤Þ¤¹¡£Â³¤±¤Þ¤¹¤«¡©";
$net2ftp_messages["Install template to directory: "] = "¥Æ¥ó¥×¥ì¡¼¥È¤ò¥Ç¥£¥ì¥¯¥È¥ê¤Ë¥¤¥ó¥¹¥È¡¼¥ë: ";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Size"] = "¥µ¥¤¥º";
$net2ftp_messages["Preview page"] = "¥×¥ì¥Ó¥å¡¼";
$net2ftp_messages["opens in a new window"] = "¿·¤·¤¤¥¦¥£¥ó¥É¥¦¤Ç³«¤¯";

// Screen 3
$net2ftp_messages["Please wait while the template files are being transferred to your server: "] = "¥Æ¥ó¥×¥ì¡¼¥È¥Õ¥¡¥¤¥ë¤¬¤¢¤Ê¤¿¤Î¥µ¡¼¥Ð¤ØÅ¾Á÷¤µ¤ì¤Þ¤¹¡£¤·¤Ð¤é¤¯¤ªÂÔ¤Á²¼¤µ¤¤: ";
$net2ftp_messages["Done."] = "´°Î»¡£";
$net2ftp_messages["Continue"] = "Â³¤±¤ë";

// Screen 4 - printEasyAdminPanel
$net2ftp_messages["Edit page"] = "¥Ú¡¼¥¸¤ÎÊÔ½¸";
$net2ftp_messages["Browse the FTP server"] = "FTP¥µ¡¼¥Ð¤ò±ÜÍ÷";
$net2ftp_messages["Add this link to your favorites to return to this page later on!"] = "¥ê¥ó¥¯¤ò¤ªµ¤¤ËÆþ¤ê¤ËÄÉ²Ã¤·¤Æ¡¢¸å¤Ç¤Þ¤¿¤³¤Î¥Ú¡¼¥¸¤ËÌá¤ì¤ë¤è¤¦¤Ë¤¹¤ë!";
$net2ftp_messages["Edit website at %1\$s"] = "web¥µ¥¤¥È %1\$s ¤òÊÔ½¸";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: ¥ê¥ó¥¯¤ò±¦¥¯¥ê¥Ã¥¯¤·\"¤ªµ¤¤ËÆþ¤ê¤ËÄÉ²Ã...\"¤òÁªÂò";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: ¥ê¥ó¥¯¤ò±¦¥¯¥ê¥Ã¥¯¤·\"Bookmark This Link...\"¤òÁªÂò";

// ftp_copy_local2ftp
$net2ftp_messages["WARNING: Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing..."] = "·Ù¹ð: ¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤òºîÀ®¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£¤â¤¦¤¹¤Ç¤ËÂ¸ºß¤·¤Æ¤¤¤Þ¤¹¡£Â³¹Ô¤·¤Þ¤¹...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "ÂÐ¾Ý¤Î¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤òºîÀ®";
$net2ftp_messages["WARNING: Unable to copy the file <b>%1\$s</b>. Continuing..."] = "·Ù¹ð: ¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤ò¥³¥Ô¡¼¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¡£Â³¹Ô¤·¤Þ¤¹...";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "¥Õ¥¡¥¤¥ë <b>%1\$s</b> ¤ò¥³¥Ô¡¼";
}


// -------------------------------------------------------------------------
// Edit module
if ($net2ftp_globals["state"] == "edit") {
// -------------------------------------------------------------------------

// /modules/edit/edit.inc.php
$net2ftp_messages["Unable to open the template file"] = "¥Æ¥ó¥×¥ì¡¼¥È¥Õ¥¡¥¤¥ë¤¬³«¤±¤Þ¤»¤ó";
$net2ftp_messages["Unable to read the template file"] = "¥Æ¥ó¥×¥ì¡¼¥È¥Õ¥¡¥¤¥ë¤¬ÆÉ¤ß¹þ¤á¤Þ¤»¤ó";
$net2ftp_messages["Please specify a filename"] = "¥Õ¥¡¥¤¥ëÌ¾¤ò»ØÄê¤·¤Æ¤¯¤À¤µ¤¤";
$net2ftp_messages["Status: This file has not yet been saved"] = "¥¹¥Æ¡¼¥¿¥¹: ¤³¤Î¥Õ¥¡¥¤¥ë¤Ï¤Þ¤ÀÊÝÂ¸¤µ¤ì¤Æ¤¤¤Þ¤»¤ó";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "¥¹¥Æ¡¼¥¿¥¹: %2\$s ¥â¡¼¥É¤Ç <b>%1\$s</b> ¾å¤ËÊÝÂ¸¤µ¤ì¤Þ¤·¤¿";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "¥¹¥Æ¡¼¥¿¥¹: <b>¤³¤Î¥Õ¥¡¥¤¥ë¤ÏÊÝÂ¸¤Ç¤­¤Þ¤»¤ó</b>";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "¥Ç¥£¥ì¥¯¥È¥ê: ";
$net2ftp_messages["File: "] = "¥Õ¥¡¥¤¥ë: ";
$net2ftp_messages["New file name: "] = "¿·¤·¤¤¥Õ¥¡¥¤¥ëÌ¾: ";
$net2ftp_messages["Character encoding: "] = "Character encoding: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "Ãí¼á: ¥Æ¥­¥¹¥È¥¨¥ê¥¢¥¿¥¤¥×¤òÊÑ¹¹¤¹¤ë¤È¡¢ÊÑ¹¹²Õ½ê¤ÏÊÝÂ¸¤µ¤ì¤ë¤³¤È¤Ë¤Ê¤ê¤Þ¤¹";
$net2ftp_messages["Copy up"] = "¾å¤Ë¥³¥Ô¡¼";
$net2ftp_messages["Copy down"] = "²¼¤Ë¥³¥Ô¡¼";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "¥Õ¥¡¥¤¥ë¤È¥Ç¥£¥ì¥¯¥È¥ê¤ò¸¡º÷";
$net2ftp_messages["Search again"] = "ºÆ¸¡º÷";
$net2ftp_messages["Search results"] = "¸¡º÷·ë²Ì";
$net2ftp_messages["Please enter a valid search word or phrase."] = "Í­¸ú¤ÊÃ±¸ì¤Þ¤¿¤Ï¸ì¶ç¤òÆþÎÏ¤·¤Æ¤¯¤À¤µ¤¤¡£";
$net2ftp_messages["Please enter a valid filename."] = "Í­¸ú¤Ê¥Õ¥¡¥¤¥ëÌ¾¤òÆþÎÏ¤·¤Æ¤¯¤À¤µ¤¤¡£";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "Í­¸ú¤Ê¥Õ¥¡¥¤¥ë¥µ¥¤¥º¤ò \"from\" ¥Æ¥­¥¹¥È¥Ü¥Ã¥¯¥¹¤ËÆþÎÏ¤·¤Æ²¼¤µ¤¤¡£(Îã: 0)";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "Í­¸ú¤Ê¥Õ¥¡¥¤¥ë¥µ¥¤¥º¤ò \"to\" ¥Æ¥­¥¹¥È¥Ü¥Ã¥¯¥¹¤ËÆþÎÏ¤·¤Æ²¼¤µ¤¤¡£(Îã: 500000)";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "Y-m-d ·Á¼°¤ÎÍ­¸ú¤ÊÆüÉÕ¤ò \"from\" ¥Æ¥­¥¹¥È¥Ü¥Ã¥¯¥¹¤ËÆþÎÏ¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "Y-m-d ·Á¼°¤ÎÍ­¸ú¤ÊÆüÉÕ¤ò \"to\" ¥Æ¥­¥¹¥È¥Ü¥Ã¥¯¥¹¤ËÆþÎÏ¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "Ê¸»úÎó <b>%1\$s</b> ¤ÏÁªÂò¤µ¤ì¤¿¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ëÃæ¤Ë¤Ï¸«¤Ä¤«¤ê¤Þ¤»¤ó¤Ç¤·¤¿¡£";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "Ê¸»úÎó <b>%1\$s</b> ¤Ï¼¡¤Î¥Õ¥¡¥¤¥ëÃæ¤Ë¸«¤Ä¤«¤ê¤Þ¤·¤¿:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "Ê¸»úÎó¤Î¸¡º÷";
$net2ftp_messages["Case sensitive search"] = "¾ÜºÙ¸¡º÷";
$net2ftp_messages["Restrict the search to:"] = "¸¡º÷À©¸Â:";
$net2ftp_messages["files with a filename like"] = "¥Õ¥¡¥¤¥ëÌ¾»ØÄê";
$net2ftp_messages["(wildcard character is *)"] = "(¥ï¥¤¥ë¥É¥«¡¼¥É: *)";
$net2ftp_messages["files with a size"] = "¥Õ¥¡¥¤¥ë¥µ¥¤¥º»ØÄê";
$net2ftp_messages["files which were last modified"] = "¥Õ¥¡¥¤¥ë¤ÎºÇ½ª¹¹¿·Æü»þ¤Ç»ØÄê";
$net2ftp_messages["from"] = "from";
$net2ftp_messages["to"] = "to";

$net2ftp_messages["Directory"] = "¥Õ¥©¥ë¥À";
$net2ftp_messages["File"] = "¥Õ¥¡¥¤¥ë";
$net2ftp_messages["Line"] = "¹Ô";
$net2ftp_messages["Action"] = "¥¢¥¯¥·¥ç¥ó";
$net2ftp_messages["View"] = "±ÜÍ÷";
$net2ftp_messages["Edit"] = "ÊÔ½¸";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "¥Õ¥¡¥¤¥ë %1\$s ¤Î¥½¡¼¥¹¥³¡¼¥É¤ò¿§ÉÕ¤­¤ÇÉ½¼¨";
$net2ftp_messages["Edit the source code of file %1\$s"] = "¥Õ¥¡¥¤¥ë %1\$s ¤Î¥½¡¼¥¹¥³¡¼¥É¤òÊÔ½¸";

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
$net2ftp_messages["Unable to open the template file"] = "¥Æ¥ó¥×¥ì¡¼¥È¥Õ¥¡¥¤¥ë¤¬³«¤±¤Þ¤»¤ó";
$net2ftp_messages["Unable to read the template file"] = "¥Æ¥ó¥×¥ì¡¼¥È¥Õ¥¡¥¤¥ë¤¬ÆÉ¤ß¹þ¤á¤Þ¤»¤ó";
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
$net2ftp_messages["Upload directories and files using a Java applet"] = "Java¥¢¥×¥ì¥Ã¥È¤òÍøÍÑ¤·¤Æ¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤ò¥¢¥Ã¥×¥í¡¼¥É";
$net2ftp_messages["Number of files:"] = "¥Õ¥¡¥¤¥ë¿ô:";
$net2ftp_messages["Size of files:"] = "¥Õ¥¡¥¤¥ë¥µ¥¤¥º:";
$net2ftp_messages["Add"] = "ÄÉ²Ã";
$net2ftp_messages["Remove"] = "½üµî";
$net2ftp_messages["Upload"] = "¥¢¥Ã¥×¥í¡¼¥É";
$net2ftp_messages["Add files to the upload queue"] = "¥Õ¥¡¥¤¥ë¤ò¥­¥å¡¼¤ËÄÉ²Ã";
$net2ftp_messages["Remove files from the upload queue"] = "¥Õ¥¡¥¤¥ë¤ò¥­¥å¡¼¤«¤é½üµî";
$net2ftp_messages["Upload the files which are in the upload queue"] = "¥­¥å¡¼¤Î¥Õ¥¡¥¤¥ë¤ò¥¢¥Ã¥×¥í¡¼¥É";
$net2ftp_messages["Maximum server space exceeded. Please select less/smaller files."] = "¥µ¡¼¥ÐÍÆÎÌ¤Î¸Â³¦¤ËÅþÃ£¡£¥Õ¥¡¥¤¥ë¤ò¾¯¤Ê¤¯/¾®¤µ¤¯¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Total size of the files is too big. Please select less/smaller files."] = "¥Õ¥¡¥¤¥ë¤Î¹ç·×¥µ¥¤¥º¤¬Âç¤­¤¹¤®¤Þ¤¹¡£¥Õ¥¡¥¤¥ë¤ò¾¯¤Ê¤¯/¾®¤µ¤¯¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Total number of files is too high. Please select fewer files."] = "¥Õ¥¡¥¤¥ë¤Î¹ç·×¿ô¤¬Â¿¤¹¤®¤Þ¤¹¡£¥Õ¥¡¥¤¥ë¿ô¤ò¾¯¤Ê¤¯¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Note: to use this applet, Sun's Java plugin must be installed (version 1.4 or newer)."] = "Ãí¼á: ¤³¤Î¥¢¥×¥ì¥Ã¥È¤òÍøÍÑ¤¹¤ë¤Ë¤Ï¡¢Sun ¤Î Java¥×¥é¥°¥¤¥ó¡Ê¥Ð¡¼¥¸¥ç¥ó 1.4 °Ê¾å¡Ë¤¬¥¤¥ó¥¹¥È¡¼¥ë¤µ¤ì¤Æ¤¤¤ëÉ¬Í×¤¬¤¢¤ê¤Þ¤¹¡£";

} // end jupload



// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login") {
// -------------------------------------------------------------------------
$net2ftp_messages["Login!"] = "¥í¥°¥¤¥ó¤·¤è¤¦!";
$net2ftp_messages["Once you are logged in, you will be able to:"] = "Once you are logged in, you will be able to:";
$net2ftp_messages["Navigate the FTP server"] = "FTP ¥µ¡¼¥Ð¤ÎÁàºî";
$net2ftp_messages["Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."] = "°ìÅÙ¥í¥°¥¤¥ó¤¹¤ì¤Ð¡¢¥Ç¥£¥ì¥¯¥È¥ê¤È¥Ç¥£¥ì¥¯¥È¥êÃæ¤Î¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê¡¢¥Õ¥¡¥¤¥ëÁ´¤Æ¤ò±ÜÍ÷¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤¹¡£";
$net2ftp_messages["Upload files"] = "¥Õ¥¡¥¤¥ë¤Î¥¢¥Ã¥×¥í¡¼¥É";
$net2ftp_messages["There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet."] = "3¼ïÎà¤ÎÊýË¡¤Ç¥Õ¥¡¥¤¥ë¤ò¥¢¥Ã¥×¥í¡¼¥É¤Ç¤­¤Þ¤¹: É¸½à¤Î¥¢¥Ã¥×¥í¡¼¥É¡¢¥¢¥Ã¥×¥í¡¼¥É¸å¼«Æ°²òÅàµ¡Ç½¡¢Java¥¢¥×¥ì¥Ã¥È¤Ë¤è¤ë¥¢¥Ã¥×¥í¡¼¥É¡£";
$net2ftp_messages["Download files"] = "¥Õ¥¡¥¤¥ë¤Î¥À¥¦¥ó¥í¡¼¥É";
$net2ftp_messages["Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive."] = "¥Õ¥¡¥¤¥ëÌ¾¤ò¥¯¥ê¥Ã¥¯¤·¤Æ¤½¤Î¥Õ¥¡¥¤¥ë¤òÂ¨ºÂ¤Ë¥À¥¦¥ó¥í¡¼¥É¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤¹¡£<br />Ê£¿ô¤Î¥Õ¥¡¥¤¥ë¤ò¥¯¥ê¥Ã¥¯¤·¤Æ¥À¥¦¥ó¥í¡¼¥É¤¹¤ë¤È¡¢1¤Ä¤Î°µ½Ì¥Õ¥¡¥¤¥ë¤Ë¤·¤Æ¥À¥¦¥ó¥í¡¼¥É¤µ¤ì¤Þ¤¹¡£";
$net2ftp_messages["Zip files"] = "¥Õ¥¡¥¤¥ë¤Î°µ½Ì";
$net2ftp_messages["... and save the zip archive on the FTP server, or email it to someone."] = "¥Õ¥¡¥¤¥ë¤ò°µ½Ì¤·¤Æ FTP¥µ¡¼¥Ð¾å¤ËÊÝÂ¸¤·¤¿¤ê¡¢ÅÅ»Ò¥á¡¼¥ë¤ÇÁ÷¿®¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤¹¡£";
$net2ftp_messages["Unzip files"] = "Unzip files";
$net2ftp_messages["Different formats are supported: .zip, .tar, .tgz and .gz."] = "Different formats are supported: .zip, .tar, .tgz and .gz.";
$net2ftp_messages["Install software"] = "Install software";
$net2ftp_messages["Choose from a list of popular applications (PHP required)."] = "Choose from a list of popular applications (PHP required).";
$net2ftp_messages["Copy, move and delete"] = "¥³¥Ô¡¼¡¢°ÜÆ°¡¢ºï½ü";
$net2ftp_messages["Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted."] = "¥Ç¥£¥ì¥¯¥È¥ê¤ò·«¤êÊÖ¤·Áàºî¤Ç¤­¤Þ¤¹¡£¤½¤ÎÃæ¤Î¥³¥ó¥Æ¥ó¥Ä¡Ê¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¡Ë¤â¥³¥Ô¡¼¡¢°ÜÆ°¡¢ºï½ü¤Ç¤­¤Þ¤¹¡£";
$net2ftp_messages["Copy or move to a 2nd FTP server"] = "ÊÌ¤ÎFTP¥µ¡¼¥Ð¤Ø¤Î¥³¥Ô¡¼¡¢°ÜÆ°";
$net2ftp_messages["Handy to import files to your FTP server, or to export files from your FTP server to another FTP server."] = "´ÊÃ±¤Ë¤¢¤Ê¤¿¤Î FTP¥µ¡¼¥Ð¤Ë¥Õ¥¡¥¤¥ë¤ò¥¤¥ó¥Ý¡¼¥È¤·¤¿¤ê¡¢¤¢¤Ê¤¿¤Î FTP¥µ¡¼¥Ð¤«¤éÊÌ¤Î FTP¥µ¡¼¥Ð¤Ø¥Õ¥¡¥¤¥ë¤ò¥¨¥­¥¹¥Ý¡¼¥È¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤¹¡£";
$net2ftp_messages["Rename and chmod"] = "¥Õ¥¡¥¤¥ëÌ¾¤ÎÊÑ¹¹¤Èµö²Ä¾ðÊó¤ÎÊÑ¹¹";
$net2ftp_messages["Chmod handles directories recursively."] = "¥Ç¥£¥ì¥¯¥È¥ê¤Îµö²Ä¾ðÊó¤ÎÊÑ¹¹¤ò·«¤êÊÖ¤·Áàºî¤Ç¤­¤Þ¤¹¡£";
$net2ftp_messages["View code with syntax highlighting"] = "¿§ÉÕ¤­¤Ç¥³¡¼¥ÉÉ½¼¨";
$net2ftp_messages["PHP functions are linked to the documentation on php.net."] = "PHPµ¡Ç½¤Ï php.net ¤Î¥É¥­¥å¥á¥ó¥È¤È¥ê¥ó¥¯¤·¤Æ¤¤¤Þ¤¹¡£";
$net2ftp_messages["Plain text editor"] = "¥×¥ì¡¼¥ó¥Æ¥­¥¹¥È¥¨¥Ç¥£¥¿";
$net2ftp_messages["Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server."] = "¥Ö¥é¥¦¥¶¾å¤Ç¥Æ¥­¥¹¥È¤òÀµ³Î¤ËÊÔ½¸¤Ç¤­¤Þ¤¹¡£ÊÑ¹¹¤·¤ÆÊÝÂ¸¤·¤¿¿·¤·¤¤¥Õ¥¡¥¤¥ë¤ÏËè²ó FTP¥µ¡¼¥Ð¤ØÅ¾Á÷¤µ¤ì¤Þ¤¹¡£";
$net2ftp_messages["HTML editors"] = "HTML¥¨¥Ç¥£¥¿";
$net2ftp_messages["Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from."] = "Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from.";
$net2ftp_messages["Code editor"] = "¥³¡¼¥É¥¨¥Ç¥£¥¿";
$net2ftp_messages["Edit HTML and PHP in an editor with syntax highlighting."] = "HTML ¤È PHP ¤ò¿§ÉÕ¤­É½¼¨¤ÇÊÔ½¸¤Ç¤­¤Þ¤¹¡£";
$net2ftp_messages["Search for words or phrases"] = "Ê¸»úÎó¤Î¸¡º÷";
$net2ftp_messages["Filter out files based on the filename, last modification time and filesize."] = "É½¼¨¤¹¤ë¥Õ¥¡¥¤¥ë¤ò¥Õ¥¡¥¤¥ëÌ¾¡¢ºÇ½ªÊÑ¹¹Æü»þ¡¢¥Õ¥¡¥¤¥ë¥µ¥¤¥º¤Ç¥Õ¥£¥ë¥¿¥ê¥ó¥°¤Ç¤­¤Þ¤¹¡£";
$net2ftp_messages["Calculate size"] = "¥µ¥¤¥º¤Î·×»»";
$net2ftp_messages["Calculate the size of directories and files."] = "¥Ç¥£¥ì¥¯¥È¥ê¤È¥Õ¥¡¥¤¥ë¤Î¥µ¥¤¥º¤ò·×»»¤Ç¤­¤Þ¤¹¡£";

$net2ftp_messages["FTP server"] = "FTP ¥µ¡¼¥Ð";
$net2ftp_messages["Example"] = "Îã";
$net2ftp_messages["Port"] = "¥Ý¡¼¥È";
$net2ftp_messages["Username"] = "¥æ¡¼¥¶Ì¾";
$net2ftp_messages["Password"] = "¥Ñ¥¹¥ï¡¼¥É";
$net2ftp_messages["Anonymous"] = "Æ¿Ì¾";
$net2ftp_messages["Passive mode"] = "Passive ¥â¡¼¥É";
$net2ftp_messages["Initial directory"] = "½é´ü¥Ç¥£¥ì¥¯¥È¥ê";
$net2ftp_messages["Language"] = "¸À¸ì";
$net2ftp_messages["Skin"] = "¥Æ¡¼¥Þ";
$net2ftp_messages["FTP mode"] = "FTP¥â¡¼¥É";
$net2ftp_messages["Automatic"] = "¼«Æ°";
$net2ftp_messages["Login"] = "¥í¥°¥¤¥ó";
$net2ftp_messages["Clear cookies"] = "¥¯¥Ã¥­¡¼¤Îºï½ü";
$net2ftp_messages["Admin"] = "Admin";
$net2ftp_messages["Please enter an FTP server."] = "FTP¥µ¡¼¥Ð¤òÆþÎÏ¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Please enter a username."] = "¥æ¡¼¥¶Ì¾¤òÆþÎÏ¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Please enter a password."] = "¥Ñ¥¹¥ï¡¼¥É¤òÆþÎÏ¤·¤Æ²¼¤µ¤¤¡£";

} // end login


// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login_small") {
// -------------------------------------------------------------------------

$net2ftp_messages["Please enter your Administrator username and password."] = "´ÉÍý¼Ô¤Î¥æ¡¼¥¶Ì¾¤È¥Ñ¥¹¥ï¡¼¥É¤òÆþÎÏ¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Please enter your username and password for FTP server <b>%1\$s</b>."] = "Please enter your username and password for FTP server <b>%1\$s</b>.";
$net2ftp_messages["Username"] = "¥æ¡¼¥¶Ì¾";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Password"] = "¥Ñ¥¹¥ï¡¼¥É";
$net2ftp_messages["Login"] = "¥í¥°¥¤¥ó";
$net2ftp_messages["Continue"] = "Â³¤±¤ë";

} // end login_small


// -------------------------------------------------------------------------
// Logout module
if ($net2ftp_globals["state"] == "logout") {
// -------------------------------------------------------------------------

// logout.inc.php
$net2ftp_messages["Login page"] = "¥í¥°¥¤¥ó¥Ú¡¼¥¸";

// logout.template.php
$net2ftp_messages["You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>."] = "You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>.";
$net2ftp_messages["Note: other users of this computer could click on the browser's Back button and access the FTP server."] = "Ãí¼á: ¥Ö¥é¥¦¥¶¤ÎÌá¤ë¥Ü¥¿¥ó¤ò¥¯¥ê¥Ã¥¯¤¹¤ë¤È¡¢¤³¤Î¥³¥ó¥Ô¥å¡¼¥¿¤ÎÂ¾¤Î¥æ¡¼¥¶¤¬ FTP¥µ¡¼¥Ð¤Ë¥¢¥¯¥»¥¹¤¹¤ë¤³¤È¤¬¤Ç¤­¤Æ¤·¤Þ¤¤¤Þ¤¹¡£";
$net2ftp_messages["To prevent this, you must close all browser windows."] = "¤³¤ì¤òËÉ¤°¤¿¤á¤Ë¤Ï¡¢Á´¤Æ¤Î¥Ö¥é¥¦¥¶¤Î¥¦¥£¥ó¥É¥¦¤òÊÄ¤¸¤ëÉ¬Í×¤¬¤¢¤ê¤Þ¤¹¡£";
$net2ftp_messages["Close"] = "ÊÄ¤¸¤ë";
$net2ftp_messages["Click here to close this window"] = "¤³¤³¤ò¥¯¥ê¥Ã¥¯¤¹¤ë¤È¤³¤Î¥¦¥£¥ó¥É¥¦¤òÊÄ¤¸¤Þ¤¹";

} // end logout


// -------------------------------------------------------------------------
// New directory module
if ($net2ftp_globals["state"] == "newdir") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create new directories"] = "¿·µ¬¥Ç¥£¥ì¥¯¥È¥ê¤ÎºîÀ®";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "¿·µ¬¥Ç¥£¥ì¥¯¥È¥ê¤Ï <b>%1\$s</b> ¤ËºîÀ®¤µ¤ì¤Þ¤¹¡£";
$net2ftp_messages["New directory name:"] = "¿·µ¬¥Ç¥£¥ì¥¯¥È¥êÌ¾:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤¬ºîÀ®¤µ¤ì¤Þ¤·¤¿¡£";
$net2ftp_messages["Directory <b>%1\$s</b> could not be created."] = "¥Ç¥£¥ì¥¯¥È¥ê <b>%1\$s</b> ¤òºîÀ®¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤»¤ó¤Ç¤·¤¿¡£";

} // end newdir


// -------------------------------------------------------------------------
// Raw module
if ($net2ftp_globals["state"] == "raw") {
// -------------------------------------------------------------------------

// /modules/raw/raw.inc.php
$net2ftp_messages["Send arbitrary FTP commands"] = "Ç¤°Õ¤Î FTP¥³¥Þ¥ó¥É¤òÁ÷¿®";


// /skins/[skin]/raw1.template.php
$net2ftp_messages["List of commands:"] = "¥³¥Þ¥ó¥É¤Î¥ê¥¹¥È:";
$net2ftp_messages["FTP server response:"] = "FTP¥µ¡¼¥Ð¤Î±þÅú:";

} // end raw


// -------------------------------------------------------------------------
// Rename module
if ($net2ftp_globals["state"] == "rename") {
// -------------------------------------------------------------------------
$net2ftp_messages["Rename directories and files"] = "¥Ç¥£¥ì¥¯¥È¥êÌ¾¤È¥Õ¥¡¥¤¥ëÌ¾¤ÎÊÑ¹¹";
$net2ftp_messages["Old name: "] = "°ÊÁ°¤ÎÌ¾Á°: ";
$net2ftp_messages["New name: "] = "¿·¤·¤¤Ì¾Á°: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "¿·¤·¤¤¥Õ¥¡¥¤¥ëÌ¾¤Ë¥É¥Ã¥È(.)¤ò´Þ¤à¤³¤È¤Ï¤Ç¤­¤Þ¤»¤ó¡£¤³¤Î¥¨¥ó¥È¥ê¤Ï <b>%1\$s</b> ¤Ë²þÌ¾¤µ¤ì¤Þ¤»¤ó¤Ç¤·¤¿¡£";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> ¤Ï <b>%2\$s</b> ¤Ë²þÌ¾¤µ¤ì¤Þ¤·¤¿";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> ¤ò <b>%2\$s</b> ¤Ë²þÌ¾¤¹¤ë¤³¤È¤Ï¤Ç¤­¤Þ¤»¤ó";

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
$net2ftp_messages["Set all targetdirectories"] = "Á´¤Æ¤ÎÂÐ¾Ý¥Ç¥£¥ì¥¯¥È¥ê¤òÁªÂò";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "¶¦ÄÌ¤ÎÂÐ¾Ý¥Ç¥£¥ì¥¯¥È¥ê¤òÀßÄê¤¹¤ë¤Ë¤Ï¡¢¾å¤Î¥Æ¥­¥¹¥È¥Ü¥Ã¥¯¥¹¤ËÂÐ¾Ý¥Ç¥£¥ì¥¯¥È¥ê¤òÆþÎÏ¤· \"Á´¤Æ¤ÎÂÐ¾Ý¥Ç¥£¥ì¥¯¥È¥ê¤òÁªÂò\" ¥Ü¥¿¥ó¤ò¥¯¥ê¥Ã¥¯¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Ãí¼á: ¥³¥Ô¡¼¤¹¤ëÁ°¤Ë¡¢ÂÐ¾Ý¥Ç¥£¥ì¥¯¥È¥ê¤¬´û¤ËÂ¸ºß¤·¤Æ¤¤¤Ê¤±¤ì¤Ð¤Ê¤ê¤Þ¤»¤ó¡£";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Unzip archive <b>%1\$s</b> to:";
$net2ftp_messages["Target directory:"] = "ÂÐ¾Ý¥Ç¥£¥ì¥¯¥È¥ê:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "¥Õ¥©¥ë¥ÀÌ¾¤ò»ÈÍÑ (¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê¤Î¼«Æ°ºîÀ®)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "¥Õ¥¡¥¤¥ë¤Î¥¢¥Ã¥×¥Ç¡¼¥È";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>·Ù¹ð: ¤³¤Îµ¡Ç½¤Ï¤Þ¤À³«È¯½é´üÃÊ³¬¤Ç¤¹¡£²õ¤ì¤Æ¤â¹½¤ï¤Ê¤¤¥Æ¥¹¥È¥Õ¥¡¥¤¥ë¤Î¤ß¤ÎÍøÍÑ¤Ë¤È¤É¤á¡¢·è¤·¤ÆÂçÀÚ¤Ê¥Õ¥¡¥¤¥ë¤Ë¤ÏÍøÍÑ¤·¤Ê¤¤¤Ç²¼¤µ¤¤! ¤³¤ì¤Ï·Ù¹ð¤Ç¤¹!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "Ì¤ÃÎ¤Î¥Ð¥°: - ¥¿¥ÖÊ¸»ú¤Î¾Ãµî - Âç¤­¤Ê¥µ¥¤¥º¤Î¥Õ¥¡¥¤¥ë¤Ç¤Ï¾å¼ê¤¯Æ°ºî¤·¤Ê¤¤ (> 50kB) - É¸½àÊ¸»ú°Ê³°¤ÎÊ¸»ú¤ò´Þ¤à¥Õ¥¡¥¤¥ë¤ÏÌ¤¥Æ¥¹¥È</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "¤³¤Îµ¡Ç½¤ÏÁªÂò¤µ¤ì¤¿¥Õ¥¡¥¤¥ë¤ò¿·¤·¤¤¥Ð¡¼¥¸¥ç¥ó¤Î¥Õ¥¡¥¤¥ë¤Ø¡¢¤½¤ì¤¾¤ì¤ÎÊÑ¹¹ÅÀ¤òÉ½¼¨¤·¤Ê¤¬¤é¡¢¸Ä¡¹¤ÎÊÑ¹¹¤òÅ¬ÍÑ¤¹¤ë¤«ÈÝ¤«¤òÁªÂò¤·¤Æ¥¢¥Ã¥×¥í¡¼¥É¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤¹. ÊÝÂ¸¤¹¤ëÁ°¤Ë¡¢Ê»¹ç¤µ¤ì¤¿¥Õ¥¡¥¤¥ë¤òÊÔ½¸¤¹¤ë¤³¤È¤¬¤Ç¤­¤Þ¤¹.";
$net2ftp_messages["Old file:"] = "°ÊÁ°¤Î¥Õ¥¡¥¤¥ë:";
$net2ftp_messages["New file:"] = "¿·¤·¤¤¥Õ¥¡¥¤¥ë:";
$net2ftp_messages["Restrictions:"] = "¥µ¥¤¥ºÀ©¸Â:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "1¤Ä¤Î¥Õ¥¡¥¤¥ë¤Î¾å¸Â¥µ¥¤¥º¤Ï net2ftp ¤Ç <b>%1\$s kB</b> ¤Þ¤Ç¡¢ PHP ¤Ç <b>%2\$s</b> ¤Þ¤Ç¤ËÀ©¸Â¤µ¤ì¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "½èÍý»þ´Ö¤Î¾å¸Â¤Ï <b>%1\$s ÉÃ</b> ¤Ç¤¹";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "FTP Å¾Á÷¥â¡¼¥É (ASCII ¤Þ¤¿¤Ï ¥Ð¥¤¥Ê¥ê) ¤Ï¥Õ¥¡¥¤¥ëÌ¾¤Î³ÈÄ¥»Ò¤Ë¤è¤Ã¤Æ¼«Æ°Åª¤Ë·èÄê¤µ¤ì¤Þ¤¹";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Å¾Á÷Àè¤ËÆ±Ì¾¤Î¥Õ¥¡¥¤¥ë¤¬¤¹¤Ç¤ËÂ¸ºß¤¹¤ë¾ì¹ç¤Ï¾å½ñ¤­¤µ¤ì¤Þ¤¹";
$net2ftp_messages["You did not provide any files or archives to upload."] = "¥¢¥Ã¥×¥í¡¼¥É¤¹¤ë¥Õ¥¡¥¤¥ë¤Þ¤¿¤Ï°µ½Ì¥Õ¥¡¥¤¥ë¤¬ÀßÄê¤µ¤ì¤Æ¤¤¤Þ¤»¤ó¡£";
$net2ftp_messages["Unable to delete the new file"] = "¿·¤·¤¤¥Õ¥¡¥¤¥ë¤òºï½ü¤¹¤ë¤³¤È¤Ï¤Ç¤­¤Þ¤»¤ó";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "¤·¤Ð¤é¤¯¤ªÂÔ¤Á²¼¤µ¤¤...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "¼¡¤Î¹Ô¤òÁªÂò¤·¤Æ µö²Ä¡¦µÑ²¼ ¤òÊÑ¹¹¤·¡¢¥Õ¥©¡¼¥à¤òÁ÷¿®¤·¤Æ¤¯¤À¤µ¤¤¡£";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "¥¢¥Ã¥×¥í¡¼¥ÉÀè¤Î¥Ç¥£¥ì¥¯¥È¥ê:";
$net2ftp_messages["Files"] = "¥Õ¥¡¥¤¥ë";
$net2ftp_messages["Archives"] = "°µ½Ì¥Õ¥¡¥¤¥ë";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "¤³¤³¤ËÆþÎÏ¤·¤¿¥Õ¥¡¥¤¥ë¤¬ FTP ¥µ¡¼¥Ð¤ØÅ¾Á÷¤µ¤ì¤Þ¤¹¡£";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "¤³¤³¤ËÆþÎÏ¤·¤¿°µ½Ì¥Õ¥¡¥¤¥ë¤¬²òÅà¤µ¤ì¡¢Ãæ¤Î¥Õ¥¡¥¤¥ë¤¬ FTP ¥µ¡¼¥Ð¤ØÅ¾Á÷¤µ¤ì¤Þ¤¹¡£";
$net2ftp_messages["Add another"] = "ÄÉ²Ã";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "¥Õ¥©¥ë¥ÀÌ¾¤ò»ÈÍÑ (¥µ¥Ö¥Ç¥£¥ì¥¯¥È¥ê¤Î¼«Æ°ºîÀ®)";

$net2ftp_messages["Choose a directory"] = "¥Ç¥£¥ì¥¯¥È¥ê¤òÁªÂò";
$net2ftp_messages["Please wait..."] = "¤·¤Ð¤é¤¯¤ªÂÔ¤Á²¼¤µ¤¤...";
$net2ftp_messages["Uploading... please wait..."] = "¥¢¥Ã¥×¥í¡¼¥ÉÃæ... ¤·¤Ð¤é¤¯¤ªÂÔ¤Á²¼¤µ¤¤...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "¥¢¥Ã¥×¥í¡¼¥É¤¬µöÍÆ»þ´Ö <b>%1\$s ÉÃ<\/b>¤òÄ¶¤¨¤ë¤è¤¦¤Ê¤é¤Ð¡¢¤è¤ê¾®¤µ¤¤/¾¯¤Ê¤¤¥Õ¥¡¥¤¥ë¤Ç¤ä¤êÄ¾¤¹É¬Í×¤¬¤¢¤ê¤Þ¤¹¡£";
$net2ftp_messages["This window will close automatically in a few seconds."] = "¤³¤Î¥¦¥£¥ó¥É¥¦¤Ï¿ôÉÃ¸å¤Ë¼«Æ°¤ÇÊÄ¤¸¤é¤ì¤Þ¤¹¡£";
$net2ftp_messages["Close window now"] = "º£¤¹¤°ÊÄ¤¸¤ë";

$net2ftp_messages["Upload files and archives"] = "¥Õ¥¡¥¤¥ë¤È°µ½Ì¥Õ¥¡¥¤¥ë¤Î¥¢¥Ã¥×¥í¡¼¥É";
$net2ftp_messages["Upload results"] = "¥¢¥Ã¥×¥í¡¼¥É·ë²Ì";
$net2ftp_messages["Checking files:"] = "¥Õ¥¡¥¤¥ë¤Î¥Á¥§¥Ã¥¯Ãæ:";
$net2ftp_messages["Transferring files to the FTP server:"] = "¥Õ¥¡¥¤¥ë¤ò FTP ¥µ¡¼¥Ð¤ØÅ¾Á÷Ãæ:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "°µ½Ì¥Õ¥¡¥¤¥ë¤ò²òÅà¤·¥Õ¥¡¥¤¥ë¤ò FTP ¥µ¡¼¥Ð¤ØÅ¾Á÷Ãæ:";
$net2ftp_messages["Upload more files and archives"] = "Â¾¤Î¥Õ¥¡¥¤¥ë¤È°µ½Ì¥Õ¥¡¥¤¥ë¤â¥¢¥Ã¥×¥í¡¼¥É¤¹¤ë";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "¥µ¥¤¥ºÀ©¸Â:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "1¤Ä¤Î¥Õ¥¡¥¤¥ë¤Î¾å¸Â¥µ¥¤¥º¤Ï net2ftp ¤Ç <b>%1\$s kB</b> ¤Þ¤Ç¡¢ PHP ¤Ç <b>%2\$s</b> ¤Þ¤Ç¤ËÀ©¸Â¤µ¤ì¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "½èÍý»þ´Ö¤Î¾å¸Â¤Ï <b>%1\$s ÉÃ</b> ¤Ç¤¹";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "FTP Å¾Á÷¥â¡¼¥É (ASCII ¤Þ¤¿¤Ï ¥Ð¥¤¥Ê¥ê) ¤Ï¥Õ¥¡¥¤¥ëÌ¾¤Î³ÈÄ¥»Ò¤Ë¤è¤Ã¤Æ¼«Æ°Åª¤Ë·èÄê¤µ¤ì¤Þ¤¹";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Å¾Á÷Àè¤ËÆ±Ì¾¤Î¥Õ¥¡¥¤¥ë¤¬¤¹¤Ç¤ËÂ¸ºß¤¹¤ë¾ì¹ç¤Ï¾å½ñ¤­¤µ¤ì¤Þ¤¹";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "¥Õ¥¡¥¤¥ë %1\$s ¤ÎÉ½¼¨";
$net2ftp_messages["View image %1\$s"] = "²èÁü %1\$s ¤ÎÉ½¼¨";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "Macromedia ShockWave Flash ¥à¡¼¥Ó¡¼ %1\$s ¤ÎÉ½¼¨";
$net2ftp_messages["Image"] = "²èÁü";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "¿§ÉÕ¤­É½¼¨¤Ï <a href=\"http://geshi.org\">GeSHi</a> ¤«¤é¶¡µë¤µ¤ì¤Æ¤¤¤Þ¤¹";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "²èÁü¤òÊÝÂ¸¤¹¤ë¤Ë¤Ï¡¢²èÁü¤Î¾å¤Ç±¦¥¯¥ê¥Ã¥¯¤· 'Ì¾Á°¤òÉÕ¤±¤Æ²èÁü¤òÊÝÂ¸...' ¤òÁªÂò¤·¤Æ¤¯¤À¤µ¤¤";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "°µ½Ì¥Õ¥¡¥¤¥ë¥¨¥ó¥È¥ê";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "FTP ¥µ¡¼¥Ð¾å¤Ç¼¡¤Î¥Õ¥¡¥¤¥ë¤ò°µ½Ì:";
$net2ftp_messages["Email the zip file in attachment to:"] = "Email ¤Ë°µ½Ì¥Õ¥¡¥¤¥ë¤òÅºÉÕ:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "Ãí¼á: ¥Õ¥¡¥¤¥ë¤ÏÆ¿Ì¾¤Ç¤ÏÁ÷¿®¤µ¤ì¤Þ¤»¤ó¡£¤¢¤Ê¤¿¤Î IP ¥¢¥É¥ì¥¹¤¬Á÷¿®»þ¤Ë email ¤ËÄÉ²Ã¤µ¤ì¤Þ¤¹¡£";
$net2ftp_messages["Some additional comments to add in the email:"] = "email ¤ËÄÉ²Ã¤¹¤ë¥³¥á¥ó¥È:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "°µ½Ì¥Õ¥¡¥¤¥ë¤Î¥Õ¥¡¥¤¥ëÌ¾¤¬ÆþÎÏ¤µ¤ì¤Æ¤¤¤Þ¤»¤ó¡£Ìá¤ë¤ò¥¯¥ê¥Ã¥¯¤·¤Æ¥Õ¥¡¥¤¥ëÌ¾¤òÆþÎÏ¤·¤Æ²¼¤µ¤¤¡£";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "ÆþÎÏ¤µ¤ì¤¿ email ¥¢¥É¥ì¥¹ (%1\$s) ¤ÏÍ­¸ú¤Ç¤Ï¤¢¤ê¤Þ¤»¤ó¡£<br />¼¡¤Î½ñ¼°¤ËÂ§¤Ã¤¿¥¢¥É¥ì¥¹¤òÆþÎÏ¤·¤Æ²¼¤µ¤¤ <b>username@domain.com</b>";

} // end zip

?>