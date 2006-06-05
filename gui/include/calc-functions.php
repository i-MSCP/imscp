<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2004 be moleSoftware		            		|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------



function calc_bars($crnt, $max, $bars_max)
{
	if($max != 0)
    {
		$percent_usage = (100*$crnt)/$max;
	}else
		$percent_usage = 0;
		
    $bars = ($percent_usage * $bars_max)/100;

    if ($bars > $bars_max) $bars = $bars_max;

    return array(
                 sprintf("%.2f", $percent_usage),
                 sprintf("%d", $bars)
                );

}

function sizeit($crnt)
{
    $kb = 1024;

    $mb = 1024*$kb;

    $gb = 1024*$mb;

    $tb = 1024*$gb;

    if (0 <= $crnt && $crnt < $kb) {

        $sz = $crnt; $name = 'B';

    } else if ($kb <= $crnt && $crnt < $mb) {

        $sz = ($crnt/$kb); $name = 'KB';

    } else if ($mb <= $crnt && $crnt < $gb) {

        $sz = ($crnt/$mb); $name = 'MB';

    } else if ($gb <= $crnt && $crnt < $tb) {

        $sz = ($crnt/$gb); $name = 'GB';

    } else if ($tb <= $crnt) {

        $sz = ($crnt/$tb); $name = 'TB';

    }

    return sprintf("%.2f %s", $sz, $name);

}

//
// some password managment.
//

function generate_rand_salt($min = 46, $max = 126) {

    $salt = chr(rand($min, $max));

    $salt .= chr(rand($min, $max));

    return $salt;

}

function get_salt_from($data) {


    $salt = substr($data, 0, 2);

    return $salt;


}

function crypt_user_pass($data) {

	$res = md5($data);
    return $res;

}

function crypt_user_ftp_pass($data) {

    $res = crypt($data, generate_rand_salt());
    return $res;

}


function check_user_pass($crdata, $data ) {

    $salt = get_salt_from($crdata);

    $udata = crypt($data, $salt);

    if ($udata == $crdata) {

        return true;

    } else {

        return false;

    }

}
?>
