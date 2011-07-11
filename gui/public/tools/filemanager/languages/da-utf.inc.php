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
$net2ftp_messages["en"] = "da";

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

$net2ftp_messages["Connecting to the FTP server"] = "Forbinder til FTP serveren";
$net2ftp_messages["Logging into the FTP server"] = "Logger p&aring; FTP serveren";
$net2ftp_messages["Setting the passive mode"] = "S&aelig;tter passiv modus";
$net2ftp_messages["Getting the FTP system type"] = "Henter FTP system type";
$net2ftp_messages["Changing the directory"] = "&AElig;ndrer mappevisning";
$net2ftp_messages["Getting the current directory"] = "Henter aktuelle mappevisning";
$net2ftp_messages["Getting the list of directories and files"] = "Henter liste over mapper og filer";
$net2ftp_messages["Parsing the list of directories and files"] = "Indl&aelig;ser liste over mapper og filer";
$net2ftp_messages["Logging out of the FTP server"] = "Logger af FTP serveren";
$net2ftp_messages["Getting the list of directories and files"] = "Henter liste over mapper og filer";
$net2ftp_messages["Printing the list of directories and files"] = "Viser liste over mapper og filer";
$net2ftp_messages["Processing the entries"] = "Bearbejder opgaver";
$net2ftp_messages["Processing entry %1\$s"] = "Bearbejder opgave %1\$s";
$net2ftp_messages["Checking files"] = "Kontrollerer filer";
$net2ftp_messages["Transferring files to the FTP server"] = "Overf&oslash;rer filer til FTP serveren";
$net2ftp_messages["Decompressing archives and transferring files"] = "Udpakker arkiver og overf&oslash;rer filer";
$net2ftp_messages["Searching the files..."] = "S&oslash;ger i filer...";
$net2ftp_messages["Uploading new file"] = "Uploader ny fil";
$net2ftp_messages["Reading the file"] = "L&aelig;ser fil";
$net2ftp_messages["Parsing the file"] = "Indl&aelig;ser fil";
$net2ftp_messages["Reading the new file"] = "L&aelig;ser den nye fil";
$net2ftp_messages["Reading the old file"] = "L&aelig;ser den gamle fil";
$net2ftp_messages["Comparing the 2 files"] = "Sammenligner filer";
$net2ftp_messages["Printing the comparison"] = "Viser sammenligning";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "Sender FTP kommando %1\$s ud af %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Henter %1\$s ud af %2\$s arkiver fra FTP serveren";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Opretter midlertidig mappe p&aring; FTP serveren";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Retter tilladelser for den midlertidige mappe";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Kopierer net2ftp installer scriptet til FTP serveren";
$net2ftp_messages["Script finished in %1\$s seconds"] = "Script udf&oslash;rt p&aring; %1\$s sekunder";
$net2ftp_messages["Script halted"] = "Script afbrudt";

// Used on various screens
$net2ftp_messages["Please wait..."] = "Vent venligst...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Uventet strengtilstand: %1\$s. Afbryder.";
$net2ftp_messages["This beta function is not activated on this server."] = "Denne beta funktion er ikke aktiveret p&aring; denne server.";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "Denne funktion er blevet deaktiveret af websidens administrator.";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "Mappen <b>%1\$s</b> findes enten ikke, eller kunne ikke hentes, mappen <b>%2\$s</b> vises i stedet for.";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "Rodmappen <b>%1\$s</b> findes enten ikke eller kunne ikke hentes.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "Mappen <b>%1\$s</b> kunne ikke hentes - du har m&aring;ske ikke tilstr&aelig;kkelige rettigheder for at se denne mappe, eller ogs&aring; findes mappen ikke.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Mapper og filer der indeholder blokkerede n&oslash;gleord kan ikke h&aring;ndteres i net2ftp. Dette er for at undg&aring; misbrug med bl.a. Paypal eller Ebay.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Filer som er for store kan ikke downloades, uploades, kopieres, flyttes, gennems&oslash;ges, komprimeres, udpakkes, vises eller redigeres. De kan kun omd&oslash;bes, slettes eller du kan rette filens chmod-rettigheder.";
$net2ftp_messages["Execute %1\$s in a new window"] = "K&oslash;r %1\$s i et nyt vindue";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "V&aelig;lg mindst en mappe eller fil!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "FTP serveren <b>%1\$s</b> er ikke p&aring; listen over tilladte FTP servere.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "FTP serveren <b>%1\$s</b> er p&aring; listen over blokerede FTP servere.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "FTP serverporten: %1\$s kan ikke benyttes.";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "Din IP adresse (%1\$s) er ikke p&aring; listen over tilladte IP adresser.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "Din IP adresse (%1\$s) er p&aring; listen over blokerede IP adresser.";

// isAuthorizedDirectory()
$net2ftp_messages["Table net2ftp_users contains duplicate rows."] = "Tabellen net2ftp_users Indeholder dubletter.";

// logAccess(), logLogin(), logError()
$net2ftp_messages["Unable to execute the SQL query."] = "Kan ikke udf&oslash;re SQL foresp&oslash;rgsel.";
$net2ftp_messages["Unable to open the system log."] = "Kan ikke &aring;bne system loggen.";
$net2ftp_messages["Unable to write a message to the system log."] = "Kan ikke skrive i system loggen.";

// checkAdminUsernamePassword()
$net2ftp_messages["You did not enter your Administrator username or password."] = "Du fik ikke indtastet dit brugernavn eller din adgangskode.";
$net2ftp_messages["Wrong username or password. Please try again."] = "Forkert brugernavn eller adgangskode. Pr&oslash;v igen.";


// -------------------------------------------------------------------------
// /includes/consumption.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to determine your IP address."] = "Kan ikke afg&oslash;re din IP adresses ophav.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "Tabellen net2ftp_log_consumption_ipaddress indeholder dubletter.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "Tabellen net2ftp_log_consumption_ftpserver indeholder dubletter.";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "Variablen  <b>consumption_ipaddress_datatransfer</b> er ikke nummerisk.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "Tabellen net2ftp_log_consumption_ipaddress kunne ikke opdateres.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "Tabellen net2ftp_log_consumption_ipaddress indeholder dubletter.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "Tabellen net2ftp_log_consumption_ftpserver kunne ikke opdateres.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "Tabellen net2ftp_log_consumption_ftpserver indeholder dubletter.";
$net2ftp_messages["Table net2ftp_log_access could not be updated."] = "Tabellen net2ftp_log_access kunne ikke opdateres.";
$net2ftp_messages["Table net2ftp_log_access contains duplicate entries."] = "Tabellen net2ftp_log_access indeholder dubletter.";


// -------------------------------------------------------------------------
// /includes/database.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "Kan ikke f&aring; forbindelse til MySQL databasen. Kontroll&eacute;r dine MySQL indstillinger i net2ftp's konfigurationsfil settings.inc.php.";
$net2ftp_messages["Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "Kan ikke v&aelig;lge MySQL databasen. Kontroll&eacute;r dine MySQL indstillinger i net2ftp's konfigurationsfil settings.inc.php.";


// -------------------------------------------------------------------------
// /includes/errorhandling.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["An error has occured"] = "En fejl er opst&aring;et";
$net2ftp_messages["Go back"] = "Tilbage";
$net2ftp_messages["Go to the login page"] = "G&aring; til login siden";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "PHPs <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP modul</a> er ikke installeret.<br /><br /> Denne websides administrator skal installere dette FTP modul. Instuktioner til installation kan findes p&aring; <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Kan ikke f&aring; forbindelse til FTP serveren <b>%1\$s</b> via port <b>%2\$s</b>.<br /><br />Kontroller at dette er den rigtige adresse til FTP serveren. Denne er ofte forskellig fra HTTP (web) serveren. Kontakt din udbyders helpdesk eller administrator for yderligere hj&aelig;lp.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Kan ikke logge p&aring; FTP serveren <b>%1\$s</b> med brugernavnet <b>%2\$s</b>.<br /><br />Kontroller om brugernavn og adgangskode er korrekt indtastet. Kontakt din udbyders helpdesk eller administrator for yderligere hj&aelig;lp.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "Kan ikke skifte til passiv modus p&aring; FTP serveren <b>%1\$s</b>.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Kan ikke skabe forbindelse til FTP serveren <b>%1\$s</b> via port <b>%2\$s</b>.<br /><br />Kontroll&eacute;r adressen for FTP serveren? Denne er ofte forskellig fra HTTP (web) serveren. Kontakt din udbyders helpdesk eller administrator for yderligere hj&aelig;lp.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Kan ikke logge p&aring; FTP serveren <b>%1\$s</b> med brugernavnet <b>%2\$s</b>.<br /><br />Kontroll&eacute;r at dette er det rigtige brugernavn og adgangskode for denne server. Kontakt din udbyders helpdesk eller administrator for yderligere hj&aelig;lp.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "Kan ikke skifte til passiv modus p&aring; FTP serveren <b>%1\$s</b>.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "Kan ikke omd&oslash;be mappen eller filen <b>%1\$s</b> til <b>%2\$s</b>";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "Kan ikke udf&oslash;re site command <b>%1\$s</b>. Bem&aelig;rk at CHMOD kommandoen kun er tilg&aelig;ngelig p&aring; Unix FTP servere, ikke p&aring; Windows FTP servere.";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "Mappen <b>%1\$s</b> blev chmodded til <b>%2\$s</b>";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "Processing entries within directory <b>%1\$s</b>:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "Filen <b>%1\$s</b> blev chmodded til <b>%2\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "Alle de valgte mapper og filer er bearbejdet.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "Kan ikke slette mappen <b>%1\$s</b>";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Kan ikke slette filen <b>%1\$s</b>";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "Kan ikke oprette mappen <b>%1\$s</b>";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "Kan ikke oprette den midlertidige fil";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "Kan ikke hente filen <b>%1\$s</b> fra FTP serveren og gemme den som den midlertidig fil <b>%2\$s</b>.<br />Kontroll&eacute;r rettighederne for mappen %3\$s.<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Kan ikke &aring;bne den midlertidige fil. Kontroll&eacute;r rettighederne for mappen %1\$s.";
$net2ftp_messages["Unable to read the temporary file"] = "Kan ikke l&aelig;se den midlertidige fil";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Kan ikke lukke den midlertidige fils handle";
$net2ftp_messages["Unable to delete the temporary file"] = "Kan ikke slette den midlertidige fil";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "Kan ikke oprette den midlertidige fil. Kontroll&eacute;r rettighederne for mappen %1\$s.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Kan ikke &aring;bne den midlertidige fil. Kontroll&eacute;r rettighederne for mappen %1\$s.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "Kan ikke skrive strengen til den midlertidige fil <b>%1\$s</b>.<br />Kontroll&eacute;r rettighederne for mappen %2\$s.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Kan ikke lukke den midlertidige fils handle";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "Kan ikke sende filen <b>%1\$s</b> til FTP serveren.<br />Du har muligvis ikke skriverettigheder for denne mappe.";
$net2ftp_messages["Unable to delete the temporary file"] = "Kan ikke slette den midlertidige fil";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "Behandler mappen <b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "Destinationen <b>%1\$s</b> er den samme mappe eller en undermappe til den oprindelige mappe <b>%2\$s</b>, denne mappe springes over";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "Mappen <b>%1\$s</b> indeholder et blokeret n&oslash;gleord. Denne mappe springes over";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "Mappen <b>%1\$s</b> indeholder et blokeret n&oslash;gleord. Afbryder flytning";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "Kan ikke oprette undermappen <b>%1\$s</b>. Den findes m&aring;ske allerede. Forts&aelig;tter kopiering/flytning...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Oprettede undermappen <b>%1\$s</b>";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "Mappen <b>%1\$s</b> kunne ikke v&aelig;lges. Denne mappe springes over";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "Kan ikke slette undermappen <b>%1\$s</b> - den er m&aring;ske ikke tom";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "Slettede undermappen <b>%1\$s</b>";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "Behandling af mappen <b>%1\$s</b> f&aelig;rdig";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "Destinationen for filen <b>%1\$s</b> er den samme som den oprindelige mappe. Denne fil springes over";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "Filen <b>%1\$s</b> indeholder et blokeret n&oslash;gleord. Denne fil springes over";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "Filen <b>%1\$s</b> indeholder et blokeret n&oslash;gleord. Afbryder flytning";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "Filen <b>%1\$s</b> er for stor til at blive kopieret. Denne fil springes over";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "Filen <b>%1\$s</b> er for stor til at blive flyttet. Afbryder flytning";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "Kan ikke kopiere filen <b>%1\$s</b>";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Kopierede filen <b>%1\$s</b>";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Kan ikke flytte filen <b>%1\$s</b>. Afbryder flytning";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "Flyttede filen <b>%1\$s</b>";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Kan ikke slette filen <b>%1\$s</b>";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "Slettede filen <b>%1\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "Alle de valgte mapper og filer er bearbejdet.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "Kan ikke kopiere den fjerne fil <b>%1\$s</b> til den lokale fil via FTP modus <b>%2\$s</b>";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "Unable to delete file <b>%1\$s</b>";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "Filen er for stor til at blive sendt";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "Daglig gr&aelig;nse n&aring;et: filen <b>%1\$s</b> bliver ikke overf&oslash;rt";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "Kan ikke kopiere den lokale fil til den fjerne fil <b>%1\$s</b> via FTP modus <b>%2\$s</b>";
$net2ftp_messages["Unable to delete the local file"] = "Kan ikke slette den lokale fil";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "Kan ikke slette den midlertidige fil";
$net2ftp_messages["Unable to send the file to the browser"] = "Kan ikke sende filen til browseren";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "Kan ikke oprette den midlertidige fil";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "Arkivet er gemt p&aring; FTP serveren med navnet <b>%1\$s</b>";
$net2ftp_messages["Requested files"] = "Requested files";

$net2ftp_messages["Dear,"] = "K&aelig;re,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "Nogen har bedt om at de vedh&aelig;ftede filer i denne mail skulle sendes til denne e-mail adresse (%1\$s).";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "Hvis du ikke kender til dette eller hvis du ikke har tillid til afsenderen s&aring; slet denne e-mail og lad v&aelig;re med at &aring;bne det vedh&aelig;ftede arkiv.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "Bem&aelig;rk: Hvis du ikke &aring;bner arkiverne s&aring; kan filerne i arkivet ikke skade din computer.";
$net2ftp_messages["Information about the sender: "] = "Information om afsenderen: ";
$net2ftp_messages["IP address: "] = "IP addresse: ";
$net2ftp_messages["Time of sending: "] = "Afsendelsestidspunkt: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "Sendt via net2ftp programmet installeret p&aring; dette website: ";
$net2ftp_messages["Webmaster's email: "] = "Webmasterens e-mail: ";
$net2ftp_messages["Message of the sender: "] = "Afsenderens meddelelse: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp er gratis software der er udgivet under GNU/GPL licensen. For yderligere information, g&aring; til http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "Arkivet er blevet sendt til <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "Filen <b>%1\$s</b> er for stor. Denne fil bliver ikke uploadet.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "Filen <b>%1\$s</b> indeholder blokerede n&oslash;gleord. Denne fil bliver ikke uploadet.";
$net2ftp_messages["Could not generate a temporary file."] = "Kan ikke oprette en midlertidig fil.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "Filen <b>%1\$s</b> kunne ikke flyttes";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "Filen <b>%1\$s</b> er OK";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "Kan ikke flytte den uploadede fil til den midlertidige mappe.<br /><br />Administratoren af denne webside skal s&aelig;tte rettighederne for /temp mappen i net2ftp til<b>chmod 777</b>.";
$net2ftp_messages["You did not provide any file to upload."] = "Du skal v&aelig;lge de filer der skal uploades.";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "Filen <b>%1\$s</b> kunne ikke overf&oslash;res til FTP serveren";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "Filen <b>%1\$s</b> blev overf&oslash;rt til FTP servern via FTP modus <b>%2\$s</b>";
$net2ftp_messages["Transferring files to the FTP server"] = "Overf&oslash;rer filer til FTP serveren";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "Behandler arkiv nr. %1\$s: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "Arkivet <b>%1\$s</b> blev ikke behandlet fordi filtypen ikke blev genkendt. Kun zip, tar, tgz og gz arkiver underst&oslash;ttes i &oslash;jeblikket.";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Kan ikke udpakke filer og mapper fra arkivet";
$net2ftp_messages["Archive contains filenames with ../ or ..\\ - aborting the extraction"] = "Arkiv indeholder filnavne med ../ eller ..\\ - afbryder udpakning";
$net2ftp_messages["Created directory %1\$s"] = "Oprettede mappen %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "Kan ikke oprette mappen %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "Kopierede fil %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "Kan ikke kopiere filen %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "Kan ikke slette den midlertidige mappe";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Kan ikke slette den midlertidige fil %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "Kan ikke udf&oslash;re site command <b>%1\$s</b>";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "Handlingen blev afbrudt";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "Handlingen du var i gang med at udf&oslash;re p&aring; net2ftp tog l&aelig;ngere tid end de tilladte %1\$s sekunder og blev derfor stoppet.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "Denne tidsbegr&aelig;nsning sikrer at fair forbrug af serverens ressourcer for alle brugere.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "Pr&oslash;v at dele din handling op i mindre handlinger: begr&aelig;ns dit udvalg af filer og undg&aring; de st&oslash;rste filer.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "Hvis du har behov for at net2ftp skal udf&oslash;re handlinger der tager l&aelig;ngere tid burde du overveje at installere net2ftp p&aring; din egen server.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "Du har ikke angivet noget tekst der skal sendes via e-mail!";
$net2ftp_messages["You did not supply a From address."] = "Du har ikke angivet en Fra adresse.";
$net2ftp_messages["You did not supply a To address."] = "Du har ikke angivet en Til adresse.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "Tekniske problemer forhindrede afsendelsen af e-mailen <b>%1\$s</b>.";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "Indtast dit brugernavn og adgangskode til FTP serveren ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Du fik ikke udfyldt login informationerne i popup vinduet.<br />Klik p&aring; \"G&aring; til login siden\" herunder.";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "Adgang til net2ftp administrationspanelet er deaktiveret fordi ingen adgangskode er sat i filen settings.inc.php. Indtast en adgangskode i den fil og opdater denne side.";
$net2ftp_messages["Please enter your Admin username and password"] = "Indtast dit administrationsbrugernavn og adgangskode"; 
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Du fik ikke udfyldt login informationerne i popup vinduet.<br />Klik p&aring; \"G&aring; til login siden\" herunder.";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "Forkert brugernavn eller adgangskode til net2ftp administrationspanelet. Brugernavn og adgangskode kan angives i filen settings.inc.php.";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "Bl&aring;t";
$net2ftp_messages["Grey"] = "Gr&aring;t";
$net2ftp_messages["Black"] = "Sort";
$net2ftp_messages["Yellow"] = "Gult";
$net2ftp_messages["Pastel"] = "Pastel";

// getMime()
$net2ftp_messages["Directory"] = "Mappe";
$net2ftp_messages["Symlink"] = "Symlink";
$net2ftp_messages["ASP script"] = "ASP script";
$net2ftp_messages["Cascading Style Sheet"] = "Cascading Style Sheet";
$net2ftp_messages["HTML file"] = "HTML fil";
$net2ftp_messages["Java source file"] = "Java source fil";
$net2ftp_messages["JavaScript file"] = "JavaScript fil";
$net2ftp_messages["PHP Source"] = "PHP Source";
$net2ftp_messages["PHP script"] = "PHP script";
$net2ftp_messages["Text file"] = "Tekstfil";
$net2ftp_messages["Bitmap file"] = "Bitmap fil";
$net2ftp_messages["GIF file"] = "GIF fil";
$net2ftp_messages["JPEG file"] = "JPEG fil";
$net2ftp_messages["PNG file"] = "PNG fil";
$net2ftp_messages["TIF file"] = "TIF fil";
$net2ftp_messages["GIMP file"] = "GIMP fil";
$net2ftp_messages["Executable"] = "Program";
$net2ftp_messages["Shell script"] = "Shell script";
$net2ftp_messages["MS Office - Word document"] = "MS Office - Word dokument";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - Excel regneark";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - PowerPoint pr&aelig;sentation";
$net2ftp_messages["MS Office - Access database"] = "MS Office - Access database";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - Visio tegning";
$net2ftp_messages["MS Office - Project file"] = "MS Office - Project fil";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - Writer 6.0 dokument";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - Writer 6.0 skabelon";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - Calc 6.0 regneark";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - Calc 6.0 skabelon";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - Draw 6.0 dokument";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - Draw 6.0 skabelon";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - Impress 6.0 pr&aelig;sentation";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - Impress 6.0 skabelon";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - Writer 6.0 globalt dokument";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - Math 6.0 dokument";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - StarWriter 5.x dokument";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - StarWriter 5.x globalt dokument";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - StarCalc 5.x regneark";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - StarDraw 5.x dokument";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - StarImpress 5.x pr&aelig;sentation";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - StarImpress pakket 5.x fil";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - StarMath 5.x dokument";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - StarChart 5.x dokument";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - StarMail 5.x mail fil";
$net2ftp_messages["Adobe Acrobat document"] = "Adobe Acrobat dokument";
$net2ftp_messages["ARC archive"] = "ARC arkiv";
$net2ftp_messages["ARJ archive"] = "ARJ arkiv";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "GZ arkiv";
$net2ftp_messages["TAR archive"] = "TAR arkiv";
$net2ftp_messages["Zip archive"] = "Zip arkiv";
$net2ftp_messages["MOV movie file"] = "MOV film";
$net2ftp_messages["MPEG movie file"] = "MPEG film";
$net2ftp_messages["Real movie file"] = "Real film";
$net2ftp_messages["Quicktime movie file"] = "Quicktime film";
$net2ftp_messages["Shockwave flash file"] = "Shockwave flash fil";
$net2ftp_messages["Shockwave file"] = "Shockwave fil";
$net2ftp_messages["WAV sound file"] = "WAV lydfil";
$net2ftp_messages["Font file"] = "Font fil";
$net2ftp_messages["%1\$s File"] = "%1\$s Fil";
$net2ftp_messages["File"] = "Fil";

// getAction()
$net2ftp_messages["Back"] = "Tilbage";
$net2ftp_messages["Submit"] = "Udf&oslash;r";
$net2ftp_messages["Refresh"] = "Opdater";
$net2ftp_messages["Details"] = "Detalier";
$net2ftp_messages["Icons"] = "Ikoner";
$net2ftp_messages["List"] = "Liste";
$net2ftp_messages["Logout"] = "Log ud";
$net2ftp_messages["Help"] = "Hj&aelig;lp";
$net2ftp_messages["Bookmark"] = "Gem som favorit/bogm&aelig;rke";
$net2ftp_messages["Save"] = "Gem";
$net2ftp_messages["Default"] = "Standard";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Hj&aelig;lp";
$net2ftp_messages["Forums"] = "Forum";
$net2ftp_messages["License"] = "Licens";
$net2ftp_messages["Powered by"] = "Styres via";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "Du sendes videre til net2ftp forummet. Dette forum er kun for net2ftp relatede emner - det er IKKE for generelle sp&oslash;rgsm&aring;l om webhosting.";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "Administrationsfunktioner";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "Version information";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "Denne version af net2ftp er opdateret.";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "Information om den nyeste version kunne ikke hentes fra net2ftps server. Kontroll&eacute;r sikkerhedsindstillingerne for din browser da disse kan forhindre indl&aelig;sningen fra net2ftp.com serveren.";
$net2ftp_messages["Logging"] = "Logger";
$net2ftp_messages["Date from:"] = "Fra dato:";
$net2ftp_messages["to:"] = "til:";
$net2ftp_messages["Empty logs"] = "T&oslash;m logs";
$net2ftp_messages["View logs"] = "Se logs";
$net2ftp_messages["Go"] = "Udf&oslash;r";
$net2ftp_messages["Setup MySQL tables"] = "Ops&aelig;t MySQL tabeller";
$net2ftp_messages["Create the MySQL database tables"] = "Opret MySQL databasetabellerne";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "Administrationsfunktioner";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "Handle for filen %1\$s kunne ikke &aring;bnes.";
$net2ftp_messages["The file %1\$s could not be opened."] = "Filen %1\$s kunne ikke &aring;bnes.";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "Handle for filen %1\$s kunne ikke lukkes.";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "Forbindelse til serveren <b>%1\$s</b> kunne ikke etableres. Kontroll&eacute;r de angivne oplysninger for databasen.";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "Kan ikke finde databasen <b>%1\$s</b>.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "SQL foresp&oslash;rgslen nr. <b>%1\$s</b> kunne ikke udf&oslash;res.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "SQL foresp&oslash;rgslen nr. <b>%1\$s</b> er udf&oslash;rt.";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "Indtast dine MySQL indstillinger:";
$net2ftp_messages["MySQL username"] = "MySQL brugernavn";
$net2ftp_messages["MySQL password"] = "MySQL adgangskode";
$net2ftp_messages["MySQL database"] = "MySQL database";
$net2ftp_messages["MySQL server"] = "MySQL server";
$net2ftp_messages["This SQL query is going to be executed:"] = "Denne SQL foresp&oslash;rgsel bliver k&oslash;rt:";
$net2ftp_messages["Execute"] = "K&oslash;r";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "Anvendte indstillinger:";
$net2ftp_messages["MySQL password length"] = "L&aelig;ngde af MySQL adgangskode";
$net2ftp_messages["Results:"] = "Resultater:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "Administrationsfunktioner";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "Kan ikke udf&oslash;re SQL foresp&oslash;rgslen <b>%1\$s</b>.";
$net2ftp_messages["No data"] = "Ingen data";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "Administrationsfunktioner";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "Tabellen <b>%1\$s</b> er t&oslash;mt.";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "Tabellen <b>%1\$s</b> kunne ikke t&oslash;mmes.";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "Tabellen <b>%1\$s</b> er optimeret.";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "Tabellen <b>%1\$s</b> kunne ikke optimeres.";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "Avancerede funktioner";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "Udf&oslash;r";
$net2ftp_messages["Disabled"] = "Deaktiveret";
$net2ftp_messages["Advanced FTP functions"] = "Avancerede FTP funktioner";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "Send arbitr&aelig;re FTP kommandoer til FTP serveren";
$net2ftp_messages["This function is available on PHP 5 only"] = "Denne funktion er kun tilg&aelig;ngelig i PHP 5 eller nyere";
$net2ftp_messages["Troubleshooting functions"] = "Fejls&oslash;gningsfunktioner";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "Fejls&oslash;g net2ftp p&aring; denne server";
$net2ftp_messages["Troubleshoot an FTP server"] = "Fejs&oslash;g en FTP server";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Test net2ftp udskriftsregler";
$net2ftp_messages["Translation functions"] = "Overs&aelig;ttelsesfunktioner";
$net2ftp_messages["Introduction to the translation functions"] = "Introduktion til overs&aelig;ttelsesfunktionerne";
$net2ftp_messages["Extract messages to translate from code files"] = "Pr&aelig;cise meddelelse der skal overs&aelig;ttes fra kode filen";
$net2ftp_messages["Check if there are new or obsolete messages"] = "Unders&oslash;g om der er nye eller overfl&oslash;dige meddelelser";

$net2ftp_messages["Beta functions"] = "Beta funktioner";
$net2ftp_messages["Send a site command to the FTP server"] = "Send et site command til FTP serveren";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: sikre mapper vha. adgangskoder, lav egne fejlsider";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: udf&oslash;r en SQL foresp&oslash;rgsel";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "Site command funktionerne er ikke tilg&aelig;ngelige p&aring; denne webserver.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "Apache funktionerne er ikke tilg&aelig;ngelige p&aring; denne webserver.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "MySQL funktionerne er ikke tilg&aelig;ngelige p&aring; denne webserver.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "Uventet state2 streng. Afslutter.";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "Fejs&oslash;g en FTP server";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "Forbindelsesindstillinger:";
$net2ftp_messages["FTP server"] = "FTP server";
$net2ftp_messages["FTP server port"] = "FTP server port";
$net2ftp_messages["Username"] = "Brugernavn";
$net2ftp_messages["Password"] = "Adgangskode";
$net2ftp_messages["Password length"] = "L&aelig;ngde p&aring; adgangskode";
$net2ftp_messages["Passive mode"] = "Passiv modus";
$net2ftp_messages["Directory"] = "Mappe";
$net2ftp_messages["Printing the result"] = "Udskriver resultatet";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "Forbinder til FTP serveren: ";
$net2ftp_messages["Logging into the FTP server: "] = "Logger p&aring; FTP serveren: ";
$net2ftp_messages["Setting the passive mode: "] = "S&aelig;tter passiv modus: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Henter FTP server system type: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Skifter til mappen %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Mappen fra FTP serveren er: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Henter r&aring; data om mapper og filer: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Pr&oslash;ver p&aring;ny at hente r&aring; data om mapper og filer: ";
$net2ftp_messages["Closing the connection: "] = "Lukker forbindelsen: ";
$net2ftp_messages["Raw list of directories and files:"] = "R&aring; data over mapper og filer:";
$net2ftp_messages["Parsed list of directories and files:"] = "Udskrevet liste over mapper og filer:";

$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "ej OK";

} // end advanced_ftpserver


// -------------------------------------------------------------------------
// Advanced_parsing module
if ($net2ftp_globals["state"] == "advanced_parsing") {
// -------------------------------------------------------------------------

$net2ftp_messages["Test the net2ftp list parsing rules"] = "Test net2ftp udskriftsregler";
$net2ftp_messages["Sample input"] = "Testinput";
$net2ftp_messages["Parsed output"] = "Parsed output";

} // end advanced_parsing


// -------------------------------------------------------------------------
// Advanced_webserver module
if ($net2ftp_globals["state"] == "advanced_webserver") {
// -------------------------------------------------------------------------

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "Test din net2ftp installation";
$net2ftp_messages["Printing the result"] = "Udskriver resultatet";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "Kontrollerer om PHP-FTP modulet er installeret: ";
$net2ftp_messages["yes"] = "Ja";
$net2ftp_messages["no - please install it!"] = "nej - installer dette modul!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "Kontrollerer mappetilladelser p&aring; webserveren: en lille fil bliver oprettet i /temp mappen og derefter slettet.";
$net2ftp_messages["Creating filename: "] = "Opretter filnavn: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "OK. Filnavn: %1\$s";
$net2ftp_messages["not OK"] = "ej OK";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "ej OK. Kontroller tilladelserne for mappen %1\$s";
$net2ftp_messages["Opening the file in write mode: "] = "&Aring;bner filen i skrivningsmodus: ";
$net2ftp_messages["Writing some text to the file: "] = "Skriver noget tekst i filen: ";
$net2ftp_messages["Closing the file: "] = "Lukker filen: ";
$net2ftp_messages["Deleting the file: "] = "Sletter filen: ";

$net2ftp_messages["Testing the FTP functions"] = "Tester FTP funktionerne";
$net2ftp_messages["Connecting to a test FTP server: "] = "Forbinder til en test FTP server: ";
$net2ftp_messages["Connecting to the FTP server: "] = "Forbinder til FTP serveren: ";
$net2ftp_messages["Logging into the FTP server: "] = "Logger p&aring; FTP serveren: ";
$net2ftp_messages["Setting the passive mode: "] = "S&aelig;tter passiv modus: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Henter FTP server system type: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Skifter til mappen %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Mappen fra FTP serveren er: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Henter r&aring; data om mapper og filer: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Pr&oslash;ver p&aring;ny at hente r&aring; data om mapper og filer: ";
$net2ftp_messages["Closing the connection: "] = "Lukker forbindelsen: ";
$net2ftp_messages["Raw list of directories and files:"] = "R&aring; data over mapper og filer:";
$net2ftp_messages["Parsed list of directories and files:"] = "Udskrevet liste over mapper og filer:";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "ej OK";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "Tilf&oslash;j dette link til dine favoritter:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: h&oslash;jreklik p&aring; linket og v&aelig;lg \"F&oslash;j til favoritter...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: h&oslash;jreklik p&aring; linket og v&aelig;lg \"Bogm&aelig;rk dette link...\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "Bem&aelig;rk: N&aring;r du benytter dette bogm&aelig;rke bliver du spurgt om dit brugernavn og adgangskode";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "V&aelig;lg en mappe";
$net2ftp_messages["Please wait..."] = "Vent venligst...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "Mapper der indeholder tegnet \' kan ikke vises korrekt. De kan kun slettes. G&aring; tilbage og v&aelig;lg en anden mappe.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "Daglig datagr&aelig;nse n&aring;et: Du kan ikke overf&oslash;re mere data i dag";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "For at h&aring;ndh&aelig;ve et fair forbrug af vores webserver er der sat begr&aelig;nsninger p&aring; hvor meget data hver bruger kan overf&oslash;re pr. dag. N&aring;r gr&aelig;nsen er n&aring;et kan du stadig bruge net2ftp, men du kan ikke overf&oslash;re data til eller fra serveren.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "Har du behov for ubegr&aelig;nset forbrug s&aring; kan du installere net2ftp p&aring; din egen server.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "Ny mappe";
$net2ftp_messages["New file"] = "Ny fil";
$net2ftp_messages["HTML templates"] = "HTML skabeloner";
$net2ftp_messages["Upload"] = "Upload";
$net2ftp_messages["Java Upload"] = "Upload via Java";
$net2ftp_messages["Flash Upload"] = "Upload via Flash";
$net2ftp_messages["Install"] = "net2ftp Installer";
$net2ftp_messages["Advanced"] = "Avanceret";
$net2ftp_messages["Copy"] = "Kopi&eacute;r";
$net2ftp_messages["Move"] = "Flyt";
$net2ftp_messages["Delete"] = "Slet";
$net2ftp_messages["Rename"] = "Omd&oslash;b";
$net2ftp_messages["Chmod"] = "Rettigheder";
$net2ftp_messages["Download"] = "Download";
$net2ftp_messages["Unzip"] = "Udpak";
$net2ftp_messages["Zip"] = "Komprim&eacute;r";
$net2ftp_messages["Size"] = "St&oslash;rrelse";
$net2ftp_messages["Search"] = "S&oslash;g";
$net2ftp_messages["Go to the parent directory"] = "G&aring; til &oslash;vre mappe";
$net2ftp_messages["Go"] = "Udf&oslash;r";
$net2ftp_messages["Transform selected entries: "] = "Bearbejdt valgte: ";
$net2ftp_messages["Transform selected entry: "] = "Bearbejdt den valgte: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "Lav en undermappe i mappen: %1\$s";
$net2ftp_messages["Create a new file in directory %1\$s"] = "Lav en ny fil i mappen: %1\$s";
$net2ftp_messages["Create a website easily using ready-made templates"] = "Lav hurtigt en webside via eksisterende skabeloner";
$net2ftp_messages["Upload new files in directory %1\$s"] = "Upload nye filer til mappen: %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Upload mapper og filer via et Java applet";
$net2ftp_messages["Upload files using a Flash applet"] = "Upload filer via et Flash applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Install&eacute;r software pakker (kr&aelig;ver PHP installeret p&aring; denne server)";
$net2ftp_messages["Go to the advanced functions"] = "G&aring; til de udvidede indstillinger";
$net2ftp_messages["Copy the selected entries"] = "Kopi&eacute;r valgte";
$net2ftp_messages["Move the selected entries"] = "Flyt valgte";
$net2ftp_messages["Delete the selected entries"] = "Slet valgte";
$net2ftp_messages["Rename the selected entries"] = "Omd&oslash;b valgte";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "Ret rettigheder (Chmod) for de valgte mapper og filer (virker kun p&aring; Unix/Linux/BSD servere)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "Download en zip fil med alle filer og mapper";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Udpak det valgte arkiv p&aring; FTP serveren";
$net2ftp_messages["Zip the selected entries to save or email them"] = "Komprimer de valgte filer og mapper og enten gem eller email dem";
$net2ftp_messages["Calculate the size of the selected entries"] = "Udregn st&oslash;rrelsen p&aring; det valgte";
$net2ftp_messages["Find files which contain a particular word"] = "Find filer som indeholder et bestemt ord";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "Klik for at sortere via %1\$s i faldende orden";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "Klik for at sortere via %1\$s i stigende orden";
$net2ftp_messages["Ascending order"] = "Stigende orden";
$net2ftp_messages["Descending order"] = "Faldende orden";
$net2ftp_messages["Upload files"] = "Upload filer";
$net2ftp_messages["Up"] = "Op";
$net2ftp_messages["Click to check or uncheck all rows"] = "Mark&eacute;r alle, eller fjern alle markeringer";
$net2ftp_messages["All"] = "Alle";
$net2ftp_messages["Name"] = "Navn";
$net2ftp_messages["Type"] = "Type";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "Ejer";
$net2ftp_messages["Group"] = "Gruppe";
$net2ftp_messages["Perms"] = "Rettigheder";
$net2ftp_messages["Mod Time"] = "Sidst &aelig;ndret";
$net2ftp_messages["Actions"] = "Handlinger";
$net2ftp_messages["Select the directory %1\$s"] = "V&aelig;lg mappen: %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "V&aelig;lg filen: %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "V&aelig;lg symlinket: %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "G&aring; til mappen: %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "Download filen: %1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "F&oslash;lg symlinket: %1\$s";
$net2ftp_messages["View"] = "Vis kildekode";
$net2ftp_messages["Edit"] = "Redig&eacute;r";
$net2ftp_messages["Update"] = "Opdat&eacute;r";
$net2ftp_messages["Open"] = "&Aring;ben";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Se den fremh&aelig;vede kildekode for filen: %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Rediger kildekoden for filen: %1\$s";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "Upload en ny version af filen: %1\$s og sammenflet &aelig;ndringerne med den nuv&aelig;rende fil";
$net2ftp_messages["View image %1\$s"] = "Se billedet: %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "Se filen: %1\$s fra din HTTP web server";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(Bem&aelig;rk: Dette link virker m&aring;ske ikke hvis du ikke har dit eget internetdom&aelig;ne.)";
$net2ftp_messages["This folder is empty"] = "Denne mappe er tom";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "Mapper";
$net2ftp_messages["Files"] = "Filer";
$net2ftp_messages["Symlinks"] = "Symlinks";
$net2ftp_messages["Unrecognized FTP output"] = "Ukendt FTP output";
$net2ftp_messages["Number"] = "Nummer";
$net2ftp_messages["Size"] = "St&oslash;rrelse";
$net2ftp_messages["Skipped"] = "Spring over";
$net2ftp_messages["Data transferred from this IP address today"] = "Data overf&oslash;rt fra denne IP adresse i dag";
$net2ftp_messages["Data transferred to this FTP server today"] = "Data overf&oslash;rt til denne FTP server i dag";

// printLocationActions()
$net2ftp_messages["Language:"] = "Sprog:";
$net2ftp_messages["Skin:"] = "Tema:";
$net2ftp_messages["View mode:"] = "Se modus:";
$net2ftp_messages["Directory Tree"] = "Mappeoversigt";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "K&oslash;r %1\$s i et nyt vindue";
$net2ftp_messages["This file is not accessible from the web"] = "Denne fil kan ikke bruges p&aring; nettet";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "Dobbeltklik for at g&aring; til en mappe:";
$net2ftp_messages["Choose"] = "V&aelig;lg";
$net2ftp_messages["Up"] = "Op";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "St&oslash;rrelse p&aring; valgte filer og mapper";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "Den samlede st&oslash;rrelse p&aring; de valgte filer og mapper er:";
$net2ftp_messages["The number of files which were skipped is:"] = "Antal filer der ikke blev regnet med:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "Ret rettigheder (Chmod) for filer og mapper";
$net2ftp_messages["Set all permissions"] = "S&aelig;t alt til dette";
$net2ftp_messages["Read"] = "L&aelig;s";
$net2ftp_messages["Write"] = "Skriv";
$net2ftp_messages["Execute"] = "K&oslash;r";
$net2ftp_messages["Owner"] = "Ejer";
$net2ftp_messages["Group"] = "Gruppe";
$net2ftp_messages["Everyone"] = "Alle";
$net2ftp_messages["To set all permissions to the same values, enter those permissions and click on the button \"Set all permissions\""] = "To set all permissions to the same values, enter those permissions and click on the button \"Set all permissions\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "Set the permissions of directory <b>%1\$s</b> to: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "S&aelig;t rettigheder for filen: <b>%1\$s</b> til: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "S&aelig;t rettigheder for symlinket: <b>%1\$s</b> til: ";
$net2ftp_messages["Chmod value"] = "Chmod v&aelig;rdi";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "Ret ogs&aring; undermapper i denne mappe";
$net2ftp_messages["Chmod also the files within this directory"] = "Ret ogs&aring; filer i denne mappe";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "Indtastningen: <b>%1\$s</b> Er ugyldig. (fra 000 til 777). Pr&oslash;v igen.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "V&aelig;lg en mappe";
$net2ftp_messages["Copy directories and files"] = "Kopi&eacute;r mapper og filer";
$net2ftp_messages["Move directories and files"] = "Flyt mapper og filer";
$net2ftp_messages["Delete directories and files"] = "Slet mapper og filer";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "Er du sikker p&aring; du vil slette disse mapper og filer?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "Alle undermapper og filer vil ogs&aring; blive slettet!";
$net2ftp_messages["Set all targetdirectories"] = "Ret alles nye placering";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "For at rette alles nye placering s&aring; indtast placeringen i feltet herover og klik p&aring; \"Ret alles nye placering\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Bem&aelig;rk: placeringen skal v&aelig;re oprettet i forvejen f&oslash;r du kan kopiere noget dertil.";
$net2ftp_messages["Different target FTP server:"] = "Anden FTP server:";
$net2ftp_messages["Username"] = "Brugernavn";
$net2ftp_messages["Password"] = "Adgangskode";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "Efterlad disse felter blanke hvis du ikke vil kopiere noget til andre FTP servere.";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "Hvis du vil kopiere filerne til en anden FTP server skal du angive FTP serverens login oplysninger.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "Efterlad disse felter blanke hvis du ikke vil flytte noget til andre FTP servere.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "Hvis du vil flytte filerne til en anden FTP server skal du angive FTP serverens login oplysninger.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "Kopi&eacute;r mappen: <b>%1\$s</b> til:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "Flyt mappen: <b>%1\$s</b> til:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "Mappe: <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "Kopi&eacute;r fil <b>%1\$s</b> til:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "Flyt fil <b>%1\$s</b> til:";
$net2ftp_messages["File <b>%1\$s</b>"] = "File <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "Kopi&eacute;r symlink <b>%1\$s</b> til:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "Flyt symlink <b>%1\$s</b> til:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Symlink <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "Placering:";
$net2ftp_messages["Target name:"] = "Navn:";
$net2ftp_messages["Processing the entries:"] = "Behandler opgave:";

} // end copymovedelete


// -------------------------------------------------------------------------
// Download file module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// EasyWebsite module
if ($net2ftp_globals["state"] == "easyWebsite") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create a website in 4 easy steps"] = "Lav en webside med 4 nemme trin";
$net2ftp_messages["Template overview"] = "Skabelonoversigt";
$net2ftp_messages["Template details"] = "Skabelon detaljer";
$net2ftp_messages["Files are copied"] = "Filerne bliver kopieret";
$net2ftp_messages["Edit your pages"] = "Rediger dine sider";

// Screen 1 - printTemplateOverview
$net2ftp_messages["Click on the image to view the details of a template."] = "Klik p&aring; billedet for at se detaljer for en skabelon.";
$net2ftp_messages["Back to the Browse screen"] = "Tilbage til oversigten";
$net2ftp_messages["Template"] = "Skabelon";
$net2ftp_messages["Copyright"] = "Copyright";
$net2ftp_messages["Click on the image to view the details of this template"] = "Klik p&aring; billedet for at se detaljer for skabelonen";

// Screen 2 - printTemplateDetails
$net2ftp_messages["The template files will be copied to your FTP server. Existing files with the same filename will be overwritten. Do you want to continue?"] = "Skabelonfilerne bliver kopieret til din FTP server. Eksisterende filer med det samme filnavn bliver overskrevet. Vil du forts&aelig;tte?";
$net2ftp_messages["Install template to directory: "] = "Installer skabelon til mappen: ";
$net2ftp_messages["Install"] = "net2ftp Installer";
$net2ftp_messages["Size"] = "St&oslash;rrelse";
$net2ftp_messages["Preview page"] = "Gennemse side";
$net2ftp_messages["opens in a new window"] = "&aring;bner i et nyt vindue";

// Screen 3
$net2ftp_messages["Please wait while the template files are being transferred to your server: "] = "Vent venligst mens skabelon filerne bliver overf&oslash;rt til din server: ";
$net2ftp_messages["Done."] = "F&aelig;rdig.";
$net2ftp_messages["Continue"] = "Forts&aelig;t";

// Screen 4 - printEasyAdminPanel
$net2ftp_messages["Edit page"] = "Rediger side";
$net2ftp_messages["Browse the FTP server"] = "Gennemse FTP serveren";
$net2ftp_messages["Add this link to your favorites to return to this page later on!"] = "Tilf&oslash;j denne side til dine favoritter for at vende tilbage senere!";
$net2ftp_messages["Edit website at %1\$s"] = "Rediger side hos %1\$s";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: h&oslash;jreklik p&aring; linket og v&aelig;lg \"F&oslash;j til favoritter...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: h&oslash;jreklik p&aring; linket og v&aelig;lg \"Bogm&aelig;rk dette link...\"";

// ftp_copy_local2ftp
$net2ftp_messages["WARNING: Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing..."] = "ADVARSEL: kunne ikke skabe mappen <b>%1\$s</b>. Den eksisterer m&aring;ske i forvejen. Forts&aelig;tter...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Oprettede undermappen <b>%1\$s</b>";
$net2ftp_messages["WARNING: Unable to copy the file <b>%1\$s</b>. Continuing..."] = "ADVARSEL: Kan ikke kopiere filen <b>%1\$s</b>. Forts&aelig;tter...";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Kopierede filen <b>%1\$s</b>";
}


// -------------------------------------------------------------------------
// Edit module
if ($net2ftp_globals["state"] == "edit") {
// -------------------------------------------------------------------------

// /modules/edit/edit.inc.php
$net2ftp_messages["Unable to open the template file"] = "Kan ikke &aring;bne skabelon filen";
$net2ftp_messages["Unable to read the template file"] = "Kan ikke l&aelig;se skabelon filen";
$net2ftp_messages["Please specify a filename"] = "Indtast et filnavn";
$net2ftp_messages["Status: This file has not yet been saved"] = "Status: Denne fil er ikke blevet gemt endnu";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "Status: Gemt d. <b>%1\$s</b> via %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "Status: <b>Denne fil kunne ikke gemmes</b>";
$net2ftp_messages["Not yet saved"] = "Not yet saved";
$net2ftp_messages["Could not be saved"] = "Could not be saved";
$net2ftp_messages["Saved at %1\$s"] = "Saved at %1\$s";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "Mappe: ";
$net2ftp_messages["File: "] = "Fil: ";
$net2ftp_messages["New file name: "] = "Nyt filnavn: ";
$net2ftp_messages["Character encoding: "] = "Tegns&aelig;t: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "Bem&aelig;rk: ved at &aelig;ndre textarea typen bliver filen automatisk gemt";
$net2ftp_messages["Copy up"] = "Kopier op";
$net2ftp_messages["Copy down"] = "Kopier ned";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "S&oslash;g i mapper og filer";
$net2ftp_messages["Search again"] = "S&oslash;g igen";
$net2ftp_messages["Search results"] = "S&oslash;geresultater";
$net2ftp_messages["Please enter a valid search word or phrase."] = "Indtast et ord eller en s&aelig;tning.";
$net2ftp_messages["Please enter a valid filename."] = "Indtast et gyldigt filnavn.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "Indtast en gyldig filst&oslash;rrelse i  \"fra\" tekstfeltet, f.eks. 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "Indtast en gyldig filst&oslash;rrelse i \"til\" tekstfeltet, f.eks. 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "Indtast en gyldig dato i formatet &Aring;&Aring;&Aring;&Aring;-M-D i \"fra\" tekstfeltet.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "Indtast en gyldig dato i formatet &Aring;&Aring;&Aring;&Aring;-M-D i \"til\" tekstfeltet.";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "Ordet <b>%1\$s</b> blev ikke fundet iblandt de valgte mapper og filer.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "Ordet <b>%1\$s</b> blev fundet i f&oslash;lgende mapper og filer:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "S&oslash;g efter et ord eller en s&aelig;tning";
$net2ftp_messages["Case sensitive search"] = "Forskel p&aring; store og sm&aring; bogstaver";
$net2ftp_messages["Restrict the search to:"] = "Begr&aelig;ns s&oslash;gningen til";
$net2ftp_messages["files with a filename like"] = "filer med et filnavn som";
$net2ftp_messages["(wildcard character is *)"] = "(wildcard tegnet er *)";
$net2ftp_messages["files with a size"] = "filer med en st&oslash;rrelse";
$net2ftp_messages["files which were last modified"] = "filer der senest blev &aelig;ndret";
$net2ftp_messages["from"] = "fra";
$net2ftp_messages["to"] = "til";

$net2ftp_messages["Directory"] = "Mappe";
$net2ftp_messages["File"] = "Fil";
$net2ftp_messages["Line"] = "Linje";
$net2ftp_messages["Action"] = "Handling";
$net2ftp_messages["View"] = "Vis kildekode";
$net2ftp_messages["Edit"] = "Redig&eacute;r";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Se den fremh&aelig;vede kildekode for filen: %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Rediger kildekoden for filen: %1\$s";

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
$net2ftp_messages["Install software packages"] = "Installer software pakker";
$net2ftp_messages["Unable to open the template file"] = "Kan ikke &aring;bne skabelon filen";
$net2ftp_messages["Unable to read the template file"] = "Kan ikke l&aelig;se skabelon filen";
$net2ftp_messages["Unable to get the list of packages"] = "Ude af stand til at hente listen over pakker";

// /skins/blue/install1.template.php
$net2ftp_messages["The net2ftp installer script has been copied to the FTP server."] = "Net2ftp installer scriptet er kopieret til FTP serveren.";
$net2ftp_messages["This script runs on your web server and requires PHP to be installed."] = "Dette script k&oslash;rer via din web server og kr&aelig;ver at PHP er installeret.";
$net2ftp_messages["In order to run it, click on the link below."] = "For at k&oslash;re scriptet skal du klikke p&aring; linket herunder.";
$net2ftp_messages["net2ftp has tried to determine the directory mapping between the FTP server and the web server."] = "Net2ftp pr&oslash;vede at afg&oslash;re mappestrukturen mellem FTP serveren og web serveren.";
$net2ftp_messages["Should this link not be correct, enter the URL manually in your web browser."] = "Hvis dette link ikke er korrekt skal du manuelt indtaste URL adressen i din web browser.";

} // end install


// -------------------------------------------------------------------------
// Java upload module
if ($net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload directories and files using a Java applet"] = "Upload mapper og filer via et Java applet";
$net2ftp_messages["Number of files:"] = "Antal filer:";
$net2ftp_messages["Size of files:"] = "Filers st&oslash;rrelse:";
$net2ftp_messages["Add"] = "Tilf&oslash;j";
$net2ftp_messages["Remove"] = "Fjern";
$net2ftp_messages["Upload"] = "Upload";
$net2ftp_messages["Add files to the upload queue"] = "Tilf&oslash;j filer til upload-k&oslash;en";
$net2ftp_messages["Remove files from the upload queue"] = "Fjern filer fra upload-k&oslash;en";
$net2ftp_messages["Upload the files which are in the upload queue"] = "Upload alle filer i upload-k&oslash;en";
$net2ftp_messages["Maximum server space exceeded. Please select less/smaller files."] = "Maximum server pladsforbrug n&aring;et. Pr&oslash;v med f&aelig;rre eller mindre filer.";
$net2ftp_messages["Total size of the files is too big. Please select less/smaller files."] = "Filernes samlede st&oslash;rrelse er for stor. Pr&oslash;v med f&aelig;rre eller mindre filer.";
$net2ftp_messages["Total number of files is too high. Please select fewer files."] = "Du har valgt for mange filer. Pr&oslash;v med f&aelig;rre filer ad gangen.";
$net2ftp_messages["Note: to use this applet, Sun's Java plugin must be installed (version 1.4 or newer)."] = "Bem&aelig;rk: for at bruge dette applet skal du have Suns Java plugin installeret p&aring; din computer (version 1.4 eller nyere).";

} // end jupload



// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login") {
// -------------------------------------------------------------------------
$net2ftp_messages["Login!"] = "Log ind!";
$net2ftp_messages["Once you are logged in, you will be able to:"] = "N&aring;r du er logget p&aring; kan du:";
$net2ftp_messages["Navigate the FTP server"] = "Nagivere i din FTP server";
$net2ftp_messages["Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."] = "N&aring;r du er logget p&aring; kan du navigere igennem dine mapper og se alle dine filer.";
$net2ftp_messages["Upload files"] = "Upload filer";
$net2ftp_messages["There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet."] = "Der er tre m&aring;der at uploade filer p&aring;: den normale upload form, upload en komprimeret fil og udpak filer og mapper, og via et  Java Applet.";
$net2ftp_messages["Download files"] = "Downloade filer";
$net2ftp_messages["Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive."] = "Klik p&aring; et filnavn for at downloade den fil.<br />V&aelig;lg flere filer og klik p&aring; Download; de valge filer bliver downloadet i et komprimeret format.";
$net2ftp_messages["Zip files"] = "Komprim&eacute;r filer";
$net2ftp_messages["... and save the zip archive on the FTP server, or email it to someone."] = "... og gem den komprimerede fil p&aring; FTP serveren, eller mail den til en eller anden.";
$net2ftp_messages["Unzip files"] = "Udpak arkiver";
$net2ftp_messages["Different formats are supported: .zip, .tar, .tgz and .gz."] = "Flere formater underst&oslash;ttes: .zip, .tar, .tgz og .gz.";
$net2ftp_messages["Install software"] = "Install&eacute;r software";
$net2ftp_messages["Choose from a list of popular applications (PHP required)."] = "V&aelig;lg fra en liste over popul&aelig;re produkter (PHP p&aring;kr&aelig;ves).";
$net2ftp_messages["Copy, move and delete"] = "Kopi&eacute;r, flyt og slet";
$net2ftp_messages["Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted."] = "Mappers indhold (undermapper og filer) inkluderes ogs&aring; n&aring;r du kopierer, flytter eller sletter mapper.";
$net2ftp_messages["Copy or move to a 2nd FTP server"] = "Kopi&eacute;r eller flyt til en anden FTP server";
$net2ftp_messages["Handy to import files to your FTP server, or to export files from your FTP server to another FTP server."] = "Brugbart for at importere filer til din FTP server, eller for at eksportere filer fra din server til en anden FTP server.";
$net2ftp_messages["Rename and chmod"] = "Omd&oslash;b og rediger rettigheder (chmod)";
$net2ftp_messages["Chmod handles directories recursively."] = "Chmod af mapper p&aring;virker ogs&aring; mappers indhold.";
$net2ftp_messages["View code with syntax highlighting"] = "Se kildekode med farvefremh&aelig;vet syntax";
$net2ftp_messages["PHP functions are linked to the documentation on php.net."] = "PHP funktioner linkes til php.net for hurigt at finde en beskrivelse.";
$net2ftp_messages["Plain text editor"] = "Normal tekstredigering";
$net2ftp_messages["Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server."] = "Ret tekst direkte i din browser, hver gang du gemmer dine &aelig;ndringer bliver filen opdateret p&aring; din FTP server.";
$net2ftp_messages["HTML editors"] = "HTML editorer";
$net2ftp_messages["Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from."] = "Ret HTML i en What-You-See-Is-What-You-Get (WYSIWYG) editor; Der er to forskellige editorer at v&aelig;lge imellem.";
$net2ftp_messages["Code editor"] = "Kode editor";
$net2ftp_messages["Edit HTML and PHP in an editor with syntax highlighting."] = "Ret HTML og PHP i en editor med farvefremh&aelig;vning af syntax.";
$net2ftp_messages["Search for words or phrases"] = "S&oslash;g efter ord eller s&aelig;tninger";
$net2ftp_messages["Filter out files based on the filename, last modification time and filesize."] = "Filtr&eacute;r filer via filnavn, filst&oslash;rrelse og hvorn&aring;r de sidst er &aelig;ndret.";
$net2ftp_messages["Calculate size"] = "Udregn st&oslash;rrelse";
$net2ftp_messages["Calculate the size of directories and files."] = "Udregn samlet st&oslash;rrelse p&aring; flere filer og mapper.";

$net2ftp_messages["FTP server"] = "FTP server";
$net2ftp_messages["Example"] = "Eksempel";
$net2ftp_messages["Port"] = "Port";
$net2ftp_messages["Username"] = "Brugernavn";
$net2ftp_messages["Password"] = "Adgangskode";
$net2ftp_messages["Anonymous"] = "Log p&aring; som anonym";
$net2ftp_messages["Passive mode"] = "Passiv modus";
$net2ftp_messages["Initial directory"] = "Mappe";
$net2ftp_messages["Language"] = "Sprog";
$net2ftp_messages["Skin"] = "Tema";
$net2ftp_messages["FTP mode"] = "FTP modus";
$net2ftp_messages["Automatic"] = "Automatisk";
$net2ftp_messages["Login"] = "Log ind";
$net2ftp_messages["Clear cookies"] = "Ryd cookies";
$net2ftp_messages["Admin"] = "Administrator";
$net2ftp_messages["Please enter an FTP server."] = "Indtast en FTP server.";
$net2ftp_messages["Please enter a username."] = "Indtast et brugernavn.";
$net2ftp_messages["Please enter a password."] = "Indtast en adganskode.";

} // end login


// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login_small") {
// -------------------------------------------------------------------------

$net2ftp_messages["Please enter your Administrator username and password."] = "Indtast enten dit brugernavn eller din adgangskode.";
$net2ftp_messages["Please enter your username and password for FTP server <b>%1\$s</b>."] = "Indtast dit brugernavn og adgangskode for FTP serveren: <b>%1\$s</b>.";
$net2ftp_messages["Username"] = "Brugernavn";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Dit login er udl&oslash;bet. Log ind igen for at forts&aelig;tte dit arbejde p&aring; FTP serveren: <b>%1\$s</b>.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Din IP adresse er &aelig;ndret; log ind igen for at forts&aelig;tte dit arbejde p&aring; FTP serveren: <b>%1\$s</b>.";
$net2ftp_messages["Password"] = "Adgangskode";
$net2ftp_messages["Login"] = "Log ind";
$net2ftp_messages["Continue"] = "Forts&aelig;t";

} // end login_small


// -------------------------------------------------------------------------
// Logout module
if ($net2ftp_globals["state"] == "logout") {
// -------------------------------------------------------------------------

// logout.inc.php
$net2ftp_messages["Login page"] = "Login side";

// logout.template.php
$net2ftp_messages["You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>."] = "Du er logget ud af din FTP server. <a href=\"%1\$s\" title=\"Login side (ALT + l)\" accesskey=\"l\">Log p&aring; igen</a>.";
$net2ftp_messages["Note: other users of this computer could click on the browser's Back button and access the FTP server."] = "Bem&aelig;rk: andre brugere af denne computer kan klikke p&aring; tilbageknappen i browseren og f&aring; adgang til FTP serveren.";
$net2ftp_messages["To prevent this, you must close all browser windows."] = "For at undg&aring; dette, s&aring; luk alle browservinduer.";
$net2ftp_messages["Close"] = "Luk";
$net2ftp_messages["Click here to close this window"] = "Luk dette vindue";

} // end logout


// -------------------------------------------------------------------------
// New directory module
if ($net2ftp_globals["state"] == "newdir") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create new directories"] = "Opret en ny mappe";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "Den nye mappe bliver oprettet i: <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "Mappenavn:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "Mappen: <b>%1\$s</b> er oprettet.";
$net2ftp_messages["Directory <b>%1\$s</b> could not be created."] = "Mappen: <b>%1\$s</b> kunne ikke oprettes.";

} // end newdir


// -------------------------------------------------------------------------
// Raw module
if ($net2ftp_globals["state"] == "raw") {
// -------------------------------------------------------------------------

// /modules/raw/raw.inc.php
$net2ftp_messages["Send arbitrary FTP commands"] = "Send arbitr&aelig;r FTP kommando";


// /skins/[skin]/raw1.template.php
$net2ftp_messages["List of commands:"] = "liste over kommandoer:";
$net2ftp_messages["FTP server response:"] = "FTP server svar:";

} // end raw


// -------------------------------------------------------------------------
// Rename module
if ($net2ftp_globals["state"] == "rename") {
// -------------------------------------------------------------------------
$net2ftp_messages["Rename directories and files"] = "Omd&oslash;b mapper og filer";
$net2ftp_messages["Old name: "] = "Gammelt navn: ";
$net2ftp_messages["New name: "] = "Nyt navn: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "Det nye navn m&aring; ikke indeholde punktummer. Denne mappe eller fil kunne ikke omd&oslash;bes til <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "Det nye navn m&aring; ikke indeholde blokerede n&oslash;gleord. Denne mappe eller fil kunne ikke omd&oslash;bes til <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> blev omd&oslash;bt til <b>%2\$s</b>";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> kunne ikke omd&oslash;bes til <b>%2\$s</b>";

} // end rename


// -------------------------------------------------------------------------
// Unzip module
if ($net2ftp_globals["state"] == "unzip") {
// -------------------------------------------------------------------------

// /modules/unzip/unzip.inc.php
$net2ftp_messages["Unzip archives"] = "Udpak arkiver";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Henter %1\$s ud af %2\$s arkiver fra FTP serveren";
$net2ftp_messages["Unable to get the archive <b>%1\$s</b> from the FTP server"] = "Kan ikke hente arkivet: <b>%1\$s</b> fra FTP serveren";

// /skins/[skin]/unzip1.template.php
$net2ftp_messages["Set all targetdirectories"] = "Ret alles nye placering";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "For at rette alles nye placering s&aring; indtast placeringen i feltet herover og klik p&aring; \"Ret alles nye placering\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Bem&aelig;rk: placeringen skal v&aelig;re oprettet i forvejen f&oslash;r du kan kopiere noget dertil.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Udpakker arkiv: <b>%1\$s</b> til:";
$net2ftp_messages["Target directory:"] = "Placering:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Brug undermapper (opretter undermapper automatisk)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "Opdater fil";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>ADVARSEL: DENNE FUNKTION ER STADIG P&Aring; TESTSTADIET. BRUG DET KUN TIL TESTFORM&Aring;L!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "Kendte fejl: - Fjerner tabulatortegn - Virker ikke p&aring; store filer (> 50kB) - er ikke testet p&aring; filer der indeholder nationale tegn</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "Denne funktion lader dig uploade en nyere version af den valgte fil s&aring; du kan godkende eller afvise hver &aelig;ndring du har foretaget f&oslash;r du gemmer den.";
$net2ftp_messages["Old file:"] = "Gammel fil:";
$net2ftp_messages["New file:"] = "Ny fil:";
$net2ftp_messages["Restrictions:"] = "Begr&aelig;nsninger:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Den maksimale filst&oslash;rrelse er sat af net2ftp til at v&aelig;re <b>%1\$s kB</b> og af PHP til at v&aelig;re <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Upload af filer m&aring; h&oslash;jst vare <b>%1\$s sekunder</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "FTP overf&oslash;rselsmodus (ASCII eller BINARY) vil automatisk blive besluttet ud fra filernes endelser";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Hvis destinationen findes i forvejen bliver indholdet erstattet";
$net2ftp_messages["You did not provide any files or archives to upload."] = "Du har ikke valgt nogen filer eller arkiver til at uploade.";
$net2ftp_messages["Unable to delete the new file"] = "Kan ikke slette den nye fil";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "Vent venligst...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "Godkend eller afvis &aelig;ndringerne herunder og klik p&aring; udf&oslash;r.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "Upload til mappe:";
$net2ftp_messages["Files"] = "Filer";
$net2ftp_messages["Archives"] = "Arkiver";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "Valgte filer bliver overf&oslash;rt til FTP serveren.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "Valgte arkiver bliver automatisk udpakket og filerne overf&oslash;res til FTP serveren.";
$net2ftp_messages["Add another"] = "Tilf&oslash;j flere";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Brug undermapper (opretter undermapper automatisk)";

$net2ftp_messages["Choose a directory"] = "V&aelig;lg en mappe";
$net2ftp_messages["Please wait..."] = "Vent venligst...";
$net2ftp_messages["Uploading... please wait..."] = "Uploader... vent venligst...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "Hvis det tager mere end <b>%1\$s sekunder<\/b> for at uploade filen m&aring; du pr&oslash;ve igen med f&aelig;rre eller mindre filer.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "Dette vindue lukker automatisk om f&aring; sekunder.";
$net2ftp_messages["Close window now"] = "Luk vindue";

$net2ftp_messages["Upload files and archives"] = "Upload filer og mapper";
$net2ftp_messages["Upload results"] = "Upload resultater";
$net2ftp_messages["Checking files:"] = "Kontrollerer filer:";
$net2ftp_messages["Transferring files to the FTP server:"] = "Overf&oslash;rer filer til FTP serveren:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "Dekomprimerer arkiver og overf&oslash;rer filer til FTP serveren:";
$net2ftp_messages["Upload more files and archives"] = "Upload flere filer og mapper";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "Begr&aelig;nsninger:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Den maksimale filst&oslash;rrelse er sat af net2ftp til at v&aelig;re <b>%1\$s kB</b> og af PHP til at v&aelig;re <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Upload af filer m&aring; h&oslash;jst vare <b>%1\$s sekunder</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "FTP overf&oslash;rselsmodus (ASCII eller BINARY) vil automatisk blive besluttet ud fra filernes endelser";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Hvis destinationen findes i forvejen bliver indholdet erstattet";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "Se fil %1\$s";
$net2ftp_messages["View image %1\$s"] = "Se billedet: %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "Se Macromedia ShockWave Flash film %1\$s";
$net2ftp_messages["Image"] = "Billede";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntax fremh&aelig;vning styres via <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "For at gemme billedet, h&oslash;jreklik og v&aelig;lg 'Gem billede som...'";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "Komprimerede filer";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "Gem den komprimerede fil p&aring; FTP serveren som:";
$net2ftp_messages["Email the zip file in attachment to:"] = "Mail den komprimerede fil som en vedh&aelig;ftning til:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "Bem&aelig;rk det ikke er anonymt at sende filer: din IP adresse og afsendelsestidspunktet bliver noteret i mailen.";
$net2ftp_messages["Some additional comments to add in the email:"] = "Tilf&oslash;j yderligere kommentarer til mailen:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "Du angav ikke et filnavn for den komprimerede fil. G&aring; tilbage og angiv et filnavn.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "Den valgte mailadresse (%1\$s) bestod ikke valideringen.<br />Indtast en mail i formatet <b>eksempel@dom&aelig;ne.dk</b>";

} // end zip

?>