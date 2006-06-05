<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

  require_once ("settings.php");
  require_once ("tools/compat.php");
  require_once ("functions-ftp.php");
  require_once ("header.php");
  require_once ("gettext.php");
  require_once ("messages.php");

  // Check that a file has been selected, otherwise redirect back to file listing.
  if ( !isset ($Filename) ) {
    $sess_Data["warn"] = sprintf ( "$code_err: $err_file." );
    $sess_Data["level"] = "medium";
    header ( "Location: ftp.php?SID=$SID\n\n" );
    exit;
  }

  $file["name"] = $Filename;
  $file["tmpname"] = ".#tmp.".$Filename;

  // Handling of tmp files for preview.
  if ( isset ($PREV) ) {
    $name = "tmpname";
  } else {
    $name = "name";
  }

  // If a new file is being created, set the content of the textbox to an empty
  // string.
  if ( isset($NEWFILE)){
    $file["content"] = "";
  }    
  else
  {
    $file["size"] = ftp_size ($fp, $file[$name]);

    if ( $file["size"] <= $editor_prefs["max_size"] ) {
      $tp = tmpfile ();
      $result = @ftp_fget ($fp, $tp, $file[$name], FTP_BINARY);
      if ( $result ) {
       rewind ($tp);
       $file["content"] = "";
       while ( !feof ($tp) ) {
         $file["content"] .= fread ($tp, 8192);
       }
       $file["content"] = htmlentities ($file["content"]);
       fclose ($tp);
     } else {
       $sess_Data["warn"] = sprintf ( "$code_err: $err_edit.", $file["name"] );
       $sess_Data["level"] = "major";
       header ( "Location: ftp.php?SID=$SID\n\n" );
       exit;
     }
   } else {
     $sess_Data["warn"] = sprintf ( "$code_err: $err_max.", $file["name"] );
     $sess_Data["level"] = "major";
     header ( "Location: ftp.php?SID=$SID\n\n" );
     exit;
   }
  }
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>VHCS File Manager</title>
<link href="/vhcs2/tools/filemanager/themes/vhcs.css" rel="stylesheet" type="text/css">

</head>

<body>
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="17"><img src="/vhcs2/themes/modern_blue/images/top/left.jpg" width="17" height="80"></td>
          <td width="198" align="center" background="/vhcs2/themes/modern_blue/images/top/logo_background.jpg"><img src="/vhcs2/images/isp_logo.gif"></td>
          <td background="/vhcs2/themes/modern_blue/images/top/left_fill.jpg"><img src="/vhcs2/themes/modern_blue/images/top/left_fill.jpg" width="2" height="80"></td>
          <td width="766"><img src="/vhcs2/themes/modern_blue/images/top/middle_background.jpg" width="766" height="80"></td>
          <td background="/vhcs2/themes/modern_blue/images/top/right_fill.jpg"><img src="/vhcs2/themes/modern_blue/images/top/right_fill.jpg" width="3" height="80"></td>
          <td width="9"><img src="/vhcs2/themes/modern_blue/images/top/right.jpg" width="9" height="80"></td>
        </tr>
    </table></td>
  </tr>
  <tr background="images/content_background_mainpage.gif">
    <td valign="top">


  <table border=0 align="center" cellpadding=2 cellspacing=0>
<?php
  if ( $sess_Data["warn"] != "" )
  {
    print ( "<TR><TH>" );
    print ( "<CENTER><B><FONT color=". $warn_color[$sess_Data["level"]] . ">" );
    printf ( "%s", gettext ($sess_Data["warn"]) );
    print ( "</FONT></B></CENTER>\n" );
    print ( "<BR></TH></TR>" );

    $sess_Data["warn"] = "";
  }
?>   
  <tr>
    <td class="border">
      <table width="100%"  border="0" cellspacing="2" cellpadding="1">
        <tr>
          <td align="right"><span class="content"><a href="crossover.php?SID=<?php echo $SID ?>&submit=LOGOUT"><b>Logout</b></a></span></td>
        </tr>
      </table>
      <table cellspacing=2 cellpadding=1 border=0 width="100%" class="manager">
      <form name="form_listing" method="post" action="crossover.php" enctype="multipart/form-data" TARGET="_top">
      <input type=hidden name="SID" value="<?php echo $SID ?>">
      <input type=hidden name="EDITOR" value="CANCEL">
      <input type=hidden name="FILE" value="<?php echo $file["name"] ?>">
      <tr>
       <td class="content" colspan=2><?php printf ( "%s @ %s", $sess_Data["user"], $sess_Data["Server Name"] ) ?></td>
      </tr>
      <tr>
       <td class="content2"><?php printf ( "%s:", gettext ("Current File") ) ?> 
       <input type="text" class="textinput" value="<?php printf ( "%s/%s", $sess_Data["dir"], $file["name"] ) ?>" size=40 readonly></td>
       <td align="right" class="content2"><input name="submit" type="submit" class="button" value="<?php printf ( "%s", gettext ("Cancel") ) ?>"></td>
      </tr>
      </form>
     </table>
    </td>
   </tr>
   <form name="form_listing" method="post" action="crossover.php" enctype="multipart/form-data" TARGET="_top">
   <tr>
    <td class="content3"><textarea name="EDITOR" cols=<?php print ( $personal["edit_row"] ) ?> rows=<?php print ( $personal["edit_col"] ) ?>><?php print ( $file["content"] ) ?></textarea></td>
   </tr>
   <tr class="buttonBar">
    <td align="center" class="content2">
     <input type="submit" name="submit" value="<?php printf ( "%s", gettext ("Save") ) ?>"> | | 
     <input type="submit" name="submit" value="<?php printf ( "%s", gettext ("Save & Edit") ) ?>"> | | 
     <input type="submit" name="submit" value="<?php printf ( "%s", gettext ("Preview") ) ?>"> | | 
     <input type="reset" value="<?php printf ( "%s", gettext ("Reset") ) ?>">
    </td>
   </tr>
   <input type=hidden name="FILE" value="<?php echo $file["name"] ?>">
   <input type=hidden name="SID" value="<?php echo $SID ?>">
   </form>
</table>




</td>
  </tr>
  <tr>
    <td height="71" background="images/background_down.gif"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr><td width="17"><img src="/vhcs2/themes/modern_blue/images/top/down_left.jpg" width="17" height="71"></td><td width="198" valign="top" background="/vhcs2/themes/modern_blue/images/top/downlogo_background.jpg"><table width="100%" border="0" cellpadding="0" cellspacing="0" >
          <tr>
            <td width="55"><a href="http://www.vhcs.net" target="_blank"><img src="/vhcs2/themes/modern_blue/images/vhcs.gif" alt="" width="51" height="71" border="0"></a></td>
            <td class="bottom">VHCS FileManager by <br>Jon Manna & Chris Michaels</td>
          </tr>
        </table>          </td>
          <td background="/vhcs2/themes/modern_blue/images/top/down_left_fill.jpg"><img src="/vhcs2/themes/modern_blue/images/top/down_left_fill.jpg" width="2" height="71"></td><td width="766" background="/vhcs2/themes/modern_blue/images/top/middle_background.jpg"><img src="/vhcs2/themes/modern_blue/images/top/down_middle_background.jpg" width="766" height="71"></td>
          <td background="/vhcs2/themes/modern_blue/images/top/down_right_fill.jpg"><img src="/vhcs2/themes/modern_blue/images/top/down_right_fill.jpg" width="3" height="71"></td>
          <td width="9"><img src="/vhcs2/themes/modern_blue/images/top/down_right.jpg" width="9" height="71"></td></tr>
    </table></td>
  </tr>
</table>
</body>
</html>
