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
$net2ftp_messages["en"] = "ar";

// HTML dir attribute: left-to-right (LTR) or right-to-left (RTL)
$net2ftp_messages["ltr"] = "rtl";

// CSS style: align left or right (use in combination with LTR or RTL)
$net2ftp_messages["left"] = "right";
$net2ftp_messages["right"] = "left";

// Encoding
$net2ftp_messages["iso-8859-1"] = "windows-1256";


// -------------------------------------------------------------------------
// Status messages
// -------------------------------------------------------------------------

// When translating these messages, keep in mind that the text should not be too long
// It should fit in the status textbox

$net2ftp_messages["Connecting to the FTP server"] = "ÇáÇÊÕÇá ÈÓÑİÑ FTP";
$net2ftp_messages["Logging into the FTP server"] = "ÇáÏÎæá Åáì ÓÑİÑ FTP";
$net2ftp_messages["Setting the passive mode"] = "ÅÚÏÇÏÇÊ ÇáæÖÚ ÇáÎÇãá";
$net2ftp_messages["Getting the FTP system type"] = "ÇáÏÎæá İí äãØ äÙÇã FTP";
$net2ftp_messages["Changing the directory"] = "ÊÛííÑ ÇáÏáíá";
$net2ftp_messages["Getting the current directory"] = "ÇáÍÕæá Úáì ÇáÏáíá ÇáÍÇáí";
$net2ftp_messages["Getting the list of directories and files"] = "ÇáÍÕæá Úáì ŞÇÆãÉ ÇáÃÏáÉ æ ÇáãáİÇÊ";
$net2ftp_messages["Parsing the list of directories and files"] = "ÊÍáíá ŞÇÆãÉ ÇáÃÏáÉ æ ÇáãáİÇÊ";
$net2ftp_messages["Logging out of the FTP server"] = "ÊÓÌíá ÇáÎÑæÌ ãä ÓÑİÑ FTP";
$net2ftp_messages["Getting the list of directories and files"] = "ÇáÍÕæá Úáì ŞÇÆãÉ ÇáÃÏáÉ æ ÇáãáİÇÊ";
$net2ftp_messages["Printing the list of directories and files"] = "ØÈÇÚÉ ŞÇÆãÉ ÇáÃÏáÉ æ ÇáãáİÇÊ";
$net2ftp_messages["Processing the entries"] = "ãÚÇáÌÉ ÇáÚäÇÕÑ";
$net2ftp_messages["Processing entry %1\$s"] = "ãÚÇáÌÉ ÇáÚäÕÑ %1\$s";
$net2ftp_messages["Checking files"] = "ÊİÍÕ ÇáãáİÇÊ";
$net2ftp_messages["Transferring files to the FTP server"] = "ÊÑÍíá ÇáãáİÇÊ Åáì ÓÑİÑ FTP";
$net2ftp_messages["Decompressing archives and transferring files"] = "İß ÖÛØ ÇáÃÑÔíİ æ ÊÑÍíá ÇáãáİÇÊ";
$net2ftp_messages["Searching the files..."] = "ÌÇÑí ÇáÈÍË Úä ÇáãáİÇÊ ...";
$net2ftp_messages["Uploading new file"] = "ÌÇÑí ÑİÚ Çáãáİ ÇáÌÏíÏ";
$net2ftp_messages["Reading the file"] = "ŞÑÇÁÉ Çáãáİ";
$net2ftp_messages["Parsing the file"] = "ÊÍáíá Çáãáİ";
$net2ftp_messages["Reading the new file"] = "ŞÑÇÁÉ Çáãáİ ÇáÌÏíÏ";
$net2ftp_messages["Reading the old file"] = "ŞÑÇÁÉ Çáãáİ ÇáŞÏíã";
$net2ftp_messages["Comparing the 2 files"] = "ãŞÇÑäÉ Çáãáİíä";
$net2ftp_messages["Printing the comparison"] = "ØÈÇÚÉ ÇáãŞÇÑäÉ";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "ÅÑÓÇá ÃãÑ FTP %1\$s ãä %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Getting archive %1\$s of %2\$s from the FTP server";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Creating a temporary directory on the FTP server";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Setting the permissions of the temporary directory";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Copying the net2ftp installer script to the FTP server";
$net2ftp_messages["Script finished in %1\$s seconds"] = "ÇáæŞÊ ÇáãÓÊÛÑŞ %1\$s ËÇäíÉ";
$net2ftp_messages["Script halted"] = "ÊÚËÑ ÇáÓßÑÈÊ";

// Used on various screens
$net2ftp_messages["Please wait..."] = "íÑÌì ÇáÇäÊÙÇÑ ...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "ÍÇáÉ ÛíÑ ãŞÈæáÉ » %1\$s . ãæÌæÏ .";
$net2ftp_messages["This beta function is not activated on this server."] = "æÙíİÉ ÇáÇÎÊÈÇÑ ÛíÑ äÔØÉ Úáì åĞÇ ÇáÓÑİÑ .";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "åĞå ÇáæÙíİÉ Êã ÊÚØíáåÇ ãä ŞÈá ÅÏÇÑÉ åĞÇ ÇáãæŞÚ .";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "ÇáÏáíá <b>%1\$s</b> ÛíÑ ãæÌæÏ Ãæ áÇ íãßä ÊÍÏíÏå , áĞÇ áÇ íãßä ÚÑÖ ÇáÏáíá <b>%2\$s</b> ÈÏáÇğ ãäå .";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "ÇáÏáíá ÇáÌÑÒ root <b>%1\$s</b> ÛíÑ ãæÌæÏ Ãæ áÇ íãßä ÊÍÏíÏå .";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "ÇáÏáíá <b>%1\$s</b> áÇ íãßä ÊÍÏíÏå - ÑÈãÇ áÇÊãÊáß ÊÎæíá ßÇİ áÚÑÖ åĞÇ ÇáÏáíá , Ãæ ÑÈãÇ íßæä ÛíÑ ãæÌæÏ .";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted.";
$net2ftp_messages["Execute %1\$s in a new window"] = "ÊäİíĞ %1\$s İí äÇİĞÉ ÌÏíÏÉ";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "íÑÌì ÊÍÏíÏ ãÌáÏ Ãæ ãáİ æÇÍÏ Úáì ÇáÃŞá !";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "ÓÑİÑ FTP <b>%1\$s</b> ÛíÑ ãæÌæÏ İí ŞÇÆãÉ ÓÑİÑÇÊ FTP ÇáãÓãæÍ ÈåÇ .";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "ÇáÓÑİÑ FTP <b>%1\$s</b> ãæÌæÏ İí ŞÇÆãÉ ÓÑİÑÇÊ FTP ÇáãÍÙæÑÉ .";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "ÚäæÇä IP (%1\$s) ãæÌæÏ İí ŞÇÆãÉ ÚäÇæíä IP ÇáãÍÙæÑÉ .";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "ãäİĞ ÓÑİÑ FTP %1\$s áÇ íãßä ÇÓÊÎÏÇãå .";

// isAuthorizedDirectory()
$net2ftp_messages["Table net2ftp_users contains duplicate rows."] = "ÇáÌÏæá net2ftp_users íÍÊæí Úáì Õİæİ ãßÑÑÉ .";

// logAccess(), logLogin(), logError()
$net2ftp_messages["Unable to execute the SQL query."] = "ÊÚĞÑ ÊäİíĞ ÇÓÊÚáÇã SQL .";

// checkAdminUsernamePassword()
$net2ftp_messages["You did not enter your Administrator username or password."] = "áã ÊŞã ÈÅÏÎÇá ÇÓã ÇáãÓÊÎÏã ááÅÏÇÑÉ Ãæ ßáãÉ ÇáãÑæÑ !";
$net2ftp_messages["Wrong username or password. Please try again."] = "ÎØÃ İí ÇÓã ÇáãÓÊÎÏã Ãæ ßáãÉ ÇáãÑæÑ . íÑÌì ÇáãÍÇæáÉ ãä ÌÏíÏ !";


// -------------------------------------------------------------------------
// /includes/consumption.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to determine your IP address."] = "ÊÚĞÑ ÊÍÏíÏ ÚäæÇä IP ÇáÎÇÕ Èß .";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "ÇáÌÏæá net2ftp_log_consumption_ipaddress íÍÊæí Úáì Õİæİ ãßÑÑÉ .";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "ÇáÌÏæá net2ftp_log_consumption_ftpserver íÍÊæí Úáì Õİæİ ãßÑÑÉ .";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "ÇáãÊÛíÑ <b>consumption_ipaddress_datatransfer</b> áíÓ ÚÏÏí .";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "áÇ íãßä ÊÍÏíË ÇáÌÏæá net2ftp_log_consumption_ipaddress .";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "ÇáÌÏæá net2ftp_log_consumption_ipaddress íÍÊæí Úáì ÚäÇÕÑ ãßÑÑÉ .";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "áÇ íãßä ÊÍÏíË ÇáÌÏæá net2ftp_log_consumption_ftpserver .";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "ÇáÌÏæá net2ftp_log_consumption_ftpserver íÍÊæí Úáì ÚäÇÕÑ ãßÑÑÉ .";


// -------------------------------------------------------------------------
// /includes/database.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "ÊÚĞÑ ÇáÇÊÕÇá ÈŞÇÚÏÉ ÇáÈíÇäÇÊ MySQL . íÑÌì ÇáÊÃßÏ ãä ÕÍÉ ãÚáæãÇÊß ÇáãÏÎáÉ İí Çáãáİ settings.inc.php.";
$net2ftp_messages["Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "ÊÚĞÑ ÊÍÏíÏ ŞÇÚÏÉ ÇáÈíÇäÇÊ MySQL . íÑÌì ÇáÊÃßÏ ãä ÕÍÉ ãÚáæãÇÊß ÇáãÏÎáÉ İí Çáãáİ settings.inc.php.";


// -------------------------------------------------------------------------
// /includes/errorhandling.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["An error has occured"] = "ÍÏË ÎØÃ";
$net2ftp_messages["Go back"] = "ÇáÚæÏÉ ááÎáİ";
$net2ftp_messages["Go to the login page"] = "ÇáĞåÇÈ Åáì ÕİÍÉ ÇáÏÎæá";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "<a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">æÙíİÉ FTP áÜ PHP</a> ÛíÑ ãËÈÊÉ .<br /><br /> Úáì ÅÏÇÑÉ ÇáãæŞÚ ÊËÈíÊ æÙíİÉ FTP . ÊÚáíãÇÊ ÇáÊËÈíÊ ÊÌÏåÇ İí <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "ÊÚĞÑ ÇáÇÊÕÇá ÈÓÑİÑ FTP <b>%1\$s</b> Úáì ÇáãäİĞ <b>%2\$s</b>.<br /><br />åá ÃäÊ ãÊÃßÏ ãä ÕÍÉ ÚäæÇä ÓÑİÑ FTP ¿ åĞÇ íÍÕá áÃÓÈÇÈ ãÎÊáİÉ ãä ÓÑİÑ HTTP (æíÈ) . íÑÌì ÇáÇÊÕÇá ÈãÎÏã ISP Ãæ ãÏíÑ ÇáäÙÇã ááãÓÇÚÏÉ .<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "ÊÚĞÑ ÇáÏÎæá Åáì ÓÑİÑ FTP <b>%1\$s</b> ÈæÇÓØÉ ÇÓã ÇáãÓÊÎÏã <b>%2\$s</b>.<br /><br />åá ÇäÊ ãÊÃßÏ ãä ÕÍÉ ÇÓã ÇáãÓÊÎÏã æ ßáãÉ ÇáãÑæÑ ¿ íÑÌì ÇáÇÊÕÇá ÈãÎÏã ISP Ãæ ãÏíÑ ÇáäÙÇã ááãÓÇÚÏÉ .<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "ÊÚĞÑ ÇáÊÈÏíá Åáì ÇáäãØ ÇáÎÇãá passive Úáì ÓÑİÑ FTP <b>%1\$s</b>.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "ÊÚĞÑ ÇáÇÊÕÇá ÈÓÑİÑ FTP ÇáËÇäí (ÇáåÏİ) <b>%1\$s</b> Úáì ÇáãäİĞ <b>%2\$s</b>.<br /><br />åá ÇäÊ ãÊÃßÏ ãä ÕÍÉ ÚäæÇä ÓÑİÑ FTP ÇáËÇäí (ÇáåÏİ) ¿ åĞÇ íÍÏË áÃÓÈÇÈ ãÎÊáİÉ ãä ÓÑİÑ HTTP (æíÈ) . íÑÌì ÇáÇÊÕÇá ÈãÎÏã ISP Ãæ ãÏíÑ ÇáäÙÇã ááãÓÇÚÏÉ .<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "ÊÚĞÑ ÇáÏÎæá Åáì ÓÑİÑ FTP ÇáËÇäí (ÇáåÏİ) <b>%1\$s</b> ÈæÇÓØÉ ÇÓã ÇáãÓÊÎÏã <b>%2\$s</b>.<br /><br />åá ÃäÊ ãÊÃßÏ ãä ÕÍÉ ÇÓã ÇáãÓÊÎÏã æ ßáãÉ ÇáãÑæÑ ¿ íÑÌì ÇáÇÊÕÇá ÈãÎÏã ISP Ãæ ãÏíÑ ÇáäÙÇã ááãÓÇÚÏÉ .<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "ÊÚĞÑ ÇáÊÈÏíá Åáì ÇáäãØ ÇáÎÇãá passive Úáì ÓÑİÑ FTP ÇáËÇäí (ÇáåÏİ) <b>%1\$s</b>.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "ÊÚĞÑ ÅÚÇÏÉ ÊÓãíÉ ÇáãÌáÏ Ãæ Çáãáİ <b>%1\$s</b> Åáì <b>%2\$s</b>";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "ÊÚĞÑ ÊäİíĞ ÃãÑ ÇáãæŞÚ <b>%1\$s</b>. áÇÍÙ Çä ÃãÑ ÇáÊÕÑíÍ CHMOD ãÊÇÍ İŞØ Úáì ÓÑİÑÇÊ Unix FTP , æ ÛíÑ ãÊÇÍ Úáì ÓÑİÑÇÊ Windows FTP ..";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "Êã ÊÛííÑ ÊÕÑíÍ ÇáãÌáÏ <b>%1\$s</b> Åáì <b>%2\$s</b> ÈäÌÇÍ ! ";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "ãÚÇáÌÉ ÇáÚäÇÕÑ İí ÇáãÌáÏ <b>%1\$s</b> »";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "Êã ÊÛííÑ ÊÕÑíÍ Çáãáİ <b>%1\$s</b> Åáì <b>%2\$s</b> ÈäÌÇÍ !";
$net2ftp_messages["All the selected directories and files have been processed."] = "ÊãÊ ãÚÇáÌÉ ÌãíÚ ÇáÃÏáÉ æ ÇáãáİÇÊ ÇáãÍÏÏÉ .";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "ÊÚĞÑ ÍĞİ ÇáãÌáÏ <b>%1\$s</b>";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "ÊÚĞÑ ÍĞİ Çáãáİ <b>%1\$s</b>";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "ÊÚĞÑ ÅäÔÇÁ ÇáãÌáÏ <b>%1\$s</b>";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "ÊÚĞÑ ÅäÔÇÁ ãáİ ÇáÊÎÒíä ÇáãÄŞÊ";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "ÊÚĞÑ ÌáÈ Çáãáİ <b>%1\$s</b> ãä ÓÑİÑ FTP æ ÍİÙå İí ãáİ ÇáÊÎÒíä ÇáãÄŞÊ <b>%2\$s</b>.<br />ÊİÍÕ ÕáÇÍíÇÊ ÇáãÌáÏ %3\$s .<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "ÊÚĞÑ İÊÍ ãáİ ÇáÊÎÒíä ÇáãÄŞÊ . ÊİÍÕ ÕáÇÍíÇÊ ÇáãÌáÏ %1\$s .";
$net2ftp_messages["Unable to read the temporary file"] = "ÊÚĞÑ ŞÑÇÁÉ ãáİ ÇáÊÎÒíä ÇáãÄŞÊ";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "ÊÚĞÑ ÅÛáÇŞ ãáİ ÇáÊÎÒíä ÇáãÄŞÊ";
$net2ftp_messages["Unable to delete the temporary file"] = "ÊÚĞÑ ÍĞİ ãáİ ÇáÊÎÒíä ÇáãÄŞÊ";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "ÊÚĞÑ ÅäÔÇÁ ãáİ ÇáÊÎÒíä ÇáãÄŞÊ . ÊİÍÕ ÕáÇÍíÇÊ ÇáãÌáÏ %1\$s .";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "ÊÚĞÑ İÊÍ ãáİ ÇáÊÎÒíä ÇáãÄŞÊ . ÊİÍÕ ÕáÇÍíÇÊ ÇáãÌáÏ %1\$s .";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "ÊÚĞÑ ÇáßÊÇÈÉ Åáì ãáİ ÇáÊÎÒíä ÇáãÄŞÊ <b>%1\$s</b>.<br />ÊİÍÕ ÕáÇÍíÇÊ ÇáãÌáÏ %2\$s .";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "ÊÚĞÑ ÅÛáÇŞ ãáİ ÇáÊÎÒíä ÇáãÄŞÊ";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "ÊÚĞÑ æÖÚ Çáãáİ <b>%1\$s</b> Úáì ÓÑİÑ FTP .<br />ÑÈãÇ áÇ ÊãÊáß ÕáÇÍíÇÊ ÇáßÊÇÈÉ Åáì åĞÇ ÇáÏáíá !";
$net2ftp_messages["Unable to delete the temporary file"] = "ÊÚĞÑ ÍĞİ ãáİ ÇáÊÎÒíä ÇáãÄŞÊ";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "Processing directory <b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "ÇáÏáíá ÇáåÏİ <b>%1\$s</b> äİÓ ÇáãÕÏÑ Ãæ Ïáíá İÑÚí ãä ÇáÏáíá ÇáãÕÏÑ <b>%2\$s</b>, áĞÇ ÓíÊã ÊÎØí åĞÇ ÇáÏáíá .";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The directory <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "ÊÚĞÑ ÅäÔÇÁ ÇáÏáíá ÇáİÑÚí <b>%1\$s</b>. ÑÈãÇ íßæä ãæÌæÏ ãä . ãÊÇÈÚÉ ÚãáíÉ ÇáäÓÎ/ÇáäŞá ...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "ÅäÔÇÁ ÇáÏáíá ÇáİÑÚí ÇáåÏİ <b>%1\$s</b>";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "ÇáÏáíá <b>%1\$s</b> áÇ íãßä ÊÍÏíÏå . áĞÇ ÓíÊã ÊÎØí åĞÇ ÇáÏáíá .";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "ÊÚĞÑ ÍĞİ ÇáÏáíá ÇáİÑÚí <b>%1\$s</b> - ÑÈãÇ íßæä İÇÑÛ";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "Êã ÍĞİ ÇáÏáíá ÇáİÑÚí <b>%1\$s</b>";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "ÊãÊ ãÚÇáÌÉ ÇáÏáíá <b>%1\$s</b>";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "ÇáÏáíá ÇáåÏİ ááãáİ <b>%1\$s</b> íÈÏæ Ãäå ßÇáãÕÏÑ , áĞÇ ÓíÊã ÊÎØí åĞÇ Çáãáİ";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The file <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "The file <b>%1\$s</b> is too big to be copied, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "The file <b>%1\$s</b> is too big to be moved, aborting the move";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "ÊÚĞÑ äÓÎ Çáãáİ <b>%1\$s</b>";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Êã äÓÎ Çáãáİ <b>%1\$s</b>";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Unable to move the file <b>%1\$s</b>, aborting the move";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "Êã äŞá Çáãáİ <b>%1\$s</b>";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "ÊÚĞÑ ÍĞİ Çáãáİ <b>%1\$s</b>";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "Êã ÍĞİ Çáãáİ <b>%1\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "ÊãÊ ãÚÇáÌÉ ÌãíÚ ÇáÃÏáÉ æ ÇáãáİÇÊ ÇáãÍÏÏÉ .";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "ÊÚĞÑ äÓÎ Çáãáİ ÇáÈÚíÏ <b>%1\$s</b> Åáì Çáãáİ ÇáãÍáí ÈÇÓÊÎÏÇã äãØ FTP <b>%2\$s</b>";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "ÊÚĞÑ ÍĞİ Çáãáİ <b>%1\$s</b>";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "The file is too big to be transferred";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "ÇáÍÕÉ ÇáíæãíÉ ÇáãÓãæÍ ÈåÇ ÇäÊåÊ » Çáãáİ <b>%1\$s</b> áä íÊã ÊÑÍíáå";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "ÊÚĞÑ äÓÎ Çáãáİ ÇáãÍáí Åáì Çáãáİ ÇáÈÚíÏ <b>%1\$s</b> ÈÇÓÊÎÏÇã äãØ FTP <b>%2\$s</b>";
$net2ftp_messages["Unable to delete the local file"] = "ÊÚĞÑ ÍĞİ Çáãáİ ÇáãÍáí";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "ÊÚĞÑ ÍĞİ ãáİ ÇáÊÎÒíä ÇáãÄŞÊ";
$net2ftp_messages["Unable to send the file to the browser"] = "ÊÚĞÑ ÅÑÓÇá Çáãáİ Åáì ÇáãÓÊÚÑÖ";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "ÊÚĞÑ ÅäÔÇÁ ãáİ ÇáÊÎÒíä ÇáãÄŞÊ";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "Êã ÍİÙ Çáãáİ ÇáãÖÛæØ zip Åáì ÓÑİÑ FTP ÈÇÓã <b>%1\$s</b>";
$net2ftp_messages["Requested files"] = "ÇáãáİÇÊ ÇáãØáæÈÉ";

$net2ftp_messages["Dear,"] = "ÇáÓáÇã Úáíßã ,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "ÔÎÕ ãÇ ØáÈ ÅÑÓÇá ÇáãáİÇÊ ÇáãÑİŞÉ Åáì ÚäæÇä ÇáÈÑíÏ ÇáÇáßÊÑæäí (%1\$s).";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "Åä áã Êßä ÊÚÑİ ÔÆ Íæá åĞÇ , Ãæ Åä áã Êßä ãÚäí ÈåĞÇ ÇáÔÎÕ , íÑÌì ÍĞİ ÇáÑÓÇáÉ ÈÏæä İÊÍ Çáãáİ ÇáãÖÛæØ ÇáãÑİŞ .";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "ãáÇÍÙÉ - Åä áã ÊŞã ÈİÊÍ Çáãáİ ÇáãÖÛæØ , İáä ÊáÍŞ ÇáãáİÇÊ ÇáÊí ÈÏÇÎáå Ãí ÃĞì ÈÌåÇÒß Åä ßäÊ ÊÔß ÈåÇ .";
$net2ftp_messages["Information about the sender: "] = "ãÚáæãÇÊ Íæá ÇáãÑÓá » ";
$net2ftp_messages["IP address: "] = "ÚäæÇä IP » ";
$net2ftp_messages["Time of sending: "] = "æŞÊ ÇáÅÑÓÇá » ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "ÃÑÓáÊ ÈæÇÓØÉ ÈÑäÇãÌ net2ftp ÇáãÑßÈ Úáì åĞÇ ÇáãæŞÚ » ";
$net2ftp_messages["Webmaster's email: "] = "ÈÑíÏ ÇáÅÏÇÑÉ » ";
$net2ftp_messages["Message of the sender: "] = "ÑÓÇáÉ ÇáãÑÓá » ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "Êã ÅÑÓÇá Çáãáİ ÇáãÖÛæØ Åáì <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "ÍÌã Çáãáİ <b>%1\$s</b> ßÈíÑ ÌÏÇğ . áä íÊã ÑİÚ åĞÇ Çáãáİ .";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded.";
$net2ftp_messages["Could not generate a temporary file."] = "ÊÚĞÑ ÅäÔÇÁ ãáİ ÇáÊÎÒíä ÇáãÄŞÊ .";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "ÊÚĞÑ äŞá Çáãáİ <b>%1\$s</b>";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "Çáãİ <b>%1\$s</b> äÌÇÍ !";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "ÊÚĞÑ äŞá Çáãáİ ÇáãÑİæÚ Åáì ãÌáÏ temp .<br /><br />íÌÈ ãäÍ ÇáÊÕÑíÍ <b>chmod 777</b> Åáì ÇáãÌáÏ /temp İí Ïáíá net2ftp.";
$net2ftp_messages["You did not provide any file to upload."] = "áã ÊŞã ÈÊÍÏíÏ Ãí ãáİ áÑİÚå !";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "ÊÚĞÑ ÊÑÍíá Çáãáİ <b>%1\$s</b> Åáì ÓÑİÑ FTP";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "Êã ÊÑÍíá Çáãáİ <b>%1\$s</b> Åáì ÓÑİÑ FTP ÈÇÓÊÎÏÇã äãØ FTP <b>%2\$s</b>";
$net2ftp_messages["Transferring files to the FTP server"] = "ÊÑÍíá ÇáãáİÇÊ Åáì ÓÑİÑ FTP";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "ãÚÇáÌÉ ÇáÃÑÔíİ ÑŞã %1\$s » <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "ÊÚĞÑ ãÚÇáÌÉ ÇáÃÑÔíİ <b>%1\$s</b> ÈÓÈÈ ÚÏã ÏÚã åĞÇ ÇáäæÚ . İŞØ ÃäæÇÚ ÇáÃÑÔíİ zip, tar, tgz æ gz ãÏÚæãÉ ÍÇáíÇğ .";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Unable to extract the files and directories from the archive";
$net2ftp_messages["Created directory %1\$s"] = "Created directory %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "Could not create directory %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "Copied file %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "Could not copy file %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "Unable to delete the temporary directory";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Unable to delete the temporary file %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "ÊÚĞÑ ÊäİíĞ ÇãÑ ÇáãæŞÚ <b>%1\$s</b>";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "Êã ÅíŞÇİ ÇáãåãÉ";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "ÇáãåãÉ ÇáÊí ÊÑíÏ ÅäÌÇÒåÇ ÈæÇÓØÉ net2ftp ÇÓÊÛÑŞÊ æŞÊ ÃØæá ãä ÇáãÓãæÍ %1\$s ËÇäíÉ , æ áĞáß Êã ÅíŞÇİ ÇáãåãÉ .";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "åĞÇ ÇáæŞÊ ÇáãÍÏÏ áÖãÇä ÚÏÇáÉ ÇÓÊÎÏÇã ÇáÓÑİÑ ááÌãíÚ .";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "ÌÑÈ ÊÌÒÆÉ ãåãÊß Åáì ãåãÇÊ ÃÕÛÑ » Şáá ãä ÚÏÏ ÇáãáİÇÊ ÇáãÍÏÏÉ , æ ÇÍĞİ ÇáãáİÇÊ ÇáÃßÈÑ .";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "ÅĞÇ ßäÊ ÊÑíÏ ÍŞÇğ Êãßíä net2ftp ãä ÅäÌÇÒ ÇáãåÇã ÇáßÈíÑÉ ÇáÊí ÊÓÊÛÑŞ æŞÊ Øæíá , íãßäß ÇáÊİßíÑ İí ÊÑßíÈ ÈÑäÇãÌ net2ftp Úáì ãæŞÚß ãÈÇÔÑÉ .";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "áã ÊŞÏã Ãí äÕ áÅÑÓÇáå ÈæÇÓØÉ ÇáÈÑíÏ ÇáÇáßÊÑæäí !";
$net2ftp_messages["You did not supply a From address."] = "íÑÌì ßÊÇÈÉ ÚäæÇä ÈÑíÏ ÇáãÑÓá !";
$net2ftp_messages["You did not supply a To address."] = "íÑÌì ßÊÇÈÉ ÚäæÇä ÈÑíÏ ÇáãÊáŞí !";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "ÍÏË ÎØÃ ÊŞäí ÎáÇá ãÍÇæáÉ ÇáÅÑÓÇá Åáì <b>%1\$s</b> ÊÚĞÑ ÇáÅÑÓÇá .";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "íÑÌì ÅÏÎÇá ÇÓã ÇáãÓÊÎÏã æ ßáãÉ ÇáãÑæÑ áÓÑİÑ FTP ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "áã ÊŞã ÈßÊÇÈÉ ãÚáæãÇÊ ÇáÏÎæá İí äÇİĞÉ ÇáÈæÈ ÇÈ .<br />ÇÖÛØ Úáì \"ÇáĞåÇÈ Åáì ÕİÍÉ ÇáÏÎæá\" ÈÇáÃÓİá .";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "ÇáÏÎæá Åáì áæÍÉ ÇáÊÍßã ÛíÑ ãÊÇÍ , ÈÓÈÈ ÚÏã ÊÚííä ßáãÉ ãÑæÑ İí Çáãáİ settings.inc.php . ÃÏÎá ßáãÉ ÇáãÑæÑ İí Çáãáİ , Ëã ÃÚÏ ÊÍãíá åĞå ÇáÕİÍÉ .";
$net2ftp_messages["Please enter your Admin username and password"] = "íÑÌì ÅÏÎÇá ÇÓã ÇáãÓÊÎÏã æ ßáãÉ ÇáãÑæÑ ÇáÅÏÇÑíÉ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "áã ÊŞã ÈßÊÇÈÉ ãÚáæãÇÊ ÇáÏÎæá İí äÇİĞÉ ÇáÈæÈ ÇÈ .<br />ÇÖÛØ Úáì \"ÇáĞåÇÈ Åáì ÕİÍÉ ÇáÏÎæá\" ÈÇáÃÓİá .";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "ÎØÃ İí ÇÓã ÇáãÓÊÎÏã Ãæ ßáãÉ ÇáãÑæÑ ááæÍÉ ÇáÊÍßã . ÇÓã ÇáãÓÊÎÏã æ ßáãÉ ÇáãÑæÑ íãßä ÊÚííäåÇ İí Çáãáİ settings.inc.php .";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "Blue";
$net2ftp_messages["Grey"] = "Grey";
$net2ftp_messages["Black"] = "Black";
$net2ftp_messages["Yellow"] = "Yellow";
$net2ftp_messages["Pastel"] = "Pastel";

// getMime()
$net2ftp_messages["Directory"] = "ÇáÏáíá";
$net2ftp_messages["Symlink"] = "Symlink";
$net2ftp_messages["ASP script"] = "ãáİ ASP";
$net2ftp_messages["Cascading Style Sheet"] = "æÑŞÉ ÃäãÇØ ãÊÊÇáíÉ";
$net2ftp_messages["HTML file"] = "ãáİ HTML";
$net2ftp_messages["Java source file"] = "ãáİ ãÕÏÑ Java";
$net2ftp_messages["JavaScript file"] = "ãáİ JavaScript";
$net2ftp_messages["PHP Source"] = "ãÕÏÑ PHP";
$net2ftp_messages["PHP script"] = "ãáİ PHP";
$net2ftp_messages["Text file"] = "ãáİ äÕí";
$net2ftp_messages["Bitmap file"] = "ÕæÑÉ äŞØíÉ Bitmap";
$net2ftp_messages["GIF file"] = "ÕæÑÉ GIF";
$net2ftp_messages["JPEG file"] = "ÕæÑÉ JPEG";
$net2ftp_messages["PNG file"] = "ÕæÑÉ PNG";
$net2ftp_messages["TIF file"] = "ÕæÑÉ TIF";
$net2ftp_messages["GIMP file"] = "ãáİ GIMP";
$net2ftp_messages["Executable"] = "ãáİ ÊäİíĞí";
$net2ftp_messages["Shell script"] = "ãáİ Shell";
$net2ftp_messages["MS Office - Word document"] = "MS Office - ãÓÊäÏ Word";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - ÌÏæá Excel";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - ÚÑÖ ÊŞÏíãí PowerPoint";
$net2ftp_messages["MS Office - Access database"] = "MS Office - ŞÇÚÏÉ ÈíÇäÇÊ Access";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - ãÎØØ Visio";
$net2ftp_messages["MS Office - Project file"] = "MS Office - ãáİ ãÔÑæÚ";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - ãÓÊäÏ Writer 6.0";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - ŞÇáÈ Writer 6.0";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - ÌÏæá Calc 6.0";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - ŞÇáÈ Calc 6.0";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - ãÓÊäÏ Draw 6.0";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - ŞÇáÈ Draw 6.0";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - ÚÑÖ ÊŞÏíãí Impress 6.0";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - ŞÇáÈ Impress 6.0";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - ŞÇáÈ ÚÇã Writer 6.0";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - ãÓÊäÏ Math 6.0";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - ãÓÊäÏ StarWriter 5.x";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - ãÓÊäÏ ÚÇã StarWriter 5.x";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - ÌÏæá StarCalc 5.x";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - ãÓÊäÏ StarDraw 5.x";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - ÚÑÖ ÊŞÏíãí StarImpress 5.x";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - ãáİ StarImpress Packed 5.x";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - ãÓÊäÏ StarMath 5.x";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - ãÓÊäÏ StarChart 5.x";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - ãáİ ÈÑíÏ StarMail 5.x";
$net2ftp_messages["Adobe Acrobat document"] = "ãÓÊäÏ Adobe Acrobat";
$net2ftp_messages["ARC archive"] = "ÃÑÔíİ ARC";
$net2ftp_messages["ARJ archive"] = "ÃÑÔíİ ARJ";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "ÃÑÔíİ GZ";
$net2ftp_messages["TAR archive"] = "ÃÑÔíİ TAR";
$net2ftp_messages["Zip archive"] = "ÃÑÔíİ Zip";
$net2ftp_messages["MOV movie file"] = "ãáİ İíÏíæ MOV";
$net2ftp_messages["MPEG movie file"] = "ãáİ İíÏíæ MPEG movie file";
$net2ftp_messages["Real movie file"] = "ãáİ İíÏíæ Real";
$net2ftp_messages["Quicktime movie file"] = "ãáİ İíÏíæ Quicktime";
$net2ftp_messages["Shockwave flash file"] = "ãáİ İáÇÔ Shockwave";
$net2ftp_messages["Shockwave file"] = "ãáİ Shockwave";
$net2ftp_messages["WAV sound file"] = "ãáİ ãæÌÉ ÕæÊíÉ";
$net2ftp_messages["Font file"] = "ãáİ ÎØ";
$net2ftp_messages["%1\$s File"] = "ãáİ %1\$s";
$net2ftp_messages["File"] = "ãáİ";

// getAction()
$net2ftp_messages["Back"] = "ÎØæÉ ááÎáİ";
$net2ftp_messages["Submit"] = "ÇÚÊãÏ ÇáÈíÇäÇÊ";
$net2ftp_messages["Refresh"] = "ÊÍÏíË ÇáÕİÍÉ";
$net2ftp_messages["Details"] = "ÇáÊİÇÕíá";
$net2ftp_messages["Icons"] = "ÇáÑãæÒ";
$net2ftp_messages["List"] = "ÇáŞÇÆãÉ";
$net2ftp_messages["Logout"] = "ÊÓÌíá ÇáÎÑæÌ";
$net2ftp_messages["Help"] = "ãÓÇÚÏÉ";
$net2ftp_messages["Bookmark"] = "ÃÖİ Åáì ÇáãİÖáÉ";
$net2ftp_messages["Save"] = "ÍİÙ";
$net2ftp_messages["Default"] = "ÇáÇİÊÑÇÖí";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Ïáíá ÇáãÓÇÚÏÉ";
$net2ftp_messages["Forums"] = "ÇáãäÊÏíÇÊ";
$net2ftp_messages["License"] = "ÇáÊÑÎíÕ";
$net2ftp_messages["Powered by"] = "Powered by";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "ÓíÊã äŞáß ÇáÂä Åáì ãäÊÏíÇÊ net2ftp . åĞå ÇáãäÊÏíÇÊ ãÊÎÕÕÉ ÈãæÇÖíÚ ÈÑäÇãÌ net2ftp İŞØ  - æ áíÓ áÃÓÆáÉ ÇáÇÓÊÖÇİÉ ÇáÚÇãÉ .";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "ÇáÎíÇÑÇÊ ÇáÅÏÇÑíÉ";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "ãÚáæãÇÊ ÇáÅÕÏÇÑ";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "åĞÇ ÇáÅÕÏÇÑ ãä net2ftp ŞÇÈá ááÊÍÏíË .";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "ÊÚĞÑ ÌáÈ ÂÎÑ ãÚáæãÇÊ ÇáÅÕÏÇÑ ãä ÓÑİÑ net2ftp . ÊİÍÕ ÅÚÏÇÏÇÊ ÇáÃãÇä İí ãÓÊÚÑÖß , ÍíË ÊãäÚ ÊÍãíá ãáİ ÕÛíÑ ãä ÓÑİÑ net2ftp.com .";
$net2ftp_messages["Logging"] = "ÇáÏÎæá";
$net2ftp_messages["Date from:"] = "ÇáÊÇÑíÎ ãä »";
$net2ftp_messages["to:"] = "Åáì »";
$net2ftp_messages["Empty logs"] = "ÅİÑÇÛ ÇáÓÌá";
$net2ftp_messages["View logs"] = "ÚÑÖ ÇáÓÌá";
$net2ftp_messages["Go"] = "ÇĞåÈ";
$net2ftp_messages["Setup MySQL tables"] = "ÅÚÏÇÏ ÌÏÇæá MySQL";
$net2ftp_messages["Create the MySQL database tables"] = "ÅäÔÇÁ ÌÏÇæá ŞÇÚÏÉ ÇáÈíÇäÇÊ MySQL";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "ÇáÎíÇÑÇÊ ÇáÅÏÇÑíÉ";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "ÇÓã Çáãáİ %1\$s áÇ íãßä İÊÍå .";
$net2ftp_messages["The file %1\$s could not be opened."] = "ÊÚĞÑ İÊÍ Çáãáİ %1\$s .";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "ÇÓã Çáãáİ %1\$s áÇ íãßä İÊÍå .";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "ÊÚĞÑ ÅÚÏÇÏ ÇáÇÊÕÇá Åáì ÇáÓÑİÑ <b>%1\$s</b> . íÑÌì ÇáÊÃßÏ ãä ãÚáæãÇÊ ŞÇÚÏÉ ÇáÈíÇäÇÊ ÇáÊí ÇÏÎáÊåÇ .";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "ÊÚĞÑ ÊÍÏíÏ ŞÇÚÏÉ ÇáÈíÇäÇÊ <b>%1\$s</b>.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "ÊÚĞÑ ÊäİíĞ ÇÓÊÚáÇã SQL  nr <b>%1\$s</b> .";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "Êã ÊäİíĞ ÇÓÊÚáÇã SQL nr <b>%1\$s</b> ÈäÌÇÍ .";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "íÑÌì ÅÏÎÇá ÅÚÏÇÏÇÊ ŞÇÚÏÉ ÇáÈíÇäÇÊ MySQL »";
$net2ftp_messages["MySQL username"] = "ÇÓã ÇáãÓÊÎÏã MySQL";
$net2ftp_messages["MySQL password"] = "ßáãÉ ÇáãÑæÑ MySQL";
$net2ftp_messages["MySQL database"] = "ŞÇÚÏÉ ÇáÈíÇäÇÊ MySQL";
$net2ftp_messages["MySQL server"] = "ÓÑİÑ MySQL";
$net2ftp_messages["This SQL query is going to be executed:"] = "ÇÓÊÚáÇã SQL ÌÇåÒ ááÊäİíĞ »";
$net2ftp_messages["Execute"] = "ÊäİíĞ ÇáÇÓÊÚáÇã";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "ÇáÅÚÏÇÏÇÊ ÇáãÓÊÎÏãÉ »";
$net2ftp_messages["MySQL password length"] = "ÚãŞ ßáãÉ ãÑæÑ MySQL";
$net2ftp_messages["Results:"] = "ÇáäÊÇÆÌ »";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "ÇáÎíÇÑÇÊ ÇáÅÏÇÑíÉ";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "ÊÚĞÑ ÊäİíĞ ÇÓÊÚáÇã SQL <b>%1\$s</b>.";
$net2ftp_messages["No data"] = "áÇ íæÌÏ ÈíÇäÇÊ";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "ÇáÎíÇÑÇÊ ÇáÅÏÇÑíÉ";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "Êã ÅİÑÇÛ ÇáÌÏæá <b>%1\$s</b> ÈäÌÇÍ !";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "ÊÚĞÑ ÅİÑÇÛ ÇáÌÏæá <b>%1\$s</b> !";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "Êã ÅÕáÇÍ ÇáÌÏæá <b>%1\$s</b> ÈäÌÇÍ !";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "ÊÚĞÑ ÅÕáÇÍ ÇáÌÏæá <b>%1\$s</b> !";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "ÇáÎíÇÑÇÊ ÇáÅÏÇÑíÉ";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "ÇĞåÈ";
$net2ftp_messages["Disabled"] = "ãÚØá";
$net2ftp_messages["Advanced FTP functions"] = "æÙÇÆİ FTP ÇáãÊŞÏãÉ";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "ÅÑÓÇá ÃãÑ FTP ÊÍßãí Åáì ÓÑİÑ FTP";
$net2ftp_messages["This function is available on PHP 5 only"] = "åĞå ÇáæÙíİÉ ãÊæİÑÉ İŞØ Úáì PHP 5";
$net2ftp_messages["Troubleshooting functions"] = "æÙÇÆİ ÊÊÈÚ ÇáÃÎØÇÁ";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "ÊÊÈÚ ÃÎØÇÁ net2ftp Úáì ÓÑİ ÇáæíÈ åĞÇ";
$net2ftp_messages["Troubleshoot an FTP server"] = "ÊÊÈÚ ÃÎØÇÁ ÓÑİÑ FTP";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "ÇÎÊÈÇÑ ŞÇÆãÉ ŞæÇäíä ÊÚÇÈíÑ net2ftp";
$net2ftp_messages["Translation functions"] = "æÙÇÆİ ÇáÊÑÌãÉ";
$net2ftp_messages["Introduction to the translation functions"] = "ãŞÏãÉ Åáì æÙÇÆİ ÇáÊÑÌãÉ";
$net2ftp_messages["Extract messages to translate from code files"] = "ÇÓÊÎÑÇÌ ÇáÑÓÇÆá áÊÑÌãÊåÇ ãä ãáİÇÊ ÇáßæÏ";
$net2ftp_messages["Check if there are new or obsolete messages"] = "ÇáÊİÍÕ Úä æÌæÏ ÑÓÇÆá ÌÏíÏÉ Ãæ ÈÇØáÉ";

$net2ftp_messages["Beta functions"] = "æÙÇÆİ ÊÌÑíÈíÉ";
$net2ftp_messages["Send a site command to the FTP server"] = "ÅÑÓÇá ÃãÑ ÇáãæŞÚ ÅáÉ ÓÑİÑ FTP";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache » ÍãÇíÉ Ïáíá ÈßáãÉ ãÑæÑ , ÅäÔÇÁ ÕİÍÇÊ ÃÎØÇÁ ãÎÕÕÉ";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL » ÊäİíĞ ÇÓÊÚáÇã SQL";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "æÙÇÆİ ÃãÑÇ áãæŞÚ ÛíÑ ãÊÇÍÉ Úáì åĞÇ ÇáæíÈ ÓÑİÑ .";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "æÙÇÆİ ÃÈÇÊÔí ÛíÑ ãÊÇÍÉ Úáì åĞÇ ÇáæíÈ ÓÑİÑ .";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "æÙÇÆİ MySQL ÛíÑ ãÊÇÍÉ Úáì åĞÇ ÇáæíÈ ÓÑİÑ .";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "ÍÇáÉ 2 ÛíÑ ãŞÈæáÉ . ãæÌæÏ .";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "ÊÊÈÚ ÃÎØÇÁ ÓÑİÑ FTP";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "ÅÚÏÇÏÇÊ ÇáÇÊÕÇá »";
$net2ftp_messages["FTP server"] = "ÓÑİÑ FTP";
$net2ftp_messages["FTP server port"] = "ãäİĞ ÓÑİÑ FTP";
$net2ftp_messages["Username"] = "ÇÓã ÇáãÓÊÎÏã";
$net2ftp_messages["Password"] = "ßáãÉ ÇáãÑæÑ";
$net2ftp_messages["Password length"] = "Øæá ßáãÉ ÇáãÑæÑ";
$net2ftp_messages["Passive mode"] = "äãØ Passive ÇáÎãæá";
$net2ftp_messages["Directory"] = "ÇáÏáíá";
$net2ftp_messages["Printing the result"] = "ØÈÇÚÉ ÇáäÊíÌÉ";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "ÇáÇÊÕÇá ÈÓÑİÑ FTP » ";
$net2ftp_messages["Logging into the FTP server: "] = "ÇáÏÎæá Åáì ÓÑİÑ FTP » ";
$net2ftp_messages["Setting the passive mode: "] = "ÅÚÏÇÏ äãØ passive ÇáÎãæá » ";
$net2ftp_messages["Getting the FTP server system type: "] = "ÏÎæá äãØ äÙÇã ÓÑİÑ FTP » ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "ÇáÊÛííÑ Åáì ÇáÏáíá %1\$s » ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "ÇáÏáíá İí ÓÑİÑ FTP åæ » %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "ÇáÍÕæá Úáì ŞÇÆãÉ ÇáÃÏáÉ æ ÇáãáİÇÊ » ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "ãÍÇæáÉ ËÇäíÉ ááÍÕæá Úáì ŞÇÆãÉ ÇáÃÏáÉ æ ÇáãáİÇÊ » ";
$net2ftp_messages["Closing the connection: "] = "ÅÛáÇŞ ÇáÇÊÕÇá » ";
$net2ftp_messages["Raw list of directories and files:"] = "ŞÇÆãÉ ÇáÃÏáÉ æ ÇáãáİÇÊ »";
$net2ftp_messages["Parsed list of directories and files:"] = "ŞÇÆãÉ ÊÚÇÈíÑ ÇáÃÏáÉ æ ÇáãáİÇÊ »";

$net2ftp_messages["OK"] = "äÌÇÍ";
$net2ftp_messages["not OK"] = "İÔá";

} // end advanced_ftpserver


// -------------------------------------------------------------------------
// Advanced_parsing module
if ($net2ftp_globals["state"] == "advanced_parsing") {
// -------------------------------------------------------------------------

$net2ftp_messages["Test the net2ftp list parsing rules"] = "ÇÎÊÈÇÑ ŞÇÆãÉ ŞæÇäíä ÊÚÇÈíÑ net2ftp";
$net2ftp_messages["Sample input"] = "ÇÎÊÈÇÑ ÇáÏÎá";
$net2ftp_messages["Parsed output"] = "ÊÚÈíÑ ÇáÎÑÌ";

} // end advanced_parsing


// -------------------------------------------------------------------------
// Advanced_webserver module
if ($net2ftp_globals["state"] == "advanced_webserver") {
// -------------------------------------------------------------------------

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "ÊÊÈÚ ÃÎØÇÁ ÊÑßíÈ net2ftp";
$net2ftp_messages["Printing the result"] = "ØÈÇÚÉ ÇáäÊíÌÉ";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "ÇáÊÍŞŞ ãä ÊÑßíÈ æÙíİÉ FTP İí PHP » ";
$net2ftp_messages["yes"] = "äÚã";
$net2ftp_messages["no - please install it!"] = "áÇ - íÑÌì ÊÑßíÈåÇ !";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "ÇáÊÍŞŞ ãä ÕáÇÍíÇÊ ÇáÏáíá Úáì ÓÑŞÑ ÇáæíÈ » ÓíÊã ßÊÇÈÉ ãáİ ÕÛíÑ Åáì ÇáãÌáÏ /temp Ëã ÍĞİå .";
$net2ftp_messages["Creating filename: "] = "ÅäÔÇÁ ÇÓã Çáãáİ » ";
$net2ftp_messages["OK. Filename: %1\$s"] = "äÌÇÍ . ÇÓã Çáãáİ » %1\$s";
$net2ftp_messages["not OK"] = "İÔá";
$net2ftp_messages["OK"] = "äÌÇÍ";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "İÔá . ÊÃßÏ ãä ÕáÇÍíÇÊ ÇáÏáíá %1\$s ";
$net2ftp_messages["Opening the file in write mode: "] = "İÊÍ Çáãáİ İí äãØ ÇáßÊÇÈÉ » ";
$net2ftp_messages["Writing some text to the file: "] = "ßÊÇÈÉ ÈÚÖ ÇáäÕ İí Çáãáİ » ";
$net2ftp_messages["Closing the file: "] = "ÅÛáÇŞ Çáãáİ » ";
$net2ftp_messages["Deleting the file: "] = "ÍĞİ Çáãáİ » ";

$net2ftp_messages["Testing the FTP functions"] = "ÇÎÊÈÇÑ æÙÇÆİ FTP";
$net2ftp_messages["Connecting to a test FTP server: "] = "ÇáÇÊÕÇá áÇÎÊÈÇÑ ÓÑİÑ FTP » ";
$net2ftp_messages["Connecting to the FTP server: "] = "ÇáÇÊÕÇá ÈÓÑİÑ FTP » ";
$net2ftp_messages["Logging into the FTP server: "] = "ÇáÏÎæá Åáì ÓÑİÑ FTP » ";
$net2ftp_messages["Setting the passive mode: "] = "ÅÚÏÇÏ äãØ passive ÇáÎãæá » ";
$net2ftp_messages["Getting the FTP server system type: "] = "ÏÎæá äãØ äÙÇã ÓÑİÑ FTP » ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "ÇáÊÛííÑ Åáì ÇáÏáíá %1\$s » ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "ÇáÏáíá İí ÓÑİÑ FTP åæ » %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "ÇáÍÕæá Úáì ŞÇÆãÉ ÇáÃÏáÉ æ ÇáãáİÇÊ » ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "ãÍÇæáÉ ËÇäíÉ ááÍÕæá Úáì ŞÇÆãÉ ÇáÃÏáÉ æ ÇáãáİÇÊ » ";
$net2ftp_messages["Closing the connection: "] = "ÅÛáÇŞ ÇáÇÊÕÇá » ";
$net2ftp_messages["Raw list of directories and files:"] = "ŞÇÆãÉ ÇáÃÏáÉ æ ÇáãáİÇÊ »";
$net2ftp_messages["Parsed list of directories and files:"] = "ŞÇÆãÉ ÊÚÇÈíÑ ÇáÃÏáÉ æ ÇáãáİÇÊ »";
$net2ftp_messages["OK"] = "äÌÇÍ";
$net2ftp_messages["not OK"] = "İÔá";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "ÃÖİ åĞÇ ÇáÑÇÈØ ÅáÉ ãİÖáÊß »";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer » ÇÖÛØ ÈÇáÒÑ ÇáÃíãä İæŞ ÇáÑÇÈØ æ ÇÎÊÑ \"ÅÖÇİÉ Åáì ÇáãİÖáÉ...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "ãáÇÍÙÉ » ÚäÏ ÇÓÊÎÏÇã ÇáÇÎÊÕÇÑ ãä ÇáãİÖáÉ , ÓíØáÈ ãäß ÈæÇÓØÉ äÇİĞÉ ÈæÈ ÇÈ ÅÏÎÇá ÇÓã ÇáãÓÊÎÏã æ ßáãÉ ÇáãÑæÑ .";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "ÇÎÊÑ Ïáíá";
$net2ftp_messages["Please wait..."] = "íÑÌì ÇáÇäÊÙÇÑ ...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "ÇáÃÏáÉ ÇáÊí ÊÍÊæí ÇÓãÇÆåÇ Úáì \' áÇ íãßä ÚÑÖåÇ ÈÔßá ÕÍíÍ . íãßä İŞØ ÍĞİåÇ . íÑÌì ÇáÚæÏÉ ááÎáİ æ ÇÎÊíÇÑ Ïáíá İÑÚí ÂÎÑ .";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "ÇáÍÕÉ ÇáíæãíÉ ÇäÊåÊ » áÇ íãßäß ãÊÇÈÚÉ ÊÑÍíá ÇáÈíÇäÇÊ .";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "áÖãÇä ÇÓÊÎÏÇã ÇáÓÑİÑ æíÈ ááÌãíÚ , Êã ÊÍÏíÏ ÍÕÉ íæãíÉ áÊÑÍíá ÇáÈíÇäÇÊ æ ÇáãáİÇÊ áßá ãÓÊÎÏã . ÚäÏ ÇÓÊåáÇßß áåĞå ÇáÍÕÉ , ÊÓØíÚ ÇÓÊÚÑÇÖ ÓÑİÑ FTP æ áßä áÇ íãßäß ãÊÇÈÚÉ äŞá ÇáÈíÇäÇÊ ãä æ Åáì .";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "ÅĞÇ ßäÊ ÊÑíÏ ÇÓÊÎÏÇã åĞå ÇÎÏãÉ ÈÏæä ÍÏæÏ , íãßäß ÊÑßíÈ net2ftp Úáì ÓÑİÑß ÇáÎÇÕ .";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "Ïáíá ÌÏíÏ";
$net2ftp_messages["New file"] = "ãáİ ÌÏíÏ";
$net2ftp_messages["HTML templates"] = "ŞæÇáÈ HTML";
$net2ftp_messages["Upload"] = "ÇáÑİÚ";
$net2ftp_messages["Java Upload"] = "ÇáÑİÚ ÈÜ Java";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Advanced"] = "ãÊŞÏã";
$net2ftp_messages["Copy"] = "äÓÎ";
$net2ftp_messages["Move"] = "äŞá";
$net2ftp_messages["Delete"] = "ÍĞİ";
$net2ftp_messages["Rename"] = "ÅÚÇÏÉ ÊÓãíÉ";
$net2ftp_messages["Chmod"] = "ÊÕÑíÍ";
$net2ftp_messages["Download"] = "ÊÍãíá";
$net2ftp_messages["Unzip"] = "Unzip";
$net2ftp_messages["Zip"] = "Zip";
$net2ftp_messages["Size"] = "ÇáÍÌã";
$net2ftp_messages["Search"] = "ÈÍË";
$net2ftp_messages["Go to the parent directory"] = "ÇáĞåÇÈ Åáì ÇáãÌáÏ ÇáÃÕá";
$net2ftp_messages["Go"] = "ÇĞåÈ";
$net2ftp_messages["Transform selected entries: "] = "ÊÍæíá ÇáÚäÇÕÑ ÇáãÍÏÏÉ » ";
$net2ftp_messages["Transform selected entry: "] = "ÊÍæíá ÇáÚäÕÑ ÇáãÍÏÏ » ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "ÅäÔÇÁ Ïáíá İÑÚí ÌÏíÏ İí ÇáÏáíá %1\$s";
$net2ftp_messages["Create a new file in directory %1\$s"] = "ÅäÔÇÁ ãáİ ÌÏíÏ İí ÇáÏáíá %1\$s";
$net2ftp_messages["Create a website easily using ready-made templates"] = "ÅäÔÇÁ ÇáãæÇŞÚ Óåá ÈÇÓÊÎÏÇã ÇáŞæÇáÈ ÇáÌÇåÒÉ";
$net2ftp_messages["Upload new files in directory %1\$s"] = "ÑİÚ ãáİÇÊ ÌÏíÏ Åáì ÇáÏáíá %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "ÑİÚ ÇáãÌáÏÇÊ æ ÇáãáİÇÊ ÈæÇÓØÉ Java applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Install software packages (requires PHP on web server)";
$net2ftp_messages["Go to the advanced functions"] = "ÇáĞåÇÈ Åáì ÇáæÙÇÆİ ÇáãÊŞÏãÉ";
$net2ftp_messages["Copy the selected entries"] = "äÓÎ ÇáÚäÇÕÑ ÇáãÍÏÏÉ";
$net2ftp_messages["Move the selected entries"] = "äŞá ÇáÚäÇÕÑ ÇáãÍÏÏÉ";
$net2ftp_messages["Delete the selected entries"] = "ÍĞİ ÇáÚäÇÕÑ ÇáãÍÏÏÉ";
$net2ftp_messages["Rename the selected entries"] = "ÅÚÇÏÉ ÊÓãíÉ ÇáÚäÇÕÑ ÇáãÍÏÏÉ";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "ÊÕÑíÍ ÇáÚäÇÕÑ ÇáãÍÏÏÉ (íÚãá İŞØ Úáì ÓÑİÑÇÊ Unix/Linux/BSD)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "ÊÍãíá ãáİ zip íÍÊæí Úáì ÌãíÚ ÇáÚäÇÕÑ ÇáãÍÏÏÉ";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Unzip the selected archives on the FTP server";
$net2ftp_messages["Zip the selected entries to save or email them"] = "ÖÛØ Zip ÇáÚäÇÕÑ ÇáãÍÏÏÉ áÍİÙåÇ Ãæ ÅÑÓÇáåÇ ÈÇáÈÑíÏ";
$net2ftp_messages["Calculate the size of the selected entries"] = "ÍÓÇÈ ÍÌã ÇáÚäÇÕÑ ÇáãÍÏÏÉ";
$net2ftp_messages["Find files which contain a particular word"] = "ÅíÌÇÏ ÇáãáİÇÊ ÇáÊí ÊÊÖãä ÇáßáãÉ ÌÒÆíÇğ";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "ÇÖÛØ áİÑÒ %1\$s ÈÊÑÊíÈ ÊäÇÒáí";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "ÇÖÛØ áİÑÒ %1\$s ÈÊÑÊíÈ ÊÕÇÚÏí";
$net2ftp_messages["Ascending order"] = "ÊÑÊíÈ ÊÕÇÚÏí";
$net2ftp_messages["Descending order"] = "ÊÑÊíÈ ÊäÇÒáí";
$net2ftp_messages["Up"] = "ÎØæÉ Åáì ÇáÃÚáì";
$net2ftp_messages["Click to check or uncheck all rows"] = "ÇÖÛØ áÊÍÏíÏ Ãæ ÅáÛÇÁ ÊÍÏíÏ ÌãíÚ ÇáÕİæİ";
$net2ftp_messages["All"] = "Çáßá";
$net2ftp_messages["Name"] = "ÇáÇÓã";
$net2ftp_messages["Type"] = "ÇáäæÚ";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "ÇáãÇáß";
$net2ftp_messages["Group"] = "ÇáãÌãæÚÉ";
$net2ftp_messages["Perms"] = "ÇáÕáÇÍíÉ";
$net2ftp_messages["Mod Time"] = "äãØ ÇáæŞÊ";
$net2ftp_messages["Actions"] = "ÇáÅÌÑÇÁÇÊ";
$net2ftp_messages["Select the directory %1\$s"] = "ÍÏÏ ÇáÏáíá %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "ÍÏÏ Çáãáİ %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "ÍÏÏ symlink %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "ÇáĞåÇÈ Åáì ÇáÏáíá ÇáİÑÚí %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "ÊÍãíá Çáãáİ %1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "Follow symlink %1\$s";
$net2ftp_messages["View"] = "ÚÑÖ";
$net2ftp_messages["Edit"] = "ÊÍÑíÑ";
$net2ftp_messages["Update"] = "ÊÍÏíË";
$net2ftp_messages["Open"] = "İÊÍ";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "ÚÑÖ ßæÏ ÇáãÕÏÑ ÇáããíÒ ááãáİ %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "ÊÍÑíÑ ßæÏ ÇáãÕÏÑ ááãáİ %1\$s";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "ÑİÚ äÓÎÉ ÌÏíÏÉ ãä Çáãáİ %1\$s æ ÏãÌ ÇáÊÚÏíáÇÊ";
$net2ftp_messages["View image %1\$s"] = "ÚÑÖ ÇáÕæÑÉ %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "ÚÑÖ Çáãáİ %1\$s ÈæÇÓØÉ ÓÑİÑ ÇáæíÈ HTTP";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(ãáÇÍÙÉ » ŞÏ áÇ íÚãá åĞÇ ÇáÑÇÈØ Åä áã íßä áÏíß Ïæãíä ÎÇÕ .)";
$net2ftp_messages["This folder is empty"] = "åĞÇ ÇáãÌáÏ İÇÑÛ";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "ÇáãÌáÏÇÊ";
$net2ftp_messages["Files"] = "ÇáãáİÇÊ";
$net2ftp_messages["Symlinks"] = "Symlinks";
$net2ftp_messages["Unrecognized FTP output"] = "ÎÑÌ FTP ÛíÑ ãÚÑæİ";
$net2ftp_messages["Number"] = "ÇáÚÏÏ";
$net2ftp_messages["Size"] = "ÇáÍÌã";
$net2ftp_messages["Skipped"] = "Êã ÊÎØíå";

// printLocationActions()
$net2ftp_messages["Language:"] = "ÇááÛÉ »";
$net2ftp_messages["Skin:"] = "ÇáÔßá »";
$net2ftp_messages["View mode:"] = "ØÑíŞÉ ÇáÚÑÖ »";
$net2ftp_messages["Directory Tree"] = "ÔÌÑÉ ÇáÏáíá";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "ÊäİíĞ %1\$s İí äÇİĞÉ ÌÏíÏÉ";
$net2ftp_messages["This file is not accessible from the web"] = "áÇ íãßä ÇáæÕæá Åáì åĞÇ Çáãáİ ãä ÇáæíÈ";


// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "ÖÛØ ãÒĞæÌ ááĞåÇÈ Åáì ÇáÏáíá ÇáİÑÚí";
$net2ftp_messages["Choose"] = "ÇÎÊíÇÑ";
$net2ftp_messages["Up"] = "ÎØæÉ Åáì ÇáÃÚáì";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "ÍÌã ÇáãÌáÏÇÊ æ ÇáãáİÇÊ ÇáãÍÏÏÉ";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "ãÌãæÚ ÍÌã ÇáãÌáÏÇÊ æ ÇáãáİÇÊ ÇáãÍÏÏÉ åæ »";
$net2ftp_messages["The number of files which were skipped is:"] = "ÚÏÏ ÇáãáİÇÊ ÇáÊí Êã ÊÎØíåÇ åæ »";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "ÊÕÑíÍ ÇáãÌáÏÇÊ æ ÇáãáİÇÊ";
$net2ftp_messages["Set all permissions"] = "ÊÚííä ÌãíÚ ÇáÕáÇÍíÇÊ";
$net2ftp_messages["Read"] = "ŞÑÇÁÉ";
$net2ftp_messages["Write"] = "ßÊÇÈÉ";
$net2ftp_messages["Execute"] = "ÊäİíĞ ÇáÇÓÊÚáÇã";
$net2ftp_messages["Owner"] = "ÇáãÇáß";
$net2ftp_messages["Group"] = "ÇáãÌãæÚÉ";
$net2ftp_messages["Everyone"] = "Ãí ÔÎÕ";
$net2ftp_messages["To set all permissions to the same values, enter those permissions above and click on the button \"Set all permissions\""] = "áÊÚííä ÌãíÚ ÇáÕáÇÍíÇÊ Åáì äİÓ ÇáŞíãÉ , ÍÏÏ ÇáÕáÇÍíÇÊ Ëã ÇÖÛØ ÒÑ \"ÊÚííä ÌãíÚ ÇáÕáÇÍíÇÊ\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "ÊÚííä ÕáÇÍíÇÊ ÇáãÌáÏ <b>%1\$s</b> Åáì » ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "ÊÚííä ÕáÇÍíÇÊ Çáãáİ <b>%1\$s</b> Åáì » ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "ÊÚííä ÕáÇÍíÇÊ symlink <b>%1\$s</b> Åáì » ";
$net2ftp_messages["Chmod value"] = "ŞíãÉ ÇáÊÕÑíÍ";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "ÊØÈíŞ ÇáÊÕÑíÍ Úáì ÇáãÌáÏÇÊ ÇáİÑÚíÉ İí åĞÇ ÇáãÌáÏ";
$net2ftp_messages["Chmod also the files within this directory"] = "ÊØÈíŞ ÇáÊÕÑíÍ Úáì ÇáãáİÇÊ ÏÇÎá åĞÇ ÇáãÌáÏ";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "ÇáÊÕÑíÍ nr <b>%1\$s</b> ÎÇÑÌ äØÇŞ 000-777. íÑÌì ÇáãÍÇæáÉ ãä ÌÏíÏ .";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "ÇÎÊÑ Ïáíá";
$net2ftp_messages["Copy directories and files"] = "äÓÎ ÇáãÌáÏÇÊ æ ÇáãáİÇÊ";
$net2ftp_messages["Move directories and files"] = "äŞá ÇáãÌáÏÇÊ æ ÇáãáİÇÊ";
$net2ftp_messages["Delete directories and files"] = "ÍĞİ ÇáãÌáÏÇÊ æ ÇáãáİÇÊ";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "åá ÇäÊ ãÊÃßÏ ãä Ãäß ÊÑíÏ ÍĞİ åĞå ÇáãÌáÏÇÊ æ ÇáãáİÇÊ ¿";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "ÌãíÚ ÇáãÌáÏÇÊ ÇáİÑÚíÉ æ ÇáãáİÇÊ İí ÇáãÌáÏÇÊ ÇáãÍÏÏÉ Óæİ ÊÍĞİ !";
$net2ftp_messages["Set all targetdirectories"] = "ÊÚííä ÌãíÚ ÇáÃÏáÉ ÇáåÏİ";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "áÊÚííä Ïáíá åÏİ ãÔÊÑß , ÃÏÎá ÇáÏáíá ÇáåÏİ İí ÇáÍŞá ÇáäÕí ÇáÓÇÈŞ Ëã ÇÖÛØ ÒÑ \"ÊÚííä ÌãíÚ ÇáÃÏáÉ ÇáåÏİ\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "ãáÇÍÙÉ » ÇáÏáíá ÇáåÏİ íÌÈ Ãä íßæä ãæÌæÏ ÃæáÇğ .";
$net2ftp_messages["Different target FTP server:"] = "ÓÑİÑ FTP ÇáÂÎÑ ÇáåÏİ »";
$net2ftp_messages["Username"] = "ÇÓã ÇáãÓÊÎÏã";
$net2ftp_messages["Password"] = "ßáãÉ ÇáãÑæÑ";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "ÇÊÑßå İÇÑÛ ÅĞÇ ßäÊ ÊÑíÏ äÓÎ ÇáãáİÇÊ Åáì äİÓ ÓÑİÑ FTP .";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "ÅĞÇ ßäÊ ÊÑíÏ äÓÎ ÇáãáİÇÊ Åáì ÓÑİÑ FTP ÂÎÑ , ÃÏÎá ÈíÇäÇÊ ÇáÏÎæá .";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "ÇÊÑßå İÇÑÛ ÅĞÇ ßäÊ ÊÑíÏ äŞá ÇáãáİÇÊ Åáì äİÓ ÓÑİÑ FTP .";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "ÅĞÇ ßäÊ ÊÑíÏ äŞá ÇáãáİÇÊ Åáì ÓÑİÑ FTP ÂÎÑ , ÃÏÎá ÈíÇäÇÊ ÇáÏÎæá .";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "äÓÎ ÇáãÌáÏ <b>%1\$s</b> Åáì »";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "äŞá ÇáãÌáÏ <b>%1\$s</b> Åáì »";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "ÇáãÌáÏ <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "äÓÎ Çáãáİ <b>%1\$s</b> Åáì »";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "äŞá Çáãáİ <b>%1\$s</b> Åáì »";
$net2ftp_messages["File <b>%1\$s</b>"] = "Çáãáİ <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "äÓÎ symlink <b>%1\$s</b> Åáì »";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "äŞá symlink <b>%1\$s</b> Åáì »";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Symlink <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "ÇáãÌáÏ ÇáåÏİ »";
$net2ftp_messages["Target name:"] = "ÇÓã ÇáåÏİ »";
$net2ftp_messages["Processing the entries:"] = "ãÚÇáÌÉ ÇáÚäÇÕÑ »";

} // end copymovedelete


// -------------------------------------------------------------------------
// Download file module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// EasyWebsite module
if ($net2ftp_globals["state"] == "easyWebsite") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create a website in 4 easy steps"] = "ÅäÔÇÁ ãæŞÚ İí 4 ÎØæÇÊ ÓåáÉ";
$net2ftp_messages["Template overview"] = "ÎáÇÕÉ ÇáŞÇáÈ";
$net2ftp_messages["Template details"] = "ÊİÇÕíá ÇáŞÇáÈ";
$net2ftp_messages["Files are copied"] = "Êã äÓÎ ÇáãáİÇÊ";
$net2ftp_messages["Edit your pages"] = "ÊÍÑíÑ ÕİÍÇÊß";

// Screen 1 - printTemplateOverview
$net2ftp_messages["Click on the image to view the details of a template."] = "ÇÖÛØ Úáì ÇáÕæÑÉ áÚÑÖ ÊİÇÕíá ÇáŞÇáÈ .";
$net2ftp_messages["Back to the Browse screen"] = "ÇáÚæÏÉ Åáì ÔÇÔÉ ÇáãÓÊÚÑÖ";
$net2ftp_messages["Template"] = "ÇáŞÇáÈ";
$net2ftp_messages["Copyright"] = "ÍŞæŞ ÇáäÔÑ";
$net2ftp_messages["Click on the image to view the details of this template"] = "ÇÖÛØ Úáì ÇáÕæÑÉ áÚÑÖ ÊİÇÕíá ÇáŞÇáÈ .";

// Screen 2 - printTemplateDetails
$net2ftp_messages["The template files will be copied to your FTP server. Existing files with the same filename will be overwritten. Do you want to continue?"] = "ÓíÊã äÓÎ ãáİÇÊ ÇáŞÇáÈ Åáì ÓÑİÑß FTP .ÇáãáİÇÊ ÇáÊí ÊÍãá äİÓ ÇáÇÓã ÓíÊã ÇáßÊÇÈÉ İæŞåÇ . åá ÊÑÛÈ ÈÇáãÊÇÈÚÉ ¿";
$net2ftp_messages["Install template to directory: "] = "ÊÑßíÈ ÇáŞÇáÈ İí ÇáÏáíá » ";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Size"] = "ÇáÍÌã";
$net2ftp_messages["Preview page"] = "ãÚÇíäÉ ÇáÕİÍÉ";
$net2ftp_messages["opens in a new window"] = "İí İí äÇİĞÉ ÌÏíÏÉ";

// Screen 3
$net2ftp_messages["Please wait while the template files are being transferred to your server: "] = "íÑÌì ÇáÇäÊÙÇÑ ÈíäãÇ íÊã äÓÎ ãáİÇÊ ÇáŞÇáÈ Åáì ÓÑİÑß » ";
$net2ftp_messages["Done."] = "ÊÜã .";
$net2ftp_messages["Continue"] = "ÇáãÊÇÈÚÉ";

// Screen 4 - printEasyAdminPanel
$net2ftp_messages["Edit page"] = "ÊÍÑíÑ ÇáÕİÍÉ";
$net2ftp_messages["Browse the FTP server"] = "ÇÓÊÚÑÇÖ ÓÑİÑ FTP";
$net2ftp_messages["Add this link to your favorites to return to this page later on!"] = "ÅÖÇİÉ åĞÇ ÇáÑÇÈØ Åáì ãİÖáÊß ááÚæÏÉ Åáì åĞå ÇáÕİÎÉ İíãÇ ÈÚÏ !";
$net2ftp_messages["Edit website at %1\$s"] = "ÊÍÑíÑ ãæŞÚ ÇáæíÈ İí %1\$s";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer » ÇÖÛØ ÈÇáÒÑ ÇáÃíãä İæŞ ÇáÑÇÈØ æ ÇÎÊÑ \"ÅÖÇİÉ Åáì ÇáãİÖáÉ...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\"";

// ftp_copy_local2ftp
$net2ftp_messages["WARNING: Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing..."] = "ÊÍĞíÑ » ÊÚĞÑ ÅäÔÇÁ ÇáÏáíá ÇáİÑÚí <b>%1\$s</b> . ÑÈãÇ íßæä ãæÌæÏ ãä ŞÈá . ÇáãÊÇÈÚÉ...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "ÅäÔÇÁ ÇáÏáíá ÇáİÑÚí ÇáåÏİ <b>%1\$s</b>";
$net2ftp_messages["WARNING: Unable to copy the file <b>%1\$s</b>. Continuing..."] = "ÊÍĞíÑ » ÊÚĞÑ äÓÎ Çáãáİ <b>%1\$s</b> . ÇáãÊÇÈÚÉ ...";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Êã äÓÎ Çáãáİ <b>%1\$s</b>";
}


// -------------------------------------------------------------------------
// Edit module
if ($net2ftp_globals["state"] == "edit") {
// -------------------------------------------------------------------------

// /modules/edit/edit.inc.php
$net2ftp_messages["Unable to open the template file"] = "ÊÚĞÑ İÊÍ ãáİ ÇáŞÇáÈ";
$net2ftp_messages["Unable to read the template file"] = "ÊÚĞÑ ŞÑÇÁÉ ãáİ ÇáŞÇáÈ";
$net2ftp_messages["Please specify a filename"] = "íÑÌì ÊÍÏíÏ ÇÓã Çáãáİ";
$net2ftp_messages["Status: This file has not yet been saved"] = "ÇáÍÇáÉ » áã íÊã ÍİÙ åĞÇ Çáãáİ ÈÚÏ";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "ÇáÍÇáÉ » Êã ÇáÍİÙ İí <b>%1\$s</b> ÈÇÓÊÎÏÇã ÇáäãØ %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "ÇáÍÇáÉ » <b>ÊÚĞÑ ÍİÙ åĞÇ Çáãáİ</b>";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "ÇáãÌáÏ » ";
$net2ftp_messages["File: "] = "Çáãáİ » ";
$net2ftp_messages["New file name: "] = "ÇÓã Çáãáİ ÇáÌÏíÏ » ";
$net2ftp_messages["Character encoding: "] = "Character encoding: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "ãáÇÍÙÉ » ÊÛííÑ äæÚ ÕäÏæŞ ÇáäÕ Óæİ íÍİÙ åĞå ÇáÊÚÏíáÇÊ";
$net2ftp_messages["Copy up"] = "äÓÎ Åáì";
$net2ftp_messages["Copy down"] = "äÓÎ ãä";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "ÈÍË İí ÇáãÌáÏÇÊ æ ÇáãáİÇÊ";
$net2ftp_messages["Search again"] = "ÈÍË ÌÏíÏ";
$net2ftp_messages["Search results"] = "äÊÇÆÌ ÇáÈÍË";
$net2ftp_messages["Please enter a valid search word or phrase."] = "íÑÌì ÅÏÎÇá ßáãÉ Ãæ ÊÚÈíÑ ãŞÈæá ááÈÍË .";
$net2ftp_messages["Please enter a valid filename."] = "íÑÌì ÅÏÎÇá ÇÓã ãáİ ãŞÈæá .";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "íÑÌì ÅÏÎÇá ÍÌã ãáİ ãŞÈæá İí ÕäÏæŞ ÇáäÕ \"ãä\" , ãËÇá 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "íÑÌì ÅÏÎÇá ÍÌã ãáİ ãŞÈæá İí ÕäÏæŞ ÇáäÕ \"Åáì\" , ãËÇá 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "íÑÌì ÅÏÎÇá ÊÇÑíÎ ãŞÈæá İí ÇáÍŞá \"ãä\" ÈÊäÓíŞ Y-m-d .";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "íÑÌì ÅÏÎÇá ÊÇÑíÎ ãŞÈæá İí ÇáÍŞá \"Åáì\" ÈÊäÓíŞ Y-m-d .";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "áã íÊã ÇáÚËæÑ Úáì ÇáßáãÉ <b>%1\$s</b> İí ÇáãÌáÏÇÊ æ ÇáãáİÇÊ ÇáãÍÏÏÉ .";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "Êã ÇáÚËæÑ Úáì ÇáßáãÉ <b>%1\$s</b> İí ÇáãáİÇÊ ÇáÊÇáíÉ »";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "ÈÍË Úä ßáãÉ Ãæ ÊÚÈíÑ";
$net2ftp_messages["Case sensitive search"] = "ÈÍË ãØÇÈŞ áÍÇáÉ ÇáÃÍÑİ";
$net2ftp_messages["Restrict the search to:"] = "ÇŞÊÕÇÑ ÇáÈÍË Úáì »";
$net2ftp_messages["files with a filename like"] = "ÇáãáİÇÊ ĞÇÊ ÇÓã Çáãáİ ããÇËá";
$net2ftp_messages["(wildcard character is *)"] = "(ãÍÑİ ÊÚãíã ÇáÈÍË åæ *)";
$net2ftp_messages["files with a size"] = "ÇáãáİÇÊ ĞÇÊ ÇáÍÌã";
$net2ftp_messages["files which were last modified"] = "ÇáãáİÇÊ ĞÇÊ ÂÎÑ ÊÚÏíá ßÇä";
$net2ftp_messages["from"] = "ãä";
$net2ftp_messages["to"] = "Åáì";

$net2ftp_messages["Directory"] = "ÇáÏáíá";
$net2ftp_messages["File"] = "ãáİ";
$net2ftp_messages["Line"] = "ÇáÓØÑ";
$net2ftp_messages["Action"] = "ÇáÅÌÑÇÁ";
$net2ftp_messages["View"] = "ÚÑÖ";
$net2ftp_messages["Edit"] = "ÊÍÑíÑ";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "ÚÑÖ ßæÏ ÇáãÕÏÑ ÇáããíÒ ááãáİ %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "ÊÍÑíÑ ßæÏ ÇáãÕÏÑ ááãáİ %1\$s";

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
$net2ftp_messages["Unable to open the template file"] = "ÊÚĞÑ İÊÍ ãáİ ÇáŞÇáÈ";
$net2ftp_messages["Unable to read the template file"] = "ÊÚĞÑ ŞÑÇÁÉ ãáİ ÇáŞÇáÈ";
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
$net2ftp_messages["Upload directories and files using a Java applet"] = "ÑİÚ ÇáãÌáÏÇÊ æ ÇáãáİÇÊ ÈæÇÓØÉ Java applet";
$net2ftp_messages["Number of files:"] = "Number of files:";
$net2ftp_messages["Size of files:"] = "Size of files:";
$net2ftp_messages["Add"] = "Add";
$net2ftp_messages["Remove"] = "Remove";
$net2ftp_messages["Upload"] = "ÇáÑİÚ";
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
$net2ftp_messages["Login!"] = "ÊÓÌíá ÇáÏÎæá !";
$net2ftp_messages["Once you are logged in, you will be able to:"] = "Once you are logged in, you will be able to:";
$net2ftp_messages["Navigate the FTP server"] = "ÇÓÊÚÑÇÖ ÓÑİÑ FTP";
$net2ftp_messages["Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."] = "ÇáÊäŞá ãä ãÌáÏ Åáì ãÌáÏ æ ÇÓÊÚÑÇÖ ÌãíÚ ÇáãÌáÏÇÊ ÇáİÑÚíÉ æ ÇáãáİÇÊ .";
$net2ftp_messages["Upload files"] = "ÑİÚ ÇáãáİÇÊ";
$net2ftp_messages["There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet."] = "íæÌÏ 3 ØÑŞ ãÎÊáİÉ áÑİÚ ÇáãáİÇÊ » 1 - ÇáØÑíŞÉ ÇáÚÇÏíÉ ÇáãÚÑæİÉ . 2 - ØÑíŞÉ ÑİÚ ãáİ ãÖÛæØ Ëã İß ÇáÖÛØ ÊáŞÇÆíÇğ . 3 - ØÑíŞÉ ÇáÌÇİÇ ÃÈáíÊ .";
$net2ftp_messages["Download files"] = "ÊÍãíá ÇáãáİÇÊ";
$net2ftp_messages["Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive."] = "ÇÖÛØ Úáì ÇÓã Çáãáİ ááÊÍãíá ÇáİÑÏí ÇáÓÑíÚ .<br />ÍÏÏ ãáİÇÊ ãÊÚÏÏÉ Ëã ÇÖÛØ Úáì ÊÍãíá , íÊã ÊÍãíá ÇáãáİÇÊ ÇáãÍÏÏÉ Öãä ãáİ ãÖÛæØ zip .";
$net2ftp_messages["Zip files"] = "ÖÛØ Zip ÇáãáİÇÊ";
$net2ftp_messages["... and save the zip archive on the FTP server, or email it to someone."] = "... æ ÍİÙ Çáãáİ zip Úáì ÓÑİÑ FTP , Ãæ ÅÑÓÇáå ÈæÇÓØÉ ÇáÈÑíÏ ÇáÇáßÊÑæäí .";
$net2ftp_messages["Unzip files"] = "Unzip files";
$net2ftp_messages["Different formats are supported: .zip, .tar, .tgz and .gz."] = "Different formats are supported: .zip, .tar, .tgz and .gz.";
$net2ftp_messages["Install software"] = "Install software";
$net2ftp_messages["Choose from a list of popular applications (PHP required)."] = "Choose from a list of popular applications (PHP required).";
$net2ftp_messages["Copy, move and delete"] = "äÓÎ , äŞá , æ ÍĞİ";
$net2ftp_messages["Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted."] = "ÇáãÌáÏÇÊ æ ãÍÊæíÇÊåÇ (ÇáãÌáÏÇÊ ÇáİÑÚíÉ æ ÇáãáİÇÊ) .";
$net2ftp_messages["Copy or move to a 2nd FTP server"] = "äÓÎ Ãæ äŞá ãä æ Åáì ÓÑİÑ FTP";
$net2ftp_messages["Handy to import files to your FTP server, or to export files from your FTP server to another FTP server."] = "ÇÓÊíÑÇÏ ÇáãáİÇÊ Åáì ÓÑİÑ FTP , Ãæ ÊÕÏíÑ ÇáãáİÇÊ ãä ÓÑİÑß Åáì ÓÑİÑ FTP ÂÎÑ .";
$net2ftp_messages["Rename and chmod"] = "ÅÚÇÏÉ ÇáÊÓãíÉ æ ÇáÊÕÇÑíÍ";
$net2ftp_messages["Chmod handles directories recursively."] = "ÊÛíÑ ÃÓãÇÁ ÇáãÌáÏÇÊ æ ÇáãáİÇÊ æ ÊÛííÑ ÇáÊÕÇÑíÍ .";
$net2ftp_messages["View code with syntax highlighting"] = "ÚÑÖ ÇáßæÏ ãÚ ÊãííÒ ÇáãÕÏÑ";
$net2ftp_messages["PHP functions are linked to the documentation on php.net."] = "ÇÑÊÈÇØÇÊ áæËÇÆŞ æÙÇÆİ PHP Úáì php.net.";
$net2ftp_messages["Plain text editor"] = "ãÍÑÑ äÕæÕ ÚÇÏíÉ";
$net2ftp_messages["Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server."] = "ÊÍÑíÑ ÇáäÕ ÈæÇÓØÉ ÇáãÓÊÚÑÖ .";
$net2ftp_messages["HTML editors"] = "ãÍÑÑ HTML";
$net2ftp_messages["Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from."] = "Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from.";
$net2ftp_messages["Code editor"] = "ãÍÑÑ ÇáßæÏ";
$net2ftp_messages["Edit HTML and PHP in an editor with syntax highlighting."] = "ÊÍÑíÑ ßæÏ HTML æ PHP ãÚ ÇáÊãííÒ .";
$net2ftp_messages["Search for words or phrases"] = "ÈÍË Úä ßáãÇÊ Ãæ ÊÚÈíÑ ÈÑãÌí";
$net2ftp_messages["Filter out files based on the filename, last modification time and filesize."] = "İáÊÑÉ Úáì ÃÓÇÓ ÇÓã Çáãáİ , æŞÊ ÂÎÑ ÊÍÑíÑ æ ÍÌã Çáãáİ .";
$net2ftp_messages["Calculate size"] = "ÍÓÇÈ ÇáÍÌã";
$net2ftp_messages["Calculate the size of directories and files."] = "ÍÓÇÈ ÍÌã ÇáãÌáÏÇÊ æ ÇáãáİÇÊ .";

$net2ftp_messages["FTP server"] = "ÓÑİÑ FTP";
$net2ftp_messages["Example"] = "ãËÇá";
$net2ftp_messages["Port"] = "ÇáãäİĞ";
$net2ftp_messages["Username"] = "ÇÓã ÇáãÓÊÎÏã";
$net2ftp_messages["Password"] = "ßáãÉ ÇáãÑæÑ";
$net2ftp_messages["Anonymous"] = "Anonymous";
$net2ftp_messages["Passive mode"] = "äãØ Passive ÇáÎãæá";
$net2ftp_messages["Initial directory"] = "ÇáÏáíá ÇáÃæáí";
$net2ftp_messages["Language"] = "ÇááÛÉ";
$net2ftp_messages["Skin"] = "ÇáÔßá";
$net2ftp_messages["FTP mode"] = "äãØ FTP";
$net2ftp_messages["Automatic"] = "ÊáŞÇÆí";
$net2ftp_messages["Login"] = "ÊÓÌíá ÇáÏÎæá";
$net2ftp_messages["Clear cookies"] = "ãÓÍ ÇáßæßíÒ";
$net2ftp_messages["Admin"] = "ÇáÅÏÇÑÉ";
$net2ftp_messages["Please enter an FTP server."] = "íÑÌì ÅÏÎÇá ÓÑİÑ FTP.";
$net2ftp_messages["Please enter a username."] = "íÑÌì ÅÏÎÇá ÇÓã ÇáãÓÊÎÏã .";
$net2ftp_messages["Please enter a password."] = "íÑÌì ÅÏÎÇá ßáãÉ ÇáãÑæÑ .";

} // end login


// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login_small") {
// -------------------------------------------------------------------------

$net2ftp_messages["Please enter your Administrator username and password."] = "íÑÌì ÅÏÎÇá ÇÓã ÇáãÓÊÎÏã æ ßáãÉ ÇáãÑæÑ ÇáÎÇÕÉ ÈÇáÅÏÇÑÉ .";
$net2ftp_messages["Please enter your username and password for FTP server <b>%1\$s</b>."] = "Please enter your username and password for FTP server <b>%1\$s</b>.";
$net2ftp_messages["Username"] = "ÇÓã ÇáãÓÊÎÏã";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Password"] = "ßáãÉ ÇáãÑæÑ";
$net2ftp_messages["Login"] = "ÊÓÌíá ÇáÏÎæá";
$net2ftp_messages["Continue"] = "ÇáãÊÇÈÚÉ";

} // end login_small


// -------------------------------------------------------------------------
// Logout module
if ($net2ftp_globals["state"] == "logout") {
// -------------------------------------------------------------------------

// logout.inc.php
$net2ftp_messages["Login page"] = "ÕİÍÉ ÇáÏÎæá";

// logout.template.php
$net2ftp_messages["You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>."] = "You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>.";
$net2ftp_messages["Note: other users of this computer could click on the browser's Back button and access the FTP server."] = "ãáÇÍÙÉ » íãßä áÃí ãÓÊÎÏã ÂÎÑ áåĞÇ ÇáÌåÇÒ Ãä íÖÛØ ÒÑ ááÎáİ İí ÇáãÓÊÚÑÖ æ ÇáæÕæá Åáì ÓÑİÑ FTP .";
$net2ftp_messages["To prevent this, you must close all browser windows."] = "áãäÚ ÍÕæá Ğáß , íÊæÌÈ Úáíß ÅÛáÇŞ ÌãíÚ ÕİÍÇÊ ÇáãÓÊÚÑÖ ÇáÂä .";
$net2ftp_messages["Close"] = "ÅÛáÇŞ";
$net2ftp_messages["Click here to close this window"] = "ÇÖÛØ åäÇ áÅÛáÇŞ åĞå ÇáäÇİĞÉ";

} // end logout


// -------------------------------------------------------------------------
// New directory module
if ($net2ftp_globals["state"] == "newdir") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create new directories"] = "ÅäÔÇÁ ãÌáÏÇÊ ÌÏíÏÉ";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "ÇáãÌáÏÇÊ ÇáÌÏíÏÉ ÓíÊã ÅäÔÇÆåÇ İí <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "New directory name:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "Êã ÅäÔÇÁ ÇáãÌáÏ <b>%1\$s</b> ÈäÌÇÍ !";
$net2ftp_messages["Directory <b>%1\$s</b> could not be created."] = "ÊÚĞÑ ÅäÔÇÁ ÇáãÌáÏ <b>%1\$s</b> !";

} // end newdir


// -------------------------------------------------------------------------
// Raw module
if ($net2ftp_globals["state"] == "raw") {
// -------------------------------------------------------------------------

// /modules/raw/raw.inc.php
$net2ftp_messages["Send arbitrary FTP commands"] = "ÅÑÓÇá ÃãÑ FTP ÊÍßãí";


// /skins/[skin]/raw1.template.php
$net2ftp_messages["List of commands:"] = "ŞÇÆãÉ ÇáÃæÇãÑ »";
$net2ftp_messages["FTP server response:"] = "ÅÌÇÈÉ ÓÑİÑ FTP »";

} // end raw


// -------------------------------------------------------------------------
// Rename module
if ($net2ftp_globals["state"] == "rename") {
// -------------------------------------------------------------------------
$net2ftp_messages["Rename directories and files"] = "ÅÚÇÏÉ ÊÓãíÉ ÇáãÌáÏÇÊ æ ÇáãáİÇÊ";
$net2ftp_messages["Old name: "] = "ÇáÇÓã ÇáŞÏíã » ";
$net2ftp_messages["New name: "] = "ÇáÇÓã ÇáÌÏíÏ » ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "ÇáÇÓã ÇáÌÏíÏ íÌÈ Ãä áÇ íÊÖãä äŞÇØ . áã ÊÊã ÅÚÇÏÉ ÊÓãíÉ åĞÇ ÇáÚäÕÑ Åáì <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "Êã ÅÚÇÏÉ ÊÓãíÉ <b>%1\$s</b> Åáì <b>%2\$s</b> ÈäÌÇÍ !";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "ÊÚĞÑ ÅÚÇÏÉ ÊÓãíÉ <b>%1\$s</b> Åáì <b>%2\$s</b> !";

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
$net2ftp_messages["Set all targetdirectories"] = "ÊÚííä ÌãíÚ ÇáÃÏáÉ ÇáåÏİ";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "áÊÚííä Ïáíá åÏİ ãÔÊÑß , ÃÏÎá ÇáÏáíá ÇáåÏİ İí ÇáÍŞá ÇáäÕí ÇáÓÇÈŞ Ëã ÇÖÛØ ÒÑ \"ÊÚííä ÌãíÚ ÇáÃÏáÉ ÇáåÏİ\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "ãáÇÍÙÉ » ÇáÏáíá ÇáåÏİ íÌÈ Ãä íßæä ãæÌæÏ ÃæáÇğ .";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Unzip archive <b>%1\$s</b> to:";
$net2ftp_messages["Target directory:"] = "ÇáãÌáÏ ÇáåÏİ »";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "ÇÓÊÎÏÇã äİÓ ÃÓãÇÁ ÇáãÌáÏÇÊ (ÅäÔÇÁ ÇáãÌáÏÇÊ ÇáİÑÚíÉ ÊáŞÇÆíÇğ)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "ÊÍÏíË Çáãáİ";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>ÊÍĞíÑ » åĞå ÇáæÙíİÉ ááãØæÑíä . ÇÓÊÎÏãåÇ İŞØ áÇÎÊÈÇÑ ÇáãáİÇÊ ! áŞÏ Êã ÊÍĞíÑß !";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "ËÛÑÇÊ ãÚÑæİÉ » - ãÓÍ ÌÏæá ÇáÈíÇäÇÊ - áÇ íÚãá ÈÔßá ÌíÏ ãÚ ÇáãáİÇÊ ÇáßÈíÑÉ (> 50 ß È) - áã ÊÎÊÈÑ ÈÚÏ Úáì ãÍÊæíÇÊ ãÍÇÑİ ÇáãáİÇÊ ÇáÛíÑ ŞíÇÓíÉ</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "åĞå ÇáæÙíİÉ Êãßäß ãä ÊÍãíá äÓÎÉ ÌÏíÏÉ ãä Çáãáİ ÇáãÍÏÏ , áãÔÇåÏÉ ãÇáĞí Êã ÊÚÏíáå æ ŞÈæá Ãæ ÑİÖ ßá ÊÚÏíá . ŞÈá ÍİÙ Ãí ÔÆ íãßäß ÊÍÑíÑ ÇáãáİÇÊ ÇáãÏãÌÉ .";
$net2ftp_messages["Old file:"] = "Çáãáİ ÇáŞÏíã »";
$net2ftp_messages["New file:"] = "Çáãáİ ÇáÌÏíÏ »";
$net2ftp_messages["Restrictions:"] = "ÇáÊÍÏíÏ »";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "ÇáÍÌã ÇáÃŞÕì ááãáİ ÇáæÇÍÏ ãÍÏÏ ÈæÇÓØÉ ÇáÈÑäÇãÌ Åáì <b>%1\$s ß È</b> æ ÈæÇÓØÉ PHP Åáì <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "ãÏÉ ÇáÊäİíĞ ÇáŞÕæì åí <b>%1\$s ËÇäíÉ</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "äãØ ÊÑÍíá FTP Åä ßÇä (ASCII Ãæ BINARY) íÊã ÊÍÏíÏå ÊáŞÇÆíÇğ , ÈÇáÃÚÊãÇÏ Úáì áÇÍŞÉ ÇÓã Çáãáİ";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "ÅĞÇ ßÇä Çáãáİ ÇáæÌåÉ ãæÌæÏ , ÓíÊã ÇÓÊÈÏÇáå";
$net2ftp_messages["You did not provide any files or archives to upload."] = "áã ÊŞã ÈÊÍÏíÏ Ãí ãáİ Ãæ ÃÑÔíİ áÑİÚå !";
$net2ftp_messages["Unable to delete the new file"] = "ÊÚĞÑ ÍĞİ Çáãáİ ÇáÌÏíÏ";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "íÑÌì ÇáÇäÊÙÇÑ ...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "ÍÏÏ ÇáÃÓØÑ ÇáÊÇáí , ŞÈæá Ãæ ÑİÖ ÇáÊÚÏíáÇÊ Ëã ÇÖÛØ ÒÑ ÇáÇÚÊãÇÏ .";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "ÑİÚ Åáì ÇáÏáíá »";
$net2ftp_messages["Files"] = "ÇáãáİÇÊ";
$net2ftp_messages["Archives"] = "ÇáÃÑÇÔíİ";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "ÇáãáİÇÊ ÇáÊí ÊÖÇİ åäÇ ÓÊÑÍá Åáì ÓÑİÑ FTP .";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "ÇáÃÑÇÔíİ ÇáÊí ÊÖÇİ åäÇ íÊã İß ÖÛØåÇ æ ÊÑÍíá ÇáãáİÇÊ ÇáÊí ÈÏÇÎáåÇ Åáì ÓÑİÑ FTP .";
$net2ftp_messages["Add another"] = "ÅÖÇİÉ ÂÎÑ";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "ÇÓÊÎÏÇã äİÓ ÃÓãÇÁ ÇáãÌáÏÇÊ (ÅäÔÇÁ ÇáãÌáÏÇÊ ÇáİÑÚíÉ ÊáŞÇÆíÇğ)";

$net2ftp_messages["Choose a directory"] = "ÇÎÊÑ Ïáíá";
$net2ftp_messages["Please wait..."] = "íÑÌì ÇáÇäÊÙÇÑ ...";
$net2ftp_messages["Uploading... please wait..."] = "ÌÇÑ ÇáÑİÚ ... íÑÌì ÇáÇäÊÙÇÑ ...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "ÅĞÇ ÇÓÊÛÑŞ ÇáÑİÚ æŞÊ ÃØæá ãä ÇáãÓãæÍ <b>%1\$s ËÇäíÉ<\/b> , ÓÊÍÇÊÌ Åáì ÅÚÇÏÉ ÇáãÍÇæáÉ ãÚ ÚÏÏ ãáİÇÊ ÃŞá / ÃÕÛÑ .";
$net2ftp_messages["This window will close automatically in a few seconds."] = "åĞå ÇáäÇİĞÉ ÓÊÛáŞ ÊáŞÇÆíÇğ ÎáÇá ËæÇä ŞáíáÉ .";
$net2ftp_messages["Close window now"] = "ÅÛáÇŞ ÇáäÇİĞÉ ÇáÂä";

$net2ftp_messages["Upload files and archives"] = "ÑİÚ ÇáãáİÇÊ æ ÇáÃÑÇÔíİ";
$net2ftp_messages["Upload results"] = "äÊÇÆÌ ÇáÑİÚ";
$net2ftp_messages["Checking files:"] = "ÊİÍÕ ÇáãáİÇÊ »";
$net2ftp_messages["Transferring files to the FTP server:"] = "ÊÑÍíá ÇáãáİÇÊ Åáì ÇáÓÑİÑ FTP »";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "İß ÇáÖÛØ æ ÊÑÍíá ÇáãáİÇÊ Åáì ÓÑİÑ FTP »";
$net2ftp_messages["Upload more files and archives"] = "ÑİÚ ÇáãÒíÏ ãä ÇáãáİÇÊ æ ÇáÃÑÇÔíİ";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "ÇáÊÍÏíÏ »";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "ÇáÍÌã ÇáÃŞÕì ááãáİ ÇáæÇÍÏ ãÍÏÏ ÈæÇÓØÉ ÇáÈÑäÇãÌ Åáì <b>%1\$s ß È</b> æ ÈæÇÓØÉ PHP Åáì <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "ãÏÉ ÇáÊäİíĞ ÇáŞÕæì åí <b>%1\$s ËÇäíÉ</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "äãØ ÊÑÍíá FTP Åä ßÇä (ASCII Ãæ BINARY) íÊã ÊÍÏíÏå ÊáŞÇÆíÇğ , ÈÇáÃÚÊãÇÏ Úáì áÇÍŞÉ ÇÓã Çáãáİ";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "ÅĞÇ ßÇä Çáãáİ ÇáæÌåÉ ãæÌæÏ , ÓíÊã ÇÓÊÈÏÇáå";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "ÚÑÖ Çáãáİ %1\$s";
$net2ftp_messages["View image %1\$s"] = "ÚÑÖ ÇáÕæÑÉ %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "ÚÑÖ Macromedia ShockWave İáã İáÇÔ %1\$s";
$net2ftp_messages["Image"] = "ÇáÕæÑÉ";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "áÍİÙ ÇáÕæÑÉ , ÇÖÛØ ÈÇáÒÑ ÇáÃíãä İæŞåÇ æ ÇÎÊÑ 'ÍİÙ ÇáÕæÑÉ ÈÇÓã...'";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "ÚäÇÕÑ Zip";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "ÍİÙ ãáİ zip Úáì ÓÑİÑ FTP ßÜ »";
$net2ftp_messages["Email the zip file in attachment to:"] = "ÅÑÓÇá ãáİ zip ÈÇáÈÑíÏ ßãÑİŞ Åáì »";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "áÇÍÙ Çä ÅÑÓÇá ÇáãáİÇÊ áÇ íÊÌÇåá » ÚäæÇäß IP ãËá ÅÖÇİÉ æŞÊ ÇáÅÑÓÇá Åáì ÇáÑÓÇáÉ .";
$net2ftp_messages["Some additional comments to add in the email:"] = "ÅÖÇİÉ ÈÚÖ ÇáÊÚáíŞÇÊ ÇáÅÖÇİíÉ Åáì ÇáÑÓÇáÉ »";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "áã ÊÏÎá ÇÓã Çáãáİ zip . ÇÑÌÚ ááÎáİ æ ÃÏÎá ÇáÇÓã .";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "ÚäæÇä ÇáÈÑíÏ ÇáÇáßÊÑæäí ÇáĞí ÃÏÎáÊå (%1\$s) ÛíÑ ãŞÈæá .<br />íÑÌì ÅÏÎÇá ÚäæÇä ÇáÈÑíÏ ÇáÇáßÊÑæäí ÈÇáÊäÓíŞ <b>username@domain.com</b>";

} // end zip

?>