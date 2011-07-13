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
$net2ftp_messages["en"] = "cs";

// HTML dir attribute: left-to-right (LTR) or right-to-left (RTL)
$net2ftp_messages["ltr"] = "ltr";

// CSS style: align left or right (use in combination with LTR or RTL)
$net2ftp_messages["left"] = "left";
$net2ftp_messages["right"] = "right";

// Encoding
$net2ftp_messages["iso-8859-1"] = "windows-1250";


// -------------------------------------------------------------------------
// Status messages
// -------------------------------------------------------------------------

// When translating these messages, keep in mind that the text should not be too long
// It should fit in the status textbox

$net2ftp_messages["Connecting to the FTP server"] = "Pøipojování k FTP serveru";
$net2ftp_messages["Logging into the FTP server"] = "Logging into the FTP server";
$net2ftp_messages["Setting the passive mode"] = "Setting the passive mode";
$net2ftp_messages["Getting the FTP system type"] = "Getting the FTP system type";
$net2ftp_messages["Changing the directory"] = "Changing the directory";
$net2ftp_messages["Getting the current directory"] = "Getting the current directory";
$net2ftp_messages["Getting the list of directories and files"] = "Získávání seznamu adresáøù a souborù";
$net2ftp_messages["Parsing the list of directories and files"] = "Parsing the list of directories and files";
$net2ftp_messages["Logging out of the FTP server"] = "Logging out of the FTP server";
$net2ftp_messages["Getting the list of directories and files"] = "Získávání seznamu adresáøù a souborù";
$net2ftp_messages["Printing the list of directories and files"] = "Vypisování seznamu adresáøù a souborù";
$net2ftp_messages["Processing the entries"] = "Zpracovávání záznamù";
$net2ftp_messages["Processing entry %1\$s"] = "Processing entry %1\$s";
$net2ftp_messages["Checking files"] = "Kontrolování souborù";
$net2ftp_messages["Transferring files to the FTP server"] = "Pøenášení souborù na FTP server";
$net2ftp_messages["Decompressing archives and transferring files"] = "Rozbalování archivù a pøenášení souborù";
$net2ftp_messages["Searching the files..."] = "Prohledávání souborù...";
$net2ftp_messages["Uploading new file"] = "Pøenášení nového souboru";
$net2ftp_messages["Reading the file"] = "Reading the file";
$net2ftp_messages["Parsing the file"] = "Parsing the file";
$net2ftp_messages["Reading the new file"] = "Ètení nového souboru";
$net2ftp_messages["Reading the old file"] = "Ètení starého souboru";
$net2ftp_messages["Comparing the 2 files"] = "Porovnávání dvou souborù";
$net2ftp_messages["Printing the comparison"] = "Vypisování porovnání";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "Sending FTP command %1\$s of %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Getting archive %1\$s of %2\$s from the FTP server";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Creating a temporary directory on the FTP server";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Setting the permissions of the temporary directory";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Copying the net2ftp installer script to the FTP server";
$net2ftp_messages["Script finished in %1\$s seconds"] = "Skript skonèil za %1\$s sekund";
$net2ftp_messages["Script halted"] = "Skript zastavil";

// Used on various screens
$net2ftp_messages["Please wait..."] = "Prosím èekejte...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Unexpected state string: %1\$s. Exiting.";
$net2ftp_messages["This beta function is not activated on this server."] = "This beta function is not activated on this server.";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "This function has been disabled by the Administrator of this website.";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead.";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "Your root directory <b>%1\$s</b> does not exist or could not be selected.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted.";
$net2ftp_messages["Execute %1\$s in a new window"] = "Spustit %1\$s v novém oknì";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "Vyberte prosím alespoò jeden adresáø nebo soubor!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "FTP server <b>%1\$s</b> není na seznamu povolenıch serverù.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "FTP server <b>%1\$s</b> je na seznamu zakázanıch serverù.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "Port %1\$s FTP serveru nemùe bıt pouit.";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "Your IP address (%1\$s) is not in the list of allowed IP addresses.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "Vaše IP adresa (%1\$s) je na seznamu zakázanıch adres.";

// isAuthorizedDirectory()
$net2ftp_messages["Table net2ftp_users contains duplicate rows."] = "Table net2ftp_users contains duplicate rows.";

// logAccess(), logLogin(), logError()
$net2ftp_messages["Unable to execute the SQL query."] = "Unable to execute the SQL query.";
$net2ftp_messages["Unable to open the system log."] = "Unable to open the system log.";
$net2ftp_messages["Unable to write a message to the system log."] = "Unable to write a message to the system log.";

// checkAdminUsernamePassword()
$net2ftp_messages["You did not enter your Administrator username or password."] = "You did not enter your Administrator username or password.";
$net2ftp_messages["Wrong username or password. Please try again."] = "Wrong username or password. Please try again.";


// -------------------------------------------------------------------------
// /includes/consumption.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to determine your IP address."] = "Nepodaøilo se zjistit vaši IP adresu.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "Tabulka net2ftp_log_consumption_ipaddress obsahuje duplicitní záznamy.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "Tabulka net2ftp_log_consumption_ftpserver obsahuje duplicitní záznamy.";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "Promìnná <b>consumption_ipaddress_datatransfer</b> není èíselná.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "Tabulka net2ftp_log_consumption_ipaddress nemohla bıt aktualizována.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "Tabulka net2ftp_log_consumption_ipaddress obsahuje duplicitní záznamy.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "Tabulka net2ftp_log_consumption_ftpserver nemohla bıt aktualizována.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "Tabulka net2ftp_log_consumption_ftpserver obsahuje duplicitní záznamy.";
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
$net2ftp_messages["An error has occured"] = "Došlo k chybì";
$net2ftp_messages["Go back"] = "Pøejít zpìt";
$net2ftp_messages["Go to the login page"] = "Pøejít na pøihlašovací stránku";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "<a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP modul PHP</a> není nainstalován.<br /><br /> Administrátor tohoto webového serveru by tento FTP modul mìl nainstalovat. Instrukce pro instalaci jsou k dispozici na <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Nepodaøilo se pøipojit k FTP serveru <b>%1\$s</b> na portu <b>%2\$s</b>.<br /><br />Víte jistì, e se jedná o adresu FTP serveru? Ta se èasto liší od adresy HTTP (webového) serveru. Pro získání pomoci prosím kontaktujte odbornou pomoc svého ISP nebo systémového administrátora.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Nepodaøilo se pøihlásit k FTP serveru <b>%1\$s</b> pod uivatelskım jménem <b>%2\$s</b>.<br /><br />Jste si jist, e je uivatelské jméno a heslo správné? Pro získání pomoci prosím kontaktujte odbornou pomoc svého ISP nebo systémového administrátora.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "Nepodaøilo se pøepnout do pasivního reimu na FTP serveru <b>%1\$s</b>.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Nepodaøilo se pøipojit ke druhému (cílovému) FTP serveru <b>%1\$s</b> na portu <b>%2\$s</b>.<br /><br />.Víte urèitì, e se jedná o adresu druhého (cílového) FTP serveru? Ta se èasto liší od adresy HTTP (webového) serveru. Pro získání pomoci prosím kontaktujte odbornou pomoc svého ISP nebo systémového administrátora.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Nepodaøilo se pøihlásit ke druhému (cílovému) FTP serveru <b>%1\$s</b> pod uivatelskım jménem <b>%2\$s</b>.<br /><br />. Jste si jisti, e zadané uivatelské jméno a heslo jsou správnì? Pro získání pomoci prosím kontaktujte odbornou pomoc svého ISP nebo systémového administrátora.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "Nepodaøilo se pøepnout do pasivního reimu na druhém (cílovém) FTP serveru <b>%1\$s</b>.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "Nepodaøilo se pøejmenovat adresáø nebo soubor <b>%1\$s</b> na <b>%2\$s</b>";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "Nepodaøilo se vykonat serverovı pøíkaz <b>%1\$s</b>. Vezmìte prosím na vìdomí, e pøíkaz CHMOD je k dispozici pouze na Unixovıch FTP serverech a ne na FTP serverech pod Windows.";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "Práva adresáøe <b>%1\$s</b> byla v poøádku zmìnìna na <b>%2\$s</b>";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "Processing entries within directory <b>%1\$s</b>:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "Práva souboru <b>%1\$s</b> byla v poøádku zmìnìna na <b>%2\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "Všechny oznaèené adresáøe a soubory byly zpracovány.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "Nepodaøilo se smazat adresáø <b>%1\$s</b>";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Nepodaøilo se smazat soubor <b>%1\$s</b>";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "Nepodaøilo se vytvoøit adresáø <b>%1\$s</b>";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "Nepodaøilo se vytvoøit doèasnı soubor";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "Nepodaøilo se získat soubor <b>%1\$s</b> z FTP serveru a uloit ho do doèasného souboru <b>%2\$s</b>.<br />Zkontrolujte oprávnìní adresáøe %3\$s.<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Nepodaøilo se otevøít doèasnı soubor. Zkontrolujte oprávnìní adresáøe %1\$s.";
$net2ftp_messages["Unable to read the temporary file"] = "Nepodaøilo se pøeèíst doèasnı soubor";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Nepodaøilo se uzavøít doèasnı soubor";
$net2ftp_messages["Unable to delete the temporary file"] = "Nepodaøilo se smazat doèasnı soubor";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "Nepodaøilo se vytvoøit doèasnı soubor. Zkontrolujte oprávnìní adresáøe %1\$s.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Nepodaøilo se otevøít doèasnı soubor. Zkontrolujte oprávnìní adresáøe %1\$s.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "Nepodaøilo se zapsat øetìzec do doèasného souboru <b>%1\$s</b>.<br />Zkontrolujte oprávnìní adresáøe %2\$s.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Nepodaøilo se uzavøít doèasnı soubor";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "Nepodaøilo se nahrát soubor <b>%1\$s</b> na FTP server.<br />Moná nemáte práva zápisu do daného adresáøe.";
$net2ftp_messages["Unable to delete the temporary file"] = "Nepodaøilo se smazat doèasnı soubor";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "Zpracování adresáøe <b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "Cílovı adresáø <b>%1\$s</b> je stejnı jako zdrojovı adresáø <b>%2\$s</b> nebo je jeho podadresáøem, tento adresáø proto bude pøeskoèen";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The directory <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "Nepodaøilo se vytvoøit podadresáø <b>%1\$s</b>. Moná u existuje. Pokraèuje se v kopírování/pøesouvání...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Created target subdirectory <b>%1\$s</b>";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "The directory <b>%1\$s</b> could not be selected, so this directory will be skipped";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "Nepodaøilo se smazat podadresáø <b>%1\$s</b> - moná není prázdnı";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "Podadresáø <b>%1\$s</b> byl smazán";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "Zpracování adresáøe <b>%1\$s</b> bylo dokonèeno";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "Cílové umístìní souboru <b>%1\$s</b> je stejné jako zdrojové, proto bude tento soubor pøeskoèen";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The file <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "The file <b>%1\$s</b> is too big to be copied, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "The file <b>%1\$s</b> is too big to be moved, aborting the move";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "Nepodaøilo se zkopírovat soubor <b>%1\$s</b>";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Copied file <b>%1\$s</b>";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Unable to move the file <b>%1\$s</b>, aborting the move";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "Soubor <b>%1\$s</b> byl pøesunut";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Nepodaøilo se smazat soubor <b>%1\$s</b>";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "Soubor <b>%1\$s</b> byl smazán";
$net2ftp_messages["All the selected directories and files have been processed."] = "Všechny oznaèené adresáøe a soubory byly zpracovány.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "Nepodaøilo se zkopírovat vzdálenı soubor <b>%1\$s</b> do místního v FTP reimu <b>%2\$s</b>";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "Nepodaøilo se smazat soubor <b>%1\$s</b>";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "The file is too big to be transferred";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "Denní limit dosaen: soubor <b>%1\$s</b> nebude pøenesen";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "Nepodaøilo se zkopírovat místní soubor do vzdáleného souboru <b>%1\$s</b> v FTP reimu <b>%2\$s</b>";
$net2ftp_messages["Unable to delete the local file"] = "Nepodaøilo se smazat místní soubor";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "Nepodaøilo se smazat doèasnı soubor";
$net2ftp_messages["Unable to send the file to the browser"] = "Nepodaøilo se odeslat soubor prohlíeèi";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "Nepodaøilo se vytvoøit doèasnı soubor";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "Soubor ZIP byl na FTP server uloen jako <b>%1\$s</b>";
$net2ftp_messages["Requested files"] = "Poadované soubory";

$net2ftp_messages["Dear,"] = "Váení,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "Nìkdo poádal o poslání souborù v pøíloze na vaši e-mailovou adresu (%1\$s).";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "Pokud o tom nic nevíte nebo dotyèné osobì nedùvìøujete, tak prosím tento e-mail smate, ani byste Zip soubor v pøíloze otevírali.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "Pokud Zip soubor neotevøete, nemohou soubory uvnitø poškodit váš poèítaè.";
$net2ftp_messages["Information about the sender: "] = "Informace o odesílateli: ";
$net2ftp_messages["IP address: "] = "IP adresa: ";
$net2ftp_messages["Time of sending: "] = "Èas odeslání: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "Posláno aplikací net2ftp nainstalovanou na tomto webovém serveru: ";
$net2ftp_messages["Webmaster's email: "] = "E-mail webmastera: ";
$net2ftp_messages["Message of the sender: "] = "Zpráva odesílatele: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp je volnı software, uvolnìnı pod licencí GNU/GPL. Pro více informací navštivte http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "Soubor ZIP byl odeslán na adresu <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "Soubor <b>%1\$s</b> je moc velkı. Tento soubor nebude nahrán.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded.";
$net2ftp_messages["Could not generate a temporary file."] = "Nepodaøilo se vytvoøit doèasnı soubor.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "Soubor <b>%1\$s</b> nemùe bıt pøesunut";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "Soubor <b>%1\$s</b> je v poøádku";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "Nepodaøilo se pøesunout nahranı soubor do doèasného adresáøe.<br /><br />Administrátor tohoto webového serveru musí na adresáø net2ftp /temp provést <b>chmod 777</b>.";
$net2ftp_messages["You did not provide any file to upload."] = "Neposkytl jste ádnı soubor, kterı se má nahrát";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "Soubor <b>%1\$s</b> nemohl bıt pøenesen na FTP server";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "Soubor <b>%1\$s</b> byl na FTP server pøenesen v reimu <b>%2\$s</b>";
$net2ftp_messages["Transferring files to the FTP server"] = "Pøenášení souborù na FTP server";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "Zpracování archivu è. %1\$s: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "Archiv <b>%1\$s</b> nebyl zpracován, protoe pøíponu jeho souboru se nepodaøilo rozpoznat. Momentálnì jsou podporovány pouze archivy zip, tar, tgz a gz.";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Unable to extract the files and directories from the archive";
$net2ftp_messages["Archive contains filenames with ../ or ..\\ - aborting the extraction"] = "Archive contains filenames with ../ or ..\\ - aborting the extraction";
$net2ftp_messages["Created directory %1\$s"] = "Created directory %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "Could not create directory %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "Copied file %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "Could not copy file %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "Unable to delete the temporary directory";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Unable to delete the temporary file %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "Nepodaøilo se vykonat pøíkaz serveru <b>%1\$s</b>";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "Vaše úloha byla zastavena";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "Úloha, kterou jste chtìli pomocí net2ftp provést, trvala více ne povolenıch %1\$s sekund, a proto byla zastavena.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "Tento èasovı limit zaruèuje spravedlivé vyuití webového serveru všemi uivateli.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "Pokuste se vaše úlohy rozdìlit na menší èásti: omezte vıbìr souborù a vynechejte ty nejvìtší.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "Pokud opravdu potøebujete, aby net2ftp dokázalo zpracovat nároèné dlouhotrvající úlohy, zvate instalaci net2ftp na vlastním serveru.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "Neuvedl jste ádnı text, kterı by se mìl poslat e-mailem!";
$net2ftp_messages["You did not supply a From address."] = "Neuvedl jste adresu odesílatele.";
$net2ftp_messages["You did not supply a To address."] = "Neuvedl jste adresu pøíjemce.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "Kvùli technickım problémùm nebylo moné odeslat e-mail na adresu <b>%1\$s</b>.";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "Prosím zadejte své pøihlašovací jméno a heslo k FTP serveru ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Nevyplnili jste pøihlašovací informace do vyskakovacího okénka.<br />Níe kliknìte na \"Pøejít na pøihlašovací stránku\".";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "Pøístup k Administraènímu panelu net2ftp je zakázanı, protoe v souboru settings.inc.php nebylo nastaveno ádné heslo. Vlote heslo do tohoto souboru a aktualizujte tuto stránku.";
$net2ftp_messages["Please enter your Admin username and password"] = "Zadejte prosím své administrátorské uivatelské jméno a heslo"; 
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Nevyplnili jste pøihlašovací informace do vyskakovacího okénka.<br />Níe kliknìte na \"Pøejít na pøihlašovací stránku\".";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "Šptané uivatelské jméno nebo heslo pro Administraèní panel net2ftp. Uivatelské jméno a heslo mùe bıt nastaveno v souboru settings.inc.php.";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "Modrá";
$net2ftp_messages["Grey"] = "Šedá";
$net2ftp_messages["Black"] = "Èerná";
$net2ftp_messages["Yellow"] = "lutá";
$net2ftp_messages["Pastel"] = "Pastelová";

// getMime()
$net2ftp_messages["Directory"] = "Adresáø";
$net2ftp_messages["Symlink"] = "Symlink" ;
$net2ftp_messages["ASP script"] = "ASP skript";
$net2ftp_messages["Cascading Style Sheet"] = "Kaskádovı styl";
$net2ftp_messages["HTML file"] = "HTML soubor";
$net2ftp_messages["Java source file"] = "Zdrojovı soubor Java";
$net2ftp_messages["JavaScript file"] = "soubor JavaScript";
$net2ftp_messages["PHP Source"] = "PHP zdrojovı kód";
$net2ftp_messages["PHP script"] = "PHP skript";
$net2ftp_messages["Text file"] = "Textovı soubor";
$net2ftp_messages["Bitmap file"] = "Bitmapa";
$net2ftp_messages["GIF file"] = "Obrázek GIF";
$net2ftp_messages["JPEG file"] = "Obrázek JPEG";
$net2ftp_messages["PNG file"] = "Obrázek PNG";
$net2ftp_messages["TIF file"] = "Obrázek TIF";
$net2ftp_messages["GIMP file"] = "Obrázek GIMP";
$net2ftp_messages["Executable"] = "Spustitelnı soubor";
$net2ftp_messages["Shell script"] = "Shellovskı skript";
$net2ftp_messages["MS Office - Word document"] = "MS Office - dokument Word";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - tabulka Excel";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - prezentace PowerPoint";
$net2ftp_messages["MS Office - Access database"] = "MS Office - databáze Access";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - kresba Visio";
$net2ftp_messages["MS Office - Project file"] = "MS Office - projekt aplikace Project";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - dokument Writer 6.0";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - šablona Writer 6.0";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - tabulka Calc 6.0";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - šablona Calc 6.0";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - dokument Draw 6.0";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - šablona Draw 6.0";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - prezentace Impress 6.0";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - šablona Impress 6.0";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - globální dokument Writer 6.0";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - dokument Math 6.0";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - dokument StarWriter 5.x";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - globální dokument StarWriter 5.x";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - tabulka StarCalc 5.x";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - dokument StarDraw 5.x";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - prezentace StarImpress 5.x";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - soubor StarImpress Packed 5.x";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - dokument StarMath 5.x";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - dokument StarChart 5.x";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - soubor pošty StarMail 5.x";
$net2ftp_messages["Adobe Acrobat document"] = "dokument Adobe Acrobat";
$net2ftp_messages["ARC archive"] = "Archiv ARC";
$net2ftp_messages["ARJ archive"] = "Archiv ARJ";
$net2ftp_messages["RPM"] = "RPM" ;
$net2ftp_messages["GZ archive"] = "Archiv GZ";
$net2ftp_messages["TAR archive"] = "Archiv TAR";
$net2ftp_messages["Zip archive"] = "Archiv Zip";
$net2ftp_messages["MOV movie file"] = "Video MOV";
$net2ftp_messages["MPEG movie file"] = "Video MPEG";
$net2ftp_messages["Real movie file"] = "Video Real";
$net2ftp_messages["Quicktime movie file"] = "Video Quicktime";
$net2ftp_messages["Shockwave flash file"] = "Shockwave Flash";
$net2ftp_messages["Shockwave file"] = "Soubor Shockwave";
$net2ftp_messages["WAV sound file"] = "Zvuk WAV";
$net2ftp_messages["Font file"] = "Soubor fontu";
$net2ftp_messages["%1\$s File"] = "Soubor %1\$s";
$net2ftp_messages["File"] = "Soubor";

// getAction()
$net2ftp_messages["Back"] = "Zpìt";
$net2ftp_messages["Submit"] = "Odeslat";
$net2ftp_messages["Refresh"] = "Obnovit";
$net2ftp_messages["Details"] = "Detaily";
$net2ftp_messages["Icons"] = "Ikony";
$net2ftp_messages["List"] = "Seznam";
$net2ftp_messages["Logout"] = "Odhlásit";
$net2ftp_messages["Help"] = "Nápovìda";
$net2ftp_messages["Bookmark"] = "Oblíbené";
$net2ftp_messages["Save"] = "Uloit";
$net2ftp_messages["Default"] = "Vıchozí";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Help Guide";
$net2ftp_messages["Forums"] = "Forums";
$net2ftp_messages["License"] = "Licence";
$net2ftp_messages["Powered by"] = "Bìí díky";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions.";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "Administrátorské funkce";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "Informace o verzi";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "This version of net2ftp is up-to-date.";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server.";
$net2ftp_messages["Logging"] = "Záznamy";
$net2ftp_messages["Date from:"] = "Datum od:";
$net2ftp_messages["to:"] = "do:";
$net2ftp_messages["Empty logs"] = "Vyprázdnit záznamy";
$net2ftp_messages["View logs"] = "Zobrazit záznamy";
$net2ftp_messages["Go"] = "OK";
$net2ftp_messages["Setup MySQL tables"] = "Nastavit MySQL tabulky";
$net2ftp_messages["Create the MySQL database tables"] = "Vytvoøit databázové MySQL tabulky";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "Administrátorské funkce";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "The handle of file %1\$s could not be opened.";
$net2ftp_messages["The file %1\$s could not be opened."] = "The file %1\$s could not be opened.";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "The handle of file %1\$s could not be closed.";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered.";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "Unable to select the database <b>%1\$s</b>.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "The SQL query nr <b>%1\$s</b> could not be executed.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "The SQL query nr <b>%1\$s</b> was executed successfully.";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "Please enter your MySQL settings:";
$net2ftp_messages["MySQL username"] = "MySQL uivatelské jméno";
$net2ftp_messages["MySQL password"] = "MySQL heslo";
$net2ftp_messages["MySQL database"] = "MySQL databáze";
$net2ftp_messages["MySQL server"] = "MySQL server" ;
$net2ftp_messages["This SQL query is going to be executed:"] = "Vykoná se tento SQL dotaz:";
$net2ftp_messages["Execute"] = "Spuštìní";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "Pouitá nastavení:";
$net2ftp_messages["MySQL password length"] = "Délka MySQL hesla";
$net2ftp_messages["Results:"] = "Vısledky:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "Administrátorské funkce";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "Unable to execute the SQL query <b>%1\$s</b>.";
$net2ftp_messages["No data"] = "ádná data";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "Administrátorské funkce";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "Tabulka <b>%1\$s</b> byla úspìšnì vyprázdnìna.";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "Nepodaøilo se vyprázdnit tabulku <b>%1\$s</b>.";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "The table <b>%1\$s</b> was optimized successfully.";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "The table <b>%1\$s</b> could not be optimized.";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "Pokroèilé funkce";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "OK";
$net2ftp_messages["Disabled"] = "Disabled";
$net2ftp_messages["Advanced FTP functions"] = "Advanced FTP functions";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "Send arbitrary FTP commands to the FTP server";
$net2ftp_messages["This function is available on PHP 5 only"] = "This function is available on PHP 5 only";
$net2ftp_messages["Troubleshooting functions"] = "Funkce pro odstraòování problémù";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "Øešení problémù net2ftp na tomto webovém serveru";
$net2ftp_messages["Troubleshoot an FTP server"] = "Øešení problémù FTP serveru";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Test the net2ftp list parsing rules";
$net2ftp_messages["Translation functions"] = "Pøekladatelské funkce";
$net2ftp_messages["Introduction to the translation functions"] = "Úvod k pøekladatelskım funkcím";
$net2ftp_messages["Extract messages to translate from code files"] = "Získat zprávy k pøeklady ze zdrojovıch souborù";
$net2ftp_messages["Check if there are new or obsolete messages"] = "Zkontrolovat pøítomnost novıch nebo zastaralıch zpráv";

$net2ftp_messages["Beta functions"] = "Beta funkce";
$net2ftp_messages["Send a site command to the FTP server"] = "Poslat serverovı pøíkaz FTP serveru";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: chránit adresáø heslem, vytvoøit vlastní chybové stránky";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: spustit SQL dotaz";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "Funkce ovládání serveru nejsou na tomto webovém serveru k dispozici.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "Funkce Apache nejsou na tomto webovém serveru k dispozici.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "Funkce MySQL nejsou na tomto webovém serveru k dispozici.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "Neoèekávanı stavovı øetìzec 2. Konèím.";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "Øešení problémù FTP serveru";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "Nastavení pøipojení:";
$net2ftp_messages["FTP server"] = "FTP server" ;
$net2ftp_messages["FTP server port"] = "Port FTP serveru";
$net2ftp_messages["Username"] = "Uivatelské jméno";
$net2ftp_messages["Password"] = "Heslo";
$net2ftp_messages["Password length"] = "Délka hesla";
$net2ftp_messages["Passive mode"] = "Pasivní reim";
$net2ftp_messages["Directory"] = "Adresáø";
$net2ftp_messages["Printing the result"] = "Printing the result";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "Pøipojování k FTP serveru: ";
$net2ftp_messages["Logging into the FTP server: "] = "Logování na FTP server: ";
$net2ftp_messages["Setting the passive mode: "] = "Nastavování pasivního reimu: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Získávání typu systému FTP serveru: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Zmìna adresáøe na %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Adresáø z FTP serveru je: %1\$s";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Získávání syrového seznamu adresáøù a souborù: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Druhı pokus získání syrového seznamu adresáøù a souborù: ";
$net2ftp_messages["Closing the connection: "] = "Zavírání pøipojení: ";
$net2ftp_messages["Raw list of directories and files:"] = "Syrovı seznam adresáøù a souborù:";
$net2ftp_messages["Parsed list of directories and files:"] = "Zpracovanı seznam adresáøù a souborù:";

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

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "Øešení problémù vaší instalace net2ftp";
$net2ftp_messages["Printing the result"] = "Printing the result";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "Kontrolování pøítomnosti FTP modulu PHP: ";
$net2ftp_messages["yes"] = "ano";
$net2ftp_messages["no - please install it!"] = "ne - prosím nainstalujte ho";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "Kontrolování práv adresáøe na webovém serveru: do /temp bude zapsán a následnì smazán malı soubor.";
$net2ftp_messages["Creating filename: "] = "Vytváøení souboru: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "OK. Soubor: %1\$s";
$net2ftp_messages["not OK"] = "not OK";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "není OK. Zkontrolujte oprávnìní adresáøe %1\$s.";
$net2ftp_messages["Opening the file in write mode: "] = "Otevírání souboru v reimu zápisu: ";
$net2ftp_messages["Writing some text to the file: "] = "Zapisování nìjakého textu do souboru: ";
$net2ftp_messages["Closing the file: "] = "Zavírání souboru: ";
$net2ftp_messages["Deleting the file: "] = "Mazání souboru: ";

$net2ftp_messages["Testing the FTP functions"] = "Testing the FTP functions";
$net2ftp_messages["Connecting to a test FTP server: "] = "Connecting to a test FTP server: ";
$net2ftp_messages["Connecting to the FTP server: "] = "Pøipojování k FTP serveru: ";
$net2ftp_messages["Logging into the FTP server: "] = "Logování na FTP server: ";
$net2ftp_messages["Setting the passive mode: "] = "Nastavování pasivního reimu: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Získávání typu systému FTP serveru: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Zmìna adresáøe na %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Adresáø z FTP serveru je: %1\$s";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Získávání syrového seznamu adresáøù a souborù: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Druhı pokus získání syrového seznamu adresáøù a souborù: ";
$net2ftp_messages["Closing the connection: "] = "Zavírání pøipojení: ";
$net2ftp_messages["Raw list of directories and files:"] = "Syrovı seznam adresáøù a souborù:";
$net2ftp_messages["Parsed list of directories and files:"] = "Zpracovanı seznam adresáøù a souborù:";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "not OK";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "Pøidejte tento odkaz do svıch záloek:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: kliknìte na odkaz pravım tlaèítkem myši a vyberte \"Pøidat k oblíbenım polokám...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "Poznámka: Kdy pouijete tento odkaz, zeptá se vás vyskakovací okénko na uivatelské jméno a heslo.";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "Vyberte adresáø";
$net2ftp_messages["Please wait..."] = "Prosím èekejte...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "Adresáøe, jejich jméno obsahuje \', nemohou bıt korektnì zobrazeny. Mohou bıt pouze smazány. Prosím vrate se zpìt a vyberte jinı jinı podadresáø.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "Denní limit byl dosaen: nebudete moci pøenášet data";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "Pro zajištìní spravedlivého uívání webového serveru kımkoliv je objem pøenešenıch dat a èasu spuštìní skriptù omezen pro kadou dvojici uivatel - den. Jakmile je tento limit dosaen, mùete procházet FTP server, ale u na nìj ani z nìj nemùete pøenášet data.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "Pokud potøebujete neomezené pouití, tak prosím net2ftp nainstalujte na vlastní webovı server.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "Novı adresáø";
$net2ftp_messages["New file"] = "Novı soubor";
$net2ftp_messages["HTML templates"] = "HTML templates";
$net2ftp_messages["Upload"] = "Nahrát";
$net2ftp_messages["Java Upload"] = "Java Nahrát";
$net2ftp_messages["Flash Upload"] = "Flash Upload";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Advanced"] = "Pokroèilé";
$net2ftp_messages["Copy"] = "Kopírovat";
$net2ftp_messages["Move"] = "Pøesunout";
$net2ftp_messages["Delete"] = "Smazat";
$net2ftp_messages["Rename"] = "Pøejmenovat";
$net2ftp_messages["Chmod"] = "Práva";
$net2ftp_messages["Download"] = "Stáhnout";
$net2ftp_messages["Unzip"] = "Unzip";
$net2ftp_messages["Zip"] = "Zip" ;
$net2ftp_messages["Size"] = "Velikost";
$net2ftp_messages["Search"] = "Vyhledat";
$net2ftp_messages["Go to the parent directory"] = "Pøejít do nadøazeného adresáøe";
$net2ftp_messages["Go"] = "OK";
$net2ftp_messages["Transform selected entries: "] = "Zmìnit vybrané poloky: ";
$net2ftp_messages["Transform selected entry: "] = "Transform selected entry: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "Vytvoøit novı podadresáø v adresáøi %1\$s";
$net2ftp_messages["Create a new file in directory %1\$s"] = "Vytvoøit novı soubor v adresáøi %1\$s";
$net2ftp_messages["Create a website easily using ready-made templates"] = "Create a website easily using ready-made templates";
$net2ftp_messages["Upload new files in directory %1\$s"] = "Nahrát nové soubory do adresáøe %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Upload directories and files using a Java applet";
$net2ftp_messages["Upload files using a Flash applet"] = "Upload files using a Flash applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Install software packages (requires PHP on web server)";
$net2ftp_messages["Go to the advanced functions"] = "Pøejít na pokroèilé funkce";
$net2ftp_messages["Copy the selected entries"] = "Zkopírovat vybrané poloky";
$net2ftp_messages["Move the selected entries"] = "Pøesunout vybrané poloky";
$net2ftp_messages["Delete the selected entries"] = "Smazat vybrané poloky";
$net2ftp_messages["Rename the selected entries"] = "Pøejmenovat vybrané poloky";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "Zmìnit práva u vybranıch poloek (funguje pouze na serverech Unix/Linux/BSD)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "Stáhnout ZIP soubor obsahující všechny vybrané poloky";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Unzip the selected archives on the FTP server";
$net2ftp_messages["Zip the selected entries to save or email them"] = "Zazipovat vybrané poloky a uloit nebo poslat e-mailem";
$net2ftp_messages["Calculate the size of the selected entries"] = "Spoèítat velikost vybranıch poloek";
$net2ftp_messages["Find files which contain a particular word"] = "Najít soubory obsahující zadané slovo";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "Kliknìte pro sestupné setøídìní podle sloupce %1\$s.";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "Kliknìte pro vzestupné setøídìní podle sloupce %1\$s.";
$net2ftp_messages["Ascending order"] = "Vzestupné tøídìní";
$net2ftp_messages["Descending order"] = "Sestupné tøídìní";
$net2ftp_messages["Upload files"] = "Upload files";
$net2ftp_messages["Up"] = "Vıše";
$net2ftp_messages["Click to check or uncheck all rows"] = "Pøepnout zaškrtnutí všech øádek";
$net2ftp_messages["All"] = "Vše";
$net2ftp_messages["Name"] = "Název";
$net2ftp_messages["Type"] = "Typ";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "Vlastník";
$net2ftp_messages["Group"] = "Skupina";
$net2ftp_messages["Perms"] = "Práva";
$net2ftp_messages["Mod Time"] = "Èas zmìny";
$net2ftp_messages["Actions"] = "Akce";
$net2ftp_messages["Select the directory %1\$s"] = "Select the directory %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "Select the file %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "Select the symlink %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "Go to the subdirectory %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "Stáhnout soubor %1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "Follow symlink %1\$s";
$net2ftp_messages["View"] = "Zobrazit";
$net2ftp_messages["Edit"] = "Upravit";
$net2ftp_messages["Update"] = "Aktualizovat";
$net2ftp_messages["Open"] = "Otevøít";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Zobrazit zvıraznìnı zdrojovı kód souboru %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Upravit zdrojovı kód souboru %1\$s";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "Nahrát novou verzi souboru %1\$s a slouèit zmìny";
$net2ftp_messages["View image %1\$s"] = "Zobrazit obrázek %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "Zobrazit soubor %1\$s z vašeho HTTP webového serveru";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(Poznámka: tento odkaz nemusí fungovat, pokud nemáte vlastní doménu.)";
$net2ftp_messages["This folder is empty"] = "Tento adresáø je prázdnı";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "Adresáøe";
$net2ftp_messages["Files"] = "Soubory";
$net2ftp_messages["Symlinks"] = "Symlinky";
$net2ftp_messages["Unrecognized FTP output"] = "Nerozpoznanı FTP vıstup";
$net2ftp_messages["Number"] = "Number";
$net2ftp_messages["Size"] = "Velikost";
$net2ftp_messages["Skipped"] = "Skipped";
$net2ftp_messages["Data transferred from this IP address today"] = "Data transferred from this IP address today";
$net2ftp_messages["Data transferred to this FTP server today"] = "Data transferred to this FTP server today";

// printLocationActions()
$net2ftp_messages["Language:"] = "Jazyk:";
$net2ftp_messages["Skin:"] = "Motiv:";
$net2ftp_messages["View mode:"] = "Reim zobrazení:";
$net2ftp_messages["Directory Tree"] = "Strom adresáøù";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "Spustit %1\$s v novém oknì";
$net2ftp_messages["This file is not accessible from the web"] = "This file is not accessible from the web";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "Poklepejte pro pøechod do podadresáøe:";
$net2ftp_messages["Choose"] = "Vybrat";
$net2ftp_messages["Up"] = "Vıše";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "Velikost vybranıch adresáøù a souborù";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "Celková velikost vybranıch adresáøù a souborù je:";
$net2ftp_messages["The number of files which were skipped is:"] = "The number of files which were skipped is:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "Zmìnit práva adresáøùm a souborùm";
$net2ftp_messages["Set all permissions"] = "Nastavit všechna práva";
$net2ftp_messages["Read"] = "Ètení";
$net2ftp_messages["Write"] = "Zápis";
$net2ftp_messages["Execute"] = "Spuštìní";
$net2ftp_messages["Owner"] = "Vlastník";
$net2ftp_messages["Group"] = "Skupina";
$net2ftp_messages["Everyone"] = "Kdokoliv";
$net2ftp_messages["To set all permissions to the same values, enter those permissions and click on the button \"Set all permissions\""] = "Pokud chcete nastavit všechna práva na stejnou hodnotu, zadejte tato práva vıše a stisknìte tlaèítko \"Nastavit všechna práva\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "Nastavit práva adresáøe <b>%1\$s</b> na: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "Nastavit práva souboru <b>%1\$s</b> na: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "Nastavit práva symlinku <b>%1\$s</b> na: ";
$net2ftp_messages["Chmod value"] = "Hodnota pro chmod";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "Zmìnit práva také všem podadresáøùm v tomto adresáøi";
$net2ftp_messages["Chmod also the files within this directory"] = "Zmìnit práva také všem souborùm v tomto adresáøi";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "Èíslo pro chmod <b>%1\$s</b> je mimo rozsah 000-777. Zkuste to prosím znovu.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "Vyberte adresáø";
$net2ftp_messages["Copy directories and files"] = "Kopírovat adresáøe a soubory";
$net2ftp_messages["Move directories and files"] = "Pøesunout adresáøe a soubory";
$net2ftp_messages["Delete directories and files"] = "Smazat adresáøe a soubory";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "Jste si jist, e chcete smazat tyto adresáøe a soubory?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "Ve vybranıch adresáøích budou smazány také všechny podadresáøe a soubory!";
$net2ftp_messages["Set all targetdirectories"] = "Nastavit všechny cílové adresáøe";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Pokud chcete nastavit obvyklı cílovı adresáø, zadejte ho do textového políèka vıše a stisknìte tlaèítko \"Nastavit všechny cílové adresáøe\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Poznámka: cílovı adresáø musí existovat døíve, ne do nìj bude cokoliv zkopírováno.";
$net2ftp_messages["Different target FTP server:"] = "Jinı cílovı FTP server:";
$net2ftp_messages["Username"] = "Uivatelské jméno";
$net2ftp_messages["Password"] = "Heslo";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "Nechte prázdné, pokud chcete soubory zkopírovat na stejnı FTP server.";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "Pokud chcete soubory zkopírovat na jinı FTP server, zadejte vaše pøihlašovací údaje.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "Nechte prázdné, pokud chcete soubory pøesunout na stejnı FTP server.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "Pokud chcete soubory pøesunout na jinı FTP server, zadejte vaše pøihlašovací údaje.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "Zkopírovat adresáø <b>%1\$s</b> do:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "Pøesunout adresáø <b>%1\$s</b> do:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "Adresáø <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "Zkopírovat soubor <b>%1\$s</b> do:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "Pøesunout soubor <b>%1\$s</b> do:";
$net2ftp_messages["File <b>%1\$s</b>"] = "Soubor <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "Zkopírovat symlink <b>%1\$s</b> do:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "Pøesunout symlink <b>%1\$s</b> do:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Symlink <b>%1\$s</b>" ;
$net2ftp_messages["Target directory:"] = "Cílovı adresáø:";
$net2ftp_messages["Target name:"] = "Cílové jméno:";
$net2ftp_messages["Processing the entries:"] = "Zpracování poloek:";

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
$net2ftp_messages["Size"] = "Velikost";
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
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: kliknìte na odkaz pravım tlaèítkem myši a vyberte \"Pøidat k oblíbenım polokám...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\"";

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
$net2ftp_messages["Unable to open the template file"] = "Nepodaøilo se otevøít soubor se šablonami";
$net2ftp_messages["Unable to read the template file"] = "Nepodaøilo se naèíst soubor se šablonami";
$net2ftp_messages["Please specify a filename"] = "Vyberte prosím soubor";
$net2ftp_messages["Status: This file has not yet been saved"] = "Stav: Tento soubor ještì nebyl uloen";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "Stav: Uloení <b>%1\$s</b> v módu %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "Stav: <b>Tento soubor nemùe bıt uloen</b>";
$net2ftp_messages["Not yet saved"] = "Not yet saved";
$net2ftp_messages["Could not be saved"] = "Could not be saved";
$net2ftp_messages["Saved at %1\$s"] = "Saved at %1\$s";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "Adresáø: ";
$net2ftp_messages["File: "] = "Soubor: ";
$net2ftp_messages["New file name: "] = "Novı název souboru: ";
$net2ftp_messages["Character encoding: "] = "Character encoding: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "Poznámka: zmìnìní textového pole uloí zmìny";
$net2ftp_messages["Copy up"] = "Copy up";
$net2ftp_messages["Copy down"] = "Copy down";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "Prohledat adresáøe a soubory";
$net2ftp_messages["Search again"] = "Hledat znovu";
$net2ftp_messages["Search results"] = "Vısledky vyhledávání";
$net2ftp_messages["Please enter a valid search word or phrase."] = "Zadejte prosím platné slovo nebo slovní spojení pro vyhledávání.";
$net2ftp_messages["Please enter a valid filename."] = "Zadejte prosím platné jméno souboru.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "Zadejte prosím platnou velikost souboru do textového pole \"od\", napøíklad 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "Zadejte prosím platnou velikost souboru do textového pole \"do\", napøíklad 50000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "Zadejte prosím platné datum ve formátu Y-m-d do textového pole \"od\".";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "Zadejte prosím platné datum ve formátu Y-m-d do textového pole \"do\".";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "Slovo <b>%1\$s</b> nebylo ve vybranıch adresáøích a souborech nalezeno.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "Slovo <b>%1\$s</b> bylo nalezeno v tìchto souborech:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "Hledat slovo nebo slovní spojení";
$net2ftp_messages["Case sensitive search"] = "Rozlišovat velikost písmen";
$net2ftp_messages["Restrict the search to:"] = "Omezit vyhledávání na:";
$net2ftp_messages["files with a filename like"] = "soubory se jménem vyhovujícímu";
$net2ftp_messages["(wildcard character is *)"] = "(zástupnı znak je *)";
$net2ftp_messages["files with a size"] = "soubory s velikostí";
$net2ftp_messages["files which were last modified"] = "soubory, které byly naposledy zmìnìny";
$net2ftp_messages["from"] = "od";
$net2ftp_messages["to"] = "do";

$net2ftp_messages["Directory"] = "Adresáø";
$net2ftp_messages["File"] = "Soubor";
$net2ftp_messages["Line"] = "Line";
$net2ftp_messages["Action"] = "Action";
$net2ftp_messages["View"] = "Zobrazit";
$net2ftp_messages["Edit"] = "Upravit";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Zobrazit zvıraznìnı zdrojovı kód souboru %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Upravit zdrojovı kód souboru %1\$s";

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
$net2ftp_messages["Unable to open the template file"] = "Nepodaøilo se otevøít soubor se šablonami";
$net2ftp_messages["Unable to read the template file"] = "Nepodaøilo se naèíst soubor se šablonami";
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
$net2ftp_messages["Upload"] = "Nahrát";
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

$net2ftp_messages["FTP server"] = "FTP server" ;
$net2ftp_messages["Example"] = "Pøíklad";
$net2ftp_messages["Port"] = "Port";
$net2ftp_messages["Username"] = "Uivatelské jméno";
$net2ftp_messages["Password"] = "Heslo";
$net2ftp_messages["Anonymous"] = "Anonymní";
$net2ftp_messages["Passive mode"] = "Pasivní reim";
$net2ftp_messages["Initial directory"] = "Vıchozí adresáø";
$net2ftp_messages["Language"] = "Jazyk";
$net2ftp_messages["Skin"] = "Motiv";
$net2ftp_messages["FTP mode"] = "FTP mód";
$net2ftp_messages["Automatic"] = "Automatic";
$net2ftp_messages["Login"] = "Pøihlásit";
$net2ftp_messages["Clear cookies"] = "Vymazat cookies";
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
$net2ftp_messages["Username"] = "Uivatelské jméno";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Password"] = "Heslo";
$net2ftp_messages["Login"] = "Pøihlásit";
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
$net2ftp_messages["Create new directories"] = "Vytvoøit nové adresáøe";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "Nové adresáøe budou vytvoøené v <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "Jméno nového adresáøe:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "Adresáø <b>%1\$s</b> byl v poøádku vytvoøen.";
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
$net2ftp_messages["Rename directories and files"] = "Pøejmenovat adresáøe nebo soubory";
$net2ftp_messages["Old name: "] = "Staré jméno: ";
$net2ftp_messages["New name: "] = "Nové jméno: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "Nové jméno nesmí obsahovat ádné teèky. Tato poloka byla pøejmenována na <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> byl úspìšnì pøejmenován na <b>%2\$s</b>";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> nemohl bıt pøejmenován na <b>%2\$s</b>";

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
$net2ftp_messages["Set all targetdirectories"] = "Nastavit všechny cílové adresáøe";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Pokud chcete nastavit obvyklı cílovı adresáø, zadejte ho do textového políèka vıše a stisknìte tlaèítko \"Nastavit všechny cílové adresáøe\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Poznámka: cílovı adresáø musí existovat døíve, ne do nìj bude cokoliv zkopírováno.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Unzip archive <b>%1\$s</b> to:";
$net2ftp_messages["Target directory:"] = "Cílovı adresáø:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Pouít jména adresáøù (automaticky vytvoøit podadresáøe)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "Aktualizovat soubor";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>VAROVÁNÍ: TATO FUNKCE JE STÁLE V POÈÁTEÈNÍ FÁZI VİVOJE. POUÍVEJTE JI POUZE NA TESTOVACÍ SOUBORY! BYL JSTE VAROVÁN!</b>";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "Známé potíe: - odstraòuje tabulátory - nepracuje korektnì s velkımi soubory (> 50kB) - dosud nebylo testováno se soubory, které obsahují nestandardní znaky</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "Tato funkce vám umoòuje nahrát novou verzi vybraného souboru, abyste se mohli podívat, jaké byly provedeny zmìny, a následnì mohli kadou zmìnu schválit nebo zamítnout. Pøed tím, ne je cokoliv uloeno, mùete slouèenı soubor editovat.";
$net2ftp_messages["Old file:"] = "Starı soubor:";
$net2ftp_messages["New file:"] = "Novı soubor:";
$net2ftp_messages["Restrictions:"] = "Omezení:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Maximální velikost jednoho souboru je v net2ftp omezena na <b>%1\$s kB</b> a v PHP na <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Maximální èas provádìní je <b>%1\$s sekund</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "Reim FTP pøenosu (ASCII nebo BINARY) bude automaticky nastaven podle koncovky";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Pokud cílovı soubor existuje, bude pøepsán";
$net2ftp_messages["You did not provide any files or archives to upload."] = "Neposkytl jste ádné soubory nebo archivy, které chcete nahrát.";
$net2ftp_messages["Unable to delete the new file"] = "Nepodaøilo se smazat novı soubor";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "Prosím èekejte...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "Vyberte øádky níe, schvalte nebo zamítnìte zmìny a odešlete formuláø.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "Nahrát do adresáøe:";
$net2ftp_messages["Files"] = "Soubory";
$net2ftp_messages["Archives"] = "Archivù";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "Zde zadané soubory budou pøenesené na FTP server.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "Zde zadané archivy budou rozbaleny a soubory uvnitø budou pøeneseny na FTP server.";
$net2ftp_messages["Add another"] = "Pøidat další";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Pouít jména adresáøù (automaticky vytvoøit podadresáøe)";

$net2ftp_messages["Choose a directory"] = "Vyberte adresáø";
$net2ftp_messages["Please wait..."] = "Prosím èekejte...";
$net2ftp_messages["Uploading... please wait..."] = "Nahrávám... prosím èekejte...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "Pokud nahrání bude trvat déle ne povolenıch <b>%1\$s sekund<\/b>, musíte to zkusit znovu s ménì nebo menšími soubory.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "Toto okno se za nìkolik vteøin automaticky zavøe.";
$net2ftp_messages["Close window now"] = "Zavøít okno hned";

$net2ftp_messages["Upload files and archives"] = "Nahrát soubory a archivy";
$net2ftp_messages["Upload results"] = "Vısledky nahrání";
$net2ftp_messages["Checking files:"] = "Kontroluji soubory:";
$net2ftp_messages["Transferring files to the FTP server:"] = "Pøenášení souborù na FTP server:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "Rozbalování archivù a pøenášení souborù na FTP server:";
$net2ftp_messages["Upload more files and archives"] = "Nahrát více souborù a archivù";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "Omezení:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Maximální velikost jednoho souboru je v net2ftp omezena na <b>%1\$s kB</b> a v PHP na <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Maximální èas provádìní je <b>%1\$s sekund</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "Reim FTP pøenosu (ASCII nebo BINARY) bude automaticky nastaven podle koncovky";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Pokud cílovı soubor existuje, bude pøepsán";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "Zobrazit soubor %1\$s";
$net2ftp_messages["View image %1\$s"] = "Zobrazit obrázek %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "Prohlédnout si klip Macromedia ShockWave Flash %1\$s";
$net2ftp_messages["Image"] = "Obrázek";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "Pokud chcete uloit obrázek, tak na nìj kliknìte pravım tlaèítkem myši a zvolte 'Uloit obrázek jako...'";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "Zazipovat poloky";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "Uloit zip na FTP serveru jako:";
$net2ftp_messages["Email the zip file in attachment to:"] = "Poslat zip v pøíloze e-mailem na:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "Vezmìte prosím na vìdomí, e pøenášení souborù není anonymní: do e-mailu bude pøidána vaše IP adresa a èas odeslání.";
$net2ftp_messages["Some additional comments to add in the email:"] = "Další komentáø, kterı chcete pøipojit k e-mailu:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "Nezadali jste jméno zip souboru. Vrate se zpìt a zadejte jméno souboru.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "Zdá se, e e-mailová adresa, kterou jste zadal (%1\$s), není platná.<br />Zadejte prosím adresu ve formátu <b>uzivatel@domena.cz</b>";

} // end zip

?>