<?php

// Samples of FTP LIST responses.

// Some samples are from FileZilla source code, file FtpListResult.cpp
// License: GPL

// Some samples are from http://cr.yp.to/ftpparse/ftpparse.c
// By D. J. Bernstein, djb@cr.yp.to   http://cr.yp.to/ftpparse.html
// License: Commercial use is fine, if you let me know what programs you're using this in.

// Some samples are provided by net2ftp users to the net2ftp development team
// License: GPL

$list_samples["UNIX-style listing, without inum and without blocks"][1] = "-rw-r--r--   1 root     other        531 Jan 29 03:26 README";
$list_samples["UNIX-style listing, without inum and without blocks"][2] = "dr-xr-xr-x   2 root     other        512 Apr  8  1994 etc";
$list_samples["UNIX-style listing, without inum and without blocks"][3] = "dr-xr-xr-x   2 root                  512 Apr  8  1994 etc2";
$list_samples["UNIX-style listing, without inum and without blocks"][4] = "lrwxrwxrwx   1 root     other          7 Jan 25 00:17 bin -> usr/bin";

$list_samples["Some listings with uncommon date/time format"][1] = "-rw-r--r--   1 root     other        531 09-26 2000 README2";
$list_samples["Some listings with uncommon date/time format"][2] = "-rw-r--r--   1 root     other        531 09-26 13:45 README3";
$list_samples["Some listings with uncommon date/time format"][3] = "-rw-r--r--   1 root     other        531 2005-06-07 21:22 README4";
		
$list_samples["Also produced by Microsoft's FTP servers for Windows"][1] = "----------   1 owner    group         1803128 Jul 10 10:18 ls-lR.Z";
$list_samples["Also produced by Microsoft's FTP servers for Windows"][2] = "d---------   1 owner    group               0 May  9 19:45 Softlib";
		
$list_samples["Also WFTPD for MSDOS"][1] = "-rwxrwxrwx   1 noone    nogroup      322 Aug 19  1996 message.ftp";
		
$list_samples["Also NetWare"][1] = "d [R----F--] supervisor            512       Jan 16 18:53    login";
$list_samples["Also NetWare"][2] = "- [R----F--] rhesus             214059       Oct 20 15:27    cx.exe";
	
$list_samples["Also NetPresenz for the Mac"][1] = "-------r--         326  1391972  1392298 Nov 22  1995 MegaPhone.sit";
$list_samples["Also NetPresenz for the Mac"][2] = "drwxrwxr-x               folder        2 May 10  1996 network";

$list_samples["MSDOS format"][1] = "04-27-00  09:09PM       <DIR>          licensed";
$list_samples["MSDOS format"][2] = "07-18-00  10:16AM       <DIR>          pub";
$list_samples["MSDOS format"][3] = "04-14-00  03:47PM                  589 readme.htm";
  
$list_samples["Some other formats some windows servers send"][1] = "-rw-r--r--   1 root 531 Jan 29 03:26 README5";
$list_samples["Some other formats some windows servers send"][2] = "-rw-r--r--   1 group domain user 531 Jan 29 03:26 README6";

$list_samples["EPLF directory listings"][1] = "+i8388621.48594,m825718503,r,s280,\teplf test 1.file";
$list_samples["EPLF directory listings"][2] = "+i8388621.50690,m824255907,/,\teplf test 2.dir";
$list_samples["EPLF directory listings"][3] = "+i8388621.48598,m824253270,r,s612,\teplf test 3.file";

$list_samples["MSDOS type listing used by IIS"][1] = "04-27-00  12:09PM       <DIR>          DOS dir 1";
$list_samples["MSDOS type listing used by IIS"][2] = "04-14-00  03:47PM                  589 DOS file 1";

$list_samples["Another type of MSDOS style listings"][1] = "2002-09-02  18:48       <DIR>          DOS dir 2";
$list_samples["Another type of MSDOS style listings"][2] = "2002-09-02  19:06                9,730 DOS file 2";

$list_samples["Numerical Unix style format"][1] = "0100644   500  101   12345    123456789       filename";

$list_samples["This one is used by SSH-2.0-VShell_2_1_2_143, this is the old VShell format"][1] = "206876  Apr 04, 2000 21:06 VShell (old)";
$list_samples["This one is used by SSH-2.0-VShell_2_1_2_143, this is the old VShell format"][2] = "0  Dec 12, 2002 02:13 VShell (old) Dir/";

$list_samples["This type of directory listings is sent by some newer versions of VShell both year and time in one line is uncommon."][1] = "-rwxr-xr-x    1 user group        9 Oct 08, 2002 09:47 VShell (new)";

$list_samples["Next ones come from an OS/2 server. The server obviously isn't Y2K aware"][1] = "36611      A    04-23-103   10:57  OS2 test1.file";
$list_samples["Next ones come from an OS/2 server. The server obviously isn't Y2K aware"][2] = " 1123      A    07-14-99   12:37  OS2 test2.file";
$list_samples["Next ones come from an OS/2 server. The server obviously isn't Y2K aware"][3] = "    0 DIR       02-11-103   16:15  OS2 test1.dir";
$list_samples["Next ones come from an OS/2 server. The server obviously isn't Y2K aware"][4] = " 1123 DIR  A    10-05-100   23:38  OS2 test2.dir";

$list_samples["Some servers send localized date formats, here the German one"][1] = "dr-xr-xr-x   2 root     other      2235 26. Juli, 20:10 datetest1 (ger)";
$list_samples["Some servers send localized date formats, here the German one"][2] = "-r-xr-xr-x   2 root     other      2235 2.   Okt.  2003 datetest2 (ger)";
$list_samples["Some servers send localized date formats, here the German one"][3] = "-r-xr-xr-x   2 root     other      2235 1999/10/12 17:12 datetest3";
$list_samples["Some servers send localized date formats, here the German one"][4] = "-r-xr-xr-x   2 root     other      2235 24-04-2003 17:12 datetest4";

$list_samples["Here a Japanese one"][1] = "-rw-r--r--   1 root       sys           8473  4\x8c\x8e 18\x93\xfa 2003\x94\x4e datatest1 (jap)";

$list_samples["VMS style listings"][1] = "vms_dir_1.DIR;1  1 19-NOV-2001 21:41 [root,root] (RWE,RWE,RE,RE)";
$list_samples["VMS style listings"][2] = "vms_file_3;1       155   2-JUL-2003 10:30:13.64";

$list_samples["VMS style listings without time"][1] = "vms_file_4;1    2/8    15-JAN-2000    [IV2_XXX]   (RWED,RWED,RE,)";
$list_samples["VMS style listings without time"][2] = "vms_file_5;1    6/8    15-JUI-2002    PRONAS   (RWED,RWED,RE,)";

$list_samples["VMS multiline"][1] = "VMS_file_1;1\r\n170774/170775     24-APR-2003 08:16:15  [FTP_CLIENT,SCOT]      (RWED,RWED,RE,)";
$list_samples["VMS multiline"][2] = "VMS_file_2;1\r\n10			     2-JUL-2003 10:30:08.59  [FTP_CLIENT,SCOT]      (RWED,RWED,RE,)";

$list_samples["IBM AS/400 style listing"][1] = "QSYS            77824 02/23/00 15:09:55 *DIR IBM AS/400 Dir1/";
$list_samples["IBM AS/400 style listing"][2] = "QSYS            77824 23/02/00 15:09:55 *FILE IBM AS/400 File1 strangedate";

$list_samples["aligned directory listing with too long size"][1] = "-r-xr-xr-x longowner longgroup123456 Feb 12 17:20 long size test1";

$list_samples["short directory listing with month name"][1] = "-r-xr-xr-x 2 owner group 4512 01-jun-99 shortdate with monthname";

$list_samples["the following format is sent by the Connect:Enterprise server by Sterling Commerce"][1] = "-C--E-----FTP B BCC3I1       7670  1294495 Jan 13 07:42 ConEnt file";
$list_samples["the following format is sent by the Connect:Enterprise server by Sterling Commerce"][2] = "-C--E-----FTS B BCC3I1       7670  1294495 Jan 13 07:42 ConEnt file2";
$list_samples["the following format is sent by the Connect:Enterprise server by Sterling Commerce"][3] = "-AR--M----TCP B ceunix      17570  2313708 Mar 29 08:56 ALL_SHORT1.zip";

$list_samples["Nortel wfFtp router"][1] = "nortel.wfFtp       1014196  06/03/04  Thur.   10:20:03";

$list_samples["VxWorks based server used in Nortel routers"][1] = "2048    Feb-28-1998  05:23:30   nortel.VwWorks dir <DIR>";

?>