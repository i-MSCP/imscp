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
//  |     $messages[...] = ["Le fichier %1\$s a �t� copi� vers %2\$s "]             |
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
$net2ftp_messages["en"] = "ar";

// HTML dir attribute: left-to-right (LTR) or right-to-left (RTL)
$net2ftp_messages["ltr"] = "rtl";

// CSS style: align left or right (use in combination with LTR or RTL)
$net2ftp_messages["left"] = "right";
$net2ftp_messages["right"] = "left";

// Encoding
$net2ftp_messages["iso-8859-1"] = "UTF-8";


// -------------------------------------------------------------------------
// Status messages
// -------------------------------------------------------------------------

// When translating these messages, keep in mind that the text should not be too long
// It should fit in the status textbox

$net2ftp_messages["Connecting to the FTP server"] = "الاتصال بسرفر FTP";
$net2ftp_messages["Logging into the FTP server"] = "الدخول إلى سرفر FTP";
$net2ftp_messages["Setting the passive mode"] = "إعدادات الوضع الخامل";
$net2ftp_messages["Getting the FTP system type"] = "الدخول في نمط نظام FTP";
$net2ftp_messages["Changing the directory"] = "تغيير الدليل";
$net2ftp_messages["Getting the current directory"] = "الحصول على الدليل الحالي";
$net2ftp_messages["Getting the list of directories and files"] = "الحصول على قائمة الأدلة و الملفات";
$net2ftp_messages["Parsing the list of directories and files"] = "تحليل قائمة الأدلة و الملفات";
$net2ftp_messages["Logging out of the FTP server"] = "تسجيل الخروج من سرفر FTP";
$net2ftp_messages["Getting the list of directories and files"] = "الحصول على قائمة الأدلة و الملفات";
$net2ftp_messages["Printing the list of directories and files"] = "طباعة قائمة الأدلة و الملفات";
$net2ftp_messages["Processing the entries"] = "معالجة العناصر";
$net2ftp_messages["Processing entry %1\$s"] = "معالجة العنصر %1\$s";
$net2ftp_messages["Checking files"] = "تفحص الملفات";
$net2ftp_messages["Transferring files to the FTP server"] = "ترحيل الملفات إلى سرفر FTP";
$net2ftp_messages["Decompressing archives and transferring files"] = "فك ضغط الأرشيف و ترحيل الملفات";
$net2ftp_messages["Searching the files..."] = "جاري البحث عن الملفات ...";
$net2ftp_messages["Uploading new file"] = "جاري رفع الملف الجديد";
$net2ftp_messages["Reading the file"] = "قراءة الملف";
$net2ftp_messages["Parsing the file"] = "تحليل الملف";
$net2ftp_messages["Reading the new file"] = "قراءة الملف الجديد";
$net2ftp_messages["Reading the old file"] = "قراءة الملف القديم";
$net2ftp_messages["Comparing the 2 files"] = "مقارنة الملفين";
$net2ftp_messages["Printing the comparison"] = "طباعة المقارنة";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "إرسال أمر FTP %1\$s من %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "جلب أرشيف %1\$s من %2\$s من سرفر FTP";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "إنشاء دليل مؤقت على سرفر FTP";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "إعداد تصاريح الدليل المؤقت";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "نسخ معالج net2ftp إلى سرفر FTP";
$net2ftp_messages["Script finished in %1\$s seconds"] = "الوقت المستغرق %1\$s ثانية";
$net2ftp_messages["Script halted"] = "تعثر المعالج";

// Used on various screens
$net2ftp_messages["Please wait..."] = "يرجى الانتظار ...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "حالة غير مقبولة » %1\$s . موجود .";
$net2ftp_messages["This beta function is not activated on this server."] = "وظيفة الاختبار غير نشطة على هذا السرفر .";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "هذه الوظيفة تم تعطيلها من قبل إدارة هذا الموقع .";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "الدليل <b>%1\$s</b> غير موجود أو لا يمكن تحديده , لذا لا يمكن عرض الدليل <b>%2\$s</b> بدلاً منه .";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "الدليل الجرز root <b>%1\$s</b> غير موجود أو لا يمكن تحديده .";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "الدليل <b>%1\$s</b> لا يمكن تحديده - ربما لاتمتلك تخويل كاف لعرض هذا الدليل , أو ربما يكون غير موجود .";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "الإدخالات التي تحتوي على كلمات مفتاحية محظورة لا يمكن إدارتها بواسطة net2ftp .  ذلك لحماية Paypal أو Ebay من الغش و التلاعب .";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "الملفات الكبيرة جداً لا يمكن تحميلها ، رفعها ، نسخها ، نقلها ، البحث فيها ، ضغطها ، فك ضغطها ، عرضها أو تحريرها ؛  فقط يمكن تغيير الاسم ، التصاريح أو الحذف .";
$net2ftp_messages["Execute %1\$s in a new window"] = "تنفيذ %1\$s في نافذة جديدة";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "يرجى تحديد مجلد أو ملف واحد على الأقل !";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "سرفر FTP <b>%1\$s</b> غير موجود في قائمة سرفرات FTP المسموح بها .";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "السرفر FTP <b>%1\$s</b> موجود في قائمة سرفرات FTP المحظورة .";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "منفذ سرفر FTP %1\$s لا يمكن استخدامه .";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "عنوان IP  الخاص بك (%1\$s) غير موجود في قائمة عناوين IP المسموح بها .";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "عنوان IP الخاص بك (%1\$s) موجود في قائمة عناوين IP المحظورة .";

// isAuthorizedDirectory()
$net2ftp_messages["Table net2ftp_users contains duplicate rows."] = "الجدول net2ftp_users يحتوي على صفوف مكررة .";

// logAccess(), logLogin(), logError()
$net2ftp_messages["Unable to execute the SQL query."] = "تعذر تنفيذ استعلام SQL .";
$net2ftp_messages["Unable to open the system log."] = "Unable to open the system log.";
$net2ftp_messages["Unable to write a message to the system log."] = "Unable to write a message to the system log.";

// checkAdminUsernamePassword()
$net2ftp_messages["You did not enter your Administrator username or password."] = "لم تقم بإدخال اسم المستخدم للإدارة أو كلمة المرور !";
$net2ftp_messages["Wrong username or password. Please try again."] = "خطأ في اسم المستخدم أو كلمة المرور . يرجى المحاولة من جديد !";


// -------------------------------------------------------------------------
// /includes/consumption.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to determine your IP address."] = "تعذر تحديد عنوان IP الخاص بك .";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "الجدول net2ftp_log_consumption_ipaddress يحتوي على صفوف مكررة .";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "الجدول net2ftp_log_consumption_ftpserver يحتوي على صفوف مكررة .";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "المتغير <b>consumption_ipaddress_datatransfer</b> ليس عددي .";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "لا يمكن تحديث الجدول net2ftp_log_consumption_ipaddress .";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "الجدول net2ftp_log_consumption_ipaddress يحتوي على عناصر مكررة .";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "لا يمكن تحديث الجدول net2ftp_log_consumption_ftpserver .";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "الجدول net2ftp_log_consumption_ftpserver يحتوي على عناصر مكررة .";
$net2ftp_messages["Table net2ftp_log_access could not be updated."] = "لا يمكن تحديث الجدول net2ftp_log_access .";
$net2ftp_messages["Table net2ftp_log_access contains duplicate entries."] = "يتضمن الجدول net2ftp_log_access مدخلات متكررة .";


// -------------------------------------------------------------------------
// /includes/database.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unable to connect to the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "تعذر الاتصال بقاعدة البيانات MySQL . يرجى التأكد من صحة معلوماتك المدخلة في الملف settings.inc.php.";
$net2ftp_messages["Unable to select the MySQL database. Please check your MySQL database settings in net2ftp's configuration file settings.inc.php."] = "تعذر تحديد قاعدة البيانات MySQL . يرجى التأكد من صحة معلوماتك المدخلة في الملف settings.inc.php.";


// -------------------------------------------------------------------------
// /includes/errorhandling.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["An error has occured"] = "حدث خطأ";
$net2ftp_messages["Go back"] = "العودة للخلف";
$net2ftp_messages["Go to the login page"] = "الذهاب إلى صفحة الدخول";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = "<a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">وظيفة FTP لـ PHP</a> غير مثبتة .<br /><br /> على إدارة الموقع تثبيت وظيفة FTP . تعليمات التثبيت تجدها في <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "تعذر الاتصال بسرفر FTP <b>%1\$s</b> على المنفذ <b>%2\$s</b>.<br /><br />هل أنت متأكد من صحة عنوان سرفر FTP ؟ هذا يحصل لأسباب مختلفة من سرفر HTTP (ويب) . يرجى الاتصال بمخدم ISP أو مدير النظام للمساعدة .<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "تعذر الدخول إلى سرفر FTP <b>%1\$s</b> بواسطة اسم المستخدم <b>%2\$s</b>.<br /><br />هل انت متأكد من صحة اسم المستخدم و كلمة المرور ؟ يرجى الاتصال بمخدم ISP أو مدير النظام للمساعدة .<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "تعذر التبديل إلى النمط الخامل passive على سرفر FTP <b>%1\$s</b>.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "تعذر الاتصال بسرفر FTP الثاني (الهدف) <b>%1\$s</b> على المنفذ <b>%2\$s</b>.<br /><br />هل انت متأكد من صحة عنوان سرفر FTP الثاني (الهدف) ؟ هذا يحدث لأسباب مختلفة من سرفر HTTP (ويب) . يرجى الاتصال بمخدم ISP أو مدير النظام للمساعدة .<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "تعذر الدخول إلى سرفر FTP الثاني (الهدف) <b>%1\$s</b> بواسطة اسم المستخدم <b>%2\$s</b>.<br /><br />هل أنت متأكد من صحة اسم المستخدم و كلمة المرور ؟ يرجى الاتصال بمخدم ISP أو مدير النظام للمساعدة .<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "تعذر التبديل إلى النمط الخامل passive على سرفر FTP الثاني (الهدف) <b>%1\$s</b>.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "تعذر إعادة تسمية المجلد أو الملف <b>%1\$s</b> إلى <b>%2\$s</b>";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "تعذر تنفيذ أمر الموقع <b>%1\$s</b>. لاحظ ان أمر التصريح CHMOD متاح فقط على سرفرات Unix FTP , و غير متاح على سرفرات Windows FTP ..";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "تم تغيير تصريح المجلد <b>%1\$s</b> إلى <b>%2\$s</b> بنجاح ! ";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "معالجة العناصر في المجلد <b>%1\$s</b> »";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "تم تغيير تصريح الملف <b>%1\$s</b> إلى <b>%2\$s</b> بنجاح !";
$net2ftp_messages["All the selected directories and files have been processed."] = "تمت معالجة جميع الأدلة و الملفات المحددة .";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "تعذر حذف المجلد <b>%1\$s</b>";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "تعذر حذف الملف <b>%1\$s</b>";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "تعذر إنشاء المجلد <b>%1\$s</b>";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "تعذر إنشاء ملف التخزين المؤقت";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "تعذر جلب الملف <b>%1\$s</b> من سرفر FTP و حفظه في ملف التخزين المؤقت <b>%2\$s</b>.<br />تفحص صلاحيات المجلد %3\$s .<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "تعذر فتح ملف التخزين المؤقت . تفحص صلاحيات المجلد %1\$s .";
$net2ftp_messages["Unable to read the temporary file"] = "تعذر قراءة ملف التخزين المؤقت";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "تعذر إغلاق ملف التخزين المؤقت";
$net2ftp_messages["Unable to delete the temporary file"] = "تعذر حذف ملف التخزين المؤقت";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "تعذر إنشاء ملف التخزين المؤقت . تفحص صلاحيات المجلد %1\$s .";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "تعذر فتح ملف التخزين المؤقت . تفحص صلاحيات المجلد %1\$s .";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "تعذر الكتابة إلى ملف التخزين المؤقت <b>%1\$s</b>.<br />تفحص صلاحيات المجلد %2\$s .";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "تعذر إغلاق ملف التخزين المؤقت";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "تعذر وضع الملف <b>%1\$s</b> على سرفر FTP .<br />ربما لا تمتلك صلاحيات الكتابة إلى هذا الدليل !";
$net2ftp_messages["Unable to delete the temporary file"] = "تعذر حذف ملف التخزين المؤقت";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "Processing directory <b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "الدليل الهدف <b>%1\$s</b> نفس المصدر أو دليل فرعي من الدليل المصدر <b>%2\$s</b>, لذا سيتم تخطي هذا الدليل .";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "الدليل <b>%1\$s</b> يحتوي على كلمات مفتاحية محظورة ، لذا سيتم تخطي هذا الدليل";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "الدليل <b>%1\$s</b> يحتوي على كلمات مفتاحية محظورة ، تم إلغاء النقل";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "تعذر إنشاء الدليل الفرعي <b>%1\$s</b>. ربما يكون موجود من . متابعة عملية النسخ/النقل ...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "إنشاء الدليل الفرعي الهدف <b>%1\$s</b>";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "الدليل <b>%1\$s</b> لا يمكن تحديده . لذا سيتم تخطي هذا الدليل .";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "تعذر حذف الدليل الفرعي <b>%1\$s</b> - ربما يكون فارغ";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "تم حذف الدليل الفرعي <b>%1\$s</b>";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "تمت معالجة الدليل <b>%1\$s</b>";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "الدليل الهدف للملف <b>%1\$s</b> يبدو أنه كالمصدر , لذا سيتم تخطي هذا الملف";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "الملف <b>%1\$s</b> يحتوي على كلمات مفتاحية محظورة ، لذا سيتم تخطي هذا الملف";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "الملف <b>%1\$s</b> يحتوي على كلمات مفتاحية محظورة ، تم إلغاء النقل";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "الملف <b>%1\$s</b> كبير جداً كي ينسخ ، لذا سيتم تجاوزه";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "الملف <b>%1\$s</b> كبير جداً كي ينقل ، تم إلغاء النقل";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "تعذر نسخ الملف <b>%1\$s</b>";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "تم نسخ الملف <b>%1\$s</b>";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "تعذر نقل الملف <b>%1\$s</b>, تم إلغاء العملية";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "تم نقل الملف <b>%1\$s</b>";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "تعذر حذف الملف <b>%1\$s</b>";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "تم حذف الملف <b>%1\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "تمت معالجة جميع الأدلة و الملفات المحددة .";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "تعذر نسخ الملف البعيد <b>%1\$s</b> إلى الملف المحلي باستخدام نمط FTP <b>%2\$s</b>";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "تعذر حذف الملف <b>%1\$s</b>";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "كبير جداً كي يتم ترحيله";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "الحصة اليومية المسموح بها استنفذت » الملف <b>%1\$s</b> لن يتم ترحيله";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "تعذر نسخ الملف المحلي إلى الملف البعيد <b>%1\$s</b> باستخدام نمط FTP <b>%2\$s</b>";
$net2ftp_messages["Unable to delete the local file"] = "تعذر حذف الملف المحلي";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "تعذر حذف ملف التخزين المؤقت";
$net2ftp_messages["Unable to send the file to the browser"] = "تعذر إرسال الملف إلى المستعرض";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "تعذر إنشاء ملف التخزين المؤقت";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "تم حفظ الملف المضغوط zip إلى سرفر FTP باسم <b>%1\$s</b>";
$net2ftp_messages["Requested files"] = "الملفات المطلوبة";

$net2ftp_messages["Dear,"] = "السلام عليكم ,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "شخص ما طلب إرسال الملفات المرفقة إلى عنوان البريد الالكتروني (%1\$s) .";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "إن لم تكن تعرف شئ حول هذا , أو إن لم تكن معني بهذا الشخص , يرجى حذف الرسالة بدون فتح الملف المضغوط المرفق .";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "ملاحظة - إن لم تقم بفتح الملف المضغوط , فلن تلحق الملفات التي بداخله أي أذى بجهازك إن كنت تشك بها .";
$net2ftp_messages["Information about the sender: "] = "معلومات حول المرسل » ";
$net2ftp_messages["IP address: "] = "عنوان IP » ";
$net2ftp_messages["Time of sending: "] = "وقت الإرسال » ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "أرسلت بواسطة برنامج net2ftp المركب على هذا الموقع » ";
$net2ftp_messages["Webmaster's email: "] = "بريد الإدارة » ";
$net2ftp_messages["Message of the sender: "] = "رسالة المرسل » ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp برنامج مجاني ، صادر تحت الترخيص GNU/GPL .  للمزيد من المعلومات ، راجع http://www.net2ftp.com .";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "تم إرسال الملف المضغوط إلى <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "حجم الملف <b>%1\$s</b> كبير جداً . لن يتم رفع هذا الملف .";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "المف <b>%1\$s</b> يتضمن كلمات مفتاحية محظورة .  لن يتم رفع هذا الملف .";
$net2ftp_messages["Could not generate a temporary file."] = "تعذر إنشاء ملف التخزين المؤقت .";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "تعذر نقل الملف <b>%1\$s</b>";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "المف <b>%1\$s</b> نجاح !";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "تعذر نقل الملف المرفوع إلى مجلد temp .<br /><br />يجب منح التصريح <b>chmod 777</b> إلى المجلد /temp في دليل net2ftp.";
$net2ftp_messages["You did not provide any file to upload."] = "لم تقم بتحديد أي ملف لرفعه !";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "تعذر ترحيل الملف <b>%1\$s</b> إلى سرفر FTP";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "تم ترحيل الملف <b>%1\$s</b> إلى سرفر FTP باستخدام نمط FTP <b>%2\$s</b>";
$net2ftp_messages["Transferring files to the FTP server"] = "ترحيل الملفات إلى سرفر FTP";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "معالجة الأرشيف رقم %1\$s » <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "تعذر معالجة الأرشيف <b>%1\$s</b> بسبب عدم دعم هذا النوع . فقط أنواع الأرشيف zip, tar, tgz و gz مدعومة حالياً .";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "تعذر استخراج الملفات و المجلدات من الأرشيف";
$net2ftp_messages["Archive contains filenames with ../ or ..\\ - aborting the extraction"] = "Archive contains filenames with ../ or ..\\ - aborting the extraction";
$net2ftp_messages["Created directory %1\$s"] = "تم إنشاء الدليل %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "تعذر إنشاء الدليل %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "تم نسخ %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "تعذر نسخ الملف %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "تعذر حذف الدليل المؤقت";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "تعذر حذف الملف المؤقت %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "تعذر تنفيذ امر الموقع <b>%1\$s</b>";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "تم إيقاف المهمة";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "المهمة التي تريد إنجازها بواسطة net2ftp استغرقت وقت أطول من المسموح %1\$s ثانية , و لذلك تم إيقاف المهمة .";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "هذا الوقت المحدد لضمان عدالة استخدام السرفر للجميع .";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "جرب تجزئة مهمتك إلى مهمات أصغر » قلل من عدد الملفات المحددة , و احذف الملفات الأكبر .";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "إذا كنت تريد حقاً تمكين net2ftp من إنجاز المهام الكبيرة التي تستغرق وقت طويل , يمكنك التفكير في تركيب برنامج net2ftp على موقعك مباشرة .";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "لم تقدم أي نص لإرساله بواسطة البريد الالكتروني !";
$net2ftp_messages["You did not supply a From address."] = "يرجى كتابة عنوان بريد المرسل !";
$net2ftp_messages["You did not supply a To address."] = "يرجى كتابة عنوان بريد المتلقي !";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "حدث خطأ تقني خلال محاولة الإرسال إلى <b>%1\$s</b> تعذر الإرسال .";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "يرجى إدخال اسم المستخدم و كلمة المرور لسرفر FTP ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "لم تقم بكتابة معلومات الدخول في نافذة البوب اب .<br />اضغط على \"الذهاب إلى صفحة الدخول\" بالأسفل .";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "الدخول إلى لوحة التحكم غير متاح , بسبب عدم تعيين كلمة مرور في الملف settings.inc.php . أدخل كلمة المرور في الملف , ثم أعد تحميل هذه الصفحة .";
$net2ftp_messages["Please enter your Admin username and password"] = "يرجى إدخال اسم المستخدم و كلمة المرور الإدارية";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "لم تقم بكتابة معلومات الدخول في نافذة البوب اب .<br />اضغط على \"الذهاب إلى صفحة الدخول\" بالأسفل .";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "خطأ في اسم المستخدم أو كلمة المرور للوحة التحكم . اسم المستخدم و كلمة المرور يمكن تعيينها في الملف settings.inc.php .";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "Blue";
$net2ftp_messages["Grey"] = "Grey";
$net2ftp_messages["Black"] = "Black";
$net2ftp_messages["Yellow"] = "Yellow";
$net2ftp_messages["Pastel"] = "Pastel";

// getMime()
$net2ftp_messages["Directory"] = "الدليل";
$net2ftp_messages["Symlink"] = "Symlink";
$net2ftp_messages["ASP script"] = "ملف ASP";
$net2ftp_messages["Cascading Style Sheet"] = "ورقة أنماط متتالية";
$net2ftp_messages["HTML file"] = "ملف HTML";
$net2ftp_messages["Java source file"] = "ملف مصدر Java";
$net2ftp_messages["JavaScript file"] = "ملف JavaScript";
$net2ftp_messages["PHP Source"] = "مصدر PHP";
$net2ftp_messages["PHP script"] = "ملف PHP";
$net2ftp_messages["Text file"] = "ملف نصي";
$net2ftp_messages["Bitmap file"] = "صورة نقطية Bitmap";
$net2ftp_messages["GIF file"] = "صورة GIF";
$net2ftp_messages["JPEG file"] = "صورة JPEG";
$net2ftp_messages["PNG file"] = "صورة PNG";
$net2ftp_messages["TIF file"] = "صورة TIF";
$net2ftp_messages["GIMP file"] = "ملف GIMP";
$net2ftp_messages["Executable"] = "ملف تنفيذي";
$net2ftp_messages["Shell script"] = "ملف Shell";
$net2ftp_messages["MS Office - Word document"] = "MS Office - مستند Word";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - جدول Excel";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - عرض تقديمي PowerPoint";
$net2ftp_messages["MS Office - Access database"] = "MS Office - قاعدة بيانات Access";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - مخطط Visio";
$net2ftp_messages["MS Office - Project file"] = "MS Office - ملف مشروع";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - مستند Writer 6.0";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - قالب Writer 6.0";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - جدول Calc 6.0";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - قالب Calc 6.0";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - مستند Draw 6.0";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - قالب Draw 6.0";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - عرض تقديمي Impress 6.0";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - قالب Impress 6.0";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - قالب عام Writer 6.0";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - مستند Math 6.0";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - مستند StarWriter 5.x";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - مستند عام StarWriter 5.x";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - جدول StarCalc 5.x";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - مستند StarDraw 5.x";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - عرض تقديمي StarImpress 5.x";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - ملف StarImpress Packed 5.x";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - مستند StarMath 5.x";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - مستند StarChart 5.x";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - ملف بريد StarMail 5.x";
$net2ftp_messages["Adobe Acrobat document"] = "مستند Adobe Acrobat";
$net2ftp_messages["ARC archive"] = "أرشيف ARC";
$net2ftp_messages["ARJ archive"] = "أرشيف ARJ";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "أرشيف GZ";
$net2ftp_messages["TAR archive"] = "أرشيف TAR";
$net2ftp_messages["Zip archive"] = "أرشيف Zip";
$net2ftp_messages["MOV movie file"] = "ملف فيديو MOV";
$net2ftp_messages["MPEG movie file"] = "ملف فيديو MPEG movie file";
$net2ftp_messages["Real movie file"] = "ملف فيديو Real";
$net2ftp_messages["Quicktime movie file"] = "ملف فيديو Quicktime";
$net2ftp_messages["Shockwave flash file"] = "ملف فلاش Shockwave";
$net2ftp_messages["Shockwave file"] = "ملف Shockwave";
$net2ftp_messages["WAV sound file"] = "ملف موجة صوتية";
$net2ftp_messages["Font file"] = "ملف خط";
$net2ftp_messages["%1\$s File"] = "ملف %1\$s";
$net2ftp_messages["File"] = "ملف";

// getAction()
$net2ftp_messages["Back"] = "خطوة للخلف";
$net2ftp_messages["Submit"] = "اعتمد البيانات";
$net2ftp_messages["Refresh"] = "تحديث الصفحة";
$net2ftp_messages["Details"] = "التفاصيل";
$net2ftp_messages["Icons"] = "الرموز";
$net2ftp_messages["List"] = "القائمة";
$net2ftp_messages["Logout"] = "تسجيل الخروج";
$net2ftp_messages["Help"] = "مساعدة";
$net2ftp_messages["Bookmark"] = "أضف إلى المفضلة";
$net2ftp_messages["Save"] = "حفظ";
$net2ftp_messages["Default"] = "الافتراضي";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "دليل المساعدة";
$net2ftp_messages["Forums"] = "المنتديات";
$net2ftp_messages["License"] = "الترخيص";
$net2ftp_messages["Powered by"] = "Powered by";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "سيتم نقلك الآن إلى منتديات net2ftp . هذه المنتديات متخصصة بمواضيع برنامج net2ftp فقط  - و ليس لأسئلة الاستضافة العامة .";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "الخيارات الإدارية";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "معلومات الإصدار";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "هذا الإصدار من net2ftp قابل للتحديث .";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "تعذر جلب آخر معلومات الإصدار من سرفر net2ftp . تفحص إعدادات الأمان في مستعرضك , حيث تمنع تحميل ملف صغير من سرفر net2ftp.com .";
$net2ftp_messages["Logging"] = "الدخول";
$net2ftp_messages["Date from:"] = "التاريخ من »";
$net2ftp_messages["to:"] = "إلى »";
$net2ftp_messages["Empty logs"] = "إفراغ السجل";
$net2ftp_messages["View logs"] = "عرض السجل";
$net2ftp_messages["Go"] = "اذهب";
$net2ftp_messages["Setup MySQL tables"] = "إعداد جداول MySQL";
$net2ftp_messages["Create the MySQL database tables"] = "إنشاء جداول قاعدة البيانات MySQL";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "الخيارات الإدارية";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "اسم الملف %1\$s لا يمكن فتحه .";
$net2ftp_messages["The file %1\$s could not be opened."] = "تعذر فتح الملف %1\$s .";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "اسم الملف %1\$s لا يمكن فتحه .";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "تعذر إعداد الاتصال إلى السرفر <b>%1\$s</b> . يرجى التأكد من معلومات قاعدة البيانات التي ادخلتها .";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "تعذر تحديد قاعدة البيانات <b>%1\$s</b>.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "تعذر تنفيذ استعلام SQL  nr <b>%1\$s</b> .";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "تم تنفيذ استعلام SQL nr <b>%1\$s</b> بنجاح .";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "يرجى إدخال إعدادات قاعدة البيانات MySQL »";
$net2ftp_messages["MySQL username"] = "اسم المستخدم MySQL";
$net2ftp_messages["MySQL password"] = "كلمة المرور MySQL";
$net2ftp_messages["MySQL database"] = "قاعدة البيانات MySQL";
$net2ftp_messages["MySQL server"] = "سرفر MySQL";
$net2ftp_messages["This SQL query is going to be executed:"] = "استعلام SQL جاهز للتنفيذ »";
$net2ftp_messages["Execute"] = "تنفيذ الاستعلام";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "الإعدادات المستخدمة »";
$net2ftp_messages["MySQL password length"] = "عمق كلمة مرور MySQL";
$net2ftp_messages["Results:"] = "النتائج »";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "الخيارات الإدارية";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "تعذر تنفيذ استعلام SQL <b>%1\$s</b>.";
$net2ftp_messages["No data"] = "لا يوجد بيانات";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "الخيارات الإدارية";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "تم إفراغ الجدول <b>%1\$s</b> بنجاح !";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "تعذر إفراغ الجدول <b>%1\$s</b> !";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "تم إصلاح الجدول <b>%1\$s</b> بنجاح !";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "تعذر إصلاح الجدول <b>%1\$s</b> !";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "الخيارات الإدارية";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "اذهب";
$net2ftp_messages["Disabled"] = "معطل";
$net2ftp_messages["Advanced FTP functions"] = "وظائف FTP المتقدمة";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "إرسال أمر FTP تحكمي إلى سرفر FTP";
$net2ftp_messages["This function is available on PHP 5 only"] = "هذه الوظيفة متوفرة فقط على PHP 5";
$net2ftp_messages["Troubleshooting functions"] = "وظائف تتبع الأخطاء";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "تتبع أخطاء net2ftp على سرف الويب هذا";
$net2ftp_messages["Troubleshoot an FTP server"] = "تتبع أخطاء سرفر FTP";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "اختبار قائمة قوانين تعابير net2ftp";
$net2ftp_messages["Translation functions"] = "وظائف الترجمة";
$net2ftp_messages["Introduction to the translation functions"] = "مقدمة إلى وظائف الترجمة";
$net2ftp_messages["Extract messages to translate from code files"] = "استخراج الرسائل لترجمتها من ملفات الكود";
$net2ftp_messages["Check if there are new or obsolete messages"] = "التفحص عن وجود رسائل جديدة أو باطلة";

$net2ftp_messages["Beta functions"] = "وظائف تجريبية";
$net2ftp_messages["Send a site command to the FTP server"] = "إرسال أمر الموقع إلة سرفر FTP";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache » حماية دليل بكلمة مرور , إنشاء صفحات أخطاء مخصصة";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL » تنفيذ استعلام SQL";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "وظائف أمرا لموقع غير متاحة على هذا الويب سرفر .";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "وظائف أباتشي غير متاحة على هذا الويب سرفر .";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "وظائف MySQL غير متاحة على هذا الويب سرفر .";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "حالة 2 غير مقبولة . موجود .";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "تتبع أخطاء سرفر FTP";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "إعدادات الاتصال »";
$net2ftp_messages["FTP server"] = "سرفر FTP";
$net2ftp_messages["FTP server port"] = "منفذ سرفر FTP";
$net2ftp_messages["Username"] = "اسم المستخدم";
$net2ftp_messages["Password"] = "كلمة المرور";
$net2ftp_messages["Password length"] = "طول كلمة المرور";
$net2ftp_messages["Passive mode"] = "نمط Passive الخمول";
$net2ftp_messages["Directory"] = "الدليل";
$net2ftp_messages["Printing the result"] = "طباعة النتيجة";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "الاتصال بسرفر FTP » ";
$net2ftp_messages["Logging into the FTP server: "] = "الدخول إلى سرفر FTP » ";
$net2ftp_messages["Setting the passive mode: "] = "إعداد نمط passive الخمول » ";
$net2ftp_messages["Getting the FTP server system type: "] = "دخول نمط نظام سرفر FTP » ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "التغيير إلى الدليل %1\$s » ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "الدليل في سرفر FTP هو » %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "الحصول على قائمة الأدلة و الملفات » ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "محاولة ثانية للحصول على قائمة الأدلة و الملفات » ";
$net2ftp_messages["Closing the connection: "] = "إغلاق الاتصال » ";
$net2ftp_messages["Raw list of directories and files:"] = "قائمة الأدلة و الملفات »";
$net2ftp_messages["Parsed list of directories and files:"] = "قائمة تعابير الأدلة و الملفات »";

$net2ftp_messages["OK"] = "نجاح";
$net2ftp_messages["not OK"] = "فشل";

} // end advanced_ftpserver


// -------------------------------------------------------------------------
// Advanced_parsing module
if ($net2ftp_globals["state"] == "advanced_parsing") {
// -------------------------------------------------------------------------

$net2ftp_messages["Test the net2ftp list parsing rules"] = "اختبار قائمة قوانين تعابير net2ftp";
$net2ftp_messages["Sample input"] = "اختبار الدخل";
$net2ftp_messages["Parsed output"] = "تعبير الخرج";

} // end advanced_parsing


// -------------------------------------------------------------------------
// Advanced_webserver module
if ($net2ftp_globals["state"] == "advanced_webserver") {
// -------------------------------------------------------------------------

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "تتبع أخطاء تركيب net2ftp";
$net2ftp_messages["Printing the result"] = "طباعة النتيجة";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "التحقق من تركيب وظيفة FTP في PHP » ";
$net2ftp_messages["yes"] = "نعم";
$net2ftp_messages["no - please install it!"] = "لا - يرجى تركيبها !";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "التحقق من صلاحيات الدليل على سرقر الويب » سيتم كتابة ملف صغير إلى المجلد /temp ثم حذفه .";
$net2ftp_messages["Creating filename: "] = "إنشاء اسم الملف » ";
$net2ftp_messages["OK. Filename: %1\$s"] = "نجاح . اسم الملف » %1\$s";
$net2ftp_messages["not OK"] = "فشل";
$net2ftp_messages["OK"] = "نجاح";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "فشل . تأكد من صلاحيات الدليل %1\$s ";
$net2ftp_messages["Opening the file in write mode: "] = "فتح الملف في نمط الكتابة » ";
$net2ftp_messages["Writing some text to the file: "] = "كتابة بعض النص في الملف » ";
$net2ftp_messages["Closing the file: "] = "إغلاق الملف » ";
$net2ftp_messages["Deleting the file: "] = "حذف الملف » ";

$net2ftp_messages["Testing the FTP functions"] = "اختبار وظائف FTP";
$net2ftp_messages["Connecting to a test FTP server: "] = "الاتصال لاختبار سرفر FTP » ";
$net2ftp_messages["Connecting to the FTP server: "] = "الاتصال بسرفر FTP » ";
$net2ftp_messages["Logging into the FTP server: "] = "الدخول إلى سرفر FTP » ";
$net2ftp_messages["Setting the passive mode: "] = "إعداد نمط passive الخمول » ";
$net2ftp_messages["Getting the FTP server system type: "] = "دخول نمط نظام سرفر FTP » ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "التغيير إلى الدليل %1\$s » ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "الدليل في سرفر FTP هو » %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "الحصول على قائمة الأدلة و الملفات » ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "محاولة ثانية للحصول على قائمة الأدلة و الملفات » ";
$net2ftp_messages["Closing the connection: "] = "إغلاق الاتصال » ";
$net2ftp_messages["Raw list of directories and files:"] = "قائمة الأدلة و الملفات »";
$net2ftp_messages["Parsed list of directories and files:"] = "قائمة تعابير الأدلة و الملفات »";
$net2ftp_messages["OK"] = "نجاح";
$net2ftp_messages["not OK"] = "فشل";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "أضف هذا الرابط إلة مفضلتك »";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer » اضغط بالزر الأيمن فوق الرابط و اختر \"إضافة إلى المفضلة...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox » اضغط بالزر الأيمن فوق الرابط و اختر \"أضف هذا الرابط إلى المفضلة...\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "ملاحظة » عند استخدام الاختصار من المفضلة , سيطلب منك بواسطة نافذة بوب اب إدخال اسم المستخدم و كلمة المرور .";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "اختر دليل";
$net2ftp_messages["Please wait..."] = "يرجى الانتظار ...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "الأدلة التي تحتوي اسمائها على \' لا يمكن عرضها بشكل صحيح . يمكن فقط حذفها . يرجى العودة للخلف و اختيار دليل فرعي آخر .";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "الحصة اليومية انتهت » لا يمكنك متابعة ترحيل البيانات .";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "لضمان استخدام السرفر ويب للجميع , تم تحديد حصة يومية لترحيل البيانات و الملفات لكل مستخدم . عند استهلاكك لهذه الحصة , تسطيع استعراض سرفر FTP و لكن لا يمكنك متابعة نقل البيانات من و إلى .";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "إذا كنت تريد استخدام هذه اخدمة بدون حدود , يمكنك تركيب net2ftp على سرفرك الخاص .";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "دليل جديد";
$net2ftp_messages["New file"] = "ملف جديد";
$net2ftp_messages["HTML templates"] = "قوالب HTML";
$net2ftp_messages["Upload"] = "الرفع";
$net2ftp_messages["Java Upload"] = "الرفع بـ Java";
$net2ftp_messages["Flash Upload"] = "رفع بواسطة الفلاش";
$net2ftp_messages["Install"] = "التركيب";
$net2ftp_messages["Advanced"] = "متقدم";
$net2ftp_messages["Copy"] = "نسخ";
$net2ftp_messages["Move"] = "نقل";
$net2ftp_messages["Delete"] = "حذف";
$net2ftp_messages["Rename"] = "إعادة تسمية";
$net2ftp_messages["Chmod"] = "تصريح";
$net2ftp_messages["Download"] = "تحميل";
$net2ftp_messages["Unzip"] = "استخراج";
$net2ftp_messages["Zip"] = "Zip";
$net2ftp_messages["Size"] = "الحجم";
$net2ftp_messages["Search"] = "بحث";
$net2ftp_messages["Go to the parent directory"] = "الذهاب إلى المجلد الأصل";
$net2ftp_messages["Go"] = "اذهب";
$net2ftp_messages["Transform selected entries: "] = "تحويل العناصر المحددة » ";
$net2ftp_messages["Transform selected entry: "] = "تحويل العنصر المحدد » ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "إنشاء دليل فرعي جديد في الدليل %1\$s";
$net2ftp_messages["Create a new file in directory %1\$s"] = "إنشاء ملف جديد في الدليل %1\$s";
$net2ftp_messages["Create a website easily using ready-made templates"] = "إنشاء المواقع سهل باستخدام القوالب الجاهزة";
$net2ftp_messages["Upload new files in directory %1\$s"] = "رفع ملفات جديد إلى الدليل %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "رفع المجلدات و الملفات بواسطة Java applet";
$net2ftp_messages["Upload files using a Flash applet"] = "رفع الملفات بواسطة Flash applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "تركيب حزمة البرنامج ( يتطلب سرفر PHP على الموقع )";
$net2ftp_messages["Go to the advanced functions"] = "الذهاب إلى الوظائف المتقدمة";
$net2ftp_messages["Copy the selected entries"] = "نسخ العناصر المحددة";
$net2ftp_messages["Move the selected entries"] = "نقل العناصر المحددة";
$net2ftp_messages["Delete the selected entries"] = "حذف العناصر المحددة";
$net2ftp_messages["Rename the selected entries"] = "إعادة تسمية العناصر المحددة";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "تصريح العناصر المحددة (يعمل فقط على سرفرات Unix/Linux/BSD)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "تحميل ملف zip يحتوي على جميع العناصر المحددة";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "فك ضغط الأراشيف المحددة على سرفر FTP";
$net2ftp_messages["Zip the selected entries to save or email them"] = "ضغط Zip العناصر المحددة لحفظها أو إرسالها بالبريد";
$net2ftp_messages["Calculate the size of the selected entries"] = "حساب حجم العناصر المحددة";
$net2ftp_messages["Find files which contain a particular word"] = "إيجاد الملفات التي تتضمن الكلمة جزئياً";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "اضغط لفرز %1\$s بترتيب تنازلي";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "اضغط لفرز %1\$s بترتيب تصاعدي";
$net2ftp_messages["Ascending order"] = "ترتيب تصاعدي";
$net2ftp_messages["Descending order"] = "ترتيب تنازلي";
$net2ftp_messages["Upload files"] = "رفع الملفات";
$net2ftp_messages["Up"] = "خطوة إلى الأعلى";
$net2ftp_messages["Click to check or uncheck all rows"] = "اضغط لتحديد أو إلغاء تحديد جميع الصفوف";
$net2ftp_messages["All"] = "الكل";
$net2ftp_messages["Name"] = "الاسم";
$net2ftp_messages["Type"] = "النوع";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "المالك";
$net2ftp_messages["Group"] = "المجموعة";
$net2ftp_messages["Perms"] = "الصلاحية";
$net2ftp_messages["Mod Time"] = "نمط الوقت";
$net2ftp_messages["Actions"] = "الإجراءات";
$net2ftp_messages["Select the directory %1\$s"] = "حدد الدليل %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "حدد الملف %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "حدد symlink %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "الذهاب إلى الدليل الفرعي %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "تحميل الملف %1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "اتبع الرابط %1\$s";
$net2ftp_messages["View"] = "عرض";
$net2ftp_messages["Edit"] = "تحرير";
$net2ftp_messages["Update"] = "تحديث";
$net2ftp_messages["Open"] = "فتح";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "عرض كود المصدر المميز للملف %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "تحرير كود المصدر للملف %1\$s";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "رفع نسخة جديدة من الملف %1\$s و دمج التعديلات";
$net2ftp_messages["View image %1\$s"] = "عرض الصورة %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "عرض الملف %1\$s بواسطة سرفر الويب HTTP";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(ملاحظة » قد لا يعمل هذا الرابط إن لم يكن لديك دومين خاص .)";
$net2ftp_messages["This folder is empty"] = "هذا المجلد فارغ";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "المجلدات";
$net2ftp_messages["Files"] = "الملفات";
$net2ftp_messages["Symlinks"] = "Symlinks";
$net2ftp_messages["Unrecognized FTP output"] = "خرج FTP غير معروف";
$net2ftp_messages["Number"] = "العدد";
$net2ftp_messages["Size"] = "الحجم";
$net2ftp_messages["Skipped"] = "تم تخطيه";
$net2ftp_messages["Data transferred from this IP address today"] = "البيانات التي تم ترحيلها بواسطة هذا الأي بي اليوم";
$net2ftp_messages["Data transferred to this FTP server today"] = "البيانات التي تم ترحيلها بواسطة سفر FTP هذا اليوم";

// printLocationActions()
$net2ftp_messages["Language:"] = "اللغة »";
$net2ftp_messages["Skin:"] = "الشكل »";
$net2ftp_messages["View mode:"] = "طريقة العرض »";
$net2ftp_messages["Directory Tree"] = "شجرة الدليل";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "تنفيذ %1\$s في نافذة جديدة";
$net2ftp_messages["This file is not accessible from the web"] = "لا يمكن الوصول إلى هذا الملف من الويب";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "ضغط مزذوج للذهاب إلى الدليل الفرعي";
$net2ftp_messages["Choose"] = "اختيار";
$net2ftp_messages["Up"] = "خطوة إلى الأعلى";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "حجم المجلدات و الملفات المحددة";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "مجموع حجم المجلدات و الملفات المحددة هو »";
$net2ftp_messages["The number of files which were skipped is:"] = "عدد الملفات التي تم تخطيها هو »";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "تصريح المجلدات و الملفات";
$net2ftp_messages["Set all permissions"] = "تعيين جميع الصلاحيات";
$net2ftp_messages["Read"] = "قراءة";
$net2ftp_messages["Write"] = "كتابة";
$net2ftp_messages["Execute"] = "تنفيذ الاستعلام";
$net2ftp_messages["Owner"] = "المالك";
$net2ftp_messages["Group"] = "المجموعة";
$net2ftp_messages["Everyone"] = "أي شخص";
$net2ftp_messages["To set all permissions to the same values, enter those permissions above and click on the button \"Set all permissions\""] = "لتعيين جميع الصلاحيات إلى نفس القيمة , حدد الصلاحيات ثم اضغط زر \"تعيين جميع الصلاحيات\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "تعيين صلاحيات المجلد <b>%1\$s</b> إلى » ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "تعيين صلاحيات الملف <b>%1\$s</b> إلى » ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "تعيين صلاحيات symlink <b>%1\$s</b> إلى » ";
$net2ftp_messages["Chmod value"] = "قيمة التصريح";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "تطبيق التصريح على المجلدات الفرعية في هذا المجلد";
$net2ftp_messages["Chmod also the files within this directory"] = "تطبيق التصريح على الملفات داخل هذا المجلد";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "التصريح nr <b>%1\$s</b> خارج نطاق 000-777. يرجى المحاولة من جديد .";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "اختر دليل";
$net2ftp_messages["Copy directories and files"] = "نسخ المجلدات و الملفات";
$net2ftp_messages["Move directories and files"] = "نقل المجلدات و الملفات";
$net2ftp_messages["Delete directories and files"] = "حذف المجلدات و الملفات";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "هل انت متأكد من أنك تريد حذف هذه المجلدات و الملفات ؟";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "جميع المجلدات الفرعية و الملفات في المجلدات المحددة سوف تحذف !";
$net2ftp_messages["Set all targetdirectories"] = "تعيين جميع الأدلة الهدف";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "لتعيين دليل هدف مشترك , أدخل الدليل الهدف في الحقل النصي السابق ثم اضغط زر \"تعيين جميع الأدلة الهدف\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "ملاحظة » الدليل الهدف يجب أن يكون موجود أولاً .";
$net2ftp_messages["Different target FTP server:"] = "سرفر FTP الآخر الهدف »";
$net2ftp_messages["Username"] = "اسم المستخدم";
$net2ftp_messages["Password"] = "كلمة المرور";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "اتركه فارغ إذا كنت تريد نسخ الملفات إلى نفس سرفر FTP .";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "إذا كنت تريد نسخ الملفات إلى سرفر FTP آخر , أدخل بيانات الدخول .";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "اتركه فارغ إذا كنت تريد نقل الملفات إلى نفس سرفر FTP .";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "إذا كنت تريد نقل الملفات إلى سرفر FTP آخر , أدخل بيانات الدخول .";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "نسخ المجلد <b>%1\$s</b> إلى »";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "نقل المجلد <b>%1\$s</b> إلى »";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "المجلد <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "نسخ الملف <b>%1\$s</b> إلى »";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "نقل الملف <b>%1\$s</b> إلى »";
$net2ftp_messages["File <b>%1\$s</b>"] = "الملف <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "نسخ symlink <b>%1\$s</b> إلى »";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "نقل symlink <b>%1\$s</b> إلى »";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "Symlink <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "المجلد الهدف »";
$net2ftp_messages["Target name:"] = "اسم الهدف »";
$net2ftp_messages["Processing the entries:"] = "معالجة العناصر »";

} // end copymovedelete


// -------------------------------------------------------------------------
// Download file module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// EasyWebsite module
if ($net2ftp_globals["state"] == "easyWebsite") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create a website in 4 easy steps"] = "إنشاء موقع في 4 خطوات سهلة";
$net2ftp_messages["Template overview"] = "خلاصة القالب";
$net2ftp_messages["Template details"] = "تفاصيل القالب";
$net2ftp_messages["Files are copied"] = "تم نسخ الملفات";
$net2ftp_messages["Edit your pages"] = "تحرير صفحاتك";

// Screen 1 - printTemplateOverview
$net2ftp_messages["Click on the image to view the details of a template."] = "اضغط على الصورة لعرض تفاصيل القالب .";
$net2ftp_messages["Back to the Browse screen"] = "العودة إلى شاشة المستعرض";
$net2ftp_messages["Template"] = "القالب";
$net2ftp_messages["Copyright"] = "حقوق النشر";
$net2ftp_messages["Click on the image to view the details of this template"] = "اضغط على الصورة لعرض تفاصيل القالب .";

// Screen 2 - printTemplateDetails
$net2ftp_messages["The template files will be copied to your FTP server. Existing files with the same filename will be overwritten. Do you want to continue?"] = "سيتم نسخ ملفات القالب إلى سرفرك FTP .الملفات التي تحمل نفس الاسم سيتم الكتابة فوقها . هل ترغب بالمتابعة ؟";
$net2ftp_messages["Install template to directory: "] = "تركيب القالب في الدليل » ";
$net2ftp_messages["Install"] = "التركيب";
$net2ftp_messages["Size"] = "الحجم";
$net2ftp_messages["Preview page"] = "معاينة الصفحة";
$net2ftp_messages["opens in a new window"] = "في في نافذة جديدة";

// Screen 3
$net2ftp_messages["Please wait while the template files are being transferred to your server: "] = "يرجى الانتظار بينما يتم نسخ ملفات القالب إلى سرفرك » ";
$net2ftp_messages["Done."] = "تـم .";
$net2ftp_messages["Continue"] = "المتابعة";

// Screen 4 - printEasyAdminPanel
$net2ftp_messages["Edit page"] = "تحرير الصفحة";
$net2ftp_messages["Browse the FTP server"] = "استعراض سرفر FTP";
$net2ftp_messages["Add this link to your favorites to return to this page later on!"] = "إضافة هذا الرابط إلى مفضلتك للعودة إلى هذه الصفخة فيما بعد !";
$net2ftp_messages["Edit website at %1\$s"] = "تحرير موقع الويب في %1\$s";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer » اضغط بالزر الأيمن فوق الرابط و اختر \"إضافة إلى المفضلة...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox » اضغط بالزر الأيمن فوق الرابط و اختر \"أضف هذا الرابط إلى المفضلة...\"";

// ftp_copy_local2ftp
$net2ftp_messages["WARNING: Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing..."] = "تحذير » تعذر إنشاء الدليل الفرعي <b>%1\$s</b> . ربما يكون موجود من قبل . المتابعة...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "إنشاء الدليل الفرعي الهدف <b>%1\$s</b>";
$net2ftp_messages["WARNING: Unable to copy the file <b>%1\$s</b>. Continuing..."] = "تحذير » تعذر نسخ الملف <b>%1\$s</b> . المتابعة ...";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "تم نسخ الملف <b>%1\$s</b>";
}


// -------------------------------------------------------------------------
// Edit module
if ($net2ftp_globals["state"] == "edit") {
// -------------------------------------------------------------------------

// /modules/edit/edit.inc.php
$net2ftp_messages["Unable to open the template file"] = "تعذر فتح ملف القالب";
$net2ftp_messages["Unable to read the template file"] = "تعذر قراءة ملف القالب";
$net2ftp_messages["Please specify a filename"] = "يرجى تحديد اسم الملف";
$net2ftp_messages["Status: This file has not yet been saved"] = "الحالة » لم يتم حفظ هذا الملف بعد";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "الحالة » تم الحفظ في <b>%1\$s</b> باستخدام النمط %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "الحالة » <b>تعذر حفظ هذا الملف</b>";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "المجلد » ";
$net2ftp_messages["File: "] = "الملف » ";
$net2ftp_messages["New file name: "] = "اسم الملف الجديد » ";
$net2ftp_messages["Character encoding: "] = "صيغة الترميز » ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "ملاحظة » تغيير نوع صندوق النص سوف يحفظ هذه التعديلات";
$net2ftp_messages["Copy up"] = "نسخ إلى";
$net2ftp_messages["Copy down"] = "نسخ من";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "بحث في المجلدات و الملفات";
$net2ftp_messages["Search again"] = "بحث جديد";
$net2ftp_messages["Search results"] = "نتائج البحث";
$net2ftp_messages["Please enter a valid search word or phrase."] = "يرجى إدخال كلمة أو تعبير مقبول للبحث .";
$net2ftp_messages["Please enter a valid filename."] = "يرجى إدخال اسم ملف مقبول .";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "يرجى إدخال حجم ملف مقبول في صندوق النص \"من\" , مثال 0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "يرجى إدخال حجم ملف مقبول في صندوق النص \"إلى\" , مثال 500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "يرجى إدخال تاريخ مقبول في الحقل \"من\" بتنسيق Y-m-d .";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "يرجى إدخال تاريخ مقبول في الحقل \"إلى\" بتنسيق Y-m-d .";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "لم يتم العثور على الكلمة <b>%1\$s</b> في المجلدات و الملفات المحددة .";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "تم العثور على الكلمة <b>%1\$s</b> في الملفات التالية »";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "بحث عن كلمة أو تعبير";
$net2ftp_messages["Case sensitive search"] = "بحث مطابق لحالة الأحرف";
$net2ftp_messages["Restrict the search to:"] = "اقتصار البحث على »";
$net2ftp_messages["files with a filename like"] = "الملفات ذات اسم الملف مماثل";
$net2ftp_messages["(wildcard character is *)"] = "(محرف تعميم البحث هو *)";
$net2ftp_messages["files with a size"] = "الملفات ذات الحجم";
$net2ftp_messages["files which were last modified"] = "الملفات ذات آخر تعديل كان";
$net2ftp_messages["from"] = "من";
$net2ftp_messages["to"] = "إلى";

$net2ftp_messages["Directory"] = "الدليل";
$net2ftp_messages["File"] = "ملف";
$net2ftp_messages["Line"] = "السطر";
$net2ftp_messages["Action"] = "الإجراء";
$net2ftp_messages["View"] = "عرض";
$net2ftp_messages["Edit"] = "تحرير";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "عرض كود المصدر المميز للملف %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "تحرير كود المصدر للملف %1\$s";

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
$net2ftp_messages["Install software packages"] = "تثبيت حزمة البرنامج";
$net2ftp_messages["Unable to open the template file"] = "تعذر فتح ملف القالب";
$net2ftp_messages["Unable to read the template file"] = "تعذر قراءة ملف القالب";
$net2ftp_messages["Unable to get the list of packages"] = "تعذر جلب قائمة الحزمة";

// /skins/blue/install1.template.php
$net2ftp_messages["The net2ftp installer script has been copied to the FTP server."] = "تم نسخ معالج تريكب net2ftp إلى سرفر FTP .";
$net2ftp_messages["This script runs on your web server and requires PHP to be installed."] = "هذا المعالج يعمل على سرفر موقع و يحتاج إلى PHP ليتم تركيبه .";
$net2ftp_messages["In order to run it, click on the link below."] = "لتشغيله ، اضغط الرابط التالي .";
$net2ftp_messages["net2ftp has tried to determine the directory mapping between the FTP server and the web server."] = "حاول net2ftp المقارنة بين سرفر FTP و سرفرل موقعك .";
$net2ftp_messages["Should this link not be correct, enter the URL manually in your web browser."] = "ربما لا يكون هذا الرابط صحيح ، أدخل الرابط URL في مستعرضك يدوياً .";

} // end install


// -------------------------------------------------------------------------
// Java upload module
if ($net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload directories and files using a Java applet"] = "رفع المجلدات و الملفات بواسطة Java applet";
$net2ftp_messages["Number of files:"] = "Number of files:";
$net2ftp_messages["Size of files:"] = "Size of files:";
$net2ftp_messages["Add"] = "Add";
$net2ftp_messages["Remove"] = "Remove";
$net2ftp_messages["Upload"] = "الرفع";
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
$net2ftp_messages["Login!"] = "تسجيل الدخول !";
$net2ftp_messages["Once you are logged in, you will be able to:"] = "Once you are logged in, you will be able to:";
$net2ftp_messages["Navigate the FTP server"] = "استعراض سرفر FTP";
$net2ftp_messages["Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."] = "التنقل من مجلد إلى مجلد و استعراض جميع المجلدات الفرعية و الملفات .";
$net2ftp_messages["Upload files"] = "رفع الملفات";
$net2ftp_messages["There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet."] = "يوجد 3 طرق مختلفة لرفع الملفات » 1 - الطريقة العادية المعروفة . 2 - طريقة رفع ملف مضغوط ثم فك الضغط تلقائياً . 3 - طريقة الجافا أبليت .";
$net2ftp_messages["Download files"] = "تحميل الملفات";
$net2ftp_messages["Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive."] = "اضغط على اسم الملف للتحميل الفردي السريع .<br />حدد ملفات متعددة ثم اضغط على تحميل , يتم تحميل الملفات المحددة ضمن ملف مضغوط zip .";
$net2ftp_messages["Zip files"] = "ضغط Zip الملفات";
$net2ftp_messages["... and save the zip archive on the FTP server, or email it to someone."] = "... و حفظ الملف zip على سرفر FTP , أو إرساله بواسطة البريد الالكتروني .";
$net2ftp_messages["Unzip files"] = "استخراج الملفات";
$net2ftp_messages["Different formats are supported: .zip, .tar, .tgz and .gz."] = "الصيغ المدعومة » .zip, .tar, .tgz و .gz.";
$net2ftp_messages["Install software"] = "تركيب البرنامج";
$net2ftp_messages["Choose from a list of popular applications (PHP required)."] = "اختر من قائمة التطبيقات الشائعة ( تتطلب PHP ) .";
$net2ftp_messages["Copy, move and delete"] = "نسخ , نقل , و حذف";
$net2ftp_messages["Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted."] = "المجلدات و محتوياتها (المجلدات الفرعية و الملفات) .";
$net2ftp_messages["Copy or move to a 2nd FTP server"] = "نسخ أو نقل من و إلى سرفر FTP";
$net2ftp_messages["Handy to import files to your FTP server, or to export files from your FTP server to another FTP server."] = "استيراد الملفات إلى سرفر FTP , أو تصدير الملفات من سرفرك إلى سرفر FTP آخر .";
$net2ftp_messages["Rename and chmod"] = "إعادة التسمية و التصاريح";
$net2ftp_messages["Chmod handles directories recursively."] = "تغير أسماء المجلدات و الملفات و تغيير التصاريح .";
$net2ftp_messages["View code with syntax highlighting"] = "عرض الكود مع تمييز المصدر";
$net2ftp_messages["PHP functions are linked to the documentation on php.net."] = "ارتباطات لوثائق وظائف PHP على php.net.";
$net2ftp_messages["Plain text editor"] = "محرر نصوص عادية";
$net2ftp_messages["Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server."] = "تحرير النص بواسطة المستعرض .";
$net2ftp_messages["HTML editors"] = "محرر HTML";
$net2ftp_messages["Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from."] = "محرر HTML متقدم (WYSIWYG) , ما تشاهده تحصل عليه , يمكنك الاختيار بين محررين .";
$net2ftp_messages["Code editor"] = "محرر الكود";
$net2ftp_messages["Edit HTML and PHP in an editor with syntax highlighting."] = "تحرير كود HTML و PHP مع التمييز .";
$net2ftp_messages["Search for words or phrases"] = "بحث عن كلمات أو تعبير برمجي";
$net2ftp_messages["Filter out files based on the filename, last modification time and filesize."] = "فلترة على أساس اسم الملف , وقت آخر تحرير و حجم الملف .";
$net2ftp_messages["Calculate size"] = "حساب الحجم";
$net2ftp_messages["Calculate the size of directories and files."] = "حساب حجم المجلدات و الملفات .";

$net2ftp_messages["FTP server"] = "سرفر FTP";
$net2ftp_messages["Example"] = "مثال";
$net2ftp_messages["Port"] = "المنفذ";
$net2ftp_messages["Username"] = "اسم المستخدم";
$net2ftp_messages["Password"] = "كلمة المرور";
$net2ftp_messages["Anonymous"] = "Anonymous";
$net2ftp_messages["Passive mode"] = "نمط Passive الخمول";
$net2ftp_messages["Initial directory"] = "الدليل الأولي";
$net2ftp_messages["Language"] = "اللغة";
$net2ftp_messages["Skin"] = "الشكل";
$net2ftp_messages["FTP mode"] = "نمط FTP";
$net2ftp_messages["Automatic"] = "تلقائي";
$net2ftp_messages["Login"] = "تسجيل الدخول";
$net2ftp_messages["Clear cookies"] = "مسح الكوكيز";
$net2ftp_messages["Admin"] = "الإدارة";
$net2ftp_messages["Please enter an FTP server."] = "يرجى إدخال سرفر FTP.";
$net2ftp_messages["Please enter a username."] = "يرجى إدخال اسم المستخدم .";
$net2ftp_messages["Please enter a password."] = "يرجى إدخال كلمة المرور .";

} // end login


// -------------------------------------------------------------------------
// Login module
if ($net2ftp_globals["state"] == "login_small") {
// -------------------------------------------------------------------------

$net2ftp_messages["Please enter your Administrator username and password."] = "يرجى إدخال اسم المستخدم و كلمة المرور الخاصة بالإدارة .";
$net2ftp_messages["Please enter your username and password for FTP server <b>%1\$s</b>."] = "يرجى إدخال اسم المستخدم و كلمة المرور لسرفر FTP <b>%1\$s</b> .";
$net2ftp_messages["Username"] = "اسم المستخدم";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "انتهت مدة جلسة العمل ، يرجى إعادة كتابة اسم المستخدم و كلمة المرور لسرفر FTP <b>%1\$s</b> للمتابعة .";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "تم تغيير عنوان IP الخاص بك ، يرجى إعادة كتابة كلمة المرور لسرفر FTP <b>%1\$s</b> للمتعابعة .";
$net2ftp_messages["Password"] = "كلمة المرور";
$net2ftp_messages["Login"] = "تسجيل الدخول";
$net2ftp_messages["Continue"] = "المتابعة";

} // end login_small


// -------------------------------------------------------------------------
// Logout module
if ($net2ftp_globals["state"] == "logout") {
// -------------------------------------------------------------------------

// logout.inc.php
$net2ftp_messages["Login page"] = "صفحة الدخول";

// logout.template.php
$net2ftp_messages["You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>."] = "تم تسجيل خروجك من سرفر FTP . لتسجيل الدخول من جديد , <a href=\"%1\$s\" title=\"صفحة الدخول (accesskey l)\" accesskey=\"l\">اتبع الرابط التالي</a>.";
$net2ftp_messages["Note: other users of this computer could click on the browser's Back button and access the FTP server."] = "ملاحظة » يمكن لأي مستخدم آخر لهذا الجهاز أن يضغط زر للخلف في المستعرض و الوصول إلى سرفر FTP .";
$net2ftp_messages["To prevent this, you must close all browser windows."] = "لمنع حصول ذلك , يتوجب عليك إغلاق جميع صفحات المستعرض الآن .";
$net2ftp_messages["Close"] = "إغلاق";
$net2ftp_messages["Click here to close this window"] = "اضغط هنا لإغلاق هذه النافذة";

} // end logout


// -------------------------------------------------------------------------
// New directory module
if ($net2ftp_globals["state"] == "newdir") {
// -------------------------------------------------------------------------
$net2ftp_messages["Create new directories"] = "إنشاء مجلدات جديدة";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "المجلدات الجديدة سيتم إنشائها في <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "New directory name:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "تم إنشاء المجلد <b>%1\$s</b> بنجاح !";
$net2ftp_messages["Directory <b>%1\$s</b> could not be created."] = "تعذر إنشاء المجلد <b>%1\$s</b> !";

} // end newdir


// -------------------------------------------------------------------------
// Raw module
if ($net2ftp_globals["state"] == "raw") {
// -------------------------------------------------------------------------

// /modules/raw/raw.inc.php
$net2ftp_messages["Send arbitrary FTP commands"] = "إرسال أمر FTP تحكمي";


// /skins/[skin]/raw1.template.php
$net2ftp_messages["List of commands:"] = "قائمة الأوامر »";
$net2ftp_messages["FTP server response:"] = "إجابة سرفر FTP »";

} // end raw


// -------------------------------------------------------------------------
// Rename module
if ($net2ftp_globals["state"] == "rename") {
// -------------------------------------------------------------------------
$net2ftp_messages["Rename directories and files"] = "إعادة تسمية المجلدات و الملفات";
$net2ftp_messages["Old name: "] = "الاسم القديم » ";
$net2ftp_messages["New name: "] = "الاسم الجديد » ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "الاسم الجديد يجب أن لا يتضمن نقاط . لم تتم إعادة تسمية هذا العنصر إلى <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "الاسم الجديد لا يمكن أن يتضمن كلمات مفتاحية محظورة .  لم تتتم إعادة التسمية إلى <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "تم إعادة تسمية <b>%1\$s</b> إلى <b>%2\$s</b> بنجاح !";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "تعذر إعادة تسمية <b>%1\$s</b> إلى <b>%2\$s</b> !";

} // end rename


// -------------------------------------------------------------------------
// Unzip module
if ($net2ftp_globals["state"] == "unzip") {
// -------------------------------------------------------------------------

// /modules/unzip/unzip.inc.php
$net2ftp_messages["Unzip archives"] = "استخراج الكل";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "جلب أرشيف %1\$s من %2\$s من سرفر FTP";
$net2ftp_messages["Unable to get the archive <b>%1\$s</b> from the FTP server"] = "تعذر جلب الأرشيف <b>%1\$s</b> من سرفر FTP";

// /skins/[skin]/unzip1.template.php
$net2ftp_messages["Set all targetdirectories"] = "تعيين جميع الأدلة الهدف";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "لتعيين دليل هدف مشترك , أدخل الدليل الهدف في الحقل النصي السابق ثم اضغط زر \"تعيين جميع الأدلة الهدف\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "ملاحظة » الدليل الهدف يجب أن يكون موجود أولاً .";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "فك الأرشيف <b>%1\$s</b> إلى »";
$net2ftp_messages["Target directory:"] = "المجلد الهدف »";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "استخدام نفس أسماء المجلدات (إنشاء المجلدات الفرعية تلقائياً)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "تحديث الملف";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>تحذير » هذه الوظيفة للمطورين . استخدمها فقط لاختبار الملفات ! لقد تم تحذيرك !";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "ثغرات معروفة » - مسح جدول البيانات - لا يعمل بشكل جيد مع الملفات الكبيرة (> 50 ك ب) - لم تختبر بعد على محتويات محارف الملفات الغير قياسية</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "هذه الوظيفة تمكنك من تحميل نسخة جديدة من الملف المحدد , لمشاهدة مالذي تم تعديله و قبول أو رفض كل تعديل . قبل حفظ أي شئ يمكنك تحرير الملفات المدمجة .";
$net2ftp_messages["Old file:"] = "الملف القديم »";
$net2ftp_messages["New file:"] = "الملف الجديد »";
$net2ftp_messages["Restrictions:"] = "التحديد »";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "الحجم الأقصى للملف الواحد محدد بواسطة البرنامج إلى <b>%1\$s ك ب</b> و بواسطة PHP إلى <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "مدة التنفيذ القصوى هي <b>%1\$s ثانية</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "نمط ترحيل FTP إن كان (ASCII أو BINARY) يتم تحديده تلقائياً , بالأعتماد على لاحقة اسم الملف";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "إذا كان الملف الوجهة موجود , سيتم استبداله";
$net2ftp_messages["You did not provide any files or archives to upload."] = "لم تقم بتحديد أي ملف أو أرشيف لرفعه !";
$net2ftp_messages["Unable to delete the new file"] = "تعذر حذف الملف الجديد";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "يرجى الانتظار ...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "حدد الأسطر التالي , قبول أو رفض التعديلات ثم اضغط زر الاعتماد .";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "رفع إلى الدليل »";
$net2ftp_messages["Files"] = "الملفات";
$net2ftp_messages["Archives"] = "الأراشيف";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "الملفات التي تضاف هنا سترحل إلى سرفر FTP .";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "الأراشيف التي تضاف هنا يتم فك ضغطها و ترحيل الملفات التي بداخلها إلى سرفر FTP .";
$net2ftp_messages["Add another"] = "إضافة آخر";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "استخدام نفس أسماء المجلدات (إنشاء المجلدات الفرعية تلقائياً)";

$net2ftp_messages["Choose a directory"] = "اختر دليل";
$net2ftp_messages["Please wait..."] = "يرجى الانتظار ...";
$net2ftp_messages["Uploading... please wait..."] = "جار الرفع ... يرجى الانتظار ...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "إذا استغرق الرفع وقت أطول من المسموح <b>%1\$s ثانية<\/b> , ستحاتج إلى إعادة المحاولة مع عدد ملفات أقل / أصغر .";
$net2ftp_messages["This window will close automatically in a few seconds."] = "هذه النافذة ستغلق تلقائياً خلال ثوان قليلة .";
$net2ftp_messages["Close window now"] = "إغلاق النافذة الآن";

$net2ftp_messages["Upload files and archives"] = "رفع الملفات و الأراشيف";
$net2ftp_messages["Upload results"] = "نتائج الرفع";
$net2ftp_messages["Checking files:"] = "تفحص الملفات »";
$net2ftp_messages["Transferring files to the FTP server:"] = "ترحيل الملفات إلى السرفر FTP »";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "فك الضغط و ترحيل الملفات إلى سرفر FTP »";
$net2ftp_messages["Upload more files and archives"] = "رفع المزيد من الملفات و الأراشيف";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "التحديد »";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "الحجم الأقصى للملف الواحد محدد بواسطة البرنامج إلى <b>%1\$s ك ب</b> و بواسطة PHP إلى <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "مدة التنفيذ القصوى هي <b>%1\$s ثانية</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "نمط ترحيل FTP إن كان (ASCII أو BINARY) يتم تحديده تلقائياً , بالأعتماد على لاحقة اسم الملف";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "إذا كان الملف الوجهة موجود , سيتم استبداله";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "عرض الملف %1\$s";
$net2ftp_messages["View image %1\$s"] = "عرض الصورة %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "عرض Macromedia ShockWave فلم فلاش %1\$s";
$net2ftp_messages["Image"] = "الصورة";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "لحفظ الصورة , اضغط بالزر الأيمن فوقها و اختر 'حفظ الصورة باسم...'";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "عناصر Zip";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "حفظ ملف zip على سرفر FTP كـ »";
$net2ftp_messages["Email the zip file in attachment to:"] = "إرسال ملف zip بالبريد كمرفق إلى »";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "لاحظ ان إرسال الملفات لا يتجاهل » عنوانك IP مثل إضافة وقت الإرسال إلى الرسالة .";
$net2ftp_messages["Some additional comments to add in the email:"] = "إضافة بعض التعليقات الإضافية إلى الرسالة »";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "لم تدخل اسم الملف zip . ارجع للخلف و أدخل الاسم .";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "عنوان البريد الالكتروني الذي أدخلته (%1\$s) غير مقبول .<br />يرجى إدخال عنوان البريد الالكتروني بالتنسيق <b>username@domain.com</b>";

} // end zip

?>