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
$net2ftp_messages["en"] = "vi";

// HTML dir attribute: left-to-right (LTR) or right-to-left (RTL)
$net2ftp_messages["ltr"] = "ltr";

// CSS style: align left or right (use in combination with LTR or RTL)
$net2ftp_messages["left"] = "left";
$net2ftp_messages["right"] = "right";

// Encoding
$net2ftp_messages["iso-8859-1"] = "UTF-8";


// -------------------------------------------------------------------------
// Status messages
// -------------------------------------------------------------------------

// When translating these messages, keep in mind that the text should not be too long
// It should fit in the status textbox

$net2ftp_messages["Connecting to the FTP server"] = "&#272;ang k&#7871;t n&#7889;i t&#7899;i m&#225;y ch&#7911; FTP";
$net2ftp_messages["Logging into the FTP server"] = "&#272;ang &#273;&#259;ng nh&#7853;p v&#224;o v&#7899;i m&#225;y ch&#7911; FTP";
$net2ftp_messages["Setting the passive mode"] = "C&#7845;u h&#236;nh ki&#7875;u th&#7909; &#273;&#7897;ng";
$net2ftp_messages["Getting the FTP system type"] = "&#272;ang l&#7853;p ki&#7875;u h&#7879; th&#7889;ng c&#7911;a m&#225;y ch&#7911; FTP";
$net2ftp_messages["Changing the directory"] = "&#272;ang chuy&#7875;n th&#432; m&#7909;c";
$net2ftp_messages["Getting the current directory"] = "&#272;ang l&#7853;p th&#432; m&#7909;c hi&#7879;n t&#7841;i";
$net2ftp_messages["Getting the list of directories and files"] = "&#272;ang s&#7855;p x&#7871;p c&#225;c t&#7879;p v&#224; c&#225;c th&#432; m&#7909;c";
$net2ftp_messages["Parsing the list of directories and files"] = "&#272;ang ph&#226;n t&#237;ch danh s&#225;ch c&#7911;a c&#225;c th&#432; m&#7909;c v&#224; c&#225;c t&#7879;p";
$net2ftp_messages["Logging out of the FTP server"] = "&#272;ang &#273;&#259;ng xu&#7845;t kh&#7887;i m&#225;y ch&#7911; FTP";
$net2ftp_messages["Getting the list of directories and files"] = "&#272;ang s&#7855;p x&#7871;p c&#225;c t&#7879;p v&#224; c&#225;c th&#432; m&#7909;c";
$net2ftp_messages["Printing the list of directories and files"] = "&#272;ang in danh s&#225;ch c&#225;c t&#7879;p v&#224; c&#225;c th&#432; m&#7909;c";
$net2ftp_messages["Processing the entries"] = "&#272;ang trong qu&#225; tr&#236;nh";
$net2ftp_messages["Processing entry %1\$s"] = "Processing entry %1\$s";
$net2ftp_messages["Checking files"] = "&#272;ang ki&#7875;m tra c&#225;c t&#7879;p";
$net2ftp_messages["Transferring files to the FTP server"] = "&#272;ang chuy&#7875;n c&#225;c t&#7879;p t&#7899;i m&#225;y ch&#7911; FTP";
$net2ftp_messages["Decompressing archives and transferring files"] = "&#272;ang n&#233;n v&#224; chuy&#7875;n c&#225;c t&#7879;p";
$net2ftp_messages["Searching the files..."] = "&#272;ang t&#236;m ki&#7871;m c&#225;c t&#7879;p...";
$net2ftp_messages["Uploading new file"] = "&#272;ang &#273;&#432;a t&#7879;p m&#7899;i l&#234;n";
$net2ftp_messages["Reading the file"] = "&#272;ang &#273;&#7885;c t&#7879;p";
$net2ftp_messages["Parsing the file"] = "&#272;ang ph&#226;n t&#237;ch  t&#7879;p";
$net2ftp_messages["Reading the new file"] = "&#272;ang &#273;&#7885;c t&#7879;p m&#7899;i";
$net2ftp_messages["Reading the old file"] = "&#272;ang &#273;&#7885;c t&#7879;p c&#361;";
$net2ftp_messages["Comparing the 2 files"] = "&#272;ang so s&#225;nh gi&#7919;a 2 t&#7879;p";
$net2ftp_messages["Printing the comparison"] = "&#272;ang in s&#7921; so s&#225;nh";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "&#272;ang g&#7917;i c&#226;u l&#7879;nh FTP %1\$s c&#7911;a %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Getting archive %1\$s of %2\$s from the FTP server";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Creating a temporary directory on the FTP server";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Setting the permissions of the temporary directory";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Copying the net2ftp installer script to the FTP server";
$net2ftp_messages["Script finished in %1\$s seconds"] = "C&#244;ng vi&#7879;c ho&#224;n th&#224;nh trong %1\$s gi&#226;y";
$net2ftp_messages["Script halted"] = "C&#244;ng vi&#234;c &#273;&#227; t&#7841;m d&#7915;ng";

// Used on various screens
$net2ftp_messages["Please wait..."] = "Xin vui l&#242;ng ch&#7901;...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Unexpected state string: %1\$s. Exiting.";
$net2ftp_messages["This beta function is not activated on this server."] = "This beta function is not activated on this server.";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "This function has been disabled by the Administrator of this website.";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "Th&#432; m&#7909;c <b>%1\$s</b> kh&#244;ng t&#7891;n t&#7841;i ho&#7863;c kh&#244;ng &#273;&#432;&#7907;c l&#7921;a ch&#7885;n, v&#236; v&#7853;y th&#432; m&#7909;c <b>%2\$s</b> &#273;&#432;&#7907;c hi&#7875;n th&#7883; &#273;&#7875; thay v&#224;o.";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "Th&#432; m&#7909;c ch&#7911; c&#7911;a b&#7841;n <b>%1\$s</b>  kh&#244;ng t&#7891;n t&#7841;i ho&#7863;c kh&#244;ng &#273;&#432;&#7907;c l&#7921;a ch&#7885;n.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted.";
$net2ftp_messages["Execute %1\$s in a new window"] = "Th&#7921;c hi&#7879;n %1\$s trong m&#7897;t c&#7911;a s&#7893; m&#7899;i";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "Xin vui l&#242;ng l&#7921;a ch&#7885;n &#237;t nh&#7845;t m&#7897;t th&#432; m&#7909;c ho&#7863;c m&#7897;t t&#7879;p!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "M&#225;y ch&#7911; FTP <b>%1\$s</b> kh&#244;ng c&#243; &#7903; trong danh s&#225;ch &#273;&#432;&#7907;c ch&#7845;p nh&#7853;n c&#7911;a.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "M&#225;y ch&#7911; FTP <b>%1\$s</b> n&#7857;m trong danh s&#225;ch c&#225;c m&#225;y ch&#7911; b&#7883; c&#7845;m c&#7911;a.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "C&#7893;ng m&#225;y ch&#7911; FTP %1\$s c&#243; th&#7875; kh&#244;ng s&#7917; d&#7909;ng &#273;&#432;&#7907;c.";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "Your IP address (%1\$s) is not in the list of allowed IP addresses.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "&#272;&#7883;a ch&#7881; IP c&#7911;a b&#7841;n (%1\$s) &#273;&#227; n&#7857;m trong danh s&#225;ch b&#7883; c&#7845;m c&#7911;a.";

// isAuthorizedDirectory()
$net2ftp_messages["Table net2ftp_users contains duplicate rows."] = "B&#7843;ng net2ftp_users ch&#7913;a &#273;&#7909;ng c&#225;c h&#224;ng gi&#7889;ng nhau.";

// logAccess(), logLogin(), logError()
$net2ftp_messages["Unable to execute the SQL query."] = "Kh&#244;ng th&#7875; th&#7921;c hi&#7879;n SQL query.";

// checkAdminUsernamePassword()
$net2ftp_messages["You did not enter your Administrator username or password."] = "Ban chua nhap ten dung hoac mat khau.";
$net2ftp_messages["Wrong username or password. Please try again."] = "Sai ten dung hoac mat khau. Xin vui long kiem tra va thuc hien lai.";


// -------------------------------------------------------------------------
// /includes/consumption.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to determine your IP address."] = "Kh&#244;ng th&#7875; x&#225;c &#273;&#7883;nh r&#245; &#273;&#7883;a ch&#7881; IP c&#7911;a b&#7841;n.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "B&#7843;ng net2ftp_log_consumption_ipaddress ch&#7913;a &#273;&#7909;ng c&#225;c h&#224;ng gi&#7889;ng nhau.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "B&#7843;ng net2ftp_log_consumption_ftpserver ch&#7913;a &#273;&#7909;ng c&#225;c h&#224;ng gi&#7889;ng nhau.";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "V&#7853;t hay thay &#273;&#7893;i <b>consumption_ipaddress_datatransfer</b> is not numeric.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "B&#7843;ng net2ftp_log_consumption_ipaddress c&#243; th&#7875; kh&#244;ng &#273;&#432;&#7907;c c&#7853;p nh&#7853;t.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "B&#7843;ng net2ftp_log_consumption_ipaddress ch&#7913;a &#273;&#7909;ng c&#225;c m&#7909;c gi&#7889;ng nhau.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "B&#7843;ng net2ftp_log_consumption_ftpserver c&#243; th&#7875; kh&#244;ng &#273;&#432;&#7907;c c&#7853;p nh&#7853;t.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "B&#7843;ng net2ftp_log_consumption_ftpserver ch&#7913;a &#273;&#7909;ng c&#225;c m&#7909;c gi&#7889;ng nhau.";
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
$net2ftp_messages["An error has occured"] = "C&#243; l&#7895;i x&#7843;y ra";
$net2ftp_messages["Go back"] = "Quay l&#7841;i";
$net2ftp_messages["Go to the login page"] = "T&#7899;i trang &#273;&#259;ng nh&#7853;p";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "<a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP m&#244; h&#236;nh ri&#234;ng c&#7911;a PHP</a> &#273;&#227; kh&#244;ng &#273;&#432;&#7907;c c&#224;i &#273;&#7863;t.<br /><br /> Qu&#7843;n tr&#7883; c&#7911;a website c&#243; th&#7867; c&#224;i &#273;&#7863;t ch&#7913;c n&#259;ng n&#224;y. Ch&#7881; d&#7851;n t&#7841;i <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Kh&#244;ng th&#7875; k&#7871;t n&#7889;i t&#7899;i m&#225;y ch&#7911; FTP <b>%1\$s</b> v&#7899;i c&#7893;ng <b>%2\$s</b>.<br /><br />B&#7841;n c&#243; ch&#7855;c ch&#7855;n &#273;&#226;y l&#224; &#273;&#7883;a ch&#7881; c&#7911;a m&#225;y ch&#7911; FTP ?. H&#227;y li&#234;n h&#7879; v&#7899;i nh&#224; cung c&#7845;p d&#7883;ch v&#7909; c&#7911;a b&#7841;n ho&#7863;c qu&#7843;n tr&#7883; c&#7911;a h&#7879; th&#7889;ng &#273;&#7875; &#273;&#432;&#7907;c gi&#250;p &#273;&#7905;.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Kh&#244;ng th&#7875; &#273;&#259;ng nh&#7853;p v&#224;o m&#225;y ch&#7911; FTP <b>%1\$s</b> v&#7899;i t&#234;n d&#249;ng <b>%2\$s</b>.<br /><br />B&#7841;n c&#243; ch&#7855;c ch&#7855;n l&#224; m&#236;nh nh&#7853;p &#273;&#250;ng th&#244;ng tin. H&#227;y li&#234;n h&#7879; v&#7899;i nh&#224; cung c&#7845;p d&#7883;ch v&#7909; c&#7911;a b&#7841;n ho&#7863;c qu&#7843;n tr&#7883; c&#7911;a h&#7879; th&#7889;ng &#273;&#7875; &#273;&#432;&#7907;c gi&#250;p &#273;&#7905;.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "Kh&#244;ng th&#7875; chuy&#7875;n &#273;&#7893;i ki&#7875;u th&#7909; &#273;&#7897;ng tr&#234;n m&#225;y ch&#7911; FTP <b>%1\$s</b>.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Kh&#244;ng th&#7875; k&#7871;t n&#7889;i v&#7899;i m&#225;y ch&#7911; FTP th&#7913; hai (m&#225;y ch&#7911; &#273;&#7871;n) <b>%1\$s</b> v&#7899;i c&#7893;ng <b>%2\$s</b>.<br /><br />B&#7841;n c&#243; ch&#7855;c ch&#7855;n &#273;&#226;y l&#224; &#273;&#7883;a ch&#7881; c&#7911;a m&#225;y ch&#7911; FTP th&#7913; hai (m&#225;y ch&#7911; &#273;&#7871;n)? . H&#227;y li&#234;n h&#7879; v&#7899;i nh&#224; cung c&#7845;p d&#7883;ch v&#7909; c&#7911;a b&#7841;n ho&#7863;c qu&#7843;n tr&#7883; c&#7911;a h&#7879; th&#7889;ng &#273;&#7875; &#273;&#432;&#7907;c gi&#250;p &#273;&#7905;.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Kh&#244;ng th&#7875; &#273;&#259;ng nh&#7853;p v&#224;o m&#225;y ch&#7911; FTP th&#7913; hai (m&#225;y ch&#7911; &#273;&#7871;n) <b>%1\$s</b> v&#7899;i t&#234;n d&#249;ng <b>%2\$s</b>.<br /><br />B&#7841;n c&#243; ch&#7855;c ch&#7855;n l&#224;  t&#234;n d&#249;ng l&#224; m&#7853;t kh&#7849;u &#273;&#7873;u &#273;&#250;ng? H&#227;y li&#234;n h&#7879; v&#7899;i nh&#224; cung c&#7845;p d&#7883;ch v&#7909; c&#7911;a b&#7841;n ho&#7863;c qu&#7843;n tr&#7883; c&#7911;a h&#7879; th&#7889;ng &#273;&#7875; &#273;&#432;&#7907;c gi&#250;p &#273;&#7905;.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "Kh&#244;ng th&#7875; chuy&#7875;n &#273;&#7893;i ki&#7875;u th&#7909; &#273;&#7897;ng tr&#234;n m&#225;y ch&#7911; FTP th&#7913; hai (m&#225;y ch&#7911; &#273;&#7871;n) <b>%1\$s</b>.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "Kh&#244;ng th&#7875; &#273;&#7893;i th&#432; m&#7909;c hay t&#234;n t&#7879;p <b>%1\$s</b> th&#224;nh <b>%2\$s</b>";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "Kh&#244;ng th&#7875; th&#7921;c hi&#7879;n c&#226;u l&#7879;nh <b>%1\$s</b>. H&#227;y ch&#250; &#253; l&#224; l&#7879;nh CHMOD ch&#7881; s&#7917; d&#7909;ng &#273;&#432;&#7907;c tr&#234;n m&#225;y ch&#7911; FTP Unix, kh&#244;ng s&#7917; d&#7909;ng &#273;&#432;&#7907;c tr&#234;n m&#225;y ch&#7911; FTP Windows.";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "Th&#432; m&#7909;c <b>%1\$s</b> &#273;&#227; thay &#273;&#7893;i ki&#7875;u th&#224;nh c&#244;ng th&#224;nh <b>%2\$s</b>";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "&#272;ang trong qu&#225; tr&#236;nh v&#7899;i th&#432; m&#7909;c <b>%1\$s</b>:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "T&#7879;p <b>%1\$s</b> &#273;&#227; thay &#273;&#7893;i ki&#7875;u th&#224;nh c&#244;ng th&#224;nh <b>%2\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "T&#7845;t c&#7843; c&#225;c th&#432; m&#7909;c v&#224; t&#7879;p &#273;&#227; l&#7921;a ch&#7885;n dang trong qu&#225; tr&#236;nh th&#7921;c hi&#7879;n.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "Kh&#244;ng th&#7875; xo&#225; th&#432; m&#7909;c <b>%1\$s</b>";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Kh&#244;ng th&#7875; xo&#225; t&#7879;p <b>%1\$s</b>";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "Kh&#244;ng th&#7875; t&#7841;o th&#432; m&#7909;c <b>%1\$s</b>";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "Kh&#244;ng th&#7875; t&#7841;o t&#7879;p t&#7841;m th&#7901;i";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "Kh&#244;ng th&#7875; t&#7843;i t&#7879;p  <b>%1\$s</b> t&#7915; m&#225;y ch&#7911; FTP v&#224; l&#432;u n&#243; l&#7841;i nh&#432; t&#7879;p t&#7841;m th&#7901;i <b>%2\$s</b>.<br />H&#227;y ki&#7875;m tra s&#7921; cho ph&#233;p c&#7911;a th&#432; m&#7909;c %3\$s.<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Kh&#244;ng th&#7875; m&#7903; t&#7879;p t&#7841;m th&#7901;i. H&#227;y ki&#7875;m tra s&#7921; cho ph&#233;p c&#7911;a th&#432; m&#7909;c %1\$s.";
$net2ftp_messages["Unable to read the temporary file"] = "Kh&#244;ng th&#7875; &#273;&#7885;c t&#7879;p t&#7841;m th&#7901;i";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Kh&#244;ng th&#7875; &#273;&#243;ng &#273;i&#7873;u khi&#7875;n c&#7911;a t&#7879;p t&#7841;m th&#7901;i";
$net2ftp_messages["Unable to delete the temporary file"] = "Kh&#244;ng th&#7875; x&#243;a t&#7879;p t&#7841;m th&#7901;i";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "Kh&#244;ng th&#7875; t&#7841;o t&#7879;p t&#7841;m th&#7901;i. H&#227;y ki&#7875;m tra s&#7921; cho ph&#233;p c&#7911;a th&#432; m&#7909;c %1\$s.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Kh&#244;ng th&#7875; m&#7903; t&#7879;p t&#7841;m th&#7901;i. H&#227;y ki&#7875;m tra s&#7921; cho ph&#233;p c&#7911;a th&#432; m&#7909;c %1\$s.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "Kh&#244;ng th&#7875; ghi l&#234;n t&#7879;p t&#7841;m th&#7901;i <b>%1\$s</b>.<br />H&#227;y ki&#7875;m tra s&#7921; cho ph&#233;p c&#7911;a th&#432; m&#7909;c %2\$s.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Kh&#244;ng th&#7875; &#273;&#243;ng &#273;i&#7873;u khi&#7875;n c&#7911;a t&#7879;p t&#7841;m th&#7901;i";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "Kh&#244;ng th&#7875; &#273;&#7863;t t&#7879;p <b>%1\$s</b> l&#234;n m&#225;y ch&#7911; FTP.<br />B&#7841;n c&#243; th&#7875; kh&#244;ng &#273;&#432;&#7907;c ph&#233;p ghi l&#234;n th&#432; m&#7909;c n&#224;y.";
$net2ftp_messages["Unable to delete the temporary file"] = "Kh&#244;ng th&#7875; x&#243;a t&#7879;p t&#7841;m th&#7901;i";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "&#272;ang th&#7921;c hi&#7879;n qu&#225; tr&#236;nh v&#7899;i th&#432; m&#7909;c <b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "Th&#432; m&#7909;c &#273;&#7871;n <b>%1\$s</b> gi&#7889;ng nh&#432; ho&#7863;c l&#224; m&#7897;t th&#432; m&#7909;c con c&#7911;a th&#432; m&#7909;c ngu&#7891;n <b>%2\$s</b>. V&#236; v&#7853;y, th&#432; m&#7909;c n&#224;y s&#7869; b&#7883; b&#7887; qua";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The directory <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "Kh&#244;ng th&#7875; t&#7841;o th&#432; m&#7909;c con <b>%1\$s</b>. N&#243; c&#243; th&#7875; &#273;&#227; t&#7891;n t&#7841;i. Ti&#7871;p t&#7909;c v&#7899;i qu&#225; tr&#236;nh sao ch&#233;p ho&#7863;c di chuy&#7875;n...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "&#272;&#227; t&#7841;o th&#432; m&#7909;c con &#273;&#7871;n <b>%1\$s</b>";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "The directory <b>%1\$s</b> could not be selected, so this directory will be skipped";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "Kh&#244;ng th&#7875; x&#243;a th&#432; m&#7909;c con <b>%1\$s</b> - n&#243; c&#243; th&#7875; &#273;&#227; b&#7883; l&#224;m r&#7895;ng";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "&#272;&#227; x&#243;a th&#432; m&#7909;c con <b>%1\$s</b>";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "Qu&#225; tr&#236;nh c&#7911;a th&#432; m&#7909;c <b>%1\$s</b> &#273;&#227; ho&#224;n th&#224;nh";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "&#272;&#237;ch t&#7899;i c&#7911;a t&#7879;p <b>%1\$s</b> gi&#7889;ng nh&#432; t&#7879;p ngu&#7891;n. V&#236; v&#7853;y, t&#7879;p n&#224;y s&#7869; b&#7883; b&#7887; qua";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The file <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "The file <b>%1\$s</b> is too big to be copied, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "The file <b>%1\$s</b> is too big to be moved, aborting the move";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "Kh&#244;ng th&#7875; sao ch&#233;p t&#7879;p <b>%1\$s</b>";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "&#272;&#227; sao ch&#233;p t&#7879;p <b>%1\$s</b>";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Unable to move the file <b>%1\$s</b>, aborting the move";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "&#272;&#227; di chuy&#7875;n t&#7879;p <b>%1\$s</b>";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Kh&#244;ng th&#7875; xo&#225; t&#7879;p <b>%1\$s</b>";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "&#272;&#227; x&#243;a t&#7879;p <b>%1\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "T&#7845;t c&#7843; c&#225;c th&#432; m&#7909;c v&#224; t&#7879;p &#273;&#227; l&#7921;a ch&#7885;n dang trong qu&#225; tr&#236;nh th&#7921;c hi&#7879;n.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "Kh&#244;ng th&#7875; sao ch&#233;p t&#7879;p xa <b>%1\$s</b> t&#7899;i t&#7879;p n&#7897;i v&#249;ng, s&#7917; d&#7909;ng ki&#7875;u FTP <b>%2\$s</b>";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "Kh&#244;ng th&#7875; x&#243;a t&#7879;p <b>%1\$s</b>";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "The file is too big to be transferred";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "T&#7899;i h&#7841;n h&#224;ng ng&#224;y: t&#7879;p <b>%1\$s</b> s&#7869; kh&#244;ng &#273;&#432;&#7907;c chuy&#7875;n";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "Kh&#244;ng th&#7875; sao ch&#233;p t&#7879;p n&#7897;i v&#249;ng t&#7899;i t&#7879;p xa <b>%1\$s</b>, s&#7917; d&#7909;ng ki&#7875;u FTP <b>%2\$s</b>";
$net2ftp_messages["Unable to delete the local file"] = "Kh&#244;ng th&#7875; x&#243;a t&#7879;p n&#7897;i v&#249;ng";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "Kh&#244;ng th&#7875; x&#243;a t&#7879;p t&#7841;m th&#7901;i";
$net2ftp_messages["Unable to send the file to the browser"] = "Kh&#244;ng th&#7875; g&#7917;i t&#7879;p t&#7899;i tr&#236;nh duy&#7879;t";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "Kh&#244;ng th&#7875; t&#7841;o t&#7879;p t&#7841;m th&#7901;i";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "T&#7879;p zip &#273;&#227; &#273;&#432;&#7907;c l&#432;u tr&#234;n m&#225;y ch&#7911; FTP t&#7841;i <b>%1\$s</b>";
$net2ftp_messages["Requested files"] = "Nh&#7919;ng t&#7879;p &#273;&#227; y&#234;u c&#7847;u";

$net2ftp_messages["Dear,"] = "Xin ch&#224;o,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "M&#7897;t ai &#273;&#243; &#273;&#227; g&#7917;i cho b&#7841;n nh&#7919;ng t&#7879;p d&#7841;ng n&#233;n (%1\$s).";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "N&#7871;u b&#7841;n kh&#244;ng bi&#7871;t c&#225;i n&#224;y ho&#7863;c b&#7841;n kh&#244;ng tin c&#7853;y v&#224;o nh&#226;n v&#7853;t &#273;&#227; g&#7917;i n&#243; vui l&#242;ng x&#243;a b&#7913;c th&#432; n&#224;y.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "H&#227;y ch&#250; &#253; l&#224; n&#7871;u b&#7841;n kh&#244;ng gi&#7843;i n&#233;n t&#7879;p l&#432;u tr&#7919; th&#236; nh&#7919;ng t&#7879;p trong &#273;&#243; s&#7869; kh&#244;ng l&#224;m h&#7841;i m&#225;y t&#237;nh c&#7911;a b&#7841;n.";
$net2ftp_messages["Information about the sender: "] = "Th&#244;ng tin v&#7873; ng&#432;&#7901;i g&#7917;i: ";
$net2ftp_messages["IP address: "] = "&#272;&#7883;a ch&#7881; IP: ";
$net2ftp_messages["Time of sending: "] = "Th&#7901;i gian g&#7917;i: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "&#272;&#432;&#7907;c g&#7917;i b&#7857;ng &#273;&#432;&#7901;ng &#273;&#432;&#7907;c c&#224;i &#273;&#7863;t tr&#234;n m&#225;y ch&#7911;: ";
$net2ftp_messages["Webmaster's email: "] = "&#272;&#7883;a ch&#7881; email c&#7911;a tr&#432;&#7903;ng web: ";
$net2ftp_messages["Message of the sender: "] = "Tin nh&#7855;n c&#7911;a ng&#432;&#7901;i g&#7917;i: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "B&#7913;c th&#432; n&#224;y &#273;&#432;&#7907;c g&#7917;i t&#7921; &#273;&#7897;ng t&#7915; http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "T&#7879;p n&#233;n &#273;&#227; &#273;&#432;&#7907;c g&#7917;i t&#7899;i <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "T&#7879;p <b>%1\$s</b> qu&#225; l&#7899;n, n&#243; s&#7869; kh&#244;ng th&#7875; &#273;&#432;a l&#234;n &#273;&#432;&#7907;c.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded.";
$net2ftp_messages["Could not generate a temporary file."] = "Kh&#244;ng th&#7875; t&#7841;o ra m&#7897;t t&#7879;p t&#7841;m th&#7901;i.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "T&#7879;p <b>%1\$s</b> c&#243; th&#7875; kh&#244;ng &#273;&#432;&#7907;c di chuy&#7875;n";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "&#272;&#227; <b>%1\$s</b> &#273;&#227; xong";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "Kh&#244;ng th&#7875; di chuy&#7875;n t&#7879;p &#273;&#227; &#273;&#432;a l&#234;n t&#7899;i th&#432; m&#7909;c t&#7841;m th&#7901;i.<br /><br />Qu&#7843;n tr&#7883; c&#7911;a trang web n&#224;y &#273;&#227; <b>chmod 777</b> th&#432; m&#7909;c <b>/temp</b> tr&#234;n.";
$net2ftp_messages["You did not provide any file to upload."] = "B&#7841;n kh&#244;ng cung c&#7845;p b&#7845;t k&#7923; t&#7879;p g&#236; &#273;&#7875; &#273;&#432;a l&#234;n.";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "T&#7879;p <b>%1\$s</b> c&#243; th&#7875; kh&#244;ng &#273;&#432;&#7907;c chuy&#7875;n  t&#7899;i m&#225;y ch&#7911; FTP";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "T&#7879;p <b>%1\$s</b> &#273;&#227; &#273;&#432;&#7907;c chuy&#7875;n  t&#7899;i m&#225;y ch&#7911; FTP, s&#7917; d&#7909;ng ki&#7875;u FTP <b>%2\$s</b>";
$net2ftp_messages["Transferring files to the FTP server"] = "&#272;ang chuy&#7875;n c&#225;c t&#7879;p t&#7899;i m&#225;y ch&#7911; FTP";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "&#272;ang m&#7903; l&#432;u tr&#7919; nr %1\$s: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "L&#432;u tr&#7919; <b>%1\$s</b> kh&#244;ng th&#7875; gi&#7843;i n&#233;n b&#7903;i v&#236; lo&#7841;i t&#7879;p n&#224;y kh&#244;ng &#273;&#432;&#7907;c ch&#7845;p nh&#7853;n. Ch&#7881; c&#243; nh&#7919;ng l&#432;u tr&#7919; d&#7841;ng zip, tar, tgz v&#224; gz &#273;&#432;&#7907;c h&#7895; tr&#7907; t&#7841;i th&#7901;i &#273;i&#7875;m n&#224;y.";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Unable to extract the files and directories from the archive";
$net2ftp_messages["Created directory %1\$s"] = "Created directory %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "Could not create directory %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "Copied file %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "Could not copy file %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "Unable to delete the temporary directory";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Unable to delete the temporary file %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "Kh&#244;ng th&#7875; th&#7921;c hi&#7879;n c&#226;u l&#7879;nh c&#7911;a site <b>%1\$s</b>";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "C&#244;ng vi&#7879;c c&#7911;a b&#7841;n &#273;&#227; d&#7915;ng l&#7841;i";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "C&#244;ng vi&#7879;c m&#224; b&#7841;n mu&#7889;n th&#7921;c hi&#7879;n v&#7899;i net2ftp ti&#234;u t&#7889;n nhi&#7873;u th&#7901;i gian h&#417;n th&#7901;i gian cho ph&#233;p l&#224; %1\$s gi&#226;y, b&#7903;i v&#7853;y c&#244;ng vi&#7879;c b&#7883; d&#7915;ng l&#7841;i.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "Th&#7901;i gian t&#7889;i &#273;a b&#7843;o &#273;&#7843;m tuy&#7879;t &#273;&#7889;i cho vi&#7879;c s&#7917; d&#7909;ng m&#225;y ch&#7911; web t&#7899;i t&#7845;t c&#7843; m&#7885;i ng&#432;&#7899;i.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "H&#227;y th&#7917; chia c&#244;ng vi&#7879;c c&#7911;a b&#7841;n th&#224;nh nhi&#7873;u c&#244;ng vi&#234;c nh&#7887; h&#417;n: gi&#7899;i h&#7841;n nh&#7919;ng l&#7921;a ch&#7885;n c&#7911;a c&#225;c t&#7879;p, v&#224; b&#7887; &#273;i nh&#7919;ng t&#7879;p l&#7899;n.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "N&#7871;u b&#7841;n c&#7847;n s&#7917; d&#7909;ng trong m&#7897;t th&#7901;i gian d&#224;i, h&#227;y c&#224;i &#273;&#7863;t n&#243; tr&#234;n m&#225;y ch&#7911; c&#7911;a b&#7841;n.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "B&#7841;n &#273;&#227; kh&#244;ng cung c&#7845;p b&#7845;t k&#7923; v&#259;n b&#7843;n n&#224;o &#273;&#7875; g&#7917;i b&#7857;ng email!";
$net2ftp_messages["You did not supply a From address."] = "B&#7841;n &#273;&#227; kh&#244;ng cung c&#7845;p &#273;&#7883;a ch&#7881; &#273;i.";
$net2ftp_messages["You did not supply a To address."] = "B&#7841;n &#273;&#227; kh&#244;ng cung c&#7845;p &#273;&#7883;a ch&#7881; t&#7899;i.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "C&#243; l&#7895;i x&#7843;y ra khi g&#7917;i email t&#7899;i <b>%1\$s</b> &#273;&#227; kh&#244;ng th&#7875; g&#7917;i.";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "Xin vui l&#242;ng &#273;i&#7873;n t&#234;n s&#7917; d&#7909;ng v&#224; m&#7853;t kh&#7849;u cho m&#225;y ch&#7911; FTP ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "B&#7841;n &#273;&#227; kh&#244;ng nh&#7853;p &#273;&#7847;y &#273;&#7911; th&#244;ng tin &#273;&#259;ng nh&#7853;p c&#7911;a b&#7841;n v&#224;o c&#7917;a s&#7893; m&#7899;i.<br />H&#227;y h&#7845;p chu&#7897;t v&#224;o &#273;&#432;&#7901;ng d&#7851;n  \"&#272;i t&#7899;i trang &#273;&#259;ng nh&#7853;p\".";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "&#272;&#432;&#7901;ng d&#7851;n t&#7899;i trang qu&#7843;n tr&#7883; &#273;&#227; b&#7883; kh&#243;a, b&#7903;i v&#236; kh&#244;ng c&#243; m&#7853;t kh&#7849;u n&#224;o &#273;&#432;&#7907;c l&#7853;p trong t&#7879;p <b>settings.inc.php</b>. Xin vui l&#242;ng nh&#7853;p m&#7853;t kh&#7849;u v&#224;o t&#7879;p n&#224;y v&#224; t&#7843;i l&#7841;i trang n&#224;y.";
$net2ftp_messages["Please enter your Admin username and password"] = "Xin vui l&#242;ng nh&#7853;p v&#224;o t&#234;n s&#7917; d&#7909;ng v&#224; m&#7853;t kh&#7849;u c&#7911;a qu&#7843;n tr&#7883;"; 
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "B&#7841;n &#273;&#227; kh&#244;ng nh&#7853;p &#273;&#7847;y &#273;&#7911; th&#244;ng tin &#273;&#259;ng nh&#7853;p c&#7911;a b&#7841;n v&#224;o c&#7917;a s&#7893; m&#7899;i.<br />H&#227;y h&#7845;p chu&#7897;t v&#224;o &#273;&#432;&#7901;ng d&#7851;n  \"&#272;i t&#7899;i trang &#273;&#259;ng nh&#7853;p\".";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "Sai t&#234;n s&#7917; d&#7909;ng ho&#7863;c m&#7853;t kh&#7849;u c&#7911;a qu&#7843;n tr&#7883;. H&#227;y ki&#7875;m tra l&#7841;i n&#243; trong t&#7879;p <b>settings.inc.php</b>.";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "Xanh d&#432;&#417;ng";
$net2ftp_messages["Grey"] = "X&#225;m";
$net2ftp_messages["Black"] = "&#272;en";
$net2ftp_messages["Yellow"] = "V&#224;ng";
$net2ftp_messages["Pastel"] = "V&#224;ng nh&#7841;t";

// getMime()
$net2ftp_messages["Directory"] = "Th&#432; m&#7909;c";
$net2ftp_messages["Symlink"] = "&#272;&#432;&#7901;ng d&#7851;n t&#432;&#7907;ng tr&#432;ng";
$net2ftp_messages["ASP script"] = "Ng&#244;n ng&#7919; ASP";
$net2ftp_messages["Cascading Style Sheet"] = "Cascading Style Sheet";
$net2ftp_messages["HTML file"] = "T&#7879;p HTML";
$net2ftp_messages["Java source file"] = "T&#7879;p m&#227; ngu&#7891;n Java";
$net2ftp_messages["JavaScript file"] = "T&#7879;p m&#227; ngu&#7891;n JavaScript";
$net2ftp_messages["PHP Source"] = "M&#227; ngu&#7891;n PHP";
$net2ftp_messages["PHP script"] = "Ng&#244;n ng&#7919; PHP";
$net2ftp_messages["Text file"] = "T&#7879;p v&#259;n b&#7843;n";
$net2ftp_messages["Bitmap file"] = "T&#7879;p Bitmap";
$net2ftp_messages["GIF file"] = "T&#7879;p GIF";
$net2ftp_messages["JPEG file"] = "T&#7879;p JPEG";
$net2ftp_messages["PNG file"] = "T&#7879;p PNG";
$net2ftp_messages["TIF file"] = "T&#7879;p TIF";
$net2ftp_messages["GIMP file"] = "T&#7879;p GIMP";
$net2ftp_messages["Executable"] = "Executable";
$net2ftp_messages["Shell script"] = "Ng&#244;n ng&#7919; Shell";
$net2ftp_messages["MS Office - Word document"] = "T&#224;i li&#7879;u MS Office - Word";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - Excel spreadsheet";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - PowerPoint presentation";
$net2ftp_messages["MS Office - Access database"] = "C&#417; s&#7903; d&#7919; li&#7879;u MS Office - Access";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - Visio drawing";
$net2ftp_messages["MS Office - Project file"] = "T&#7879;p MS Office - Project";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "T&#224;i li&#7879;u OpenOffice - Writer 6.0";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = " Giao di&#7879;nOpenOffice - Writer 6.0";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - Calc 6.0 spreadsheet";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "Giao di&#7879;n OpenOffice - Calc 6.0";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "T&#224;i li&#7879;u OpenOffice - Draw 6.0";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "Giao di&#7879;n OpenOffice - Draw 6.0";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - Impress 6.0 presentation";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "Giao di&#7879;n OpenOffice - Impress 6.0";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "T&#224;i li&#7879;u OpenOffice - Writer 6.0 global";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "T&#224;i li&#7879;u OpenOffice - Math 6.0";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "T&#224;i li&#7879;u StarOffice - StarWriter 5.x";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "T&#224;i li&#7879;u StarOffice - StarWriter 5.x global";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - StarCalc 5.x spreadsheet";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "T&#224;i li&#7879;u StarOffice - StarDraw 5.x";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - StarImpress 5.x presentation";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - StarImpress Packed 5.x file";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "T&#224;i li&#7879;u StarOffice - StarMath 5.x";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "T&#224;i li&#7879;u StarOffice - StarChart 5.x";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - StarMail 5.x mail file";
$net2ftp_messages["Adobe Acrobat document"] = "T&#224;i li&#7879;u Adobe Acrobat";
$net2ftp_messages["ARC archive"] = "L&#432;u tr&#7919; d&#7841;ng ARC";
$net2ftp_messages["ARJ archive"] = "L&#432;u tr&#7919; d&#7841;ng ARJ";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "L&#432;u tr&#7919; d&#7841;ng GZ";
$net2ftp_messages["TAR archive"] = "L&#432;u tr&#7919; d&#7841;ng TAR";
$net2ftp_messages["Zip archive"] = "L&#432;u tr&#7919; d&#7841;ng Zip";
$net2ftp_messages["MOV movie file"] = "T&#7879;p MOV movie";
$net2ftp_messages["MPEG movie file"] = "T&#7879;p MPEG movie";
$net2ftp_messages["Real movie file"] = "T&#7879;p Real movie";
$net2ftp_messages["Quicktime movie file"] = "T&#7879;p Quicktime movie";
$net2ftp_messages["Shockwave flash file"] = "T&#7879;p Shockwave Flash";
$net2ftp_messages["Shockwave file"] = "T&#7879;p Shockwave";
$net2ftp_messages["WAV sound file"] = "T&#7879;p &#226;m thanh WAV";
$net2ftp_messages["Font file"] = "T&#7879;p Font";
$net2ftp_messages["%1\$s File"] = "T&#7879;p %1\$s";
$net2ftp_messages["File"] = "T&#7879;p";

// getAction()
$net2ftp_messages["Back"] = "Quay l&#7841;i";
$net2ftp_messages["Submit"] = "Th&#7921;c hi&#7879;n";
$net2ftp_messages["Refresh"] = "L&#224;m t&#432;&#417;i";
$net2ftp_messages["Details"] = "Chi ti&#7871;t";
$net2ftp_messages["Icons"] = "Bi&#7875;u t&#432;&#7907;ng";
$net2ftp_messages["List"] = "Danh s&#225;ch";
$net2ftp_messages["Logout"] = "&#272;&#259;ng xu&#7845;t";
$net2ftp_messages["Help"] = "Tr&#7907; gi&#250;p";
$net2ftp_messages["Bookmark"] = "M&#7909;c &#432;a th&#237;ch";
$net2ftp_messages["Save"] = "L&#432;u";
$net2ftp_messages["Default"] = "M&#7863;c &#273;&#7883;nh";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "H&#432;&#7899;ng d&#7851;n";
$net2ftp_messages["Forums"] = "Di&#7877;n &#273;&#224;n";
$net2ftp_messages["License"] = "&#272;&#259;ng k&#253;";
$net2ftp_messages["Powered by"] = "M&#227; ngu&#7891;n &#273;&#432;&#7907;c cung c&#7845;p b&#7903;i";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions.";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "Ch&#7913;c n&#259;ng qu&#7843;n tr&#7883;";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "Th&#244;ng tin phi&#234;n b&#7843;n";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "Phi&#234;n b&#7843;n n&#224;y c&#7911;a &#273;&#227; &#273;&#432;&#7907;c c&#7853;p nh&#7853;t.";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "Phi&#234;n b&#7843;n m&#7899;i nh&#7845;t &#273;&#227; kh&#244;ng th&#7875; &#273;&#432;&#7907;c c&#7853;p nh&#7853;t t&#7915; m&#225;y ch&#7911; net2ftp.com. H&#227;y ki&#7875;m tra c&#7845;u h&#236;nh b&#7843;o m&#7853;t c&#7911;a tr&#236;nh duy&#7879;t c&#7911;a b&#7841;n, c&#225;i m&#224; c&#243; th&#7875; c&#7843;n tr&#7903; vi&#7879;c c&#7853;p nh&#7853;t phi&#234;n b&#7843;n m&#7899;i nh&#7845;t t&#7915; m&#225;y ch&#7911; net2ftp.com.";
$net2ftp_messages["Logging"] = "&#272;ang &#273;&#259;ng nh&#7853;p";
$net2ftp_messages["Date from:"] = "T&#7915; ng&#224;y:";
$net2ftp_messages["to:"] = "t&#7899;i:";
$net2ftp_messages["Empty logs"] = "L&#224;m r&#7895;ng nh&#7853;t k&#253;";
$net2ftp_messages["View logs"] = "Xem nh&#7853;t k&#253;";
$net2ftp_messages["Go"] = "&#272;i";
$net2ftp_messages["Setup MySQL tables"] = "C&#224;i &#273;&#7863;t c&#225;c b&#7843;ng MySQL";
$net2ftp_messages["Create the MySQL database tables"] = "T&#7841;o c&#225;c b&#7843;ng MySQL";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "Ch&#7913;c n&#259;ng qu&#7843;n tr&#7883;";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "The handle of file %1\$s could not be opened.";
$net2ftp_messages["The file %1\$s could not be opened."] = "The file %1\$s could not be opened.";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "The handle of file %1\$s could not be closed.";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "Kh&#244;ng th&#7875; k&#233;t n&#7889;i v&#7899;i m&#225;y ch&#7911;  <b>%1\$s</b>. H&#227;y ki&#7875;m tra l&#7841;i c&#7845;u h&#236;nh c&#417; s&#7903; d&#7919; li&#7879;u m&#224; b&#7841;n &#273;&#227; nh&#7853;p v&#224;o.";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "Kh&#244;ng th&#7875; l&#7921;a ch&#7885;n c&#417; s&#7903; d&#7919; li&#7879;u <b>%1\$s</b>.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "C&#226;u l&#7879;nh SQL nr <b>%1\$s</b> &#273;&#227; kh&#244;ng th&#7875; th&#7921;c hi&#7879;n &#273;&#432;&#7907;c.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "C&#226;u l&#7879;nh SQL nr <b>%1\$s</b> &#273;&#227; th&#7921;c hi&#7879;n th&#224;nh c&#244;ng.";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "Xin vui l&#242;ng nh&#7853;p c&#7845;u h&#236;nh c&#7911;a MySQL:";
$net2ftp_messages["MySQL username"] = "T&#234;n d&#249;ng";
$net2ftp_messages["MySQL password"] = "M&#7853;t kh&#7849;u";
$net2ftp_messages["MySQL database"] = "T&#234;n c&#417; s&#7903; d&#7919; li&#7879;u";
$net2ftp_messages["MySQL server"] = "M&#225;y ch&#7911; MySQL";
$net2ftp_messages["This SQL query is going to be executed:"] = "L&#7879;nh SQL s&#7869; &#273;&#432;&#7907;c th&#7921;c hi&#7879;n:";
$net2ftp_messages["Execute"] = "Th&#7921;c hi&#7879;n";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "C&#7845;u h&#236;nh s&#7917; d&#7909;ng:";
$net2ftp_messages["MySQL password length"] = "&#272;&#7897; d&#224;i c&#7911;a m&#7853;t kh&#7849;u MySQL";
$net2ftp_messages["Results:"] = "K&#7871;t qu&#7843;:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "Ch&#7913;c n&#259;ng qu&#7843;n tr&#7883;";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "Kh&#244;ng th&#7875; th&#7921;c hi&#7879;n l&#7879;nh SQL <b>%1\$s</b>.";
$net2ftp_messages["No data"] = "Kh&#244;ng c&#243; d&#7919; li&#7879;u";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "Ch&#7913;c n&#259;ng qu&#7843;n tr&#7883;";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "B&#7843;ng <b>%1\$s</b> &#273;&#227; l&#224;m r&#7895;ng th&#224;nh c&#244;ng.";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "B&#7843;ng <b>%1\$s</b> &#273;&#227; kh&#244;ng th&#7875; l&#224;m r&#7895;ng.";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "B&#7843;ng <b>%1\$s</b> &#273;&#227; t&#7889;i &#432;u h&#243;a th&#224;nh c&#244;ng.";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "B&#7843;ng <b>%1\$s</b> &#273;&#227; kh&#244;ng th&#7875; t&#7889;i &#432;u h&#243;a.";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "Ch&#7913;c n&#259;ng qu&#7843;n tr&#7883;";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "&#272;i";
$net2ftp_messages["Disabled"] = "Ng&#7915;ng hi&#7879;u l&#7921;c";
$net2ftp_messages["Advanced FTP functions"] = "Nh&#7919;ng ch&#7913;c n&#259;ng FTP n&#226;ng cao";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "T&#7921; &#273;&#7897;ng g&#7917;i l&#7879;nh FTP t&#7899;i m&#225;y ch&#7911; FTP";
$net2ftp_messages["This function is available on PHP 5 only"] = "Ch&#7913;c n&#259;ng n&#224;y ch&#7881; c&#243; hi&#7879;u l&#7921;c v&#7899;i PHP 5";
$net2ftp_messages["Troubleshooting functions"] = "Nh&#7919;ng ch&#7913;c n&#259;ng ch&#7881;nh &#273;&#243;n";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "Ch&#7881;nh &#273;&#7889;n net2ftp tr&#234;n m&#225;y ch&#7911; web n&#224;y";
$net2ftp_messages["Troubleshoot an FTP server"] = "Ch&#7881;nh &#273;&#7889;n l&#7841;i m&#7897;t m&#225;y ch&#7911; FTP";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Ki&#7875;m tra danh s&#225;ch quy lu&#7853;t ph&#226;n t&#237;ch c&#7911;a net2ftp";
$net2ftp_messages["Translation functions"] = "Ch&#7913;c n&#259;ng d&#7883;ch thu&#7853;t";
$net2ftp_messages["Introduction to the translation functions"] = "Ch&#7881; d&#7851;n s&#7917; d&#7909;ng ch&#7913;c n&#259;ng d&#7883;ch thu&#7853;t";
$net2ftp_messages["Extract messages to translate from code files"] = "Th&#244;ng &#273;i&#7879;p thu &#273;&#432;&#7907;c t&#7915; vi&#7879;c d&#7883;ch thu&#7853;t nh&#7919;ng t&#7879;p m&#227; ngu&#7891;n";
$net2ftp_messages["Check if there are new or obsolete messages"] = "Check if there are new or obsolete messages";

$net2ftp_messages["Beta functions"] = "Nh&#7919;ng ch&#7913;c n&#259;ng th&#7917; nghi&#7879;m";
$net2ftp_messages["Send a site command to the FTP server"] = "G&#7911;i m&#7897;t c&#226;u l&#7879;nh t&#7899;i m&#225;y ch&#7911; FTP";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: m&#7853;t kh&#7849;u b&#7843;o v&#7879; m&#7897;t th&#432; m&#7909;c, t&#7841;o trang b&#225;o l&#7895;i";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: th&#7921;c hi&#234;n m&#7897;t c&#226;u l&#7879;nh SQL";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "Ch&#7913;c n&#259;ng l&#7879;nh n&#224;y kh&#244;ng c&#243; hi&#7879;u l&#7921;c tr&#234;n m&#225;y ch&#7911; web n&#224;y.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "Ch&#7913;c n&#259;ng Apache kh&#244;ng c&#243; hi&#7879;u l&#7921;c tr&#234;n m&#225;y ch&#7911; web n&#224;y.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "Ch&#7913;c n&#259;ng MySQL kh&#244;ng c&#243; hi&#7879;u l&#7921;c tr&#234;n m&#225;y ch&#7911; web n&#224;y.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "&#272;&#227; kh&#244;ng th&#7875; ch&#7901; &#273;&#7907;i. &#272;ang tho&#225;t.";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "Ch&#7881;nh &#273;&#7889;n l&#7841;i m&#7897;t m&#225;y ch&#7911; FTP";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "Nh&#7919;g c&#7845;u h&#236;nh k&#7871;t n&#7889;i:";
$net2ftp_messages["FTP server"] = "M&#225;y ch&#7911; FTP";
$net2ftp_messages["FTP server port"] = "C&#7893;ng";
$net2ftp_messages["Username"] = "T&#234;n &#273;&#249;ng";
$net2ftp_messages["Password"] = "M&#7853;t kh&#7849;u";
$net2ftp_messages["Password length"] = "&#272;&#7897; d&#224;i c&#7911;a m&#7853;t kh&#7849;u";
$net2ftp_messages["Passive mode"] = "Ki&#7875;u th&#7909; &#273;&#7897;ng";
$net2ftp_messages["Directory"] = "Th&#432; m&#7909;c";
$net2ftp_messages["Printing the result"] = "&#272;ang in k&#7871;t qu&#7843;";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "&#272;ang k&#7871;t n&#7889;i v&#7899;i m&#225;y ch&#7911; FTP: ";
$net2ftp_messages["Logging into the FTP server: "] = "&#272;ang &#273;&#259;ng nh&#7853;p v&#224;o m&#225;y ch&#7911; FTP: ";
$net2ftp_messages["Setting the passive mode: "] = "C&#7845;u h&#236;nh ki&#7875;u th&#7909; &#273;&#7897;ng:";
$net2ftp_messages["Getting the FTP server system type: "] = "&#272;ang l&#7853;p ki&#7875;u h&#7879; th&#7889;ng c&#7911;a m&#225;y ch&#7911; FTP: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "&#272;ang chuy&#7875;n t&#7899;i th&#432; m&#7909;c %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Th&#432; m&#7909;c t&#7915; m&#225;y ch&#7911; FTP l&#224;: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "&#272;ang l&#7853;p danh s&#225;ch c&#7911;a c&#225;c t&#7879;p v&#224; c&#225;c th&#432; m&#7909;c: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "&#272;ang th&#7917; l&#7853;p danh s&#225;ch c&#7911;a c&#225;c t&#7879;p v&#224; c&#225;c th&#432; m&#7909;c l&#7847;n th&#7913; hai: ";
$net2ftp_messages["Closing the connection: "] = "&#272;ang &#273;&#243;ng k&#7871;t n&#7889;i: ";
$net2ftp_messages["Raw list of directories and files:"] = "S&#7855;p x&#7871;p danh s&#225;ch c&#225;c th&#432; m&#7909;c v&#224; c&#225;c t&#7879;p: ";
$net2ftp_messages["Parsed list of directories and files:"] = "Danh s&#225;ch c&#225;c th&#432; m&#7909;c v&#224; c&#225;c t&#7879;p &#273;&#227; ph&#226;n t&#237;ch: ";

$net2ftp_messages["OK"] = "Th&#224;nh c&#244;ng";
$net2ftp_messages["not OK"] = "Kh&#244;ng th&#224;nh c&#244;ng";

} // end advanced_ftpserver


// -------------------------------------------------------------------------
// Advanced_parsing module
if ($net2ftp_globals["state"] == "advanced_parsing") {
// -------------------------------------------------------------------------

$net2ftp_messages["Test the net2ftp list parsing rules"] = "Ki&#7875;m tra danh s&#225;ch quy lu&#7853;t ph&#226;n t&#237;ch c&#7911;a net2ftp";
$net2ftp_messages["Sample input"] = "D&#7919; li&#7879;u v&#224;o &#273;&#417;n gi&#7843;n";
$net2ftp_messages["Parsed output"] = "D&#7919; li&#7879;u ph&#226;n t&#237;ch ra";

} // end advanced_parsing


// -------------------------------------------------------------------------
// Advanced_webserver module
if ($net2ftp_globals["state"] == "advanced_webserver") {
// -------------------------------------------------------------------------

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "Ch&#7881;nh &#273;&#7889;n l&#7841;i s&#7921; c&#224;i &#273;&#7863;t net2ftp c&#7911;a b&#7841;n";
$net2ftp_messages["Printing the result"] = "&#272;ang in k&#7871;t qu&#7843;";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "&#272;&#225;nh d&#7845;u n&#7871;u module FTP c&#7911;a PHP &#273;&#227; &#273;&#432;&#7907;c c&#224;i &#273;&#7863;t: ";
$net2ftp_messages["yes"] = "c&#243;";
$net2ftp_messages["no - please install it!"] = "Kh&#244;ng - Xin vui l&#242;ng c&#224;i &#273;&#7863;t n&#243;!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "Ki&#7875;m tra s&#7921; cho ph&#233;p c&#7911;a th&#432; m&#7909;c tr&#234;n m&#225;y ch&#7911;: m&#7897;t t&#7879;p nh&#7887; s&#7869; &#273;&#432;&#7907;c ghi th&#7917; &#273;&#7875; ki&#7875;m tra r&#7891;i sau &#273;&#243; s&#7869; b&#7883; x&#243;a.";
$net2ftp_messages["Creating filename: "] = "&#272;ang t&#7841;o t&#7879;p c&#243; t&#234;n: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "Th&#224;nh c&#244;ng. T&#234;n t&#7879;p l&#224;: %1\$s";
$net2ftp_messages["not OK"] = "Kh&#244;ng th&#224;nh c&#244;ng";
$net2ftp_messages["OK"] = "Th&#224;nh c&#244;ng";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "kh&#244;ng th&#224;nh c&#244;ng. H&#227;y ki&#7875;m tra s&#7921; cho ph&#233;p c&#7911;a th&#432; m&#7909;c %1\$s";
$net2ftp_messages["Opening the file in write mode: "] = "&#272;ang m&#7903; t&#7879;p trong ch&#7871; &#273;&#7885; ghi: ";
$net2ftp_messages["Writing some text to the file: "] = "&#272;ang ghi m&#7897;t v&#224;i t&#7915; l&#234;n t&#7879;p: ";
$net2ftp_messages["Closing the file: "] = "&#272;ang &#273;&#243;ng t&#7879;p: ";
$net2ftp_messages["Deleting the file: "] = "&#272;ang x&#243;a t&#7879;p: ";

$net2ftp_messages["Testing the FTP functions"] = "Testing the FTP functions";
$net2ftp_messages["Connecting to a test FTP server: "] = "Connecting to a test FTP server: ";
$net2ftp_messages["Connecting to the FTP server: "] = "&#272;ang k&#7871;t n&#7889;i v&#7899;i m&#225;y ch&#7911; FTP: ";
$net2ftp_messages["Logging into the FTP server: "] = "&#272;ang &#273;&#259;ng nh&#7853;p v&#224;o m&#225;y ch&#7911; FTP: ";
$net2ftp_messages["Setting the passive mode: "] = "C&#7845;u h&#236;nh ki&#7875;u th&#7909; &#273;&#7897;ng:";
$net2ftp_messages["Getting the FTP server system type: "] = "&#272;ang l&#7853;p ki&#7875;u h&#7879; th&#7889;ng c&#7911;a m&#225;y ch&#7911; FTP: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "&#272;ang chuy&#7875;n t&#7899;i th&#432; m&#7909;c %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Th&#432; m&#7909;c t&#7915; m&#225;y ch&#7911; FTP l&#224;: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "&#272;ang l&#7853;p danh s&#225;ch c&#7911;a c&#225;c t&#7879;p v&#224; c&#225;c th&#432; m&#7909;c: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "&#272;ang th&#7917; l&#7853;p danh s&#225;ch c&#7911;a c&#225;c t&#7879;p v&#224; c&#225;c th&#432; m&#7909;c l&#7847;n th&#7913; hai: ";
$net2ftp_messages["Closing the connection: "] = "&#272;ang &#273;&#243;ng k&#7871;t n&#7889;i: ";
$net2ftp_messages["Raw list of directories and files:"] = "S&#7855;p x&#7871;p danh s&#225;ch c&#225;c th&#432; m&#7909;c v&#224; c&#225;c t&#7879;p: ";
$net2ftp_messages["Parsed list of directories and files:"] = "Danh s&#225;ch c&#225;c th&#432; m&#7909;c v&#224; c&#225;c t&#7879;p &#273;&#227; ph&#226;n t&#237;ch: ";
$net2ftp_messages["OK"] = "Th&#224;nh c&#244;ng";
$net2ftp_messages["not OK"] = "Kh&#244;ng th&#224;nh c&#244;ng";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "Th&#234;m &#273;&#432;&#7901;ng d&#7851;n n&#224;y v&#224;o m&#7909;c &#432;a th&#237;ch:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: nh&#7845;p chu&#7897;t ph&#7843;i l&#234;n &#273;&#432;&#7901;ng d&#7851;n n&#224;y v&#224; ch&#7885;n \"Add to Favorites...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: nh&#7845;p chu&#7897;t ph&#7843;i l&#234;n &#273;&#432;&#7901;ng d&#7851;n n&#224;y v&#224; ch&#7885;n  \"Bookmark This Link...\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "Ch&#250; &#253;: khi b&#7841;n s&#7917; d&#7909;ng ch&#7913;c n&#259;ng n&#224;y, m&#7897;t c&#7917;a s&#7893; m&#7899;i s&#7869; hi&#7879;n l&#234;n v&#224; y&#234;u c&#7847;u b&#7841;n nh&#7853;p t&#234;n s&#7917; d&#7909;ng v&#224; m&#7853;t kh&#7849;u.";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "Ch&#7885;n m&#7897;t th&#432; m&#7909;c";
$net2ftp_messages["Please wait..."] = "Xin vui l&#242;ng ch&#7901;...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "Nh&#7919;ng th&#432; m&#7909;c v&#7899;i t&#234;n ch&#7913;a &#273;&#7921;ng \' c&#243; th&#7875; kh&#244;ng hi&#7875;n th&#7883; &#273;&#250;ng. Ch&#250;ng c&#243; th&#7875; b&#7883; x&#243;a. Xin vui l&#242;ng quay l&#7841;i v&#224; l&#7921;a chon th&#432; m&#7909;c con kh&#225;c.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "T&#7899;i h&#7841;n: b&#7841;n s&#7869; kh&#244;ng th&#7875; kh&#244;ng truy&#7873;n t&#7843;i &#273;&#432;&#7907;c d&#7919; li&#7879;u";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "Trong n&#7897;i quy, &#273;&#7875; b&#7843;o &#273;&#7843;m s&#7921; c&#244;ng b&#7857;ng t&#7899;i t&#7845;t c&#7843; m&#7885;i ng&#432;&#7901;i, b&#259;ng th&#244;ng truy&#7873;n t&#7843;i v&#224; th&#7901;i gian s&#7917; d&#7909;ng s&#7869; gi&#7899;i h&#7841;n &#273;&#7889;i v&#7899;i 1 ng&#432;&#7901;i d&#249;ng, v&#224; m&#7897;t ng&#224;y. M&#7897;t khi t&#7899;i gi&#7899;i h&#7841;n, b&#7841;n v&#7851;n c&#242;n tr&#236;nh duy&#7879;t FTP, nh&#432;ng kh&#244;ng th&#7875; s&#7917; d&#7909;ng n&#243;.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "N&#7871;u b&#7841;n c&#7847;n s&#7917; d&#7909;ng nhi&#7873;u l&#7847;n v&#224; th&#432;&#7901;ng xuy&#234;n, h&#227;y c&#224;i &#273;&#7863;t n&#243; l&#234;n m&#225;y ch&#7911; c&#7911;a b&#7841;n.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "Th&#432; m&#7909;c m&#7899;i";
$net2ftp_messages["New file"] = "T&#7879;p m&#7899;i";
$net2ftp_messages["HTML templates"] = "Giao di&#7879;n HTML";
$net2ftp_messages["Upload"] = "&#272;&#432;a l&#234;n";
$net2ftp_messages["Java Upload"] = "&#272;&#432;a l&#234;n b&#7857;ng Java";
$net2ftp_messages["Flash Upload"] = "Flash Upload";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Advanced"] = "N&#226;ng cao";
$net2ftp_messages["Copy"] = "Sao ch&#233;p";
$net2ftp_messages["Move"] = "Di chuy&#7875;n";
$net2ftp_messages["Delete"] = "X&#243;a";
$net2ftp_messages["Rename"] = "&#272;&#7893;i t&#234;n";
$net2ftp_messages["Chmod"] = "Thay &#273;&#7893;i ki&#7875;u";
$net2ftp_messages["Download"] = "T&#7843;i ph&#7847;n m&#7873;m";
$net2ftp_messages["Unzip"] = "Unzip";
$net2ftp_messages["Zip"] = "N&#233;n";
$net2ftp_messages["Size"] = "K&#237;ch th&#432;&#7899;c";
$net2ftp_messages["Search"] = "T&#236;m ki&#7871;m";
$net2ftp_messages["Go to the parent directory"] = "T&#7899;i th&#432; m&#7909;c ch&#7911;";
$net2ftp_messages["Go"] = "&#272;i";
$net2ftp_messages["Transform selected entries: "] = "Thay &#273;&#7893;i c&#225;c k&#7871;t qu&#7843; &#273;&#227; l&#7921;a ch&#7885;n: ";
$net2ftp_messages["Transform selected entry: "] = "Transform selected entry: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "T&#7841;o m&#7897;t th&#432; m&#7909;c con trong th&#432; m&#7909;c %1\$s";
$net2ftp_messages["Create a new file in directory %1\$s"] = "T&#7841;o m&#7897;t t&#7879;p m&#7899;i trong th&#432; m&#7909;c %1\$s";
$net2ftp_messages["Create a website easily using ready-made templates"] = "T&#7841;o m&#7897;t trang web m&#7899;i d&#7877; d&#224;ng v&#7899;i c&#225;c giao di&#7879;n HTML &#273;&#227; c&#243; s&#7861;n";
$net2ftp_messages["Upload new files in directory %1\$s"] = "&#272;&#432;a l&#234;n nh&#7919;ng t&#7879;p m&#7899;i v&#224;o th&#432; m&#7909;c %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "&#272;&#432;a l&#234;n c&#225;c th&#432; m&#7909;c v&#224; c&#225;c t&#7879;p s&#7917; d&#7909;ng Java applet";
$net2ftp_messages["Upload files using a Flash applet"] = "Upload files using a Flash applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Install software packages (requires PHP on web server)";
$net2ftp_messages["Go to the advanced functions"] = "&#272;i t&#7899;i nh&#7919;ng ch&#7913;c n&#259;ng n&#226;ng cao";
$net2ftp_messages["Copy the selected entries"] = "Sao ch&#233;p k&#7871;t qu&#7843; &#273;&#227; l&#7921;a ch&#7885;n";
$net2ftp_messages["Move the selected entries"] = "Di chuy&#7875;n k&#7871;t qu&#7843; &#273;&#227; l&#7921;a ch&#7885;n";
$net2ftp_messages["Delete the selected entries"] = "X&#243;a k&#7871;t qu&#7843; &#273;&#227; l&#7921;a ch&#7885;n";
$net2ftp_messages["Rename the selected entries"] = "&#272;&#7893;i t&#234;n k&#7871;t qu&#7843; &#273;&#227; l&#7921;a ch&#7885;n";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "Chmod c&#225;c k&#7871;t qu&#7843; &#273;&#227; l&#7921;a ch&#7885;n (ch&#7881; l&#224;m vi&#234;c tr&#234;n m&#225;y ch&#7911; Unix/Linux )";
$net2ftp_messages["Download a zip file containing all selected entries"] = "T&#7843;i xu&#7889;ng m&#7897;t t&#7879;p n&#233;n ch&#7913;a t&#7845;t c&#7843; c&#225;c k&#7871;t qu&#7843; &#273;&#227; l&#7921;a ch&#7885;n";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Unzip the selected archives on the FTP server";
$net2ftp_messages["Zip the selected entries to save or email them"] = "N&#233;n k&#7871;t qu&#7843; &#273;&#227; l&#7921;a ch&#7885;n r&#7891;i sau &#273;&#243; l&#432;u l&#7841;i ho&#7863;c g&#7917;i qua email";
$net2ftp_messages["Calculate the size of the selected entries"] = "T&#237;nh to&#225;n k&#237;ch th&#432;&#7899;c c&#7911;a c&#225;c k&#7871;t qu&#7843; &#273;&#227; l&#7921;a ch&#7885;n";
$net2ftp_messages["Find files which contain a particular word"] = "T&#236;m ki&#7871;m c&#225;c t&#7879;p c&#243; ch&#7913;a &#273;&#7921;ng kh&#243;a";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "H&#227;y nh&#7845;p chu&#7897;t &#273;&#7875; s&#7855;p x&#7871;p %1\$s theo s&#7921; gi&#7843;m d&#7847;n c&#7911;a th&#7913; t&#7921;";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "H&#227;y nh&#7845;p chu&#7897;t &#273;&#7875; s&#7855;p x&#7871;p %1\$s theo s&#7921; t&#259;ng d&#7847;n c&#7911;a th&#7913; t&#7921;";
$net2ftp_messages["Ascending order"] = "T&#259;ng l&#234;n theo th&#7913; t&#7921;";
$net2ftp_messages["Descending order"] = "Gi&#7843;m d&#7847;n theo th&#7913; t&#7921;";
$net2ftp_messages["Upload files"] = "&#272;&#432;a t&#7879;p l&#234;n m&#225;y ch&#7911;";
$net2ftp_messages["Up"] = "L&#234;n tr&#234;n";
$net2ftp_messages["Click to check or uncheck all rows"] = "H&#227;y nh&#7845;p chu&#7897;t &#273;&#7875; &#273;&#225;nh d&#7845;u ho&#7863;c kh&#244;ng &#273;&#225;nh d&#7845;u c&#225;c h&#224;ng ngang";
$net2ftp_messages["All"] = "T&#7845;t c&#7843;";
$net2ftp_messages["Name"] = "T&#234;n";
$net2ftp_messages["Type"] = "Ki&#7875;u";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "Ng&#432;&#7901;i t&#7841;o n&#234;n";
$net2ftp_messages["Group"] = "Nh&#243;m";
$net2ftp_messages["Perms"] = "V&#7883; tr&#237;";
$net2ftp_messages["Mod Time"] = "Ki&#7875;u th&#7901;i gian";
$net2ftp_messages["Actions"] = "C&#225;c h&#224;nh &#273;&#7897;ng";
$net2ftp_messages["Select the directory %1\$s"] = "L&#7921;a ch&#7885;n th&#432; m&#7909;c %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "L&#7921;a ch&#7885;n t&#7879;p %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "L&#7921;a ch&#7885;n l&#7889;i t&#7855;t %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "&#272;i t&#7899;i th&#432; m&#7909;c %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "T&#7843;i t&#7879;p %1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "Follow symlink %1\$s";
$net2ftp_messages["View"] = "Xem";
$net2ftp_messages["Edit"] = "S&#7917;a &#273;&#7893;i";
$net2ftp_messages["Update"] = "C&#7853;p nh&#7853;t";
$net2ftp_messages["Open"] = "M&#7903;";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Xem m&#227; ngu&#7891;n c&#7911;a t&#7879;p %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "S&#7917;a &#273;&#7893;i m&#227; ngu&#7891;n c&#7911;a t&#7879;p %1\$s";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "&#272;&#432;a l&#234;n m&#7897;t phi&#234;n b&#7843;n m&#7899;i c&#7911;a t&#7879;p %1\$s v&#224; h&#242;a v&#224;o nh&#7919;ng thay &#273;&#7893;i";
$net2ftp_messages["View image %1\$s"] = "Xem &#7843;nh %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "Xem t&#7879;p %1\$s t&#7915; m&#225;y ch&#7911; web c&#7911;a b&#7841;n";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(Ch&#253; &#253;: &#273;&#432;&#7901;ng d&#7851;n n&#224;y c&#243; th&#7875; kh&#244;ng l&#224;m vi&#7879;c n&#7871;u nh&#432; b&#7841;n kh&#244;ng c&#243; m&#7897;t t&#234;n mi&#7873;n.)";
$net2ftp_messages["This folder is empty"] = "Th&#432; m&#7909;c n&#224;y tr&#7889;ng r&#7895;ng";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "C&#225;c th&#432; m&#7909;c";
$net2ftp_messages["Files"] = "C&#225;c t&#7879;p";
$net2ftp_messages["Symlinks"] = "C&#225;c l&#7889;i t&#7855;t";
$net2ftp_messages["Unrecognized FTP output"] = "Kh&#244;ng th&#7875; th&#7845;y r&#245; &#273;&#432;&#7901;ng ra FTP";
$net2ftp_messages["Number"] = "S&#7889;";
$net2ftp_messages["Size"] = "K&#237;ch th&#432;&#7899;c";
$net2ftp_messages["Skipped"] = "&#272;&#227; b&#7887; qua";
$net2ftp_messages["Data transferred from this IP address today"] = "Data transferred from this IP address today";
$net2ftp_messages["Data transferred to this FTP server today"] = "Data transferred to this FTP server today";

// printLocationActions()
$net2ftp_messages["Language:"] = "Ng&#244;n ng&#7919:";
$net2ftp_messages["Skin:"] = "Giao di&#7879;n:";
$net2ftp_messages["View mode:"] = "Xem ki&#7875;u:";
$net2ftp_messages["Directory Tree"] = "C&#226;y th&#432; m&#7909;c";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "Th&#7921;c hi&#7879;n %1\$s trong m&#7897;t c&#7911;a s&#7893; m&#7899;i";
$net2ftp_messages["This file is not accessible from the web"] = "T&#7879;p n&#224;y kh&#244;ng c&#243; s&#7861;n t&#7915; web";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "Nh&#225;y chu&#7897;t &#273;&#7875; t&#7899;i m&#7897;t th&#432; m&#7909;c con:";
$net2ftp_messages["Choose"] = "L&#7921;a ch&#7885;n";
$net2ftp_messages["Up"] = "L&#234;n tr&#234;n";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "K&#237;ch th&#432;&#7899;c c&#7911;a c&#225;c th&#432; m&#7909;c v&#224; t&#7879;p &#273;&#227; l&#7921;a ch&#7885;n";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "K&#237;ch th&#432;&#7899;c t&#7893;ng c&#7897;ng c&#7911;a c&#225;c th&#432; m&#7909;c v&#224; t&#7879;p &#273;&#227; l&#7921;a ch&#7885;n l&#224;:";
$net2ftp_messages["The number of files which were skipped is:"] = "S&#7889; t&#7879;p b&#7883; b&#7887; qua l&#224;:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "Chmod th&#432; m&#7909;c v&#224; t&#7879;p";
$net2ftp_messages["Set all permissions"] = "L&#7853;p t&#7845;t c&#7843; s&#7921; cho ph&#233;p";
$net2ftp_messages["Read"] = "&#272;&#7885;c";
$net2ftp_messages["Write"] = "Ghi";
$net2ftp_messages["Execute"] = "Th&#7921;c hi&#7879;n";
$net2ftp_messages["Owner"] = "Ng&#432;&#7901;i t&#7841;o n&#234;n";
$net2ftp_messages["Group"] = "Nh&#243;m";
$net2ftp_messages["Everyone"] = "M&#7885;i ng&#432;&#7901;i";
$net2ftp_messages["To set all permissions to the same values, enter those permissions above and click on the button \"Set all permissions\""] = "&#272;&#7875; l&#7853;p t&#7845;t c&#7843; s&#7921; cho ph&#233;p nhanh ch&#243;ng, ch&#7885;n t&#7845;t c&#7843; s&#7921; cho ph&#233;p &#7903; tr&#234;n v&#224; &#7845;n n&#250;t \"L&#7853;p t&#7845;t c&#7843; s&#7921; cho ph&#233;p\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "&#272;&#227; thay &#273;&#7893;i s&#7921; cho ph&#233;p c&#7911;a th&#432; m&#7909;c <b>%1\$s</b> th&#224;nh: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "&#272;&#227; thay &#273;&#7893;i s&#7921; cho ph&#233;p c&#7911;a t&#7879;p <b>%1\$s</b> th&#224;nh: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "&#272;&#227; thay &#273;&#7893;i s&#7921; cho ph&#233;p c&#7911;a l&#7889; t&#7855;t <b>%1\$s</b> th&#224;nh: ";
$net2ftp_messages["Chmod value"] = "K&#7871;t qu&#7843; Chmod";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "Chmod t&#7845;t c&#7843; c&#225;c th&#432; m&#7909;c con c&#7911;a th&#432; m&#7909;c n&#224;y";
$net2ftp_messages["Chmod also the files within this directory"] = "Chmod t&#7845;t c&#7843; c&#225;c t&#7879;p thu&#7897;c th&#432; m&#7909;c n&#224;y";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "L&#7879;nh Chmod nr <b>%1\$s</b> &#273;&#227; ra kh&#7887;i ph&#7841;m vi 000-777. Xin vui l&#242;ng l&#224;m l&#7841;i.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "Ch&#7885;n m&#7897;t th&#432; m&#7909;c";
$net2ftp_messages["Copy directories and files"] = "Sao ch&#233;p c&#225;c th&#432; m&#7909;c v&#224; c&#225;c t&#7879;p";
$net2ftp_messages["Move directories and files"] = "Di chuy&#7875;n c&#225;c th&#432; m&#7909;c v&#224; c&#225;c t&#7879;p";
$net2ftp_messages["Delete directories and files"] = "X&#243;a c&#225;c th&#432; m&#7909;c v&#224; c&#225;c t&#7879;p";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "B&#7841;n c&#243; ch&#7855;c ch&#7855;n mu&#7889;n xo&#225; c&#225;c th&#432; m&#7909;c v&#224; c&#225;c t&#7879;p n&#224;y?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "T&#7845;t c&#7843; th&#432; m&#7909;c v&#224; t&#7879;p con c&#7911;a th&#432; m&#7909;c &#273;&#227; l&#7921;a ch&#7885;n c&#361;ng s&#7869; b&#7883; x&#243;a!";
$net2ftp_messages["Set all targetdirectories"] = "L&#7853;p t&#7845;t c&#7843; c&#225;c th&#432; m&#7909;c t&#7899;i";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "&#272;&#7875; l&#7853;p m&#7897;t th&#432; m&#7909;c t&#7899;i th&#244;ng th&#432;&#7901;ng, h&#227;y nh&#7853;p th&#432; m&#7909;c t&#7899;i v&#224;o b&#234;n gi&#7899;i v&#224; nh&#7845;p v&#224;o n&#250;t\"L&#7853;p t&#7845;t c&#7843; c&#225;c th&#432; m&#7909;c t&#7899;i\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Ch&#250; &#253;: &#273;&#237;ch &#273;&#7871;n ph&#7843;i t&#7891;n t&#7841;i tr&#432;&#7899;c khi b&#7845;t k&#7923; th&#7913; g&#236; c&#243; th&#7875; &#273;&#432;&#7907;c sao ch&#233;p t&#7899;i n&#243;.";
$net2ftp_messages["Different target FTP server:"] = "&#272;&#7871;n m&#7897;t m&#225;y ch&#7911; FTP kh&#225;c:";
$net2ftp_messages["Username"] = "T&#234;n &#273;&#249;ng";
$net2ftp_messages["Password"] = "M&#7853;t kh&#7849;u";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "&#272;&#7875; tr&#7889;ng n&#7871;ub&#7841;n mu&#7889;n sao ch&#233;p c&#225;c t&#7879;p t&#7899;i c&#249;ng m&#7897;t t&#224;i kho&#7843;n tr&#234;n m&#225;y ch&#7911; FTP.";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "N&#7871;u b&#7841;n mu&#7889;n sao ch&#233;p c&#225;c t&#7879;p t&#7899;i m&#7897;t m&#225;y ch&#7911; FTP kh&#225;c, h&#227;y nh&#7853;p v&#224;o d&#7919; li&#7879;u &#273;&#259;ng nh&#7853;p.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "&#272;&#7875; tr&#7889;ng n&#7871;u b&#7841;n mu&#7889;n di chuy&#7875;n c&#225;c t&#7879;p t&#7899;i c&#249;ng m&#7897;t t&#224;i kho&#7843;n tr&#234;n m&#225;y ch&#7911; FTP.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "N&#7871;u b&#7841;n mu&#7889;n di chuy&#7875;n c&#225;c t&#7879;p t&#7899;i m&#7897;t m&#225;y ch&#7911; FTP kh&#225;c, h&#227;y nh&#7853;p v&#224;o d&#7919; li&#7879;u &#273;&#259;ng nh&#7853;p.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "Sao ch&#233;p th&#432; m&#7909;c <b>%1\$s</b> t&#7899;i:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "Di chuy&#7875;n th&#432; m&#7909;c <b>%1\$s</b> t&#7899;i:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "Th&#432; m&#7909;c <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "Sao ch&#233;p t&#7879;p <b>%1\$s</b> t&#7899;i:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "Di chuy&#7875;n t&#7879;p <b>%1\$s</b> t&#7899;i:";
$net2ftp_messages["File <b>%1\$s</b>"] = "T&#7879;p <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "Sao ch&#233;p l&#7889;i t&#7855;t <b>%1\$s</b> t&#7899;i:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "Di chuy&#7875;n l&#7889;i t&#7855;t <b>%1\$s</b> t&#7899;i:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "L&#7889;i t&#7855;t <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "Th&#432; m&#7909;c &#273;&#7871;n:";
$net2ftp_messages["Target name:"] = "T&#234;n &#273;&#7871;n:";
$net2ftp_messages["Processing the entries:"] = "&#272;ang trong qu&#225; tr&#236;nh:";

} // end copymovedelete


// -------------------------------------------------------------------------
// Download file module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// EasyWebsite module
if ($net2ftp_globals["state"] == "easyWebsite") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create a website in 4 easy steps"] = "T&#7841;o m&#7897;t trang web &#273;&#7877; d&#224;ng v&#7899;i 4 b&#432;&#7899;c";
$net2ftp_messages["Template overview"] = "T&#7893;ng quan giao di&#7879;n";
$net2ftp_messages["Template details"] = "Chi ti&#7871;t giao di&#7879;n";
$net2ftp_messages["Files are copied"] = "C&#225;c t&#7879;p &#273;&#227; sao ch&#233;p";
$net2ftp_messages["Edit your pages"] = "S&#7917;a &#273;&#7893;i c&#225;c trang c&#7911;a b&#7841;n";

// Screen 1 - printTemplateOverview
$net2ftp_messages["Click on the image to view the details of a template."] = "Nh&#7845;p chu&#7897;t v&#224;o h&#236;nh &#7843;nh &#273;&#7875; xem chi ti&#7871;t c&#7911;a giao di&#7879;n.";
$net2ftp_messages["Back to the Browse screen"] = "Quay l&#7841;i trang l&#7921;a ch&#7885;n";
$net2ftp_messages["Template"] = "Giao di&#7879;n";
$net2ftp_messages["Copyright"] = "B&#7843;n quy&#7873;n";
$net2ftp_messages["Click on the image to view the details of this template"] = "Nh&#7845;p chu&#7897;t v&#224;o h&#236;nh &#7843;nh &#273;&#7875; xem chi ti&#7871;t c&#7911;a giao di&#7879;n n&#224;y";

// Screen 2 - printTemplateDetails
$net2ftp_messages["The template files will be copied to your FTP server. Existing files with the same filename will be overwritten. Do you want to continue?"] = "Nh&#7919;ng t&#7879;p giao di&#7879;n n&#224;y s&#7869; &#273;&#432;&#7907;c sao ch&#233;p t&#7899;i m&#225;y ch&#7911; FTP. Nh&#7919;ng t&#7879;p &#273;&#227; t&#7891;n t&#7841;i nh&#432; t&#7879;p n&#224;y s&#7869; b&#7883; ghi &#273;&#232; l&#234;n. B&#7841;n c&#243; mu&#7889;n ti&#7871;p t&#7909;c?";
$net2ftp_messages["Install template to directory: "] = "C&#224;i &#273;&#7863;t t&#7899;i th&#432; m&#7909;c: ";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Size"] = "K&#237;ch th&#432;&#7899;c";
$net2ftp_messages["Preview page"] = "Xem trang";
$net2ftp_messages["opens in a new window"] = "m&#7903; ra m&#7897;t c&#7917;a s&#7893; m&#7899;i";

// Screen 3
$net2ftp_messages["Please wait while the template files are being transferred to your server: "] = "Xin vui l&#242;ng ch&#7901; &#273;&#7907;i trong khi t&#7879;p giao di&#7879;n &#273;ang &#273;&#432;&#7907;c chuy&#7875;n t&#7899;i m&#225;y ch&#7911; c&#7911;a b&#7841;n: ";
$net2ftp_messages["Done."] = "&#272;&#227; th&#7921;c hi&#7879;n.";
$net2ftp_messages["Continue"] = "Ti&#7871;p t&#7909;c";

// Screen 4 - printEasyAdminPanel
$net2ftp_messages["Edit page"] = "S&#7917;a &#273;&#7893;i trang";
$net2ftp_messages["Browse the FTP server"] = "Duy&#7879;t m&#225;y ch&#7911; FTP";
$net2ftp_messages["Add this link to your favorites to return to this page later on!"] = "Th&#234;m &#273;&#432;&#7901;ng d&#7851;n n&#224;y v&#224;o m&#7909;c &#432;a th&#237;ch &#273;&#7875; c&#243; th&#7875; tr&#7903; l&#7841;i trang n&#224;y sau!";
$net2ftp_messages["Edit website at %1\$s"] = "S&#7917;a &#273;&#7893;i trang web t&#7841;i %1\$s";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: nh&#7845;p chu&#7897;t ph&#7843;i l&#234;n &#273;&#432;&#7901;ng d&#7851;n n&#224;y v&#224; ch&#7885;n \"Add to Favorites...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: nh&#7845;p chu&#7897;t ph&#7843;i l&#234;n &#273;&#432;&#7901;ng d&#7851;n n&#224;y v&#224; ch&#7885;n  \"Bookmark This Link...\"";

// ftp_copy_local2ftp
$net2ftp_messages["WARNING: Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing..."] = "Th&#244;ng b&#225;o: kh&#244;ng th&#7875; t&#7841;o th&#432; m&#7909;c con <b>%1\$s</b>. N&#243; c&#243; th&#7875; &#273;&#227; t&#7891;n t&#7841;i. &#272;ang l&#224;m ti&#7871;p...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "&#272;&#227; t&#7841;o th&#432; m&#7909;c con &#273;&#7871;n <b>%1\$s</b>";
$net2ftp_messages["WARNING: Unable to copy the file <b>%1\$s</b>. Continuing..."] = "Th&#244;ng b&#225;o: kh&#244;ng th&#7875; sao ch&#233;p t&#7879;p <b>%1\$s</b>. &#272;ang l&#224;m ti&#7871;p...";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "&#272;&#227; sao ch&#233;p t&#7879;p <b>%1\$s</b>";
}


// -------------------------------------------------------------------------
// Edit module
if ($net2ftp_globals["state"] == "edit") {
// -------------------------------------------------------------------------

// /modules/edit/edit.inc.php
$net2ftp_messages["Unable to open the template file"] = "Kh&#244;ng th&#7875; m&#7903; t&#7879;p giao di&#7879;n";
$net2ftp_messages["Unable to read the template file"] = "Kh&#244;ng th&#7875; &#273;&#7885;c t&#7879;p giao di&#7879;n";
$net2ftp_messages["Please specify a filename"] = "Xin vui l&#242;ng x&#225;c &#273;&#7883;nh m&#7897;t t&#234;n t&#7879;p";
$net2ftp_messages["Status: This file has not yet been saved"] = "Th&#244;ng b&#225;o: t&#7879;p kh&#244;ng &#273;&#432;&#7907;c l&#432;u l&#7841;i";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "Th&#244;ng b&#225;o: &#273;&#227; l&#432;u <b>%1\$s</b> s&#7917; d&#7909;ng ki&#7875;u %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "Th&#244;ng b&#225;o: <b>T&#7879;p n&#224;y &#273;&#227; kh&#244;ng th&#7875; l&#432;u l&#7841;i &#273;&#432;&#7907;c</b>";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "Th&#432; m&#7909;c: ";
$net2ftp_messages["File: "] = "T&#7879;p: ";
$net2ftp_messages["New file name: "] = "T&#234;n t&#7879;p m&#7899;i: ";
$net2ftp_messages["Character encoding: "] = "Character encoding: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "Note: changing the textarea type will save the changes";
$net2ftp_messages["Copy up"] = "Sao ch&#233;p l&#234;n";
$net2ftp_messages["Copy down"] = "Sao ch&#233;p xu&#7889;ng";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "T&#236;m ki&#7871;m c&#225;c th&#432; m&#7909;c v&#224; t&#7879;p";
$net2ftp_messages["Search again"] = "T&#236;m ki&#7871;m l&#7841;i";
$net2ftp_messages["Search results"] = "K&#7871;t qu&#7843; t&#236;m ki&#7871;m";
$net2ftp_messages["Please enter a valid search word or phrase."] = "Xin vui l&#242;ng &#273;i&#7873;n &#273;&#250;ng t&#7915; kho&#225; hay c&#226;u t&#236;m ki&#7871;m.";
$net2ftp_messages["Please enter a valid filename."] = "Xin vui l&#242;ng &#273;i&#7873;n &#273;&#250;ng t&#234;n t&#7879;p.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "Xin vui l&#242;ng &#273;i&#7873;n &#273;&#250;ng k&#237;ch th&#432;&#7899;c t&#7879;p v&#224;o  &#244; tr&#7889;ng \"t&#7915;\" , v&#237; d&#7909;: 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "Xin vui l&#242;ng &#273;i&#7873;n &#273;&#250;ng k&#237;ch th&#432;&#7899;c t&#7879;p v&#224;o &#244; tr&#7889;ng \"&#273;&#7871;n\", v&#237; d&#7909;: 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "Xin vui l&#242;ng &#273;i&#7873;n &#273;&#250;ng &#273;&#7883;nh d&#7841;ng n&#259;m-th&#225;ng-ng&#224;y v&#224;o &#244; tr&#7889;ng \"t&#7915;\".";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "Xin vui l&#242;ng &#273;i&#7873;n &#273;&#250;ng &#273;&#7883;nh d&#7841;ng n&#259;m-th&#225;ng-ng&#224;y v&#224;o &#244; tr&#7889;ng \"&#273;&#7871;n\".";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "T&#7915; kho&#225; <b>%1\$s</b> kh&#244;ng t&#236;m th&#7845;y trong c&#225;c t&#7879;p hay th&#432; m&#7909;c &#273;&#227; l&#7921;a ch&#7885;n.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "T&#7915; kho&#225; <b>%1\$s</b> &#273;&#227; &#273;&#432;&#7907;c t&#236;m th&#7845;y trong c&#225;c t&#7879;p:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "T&#236;m ki&#7871;m m&#7897;t t&#7915; ho&#7863;c m&#7897;t c&#226;u";
$net2ftp_messages["Case sensitive search"] = "Case sensitive search";
$net2ftp_messages["Restrict the search to:"] = "T&#236;m ki&#7871;m l&#7841;i m&#7897;t c&#225;c k&#7929; l&#432;&#7905;ng t&#7899;i:";
$net2ftp_messages["files with a filename like"] = "nh&#7919;ng t&#7879;p c&#243; t&#234;n gi&#7889;ng nhau";
$net2ftp_messages["(wildcard character is *)"] = "(k&#253; t&#7921; li&#234;n k&#7871;t l&#224; *)";
$net2ftp_messages["files with a size"] = "nh&#7919;ng t&#7879;p c&#243; c&#249;ng k&#237;ch th&#432;&#7899;c";
$net2ftp_messages["files which were last modified"] = "Nh&#7919;ng t&#7879;p &#273;&#432;&#7907;c s&#7917;a &#273;&#7893;i sau c&#249;ng";
$net2ftp_messages["from"] = "t&#7915;";
$net2ftp_messages["to"] = "&#273;&#7871;n";

$net2ftp_messages["Directory"] = "Th&#432; m&#7909;c";
$net2ftp_messages["File"] = "T&#7879;p";
$net2ftp_messages["Line"] = "&#272;&#432;&#7901;ng th&#7859;ng";
$net2ftp_messages["Action"] = "H&#224;nh &#273;&#7897;ng";
$net2ftp_messages["View"] = "Xem";
$net2ftp_messages["Edit"] = "S&#7917;a &#273;&#7893;i";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Xem m&#227; ngu&#7891;n c&#7911;a t&#7879;p %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "S&#7917;a &#273;&#7893;i m&#227; ngu&#7891;n c&#7911;a t&#7879;p %1\$s";

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
$net2ftp_messages["Unable to open the template file"] = "Kh&#244;ng th&#7875; m&#7903; t&#7879;p giao di&#7879;n";
$net2ftp_messages["Unable to read the template file"] = "Kh&#244;ng th&#7875; &#273;&#7885;c t&#7879;p giao di&#7879;n";
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
$net2ftp_messages["Upload directories and files using a Java applet"] = "&#272;&#432;a l&#234;n c&#225;c th&#432; m&#7909;c v&#224; c&#225;c t&#7879;p s&#7917; d&#7909;ng Java applet";
$net2ftp_messages["Number of files:"] = "S&#7889; t&#7879;p:";
$net2ftp_messages["Size of files:"] = "K&#237;ch th&#432;&#7899;c t&#7879;p:";
$net2ftp_messages["Add"] = "Th&#234;m";
$net2ftp_messages["Remove"] = "Lo&#7841;i b&#7887;";
$net2ftp_messages["Upload"] = "&#272;&#432;a l&#234;n";
$net2ftp_messages["Add files to the upload queue"] = "Th&#234;m t&#7879;p v&#224;o danh s&#225;ch &#273;&#432;a l&#234;n";
$net2ftp_messages["Remove files from the upload queue"] = "Lo&#7841;i b&#7887; t&#7879;p kh&#7887;i danh s&#225;ch &#273;&#432;a l&#234;n";
$net2ftp_messages["Upload the files which are in the upload queue"] = "&#272;&#432;a l&#234;n nh&#7919;ng t&#7879;p trong danh s&#225;ch";
$net2ftp_messages["Maximum server space exceeded. Please select less/smaller files."] = "Qu&#225; gi&#7899;i h&#7841;n dung l&#432;&#7907;ng c&#7911;a m&#225;y ch&#7911;. Xin vui l&#242;ng l&#7921;a ch&#7885;n t&#7879;p nh&#7887; h&#417;n ho&#7863;c &#237;t t&#7879;p h&#417;n.";
$net2ftp_messages["Total size of the files is too big. Please select less/smaller files."] = "K&#237;ch th&#432;&#7899;c t&#7893;ng c&#7897;ng c&#7911;a c&#225;c t&#7879;p qu&#225; l&#7899;n. Xin vui l&#242;ng l&#7921;a ch&#7885;n t&#7879;p nh&#7887; h&#417;n ho&#7863;c &#237;t t&#7879;p h&#417;n.";
$net2ftp_messages["Total number of files is too high. Please select fewer files."] = "S&#7889; t&#7879;p t&#7893;ng c&#7897;ng qu&#225; l&#7899;n. Xin vui l&#242;ng ch&#7885;n &#237;t t&#7879;p h&#417;n.";
$net2ftp_messages["Note: to use this applet, Sun's Java plugin must be installed (version 1.4 or newer)."] = "Ch&#250; &#253;: mu&#7889;n s&#7917; d&#7909;ng Applet, Sun's Java plugin ph&#7843;i &#273;&#432;&#7907;c c&#224;i &#273;&#7863;t (phi&#234;n b&#7843;n 1.4 ho&#7863;c m&#7899;i h&#417;n).";

} // end jupload



// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login") {
// -------------------------------------------------------------------------
$net2ftp_messages["Login!"] = "&#272;&#259;ng nh&#7853;p!";
$net2ftp_messages["Once you are logged in, you will be able to:"] = "Once you are logged in, you will be able to:";
$net2ftp_messages["Navigate the FTP server"] = "&#272;&#7883;nh h&#432;&#7899;ng m&#225;y ch&#7911; FTP";
$net2ftp_messages["Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."] = "Sau khi &#273;&#259;ng nh&#7853;p b&#7841;n c&#243; th&#7875; duy&#7879;t th&#432; m&#7909;c t&#7899;i th&#432; m&#7909;c v&#224; xem t&#7845;t c&#7843; c&#225;c th&#432; m&#7909;c con v&#224; t&#7879;p con.";
$net2ftp_messages["Upload files"] = "&#272;&#432;a t&#7879;p l&#234;n m&#225;y ch&#7911;";
$net2ftp_messages["There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet."] = "C&#243; ba c&#225;ch kh&#225;c nhau &#273;&#7875; &#273;&#432;a c&#225;c t&#7879;p l&#234;n m&#225;y ch&#7911;: c&#225;ch chu&#7849;n, &#273;&#432;a t&#7879;p n&#233;n (.zip) l&#234;n v&#224; sau &#273;&#243; gi&#7843;i n&#233;n n&#243;, v&#224; s&#7917; d&#7909;ng Java Applet.";
$net2ftp_messages["Download files"] = "T&#7843;i t&#7879;p";
$net2ftp_messages["Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive."] = "Nh&#7845;p chu&#7897;t v&#224;o t&#234;n t&#7879;p &#273;&#7875; t&#7843;i nhanh n&#243;.<br />Ch&#7885;n nhi&#7873;u t&#7879;p c&#249;ng m&#7897;t l&#250;c v&#224; t&#7843;i xu&#7889;ng. T&#7845;t c&#7843; c&#225; t&#7879;p &#273;&#243; s&#7869; &#273;&#7921;oc gom l&#7841;i v&#224;o m&#7897;t t&#7879;p c&#243; d&#7841;ng n&#233;n.";
$net2ftp_messages["Zip files"] = "N&#233;n t&#7879;p";
$net2ftp_messages["... and save the zip archive on the FTP server, or email it to someone."] = "...v&#224; l&#432;u tr&#234;n m&#225;y ch&#7911; FTP ho&#7863;c g&#7917;i t&#7899;i cho m&#7897;t ai &#273;&#243;.";
$net2ftp_messages["Unzip files"] = "Unzip files";
$net2ftp_messages["Different formats are supported: .zip, .tar, .tgz and .gz."] = "Different formats are supported: .zip, .tar, .tgz and .gz.";
$net2ftp_messages["Install software"] = "Install software";
$net2ftp_messages["Choose from a list of popular applications (PHP required)."] = "Choose from a list of popular applications (PHP required).";
$net2ftp_messages["Copy, move and delete"] = "Sao ch&#233;p, di chuy&#7875;n v&#224; xo&#225;";
$net2ftp_messages["Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted."] = "B&#7841;n c&#243; th&#7875; sao ch&#233;p, di chuy&#7875;n, ho&#7863;c xo&#225; m&#7897;t t&#7879;p, nhi&#7873;u t&#7879;p, m&#7897;t th&#432; m&#7909;c hay  nhi&#7873;u th&#432; m&#7909;c.";
$net2ftp_messages["Copy or move to a 2nd FTP server"] = "Sao ch&#233;p, di chuy&#7875;n gi&#7919;a 2 m&#225;y ch&#7911; FTP";
$net2ftp_messages["Handy to import files to your FTP server, or to export files from your FTP server to another FTP server."] = "Sao ch&#233;p ho&#7863;c di chuy&#7875;n t&#7879;p ho&#7863;c th&#432; m&#7909;c gi&#7919;a 2 m&#225;y ch&#7911; FTP.";
$net2ftp_messages["Rename and chmod"] = "&#272;&#7893;i t&#234;n ho&#7863;c Chmod";
$net2ftp_messages["Chmod handles directories recursively."] = "&#272;&#7893;i t&#234;n ho&#7863;c Chmod t&#7879;p ho&#7863;c th&#432; m&#7909;c.";
$net2ftp_messages["View code with syntax highlighting"] = "Hi&#7875;n th&#7883; m&#227; c&#7911;a t&#7879;p";
$net2ftp_messages["PHP functions are linked to the documentation on php.net."] = "C&#225;c ch&#7913; n&#259;ng PHP &#273;&#432;&#7907;c li&#234;n k&#7871;t t&#7899;i t&#224;i li&#7879;u t&#7841;i Php.net.";
$net2ftp_messages["Plain text editor"] = "S&#7917;a &#273;&#7893;i v&#259;n b&#7843;n";
$net2ftp_messages["Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server."] = "S&#7917;a &#273;&#7893;i v&#259;n b&#7843;n ngay tr&#234;n tr&#236;nh duy&#7879;t c&#7911;a b&#7841;n. B&#7845;t k&#7923; l&#250;c n&#224;o b&#7841;n c&#361;ng c&#243; th&#7875; l&#432;u l&#7841;i n&#243; tr&#234;n m&#225;y ch&#7911; FTP.";
$net2ftp_messages["HTML editors"] = "S&#7917;a &#273;&#7893;i t&#7879;p HTML";
$net2ftp_messages["Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from."] = "Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from.";
$net2ftp_messages["Code editor"] = "S&#7917;a &#273;&#7893;i m&#227; ngu&#7891;n";
$net2ftp_messages["Edit HTML and PHP in an editor with syntax highlighting."] = "S&#7917;a &#273;&#7893;i HTML v&#224; PHP v&#7899;i m&#7897;t c&#244;ng c&#7909; s&#7917;a &#273;&#7893;i ho&#224;n ch&#7881;nh.";
$net2ftp_messages["Search for words or phrases"] = "T&#236;m ki&#7871;m t&#7915; ho&#7863;c c&#226;u";
$net2ftp_messages["Filter out files based on the filename, last modification time and filesize."] = "T&#236;m theo t&#234;n t&#7879;p, k&#237;ch th&#432;&#7899;c t&#7879;p ho&#7863;c s&#7921; s&#7917;a &#273;&#7893;i sau c&#249;ng.";
$net2ftp_messages["Calculate size"] = "T&#237;nh to&#225;n k&#237;ch th&#432;&#7899;c";
$net2ftp_messages["Calculate the size of directories and files."] = "T&#237;nh to&#225;n k&#237;ch th&#432;&#7899;c c&#7911;a t&#7879;p ho&#7863;c th&#432; m&#7909;c.";

$net2ftp_messages["FTP server"] = "M&#225;y ch&#7911; FTP";
$net2ftp_messages["Example"] = "V&#237; d&#7909;";
$net2ftp_messages["Port"] = "C&#7893;ng";
$net2ftp_messages["Username"] = "T&#234;n &#273;&#249;ng";
$net2ftp_messages["Password"] = "M&#7853;t kh&#7849;u";
$net2ftp_messages["Anonymous"] = "V&#244; danh";
$net2ftp_messages["Passive mode"] = "Ki&#7875;u th&#7909; &#273;&#7897;ng";
$net2ftp_messages["Initial directory"] = "Th&#432; m&#7909;c t&#7899;i";
$net2ftp_messages["Language"] = "Ng&#244;n ng&#7919;";
$net2ftp_messages["Skin"] = "Giao di&#7879;n";
$net2ftp_messages["FTP mode"] = "Ki&#7875;u FTP";
$net2ftp_messages["Automatic"] = "T&#7921; &#273;&#7897;ng";
$net2ftp_messages["Login"] = "&#272;&#259;ng nh&#7853;p";
$net2ftp_messages["Clear cookies"] = "X&#243;a cookies";
$net2ftp_messages["Admin"] = "Qu&#7843;n tr&#7883;";
$net2ftp_messages["Please enter an FTP server."] = "Xin vui lo`nng nhap mot may chu FTP.";
$net2ftp_messages["Please enter a username."] = "Xin vui lo`nng nhap ten dung.";
$net2ftp_messages["Please enter a password."] = "Xin vui lo`nng nhap mat khau.";

} // end login


// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login_small") {
// -------------------------------------------------------------------------

$net2ftp_messages["Please enter your Administrator username and password."] = "Xin vui l&#242;ng nh&#7853;p t&#234;n d&#249;ng v&#224; m&#7853;t kh&#7849;u c&#7911;a ng&#432;&#7901;i qu&#7843;n tr&#7883;";
$net2ftp_messages["Please enter your username and password for FTP server <b>%1\$s</b>."] = "Please enter your username and password for FTP server <b>%1\$s</b>.";
$net2ftp_messages["Username"] = "T&#234;n &#273;&#249;ng";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Password"] = "M&#7853;t kh&#7849;u";
$net2ftp_messages["Login"] = "&#272;&#259;ng nh&#7853;p";
$net2ftp_messages["Continue"] = "Ti&#7871;p t&#7909;c";

} // end login_small


// -------------------------------------------------------------------------
// Logout module
if ($net2ftp_globals["state"] == "logout") {
// -------------------------------------------------------------------------

// logout.inc.php
$net2ftp_messages["Login page"] = "Trang &#273;&#259;ng nh&#7853;p";

// logout.template.php
$net2ftp_messages["You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>."] = "You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>.";
$net2ftp_messages["Note: other users of this computer could click on the browser's Back button and access the FTP server."] = "Ch&#250; &#253;: m&#7897;t ng&#432;&#7901;i d&#249;ng kh&#225;c s&#7917; d&#7909;ng chi&#7871;c m&#225;y t&#237;nh n&#224;y c&#243; th&#7875; &#7845;n v&#224;o n&#250;t Back tr&#234;n tr&#236;nh duy&#7879;t v&#224; c&#243; th&#7875; tr&#7903; l&#7841;i m&#225;y ch&#7911; FTP.";
$net2ftp_messages["To prevent this, you must close all browser windows."] = "&#272;&#7875;  ch&#7855;c ch&#7855;n b&#7841;n ph&#7843;i &#273;&#243;ng tr&#236;nh duy&#7879;t.";
$net2ftp_messages["Close"] = "&#272;&#243;ng";
$net2ftp_messages["Click here to close this window"] = "Nh&#7845;p v&#224;o &#273;&#226;y &#273;&#7875; &#273;&#243;ng tr&#236;nh duy&#7879;t";

} // end logout


// -------------------------------------------------------------------------
// New directory module
if ($net2ftp_globals["state"] == "newdir") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create new directories"] = "T&#7841;o th&#432; m&#7909;c m&#7899;i";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "Th&#432; m&#7909;c m&#7899;i s&#7869; &#273;&#432;&#7907;c t&#7841;o trong <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "T&#234;n th&#432; m&#7909;c m&#7899;i:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "Th&#432; m&#7909;c <b>%1\$s</b> &#273;&#227; &#273;&#432;&#417;c t&#7841;o th&#224;nh c&#244;ng.";
$net2ftp_messages["Directory <b>%1\$s</b> could not be created."] = "Th&#432; m&#7909;c <b>%1\$s</b> &#273;&#227; kh&#244;ng th&#7875; t&#7841;o.";

} // end newdir


// -------------------------------------------------------------------------
// Raw module
if ($net2ftp_globals["state"] == "raw") {
// -------------------------------------------------------------------------

// /modules/raw/raw.inc.php
$net2ftp_messages["Send arbitrary FTP commands"] = "T&#7921; &#273;&#7897;ng g&#7917;i c&#225;c l&#7879;nh FTP";


// /skins/[skin]/raw1.template.php
$net2ftp_messages["List of commands:"] = "Danh s&#225;ch c&#225;c l&#7879;nh:";
$net2ftp_messages["FTP server response:"] = "M&#225;y ch&#7911; FTP tr&#7843; l&#7901;i:";

} // end raw


// -------------------------------------------------------------------------
// Rename module
if ($net2ftp_globals["state"] == "rename") {
// -------------------------------------------------------------------------
$net2ftp_messages["Rename directories and files"] = "&#272;&#7893;i t&#234;n c&#225;c th&#432; m&#7909;c v&#224; c&#225;c t&#7879;p";
$net2ftp_messages["Old name: "] = "T&#234;n c&#361;: ";
$net2ftp_messages["New name: "] = "T&#234;n m&#7899;i: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "T&#234;n m&#7899;i c&#243; th&#7875; ch&#7913;a d&#7845;u ch&#7845;m. Kh&#244;ng th&#7875; &#273;&#7893;i t&#234;n th&#224;nh <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> &#273;&#227; &#273;&#7893;i t&#234;n th&#224;nh c&#244;ng th&#224;nh <b>%2\$s</b>";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> kh&#244;ng th&#7875; &#273;&#7893;i t&#234;n th&#224;nh <b>%2\$s</b>";

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
$net2ftp_messages["Set all targetdirectories"] = "L&#7853;p t&#7845;t c&#7843; c&#225;c th&#432; m&#7909;c t&#7899;i";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "&#272;&#7875; l&#7853;p m&#7897;t th&#432; m&#7909;c t&#7899;i th&#244;ng th&#432;&#7901;ng, h&#227;y nh&#7853;p th&#432; m&#7909;c t&#7899;i v&#224;o b&#234;n gi&#7899;i v&#224; nh&#7845;p v&#224;o n&#250;t\"L&#7853;p t&#7845;t c&#7843; c&#225;c th&#432; m&#7909;c t&#7899;i\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Ch&#250; &#253;: &#273;&#237;ch &#273;&#7871;n ph&#7843;i t&#7891;n t&#7841;i tr&#432;&#7899;c khi b&#7845;t k&#7923; th&#7913; g&#236; c&#243; th&#7875; &#273;&#432;&#7907;c sao ch&#233;p t&#7899;i n&#243;.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Unzip archive <b>%1\$s</b> to:";
$net2ftp_messages["Target directory:"] = "Th&#432; m&#7909;c &#273;&#7871;n:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "S&#7917; d&#7909;ng t&#234;n th&#432; m&#7909;c (t&#7921; &#273;&#7897;ng t&#7841;o th&#432; m&#7909;c con)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "C&#7853;p nh&#7853;t t&#7879;p";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>C&#7842;NH B&#193;O: CH&#7912;C N&#258;NG N&#192;Y V&#7850;N C&#210;N &#272;ANG &#272;&#431;&#7906;C PH&#193;T TRI&#7874;N TH&#202;M. CH&#7880; N&#202;N S&#7910; D&#7908;NG N&#211; TR&#202;N C&#193;C T&#7878;P TH&#7916; NGHI&#7878;M!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "Nh&#7919;ng l&#7895;i &#273;&#227; bi&#7871;t: - x&#243;a b&#7887; nh&#7919;ng k&#253; t&#7921; nh&#227;n - kh&#244;ng l&#224;m vi&#7879;c t&#7889;t v&#7899;i t&#7879;p l&#7899;n (> 50kB) - kh&#244;ng ki&#7875;m tra &#273;&#432;&#7907;c tr&#234;n c&#225;c t&#7879;p ch&#7913;a &#273;&#7921;ng k&#253; t&#7921; kh&#244;ng chu&#7849;n</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "Ch&#7913;c n&#259;ng n&#224;y cho ph&#233;p b&#7841;n &#273;&#432;a l&#234;n m&#7897;t phi&#234;n b&#7843;n m&#7899;i c&#7911;a t&#7879;p &#273;&#227; l&#7921;a ch&#7885;n, hi&#7875;n th&#7883; nh&#7919;ng c&#225;i &#273;&#227; thay &#273;&#7893;i v&#224; ch&#7845;p nh&#7853;n hay kh&#244;ng ch&#7845;p nh&#7853;n s&#7921; thay &#273;&#7893;i. Tr&#432;&#7899;c khi b&#7845;t k&#7923; th&#7913; g&#236; &#273;&#432;&#417;c l&#432;u, b&#7841;n c&#243; th&#7875; s&#7917;a &#273;&#7893;i c&#225;c t&#7879;p &#273;&#227; k&#7871;t h&#7907;p.";
$net2ftp_messages["Old file:"] = "T&#7879;p c&#361;:";
$net2ftp_messages["New file:"] = "T&#7879;p m&#7899;i:";
$net2ftp_messages["Restrictions:"] = "Nh&#7919;ng gi&#7899;i h&#7841;n:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "K&#237;ch th&#432;&#7899;c l&#7899;n nh&#7845;t c&#7911;a m&#7897;t t&#7879;p gi&#7899;i h&#7841;n b&#7903;i net2ftp t&#7899;i <b>%1\$s kB</b> v&#224; b&#7903;i PHP t&#7915; <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Th&#7901;i gian th&#7921;c hi&#234;n l&#7899;n nh&#7845;t l&#224; <b>%1\$s gi&#226;y</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "Ki&#7875;u truy&#7875;n t&#7843;i c&#7911;a FTP (ASCII hay BINARY) s&#7869; &#273;&#432;&#7907;c x&#225;c &#273;&#7883;nh m&#7897;t c&#225;ch t&#7921; &#273;&#7897;ng";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "N&#7871;u &#273;&#227; t&#7891;n t&#7841;i m&#7897;t t&#7879;p t&#432;&#417;ng t&#7921; tr&#234;n m&#225;y ch&#7911; FTP, n&#243; s&#7869; b&#7883; ghi &#273;&#232; l&#234;n";
$net2ftp_messages["You did not provide any files or archives to upload."] = "B&#7841;n kh&#244;ng c&#243; b&#7845;t k&#7923; t&#7879;p hay l&#432;u tr&#7919; n&#224;o &#273;&#7875; &#273;&#432;a l&#234;n.";
$net2ftp_messages["Unable to delete the new file"] = "Kh&#244;ng th&#7875; xo&#225; t&#7879;p m&#7899;i";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "Xin vui l&#242;ng ch&#7901;...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "L&#7921;a ch&#7885;n nh&#7919;ng &#273;&#432;&#7901;ng th&#7859;ng &#7903; b&#234;n d&#432;&#7899;i, &#273;&#7891;ng &#253; hay t&#7915; ch&#7889;i nh&#7919;ng thay d&#7893;i v&#224; &#7845;n n&#250;t h&#224;nh &#273;&#7897;ng.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "&#272;&#432;a l&#234;n t&#7899;i th&#432; m&#7909;c:";
$net2ftp_messages["Files"] = "C&#225;c t&#7879;p";
$net2ftp_messages["Archives"] = "C&#225;c l&#432;u tr&#7919";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "Nh&#7919;ng t&#7879;p v&#7915;a nh&#7853;p v&#224;o s&#7869; &#273;&#432;&#417;c chuy&#7875;n t&#7899;i m&#225;y ch&#7911; FTP.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "Nh&#7919;ng l&#432;u tr&#7919; n&#224;y s&#7869; &#273;&#432;&#7907;c gi&#7843;i n&#233;n, v&#224; c&#225;c t&#7879;p trong &#273;&#243; s&#7869; &#273;&#432;&#417;c chuy&#7875;n t&#7899;i m&#225;y ch&#7911; FTP.";
$net2ftp_messages["Add another"] = "Th&#234;m v&#224;o c&#225;i kh&#225;c";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "S&#7917; d&#7909;ng t&#234;n th&#432; m&#7909;c (t&#7921; &#273;&#7897;ng t&#7841;o th&#432; m&#7909;c con)";

$net2ftp_messages["Choose a directory"] = "Ch&#7885;n m&#7897;t th&#432; m&#7909;c";
$net2ftp_messages["Please wait..."] = "Xin vui l&#242;ng ch&#7901;...";
$net2ftp_messages["Uploading... please wait..."] = "&#272;ang &#273;&#432;a l&#234;n... xin vui l&#242;ng ch&#7901; trong gi&#226;y l&#225;t......";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "N&#7871;u th&#7901;i gian &#273;&#432;a l&#234;n v&#432;&#7907;t qu&#225; th&#242;i gian quy &#273;&#7883;nh l&#224; <b>%1\$s gi&#226;y<\/b>, b&#7841;n s&#7869; ph&#7843;i th&#7921;c hi&#7879;n l&#7841;i v&#7899;i &#237;t t&#7879;p h&#417;n ho&#7863;c k&#237;ch th&#432;&#7899;c nh&#7887; h&#417;n.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "C&#7917;a s&#7893; n&#224;y s&#7869; t&#7921; &#273;&#7897;ng &#273;&#243;ng l&#7841;i trong m&#7897;t v&#224;i gi&#226;y n&#7919;a.";
$net2ftp_messages["Close window now"] = "&#272;&#243;ng c&#7917;a s&#7893; ngay b&#226;y gi&#7901;";

$net2ftp_messages["Upload files and archives"] = "&#272;&#432;a l&#234;n c&#225;c t&#7879;p v&#224; c&#225;c l&#432;u tr&#7919;";
$net2ftp_messages["Upload results"] = "K&#7871;t qu&#7843; &#273;&#432;a l&#234;n";
$net2ftp_messages["Checking files:"] = "&#272;ang ki&#7875;m tra c&#225;c t&#7879;p:";
$net2ftp_messages["Transferring files to the FTP server:"] = "&#272;ang chuy&#7875;n c&#225;c t&#7879;p t&#7899;i m&#225;y ch&#7911; FTP:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "&#272;ang gi&#7843;i n&#233;n c&#225;c l&#432;u tr&#7919; v&#224; chuy&#7875;n t&#7899;i m&#225;y ch&#7911; FTP:";
$net2ftp_messages["Upload more files and archives"] = "&#272;&#432;a l&#234;n th&#234;m c&#225;c t&#7879;p v&#224; l&#432;u tr&#7919;";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "Nh&#7919;ng gi&#7899;i h&#7841;n:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "K&#237;ch th&#432;&#7899;c l&#7899;n nh&#7845;t c&#7911;a m&#7897;t t&#7879;p gi&#7899;i h&#7841;n b&#7903;i net2ftp t&#7899;i <b>%1\$s kB</b> v&#224; b&#7903;i PHP t&#7915; <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Th&#7901;i gian th&#7921;c hi&#234;n l&#7899;n nh&#7845;t l&#224; <b>%1\$s gi&#226;y</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "Ki&#7875;u truy&#7875;n t&#7843;i c&#7911;a FTP (ASCII hay BINARY) s&#7869; &#273;&#432;&#7907;c x&#225;c &#273;&#7883;nh m&#7897;t c&#225;ch t&#7921; &#273;&#7897;ng";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "N&#7871;u &#273;&#227; t&#7891;n t&#7841;i m&#7897;t t&#7879;p t&#432;&#417;ng t&#7921; tr&#234;n m&#225;y ch&#7911; FTP, n&#243; s&#7869; b&#7883; ghi &#273;&#232; l&#234;n";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "Xem t&#7879;p %1\$s";
$net2ftp_messages["View image %1\$s"] = "Xem &#7843;nh %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "Xem t&#7879;p Macromedia ShockWave Flash movie %1\$s";
$net2ftp_messages["Image"] = "&#7842;nh";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntax highlighting cung c&#7845;p b&#7903;i <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "&#272;&#7875; l&#432;u &#7843;nh, nh&#7845;p chu&#7897;t ph&#7843;i l&#234;n n&#243; v&#224; ch&#7885;n 'Save picture as...'";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "K&#7871;t qu&#7843; Zip";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "L&#432;u t&#7879;p n&#233;n tr&#234;n m&#225;y ch&#7911; FTP t&#7841;i:";
$net2ftp_messages["Email the zip file in attachment to:"] = "G&#7917;i t&#7879;p n&#233;n n&#224;y t&#7899;i:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "H&#227;y ch&#250; &#253; l&#224; email kh&#244;ng n&#7863;c danh: &#273;&#7883;a ch&#7881; IP c&#7911;a b&#7841;n c&#361;ng nh&#432; th&#7901;i gian g&#7917;i s&#7869; &#273;&#432;&#7907;c th&#234;m v&#224;o email.";
$net2ftp_messages["Some additional comments to add in the email:"] = "Th&#234;m tin nh&#7855;n v&#224;o email:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "B&#7841;n &#273;&#227; kh&#244;ng nh&#7853;p t&#234;n v&#224;o cho t&#7879;p n&#233;n. H&#227;y tr&#7903; l&#7841;i v&#224; &#273;i&#7873;n v&#224;o m&#7897;t c&#225;i t&#234;n.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "&#272;&#7883;a ch&#7881; email m&#224; b&#7841;n v&#7915;a nh&#7853;p v&#224;o (%1\$s) kh&#244;ng &#273;&#250;ng.<br />Xin vui l&#242;ng nh&#7853;p l&#7841;i theo &#273;&#7883;nh d&#7841;ng: t&#234;nd&#249;ng@t&#234;nmi&#7873;n.ect</b>";

} // end zip

?>