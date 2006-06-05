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

  if ( !isset ($Filename) ) {
    printf ( "<HTML><HEAD><TITLE>VHCS File Manager %s: %s</TITLE></HEAD>\n", "Viewer", "Error" );
    printf ( "<BODY><P>%s.</P>\n</BODY></HTML>", "No file was selected" );
    exit;
  }

  if ( isset ($Dir) )
    ftp_chdir ( $fp, $Dir );

  if ( ftp_size ($fp, $Filename) > $viewer_max_filesize ) {
    printf ( "<HTML><HEAD><TITLE>VHCS File Manager %s: %s</TITLE></HEAD>\n", "Viewer", "Error" );
    printf ( "<BODY><P>%s.</P>\n</BODY></HTML>", "File size exceeds maximum allowed in settings" );
    exit;
  }

  // download the file to a temp file.
  $tp = tmpfile ();
  $result = @ftp_fget ($fp, $tp, $Filename, FTP_BINARY);
  rewind ( $tp );

  if ( $result == TRUE ) {
    // rip appart the filename to get the extension
    $find_ext = explode( ".", strtolower($Filename) );
    $ext = $find_ext[count($find_ext)-1];

    // Get a list of viewer files in the viewers directory.
    $dp = opendir ( $viewer_dir );
    while ( $viewer_file = readdir ( $dp ) ) {
      if ( substr ( $viewer_file, strlen ($viewer_file) - 4 ) == ".php" ) $viewer_list[] = $viewer_file;
    }
    closedir ( $dp );

    // Set the default viewer.
    $viewers["default"] = "viewer_". $viewer_default;

    foreach ( $viewer_list as $val ) {
      if ( is_readable ( $viewer_dir . $val ) )
        require_once ( $viewer_dir . $val );
    }

    if ( compat_array_key_exists ($ext, $viewers) )
      $use_viewer = $ext;
    else
      $use_viewer = "default";

    $viewer_return = $viewers[$use_viewer] ( $tp, $Filename, $ext, ftp_pwd ($fp) , $QUERY_STRING );

    header( "Content-Disposition: inline; filename=\"$PHP_SELF\"\n\n");
    $header = "Content-type: ". $viewer_return[0] ."; name=\"$PHP_SELF\"\n\n";
    header ( $header );

    rewind ( $viewer_return[1] );
    fpassthru ( $viewer_return[1] );
  } else {
    printf ( "<HTML><HEAD><TITLE>VHCS File Manager %s: %s</TITLE></HEAD>\n", "Viewer", "Error" );
    printf ( "<BODY><P>%s: %s \"%s\".</P>\n</BODY></HTML>", "Error", "An error occurred while trying to retrieve", $Filename );
    exit;
  }
?>
