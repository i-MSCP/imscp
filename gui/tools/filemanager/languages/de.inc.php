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
$net2ftp_messages["en"] = "de";

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

$net2ftp_messages["Connecting to the FTP server"] = "Verbindung zum FTP-Server wird hergestellt";
$net2ftp_messages["Logging into the FTP server"] = "Logging into the FTP server";
$net2ftp_messages["Setting the passive mode"] = "Setting the passive mode";
$net2ftp_messages["Getting the FTP system type"] = "Getting the FTP system type";
$net2ftp_messages["Changing the directory"] = "Changing the directory";
$net2ftp_messages["Getting the current directory"] = "Getting the current directory";
$net2ftp_messages["Getting the list of directories and files"] = "Ordner- und Dateiliste wird empfangen";
$net2ftp_messages["Parsing the list of directories and files"] = "Parsing the list of directories and files";
$net2ftp_messages["Logging out of the FTP server"] = "Logging out of the FTP server";
$net2ftp_messages["Getting the list of directories and files"] = "Ordner- und Dateiliste wird empfangen";
$net2ftp_messages["Printing the list of directories and files"] = "Ordner- und Dateiliste wird erstellt";
$net2ftp_messages["Processing the entries"] = "Verarbeiten der Einträge";
$net2ftp_messages["Processing entry %1\$s"] = "Processing entry %1\$s";
$net2ftp_messages["Checking files"] = "Verarbeiten der Dateien";
$net2ftp_messages["Transferring files to the FTP server"] = "Dateien werden zum FTP-Server geschickt";
$net2ftp_messages["Decompressing archives and transferring files"] = "Archive werden entpackt und die Dateien transferriert";
$net2ftp_messages["Searching the files..."] = "Dateien werden gesucht...";
$net2ftp_messages["Uploading new file"] = "Upload der neuen Datei";
$net2ftp_messages["Reading the file"] = "Reading the file";
$net2ftp_messages["Parsing the file"] = "Parsing the file";
$net2ftp_messages["Reading the new file"] = "Lesen der neuen Datei";
$net2ftp_messages["Reading the old file"] = "Lesen der alten Datei";
$net2ftp_messages["Comparing the 2 files"] = "Vergleich der 2 Dateien";
$net2ftp_messages["Printing the comparison"] = "Ausgabe des Vergleichs";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "Sending FTP command %1\$s of %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Getting archive %1\$s of %2\$s from the FTP server";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Creating a temporary directory on the FTP server";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Setting the permissions of the temporary directory";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Copying the net2ftp installer script to the FTP server";
$net2ftp_messages["Script finished in %1\$s seconds"] = "Script beendet in %1\$s Sekunden";
$net2ftp_messages["Script halted"] = "Script angehalten";

// Used on various screens
$net2ftp_messages["Please wait..."] = "Bitte warten...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Unexpected state string: %1\$s. Exiting.";
$net2ftp_messages["This beta function is not activated on this server."] = "Dies Beta-Funktion ist auf Ihrem Server nicht aktiviert.";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "This function has been disabled by the Administrator of this website.";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead.";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "Your root directory <b>%1\$s</b> does not exist or could not be selected.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted.";
$net2ftp_messages["Execute %1\$s in a new window"] = "In einem neuen Fenster %1\$s ausführen";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "Bitte mindestens eine Datei oder ein Verzeichniss auswählen!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "Der FTP Server <b>%1\$s</b> ist nicht in der Liste der erlaubten FTP Server.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "Der FTP Server <b>%1\$s</b> ist in der Liste der verbotenen FTP Server.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "Der FTP Server Port %1\$s darf nicht genutzt werden.";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "Your IP address (%1\$s) is not in the list of allowed IP addresses.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "Ihre IP address (%1\$s) ist in der Liste der verbotenen IP Addressen.";

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
$net2ftp_messages["Unable to determine your IP address."] = "Kann Ihre IP-Adresse nicht auflösen.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "Tabelle net2ftp_log_consumption_ipaddress enthält doppelte Einträge.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "Tabelle net2ftp_log_consumption_ftpserver enthält doppelte Einträge.";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "Die Variable <b>consumption_ipaddress_datatransfer</b> ist nicht numerisch.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "Tabelle net2ftp_log_consumption_ipaddress konnte nicht aktualisiert werden.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "Tabelle net2ftp_log_consumption_ipaddress enthält doppelte Einträge.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "Tabelle net2ftp_log_consumption_ftpserver konnte nicht aktualisiert werden.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "Tabelle net2ftp_log_consumption_ftpserver enthält doppelte Einträge.";
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
$net2ftp_messages["An error has occured"] = "Ein Fehler ist aufgetreten";
$net2ftp_messages["Go back"] = "Zurück";
$net2ftp_messages["Go to the login page"] = "Zurück zur Anmeldeseite";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "Das <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP-Modul von PHP</a> ist nicht installiert.<br /><br /> Der Administrator dieser Webseite sollte das FTP-Modul installieren. Hinweise zur Installation stehen auf <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Konnte keine Verbindung zum FTP Server <b>%1\$s</b> auf Port <b>%2\$s</b> herstellen.<br /><br />Bitte Prüfen Sie die Adresse des FTP-Servers - diese unterscheidet sich oft von der Adresse des HTTP (Web) Servers. Bitte kontaktieren Sie die Hotline Ihres Providers oder Ihren Systemadministrator.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Anmeldung am FTP Server  <b>%1\$s</b> mit Benutzername <b>%2\$s</b> fehlgeschlagen.<br /><br />Bitte prüfen Sie Ihren Benutzernamen und das Kennwort. Kontaktieren Sie die Hotline Ihres Providers oder Fragen Sie Ihren Systemadministrator.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "Konnte nicht in den passiven Modus auf dem FTP-Server <b>%1\$s</b> wechseln.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Konnte nicht zum zweiten (Ziel-) FTP-Server <b>%1\$s</b> auf Port <b>%2\$s</b> verbinden.<br /><br />Bitte Prüfen Sie die Adresse des FTP-Servers - diese unterscheidet sich oft von der Adresse des HTTP (Web) Servers. Bitte kontaktieren Sie die Hotline Ihres Providers oder Ihren Systemadministrator.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Anmeldung am zweiten (Ziel-) FTP Server <b>%1\$s</b> mit Benutzername <b>%2\$s</b> fehlgeschlagen.<br /><br />Bitte prüfen Sie Ihren Benutzernamen und das Kennwort. Kontaktieren Sie die Hotline Ihres Providers oder Fragen Sie Ihren Systemadministrator.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "Konnte nicht in den passiven Modus auf dem zweiten (Ziel-) FTP-Server <b>%1\$s</b> wechseln.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "Umbenennen der Datei oder des Verzeichnisses <b>%1\$s</b> in <b>%2\$s</b> fehlgeschlagen";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "Ausführung des SITE-Kommandos <b>%1\$s</b> fehlgeschlagen. Hinweis: Das CHMOD Kommando ist nur auf Unix-FTP-Servern verfügbar, nicht auf Windows-FTP-Servern.";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "Zugriffsrechte des Verzeichnisses <b>%1\$s</b> erfolgreich in <b>%2\$s</b> geändert";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "Processing entries within directory <b>%1\$s</b>:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "Zugriffsrechte der Datei <b>%1\$s</b> erfolgreich in <b>%2\$s</b> geändert";
$net2ftp_messages["All the selected directories and files have been processed."] = "Alle ausgewählten Verzeichnisse und Dateien wurden verarbeitet.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "Löschen des Verzeichnisses <b>%1\$s</b> fehlgeschlagen";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Löschen der Datei <b>%1\$s</b> fehlgeschlagen";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "Der neue Ordner <b>%1\$s</b> kann nicht angelegt werden";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "Die temporäre Datei kann nicht erstellt werden";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "Laden der Datei <b>%1\$s</b> vom FTP Server und Zwischenspeichern als <b>%2\$s</b> fehlgeschlagen.<br />Bitte prüfen Sie die Zugriffsrechte des Ordners %3\$s.<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Öffnen der zwischengespeicherten Datei fehlgeschlagen. Bitte prüfen Sie die Zugriffsrechte des Ordners %1\$s.";
$net2ftp_messages["Unable to read the temporary file"] = "Lesen der temporären Datei fehlgeschlagen.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Die Verarbeitung der temporären Datei konnte nicht beendet werden";
$net2ftp_messages["Unable to delete the temporary file"] = "Die temporäre Datei kann nicht gelöscht werden";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "Die temporäre Datei kann nicht erstellt werden. Bitte Berechtigung des Verzeichnisses %1\$s überprüfen.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Öffnen der zwischengespeicherten Datei fehlgeschlagen. Bitte prüfen Sie die Zugriffsrechte des Ordners %1\$s.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "Speichern der Zeichenkette in die temporäre Datei <b>%1\$s</b> fehlgeschlagen.<br />Bitte prüfen Sie die Zugriffsrechte des Ordners %2\$s.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Die Verarbeitung der temporären Datei konnte nicht beendet werden";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "Konnte Datei <b>%1\$s</b> nicht auf dem FTP Server ablegen.<br />Bitte prüfen Sie Ihre Schreibrechte in diesem Verzeichnis.";
$net2ftp_messages["Unable to delete the temporary file"] = "Die temporäre Datei kann nicht gelöscht werden";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "Verarbeiten des Ordners <b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "Das Ziel-Verzeichnis <b>%1\$s</b> ist das Gleiche als der Quellordner <b>%2\$s</b>, oder ein Unterordner davon, Ordner wird übersprungen";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The directory <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Created target subdirectory <b>%1\$s</b>";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "The directory <b>%1\$s</b> could not be selected, so this directory will be skipped";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "Das Unterverzeichniss <b>%1\$s</b> konnte nicht gelöscht werden - es ist nicht leer";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "Gelöschtes Verzeichniss <b>%1\$s</b>";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "Verarbeitung des Verzeichnisses <b>%1\$s</b> beendet";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "Das Ziel für die Datei <b>%1\$s</b> ist die selbe wie die Quelle, diese Datei wird übersprungen";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The file <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "The file <b>%1\$s</b> is too big to be copied, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "The file <b>%1\$s</b> is too big to be moved, aborting the move";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "Die Datei <b>%1\$s</b> kann nicht kopiert werden";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Copied file <b>%1\$s</b>";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Unable to move the file <b>%1\$s</b>, aborting the move";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "Moved file <b>%1\$s</b>";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Löschen der Datei <b>%1\$s</b> fehlgeschlagen";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "Gelöschte Datei <b>%1\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "Alle ausgewählten Verzeichnisse und Dateien wurden verarbeitet.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "Die Remote Datei <b>%1\$s</b> konnte nicht lokal per FTP Modus <b>%2\$s</b> kopiert werden";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "Die Datei <b>%1\$s</b> kann nicht gelöscht werden";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "The file is too big to be transferred";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "Tages-Beschränkung erreicht: die Datei <b>%1\$s</b> wird nicht transferiert";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>";
$net2ftp_messages["Unable to delete the local file"] = "Lokale Datei kann nicht gelöscht werden";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "Die temporäre Datei kann nicht gelöscht werden";
$net2ftp_messages["Unable to send the file to the browser"] = "Konnte Datei nicht an Browser senden";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "Die temporäre Datei kann nicht erstellt werden";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "Das ZIP-Archiv wurde auf dem FTP-Server als <b>%1\$s</b> gespeichert";
$net2ftp_messages["Requested files"] = "Angeforderte Dateien";

$net2ftp_messages["Dear,"] = "Sehr geehrte(r),";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "Jemand hat veranlasst, daß diese Datei an Ihre E-Mail Adresse (%1\$s) gesendet wird.";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "Wenn Sie nichts davon Wissen oder der Person nicht trauen, löschen Sie bitte diese E-Mail und den Anhang, ohne Sie zu öffnen.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "Beachten Sie bitte, daß die Dateien im Anhang Ihrem Computer nicht schaden können, wenn Sie die Datei nicht öffnen.";
$net2ftp_messages["Information about the sender: "] = "Informationen über den Absender: ";
$net2ftp_messages["IP address: "] = "IP Addresse: ";
$net2ftp_messages["Time of sending: "] = "Gesendet: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "Versendet durch den net2ftp Dienst der Webseite: ";
$net2ftp_messages["Webmaster's email: "] = "E-Mail Adresse des Webmaster: ";
$net2ftp_messages["Message of the sender: "] = "Nachricht des Absenders: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp ist freie Software, freigegeben unter der GNU/GPL Lizenz. Für mehr Information, gehen Sie zu http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "Die Zip Datei wurde versand an <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "Datei <b>%1\$s</b> ist zu groß. Diese Datei wird nicht hochgeladen.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded.";
$net2ftp_messages["Could not generate a temporary file."] = "Temporäre Datei kann nicht erstellt werden.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "Datei <b>%1\$s</b> konnte nicht verschoben werden";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "Datei <b>%1\$s</b> ist OK";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp.";
$net2ftp_messages["You did not provide any file to upload."] = "Sie haben keine Datei zum Upload ausgewählt.";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "Datei <b>%1\$s</b> konnte nicht auf den FTP-Server geladen werden";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "Datei <b>%1\$s</b> wurde erfolgreich auf den FTP-Server im Modus <b>%2\$s</b> übertragen";
$net2ftp_messages["Transferring files to the FTP server"] = "Dateien werden zum FTP-Server geschickt";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "Verarbeitung von Archiv Nr. %1\$s: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "Archiv <b>%1\$s</b> wurde nicht verarbeitet, das Format ist unbekannt. Zur Zeit unterstützte Archiv-Formate: zip, tar, tgz (tar-gzip), gz (gzip).";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Unable to extract the files and directories from the archive";
$net2ftp_messages["Created directory %1\$s"] = "Created directory %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "Could not create directory %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "Copied file %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "Could not copy file %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "Unable to delete the temporary directory";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Unable to delete the temporary file %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "SITE-Kommando <b>%1\$s</b> fehlgeschlagen";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "Ihr Auftrag wurde angehalten";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "Ihr Arbeitsauftrag den Sie mit net2ftp ausführen wollten, hat mehr Zeit als die erlaubten  %1\$s Sekunden in Anspruch genommen, und wurde deswegen angehalten.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "Diese Zeitbeschränkung gewährleistet den Betrieb des Webservers für andere Nutzer.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "Versuchen Sie, Ihren Auftrag in kleinere Schritte aufzutrennen: schränken Sie die Auswahl an Dateien ein, und/oder überspringen sie die größten Dateien.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "Sollten Sie net2ftp benötigen, um größere Arbeitsaufträge auszuführen, können Sie net2ftp auf Ihrem eigenen Webserver installieren.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "Sie haben keinen Text für den EMail-Versand angegeben!";
$net2ftp_messages["You did not supply a From address."] = "Sie haben keine Absenderadresse eingegeben.";
$net2ftp_messages["You did not supply a To address."] = "Sie haben keine Empfängeradresse eingegeben.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "Aus technischen Gründen konnte die EMail an <b>%1\$s</b> nicht versendet werden.";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "Bitte geben Sie Ihren Benutzernamen und das Kennwort ein, für den FTP Server ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Sie haben keine Zugangsdaten im Popup-Fenster ausgefüllt.<br />Klicken Sie unten auf \"Go to the login page\".";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "Der Zugang zum net2ftp Administrationsbereich wurde deaktiviert, da kein Kennwort in der Datei settings.inc.php eingetragen wurde. Tragen Sie dort ein Kennwort ein, und laden diese Seite neu.";
$net2ftp_messages["Please enter your Admin username and password"] = "Bitte geben Sie Ihren Administrations-Benutzernamen und das entsprechende Kennwort ein"; 
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Sie haben keine Zugangsdaten im Popup-Fenster ausgefüllt.<br />Klicken Sie unten auf \"Go to the login page\".";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "Falscher Benutzername oder falsches Kennwort für net2ftp Administrationsbereich. Bitte prüfen Sie Ihre Eingabe bzw. die Einstellungen in der Datei settings.inc.php.";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "Blau";
$net2ftp_messages["Grey"] = "Grau";
$net2ftp_messages["Black"] = "Schwarz";
$net2ftp_messages["Yellow"] = "Gelb";
$net2ftp_messages["Pastel"] = "Pastel";

// getMime()
$net2ftp_messages["Directory"] = "Verzeichnis";
$net2ftp_messages["Symlink"] = "Symlink";
$net2ftp_messages["ASP script"] = "ASP Script";
$net2ftp_messages["Cascading Style Sheet"] = "Cascading Style Sheet";
$net2ftp_messages["HTML file"] = "HTML Datei";
$net2ftp_messages["Java source file"] = "Java source Datei";
$net2ftp_messages["JavaScript file"] = "JavaScript Datei";
$net2ftp_messages["PHP Source"] = "PHP Source";
$net2ftp_messages["PHP script"] = "PHP Skript";
$net2ftp_messages["Text file"] = "Text Datei";
$net2ftp_messages["Bitmap file"] = "Bitmap Datei";
$net2ftp_messages["GIF file"] = "GIF Datei";
$net2ftp_messages["JPEG file"] = "JPEG Datei";
$net2ftp_messages["PNG file"] = "PNG Datei";
$net2ftp_messages["TIF file"] = "TIF Datei";
$net2ftp_messages["GIMP file"] = "GIMP Datei";
$net2ftp_messages["Executable"] = "Ausführbare Datei";
$net2ftp_messages["Shell script"] = "Shell script";
$net2ftp_messages["MS Office - Word document"] = "MS Office - Word Dokument";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - Excel spreadsheet";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - PowerPoint Presentation";
$net2ftp_messages["MS Office - Access database"] = "MS Office - Access Datenbank";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - Visio drawing";
$net2ftp_messages["MS Office - Project file"] = "MS Office - Project Datei";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - Writer 6.0 Dokument";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - Writer 6.0 Vorlage";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - Calc 6.0 Tabellemndokument";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - Calc 6.0 Vorlage";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - Draw 6.0 Dokument";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - Draw 6.0 Vorlage";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - Impress 6.0 Präsentation";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - Impress 6.0 Vorlage";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - Writer 6.0 Globaldokument";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - Math 6.0 Dokument";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - StarWriter 5.x document";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - StarWriter 5.x global Dokument";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - StarCalc 5.x Tabellendokument";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - StarDraw 5.x Dokument";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - StarImpress 5.x Präsentation";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - StarImpress gepackte 5.x Datei";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - StarMath 5.x Dokument";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - StarChart 5.x Dokument";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - StarMail 5.x Maildatei";
$net2ftp_messages["Adobe Acrobat document"] = "Adobe Acrobat Dokument";
$net2ftp_messages["ARC archive"] = "ARC Archiv";
$net2ftp_messages["ARJ archive"] = "ARJ Archiv";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "GZ Archiv";
$net2ftp_messages["TAR archive"] = "TAR Archiv";
$net2ftp_messages["Zip archive"] = "Zip Archiv";
$net2ftp_messages["MOV movie file"] = "MOV Videodatei";
$net2ftp_messages["MPEG movie file"] = "MPEG Videodatei";
$net2ftp_messages["Real movie file"] = "Real Videodatei";
$net2ftp_messages["Quicktime movie file"] = "Quicktime Datei";
$net2ftp_messages["Shockwave flash file"] = "Shockwave Flash Datei";
$net2ftp_messages["Shockwave file"] = "Shockwave Datei";
$net2ftp_messages["WAV sound file"] = "WAV Audiodatei";
$net2ftp_messages["Font file"] = "Font Datei";
$net2ftp_messages["%1\$s File"] = "%1\$s Datei";
$net2ftp_messages["File"] = "Datei";

// getAction()
$net2ftp_messages["Back"] = "Zurück";
$net2ftp_messages["Submit"] = "Senden";
$net2ftp_messages["Refresh"] = "Aktualisieren";
$net2ftp_messages["Details"] = "Details";
$net2ftp_messages["Icons"] = "Icons";
$net2ftp_messages["List"] = "List";
$net2ftp_messages["Logout"] = "Abmelden";
$net2ftp_messages["Help"] = "Hilfe";
$net2ftp_messages["Bookmark"] = "Lesezeichen";
$net2ftp_messages["Save"] = "Speichern";
$net2ftp_messages["Default"] = "Standard";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Help Guide";
$net2ftp_messages["Forums"] = "Forums";
$net2ftp_messages["License"] = "Lizenz";
$net2ftp_messages["Powered by"] = "Powered by";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions.";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "Administrationsfunktionen";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "Versionsinformationen";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "This version of net2ftp is up-to-date.";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server.";
$net2ftp_messages["Logging"] = "Logging";
$net2ftp_messages["Date from:"] = "Datum ab:";
$net2ftp_messages["to:"] = "bis:";
$net2ftp_messages["Empty logs"] = "Leer";
$net2ftp_messages["View logs"] = "Logs betrachten";
$net2ftp_messages["Go"] = "Weiter";
$net2ftp_messages["Setup MySQL tables"] = "MySQL-Tabellen einrichten";
$net2ftp_messages["Create the MySQL database tables"] = "Anlegen der MySQL-Datenbanktabellen";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "Administrationsfunktionen";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "The handle of file %1\$s could not be opened.";
$net2ftp_messages["The file %1\$s could not be opened."] = "The file %1\$s could not be opened.";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "The handle of file %1\$s could not be closed.";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered.";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "Unable to select the database <b>%1\$s</b>.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "The SQL query nr <b>%1\$s</b> could not be executed.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "The SQL query nr <b>%1\$s</b> was executed successfully.";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "Please enter your MySQL settings:";
$net2ftp_messages["MySQL username"] = "MySQL Benutzername";
$net2ftp_messages["MySQL password"] = "MySQL Kennwort";
$net2ftp_messages["MySQL database"] = "MySQL Datenbankname";
$net2ftp_messages["MySQL server"] = "MySQL Server";
$net2ftp_messages["This SQL query is going to be executed:"] = "Folgende SQL-Anfrage wird ausgef&uuml;hrt:";
$net2ftp_messages["Execute"] = "Ausführen";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "Verwendete Einstellungen:";
$net2ftp_messages["MySQL password length"] = "MySQL Kennwortl&auml;nge";
$net2ftp_messages["Results:"] = "Ergebnisse:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "Administrationsfunktionen";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "Unable to execute the SQL query <b>%1\$s</b>.";
$net2ftp_messages["No data"] = "Keine Daten";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "Administrationsfunktionen";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "Die Tabelle <b>%1\$s</b> wurde erfolgreich geleert.";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "Die Tabelle <b>%1\$s</b> konnte nicht geleert werden.";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "The table <b>%1\$s</b> was optimized successfully.";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "The table <b>%1\$s</b> could not be optimized.";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "Erweiterte Funktionen";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "Weiter";
$net2ftp_messages["Disabled"] = "Disabled";
$net2ftp_messages["Advanced FTP functions"] = "Advanced FTP functions";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "Send arbitrary FTP commands to the FTP server";
$net2ftp_messages["This function is available on PHP 5 only"] = "This function is available on PHP 5 only";
$net2ftp_messages["Troubleshooting functions"] = "Fehlersuchfunktionen";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "Fehlersuche bei net2ftp auf diesem Webserver";
$net2ftp_messages["Troubleshoot an FTP server"] = "Fehlersuche bei einem FTP Server";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Test the net2ftp list parsing rules";
$net2ftp_messages["Translation functions"] = "&Uuml;bersetzungsfunktionen";
$net2ftp_messages["Introduction to the translation functions"] = "Einf&uuml;hrung in die &Uuml;bersetzungsfunktionen";
$net2ftp_messages["Extract messages to translate from code files"] = "Extrahiere zu &uuml;bersetzende Zeichenketten aus dem Quelltext";
$net2ftp_messages["Check if there are new or obsolete messages"] = "Suche nach neuen oder veralteten Zeichenketten";

$net2ftp_messages["Beta functions"] = "Betafunktionen";
$net2ftp_messages["Send a site command to the FTP server"] = "Ein SITE-Kommando auf dem FTP Server absetzen";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: Verzeichnis passwortsch&uuml;tzen, eine eigene Error-Seite anlegen";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: eine SQL-Anfrage ausf&uuml;hren";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "Die Navigations Funktionen sind auf diesem Webserver nicht verfügbar.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "Die Apache Funktionen sind auf diesem Webserver nicht verfügbar.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "Die MySQL Funktionen sind auf diesem Webserver nicht verfügbar.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "Unerwartete state2-Zeichenkette. Beende.";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "Fehlersuche bei einem FTP Server";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "Verbindungseigenschaften:";
$net2ftp_messages["FTP server"] = "FTP Server";
$net2ftp_messages["FTP server port"] = "FTP Server Port";
$net2ftp_messages["Username"] = "Username";
$net2ftp_messages["Password"] = "Passwort";
$net2ftp_messages["Password length"] = "Passwortlänge";
$net2ftp_messages["Passive mode"] = "Passiver Modus";
$net2ftp_messages["Directory"] = "Verzeichnis";
$net2ftp_messages["Printing the result"] = "Printing the result";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "Verbinden mit dem FTP Server: ";
$net2ftp_messages["Logging into the FTP server: "] = "Anmelden am FTP Server: ";
$net2ftp_messages["Setting the passive mode: "] = "Setzen des passiven Modus:";
$net2ftp_messages["Getting the FTP server system type: "] = "Prüfe Systemtyp des FTP-Servers: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Wechseln in das Verzeichniss %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Das Verzeichniss des FTP Server ist: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Empfang einer Rohliste der Dateien und Ordnern: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Erneuter Empfangsversuch einer Rohliste der Dateien und Ordnern: ";
$net2ftp_messages["Closing the connection: "] = "Verbindung wird geschlossen: ";
$net2ftp_messages["Raw list of directories and files:"] = "Rohliste der Dateien und Ordner:";
$net2ftp_messages["Parsed list of directories and files:"] = "Zergliederte Liste der Dateien und Ordner:";

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

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "Fehlersuche bei der net2ftp Installation";
$net2ftp_messages["Printing the result"] = "Printing the result";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "Überprüfen ob das FTP Modul von PHP installiert ist";
$net2ftp_messages["yes"] = "Ja";
$net2ftp_messages["no - please install it!"] = "Nein - bitte installieren!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "Überprüfung der Berechtigungen des Verzeichnisses auf dem Webserver: eine kleine Datei wird in den /temp Ordner geschrieben und anschließend gelöscht.";
$net2ftp_messages["Creating filename: "] = "Dateiname wird erstellt: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "OK. Dateiname: %tempfilename";
$net2ftp_messages["not OK"] = "not OK";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "nicht OK. Bitte die Berechtigung des Ordners %1\$s überprüfen";
$net2ftp_messages["Opening the file in write mode: "] = "&Ouml;ffnen der Datei im Schreib-Modus: ";
$net2ftp_messages["Writing some text to the file: "] = "Schreiben von Text in die Datei: ";
$net2ftp_messages["Closing the file: "] = "Schließen der Datei: ";
$net2ftp_messages["Deleting the file: "] = "Löschen der Datei: ";

$net2ftp_messages["Testing the FTP functions"] = "Testing the FTP functions";
$net2ftp_messages["Connecting to a test FTP server: "] = "Connecting to a test FTP server: ";
$net2ftp_messages["Connecting to the FTP server: "] = "Verbinden mit dem FTP Server: ";
$net2ftp_messages["Logging into the FTP server: "] = "Anmelden am FTP Server: ";
$net2ftp_messages["Setting the passive mode: "] = "Setzen des passiven Modus:";
$net2ftp_messages["Getting the FTP server system type: "] = "Prüfe Systemtyp des FTP-Servers: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Wechseln in das Verzeichniss %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Das Verzeichniss des FTP Server ist: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Empfang einer Rohliste der Dateien und Ordnern: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Erneuter Empfangsversuch einer Rohliste der Dateien und Ordnern: ";
$net2ftp_messages["Closing the connection: "] = "Verbindung wird geschlossen: ";
$net2ftp_messages["Raw list of directories and files:"] = "Rohliste der Dateien und Ordner:";
$net2ftp_messages["Parsed list of directories and files:"] = "Zergliederte Liste der Dateien und Ordner:";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "not OK";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "Diesen Link zu Ihren Favoriten hinzufügen:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: Rechtsklick auf den Link und \"Zu Favoriten hinzufügen ...\" auswählen";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: Rechtsklick auf den Link und \"Bookmark This Link...\" auswählen";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "Achtung: Wenn Sie dieses Lesezeichen benutzen, werden Sie in einem Popup Fenster nach dem Usernamen und Passwort gefragt.";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "Verzeichniss auswählen";
$net2ftp_messages["Please wait..."] = "Bitte warten...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "Verzeichnisse, die  \' enthalten, können nicht korrekt dargestellt werden. Diese können nur gelöscht werden. Bitte gehen Sie zurück und wählen Sie ein anderes Verzeichniss.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "Tages-Beschränkung erreicht: Sie können keine Daten mehr transferieren.";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "Um die faire Nutzung des Webservers für alle Nutzer zu gewährleisten, ist das Transfervolumen und die Laufzeit von Skripten pro Nutzer und Tag beschränkt. Wird die Beschränkung erreicht, können Sie immernoch den FTP Server durchsuchen, allerdings können keine Daten mehr hoch- oder runtergeladen werden.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "Wenn Sie unbeschränkten Zugang benötigen, installieren Sie net2ftp bitte auf Ihrem eigenen Webserver.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "Neuer Ordner";
$net2ftp_messages["New file"] = "Neue Datei";
$net2ftp_messages["HTML templates"] = "HTML templates";
$net2ftp_messages["Upload"] = "Upload";
$net2ftp_messages["Java Upload"] = "Java Upload";
$net2ftp_messages["Flash Upload"] = "Flash Upload";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Advanced"] = "Erweitert";
$net2ftp_messages["Copy"] = "Kopieren";
$net2ftp_messages["Move"] = "Verschieben";
$net2ftp_messages["Delete"] = "Löschen";
$net2ftp_messages["Rename"] = "Umbenennen";
$net2ftp_messages["Chmod"] = "Zugriffsrechte";
$net2ftp_messages["Download"] = "Download";
$net2ftp_messages["Unzip"] = "Unzip";
$net2ftp_messages["Zip"] = "Zip";
$net2ftp_messages["Size"] = "Größe";
$net2ftp_messages["Search"] = "Suchen";
$net2ftp_messages["Go to the parent directory"] = "Übergeordneter Ordner";
$net2ftp_messages["Go"] = "Weiter";
$net2ftp_messages["Transform selected entries: "] = "Ausgewählte Einträge transformieren: ";
$net2ftp_messages["Transform selected entry: "] = "Transform selected entry: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "Erstellen eines neuen Unterverzeichnisses im Ordner %1\$s";
$net2ftp_messages["Create a new file in directory %1\$s"] = "Erstellen einer neuen Datein im Ordner %1\$s";
$net2ftp_messages["Create a website easily using ready-made templates"] = "Create a website easily using ready-made templates";
$net2ftp_messages["Upload new files in directory %1\$s"] = "Upload neuer Dateien in Verzeichniss %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Upload directories and files using a Java applet";
$net2ftp_messages["Upload files using a Flash applet"] = "Upload files using a Flash applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Install software packages (requires PHP on web server)";
$net2ftp_messages["Go to the advanced functions"] = "Wechseln zu erweiterten Funktionen";
$net2ftp_messages["Copy the selected entries"] = "Kopieren der ausgewählten Einträge";
$net2ftp_messages["Move the selected entries"] = "Verschieben der ausgewählten Einträge";
$net2ftp_messages["Delete the selected entries"] = "Löschen der ausgewählten Einträge";
$net2ftp_messages["Rename the selected entries"] = "Umbenennen der ausgewählten Einträge";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "Zugriffsrechte der ausgewählten Einträge ändern (funktioniert nur auf Unix/Linux/BSD Servern)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "Download eine ZIP Datei mit allen ausgewählten Elementen";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Unzip the selected archives on the FTP server";
$net2ftp_messages["Zip the selected entries to save or email them"] = "Zippen der ausgewälten Elemente zum speichern oder versenden per Mail";
$net2ftp_messages["Calculate the size of the selected entries"] = "Kalkulieren der Größe ausgewählter Einträge";
$net2ftp_messages["Find files which contain a particular word"] = "Suchen von Dateien mit einem bestimmten Wort im Text";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "Absteigend nach %1\$s sortieren";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "Aufsteigend nach %1\$s sortieren";
$net2ftp_messages["Ascending order"] = "Aufsteigend";
$net2ftp_messages["Descending order"] = "Absteigend";
$net2ftp_messages["Upload files"] = "Upload files";
$net2ftp_messages["Up"] = "Aufwärts";
$net2ftp_messages["Click to check or uncheck all rows"] = "Alle Zeilen an- bzw. abwählen";
$net2ftp_messages["All"] = "Alle";
$net2ftp_messages["Name"] = "Name";
$net2ftp_messages["Type"] = "Typ";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "Besitzer";
$net2ftp_messages["Group"] = "Gruppe";
$net2ftp_messages["Perms"] = "Berechtigungen";
$net2ftp_messages["Mod Time"] = "Änderungs-Datum/Zeit";
$net2ftp_messages["Actions"] = "Aktionen";
$net2ftp_messages["Select the directory %1\$s"] = "Select the directory %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "Select the file %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "Select the symlink %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "Go to the subdirectory %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "Datei %1\$s herunterladen";
$net2ftp_messages["Follow symlink %1\$s"] = "Follow symlink %1\$s";
$net2ftp_messages["View"] = "Anzeigen";
$net2ftp_messages["Edit"] = "Bearbeiten";
$net2ftp_messages["Update"] = "Aktualisieren";
$net2ftp_messages["Open"] = "Öffnen";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Den Quellcode der Datei %1\$s ansehen";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Den Quellcode der Datei %1\$s bearbeiten";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "Hochladen einer neuen Version der Datei %1\$s und zusammenfügen der Änderungen";
$net2ftp_messages["View image %1\$s"] = "View image %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "Die Datei %1\$s von Ihrem Webserver ansehen";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(Achtung: Dieser Link wird nicht funktionieren, wenn Sie keinen eigene Domäne haben.)";
$net2ftp_messages["This folder is empty"] = "Dieser Ordner ist leer";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "Ordner";
$net2ftp_messages["Files"] = "Dateien";
$net2ftp_messages["Symlinks"] = "Symlinks";
$net2ftp_messages["Unrecognized FTP output"] = "Unerkannter FTP Output";
$net2ftp_messages["Number"] = "Nummer";
$net2ftp_messages["Size"] = "Größe";
$net2ftp_messages["Skipped"] = "Skipped";
$net2ftp_messages["Data transferred from this IP address today"] = "Data transferred from this IP address today";
$net2ftp_messages["Data transferred to this FTP server today"] = "Data transferred to this FTP server today";

// printLocationActions()
$net2ftp_messages["Language:"] = "Sprache:";
$net2ftp_messages["Skin:"] = "Skin:";
$net2ftp_messages["View mode:"] = "Ansichts-Modus:";
$net2ftp_messages["Directory Tree"] = "Verzeichnissbaum";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "In einem neuen Fenster %1\$s ausführen";
$net2ftp_messages["This file is not accessible from the web"] = "This file is not accessible from the web";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "Doppleklick um in ein Unterverzeichniss zu wechseln:";
$net2ftp_messages["Choose"] = "Auswahl";
$net2ftp_messages["Up"] = "Aufwärts";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "Größe der ausgewählten Ordner und Dateien";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "Die verbrauchte Gesamtgröße der ausgewählten Ordner und Dateien ist::";
$net2ftp_messages["The number of files which were skipped is:"] = "The number of files which were skipped is:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "Berechtigungen von Ordnern und Dateien ändern";
$net2ftp_messages["Set all permissions"] = "Setzen aller Berechtigungen";
$net2ftp_messages["Read"] = "Lesen";
$net2ftp_messages["Write"] = "Schreiben";
$net2ftp_messages["Execute"] = "Ausführen";
$net2ftp_messages["Owner"] = "Besitzer";
$net2ftp_messages["Group"] = "Gruppe";
$net2ftp_messages["Everyone"] = "Jeder";
$net2ftp_messages["To set all permissions to the same values, enter those permissions above and click on the button \"Set all permissions\""] = "Um alle Zugriffsrechte gemeinsam zu verändern, setzen Sie die Zugriffsrechte und klicken auf den Schalter \"Set all permissions\" bzw. \"Alle Zugriffsrechte setzen\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "Setze Zugriffsrechte des Ordners <b>%1\$s</b> auf: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "Setze Zugriffsrechte der Datei<b>%1\$s</b> auf: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "Setze Zugriffsrechte des Symlinks <b>%1\$s</b> auf: ";
$net2ftp_messages["Chmod value"] = "Zugriffsrecht";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "Zugriffsrechte auch in Unterordnern dieses Ordners setzen";
$net2ftp_messages["Chmod also the files within this directory"] = "Zugriffsrechte auch für Dateien in diesem Ordner setzen";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "Das Zugriffsrecht <b>%1\$s</b> ist nicht innerhalb des erlaubten Bereichs 000-777. Bitte versuchen Sie es erneut.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "Verzeichniss auswählen";
$net2ftp_messages["Copy directories and files"] = "Dateien und Verzeichnisse kopieren";
$net2ftp_messages["Move directories and files"] = "Dateien und Verzeichnisse verschieben";
$net2ftp_messages["Delete directories and files"] = "Dateien und Verzeichnisse löschen";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "Sind Sie sicher, daß Sie diese Dateien und Verzeichnisse löschen wollen?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "Alle Unterordner und Dateien der ausgewählten Verzeichnisse werden ebenfalls gelöscht!";
$net2ftp_messages["Set all targetdirectories"] = "Setzen als Zielverzeichniss für alle";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Um einen gemeinsamen Zielordner anzugeben, tragen Sie das Zielverzeichnis in das obere Eingabefeld ein, und klicken auf \"Set all targetdirectories\" bzw \"Alle Zielordner setzen\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Hinweis: der Zielordner muss bereits existieren, bevor Dateien hineinkopiert werden können.";
$net2ftp_messages["Different target FTP server:"] = "Anderer Ziel FTP Server:";
$net2ftp_messages["Username"] = "Username";
$net2ftp_messages["Password"] = "Passwort";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "Leer lassen, um Dateien auf den gleichen FTP Server zu übertragen";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "Um Dateien auf einen anderen FTP-Server zu übertragen, geben Sie Ihre Login-Daten ein.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "Leer lassen, um Dateien auf dem gleichen FTP-Server zu verschieben.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "Um Dateien auf einen anderen FTP-Server zu verschieben, geben Sie Ihre Login-Daten ein.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "Kopiere Verzeichniss <b>%1\$s</b> nach:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "Verschiebe Verzeichniss <b>%1\$s</b> nach:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "Verzeichniss <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "Kopiere Datei <b>%1\$s</b> nach:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "Verschiebe Datei <b>%1\$s</b> nach:";
$net2ftp_messages["File <b>%1\$s</b>"] = "File <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "Kopiere Symlink <b>%1\$s</b> nach:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "Verschiebe Symlink <b>%1\$s</b> nach:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Symlink <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "Ziel Verzeichniss:";
$net2ftp_messages["Target name:"] = "Ziel Name:";
$net2ftp_messages["Processing the entries:"] = "Verarbeiten der Einträge:";

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
$net2ftp_messages["Size"] = "Größe";
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
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: Rechtsklick auf den Link und \"Zu Favoriten hinzufügen ...\" auswählen";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: Rechtsklick auf den Link und \"Bookmark This Link...\" auswählen";

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
$net2ftp_messages["Unable to open the template file"] = "Die Vorlage kann nicht geöffnet werden";
$net2ftp_messages["Unable to read the template file"] = "Die Vorlage kann nicht gelesen werden";
$net2ftp_messages["Please specify a filename"] = "Bitte geben Sie einen Dateinamen an";
$net2ftp_messages["Status: This file has not yet been saved"] = "Status: Diese Datei wurde noch nicht gespeichert";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "Status: Speichern auf <b>%1\$s</b> im Modus %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "Status: <b>Die Datei konnte nicht gespeichert werden</b>";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "Verzeichniss: ";
$net2ftp_messages["File: "] = "Datei: ";
$net2ftp_messages["New file name: "] = "Dateiname: ";
$net2ftp_messages["Character encoding: "] = "Character encoding: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "Hinweis: Ändern des Textarea-Typs speichert die Änderungen";
$net2ftp_messages["Copy up"] = "Copy up";
$net2ftp_messages["Copy down"] = "Copy down";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "Suche Ordner und Dateien";
$net2ftp_messages["Search again"] = "Erneute Suche";
$net2ftp_messages["Search results"] = "Suchergebnisse";
$net2ftp_messages["Please enter a valid search word or phrase."] = "Bitte geben Sie ein gültiges Suchwort oder Satzteil ein.";
$net2ftp_messages["Please enter a valid filename."] = "Bitte geben Sie einen gültigen Dateinamen an.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "Bitte geben Sie eine gültige Dateigröße im \"von\" Textfeld ein, zum Beispiel 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "Bitte geben Sie eine gültige Dateigröße im \"bis\" Textfeld ein, zum Beispiel 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "Bitte geben Sie ein gültiges Datum in der Form J-m-t in das \"von\" Textfeld ein.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "Bitte geben Sie ein gültiges Datum in der Form J-m-t in das \"bis\" Textfeld ein.";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "Das Suchwort <b>%1\$s</b> konnte in den ausgewählten Dateien und Ordnern nicht gefunden werden.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "Das Suchwort <b>%1\$s</b> wurde in folgenden Dateien gefunden:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "Suche nach einem Wort oder Satzteil";
$net2ftp_messages["Case sensitive search"] = "Groß- und Kleinschreibung bei Suche beachten";
$net2ftp_messages["Restrict the search to:"] = "Einschränken der Suche nach:";
$net2ftp_messages["files with a filename like"] = "Dateien mit einem Namen wie";
$net2ftp_messages["(wildcard character is *)"] = "(wildcard character is *)";
$net2ftp_messages["files with a size"] = "Dateien mit einer Größe";
$net2ftp_messages["files which were last modified"] = "Dateien die zuletzt geändert wurden am";
$net2ftp_messages["from"] = "von";
$net2ftp_messages["to"] = "bis";

$net2ftp_messages["Directory"] = "Verzeichnis";
$net2ftp_messages["File"] = "Datei";
$net2ftp_messages["Line"] = "Line";
$net2ftp_messages["Action"] = "Action";
$net2ftp_messages["View"] = "Anzeigen";
$net2ftp_messages["Edit"] = "Bearbeiten";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Den Quellcode der Datei %1\$s ansehen";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Den Quellcode der Datei %1\$s bearbeiten";

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
$net2ftp_messages["Unable to open the template file"] = "Die Vorlage kann nicht geöffnet werden";
$net2ftp_messages["Unable to read the template file"] = "Die Vorlage kann nicht gelesen werden";
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
$net2ftp_messages["Upload"] = "Upload";
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

$net2ftp_messages["FTP server"] = "FTP Server";
$net2ftp_messages["Example"] = "Beispiel";
$net2ftp_messages["Port"] = "Port";
$net2ftp_messages["Username"] = "Username";
$net2ftp_messages["Password"] = "Passwort";
$net2ftp_messages["Anonymous"] = "Anonym";
$net2ftp_messages["Passive mode"] = "Passiver Modus";
$net2ftp_messages["Initial directory"] = "Anfangsverzeichniss";
$net2ftp_messages["Language"] = "Sprache";
$net2ftp_messages["Skin"] = "Skin";
$net2ftp_messages["FTP mode"] = "FTP mode";
$net2ftp_messages["Automatic"] = "Automatic";
$net2ftp_messages["Login"] = "Anmeldung";
$net2ftp_messages["Clear cookies"] = "Cookies löschen";
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
$net2ftp_messages["Username"] = "Username";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Password"] = "Passwort";
$net2ftp_messages["Login"] = "Anmeldung";
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
$net2ftp_messages["Create new directories"] = "Erstellen neuer Verzeichnisse";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "Die neuen Verzeichnisse werden erstellt in <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "Neuer Verzeichniss Name:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "Verzeichniss <b>%1\$s</b> wurde erfolgreich angelegt.";
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
$net2ftp_messages["Rename directories and files"] = "Umbenennen von Ordnern und Dateien";
$net2ftp_messages["Old name: "] = "Alter Name: ";
$net2ftp_messages["New name: "] = "Neuer Name: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "Der neue Name darf kein Punkte beinhalten. Dieser Eintrag wurde nicht umbenannt in <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> wurde erfolgreich umbenannt in <b>%2\$s</b>";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> konnte nicht in <b>%2\$s</b> umbenannt werden";

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
$net2ftp_messages["Set all targetdirectories"] = "Setzen als Zielverzeichniss für alle";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Um einen gemeinsamen Zielordner anzugeben, tragen Sie das Zielverzeichnis in das obere Eingabefeld ein, und klicken auf \"Set all targetdirectories\" bzw \"Alle Zielordner setzen\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Hinweis: der Zielordner muss bereits existieren, bevor Dateien hineinkopiert werden können.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Unzip archive <b>%1\$s</b> to:";
$net2ftp_messages["Target directory:"] = "Ziel Verzeichniss:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Benutze Ordner Namen (Erstellt Unterordner automatisch)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "Datei aktualisieren";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>ACHTUNG: DIESE FUNKTION IST NOCH IM FRÜHEN TESTBETRIEB. BENUTZEN SIE ES NUR MIT TESTDATEIEN! SIE WURDEN GEWARNT!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "Bekannte Programmfehler: - löscht Tabulatoren - arbeitet nicht zuverlässig mit großen Dateien (> 50kB) - konnte noch nicht mit Dateien getestet werden, die einen anderen Zeichensatz verwenden</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "Diese Funktion erlaubt, eine neue Version der ausgewählten Datei hochzuladen, die Änderungen zu betrachten und für jede Änderung eine Auswahl zwischen Annehmen und Ablehnen. Vor dem Speichern können Sie die zusammengeführte Datei editieren.";
$net2ftp_messages["Old file:"] = "Alte Datei:";
$net2ftp_messages["New file:"] = "Neue Datei:";
$net2ftp_messages["Restrictions:"] = "Einschränkungen:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Die maximale Größe einer Datei ist von net2ftp auf <b>%1\$s kB</b> und von PHP auf <b>%2\$s</b> begrenzt";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Die maximale Zeit zum ausführen ist <b>%1\$s Sekunden</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "Der FTP Transfer Modus (ASCII oder BINARY) wird automatisch gewählt, basierend auf der Dateierweiterung";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Wenn die Zieldatei bereits existiert wird sie überschrieben";
$net2ftp_messages["You did not provide any files or archives to upload."] = "Sie haben keine Dateien oder Ordner zum Hochladen ausgewählt.";
$net2ftp_messages["Unable to delete the new file"] = "Löschen der neuen Datei fehlgeschlagen";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "Bitte warten...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "Wählen Sie unten die Zeilen aus, wählen zwischen Annehmen und Ablehnen und schicken Sie das Formular ab.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "In Ordner hochladen:";
$net2ftp_messages["Files"] = "Dateien";
$net2ftp_messages["Archives"] = "Archives";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "Hier angegebene Dateien werden zum FTP Server übertragen.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "Hier angegebene Archive werden dekomprimiert und die Dateien werden an den FTP Server übermittelt.";
$net2ftp_messages["Add another"] = "Weitere hinzufügen";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Benutze Ordner Namen (Erstellt Unterordner automatisch)";

$net2ftp_messages["Choose a directory"] = "Verzeichniss auswählen";
$net2ftp_messages["Please wait..."] = "Bitte warten...";
$net2ftp_messages["Uploading... please wait..."] = "Upload... Bitte warten...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "If the upload takes more than the allowed <b>%1\$s<\/b>, you will have to try again with less/smaller files.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "Dieses Fenster schließt sich in wenigen Sekunden selber.";
$net2ftp_messages["Close window now"] = "Alle Fenster schließen";

$net2ftp_messages["Upload files and archives"] = "Dateien und Archive hochladen";
$net2ftp_messages["Upload results"] = "Ergebnisse des Hochladens";
$net2ftp_messages["Checking files:"] = "Überprüfe Dateien:";
$net2ftp_messages["Transferring files to the FTP server:"] = "Übertragen der Dateien an den FTP-Server:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "Entpacke Archiv und übertrage Dateien auf den FTP-Server:";
$net2ftp_messages["Upload more files and archives"] = "Weitere Dateien und Ordner hochladen";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "Einschränkungen:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Die maximale Größe einer Datei ist von net2ftp auf <b>%1\$s kB</b> und von PHP auf <b>%2\$s</b> begrenzt";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Die maximale Zeit zum ausführen ist <b>%1\$s Sekunden</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "Der FTP Transfer Modus (ASCII oder BINARY) wird automatisch gewählt, basierend auf der Dateierweiterung";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Wenn die Zieldatei bereits existiert wird sie überschrieben";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "Datei %1\$s anzeigen";
$net2ftp_messages["View image %1\$s"] = "View image %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "Macromedia ShockWave Flash Film %1\$s betrachten";
$net2ftp_messages["Image"] = "Bild";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "Um Bilder abzuspeichern, klicken Sie mit der rechten Maustaste darauf und wählen 'Bild speichern unter ...' im Kontextmenü";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "Zip Einträge";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "Datei als ZIP-File auf dem FTP Server speichern als:";
$net2ftp_messages["Email the zip file in attachment to:"] = "ZIP-Archiv im EMail-Anhang versenden an::";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "Hinweis: Das Versenden von Dateien ist nicht anonym: Ihre IP-Addresse und die aktuelle Zeit werden an die EMail angehängt.";
$net2ftp_messages["Some additional comments to add in the email:"] = "Weitere Kommentare an die EMail anhängen::";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "Sie haben keinen Dateinamen für das ZIP-Archiv spezifiziert. Gehen Sie zurück und geben Sie einen Dateinamen an.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "Die von Ihnen eingegebene EMail-Adresse (%1\$s) scheint ungültig zu sein.<br />Bitte geben Sie die Adresse in der Form <b>benutzername@domain.de</b> ein";

} // end zip

?>