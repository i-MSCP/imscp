<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

  require_once ("settings.php");
  require_once ("tools/compat.php");
  require_once ("functions-ftp.php");

  // Redirect to the login page if no Session ID is set.
  if (isset($SID))
    session_id("$SID");
  else
  {
    header( "Location: login.php\n\n" );
    exit;
  }

  // Load the session data.
  session_register("sess_Data");
  session_register("theme");
  session_register("personal");

  // Redirect to the login page if the remote IP address doesn't match that
  // specified in the session data.
  if ($REMOTE_ADDR != $sess_Data["IP"]) 
  {
    header( "Location: login.php\n\n" );
    exit;
  }

  // Checks the status of mcrypt and decrypts the password if necessary.  Redirect
  // to the login page if mcrypt is unavailable but not disabled.
  if ( $ftp_disable_mcrypt ) {
    $ftp_Pass = $sess_Data["pass"];
  } elseif ( extension_loaded ($mcrypt_mod) ) {
    $ftp_Pass = decrypt_string($sess_Data["pass"], $key.$REMOTE_ADDR.$HTTP_USER_AGENT, $SID, $pref_ciphers);
  } else {
    header ( "Location: login.php\n\n" );
    exit;
  } 

  // Log into the user's FTP account.
  $fp = ftp_connect ( $sess_Data["server"], $sess_Data["port"]);
  //ftp_login ( $fp, $sess_Data["user"], $ftp_Pass );

  // Attempt to log into your account with the username and password
  $result = @ftp_login ($fp, $sess_Data["user"], $ftp_Pass);
  if ( $result == 0 )
  {
    header ( "Location: login.php?ERROR=3\n\n" );
    exit;
  }

  // Set passive mode if needed.
  if ( $ftp_Passive_Mode ) @ftp_pasv ( $fp, TRUE );
  // Set the user's home directory.
  $home_Dir = ftp_pwd( $fp );
  // Change to the 'current' dir.
  ftp_chdir ( $fp, $sess_Data["dir"] );

  // create the styles for the page
  $style = build_style_sheet( $theme );
  
  $tmp_time = time () - 86400;
  // Set expires header to one day earlier from now, so hopefully browser will not
  //  cache the page.
  header ( "Expires: " . gmdate ("l, d-M-y H:i:s GMT", $tmp_time) . "\n\n");
  header ( "Pragma: nocache" );
?>
