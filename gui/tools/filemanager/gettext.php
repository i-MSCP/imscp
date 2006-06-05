<?php

/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

  require_once ("tools/compat.php");

  if ( isset ( $_GET ) && compat_array_key_exists ( 'lang', $_GET ) ) {
    $iso_code = $_GET['lang'];
    if ( isset ( $personal ) )
      $personal['language'] = $_GET['lang'];
  } elseif ( isset ( $personal ) && compat_array_key_exists ( 'language', $personal ) ) {
    $iso_code = $personal['language'];
  } else {
    $iso_code = $default_lang;
  }

  putenv ( 'LANGUAGE='.$iso_code );
  putenv ( 'LC_ALL='.$iso_code );

  $locale_path = dirname ( $_SERVER['SCRIPT_FILENAME'] ) . '/lang';

  bindtextdomain ( 'messages', $locale_path );
  textdomain ( 'messages' );

  // need to write some kinda of substitute for gettext() if it doesn't exist, 
  // even if all it does is return the test passed to it.  Otherwise we'll
  // be dependant on gettext().

?>
