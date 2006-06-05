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

  if ( !isset ($Filename) ) {
    $sess_Data["warn"] = sprintf ( "$code_err: $err_file." );
    $sess_Data["level"] = "medium";
    header ( "Location: ftp.php?SID=$SID\n\n" );
    exit;
  }

  $file["name"] = $Filename;
  $file["tmpname"] = ".#tmp.".$Filename;

  printf ( "<HTML><HEAD><TITLE>Weeble File Manager %s</TITLE></HEAD>", gettext ("Editor") );
  printf ( "<FRAMESET ROWS=\"%s,*\">", $personal["prev_size"] );
  printf ( "<FRAME NAME=\"edit_preview\" SRC=\"viewer.php?SID=$SID&Filename=%s\">\n", rawurlencode ($file["tmpname"]) );
  printf ( "<FRAME NAME=\"edit_box\" SRC=\"edit.php?SID=$SID&Filename=%s&PREV=1\">\n", rawurlencode ($file["name"]) );
  print ( "</FRAMESET>\n" );
  print ( "</HTML>" );
?>
