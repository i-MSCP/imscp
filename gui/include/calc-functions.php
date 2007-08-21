<?php
/**
 *  ispCP (OMEGA) a Virtual Hosting Control Panel
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/



function calc_bars($crnt, $max, $bars_max) {
	if($max != 0) {
		$percent_usage = (100*$crnt)/$max;
	} else {
		$percent_usage = 0;
	}

    $bars = ($percent_usage * $bars_max)/100;

    if ($bars > $bars_max) $bars = $bars_max;

    return array(
                 sprintf("%.2f", $percent_usage),
                 sprintf("%d", $bars)
                );

}

function sizeit($bytes, $from = 'B') {

    switch ($from) {
        case 'PiB':
            $bytes = $bytes * pow(1024, 5);
            break;
        case 'TiB':
            $bytes = $bytes * pow(1024, 4);
            break;
        case 'GiB':
            $bytes = $bytes * pow(1024, 3);
            break;
        case 'MB':
            $bytes = $bytes * pow(1024, 2);
            break;
        case 'KiB':
            $bytes = $bytes * pow(1024, 1);
            break;
        case 'B':
            break;
        default:
            die('FIXME: ' . __FILE__ . ':' . __LINE__);
            break;
    }

    if ($bytes == '' || $bytes < 0 ) {
        $bytes = 0;
    }

    if ($bytes > pow(1024, 5)) {
        $bytes = $bytes/pow(1024, 5);
        $ret   = tr('%.2f PB');
    } else if ($bytes > pow(1024, 4)) {
        $bytes = $bytes/pow(1024, 4);
        $ret   = tr('%.2f TB');
    } else if ($bytes > pow(1024, 3)) {
        $bytes = $bytes/pow(1024, 3);
        $ret   = tr('%.2f GB');
    } else if ($bytes > pow(1024, 2) ) {
        $bytes = $bytes/pow(1024, 2);
        $ret   = tr('%.2f MB');
    } else if ($bytes > pow(1024, 1)) {
        $bytes = $bytes/pow(1024, 1);
        $ret   = tr('%.2f KB');
    } else {
        $ret   = tr('%d B', $bytes);
    }

    return $ret;

}

//
// some password managment.
//

function generate_rand_salt($min = 46, $max = 126) {

    $salt = chr(mt_rand($min, $max));

    $salt .= chr(mt_rand($min, $max));

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

    return ($udata == $crdata);
}

function count_array($array) {

	$count = 0;
	reset($array);

	while(list($key, $val) = each($array)) $count += count($val);

	return $count - 1;

}

function _passgen() {

	global $cfg;

	$pw = '';

	$chars = "2,3,4,7,8,9,A,B,C,D,E,F,G,H,K,M,N,P,R,T,W,U,Y,a,b,c,d,e,f,g,h,k,m,n,p,q,r,t,w,u,y";

  	$chars_array = explode(",", $chars);

	$chars_count = count_array($chars_array);

  	for($i=0; $i < $cfg['PASSWD_CHARS']; $i++) {

  		mt_srand((double)microtime() * 1000000);

    	$z = mt_rand(0, $chars_count);

    	$pw .= "" . $chars_array[$z] . "";

  	}
	return $pw;

}

function passgen() {

    $pw = null;

    while ($pw == null || !chk_password($pw)) {
        $pw = _passgen();
    }

    return $pw;

}

function translate_limit_value($value, $autosize = false)
{
    if ($value == -1) {
        return tr('disabled');
    } else if ($value == 0){
        return tr('unlimited');
    } else {
        if (!$autosize) {
            return $value;
        } else {
            return sizeit($value, 'MB');
        }
    }
}

?>