<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

$tar_exec = "/usr/bin/tar";
$gunzip_exec = "/usr/bin/gunzip";

// Register extenstions supported by this viewer.
//  $viewer["ext"] = "function_name";
  $viewers["tar"] = "viewer_tar";
  $viewers["tgz"] = "viewer_tar";
  $viewers["gz"] = "viewer_tar";
  $viewers["z"] = "viewer_tar";
/*
   Actual function that processes the data and sends it to the viewer.  Please use
    the same name used for type as the filename.  E.g. viewer_html is contaned in
    html.php.
  function viewer_TYPE ( $fp, $filename, $ext, $dir, $query_string )
*/
function viewer_tar ( $fp, $filename, $ext, $dir, $query_string ) {
  global $tar_exec, $gunzip_exec, $viewers;
  
  switch ( $ext ) {
    case "tgz":
    case "gz":
      $tar_parms = " -tzf ";
      break;
    default:
      $tar_parms = " -tf ";
  }

  $find_ext = explode( ".", strtolower($filename) );
  $gz_ext = $find_ext[count($find_ext)-2];

  $tmp_name = tempnam ( "/tmp", "tar" );
  $tp = fopen ( $tmp_name, "w+" );
  while ( !feof ( $fp ) )
    fwrite ( $tp, fread ( $fp, 1024 ), 1024 );
  fclose ( $tp );

  $out_fp = tmpfile ();
  if ( $ext == "tar" || $ext == "tgz" || $gz_ext == "tar" ) {
    fwrite ( $out_fp, sprintf ( "%s %s:\n\n", gettext ("Listing of"), $filename ) );
    fwrite ( $out_fp, shell_exec ( $tar_exec.$tar_parms.$tmp_name ) );
    $file = array ( "text/plain", $out_fp );
  } else {
    fwrite ( $out_fp, shell_exec ( $gunzip_exec." -c ".$tmp_name ) );
    rewind ( $out_fp );
    if ( compat_array_key_exists ($gz_ext, $viewers) )
      $use_viewer = $gz_ext;
    else
      $use_viewer = "default";

    $file = $viewers[$use_viewer] ( $out_fp, $filename, $gz_ext, $dir, $query_string );
  }
  unlink ( $tmp_name );

  return $file;
}
?>
