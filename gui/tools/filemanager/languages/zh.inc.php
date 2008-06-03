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
$net2ftp_messages["en"] = "zh";

// HTML dir attribute: left-to-right (LTR) or right-to-left (RTL)
$net2ftp_messages["ltr"] = "ltr";

// CSS style: align left or right (use in combination with LTR or RTL)
$net2ftp_messages["left"] = "left";
$net2ftp_messages["right"] = "right";

// Encoding
$net2ftp_messages["iso-8859-1"] = "UTF-8";


// -------------------------------------------------------------------------
// Status messages
// -------------------------------------------------------------------------

// When translating these messages, keep in mind that the text should not be too long
// It should fit in the status textbox

$net2ftp_messages["Connecting to the FTP server"] = "正链接到FTP服务器";
$net2ftp_messages["Logging into the FTP server"] = "Logging into the FTP server";
$net2ftp_messages["Setting the passive mode"] = "Setting the passive mode";
$net2ftp_messages["Getting the FTP system type"] = "Getting the FTP system type";
$net2ftp_messages["Changing the directory"] = "Changing the directory";
$net2ftp_messages["Getting the current directory"] = "Getting the current directory";
$net2ftp_messages["Getting the list of directories and files"] = "正获取目录和文件列表";
$net2ftp_messages["Parsing the list of directories and files"] = "Parsing the list of directories and files";
$net2ftp_messages["Logging out of the FTP server"] = "Logging out of the FTP server";
$net2ftp_messages["Getting the list of directories and files"] = "正获取目录和文件列表";
$net2ftp_messages["Printing the list of directories and files"] = "正打印目录和文件列表";
$net2ftp_messages["Processing the entries"] = "正处理输入中";
$net2ftp_messages["Processing entry %1\$s"] = "Processing entry %1\$s";
$net2ftp_messages["Checking files"] = "确认文件中";
$net2ftp_messages["Transferring files to the FTP server"] = "正传送文件到FTP服务器上";
$net2ftp_messages["Decompressing archives and transferring files"] = "解压缩文档并传送文件中";
$net2ftp_messages["Searching the files..."] = "正搜索文件...";
$net2ftp_messages["Uploading new file"] = "正上传新文件";
$net2ftp_messages["Reading the file"] = "Reading the file";
$net2ftp_messages["Parsing the file"] = "Parsing the file";
$net2ftp_messages["Reading the new file"] = "正读取新文件";
$net2ftp_messages["Reading the old file"] = "正读取旧文件";
$net2ftp_messages["Comparing the 2 files"] = "比较2个文件";
$net2ftp_messages["Printing the comparison"] = "打印比较的结果";
$net2ftp_messages["Sending FTP command %1\$s of %2\$s"] = "Sending FTP command %1\$s of %2\$s";
$net2ftp_messages["Getting archive %1\$s of %2\$s from the FTP server"] = "Getting archive %1\$s of %2\$s from the FTP server";
$net2ftp_messages["Creating a temporary directory on the FTP server"] = "Creating a temporary directory on the FTP server";
$net2ftp_messages["Setting the permissions of the temporary directory"] = "Setting the permissions of the temporary directory";
$net2ftp_messages["Copying the net2ftp installer script to the FTP server"] = "Copying the net2ftp installer script to the FTP server";
$net2ftp_messages["Script finished in %1\$s seconds"] = "代码执行耗时 %1\$s 秒";
$net2ftp_messages["Script halted"] = "代码终止";

// Used on various screens
$net2ftp_messages["Please wait..."] = "请稍候...";


// -------------------------------------------------------------------------
// index.php
// -------------------------------------------------------------------------
$net2ftp_messages["Unexpected state string: %1\$s. Exiting."] = "Unexpected state string: %1\$s. Exiting.";
$net2ftp_messages["This beta function is not activated on this server."] = "此测试功能没有被系统激活.";
$net2ftp_messages["This function has been disabled by the Administrator of this website."] = "This function has been disabled by the Administrator of this website.";


// -------------------------------------------------------------------------
// /includes/browse.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead."] = "The directory <b>%1\$s</b> does not exist or could not be selected, so the directory <b>%2\$s</b> is shown instead.";
$net2ftp_messages["Your root directory <b>%1\$s</b> does not exist or could not be selected."] = "Your root directory <b>%1\$s</b> does not exist or could not be selected.";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist."] = "The directory <b>%1\$s</b> could not be selected - you may not have sufficient rights to view this directory, or it may not exist.";
$net2ftp_messages["Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp."] = "Entries which contain banned keywords can't be managed using net2ftp. This is to avoid Paypal or Ebay scams from being uploaded through net2ftp.";
$net2ftp_messages["Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted."] = "Files which are too big can't be downloaded, uploaded, copied, moved, searched, zipped, unzipped, viewed or edited; they can only be renamed, chmodded or deleted.";
$net2ftp_messages["Execute %1\$s in a new window"] = "执行 %1\$s 于新打开的窗口";


// -------------------------------------------------------------------------
// /includes/main.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please select at least one directory or file!"] = "请选择至少的一个目录或者文件!";


// -------------------------------------------------------------------------
// /includes/authorizations.inc.php
// -------------------------------------------------------------------------

// checkAuthorization()
$net2ftp_messages["The FTP server <b>%1\$s</b> is not in the list of allowed FTP servers."] = "FTP服务器 <b>%1\$s</b> 不在被允许的FTP 服务器列表中.";
$net2ftp_messages["The FTP server <b>%1\$s</b> is in the list of banned FTP servers."] = "FTP服务器 <b>%1\$s</b> 在被禁的FTP服务器中.";
$net2ftp_messages["The FTP server port %1\$s may not be used."] = "FTP 服务端口 %1\$s 可能无法被使用.";
$net2ftp_messages["Your IP address (%1\$s) is not in the list of allowed IP addresses."] = "Your IP address (%1\$s) is not in the list of allowed IP addresses.";
$net2ftp_messages["Your IP address (%1\$s) is in the list of banned IP addresses."] = "你的IP 地址 (%1\$s) 在被禁的IP地址列表里.";

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
$net2ftp_messages["Unable to determine your IP address."] = "无法确认你的	IP地址.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate rows."] = "Table net2ftp_log_consumption_ipaddress 含有重复列.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate rows."] = "表格 net2ftp_log_consumption_ftpserver 含有重复列.";
$net2ftp_messages["The variable <b>consumption_ipaddress_datatransfer</b> is not numeric."] = "函数 <b>consumption_ipaddress_datatransfer</b> 不是数值型.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress could not be updated."] = "表格 net2ftp_log_consumption_ipaddress 无法被更新.";
$net2ftp_messages["Table net2ftp_log_consumption_ipaddress contains duplicate entries."] = "表格 net2ftp_log_consumption_ipaddress 含有重复内容.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver could not be updated."] = "表格 net2ftp_log_consumption_ftpserver 无法被更新.";
$net2ftp_messages["Table net2ftp_log_consumption_ftpserver contains duplicate entries."] = "表格 net2ftp_log_consumption_ftpserver 含有重复内容.";
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
$net2ftp_messages["An error has occured"] = "发生一个错误";
$net2ftp_messages["Go back"] = "退回";
$net2ftp_messages["Go to the login page"] = "登陆";


// -------------------------------------------------------------------------
// /includes/filesystem.inc.php
// -------------------------------------------------------------------------

// ftp_openconnection()
$net2ftp_messages["The <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">FTP module of PHP</a> is not installed.<br /><br /> The administrator of this website should install this FTP module. Installation instructions are given on <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />"] = " <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">PHP的FTP模块</a> 没有被安装.<br /><br /> 此网站的系统管理员必须安装此模块，安装指南可以查看 <a href=\"http://www.php.net/manual/en/ref.ftp.php\" target=\"_blank\">php.net</a><br />";
$net2ftp_messages["Unable to connect to FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "无法链接到 FTP 服务器 <b>%1\$s</b> 在端口 <b>%2\$s</b>.<br /><br />确定此FTP服务器地址没有错? 和 HTTP (web) 服务器不同. 请联系你的ISP 服务商或者系统管理员获取帮助.<br />";
$net2ftp_messages["Unable to login to FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "无法登入到你的FTP 服务器 <b>%1\$s</b> ，使用用户名 <b>%2\$s</b>.<br /><br />你的用户名和密码正确否? 请联系你的ISP 服务商或者系统管理员获取帮助.<br />";
$net2ftp_messages["Unable to switch to the passive mode on FTP server <b>%1\$s</b>."] = "无法在FTP服务器上转到passive 模式 <b>%1\$s</b>.";

// ftp_openconnection2()
$net2ftp_messages["Unable to connect to the second (target) FTP server <b>%1\$s</b> on port <b>%2\$s</b>.<br /><br />Are you sure this is the address of the second (target) FTP server? This is often different from that of the HTTP (web) server. Please contact your ISP helpdesk or system administrator for help.<br />"] = "无法链接到第二台 (target) FTP 服务器 <b>%1\$s</b> 在端口 <b>%2\$s</b>.<br /><br />确认此地址是否正确? 和 HTTP (web) 服务器不同. 请联系你的ISP 服务商或者系统管理员获取帮助.<br />";
$net2ftp_messages["Unable to login to the second (target) FTP server <b>%1\$s</b> with username <b>%2\$s</b>.<br /><br />Are you sure your username and password are correct? Please contact your ISP helpdesk or system administrator for help.<br />"] = "无法链接到第二台 (target) FTP 服务器 <b>%1\$s</b> ，使用用户名 <b>%2\$s</b>.<br /><br />你的用户名和密码正确否? 请联系你的ISP 服务商或者系统管理员获取帮助.<br />";
$net2ftp_messages["Unable to switch to the passive mode on the second (target) FTP server <b>%1\$s</b>."] = "无法转到passive 模式，在第二台 (target) FTP 服务器 <b>%1\$s</b>.";

// ftp_myrename()
$net2ftp_messages["Unable to rename directory or file <b>%1\$s</b> into <b>%2\$s</b>"] = "无法重命名目录和文件 <b>%1\$s</b> 为 <b>%2\$s</b>";

// ftp_mychmod()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>. Note that the CHMOD command is only available on Unix FTP servers, not on Windows FTP servers."] = "无法执行站点命令 <b>%1\$s</b>. 注意CHMOD 命令只适用于 Unix FTP 服务器, 而不是Windows FTP 服务器.";
$net2ftp_messages["Directory <b>%1\$s</b> successfully chmodded to <b>%2\$s</b>"] = "目录 <b>%1\$s</b> 被成功修改权限为 <b>%2\$s</b>";
$net2ftp_messages["Processing entries within directory <b>%1\$s</b>:"] = "Processing entries within directory <b>%1\$s</b>:";
$net2ftp_messages["File <b>%1\$s</b> was successfully chmodded to <b>%2\$s</b>"] = "文件 <b>%1\$s</b> 被成功修改权限为 <b>%2\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "所有选定的目录和文件被处理.";

// ftp_rmdir2()
$net2ftp_messages["Unable to delete the directory <b>%1\$s</b>"] = "无法删除目录 <b>%1\$s</b>";

// ftp_delete2()
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "无法删除文件 <b>%1\$s</b>";

// ftp_newdirectory()
$net2ftp_messages["Unable to create the directory <b>%1\$s</b>"] = "无法创建目录 <b>%1\$s</b>";

// ftp_readfile()
$net2ftp_messages["Unable to create the temporary file"] = "无法创建暂时文件";
$net2ftp_messages["Unable to get the file <b>%1\$s</b> from the FTP server and to save it as temporary file <b>%2\$s</b>.<br />Check the permissions of the %3\$s directory.<br />"] = "无法从FTP服务器上获取文件 <b>%1\$s</b> , 并保存为暂时文件 <b>%2\$s</b>.<br />查看目录 %3\$s 的权限设置.<br />";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "无法打开暂时文件. 查看目录 %1\$s 的权限设置.";
$net2ftp_messages["Unable to read the temporary file"] = "无法读取暂时文件";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "无法关闭对暂时文件的处理";
$net2ftp_messages["Unable to delete the temporary file"] = "无法删除暂时文件";

// ftp_writefile()
$net2ftp_messages["Unable to create the temporary file. Check the permissions of the %1\$s directory."] = "无法创建暂时文件. 查看目录 %1\$s 的权限设置.";
$net2ftp_messages["Unable to open the temporary file. Check the permissions of the %1\$s directory."] = "无法打开暂时文件. 查看目录 %1\$s 的权限设置.";
$net2ftp_messages["Unable to write the string to the temporary file <b>%1\$s</b>.<br />Check the permissions of the %2\$s directory."] = "无法写入语句到暂时文件 <b>%1\$s</b>.<br />查看目录 %2\$s 的权限设置.";
$net2ftp_messages["Unable to close the handle of the temporary file"] = "无法关闭对暂时文件的处理";
$net2ftp_messages["Unable to put the file <b>%1\$s</b> on the FTP server.<br />You may not have write permissions on the directory."] = "无法放置文件 <b>%1\$s</b> 到FTP 服务器上.<br />你也许对该目录没有写入的权限.";
$net2ftp_messages["Unable to delete the temporary file"] = "无法删除暂时文件";

// ftp_copymovedelete()
$net2ftp_messages["Processing directory <b>%1\$s</b>"] = "处理目录<b>%1\$s</b>";
$net2ftp_messages["The target directory <b>%1\$s</b> is the same as or a subdirectory of the source directory <b>%2\$s</b>, so this directory will be skipped"] = "目标目录 <b>%1\$s</b> 和源目录<b>%2\$s</b>相同或者是源目录<b>%2\$s</b>的子目录 , 所以此目录被跳过";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped"] = "The directory <b>%1\$s</b> contains a banned keyword, so this directory will be skipped";
$net2ftp_messages["The directory <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The directory <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["Unable to create the subdirectory <b>%1\$s</b>. It may already exist. Continuing the copy/move process..."] = "无法创建子目录 <b>%1\$s</b>. 可能它已经存在. 继续 复制/移动 的处理...";
$net2ftp_messages["Created target subdirectory <b>%1\$s</b>"] = "Created target subdirectory <b>%1\$s</b>";
$net2ftp_messages["The directory <b>%1\$s</b> could not be selected, so this directory will be skipped"] = "The directory <b>%1\$s</b> could not be selected, so this directory will be skipped";
$net2ftp_messages["Unable to delete the subdirectory <b>%1\$s</b> - it may not be empty"] = "无法删除子目录 <b>%1\$s</b> - 它可能不为空";
$net2ftp_messages["Deleted subdirectory <b>%1\$s</b>"] = "被删除的子目录 <b>%1\$s</b>";
$net2ftp_messages["Processing of directory <b>%1\$s</b> completed"] = "对目录 <b>%1\$s</b> 的处理已经完成";
$net2ftp_messages["The target for file <b>%1\$s</b> is the same as the source, so this file will be skipped"] = "目标文件 <b>%1\$s</b> 和源文件相同, 所以此文件被跳过";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped"] = "The file <b>%1\$s</b> contains a banned keyword, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> contains a banned keyword, aborting the move"] = "The file <b>%1\$s</b> contains a banned keyword, aborting the move";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be copied, so this file will be skipped"] = "The file <b>%1\$s</b> is too big to be copied, so this file will be skipped";
$net2ftp_messages["The file <b>%1\$s</b> is too big to be moved, aborting the move"] = "The file <b>%1\$s</b> is too big to be moved, aborting the move";
$net2ftp_messages["Unable to copy the file <b>%1\$s</b>"] = "无法复制文件 <b>%1\$s</b>";
$net2ftp_messages["Copied file <b>%1\$s</b>"] = "Copied file <b>%1\$s</b>";
$net2ftp_messages["Unable to move the file <b>%1\$s</b>, aborting the move"] = "Unable to move the file <b>%1\$s</b>, aborting the move";
$net2ftp_messages["Moved file <b>%1\$s</b>"] = "移动文件 <b>%1\$s</b>";
$net2ftp_messages["Unable to delete the file <b>%1\$s</b>"] = "无法删除文件 <b>%1\$s</b>";
$net2ftp_messages["Deleted file <b>%1\$s</b>"] = "删除文件 <b>%1\$s</b>";
$net2ftp_messages["All the selected directories and files have been processed."] = "所有选定的目录和文件被处理.";

// ftp_processfiles()

// ftp_getfile()
$net2ftp_messages["Unable to copy the remote file <b>%1\$s</b> to the local file using FTP mode <b>%2\$s</b>"] = "无法复制远端文件 <b>%1\$s</b> 到当前文件，使用FTP模式 <b>%2\$s</b>";
$net2ftp_messages["Unable to delete file <b>%1\$s</b>"] = "无法删除文件 <b>%1\$s</b>";

// ftp_putfile()
$net2ftp_messages["The file is too big to be transferred"] = "The file is too big to be transferred";
$net2ftp_messages["Daily limit reached: the file <b>%1\$s</b> will not be transferred"] = "已经达到每日限制: 文件 <b>%1\$s</b> 无法被传送";
$net2ftp_messages["Unable to copy the local file to the remote file <b>%1\$s</b> using FTP mode <b>%2\$s</b>"] = "无法复制本地文件到远端文件 <b>%1\$s</b> ，使用FTP模式 <b>%2\$s</b>";
$net2ftp_messages["Unable to delete the local file"] = "无法删除本地文件";

// ftp_downloadfile()
$net2ftp_messages["Unable to delete the temporary file"] = "无法删除暂时文件";
$net2ftp_messages["Unable to send the file to the browser"] = "无法发送文件到浏览器";

// ftp_zip()
$net2ftp_messages["Unable to create the temporary file"] = "无法创建暂时文件";
$net2ftp_messages["The zip file has been saved on the FTP server as <b>%1\$s</b>"] = "zip文件已经保存到FTP服务器，为 <b>%1\$s</b>";
$net2ftp_messages["Requested files"] = "被请求的文件";

$net2ftp_messages["Dear,"] = "Dear,";
$net2ftp_messages["Someone has requested the files in attachment to be sent to this email account (%1\$s)."] = "Someone has requested the files in attachment to be sent to this email account (%1\$s).";
$net2ftp_messages["If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment."] = "If you know nothing about this or if you don't trust that person, please delete this email without opening the Zip file in attachment.";
$net2ftp_messages["Note that if you don't open the Zip file, the files inside cannot harm your computer."] = "Note that if you don't open the Zip file, the files inside cannot harm your computer.";
$net2ftp_messages["Information about the sender: "] = "Information about the sender: ";
$net2ftp_messages["IP address: "] = "IP address: ";
$net2ftp_messages["Time of sending: "] = "Time of sending: ";
$net2ftp_messages["Sent via the net2ftp application installed on this website: "] = "Sent via the net2ftp application installed on this website: ";
$net2ftp_messages["Webmaster's email: "] = "Webmaster's email: ";
$net2ftp_messages["Message of the sender: "] = "Message of the sender: ";
$net2ftp_messages["net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com."] = "net2ftp is free software, released under the GNU/GPL license. For more information, go to http://www.net2ftp.com.";

$net2ftp_messages["The zip file has been sent to <b>%1\$s</b>."] = "zip文件已经发送到 <b>%1\$s</b>.";

// acceptFiles()
$net2ftp_messages["File <b>%1\$s</b> is too big. This file will not be uploaded."] = "文件 <b>%1\$s</b> 太大. 无法上传该文件.";
$net2ftp_messages["File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded."] = "File <b>%1\$s</b> is contains a banned keyword. This file will not be uploaded.";
$net2ftp_messages["Could not generate a temporary file."] = "无法创建暂时文件.";
$net2ftp_messages["File <b>%1\$s</b> could not be moved"] = "文件 <b>%1\$s</b> 无法移动";
$net2ftp_messages["File <b>%1\$s</b> is OK"] = "文件 <b>%1\$s</b> 是ok的";
$net2ftp_messages["Unable to move the uploaded file to the temp directory.<br /><br />The administrator of this website has to <b>chmod 777</b> the /temp directory of net2ftp."] = "无法移动要上传的文件到temp目录.<br /><br />系统管理员必须将net2ftp的 /temp目录的权限设置为<b> 777</b> .";
$net2ftp_messages["You did not provide any file to upload."] = "你没有提供要上传的文件.";

// ftp_transferfiles()
$net2ftp_messages["File <b>%1\$s</b> could not be transferred to the FTP server"] = "文件 <b>%1\$s</b> 无法传送到FTP服务器上";
$net2ftp_messages["File <b>%1\$s</b> has been transferred to the FTP server using FTP mode <b>%2\$s</b>"] = "文件<b>%1\$s</b> 传送到FTP服务器上,使用的FTP模式为 <b>%2\$s</b>";
$net2ftp_messages["Transferring files to the FTP server"] = "正传送文件到FTP服务器上";

// ftp_unziptransferfiles()
$net2ftp_messages["Processing archive nr %1\$s: <b>%2\$s</b>"] = "正在处理压缩文档 %1\$s: <b>%2\$s</b>";
$net2ftp_messages["Archive <b>%1\$s</b> was not processed because its filename extension was not recognized. Only zip, tar, tgz and gz archives are supported at the moment."] = "文档 <b>%1\$s</b> 没有被处理，因为无法识别它的扩展名. 当前只有 zip, tar, tgz and gz 文档被支持.";
$net2ftp_messages["Unable to extract the files and directories from the archive"] = "Unable to extract the files and directories from the archive";
$net2ftp_messages["Created directory %1\$s"] = "Created directory %1\$s";
$net2ftp_messages["Could not create directory %1\$s"] = "Could not create directory %1\$s";
$net2ftp_messages["Copied file %1\$s"] = "Copied file %1\$s";
$net2ftp_messages["Could not copy file %1\$s"] = "Could not copy file %1\$s";
$net2ftp_messages["Unable to delete the temporary directory"] = "Unable to delete the temporary directory";
$net2ftp_messages["Unable to delete the temporary file %1\$s"] = "Unable to delete the temporary file %1\$s";

// ftp_mysite()
$net2ftp_messages["Unable to execute site command <b>%1\$s</b>"] = "无法执行命令 <b>%1\$s</b>";

// shutdown()
$net2ftp_messages["Your task was stopped"] = "Your task was stopped";
$net2ftp_messages["The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped."] = "The task you wanted to perform with net2ftp took more time than the allowed %1\$s seconds, and therefor that task was stopped.";
$net2ftp_messages["This time limit guarantees the fair use of the web server for everyone."] = "This time limit guarantees the fair use of the web server for everyone.";
$net2ftp_messages["Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files."] = "Try to split your task in smaller tasks: restrict your selection of files, and omit the biggest files.";
$net2ftp_messages["If you really need net2ftp to be able to handle big tasks which take a long time, consider installing net2ftp on your own server."] = "如果确实需要net2ftp 来处理耗时长的任务, 请考虑安装net2ftp 到你自己的服务器上.";

// SendMail()
$net2ftp_messages["You did not provide any text to send by email!"] = "没有输入要电邮的文字!";
$net2ftp_messages["You did not supply a From address."] = "没有输入寄信人邮件地址.";
$net2ftp_messages["You did not supply a To address."] = "没有输入收信人邮件地址.";
$net2ftp_messages["Due to technical problems the email to <b>%1\$s</b> could not be sent."] = "由于技术原因，发送到地址 <b>%1\$s</b> 的邮件无法被邮寄出。";


// -------------------------------------------------------------------------
// /includes/registerglobals.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Please enter your username and password for FTP server "] = "请输入用户名和密码 ";
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "弹出窗口里的登陆信息未填全.<br />点击下面的 \"登陆\" .";
$net2ftp_messages["Access to the net2ftp Admin panel is disabled, because no password has been set in the file settings.inc.php. Enter a password in that file, and reload this page."] = "使用net2ftp 系统管理台被禁用, 因为在settings.inc.php文件中没有设置管理员密码. 请在该文件中输入密码, 然后重新刷新此页面.";
$net2ftp_messages["Please enter your Admin username and password"] = "请输入系统管理员用户名和密码"; 
$net2ftp_messages["You did not fill in your login information in the popup window.<br />Click on \"Go to the login page\" below."] = "弹出窗口里的登陆信息未填全.<br />点击下面的 \"登陆\" .";
$net2ftp_messages["Wrong username or password for the net2ftp Admin panel. The username and password can be set in the file settings.inc.php."] = "错误的系统管理员用户名和密码. 用户名和密码设置在settings.inc.php里.";


// -------------------------------------------------------------------------
// /skins/skins.inc.php
// -------------------------------------------------------------------------
$net2ftp_messages["Blue"] = "蓝色";
$net2ftp_messages["Grey"] = "灰色";
$net2ftp_messages["Black"] = "黑色";
$net2ftp_messages["Yellow"] = "黄色";
$net2ftp_messages["Pastel"] = "浅色";

// getMime()
$net2ftp_messages["Directory"] = "目录";
$net2ftp_messages["Symlink"] = "Symlink";
$net2ftp_messages["ASP script"] = "ASP script";
$net2ftp_messages["Cascading Style Sheet"] = "Cascading Style Sheet";
$net2ftp_messages["HTML file"] = "HTML file";
$net2ftp_messages["Java source file"] = "Java source file";
$net2ftp_messages["JavaScript file"] = "JavaScript file";
$net2ftp_messages["PHP Source"] = "PHP Source";
$net2ftp_messages["PHP script"] = "PHP script";
$net2ftp_messages["Text file"] = "Text file";
$net2ftp_messages["Bitmap file"] = "Bitmap file";
$net2ftp_messages["GIF file"] = "GIF file";
$net2ftp_messages["JPEG file"] = "JPEG file";
$net2ftp_messages["PNG file"] = "PNG file";
$net2ftp_messages["TIF file"] = "TIF file";
$net2ftp_messages["GIMP file"] = "GIMP file";
$net2ftp_messages["Executable"] = "Executable";
$net2ftp_messages["Shell script"] = "Shell script";
$net2ftp_messages["MS Office - Word document"] = "MS Office - Word document";
$net2ftp_messages["MS Office - Excel spreadsheet"] = "MS Office - Excel spreadsheet";
$net2ftp_messages["MS Office - PowerPoint presentation"] = "MS Office - PowerPoint presentation";
$net2ftp_messages["MS Office - Access database"] = "MS Office - Access database";
$net2ftp_messages["MS Office - Visio drawing"] = "MS Office - Visio drawing";
$net2ftp_messages["MS Office - Project file"] = "MS Office - Project file";
$net2ftp_messages["OpenOffice - Writer 6.0 document"] = "OpenOffice - Writer 6.0 document";
$net2ftp_messages["OpenOffice - Writer 6.0 template"] = "OpenOffice - Writer 6.0 template";
$net2ftp_messages["OpenOffice - Calc 6.0 spreadsheet"] = "OpenOffice - Calc 6.0 spreadsheet";
$net2ftp_messages["OpenOffice - Calc 6.0 template"] = "OpenOffice - Calc 6.0 template";
$net2ftp_messages["OpenOffice - Draw 6.0 document"] = "OpenOffice - Draw 6.0 document";
$net2ftp_messages["OpenOffice - Draw 6.0 template"] = "OpenOffice - Draw 6.0 template";
$net2ftp_messages["OpenOffice - Impress 6.0 presentation"] = "OpenOffice - Impress 6.0 presentation";
$net2ftp_messages["OpenOffice - Impress 6.0 template"] = "OpenOffice - Impress 6.0 template";
$net2ftp_messages["OpenOffice - Writer 6.0 global document"] = "OpenOffice - Writer 6.0 global document";
$net2ftp_messages["OpenOffice - Math 6.0 document"] = "OpenOffice - Math 6.0 document";
$net2ftp_messages["StarOffice - StarWriter 5.x document"] = "StarOffice - StarWriter 5.x document";
$net2ftp_messages["StarOffice - StarWriter 5.x global document"] = "StarOffice - StarWriter 5.x global document";
$net2ftp_messages["StarOffice - StarCalc 5.x spreadsheet"] = "StarOffice - StarCalc 5.x spreadsheet";
$net2ftp_messages["StarOffice - StarDraw 5.x document"] = "StarOffice - StarDraw 5.x document";
$net2ftp_messages["StarOffice - StarImpress 5.x presentation"] = "StarOffice - StarImpress 5.x presentation";
$net2ftp_messages["StarOffice - StarImpress Packed 5.x file"] = "StarOffice - StarImpress Packed 5.x file";
$net2ftp_messages["StarOffice - StarMath 5.x document"] = "StarOffice - StarMath 5.x document";
$net2ftp_messages["StarOffice - StarChart 5.x document"] = "StarOffice - StarChart 5.x document";
$net2ftp_messages["StarOffice - StarMail 5.x mail file"] = "StarOffice - StarMail 5.x mail file";
$net2ftp_messages["Adobe Acrobat document"] = "Adobe Acrobat document";
$net2ftp_messages["ARC archive"] = "ARC archive";
$net2ftp_messages["ARJ archive"] = "ARJ archive";
$net2ftp_messages["RPM"] = "RPM";
$net2ftp_messages["GZ archive"] = "GZ archive";
$net2ftp_messages["TAR archive"] = "TAR archive";
$net2ftp_messages["Zip archive"] = "Zip archive";
$net2ftp_messages["MOV movie file"] = "MOV movie file";
$net2ftp_messages["MPEG movie file"] = "MPEG movie file";
$net2ftp_messages["Real movie file"] = "Real movie file";
$net2ftp_messages["Quicktime movie file"] = "Quicktime movie file";
$net2ftp_messages["Shockwave flash file"] = "Shockwave flash file";
$net2ftp_messages["Shockwave file"] = "Shockwave file";
$net2ftp_messages["WAV sound file"] = "WAV sound file";
$net2ftp_messages["Font file"] = "Font file";
$net2ftp_messages["%1\$s File"] = "%1\$s File";
$net2ftp_messages["File"] = "文件";

// getAction()
$net2ftp_messages["Back"] = "退回";
$net2ftp_messages["Submit"] = "提交";
$net2ftp_messages["Refresh"] = "刷新";
$net2ftp_messages["Details"] = "详细资料";
$net2ftp_messages["Icons"] = "图标";
$net2ftp_messages["List"] = "浏览";
$net2ftp_messages["Logout"] = "登出";
$net2ftp_messages["Help"] = "帮助";
$net2ftp_messages["Bookmark"] = "书签";
$net2ftp_messages["Save"] = "保存";
$net2ftp_messages["Default"] = "缺省";


// -------------------------------------------------------------------------
// /skins/[skin]/footer.template.php and statusbar.template.php
// -------------------------------------------------------------------------
$net2ftp_messages["Help Guide"] = "Help Guide";
$net2ftp_messages["Forums"] = "Forums";
$net2ftp_messages["License"] = "协议";
$net2ftp_messages["Powered by"] = "使用";
$net2ftp_messages["You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."] = "You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions.";


// -------------------------------------------------------------------------
// Admin module
if ($net2ftp_globals["state"] == "admin") {
// -------------------------------------------------------------------------

// /modules/admin/admin.inc.php
$net2ftp_messages["Admin functions"] = "系统管理功能";

// /skins/[skin]/admin1.template.php
$net2ftp_messages["Version information"] = "版本信息";
$net2ftp_messages["This version of net2ftp is up-to-date."] = "This version of net2ftp is up-to-date.";
$net2ftp_messages["The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."] = "The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server.";
$net2ftp_messages["Logging"] = "登入中";
$net2ftp_messages["Date from:"] = "日期从:";
$net2ftp_messages["to:"] = "到:";
$net2ftp_messages["Empty logs"] = "为空";
$net2ftp_messages["View logs"] = "查看登陆信息";
$net2ftp_messages["Go"] = "前往";
$net2ftp_messages["Setup MySQL tables"] = "安装 MySQL 数据表";
$net2ftp_messages["Create the MySQL database tables"] = "创建MySQL 数据表";

} // end admin

// -------------------------------------------------------------------------
// Admin_createtables module
if ($net2ftp_globals["state"] == "admin_createtables") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_createtables.inc.php
$net2ftp_messages["Admin functions"] = "系统管理功能";
$net2ftp_messages["The handle of file %1\$s could not be opened."] = "The handle of file %1\$s could not be opened.";
$net2ftp_messages["The file %1\$s could not be opened."] = "The file %1\$s could not be opened.";
$net2ftp_messages["The handle of file %1\$s could not be closed."] = "The handle of file %1\$s could not be closed.";
$net2ftp_messages["The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered."] = "The connection to the server <b>%1\$s</b> could not be set up. Please check the database settings you've entered.";
$net2ftp_messages["Unable to select the database <b>%1\$s</b>."] = "Unable to select the database <b>%1\$s</b>.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> could not be executed."] = "The SQL query nr <b>%1\$s</b> could not be executed.";
$net2ftp_messages["The SQL query nr <b>%1\$s</b> was executed successfully."] = "The SQL query nr <b>%1\$s</b> was executed successfully.";

// /skins/[skin]/admin_createtables1.template.php
$net2ftp_messages["Please enter your MySQL settings:"] = "Please enter your MySQL settings:";
$net2ftp_messages["MySQL username"] = "MySQL 用户名";
$net2ftp_messages["MySQL password"] = "MySQL 用户密码";
$net2ftp_messages["MySQL database"] = "MySQL 数据库名";
$net2ftp_messages["MySQL server"] = "MySQL 服务器名";
$net2ftp_messages["This SQL query is going to be executed:"] = "以下SQL语句将要被执行:";
$net2ftp_messages["Execute"] = "执行";

// /skins/[skin]/admin_createtables2.template.php
$net2ftp_messages["Settings used:"] = "使用以下设置:";
$net2ftp_messages["MySQL password length"] = "MySQL 密码长度";
$net2ftp_messages["Results:"] = "结果:";

} // end admin_createtables


// -------------------------------------------------------------------------
// Admin_viewlogs module
if ($net2ftp_globals["state"] == "admin_viewlogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_viewlogs.inc.php
$net2ftp_messages["Admin functions"] = "系统管理功能";
$net2ftp_messages["Unable to execute the SQL query <b>%1\$s</b>."] = "Unable to execute the SQL query <b>%1\$s</b>.";
$net2ftp_messages["No data"] = "无数据";

} // end admin_viewlogs


// -------------------------------------------------------------------------
// Admin_emptylogs module
if ($net2ftp_globals["state"] == "admin_emptylogs") {
// -------------------------------------------------------------------------

// /modules/admin_createtables/admin_emptylogs.inc.php
$net2ftp_messages["Admin functions"] = "系统管理功能";
$net2ftp_messages["The table <b>%1\$s</b> was emptied successfully."] = "数据表 <b>%1\$s</b> 被成功清空.";
$net2ftp_messages["The table <b>%1\$s</b> could not be emptied."] = "数据表 <b>%1\$s</b> 无法被清空.";
$net2ftp_messages["The table <b>%1\$s</b> was optimized successfully."] = "数据表 <b>%1\$s</b> 优化成功.";
$net2ftp_messages["The table <b>%1\$s</b> could not be optimized."] = "数据表 <b>%1\$s</b> 无法被优化.";

} // end admin_emptylogs


// -------------------------------------------------------------------------
// Advanced module
if ($net2ftp_globals["state"] == "advanced") {
// -------------------------------------------------------------------------

// /modules/advanced/advanced.inc.php
$net2ftp_messages["Advanced functions"] = "高级功能";

// /skins/[skin]/advanced1.template.php
$net2ftp_messages["Go"] = "前往";
$net2ftp_messages["Disabled"] = "Disabled";
$net2ftp_messages["Advanced FTP functions"] = "Advanced FTP functions";
$net2ftp_messages["Send arbitrary FTP commands to the FTP server"] = "Send arbitrary FTP commands to the FTP server";
$net2ftp_messages["This function is available on PHP 5 only"] = "This function is available on PHP 5 only";
$net2ftp_messages["Troubleshooting functions"] = "错误诊断功能";
$net2ftp_messages["Troubleshoot net2ftp on this webserver"] = "此web服务器上的net2ftp 错误诊断";
$net2ftp_messages["Troubleshoot an FTP server"] = "FTP服务器上的错误诊断";
$net2ftp_messages["Test the net2ftp list parsing rules"] = "Test the net2ftp list parsing rules";
$net2ftp_messages["Translation functions"] = "错误诊断功能";
$net2ftp_messages["Introduction to the translation functions"] = "翻译功能的介绍";
$net2ftp_messages["Extract messages to translate from code files"] = "从代码文件中抽取信息翻译";
$net2ftp_messages["Check if there are new or obsolete messages"] = "查看是否有新语句";

$net2ftp_messages["Beta functions"] = "Beta 功能";
$net2ftp_messages["Send a site command to the FTP server"] = "发送站点命令到FTP服务器";
$net2ftp_messages["Apache: password-protect a directory, create custom error pages"] = "Apache: 密码保护目录, 创建自定义的错误页面";
$net2ftp_messages["MySQL: execute an SQL query"] = "MySQL: 执行SQL语句";


// advanced()
$net2ftp_messages["The site command functions are not available on this webserver."] = "站点命令功能无法在此web服务器上使用.";
$net2ftp_messages["The Apache functions are not available on this webserver."] = "Apache功能无法在此服务器上使用.";
$net2ftp_messages["The MySQL functions are not available on this webserver."] = "MySQL功能无法在此服务器上使用.";
$net2ftp_messages["Unexpected state2 string. Exiting."] = "非预知的state2 语句. 结束.";

} // end advanced


// -------------------------------------------------------------------------
// Advanced_ftpserver module
if ($net2ftp_globals["state"] == "advanced_ftpserver") {
// -------------------------------------------------------------------------

// /modules/advanced_ftpserver/advanced_ftpserver.inc.php
$net2ftp_messages["Troubleshoot an FTP server"] = "FTP服务器上的错误诊断";

// /skins/[skin]/advanced_ftpserver1.template.php
$net2ftp_messages["Connection settings:"] = "连接语句:";
$net2ftp_messages["FTP server"] = "FTP 服务器";
$net2ftp_messages["FTP server port"] = "FTP 服务器端口";
$net2ftp_messages["Username"] = "用户名";
$net2ftp_messages["Password"] = "密码";
$net2ftp_messages["Password length"] = "密码长度";
$net2ftp_messages["Passive mode"] = "Passive 模式";
$net2ftp_messages["Directory"] = "目录";
$net2ftp_messages["Printing the result"] = "Printing the result";

// /skins/[skin]/advanced_ftpserver2.template.php
$net2ftp_messages["Connecting to the FTP server: "] = "正链接到FTP服务器: ";
$net2ftp_messages["Logging into the FTP server: "] = "正登入到FTP服务器: ";
$net2ftp_messages["Setting the passive mode: "] = "设置passive 模式: ";
$net2ftp_messages["Getting the FTP server system type: "] = "正获取FTP 服务器系统类型: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "正改变目录到 %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "FTP服务器上的目录为: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "正获取原始目录和文件: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "再一次尝试获取原始目录和文件: ";
$net2ftp_messages["Closing the connection: "] = "正关闭链接: ";
$net2ftp_messages["Raw list of directories and files:"] = "原始目录和文件列表:";
$net2ftp_messages["Parsed list of directories and files:"] = "传过来的目录和文件列表:";

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

$net2ftp_messages["Troubleshoot your net2ftp installation"] = "诊断你的net2ftp 安装";
$net2ftp_messages["Printing the result"] = "Printing the result";

$net2ftp_messages["Checking if the FTP module of PHP is installed: "] = "查看PHP的FTP模块是否已经安装: ";
$net2ftp_messages["yes"] = "是";
$net2ftp_messages["no - please install it!"] = "否 - 请安装!";

$net2ftp_messages["Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."] = "查看web服务器上的目录权限: 一个文件将会写入到 /temp 目录然后会被删除.";
$net2ftp_messages["Creating filename: "] = "创建文件名: ";
$net2ftp_messages["OK. Filename: %1\$s"] = "OK. 文件名: %1\$s";
$net2ftp_messages["not OK"] = "not OK";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK. Check the permissions of the %1\$s directory"] = "不OK. 查看 %1\$s 目录的权限";
$net2ftp_messages["Opening the file in write mode: "] = "在写入模式下打开文件: ";
$net2ftp_messages["Writing some text to the file: "] = "给此文件写入些文字: ";
$net2ftp_messages["Closing the file: "] = "关闭文件: ";
$net2ftp_messages["Deleting the file: "] = "删除文件: ";

$net2ftp_messages["Testing the FTP functions"] = "Testing the FTP functions";
$net2ftp_messages["Connecting to a test FTP server: "] = "Connecting to a test FTP server: ";
$net2ftp_messages["Connecting to the FTP server: "] = "正链接到FTP服务器: ";
$net2ftp_messages["Logging into the FTP server: "] = "正登入到FTP服务器: ";
$net2ftp_messages["Setting the passive mode: "] = "设置passive 模式: ";
$net2ftp_messages["Getting the FTP server system type: "] = "正获取FTP 服务器系统类型: ";
$net2ftp_messages["Changing to the directory %1\$s: "] = "正改变目录到 %1\$s: ";
$net2ftp_messages["The directory from the FTP server is: %1\$s "] = "FTP服务器上的目录为: %1\$s ";
$net2ftp_messages["Getting the raw list of directories and files: "] = "正获取原始目录和文件: ";
$net2ftp_messages["Trying a second time to get the raw list of directories and files: "] = "再一次尝试获取原始目录和文件: ";
$net2ftp_messages["Closing the connection: "] = "正关闭链接: ";
$net2ftp_messages["Raw list of directories and files:"] = "原始目录和文件列表:";
$net2ftp_messages["Parsed list of directories and files:"] = "传过来的目录和文件列表:";
$net2ftp_messages["OK"] = "OK";
$net2ftp_messages["not OK"] = "not OK";

} // end advanced_webserver


// -------------------------------------------------------------------------
// Bookmark module
if ($net2ftp_globals["state"] == "bookmark") {
// -------------------------------------------------------------------------
$net2ftp_messages["Add this link to your bookmarks:"] = "添加此链接到你的书签:";
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: 右键点击链接，然后选择 \"Add to Favorites...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: 右键点击链接，然后选择 \"Bookmark This Link...\"";
$net2ftp_messages["Note: when you will use this bookmark, a popup window will ask you for your username and password."] = "注意: 当你使用此书签, 会弹出一个窗口，要求你的用户名和密码.";

} // end bookmark


// -------------------------------------------------------------------------
// Browse module
if ($net2ftp_globals["state"] == "browse") {
// -------------------------------------------------------------------------

// /modules/browse/browse.inc.php
$net2ftp_messages["Choose a directory"] = "选择一个目录";
$net2ftp_messages["Please wait..."] = "请稍候...";

// browse()
$net2ftp_messages["Directories with names containing \' cannot be displayed correctly. They can only be deleted. Please go back and select another subdirectory."] = "目录名含有字符 \' 无法被正常显示. 只能删除它们. 请退回然后选择其它的子目录.";

$net2ftp_messages["Daily limit reached: you will not be able to transfer data"] = "Daily limit reached: you will not be able to transfer data";
$net2ftp_messages["In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it."] = "In order to guarantee the fair use of the web server for everyone, the data transfer volume and script execution time are limited per user, and per day. Once this limit is reached, you can still browse the FTP server but not transfer data to/from it.";
$net2ftp_messages["If you need unlimited usage, please install net2ftp on your own web server."] = "If you need unlimited usage, please install net2ftp on your own web server.";

// printdirfilelist()
// Keep this short, it must fit in a small button!
$net2ftp_messages["New dir"] = "新目录";
$net2ftp_messages["New file"] = "新文件";
$net2ftp_messages["HTML templates"] = "HTML templates";
$net2ftp_messages["Upload"] = "上�";
$net2ftp_messages["Java Upload"] = "Java 上�";
$net2ftp_messages["Flash Upload"] = "Flash Upload";
$net2ftp_messages["Install"] = "Install";
$net2ftp_messages["Advanced"] = "高级";
$net2ftp_messages["Copy"] = "复制";
$net2ftp_messages["Move"] = "移到";
$net2ftp_messages["Delete"] = "删除";
$net2ftp_messages["Rename"] = "重命名";
$net2ftp_messages["Chmod"] = "修改权限";
$net2ftp_messages["Download"] = "下载";
$net2ftp_messages["Unzip"] = "Unzip";
$net2ftp_messages["Zip"] = "Zip";
$net2ftp_messages["Size"] = "大小";
$net2ftp_messages["Search"] = "搜索";
$net2ftp_messages["Go to the parent directory"] = "上级目录";
$net2ftp_messages["Go"] = "前往";
$net2ftp_messages["Transform selected entries: "] = "传送所选的: ";
$net2ftp_messages["Transform selected entry: "] = "Transform selected entry: ";
$net2ftp_messages["Make a new subdirectory in directory %1\$s"] = "在目录 %1\$s 里创建子目录";
$net2ftp_messages["Create a new file in directory %1\$s"] = "在目录 %1\$s 里创建新文件";
$net2ftp_messages["Create a website easily using ready-made templates"] = "Create a website easily using ready-made templates";
$net2ftp_messages["Upload new files in directory %1\$s"] = "上传新文件到目录 %1\$s";
$net2ftp_messages["Upload directories and files using a Java applet"] = "Upload directories and files using a Java applet";
$net2ftp_messages["Upload files using a Flash applet"] = "Upload files using a Flash applet";
$net2ftp_messages["Install software packages (requires PHP on web server)"] = "Install software packages (requires PHP on web server)";
$net2ftp_messages["Go to the advanced functions"] = "前往高级功能";
$net2ftp_messages["Copy the selected entries"] = "复制所选";
$net2ftp_messages["Move the selected entries"] = "移动所选";
$net2ftp_messages["Delete the selected entries"] = "删除所选";
$net2ftp_messages["Rename the selected entries"] = "重命名所选";
$net2ftp_messages["Chmod the selected entries (only works on Unix/Linux/BSD servers)"] = "修改所选的权限 (只可用于 Unix/Linux/BSD 服务器)";
$net2ftp_messages["Download a zip file containing all selected entries"] = "下载一个zip 文件含有所有选定的内容";
$net2ftp_messages["Unzip the selected archives on the FTP server"] = "Unzip the selected archives on the FTP server";
$net2ftp_messages["Zip the selected entries to save or email them"] = "Zip 压缩所选的内容来保存或者电邮";
$net2ftp_messages["Calculate the size of the selected entries"] = "计算所选的内容的大小";
$net2ftp_messages["Find files which contain a particular word"] = "搜索含有特定单词的文件";
$net2ftp_messages["Click to sort by %1\$s in descending order"] = "点击按 %1\$s 分类并降序排列";
$net2ftp_messages["Click to sort by %1\$s in ascending order"] = "点击按 %1\$s 分类并升序排列";
$net2ftp_messages["Ascending order"] = "升序排列";
$net2ftp_messages["Descending order"] = "降序排列";
$net2ftp_messages["Upload files"] = "Upload files";
$net2ftp_messages["Up"] = "向上";
$net2ftp_messages["Click to check or uncheck all rows"] = "点击对所有列复选或者取消复选";
$net2ftp_messages["All"] = "全部";
$net2ftp_messages["Name"] = "名称";
$net2ftp_messages["Type"] = "类型";
//$net2ftp_messages["Size"] = "Size";
$net2ftp_messages["Owner"] = "拥有者";
$net2ftp_messages["Group"] = "组";
$net2ftp_messages["Perms"] = "权限";
$net2ftp_messages["Mod Time"] = "修改时间";
$net2ftp_messages["Actions"] = "操作";
$net2ftp_messages["Select the directory %1\$s"] = "Select the directory %1\$s";
$net2ftp_messages["Select the file %1\$s"] = "Select the file %1\$s";
$net2ftp_messages["Select the symlink %1\$s"] = "Select the symlink %1\$s";
$net2ftp_messages["Go to the subdirectory %1\$s"] = "Go to the subdirectory %1\$s";
$net2ftp_messages["Download the file %1\$s"] = "下载文件 %1\$s";
$net2ftp_messages["Follow symlink %1\$s"] = "Follow symlink %1\$s";
$net2ftp_messages["View"] = "查看";
$net2ftp_messages["Edit"] = "编辑";
$net2ftp_messages["Update"] = "更新";
$net2ftp_messages["Open"] = "打开";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "查看有语法高亮显示的文件 %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "编辑文件 %1\$s 的源代码";
$net2ftp_messages["Upload a new version of the file %1\$s and merge the changes"] = "上传文件 %1\$s 的新版本";
$net2ftp_messages["View image %1\$s"] = "查看图像 %1\$s";
$net2ftp_messages["View the file %1\$s from your HTTP web server"] = "从你的WEB服务器上查看文件 %1\$s ";
$net2ftp_messages["(Note: This link may not work if you don't have your own domain name.)"] = "(注意: 如果你没有域名，此链接可能无法使用.)";
$net2ftp_messages["This folder is empty"] = "此目录为空";

// printSeparatorRow()
$net2ftp_messages["Directories"] = "目录";
$net2ftp_messages["Files"] = "文件";
$net2ftp_messages["Symlinks"] = "链接";
$net2ftp_messages["Unrecognized FTP output"] = "无法识别FTP输出";
$net2ftp_messages["Number"] = "Number";
$net2ftp_messages["Size"] = "大小";
$net2ftp_messages["Skipped"] = "Skipped";
$net2ftp_messages["Data transferred from this IP address today"] = "Data transferred from this IP address today";
$net2ftp_messages["Data transferred to this FTP server today"] = "Data transferred to this FTP server today";

// printLocationActions()
$net2ftp_messages["Language:"] = "语言:";
$net2ftp_messages["Skin:"] = "皮肤:";
$net2ftp_messages["View mode:"] = "浏览模式:";
$net2ftp_messages["Directory Tree"] = "目录树";

// ftp2http()
$net2ftp_messages["Execute %1\$s in a new window"] = "执行 %1\$s 于新打开的窗口";
$net2ftp_messages["This file is not accessible from the web"] = "This file is not accessible from the web";

// printDirectorySelect()
$net2ftp_messages["Double-click to go to a subdirectory:"] = "双击打开下级目录:";
$net2ftp_messages["Choose"] = "选择";
$net2ftp_messages["Up"] = "向上";

} // end browse


// -------------------------------------------------------------------------
// Calculate size module
if ($net2ftp_globals["state"] == "calculatesize") {
// -------------------------------------------------------------------------
$net2ftp_messages["Size of selected directories and files"] = "所选的目录和文件容量大小";
$net2ftp_messages["The total size taken by the selected directories and files is:"] = "所选的目录和文件所占的容量大小:";
$net2ftp_messages["The number of files which were skipped is:"] = "The number of files which were skipped is:";

} // end calculatesize


// -------------------------------------------------------------------------
// Chmod module
if ($net2ftp_globals["state"] == "chmod") {
// -------------------------------------------------------------------------
$net2ftp_messages["Chmod directories and files"] = "修改目录和文件权限";
$net2ftp_messages["Set all permissions"] = "设置所有权限";
$net2ftp_messages["Read"] = "读取";
$net2ftp_messages["Write"] = "写入";
$net2ftp_messages["Execute"] = "执行";
$net2ftp_messages["Owner"] = "拥有者";
$net2ftp_messages["Group"] = "组";
$net2ftp_messages["Everyone"] = "任何人";
$net2ftp_messages["To set all permissions to the same values, enter those permissions above and click on the button \"Set all permissions\""] = "设置所有的权限为相同值, 在上面中输入需要的权限，然后点击按钮 \"设置所有权限\"";
$net2ftp_messages["Set the permissions of directory <b>%1\$s</b> to: "] = "设置目录 <b>%1\$s</b> 的权限为: ";
$net2ftp_messages["Set the permissions of file <b>%1\$s</b> to: "] = "设置文件 <b>%1\$s</b> 的权限为: ";
$net2ftp_messages["Set the permissions of symlink <b>%1\$s</b> to: "] = "设置链接 <b>%1\$s</b> 的权限为: ";
$net2ftp_messages["Chmod value"] = "权限值";
$net2ftp_messages["Chmod also the subdirectories within this directory"] = "也设置此目录里的子目录的权限";
$net2ftp_messages["Chmod also the files within this directory"] = "也设置此目录里的文件的权限";
$net2ftp_messages["The chmod nr <b>%1\$s</b> is out of the range 000-777. Please try again."] = "输入的权限值 <b>%1\$s</b> 超出可用的范围 000-777. 请重试.";

} // end chmod


// -------------------------------------------------------------------------
// Clear cookies module
// -------------------------------------------------------------------------
// No messages


// -------------------------------------------------------------------------
// Copy/Move/Delete module
if ($net2ftp_globals["state"] == "copymovedelete") {
// -------------------------------------------------------------------------
$net2ftp_messages["Choose a directory"] = "选择一个目录";
$net2ftp_messages["Copy directories and files"] = "复制目录和文件";
$net2ftp_messages["Move directories and files"] = "移动目录和文件";
$net2ftp_messages["Delete directories and files"] = "删除目录和文件";
$net2ftp_messages["Are you sure you want to delete these directories and files?"] = "确定要删除这些目录和文件?";
$net2ftp_messages["All the subdirectories and files of the selected directories will also be deleted!"] = "所有所选的目录里的子目录和文件的内容也都将被删除!";
$net2ftp_messages["Set all targetdirectories"] = "设置所有的目标目录";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "设置一个一般的目录, 在上面的输入框中输入目标目录的名称，然后点击按钮 \"设置所有的目标目录\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "注意: 在复制任何内容之前，必须保证目标目录已经存在.";
$net2ftp_messages["Different target FTP server:"] = "不同的目标FTP服务器:";
$net2ftp_messages["Username"] = "用户名";
$net2ftp_messages["Password"] = "密码";
$net2ftp_messages["Leave empty if you want to copy the files to the same FTP server."] = "如果你要复制到相同的FTP服务器上，请留空.";
$net2ftp_messages["If you want to copy the files to another FTP server, enter your login data."] = "如果你要复制这些文件到其他其他FTP服务器, 请输入你的登入信息.";
$net2ftp_messages["Leave empty if you want to move the files to the same FTP server."] = "如果你要移动到相同的FTP服务器上，请留空.";
$net2ftp_messages["If you want to move the files to another FTP server, enter your login data."] = "如果你要移动这些文件到其他FTP服务器,请输入你的登入信息.";
$net2ftp_messages["Copy directory <b>%1\$s</b> to:"] = "复制目录 <b>%1\$s</b> 到:";
$net2ftp_messages["Move directory <b>%1\$s</b> to:"] = "移动目录 <b>%1\$s</b> 到:";
$net2ftp_messages["Directory <b>%1\$s</b>"] = "目录 <b>%1\$s</b>";
$net2ftp_messages["Copy file <b>%1\$s</b> to:"] = "复制文件 <b>%1\$s</b> 到:";
$net2ftp_messages["Move file <b>%1\$s</b> to:"] = "移动文件 <b>%1\$s</b> 到:";
$net2ftp_messages["File <b>%1\$s</b>"] = "文件 <b>%1\$s</b>";
$net2ftp_messages["Copy symlink <b>%1\$s</b> to:"] = "复制链接 <b>%1\$s</b> 到:";
$net2ftp_messages["Move symlink <b>%1\$s</b> to:"] = "移动链接 <b>%1\$s</b> 到:";
$net2ftp_messages["Symlink <b>%1\$s</b>"] = "链接 <b>%1\$s</b>";
$net2ftp_messages["Target directory:"] = "目标目录:";
$net2ftp_messages["Target name:"] = "目标名称:";
$net2ftp_messages["Processing the entries:"] = "处理以下输入:";

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
$net2ftp_messages["Size"] = "大小";
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
$net2ftp_messages["Internet Explorer: right-click on the link and choose \"Add to Favorites...\""] = "Internet Explorer: 右键点击链接，然后选择 \"Add to Favorites...\"";
$net2ftp_messages["Netscape, Mozilla, Firefox: right-click on the link and choose \"Bookmark This Link...\""] = "Netscape, Mozilla, Firefox: 右键点击链接，然后选择 \"Bookmark This Link...\"";

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
$net2ftp_messages["Unable to open the template file"] = "无法打开暂存文件";
$net2ftp_messages["Unable to read the template file"] = "无法读取暂存文件";
$net2ftp_messages["Please specify a filename"] = "请确认一个文件名";
$net2ftp_messages["Status: This file has not yet been saved"] = "状态: 此文件未保存";
$net2ftp_messages["Status: Saved on <b>%1\$s</b> using mode %2\$s"] = "状态: 保存在 <b>%1\$s</b> 使用模式 %2\$s";
$net2ftp_messages["Status: <b>This file could not be saved</b>"] = "状态: <b>此文件无法被保存</b>";

// /skins/[skin]/edit.template.php
$net2ftp_messages["Directory: "] = "目录: ";
$net2ftp_messages["File: "] = "文件: ";
$net2ftp_messages["New file name: "] = "新文件名: ";
$net2ftp_messages["Character encoding: "] = "Character encoding: ";
$net2ftp_messages["Note: changing the textarea type will save the changes"] = "注意: 改变文本输入类型将会保存改动";
$net2ftp_messages["Copy up"] = "Copy up";
$net2ftp_messages["Copy down"] = "Copy down";

} // end if edit


// -------------------------------------------------------------------------
// Find string module
if ($net2ftp_globals["state"] == "findstring") {
// -------------------------------------------------------------------------

// /modules/findstring/findstring.inc.php 
$net2ftp_messages["Search directories and files"] = "搜索目录和文件";
$net2ftp_messages["Search again"] = "重新搜索";
$net2ftp_messages["Search results"] = "搜索结果为";
$net2ftp_messages["Please enter a valid search word or phrase."] = "请输入正确的词汇或者词组.";
$net2ftp_messages["Please enter a valid filename."] = "请输入正确的文件名称.";
$net2ftp_messages["Please enter a valid file size in the \"from\" textbox, for example 0."] = "请输入正确的文件大小在 \"从\" 输入框, 如0.";
$net2ftp_messages["Please enter a valid file size in the \"to\" textbox, for example 500000."] = "请输入正确的文件大小在 \"到\" 输入框, 如500000.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"from\" textbox."] = "请输入正确的日期 Y-m-d 格式在 \"从\" 输入框.";
$net2ftp_messages["Please enter a valid date in Y-m-d format in the \"to\" textbox."] = "请输入正确的日期 Y-m-d 格式在 \"到\" 输入框.";
$net2ftp_messages["The word <b>%1\$s</b> was not found in the selected directories and files."] = "词汇<b>%1\$s</b> 没有在所选的目录和文件中找到.";
$net2ftp_messages["The word <b>%1\$s</b> was found in the following files:"] = "词汇 <b>%1\$s</b> 出现在以下文件中:";

// /skins/[skin]/findstring1.template.php
$net2ftp_messages["Search for a word or phrase"] = "查找词汇或者词组";
$net2ftp_messages["Case sensitive search"] = "大小写字母有区别";
$net2ftp_messages["Restrict the search to:"] = "限制查询范围为:";
$net2ftp_messages["files with a filename like"] = "文件名称类似";
$net2ftp_messages["(wildcard character is *)"] = "(取代 字符是 *)";
$net2ftp_messages["files with a size"] = "文件大小";
$net2ftp_messages["files which were last modified"] = "文件被最新修改";
$net2ftp_messages["from"] = "从";
$net2ftp_messages["to"] = "到";

$net2ftp_messages["Directory"] = "目录";
$net2ftp_messages["File"] = "文件";
$net2ftp_messages["Line"] = "Line";
$net2ftp_messages["Action"] = "Action";
$net2ftp_messages["View"] = "查看";
$net2ftp_messages["Edit"] = "编辑";
$net2ftp_messages["View the highlighted source code of file %1\$s"] = "查看有语法高亮显示的文件 %1\$s";
$net2ftp_messages["Edit the source code of file %1\$s"] = "编辑文件 %1\$s 的源代码";

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
$net2ftp_messages["Unable to open the template file"] = "无法打开暂存文件";
$net2ftp_messages["Unable to read the template file"] = "无法读取暂存文件";
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
$net2ftp_messages["Upload"] = "上�";
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

$net2ftp_messages["FTP server"] = "FTP 服务器";
$net2ftp_messages["Example"] = "例子";
$net2ftp_messages["Port"] = "Port";
$net2ftp_messages["Username"] = "用户名";
$net2ftp_messages["Password"] = "密码";
$net2ftp_messages["Anonymous"] = "匿名";
$net2ftp_messages["Passive mode"] = "Passive 模式";
$net2ftp_messages["Initial directory"] = "初始目录";
$net2ftp_messages["Language"] = "语言";
$net2ftp_messages["Skin"] = "皮肤";
$net2ftp_messages["FTP mode"] = "FTP 模式";
$net2ftp_messages["Automatic"] = "Automatic";
$net2ftp_messages["Login"] = "登入";
$net2ftp_messages["Clear cookies"] = "清除cookies";
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
$net2ftp_messages["Username"] = "用户名";
$net2ftp_messages["Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your session has expired; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue."] = "Your IP address has changed; please enter your password for FTP server <b>%1\$s</b> to continue.";
$net2ftp_messages["Password"] = "密码";
$net2ftp_messages["Login"] = "登入";
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
$net2ftp_messages["Create new directories"] = "新建目录";
$net2ftp_messages["The new directories will be created in <b>%1\$s</b>."] = "新目录将创建在 <b>%1\$s</b>.";
$net2ftp_messages["New directory name:"] = "新目录名称:";
$net2ftp_messages["Directory <b>%1\$s</b> was successfully created."] = "目录 <b>%1\$s</b> 被成功创建.";
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
$net2ftp_messages["Rename directories and files"] = "重命名目录和文件";
$net2ftp_messages["Old name: "] = "旧名称: ";
$net2ftp_messages["New name: "] = "新名称: ";
$net2ftp_messages["The new name may not contain any dots. This entry was not renamed to <b>%1\$s</b>"] = "新名称不能含有点号. 无法被重命名为 <b>%1\$s</b>";
$net2ftp_messages["The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>"] = "The new name may not contain any banned keywords. This entry was not renamed to <b>%1\$s</b>";
$net2ftp_messages["<b>%1\$s</b> was successfully renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> 被成功重命名为 <b>%2\$s</b>";
$net2ftp_messages["<b>%1\$s</b> could not be renamed to <b>%2\$s</b>"] = "<b>%1\$s</b> 无法被重命名为 <b>%2\$s</b>";

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
$net2ftp_messages["Set all targetdirectories"] = "设置所有的目标目录";
$net2ftp_messages["To set a common target directory, enter that target directory in the textbox above and click on the button \"Set all targetdirectories\"."] = "设置一个一般的目录, 在上面的输入框中输入目标目录的名称，然后点击按钮 \"设置所有的目标目录\".";
$net2ftp_messages["Note: the target directory must already exist before anything can be copied into it."] = "注意: 在复制任何内容之前，必须保证目标目录已经存在.";
$net2ftp_messages["Unzip archive <b>%1\$s</b> to:"] = "Unzip archive <b>%1\$s</b> to:";
$net2ftp_messages["Target directory:"] = "目标目录:";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "使用的目录名 (子目录自动被创建)";

} // end unzip


// -------------------------------------------------------------------------
// Update file module
if ($net2ftp_globals["state"] == "updatefile") {
// -------------------------------------------------------------------------
$net2ftp_messages["Update file"] = "更新文件";
$net2ftp_messages["<b>WARNING: THIS FUNCTION IS STILL IN EARLY DEVELOPMENT. USE IT ONLY ON TEST FILES! YOU HAVE BEEN WARNED!"] = "<b>警告: 此功能仍在发展中. 仅限于测试使用! 请小心!";
$net2ftp_messages["Known bugs: - erases tab characters - doesn't work well with big files (> 50kB) - was not tested yet on files containing non-standard characters</b>"] = "已知错误: - 删除tab 字符 - 无法应用在大文件中 (> 50kB) - 还未测试那些含有非标准字符的文件。</b>";
$net2ftp_messages["This function allows you to upload a new version of the selected file, to view what are the changes and to accept or reject each change. Before anything is saved, you can edit the merged files."] = "此功能允许你上穿所选定的文件的新版本文件, 查看有哪些内容改动和接受或者拒绝改动. 在保存之前, 你可以编辑合并的文件.";
$net2ftp_messages["Old file:"] = "旧文件:";
$net2ftp_messages["New file:"] = "新文件:";
$net2ftp_messages["Restrictions:"] = "限制:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "文件的最大容量受net2ftp 限制为 <b>%1\$s kB</b> 和受PHP限制为 <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "最长的执行时间为 <b>%1\$s 秒</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "根据文件扩展名，将自动选择FTP模式 (ASCII 或者 BINARY) ";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "如果目标文件已经存在，它将被覆盖";
$net2ftp_messages["You did not provide any files or archives to upload."] = "你没有提交任何要上传的文件或者压缩文档.";
$net2ftp_messages["Unable to delete the new file"] = "无法删除新文件";

// printComparisonSelect()
$net2ftp_messages["Please wait..."] = "请稍候...";
$net2ftp_messages["Select lines below, accept or reject changes and submit the form."] = "请选择下面的内容，接受或者拒绝改动，然后提交表单.";

} // end updatefile


// -------------------------------------------------------------------------
// Upload module
if ($net2ftp_globals["state"] == "upload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Upload to directory:"] = "上传到目录:";
$net2ftp_messages["Files"] = "文件";
$net2ftp_messages["Archives"] = "压缩文件";
$net2ftp_messages["Files entered here will be transferred to the FTP server."] = "在这里输入的文件将被传送到FTP服务器.";
$net2ftp_messages["Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."] = "在这里输入的压缩文件将被解压缩, 文档里的文件将被上传到FTP服务器.";
$net2ftp_messages["Add another"] = "Add another";
$net2ftp_messages["Use folder names (creates subdirectories automatically)"] = "使用的目录名 (子目录自动被创建)";

$net2ftp_messages["Choose a directory"] = "选择一个目录";
$net2ftp_messages["Please wait..."] = "请稍候...";
$net2ftp_messages["Uploading... please wait..."] = "上传中... 请稍候...";
$net2ftp_messages["If the upload takes more than the allowed <b>%1\$s seconds<\/b>, you will have to try again with less/smaller files."] = "如果上传的时间超过允许的时间 <b>%1\$s 秒<\/b>, 你可以把文件减小后再试一试.";
$net2ftp_messages["This window will close automatically in a few seconds."] = "此窗口将在几秒钟后自动关闭.";
$net2ftp_messages["Close window now"] = "现在关闭窗口";

$net2ftp_messages["Upload files and archives"] = "上传文件和压缩文档";
$net2ftp_messages["Upload results"] = "上传的结果";
$net2ftp_messages["Checking files:"] = "查看文件:";
$net2ftp_messages["Transferring files to the FTP server:"] = "正将文件传送到服务器中:";
$net2ftp_messages["Decompressing archives and transferring files to the FTP server:"] = "解压缩文件并传送到FTP服务器上:";
$net2ftp_messages["Upload more files and archives"] = "上传更多的文件和压缩文档";

} // end upload


// -------------------------------------------------------------------------
// Messages which are shared by upload and jupload
if ($net2ftp_globals["state"] == "upload" || $net2ftp_globals["state"] == "jupload") {
// -------------------------------------------------------------------------
$net2ftp_messages["Restrictions:"] = "限制:";
$net2ftp_messages["The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>"] = "文件的最大容量受net2ftp 限制为 <b>%1\$s kB</b> 和受PHP限制为 <b>%2\$s</b>";
$net2ftp_messages["The maximum execution time is <b>%1\$s seconds</b>"] = "最长的执行时间为 <b>%1\$s 秒</b>";
$net2ftp_messages["The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"] = "根据文件扩展名，将自动选择FTP模式 (ASCII 或者 BINARY) ";
$net2ftp_messages["If the destination file already exists, it will be overwritten"] = "如果目标文件已经存在，它将被覆盖";

} // end upload or jupload


// -------------------------------------------------------------------------
// View module
if ($net2ftp_globals["state"] == "view") {
// -------------------------------------------------------------------------

// /modules/view/view.inc.php
$net2ftp_messages["View file %1\$s"] = "查看文件 %1\$s";
$net2ftp_messages["View image %1\$s"] = "查看图像 %1\$s";
$net2ftp_messages["View Macromedia ShockWave Flash movie %1\$s"] = "查看Macromedia ShockWave Flash 影片 %1\$s";
$net2ftp_messages["Image"] = "图像";

// /skins/[skin]/view1.template.php
$net2ftp_messages["Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>"] = "Syntax highlighting powered by <a href=\"http://geshi.org\">GeSHi</a>";
$net2ftp_messages["To save the image, right-click on it and choose 'Save picture as...'"] = "保存图像, 右键点击它, 选择 '保存图像为...'";

} // end view


// -------------------------------------------------------------------------
// Zip module
if ($net2ftp_globals["state"] == "zip") {
// -------------------------------------------------------------------------

// /modules/zip/zip.inc.php
$net2ftp_messages["Zip entries"] = "Zip 输入";

// /skins/[skin]/zip1.template.php
$net2ftp_messages["Save the zip file on the FTP server as:"] = "保存zip文件到FTP服务器中为:";
$net2ftp_messages["Email the zip file in attachment to:"] = "把zip文件作为邮件附件发送到:";
$net2ftp_messages["Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."] = "注意发送文件不是匿名发送: 你的IP地址和发送日期将会自动加入到邮件里.";
$net2ftp_messages["Some additional comments to add in the email:"] = "添加到电子邮件里的一些说明:";

$net2ftp_messages["You did not enter a filename for the zipfile. Go back and enter a filename."] = "没有为zip文件命名. 退回并命名.";
$net2ftp_messages["The email address you have entered (%1\$s) does not seem to be valid.<br />Please enter an address in the format <b>username@domain.com</b>"] = "你输入的邮件地址 (%1\$s) 格式不正确.<br />请输入类似以下格式的邮件地址 <b>username@domain.com</b>";

} // end zip

?>