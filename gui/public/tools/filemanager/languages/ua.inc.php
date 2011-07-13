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
//  |     $messages[...] = ["Le fichier %1\$s a йtй copiй vers %2\$s "]             |
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
$net2ftp_messages["en"] = "ua";

// HTML dir attribute: left-to-right (LTR) or right-to-left (RTL)
$net2ftp_messages["ltr"] = "ltr";

// CSS style: align left or right (use in combination with LTR or RTL)
$net2ftp_messages["left"] = "left";
$net2ftp_messages["right"] = "right";

// Encoding
$net2ftp_messages["iso-8859-1"] = "windows-1251";


// -------------------------------------------------------------------------
// Status messages
// -------------------------------------------------------------------------

// When translating these messages, keep in mind that the text should not be too long
// It should fit in the status textbox

$net2ftp_messages["Connecting to the FTP server"] = "З'єднання з FTP-сервером";
$net2ftp_messages["Logging into the FTP server"] = "Вхiд на FTP-сервер";
$net2ftp_messages["Setting the passive mode"] = "Встановлення пассивного режима";
$net2ftp_messages["Getting the FTP system type"] = "Визначення  типу FTP сервера";
$net2ftp_messages["Changing the directory"] = "Змiна директорiї";
$net2ftp_messages["Getting the current directory"] = "Змiна поточної директорiї";
$net2ftp_messages["Getting the list of directories and files"] = "Отримання списку папок та файлiв";
$net2ftp_messages["Parsing the list of directories and files"] = "Обробка списку файлiв та директорiй";
$net2ftp_messages["Logging out of the FTP server"] = "Вихiд з FTP сервера";
$net2ftp_messages["Getting the list of directories and files"] = "Отримання списку папок та файлiв";
$net2ftp_messages["Printing the list of directories and files"] = "Вивiд списку папок та файлiв";
$net2ftp_messages["Processing the entries"] = "Обробка змiсту";
$net2ftp_messages["Processing entry %1\$s"] = "Обробка запису %1\$s";
$net2ftp_messages["Checking files"] = "Перевiрка файлiв";
$net2ftp_messages["Transferring files to the FTP server"] = "Перемiщення файлiв на FTP-сервер";
$net2ftp_messages["Decompressing archives and transferring files"] = "Розпакування архiвiв та перемiщення файлiв";
$net2ftp_messages["Searching the files..."] = "Пошук файлу...";
$net2ftp_messages["Uploading new file"] = "Закачати новий файл";
$net2ftp_messages["Reading the file"] = "Читання файлу";
$net2ftp_messages["Parsing the file"] = "Редагування файлу";
$net2ftp_messages["Reading the new file"] = "Читання нового файлу";
$net2ftp_messages["Reading the old file"] = "Читання старого файлу";
$net2ftp_messages["Comparing the 2 files"] = "Порiвняння двох файлiв";
$net2ftp_messages["Printing the comparison"] = "Вивiд результату";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "Вiдправлення FTP команди %1\$s of %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Отримання архiву %1\$s of %2\$s з FTP сервера";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Створення тимчасової директорiї на FTP серверi";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Встановлення прав на тимчасову директорiю";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Копiювання установочного скрипта на FTP сервер";
$net2ftp_messages["Script finished in %1\$s seconds"] = "Скрипт виконаний за %1\$s секунд";
$net2ftp_messages["Script halted"] = "Скрипт перерваний";

// Used on various screens
$net2ftp_messages["Please wait..."] = "Будь ласка, зачекайте...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Несподiваний формат рядка: %1\$s. Вихiд.";
$net2ftp_messages["This beta function is not activated on this server."] = "Ця бета функцiя не активована на серверi.";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "Данная функцiя вiдключена Адмiнiстратором.";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "Директорiя <b>%1\$s</b> не iснує або не може бути вибраною, томущо вибрана директорiя <b>%2\$s</b> .";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "Ваша корневая директорiя <b>%1\$s</b> не iснує або не може бути вибраною.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "Директорiя <b>%1\$s</b> не може бути вибрана - у Вас може бути недостатньо прав для перегляду або директорiя не iснує.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "З допомогою net2ftp неможливо керувати данними, якi мiстять забороненi слова. Це необхiдно для захисту вiд пiдробок PayPal або Ebay.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Занадто великi файли неможна завантажувати, копiювати, перемiщати, архiвувати, розпаковувати, переглядати або редагувати; їх можна перейменовувати, змiнювати права доступу або видаляти.";
$net2ftp_messages["Execute %1\$s in a new window"] = "Виконати %1\$s в новому вiкнi";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "Виберiть хоча б одну папку або файл.";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "FTP-сервер <b>%1\$s</b> не знайдений в списку дозволених FTP-серверiв.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "FTP-сервер <b>%1\$s</b> знаходиться в списку заборонених FTP-серверiв.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "Порт FTP-сервера %1\$s не може використовуватися.";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "Ваш IP-адреса (%1\$s) не знаходиться в списку дозволених.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "Ваш IP-адреса (%1\$s) знаходиться в списку заборонених IP-адрес.";

// isAuthorizedDirectory()
$net2ftp_messages["Table net2ftp_users contains duplicate rows."] = "Таблиця net2ftp_users мiстить однаковi записи.";

// logAccess(), logLogin(), logError()
$net2ftp_messages["Unable to execute the SQL query."] = "Не вдається виконати SQL запит.";
$net2ftp_messages["Unable to open the system log."] = "Неможливо вiдкрити системний лог.";
$net2ftp_messages["Unable to write a message to the system log."] = "Неможливо записати повiдомлення до системного логу.";

// checkAdminUsernamePassword()
$net2ftp_messages["You did not enter your Administrator username or password."] = "Ви не вказали iм'я користувача або пароль Адмiнiстратора.";
$net2ftp_messages["Wrong username or password. Please try again."] = "Неправильне iм'я користувача або пароль. Будь ласка, спробуйте ще раз.";


// -------------------------------------------------------------------------
// /includes/consumption.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to determine your IP address."] = "Не вдається розпiзнати Вашу ip адресу.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "Таблиця net2ftp_log_consumption_ipaddress мiстить однаковi записи.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "Таблиця net2ftp_log_consumption_ftpserver мiстить однаковi записи.";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "Змiнна <b>consumption_ipaddress_datatransfer</b> не являється числом.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "Не вдалося оновити таблицю net2ftp_log_consumption_ipaddress.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "Таблиця net2ftp_log_consumption_ipaddress мiстить однаковi записи.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "Не вдалося оновити таблицю net2ftp_log_consumption_ftpserver.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "Table net2ftp_log_consumption_ftpserver contains duplicate entries.";
$net2ftp_messages["Table net2ftp_log_access could not be updated."] = "Не вдалося оновити таблицю net2ftp_log_access.";
$net2ftp_messages["Table net2ftp_log_access contains duplicate entries."] = "Таблиця net2ftp_log_access мiстить однаковi записи.";


// -------------------------------------------------------------------------
// /includes/database.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "Неможливо з'єднатися з сервером MySQL. Перевiрте параметри пiдключення в файлi settings.inc.php.";
$net2ftp_messages["Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php.";


// -------------------------------------------------------------------------
// /includes/errorhandling.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["An error has occured"] = "Виникла помилка";
$net2ftp_messages["Go back"] = "Назад";
$net2ftp_messages["Go to the login page"] = "На сторiнку входу";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "<a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP-модуль PHP</a> не встановлений.<br /><br /> Адмiнiстратор цього сайту повинен встановити FTP-модуль. Iнструкцiя встановлення дана на <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Не вдалося з'єднатися з FTP-сервером <b>%1\$s</b> на порту <b>%2\$s</b>.<br /><br />Чи правильна адреса FTP-сервера? Вона часто вiдрiзняється вiд адреса HTTP-сервера. Будь ласка, зв'яжiться з техпiдтримкою вашого ISP або сисадмiном.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Не вдалося ввiйти на FTP-сервер <b>%1\$s</b> з логiном <b>%2\$s</b>.<br /><br />Правильнi логiн та пароль? Будь ласка, зв'яжiться з техпiдтримкою вашого ISP або сисадмiном.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "Не вдалося перемкнутися в пасивний режим FTP <b>%1\$s</b>.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "Не вдалося з'єднатися з другим FTP-сервером <b>%1\$s</b> на порту <b>%2\$s</b>.<br /><br />Чи правильна адреса FTP-сервера? Вона часто вiдрiзняється вiд адреса HTTP-сервера. Будь ласка, зв'яжiться з техпiдтримкою вашого ISP або сисадмiном.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "Не вдалося з'єднатися з другим FTP-сервером <b>%1\$s</b> з логiном <b>%2\$s</b>.<br /><br />Правильнi iм'я користувача та пароль? Будь ласка, зв'яжiться з техпiдтримкою вашого ISP або сисадмiном.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "Не вдалося перемкнутися в пасивний режим на другому FTP <b>%1\$s</b>.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "Не вдалося перейменувати папку або файл <b>%1\$s</b> в <b>%2\$s</b>";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "Не вдалося виконати команду <b>%1\$s</b>. Команда CHMOD доступна тiльки на Unix-серверах.";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "Папка <b>%1\$s</b> успiшно chmodded <b>%2\$s</b>";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "Обробка файлiв директорiй <b>%1\$s</b>:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "Файл <b>%1\$s</b> успiшно chmodded <b>%2\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "Всi вибранi папки та файли перевiренi.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "Не вдалося видалити папку <b>%1\$s</b>";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Не вдалося видалити файл <b>%1\$s</b>";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "Не вдалося створити папку <b>%1\$s</b>";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "Не вдалося створити тимчасовий файл";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "Не вдалося завантажити файл <b>%1\$s</b> з FTP-сервера та зберегти його як тимчасовий файл <b>%2\$s</b>.<br />Перевiрте дозволи папки %3\$s.<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Не вдалося вiдкрити файл. Перевiрте дозволи папки %1\$s.";
$net2ftp_messages["Unable to read the temporary file"] = "Не вдалося прочитати тимчасовий файл";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Не вдалося закрити тимчасовий файл";
$net2ftp_messages["Unable to delete the temporary file"] = "Не вдалося видалити тимчасовий файл";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "Не вдалося створити тимчасовий файл. Перевiрте дозволи папки %1\$s.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "Не вдалося вiдкрити файл. Перевiрте дозволи папки %1\$s.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "Не вдалося записати рядок в тимчасовий файл <b>%1\$s</b>.<br />Перевiрте дозволи папки %2\$s.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "Не вдалося закрити тимчасовий файл";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "Не вдалося завантажити файл <b>%1\$s</b> на FTP-сервер.<br />Мабуть, у вас немає прав.";
$net2ftp_messages["Unable to delete the temporary file"] = "Не вдалося видалити тимчасовий файл";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "Перевiрка папки <b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "Папка призначення <b>%1\$s</b> зпiвпадає з пiдпапкою <b>%2\$s</b>, тому вона буде пропущена";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "Директорiя <b>%1\$s</b> мiстить заборонене слово, тому директорiя будет пропущена";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "Директорiя <b>%1\$s</b> мiстить заборонене слово, перемiщення не буде виконано";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "Не вдалося створити підпапку <b>%1\$s</b>. Вона вже iснує. Продовження процесу...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Створено пiдпапку <b>%1\$s</b>";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "Директорiя <b>%1\$s</b> не може бути вибрана, томущо вона буде пропущена";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "Не вдалося видалити підпапку <b>%1\$s</b> - вона не пуста";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "Видалена пiдпапка <b>%1\$s</b>";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "Перевiрка папки <b>%1\$s</b> закiнчена";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "Файл призначення <b>%1\$s</b> зпiвпадає з вихiдним файлом, вiн буде пропущений";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "Файл <b>%1\$s</b> мiстить забороненi слова, тому файл буде пропущений";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "Файл <b>%1\$s</b> мiстить забороненi слова, файл не буде перемiщений";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "Файл <b>%1\$s</b> занадто великий для копiювання, тому вiн буде пропущений";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "Файл <b>%1\$s</b> занадто великий для перемiщення, перемiщення не буде виконано";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "Не вдалося зкопiювати файл <b>%1\$s</b>";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Copied file <b>%1\$s</b>";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Не вдалося перемiстити файл <b>%1\$s</b>, перемiщення не виконано.";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "Перемiщено файл <b>%1\$s</b>";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "Не вдалося видалити файл <b>%1\$s</b>";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "Видалено файл <b>%1\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "Всi вибранi папки та файли перевiренi.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "Не вдалося скопiювати видалений файл <b>%1\$s</b> на локальний комп'ютер, використовуючи FTP-ht;bv <b>%2\$s</b>";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "Не вдалося видалити файл <b>%1\$s</b>";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "Файл занадто великий для передачi.";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "Досягнуто добовий лiмiт: файл <b>%1\$s</b> не буде переданий";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "Не вдалося скопiювати локальний файл <b>%1\$s</b> на видалений комп'ютер, використовуючи режим <b>%2\$s</b>";
$net2ftp_messages["Unable to delete the local file"] = "Не вдалося видалити локальний файл";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "Не вдалося видалити тимчасовий файл";
$net2ftp_messages["Unable to send the file to the browser"] = "Неможливо передати файл браузер";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "Не вдалося створити тимчасовий файл";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "Zip-файл збережений на FTP-серверi як <b>%1\$s</b>";
$net2ftp_messages["Requested files"] = "Запитанi файли";

$net2ftp_messages["Dear,"] = "Шановний,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "Хтось (можливо Ви) вiдправив файли, прикладенi до листовi, на цю адресу (%1\$s).";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "Якщо Ви не знаєте, що це за файли, або не довiряєте вiдправниковi, будь ласка, видалите цей лист, не вiдкриваючи файли.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "Якщо Ви не будете вiдкривати zip-файл, файли, що утримуються в ньому, вони не зможуть зашкодити Вашому комп'ютеровi.";
$net2ftp_messages["Information about the sender: "] = "Iнформацiя про вiдправника: ";
$net2ftp_messages["IP address: "] = "IP-адреса: ";
$net2ftp_messages["Time of sending: "] = "Час вiдправлення: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "Вiдправлено через net2ftp з сайта ";
$net2ftp_messages["Webmaster's email: "] = "Адреса Веб-майстра: ";
$net2ftp_messages["Message of the sender: "] = "Повідомлення відправнику: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp безкоштовне ПО, що випускається пiд лiцензiєю GNU/GPL. Бiльш докладна iнформацiя знаходиться за адресою http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "Zip-файл відправлений <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "Файл <b>%1\$s</b> занадто великий. Файл не буде завантажений.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "Файл <b>%1\$s</b> мiстить забороненi слова. Файл не буде завантажений.";
$net2ftp_messages["Could not generate a temporary file."] = "Не вдалося згенерувати тимчасовий файл.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "Файл <b>%1\$s</b> не може бути перемiщений";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "Файл <b>%1\$s</b> Ok";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "Не вдалося перемiстити завантажений файл в тимчасову папку.<br /><br />Адміністратору сайту потрібно змінити <b>chmod</b> на <b>777</b> папки /temp.";
$net2ftp_messages["You did not provide any file to upload."] = "Ви не вибрали файл.";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "Файл <b>%1\$s</b> не може бути завантажений на FTP-сервер";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "Файл <b>%1\$s</b> бул завантажений на FTP-сервер, використовуючи FTP-режим <b>%2\$s</b>";
$net2ftp_messages["Transferring files to the FTP server"] = "Перемiщення файлiв на FTP-сервер";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "Перевiрка архiву nr %1\$s: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "Архів <b>%1\$s</b> не бул перевірений, тому що розширення файлу неправильно. Тільки zіp, tar, tgz та gz архіви підтримуються.";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Не вдалося витягти файли из архiву";
$net2ftp_messages["Archive contains filenames with ../ or ..\\ - aborting the extraction"] = "Імена які архівів містять ../ or ..\\ - недопускаються. Витягування припинено.";
$net2ftp_messages["Created directory %1\$s"] = "Створена директорiя %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "Не вдалося створити директорiю %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "Зкопійований файл %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "Не вдалося скопiювати файл %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "Не вдалося видалити тимчасову директорiю";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Не вдалося видалити тимчасовий файл %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "Не вдалося виконати команду <b>%1\$s</b>";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "Ваше завдання зупинене";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "Завдання, що ви хотіли припинити через net2ftp займе більше %1\$s дозволених секунд. Виконання зупинене.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "Це обмеження часу дозволяє користуватися сервером без перебоїв.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "Спробуйте розділити завдання: наприклад, забороніть вибір окремих файлів.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "Якщо ви дійсно хочете виконати це завдання через net2ftp, то встановіть net2ftp на власному сервері.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "Немає тексту для відправлення по електронній пошті!";
$net2ftp_messages["You did not supply a From address."] = "Ви не вказали адресу відправника.";
$net2ftp_messages["You did not supply a To address."] = "Ви не вказали адресу одержувача.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "У зв'язку з технічними проблемами emaіl для <b>%1\$s</b> не може бути відправлений.";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "Будь ласка, введiть Ваш логiн та пароль вiд FTP сервера";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Ви не заполнли форму логіну в спливаючому вікні.<br />Натисніть на посилання \"На головну сторiнку\" нижче.";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "Доступ до панелі Адмiнiстратора net2ftp заблокований, тому що в файлi settings.inc.php не зазначений пароль для входу. Встановіть пароль у цьому файлі та оновіть сторінку.";
$net2ftp_messages["Please enter your Admin username and password"] = "Будь ласка, введiть логiн та пароль Адмiнiстратора."; 
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "Ви не заполнли форму логіну в спливаючому вікні.<br />Натисніть на посилання \"На головну сторiнку\" нижче.";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "Невірний логін або пароль для входу в панель Адмiнiстратора net2ftp. Логін та пароль вказуються у файлі settings.inc.php.";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "Синiй";
$net2ftp_messages["Grey"] = "Сiрий";
$net2ftp_messages["Black"] = "Чорний";
$net2ftp_messages["Yellow"] = "Жовтий";
$net2ftp_messages["Pastel"] = "Пастельний";

// getMime()
$net2ftp_messages["Directory"] = "Папка";
$net2ftp_messages["Symlink"] = "Посилання";
$net2ftp_messages["ASP script"] = "Скрипт ASP";
$net2ftp_messages["Cascading Style Sheet"] = "CSS";
$net2ftp_messages["HTML file"] = "Файл HTML";
$net2ftp_messages["Java source file"] = "Код Java";
$net2ftp_messages["JavaScript file"] = "Файл JavaScript";
$net2ftp_messages["PHP Source"] = "PHP код";
$net2ftp_messages["PHP script"] = "Скрипт PHP";
$net2ftp_messages["Text file"] = "Текст";
$net2ftp_messages["Bitmap file"] = "Зображення";
$net2ftp_messages["GIF file"] = "GIF";
$net2ftp_messages["JPEG file"] = "JPEG";
$net2ftp_messages["PNG file"] = "PNG";
$net2ftp_messages["TIF file"] = "TIF";
$net2ftp_messages["GIMP file"] = "Файл GIMP";
$net2ftp_messages["Executable"] = "Додаток";
$net2ftp_messages["Shell script"] = "Скрипт shell";
$net2ftp_messages["MS Office - Word document"] = "MS Office - документ Word";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - таблиця Excel";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - презентацiя PowerPoint";
$net2ftp_messages["MS Office - Access database"] = "MS Office - БД Access";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - малюнок Visio";
$net2ftp_messages["MS Office - Project file"] = "MS Office - файл проекта";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - документ Writer 6.0";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - шаблон Writer 6.0";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - таблиця Calc 6.0";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - шаблон Calc 6.0";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - документ Draw 6.0";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - шаблон Draw 6.0";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - презентацiя Impress 6.0";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - шаблон Impress 6.0";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - документ Writer 6.0";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - документ Math 6.0";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - документ StarWriter 5.x";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - документ StarWriter 5.x";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - таблиця StarCalc 5.x";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - документ StarDraw 5.x";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - презентацiя StarImpress 5.x";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - файл StarImpress Packed 5.x";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - документ StarMath 5.x";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - документ StarChart 5.x";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - файл пошти StarMail 5.x";
$net2ftp_messages["Adobe Acrobat document"] = "Документ Adobe Acrobat";
$net2ftp_messages["ARC archive"] = "ARC-архiв";
$net2ftp_messages["ARJ archive"] = "ARJ-архiв";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "GZ-архiв";
$net2ftp_messages["TAR archive"] = "TAR-архiв";
$net2ftp_messages["Zip archive"] = "Zip-архiв";
$net2ftp_messages["MOV movie file"] = "Фiльм MOV";
$net2ftp_messages["MPEG movie file"] = "Фiльм MPEG";
$net2ftp_messages["Real movie file"] = "Фiльм в форматi Real";
$net2ftp_messages["Quicktime movie file"] = "Фiльм Quicktime";
$net2ftp_messages["Shockwave flash file"] = "ФайлShockwave flash";
$net2ftp_messages["Shockwave file"] = "Файл Shockwave";
$net2ftp_messages["WAV sound file"] = "Звук WAV";
$net2ftp_messages["Font file"] = "Файл шрифта";
$net2ftp_messages["%1\$s File"] = "%1\$s файл";
$net2ftp_messages["File"] = "Файл";

// getAction()
$net2ftp_messages["Back"] = "Назад";
$net2ftp_messages["Submit"] = "Вiдправити";
$net2ftp_messages["Refresh"] = "Оновити";
$net2ftp_messages["Details"] = "Деталi";
$net2ftp_messages["Icons"] = "Значки";
$net2ftp_messages["List"] = "Список";
$net2ftp_messages["Logout"] = "Вихiд";
$net2ftp_messages["Help"] = "Допомога";
$net2ftp_messages["Bookmark"] = "Закладка";
$net2ftp_messages["Save"] = "Зберегти";
$net2ftp_messages["Default"] = "За замовчуванням";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Допомога";
$net2ftp_messages["Forums"] = "Форуми";
$net2ftp_messages["License"] = "Ліцензія";
$net2ftp_messages["Powered by"] = "Створено на";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "Ви спрямовані на форум net2ftp. Цей форум тільки для обговорення net2ftp, він не призначений для обговорення загальних опитувань хостингу.";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "Функції адміністратора";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "Iнформацiя про версію";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "У Вас встановлена остання версія net2ftp.";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "Не вдалося одержати інформацію про останню версію з сайта net2ftp.com. Перевiрте налаштування безпеки Вашего браузера, які можуть перешкоджати завантаженню невеликого файлу з net2ftp.com.";
$net2ftp_messages["Logging"] = "Логінування ";
$net2ftp_messages["Date from:"] = "Дата з:";
$net2ftp_messages["to:"] = "до:";
$net2ftp_messages["Empty logs"] = "Очистити логи";
$net2ftp_messages["View logs"] = "Перегляд логів";
$net2ftp_messages["Go"] = "Вперед";
$net2ftp_messages["Setup MySQL tables"] = "Налаштувати таблиці MySQL";
$net2ftp_messages["Create the MySQL database tables"] = "Створити таблиці в базі даних MySQL";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "Функції адміністратора";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "Не вдалося вiдкрити покажчик для файла %1\$s";
$net2ftp_messages["The file %1\$s could not be opened."] = "Файл %1\$s не може бути відкритий.";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "Не вдалося закрити покажчик на файл %1\$s";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "Не вдається з'єднатися з сервером <b>%1\$s</b>. Будь ласка, перевірте введені параметри з'єднання.";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "Не вдається вибрати базу даних <b>%1\$s</b>.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "SQL-запит <b>%1\$s</b> не може бути виконаний.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "SQL-запит <b>%1\$s</b> бул успiшно виконаний.";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "Будь ласка, вкажіть параметри работи з MySQL:";
$net2ftp_messages["MySQL username"] = "Ім'я користувача MySQL";
$net2ftp_messages["MySQL password"] = "Пароль MySQL";
$net2ftp_messages["MySQL database"] = "База даних MySQL";
$net2ftp_messages["MySQL server"] = "MySQL server";
$net2ftp_messages["This SQL query is going to be executed:"] = "Буде виконаний наступний SQL-запит:";
$net2ftp_messages["Execute"] = "Виконати";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "Використовувані налаштування:";
$net2ftp_messages["MySQL password length"] = "Довжина пароля MySQL";
$net2ftp_messages["Results:"] = "Результати:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "Функції адміністратора";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "Не вдалося виконати SQL запит <b>%1\$s</b>.";
$net2ftp_messages["No data"] = "Немає даних";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "Функції адміністратора";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "Таблиця <b>%1\$s</b> успiшно очищена.";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "Таблиця <b>%1\$s</b> не може бути очищена.";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "Таблиця <b>%1\$s</b> успiшно оптимізована.";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "Таблиця <b>%1\$s</b> не може бути оптимізована.";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "Розширені функції";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "Вперед";
$net2ftp_messages["Disabled"] = "Відключено";
$net2ftp_messages["Advanced FTP functions"] = "Додаткові FTP функції";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "Відправити довільні FTP команди на FTP сервер";
$net2ftp_messages["This function is available on PHP 5 only"] = "Ця функцiя доступна тiльки з PHP 5";
$net2ftp_messages["Troubleshooting functions"] = "Функції для усунення проблем";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "Вирішення проблем net2ftp на цьому веб-серверi";
$net2ftp_messages["Troubleshoot an FTP server"] = "Вирішення проблем FTP-сервера";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Перевірити правила розбору списку net2ftp";
$net2ftp_messages["Translation functions"] = "Функції перекладу";
$net2ftp_messages["Introduction to the translation functions"] = "Початкова інформація про функціях перекладу";
$net2ftp_messages["Extract messages to translate from code files"] = "Extract messages to translate from code files";
$net2ftp_messages["Check if there are new or obsolete messages"] = "Перевірити навність наявних або застарілих повідомлень";

$net2ftp_messages["Beta functions"] = "Бета-функції";
$net2ftp_messages["Send a site command to the FTP server"] = "Відправити команду сайту на  FTP сервер";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: захистити папку паролем, створити персональні сторінки помилок";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: виконати SQL-запит";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "Командні функції цього сайту недоступні на веб-серверi.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "Функції Apache недоступні на цьому веб-серверi.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "Функції MySQL недоступні на цьому веб-серверi.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "Несподіваний зміст рядка 2. Завершення.";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "Вирішення проблем FTP-сервера";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "Параметри з'єднання:";
$net2ftp_messages["FTP server"] = "FTP-сервер";
$net2ftp_messages["FTP server port"] = "Порт FTP-сервера";
$net2ftp_messages["Username"] = "Логiн";
$net2ftp_messages["Password"] = "Пароль";
$net2ftp_messages["Password length"] = "Довжина пароля";
$net2ftp_messages["Passive mode"] = "Пасивний режим";
$net2ftp_messages["Directory"] = "Папка";
$net2ftp_messages["Printing the result"] = "Printing the result";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "З'єднання з FTP-сервером: ";
$net2ftp_messages["Logging into the FTP server: "] = "Вхiд на FTP-сервер: ";
$net2ftp_messages["Setting the passive mode: "] = "Перехід на пасивний режим: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Визначається тип системи FTP-сервера: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Перехід в папку %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Папка FTP-сервера: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Отримання списку папок та файлiв: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Повторна спроба одержання списка: ";
$net2ftp_messages["Closing the connection: "] = "Закриття з'єднання: ";
$net2ftp_messages["Raw list of directories and files:"] = "Список папок та файлiв:";
$net2ftp_messages["Parsed list of directories and files:"] = "Оброблений список папок та файлiв:";

$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "не OK";

} // end advanced_ftpserver


// -------------------------------------------------------------------------
// Advanced_parsing module
if ($net2ftp_globals["state"] == "advanced_parsing") {
// -------------------------------------------------------------------------

$net2ftp_messages["Test the net2ftp list parsing rules"] = "Перевірити правила розбору списку net2ftp";
$net2ftp_messages["Sample input"] = "Приклад вхідних даних";
$net2ftp_messages["Parsed output"] = "Оброблені вхідні дані";

} // end advanced_parsing


// -------------------------------------------------------------------------
// Advanced_webserver module
if ($net2ftp_globals["state"] == "advanced_webserver") {
// -------------------------------------------------------------------------

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "Вирішення проблем встановлення net2ftp";
$net2ftp_messages["Printing the result"] = "Printing the result";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "Перевiрка встановлення модуля FTP вiд PHP: ";
$net2ftp_messages["yes"] = "так";
$net2ftp_messages["no - please install it!"] = "ні - будь ласка, встановіть його!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "Перевірка дозволів папки на веб-сервері: невеликий файл може бути записаний у папку /temp та потім видалений.";
$net2ftp_messages["Creating filename: "] = "Ім'я файла для створення: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "OK. Ім'я файла: %1\$s";
$net2ftp_messages["not OK"] = "не OK";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "не OK. Перевiрте дозволи папки %1\$s";
$net2ftp_messages["Opening the file in write mode: "] = "Відкриття файла в режимі для запису: ";
$net2ftp_messages["Writing some text to the file: "] = "Запис тексту в файл: ";
$net2ftp_messages["Closing the file: "] = "Закриття файлу: ";
$net2ftp_messages["Deleting the file: "] = "Видалення файлу: ";

$net2ftp_messages["Testing the FTP functions"] = "Тестування функцій FTP";
$net2ftp_messages["Connecting to a test FTP server: "] = "Підключення до тестового FTP серверу: ";
$net2ftp_messages["Connecting to the FTP server: "] = "З'єднання з FTP-сервером: ";
$net2ftp_messages["Logging into the FTP server: "] = "Вхiд на FTP-сервер: ";
$net2ftp_messages["Setting the passive mode: "] = "Перехід на пасивний режим: ";
$net2ftp_messages["Getting the FTP server system type: "] = "Визначається тип системи FTP-сервера: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "Перехід в папку %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "Папка FTP-сервера: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "Отримання списку папок та файлiв: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "Повторна спроба одержання списка: ";
$net2ftp_messages["Closing the connection: "] = "Закриття з'єднання: ";
$net2ftp_messages["Raw list of directories and files:"] = "Список папок та файлiв:";
$net2ftp_messages["Parsed list of directories and files:"] = "Оброблений список папок та файлiв:";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "не OK";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "Додати це посилання у ваші закладки:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: клацніть правою кнопкою на посиланні та виберіть \"Додати в Обране...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: клацніть правою кнопкою на посилання та виберіть \"Bookmark This Link...\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "Примітка: коли Ви будете використовувати закладку, спливаюче вікно запитає у Вас ім'я та пароль.";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "Виберiть папку";
$net2ftp_messages["Please wait..."] = "Будь ласка, зачекайте...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "Папки з іменами, що містять \' не можуть коректно відображатися. Їх можна тільки видалити. Будь ласка, поверніться та виберіть іншу папку.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "Досягнуть добовий ліміт: Ви не можете більше передавати дані";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "Щоб гарантувати рівну доступність цього сервера для кожного користувача, накладається обмеження на обсяг переданих даних та час виконання скриптів для користувача в день. Як тільки цей ліміт перевищений, Ви можете переглядати папки, але не можете завантажувати файли.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "Якщо Вам необхідні необмежені ресурси, будь ласка встановіть net2ftp на Ваш власний ftp сервер.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "Нова папка";
$net2ftp_messages["New file"] = "Новий файл";
$net2ftp_messages["HTML templates"] = "HTML templates";
$net2ftp_messages["Upload"] = "Завантажити на серв";
$net2ftp_messages["Java Upload"] = "Завантажити Java";
$net2ftp_messages["Flash Upload"] = "Завантажити Flash";
$net2ftp_messages["Install"] = "Встановити";
$net2ftp_messages["Advanced"] = "Опції";
$net2ftp_messages["Copy"] = "Копір.";
$net2ftp_messages["Move"] = "Переміст.";
$net2ftp_messages["Delete"] = "Видалити";
$net2ftp_messages["Rename"] = "Перейм.";
$net2ftp_messages["Chmod"] = "Chmod";
$net2ftp_messages["Download"] = "Завантажити на комп";
$net2ftp_messages["Unzip"] = "Розпакувати";
$net2ftp_messages["Zip"] = "Архівувати";
$net2ftp_messages["Size"] = "Розмір";
$net2ftp_messages["Search"] = "Пошук";
$net2ftp_messages["Go to the parent directory"] = "Перейти на уровень вище";
$net2ftp_messages["Go"] = "Вперед";
$net2ftp_messages["Transform selected entries: "] = "Перетворити вибраного: ";
$net2ftp_messages["Transform selected entry: "] = "Перетворити вибраного: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "Створити підпапку в папці %1\$s";
$net2ftp_messages["Create a new file in directory %1\$s"] = "Створити файл в папці %1\$s";
$net2ftp_messages["Create a website easily using ready-made templates"] = "Створити сайт  використовуючи готові шаблони";
$net2ftp_messages["Upload new files in directory %1\$s"] = "Завантажити новi файли в папку %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Завантажити файли та директорiї використовуючи Java апплет";
$net2ftp_messages["Upload files using a Flash applet"] = "Завантажити файли на сервер, використовуючи Flash апплет";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Встановити пакети (вимагає наявності PHP на веб-серверi)";
$net2ftp_messages["Go to the advanced functions"] = "Перейти в дод. функції";
$net2ftp_messages["Copy the selected entries"] = "Копіювати вибранi папки";
$net2ftp_messages["Move the selected entries"] = "Перемістити вибранi папки";
$net2ftp_messages["Delete the selected entries"] = "Видалити вибранi папки";
$net2ftp_messages["Rename the selected entries"] = "Перейменувати вибраного";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "Chmod вибраного (работает на Unix/Linux/BSD серверах)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "Завантажити на комп zip-файл, якій містить вибранi файли";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Розпакувати вибранi архіви на FTP сервер";
$net2ftp_messages["Zip the selected entries to save or email them"] = "Стиснути вибраного та відправити по email";
$net2ftp_messages["Calculate the size of the selected entries"] = "Обчислити розмір вибранного";
$net2ftp_messages["Find files which contain a particular word"] = "Знайти файли, що містять частину слова";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "Натисніть для сортування %1\$s у порядку зростання";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "Натисніть для сортування %1\$s у порядку убування";
$net2ftp_messages["Ascending order"] = "Убування";
$net2ftp_messages["Descending order"] = "Зростання";
$net2ftp_messages["Upload files"] = "Завантажити файли на сервер";
$net2ftp_messages["Up"] = "Нагору";
$net2ftp_messages["Click to check or uncheck all rows"] = "Натисніть для вибору або скасування вибору всіх";
$net2ftp_messages["All"] = "Всi";
$net2ftp_messages["Name"] = "Ім'я";
$net2ftp_messages["Type"] = "Тип";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "Користувач";
$net2ftp_messages["Group"] = "Група";
$net2ftp_messages["Perms"] = "Дозволи";
$net2ftp_messages["Mod Time"] = "Час";
$net2ftp_messages["Actions"] = "Дії";
$net2ftp_messages["Select the directory %1\$s"] = "Вибрати директорiю %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "Вибрати файл %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "Вибрати symlink %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "Перейти в піддиректорію %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "Завантажити на комп файл%1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "Слідувати symlink'у %1\$s";
$net2ftp_messages["View"] = "Показ.";
$net2ftp_messages["Edit"] = "Редакт.";
$net2ftp_messages["Update"] = "Оновити";
$net2ftp_messages["Open"] = "Відкрити";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Переглянути вихідний код %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Редагувати вихідний код файла %1\$s";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "Завантажити нову версію файла 1\$s та застосувати зміни";
$net2ftp_messages["View image %1\$s"] = "Переглянути малюнок %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "Переглянути файл %1\$s з вашого HTTP-сервера";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(Примітка: Посилання може не працювати, якщо у вас немає доменного імені.)";
$net2ftp_messages["This folder is empty"] = "Папка пуста";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "Папки";
$net2ftp_messages["Files"] = "Файли";
$net2ftp_messages["Symlinks"] = "Посилання";
$net2ftp_messages["Unrecognized FTP output"] = "Невідомий вихід FTP";
$net2ftp_messages["Number"] = "Номер";
$net2ftp_messages["Size"] = "Розмір";
$net2ftp_messages["Skipped"] = "Пропущено";
$net2ftp_messages["Data transferred from this IP address today"] = "Об'єм даних, переданий з цієї ІP адреси за сьогодні";
$net2ftp_messages["Data transferred to this FTP server today"] = "Об'єм даних переданих цьому FTP серверові сьогодні";

// printLocationActions()
$net2ftp_messages["Language:"] = "Мова:";
$net2ftp_messages["Skin:"] = "Тема:";
$net2ftp_messages["View mode:"] = "Режим перегляду:";
$net2ftp_messages["Directory Tree"] = "Дерево папок";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "Виконати %1\$s в новому вiкнi";
$net2ftp_messages["This file is not accessible from the web"] = "Цей файл не доступний з web-інтерфейсу";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "Натисніть двічі для переходу в підпапку:";
$net2ftp_messages["Choose"] = "Вибір";
$net2ftp_messages["Up"] = "Нагору";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "Розмір вибраних папок та файлiв";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "Загальний розмір файлiв та папок:";
$net2ftp_messages["The number of files which were skipped is:"] = "Кількість файлiв, які були пропущені:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "Chmod на папки та файли";
$net2ftp_messages["Set all permissions"] = "Змінити всі права";
$net2ftp_messages["Read"] = "Читання";
$net2ftp_messages["Write"] = "Запис";
$net2ftp_messages["Execute"] = "Виконати";
$net2ftp_messages["Owner"] = "Користувач";
$net2ftp_messages["Group"] = "Група";
$net2ftp_messages["Everyone"] = "Всi";
$net2ftp_messages["To set all permissions to the same values, enter those permissions and click on the button \"Set all permissions\""] = "Для вибору однакових дозволів, введіть їхнє значення нижче та натисніть на кнопку \"Вибрати дозволи\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "Вибрати дозволи для папки <b>%1\$s</b>: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "Вибрати дозволи для файла <b>%1\$s</b>: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "Вибрати дозволи для сімлінка <b>%1\$s</b>: ";
$net2ftp_messages["Chmod value"] = "Значення Chmod";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "Chmod також на підпапки всередині цієї папки";
$net2ftp_messages["Chmod also the files within this directory"] = "Chmod також на файли всередині цієї папки";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "Chmod <b>%1\$s</b> виходить з діапазону 000-777. Спробуйте ще раз.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "Виберiть папку";
$net2ftp_messages["Copy directories and files"] = "Копіювати папки та файли";
$net2ftp_messages["Move directories and files"] = "Перемістити папки та файли";
$net2ftp_messages["Delete directories and files"] = "Видалити папки та файли";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "Ви дійсно бажаєте видалити ці файли та папки?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "Всi підпапки та файли в зазначених папках будут видалені!";
$net2ftp_messages["Set all targetdirectories"] = "Вибрати всі папки";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Щоб задати головну папку, введіть її назву в поле вище та виберіть пункт \"Вибрати всі папки\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Примітка: папка повинна вже існувати.";
$net2ftp_messages["Different target FTP server:"] = "Інший FTP-сервер:";
$net2ftp_messages["Username"] = "Логiн";
$net2ftp_messages["Password"] = "Пароль";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "Залишіть порожнім, якщо ви бажаєте скопіювати файли в ту ж папку FTP-сервера.";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "Якщо ви бажаєте відкрити файли на іншому FTP-сервері, то введіть дані для входу.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "Залишіть порожнім, якщо ви бажаєте перемістити файли в ту ж папку FTP-сервера.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "Якщо ви бажаєте перемістити файли на інший FTP-сервер, уведіть дані для входу.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "Копіювати папку <b>%1\$s</b> в:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "Перемістити папку <b>%1\$s</b> в:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "Папка <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "Копіювати файл <b>%1\$s</b> в:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "Перемістити файл <b>%1\$s</b> в:";
$net2ftp_messages["File <b>%1\$s</b>"] = "Файл <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "Копіювати симлинк <b>%1\$s</b> в:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "Перемістити симлинк <b>%1\$s</b> в:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Сімлінк <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "Папка призначення:";
$net2ftp_messages["Target name:"] = "Ім'я призначення:";
$net2ftp_messages["Processing the entries:"] = "Перегляд вмісту:";

} // end copymovedelete


// -------------------------------------------------------------------------
// Download file module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// EasyWebsite module
if ($net2ftp_globals["state"] == "easyWebsite") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create a website in 4 easy steps"] = "Cоздайте сайт за 4 кроки";
$net2ftp_messages["Template overview"] = "Перегляд щаблону";
$net2ftp_messages["Template details"] = "Деталi шаблона";
$net2ftp_messages["Files are copied"] = "Скопійовані файли";
$net2ftp_messages["Edit your pages"] = "Редагувати сторінки";

// Screen 1 - printTemplateOverview
$net2ftp_messages["Click on the image to view the details of a template."] = "Натисніть на зображення щоб подивитися подробиці про шаблон.";
$net2ftp_messages["Back to the Browse screen"] = "Назад, до сторінки перегляду";
$net2ftp_messages["Template"] = "Шаблон";
$net2ftp_messages["Copyright"] = "Авторське право";
$net2ftp_messages["Click on the image to view the details of this template"] = "Натисніть на зображення щоб подивитися подробиці про шаблон";

// Screen 2 - printTemplateDetails
$net2ftp_messages["The template files will be copied to your FTP server. Existing files with the same filename will be overwritten. Do you want to continue?"] = "Файли шаблона будуть скопійовані на Ваш FTP-сервер. Існуючі файли з такими ж іменами будуть замінені новими.  Чи хочете Ви продовжити?";
$net2ftp_messages["Install template to directory: "] = "Встановити шаблон в директорiю: ";
$net2ftp_messages["Install"] = "Встановити";
$net2ftp_messages["Size"] = "Розмір";
$net2ftp_messages["Preview page"] = "Перегляд сторінки";
$net2ftp_messages["opens in a new window"] = "відкриється в новому вікні";

// Screen 3
$net2ftp_messages["Please wait while the template files are being transferred to your server: "] = "Будь ласка, дочекайтеся закінчення переносу файлів на сервер: ";
$net2ftp_messages["Done."] = "Завершено.";
$net2ftp_messages["Continue"] = "Продовжити";

// Screen 4 - printEasyAdminPanel
$net2ftp_messages["Edit page"] = "Редагування";
$net2ftp_messages["Browse the FTP server"] = "Перегляд  FTP сервера";
$net2ftp_messages["Add this link to your favorites to return to this page later on!"] = "Додайте це посилання в закладки щоб повернутися сюди пізніше!";
$net2ftp_messages["Edit website at %1\$s"] = "Редагувати сайт %1\$s";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: клацніть правою кнопкою на посиланні та виберіть \"Додати в Обране...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: клацніть правою кнопкою на посилання та виберіть \"Bookmark This Link...\"";

// ftp_copy_local2ftp
$net2ftp_messages["WARNING: Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing..."] = "УВАГА: неможливо створити підпапку <b>%1\$s</b>. Можливо, вона вже iснує. Продовження...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Створено пiдпапку <b>%1\$s</b>";
$net2ftp_messages["WARNING: Unable to copy the file <b>%1\$s</b>. Continuing..."] = "УВАГА: не вдалося скопiювати файл <b>%1\$s</b>. Продовження...";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Copied file <b>%1\$s</b>";
}


// -------------------------------------------------------------------------
// Edit module
if ($net2ftp_globals["state"] == "edit") {
// -------------------------------------------------------------------------

// /modules/edit/edit.inc.php
$net2ftp_messages["Unable to open the template file"] = "Не вдалося вiдкрити тимчасовий файл";
$net2ftp_messages["Unable to read the template file"] = "Не вдалося прочитати тимчасовий файл";
$net2ftp_messages["Please specify a filename"] = "Вкажіть iм'я файла";
$net2ftp_messages["Status: This file has not yet been saved"] = "Стан: файл не збережений";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "Стан: збережено в <b>%1\$s</b> в режимі %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "Стан: <b>цей файл не може бути збережений</b>";
$net2ftp_messages["Not yet saved"] = "Not yet saved";
$net2ftp_messages["Could not be saved"] = "Could not be saved";
$net2ftp_messages["Saved at %1\$s"] = "Saved at %1\$s";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "Папка: ";
$net2ftp_messages["File: "] = "Файл: ";
$net2ftp_messages["New file name: "] = "Нове iм'я файла: ";
$net2ftp_messages["Character encoding: "] = "Кодування символів: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "Примітка: зміна тексту збереже зміни";
$net2ftp_messages["Copy up"] = "Скопіювати нагору";
$net2ftp_messages["Copy down"] = "Скопіювати вниз";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "Пошук папок та файлiв";
$net2ftp_messages["Search again"] = "Шукати знову";
$net2ftp_messages["Search results"] = "Результати пошуку";
$net2ftp_messages["Please enter a valid search word or phrase."] = "Введіть правильне слово або фразу.";
$net2ftp_messages["Please enter a valid filename."] = "Введіть правильне iм'я файлу.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "Будь ласка, введiть правильну назву в поле \"из\", наприклад, 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "Будь ласка, введiть правильний розмір в поле \"в\", наприклад, 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "Будь ласка, введiть правильну дату в форматi г-м-д в поле \"из\".";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "Будь ласка, введiть правильну дату в форматi г-м-д в поле \"в\".";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "Слово <b>%1\$s</b> не було знайдено.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "Слово <b>%1\$s</b> було знайдено в наступних фразах:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "Пошук слова або фрази";
$net2ftp_messages["Case sensitive search"] = "Відчутно до регістру";
$net2ftp_messages["Restrict the search to:"] = "Заборонити пошук:";
$net2ftp_messages["files with a filename like"] = "iм'я файла як";
$net2ftp_messages["(wildcard character is *)"] = "(символ *)";
$net2ftp_messages["files with a size"] = "файли з розміром";
$net2ftp_messages["files which were last modified"] = "файли, змінені";
$net2ftp_messages["from"] = "вiд";
$net2ftp_messages["to"] = "до";

$net2ftp_messages["Directory"] = "Папка";
$net2ftp_messages["File"] = "Файл";
$net2ftp_messages["Line"] = "Рядок";
$net2ftp_messages["Action"] = "Дія";
$net2ftp_messages["View"] = "Показ.";
$net2ftp_messages["Edit"] = "Редакт.";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "Переглянути вихідний код %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "Редагувати вихідний код файла %1\$s";

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
$net2ftp_messages["Install software packages"] = "Встановити пакети ПО";
$net2ftp_messages["Unable to open the template file"] = "Не вдалося вiдкрити тимчасовий файл";
$net2ftp_messages["Unable to read the template file"] = "Не вдалося прочитати тимчасовий файл";
$net2ftp_messages["Unable to get the list of packages"] = "Не вдається одержати список пакетів";

// /skins/blue/install1.template.php
$net2ftp_messages["The net2ftp installer script has been copied to the FTP server."] = "Скрипт встановлення net2ftp був скопійований на FTP сервер.";
$net2ftp_messages["This script runs on your web server and requires PHP to be installed."] = "Цей скрипт виконується на Вашому сервері та вимагає навність PHP.";
$net2ftp_messages["In order to run it, click on the link below."] = "Для запуску натисніть посилання нижче";
$net2ftp_messages["net2ftp has tried to determine the directory mapping between the FTP server and the web server."] = "net2ftp спробував визначити зв'язок директорій між FTP та веб-сервером.";
$net2ftp_messages["Should this link not be correct, enter the URL manually in your web browser."] = "Should this link not be correct, enter the URL manually in your web browser.";

} // end install


// -------------------------------------------------------------------------
// Java upload module
if ($net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload directories and files using a Java applet"] = "Завантажити файли та директорiї використовуючи Java апплет";
$net2ftp_messages["Number of files:"] = "Кількість файлiв:";
$net2ftp_messages["Size of files:"] = "Розмір файлiв:";
$net2ftp_messages["Add"] = "Додати";
$net2ftp_messages["Remove"] = "Видалити";
$net2ftp_messages["Upload"] = "Завантажити на серв";
$net2ftp_messages["Add files to the upload queue"] = "Додати файли в чергу закачки";
$net2ftp_messages["Remove files from the upload queue"] = "Видалити файли из черги закачки";
$net2ftp_messages["Upload the files which are in the upload queue"] = "Завантажити файли з черги завантажень";
$net2ftp_messages["Maximum server space exceeded. Please select less/smaller files."] = "Досягнутий максимум дискового простору на сервері. Будь ласка, виберіть менше файлів або файли меншого розміру.";
$net2ftp_messages["Total size of the files is too big. Please select less/smaller files."] = "Загальний розмір файлів занадто великий. Будь ласка, виберіть менше файлів або файли меншого розміру";
$net2ftp_messages["Total number of files is too high. Please select fewer files."] = "Кількість файлів занадто велика. Будь ласка, виберіть менше.";
$net2ftp_messages["Note: to use this applet, Sun's Java plugin must be installed (version 1.4 or newer)."] = "Увага: щоб використовувати апплет, необхідно мати встановлений плагін Sun Java (версії 1.4 або вище)";

} // end jupload



// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login") {
// -------------------------------------------------------------------------
$net2ftp_messages["Login!"] = "Увійти!";
$net2ftp_messages["Once you are logged in, you will be able to:"] = "Коли Ви здійсните вхід, Ви зможете:";
$net2ftp_messages["Navigate the FTP server"] = "Переглядати папки та файли на FTP-сервері";
$net2ftp_messages["Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."] = "Коли Ви здійсните вхід, Ви зможете переходити від папки до папки та переглядати усі файли та підпапки.";
$net2ftp_messages["Upload files"] = "Завантажити файли на сервер";
$net2ftp_messages["There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet."] = "Є 3 способи завантажити файли: стандартна форма завантаження, функція upload-and-unzіp (завантажити та розпакувати) та з допомогою Java-апплета.";
$net2ftp_messages["Download files"] = "Завантажити файли на комп";
$net2ftp_messages["Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive."] = "Натисніть на ім'я файлу щоб швидко завантажити один файл<br />Виберіть трохи файлів та натисніть Завантажити на комп - вибрані файли будуть скачані як zіp-архів.";
$net2ftp_messages["Zip files"] = "Запакувати файли";
$net2ftp_messages["... and save the zip archive on the FTP server, or email it to someone."] = "... та зберегти архів на сервері або відправити поштою.";
$net2ftp_messages["Unzip files"] = "Розпакувати файли";
$net2ftp_messages["Different formats are supported: .zip, .tar, .tgz and .gz."] = "Підтримуються різні формати: .zip, .tar, .tgz та .gz.";
$net2ftp_messages["Install software"] = "Встановити ПО";
$net2ftp_messages["Choose from a list of popular applications (PHP required)."] = "Вибрати зі списку популярних додатків (потрібно PHP).";
$net2ftp_messages["Copy, move and delete"] = "Копіювати, переміщати та видаляти";
$net2ftp_messages["Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted."] = "Папки обробляються рекурсивно, що означає, що всі підпапки та файли в них також будуть скопійовані, переміщені або видалені.";
$net2ftp_messages["Copy or move to a 2nd FTP server"] = "Скопіювати або перемістити на другий FTP-сервер";
$net2ftp_messages["Handy to import files to your FTP server, or to export files from your FTP server to another FTP server."] = "Зручно для того, щоб імпортувати файли на FTP-сервер або відправити файлв з Вашого FTP-сервера на інший FTP-сервер.";
$net2ftp_messages["Rename and chmod"] = "Перейменовувати та змінювати права доступу";
$net2ftp_messages["Chmod handles directories recursively."] = "Виконати chmod рекурсивно.";
$net2ftp_messages["View code with syntax highlighting"] = "Перегляд коду з підсвічуванням синтаксису";
$net2ftp_messages["PHP functions are linked to the documentation on php.net."] = "PHP функції зазначені на сайті php.net.";
$net2ftp_messages["Plain text editor"] = "Текстовий редактор";
$net2ftp_messages["Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server."] = "Редагувати текст прямо в браузері. Щораз, коли Ви зберігаєте зміни, вони копіюються на FTP-сервер.";
$net2ftp_messages["HTML editors"] = "HTML редактор";
$net2ftp_messages["Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from."] = "Редагувати HTML редактором WYSІWYG. Можна вибрати один із двох редакторів.";
$net2ftp_messages["Code editor"] = "Редактор коду";
$net2ftp_messages["Edit HTML and PHP in an editor with syntax highlighting."] = "Редагувати HTML та PHP з підсвічуванням синтаксису.";
$net2ftp_messages["Search for words or phrases"] = "Шукати слова та фрази";
$net2ftp_messages["Filter out files based on the filename, last modification time and filesize."] = "Фільтрувати файли по імені, зміни часу та розмірові.";
$net2ftp_messages["Calculate size"] = "Підрахувати розмір";
$net2ftp_messages["Calculate the size of directories and files."] = "Підрахувати розмір директорій та файлів.";

$net2ftp_messages["FTP server"] = "FTP-сервер";
$net2ftp_messages["Example"] = "Приклад";
$net2ftp_messages["Port"] = "Порт";
$net2ftp_messages["Username"] = "Логiн";
$net2ftp_messages["Password"] = "Пароль";
$net2ftp_messages["Anonymous"] = "Анонімно";
$net2ftp_messages["Passive mode"] = "Пасивний режим";
$net2ftp_messages["Initial directory"] = "Папка";
$net2ftp_messages["Language"] = "Мова";
$net2ftp_messages["Skin"] = "Оформлення";
$net2ftp_messages["FTP mode"] = "Режим работи FTP";
$net2ftp_messages["Automatic"] = "Автоматичний";
$net2ftp_messages["Login"] = "Вхiд";
$net2ftp_messages["Clear cookies"] = "Очистити кукі";
$net2ftp_messages["Admin"] = "Адмiнiстратор";
$net2ftp_messages["Please enter an FTP server."] = "Будь ласка, введiть FTP сервер.";
$net2ftp_messages["Please enter a username."] = "Будь ласка, введiть iм'я користувача.";
$net2ftp_messages["Please enter a password."] = "Будь ласка, введiть пароль.";

} // end login


// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login_small") {
// -------------------------------------------------------------------------

$net2ftp_messages["Please enter your Administrator username and password."] = "Будь ласка, введiть логiн та пароль адміністратора.";
$net2ftp_messages["Please enter your username and password for FTP server <b>%1\$s</b>."] = "Будь ласка, введiть логiн та пароль до FTP серверу <b>%1\$s</b>.";
$net2ftp_messages["Username"] = "Логiн";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Час дії сесії минув; будь ласка, уведіть Ваш пароль до FTP сервера <b>%1\$s</b> щоб продовжити.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Ваш IP адреса змінилася; будь ласка, введiть Ваш пароль вiд FTP сервера <b>%1\$s</b> щоб продовжити.";
$net2ftp_messages["Password"] = "Пароль";
$net2ftp_messages["Login"] = "Вхiд";
$net2ftp_messages["Continue"] = "Продовжити";

} // end login_small


// -------------------------------------------------------------------------
// Logout module
if ($net2ftp_globals["state"] == "logout") {
// -------------------------------------------------------------------------

// logout.inc.php
$net2ftp_messages["Login page"] = "Сторiнка входу";

// logout.template.php
$net2ftp_messages["You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>."] = "Ви вийшли з FTP сервера. Щоб зайти назад, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">натисніть на це посилання</a>.";
$net2ftp_messages["Note: other users of this computer could click on the browser's Back button and access the FTP server."] = "Увага: інші користувачі цього комп'ютера можуть натиснути на кнопку \"назад\" та одержати доступ до FTP сервера.";
$net2ftp_messages["To prevent this, you must close all browser windows."] = "Щоб запобігти цьому, закрийте усі вікна браузера.";
$net2ftp_messages["Close"] = "Закрити";
$net2ftp_messages["Click here to close this window"] = "Натисніть тут, щоб закрити вікно";

} // end logout


// -------------------------------------------------------------------------
// New directory module
if ($net2ftp_globals["state"] == "newdir") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create new directories"] = "Створити новi папки";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "Нові папки будут створені в <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "Нове iм'я папки:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "Папка <b>%1\$s</b> була успiшно створена.";
$net2ftp_messages["Directory <b>%1\$s</b> could not be created."] = "Директорiя <b>%1\$s</b> не може бути створена.";

} // end newdir


// -------------------------------------------------------------------------
// Raw module
if ($net2ftp_globals["state"] == "raw") {
// -------------------------------------------------------------------------

// /modules/raw/raw.inc.php
$net2ftp_messages["Send arbitrary FTP commands"] = "Вiдправлення випадкової FTP команди";


// /skins/[skin]/raw1.template.php
$net2ftp_messages["List of commands:"] = "Список команд:";
$net2ftp_messages["FTP server response:"] = "Відповідь FTP сервера:";

} // end raw


// -------------------------------------------------------------------------
// Rename module
if ($net2ftp_globals["state"] == "rename") {
// -------------------------------------------------------------------------
$net2ftp_messages["Rename directories and files"] = "Перейменувати папки та файли";
$net2ftp_messages["Old name: "] = "Старе iм'я: ";
$net2ftp_messages["New name: "] = "Нове iм'я: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "Ім'я не може містити крапок. Не було перейменовано в <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "Ім'я не може містити заборонені слова. Файл не був перейменований у <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> було успiшно переименовано в <b>%2\$s</b>";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> не вдалося перейменувати в <b>%2\$s</b>";

} // end rename


// -------------------------------------------------------------------------
// Unzip module
if ($net2ftp_globals["state"] == "unzip") {
// -------------------------------------------------------------------------

// /modules/unzip/unzip.inc.php
$net2ftp_messages["Unzip archives"] = "Распокавать архіви";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Отримання архiву %1\$s of %2\$s з FTP сервера";
$net2ftp_messages["Unable to get the archive <b>%1\$s</b> from the FTP server"] = "Не вдалося одержати архiв <b>%1\$s</b> з FTP сервера";

// /skins/[skin]/unzip1.template.php
$net2ftp_messages["Set all targetdirectories"] = "Вибрати всі папки";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "Щоб задати головну папку, введіть її назву в поле вище та виберіть пункт \"Вибрати всі папки\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "Примітка: папка повинна вже існувати.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Розпакувати архiв <b>%1\$s</b> to:";
$net2ftp_messages["Target directory:"] = "Папка призначення:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Використовувати імена папок (створювати підпапки автоматично)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "Оновити файл";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>УВАГА: ЦЯ ФУНКЦІЯ ЗНАХОДИТЬСЯ НА ПОЧАТКОВІЙ СТАДІЇ РОЗВИТКУ. ВИКОРИСТОВУЙТЕ ТІЛЬКИ ДЛЯ ТЕСТУВАННЯ! ВИ БУЛИ ПОПЕРЕДЖЕНІ!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "Відомі помилки: - символи вкладки видаляються - погано працює з великими файлами (> 50Кб) - не тестувалося на файлах з нестандартними символами</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "Ця функція дозволяє вам завантажити файл, переглянути, дозволити або скасувати зміни. Перед збереженням, ви можете редагувати розділені файли.";
$net2ftp_messages["Old file:"] = "Старий файл:";
$net2ftp_messages["New file:"] = "Новий файл:";
$net2ftp_messages["Restrictions:"] = "Обмеження:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Максимальний розмір одного файлу обмежений net2ftp до <b>%1\$s Кб</b> та PHP до <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Максимальний час виконання <b>%1\$s секунд</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "Режим передачі FTP (ASCІІ або BІNARY) буде автоматично визначений, заснований на розширенні";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Якщо файл вже iснує, вiн буде перезаписаний";
$net2ftp_messages["You did not provide any files or archives to upload."] = "Ви не вказали файли або архіви для накачування.";
$net2ftp_messages["Unable to delete the new file"] = "Не вдалося видалити новий файл";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "Будь ласка, зачекайте...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "Виберіть лінії нижче, дозволіть або скасуйте зміни та натисніть кнопку Відправити.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "Завантажити в папку:";
$net2ftp_messages["Files"] = "Файли";
$net2ftp_messages["Archives"] = "Архiви";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "Файли, введені тут будуть переміщені на FTP-сервер.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "Архіви введені тут будуть розпаковані та файли будуть переміщені на FTP-сервер.";
$net2ftp_messages["Add another"] = "Додати інший";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "Використовувати імена папок (створювати підпапки автоматично)";

$net2ftp_messages["Choose a directory"] = "Виберiть папку";
$net2ftp_messages["Please wait..."] = "Будь ласка, зачекайте...";
$net2ftp_messages["Uploading... please wait..."] = "Завантаження... зачекайте...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "Якщо закачування займає більш <b>%1\$s секунд<\/b>, спробуйте завантажити менше або менші файли.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "Це вікно автоматично закриється через кілька секунд.";
$net2ftp_messages["Close window now"] = "Закрити вікно зараз";

$net2ftp_messages["Upload files and archives"] = "Завантажити файли та папки";
$net2ftp_messages["Upload results"] = "Результати закачування";
$net2ftp_messages["Checking files:"] = "Перевiрка файлiв:";
$net2ftp_messages["Transferring files to the FTP server:"] = "Перемiщення файлiв на FTP-сервер:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "Розпакування та переміщення файлiв на сервер:";
$net2ftp_messages["Upload more files and archives"] = "Завантажити інші файли та архіви";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "Обмеження:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "Максимальний розмір одного файлу обмежений net2ftp до <b>%1\$s Кб</b> та PHP до <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "Максимальний час виконання <b>%1\$s секунд</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "Режим передачі FTP (ASCІІ або BІNARY) буде автоматично визначений, заснований на розширенні";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "Якщо файл вже iснує, вiн буде перезаписаний";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "Перегляд файлу %1\$s";
$net2ftp_messages["View image %1\$s"] = "Переглянути малюнок %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "Переглянути ролик Macromedia ShockWave Flash %1\$s";
$net2ftp_messages["Image"] = "Зображення";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Підсвічування синтаксису реалізоване <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "Для збереження натисніть праву клавішу миші та виберіть 'Save picture as...'";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "Вмiст Zip";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "Зберегти zip-файл на FTP-серверi як:";
$net2ftp_messages["Email the zip file in attachment to:"] = "Email zip-файл прикріпленим:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "Заметьте, что отправка файлiв не анонимна: ваш IP-адреса так же як та время відправленийия буде добавлен в email.";
$net2ftp_messages["Some additional comments to add in the email:"] = "Коментарi до email:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "Ви не ввели iм'я файла для zip. Повернiться назад та введiть iм'я файла.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "Email адреса, яку ви ввели (%1\$s) неправильна.<br />Будь ласка, введiть адресу в форматi <b>iм'я_корстувача@домен.uа</b>";

} // end zip

?>