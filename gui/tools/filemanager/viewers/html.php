<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

// This file is not like the standard viewers and is dependant on text.php
// and general.php to function properly.

// Register extenstions supported by this viewer.
//  $viewer["ext"] = "function_name";
 $viewer_html_ext = array ( "html", "htm", "shtml" );

 if ( $viewer_allow_html == TRUE )
   foreach ( $viewer_html_ext as $val )
     $viewers[$val] = "viewer_general";
 else
   foreach ( $viewer_html_ext as $val )
     $viewers[$val] = "viewer_text";

?>
