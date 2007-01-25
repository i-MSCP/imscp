<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

  require_once ("settings.php");
  require_once ("tools/compat.php");
  require_once ("functions-ftp.php");
  require_once ("access_list.php");
  require_once ("gettext.php");

  $cookie_array = array ( "", "", "" );
  $cookie_present = FALSE;

  if ( $ftp_disable_mcrypt ) {
    $ftp_remember_me = FALSE;
  } elseif ( extension_loaded ($mcrypt_mod) ) {
    if ( isset ($nocookie) ) {
      setcookie ( "WeebleFM_cookie", "", time(), "/", $HTTP_SERVER_VARS["SERVER_NAME"], 0);
      setcookie ( "WeebleFM_SID", "", time(), "/", $HTTP_SERVER_VARS["SERVER_NAME"], 0);
      setcookie ( "WeebleFM_Server", "", time(), "/", $HTTP_SERVER_VARS["SERVER_NAME"], 0);
    } elseif ( isset ($WeebleFM_cookie) && isset ($WeebleFM_SID) ) {
      $cookie_string = decrypt_string ( $WeebleFM_cookie, $key, $WeebleFM_SID, $pref_ciphers );
      $cookie_array = explode ( "::", $cookie_string, 2 );
      if ( isset ($WeebleFM_Server) ) $cookie_array[2] = $WeebleFM_Server;
      $cookie_present = TRUE;
    }
  } else {
    if (!isset ($ERROR)) $ERROR = 20;
    $ftp_remember_me = FALSE;
  }

  // If register_globals = off display an error.
  if ( !ini_get ("register_globals") && !isset ($ERROR) ) $ERROR = 21;
  elseif ( (phpversion() >= "4.0.3") && !ini_get ("file_uploads") && !isset ($ERROR) ) $ERROR = 22;
  elseif ( !extension_loaded ("ftp") && !isset ($ERROR) ) $ERROR = 23;
  elseif ( !isset ($ftp_Servers) && !isset ($ERROR) ) $ERROR = 10;

  // Load the default theme into the login page.
  if ( @is_readable( "themes/" . $default_theme . ".thm" ) ) {
    $tp = fopen( "themes/" . $default_theme . ".thm", 'r' );
    $theme = load_theme( $tp );
    fclose ($tp);
	$style = build_style_sheet( $theme );
  }

  if (!isset($ERROR) ) $ERROR = 0;
?>
<html>
<head>
<title>VHCS File Manager</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="/vhcs2/themes/modern_blue/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="/vhcs2/themes/modern_blue/css/css/vhcs.js"></script>
</head>
<body text="#000000">
<table width="100%" height="99%"  border="00" cellpadding="0" cellspacing="0" bgcolor="#334163">
  <tr>
    <td height="551"><table width="100%"  border="00" cellpadding="0" cellspacing="0">
      <tr bgcolor="#334163">
        <td>&nbsp;</td>
        <td align="right">&nbsp;</td>
      </tr>
      <tr>
        <td width="1" background="/vhcs2/themes/modern_blue/images/login/content_background.gif"><img src="/vhcs2/themes/modern_blue/images/login/content_background.gif" width="1" height="348"></td>
        <td height="348" align="center" background="/vhcs2/themes/modern_blue/images/login/content_background.gif">


<form name="form_Login" method="post" action="check_login.php">
  <p>&nbsp;</p>
  <table align="center" cellspacing="7">
    <tr >
      <td width="109"><div class="login_text"><strong><?php printf ( "%s:", gettext ("Username") ) ?></strong></div></td>
      <td width="209"><input name="ftp_User" type="text" style="width:210px" class="textinput" value="<?php echo $cookie_array[0] ?>" size="20">
      </td>
    </tr>
    <tr>
      <td><div class="login_text"><strong><?php printf ( "%s:", gettext ("Password") ) ?></strong></div></td>
      <td><input name="ftp_Pass" type="password" style="width:210px" class="textinput" value="<?php echo $cookie_array[1] ?>" size="20">
      </td>
    </tr>
    <?php
  if ( isset ($ftp_Servers) ) {
    if ( count( $ftp_Servers) == 1) {
  		reset( $ftp_Servers );
  		printf ( "<input type=\"hidden\" name=\"login_server\" value=\"%s\">", key( $ftp_Servers ) );
  	} else {
    	printf ( "<tr><td align=\"left\" ><div class=\"login_text\"><strong>%s:</strong></div></td>", gettext ("Server") ) ;
    	print ( "<td><select name=\"login_server\">" );
    	while ( list ($key, $val) = each ($ftp_Servers) ) {
      	printf ( "<option value=\"%s\"", $key );
      	if ( $cookie_array[2] == $key ) print ( " SELECTED" );
      	printf ( ">%s</option>", $key );
    	}
    	print ( "</select></td></tr>" );
    }
  }
?>
    <tr>
      <td colspan=2 align="center" class="buttonBar">
          <input type="submit" name="Submit" value="<?php printf ( "%s", gettext ("Login") ) ?>" <?php if ($ERROR >= 10) print ( "DISABLED" ) ?>>
          <input type="reset" name="Reset" value="<?php printf ( "%s", gettext ("Reset") ) ?>">
      </td>
    </tr>
    <tr>
      <td colspan=2 align="center">
        <?php
          $cookie_checked = "";
          if ( $cookie_present ) $cookie_checked = " CHECKED";
          if ( !$ftp_remember_me ) $cookie_checked = " DISABLED";
          printf ( "<input type=\"checkbox\" name=\"ftp_Remember\" value=\"TRUE\" %s> %s\n", $cookie_checked, gettext ("Remember Me") );

          // Pass the language if it's set.
          if ( isset ( $_GET ) && compat_array_key_exists ( 'lang', $_GET ) )
            printf ( "<input type=\"hidden\" name=\"lang\" value=\"%s\">", $_GET['lang'] );
        ?>
      </td>
    </tr>
<?php
  if ( $cookie_present == TRUE ) {
    print ( "    <tr class=\"alt_row\">" );
    print ( "      <td colspan=2 align=\"center\" style=\"font-size: smaller\">" );
    printf ( "        <A href=\"%s?nocookie=1\">%s</A>", $PHP_SELF, gettext ("Remove Login Cookie") );
    print ( "      </td>" );
    print ( "    </tr>" );
  }
?>
  </table>
  <p>&nbsp;</p>
</form>
<P align="center">
  <?php
    /*
      Error message definitions:
          0 = No error
       1- 9 = Non-fatal errors, login will still be allowed.
      10-19 = Fatal: Configuration (settings.php) based errors.
      20-29 = Fatal: PHP based errors (e.g. required module isn't installed.
      30-39 = Fatal: UnKnown
         99 = Fatal: Access Denied by configuration.
    */
    switch ( $ERROR )
    {
      case 1:
        printf ( "<B>%s.</B>", gettext ("Missing username or password") );
        break;
      case 2:
        printf ( "<B>%s.</B>", gettext ("Server could not be found") );
        break;
      case 3:
        printf ( "<B>%s.</B>", gettext ("Incorrect username or password") );
        break;
      case 10:
        printf ( "<B>%s.</B>", gettext ("No FTP servers have been defined") );
        printf ( "<BR><B>%s.</B>", gettext ("Please contact your system administrator to correct this error") );
        break;
      case 20:
        printf ( "<B><EM>%s.</EM></B>", gettext ("Encryption (mcrypt) support is broken or not compiled into PHP4") );
        printf ( "<BR><B>%s.</B>", gettext ("Please contact your system administrator to correct this error") );
        break;
      case 21:
        printf ( "<B><EM>register_globals=off</EM></B>" );
        printf ( "<BR><B>%s.</B>", gettext ("Please contact your system administrator to correct this error") );
        break;
      case 22:
        printf ( "<B><EM>file_uploads=off</EM></B>" );
        printf ( "<BR><B>%s.</B>", gettext ("Please contact your system administrator to correct this error") );
        break;
      case 23:
        printf ( "<B><EM>%s.</EM></B>", gettext ("FTP module is broken or not compiled into PHP4") );
        printf ( "<BR><B>%s.</B>", gettext ("Please contact your system administrator to correct this error") );
        break;
      case 99:
        printf ( "<B><EM>%s.</EM></B>", gettext ("Access denied by WeebleFM configuration") );
        printf ( "<BR><B>%s.</B>", gettext ("Please contact your system administrator to correct this error") );
        break;
    }
  ?>






</td>
      </tr>
      <tr>
        <td width="1" height="2" background="/vhcs2/themes/modern_blue/images/login/content_down.gif"><img src="/vhcs2/themes/modern_blue/images/login/content_down.gif" width="2" height="2"></td>
        <td height="2" background="/vhcs2/themes/modern_blue/images/login/content_down.gif"><img src="/vhcs2/themes/modern_blue/images/login/content_down.gif" width="2" height="2"></td>
      </tr>
      <tr>
        <td width="1" bgcolor="#334163">&nbsp;</td>
        <td bgcolor="#334163"><a href="http://www.isp-control.net" target="_blank"><img src="/vhcs2/themes/modern_blue/images/login/vhcs_logo.gif" alt="VHCS OMEGA - Virtual Hosting Control System - OMEGA Version!" width="68" height="60" border="0"></a><br><span class="login_bottom">VHCS FileManager by <br>Jon Manna & Chris Michaels</span></td>
        </tr>
    </table></td>
  </tr>
</table>
</body>
</html>
