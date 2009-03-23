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
$net2ftp_messages["en"] = "nl";

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

$net2ftp_messages["Connecting to the FTP server"] = "Verbinding maken met de FTP server";
$net2ftp_messages["Logging into the FTP server"] = "Bezig met aanmelden op de FTP server";
$net2ftp_messages["Setting the passive mode"] = "Activering passieve modus";
$net2ftp_messages["Getting the FTP system type"] = "FTP systeem ophalen";
$net2ftp_messages["Changing the directory"] = "Bezig met veranderen van map";
$net2ftp_messages["Getting the current directory"] = "Bezig met ophalen van de map";
$net2ftp_messages["Getting the list of directories and files"] = "Lijst van mappen en bestanden wordt opgevraagd";
$net2ftp_messages["Parsing the list of directories and files"] = "Lijst van mappen en bestanden wordt geanalyseerd";
$net2ftp_messages["Logging out of the FTP server"] = "Bezig met afmelden van de FTP server";
$net2ftp_messages["Getting the list of directories and files"] = "Lijst van mappen en bestanden wordt opgevraagd";
$net2ftp_messages["Printing the list of directories and files"] = "Lijst van mappen en bestanden wordt afgebeeld";
$net2ftp_messages["Processing the entries"] = "Reeks wordt verwerkt";
$net2ftp_messages["Processing entry %1\$s"] = "Bezig met verwerken van entry %1\$s";
$net2ftp_messages["Checking files"] = "Bestanden worden gecontroleerd";
$net2ftp_messages["Transferring files to the FTP server"] = "Bestanden worden naar de FTP server verzonden";
$net2ftp_messages["Decompressing archives and transferring files"] = "Archieven worden uitgepakt en verzonden";
$net2ftp_messages["Searching the files..."] = "Bestanden worden doorzocht...";
$net2ftp_messages["Uploading new file"] = "Nieuw bestand wordt verzonden";
$net2ftp_messages["Reading the file"] = "Bezig met lezen van bestand";
$net2ftp_messages["Parsing the file"] = "Bezig met analyzeren van bestand";
$net2ftp_messages["Reading the new file"] = "Nieuw bestand wordt gelezen";
$net2ftp_messages["Reading the old file"] = "Oud bestand wordt gelezen";
$net2ftp_messages["Comparing the 2 files"] = "Bestanden worden vergeleken";
$net2ftp_messages["Printing the comparison"] = "Vergelijking wordt afgebeeld";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "Bezig met versturen FTP commando %1\$s van %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Bezig met ophalen archief %1\$s van %2\$s van de FTP server";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Bezig met het aanmaken van een tijdelijke map op de FTP server";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Bezig met wijzigen van de rechten van de tijdelijke map";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Bezig met kopieren van het net2ftp installatie script naar de FTP server";
$net2ftp_messages["Script finished in %1\$s seconds"] = "Script beëindigd in %1\$s seconden";
$net2ftp_messages["Script halted"] = "Script werd onderbroken";

// Used on various screens
$net2ftp_messages["Please wait..."] = "Even wachten...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Onverwachts resultaat: %1\$s. Beeindigen.";
$net2ftp_messages["This beta function is not activated on this server."] = "Deze test functie is niet geactiveerd op deze server.";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "Deze functie is gedeactiveerd door de Administrator van deze website.";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "De map <b>%1\$s</b> bestaat niet of kon niet worden geselecteerd, hierom is de map <b>%2\$s</b> weergegeven.";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "Uw hoofd map <b>%1\$s</b> bestaat niet of kon niet worden geselecteerd.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "De map <b>%1\$s</b> kon niet worden geselecteerd - wellicht heeft u onvoldoende rechten om deze in te zien, of het bestaat niet.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Regels welke verboden termen bevatten kunnen niet worden beheerd via net2ftp. Dit is om te voorkomen dat PayPal of Ebay scams geupload worden met net2ftp.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Bestanden die te groot zijn kunnen niet worden gedownload, geupload, gekopieerd, verplaatst, ingepakt, uitgepakt of bewerkt worden. Ze kunnen alleen hernoemd, gewijzigd qua rechten of verwijderd worden.";
$net2ftp_messages["Execute %1\$s in a new window"] = "Voer %1\$s uit in een nieuw venster";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "Kies minimaal één map of bestand!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "De FTP server <b>%1\$s</b> staat niet in de lijst met toegestane FTP servers.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "De FTP server <b>%1\$s</b> staat in de lijst met verboden FTP servers.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "De FTP server poort %1\$s mag niet worden gebruikt.";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "Uw IP adres (%1\$s) staat niet in de lijst met toegestane IP adressen";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "Uw IP adres (%1\$s) staat in de lijst met verboden IP adressen.";

// isAuthorizedDirectory()
$net2ftp_messages["Table net2ftp_users contains duplicate rows."] = "De tabel net2ftp_users bevat dubbele rijen.";

// logAccess(), logLogin(), logError()
$net2ftp_messages["Unable to execute the SQL query."] = "Kan SQL query niet uitvoeren.";
$net2ftp_messages["Unable to open the system log."] = "Kan het systeemlogboekbestand niet openen.";
$net2ftp_messages["Unable to write a message to the system log."] = "Kan geen regel wegschrijven in het systeemlogboekbestand.";

// checkAdminUsernamePassword()
$net2ftp_messages["You did not enter your Administrator username or password."] = "U heeft geen beheerders gebruikersnaam of wachtwoord opgegeven.";
$net2ftp_messages["Wrong username or password. Please try again."] = "Verkeerde gebruikersnaam of wachtwoord ingevoerd, probeer het nogmaals.";


// -------------------------------------------------------------------------
// /includes/consumption.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to determine your IP address."] = "Het achterhalen van uw IP adres is mislukt.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "Tabel net2ftp_log_consumption_ipaddress bevat gelijke rijen.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "Tabel net2ftp_log_consumption_ftpserver bevat gelijke rijen.";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "De variabele <b>consumption_ipaddress_datatransfer</b> is geen cijfer.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "Tabel net2ftp_log_consumption_ipaddress kan niet worden vernieuwd.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "Tabel net2ftp_log_consumption_ipaddress bevat gelijke gegevens.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "Tabel net2ftp_log_consumption_ftpserver kan niet worden vernieuwd.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "Tabel net2ftp_log_consumption_ftpserver bevat gelijke gegevens.";
$net2ftp_messages["Table net2ftp_log_access could not be updated."] = "Tabel net2ftp_log_access kon niet worden geupdate.";
$net2ftp_messages["Table net2ftp_log_access contains duplicate entries."] = "Tabll net2ftp_log_access bevat dubbele rijen.";


// -------------------------------------------------------------------------
// /includes/database.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "Kan geen verbinding maken met de MySQL database. Controleer uw MySQL database instellingen in net2ftp's configuratie bestand settings.inc.php.";
$net2ftp_messages["Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "Kan de MySQL database niet selecteren. Controleer uw MySQL database instellingen in net2ftp's configuratie bestand settings.inc.php.";


// -------------------------------------------------------------------------
// /includes/errorhandling.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["An error has occured"] = "Er heeft zich een fout voorgedaan";
$net2ftp_messages["Go back"] = "Ga terug";
$net2ftp_messages["Go to the login page"] = "Ga naar de login pagina";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "De <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module van PHP</a> is niet geïnstalleerd.<br /><br /> De systeembeheerder van deze site zou de FTP module moeten installeren. Installatie instructies zijn gegeven op <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Verbinding maken met de FTP server <b>%1\$s</b> op poort <b>%2\$s</b> is mislukt.<br /><br />Weet u zeker dat dit het correcte adres is van de FTP server? Deze is vaak verschillend van de HTTP (web) server. Neem a.u.b. contact op met uw ISP helpdesk of systeembeheerder voor hulp.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Inloggen op FTP server <b>%1\$s</b> met gebruikersnaam <b>%2\$s</b> is mislukt.<br /><br />Weet u zeker dat uw gebruikersnaam en wachtwoord correct zijn? Neem a.u.b. contact op met uw ISP helpdesk of systeembeheerder voor hulp.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "Het overschakelen naar passieve mode op de FTP server <b>%1\$s</b> is mislukt.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Verbinding met de tweede (doel) FTP server <b>%1\$s</b> op poort <b>%2\$s</b> is mislukt.<br /><br />Weet u zeker dat dit het correcte adres is van de FTP server? Deze is vaak verschillend van de HTTP (web) server. Neem a.u.b. contact op met uw ISP helpdesk of systeembeheerder voor hulp.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Inloggen op tweede (doel) FTP server <b>%1\$s</b> met gebruikersnaam <b>%2\$s</b> is mislukt.<br /><br />Weet u zeker dat uw gebruikersnaam en wachtwoord correct zijn? Neem a.u.b. contact op met uw ISP helpdesk of systeembeheerder voor hulp.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "Het overschakelen naar passieve mode op de tweede (doel) FTP server <b>%1\$s</b> is mislukt.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "Het hernoemen van de map of bestand <b>%1\$s</b> in <b>%2\$s</b> is mislukt";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "Het uitvoeren van het commando <b>%1\$s</b> is mislukt. Let op dat het CHMOD commando alleen beschikbaar is op Unix FTP servers, niet op Windows FTP servers.";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "Map <b>%1\$s</b> is successvol ge-chmod naar <b>%2\$s</b>";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "Bezig met verwerken van regels in map <b>%1\$s</b>:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "Bestand <b>%1\$s</b> is successvol ge-chmod naar <b>%2\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "Alle geselecteerde mappen en bestanden zijn verwerkt.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "Het verwijderen van de map <b>%1\$s</b> is mislukt.";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Het verwijderen van het bestand <b>%1\$s</b> is mislukt";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "Het creëren van de map <b>%1\$s</b> is mislukt";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "Het creëren van het tijdelijke bestand is mislukt";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "Het openen van bestand <b>%1\$s</b> van de FTP server, en op te slaan als tijdelijk bestand <b>%2\$s</b> is mislukt.<br />Controleer de rechten van de map %3\$s.<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Het openen van het tijdelijke bestand is mislukt. Controleer de rechten van de map %1\$s.";
$net2ftp_messages["Unable to read the temporary file"] = "Het lezen van het tijdelijke bestand is mislukt";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Het sluiten van het tijdelijke bestand is mislukt";
$net2ftp_messages["Unable to delete the temporary file"] = "Het verwijderen van het tijdelijke bestand is mislukt";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "Het creëren van het tijdelijke bestand is mislukt. Controleer de rechten van de map %1\$s.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Het openen van het tijdelijke bestand is mislukt. Controleer de rechten van de map %1\$s.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "Het schrijven van de regel naar het bestand <b>%1\$s</b> is mislukt.<br />Controleer de rechten van de map %2\$s.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Het sluiten van het tijdelijke bestand is mislukt";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "Het opslaan van de bestand <b>%1\$s</b> op de FTP server is mislukt.<br />U heeft mogelijk geen schrijf rechten in deze map.";
$net2ftp_messages["Unable to delete the temporary file"] = "Het verwijderen van het tijdelijke bestand is mislukt";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "Map <b>%1\$s</b> wordt verwerkt";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "De doel map <b>%1\$s</b> is de zelfde als, of is een onderliggende map van de bron map <b>%2\$s</b>, daarom wordt deze map overgeslagen";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "De map <b>%1\$s</b> bevat een verboden term, de map zal worden overgeslagen";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "De map <b>%1\$s</b> bevat een verboden term, de verplaatsing wordt afgebroken";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "Het aanmaken van de map <b>%1\$s</b> is mislukt. Deze bestaat mogelijk al. De kopiëren/verplaatsen procedure wordt voortgezet...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Doelmap <b>%1\$s</b> is aangemaakt";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "De map <b>%1\$s</b> kon niet worden geselecteerd, de map wordt overgeslagen";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "Het verwijderen van de onderliggende map <b>%1\$s</b> is mislukt - deze is mogelijk niet leeg";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "Onderliggende map <b>%1\$s</b> verwijderd";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "Verwerken van map <b>%1\$s</b> voltooid";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "Het doel van het bestand <b>%1\$s</b> is het zelfde als de bron, daarom wordt dit bestand overgeslagen";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "Het bestand <b>%1\$s</b> bevat een verboden term en zal worden overgeslagen";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "Het bestand <b>%1\$s</b> bevat een verboden term en zal niet verplaatst worden";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "Het bestand <b>%1\$s</b> is te groot om gekopieerd te worden en wordt overgeslagen";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "Het bestand <b>%1\$s</b> is te groot om verplaatst te worden en wordt overgeslagen";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "Het kopiëren van het bestand <b>%1\$s</b> is mislukt";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Bestand <b>%1\$s</b> gekopieerd";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Kan het bestand <b>%1\$s</b> niet verplaatsen";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "Bestand <b>%1\$s</b> verplaatst";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Het verwijderen van het bestand <b>%1\$s</b> is mislukt";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "Bestand <b>%1\$s</b> verwijderd";
$net2ftp_messages["All the selected directories and files have been processed."] = "Alle geselecteerde mappen en bestanden zijn verwerkt.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "Het kopiëren van het externe bestand <b>%1\$s</b> naar het locale bestand via FTP mode <b>%2\$s</b> is mislukt";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "Het verwijderen van het bestand <b>%1\$s</b> is mislukt";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "Het bestand is te groot om verstuurd te worden.";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "Dagelijkse limiet bereikt: het bestand <b>%1\$s</b> wordt niet verstuurd";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "Het kopiëren van het locale bestand naar het externe bestand <b>%1\$s</b> via FTP mode <b>%2\$s</b> is mislukt";
$net2ftp_messages["Unable to delete the local file"] = "Het verwijderen van het locale bestand is mislukt";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "Het verwijderen van het tijdelijke bestand is mislukt";
$net2ftp_messages["Unable to send the file to the browser"] = "Het verzenden van het bestand naar de browser is mislukt";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "Het creëren van het tijdelijke bestand is mislukt";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "Het zip bestand is opgeslagen op de FTP server als <b>%1\$s</b>";
$net2ftp_messages["Requested files"] = "Aangevraagde bestanden";

$net2ftp_messages["Dear,"] = "Geachte,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "Iemand heeft gevraagd om het bestand dat als bijlage is toegevoegd dit email adres (%1\$s) te versturen.";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "Als u hier niks van af weet, of als u de afzender niet vertrouwt, verwijder dan a.u.b. deze email zonder de bijlage te openen.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "Als u de bijlage niet opent, kan uw computer niet worden beschadigd.";
$net2ftp_messages["Information about the sender: "] = "Informatie over de afzender: ";
$net2ftp_messages["IP address: "] = "IP adres: ";
$net2ftp_messages["Time of sending: "] = "Tijdstip van verzending: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "Verstuurd met het net2ftp programma vanaf de website: ";
$net2ftp_messages["Webmaster's email: "] = "Email van de webmaster: ";
$net2ftp_messages["Message of the sender: "] = "Bericht van de afzender: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp is vrije software, uitgebracht onder de GNU/GPL licentie. Ga voor meer informatie naar http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "Het zip bestand is verzonden naar <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "Het bestand <b>%1\$s</b> is te groot. Dit bestand zal niet worden geupload.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "Het bestand <b>%1\$s</b> bevat een verboden term. Dit bestand zal niet worden geupload.";
$net2ftp_messages["Could not generate a temporary file."] = "Het creëren van het tijdelijke bestand is mislukt.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "Het bestand <b>%1\$s</b> kon niet worden verplaatst";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "Het bestand <b>%1\$s</b> is OK";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "Het verplaatsen van het geuploade bestand naar de tijdelijke map is mislukt.<br /><br />De systeembeheerder van deze website moet de /temp map van net2ftp <b>chmod-den naar 777</b>.";
$net2ftp_messages["You did not provide any file to upload."] = "U heeft geen bestand opgegeven dat moet worden geupload.";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "Het bestand <b>%1\$s</b> kon niet worden verzonden naar de FTP server";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "Het bestand <b>%1\$s</b> is verzonden naar de FTP server via FTP mode <b>%2\$s</b>";
$net2ftp_messages["Transferring files to the FTP server"] = "Bestanden worden naar de FTP server verzonden";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "Het archief nummer %1\$s: <b>%2\$s</b> wordt verwerkt";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "Archief <b>%1\$s</b> is niet verwerkt omdat de extensie niet is herkent. Op dit moment worden alleen zip, tar, tgz and gz archieven ondersteund.";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Het is niet mogeljk bestanden en mappen uit het archief te halen.";
$net2ftp_messages["Archive contains filenames with ../ or ..\\ - aborting the extraction"] = "Het archief bevat bestandsnamen met ../ of ..\\ - niet toegestaan";
$net2ftp_messages["Created directory %1\$s"] = "Map %1\$s aangemaakt";
$net2ftp_messages["Could not create directory %1\$s"] = "Kan de map %1\$s niet aanmaken";
$net2ftp_messages["Copied file %1\$s"] = "Bestand %1\$s verstuurd";
$net2ftp_messages["Could not copy file %1\$s"] = "Kon bestand %1\$s niet versturen";
$net2ftp_messages["Unable to delete the temporary directory"] = "De tijdelijke map kon niet verwijderd worden";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Het tijdelijke bestand %1\$s kon niet verwijderd worden";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "Het uitvoeren van het commando <b>%1\$s</b> is mislukt";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "Uw opdracht is gestopt";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "De opdracht die u probeerde uit te voeren met net2ftp duurde langer dan de toegestane %1\$s seconden, en is daarom gestopt.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "Deze tijd limiet garandeert een eerlijk gebruik van de web server voor iedereen.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "Probeer uw opdracht op te splitsen in lichtere opdrachten: kies een kleiner aantal bestanden, en laat de grotere bestanden weg.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "Indien het voor u echt nodig is dat net2ftp zware opdrachten kan uitvoeren die veel tijd nemen, overweeg dan om net2ftp op uw eigen server te installeren.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "U heeft geen tekst opgegeven om te worden verzonden met de email!";
$net2ftp_messages["You did not supply a From address."] = "U heeft geen afzender adres opgegeven.";
$net2ftp_messages["You did not supply a To address."] = "U heeft geen ontvangst adres opgegeven.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "In verband met technische problemen kon de email naar <b>%1\$s</b> niet worden verzonden.";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "Geef uw gebruikersnaam en wachtwoord op voor FTP server ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "U heeft geen inlog gegevens opgegeven.<br />Klik hieronder op \"Ga naar de login pagina\" om in te loggen.";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "De toegang tot de net2ftp Admin panel is uitgeschakeld, omdat er geen wachtwoord is opgegeven in het bestand settings.inc.php. Voeg een wachtwoord toe in dat bestand, en vernieuw deze pagina.";
$net2ftp_messages["Please enter your Admin username and password"] = "Geef a.u.b. uw Admin gebruikersnaam en wachtwoord op";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "U heeft geen inlog gegevens opgegeven.<br />Klik hieronder op \"Ga naar de login pagina\" om in te loggen.";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "De gebruikersnaam en wachtwoord voor de Admin panel is ongeldig. De gebruikersnaam en wachtwoord kan worden ingesteld in het bestand settings.inc.php.";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "Blauw";
$net2ftp_messages["Grey"] = "Grijs";
$net2ftp_messages["Black"] = "Zwart";
$net2ftp_messages["Yellow"] = "Geel";
$net2ftp_messages["Pastel"] = "Pastel";

// getMime()
$net2ftp_messages["Directory"] = "Map";
$net2ftp_messages["Symlink"] = "Symlink";
$net2ftp_messages["ASP script"] = "ASP script";
$net2ftp_messages["Cascading Style Sheet"] = "Cascading Style Sheet";
$net2ftp_messages["HTML file"] = "HTML bestand";
$net2ftp_messages["Java source file"] = "Java source bestand";
$net2ftp_messages["JavaScript file"] = "JavaScript bestand";
$net2ftp_messages["PHP Source"] = "PHP Source";
$net2ftp_messages["PHP script"] = "PHP script";
$net2ftp_messages["Text file"] = "Tekst bestand";
$net2ftp_messages["Bitmap file"] = "Bitmap bestand";
$net2ftp_messages["GIF file"] = "GIF bestand";
$net2ftp_messages["JPEG file"] = "JPEG bestand";
$net2ftp_messages["PNG file"] = "PNG bestand";
$net2ftp_messages["TIF file"] = "TIF bestand";
$net2ftp_messages["GIMP file"] = "GIMP bestand";
$net2ftp_messages["Executable"] = "Uitvoerbaar bestand";
$net2ftp_messages["Shell script"] = "Shell script";
$net2ftp_messages["MS Office - Word document"] = "MS Office - Word document";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - Excel spreadsheet";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - PowerPoint presentatie";
$net2ftp_messages["MS Office - Access database"] = "MS Office - Access database";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - Visio afbeelding";
$net2ftp_messages["MS Office - Project file"] = "MS Office - Project bestand";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - Writer 6.0 document";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - Writer 6.0 template";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - Calc 6.0 spreadsheet";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - Calc 6.0 template";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - Draw 6.0 document";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - Draw 6.0 template";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - Impress 6.0 presentatie";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - Impress 6.0 template";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - Writer 6.0 global document";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - Math 6.0 document";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - StarWriter 5.x document";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - StarWriter 5.x global document";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - StarCalc 5.x spreadsheet";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - StarDraw 5.x document";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - StarImpress 5.x presentatie";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - StarImpress Packed 5.x bestand";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - StarMath 5.x document";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - StarChart 5.x document";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - StarMail 5.x mail bestand";
$net2ftp_messages["Adobe Acrobat document"] = "Adobe Acrobat document";
$net2ftp_messages["ARC archive"] = "ARC archief";
$net2ftp_messages["ARJ archive"] = "ARJ archief";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "GZ archief";
$net2ftp_messages["TAR archive"] = "TAR archief";
$net2ftp_messages["Zip archive"] = "Zip archief";
$net2ftp_messages["MOV movie file"] = "MOV film bestand";
$net2ftp_messages["MPEG movie file"] = "MPEG film bestand";
$net2ftp_messages["Real movie file"] = "Real film bestand";
$net2ftp_messages["Quicktime movie file"] = "Quicktime film bestand";
$net2ftp_messages["Shockwave flash file"] = "Shockwave flash bestand";
$net2ftp_messages["Shockwave file"] = "Shockwave bestand";
$net2ftp_messages["WAV sound file"] = "WAV geluids bestand";
$net2ftp_messages["Font file"] = "Font bestand";
$net2ftp_messages["%1\$s File"] = "%1\$s Bestand";
$net2ftp_messages["File"] = "Bestand";

// getAction()
$net2ftp_messages["Back"] = "Terug";
$net2ftp_messages["Submit"] = "Verzenden";
$net2ftp_messages["Refresh"] = "Vernieuwen";
$net2ftp_messages["Details"] = "Details";
$net2ftp_messages["Icons"] = "Iconen";
$net2ftp_messages["List"] = "Lijst";
$net2ftp_messages["Logout"] = "Uitloggen";
$net2ftp_messages["Help"] = "Help";
$net2ftp_messages["Bookmark"] = "Favoriet";
$net2ftp_messages["Save"] = "Opslaan";
$net2ftp_messages["Default"] = "Standaard";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Help Guide";
$net2ftp_messages["Forums"] = "Forums";
$net2ftp_messages["License"] = "Licentie";
$net2ftp_messages["Powered by"] = "Aangedreven door";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "U wordt nu doorgestuurd naar de forums van net2ftp. Deze forums zijn alleen voor net2ftp gerelateerde onderwerpen. Niet bedoeld voor algemene webhosting vragen.";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "Admin functies";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "Versie informatie";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "Deze versie van net2ftp is up-to-date.";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "De laatste versie informatie kon niet worden opgehaald van de net2ftp server. Controleer de beveiligingsinstellingen van uw browser. Deze kunnen voorkomen dat een klein bestand van de net2ftp server geladen wordt.";
$net2ftp_messages["Logging"] = "Logging";
$net2ftp_messages["Date from:"] = "Datum van:";
$net2ftp_messages["to:"] = "tot:";
$net2ftp_messages["Empty logs"] = "Leeg logs";
$net2ftp_messages["View logs"] = "Bekijk logs";
$net2ftp_messages["Go"] = "Ga";
$net2ftp_messages["Setup MySQL tables"] = "Stel de MySQL tabellen in";
$net2ftp_messages["Create the MySQL database tables"] = "Maak de MySQL tabellen aan";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "Admin functies";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "De handle van bestand %1\$s kon niet worden geopend.";
$net2ftp_messages["The file %1\$s could not be opened."] = "Het bestand %1\$s kon niet worden geopend.";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "De handle van bestand %1\$s kon niet worden gesloten.";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "De verbinding naar de server <b>%1\$s</b> kon niet opgezet worden. Controleer uw database instellingen.";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "De database <b>%1\$s</b> kon niet worden geselecteerd.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "De SQL query nr <b>%1\$s</b> kon niet worden uitgevoerd.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "De SQL query nr <b>%1\$s</b> is succesvol uitgevoerd.";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "Vul aub uw MySQL instellingen in:";
$net2ftp_messages["MySQL username"] = "MySQL gebruikersnaam";
$net2ftp_messages["MySQL password"] = "MySQL wachtwoord";
$net2ftp_messages["MySQL database"] = "MySQL database";
$net2ftp_messages["MySQL server"] = "MySQL server";
$net2ftp_messages["This SQL query is going to be executed:"] = "Deze SQL query zal worden uitgevoerd:";
$net2ftp_messages["Execute"] = "Uitvoeren";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "Gebruikte instellingen:";
$net2ftp_messages["MySQL password length"] = "MySQL wachtwoord lengte";
$net2ftp_messages["Results:"] = "Resultaten:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "Admin functies";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "De SQL query <b>%1\$s</b> kon niet worden uitgevoerd.";
$net2ftp_messages["No data"] = "Geen data";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "Admin functies";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "De tabel <b>%1\$s</b> werd succesvol geleegd.";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "De tabel <b>%1\$s</b> kon niet worden geleegd.";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "De tabel <b>%1\$s</b> is succesvol geoptimaliseerd.";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "De tabel <b>%1\$s</b> kon niet worden geoptimaliseerd.";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "Geavanceerde functies";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "Ga";
$net2ftp_messages["Disabled"] = "Uitgeschakeld";
$net2ftp_messages["Advanced FTP functions"] = "Geavanceerde FTP functies";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "Verstuur ruwe FTP commandos naar de FTP server";
$net2ftp_messages["This function is available on PHP 5 only"] = "Deze functie is alleen beschikbaar onder PHP 5";
$net2ftp_messages["Troubleshooting functions"] = "Probleemoplossing functies";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "Test de installatie van net2ftp op deze webserver";
$net2ftp_messages["Troubleshoot an FTP server"] = "Test net2ftp op een FTP server";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Test de net2ftp regel interpretatie regels";
$net2ftp_messages["Translation functions"] = "Vertaal functies";
$net2ftp_messages["Introduction to the translation functions"] = "Inleiding tot de vertaal functies";
$net2ftp_messages["Extract messages to translate from code files"] = "Verkrijg de te vertalen berichten uit de code bestanden";
$net2ftp_messages["Check if there are new or obsolete messages"] = "Kijk na of er nieuwe of verouderde berichten zijn";

$net2ftp_messages["Beta functions"] = "Beta functies";
$net2ftp_messages["Send a site command to the FTP server"] = "Verstuur een site commando naar de FTP server";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: bescherm een map met een wachtwoord, maak speciale foutpagina's aan";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: voer een SQL query uit";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "De site commando functies zijn niet beschikbaar op deze webserver.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "De Apache functies zijn niet beschikbaar op deze webserver.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "De MySQL functies zijn niet beschikbaar op deze webserver.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "Onverwachte state2 string. De applicatie wordt onderbroken.";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "Test net2ftp op een FTP server";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "Verbindings instellingen:";
$net2ftp_messages["FTP server"] = "FTP server";
$net2ftp_messages["FTP server port"] = "FTP server poort";
$net2ftp_messages["Username"] = "Gebruikersnaam";
$net2ftp_messages["Password"] = "Wachtwoord";
$net2ftp_messages["Password length"] = "Wachtwoord lengte";
$net2ftp_messages["Passive mode"] = "Passieve mode";
$net2ftp_messages["Directory"] = "Map";
$net2ftp_messages["Printing the result"] = "Printing the result";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "Verbinding met de FTP server wordt aangemaakt: ";
$net2ftp_messages["Logging into the FTP server: "] = "Aan het inloggen: ";
$net2ftp_messages["Setting the passive mode: "] = "De passieve mode wordt gekozen: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Het FTP server system type wordt opgevraagd: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Huidige map wordt veranderd naar %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "De map van de FTP server is: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Ruwe lijst van mappen en bestanden wordt aangevraagd: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Er wordt een tweede keer geprobeerd om een ruwe lijst van mappen en bestanden te krijgen: ";
$net2ftp_messages["Closing the connection: "] = "Verbinding wordt gesloten: ";
$net2ftp_messages["Raw list of directories and files:"] = "Ruwe lijst van mappen en bestanden:";
$net2ftp_messages["Parsed list of directories and files:"] = "Verkregen lijst van mappen en bestanden:";

$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "niet OK";

} // end advanced_ftpserver


// -------------------------------------------------------------------------
// Advanced_parsing module
if ($net2ftp_globals["state"] == "advanced_parsing") {
// -------------------------------------------------------------------------

$net2ftp_messages["Test the net2ftp list parsing rules"] = "Test de net2ftp regel interpretatie regels";
$net2ftp_messages["Sample input"] = "Voorbeeld invoer";
$net2ftp_messages["Parsed output"] = "Verwerkte uitvoer";

} // end advanced_parsing


// -------------------------------------------------------------------------
// Advanced_webserver module
if ($net2ftp_globals["state"] == "advanced_webserver") {
// -------------------------------------------------------------------------

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "Test de installatie van net2ftp op deze webserver";
$net2ftp_messages["Printing the result"] = "Printing the result";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "Er wordt gecontroleerd of de FTP module van PHP is geïnstalleerd: ";
$net2ftp_messages["yes"] = "ja";
$net2ftp_messages["no - please install it!"] = "nee - installeer de module!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "De rechten van de map op de webserver zullen worden nagekeken: een klein bestand zal worden weggeschreven naar de /temp directory en zal daarna worden verwijderd.";
$net2ftp_messages["Creating filename: "] = "Bestand wordt aangemaakt met naam: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "OK. Naam van het bestand: %1\$s";
$net2ftp_messages["not OK"] = "niet OK";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "niet OK. Controleer de rechten van de map %1\$s";
$net2ftp_messages["Opening the file in write mode: "] = "Bestand wordt in schrijf-mode geopend: ";
$net2ftp_messages["Writing some text to the file: "] = "Er wordt wat tekst weggeschreven naar het bestand: ";
$net2ftp_messages["Closing the file: "] = "Bestand wordt gesloten: ";
$net2ftp_messages["Deleting the file: "] = "Bestand wordt verwijderd: ";

$net2ftp_messages["Testing the FTP functions"] = "Bezig met het testen van de FTP functies";
$net2ftp_messages["Connecting to a test FTP server: "] = "Bezig met aanmelden op een test FTP server: ";
$net2ftp_messages["Connecting to the FTP server: "] = "Verbinding met de FTP server wordt aangemaakt: ";
$net2ftp_messages["Logging into the FTP server: "] = "Aan het inloggen: ";
$net2ftp_messages["Setting the passive mode: "] = "De passieve mode wordt gekozen: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Het FTP server system type wordt opgevraagd: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Huidige map wordt veranderd naar %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "De map van de FTP server is: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Ruwe lijst van mappen en bestanden wordt aangevraagd: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Er wordt een tweede keer geprobeerd om een ruwe lijst van mappen en bestanden te krijgen: ";
$net2ftp_messages["Closing the connection: "] = "Verbinding wordt gesloten: ";
$net2ftp_messages["Raw list of directories and files:"] = "Ruwe lijst van mappen en bestanden:";
$net2ftp_messages["Parsed list of directories and files:"] = "Verkregen lijst van mappen en bestanden:";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "niet OK";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "Voeg deze link toe aan uw favorieten:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: rechter muisklik op de link, en kies \"Toevoegen aan Favorieten...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: rechter muisklik op de link, en kies \"Bladwijzer van deze pagina maken...\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "Opmerking: als u deze link gebruikt, vraagt een extra venster naar uw gebruikersnaam en wachtwoord.";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "Kies een map";
$net2ftp_messages["Please wait..."] = "Even wachten...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "Mappen die het teken \' in hun naam hebben kunnen niet correct worden afgebeeld. Ze kunnen alleen worden verwijderd. Kiest u a.u.b. een andere map.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "De dagelijkse limiet is bereikt: hierdoor kunt u geen gegevens meer versturen";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "Om iedereen een eerlijk gebruik van deze webserver te garanderen, zijn de hoeveelheid data en script verwerkingstijd dagelijks beperkt. Zodra deze limiet is bereikt, kunt u nog wel door de FTP server bladeren, maar er geen bestanden vanaf/naartoe sturen.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "Als u onbeperkt gebruik wil, overweeg dan om net2ftp op uw eigen server te installeren.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "Nieuwe map";
$net2ftp_messages["New file"] = "Nieuw bestand";
$net2ftp_messages["HTML templates"] = "HTML templates";
$net2ftp_messages["Upload"] = "Upload";
$net2ftp_messages["Java Upload"] = "Java Upload";
$net2ftp_messages["Flash Upload"] = "Flash Upload";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Advanced"] = "Geavanceerd";
$net2ftp_messages["Copy"] = "Kopiëren";
$net2ftp_messages["Move"] = "Verplaatsen";
$net2ftp_messages["Delete"] = "Verwijderen";
$net2ftp_messages["Rename"] = "Hernoemen";
$net2ftp_messages["Chmod"] = "Chmod";
$net2ftp_messages["Download"] = "Download";
$net2ftp_messages["Unzip"] = "Unzip";
$net2ftp_messages["Zip"] = "Inpakken";
$net2ftp_messages["Size"] = "Grootte";
$net2ftp_messages["Search"] = "Zoeken";
$net2ftp_messages["Go to the parent directory"] = "Ga naar de bovenliggende map";
$net2ftp_messages["Go"] = "Ga";
$net2ftp_messages["Transform selected entries: "] = "Verwerk de geselecteerde reeks: ";
$net2ftp_messages["Transform selected entry: "] = "Verwerk het geselecteerde document: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "Maak een nieuwe onderliggende map aan in de map %1\$s";
$net2ftp_messages["Create a new file in directory %1\$s"] = "Maak een nieuw bestand aan in de map %1\$s";
$net2ftp_messages["Create a website easily using ready-made templates"] = "Creeer gemakkelijk een website met behulp van kant en klare sjablonen";
$net2ftp_messages["Upload new files in directory %1\$s"] = "Upload een nieuw bestand in de map %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Upload mappen en bestanden met een Java applet";
$net2ftp_messages["Upload files using a Flash applet"] = "Upload files using a Flash applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Installeer software pakketten (vereist PHP op de web server)";
$net2ftp_messages["Go to the advanced functions"] = "Ga naar de geavanceerde functies";
$net2ftp_messages["Copy the selected entries"] = "Kopieer de geselecteerde reeksen";
$net2ftp_messages["Move the selected entries"] = "Verplaats de geselecteerde reeksen";
$net2ftp_messages["Delete the selected entries"] = "Verwijder de geselecteerde reeksen";
$net2ftp_messages["Rename the selected entries"] = "Hernoem de geselecteerde reeksen";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "Chmod de geselecteerde reeks (werkt alleen op Unix/Linux/BSD servers)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "Download een zip bestand die alle geselecteerde reeksen bevat";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Unzip het geselecteerde archief op de FTP server";
$net2ftp_messages["Zip the selected entries to save or email them"] = "Zip de geselecteerde reeks en sla deze op, of email deze";
$net2ftp_messages["Calculate the size of the selected entries"] = "Bereken de grootte van de geselecteerde reeks";
$net2ftp_messages["Find files which contain a particular word"] = "Vind bestanden die een bepaald woord bevatten";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "Klik om op %1\$s te sorteren in omgekeerde volgorde";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "Klik om op %1\$s te sorteren in alfabetische volgorde";
$net2ftp_messages["Ascending order"] = "Alfabetische volgorde";
$net2ftp_messages["Descending order"] = "Omgekeerde volgorde";
$net2ftp_messages["Upload files"] = "Upload files";
$net2ftp_messages["Up"] = "Omhoog";
$net2ftp_messages["Click to check or uncheck all rows"] = "Klik hier om alle rijen te selecteren of deselecteren";
$net2ftp_messages["All"] = "Alles";
$net2ftp_messages["Name"] = "Naam";
$net2ftp_messages["Type"] = "Type";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "Eigenaar";
$net2ftp_messages["Group"] = "Groep";
$net2ftp_messages["Perms"] = "Rechten";
$net2ftp_messages["Mod Time"] = "Gewijzigd Op";
$net2ftp_messages["Actions"] = "Acties";
$net2ftp_messages["Select the directory %1\$s"] = "Selecteer de map %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "Selecteer het bestand %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "Selecteer de symlink %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "Ga naar de map %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "Download het bestand %1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "Volg symlink %1\$s";
$net2ftp_messages["View"] = "Bekijk";
$net2ftp_messages["Edit"] = "Bewerk";
$net2ftp_messages["Update"] = "Vernieuw";
$net2ftp_messages["Open"] = "Open";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Bekijk de geaccentueerde bron van het bestand %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Bewerk de bron van het bestand %1\$s";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "Upload een nieuwere versie van het bestand %1\$s en voeg de wijzigingen samen";
$net2ftp_messages["View image %1\$s"] = "Bekijk afbeelding %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "Bekijk het bestand %1\$s vanaf uw HTTP web server";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(Opmerking: Deze link werkt mogelijk niet als u geen eigen domeinnaam heeft.)";
$net2ftp_messages["This folder is empty"] = "Deze map is leeg";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "Mappen";
$net2ftp_messages["Files"] = "Bestanden";
$net2ftp_messages["Symlinks"] = "Symlinks";
$net2ftp_messages["Unrecognized FTP output"] = "Niet herkende FTP uitvoer";
$net2ftp_messages["Number"] = "Aantal";
$net2ftp_messages["Size"] = "Grootte";
$net2ftp_messages["Skipped"] = "Overgeslagen";
$net2ftp_messages["Data transferred from this IP address today"] = "Data verzonden vanaf dit IP adres vandaag";
$net2ftp_messages["Data transferred to this FTP server today"] = "Data verzonden naar deze FTP server vandaag";

// printLocationActions()
$net2ftp_messages["Language:"] = "Taal:";
$net2ftp_messages["Skin:"] = "Uiterlijk:";
$net2ftp_messages["View mode:"] = "Bekijk mode:";
$net2ftp_messages["Directory Tree"] = "Mappen volgorde";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "Voer %1\$s uit in een nieuw venster";
$net2ftp_messages["This file is not accessible from the web"] = "Dit bestand is niet via het web toegankelijk";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "Dubbel klik om naar een onderliggende map te gaan:";
$net2ftp_messages["Choose"] = "Kies";
$net2ftp_messages["Up"] = "Omhoog";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "Grootte van de geselecteerde mappen en bestanden";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "De totale grootte van de geselecteerde mappen en bestanden is:";
$net2ftp_messages["The number of files which were skipped is:"] = "Het aantal overgeslagen bestanden is:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "Chmod mappen en bestanden";
$net2ftp_messages["Set all permissions"] = "Wijzig alle rechten";
$net2ftp_messages["Read"] = "Lezen";
$net2ftp_messages["Write"] = "Schrijven";
$net2ftp_messages["Execute"] = "Uitvoeren";
$net2ftp_messages["Owner"] = "Eigenaar";
$net2ftp_messages["Group"] = "Groep";
$net2ftp_messages["Everyone"] = "Iedereen";
$net2ftp_messages["To set all permissions to the same values, enter those permissions above and click on the button \"Set all permissions\""] = "Om alle rechten gelijk te maken, specificeer deze rechten hierboven en klik op de knop \"Wijzig alle rechten\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "Stel de rechten van me map <b>%1\$s</b> in op: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "Stel de rechten van het bestand <b>%1\$s</b> in op: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "Stel de rechten van de symlink <b>%1\$s</b> in op: ";
$net2ftp_messages["Chmod value"] = "Chmod waarde";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "Chmod ook de onderliggende mappen van deze map";
$net2ftp_messages["Chmod also the files within this directory"] = "Chmod ook alle bestanden in deze map";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "Het chmod nummer <b>%1\$s</b> is buiten het limiet van 000-777. Probeert u het a.u.b. opnieuw.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "Kies een map";
$net2ftp_messages["Copy directories and files"] = "Kopieer mappen en bestanden";
$net2ftp_messages["Move directories and files"] = "Verplaats mappen en bestanden";
$net2ftp_messages["Delete directories and files"] = "Verwijder mappen en bestanden";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "Weet u zeker dat u deze mappen en bestanden wilt verwijderen?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "Alle onderliggende mappen zullen ook worden verwijderd!";
$net2ftp_messages["Set all targetdirectories"] = "Stel alle doel mappen in";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Om een gemeenschappelijke doel map op te geven, voer die doel map in in de bovenstaande textbox, en klik op de knop \"Stel alle doel mappen in\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Opmerking: deze doel map moet reeds bestaan voordat er iets naar toe kan worden gekopieerd.";
$net2ftp_messages["Different target FTP server:"] = "Andere doel FTP server:";
$net2ftp_messages["Username"] = "Gebruikersnaam";
$net2ftp_messages["Password"] = "Wachtwoord";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "Laat leeg als u de bestanden naar de zelfde FTP server wilt kopiëren.";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "Als u de bestanden naar een andere FTP server wilt kopiëren, voert u dan de login gegevens in.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "Laat leeg als u de bestanden naar de zelfde FTP server wilt kopiëren.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "Als u de bestanden naar een andere FTP server wilt kopiëren, voert u dan de login gegevens in.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "Kopieer map <b>%1\$s</b> naar:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "Verplaats map <b>%1\$s</b> naar:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "Map <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "Kopieer bestand <b>%1\$s</b> naar:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "Verplaats bestand <b>%1\$s</b> naar:";
$net2ftp_messages["File <b>%1\$s</b>"] = "Bestand <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "Kopieer symlink <b>%1\$s</b> naar:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "Verplaats symlink <b>%1\$s</b> naar:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Symlink <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "Doel map:";
$net2ftp_messages["Target name:"] = "Doel naam:";
$net2ftp_messages["Processing the entries:"] = "Verwerk de reeks:";

} // end copymovedelete


// -------------------------------------------------------------------------
// Download file module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// EasyWebsite module
if ($net2ftp_globals["state"] == "easyWebsite") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create a website in 4 easy steps"] = "Creeer een website in 4 makkelijke stappen";
$net2ftp_messages["Template overview"] = "Template overview";
$net2ftp_messages["Template details"] = "Template details";
$net2ftp_messages["Files are copied"] = "Bestanden worden gekopieerd";
$net2ftp_messages["Edit your pages"] = "Bewerk uw paginas";

// Screen 1 - printTemplateOverview
$net2ftp_messages["Click on the image to view the details of a template."] = "Klik op de afbeelding om de details te bekijken van een template.";
$net2ftp_messages["Back to the Browse screen"] = "Terug naar het Browse scherm";
$net2ftp_messages["Template"] = "Template";
$net2ftp_messages["Copyright"] = "Copyright";
$net2ftp_messages["Click on the image to view the details of this template"] = "Klik op deze afbeelding om de details te bekijken van deze template";

// Screen 2 - printTemplateDetails
$net2ftp_messages["The template files will be copied to your FTP server. Existing files with the same filename will be overwritten. Do you want to continue?"] = "De template bestanden zullen worden gecopieerd naar de FTP server. Bestaande bestanden met dezelfde naam zullen worden overschreven. Wil u verdergaan?";
$net2ftp_messages["Install template to directory: "] = "Installeer template naar map: ";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Size"] = "Grootte";
$net2ftp_messages["Preview page"] = "Bekijk pagina";
$net2ftp_messages["opens in a new window"] = "wordt in een nieuw venster geopend";

// Screen 3
$net2ftp_messages["Please wait while the template files are being transferred to your server: "] = "Een ogenblikje geduld; de template bestanden worden getransfereerd naar uw server: ";
$net2ftp_messages["Done."] = "Klaar.";
$net2ftp_messages["Continue"] = "Ga verder";

// Screen 4 - printEasyAdminPanel
$net2ftp_messages["Edit page"] = "Bewerk pagina";
$net2ftp_messages["Browse the FTP server"] = "Blader doorheen de FTP server";
$net2ftp_messages["Add this link to your favorites to return to this page later on!"] = "Voeg deze link toe aan uw Favorieten om terug te kunnen komen naar deze pagina!";
$net2ftp_messages["Edit website at %1\$s"] = "Bewerk website op %1\$s";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: rechter muisklik op de link, en kies \"Toevoegen aan Favorieten...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: rechter muisklik op de link, en kies \"Bladwijzer van deze pagina maken...\"";

// ftp_copy_local2ftp
$net2ftp_messages["WARNING: Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing..."] = "WAARSCHUWING: Het aanmaken van de map <b>%1\$s</b> is mislukt. Deze bestaat mogelijk al. Het programma wordt voortgezet...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Doelmap <b>%1\$s</b> is aangemaakt";
$net2ftp_messages["WARNING: Unable to copy the file <b>%1\$s</b>. Continuing..."] = "WAARSCHUWING: Het kopiëren van het bestand <b>%1\$s</b> is mislukt. Het programma wordt voortgezet...";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Bestand <b>%1\$s</b> gekopieerd";
}


// -------------------------------------------------------------------------
// Edit module
if ($net2ftp_globals["state"] == "edit") {
// -------------------------------------------------------------------------

// /modules/edit/edit.inc.php
$net2ftp_messages["Unable to open the template file"] = "Het openen van het template bestand is mislukt";
$net2ftp_messages["Unable to read the template file"] = "Het lezen van het template bestand is mislukt";
$net2ftp_messages["Please specify a filename"] = "Kiest u a.u.b. een bestandsnaam";
$net2ftp_messages["Status: This file has not yet been saved"] = "Status: Dit bestand is nog niet opgeslagen";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "Status: Opgeslagen om <b>%1\$s</b> met de mode %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "Status: <b>Dit bestand kon niet worden opgeslagen</b>";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "Map: ";
$net2ftp_messages["File: "] = "Bestand: ";
$net2ftp_messages["New file name: "] = "Nieuwe bestandsnaam: ";
$net2ftp_messages["Character encoding: "] = "Character encoding: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "Opmerking: als u verandert van textarea worden de wijzigingen opgeslagen";
$net2ftp_messages["Copy up"] = "Copy up";
$net2ftp_messages["Copy down"] = "Copy down";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "Doorzoek mappen en bestanden";
$net2ftp_messages["Search again"] = "Zoek opnieuw";
$net2ftp_messages["Search results"] = "Zoek resultaten";
$net2ftp_messages["Please enter a valid search word or phrase."] = "Kies a.u.b. een geldig zoek woord of uitdrukking.";
$net2ftp_messages["Please enter a valid filename."] = "Kies a.u.b. een geldig bestandsnaam.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "Kies a.u.b. een geldig bestands grootte in de \"van\" textbox, bijvoorbeeld 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "Kies a.u.b. een geldig bestands grootte in de \"tot\" textbox, bijvoorbeeld 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "Kies a.u.b. een geldige datum in het Y-m-d formaat in de \"van\" textbox.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "Kies a.u.b. een geldige datum in het Y-m-d formaat in de \"tot\" textbox.";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "Het woord <b>%1\$s</b> is niet gevonden in de geselecteerde mappen en bestanden.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "Het woord <b>%1\$s</b> is gevonden in de volgende bestanden:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "Zoek naar een woord of uitdrukking";
$net2ftp_messages["Case sensitive search"] = "Zoek hoofdletter gevoelig";
$net2ftp_messages["Restrict the search to:"] = "Beperk het zoeken tot:";
$net2ftp_messages["files with a filename like"] = "bestanden met een bestandsnaam die lijken op";
$net2ftp_messages["(wildcard character is *)"] = "(wildcard karakter is *)";
$net2ftp_messages["files with a size"] = "bestand met een grootte";
$net2ftp_messages["files which were last modified"] = "bestanden die als laatst zijn gewijzigd";
$net2ftp_messages["from"] = "van";
$net2ftp_messages["to"] = "tot";

$net2ftp_messages["Directory"] = "Map";
$net2ftp_messages["File"] = "Bestand";
$net2ftp_messages["Line"] = "Regel";
$net2ftp_messages["Action"] = "Action";
$net2ftp_messages["View"] = "Bekijk";
$net2ftp_messages["Edit"] = "Bewerk";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Bekijk de geaccentueerde bron van het bestand %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Bewerk de bron van het bestand %1\$s";

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
$net2ftp_messages["Install software packages"] = "Installeer software pakketten";
$net2ftp_messages["Unable to open the template file"] = "Het openen van het template bestand is mislukt";
$net2ftp_messages["Unable to read the template file"] = "Het lezen van het template bestand is mislukt";
$net2ftp_messages["Unable to get the list of packages"] = "Lijst van pakketten kon niet worden opgehaald";

// /skins/blue/install1.template.php
$net2ftp_messages["The net2ftp installer script has been copied to the FTP server."] = "Het net2ftp installatie script is gekopieerd naar de FTP server.";
$net2ftp_messages["This script runs on your web server and requires PHP to be installed."] = "Dit script draait op uw webserver en vereist dat PHP geinstalleerd is.";
$net2ftp_messages["In order to run it, click on the link below."] = "Klik op onderstaande link om het uit te voeren.";
$net2ftp_messages["net2ftp has tried to determine the directory mapping between the FTP server and the web server."] = "net2ftp heeft geprobeerd de mapping tussen de FTP server en de Webserver te bepalen.";
$net2ftp_messages["Should this link not be correct, enter the URL manually in your web browser."] = "Indien dit onjuist is, voer dan handmatig de URL in in uw webbrowser.";

} // end install


// -------------------------------------------------------------------------
// Java upload module
if ($net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload directories and files using a Java applet"] = "Upload mappen en bestanden met een Java applet";
$net2ftp_messages["Number of files:"] = "Aantal bestanden:";
$net2ftp_messages["Size of files:"] = "Grootte van bestanden:";
$net2ftp_messages["Add"] = "Voeg toe";
$net2ftp_messages["Remove"] = "Verwijder";
$net2ftp_messages["Upload"] = "Upload";
$net2ftp_messages["Add files to the upload queue"] = "Voeg bestanden toe aan de upload wachtrij";
$net2ftp_messages["Remove files from the upload queue"] = "Verwijder bestanden uit de upload wachtrij";
$net2ftp_messages["Upload the files which are in the upload queue"] = "Upload de bestanden die in de wachtrij staan";
$net2ftp_messages["Maximum server space exceeded. Please select less/smaller files."] = "Maximum ruimte op server overschreden. Selecteer a.u.b. minder/kleinere bestanden.";
$net2ftp_messages["Total size of the files is too big. Please select less/smaller files."] = "De totale grootte van de bestanden is te groot. Selecteer a.u.b. minder/kleinere bestanden.";
$net2ftp_messages["Total number of files is too high. Please select fewer files."] = "Er zijn teveel bestanden opgegeven. Selecteer a.u.b. minder bestanden.";
$net2ftp_messages["Note: to use this applet, Sun's Java plugin must be installed (version 1.4 or newer)."] = "Opmerking: om van deze applet gebruik te maken moet Sun's Java plugin zijn geinstalleerd (versie 1.4 of nieuwer).";

} // end jupload



// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login") {
// -------------------------------------------------------------------------
$net2ftp_messages["Login!"] = "Login!";
$net2ftp_messages["Once you are logged in, you will be able to:"] = "Eenmaal ingelogd kunt u:";
$net2ftp_messages["Navigate the FTP server"] = "Navigeer op de FTP server";
$net2ftp_messages["Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."] = "Eenmaal ingelogd, kunt u van map naar map navigeren en alle submappen en inhoud zien.";
$net2ftp_messages["Upload files"] = "Upload files";
$net2ftp_messages["There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet."] = "Er zijn 3 verschillende manieren op bestanden te uploaden: via het standaard upload formulier, de upload-and-unzip functionaliteit, en via de Java Applet.";
$net2ftp_messages["Download files"] = "Download bestanden";
$net2ftp_messages["Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive."] = "Klik op een bestandsnaam om snel 1 bestand te downloaden.<br />Selecteer meerdere bestanden en klik op Download; De geselecteerde bestanden worden automatisch in een zip bestand verstuurd.";
$net2ftp_messages["Zip files"] = "Zip bestanden";
$net2ftp_messages["... and save the zip archive on the FTP server, or email it to someone."] = "... en sla het zip archief op op de server, of email het naar iemand.";
$net2ftp_messages["Unzip files"] = "Unzip bestanden";
$net2ftp_messages["Different formats are supported: .zip, .tar, .tgz and .gz."] = "Verschillende formaten worden ondersteund: .zip, .tar, .tgz and .gz.";
$net2ftp_messages["Install software"] = "Installeer software";
$net2ftp_messages["Choose from a list of popular applications (PHP required)."] = "Kies van een lijst met populaire applicaties (PHP vereist).";
$net2ftp_messages["Copy, move and delete"] = "Kopieer, verplaats en verwijder";
$net2ftp_messages["Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted."] = "Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted.";
$net2ftp_messages["Copy or move to a 2nd FTP server"] = "Kopieer of verplaats naar een 2e FTP server";
$net2ftp_messages["Handy to import files to your FTP server, or to export files from your FTP server to another FTP server."] = "Handig om bestanden van je FTP server te importeren of exporteren naar een andere FTP server.";
$net2ftp_messages["Rename and chmod"] = "Hernoen en wijzig rechten";
$net2ftp_messages["Chmod handles directories recursively."] = "Chmod verwerkt mappen recursief.";
$net2ftp_messages["View code with syntax highlighting"] = "Bekijk code met syntax highlighting";
$net2ftp_messages["PHP functions are linked to the documentation on php.net."] = "PHP functies zijn gelinkt aan de documentatie op php.net.";
$net2ftp_messages["Plain text editor"] = "Normale text editor";
$net2ftp_messages["Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server."] = "Wijzig text vanuit je browser; Iedere keer dat je wijzigingen opslaat is het nieuwe bestand geupload naar de server.";
$net2ftp_messages["HTML editors"] = "HTML editors";
$net2ftp_messages["Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from."] = "Wijzig HTML via een What-You-See-Is-What-You-Get (WYSIWYG) formulier; Er zijn 2 verschillende editors om uit te kiezen.";
$net2ftp_messages["Code editor"] = "Code editor";
$net2ftp_messages["Edit HTML and PHP in an editor with syntax highlighting."] = "Wijzig HTML en PHP in een editor met syntax highlighting.";
$net2ftp_messages["Search for words or phrases"] = "Zoek naar woorden of termen.";
$net2ftp_messages["Filter out files based on the filename, last modification time and filesize."] = "Filtereer bestanden gebaseerd op bestandsnaam, laatst aangepast datum en/of bestandsgrootte.";
$net2ftp_messages["Calculate size"] = "Calculate size";
$net2ftp_messages["Calculate the size of directories and files."] = "Bereken de totale grootte van de mappen en bestanden.";

$net2ftp_messages["FTP server"] = "FTP server";
$net2ftp_messages["Example"] = "Voorbeeld";
$net2ftp_messages["Port"] = "Poort";
$net2ftp_messages["Username"] = "Gebruikersnaam";
$net2ftp_messages["Password"] = "Wachtwoord";
$net2ftp_messages["Anonymous"] = "Anoniem";
$net2ftp_messages["Passive mode"] = "Passieve mode";
$net2ftp_messages["Initial directory"] = "Begin map";
$net2ftp_messages["Language"] = "Taal";
$net2ftp_messages["Skin"] = "Uiterlijk";
$net2ftp_messages["FTP mode"] = "FTP mode";
$net2ftp_messages["Automatic"] = "Automatisch";
$net2ftp_messages["Login"] = "Inloggen";
$net2ftp_messages["Clear cookies"] = "Verwijder cookies";
$net2ftp_messages["Admin"] = "Admin";
$net2ftp_messages["Please enter an FTP server."] = "Gelieve een FTP server in te vullen.";
$net2ftp_messages["Please enter a username."] = "Gelieve een gebruikersnaam in te vullen.";
$net2ftp_messages["Please enter a password."] = "Gelieve een paswoord in te vullen.";

} // end login


// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login_small") {
// -------------------------------------------------------------------------

$net2ftp_messages["Please enter your Administrator username and password."] = "Gelieve uw beheerders gebruikersnaam en wachtwoord in te vullen.";
$net2ftp_messages["Please enter your username and password for FTP server <b>%1\$s</b>."] = "Gelieve uw gebruikersnaam en wachtwoord voor de FTP server in te vullen <b>%1\$s</b>.";
$net2ftp_messages["Username"] = "Gebruikersnaam";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Uw sessie is verlopen; vul nogmaals uw gebruikersnaam en wachtwoord in voor FTP server <b>%1\$s</b> om door te gaan.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Uw IP adres is veranderd; vul nogmaals uw gebruikersnaam en wachtwoord in voor FTP server <b>%1\$s</b> om door te gaan.";
$net2ftp_messages["Password"] = "Wachtwoord";
$net2ftp_messages["Login"] = "Inloggen";
$net2ftp_messages["Continue"] = "Ga verder";

} // end login_small


// -------------------------------------------------------------------------
// Logout module
if ($net2ftp_globals["state"] == "logout") {
// -------------------------------------------------------------------------

// logout.inc.php
$net2ftp_messages["Login page"] = "Login page";

// logout.template.php
$net2ftp_messages["You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>."] = "U bent afgemeld van de FTP server. Om u opnieuw aan te melden, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">klikt u hier</a>.";
$net2ftp_messages["Note: other users of this computer could click on the browser's Back button and access the FTP server."] = "Let op: andere gebruikers van deze computer kunnen de 'back' knop van uw browser gebruiken om in te loggen op de FTP server.";
$net2ftp_messages["To prevent this, you must close all browser windows."] = "Om dit te voorkomen moet u alle browser vensters sluiten.";
$net2ftp_messages["Close"] = "Sluiten";
$net2ftp_messages["Click here to close this window"] = "Klik hier om dit venster te sluiten";

} // end logout


// -------------------------------------------------------------------------
// New directory module
if ($net2ftp_globals["state"] == "newdir") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create new directories"] = "Creëer nieuwe map";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "De nieuwe map wordt gecreëerd in <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "Nieuwe map naam:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "De map <b>%1\$s</b> is succesvol gecreëerd.";
$net2ftp_messages["Directory <b>%1\$s</b> could not be created."] = "De map <b>%1\$s</b> kon niet worden aangemaakt.";

} // end newdir


// -------------------------------------------------------------------------
// Raw module
if ($net2ftp_globals["state"] == "raw") {
// -------------------------------------------------------------------------

// /modules/raw/raw.inc.php
$net2ftp_messages["Send arbitrary FTP commands"] = "Verstuur ruwe FTP commandos";


// /skins/[skin]/raw1.template.php
$net2ftp_messages["List of commands:"] = "Lijst van commandos:";
$net2ftp_messages["FTP server response:"] = "FTP server antwoord:";

} // end raw


// -------------------------------------------------------------------------
// Rename module
if ($net2ftp_globals["state"] == "rename") {
// -------------------------------------------------------------------------
$net2ftp_messages["Rename directories and files"] = "Hernoem mappen en bestanden";
$net2ftp_messages["Old name: "] = "Oude naam: ";
$net2ftp_messages["New name: "] = "Nieuwe naam: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "De nieuwe naam mag geen punten bevatten. Deze reeks is niet hernoemt naar <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "De niewe naam mag geen verboden termen bevatten. Het bestand is niet hernoemd naar: <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> is succesvol hernoemd naar <b>%2\$s</b>";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> is niet hernoemd naar <b>%2\$s</b>";

} // end rename


// -------------------------------------------------------------------------
// Unzip module
if ($net2ftp_globals["state"] == "unzip") {
// -------------------------------------------------------------------------

// /modules/unzip/unzip.inc.php
$net2ftp_messages["Unzip archives"] = "Unzip archieven";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Bezig met ophalen archief %1\$s van %2\$s van de FTP server";
$net2ftp_messages["Unable to get the archive <b>%1\$s</b> from the FTP server"] = "Kon archief <b>%1\$s</b> niet ophalen van de FTP server";

// /skins/[skin]/unzip1.template.php
$net2ftp_messages["Set all targetdirectories"] = "Stel alle doel mappen in";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Om een gemeenschappelijke doel map op te geven, voer die doel map in in de bovenstaande textbox, en klik op de knop \"Stel alle doel mappen in\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Opmerking: deze doel map moet reeds bestaan voordat er iets naar toe kan worden gekopieerd.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Unzip archief <b>%1\$s</b> naar:";
$net2ftp_messages["Target directory:"] = "Doel map:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Gebruik map structuur (creëert onderliggende mappen automatisch)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "Update bestanden";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>WAARSCHUWING: DEZE FUNCTIE IS PAS IN EEN VROEGE ONTWERPFASE. GEBRUIK HET ALLEEN OP TEST BESTANDEN! U BENT GEWAARSCHUWD!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "Bekende fouten: - verwijderd tab karakters - werkt niet goed op grootte bestanden (> 50kB) - is niet getest op bestanden die niet-standaard karakters bevatten</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "Deze functie stelt u in staat om een nieuwere versie van een bestand te uploaden, om de wijzigingen te bekijken, en om deze wijzigingen te accepteren of af te wijzen. Voordat er iets wordt opgeslagen kunt u wijzigingen in de samengevoegde bestanden toebrengen.";
$net2ftp_messages["Old file:"] = "Oud bestand:";
$net2ftp_messages["New file:"] = "Nieuw bestand:";
$net2ftp_messages["Restrictions:"] = "Beperkingen:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "De maximale grootte van de bestanden zijn beperkt door net2ftp tot <b>%1\$s kB</b> en door PHP tot <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "De maximale uitvoeringstijd is <b>%1\$s seconden</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "De FTP verzend mode (ASCII of BINARY) wordt automatisch gedetecteerd, afhankelijk van de bestandsnaam extensie";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Als het doel bestand al bestaat, wordt deze overschreven";
$net2ftp_messages["You did not provide any files or archives to upload."] = "U heeft geen bestanden of archieven opgegeven om te worden geupload.";
$net2ftp_messages["Unable to delete the new file"] = "Het verwijderen van het nieuwe bestand is mislukt";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "Even wachten...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "Selecteer onderstaande lijnen, accepteer of verwerp wijzigingen in het formulier.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "Upload naar map:";
$net2ftp_messages["Files"] = "Bestanden";
$net2ftp_messages["Archives"] = "Archieven";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "Deze opgegeven bestanden worden naar de FTP server verzonden.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "Deze opgegeven archieven zullen worden uitgepakt, en de inhoud zal naar de FTP server worden verzonden";
$net2ftp_messages["Add another"] = "Voeg nog een toe";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Gebruik map structuur (creëert onderliggende mappen automatisch)";

$net2ftp_messages["Choose a directory"] = "Kies een map";
$net2ftp_messages["Please wait..."] = "Even wachten...";
$net2ftp_messages["Uploading... please wait..."] = "Aan het uploaden... even wachten...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "Als het uploaden langer duurt dan de toegestane <b>%1\$s<\/b> seconden, moet u het opnieuw proberen met minder/kleinere bestanden.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "Dit venster wordt automatisch gesloten in enkele seconden.";
$net2ftp_messages["Close window now"] = "Sluit dit venster";

$net2ftp_messages["Upload files and archives"] = "Upload bestanden en archieven";
$net2ftp_messages["Upload results"] = "Upload resultaten";
$net2ftp_messages["Checking files:"] = "Controleren van bestanden:";
$net2ftp_messages["Transferring files to the FTP server:"] = "Bestanden worden naar de FTP server verzonden:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "Archieven worden uitgepakt en verzonden naar de FTP server:";
$net2ftp_messages["Upload more files and archives"] = "Upload meer bestanden en archieven";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "Beperkingen:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "De maximale grootte van de bestanden zijn beperkt door net2ftp tot <b>%1\$s kB</b> en door PHP tot <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "De maximale uitvoeringstijd is <b>%1\$s seconden</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "De FTP verzend mode (ASCII of BINARY) wordt automatisch gedetecteerd, afhankelijk van de bestandsnaam extensie";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Als het doel bestand al bestaat, wordt deze overschreven";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "Bekijk bestand %1\$s";
$net2ftp_messages["View image %1\$s"] = "Bekijk afbeelding %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "Bekijk Macromedia ShockWave Flash video %1\$s";
$net2ftp_messages["Image"] = "Foto";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntax highlighting aangedreven door <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "Voor het opslaan van deze afbeelding: rechter muisklik op de afbeelding, en kies 'Afbeelding opslaan als...'";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "Zip reeks";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "Sla het zip bestand op de FTP server op als:";
$net2ftp_messages["Email the zip file in attachment to:"] = "Email het zip bestand als bijlage naar:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "Let op dat dit niet anoniem gebeurd: uw IP adres, en de tijd waarop deze is verzonden wordt toegevoegd aan de email.";
$net2ftp_messages["Some additional comments to add in the email:"] = "Voeg een opmerking toe aan deze email:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "U heeft geen bestandsnaam opgegeven voor het zip bestand. Ga terug, en geef deze op.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "Het door u opgegeven email adres (%1\$s) is ongeldig.<br />Gebruik het formaat <b>gebruikersnaam@domein.com</b>";

} // end zip

?>