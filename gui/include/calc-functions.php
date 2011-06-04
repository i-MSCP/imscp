<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-MSCP | http://i-mscp.net
 * @version     SVN: $Id$
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/**
 * @param  $crnt
 * @param  $max
 * @param  $bars_max
 * @return array
 */
function calc_bars($crnt, $max, $bars_max)
{
    if ($max != 0) {
        $percent_usage = (100 * $crnt) / $max;
    } else {
        $percent_usage = 0;
    }

    $bars = ($percent_usage * $bars_max) / 100;

    if ($bars > $bars_max) {
        $bars = $bars_max;
    }

    return array(
        sprintf("%.2f", $percent_usage),
        sprintf("%d", $bars)
    );
}

/**
 *
 * @throws iMSCP_Exception
 * @param  $bytes
 * @param string $to
 * @return Translated
 */
function sizeit($bytes, $to = 'B')
{
    switch ($to) {
        case 'PB':
            $bytes = $bytes * pow(1024, 5);
            break;
        case 'TB':
            $bytes = $bytes * pow(1024, 4);
            break;
        case 'GB':
            $bytes = $bytes * pow(1024, 3);
            break;
        case 'MB':
            $bytes = $bytes * pow(1024, 2);
            break;
        case 'KB':
            $bytes = $bytes * pow(1024, 1);
            break;
        case 'B':
            break;
        default:
            write_log(sprintf('FIXME: %s:%d' . "\n" . 'Unknown byte count %s', __FILE__, __LINE__, $to));
            throw new iMSCP_Exception('FIXME: ' . __FILE__ . ':' . __LINE__);
    }

    if ($bytes == '' || $bytes < 0) {
        $bytes = 0;
    }

    if ($bytes > pow(1024, 5)) {
        $bytes = $bytes / pow(1024, 5);
        $ret = tr('%.2f PB', $bytes);
    } elseif ($bytes > pow(1024, 4)) {
        $bytes = $bytes / pow(1024, 4);
        $ret = tr('%.2f TB', $bytes);
    } elseif ($bytes > pow(1024, 3)) {
        $bytes = $bytes / pow(1024, 3);
        $ret = tr('%.2f GB', $bytes);
    } elseif ($bytes > pow(1024, 2)) {
        $bytes = $bytes / pow(1024, 2);
        $ret = tr('%.2f MB', $bytes);
    } elseif ($bytes > pow(1024, 1)) {
        $bytes = $bytes / pow(1024, 1);
        $ret = tr('%.2f KB', $bytes);
    } else {
        $ret = tr('%d B', $bytes);
    }

    return $ret;
}

//
// some password management.
//

/**
 * Generates a random salt for passwords.
 *
 * @param int $min minimum ASCII char
 * @param int $max maximum ASCII char
 * @return string Salt for password
 */
function generate_rand_salt($min = 46, $max = 126)
{
    if (CRYPT_BLOWFISH == 2) { // WTF ? Will never match since value can be 0 or 1
        $length = 13;
        $pre = '$2$';
    } elseif (CRYPT_MD5 == 1) {
        $length = 9;
        $pre = '$1$';
    } elseif (CRYPT_EXT_DES == 1) {
        $length = 9;
        $pre = '';
    } elseif (CRYPT_STD_DES == 1) {
        $length = 2;
        $pre = '';
    }

    $salt = $pre;

    for ($i = 0; $i < $length; $i++) {
        $salt .= chr(mt_rand($min, $max));
    }

    return $salt;
}

/**
 *
 * @param  $data
 * @return string
 */
function get_salt_from($data)
{
    return substr($data, 0, 2);
}

/**
 *
 * @param  $data
 * @return string
 */
function crypt_user_pass($data)
{
    return md5($data);
}

/**
 * Encrypts the FTP user password.
 *
 * @param string $data the password in clear text
 * @return string the password encrypted with salt
 */
function crypt_user_pass_with_salt($data)
{
    return crypt($data, generate_rand_salt());
}

/**
 * Generates random password of size specified in Config Var 'PASSWD_CHARS'
 *
 * @return String password
 */
function _passgen()
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');
    $pw = '';

    for ($i = 0, $passwd_chars = $cfg->PASSWD_CHARS; $i <= $passwd_chars; $i++) {
        do {
            $z = mt_rand(42, 123);
        } while ($z >= 91 && $z <= 96);
        $pw .= chr($z);
    }
    return $pw;
}

/**
 * Generates random password matching the chk_password criteria.
 *
 * @see _passgen()
 * @return String password
 */
function passgen()
{
    $pw = null;

    while ($pw == null || !chk_password($pw, 50, "/[<>]/")) {
        $pw = _passgen();
    }

    return $pw;
}

/**
 * Translates -1, 0 or value string into human readable string.
 *
 * @param int $value variable to be translated
 * @param bool $autosize calculate value in different unit (default false)
 * @param string $to unit to calclulate to (default 'MB')
 * @return String
 */
function translate_limit_value($value, $autosize = false, $to = 'MB')
{
    switch ($value) {
        case -1:
            return tr('disabled');
        case  0:
            return tr('unlimited');
        default:
            return (!$autosize) ? $value : sizeit($value, $to);
    }
}
