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
//  |     $messages[...] = ["Le fichier %1\$s a ÈtÈ copiÈ vers %2\$s "]             |
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
$net2ftp_messages["en"] = "fi";

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

$net2ftp_messages["Connecting to the FTP server"] = "Yhdistet‰‰n FTP-palvelimelle";
$net2ftp_messages["Logging into the FTP server"] = "Kirjaudutaan sis‰‰n FTP-palvelimelle";
$net2ftp_messages["Setting the passive mode"] = "Asetetaan passiivitilaan";
$net2ftp_messages["Getting the FTP system type"] = "Haetaan FTP:n j‰rjestelm‰tyyppi‰";
$net2ftp_messages["Changing the directory"] = "Muutetaan hakemistoa";
$net2ftp_messages["Getting the current directory"] = "Haetaan nykyist‰ hakemistoa";
$net2ftp_messages["Getting the list of directories and files"] = "Haetaan listaa kansioista ja tiedostoista";
$net2ftp_messages["Parsing the list of directories and files"] = "J‰sennet‰‰n listaa kansioista ja tiedostoista";
$net2ftp_messages["Logging out of the FTP server"] = "Kirjaudutaan ulos FTP-palvelimelta";
$net2ftp_messages["Getting the list of directories and files"] = "Haetaan listaa kansioista ja tiedostoista";
$net2ftp_messages["Printing the list of directories and files"] = "Tulostetaan listaa kansioista ja tiedostoista";
$net2ftp_messages["Processing the entries"] = "K‰sitell‰‰n kohteita";
$net2ftp_messages["Processing entry %1\$s"] = "K‰sitell‰‰n kohdetta %1\$s";
$net2ftp_messages["Checking files"] = "Tarkistetaan teidostoja";
$net2ftp_messages["Transferring files to the FTP server"] = "Siirret‰‰n tiedostoja FTP-palvelimelle";
$net2ftp_messages["Decompressing archives and transferring files"] = "Puretaan paketteja ja siirret‰‰n tiedostoja";
$net2ftp_messages["Searching the files..."] = "Etsit‰‰n tiedostoja...";
$net2ftp_messages["Uploading new file"] = "Ladataan uutta tiedostoa palvelimelle";
$net2ftp_messages["Reading the file"] = "Luetaan tiedostoa";
$net2ftp_messages["Parsing the file"] = "J‰sennet‰‰n tiedostoa";
$net2ftp_messages["Reading the new file"] = "Luetaan uutta tiedostoa";
$net2ftp_messages["Reading the old file"] = "Luetaan vanhaa tiedostoa";
$net2ftp_messages["Comparing the 2 files"] = "Verrataan kahta tiedostoa";
$net2ftp_messages["Printing the comparison"] = "Tulostetaan vertailua";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "L‰hetet‰‰n FTP-komentoa %1\$s/%2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Haetaan pakettia %1\$s/%2\$s FTP-palvelimelta";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Luodaan v‰liaikaista hakemistoa FTP-palvelimelle";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Asetetaan oikeuksia v‰liaikaiselle hakemistolle";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Kopioidaan net2ftp:n asennus FTP-palvelimelle";
$net2ftp_messages["Script finished in %1\$s seconds"] = "Skripti on valmis %1\$s sekunnin kuluttua";
$net2ftp_messages["Script halted"] = "Skripti pys‰ytettiin";

// Used on various screens
$net2ftp_messages["Please wait..."] = "Odota...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "M‰‰ritt‰m‰tˆn tilamerkkijono: %1\$s. Poistutaan.";
$net2ftp_messages["This beta function is not activated on this server."] = "T‰m‰ beta-funktio ei ole k‰ytˆss‰ t‰ll‰ palvelimella.";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "T‰m‰n sivuston yll‰pit‰j‰ on poistanut funktion k‰ytˆst‰.";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "Hakemistoa <b>%1\$s</b> ei ole olemassa tai sit‰ ei voida valita, joten hakemisto <b>%2\$s</b> n‰ytet‰‰n sen sijaan.";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "P‰‰hakemistoa <b>%1\$s</b> ei ole olemassa tai sit‰ ei voida valita.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "Hakemistoa <b>%1\$s</b> ei voida valita - sinulla ei ole ehk‰ tarpeeksi oikeuksia hakemiston n‰ytt‰miseen tai sit‰ ei ehk‰ ole olemassa.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Kohteita, jotka sis‰lt‰v‰t kiellettyj‰ sanoja, ei voida hallita net2ftp:t‰ k‰ytt‰m‰ll‰. T‰ll‰ pyrit‰‰n v‰ltt‰m‰‰n, ettei Paypalia tai Ebayta voitaisi huijata net2ftp:t‰ k‰ytt‰m‰ll‰.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Tiedostoja, jotka ovat liian isoja, ei voida ladata, ladata palvelimelle, kopioida, siirt‰‰, hakea, pakata, purkaa, n‰ytt‰‰ tai muokata; niit‰ voidaan vain uudelleennimet‰, asettaa oikeuksia tai poistaa.";
$net2ftp_messages["Execute %1\$s in a new window"] = "Suorita %1\$s uudessa ikkunassa";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "Valitse v‰hint‰‰n yksi kansio tai tiedosto!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "FTP-palvelin <b>%1\$s</b> ei ole sallittujen FTP-palvelimien listalla.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "FTP-palvelin <b>%1\$s</b> on estettyjen FTP-palvelimien listalla.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "FTP-palvelinporttia %1\$s ei voi k‰ytt‰‰.";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "IP-osoitteesi (%1\$s) ei ole sallittujen IP-osoitteiden listalla.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "IP-osoitteesi (%1\$s) on estettyjen IP-osoitteiden listalla.";

// isAuthorizedDirectory()
$net2ftp_messages["Table net2ftp_users contains duplicate rows."] = "Taulu net2ftp_users sis‰lt‰‰ rivej‰, jotka ovat t‰ysin samanlaisia.";

// logAccess(), logLogin(), logError()
$net2ftp_messages["Unable to execute the SQL query."] = "SQL-kysely‰ ei voida suorittaa.";
$net2ftp_messages["Unable to open the system log."] = "J‰rjestelm‰lokia ei voida avata.";
$net2ftp_messages["Unable to write a message to the system log."] = "Viesti‰ ei voida kirjoittaa j‰rjestelm‰lokiin.";

// checkAdminUsernamePassword()
$net2ftp_messages["You did not enter your Administrator username or password."] = "Et syˆtt‰nyt j‰rjestelm‰nvalvojan k‰ytt‰j‰tunnusta tai salasanaa.";
$net2ftp_messages["Wrong username or password. Please try again."] = "V‰‰r‰ k‰ytt‰j‰tunnus tai salasana. Yrit‰ uudelleen.";


// -------------------------------------------------------------------------
// /includes/consumption.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to determine your IP address."] = "IP-osoitettasi ei voida selvitt‰‰.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "Taulu net2ftp_log_consumption_ipaddress sis‰lt‰‰ rivej‰, jotka ovat t‰ysin samanlaisia.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "Taulu net2ftp_log_consumption_ftpserver sis‰lt‰‰ rivej‰, jotka ovat t‰ysin samanlaisia.";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "Muuttujan <b>consumption_ipaddress_datatransfer</b> arvo ei ole numero.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "Taulua net2ftp_log_consumption_ipaddress ei voida p‰ivitt‰‰.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "Taulu net2ftp_log_consumption_ipaddress sis‰lt‰‰ rivej‰, jotka ovat t‰ysin samanlaisia..";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "Taulua net2ftp_log_consumption_ftpserver ei voida p‰ivitt‰‰.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "Taulu net2ftp_log_consumption_ftpserver sis‰lt‰‰ rivej‰, jotka ovat t‰ysin samanlaisia..";
$net2ftp_messages["Table net2ftp_log_access could not be updated."] = "Taulua net2ftp_log_access ei voida p‰ivitt‰‰.";
$net2ftp_messages["Table net2ftp_log_access contains duplicate entries."] = "Taulu net2ftp_log_access sis‰lt‰‰ rivej‰, jotka ovat t‰ysin samanlaisia..";


// -------------------------------------------------------------------------
// /includes/database.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "MySQL-tietokantaan ei voida yhdist‰‰. Tarkista MySQL-tietokanta-asetukset net2ftp:n asetustiedostossa settings.inc.php.";
$net2ftp_messages["Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "MYSQL-tietokantaa ei voida valita. Tarkista MySQL-tietokanta-asetukset net2ftp:n asetustiedostossa settings.inc.php.";


// -------------------------------------------------------------------------
// /includes/errorhandling.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["An error has occured"] = "Virhe ilmaantui";
$net2ftp_messages["Go back"] = "Palaa takaisin";
$net2ftp_messages["Go to the login page"] = "Siirry kirjautumissivulle";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "<a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">PHP:n FTP-moduulia</a> ei ole asennettu.<br /><br /> Sivuston yll‰pit‰j‰n pit‰isi asentaa FTP-moduuli. Asennusohjeet lˆytyv‰t <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.netist‰</a>.<br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "FTP-palvelimelle <b>%1\$s</b> porttiin <b>%2\$s</b> ei voida yhdist‰‰.<br /><br />Oletko varma, ett‰ FTP-palvelimen osoite on oikein? Yleens‰ se on eri osoite kuin palvelimen HTTP-osoite (verkko-osoite). Ota yhteytt‰ Internet-yhteytesi palveluntarjoajaan tai j‰rjestelm‰n yll‰pit‰j‰‰n saadaksesi ohjeita.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "Passiivitilaan ei voida vaihtaa FTP-palvelimella <b>%1\$s</b>.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Ei voida yhdist‰‰ toiseen (kohde-) FTP-palvelimeen <b>%1\$s</b> portissa <b>%2\$s</b>.<br /><br />Oletko varma, ett‰ t‰m‰ on  toisen (kohde-) FTP-palvelimen osoite? Yleens‰ se on eri osoite kuin palvelimen HTTP-osoite (verkko-osoite). Ota yhteytt‰ Internet-yhteytesi palveluntarjoajaan tai j‰rjestelm‰n yll‰pit‰j‰‰n saadaksesi ohjeita.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Ei voida kirjautua sis‰‰n toiselle (kohde-) FTP-palvelimelle <b>%1\$s</b> k‰ytt‰j‰tunnuksella <b>%2\$s</b>.<br /><br />Oletko varma, ett‰ k‰ytt‰j‰tunnus ja salasana ovat oikein? Ota yhteytt‰ Internet-yhteytesi palveluntarjoajaan tai j‰rjestelm‰n yll‰pit‰j‰‰n saadaksesi ohjeita.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "Hakemistoa tai tiedostoa <b>%1\$s</b> ei voida uudelleennimet‰ nimell‰ <b>%2\$s</b>";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "Sivustokomentoa <b>%1\$s</b> ei voida suorittaa. Huomaathan, ett‰ CHMOD-komento on saatavilla vain Unix-FTP-palvelimilla, ei Windows FTP-palvelimilla.";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "Hakemiston <b>%1\$s</b> oikeudet on asetettu oikeuksiksi <b>%2\$s</b>";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "Hakemiston <b>%1\$s</b> kohteita k‰sitell‰‰n:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "Tiedoston <b>%1\$s</b> oikeudet on asetettu oikeuksiksi <b>%2\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "Kaikki valitut kansiot ja tiedostot on k‰sitelty.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "Hakemistoa <b>%1\$s</b> ei voida poistaa.";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Tiedostoa <b>%1\$s</b> ei voida poistaa.";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "Hakemistoa <b>%1\$s</b> ei voida luoda.";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "V‰liaikaista tiedostoa ei voida luoda";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "Tiedostoa <b>%1\$s</b> ei voida hakea FTP-palvelimelta ja tallentaa sit‰ v‰liaikaiseen tiedostoon <b>%2\$s</b>.<br />Tarkista hakemiston %3\$s oikeudet.<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "V‰liaikaista tiedostoa ei voida avata. Tarkista hakemiston %1\$s oikeudet.";
$net2ftp_messages["Unable to read the temporary file"] = "V‰liaikaista tiedostoa ei voida lukea";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "V‰liaikaisen tiedoston k‰sittely‰ ei voida lopettaa";
$net2ftp_messages["Unable to delete the temporary file"] = "V‰liaikaista tiedostoa ei voida poistaa";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "V‰liaikaista tiedostoa ei voida luoda. Tarkista hakemiston %1\$s oikeudet.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "V‰liaikaista tiedostoa ei voida avata. Tarkista hakemiston %1\$s oikeudet.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "Merkkijonoa ei voida kirjoittaa v‰liaikaiseen tiedostoon <b>%1\$s</b>.<br />Tarkista hakemiston %2\$s oikeudet.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "V‰liaikaisen tiedoston k‰sittely‰ ei voida lopettaa";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "Tiedostoa <b>%1\$s</b> ei voida siirt‰‰ FTP-palvelimelle.<br />Sinulla ei ehk‰ ole kirjoitusoikeuksia hakemistoon.";
$net2ftp_messages["Unable to delete the temporary file"] = "V‰liaikaista tiedostoa ei voida poistaa";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "K‰sitell‰‰n hakemistoa <b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "Kohdehakemisto <b>%1\$s</b> on sama tai l‰hdehakemiston alihakemisto <b>%2\$s</b>, joten t‰m‰ hakemisto ohitetaan.";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "Hakemisto <b>%1\$s</b> sis‰lt‰‰ estetyn sanan, joten hakemisto ohitetaan";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "Hakemisto <b>%1\$s</b> sis‰lt‰‰ estetyn sanan, siirto hyl‰t‰‰n";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "Alihakemistoa <b>%1\$s</b> ei voida luoda. Se on ehk‰ jo olemassa. Jatketaan kopiointi/siirto -prosessia...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Kohde-alihakemisto <b>%1\$s</b> luotu";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "Hakemistoa <b>%1\$s</b> ei voida valita, joten t‰m‰ hakemisto ohitetaan";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "Alihakemistoa <b>%1\$s</b> ei voida poistaa - se ei ehk‰ ole tyhj‰";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "Alihakemisto <b>%1\$s</b> poistettu";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "Hakemiston <b>%1\$s</b> k‰sittely valmis";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "Kohde tiedostolle <b>%1\$s</b> on sama kuin l‰hde, tiedosto ohitetaan";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "Tiedosto <b>%1\$s</b> sis‰lt‰‰ estetyn sanan, joten tiedosto ohitetaan";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "Tiedosto <b>%1\$s</b> sis‰lt‰‰ estetyn sanan, siirto ohitetaan";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "Tiedosto <b>%1\$s</b> on liian iso kopioitavaksi, joten tiedosto ohitetaan";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "Tiedosto <b>%1\$s</b> on liian iso siirrett‰v‰ksi, siirto ohitetaan";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "Tiedostoa <b>%1\$s</b> ei voida kopioida";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Tiedosto <b>%1\$s</b> kopioitu";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Tiedostoa <b>%1\$s</b> ei voida siirt‰‰, siirto ohitetaan";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "Tiedosto <b>%1\$s</b> siirretty";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Tiedostoa <b>%1\$s</b> ei voida poistaa.";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "Tiedosto <b>%1\$s</b> poistettu";
$net2ftp_messages["All the selected directories and files have been processed."] = "Kaikki valitut kansiot ja tiedostot on k‰sitelty.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "Ulkoista tiedostoa <b>%1\$s</b> ei voida kopioida paikalliseen tiedostoon k‰ytt‰en FTP-tilaa <b>%2\$s</b>";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "Tiedostoa <b>%1\$s</b> ei voida poistaa";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "Tiedosto on liian iso siirrett‰v‰ksi";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "P‰ivitt‰inen raja saavutettu: tiedostoa <b>%1\$s</b> ei voida siirt‰‰";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "Paikallista tiedostoa ei voida kopioida ulkoiseen tiedostoon <b>%1\$s</b> k‰ytt‰en FTP-tilaa <b>%2\$s</b>";
$net2ftp_messages["Unable to delete the local file"] = "Paikallista tiedostoa ei voida poistaa";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "V‰liaikaista tiedostoa ei voida poistaa";
$net2ftp_messages["Unable to send the file to the browser"] = "Tiedostoa ei voida l‰hett‰‰ selaimelle";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "V‰liaikaista tiedostoa ei voida luoda";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "ZIP-tiedosto on tallennettu FTP-palvelimelle nimell‰ <b>%1\$s</b>";
$net2ftp_messages["Requested files"] = "Pyydetyt tiedostot";

$net2ftp_messages["Dear,"] = "Hyv‰";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "Joku on pyyt‰nyt, ett‰ tiedostot liitteess‰ l‰hetet‰‰n t‰lle s‰hkˆpostitilille (%1\$s).";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "Jos et tied‰ mit‰‰n t‰st‰ tai jos et luota t‰h‰n henkilˆˆn, poista t‰m‰ s‰hkˆpostiviesti ilman ZIP-tiedoston, joka on liitteess‰, avaamista.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "Huomaathan, ett‰ jos et avaa ZIP-tiedostoa, tiedostot sen sis‰ll‰ eiv‰t voi vahingoittaa tietokonettasi.";
$net2ftp_messages["Information about the sender: "] = "Tietoja l‰hett‰j‰st‰: ";
$net2ftp_messages["IP address: "] = "IP-osoite: ";
$net2ftp_messages["Time of sending: "] = "L‰hetysaika: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "L‰hetetty net2ftp -ohjelman kautta, joka on asennettu verkkosivustolle: ";
$net2ftp_messages["Webmaster's email: "] = "Yll‰pit‰j‰n (webmasterin) s‰hkˆpostiosoite: ";
$net2ftp_messages["Message of the sender: "] = "L‰hett‰j‰n viesti: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp on ilmainen ohjelmisto, julkaistu GNU/GPL -lisenssin alaisena. Saadaksesi lis‰tietoja, k‰y osoitteessa http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "ZIP-tiedosto on l‰hetetty osoitteeseen <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "Tiedosto <b>%1\$s</b> on liian iso. Tiedostoa ei ladattu palvelimelle.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "Tiedosto <b>%1\$s</b> sis‰lt‰‰ estetyn sanan. T‰t‰ tiedostoa ei ladattu palvelimelle.";
$net2ftp_messages["Could not generate a temporary file."] = "V‰liaikaista tiedostoa ei voida luoda.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "Tiedostoa <b>%1\$s</b> ei voida siirt‰‰";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "Tiedosto <b>%1\$s</b> on kunnossa";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "Ladattua tiedostoa ei voida siirt‰‰ v‰liaikaishakemistoon.<br /><br />Verkkosivuston yll‰pit‰j‰n pit‰‰ <b>asettaa oikeudet 777</b> hakemistolle /temp.";
$net2ftp_messages["You did not provide any file to upload."] = "Et antanut mit‰‰n tiedostoa, jonka voisi ladata palvelimelle.";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "Tiedostoa <b>%1\$s</b> ei voida siirt‰‰ FTP-palvelimelle";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "Tiedosto <b>%1\$s</b> on siirretty FTP-palvelimelle k‰ytt‰en FTP-tilaa <b>%2\$s</b>";
$net2ftp_messages["Transferring files to the FTP server"] = "Siirret‰‰n tiedostoja FTP-palvelimelle";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "K‰sitell‰‰n pakettia numero %1\$s: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "Pakettia <b>%1\$s</b> ei k‰sitelty koska sen tiedostotyyppi‰ ei tunnistettu. Vain zip, tar, tgz ja gz -paketit ovat tuettuja t‰ll‰ hetkell‰.";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Hakemistoja ja tiedostoja ei voida purkaa paketista";
$net2ftp_messages["Archive contains filenames with ../ or ..\\ - aborting the extraction"] = "Paketti sis‰lt‰‰ tiedostoja, joiden nimet sis‰lt‰v‰t ../ tai ..\\ - purkaminen hyl‰t‰‰n";
$net2ftp_messages["Created directory %1\$s"] = "Hakemisto %1\$s luotu";
$net2ftp_messages["Could not create directory %1\$s"] = "Hakemistoa %1\$s ei voida luoda";
$net2ftp_messages["Copied file %1\$s"] = "Tiedosto %1\$s kopioitu";
$net2ftp_messages["Could not copy file %1\$s"] = "Tiedostoa %1\$s ei voida kopioida";
$net2ftp_messages["Unable to delete the temporary directory"] = "V‰liaikaista hakemistoa ei voida poistaa";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "V‰liaikaista tiedostoa %1\$s ei voida poistaa";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "Sivustokomentoa <b>%1\$s</b> ei voida suorittaa";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "Teht‰v‰si lopetettiin";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "Teht‰v‰, jonka halusit suorittaa net2ftp:ll‰, k‰ytti enemm‰n aikaa kuin sallittu %1\$s sekuntia. T‰m‰n takia teht‰v‰ lopetettiin.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "T‰m‰ aikaraja takaa web-palvelimen reilun k‰ytˆn kaikille.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "Yrit‰ pilkkoa teht‰v‰si pienempiin osiin: pienenn‰ tiedostojen valintaasi, ja j‰t‰ suurimmat tiedostot pois.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "Jos todella tarvitset net2ftp:t‰ isojen teht‰vien k‰sittelyyn, jotka vaativat pitk‰n odotusajan, harkitse net2ftp:n asentamista omalle palvelimellesi.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "Et antanut mit‰‰n teksti‰ s‰hkˆpostitse l‰hetett‰v‰ksi!";
$net2ftp_messages["You did not supply a From address."] = "Et antanut kenelt‰-osoitetta.";
$net2ftp_messages["You did not supply a To address."] = "Et antanut kenelle-osoitetta.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "Teknisist‰ ongelmista johtuen s‰hkˆpostia osoitteeseen <b>%1\$s</b> ei voitu l‰hett‰‰.";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "Syˆt‰ k‰ytt‰j‰tunnuksesi ja salasanasi FTP-palvelinta varten ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Et t‰ytt‰nyt kirjautumistietojasi uudessa ikkunassa.<br />Klikkaa allaolevaa \"Siirry kirjautumissivulle\" -linkki‰.";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "net2ftp:n hallintapaneeliin p‰‰sy estettiin, koska tiedostossa settings.inc.php ei ole asetettu salasanaa. Syˆt‰ salasana t‰h‰n tiedostoon, ja p‰ivit‰ t‰m‰ sivu.";
$net2ftp_messages["Please enter your Admin username and password"] = "Syˆt‰ yll‰pit‰j‰n k‰ytt‰j‰tunnus ja salasana"; 
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Et t‰ytt‰nyt kirjautumistietojasi uudessa ikkunassa.<br />Klikkaa allaolevaa \"Siirry kirjautumissivulle\" -linkki‰.";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "V‰‰r‰ k‰ytt‰j‰tunnus tai salasana net2ftp:n hallintapaneeliin. K‰ytt‰j‰tunnus ja salasana voidaan asettaa tiedostossa settings.inc.php.";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "Sininen";
$net2ftp_messages["Grey"] = "Harmaa";
$net2ftp_messages["Black"] = "Black";
$net2ftp_messages["Yellow"] = "Keltainen";
$net2ftp_messages["Pastel"] = "Pastelli";

// getMime()
$net2ftp_messages["Directory"] = "Hakemisto";
$net2ftp_messages["Symlink"] = "Symbolinen linkki";
$net2ftp_messages["ASP script"] = "ASP-komentosarja";
$net2ftp_messages["Cascading Style Sheet"] = "Cascading Style Sheet";
$net2ftp_messages["HTML file"] = "HTML-tiedosto";
$net2ftp_messages["Java source file"] = "Java-l‰hdekooditiedosto";
$net2ftp_messages["JavaScript file"] = "JavaScript-tiedosto";
$net2ftp_messages["PHP Source"] = "PHP-l‰hdekoodi";
$net2ftp_messages["PHP script"] = "PHP-komentosarja";
$net2ftp_messages["Text file"] = "Tekstitiedosto";
$net2ftp_messages["Bitmap file"] = "Bittikarttatiedosto";
$net2ftp_messages["GIF file"] = "GIF-tiedosto";
$net2ftp_messages["JPEG file"] = "JPEG-tiedosto";
$net2ftp_messages["PNG file"] = "PNG-tiedosto";
$net2ftp_messages["TIF file"] = "TIF-tiedosto";
$net2ftp_messages["GIMP file"] = "GIMP-tiedosto";
$net2ftp_messages["Executable"] = "Ohjelmatiedosto";
$net2ftp_messages["Shell script"] = "Shell-komentosarja";
$net2ftp_messages["MS Office - Word document"] = "MS Office - Word-asiakirja";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - Excel-taulukkolaskenta";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - PowerPoint-esitys";
$net2ftp_messages["MS Office - Access database"] = "MS Office - Access-tietokanta";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - Visio-piirros";
$net2ftp_messages["MS Office - Project file"] = "MS Office - Projektitiedosto";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - Writer 6.0-asiakirja";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - Writer 6.0-malli";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - Calc 6.0-taulukkolaskenta";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - Calc 6.0-malli";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - Draw 6.0-asiakirja";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - Draw 6.0-malli";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - Impress 6.0-esitys";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - Impress 6.0-malli";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - Writer 6.0-globaali asiakirja";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - Math 6.0-asiakirja";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - StarWriter 5.x-asiakirja";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - StarWriter 5.x-globaali asiakirja";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - StarCalc 5.x-taulukkolaskenta";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - StarDraw 5.x-asiakirja";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - StarImpress 5.x-esitys";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - StarImpress Packed 5.x-tiedosto";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - StarMath 5.x-asiakirja";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - StarChart 5.x-asiakirja";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - StarMail 5.x-s‰hkˆpostitiedosto";
$net2ftp_messages["Adobe Acrobat document"] = "Adobe Acrobat-asiakirja";
$net2ftp_messages["ARC archive"] = "ARC-paketti";
$net2ftp_messages["ARJ archive"] = "ARJ-paketti";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "GZ-paketti";
$net2ftp_messages["TAR archive"] = "TAR-paketti";
$net2ftp_messages["Zip archive"] = "ZIP-paketti";
$net2ftp_messages["MOV movie file"] = "MOV-elokuvatiedosto";
$net2ftp_messages["MPEG movie file"] = "MPEG-elokuvatiedosto";
$net2ftp_messages["Real movie file"] = "Real-elokuvatiedosto";
$net2ftp_messages["Quicktime movie file"] = "Quicktime-elokuvatiedosto";
$net2ftp_messages["Shockwave flash file"] = "Shockwave Flash -tiedosto";
$net2ftp_messages["Shockwave file"] = "Shockwave-tiedosto";
$net2ftp_messages["WAV sound file"] = "WAV-‰‰nitiedosto";
$net2ftp_messages["Font file"] = "Kirjasintiedosto";
$net2ftp_messages["%1\$s File"] = "%1\$s -tiedosto";
$net2ftp_messages["File"] = "Tiedosto";

// getAction()
$net2ftp_messages["Back"] = "Takaisin";
$net2ftp_messages["Submit"] = "L‰het‰";
$net2ftp_messages["Refresh"] = "P‰ivit‰";
$net2ftp_messages["Details"] = "Tiedot";
$net2ftp_messages["Icons"] = "Kuvakkeet";
$net2ftp_messages["List"] = "Lista";
$net2ftp_messages["Logout"] = "Kirjaudu ulos";
$net2ftp_messages["Help"] = "Ohje";
$net2ftp_messages["Bookmark"] = "Kirjanmerkki";
$net2ftp_messages["Save"] = "Tallenna";
$net2ftp_messages["Default"] = "Oletus";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Ohje";
$net2ftp_messages["Forums"] = "Keskustelualueet";
$net2ftp_messages["License"] = "Lisenssi";
$net2ftp_messages["Powered by"] = "Toteutettu ohjelmistolla";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "Olet siirtym‰ss‰ net2ftp:n keskustelualueelle. N‰it‰ keskustelualueita saa k‰ytt‰‰ vain net2ftp-aiheisiin keskusteluihin - ei yleisiin sivutilakysymyksiin.";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "Yll‰pidon toiminnot";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "Versiotiedot";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "T‰m‰ net2ftp-versio on ajantasainen.";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "Versiotietoja ei voida hakea net2ftp.com:n palvelimelta. Tarkista selaimesi tietoturva-asetukset, jotka saattavat est‰‰ pienen tiedoston lataamisen net2ftp.com -palvelimelta.";
$net2ftp_messages["Logging"] = "Kirjaus";
$net2ftp_messages["Date from:"] = "P‰iv‰ys mist‰:";
$net2ftp_messages["to:"] = "mihin:";
$net2ftp_messages["Empty logs"] = "Tyhjenn‰ lokit";
$net2ftp_messages["View logs"] = "N‰yt‰ lokit";
$net2ftp_messages["Go"] = "Siirry";
$net2ftp_messages["Setup MySQL tables"] = "Asenna MySQL-taulut";
$net2ftp_messages["Create the MySQL database tables"] = "Luo MySQL-tietokantataulut";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "Yll‰pidon toiminnot";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "Tiedostoa %1\$s ei voida avata k‰sitelt‰v‰ksi.";
$net2ftp_messages["The file %1\$s could not be opened."] = "Tiedostoa %1\$s ei voida avata.";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "Tiedoston %1\$s k‰sittely‰ ei voida lopettaa.";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "Yhteytt‰ palvelimelle <b>%1\$s</b> ei voida asettaa. Tarkista syˆtt‰m‰si tietokanta-asetukset.";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "Tietokantaa <b>%1\$s</b> ei voida valita.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "SQL-kysely‰ numero <b>%1\$s</b> ei voida suorittaa.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "SQL-kysely numero <b>%1\$s</b> suoritettiin onnistuneesti.";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "Syˆt‰ MySQL-asetuksesi:";
$net2ftp_messages["MySQL username"] = "MySQL-k‰ytt‰j‰tunnus";
$net2ftp_messages["MySQL password"] = "MySQL-salasana";
$net2ftp_messages["MySQL database"] = "MySQL-tietokanta";
$net2ftp_messages["MySQL server"] = "MySQL-palvelin";
$net2ftp_messages["This SQL query is going to be executed:"] = "T‰m‰ SQL-kysely suoritetaan:";
$net2ftp_messages["Execute"] = "Suorita";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "K‰ytetyt asetukset:";
$net2ftp_messages["MySQL password length"] = "MySQL-salasanan pituus";
$net2ftp_messages["Results:"] = "Tulokset:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "Yll‰pidon toiminnot";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "SQL-kysely‰ <b>%1\$s</b> ei voida suorittaa.";
$net2ftp_messages["No data"] = "Ei tietoja";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "Yll‰pidon toiminnot";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "Taulu <b>%1\$s</b> tyhjennettiin onnistuneesti.";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "Taulua <b>%1\$s</b> ei voitu tyhjent‰‰.";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "Taulu <b>%1\$s</b> optimoitiin onnistuneesti.";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "Taulua <b>%1\$s</b> ei voitu optimoida.";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "Kehittyneet toiminnot";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "Siirry";
$net2ftp_messages["Disabled"] = "Poistettu k‰ytˆst‰";
$net2ftp_messages["Advanced FTP functions"] = "Kehittyneet FTP-toiminnot";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "L‰het‰ omavalintaiset FTP-komennot FTP-komennot";
$net2ftp_messages["This function is available on PHP 5 only"] = "T‰m‰ toiminto on saatavilla vain PHP 5:ss‰";
$net2ftp_messages["Troubleshooting functions"] = "Vianratkaisutoiminnot";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "Etsi vikoja net2ftp:st‰ t‰ll‰ verkkopalvelimella";
$net2ftp_messages["Troubleshoot an FTP server"] = "Etsi vikoja FTP-palvelimesta";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Testaa net2ftp:n listanj‰sennyss‰‰nnˆt";
$net2ftp_messages["Translation functions"] = "K‰‰nnˆstoiminnot";
$net2ftp_messages["Introduction to the translation functions"] = "K‰‰nnˆstoimintojen esittely";
$net2ftp_messages["Extract messages to translate from code files"] = "Pura tekstit k‰‰nnett‰v‰ksi kooditiedostoista";
$net2ftp_messages["Check if there are new or obsolete messages"] = "Tarkista, onko tiedostossa uusia tai vanhentuneita tekstej‰";

$net2ftp_messages["Beta functions"] = "Kokeelliset toiminnot";
$net2ftp_messages["Send a site command to the FTP server"] = "L‰het‰ sivustokomento FTP-palvelimelle";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: salasanasuojaa hakemisto, luo mukautettuja virhesivuja";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: suorita SQL-kysely";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "Sivustokomentotoiminnot eiv‰t ole saatavilla t‰ll‰ web-palvelimella.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "Apache-toiminnot eiv‰t ole saatavilla t‰ll‰ web-palvelimella.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "MySQL-toiminnot eiv‰t ole saatavilla t‰ll‰ web-palvelimella.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "M‰‰ritt‰m‰tˆn state2 -merkkijono. Poistutaan.";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "Etsi vikoja FTP-palvelimesta";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "Yhteysasetukset:";
$net2ftp_messages["FTP server"] = "FTP-palvelin";
$net2ftp_messages["FTP server port"] = "FTP-palvelinportti";
$net2ftp_messages["Username"] = "K‰ytt‰j‰tunnus";
$net2ftp_messages["Password"] = "Salasana";
$net2ftp_messages["Password length"] = "Salasanan pituus";
$net2ftp_messages["Passive mode"] = "Passiivitila";
$net2ftp_messages["Directory"] = "Hakemisto";
$net2ftp_messages["Printing the result"] = "Tulostetaan tulos";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "Yhdistet‰‰n FTP-palvelimelle: ";
$net2ftp_messages["Logging into the FTP server: "] = "Kirjaudutaan sis‰‰n FTP-palvelimelle: ";
$net2ftp_messages["Setting the passive mode: "] = "Asetetaan passiivitilaan: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Haetaan FTP-palvelimen j‰rjestelm‰tyyppi‰: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Vaihdetaan hakemistoksi %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Hakemisto FTP-palvelimelta on: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Haetaan tekstimuotoista listaa tiedostoista ja hakemistoista: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Yritet‰‰n uudelleen tekstimuotoisen listan hakua tiedostoista ja hakemistoista: ";
$net2ftp_messages["Closing the connection: "] = "Suljetaan yhteytt‰: ";
$net2ftp_messages["Raw list of directories and files:"] = "Tekstimuotoinen lista tiedostoista ja hakemistoista:";
$net2ftp_messages["Parsed list of directories and files:"] = "J‰sennetty lista tiedostoista ja hakemistoista:";

$net2ftp_messages["OK"] = "kunnossa";
$net2ftp_messages["not OK"] = "ei kunnossa";

} // end advanced_ftpserver


// -------------------------------------------------------------------------
// Advanced_parsing module
if ($net2ftp_globals["state"] == "advanced_parsing") {
// -------------------------------------------------------------------------

$net2ftp_messages["Test the net2ftp list parsing rules"] = "Testaa net2ftp:n listanj‰sennyss‰‰nnˆt";
$net2ftp_messages["Sample input"] = "Esimerkkisyˆttˆ";
$net2ftp_messages["Parsed output"] = "J‰sennetty ulostulo";

} // end advanced_parsing


// -------------------------------------------------------------------------
// Advanced_webserver module
if ($net2ftp_globals["state"] == "advanced_webserver") {
// -------------------------------------------------------------------------

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "Etsi vikoja net2ftp-ohjelmistostasi";
$net2ftp_messages["Printing the result"] = "Tulostetaan tulos";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "Tarkistetaan PHP:n FTP-moduulin asennusta: ";
$net2ftp_messages["yes"] = "Asennettu";
$net2ftp_messages["no - please install it!"] = "Ei asennettu - asenna se!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "Haetaan hakemiston oikeuksia: pieni tiedosto luodaan /temp -kansioon. Se poistetaan prosessin j‰lkeen.";
$net2ftp_messages["Creating filename: "] = "Luodaan tiedostoa: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "Kunnossa. Tiedostonimi: %1\$s";
$net2ftp_messages["not OK"] = "ei kunnossa";
$net2ftp_messages["OK"] = "kunnossa";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "Ei kunnossa. Tarkista hakemiston %1\$s oikeudet.";
$net2ftp_messages["Opening the file in write mode: "] = "Avataan tiedosto kirjoitustilassa: ";
$net2ftp_messages["Writing some text to the file: "] = "Kirjoitetaan teksti‰ tiedostoon: ";
$net2ftp_messages["Closing the file: "] = "Suljetaan tiedosto: ";
$net2ftp_messages["Deleting the file: "] = "Poistetaan tiedosto: ";

$net2ftp_messages["Testing the FTP functions"] = "Testataan FTP-toimintoja";
$net2ftp_messages["Connecting to a test FTP server: "] = "Yhdistet‰‰n testi-FTP-palvelimelle: ";
$net2ftp_messages["Connecting to the FTP server: "] = "Yhdistet‰‰n FTP-palvelimelle: ";
$net2ftp_messages["Logging into the FTP server: "] = "Kirjaudutaan sis‰‰n FTP-palvelimelle: ";
$net2ftp_messages["Setting the passive mode: "] = "Asetetaan passiivitilaan: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Haetaan FTP-palvelimen j‰rjestelm‰tyyppi‰: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Vaihdetaan hakemistoksi %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Hakemisto FTP-palvelimelta on: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Haetaan tekstimuotoista listaa tiedostoista ja hakemistoista: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Yritet‰‰n uudelleen tekstimuotoisen listan hakua tiedostoista ja hakemistoista: ";
$net2ftp_messages["Closing the connection: "] = "Suljetaan yhteytt‰: ";
$net2ftp_messages["Raw list of directories and files:"] = "Tekstimuotoinen lista tiedostoista ja hakemistoista:";
$net2ftp_messages["Parsed list of directories and files:"] = "J‰sennetty lista tiedostoista ja hakemistoista:";
$net2ftp_messages["OK"] = "kunnossa";
$net2ftp_messages["not OK"] = "ei kunnossa";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "Lis‰‰ t‰m‰ linkki kirjanmerkkeihisi:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: klikkaa linkki‰ hiiren oikealla n‰pp‰imell‰ ja valitse \"Lis‰‰ Suosikit-kansioon...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla ja Firefox: klikkaa linkki‰ hiiren oikealla n‰pp‰imell‰ ja valitse \"Lis‰‰ kohde kirjanmerkkeihin\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "Huomautus: kun k‰yt‰t kirjanmerkki‰, uusi ikkuna kysyy k‰ytt‰j‰tunnustasi ja salasanaasi.";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "Valitse hakemisto";
$net2ftp_messages["Please wait..."] = "Odota...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "Hakemistoja, joiden nimet sis‰lt‰v‰t merkin \', ei voida n‰ytt‰‰ oikein. Ne voidaan vain poistaa. Palaa takaisin ja valitse toinen alihakemisto.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "P‰ivitt‰inen raja saavutettu: et voi siirt‰‰ tietoja";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "Jotta voisimme taata reilun web-palvelink‰ytˆn jokaiselle, tiedostonsiirto- ja skriptin suoritusaikamaksimi on rajoitettu jokaiselle k‰ytt‰j‰lle per p‰iv‰. Kun t‰m‰ raja on saavutettu, voit edelleen selata FTP-palvelinta, mutta et voi siirt‰‰ tietoja pois sielt‰/sinne.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "Jos tarvitset rajoittamattoman k‰ytˆn, asenna net2ftp omalle web-palvelimellesi.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "Uusi hak.";
$net2ftp_messages["New file"] = "Uusi tied.";
$net2ftp_messages["HTML templates"] = "HTML-mallit";
$net2ftp_messages["Upload"] = "Lataa palvelimelle";
$net2ftp_messages["Java Upload"] = "Java-lataus";
$net2ftp_messages["Flash Upload"] = "Flash-lataus";
$net2ftp_messages["Install"] = "Asenna";
$net2ftp_messages["Advanced"] = "Kehittynyt";
$net2ftp_messages["Copy"] = "Kopioi";
$net2ftp_messages["Move"] = "Siirr‰";
$net2ftp_messages["Delete"] = "Poista";
$net2ftp_messages["Rename"] = "Nime‰ uud.";
$net2ftp_messages["Chmod"] = "Aseta oik.";
$net2ftp_messages["Download"] = "Lataa";
$net2ftp_messages["Unzip"] = "Pura";
$net2ftp_messages["Zip"] = "Pakkaa";
$net2ftp_messages["Size"] = "Koko";
$net2ftp_messages["Search"] = "Haku";
$net2ftp_messages["Go to the parent directory"] = "Siirry ylemp‰‰n hakemistoon";
$net2ftp_messages["Go"] = "Siirry";
$net2ftp_messages["Transform selected entries: "] = "Muuta valitut kohteet: ";
$net2ftp_messages["Transform selected entry: "] = "Muuta valittu kohde: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "Luo uusi alihakemisto hakemistoon %1\$s";
$net2ftp_messages["Create a new file in directory %1\$s"] = "Tee uusi tiedosto hakemistoon %1\$s";
$net2ftp_messages["Create a website easily using ready-made templates"] = "Tee verkkosivusto helposti k‰ytt‰en valmiita malleja";
$net2ftp_messages["Upload new files in directory %1\$s"] = "Lataa uudet tiedostot hakemistoon %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Lataa tiedostot ja hakemistot k‰ytt‰en Java-sovelmaa";
$net2ftp_messages["Upload files using a Flash applet"] = "Lataa tiedostot k‰ytt‰en Flash-sovelmaa";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Asenna ohjelmistopaketteja (vaatii PHP:n web-palvelimelta)";
$net2ftp_messages["Go to the advanced functions"] = "Siirry kehittyneisiin toimintoihin";
$net2ftp_messages["Copy the selected entries"] = "Kopioi valitut kohteet";
$net2ftp_messages["Move the selected entries"] = "Siirr‰ valitut kohteet";
$net2ftp_messages["Delete the selected entries"] = "Poista valitut kohteet";
$net2ftp_messages["Rename the selected entries"] = "Nime‰ valitut kohteet uudelleen";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "Aseta oikeudet valituille kohteille (toimii vain Unix/Linux/BSD-palvelimilla)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "Lataa ZIP, joka sis‰lt‰‰ kaikki valitut kohteet";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Pura valitut paketit FTP-palvelimelle";
$net2ftp_messages["Zip the selected entries to save or email them"] = "Pakkaa valitut kohteet tallentaaksesi ne tai l‰hett‰‰ksesi ne s‰hkˆpostilla";
$net2ftp_messages["Calculate the size of the selected entries"] = "Laske valittujen kohteiden koko";
$net2ftp_messages["Find files which contain a particular word"] = "Etsi tiedostot, jotka sis‰lt‰v‰t tietyn sanan";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "Klikkaa j‰rjest‰‰ksesi kohteen %1\$s mukaan laskevassa j‰rjestyksess‰";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "Klikkaa j‰rjest‰‰ksesi kohteen %1\$s mukaan nousevassa j‰rjestyksess‰";
$net2ftp_messages["Ascending order"] = "Nouseva j‰rjestys";
$net2ftp_messages["Descending order"] = "Laskeva j‰rjestys";
$net2ftp_messages["Upload files"] = "Lataa tiedostoja palvelimelle";
$net2ftp_messages["Up"] = "Ylˆs";
$net2ftp_messages["Click to check or uncheck all rows"] = "Klikkaa valitaksesi kaikki rivit tai poistaaksesi kaikkien rivien valinnat";
$net2ftp_messages["All"] = "Kaikki";
$net2ftp_messages["Name"] = "Nimi";
$net2ftp_messages["Type"] = "Tyyppi";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "Omistaja";
$net2ftp_messages["Group"] = "Ryhm‰";
$net2ftp_messages["Perms"] = "Oik.";
$net2ftp_messages["Mod Time"] = "Muut. aika";
$net2ftp_messages["Actions"] = "Toiminnot";
$net2ftp_messages["Select the directory %1\$s"] = "Valitse hakemisto %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "Valitse tiedosto %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "Valitse symbolinen linkki %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "Siirry alihakemistoon %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "Lataa tiedosto %1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "Seuraa symbolista linkki‰ %1\$s";
$net2ftp_messages["View"] = "N‰yt‰";
$net2ftp_messages["Edit"] = "Muokkaa";
$net2ftp_messages["Update"] = "P‰ivit‰";
$net2ftp_messages["Open"] = "Avaa";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "N‰yt‰ tiedoston %1\$s v‰ritetty l‰hdekoodi";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Muokkaa tiedoston %1\$s l‰hdekoodia";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "Lataa uusi versio tiedostosta %1\$s ja yhdenn‰ muutokset";
$net2ftp_messages["View image %1\$s"] = "N‰yt‰ kuva %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "N‰yt‰ tiedosto %1\$s HTTP-web-palvelimeltasi";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(Huomautus: T‰m‰ linkki ei v‰ltt‰m‰tt‰ toimi jos sinulla ei ole omaa verkkotunnusta.)";
$net2ftp_messages["This folder is empty"] = "T‰m‰ hakemisto on tyhj‰";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "Hakemistot";
$net2ftp_messages["Files"] = "Tiedostot";
$net2ftp_messages["Symlinks"] = "Symboliset linkit";
$net2ftp_messages["Unrecognized FTP output"] = "Tunnistamaton FTP-ulostulo";
$net2ftp_messages["Number"] = "Numero";
$net2ftp_messages["Size"] = "Koko";
$net2ftp_messages["Skipped"] = "Ohitettu";
$net2ftp_messages["Data transferred from this IP address today"] = "T‰ll‰ IP-osoitteella on siirretty tietoja t‰n‰‰n";
$net2ftp_messages["Data transferred to this FTP server today"] = "T‰lle FTP-palvelimelle on siirretty tietoja t‰n‰‰n";

// printLocationActions()
$net2ftp_messages["Language:"] = "Kieli:";
$net2ftp_messages["Skin:"] = "Ulkoasu:";
$net2ftp_messages["View mode:"] = "N‰yttˆtila:";
$net2ftp_messages["Directory Tree"] = "Hakemistopuu";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "Suorita %1\$s uudessa ikkunassa";
$net2ftp_messages["This file is not accessible from the web"] = "T‰t‰ tiedostoa ei voi k‰ytt‰‰ verkosta";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "Kaksoisklikkaa siirty‰ksesi alihakemistoon:";
$net2ftp_messages["Choose"] = "Valitse";
$net2ftp_messages["Up"] = "Ylˆs";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "Valittujen tiedostojen ja hakemistojen koko";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "Valittujen tiedostojen ja hakemistojen yhteiskoko on:";
$net2ftp_messages["The number of files which were skipped is:"] = "Ohitettuja tiedostoja:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "Anna oikeuksia tiedostoille ja hakemistoille";
$net2ftp_messages["Set all permissions"] = "Aseta kaikki oikeudet";
$net2ftp_messages["Read"] = "Luku";
$net2ftp_messages["Write"] = "Kirjoitus";
$net2ftp_messages["Execute"] = "Suorita";
$net2ftp_messages["Owner"] = "Omistaja";
$net2ftp_messages["Group"] = "Ryhm‰";
$net2ftp_messages["Everyone"] = "Jokainen";
$net2ftp_messages["To set all permissions to the same values, enter those permissions and click on the button \"Set all permissions\""] = "To set all permissions to the same values, enter those permissions and click on the button \"Set all permissions\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "Aseta hakemiston <b>%1\$s</b> oikeudet: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "Aseta tiedoston <b>%1\$s</b> oikeudet: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "Aseta symbolisen linkin <b>%1\$s</b> oikeudet: ";
$net2ftp_messages["Chmod value"] = "Oikeusarvo";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "Anna oikeudet myˆs hakemiston alihakemistoille";
$net2ftp_messages["Chmod also the files within this directory"] = "Anna oikeudet myˆs hakemiston tiedoille";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "Oikeusnumero <b>%1\$s</b> on arvojen 000-777 ulkopuolella. Yrit‰ uudelleen.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "Valitse hakemisto";
$net2ftp_messages["Copy directories and files"] = "Kopioi tiedostoja ja hakemistoja";
$net2ftp_messages["Move directories and files"] = "Siirr‰ tiedostoja ja hakemistoja";
$net2ftp_messages["Delete directories and files"] = "Poista tiedostoja ja hakemistoja";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "Oletko varma, ett‰ haluat poistaa n‰m‰ tiedostot ja hakemistot?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "Valittujen hakemistojen tiedostot ja alihakemistot poistetaan myˆs!";
$net2ftp_messages["Set all targetdirectories"] = "Aseta kaikki kohdehakemistot";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Asettaaksesi yleisen kohdehakemiston, syˆt‰ kohdehakemisto yll‰olevaan tekstikentt‰‰n ja klikkaa \"Aseta kaikki kohdehakemistot\" -painiketta.";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Huomautus: kohdehakemisto pit‰‰ olla olemassa ennen kuin mit‰‰n voidaan kopioida siihen.";
$net2ftp_messages["Different target FTP server:"] = "Toinen kohde-FTP-palvelin:";
$net2ftp_messages["Username"] = "K‰ytt‰j‰tunnus";
$net2ftp_messages["Password"] = "Salasana";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "J‰t‰ tyhj‰ksi, jos haluat kopioida tiedostot samalle FTP-palvelimelle.";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "Jos haluat kopioida tiedostot toiselle FTP-palvelimelle, syˆt‰ kirjautumistietosi.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "J‰t‰ tyhj‰ksi, jos haluat siirt‰‰ tiedostot samalle FTP-palvelimelle.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "Jos haluat siirt‰‰ tiedostot toiselle FTP-palvelimelle, syˆt‰ kirjautumistietosi.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "Kopioi hakemisto <b>%1\$s</b> kohteeseen:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "Siirr‰ hakemisto <b>%1\$s</b> kohteeseen:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "Hakemisto <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "Kopioi tiedosto <b>%1\$s</b> kohteeseen:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "Siirr‰ tiedosto <b>%1\$s</b> kohteeseen:";
$net2ftp_messages["File <b>%1\$s</b>"] = "Tiedosto <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "Kopioi symbolinen linkki <b>%1\$s</b> kohteeseen:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "Siirr‰ symbolinen linkki <b>%1\$s</b> kohteeseen:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Symbolinen linkki <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "Kohdehakemisto:";
$net2ftp_messages["Target name:"] = "Kohdenimi:";
$net2ftp_messages["Processing the entries:"] = "K‰sitell‰‰n kohteita:";

} // end copymovedelete


// -------------------------------------------------------------------------
// Download file module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// EasyWebsite module
if ($net2ftp_globals["state"] == "easyWebsite") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create a website in 4 easy steps"] = "Luo verkkosivusto nelj‰n helpon vaiheen avulla";
$net2ftp_messages["Template overview"] = "Ulkoasun yleiskatsaus";
$net2ftp_messages["Template details"] = "Ulkoasun tiedot";
$net2ftp_messages["Files are copied"] = "Tiedostot on kopioitu";
$net2ftp_messages["Edit your pages"] = "Muokkaa sivustoasi";

// Screen 1 - printTemplateOverview
$net2ftp_messages["Click on the image to view the details of a template."] = "Klikkaa kuvaa n‰hd‰ksesi ulkoasun tiedot.";
$net2ftp_messages["Back to the Browse screen"] = "Palaa takaisin Selaa -ruutuun";
$net2ftp_messages["Template"] = "Ulkoasu";
$net2ftp_messages["Copyright"] = "Tekij‰noikeudet";
$net2ftp_messages["Click on the image to view the details of this template"] = "Klikkaa kuvaa n‰hd‰ksesi t‰m‰n ulkoasun tiedot";

// Screen 2 - printTemplateDetails
$net2ftp_messages["The template files will be copied to your FTP server. Existing files with the same filename will be overwritten. Do you want to continue?"] = "Ulkoasutiedostot kopioidaan FTP-palvelimellesi. Jos FTP-palvelimellasi on samannimisi‰ tiedostoja, mit‰ ulkoasussa, FTP-palvelimellasi olevat tiedostot p‰‰llekirjoitetaan. Haluatko jatkaa?";
$net2ftp_messages["Install template to directory: "] = "Asenna ulkoasu hakemistoon: ";
$net2ftp_messages["Install"] = "Asenna";
$net2ftp_messages["Size"] = "Koko";
$net2ftp_messages["Preview page"] = "Esikatselusivu";
$net2ftp_messages["opens in a new window"] = "Aukeaa uuteen ikkunaan";

// Screen 3
$net2ftp_messages["Please wait while the template files are being transferred to your server: "] = "Odota. Ulkoasutiedostoja siirret‰‰n palvelimellesi: ";
$net2ftp_messages["Done."] = "Valmis.";
$net2ftp_messages["Continue"] = "Jatka";

// Screen 4 - printEasyAdminPanel
$net2ftp_messages["Edit page"] = "Muokkaa sivustoa";
$net2ftp_messages["Browse the FTP server"] = "Selaa FTP-palvelinta";
$net2ftp_messages["Add this link to your favorites to return to this page later on!"] = "Lis‰‰ t‰m‰ linkki kirjanmerkkeihisi palataksesi myˆhemmin takaisin!";
$net2ftp_messages["Edit website at %1\$s"] = "Muokkaa verkkosivustoa palvelimella %1\$s";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: klikkaa linkki‰ hiiren oikealla n‰pp‰imell‰ ja valitse \"Lis‰‰ Suosikit-kansioon...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla ja Firefox: klikkaa linkki‰ hiiren oikealla n‰pp‰imell‰ ja valitse \"Lis‰‰ kohde kirjanmerkkeihin\"";

// ftp_copy_local2ftp
$net2ftp_messages["WARNING: Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing..."] = "VAROITUS: Alihakemistoa <b>%1\$s</b> ei voida luoda. Se on ehk‰ jo olemassa. Jatketaan...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Kohde-alihakemisto <b>%1\$s</b> luotu";
$net2ftp_messages["WARNING: Unable to copy the file <b>%1\$s</b>. Continuing..."] = "VAROITUS: Tiedostoa <b>%1\$s</b> ei voida kopioida. Jatketaan...";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Tiedosto <b>%1\$s</b> kopioitu";
}


// -------------------------------------------------------------------------
// Edit module
if ($net2ftp_globals["state"] == "edit") {
// -------------------------------------------------------------------------

// /modules/edit/edit.inc.php
$net2ftp_messages["Unable to open the template file"] = "Ulkoasutiedostoa ei voida avata";
$net2ftp_messages["Unable to read the template file"] = "Ulkoasutiedostoa ei voida lukea";
$net2ftp_messages["Please specify a filename"] = "Anna tiedostonimi";
$net2ftp_messages["Status: This file has not yet been saved"] = "Tila: T‰t‰ tiedostoa ei ole viel‰ tallennettu";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "Tila: Tallennettu <b>%1\$s</b> k‰ytt‰en tilaa %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "Tila: <b>T‰t‰ tiedostoa ei voitu tallentaa</b>";
$net2ftp_messages["Not yet saved"] = "Not yet saved";
$net2ftp_messages["Could not be saved"] = "Could not be saved";
$net2ftp_messages["Saved at %1\$s"] = "Saved at %1\$s";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "Hakemisto: ";
$net2ftp_messages["File: "] = "Tiedosto: ";
$net2ftp_messages["New file name: "] = "Tiedoston uusi nimi: ";
$net2ftp_messages["Character encoding: "] = "Merkistˆ: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "Huomautus: tekstialueen tyypin vaihtaminen tallentaa muutokset";
$net2ftp_messages["Copy up"] = "Kopioi ylˆs";
$net2ftp_messages["Copy down"] = "Kopioi alas";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "Etsi hakemistoja ja tiedostoja";
$net2ftp_messages["Search again"] = "Etsi uudelleen";
$net2ftp_messages["Search results"] = "Haun tulokset";
$net2ftp_messages["Please enter a valid search word or phrase."] = "Anna kelvollinen hakusana tai -lause.";
$net2ftp_messages["Please enter a valid filename."] = "Anna kelvollinen tiedostonimi.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "Syˆt‰ kelvollinen tiedostokoko \"mist‰\" -tekstikentt‰‰n, esimerkiksi 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "Syˆt‰ kelvollinen tiedostokoko \"mihin\" -tekstikentt‰‰n, esimerkiksi 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "Syˆt‰ kelvollinen p‰iv‰m‰‰r‰ muodossa VVVV-KK-PP \"mist‰\" -tekstikentt‰‰n.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "Syˆt‰ kelvollinen p‰iv‰m‰‰r‰ muodossa VVVV-KK-PP \"mihin\" -tekstikentt‰‰n.";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "Sanaa <b>%1\$s</b> ei lˆytynyt valituista tiedostoista tai hakemistoista.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "Sana <b>%1\$s</b> lˆytyi seuraavista tiedostoista:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "Etsi sanaa tai lausetta";
$net2ftp_messages["Case sensitive search"] = "Sama kirjainkoko";
$net2ftp_messages["Restrict the search to:"] = "Rajoita haku:";
$net2ftp_messages["files with a filename like"] = "tiedostoihin, joiden nimi on";
$net2ftp_messages["(wildcard character is *)"] = "(jokerimerkki on *)";
$net2ftp_messages["files with a size"] = "tiedostoihin, joiden koko on";
$net2ftp_messages["files which were last modified"] = "tiedostoihin, joita on viimeksi muutettu";
$net2ftp_messages["from"] = "mist‰";
$net2ftp_messages["to"] = "mihin";

$net2ftp_messages["Directory"] = "Hakemisto";
$net2ftp_messages["File"] = "Tiedosto";
$net2ftp_messages["Line"] = "Rivi";
$net2ftp_messages["Action"] = "Toiminnot";
$net2ftp_messages["View"] = "N‰yt‰";
$net2ftp_messages["Edit"] = "Muokkaa";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "N‰yt‰ tiedoston %1\$s v‰ritetty l‰hdekoodi";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Muokkaa tiedoston %1\$s l‰hdekoodia";

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
$net2ftp_messages["Install software packages"] = "Asenna ohjelmistopaketteja";
$net2ftp_messages["Unable to open the template file"] = "Ulkoasutiedostoa ei voida avata";
$net2ftp_messages["Unable to read the template file"] = "Ulkoasutiedostoa ei voida lukea";
$net2ftp_messages["Unable to get the list of packages"] = "Pakettien listaa ei voida hakea";

// /skins/blue/install1.template.php
$net2ftp_messages["The net2ftp installer script has been copied to the FTP server."] = "net2ftp -asennusskripti on kopioitu FTP-palvelimelle.";
$net2ftp_messages["This script runs on your web server and requires PHP to be installed."] = "T‰m‰ skripti suoritetaan omalla web-palvelimellasi ja sen asentaminen vaatii PHP:n.";
$net2ftp_messages["In order to run it, click on the link below."] = "Jos haluat suorittaa sen, klikkaa allaolevaa linkki‰.";
$net2ftp_messages["net2ftp has tried to determine the directory mapping between the FTP server and the web server."] = "net2ftp yritti selvitt‰‰ hakemistokartoitusta FTP-palvelimen ja web-palvelimen v‰lill‰.";
$net2ftp_messages["Should this link not be correct, enter the URL manually in your web browser."] = "Jos t‰m‰ linkki ei ole oikein, syˆt‰ URL-osoite manuaalisesti web-selaimeesi.";

} // end install


// -------------------------------------------------------------------------
// Java upload module
if ($net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload directories and files using a Java applet"] = "Lataa tiedostot ja hakemistot k‰ytt‰en Java-sovelmaa";
$net2ftp_messages["Number of files:"] = "Tiedostoja yhteens‰:";
$net2ftp_messages["Size of files:"] = "Tiedostojen koko yhteens‰:";
$net2ftp_messages["Add"] = "Lis‰‰";
$net2ftp_messages["Remove"] = "Poista";
$net2ftp_messages["Upload"] = "Lataa palvelimelle";
$net2ftp_messages["Add files to the upload queue"] = "Lis‰‰ tiedostoja latausjonoon";
$net2ftp_messages["Remove files from the upload queue"] = "Poista tiedostoja latausjonosta";
$net2ftp_messages["Upload the files which are in the upload queue"] = "Lataa tiedostot, jotka ovat latausjonossa, palvelimelle";
$net2ftp_messages["Maximum server space exceeded. Please select less/smaller files."] = "Palvelimen maksimitila ylitettiin. Valitse v‰hemm‰n tiedostoja tai pienempi‰ tiedostoja.";
$net2ftp_messages["Total size of the files is too big. Please select less/smaller files."] = "Tiedostojen yhteiskoko on liian iso. Valitse v‰hemm‰n tiedostoja tai pienempi‰ tiedostoja.";
$net2ftp_messages["Total number of files is too high. Please select fewer files."] = "Tiedostoja on liikaa. Valitse v‰hemm‰n tiedostoja.";
$net2ftp_messages["Note: to use this applet, Sun's Java plugin must be installed (version 1.4 or newer)."] = "Huomautus: k‰ytt‰‰ksesi t‰t‰ sovelmaa, Sunin Java -lis‰osa pit‰‰ olla asennettuna (versio 1.4 tai uudempi).";

} // end jupload



// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login") {
// -------------------------------------------------------------------------
$net2ftp_messages["Login!"] = "Kirjaudu sis‰‰n!";
$net2ftp_messages["Once you are logged in, you will be able to:"] = "Kun olet kirjautunut sis‰‰n, voit:";
$net2ftp_messages["Navigate the FTP server"] = "Liikkua FTP-palvelimella";
$net2ftp_messages["Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."] = "Kun olet kirjautunut sis‰‰n, voit siirty‰ hakemistosta hakemistoon ja n‰hd‰ kaikki tiedostot ja alihakemistot.";
$net2ftp_messages["Upload files"] = "Lataa tiedostoja palvelimelle";
$net2ftp_messages["There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet."] = "Sinulla on kolme eri lataustapaa: tavallinen latauslomake, lataa-ja-pura -toiminto ja Java-sovelma.";
$net2ftp_messages["Download files"] = "Ladata tiedostoja";
$net2ftp_messages["Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive."] = "Klikkaa tiedostonime‰ ladataksesi yhden tiedoston nopeasti.<br />Valitse useita tiedostoja ja klikkaa Lataa; voit ladata valitsemasi tiedostot ZIP-paketissa.";
$net2ftp_messages["Zip files"] = "ZIP-tiedostot";
$net2ftp_messages["... and save the zip archive on the FTP server, or email it to someone."] = "... ja s‰‰st‰ ZIP-paketti FTP-palvelimella tai l‰het‰ se jollekulle.";
$net2ftp_messages["Unzip files"] = "Pura tiedostoja";
$net2ftp_messages["Different formats are supported: .zip, .tar, .tgz and .gz."] = "Useat muodot ovat tuettuja: .zip, .tar, .tgz ja .gz.";
$net2ftp_messages["Install software"] = "Asenna ohjelmistoja";
$net2ftp_messages["Choose from a list of popular applications (PHP required)."] = "Valitse yleisten ohjelmistojen listalta (PHP vaaditaan).";
$net2ftp_messages["Copy, move and delete"] = "Kopioi, siirr‰ ja poista";
$net2ftp_messages["Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted."] = "Hakemistot k‰sitell‰‰n rekursiivisesti, joka tarkoittaa, ett‰ myˆs niiden sis‰llˆt (alihakemistot ja tiedostot) kopioidaan, siirret‰‰n ja poistetaan.";
$net2ftp_messages["Copy or move to a 2nd FTP server"] = "Kopioi tai siirr‰ toiselle FTP-palvelimelle";
$net2ftp_messages["Handy to import files to your FTP server, or to export files from your FTP server to another FTP server."] = "Tuo tiedostoja k‰tev‰sti FTP-palvelimellesi, tai vie tiedostoja FTP-palvelimeltasi toiselle FTP-palvelimelle.";
$net2ftp_messages["Rename and chmod"] = "Nime‰ uudelleen ja aseta oikeuksia";
$net2ftp_messages["Chmod handles directories recursively."] = "Oikeuksien asetus k‰sittelee hakemistot rekursiivisesti.";
$net2ftp_messages["View code with syntax highlighting"] = "N‰yt‰ koodia syntaksin v‰rityksell‰";
$net2ftp_messages["PHP functions are linked to the documentation on php.net."] = "PHP-funktiot linkitet‰‰n php.netin dokumentaatioon.";
$net2ftp_messages["Plain text editor"] = "Pelk‰n tekstin muokkain";
$net2ftp_messages["Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server."] = "Muokkaa teksti‰ suoraan selaimestasi; joka kerta, kun tallennat muutokset, uusi tiedosto siirret‰‰n FTP-palvelimelle.";
$net2ftp_messages["HTML editors"] = "HTML-muokkaimet";
$net2ftp_messages["Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from."] = "Muokkaa HTML:‰‰ What-You-See-Is-What-You-Get (WYSIWYG) (mit‰ n‰et, sit‰ saat) -lomakkeella; voit valita kahdesta eri muokkaimesta.";
$net2ftp_messages["Code editor"] = "Koodin muokkain";
$net2ftp_messages["Edit HTML and PHP in an editor with syntax highlighting."] = "Muokkaa HTML:‰‰ ja PHP:t‰ syntaksin v‰rikoodauksella varustetulla muokkaimella.";
$net2ftp_messages["Search for words or phrases"] = "Etsi sanoja tai lauseita";
$net2ftp_messages["Filter out files based on the filename, last modification time and filesize."] = "Erottele tiedostoja tiedostonimeen, viimeisimp‰‰n muutosaikaan ja tiedostokokoon perustuen.";
$net2ftp_messages["Calculate size"] = "Laske koko";
$net2ftp_messages["Calculate the size of directories and files."] = "Laske hakemistojen ja tiedostojen koko.";

$net2ftp_messages["FTP server"] = "FTP-palvelin";
$net2ftp_messages["Example"] = "Esimerkki";
$net2ftp_messages["Port"] = "Portti";
$net2ftp_messages["Username"] = "K‰ytt‰j‰tunnus";
$net2ftp_messages["Password"] = "Salasana";
$net2ftp_messages["Anonymous"] = "Nimetˆn (anonyymi)";
$net2ftp_messages["Passive mode"] = "Passiivitila";
$net2ftp_messages["Initial directory"] = "Oletushakemisto";
$net2ftp_messages["Language"] = "Kieli";
$net2ftp_messages["Skin"] = "Ulkoasu";
$net2ftp_messages["FTP mode"] = "FTP-tila";
$net2ftp_messages["Automatic"] = "Automaattinen";
$net2ftp_messages["Login"] = "Kirjaudu sis‰‰n";
$net2ftp_messages["Clear cookies"] = "Tyhjenn‰ ev‰steet";
$net2ftp_messages["Admin"] = "Yll‰pito";
$net2ftp_messages["Please enter an FTP server."] = "Syˆt‰ FTP-palvelin.";
$net2ftp_messages["Please enter a username."] = "Syˆt‰ k‰ytt‰j‰tunnus.";
$net2ftp_messages["Please enter a password."] = "Syˆt‰ salasana.";

} // end login


// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login_small") {
// -------------------------------------------------------------------------

$net2ftp_messages["Please enter your Administrator username and password."] = "Syˆt‰ yll‰pit‰j‰n k‰ytt‰j‰tunnus ja salasana.";
$net2ftp_messages["Please enter your username and password for FTP server <b>%1\$s</b>."] = "Syˆt‰ k‰ytt‰j‰tunnus ja salasana FTP-palvelinta <b>%1\$s</b> varten.";
$net2ftp_messages["Username"] = "K‰ytt‰j‰tunnus";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Istuntosi on vanhentunut; syˆt‰ salasanasi FTP-palvelimelle <b>%1\$s</b> jatkaaksesi.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "IP-osoitteesi on muuttunut; syˆt‰ salasanasi FTP-palvelimelle <b>%1\$s</b> jatkaaksesi.";
$net2ftp_messages["Password"] = "Salasana";
$net2ftp_messages["Login"] = "Kirjaudu sis‰‰n";
$net2ftp_messages["Continue"] = "Jatka";

} // end login_small


// -------------------------------------------------------------------------
// Logout module
if ($net2ftp_globals["state"] == "logout") {
// -------------------------------------------------------------------------

// logout.inc.php
$net2ftp_messages["Login page"] = "Kirjautumissivu";

// logout.template.php
$net2ftp_messages["You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>."] = "Kirjauduit ulos FTP-palvelimelta. Kirjautuaksesi takaisin sis‰‰n, <a href=\"%1\$s\" title=\"Kirjautumissivu (pikan‰pp‰in l)\" accesskey=\"l\">klikkaa t‰st‰</a>.";
$net2ftp_messages["Note: other users of this computer could click on the browser's Back button and access the FTP server."] = "Huomautus: t‰m‰n tietokoneen muut k‰ytt‰j‰t voivat klikata selaimen Edellinen -painiketta ja k‰ytt‰‰ FTP-palvelinta.";
$net2ftp_messages["To prevent this, you must close all browser windows."] = "Est‰‰ksesi t‰m‰n, sinun t‰ytyy sulkea kaikki selainikkunat.";
$net2ftp_messages["Close"] = "Sulje";
$net2ftp_messages["Click here to close this window"] = "Klikkaa t‰st‰ sulkeaksesi t‰m‰n ikkunan";

} // end logout


// -------------------------------------------------------------------------
// New directory module
if ($net2ftp_globals["state"] == "newdir") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create new directories"] = "Luo uusia hakemistoja";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "Uudet hakemistot luodaan kohteeseen <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "Uuden hakemiston nimi:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "Hakemisto <b>%1\$s</b> luotiin onnistuneesti.";
$net2ftp_messages["Directory <b>%1\$s</b> could not be created."] = "Hakemistoa <b>%1\$s</b> ei voitu luoda.";

} // end newdir


// -------------------------------------------------------------------------
// Raw module
if ($net2ftp_globals["state"] == "raw") {
// -------------------------------------------------------------------------

// /modules/raw/raw.inc.php
$net2ftp_messages["Send arbitrary FTP commands"] = "L‰het‰ omavalintaisia FTP-komentoja";


// /skins/[skin]/raw1.template.php
$net2ftp_messages["List of commands:"] = "Lista komennoista:";
$net2ftp_messages["FTP server response:"] = "FTP-palvelimen vastaus:";

} // end raw


// -------------------------------------------------------------------------
// Rename module
if ($net2ftp_globals["state"] == "rename") {
// -------------------------------------------------------------------------
$net2ftp_messages["Rename directories and files"] = "Nime‰ tiedostoja ja hakemistoja uudelleen";
$net2ftp_messages["Old name: "] = "Vanha nimi: ";
$net2ftp_messages["New name: "] = "Uusi nimi: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "Uusi nimi ei saa sis‰lt‰‰ pisteit‰. T‰t‰ kohdetta ei nimetty nimell‰ <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "Uusi nimi ei saa sis‰lt‰‰ estettyj‰ sanoja. T‰t‰ kohdetta ei nimetty nimell‰ <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> nimettiin uudella nimell‰ <b>%2\$s</b>";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> ei voitu nimet‰ uudella nimell‰ <b>%2\$s</b>";

} // end rename


// -------------------------------------------------------------------------
// Unzip module
if ($net2ftp_globals["state"] == "unzip") {
// -------------------------------------------------------------------------

// /modules/unzip/unzip.inc.php
$net2ftp_messages["Unzip archives"] = "Pura paketteja";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Haetaan pakettia %1\$s/%2\$s FTP-palvelimelta";
$net2ftp_messages["Unable to get the archive <b>%1\$s</b> from the FTP server"] = "Pakettia <b>%1\$s</b> ei voitu hakea FTP-palvelimelta";

// /skins/[skin]/unzip1.template.php
$net2ftp_messages["Set all targetdirectories"] = "Aseta kaikki kohdehakemistot";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Asettaaksesi yleisen kohdehakemiston, syˆt‰ kohdehakemisto yll‰olevaan tekstikentt‰‰n ja klikkaa \"Aseta kaikki kohdehakemistot\" -painiketta.";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Huomautus: kohdehakemisto pit‰‰ olla olemassa ennen kuin mit‰‰n voidaan kopioida siihen.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Pura paketti <b>%1\$s</b> kohteeseen:";
$net2ftp_messages["Target directory:"] = "Kohdehakemisto:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "K‰yt‰ hakemistojen nimi‰ (luo alihakemistot automaattisesti)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "P‰ivit‰ tiedosto";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>VAROITUS: TƒMƒ TOIMINTO ON EDELLEEN VARHAISEN KEHITYKSEN ALAISENA. KƒYTƒ SITƒ VAIN TESTITIEDOSTOILLE! SINUA ON VAROITETTU!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "Tunnetut ongelmat: - poistaa tabulaattorimerkit - ei toimi hyvin isojen tiedostojen kanssa (enemm‰n kuin 50kt) - ei ole viel‰ testattu ei-normaaleja merkkej‰ sis‰lt‰vien tiedostojen kanssa</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "T‰m‰ toiminto antaa sinun ladata uuden version valitusta tiedostosta, n‰hd‰ksesi, mitk‰ ovat muutoksia ja voidaksesi hyv‰ksy‰ tai hyl‰t‰ jokaisen muutoksen erikseen. Ennen kuin mit‰‰n on tallennettu, voit muokata liitettyj‰ tiedostoja.";
$net2ftp_messages["Old file:"] = "Vanha tiedosto:";
$net2ftp_messages["New file:"] = "Uusi tiedosto:";
$net2ftp_messages["Restrictions:"] = "Rajoitukset:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "net2ftp on asettanut yhden tiedoston maksimikoon <b>%1\$s kilotavuksi</b> ja PHP:ll‰ <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Maksimisuoritusaika on <b>%1\$s sekuntia</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "FTP:n siirtotila (ASCII tai BINƒƒRI) tunnistetaan automaattisesti tiedostop‰‰tteest‰";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Jos kohdetiedosto on jo olemassa, se p‰‰llekirjoitetaan";
$net2ftp_messages["You did not provide any files or archives to upload."] = "Et antanut tiedostoja tai paketteja ladattavaksi.";
$net2ftp_messages["Unable to delete the new file"] = "Uutta tiedostoa ei voida poistaa";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "Odota...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "Valitse rivit alla, hyv‰ksy tai hylk‰‰ muutokset ja l‰het‰ lomake.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "Lataa palvelimelle hakemistoon:";
$net2ftp_messages["Files"] = "Tiedostot";
$net2ftp_messages["Archives"] = "Paketit";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "T‰h‰n syˆtetyt tiedostot siirret‰‰n FTP-palvelimelle.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "T‰h‰n syˆtetyt paketit puretaan ja sen sis‰ll‰ olevat tiedostot siirret‰‰n FTP-palvelimelle.";
$net2ftp_messages["Add another"] = "Lis‰‰ toinen";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "K‰yt‰ hakemistojen nimi‰ (luo alihakemistot automaattisesti)";

$net2ftp_messages["Choose a directory"] = "Valitse hakemisto";
$net2ftp_messages["Please wait..."] = "Odota...";
$net2ftp_messages["Uploading... please wait..."] = "Ladataan palvelimelle... odota...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "Jos palvelimelle lataaminen kest‰‰ enemm‰n kuin sallittu <b>%1\$s sekuntia<\/b>, sinun t‰ytyy yritt‰‰ uudelleen k‰ytt‰en v‰hemm‰n tiedostoja tai pienempi‰ tiedostoja.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "T‰m‰ ikkuna suljetaan automaattisesti muutaman sekunnin kuluttua.";
$net2ftp_messages["Close window now"] = "Sulje ikkuna nyt";

$net2ftp_messages["Upload files and archives"] = "Lataa tiedostoja ja paketteja palvelimelle";
$net2ftp_messages["Upload results"] = "Latauksen tulokset";
$net2ftp_messages["Checking files:"] = "Tarkistetaan tiedostoja:";
$net2ftp_messages["Transferring files to the FTP server:"] = "Siirret‰‰n tiedostoja FTP-palvelimelle:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "Puretaan paketteja ja siirret‰‰n tiedostoja FTP-palvelimelle:";
$net2ftp_messages["Upload more files and archives"] = "Lataa lis‰‰ tiedostoja tai paketteja palvelimelle";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "Rajoitukset:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "net2ftp on asettanut yhden tiedoston maksimikoon <b>%1\$s kilotavuksi</b> ja PHP:ll‰ <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Maksimisuoritusaika on <b>%1\$s sekuntia</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "FTP:n siirtotila (ASCII tai BINƒƒRI) tunnistetaan automaattisesti tiedostop‰‰tteest‰";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Jos kohdetiedosto on jo olemassa, se p‰‰llekirjoitetaan";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "N‰yt‰ tiedosto %1\$s";
$net2ftp_messages["View image %1\$s"] = "N‰yt‰ kuva %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "Katso Macromedia ShockWave Flash -elokuva %1\$s";
$net2ftp_messages["Image"] = "Kuva";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntaksin v‰ritys on toteutettu <a href=\"http://geshi.org\">GeSHi</a>:ll‰";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "Tallentaaksesi kuvan, klikkaa sit‰ hiiren oikealla n‰pp‰imell‰ ja valitse 'Tallenna kuva nimell‰...'.";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "ZIP-kohteet";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "Tallenna ZIP-tiedosto FTP-palvelimelle nimell‰:";
$net2ftp_messages["Email the zip file in attachment to:"] = "L‰het‰ ZIP-tiedosto s‰hkˆpostin liitteen‰ kohteelle:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "Huomaathan, ett‰ tiedostojen l‰hett‰minen ei ole nimetˆnt‰: IP-osoitteesi, kuten myˆs l‰hetysaika, lis‰t‰‰n s‰hkˆpostiin.";
$net2ftp_messages["Some additional comments to add in the email:"] = "Kirjoita kommentteja lis‰tt‰v‰ksi s‰hkˆpostiin:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "Et antanut ZIP-tiedoston tiedostonime‰. Palaa takaisin ja syˆt‰ tiedostonimi.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "Syˆtt‰m‰si s‰hkˆpostiosoite (%1\$s) ei ole kelvollinen.<br />Syˆt‰ osoite muodossa <b>kayttaja@osoite.fi</b>";

} // end zip

?>