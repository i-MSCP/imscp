<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

// Register extenstions supported by this viewer.
//  $viewer["ext"] = "function_name";
  foreach ( array ( "bmp", "gif", "jpeg",
                  "jpg", "tiff", "png", 
                  "mp3", "mpeg" ) as $val )
  $viewers[$val] = "viewer_general";

/*
   Actual function that processes the data and sends it to the viewer.  Please use
    the same name used for type as the filename.  E.g. viewer_html is contaned in
    html.php.
  function viewer_TYPE ( $fp, $filename, $ext, $dir, $query_string )
*/
function viewer_general ( $fp, $filename, $ext, $dir, $query_string ) {
  switch ( $ext ) {
    case "gif":
      $file[0] = "image/gif";
      break;
    case "jpeg":
    case "jpg":
      $file[0] = "image/jpeg";
      break;
    case "tiff":
      $file[0] = "image/tiff";
      break;
    case "png":
      $file[0] = "image/png";
      break;
    case "html":
    case "htm":
    case "shtml":
      $file[0] = "text/html";
      break;
    case "mp3":
    case "mpeg":
      $file[0] = "audio/mpeg";
      break;
    default:
      $file[0] = "application/octet-stream";
  }

  $file[1] = $fp;
/*
  return array ( "mimetype" , "fp" );
*/
  return $file;
}
?>
