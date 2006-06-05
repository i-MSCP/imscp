<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

  require_once ( "settings.php" );
  require_once ( "tools/compat.php" );
  require_once ( "functions-ftp.php" );
  require_once ( "access_list.php" );
  require_once ( "gettext.php" );
  require_once ( "messages.php" );

  // Set language if it was passed to the script.
  if ( isset ( $_POST ) && compat_array_key_exists ( 'lang', $_POST ) )
    $lang_loc = "&lang=".$_POST['lang'];
  else
    $lang_loc = "";

  // Check for username and password. If they don't exist then redirect them back to the login page
  if ( empty( $ftp_User ) || empty( $ftp_Pass ) )
  {
    header ( "Location: login.php?ERROR=1\n\n" );
    exit;
  }
  
  if ( isset ($login_server) && compat_array_key_exists ($login_server, $ftp_Servers) ) {
    $ftp_Server = strtok ($ftp_Servers[$login_server], ":");
    $ftp_Port = strtok ( ":" );
    if ( empty ($ftp_Port) ) $ftp_Port = 21;
  } else {
    header ( "Location: login.php?ERROR=2\n\n" );
    exit;
  }

  // attempt the initial login to the server
  $fp = ftp_connect ( $ftp_Server, $ftp_Port );
  if ( $fp == 0 )
  {
    header ( "Location: login.php?ERROR=2\n\n" );
    exit;
  }
  
  // attempt to log into your account with the username and password
  $result = @ftp_login ($fp, $ftp_User, $ftp_Pass);
  if ( $result == 0 )
  {
    header ( "Location: login.php?ERROR=3\n\n" );
    exit;
  }

  // create session variable to hold session data
  session_start();
  session_register("sess_Data");
  session_register("theme");
  session_register("personal");
 
  // Check if mcrypt is enabled/avaialble and encrpyt password.
  if ( $ftp_disable_mcrypt ) {
    $enc_ftp_Pass = $ftp_Pass;
  } elseif ( extension_loaded ($mcrypt_mod) ) {
    $enc_ftp_Pass = encrypt_string ( $ftp_Pass, $key.$REMOTE_ADDR.$HTTP_USER_AGENT, session_id(), $pref_ciphers );
  } else {
    header ( "Location: login.php\n\n" );
    exit;
  } 

  // default settings loaded, may be overwritten when preferences get loaded
  // to handle older .wfmrc files
  $personal["language"] = $default_lang;
  $personal["prev_size"] = $editor_prefs["preview_size"];
  $personal["edit_col"] = $editor_prefs["cols"];
  $personal["edit_row"] = $editor_prefs["rows"];
  $personal["show_hidden"] = FALSE;

  // load users theme if allow_custom setting is on
  if ( $allow_custom )
  {
    // load the .wfmrc file, then load the appropriate theme
	// overwrites default settings set earlier
	$personal = array_merge( $personal, load_settings( $fp, $default_theme, $def_Display_Hidden, $numeric_val ) );

    // if the user specified to start in a different dir from his home dir
    if ( compat_array_key_exists ( "start_dir", $personal ) && $personal["start_dir"] != "" )
    {
      $location =  "Location: crossover.php?SID=" . session_id() . "&submit=CD&CHDIR=$personal[start_dir]".$lang_loc;
    }  
  }
  $theme = find_theme( $fp, $personal, $default_theme );

  // Following variables are stored in an array in the session in this order
  // user - user who is logging in
  // pass - users encrypted (if supported) password
  // server - server connected too
  // port - port connected on
  // dir - current directory
  // warn - warning messagez
  // IP - remote IP address
  // level - warning severity level
  // Server Name - Display name for the ftp server (selected from the login dropdown)

  $sess_Data = array( "user" => $ftp_User, "pass" => $enc_ftp_Pass,
  "server" => $ftp_Server, "port" => $ftp_Port, "dir" => ftp_pwd( $fp ), 
  "warn" => "", "level" => 0, "Server Name" => $login_server, "IP" => $REMOTE_ADDR );
  
  if ( (isset ($ftp_Remember)) && ($ftp_remember_me) ) {
    $cookie_string = $ftp_User . "::" . $ftp_Pass;
    $cookie_string = encrypt_string ( $cookie_string, $key, session_id(), $pref_ciphers );
    setcookie ( "WeebleFM_cookie", $cookie_string, time() + 31536000, "/", $HTTP_SERVER_VARS["SERVER_NAME"], 0);
    setcookie ( "WeebleFM_SID", session_id(), time() + 31536000, "/", $HTTP_SERVER_VARS["SERVER_NAME"], 0);
    setcookie ( "WeebleFM_Server", $login_server, time() + 31536000, "/", $HTTP_SERVER_VARS["SERVER_NAME"], 0);
  }
  
  // Set passive mode if desired.
  if ( $ftp_Passive_Mode ) {
    $result = @ftp_pasv ( $fp, TRUE );
    if ( $result == 0 ) {
      $sess_Data["warn"] = sprintf ( "$code_err: $err_ftp." );
      $sess_Data["level"] = "medium";
    }
  }

  // log sign in if set too
  if ( $log["level"] > 0 )
  	log_message( $log, $ftp_User . "/" . $REMOTE_ADDR . " - Logged in.\n" );
  
  if (!isset($location)) $location = "Location: ftp.php?SID=".session_id().$lang_loc;

  header ($location . "\n\n");
  exit;
?>
