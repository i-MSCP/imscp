<?php
/*
	Weeble File Manager (c) Christopher Michaels & Jonathan Manna
	This software is released under the BSD License.  For a copy of
	the complete licensing agreement see the LICENSE file.
*/

/*
  User 'tunable' variables are defined in this script.  This is the only file 
  that should need to be edited by the user.
*/

/*************************************************************************
	SERVER SETTINGS
*************************************************************************/

  // List of available FTP servers and ports that the users are allowed to
  //  connect to.
  // *NOTE* The setting replaces the old $ftp_Server & $ftp_Port variables.
  $ftp_Servers = array (
    "Local FTP Server" => "localhost:21"
//    "Local By IP" => "127.0.0.1:21",
//    "Example Server" => "ChangeME:21"
  );

  // Setting this to true will try to establish a passive mode FTP connection.
  $ftp_Passive_Mode = TRUE;
  
/*************************************************************************
	DISPLAY SETTINGS
*************************************************************************/

  // Default language
  $default_lang = "en";

  // Error message colors for Warning/Info messages.
  $warn_color = array (
    "info" => "#000000",
    "minor" => "#000055", 
    "medium" => "#555599",
    "major" => "#FF0000"
  );

  // Default theme to be loaded
  $default_theme = "Default";
  
  // Set to true to allow users to customize their color scheme
  $allow_custom = FALSE;
  
  // Default setting for hidden file display.
  $def_Display_Hidden = FALSE;

  // Different picture icons for directory listing.
  $icon_display = array (
    "dirup" => "images/dirup.gif", 
    "home"  => "images/home.gif", 
    "dir"   => "images/dir.gif", 
    "file"  => "images/generic.gif", 
    "php"   => "images/script.gif",
    "zip"   => "images/transfer.gif",
    "gz"    => "images/transfer.gif",
    "tgz"   => "images/transfer.gif",
    "bz2"   => "images/transfer.gif",
    "mp3"   => "images/sound2.gif",
    "wav"   => "images/sound2.gif",
    "txt"   => "images/text.gif",
    "htm"   => "images/layout.gif",
    "html"  => "images/layout.gif",
    "gif"   => "images/image2.gif",
    "jpg"   => "images/image2.gif",
    "jpeg"  => "images/image2.gif",
    "tif"   => "images/image2.gif",
    "tiff"  => "images/image2.gif",
    "png"   => "images/image2.gif",
    "bmp"   => "images/image2.gif"
  );

  // Weeble File Manager Images
  $logo = "images/Logo-Dark.gif";
  $logo_anim = "images/title-anim.gif";
  
  // Set editor defaults.
  $editor_prefs = array (
    "rows" => 100, 
    "cols" => 80, 
    "max_size" => 50000,
    "preview_size" => "25%",
  );

  // This variable allows the admin to choose what columns to display in the file
  // manager and which not to display
  //  owner: owner of the file/dir and the group they are in
  //  date: date and time the file/dir was last modified
  //  size: size of the file/dir
  //  perm: permissions for the file/dir
  $show_col = array(
    "owner" => TRUE,
    "date" => TRUE,
    "size" => TRUE,
    "perm" => TRUE
  );
  
  // allow_chmod when set to TRUE allows users to chmod files and directories,
  // when set to FALSE they will not be able too.
  $allow_chmod = TRUE;

/*************************************************************************
	ENCRYPTION SETTINGS
*************************************************************************/

  // Encryption key used to encrypt ftp passwords.  Please change this!!!
  // Note: The encryption should be in single (') not double (") quotes.
  $key = 'WEEBLEFM';
  
  // Preferred MCRYPT encryption ciphers.  See http://www.php.net/manual/en/ref.mcrypt.php
  // for more information on what is available.
  $pref_ciphers = array ("rijndael-256", "tripledes", "blowfish", "des");
  
  // ***WARNING***CAUTION***WET FLOOR***WARNING****
  // DO NOT SET THIS UNLESS ABSOLUTELY NECESSARY!
  // Setting this variable will allow WeebleFM to run w/o mcrypt support.
  //  Be fully aware that passwords will no longer be encrypted and can 
  //  potentially be read by other users whom have access to the web server.
  $ftp_disable_mcrypt = FALSE;

/*************************************************************************
	VIEWER SETTINGS
*************************************************************************/
  // The maximum file size the viewer will accept.
  $viewer_max_filesize = 5000000;
  
  // Directory where viewer pluggins can be found.
  $viewer_dir = "viewers/";
  
  // Name of the default viewer.  E.g. if you want text.php to handle any
  //  unknown file types set this to "text".
  $viewer_default = "text";

  // Setting this to true will allow the viewer (and edit preview) to render
  // HTML instead of displaying them as plain text.  Set this to FALSE if you
  // Do not want to allow this feature.
  $viewer_allow_html = FALSE;

/*************************************************************************
	MISC. SETTINGS
*************************************************************************/

  // Maximum number of uploads allowed at one time.
  $ftp_max_uploads = 5;
  
  // Set this to true to enable the Remember Me checkbox.
  $ftp_remember_me = TRUE;
  
  // List of allowed/denied ip addresses.  Prefix allowed with "+" and denied with "-".
  // Format: [+,-]IP/MASK ( e.g. -172.16.0.1/255.255.255.0 )
  // Note: Some versions of php4 choke on "255.255.255.255", in those cases
  //  please use "/32" instead.
  $ftp_access_list = array (
    "-172.16.10.8/24",
    "+172.17.45.1/255.255.255.0"
  );

  // Default access setting, if the remote ip doesn't meet any item in the access
  //  list, access will be determined by the setting of this variable.
  $ftp_access = TRUE;
  
  // Configuration for logging of File Manager access
  // dir: directory to store the log file
  // filename: name of log file
  // level: 0 - no logging
  //        1 - login
  //        2 - login, errors

  $log = array(
    "dir" => "/tmp",
    "filename" => "wfm.log",
    "level" => 0
  );
?>
