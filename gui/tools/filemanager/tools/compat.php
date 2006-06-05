<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

// bool compat_array_key_exist ( string key, array search )
// The function to search an array for a string has a couple different iterations
//  depending on the php version.  This attempts to compensate for that.
function compat_array_key_exists ( $key, $search )
{
  if ( phpversion() >= "4.1.0" )
    return array_key_exists( $key, $search );
  else if ( phpversion() >= "4.0.6" && phpversion() < "4.1.0" )
    return key_exists( $key, $search );
  else
  {
    $search = array_keys($search);
    for ( $i = 0; $i < count( $search ); $i++ ) 
       if ( $key == $search[$i] )
        return TRUE;
    return FALSE; 
  }
}

// string compat_str_pad ( string input, int pad_length [, string pad_string [, int pad_type]])
// The function to pad a string has a couple different iterations depending
//  on the php version (doesn't even exist in 4.0.0).  This attempts to compensate for that.
function compat_str_pad ( $input, $pad_length ) {
  if ( !defined ("STR_PAD_LEFT") ) define ("STR_PAD_LEFT", 0);
  if ( !defined ("STR_PAD_RIGHT") ) define ("STR_PAD_RIGHT", 1);
  if ( !defined ("STR_PAD_BOTH") ) define ("STR_PAD_BOTH", 2);

  if ( func_num_args() >= 3 ) $pad_string = func_get_arg(2);
  else $pad_string = " ";

  if ( func_num_args() >= 4 ) $pad_type = func_get_arg(3);
  else $pad_type = STR_PAD_RIGHT;

  if ( phpversion() >= "4.0.2" ) return str_pad ( $input, $pad_length, $pad_string, $pad_type );
  else {
    $pad_diff = $pad_length - strlen ( $input );

    $tmp_pad = "";
    while ( strlen ( $tmp_pad ) < $pad_diff ) {
      $tmp_pad .= $pad_string;
    }
    if ( strlen ( $tmp_pad ) > $pad_diff ) $tmp_pad = substr ( $tmp_pad, 0, $pad_diff );

    switch ($pad_type) {
      case STR_PAD_BOTH:
        $pad_right = substr ( $tmp_pad, 0, ceil ( $pad_diff / 2 ) );
        $pad_left = substr ( $tmp_pad, 0, floor ( $pad_diff / 2 ) );
        $input = $pad_left . $input . $pad_right;
        break;
      case STR_PAD_LEFT:
        $input = $tmp_pad . $input;
        break;
      case STR_PAD_RIGHT:
      default:
        $input = $input . $tmp_pad;
        break;
    }
    return $input;
  }
}

/* Userland test for uploaded file. */
if ( !function_exists ("is_uploaded_file") ) {
  function is_uploaded_file($filename) {
    return TRUE;
  }
}

?>
