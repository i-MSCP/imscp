<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

  // User configurable settings.
  // Location of the solutions files (.wts ext).
  $wfm_sol_dir = "./";
  // Title of this document.
  $wfm_title = "Weeble File Manager Help Desk (beta)";
  // HTML tags that allowed in this document.  These do not apply to the 
  // category field.
  $wfm_html_tags = array ( "<P>", "<BR>", "<UL>", "<OL>", "<LI>", "<I>", 
    "<B>", "<U>", "<TT>", "<EM>" );

  // DO NO EDIT BELOW THIS LINE
  include ( "../compat.php" );

  // List of "x-wfm-" types that are currently handled by this script.
  $wfm_types = array ( "version", "keyword", "problem", "solution" );

  // Create a list of the approved HTML tags in both upper and lower case versions.
  reset ( $wfm_html_tags );
  foreach ( $wfm_html_tags as $val ) {
    $tmp_html_tags[] = strtolower ( $val );
    $tmp_html_tags[] = strtoupper ( $val );
  }
  $wfm_html_tags = $tmp_html_tags;
  unset ( $tmp_html_tags );

  // Add a list of cooresponding closing tags.
  reset ( $wfm_html_tags );
  foreach ( $wfm_html_tags as $val )
    $wfm_html_tags[] = substr ( $val, 0, 1 ) . "/" . substr ( $val, 1 );

  // Generate a list of 'BB' tags for each allowed html tag.
  reset ( $wfm_html_tags );
  foreach ( $wfm_html_tags as $key => $val )
    $wfm_bb_tags[$key] = str_replace ( array ( "<", ">" ), array ( "[", "]" ), $val ) ;

  if ( !isset ( $submit ) ) $submit = "";

  // Unset $search if it is empty, or if 'Open' was clicked.
  if ( isset ( $search ) ) {
    if ( empty ( $search ) || $submit == "Open" ) 
      unset ( $search ) ;
    else {
      $search = rawurldecode ( $search );
      $wfm_search_terms = explode ( " ", $search );
      foreach ( $wfm_search_terms as $val ) {
        $wfm_bb_tags[] = $val;
        $wfm_html_tags[] = "<span class=\"highlight\">$val</span>";
      }
    }
  }

  // Unset $id if it is empty, or if 'Search' was clicked.
  if ( isset ( $id ) ) {
    if ( empty ( $id ) || $submit == "Search" ) 
      unset ( $id ) ;
    else 
      $id = rawurldecode ( $id );
  }

  // Get a list of files or load the file specified on the url.
  if ( !isset ( $id ) ) {
    $dp = opendir ( $wfm_sol_dir );
    while ( $file = readdir ( $dp ) ) {
      if ( substr ( $file, strlen ($file) - 4 ) == ".wts" ) $wfm_sol_list[] = $file;
    }
    closedir ( $dp );
  } else {
    $wfm_sol_list[] = $id . ".wts";
  }

  $wfm_docs_found["total"] = 0;
  foreach ( $wfm_sol_list as $sol_file ) {
    unset ( $output );
    // Check that file is readable, if not skip it.
    if ( !is_readable ( $wfm_sol_dir . $sol_file ) ) continue;

    $fp = fopen ( $wfm_sol_dir . $sol_file, "r" );
    if ( $fp == FALSE ) continue;

    $search_found = FALSE;
    while ( !feof( $fp ) && ($fp != FALSE) ) {
      $input_line = fgets ( $fp, 1024 );

      if ( substr ( $input_line, 0, 6 ) == "x-wfm-" ) {
        $input_type = rtrim ( substr ( $input_line, 6 ) );
        $output[$input_type] = "";
      } elseif ( $input_type == "category" ) {
        $output[$input_type] .= rtrim ( $input_line );
        if ( !compat_array_key_exists ($output[$input_type], $wfm_docs_found ) ) $wfm_docs_found[$output[$input_type]] = 0;
      } elseif ( isset ( $search ) ) {
        foreach ( $wfm_search_terms as $val ) {
          $search_count = substr_count ( $input_line, $val );
          if ( $search_count > 0 ) 
            $search_found = TRUE;
        }
        $output[$input_type] .= $input_line;
      } else
        $output[$input_type] .= $input_line;
    }
    
    if ( $search_found == TRUE || !isset ( $search ) ) {
      foreach ( $output as $key => $val ) {
        if ( $key != "category" ) {
          $val = str_replace ( $wfm_html_tags, $wfm_bb_tags, $val );
          $val = htmlentities ( $val );
          $val = str_replace ( $wfm_bb_tags, $wfm_html_tags, $val );
        } else
          $val = htmlentities ( $val );
        $output[$key] = $val;
      }
      if ( !isset ($wfm_sol_files) || !compat_array_key_exists ("category", $output) || !compat_array_key_exists ($output["category"], $wfm_sol_files))
        $wfm_sol_files[$output["category"]] = tmpfile();

      $wfm_docs_found["total"]++;
      $wfm_docs_found[$output["category"]]++;

      $out_id = substr ($sol_file, 0, strlen ($sol_file) - 4);
      fwrite ( $wfm_sol_files[$output["category"]], sprintf ("<P><A NAME=\"%s\"></A>ID: <A HREF=\"$PHP_SELF?id=%s\">%s</A><BR>\n", $out_id, rawurlencode ($out_id), $out_id ) );
      fwrite ( $wfm_sol_files[$output["category"]], sprintf ("Modified: %s<BR>\n", date( "D M j G:i:s T Y", filectime ($wfm_sol_dir . $sol_file) ) ) );
      fwrite ( $wfm_sol_files[$output["category"]], sprintf ("Version(s): %s<BR>\n", $output["version"]) );
      fwrite ( $wfm_sol_files[$output["category"]], sprintf ("Keyword(s): %s</P>\n", $output["keyword"]) );
      fwrite ( $wfm_sol_files[$output["category"]], sprintf ("<H4>Problem:</H4>\n<BLOCKQUOTE>%s</BLOCKQUOTE>\n", text_to_links ($output["problem"])) );
      fwrite ( $wfm_sol_files[$output["category"]], sprintf ("<H4>Solution:</H4>\n<BLOCKQUOTE>%s</BLOCKQUOTE>\n", text_to_links ($output["solution"])) );

      foreach ( $wfm_types as $val )
        unset ( $output[$val] );
      if ( count($output) > 1 ) {
        fwrite ( $wfm_sol_files[$output["category"]], sprintf ("<H4>%s:</H4>", "Additional Information" ) );
        foreach ( $output as $key => $val )
          if ( $key != "category" ) fwrite ( $wfm_sol_files[$output["category"]], sprintf ("<H5>%s:</H5><BLOCKQUOTE>%s</BLOCKQUOTE>", $key, text_to_links ($val) ) );
      }
      fwrite ( $wfm_sol_files[$output["category"]], "<HR>" );
      fflush ( $wfm_sol_files[$output["category"]] );
    }
  }

  // Set expires header to one day earlier from now, so hopefully browser
  //  will not cache the page.
  $tmp_time = time () - 86400;
  header ( "Expires: " . gmdate ("l, d-M-y H:i:s GMT", $tmp_time) . "\n\n");
  header ( "Pragma: nocache" );

  printf ( '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">' );
  printf ( "<HTML><HEAD><TITLE>%s</TITLE>\n", $wfm_title );
  
?>
  <style type="text/css">
   <!--
   a:active { font-weight: bold; text-decoration: none; color: #000066; background-color: #CCCCFF}
   a:hover { color: navy; font-weight: bold; text-decoration: none; background-color: #CCCCFF}
   a:link { text-decoration: none; color: navy}
   a:visited { text-decoration: none; color: navy}
   body {  font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10pt; ; color: #000000; background-color: #FFFFFF}
   table {  font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10pt; ; color: #000000; background-color: #FFFFFF}
   dt {  font-size: larger; font-weight: bolder; font-variant: small-caps}
   .sig {  font-size: 9px; font-style: italic; font-variant: small-caps; border-color: #000099 #FFFFFF #FFFFFF; border-style: solid; border-top-width: thin; border-right-width: thin; border-bottom-width: thin; border-left-width: thin}
   .error { font-family: Courier New, Courier, fixed; }
   .highlight { background-color: yellow; }
   .cat { background-color: #CCCCFF; }
   -->
  </style>
<?php
  if ( !isset ( $search ) ) $search = "";
  if ( !isset ( $id ) ) $id = "";

  printf ( "<BODY>\n<h2>%s</h2>\n", $wfm_title );
  printf ( "<hr><div>%s", "The latest version of this document can be found in our" );
  printf ( " <a href=\"http://sourceforge.net/docman/?group_id=23588\">%s</a>", "Document Manager" );
  printf ( " @ <a href=\"http://www.sourceforge.net/\">%s</a>.</div><hr>\n", "SourceForge" );

  printf ( "<FORM METHOD=\"GET\" ACTION=\"$PHP_SELF\">\n" );
  printf ( "<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=1>\n" );
  printf ( "<TR><TD align=\"right\">%s:</TD>\n", "Search Terms" );
  printf ( "<TD><INPUT TYPE=\"text\" NAME=\"search\" VALUE=\"$search\"></TD>\n" );
  printf ( "<TD><INPUT TYPE=\"submit\" NAME=\"submit\" VALUE=\"%s\"></TD></TR>\n", "Search" );
  printf ( "<TR><TD align=\"right\">\n%s:</TD>\n", "Doc ID" );
  printf ( "<TD><INPUT TYPE=\"text\" NAME=\"id\" VALUE=\"$id\"></TD><TD><INPUT TYPE=\"submit\" NAME=\"submit\" VALUE=\"%s\"></TD></TR>\n", "Open" );
  printf ( "<TR><TD colspan=3></TD></TR>\n");
  printf ( "<TR><TD></TD><TD colspan=2><B>%s</B> %s.</TD></TR></TABLE>\n", $wfm_docs_found["total"], "document(s) found" );
  printf ( "</FORM>" );
  echo "<HR><DIV>";

  if ( isset ($wfm_sol_files) ) {
    ksort ( $wfm_sol_files );
    printf ( "%s: ", "Categories" );
    foreach ( $wfm_sol_files as $key => $val ) 
      printf ( "<A HREF=\"#%s\"><B>%s</B></A> (%s) | ", $key, $key, $wfm_docs_found[$key] );

    echo "</DIV><HR>\n";

    foreach ( $wfm_sol_files as $key => $val ) {
      printf ( "<H3 CLASS=\"cat\"><A NAME=\"%s\"></A>Category: %s</H3>\n", $key, $key );
      rewind ( $val );
      fpassthru ( $val );
    }
  } else {
    printf ( "<P>%s</P>", "No matches found." );
  }

  echo '<p class="sig"><a href="http://weeblefm.sourceforge.net/">Weeble File Manager</a>'; 
  echo ' by Jon Manna &amp; Chris Michaels<br>';
  echo ' Updated May 4, 2002</p>';

  echo "</BODY></HTML>";
?>
<?php
function text_to_links ($data) {
  /*
    This function courtesy of the SourceForge Code Snippet Library and is subject
    to the terms of the GNU General Public License.

    http://sourceforge.net/snippet/detail.php?type=snippet&id=100004
  */
  if(empty($data)) {
    return $data;
  }

  $lines = explode("\n", $data);

  while (list ($key, $line) = each ($lines)) {

    $line = eregi_replace("([ \t]|^)www\.", " http://www.", $line);
    $line = eregi_replace("([ \t]|^)ftp\.", " ftp://ftp.", $line);
	  $line = eregi_replace("((http://|https://|ftp://|news://)[^ )\r\n]+)", "<a href=\"\\1\" target=\"_blank\">\\1</a>", $line);
    $line = eregi_replace("([-a-z0-9_]+(\.[_a-z0-9-]+)*@([a-z0-9-]+(\.[a-z0-9-]+)+))", "<a href=\"mailto:\\1\">\\1</a>", $line);
    
    if (empty($newText))
      $newText = $line;
    else
      $newText .= "\n$line";
  }

  return $newText;
}
?>

