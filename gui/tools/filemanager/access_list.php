<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

  require_once ('settings.php');
  require_once ('tools/compat.php');
  require_once ('functions-ftp.php');
  require_once ('gettext.php');

// string mask_ip ( string ip, string mask )
// Applies a binary subnet mask to a binary ip and returns a binary network id.
function mask_ip ( $ip, $mask ) {
  $netid = "";
  for ( $i = 0; $i < strlen ($ip); $i++ ) {
    $pos_ip = substr ( $ip, $i, 1);
    $pos_mask = substr ( $mask, $i, 1);
    if ( $pos_ip && $pos_mask ) $netid .= "1";
    else $netid .= "0";
  }
  return $netid;
}

// string ip2bin ( string ip )
// Takes an ip (either doted quad or hostname) and converts it to a binary string.
function ip2bin ( $ip ) {
  $long_ip = ip2long ($ip);
  if ( $long_ip == -1 ) {
    $long_ip = ip2long (gethostbyname ($ip));
    if ( $long_ip == -1 ) return FALSE;
  }
  return compat_str_pad (decbin ($long_ip), 32, "0", STR_PAD_LEFT);
}
// string mask2bin ( string subnet_mask )
// Takes a subnet mask (either doted quad or /CERN notation) and converts it
//  to a binary string.
function mask2bin ( $mask ) {
  if ( count ( explode ('.', $mask) ) == 4 ) {
    return ip2long ( $mask );
  } else {
    if ( $mask <= 32 && $mask >= 0 ) {
      $bin_mask = compat_str_pad ( '', $mask, '1' );
      $bin_mask = compat_str_pad ( $bin_mask, 32, "0", STR_PAD_RIGHT );
      return $bin_mask;
    } else {
      return compat_str_pad ( '', 32, '1' );
    }
  }
}

// bool ip_compare ( string access_ip, string remote_ip, string mask )
// Compares to IP addresses using the given subnet mask.  IP's can be in either
//  dotted quad or hostname notation.  Subnet mask can be in dotted quad or /CERN
//  notation.
function ip_compare ( $access_ip, $remote_ip, $mask ) {
  $b_access_ip = ip2bin ($access_ip);
  $b_remote_ip = ip2bin ($remote_ip);
  $b_mask = mask2bin ($mask);

  $b_net_access = mask_ip ($b_access_ip, $b_mask);
  $b_net_remote = mask_ip ($b_remote_ip, $b_mask);

  $d_net_access = bindec ($b_net_access);
  $d_net_remote = bindec ($b_net_remote);

  if ( $d_net_access == $d_net_remote ) return TRUE;
  else return FALSE;
}

// Test the remote_ip address against the access list.
foreach ( $ftp_access_list as $val ) {
  $type = substr ( $val, 0, 1 );
  $ip = substr ( strtok ($val, '/'), 1 );
  $mask = strtok ('/');

  $result = ip_compare ($ip, $REMOTE_ADDR, $mask);
  
  if ( $type == '+' && $result ) {
    $ftp_access = TRUE;
    break;
  } elseif ( $type != '+' && $result ) {
    $ftp_access = FALSE;
    break;
  }
}

// If acces is denied redirect to login and deny login.
if ( $ftp_access == FALSE ) {
  $php_path = explode('/', $PHP_SELF);
  $php_script = strtok ($php_path[count($php_path)-1], '?');
  if ( $php_script == 'login.php' ) $ERROR = 99;
  else {
    header ( "Location: login.php\n\n" );
    exit;
  }
}
?>
