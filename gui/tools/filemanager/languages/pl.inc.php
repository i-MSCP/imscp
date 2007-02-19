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
$net2ftp_messages["en"] = "pl";

// HTML dir attribute: left-to-right (LTR) or right-to-left (RTL)
$net2ftp_messages["ltr"] = "ltr";

// CSS style: align left or right (use in combination with LTR or RTL)
$net2ftp_messages["left"] = "left";
$net2ftp_messages["right"] = "right";

// Encoding
$net2ftp_messages["iso-8859-1"] = "iso-8859-2";


// -------------------------------------------------------------------------
// Status messages
// -------------------------------------------------------------------------

// When translating these messages, keep in mind that the text should not be too long
// It should fit in the status textbox

$net2ftp_messages["Connecting to the FTP server"] = "£±czenie z serwerem FTP";
$net2ftp_messages["Logging into the FTP server"] = "Logging into the FTP server";
$net2ftp_messages["Setting the passive mode"] = "Setting the passive mode";
$net2ftp_messages["Getting the FTP system type"] = "Getting the FTP system type";
$net2ftp_messages["Changing the directory"] = "Changing the directory";
$net2ftp_messages["Getting the current directory"] = "Getting the current directory";
$net2ftp_messages["Getting the list of directories and files"] = "Pobieranie listy plików i katalogów";
$net2ftp_messages["Parsing the list of directories and files"] = "Parsing the list of directories and files";
$net2ftp_messages["Logging out of the FTP server"] = "Logging out of the FTP server";
$net2ftp_messages["Getting the list of directories and files"] = "Pobieranie listy plików i katalogów";
$net2ftp_messages["Printing the list of directories and files"] = "Tworzenie listy plików i katalogów";
$net2ftp_messages["Processing the entries"] = "Przetwarzanie sk³adowych listy";
$net2ftp_messages["Processing entry %1\$s"] = "Processing entry %1\$s";
$net2ftp_messages["Checking files"] = "Sprawdzanie plików";
$net2ftp_messages["Transferring files to the FTP server"] = "Przesy³anie plików na serwer FTP";
$net2ftp_messages["Decompressing archives and transferring files"] = "Dekompresja plików i przesy³anie";
$net2ftp_messages["Searching the files..."] = "Szukanie plików...";
$net2ftp_messages["Uploading new file"] = "Przesy³anie nowego pliku";
$net2ftp_messages["Reading the file"] = "Reading the file";
$net2ftp_messages["Parsing the file"] = "Parsing the file";
$net2ftp_messages["Reading the new file"] = "Czytanie nowego pliku";
$net2ftp_messages["Reading the old file"] = "Czytanie starego pliku";
$net2ftp_messages["Comparing the 2 files"] = "Porównywanie dwóch plików";
$net2ftp_messages["Printing the comparison"] = "Wyprowadzanie wyniku porównania";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "Sending FTP command %1\$s of %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Getting archive %1\$s of %2\$s from the FTP server";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Creating a temporary directory on the FTP server";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Setting the permissions of the temporary directory";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Copying the net2ftp installer script to the FTP server";
$net2ftp_messages["Script finished in %1\$s seconds"] = "Czas wykonania skryptu %1\$s sekund";
$net2ftp_messages["Script halted"] = "Skrypt zatrzymany";

// Used on various screens
$net2ftp_messages["Please wait..."] = "Proszê czekaæ...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Unexpected state string: %1\$s. Exiting.";
$net2ftp_messages["This beta function is not activated on this server."] = "Ta funkcja beta nie jest aktywna na tym serwerze.";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "This function has been disabled by the Administrator of this website.";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead.";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "Your root directory <b>%1\$s</b> does not exist or could not be selected.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted.";
$net2ftp_messages["Execute %1\$s in a new window"] = "Uruchom %1\$s w nowym oknie";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "Wybierz przynajmniej jeden plik lub katalog!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "Serwer <b>%1\$s</b> Nie znajduje siê na li¶cie dozwolonych serwerów.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "Serwer <b>%1\$s</b> jest na czarnej li¶cie.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "Twój adres IP (%1\$s) jest na czarnej li¶cie.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "Port %1\$s nie mo¿e byæ u¿yty.";

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


// -------------------------------------------------------------------------
// /includes/database.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php.";
$net2ftp_messages["Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php.";


// -------------------------------------------------------------------------
// /includes/errorhandling.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["An error has occured"] = "B³±d";
$net2ftp_messages["Go back"] = "Wstecz";
$net2ftp_messages["Go to the login page"] = "Przejd¼ do strony logowania";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "<a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">Modu³ FTP ala PHP</a> nie jest zainstalowany.<br /><br /> Administrator tej strony powinien zainstalowaæ ten modu³. Instrukcja instalacji znajduje siê pod adresem <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Nie mo¿na po³±czyæ siê z serwerem FTP <b>%1\$s</b> na porcie <b>%2\$s</b>.<br /><br />Czy jeste¶ pewny ¿e jest to adres serwera FTP? Czêsto serwer FTP ma inny adres ni¿ serwer HTTP. Skontaktuj siê z dostawc± internetu b±d¼ administratorem.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Nie mo¿na zalogowaæ siê na serwer FTP<b>%1\$s</b> u¿ytkownik <b>%2\$s</b>.<br /><br />czy jeste¶ pewien ¿e nazwa u¿ytkownika i has³o s± poprawne? Skontaktuj siê z dostawc± internetu b±d¼ administratorem.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "Nie mo¿na prze³±czyæ siê w tryb pasywny na serwerze FTP <b>%1\$s</b>.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Nie mo¿na po³±czyæ siê z drugim serwerem FTP <b>%1\$s</b> na porcie <b>%2\$s</b>.<br /><br />Czy jeste¶ pewny ¿e jest to adres drugiego serwera FTP? Czêsto serwer FTP ma inny adres ni¿ serwer HTTP. Skontaktuj siê z dostawc± internetu b±d¼ administratorem.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Nie mo¿na zalogowaæ siê na drugi serwer FTP<b>%1\$s</b> u¿ytkownik <b>%2\$s</b>.<br /><br />czy jeste¶ pewien ¿e nazwa u¿ytkownika i has³o s± poprawne? Skontaktuj siê z dostawc± internetu b±d¼ administratorem.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "ie mo¿na prze³±czyæ siê w tryb pasywny na drugim serwerze FTP <b>%1\$s</b>.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "Nie mo¿na zmieniæ nazwy katalogu lub pliku <b>%1\$s</b> na <b>%2\$s</b>";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers.";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "Zmieniono atrybuty katalogu <b>%1\$s</b> na <b>%2\$s</b>";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "Processing entries within directory <b>%1\$s</b>:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "Zmieniono atrybuty pliku <b>%1\$s</b> na <b>%2\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "Wszystkie zaznaczone pliki i katalogi zosta³y przetworzone.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "Nie mo¿na usun±æ katalogu <b>%1\$s</b>";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Nie mo¿na skasowaæ pliku <b>%1\$s</b>";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "Nie mo¿na utworzyæ katalogu <b>%1\$s</b>";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "Nie mo¿na utworzyæ pliku tymczasowego";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "Nie mo¿na pobraæ pliku <b>%1\$s</b> z serwera FTP i zapisaæ go jako plik tymczasowy <b>%2\$s</b>.<br />Sprawd¼ uprawnienia dla katalogu %3\$s.<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Nie mo¿na otworzyæ pliku tymczasowego. Sprawd¼ uprawnienia dla katalogu %1\$s.";
$net2ftp_messages["Unable to read the temporary file"] = "Nie mo¿na czytaæ pliku tymczasowego";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Nie mo¿na zamkn±æ pliku tymczasowego";
$net2ftp_messages["Unable to delete the temporary file"] = "Nie mo¿na skasowaæ pliku tymczasowego";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "Nie mo¿na utworzyæ pliku tymczasowego. Sprawd¼ uprawnienia dla katalogu %1\$s.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Nie mo¿na otworzyæ pliku tymczasowego. Sprawd¼ uprawnienia dla katalogu %1\$s.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "Nie mo¿na zapisaæ tekstu w pliku tymczasowym <b>%1\$s</b>.<br />Sprawd¼ uprawnienia dla katalogu %2\$s.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Nie mo¿na zamkn±æ pliku tymczasowego";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "Nie mo¿na umie¶ciæ pliku <b>%1\$s</b> na serwerze FTP.<br />Mo¿esz nie mieæ uprawnieñ do tego katalogu.";
$net2ftp_messages["Unable to delete the temporary file"] = "Nie mo¿na skasowaæ pliku tymczasowego";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "Przetwarzanie katalogu <b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The directory <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "Nie mo¿na utworzyæ podkatalogu <b>%1\$s</b>. Mo¿e ju¿ istnieæ. Kontynuujê proces kopiowania/przenoszenia...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Created target subdirectory <b>%1\$s</b>";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "The directory <b>%1\$s</b> could not be selected, so this directory will be skipped";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "Nie mo¿na usun±æ podkatalogu <b>%1\$s</b> - mo¿e nie jest pusty";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "Usuniêto podkatalog <b>%1\$s</b>";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "Przetwarzanie katalogu <b>%1\$s</b> zakoñczone";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "Plik docelowy <b>%1\$s</b> jest taki sam jak plik ¼ród³owy, wiêc zostanie pominiêty";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The file <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "The file <b>%1\$s</b> is too big to be copied, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "The file <b>%1\$s</b> is too big to be moved, aborting the move";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "Nie mo¿na skopiowaæ pliku <b>%1\$s</b>";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Copied file <b>%1\$s</b>";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Unable to move the file <b>%1\$s</b>, aborting the move";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "Przesuniêto plik <b>%1\$s</b>";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Nie mo¿na skasowaæ pliku <b>%1\$s</b>";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "Skasowano plik <b>%1\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "Wszystkie zaznaczone pliki i katalogi zosta³y przetworzone.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "Nie mo¿na skopiowaæ pliku ze zdalego systemu <b>%1\$s</b> do pliku lokalnego przy u¿yciu trybu FTP <b>%2\$s</b>";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "Nie mo¿na skasowaæ pliku <b>%1\$s</b>";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "The file is too big to be transferred";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "Daily limit reached: the file <b>%1\$s</b> will not be transferred";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "Nie mo¿na skopiowaæ pliku lokalnego do pliku zdalnego <b>%1\$s</b> przy u¿yciu trybu FTP <b>%2\$s</b>";
$net2ftp_messages["Unable to delete the local file"] = "Nie mo¿na usun±æ lokalnego pliku";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "Nie mo¿na skasowaæ pliku tymczasowego";
$net2ftp_messages["Unable to send the file to the browser"] = "Unable to send the file to the browser";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "Nie mo¿na utworzyæ pliku tymczasowego";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "Plik zip zosta³ zapisany na serwerze FTP jako <b>%1\$s</b>";
$net2ftp_messages["Requested files"] = "¯±dane pliki";

$net2ftp_messages["Dear,"] = "Witaj,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "Kto¶ za¿±da³ by pliki z za³±cznika zosta³y przes³ane na ten email (%1\$s).";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "Je¶li nic o tym nie wiesz lub nie znasz tej osoby, skasuj pliki z za³±cznika bez ich przegl±dania.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "Je¶li nie otworzysz plików zip, nic nie mo¿e siê staæ Twojemu komputerowi.";
$net2ftp_messages["Information about the sender: "] = "Informacje o nadawcy: ";
$net2ftp_messages["IP address: "] = "Adres IP: ";
$net2ftp_messages["Time of sending: "] = "Czas wys³ania: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "Wys³ane przez net2ftp zainstalowane na tym serwerze: ";
$net2ftp_messages["Webmaster's email: "] = "Adres webmastera: ";
$net2ftp_messages["Message of the sender: "] = "Wiadomo¶æ nadawcy: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp jest darmowym oprogramowaniem, na licencji GNU/GPL. Wiêcej informacji http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "Plik zip zosta³ wys³any do <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "Plik <b>%1\$s</b> jest zbyt du¿y. Ten plik nie mo¿e byæ pobrany.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded.";
$net2ftp_messages["Could not generate a temporary file."] = "Nie mo¿na wygenerowaæ pliku tymczasowego.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "Plik <b>%1\$s</b> nie mo¿e byæ przeniesiony";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "Plik <b>%1\$s</b> jest OK";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp.";
$net2ftp_messages["You did not provide any file to upload."] = "Nie wybrano ¿adnych plików do pobrania.";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "Plik <b>%1\$s</b> nie mo¿e byæ preniesiony na serwer FTP";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "Plik <b>%1\$s</b> zosta³ przeniesiony na serwer FTP w trybie <b>%2\$s</b>";
$net2ftp_messages["Transferring files to the FTP server"] = "Przesy³anie plików na serwer FTP";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "Przetwarzanie archiwum nr %1\$s: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "Archiwum <b>%1\$s</b> nie zosta³o przetworzone gdy¿ jego rozsze¿enie nie zosta³o rozpoznane. Obecnie tylko archiwa zip, tar, tgz i gz s± obs³ugiwane.";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Unable to extract the files and directories from the archive";
$net2ftp_messages["Created directory %1\$s"] = "Created directory %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "Could not create directory %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "Copied file %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "Could not copy file %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "Unable to delete the temporary directory";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Unable to delete the temporary file %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "Nie mo¿na wykonaæ lokalnego polecenia <b>%1\$s</b>";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "Twoje zadanie zosta³o zatrzymane";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "Zadanie, które chcia³e¶ wykonaæ w net2ftp zajê³o wiêcej czasu ni¿ dozwolone %1\$s sekund, dlatego zosta³o zatrzymane.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "Taki limit czasu umo¿liwia sprawiedliwe wykorzystanie serwera przez wszytkich u¿ytkowników.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "Spróbuj podzieliæ zadanie na mniejsze czê¶æi: Wybierz mniej plików, pomiñ najwiêksze pliki.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "Je¶li naprawdê potrzebujesz by net2frt wykonywa³ rozbudowane zadania, rozwa¿ instalacjê net2ftp na swoim serwerze.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "Nie wprowadzono ¿adnego tekstu do wys³ania emailem";
$net2ftp_messages["You did not supply a From address."] = "Nie wprowadzono pola OD";
$net2ftp_messages["You did not supply a To address."] = "Nie wprowadzono pol DO.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "W zwi±zku z problemami technicznymi email do <b>%1\$s</b> nie móg³ byæ wys³any.";


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
$net2ftp_messages["Blue"] = "Niebieska";
$net2ftp_messages["Grey"] = "Szara";
$net2ftp_messages["Black"] = "Czarna";
$net2ftp_messages["Yellow"] = "¯ó³ta";
$net2ftp_messages["Pastel"] = "Pastel";

// getMime()
$net2ftp_messages["Directory"] = "Katalog";
$net2ftp_messages["Symlink"] = "Link symboliczny";
$net2ftp_messages["ASP script"] = "Sktypt ASP";
$net2ftp_messages["Cascading Style Sheet"] = "Styl CSS";
$net2ftp_messages["HTML file"] = "Plik HTML";
$net2ftp_messages["Java source file"] = "Plik ¼ród³owy Java";
$net2ftp_messages["JavaScript file"] = "Plik JavaScript";
$net2ftp_messages["PHP Source"] = "Plik ¼ród³owy PHP";
$net2ftp_messages["PHP script"] = "Skrypt PHP";
$net2ftp_messages["Text file"] = "Plik tekstowy";
$net2ftp_messages["Bitmap file"] = "Bitmapa";
$net2ftp_messages["GIF file"] = "GIF";
$net2ftp_messages["JPEG file"] = "JPEG";
$net2ftp_messages["PNG file"] = "PNG";
$net2ftp_messages["TIF file"] = "TIF";
$net2ftp_messages["GIMP file"] = "GIMP";
$net2ftp_messages["Executable"] = "Wykonywalny";
$net2ftp_messages["Shell script"] = "Skrypt Shella";
$net2ftp_messages["MS Office - Word document"] = "MS Office - dokument Word";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - dokument Excel";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - prezentacja PowerPoint";
$net2ftp_messages["MS Office - Access database"] = "MS Office - baza Access";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - rysunek Visio";
$net2ftp_messages["MS Office - Project file"] = "MS Office - plik Projecta";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - dokument Writer 6.0";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - szablon Writer 6.0";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - dokument Calc 6.0";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - szablon Calc 6.0";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - dokument Draw 6.0";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - szablon Draw 6.0";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - prezentacja Impress 6.0";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - szablon Impress 6.0";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - dokument Writer 6.0 global";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - dokument Math 6.0";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - dokument StarWriter 5.x";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - dokument StarWriter 5.x global";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - dokument StarCalc 5.x";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - dokument StarDraw 5.x";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - prezentacja StarImpress 5.x";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - plik StarImpress Packed 5.x";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - dokument StarMath 5.x";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - dokument StarChart 5.x";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - plik poczty StarMail 5.x";
$net2ftp_messages["Adobe Acrobat document"] = "dokument Adobe Acrobat";
$net2ftp_messages["ARC archive"] = "archiwum ARC";
$net2ftp_messages["ARJ archive"] = "archiwum ARJ";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "archiwum GZ";
$net2ftp_messages["TAR archive"] = "archiwum TAR";
$net2ftp_messages["Zip archive"] = "archiwum Zip";
$net2ftp_messages["MOV movie file"] = "film MOV";
$net2ftp_messages["MPEG movie file"] = "film MPEG";
$net2ftp_messages["Real movie file"] = "film Real";
$net2ftp_messages["Quicktime movie file"] = "film Quicktime";
$net2ftp_messages["Shockwave flash file"] = "animacja Shockwave flash";
$net2ftp_messages["Shockwave file"] = "plik Shockwave";
$net2ftp_messages["WAV sound file"] = "d¼wiêk WAV";
$net2ftp_messages["Font file"] = "Czcionka";
$net2ftp_messages["%1\$s File"] = "plik %1\$s";
$net2ftp_messages["File"] = "Plik";

// getAction()
$net2ftp_messages["Back"] = "Wstecz";
$net2ftp_messages["Submit"] = "Wy¶lij";
$net2ftp_messages["Refresh"] = "Od¶wie¿";
$net2ftp_messages["Details"] = "Szczegó³y";
$net2ftp_messages["Icons"] = "Ikony";
$net2ftp_messages["List"] = "Lista";
$net2ftp_messages["Logout"] = "Wyloguj";
$net2ftp_messages["Help"] = "Pomoc";
$net2ftp_messages["Bookmark"] = "Zak³adki";
$net2ftp_messages["Save"] = "Zapisz";
$net2ftp_messages["Default"] = "Domy¶lnie";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Help Guide";
$net2ftp_messages["Forums"] = "Forums";
$net2ftp_messages["License"] = "Licencja";
$net2ftp_messages["Powered by"] = "Obs³ugiwane przez";
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
$net2ftp_messages["Execute"] = "Wykonywanie";

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
$net2ftp_messages["Advanced functions"] = "Zaawansowane funkcje";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "Go";
$net2ftp_messages["Disabled"] = "Disabled";
$net2ftp_messages["Advanced FTP functions"] = "Advanced FTP functions";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "Send arbitrary FTP commands to the FTP server";
$net2ftp_messages["This function is available on PHP 5 only"] = "This function is available on PHP 5 only";
$net2ftp_messages["Troubleshooting functions"] = "Troubleshooting functions";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "Status net2ftp na tym serwerze";
$net2ftp_messages["Troubleshoot an FTP server"] = "Status serwera FTP";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Test the net2ftp list parsing rules";
$net2ftp_messages["Translation functions"] = "T³umaczenie";
$net2ftp_messages["Introduction to the translation functions"] = "Introduction to the translation functions";
$net2ftp_messages["Extract messages to translate from code files"] = "Extract messages to translate from code files";
$net2ftp_messages["Check if there are new or obsolete messages"] = "Check if there are new or obsolete messages";

$net2ftp_messages["Beta functions"] = "Beta functions";
$net2ftp_messages["Send a site command to the FTP server"] = "Send a site command to the FTP server";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: password-protect a directory, create custom error pages";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: execute an SQL query";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "Lokalne polecenia nie s± dostêpne na tym serwerze.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "Funkcje Apacha nie s± dostêpne na tym serwerze.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "Funkcje MySQL nie s± dostêpne na tym serwerze.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "Nieznana wartosæ zmiennej state2. Koñczenie dzia³ania.";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "Status serwera FTP";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "Connection settings:";
$net2ftp_messages["FTP server"] = "Serwer FTP";
$net2ftp_messages["FTP server port"] = "Port serwera FTP";
$net2ftp_messages["Username"] = "U¿ytkownik";
$net2ftp_messages["Password"] = "Has³o";
$net2ftp_messages["Password length"] = "Password length";
$net2ftp_messages["Passive mode"] = "Tryb pasywny";
$net2ftp_messages["Directory"] = "Katalog";
$net2ftp_messages["Printing the result"] = "Printing the result";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "£±czenie z serwerem FTP: ";
$net2ftp_messages["Logging into the FTP server: "] = "Logowanie na serwer FTP: ";
$net2ftp_messages["Setting the passive mode: "] = "Ustawianie trybu pasywnego: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Getting the FTP server system type: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Zmiana na katalog %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Katalog na serwerze FTP: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Pobieranie listy plików i katalogów: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Ponowna próba pobrania listy plikó i katalogów: ";
$net2ftp_messages["Closing the connection: "] = "Zamykanie po³±czenia: ";
$net2ftp_messages["Raw list of directories and files:"] = "Lista plików i katalogów:";
$net2ftp_messages["Parsed list of directories and files:"] = "Przetworzona plików i katalogów:";

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

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "Status obecnej instalacji net2ftp";
$net2ftp_messages["Printing the result"] = "Printing the result";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "Sprawdzanie czy modu³ FTP jest zaninstalowany w PHP: ";
$net2ftp_messages["yes"] = "tak";
$net2ftp_messages["no - please install it!"] = "nie - konieczna jest jego instalacja!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "Sprawdzanie uprawnieñ katalogów na serwerze: w katalogu /temp zostanie utworzony a nastêpnie skasowany ma³y plik.";
$net2ftp_messages["Creating filename: "] = "Tworzenie pliku: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "OK. Plik: %1\$s";
$net2ftp_messages["not OK"] = "not OK";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "B³±d. Sprawd¼ uprawnienia katalogu %1\$s";
$net2ftp_messages["Opening the file in write mode: "] = "Opening the file in write mode: ";
$net2ftp_messages["Writing some text to the file: "] = "Zapisywanie przyk³adowego tekstu do pliku: ";
$net2ftp_messages["Closing the file: "] = "Zamykanie pliku: ";
$net2ftp_messages["Deleting the file: "] = "Kasowanie pliku: ";

$net2ftp_messages["Testing the FTP functions"] = "Testing the FTP functions";
$net2ftp_messages["Connecting to a test FTP server: "] = "Connecting to a test FTP server: ";
$net2ftp_messages["Connecting to the FTP server: "] = "£±czenie z serwerem FTP: ";
$net2ftp_messages["Logging into the FTP server: "] = "Logowanie na serwer FTP: ";
$net2ftp_messages["Setting the passive mode: "] = "Ustawianie trybu pasywnego: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Getting the FTP server system type: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Zmiana na katalog %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Katalog na serwerze FTP: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Pobieranie listy plików i katalogów: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Ponowna próba pobrania listy plikó i katalogów: ";
$net2ftp_messages["Closing the connection: "] = "Zamykanie po³±czenia: ";
$net2ftp_messages["Raw list of directories and files:"] = "Lista plików i katalogów:";
$net2ftp_messages["Parsed list of directories and files:"] = "Przetworzona plików i katalogów:";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "not OK";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "Dodaj tem link do zak³adek:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: kliknij prawym przycikiem na linku i wybierz \"Dodaj do ulubionych...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: kliknij prawym przycikiem na link i wybierz \"Dodaj stronê do zak³adek\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "Uwaga: je¶li u¿yjesz zapamiêtanej zak³adki, pojawi siê okno pytaj±ce o nazwê u¿ytkownika i has³o.";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "Wybierz katalog";
$net2ftp_messages["Please wait..."] = "Proszê czekaæ...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "Katalogi zawieraj±ce w nazwie \' nie mog± byæ poprawnie wy¶wietlane. Mog± zostaæ jedynie skasowane. Cofnij siê i wybie¿ inny podkatalog.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "Daily limit reached: you will not be able to transfer data";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "If you need unlimited usage, please install net2ftp on your own web server.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "Nowy katalog";
$net2ftp_messages["New file"] = "Nowy plik";
$net2ftp_messages["HTML templates"] = "HTML templates";
$net2ftp_messages["Upload"] = "Przy¶lij";
$net2ftp_messages["Java Upload"] = "Przy¶lij Java";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Advanced"] = "Zaawansowane";
$net2ftp_messages["Copy"] = "Kopiuj";
$net2ftp_messages["Move"] = "Przenie¶";
$net2ftp_messages["Delete"] = "Kasuj";
$net2ftp_messages["Rename"] = "Zmieñ nazwê";
$net2ftp_messages["Chmod"] = "Chmod";
$net2ftp_messages["Download"] = "Pobierz";
$net2ftp_messages["Unzip"] = "Unzip";
$net2ftp_messages["Zip"] = "Zip";
$net2ftp_messages["Size"] = "Rozmiar";
$net2ftp_messages["Search"] = "Szukaj";
$net2ftp_messages["Go to the parent directory"] = "Przejd¼ do katalogu macierzystego";
$net2ftp_messages["Go"] = "Go";
$net2ftp_messages["Transform selected entries: "] = "Zmieñ zaznaczone pozycje: ";
$net2ftp_messages["Transform selected entry: "] = "Transform selected entry: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "Utwórz nowy katalog w katalogu %1\$s";
$net2ftp_messages["Create a new file in directory %1\$s"] = "Utwórz nowy plik w katalogu %1\$s";
$net2ftp_messages["Create a website easily using ready-made templates"] = "Create a website easily using ready-made templates";
$net2ftp_messages["Upload new files in directory %1\$s"] = "Przy¶lij nowe pliki do katalogu %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Upload directories and files using a Java applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Install software packages (requires PHP on web server)";
$net2ftp_messages["Go to the advanced functions"] = "Poka¿ zaawansowane funkcje";
$net2ftp_messages["Copy the selected entries"] = "Kopiuj zaznaczone pozycjom";
$net2ftp_messages["Move the selected entries"] = "Przenie¶ zaznaczone pozycjom";
$net2ftp_messages["Delete the selected entries"] = "Kasuj zaznaczone pozycjom";
$net2ftp_messages["Rename the selected entries"] = "Zmieñ nazwy zaznaczonym pozycjom";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "Chmod zaznaczonym pozycjom (dzia³a jedynie na serwerach Unix/Linux/BSD)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "Pobierz plik zip zawieraj±cy wszytkie zaznaczone pozycje";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Unzip the selected archives on the FTP server";
$net2ftp_messages["Zip the selected entries to save or email them"] = "Zipuj zaznaczone pozycje i prze¶lij mailem";
$net2ftp_messages["Calculate the size of the selected entries"] = "Oblicz rozmiar zaznaczonych pozycji";
$net2ftp_messages["Find files which contain a particular word"] = "Szukaj plików zawieraj±cych podan± frazê";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "Kliknij by sortowaæ po %1\$s w porz±dku malej±cym";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "Kliknij by sortowaæ po %1\$s w porz±dku rosn±cym";
$net2ftp_messages["Ascending order"] = "Rosn±co";
$net2ftp_messages["Descending order"] = "Malej±co";
$net2ftp_messages["Up"] = "Do góry";
$net2ftp_messages["Click to check or uncheck all rows"] = "Kliknij by zaznaczyæ/odznaczyæ wszytkie pozycje";
$net2ftp_messages["All"] = "+/-";
$net2ftp_messages["Name"] = "Nazwa";
$net2ftp_messages["Type"] = "Typ";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "W³a¶ciciel";
$net2ftp_messages["Group"] = "Grupa";
$net2ftp_messages["Perms"] = "Atrybuty";
$net2ftp_messages["Mod Time"] = "Czas mod.";
$net2ftp_messages["Actions"] = "Dzia³ania";
$net2ftp_messages["Select the directory %1\$s"] = "Select the directory %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "Select the file %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "Select the symlink %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "Go to the subdirectory %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "Pobierz plik %1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "Follow symlink %1\$s";
$net2ftp_messages["View"] = "Podgl±d";
$net2ftp_messages["Edit"] = "Edycja";
$net2ftp_messages["Update"] = "Aktualizuj";
$net2ftp_messages["Open"] = "Otwórz";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Poka¿ kolorowany kod ¼ród³owy pliku %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Edytuj kod ¼ródowy pliku %1\$s";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "Prze¶lij now± wersjê pliku %1\$s i scal zmiany";
$net2ftp_messages["View image %1\$s"] = "Poka¿ obraz %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "Poka¿ plik %1\$s z Twojego swrwera HTTP";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(Uwaga: Ten link mo¿e nie dzia³aæ je¶li nie posiadasz w³asnej nazwy domeny.)";
$net2ftp_messages["This folder is empty"] = "Ten katalog jest pusty";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "Katalogi";
$net2ftp_messages["Files"] = "Pliki";
$net2ftp_messages["Symlinks"] = "Linki symboliczne";
$net2ftp_messages["Unrecognized FTP output"] = "Nie rozpoznane wyj¶cie FTP";
$net2ftp_messages["Number"] = "Number";
$net2ftp_messages["Size"] = "Rozmiar";
$net2ftp_messages["Skipped"] = "Skipped";

// printLocationActions()
$net2ftp_messages["Language:"] = "Jêzyk:";
$net2ftp_messages["Skin:"] = "Skórka:";
$net2ftp_messages["View mode:"] = "Tryb podgl±du:";
$net2ftp_messages["Directory Tree"] = "Drzewo katalogów";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "Uruchom %1\$s w nowym oknie";
$net2ftp_messages["This file is not accessible from the web"] = "This file is not accessible from the web";


// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "Kliknij dwukrotnie by przej¶æ do podkatalogu:";
$net2ftp_messages["Choose"] = "Wybierz";
$net2ftp_messages["Up"] = "Do góry";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "Rozmiar zaznaczonych plików i katalogów";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "Ca³kowity rozmiar zaznaczonych plików i katalogów:";
$net2ftp_messages["The number of files which were skipped is:"] = "The number of files which were skipped is:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "Zmiana atrybutów plików i katalogów";
$net2ftp_messages["Set all permissions"] = "Ustaw wszytkim";
$net2ftp_messages["Read"] = "Czytanie";
$net2ftp_messages["Write"] = "Pisanie";
$net2ftp_messages["Execute"] = "Wykonywanie";
$net2ftp_messages["Owner"] = "W³a¶ciciel";
$net2ftp_messages["Group"] = "Grupa";
$net2ftp_messages["Everyone"] = "Wszyscy";
$net2ftp_messages["To set all permissions to the same values, enter those permissions above and click on the button \"Set all permissions\""] = "Aby zmieniæ wszytkie uprawnienia na takie same warto¶ci, wprowad¼ te uprawnienia i naci¶nij przycisk \"Ustaw wszytkie uprawnienia\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "Zmieniono uprawnienia katalogowi <b>%1\$s</b> to: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "Zmieniono uprawnienia plikowi <b>%1\$s</b> to: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "Zmieniono uprawnienia linkowi symbolicznemu <b>%1\$s</b> to: ";
$net2ftp_messages["Chmod value"] = "warto¶æ Chmod";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "Zmien uprawnienia równie¿ podkatalogom tego katalogu";
$net2ftp_messages["Chmod also the files within this directory"] = "Zmien uprawnienia równie¿ wszytkim plikom w tym katalogu";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "Chmod nr <b>%1\$s</b> nie jest z zakresu 000-777. Spróbuj jeszcze raz.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "Wybierz katalog";
$net2ftp_messages["Copy directories and files"] = "Kopiowanie plików i katalogów";
$net2ftp_messages["Move directories and files"] = "Przenoszenie plików i katalogów";
$net2ftp_messages["Delete directories and files"] = "Kasowanie plików i katalogów";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "Czy napewno chcesz usun±æ te pliki i katalogi?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "Wszytkie podkatalogi i pliki wybrane katalogu zostan± usuniête!";
$net2ftp_messages["Set all targetdirectories"] = "Ustaw dla wszytkich";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Aby wybraæ standardow± czynno¶æ dla katalogu, wprowad¼ nazwê katalogu w powy¿szym oknie i naci¶nij przycisk \"Ustaw dla wszytkich wybranych katalogów\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Uwaga: Katalog docelowy musi istnieæ zanim bedzie mozna do niego kopiowaæ pliki.";
$net2ftp_messages["Different target FTP server:"] = "Adres innego docelowego serwera FTP:";
$net2ftp_messages["Username"] = "U¿ytkownik";
$net2ftp_messages["Password"] = "Has³o";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "Pozostaw puste, je¶li chesz kopiowaæ pliki na ten sam serwer FTP.";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "Je¶li chcesz kopiowaæ pliki na inny serwer wprowad¼ jego adres, swoj± nazwê u¿ytkownika oraz has³o.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "Pozostaw puste je¶li chcesz przenosiæ plik na tym samym serwerze FTP.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "Je¶li chcesz przenosiæ pliki na inny serwer FTP wprowad¼ jego adres, swoj± nazwê u¿ytkownika oraz has³o.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "Kopiowanie katalogu <b>%1\$s</b> do:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "Przenoszenie katalogu <b>%1\$s</b> do:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "Katalog <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "Kopiowanie pliku <b>%1\$s</b> do:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "Przenoszenie pliku <b>%1\$s</b> do:";
$net2ftp_messages["File <b>%1\$s</b>"] = "Plik <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "Kopiowanie linku symbolicznego <b>%1\$s</b> do:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "Przenoszenie linku symbolicznego <b>%1\$s</b> do:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Link symboliczny <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "Katalog docelowy:";
$net2ftp_messages["Target name:"] = "Nazwa docelowa:";
$net2ftp_messages["Processing the entries:"] = "Przetwarzanie pozycji:";

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
$net2ftp_messages["Size"] = "Rozmiar";
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
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: kliknij prawym przycikiem na linku i wybierz \"Dodaj do ulubionych...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: kliknij prawym przycikiem na link i wybierz \"Dodaj stronê do zak³adek\"";

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
$net2ftp_messages["Unable to open the template file"] = "Nie mo¿na otworzyæ pliku z szablonem";
$net2ftp_messages["Unable to read the template file"] = "Nie mo¿na odczytaæ pliku z szablonem";
$net2ftp_messages["Please specify a filename"] = "Podaj nazwê pliku";
$net2ftp_messages["Status: This file has not yet been saved"] = "Status: Ten plik nie zosta³ jeszcze zapisany";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "Status: Zapisany <b>%1\$s</b> w trybie %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "Status: <b>Nie mo¿na zapisaæ tego pliku</b>";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "Katalog: ";
$net2ftp_messages["File: "] = "plik: ";
$net2ftp_messages["New file name: "] = "Nowa nazwa pliku: ";
$net2ftp_messages["Character encoding: "] = "Character encoding: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "Uwaga: zmiany w tek¶cie zostan± zapisane";
$net2ftp_messages["Copy up"] = "Copy up";
$net2ftp_messages["Copy down"] = "Copy down";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "Szukaj katalogów i plików";
$net2ftp_messages["Search again"] = "Nowe szukanie";
$net2ftp_messages["Search results"] = "Wyniki wyszukiwania";
$net2ftp_messages["Please enter a valid search word or phrase."] = "Wprowad¼ prawid³ow± frazê lub s³owo do wyszukania.";
$net2ftp_messages["Please enter a valid filename."] = "Wprowad¼ prawid³ow± nazwê plików.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "Wprowad¼ prawid³owy rozmiar pliku w polu \"od\" , np. 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "Wprowad¼ prawid³owy rozmiar pliku w polu \"do\" , np. 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "Wprowad¼ prawid³ow± datê w formacie Y-m-d w polu \"od\".";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "Wprowad¼ prawid³ow± datê w formacie Y-m-d w polu \"do\".";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "S³owo <b>%1\$s</b> nie zosta³o znalezione w zaznaczonych katalogach i plikach.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "S³owo <b>%1\$s</b> znaleziono w nastêpuj±cych plikach:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "Szukaj s³owa lub frazy";
$net2ftp_messages["Case sensitive search"] = "Wa¿na wielko¶æ liter";
$net2ftp_messages["Restrict the search to:"] = "Ogranicz szukanie do:";
$net2ftp_messages["files with a filename like"] = "nazw plików typu";
$net2ftp_messages["(wildcard character is *)"] = "(maska = *)";
$net2ftp_messages["files with a size"] = "plików o rozmiarze";
$net2ftp_messages["files which were last modified"] = "plików ostatnio modyfikowanych";
$net2ftp_messages["from"] = "od";
$net2ftp_messages["to"] = "do";

$net2ftp_messages["Directory"] = "Katalog";
$net2ftp_messages["File"] = "Plik";
$net2ftp_messages["Line"] = "Line";
$net2ftp_messages["Action"] = "Action";
$net2ftp_messages["View"] = "Podgl±d";
$net2ftp_messages["Edit"] = "Edycja";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Poka¿ kolorowany kod ¼ród³owy pliku %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Edytuj kod ¼ródowy pliku %1\$s";

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
$net2ftp_messages["Unable to open the template file"] = "Nie mo¿na otworzyæ pliku z szablonem";
$net2ftp_messages["Unable to read the template file"] = "Nie mo¿na odczytaæ pliku z szablonem";
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
$net2ftp_messages["Upload"] = "Przy¶lij";
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

$net2ftp_messages["FTP server"] = "Serwer FTP";
$net2ftp_messages["Example"] = "Przyk³ad";
$net2ftp_messages["Port"] = "Port";
$net2ftp_messages["Username"] = "U¿ytkownik";
$net2ftp_messages["Password"] = "Has³o";
$net2ftp_messages["Anonymous"] = "Anonimowo";
$net2ftp_messages["Passive mode"] = "Tryb pasywny";
$net2ftp_messages["Initial directory"] = "Katalog startowy";
$net2ftp_messages["Language"] = "Jêzyk";
$net2ftp_messages["Skin"] = "Skórka";
$net2ftp_messages["FTP mode"] = "FTP mode";
$net2ftp_messages["Automatic"] = "Automatic";
$net2ftp_messages["Login"] = "Logowanie";
$net2ftp_messages["Clear cookies"] = "Usuñ ciasteczka";
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
$net2ftp_messages["Username"] = "U¿ytkownik";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Password"] = "Has³o";
$net2ftp_messages["Login"] = "Logowanie";
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
$net2ftp_messages["Create new directories"] = "Tworzenie nowych katalogów";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "Nowe katalogi bêd± utworzone w <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "Nazwa nowego katalogu:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "Katalog <b>%1\$s</b> zosta³ utworzony.";
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
$net2ftp_messages["Rename directories and files"] = "Przemianowywanie plików i katalogów";
$net2ftp_messages["Old name: "] = "Stara nazwa: ";
$net2ftp_messages["New name: "] = "Nowa nazwa: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "Nowa nazwa nie mo¿e zawieraæ zadnych kropek. Ta pozycja nie zosta³a przemianowana na <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> zosta³a przemianowana na <b>%2\$s</b>";
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
$net2ftp_messages["Set all targetdirectories"] = "Ustaw dla wszytkich";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Aby wybraæ standardow± czynno¶æ dla katalogu, wprowad¼ nazwê katalogu w powy¿szym oknie i naci¶nij przycisk \"Ustaw dla wszytkich wybranych katalogów\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Uwaga: Katalog docelowy musi istnieæ zanim bedzie mozna do niego kopiowaæ pliki.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Unzip archive <b>%1\$s</b> to:";
$net2ftp_messages["Target directory:"] = "Katalog docelowy:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "U¿yj nazw katalogów (automatycznie tworzy podkatalogi)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "Aktualizuj plik";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>UWAGA: TA FUNKCJA JEST W STADIUM PROJEKTOWANIA. U¯YWAJ JEJ NA PLIKACH TESTOWYCH! ZOSTA£E¦ OSTRZE¯ONY!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "Znane b³êdy: - kasuje znaki tabulacji - nie dzia³a poprawnie dla du¿ych plików (> 50kB) - nie by³a testowana na plikach zawieraj±cych niestandardowe znaki</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "Ta funkcja pozwala na wys³anie nowej wesji pliku, przejrzenia jakie nast±pi³y zmiany, zaakceptowaæ lub odrzuciæ zmiany. Mo¿na edutowaæ i scaliæ pliki zanim zostan± zapisane.";
$net2ftp_messages["Old file:"] = "Stary plik:";
$net2ftp_messages["New file:"] = "Nowy plik:";
$net2ftp_messages["Restrictions:"] = "Ograniczenia:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Maksymalny rozmiar pliku ustawiony w  net2ftp to <b>%1\$s kB</b> i w PHP to <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Maksymalny czas wykonywania skryptu <b>%1\$s sekund</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "Tryb transferu FTP (ASCII or BINARY) bêdzie ustawiony automatycznie na podstawie rozszerzenia";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Je¶li plik docelowy istnieje, zostanie nadpisany";
$net2ftp_messages["You did not provide any files or archives to upload."] = "Nie wprowadzono ¿adnych plików/archiwów do wys³ania.";
$net2ftp_messages["Unable to delete the new file"] = "Nie mozna skasowaæ nowego pliku";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "Proszê czekaæ...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "Zaznacz poni¿ej linie, akceptuj lub odrzuæ zmiany i wy¶lij formularz.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "Wy¶lij do katalogu:";
$net2ftp_messages["Files"] = "Pliki";
$net2ftp_messages["Archives"] = "Archiwa";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "Wpisane tu pliki bêd± wys³ane na serwer FTP.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "Wpisane tu archiwa bêd± wys³±ne na serwer FTP.";
$net2ftp_messages["Add another"] = "Dodaj nastêpne";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "U¿yj nazw katalogów (automatycznie tworzy podkatalogi)";

$net2ftp_messages["Choose a directory"] = "Wybierz katalog";
$net2ftp_messages["Please wait..."] = "Proszê czekaæ...";
$net2ftp_messages["Uploading... please wait..."] = "Wysy³anie... proszê czekaæ...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "To okno zamknie siê automatycznie w ci±gu kilku sekund.";
$net2ftp_messages["Close window now"] = "Zamknij okno";

$net2ftp_messages["Upload files and archives"] = "Wysy³anie plików i archiwów";
$net2ftp_messages["Upload results"] = "Wyniki wysy³ania";
$net2ftp_messages["Checking files:"] = "Sprawdzanie plików:";
$net2ftp_messages["Transferring files to the FTP server:"] = "Transfer plików na serwer FTP:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "Dekompresja plików i transfer na serwer FTP:";
$net2ftp_messages["Upload more files and archives"] = "Wy¶lij wiêcej plików i archiwów";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "Ograniczenia:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Maksymalny rozmiar pliku ustawiony w  net2ftp to <b>%1\$s kB</b> i w PHP to <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Maksymalny czas wykonywania skryptu <b>%1\$s sekund</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "Tryb transferu FTP (ASCII or BINARY) bêdzie ustawiony automatycznie na podstawie rozszerzenia";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Je¶li plik docelowy istnieje, zostanie nadpisany";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "View file %1\$s";
$net2ftp_messages["View image %1\$s"] = "Poka¿ obraz %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "View Macromedia ShockWave Flash movie %1\$s";
$net2ftp_messages["Image"] = "Image";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "Aby zapisaæ obraz, kliknij na obrazie przwym przyciskiem i wybierz 'Zapisz obraz jako...'";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "Zipowanie wybranych pozycji";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "Zapisz plik zip na serwerze FTP jako:";
$net2ftp_messages["Email the zip file in attachment to:"] = "Wy¶lij plik zip poczt± do:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "Wysy³anie plików nie jest anonimowe: Twój adres IP i czas wys³ania bêdzie dodany do emaila.";
$net2ftp_messages["Some additional comments to add in the email:"] = "Dodatkowe komentarze do emaila:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "Nie podano nazwy pliku zip. Cofnij i wprowad¼ nazwê pliku.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "Adres, który wprowadzono (%1\$s) nie jest prawidowym adresem email.<br />Wprowad¼ adres w formacie <b>username@domain.com</b>";

} // end zip

?>