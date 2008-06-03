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
$net2ftp_messages["en"] = "se";

// HTML dir attribute: left-to-right (LTR) or right-to-left (RTL)
$net2ftp_messages["ltr"] = "ltr";

// CSS style: align left or right (use in combination with LTR or RTL)
$net2ftp_messages["left"] = "left";
$net2ftp_messages["right"] = "right";

// Encoding
$net2ftp_messages["iso-8859-1"] = "iso-8859-1";


// -------------------------------------------------------------------------
// Status messages
// -------------------------------------------------------------------------

// When translating these messages, keep in mind that the text should not be too long
// It should fit in the status textbox

$net2ftp_messages["Connecting to the FTP server"] = "Ansluter till FTP server";
$net2ftp_messages["Logging into the FTP server"] = "Loggar in på FTP server";
$net2ftp_messages["Setting the passive mode"] = "Ställer in passive mode";
$net2ftp_messages["Getting the FTP system type"] = "Mottar FTP system typ";
$net2ftp_messages["Changing the directory"] = "Ändrar i biblioteket";
$net2ftp_messages["Getting the current directory"] = "Mottar aktuellt bibliotek";
$net2ftp_messages["Getting the list of directories and files"] = "Mottar lista med bibliotek och filer";
$net2ftp_messages["Parsing the list of directories and files"] = "Analyserar listan med bibliotek och filer";
$net2ftp_messages["Logging out of the FTP server"] = "Loggar ut från FTP server";
$net2ftp_messages["Getting the list of directories and files"] = "Mottar lista med bibliotek och filer";
$net2ftp_messages["Printing the list of directories and files"] = "Skriver ut lista med bibliotek och filer";
$net2ftp_messages["Processing the entries"] = "Bearbetar inmatningarna";
$net2ftp_messages["Processing entry %1\$s"] = "Bearbetar inmatning %1\$s";
$net2ftp_messages["Checking files"] = "Kontrollerar filer";
$net2ftp_messages["Transferring files to the FTP server"] = "Överför filer till FTP server";
$net2ftp_messages["Decompressing archives and transferring files"] = "Dekomprimerar arkiv och överför filer";
$net2ftp_messages["Searching the files..."] = "Letar efter filer...";
$net2ftp_messages["Uploading new file"] = "Laddar upp ny fil";
$net2ftp_messages["Reading the file"] = "Läser filen";
$net2ftp_messages["Parsing the file"] = "Analyserar filen";
$net2ftp_messages["Reading the new file"] = "Läser den nya filen";
$net2ftp_messages["Reading the old file"] = "Läser den gamla filen";
$net2ftp_messages["Comparing the 2 files"] = "Jämför de 2 filerna";
$net2ftp_messages["Printing the comparison"] = "Skriver ut jämförelse";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "Skickar FTP order %1\$s of %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Mottar arkiv %1\$s av %2\$s från FTP server";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Skapar temporärt bibliotek på FTP server";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Ställer in rättigheter för temporärt bibliotek";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Kopierar net2ftp instalationsscript till FTP server";
$net2ftp_messages["Script finished in %1\$s seconds"] = "Script avslutat efter %1\$s sekunder";
$net2ftp_messages["Script halted"] = "Script pausat";

// Used on various screens
$net2ftp_messages["Please wait..."] = "Vänta...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Oväntad meddelande rad: %1\$s. Avslutar.";
$net2ftp_messages["This beta function is not activated on this server."] = "Denna beta funktion är inte aktiverad på denna server.";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "Denna funktion har stängts av Administratorn för denna websida.";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "Biblioteket <b>%1\$s</b> existerar inte eller kunde inte väljas, så biblioteket <b>%2\$s</b> visas istället.";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "Ditt root bibliotek <b>%1\$s</b> existerar inte eller kunde inte väljas.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "Biblioteket <b>%1\$s</b> kunde inte väljas - du kanske inte har rättigheter att se detta bibliotek, eller så existerar det inte.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted.";
$net2ftp_messages["Execute %1\$s in a new window"] = "Utför %1\$s i ett nytt fönster";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "Välj minst ett bibliotek eller fil!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "FTP servern <b>%1\$s</b> finns inte i listan  med tillåtna FTP servers.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "FTP servern <b>%1\$s</b> finns i listan med bannlysta FTP servers.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "FTP server porten %1\$s får inte användas.";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "Your IP address (%1\$s) is not in the list of allowed IP addresses.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "Din IP address (%1\$s) finns i listan med bannlysta IP addresser.";

// isAuthorizedDirectory()
$net2ftp_messages["Table net2ftp_users contains duplicate rows."] = "Tabellen net2ftp_users innehåller dubblettrader.";

// logAccess(), logLogin(), logError()
$net2ftp_messages["Unable to execute the SQL query."] = "Kunde inte utföra SQL fråga.";

// checkAdminUsernamePassword()
$net2ftp_messages["You did not enter your Administrator username or password."] = "Du skrev inte in ditt Administratörs användarnamn eller lösenord.";
$net2ftp_messages["Wrong username or password. Please try again."] = "Fel användarnamn eller lösenord. Försök igen.";


// -------------------------------------------------------------------------
// /includes/consumption.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to determine your IP address."] = "Kunde inte fastställa din IP address.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "Tabell net2ftp_log_consumption_ipaddress inehåller dubblettrader.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "Tabell net2ftp_log_consumption_ftpserver inehåller dubblettrader.";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "Variabeln <b>consumption_ipaddress_datatransfer</b> är inte numerisk.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "Tabell net2ftp_log_consumption_ipaddress kunde inte uppdateras.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "Tabell net2ftp_log_consumption_ipaddress inehåller dubblettinmatningar.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "Tabell net2ftp_log_consumption_ftpserver kunde inte uppdateras.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "Tabell net2ftp_log_consumption_ftpserver inehåller dubblettinmatningar.";
$net2ftp_messages["Table net2ftp_log_access could not be updated."] = "Table net2ftp_log_access could not be updated.";
$net2ftp_messages["Table net2ftp_log_access contains duplicate entries."] = "Table net2ftp_log_access contains duplicate entries.";


// -------------------------------------------------------------------------
// /includes/database.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "Kunde inte ansluta till MySQL databasen. Kontrollera dina MySQL databas inställningar i net2ftp's konfigurationsfil settings.inc.php.";
$net2ftp_messages["Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "Kunde inte välja MySQL databas. Kontrollera dina MySQL databas inställningar i net2ftp's konfigurationsfil settings.inc.php.";


// -------------------------------------------------------------------------
// /includes/errorhandling.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["An error has occured"] = "Ett fel har uppstått";
$net2ftp_messages["Go back"] = "Go back";
$net2ftp_messages["Go to the login page"] = "Gå till login sidan";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = " <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP modul i PHP</a> är inte installerad.<br /><br /> Administratören för denna websida måste installera denna FTP modul. Installations instruktioner finns på <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Kunde inte ansluta till FTP server <b>%1\$s</b> på port <b>%2\$s</b>.<br /><br />Är du säker att det är rätt adress till FTP servern? Denna är ofta annorlunda än den till HTTP (web) servern. Kontakta din ISP´s support eller systemadministratör för hjälp.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Kunde inte logga in på FTP servern <b>%1\$s</b> med användarnamn <b>%2\$s</b>.<br /><br />Är du säker att ditt användarnamn och lösenord är korrekt? Kontakta din ISP´s support eller systemadministratör för hjälp.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "Kunde inte välja passive mode på FTP servern <b>%1\$s</b>.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Kunde inte ansluta till den andra (target) FTP servern <b>%1\$s</b> på port <b>%2\$s</b>.<br /><br />Är du säker att detta är adressen till den andra (target) FTP servern? Denna är ofta annorlunda än den till HTTP (web) servern. Kontakta din ISP´s support eller systemadministratör för hjälp.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Kunde inte logga in på den andra (target) FTP servern <b>%1\$s</b> med användarnamn <b>%2\$s</b>.<br /><br />Är du säker att ditt användarnamn och lösenord är korrekt? Kontakta din ISP´s support eller systemadministratör för hjälp.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "Kunde inte välja passive mode på den andra (target) FTP servern <b>%1\$s</b>.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "Kunde inte döpa om bibliotek eller fil <b>%1\$s</b> till <b>%2\$s</b>";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "Kunde inte utföra site command <b>%1\$s</b>. Notera att CHMOD command bara är tillgängligt på Unix FTP servrar, inte på Windows FTP servrar.";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "Bibliotek <b>%1\$s</b> framgångsrikt chmodded till <b>%2\$s</b>";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "Bearbetar inmatningar i biblioteket <b>%1\$s</b>:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "Fil <b>%1\$s</b> framgångsrikt chmodded till <b>%2\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "Alla valda bibliotek och filer har bearbetats.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "Kunde inte radera biblioteket <b>%1\$s</b>";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Kunde inte radera filen <b>%1\$s</b>";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "Kunde inte skapa biblioteket <b>%1\$s</b>";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "Kunde inte skapa tillfällig fil";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "Kunde inte ladda filen <b>%1\$s</b> från FTP servern och spara den som temporär fil <b>%2\$s</b>.<br />Kontrollera rättigheter för %3\$s biblioteket.<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Kunde inte öppna den temporära filen. Kontrollera rättigheter för %1\$s biblioteket.";
$net2ftp_messages["Unable to read the temporary file"] = "Kunde inte läsa den temporära filen";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Kunde inte avsluta hanteringen av den temporära filen";
$net2ftp_messages["Unable to delete the temporary file"] = "Kunde inte radera den temporära filen";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "Kunde inte skapa den temporära filen. Kontrollera rättigheter för %1\$s biblioteket.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Kunde inte öppna den temporära filen. Kontrollera rättigheter för %1\$s biblioteket.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "Kunde inte skriva raden till den temporära filen <b>%1\$s</b>.<br />Kontrollera rättigheter för %2\$s biblioteket.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Kunde inte avsluta hanteringen av den temporära filen";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "Kunde inte placera filen <b>%1\$s</b> på FTP servern.<br />Du kanske inte har rättigheter att skriva till biblioteket.";
$net2ftp_messages["Unable to delete the temporary file"] = "Kunde inte radera den temporära filen";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "Bearbetar bibliotek <b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "Målbibliotek <b>%1\$s</b> är samma som eller ett underbibliotek till källbiblioteket <b>%2\$s</b>, så detta bibliotek skapas inte";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The directory <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "Kunde inte skapa underbibliotek <b>%1\$s</b>. Det kanske redan finns. Fortsätter kopiera/flytta processen...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Skapat målunderbibliotek <b>%1\$s</b>";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "Biblioteket <b>%1\$s</b> kunde inte väljas, så detta bibliotek skapas inte";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "Kunde inte radera underbiblioteket <b>%1\$s</b> - det kan innehålla filer";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "Raderat underbibliotek <b>%1\$s</b>";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "Bearbetande av bibliotek <b>%1\$s</b> färdigt";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "Målet för filen <b>%1\$s</b> är samma som källan, så denna fil skapas inte";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The file <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "Filen <b>%1\$s</b> är för stor för att kopieras, så denna fil skapas inte";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "Filen <b>%1\$s</b> är för stor för att flyttas, avbryter flytt";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "Unable to copy the file <b>%1\$s</b>";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Copied file <b>%1\$s</b>";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Unable to move the file <b>%1\$s</b>, aborting the move";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "Flyttade fil <b>%1\$s</b>";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Kunde inte radera filen <b>%1\$s</b>";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "Raderade filen <b>%1\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "Alla valda bibliotek och filer har bearbetats.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "Kunde inte kopiera fjärrfilen <b>%1\$s</b> till den lokala filen med FTP mode <b>%2\$s</b>";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "Kunde inte radera filen <b>%1\$s</b>";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "The file is too big to be transferred";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "Daglig gräns uppnådd: filen <b>%1\$s</b> kommer inte överföras";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "Kunde inte kopiera den lokala filen till fjärrfilen <b>%1\$s</b> med FTP mode <b>%2\$s</b>";
$net2ftp_messages["Unable to delete the local file"] = "Kunde inte radera den lokala filen";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "Kunde inte radera den temporära filen";
$net2ftp_messages["Unable to send the file to the browser"] = "Kunde inte skicka filen till webbläsaren";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "Kunde inte skapa tillfällig fil";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "Zip filen har sparats på FTP servern som <b>%1\$s</b>";
$net2ftp_messages["Requested files"] = "Begärd fil";

$net2ftp_messages["Dear,"] = "Kära,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "Någon har begärt att filen i bilagan skickas till denna emailadress (%1\$s).";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "Om du inte känner till detta eller litar på den personen, radera detta email utan att öppna bifogad Zip fil.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "Kom ihåg att om du inte öppnar Zip filen, kan inte filer inuti ziparkivet skada din dator.";
$net2ftp_messages["Information about the sender: "] = "Information om avsändaren: ";
$net2ftp_messages["IP address: "] = "IP adress: ";
$net2ftp_messages["Time of sending: "] = "Skickat den: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "Skickat via net2ftp applikationen installerad på denna webbsida: ";
$net2ftp_messages["Webmaster's email: "] = "Webmaster's email: ";
$net2ftp_messages["Message of the sender: "] = "Meddelande från avsändaren: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp är fri programvara, släppt under GNU/GPL licens. För mer information, http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "Zip fil skickad till <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "Filen <b>%1\$s</b> är för stor. Denna fil kommer inte att laddas upp.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded.";
$net2ftp_messages["Could not generate a temporary file."] = "Kunde inte skapa temporär fil.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "Filen <b>%1\$s</b> kunde inte flyttas";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "Filen <b>%1\$s</b> är OK";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "Kunde inte flytta den uppladdade filen till temporärt bibliotek.<br /><br />Administratören för denna webbsida måste <b>chmod 777</b> /temp biblioteket av net2ftp.";
$net2ftp_messages["You did not provide any file to upload."] = "Du valde ingen fil att ladda upp.";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "Filen <b>%1\$s</b> kunde inte överföras till FTP servern";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "Filen <b>%1\$s</b> har överförts till FTP servern med FTP mode <b>%2\$s</b>";
$net2ftp_messages["Transferring files to the FTP server"] = "Överför filer till FTP server";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "Bearbetar arkiv nr %1\$s: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "Arkiv <b>%1\$s</b> ej bearbetat, filnamnsförlängning inte igenkänt. Bara zip, tar, tgz och gz arkiv stödjs för tillfället.";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Kunde inte extrahera filer och bibliotek från arkivet";
$net2ftp_messages["Created directory %1\$s"] = "Skapade bibliotek %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "Kunde inte skapa bibliotek %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "Kopierade fil %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "Kunde inte kopiera fil %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "Kunde inte radera det temporära biblioteket";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Kunde inte radera den temporära filen %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "Kunde inte utföra site command <b>%1\$s</b>";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "Din uppgift stoppades";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "Den uppgift du ville utföra med net2ftp tog längre tid än tillåtna %1\$s sekunder, därför stoppades uppgiften.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "Denna tidsgräns garanterar rättvist utnyttjande av webbservern för alla.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "Försök dela upp din uppgift i mindre bitar: begränsa ditt val av filer och uteslut de största filerna.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "Om du verkligen vill att net2ftp ska kunna hantera stora uppgifter som tar längre tid, överväg att installera net2ftp på din egen server.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "Du skrev ingen text att sända via email!";
$net2ftp_messages["You did not supply a From address."] = "Du skrev ingen Från adress.";
$net2ftp_messages["You did not supply a To address."] = "Du skrev ingen Till adress.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "Pga tekniska problem kunde inte email till <b>%1\$s</b> skickas.";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "Fyll i användarnamn och lösenord för FTP servern ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Du fyllde inte i dina inloggningsuppgifter i popup fönstret.<br />Klicka på \"Gå till login sida\" nedan.";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "Åtkomst av net2ftp Admin panel är avstängd, därför inget lösenord angivits i file settings.inc.php. Fyll i lösenord i den filen och ladda om den här sidan.";
$net2ftp_messages["Please enter your Admin username and password"] = "Fyll i ditt Admin användarnamn och lösenord"; 
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Du fyllde inte i dina inloggningsuppgifter i popup fönstret.<br />Klicka på \"Gå till login sida\" nedan.";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "Fel användarnamn eller lösenord för net2ftp Admin panel. Användarnamn och lösenord anges i file settings.inc.php.";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "Blå";
$net2ftp_messages["Grey"] = "Grå";
$net2ftp_messages["Black"] = "Svart";
$net2ftp_messages["Yellow"] = "Gul";
$net2ftp_messages["Pastel"] = "Pastell";

// getMime()
$net2ftp_messages["Directory"] = "Bibliotek";
$net2ftp_messages["Symlink"] = "Symlink";
$net2ftp_messages["ASP script"] = "ASP script";
$net2ftp_messages["Cascading Style Sheet"] = "Cascading Style Sheet";
$net2ftp_messages["HTML file"] = "HTML fil";
$net2ftp_messages["Java source file"] = "Java source fil";
$net2ftp_messages["JavaScript file"] = "JavaScript fil";
$net2ftp_messages["PHP Source"] = "PHP Source";
$net2ftp_messages["PHP script"] = "PHP script";
$net2ftp_messages["Text file"] = "Text fil";
$net2ftp_messages["Bitmap file"] = "Bitmap fil";
$net2ftp_messages["GIF file"] = "GIF fil";
$net2ftp_messages["JPEG file"] = "JPEG fil";
$net2ftp_messages["PNG file"] = "PNG fil";
$net2ftp_messages["TIF file"] = "TIF fil";
$net2ftp_messages["GIMP file"] = "GIMP fil";
$net2ftp_messages["Executable"] = "Executable";
$net2ftp_messages["Shell script"] = "Shell script";
$net2ftp_messages["MS Office - Word document"] = "MS Office - Word dokument";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - Excel kalkylblad";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - PowerPoint presentation";
$net2ftp_messages["MS Office - Access database"] = "MS Office - Access databas";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - Visio drawing";
$net2ftp_messages["MS Office - Project file"] = "MS Office - Projekt fil";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - Writer 6.0 dokument";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - Writer 6.0 mall";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - Calc 6.0 kalkylblad";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - Calc 6.0 mall";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - Draw 6.0 dokument";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - Draw 6.0 mall";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - Impress 6.0 presentation";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - Impress 6.0 mall";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - Writer 6.0 global dokument";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - Math 6.0 dokument";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - StarWriter 5.x dokument";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - StarWriter 5.x global dokument";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - StarCalc 5.x kalkylblad";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - StarDraw 5.x dokument";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - StarImpress 5.x presentation";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - StarImpress Packed 5.x fil";
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
$net2ftp_messages["MOV movie file"] = "MOV film fil";
$net2ftp_messages["MPEG movie file"] = "MPEG film fil";
$net2ftp_messages["Real movie file"] = "Real film fil";
$net2ftp_messages["Quicktime movie file"] = "Quicktime film fil";
$net2ftp_messages["Shockwave flash file"] = "Shockwave flash fil";
$net2ftp_messages["Shockwave file"] = "Shockwave fil";
$net2ftp_messages["WAV sound file"] = "WAV sound fil";
$net2ftp_messages["Font file"] = "Font fil";
$net2ftp_messages["%1\$s File"] = "%1\$s Fil";
$net2ftp_messages["File"] = "Fil";

// getAction()
$net2ftp_messages["Back"] = "Bakåt";
$net2ftp_messages["Submit"] = "Skicka in";
$net2ftp_messages["Refresh"] = "Uppdatera";
$net2ftp_messages["Details"] = "Details";
$net2ftp_messages["Icons"] = "Ikoner";
$net2ftp_messages["List"] = "Lista";
$net2ftp_messages["Logout"] = "Logga ut";
$net2ftp_messages["Help"] = "Hjälp";
$net2ftp_messages["Bookmark"] = "Bokmärke";
$net2ftp_messages["Save"] = "Spara";
$net2ftp_messages["Default"] = "Förvalt";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Hjälp Guide";
$net2ftp_messages["Forums"] = "Forum";
$net2ftp_messages["License"] = "Licens";
$net2ftp_messages["Powered by"] = "Powered by";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "Du skickas nu till net2ftp forum. Dessa forum är endast till för net2ftp relaterade ämnen - inte för allmänna webhotellsfrågor.";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "Admin funktioner";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "Versions information";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "Denna version av net2ftp är up-to-date.";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "Information om senaste version kunde inte laddas ner från net2ftp.com´s server. Kontrollera säkerhetsinställningarna i din webbläsare, dessa kan hindra nedladdning av en liten fil från net2ftp.com´s server.";
$net2ftp_messages["Logging"] = "Loggar";
$net2ftp_messages["Date from:"] = "Datum från:";
$net2ftp_messages["to:"] = "till:";
$net2ftp_messages["Empty logs"] = "Töm logg";
$net2ftp_messages["View logs"] = "Visa logg";
$net2ftp_messages["Go"] = "Kör";
$net2ftp_messages["Setup MySQL tables"] = "Setup MySQL tabell";
$net2ftp_messages["Create the MySQL database tables"] = "Skapa MySQL databastabell";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "Admin funktioner";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "Hanteringen av filen %1\$s kunde inte startas.";
$net2ftp_messages["The file %1\$s could not be opened."] = "Filen %1\$s kunde inte öppnas.";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "Hanteringen av filen %1\$s kunde inte avslutas.";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "Anslutningen till servern <b>%1\$s</b> kunde inte upprättas. kontrollera databasinställningarna du angett.";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "Kunde inte välja databasen <b>%1\$s</b>.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "SQL fråga nr <b>%1\$s</b> kunde inte utföras.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "SQL fråga nr <b>%1\$s</b> utförd framgångsrikt.";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "Fyll i dina MySQL inställningar:";
$net2ftp_messages["MySQL username"] = "MySQL användarnamn";
$net2ftp_messages["MySQL password"] = "MySQL lösenord";
$net2ftp_messages["MySQL database"] = "MySQL databas";
$net2ftp_messages["MySQL server"] = "MySQL server";
$net2ftp_messages["This SQL query is going to be executed:"] = "Denna SQL fråga kommer utföras:";
$net2ftp_messages["Execute"] = "Utför";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "Använda inställningar:";
$net2ftp_messages["MySQL password length"] = "MySQL lösenord längd";
$net2ftp_messages["Results:"] = "Resultat:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "Admin funktioner";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "Kunde inte utföra SQL fråga <b>%1\$s</b>.";
$net2ftp_messages["No data"] = "Inga data";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "Admin funktioner";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "Tabellen <b>%1\$s</b> tömdes.";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "Tabellen <b>%1\$s</b> kunde inte tömmas.";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "Tabellen <b>%1\$s</b> optimerades.";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "Tabellen <b>%1\$s</b> kunde inte optimeras.";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "Avancerade funktioner";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "Kör";
$net2ftp_messages["Disabled"] = "Avstängd";
$net2ftp_messages["Advanced FTP functions"] = "Avancerade FTP funktioner";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "Skicka godtyckligt FTP kommando till FTP servern";
$net2ftp_messages["This function is available on PHP 5 only"] = "Denna funktion är endast tillgänglig med PHP 5";
$net2ftp_messages["Troubleshooting functions"] = "Felsökningsfunktioner";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "Felsök net2ftp på denna webbserver";
$net2ftp_messages["Troubleshoot an FTP server"] = "Felsök en FTP server";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Testa net2ftp listanalysregler";
$net2ftp_messages["Translation functions"] = "Översättningsfunktioner";
$net2ftp_messages["Introduction to the translation functions"] = "Introduktion till översättningsfunktioner";
$net2ftp_messages["Extract messages to translate from code files"] = "Extrhera meddelande för att översätta från kodade filer";
$net2ftp_messages["Check if there are new or obsolete messages"] = "Sök nya och gamla meddelanden";

$net2ftp_messages["Beta functions"] = "Beta funktioner";
$net2ftp_messages["Send a site command to the FTP server"] = "Skicka site command till FTP servern";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: lösenordsskydda ett bibliotek, skapa egna felsidor";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: utför en SQL fråga";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "Site command funktioner inte tillgängliga på denna webbserver.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "Apache funktioner inte tillgängliga på denna webbserver.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "MySQL funktioner inte tillgängliga på denna webbserver.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "Oväntad state2 rad. Avslutar.";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "Felsök en FTP server";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "Anslutningsinställningar:";
$net2ftp_messages["FTP server"] = "FTP server";
$net2ftp_messages["FTP server port"] = "FTP server port";
$net2ftp_messages["Username"] = "Användarnamn";
$net2ftp_messages["Password"] = "Lösenord";
$net2ftp_messages["Password length"] = "Lösenord längd";
$net2ftp_messages["Passive mode"] = "Passive mode";
$net2ftp_messages["Directory"] = "Bibliotek";
$net2ftp_messages["Printing the result"] = "Skriver ut resultat";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "Ansluter till FTP servern: ";
$net2ftp_messages["Logging into the FTP server: "] = "Loggar in på FTP servern: ";
$net2ftp_messages["Setting the passive mode: "] = "Ställer in passive mode: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Mottar FTP server system typ: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Ändrar till bibliotek %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Biblioteket från FTP servern är: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Mottar rålista med bibliotek och filer: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Försöker en andra gång att motta rålistan med bibliotek och filer: ";
$net2ftp_messages["Closing the connection: "] = "Stänger anslutning: ";
$net2ftp_messages["Raw list of directories and files:"] = "Rålista med bibliotek och filer:";
$net2ftp_messages["Parsed list of directories and files:"] = "Analyserad lista med bibliotek och filer:";

$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "inte OK";

} // end advanced_ftpserver


// -------------------------------------------------------------------------
// Advanced_parsing module
if ($net2ftp_globals["state"] == "advanced_parsing") {
// -------------------------------------------------------------------------

$net2ftp_messages["Test the net2ftp list parsing rules"] = "Testa net2ftp listanalysregler";
$net2ftp_messages["Sample input"] = "Exempelinmatning";
$net2ftp_messages["Parsed output"] = "Analyserat resultat";

} // end advanced_parsing


// -------------------------------------------------------------------------
// Advanced_webserver module
if ($net2ftp_globals["state"] == "advanced_webserver") {
// -------------------------------------------------------------------------

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "Felsök din net2ftp installation";
$net2ftp_messages["Printing the result"] = "Skriver ut resultat";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "Kontrollerar om FTP modul i PHP är installerad: ";
$net2ftp_messages["yes"] = "ja";
$net2ftp_messages["no - please install it!"] = "nej - installera den!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "Kontrollerar rättigheter för biblioteket på webbservern: en liten fil kommer skrivas till /temp mappen och sen raderas.";
$net2ftp_messages["Creating filename: "] = "Skapar filenamn: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "OK. Filnamn: %1\$s";
$net2ftp_messages["not OK"] = "inte OK";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "inte OK. Kontrollera rättigheter för %1\$s biblioteket";
$net2ftp_messages["Opening the file in write mode: "] = "Öppnar filen i skrivläge: ";
$net2ftp_messages["Writing some text to the file: "] = "Skriver text till filen: ";
$net2ftp_messages["Closing the file: "] = "Stänger filen: ";
$net2ftp_messages["Deleting the file: "] = "Raderar filen: ";

$net2ftp_messages["Testing the FTP functions"] = "Testar FTP funktioner";
$net2ftp_messages["Connecting to a test FTP server: "] = "Ansluter till en test FTP server: ";
$net2ftp_messages["Connecting to the FTP server: "] = "Ansluter till FTP servern: ";
$net2ftp_messages["Logging into the FTP server: "] = "Loggar in på FTP servern: ";
$net2ftp_messages["Setting the passive mode: "] = "Ställer in passive mode: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Mottar FTP server system typ: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Ändrar till bibliotek %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Biblioteket från FTP servern är: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Mottar rålista med bibliotek och filer: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Försöker en andra gång att motta rålistan med bibliotek och filer: ";
$net2ftp_messages["Closing the connection: "] = "Stänger anslutning: ";
$net2ftp_messages["Raw list of directories and files:"] = "Rålista med bibliotek och filer:";
$net2ftp_messages["Parsed list of directories and files:"] = "Analyserad lista med bibliotek och filer:";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "inte OK";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "Lägg till som bokmärke:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: höger-klicka på länken och välj \"Lägg till i Favoriter...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: höger-klicka på länken och välj \"Lägg till bokmärke...\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "Anmärkning: när du använder detta bokmärke, kommer ett popup fönster öppnas och fråga efter användarnamn och lösenord.";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "Välj bibliotek";
$net2ftp_messages["Please wait..."] = "Vänta...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "Bibliotek med namn som innehåller \' kan inte visas korrekt. De kan bara raderas. Backa och välj ett annat underbibliotek.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "Daglig gräns uppnådd: du kommer inte kunna överföra data";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "För att garantera rättvist användande av webbservern för alla, är dataöverföringsvolymer och scriptkörningar begränsade för alla användare och per dag. Vid uppnådd gräns kan du fortfarande navigera på FTP servern men inte överföra data till/från den.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "Behöver du obegränsat användande, Installera net2ftp på din egen webbserver.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "Nytt dir";
$net2ftp_messages["New file"] = "Ny fil";
$net2ftp_messages["HTML templates"] = "HTML mallar";
$net2ftp_messages["Upload"] = "Upload";
$net2ftp_messages["Java Upload"] = "Java Upload";
$net2ftp_messages["Flash Upload"] = "Flash Upload";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Advanced"] = "Avancerat";
$net2ftp_messages["Copy"] = "Kopiera";
$net2ftp_messages["Move"] = "Flytta";
$net2ftp_messages["Delete"] = "Radera";
$net2ftp_messages["Rename"] = "Byt namn";
$net2ftp_messages["Chmod"] = "Chmod";
$net2ftp_messages["Download"] = "Download";
$net2ftp_messages["Unzip"] = "Unzip";
$net2ftp_messages["Zip"] = "Zip";
$net2ftp_messages["Size"] = "Storlek";
$net2ftp_messages["Search"] = "Sök";
$net2ftp_messages["Go to the parent directory"] = "Gå till huvudbibliotek";
$net2ftp_messages["Go"] = "Kör";
$net2ftp_messages["Transform selected entries: "] = "Omvandla valda inmatningar: ";
$net2ftp_messages["Transform selected entry: "] = "Omvandla vald inmatning: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "Skapa nytt underbibliotek i bibliotek %1\$s";
$net2ftp_messages["Create a new file in directory %1\$s"] = "Skapa ny fil i bibliotek %1\$s";
$net2ftp_messages["Create a website easily using ready-made templates"] = "Skapa webbsida lätt med färdiga mallar";
$net2ftp_messages["Upload new files in directory %1\$s"] = "Ladda upp nya filer i bibliotek %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Ladda upp bibliotek och filer med en Java applet";
$net2ftp_messages["Upload files using a Flash applet"] = "Upload files using a Flash applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Installera mjukvarupaket (kräver PHP på webbservern)";
$net2ftp_messages["Go to the advanced functions"] = "Gå till avancerade funktioner";
$net2ftp_messages["Copy the selected entries"] = "Kopiera valda inmatningar";
$net2ftp_messages["Move the selected entries"] = "Flytta valda inmatningar";
$net2ftp_messages["Delete the selected entries"] = "Radera valda inmatningar";
$net2ftp_messages["Rename the selected entries"] = "Döp om valda inmatningar";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "Chmod valda inmatningar (fungerar endast på Unix/Linux/BSD serverar)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "Ladda ner en zip fil innehållande alla valda inmatningar";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Unzip valda arkiv på FTP servern";
$net2ftp_messages["Zip the selected entries to save or email them"] = "Zippa valda inmatningar för att spara eller skicka med email";
$net2ftp_messages["Calculate the size of the selected entries"] = "Beräkna storlek på valda inmatningar";
$net2ftp_messages["Find files which contain a particular word"] = "Hitta filer som innehåller ett särskillt ord";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "Klicka för att sortera efter %1\$s i fallande ordning";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "Klicka för att sortera efter %1\$s i stigande ordning";
$net2ftp_messages["Ascending order"] = "Stigande ordning";
$net2ftp_messages["Descending order"] = "Fallande ordning";
$net2ftp_messages["Upload files"] = "Upload files";
$net2ftp_messages["Up"] = "Upp";
$net2ftp_messages["Click to check or uncheck all rows"] = "Klicka för att markera eller avmarkera alla rader";
$net2ftp_messages["All"] = "Alla";
$net2ftp_messages["Name"] = "Namn";
$net2ftp_messages["Type"] = "Typ";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "Ägare";
$net2ftp_messages["Group"] = "Grupp";
$net2ftp_messages["Perms"] = "Rättigh";
$net2ftp_messages["Mod Time"] = "Mod Tid";
$net2ftp_messages["Actions"] = "Funktion";
$net2ftp_messages["Select the directory %1\$s"] = "Välj bibliotek %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "Välj filen %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "Välj symlink %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "Gå till underbibliotek %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "Ladda ner filen %1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "Följ symlink %1\$s";
$net2ftp_messages["View"] = "Visa";
$net2ftp_messages["Edit"] = "Ändra";
$net2ftp_messages["Update"] = "Upddatera";
$net2ftp_messages["Open"] = "Öppna";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Visa markerad källkod för fil %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Ändra källkod för fil %1\$s";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "Ladda upp ny version av filen %1\$s och förena ändringar";
$net2ftp_messages["View image %1\$s"] = "Visa bild %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "Visa filen %1\$s från din HTTP webbserver";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(Notering: Denna länk kanske inte fungerar om du inte har eget domännamn.)";
$net2ftp_messages["This folder is empty"] = "Denna mapp är tom";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "Bibliotek";
$net2ftp_messages["Files"] = "Filer";
$net2ftp_messages["Symlinks"] = "Symlinks";
$net2ftp_messages["Unrecognized FTP output"] = "Okänd FTP utdata";
$net2ftp_messages["Number"] = "Nummer";
$net2ftp_messages["Size"] = "Storlek";
$net2ftp_messages["Skipped"] = "Hoppa över";
$net2ftp_messages["Data transferred from this IP address today"] = "Data transferred from this IP address today";
$net2ftp_messages["Data transferred to this FTP server today"] = "Data transferred to this FTP server today";

// printLocationActions()
$net2ftp_messages["Language:"] = "Språk:";
$net2ftp_messages["Skin:"] = "Skin:";
$net2ftp_messages["View mode:"] = "Visa mode:";
$net2ftp_messages["Directory Tree"] = "Bibliotek";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "Utför %1\$s i ett nytt fönster";
$net2ftp_messages["This file is not accessible from the web"] = "Ingen åtkomst av denna fil från webben";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "Dubbel-klicka för attgå till underbibliotek:";
$net2ftp_messages["Choose"] = "Välj";
$net2ftp_messages["Up"] = "Upp";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "Storlek av valda bibliotek och filer";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "Total storlek av valda bibliotek och filer är:";
$net2ftp_messages["The number of files which were skipped is:"] = "Antal filer som hoppades över är:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "Chmod bibliotek och filer";
$net2ftp_messages["Set all permissions"] = "Ställ in alla behörigheter";
$net2ftp_messages["Read"] = "Läs";
$net2ftp_messages["Write"] = "Skriv";
$net2ftp_messages["Execute"] = "Utför";
$net2ftp_messages["Owner"] = "Ägare";
$net2ftp_messages["Group"] = "Grupp";
$net2ftp_messages["Everyone"] = "Alla";
$net2ftp_messages["To set all permissions to the same values, enter those permissions above and click on the button \"Set all permissions\""] = "För att ställa in samma värde på alla behörigheter, Skriv i behörigheter ovanför och klicka på knappen \"Ställ in alla behörigheter\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "Ställ in behörigheter för bibliotek <b>%1\$s</b> till: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "Ställ in behörigheter för fil <b>%1\$s</b> till: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "Ställ in behörigheter för symlink <b>%1\$s</b> till: ";
$net2ftp_messages["Chmod value"] = "Chmod värde";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "Chmod även underbibliotek i detta bibliotek";
$net2ftp_messages["Chmod also the files within this directory"] = "Chmod även filer i detta bibliotek";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "Chmod nr <b>%1\$s</b> är utanför intervallet 000-777. Försök igen.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "Välj bibliotek";
$net2ftp_messages["Copy directories and files"] = "Kopiera bibliotek och filer";
$net2ftp_messages["Move directories and files"] = "Flytta bibliotek och filer";
$net2ftp_messages["Delete directories and files"] = "Radera bibliotek och filer";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "Är du säker du vill radera dessa bibliotek och filer?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "Alla underbibliotek och filer i de valda biblioteken kommer också raderas!";
$net2ftp_messages["Set all targetdirectories"] = "Ange målbibliotek";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "För att ställa in ett vanligt målbibliotek, ange det målbiblioteket i textfältet ovan och klicka på knappen \"Ange alla målbibliotek\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Notering: målbiblioteket måste vara skapat innan något kan kopieras in i det.";
$net2ftp_messages["Different target FTP server:"] = "Annan målFTP server:";
$net2ftp_messages["Username"] = "Användarnamn";
$net2ftp_messages["Password"] = "Lösenord";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "Lämna tomt om du vill kopiera filerna till samma FTP server.";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "Om du vill kopiera filerna till en annan FTP server, ange dina inloggningsuppgifter.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "Lämna tomt om du vill flytta filer till samma FTP server.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "Om du vill flytta filer till en annan FTP server, ange dina inloggningsuppgifter.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "Kopiera bibliotek <b>%1\$s</b> till:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "Flytta bibliotek <b>%1\$s</b> till:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "Bibliotek <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "Kopiera fil <b>%1\$s</b> till:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "Flytta fil <b>%1\$s</b> till:";
$net2ftp_messages["File <b>%1\$s</b>"] = "Fil <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "Kopiera symlink <b>%1\$s</b> till:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "Flytta symlink <b>%1\$s</b> till:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Symlink <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "Målbibliotek:";
$net2ftp_messages["Target name:"] = "Målnamn:";
$net2ftp_messages["Processing the entries:"] = "Bearbetar inmatningarna:";

} // end copymovedelete


// -------------------------------------------------------------------------
// Download file module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// EasyWebsite module
if ($net2ftp_globals["state"] == "easyWebsite") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create a website in 4 easy steps"] = "Skapa webbsite i 4 enkla steg";
$net2ftp_messages["Template overview"] = "Mallöversikt";
$net2ftp_messages["Template details"] = "Mallegenskaper";
$net2ftp_messages["Files are copied"] = "Filer är kopierade";
$net2ftp_messages["Edit your pages"] = "Ändra dina sidor";

// Screen 1 - printTemplateOverview
$net2ftp_messages["Click on the image to view the details of a template."] = "Klicka på bilden för att visa mallegenskaper.";
$net2ftp_messages["Back to the Browse screen"] = "Tillbaka till bläddringsskärm";
$net2ftp_messages["Template"] = "Mall";
$net2ftp_messages["Copyright"] = "Copyright";
$net2ftp_messages["Click on the image to view the details of this template"] = "Klick på bilden för att visa egenskaper för denna mall";

// Screen 2 - printTemplateDetails
$net2ftp_messages["The template files will be copied to your FTP server. Existing files with the same filename will be overwritten. Do you want to continue?"] = "Mallfiler kommer att kopieras till din FTP server. Existerande filer med samma namn kommer att skrivas över. Vill du fortsätta?";
$net2ftp_messages["Install template to directory: "] = "Installera mall i bibliotek: ";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Size"] = "Storlek";
$net2ftp_messages["Preview page"] = "Översiktssida";
$net2ftp_messages["opens in a new window"] = "öppnas i nytt fönster";

// Screen 3
$net2ftp_messages["Please wait while the template files are being transferred to your server: "] = "Vänta medans mallfiler överförs till din server: ";
$net2ftp_messages["Done."] = "Färdig.";
$net2ftp_messages["Continue"] = "Fortsätt";

// Screen 4 - printEasyAdminPanel
$net2ftp_messages["Edit page"] = "Ändra sida";
$net2ftp_messages["Browse the FTP server"] = "Bläddra i FTP servern";
$net2ftp_messages["Add this link to your favorites to return to this page later on!"] = "Lägg till denna länk i dina favoriter för att återvända till denna sida senare!";
$net2ftp_messages["Edit website at %1\$s"] = "Ändra website hos %1\$s";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: höger-klicka på länken och välj \"Lägg till i Favoriter...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: höger-klicka på länken och välj \"Lägg till bokmärke...\"";

// ftp_copy_local2ftp
$net2ftp_messages["WARNING: Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing..."] = "VARNING: Kunde inte skapa underbibliotek <b>%1\$s</b>. Det kan redan finnas. Fortsätter...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Skapat målunderbibliotek <b>%1\$s</b>";
$net2ftp_messages["WARNING: Unable to copy the file <b>%1\$s</b>. Continuing..."] = "WARNING: Unable to copy the file <b>%1\$s</b>. Continuing...";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Copied file <b>%1\$s</b>";
}


// -------------------------------------------------------------------------
// Edit module
if ($net2ftp_globals["state"] == "edit") {
// -------------------------------------------------------------------------

// /modules/edit/edit.inc.php
$net2ftp_messages["Unable to open the template file"] = "Kunde inte öppna mallfilen";
$net2ftp_messages["Unable to read the template file"] = "kunde inte läsa mallfilen";
$net2ftp_messages["Please specify a filename"] = "Specificera filnamn";
$net2ftp_messages["Status: This file has not yet been saved"] = "Status: Denna fil är inte sparad";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "Status: Sparad på <b>%1\$s</b> med mode %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "Status: <b>Denna fil kunde inte sparas</b>";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "Bibliotek: ";
$net2ftp_messages["File: "] = "Fil: ";
$net2ftp_messages["New file name: "] = "Nytt filnamn: ";
$net2ftp_messages["Character encoding: "] = "Character encoding: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "Notering: förändring av textareatyp kommer sparas";
$net2ftp_messages["Copy up"] = "Kopiera upp";
$net2ftp_messages["Copy down"] = "Kopiera ner";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "Sök bibliotek och filer";
$net2ftp_messages["Search again"] = "Sök igen";
$net2ftp_messages["Search results"] = "Sökresultat";
$net2ftp_messages["Please enter a valid search word or phrase."] = "Skriv in giltigt sökord eller fras.";
$net2ftp_messages["Please enter a valid filename."] = "Skriv in giltigt filnamn.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "Skriv in giltig storlek i \"från\" textruta, till exempel 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "Skriv in giltig storlek i \"till\" textruta, till exempel 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "Skriv in giltigt datum i Å-m-d format i \"från\" textruta.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "Skriv in giltigt datum i Å-m-d format i \"till\" textruta.";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "Ordet <b>%1\$s</b> hittades inte i de valda biblioteken och filerna.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "Ordet <b>%1\$s</b> hittades i följande filer:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "Sök ord eller fras";
$net2ftp_messages["Case sensitive search"] = "Case sensitive sökning";
$net2ftp_messages["Restrict the search to:"] = "Begränsa sökningen till:";
$net2ftp_messages["files with a filename like"] = "filer med filnamn som";
$net2ftp_messages["(wildcard character is *)"] = "(wildcard tecken är *)";
$net2ftp_messages["files with a size"] = "filer med storlek";
$net2ftp_messages["files which were last modified"] = "filer som var senast ändrade";
$net2ftp_messages["from"] = "från";
$net2ftp_messages["to"] = "till";

$net2ftp_messages["Directory"] = "Bibliotek";
$net2ftp_messages["File"] = "Fil";
$net2ftp_messages["Line"] = "Rad";
$net2ftp_messages["Action"] = "Funktion";
$net2ftp_messages["View"] = "Visa";
$net2ftp_messages["Edit"] = "Ändra";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Visa markerad källkod för fil %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Ändra källkod för fil %1\$s";

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
$net2ftp_messages["Install software packages"] = "Installera mjukvarupaket";
$net2ftp_messages["Unable to open the template file"] = "Kunde inte öppna mallfilen";
$net2ftp_messages["Unable to read the template file"] = "kunde inte läsa mallfilen";
$net2ftp_messages["Unable to get the list of packages"] = "Kunde inte motta lisa med paket";

// /skins/blue/install1.template.php
$net2ftp_messages["The net2ftp installer script has been copied to the FTP server."] = "net2ftp installationsscript har kopierats till FTP servern.";
$net2ftp_messages["This script runs on your web server and requires PHP to be installed."] = "Detta script körs på din webbserver och kräver att PHP installeras.";
$net2ftp_messages["In order to run it, click on the link below."] = "För att köra det, klicka på länken nedan.";
$net2ftp_messages["net2ftp has tried to determine the directory mapping between the FTP server and the web server."] = "net2ftp har försökt bestämma bibliotekskopplingar mellan FTP server och webbserver.";
$net2ftp_messages["Should this link not be correct, enter the URL manually in your web browser."] = "Är denna länk fel, skriv URL manuellt i din webbläsare.";

} // end install


// -------------------------------------------------------------------------
// Java upload module
if ($net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload directories and files using a Java applet"] = "Ladda upp bibliotek och filer med en Java applet";
$net2ftp_messages["Number of files:"] = "Antal filer:";
$net2ftp_messages["Size of files:"] = "Storlek på filer:";
$net2ftp_messages["Add"] = "Lägg till";
$net2ftp_messages["Remove"] = "Radera";
$net2ftp_messages["Upload"] = "Upload";
$net2ftp_messages["Add files to the upload queue"] = "Lägg till filer i uppladdningskön";
$net2ftp_messages["Remove files from the upload queue"] = "Radera filer från uppladdningskön";
$net2ftp_messages["Upload the files which are in the upload queue"] = "Ladda upp filerna i uppladdningskön";
$net2ftp_messages["Maximum server space exceeded. Please select less/smaller files."] = "Maximalt serverutrymme överskridet. Välj färre/mindre filer.";
$net2ftp_messages["Total size of the files is too big. Please select less/smaller files."] = "Total storlek för filer för stor. Välj färre/mindre filer.";
$net2ftp_messages["Total number of files is too high. Please select fewer files."] = "Totalt antal filers för högt. Välj färre filer.";
$net2ftp_messages["Note: to use this applet, Sun's Java plugin must be installed (version 1.4 or newer)."] = "Notering: för att använda denna applet, måste Sun's Java plugin vara installerat (version 1.4 eller nyare).";

} // end jupload



// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login") {
// -------------------------------------------------------------------------
$net2ftp_messages["Login!"] = "Login!";
$net2ftp_messages["Once you are logged in, you will be able to:"] = "När du loggat in, kan du:";
$net2ftp_messages["Navigate the FTP server"] = "Navigera på FTP servern";
$net2ftp_messages["Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."] = "När du loggat in,kan du bläddra från bibliotek till bibliotek och se alla underbibliotek och filer.";
$net2ftp_messages["Upload files"] = "Upload files";
$net2ftp_messages["There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet."] = "Det finns 3 olika sätt att ladda upp filer: standard uppladdningsformulär, uppladdnings-och-unzip funktionen, och en Java Applet.";
$net2ftp_messages["Download files"] = "Download files";
$net2ftp_messages["Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive."] = "Klicka på ett filnamn för att snabbt ladda ner en fil.<br />Välj flera filer och klicka på Ladda ner; valda filer laddas ner i ett zip arkiv.";
$net2ftp_messages["Zip files"] = "Zip files";
$net2ftp_messages["... and save the zip archive on the FTP server, or email it to someone."] = "... och spara zip arkivet på FTP servern, eller emaila det till någon.";
$net2ftp_messages["Unzip files"] = "Unzip files";
$net2ftp_messages["Different formats are supported: .zip, .tar, .tgz and .gz."] = "Different formats are supported: .zip, .tar, .tgz and .gz.";
$net2ftp_messages["Install software"] = "Install software";
$net2ftp_messages["Choose from a list of popular applications (PHP required)."] = "Choose from a list of popular applications (PHP required).";
$net2ftp_messages["Copy, move and delete"] = "Copy, move and delete";
$net2ftp_messages["Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted."] = "Bibliotek hanteras enhetligt, allt innehåll (underbibliotek och filer) kommer också kopieras, flyttas eller raderas.";
$net2ftp_messages["Copy or move to a 2nd FTP server"] = "Copy or move to a 2nd FTP server";
$net2ftp_messages["Handy to import files to your FTP server, or to export files from your FTP server to another FTP server."] = "Enkelt at importera filer till din FTP server, eller att exportera filer från din FTP server till en annan FTP server.";
$net2ftp_messages["Rename and chmod"] = "Byt namn och chmod";
$net2ftp_messages["Chmod handles directories recursively."] = "Chmod hanterar bibliotek enhetligt.";
$net2ftp_messages["View code with syntax highlighting"] = "Visa kod med syntaxmarkering";
$net2ftp_messages["PHP functions are linked to the documentation on php.net."] = "PHP funktioner är länkade till dokumentationen av php.net.";
$net2ftp_messages["Plain text editor"] = "Enkel texteditor";
$net2ftp_messages["Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server."] = "Ändra text direkt från din webbläsare; varje gång du sparar förs den nya filen över till FTP servern.";
$net2ftp_messages["HTML editors"] = "HTML editors";
$net2ftp_messages["Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from."] = "Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from.";
$net2ftp_messages["Code editor"] = "Code editor";
$net2ftp_messages["Edit HTML and PHP in an editor with syntax highlighting."] = "Ändra HTML och PHP i en editor med syntaxmarkering.";
$net2ftp_messages["Search for words or phrases"] = "Sök efter ord eller fraser";
$net2ftp_messages["Filter out files based on the filename, last modification time and filesize."] = "Filtrera ut filer baserat på filnamnet, tid för senast modifierat och filstorlek.";
$net2ftp_messages["Calculate size"] = "Beräkna storlek";
$net2ftp_messages["Calculate the size of directories and files."] = "Beräkna storlek på bibliotek och filer.";

$net2ftp_messages["FTP server"] = "FTP server";
$net2ftp_messages["Example"] = "Exempel";
$net2ftp_messages["Port"] = "Port";
$net2ftp_messages["Username"] = "Användarnamn";
$net2ftp_messages["Password"] = "Lösenord";
$net2ftp_messages["Anonymous"] = "Anonym";
$net2ftp_messages["Passive mode"] = "Passive mode";
$net2ftp_messages["Initial directory"] = "Initialt bibliotek";
$net2ftp_messages["Language"] = "Språk";
$net2ftp_messages["Skin"] = "Skin";
$net2ftp_messages["FTP mode"] = "FTP mode";
$net2ftp_messages["Automatic"] = "Automatiskt";
$net2ftp_messages["Login"] = "Logga in";
$net2ftp_messages["Clear cookies"] = "Rensa cookies";
$net2ftp_messages["Admin"] = "Admin";
$net2ftp_messages["Please enter an FTP server."] = "Skriv in FTP server.";
$net2ftp_messages["Please enter a username."] = "Skriv in användarnamn.";
$net2ftp_messages["Please enter a password."] = "Skriv in lösenord.";

} // end login


// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login_small") {
// -------------------------------------------------------------------------

$net2ftp_messages["Please enter your Administrator username and password."] = "Skriv in ditt Administratörs användarnamn och lösenord.";
$net2ftp_messages["Please enter your username and password for FTP server <b>%1\$s</b>."] = "Please enter your username and password for FTP server <b>%1\$s</b>.";
$net2ftp_messages["Username"] = "Användarnamn";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Password"] = "Lösenord";
$net2ftp_messages["Login"] = "Logga in";
$net2ftp_messages["Continue"] = "Fortsätt";

} // end login_small


// -------------------------------------------------------------------------
// Logout module
if ($net2ftp_globals["state"] == "logout") {
// -------------------------------------------------------------------------

// logout.inc.php
$net2ftp_messages["Login page"] = "Login sida";

// logout.template.php
$net2ftp_messages["You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>."] = "You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>.";
$net2ftp_messages["Note: other users of this computer could click on the browser's Back button and access the FTP server."] = "Notering: andra användare på denna dator kan använda webbläsarens bakåtknapp och komma in på FTP servern.";
$net2ftp_messages["To prevent this, you must close all browser windows."] = "För att hindra detta måste du stänga alla öppna webbläsarfönster.";
$net2ftp_messages["Close"] = "Stäng";
$net2ftp_messages["Click here to close this window"] = "Klicka här för att stänga detta fönster";

} // end logout


// -------------------------------------------------------------------------
// New directory module
if ($net2ftp_globals["state"] == "newdir") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create new directories"] = "Skapa nya bibliotek";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "De nya biblioteken kommer skapas i <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "Nytt biblioteks namn:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "Bibliotek <b>%1\$s</b> skapades framgångsrikt.";
$net2ftp_messages["Directory <b>%1\$s</b> could not be created."] = "Bibliotek <b>%1\$s</b> kunde inte skapas.";

} // end newdir


// -------------------------------------------------------------------------
// Raw module
if ($net2ftp_globals["state"] == "raw") {
// -------------------------------------------------------------------------

// /modules/raw/raw.inc.php
$net2ftp_messages["Send arbitrary FTP commands"] = "Skicka slumpmässig FTP kommando";


// /skins/[skin]/raw1.template.php
$net2ftp_messages["List of commands:"] = "Lista med kommandon:";
$net2ftp_messages["FTP server response:"] = "FTP server svar:";

} // end raw


// -------------------------------------------------------------------------
// Rename module
if ($net2ftp_globals["state"] == "rename") {
// -------------------------------------------------------------------------
$net2ftp_messages["Rename directories and files"] = "Byt namn på bibliotek och filer";
$net2ftp_messages["Old name: "] = "Gammalt namn: ";
$net2ftp_messages["New name: "] = "Nytt namn: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "Det nya namnet får inte innehålla punkter. Denna inmatning döptes om till <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> döptes framgångsrikt om till <b>%2\$s</b>";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> kunde inte döpas om till <b>%2\$s</b>";

} // end rename


// -------------------------------------------------------------------------
// Unzip module
if ($net2ftp_globals["state"] == "unzip") {
// -------------------------------------------------------------------------

// /modules/unzip/unzip.inc.php
$net2ftp_messages["Unzip archives"] = "Unzip arkive";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Mottar arkiv %1\$s av %2\$s från FTP server";
$net2ftp_messages["Unable to get the archive <b>%1\$s</b> from the FTP server"] = "Kunde inte ladda ner arkivet <b>%1\$s</b> från FTP servern";

// /skins/[skin]/unzip1.template.php
$net2ftp_messages["Set all targetdirectories"] = "Ange målbibliotek";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "För att ställa in ett vanligt målbibliotek, ange det målbiblioteket i textfältet ovan och klicka på knappen \"Ange alla målbibliotek\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Notering: målbiblioteket måste vara skapat innan något kan kopieras in i det.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Unzip arkiv <b>%1\$s</b> till:";
$net2ftp_messages["Target directory:"] = "Målbibliotek:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Använd mappnamn (skapar underbibliotek automatiskt)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "Update file";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>VARNING: DENNA FUNKTION ÄR UNDER UTVECKLING. ANVÄND DEN BARA PÅ TESTFILER! DU HAR BLIVIT VARNAD!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "Kända fel: - radera tab tecken - fungerar inte bra i stora filer (> 50kB) - inte ännu testad på filer innehållande icke-standard tecken</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "Denna funktion tillåter uppladdning av nyare version av vald fil, för att visa vilka förändringar och att acceptera eller neka varje ändring. Innan något sparas, kan du ändra den sammanfogade filerna.";
$net2ftp_messages["Old file:"] = "Gammal fil:";
$net2ftp_messages["New file:"] = "Ny fil:";
$net2ftp_messages["Restrictions:"] = "Begränsningar:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Maximal storlek för en fil är begränsad av net2ftp till <b>%1\$s kB</b> och av PHP till <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Maximal exekveringstid är <b>%1\$s sekunder</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "FTP transfer mode (ASCII eller BINARY) kommer automatiskt bestämmas, baserat på filenamnsändelse";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Om destinationsfil redan finns, kommer den skrivas över";
$net2ftp_messages["You did not provide any files or archives to upload."] = "Du angav inga filer eller arkiv för uppladdning.";
$net2ftp_messages["Unable to delete the new file"] = "Kunde inte radera den nya filen";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "Vänta...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "Välj rad nedan, acceptera eller neka ändringar och skicka formulär.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "Ladda upp till bibliotek:";
$net2ftp_messages["Files"] = "Filer";
$net2ftp_messages["Archives"] = "Arkive";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "Filer inmatade här kommer överföras till FTP servern.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "Arkive inmatade här kommer dekomprimeras och filer inuti överföras till FTP servern.";
$net2ftp_messages["Add another"] = "Lägg till nästa";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Använd mappnamn (skapar underbibliotek automatiskt)";

$net2ftp_messages["Choose a directory"] = "Välj bibliotek";
$net2ftp_messages["Please wait..."] = "Vänta...";
$net2ftp_messages["Uploading... please wait..."] = "Laddar upp... vänta...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "Om uppladdning tar mer än tillåtna <b>%1\$s sekunder<\/b>, får du försöka igen med färre/mindre filer.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "Detta fönster stängs automatiskt om några sekunder.";
$net2ftp_messages["Close window now"] = "Stäng fönster nu";

$net2ftp_messages["Upload files and archives"] = "Ladda upp filer och arkiv";
$net2ftp_messages["Upload results"] = "Uppladdningsresultat";
$net2ftp_messages["Checking files:"] = "Kontrollerar filer:";
$net2ftp_messages["Transferring files to the FTP server:"] = "Överför filer till FTP servern:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "Dekomprimerar arkiv och överför filer till FTP servern:";
$net2ftp_messages["Upload more files and archives"] = "Ladda upp fler filer och arkiv";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "Begränsningar:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Maximal storlek för en fil är begränsad av net2ftp till <b>%1\$s kB</b> och av PHP till <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Maximal exekveringstid är <b>%1\$s sekunder</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "FTP transfer mode (ASCII eller BINARY) kommer automatiskt bestämmas, baserat på filenamnsändelse";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Om destinationsfil redan finns, kommer den skrivas över";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "Visa fil %1\$s";
$net2ftp_messages["View image %1\$s"] = "Visa bild %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "Visa Macromedia ShockWave Flash film %1\$s";
$net2ftp_messages["Image"] = "Bild";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntax markerad powered by <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "För att spara bild, höger-klicka på den och välj 'Spara bild som...'";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "Zip entries";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "Spara zip fil på FTP servern som:";
$net2ftp_messages["Email the zip file in attachment to:"] = "Email zip fil som bilaga till:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "Notera att skicka filer ej är anonymt: din IP adress och tid vid avsändande läggs till i e-mailet.";
$net2ftp_messages["Some additional comments to add in the email:"] = "Lägg till övriga kommentarer i email:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "Du angav inget filnamn för zipfilen. Backa och ange filnamn.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "Email adressen du angav (%1\$s) verkar inte vara giltig.<br />Ange en adress i formatet <b>användarnamn@domän.com</b>";

} // end zip

?>