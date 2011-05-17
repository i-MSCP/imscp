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
$net2ftp_messages["en"] = "it";

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

$net2ftp_messages["Connecting to the FTP server"] = "Connessione al server FTP";
$net2ftp_messages["Logging into the FTP server"] = "Logging into the FTP server";
$net2ftp_messages["Setting the passive mode"] = "Setting the passive mode";
$net2ftp_messages["Getting the FTP system type"] = "Getting the FTP system type";
$net2ftp_messages["Changing the directory"] = "Changing the directory";
$net2ftp_messages["Getting the current directory"] = "Getting the current directory";
$net2ftp_messages["Getting the list of directories and files"] = "Sto rilevando l&acute;elenco delle cartelle e dei file";
$net2ftp_messages["Parsing the list of directories and files"] = "Parsing the list of directories and files";
$net2ftp_messages["Logging out of the FTP server"] = "Logging out of the FTP server";
$net2ftp_messages["Getting the list of directories and files"] = "Sto rilevando l&acute;elenco delle cartelle e dei file";
$net2ftp_messages["Printing the list of directories and files"] = "Stampa l&acute;elenco delle cartelle e dei file";
$net2ftp_messages["Processing the entries"] = "Analisi dei dati";
$net2ftp_messages["Processing entry %1\$s"] = "Processing entry %1\$s";
$net2ftp_messages["Checking files"] = "Controllo dei file";
$net2ftp_messages["Transferring files to the FTP server"] = "Trasferimento dei file verso il server FTP";
$net2ftp_messages["Decompressing archives and transferring files"] = "Decompressione degli archivi e trasferimento dei file";
$net2ftp_messages["Searching the files..."] = "Ricerca file...";
$net2ftp_messages["Uploading new file"] = "Carica (Upload) un nuovo file";
$net2ftp_messages["Reading the file"] = "Reading the file";
$net2ftp_messages["Parsing the file"] = "Parsing the file";
$net2ftp_messages["Reading the new file"] = "Lettura del nuovo file";
$net2ftp_messages["Reading the old file"] = "Lettura del vecchio file";
$net2ftp_messages["Comparing the 2 files"] = "Confronto fra i due file";
$net2ftp_messages["Printing the comparison"] = "Stampa il risultato del confronto";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "Sending FTP command %1\$s of %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Getting archive %1\$s of %2\$s from the FTP server";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Creating a temporary directory on the FTP server";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Setting the permissions of the temporary directory";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Copying the net2ftp installer script to the FTP server";
$net2ftp_messages["Script finished in %1\$s seconds"] = "Esecuzione finita in %1\$s secondi";
$net2ftp_messages["Script halted"] = "Esecuzione arrestata";

// Used on various screens
$net2ftp_messages["Please wait..."] = "Per favore attendi...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Unexpected state string: %1\$s. Exiting.";
$net2ftp_messages["This beta function is not activated on this server."] = "Questa funzione beta non e&acute; attiva su questo server.";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "This function has been disabled by the Administrator of this website.";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead.";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "Your root directory <b>%1\$s</b> does not exist or could not be selected.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted.";
$net2ftp_messages["Execute %1\$s in a new window"] = "Esegui %1\$s in una nuova finestra";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "Per favore seleziona almeno una cartella o file!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "Il server FTP <b>%1\$s</b> non e&acute; nell&acute;elenco dei server FTP permessi.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "Il server FTP <b>%1\$s</b> e&acute; nell&acute;elenco dei server FTP proibiti.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "La porta %1\$s del server FTP non puo&acute; essere usata.";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "Your IP address (%1\$s) is not in the list of allowed IP addresses.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "Il tuo indirizzo IP (%1\$s) e&acute; nell&acute;elenco degli indirizzi IP banditi.";

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
$net2ftp_messages["Unable to determine your IP address."] = "Non riesco a determinare il tuo indirizzo IP.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "La tabella net2ftp_log_consumption_ipaddress contiene una riga doppia.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "La tabella net2ftp_log_consumption_ftpserver contiene una riga doppia.";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "La variabile <b>consumption_ipaddress_datatransfer</b> non è numerica.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "La tabella net2ftp_log_consumption_ipaddress non puo&acute; essere aggiornata.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "La tabella net2ftp_log_consumption_ipaddress contiene dati doppi.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "La tabella net2ftp_log_consumption_ftpserver non puo&acute; essere aggiornata.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "La tabella net2ftp_log_consumption_ftpserver contiente dati doppi.";
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
$net2ftp_messages["An error has occured"] = "Si e&acute; verificato un errore";
$net2ftp_messages["Go back"] = "Torna indietro";
$net2ftp_messages["Go to the login page"] = "Torna alla pagina di accesso";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "Il <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">modulo FTP del PHP</a> non e&acute; installato.<br /><br /> l&acute;amministratore di questo sito dovrebbe installare questo modulo FTP. Istruzioni sull&acute;istallazione sono date su <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Non e&acute; possibile connettersi al server FTP <b>%1\$s</b> sulla porta <b>%2\$s</b>.<br /><br />Sei sicuro che questo sia l&acute;indirizzo del server FTP? Spesso tale indirizzo e&acute; diverso da quello del server web HTTP. Per favore contatta il tuo provider ISP o l&acute;amministratore di sistema per aiuto.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Non e&acute; possibile effettuare l&acute;accesso al server FTP <b>%1\$s</b> con username <b>%2\$s</b>.<br /><br />Sei sicuro che il tuo username e la password sono corrette? Per favore contatta il tuo provider ISP o l&acute;amministratore di sistema per aiuto.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "Non e&acute; possibile passare in modalita&acute; -passive mode- sul server FTP <b>%1\$s</b>.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Non e&acute; possibile connettersi al server FTP di destinazione <b>%1\$s</b> sulla porta <b>%2\$s</b>.<br /><br />Sei sicuro che dell&acute;indirizzo del server FTP di destinazione? Spesso tale indirizzo e&acute; diverso da quello del server web HTTP. Per favore contatta il tuo provider ISP o l&acute;amministratore di sistema per aiuto.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Non e&acute; possibile effettuare l&acute;accesso al server FTP di destinazione <b>%1\$s</b> con username <b>%2\$s</b>.<br /><br />Sei sicuro che il tuo username e la password sono corrette? Per favore contatta il tuo provider ISP o l&acute;amministratore di sistema per aiuto.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "Non e&acute; possibile passare in modalita&acute; passive mode sul server FTP di destinazione <b>%1\$s</b>.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "Non e&acute; possibile rinominare la cartella o il file <b>%1\$s</b> in <b>%2\$s</b>";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "Non e&acute; possibile i comandi del sito <b>%1\$s</b>. Nota che il comando CHMOD e&acute; disponibile solo su server FTP Unix/Linux, non su server FTP Windows.";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "E&acute; stato eseguito con successo un CHMOD <b>%2\$s</b> sulla cartella <b>%1\$s</b>";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "Processing entries within directory <b>%1\$s</b>:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "E&acute; stato eseguito con successo un CHMOD <b>%2\$s</b> sul file <b>%1\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "I file e le cartelle selezionati sono stati elaborati.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "Non e&acute; possibile eliminare la cartella <b>%1\$s</b>";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Non e&acute; possibile eliminare il file <b>%1\$s</b>";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "Non e&acute; possibile creare la cartella <b>%1\$s</b>";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "Non e&acute; possibile creare il file temporaneo";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "Non e&acute; possibile ottenere il file <b>%1\$s</b> dal server FTP e salvarlo come file temporaneo <b>%2\$s</b>.<br />Controlla i permessi della cartella %3\$s.<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Non e&acute; possibile aprire il file temporaneo. Controlla i permessi della cartella %1\$s.";
$net2ftp_messages["Unable to read the temporary file"] = "Non e&acute; possibile leggere i file temporanei";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Non e&acute; possibile chiudere il file temporaneo";
$net2ftp_messages["Unable to delete the temporary file"] = "Non e&acute; possibile eliminare il file temporaneo";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "Non e&acute; possibile creare il file temporaneo. Controlla i permessi della cartella %1\$s.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Non e&acute; possibile aprire il file temporaneo. Controlla i permessi della cartella %1\$s.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "Non e&acute; possibile ascrivere la stringa nel file temporaneo <b>%1\$s</b>.<br />Controlla i permessi della cartella %2\$s.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Non e&acute; possibile chiudere il file temporaneo";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "Non e&acute; possibile inserire il file <b>%1\$s</b> sul server FTP.<br />Forse non hai permessi in scrittura su quella cartella.";
$net2ftp_messages["Unable to delete the temporary file"] = "Non e&acute; possibile eliminare il file temporaneo";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "Sto elaborando la cartella<b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "La cartella di destinazione <b>%1\$s</b> e&acute; identica o e&acute; una sotto-cartella della cartella di origine <b>%2\$s</b>, quindi questa cartella verra&acute; saltata";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The directory <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "Non e&acute; possibile creare la sotto-cartella <b>%1\$s</b>. Forse esiste gia&acute;. Continuo l&acute;esecuzione di copia/spostamento...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Created target subdirectory <b>%1\$s</b>";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "The directory <b>%1\$s</b> could not be selected, so this directory will be skipped";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "Non e&acute; possibile eliminare la sotto-cartella <b>%1\$s</b> - forse non e&acute; vuota";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "E&acute; stata eliminata la sotto-cartella <b>%1\$s</b>";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "Completata l&acute;analisi della cartella <b>%1\$s</b>";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "La destinazione per il file <b>%1\$s</b> e&acute; identica all&acute;origine, quindi questo file verra&acute; saltato";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The file <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "The file <b>%1\$s</b> is too big to be copied, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "The file <b>%1\$s</b> is too big to be moved, aborting the move";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "Non e&acute; possibile copiare il file <b>%1\$s</b>";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Copied file <b>%1\$s</b>";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Unable to move the file <b>%1\$s</b>, aborting the move";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "Spostato il file <b>%1\$s</b>";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Non e&acute; possibile eliminare il file <b>%1\$s</b>";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "E&acute; stato eliminato il file <b>%1\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "I file e le cartelle selezionati sono stati elaborati.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "Non e&acute; possibile copiare il file remoto <b>%1\$s</b> in un file locale usando la modalita&acute; FTP <b>%2\$s</b>";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "Non e&acute; possibile eliminare il file <b>%1\$s</b>";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "The file is too big to be transferred";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "Limite giornaliero raggiunto: il file <b>%1\$s</b> non sara&acute; trasferito";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "Non e&acute; possibile copiare il file locale nel file remoto <b>%1\$s</b> usando la modalita&acute; FTP <b>%2\$s</b>";
$net2ftp_messages["Unable to delete the local file"] = "Non riesco a eliminare il file locale";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "Non e&acute; possibile eliminare il file temporaneo";
$net2ftp_messages["Unable to send the file to the browser"] = "Non e&acute; possibile inviare il file al browser";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "Non e&acute; possibile creare il file temporaneo";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "Il file zip e&acute; stato salvato sul server FTP come <b>%1\$s</b>";
$net2ftp_messages["Requested files"] = "Files richiesti";

$net2ftp_messages["Dear,"] = "Gentile,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "Qualcuno ha pensato che i file allegati a questo messaggio siano mandati al suo account e-mail (%1\$s).";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "Se non sai nulla di cio&acute; o non ti fidi di questa persona, per favore elimina questa e-mail senza aprire il file zip in allegato.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "Nota che se non apri il file zip, i files al suo interno non possono danneggiare il tuo computer.";
$net2ftp_messages["Information about the sender: "] = "Informationi sul mittente: ";
$net2ftp_messages["IP address: "] = "Indirizzo IP: ";
$net2ftp_messages["Time of sending: "] = "Ora dell&acute;invio: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "Inviato tramite il programma net2ftp installato sul sito web: ";
$net2ftp_messages["Webmaster's email: "] = "Email del webmaster: ";
$net2ftp_messages["Message of the sender: "] = "Messaggio dal mittente: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp e&acute; software gratuito, rilasciato sotto la licenza GNU/GPL. Per ulteriori informazioni, vai al sito http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "Il file zip e&acute; stato inviato a <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "Il file <b>%1\$s</b> e&acute; troppo grande. Questo file non sara&acute; caricato sul sito.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded.";
$net2ftp_messages["Could not generate a temporary file."] = "Non e&acute; possibile generare un file temporaneo.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "Il file <b>%1\$s</b> non puo&acute; esser spostato";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "Il file <b>%1\$s</b> e&acute; OK";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "Non e&acute; possibile spostare nella cartella temporanea il file caricato.<br /><br />L&acute;amministratore di questo sito web deve cambiare il permesso della cartella /temp di net2ftp con <b>chmod 777</b>.";
$net2ftp_messages["You did not provide any file to upload."] = "Non hai specificato nessun file da caricare.";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "Il file <b>%1\$s</b> non puo&acute; esser trasferito al server FTP";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "Il file <b>%1\$s</b> e&acute; stato trasferito al server FTP usando la modalita&acute; FTP <b>%2\$s</b>";
$net2ftp_messages["Transferring files to the FTP server"] = "Trasferimento dei file verso il server FTP";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "Sto elaborando l&acute;archivio numero %1\$s: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "L&acute;archivio <b>%1\$s</b> non e&acute; stato elaborato perche&acute; la sua estensione di filename non e&acute; stata riconosciuta. Al momento sono supportati solo archivio zip, tar, tgz e gz.";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Unable to extract the files and directories from the archive";
$net2ftp_messages["Archive contains filenames with ../ or ..\\ - aborting the extraction"] = "Archive contains filenames with ../ or ..\\ - aborting the extraction";
$net2ftp_messages["Created directory %1\$s"] = "Created directory %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "Could not create directory %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "Copied file %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "Could not copy file %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "Unable to delete the temporary directory";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Unable to delete the temporary file %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "Non e&acute; possibile eseguire il comando del sito <b>%1\$s</b>";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "La tua richiesta e&acute; stata annullata";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "Il compito che hai chiesto di fare con net2ftp richiede piu&acute; tempo dei %1\$s secondi permessi, e quindi la tua richiesta e&acute; stata fermata.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "Questo limite sul tempo di esecuzione garantisce un uso corretto del server web per tutti.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "Cerca di dividere il tuo compito in passi piu&acute; piccoli: restringi la tua selezione di files ed ometti i files piu&acute; grandi.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "Se davvero hai bisogno di net2ftp per risolvere compiti che richiedono un lungo tempo, condiera la possibilita&acute; di installare net2ftp sul tuo server personale.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "Non hai inserito alcun messaggio da mandare via e-mail!";
$net2ftp_messages["You did not supply a From address."] = "Non hai inserito l&acute;indirizzo From.";
$net2ftp_messages["You did not supply a To address."] = "Non hai inserito l&acute;indirizzo To.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "A causa di problemi tecnici il messaggio e-mail a <b>%1\$s</b> non e&acute; stato inviato.";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "Inserire username e password per il server FTP ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Non sono stati inseriti i dati di accesso nella finestra popup.<br />Clicca su \"Vai alla pagina di accesso\" sopra.";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "L&acute;accesso al pannello di amministrazione net2ftp e&acute; disabilitato, perche&acute; non e&acute; stata specificata la password nel file settings.inc.php. Inserire una password in quel file e riprovare.";
$net2ftp_messages["Please enter your Admin username and password"] = "Inserire username e password di amministrazione"; 
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Non sono stati inseriti i dati di accesso nella finestra popup.<br />Clicca su \"Vai alla pagina di accesso\" sopra.";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "Username e password di amministrazione errati. Username e password possono essere impostati nel file settings.inc.php.";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "Blu";
$net2ftp_messages["Grey"] = "Grigio";
$net2ftp_messages["Black"] = "Nero";
$net2ftp_messages["Yellow"] = "Giallo";
$net2ftp_messages["Pastel"] = "Pastello";

// getMime()
$net2ftp_messages["Directory"] = "Cartella";
$net2ftp_messages["Symlink"] = "Link simbolico";
$net2ftp_messages["ASP script"] = "Script ASP";
$net2ftp_messages["Cascading Style Sheet"] = "Cascading Style Sheet";
$net2ftp_messages["HTML file"] = "File HTML";
$net2ftp_messages["Java source file"] = "File sorgente Java";
$net2ftp_messages["JavaScript file"] = "File JavaScript";
$net2ftp_messages["PHP Source"] = "Sorgente PHP";
$net2ftp_messages["PHP script"] = "Script PHP";
$net2ftp_messages["Text file"] = "File di testo";
$net2ftp_messages["Bitmap file"] = "File bitmap";
$net2ftp_messages["GIF file"] = "File GIF";
$net2ftp_messages["JPEG file"] = "File JPEG";
$net2ftp_messages["PNG file"] = "File PNG";
$net2ftp_messages["TIF file"] = "File TIF";
$net2ftp_messages["GIMP file"] = "File GIMP";
$net2ftp_messages["Executable"] = "Eseguibile";
$net2ftp_messages["Shell script"] = "Script shell";
$net2ftp_messages["MS Office - Word document"] = "MS Office - documento Word";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - Excel spreadsheet";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - presentatione PowerPoint";
$net2ftp_messages["MS Office - Access database"] = "MS Office - database Access";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - disegno Visio";
$net2ftp_messages["MS Office - Project file"] = "MS Office - file di Project";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - Writer 6.0 documento";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - Writer 6.0 template";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - Calc 6.0 spreadsheet";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - Calc 6.0 template";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - Draw 6.0 documento";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - Draw 6.0 template";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - Impress 6.0 presentatione";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - Impress 6.0 template";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - Writer 6.0 documento globale";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - Math 6.0 documento";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - StarWriter 5.x documento";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - StarWriter 5.x  documento globale";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - StarCalc 5.x spreadsheet";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - StarDraw 5.x documento";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - StarImpress 5.x presentatione";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - StarImpress Packed 5.x file";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - StarMath 5.x documento";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - StarChart 5.x documento";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - StarMail 5.x file di mail";
$net2ftp_messages["Adobe Acrobat document"] = "Documento Adobe Acrobat";
$net2ftp_messages["ARC archive"] = "Archivio ARC";
$net2ftp_messages["ARJ archive"] = "Archivio ARJ";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "Archivio GZ";
$net2ftp_messages["TAR archive"] = "Archivio TAR";
$net2ftp_messages["Zip archive"] = "Archivio Zip";
$net2ftp_messages["MOV movie file"] = "Filmato MOV";
$net2ftp_messages["MPEG movie file"] = "Filmato MPEG";
$net2ftp_messages["Real movie file"] = "Filmato Real";
$net2ftp_messages["Quicktime movie file"] = "Filmato Quicktime";
$net2ftp_messages["Shockwave flash file"] = "File Shockwave flash";
$net2ftp_messages["Shockwave file"] = "File Shockwave";
$net2ftp_messages["WAV sound file"] = "File di suono WAV";
$net2ftp_messages["Font file"] = "File di Font";
$net2ftp_messages["%1\$s File"] = "%1\$s File";
$net2ftp_messages["File"] = "File";

// getAction()
$net2ftp_messages["Back"] = "Indietro";
$net2ftp_messages["Submit"] = "Invia";
$net2ftp_messages["Refresh"] = "Aggiorna";
$net2ftp_messages["Details"] = "Dettagli";
$net2ftp_messages["Icons"] = "Icone";
$net2ftp_messages["List"] = "Elenco";
$net2ftp_messages["Logout"] = "Uscita";
$net2ftp_messages["Help"] = "Aiuto";
$net2ftp_messages["Bookmark"] = "Preferiti";
$net2ftp_messages["Save"] = "Salva";
$net2ftp_messages["Default"] = "Default";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Help Guide";
$net2ftp_messages["Forums"] = "Forums";
$net2ftp_messages["License"] = "Licenza";
$net2ftp_messages["Powered by"] = "Powered by";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions.";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "Funzioni di amministrazione";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "Info versione";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "This version of net2ftp is up-to-date.";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server.";
$net2ftp_messages["Logging"] = "Accesso in corso";
$net2ftp_messages["Date from:"] = "Dal :";
$net2ftp_messages["to:"] = "al:";
$net2ftp_messages["Empty logs"] = "Vuoto";
$net2ftp_messages["View logs"] = "Vedi log";
$net2ftp_messages["Go"] = "Esegui";
$net2ftp_messages["Setup MySQL tables"] = "Imposta tabelle MySQL";
$net2ftp_messages["Create the MySQL database tables"] = "Crea le tabelle MySQL";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "Funzioni di amministrazione";
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
$net2ftp_messages["This SQL query is going to be executed:"] = "Verra&acute; eseguita questa query SQL:";
$net2ftp_messages["Execute"] = "Esegui";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "Impostazioni utilizzate:";
$net2ftp_messages["MySQL password length"] = "Lunghezza password MySQL";
$net2ftp_messages["Results:"] = "Risultati:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "Funzioni di amministrazione";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "Unable to execute the SQL query <b>%1\$s</b>.";
$net2ftp_messages["No data"] = "Nessun dato";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "Funzioni di amministrazione";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "La tabella <b>%1\$s</b> e&acute; stata svuotata correttamente.";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "La tabella <b>%1\$s</b> non puo&acute; essere svuotata.";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "The table <b>%1\$s</b> was optimized successfully.";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "The table <b>%1\$s</b> could not be optimized.";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "Funzioni Avanzate";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "Esegui";
$net2ftp_messages["Disabled"] = "Disabled";
$net2ftp_messages["Advanced FTP functions"] = "Advanced FTP functions";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "Send arbitrary FTP commands to the FTP server";
$net2ftp_messages["This function is available on PHP 5 only"] = "This function is available on PHP 5 only";
$net2ftp_messages["Troubleshooting functions"] = "Funzioni di controllo";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "Controlla net2ftp su questo server web";
$net2ftp_messages["Troubleshoot an FTP server"] = "Controlla un server FTP";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Test the net2ftp list parsing rules";
$net2ftp_messages["Translation functions"] = "Funzioni di traduzione";
$net2ftp_messages["Introduction to the translation functions"] = "Introduzione alle funzionalita&acute; di traduzione";
$net2ftp_messages["Extract messages to translate from code files"] = "Estrai i messaggi da tradurre dai file sorgente";
$net2ftp_messages["Check if there are new or obsolete messages"] = "Controlla se ci sono vecchi o nuovi messaggi";

$net2ftp_messages["Beta functions"] = "Funzioni BETA";
$net2ftp_messages["Send a site command to the FTP server"] = "Invia un comando FTP al server";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: proteggi una cartella, crea pagine di errore personali";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: esegui una query SQL";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "Le funzioni di comando del sito non sono disponibili su questo server web.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "Le funzioni Apache non sono disponibili su questo server web.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "Le funzioni MySQL non sono disponibili su questo server web.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "Stringa di stato2 inattesa. Interrompo l&acute;esecuzione.";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "Controlla un server FTP";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "Settaggi di connessione:";
$net2ftp_messages["FTP server"] = "Server FTP";
$net2ftp_messages["FTP server port"] = "Porta del server FTP";
$net2ftp_messages["Username"] = "Username";
$net2ftp_messages["Password"] = "Password";
$net2ftp_messages["Password length"] = "Lunghezza della password";
$net2ftp_messages["Passive mode"] = "Modo passivo";
$net2ftp_messages["Directory"] = "Cartella";
$net2ftp_messages["Printing the result"] = "Printing the result";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "Connessione al server FTP: ";
$net2ftp_messages["Logging into the FTP server: "] = "Accesso al server FTP: ";
$net2ftp_messages["Setting the passive mode: "] = "Setto la modalita&acute; passive mode: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Tipo di server FTP: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Cambio alla cartella %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "La cartella del server FTP e&acute;: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Rilevamento dell&acute;elenco cartelle e file: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Secondo tentativo di rilevamento dell&acute;elenco cartelle e file: ";
$net2ftp_messages["Closing the connection: "] = "Chiusura della connessione: ";
$net2ftp_messages["Raw list of directories and files:"] = "Elenco delle cartelle e dei file:";
$net2ftp_messages["Parsed list of directories and files:"] = "Elenco ordinato delle cartelle e dei file:";

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

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "Controlla la tua installazione di net2ftp";
$net2ftp_messages["Printing the result"] = "Printing the result";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "Controlla se il modulo FTP di PHP e&acute; installato: ";
$net2ftp_messages["yes"] = "si";
$net2ftp_messages["no - please install it!"] = "no - per favore installalo!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "Controlla i permessi della cartella sul server web: un piccolo file di prova verra&acute; scritto nella cartella /temp e poi verra&acute; eliminato.";
$net2ftp_messages["Creating filename: "] = "Crea il file: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "OK. Nome del file: %1\$s";
$net2ftp_messages["not OK"] = "not OK";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "non va bene. Controlla i permessi della cartella %1\$s";
$net2ftp_messages["Opening the file in write mode: "] = "Apre il file in scrittura: ";
$net2ftp_messages["Writing some text to the file: "] = "Inserimento di testo nel file: ";
$net2ftp_messages["Closing the file: "] = "Chiusura del file: ";
$net2ftp_messages["Deleting the file: "] = "Eliminazione del file: ";

$net2ftp_messages["Testing the FTP functions"] = "Testing the FTP functions";
$net2ftp_messages["Connecting to a test FTP server: "] = "Connecting to a test FTP server: ";
$net2ftp_messages["Connecting to the FTP server: "] = "Connessione al server FTP: ";
$net2ftp_messages["Logging into the FTP server: "] = "Accesso al server FTP: ";
$net2ftp_messages["Setting the passive mode: "] = "Setto la modalita&acute; passive mode: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Tipo di server FTP: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Cambio alla cartella %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "La cartella del server FTP e&acute;: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Rilevamento dell&acute;elenco cartelle e file: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Secondo tentativo di rilevamento dell&acute;elenco cartelle e file: ";
$net2ftp_messages["Closing the connection: "] = "Chiusura della connessione: ";
$net2ftp_messages["Raw list of directories and files:"] = "Elenco delle cartelle e dei file:";
$net2ftp_messages["Parsed list of directories and files:"] = "Elenco ordinato delle cartelle e dei file:";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "not OK";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "Aggiungi questo link ai tuoi Favoriti:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: clicca col tasto destro sul link e scegli \"Aggiungi ai Favoriti...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: clicca col tasto destro sul link e scegli \"Metti questo link nei Bookmark...\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "Nota: quando userai questo bookmark, una finestra popup ti chiedera&acute; il tuo username e la tua password.";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "Scegli una cartella";
$net2ftp_messages["Please wait..."] = "Per favore attendi...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "Le cartelle con i nomi che contengono \' non possono essere mostrate correttamente. Possono solo essere eliminate. Per favore torna indietro e seleziona una sotto-cartella diversa.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "Limite giornaliero raggiunto: non e&acute; possibile trasferire ulteriori file";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "Per garantire a tutti un uso efficente del sistema net2ftp, sono impostati un limite di trasferimento e un timeout di esecuzione per ugni utente. A limite raggiunto sara&acute; possibile effettuare solo un browse dei file senza nessun tipo di trasferimento.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "Se desideri un utilizzo senza limiti, installa net2ftp nel tuo proprio sito web.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "Nuova cartella";
$net2ftp_messages["New file"] = "Nuovo file";
$net2ftp_messages["HTML templates"] = "HTML templates";
$net2ftp_messages["Upload"] = "Carica";
$net2ftp_messages["Java Upload"] = "Carica Java";
$net2ftp_messages["Flash Upload"] = "Flash Upload";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Advanced"] = "Funzioni avanzate";
$net2ftp_messages["Copy"] = "Copia";
$net2ftp_messages["Move"] = "Sposta";
$net2ftp_messages["Delete"] = "Elimina";
$net2ftp_messages["Rename"] = "Rinomina";
$net2ftp_messages["Chmod"] = "CHMOD";
$net2ftp_messages["Download"] = "Scarica";
$net2ftp_messages["Unzip"] = "Unzip";
$net2ftp_messages["Zip"] = "Zip";
$net2ftp_messages["Size"] = "Dimensione";
$net2ftp_messages["Search"] = "Cerca";
$net2ftp_messages["Go to the parent directory"] = "Vai alla cartella di livello superiore";
$net2ftp_messages["Go"] = "Esegui";
$net2ftp_messages["Transform selected entries: "] = "Trasforma gli elementi selezionati: ";
$net2ftp_messages["Transform selected entry: "] = "Transform selected entry: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "Crea una nuova sotto-cartella nella cartella %1\$s";
$net2ftp_messages["Create a new file in directory %1\$s"] = "Crea un nuovo file nella cartella %1\$s";
$net2ftp_messages["Create a website easily using ready-made templates"] = "Create a website easily using ready-made templates";
$net2ftp_messages["Upload new files in directory %1\$s"] = "Caricamento nuovi file nella cartella %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Upload directories and files using a Java applet";
$net2ftp_messages["Upload files using a Flash applet"] = "Upload files using a Flash applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Install software packages (requires PHP on web server)";
$net2ftp_messages["Go to the advanced functions"] = "Vai alle funzioni avanzate";
$net2ftp_messages["Copy the selected entries"] = "Copia gli elementi selezionati";
$net2ftp_messages["Move the selected entries"] = "Sposta gli elementi selezionati";
$net2ftp_messages["Delete the selected entries"] = "Elimina gli elementi selezionati";
$net2ftp_messages["Rename the selected entries"] = "Renonima gli elementi selezionati";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "CHMOD sugli elementi selezionati (solo per servers Unix/Linux/BSD)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "Scarica un archivio zip che contiene gli elementi selezionati";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Unzip the selected archives on the FTP server";
$net2ftp_messages["Zip the selected entries to save or email them"] = "Crea un archivio zip con gli elementi selezionati da salvare o mandare via e-mail";
$net2ftp_messages["Calculate the size of the selected entries"] = "Calcola le dimensioni degli elementi selezionati";
$net2ftp_messages["Find files which contain a particular word"] = "Trova i file che contengono una determinata parola";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "Clicca per ordinare per %1\$s in ordine descrescente";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "Clicca per ordinare per %1\$s in ordine crescente";
$net2ftp_messages["Ascending order"] = "Ordine crescente";
$net2ftp_messages["Descending order"] = "Ordine descrescente";
$net2ftp_messages["Upload files"] = "Upload files";
$net2ftp_messages["Up"] = "Su";
$net2ftp_messages["Click to check or uncheck all rows"] = "Clicca per selezionare/deselezionare tutte le righe";
$net2ftp_messages["All"] = "Tutto";
$net2ftp_messages["Name"] = "Nome";
$net2ftp_messages["Type"] = "Tipo";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "Utente";
$net2ftp_messages["Group"] = "Gruppo";
$net2ftp_messages["Perms"] = "Permessi";
$net2ftp_messages["Mod Time"] = "Ora modifica";
$net2ftp_messages["Actions"] = "Azioni";
$net2ftp_messages["Select the directory %1\$s"] = "Select the directory %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "Select the file %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "Select the symlink %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "Go to the subdirectory %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "Scarica il file %1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "Follow symlink %1\$s";
$net2ftp_messages["View"] = "Vedi";
$net2ftp_messages["Edit"] = "Modifica";
$net2ftp_messages["Update"] = "Aggiorna";
$net2ftp_messages["Open"] = "Apri";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Vedi il codice sorgente selezionato del file %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Modifica il codice sorgente del file %1\$s";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "Carica una nuova versione del file %1\$s e sincronizza i cambiamenti";
$net2ftp_messages["View image %1\$s"] = "Guarda l&acute;immagine %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "Guarda il file %1\$s dal tuo web server HTTP";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(Nota: Questo link puo&acute; non funzionare se non hai un tuo dominio personale.)";
$net2ftp_messages["This folder is empty"] = "Questa cartella e&acute; vuota";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "Cartelle";
$net2ftp_messages["Files"] = "File";
$net2ftp_messages["Symlinks"] = "Link simbolici";
$net2ftp_messages["Unrecognized FTP output"] = "Risultato FTP non riconosciuto";
$net2ftp_messages["Number"] = "Number";
$net2ftp_messages["Size"] = "Dimensione";
$net2ftp_messages["Skipped"] = "Skipped";
$net2ftp_messages["Data transferred from this IP address today"] = "Data transferred from this IP address today";
$net2ftp_messages["Data transferred to this FTP server today"] = "Data transferred to this FTP server today";

// printLocationActions()
$net2ftp_messages["Language:"] = "Lingua:";
$net2ftp_messages["Skin:"] = "Skin:";
$net2ftp_messages["View mode:"] = "Modalita&acute; visione:";
$net2ftp_messages["Directory Tree"] = "Struttura cartelle";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "Esegui %1\$s in una nuova finestra";
$net2ftp_messages["This file is not accessible from the web"] = "This file is not accessible from the web";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "Clicca due volte per andare in una sotto-cartella:";
$net2ftp_messages["Choose"] = "Scegli";
$net2ftp_messages["Up"] = "Su";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "Dimensione delle cartelle e dei files selezionati";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "La dimensione totale occupata dalle cartelle e files selezionati e&acute;:";
$net2ftp_messages["The number of files which were skipped is:"] = "The number of files which were skipped is:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "CHMOD cartelle e file";
$net2ftp_messages["Set all permissions"] = "Imposta a tutti i permessi";
$net2ftp_messages["Read"] = "Leggi";
$net2ftp_messages["Write"] = "Scrivi";
$net2ftp_messages["Execute"] = "Esegui";
$net2ftp_messages["Owner"] = "Utente";
$net2ftp_messages["Group"] = "Gruppo";
$net2ftp_messages["Everyone"] = "Qualsiasi";
$net2ftp_messages["To set all permissions to the same values, enter those permissions and click on the button \"Set all permissions\""] = "Per settare tutti i permessi, inserisci i permessi indicati su e clicca sul tasto \"Imposta a tutti i permessi\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "Setta i permessi per la cartella <b>%1\$s</b>: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "Setta i permessi per il file <b>%1\$s</b>: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "Setta i permessi per il link simblico <b>%1\$s</b> to: ";
$net2ftp_messages["Chmod value"] = "Valore CHMOD";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "Esegui CHMOD anche sulle sotto-cartelle all&acute;interno di questa cartella";
$net2ftp_messages["Chmod also the files within this directory"] = "Esegui CHMOD anche sui files all&acute;interno di questa cartella";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "Il valore CHMOD <b>%1\$s</b> non e&acute; nell&acute;intervallo consentito 000-777. Per favore riprova.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "Scegli una cartella";
$net2ftp_messages["Copy directories and files"] = "Copia cartelle e file";
$net2ftp_messages["Move directories and files"] = "Sposta cartelle e file";
$net2ftp_messages["Delete directories and files"] = "Elimina cartelle e file";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "Sei sicuro di voler eliminare queste cartelle e file?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "Tutte le sotto-cartelle delle cartelle selezionate e i relativi file saranno eliminati!";
$net2ftp_messages["Set all targetdirectories"] = "Imposta a tutti come cartella di destinazione";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Per selezionare una cartella destinazione comune, inserisci quella cartella destinazione nella casella di testo su e clicca sul tasto \"Imposta a tutti come cartella di destinazione\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Nota: la cartella di destinazione deve essere esistente prima che qualunque cosa possa essere copiata al suo interno.";
$net2ftp_messages["Different target FTP server:"] = "Server FTP di destinazione esterno:";
$net2ftp_messages["Username"] = "Username";
$net2ftp_messages["Password"] = "Password";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "Lascia vuoto se vuoi copiare i files nello stesso server FTP.";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "Se vuoi copiare i files su un altro server FTP, inserisci i relativi dati di accesso.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "Lascia vuoto se vuoi spostare i files sullo stesso server FTP.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "Se vuoi spostare i fiels su un altro server FTP, inserisi i relativi dati di accesso.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "Copia cartella <b>%1\$s</b> in:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "Sposta cartella <b>%1\$s</b> in:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "cartella <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "Copia file <b>%1\$s</b> in:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "Sposta file <b>%1\$s</b> in:";
$net2ftp_messages["File <b>%1\$s</b>"] = "File <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "Copia link simbolico <b>%1\$s</b> in:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "Sposta link simbolico <b>%1\$s</b> in:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Link simbolico <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "Cartella di destinazione:";
$net2ftp_messages["Target name:"] = "Nome destinazione:";
$net2ftp_messages["Processing the entries:"] = "Elaborazione dati:";

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
$net2ftp_messages["Size"] = "Dimensione";
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
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: clicca col tasto destro sul link e scegli \"Aggiungi ai Favoriti...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: clicca col tasto destro sul link e scegli \"Metti questo link nei Bookmark...\"";

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
$net2ftp_messages["Unable to open the template file"] = "Non e&acute; possibile aprire il file template";
$net2ftp_messages["Unable to read the template file"] = "Non e&acute; possibile leggere il file template";
$net2ftp_messages["Please specify a filename"] = "Per favore specifica un nome per il file";
$net2ftp_messages["Status: This file has not yet been saved"] = "Stato: Questo file non e&acute; stato ancora salvato";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "Stato: Salvato il <b>%1\$s</b> usando la modalita&acute; %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "Stato: <b>Questo file non puo&acute; essere salvato</b>";
$net2ftp_messages["Not yet saved"] = "Not yet saved";
$net2ftp_messages["Could not be saved"] = "Could not be saved";
$net2ftp_messages["Saved at %1\$s"] = "Saved at %1\$s";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "Cartella: ";
$net2ftp_messages["File: "] = "File: ";
$net2ftp_messages["New file name: "] = "Nuovo nome per il file: ";
$net2ftp_messages["Character encoding: "] = "Character encoding: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "Nota: cambiando il tipo di area testuale salvera&acute; le modifiche fatte finora";
$net2ftp_messages["Copy up"] = "Copy up";
$net2ftp_messages["Copy down"] = "Copy down";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "Cerca cartelle e file";
$net2ftp_messages["Search again"] = "Cerca ancora";
$net2ftp_messages["Search results"] = "Cerca fra i risultati";
$net2ftp_messages["Please enter a valid search word or phrase."] = "Per favore inserisci una valida parola o frase da ricercare.";
$net2ftp_messages["Please enter a valid filename."] = "Per favore inserisci un nome file valido.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "Per favore inserisci una dimensione file valida nella casella \"da\", per esempio 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "Per favore inserisci una dimensione file valida nella casella \"a\", per esempio 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "Per favore inserisci una data valida nel formato Anno-mese-giorno nella casella \"da\".";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "Per favore inserisci una data valida nel formato Anno-mese-giorno nella casella \"a\".";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "La parola <b>%1\$s</b> non e&acute; stata trovata nelle cartelle e file selezionati.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "La parola <b>%1\$s</b> e&acute; stata trovata nei seguenti files:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "Cerca una parola o frase";
$net2ftp_messages["Case sensitive search"] = "Ricerca Case sensitive";
$net2ftp_messages["Restrict the search to:"] = "Restringi la ricerca a:";
$net2ftp_messages["files with a filename like"] = "files con un nome tipo";
$net2ftp_messages["(wildcard character is *)"] = "(il carattere jolly e&acute; *)";
$net2ftp_messages["files with a size"] = "files con una dimensione";
$net2ftp_messages["files which were last modified"] = "files modificati per l&acute;ultima volta";
$net2ftp_messages["from"] = "da";
$net2ftp_messages["to"] = "a";

$net2ftp_messages["Directory"] = "Cartella";
$net2ftp_messages["File"] = "File";
$net2ftp_messages["Line"] = "Line";
$net2ftp_messages["Action"] = "Action";
$net2ftp_messages["View"] = "Vedi";
$net2ftp_messages["Edit"] = "Modifica";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Vedi il codice sorgente selezionato del file %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Modifica il codice sorgente del file %1\$s";

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
$net2ftp_messages["Unable to open the template file"] = "Non e&acute; possibile aprire il file template";
$net2ftp_messages["Unable to read the template file"] = "Non e&acute; possibile leggere il file template";
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
$net2ftp_messages["Upload"] = "Carica";
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

$net2ftp_messages["FTP server"] = "Server FTP";
$net2ftp_messages["Example"] = "Esempio";
$net2ftp_messages["Port"] = "Port";
$net2ftp_messages["Username"] = "Username";
$net2ftp_messages["Password"] = "Password";
$net2ftp_messages["Anonymous"] = "Anonimo";
$net2ftp_messages["Passive mode"] = "Modo passivo";
$net2ftp_messages["Initial directory"] = "Cartella iniziale";
$net2ftp_messages["Language"] = "Lingua";
$net2ftp_messages["Skin"] = "Skin";
$net2ftp_messages["FTP mode"] = "modalita&acute; FTP";
$net2ftp_messages["Automatic"] = "Automatic";
$net2ftp_messages["Login"] = "Accesso";
$net2ftp_messages["Clear cookies"] = "Elimina i cookie";
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
$net2ftp_messages["Password"] = "Password";
$net2ftp_messages["Login"] = "Accesso";
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
$net2ftp_messages["Create new directories"] = "Crea nuove cartelle";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "Le nuove cartelle saranno create in <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "Nome della nuova cartella:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "La cartella <b>%1\$s</b> e&acute; stata creata con successo.";
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
$net2ftp_messages["Rename directories and files"] = "Rinonima cartelle e file";
$net2ftp_messages["Old name: "] = "Vecchio nome: ";
$net2ftp_messages["New name: "] = "Nuovo nome: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "Il nuovo nome non puo&acute; contenere alcun punto. Questa selezione non e&acute; stata rinominata in <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> e&acute; stato rinominato con successo in <b>%2\$s</b>";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> non puo&acute; essere rinominato in <b>%2\$s</b>";

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
$net2ftp_messages["Set all targetdirectories"] = "Imposta a tutti come cartella di destinazione";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Per selezionare una cartella destinazione comune, inserisci quella cartella destinazione nella casella di testo su e clicca sul tasto \"Imposta a tutti come cartella di destinazione\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Nota: la cartella di destinazione deve essere esistente prima che qualunque cosa possa essere copiata al suo interno.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Unzip archive <b>%1\$s</b> to:";
$net2ftp_messages["Target directory:"] = "Cartella di destinazione:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Usa i nomi delle cartelle (crea le sotto-cartelle automaticamente)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "Aggiorna file";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>ATTENZIONE: QUESTA FUNZIONE E&acute; ANCORA IN FASE DI SVILUPPO. USALA SOLO SU FILES DI TEST! SEI STATO AVVERTITO	!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "Questa funzione ti permette di caricare una nuova versione del file selezionato, di vedere quali sono le differenze e di accettare o annullare la sostituzione. Prima di salvare qualunque cosa, puoi sempre modificare i files risultanti dall&acute;unione.";
$net2ftp_messages["Old file:"] = "Vecchio file:";
$net2ftp_messages["New file:"] = "Nuovo file:";
$net2ftp_messages["Restrictions:"] = "Restrizioni:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "La massima dimensione di un file e&acute; settata da net2ftp a <b>%1\$s kB</b> e dal PHP a <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Il tempo di esecuzione massimo e&acute; di <b>%1\$s secondi</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "La modalita&acute; di trasferimento FTP (ASCII o BINARY) sara&acute; determinata automaticamente, basandosi sull&acute;estensione del file";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Se la destinazione esiste gia&acute;, sara&acute; sovrascritta";
$net2ftp_messages["You did not provide any files or archives to upload."] = "Non hai specificato alcun file o archivio da caricare.";
$net2ftp_messages["Unable to delete the new file"] = "Non e&acute; possibile eliminare il nuovo file";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "Per favore attendi...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "Seleziona le linee sottostanti, accetta o respingi i cambi e inserisci il modulo.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "Carica nella cartella:";
$net2ftp_messages["Files"] = "File";
$net2ftp_messages["Archives"] = "Archivi";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "I file inseriti qui saranno trasferiti sul server FTP.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "Gli archivi inseriti qui saranno decompressi, e i file al loro interno saranno trasferiti sul server FTP.";
$net2ftp_messages["Add another"] = "Aggiungi un altro";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Usa i nomi delle cartelle (crea le sotto-cartelle automaticamente)";

$net2ftp_messages["Choose a directory"] = "Scegli una cartella";
$net2ftp_messages["Please wait..."] = "Per favore attendi...";
$net2ftp_messages["Uploading... please wait..."] = "Sto caricando dei file... per favore attendi...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "Se il caricamento supera i <b>%1\$s secondi<\/b> permessi, dovrai riprovare con meno files alla volta o file piu&acute; piccoli.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "Questa finestra si chiudera&acute; automaticamente fra pochi secondi.";
$net2ftp_messages["Close window now"] = "Chiudi la finestra ora";

$net2ftp_messages["Upload files and archives"] = "Carica file e archivi";
$net2ftp_messages["Upload results"] = "Risultati del caricamento";
$net2ftp_messages["Checking files:"] = "Controllo file:";
$net2ftp_messages["Transferring files to the FTP server:"] = "Trasferimento dei file sul server FTP:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "Estrazione file dagli archivi e trasferimento dei file sul server FTP:";
$net2ftp_messages["Upload more files and archives"] = "Carica altri file e archivi";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "Restrizioni:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "La massima dimensione di un file e&acute; settata da net2ftp a <b>%1\$s kB</b> e dal PHP a <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Il tempo di esecuzione massimo e&acute; di <b>%1\$s secondi</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "La modalita&acute; di trasferimento FTP (ASCII o BINARY) sara&acute; determinata automaticamente, basandosi sull&acute;estensione del file";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Se la destinazione esiste gia&acute;, sara&acute; sovrascritta";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "Visualizza file %1\$s";
$net2ftp_messages["View image %1\$s"] = "Guarda l&acute;immagine %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "Visualizza filmato Macromedia ShockWave %1\$s";
$net2ftp_messages["Image"] = "Immagine";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "Per salvare l&acute;immagine, click con tasto destro e scegliere \"Salva immagine come...\"";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "Archivi Zip";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "Salva il file zip sul server FTP come:";
$net2ftp_messages["Email the zip file in attachment to:"] = "Manda il file zip in e-mail come allegato a:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "Nota che mandare i files non e&acute; anonimo: il tuo indirizzo IP cosi&acute; come l&acute;ora di invio saranno aggiunti al messaggio e-mail.";
$net2ftp_messages["Some additional comments to add in the email:"] = "Qualche commento aggiuntivo da inserire nell&acute;e-mail:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "Non hai inserito un nome del file per l&acute;archivio zip. Torna indietro ed inserisci un nome.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "L&acute;indirizzo e-mail che hai inserito (%1\$s) non sembra esser valido.<br />Per favore inserisci un indirizzo nel formato <b>username@domain.com</b>";

} // end zip

?>