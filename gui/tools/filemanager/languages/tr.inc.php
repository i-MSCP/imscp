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
$net2ftp_messages["en"] = "tr";

// HTML dir attribute: left-to-right (LTR) or right-to-left (RTL)
$net2ftp_messages["ltr"] = "ltr";

// CSS style: align left or right (use in combination with LTR or RTL)
$net2ftp_messages["left"] = "left";
$net2ftp_messages["right"] = "right";

// Encoding
$net2ftp_messages["iso-8859-1"] = "iso-8859-9";


// -------------------------------------------------------------------------
// Status messages
// -------------------------------------------------------------------------

// When translating these messages, keep in mind that the text should not be too long
// It should fit in the status textbox

$net2ftp_messages["Connecting to the FTP server"] = "FTP sunucusuna baglaniyor";
$net2ftp_messages["Logging into the FTP server"] = "FTP sunucusuna giris yapiyor";
$net2ftp_messages["Setting the passive mode"] = "Pasif moda geciyor";                 //geçiyor: ayarlýyor
$net2ftp_messages["Getting the FTP system type"] = "FTP sistem türünü aliyor";
$net2ftp_messages["Changing the directory"] = "Dizini degistiriyor";                  //dizini: klasörü
$net2ftp_messages["Getting the current directory"] = "Güncel dizini aliyor";          //dizini: klasörü
$net2ftp_messages["Getting the list of directories and files"] = "Dosya ve dizinlerin listesini aliyor";           //dizinlerin: klasörlerin
$net2ftp_messages["Parsing the list of directories and files"] = "Dosya ve dizinlerin listesini cözümlüyor";       //parsing: ?
$net2ftp_messages["Logging out of the FTP server"] = "FTP sunucusundan cikis yapiyor";
$net2ftp_messages["Getting the list of directories and files"] = "Dosya ve dizinlerin listesini aliyor";           //dizinlerin: klasörlerin
$net2ftp_messages["Printing the list of directories and files"] = "Dosya ve dizinlerin listesini yazdiriyor";      //dizinlerin: klasörlerin
$net2ftp_messages["Processing the entries"] = "Girisi isleme aliyor";
$net2ftp_messages["Processing entry %1\$s"] = "Girisi isleme aliyor %1\$s";
$net2ftp_messages["Checking files"] = "Dosyalari denetliyor";
$net2ftp_messages["Transferring files to the FTP server"] = "FTP sunucusuna dosyalari aktariyor";
$net2ftp_messages["Decompressing archives and transferring files"] = "Arsiv paketini aciyor ve dosyalari aktariyor";
$net2ftp_messages["Searching the files..."] = "Dosyalari ariyor...";
$net2ftp_messages["Uploading new file"] = "Yeni dosya yüklüyor";
$net2ftp_messages["Reading the file"] = "Dosyayi okuyor";
$net2ftp_messages["Parsing the file"] = "Dosyayi çözümlüyor";
$net2ftp_messages["Reading the new file"] = "Yeni dosyayi okuyor";
$net2ftp_messages["Reading the old file"] = "Eski dosyayi okuyor";
$net2ftp_messages["Comparing the 2 files"] = "2 Dosyayi karsilastiriyor";
$net2ftp_messages["Printing the comparison"] = "Karsilastirmayi yazdiriyor";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "FTP komutu gönderiyor %1\$s - %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "FTP sunucusundan arsiv paketini aliyor %1\$s - %2\$s";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "FTP sunucusunda gecici dizin yaratiyor";           //dizin: klasör
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Gecici diznin iznini ayarliyor";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "FTP sunucusuna net2ftp yükleyici yazilimini kopyaliyor";
$net2ftp_messages["Script finished in %1\$s seconds"] = "Yazilim %1\$s saniyede bitti";
$net2ftp_messages["Script halted"] = "Yazýlým durdu";

// Used on various screens
$net2ftp_messages["Please wait..."] = "Lütfen bekleyin...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Beklenmeyen durum dizisi: %1\$s. Çýkýyor.";
$net2ftp_messages["This beta function is not activated on this server."] = "Bu beta iþlevi, bu sunucuda açýlmadý.";    //activated: ?
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "Bu iþlev bu websitenin Yöneticisi tarafýndan kapatýldý.";    //disabled: ?


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "Dizin <b>%1\$s</b> yok veya seçilemiyor, bu yüzden yerine <b>%2\$s</b> dizini gösteriliyor.";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "Ana dizininiz <b>%1\$s</b> yok veya seçilemiyor.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "Dizin <b>%1\$s</b> seçilemiyor - bu dizini görüntüleyebilmek için yeterli haklara sahip olmayabilirsiniz veya o, var deðil.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Yasaklý anahtar kelimeleri içeren giriþler net2ftp kullanýlarak yönetilemez. Bu, net2ftp yoluyla Paypal veya Ebay dolandýrýcýlýðýnýn yüklenmesini önlemek içindir.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Çok büyük olan dosyalar, indirilemez, yüklenemez, kopyalanamaz, taþýnamaz, aranamaz, arþiv paketine eklenemez, arþiv paketinden çýkartýlamaz, görüntülemez veya düzenlemez; sadece yeniden adlandýrýlabilir, izinleri deðiþtirilebilir veya silinebilir.";
$net2ftp_messages["Execute %1\$s in a new window"] = "Yeni pencerede %1\$s gerçekleþtir";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "Lütfen en azýndan bir dizin veya dosya seçin!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "FTP sunucusu <b>%1\$s</b> izin verilen FTP sunucularý listesinde deðil.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "FTP sunucusu <b>%1\$s</b> yasaklý FTP sunucularý listesinde.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "FTP sunucusu portu %1\$s kullanýlmayabilir.";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "Your IP address (%1\$s) is not in the list of allowed IP addresses.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "IP adresiniz (%1\$s) yasaklý IP adresleri listesinde.";

// isAuthorizedDirectory()
$net2ftp_messages["Table net2ftp_users contains duplicate rows."] = "net2ftp_users tablosu çift dizeler içeriyor.";     //dublicate: ?

// logAccess(), logLogin(), logError()
$net2ftp_messages["Unable to execute the SQL query."] = "SQL sorgusunu gerçekleþtiremiyor.";
$net2ftp_messages["Unable to open the system log."] = "Unable to open the system log.";
$net2ftp_messages["Unable to write a message to the system log."] = "Unable to write a message to the system log.";

// checkAdminUsernamePassword()
$net2ftp_messages["You did not enter your Administrator username or password."] = "Yönetici kullanýcý adýnýzý veya þifrenizi girmediniz.";
$net2ftp_messages["Wrong username or password. Please try again."] = "Yanlýþ kullanýcý adý veya þifre. Lütfen tekrar deneyin.";


// -------------------------------------------------------------------------
// /includes/consumption.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to determine your IP address."] = "IP adresinizi belirleyemiyor.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "net2ftp_log_consumption_ipaddress tablosu çift dizeler içeriyor.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "net2ftp_log_consumption_ftpserver tablosu çift dizeler içeriyor.";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "<b>consumption_ipaddress_datatransfer</b> deðiþkeni sayýsal deðil.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "net2ftp_log_consumption_ipaddress tablosu güncellenemiyor.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "net2ftp_log_consumption_ipaddress tablosu contains çift giriþler içeriyor.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "net2ftp_log_consumption_ftpserver tablosu güncellenemiyor.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "net2ftp_log_consumption_ftpserver tablosu çift giriþler içeriyor.";
$net2ftp_messages["Table net2ftp_log_access could not be updated."] = "Table net2ftp_log_access could not be updated.";
$net2ftp_messages["Table net2ftp_log_access contains duplicate entries."] = "Table net2ftp_log_access contains duplicate entries.";


// -------------------------------------------------------------------------
// /includes/database.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "MySQL veritabanýna baðlanamýyor. Lütfen MySQL veritabaný ayarlarýnýzý net2ftp'nin ayar dosyasý settings.inc.php de denetleyin.";
$net2ftp_messages["Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "MySQL veritabanýný seçemiyor. Lütfen MySQL veritabaný ayarlarýnýzý net2ftp'nin ayar dosyasý settings.inc.php de denetleyin.";


// -------------------------------------------------------------------------
// /includes/errorhandling.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["An error has occured"] = "Bir hata oluþtu";
$net2ftp_messages["Go back"] = "Geri dön";
$net2ftp_messages["Go to the login page"] = "Giriþ sayfasýna git";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = " <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">PHP'nin FTP modülü</a> yüklü deðil.<br /><br /> Bu websitesinin yöneticisi bu FTP modülünü yüklemelidir. Yükleme talimatlarý <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />te veriliyor";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "FTP sunucusuna <b>%1\$s</b> portunda <b>%2\$s</b> baðlanamýyor.<br /><br />FTP sunucusunun adresinin bu olduðundan emin misiniz? Bu sýklýkla HTTP (web) sunucusununkinden farklýdýr. Lütfen yardým için ISS yardým masanýzla ya da sistem yöneticinizle iletiþim kurun.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "FTP sunucusuna <b>%1\$s</b> kullanýcý adýyla <b>%2\$s</b> giriþ yapamýyor.<br /><br />Kullanýcý adýnýzýn ve þifrenizin doðru olduðundan emin misiniz? Lütfen yardým için ISS yardým masanýzla ya da sistem yöneticinizle iletiþim kurun.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "FTP sunucusunda <b>%1\$s</b> pasif moda geçemiyor.";    //switch: ?

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Ýkinci (hedef) FTP sunucusuna <b>%1\$s</b> portunda <b>%2\$s</b> baðlanamýyor.<br /><br />Ýkinci (hedef) FTP sunucusunun adresinin bu olduðundan emin misiniz? Bu sýklýkla HTTP (web) sunucusununkinden farklýdýr. Lütfen yardým için ISS yardým masanýzla ya da sistem yöneticinizle iletiþim kurun.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Ýkinci (hedef) FTP sunucusuna <b>%1\$s</b> kullanýcý adýyla <b>%2\$s</b> giriþ yapamýyor.<br /><br />Kullanýcý adýnýzýn ve þifrenizin doðru olduðundan emin misiniz? Lütfen yardým için ISS yardým masanýzla ya da sistem yöneticinizle iletiþim kurun.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "Ýkinci (hedef) FTP sunucusunda <b>%1\$s</b> pasif moda geçemiyor.";   //switch: ?

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "<b>%1\$s</b> dosya ya da dizinini <b>%2\$s</b>e yeniden adlandýramýyor";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "Site komutunu <b>%1\$s</b> gerçekleþtiremiyor. CHMOD komutunun, Windows FTP sunucularýnda deðil de sadece Unix FTP sunucularýnda mümkün olduðuna dikkat edin.";    //note: ?
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "Dizin <b>%1\$s</b> baþarýlý bir þekilde <b>%2\$s</b> e chmod yapýldý.";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "<b>%1\$s</b> dizin içerisindeki giriþleri iþleme alýyor:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "<b>%1\$s</b> Dosya baþarýlý bir þekilde <b>%2\$s</b>e chmod yapýldý";
$net2ftp_messages["All the selected directories and files have been processed."] = "Tüm seçili dosya ve dizinler iþleme alýndý.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "<b>%1\$s</b> dizini silemiyor";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "<b>%1\$s</b> dosyayý silemiyor";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "<b>%1\$s</b> dizini yaratamýyor";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "Geçici dosya yaratamýyor";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "FTP sunucusundan <b>%1\$s</b> dosyasýný alamýyor ve <b>%2\$s</b> geçici dosya olarak kaydedemiyor.<br /> %3\$s dizininin izinlerini denetleyin.<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Geçici dosyayý açamýyor. %1\$s dizininin izinlerini denetleyin.";
$net2ftp_messages["Unable to read the temporary file"] = "Geçici dosyayý okuyamýyor";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Geçici dosyanýn idaresini kapatamýyor";    //handle: ?
$net2ftp_messages["Unable to delete the temporary file"] = "Geçici dosyayý silemiyor";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "Geçici dosya yaratamýyor. %1\$s dizininin izinlerini denetleyin.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Geçici dosyayý açamýyor. %1\$s dizininin izinlerini denetleyin.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "<b>%1\$s</b> geçici dosyaya diziyi yazamýyor.<br />%1\$s dizininin izinlerini denetleyin.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Geçici dosyanýn idaresini kapatamýyor";    //handle: ?
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "FTP sunucusuna <b>%1\$s</b> dosyasýný yerleþtiremiyor.<br />Dizinde yazma iznine sahip olamayabilirsiniz.";
$net2ftp_messages["Unable to delete the temporary file"] = "Geçici dosyayý silemiyor";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "<b>%1\$s</b> dizinini iþleme alýyor";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "<b>%1\$s</b> hedef dizini <b>%2\$s</b> kaynak dizininin aynýsý veya bir alt dizini , bu yüzden bu dizin atlanýlacak";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "The directory <b>%1\$s</b> contains a banned keyword,bu yüzden bu dizin atlanýlacak";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "<b>%1\$s</b> dizini yasaklý bir anahtar kelime içeriyor, taþýmayý iptal ediyor";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "<b>%1\$s</b> alt dizini yaratamýyor. Önceden var olabilir. Kopyalama/taþýma iþlemine devam ediyor...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Hedef alt dizin <b>%1\$s</b> yaratýldý";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "Dizin <b>%1\$s</b> seçilemiyor,bu yüzden bu dizin atlanýlacak";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "Alt dizini <b>%1\$s</b> silemiyor - boþ olmayabilir";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "Alt dizin <b>%1\$s</b> silindi";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "Dizinin <b>%1\$s</b> iþleme alýnmasý tamamlandý";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "Dosya için hedef <b>%1\$s</b> kaynaðýn aynýsý, bu yüzden bu dosya atlanýlacak";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "Dosya <b>%1\$s</b> yasaklý anahtar sözcük içeriyor, bu yüzden bu dosya atlanýlacak";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "Dosya <b>%1\$s</b> yasaklý anahtar sözcük içeriyor, taþýmayý iptal ediyor";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "Dosya <b>%1\$s</b> kopyalayabilmek için çok büyük, bu yüzden bu dosya atlanýlacak";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "Dosya <b>%1\$s</b> taþýmak için çok büyük, taþýmayý iptal ediyor";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "Dosyayý <b>%1\$s</b> kopyalayamýyor";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Dosyayý <b>%1\$s</b> kopyaladý";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Dosyayý <b>%1\$s</b> taþýyamýyor, taþýmayý iptal ediyor";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "Dosyayý <b>%1\$s</b> taþýyamýyor";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "<b>%1\$s</b> dosyayý silemiyor";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "Dosyayý <b>%1\$s</b> sildi";
$net2ftp_messages["All the selected directories and files have been processed."] = "Tüm seçili dosya ve dizinler iþleme alýndý.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "Uzaktaki dosyayý <b>%1\$s</b> yerel dosyaya  FTP <b>%2\$s</b> modunu kullanarak kopyalayamýyor";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "Dosyayý <b>%1\$s</b> silemiyor";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "Dosya aktarabilmek için çok büyük";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "Günlük sýnýra ulaþtý: dosya <b>%1\$s</b> aktarýlamayacak";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "Yerel dosyayý, uzaktaki dosyaya <b>%1\$s</b> FTP <b>%2\$s</b> modunu kullanarak kopyalayamýyor";
$net2ftp_messages["Unable to delete the local file"] = "Yerel dosyayý silemiyor";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "Geçici dosyayý silemiyor";
$net2ftp_messages["Unable to send the file to the browser"] = "Tarayýcýya dosyayý gönderemiyor";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "Geçici dosya yaratamýyor";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "Zip arþiv paketi dosyasý, FTP sunucusuna <b>%1\$s</b> olarak kaydedildi";
$net2ftp_messages["Requested files"] = "Ýstenilen dosyalar";

$net2ftp_messages["Dear,"] = "Sayýn,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "Birisi bu e-posta hesabýna (%1\$s) ekteki dosyanýn gönderilemsini istedi.";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "Eðer bunun hakkýnda hiçbir þey bilmiyorsanýz ya da bu kiþiye güvenmiyorsanýz, lütfen ekteki zip arþiv paketi dosyasýný açmadan bu e-postayý silin.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "Zip arþiv paketi dosyasýný açmazsanýz, içerisindeki dosyalarýn bilgisayarýnýza zarar veremeyeceðine dikkat edin.";   //note: ?
$net2ftp_messages["Information about the sender: "] = "Gönderen hakkýnda bilgiler: ";
$net2ftp_messages["IP address: "] = "IP adres: ";
$net2ftp_messages["Time of sending: "] = "Gönderme zamaný: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "Bu websitesine yüklenen net2ftp uygulamasý yoluyla gönderildi: ";
$net2ftp_messages["Webmaster's email: "] = "Web sahibi'nin e-postasý: ";
$net2ftp_messages["Message of the sender: "] = "Gönderenin iletisi: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp GNU/GPL lisansý altýnda piyasaya sürülen bedava bir yazýlýmdýr. Daha fazla bilgi için, http://www.net2ftp.com ye gidin.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "Zip arþivi paketi, <b>%1\$s</b>e gönderildi.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "Dosya <b>%1\$s</b> çok büyük. Bu dosya yüklenilmeyecek.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "Dosya <b>%1\$s</b> yasaklý anahtar sözcük içeriyor. Bu dosya yüklenilmeyecek.";
$net2ftp_messages["Could not generate a temporary file."] = "Geçici bir dosya oluþturamýyor.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "Dosya <b>%1\$s</b> taþýnamýyor";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "Dosya <b>%1\$s</b> TAMAM";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "Yüklenen dosyayý temp dizinine taþýyamýyor.<br /><br />Bu websitesinin yöneticisi net2ftp nin temp dizinini <b>777 chmod</b> yapmalý.";
$net2ftp_messages["You did not provide any file to upload."] = "Yüklemek için herhangi bir dosya saðlamadýnýz.";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "Dosya <b>%1\$s</b> FTP sunucusuna aktarýlamýyor";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "Dosya <b>%1\$s</b> FTP sunucusuna, FTP <b>%2\$s</b> modu kullanarak aktarýldý";
$net2ftp_messages["Transferring files to the FTP server"] = "FTP sunucusuna dosyalari aktariyor";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "Arþiv paketini %1\$s iþleme alýyor: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "Arþiv paketi <b>%1\$s</b> iþleme alýnmadý çünkü dosya uzantýsý tanýnmadý. þu an sadece zip, tar, tgz ve gz arþiv paketleri destekleniyor.";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Arþiv paketinden dosya ve dizinleri çýkartamýyor";
$net2ftp_messages["Archive contains filenames with ../ or ..\\ - aborting the extraction"] = "Archive contains filenames with ../ or ..\\ - aborting the extraction";
$net2ftp_messages["Created directory %1\$s"] = "Dizini %1\$s yarattý";
$net2ftp_messages["Could not create directory %1\$s"] = "Dizini %1\$s yaratamýyor";
$net2ftp_messages["Copied file %1\$s"] = "Dosyayý %1\$s kopyaladý";
$net2ftp_messages["Could not copy file %1\$s"] = "Dosyayý %1\$s kopyalayamýyor";
$net2ftp_messages["Unable to delete the temporary directory"] = "Geçici dizini silemiyor";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Geçici dosyayý %1\$s silemiyor";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "Site komutunu <b>%1\$s</b> gerçekleþtiremiyor";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "Ýþleminiz durduruldu";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "Net2ftp ile gerçekleþtirmek istediðiniz iþlem izin verilen %1\$s saniyeden daha fazla sürdü ve bu nedenle iþlem durduruldu.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "Bu süre sýnýrý, web sunucusunu herkes tarafýndan adil kullanýmýný garantiler.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "Ýþleminizi daha küçük iþlemlere bölmeye çalýþýn: dosyalarýnýzýn seçimini kýsýtlayýn ve en büyük dosyalarý atlayýn.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "Eðer gerçekten net2ftp  nin daha uzun süre alan büyük iþlemleri ele alabilmesi için, net2ftp yi kendi sunucunuza yüklemeyi düþünün.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "E-postayla göndermek için herhangi bir metin saðlamadýnýz!";
$net2ftp_messages["You did not supply a From address."] = "Kimden adresi saðlamadýnýz.";
$net2ftp_messages["You did not supply a To address."] = "Kime adresi saðlamdýnýz.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "Teknik problemler yüzünden e-posta <b>%1\$s</b>a gönderilemiyor.";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "Lütfen FTP sunucusu için kullanýcý adýnýzý ve þifrenizi girin ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Pop açýlan pencereye giriþ bilginizi doldurmadýnýz.<br />Aþaðýdaki \"Giriþ sayfasýna git\" üzerine týklayýn.";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "Net2ftp Yönetici paneline giriþ kapatýldý, çünkü settings.inc.php dosyasýnda þifre ayarlanmadý. O dosyaya þifre girin ve bu sayfayý tekrar yükleyin.";
$net2ftp_messages["Please enter your Admin username and password"] = "Lütfen Yönetici kullanýcý adýnýzý ve þifrenizi girin"; 
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Pop açýlan pencereye giriþ bilginizi doldurmadýnýz.<br />Aþaðýdaki \"Giriþ sayfasýna git\" üzerine týklayýn.";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "Net2ftp Yönetici paneli için yanlýþ kullanýcý adý veya þifre. Kullancý adý veya þifre, settings.inc.php dosyasýnda ayarlanabilir.";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "Mavi";
$net2ftp_messages["Grey"] = "Gri";
$net2ftp_messages["Black"] = "Siyah";
$net2ftp_messages["Yellow"] = "Sarý";
$net2ftp_messages["Pastel"] = "Pastel";

// getMime()
$net2ftp_messages["Directory"] = "Dizin";
$net2ftp_messages["Symlink"] = "Symlink";
$net2ftp_messages["ASP script"] = "ASP yazýlým";
$net2ftp_messages["Cascading Style Sheet"] = "Cascading Style Sheet";
$net2ftp_messages["HTML file"] = "HTML dosyasý";
$net2ftp_messages["Java source file"] = "Java kaynak dosyasý";
$net2ftp_messages["JavaScript file"] = "JavaScript dosyasý";
$net2ftp_messages["PHP Source"] = "PHP Kaynak";
$net2ftp_messages["PHP script"] = "PHP yazýlýmý";
$net2ftp_messages["Text file"] = "Metin dosyasý";
$net2ftp_messages["Bitmap file"] = "Bitmap dosyasý";
$net2ftp_messages["GIF file"] = "GIF dosyasý";
$net2ftp_messages["JPEG file"] = "JPEG dosyasý";
$net2ftp_messages["PNG file"] = "PNG dosyasý";
$net2ftp_messages["TIF file"] = "TIF dosyasý";
$net2ftp_messages["GIMP file"] = "GIMP dosyasý";
$net2ftp_messages["Executable"] = "Uygulama";
$net2ftp_messages["Shell script"] = "Shell yazýlýmý";
$net2ftp_messages["MS Office - Word document"] = "MS Ofis - Word belgesi";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Ofis - Excel çizelgesi";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Ofis - PowerPoint sunumu";
$net2ftp_messages["MS Office - Access database"] = "MS Ofis - Access veritabaný";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Ofis - Visio çizimi";
$net2ftp_messages["MS Office - Project file"] = "MS Ofis - Project dosyasý";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - Writer 6.0 belgesi";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - Writer 6.0 þablonu";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - Calc 6.0 çizelgesi";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - Calc 6.0 þablonu";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - Draw 6.0 belgesi";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - Draw 6.0 þablonu";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - Impress 6.0 sunumu";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - Impress 6.0 þablonu";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - Writer 6.0 evrensel belge";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - Math 6.0 belgesi";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - StarWriter 5.x belgesi";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - StarWriter 5.x evrensel belge";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - StarCalc 5.x çözümü";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - StarDraw 5.x belgesi";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - StarImpress 5.x sunumu";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - StarImpress Packed 5.x dosyasý";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - StarMath 5.x belgesi";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - StarChart 5.x belgesi";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - StarMail 5.x posta dosyasý";
$net2ftp_messages["Adobe Acrobat document"] = "Adobe Acrobat belgesi";
$net2ftp_messages["ARC archive"] = "ARC arþiv paketi";
$net2ftp_messages["ARJ archive"] = "ARJ arþiv paketi";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "GZ arþiv paketi";
$net2ftp_messages["TAR archive"] = "TAR arþiv paketi";
$net2ftp_messages["Zip archive"] = "Zip arþiv paketi";
$net2ftp_messages["MOV movie file"] = "MOV film dosyasý";
$net2ftp_messages["MPEG movie file"] = "MPEG film dosyasý";
$net2ftp_messages["Real movie file"] = "Real film dosyasý";
$net2ftp_messages["Quicktime movie file"] = "Quicktime film dosyasý";
$net2ftp_messages["Shockwave flash file"] = "Shockwave flaþ dosyasý";
$net2ftp_messages["Shockwave file"] = "Shockwave dosyasý";
$net2ftp_messages["WAV sound file"] = "WAV ses dosyasý";
$net2ftp_messages["Font file"] = "Font dosyasý";
$net2ftp_messages["%1\$s File"] = "%1\$s Dosyasý";
$net2ftp_messages["File"] = "Dosya";

// getAction()
$net2ftp_messages["Back"] = "Geri";
$net2ftp_messages["Submit"] = "Gönder";
$net2ftp_messages["Refresh"] = "Yenile";
$net2ftp_messages["Details"] = "Detaylar";
$net2ftp_messages["Icons"] = "Ikonlar";
$net2ftp_messages["List"] = "Liste";
$net2ftp_messages["Logout"] = "Çýkýþ";
$net2ftp_messages["Help"] = "Yardým";
$net2ftp_messages["Bookmark"] = "Sýk Kullanýlanlar";
$net2ftp_messages["Save"] = "Kaydet";
$net2ftp_messages["Default"] = "Varsayýlan";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Yardým Rehberi";
$net2ftp_messages["Forums"] = "Forumlar";
$net2ftp_messages["License"] = "Lisans";
$net2ftp_messages["Powered by"] = "Katkýlarýyla";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "Þimdi net2ftp forumlarýna götürülüyorsunuz. Bu forumlar, sadece net2ftp alakalý konular içindir - genel web barýndýrma sorularý için deðil.";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "Yönetici iþlevleri";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "Sürüm bilgisi";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "Net2ftp nin bu sürümü güncel.";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "En son sürüm bilgisi, net2ftp.com sunucusundan alýnamýyor. Tarayýcýnýzýn net2ftp.com sunucusundan küçük bir dosya yüklemesini engelleyebilen güvenlik ayarlarýný denetleyin.";   //retrieved: ?
$net2ftp_messages["Logging"] = "Giriyor";
$net2ftp_messages["Date from:"] = "Tarih den:";
$net2ftp_messages["to:"] = "e:";
$net2ftp_messages["Empty logs"] = "Boþ kayýtlar";
$net2ftp_messages["View logs"] = "Kayýtlarý görüntüle";
$net2ftp_messages["Go"] = "Git";
$net2ftp_messages["Setup MySQL tables"] = "MySQL tablolarýný kur";
$net2ftp_messages["Create the MySQL database tables"] = "MySQL veritabaný tablolarý yarat";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "Yönetici iþlevleri";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "Dosyanýn idaresi %1\$s açýlamýyor.";   //handle: ?
$net2ftp_messages["The file %1\$s could not be opened."] = "Dosya %1\$s açýlamýyor.";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "Dosyanýn idaresi %1\$s kapatýlamýyor."; //handle: ?
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "Sunucuya <b>%1\$s</b> baðlantý kurulamýyor. Lütfen girdiðiniz veritabaný ayarlarýný denetleyin.";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "Veritabaný <b>%1\$s</b> seçemiyor.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "SQL sorgu sayýsý <b>%1\$s</b> gerçekleþtirilemiyor.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "SQL sorgu sayýsý <b>%1\$s</b> baþarýlý bir þekilde gerçekleþtirildi.";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "Lütfen MySQL ayarlarýnýzý girin:";
$net2ftp_messages["MySQL username"] = "MySQL kullanýcý adý";
$net2ftp_messages["MySQL password"] = "MySQL þifresi";
$net2ftp_messages["MySQL database"] = "MySQL veritabaný";
$net2ftp_messages["MySQL server"] = "MySQL sunucusu";
$net2ftp_messages["This SQL query is going to be executed:"] = "Bu SQL sorgusu gerçekleþtirilecek:";
$net2ftp_messages["Execute"] = "Gerçekleþtir";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "Kullanýlan ayarlar:";
$net2ftp_messages["MySQL password length"] = "MySQL þifre uzunluðu";
$net2ftp_messages["Results:"] = "Sonuçlar:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "Yönetici iþlevleri";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "SQL sorgusunu <b>%1\$s</b> gerçekleþtiremiyor.";
$net2ftp_messages["No data"] = "Bilgi yok";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "Yönetici iþlevleri";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "Tablo <b>%1\$s</b> baþarýlý bir þekilde boþaltýldý.";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "Tablo <b>%1\$s</b> boþaltýlamýyor.";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "Tablo <b>%1\$s</b> baþarýlý bir þekilde onarýldý.";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "Tablo <b>%1\$s</b> onarýlamýyor.";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "Geliþmiþ iþlevler";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "Git";
$net2ftp_messages["Disabled"] = "Kapatýldý";    //disabled: ?
$net2ftp_messages["Advanced FTP functions"] = "Geliþmiþ FTP iþlevleri";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "FTP sunucusuna rastgele FTP komutlarý gönder";
$net2ftp_messages["This function is available on PHP 5 only"] = "Bu iþlev sadece PHP 5 de mümkün";
$net2ftp_messages["Troubleshooting functions"] = "Ýþlevlerin sorunlarýný gider";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "Bu websunucusunda net2ftp nin sorunlarýný gider";
$net2ftp_messages["Troubleshoot an FTP server"] = "FTP sunucusunun sorunlarýný gider";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Net2ftp liste çözümleme kurallarýný test et";
$net2ftp_messages["Translation functions"] = "Çeviri iþlevleri";
$net2ftp_messages["Introduction to the translation functions"] = "Çeviri iþlevlerine giriþ";
$net2ftp_messages["Extract messages to translate from code files"] = "Kod dosyalarýndan çevrilecek metin çýkart";
$net2ftp_messages["Check if there are new or obsolete messages"] = "Yeni ya da eskimiþ ileti olup olmadýðýný denetle";

$net2ftp_messages["Beta functions"] = "Beta iþlevleri";
$net2ftp_messages["Send a site command to the FTP server"] = "FTP sunucusuna bir site komutu gönder";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: þifre-korumalý dizin, tasarlanmýþ hata sayfalarý yarat";   //custom: ?
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: SQL sorgusu gerçekleþtir";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "Site komut iþlevleri bu web sunucusunda mümükün deðil.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "Apache iþlevleri bu web sunucusunda mümükün deðil.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "MySQL iþlevleri bu web sunucusunda mümükün deðil.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "Beklenmedik state2 dizisi. Çýkýyor.";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "FTP sunucusunun sorunlarýný gider";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "Baðlantý ayarlarý:";
$net2ftp_messages["FTP server"] = "FTP sunucusu";
$net2ftp_messages["FTP server port"] = "FTP sunucu port";
$net2ftp_messages["Username"] = "Kullanýcý adý";
$net2ftp_messages["Password"] = "Þifre";
$net2ftp_messages["Password length"] = "Þifre uzunluðu";
$net2ftp_messages["Passive mode"] = "Pasif mod";
$net2ftp_messages["Directory"] = "Dizin";
$net2ftp_messages["Printing the result"] = "Sonuçlarý yazdýrýyor";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "FTP sunucusuna baðlanýyor: ";
$net2ftp_messages["Logging into the FTP server: "] = "FTP sunucusuna giriþ yapýyor: ";
$net2ftp_messages["Setting the passive mode: "] = "Pasif moda geçiyor: ";
$net2ftp_messages["Getting the FTP server system type: "] = "FTP sunucusu sistem türünü alýyor: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Dizini %1\$s deðiþtiriyor: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "FTP sunucusundan dizin: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Dosya ve dizinlerin ham listesini alýyor: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Dosya ve dizinlerin ham listesini almak için ikince bir defa deniyor: ";
$net2ftp_messages["Closing the connection: "] = "Baðlantýyý kapatýyor: ";
$net2ftp_messages["Raw list of directories and files:"] = "Dosya ve dizinlerin ham listesi:";
$net2ftp_messages["Parsed list of directories and files:"] = "Dosya ve dizinlerin çözümlenmiþ listesi:";   //parsed: ?

$net2ftp_messages["OK"] = "TAMAM";
$net2ftp_messages["not OK"] = "TAMAM deðil";

} // end advanced_ftpserver


// -------------------------------------------------------------------------
// Advanced_parsing module
if ($net2ftp_globals["state"] == "advanced_parsing") {
// -------------------------------------------------------------------------

$net2ftp_messages["Test the net2ftp list parsing rules"] = "Net2ftp liste çözümleme kurallarýný test et";
$net2ftp_messages["Sample input"] = "Örnek girdi";
$net2ftp_messages["Parsed output"] = "Çözümlenmiþ sonuç";   //parsed: ?

} // end advanced_parsing


// -------------------------------------------------------------------------
// Advanced_webserver module
if ($net2ftp_globals["state"] == "advanced_webserver") {
// -------------------------------------------------------------------------

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "Net2ftp yüklemenizin sorunu çözün";
$net2ftp_messages["Printing the result"] = "Sonuçlarý yazdýrýyor";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "Checking if the FTP module of PHP is installed: ";
$net2ftp_messages["yes"] = "evet";
$net2ftp_messages["no - please install it!"] = "hayýr - lütfen onu yükle!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "Web sunucunuzdaki dizinin iznini denetliyor: küçük bir dosya /temp klasörüne yazýlacak ve sonra silinecek.";
$net2ftp_messages["Creating filename: "] = "Dosya adý yaratýyor: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "TAMAM. Dosya adý: %1\$s";
$net2ftp_messages["not OK"] = "TAMAM deðil";
$net2ftp_messages["OK"] = "TAMAM";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "TAMAM deðil. %1\$s dizinin iznini denetliyor";
$net2ftp_messages["Opening the file in write mode: "] = "Dosyayý yazma modunda açýyor: ";
$net2ftp_messages["Writing some text to the file: "] = "Dosyaya herhangi bir metin yazýor : ";
$net2ftp_messages["Closing the file: "] = "Dosyayý kapatýor: ";
$net2ftp_messages["Deleting the file: "] = "Dosyayý siliyor: ";

$net2ftp_messages["Testing the FTP functions"] = "FTP iþlevlerini test ediyor";
$net2ftp_messages["Connecting to a test FTP server: "] = "Test FTP sunucusuna baðlanýyor: ";
$net2ftp_messages["Connecting to the FTP server: "] = "FTP sunucusuna baðlanýyor: ";
$net2ftp_messages["Logging into the FTP server: "] = "FTP sunucusuna giriþ yapýyor: ";
$net2ftp_messages["Setting the passive mode: "] = "Pasif moda geçiyor: ";
$net2ftp_messages["Getting the FTP server system type: "] = "FTP sunucusu sistem türünü alýyor: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Dizini %1\$s deðiþtiriyor: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "FTP sunucusundan dizin: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Dosya ve dizinlerin ham listesini alýyor: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Dosya ve dizinlerin ham listesini almak için ikince bir defa deniyor: ";
$net2ftp_messages["Closing the connection: "] = "Baðlantýyý kapatýyor: ";
$net2ftp_messages["Raw list of directories and files:"] = "Dosya ve dizinlerin ham listesi:";
$net2ftp_messages["Parsed list of directories and files:"] = "Dosya ve dizinlerin çözümlenmiþ listesi:";   //parsed: ?
$net2ftp_messages["OK"] = "TAMAM";
$net2ftp_messages["not OK"] = "TAMAM deðil";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "Bu baðlantýyý sýk kullanýlanlarýnýza ekleyin:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: baðlantýa sað týklayýn ve \"Sýk Kullanýlanlara Ekle...\"seçin ";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: baðlantýya sað týklayýn ve \"Yer imlerine ekle...\"seçin";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "Not: bu sýk kullanýlanlarý kullandýðýnýz zaman, pop açýlan pencere size kullanýcý adýnýzý ve þifrenizi soracak.";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "Dizin seç";
$net2ftp_messages["Please wait..."] = "Lütfen bekleyin...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = " \' adýný içeren dizinler doðru bir þekilde gösterilemez. sadece silinebilir. Lütfen geri dönün ve bir baþka alt dizin seçin.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "Günlük sýnýrý aþtý: bilgi aktaramayacaksýnýz";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "Herkesin web sunucusunu adil bir þekilde kullanýmýný garanti etmek için, bilgi aktarým hacmi ve yazýlým uygulama süresi, kullanýcý baþýna ve günlük olarak sýnýrlandýrýlmýþtýr. Bir kez bu sýnýra ulaþýldýðýnda, FTP sunucusunu hala gezebilir fakat ondan/ona bilgi aktaramazsýnýz.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "Eðer sýnýrsýz kullanýma ihtiyaç duyuyorsanýz, lütfen net2ftpyi kendi web sunucunuza yükleyin.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "Yeni Dizin";
$net2ftp_messages["New file"] = "Yeni Dosya";
$net2ftp_messages["HTML templates"] = "HTML þablonlarý";
$net2ftp_messages["Upload"] = "Yükle";
$net2ftp_messages["Java Upload"] = "Java ile Yükle";
$net2ftp_messages["Flash Upload"] = "Flash Upload";
$net2ftp_messages["Install"] = "Kur";
$net2ftp_messages["Advanced"] = "Geliþmiþ";
$net2ftp_messages["Copy"] = "Kopyala";
$net2ftp_messages["Move"] = "Taþý";
$net2ftp_messages["Delete"] = "Sil";
$net2ftp_messages["Rename"] = "Yeniden Adlandýr";
$net2ftp_messages["Chmod"] = "Chmod";
$net2ftp_messages["Download"] = "Ýndir";
$net2ftp_messages["Unzip"] = "Arþiv aç";
$net2ftp_messages["Zip"] = "Zip";
$net2ftp_messages["Size"] = "Boyut";
$net2ftp_messages["Search"] = "Arama";
$net2ftp_messages["Go to the parent directory"] = "Ana dizine git";
$net2ftp_messages["Go"] = "Git";
$net2ftp_messages["Transform selected entries: "] = "Seçili giriþleri dönüþtür: ";
$net2ftp_messages["Transform selected entry: "] = "Seçili giriþi dönüþtür: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "%1\$s dizininde yeni bir alt dizin yap";
$net2ftp_messages["Create a new file in directory %1\$s"] = "%1\$s dizininde yeni bir dosya yarat";
$net2ftp_messages["Create a website easily using ready-made templates"] = "Hazýr-yapýlmýþ þablon kullanarak kolayca bir websitesi yaratýn";
$net2ftp_messages["Upload new files in directory %1\$s"] = "%1\$s dizinine yeni dosya yükle";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Java aplet kullanarak dizin ve dosyalarý yükle";
$net2ftp_messages["Upload files using a Flash applet"] = "Upload files using a Flash applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Yazýlým paketlerini yükle (web sunucusunda PHP gerektirir)";
$net2ftp_messages["Go to the advanced functions"] = "Geliþmiþ iþlevlere git";
$net2ftp_messages["Copy the selected entries"] = "Seçili giriþleri kopyala";
$net2ftp_messages["Move the selected entries"] = "Seçili giriþleri taþý";
$net2ftp_messages["Delete the selected entries"] = "Seçili giriþleri sil";
$net2ftp_messages["Rename the selected entries"] = "Seçili giriþleri yeniden adlandýr";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "Seçili giriþleri chmod (sadece Unix/Linux/BSD sunucularýnda çalýþýyor)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "Tüm seçili giriþleri içeren bir zip dosyasý indir";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "FTP sunucusundaki seçili arþiv paketlerini aç";
$net2ftp_messages["Zip the selected entries to save or email them"] = "Kaydedebilmek veya e-posta ile gönderebilmek için, seçili giriþleri ziple";
$net2ftp_messages["Calculate the size of the selected entries"] = "Seçili giriþlerin boyutunu hesapla";
$net2ftp_messages["Find files which contain a particular word"] = "Belirli bir sözcüðü içeren dosyalarý bul";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "Alçalan sýrada %1\$s ile sýralamak için týklayýn";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "Yükselen sýrada %1\$s ile sýralamak için týklayýn";
$net2ftp_messages["Ascending order"] = "Yükselen sýralama";
$net2ftp_messages["Descending order"] = "Alçalan sýralama";
$net2ftp_messages["Upload files"] = "Dosyalarý yükle";
$net2ftp_messages["Up"] = "Yukarý";
$net2ftp_messages["Click to check or uncheck all rows"] = "Tüm dizileri iþaretlemek ya da iþareti kaldýrmak için týklayýn";
$net2ftp_messages["All"] = "Tümü";
$net2ftp_messages["Name"] = "Ad";
$net2ftp_messages["Type"] = "Tür";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "sahip";
$net2ftp_messages["Group"] = "Grup";
$net2ftp_messages["Perms"] = "Ýzinler";
$net2ftp_messages["Mod Time"] = "Mod Zamaný";
$net2ftp_messages["Actions"] = "Eylemler";
$net2ftp_messages["Select the directory %1\$s"] = "Dizini %1\$s seç";
$net2ftp_messages["Select the file %1\$s"] = "Dosyayý %1\$s seç";
$net2ftp_messages["Select the symlink %1\$s"] = "Symlink %1\$s seç";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "Alt dizine %1\$s git";
$net2ftp_messages["Download the file %1\$s"] = "Dosyayý %1\$s indir";
$net2ftp_messages["Follow symlink %1\$s"] = "Symlink %1\$s takip et";
$net2ftp_messages["View"] = "Görüntüle";
$net2ftp_messages["Edit"] = "Düzenle";
$net2ftp_messages["Update"] = "Güncelle";
$net2ftp_messages["Open"] = "Aç";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Dosyanýn %1\$s vurgulanmýþ kaynak kodunu görüntüle";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Dosyanýn %1\$s kaynak kodunu düzenle";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "Dosyanýn %1\$s yeni sürümünü yükle ve deðiþiklikleri birleþtir";   //mere: ?
$net2ftp_messages["View image %1\$s"] = "Resmi %1\$s görüntüle";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "HTTP web sunucunuzdan %1\$s dosyayý görüntüle";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(Not: Eðer kendi alan adýnýza sahip deðilseniz, bu baðlantý çalýþmayabilir.)";
$net2ftp_messages["This folder is empty"] = "Bu klasör boþ";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "Dizinler";
$net2ftp_messages["Files"] = "Dosyalar";
$net2ftp_messages["Symlinks"] = "Symlinks";
$net2ftp_messages["Unrecognized FTP output"] = "Tanýnmayan FTP sonucu";
$net2ftp_messages["Number"] = "Sayý";
$net2ftp_messages["Size"] = "Boyut";
$net2ftp_messages["Skipped"] = "Atlanýlan";
$net2ftp_messages["Data transferred from this IP address today"] = "Data transferred from this IP address today";
$net2ftp_messages["Data transferred to this FTP server today"] = "Data transferred to this FTP server today";

// printLocationActions()
$net2ftp_messages["Language:"] = "Dil:";
$net2ftp_messages["Skin:"] = "Kaplama:";
$net2ftp_messages["View mode:"] = "Görüntüleme modu:";
$net2ftp_messages["Directory Tree"] = "Dizin aðacý";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "Yeni pencerede %1\$s gerçekleþtir";
$net2ftp_messages["This file is not accessible from the web"] = "Bu sayfaya webten ulaþýlabilir deðil";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "Alt dizine gitmek için çift týklayýn:";
$net2ftp_messages["Choose"] = "Seç";
$net2ftp_messages["Up"] = "Yukarý";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "Seçili dizin ve dosyalarýn boyutu";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "Seçili dizin ve dosyalarýn aldýðý toplam boyut:";
$net2ftp_messages["The number of files which were skipped is:"] = "Atlanýlan dosyalarýn sayýsý:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "Dizin ve dosyalarý chmod";
$net2ftp_messages["Set all permissions"] = "Tüm izinleri ayarla";
$net2ftp_messages["Read"] = "Oku";
$net2ftp_messages["Write"] = "Yaz";
$net2ftp_messages["Execute"] = "Gerçekleþtir";
$net2ftp_messages["Owner"] = "sahip";
$net2ftp_messages["Group"] = "Grup";
$net2ftp_messages["Everyone"] = "Herkes";
$net2ftp_messages["To set all permissions to the same values, enter those permissions above and click on the button \"Set all permissions\""] = "Tüm izinleri ayný deðere ayarlamak için, yukarýdaki o izinleri girin ve \"Tüm izinleri ayarla\" düðmesine týklayýn";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "Diznin <b>%1\$s</b> izinlerini þuna ayarla: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "Dosyanýn <b>%1\$s</b> izinlerini þuna ayarla: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "symlink <b>%1\$s</b> izinlerini þuna ayarla: ";      //smlink: ?
$net2ftp_messages["Chmod value"] = "Chmod deðeri";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "Bu dizin içerisindeki alt dizinleri de chmod";
$net2ftp_messages["Chmod also the files within this directory"] = "Bu dizin içerisindeki dosyalarý da chmod";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "chmod sayýsý <b>%1\$s</b> 000-777 aralýðýnýn dýþýndadýr. Lütfen tekrar deneyin.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "Dizin seç";
$net2ftp_messages["Copy directories and files"] = "Dizin ve dosyalarý kopyala";
$net2ftp_messages["Move directories and files"] = "Dizin ve dosyalarý taþý";
$net2ftp_messages["Delete directories and files"] = "Dizin ve dosyalarý sil";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "Bu dizin ve dosyalarý silmek istediðinizden emin misiniz?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "Seçili dizinlerin tüm dosyalarý ve alt dizinleri ayrýca silinecek!";
$net2ftp_messages["Set all targetdirectories"] = "Tüm hedef dizinleri ayarla";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Sýradan bir hedef dizin ayarlamak için, yukarýdaki metin kutusuna hedef dizini girin ve \"Tüm hedef dizinleri ayarla\"düðmesine týklayýn.";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Not: içerisine herhangi bir þey kopyalanmadan önce, hedef dizin önceden var olmalý.";
$net2ftp_messages["Different target FTP server:"] = "Farklý hedef FTP sunucusu:";
$net2ftp_messages["Username"] = "Kullanýcý adý";
$net2ftp_messages["Password"] = "Þifre";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "Eðer dosyalarý, ayný FTP sunucusuna kopyalamak istiyorsanýz, boþ býrakýn.";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "Eðer dosyalarý bir baþka FTP sunucusuna kopyalamak istiyorsanýz, giriþ bilginizi girin.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "Eðer dosyalarý, ayný FTP sunucusuna taþýmak istiyorsanýz, boþ býrakýn.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "Eðer dosyalarý bir baþka FTP sunucusuna taþýmak istiyorsanýz, giriþ bilginizi girin.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "Dizini <b>%1\$s</b> þuna kopyala:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "Dizini <b>%1\$s</b> þuna taþý:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "Dizin <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "Dosyayý <b>%1\$s</b> þuna kopyala:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "Dosyayý <b>%1\$s</b> þuna taþý:";
$net2ftp_messages["File <b>%1\$s</b>"] = "Dosya <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "symlink <b>%1\$s</b> þuna kopyala:";    //symlink: ?
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "symlink <b>%1\$s</b> þuna taþý:";       //symlink: ?
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Symlink <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "Hedef dizin:";
$net2ftp_messages["Target name:"] = "Hedef adý:";
$net2ftp_messages["Processing the entries:"] = "Giriþi iþleme alýyor:";

} // end copymovedelete


// -------------------------------------------------------------------------
// Download file module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// EasyWebsite module
if ($net2ftp_globals["state"] == "easyWebsite") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create a website in 4 easy steps"] = "4 kolay adýmda bir websitesi yarat";
$net2ftp_messages["Template overview"] = "Þablon önizleme";
$net2ftp_messages["Template details"] = "Þablon detaylarý";
$net2ftp_messages["Files are copied"] = "Dosyalar kopyalandý";
$net2ftp_messages["Edit your pages"] = "Sayfalarýný düzenle";

// Screen 1 - printTemplateOverview
$net2ftp_messages["Click on the image to view the details of a template."] = "Bir þablonun detaylarýný görüntülemek için resme týklayýn.";
$net2ftp_messages["Back to the Browse screen"] = "Tarama ekranýna geri dön";
$net2ftp_messages["Template"] = "Þablon";
$net2ftp_messages["Copyright"] = "Telif hakký";
$net2ftp_messages["Click on the image to view the details of this template"] = "Bu þablonun detaylarýný görüntülemek için resme týklayýn";

// Screen 2 - printTemplateDetails
$net2ftp_messages["The template files will be copied to your FTP server. Existing files with the same filename will be overwritten. Do you want to continue?"] = "Þablon dosyalarý, FTP sunucunuza kopyalanacak. Ayný isimle var olan dosyalar üzerine yazýlacak. Devam etmek istiyor musunuz?";
$net2ftp_messages["Install template to directory: "] = "Þablonu dizine yükle: ";
$net2ftp_messages["Install"] = "Kur";
$net2ftp_messages["Size"] = "Boyut";
$net2ftp_messages["Preview page"] = "Önizleme sayfasý";
$net2ftp_messages["opens in a new window"] = "yeni pencerede açar";

// Screen 3
$net2ftp_messages["Please wait while the template files are being transferred to your server: "] = "Þablon dosyalarý sunucunuza aktarýlýrken lütfen bekleyin: ";
$net2ftp_messages["Done."] = "Bitti.";
$net2ftp_messages["Continue"] = "Devam";

// Screen 4 - printEasyAdminPanel
$net2ftp_messages["Edit page"] = "Sayfayý düzenle";
$net2ftp_messages["Browse the FTP server"] = "FTP sunucusunu gez";
$net2ftp_messages["Add this link to your favorites to return to this page later on!"] = "Daha sonra bu sayfaya geri dönebilmek için bu baðlantýyý sýk kullanýlanlarýnýza ekleyin!";
$net2ftp_messages["Edit website at %1\$s"] = "%1\$s de websiteni düzenle";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: baðlantýa sað týklayýn ve \"Sýk Kullanýlanlara Ekle...\"seçin ";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: baðlantýya sað týklayýn ve \"Yer imlerine ekle...\"seçin";

// ftp_copy_local2ftp
$net2ftp_messages["WARNING: Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing..."] = "UYARI: <b>%1\$s</b> alt dizinini yaratamýyor. Önceden var olabilir. Devam ediyor...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Hedef alt dizin <b>%1\$s</b> yaratýldý";
$net2ftp_messages["WARNING: Unable to copy the file <b>%1\$s</b>. Continuing..."] = "UYARI: <b>%1\$s</b> dosyasýný kopyalayamýyor. Devam ediyor...";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Dosyayý <b>%1\$s</b> kopyaladý";
}


// -------------------------------------------------------------------------
// Edit module
if ($net2ftp_globals["state"] == "edit") {
// -------------------------------------------------------------------------

// /modules/edit/edit.inc.php
$net2ftp_messages["Unable to open the template file"] = "Þablon dosyasýný açamýyor";
$net2ftp_messages["Unable to read the template file"] = "Þablon dosyasýný okuyamýyor";
$net2ftp_messages["Please specify a filename"] = "Lütfen bir dosya adý belirtin";
$net2ftp_messages["Status: This file has not yet been saved"] = "Durum: Bu sayfa henüz kaydedilmedi";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "Durum: <b>%1\$s</b>de %2\$s modu kullanýlarak kaydedildi";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "Durum: <b>Bu sayfa kaydedilemiyor</b>";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "Dizin: ";
$net2ftp_messages["File: "] = "Dosya: ";
$net2ftp_messages["New file name: "] = "Yeni dosya adý: ";
$net2ftp_messages["Character encoding: "] = "Karakter kodlamasý: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "Not: metin alaný türünü deðiþtirmek, deðiþiklikleri kaydedecektir";
$net2ftp_messages["Copy up"] = "Yukarýyý kopyala";     //copy up: ?
$net2ftp_messages["Copy down"] = "Aþaðýyý kopyala";    //copy down: ?

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "Dosya ve dizinleri ara";
$net2ftp_messages["Search again"] = "Tekrar ara";
$net2ftp_messages["Search results"] = "Arama sonuçlarý";
$net2ftp_messages["Please enter a valid search word or phrase."] = "Lütfen geçerli bir arama szcüðü ya da sz öbeði girin.";
$net2ftp_messages["Please enter a valid filename."] = "Lütfen geçerli bir dosya adý girin.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "Lütfen \"kimden\" metin kutusuna geçerli bir dosya boyutu girin, örneðin 0.";      //from: ?
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "Lütfen \"kime\" metin kutusuna geçerli bir dosya boyutu girin, örneðin 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "Lütfen \"kimden\" metin kutusuna yýl-ay-gün biçiminde geçerli bir tarih girin.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "Lütfen \"kime\" metin kutusuna yýl-ay-gün biçiminde geçerli bir tarih girin.";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "Sözcük <b>%1\$s</b>, seçili alt dizinlerde ve dosyalarda bulunamadý.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "Sözcük <b>%1\$s</b>, aþaðýdaki dosyalarda bulundu:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "Kelime ya da söz öbeði ara";
$net2ftp_messages["Case sensitive search"] = "Büyük/küçük harf hassas arama";
$net2ftp_messages["Restrict the search to:"] = "Aramayý þuna kýsýtla:";
$net2ftp_messages["files with a filename like"] = "Benzer adlý dosyalar";
$net2ftp_messages["(wildcard character is *)"] = "(joker karakter: *)";              //wildcard: ?
$net2ftp_messages["files with a size"] = "boyutlu dosyalar";
$net2ftp_messages["files which were last modified"] = "son deðiþtirilen dosyalar";
$net2ftp_messages["from"] = "kimden";
$net2ftp_messages["to"] = "kime";

$net2ftp_messages["Directory"] = "Dizin";
$net2ftp_messages["File"] = "Dosya";
$net2ftp_messages["Line"] = "Satýr";
$net2ftp_messages["Action"] = "Eylem";
$net2ftp_messages["View"] = "Görüntüle";
$net2ftp_messages["Edit"] = "Düzenle";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Dosyanýn %1\$s vurgulanmýþ kaynak kodunu görüntüle";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Dosyanýn %1\$s kaynak kodunu düzenle";

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
$net2ftp_messages["Install software packages"] = "Yazýlým paketlerini yükle";
$net2ftp_messages["Unable to open the template file"] = "Þablon dosyasýný açamýyor";
$net2ftp_messages["Unable to read the template file"] = "Þablon dosyasýný okuyamýyor";
$net2ftp_messages["Unable to get the list of packages"] = "Paketlerin listesini alamýyor";

// /skins/blue/install1.template.php
$net2ftp_messages["The net2ftp installer script has been copied to the FTP server."] = "Net2ftp yükleyici yazýlýmý FTP sunucusuna kopyalandý.";
$net2ftp_messages["This script runs on your web server and requires PHP to be installed."] = "Bu yazýlým, web sunucunuzda çalýþýr ve yüklenebilmesi için PHP gerektirir.";
$net2ftp_messages["In order to run it, click on the link below."] = "Çalýþtýrmak için, aþaðýdaki baðlantýya týklayýn.";
$net2ftp_messages["net2ftp has tried to determine the directory mapping between the FTP server and the web server."] = "net2ftp, FTP sunucusu ile web sunucusu arasýnda dizin haritasý belirlemeyi denedi .";    //mapping: ?
$net2ftp_messages["Should this link not be correct, enter the URL manually in your web browser."] = "Eðer bu baðlantý doðru deðilse, URL yi web tarayýcýnýza elinizle girin.";

} // end install


// -------------------------------------------------------------------------
// Java upload module
if ($net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload directories and files using a Java applet"] = "Java aplet kullanarak dizin ve dosyalarý yükle";
$net2ftp_messages["Number of files:"] = "Dosyalarýn sayýsý:";
$net2ftp_messages["Size of files:"] = "Dosyalarýn boyutu:";
$net2ftp_messages["Add"] = "Ekle";
$net2ftp_messages["Remove"] = "Kaldýr";
$net2ftp_messages["Upload"] = "Yükle";
$net2ftp_messages["Add files to the upload queue"] = "Dosyalarý yükleme kuyruðuna ekle";
$net2ftp_messages["Remove files from the upload queue"] = "Dosyalarý yükleme kuyruðundan kaldýr";
$net2ftp_messages["Upload the files which are in the upload queue"] = "Yükleme kuyruðunda olan dosyalarý yükleyin";
$net2ftp_messages["Maximum server space exceeded. Please select less/smaller files."] = "Maximum sunucu alanýný aþtý. Lütfen daha az/daha küçük dosya seçin.";
$net2ftp_messages["Total size of the files is too big. Please select less/smaller files."] = "Dosyalarýn toplam boyutu çok büyük. Lütfen daha az/daha küçük dosya seçin.";
$net2ftp_messages["Total number of files is too high. Please select fewer files."] = "Dosyalarýn toplam sayýsý çok yüksek. Lütfen daha az dosya seçin.";
$net2ftp_messages["Note: to use this applet, Sun's Java plugin must be installed (version 1.4 or newer)."] = "Not: bu apleti kullanmak için, Sun'ýn Java eklentisi yüklenmiþ olmalý (sürüm 1.4 veya daha yeni).";

} // end jupload



// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login") {
// -------------------------------------------------------------------------
$net2ftp_messages["Login!"] = "Giriþ!";
$net2ftp_messages["Once you are logged in, you will be able to:"] = "Bir kez giriþ yaptýðýnýz zaman, þunu yapabileceksiniz:";
$net2ftp_messages["Navigate the FTP server"] = "FTP sunucusunu yönlendirme";
$net2ftp_messages["Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."] = "Bir kez giriþ yaptýðýnýz zaman, dizinden dizine tarama yapabilir ve tüm alt dizinleri ve dosyalarý görebilirsiniz.";
$net2ftp_messages["Upload files"] = "Dosyalarý yükle";
$net2ftp_messages["There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet."] = "Dosyalarý yüklemek için 3 farklý yol var: standard yükleme formu, yükle-ve-aç iþlevselliði, ve Java Apleti.";          //Applet: ?
$net2ftp_messages["Download files"] = "Dosyalarý indir";
$net2ftp_messages["Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive."] = "Bir dosyayý hýzlýca indirmek için dosya adýna týklayýn.<br />Çoklu dosya seçin ve Ýndir e týklayýn; seçili dosyalar, zip arþiv paketi olarak indirilecektir.";
$net2ftp_messages["Zip files"] = "Zip dosyalarý";
$net2ftp_messages["... and save the zip archive on the FTP server, or email it to someone."] = "... ve zip arþiv paketini FTP sunucusunda kaydet, veya birisine e-posta ile gönder.";
$net2ftp_messages["Unzip files"] = "Dosyalarý çýkart";
$net2ftp_messages["Different formats are supported: .zip, .tar, .tgz and .gz."] = "Farklý biçimler destekleniyor: .zip, .tar, .tgz ve .gz.";
$net2ftp_messages["Install software"] = "Yazýlým yükle";
$net2ftp_messages["Choose from a list of popular applications (PHP required)."] = "Sevilen uygulamalarýn listesinden seçin(PHP gerekli).";
$net2ftp_messages["Copy, move and delete"] = "Kopyala, taþý ve sil";
$net2ftp_messages["Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted."] = "Dizinler akýcý bir þekilde ele alýnýr, yani içeriði (alt dizinler ve dosyalar) ayrýca kopyalancak, taþýncak veya silinecek.";            //recursively: ?
$net2ftp_messages["Copy or move to a 2nd FTP server"] = "2. FTP sunucusuna kopyala veya taþý";
$net2ftp_messages["Handy to import files to your FTP server, or to export files from your FTP server to another FTP server."] = "FTP sunucunuza dosya aktarmak veya FTP sunucunuzdan bir baþka FTP sunucusuna dosya aktarmak için kullanýþlýdýr.";
$net2ftp_messages["Rename and chmod"] = "Yenidenn adlandýr ve chmod";
$net2ftp_messages["Chmod handles directories recursively."] = "Chmod dizinleri akýcý bir þekilde ele alýr.";              //recursively: ?
$net2ftp_messages["View code with syntax highlighting"] = "Sözdizim vurgulamasý ile kodu görüntüle";
$net2ftp_messages["PHP functions are linked to the documentation on php.net."] = "PHP iþlevleri, php.net deki destek belgelerine baðlýdýr.";
$net2ftp_messages["Plain text editor"] = "Basit metin editörü";
$net2ftp_messages["Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server."] = "Tarayýcýnýzdan metni düzenleyin; her ne zaman deðiþiklikleri kaydederseniz, yeni dosya FTP sunucusuna aktarýlacaktýr.";
$net2ftp_messages["HTML editors"] = "HTML editörleri";
$net2ftp_messages["Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from."] = "Ne-Gördüðün-Ne-Elde-Ettiðindir (WYSIWYG) formunda HTML yi düzenle; seçebileceðiniz 2 farklý editör var.";
$net2ftp_messages["Code editor"] = "Kod editörü";
$net2ftp_messages["Edit HTML and PHP in an editor with syntax highlighting."] = "Sözdizim vurgulamalý bir editörde HTML ve PHP düzenle.";
$net2ftp_messages["Search for words or phrases"] = "Sözcük ya da söz öbeði arayýn";
$net2ftp_messages["Filter out files based on the filename, last modification time and filesize."] = "Dosyalarý adýna,son deðiþtirme zamanýna ve boyutuna dayalý olarak filtrele.";
$net2ftp_messages["Calculate size"] = "Boyutu hesapla";
$net2ftp_messages["Calculate the size of directories and files."] = "Dosya ve dizinlerin boyutunu hesapla.";

$net2ftp_messages["FTP server"] = "FTP sunucusu";
$net2ftp_messages["Example"] = "Örnek";
$net2ftp_messages["Port"] = "Port";
$net2ftp_messages["Username"] = "Kullanýcý adý";
$net2ftp_messages["Password"] = "Þifre";
$net2ftp_messages["Anonymous"] = "Anonim";
$net2ftp_messages["Passive mode"] = "Pasif mod";
$net2ftp_messages["Initial directory"] = "Ýlk dizin";
$net2ftp_messages["Language"] = "Dil";
$net2ftp_messages["Skin"] = "Kaplama";
$net2ftp_messages["FTP mode"] = "FTP modu";
$net2ftp_messages["Automatic"] = "Otomatik";
$net2ftp_messages["Login"] = "Giriþ";
$net2ftp_messages["Clear cookies"] = "Çerezleri temizle";
$net2ftp_messages["Admin"] = "Yönetici";
$net2ftp_messages["Please enter an FTP server."] = "Lütfen bir FTP sunucusu girin.";
$net2ftp_messages["Please enter a username."] = "Lütfen bir kullanýcý adý girin.";
$net2ftp_messages["Please enter a password."] = "Lütfen bir þifre girin.";

} // end login


// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login_small") {
// -------------------------------------------------------------------------

$net2ftp_messages["Please enter your Administrator username and password."] = "Lütfen yönetici kullanýcý adýnýzý ve þifrenizi girin.";
$net2ftp_messages["Please enter your username and password for FTP server <b>%1\$s</b>."] = "Lütfen FTP sunucusu için kullanýcý adýnýzý ve þifrenizi girin<b>%1\$s</b>.";
$net2ftp_messages["Username"] = "Kullanýcý adý";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Oturumunuz sona erdi; lütfen <b>%1\$s</b> devam etmek için,FTP sunucusu için þifrenizi girin.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "IP adresiniz deðiþti; lütfen <b>%1\$s</b> devam etmek için,FTP sunucusu için þifrenizi girin.";
$net2ftp_messages["Password"] = "Þifre";
$net2ftp_messages["Login"] = "Giriþ";
$net2ftp_messages["Continue"] = "Devam";

} // end login_small


// -------------------------------------------------------------------------
// Logout module
if ($net2ftp_globals["state"] == "logout") {
// -------------------------------------------------------------------------

// logout.inc.php
$net2ftp_messages["Login page"] = "Giriþ sayfasý";

// logout.template.php
$net2ftp_messages["You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>."] = "FTP sunucusundan çýkýþ yaptýnýz. Geri giriþ yapmak için, <a href=\"%1\$s\" title=\"Giriþ sayfasý (accesskey l)\" accesskey=\"l\">bu baðlantýyý takip edin</a>.";
$net2ftp_messages["Note: other users of this computer could click on the browser's Back button and access the FTP server."] = "Not: bu bilgisayarýn diðer kullanýcýlarý,tarayýcýnýn Geri düðmesine týklayabilir ve FTP sunucusuna ulaþabilir.";
$net2ftp_messages["To prevent this, you must close all browser windows."] = "Bunu önlemek için, tüm tarayýcý pencerelerini kapatmalýsýnýz.";
$net2ftp_messages["Close"] = "Kapat";
$net2ftp_messages["Click here to close this window"] = "Bu pencereyi kapatmak için buraya týklayýnýz";

} // end logout


// -------------------------------------------------------------------------
// New directory module
if ($net2ftp_globals["state"] == "newdir") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create new directories"] = "Yeni dizinler yarat";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "Yeni dizinler, <b>%1\$s</b>de yaratýlacaktýr.";
$net2ftp_messages["New directory name:"] = "Yeni dizin adý:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "Dizin <b>%1\$s</b> baþarýlý bir þekilde yaratýldý.";
$net2ftp_messages["Directory <b>%1\$s</b> could not be created."] = "Dizin <b>%1\$s</b> yaratýlamýyor.";

} // end newdir


// -------------------------------------------------------------------------
// Raw module
if ($net2ftp_globals["state"] == "raw") {
// -------------------------------------------------------------------------

// /modules/raw/raw.inc.php
$net2ftp_messages["Send arbitrary FTP commands"] = "Rastgele FTP komutlarý gönder";


// /skins/[skin]/raw1.template.php
$net2ftp_messages["List of commands:"] = "Komutlarýn listesi:";
$net2ftp_messages["FTP server response:"] = "FTP sunucusu cevabý:";

} // end raw


// -------------------------------------------------------------------------
// Rename module
if ($net2ftp_globals["state"] == "rename") {
// -------------------------------------------------------------------------
$net2ftp_messages["Rename directories and files"] = "Dosya ve dizileri yeniden adlandýr";
$net2ftp_messages["Old name: "] = "Eski ad: ";
$net2ftp_messages["New name: "] = "Yeni ad: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "Yeni ad nokta içeremez. Bu giriþ, <b>%1\$s</b>e yeniden adlandýrýlmadý";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "Yeni ad yasaklý anahtar kelime içeremez. Bu giriþ, <b>%1\$s</b>e yeniden adlandýrýlmadý";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> baþarýlý bir þekilde <b>%2\$s</b>e yeniden adlandýrýldý";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> ,<b>%2\$s</b>e yeniden adlandýrýlamýyor";

} // end rename


// -------------------------------------------------------------------------
// Unzip module
if ($net2ftp_globals["state"] == "unzip") {
// -------------------------------------------------------------------------

// /modules/unzip/unzip.inc.php
$net2ftp_messages["Unzip archives"] = "Arþiv paketlerini aç";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "FTP sunucusundan arsiv paketini aliyor %1\$s - %2\$s";
$net2ftp_messages["Unable to get the archive <b>%1\$s</b> from the FTP server"] = "FTP sunucusundan arþiv paketini alamýyor <b>%1\$s</b>";

// /skins/[skin]/unzip1.template.php
$net2ftp_messages["Set all targetdirectories"] = "Tüm hedef dizinleri ayarla";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Sýradan bir hedef dizin ayarlamak için, yukarýdaki metin kutusuna hedef dizini girin ve \"Tüm hedef dizinleri ayarla\"düðmesine týklayýn.";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Not: içerisine herhangi bir þey kopyalanmadan önce, hedef dizin önceden var olmalý.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Arþiv paketlerini <b>%1\$s</b> þuna aç:";
$net2ftp_messages["Target directory:"] = "Hedef dizin:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Klasör adlarýný kullan (otomatik alt dizinler yaratýr)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "Dosyayý güncelle";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>UYARI: BU ÝÞLEV HALA ÝLK GELÝÞTÝRME AÞAMASINDA. BUNU SADECE TEST DOSYALARI ÜZERÝNDE KULLANIN! UYARILIYORSUNUZ!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "Bilinen hatalar: - tab karakterlerini siler - büyük dosyalar ile iyi çalýþmýyor (> 50kB) -  henüz standart olmayan karakterler içeren dosyalar üzerinde test edilmemiþtir</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "Bu iþlev,seçili dosyanýn yeni sürümünü yüklemenize, deðiþikliklerin ne olduðunu görüntülemenize ve her bir deðiþikliði kabul veya reddetmenize olanak saðlar. Herhangi bir þey kaydedilmeden önce, birleþtirilmiþ dosyayý düzenleyebilirsiniz.";          //merged= ?
$net2ftp_messages["Old file:"] = "Eski dosya:";
$net2ftp_messages["New file:"] = "Yeni dosya:";
$net2ftp_messages["Restrictions:"] = "Kýsýtlamalar:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Bir dosyanýn maksimum boyutu net2ftp tarafýndan <b>%1\$s kB</b>a ve PHP tarafýndan <b>%2\$s</b>a kýsýtlanmýþtýr ";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Maksimum uygulama süresi <b>%1\$s saniye</b>dir";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "FTP aktarým modu (ASCII veya BINARY) dosya uzantýsýna baðlý olarak ,otomatik belirlenecektir";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Eðer hedef dosya zaten varsa, üzerine yazýlacaktýr";
$net2ftp_messages["You did not provide any files or archives to upload."] = "Yüklemek için herhangi bir dosya ya da arþiv paketi saðlamadýnýz.";
$net2ftp_messages["Unable to delete the new file"] = "Yeni dosyayý silemiyor";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "Lütfen bekleyin...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "Aþaðýdaki satýrlarý seçin, deðiþiklikleri kabul ya da reddedin ve formu gönderin.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "Dizine yükle:";    //dizine: klasöre
$net2ftp_messages["Files"] = "Dosyalar";
$net2ftp_messages["Archives"] = "Arþiv paketleri";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "Buraya girilen dosyalar FTP sunucusuna aktarýlacaktýr.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "Buraya girilen arþiv paketleri açýlacaktýr ve içerisindeki dosyalar FTP sunucusuna aktarýlacaktýr.";
$net2ftp_messages["Add another"] = "Bir baþkasýný ekle";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Klasör adlarýný kullan (otomatik alt dizinler yaratýr)";

$net2ftp_messages["Choose a directory"] = "Dizin seç";
$net2ftp_messages["Please wait..."] = "Lütfen bekleyin...";
$net2ftp_messages["Uploading... please wait..."] = "Yüklüyor... lütfen bekleyin...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "Eðer yükleme izin verilen <b>%1\$s saniye<\/b>den daha fazla sürerse, daha az/daha küçük dosyalar ile tekrar denemek zorunda kalacaksýnýz.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "Bu pencere birkaç saniye içinde otomatik olarak kapancaktýr.";
$net2ftp_messages["Close window now"] = "Pencereyi þimdi kapat";

$net2ftp_messages["Upload files and archives"] = "Dosyalarý ve arþiv paketlerini yükle";
$net2ftp_messages["Upload results"] = "Yükleme sonuçlarý";
$net2ftp_messages["Checking files:"] = "Dosyalarý denetliyor:";
$net2ftp_messages["Transferring files to the FTP server:"] = "Dosyalarý FTP sunucusuna aktarýyor:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "Arþiv paketlerini açýyor ve FTP sunucusuna aktarýyor:";
$net2ftp_messages["Upload more files and archives"] = "Daha fazla arþiv paketi ve dosya yükle";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "Kýsýtlamalar:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Bir dosyanýn maksimum boyutu net2ftp tarafýndan <b>%1\$s kB</b>a ve PHP tarafýndan <b>%2\$s</b>a kýsýtlanmýþtýr ";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Maksimum uygulama süresi <b>%1\$s saniye</b>dir";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "FTP aktarým modu (ASCII veya BINARY) dosya uzantýsýna baðlý olarak ,otomatik belirlenecektir";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Eðer hedef dosya zaten varsa, üzerine yazýlacaktýr";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "Dosyayý göster %1\$s";
$net2ftp_messages["View image %1\$s"] = "Resmi %1\$s görüntüle";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "Macromedia ShockWave Flaþ filmini göster %1\$s";
$net2ftp_messages["Image"] = "Resim";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Sözdizim vurgulama <a href=\"http://geshi.org\">GeSHi</a>ile güçlendirilmiþtir";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "Resmi kaydetmek için, üzerine sað týklayýn ve 'Resmi Farklý Kaydet...'i seçin";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "Zip giriþleri";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "FTP sunucusunda zip dosyasý olarak kaydet:";
$net2ftp_messages["Email the zip file in attachment to:"] = "Zip dosyasýný ek þeklinde birisine, e-posta ile gönder:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "Dosya göndermenin anonim olmadýðýna dikkat edin: Gönderme zamanýnýza ek olarak, IP adresiniz e-postaya eklenecek.";
$net2ftp_messages["Some additional comments to add in the email:"] = "E-postaya eklenecek bazý ek yorumlar:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "Zip dosyasý için bir dosya adý girmediniz. Geri dönün ve bir dosya adý girin.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "Girdiðiniz e-posta adresi (%1\$s) geçerli gözükmüyor.<br />Lütfen <b>kullanýcýadý@alanadý.com</b> biçiminde bir adres girin";

} // end zip

?>