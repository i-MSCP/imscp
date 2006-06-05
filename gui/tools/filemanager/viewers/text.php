<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

require_once ( "gettext.php" );

foreach ( array ( "txt", "text" ) as $val )
  $viewers[$val] = "viewer_text";

function viewer_text ( $fp, $filename, $ext, $dir, $query_string ) {
  parse_str ( $query_string );

  if ( isset ($search) ) {
    if ( phpversion() >= "4.0.5" ) {
      $search_term = explode ( " ", rawurldecode ( $search ) );
      foreach ( $search_term as $key => $val )
        $search_replace[$key] = "<span style=\"background-color: yellow\">$val</span>";
    } else {
      $search_term = $search;
      $search_replace = "<span style=\"background-color: yellow\">$search</span>";
    }
  } else
    $search = "";

  $tp = tmpfile ();
  fwrite ( $tp, "<HTML><HEAD>" );
  fwrite ( $tp, sprintf ("<TITLE>WeebleFM %s: %s</TITLE>\n", gettext ("Viewer"), $filename ) );
  fwrite ( $tp, "<STYLE>\n.pre { font-family: monospace; white-space: pre; }\n</STYLE>\n" );
  fwrite ( $tp, "</HEAD><BODY>\n" );
  fwrite ( $tp, sprintf ( "<FORM ACTION=\"viewer.php\" METHOD=\"get\">%s:\n", "Search Terms" ) );
  fwrite ( $tp, "<INPUT TYPE=\"text\" NAME=\"search\" value=\"$search\">\n" );
  fwrite ( $tp, "<INPUT TYPE=\"submit\" name=\"submit\" VALUE=\"Search\">\n" );
  fwrite ( $tp, "<INPUT TYPE=\"hidden\" name=\"Filename\" VALUE=\"$filename\">\n" );
  fwrite ( $tp, "<INPUT TYPE=\"hidden\" name=\"Dir\" VALUE=\"$dir\">\n" );
  fwrite ( $tp, "<INPUT TYPE=\"hidden\" name=\"SID\" VALUE=\"$SID\">\n" );
  fwrite ( $tp, "</FORM>\n" );
  fwrite ( $tp, "<PRE><OL>" );

  while ( !feof ($fp) ) {
    $string = htmlentities ( fgets ($fp, 4096) );
    if ( isset ( $search_term ) )
      $string = str_replace ( $search_term, $search_replace, $string );
    fwrite ( $tp, "<li class=\"pre\">$string</li>" );
  }
  fwrite ( $tp, "</OL></PRE></BODY></HTML>" );

  $file = array ( "text/html", $tp );

  return $file;
}
?>
